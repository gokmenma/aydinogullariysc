<?php
permcontrol("servadd");
define("MAXSX", set("max_sr"));

if ($_POST) {

	if (!$_POST["Firma"] || !$_POST["Marka"]) {
		header("Location: index.php?p=new-service&st=empties");
		exit;
	}

	$Firma = @$_POST["Firma"];
	$SubeAdresi = @$_POST["SubeAdresi"];
	$Cihaz = @$_POST["Cihaz"];
	$Marka = @$_POST["Marka"];
	$Model = @$_POST["Model"];
	$Kategori = @$_POST["Kategori"];
	$BaslamaTarihi = @$_POST["BaslamaTarihi"];
	$BitisTarihi = @$_POST["BitisTarihi"];
	// $Personel = @$_POST["Personel"];
	// $SikayetTanim = @$_POST["SikayetTanim"];
	// $CihazSeriNo = @$_POST["CihazSeriNo"];
	// $CihazUretimTarihi = @$_POST["CihazUretimTarihi"];
	// $GarantiSuresiGun = @$_POST["GarantiSuresiGun"];
	// $Ucret = @$_POST["Ucret"];
	// $Aciklama = @$_POST["Aciklama"];
	// $Sonuc = @$_POST["Sonuc"];
	// $Ozellikler = @$_POST["Ozellikler"];
	// $Durum = @$_POST["Durum"];


	// $regg = $ac->prepare("INSERT INTO services SET
	// 							  Firma = ?, SubeAdresi = ?, Cihaz = ?,
	// 							  Marka = ?, Model = ?, Kategori = ?,
	// 							  BaslamaTarihi = ?, BitisTarihi = ?,
	// 							  Personel = ?, SikayetTanim = ?, CihazSeriNo = ?,
	// 							  CihazUretimTarihi = ?, GarantiSuresiGun = ?,
	// 							  ucret = ?, Aciklama = ?, Sonuc = ?,
	// 							  Ozellikler = ?,	Durum = ?, 
	// 							  ");

	$regg = $ac->prepare("INSERT INTO services SET  
									Firma = ?,SubeAdresi = ?, Cihaz = ?,
									Marka = ?, Model = ?, Kategori = ?,
									BaslamaTarihi = ?, BitisTarihi = ?
									");

	$isRecord = $regg->execute(array(
		$Firma, $SubeAdresi, $Cihaz,
		$Marka, $Model, $Kategori,
		$BaslamaTarihi, $BitisTarihi
	));

	$lidx = $ac->lastInsertId();
	if ($isRecord) {

		header("Location: index.php?p=new-service&st=newsuccess");
	} else {
	}
}

if (@$_GET["st"] == "empties") {
	showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
} elseif (@$_GET["st"] == "newsuccess") {
	showAlert('success', 'Servis kaydı başarıyla eklendi!');
} ?>
<!-- Default Basic Forms Start -->
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
				bırakmayın..<br></p>
		</div>
		<input type="submit" id="submitButton" value="Kaydet" class="float-right btn btn-primary">

	</div>
	<form enctype="multipart/form-data" action="" id="myForm" method="POST">

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font> Firma Adı:
			</label>
			<div class="col-sm-12 col-md-4">
				<input required name="Firma" type="text" class="form-control">
			</div>
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font> Şube Adresi :
			</label>
			<div class="col-sm-12 col-md-4"><input required name="SubeAdresi" type="text" class="form-control">
			</div>
		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font> Ürün Adı:
			</label>
			<div class="col-sm-12 col-md-4">
				<select name="Cihaz" class="form-control">
					<?php $cek = $ac->prepare("SELECT * FROM products");
					$cek->execute();
					while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
					?>
						<option value="<?php echo $dat["id"]; ?>"><?php echo $dat["Adi"]; ?></option>
					<?php
					}
					?>
				</select>
			</div>
			<label class="col-sm-12 col-md-2 col-form-label">Cihaz Seri No:</label>
			<div class="col-sm-12 col-md-4"><input name="Cihaz Seri No" type="text" class="form-control">
			</div>
		</div>




		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font> Marka :
			</label>
			<div class="col-sm-12 col-md-4"><input required name="Marka" type="text" class="form-control">
			</div>

			<label class="col-sm-12 col-md-2 col-form-label">Model:</label>
			<div class="col-sm-12 col-md-4"><input name="Model" type="text" class="form-control">
			</div>
		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Kategori:</label>
			<div class="col-sm-12 col-md-4"><input name="Kategori" type="text" class="form-control">
			</div>
			<label class="col-sm-12 col-md-2 col-form-label"> Cihaz Üretim Tarihi:</label>
			<div class="col-sm-12 col-md-4">
				<input name="BitisTarihi" type="text" class="form-control date-picker" placeholder="Cihazın Üretim Tarihini giriniz!">
			</div>
		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font> Personel Adı:
			</label>
			<div class="col-sm-12 col-md-4">
				<select name="Personel" class="form-control">
					<?php $cek = $ac->prepare("SELECT * FROM users");
					$cek->execute();
					while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
					?>
						<option value="<?php echo $dat["id"]; ?>">
							<?php echo $dat["name"] . " " . $dat["surname"]; ?>
						</option>
					<?php
					}
					?>
				</select>
			</div>



			<label class="col-sm-12 col-md-2 col-form-label">Garanti Süresi Gün:</label>
			<div class="col-sm-12 col-md-4">
				<input name="GarantiSuresiGun" type="text" class="form-control">
			</div>

		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label"> Başlama Tarihi:</label>
			<div class="col-sm-12 col-md-4">
				<input name="BaslamaTarihi" type="text" class="form-control date-picker" placeholder="Başlama Tarihini giriniz!">
			</div>
			<label class="col-sm-12 col-md-2 col-form-label"> Bitiş Tarihi:</label>
			<div class="col-sm-12 col-md-4">
				<input name="BitisTarihi" type="text" class="form-control date-picker" placeholder="Başlama Tarihini giriniz!">
			</div>
		</div>



		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Şikayet Tanım:</label>
			<div class="col-sm-12 col-md-4">
				<textarea name="SikayetTanim" type="textarea" class="form-control"></textarea>
			</div>
			<label class="col-sm-12 col-md-2 col-form-label">
				Açıklama:
			</label>
			<div class="col-sm-12 col-md-4">
				<textarea name="cnotes" placeholder="Servis açıklamasını, yapılan işlemleri yazınız!" class="form-control"></textarea>
			</div>

		</div>


		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Ücret:</label>

			<div class="col-sm-12 col-md-4">
				<input name="ucret" type="text" class="form-control">
			</div>

		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Sonuc:</label>
			<div class="col-sm-12 col-md-4"><input name="Sonuc" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label"> Özellikler:</label>
			<div class="col-sm-12 col-md-4"><input name="Ozellikler" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label"> Durum:</label>
			<div class="col-sm-12 col-md-4">
				<input name="Durum" type="text" class="form-control">
			</div>
		</div>




	</form>
</div>


<?php include('include/app.js'); ?>
<?php include 'include/footer.php'; ?>