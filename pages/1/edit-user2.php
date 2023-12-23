<?php
if (!permtrue("uedit") and sesset("id") != $_GET["uid"]) {
	header("Location: index.php");
}
if (!@$_GET["uid"]) {

	header("Location:index.php?p=all-users");
	exit;
}

$uid = $_GET["uid"];

$conts = $ac->prepare("SELECT * FROM users WHERE id = ?");
$conts->execute(array($uid));
$cc = $conts->fetch(PDO::FETCH_ASSOC);
if (!$cc) {
	header("Location:index.php?p=all-users&err=4324324");
	exit;
}

if ($_POST) {

	if (!$_POST["uusername"]) {
		header("Location: index.php?p=new-user&st=empties");
		exit;
	}
	if (@$_POST["upassword"]) {
		$upassword = md5(md5(md5($_POST["upassword"])));
	} else {
		$upassword = $cc["password"];
	}

	$uusername = @$_POST["uusername"];
	$uemail = @$_POST["uemail"];
	$uname = @$_POST["uname"];
	$usurname = @$_POST["usurname"];

	$ugsm = @$_POST["ugsm"];
	$uaddress = @$_POST["uaddress"];
	$ucity = @$_POST["ucity"];
	$uperm = 1;
	$uface = @$_POST["uface"];
	$utwit = @$_POST["utwit"];
	$uinsta = @$_POST["uinsta"];
	$ulinked = @$_POST["ulinked"];

	$uprs = $_POST["permission"];




	$regg = $ac->prepare("UPDATE users SET
    username = ?,
    password = ?,
    avatar_link = ?,
    email = ?,
    gsm = ?,
    city = ?,
    address = ?,
    name = ?,
    surname = ?,
    facebook = ?,
    twitter = ?,
    instagram = ?,
    linkedin = ?,
    perm = ? WHERE id = ?");

	$regg->execute(array($uusername, $upassword, "vendors/images/photo2.jpg", $uemail, $ugsm, $ucity, $uaddress, $uname, $usurname, $uface, $utwit, $uinsta, $ulinked, $uperm, $uid));

	if (permtrue("uperm") and $uid != sesset("id")) {
		$uplk = $ac->prepare("UPDATE users SET permission = ? WHERE id = ?");
		$uplk->execute(array($uprs, $uid));
	}
	if ($regg) {
 		header("Location:index.php?p=edit-user&uid=$uid&st=newsuccess");
	} else {
		
	}
}



if (@$_GET["st"] == "empties") {
?>
	<div class="alert alert-danger" role="alert">
		(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
	</div>
<?php
}
if (@$_GET["st"] == "newsuccess") {

?>
	<div class="alert alert-success" role="alert">
		Bilgiler kaydedildi.
	</div>
<?php
}
?>


<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>
		<div class="form-group">
			<input type="submit" value="Kaydet" id="submitButton" class="btn btn-primary float-right">
		</div>
	</div>



	<form enctype="multipart/form-data" action="" id="myForm" method="POST">
		<div class="row">

			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font>Kimlik No
					</label>
					<input required type="text" id="utc" value="<?php echo $cc["tckimlikno"]; ?>" name="utc" class="form-control" maxlength="11">
				</div>
			</div>

			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Ad:
					</label>
					<input required type="text" value="<?php echo $cc["name"]; ?>" name="uname" class="form-control">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Soyad:
					</label>
					<input required type="text" value="<?php echo $cc["surname"]; ?>" name="usurname" class="form-control">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> E-Posta:
					</label>
					<input required name="uemail" value="<?php echo $cc["email"]; ?>" type="text" class="form-control">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Kullanıcı Adı
					</label>
					<input required type="text" value="<?php echo $cc["username"]; ?>" name="uusername" class="form-control">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Parola:
					</label>
					<input required name="upassword" value="<?php echo $cc["password"]; ?>" type="text" class="form-control">
				</div>
			</div>

		</div>
		<div class="row">

			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label> Adres:</label>
					<input name="uaddress" type="text" value="<?php echo $cc["adress"]; ?>" class="form-control">
				</div>
			</div>
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label> Şehir:</label>
					<input name="ucity" value="<?php echo $cc["city"]; ?>" type="text" class="form-control">
				</div>
			</div>
		</div>


		<div class="row">
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label> Telefon:</label>
					<input name="ugsm" value="<?php echo $cc["gsm"]; ?>" type="text" class="form-control">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label> Telefon 2:</label>
					<input name="ugsm2" value="<?php echo $cc["gsm2"]; ?>" type="text" class="form-control">
				</div>
			</div>
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>Doğum Tarihi</label>
					<input name="udgmtarihi" value="<?php echo $cc["dogumtarihi"]; ?>" type="text" class="form-control date-picker" placeholder="dd.mm.yyyy">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>İşe Başlama Tarihi</label>
					<input name="ugiristarihi" value="<?php echo $cc["giristarihi"]; ?>" type="text" class="form-control date-picker" placeholder="dd.mm.yyyy">
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label>İşten Çıkış Tarihi</label>
					<input name="ucikistarihi" value="<?php echo $cc["cikistarihi"]; ?>" type="text" class="form-control date-picker" placeholder="dd.mm.yyyy">
				</div>
			</div>

			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Pozisyon:
					</label>
					<select name="permission" class="custom-select col-12">
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
<!-- <script>
	document.getElementById("submitButton").addEventListener("click", function() {
		var form = document.getElementById("myForm");
		form.submit();
	})
</script> -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function() {
    $("#submitButton").on("click", function() {
      var form = $("#myForm");
      form.submit();
    });
  });
</script>

<?php include('include/footer.php'); ?>