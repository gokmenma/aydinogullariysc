-- Migration: 20260924_092200_version_note_v2_4_64.sql
-- Sürüm Notu: Teklif Listesi Tablo Arama Hatası Düzeltmesi (Collation Uyuşmazlığı)

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.64',
    'Teklif Listesi Tablo Arama Hatası Düzeltmesi',
    'bugfix',
    '- Teklif listesi tablosunun genel arama kutusunda arama yapıldığında oluşan SQL Collation uyuşmazlığı hatası (Illegal mix of collations: utf8mb4_general_ci / utf8mb4_unicode_ci) giderildi.\n- `view_offers` görünümündeki dinamik durum alanı `utf8mb4_unicode_ci` karşılaştırma kuralı ile sabitlendi.\n- Teklif listesi API (`App/api/get-offers.php`) sorgularına hata yakalama (try-catch) ve loglama mekanizması eklendi.',
    'Antigravity AI',
    '2026-09-24 09:22:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.64'
);
