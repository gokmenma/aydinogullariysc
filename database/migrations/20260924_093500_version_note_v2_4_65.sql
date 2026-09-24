-- Migration: 20260924_093500_version_note_v2_4_65.sql
-- Sürüm Notu: Tablo Arama Kutusuna Arama İkonu ve Veri Temizleme (X) Butonu Entegrasyonu

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.65',
    'Tablo Arama Kutusuna İkon ve Temizleme (X) Butonu',
    'improvement',
    '- Tablo arama kutularına sol tarafta modern büyüteç arama ikonu eklendi.\n- Arama kutusuna metin girildiğinde sağ tarafta otomatik beliren "Temizle (X)" butonu entegre edildi.\n- Temizle butonuna tıklandığında metin sıfırlanıp arama otomatik olarak yenilenir ve odak arama kutusunda kalır.\n- Dark mode ve mobil uyumluluk tam olarak sağlandı.',
    'Antigravity AI',
    '2026-09-24 09:35:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.65'
);
