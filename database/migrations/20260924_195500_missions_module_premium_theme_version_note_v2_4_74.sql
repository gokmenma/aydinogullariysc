-- Sürüm Notu Migration: Görev Yönetimi Modernizasyonu ve Hızlı Önizleme Modalı
-- Sürüm: v2.4.74
-- Tarih: 2026-09-24 21:05:00

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.74',
    'Görev Yönetimi Premium Tema Modernizasyonu & Hızlı Önizleme Modalı',
    'feature',
    '- Görev Yönetimi modülündeki tüm sayfalar (Görevlerim, Verdiğim Görevler, Sistemdeki Tüm Görevler, Görev Oluştur, Görev Detay) Premium Tema kurallarına ve yüksek kontrast standartlarına uyarlandı.
- Görüntüle butonu ve görev başlıklarına tıklandığında sayfadan ayrılmadan açılan interaktif Hızlı Önizleme Modalı (Quick Preview Modal) eklendi; modal başlığında ferah yerleşim, sağa hizalı modern pill rozetler (Aciliyet & Durum), doğrudan görev tamamlama ve tam sayfada açma imkanı sağlandı.
- Özet (KPI) kartları için arama kutusunun sağında yer alan ve açık/kapalı durumunu localStorage ile koruyan daraltma/genişletme butonu standardı uygulandı ve AGENTS.md proje kurallarına eklendi.
- Yeni Görev Oluştur sayfasındaki aciliyet butonları (Yüksek, Orta, Düşük) tam satırı kaplayacak biçimde (CSS Grid, gap: 8px) %100 genişliğe uyarlandı.
- Zengin metin editörlerinde (WYSIHTML5 & Summernote) placeholder ve içerik alanlarının üst ve sol iç boşlukları (padding: 6px 12px) araç çubuğunun hemen altından başlayacak şekilde optimize edildi.
- Görevler için MVC Model katmanı (App/Model/MissionModel.php) oluşturularak tüm veritabanı sorguları güvenli PDO mimarisine taşındı.',
    'Antigravity AI',
    '2026-09-24 21:05:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.74'
);
