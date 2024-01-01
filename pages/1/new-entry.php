<?php
permcontrol("exadd");
if ($_POST) {

	$stitle = @$_POST["titles"];
	$sdesc = @$_POST["descs"];
	$stype = "in";
	$spay = @$_POST["pays"];
	$catm = @$_POST["catsm"];
	$paymethods = @$_POST["paymethods"];

	if (@$_POST["endate"]) {
		$istar = $_POST["endate"];
	} else {
		$istar = TODAY;
	}

	if (empty($stitle) || empty($spay) || empty($paymethods) || empty($catm)) {
		header("Location: index.php?p=new-entry&st=empties");
		exit;
	}

	if (!is_numeric($spay)) {
		header("Location:index.php?p=new-entry&st=numericerror");
		exit;
	}


	$insq = $ac->prepare("INSERT INTO inexps SET
	type = ?,
	title = ?,
	descs = ?,
	cat = ?,
	sdate = ?,
	pay = ?,
	pay_method = ?");

	$insq->execute(array($stype, $stitle, $sdesc, $catm, date_tr($istar), $spay, $paymethods));
	if ($insq) {
		$shw = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
		$shw->execute(array($paymethods));
		$sh = $shw->fetch(PDO::FETCH_ASSOC);

		if ($stype == "in") {
			$ak = $sh["total"] + $spay;
		} else {
			$ak = $sh["total"] - $spay;
		}
		$ups = $ac->prepare("UPDATE pay_methods SET
		total = ?, last_action = ? WHERE id = ?");
		$ups->execute(array($ak, date("d-m-Y - H:i:s"), $paymethods));

		header("Location: index.php?p=income-expense-list&st=newsuccess");
	}
}



$dat = $ac->prepare("SELECT * FROM mainservices ");
$dat->execute();

if (@$_GET["st"] == "empties") {
?>
	<div class="alert alert-danger" role="alert">
		(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
	</div>
<?php
}
if (@$_GET["st"] == "newsuccess") {
?>
	<div class="alert alert-success" role="alert">
		Bilgiler kaydedildi.
	</div>
<?php
} else if (@$_GET["st"] == "numericerror") {
?>
	<div class="alert alert-danger" role="alert">
		Fiyat kısmına sadece rakamlardan oluşan değer girebilirsiniz.
	</div>
<?php
}
?>

<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>

	</div>
	<form method="POST" action="index.php?p=new-entry">

		<div class="row">
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font>Başlık
					</label>
					<input required name="titles" value="" class="form-control" type="text">

				</div>
			</div>

			<div class="form-group row col-md-6 col-sm-12">

				<div class="col-sm-12 col-md-12"><label>
						<font color="red">(*)</font>Kategori
					</label>
					<select required name="catsm" class="custom-select col-12">
						<option disabled selected>Seçim Yapınız</option>
						<?php
						$ceks = $ac->prepare("SELECT * FROM entry_categories WHERE type = ?");
						$ceks->execute(array(1));
						while ($cc = $ceks->fetch(PDO::FETCH_ASSOC)) {
						?>
							<option value="<?php echo $cc["title"]; ?>"><?php echo $cc["title"]; ?></option>
						<?php
						}
						?>
					</select>
				</div>
			</div>
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font>Ödeme Miktarı
					</label>
					<input required name="pays" value="" placeholder="örn: 150" class="form-control" type="text">

				</div>
			</div>

			<div class="form-group row col-md-6 col-sm-12">

				<div class="col-sm-12 col-md-12"><label>
						<font color="red">(*)</font>Ödeme Kanalı
					</label>
					<select required name="paymethods" class="custom-select col-12">
						<?php
						$mq = $ac->prepare("SELECT * FROM pay_methods ");
						$mq->execute();
						while ($mm = $mq->fetch(PDO::FETCH_ASSOC)) {
							if ($mm["currency"] == "tl") {
								$pr = "₺";
							} elseif ($mm["currency"] == "euro") {
								$pr = "€";
							} elseif ($mm["currency"] == "dollar") {
								$pr = "$";
							}
						?>
							<option value="<?php echo $mm["id"]; ?>"><?php echo $mm["title"] . " (" . $mm["total"] . $pr . ")"; ?></option>
						<?php
						}
						?>

					</select>
				</div>
			</div>
			<div class="col-md-11 col-sm-6">
				<div class="form-group">
					<label>Açıklama </label>
					<textarea name="descs" value="" class="form-control" type="text"></textarea>

				</div>
			</div>
			<div class="col-md-6 col-sm-6">
				<div class="form-group"><label>İşlem Tarihi:</label>
					<input class="custom-select col-12 date-picker" type="text" name="endate" placeholder="Boş bırakırsanız otomatik bugün seçilir.">
				</div>

			</div>
		</div><br>



		<input type="submit" value="Değişiklikleri Kaydet" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
	</form>
</div>