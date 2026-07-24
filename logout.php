<?php 
require_once 'bootstrap.php';

if (isset($_SESSION['lid'])) {
    audit_log("logout", "auth", "Sistemden çıkış yaptı", "user", $_SESSION['lid']);
}

session_destroy();
header("Location: login.php");
exit;
?>
