-- Sürüm Notu: Yetki Bazlı Bakım Modu

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT
    'v2.4.68',
    'Yetki Bazlı Bakım Modu',
    'feature',
    '- Panel ayarlarına bakım modunu açma ve kapatma kontrolü eklendi.\n- Bakım sırasında sayfa ve API erişimleri merkezi olarak engellendi.\n- Bakım Modunda Sisteme Erişim yetkisine sahip kullanıcıların çalışmaya devam etmesi sağlandı.\n- Yetkili yönetici giriş alanı içeren mobil uyumlu bakım sayfası eklendi.',
    'Antigravity AI',
    '2026-09-24 12:00:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.68'
);
