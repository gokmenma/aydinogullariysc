-- Sistem aktiviteleri icin aktif / arsiv saklama altyapisi.
-- Tekrar calistirilabilir; mevcut log kayitlarini silmez.

UPDATE `logs`
SET `created_at` = STR_TO_DATE(
    CONCAT(`dates`, ' ', IF(NULLIF(`clock`, '') IS NULL, '00:00:00', `clock`)),
    '%d-%m-%Y %H:%i:%s'
)
WHERE `created_at` IS NULL
  AND `dates` REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}$'
  AND (NULLIF(`clock`, '') IS NULL OR `clock` REGEXP '^[0-9]{2}:[0-9]{2}:[0-9]{2}$');

SET @logs_needs_conversion = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'logs'
      AND (ENGINE <> 'InnoDB' OR TABLE_COLLATION <> 'utf8mb4_turkish_ci')
);
SET @logs_conversion_sql = IF(
    @logs_needs_conversion > 0,
    'ALTER TABLE `logs` ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci',
    'SELECT 1'
);
PREPARE logs_conversion_stmt FROM @logs_conversion_sql;
EXECUTE logs_conversion_stmt;
DEALLOCATE PREPARE logs_conversion_stmt;

ALTER TABLE `logs`
    ADD INDEX IF NOT EXISTS `idx_logs_created_at` (`created_at`),
    ADD INDEX IF NOT EXISTS `idx_logs_user_created` (`user_id`, `created_at`),
    ADD INDEX IF NOT EXISTS `idx_logs_level_created` (`level`, `created_at`);

CREATE TABLE IF NOT EXISTS `logs_archive` LIKE `logs`;
ALTER TABLE `logs_archive`
    ADD COLUMN IF NOT EXISTS `archived_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_at`,
    ADD INDEX IF NOT EXISTS `idx_logs_archive_archived_at` (`archived_at`);

CREATE TABLE IF NOT EXISTS `log_daily_stats` (
    `log_date` date NOT NULL,
    `event_type` varchar(30) NOT NULL DEFAULT 'legacy',
    `module` varchar(80) NOT NULL DEFAULT 'system',
    `record_count` int unsigned NOT NULL DEFAULT 0,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_date`, `event_type`, `module`),
    KEY `idx_log_daily_stats_event_date` (`event_type`, `log_date`),
    KEY `idx_log_daily_stats_module_date` (`module`, `log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `log_retention_runs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `started_at` datetime NOT NULL,
    `finished_at` datetime DEFAULT NULL,
    `status` varchar(20) NOT NULL,
    `archived_count` int unsigned NOT NULL DEFAULT 0,
    `purged_count` int unsigned NOT NULL DEFAULT 0,
    `details` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_log_retention_runs_started` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
