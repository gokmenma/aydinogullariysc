-- Sürüm Notu: Not Silme İşlemlerinde SweetAlert2 Onay Modalı Entegrasyonu (v2.4.23)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.23',
    'Not Silme İşlemlerinde SweetAlert2 Onay Modalı Entegrasyonu',
    '- Notlar sayfasındaki (all-notes) not silme işlemi için tarayıcının yerel (native) confirm popupı yerine modern, şık ve kullanıcı dostu SweetAlert2 onay modalı entegre edildi.\n- DataTables sayfalama ve filtrelemelerinde silme butonunun sorunsuz çalışması için JavaScript event delegation yapısı uygulandı.\n- Not detay görüntüleme modalı içerisine de yetkiye bağlı hızlı silme butonu ve SweetAlert2 onayı dahil edildi.',
    'improvement',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.23'
);
