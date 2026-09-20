-- Sürüm notları sayfalama ve zaman aralığı güncellemesi sürüm notu
-- Tarih: 2026-09-20

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 'Sürüm Notları Sayfasına Dinamik Sayfalama, Zaman Aralığı Filtresi ve Tablo Görünümü Eklendi', 'v2.5.2', 'improvement', '- İlerleyen dönemlerde kayıt sayısı arttıkça oluşabilecek yavaşlamaları önlemek için varsayılan olarak ''Son 1 Ay'' veri çekme filtresi ve dinamik sayfalama (pagination) altyapısı entegre edildi.\n- Zaman Aralığı hızlı butonları (Son 1 Ay, Son 3 Ay, Son 6 Ay, Bu Yıl, Tüm Zamanlar, Özel Aralık) eklendi.\n- Görünüm Değiştirici (View Switcher) eklenerek Zaman Çizelgesi (Timeline) ve DataTables Tablo görünümü arasında anlık geçiş imkanı sağlandı.\n- AJAX tabanlı veri yükleme mekanizması ile sayfa yenilenmeden milisaniyeler içinde hızlı filtreleme ve sayfa geçişi sağlandı.', 'Antigravity AI', '2026-09-20 22:58:03'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.5.2');
