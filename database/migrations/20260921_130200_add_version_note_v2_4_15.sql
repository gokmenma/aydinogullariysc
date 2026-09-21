-- Sürüm Notu: Sistem Aktiviteleri Tablo Filtrelerinde Tüm Veritabanı Toplam Sayılarının Entegrasyonu
-- Tarih: 2026-09-21 13:02:00
-- Sürüm: v2.4.15

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Sistem Aktiviteleri Tablo Filtrelerinde Tüm Veritabanı Toplam Sayılarının Entegrasyonu',
    'v2.4.15',
    'bugfix',
    '- Sistem Aktiviteleri (Logs) sayfasında sayfalanmış DOM tablosu için Kullanıcı, İşlem Türü ve Modül sütun filtrelerine tüm veritabanındaki (40.725 kayıt) gerçek toplam sayılar entegre edildi.\n- TableFilter motoruna data-filter-counts desteği eklenerek tek sayfadaki 50 kayıt yerine tüm kullanıcı ve aktivite havuzunun listelenmesi sağlandı.\n- Sıfır (0) olan seçenekler filtrelendi ve doğru sayılarla gösterildi.',
    'Antigravity AI',
    '2026-09-21 13:02:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.15' AND `title` = 'Sistem Aktiviteleri Tablo Filtrelerinde Tüm Veritabanı Toplam Sayılarının Entegrasyonu'
);
