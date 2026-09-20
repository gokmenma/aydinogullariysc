-- SMS Gönderim Sayfası Premium Tema & Entegrasyon Kontrolü Güncellemesi
-- Migration Date: 2026-09-20

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`) 
VALUES (
    'SMS Gönderim Sayfası Premium Tema & Entegrasyon Kontrolü',
    'v2.4.5',
    'improvement',
    '- SMS Gönderim sayfası (`send-sms.php`) premium-theme standartlarına uygun olarak modernleştirildi.\n- NetGSM SMS entegrasyon ayarları kontrolü eklendi; ayarlar eksik veya pasif olduğunda açıklayıcı uyarı kartı ve Panel Ayarlarına hızlı geçiş butonu sunuldu.\n- Dinamik karakter sayacı ve SMS parça hesaplayıcı eklendi.\n- Müşteri seçimi Select2 ile modernize edildi; hızlı seçim araçları ve arama özellikleri sağlandı.\n- SMS gönderim işlemleri NetGSM servisine bağlandı ve audit_log ile loglama entegre edildi.',
    'Antigravity AI',
    '2026-09-20 22:05:00'
);
