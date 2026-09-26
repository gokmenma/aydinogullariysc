<?php
require_once __DIR__ . '/bootstrap.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$message = '';
$isValidToken = false;
$tokenRow = null;

if (preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $tokenQuery = $ac->prepare(
        'SELECT prt.id, prt.user_id, u.email
         FROM password_reset_tokens prt
         INNER JOIN users u ON u.id = prt.user_id
         WHERE prt.token_hash = ? AND prt.used_at IS NULL AND prt.expires_at >= NOW() AND u.statu = 1
         LIMIT 1'
    );
    $tokenQuery->execute([hash('sha256', $token)]);
    $tokenRow = $tokenQuery->fetch(PDO::FETCH_ASSOC);
    $isValidToken = (bool) $tokenRow;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isValidToken) {
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    if (!\App\Helper\Security::checkCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
        $message = '<div class="alert error">Oturum doğrulaması başarısız. Sayfayı yenileyin.</div>';
    } elseif (strlen($password) < 10 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)
        || !preg_match('/\d/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $message = '<div class="alert error">Parola en az 10 karakter; büyük harf, küçük harf, rakam ve özel karakter içermelidir.</div>';
    } elseif (!hash_equals($password, $confirmation)) {
        $message = '<div class="alert error">Parola doğrulaması eşleşmiyor.</div>';
    } else {
        try {
            $ac->beginTransaction();
            $consume = $ac->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ? AND used_at IS NULL AND expires_at >= NOW()');
            $consume->execute([(int) $tokenRow['id']]);
            if ($consume->rowCount() !== 1) {
                throw new RuntimeException('Token daha önce kullanılmış.');
            }
            $update = $ac->prepare('UPDATE users SET password = ? WHERE id = ? AND statu = 1');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), (int) $tokenRow['user_id']]);
            $ac->commit();
            unset($_SESSION['login_failure_count'], $_SESSION['login_failure_started_at']);
            \App\Helper\ApiSecurity::log($ac, 'password_reset_completed', 'reset-password.php', ['user_id' => (int) $tokenRow['user_id']]);
            header('Location: login.php?password_reset=1');
            exit;
        } catch (Throwable $e) {
            if ($ac->inTransaction()) {
                $ac->rollBack();
            }
            error_log('Password reset failed: ' . $e->getMessage());
            $message = '<div class="alert error">Parola yenilenemedi. Yeni bir bağlantı talep edin.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parola Yenile | AYDINOĞULLARI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="vendors/styles/login.css?v=<?php echo filemtime('vendors/styles/login.css'); ?>">
</head>
<body>
<div class="auth-shell">
    <div class="auth-card">
        <a href="login.php" class="brand"><img src="<?php echo htmlspecialchars(set('logo') ?: 'src/images/logo.svg', ENT_QUOTES, 'UTF-8'); ?>" alt="AYDINOĞULLARI Logo" class="main-logo"></a>
        <h2 class="auth-title">Yeni Parola Belirleyin</h2>
        <?php if (!$isValidToken): ?>
            <div class="alert error">Bu bağlantı geçersiz, süresi dolmuş veya daha önce kullanılmış.</div>
            <div class="footer-text"><a href="forgot-password.php">Yeni bağlantı isteyin</a></div>
        <?php else: ?>
            <p class="subtitle">En az 10 karakterden oluşan güçlü bir parola belirleyin.</p>
            <?php echo $message; ?>
            <form method="POST" action="reset-password.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\App\Helper\Security::csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="floating-group"><div class="input-wrapper"><i class="fa-solid fa-lock icon"></i><input type="password" id="password" name="password" class="input-element" placeholder=" " required autocomplete="new-password"><label class="floating-label" for="password">Yeni Parola</label></div></div>
                <div class="floating-group"><div class="input-wrapper"><i class="fa-solid fa-lock icon"></i><input type="password" id="password_confirmation" name="password_confirmation" class="input-element" placeholder=" " required autocomplete="new-password"><label class="floating-label" for="password_confirmation">Yeni Parola Tekrar</label></div></div>
                <button type="submit" class="btn-login">Parolayı Yenile</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<script>document.querySelectorAll('.input-element').forEach(function(i){function u(){i.closest('.floating-group').classList.toggle('filled',!!i.value);}['input','change','blur','focus'].forEach(function(e){i.addEventListener(e,u);});u();});</script>
</body>
</html>
