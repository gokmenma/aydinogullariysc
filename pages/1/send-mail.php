<?php
permcontrol("mailandsmssend");
ini_set('display_errors', 1);
error_reporting(E_ALL);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

//MÜSTERİLER SAYFASINDAN MAİL GÖNDERMEK İÇİN
    $customer_id = isset($_GET['customer']) ? decrypt($_GET['customer']) : 0 ;

if ($_POST) {
	$dosya_adi = $_FILES["dosya"]["name"];
	
	//Dosya yükleme işlemini gerçekleştir
	if(isset($dosya_adi)){

		if ($_FILES["dosya"]["error"] === UPLOAD_ERR_OK) {
			$dosya_adi = $_FILES["dosya"]["name"];
			$dosya_yolu = $_FILES["dosya"]["tmp_name"];

			$dizin = "files/";
			$rast1 = rand(1, 100);
			$hedef = $dizin . $rast1 . "_" . basename($dosya_adi);

			if (move_uploaded_file($dosya_yolu, $hedef)) {
				// Dosya başarıyla yüklendi
				// E-posta gönderme işlemine devam et
			} else {
				echo "Dosya yükleme hatası.";
				
			}
		} else {
			echo "Dosya yükleme hatası: " . $_FILES["dosya"]["error"];
			
		}
	}	


	try {

			if (!empty($_POST["customers"])) {
				$mailkonu = $_POST["mailkonu"];
				$mailicerik = $_POST["mailicerik"];
				$mail_from = $_POST["mail_address"];


				// include ("include/mailer/class.phpmailer.php");
                //require 'include/mailler/src/PHPMailer.php';
                
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->SMTPDebug = 2;
				$mail->SMTPAuth = true;
           
				$mail->SMTPSecure   = 'tls'; // Güvenli bağlantı için tls kullanıyoruz
				$mail->Host         = set("mail_host"); // Mail sunucusunun adresi (IP de olabilir)
				$mail->Port         = set("mail_port");
				$mail->IsHTML(true);
   				$mail->Encoding     = 'base64';
				$mail->SetLanguage("tr", "phpmailer/language");
				$mail->Username     = set('mail_username'); // Gönderici adresiniz (e-posta adresiniz)
				$mail->Password     = set('mail_password'); // Mail adresimizin sifresi
				$mail->setFrom($mail_from, set("company_name"));

				foreach ($_POST["customers"] as $cust) {
					$mail->AddAddress($cust); // Gönderilen Alıcı
					$mailto .= $cust . "|";
				}

				$mail->AddAttachment($hedef); // Yüklenen dosyayı ekle
				$mail->Subject      = $mailkonu;
				$mail->Body         = $mailicerik;
                $mail->CharSet      = "UTF-8";
				if ($mail->Send()) {

					//Eğer başarılı ise veritabanına kayıt edilir
					$sql= $ac->prepare("INSERT INTO mail_logs SET tomail = ?, from_mail = ?, mail_file = ? , mail_body = ? ,sender = ?");
					$sql->execute(array($mailto,$mail_from,$dosya_adi,$mailicerik,sesset("id")));
					header("Location: index.php?p=send-mail&send=true");

				} else {
					header("Location:index.php?p=send-mail&st=unsuccessful");

				}
			} else {
				header("Location:index.php?p=send-mail&st=nocustom");

			}
			
	} catch (PDOException $e) {
		echo "Error: " . $e->getMessage();
		
	}
		
}

if (@$_GET["st"] == "unsuccessful") {
	showAlert("alert", "E-posta gönderimi başarısız oldu.");
}
if (@$_GET["st"] == "nocustom") {
	showAlert("alert", "En az 1 müşteri seçmelisiniz");
}
if (@$_GET["send"] == "true") {
	showAlert("success", "Mail başarı ile gönderildi!");
}
?>

