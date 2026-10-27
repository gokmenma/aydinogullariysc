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
                    backup_type, file_name, file_path, remote_file_id, file_size, status, 
                    remote_status, mail_status, duration_sec, sha256_hash, 
                    message, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, NOW()
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['backup_type'] ?? 'full',
            $data['file_name'] ?? '',
            $data['file_path'] ?? '',
            $data['remote_file_id'] ?? null,
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

    public function markStaleBackupsAsFailed(int $timeoutMinutes = 5): int
    {
        $stmt = $this->db->prepare("UPDATE backup_logs 
                                    SET status = 'failed', 
                                        message = 'İşlem zaman aşımına uğradı veya sunucu tarafından durduruldu.' 
                                    WHERE status = 'in_progress' 
                                      AND created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt->execute([$timeoutMinutes]);
        return $stmt->rowCount();
    }

    public function getRecentLogs(int $limit = 50): array
    {
        $this->markStaleBackupsAsFailed(5);
        $stmt = $this->db->prepare("SELECT * FROM backup_logs ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveBackup(): ?array
    {
        $this->markStaleBackupsAsFailed(5);
        $stmt = $this->db->prepare("SELECT * FROM backup_logs WHERE status = 'in_progress' AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getLogById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM backup_logs WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getLogByFileName(string $fileName): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM backup_logs WHERE file_name = ? LIMIT 1");
        $stmt->execute([$fileName]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getUploadedLogs(): array
    {
        $stmt = $this->db->query("SELECT * FROM backup_logs WHERE remote_status IN ('uploaded', 'deleted_from_drive') OR (remote_file_id IS NOT NULL AND remote_file_id != '') ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteLog(int $id): bool
    {
        $this->deleteRemoteFilesByLog($id);
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
            'backup_retention_remote_daily_days',
            'backup_retention_weekly_weeks',
            'backup_retention_monthly_months',
            'backup_retention_min_keep',
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
        $check = $this->db->prepare("SELECT COUNT(*) FROM settings WHERE `var` = ?");
        $update = $this->db->prepare("UPDATE settings SET `val` = ? WHERE `var` = ?");
        $insert = $this->db->prepare("INSERT INTO settings (`var`, `val`) VALUES (?, ?)");
        foreach ($settings as $key => $val) {
            $check->execute([$key]);
            if ((int)$check->fetchColumn() > 0) {
                $update->execute([(string)$val, $key]);
            } else {
                $insert->execute([$key, (string)$val]);
            }
        }
        return true;
    }

    public function getLogsForRetention(): array
    {
        $stmt = $this->db->query("SELECT * FROM backup_logs ORDER BY created_at DESC, id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGdriveAccounts(bool $onlyActive = false): array
    {
        $sql = "SELECT * FROM backup_gdrive_accounts WHERE deleted_at IS NULL";
        if ($onlyActive) {
            $sql .= " AND is_active = 1";
        }
        $stmt = $this->db->query($sql . " ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGdriveAccount(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM backup_gdrive_accounts WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function saveGdriveAccount(?int $id, array $data, ?int $userId = null): int
    {
        if ($id) {
            $stmt = $this->db->prepare("UPDATE backup_gdrive_accounts SET label = ?, client_id = ?, client_secret = ?, folder_id = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$data['label'], $data['client_id'], $data['client_secret'], $data['folder_id'], (int)$data['is_active'], $id]);
            return $id;
        }

        $stmt = $this->db->prepare("INSERT INTO backup_gdrive_accounts (label, client_id, client_secret, folder_id, is_active, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$data['label'], $data['client_id'], $data['client_secret'], $data['folder_id'], (int)$data['is_active'], $userId]);
        return (int)$this->db->lastInsertId();
    }

    public function setGdriveTokens(int $id, ?string $refreshToken, ?string $accessToken): bool
    {
        if ($refreshToken === null) {
            $stmt = $this->db->prepare("UPDATE backup_gdrive_accounts SET access_token = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$accessToken, $id]);
        }
        $stmt = $this->db->prepare("UPDATE backup_gdrive_accounts SET refresh_token = ?, access_token = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$refreshToken, $accessToken, $id]);
    }

    public function softDeleteGdriveAccount(int $id, ?int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE backup_gdrive_accounts SET deleted_at = NOW(), deleted_by = ?, is_active = 0, refresh_token = NULL, access_token = NULL WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$userId, $id]);
    }

    public function getRemoteFiles(int $logId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM backup_remote_files WHERE backup_log_id = ?");
        $stmt->execute([$logId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveRemoteFile(int $logId, int $accountId, string $remoteFileId): bool
    {
        $stmt = $this->db->prepare("INSERT INTO backup_remote_files (backup_log_id, account_id, remote_file_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE remote_file_id = VALUES(remote_file_id)");
        return $stmt->execute([$logId, $accountId, $remoteFileId]);
    }

    public function deleteRemoteFile(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM backup_remote_files WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteRemoteFilesByLog(int $logId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM backup_remote_files WHERE backup_log_id = ?");
        return $stmt->execute([$logId]);
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
