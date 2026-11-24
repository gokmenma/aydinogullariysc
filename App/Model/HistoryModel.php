<?php
namespace App\Model;

use PDO;

class HistoryModel extends BaseModel
{
    protected $table = 'logs';
    protected string $module = '';
    protected array $entityTypes = [];
    protected array $fieldLabels = [];

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getHistory(int $entityId, int $limit = 300): array
    {
        if ($this->module === '' || !$this->entityTypes) {
            return [];
        }
        $limit = max(1, min(1000, $limit));
        $in = implode(',', array_fill(0, count($this->entityTypes), '?'));
        $sql = "SELECT l.id, l.event_type, l.summary, l.details, l.ip_address, l.created_at, u.Unvan AS user_unvan,
                       COALESCE(NULLIF(u.username, ''), IF(COALESCE(NULLIF(l.user_id, 0), l.author) = 0, 'Sistem', CONCAT('Kullanıcı #', COALESCE(NULLIF(l.user_id, 0), l.author)))) AS username
                FROM {$this->table} l
                LEFT JOIN users u ON u.id = COALESCE(NULLIF(l.user_id, 0), l.author)
                WHERE l.module = ?
                  AND l.entity_type IN ($in)
                  AND l.entity_id = ?
                  AND (l.event_type IS NULL OR l.event_type <> 'view')
                ORDER BY l.id DESC
                LIMIT " . $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->module, ...$this->entityTypes, (string) $entityId]);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $details = json_decode((string) ($row['details'] ?? ''), true);
            $context = is_array($details) ? ($details['context'] ?? []) : [];
            $rows[] = [
                'id' => (int) $row['id'],
                'event_type' => $row['event_type'],
                'event_label' => ActivityLogModel::getEventLabel($row['event_type']),
                'badge_class' => ActivityLogModel::getEventBadgeClass($row['event_type']),
                'event_icon' => ActivityLogModel::getEventIcon($row['event_type']),
                'summary' => $row['summary'],
                'user_name' => $row['username'],
                'user_unvan' => $row['user_unvan'] ?: '',
                'ip_address' => $row['ip_address'] ?: '-',
                'created_at_formatted' => $row['created_at'] ? date('d.m.Y H:i:s', strtotime($row['created_at'])) : '-',
                'relative_time' => ActivityLogModel::formatRelativeTime($row['created_at']),
                'changed_fields' => $this->formatChanges($context['changed_fields'] ?? []),
                'item_changes' => is_array($context['item_changes'] ?? null) ? $context['item_changes'] : [],
            ];
        }
        return $rows;
    }

    protected function formatChanges($changes): array
    {
        $out = [];
        if (!is_array($changes)) {
            return $out;
        }
        foreach ($changes as $field => $change) {
            if (!is_array($change)) {
                continue;
            }
            $out[] = [
                'label' => $this->fieldLabels[$field] ?? (string) $field,
                'old' => $change['old'] ?? '',
                'new' => $change['new'] ?? '',
            ];
        }
        return $out;
    }
}
