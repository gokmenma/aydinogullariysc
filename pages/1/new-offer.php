<?php

permcontrol("oadd");
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define("MATROW", 10);

?>


<style>
	.dropzone {
		border: 2px dashed #444;
		background-color: #f1f1f1;
		font-size: 23px;
		font-style: italic;
		font-family: Arial;
		text-align: center;
	}
</style>
<?php


if ($_POST) {




	if (!$_POST["customers"] ||  !$_POST["permings"]) {
		header("Location: index.php?p=new-offer&st=empties");
		exit;
	}



	$cur = $_POST["cur"];
	$customerx = $_POST["customers"];
	$craetiverx = sesset("id");

	$companyx = set("company_name");
	$taxx = @$_POST["tax"];
	if ($taxx != 0 and $taxx != 1 and $taxx != 8 and $taxx != 18) {
		header("Location: index.php");
		exit;
	}

	$pps = "";
	foreach ($_POST["permings"] as $psx) {
		$pps .= $psx . "|";
	}


	$notesx = $_POST["notesx"];

	$tdg = 1;
	$tot = 0;
	while ($tdg <= MATROW) {
		$tektop = floatval($_POST["price$tdg"]) * floatval($_POST["amount$tdg"]);
		$tot = $tot + $tektop;
		$tdg++;
	}



	$regxs = $ac->prepare("INSERT INTO offers SET
                cid = ?,
                total_price = ?,
                mycompany = ?,
                authors = ?,
                reg_date = ?,
                tax = ?,
                creativer = ?,
                notes = ?,
                currency = ?,
                statu = ?");

	$regxs->execute(array($customerx, $tot, $companyx, $pps, TODAY, $taxx, sesset("id"), $notesx, $cur,	0));
	$lastid = $ac->lastInsertId();


	$dg = 1;
	while ($dg <= MATROW) {

		if (isset($_POST["matter$dg"]) && $_POST["matter$dg"]) {
			$regmatter = $ac->prepare("INSERT INTO offermatters SET
	xid = ?,
	oid = ?,
	title = ?,
	unit = ?,
	amount = ?,
	price = ?,
	total = ?");

			$totals = floatval($_POST["amount$dg"]) * floatval($_POST["price$dg"]);

			$regmatter->execute(array($dg, $lastid, $_POST["matter$dg"], $_POST["unit$dg"], $_POST["amount$dg"], $_POST["price$dg"], $totals));
		}
		$dg++;
	}

	if ($regxs) {
		header("Location: index.php?p=edit-offer&type=fileupload&insert=new&ccs=083y3&oid=$lastid&stx=newreg");
	} else {
		header("Location: index.php?p=all-offers&st=newerror&code=acmd008");
	}
}


if (@$_GET["st"] == "empties") {
?>
	<div class="alert alert-danger" role="alert">
		(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
	</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "name") {
?>
	<div class="alert alert-warning" role="alert">
		Aynı adda bir excel dosyası bulunuyor, lütfen ismini değiştirerek teklifi tekrar oluşturmayı deneyin.
	</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "size") {
?>
	<div class="alert alert-warning" role="alert">
		Yüklediğiniz dosyaın boyutu <b>10 MB</b>'dan daha büyük olamaz. Teklif oluşturulamadı, tekrar deneyin.
	</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "erno") {
?>
	<div class="alert alert-danger" role="alert">
		Teklif oluşturuldu ancak, dosya yüklenirken bir problem yaşandı.
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


					<p class="mb-30 font-14">Oluşturduğunuz teklif'in excel dosyasını sisteme upload etmeyi unutmayınız.<br>Bu teklife özel olarak fiyatlarda değişiklik yapmak için, teklifi oluşturduktan sonra, teklif düzenleme sayfasına gidebilirsiniz.</p>
				</div>

			</div>
			<form enctype="multipart/form-data" action="index.php?p=new-offer" method="POST">

				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">
						<font color="red">(*)</font>Müşteri:
					</label>
					<div class="col-sm-12 col-md-10">
						<select name="customers" class="selectpicker form-control ">
							<option value="0" disabled selected="">Seçiniz...</option>
							<?php
							$qct = $ac->prepare("SELECT * FROM customers ORDER BY id DESC");
							$qct->execute();
							while ($cscs = $qct->fetch(PDO::FETCH_ASSOC)) {
							?>
								<option value="<?php echo $cscs["id"]; ?>"><?php echo "#" . $cscs["id"] . " - " . $cscs["name"]; ?></option>
							<?php
							}
							?>
						</select>
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
				<div class="form-group row">
					<label style="font-weight: bold" class="col-sm-12 col-md-2 col-form-label">Teklif Para Birimi:</label>
					<div class="col-sm-12 col-md-10">

						<select name="cur" class="selectpicker col-sm-3">

							<option selected value="tl">TL [Türk Lirası]</option>
							<option value="dollar">$ [Dolar]</option>
							<option value="euro">€ [Euro]</option>


						</select>
					</div>
				</div><br>
				<?php


				$MT = 1;
				while ($MT <= MATROW) {



				?>

					<div id="mattersx" class="form-group row">
						<label class="col-sm-12 col-md-2 col-form-label">
							<font color="red"></font>Kalem <?php echo $MT; ?>:
						</label>
						<div class="col-sm-12 col-md-10">
							<select name="matter<?php echo $MT; ?>" class="selectpicker col-sm-3">
								<option value="" selected="">Seçiniz...</option>
								<?php
								$qcts = $ac->prepare("SELECT * FROM services ORDER BY mid ASC");
								$qcts->execute();
								while ($svs = $qcts->fetch(PDO::FETCH_ASSOC)) {

								?>

									<option value="<?php echo $svs["stitle"]; ?>"><?php echo $svs["stitle"]; ?>

									</option>

								<?php

								}
								?>
							</select>
							<select name="unit<?php echo $MT; ?>" class="selectpicker col-sm-3">
								<option value="0" selected disabled>Birim seçiniz</option>
								<?php
								$unq = $ac->prepare("SELECT * FROM units ");
								$unq->execute();
								while ($uu = $unq->fetch(PDO::FETCH_ASSOC)) {
								?>
									<option value="<?php echo $uu["title"]; ?>"><?php echo $uu["title"]; ?></option>
								<?php } ?>
							</select>Miktar : <input name="amount<?php echo $MT; ?>" class="selectpicker col-sm-1"></input>&nbsp;&nbsp;B. Fiyatı: <input name="price<?php echo $MT; ?>" class="selectpicker col-sm-2"></input>

						</div>
					</div>

				<?php
					$MT++;
				} ?>




				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">
						<font color="red">(*)</font>Şirket:
					</label>
					<div class="col-sm-12 col-md-10">
						<input disabled class="form-control" value="<?php echo set("company_name"); ?>" type="email">
					</div>
				</div><br>
				<div class="col-md-6 col-sm-12">
					<font color="red">(*)</font><label class="weight-600">KDV</label>
					<select name="tax" class="form-control ">
						<option value="0">0%</option>
						<option value="1">1%</option>
						<option value="8">8%</option>
						<option value="18">18%</option>
					</select>
				</div>
				<br>

				<div class="form-group">
					<label>Notlar</label>
					<textarea name="notesx" placeholder="Teklif hakkında bilgilendirici nitelikte not ekleyiniz." class="form-control"></textarea>
				</div>

				<div class="form-group row col-md-4 col-sm-12">
					<div class="form-group">
						<font color="red">(*)</font><label>Teklif Yetkilileri</label>
						<select name="permings[]" class="selectpicker form-control" data-style="btn-outline-secondary" multiple data-actions-box="true" data-selected-text-format="count">
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



				<div class="form-group">
					<input id="submit-all" type="submit" value="Teklifi Kayıt Et" class="form-control btn-outline-secondary">
				</div>
			</form>




		</div>
	</div>
</div>
<!-- Input Validation End -->

</div>
<?php include('include/footer.php'); ?>