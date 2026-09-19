<?php
require_once dirname(__DIR__, 3) . "/bootstrap.php";


use App\Helper\Helper;
use App\Helper\Security;
use App\Model\ProductModel;

$Products = new ProductModel();


if ($_POST['action'] == 'save-product') {

    $id = $_POST['id'] !=  0 ? Security::decrypt($_POST['id']) : 0;

    try {

        $existingProduct = $id > 0 ? $Products->find($id) : null;
        $data = [
            'id' => $id,
            "StokKodu" => $_POST['StokKodu'],
            "Adi" => $_POST['urunAdi'],
            "Birimi" => $_POST['Birimi'],
            "AlisFiyati" => $_POST['AlisFiyati'],
            "AlisParaBirimi" => $_POST['AlisParaBirimi'],
            "SatisFiyati" => $_POST['SatisFiyati'],
            "SatisParaBirimi" => $_POST['SatisParaBirimi'],
            "Aciklama" => $_POST['Aciklama'] ?? '',
        ];

        if ($id == 0) {
            $data['OlusturmaTarihi'] = date('d.m.Y');
            $data['PersonelID'] = $_SESSION['lid'] ?? 0;
        }

        $lastInsertId = $Products->save($data) ?? $_POST['id'];
        audit_log(
            $id == 0 ? 'create' : 'update',
            'products',
            ($id == 0 ? 'Yeni ürün/hizmet oluşturuldu: ' : 'Ürün/hizmet güncellendi: ') . $data['Adi'],
            'product',
            $lastInsertId,
            [
                'stock_code' => $data['StokKodu'],
                'name' => $data['Adi'],
                'changed_fields' => $id > 0
                    ? audit_changes(
                        $existingProduct,
                        $data,
                        [
                            'StokKodu', 'Adi', 'Birimi', 'AlisFiyati',
                            'AlisParaBirimi', 'SatisFiyati',
                            'SatisParaBirimi', 'Aciklama'
                        ]
                    )
                    : [],
            ]
        );

        $msg = $id == 0 ? "kaydedildi" : "güncellendi";
        $status = "success";
        $message = "Ürün/Hizmet başarıyla " . $msg;


    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    $res = [
        "status" => $status,
        "message" => $message,
        "data" => $data

    ];

    echo json_encode($res);
}

//Ürün silme

if ($_POST['action'] == 'delete-product') {
    $id = $_POST['id'];

    try {
        $decryptedId = Security::decrypt($id);
        $Products->delete($decryptedId);
        audit_log("delete", "products", "Ürün/hizmet silindi", "product", $decryptedId);
        $status = "success";
        $message = "Ürün başarıyla silindi.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    $res = [
        "status" => $status,
        "message" => $message
    ];

    echo json_encode($res);
}
