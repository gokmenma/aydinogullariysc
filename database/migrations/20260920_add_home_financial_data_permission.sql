-- Ana sayfa finansal verileri görüntüleme yetkisi
-- Tarih: 2026-09-20

INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'home_financial_data_view', 'Ana Sayfa Finansal Verileri Görüntüle', 12, 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `authority` WHERE `authName` = 'home_financial_data_view'
);

-- Yeni yetkiyi başlangıçta yalnızca Admin rolüne ver.
INSERT INTO `userauths` (`roleID`, `authID`)
SELECT 1, a.id
FROM `authority` a
WHERE a.authName = 'home_financial_data_view'
AND NOT EXISTS (
    SELECT 1
    FROM `userauths` ua
    WHERE ua.roleID = 1 AND ua.authID = a.id
);
