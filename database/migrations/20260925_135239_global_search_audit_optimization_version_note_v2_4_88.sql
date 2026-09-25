-- Sürüm Notu: Global Arama Log Optimizasyonu
-- Sürüm: v2.4.88
-- Tarih: 2026-09-25 13:52:39

INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT
    'v2.4.88',
    'Global Arama Log Optimizasyonu',
    '- Global aramada her harf ve sonuç isteği için ayrı aktivite kaydı oluşturulması engellendi.\n- Kullanıcının yazmayı bitirmesi, aramayı temizlemesi/kapatması veya bir sonuç seçmesi halinde son başarılı arama için yalnızca bir log kaydı oluşturulması sağlandı.\n- Sonuç seçimi sırasında seçilen kayıt türü ve kimliği audit detaylarına eklendi.\n- Devam eden arama isteğinin pencere kapatıldığında sonuç listesini yeniden açması önlendi.',
    'improvement',
    'Antigravity AI',
    '2026-09-25 13:52:39'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.88'
);
