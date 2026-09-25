-- Aydınoğulları YSC merkezi sürüm notları
-- Tüm sürüm notları bu dosyada kısa, kullanıcı odaklı ve tekrar çalıştırılabilir tutulur.

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.49', 'Veritabanı ve Yedekleme Düzenlemesi', 'improvement', '- Veritabanı bağlantısı ve bulut yedekleme akışı iyileştirildi.', 'Antigravity AI', '2026-09-23 20:05:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.49');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.50', 'Servis Durum Rozeti Düzeltmesi', 'bugfix', '- Uzun servis durumlarının tablo sütunlarına taşması önlendi.', 'Antigravity AI', '2026-09-23 20:58:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.50');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.51', 'Keşif İşlem Alanı Düzenlemesi', 'improvement', '- Keşif listesindeki işlem butonlarının görünümü ve sütun genişliği düzenlendi.', 'Antigravity AI', '2026-09-23 21:01:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.51');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.52', 'Fiyat Talebi Detay Ekranı', 'improvement', '- Fiyat talebi detay penceresi daha sade, okunabilir ve işlem odaklı hale getirildi.', 'Antigravity AI', '2026-09-23 21:05:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.52');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.53', 'Müşteri Özet Kartları Hizalaması', 'improvement', '- Müşteri özet kartları sayfa genişliği ve mobil görünümle uyumlu hale getirildi.', 'Antigravity AI', '2026-09-23 21:25:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.53');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.54', 'Müşteri Özet Kartlarını Gizleme', 'feature', '- Müşteri özet kartlarına kullanıcı tercihini hatırlayan gizle/göster seçeneği eklendi.', 'Antigravity AI', '2026-09-23 21:32:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.54');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.55', 'Ana Sayfa Hızlı İşlemleri', 'improvement', '- Ana sayfadaki hızlı işlem butonları daha kompakt ve erişilebilir hale getirildi.', 'Antigravity AI', '2026-09-23 21:45:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.55');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.56', 'Logo ve Koyu Tema Uyumu', 'improvement', '- Logolar farklı tema ve ekranlarda daha net görünecek şekilde güncellendi.', 'Antigravity AI', '2026-09-23 21:55:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.56');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.57', 'Müşteri Formunda Harita Seçimi', 'feature', '- Müşteri adresi haritadan seçilebilir hale getirildi ve seçim alanları geliştirildi.', 'Antigravity AI', '2026-09-23 22:00:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.57');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.58', 'Tanımlamalar Menüsü Yetkileri', 'bugfix', '- Tanımlamalar menüsünün görünürlük ve işlem yetkileri düzeltildi.', 'Antigravity AI', '2026-09-23 22:35:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.58');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.59', 'Teklif Numarasından Hızlı Erişim', 'improvement', '- Yetkili kullanıcılar teklif numarasından doğrudan düzenleme ekranına geçebilir.', 'Antigravity AI', '2026-09-24 08:10:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.59');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.60', 'Keşif Bilgi Balonu Düzeltmesi', 'bugfix', '- Keşif listesindeki uzun açıklamaların bilgi balonunda kesilmesi giderildi.', 'Antigravity AI', '2026-09-24 08:17:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.60');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.61', 'Tema ve Keşif Görünüm Düzeltmeleri', 'improvement', '- Sayfalama renkleri temayla uyumlu hale getirildi; keşif bilgi balonu ve eksik görsel hataları düzeltildi.', 'Antigravity AI', '2026-09-24 08:20:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.61');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.62', 'Keşif Listesi Uzun Metin Görünümü', 'improvement', '- Uzun keşif açıklamaları tabloda kısaltıldı; tam içerik bilgi balonu ve dışa aktarmada korundu.', 'Antigravity AI', '2026-09-24 08:26:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.62');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.63', 'Keşif Kaydına Hızlı Düzenleme', 'feature', '- Yapılacak iş alanından ilgili keşif kaydını düzenleme kolaylığı eklendi.', 'Antigravity AI', '2026-09-24 08:27:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.63');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.64', 'Teklif Arama Düzeltmesi', 'bugfix', '- Teklif listesinde bazı aramaların hata vermesine neden olan karakter karşılaştırma sorunu giderildi.', 'Antigravity AI', '2026-09-24 09:22:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.64');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.65', 'Arama Kutusu İyileştirmesi', 'improvement', '- Tablo aramalarına arama simgesi ve tek tıkla temizleme seçeneği eklendi.', 'Antigravity AI', '2026-09-24 09:35:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.65');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.66', 'Hidrostatik Rapor Düzenleme Düzeltmesi', 'bugfix', '- Hidrostatik test raporunun düzenleme ekranında açılmasını engelleyen hata giderildi.', 'Antigravity AI', '2026-09-24 10:18:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.66');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.67', 'Rapor Kayıt Bilgileri', 'feature', '- Rapor listesine kayıt tarihi ve kaydı oluşturan kullanıcı bilgileri eklendi.', 'Antigravity AI', '2026-09-24 10:32:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.67');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.68', 'Rapor ve Bakım Modu İyileştirmeleri', 'improvement', '- Rapor tablosu işlemleri iyileştirildi ve bakım modu yetki kontrollü hale getirildi.', 'Antigravity AI', '2026-09-24 11:26:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.68');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.69', 'Bakım Sayfası Yenilemesi', 'improvement', '- Bakım sayfası mobil ve koyu tema uyumlu, daha anlaşılır bir görünüme kavuştu.', 'Antigravity AI', '2026-09-24 12:20:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.69');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.70', 'Bakım Ekranı Görsel Güncellemesi', 'improvement', '- Bakım ekranı yangın güvenliği temasına uygun görsellerle güncellendi.', 'Antigravity AI', '2026-09-24 12:50:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.70');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.71', 'Bakım Sayfası Yazı Tipi', 'improvement', '- Bakım sayfasının yazı tipi ve okunabilirliği iyileştirildi.', 'Antigravity AI', '2026-09-24 13:00:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.71');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.72', 'Belge Firma Başlıkları', 'improvement', '- Rapor ve tekliflerdeki firma başlıkları dinamik ve dengeli hale getirildi.', 'Antigravity AI', '2026-09-24 16:05:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.72');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.73', 'Tema ve Üst Menü Seçenekleri', 'feature', '- Üst menü ve sol menü renkleri bağımsız seçilebilir hale getirildi; yeni tema ve yazı tipleri eklendi.', 'Antigravity AI', '2026-09-24 19:35:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.73');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.74', 'Görev Yönetimi Yenilemesi', 'improvement', '- Görev sayfaları, özet kartları ve işlem akışları daha sade ve tutarlı hale getirildi.', 'Antigravity AI', '2026-09-24 19:55:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.74');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.75', 'Görev Düzenleme', 'feature', '- Yetkili kullanıcılar için görev düzenleme ve güvenli güncelleme akışı eklendi.', 'Antigravity AI', '2026-09-24 21:30:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.75');

