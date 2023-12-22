<?php 
include("configs/index.php");

$logtab = $ac->prepare("INSERT INTO logs SET
		type = ?,
		dates = ?,
		clock = ?,
		message = ?,
		author = ?");
		$logtab->execute(array(1,TODAY,NOWCLOCK,"panelden çıkış yaptı.",$_SESSION["lid"]));	

session_destroy();
header("Location: index.php?statu=logout");
exit;
?>