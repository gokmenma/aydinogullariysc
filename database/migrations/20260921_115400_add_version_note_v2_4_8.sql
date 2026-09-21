-- Migration: Add Version Note v2.4.8
-- Description: Tablo filtre popover kural operatörleri için Select2 tekli seçim desteği eklendi

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Filtre Kural Operatörlerinde Select2 Desteği',
    'v2.4.8',
    'improvement',
    '- Tablo başlık filtre popoverlarındaki arama kriter/operatör seçim kutusu (İçerir, Eşittir, İle Başlar vb.) Select2 bileşeni ile zenginleştirildi.
- Popover açılışında ve dinamik kural ekleme işlemlerinde Select2 otomatik olarak bağlandı.
- Operatör değiştiğinde (Boş / Dolu durumlarında metin kutusunun gizlenmesi gibi) anlık tetikleme korundu.',
    'Antigravity AI',
    '2026-09-21 11:54:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `title` = 'Filtre Kural Operatörlerinde Select2 Desteği'
);
