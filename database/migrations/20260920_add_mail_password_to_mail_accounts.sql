-- 20260920_add_mail_password_to_mail_accounts.sql
-- Mail hesaplarına SMTP şifresi kolonu ekleme

SET @dbname = DATABASE();
SET @tablename = "mail_accounts";
SET @columnname = "mail_password";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE mail_accounts ADD COLUMN mail_password VARCHAR(255) NULL AFTER mail_user"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
