<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bağlantı Gönderildi | AYDINOĞULLARI</title>
    
    <!-- Google Fonts -->
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
    
    <style>
        .success-icon {
            font-size: 3.5rem;
            color: #22c55e;
            margin-bottom: 18px;
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: inline-block;
        }

        @keyframes popIn {
            0% { transform: scale(0.4); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

    <div class="auth-shell">
        <div class="auth-card" style="text-align: center;">
            <a href="login.php" class="brand">
                <img src="<?php echo set('logo') ?: 'src/images/logo.svg'; ?>" alt="AYDINOĞULLARI Logo" class="main-logo">
            </a>
            
            <div class="success-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            
            <h2 class="auth-title">E-postanızı Kontrol Edin</h2>
            <p class="subtitle" style="margin-bottom: 24px;">
                Şifre sıfırlama talimatlarını içeren bir bağlantı e-posta adresinize gönderildi.
                <br><br>
                <small style="color: var(--light-text-color);">E-postayı göremiyorsanız lütfen spam (istenmeyen) klasörünüzü de kontrol edin.</small>
            </p>

            <a href="login.php" class="btn-login" style="display: block; text-decoration: none; text-align: center;">Giriş Sayfasına Dön</a>
        </div>
    </div>

</body>
</html>