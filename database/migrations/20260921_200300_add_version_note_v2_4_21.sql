-- Sürüm Notu: Sistem Aktiviteleri Detay Modalı Event Delegation ve Veri Ayrıştırma Düzeltmesi (v2.4.21)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.21',
    'Sistem Aktiviteleri Detay Modalı Event Delegation ve Veri Ayrıştırma Düzeltmesi',
    '- ServerSide DataTables AJAX ile yüklenen log satırlarındaki Detay butonlarının tıklama olaylarını yakalaması için JavaScript event delegation (document on click) yapısına geçildi.\n- Detay modalının hem Bootstrap Modal sınıfı hem de jQuery modal arayüzü ile uyumlu ve güvenli şekilde tetiklenmesi sağlandı.\n- data-json ve context verilerinin güvenli ayrıştırılması (JSON parse error guard) sağlanarak boş veya beklenmeyen veri formatlarında modalın açılmasını engelleyen hatalar giderildi.',
    'bugfix',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.21'
);

-- Sürüm Notu: Notlar Sayfası Detay Modalında Zengin HTML ve Liste Çıktısı Desteği (v2.4.22)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.22',
    'Notlar Sayfası Detay Modalında Zengin HTML ve Liste Çıktısı Desteği',
    '- Notlar sayfasında (all-notes) not detay modalı açıldığında içeriklerin ham HTML etiketleri yerine biçimlendirilmiş zengin metin ve HTML çıktısı olarak görüntülenmesi sağlandı.\n- Not detay görüntüleme kutusuna (note-desc-box) liste (ul, ol, li), başlık, alıntı (blockquote), tablo, kalın metin ve link stilleri eklenerek tipografi ve okunabilirlik iyileştirildi.\n- Düz metin ve yeni satır içeren eski kayıtlar için otomatik satır sonu desteği korunarak geriye dönük uyumluluk ve Dark Mode desteği sağlandı.',
    'improvement',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.22'
);

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

-- Yapılacaklar Sayfası SweetAlert2 Silme Onayı ve Sağ Tık Menüsü Sürüm Notu Migration
INSERT INTO version_notes (title, version_tag, category, description, author, created_at, updated_at)
SELECT 
    'Yapılacaklar Sayfası SweetAlert2 Silme Onayı ve Sağ Tık Menüsü',
    'v2.4.25',
    'improvement',
    '- Yapılacaklar (tasks) sayfasındaki görev silme işlemlerinde tarayıcının yerel confirm popupı yerine modern, şık ve animasyonlu SweetAlert2 onay modalı entegre edildi.\n- Görev listesi tablosundaki satırlara sağ tıklandığında açılan dinamik Context Menu (Sağ Tık Menüsü) geliştirildi.\n- Sağ tık menüsü üzerinden görevi inceleme, durumu anlık güncelleme (Yapıldı, Ertele, Yapılmadı), görevi düzenleme ve yetkiye bağlı güvenli silme aksiyonları eklendi.\n- Sağ tık menüsü için açık ve koyu tema (dark mode) tam uyumluluğu, ekran sınırlandırmaları ve ESC/dış tıklama ile kapanma etkileşimleri sağlandı.',
    'Antigravity AI',
    '2026-09-21 20:22:00',
    '2026-09-21 20:22:00'
WHERE NOT EXISTS (
    SELECT 1 FROM version_notes WHERE version_tag = 'v2.4.25'
);
