<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Model\CustomerModel;

$id = $_POST["id"] ?? null;
$type = $_GET["type"] ?? null;

//RAPOR DETAYI SORGULAMA İÇİN
if ($type == "report-detail") {
    $sql = $ac->prepare("SELECT rp.id, u.username as username,rp.create_time 
                         FROM reports rp 
                         LEFT JOIN users u ON u.id= rp.creator 
                         WHERE rp.id = ?");
    $sql->execute(array($id)); // Sorguyu çalıştır

    $row = $sql->fetch(PDO::FETCH_ASSOC);
    echo json_encode(
        array(
            "creator" => $row["username"],
            "create_time" => $row["create_time"],
        )
    );
}


//MUSTERİ DETAYI SORGULAMA İÇİN
if ($type == "customer-detail") {

    try {
        $sql = $ac->prepare("SELECT c.id, u.username as username, c.regdate, c.updater, c.updated_at  from customers c
                         LEFT JOIN users u ON u.id = c.creativer
                         where c.id = ?");
        $sql->execute(array($id)); // Sorguyu çalıştır

        $row = $sql->fetch(PDO::FETCH_ASSOC);

        $res = array(
            "creator" => $row["username"],
            "create_time" => $row["regdate"],
            "updater" => $row["updater"],
            "updated_at" => $row["updated_at"],
        );
        echo json_encode($res);
     
       return false;
    } catch (PDOException $ex) {
        $message = $ex->getMessage();
        $statu = 400;
    }

    //echo json_encode($res);
}



if ($type == "delete-file") {

    $sql = $ac->prepare("SELECT * FROM offers where id = ?");
    $sql->execute(array($id));
    $result = $sql->fetch(PDO::FETCH_ASSOC);
    $file_path = "../../files/offer/" . $result["file"];


    if (file_exists($file_path)) {
        unlink($file_path);
        $sql = $ac->prepare("UPDATE offers SET file = '' WHERE id = ?");
        $sql->execute(array($id));
        echo json_encode(
            array(
                "message" => "Belirlenen dosya silindi.",
                "status" => "success"
            )
        );

    } else {
        echo json_encode(
            array(
                "message" => "Belirlenen dosya " . $file_path . " dizininde değildir.",
                "status" => "error"
            )
        );
        ;
    }


}

if ($_POST["page"] == "offers" && $_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
    $id = @$_POST["id"];
    permcontrol("offerdelete");
    try {
        $ofs = $ac->prepare("SELECT o.*, c.company as customer_name FROM offers o LEFT JOIN customers c ON c.id = o.cid WHERE o.id = ?");
        $ofs->execute(array($id));
        $of = $ofs->fetch(PDO::FETCH_ASSOC);

        $matterQ = $ac->prepare("SELECT id, product_name, amount, price, total_price, currency, description FROM offermatters WHERE oid = ?");
        $matterQ->execute(array($id));
        $matters = $matterQ->fetchAll(PDO::FETCH_ASSOC);

        $fileq = $ac->prepare("SELECT * FROM files WHERE oid = ?");
        $fileq->execute(array($id));
        $deletedFileList = [];
        while ($ff = $fileq->fetch(PDO::FETCH_ASSOC)) {
            $deletedFileList[] = $ff["filename"];
            @unlink("../../files/offer/" . $ff["filename"]);
        }
        $delets = $ac->prepare("DELETE FROM files WHERE oid = ?");
        $delets->execute(array($id));

        if ($of["statu"] == 3 || $of["statu"] == 5) {
            $deleteproj = $ac->prepare("UPDATE projects SET deleted_at = NOW(), deleted_by = ? WHERE poid = ? AND deleted_at IS NULL");
            $deleteproj->execute(array($_SESSION['lid'] ?? 0, $of["id"]));
        }
        $deleteone = $ac->prepare("DELETE FROM offers WHERE id = ?");
        $deleteone->execute(array($id));

        $deleteonet = $ac->prepare("DELETE FROM offermatters WHERE oid = ?");
        $deleteonet->execute(array($id));

        $offerContext = [
            'id' => $id,
            'offer_number' => $of['offerNumber'] ?? null,
            'customer_id' => $of['cid'] ?? null,
            'customer_name' => $of['customer_name'] ?? null,
            'offer_date' => $of['offer_date'] ?? ($of['created_at'] ?? null),
            'subject' => $of['offer_subject'] ?? null,
            'total_price' => $of['total_price'] ?? null,
            'currency' => $of['currency'] ?? null,
            'status_id' => $of['statu'] ?? null,
            'description' => $of['description'] ?? null,
            'matters_count' => count($matters),
            'matters' => $matters,
            'files_count' => count($deletedFileList),
            'files' => $deletedFileList,
            'deleted_by_user_id' => $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0),
            'deleted_by_username' => $_SESSION['username'] ?? null,
        ];

        audit_log(
            "delete",
            "offers",
            "Teklif silindi: " . ($of['offerNumber'] ?? ('#' . $id)) . (!empty($of['customer_name']) ? " (" . $of['customer_name'] . ")" : ""),
            "offer",
            $id,
            $offerContext
        );

        $res = array(
            "message" => "Başarılı", // Silme işlemi başarılı olduğunda başarılı mesajı döndürülür
            "status" => 200 // Başarılı durum kodu
        );
        echo json_encode($res);
        return false;
    } catch (PDOException $e) {
        $res = array(
            "message" => "İşlem sırasında bir hata oluştu.",
            "status" => 400 // Başarısız durum kodu
        );
        echo json_encode($res);
        return false;
    }
}


if ($_POST["page"] == "reports/reports") {
    $ris = $_GET["id"];
    if ($ris && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
        // permcontrol("reportdel");
        try {
            // RAPOR BİLGİLERİNİ VE SNAPSHOT'I ÇEK
            $repStmt = $ac->prepare("SELECT r.*, rt.name as report_type_name, rt.page_link, u.username as creator_name, c.company as customer_name 
                                     FROM reports r 
                                     LEFT JOIN report_types rt on rt.id = r.report_type 
                                     LEFT JOIN users u ON u.id = r.creator
                                     LEFT JOIN customers c ON c.id = r.cid
                                     WHERE r.id = ?");
            $repStmt->execute(array($ris));
            $repData = $repStmt->fetch(PDO::FETCH_ASSOC);
            $content_table = $repData["page_link"] ?? null;

            //Bazı raporlara ait içerik olmadığından böyle bir tablo var mı diye kontrol edilir
            $content_table_name = 'report_' . $content_table . '_content';
            if ($content_table && isTableExists($content_table_name)) {
                $delete_content = $ac->prepare("DELETE FROM " . $content_table_name . " WHERE report_id = ?");
                $delete_content->execute(array($ris));
            }

            // RAPORA AİT DOSYALAR GETİRİLİR VE FILES KLASORUNDEN SİLİNİR
            $report_files = $ac->prepare("SELECT * FROM files WHERE report_id = ?");
            $report_files->execute(array($ris));
            $deletedFiles = [];

            while ($files = $report_files->fetch(PDO::FETCH_ASSOC)) {
                $file_path = "files/" . $files["filename"]; // Dosya yolunu oluştur
                $deletedFiles[] = $files["filename"];
                if (file_exists($file_path)) { // Dosya var mı diye kontrol et
                    @unlink($file_path); // Dosyayı sil
                }
            }
            //RAPORA AİT DOSYALAR VERİTABANINDAN SİLİNİR
            $delete_files = $ac->prepare("DELETE FROM files WHERE report_id = ?");
            $delete_files->execute(array($ris));

            //RAPORUN KENDİSİ VERİTABANINNDAN SİLİNİR
            $delets = $ac->prepare("DELETE FROM reports WHERE id = ?");
            $delets->execute(array($ris));

            $reportContext = [
                'id' => $ris,
                'report_type' => $repData['report_type_name'] ?? ($repData['report_type'] ?? null),
                'customer_id' => $repData['cid'] ?? null,
                'customer_name' => $repData['customer_name'] ?? null,
                'project_id' => $repData['project_id'] ?? null,
                'creator' => $repData['creator_name'] ?? null,
                'create_time' => $repData['create_time'] ?? null,
                'details' => $repData['details'] ?? null,
                'files_count' => count($deletedFiles),
                'files' => $deletedFiles,
                'deleted_by_user_id' => $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0),
                'deleted_by_username' => $_SESSION['username'] ?? null,
            ];

            audit_log(
                "delete",
                "reports",
                "Rapor silindi: " . ($repData['report_type_name'] ?? '#' . $ris) . (!empty($repData['customer_name']) ? " (" . $repData['customer_name'] . ")" : ""),
                "report",
                $ris,
                $reportContext
            );

            $res = array(
                "message" => "Başarılı", // Silme işlemi başarılı olduğunda başarılı mesajı döndürülür
                "status" => 200 // Başarılı durum kodu
            );
            echo json_encode($res);
            return false;
        } catch (PDOException $e) {
            $res = array(
                "message" => $e->getMessage(), // Hata mesajı döndürülür
                "status" => 400 // Başarısız durum kodu
            );
            echo json_encode($res);
            return false;
        }

    }

}





if ($id && $_GET["mode"] == "delete" && $_GET["code"] == "04md177") {
    if ($_POST["page"] != "offers" && $_POST["page"] != "reports/reports") {

        $id = @$_POST["id"];
        $table = $_POST["table"] ? $_POST["table"] : $_POST["page"];
        try {
            if ($_POST["page"] === "customers") {
                if (!permtrue("customerdelete")) {
                    throw new RuntimeException("Firma silme yetkiniz bulunmuyor.");
                }

                $custStmt = $ac->prepare("SELECT * FROM customers WHERE id = ?");
                $custStmt->execute([$id]);
                $custData = $custStmt->fetch(PDO::FETCH_ASSOC);

                $customer = new CustomerModel();
                if (!$customer->softDelete($id, $_SESSION['lid'] ?? 0)) {
                    throw new RuntimeException("Firma bulunamadı veya daha önce silinmiş.");
                }

                $custContext = [
                    'id' => $id,
                    'company' => $custData['company'] ?? null,
                    'email' => $custData['email'] ?? null,
                    'gsm' => $custData['gsm'] ?? null,
                    'yetkili' => $custData['yetkili'] ?? null,
                    'city' => $custData['city'] ?? null,
                    'ilce' => $custData['ilce'] ?? null,
                    'address' => $custData['address'] ?? null,
                    'tax_office' => $custData['tax_office'] ?? null,
                    'tax_number' => $custData['tax_number'] ?? null,
                    'region' => $custData['region'] ?? null,
                    'group' => $custData['grp'] ?? null,
                    'regdate' => $custData['regdate'] ?? null,
                    'deleted_by_user_id' => $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0),
                    'deleted_by_username' => $_SESSION['username'] ?? null,
                ];

                audit_log("delete", "customers", "Firma pasife alındı: " . ($custData['company'] ?? ('#' . $id)), "customer", $id, $custContext);
            } elseif ($_POST["page"] === "permission-settings") {
                if (!permtrue("authDel")) {
                    throw new RuntimeException("Yetki/Pozisyon silme yetkiniz bulunmuyor.");
                }

                $roleStmt = $ac->prepare("SELECT * FROM roles WHERE id = ?");
                $roleStmt->execute([(int)$id]);
                $roleData = $roleStmt->fetch(PDO::FETCH_ASSOC);

                $permModel = new \App\Model\PermissionModel();
                $delRes = $permModel->deleteRole((int)$id);
                if (!$delRes['success']) {
                    throw new RuntimeException($delRes['message']);
                }

                audit_log(
                    "delete",
                    "permission-settings",
                    "Yetki rolü silindi: " . ($roleData['name'] ?? ('#' . $id)),
                    "role",
                    $id,
                    [
                        'id' => $id,
                        'role_name' => $roleData['name'] ?? null,
                        'description' => $roleData['description'] ?? null,
                        'deleted_by_user_id' => $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0),
                        'deleted_by_username' => $_SESSION['username'] ?? null,
                    ]
                );
            } elseif ($_POST["page"] === "all-files") {
                if (!permtrue("filedelete")) {
                    throw new RuntimeException("Dosya silme yetkiniz bulunmuyor.");
                }
                $fileStmt = $ac->prepare("SELECT * FROM upfiles WHERE id = ?");
                $fileStmt->execute([$id]);
                $fileData = $fileStmt->fetch(PDO::FETCH_ASSOC);
                if (!$fileData) {
                    throw new RuntimeException("Silinecek dosya kaydı bulunamadı.");
                }
                if (!empty($fileData['filename'])) {
                    $physicalPath = __DIR__ . "/../../files/" . $fileData['filename'];
                    if (file_exists($physicalPath) && is_file($physicalPath)) {
                        @unlink($physicalPath);
                    }
                }
                $delQ = $ac->prepare("DELETE FROM upfiles WHERE id = ?");
                $delQ->execute([$id]);

                $fileContext = [
                    'id' => $id,
                    'filename' => $fileData['filename'] ?? null,
                    'original_name' => $fileData['original_name'] ?? null,
                    'filesize' => $fileData['filesize'] ?? null,
                    'filetype' => $fileData['filetype'] ?? null,
                    'cid' => $fileData['cid'] ?? null,
                    'category_id' => $fileData['category_id'] ?? null,
                    'uploaded_at' => $fileData['created_at'] ?? ($fileData['uploaded_at'] ?? null),
                    'deleted_by_user_id' => $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0),
                    'deleted_by_username' => $_SESSION['username'] ?? null,
                ];

                audit_log("delete", "all-files", "Dosya silindi: " . ($fileData['filename'] ?? '#' . $id), "upfiles", $id, $fileContext);
            } elseif ($_POST["page"] === "file-categories") {
                if (!permtrue("filedelete") && !permtrue("fileadd")) {
                    throw new RuntimeException("Kategori silme yetkiniz bulunmuyor.");
                }
                // Check if any files are assigned to this category
                $countStmt = $ac->prepare("SELECT COUNT(*) FROM upfiles WHERE cid = ?");
                $countStmt->execute([$id]);
                $linkedCount = (int)$countStmt->fetchColumn();
                if ($linkedCount > 0) {
                    throw new RuntimeException("Bu kategoriye ait {$linkedCount} adet dosya bulunmaktadır. Lütfen önce dosyaları siliniz veya başka bir kategoriye taşıyınız.");
                }
                $catStmt = $ac->prepare("SELECT * FROM upfile_categories WHERE id = ?");
                $catStmt->execute([$id]);
                $catData = $catStmt->fetch(PDO::FETCH_ASSOC);
                if (!$catData) {
                    throw new RuntimeException("Kategori bulunamadı.");
                }
                $delQ = $ac->prepare("DELETE FROM upfile_categories WHERE id = ?");
                $delQ->execute([$id]);

                $catContext = [
                    'id' => $id,
                    'title' => $catData['title'] ?? null,
                    'description' => $catData['description'] ?? null,
                    'color' => $catData['color'] ?? null,
                    'created_at' => $catData['created_at'] ?? null,
                    'deleted_by_user_id' => $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0),
                    'deleted_by_username' => $_SESSION['username'] ?? null,
                ];

                audit_log("delete", "file-categories", "Dosya kategorisi silindi: " . ($catData['title'] ?? '#' . $id), "upfile_categories", $id, $catContext);
            } elseif ($_POST["page"] === "services" || $_POST["page"] === "all-projects" || $_POST["page"] === "service/list" || $table === "projects") {
                if (!permtrue("serviceDel")) {
                    throw new RuntimeException("Servis silme yetkiniz bulunmuyor.");
                }
                $stItem = $ac->prepare("
                    SELECT p.*, 
                           c.company as customer_name,
                           u.username as creator_username,
                           st.title as service_type_name,
                           ss.title as status_name,
                           sr.title as region_name
                    FROM projects p
                    LEFT JOIN customers c ON c.id = p.pcid
                    LEFT JOIN users u ON u.id = p.creativer
                    LEFT JOIN units st ON st.id = p.p_type AND st.statu = 'servicestype'
                    LEFT JOIN units ss ON ss.id = p.pstatu AND ss.statu = 'servicestatus'
                    LEFT JOIN units sr ON sr.id = p.pregion AND sr.statu = 'serviceregion'
                    WHERE p.id = ? AND p.deleted_at IS NULL
                ");
                $stItem->execute([$id]);
                $itemData = $stItem->fetch(PDO::FETCH_ASSOC);
                if (!$itemData) {
                    throw new RuntimeException("Servis kaydı bulunamadı veya daha önce silinmiş.");
                }

                $serviceModel = new \App\Model\ServiceModel();
                $deletedBy = $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0);
                if (!$serviceModel->softDelete($id, $deletedBy)) {
                    throw new RuntimeException("Servis silinirken bir hata oluştu.");
                }

                $serviceContext = [
                    'id' => $id,
                    'service_number' => $itemData['service_number'] ?? null,
                    'customer_id' => $itemData['pcid'] ?? null,
                    'customer_name' => $itemData['customer_name'] ?? null,
                    'service_type' => $itemData['service_type_name'] ?? ($itemData['p_type'] ?? null),
                    'status' => $itemData['status_name'] ?? ($itemData['pstatu'] ?? null),
                    'region' => $itemData['region_name'] ?? ($itemData['pregion'] ?? null),
                    'price' => $itemData['price'] ?? null,
                    'currency' => $itemData['currency'] ?? null,
                    'start_date' => $itemData['start_date'] ?? null,
                    'finish_date' => $itemData['finish_date'] ?? null,
                    'description' => $itemData['description'] ?? null,
                    'creator' => $itemData['creator_username'] ?? null,
                    'created_at' => $itemData['created_at'] ?? null,
                    'deleted_by_user_id' => $deletedBy,
                    'deleted_by_username' => $_SESSION['username'] ?? null,
                ];

                audit_log(
                    "delete",
                    "services",
                    "Servis silindi: " . ($itemData['service_number'] ?? '#' . $id) . (!empty($itemData['customer_name']) ? " (" . $itemData['customer_name'] . ")" : ""),
                    "service",
                    $id,
                    $serviceContext
                );
            } else {
                $deleteTableMap = [
                    'all-files' => 'upfiles',
                    'categories' => 'mainservices',
                    'all-users' => 'users',
                    'all-services' => 'services',
                    'all-projects' => 'projects',
                    'services' => 'projects',
                    'support-list' => 'support_requests',
                    'view-outdocument' => 'evraktakip',
                    'view-indocument' => 'evraktakip',
                    'indocument-categories' => 'indocument_categories',
                    'users' => 'users',
                    'file-categories' => 'upfile_categories',
                    'tasks' => 'todolist',
                    'define-units' => 'units',
                    'paytype' => 'units',
                    'service-type' => 'units',
                    'service-status' => 'units',
                    'service-region' => 'units',
                    'servicestype' => 'units',
                    'offer-templates' => 'offertemplate',
                    'purchases' => 'purchases',
                    'products' => 'products',
                    'products-categories' => 'products_categories',
                    'send-mail-accounts' => 'mail_accounts',
                ];
                $pageKey = (string) $_POST["page"];
                $resolvedTable = $deleteTableMap[$pageKey] ?? null;
                if ($resolvedTable === null || ($table && $table !== $resolvedTable)) {
                    throw new RuntimeException("Geçersiz silme hedefi.");
                }

                // Silinmeden önce kaydın snapshot verisini çek
                $selStmt = $ac->prepare("SELECT * FROM `" . $resolvedTable . "` WHERE id = ?");
                $selStmt->execute([$id]);
                $recordData = $selStmt->fetch(PDO::FETCH_ASSOC);

                if (!$recordData) {
                    throw new RuntimeException("Silinecek kayıt bulunamadı.");
                }

                // Hassas veya gereksiz alanları context'ten temizle (güvenlik)
                $sensitiveFields = ['password', 'pwd', 'pass', 'token', 'secret', 'api_key', 'salt', 'auth_key', 'private_key'];
                $cleanedData = [];
                foreach ($recordData as $k => $v) {
                    if (!in_array(strtolower($k), $sensitiveFields, true)) {
                        $cleanedData[$k] = $v;
                    }
                }
                $entityContext = $cleanedData;
                $entityContext['deleted_by_user_id'] = $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0);
                $entityContext['deleted_by_username'] = $_SESSION['username'] ?? null;

                $entityType = $resolvedTable === 'projects' ? 'service' : $resolvedTable;
                $entitySummary = "Kayıt silindi";

                if ($resolvedTable === 'evraktakip') {
                    $docTypeLabel = ($pageKey === 'view-indocument') ? 'Gelen Evrak' : 'Giden Evrak';
                    $firmName = $recordData['firma'] ?? ($recordData['customer_name'] ?? '');
                    $docSubject = $recordData['aciklama'] ?? ($recordData['evrakturu'] ?? '');
                    $entitySummary = "{$docTypeLabel} silindi: " . ($firmName ? "{$firmName} " : "") . "(#{$id})";
                    if ($docSubject) {
                        $entitySummary .= " - " . mb_substr($docSubject, 0, 40);
                    }
                } elseif ($resolvedTable === 'projects') {
                    $entitySummary = "Servis silindi: " . ($recordData['service_number'] ?? '#' . $id);
                } elseif ($resolvedTable === 'products') {
                    $prodName = $recordData['Adi'] ?? ($recordData['name'] ?? '#' . $id);
                    $entitySummary = "Ürün/hizmet silindi: " . $prodName;
                } elseif ($resolvedTable === 'purchases') {
                    $pTitle = $recordData['title'] ?? ($recordData['company'] ?? '#' . $id);
                    $entitySummary = "Satın alma kaydı silindi: " . $pTitle;
                } elseif ($resolvedTable === 'users') {
                    $uName = $recordData['username'] ?? ($recordData['fullname'] ?? '#' . $id);
                    $entitySummary = "Kullanıcı silindi: " . $uName;
                } elseif ($resolvedTable === 'todolist') {
                    $tTitle = $recordData['title'] ?? ($recordData['content'] ?? '#' . $id);
                    $entitySummary = "Görev silindi: " . mb_substr($tTitle, 0, 50);
                } elseif ($resolvedTable === 'units') {
                    $uTitle = $recordData['title'] ?? ($recordData['name'] ?? '#' . $id);
                    $unitTypeMap = [
                        'define-units' => 'Birim',
                        'paytype' => 'Tahsilat Türü',
                        'service-type' => 'Servis Konusu',
                        'service-status' => 'Servis Durumu',
                        'service-region' => 'Servis Bölgesi',
                        'servicestype' => 'Servis Türü'
                    ];
                    $unitLabel = $unitTypeMap[$pageKey] ?? 'Tanımlama';
                    $entitySummary = "{$unitLabel} silindi: " . $uTitle;
                } elseif ($resolvedTable === 'offertemplate') {
                    $tplTitle = $recordData['title'] ?? ($recordData['name'] ?? '#' . $id);
                    $entitySummary = "Teklif şablonu silindi: " . $tplTitle;
                } elseif ($resolvedTable === 'support_requests') {
                    $sTitle = $recordData['subject'] ?? ($recordData['title'] ?? '#' . $id);
                    $entitySummary = "Destek talebi silindi: " . $sTitle;
                } elseif ($resolvedTable === 'indocument_categories') {
                    $catTitle = $recordData['name'] ?? ($recordData['title'] ?? '#' . $id);
                    $entitySummary = "Evrak kategorisi silindi: " . $catTitle;
                } elseif ($resolvedTable === 'mail_accounts') {
                    $mTitle = $recordData['email_address'] ?? ($recordData['email'] ?? '#' . $id);
                    $entitySummary = "Mail hesabı silindi: " . $mTitle;
                }

                $pdq = $ac->prepare("DELETE FROM `" . $resolvedTable . "` WHERE id = ?");
                $pdq->execute(array($id));
                audit_log(
                    "delete",
                    $pageKey,
                    $entitySummary,
                    $entityType,
                    $id,
                    $entityContext
                );
            }

            $res = array(
                "message" => $_POST["page"] === "customers"
                    ? "Firma listeden kaldırıldı; ilişkili teklif ve servisler korundu."
                    : "Başarılı",
                "status" => 200 // Başarılı durum kodu
            );
            echo json_encode($res);
            return false;
        } catch (Throwable $e) {
            $res = array(
                "message" => "İşlem sırasında bir hata oluştu: " . $e->getMessage(),
                "status" => 400 // Başarısız durum kodu
            );
            echo json_encode($res);
            return false;
        }
    }
}




