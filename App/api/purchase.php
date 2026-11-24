<?php
require_once dirname(__DIR__, 2) . "/bootstrap.php";

use App\Helper\Helper;
use App\Helper\Security;
use App\Model\PurchaseModel;
use App\Model\PurchaseHistoryModel;

$Purchase = new PurchaseModel();

if (($_POST['action'] ?? '') == 'convertPriceRequestToOrder') {
    header('Content-Type: application/json; charset=utf-8');

    if (!permtrue('purchaseadd')) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Bu işlem için yetkiniz yok.']);
        exit;
    }

    $requestId = (int)($_POST['id'] ?? 0);
    $own = permtrue('tum_fiyat_taleplerini_gor');
    $result = null;

    try {
        if ($requestId <= 0) {
            throw new \DomainException('Geçersiz fiyat talebi.');
        }
        if (!$own) {
            $row = $Purchase->find($requestId);
            if (!$row || (int)$row->creator !== (int)sesset('id')) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Bu fiyat talebi için yetkiniz yok.']);
                exit;
            }
        }
        $result = $Purchase->convertPriceRequestToOrder($requestId, $_SESSION['lid']);
        audit_log("create", "purchases", "Fiyat talebi siparişe çevrildi: " . $result['request_no'] . " -> " . $result['order_no'], "purchase", $result['order_id'], [
            'request_id' => $requestId,
            'request_number' => $result['request_no'],
            'order_number' => $result['order_no'],
            'item_count' => $result['item_count'],
        ]);
        audit_log("status_change", "purchases", "Siparişe çevrildi: " . $result['request_no'] . " -> " . $result['order_no'], "purchase", $requestId, [
            'order_id' => $result['order_id'],
            'order_number' => $result['order_no'],
        ]);
        echo json_encode([
            'status' => 'success',
            'message' => $result['request_no'] . ' talebi ' . $result['order_no'] . ' numaralı siparişe çevrildi.',
        ]);
    } catch (\DomainException $ex) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
    } catch (\Throwable $ex) {
        error_log('Price request convert failed: ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Sipariş oluşturulurken bir hata oluştu.']);
    }
    exit;
}

if (($_POST['action'] ?? '') == 'getPurchaseHistory') {
    header('Content-Type: application/json; charset=utf-8');
    $historyId = (int) ($_POST['id'] ?? 0);
    $canSeeAll = permtrue('purchaseedit') || permtrue('purchasedelete') || permtrue('purchase_dashboard') || permtrue('tum_fiyat_taleplerini_gor');
    if ($historyId <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz kayıt.']);
        exit;
    }
    try {
        $row = $Purchase->find($historyId);
        if (!$row || (!$canSeeAll && (int) $row->creator !== (int) sesset('id'))) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Bu kaydın log kayıtlarını görme yetkiniz yok.']);
            exit;
        }
        $history = new PurchaseHistoryModel();
        echo json_encode(['status' => 'success', 'purchase' => $history->getSummary($historyId), 'logs' => $history->getHistory($historyId)], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $ex) {
        error_log('Purchase logs failed: ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Log kayıtları alınırken bir hata oluştu.']);
    }
    exit;
}

if ($_POST['action'] == 'doneDemand') {
    $id = $_POST['id'];

    try {
        $oldRow = $Purchase->find((int) $id);
        $sql = $ac->prepare('UPDATE purchases SET state = 2 WHERE id = ?');
        $sql->execute(array($id));
        audit_log("status_change", "purchases", "Durum değişti: " . ($oldRow->siparisNo ?? ('#' . $id)), "purchase", $id, [
            'changed_fields' => ['state' => ['old' => $oldRow->state ?? null, 'new' => 2]],
        ]);

        $status = 'success';
        $message = 'Talep başarıyla tamamlandı.';
    } catch (PDOException $ex) {
        $status = 'error';
        error_log('Purchase status update failed: ' . $ex->getMessage());
        $message = 'Satın alma işlemi sırasında bir hata oluştu.';
    }

    $res = array(
        'status' => $status,
        'message' => $message
    );
    echo json_encode($res);
    exit;
}

