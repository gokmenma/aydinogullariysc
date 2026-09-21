-- Sürüm Notu: TableFilter Seçimlerinde Üst Form Tarih/İşlem Kısıtlaması Çakışma Çözümü (v2.4.20)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.20',
    'TableFilter Seçimlerinde Üst Form Tarih/İşlem Kısıtlaması Çakışma Çözümü',
    '- Sistem Aktiviteleri (App/api/get-logs.php) uç noktasında TableFilter üzerinden Kullanıcı seçildiğinde, üst formdaki bugünkü tarih ve işlem türü kısıtlamalarının geçmiş dönem kayıtlarını (Cüneyt GÜLSÜN, ERSİN YILMAZ, Yıldıray ÇOBAN vb. az kayıtlı eski personelleri) gizlemesi engellendi.\n- Başlıktaki dinamik filtreler ile sayfa üstü sabit filtrelerin hiyerarşik önceliği optimize edildi.',
    'bugfix',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.20'
);
