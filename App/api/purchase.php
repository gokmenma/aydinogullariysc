<?php
require_once dirname(__DIR__, 2) . "/bootstrap.php";

use App\Helper\Helper;
use App\Helper\Security;
use App\Model\PurchaseModel;

$Purchase = new PurchaseModel();

if ($_POST['action'] == 'doneDemand') {
    $id = $_POST['id'];

    try {
        $sql = $ac->prepare('UPDATE purchases SET state = 2 WHERE id = ?');
        $sql->execute(array($id));
        audit_log("status_change", "purchases", "Satın alma talebi tamamlandı", "purchase", $id, [
            'new_status' => 2,
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
        audit_log("delete", "purchases", "Satın alma kaleminin dosyası silindi", "purchase_item", $itemId, [
            'purchase_id' => (int) $purchaseId,
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
        if (!empty($urunAdi) && is_array($urunAdi)) {
            //Satın alma'nın ürünlerini siler
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

