<?php

define('CIPHER', 'AES-128-CBC');
define('KEY', 'aydinogullariysc.2024');

function set($vars)
{
	global $ac;

	$setque = $ac->prepare('SELECT * FROM settings WHERE var = ?');
	$setque->execute(array($vars));
	$data = $setque->fetch(PDO::FETCH_ASSOC);

	return $data['val'];
}

function sesset($vars)
{
	$sid = $_SESSION['lid'];
	global $ac;

	$setques = $ac->prepare('SELECT * FROM users WHERE id = ?');
	$setques->execute(array($sid));
	$datas = $setques->fetch(PDO::FETCH_ASSOC);

	return $datas[$vars];
}

function pfail()
{
	header('Location: logout.php?pfailed=true');
	exit;
}

function sadmin()
{
	if (sesset('id') == 1) {
		return true;
	} else {
		pfail();
	}
}

function uset($varsx, $tit)
{
	global $ac;

	$setquesx = $ac->prepare('SELECT * FROM users WHERE id = ?');
	$setquesx->execute(array($varsx));
	$dataxc = $setquesx->fetch(PDO::FETCH_ASSOC);

	return $dataxc[$tit];
}

function permd()
{
	$sid = sesset('perm');
	global $ac;

	$setques = $ac->prepare('SELECT * FROM perms WHERE id = ?');
	$setques->execute(array($sid));
	$datasdat = $setques->fetch(PDO::FETCH_ASSOC);

	return $datasdat['p_title'];
}

function dtf($date1, $date2)
{
	$dt1 = strtotime($date1);
	$dt2 = strtotime($date2);

	$rep = ($dt2 - $dt1) / 86400;
	return round($rep);
}

function title_show($ttlink)
{
	global $ac;
	$ttquery = $ac->prepare('SELECT * FROM pages WHERE p_link = ?');
	$ttquery->execute(array($ttlink));
	$tts = $ttquery->fetch(PDO::FETCH_ASSOC);

	return $tts['p_title'];
}

function date_tr($datx)
{
	return $datx;
}

function redate_tr($datx)
{
	return $datx;
}

function shorted($kelime, $str = 10)
{
	if (strlen($kelime) > $str) {
		if (function_exists('mb_substr'))
			$kelime = mb_substr($kelime, 0, $str, 'UTF-8') . '..';
		else
			$kelime = substr($kelime, 0, $str) . '..';
	}
	return $kelime;
}

// Kullanımı

function repdate($a)
{
	explode('-', $a);

	return $a[2] . '-' . $a[1] . '-' . $a[0];
}

function send_sms($phones, $message)
{
	$username = trim((string)set('sms_username'));
	$title    = trim((string)set('sms_title'));
	$pass     = (string)set('sms_pass');
	$active   = set('sms_active');

	if ($active !== 'on' || empty($username) || empty($title) || empty($pass)) {
		return [
			'success' => false,
			'message' => 'SMS entegrasyonu aktif değil veya giriş bilgileri eksik.',
			'code'    => 'CONFIG_ERROR'
		];
	}

	if (empty($phones) || empty(trim((string)$message))) {
		return [
			'success' => false,
			'message' => 'Alıcı listesi veya mesaj metni boş olamaz.',
			'code'    => 'INPUT_ERROR'
		];
	}

	// Alıcı numaralarını temizle (sadece rakam, min 10 hane)
	$cleanPhones = [];
	$rawPhones = is_array($phones) ? $phones : explode(',', (string)$phones);
	foreach ($rawPhones as $p) {
		$p = preg_replace('/[^0-9]/', '', (string)$p);
		if (strlen($p) === 10) {
			$cleanPhones[] = $p;
		} elseif (strlen($p) === 11 && strpos($p, '0') === 0) {
			$cleanPhones[] = substr($p, 1);
		} elseif (strlen($p) === 12 && strpos($p, '90') === 0) {
			$cleanPhones[] = substr($p, 2);
		} elseif (!empty($p)) {
			$cleanPhones[] = $p;
		}
	}
	$cleanPhones = array_values(array_unique(array_filter($cleanPhones)));

	if (empty($cleanPhones)) {
		return [
			'success' => false,
			'message' => 'Geçerli bir telefon numarası bulunamadı.',
			'code'    => 'NO_VALID_PHONE'
		];
	}

	try {
		if (class_exists('SoapClient')) {
			$client = new SoapClient("http://soap.netgsm.com.tr:8080/Sms_webservis/SMS?wsdl", [
				'trace' => 1,
				'exceptions' => true,
				'connection_timeout' => 10
			]);

			$result = $client->smsGonder1NV2([
				'username'  => $username,
				'password'  => $pass,
				'header'    => $title,
				'msg'       => $message,
				'gsm'       => $cleanPhones,
				'filter'    => '',
				'startdate' => '',
				'stopdate'  => '',
				'encoding'  => 'TR'
			]);

			$resCode = is_string($result) ? trim($result) : (string)$result;
			if (strpos($resCode, '00 ') === 0 || strpos($resCode, '01 ') === 0 || strpos($resCode, '02 ') === 0 || $resCode === '00' || $resCode === '01') {
				return [
					'success'  => true,
					'message'  => 'SMS başarıyla iletildi.',
					'job_id'   => $resCode,
					'count'    => count($cleanPhones)
				];
			} else {
				$errorMap = [
					'20' => 'Mesaj metni hatalı veya karakter sınırı aşıldı.',
					'30' => 'Geçersiz NetGSM kullanıcı adı, şifre veya API erişim kısıtlaması.',
					'40' => 'Gönderici başlığı (Originator) NetGSM sisteminde onaylı değil.',
					'50' => 'Abone hesabınızda yeterli SMS kredisi bulunmuyor.',
					'51' => 'Abonelik veya hesap durumu SMS gönderimine kapalı.',
					'70' => 'Hatalı parametre veya geçersiz numara formatı.',
					'80' => 'Gönderim sınırına ulaşıldı.',
					'85' => 'Mükerrer gönderim engellendi.'
				];
				$msg = $errorMap[$resCode] ?? "NetGSM Hata Kodu: {$resCode}";
				return [
					'success' => false,
					'message' => $msg,
					'code'    => $resCode
				];
			}
		} else {
			// HTTP POST Fallback to api.netgsm.com.tr
			$postFields = http_build_query([
				'usercode'  => $username,
				'password'  => $pass,
				'gsmno'     => implode(',', $cleanPhones),
				'message'   => $message,
				'msgheader' => $title,
				'dil'       => 'TR'
			]);

			$ch = curl_init('https://api.netgsm.com.tr/sms/send/get');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			$response = curl_exec($ch);
			curl_close($ch);

			$resCode = trim((string)$response);
			if (strpos($resCode, '00 ') === 0 || $resCode === '00') {
				return [
					'success'  => true,
					'message'  => 'SMS başarıyla iletildi.',
					'job_id'   => $resCode,
					'count'    => count($cleanPhones)
				];
			}
			return [
				'success' => false,
				'message' => 'SMS gönderilemedi (NetGSM Kod: ' . $resCode . ')',
				'code'    => $resCode
			];
		}
	} catch (Exception $e) {
		error_log('SMS Send Error: ' . $e->getMessage());
		return [
			'success' => false,
			'message' => 'SMS servisi bağlantı hatası: ' . $e->getMessage(),
			'code'    => 'EXCEPTION'
		];
	}
}

