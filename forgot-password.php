<?php
require_once "bootstrap.php";

$message = '';
if ($_POST) {
    $email = $_POST['email'] ?? '';
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
    <title>Şifremi Unuttum | AYDINOĞULLARI</title>
    
    <!-- Google Fonts (Geist, Inter, Plus Jakarta Sans, Poppins, Outfit, Roboto, Montserrat) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tema Yükleyici (Flicker Önleme) -->
    <script>
        (function () {
            try {
                var savedPreset = localStorage.getItem('app_theme_preset') || 'kode';
                document.documentElement.setAttribute('data-theme-preset', savedPreset);

                var themePresetFonts = {
                    'kode': 'inter',
                    'ersan-gold': 'poppins',
                    'zumrut': 'plus-jakarta',
                    'kraliyet-moru': 'outfit',
                    'rose': 'poppins',
                    'sade-beyaz': 'inter',
                    'koyu-gece': 'geist'
                };
                var savedFont = localStorage.getItem('app_theme_font') || themePresetFonts[savedPreset] || 'inter';
                document.documentElement.setAttribute('data-theme-font', savedFont);

                var theme = localStorage.getItem('theme');
                if (theme === 'dark' || savedPreset === 'koyu-gece') {
                    document.documentElement.classList.add('dark-mode');
                } else {
                    document.documentElement.classList.remove('dark-mode');
                }

                document.addEventListener('DOMContentLoaded', function () {
                    if (document.body) {
                        document.body.setAttribute('data-theme-preset', savedPreset);
                        document.body.setAttribute('data-theme-font', savedFont);
                        if (theme === 'dark' || savedPreset === 'koyu-gece') {
                            document.body.classList.add('dark-mode');
                        } else {
                            document.body.classList.remove('dark-mode');
                        }
                    }
                });
            } catch (e) {}
        })();
    </script>

    <!-- Modern Login CSS -->
    <link rel="stylesheet" href="vendors/styles/login.css?v=<?php echo filemtime("vendors/styles/login.css"); ?>">
</head>
<body>

    <div class="auth-shell">
        <div class="auth-card">
            <a href="login.php" class="brand">
                <img src="src/images/logo.png" alt="AYDINOĞULLARI Logo" class="main-logo">
            </a>
            <h2 class="auth-title">Şifremi Unuttum</h2>
            <p class="subtitle">Hesabınıza kayıtlı e-posta adresini girin, size bir sıfırlama bağlantısı gönderelim.</p>

            <?php echo $message; ?>

            <form action="forgot-password.php" method="POST">
                <div class="floating-group">
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope icon"></i>
                        <input type="email" id="email" name="email" class="input-element" placeholder=" " required autocomplete="email">
                        <label class="floating-label" for="email">E-posta Adresi</label>
                    </div>
                </div>

                <button type="submit" class="btn-login">Sıfırlama Bağlantısı Gönder</button>
            </form>

            <div class="footer-text">
                Şifrenizi hatırladınız mı? <a href="login.php">Giriş Yap</a>
            </div>
        </div>
    </div>

<script>
var flInputs = document.querySelectorAll('.floating-group .input-element');
flInputs.forEach(function(input){
  function update(){
    var group = input.closest('.floating-group');
    if(!group) return;
    if(input.value && input.value.trim() !== '') group.classList.add('filled');
    else group.classList.remove('filled');
  }
  ['input','change','blur','focus'].forEach(function(ev){ input.addEventListener(ev, update); });
  setTimeout(update, 0);
});
</script>
</body>
</html>