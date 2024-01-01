 <?php
	permcontrol("sedit");
	if (!@$_GET["sid"]) {
		header("Location:index.php?p=sales");
		exit;
		die;
	}
	$sid = $_GET["sid"];
	if ($_POST) {

		$startdate = $_POST["startdate"];
		$enddate = $_POST["enddate"];
		$desc = @$_POST["desc"];

		if (empty($startdate) || empty($enddate)) {
			header("Location: index.php?p=edit-sale&sid=$sid&st=empties");
			exit;
		}

		$colorray = array("yellow", "blue-50", "blue", "green", "yellow");

		$clrs = array_rand($colorray);

		$insq = $ac->prepare("UPDATE sales SET
	descs = ?,
	start_date = ?,
	end_date = ? WHERE id = ?");

		$insq->execute(array($desc, $startdate, $enddate, $sid));

		header("Location: index.php?p=edit-sale&sid=$sid&st=newsuccess");
	}

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
 			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br>Not: Satışlarınız, gelir-gider sayfasına otomatik olarak yansımaktadır. <br>
 				<font color="red">Tarih kısımlarını "gg-aa-yyyy" formatına uygun şekilde girmeye özen göstermelisiniz.</font><br>Ödemeler, başlangıç tarihinin günü esas alınarak aylık veya yıllık olarak, hizmet bitiş tarihine kadar tekrarlanır.
 			</p>
 		</div>

 	</div>
 	<?php
		$cek = $ac->prepare("SELECT * FROM sales WHERE id = ?");
		$cek->execute(array($_GET["sid"]));
		$cc = $cek->fetch(PDO::FETCH_ASSOC);
		if (!$cc) {
			header("Location:index.php");
			exit;
		}


		?>
 	<form method="POST" action="">

 		<div class="row">
 			<div class="col-md-6 col-sm-12">
 				<div class="col-sm-12 col-md-12"><label>
 						<font color="red">(*)</font>Müşteri:
 					</label>
 					<select disabled name="cid" class="custom-select col-12">
 						<?php
							$ms = $ac->prepare("SELECT * FROM customers ");
							$ms->execute();
							while ($mm = $ms->fetch(PDO::FETCH_ASSOC)) {

							?>
 							<option <?php echo $mm["id"] == $cc["cid"] ? "selected" : ""; ?> value="<?php echo $mm["id"]; ?>"><?php echo $mm["name"]; ?></option>
 						<?php

							}
							?>
 					</select>
 				</div>

 			</div>

 			<div class="form-group row col-md-6 col-sm-12">

 				<div class="col-sm-12 col-md-12"><label>
 						<font color="red">(*)</font>Ürün/Hizmet Seçimi
 					</label>
 					<select disabled name="sid" class="custom-select col-12">
 						<?php
							$msx = $ac->prepare("SELECT * FROM services ");
							$msx->execute();
							while ($mms = $msx->fetch(PDO::FETCH_ASSOC)) {

							?>
 							<option <?php echo $mms["id"] == $cc["sid"] ? "selected" : ""; ?> value="<?php echo $mms["id"]; ?>"><?php echo $mms["stitle"]; ?></option>
 						<?php

							}
							?>
 					</select>
 				</div>
 			</div>
 			<div class="col-md-6 col-sm-12">
 				<div class="col-sm-12 col-md-12"><label>
 						<font color="red">(*)</font>Fiyat :
 					</label>
 					<input disabled class="custom-select col-12" type="" name="price" value="<?php echo $cc["price"]; ?>">
 				</div>

 			</div>

 			<div class="form-group row col-md-6 col-sm-12">

 				<div class="col-sm-12 col-md-12"><label>
 						<font color="red">(*)</font>Ödeme Sıklığı
 					</label>
 					<select disabled name="pay_type" class="custom-select col-12">
 						<option <?php echo $cc["pay_type"] == 1 ? "selected" : ""; ?> value="1">Aylık</option>
 						<option <?php echo $cc["pay_type"] == 2 ? "selected" : ""; ?> value="2">Yıllık</option>


 					</select>
 				</div>
 			</div>
 			<div class="col-md-6 col-sm-12">
 				<div class="col-sm-12 col-md-12"><label>
 						<font color="red">(*)</font>Başlangıç Tarihi:
 					</label>
 					<input class="custom-select col-12 date-picker" type="text" name="startdate" value="<?php echo redate_tr($cc["start_date"]); ?>">
 				</div>

 			</div>


 			<div class="col-md-6 col-sm-12">
 				<div class="col-sm-12 col-md-12"><label>
 						<font color="red">(*)</font>Bitiş Tarihi:
 					</label>
 					<input class="custom-select col-12 date-picker" type="text" name="enddate" value="<?php echo redate_tr($cc["end_date"]); ?>">
 				</div><br>


 			</div>
 			<div class="form-group row col-md-12 col-sm-12">

 				<div class="col-sm-12 col-md-12"><label>
 						<font color="red">(*)</font>Ödeme Şekli
 					</label>
 					<select disabled name="pay_method" class="custom-select col-12">
 						<?php
							$pmq = $ac->prepare("SELECT * FROM pay_methods ");
							$pmq->execute();
							while ($pm = $pmq->fetch(PDO::FETCH_ASSOC)) {
							?>
 							<option <?php echo $pm["id"] == $cc["pay_method"] ? "selected" : ""; ?> value="<?php echo $pm["id"]; ?>"><?php echo $pm["title"]; ?></option>
 						<?php } ?>


 					</select>
 				</div>
 			</div>

 			<div class="col-md-6 col-sm-12">


 				<br>

 			</div>
 			<div class="col-md-11 col-sm-12">
 				<div class="form-group">
 					<label>Açıklama </label>
 					<textarea name="desc" value="" class="form-control" type="text"><?php echo $cc["descs"]; ?></textarea>

 				</div>
 			</div>

 		</div><br>



 		<input type="submit" value="Satış Yap" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
 	</form>
 </div>