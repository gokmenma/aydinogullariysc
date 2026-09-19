<?php
permcontrol("todoedit");
if (!$_GET["id"]) {
	header("Location: index.php?p=tasks");
	exit;
}
$tid = (int)$_GET["id"];
if ($_POST) {

	$title = trim(@$_POST["title"]);
	$desc = trim(@$_POST["desc"]);
	$okey = isset($_POST["okey"]) ? (int)$_POST["okey"] : 0;
	$ldate = !empty($_POST["lastdate"]) ? date_tr($_POST["lastdate"]) : '';
	$sdate = !empty($_POST["startdate"]) ? date_tr($_POST["startdate"]) : '';

	if (empty($title) || empty($desc) || empty($ldate)) {
		header("Location: index.php?p=task-edit&id=$tid&st=empties");
		exit;
	}

	$insq = $ac->prepare("UPDATE todolist SET
		title = ?,
		description = ?,
		regdate = ?,
		last_date = ?,
		okey = ? WHERE id = ?");

	$insq->execute(array($title, $desc, $sdate, $ldate, $okey, $tid));
	if ($insq) {
		header("Location: index.php?p=task-edit&id=$tid&st=success");
		exit;
	}
}

$dat = $ac->prepare("SELECT * FROM todolist WHERE id = ?");
$dat->execute(array($tid));
$dd = $dat->fetch(PDO::FETCH_ASSOC);

if (!$dd) {
	header("Location: index.php?p=tasks");
	exit;
}

if (@$_GET["st"] == "empties") {
	showAlert("alert", "Zorunlu alanları (*) boş bırakmayınız.");
}
if (@$_GET["st"] == "success") {
	showAlert("success", "Yapılacak görev başarı ile güncellendi.");
}
?>

<style>
    .task-manage-wrapper {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Form styling overrides */
    .form-field textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }
</style>

<div class="task-manage-wrapper">
	<form method="POST" id="myForm">
		<!-- Header Card -->
		<div class="premium-header-card animate-fade-in">
			<div class="header-content">
				<div class="header-left">
					<div class="header-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;">
						<i class="fa fa-tasks"></i>
					</div>
					<div class="header-title">
						<h4>Görev Düzenle</h4>
						<span class="header-number-badge">
							<i class="fa fa-info-circle"></i> Sayfadaki (*) yıldız ile belirtilen alanları boş bırakmayın.
						</span>
					</div>
				</div>
				<div class="header-actions">
					<a href="index.php?p=tasks" class="btn-header btn-header-list">
						<i class="fa fa-list"></i> Listeye Dön
					</a>
					<button type="button" id="submitButton" onclick="validateForm()" class="btn-header btn-header-save">
						<i class="fa fa-save"></i> Değişiklikleri Kaydet
					</button>
				</div>
			</div>
		</div>

		<!-- Form Card -->
		<div class="form-card animate-fade-in">
			<div class="form-card-header">
				<div class="card-icon card-icon-blue">
					<i class="fa fa-pencil-square-o"></i>
				</div>
				<div>
					<h5>Görev Bilgilerini Düzenle</h5>
					<p>Yapılacak görev detaylarını, durumunu ve teslim tarihlerini güncelleyiniz.</p>
				</div>
			</div>

			<div class="form-grid">
				<!-- Başlık -->
				<div class="form-field full-width">
					<label for="title"><font color="red">(*)</font> Başlık</label>
					<input name="title" id="title" value="<?php echo htmlspecialchars($dd["title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required class="form-control" type="text" placeholder="Görev başlığını giriniz">
				</div>

				<!-- Sol Kolon - Parametreler -->
				<div class="form-field">
					<!-- Durum -->
					<div class="form-field mb-3">
						<label for="okey"><font color="red">(*)</font> Durum</label>
						<select name="okey" id="okey" class="selectpicker form-control" data-style="border bg-white">
							<option <?php echo (int)$dd["okey"] === 0 ? "selected" : ""; ?> value="0">⏳ Yapılmadı (Bekliyor)</option>
							<option <?php echo (int)$dd["okey"] === 1 ? "selected" : ""; ?> value="1">✅ Yapıldı (Tamamlandı)</option>
							<option <?php echo (int)$dd["okey"] === 2 ? "selected" : ""; ?> value="2">⏸️ Ertelendi</option>
						</select>
					</div>

					<!-- Başlangıç Tarihi -->
					<div class="form-field mb-3">
						<label for="startdate">Başlangıç / Kayıt Tarihi</label>
						<input name="startdate" id="startdate" autocomplete="off" class="form-control date-picker" placeholder="Tarih Seçin" value="<?php echo !empty($dd["regdate"]) ? redate_tr($dd["regdate"]) : ''; ?>" type="text">
					</div>

					<!-- Son Tarihi -->
					<div class="form-field">
						<label for="lastdate"><font color="red">(*)</font> Son Tarih</label>
						<input name="lastdate" id="lastdate" class="form-control date-picker" autocomplete="off" required placeholder="Tarih Seçin" value="<?php echo redate_tr($dd["last_date"]); ?>" type="text">
					</div>
				</div>

				<!-- Sağ Kolon - Açıklama -->
				<div class="form-field">
					<label for="desc"><font color="red">(*)</font> Açıklama</label>
					<textarea required name="desc" id="desc" class="form-control" placeholder="Görev detayları ve yapılacak işler hakkında bir şeyler yazın..."><?php echo htmlspecialchars($dd["description"] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
				</div>
			</div>
		</div>
	</form>
</div>