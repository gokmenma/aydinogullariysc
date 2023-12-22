
  <?php
  permcontrol("payview");
if(!$_GET["tid"]){
	header("Location:index.php");
	exit;
}
$id = $_GET["tid"];

$conts = $ac->prepare("SELECT * FROM payments WHERE id = ?");
$conts->execute(array($id));
$cont = $conts->fetch(PDO::FETCH_ASSOC);
if($cont["okey"] == 0){
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
	
if(@$_GET["st"] == "empties"){
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
							<p class="mb-30 font-14">Bu sayfada ödemenin hangi ödeme kanalına yapıldığını görüntüleyebilirsiniz.  <br></p>
						</div>
						
					</div>
<form method="POST" action="">
	
	<div class="row">
		<div class="col-md-6 col-sm-12">
				<div class="col-sm-12 col-md-12"><label>Müşteri:</label>
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

		<div class="col-sm-12 col-md-12"><label>Ürün/Hizmet Seçimi</label>
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

				if($xcx["currency"] == "tl"){
								    			$pr = "₺";
								    		}elseif($xcx["currency"] == "euro"){
								    			$pr = "€";
								    		}elseif($xcx["currency"] == "dollar"){
								    			$pr = "$";
								    		}else{
								    			$pr = "";
								    		}
				?>
			</select>
		</div>
	</div><div class="col-md-6 col-sm-12">
				<div class="col-sm-12 col-md-12"><label>Fiyat:</label>
			<input class="custom-select col-12"type="" name="price" disabled value="<?php echo $xcx["price"];?>">
		</div>
			
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12"><label>Ödeme Sıklığı</label>
			<select disabled name="pay_type" class="custom-select col-12">
				<option <?php echo $cont["type"] == "aylik" ? "selected" : ""; ?> value="1">Aylık</option>
				<option <?php echo $cont["type"] == "yillik" ? "selected" : ""; ?> value="2">Yıllık</option>

				
			</select>
		</div>
	</div>

	<div class="form-group row col-md-12 col-sm-12">

		<div class="col-sm-12 col-md-12"><label>Ödeme Kanalı</label>
			<select disabled name="pay_method" class="custom-select col-12">
				<?php
					$pmq = $ac->prepare("SELECT * FROM pay_methods ");
					$pmq->execute();
					while($pm = $pmq->fetch(PDO::FETCH_ASSOC)){
							if($pm["currency"] == "tl"){
								$pr = "₺";
							}elseif($pm["currency"] == "euro"){
								$pr = "€";
							}elseif($pm["currency"] == "dollar"){
								$pr = "$";
							}else{
								$pr = "";
							}
				?>
				<option <?php echo $pm["id"] == $cont["pay_method"] ? "selected" : "";?> value="<?php echo $pm["id"]; ?>"><?php echo $pm["title"]." [$pr]"; ?></option>
			<?php } ?>

				
			</select>
		</div><br><br><br><br>
		
	</div>


	</div><br>
</form>
							</div>