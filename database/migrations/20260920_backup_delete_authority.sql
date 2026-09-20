-- Yedek Silme Yetkisi (backupdelete) Tanımı
-- Hedef: MariaDB 10.4+ / MySQL 8.0+
-- Tekrar çalıştırılabilir (Idempotent)

INSERT INTO `authority`
    (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT
    'backupdelete',
    'Sistem Yedeklerini Silme',
    1,
    1,
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM `authority`
    WHERE `authName` = 'backupdelete'
);

-- Admin Rolüne (roleID = 1) Yetki Tanımı
INSERT INTO `userauths` (`roleID`, `authID`)
SELECT
    1,
    a.`id`
FROM `authority` AS a
WHERE a.`authName` = 'backupdelete'
  AND NOT EXISTS (
      SELECT 1
      FROM `userauths` AS ua
      WHERE ua.`roleID` = 1
        AND ua.`authID` = a.`id`
  );
