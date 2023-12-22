 <?php

 permcontrol("mlogview");
if($_POST){

	if($_POST["customers"]){

	
	$mailkonu = $_POST["mailkonu"];
	$mailicerik  = $_POST["mailicerik"];
	

		include("include/mailer/class.phpmailer.php");

		foreach($_POST["customers"] as $karsit){

		
			$mail = new PHPMailer();
			$mail->IsSMTP();
			$mail->SMTPDebug = 1; // Hata ayıklama değişkeni: 1 = hata ve mesaj gösterir, 2 = sadece mesaj gösterir
			$mail->SMTPAuth = true; //SMTP doğrulama olmalı ve bu değer değişmemeli
			$mail->SMTPSecure = ''; // Normal bağlantı için boş bırakın veya tls yazın, güvenli bağlantı kullanmak için ssl yazın
			$mail->Host = set("mail_host"); // Mail sunucusunun adresi (IP de olabilir)
			$mail->Port = set("mail_port"); // Normal bağlantı için 587, güvenli bağlantı için 465 yazın
			$mail->IsHTML(true);
			$mail->SetLanguage("tr", "phpmailer/language");
			$mail->Encoding = 'base64';
			$mail->CharSet  ="utf-8";
			$mail->Username = set("mail_username"); // Gönderici adresiniz (e-posta adresiniz)
			$mail->Password = set("mail_password"); // Mail adresimizin sifresi
			$mail->SetFrom(set("mail_username"), set("company_name"));
			$mail->AddAddress($karsit); // Gönderilen Alıcı
			$mail->Subject = $mailkonu; // Email konu başlığı
			$mail->Body = $mailicerik; // Mailin içeriği
			if($mail->Send()){
				header("Location: index.php?p=cancelled-payments&st=newsuccess");
			} else {
			  	echo "olmadı";
			}	
		}
	
	

			
		
	}else{
		header("Location:index.php?p=send-mail&st=nocustom");
	}

	

}



if(@$_GET["st"] == "empties"){
		?>
			<div class="alert alert-danger" role="alert">
								(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
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
									Mail başarıyla iletildi. .
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
<form method="POST" action="index.php?p=send-mail&posted=true">
	
	<div class="row">
		
		<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label>Mail Konu</label>
									<input required type="text" class="form-control" name="mailkonu">
								</div>
							
		
	</div>
			
	<div class="col-md-13 col-sm-12">
			<div class="form-group"> 
				<label><font color="red">(*)</font>Mail İçeriği  </label>
				<textarea required class="textarea_editor form-control border-radius-0" name="mailicerik" value="" class="form-control" type="text" ></textarea>
				
			</div>
		</div>
<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label>Müşteri Seçimi</label>
									<select required name="customers[]" class="selectpicker form-control" data-style="btn-outline-success" multiple data-actions-box="true" data-selected-text-format="count">
										<?php
										$grups = $ac->prepare("SELECT * FROM cgroups ");
										$grups->execute();

										while($grg = $grups->fetch(PDO::FETCH_ASSOC)){
										?>
										<optgroup label="<?php echo $grg["title"]; ?>">
										<?php 
										$mcek = $ac->prepare("SELECT * FROM customers WHERE grp = ? ORDER BY name ASC");
										$mcek->execute(array($grg["id"]));
										while($mm = $mcek->fetch(PDO::FETCH_ASSOC)){
										?>
											<option value="<?php echo $mm["email"];?>"><?php echo $mm["name"]; ?></option>
										<?php
										}
										?>
									</optgroup>
										<?php
									}

										 ?>
										

									</select>
								</div>
							
		
	</div>
	</div><br>
	


<input type="submit" value="Mail Gönder" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
</form>
							</div>