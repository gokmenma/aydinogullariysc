-- Google Drive Yedekleme Ayarları Migration
-- Hedef: MariaDB 10.4+ / MySQL 8.0+
-- Tekrar çalıştırılabilir (Idempotent)

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_gdrive_folder_id', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_gdrive_folder_id');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_gdrive_service_account_json', ''
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_gdrive_service_account_json');
