<?php
permcontrol("noteedit");
if (!$_GET["nid"]) {
	header("Location:index.php?p=all-notes");
	exit;
}
$nid = $_GET["nid"];
$currentUserId = (int)sesset("id");

$ckk = $ac->prepare("SELECT * FROM notes WHERE id = ? AND (visibility = 'general' OR creativer = ?)");
$ckk->execute(array($nid, $currentUserId));
$ck = $ckk->fetch(PDO::FETCH_ASSOC);
if (!$ck) {
	header("Location:index.php?p=all-notes&st=access_denied");
	exit;
}

if ($_POST) {

	$title = @$_POST["title"];
	$desc = @$_POST["desc"];

	$sdate = @$_POST["startdate"] ? date_tr($_POST["startdate"]) : TODAY;
	$lastdate = @$_POST["lastdate"] ? date_tr($_POST["lastdate"]) : '';

	$urg = $_POST["urgency"];
	$cat = $_POST["cat"];
	$visibility = ($_POST["visibility"] ?? 'general') === 'private' ? 'private' : 'general';
	if ($visibility === 'private' && (int)$ck['creativer'] !== $currentUserId) {
		$visibility = 'general';
	}
	if (empty($title) || empty($desc)) {
		header("Location: index.php?p=edit-note&nid=" . $nid . "&st=empties");
		exit;
	}
	$insq = $ac->prepare("UPDATE notes SET
	category = ?,
	title = ?,
	dates = ?,
	lastdate = ?,
	urgency = ?,
	descs = ?,
	visibility = ? WHERE id = ? AND (visibility = 'general' OR creativer = ?)");

	$result =$insq->execute(array($cat, $title, $sdate, $lastdate, $urg, $desc, $visibility, $nid, $currentUserId));

	if($result){
		header("Location: index.php?p=all-notes&st=newsuccess");
		exit;
	}
}

$urgencyFromDatabase = $ck['urgency'];

if (@$_GET["st"] == "empties") {
?>
	<div class="alert alert-danger" role="alert">
		(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
	</div>
<?php
}
?>

<form enctype="multipart/form-data" method="POST" action="" id="myForm">
    <div class="new-note-manage-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-sticky-note"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $pdat["p_title"] ?? 'Not Düzenle'; ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-info-circle"></i> Not Düzenleme (#<?php echo $nid; ?>)
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=all-notes" class="btn-header btn-header-list mr-2">
                        <i class="fa fa-list"></i> Listeye Dön
                    </a>
                    <button type="button" id="submitButton" onclick="validateForm()" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Güncelle
                    </button>
                </div>
            </div>
        </div>

        <!-- Kart 1: Not Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div>
                    <h5>Not Bilgileri</h5>
                    <p>Notunuza ait genel başlık, aciliyet derecesi, kategori ve tarih detayları</p>
                </div>
            </div>

            <div class="form-grid">
                <!-- Başlık (Full Width) -->
                <div class="form-field full-width">
                    <label for="title"><font color="red">(*)</font> Başlık</label>
                    <input name="title" id="title" value="<?php echo htmlspecialchars($ck["title"] ?? '', ENT_QUOTES); ?>" class="form-control" type="text" placeholder="Not başlığını giriniz" required>
                </div>

                <!-- Aciliyet -->
                <div class="form-field">
                    <label for="urgency">Aciliyet</label>
                    <select name="urgency" id="urgency" class="form-control note-select2" style="width: 100%;">
                        <option value="Yüksek" <?php echo $urgencyFromDatabase == "Yüksek" ? "selected" : ""; ?>>Yüksek</option>
                        <option value="Orta" <?php echo $urgencyFromDatabase == "Orta" ? "selected" : ""; ?>>Orta</option>
                        <option value="Düşük" <?php echo $urgencyFromDatabase == "Düşük" ? "selected" : ""; ?>>Düşük</option>
                    </select>
                </div>

                <!-- Kategori -->
                <div class="form-field">
                    <label for="cat">Kategori</label>
                    <select name="cat" id="cat" class="form-control note-select2" style="width: 100%;">
                        <?php
                        $nqu = $ac->prepare("SELECT * FROM note_categories");
                        $nqu->execute();
                        while ($nn = $nqu->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                            <option <?php echo $nn["id"] == $ck["category"] ? "selected" : ""; ?> value="<?php echo (int)$nn["id"]; ?>"><?php echo htmlspecialchars($nn["title"], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="visibility">Görünürlük</label>
                    <select name="visibility" id="visibility" class="form-control note-select2" style="width: 100%;">
                        <option value="general" <?php echo ($ck['visibility'] ?? 'general') === 'general' ? 'selected' : ''; ?>>Genel — Herkes görebilir</option>
                        <?php if ((int)$ck['creativer'] === $currentUserId) { ?>
                            <option value="private" <?php echo ($ck['visibility'] ?? 'general') === 'private' ? 'selected' : ''; ?>>Özel — Yalnızca ben görebilirim</option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Başlangıç Tarihi -->
                <div class="form-field">
                    <label for="startdate">Başlangıç Tarihi</label>
                    <input name="startdate" id="startdate" class="form-control date-picker" autocomplete="off" value="<?php echo redate_tr($ck["dates"]); ?>" placeholder="Tarih Seçin" type="text">
                </div>

                <!-- Son Tarih -->
                <div class="form-field">
                    <label for="lastdate">Son Tarih</label>
                    <input name="lastdate" id="lastdate" class="form-control date-picker" autocomplete="off" value="<?php echo redate_tr($ck["lastdate"]); ?>" placeholder="Tarih Seçin" type="text">
                </div>
            </div>
        </div>

        <!-- Kart 2: Not İçeriği -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-purple">
                    <i class="fa fa-pencil-square-o"></i>
                </div>
                <div>
                    <h5>Not İçeriği</h5>
                    <p>Not içeriğini ve detaylı açıklamalarını giriniz</p>
                </div>
            </div>
            <div class="editor-wrapper">
                <textarea name="desc" class="textarea_editor form-control border-radius-8" placeholder="Bir şeyler yaz ..."><?php echo htmlspecialchars($ck["descs"] ?? ''); ?></textarea>
            </div>
        </div>
    </div>
</form>

<button type="button" id="floatingSubmitButton" class="floating-note-save" onclick="validateForm()" aria-label="Notu güncelle">
    <i class="fa fa-save"></i><span>Güncelle</span>
</button>

<style>
.floating-note-save { position: fixed; right: 24px; bottom: 24px; z-index: 1030; display: none; align-items: center; gap: 8px; border: 0; border-radius: 999px; padding: 12px 19px; background: #16a34a; color: #fff; font-weight: 700; box-shadow: 0 8px 24px rgba(22,163,74,.32); }
.floating-note-save.is-visible { display: inline-flex; }
.floating-note-save:hover { background: #15803d; color: #fff; }
@media (max-width: 576px) { .floating-note-save { right: 16px; bottom: 16px; } }
</style>

<script>
	$(document).ready(function () {
		if ($.fn.select2) {
			$('#myForm .note-select2').select2({
				width: '100%',
				minimumResultsForSearch: 0,
				language: {
					noResults: function () { return 'Sonuç bulunamadı'; },
					searching: function () { return 'Aranıyor...'; }
				}
			});
		}
	});

	(function () {
		var originalButton = document.getElementById('submitButton');
		var floatingButton = document.getElementById('floatingSubmitButton');
		if (!originalButton || !floatingButton) return;
		function updateFloatingButton() {
			var rect = originalButton.getBoundingClientRect();
			floatingButton.classList.toggle('is-visible', rect.bottom < 0 || rect.top > window.innerHeight);
		}
		window.addEventListener('scroll', updateFloatingButton, { passive: true });
		window.addEventListener('resize', updateFloatingButton);
		updateFloatingButton();
	})();
</script>
