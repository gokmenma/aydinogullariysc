<?php


// if ($pids && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
// 	permcontrol("prodelete");
// 	$qcont = $ac->prepare("SELECT * FROM products WHERE id = ?");
// 	$qcont->execute(array($pids));
// 	$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
// 	if ($qkx) {
// 		$pdq = $ac->prepare("DELETE FROM products WHERE id = ?");
// 		$pdq->execute(array($pids));

// 		header("Location: index.php?p=products&type=delete&code=0882md25&pid=$pids");
// 	}
// }



if($_POST){
    $id=$_POST['id'];
    if (isset($_POST['id'])) {
        $sql=$ac->prepare("DELETE FROM customers where id = ?");
        $sql->execute(array($id));
    }
}
?>