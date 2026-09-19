-- Ürün Dashboard Sayfası ve Yetkilendirme Migration
-- Tarih: 2026-09-19

-- 1. pages tablosuna ürün dashboard sayfasını ekle
INSERT INTO `pages` (`p_title`, `p_link`, `pid`)
SELECT 'Ürün Dashboard', 'products/dashboard', 4
WHERE NOT EXISTS (
    SELECT 1 FROM `pages` WHERE `p_link` = 'products/dashboard'
);

-- 2. authority tablosuna product_dashboard yetkisi ekle
INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'product_dashboard', 'Ürün Dashboard Görüntüle', 11, 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `authority` WHERE `authName` = 'product_dashboard'
);

-- 3. userauths tablosunda yetkili rollere (productadd, productedit, productcategory yetkisi olanlar) bu izni ver
INSERT INTO `userauths` (`roleId`, `authID`)
SELECT DISTINCT ua.roleId, a.id
FROM `userauths` ua
JOIN `authority` a ON a.authName = 'product_dashboard'
WHERE ua.authID IN (SELECT id FROM `authority` WHERE authName IN ('productadd', 'productedit', 'productcategory'))
AND NOT EXISTS (
    SELECT 1 FROM `userauths` u2 
    WHERE u2.roleId = ua.roleId AND u2.authID = a.id
);

-- 4. roleId = 1 (Admin) için de garantiye al
INSERT INTO `userauths` (`roleId`, `authID`)
SELECT 1, a.id
FROM `authority` a
WHERE a.authName = 'product_dashboard'
AND NOT EXISTS (
    SELECT 1 FROM `userauths` u3 
    WHERE u3.roleId = 1 AND u3.authID = a.id
);
