<?php
define("MATROW", 1); // Sabit tanımı

$tdg = 1;
$tot = 0;

while ($tdg <= MATROW) {
    $tektop = floatval($_POST["price$tdg"]) * floatval($_POST["amount$tdg"]);
    $tot = $tot + $tektop;
    $tdg++;
}

echo "Toplam: " . $tot; // Hesaplanan toplamı ekrana yazdırma
?>