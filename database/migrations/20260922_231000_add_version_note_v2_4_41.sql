-- Sürüm Notu: v2.4.41 - Giriş (Login) Sayfasının Dinamik Tema ve Tipografi Sistemi Entegrasyonu
INSERT INTO version_notes (version_tag, title, category, description, author, created_at)
SELECT 
    'v2.4.41',
    'Giriş Sayfasının Dinamik Tema ve Tipografi Sistemi Entegrasyonu',
    'feature',
    '- Giriş (login.php), şifremi unuttum (forgot-password.php) ve bilgilendirme sayfaları merkezi tema motoruna bağlandı.
- Kullanıcının seçtiği aktif hazır tema (Kraliyet Moru, Kode, Zümrüt, Rose, Ersan Gold, Sade Beyaz, Koyu Gece) ve karanlık mod ayarları login ekranında otomatik olarak yüklenip kart çerçeve ışıkları, buton gradyanları, başlık metinleri ve arka plan parıltılarına uygulandı.
- Login ekranı sağ üst köşesine hızlı tema ve karanlık mod seçici hap (pill) butonları eklendi; oturum açmadan önce de anlık tema değişimi ve localStorage eşitlemesi sağlandı.
- Başarılı oturum açma sonrasında session fixation zafiyetine karşı session_regenerate_id(true) güvenliği pekiştirildi.',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM version_notes WHERE version_tag = 'v2.4.41'
);