<form method="POST" id="myForm" enctype="multipart/form-data">
    <div class="send-mail-manage-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-paper-plane"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $pdat["p_title"] ?? 'Mail Gönder'; ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-info-circle"></i> Toplu & Bireysel E-Posta Gönderim Paneli
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <button type="button" id="submitButton" onclick="validateForm()" class="btn-header btn-header-save">
                        <i class="fa fa-paper-plane"></i> Mail Gönder
                    </button>
                </div>
            </div>
        </div>

        <!-- Kart 1: Alıcı & Gönderici Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-envelope-o"></i>
                </div>
                <div>
                    <h5>E-Posta Konfigürasyonu</h5>
                    <p>Gönderici hesabı ve alıcı firmaları seçin, e-posta konusunu ve dosya eklerini belirleyin</p>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Gönderen Mail Adresi -->
                <div class="form-field">
                    <label for="mail_address"><font color="red">(*)</font> Gönderen Mail Adresi:</label>
                    <select name="mail_address" id="mail_address" class="selectpicker form-control" data-style="border bg-white">
                        <?php 
                        $sql = $ac->prepare("SELECT * from mail_accounts where mail_user = ? or mail_user = ?");
                        $sql->execute(array(1,sesset("id")));
                        while ($row = $sql->fetch(PDO::FETCH_ASSOC)){
                            echo "<option value='".$row["mail_address"]."' > ". $row["mail_address"] . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Firma Seçimi -->
                <div class="form-field">
                    <label for="customers"><font color="red">(*)</font> Firma Seçimi :</label>
                    <select required name="customers[]" id="customers" class="selectpicker form-control" data-style="border bg-white"
                        multiple data-actions-box="true" data-selected-text-format="count">
                        <?php
                        $mcek = $ac->prepare("SELECT * FROM customers");
                        $mcek->execute();
                        while ($mm = $mcek->fetch(PDO::FETCH_ASSOC)) {
                            $selected = $customer_id == $mm['id'] ? ' selected' : '';
                            ?>
                            <option <?php echo $selected ;?> value="<?php echo $mm["email"]; ?>"><?php echo $mm["company"]; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>

                <!-- Konu Başlığı -->
                <div class="form-field">
                    <label for="mailkonu"><font color="red">(*)</font> Konu Başlığı :</label>
                    <input autocomplete="off" required type="text" class="form-control" name="mailkonu" id="mailkonu" placeholder="E-posta konusunu giriniz">
                </div>

                <!-- Ek -->
                <div class="form-field">
                    <label for="dosya">Dosya Eki (Opsiyonel):</label>
                    <input class="form-control form-control-sm" name="dosya" id="dosya" type="file" style="height: auto; padding: 8px 14px;">
                </div>
            </div>
        </div>

        <!-- Kart 2: Mail İçeriği -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="card-icon card-icon-purple">
                        <i class="fa fa-pencil-square-o"></i>
                    </div>
                    <div>
                        <h5>Mail İçeriği</h5>
                        <p>Gönderilecek e-posta metnini ve detaylarını hazırlayın</p>
                    </div>
                </div>
                <!-- Şablon Yönetimi -->
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-tooltip="Şablon Olarak Kaydet"
                        data-tooltip-location="bottom" style="border-radius: 8px; padding: 6px 12px; font-size: 13px;">
                        <i class="fa fa-save"></i> Şablon Kaydet
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-tooltip="Şablondan Aktar"
                        data-tooltip-location="bottom" style="border-radius: 8px; padding: 6px 12px; font-size: 13px;">
                        <i class="fa fa-hand-o-right"></i> Şablondan Yükle
                    </button>
                </div>
            </div>
            <div class="editor-wrapper">
                <textarea required class="textarea_editor form-control border-radius-8" name="mailicerik" placeholder="E-posta içeriğinizi yazınız..."></textarea>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    $(".selectpicker").selectpicker({
        noneSelectedText: "Listeden Firma Seçiniz!",
        size: 8,
        deselectAllText: "Seçimi Temizle",
        selectAllText: "Tümünü Seç",
        countSelectedText: "{0} Firma seçildi",
        liveSearch: "true"
    })
});
</script>