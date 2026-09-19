<?php
require_once "vendor/autoload.php"; // Composer autoload dosyasını ekleyin


// PHP kodunuzu buraya ekleyebilirsiniz.
// Form gönderildiğinde e-postayı alıp veritabanında kontrol etme,
// bir token oluşturma ve e-posta gönderme işlemleri burada yapılır.
// Başarılı olursa, kullanıcıyı 'forgot-password-success.php' sayfasına yönlendirin.

$message = '';
if ($_POST) {
    $email = $_POST['email'] ?? '';
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // --- BURASI SİZİN PHP KODUNUZ İÇİN ---
        // 1. E-postanın veritabanında var olup olmadığını kontrol et.
        // 2. Güvenli bir sıfırlama token'ı oluştur (örn: random_bytes).
        // 3. Token'ı ve son kullanma tarihini veritabanında kullanıcıya bağla.
        // 4. Kullanıcıya sıfırlama linki (örn: reset-password.php?token=TOKEN_DEGERI) içeren bir e-posta gönder.
        // 5. Her şey başarılıysa, onay sayfasına yönlendir.
        
        // Örnek yönlendirme:
        header('Location: forgot-password-success.php');
        exit;

    } else {
        $message = '<div class="alert error">Lütfen geçerli bir e-posta adresi girin.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum | Proje Adı</title>
    
    <!-- Gerekli Fontlar ve İkonlar -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    
    <!-- Modern Login CSS dosyasını kullanıyoruz, çünkü stil aynı -->
    <link rel="stylesheet" href="vendors/styles/login.css">

</head>
<body>

    <div class="login-container">
        <!-- Sol Taraf: Markalaşma ve Görsel Alan (Aynı kalıyor) -->
        <div class="login-branding">
            <div class="branding-content">
                <h1>Bir Adım Uzağınızdayız</h1>
                <p>Şifrenizi sıfırlamak ve hesabınıza tekrar erişmek için adımları takip edin.</p>
            </div>
        </div>

        <!-- Sağ Taraf: Şifre Sıfırlama Formu -->
        <div class="login-form-area">
            <div class="form-wrapper">
                <h2>Şifremi Unuttum</h2>
                <p class="subtitle">Hesabınıza kayıtlı e-posta adresini girin, size bir sıfırlama bağlantısı göndereceğiz.</p>

                <?php echo $message; // Hata mesajlarını göster ?>

                <form action="forgot-password.php" method="POST">
                    <div class="form-group">
                        <label for="email">E-posta Adresi</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-envelope icon"></i>
                            <input type="email" id="email" name="email" placeholder="ornek@mail.com" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Sıfırlama Bağlantısı Gönder</button>
                </form>

                <div class="footer-text">
                    Şifrenizi hatırladınız mı? <a href="login.php">Giriş Yap</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>