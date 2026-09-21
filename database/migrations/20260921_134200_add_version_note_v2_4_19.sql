-- Sürüm Notu: Kullanıcı Sütun Filtresi Gelişmiş Eşleştirme ve DataTables ServerSide Tespiti Güçlendirmesi (v2.4.19)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.19',
    'Kullanıcı Sütun Filtresi Gelişmiş Eşleştirme ve DataTables ServerSide Tespiti Güçlendirmesi',
    '- App/api/get-logs.php içinde kullanıcı filtresi için kullanıcı adından dinamik user ID çözümleme (l.user_id IN / l.author IN) ve TRIM/büyük-küçük harf toleranslı çoklu eşleştirme altyapısı kuruldu.\n- include/js/table-filter.js ext.search kancasında DataTables serverSide tespiti güçlendirilerek kullanıcı kolonu dahil tüm filtrelerin sunucu taraflı sorunsuz çalışması sağlandı.',
    'improvement',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.19'
);
