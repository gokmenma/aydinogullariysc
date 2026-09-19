<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

try {
    // 1. backup_logs tablosunu oluştur
    $ac->exec("CREATE TABLE IF NOT EXISTS `backup_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `backup_type` VARCHAR(30) NOT NULL DEFAULT 'full',
        `file_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(255) NULL,
        `file_size` BIGINT UNSIGNED DEFAULT 0,
        `status` ENUM('success', 'failed', 'in_progress') NOT NULL DEFAULT 'in_progress',
        `remote_status` VARCHAR(100) DEFAULT 'none',
        `mail_status` VARCHAR(100) DEFAULT 'none',
        `duration_sec` DECIMAL(8,2) DEFAULT 0.00,
        `sha256_hash` VARCHAR(64) NULL,
        `message` TEXT NULL,
        `created_by` INT NULL DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_backup_logs_created` (`created_at`),
        INDEX `idx_backup_logs_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Varsayılan ayarları ekle
    $defaultSettings = [
        'backup_notification_email' => '',
        'backup_send_mail' => '1',
        'backup_remote_enabled' => '0',
        'backup_remote_type' => 'ftp',
        'backup_remote_host' => '',
        'backup_remote_port' => '21',
        'backup_remote_user' => '',
        'backup_remote_pass' => '',
        'backup_remote_path' => '/backups',
        'backup_retention_days' => '30',
        'backup_cron_token' => bin2hex(random_bytes(16))
    ];

    $checkStmt = $ac->prepare("SELECT COUNT(*) FROM `settings` WHERE `var` = ?");
    $insertStmt = $ac->prepare("INSERT INTO `settings` (`var`, `val`) VALUES (?, ?)");

    foreach ($defaultSettings as $key => $val) {
        $checkStmt->execute([$key]);
        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([$key, $val]);
        }
    }

    // 3. Yetki kontrolü ve ekleme
    $authCheck = $ac->prepare("SELECT id FROM authority WHERE authName = ? LIMIT 1");
    $authCheck->execute(['backupmanage']);
    $authId = $authCheck->fetchColumn();

    if (!$authId) {
        $authInsert = $ac->prepare("INSERT INTO authority (authName, authTitle, authGroup, authValue, isActive) VALUES (?, ?, ?, ?, ?)");
        $authInsert->execute(['backupmanage', 'Sistem Yedeklerini Yönet', 1, 1, 1]);
        $authId = $ac->lastInsertId();
    }

    // Admin rolüne yetki bağla
    $grantStmt = $ac->prepare(
        "INSERT INTO userauths (roleID, authID)
         SELECT 1, ?
         WHERE NOT EXISTS (
             SELECT 1 FROM userauths WHERE roleID = 1 AND authID = ?
         )"
    );
    $grantStmt->execute([$authId, $authId]);

    // Pages tablosuna backups sayfasını ekle
    $pageCheck = $ac->prepare("SELECT id FROM pages WHERE p_link = ? LIMIT 1");
    $pageCheck->execute(['backups']);
    if (!$pageCheck->fetchColumn()) {
        $pageInsert = $ac->prepare("INSERT INTO pages (p_title, p_link, pid) VALUES (?, ?, ?)");
        $pageInsert->execute(['Veritabanı ve Sistem Yedekleme', 'backups', 1]);
    }

    echo "Backup migration completed successfully.\n";

} catch (Exception $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
