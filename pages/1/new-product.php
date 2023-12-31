<?php

use LDAP\Result;

permcontrol("seradd");


if ($_POST) {


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


	$insq = $ac->prepare("INSERT INTO products SET Adi = ? , 
													Turu = ? , 
													TedarikciID = ? , 
													StokKodu = ? , 
													UrunGrubu = ? , 
													Birimi = ? , 
													AlisFiyati = ? , 
													AlisParaBirimi = ? , 
													AlisKDV = ? , 
													AlisIskonto = ? , 
													SatisFiyati = ? , 
													SatisParaBirimi = ? , 
													SatisKDV = ? , 
													SatisIskonto = ? , 
													ExtraMaliyet = ? , 
													Barkod = ? , 
													Teslimat = ? , 
													RafKodu = ? , 
													MinStok = ? , 
													Aciklama = ? , 
													Durum = ? , 
													OlusturmaTarihi = ?
													");
	$insq->execute(array($Adi,$Turu,$TedarikciID,$StokKodu,$UrunGrubu,$Birimi,$AlisFiyati,$AlisParaBirimi,$AlisKDV,$AlisIskonto,$SatisFiyati,$SatisParaBirimi,$SatisKDV,$SatisIskonto,$ExtraMaliyet,$Barkod,$Teslimat,$RafKodu,$MinStok,$Aciklama,$Durum,TODAY));
	
}
?>


<div id="maincontainer" class="pd-20 bg-white border-radius-8 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>

		<div class="form-group">
			<div class="row float-right">
				<button class="btn btn-primary mr-2" onclick="requiredFieldControl()">Kaydet</button>

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
				<label for="UrunGurubu">
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
				<label for="Turu">
					<font color="red">(*)</font>Kategori Seçimi
				</label>
			</div>
			<div class="col-sm-12 col-md-4">
				<select required name="Turu" class="custom-select col-12">
					<option disabled selected="">Kategori Seçimi</option>
					<option value="Ürün">Ürün</option>
					<option value="Hizmet">Hizmet</option>
				</select>
			</div>
		</div>

		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label for="Adi">
					<font color="red">(*)</font>Ürün/Hizmet Adı
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<input required name="Adi" value="" class="form-control" type="text">
			</div>

			<div class="col-md-2 col-sm-12">
				<label for="StokKodu">
					<font color="red">(*)</font>Stok Kodu :
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<input required name="StokKodu" value="" class="form-control" type="text">
			</div>

		</div>

		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label for="TedarikciID">
					<font color="red">(*)</font>Tedarikci :
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<select required name="TedarikciID" class="form-control">
					<option disabled selected value="">Tedarikçiyi seçiniz!</option>
					<option value="1">Ahmet Şansal</option>
				</select>
			</div>

			<div class="col-md-2 col-sm-12">
				<label for="Birimi">
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
				<label for="AlisFiyati">
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
				<label for="SatişFiyati">
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
				<label for="AlisIskonto">
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
				<label for="SatisIskonto">
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
				<label for="EkstraMaliyet">
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

		<div class="form-group row">
			<div class="col-md-6 col-sm-12">
				<div class="form-group row">
					<div class="col-md-4 col-sm-12">
						<label for="Barkod">
							<font color="red">(*)</font>Barkod :
						</label>
					</div>
					<div class="col-md-8 col-sm-12">
						<input required name="Barkod" value="" class="form-control" type="text">
					</div>
				</div>
				<div class="form-group row">
					<div class="col-md-4 col-sm-12">
						<label for="Teslimat">
							<font color="red">(*)</font>Teslimat Gün :
						</label>
					</div>
					<div class="col-md-8 col-sm-12">
						<input required name="Teslimat" value="" class="form-control date-picker" type="text">
					</div>
				</div>
				<div class="form-group row">
					<div class="col-md-4 col-sm-12">
						<label for="RafKodu">
							<font color="red">(*)</font>Raf Kodu :
						</label>
					</div>
					<div class="col-md-8 col-sm-12">
						<input required name="RafKodu" value="Raf Kodu" class="form-control date-picker" type="text">
					</div>
				</div>

			</div>


			<div class="col-md-2 col-sm-12">
				<label>
					<font color="red">(*)</font>Açıklama :
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<textarea name="Aciklama" value="" class="form-control" type="textarea">	</textarea>
			</div>

		</div>


		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label for="MinStok">
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


<script>
	function requiredFieldControl() {
		$(document).ready(function() {
			var StokKoduval = $('[name="StokKodu"]').val().trim();

			if (StokKoduval == '') {
				showMessage('Stok Kodu boş olamaz!', 'alert');
			} else {
				validateForm();
			};
		});
	};
</script>
