-- Migration: Add soft delete columns to projects table
-- Date: 2026-10-09

SET @dbname = DATABASE();
SET @tablename = 'projects';

-- deleted_at kolonu ekle
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = 'deleted_at'
  ) > 0,
  'SELECT 1',
  'ALTER TABLE `projects` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `contract_updated_by`;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- deleted_by kolonu ekle
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = 'deleted_by'
  ) > 0,
  'SELECT 1',
  'ALTER TABLE `projects` ADD COLUMN `deleted_by` INT(11) NULL DEFAULT NULL AFTER `deleted_at`;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- deleted_at indeksi ekle (performans için)
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND INDEX_NAME = 'idx_projects_deleted_at'
  ) > 0,
  'SELECT 1',
  'ALTER TABLE `projects` ADD INDEX `idx_projects_deleted_at` (`deleted_at`);'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
