<?php

permcontrol("pedit");

if (@!$_GET["pid"]) {
	header("Location:index.php?p=products&update=none&code=md88763");
	exit;
}


$pid = $_GET["pid"];
if ($_POST) {


	$urunAdi = @$_POST["urunAdi"];
	$StokKodu = @$_POST["StokKodu"];
	$Birimi = @$_POST["Birimi"];
	$AlisFiyati = @$_POST["AlisFiyati"];
	$AlisParaBirimi = @$_POST["AlisParaBirimi"];
	$SatisFiyati = @$_POST["SatisFiyati"];
	$SatisParaBirimi = @$_POST["SatisParaBirimi"];
	$aciklama = @$_POST["aciklama"];



	$upquery = $ac->prepare("UPDATE products SET Adi = ? , 
													StokKodu = ? , 
													Birimi = ? , 
													AlisFiyati = ? , 
													AlisParaBirimi = ? , 
													SatisFiyati = ? , 
													SatisParaBirimi = ? , 
													Aciklama = ? ,
													OlusturmaTarihi = ?
													WHERE id = ?");
	$upquery->execute(array($urunAdi, $StokKodu, $Birimi, $AlisFiyati, $AlisParaBirimi, $SatisFiyati, $SatisParaBirimi, $aciklama, TODAY, $pid));
	if ($upquery) {
		header("Location: index.php?p=edit-product&pid=$pid&st=success");

	}
}

if (@$_GET["st"] == "success") {
	showAlert("success", "Ürün başarı ile güncellendi!");

}

$query = $ac->prepare("SELECT * FROM products where id = ?");
$query->execute(array($pid));
$result = $query->fetch(PDO::FETCH_ASSOC);
?>


<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue">
				<?php echo $pdat["p_title"]; ?>
			</h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
				bırakmayın..<br></p>
		</div>

		<div class="form-group">
			<div class="row float-right m-2">

			<button id="submitButton" onclick="validateForm()" data-tooltip="Kaydet" data-tooltip-location="bottom"
					class="btn btn-primary btn-sm text-white">
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
				<input required name="urunAdi" value="<?php echo $result["Adi"] ?>" class="form-control" type="text">
			</div>

			<div class="col-md-2 col-sm-12">
				<label for="StokKodu">
					Stok Kodu :
				</label>
			</div>
			<div class="col-md-4 col-sm-12">
				<input required name="StokKodu" value="<?php echo $result["StokKodu"] ?>" class="form-control"
					type="text">
			</div>

		</div>

		<div class="form-group row">


			<div class="col-md-2 col-sm-12">
				<label for="Birimi">
					<font color="red">(*)</font>Birimi :
				</label>
			</div>
			<div class="col-sm-12 col-md-4">
				<?php OlcuBirimleri('Birimi', $result['Birimi'], 'required', "unit") ?>
			</div>

		</div>



		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label for="AlisFiyati">
					Alış Fiyat :
				</label>
			</div>
			<div class="col-md-2 col-sm-12">
				<input name="AlisFiyati" value="<?php echo $result["AlisFiyati"] ?>" class="form-control" type="text">
			</div>

			<div class="col-sm-12 col-md-2">
				<?php ParaBirimleri('AlisParaBirimi', $result["AlisParaBirimi"], "cur") ?>
			</div>


			<div class="col-md-2 col-sm-12">
				<label for="SatisFiyati">
					Satış Fiyat :
				</label>
			</div>
			<div class="col-md-2 col-sm-12">
				<input name="SatisFiyati" value="<?php echo $result["SatisFiyati"] ?>" class="form-control" type="text">
			</div>

			<div class="col-sm-12 col-md-2">
				<?php ParaBirimleri('SatisParaBirimi', $result["SatisParaBirimi"], "cur") ?>
			</div>
		</div>



		<div class="form-group row">
			<div class="col-md-2 col-sm-12">
				<label for="aciklama">
					Açıklama :
				</label>
			</div>
			<div class="col-md-4 col-sm-12">

				<textarea name="aciklama" class="form-control"
					type="textarea"><?php echo $result["Aciklama"] ?></textarea>
			</div>


		</div>

	</form>
	<button class="border accordion">Detay</button>
	<div class="acordion-panel">
		<p>Eklenme Tarihi :
			<?php echo $result["OlusturmaTarihi"] ?>
		</p>
	</div>

</div>


</div>


<script>
	function requiredFieldControl() {
		$(document).ready(function () {
			var StokKoduval = $('[name="StokKodu"]').val().trim();

			if (StokKoduval == '') {
				showMessage('Stok Kodu boş olamaz!', 'alert');
			} else {
				validateForm();
			};
		});
	};


	var acc = document.getElementsByClassName("accordion");
	var i;

	for (i = 0; i < acc.length; i++) {
		acc[i].addEventListener("click", function () {
			this.classList.toggle("active");
			var panel = this.nextElementSibling;
			if (panel.style.maxHeight) {
				panel.style.maxHeight = null;
			} else {
				panel.style.maxHeight = panel.scrollHeight + "px";
			}
		});
	}
</script>