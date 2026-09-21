-- Sürüm Notu: Sistem Aktiviteleri Tablo Filtreleme Motoru ve Seçim Kutusu Modernizasyonu
-- Tarih: 2026-09-21 09:41:40
-- Sürüm: v2.4.3

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Sistem Aktiviteleri Tablo Filtreleme Motoru ve Seçim Kutusu Modernizasyonu',
    'v2.4.3',
    'bugfix',
    '- Sunucu taraflı/statik DOM tablolarında sütun filtre kurallarının çalışmaması sorunu giderildi (filterDOMTable entegrasyonu).\n- DataTable ve saf HTML tablolarının ikisinde de çoklu filtrelerin anlık ve doğru çalışması sağlandı.\n- Seçim kutularındaki checkbox binişme ve ilk harf kesilme sorunu izole özel sınıflarla çözüldü.\n- Çift scrollbar problemi giderildi, ince ve modern kaydırma çubuğu eklendi.\n- Canlı arama, dinamik sayaç, tümünü seç / seçimi kaldır aksiyonları ve seçili satır vurgusu eklendi.',
    'Antigravity AI',
    '2026-09-21 09:41:40'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.3' AND `title` = 'Sistem Aktiviteleri Tablo Filtreleme Motoru ve Seçim Kutusu Modernizasyonu'
);
