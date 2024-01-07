<?php
include("configs/config.php");
$target_dir = "files/projects/offers/";

$request = 1;
if (isset($_POST['id'])) {
	$oid = $_POST['id'];
}

$Today = date("Y-m-d H:i:s");

if (isset($_POST['request'])) {
	$request = $_POST['request'];
}

// Upload file
if ($request == 1) {
	$msg = "";

	$target_file = $target_dir . basename($_FILES["file"]["name"]);

	if ($target_file) {


		if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_dir . $_FILES['file']['name'])) {

			$file_path = $target_dir . $_FILES['file']['name'];
			$upfile = $ac->prepare("INSERT INTO files SET oid = ?, filename = ?, regdate = ? ");
			$upfile->execute(array($oid, $file_path, $Today));
			$msg = "Dosya yüklendi";
			
		} else {
			$msg = "Yükleme sırasında hata oluştu";
		}

		echo $msg;
	}
}
// Remove file
if ($request == 2) {
	if (isset($_POST['name'])) {
		$filename = $target_dir . $_POST['name'];
		if (file_exists($filename)) {
			unlink($filename);
			echo "Dosya silindi";
		} else {
			echo "Dosya bulunamadı";
		}
	} else {
		echo "Dosya adı belirtilmedi";
	}
	exit;
}
