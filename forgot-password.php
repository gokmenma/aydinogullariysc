<?php
require_once "bootstrap.php";

$message = '';
if ($_POST) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!\App\Helper\Security::checkCsrfToken($csrfToken)) {
        $message = '<div class="alert error">Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.</div>';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert error">Lütfen geçerli bir e-posta adresi girin.</div>';
    } else {
        $ipAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $emailHash = hash('sha256', mb_strtolower($email, 'UTF-8'));
        $limit = $ac->prepare('SELECT COUNT(*) FROM password_reset_tokens WHERE request_ip = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)');
        $limit->execute([$ipAddress]);

        if ((int) $limit->fetchColumn() < 5) {
            $userQuery = $ac->prepare('SELECT id, username, email FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) AND statu = 1 LIMIT 1');
            $userQuery->execute([$email]);
            $user = $userQuery->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                try {
                    $rawToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $rawToken);
                    $ac->beginTransaction();
                    $invalidate = $ac->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
                    $invalidate->execute([(int) $user['id']]);
                    $insert = $ac->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, request_ip, expires_at, created_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())');
                    $insert->execute([(int) $user['id'], $tokenHash, $ipAddress]);
                    $ac->commit();

                    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
                    $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
                    $resetUrl = ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                        . $basePath . '/reset-password.php?token=' . rawurlencode($rawToken);

                    $mailer = get_configured_mailer(null, 'Aydınoğulları YSC');
                    $mailer->addAddress((string) $user['email'], (string) $user['username']);
                    $mailer->Subject = 'Parola Sıfırlama Bağlantısı';
                    $safeName = htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8');
                    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
                    $mailer->Body = '<p>Merhaba ' . $safeName . ',</p>'
                        . '<p>Parolanızı yenilemek için aşağıdaki bağlantıyı kullanabilirsiniz. Bağlantı 30 dakika geçerlidir ve yalnızca bir kez kullanılabilir.</p>'
                        . '<p><a href="' . $safeUrl . '">Parolamı Yenile</a></p>'
                        . '<p>Bu talebi siz oluşturmadıysanız e-postayı dikkate almayın.</p>';
                    $mailer->AltBody = "Parolanızı yenilemek için bağlantıyı açın (30 dakika geçerli): " . $resetUrl;
                    $mailer->send();
                    \App\Helper\ApiSecurity::log($ac, 'password_reset_sent', 'forgot-password.php', [
                        'user_id' => (int) $user['id'],
                        'identity_hash' => $emailHash,
                    ]);
                } catch (Throwable $e) {
                    if ($ac->inTransaction()) {
                        $ac->rollBack();
                    }
                    error_log('Password reset request failed: ' . $e->getMessage());
                    \App\Helper\ApiSecurity::log($ac, 'password_reset_delivery_failed', 'forgot-password.php', ['identity_hash' => $emailHash]);
                }
            }
        } else {
            \App\Helper\ApiSecurity::log($ac, 'password_reset_rate_limited', 'forgot-password.php', ['identity_hash' => $emailHash]);
        }

        // Hesap varlığını dışarı sızdırmamak için her durumda aynı sayfaya yönlendir.
        header('Location: forgot-password-success.php');
        exit;
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
                <img src="<?php echo set('logo') ?: 'src/images/logo.svg'; ?>" alt="AYDINOĞULLARI Logo" class="main-logo">
            </a>
            <h2 class="auth-title">Şifremi Unuttum</h2>
            <p class="subtitle">Hesabınıza kayıtlı e-posta adresini girin, size bir sıfırlama bağlantısı gönderelim.</p>

            <?php echo $message; ?>

            <form action="forgot-password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\App\Helper\Security::csrf(), ENT_QUOTES, 'UTF-8'); ?>">
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
