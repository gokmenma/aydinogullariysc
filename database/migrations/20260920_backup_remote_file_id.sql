-- Add remote_file_id column to backup_logs table
-- Target: MariaDB 10.4+ / MySQL 8.0+
-- Idempotent

SET @dbname = DATABASE();
SET @tablename = "backup_logs";
SET @columnname = "remote_file_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `backup_logs` ADD COLUMN `remote_file_id` VARCHAR(100) NULL AFTER `file_path`"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
