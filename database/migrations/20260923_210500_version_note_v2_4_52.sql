-- Sürüm Notu: v2.4.52
INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`, `updated_at`)
SELECT 
    'Fiyat Talebi Detay Modalı Premium Tema Yenilemesi',
    'v2.4.52',
    'improvement',
    '- Satın alma fiyat talepleri listesinde (purchases/price-request-list) yer alan Fiyat Talebi Detay Modalı modern ve premium-theme standartlarına uygun olarak baştan tasarlandı.\n- Üst hero başlık alanı ferahlatıldı; talep no, kayıt/termin tarihleri, oluşturan personel bilgileri modern hap rozetlere dönüştürüldü.\n- Firma ve yetkili iletişim bilgileri ile durum rozeti sağ üst alanda dengeli bir kart yapısına kavuşturuldu.\n- Ara toplam, genel toplam ve toplam kalem/miktar bilgilerini gösteren 3\'lü renk vurgulu KPI istatistik kartları eklendi.\n- Talep edilen kalemler tablosu sadeleştirildi; stok kodu, miktar rozetleri, birim/toplam fiyatlar ve ek dosya/görsel butonları şık ve kompakt hale getirildi.\n- Modal footer alanına doğrudan "Düzenle" butonu eklendi, yazdırma ve PDF indirme aksiyonları optimize edildi.',
    'Antigravity AI',
    '2026-09-23 21:05:00',
    '2026-09-23 21:05:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.52'
);