if (isset($_POST["customer_id"])) {

    try {

        $customer_id = $_POST["customer_id"];
        $sql = $ac->prepare("SELECT * FROM customers WHERE id = ?");
        $sql->execute(array($customer_id));
        $result = $sql->fetch(PDO::FETCH_ASSOC);

        $of_query = $ac->prepare("SELECT id, offerNumber FROM offers WHERE cid = ? AND statu = 2");
        $of_query->execute(array($customer_id));


        $status = 200;
        $res = array(
            "city" => $result["city"],
            "ilce" => $result["ilce"],
            "region" => $result["region"],
            "status" => $status,
            "offers" => $of_query->fetchAll(PDO::FETCH_ASSOC),
        );




        echo json_encode($res);
        return false;

    } catch (PDOException $ex) {

        $res = array(
            "message" => $ex->getMessage(),
            "status" => 400,
        );
        echo json_encode($res);
        return false;
    }
}

// Merkezi AJAX ile Firma/Müşteri Arama Endpoint'i
if (isset($_GET["action"]) && $_GET["action"] == "search-customers") {
    try {
        $q = isset($_GET["q"]) ? trim($_GET["q"]) : '';
        
        if ($q !== '') {
            $sql = $ac->prepare("SELECT id, company FROM customers WHERE deleted_at IS NULL AND company LIKE ? AND company IS NOT NULL AND company != '' ORDER BY company ASC LIMIT 30");
            $sql->execute(array("%" . $q . "%"));
        } else {
            $sql = $ac->prepare("SELECT id, company FROM customers WHERE deleted_at IS NULL AND company IS NOT NULL AND company != '' ORDER BY company ASC LIMIT 30");
            $sql->execute();
        }
        
        $customers = $sql->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        foreach ($customers as $c) {
            $results[] = [
                'id' => $c['id'],
                'text' => $c['company']
            ];
        }
        
        echo json_encode([
            'results' => $results
        ]);
        return false;
    } catch (PDOException $ex) {
        echo json_encode([
            'error' => $ex->getMessage()
        ]);
        return false;
    }
}
