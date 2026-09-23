-- Sürüm Notu: v2.4.56
INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`, `updated_at`)
SELECT 
    'Koyu Renkler ve Sidebar İçin Vektörel (SVG) Logo Entegrasyonu',
    'v2.4.56',
    'improvement',
    '- Koyu renk temalar, karanlık mod (dark mode) ve sol menü (sidebar) için logonun beyaz alt yazılı yüksek kontrastlı SVG versiyonu sıfırdan vektörel olarak tasarlandı.\n- logo.svg, logo-dark.svg, logo-white.svg (monokrom beyaz) ve logo-light.svg formatları oluşturuldu.\n- Panel ayarlarındaki varsayılan logo yolu pikselsiz ve kristal netliğinde SVG formatına güncellendi.\n- Giriş ve şifre sıfırlama sayfalarındaki logolar dinamik ayar yapısıyla senkronize edildi.',
    'Antigravity AI',
    '2026-09-23 21:55:00',
    '2026-09-23 21:55:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.56'
);
