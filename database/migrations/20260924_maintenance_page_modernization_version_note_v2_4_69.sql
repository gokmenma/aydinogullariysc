-- Sürüm Notu: Bakım Sayfası Modernizasyonu

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT
    'v2.4.69',
    'Bakım Sayfası Modernizasyonu',
    'improvement',
    '- Bakım sayfası modern, mobil uyumlu ve koyu tema destekli bir tasarımla yenilendi.\n- Normal giriş ekranının bakım sırasında erişilebilir kalması sağlandı.\n- Bakım yetkisi kontrolü giriş sonrasına taşındı; yetkisiz kullanıcılar bakım sayfasına, yetkili kullanıcılar sisteme yönlendirildi.\n- Bakım sayfasına farklı hesapla giriş yapma bağlantısı eklendi.',
    'Antigravity AI',
    '2026-09-24 12:20:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.69'
);