// function send_mail($titlek, $text, $sendto)
// {

// 	include ("include/mailer/class.phpmailer.php");
// 	$mail = new PHPMailer();
// 	$mail->IsSMTP();
// 	$mail->SMTPDebug = 1; // Hata ayıklama değişkeni: 1 = hata ve mesaj gösterir, 2 = sadece mesaj gösterir
// 	$mail->SMTPAuth = true; //SMTP doğrulama olmalı ve bu değer değişmemeli
// 	$mail->SMTPSecure = ''; // Normal bağlantı için boş bırakın veya tls yazın, güvenli bağlantı kullanmak için ssl yazın
// 	$mail->Host = set("mail_host"); // Mail sunucusunun adresi (IP de olabilir)
// 	$mail->Port = set("mail_port"); // Normal bağlantı için 587, güvenli bağlantı için 465 yazın
// 	$mail->IsHTML(true);
// 	$mail->SetLanguage("tr", "phpmailer/language");
// 	$mail->CharSet = "utf-8";
// 	$mail->Username = set("mail_username"); // Gönderici adresiniz (e-posta adresiniz)
// 	$mail->Password = set("mail_password"); // Mail adresimizin sifresi
// 	$mail->SetFrom(set("mail_from"), set("mail_name"));
// 	$mail->AddAddress($sendto); // Gönderilen Alıcı
// 	$mail->Subject = set("company_name") . " - Bilgi"; // Email konu başlığı
// 	$mail->Body = $text; // Mailin içeriği
// 	if (!$mail->Send()) {
// 		return false;
// 	} else {
// 		return true;
// 	}
// }

function send_mail($send_file_name, $pdf_content, $customer, $creator)
{
	include('../include/mailer/class.phpmailer.php');
	// PDF dosyasını sunucuda geçici olarak saklayın

	/* $pdf_content = pdf olarak hazırlanan içerik */
	/* $sender_mail = göndericek olan kullanıcının mail adresi */

	$user_id = $_SESSION['lid'];
	$sender_mail = getUserInfo($user_id, 'email');


	file_put_contents($send_file_name, $pdf_content);

	// // E-posta ekini tanımlayın
	$attachment = chunk_split(base64_encode(file_get_contents($pdf_file)));
	try {
		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->SMTPDebug = 2;
		$mail->SMTPAuth = true;

		$mail->SMTPSecure = 'tls';  // Güvenli bağlantı için tls kullanıyoruz
		$mail->Host = set('mail_host');  // Mail sunucusunun adresi (IP de olabilir)
		$mail->Port = set('mail_port');
		$mail->IsHTML(true);
		$mail->SetLanguage('tr', 'phpmailer/language');
		$mail->Encoding = 'base64';

		$mail->Username = set('mail_username');  // Gönderici adresiniz (e-posta adresiniz)
		$mail->Password = set('mail_password');  // Mail adresimizin sifresi
		$mail->SetFrom($sender_mail, set('company_name'));
		// $mail->AddAddress($customer['email']);  // Gönderilen Alıcı
		$mail->AddAddress('beyzade83@hotmail.com');  // Gönderilen Alıcı

		$mail->AddAttachment($pdf_file);  // Yüklenen dosyayı ekle
		$mail->Subject = 'Teklif Maili';
		$mail->Body = 'Teklifimiz ekte sunulmuştur';
		$mail->CharSet = 'utf-8';

		if ($mail->Send()) {
			$sql = $ac->prepare('INSERT INTO mail_logs SET tomail = ?, from_mail = ? , mail_body= ?, statu = ? ,mail_file =?, sender = ?');
			$sql->execute(array($customer['email'], $sender_mail, $mail->Body, 1, $pdf_file, $creator['id']));

			header('Location: index.php?p=offers&st=success-mail');
		} else {
			header('Location:index.php?p=offers&st=unsuccessful');
		}
	} catch (phpmailerException $e) {
		echo $e->errorMessage();
	}

	// PDF dosyasını sunucudan silin
	unlink($pdf_file);
}

