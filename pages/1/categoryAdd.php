<?php

if ($_POST) {
    if (isset($_POST['Addcategory'])) {
        $categoryName = $_POST['Addcategory'];

        global $ac;
		
		$insq = $ac->prepare("INSERT INTO missioncategory SET categoryName = ? ");
		$insq->execute(array($categoryName));
        
    }
}
?>