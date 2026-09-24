<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

define("ROOT", $_SERVER['DOCUMENT_ROOT']);
require_once ROOT . '/App/Model/DocumentModel.php';
$Documents = new DocumentModel();


if ($_POST["action"] == "receiveDocument") {
    $id = $_POST['id'];

    try {
        $data = [
            'id' => $id,
            'teslimalmatarihi' => date('Y-m-d H:i:s')
        ];
        $result = $Documents->save($data);
        $status = "success";
        $message = "Evrak teslim alındı";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        'status' => $status,
        'message' => $message
    ];
    echo json_encode($res);

}
