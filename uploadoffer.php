<?php 

	$dir = "projects/offers/";
	$filez = $dir . basename($_FILES["file"]["name"]);

	if(move_uploaded_file($_FILES["file"]["tmp_name"], $filez)){
		echo "İşlem başarılı";
	}else{
		echo "Hata olustu";
	}
?>