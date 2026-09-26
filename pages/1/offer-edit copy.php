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



	if (!$_POST["customers"] || !$_POST["permings"]) {
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
 
	$usdValue =$tot /  $_POST["usdValue"]  ;
	$euroValue =$tot  / $_POST["euroValue"] ;
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
				statu = ? ,
				dollar = ? ,
				euro = ?  WHERE id = ?");
	$regxs->execute(array($tot, $pps, $taxx, sesset("id"), $notesx, $cur, 0, $usdValue,$euroValue,$ososx));

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

			$regmatter->execute(array($ososx, $matters[$i], $units[$i], $amounts[$i], $prices[$i], $totals));
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

	// if ($regxs) {

	// 	header("Location: index.php?p=edit-offer&type=fileupload&insert=update&ccs=083y3&oid=$ososx&stx=updreg");

	// }


	
	$filedesc = $_POST['filedesc'];
	$filecategory = $_POST['fileCategory'];



	if($filedesc > 0){
		$query=$ac->prepare("DELETE FROM files WHERE oid = ?");
		$query->execute(array($ososx));

	}

	for ($i = 0; $i < count($filedesc); $i++) {
		$isTextType = isset($_POST['filename']) && $_POST['filename'] === 'true';

		if ($isTextType) {
			// Metin tipinde ise POST ile değeri al
			$filename = $_POST['filename'][$i];
		} else {
			// Dosya seçicide ise $_FILES ile değeri al
			$filename = $_FILES['filename']['name'][$i];
		}

		if ($filename) {
			$regdate = date('Y-m-d H:i:s'); // Varsayılan olarak şu anki tarih ve saat

			$insq = $ac->prepare("INSERT INTO files SET oid = ? , filename = ? , regdate = ? , filedesc = ? , filecategory = ? ");
			$query = $insq->execute(array($ososx, $filename, $regdate, $filedesc[$i] , $filecategory[$i]));

			if ($query) {
				$uploadDir = 'files/offer/'; // Değiştirilmesi gereken dizin
				$uploadPath = $uploadDir . basename($filename);

				// Dosyayı belirtilen dizine taşı
				if (move_uploaded_file($_FILES['filename']['tmp_name'][$i], $uploadPath)) {
					echo 'Dosya başarıyla yüklendi ve kaydedildi.';
				} else {
					echo 'Dosya taşıma hatası.';
				}
			} else {
				echo 'Veritabanına ekleme hatası.';
			}
		}
	}
	//<----Teklife ait dosyalar varsa kaydedilir---->
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
} elseif (@$_GET["stx"] == "updreg") {
	showAlert("success", "Teklif başarılı ile güncellendi!");
}


