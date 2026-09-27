<?php
permcontrol("docedit");
$eid = (int)($_GET["id"] ?? 0);

$cerq = $ac->prepare("SELECT * FROM evraktakip WHERE id = ?");
$cerq->execute(array($eid));
$cc = $cerq->fetch(PDO::FETCH_ASSOC);

if (!$cc) {
    echo "<div class='alert alert-danger'>Evrak kaydı bulunamadı.</div>";
    return;
}

if ($_POST) {
	if(!$_POST["firma"] || !$_POST["evrakturu"] || !$_POST["kategori"] || !$_POST["teslimeden"]){
		header("Location: index.php?p=indocument-edit&id=" . $eid . "&st=empties");
		exit;
	}
    $firma = $_POST["firma"];
    $evrakturu = $_POST["evrakturu"];
    $kategori = $_POST["kategori"];
    $adet = $_POST["adet"];
    $teslimalan = sesset("id"); 
   	$teslimtarihi = $_POST["teslimtarihi"] ?: date('d-m-Y');
	$estatu = $_POST["estatu"] ?: 'Bekliyor';
    $aciklama = $_POST["aciklama"] ?? '';
	$teslimeden = $_POST["teslimeden"];
	$upxsx = $ac->prepare("UPDATE evraktakip SET
				firma = ?,
                evrakturu = ?,
				kategori = ?,
				adet = ?,
				teslimalan = ?,
                teslimeden = ?,
                teslimtarihi = ?,
				estatu = ?,
				aciklama = ? WHERE id = ?");

	$upxsx->execute(array($firma, $evrakturu, $kategori, $adet, $teslimalan, $teslimeden, $teslimtarihi, $estatu, $aciklama, $eid));

	if($upxsx)
	{
		if ($evrakturu == "Gelen")
		{
			header("Location: index.php?p=view-indocument&id=$eid&up=success&st=yes&mdcode=14");
			exit;
		}
		if ($evrakturu == "Giden")
		{
			header("Location: index.php?p=view-outdocument&id=$eid&up=success&st=yes&mdcode=14");
			exit;
		}
	}
}

if (@$_GET["st"] == "empties") {
    showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
}

if (@$_GET["st"] == "newsuccess") {
    showAlert("success", "İşlem Başarı ile tamamlandı!");
}

$selected_company_id = (int)($cc["firma"] ?? 0);
$selected_company_name = '';
$selected_company_yetkili = '';
$selected_company_city = '';

if ($selected_company_id > 0) {
    $custQ = $ac->prepare("SELECT id, company, yetkili, city FROM customers WHERE id = ?");
    $custQ->execute([$selected_company_id]);
    $custData = $custQ->fetch(PDO::FETCH_ASSOC);
    if ($custData) {
        $selected_company_name = $custData['company'];
        $selected_company_yetkili = $custData['yetkili'] ?? '';
        $selected_company_city = $custData['city'] ?? '';
    }
} elseif (!empty($cc["firma"])) {
    $custQ = $ac->prepare("SELECT id, company, yetkili, city FROM customers WHERE company = ? LIMIT 1");
    $custQ->execute([$cc["firma"]]);
    $custData = $custQ->fetch(PDO::FETCH_ASSOC);
    if ($custData) {
        $selected_company_id = (int)$custData['id'];
        $selected_company_name = $custData['company'];
        $selected_company_yetkili = $custData['yetkili'] ?? '';
        $selected_company_city = $custData['city'] ?? '';
    } else {
        $selected_company_name = $cc["firma"];
    }
}

$docModel = new \App\Model\DocumentModel();
$kategoriler = $docModel->getDistinctCategories();
?>

<form method="POST" action="" id="evrakEditForm">
    <div class="evrak-manage-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-folder-open"></i>
                    </div>
                    <div class="header-title">
                        <h4>Evrak Düzenleme Sayfası</h4>
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
                        <i class="fa fa-save"></i> Güncelle
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
                    <select name="firma" id="firma" class="form-control select2-customer" style="width: 100%;" required>
                        <?php if ($selected_company_id > 0 && !empty($selected_company_name)): ?>
                            <option value="<?php echo $selected_company_id; ?>" selected 
                                data-company="<?php echo htmlspecialchars($selected_company_name, ENT_QUOTES, 'UTF-8'); ?>"
                                data-yetkili="<?php echo htmlspecialchars($selected_company_yetkili, ENT_QUOTES, 'UTF-8'); ?>"
                                data-city="<?php echo htmlspecialchars($selected_company_city, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($selected_company_name, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php elseif (!empty($selected_company_name)): ?>
                            <option value="<?php echo htmlspecialchars($selected_company_name, ENT_QUOTES, 'UTF-8'); ?>" selected>
                                <?php echo htmlspecialchars($selected_company_name, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php else: ?>
                            <option value="">Lütfen Firma Seçiniz</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="evrakturu"><font color="red">(*)</font> Evrak Türü</label>
                    <select required name="evrakturu" id="evrakturu" class="selectpicker form-control" data-style="border bg-white" data-container="body">
						<option <?php echo $cc["evrakturu"] == "Gelen" ? "selected" : "";?> value="Gelen">Gelen Evrak</option>
                        <option <?php echo $cc["evrakturu"] == "Giden" ? "selected" : "";?> value="Giden">Giden Evrak</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="kategori"><font color="red">(*)</font> Kategori</label>
                    <input required name="kategori" id="kategori" list="kategori_list" placeholder="Kategori yazın veya listeden seçin..." class="form-control" type="text" autocomplete="off" value="<?php echo htmlspecialchars($cc["kategori"] ?? '', ENT_QUOTES); ?>">
                    <datalist id="kategori_list">
                        <?php foreach ($kategoriler as $kat): ?>
                            <option value="<?php echo htmlspecialchars($kat, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-field">
                    <label for="adet"><font color="red">(*)</font> Adet</label>
                    <input required name="adet" id="adet" placeholder="Evrak sayısını girin" class="form-control" type="number" min="1" value="<?php echo htmlspecialchars($cc["adet"] ?? '', ENT_QUOTES); ?>">
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
                    <label><font color="red">(*)</font> Teslim Alan</label>
                    <input disabled class="form-control" value="<?php echo sesset("username"); ?>" type="text">
                </div>
                <div class="form-field">
                    <label for="teslimeden"><font color="red">(*)</font> Teslim Eden</label>
                    <select name="teslimeden" id="teslimeden" title="Seçiniz" class="selectpicker form-control" data-live-search="true" data-style="btn-outline-secondary" data-container="body" required>
						<?php
						$selected_user = $cc["teslimeden"];
						$tt = $ac->prepare("SELECT * FROM users ORDER BY username ASC");
						$tt->execute();
						while ($mm2 = $tt->fetch(PDO::FETCH_ASSOC)) {
							$selected = ($mm2["id"] == $selected_user) ? "selected" : "";
							echo '<option ' . $selected . ' value="' . $mm2["id"] . '">' . htmlspecialchars($mm2["username"]) . '</option>';
						}
						?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="teslimtarihi">Teslim Alma Tarihi</label>
                    <input name="teslimtarihi" id="teslimtarihi" type="text" placeholder="Boş bırakırsanız otomatik bugün seçilir." class="form-control date-picker" autocomplete="off" value="<?php echo htmlspecialchars($cc["teslimtarihi"] ?? '', ENT_QUOTES); ?>">
                </div>
                <div class="form-field">
                    <label for="estatu">Evrak Durumu</label>
                    <select name="estatu" id="estatu" class="selectpicker form-control" data-style="btn-outline-secondary" data-container="body">
                        <option <?php echo $cc["estatu"] == "Bekliyor" ? "selected" : ""; ?> data-content="<span class='badge badge-warning'>Bekliyor</span>" value="Bekliyor">Bekliyor</option>
                        <option <?php echo $cc["estatu"] == "Çalışıyor" ? "selected" : ""; ?> data-content="<span class='badge badge-primary'>Çalışıyor</span>" value="Çalışıyor">Çalışıyor</option>
                        <option <?php echo $cc["estatu"] == "Tamamlandı" ? "selected" : ""; ?> data-content="<span class='badge badge-success'>Tamamlandı</span>" value="Tamamlandı">Tamamlandı</option>
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
                    <textarea name="aciklama" id="aciklama" class="form-control" rows="4" placeholder="Evrakla ilgili açıklama girebilirsiniz"><?php echo htmlspecialchars($cc["aciklama"] ?? '', ENT_QUOTES); ?></textarea>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    function formatCustomerResult(item) {
        if (item.loading) return item.text;
        var company = $('<div>').text(item.company || item.text).html();
        var yetkili = item.yetkili ? $('<div>').text(item.yetkili).html() : '';
        var city = item.city ? $('<div>').text(item.city).html() : '';
        
        var subText = [];
        if (yetkili) subText.push('<i class="fa fa-user-o mr-1"></i>' + yetkili);
        if (city) subText.push('<i class="fa fa-map-marker mr-1"></i>' + city);

        return $(
            '<div style="padding: 4px 0; line-height: 1.2;">' +
                '<div style="font-weight: 600; color: #1e293b;">' + company + '</div>' +
                (subText.length ? '<div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">' + subText.join(' &bull; ') + '</div>' : '') +
            '</div>'
        );
    }

    function formatCustomerSelection(item) {
        return item.company || (item.element ? $(item.element).data('company') : '') || item.text || 'Lütfen Firma Seçiniz';
    }

    var $firmaSelect = $('#firma');
    if ($firmaSelect.length && $.fn.select2) {
        $firmaSelect.select2({
            placeholder: 'Firma adı veya yetkili yazarak arayın...',
            allowClear: false,
            width: '100%',
            ajax: {
                url: 'api/search_customers.php',
                dataType: 'json',
                delay: 150,
                data: function(params) {
                    return {
                        q: params.term || '',
                        limit: 30
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            },
            templateResult: formatCustomerResult,
            templateSelection: formatCustomerSelection,
            language: {
                searching: function() { return "Aranıyor..."; },
                noResults: function() { return "Eşleşen firma bulunamadı"; },
                loadingMore: function() { return "Daha fazla yükleniyor..."; },
                inputTooShort: function() { return "Aramak için yazmaya başlayın..."; }
            }
        });
    }
});
</script>