function permfalse($var)
{
	global $ac;
	$pcheck = $ac->prepare('SELECT * FROM perms WHERE id = ?');
	$pcheck->execute(array(sesset('permission')));
	$pd = $pcheck->fetch(PDO::FETCH_ASSOC);
	$pin = @$_GET['p'];
	if ($pin) {
		if ($pd[$var] == 'on') {
			return false;
		} else {
			return true;
		}
	} else {
		true;
	}
}

function authid($authName)
{
	$sid = sesset('perm');
	global $ac;

	$setques = $ac->prepare('SELECT * FROM authority WHERE authName = ? ');
	$setques->execute(array($authName));
	$data = $setques->fetch(PDO::FETCH_ASSOC);

	return $data['id'] ?? 0;
}

function permtrue($var)
{
	global $ac;
	$authid = authid($var);
	$pcheck = $ac->prepare('SELECT * FROM userauths WHERE roleId = ? and authID = ?');
	$pcheck->execute(array(sesset('permission'), $authid));
	$auth = $pcheck->fetchAll(PDO::FETCH_ASSOC);
	$pin = $_GET['p'] ?? "saayfa yok";

	
	if ($pin) {
		if (count($auth) > 0) {
			return true;
		} else {
			return false;
		}
	} else {
		return true;
	}
}

function permcontrol($var)
{
	global $ac;
	$authid = authid($var);
	$pcheck = $ac->prepare('SELECT * FROM userauths WHERE roleId = ? and authID = ?');
	$pcheck->execute(array(sesset('permission'), $authid));
	$auth = $pcheck->fetchAll(PDO::FETCH_ASSOC);
	$pin = @$_GET['p'];
	if ($pin) {
		if (count($auth) > 0) {
			return true;
		} else {
			header('Location:index.php?error=nopermission');
			exit;
		}
	} else {
		true;
	}
}

// functions.php dosyasındaki eski fonksiyonun YERİNE bunu koyun.

function checkAuth($var)
{
    global $ac;

    // 1. Kullanıcının bir yetki ID'si var mı kontrol et. Yoksa direkt false döndür.
    $user_permission_id = sesset('permission');
    if (!$user_permission_id) {
        return false;
    }

    // 2. İstenen iznin ('offercopy' gibi) ID'sini al.
    // authid() fonksiyonunun veritabanına sorgu yaptığını varsayıyorum.
    $authid = authid($var);
    if (!$authid) {
        // Eğer 'offercopy' gibi bir izin sistemde kayıtlı değilse, yetkisi olamaz.
        return false;
    }

    // 3. Kullanıcının rolü ile istenen izni eşleştiren bir kayıt var mı diye kontrol et.
    try {
        $pcheck = $ac->prepare('SELECT 1 FROM userauths WHERE roleId = ? and authID = ? LIMIT 1');
        $pcheck->execute(array($user_permission_id, $authid));

        // fetchColumn() bir sonuç bulursa sütun değerini (1), bulamazsa false döndürür.
        // Bu, fetchAll() yapıp sonra count() ile saymaktan çok daha performanslıdır.
        if ($pcheck->fetchColumn()) {
            return true; // Eşleşme bulundu.
        } else {
            return false; // Eşleşme bulunamadı.
        }

    } catch (PDOException $e) {
        // Veritabanı hatası olursa loglayıp false döndürmek en güvenlisidir.
        // error_log("Yetki kontrol hatası: " . $e->getMessage());
        return false;
    }
}

// function permcontrol($var)
// {
// 	global $ac;
// 	$pcheck = $ac->prepare("SELECT * FROM userauths WHERE roleId = ? and authID = ?");
// 	$pcheck->execute(array(sesset("permission")));
// 	$pd = $pcheck->fetch(PDO::FETCH_ASSOC);
// 	$pin = @$_GET["p"];
// 	if ($pin) {
// 		if ($pd[$var] == "on") {
// 		} else {
// 			header("Location:index.php?error=nopermission");
// 			exit;
// 			die;
// 		}
// 	} else {
// 		true;
// 	}
// }

// function permtrue($var)
// {
// 	global $ac;
// 	$pcheck = $ac->prepare("SELECT * FROM perms WHERE id = ?");
// 	$pcheck->execute(array(sesset("permission")));
// 	$pd = $pcheck->fetch(PDO::FETCH_ASSOC);
// 	$pin = @$_GET["p"];
// 	if ($pin) {
// 		if ($pd[$var] == "on") {

// 			return true;
// 		} else {
// 			return false;
// 		}
// 	} else {
// 		true;
// 	}
// }

function encrypt($data)
{
	return openssl_encrypt($data, CIPHER, KEY);
}

function decrypt($data)
{
	return openssl_decrypt($data, CIPHER, KEY);
}

