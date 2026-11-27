<?php

namespace App\Model;

use PDO;

class ReportAlarmModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        if ($db instanceof PDO) {
            $this->db = $db;
            return;
        }
        global $ac;
        if (!($ac instanceof PDO)) {
            throw new \RuntimeException('Veritabanı bağlantısı bulunamadı.');
        }
        $this->db = $ac;
    }

    private static function parseDate(string $column): string
    {
        return "STR_TO_DATE(REPLACE(REPLACE(TRIM($column),'.','-'),'/','-'),'%d-%m-%Y')";
    }

    public static function dueExpression(string $alias = 'r'): string
    {
        return 'COALESCE(' . self::parseDate("$alias.validity_date")
            . ", IF($alias.report_type IN ('3','4'), DATE_ADD(" . self::parseDate("$alias.control_date") . ', INTERVAL 1 YEAR), NULL))';
    }

    public function buildMatch(array $alarm, array &$params, string $prefix = 'al', string $alias = 'r', string $customerAlias = 'c'): string
    {
        $due = self::dueExpression($alias);
        $days = max(0, min(3650, (int) $alarm['days_before']));
        $conditions = [
            "$customerAlias.deleted_at IS NULL",
            "$due IS NOT NULL",
            "$due <= DATE_ADD(CURDATE(), INTERVAL $days DAY)",
        ];
        if (empty($alarm['include_overdue'])) {
            $conditions[] = "$due >= CURDATE()";
        }

        $customerIds = array_map('intval', $alarm['customer_ids'] ?? []);
        if ($customerIds) {
            $ph = [];
            foreach ($customerIds as $i => $id) {
                $key = ":{$prefix}_c{$i}";
                $params[$key] = $id;
                $ph[] = $key;
            }
            $conditions[] = "$alias.customer_id IN (" . implode(',', $ph) . ')';
        }

        $typeIds = array_map('intval', $alarm['type_ids'] ?? []);
        if ($typeIds) {
            $ph = [];
            foreach ($typeIds as $i => $id) {
                $key = ":{$prefix}_t{$i}";
                $params[$key] = $id;
                $ph[] = $key;
            }
            $conditions[] = "$alias.report_type IN (" . implode(',', $ph) . ')';
        }

        if (!empty($alarm['latest_only'])) {
            $conditions[] = "$alias.id IN (SELECT MAX(r2.id) FROM reports r2 GROUP BY r2.customer_id, r2.report_type)";
        }

        return implode(' AND ', $conditions);
    }

    public function listForUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM report_alarms WHERE user_id = ? AND deleted_at IS NULL ORDER BY id DESC');
        $stmt->execute([$userId]);
        $alarms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($alarms as &$alarm) {
            $this->hydrate($alarm);
        }
        unset($alarm);
        return $alarms;
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM report_alarms WHERE id = ? AND user_id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id, $userId]);
        $alarm = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$alarm) {
            return null;
        }
        $this->hydrate($alarm);
        return $alarm;
    }

    private function hydrate(array &$alarm): void
    {
        $c = $this->db->prepare('SELECT ac.customer_id, c.company FROM report_alarm_customers ac LEFT JOIN customers c ON c.id = ac.customer_id WHERE ac.alarm_id = ?');
        $c->execute([$alarm['id']]);
        $customers = $c->fetchAll(PDO::FETCH_ASSOC);
        $alarm['customer_ids'] = array_map(fn($x) => (int) $x['customer_id'], $customers);
        $alarm['customer_names'] = array_map(fn($x) => trim((string) $x['company']), $customers);

        $t = $this->db->prepare('SELECT at.report_type_id, rt.reportName FROM report_alarm_types at LEFT JOIN report_types rt ON rt.id = at.report_type_id WHERE at.alarm_id = ?');
        $t->execute([$alarm['id']]);
        $types = $t->fetchAll(PDO::FETCH_ASSOC);
        $alarm['type_ids'] = array_map(fn($x) => (int) $x['report_type_id'], $types);
        $alarm['type_names'] = array_map(fn($x) => (string) $x['reportName'], $types);

        $alarm['days_before'] = (int) $alarm['days_before'];
        $alarm['include_overdue'] = (int) $alarm['include_overdue'];
        $alarm['latest_only'] = (int) $alarm['latest_only'];
        $alarm['is_active'] = (int) $alarm['is_active'];
    }

    public function counts(array $alarm): array
    {
        $params = [];
        $where = $this->buildMatch($alarm, $params);
        $due = self::dueExpression('r');
        $sql = "SELECT COUNT(*) AS total, COALESCE(SUM($due < CURDATE()), 0) AS overdue
                FROM reports r
                LEFT JOIN customers c ON c.id = r.customer_id
                WHERE $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $total = (int) ($row['total'] ?? 0);
        $overdue = (int) ($row['overdue'] ?? 0);
        return ['total' => $total, 'overdue' => $overdue, 'upcoming' => $total - $overdue];
    }

    public function summaryForUser(int $userId): array
    {
        $result = [];
        foreach ($this->listForUser($userId) as $alarm) {
            if (!$alarm['is_active']) {
                continue;
            }
            $counts = $this->counts($alarm);
            if ($counts['total'] > 0) {
                $result[] = [
                    'id' => (int) $alarm['id'],
                    'name' => (string) $alarm['name'],
                    'total' => $counts['total'],
                    'overdue' => $counts['overdue'],
                    'upcoming' => $counts['upcoming'],
                ];
            }
        }
        return $result;
    }

    public function save(int $userId, array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $name = mb_substr(trim((string) ($data['name'] ?? '')), 0, 120);
        $days = max(0, min(3650, (int) ($data['days_before'] ?? 30)));
        $overdue = !empty($data['include_overdue']) ? 1 : 0;
        $latest = !empty($data['latest_only']) ? 1 : 0;
        $active = array_key_exists('is_active', $data) ? (!empty($data['is_active']) ? 1 : 0) : 1;

        $customerIds = $this->validIds($data['customer_ids'] ?? [], 'SELECT id FROM customers WHERE deleted_at IS NULL AND id IN (%s)');
        $typeIds = $this->validIds($data['type_ids'] ?? [], 'SELECT id FROM report_types WHERE id IN (%s)');

        $this->db->beginTransaction();
        try {
            if ($id > 0) {
                if (!$this->findForUser($id, $userId)) {
                    throw new \RuntimeException('Alarm bulunamadı.');
                }
                $stmt = $this->db->prepare('UPDATE report_alarms SET name = ?, days_before = ?, include_overdue = ?, latest_only = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
                $stmt->execute([$name, $days, $overdue, $latest, $active, $id, $userId]);
                $this->db->prepare('DELETE FROM report_alarm_customers WHERE alarm_id = ?')->execute([$id]);
                $this->db->prepare('DELETE FROM report_alarm_types WHERE alarm_id = ?')->execute([$id]);
            } else {
                $stmt = $this->db->prepare('INSERT INTO report_alarms (user_id, name, days_before, include_overdue, latest_only, is_active) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $name, $days, $overdue, $latest, $active]);
                $id = (int) $this->db->lastInsertId();
            }

            $insC = $this->db->prepare('INSERT INTO report_alarm_customers (alarm_id, customer_id) VALUES (?, ?)');
            foreach ($customerIds as $cid) {
                $insC->execute([$id, $cid]);
            }
            $insT = $this->db->prepare('INSERT INTO report_alarm_types (alarm_id, report_type_id) VALUES (?, ?)');
            foreach ($typeIds as $tid) {
                $insT->execute([$id, $tid]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
        return $id;
    }

    private function validIds($ids, string $sqlTemplate): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids), fn($v) => $v > 0)));
        if (!$ids) {
            return [];
        }
        $stmt = $this->db->prepare(sprintf($sqlTemplate, implode(',', array_fill(0, count($ids), '?'))));
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function setActive(int $id, int $userId, bool $active): bool
    {
        $stmt = $this->db->prepare('UPDATE report_alarms SET is_active = ?, updated_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
        $stmt->execute([$active ? 1 : 0, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function softDelete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE report_alarms SET deleted_at = NOW(), deleted_by = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
        $stmt->execute([$userId, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function options(): array
    {
        $customers = $this->db->query("SELECT id, TRIM(company) AS company FROM customers WHERE deleted_at IS NULL AND company IS NOT NULL AND TRIM(company) <> '' ORDER BY company ASC")->fetchAll(PDO::FETCH_ASSOC);
        $types = $this->db->query('SELECT id, reportName FROM report_types ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
        return ['customers' => $customers, 'types' => $types];
    }
}