?>
<form enctype="multipart/form-data" action="index.php?p=edit-offer&oid=<?= $ososx; ?>" method="POST">
	<!-- Default Basic Forms Start -->
	<div class="content pd-20 bg-white border-radius-16 box-shadow mb-20">
		<div class="clearfix">
			<div class="pull-left">
				<h4 class="text-blue">
					<?php echo $pdat["p_title"]; ?>
				</h4>
				<p class="mb-30 font-14">Oluşturduğunuz teklif'in dökümanlarını sisteme upload etmeyi unutmayınız.<br>
				</p>
			</div>

		</div>




		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font>Müşteri:
			</label>
			<div class="col-sm-12 col-md-10">
				<select required name="customers" id="customers" title="Seçiniz..." class="selectpicker form-control"
					data-style="btn-outline-primary">
					<?php
					$qct = $ac->prepare("SELECT * FROM customers ORDER BY id DESC");
					$qct->execute();
					while ($cscs = $qct->fetch(PDO::FETCH_ASSOC)) {
						?>
						<option <?php echo $cscs["id"] == $cc["cid"] ? "selected" : ""; ?> value="<?php echo $cscs["id"]; ?>">
							<?php echo "#" . $cscs["id"] . " - " . $cscs["name"]; ?>
						</option>
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

			<div class="col-md-2 col-sm-12">
				<font color="red">(*)</font><label class="weight-600">KDV</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<?php KdvOranları('tax', $cc["tax"]) ?>

			</div>

		</div>



		<div class="form-group row">

			<div class="col-sm-12 col-md-2">
				<label class="col-form-label">
					<font color="red">(*)</font>Teklif Yetkilileri
				</label>
			</div>
			<div class="col-sm-12 col-md-4">
				<select name="permings[]" class="selectpicker form-control" data-style="btn-outline-primary" multiple
					data-actions-box="true" data-live-search="true" data-selected-text-format="count">
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
								<option <?php echo @$active == true ? "selected" : ""; ?> value="<?php echo $px["id"]; ?>">
									<?php echo $px["username"]; ?>
								</option>
							<?php } ?>
						</optgroup>
					<?php } ?>
				</select>
			</div>
			<!-- iskonto -->

			<div class="col-md-2 col-sm-12">
				<font color="red">(*)</font><label class="weight-600">İskonto</label>
			</div>
			<div class="col-md-2 col-sm-12">
				<input class="form-control" value="" type="text">

			</div>

		</div>


		<div class="form-group row">
			<div class="col-md-2 col-sm-12">

				<label class="col-form-label">
					<font color="red">(*)</font>Teklif Para Birimi:
				</label>
			</div>
			<div class="col-md-4 col-sm-12">

				<select name="cur" id="cur" class="selectpicker" data-style="btn-outline-secondary">

					<option <?php echo $cc["currency"] == "tl" ? "selected" : ""; ?> value="tl">TL [Türk Lirası]
					</option>
					<option <?php echo $cc["currency"] == "dollar" ? "selected" : ""; ?> value="dollar">$ [Dolar]
					</option>
					<option <?php echo $cc["currency"] == "euro" ? "selected" : ""; ?> value="euro">€ [Euro]</option>


				</select>


				<hr>

				<!--doviz kurları buraya-->
				<?php include "doviz-kuru.php";?>
				<!--doviz kurları buraya-->

			</div>

		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font>Şirket:
			</label>
			<div class="col-sm-12 col-md-10">
				<input disabled class="form-control" value="<?php echo $cc["mycompany"]; ?>" type="text">
			</div>
		</div>

		<div class="form-group row">
			<div class="col-md-2">
				<label>Notlar</label>
			</div>
			<div class="col-md-10">
				<textarea name="notesx" placeholder="Teklif hakkında bilgilendirici nitelikte not ekleyiniz."
					class="form-control"><?php echo $cc["notes"]; ?></textarea>
			</div>

		</div>
	</div>
	<div class="content pd-20 bg-white border-radius-16 box-shadow mb-20">
		<div class="table-responsive">
			<table id="kalem_ekle" class="table table-bordered">
				<div class="row margin-5 pd-10 justify-content-between">

					<h4 class="text-blue">
						Teklif Kalemleri</h4>
					<input id="ekle" type="button" class="btn btn-warning float-right mb-2" value="Kalem Ekle">

					</input>
				</div>
				<thead col-md-12>
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
							<th class="col-md-1"><strong>Kalem
									<?php echo $satirSay ?>
								</strong></th>



							<td class="col-md-4">
								<select required id="matter_" name="matter[]" class="form-control col-sm-12">
									<option value="" selected="">Seçiniz...</option>
									<?php
									$qcts = $ac->prepare("SELECT * FROM products ORDER BY id ASC");
									$qcts->execute();
									while ($product = $qcts->fetch(PDO::FETCH_ASSOC)) {
										?>
										<option <?php echo $product["Adi"] == $qx["title"] ? "selected" : ""; ?>
											value="<?php echo $product["Adi"]; ?>">
											<?php echo $product["Adi"]; ?>
										</option>
									<?php } ?>
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
										<option <?php echo $uu["title"] == $qx["unit"] ? "selected" : ""; ?>
											value="<?php echo $uu["title"]; ?>">
											<?php echo $uu["title"]; ?>
										</option>
									<?php } ?>
								</select>
							</td>
							<td class="col-md-2"><input required id="amount_" value="<?php echo $qx["amount"] ?>"
									name="amount[]" type="text" class="form-control"></td>
							<td class="col-md-2"><input required id="price_" value="<?php echo $qx["price"] ?>"
									name="price[]" type="text" class="form-control"></td>
							<td class="col-md-1"><input type="button" id="satirSil" class="sil btn btn-danger float-right"
									value="Sil"></button></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<br>
	</div>


	<div class="content pd-20 bg-white border-radius-16 box-shadow mb-20">
		<div class="table-responsive">
			<table id="offerfiles" class="table table-bordered">
				<div class="row margin-5 pd-10 justify-content-between">

					<h4 class="text-blue">
						Teklif Dosyaları</h4>
					<input id="fileAdd" type="button" class="btn btn-primary float-right mb-2" value="Dosya Ekle">
					</input>
				</div>
				<thead col-md-12>
					<tr>

					</tr>

					<tr>
						<th><strong>Sıra No</strong></th>
						<th>Açıklama</th>
						<th>Dosya Türü</th>
						<th>Yüklenme Tarihi</th>
						<th>Dosya</th>

						<th>İşlem</th>
					</tr>
				</thead>

				<tbody>
					<tr>
						<?php

						$offerid = $_GET["oid"];
						$quer = $ac->prepare("SELECT * FROM files WHERE oid = ? ");
						$quer->execute(array($offerid));
						//$qx = $quer->fetch(PDO::FETCH_ASSOC);
						
						$satirSay = 0;
						while ($qx = $quer->fetch(PDO::FETCH_ASSOC)) {
							$satirSay += 1; ?>
							<th class="col-md-1"><strong>
									<?php echo $satirSay ?>
								</strong></th>
							<td class="col-md-3">
								<input required type="text" class="form-control" name="filedesc[]" value="<?php echo $qx["filedesc"]; ?>">

							</td>
							<td class="col-md-2">
								<input required type="text" class="form-control" name="fileCategory" value="<?php echo $qx["filecategory"]; ?>">

							</td>
							<td class="col-md-2">
								<input required type="text" class="form-control date-picker" name="regdate" value="<?php echo $qx["regdate"]; ?>">

							</td>
							<td class="col-md-2">
								<input required type="text" class="form-control" name="filename[]"
									value="<?php echo $qx["filename"]; ?>">

							</td>
							<td class="col-md-2 justify-content-between ">
								<div class="float-right">


									<button type="button" class="btn btn-light"><i
											class="fa fa-file pd-4 mr-1"></i>İndir</button>

									<button type="button" class="sil btn btn-danger ">Sil</button>
								</div>

							</td>

						</tr>
					<?php } ?>
					</tr>
				</tbody>
			</table>
			<br>
		</div>

	</div>
	<div class="form-group row col-md-12">
		<div class="col-md-12">
			<input id="submit-all" type="submit" data-toggle="tooltip" data-placement="top"
				title="Teklifte yapılan değişiklikleri kaydedin" value="Kaydet"
				class="col-md-10 float-right btn btn-success">
		</div>

	</div>