function showAlert($type, $message, $link = '')
{
	$pValue = isset($_GET['p']) ? $_GET['p'] : null;
	$id = isset($_GET['id']) ? $_GET['id'] : null;

	if (empty($id) || $id == null) {
		$routelink = $pValue . $link;
	} else {
		$routelink = $pValue . '&id=' . $id . $link;
	}
	?>
	<script>
		showMessage('<?php echo $message ?>', '<?php echo $type ?>', '<?php echo $routelink ?>');
	</script>

	<?php
}

function ParaBirimleri($name, $val, $id)
{
	echo '<select id=' . $id . ' required name="' . $name . '" class="selectpicker form-control" 
	data-container="body" data-style="bg-white" data-header="Para Birimi">';

	$secenekler = array('TRY', 'USD', 'EUR');
	foreach ($secenekler as $secenek) {
		if ($secenek == $val) {
			echo '<option value="' . $secenek . '" selected>' . $secenek . '</option>';
		} else {
			echo '<option value="' . $secenek . '">' . $secenek . '</option>';
		}
	}


	echo '</select>';
}

function customers($name, $val, $required = 'required')
{
	global $ac;
	$selectedOption = '';
	if (!empty($val)) {
		$sql = $ac->prepare("SELECT id, company FROM customers WHERE id = ?");
		$sql->execute(array($val));
		$row = $sql->fetch(PDO::FETCH_ASSOC);
		if ($row && !empty(trim($row['company']))) {
			$selectedOption = '<option value="' . $row['id'] . '" selected>' . htmlspecialchars($row['company']) . '</option>';
		}
	}

	echo '<select id="' . $name . '" ' . $required . ' name="' . $name . '" class="form-control ajax-customer-select">
	<option value="">Müşteri Seçiniz</option>
	' . $selectedOption . '
	</select>';
}

function yangin_sondurme_sinifi($name, $val)
{
	echo '<select required name="' . $name . '" class="selectpicker form-control" 
    data-container="body" data-style="bg-white">';

	global $ac;

	$siniflar = ['KÖPÜKLÜ SİSTEM', 'GAZLI SİSTEM', 'TOZLU SİSTEM'];

	foreach ($siniflar as $sinif) {
		if ($sinif == $val) {
			echo '<option value="' . $sinif . '" selected>' . $sinif . '</option>';
		} else {
			echo '<option value="' . $sinif . '">' . $sinif . '</option>';
		}
	}

	echo '</select>';
}

function regions($name, $val)
{
	echo '<select required name="' . $name . '" class="selectpicker form-control" 
    data-container="body" data-style="border bg-white">';

	global $ac;

	$durumlar = ['Marmara', 'Doğu Anadolu', 'İ'];

	foreach ($durumlar as $durum) {
		if ($durum == $val) {
			echo '<option value="' . $durum . '" selected>' . $durum . '</option>';
		} else {
			echo '<option value="' . $durum . '">' . $durum . '</option>';
		}
	}

	echo '</select>';
}

function aciliyet_durumu($name, $val)
{
	echo '<select required name="' . $name . '" class="selectpicker form-control" 
    data-container="body" data-style="border bg-white">';

	global $ac;

	$durumlar = ['Çok Acil', 'Acil', 'Acil Değil'];

	foreach ($durumlar as $durum) {
		if ($durum == $val) {
			echo '<option value="' . $durum . '" selected>' . $durum . '</option>';
		} else {
			echo '<option value="' . $durum . '">' . $durum . '</option>';
		}
	}

	echo '</select>';
}

function servisDurum($name, $val, $className = 'selectpicker form-control')
{
	echo '<select id="' . $name . '" required name="' . $name . '" class="' . $className . '" 
    data-container="body" data-style="border bg-white" data-placeholder="Servis Durumu Seçiniz">';

	global $ac;

	$sql = $ac->prepare('SELECT * FROM units where statu = ?');
	$sql->execute(array(4));
	while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
		if ($row['id'] == $val) {
			echo '<option value="' . $row['id'] . '" selected>' . htmlspecialchars($row['title']) . '</option>';
		} else {
			echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['title']) . '</option>';
		}
	}

	echo '</select>';
}

//Sözleşme durumu : Sözleşme Bekliyor, Sözleşme Yapıldı, Sözleşme Yapılmadı

const SOZLESMEDURUMU = [
	'' => 'Seçiniz',
	'1' => 'Sözleşme Bekliyor',
	'2' => 'Sözleşme Yapıldı',
	'3' => 'Sözleşme Yapılmadı',
	'4' => 'S.Kapsamında Değildir'
];

function sozlesmeDurumu($name, $val, $className = 'selectpicker form-control')
{
	echo '<select required 
	name="' . $name . '" 
	id="' . $name . '" 
	class="' . $className . '" 
	data-container="body" data-style="border bg-white" data-placeholder="Sözleşme Durumu Seçiniz">';
	foreach (SOZLESMEDURUMU as $key => $value) {
		$style =  '';
		switch ($key) {
			case $val:
				echo "<option value='{$key}' {$style} selected >" . htmlspecialchars($value) . "</option>";
				break;
			default:
				echo "<option value='{$key}' {$style} >" . htmlspecialchars($value) . "</option>";
				break;
		}

	}

	echo '</select>';
}

//Servis Durumu badge olarak gösterilir

function getSozlesmeStatusBadge($id)
{
	$badgeClass = 'badge-secondary';
	if ($id == 1)
		$badgeClass = 'badge-warning';
	else if ($id == 2)
		$badgeClass = 'badge-success';
	else if ($id == 3 || $id == 4)
		$badgeClass = 'badge-danger';
	$statusText = SOZLESMEDURUMU[$id] ?? 'Seçiniz';
	//<span class="badge badge-primary">Primary</span>
	return "<span class='badge " . $badgeClass . "'>$statusText</span>";
}

