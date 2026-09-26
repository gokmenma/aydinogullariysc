<?php

namespace App\Service;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

final class LogRetentionService
{
    private const LOG_COLUMNS = 'id, type, dates, clock, message, author, user_id, action, event_type, module, entity_type, entity_id, summary, details, level, ip_address, url, method, created_at';

    /** @var array<string, array{active_days:int, archive_days:int, predicate:string}> */
    private const POLICIES = [
        'critical' => [
            'active_days' => 730,
            'archive_days' => 1825,
            'predicate' => "(event_type IN ('delete', 'export', 'error') OR level IN ('WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'))",
        ],
        'authentication' => [
            'active_days' => 180,
            'archive_days' => 730,
            'predicate' => "event_type IN ('login', 'logout') AND COALESCE(level, '') NOT IN ('WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY')",
        ],
        'page_view' => [
            'active_days' => 60,
            'archive_days' => 180,
            'predicate' => "event_type = 'view' AND COALESCE(level, '') NOT IN ('WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY')",
        ],
        'standard' => [
            'active_days' => 365,
            'archive_days' => 1095,
            'predicate' => "(event_type IS NULL OR event_type = '' OR event_type NOT IN ('view', 'login', 'logout', 'delete', 'export', 'error')) AND COALESCE(level, '') NOT IN ('WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY')",
        ],
    ];

    public function __construct(private PDO $db, private int $batchSize = 1000)
    {
        $this->batchSize = max(100, min(5000, $this->batchSize));
    }

