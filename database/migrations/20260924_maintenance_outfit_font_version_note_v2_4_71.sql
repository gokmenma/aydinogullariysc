-- Sürüm Notu: Bakım Sayfası Outfit Yazı Tipi

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT
    'v2.4.71',
    'Bakım Sayfası Outfit Yazı Tipi',
    'improvement',
    '- Bakım sayfasının tüm metinlerinde Outfit yazı tipi kullanılmaya başlandı.\n- Font yüklenemediğinde sistem yazı tiplerine güvenli geri dönüş tanımlandı.',
    'Antigravity AI',
    '2026-09-24 13:00:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.71'
);
