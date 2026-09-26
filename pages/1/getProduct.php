<?php
require_once '../../configs/config.php';

$id = $_POST["id"];
    $sql = $ac->prepare("SELECT Adi,StokKodu,AlisFiyati,AlisParaBirimi,u.title as Birimi,SatisParaBirimi,SatisFiyati 
                                FROM products p
                                LEFT JOIN units u ON p.Birimi = u.id
                                where p.id = ? order BY Adi");
    $sql->execute(array($id)); // Sorguyu çalıştır

    $row = $sql->fetch(PDO::FETCH_ASSOC);
    echo json_encode(array("Adi" => $row["Adi"], 
                           "StokKodu" => $row["StokKodu"],
                           "AlisFiyati" => $row["AlisFiyati"],
                           "buycur" => $row["AlisParaBirimi"],
                           "unit" => $row["Birimi"],
                           "salecur" => $row["SatisParaBirimi"],
                           "SatisFiyati" =>  $row["SatisFiyati"] ));

