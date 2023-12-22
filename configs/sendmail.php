 <?php

/* Otomatik Gönderim Kontrol */

$cont1 = $ac->prepare("SELECT * FROM reminders WHERE type = ? AND mail = ? AND statu = ?");
$cont1->execute(array("satishatirlatici",1,0));
while($cc = $cont1->fetch(PDO::FETCH_ASSOC)){


$fark = $cc["dates"];

if($fark == TODAY){

$cinf = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$cinf->execute(array($cc["cid"]));
$cinfo = $cinf->fetch(PDO::FETCH_ASSOC);

$sinf = $ac->prepare("SELECT * FROM sales WHERE id = ?");
$sinf->execute(array($cc["sid"]));
$saleinfo = $sinf->fetch(PDO::FETCH_ASSOC);

$serviceinf = $ac->prepare("SELECT * FROM services WHERE id = ?");
$serviceinf->execute(array($saleinfo["sid"]));
$servicei = $serviceinf->fetch(PDO::FETCH_ASSOC);

if($saleinfo["okey"] == 0){
	$isim = $cinfo["name"];
	$hizmet = $servicei["stitle"];
	$tarih = $cc["dates"];
	$sirket = set("company_name");
	$telefon = set("company_phone1");

	###############################################
	
	/* Kullanabileceğiniz Değişkenler */
	/* $isim  -> Müşterinin adı - soyadı */
	/* $hizmet -> Hizmet adı */
	/* $tarih -> Hizmet'e ait sıradaki son ödeme tarihi */
	/* $sirket -> Panel ayarları sayfasından girdiğiniz Şirket Adı  */
	/* $telefon -> Panel ayarları sayfasından girdiğiniz Şirket Telefon Numarası  */
	
	###############################################
	
	/* Sadece 49. satır itibariyle değişiklik yapmalısınız. */
	
	###############################################

	$sendtexts = "<br><br>
			Merhaba Sn. ".$cinfo['name']."<br><br>".$servicei['stitle']." isimli hizmetinizle ilgili ödeme yapılması gerekmektedir.<br>
Son Ödeme Tarihi ".$cc["dates"]." <br>Bu tarihten önce ödeme yaptıysanız bu mesajı dikkate almayınız.
Belirtilen tarihte ödeme yapmamanız durumunda sistem tarafından hizmetiniz durduralacaktır.<br>
Detaylı bilgi almak için iletişime geçebilirsiniz.<br>
<b>".set("company_name")."<br>
".set("company_phone1")."</b>";
		
		$sendto = $cinfo["email"];

		include("include/mailer/class.phpmailer.php");
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
		$mail->SetFrom(set("mail_from"), set("mail_name"));
		$mail->AddAddress($sendto); // Gönderilen Alıcı
		$mail->Subject = "Odeme Hatirlatma"; // Email konu başlığı
		$mail->Body = $sendtexts; // Mailin içeriği
		if($mail->Send()){
			$uprem = $ac->prepare("UPDATE reminders SET
				statu = ? WHERE id = ?");
			$uprem->execute(array(1,$cc["id"]));

		  $inslog = $ac->prepare("INSERT INTO mail_logs SET
		  	tomail = ?,
		  	type = ?,
		  	statu = ?");
		  $inslog->execute(array($sendto,"Hatırlatıcı",1));
		} else {
		  
		}


}

}
}

?>