function getStatusBadge($id)
{
	global $ac;
	$badgeClass = '#777';
	$statusText = 'Tanımsız';
	$sql = $ac->prepare('SELECT * FROM units where statu = ? and  id = ? ');
	$sql->execute(array(4, $id));
	$result = $sql->fetch(PDO::FETCH_ASSOC);

	if ($result) {
		$badgeClass = (!empty($result['colour'])) ? $result['colour'] : '#777';
		$statusText = $result['title'];
	}
	return "<span style='background-color:" . $badgeClass . "' class='badge'>$statusText</span>";
}

function servisNo($name, $val)
{
	echo '<select required name="' . $name . '" class="selectpicker form-control" 
    data-container="body" data-style="border bg-white">';

	global $ac;
	$sql = $ac->prepare('SELECT * FROM projects');
	$sql->execute();
	while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
		if ($row['id'] == $val) {
			echo '<option value="SN' . $row['id'] . '" selected>SN' . $row['id'] . '</option>';
		} else {
			echo '<option value="SN' . $row['id'] . '">SN' . $row['id'] . '</option>';
		}
	}

	echo '</select>';
}

function users($name, $val)
{
	echo '<select id=' . $name . ' required name="' . $name . '" class="selectpicker form-control" 
    data-container="body" data-style="bg-white">
	<option selected disabled value="">Kullanıcı seçiniz!</option>
	';
	global $ac;
	$sql = $ac->prepare('SELECT * FROM users where id != ?');
	$sql->execute(array(1));
	while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
		if ($row['id'] == $val) {
			echo '<option value="' . $row['id'] . '" selected>' . $row['username'] . '</option>';
		} else {
			echo '<option value="' . $row['id'] . '">' . $row['username'] . '</option>';
		}
	}
	echo '</select>';
}

function userandjob($name, $val)
{
	echo '<select required name="' . $name . '" class="selectpicker form-control" 
    data-container="body" data-style="border bg-white">
	<option selected disabled value="">Mühendis seçiniz!</option>
	';

	global $ac;
	$sql = $ac->prepare('SELECT * FROM users where id != ?');
	$sql->execute(array(1));
	while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
		if ($row['id'] == $val) {
			echo '<option value="' . $row['id'] . '" selected>' . $row['username'] . ' - ' . $row['meslek'] . '</option>';
		} else {
			echo '<option value="' . $row['id'] . '">' . $row['username'] . ' - ' . $row['meslek'] . '</option>';
		}
	}
	echo '</select>';
}

function units($name, $val, $type)
{
	echo '<select required name="' . $name . '" class="selectpicker form-control" 
    data-container="body" data-style="bg-white">';

	global $ac;
	// $secenekler = array("Döviz Alış", "Döviz Satış", "Efektif Alış","Efektif Satış");
	$sql = $ac->prepare('SELECT * FROM units WHERE statu = ?');
	$sql->execute(array($type));
	while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
		if ($row['title'] == $val) {
			echo '<option value="' . $row['id'] . '" selected>' . $row['title'] . '</option>';
		} else {
			echo '<option value="' . $row['id'] . '">' . $row['title'] . '</option>';
		}
	}

	echo '</select>';
}

function offerTemplate($name, $val, $type, $className = 'selectpicker form-control')
{
	echo '<select id="' . $name . '" required name="' . $name . '" class="' . $className . '" 
    data-container="body" data-style="bg-white" data-placeholder="Şablon Seçiniz">';

	global $ac;
	$sql = $ac->prepare('SELECT * FROM offertemplate where State = ?');
	$sql->execute(array($type));
	echo "<option value=''>Şablon Seçiniz</option>";
	while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
		if ($row['id'] == $val) {
			echo '<option value="' . $row['id'] . '" selected>' . htmlspecialchars($row['Title']) . '</option>';
		} else {
			echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['Title']) . '</option>';
		}
	}

	echo '</select>';
}

//offerTemplateContent
function offerTemplateContent($id)
{
	global $ac;
	$sql = $ac->prepare('SELECT Content FROM offertemplate where id = ?');
	$sql->execute(array($id));
	$row = $sql->fetch(PDO::FETCH_OBJ);
	return $row->Content;
}

function checkemail($str)
{
	return (!preg_match('/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix', $str)) ? FALSE : TRUE;
}

// function KurTuru($name, $val)
// {
// 	echo '<select required name="' . $name . '" class="selectpicker form-control"
// 	data-container="body" data-style="border bg-white">';

// 	$secenekler = array("Döviz Alış", "Döviz Satış", "Efektif Alış","Efektif Satış");
// 	foreach ($secenekler as $secenek) {
// 		if ($secenek == $val) {
// 			echo '<option value="' . $secenek . '" selected data-content="<span class=\'badge badge-warning\'>'. $secenek .'</span>" ></option>';
// 		} else {
// 			echo '<option value="' . $secenek . '">' . $secenek . '</option>';
// 		}
// 	}

// 	echo '</select>';
// }

