<?php
permcontrol("setview");

if ($_POST) {

	$title = @$_POST["title"];
	$url = @$_POST["url"];
	$company = @$_POST["company"];
	$address = @$_POST["caddress"];
	$city = @$_POST["city"];
	$gsm1 = @$_POST["gsm1"];
	$gsm2 = @$_POST["gsm2"];
	$mail_host = @$_POST["mail_host"];
	$mail_username = @$_POST["mail_username"];
	$mail_password = @$_POST["mail_password"];
	$mail_port = @$_POST["mail_port"];
	$mail_admin = @$_POST["mail_admin"];

	if (empty($title) || empty($url) || empty($company) || empty($address || empty($city) || empty($gsm1))) {
		header("Location: index.php?p=settings&st=empties");
		exit;
	}

	if (@$_POST["sms_active"] == "on") {
		$smsact = "on";
	} else {
		$smsact = "";
	}


	$ins1 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins1->execute(array($title, "site_title"));

	$ins2 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins2->execute(array($url, "panel_url"));

	$ins3 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins3->execute(array($company, "company_name"));

	$ins4 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins4->execute(array($address, "company_address"));

	$ins5 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins5->execute(array($city, "company_city"));

	$ins6 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins6->execute(array($gsm1, "company_phone1"));


	$ins7 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins7->execute(array($gsm2, "company_phone2"));

	$ins8 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins8->execute(array($mail_host, "mail_host"));

	$ins9 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins9->execute(array($mail_username, "mail_username"));

	$ins10 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins10->execute(array($mail_password, "mail_password"));

	$ins11 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins11->execute(array($mail_port, "mail_port"));

	$ins12 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins12->execute(array($mail_admin, "admin_mail"));

	$ins13 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins13->execute(array($smsact, "sms_active"));

	$ins14 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins14->execute(array($_POST["sms_username"], "sms_username"));

	$ins15 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins15->execute(array($_POST["sms_pass"], "sms_pass"));

	$ins16 = $ac->prepare("UPDATE settings SET
	val = ? WHERE var = ?");
	$ins16->execute(array($_POST["sms_title"], "sms_title"));




	print_r($_FILES);
	if (isset($_FILES["logos"]["name"])) {

		$ddx = 'src/images/';
		$ffx = $ddx . basename($_FILES['logos']['name']);

		if (move_uploaded_file($_FILES['logos']['tmp_name'], $ffx)) {
			$oldlog = set("logo");
			unlink($oldlog);
			$ins8 = $ac->prepare("UPDATE settings SET
				val = ? WHERE var = ?");
			$ins8->execute(array($ddx . $_FILES["logos"]["name"], "logo"));


			header("Location:index.php?p=settings&st=suc");
		} else {
			header("Location:index.php?p=settings&st=er");
		}
	}


	header("Location: index.php?p=settings&pid=$pid&st=newsuccess");
}

if (@$_GET["st"] == "empties") {
?>
	<div class="alert alert-danger" role="alert">
		Eksik alan bırakmadan tekrar denemelisiniz.
	</div>
<?php
}
?>


<div class="pd-ltr-20 xs-pd-20-10">
	<div class="min-height-200px">

		<!-- Default Basic Forms Start -->
		<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
			<div class="clearfix">
				<div class="pull-left">
					<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
					<p class="mb-30 font-14">Sistemin tüm ayarlarını buradan yönetebilirsiniz.</p>
				</div>
				<div class="form-group">
					<input type="submit" id="submitbutton" value="KAYDET" class="float-right btn btn-secondary">
				</div>
			</div>
			<h3>Temel Ayarlar</h3><br>
			<form enctype="multipart/form-data" id="myform" action="index.php?p=settings" method="POST">
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">
						<font color="red"></font>Panel Başlığı:
					</label>
					<div class="col-sm-12 col-md-10">
						<input type="text" name="title" class="form-control" value="<?php echo set("site_title"); ?>" />
					</div>
				</div>
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">
						<font color="red">Panel URL:</font>
					</label>
					<div class="col-sm-12 col-md-10">
						<input tyoe="text" name="url" class="form-control" value="<?php echo set("panel_url"); ?>" />
					</div>
				</div>

				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">Şirket İsmi:</label>
					<div class="col-sm-12 col-md-10">
						<input tyoe="text" name="company" class="form-control" value="<?php echo set("company_name"); ?>" />
					</div>
				</div>
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">Şirket Adres:</label>
					<div class="col-sm-12 col-md-10">
						<input tyoe="text" name="caddress" class="form-control" value="<?php echo set("company_address"); ?>" />
					</div>
				</div>
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">Şehir/Ülke:</label>
					<div class="col-sm-12 col-md-10">
						<input tyoe="text" name="city" class="form-control" value="<?php echo set("company_city"); ?>" />
					</div>
				</div>
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">Şirket Telefon 1:</label>
					<div class="col-sm-12 col-md-10">
						<input tyoe="text" name="gsm1" class="form-control" value="<?php echo set("company_phone1"); ?>" />
					</div>
				</div>
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">Şirket Telefon 2:</label>
					<div class="col-sm-12 col-md-10">
						<input tyoe="text" name="gsm2" class="form-control" value="<?php echo set("company_phone2"); ?>" />
					</div>
				</div>
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">
						<font color="red"></font>Mail Sunucusu:
					</label>
					<div class="col-sm-12 col-md-10">
						<input type="text" name="mail_host" class="form-control" value="<?php echo set("mail_host"); ?>" />
					</div>
				</div>
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">Gönderici Mail Kullanıcı Adı:</label>
					<div class="col-sm-12 col-md-10">
						<input tyoe="text" name="mail_username" class="form-control" value="<?php echo set("mail_username"); ?>" />
					</div>
				</div>

				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">Gönderici Mail Şifre:</label>
					<div class="col-sm-12 col-md-10">
						<input type="password" name="mail_password" class="form-control" value="<?php echo set("mail_password"); ?>" />
					</div>
				</div>

				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">Gönderici Mail PORT:</label>
					<div class="col-sm-12 col-md-10">
						<input type="text" name="mail_port" class="form-control" value="<?php echo set("mail_port"); ?>" />
					</div>
				</div>
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">Bilgilendirme Mail Alıcı:</label>
					<div class="col-sm-12 col-md-10">
						<input type="text" name="mail_admin" class="form-control" value="<?php echo set("admin_mail"); ?>" />
					</div>
				</div>
				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">SMS Gönderimi (NetGSM)</label>
					<div class="col-sm-12 col-md-10">
						<input type="checkbox" class="switch-btn" <?php echo set("sms_active") == "on" ? "checked" : ""; ?> name="sms_active" data-color="#0059b2">
					</div>
				</div>

				<?php if (set("sms_active") == "on") {
				?>
					<div class="form-group row">
						<label class="col-sm-12 col-md-2 col-form-label">NETGSM SMS Kullanıcı Adı:</label>
						<div class="col-sm-12 col-md-10">
							<input tyoe="text" name="sms_username" class="form-control" value="<?php echo set("sms_username"); ?>" />
						</div>
					</div>

					<div class="form-group row">
						<label class="col-sm-12 col-md-2 col-form-label">NETGSM SMS Şifre:</label>
						<div class="col-sm-12 col-md-10">
							<input type="password" name="sms_title" class="form-control" value="<?php echo set("sms_title"); ?>" />
						</div>
					</div>

					<div class="form-group row">
						<label class="col-sm-12 col-md-2 col-form-label">NETGSM SMS Başlığı:</label>
						<div class="col-sm-12 col-md-10">
							<input type="text" name="sms_pass" class="form-control" value="<?php echo set("sms_pass"); ?>" />
						</div>
					</div>
				<?php
				} ?>




				<div class="form-group">
					<label>Şirket Logo</label>
					<br><img width="150" src="<?php echo set("logo"); ?>" />
					<br><br><input name="logos" type="file" class="form-control-file form-control height-auto">
				</div>
				<br>
			</form>
		</div>
	</div>
</div>
<!-- Input Validation End -->

</div>
<script>
	document.getElementById("submitbutton").addEventListener("click",function(){
		var form=document.getElementById("myform");
		form.submit();
	})
</script>

