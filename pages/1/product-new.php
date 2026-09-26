<?php


permcontrol("productadd");
$stok_kodu ="STK". benzersizStokKodu(); 



if ($_POST) {


	$urunAdi = @$_POST["urunAdi"];
	$StokKodu = @$_POST["StokKodu"];
	$Birimi = @$_POST["Birimi"];
	$AlisFiyati = @$_POST["AlisFiyati"];
	$AlisFiyati = str_replace(',', '.', $AlisFiyati);
	$AlisParaBirimi = @$_POST["AlisParaBirimi"];
	$SatisFiyati = @$_POST["SatisFiyati"];
	$SatisFiyati =str_replace(',', '.', $SatisFiyati);
	$SatisParaBirimi = @$_POST["SatisParaBirimi"];
	$aciklama = @$_POST["aciklama"];



	$insq = $ac->prepare("INSERT INTO products SET Adi = ? , 
													StokKodu = ? , 
													Birimi = ? , 
													AlisFiyati = ? , 
													AlisParaBirimi = ? , 
													SatisFiyati = ? , 
													SatisParaBirimi = ? , 
													aciklama = ? ,
													OlusturmaTarihi = ?
													");
	$insq->execute(array($urunAdi,$StokKodu,$Birimi,$AlisFiyati,$AlisParaBirimi,$SatisFiyati,$SatisParaBirimi,$aciklama, TODAY));
	if ($insq){
		header("Location: index.php?p=new-product&st=newsuccess");

	}
}

if (@$_GET["st"] == "newsuccess") {

	showAlert("success", "Teklif Başarı ile kaydedildi!");
	?>
	<script>
		window.history.pushState({}, '', 'index.php?p=offer-new')
	</script>
	<?php
}
?>

<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>

		<div class="form-group">
			<div class="row float-right m-2">
			<button id="submitButton" onclick="validateForm()" data-tooltip="Kaydet"
						data-tooltip-location="bottom" class="btn btn-primary btn-sm text-white">
						<i class="fa fa-save"></i>
						Kaydet</button>
				<a class="btn btn-secondary btn-sm ml-1 text-white" href="index.php?p=products" data-tooltip="Listeye Dön">
				<i class="fa fa-list p-1"></i>Ürün Listesi</a>

				

			</div>

		</div>
	</div>

	<form action="" method="POST" id="myForm">
		<!-- <form method="POST" action="index.php?p=new-product"> -->

		<div class="form-group row">
	

			<div class="col-md-2 col-sm-12">
				<label for="urunAdi">
					<font color="red">(*)</font>Ürün/Hizmet Adı
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<input required name="urunAdi" value="" class="form-control" type="text">
			</div>

			<div class="col-md-2 col-sm-12">
				<label for="StokKodu">
					Stok Kodu :
				</label>
			</div>  
			<div class="col-md-4 col-sm-12">
				<input required name="StokKodu" value="<?php echo $stok_kodu ?>" class="form-control" type="text">
			</div>

		</div>

		<div class="form-group row">
		

			<div class="col-md-2 col-sm-12">
				<label for="Birimi">
					<font color="red">(*)</font>Birimi :
				</label>
			</div>
			<div class="col-sm-12 col-md-4">
				<?php OlcuBirimleri('Birimi',"","required","unit") ?>
			</div>

		</div>
		


		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label for="AlisFiyati">
					Alış Fiyat :
				</label>
			</div>
			<div class="col-md-2 col-sm-12">
				<input  name="AlisFiyati" value="" class="form-control" type="text">
			</div>

			<div class="col-sm-12 col-md-2">
				<?php ParaBirimleri('AlisParaBirimi',"","cur") ?>
			</div>


			<div class="col-md-2 col-sm-12">
				<label for="SatişFiyati">
					Satış Fiyat :
				</label>
			</div>
			<div class="col-md-2 col-sm-12">
				<input name="SatişFiyati" value="" class="form-control" type="text">
			</div>

			<div class="col-sm-12 col-md-2">
				<?php ParaBirimleri('SatisParaBirimi',"","cur") ?>
			</div>
		</div>



		<div class="form-group row">
		<div class="col-md-2 col-sm-12">
				<label for="AlisFiyati">
					Açıklama :
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				
				<textarea name="aciklama" value="" class="form-control" type="textarea"></textarea>
			</div>

	
		</div>

	</form>
</div>


<!-- <script>
	function requiredFieldControl() {
		$(document).ready(function() {
			var StokKoduval = $('[name="stokKodu"]').val().trim();

			if (StokKoduval == '') {
				showMessage('Stok Kodu boş olamaz!', 'alert');
			} else {
				validateForm();
			};
		});
	};
</script> -->
