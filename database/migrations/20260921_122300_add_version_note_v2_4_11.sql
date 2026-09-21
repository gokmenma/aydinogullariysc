-- Migration: Add Version Note v2.4.11
-- Description: Teklifler ve Teklif Kalemleri view_offers SQL Görünümünün Geri Yüklenmesi

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Teklifler ve Teklif Kalemleri view_offers Görünümü Onarımı',
    'v2.4.11',
    'bugfix',
    '- Teklifler ve Teklif Kalemleri modüllerinin Ajax sorgularında kullandığı view_offers SQL görünümü (VIEW) geri yüklendi.
- DataTables itemsTable (Teklif Kalemleri) ve Teklif Listesi sayfalarının Ajax veri yükleme hatası giderildi.
- Teklif kalem filtreleme seçenekleri ve arama optimizasyonu sağlandı.',
    'Antigravity AI',
    '2026-09-21 12:23:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `title` = 'Teklifler ve Teklif Kalemleri view_offers Görünümü Onarımı'
);
