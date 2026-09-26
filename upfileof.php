<?php

	require_once __DIR__ . '/bootstrap.php';
	use App\Helper\UploadSecurity;

 	$dizin = "projects/offers/";

 	$kaynak = $_FILES["file"]["tmp_name"];

	$validatedFile = UploadSecurity::validate($_FILES['file']);
	$storedName = UploadSecurity::randomName($validatedFile);
	$hedef = $dizin . $storedName;
 	if(move_uploaded_file($kaynak, $hedef)){
 		$ins1 = $ac->prepare("INSERT INTO files SET
 		pid = ?,
 		oid = ?,
 		filename = ?");

	 	$ins1->execute(array(0, (int) $_GET["oid"], $storedName));
 	}else{
 	
 	}

 ?>
