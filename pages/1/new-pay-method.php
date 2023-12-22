<?php
permcontrol("pmadd");
define("MAXSX", set("max_sr"));


if ($_POST) {

	if (!$_POST["title"]) {
		header("Location: index.php?p=new-pay-method&st=empties");
		exit;
	}


	$title = @$_POST["title"];
	$iban = @$_POST["iban"];
	$hesapno = @$_POST["hesapno"];
	$hesapsahibi = @$_POST["hesapsahibi"];
	$subekodu = @$_POST["subekodu"];
	$cur = @$_POST["cur"];

	$reggs = $ac->prepare("INSERT INTO pay_methods SET
    title = ?,
    iban = ?,
    author = ?,
    accountno = ?,
    branchcode = ?,
    currency = ?,
    total = ?,
    last_action = ?");


	$reggs->execute(array($title, $iban, $hesapsahibi, $hesapno, $subekodu, $cur, 0, date("d-m-Y - H:i:s")));


	if ($reggs) {
		header("Location: index.php?p=new-pay-method&st=newsuccess");
	} else {
	}
}



if (@$_GET["st"] == "empties") {
	showAlert('alert', "(*) ile işaretli alanları boş bırakmadan tekrar deneyin."); ?>

<?php } elseif (@$_GET["st"] == "newsuccess") {
?>
	<?php showAlert('success', "Yeni Kayıt başarı ile oluşturuldu!"); ?>

<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "size") {
?>
	<?php showAlert('alert', 'Yüklediğiniz dosyaın boyutu <b>3 MB</b>\'dan daha büyük olamaz. Proje oluşturulamadı, tekrar deneyin.'); ?>

<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "erno") {
?>
	<?php showAlert('alert', "Proje oluşturuldu ancak, dosya yüklenirken bir problem yaşandı."); ?>

<?php
}


?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<!-- <div class="pd-ltr-20 xs-pd-20-10"> -->
	<div class="min-height-200px">

		<!-- Default Basic Forms Start -->
		<!-- <div class="pd-20 bg-white border-radius-4 box-shadow mb-30"> -->
		<div class="clearfix">
			<div class="pull-left">
				<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
				<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
			</div>
			<div class="form-group">
				<input type="submit" id="submitButton" value="Kaydet" class="btn btn-primary float-lg-right ">
			</div>
		</div>
		<form action="" method="POST" id="myForm">
			<div class="form-group row">
				<label class="col-sm-12 col-md-2 col-form-label">
					<font color="red">(*)</font> Başlık:
				</label>
				<div class="col-sm-12 col-md-10"><input required name="title" type="text" class="form-control">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-12 col-md-2 col-form-label">
					<font color="red">(*)</font> Kasa Para Birimi:
				</label>
				<div class="col-sm-12 col-md-10">

					<select required name="cur" class="selectpicker col-sm-2">

						<option selected value="tl">₺Türk Lirası</option>
						<option value="dollar">$Dolar</option>
						<option value="euro">€Euro</option>


					</select>
				</div>
			</div>

			<div class="form-group row">
				<label class="col-sm-12 col-md-2 col-form-label">IBAN:</label>
				<div class="col-sm-12 col-md-10"><input name="iban" type="text" class="form-control">
				</div>
			</div>

			<div class="form-group row">
				<label class="col-sm-12 col-md-2 col-form-label"> Hesap Sahibi:</label>
				<div class="col-sm-12 col-md-10"><input name="hesapsahibi" type="text" class="form-control">
				</div>
			</div>

			<div class="form-group row">
				<label class="col-sm-12 col-md-2 col-form-label">Hesap No:</label>
				<div class="col-sm-12 col-md-10"><input name="hesapno" type="text" class="form-control">
				</div>
			</div>

			<div class="form-group row">
				<label class="col-sm-12 col-md-2 col-form-label">Şube Kodu:</label>
				<div class="col-sm-12 col-md-10"><input name="subekodu" type="text" class="form-control">
				</div>
			</div>








			<!-- <div class="form-group">
							<input type="submit" value="Kaydet"class="form-control btn-outline-success">
						</div> -->
		</form>
		<!-- </div> -->


	</div>
</div>
</div>
<!-- Input Validation End -->

</div>
<script>
	document.getElementById("submitButton").addEventListener("click", function() {
		var form = document.getElementById("myForm");
		form.submit();
	});
</script>


<?php include('include/footer.php'); ?>