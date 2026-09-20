-- Dünden bugüne (19-20 Eylül 2026) yapılan geliştirmelerin sürüm notlarına eklenmesi
-- Tarih: 2026-09-20

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 'Merkezi Tablo Filtreleme Motoru ve Geist Tipografi Entegrasyonu', 'v2.4.0', 'improvement', '- Tüm DataTables tabloları için gelişmiş dinamik filtreleme altyapısı (table-filter.js) entegre edildi.\n- Sütun bazlı metin, sayı, tarih ve çoklu seçim filtre kuralları (İçerir, Eşittir, Büyüktür, Küçüktür, Aralık vb.) eklendi.\n- Sistem geneli yazı tipi modern Google Geist font ailesine geçirildi.\n- Ana sayfa, teklifler, müşteriler, ürünler ve servis listeleri yeni premium tema ile modernize edildi.\n- Tablo filtre durumunu URL ve yerel depolamada saklama desteği getirildi.', 'Antigravity AI', '2026-09-19 14:04:28'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.0' AND `title` LIKE 'Merkezi Tablo Filtreleme%');

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 'Google Drive Bulut Yedekleme ve Otomatik Cron Servisi', 'v2.4.1', 'feature', '- Google Drive API OAuth 2.0 entegrasyonu (BackupService.php ve BackupModel.php) tamamlandı.\n- Tek tıkla veritabanı (MySQL dump) ve sistem dosyalarını zipleyerek yerel ve Google Drive bulutuna yedekleme özelliği eklendi.\n- Periyodik otomatik yedekleme için CLI tabanlı cron scripti (cron_backup.php) geliştirildi.\n- Yedekleme ayarları, yedek logları ve dosya boyutu kontrolü yönetim paneline entegre edildi.\n- backups yönetim sayfası modern responsive arayüz ile sıfırdan oluşturuldu.', 'Antigravity AI', '2026-09-19 16:07:29'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.1');

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 'Kapsamlı Modül Dashboard\'ları ve KPI İstatistik Panelleri', 'v2.4.2', 'feature', '- Müşteri Yönetimi için interaktif KPI kartları, il/bölge dağılımı ve müşteri segment analiz paneli (customers/dashboard.php) eklendi.\n- Teklif Yönetimi için onay/bekleme oranları, toplam hacim ve döviz bazlı dönüşüm analiz paneli (offers/dashboard.php) eklendi.\n- Ürün/Hizmet Yönetimi için stok devir hızı, en çok satanlar ve kategori bazlı gelir dağılım paneli (products/dashboard.php) eklendi.\n- Satın Alma Modülü için tedarikçi hacimleri, bekleyen onaylar ve maliyet dağılım paneli (purchases/dashboard.php) eklendi.\n- Raporlar Modülü için test/yangın tüpü raporlama performans ve periyot takip paneli (reports/dashboard.php) eklendi.\n- Görev Yönetimi (tasks.php, task-edit.php) ve Notlar (all-notes.php) sayfaları modern filtreleme ve kart arayüzüyle yenilendi.', 'Antigravity AI', '2026-09-19 22:00:05'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.2');

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 'Servis Yönetimi İstatistik Paneli ve Tablo Düzenlemeleri', 'v2.4.3', 'feature', '- Servis Yönetimi için durum bazlı dağılım, teknisyen performansı ve aylık servis trendi dashboard\'u (service/dashboard.php) eklendi.\n- Satın alma ve fiyat talep listelerinde tablo sütun genişlikleri optimize edilerek yatay kaydırma önlendi.\n- Tablo içi işlem butonlarının görünürlüğü ve hizalamaları iyileştirildi.', 'Antigravity AI', '2026-09-19 23:36:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.3');

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 'Keşif Dashboard Modülü ve Finansal Veri Görüntüleme Yetkilendirmesi', 'v2.4.4', 'feature', '- Keşif analiz ve takip süreçleri için modern Keşif Dashboard\'u (kesif/dashboard.php, KesifModel.php) sisteme kazandırıldı.\n- Ana sayfa üzerindeki finansal veri ve ciro kartlarının role dayalı yetkilendirmesi (permission_home_financial) tamamlandı.\n- Kullanıcı profili şifre değiştirme sayfası ve güvenlik modalı (pages/1/profile-password.php) kullanıma sunuldu.\n- Dashboard bileşenlerinin Koyu Mod (Dark Mode) renk uyumu ve Chart.js/ApexCharts konfigürasyonları optimize edildi.', 'Antigravity AI', '2026-09-20 11:03:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.4');

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 'Teklif Ret Nedeni Takibi ve Dashboard Standartlaştırması', 'v2.4.5', 'improvement', '- Teklif iptal ve ret süreçlerinde müşteriden gelen ret gerekçesinin seçilip kaydedilebilmesi için reject_reason alanı ve modal entegrasyonu sağlandı.\n- Teklif şablonları yönetimi modernize edilerek SettingsModel üzerinden yapılandırıldı.\n- Tüm dashboard ekranlarındaki tablo ve kart stilleri ortak tasarım standartlarına (dashboard-unified.css) kavuşturuldu.', 'Antigravity AI', '2026-09-20 15:08:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.5');

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 'Yedek Silme Yetkilendirmesi ve Google Drive Trash Entegrasyonu', 'v2.4.6', 'security', '- Sistem yedeklerinin silinmesi işlemi özel yetki kontrolüne (permission_backup_delete) bağlandı.\n- Google Drive üzerindeki yedeklerin silinmesi durumunda doğrudan kalıcı silme yerine önce Google Drive Çöp Kutusu\'na (Trash) taşınması sağlandı.\n- Uzaktan dosya takibi için remote_file_id eşleştirmesi ve çift taraflı senkronizasyon mekanizması kuruldu.\n- Kullanıcı düzenleme sayfasındaki yönlendirme kontrolleri optimize edildi.', 'Antigravity AI', '2026-09-20 15:52:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.6');
