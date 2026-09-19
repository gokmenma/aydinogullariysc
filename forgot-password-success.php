<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bağlantı Gönderildi | Proje Adı</title>
    
    <!-- Gerekli Fontlar ve İkonlar -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    
    <!-- Modern Login CSS -->
    <link rel="stylesheet" href="vendors/styles/login.css">
    
    <!-- Bu sayfa için ek stil -->
    <style>
        .success-icon {
            font-size: 4rem;
            color: #28a745; /* Yeşil renk */
            margin-bottom: 20px;
            animation: popIn 0.5s ease-out;
        }

        @keyframes popIn {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <!-- Sol Taraf: Markalaşma ve Görsel Alan -->
        <div class="login-branding">
            <div class="branding-content">
                <img src="assets/images/logo/logo-white.svg" alt="Proje Logosu" class="logo">
                <h1>İşlem Tamam!</h1>
                <p>Hesabınıza yeniden erişmek üzeresiniz.</p>
            </div>
        </div>

        <!-- Sağ Taraf: Onay Mesajı -->
        <div class="login-form-area">
            <div class="form-wrapper" style="text-align: center;">
                <i class="fa-solid fa-check-circle success-icon"></i>
                <h2>E-postanızı Kontrol Edin</h2>
                <p class="subtitle" style="margin-bottom: 30px;">
                    Şifre sıfırlama talimatlarını içeren bir bağlantı e-posta adresinize gönderildi.
                    <br><br>
                    E-postayı göremiyorsanız, lütfen spam (istenmeyen) klasörünüzü de kontrol edin.
                </p>

                <div class="footer-text">
                    <a href="login.php">Giriş Sayfasına Dön</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>