 <?php

	permcontrol("sadd");
	if (set("sale_view") != "1") {
		showAlert("alert","Yeni satış oluşturduğunuzda, belirlediğiniz başlangıç - bitiş tarihleri arasında otomatik ödeme planları oluşturulur. Ödemeler sayfasından takibini yapabilirsiniz. Oluşturulan satışa ait bilgiler düzenlenemez. Bu mesaj sadece 1 kez gösterilir.");

		$regupa = $ac->prepare("UPDATE settings SET val = ? WHERE var = ?");
		$regupa->execute(array("1", "sale_view"));
	}
	if ($_POST) {

		$cid = @$_POST["cid"];
		$sid = @$_POST["sid"];
		$price = @$_POST["price"];
		$pay_type = $_POST["pay_type"];
		$pay_method = $_POST["pay_method"];
		$startdate = date_tr($_POST["startdatex"]);
		$enddate = date_tr($_POST["enddatex"]);
		$desc = $_POST["desc"];




		if (empty($price) || empty($startdate) || empty($enddate)) {
			header("Location: index.php?p=new-sales&st=empties");
			exit;
		}

		if (!is_numeric($price)) {
			header("Location:index.php?p=new-sales&st=numericerror");
			exit;
		}
		$colorray = array("yellow", "blue-50", "blue", "green", "yellow");

		$clrs = array_rand($colorray);

		$insq = $ac->prepare("INSERT INTO sales SET
											cid = ?,
											sid = ?,
											price = ?,
											pay_type = ?,
											pay_method = ?,
											descs = ?,
											start_date = ?,
											end_date = ?,
											recalls = ?,
											statu = ?,
											deleted = ?");

		$insq->execute(array($cid, $sid, $price, $pay_type, $pay_method, $desc, $startdate, $enddate, 0, 1, 0));



		$paysx = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
		$paysx->execute(array($pay_method));
		$payme = $paysx->fetch(PDO::FETCH_ASSOC);
		$paymet = $payme["title"];


		$shw = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
		$shw->execute(array($pay_method));
		$sh = $shw->fetch(PDO::FETCH_ASSOC);

		$ak = $sh["total"] + $price;



		if ($pay_type == 1) {
			$sklk = "Aylık";
		} elseif ($pay_type == 2) {
			$sklk = "Yıllık";
		} else {
			$sklk = "Tek Seferlik";
		}
		$ccs = $ac->prepare("SELECT * FROM customers WHERE id = ?");
		$ccs->execute(array($cid));
		$cinf = $ccs->fetch(PDO::FETCH_ASSOC);



		/* ÖDEME KAYITLARI */
		$pmet = $_POST["pay_method"];
		if ($pay_type == "1") {

			$say = 0;
			while ($say < 13) {

				$paydate = date("d-m-Y", strtotime($startdate . " +$say months"));

				if (dtf($paydate, $enddate) < 0) {
					header("Location:index.php?p=sales&st=newsuccess");
					exit;
				}

				$cekb = $ac->prepare("SELECT * FROM sales WHERE cid = ? ORDER BY id DESC LIMIT 1");

				$cekb->execute(array($cid));
				$cck = $cekb->fetch(PDO::FETCH_ASSOC);
				$insertle = $ac->prepare("INSERT INTO payments SET
									cid = ?,
									pid = ?,
									saleid = ?,
									lastdate = ?,
									pay_method = ?,
									type = ?,
									okey = ?");
				$sys = $say + 1;
				$insertle->execute(array($cid, $say, $cck["id"], $paydate, $pmet, "aylik", 0));





				$say++;
			}
		} elseif ($pay_type == 2) {


			$say = 0;
			while ($say < 5) {

				$paydate = date("d-m-Y", strtotime($startdate . " +$say year"));
				if (dtf($paydate, $enddate) < 0) {
					header("Location:index.php?p=sales&st=newsuccess");
					exit;
				}
				$cekb = $ac->prepare("SELECT * FROM sales WHERE cid = ? ORDER BY id DESC LIMIT 1");
				$cekb->execute(array($cid));
				$cck = $cekb->fetch(PDO::FETCH_ASSOC);
				$insertle = $ac->prepare("INSERT INTO payments SET
									cid = ?,
									pid = ?,
									saleid = ?,
									lastdate = ?,
									pay_method = ?,
									type = ?,
									okey = ?");
				$sys = $say + 1;
				$insertle->execute(array($cid, $sys, $cck["id"], $paydate, $pmet, "yillik", 0));





				$say++;
			}
		} else {



			$cekb = $ac->prepare("SELECT * FROM sales WHERE cid = ? ORDER BY id DESC LIMIT 1");
			$cekb->execute(array($cid));
			$cck = $cekb->fetch(PDO::FETCH_ASSOC);
			$insertle = $ac->prepare("INSERT INTO payments SET
				cid = ?,
				pid = ?,
				saleid = ?,
				lastdate = ?,
				pay_method = ?,
				type = ?,
				okey = ?");
			$sys = 1;
			$paydate = TODAY;
			$insertle->execute(array($cid, $sys, $cck["id"], $paydate, $pmet, "tekseferlik", 0));
		}

		header("Location: index.php?p=sales&st=newsuccess");
	}

	if (@$_GET["st"] == "empties") {

		showAlert('alert', "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.");
	}
	if (@$_GET["st"] == "newsuccess") {

		showAlert('success', "Bilgiler kaydedildi.");
	} else if (@$_GET["st"] == "numericerror") {

		showAlert('warning', "Fiyat kısmına sadece rakamlardan oluşan değer girebilirsiniz.");
	}
	?>

 <div class="content pd-20 bg-white border-radius-8 box-shadow mb-30">
 	<div class="clearfix">
 		<div class="pull-left">
 			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
 			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br>Not: Satışlarınız, gelir-gider sayfasına otomatik olarak yansımaktadır. <br>
 				<font color="red">Tarih kısımlarını "gg-aa-yyyy" formatına uygun şekilde girmeye özen göstermelisiniz.</font>
 			</p>
 		</div>
 		<div class="form-group">
 			<input type="submit" id="submitButton" onclick="validateForm()" value="Satış Yap" class="float-right btn btn-success"><br><br>
 		</div>
 	</div>

 	<form method="POST" id="myForm" action="">

 		<div class="form-group row">
 			<label class="col-md-2">
 				<font color="red">(*)</font>Müşteri:
 			</label>
 			<div class="col-md-4">
 				<select required name="cid" class="custom-select col-12">
 					<option disabled selected="">Seçiniz..</option>
 					<?php
						$ms = $ac->prepare("SELECT * FROM customers ");
						$ms->execute();
						while ($mm = $ms->fetch(PDO::FETCH_ASSOC)) {

						?>
 						<option value="<?php echo $mm["id"]; ?>"><?php echo $mm["name"]; ?></option>
 					<?php

						}
						?>
 				</select>
 			</div>

 			<label for="sid" class="col-md-2">
 				<font color="red">(*)</font>Ürün/Hizmet Seçimi:
 			</label>
 			<div class="col-md-4">
 				<select required name="sid" class="custom-select col-12">
 					<option disabled selected="">Hizmet Seçimi</option>
 					<?php
						$msx = $ac->prepare("SELECT * FROM services ");
						$msx->execute();
						while ($mms = $msx->fetch(PDO::FETCH_ASSOC)) {
							if ($mms["stitle"]) {
						?>
 							<option value="<?php echo $mms["id"]; ?>"><?php echo $mms["stitle"]; ?></option>
 					<?php
							}
						}
						?>
 				</select>
 			</div>

 		</div>




 		<div class="form-group row">
 			<label for="price" class="col-md-2">
 				<font color="red">(*)</font>Fiyat:
 			</label>
 			<div class="col-md-4">
 				<input required class="form-control" type="number" name="price" placeholder="₺ - $ gibi birimler girmeyiniz">
 			</div>

 			<label class="col-md-2">
 				<font color="red">(*)</font>Ödeme Sıklığı:
 			</label>
 			<div class="col-md-4">
 				<select name="pay_type" class="custom-select col-12">
 					<option value="1">Aylık</option>
 					<option value="2">Yıllık</option>
 					<option selected value="3">Tek Seferlik</option>

 				</select>
 			</div>
 		</div>

 		<div class="form-group row">
 			<label for="startdatex" class="col-md-2">
			 <font color="red">(*)</font>Başlangıç Tarihi:
 			</label>
 			<div class="col-md-4">
 				<input required class="form-control date-picker" type="text" name="startdatex" placeholder="gg-aa-yyyy">
 			</div>

 			<label for="enddatex" class="col-md-2">
			 <font color="red">(*)</font>Bitiş Tarihi:
 			</label>
 			<div  class="col-md-4">
 				<input required class="form-control date-picker" type="text" name="enddatex" placeholder="gg-aa-yyyy">
 			</div>
 		</div>


 		<div class="form-group row">

 			<label class="col-md-2">
 				Varsayılan Ödeme Kanalı:
 			</label>
 			<div class="col-md-4">

 				<select name="pay_method" class="custom-select col-12">
 					<?php
						$pmq = $ac->prepare("SELECT * FROM pay_methods ");
						$pmq->execute();
						while ($pm = $pmq->fetch(PDO::FETCH_ASSOC)) {
							if ($pm["currency"] == "tl") {
								$pr = "₺";
							} elseif ($pm["currency"] == "euro") {
								$pr = "$";
							} elseif ($pm["currency"] == "dollar") {
								$pr = "€";
							} else {
								$pr = "";
							}
						?>
 						<option value="<?php echo $pm["id"]; ?>"><?php echo "[$pr] - " . $pm["title"]; ?></option>
 					<?php } ?>


 				</select>
 			</div>
 			<label class="col-md-2">
 				Satış hakkında notlar:
 			</label>
 			<div class="col-md-4">

 				<textarea name="desc" value="" class="form-control" type="text"></textarea>
 			</div>
 		</div>


 	</form>
 </div>
 