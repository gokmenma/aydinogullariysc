-- Migration: Add Version Note v2.4.9
-- Description: Tablo sütun filtrelerinde arama kutulu onay kutusu (checkbox) listesi arayüzü ve operatör Select2 optimizasyonu

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Tablo Sütun Filtrelerinde Çoklu Seçim ve Arama Optimizasyonu',
    'v2.4.9',
    'improvement',
    '- Tablo sütun filtrelerinde (Durum, Bölge, Sözleşme, Oluşturan vb.) arama kutulu, adet rozetli ve ergonomik onay kutusu (checkbox) listesi arayüzü restore edildi.
- "Tümünü Seç" ve "Temizle" hızlı seçim aksiyonları anlık filtre araması ile senkronize edildi.
- Filtre popoverlarındaki kural operatörlerinde (İçerir, Eşittir vb.) Select2 entegrasyonu kararlı hale getirildi.
- Servis listesi DataTable filtrelerinde servis numarası arama optimizasyonu sağlandı.',
    'Antigravity AI',
    '2026-09-21 12:05:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `title` = 'Tablo Sütun Filtrelerinde Çoklu Seçim ve Arama Optimizasyonu'
);
