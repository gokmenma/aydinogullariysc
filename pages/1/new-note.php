<?php
permcontrol("noteadd");
if ($_POST) {

	$title = @$_POST["title"];
	$desc = @$_POST["desc"];
	$sdate = @$_POST["startdate"] ? date_tr($_POST["startdate"]) : TODAY;
	$lastdate = date_tr(@$_POST["lastdate"]);
	$urg = $_POST["urgency"];
	$cat = $_POST["cat"];

	// if (empty($title) || empty($desc)) {
	// 	header("Location: index.php?p=new-note&st=empties");
	// 	exit;
	// }


	$insq = $ac->prepare("INSERT INTO notes SET
	category = ?,
	title = ?,
	dates = ?,
	lastdate = ?,
	creativer = ?,
	urgency = ?,
	descs = ?");

	$result = $insq->execute(array($cat, $title, $sdate, $lastdate, sesset("id"), $urg, $desc));

	if ($result) {
		header("Location: index.php?p=all-notes");
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
?>

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
                        <h4><?php echo $pdat["p_title"] ?? 'Yeni Not Oluştur'; ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-info-circle"></i> Yeni Not Tanımlama
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <button type="button" id="submitButton" onclick="validateForm()" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Kaydet
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
                    <input name="title" id="title" value="" class="form-control" type="text" placeholder="Not başlığını giriniz" required>
                </div>

                <!-- Aciliyet -->
                <div class="form-field">
                    <label for="urgency">Aciliyet</label>
                    <select name="urgency" id="urgency" class="selectpicker form-control" data-style="border bg-white" data-container="body">
                        <option value="Yüksek">Yüksek</option>
                        <option value="Orta" selected>Orta</option>
                        <option value="Düşük">Düşük</option>
                    </select>
                </div>

                <!-- Kategori -->
                <div class="form-field">
                    <label for="cat">Kategori</label>
                    <select name="cat" id="cat" class="selectpicker form-control" data-style="border bg-white" data-container="body">
                        <?php
                        $nqu = $ac->prepare("SELECT * FROM note_categories");
                        $nqu->execute();
                        while ($nn = $nqu->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                            <option value="<?php echo $nn["id"]; ?>"><?php echo $nn["title"]; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Başlangıç Tarihi -->
                <div class="form-field">
                    <label for="startdate">Başlangıç Tarihi</label>
                    <input name="startdate" id="startdate" class="form-control date-picker" autocomplete="off" value="" placeholder="Tarih Seçin" type="text">
                </div>

                <!-- Son Tarih -->
                <div class="form-field">
                    <label for="lastdate">Son Tarih</label>
                    <input name="lastdate" id="lastdate" class="form-control date-picker" autocomplete="off" value="" placeholder="Tarih Seçin" type="text">
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
                <textarea name="desc" class="textarea_editor form-control border-radius-8" placeholder="Bir şeyler yaz ..."></textarea>
            </div>
        </div>
    </div>
</form>

<script>
	$(document).ready(function () {
		$(".selectpicker").selectpicker({
			selectAllText: "Tümünü Seç",
			deselectAllText: 'Seçimi Temizle',
			style: "border bg-white",
			liveSearch: true,
			liveSearchPlaceholder: "Ara..",
			noneResultsText: 'Eşleşen kayıt yok {0}',
			size: 5,
			noneSelectedText: "Seçim Yapınız!"
		})
	})
</script>