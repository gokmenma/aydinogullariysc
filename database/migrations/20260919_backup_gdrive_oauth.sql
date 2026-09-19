-- Google Drive OAuth 2.0 Ayarları Migration
-- Hedef: MariaDB 10.4+ / MySQL 8.0+
-- Tekrar çalıştırılabilir (Idempotent)

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_gdrive_client_id', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_gdrive_client_id');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_gdrive_client_secret', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_gdrive_client_secret');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_gdrive_refresh_token', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_gdrive_refresh_token');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_gdrive_access_token', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_gdrive_access_token');
