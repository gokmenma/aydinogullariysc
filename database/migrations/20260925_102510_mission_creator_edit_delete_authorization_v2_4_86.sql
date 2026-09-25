-- Sürüm Notu: Görev Sahipliği Tabanlı Düzenleme ve Silme Yetkisi
-- Sürüm: v2.4.86
-- Tarih: 2026-09-25 10:25:10

INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT
    'v2.4.86',
    'Görev Sahipliği Tabanlı Düzenleme ve Silme Yetkisi',
    '- Görev düzenleme ve silme işlemleri yalnızca görevi oluşturan kullanıcıya sınırlandırıldı.\n- Sistemdeki Tüm Görevler listesi, hızlı önizleme penceresi ve görev detay sayfasındaki işlem butonları sahiplik kuralına uyarlandı.\n- Doğrudan URL ve değiştirilmiş isteklerle yapılabilecek yetkisiz güncelleme ve silme girişimleri sunucu tarafında engellendi.',
    'security',
    'Antigravity AI',
    '2026-09-25 10:25:10'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.86'
);
