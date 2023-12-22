<?php

 	include("configs/config.php");

 	$dizin = "projects/";

 	$kaynak = $_FILES["file"]["tmp_name"];

 	$rast1 = rand(1,100);
 	$rast2 = rand(1,199);

 	$hedef = $dizin.$rast1.$rast2.basename($_FILES["file"]["name"]);
 	if(move_uploaded_file($kaynak, $hedef)){
 		$ins1 = $ac->prepare("INSERT INTO files SET
 		pid = ?,
 		oid = ?,
 		filename = ?");

 		$ins1->execute(array($_GET["pid"],0,$rast1.$rast2.$_FILES["file"]["name"]));
 	}else{
 	
 	}

 ?>