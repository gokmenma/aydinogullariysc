<?php
require_once 'bootstrap.php';

if ($_POST) {
	$up = trim((string) ($_POST['email'] ?? ''));
	$plainPassword = (string) ($_POST['passwordp'] ?? '');
	$csrfToken = (string) ($_POST['csrf_token'] ?? '');
	$ipAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
	$identityHash = hash('sha256', mb_strtolower($up, 'UTF-8'));

	if (!\App\Helper\Security::checkCsrfToken($csrfToken)) {
		header('Location: login.php?error=104');
		exit;
	}

	if ($up === '' || $plainPassword === '') {
		header('Location: login.php?error=102');
		exit;
	} else {
		// Aynı tarayıcı oturumunda e-posta değiştirilerek limitin aşılmasını engelle.
		$sessionFailureCount = (int) ($_SESSION['login_failure_count'] ?? 0);
		$sessionFailureStartedAt = (int) ($_SESSION['login_failure_started_at'] ?? 0);
		if ($sessionFailureStartedAt === 0 || (time() - $sessionFailureStartedAt) > 900) {
			$sessionFailureCount = 0;
			$_SESSION['login_failure_count'] = 0;
			$_SESSION['login_failure_started_at'] = time();
		}
		if ($sessionFailureCount >= 5) {
			\App\Helper\ApiSecurity::log($ac, 'login_rate_limited', 'login.php', [
				'identity_hash' => $identityHash,
				'limit_scope' => 'browser_session',
			]);
			header('Location: login.php?error=105');
			exit;
		}

		$attemptQuery = $ac->prepare('SELECT COUNT(*) FROM login_attempts WHERE identity_hash = ? AND ip_address = ? AND was_successful = 0 AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)');
		$attemptQuery->execute([$identityHash, $ipAddress]);
		if ((int) $attemptQuery->fetchColumn() >= 5) {
			\App\Helper\ApiSecurity::log($ac, 'login_rate_limited', 'login.php', ['identity_hash' => $identityHash]);
			header('Location: login.php?error=105');
			exit;
		}

		$ucont = $ac->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(?) AND statu = ? LIMIT 1');
		$ucont->execute(array($up, 1));
		$conts = $ucont->fetch();
		$storedHash = (string) ($conts['password'] ?? '');
		$isLegacy = (bool) preg_match('/^[a-f0-9]{32}$/i', $storedHash);
		$isValid = $conts && ($isLegacy
			? hash_equals(strtolower($storedHash), md5(md5(md5($plainPassword))))
			: password_verify($plainPassword, $storedHash));

		$attemptInsert = $ac->prepare('INSERT INTO login_attempts (identity_hash, ip_address, was_successful, attempted_at) VALUES (?, ?, ?, NOW())');
		$attemptInsert->execute([$identityHash, $ipAddress, $isValid ? 1 : 0]);

		if ($isValid) {
			unset($_SESSION['login_failure_count'], $_SESSION['login_failure_started_at']);
			if ($isLegacy || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
				$newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
				$rehash = $ac->prepare('UPDATE users SET password = ? WHERE id = ?');
				$rehash->execute([$newHash, $conts['id']]);
			}
			session_regenerate_id(true);
			$_SESSION['login'] = true;
			$_SESSION['perm'] = $conts['permission'];
			$_SESSION['lid'] = $conts['id'];
			$_SESSION['username'] = $conts['username'];

			// Log successful login
			audit_log("login", "auth", "Sisteme giriş yaptı", "user", $conts['id']);

			// returnUrl parametresini kontrol edin ve varsayılan değeri ayarlayın
			$redirectUri = 'index.php?p=home';
			if (!empty($_GET['returnUrl'])) {
				$candidate = rawurldecode((string) $_GET['returnUrl']);
				if ($candidate !== '' && $candidate[0] === '/' && substr($candidate, 0, 2) !== '//') {
					$redirectUri = $candidate;
				} elseif (preg_match('/^index\.php(?:\?|$)/', $candidate)) {
					$redirectUri = $candidate;
				}
			}

			header('Location: ' . $redirectUri);
			exit;
		} else {
			$_SESSION['login_failure_count'] = $sessionFailureCount + 1;
			$_SESSION['login_failure_started_at'] = $_SESSION['login_failure_started_at'] ?? time();
			header('Location: login.php?error=103&HATA');
			exit;
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

    <!-- Google Fonts (Geist, Inter, Plus Jakarta Sans, Poppins, Outfit, Roboto, Montserrat) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Font Awesome İkonları -->
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

                var savedWeight = localStorage.getItem('app_theme_weight') || '400';
                document.documentElement.setAttribute('data-theme-weight', savedWeight);

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
                        document.body.setAttribute('data-theme-weight', savedWeight);
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

    <!-- CSS -->
    <link rel="stylesheet" href="vendors/styles/login.css?v=<?php echo filemtime("vendors/styles/login.css"); ?>">
</head>

<body>

    <!-- Hızlı Tema & Mod Seçici Bar (Kompakt Açılır Tasarım) -->
    <div class="login-theme-bar" id="loginThemeBar" role="toolbar" aria-label="Tema Seçimi">
        <div class="theme-bar-trigger" id="themeBarTrigger" title="Temaları Göster / Gizle">
            <span class="theme-bar-arrow">
                <i class="fa-solid fa-chevron-left"></i>
            </span>
            <span class="theme-bar-current-color" id="themeActiveColorIndicator" title="Aktif Tema Rengi"></span>
        </div>
        <div class="theme-pills-list">
            <button type="button" class="theme-pill-btn" data-preset="kode" style="background: #2563eb;" title="Kode (Mavi)"></button>
            <button type="button" class="theme-pill-btn" data-preset="ersan-gold" style="background: #d97706;" title="Ersan Gold"></button>
            <button type="button" class="theme-pill-btn" data-preset="zumrut" style="background: #059669;" title="Zümrüt Yeşili"></button>
            <button type="button" class="theme-pill-btn" data-preset="kraliyet-moru" style="background: #6f42c1;" title="Kraliyet Moru"></button>
            <button type="button" class="theme-pill-btn" data-preset="rose" style="background: #e11d48;" title="Rose"></button>
            <button type="button" class="theme-pill-btn" data-preset="sade-beyaz" style="background: #334155;" title="Sade Beyaz"></button>
            <button type="button" class="theme-pill-btn" data-preset="safir-okyanus" style="background: #0284c7;" title="Safir Okyanus"></button>
            <button type="button" class="theme-pill-btn" data-preset="gun-batimi" style="background: #ea580c;" title="Gün Batımı"></button>
            <button type="button" class="theme-pill-btn" data-preset="gece-altini" style="background: #eab308;" title="Gece Altını"></button>
            <button type="button" class="theme-pill-btn" data-preset="mistik-bordo" style="background: #881337;" title="Mistik Bordo"></button>
            <button type="button" class="theme-pill-btn" data-preset="nordik-cam" style="background: #14532d;" title="Nordik Çam"></button>
            <button type="button" class="theme-pill-btn" data-preset="soft-lavanta" style="background: #8b5cf6;" title="Soft Lavanta"></button>
            <button type="button" class="theme-pill-btn" data-preset="soft-adacayi" style="background: #0d9488;" title="Soft Adaçayı"></button>
            <button type="button" class="theme-pill-btn" data-preset="soft-seftali" style="background: #f97316;" title="Soft Şeftali"></button>
            <button type="button" class="theme-pill-btn" data-preset="soft-buz-mavisi" style="background: #38bdf8;" title="Soft Buz Mavisi"></button>
            <button type="button" class="theme-pill-btn" data-preset="soft-vizon" style="background: #78716c;" title="Soft Vizon"></button>
        </div>
        <button type="button" class="theme-mode-toggle" id="loginThemeToggle" title="Karanlık / Aydınlık Mod">
            <i class="fa-solid fa-moon"></i>
        </button>
    </div>

    <div class="auth-shell">
        <div class="auth-card">
            <a href="index.php" class="brand">
                <img src="<?php echo set('logo') ?: 'src/images/logo.svg'; ?>" alt="AYDINOĞULLARI Logo" class="main-logo">
            </a>
            <h2 class="auth-title">Giriş Yap</h2>
            <p class="subtitle">E-posta ve parolanızla devam edin.</p>

            <?php 
            if (isset($_GET['password_reset'])) {
                echo '<div class="alert" style="background:#dcfce7;color:#166534;">Parolanız yenilendi. Yeni parolanızla giriş yapabilirsiniz.</div>';
            }
            if (isset($_GET['error'])) {
                $errorMessage = '';
                switch ($_GET['error']) {
                    case '102':
                        $errorMessage = 'Lütfen tüm alanları doldurun.';
                        break;
                    case '103':
                        $errorMessage = 'E-posta veya parola hatalı.';
                        break;
                    case '104':
                        $errorMessage = 'Oturum doğrulaması başarısız. Lütfen tekrar deneyin.';
                        break;
                    case '105':
                        $errorMessage = 'Çok fazla başarısız deneme. Lütfen 15 dakika sonra tekrar deneyin.';
                        break;
                    default:
                        $errorMessage = 'Bilinmeyen bir hata oluştu.';
                        break;
                }
                echo '<div class="alert error">' . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            ?>

            <form action="login.php<?php echo isset($_GET['returnUrl']) ? '?returnUrl=' . htmlspecialchars($_GET['returnUrl'], ENT_QUOTES, 'UTF-8') : ''; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\App\Helper\Security::csrf(), ENT_QUOTES, 'UTF-8'); ?>">
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
// Parola Göster/Gizle
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

// Floating Input Handlers
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

// Tema Seçici & Eşitleme
(function () {
    var themePresetFonts = {
        'kode': 'inter',
        'ersan-gold': 'montserrat',
        'zumrut': 'plus-jakarta',
        'kraliyet-moru': 'outfit',
        'rose': 'poppins',
        'sade-beyaz': 'inter',
        'koyu-gece': 'geist',
        'safir-okyanus': 'outfit',
        'gun-batimi': 'poppins',
        'gece-altini': 'montserrat',
        'mistik-bordo': 'montserrat',
        'nordik-cam': 'plus-jakarta',
        'soft-lavanta': 'outfit',
        'soft-adacayi': 'plus-jakarta',
        'soft-seftali': 'poppins',
        'soft-buz-mavisi': 'inter',
        'soft-vizon': 'montserrat'
    };

    function applyPreset(presetName) {
        if (!presetName) return;
        try { localStorage.setItem('app_theme_preset', presetName); } catch(e){}
        
        document.documentElement.setAttribute('data-theme-preset', presetName);
        if (document.body) document.body.setAttribute('data-theme-preset', presetName);

        var font = themePresetFonts[presetName] || 'inter';
        try { localStorage.setItem('app_theme_font', font); } catch(e){}
        document.documentElement.setAttribute('data-theme-font', font);
        if (document.body) document.body.setAttribute('data-theme-font', font);

        if (presetName === 'koyu-gece' || presetName === 'gece-altini') {
            setDarkMode(true);
        } else {
            setDarkMode(false);
        }

        syncPills();
    }

    function setDarkMode(isDark) {
        var html = document.documentElement;
        var body = document.body;
        var icon = document.querySelector('#loginThemeToggle i');

        if (isDark) {
            html.classList.add('dark-mode');
            if (body) body.classList.add('dark-mode');
            try { localStorage.setItem('theme', 'dark'); } catch(e){}
            if (icon) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        } else {
            html.classList.remove('dark-mode');
            if (body) body.classList.remove('dark-mode');
            try { localStorage.setItem('theme', 'light'); } catch(e){}
            if (icon) {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
    }

    function syncPills() {
        var activePreset = localStorage.getItem('app_theme_preset') || document.documentElement.getAttribute('data-theme-preset') || 'kode';
        document.querySelectorAll('.theme-pill-btn').forEach(function(pill) {
            if (pill.getAttribute('data-preset') === activePreset) {
                pill.classList.add('active');
            } else {
                pill.classList.remove('active');
            }
        });

        var isDark = document.documentElement.classList.contains('dark-mode') || localStorage.getItem('theme') === 'dark';
        var icon = document.querySelector('#loginThemeToggle i');
        if (icon) {
            if (isDark) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
    }

    document.querySelectorAll('.theme-pill-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var preset = btn.getAttribute('data-preset');
            applyPreset(preset);
        });
    });

    var modeToggle = document.getElementById('loginThemeToggle');
    if (modeToggle) {
        modeToggle.addEventListener('click', function() {
            var isDark = document.documentElement.classList.contains('dark-mode');
            setDarkMode(!isDark);
        });
    }

    var themeBarTrigger = document.getElementById('themeBarTrigger');
    var loginThemeBar = document.getElementById('loginThemeBar');
    if (themeBarTrigger && loginThemeBar) {
        themeBarTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            loginThemeBar.classList.toggle('expanded');
        });

        document.addEventListener('click', function(e) {
            if (!loginThemeBar.contains(e.target)) {
                loginThemeBar.classList.remove('expanded');
            }
        });
    }

    syncPills();
})();
</script>
</body>
</html>