function KurTuru($name, $val)
{
	echo '<select id="currency" required name="' . $name . '" class="selectpicker form-control" data-container="body" data-style="bg-white">';
	echo '<option disabled>Ödeme Vadesini Seçiniz</option>';

	// Seçeneklerin listesini oluştur
	$options = array(
		array('label' => 'Döviz Alış', 'value' => 'Döviz Alış', 'class' => 'success'),
		array('label' => 'Döviz Satış', 'value' => 'Döviz Satış', 'class' => 'warning'),
		array('label' => 'Efektif Alış', 'value' => 'Efektif Alış', 'class' => 'success'),
		array('label' => 'Efektif Satış', 'value' => 'Efektif Satış', 'class' => 'warning')
	);

	// Her seçeneği yazdır
	foreach ($options as $option) {
		$selected = ($val == $option['value']) ? 'selected' : '';  // Eğer $val, bu seçeneğin değerine eşitse 'selected' olacak
		echo '<option ' . $selected . ' data-content="<span class=\'badge badge-' . $option['class'] . "'>" . $option['label'] . '</span>">' . $option['label'] . '</option>';
	}

	echo '</select>';
}

function getcurType()
{
}

function OdemeVadesi($name, $val)
{
	echo '<select required name="' . $name . '" class="selectpicker form-control" data-container="body" data-style="bg-white">';
	echo '<option disabled>Ödeme Vadesini Seçiniz</option>';

	// Seçeneklerin listesini oluştur
	$options = array(
		array('label' => 'Peşin', 'value' => 'Peşin', 'class' => 'light'),
		array('label' => '3 Ay', 'value' => '3 Ay', 'class' => 'success'),
		array('label' => '6 Ay', 'value' => '6 Ay', 'class' => 'info'),
		array('label' => '9 Ay', 'value' => '9 Ay', 'class' => 'warning'),
		array('label' => '12 Ay', 'value' => '12 Ay', 'class' => 'danger')
	);

	// Her seçeneği yazdır
	foreach ($options as $option) {
		$selected = ($val == $option['value']) ? 'selected' : '';  // Eğer $val, bu seçeneğin değerine eşitse 'selected' olacak
		echo '<option ' . $selected . ' data-content="<span class=\'badge badge-' . $option['class'] . "'>" . $option['label'] . '</span>">' . $option['label'] . '</option>';
	}

	echo '</select>';
}

function KdvOranları($name, $val)
{
	echo '<select id="' . $name . '" required name="' . $name . '" class="selectpicker form-control" data-container="body" data-style="bg-white">
            <option disabled>Oran Seçiniz </option>
            <option ' . ($val == 20 ? 'selected' : '') . ' value="20">%20</option>
            <option ' . ($val == 18 ? 'selected' : '') . ' value="18">%18</option>
            <option ' . ($val == 10 ? 'selected' : '') . ' value="10">%10</option>
            <option ' . ($val == 8 ? 'selected' : '') . ' value="8">%8</option>
            <option ' . ($val == 1 ? 'selected' : '') . ' value="1">%1</option>
            <option ' . ($val !== null && $val !== '' && $val == 0 ? 'selected' : '') . ' value="0">%0</option>
        </select>';
}

// function OlcuBirimleri($name, $val, $required, $id)
// {
// 	echo '<select ' . $required . ' name="' . $name . '" id= "' . $id . '" class="selectpicker form-control col-md-12" '
// 		. 'data-container="body" data-style="border bg-white" data-header="Birimi">';
// 	// echo '<option disabled value="">Birim Seçiniz</option>';

// 	$birimler = array('Kg', 'Ad.', 'Gram', 'Mt', 'Litre', 'm2');
// 	foreach ($birimler as $birim) {
// 		if ($birim == $val) {
// 			echo '<option value="' . $birim . '" selected>' . $birim . '</option>';
// 		} else {
// 			echo '<option value="' . $birim . '">' . $birim . '</option>';
// 		}
// 	}

// 	echo '</select>';
// }
function OlcuBirimleri($name, $val)
{
	global $ac;
	$sql = $ac->prepare("SELECT * FROM units WHERE statu = ?");
	$sql->execute([1]);

	while ($unit = $sql->fetchAll(PDO::FETCH_OBJ)) {
		echo '<select id =' . $name . '  name=' . $name . ' class="selectpicker form-control col-md-12" data-container="body" data-style="bg-white" required>
		<option disabled selected="">Birim Seçiniz </option>';
		foreach ($unit as $u) {
			echo '<option ' . ($val == $u->title ? 'selected' : '') . ' value="' . $u->title . '">' . $u->title . '</option>';
		}
		echo '</select>';
	}
}
function OlcuBirimleriValID($name, $val)
{
	global $ac;
	$sql = $ac->prepare("SELECT * FROM units WHERE statu = ?");
	$sql->execute([1]);

	while ($unit = $sql->fetchAll(PDO::FETCH_OBJ)) {
		echo '<select id =' . $name . '  name=' . $name . ' class="selectpicker form-control col-md-12" data-container="body" data-style="bg-white" required>
		<option disabled selected="">Birim Seçiniz </option>';
		foreach ($unit as $u) {
			echo '<option ' . ($val == $u->id ? 'selected' : '') . ' value="' . $u->id . '">' . $u->title . '</option>';
		}
		echo '</select>';
	}
}

