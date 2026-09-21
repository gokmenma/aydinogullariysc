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
