-- Mail Kayıtları Yetkilendirme Migration
-- Tarih: 2026-09-20

INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'mail-logs-view', 'Mail Kayıtlarını Görüntüle', 12, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `authority` WHERE `authName` = 'mail-logs-view');

INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'mail-logs-delete', 'Mail Kayıtlarını Sil', 12, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `authority` WHERE `authName` = 'mail-logs-delete');

-- Admin rolüne (roleID = 1) yetkileri ata
INSERT INTO `userauths` (`roleID`, `authID`)
SELECT 1, a.id 
FROM `authority` a 
WHERE a.authName IN ('mail-logs-view', 'mail-logs-delete')
AND NOT EXISTS (
    SELECT 1 FROM `userauths` ua 
    WHERE ua.roleID = 1 AND ua.authID = a.id
);
