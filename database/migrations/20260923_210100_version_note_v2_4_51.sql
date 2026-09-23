-- Sürüm Notu: v2.4.51
INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`, `updated_at`)
SELECT 
    'Keşif Listesi İşlem Kolonu Genişliği Düzenlemesi',
    'v2.4.51',
    'improvement',
    '- Keşif listesi sayfasında (pages/1/kesif/list.php) işlem sütununun dar kalması sebebiyle aksiyon butonlarının (Düzenle, Sil, Diğer Menüsü) sağ kenardan kesilmesi ve sığmaması sorunu düzeltildi.\n- İşlem sütunu genişliği 100px olarak genişletildi ve buton grubu için nowrap, merkezleme ve taşma önleyici stiller tanımlandı.\n- Tablo sütun genişlikleri orantılı olarak dengelendi.',
    'Antigravity AI',
    '2026-09-23 21:01:00',
    '2026-09-23 21:01:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.51'
);
