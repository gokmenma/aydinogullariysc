-- Customer Label Page SQL Migration
INSERT INTO `pages` (`p_title`, `p_link`, `pid`) 
SELECT 'Müşteri Etiketi', 'customer-label', 0
WHERE NOT EXISTS (
    SELECT 1 FROM `pages` WHERE `p_link` = 'customer-label'
);
