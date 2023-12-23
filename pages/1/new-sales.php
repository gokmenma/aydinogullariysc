 <?php

 permcontrol("sadd");
 if(set("sale_view") != "1"){
  ?>
 <div class="alert alert-warning" role="alert">
                <b>Dikkat!</b> Yeni satış oluşturduğunuzda, belirlediğiniz başlangıç - bitiş tarihleri arasında otomatik ödeme planları oluşturulur. Ödemeler sayfasından takibini yapabilirsiniz. Oluşturulan satışa ait bilgiler düzenlenemez. Bu mesaj sadece 1 kez gösterilir.
</div>
  <?php


    $regupa = $ac->prepare("UPDATE settings SET val = ? WHERE var = ?");
    $regupa->execute(array("1","sale_view"));

}
if($_POST){

	$cid = @$_POST["cid"];
	$sid = @$_POST["sid"];
	$price = @$_POST["price"];
	$pay_type = $_POST["pay_type"];
	$pay_method = $_POST["pay_method"];
	$startdate = date_tr($_POST["startdatex"]);
	$enddate = date_tr($_POST["enddatex"]);
	$desc = $_POST["desc"];




	if(empty($price) || empty($startdate) || empty($enddate)){
		header("Location: index.php?p=new-sales&st=empties");
		exit;
	}

	if(!is_numeric($price)){
		header("Location:index.php?p=new-sales&st=numericerror");
		exit;
	}
	$colorray = array("yellow","blue-50","blue","green","yellow");

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

	$insq->execute(array($cid,$sid,$price,$pay_type,$pay_method,$desc,$startdate,$enddate,0,1,0));



	$paysx = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
	$paysx->execute(array($pay_method));
	$payme = $paysx->fetch(PDO::FETCH_ASSOC);
	$paymet = $payme["title"];


		$shw = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
		$shw->execute(array($pay_method));
		$sh = $shw->fetch(PDO::FETCH_ASSOC);

		$ak = $sh["total"]+$price;

		

		if($pay_type == 1){
			$sklk = "Aylık";
		}elseif($pay_type == 2){
			$sklk = "Yıllık";
		}else{
			$sklk = "Tek Seferlik";
		}
		$ccs = $ac->prepare("SELECT * FROM customers WHERE id = ?");
		$ccs->execute(array($cid));
		$cinf = $ccs->fetch(PDO::FETCH_ASSOC);


	
	/* ÖDEME KAYITLARI */
		$pmet = $_POST["pay_method"];
	if($pay_type == "1"){
			
						$say = 0;
						while($say < 13){

								$paydate = date("d-m-Y", strtotime($startdate ." +$say months") );
								
								if(dtf($paydate,$enddate) < 0){
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
								$sys = $say+1;
								$insertle->execute(array($cid,$say,$cck["id"],$paydate,$pmet,"aylik",0));





								$say++;

						}


}elseif($pay_type == 2){


						$say = 0;
						while($say < 5){

								$paydate = date("d-m-Y", strtotime($startdate ." +$say year") );
								if(dtf($paydate,$enddate) < 0){
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
								$sys = $say+1;
								$insertle->execute(array($cid,$sys,$cck["id"],$paydate,$pmet,"yillik",0));





								$say++;

						}



}else{



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
				$insertle->execute(array($cid,$sys,$cck["id"],$paydate,$pmet,"tekseferlik",0));
}

				header("Location: index.php?p=sales&st=newsuccess");
	



}

if(@$_GET["st"] == "empties"){

		showAlert('alert', "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.");

	}
	if(@$_GET["st"] == "newsuccess" ){

		showAlert('success',"Bilgiler kaydedildi.");

	}else if(@$_GET["st"] == "numericerror"){

		showAlert('warning',"Fiyat kısmına sadece rakamlardan oluşan değer girebilirsiniz.");

	}
?>

<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
					<div class="clearfix">
						<div class="pull-left">
							<h4 class="text-blue"><?php echo $pdat["p_title"];?></h4>
							<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br>Not: Satışlarınız, gelir-gider sayfasına otomatik olarak yansımaktadır. <br><font color="red">Tarih kısımlarını "gg-aa-yyyy" formatına uygun şekilde girmeye özen göstermelisiniz.</font></p>
						</div>
						<div class="form-group">
							<input type="submit" id="submitbutton" value="Satış Yap" style="float:right" class="btn btn-success"><br><br>
						</div>
					</div>

<form method="POST" id="myform" action="">
	
	<div class="row">
		<div class="col-md-6 col-sm-12">
				<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Müşteri:</label>
			<select name="cid" class="custom-select col-12">
				<option disabled selected="">Seçiniz..</option>
				<?php
					$ms = $ac->prepare("SELECT * FROM customers ");
					$ms->execute();
					while($mm = $ms->fetch(PDO::FETCH_ASSOC)){
						
				?>
				<option value="<?php echo $mm["id"];?>"><?php echo $mm["name"]; ?></option>
				<?php
					
				}
				?>
			</select>
		</div>
			
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Ürün/Hizmet Seçimi</label>
			<select name="sid" class="custom-select col-12">
				<option disabled selected="">Hizmet Seçimi</option>
				<?php
					$msx = $ac->prepare("SELECT * FROM services ");
					$msx->execute();
					while($mms = $msx->fetch(PDO::FETCH_ASSOC)){
						if($mms["stitle"]){
				?>
				<option value="<?php echo $mms["id"];?>"><?php echo $mms["stitle"]; ?></option>
				<?php
					}
				}
				?>
			</select>
		</div>
	</div><div class="col-md-6 col-sm-12">
				<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Fiyat :</label>
			<input class="custom-select col-12"type="" name="price" placeholder="₺ - $ gibi birimler girmeyiniz">
		</div>
			
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Ödeme Sıklığı</label>
			<select name="pay_type" class="custom-select col-12">
				<option value="1">Aylık</option>
				<option value="2">Yıllık</option>
				<option value="3">Tek Seferlik</option>
				
			</select>
		</div>
	</div>
<div class="col-md-6 col-sm-12">
				<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Başlangıç Tarihi:</label>
			<input class="form-control date-picker" type="text" name="startdatex" placeholder="GG-AA-YYYY">
		</div>
			
		</div>
		

		<div class="col-md-6 col-sm-12">
				<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Bitiş Tarihi:</label>
			<input class="form-control date-picker"type="text" name="enddatex" placeholder="GG-AA-YYYY">
		</div><br>
			
	
	</div>
	<div class="form-group row col-md-12 col-sm-12">

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Varsayılan Ödeme Kanalı</label>
			<select name="pay_method" class="custom-select col-12">
				<?php
					$pmq = $ac->prepare("SELECT * FROM pay_methods ");
					$pmq->execute();
					while($pm = $pmq->fetch(PDO::FETCH_ASSOC)){
						if($pm["currency"] == "tl"){
    			$pr = "₺";
    		}elseif($pm["currency"] == "euro"){
    			$pr = "$";
    		}elseif($pm["currency"] == "dollar"){
    			$pr = "€";
    		}else{
    			$pr = "";
    		}
				?>
				<option value="<?php echo $pm["id"]; ?>"><?php echo "[$pr] - ".$pm["title"]; ?></option>
			<?php } ?>

				
			</select>
		</div><br><br><br><br>

	</div>


	<div class="col-md-11 col-sm-12">
			<div class="form-group"> 
				<label>Satış hakkında notlar </label>
				<textarea name="desc" value="" class="form-control" type="text" ></textarea>
				
			</div>
		</div>

	</div><br>
	



</form>
							</div>
<script>
	document.getElementById("submitbutton").addEventListener("click",function(){
		var form=document.getElementById("myform");
		form.submit();
	})
</script>