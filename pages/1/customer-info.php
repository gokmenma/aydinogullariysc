<?php
require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['yetkili' => '', 'odemevadesi' => '', 'error' => 'Yetkisiz erişim.']);
    exit;
}

$id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
if ($id > 0) {
    $sql = $ac->prepare("SELECT yetkili, OdemeVade FROM customers WHERE id = ?");
    $sql->execute([$id]);
    $row = $sql->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode([
            "yetkili" => $row["yetkili"] ?? "",
            "odemevadesi" => $row["OdemeVade"] ?? ""
        ]);
        exit;
    }
}

echo json_encode([
    "yetkili" => "",
    "odemevadesi" => ""
]);