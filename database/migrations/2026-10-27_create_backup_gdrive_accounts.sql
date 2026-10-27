CREATE TABLE IF NOT EXISTS `backup_gdrive_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(150) NOT NULL,
  `account_email` varchar(190) DEFAULT NULL,
  `client_id` varchar(255) NOT NULL DEFAULT '',
  `client_secret` varchar(255) NOT NULL DEFAULT '',
  `folder_id` varchar(255) NOT NULL DEFAULT '',
  `refresh_token` text DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_backup_gdrive_accounts_active` (`is_active`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `backup_remote_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_log_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `remote_file_id` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_backup_remote_files_log_account` (`backup_log_id`, `account_id`),
  KEY `idx_backup_remote_files_account` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `backup_gdrive_accounts` (`label`, `client_id`, `client_secret`, `folder_id`, `refresh_token`, `access_token`, `is_active`, `created_at`)
SELECT
    'Ana Google Hesabı',
    COALESCE((SELECT `val` FROM `settings` WHERE `var` = 'backup_gdrive_client_id' LIMIT 1), ''),
    COALESCE((SELECT `val` FROM `settings` WHERE `var` = 'backup_gdrive_client_secret' LIMIT 1), ''),
    COALESCE((SELECT `val` FROM `settings` WHERE `var` = 'backup_gdrive_folder_id' LIMIT 1), ''),
    (SELECT `val` FROM `settings` WHERE `var` = 'backup_gdrive_refresh_token' LIMIT 1),
    (SELECT `val` FROM `settings` WHERE `var` = 'backup_gdrive_access_token' LIMIT 1),
    1,
    NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `backup_gdrive_accounts`)
  AND COALESCE((SELECT `val` FROM `settings` WHERE `var` = 'backup_gdrive_client_id' LIMIT 1), '') <> '';

INSERT INTO `backup_remote_files` (`backup_log_id`, `account_id`, `remote_file_id`)
SELECT l.`id`, (SELECT MIN(a.`id`) FROM `backup_gdrive_accounts` a), l.`remote_file_id`
FROM `backup_logs` l
WHERE l.`remote_file_id` IS NOT NULL
  AND l.`remote_file_id` <> ''
  AND EXISTS (SELECT 1 FROM `backup_gdrive_accounts`)
  AND NOT EXISTS (SELECT 1 FROM `backup_remote_files` r WHERE r.`backup_log_id` = l.`id`);
