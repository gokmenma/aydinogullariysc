-- Migration: SweetAlert2 Tema Yazı Tipi Uyumu
-- Version: v2.4.84
-- Date: 2026-09-25 09:44:34

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT
    'SweetAlert2 Tema Yazı Tipi Uyumu',
    'v2.4.84',
    'improvement',
    '- SweetAlert2 bildirim pencerelerinin başlık, içerik, form alanı ve işlem düğmeleri seçili tema yazı tipini kullanacak şekilde güncellendi.',
    'Antigravity AI',
    '2026-09-25 09:44:34'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.84'
);
