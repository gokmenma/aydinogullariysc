<?php
permcontrol("seradd");

if ($_POST) {

	$stitle = @$_POST["stitle"];
	$sdesc = @$_POST["sdesc"];
	$smain = @$_POST["smain"];

	if (empty($stitle) || empty($smain)) {
		header("Location: index.php?p=new-product&st=empties");
		exit;
	}


	$colorray = array("yellow", "blue-50", "blue", "green", "yellow");

	$clrs = array_rand($colorray);

	$insq = $ac->prepare("INSERT INTO services SET
	mid = ?,
	stitle = ?,
	sdesc = ?");

	$insq->execute(array($smain, $stitle, $sdesc));
	if ($insq) {
		header("Location: index.php?p=new-product&st=newsuccess");
	}
}



$dat = $ac->prepare("SELECT * FROM mainservices ");
$dat->execute();

if (@$_GET["st"] == "empties") {
	showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
}
if (@$_GET["st"] == "newsuccess") {
	showAlert('success', 'Bilgiler Kaydedildi');
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

		<div class="row">

			<!-- <div class="form-group row col-md-6 col-sm-12"> -->
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font>Hizmet Adı
					</label>
					<input required name="stitle" value="" class="form-control" type="text">

				</div>
			</div>

			<div class="col-sm-12 col-md-4"><label>
					<font color="red">(*)</font>Kategori Seçimi
				</label>
				<select required name="smain" class="custom-select col-12">
					<option disabled selected="">Kategori Seçimi</option>
					<?php
					$ms = $ac->prepare("SELECT * FROM mainservices ");
					$ms->execute();
					while ($mm = $ms->fetch(PDO::FETCH_ASSOC)) {
						if ($mm["stitle"]) {
					?>
							<option value="<?php echo $mm["id"]; ?>"><?php echo $mm["stitle"]; ?></option>
					<?php
						}
					}
					?>
				</select>
			</div>

			<!-- </div> -->
			<div class="col-md-12 col-sm-12">
				<div class="form-group">
					<label>Açıklama </label>
					<textarea name="sdesc" value="" class="form-control" type="text"></textarea>

				</div>
			</div>

		</div><br>



		<!-- <input type="submit" value="Değişiklikleri Kaydet" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br> -->
	</form>
	<script>
		document.getElementById("submitButton").addEventListener("click", function() {
			var form = document.getElementById("myForm");
			form.submit();
		});
	</script>
</div>



<?php include('include/footer.php'); ?>