DELETE FROM `version_notes` WHERE `version_tag` = 'v2.4.76';
INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
VALUES ('v2.4.76', 'E-Posta Şablonları ve Gönderim Kolaylıkları', 'feature', '- Kişisel e-posta şablonları, akıllı alıcı doldurma ve editör kullanım iyileştirmeleri eklendi.', 'Antigravity AI', '2026-09-24 21:50:51');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.77', 'Yönetim Formlarında Seçim Alanları', 'improvement', '- Yönetim sayfalarındaki seçim alanlarının arama ve kullanım deneyimi iyileştirildi.', 'Antigravity AI', '2026-09-25 09:03:57'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.77');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.78', 'Not Görünürlüğü ve Hızlı İşlemler', 'feature', '- Notlara özel görünürlük seçenekleri ve hızlı işlem araçları eklendi.', 'Antigravity AI', '2026-09-25 09:08:31'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.78');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.80', 'Ekip Üyesi Bildirimleri', 'bugfix', '- Ekip üyesi kayıt ve güncelleme bildirimleri düzeltildi.', 'Antigravity AI', '2026-09-25 15:45:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.80');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.81', 'Görev Tabloları Boş Durumu', 'bugfix', '- Görev tablolarındaki boş sonuç görünümü temayla uyumlu hale getirildi.', 'Antigravity AI', '2026-09-25 09:31:14'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.81');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.82', 'Ürün Listesi Yetki Düzeltmesi', 'bugfix', '- Ürün listesinin yetkili kullanıcılar için yüklenmesini engelleyen hata giderildi.', 'Antigravity AI', '2026-09-25 09:33:52'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.82');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.83', 'Tablo Boş Durum Tasarımı', 'improvement', '- Veri bulunmayan tablolar için ortak ve anlaşılır boş durum görünümü eklendi.', 'Antigravity AI', '2026-09-25 09:37:07'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.83');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.84', 'Bildirim Pencerelerinde Tema Uyumu', 'improvement', '- Bildirim pencereleri seçili tema yazı tipiyle uyumlu hale getirildi.', 'Antigravity AI', '2026-09-25 09:44:34'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.84');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.85', 'Profil ve Kullanıcı Aktiviteleri', 'feature', '- Profil sayfası sekmeli yapıya geçirildi ve kullanıcı aktiviteleri erişilebilir hale getirildi.', 'Antigravity AI', '2026-09-25 09:50:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.85');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.86', 'Görev Sahipliği Yetkileri', 'security', '- Görev düzenleme ve silme işlemleri görev sahibi ve yetkili kullanıcılarla sınırlandırıldı.', 'Antigravity AI', '2026-09-25 10:25:10'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.86');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.87', 'Keşif Formunda Firma Arama', 'improvement', '- Keşif formundaki firma seçimi aranabilir ve daha hızlı kullanılabilir hale getirildi.', 'Antigravity AI', '2026-09-25 11:41:35'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.87');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.88', 'Global Arama Kayıt Optimizasyonu', 'improvement', '- Global arama daha az ve daha anlamlı aktivite kaydı oluşturacak şekilde düzenlendi.', 'Antigravity AI', '2026-09-25 13:52:39'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.88');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.89', 'Rapor Formları Tema Yenilemesi', 'feature', '- YAS, OYS ve AAS rapor formları ortak tema, gelişmiş seçim alanları ve koyu mod desteğiyle yenilendi.', 'Antigravity AI', '2026-09-25 14:15:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.89');

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 'v2.4.90', 'Yazı Kalınlığı Ayarı', 'feature', '- Tema özelleştiriciye kullanıcı tercihini hatırlayan yazı kalınlığı seçimi eklendi.', 'Antigravity AI', '2026-09-25 15:25:00'
WHERE NOT EXISTS (SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.90');

-- Aynı Teklif İcmali geliştirmesinin ara kayıtlarını tek teslimat kaydında birleştir.
DELETE FROM `version_notes` WHERE `version_tag` IN ('v2.4.91', 'v2.4.92', 'v2.4.93', 'v2.4.94');
DELETE FROM `version_notes` WHERE `version_tag` = 'v2.4.95';

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
VALUES (
    'v2.4.95',
    'Firma Teklif İcmali',
    'feature',
    '- Müşteri teklifleri tek ekranda özetlenebilir, filtrelenebilir; kurumsal PDF, yazdırma ve Excel çıktısı alınabilir hale getirildi.',
    'Antigravity AI',
    '2026-09-25 20:58:00'
);

-- Daha önce eklenmiş kayıtları da kısa kullanıcı metinleriyle eşitle.
UPDATE `version_notes` SET `title` = 'Veritabanı ve Yedekleme Düzenlemesi', `description` = '- Veritabanı bağlantısı ve bulut yedekleme akışı iyileştirildi.' WHERE `version_tag` = 'v2.4.49';
UPDATE `version_notes` SET `title` = 'Servis Durum Rozeti Düzeltmesi', `description` = '- Uzun servis durumlarının tablo sütunlarına taşması önlendi.' WHERE `version_tag` = 'v2.4.50';
UPDATE `version_notes` SET `title` = 'Keşif İşlem Alanı Düzenlemesi', `description` = '- Keşif listesindeki işlem butonlarının görünümü ve sütun genişliği düzenlendi.' WHERE `version_tag` = 'v2.4.51';
UPDATE `version_notes` SET `title` = 'Fiyat Talebi Detay Ekranı', `description` = '- Fiyat talebi detay penceresi daha sade, okunabilir ve işlem odaklı hale getirildi.' WHERE `version_tag` = 'v2.4.52';
UPDATE `version_notes` SET `title` = 'Müşteri Özet Kartları Hizalaması', `description` = '- Müşteri özet kartları sayfa genişliği ve mobil görünümle uyumlu hale getirildi.' WHERE `version_tag` = 'v2.4.53';
UPDATE `version_notes` SET `title` = 'Müşteri Özet Kartlarını Gizleme', `description` = '- Müşteri özet kartlarına kullanıcı tercihini hatırlayan gizle/göster seçeneği eklendi.' WHERE `version_tag` = 'v2.4.54';
UPDATE `version_notes` SET `title` = 'Ana Sayfa Hızlı İşlemleri', `description` = '- Ana sayfadaki hızlı işlem butonları daha kompakt ve erişilebilir hale getirildi.' WHERE `version_tag` = 'v2.4.55';
UPDATE `version_notes` SET `title` = 'Logo ve Koyu Tema Uyumu', `description` = '- Logolar farklı tema ve ekranlarda daha net görünecek şekilde güncellendi.' WHERE `version_tag` = 'v2.4.56';
UPDATE `version_notes` SET `title` = 'Müşteri Formunda Harita Seçimi', `description` = '- Müşteri adresi haritadan seçilebilir hale getirildi ve seçim alanları geliştirildi.' WHERE `version_tag` = 'v2.4.57';
UPDATE `version_notes` SET `title` = 'Tanımlamalar Menüsü Yetkileri', `description` = '- Tanımlamalar menüsünün görünürlük ve işlem yetkileri düzeltildi.' WHERE `version_tag` = 'v2.4.58';
UPDATE `version_notes` SET `title` = 'Teklif Numarasından Hızlı Erişim', `description` = '- Yetkili kullanıcılar teklif numarasından doğrudan düzenleme ekranına geçebilir.' WHERE `version_tag` = 'v2.4.59';
UPDATE `version_notes` SET `title` = 'Keşif Bilgi Balonu Düzeltmesi', `description` = '- Keşif listesindeki uzun açıklamaların bilgi balonunda kesilmesi giderildi.' WHERE `version_tag` = 'v2.4.60';
UPDATE `version_notes` SET `title` = 'Tema ve Keşif Görünüm Düzeltmeleri', `description` = '- Sayfalama renkleri temayla uyumlu hale getirildi; keşif bilgi balonu ve eksik görsel hataları düzeltildi.' WHERE `version_tag` = 'v2.4.61';
UPDATE `version_notes` SET `title` = 'Keşif Listesi Uzun Metin Görünümü', `description` = '- Uzun keşif açıklamaları tabloda kısaltıldı; tam içerik bilgi balonu ve dışa aktarmada korundu.' WHERE `version_tag` = 'v2.4.62';
UPDATE `version_notes` SET `title` = 'Keşif Kaydına Hızlı Düzenleme', `description` = '- Yapılacak iş alanından ilgili keşif kaydını düzenleme kolaylığı eklendi.' WHERE `version_tag` = 'v2.4.63';
UPDATE `version_notes` SET `title` = 'Teklif Arama Düzeltmesi', `description` = '- Teklif listesinde bazı aramaların hata vermesine neden olan karakter karşılaştırma sorunu giderildi.' WHERE `version_tag` = 'v2.4.64';
UPDATE `version_notes` SET `title` = 'Arama Kutusu İyileştirmesi', `description` = '- Tablo aramalarına arama simgesi ve tek tıkla temizleme seçeneği eklendi.' WHERE `version_tag` = 'v2.4.65';
UPDATE `version_notes` SET `title` = 'Hidrostatik Rapor Düzenleme Düzeltmesi', `description` = '- Hidrostatik test raporunun düzenleme ekranında açılmasını engelleyen hata giderildi.' WHERE `version_tag` = 'v2.4.66';
UPDATE `version_notes` SET `title` = 'Rapor Kayıt Bilgileri', `description` = '- Rapor listesine kayıt tarihi ve kaydı oluşturan kullanıcı bilgileri eklendi.' WHERE `version_tag` = 'v2.4.67';
UPDATE `version_notes` SET `title` = 'Rapor ve Bakım Modu İyileştirmeleri', `category` = 'improvement', `description` = '- Rapor tablosu işlemleri iyileştirildi ve bakım modu yetki kontrollü hale getirildi.' WHERE `version_tag` = 'v2.4.68';
UPDATE `version_notes` SET `title` = 'Bakım Sayfası Yenilemesi', `description` = '- Bakım sayfası mobil ve koyu tema uyumlu, daha anlaşılır bir görünüme kavuştu.' WHERE `version_tag` = 'v2.4.69';
UPDATE `version_notes` SET `title` = 'Bakım Ekranı Görsel Güncellemesi', `description` = '- Bakım ekranı yangın güvenliği temasına uygun görsellerle güncellendi.' WHERE `version_tag` = 'v2.4.70';
UPDATE `version_notes` SET `title` = 'Bakım Sayfası Yazı Tipi', `description` = '- Bakım sayfasının yazı tipi ve okunabilirliği iyileştirildi.' WHERE `version_tag` = 'v2.4.71';
UPDATE `version_notes` SET `title` = 'Belge Firma Başlıkları', `description` = '- Rapor ve tekliflerdeki firma başlıkları dinamik ve dengeli hale getirildi.' WHERE `version_tag` = 'v2.4.72';
UPDATE `version_notes` SET `title` = 'Tema ve Üst Menü Seçenekleri', `description` = '- Üst menü ve sol menü renkleri bağımsız seçilebilir hale getirildi; yeni tema ve yazı tipleri eklendi.' WHERE `version_tag` = 'v2.4.73';
UPDATE `version_notes` SET `title` = 'Görev Yönetimi Yenilemesi', `description` = '- Görev sayfaları, özet kartları ve işlem akışları daha sade ve tutarlı hale getirildi.' WHERE `version_tag` = 'v2.4.74';
UPDATE `version_notes` SET `title` = 'Görev Düzenleme', `description` = '- Yetkili kullanıcılar için görev düzenleme ve güvenli güncelleme akışı eklendi.' WHERE `version_tag` = 'v2.4.75';
UPDATE `version_notes` SET `title` = 'E-Posta Şablonları ve Gönderim Kolaylıkları', `description` = '- Kişisel e-posta şablonları, akıllı alıcı doldurma ve editör kullanım iyileştirmeleri eklendi.' WHERE `version_tag` = 'v2.4.76';
UPDATE `version_notes` SET `title` = 'Yönetim Formlarında Seçim Alanları', `description` = '- Yönetim sayfalarındaki seçim alanlarının arama ve kullanım deneyimi iyileştirildi.' WHERE `version_tag` = 'v2.4.77';
UPDATE `version_notes` SET `title` = 'Not Görünürlüğü ve Hızlı İşlemler', `description` = '- Notlara özel görünürlük seçenekleri ve hızlı işlem araçları eklendi.' WHERE `version_tag` = 'v2.4.78';
UPDATE `version_notes` SET `title` = 'Ekip Üyesi Bildirimleri', `description` = '- Ekip üyesi kayıt ve güncelleme bildirimleri düzeltildi.' WHERE `version_tag` = 'v2.4.80';
UPDATE `version_notes` SET `title` = 'Görev Tabloları Boş Durumu', `description` = '- Görev tablolarındaki boş sonuç görünümü temayla uyumlu hale getirildi.' WHERE `version_tag` = 'v2.4.81';
UPDATE `version_notes` SET `title` = 'Ürün Listesi Yetki Düzeltmesi', `description` = '- Ürün listesinin yetkili kullanıcılar için yüklenmesini engelleyen hata giderildi.' WHERE `version_tag` = 'v2.4.82';
UPDATE `version_notes` SET `title` = 'Tablo Boş Durum Tasarımı', `description` = '- Veri bulunmayan tablolar için ortak ve anlaşılır boş durum görünümü eklendi.' WHERE `version_tag` = 'v2.4.83';
UPDATE `version_notes` SET `title` = 'Bildirim Pencerelerinde Tema Uyumu', `description` = '- Bildirim pencereleri seçili tema yazı tipiyle uyumlu hale getirildi.' WHERE `version_tag` = 'v2.4.84';
UPDATE `version_notes` SET `title` = 'Profil ve Kullanıcı Aktiviteleri', `description` = '- Profil sayfası sekmeli yapıya geçirildi ve kullanıcı aktiviteleri erişilebilir hale getirildi.' WHERE `version_tag` = 'v2.4.85';
UPDATE `version_notes` SET `title` = 'Görev Sahipliği Yetkileri', `description` = '- Görev düzenleme ve silme işlemleri görev sahibi ve yetkili kullanıcılarla sınırlandırıldı.' WHERE `version_tag` = 'v2.4.86';
UPDATE `version_notes` SET `title` = 'Keşif Formunda Firma Arama', `description` = '- Keşif formundaki firma seçimi aranabilir ve daha hızlı kullanılabilir hale getirildi.' WHERE `version_tag` = 'v2.4.87';
UPDATE `version_notes` SET `title` = 'Global Arama Kayıt Optimizasyonu', `description` = '- Global arama daha az ve daha anlamlı aktivite kaydı oluşturacak şekilde düzenlendi.' WHERE `version_tag` = 'v2.4.88';
UPDATE `version_notes` SET `title` = 'Rapor Formları Tema Yenilemesi', `description` = '- YAS, OYS ve AAS rapor formları ortak tema, gelişmiş seçim alanları ve koyu mod desteğiyle yenilendi.' WHERE `version_tag` = 'v2.4.89';
UPDATE `version_notes` SET `title` = 'Yazı Kalınlığı Ayarı', `description` = '- Tema özelleştiriciye kullanıcı tercihini hatırlayan yazı kalınlığı seçimi eklendi.' WHERE `version_tag` = 'v2.4.90';
