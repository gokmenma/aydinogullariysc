-- Keşif Dashboard Sayfası ve Yetkilendirme Migration
-- Tarih: 2026-09-20

INSERT INTO `pages` (`p_title`, `p_link`, `pid`)
SELECT 'Keşif Dashboard', 'kesif/dashboard', 144
WHERE NOT EXISTS (
    SELECT 1 FROM `pages` WHERE `p_link` = 'kesif/dashboard'
);

INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'kesif_dashboard', 'Keşif Dashboard Görüntüle', 20, 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `authority` WHERE `authName` = 'kesif_dashboard'
);

INSERT INTO `userauths` (`roleId`, `authID`)
SELECT DISTINCT ua.roleId, a.id
FROM `userauths` ua
JOIN `authority` a ON a.authName = 'kesif_dashboard'
WHERE ua.authID IN (SELECT id FROM `authority` WHERE authName = 'kesifView')
AND NOT EXISTS (
    SELECT 1 FROM `userauths` u2
    WHERE u2.roleId = ua.roleId AND u2.authID = a.id
);

INSERT INTO `userauths` (`roleId`, `authID`)
SELECT 1, a.id
FROM `authority` a
WHERE a.authName = 'kesif_dashboard'
AND NOT EXISTS (
    SELECT 1 FROM `userauths` u3
    WHERE u3.roleId = 1 AND u3.authID = a.id
);
