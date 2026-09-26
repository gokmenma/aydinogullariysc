<?php
permcontrol("pedit");

$pid = $_GET["pid"];
$pis = $pid;
$poid = $_GET["poid"];


$cerq = $ac->prepare("SELECT * FROM projects WHERE id = ?");
$cerq->execute(array($_GET["pid"]));
$cc = $cerq->fetch(PDO::FETCH_ASSOC);

$pstatus = $_POST["pstatu"];

$secilen_pcid = $cc["pcreativer"];
$qct = $ac->prepare("SELECT * FROM users WHERE id=$secilen_pcid");
$qct->execute();
$cscs = $qct->fetch(PDO::FETCH_ASSOC);


if (!$cc) {
	header("Location: index.php?p=all-projects&err=01735");
	exit;
}


if ($_POST) {

	if (!$_POST["company"] || !$_POST["ServisKonusu"] || !$_POST["TahsilatTuru"]) {
		header("Location: index.php?p=edit-project&st=empties");
		exit;
	}

	if (@$_POST["pstatu"] == 18) {
		$kadi = $cscs["username"];
		$datetime = $cc["pregdate"];
		$date = date("d.m.Y H:i:s", strtotime($datetime));
		$servissonucu = @$_POST["servicesnote"];
		$pnote = "Servis " . $kadi . " adlı kullanıcı tarafından " . $date . " tarihinde iptal edilmiştir. " . $servissonucu;

	} else {
		$pnote = addslashes(@$_POST["servicesnote"]);
	}
	$company = $_POST["company"];
	$offerno = $_POST["offerno"];
	$servicestype = $_POST["ServisKonusu"];
	$collectiontype = $_POST["TahsilatTuru"];
	$address = $_POST["address"];
	$creativerx = sesset("id");
	$pdesc = $_POST["pdesc"];
	$pregdate = date_tr("Y-m-d H:i:s");
	$pstartdate = date_tr($_POST["pstartdate"]);
	$price = $_POST["price"];
	$teklifID = $soneklenen_dosyaid;
	$pstatu = $_POST["pstatu"];
	$pps = "";
	foreach ($_POST["permings"] as $psx) {
		$pps .= $psx . "|";

	}
	$upxsx = $ac->prepare("UPDATE projects SET
				pcid = ?,
                poid = ?,
				servicestype = ?,
				collectiontype = ?,
				address = ?,
                pcreativer = ?,
                pdesc = ?,
				pstart_date = ?,
                pauthors = ?,
				price = ?,
				pnotes = ?,
                pstatu = ?  WHERE id = ?");

	$upxsx->execute(array($company, $offerno, $servicestype, $collectiontype, $address, $creativerx, $pdesc, $pstartdate, $pps, $price, $pnote, $pstatu, $pid));

	if ($upxsx) {
		header("Location: index.php?p=all-projects&pid=$pid&up=success&st=yes&mdcode=14");
	} else {
		header("Location: index.php?p=all-projects&st=newerror&code=acmd008");
	}
}
if (@$_GET["st"] == "yes") {
	showAlert('success', $pid . 'Numaralı Servis Başarı ile Güncellendi.');
}
if (@$_GET["st"] == "empties") {
	showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');

}
if (@$_GET["st"] == "iptal") {
	showAlert('alert', 'Servis İPTAL edildiği için Servis Sonucu alanına kim tarafından neden iptal edildiği bilgisini giriniz.');

}
$ofinf = $ac->prepare("SELECT * FROM offers WHERE id = ?");
$ofinf->execute(array($cc["poid"]));
$ofi = $ofinf->fetch(PDO::FETCH_ASSOC);
?>
<form name="myForm" enctype="multipart/form-data" action="" method="POST">
	<div class="pd-ltr-20 xs-pd-20-10">
		<div class="min-height-200px">
			<!-- Default Basic Forms Start -->
			<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
				<div class="clearfix mb-30">
					<div class="pull-left">
						<h4 class="text-blue">
							<?php echo $pdat["p_title"]; ?>
						</h4>
						<br>
					</div>

					<button type="submit" id="submitButton" onclick="validateForm()"
						class="float-right btn btn-sm btn-primary"><i class="fa fa-save"></i> Kaydet </button>
				</div>
				<div class="form-group row">
					<label class="col-md-2"> Servis Numarası : </label>
					<div class="input-group col-md-4">
						<h4>
							<?php echo "SN" . $cc["id"]; ?>
						</h4>
					</div>
					<label class="col-md-2">
						<font color="red">(*)</font>Servis Konusu :
					</label>
					<div class="input-group col-md-4">
						<select required name="ServisKonusu" id="ServisKonusu" class="selectpicker form-control"
							data-container="body" data-style="border bg-white" title="Servis Konusu Seçiniz!">
							<?php
							$sk = $ac->prepare("SELECT * FROM units WHERE statu='2' ");
							$sk->execute();
							while ($mm1 = $sk->fetch(PDO::FETCH_ASSOC)) {
								?>
								<option <?php echo $mm1["id"] == $cc["servicestype"] ? "selected" : ""; ?>
									value="<?php echo $mm1["id"]; ?>">
									<?php echo $mm1["title"]; ?>
								</option>
							<?php } ?>
						</select>
						<div class="chooseitem">
							<!-- Button trigger modal -->
							<a type="button" href="index.php?p=servicestype" target="_blank" class="btn btn-success"
								data-toggle="tooltip" data-placement="top"
								title="Servis Konusu Eklemek için tıklayınız!">
								<i class="fa fa-plus-circle"></i>
							</a>
						</div>
					</div>
				</div>
				<div class="form-group row ">
					<label class="col-md-2">
						<font color="red">(*)</font>Firma :
					</label>
					<div class="input-group col-md-4">
						<select required name="company" id="company" class="form-control" style="font-weight: bold;">
							<!--<option disabled >Bu sayfada başka firma seçilemez!!!</option> -->
							<?php
							$secilen_cid = $cc["pcid"];
							$qct = $ac->prepare("SELECT * FROM customers WHERE id=$secilen_cid");
							$qct->execute();
							$cscs = $qct->fetch(PDO::FETCH_ASSOC)
								?>
							<option value="<?php echo $cscs["id"]; ?>">
								<?php echo $cscs["company"]; ?>
							</option>

						</select>
						<div class="chooseitem">
							<!-- Button trigger modal -->
							<a type="button" href="index.php?p=new-customer" target="_blank" class="btn btn-success"
								data-toggle="tooltip" data-placement="top" title="Yeni Firma Eklemek için tıklayınız!">
								<i class="fa fa-plus-circle"></i>
							</a>
						</div>
					</div>
					<label class="col-md-2">
						<font color="red">(*)</font>Tahsilat Türü :
					</label>
					<div class="input-group col-md-4">
						<select required name="TahsilatTuru" id="TahsilatTuru" class="selectpicker form-control"
							data-container="body" data-style="border bg-white" title="Tahsilat Türü Seçiniz!">
							<?php
							$tt = $ac->prepare("SELECT * FROM units WHERE statu='3' ");
							$tt->execute();
							while ($mm2 = $tt->fetch(PDO::FETCH_ASSOC)) {
								?>
								<option <?php echo $mm2["id"] == $cc["collectiontype"] ? "selected" : ""; ?>
									value="<?php echo $mm2["id"]; ?>">
									<?php echo $mm2["title"]; ?>
								</option>
							<?php } ?>
						</select>
						<div class="chooseitem">
							<!-- Button trigger modal -->
							<a type="button" href="index.php?p=paytype" target="_blank" class="btn btn-success"
								data-toggle="tooltip" data-placement="top"
								title="Tahsilat Türü Eklemek için tıklayınız!">
								<i class="fa fa-plus-circle"></i>
							</a>
						</div>
					</div>
				</div>


				<div class="form-group row ">
					<label class="col-md-2"> Adres Bölge : </label>
					<div class="input-group col-md-4">
						<input type="text" id="address" name="address" class=" form-control"
							value="<?php echo $cc['address']; ?>">
					</div>
					<label class="col-md-2"> Başlama Tarihi : </label>
					<div class="input-group col-md-4">
						<input name="pstartdate" class="form-control date-picker" placeholder="Tarih Seçin" type="text"
							value="<?php echo $cc['pstart_date']; ?>">
					</div>
				</div>
				<div class="form-group row ">
					<label class="col-md-2"> Servis Yetkilileri : </label>
					<div class="input-group col-md-4">
						<select name="permings[]" class="selectpicker form-control" class="selectpicker form-control"
							data-container="body" data-style="border bg-white " multiple data-max-options="3">
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

										?>
										<option <?php
										$caks = explode("|", $cc["pauthors"]);
										foreach ($caks as $kiks) {
											if ($kiks == $px["id"]) {
												echo "selected ";
											}
										}
										?> value="<?php echo $px["id"]; ?>">
											<?php echo $px["username"]; ?>
										</option>
									<?php } ?>
								</optgroup>
							<?php } ?>
						</select>
					</div>
					<label class="col-md-2"> Fiyat Bilgisi : </label>
					<div class="input-group col-md-4">
						<input name="price" class="form-control" type="text" value="<?php echo $cc['price']; ?>">
					</div>
				</div>
				<div class="form-group row">

					<label class="col-md-2">Teklif Numarası:</label>
					<div class="col-md-4">
						<input name="offerno" class="form-control" type="text"
							value="<?php echo ($cc['poid'] != 0) ? $cc['poid'] : 'Teklif No Yok'; ?>">

					</div>

					<label class="col-md-2">Teklif Dosyası:</label>
					<div class="col-md-4">

						<?php
						$cq = $ac->prepare("SELECT * FROM files WHERE pid = ? ORDER by id DESC");
						$cq->execute(array($pis));
						//$kx = 1;
						$as = $cq->fetch(PDO::FETCH_ASSOC);
						if ($as != NULL) {
							?>
							<a href="servicefiles/<?php echo $as["filename"]; ?>"><span
									class="badge badge-success">İndir</span></a>
							<?php
						} else
							echo "Teklif Dosyası Yok";
						?>
					</div>
				</div>

				<div class="form-group row">
					<label class="col-md-2">Açıklama : </label>
					<div class="col-md-4">
						<textarea name="pdesc" placeholder="Servis hakkında açıklama ekleyebilirsiniz."
							class="form-control"> <?php echo trim($cc["pdesc"]); ?></textarea>
					</div>

					<label class="col-md-2">Servis Sonucu : </label>
					<div class="col-md-4">

						<textarea oninput="kontrolEt()" name="servicesnote" id="servicesnote"
							placeholder="Servis sonucu hakkında  bir açıklama ekleyiniz."
							class="form-control"> <?php echo trim($cc["pnotes"]); ?></textarea>
					</div>
				</div>



				<div class="form-group row">
					<label class="col-md-2">Servis Durumu :
					</label>
					<div class="col-md-4">
						<select name="pstatu" id="pstatu" class="selectpicker" data-style="border bg-white"
							onchange="kontrolEt()">
							<option style="color:orange" <?php echo $cc["pstatu"] == "1" ? "selected" : ""; ?> value="1">
								Bekliyor</option>
							<option style="color:blue" <?php echo $cc["pstatu"] == "2" ? "selected" : ""; ?> value="2">
								Çalışıyor</option>
							<option style="color:green" <?php echo $cc["pstatu"] == "3" ? "selected" : ""; ?> value="3">
								Tamamlandı</option>
							<option style="color:red" <?php echo $cc["pstatu"] == "4" ? "selected" : ""; ?> value="4">
								İptal Edildi</option>
						</select>


					</div>
					<div class="col-md-2"></div>
					<div id="uyari" class="col-md-4"> </div>

				</div>
				<div class="row">

					<div class="col-md-2">
					</div>
					<div class="col-md-10">
						<div class="progress mb-20">
							<div id="progress-bar" class="progress-bar" role="progressbar " style="width: 0%"
								aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
						</div>
					</div>
				</div>

			</div>


		</div>
	</div>
