<?php 
/* MYSQL Bağlantı Bilgileri */
session_start();
error_reporting(0);
ob_start();
	define("HOSTNAME", "localhost"); // Veritabanı Sunucu
	define("HOSTUSERNAME","root");	// Veritabanı Kullanıcı Adı
	define("HOSTPASSWORD","");	// Veritabanı Kullanıcı Parolası
	define("HOSTDATABASE","aydinogullariysc");	// Veritabanı İsmi

try{

	$ac = new PDO("mysql:host=".HOSTNAME.";dbname=".HOSTDATABASE, HOSTUSERNAME, HOSTPASSWORD);
	
}catch(PDOException $e){
	echo "Hata! <br>".$e->getMessage();
	die();
}
$ac->query("SET CHARACTER SET utf8");
date_default_timezone_set('Europe/Istanbul');
define("TODAY",date("d-m-Y"));
define("NOWCLOCK",date("H:i:s"));

	$a=0;
	$b=0;
	$inexpsup = $ac->prepare("SELECT * FROM inexps WHERE type = ?");
	$inexpsup->execute(array("in"));
	

	

?>
