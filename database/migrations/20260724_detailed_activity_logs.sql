-- Sistem aktivitelerini işlem türü ve ilgili kayıt bazında detaylandırır.
-- Hedef: MariaDB 10.4+
-- Tekrar çalıştırılabilir ve mevcut log kayıtlarını değiştirmez.

ALTER TABLE `logs`
    ADD COLUMN IF NOT EXISTS `event_type` VARCHAR(30) NULL DEFAULT NULL AFTER `action`,
    ADD COLUMN IF NOT EXISTS `module` VARCHAR(80) NULL DEFAULT NULL AFTER `event_type`,
    ADD COLUMN IF NOT EXISTS `entity_type` VARCHAR(80) NULL DEFAULT NULL AFTER `module`,
    ADD COLUMN IF NOT EXISTS `entity_id` VARCHAR(100) NULL DEFAULT NULL AFTER `entity_type`,
    ADD COLUMN IF NOT EXISTS `summary` VARCHAR(255) NULL DEFAULT NULL AFTER `entity_id`;

SET @has_event_index = (
    SELECT COUNT(*)
    FROM `information_schema`.`statistics`
    WHERE `table_schema` = DATABASE()
      AND `table_name` = 'logs'
      AND `index_name` = 'idx_logs_event_created'
);
SET @event_index_sql = IF(
    @has_event_index = 0,
    'CREATE INDEX `idx_logs_event_created` ON `logs` (`event_type`, `created_at`)',
    'SELECT 1'
);
PREPARE event_index_stmt FROM @event_index_sql;
EXECUTE event_index_stmt;
DEALLOCATE PREPARE event_index_stmt;

SET @has_module_index = (
    SELECT COUNT(*)
    FROM `information_schema`.`statistics`
    WHERE `table_schema` = DATABASE()
      AND `table_name` = 'logs'
      AND `index_name` = 'idx_logs_module_created'
);
SET @module_index_sql = IF(
    @has_module_index = 0,
    'CREATE INDEX `idx_logs_module_created` ON `logs` (`module`, `created_at`)',
    'SELECT 1'
);
PREPARE module_index_stmt FROM @module_index_sql;
EXECUTE module_index_stmt;
DEALLOCATE PREPARE module_index_stmt;
