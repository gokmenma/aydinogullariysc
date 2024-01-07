<?php
define("MATROW", 10);
if (@$_GET["stx"] == "newreg") {
	showAlert("success", "Teklif başarıyla oluşturuldu. Döküman yüklemesini aşağıya sürükle-bırak yöntemi ile yapabilirsiniz.");
}

permcontrol("oedit");

if (!@$_GET["oid"]) {
	header("Location: index.php?p=all-offers");
	exit;
}

$cerq = $ac->prepare("SELECT * FROM offers WHERE id = ?");
$cerq->execute(array($_GET["oid"]));
$cc = $cerq->fetch(PDO::FETCH_ASSOC);

$ososx = $_GET["oid"];
if (!$cc) {
	header("Location: index.php?p=all-offers&err=01735");
	exit;
}
?>


<?php

define("MAXSX", set("max_sr"));


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


	$tot = 0;
	$prices = $_POST['price'];
	$amounts = $_POST['amount'];

	for ($i = 0; $i < count($prices); $i++) {
		$tektop = floatval($prices[$i]) * floatval($amounts[$i]);
		$tot += $tektop;
	}

	// $regxs = $ac->prepare("UPDATE offers SET
	//             cid = ?,
	//             total_price = ?,
	//             authors = ?,
	//             tax = ?,
	//             notes = ?,
	//             currency = ?,
	//             statu = ? WHERE id = ?");

	$regxs = $ac->prepare("UPDATE offers SET
				total_price = ? ,
				authors = ? ,
				tax = ? ,
				creativer = ? ,
				notes = ? ,
				currency = ? ,
				statu = ? WHERE id = ?");
	$regxs->execute(array($tot, $pps, $taxx, sesset("id"), $notesx, $cur, 0, $ososx));

	// $lastid = $ac->lastInsertId();

	$matters = $_POST['matter'];
	$units = $_POST['unit'];
	if (count($matters) > 0) {

		$delofm = $ac->prepare("DELETE FROM offermatters where oid = ? ");
		$delofm->execute(array($ososx));
	}

	for ($i = 0; $i < count($matters); $i++) {

		if (isset($matters[$i])) {
			$regmatter = $ac->prepare("INSERT INTO offermatters SET
													oid = ?,
													title = ?,
													unit = ?,
													amount = ?,
													price = ?,
													total = ?");

			$totals = floatval($prices[$i]) * floatval($amounts[$i]);

			$regmatter->execute(array( $ososx, $matters[$i], $units[$i], $amounts[$i], $prices[$i], $totals));
		}
	}

	// $regxs->execute(array($customerx, $tot, $pps, $taxx, $notesx, $_POST["cur"], 0, $ososx));
	// $lastid = $ac->lastInsertId();

	// $dg = 1;
	// while ($dg <= MATROW) {

	// 	if (trim($_POST["matter$dg"]) != "") {
	// 		$sellect = $ac->prepare("SELECT * FROM offermatters WHERE oid = ? AND xid = ?");
	// 		$sellect->execute(array($ososx, $dg));
	// 		if ($sellect->rowCount() > 0) {
	// 			$regmatter = $ac->prepare("UPDATE offermatters SET
	// title = ?,
	// unit = ?,
	// amount = ?,
	// price = ?,
	// total = ? WHERE oid = ? AND xid = ?");

	// 			$totals = $_POST["amount$dg"] * $_POST["price$dg"];

	// 			$regmatter->execute(array($_POST["matter$dg"], $_POST["unit$dg"], $_POST["amount$dg"], $_POST["price$dg"], $totals, $ososx, $dg));
	// 		} else {



	// 			$regmatter = $ac->prepare("INSERT INTO offermatters SET
	// xid = ?,
	// oid = ?,
	// title = ?,
	// unit = ?,
	// amount = ?,
	// price = ?,
	// total = ?");

	// 			$totals = $_POST["amount$dg"] * $_POST["price$dg"];

	// 			$regmatter->execute(array($dg, $ososx, $_POST["matter$dg"], $_POST["unit$dg"], $_POST["amount$dg"], $_POST["price$dg"], $totals));
	// 		}
	// 	}

	// 	$dg++;
	// }

	if ($regxs) {
		
		header("Location: index.php?p=edit-offer&type=fileupload&insert=update&ccs=083y3&oid=$ososx&stx=updreg");
		
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
}elseif(@$_GET["stx"]=="updreg"){
	showAlert("success","Teklif başarılı ile güncellendi!");
}


?>
<!-- Default Basic Forms Start -->
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Oluşturduğunuz teklif'in dökümanlarını sisteme upload etmeyi unutmayınız.<br></p>
		</div>

	</div>
	<form enctype="multipart/form-data" action="index.php?p=edit-offer&oid=<?= $ososx; ?>" method="POST">



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
						<option <?php echo $cscs["id"] == $cc["cid"] ? "selected" : ""; ?> value="<?php echo $cscs["id"]; ?>"><?php echo "#" . $cscs["id"] . " - " . $cscs["name"]; ?></option>
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
					<font color="red">(*)</font>Teklif Yetkilileri
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
							while ($px = $permx->fetch(PDO::FETCH_ASSOC)) {
								$autx = explode("|", $cc["authors"]);
								foreach ($autx as $au) {
									if ($au == $px["id"]) {
										$active = true;
									}
								}
							?>
								<option <?php echo @$active == true ? "selected" : ""; ?> value="<?php echo $px["id"]; ?>"><?php echo $px["username"]; ?></option>
							<?php } ?>
						</optgroup>
					<?php } ?>
				</select>
			</div>

		</div>



		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font>Teklif Para Birimi:
			</label>
			<div class="col-sm-12 col-md-4">
				<select name="cur" class="selectpicker" data-style="btn-outline-secondary">

					<option <?php echo $cc["currency"] == "tl" ? "selected" : ""; ?> value="tl">TL [Türk Lirası]</option>
					<option <?php echo $cc["currency"] == "dollar" ? "selected" : ""; ?> value="dollar">$ [Dolar]</option>
					<option <?php echo $cc["currency"] == "euro" ? "selected" : ""; ?> value="euro">€ [Euro]</option>


				</select>
			</div>
			<div class="col-md-2 col-sm-12">
				<font color="red">(*)</font><label class="weight-600">KDV</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<?php KdvOranları('tax',$cc["tax"]) ?>

			</div>
		</div>


		<div class="table-responsive">
			<table id="kalem_ekle" class="table table-bordered">
				<thead col-md-12>
					<tr>

						<p id="ekle"><input type="button" value="Kalem Ekle" class="float-right btn btn-warning mb-2"/>
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

				<tbody id="tBody">
					<tr>

						<?php

						$offerid = $_GET["oid"];
						$quer = $ac->prepare("SELECT * FROM offermatters WHERE oid = ? ");
						$quer->execute(array($offerid));
						//$qx = $quer->fetch(PDO::FETCH_ASSOC);

						$satirSay = 0;
						while ($qx = $quer->fetch(PDO::FETCH_ASSOC)) {
							$satirSay += 1; ?>
							<th class="col-md-1"><strong>Kalem <?php echo $satirSay ?></strong></th>



							<td class="col-md-4">
								<select required id="matter_" name="matter[]" class="form-control col-sm-12">
									<option value="" selected="">Seçiniz...</option>
									<?php
									$qcts = $ac->prepare("SELECT * FROM products ORDER BY id ASC");
									$qcts->execute();
									while ($product = $qcts->fetch(PDO::FETCH_ASSOC)) {
									?>
										<option <?php echo $product["Adi"] == $qx["title"] ? "selected" : ""; ?> value="<?php echo $product["Adi"]; ?>"><?php echo $product["Adi"]; ?></option>
									<?php }	?>
								</select>
							</td>

							<td class="col-md-2">
								<select required id="unit_" name="unit[]" class="form-control" title="Birim seçiniz">
									<option value="" selected="">Birim Seçiniz...</option>
									<?php
									$unq = $ac->prepare("SELECT * FROM units");
									$unq->execute();
									while ($uu = $unq->fetch(PDO::FETCH_ASSOC)) {
									?>
										<option <?php echo $uu["title"] == $qx["unit"]  ? "selected" : ""; ?> value="<?php echo $uu["title"]; ?>"><?php echo $uu["title"]; ?></option>
									<?php } ?>
								</select>
							</td>
							<td class="col-md-2"><input required id="amount_" value="<?php echo $qx["amount"] ?>" name="amount[]" type="text" class="form-control"></td>
							<td class="col-md-2"><input required id="price_" value="<?php echo $qx["price"] ?>" name="price[]" type="text" class="form-control"></td>
							<td class="col-md-1"><input type="button" id="satirSil" class="sil btn btn-danger" value="Sil"></button></td>
					</tr>
				<?php } ?>
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
				<input disabled class="form-control" value="<?php echo $cc["mycompany"]; ?>" type="text">
			</div>
		</div><br>

		<br>

		<div class="form-group row">
			<div class="col-md-2">
				<label>Notlar</label>
			</div>
			<div class="col-md-10">
				<textarea name="notesx" placeholder="Teklif hakkında bilgilendirici nitelikte not ekleyiniz." class="form-control"><?php echo $cc["notes"]; ?></textarea>
			</div>

		</div>
		<div class="form-group row col-md-12">
			<div class="col-md-12">
				<input id="submit-all" type="submit" data-toggle="tooltip" data-placement="top" title="Teklifte yapılan değişiklikleri kaydedin" value="Kaydet" class="col-md-10 float-right btn btn-success">
			</div>

		</div>
	</form>
</div>


<?php

if (@$_GET["type"] == "fileupload") {
	include("pages/1/fileuploadx.php");
}
?>
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

		$('#tBody').append(
			'<tr><th><strong>Kalem ' + (unitPrices() + 1) + '</strong></th>' +
			'<td>' + selectMatter + '</td>' +
			'<td>' + selectUnit + '</td>' +
			'<td><input required id="amount_' + sayac + '" name="amount[]' + '" type="text" class="form-control" /></td>' +
			'<td><input required id="price_' + sayac + '" name="price[]' + '" type="text" class="form-control" /></td>' +
			'<td><input type="button" id="satirSil_' + sayac + '" class="sil btn btn-danger" value="Sil"></button></td></tr>'
		);
	}

	// $(document).ready(function() {
	// 	$('#satirSil').click(function(e) {
	// 		e.preventDefault();
	// 		$(this).closest("tr").remove();
	// 	});
	// });

	$('#tBody').on("click", ".sil", function(e) { //user click on remove text
		e.preventDefault();
		var $tr = $(this).closest("tr");
		var index = $tr.index() + 1;

		$tr.remove();

		// Kalan satırları güncelle
		$('#tBody tr').each(function(i) {
			$(this).find('strong').text('Kalem ' + (i + 1));
		});

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

	function unitPrices() {
		var unitCount = $('select[name="unit[]"]').length;
		return unitCount;
	}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
	$(document).ready(function() {
		$('#countButton').click(function(e) {
			e.preventDefault(); // Düğmenin normal davranışını engeller

			var unitCount = unitPrices();
			console.log("Toplam " + unitCount + " adet 'unit[]' alanı var.");

			// Burada bu sayıyı başka bir yere yazdırabilir veya işleyebilirsiniz
		});
	});
</script>