    /**
     * @return array{dry_run:bool, archived:int, purged:int, policies:array<string, array<string, int>>, affected_dates:string[]}
     */
    public function run(bool $execute = false): array
    {
        $result = [
            'dry_run' => !$execute,
            'archived' => 0,
            'purged' => 0,
            'policies' => [],
            'affected_dates' => [],
        ];

        if (!$execute) {
            foreach (self::POLICIES as $name => $policy) {
                $result['policies'][$name] = [
                    'archive_candidates' => $this->countCandidates('logs', $policy['predicate'], $policy['active_days']),
                    'purge_candidates' => $this->countCandidates('logs_archive', $policy['predicate'], $policy['archive_days']),
                ];
            }
            return $result;
        }

        $runId = $this->startRun();
        $affectedDates = [];

        try {
            foreach (self::POLICIES as $name => $policy) {
                $archived = $this->archivePolicy($policy, $affectedDates);
                $result['policies'][$name] = [
                    'archived' => $archived,
                    'purged' => 0,
                ];
                $result['archived'] += $archived;
            }

            $dates = array_keys($affectedDates);
            sort($dates);
            $this->refreshDailyStats($dates);
            $result['affected_dates'] = $dates;

            // Günlük özetler aktif + arşiv toplamından üretildikten sonra süresi
            // dolan arşiv kayıtları kaldırılır. Böylece sayısal geçmiş korunur.
            foreach (self::POLICIES as $name => $policy) {
                $purged = $this->purgePolicy($policy);
                $result['policies'][$name]['purged'] = $purged;
                $result['purged'] += $purged;
            }

            $this->finishRun($runId, 'completed', $result);
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->finishRun($runId, 'failed', [
                'archived' => $result['archived'],
                'purged' => $result['purged'],
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** @param array{active_days:int, archive_days:int, predicate:string} $policy */
    private function archivePolicy(array $policy, array &$affectedDates): int
    {
        $total = 0;
        $cutoff = (new DateTimeImmutable())->modify('-' . $policy['active_days'] . ' days')->format('Y-m-d H:i:s');

        do {
            $this->db->beginTransaction();
            $selectSql = "SELECT id, DATE(created_at) AS log_date FROM logs WHERE created_at IS NOT NULL AND created_at < :cutoff AND {$policy['predicate']} ORDER BY id ASC LIMIT {$this->batchSize} FOR UPDATE";
            $select = $this->db->prepare($selectSql);
            $select->execute([':cutoff' => $cutoff]);
            $rows = $select->fetchAll(PDO::FETCH_ASSOC);

            if (!$rows) {
                $this->db->commit();
                break;
            }

            $ids = array_map('intval', array_column($rows, 'id'));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $archiveSql = 'INSERT IGNORE INTO logs_archive (' . self::LOG_COLUMNS . ', archived_at) '
                . 'SELECT ' . self::LOG_COLUMNS . ', NOW() FROM logs WHERE id IN (' . $placeholders . ')';
            $archive = $this->db->prepare($archiveSql);
            $archive->execute($ids);

            $verify = $this->db->prepare("SELECT COUNT(*) FROM logs_archive WHERE id IN ({$placeholders})");
            $verify->execute($ids);
            if ((int) $verify->fetchColumn() !== count($ids)) {
                throw new RuntimeException('Arşiv doğrulaması başarısız oldu; aktif kayıtlar korunarak işlem durduruldu.');
            }

            $delete = $this->db->prepare("DELETE FROM logs WHERE id IN ({$placeholders})");
            $delete->execute($ids);
            if ($delete->rowCount() !== count($ids)) {
                throw new RuntimeException('Aktif log temizliği beklenen kayıt sayısıyla eşleşmedi.');
            }

            $this->db->commit();
            foreach ($rows as $row) {
                if (!empty($row['log_date'])) {
                    $affectedDates[$row['log_date']] = true;
                }
            }
            $batchCount = count($ids);
            $total += $batchCount;
        } while ($batchCount === $this->batchSize);

        return $total;
    }

    /** @param array{active_days:int, archive_days:int, predicate:string} $policy */
    private function purgePolicy(array $policy): int
    {
        $total = 0;
        $cutoff = (new DateTimeImmutable())->modify('-' . $policy['archive_days'] . ' days')->format('Y-m-d H:i:s');

        do {
            $this->db->beginTransaction();
            $selectSql = "SELECT id, DATE(created_at) AS log_date FROM logs_archive WHERE created_at IS NOT NULL AND created_at < :cutoff AND {$policy['predicate']} ORDER BY id ASC LIMIT {$this->batchSize} FOR UPDATE";
            $select = $this->db->prepare($selectSql);
            $select->execute([':cutoff' => $cutoff]);
            $rows = $select->fetchAll(PDO::FETCH_ASSOC);

            if (!$rows) {
                $this->db->commit();
                break;
            }

            $ids = array_map('intval', array_column($rows, 'id'));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $dates = array_values(array_unique(array_filter(array_column($rows, 'log_date'))));
            $this->preserveDailyStats($dates);
            $delete = $this->db->prepare("DELETE FROM logs_archive WHERE id IN ({$placeholders})");
            $delete->execute($ids);
            if ($delete->rowCount() !== count($ids)) {
                throw new RuntimeException('Arşiv temizliği beklenen kayıt sayısıyla eşleşmedi.');
            }
            $this->db->commit();

            $batchCount = count($ids);
            $total += $batchCount;
        } while ($batchCount === $this->batchSize);

        return $total;
    }

    private function countCandidates(string $table, string $predicate, int $days): int
    {
        if (!in_array($table, ['logs', 'logs_archive'], true)) {
            throw new RuntimeException('Geçersiz log tablosu.');
        }
        $cutoff = (new DateTimeImmutable())->modify("-{$days} days")->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE created_at IS NOT NULL AND created_at < :cutoff AND {$predicate}");
        $stmt->execute([':cutoff' => $cutoff]);
        return (int) $stmt->fetchColumn();
    }

    /** @param string[] $dates */
    private function refreshDailyStats(array $dates): void
    {
        $delete = $this->db->prepare('DELETE FROM log_daily_stats WHERE log_date = ?');
        $insert = $this->db->prepare(
            "INSERT INTO log_daily_stats (log_date, event_type, module, record_count)
             SELECT log_date, event_type, module, COUNT(*)
             FROM (
                 SELECT DATE(created_at) log_date, COALESCE(NULLIF(event_type, ''), 'legacy') event_type, COALESCE(NULLIF(module, ''), 'system') module
                 FROM logs WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                 UNION ALL
                 SELECT DATE(created_at) log_date, COALESCE(NULLIF(event_type, ''), 'legacy') event_type, COALESCE(NULLIF(module, ''), 'system') module
                 FROM logs_archive WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
             ) combined
             GROUP BY log_date, event_type, module"
        );

        foreach ($dates as $date) {
            $this->db->beginTransaction();
            $delete->execute([$date]);
            $insert->execute([$date, $date, $date, $date]);
            $this->db->commit();
        }
    }

    /** @param string[] $dates */
    private function preserveDailyStats(array $dates): void
    {
        $exists = $this->db->prepare('SELECT COUNT(*) FROM log_daily_stats WHERE log_date = ?');
        $insert = $this->db->prepare(
            "INSERT INTO log_daily_stats (log_date, event_type, module, record_count)
             SELECT log_date, event_type, module, COUNT(*)
             FROM (
                 SELECT DATE(created_at) log_date, COALESCE(NULLIF(event_type, ''), 'legacy') event_type, COALESCE(NULLIF(module, ''), 'system') module
                 FROM logs WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                 UNION ALL
                 SELECT DATE(created_at) log_date, COALESCE(NULLIF(event_type, ''), 'legacy') event_type, COALESCE(NULLIF(module, ''), 'system') module
                 FROM logs_archive WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
             ) combined
             GROUP BY log_date, event_type, module"
        );

        foreach ($dates as $date) {
            $exists->execute([$date]);
            if ((int) $exists->fetchColumn() === 0) {
                $insert->execute([$date, $date, $date, $date]);
            }
        }
    }

    private function startRun(): int
    {
        $stmt = $this->db->prepare("INSERT INTO log_retention_runs (started_at, status) VALUES (NOW(), 'running')");
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    private function finishRun(int $runId, string $status, array $details): void
    {
        $stmt = $this->db->prepare(
            'UPDATE log_retention_runs SET finished_at = NOW(), status = :status, archived_count = :archived, purged_count = :purged, details = :details WHERE id = :id'
        );
        $stmt->execute([
            ':status' => $status,
            ':archived' => (int) ($details['archived'] ?? 0),
            ':purged' => (int) ($details['purged'] ?? 0),
            ':details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':id' => $runId,
        ]);
    }
}
