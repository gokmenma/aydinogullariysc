<?php permcontrol("fadd"); ?>
<?php permcontrol("fview"); ?>
<?php permcontrol("fdelete"); ?>

<?php $id = @$_GET["id"];
if (!$id) {
	header("Location: index.php");
	exit;
} ?>
<?php


$bral = $ac->prepare("SELECT * FROM upfile_categories WHERE id = ?");
$bral->execute(array($id));
$data = $bral->fetch(PDO::FETCH_ASSOC);
if (!$data) {
	header("Location: index.php");
	exit;
}

if (@$_POST and @$_GET["mode"] == "edit") {

	$title = trim($_POST["title"]);

	$up = $ac->prepare("UPDATE upfile_categories SET title = ? WHERE id = ?");
	$up->execute(array($title, $id));

	header("Location: index.php?p=file-categories&st=newsuccess");
}
?><div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Kategori Adını Değiştir</h5>

		</div>

	</div>
	<form method="POST" action="index.php?p=edit-fcategory&id=<?php echo $id; ?>&mode=edit&code=38&cc=087s3">

		<div class="row">
			<h4>&nbsp;&nbsp;Yeni Marka Oluştur</h4><br><br>

			<div class="col-sm-12 col-md-12">



				<div class="form-group">
					<label>
						<font color="red">(*)</font>Kategori Adı:
					</label>
					<input required name="title" value="<?php echo $data["title"]; ?>" class="form-control" type="text">

				</div><button type="submit" style="float:right;" type="button" class="btn btn-success">Ekle</button><br><br><br>
			</div>
	</form>
</div>