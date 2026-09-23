-- Sürüm Notu: v2.4.57
INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`, `updated_at`)
SELECT 
    'Müşteri Formuna Haritadan Konum Seçimi ve Select2 Entegrasyonu',
    'v2.4.57',
    'feature',
    '- customers/manage (Müşteri Ekleme/Düzenleme) sayfasındaki adres alanının sağ üst köşesine "Haritadan Seç" butonu eklendi.\n- Google Maps Yol Katmanı ve yerel Leaflet altyapısı ile filigransız, yüksek çözünürlüklü interaktif harita modalı entegre edildi.\n- Akıllı backend geocoding proxy ile Türkçe adresler ve kısaltmalar (Cd., Sk., Mah., No) otomatik ayrıştırılıp koordinata çevrildi.\n- Formdaki tüm seçim kutuları (Grup, İl, İlçe, Bölge) modern Select2 yapısına dönüştürüldü; arama, filtreleme, validasyon ve haritadan otomatik aktarım senkronizasyonu sağlandı.',
    'Antigravity AI',
    '2026-09-23 22:00:00',
    '2026-09-23 22:00:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.57'
);
