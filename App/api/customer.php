<?php

require_once dirname(__DIR__, 2) . "/bootstrap.php";


use App\Helper\Helper;
use App\Model\CustomerModel;




$Customer = new CustomerModel();

header('Content-Type: application/json; charset=utf-8');

if (($_POST['action'] ?? '') == 'create') {
    $id = intval($_POST['company_id'] ?? 0);

    $requiredPermission = $id > 0 ? 'customeredit' : 'customeradd';
    if (!permtrue($requiredPermission)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Bu firma işlemi için yetkiniz bulunmuyor.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        if ($id > 0) {
            $existingCustomer = $Customer->find($id);
            if (!$existingCustomer || !empty($existingCustomer->deleted_at)) {
                throw new Exception('Silinmiş bir firma kaydı güncellenemez.');
            }
        }

        $company = trim($_POST['company'] ?? '');
        if ($company === '') {
            throw new Exception('Firma adı zorunludur.');
        }
        if ($Customer->companyNameExists($company, $id)) {
            throw new Exception('Aynı isimde aktif bir firma kaydı zaten mevcut.');
        }

        $data = [
            'company' => $company,
            'email' => $_POST['cemail'] ?? '',
            'address' => $_POST['customer_address'] ?? '',
            'city' => $_POST['il'] ?? '',
            'ilce' => $_POST['ilce'] ?? '',
            'cdesc' => $_POST['cdesc'] ?? '',
            'gsm' => $_POST['cgsm'] ?? '',
            'yetkili' => $_POST['yetkili'] ?? '',
            'grp' => $_POST['categoryName'] ?? null,
            'OdemeVade' => $_POST['vade'] ?? '',
            'region' => $_POST['region'] ?? '',
            'represant' => $_POST['represant'] ?? '',
            'updater' => $_SESSION['lid'],
            'updated_at' => date("Y-m-d H:i:s")
        ];

        if ($id > 0) {
            $data['id'] = $id;
        } else {
            $data['creativer'] = $_SESSION['lid'];
            $data['regdate'] = date("Y-m-d H:i:s");
            $data['reg_date'] = date("Y-m-d H:i:s");
        }

        $lastInsertId = $Customer->save($data) ?? $id;
        $status = 'success';
        $message = 'Firma işlemi başarı ile tamamlandı!';


    } catch (Exception $e) {
        $status = 'error';
        $message = $e->getMessage();
    }
    $res = [
        'status' => $status,
        'message' => $message
    ];
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
}

