-- Servis Dashboard Sayfası ve Yetkilendirme Migration
-- Tarih: 2026-09-19

-- 1. pages tablosuna servis dashboard sayfasını ekle
INSERT INTO `pages` (`p_title`, `p_link`, `pid`)
SELECT 'Servis Dashboard', 'service/dashboard', 81
WHERE NOT EXISTS (
    SELECT 1 FROM `pages` WHERE `p_link` = 'service/dashboard'
);

-- 2. authority tablosuna service_dashboard yetkisi ekle
INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'service_dashboard', 'Servis Dashboard Görüntüle', 5, 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `authority` WHERE `authName` = 'service_dashboard'
);

-- 3. userauths tablosunda yetkili rollere (serviceView yetkisi olanlar) bu izni ver
INSERT INTO `userauths` (`roleId`, `authID`)
SELECT DISTINCT ua.roleId, a.id
FROM `userauths` ua
JOIN `authority` a ON a.authName = 'service_dashboard'
WHERE ua.authID IN (SELECT id FROM `authority` WHERE authName = 'serviceView')
AND NOT EXISTS (
    SELECT 1 FROM `userauths` u2 
    WHERE u2.roleId = ua.roleId AND u2.authID = a.id
);

-- 4. roleId = 1 (Admin) için de garantiye al
INSERT INTO `userauths` (`roleId`, `authID`)
SELECT 1, a.id
FROM `authority` a
WHERE a.authName = 'service_dashboard'
AND NOT EXISTS (
    SELECT 1 FROM `userauths` u3 
    WHERE u3.roleId = 1 AND u3.authID = a.id
);