</form>

<!-- <?php

// if (@$_GET["type"] == "fileupload") {
// 	include("pages/1/fileuploadx.php");
// }
?>-->

<script type="text/javascript" src="src/js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="src/js/bootstrap.min.js"></script>

<!--buradan başlıyor-->
<?php

$qcts = $ac->prepare("SELECT * FROM products ORDER BY id ASC");
$qcts->execute();
$services = $qcts->fetchAll(PDO::FETCH_ASSOC);
$jsonDataServices = json_encode($services);



$unq = $ac->prepare("SELECT * FROM units");
$unq->execute();
$units = $unq->fetchAll(PDO::FETCH_ASSOC);

// Diziyi JSON formatına dönüştürün
$jsonDataUnits = json_encode($units);

?>
<script type="text/javascript">
	$(function () {
		$('#ekle').click(function () {

			var jsonDataServices = <?php echo $jsonDataServices; ?>;
			var jsonDataUnits =
				<?php echo $jsonDataUnits; ?>; // JSON verilerini JavaScript değişkenine atama
			fillSelectOptions(jsonDataServices, jsonDataUnits);

		});
	});


	function fillSelectOptions(services, units) {

		var sayac = sayacGuncelle('#kalem_ekle');
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
			'<tr><th><strong class="sira">Kalem ' + sayac + '</strong></th>' +
			'<td>' + selectMatter + '</td>' +
			'<td>' + selectUnit + '</td>' +
			'<td><input required id="amount_' + sayac + '" name="amount[]' +
			'" type="text" class="form-control" /></td>' +
			'<td><input required id="price_' + sayac + '" name="price[]' +
			'" type="text" class="form-control" /></td>' +
			'<td><a href="#" class="sil btn btn-danger float-right">Sil</a></td></tr>'
		);
		sayac += 1;
	}
	$('#kalem_ekle').on("click", ".sil", function (e) { //user click on remove text
		e.preventDefault();
		var removedRowIndex = $(this).closest("tr").index() + 1;
		$(this).closest("tr").remove();
		// Kalan satırların sıra numaralarını güncelle
		$('#kalem_ekle tbody tr').each(function (index) {
			$(this).find('.sira').text('Kalem ' + index);
		});

		sayac -= 1; // Sayacı güncelle

	})


	$(function () {
		$('#fileAdd').click(function () {
			var sayac = sayacGuncelle('#offerfiles');
			$('#offerfiles tbody').append(
				'<tr><th><strong class="sira">' + sayac + '</strong></th>' +
				'<td><input required id="filedesc' + sayac + '" type="text" name="filedesc[]" class="form-control"></input></td>' +
				'<td><input required id="fileCategory' + sayac + '" type="text" name="fileCategory[]" class="form-control"></input></td>' +
				'<td><input required id="regdate' + sayac + '" type="text" class="form-control date-picker" name="regdate" value=""></td>' +
				'<td><input required id="filename_' + sayac + '" name="filename[]' + '" type="file" class="form-control"></input></td>' +
				'<td><a href="#" class="sil btn btn-danger float-right">Sil</a></td></tr>'
			);
			sayac += 1;
		});
	});
	$('#offerfiles').on("click", ".sil", function (e) { //user click on remove text
		e.preventDefault();
		var removedRowIndex = $(this).closest("tr").index() + 1;

		// Silinen satırı kaldır
		$(this).closest("tr").remove();

		// Kalan satırların sıra numaralarını güncelle
		$('#offerfiles tbody tr').each(function (index) {
			$(this).find('.sira').text(index);
		});
		sayac -= 1; // Sayacı güncelle

	})



	document.getElementById('myForm').addEventListener('submit', function (event) {
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


	function sayacGuncelle(tabloId) {
		var trSayisi = $(tabloId + ' tbody tr').length;
		return trSayisi;
	}
</script>