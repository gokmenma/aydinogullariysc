-- Migration: mail_logs tablosuna cc_mail (kopya e-posta) alanı ekleme
SET @dbname = DATABASE();
SET @tablename = 'mail_logs';
SET @columnname = 'cc_mail';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  'ALTER TABLE `mail_logs` ADD COLUMN `cc_mail` TEXT DEFAULT NULL AFTER `tomail`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
