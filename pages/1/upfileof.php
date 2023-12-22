 <?php

 	$dizin = "projects/offers/";
 	$kaynak = $_FILES["file"]["tmp_name"];
 	$hedef = $dizin.basename($_FILES["file"]["name"]);
 	if(move_uploaded_file($kaynak, $hedef)){
 		echo "yes";
 	}else{
 		echo "no";
 	}

 ?>