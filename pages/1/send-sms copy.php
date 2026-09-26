  <?php

   permcontrol("mlogview");


   if(set("sms_active") != "on"){

  		header("Location: index.php");
   		exit;

   }
    if(set("sms_viewx") != "1"){
  ?>
 <div class="alert alert-warning" role="alert">
                <b>Dikkat!</b> SMS Sistemi Yalnızca NetGSM ile çalışmaktadır. Farklı sağlayacılar kullanmak ve sistemde kullanmak istiyorsanız. www.ahmetcakmak.net adresinden fiyat teklifi alabilirsiniz. Bu mesaj yalnızca bir kere gösterilecektir.
</div>
  <?php


    $regupa = $ac->prepare("UPDATE settings SET val = ? WHERE var = ?");
    $regupa->execute(array("1","sms_viewx"));

}


if($_POST){

	if(strlen($_POST["message"]) < 160){

		if($_POST["customers"]){

	try {
	$client = new SoapClient("http://soap.netgsm.com.tr:8080/Sms_webservis/SMS?wsdl");

	$msg  = $_POST["message"];
	$gsm  = $_POST["customers"];

	$username = set("sms_username");
	$pass = set("sms_pass");
	$title = set("sms_title");

	$Result = $client -> smsGonder1NV2(array('username'=> $username, 'password' => $pass, 'header' => $title, 'msg' => $msg, 'gsm' => $gsm,  'filter' => '', 'startdate'  => '', 'stopdate'  => '', 'encoding' => 'TR'  ));

	if($Result){
		header("Location:index.php?p=send-sms&st=newsuccess");
	}

	} catch (Exception $exc)
	 {
	 // Hata olusursa yakala
		header("Location:index.php?p=send-sms&st=errorsms");
	}

			
		}else{
			header("Location:index.php?p=send-sms&st=nocustom");
		}

	}else{
		header("Location:index.php?p=send-sms&st=long");
	}

}



if(@$_GET["st"] == "empties"){
		?>
			<div class="alert alert-danger" role="alert">
								(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
							</div>
		<?php
	}if(@$_GET["st"] == "long"){
		?>
			<div class="alert alert-danger" role="alert">
								Mesaj uzunluğunuz 160 karakterden uzun olmamalı.
							</div>
		<?php
	}if(@$_GET["st"] == "nocustom"){
		?>
			<div class="alert alert-danger" role="alert">
								En az 1 müşteri seçmelisiniz
							</div>
		<?php
	}
	if(@$_GET["st"] == "newsuccess" ){
		?>
	<div class="alert alert-success" role="alert">
									SMS başarıyla gönderildi. .
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
							<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
						</div>
						
					</div>
<form method="POST" action="index.php?p=send-sms&post=true">
	
	<div class="row">
		
		
			
	<div class="col-md-13 col-sm-12">
			<div class="form-group"> 
				<label><font color="red">(*)</font>Mesaj İçeriği <font color="red">(En fazla 160 karakter olmasına dikkat ediniz.)</font> </label>
				<textarea name="message" value="" class="form-control" type="text" ></textarea>
				
			</div>
		</div>
<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><font color="red">(*)</font>Müşteri Seçimi</label>
									<select name="customers[]" class="selectpicker form-control" data-style="btn-outline-success" multiple data-actions-box="true" data-selected-text-format="count">
										<?php 
										$mcek = $ac->prepare("SELECT * FROM customers ORDER BY name ASC");
										$mcek->execute();
										while($mm = $mcek->fetch(PDO::FETCH_ASSOC)){
										?>
											<option value="9<?php echo $mm["gsm"];?>"><?php echo $mm["name"]." - [ ".$mm["yetkili"]." - ".$mm["gsm"]." ]"; ?></option>
										<?php
										}


										 ?>
										

									</select>
								</div>
							
		
	</div>
	</div><br>
	


<input type="submit" value="SMS Gönder" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
</form>
							</div>