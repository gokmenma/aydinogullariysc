-- Otomatik Yedekleme Tablosu, Ayarları ve Yetki Tanımları
-- Hedef: MariaDB 10.4+ / MySQL 8.0+
-- Tekrar çalıştırılabilir (Idempotent)

CREATE TABLE IF NOT EXISTS `backup_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan Yedekleme Ayarları
INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_notification_email', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_notification_email');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_send_mail', '1'
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_send_mail');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_remote_enabled', '0'
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_remote_enabled');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_remote_type', 'ftp'
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_remote_type');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_remote_host', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_remote_host');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_remote_port', '21'
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_remote_port');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_remote_user', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_remote_user');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_remote_pass', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_remote_pass');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_remote_path', '/backups'
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_remote_path');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_retention_days', '30'
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_retention_days');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_cron_token', SHA2(CONCAT(RAND(), UUID(), NOW()), 256)
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_cron_token');

-- Yedekleme Yönetim Yetkisi
INSERT INTO `authority`
    (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT
    'backupmanage',
    'Sistem Yedeklerini Yönet',
    1,
    1,
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM `authority`
    WHERE `authName` = 'backupmanage'
);

-- Admin Rolüne (roleID = 1) Yetki Tanımı
INSERT INTO `userauths` (`roleID`, `authID`)
SELECT
    1,
    a.`id`
FROM `authority` AS a
WHERE a.`authName` = 'backupmanage'
  AND NOT EXISTS (
      SELECT 1
      FROM `userauths` AS ua
      WHERE ua.`roleID` = 1
        AND ua.`authID` = a.`id`
  );

-- Sayfa Tanımı (pages tablosu)
INSERT INTO `pages` (`p_title`, `p_link`, `pid`)
SELECT 'Veritabanı ve Sistem Yedekleme', 'backups', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `pages` WHERE `p_link` = 'backups'
);