function optionselect($name, $val, $required = '', $id = '', $colwidth = '', $type = '1')
{
	echo '<select ' . $required . ' name="' . $name . '"class="selectpicker form-control ' . $colwidth . '" '
		. 'data-container="body" data-style="bg-white">';
	echo '<option disabled >Seçiniz</option>';
	if ($type == 1) {
		$options = array('UYGUN', 'UYGUN DEĞİL');
	} else if ($type == 2) {
		$options = array('VAR', 'YOK');
	} else if ($type == 3) {
		$options = array('EVET', 'HAYIR');
	}
	$optionval = array('1', '0');
	$i = '0';
	foreach ($options as $option) {
		if ($option == $val) {
			echo '<option value="' . $optionval[$i] . '" selected>' . $option . '</option>';
		} else {
			echo '<option value="' . $optionval[$i] . '">' . $option . '</option>';
		}
		$i++;
	}

	echo '</select>';
}

function getTableColumns($tableName)
{
	global $ac;  // $ac değişkeni global olarak tanımlanmalı veya fonksiyon içinde tanımlanmalıdır

	$ttquery = $ac->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'aydinogullariysc3' AND TABLE_NAME = ?");
	$ttquery->execute([$tableName]);

	$columns = '';
	$field = '';
	$insquery = '';
	while ($row = $ttquery->fetch(PDO::FETCH_ASSOC)) {
		if ($row['COLUMN_NAME'] != 'ID') {
			$columns .= '$' . $row['COLUMN_NAME'] . ' = @$_POST["' . $row['COLUMN_NAME'] . '"];' . "\n";
			$field .= $row['COLUMN_NAME'] . ' = ? , ' . "\n";
			$insquery .= '$' . $row['COLUMN_NAME'] . ',';
		}
	}
	$result = $columns . "\n"
		. '$insq = $ac->prepare("INSERT INTO ' . $tableName . ' SET ' . $field . '");' . "\n"
		. '$insq->execute(array(' . $insquery . '));';

	echo '<script> console.log(`' . addslashes($result) . '`); </script>';
}

function generateProductSelect($name, $value, $text = 'Adi', $title = 'Adi', $dataContent = 'StokKodu')
{
	global $ac;  // Eğer $ac değişkeni bu fonksiyonun dışında tanımlandıysa, bu satırı silin

	echo '<select name="' . $name . "\" id=\"productName\" class=\"selectpicker form-control\"\t
				data-container=\"body\" data-size=\"6\" data-live-search=\"true\" data-style=\"bg-white\">";

	$query = $ac->prepare('SELECT * FROM products ORDER BY Adi');
	$query->execute();

	while ($product = $query->fetch(PDO::FETCH_ASSOC)) {
		$isSelected = ($product['ID'] == $value) ? 'selected' : '';  // Seçili mi kontrolü

		echo '<option value="' . $product['ID'] . "\" 
				\t  title=\"" . $product[$title] . "\" 
				\t  data-content=\"
						<div class='col'>
							<h6>" . $product[$text] . "</h6>
							<p style='font-size:14px' class='mb-0'>" . $product[$dataContent] . '</p>"
						' . $isSelected . '>' . $product[$text] . '</option>';
	}

	echo '</select>';
	// echo '<script> console.log(`' . addslashes($product["ID"]) . '`); </script>';
}

function tlFormat($val)
{
	if ($val === null || $val === '')
		return '0,00';
	$tlFormat = number_format($val, 2, ',', '.');
	return $tlFormat;
}

function formatNumber($num)
{
	$formatted = number_format($num, 2, ',', '.');  // Sayıyı formatla
	return str_replace('.', '#', str_replace(',', '.', str_replace('#', ',', $formatted)));  // Nokta ve virgül yer değiştirme
}

function NewCategorySave($categoryName)
{
	global $ac;

	$insq = $ac->prepare('INSERT INTO missioncategory SET categoryName = ? ');
	$insq->execute(array($categoryName));
}

function benzersizStokKodu()
{
	global $ac;

	do {
		$kod = mt_rand(100000, 9999999);
		$query = "SELECT COUNT(*) as count FROM products WHERE StokKodu = 'STK" . $kod . "'";
		$result = $ac->query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		$count = $row['count'];
	} while ($count > 0);

	return $kod;
}

function getUsername($id)
{
	if($id == 0 || $id == '') return '';
	global $ac;
	$sql = $ac->prepare('SELECT * FROM users WHERE id = ?');
	$sql->execute(array($id));
	$userInfo = $sql->fetch(PDO::FETCH_ASSOC);
	return $userInfo['username'] ?? '';
}

function getUserInfo($id, $field = 'username')
{
	global $ac;
	$sql = $ac->prepare('SELECT * FROM users WHERE id = ?');
	$sql->execute(array($id));
	$userInfo = $sql->fetch(PDO::FETCH_ASSOC);
	return $userInfo[$field] ?? '';
}

function getCustomerName($id)
{
	global $ac;
	$sql = $ac->prepare('SELECT * FROM customers WHERE id = ?');
	$sql->execute(array($id));
	$company = $sql->fetch(PDO::FETCH_ASSOC);
	return $company['company'] ?? '';
}

function getMailInfo($id, $field)
{
	global $ac;
	$sql = $ac->prepare('SELECT * FROM mail_accounts WHERE id = ?');
	$sql->execute(array($id));
	$mailinfo = $sql->fetch(PDO::FETCH_ASSOC);
	return $mailinfo[$field] ?? '';
}

function getSettingsField($fieldname)
{
	global $ac;
	$sql = $ac->prepare('SELECT * FROM settings WHERE var = ?');
	$sql->execute(array($fieldname));
	$settingsinfo = $sql->fetch(PDO::FETCH_ASSOC);
	return $settingsinfo['val'] ?? '';
}

/*
 * TCMB Currency Converter
 * https://dogukan.dev
 * http://github.com/dogukanoksuz
 * version: 2.0
 */

