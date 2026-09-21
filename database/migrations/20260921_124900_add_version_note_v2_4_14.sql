-- Sürüm Notu: Sütun Filtresi Arama İkonu Hizalama Düzeltmesi
-- Tarih: 2026-09-21 12:49:00
-- Sürüm: v2.4.14

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Sütun Filtresi Arama İkonu Hizalama Düzeltmesi',
    'v2.4.14',
    'improvement',
    '- Sütun filtresi arama kutusunun (Listede ara...) solunda yer alan büyüteç arama ikonunun dikey ortalanması ve konumlandırması düzeltildi.\n- .tf-search-box ve .tf-search-wrap CSS kuralları optimize edildi.',
    'Antigravity AI',
    '2026-09-21 12:49:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.14' AND `title` = 'Sütun Filtresi Arama İkonu Hizalama Düzeltmesi'
);
