-- ==========================================================
-- Mail Kayıtları & Yetkilendirme Tam Kurulum SQL Scripti
-- Tarih: 2026-09-20
-- ==========================================================

-- 1. mail_logs Tablosunu Oluştur (Yoksa)
CREATE TABLE IF NOT EXISTS `mail_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tomail` varchar(255) NOT NULL,
  `from_mail` varchar(100) NOT NULL,
  `mail_file` varchar(255) DEFAULT NULL,
  `mail_body` varchar(10000) DEFAULT NULL,
  `datest` timestamp NOT NULL DEFAULT current_timestamp(),
  `statu` int(11) NOT NULL DEFAULT 1,
  `sender` int(20) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. pages Tablosuna 'mail-logs' Sayfasını Ekle (Yoksa)
-- Not: Sistem index.php üzerinden sayfayı çağırırken pages tablosunu kontrol eder!
INSERT INTO `pages` (`p_title`, `p_link`, `pid`)
SELECT 'Mail Kayıtları', 'mail-logs', 15
WHERE NOT EXISTS (SELECT 1 FROM `pages` WHERE `p_link` = 'mail-logs');

-- 3. authority Tablosuna Yetkileri Ekle (Yoksa)
INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'mail-logs-view', 'Mail Kayıtlarını Görüntüle', 12, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `authority` WHERE `authName` = 'mail-logs-view');

INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'mail-logs-delete', 'Mail Kayıtlarını Sil', 12, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `authority` WHERE `authName` = 'mail-logs-delete');

-- 4. userauths Tablosuna Admin Rolü (roleID = 1) İçin Yetkileri Bağla
INSERT INTO `userauths` (`roleID`, `authID`)
SELECT 1, a.id 
FROM `authority` a 
WHERE a.authName IN ('mail-logs-view', 'mail-logs-delete')
AND NOT EXISTS (
    SELECT 1 FROM `userauths` ua 
    WHERE ua.roleID = 1 AND ua.authID = a.id
);
