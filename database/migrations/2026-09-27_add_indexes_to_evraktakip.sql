-- Migration: Add indexes to evraktakip table for high-performance server-side DataTables
-- Date: 2026-09-27

SET @dbname = DATABASE();
SET @tablename = "evraktakip";

-- idx_evrakturu
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = @dbname
      AND table_name = @tablename
      AND index_name = 'idx_evrakturu'
  ) > 0,
  "SELECT 1",
  "CREATE INDEX idx_evrakturu ON evraktakip(evrakturu)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- idx_firma
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = @dbname
      AND table_name = @tablename
      AND index_name = 'idx_firma'
  ) > 0,
  "SELECT 1",
  "CREATE INDEX idx_firma ON evraktakip(firma)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- idx_estatu
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = @dbname
      AND table_name = @tablename
      AND index_name = 'idx_estatu'
  ) > 0,
  "SELECT 1",
  "CREATE INDEX idx_estatu ON evraktakip(estatu)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- idx_teslimeden
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = @dbname
      AND table_name = @tablename
      AND index_name = 'idx_teslimeden'
  ) > 0,
  "SELECT 1",
  "CREATE INDEX idx_teslimeden ON evraktakip(teslimeden)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- idx_teslimalan
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = @dbname
      AND table_name = @tablename
      AND index_name = 'idx_teslimalan'
  ) > 0,
  "SELECT 1",
  "CREATE INDEX idx_teslimalan ON evraktakip(teslimalan)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