function TCMB_Converter($from = 'TRY', $to = 'USD', $val = 1)
{
	// Sistemimizde Simplexml ve Curl fonksiyonları var mı kontrol ediyoruz.
	if (!function_exists('simplexml_load_string') || !function_exists('curl_init')) {
		return 'Simplexml extension missing.';
	}

	// Başlangıç için nereden/nereye değerlerini 1 yapıyoruz çünkü TRY'nin bir karşılığı yok.
	$CurrencyData = [
		'from' => 1,
		'to' => 1
	];

	// XML verisini curl ile alıyoruz, hata var mı yok mu diye try/catch bloklarına alıyoruz.
	try {
		$tcmbMirror = 'https://www.tcmb.gov.tr/kurlar/today.xml';
		$curl = curl_init($tcmbMirror);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_URL, $tcmbMirror);

		$dataFromtcmb = curl_exec($curl);
	} catch (Exception $e) {
		echo 'Unhandled exception, maybe from cURL' . $e->getMessage();
		return 0;
	}

	// XML verisini SimpleXML'e aktararak bir class haline getiriyoruz.
	$Currencies = simplexml_load_string($dataFromtcmb);

	// Bütün verileri foreach ile gezerek arıyoruz ve nereden/nereye değerlerimize eşitliyoruz.
	foreach ($Currencies->Currency as $Currency) {
		if ($from == $Currency['CurrencyCode'])
			$CurrencyData['from'] = $Currency->BanknoteSelling;
		if ($to == $Currency['CurrencyCode'])
			$CurrencyData['to'] = $Currency->BanknoteSelling;
	}

	// Hesaplama işlemini yaparak return ediyoruz.
	return round(($CurrencyData['to'] / $CurrencyData['from']) * $val, 10);
}

function newNumber($tables)
{
	global $ac;  // Eğer $ac değişkeni fonksiyon dışında tanımlanmışsa gerekli olabilir

	$sql = $ac->prepare('SELECT max(id) as maxid FROM ' . $tables);
	$sql->execute();  // Sorguyu yürüt

	$row = $sql->fetch(PDO::FETCH_ASSOC);  // Tek bir satırı al
	if ($row) {
		$sonId = $row['maxid'];
		return $sonId + 1;
	}
}

function tarihDuzenle($tarih)
{
	$tarih = array_reverse(explode('-', $tarih));
	$tarih = implode('.', $tarih);
	return $tarih;
}

// Kar Tutarı Hesaplama
function calculateProfit($buyPrice, $sellPrice)
{
	// Alış ve satış fiyatlarını kontrol et
	if (is_numeric($buyPrice) || is_numeric($sellPrice)) {
		// Karı hesapla
		$profit = $sellPrice - $buyPrice;
	}

	// Sonucu döndür
	return $profit;
}

function servisDurumuKullaniliyormu($id)
{
	global $ac;
	$sql = $ac->prepare('SELECT id FROM projects WHERE servicestype = ?');
	$sql->execute(array($id));
	$result = $sql->fetchColumn();
	if ($result != false) {
		return true;
	} else {
		return false;
	}
}

function setNumber($type)
{
	global $ac;

	$sql = $ac->prepare("SELECT $type FROM define_numbers ");
	$sql->execute();
	$number = $sql->fetchColumn();
	return $number;
}

function isTableExists($tableName)
{
	global $ac;
	$check_table_sql = 'SHOW TABLES LIKE ?';
	$check_table_stmt = $ac->prepare($check_table_sql);
	$check_table_stmt->execute(array($tableName));
	return $check_table_stmt->rowCount() > 0;
}

/**
 * Yapılandırılmış PHPMailer nesnesi döndürür.
 * Belirtilen $fromEmail mail_accounts tablosunda özel şifreye sahipse o kimlik bilgileriyle,
 * aksi halde panel ana SMTP ayarlarıyla hazırlanır.
 */
function get_configured_mailer($fromEmail = null, $fromName = null)
{
	$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
	$mail->isSMTP();
	$mail->SMTPAuth = true;

	$host = trim((string)set('mail_host'));
	$port = (int)set('mail_port');
	$user = trim((string)set('mail_username'));
	$pass = (string)set('mail_password');
	$companyName = $fromName ?: (trim((string)set('company_name')) ?: 'Aydınoğulları YSC');

	$fromAddress = $fromEmail ?: $user;

	if (!empty($fromEmail)) {
		try {
			$mailModel = new \App\Model\MailAccountModel();
			$account = $mailModel->getAccountByAddress($fromEmail);
			if ($account && !empty($account['mail_password'])) {
				$user = $account['mail_address'];
				$pass = $account['mail_password'];
			}
		} catch (\Throwable $t) {
			// Model hatası durumunda ana SMTP ayarları fallback kalır
		}
	}

	$mail->Host     = $host;
	$mail->Port     = $port;
	$mail->Username = $user;
	$mail->Password = $pass;

	if ($port == 465) {
		$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
	} elseif ($port == 587) {
		$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
	} else {
		$mail->SMTPSecure = false;
		$mail->SMTPAutoTLS = true;
	}

	$mail->isHTML(true);
	$mail->CharSet  = 'UTF-8';
	$mail->Encoding = 'base64';

	if (!empty($fromAddress)) {
		$mail->setFrom($fromAddress, $companyName);
		$mail->addReplyTo($fromAddress, $companyName);
	}

	return $mail;
}

