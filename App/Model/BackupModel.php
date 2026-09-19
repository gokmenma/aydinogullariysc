<?php
namespace App\Model;

use App\Model\BaseModel;
use PDO;

class BackupModel extends BaseModel
{
    protected $table = 'backup_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function createLog(array $data): int
    {
        $sql = "INSERT INTO backup_logs (
                    backup_type, file_name, file_path, file_size, status, 
                    remote_status, mail_status, duration_sec, sha256_hash, 
                    message, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, NOW()
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['backup_type'] ?? 'full',
            $data['file_name'] ?? '',
            $data['file_path'] ?? '',
            (int)($data['file_size'] ?? 0),
            $data['status'] ?? 'in_progress',
            $data['remote_status'] ?? 'none',
            $data['mail_status'] ?? 'none',
            (float)($data['duration_sec'] ?? 0.0),
            $data['sha256_hash'] ?? null,
            $data['message'] ?? null,
            isset($data['created_by']) ? (int)$data['created_by'] : null
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateLog(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $val) {
            $fields[] = "`$key` = ?";
            $params[] = $val;
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE backup_logs SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getRecentLogs(int $limit = 50): array
    {
        $stmt = $this->db->prepare("SELECT * FROM backup_logs ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveBackup(): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM backup_logs WHERE status = 'in_progress' AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getLogById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM backup_logs WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deleteLog(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM backup_logs WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getBackupSettings(): array
    {
        $keys = [
            'backup_notification_email',
            'backup_send_mail',
            'backup_remote_enabled',
            'backup_remote_type',
            'backup_remote_host',
            'backup_remote_port',
            'backup_remote_user',
            'backup_remote_pass',
            'backup_remote_path',
            'backup_retention_days',
            'backup_cron_token',
            'backup_gdrive_folder_id',
            'backup_gdrive_service_account_json',
            'backup_gdrive_client_id',
            'backup_gdrive_client_secret',
            'backup_gdrive_refresh_token',
            'backup_gdrive_access_token',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_from',
            'mail_name'
        ];

        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        $stmt = $this->db->prepare("SELECT `var`, `val` FROM settings WHERE `var` IN ($placeholders)");
        $stmt->execute($keys);
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['var']] = $row['val'];
        }

        return $settings;
    }

    public function updateBackupSettings(array $settings): bool
    {
        $stmt = $this->db->prepare("INSERT INTO settings (`var`, `val`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `val` = VALUES(`val`)");
        foreach ($settings as $key => $val) {
            $stmt->execute([$key, (string)$val]);
        }
        return true;
    }

    public function cleanOldLogs(int $retentionDays): int
    {
        if ($retentionDays <= 0) {
            return 0;
        }
        $stmt = $this->db->prepare("DELETE FROM backup_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$retentionDays]);
        return $stmt->rowCount();
    }
}
