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
        permcontrol("offerdelete");


        $ofs = $ac->prepare("SELECT * FROM offers WHERE id = ?");
        $ofs->execute(array($id));
        $of = $ofs->fetch(PDO::FETCH_ASSOC);

        $fileq = $ac->prepare("SELECT * FROM files WHERE oid = ?");
        $fileq->execute(array($id));
        while ($ff = $fileq->fetch(PDO::FETCH_ASSOC)) {
            unlink("../../files/offer/" . $ff["filename"]);
        }
        $delets = $ac->prepare("DELETE FROM files WHERE oid = ?");
        $delets->execute(array($id));

        if ($of["statu"] == 3 || $of["statu"] == 5) {

            $deleteproj = $ac->prepare("DELETE FROM projects WHERE poid = ?");
            $deleteproj->execute(array($of["id"]));
        }
        $deleteone = $ac->prepare("DELETE FROM offers WHERE id = ?");
        $deleteone->execute(array($id));

        $deleteonet = $ac->prepare("DELETE FROM offermatters WHERE oid = ?");
        $deleteonet->execute(array($id));

        audit_log(
            "delete",
            "offers",
            "Teklif silindi: " . ($of['offerNumber'] ?? ('#' . $id)),
            "offer",
            $id,
            ['offer_number' => $of['offerNumber'] ?? null]
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

            //RAPORA AİT İÇERİKLER SİLİNİR
            $sql = $ac->prepare("SELECT rt.page_link FROM reports r 
                            LEFT JOIN report_types rt on rt.id = r.report_type 
                            WHERE r.id= ?");
            $sql->execute(array($ris));
            $content_table = $sql->fetchColumn();


            //Bazı raporlara ait içerik olmadığından böyle bir tablo var mı diye kontrol edilir
            $content_table_name = 'report_' . $content_table . '_content';
            if (isTableExists($content_table_name)) {
                $delete_content = $ac->prepare("DELETE FROM " . $content_table_name . " WHERE report_id = ?");
                $delete_content->execute(array($ris));
            }

            // RAPORA AİT DOSYALAR GETİRİLİR VE FILES KLASORUNDEN SİLİNİR
            $report_files = $ac->prepare("SELECT * FROM files WHERE report_id = ?");
            $report_files->execute(array($ris));

            while ($files = $report_files->fetch(PDO::FETCH_ASSOC)) {
                $file_path = "files/" . $files["filename"]; // Dosya yolunu oluştur
                if (file_exists($file_path)) { // Dosya var mı diye kontrol et
                    unlink($file_path); // Dosyayı sil
                    echo "Dosya silindi: $file_path<br>"; // İsteğe bağlı: silinen dosyayı göster
                } else {
                    echo "Dosya bulunamadı: $file_path<br>"; // İsteğe bağlı: bulunamayan dosyayı göster
                }
            }
            //RAPORA AİT DOSYALAR VERİTABANINDAN SİLİNİR
            $delete_files = $ac->prepare("DELETE FROM files WHERE report_id = ?");
            $delete_files->execute(array($ris));


            //RAPORUN KENDİSİ VERİTABANINNDAN SİLİNİR
            $delets = $ac->prepare("DELETE FROM reports WHERE id = ?");
            $delets->execute(array($ris));
            audit_log("delete", "reports", "Rapor silindi", "report", $ris);

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

                $customer = new CustomerModel();
                if (!$customer->softDelete($id, $_SESSION['lid'] ?? 0)) {
                    throw new RuntimeException("Firma bulunamadı veya daha önce silinmiş.");
                }
                audit_log("delete", "customers", "Firma pasife alındı", "customer", $id);
            } elseif ($_POST["page"] === "permission-settings") {
                if (!permtrue("authDel")) {
                    throw new RuntimeException("Yetki/Pozisyon silme yetkiniz bulunmuyor.");
                }
                $permModel = new \App\Model\PermissionModel();
                $delRes = $permModel->deleteRole((int)$id);
                if (!$delRes['success']) {
                    throw new RuntimeException($delRes['message']);
                }
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
                audit_log("delete", "all-files", "Dosya silindi: " . ($fileData['filename'] ?? '#' . $id), "upfiles", $id, $fileData);
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
                audit_log("delete", "file-categories", "Dosya kategorisi silindi: " . ($catData['title'] ?? '#' . $id), "upfile_categories", $id, $catData);
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

                $entitySummary = "Kayıt silindi";
                $entityContext = [];
                $entityType = $resolvedTable === 'projects' ? 'service' : $resolvedTable;

                if ($resolvedTable === 'projects') {
                    $stItem = $ac->prepare("SELECT service_number, pcid FROM projects WHERE id = ?");
                    $stItem->execute([$id]);
                    $itemData = $stItem->fetch(PDO::FETCH_ASSOC);
                    if ($itemData) {
                        $entitySummary = "Servis silindi: " . ($itemData['service_number'] ?? '#' . $id);
                        $entityContext = ['service_number' => $itemData['service_number'] ?? null, 'customer_id' => $itemData['pcid'] ?? null];
                    }
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
                "message" => "İşlem sırasında bir hata oluştu.",
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
