-- Migration: 20260924_082700_version_note_v2_4_63.sql
-- Sürüm Notu: Keşif Listesinde Yapılacak İş Alanından Düzenleme Modalı Açma

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.63',
    'Keşif Listesi Yapılacak İş Alanından Düzenleme Modalı Açma',
    'feature',
    '- Keşif listesi tablosundaki `Yapılacak İş` hücresi yetkili kullanıcılar için tıklanabilir hale getirildi.\n- Hücreye tıklandığında doğrudan ilgili kaydın keşif düzenleme modalının (`kesifModal`) açılması sağlandı.\n- Hover durumunda renk değişimi ve alt çizgi ile kullanıcı deneyimi zenginleştirildi.',
    'Antigravity AI',
    '2026-09-24 08:27:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.63'
);
