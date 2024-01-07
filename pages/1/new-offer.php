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
	$taxx = $_POST["tax"];
	$notesx = $_POST["notesx"];
	if ($taxx != 0 and $taxx != 1 and $taxx != 8 and $taxx != 18 and $taxx != 20) {
		header("Location: index.php");
		exit;
	}

	$pps = "";
	foreach ($_POST["permings"] as $psx) {
		$pps .= $psx . "|";
	}




	// $tdg = 1;
	// $tot = 0;
	// while ($tdg <= MATROW) {
	// 	$tektop = floatval($_POST["price$tdg"]) * floatval($_POST["amount$tdg"]);
	// 	$tot = $tot + $tektop;
	// 	$tdg++;
	// }
	
	$tot = 0;
	$prices = $_POST['price'];
	$amounts = $_POST['amount'];

	for ($i = 0; $i < count($prices); $i++) {
		$tektop = floatval($prices[$i]) * floatval($amounts[$i]);
		$tot += $tektop;
	}


	$regxs = $ac->prepare("INSERT INTO offers SET
	cid = ?,
	total_price = ? ,
	mycompany = ? ,
	authors = ? ,
	reg_date = ?,
	tax = ? ,
	creativer = ? ,
	notes = ? ,
	currency = ? ,
	statu = ?");

	$regxs->execute(array($customerx, $tot, $companyx, $pps, TODAY, $taxx, sesset("id"), $notesx, $cur,	0));
	$lastid = $ac->lastInsertId();


	$dg = 1;
	// while ($dg <= MATROW) {

	// 	if (isset($_POST["matter$dg"]) && $_POST["matter$dg"]) {
	// 		$regmatter = $ac->prepare("INSERT INTO offermatters SET
	// 												xid = ?,
	// 												oid = ?,
	// 												title = ?,
	// 												unit = ?,
	// 												amount = ?,
	// 												price = ?,
	// 												total = ?");

	// 		$totals = floatval($_POST["amount$dg"]) * floatval($_POST["price$dg"]);

	// 		$regmatter->execute(array($dg, $lastid, $_POST["matter$dg"], $_POST["unit$dg"], $_POST["amount$dg"], $_POST["price$dg"], $totals));
	// 	}
	// 	$dg++;
	// }

	$matters = $_POST['matter'];
	$units = $_POST['unit'];
	for ($i = 0; $i < count($matters); $i++) {

		if (isset($matters[$i])) {
			$regmatter = $ac->prepare("INSERT INTO offermatters SET
													xid = ?,
													oid = ?,
													title = ?,
													unit = ?,
													amount = ?,
													price = ?,
													total = ?");

			$totals = floatval($prices[$i]) * floatval($amounts[$i]);

			$regmatter->execute(array($dg, $lastid, $matters[$i], $units[$i], $amounts[$i], $prices[$i], $totals));
		}
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


<!-- Default Basic Forms Start -->
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"] ?></h4>


			<p class="mb-30 font-14">Oluşturduğunuz teklif'in excel dosyasını sisteme upload etmeyi unutmayınız.<br>Bu teklife özel olarak fiyatlarda değişiklik yapmak için, teklifi oluşturduktan sonra, teklif düzenleme sayfasına gidebilirsiniz.</p>
		</div>

	</div>
	<form enctype="multipart/form-data" id="myForm" action="index.php?p=new-offer" method="POST">

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font>Müşteri:
			</label>
			<div class="col-sm-12 col-md-10">
				<select required name="customers" id="customers" title="Seçiniz..." class="selectpicker form-control" data-style="btn-outline-primary">
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
				<select  required name="permings[]" data-selected-text-format="count > 3" class="selectpicker form-control show-menu-arrow" multiple data-actions-box="true" data-live-search="true" title="Yetkilileri Seçiniz!" data-style="btn-outline-secondary" multiple data-actions-box="true" data-selected-text-format="count">
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
						<p id="ekle"><a href="#" class="float-right btn btn-warning mb-2">&raquo; Kalem Ekle </a>
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
							<select required id="matter_1" name="matter[]" class="form-control col-sm-12">
								<option value="" selected="">Seçiniz...</option>
								<?php
								$qcts = $ac->prepare("SELECT * FROM products ORDER BY id ASC");
								$qcts->execute();
								while ($svs = $qcts->fetch(PDO::FETCH_ASSOC)) {
								?>
									<option value="<?php echo $svs["Adi"]; ?>"><?php echo $svs["Adi"]; ?></option>
								<?php }	?>
							</select>
						</td>

						<td class="col-md-2">
							<select required id="unit_1" name="unit[]" class="form-control col-sm-12">
								<option value="" selected disabled>Birim seçiniz</option>
								<?php
								$unq = $ac->prepare("SELECT * FROM units ");
								$unq->execute();
								while ($uu = $unq->fetch(PDO::FETCH_ASSOC)) {
								?>
									<option value="<?php echo $uu["title"]; ?>"><?php echo $uu["title"]; ?></option>
								<?php } ?>
							</select>
						</td>
						<td class="col-md-2"><input required id="amount_1" name="amount[]" type="text" class="form-control"></td>
						<td class="col-md-2"><input required id="price_1" name="price[]" type="text" class="form-control"></td>
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
				<input id="submit-all" type="submit" data-toggle="tooltip" data-placement="top" title="Teklifi Kaydedin ve dosya yükleme ekranına geçin" value="Teklifi Kayıt Et" class="col-md-10 float-right btn btn-success">
			</div>

		</div>
	</form>
</div>
<script type="text/javascript" src="src/js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="src/js/bootstrap.min.js"></script>



<!--buradan başlıyor-->
<?php

$qcts = $ac->prepare("SELECT * FROM products ORDER BY id ASC");
$qcts->execute();
$services =	$qcts->fetchAll(PDO::FETCH_ASSOC);
$jsonDataServices = json_encode($services);



$unq = $ac->prepare("SELECT * FROM units");
$unq->execute();
$units = $unq->fetchAll(PDO::FETCH_ASSOC);

// Diziyi JSON formatına dönüştürün
$jsonDataUnits = json_encode($units);

?>
<script type="text/javascript">
	$(function() {
		$('#ekle').click(function() {

			var jsonDataServices = <?php echo $jsonDataServices; ?>;
			var jsonDataUnits = <?php echo $jsonDataUnits; ?>; // JSON verilerini JavaScript değişkenine atama
			fillSelectOptions(jsonDataServices, jsonDataUnits);

		});
	});
	var sayac = 1;

	function fillSelectOptions(services, units) {
		sayac += 1;
		var selectMatter = '<select required id="matter_' + sayac + '" name="matter[]" class="form-control">';
		var selectUnit = '<select required id="unit_' + sayac + '" name="unit[]" class="form-control">';

		selectMatter += '<option value="" selected disabled>Seçiniz...</option>';
		selectUnit += '<option value="" selected disabled>Birim seçiniz</option>';

		for (var i = 0; i < services.length; i++) {
			selectMatter += '<option value="' + services[i].Adi + '">' + services[i].Adi + '</option>';

		}

		for (var i = 0; i < units.length; i++) {
			selectUnit += '<option value="' + units[i].title + '">' + units[i].title + '</option>';
		}

		selectMatter += '</select>';
		selectUnit += '</select>';

		$('#kalem_ekle tbody').append(
			'<tr><th><strong>Kalem ' + sayac + '</strong></th>' +
			'<td>' + selectMatter + '</td>' +
			'<td>' + selectUnit + '</td>' +
			'<td><input required id="amount_' + sayac + '" name="amount[]' + '" type="text" class="form-control" /></td>' +
			'<td><input required id="price_' + sayac + '" name="price[]' + '" type="text" class="form-control" /></td>' +
			'<td><a href="#" class="sil btn btn-danger">Sil</a></td></tr>'
		);
	}
	$('#kalem_ekle').on("click", ".sil", function(e) { //user click on remove text
		e.preventDefault();
		$(this).closest("tr").remove();

	})

	document.getElementById('myForm').addEventListener('submit', function(event) {
		event.preventDefault(); // Form submit olayını durdur

		var customerIDSelect = document.getElementById('customers');
		var customerIDValue = customerIDSelect.value; // Seçilen değeri al

		// Boş değer kontrolü yap
		if (customerIDValue === '' || customerIDValue === null) {
			showMessage("Customer boş olamaz!", "alert");
		} else {
			// Seçilen değer boş değilse formu submit et
			 this.submit();
		}
	});

</script>