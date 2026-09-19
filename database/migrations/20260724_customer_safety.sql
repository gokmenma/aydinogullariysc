-- Firma güvenliği ve Excel dışa aktarma yetkisi
-- Hedef: MariaDB 10.4+
-- Bu migration tekrar çalıştırılabilir.

ALTER TABLE `customers`
    ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL DEFAULT NULL;

ALTER TABLE `customers`
    ADD COLUMN IF NOT EXISTS `deleted_by` INT NULL DEFAULT NULL;

INSERT INTO `authority`
    (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT
    'customerexport',
    'Firmaları Excel''e Aktar',
    1,
    1,
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM `authority`
    WHERE `authName` = 'customerexport'
);

-- Yeni dışa aktarma yetkisi ilk kurulumda yalnızca Admin rolüne (roleID = 1) verilir.
INSERT INTO `userauths` (`roleID`, `authID`)
SELECT
    1,
    a.`id`
FROM `authority` AS a
WHERE a.`authName` = 'customerexport'
  AND NOT EXISTS (
      SELECT 1
      FROM `userauths` AS ua
      WHERE ua.`roleID` = 1
        AND ua.`authID` = a.`id`
  );