</form>
<style>
	@keyframes blink {
		0% {
			opacity: 1;
		}

		50% {
			opacity: 0;
		}

		100% {
			opacity: 1;
		}
	}

	#uyari {
		color: red;
		animation: blink 4s infinite;
		/* Sonsuz döngüde blink animasyonu */
		font-size: 18px;
	}
</style>

<script>
	$(document).ready(function () {
		kontrolEt();
	})

	function kontrolEt() {
		var servicesNote = $("#servicesnote").val('');
		var submitButton = $("#submitButton");
		var selectedStatus = $("#pstatu").val();
		var yuzde, renk;

		if (selectedStatus == 0) {
			yuzde = 0;
			renk = "";
		} else if (selectedStatus == 1) {
			yuzde = 33;
			renk = "bg-warning";
		} else if (selectedStatus == 2) {
			yuzde = 66;
			renk = "bg-primary";
		} else if (selectedStatus == 3) {
			yuzde = 100;
			renk = "bg-success";
		} else if (selectedStatus == 4) {
			yuzde = 100;
			renk = "bg-danger"; // Gri renk için boş string
			$("#uyari").html("<b>İptal Edildi</b> seçildiği için <b>Servis Sonucu</b> alanını mutlaka doldurunuz...!")
			submitButton.disabled = true; // Butonu pasif yap
		}
		if (selectedStatus != 4) {
			$("#uyari").html("");
			submitButton.disabled = false; // Butonu aktif yap
		}
		$('#progress-bar').css('width', yuzde + '%').attr('aria-valuenow', yuzde).removeClass().addClass('progress-bar ' + renk);

	};

</script>