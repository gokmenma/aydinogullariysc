-- Sürüm Notu: Notlar Listesi Tablosuna Başlangıç Tarihi Sütunu Eklenmesi (v2.4.25)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.25',
    'Notlar Listesi Tablosuna Başlangıç Tarihi Sütunu Eklenmesi',
    '- Notlar sayfasındaki (all-notes) ana listeleme tablosuna "Başlangıç Tarihi" sütunu ve filtreleme desteği eklendi.\n- Başlangıç ve bitiş tarihleri şık takvim rozetleri (badge-date) ile görselleştirilerek notların zaman aralıklarının hızlıca takip edilebilmesi sağlandı.',
    'improvement',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.25'
);