if ($_POST['action'] == 'deleteItemFile') {
    $purchaseId = $_POST['purchaseId'];
    $itemId = $_POST['itemId'];

    try {
        // Clear both image and excel_file fields as we merged them in the UI
        $sql = $ac->prepare('UPDATE purchase_items SET image = NULL, excel_file = NULL WHERE id = ?');
        $sql->execute(array($itemId));
        audit_log("delete", "purchases", "Satın alma kaleminin dosyası silindi", "purchase", (int) $purchaseId, [
            'item_id' => (int) $itemId,
        ]);

        $status = 'success';
        $message = 'Dosya başarıyla silindi.';
    } catch (PDOException $ex) {
        $status = 'error';
        $message = $ex->getMessage();
    }

    $res = array(
        'status' => $status,
        'message' => $message
    );
    echo json_encode($res);
    exit;
}

if ($_POST['action'] == 'savePurchases') {

    $id = $_POST['id'];
    $wasNewPurchase = ((int) $id === 0) || ((int) ($_POST['demand'] ?? 0) === 1);
    $existingPurchase = !$wasNewPurchase && (int) $id > 0
        ? $Purchase->find((int) $id)
        : null;
    $demand = $_POST['demand'] ?? 0;
    
    //Eğer satın alma talebinden geliyorsa yeni bir satın alma işlemi oluşturulacak
    if ($demand == 1) {
        $id = 0;
    }

    try {
        $vadeGun = 0;
        if (isset($_POST['vadeGun']) && is_numeric(trim((string)$_POST['vadeGun']))) {
            $vadeGun = (int)trim((string)$_POST['vadeGun']);
        }

        $dollarRate = 0.0;
        if (!empty($_POST['curDollar'])) {
            $dollarRate = (float)str_replace(',', '.', (string)$_POST['curDollar']);
        } elseif (!empty($_POST['Dollar'])) {
            $dollarRate = (float)str_replace(',', '.', (string)$_POST['Dollar']);
        }

        $euroRate = 0.0;
        if (!empty($_POST['curEuro'])) {
            $euroRate = (float)str_replace(',', '.', (string)$_POST['curEuro']);
        } elseif (!empty($_POST['Euro'])) {
            $euroRate = (float)str_replace(',', '.', (string)$_POST['Euro']);
        }

        $data = [
            'id' => $id,
            'siparisNo' => $_POST['siparisNo'],
            'companyID' => $_POST['customers'],
            'currency' => $_POST['cur_type'],
            'deadline' => $_POST['deadline'],
            'payment_period' => $_POST['payment_period'] ?? '',
            'payment_date' => $_POST['payment_date'] ?? '',
            'description1' => $_POST['description1'],
            'description2' => $_POST['description2'] ?? '',
            'altToplam' => $_POST['altToplam'],
            'vadeGun' => $vadeGun,
            'Dollar' => $dollarRate,
            'Euro' => $euroRate,
            'DolarTotal' => $_POST['DolarAlttoplam'] ?? 0,
            'EuroTotal' => $_POST['EuroAlttoplam'] ?? 0,
            'TLTotal' => $_POST['TLAlttoplam'] ?? 0,
            'Kdv' => $_POST['Kdv'] ?? 0,
            'iskonto' => $_POST['iskonto'] ?? 0,
            'ToplamTL' => $_POST['ToplamTL'] ?? 0,
            'state' => $_POST['state'] ?? 1,
            'invoice_date' => $_POST['invoice_date'] ?? '',
            'invoice_number' => $_POST['invoice_number'] ?? '',
            'emailState' => $_POST['emailState'] ?? '0',
            'type' => $_POST['type'] ?? 0
        ];
        $purchaseAuditData = $data;

        if ($id == 0) {
            $data['creator'] = $_SESSION['lid'];
      
        } else {
            $data['updater'] = $_SESSION['lid'];
        }

        $lastInsertId = $Purchase->save($data) ?? $id;

        $talepSipariseDonuyor = $_POST['satinAlmaTalebiniKapat'] ?? 0;
        $talep_id = $_POST['talep_id'] ?? 0;

        if( $talepSipariseDonuyor == 1) {
            $data = [
                'id' => $talep_id,
                'state' => 2, //Siparişe dönüştürülmüş
                'talep_id' => $talep_id,
            ];
            $Purchase->save($data);
        }

        $urunAdi = $_POST['urunAdi'] ?? [];
        $oldItems = [];
        $newItems = [];
        if (!empty($urunAdi) && is_array($urunAdi)) {
            $oldItems = (int) $id > 0
                ? array_map('get_object_vars', $Purchase->getPurchaseItems((int) $id))
                : [];
            $Purchase->deletePurchaseItems($id);

            //Satın alma'nın ürünlerini ekler
            for ($i = 0; $i < count($urunAdi); $i++) {
                $itemData = [
                    'purID' => $lastInsertId,
                    'product' => $urunAdi[$i],
                    'stokKodu' => $_POST['stokKodu'][$i],
                    'amount' => $_POST['amount'][$i],
                    'unit' => $_POST['unit'][$i],
                    'price' => $_POST['price'][$i],
                    'currency' => $_POST['currency'][$i],
                    'rowdescription' => $_POST['rowdescription'][$i] ?? ''
                ];

                // Tek bir dosya yükleme işlemi (Resim veya Excel)
                if (isset($_FILES["row_file_$i"]) && $_FILES["row_file_$i"]['name'] != '') {
                    $fileError = $_FILES["row_file_$i"]['error'];
                    
                    if ($fileError === UPLOAD_ERR_OK) {
                        $fileSize = $_FILES["row_file_$i"]['size'];
                        $maxSize = 5 * 1024 * 1024; // 5MB limit
                        if ($fileSize > $maxSize) {
                            throw new Exception("Dosya Yükleme Hatası (" . $_FILES["row_file_$i"]['name'] . "): Dosya boyutu 5MB sınırını aşıyor.");
                        }

                        $fileInfo = pathinfo($_FILES["row_file_$i"]['name']);
                        $ext = strtolower($fileInfo['extension'] ?? '');
                        $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES["row_file_$i"]['name']);
                        $uploadFolder = dirname(__DIR__, 2) . "/uploads/purchases";
                        $targetPath = "uploads/purchases/" . $fileName;
                        $fullDest = $uploadFolder . "/" . $fileName;
                        
                        // Check if directory exists and is writable
                        if (!is_dir($uploadFolder)) {
                            throw new Exception("Yükleme klasörü mevcut değil: uploads/purchases");
                        }
                        if (!is_writable($uploadFolder)) {
                            throw new Exception("Sunucu klasöre yazma yetkisine sahip değil: uploads/purchases");
                        }

                        if (move_uploaded_file($_FILES["row_file_$i"]['tmp_name'], $fullDest)) {
                            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            // Desteklenen doküman uzantılarını genişlettik (docx, doc, zip vb.)
                            $docExtensions = ['xls', 'xlsx', 'csv', 'pdf', 'docx', 'doc', 'zip', 'rar', 'txt'];
                            
                            if (in_array($ext, $imageExtensions)) {
                                $itemData['image'] = $targetPath;
                            } elseif (in_array($ext, $docExtensions)) {
                                $itemData['excel_file'] = $targetPath;
                            } else {
                                // Diğer tipleri de doküman olarak kabul et ki havada kalmasın
                                $itemData['excel_file'] = $targetPath;
                            }
                        } else {
                            throw new Exception("Dosya sunucuya taşınırken bilinmeyen bir hata oluştu: " . $_FILES["row_file_$i"]['name']);
                        }
                    } elseif ($fileError != UPLOAD_ERR_NO_FILE) {
                        // PHP'nin kendi upload hatalarını yakala (Boyut sınırı vb.)
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE   => 'Dosya sunucu yükleme sınırını (upload_max_filesize) aşıyor.',
                            UPLOAD_ERR_FORM_SIZE  => 'Dosya HTML formu limitini aşıyor.',
                            UPLOAD_ERR_PARTIAL    => 'Dosya sadece kısmen yüklenebildi.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Sunucu geçici klasörü bulunamadı.',
                            UPLOAD_ERR_CANT_WRITE => 'Dosya sunucu diskine yazılamadı.',
                            UPLOAD_ERR_EXTENSION  => 'Bir PHP uzantısı dosya yüklemesini durdurdu.'
                        ];
                        $errText = $errorMessages[$fileError] ?? 'Bilinmeyen PHP upload hatası kodu: ' . $fileError;
                        throw new Exception("Dosya Yükleme Hatası (" . $_FILES["row_file_$i"]['name'] . "): " . $errText);
                    }
                } else {
                    // Yeni dosya yüklenmediyse, frontend'den gelen mevcut dosya yollarını koru
                    if (!empty($_POST['existing_image'][$i])) {
                        $itemData['image'] = $_POST['existing_image'][$i];
                    }
                    if (!empty($_POST['existing_excel_file'][$i])) {
                        $itemData['excel_file'] = $_POST['existing_excel_file'][$i];
                    }
                }

                $newItems[] = $itemData;
                $Purchase->savePurchaseItems($itemData);
            }
        }

        

      

