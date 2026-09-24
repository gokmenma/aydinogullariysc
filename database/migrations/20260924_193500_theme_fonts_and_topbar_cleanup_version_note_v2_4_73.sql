-- Sürüm Notu: Üst Menü Düzenlemesi, Bağımsız Topbar / Sidebar Renklendirme, Yeni Temalar ve Tipografi

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT
    'v2.4.73',
    'Bağımsız Topbar & Sidebar Renk Seçimi, Üst Menü Düzenlemesi ve Genişletilmiş Tema & Yazı Tipleri',
    'feature',
    '- Tema Özelleştirici paneline Topbar (Üst Menü) ve Sidebar (Sol Menü) için birbirinden bağımsız renk paleti seçicileri eklendi (16 farklı Topbar rengi ve 11 farklı Sidebar rengi serbestçe karıştırılabilir).\n- Üst menüden (topbar) SMS ve E-Posta butonları kaldırılarak başlık çubuğu daha sade ve odaklı hale getirildi.\n- Renkli topbar ve gri/anstrasit sidebar kombinasyonlarına sahip 8 yeni hazır tema (Modern Çelik, Antrasit Zümrüt, Dumanlı Bordo, Grafiti Mor, Kül Amber, Petrol Taş, Platin Mavi, Titan Okyanus) eklendi.\n- Tipografi seçeneklerine DM Sans, Manrope, Space Grotesk, Urbanist, Figtree ve Sora gibi modern SaaS ve kurumsal arayüz yazı tipleri dahil edildi.',
    'Antigravity AI',
    '2026-09-24 19:35:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.73'
);

-- Eğer kayıt daha önce eklendiyse açıklama ve başlığı güncelle
UPDATE `version_notes`
SET `title` = 'Bağımsız Topbar & Sidebar Renk Seçimi, Üst Menü Düzenlemesi ve Genişletilmiş Tema & Yazı Tipleri',
    `description` = '- Tema Özelleştirici paneline Topbar (Üst Menü) ve Sidebar (Sol Menü) için birbirinden bağımsız renk paleti seçicileri eklendi (16 farklı Topbar rengi ve 11 farklı Sidebar rengi serbestçe karıştırılabilir).\n- Üst menüden (topbar) SMS ve E-Posta butonları kaldırılarak başlık çubuğu daha sade ve odaklı hale getirildi.\n- Renkli topbar ve gri/anstrasit sidebar kombinasyonlarına sahip 8 yeni hazır tema (Modern Çelik, Antrasit Zümrüt, Dumanlı Bordo, Grafiti Mor, Kül Amber, Petrol Taş, Platin Mavi, Titan Okyanus) eklendi.\n- Tipografi seçeneklerine DM Sans, Manrope, Space Grotesk, Urbanist, Figtree ve Sora gibi modern SaaS ve kurumsal arayüz yazı tipleri dahil edildi.'
WHERE `version_tag` = 'v2.4.73';

-- Migration: Görev Yönetimi Modülü Premium Tema & MVC Modernizasyonu
-- Version: v2.4.74
-- Date: 2026-09-24 19:55:00

INSERT INTO version_notes (title, version_tag, category, description, author, created_at, updated_at)
SELECT 
    'Görev Yönetimi Modülü Premium Tema & MVC Modernizasyonu',
    'v2.4.74',
    'improvement',
    '- Görevlerim (`my-missions.php`), Verdiğim Görevler (`mygmissions.php`), Sistemdeki Tüm Görevler (`all-missions.php`), Görev Detayı (`view-mission.php`) ve Görev Oluştur (`new-mission.php`) sayfaları kurumsal premium-theme tasarım diline kavuşturuldu.\n- Veritabanı ve iş mantığı işlemleri için `App\\Model\\MissionModel` modeli oluşturularak tüm görev sorgulama, durum güncelleme ve silme operasyonları MVC mimarisine taşındı.\n- Sayfa başlarına yüksek kontrastlı okunabilir istatistik hapları (Stat Pills) ve kompakt 4 adet renk vurgulu KPI özet kartı (Toplam, Bekleyen, Tamamlanan, Acil) entegre edildi.\n- Arama kutusu yanına özet kartlarını tek tıkla gizleyip açan ve tercihi yerel depolamada saklayan toggle butonu eklendi.\n- Görev tabloları DataTables ve table-filter.js altyapısıyla modernize edildi; arama kutuları, aciliyet seviyeleri (Yüksek, Orta, Düşük) ve durum rozetleri estetik soft badge görünümüne geçirildi.\n- Görevi oluşturan ve atanan personeller için avatar baş harfli şık kullanıcı rozetleri eklendi; birden fazla personele atanan görevlerin gösterimi optimize edildi.\n- Görev tamamlama ve silme işlemleri için SweetAlert2 onay pencereleri entegre edildi.',
    'Antigravity AI',
    '2026-09-24 19:55:00',
    '2026-09-24 19:55:00'
WHERE NOT EXISTS (
    SELECT 1 FROM version_notes WHERE version_tag = 'v2.4.74'
);
