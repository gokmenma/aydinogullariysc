<?php

permcontrol("oadd");
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define("MATROW", 1);

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
						<select name="customers" class="form-control ">
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
					<div class="col-sm-12 col-md-2">
						<label class="col-form-label">
							<font color="red">(*)</font>Hazırlayan
						</label>
					</div>
					<div class="col-sm-12 col-md-4">
						<input disabled class="form-control" value="<?php echo sesset("username"); ?>" type="text">
					</div>


					<div class="col-sm-12 col-md-2">
						<label class="col-form-label">
							<font color="red">(*)</font><label>Teklif Yetkilileri</label>
						</label>
					</div>
					<div class="col-sm-12 col-md-4">
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
				<div class="form-group row">
					<div class="col-sm-12 col-md-2">
						<label style="font-weight: bold">Teklif Para Birimi:</label>
					</div>
					<div class="col-sm-12 col-md-4">
						<?php ParaBirimleri('cur') ?>

					</div>
					<div class="col-md-2 col-sm-12">
						<font color="red">(*)</font><label class="weight-600">KDV</label>
					</div>
					<div class="col-md-4 col-sm-12">
						<?php KdvOranları('tax') ?>

					</div>
				</div><br>


				<div class="table-responsive">
					<table id="kalem_ekle" class="table table-bordered">
						<thead col-md-12>
							<tr>
								<p id="ekle"><a href="#" class="float-right btn btn-primary mb-2">&raquo; Kalem Ekle </a>
							</tr>

							<tr>
								<th class="col-md-1"><strong>Kalem</strong></th>
								<th class="col-md-4">Ürün/Malzeme</th>
								<th class="col-md-2">Birim</th>
								<th class="col-md-2">Miktar</th>
								<th class="col-md-2">Adet</th>
								<th class="col-md-2">İşlem</th>
							</tr>
						</thead>

						<tbody>
							<tr>
								<!-- tablonun body kısmında varsayılan olarak 1. Dersi ekliyoruz -->
								<!-- metin kutularını dizi olarak ekliyoruz (alanlar[]). -->
								<th class="col-md-1"><strong>Kalem 1</strong></th>
								<td class="col-md-4">
									<select id="matter_" name="matter[]" class="form-control col-sm-12">
										<option value="" selected="">Seçiniz...</option>
										<?php
										$qcts = $ac->prepare("SELECT * FROM services ORDER BY id ASC");
										$qcts->execute();
										while ($svs = $qcts->fetch(PDO::FETCH_ASSOC)) {
										?>
											<option value="<?php echo $svs["Firma"]; ?>"><?php echo $svs["Firma"]; ?></option>
										<?php }	?>
									</select>
								</td>

								<td class="col-md-2">
									<select id="unit_" name="unit[]" class="form-control col-sm-12">
										<option value="0" selected disabled>Birim seçiniz</option>
										<?php
										$unq = $ac->prepare("SELECT * FROM units ");
										$unq->execute();
										while ($uu = $unq->fetch(PDO::FETCH_ASSOC)) {
										?>
											<option value="<?php echo $uu["title"]; ?>"><?php echo $uu["title"]; ?></option>
										<?php } ?>
									</select>
								</td>
								<td class="col-md-2"><input id="amount_" name="amount[]" type="text" class="form-control"></td>
								<td class="col-md-2"><input id="price_" name="price[]" type="text" class="form-control"></td>
								<td class="col-md-1"></td>
							</tr>
						</tbody>
					</table>
				</div>
<hr>
<br>

				<div class="form-group row">
					<label class="col-sm-12 col-md-2 col-form-label">
						<font color="red">(*)</font>Şirket:
					</label>
					<div class="col-sm-12 col-md-10">
						<input disabled class="form-control" value="<?php echo set("company_name"); ?>" type="text">
					</div>
				</div><br>

				<br>

				<div class="form-group row">
					<div class="col-md-2">
						<label>Notlar</label>
					</div>
					<div class="col-md-10">
						<textarea name="notesx" placeholder="Teklif hakkında bilgilendirici nitelikte not ekleyiniz." class="form-control"></textarea>
					</div>

				</div>




				<div class="form-group row col-md-12">
					<div class="col-md-12">
						<input id="submit-all" type="submit" value="Teklifi Kayıt Et" class="col-md-10 float-right btn btn-outline-secondary">
					</div>

				</div>
			</form>

			<script type="text/javascript" src="src/js/jquery-3.1.1.min.js"></script>
			<script type="text/javascript" src="src/js/bootstrap.min.js"></script>




			<script type="text/javascript">
				//ekle bağlantısına tıklandığında çalışacak jquery kodlarımız
				//burada table ın tbody kısmına satır (tr) ekleme yöntemi ile ders için input ekliyoruz.
				var sayac = 1; //kaçıncı ders bilgisini tutuyoruz
				$(function() {
					$('#ekle').click(function() {
						sayac += 1;
						$('#kalem_ekle tbody').append(
							'<tr><th><strong>Kalem ' + sayac + '</strong></th>' +
							'<td><select id="matter_' + sayac + '" name=matter[] class="form-control" >' +
							'<option value= "" selected="">Seçiniz... </option>' +
							' </select></td>' +
							'<td><select id="unit_' + sayac + '" name="unit[]' + '"  class="form-control" >' +
							'<option value= "" selected="">Birim Seçiniz... </option>' +
							' </select></td>' +
							'<td><input id="amount_' + sayac + '" name="amount[]' + '" type="text" class="form-control" /></td>' +
							'<td><input id="price_' + sayac + '" name="price[]' + '" type="text" class="form-control" /></td>' +
							'<td><a href="#" class="sil btn btn-danger">Sil</a></td></tr>');
					});
					//sil bağlantısına tıklanınca çalışacak jquery kodumuz
					//sil tıklandığında tablodaki bulunduğu tr yi siliyoruz
					$('#kalem_ekle').on("click", ".sil", function(e) { //user click on remove text
						e.preventDefault();
						$(this).closest("tr").remove();

					})
				});
			</script>


		</div>
	</div>
</div>
<!-- Input Validation End -->
