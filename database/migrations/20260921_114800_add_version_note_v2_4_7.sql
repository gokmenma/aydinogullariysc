-- Migration: Add Version Note v2.4.7
-- Description: Sütun filtreleme popover menülerinde Select2 çoklu seçim (multiple select) desteği entegre edildi

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Sütun Filtrelerinde Select2 Çoklu Seçim Entegrasyonu',
    'v2.4.7',
    'improvement',
    '- Tablo başlık filtreleme popoverlarında seçim tipi sütunlar (Bölge, Durum, Sözleşme, Muhasebe vb.) için Select2 çoklu seçim (multiple select) bileşeni entegre edildi.
- Popover içinde Select2 arama, etiketleme ve hızlı seçim işlevleri sağlandı.
- \"Tümünü Seç\" ve \"Temizle\" hızlı butonları ile seçili öğe sayacı Select2 durumuyla senkronize edildi.
- Açık ve koyu (dark mode) temalara tam uyumlu Select2 popover CSS stilleri eklendi.',
    'Antigravity AI',
    '2026-09-21 11:48:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `title` = 'Sütun Filtrelerinde Select2 Çoklu Seçim Entegrasyonu'
);
