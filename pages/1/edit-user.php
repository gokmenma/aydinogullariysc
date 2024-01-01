<?php

if (!permtrue("uedit") && sesset("id") != $_GET["id"]) {
	header("Location: index.php");
}

if (!@$_GET["id"]) {

	header("Location:index.php?p=all-users");
	exit;
}


$uid = $_GET["id"];
$conts = $ac->prepare("SELECT * FROM users WHERE id = ?");
$conts->execute(array($uid));
$cc = $conts->fetch(PDO::FETCH_ASSOC);

if ($_POST) {

	if (!$_POST["username"]) {
		header("Location: index.php?p=new-user&st=empties");
		exit;
	}
	if (@$_POST["password"]) {
		$password = md5(md5(md5($_POST["password"])));
	} else {
		$password = $cc["password"];
	}


	$username = @$_POST["username"];
	$tckimlikno = @$_POST["tckimlikno"];
	$email = @$_POST["email"];
	$gsm = @$_POST["gsm"];
	$gsm2 = @$_POST["gsm2"];
	$city = @$_POST["city"];
	$address = @$_POST["address"];
	$name = @$_POST["name"];
	$surname = @$_POST["surname"];
	$regdate = @$_POST["regdate"];
	$giristarihi = @$_POST["giristarihi"];
	$dogumtarihi = @$_POST["dogumtarihi"];
	$cikistarihi = @$_POST["cikistarihi"];
	$permission = @$_POST["permission"];


	$insq = $ac->prepare("UPDATE users SET  
									username = ? , tckimlikno = ? , password = ? , 
									avatar_link = ? , email = ? , gsm = ? , 
									gsm2 = ? , city = ? , address = ? , 
									name = ? , surname = ? , regdate = ? , 
									creativer = ? , giristarihi = ? , dogumtarihi = ? , 
									cikistarihi = ? , permission = ?  WHERE id = ? ");

	$insq->execute(array(
		$username, $tckimlikno, $password,
		"vendors/images/photo2.jpg", $email, $gsm,
		$gsm2, $city, $address,
		$name, $surname, TODAY,
		$uid, $giristarihi, $dogumtarihi,
		$cikistarihi, $permission, $uid
	));
	//echo '<script> console.log(`' . json_encode($insq) . '`); </script>';

	// if (permtrue("uperm") and $uid != sesset("id")) {
	// 	$uplk = $ac->prepare("UPDATE users SET permission = ? WHERE id = ?");
	// 	$uplk->execute(array($uprs, $uid));
	// }
	if ($insq) {
		header("Location:index.php?p=all-users");
	}
}

?>


<!-- Default Basic Forms Start -->
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>

			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>

		<div class="form-group float-right m-2 ">

			<input type="submit" id="submitButton" onclick="validateForm()" value="Kaydet" class="btn btn-primary">
			<a class="btn btn-secondary" href="index.php?p=all-users">Listeye Dön</a>
		</div>
	</div>
	<form enctype="multipart/form-data" id="myForm" action="" method="POST">
		<div class="row">

			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label for="utc">
						<font color="red">(*)</font>Kimlik No
					</label>
					<input required type="text" name="tckimlikno" value="<?php echo $cc["tckimlikno"]; ?>" class="form-control" maxlength="11">
				</div>
			</div>

			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Ad:
					</label>
					<input required type="text" value="<?php echo $cc["name"]; ?>" name="name" class="form-control">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Soyad:
					</label>
					<input required type="text" value="<?php echo $cc["surname"]; ?>" name="surname" class="form-control">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> E-Posta:
					</label>
					<input required name="email" value="<?php echo $cc["email"]; ?>" type="text" class="form-control">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Kullanıcı Adı
					</label>
					<input required type="text" name="username" value="<?php echo $cc["username"]; ?>" class="form-control">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Parola:
					</label>
					<input required id="passwordField" name="password" type="password" value="<?php echo $cc["username"]; ?>" class="form-control">
				</div>
			</div>

		</div>
		<div class="row">

			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label> Adres:</label>
					<input name="address" type="text" value="<?php echo $cc["address"]; ?>" class="form-control">
				</div>
			</div>
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label> Şehir:</label>
					<input name="city" type="text" value="<?php echo $cc["city"]; ?>" class="form-control">
				</div>
			</div>
		</div>


		<div class="row">
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label> Telefon:</label>
					<input name="gsm" type="text" value="<?php echo $cc["gsm"]; ?>" class="form-control">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label> Telefon 2:</label>
					<input name="gsm2" type="text" value="<?php echo $cc["gsm2"]; ?>" class="form-control">
				</div>
			</div>
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>Doğum Tarihi</label>
					<input name="dogumtarihi" value="<?php echo $cc["dogumtarihi"]; ?>" class="form-control date-picker" placeholder="dd.mm.yyyy">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>İşe Başlama Tarihi</label>
					<input name="giristarihi" value="<?php echo $cc["giristarihi"]; ?>" class="form-control date-picker" placeholder="dd.mm.yyyy">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
						<label>İşten Çıkış Tarihi</label>
					<input name="cikistarihi" value="<?php echo $cc["cikistarihi"]; ?>" class="form-control date-picker" placeholder="dd.mm.yyyy">
				</div>
			</div>

			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Pozisyon:
					</label>
					<select name="permission" class="form-control">
						<?php
						$pquery = $ac->prepare("SELECT * FROM perms ");
						$pquery->execute();
						while ($pm = $pquery->fetch(PDO::FETCH_ASSOC)) {
						?>

							<option value="<?php echo $pm["id"]; ?>"><?php echo $pm["p_title"]; ?></option>
						<?php } ?>

					</select>
				</div>
			</div>

		</div><br><br>
	</form>

</div>
<!-- Input Validation End -->


