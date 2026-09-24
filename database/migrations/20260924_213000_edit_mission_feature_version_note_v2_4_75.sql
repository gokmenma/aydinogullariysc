-- Sürüm Notu Migration: Görev Düzenleme (Edit Mission) Özelliği & Sayfası
-- Sürüm: v2.4.75
-- Tarih: 2026-09-24 21:30:00

-- 1. Sayfa Tanımı
INSERT INTO `pages` (`p_title`, `p_link`, `pid`)
SELECT 'Görev Düzenle', 'edit-mission', 0
WHERE NOT EXISTS (
    SELECT 1 FROM `pages` WHERE `p_link` = 'edit-mission'
);

-- 2. Sürüm Notu
INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.75',
    'Görev Düzenleme Modülü & Yönetim Altyapısı',
    'feature',
    '- Görev Yönetimi modülüne eksik olan "Görev Düzenle" (Edit Mission) sayfası (pages/1/edit-mission.php) ve işlem altyapısı eklendi.
- Görevi oluşturan kullanıcının veya tüm görevleri yönetme yetkisine (allmisview) sahip yöneticilerin görevin başlık, firma, kategori, başlangıç/bitiş tarihi, atanan personeller, aciliyet seviyesi, durum ve açıklamasını güncelleyebilmesi sağlandı.
- Verdiğim Görevler (mygmissions.php) ve Tüm Görevler (all-missions.php) tablolarına "Düzenle" işlem butonu (btn-table-edit) eklendi.
- Hızlı Önizleme Modalı (Quick Preview Modal) ve Görev Detay (view-mission.php) sayfasına yetkili kullanıcılar için doğrudan düzenleme bağlantısı entegre edildi.
- MissionModel içerisine güvenli PDO sorguları ve ActivityLog denetim iziyle updateMission() metodu eklendi.',
    'Antigravity AI',
    '2026-09-24 21:30:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.75'
);
