
  <?php
  permcontrol("payadd");
if(!$_GET["tid"]){
	header("Location:index.php");
	exit;
}
$id = $_GET["tid"];

$conts = $ac->prepare("SELECT * FROM payments WHERE id = ?");
$conts->execute(array($id));
$cont = $conts->fetch(PDO::FETCH_ASSOC);
if($cont["okey"] == 1){
	header("Location: index.php?p=home");
	exit;
}
$csx = $ac->prepare("SELECT * FROM sales WHERE id = ?");
$csx->execute(array($cont["saleid"]));
$xcx = $csx->fetch(PDO::FETCH_ASSOC);

$price = $xcx["price"];
if(!$cont){
	exit;
}
if($_POST){


	$pay_method = $_POST["pay_method"];


	$upske = $ac->prepare("UPDATE payments SET
	pay_method = ?,
	okey = ? WHERE id = ?");
	$upske->execute(array($pay_method,1,$id));

	$insqa = $ac->prepare("INSERT INTO inexps SET
	type = ?,
	title = ?,
	descs = ?,
	cat = ?,
	sdate = ?,
	pay = ?,
	pay_method = ?");


	$insqa->execute(array("in","Ödeme alındı",TODAY." tarihinde ".sesset("username")." tarafından müşteri için ödeme girişi yapıldı.","Müşteri Ödemesi",TODAY,$price,$pay_method));

	$paysx = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
	$paysx->execute(array($pay_method));
	$payme = $paysx->fetch(PDO::FETCH_ASSOC);
	$paymet = $payme["title"];


	if($insqa){
		$shw = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
		$shw->execute(array($pay_method));
		$sh = $shw->fetch(PDO::FETCH_ASSOC);


		$ak = $sh["total"]+$price;

		$ups = $ac->prepare("UPDATE pay_methods SET
		total = ?, last_action = ? WHERE id = ?");
		$ups->execute(array($ak,date("d-m-Y - H:i:s"),$pay_method));
		$bsk = set("site-title");


		header("Location: index.php?p=info-payment&tid=$id&st=newsuccess");
	}



}

if(@$_GET["st"] == "empties"){ showAlert('success','Başarlıl')
		?>
			<div class="alert alert-danger" role="alert">
								(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
							</div>
		<?php
	}
	if(@$_GET["st"] == "newsuccess" ){
		?>
	<div class="alert alert-success" role="alert">
									Bilgiler kaydedildi.
								</div>
		<?php
	}else if(@$_GET["st"] == "numericerror"){
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
							<h4 class="text-blue"><?php echo $pdat["p_title"];?></h4>
							<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br>Not: Satışlarınız, gelir-gider sayfasına otomatik olarak yansımaktadır. <br></p>
						</div>
						
					</div>
<form method="POST" action="">
	
	<div class="row">
		<div class="col-md-6 col-sm-12">
				<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Müşteri:</label>
			<select disabled name="cid" class="custom-select col-12">

				<?php
					$ms = $ac->prepare("SELECT * FROM customers ");
					$ms->execute();
					while($mm = $ms->fetch(PDO::FETCH_ASSOC)){
						
				?>
				<option <?php echo $mm["id"] == $cont["cid"] ? "selected" : ""; ?> value="<?php echo $mm["id"];?>"><?php echo $mm["name"]; ?></option>
				<?php
					
				}
				?>
			</select>
		</div>
			
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Ürün/Hizmet Seçimi</label>
			<select disabled name="sid" class="custom-select col-12">
				<option disabled selected="">Hizmet Seçimi</option>
				<?php
					$msx = $ac->prepare("SELECT * FROM services ");
					$msx->execute();
					while($mms = $msx->fetch(PDO::FETCH_ASSOC)){
						
						if($mms["stitle"]){
				?>
				<option <?php echo $xcx["sid"] == $mms["id"] ? "selected" : ""; ?> value="<?php echo $mms["id"];?>"><?php echo $mms["stitle"]; ?></option>
				<?php
					}
				}
				?>
			</select>
		</div>
	</div><div class="col-md-6 col-sm-12">
				<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Fiyat :</label>
			<input class="custom-select col-12"type="" name="price" disabled value="<?php echo $xcx["price"];?>">
		</div>
			
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Ödeme Sıklığı</label>
			<select disabled name="pay_type" class="custom-select col-12">
				<option <?php echo $cont["type"] == "aylik" ? "selected" : ""; ?> value="1">Aylık</option>
				<option <?php echo $cont["type"] == "yillik" ? "selected" : ""; ?> value="2">Yıllık</option>

				
			</select>
		</div>
	</div>

	<div class="form-group row col-md-12 col-sm-12">

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Ödeme Şekli</label>
			<select name="pay_method" class="custom-select col-12">
				<?php
					$pmq = $ac->prepare("SELECT * FROM pay_methods ");
					$pmq->execute();
					while($pm = $pmq->fetch(PDO::FETCH_ASSOC)){
							if($pm["currency"] == "tl"){
								$paz = "₺";
							}elseif($pm["currency"] == "dollar"){
								$paz = "$";
							}elseif($pm["currency"] == "euro"){
								$paz = "€";
							}else{
								$paz = "₺";
							}
				?>
				<option value="<?php echo $pm["id"]; ?>"><?php echo $pm["title"]." [$paz]"; ?></option>
			<?php } ?>

				
			</select>
		</div><br><br><br><br>
		
	</div>


	</div><br>
	


<input type="submit" value="Satış Yap" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
</form>
							</div>