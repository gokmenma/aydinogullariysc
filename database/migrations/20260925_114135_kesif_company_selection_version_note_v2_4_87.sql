-- Sürüm Notu: Keşif Formunda Aranabilir Firma Seçimi
-- Sürüm: v2.4.87
-- Tarih: 2026-09-25 11:41:35

ALTER TABLE `customers`
    ADD COLUMN IF NOT EXISTS `location` VARCHAR(500) NULL AFTER `address`;

INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT
    'v2.4.87',
    'Keşif Formunda Aranabilir Firma Seçimi',
    '- Yeni keşif ekleme ve keşif düzenleme formlarına aktif firma kayıtları arasında arama ve seçim yapılabilen firma alanı eklendi.\n- Firma seçimi tarih alanının altına tam genişlikte taşındı ve diğer form alanlarıyla eşit yüksekliğe getirildi.\n- Firma kayıtlarına isteğe bağlı Keşif / Saha Konumu alanı eklendi; firma seçildiğinde kayıtlı konumun keşif formuna otomatik aktarılması sağlandı.\n- Kayıtlı olmayan müşteriler için elle firma adı girme desteği korundu.\n- Eski veya pasif firmaya bağlı keşifler düzenlenirken mevcut firma adının korunması ve firma adlarındaki baş/son boşlukların temizlenmesi sağlandı.',
    'improvement',
    'Antigravity AI',
    '2026-09-25 11:41:35'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.87'
);

UPDATE `version_notes`
SET `title` = 'Keşif Formunda Aranabilir Firma Seçimi',
    `description` = '- Yeni keşif ekleme ve keşif düzenleme formlarına aktif firma kayıtları arasında arama ve seçim yapılabilen firma alanı eklendi.\n- Firma seçimi tarih alanının altına tam genişlikte taşındı ve diğer form alanlarıyla eşit yüksekliğe getirildi.\n- Firma kayıtlarına isteğe bağlı Keşif / Saha Konumu alanı eklendi; firma seçildiğinde kayıtlı konumun keşif formuna otomatik aktarılması sağlandı.\n- Kayıtlı olmayan müşteriler için elle firma adı girme desteği korundu.\n- Eski veya pasif firmaya bağlı keşifler düzenlenirken mevcut firma adının korunması ve firma adlarındaki baş/son boşlukların temizlenmesi sağlandı.',
    `category` = 'improvement',
    `author` = 'Antigravity AI'
WHERE `version_tag` = 'v2.4.87';
