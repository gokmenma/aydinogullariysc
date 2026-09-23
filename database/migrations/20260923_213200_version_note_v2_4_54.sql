-- Sürüm Notu: v2.4.54
INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`, `updated_at`)
SELECT 
    'Müşteri Yönetimi Özet Kartları Gizle/Göster Butonu ve LocalStorage Entegrasyonu',
    'v2.4.54',
    'feature',
    '- Müşteri düzenleme sayfasında (customers/manage) "Firma Bilgileri" kart başlığının sağ tarafına özet bilgi kartlarını daraltma/genişletme (yukarı/aşağı ok) butonu eklendi.\n- Kartların açık/kapalı olma durumu tarayıcının LocalStorage belleğinde saklanarak kullanıcının tercihi kalıcı hale getirildi.\n- Sayfa açılışında görsel titremeyi (flicker) önlemek amacıyla sayfa render edilmeden önce çalışan erken JavaScript ve CSS kuralı entegre edildi.\n- Butona basıldığında akıcı slideUp/slideDown animasyonu ile ikon ve tooltip durumları dinamik olarak güncellenmektedir.',
    'Antigravity AI',
    '2026-09-23 21:32:00',
    '2026-09-23 21:32:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.54'
);
