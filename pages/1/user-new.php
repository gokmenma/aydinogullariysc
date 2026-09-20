<?php
permcontrol("useradd");


if ($_POST) {

	if (!$_POST["uemail"] || !$_POST["uname"] || !$_POST["upassword"]) {

		header("Location: index.php?p=new-user&st=empties");
		exit;
	}

	$contpid = $ac->prepare("SELECT email FROM users WHERE email = ?");
	$email = $_POST["uemail"];
	$contpid->execute(array($email));
	$result = $contpid->fetch(PDO::FETCH_ASSOC);

	if ($result) {
		// Eğer veritabanında bir sonuç varsa, bu e-posta ile kayıtlı bir kullanıcı var
		//showAlert('alert', 'Bu adı taşıyan bir kullanıcı zaten mevcut.');
		header("Location: index.php?p=user-new&st=thereuser");
		exit;
	}



	$fullName = @$_POST["uname"];
	$uemail = @$_POST["uemail"];
	$unvan = @$_POST["unvan"];
	$upassword = md5(md5(md5($_POST["upassword"])));
	$uperm = 1;
	$perm = @$_POST["permission"];
	$ugsm = @$_POST["ugsm"];
	$meslek = $_POST["meslek"];
	$odasicilno = $_POST["odasicilno"];
	$yetkinlikno = $_POST["yetkinlikno"];
	$ekipnetno = $_POST["ekipnetno"];



	try {

		$regg = $ac->prepare("INSERT INTO users SET
										username = ?, 	
										password = ?,
										unvan = ?,
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
										statu = ? ");

	$regg->execute(array($fullName, $upassword, $unvan, $uemail, $ugsm, $uperm, $perm, TODAY, sesset("id"), 
									$meslek,$odasicilno, $yetkinlikno,$ekipnetno, 1));
		header("Location: index.php?p=user-edit&st=newsuccess");
	} catch (PDOException $e) {
		echo "Hata: " . $e->getMessage();
	}

	if ($regg) {
		header("Location: index.php?p=user-new&st=newsuccess");
	}
}

if (@$_GET["st"] == "empties") {
	showAlert('alert', "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.!");
}
if ($_GET["st"] == "newsuccess") {
	showAlert("success", "İşlem Başarı ile tamamlandı!");

}
if ($_GET["st"] == "thereuser") {
	showAlert("alert", "Bu email adresi ile kullanıcı kaydı yapılmıştır!");
}

?>




<form enctype="multipart/form-data" id="myForm" method="POST">
    <div class="user-manage-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-user-plus"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $pdat["p_title"] ?? 'Ekip Üyesi Ekle'; ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-info-circle"></i> Yeni Ekip Üyesi Tanımlama
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
                        <i class="fa fa-save"></i> Kaydet
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
                    <input required type="text" name="uname" id="uname" value="<?php echo htmlspecialchars($uname ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Ad Soyad giriniz">
                </div>

                <!-- E-Posta -->
                <div class="form-field">
                    <label for="uemail"><font color="red">(*)</font> E-Posta:</label>
                    <input required name="uemail" id="uemail" type="email" value="<?php echo htmlspecialchars($uemail ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="E-Posta adresi giriniz">
                </div>

                <!-- Parola -->
                <div class="form-field">
                    <label for="upassword"><font color="red">(*)</font> Parola:</label>
                    <input required name="upassword" id="upassword" type="text" class="form-control" placeholder="Giriş parolası giriniz">
                </div>

                <!-- Telefon -->
                <div class="form-field">
                    <label for="ugsm">Telefon:</label>
                    <input name="ugsm" id="ugsm" type="text" value="<?php echo htmlspecialchars($ugsm ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Telefon numarası giriniz">
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
                            <option value="<?php echo $pm["id"]; ?>">
                                <?php echo $pm["roleName"]; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Unvanı -->
                <div class="form-field">
                    <label for="unvan">Unvanı :</label>
                    <input name="unvan" id="unvan" type="text" class="form-control" placeholder="Mühendis, Tekniker vb.">
                </div>

                <!-- Mesleği -->
                <div class="form-field full-width">
                    <label for="meslek">Mesleği:</label>
                    <input name="meslek" id="meslek" type="text" value="<?php echo htmlspecialchars($meslek ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Meslek dalı giriniz">
                </div>
            </div>
        </div>

        <!-- Kart 2: Sicil Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-purple">
                    <i class="fa fa-id-card-o"></i>
                </div>
                <div>
                    <h5>Sicil ve Yetkinlik Bilgileri</h5>
                    <p>Ekip üyesinin oda kayıtları, ekipnet ve sertifika yetkinlik numaraları</p>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Ekipnet No -->
                <div class="form-field">
                    <label for="ekipnetno">Ekipnet No:</label>
                    <input name="ekipnetno" id="ekipnetno" type="text" value="<?php echo htmlspecialchars($ekipnetno ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Ekipnet numarasını giriniz">
                </div>

                <!-- Oda Sicil No -->
                <div class="form-field">
                    <label for="odasicilno">Oda Sicil No:</label>
                    <input name="odasicilno" id="odasicilno" type="text" value="<?php echo htmlspecialchars($odasicilno ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Oda sicil numarasını giriniz">
                </div>

                <!-- Yetkinlik No -->
                <div class="form-field">
                    <label for="yetkinlikno">Yetkinlik No:</label>
                    <input name="yetkinlikno" id="yetkinlikno" type="text" value="<?php echo htmlspecialchars($yetkinlikno ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Yetkinlik numarasını giriniz">
                </div>
            </div>
        </div>
    </div>
</form>


</div>
<!-- Input Validation End -->