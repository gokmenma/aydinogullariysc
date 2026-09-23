-- Sürüm Notu: v2.4.50
INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`, `updated_at`)
SELECT 
    'Servis Listesi Durum Rozeti Taşma Düzeltmesi',
    'v2.4.50',
    'bugfix',
    '- Servis listesinde (pages/1/service/list.php ve api/services_datatables.php) uzun metinli durum rozetlerinin (örneğin "MUHASEBEYE TESLİM EDİLDİ.") yan sütunlara taşarak metinlerin üst üste binmesi sorunu giderildi.\n- Tablo durum rozetleri için otomatik satır kaydırma (white-space: normal, word-break: break-word, max-width: 100%) tanımlandı.\n- Tablo sütun genişlikleri optimize edilerek durum sütunu dengelendi ve taşmalar önlendi.',
    'Antigravity AI',
    '2026-09-23 20:58:00',
    '2026-09-23 20:58:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.50'
);
