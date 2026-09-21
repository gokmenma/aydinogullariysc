-- Sürüm Notu: Server-Side ve Client-Side DataTables Başlık Filtrelerinde Gerçek Veritabanı Toplam Sayılarının Sağlanması
-- Tarih: 2026-09-21 12:48:00
-- Sürüm: v2.4.13

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Server-Side ve Client-Side DataTables Başlık Filtrelerinde Gerçek Veritabanı Toplam Sayılarının Sağlanması',
    'v2.4.13',
    'bugfix',
    '- Sunucu taraflı (serverSide: true) DataTables modüllerinde (Teklifler, Teklif Kalemleri, Servisler, Müşteriler vb.) sadece görüntülenen mevcut sayfa (10 kayıt) sayısının değil, tüm veritabanındaki toplam gerçek kayıt sayılarının (örn: 3.508 kayıt içerisindeki 2.890 Bekliyor, 618 Tamamlandı) hesaplanarak filtre menüsüne aktarılması sağlandı.\n- Sunucu API yanıtlarına (columnCounts) tüm veri kümesi toplamları entegre edildi.\n- Sıfır (0) olan seçenekler filtrelenerek sadece verisi olan gerçek seçenekler ve doğru adetleri listelendi.',
    'Antigravity AI',
    '2026-09-21 12:48:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.13' AND `title` = 'Server-Side ve Client-Side DataTables Başlık Filtrelerinde Gerçek Veritabanı Toplam Sayılarının Sağlanması'
);
