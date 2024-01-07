 <?php
echo @$_GET["oid"];
 	$dizin = "src/images/";
 	$kaynak = $_FILES["file"]["tmp_name"];
 	$hedef = $dizin.basename($_FILES["file"]["name"]);
 	if(move_uploaded_file($kaynak, $hedef)){
 		echo "yes";
		 $upfile=$ac->prepare("INSERT INTO files SET 
													oid= ?, 
													filename= ? , 
													regdate = ?");
		 $upfile->execute(array($oid,$hedef,TODAY));
	 
 	}else{
 		echo "no";
 	}

 ?>