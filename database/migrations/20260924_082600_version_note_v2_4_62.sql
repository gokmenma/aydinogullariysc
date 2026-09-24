-- Migration: 20260924_082600_version_note_v2_4_62.sql
-- Sürüm Notu: Keşif Listesinde Uzun Metinlerin Tabloda 150 Karaktere Kısaltılması

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.62',
    'Keşif Listesi Tablo İçi Uzun Metin Kısaltması (150 Karakter)',
    'improvement',
    '- Keşif listesi tablosunda `Yapılacak İş` ve `Keşif Sonu Notu` sütunlarındaki uzun metinler satır yüksekliğini aşırı uzatmaması için 150 karakter ile sınırlandırıldı ve sonuna üç nokta (...) eklendi.\n- Metinlerin tam hali `data-tooltip` ve `data-export` üzerinde korunarak fare ile üzerine gelindiğinde tooltip içinde eksiksiz okunması ve dışa aktarmada tam metin kalması sağlandı.',
    'Antigravity AI',
    '2026-09-24 08:26:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.62'
);
