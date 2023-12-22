<?php
permcontrol("fadd");
if (@$_POST && @$_FILES["dosya"]["name"]) {

	echo "<pre>";
	print_r($_FILES["dosya"]);
	echo "</pre>";

	$dizin = "files/";

	$kaynak = $_FILES["dosya"]["tmp_name"];

	$rast1 = rand(1, 100);

	$hedef = $dizin . $rast1 . "_" . basename($_FILES["dosya"]["name"]);

	$upx = move_uploaded_file($kaynak, $hedef);


	if (@$upx) {
		$ins = $ac->prepare("INSERT INTO upfiles SET
 		cid = ?,
 		filename = ?,
 		size = ?,
 		creativer = ?");
		$ins->execute(array(@$_POST["cid"], $rast1 . "_" . basename($_FILES["dosya"]["name"]), $_FILES["dosya"]["size"], sesset("id")));
		header("Location: index.php?p=all-files&st=newsuccess");
	}
} else {
}

?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue">Dosya Upload</h4>
			<p class="mb-30 font-14"></p>
		</div>
		<div class="form-group">
			<input type="submit" id="submitButton" value="Yükle" class="float-right btn btn-primary">
		</div>
	</div>
	<form enctype="multipart/form-data" id="myForm" action="index.php?p=new-file&post=true" method="POST">
		<div class="row">
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Dosya Seçin
					</label>
					<input required name="dosya" type="file" class="form-control-file form-control height-auto">
				</div>
				<input type="hidden" name="posted" value="true">
			</div>

			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font> Kategori
					</label>
					<select required name="cid" class="form-control selectpicker">
						<?php
						$catcek = $ac->prepare("SELECT * FROM upfile_categories");
						$catcek->execute();
						while ($cc = $catcek->fetch(PDO::FETCH_ASSOC)) {
						?>
							<option value="<?php echo $cc["id"]; ?>"><?php echo $cc["title"]; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>


		</div><br><br>

	</form>

	<script>
		document.getElementById("submitButton").addEventListener("click",function(){
			var form=document.getElementById("myForm");
			form.submit();
		})
	</script>

	<!-- <script>
		document.getElementById("submitButton").addEventListener("click", function() {
			var form = document.getElementById("myForm");
			form.submit();
		});
	</script> -->