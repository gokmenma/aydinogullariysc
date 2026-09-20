<?php

$uid = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($uid <= 0) {
	header("Location:index.php?p=users");
	exit;
}

permcontrol("useredit");

$conts = $ac->prepare("SELECT * FROM users WHERE id = ?");
$conts->execute(array($uid));
$cc = $conts->fetch(PDO::FETCH_ASSOC);



if ($_POST) {

	if (!$_POST["uname"]) {
		header("Location: index.php?p=user-new&st=empties&id=" . $uid);
		exit;
	}

	if (@$_POST["upassword"] && $_POST["upassword"] != "******") {
		$upassword = md5(md5(md5($_POST["upassword"])));
	} else {
		$upassword = $cc["password"];
	}


	$fullName = $_POST["uname"];
	$uemail = $_POST["uemail"];
	$unvan = $_POST["unvan"];
	$uperm = 1;
	$perm = $_POST["permission"];
	$ugsm = $_POST["ugsm"];
	$meslek = $_POST["meslek"];
	$odasicilno = $_POST["odasicilno"];
	$yetkinlikno = $_POST["yetkinlikno"];
	$ekipnetno = $_POST["ekipnetno"];
	$imza = $_FILES["imza_file"] ?? null;


	try {




		// Mevcut dosya adını veritabanından al
		$stmt = $ac->prepare("SELECT imza_file FROM users WHERE id = ?");
		$stmt->execute([$uid]);
		$currentFile = $stmt->fetch(PDO::FETCH_OBJ)->imza_file;
		$imza_file_name = $currentFile;
		
		if ($imza && $imza["size"] > 0) {
			$imza_file_name = uniqid() . $imza["name"];
			$imza_file_path = $imza["tmp_name"];
			$destination = "files/imzalar/" . $imza_file_name;

			// Mevcut dosya varsa sil
			if ($currentFile && file_exists("files/imzalar/" . $currentFile)) {
				unlink("files/imzalar/" . $currentFile);
			}

			// Yeni dosyayı yükle
			if (move_uploaded_file($imza_file_path, $destination)) {
				echo "Dosya başarıyla yüklendi.";
			} else {
				throw new Exception("Dosya yükleme başarısız.");
			}
		} 
		
		



		$regg = $ac->prepare("UPDATE users SET
										username = ?, 	
										password = ?,
										unvan = ? ,
										email = ?, 
										gsm = ?,
										perm = ?, 
										permission = ?, 
										regdate = ?,
										creativer = ?, 
										meslek = ?, 
										odasicilno = ?, 
										yetkinlikno = ?, 
										ekipnetno = ?, 
										statu = ?,
										imza_file = ?
										WHERE id = ? ");

		$regg->execute(array(
			$fullName,
			$upassword,
			$unvan,
			$uemail,
			$ugsm,
			$uperm,
			$perm,
			TODAY,
			sesset("id"),
			$meslek,
			$odasicilno,
			$yetkinlikno,
			$ekipnetno,
			1,
			$imza_file_name,
			$uid
		));
		header("Location: index.php?p=user-edit&st=newsuccess&id=" . $uid);
	} catch (PDOException $e) {
		echo "Hata: " . $e->getMessage();
	}




	// if ($regg) {
	// 	//header("Location:index.php?p=user-edit&id=$uid");
	// 	header("Location: index.php?p=user-edit&st=newsuccess&id=" . $uid);
	// }
}
if (@$_GET["st"] == "empties") {
	showAlert("alert", "Zorunlu alanları doldurun");
}


if (@$_GET["st"] == "newsuccess") {
	showAlert("success", "Ekip üyesi Başarı ile güncellendi!");
}


$query = $ac->prepare("SELECT * FROM users WHERE id = ?");
$query->execute(array($uid));
$user = $query->fetch(PDO::FETCH_ASSOC);

?>



<form enctype="multipart/form-data" id="myForm" method="POST">
    <div class="user-manage-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $pdat["p_title"] ?? 'Ekip Üyesi Düzenle'; ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-tag"></i> Kullanıcı ID: #<?php echo $uid; ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <?php if (permtrue("userview")) { ?>
                        <a href="index.php?p=users" class="btn-header btn-header-list mr-2">
                            <i class="fa fa-list"></i> Listeye Dön
                        </a>
                    <?php } ?>
                    <button type="button" id="submitButton" onclick="validateForm()" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Güncelle
                    </button>
                </div>
            </div>
        </div>

        <!-- Kart 1: Kişisel Bilgiler & Pozisyon -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-user"></i>
                </div>
                <div>
                    <h5>Kişisel Bilgiler & Yetki Pozisyonu</h5>
                    <p>Ekip üyesinin adı, iletişim adresleri, giriş parolası ve rol yetkisi</p>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Adı Soyadı -->
                <div class="form-field">
                    <label for="uname"><font color="red">(*)</font> Adı Soyadı :</label>
                    <input required type="text" name="uname" id="uname" value="<?php echo htmlspecialchars($user["username"] ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Ad Soyad giriniz">
                </div>

                <!-- E-Posta -->
                <div class="form-field">
                    <label for="uemail"><font color="red">(*)</font> E-Posta:</label>
                    <input required name="uemail" id="uemail" type="email" value="<?php echo htmlspecialchars($cc["email"] ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="E-Posta adresi giriniz">
                </div>

                <!-- Parola -->
                <div class="form-field">
                    <label for="upassword"><font color="red">(*)</font> Parola:</label>
                    <input required name="upassword" id="upassword" type="text" value="******" class="form-control" placeholder="Giriş parolası giriniz">
                </div>

                <!-- Telefon -->
                <div class="form-field">
                    <label for="ugsm">Telefon:</label>
                    <input name="ugsm" id="ugsm" type="text" value="<?php echo htmlspecialchars($cc["gsm"] ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Telefon numarası giriniz">
                </div>

                <!-- Pozisyon -->
                <div class="form-field">
                    <label for="permission"><font color="red">(*)</font> Pozisyon:</label>
                    <select name="permission" id="permission" class="selectpicker form-control" data-style="border bg-white" data-container="body">
                        <?php
                        $pquery = $ac->prepare("SELECT * FROM userroles");
                        $pquery->execute();
                        while ($pm = $pquery->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <option <?php echo $cc["permission"] == $pm["id"] ? "selected" : ""; ?> value="<?php echo $pm["id"]; ?>">
                                <?php echo $pm["roleName"]; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Unvanı -->
                <div class="form-field">
                    <label for="unvan">Unvanı :</label>
                    <input name="unvan" id="unvan" type="text" value="<?php echo htmlspecialchars($cc["Unvan"] ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Mühendis, Tekniker vb.">
                </div>

                <!-- Mesleği -->
                <div class="form-field full-width">
                    <label for="meslek">Mesleği:</label>
                    <input name="meslek" id="meslek" type="text" value="<?php echo htmlspecialchars($cc["meslek"] ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Meslek dalı giriniz">
                </div>
            </div>
        </div>

        <!-- Kart 2: Sicil & İmza Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-purple">
                    <i class="fa fa-id-card-o"></i>
                </div>
                <div>
                    <h5>Sicil, Yetkinlik ve İmza Bilgileri</h5>
                    <p>Ekip üyesinin oda kayıtları, ekipnet numaraları ve dijital imza belgesi</p>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Ekipnet No -->
                <div class="form-field">
                    <label for="ekipnetno">Ekipnet No:</label>
                    <input name="ekipnetno" id="ekipnetno" type="text" value="<?php echo htmlspecialchars($cc["ekipnetno"] ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Ekipnet numarasını giriniz">
                </div>

                <!-- Oda Sicil No -->
                <div class="form-field">
                    <label for="odasicilno">Oda Sicil No:</label>
                    <input name="odasicilno" id="odasicilno" type="text" value="<?php echo htmlspecialchars($cc["odasicilno"] ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Oda sicil numarasını giriniz">
                </div>

                <!-- Yetkinlik No -->
                <div class="form-field">
                    <label for="yetkinlikno">Yetkinlik No:</label>
                    <input name="yetkinlikno" id="yetkinlikno" type="text" value="<?php echo htmlspecialchars($cc["yetkinlikno"] ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Yetkinlik numarasını giriniz">
                </div>

                <!-- İmza Yükleme -->
                <div class="form-field">
                    <label for="imza_file">İmza Dosyası:</label>
                    <input name="imza_file" id="imza_file" type="file" class="form-control btn-sm">
                    <?php if ($cc["imza_file"]) { ?>
                        <div class="mt-2" style="font-size: 12.5px; color: #64748b;">
                            <i class="fa fa-file-image-o"></i> Mevcut İmza: 
                            <a href="files/imzalar/<?php echo $cc["imza_file"]; ?>" target="_blank" class="text-blue weight-600">
                                Görüntüle
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</form>
