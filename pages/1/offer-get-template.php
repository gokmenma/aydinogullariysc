<?php
require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$isLoggedIn = !empty($_SESSION['login']) || !empty($_SESSION['lid']) || !empty($_SESSION['user_id']) || !empty($_SESSION['id']);
if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Oturum açmanız gerekmektedir.",
        "content" => ""
    ]);
    exit;
}

$id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
if ($id > 0) {
    $sql = $ac->prepare("SELECT * FROM offertemplate WHERE id = ?");
    $sql->execute([$id]);
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode([
            "status" => "success",
            "id" => (int)($row["id"] ?? 0),
            "title" => $row["Title"] ?? '',
            "state" => $row["State"] ?? '',
            "content" => $row["Content"] ?? ''
        ]);
        exit;
    }
}

echo json_encode([
    "status" => "error",
    "content" => ""
]);
