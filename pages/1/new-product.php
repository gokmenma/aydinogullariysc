<?php

use LDAP\Result;

permcontrol("seradd");


if ($_POST) {


	// if (empty($Adi) || empty($Turu)) {
	// 	header("Location: index.php?p=new-product&st=empties");
	// 	exit;
	// }



	$Adi = @$_POST["Adi"];
	$Turu = @$_POST["Turu"];
	$TedarikciID = @$_POST["TedarikciID"];
	$StokKodu = @$_POST["StokKodu"];
	$UrunGrubu = @$_POST["UrunGrubu"];
	$Birimi = @$_POST["Birimi"];
	$AlisFiyati = @$_POST["AlisFiyati"];
	$AlisParaBirimi = @$_POST["AlisParaBirimi"];
	$AlisKDV = @$_POST["AlisKDV"];
	$AlisIskonto = @$_POST["AlisIskonto"];
	$SatisFiyati = @$_POST["SatisFiyati"];
	$SatisParaBirimi = @$_POST["SatisParaBirimi"];
	$SatisKDV = @$_POST["SatisKDV"];
	$SatisIskonto = @$_POST["SatisIskonto"];
	$ExtraMaliyet = @$_POST["ExtraMaliyet"];
	$Barkod = @$_POST["Barkod"];
	$Teslimat = @$_POST["Teslimat"];
	$RafKodu = @$_POST["RafKodu"];
	$MinStok = @$_POST["MinStok"];
	$Aciklama = @$_POST["Aciklama"];
	$Durum = @$_POST["Durum"];


	// $insq = $ac->prepare("INSERT INTO products SET Adi = ? , 
	// 												Turu = ? , 
	// 												TedarikciID = ? , 
	// 												StokKodu = ? , 
	// 												UrunGrubu = ? , 
	// 												Birimi = ? , 
	// 												AlisFiyati = ? , 
	// 												AlisParaBirimi = ? , 
	// 												AlisKDV = ? , 
	// 												AlisIskonto = ? , 
	// 												SatisFiyati = ? , 
	// 												SatisParaBirimi = ? , 
	// 												SatisKDV = ? , 
	// 												SatisIskonto = ? , 
	// 												ExtraMaliyet = ? , 
	// 												Barkod = ? , 
	// 												Teslimat = ? , 
	// 												RafKodu = ? , 
	// 												MinStok = ? , 
	// 												Aciklama = ? , 
	// 												Durum = ? , 
	// 												OlusturmaTarihi = ? 
	// 												");



$insq = $ac->prepare("INSERT INTO products SET Adi = ? , 
												Turu = ? ,
												StokKodu = ? , 
												UrunGrubu = ? , 
												Birimi = ? , 
												OlusturmaTarihi = ?");
	$insq->execute(array($Adi, $Turu,$StokKodu,$UrunGrubu,$Birimi, TODAY));
	if ($insq) {
		header("Location: index.php?p=new-product&st=newsuccess");
	}
}


if (@$_GET["st"] == "empties") {
	showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
}
if (@$_GET["st"] == "newsuccess") {
	showAlert('success', 'Ürün Kaydedildi');
} else if (@$_GET["st"] == "numericerror") {
	showAlert('alert', 'Fiyat kısmına sadece rakamlardan oluşan değer girebilirsiniz.');
}
?>

<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>

		<div class="form-group">
			<div class="row float-right">
				<input type="submit" id="submitButton" value="Kaydet" class="btn btn-primary mr-2">
				<a href="index.php?p=products">
					<input type="submit" value="Ürünleri Listele" class="btn btn-success  mr-3">
				</a>

			</div>

		</div>
	</div>

	<form action="" method="POST" id="myForm">
		<!-- <form method="POST" action="index.php?p=new-product"> -->

		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Ana Ürün:
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<select required name="UrunGrubu" value="" class="form-control">
					<option disabled selected value="">Ana Ürünü Listeden Seçiniz!</option>
					<option value="Yangın Söndürme Cihazı">Yangın Söndürme Cihazı</option>

				</select>
			</div>



			<div class="col-sm-12 col-md-2">
				<label>
					<font color="red">(*)</font>Kategori Seçimi
				</label>
			</div>
			<div class="col-sm-12 col-md-4">
				<select required name="Turu" class="custom-select col-12">
					<option disabled selected="">Kategori Seçimi</option>
					<option value="">Ürün</option>
					<option value="">Hizmet</option>
				</select>
			</div>
		</div>

		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Ürün/Hizmet Adı
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<input required name="Adi" value="" class="form-control" type="text">
			</div>

			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Stok Kodu :
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<input required name="StokKodu" value="" class="form-control" type="text">
			</div>

		</div>

		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Tedarikci :
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<select name="TedarikciID" class="form-control">
					<option disabled selected value="">Tedarikçiyi seçiniz!</option>
				</select>
			</div>

			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Birimi :
				</label>
			</div>
			<div class="col-sm-12 col-md-4">
				<?php OlcuBirimleri('Birimi') ?>
			</div>

		</div>
		<br>
		<hr>
		<br>


		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Alış Fiyat :
				</label>
			</div>
			<div class="col-md-2 col-sm-12">
				<input required name="AlisFiyati" value="" class="form-control" type="text">
			</div>

			<div class="col-sm-12 col-md-2">
				<?php ParaBirimleri('AlisParaBirimi') ?>
			</div>


			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Satış Fiyat :
				</label>
			</div>
			<div class="col-md-2 col-sm-12">
				<input required name="SatişFiyati" value="" class="form-control" type="text">
			</div>

			<div class="col-sm-12 col-md-2">
				<?php ParaBirimleri('SatisParaBirimi') ?>
			</div>
		</div>



		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Alış İskonto / KDV :
				</label>
			</div>
			<div class="col-md-2 col-sm-12">
				<input required name="AlisIskonto" value="" class="form-control" type="text">
			</div>
			<div class="col-sm-12 col-md-2">
				<?php KdvOranları('AlisKDV') ?>
			</div>

			<!-- Satış Para Biriimi-->
			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Satış İskonto / KDV :
				</label>
			</div>
			<div class="col-md-2 col-sm-12">
				<input required name="SatisIskonto" value="" class="form-control" type="text">
			</div>
			<div class="col-sm-12 col-md-2">
				<?php KdvOranları('SatisKDV') ?>
			</div>

		</div>

		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label>
					Eksrta Maliyet:
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<input required name="EkstraMaliyet" value="" class="form-control" type="text">
			</div>
		</div>
		<br>
		<hr>
		<br>


		<!-- = ? , 
 = ? , 
 = ? , 
 ExtraMaliyet= ? , 
 = ? , 
 = ? , 
 = ? , 
 = ? , 
 = ? , 
OlusturmaTarihi = ? -->

		<div class="form-group row">
			<div class="col-md-6 col-sm-12">
				<div class="form-group row">
					<div class="col-md-4 col-sm-12">
						<label>
							<font color="red">(*)</font>Barkod :
						</label>
					</div>
					<div class="col-md-8 col-sm-12">
						<input required name="Barkod" value="" class="form-control" type="text">
					</div>
				</div>
				<div class="form-group row">
					<div class="col-md-4 col-sm-12">
						<label>
							<font color="red">(*)</font>Teslimat Gün :
						</label>
					</div>
					<div class="col-md-8 col-sm-12">
						<input required name="Teslimat" value="" class="form-control date-picker" type="text">
					</div>
				</div>
				<div class="form-group row">
					<div class="col-md-4 col-sm-12">
						<label>
							<font color="red">(*)</font>Raf Kodu :
						</label>
					</div>
					<div class="col-md-8 col-sm-12">
						<input required name="RafKodu" value="" class="form-control date-picker" type="text">
					</div>
				</div>

			</div>


			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Açıklama :
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<textarea required name="Aciklama" value="" class="form-control" type="textarea">	</textarea>
			</div>

		</div>


		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Minimum Stok
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<input required name="MinStok" value="" class="form-control" type="text">
			</div>

			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Durumu :
				</label>
			</div>
			<div class="col-sm-12 col-md-4">
				<select name="Durum" id="" class="form-control">
					<option disabled>Durumunu Seçiniz!</option>
					<option selected value="Aktif">Aktif</option>
					<option value="Pasif">Pasif</option>
				</select>
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
		</div>



	</form>
</div>
<?php include('include/app.js'); ?>
<?php include('include/footer.php'); ?>