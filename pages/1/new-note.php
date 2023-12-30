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

<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>
		<div class="float-right">
			<input type="submit" id="submitButton" onclick="validateForm()" value="Kaydet" class="float-right btn btn-primary">

		</div>

	</div>
	<form enctype="multipart/form-data" method="POST" action="" id=myForm>

		<div class="row">
			<div class="col-md-12 col-sm-12">
				<div class="form-group">
					<label for="title">
						<font color="red">(*)</font>Başlık
					</label>
					<input name="title" value="" class="form-control" type="text">

				</div>
			</div>
		</div>

		<div class="form-group row">

			<label class="col-md-2">Aciliyet</label>
			<div class="col-md-4">
				<select name="urgency" class="form-control">
					<option value="Yüksek">Yüksek</option>
					<option value="Orta">Orta</option>
					<option value="Düşük">Düşük</option>
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

						<option value="<?php echo $nn["id"]; ?>"><?php echo $nn["title"]; ?></option>
					<?php } ?>
				</select>


			</div>

		</div>

		<div class="form-group row">

			<label class="col-md-2">Başlangıç Tarihi</label>

			<div class="col-md-4 col-sm-12">
				<input name="startdate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">
			</div>


			<label class="col-md-2">Son Tarih</label>
			<div class="col-sm-12 col-md-4">
				<input name="lastdate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">
			</div>

		</div>
		<div class="form-group">

			<div class="html-editor">
				<h3 class="weight-500">Not Oluştur</h3>
				<p></p>
				<textarea name="desc" class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ..."></textarea><br>
			</div>



	</form>

</div>