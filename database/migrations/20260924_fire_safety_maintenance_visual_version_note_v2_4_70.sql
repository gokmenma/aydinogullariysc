-- Sürüm Notu: Yangın Güvenliği Temalı Bakım Ekranı

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT
    'v2.4.70',
    'Yangın Güvenliği Temalı Bakım Ekranı',
    'improvement',
    '- Bakım ekranı iki bölümlü modern ve kurumsal bir düzene geçirildi.\n- Yangın güvenliği teknisyeni, söndürücü ve kontrollü alevlerden oluşan özgün illüstrasyon eklendi.\n- Teknisyen, ateş parlaması ve bakım durumu için erişilebilir CSS animasyonları eklendi.\n- Masaüstü, mobil ve koyu tema görünümleri firma renklerine uygun biçimde güncellendi.',
    'Antigravity AI',
    '2026-09-24 12:50:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.70'
);
