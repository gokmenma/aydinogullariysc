SET @idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'reports' AND index_name = 'idx_reports_customer_type');
SET @sql := IF(@idx = 0, 'ALTER TABLE `reports` ADD INDEX `idx_reports_customer_type` (`customer_id`, `report_type`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
