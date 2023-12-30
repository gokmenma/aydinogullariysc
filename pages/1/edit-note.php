<?php
permcontrol("noteedit");
if (!$_GET["nid"]) {
	header("Location:index.php?p=all-notes");
	exit;
}
$nid = $_GET["nid"];
if ($_POST) {

	$title = @$_POST["title"];
	$desc = @$_POST["desc"];

	$sdate = @$_POST["startdate"] ? date_tr($_POST["startdate"]) : TODAY;
	$lastdate = @$_POST["lastdate"];

	$urg = $_POST["urgency"];
	$cat = $_POST["cat"];
	if (empty($title) || empty($desc)) {
		header("Location: index.php?p=new-note&st=empties");
		exit;
	}


	$insq = $ac->prepare("UPDATE notes SET
	category = ?,
	title = ?,
	dates = ?,
	lastdate = ?,
	urgency = ?,
	descs = ? WHERE id = ?");

	$result =$insq->execute(array($cat, $title, $sdate, $lastdate, $urg, $desc, $nid));

	if($result){
		header("Location: index.php?p=all-notes");
	}
	
}

$ckk = $ac->prepare("SELECT * FROM notes WHERE id = ?");
$ckk->execute(array($nid));
$ck = $ckk->fetch(PDO::FETCH_ASSOC);


if (@$_GET["st"] == "empties") {
?>

<?php
}
if (@$_GET["st"] == "newsuccess") {
?>
<?php
}
?>




<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>
		<div class="float-right">
			<input type="submit" id="submitButton" onclick="validateForm()" value="Kaydet" class="btn btn-primary">
			<input type="button"  value="Listeye Dön" class="btn btn-secondary">

		</div>

	</div>
	<form enctype="multipart/form-data" method="POST" action="" id=myForm>

		<div class="row">
			<div class="col-md-12 col-sm-12">
				<div class="form-group">
					<label for="title">
						<font color="red">(*)</font>Başlık
					</label>
					<input name="title" value="<?php echo $ck["title"]; ?>" class="form-control" type="text">

				</div>
			</div>
		</div>

		<div class="form-group row">

			<label class="col-md-2">Aciliyet</label>
			<div class="col-md-4">
				<select name="urgency" class="form-control">
					<option <?php echo $ck["urgency"] == "Yüksek" ? "selected" : ""; ?> value="Yüksek">Yüksek</option>
					<option <?php echo $ck["urgency"] == "Orta" ? "selected" : ""; ?> value="Orta">Orta</option>
					<option <?php echo $ck["urgency"] == "Düşük" ? "selected" : ""; ?> value="Düşük">Düşük</option>
				</select>
			</div>

			<label class="col-md-2">Kategori</label>

			<div class="col-md-4">
				<select name="cat" class="form-control">
					<?php
					$nqu = $ac->prepare("SELECT * FROM note_categories");
					$nqu->execute();
					while ($nn = $nqu->fetch(PDO::FETCH_ASSOC)) {
					?>

						<option <?php echo $nn["id"] == $ck["category"] ? "selected" : ""; ?> value="<?php echo $nn["id"]; ?>"><?php echo $nn["title"]; ?></option>
					<?php } ?>
				</select>


			</div>

		</div>

		<div class="form-group row">

			<label class="col-md-2">Başlangıç Tarihi</label>

			<div class="col-md-4 col-sm-12">
				<input name="startdate" class="form-control date-picker" value="<?php echo redate_tr($ck["dates"]); ?>" placeholder="Tarih Seçin" type="text">
			</div>


			<label class="col-md-2">Son Tarih</label>
			<div class="col-sm-12 col-md-4">
				<input name="lastdate" class="form-control date-picker" value="<?php echo redate_tr($ck["lastdate"]); ?>" placeholder="Tarih Seçin" type="text">
			</div>

		</div>
		<div class="form-group">

			<div class="html-editor">
				<h3 class="weight-500">Not Oluştur</h3>
				<p></p>
				<textarea name="desc" class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ..."><?php echo $ck["descs"]; ?></textarea><br>
			</div>



	</form>
</div>










