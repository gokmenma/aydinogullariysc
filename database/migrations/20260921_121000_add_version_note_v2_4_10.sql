-- Migration: Add Version Note v2.4.10
-- Description: DataTable ve DOM Tablo Filtrelerinde Dinamik Sayfalama ve Bilgi Senkronizasyonu

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Tablo Filtrelerinde Dinamik Sayfalama ve Kayıt Sayısı Senkronizasyonu',
    'v2.4.10',
    'improvement',
    '- Gelişmiş tablo filtreleme motorunda (table-filter.js) filtre uygulandığında sayfalama butonları ve bilgi metninin (dataTables_info) filtrelenen kayıt adedine göre anlık dinamik olarak yeniden hesaplanması sağlandı.
- Filtre uygulandığında toplam kayıt sayısı, filtrelenen kayıt sayısı ve aktif sayfa aralığının (örn: 1 - 50, 1 - 5 vb.) doğru biçimde gösterilmesi sağlandı.
- Filtrelenen sonuçlar üzerinde dinamik istemci taraflı sayfalama (Önceki, 1, 2, 3... Sonraki) desteği eklendi.
- Filtreler temizlendiğinde orijinal sunucu/sayfa durumuna ve sayfa bağlantılarına eksiksiz geri dönülmesi sağlandı.
- Aktivite günlükleri (logs/index.php) tablosuna stabil ID tanımlanarak filtre-sayfalama entegrasyonu güçlendirildi.',
    'Antigravity AI',
    '2026-09-21 12:10:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `title` = 'Tablo Filtrelerinde Dinamik Sayfalama ve Kayıt Sayısı Senkronizasyonu'
);
