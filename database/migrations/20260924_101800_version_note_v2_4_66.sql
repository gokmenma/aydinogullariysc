-- Migration: 20260924_101800_version_note_v2_4_66.sql
-- Sürüm Notu: Hidrostatik Test Raporu Düzenleme Sayfası PHP Kodu Render Hatası Düzeltmesi

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.66',
    'Hidrostatik Test Raporu Düzenleme Sayfası Kod Hatası Düzeltmesi',
    'bugfix',
    '- Hidrostatik Test Raporu düzenleme sayfasında (report-edit-hst.php) PHP etiketinin erken kapanması nedeniyle ekranda ham PHP kodunun görünmesi ve rapor verilerinin yüklenememesi sorunu giderildi.\n- Rapor sorgusu ve veri yükleme akışı düzenlendi.',
    'Antigravity AI',
    '2026-09-24 10:18:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.66'
);
