<?php

$userId = (int) sesset('id');

if (!isset($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}

$status = $_GET['st'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $newPasswordConfirmation = (string) ($_POST['new_password_confirmation'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['profile_csrf_token'], $csrfToken)) {
        $status = 'csrf_error';
    } elseif ($currentPassword === '' || $newPassword === '' || $newPasswordConfirmation === '') {
        $status = 'empties';
    } elseif (md5(md5(md5($currentPassword))) !== (string) sesset('password')) {
        $status = 'current_password_error';
    } elseif (strlen($newPassword) < 8) {
        $status = 'password_short';
    } elseif ($newPassword !== $newPasswordConfirmation) {
        $status = 'password_mismatch';
    } elseif ($currentPassword === $newPassword) {
        $status = 'password_same';
    } else {
        $passwordHash = md5(md5(md5($newPassword)));
        $update = $ac->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([$passwordHash, $userId]);

        $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));

        if (function_exists('audit_log')) {
            audit_log('update', 'profile', 'Kullanıcı kendi şifresini değiştirdi.', 'user', $userId);
        }

        header('Location: index.php?p=profile&st=success');
        exit;
    }
}

$alerts = [
    'success' => ['success', 'Şifreniz başarıyla güncellendi.'],
    'empties' => ['alert', 'Lütfen tüm şifre alanlarını doldurun.'],
    'csrf_error' => ['alert', 'Oturum doğrulaması başarısız oldu. Lütfen formu yeniden gönderin.'],
    'current_password_error' => ['alert', 'Mevcut şifreniz hatalı.'],
    'password_short' => ['alert', 'Yeni şifreniz en az 8 karakter olmalıdır.'],
    'password_mismatch' => ['alert', 'Yeni şifre ile şifre tekrarı eşleşmiyor.'],
    'password_same' => ['alert', 'Yeni şifreniz mevcut şifrenizden farklı olmalıdır.'],
];

if (isset($alerts[$status])) {
    showAlert($alerts[$status][0], $alerts[$status][1]);
}
?>

<div class="profile-password-wrapper">
    <div class="premium-header-card mb-4">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon"><i class="fa fa-lock"></i></div>
                <div class="header-title">
                    <h4>Profil Güvenliği</h4>
                    <span class="header-number-badge">
                        <i class="fa fa-user"></i>
                        <?php echo htmlspecialchars((string) sesset('username'), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card profile-password-card">
        <div class="form-card-header">
            <div class="card-icon card-icon-blue"><i class="fa fa-key"></i></div>
            <div>
                <h5>Şifre Değiştir</h5>
                <p>Hesabınızın şifresini güvenli biçimde güncelleyin.</p>
            </div>
        </div>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['profile_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-field mb-3">
                <label for="current_password"><span class="text-danger">(*)</span> Mevcut Şifre</label>
                <input required type="password" name="current_password" id="current_password" class="form-control" autocomplete="current-password" placeholder="Mevcut şifrenizi girin">
            </div>

            <div class="form-field mb-3">
                <label for="new_password"><span class="text-danger">(*)</span> Yeni Şifre</label>
                <input required minlength="8" type="password" name="new_password" id="new_password" class="form-control" autocomplete="new-password" placeholder="En az 8 karakter">
            </div>

            <div class="form-field mb-4">
                <label for="new_password_confirmation"><span class="text-danger">(*)</span> Yeni Şifre Tekrar</label>
                <input required minlength="8" type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" autocomplete="new-password" placeholder="Yeni şifrenizi tekrar girin">
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fa fa-save mr-2"></i> Şifreyi Güncelle
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .profile-password-wrapper { max-width: 760px; margin: 0 auto; }
    .profile-password-card { padding: 28px; }
    .profile-password-card .form-card-header { margin-bottom: 24px; }
    @media (max-width: 576px) { .profile-password-card { padding: 20px; } }
</style>
