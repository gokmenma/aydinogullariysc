<?php
require_once 'bootstrap.php';

if ($_POST) {
	$up = $_POST['email'];
	$pp = md5(md5(md5($_POST['passwordp'])));

	if (!$up || !$pp) {
		header('Location: login.php?error=102');
		exit;  // Yönlendirmeden sonra scriptin çalışmasını durdurun
	} else {
		$ucont = $ac->prepare('SELECT * FROM users WHERE email = ? AND password = ? AND statu = ?');
		$ucont->execute(array($up, $pp, 1));
		$conts = $ucont->fetch();

		if ($conts) {
			$_SESSION['login'] = true;
			$_SESSION['perm'] = $conts['permission'];
			$_SESSION['lid'] = $conts['id'];
            $_SESSION['username'] = $conts['username'];

            // Log successful login
            audit_log("login", "auth", "Sisteme giriş yaptı", "user", $conts['id']);

			// returnUrl parametresini kontrol edin ve varsayılan değeri ayarlayın
			$redirectUri = isset($_GET['returnUrl']) && !empty($_GET['returnUrl']) ? $_GET['returnUrl'] : 'index.php?p=home';
	
			header('Location: ' . $redirectUri);
			exit;  // Yönlendirmeden sonra scriptin çalışmasını durdurun
		} else {
			header('Location: login.php?error=103&HATA');
			exit;  // Yönlendirmeden sonra scriptin çalışmasını durdurun
		}
	}
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş | AYDINOĞULLARI</title>

    <!-- Google Fonts: Geist font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">

    <!-- Font Awesome İkonları -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="vendors/styles/login.css?v=<?php echo filemtime("vendors/styles/login.css")?>">
</head>

<body>

    <div class="auth-shell">
        <div class="auth-card">
            <a href="index.php" class="brand">
                <img src="src/images/logo.png" alt="AYDINOĞULLARI Logo" class="main-logo">
            </a>
            <h2 class="auth-title">Giriş Yap</h2>
            <p class="subtitle">E-posta ve parolanızla devam edin.</p>

            <?php 
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
                echo '<div class="alert error">' . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            ?>

            <form action="login.php<?php echo isset($_GET['returnUrl']) ? '?returnUrl=' . htmlspecialchars($_GET['returnUrl'], ENT_QUOTES, 'UTF-8') : ''; ?>" method="POST">
                <div class="floating-group">
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope icon"></i>
                        <input type="email" id="email" name="email" class="input-element" placeholder=" " required autocomplete="email">
                        <label class="floating-label" for="email">E-posta Adresi</label>
                    </div>
                </div>

                <div class="floating-group">
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock icon"></i>
                        <input type="password" id="passwordp" name="passwordp" class="input-element" placeholder=" " required autocomplete="current-password">
                        <label class="floating-label" for="passwordp">Parola</label>
                        <button type="button" class="toggle-password" aria-label="Parolayı göster" data-target="passwordp">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" class="switch-input">
                        <label for="remember" class="switch-label">Beni Hatırla</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">Parolamı Unuttum</a>
                </div>

                <button type="submit" class="btn-login">Giriş Yap</button>
            </form>

            <div class="footer-text">
                Hesabınız yok mu? <a href="#">Kayıt Ol</a>
            </div>
        </div>
    </div>
<script>
document.querySelectorAll('.toggle-password').forEach(function(btn){
  btn.addEventListener('click', function(){
    var input = document.getElementById(btn.getAttribute('data-target'));
    var icon = btn.querySelector('i');
    if(!input) return;
    if(input.type === 'password'){
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
      btn.setAttribute('aria-label', 'Parolayı gizle');
    }else{
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
      btn.setAttribute('aria-label', 'Parolayı göster');
    }
  });
});

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
  setTimeout(update, 300);
  var started = Date.now();
  var timer = setInterval(function(){
    update();
    if(Date.now() - started > 5000) clearInterval(timer);
  }, 400);
});

// Button ripple/shine trigger fallback for keyboard navigation
document.querySelectorAll('.btn-login').forEach(function(btn){
  btn.addEventListener('focus', function(){
    btn.classList.add('focused');
  });
  btn.addEventListener('blur', function(){
    btn.classList.remove('focused');
  });
});
</script>
</body>
</html>
