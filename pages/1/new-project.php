<?php
permcontrol("padd");
define("MAXSX", set("max_sr"));

if ($_POST) {

	if (!$_POST["ptitle"] || !$_POST["pdesc"] || !$_POST["offerno"] || !$_POST["pstartdate"] || !$_POST["pfinaldate"] || !$_POST["permings"]) {
		header("Location: index.php?p=new-project&st=empties");
		exit;
	}

	$ost = $ac->prepare("SELECT * FROM offers WHERE id = ?");
	$ost->execute(array($_POST["offerno"]));
	$oms = $ost->fetch(PDO::FETCH_ASSOC);

	$mstm = $ac->prepare("SELECT * FROM customers WHERE id = ?");
	$mstm->execute(array($oms["cid"]));
	$mxm = $mstm->fetch(PDO::FETCH_ASSOC);



	$customerx = $oms["cid"];
	$creativerx = sesset("id");
	$companyx = $oms["mycompany"];
	$ptitle = $_POST["ptitle"];
	$pdesc = $_POST["pdesc"];
	$offerno = $_POST["offerno"];
	$pregdates = TODAY;
	$pstartdate = date_tr($_POST["pstartdate"]);
	$pfinaldate = date_tr($_POST["pfinaldate"]);
	$pnote = addslashes(@$_POST["pnotes"]);

	$pps = "";
	foreach ($_POST["permings"] as $psx) {
		$pps .= $psx . "|";
	}



	$regxs = $ac->prepare("INSERT INTO projects SET
                pcid = ?,
                poid = ?,
                pcreativer = ?,
                ptitle = ?,
                pdesc = ?,
                preg_date = ?,
                pstart_date = ?,
                pfinal_date = ?,
                pauthors = ?,
                pnotes = ?,
                pstatu = ?");

	$regxs->execute(array($customerx, $offerno, $creativerx, $ptitle, $pdesc, $pregdates, $pstartdate, $pfinaldate, $pps, $pnote, 0));
	// application/vnd.ms-excel
	// application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
	if ($regxs) {

		$upof = $ac->prepare("UPDATE offers SET statu = ? WHERE id = ?");
		$upof->execute(array(3, $offerno));

		header("Location:index.php?p=all-projects&st=newsuccess");
	} else {
		header("Location: index.php?p=all-offers&st=newerror&code=acmd008");
	}
}


if (@$_GET["st"] == "empties") {
	showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "name") {
?>
	<div class="alert alert-warning" role="alert">
		Aynı adda bir dosya bulunuyor, lütfen ismini değiştirerek projeyi tekrar oluşturmayı deneyin.
	</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "size") {
?>
	<div class="alert alert-warning" role="alert">
		Yüklediğiniz dosyaın boyutu <b>3 MB</b>'dan daha büyük olamaz. Proje oluşturulamadı, tekrar deneyin.
	</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "erno") {
?>
	<div class="alert alert-danger" role="alert">
		Proje oluşturuldu ancak, dosya yüklenirken bir problem yaşandı.
	</div>
<?php
}


?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<!-- <div class="min-height-200px"> -->

	<!-- Default Basic Forms Start -->
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Proje oluşturmadan önce projenin teklifini hazırlamanız gerekir.
				<br><b>Yalnızca onaylanan teklifler</b> için proje oluşturabilirsiniz.
				<br>Hazırladığınız <b>teklif numarasını not almayı</b>, bu sayfada kullanmanız gerektiğini unutmayın.
			</p>
		</div>

		<div class="form-group">
			<input type="submit" id="submitButton" value="Kaydet" class="btn btn-primary float-lg-right ">
		</div>
	</div>

	<form enctype="multipart/form-data" id="myForm" action="" method="POST">

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font>Proje Başlığı:
			</label>
			<div class="col-sm-12 col-md-10"><input name="ptitle" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group">
			<label>
				<font color="red">(*)</font>Proje Açıklaması
			</label>
			<textarea name="pdesc" placeholder="Proje hakkında bilgilendirici nitelikte kısa bir açıklama ekleyiniz." class="form-control"></textarea>
		</div>

		<div class="row">
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>Çalışma Başlangıç Tarihi</label>
					<input name="pstartdate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">
				</div>
			</div>

			<div class="col-md-6 col-sm-12">
				<div class="form-group">

					<label>Planlanan Bitiş Tarihi</label>
					<input required name="pfinaldate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font>Hazırlayan
			</label>
			<div class="col-sm-12 col-md-10">
				<input disabled class="form-control" value="<?php echo sesset("username"); ?>" type="text">
			</div>
		</div>

		<!-- <div class="col-md-6 col-sm-12">
			<label>
				<font color="red">(*)</font>Çalışma Başlangıç Tarihi
			</label>
			<input name="pstartdate" class="form-control date-picker" placeholder="Tarih Seçin" type="text">
		</div>
		<div class="col-md-6 col-sm-12">
			<label>
				<font color="red">(*)</font>Planlanan Bitiş Tarihi
			</label>
			<input name="pfinaldate" class="form-control date-picker" placeholder="Tarih Seçin" type="text">
		</div> -->






		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font>Şirket:
			</label>
			<div class="col-sm-12 col-md-10">
				<input disabled class="form-control" value="<?php echo set("company_name"); ?>" type="email">
			</div>
		</div><br>
		<br>

		<div class="row">
			<div class="form-group col-md-6 col-sm-12">
				<div class="form-group">
					<font color="red">(*)</font><label>Onaylanmış Teklif Numarası:</label>
					<select name="offerno" class="selectpicker form-control" data-style="btn-outline-secondary" data-max-options="3">
						<?php
						$permq = $ac->prepare("SELECT * FROM offers WHERE statu =? ");
						$permq->execute(array(2));
						while ($pp = $permq->fetch(PDO::FETCH_ASSOC)) {
							$mst = $ac->prepare("SELECT * FROM customers WHERE id = ?");
							$mst->execute(array($pp["cid"]));
							$mms = $mst->fetch(PDO::FETCH_ASSOC);
						?>

							<option <?php echo $pp["id"] == @$_GET["oid"] ? "selected" : ""; ?> value="<?php echo $pp["id"]; ?>"><?php echo "#" . $pp["id"] . " --> " . $mms["name"]; ?></option>


						<?php } ?>
					</select>
				</div>
			</div>


			<div class="form-group col-md-6 col-sm-12">
				<div class="form-group">
					<font color="red">(*)</font><label>Proje Yetkilileri</label>
					<select name="permings[]" class="selectpicker form-control" data-style="btn-outline-secondary" multiple data-max-options="3">
						<?php
						$permq = $ac->prepare("SELECT * FROM perms ");
						$permq->execute();
						while ($pp = $permq->fetch(PDO::FETCH_ASSOC)) {
						?>
							<optgroup label="<?php echo $pp["p_title"]; ?>">
								<?php
								$permx = $ac->prepare("SELECT * FROM users WHERE permission = ? ");
								$permx->execute(array($pp["id"]));
								while ($px = $permx->fetch(PDO::FETCH_ASSOC)) { ?>
									<option value="<?php echo $px["id"]; ?>"><?php echo $px["username"]; ?></option>
								<?php } ?>
							</optgroup>
						<?php } ?>
					</select>
				</div>
			</div>
		</div>

		<div class="pd-ltr-20 xs-pd-20-10">


			<div class="html-editor  mb-30">
				<h3 class="weight-500">Projenin Son Durumu</h3>
				<p>Projenin son durumu hakkında bir mesaj bırakınız. Çalışma süresince bu not yetkililer tarafından güncellenebilir.</p>
				<textarea name="pnotes" class="textarea_editor form-control border-radius-0" placeholder="Çalışmaya yeni başlanıldı..."></textarea>
				<div class="form-group"><br>

				</div>
			</div>
		</div>

	</form>
</div>
<!-- Input Validation End -->

<script>
	document.getElementById("submitButton").addEventListener("click", function() {
		var form = document.getElementById("myForm");
		form.submit();
	});
</script>
<?php include('include/footer.php'); ?>