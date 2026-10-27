-- Migration: Add performance indexes to kesifler table for Mobile SPA and list filtering
-- Date: 2026-10-26

SET @dbname = DATABASE();
SET @tablename = "kesifler";

-- idx_kesif_tarihi
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = @dbname
      AND table_name = @tablename
      AND index_name = 'idx_kesif_tarihi'
  ) > 0,
  "SELECT 1",
  "CREATE INDEX idx_kesif_tarihi ON kesifler(kesif_tarihi)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- idx_silinme_tarihi
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = @dbname
      AND table_name = @tablename
      AND index_name = 'idx_silinme_tarihi'
  ) > 0,
  "SELECT 1",
  "CREATE INDEX idx_silinme_tarihi ON kesifler(silinme_tarihi)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
