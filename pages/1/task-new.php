<?php
permcontrol("todoadd");
if ($_POST) {

	$title = @$_POST["title"];
	$desc = @$_POST["desc"];
	$okey = @$_POST["okey"];
	$ldate = date_tr($_POST["lastdate"]);
	$sdate = date_tr($_POST["startdate"]);

	if (empty($title) || empty($desc) || empty($ldate)) {
		header("Location: index.php?p=task-new&st=empties");
		exit;
	}


	$insq = $ac->prepare("INSERT INTO todolist SET
	title = ?,
	description = ?,
	regdate = ?,
	last_date = ?,
	creativer = ?,
	okey = ?");

	$insq->execute(array($title, $desc, $sdate, $ldate, sesset("id"), $okey));

	header("Location: index.php?p=task-new&st=newsuccess");
}

if (@$_GET["st"] == "empties") {
	showAlert("alert", "Zorunlu alanları boş bırakmayınız");
}
if (@$_GET["st"] == "newsuccess") {
	showAlert("success", "Yapılacak görev başarı ile kaydedildi");
}
?>

<style>
    .task-manage-wrapper {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Form styling overrides if any */
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
					<div class="header-icon">
						<i class="fa fa-tasks"></i>
					</div>
					<div class="header-title">
						<h4><?php echo $pdat["p_title"]; ?></h4>
						<span class="header-number-badge">
							<i class="fa fa-info-circle"></i> Sayfadaki (*) yıldız ile belirtilen alanları boş bırakmayın..
						</span>
					</div>
				</div>
				<div class="header-actions">
					<a href="index.php?p=tasks" class="btn-header btn-header-list">
						<i class="fa fa-list"></i> Listeye Dön
					</a>
					<button type="button" id="submitButton" onclick="validateForm()" class="btn-header btn-header-save">
						<i class="fa fa-save"></i> Kaydet
					</button>
				</div>
			</div>
		</div>

		<!-- Form Card -->
		<div class="form-card animate-fade-in">
			<div class="form-card-header">
				<div class="card-icon card-icon-blue">
					<i class="fa fa-calendar-plus-o"></i>
				</div>
				<div>
					<h5>Görev Detayları</h5>
					<p>Lütfen yapılacak görev bilgilerini ve tarih sınırlarını giriniz.</p>
				</div>
			</div>

			<div class="form-grid">
				<!-- Başlık -->
				<div class="form-field full-width">
					<label for="title"><font color="red">(*)</font> Başlık</label>
					<input name="title" id="title" value="" required class="form-control" type="text" placeholder="Görev başlığını giriniz">
				</div>

				<!-- Sol Kolon - Parametreler -->
				<div class="form-field">
					<!-- Durum -->
					<div class="form-field mb-3">
						<label for="okey"><font color="red">(*)</font> Durum</label>
						<select name="okey" id="okey" class="selectpicker form-control" data-style="border bg-white">
							<option disabled value="1">Yapıldı</option>
							<option selected value="0">Yapılmadı</option>
						</select>
					</div>

					<!-- Başlangıç Tarihi -->
					<div class="form-field mb-3">
						<label for="startdate">Başlangıç Tarihi</label>
						<input name="startdate" id="startdate" autocomplete="off" class="form-control date-picker" placeholder="Tarih Seçin" type="text">
					</div>

					<!-- Son Tarihi -->
					<div class="form-field">
						<label for="lastdate"><font color="red">(*)</font> Son Tarih</label>
						<input name="lastdate" id="lastdate" class="form-control date-picker" autocomplete="off" required placeholder="Tarih Seçin" type="text">
					</div>
				</div>

				<!-- Sağ Kolon - Açıklama -->
				<div class="form-field">
					<label for="desc"><font color="red">(*)</font> Açıklama</label>
					<textarea required name="desc" id="desc" class="form-control" placeholder="Görev detayları ve yapılacak işler hakkında bir şeyler yazın..."></textarea>
				</div>
			</div>
		</div>
	</form>
</div>
