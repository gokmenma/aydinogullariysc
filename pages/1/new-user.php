<?php
permcontrol("uadd");


if ($_POST) {

	if (!$_POST["uemail"] || !$_POST["uname"] || !$_POST["upassword"]) {

		header("Location: index.php?p=new-user&st=empties");
		exit;
	}

	$contpid = $ac->prepare("SELECT email FROM users WHERE email = ?");
	$email = $_POST["uemail"];
	$contpid->execute(array($email));
	$result = $contpid->fetch(PDO::FETCH_ASSOC);

	if ($result) {
		// Eğer veritabanında bir sonuç varsa, bu e-posta ile kayıtlı bir kullanıcı var
		//showAlert('alert', 'Bu adı taşıyan bir kullanıcı zaten mevcut.');
		header("Location: index.php?p=new-user&st=thereuser");
		exit;
	}



	$uname = @$_POST["uname"];
	$uemail = @$_POST["uemail"];
	$upassword = password_hash((string) $_POST["upassword"], PASSWORD_DEFAULT);
	$uperm = 1;
	$perm = @$_POST["permission"];
	$ugsm = @$_POST["ugsm"];



	$regg = $ac->prepare("INSERT INTO users SET
										name = ?, 	
										password = ?,
										email = ?, 
										gsm = ?,
										perm = ?, 
										permission = ?, 
										regdate = ?,
										creativer = ?, 
										statu = ?");

	$regg->execute(array($uname, $upassword, $uemail, $ugsm, $uperm, $perm ,TODAY, sesset("id"), 1));

	if ($regg) {
		//header("Location: index.php?p=new-user&st=newsuccess");
	}

	if (@$_GET["st"] == "empties") {
		showAlert('alert', "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.");
	}
	// if ($_GET["st"] == "newsuccess") {
	if ($regg) {
		showAlert("success", "İşlem Başarı ile tamamlandı!"); ?>
		<!--sayfa yenilemesi yaptığında tekrar mesajı göstermemesi için link yönlendirmesi yapıldı -->
		<script> window.history.pushState({}, '', 'index.php?p=new-user')</script>
		<?php
	}

}
	 if ($_GET["st"] == "thereuser") {
			showAlert("alert", "Bu email adresi ile kullanıcı kaydı yapılmıştır!"); ?>
			<!--sayfa yenilemesi yaptığında tekrar mesajı göstermemesi için link yönlendirmesi yapıldı -->
			<script> window.history.pushState({}, '', 'index.php?p=new-user')</script>
			<?php 
		}

?>




<!-- Default Basic Forms Start -->
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue">
				<?php echo $pdat["p_title"]; ?>
			</h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
				bırakmayın..<br></p>
		</div>
		<div class="form-group">

			<input type="submit" id="submitButton" onclick="validateForm()" data-toggle="tooltip" data-placement="top"
				title="Kaydet" value="Kaydet" class="float-right btn btn-primary">
		</div>
	</div>
	<form enctype="multipart/form-data" id="myForm" method="POST">
		<div class="row">

			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label for="uname">
						<font color="red">(*)</font> Adı Soyadı :
					</label>
					<input required type="text" name="uname" value="<?php echo $uname ?>" class="form-control">
				</div>
			</div>
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label for="uemail">
						<font color="red">(*)</font> E-Posta:
					</label>
					<input required name="uemail" type="text" value="<?php echo $uemail ?>" class="form-control">
				</div>
			</div>
		</div>
		<div class="row">

			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label for="upassword">
						<font color="red">(*)</font> Parola:
					</label>
					<input required name="upassword" type="text" class="form-control">
				</div>
			</div>

			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<label for="ugsm"> Telefon:</label>
					<input name="ugsm" type="text" value="<?php echo $ugsm ?>" class="form-control">
				</div>
			</div>

			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Pozisyon:
					</label>
					<select name="permission" class="selectpicker form-control" data-style="border bg-white">
						<?php
						$pquery = $ac->prepare("SELECT * FROM perms ");
						$pquery->execute();
						while ($pm = $pquery->fetch(PDO::FETCH_ASSOC)) {
							?>

							<option value="<?php echo $pm["id"]; ?>">
								<?php echo $pm["p_title"]; ?>
							</option>
						<?php } ?>

					</select>
				</div>


			</div>
		</div>
		<br>
	</form>


</div>
<!-- Input Validation End -->
