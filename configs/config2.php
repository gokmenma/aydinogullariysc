<?php 
/* MYSQL Bağlantı Bilgileri */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ob_start();
	define("HOSTNAME", getenv('DB_HOST') ?: "localhost");
	define("HOSTUSERNAME", getenv('DB_USER') ?: "root");
	define("HOSTPASSWORD", getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "");
	define("HOSTDATABASE", getenv('DB_NAME') ?: "aydinogu_aydinogullari_yeni");

try{

	$ac = new PDO("mysql:host=".HOSTNAME.";dbname=".HOSTDATABASE.";charset=utf8mb4", HOSTUSERNAME, HOSTPASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
	
}catch(PDOException $e){
	error_log('Database connection failed: ' . $e->getMessage());
	echo "Veritabanı bağlantısı kurulamadı.";
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