//Geriye mesaj ve durumu döndür
        $status = "success";
        $message = "İşlem başarıyla tamamlandı." ;
        $id = $lastInsertId;
        $purchaseType = (int) ($_POST['type'] ?? 0) === 2 ? 'Fiyat talebi' : 'Satın alma';
        audit_log(
            $wasNewPurchase ? "create" : "update",
            "purchases",
            $purchaseType . ($wasNewPurchase ? " oluşturuldu: " : " güncellendi: ") . ($_POST['siparisNo'] ?? ''),
            "purchase",
            $lastInsertId,
            [
                'order_number' => $_POST['siparisNo'] ?? '',
                'customer_id' => (int) ($_POST['customers'] ?? 0),
                'item_count' => isset($_POST['urunAdi']) && is_array($_POST['urunAdi'])
                    ? count($_POST['urunAdi'])
                    : 0,
                'type' => (int) ($_POST['type'] ?? 0),
                'changed_fields' => !$wasNewPurchase
                    ? audit_changes(
                        $existingPurchase,
                        $purchaseAuditData,
                        [
                            'siparisNo', 'companyID', 'currency', 'deadline',
                            'payment_period', 'payment_date', 'description1',
                            'description2', 'altToplam', 'vadeGun',
                            'DolarTotal', 'EuroTotal', 'TLTotal', 'Kdv',
                            'iskonto', 'ToplamTL', 'state', 'invoice_date',
                            'invoice_number', 'type'
                        ]
                    )
                    : [],
                'item_changes' => !$wasNewPurchase && $newItems
                    ? PurchaseHistoryModel::diffItems($oldItems, $newItems)
                    : [],
            ]
        );


        //Bir sonraki sipariş numarasını belirle
        if ($_POST['type'] == 2) {
             Helper::setDefineNumber('price_request');
        } else {
             Helper::setDefineNumber('purchase');
        }
    } catch (Exception $ex) {
        $status = "error";
        $message = $ex->getMessage();
        error_log("Purchase save error: " . $message);
    }

    $editUrl = $status === 'success' && (int)$id > 0
        ? 'siparis-duzenle?id=' . Security::encrypt((string)$id)
        : null;

    $res = [
        'status' => $status,
        'message' => $message,
        'edit_url' => $editUrl,
    ];

    echo json_encode($res);

}

