<?php
require_once '../../configs/config.php';

$id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
if ($id > 0) {
    $sql = $ac->prepare("SELECT * FROM offertemplate WHERE id = ?");
    $sql->execute([$id]);
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        "status" => "success",
        "id" => $row["id"] ?? 0,
        "title" => $row["Title"] ?? '',
        "state" => $row["State"] ?? '',
        "content" => $row["Content"] ?? ''
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "content" => ""
    ]);
}
