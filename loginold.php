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
<html>

<head>
	<?php include ('include/head.php'); ?>
</head>

<body>
	<div class="login-wrap customscroll d-flex align-items-center flex-wrap justify-content-center pd-20">
		<div class="login-box box-shadow pd-30 border-radius-5">
			<img src="vendors/images/login-img.png" alt="Giriş yap" class="login-img">
			<h2 class="text-center mb-30 login-title">Kullanıcı Girişi</h2>
			<form action="" method="POST">
				<div class="input-group custom input-group-lg">
					<input type="email" name="email" class="form-control" placeholder="Eposta Adresi">
					<div class="input-group-append custom">
						<span class="input-group-text"><i class="fa fa-user" aria-hidden="true"></i></span>
					</div>
				</div>
				<div class="input-group custom input-group-lg">
					<input type="password" name="passwordp" class="form-control" placeholder="Parola">
					<div class="input-group-append custom">
						<span class="input-group-text"><i class="fa fa-lock" aria-hidden="true"></i></span>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<div class="input-group">
							<!--
								use code for form submit
								<input class="btn btn-outline-primary btn-lg btn-block" type="submit" value="Sign In">
							-->
							<input type="submit" class="btn btn-outline-primary btn-lg btn-block"
								value="Giriş yap">
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
	<?php include ('include/script.php'); ?>
</body>

</html>
