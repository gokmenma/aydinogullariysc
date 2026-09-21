-- Sürüm Notu: TableFilter Sütun İndeks Tipi ve ServerSide DOM Row Guard Düzeltmesi (v2.4.18)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.18',
    'TableFilter Sütun İndeks Tipi ve ServerSide DOM Row Guard Düzeltmesi',
    '- App/api/get-logs.php içinde columns parametre dizinlerinin string/integer tip uyuşmazlığı intval($idx) ve json[colIndex] desteğiyle giderildi.\n- include/js/table-filter.js filterDOMTable fonksiyonuna serverSide DataTables koruması eklenerek AJAX ile gelen satırların istemcide DOM seviyesinde yanlışlıkla gizlenmesi engellendi.\n- Düz metin ve çoklu seçim filtreleri için geriye dönük fallback desteği eklendi.',
    'bugfix',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.18'
);
