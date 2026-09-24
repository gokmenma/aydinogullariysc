-- Migration: 20260924_082300_version_note_v2_4_61.sql
-- Sürüm Notu: Keşif Listesi Tooltip JS Hatası ve Görsel 404 Düzeltmesi

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.61',
    'Keşif Listesi Tooltip JS Delegasyonu ve 404 Görsel Kontrolü',
    'bugfix',
    '- Bootstrap 4 uyumluluğu için tooltip delegasyonu `$("body").tooltip({ selector: "#kesifTable [data-tooltip]" })` yapısına güncellenerek `bootstrap.Tooltip.getInstance` hatası giderildi.\n- Keşif listesi görsel sütununda diskte fiziksel olarak bulunmayan test/artık dosyaların 404 hatası üretmesi engellendi.',
    'Antigravity AI',
    '2026-09-24 08:23:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.61'
);
