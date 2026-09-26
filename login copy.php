<?php
header('Location: login.php', true, 301);
exit;
require_once 'configs/config.php';
?>
<?php require_once 'configs/functions.php' ?>

<?php
session_start();
if ($_POST) {
	$up = $_POST['email'];
	$pp = md5(md5(md5($_POST['passwordp'])));

	if (!$up || !$pp) {
		header('Location: index.php?error=102');
		exit;  // Yönlendirmeden sonra scriptin çalışmasını durdurun
	} else {
		$ucont = $ac->prepare('SELECT * FROM users WHERE email = ? AND password = ? AND statu = ?');
		$ucont->execute(array($up, $pp, 1));
		$conts = $ucont->fetch();

		if ($conts) {
			$_SESSION['login'] = true;
			$_SESSION['perm'] = $conts['permission'];
			$_SESSION['lid'] = $conts['id'];

			// returnUrl parametresini kontrol edin ve varsayılan değeri ayarlayın
			$redirectUri = isset($_GET['returnUrl']) && !empty($_GET['returnUrl']) ? $_GET['returnUrl'] : 'index.php?p=home';
	
			header('Location: ' . $redirectUri);
			exit;  // Yönlendirmeden sonra scriptin çalışmasını durdurun
		} else {
			header('Location: index.php?error=103&HATA');
			exit;  // Yönlendirmeden sonra scriptin çalışmasını durdurun
		}
	}
} else {
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Girişi | Proje Adı</title>
    
    <!-- Google Fonts: Modern ve okunaklı bir font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome İkonları -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    
    <!-- Yeni CSS dosyamız -->
    <link rel="stylesheet" href="assets/css/modern-login.css">

	<style>
		/* Genel Stil ve Değişkenler */
:root {
    --primary-color: #007bff; /* Ana Mavi Renk */
    --primary-hover: #0056b3;
    --secondary-color: #6c757d;
    --background-color: #f8f9fa;
    --form-bg-color: #ffffff;
    --text-color: #212529;
    --light-text-color: #6c757d;
    --border-color: #dee2e6;
    --error-color: #dc3545;
    --error-bg: #f8d7da;
    --font-family: 'Poppins', sans-serif;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--font-family);
    background-color: var(--background-color);
    color: var(--text-color);
    line-height: 1.6;
}

.login-container {
    display: flex;
    min-height: 100vh;
    width: 100%;
}

/* Sol Taraf: Markalaşma Alanı */
.login-branding {
    width: 45%;
    background: linear-gradient(135deg, var(--primary-color), #0056b3);
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
    position: relative;
    overflow: hidden;
}

.login-branding::before {
    content: '';
    position: absolute;
    top: -50px;
    left: -50px;
    width: 200px;
    height: 200px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.login-branding::after {
    content: '';
    position: absolute;
    bottom: -80px;
    right: -80px;
    width: 300px;
    height: 300px;
    background-color: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
}

.branding-content {
    text-align: center;
    z-index: 1;
}

.branding-content .logo {
    max-width: 150px;
    margin-bottom: 30px;
}

.branding-content h1 {
    font-size: 2.2rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.branding-content p {
    font-size: 1.1rem;
    opacity: 0.9;
}


/* Sağ Taraf: Form Alanı */
.login-form-area {
    width: 55%;
    background-color: var(--form-bg-color);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
}

.form-wrapper {
    width: 100%;
    max-width: 400px;
}

.form-wrapper h2 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.form-wrapper .subtitle {
    color: var(--light-text-color);
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 8px;
}

.input-wrapper {
    position: relative;
}

.input-wrapper .icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--light-text-color);
    transition: color 0.3s ease;
}

.input-wrapper input {
    width: 100%;
    padding: 12px 15px 12px 45px; /* İkon için solda boşluk */
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.input-wrapper input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
}

.input-wrapper input:focus + .icon, .input-wrapper:focus-within .icon {
    color: var(--primary-color);
}


.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    font-size: 0.9rem;
}

.remember-me {
    display: flex;
    align-items: center;
}

.remember-me input[type="checkbox"] {
    margin-right: 8px;
}

.forgot-password {
    color: var(--primary-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.forgot-password:hover {
    color: var(--primary-hover);
}

.btn-login {
    width: 100%;
    padding: 14px;
    background-color: var(--primary-color);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.btn-login:hover {
    background-color: var(--primary-hover);
    transform: translateY(-2px);
}

.footer-text {
    text-align: center;
    margin-top: 30px;
    color: var(--light-text-color);
}

.footer-text a {
    color: var(--primary-color);
    font-weight: 500;
    text-decoration: none;
}

/* Hata Mesajı Stili */
.alert.error {
    background-color: var(--error-bg);
    color: var(--error-color);
    border: 1px solid var(--error-color);
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}


/* Mobil Uyum (Responsive) */
@media (max-width: 768px) {
    .login-container {
        flex-direction: column;
    }

    .login-branding {
        width: 100%;
        min-height: 250px;
        text-align: center;
    }
    
    .login-branding h1 {
        font-size: 1.8rem;
    }

    .login-form-area {
        width: 100%;
        padding: 30px 20px;
    }
}
	</style>
</head>
<body>

    <div class="login-container">
        <!-- Sol Taraf: Markalaşma ve Görsel Alan -->
        <div class="login-branding">
            <div class="branding-content">
                <!-- Kendi logonuzu buraya koyun -->
                <img src="assets/images/logo/logo-white.svg" alt="Proje Logosu" class="logo">
                <h1>Projenize Tekrar Hoş Geldiniz</h1>
                <p>Yönetim panelinize erişmek için lütfen giriş yapın.</p>
            </div>
        </div>

        <!-- Sağ Taraf: Giriş Formu -->
        <div class="login-form-area">
            <div class="form-wrapper">
                <h2>Giriş Yap</h2>
                <p class="subtitle">E-posta ve parolanızla devam edin.</p>

                <?php 
                // Hata mesajlarını göstermek için modern bir alan
                if (isset($_GET['error'])) {
                    $errorMessage = '';
                    switch ($_GET['error']) {
                        case '102':
                            $errorMessage = 'Lütfen tüm alanları doldurun.';
                            break;
                        case '103':
                            $errorMessage = 'E-posta veya parola hatalı.';
                            break;
                        default:
                            $errorMessage = 'Bilinmeyen bir hata oluştu.';
                            break;
                    }
                    echo '<div class="alert error">' . htmlspecialchars($errorMessage) . '</div>';
                }
                ?>
                
                <form action="login.php<?php echo isset($_GET['returnUrl']) ? '?returnUrl=' . htmlspecialchars($_GET['returnUrl']) : ''; ?>" method="POST">
                    <div class="form-group">
                        <label for="email">E-posta Adresi</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-envelope icon"></i>
                            <input type="email" id="email" name="email" placeholder="ornek@mail.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="passwordp">Parola</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock icon"></i>
                            <input type="password" id="passwordp" name="passwordp" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember">
                            <label for="remember">Beni Hatırla</label>
                        </div>
                        <a href="#" class="forgot-password">Parolamı Unuttum</a>
                    </div>

                    <button type="submit" class="btn-login">Giriş Yap</button>
                </form>

                <div class="footer-text">
                    Hesabınız yok mu? <a href="#">Kayıt Ol</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
