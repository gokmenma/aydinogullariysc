-- Sürüm Notu: Not Silme Onay Modalında Buton Sıralaması ve Boşluk Düzenlemesi (v2.4.24)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.24',
    'Not Silme Onay Modalında Buton Sıralaması ve Boşluk Düzenlemesi',
    '- Notlar sayfasındaki SweetAlert2 silme onay modalında "Vazgeç" ve "Evet, Sil" butonlarının yerleşim sıralaması kullanıcı geri bildirimine uygun olarak yer değiştirildi.\n- Buton aksiyon kapsayıcısına merkezi hizalama ve dengeli boşluk (gap) yapısı kazandırıldı.',
    'improvement',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.24'
);
