<?php
permcontrol("indocadd");
if ($_POST) {
    $firma = $_POST["firma"];
    $evrakturu = $_POST["evrakturu"];
    $kategori = $_POST["kategori"];
    $adet = $_POST["adet"];
    $teslimalan = sesset("id"); 
    $teslimeden = $_POST["teslimeden"];
    $aciklama = $_POST["aciklama"];
	$estatu = $_POST["estatu"];

    if (@$_POST["teslimtarihi"]) {
		$teslimtarihi = $_POST["teslimtarihi"];
	} else {
		$teslimtarihi = TODAY;
	}

    try {
        $kyt = $ac->prepare("INSERT INTO evraktakip SET
            firma = ?,
            evrakturu = ?,
            kategori = ?,
            adet = ?,
            teslimalan = ?,
            teslimeden = ?,
            teslimtarihi = ?,
			estatu = ?,
            aciklama = ?");
        $kyt->execute(array($firma, $evrakturu, $kategori, $adet, $teslimalan, $teslimeden, $teslimtarihi,$estatu, $aciklama));

        if ($kyt->rowCount() > 0) {
            header("Location: index.php?p=new-indocument&st=newsuccess");
        } else {
            header("Location: index.php?p=new-indocument&st=newerror&code=acmd008");
        }
    } catch (PDOException $e) {
        echo "Veri kaydetme hatası: " . $e->getMessage();
    }
}

if (@$_GET["st"] == "empties") {
    ?>
    <div class="alert alert-danger" role="alert">
        (*) ile işaretli alanları boş bırakmadan tekrar deneyin.
    </div>
<?php
}

if (@$_GET["st"] == "newsuccess") {
    showAlert("success", "İşlem Başarı ile tamamlandı!");
}
?>


<form method="POST" action="index.php?p=new-indocument" id="evrakForm">
    <div class="evrak-manage-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-folder-open"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $pdat["p_title"]; ?></h4>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=view-indocument" class="btn-header btn-header-list mr-2">
                        <i class="fa fa-list"></i> Gelen Evrak Listesi
                    </a>
                    <a href="index.php?p=view-outdocument" class="btn-header btn-header-list mr-2">
                        <i class="fa fa-list"></i> Giden Evrak Listesi
                    </a>
                    <button type="submit" id="submitButton" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>

        <!-- Kart 1: Evrak Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-file-text"></i>
                </div>
                <div>
                    <h5>Evrak Bilgileri</h5>
                    <p>Evrağın ait olduğu firma, tür, kategori ve adet bilgileri</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="firma"><font color="red">(*)</font> Firma</label>
                    <select name="firma" id="firma" title="Lütfen Firma Seçiniz" class="selectpicker form-control" data-live-search="true" data-style="btn-outline-secondary" data-selected-text-format="count" data-container="body" required>
						<?php
						$stmt = $ac->prepare("SELECT id, company FROM customers");
						$stmt->execute();
						while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
							$selected = ($row['id'] == $firma) ? 'selected' : '';
							echo '<option value="' . $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['company']) . '</option>';
						}
						?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="evrakturu"><font color="red">(*)</font> Evrak Türü</label>
                    <select required name="evrakturu" id="evrakturu" class="selectpicker form-control" data-style="border bg-white" data-container="body">
                        <option disabled selected value="">Seçim Yapınız</option>	
                        <option value="Gelen">Gelen Evrak</option>
                        <option value="Giden">Giden Evrak</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="kategori"><font color="red">(*)</font> Kategori</label>
                    <input required name="kategori" id="kategori" placeholder="Lütfen kategori yazınız" class="form-control" type="text">
                </div>
                <div class="form-field">
                    <label for="adet"><font color="red">(*)</font> Adet</label>
                    <input required name="adet" id="adet" placeholder="Evrak sayısını girin" class="form-control" type="number" min="1">
                </div>
            </div>
        </div>

        <!-- Kart 2: Teslim Detayları & Durum -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-green">
                    <i class="fa fa-users"></i>
                </div>
                <div>
                    <h5>Teslim Detayları & Durum</h5>
                    <p>Evrağın teslim bilgileri ve güncel durum bilgisi</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label><font color="red">(*)</font> Teslim Eden</label>
                    <input disabled class="form-control" value="<?php echo sesset("username"); ?>" type="text">
                </div>
                <div class="form-field">
                    <label for="teslimeden"><font color="red">(*)</font> Teslim Alan</label>
                    <select name="teslimeden" id="teslimeden" title="Seçiniz" class="selectpicker form-control" data-style="btn-outline-secondary" data-live-search="true" data-container="body" required>
                        <?php
                        $permx = $ac->prepare("SELECT * FROM users ");
                        $permx->execute();
                        while ($px = $permx->fetch(PDO::FETCH_ASSOC)) { ?>
                            <option value="<?php echo $px["id"]; ?>"><?php echo htmlspecialchars($px["username"]); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="teslimtarihi">Teslim Alma Tarihi</label>
                    <input name="teslimtarihi" id="teslimtarihi" type="text" placeholder="Boş bırakırsanız otomatik bugün seçilir." class="form-control date-picker" autocomplete="off">
                </div>
                <div class="form-field">
                    <label for="estatu">Evrak Durumu</label>
                    <select name="estatu" id="estatu" class="selectpicker form-control" data-style="btn-outline-secondary" data-container="body">
                        <option data-content="<span class='badge badge-warning'>Bekliyor</span>" value="Bekliyor">Bekliyor</option>
                        <option data-content="<span class='badge badge-primary'>Çalışıyor</span>" value="Çalışıyor">Çalışıyor</option>
                        <option data-content="<span class='badge badge-success'>Tamamlandı</span>" value="Tamamlandı">Tamamlandı</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Kart 3: Açıklama -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-purple">
                    <i class="fa fa-align-left"></i>
                </div>
                <div>
                    <h5>Açıklama</h5>
                    <p>Evrakla ilgili ek notlar ve açıklamalar</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-field full-width">
                    <label for="aciklama">Açıklama</label>
                    <textarea name="aciklama" id="aciklama" class="form-control" rows="4" placeholder="Evrakla ilgili açıklama girebilirsiniz"></textarea>
                </div>
            </div>
        </div>
    </div>
</form>