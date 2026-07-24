<?php

require_once dirname(__DIR__, 2) . "/bootstrap.php";


use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Financial;
use App\Model\OfferModel;

$offer = new OfferModel();

// $Financial = new Financial();
if ($_POST['action'] == 'copyOffer') {
    $id = $_POST['id'];

    //teklif var mı kontrol et
    $offerExists = $offer->find($id);
    if (!$offerExists) {
        $res = [
            'status' => 404,
            'message' => 'Teklif bulunamadı.'
        ];
        echo json_encode($res);
        exit;
    }

    $offer->copyOffer($id);
    audit_log("copy", "offers", "Teklif kopyalandı", "offer", $id, ['source_offer_id' => $id]);
    $res = [
        'status' => 200,
        'message' => 'Teklif kopyalandı.'
    ];
    echo json_encode($res);
}

if ($_POST['action'] == 'saveOffer') {
    $id = $_POST['offer_id'];

    //Eğer şablon teklif ise Ş ile başlayan teklif numarasını al
    if (isset($_POST['is_template'])) {
        $is_template = 1;
        $offerNumber = $_POST['templateOfferNumber'];
    } else {
        $is_template = 0;
        $offerNumber = $_POST['offerNumber'];
    }


    /**teklif numarası var mı kontrol et */
    if ($offer->checkOfferNumberExists($offerNumber, $id)) {
        $offerNumber = preg_replace('/[^0-9]/', '', $offerNumber);

        //Teklif numarası en yüksek numarayı bul
        $highestNumber = Helper::getHighestOffereNumber() + 1;

        if ($is_template == 1) {
            Helper::setDefineNumber("template_offer", $highestNumber);
            $offerNumber = Helper::generateNumber("template_offer", "Ş");
        } else {
            Helper::setDefineNumber("offer", $highestNumber);
            $offerNumber = Helper::generateNumber("offer", "TK");
        }
    }

    try {
        //transaction başlat
        //$offer->beginTransaction();

        $data = [
            'id' => $id,
            "offerNumber" => $offerNumber,
            "cid" => $_POST['customers'],
            "company_authors" => $_POST['compAuths'],
            "offer_subject" => $_POST['offer_subject'],
            "currency" => $_POST["currency"],
            "payment_period" => $_POST['payPeriod'],
            "statu" => $_POST['offerstatu'],
            "description" => $_POST['description'],
            "offer_header" => $_POST['offerHeader'],
            "offer_header_content" => $_POST['offerHeaderContent'] ?? '',
            "offer_footer" => $_POST['offerFooter'],
            "offer_footer_content" => $_POST['offerFooterContent'] ?? '',
            "offer_date" => $_POST['offer_date'],
            "euro_alt_toplam" => $_POST['euro_alt_toplam'],
            "dolar_alt_toplam" => $_POST['dolar_alt_toplam'],
            "tl_alt_toplam" => $_POST['tl_alt_toplam'],
            "euro_iskonto" => $_POST['euro_iskonto'],
            "dolar_iskonto" => $_POST['dolar_iskonto'],
            "tl_iskonto" => $_POST['tl_iskonto'],
            "curEuro" => $_POST['cur-Euro'],
            "curDollar" => $_POST['cur-Dollar'],
            "Kdv" => $_POST['Kdv'],
            "euro_ara_toplam" => $_POST['euro_ara_toplam'],
            "dolar_ara_toplam" => $_POST['dolar_ara_toplam'],
            "tl_ara_toplam" => $_POST['tl_ara_toplam'],
            "euro_kdv" => $_POST['euro_kdv'],
            "dolar_kdv" => $_POST['dolar_kdv'],
            "tl_kdv" => $_POST['tl_kdv'],
            "euro_kdvli_toplam" => $_POST['euro_kdvli_toplam'],
            "dolar_kdvli_toplam" => $_POST['dolar_kdvli_toplam'],
            "tl_kdvli_toplam" => $_POST['tl_kdvli_toplam'],
            "tl_toplam_karsilik" => Financial::formattedMoneyToNumber($_POST['tl_toplam_karsilik']),
            "file" => $_FILES['offerFile']['name'],
            "total_price" => Financial::formattedMoneyToNumber($_POST['tl_toplam_karsilik']),
            "tl_alis_toplam" => $_POST['buy-tl-input'],
            "tl_satis_toplam" => $_POST['sale-tl-input'],


        ];
        //yenikayıt ise oluşturan creativer alanını ekle
        if ($id == 0) {
            $data["created_at"] = date("Y-m-d H:i:s");
            $data['creativer'] = $_SESSION["lid"];
            ;
        } else {
            $data['updater'] = $_SESSION["lid"];
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        if (permtrue("template_offer_create")) {
            $data['is_template'] = $is_template;
        } else {
            $data['is_template'] = 0;
        }

        //Teklifin mevcut durumunu al
        if ($id > 0) {
            $currentStatus = $offer->getOfferStatus($id); // Mevcut durumu alın (örneğin, bir model fonksiyonu ile)

            if ($_POST['offerstatu'] == 2 && $currentStatus != 2) {
                $data['onay_tarihi'] = date("Y-m-d H:i:s");
            } else if ($_POST['offerstatu'] == 1) {
                $data['onay_tarihi'] = null; // Onay tarihi boş bırakılır

            }
            ;
        }

        $lastInsertId = $offer->save($data) ?? $id;

        // Log action
        $logAction = ($id == 0) ? "Yeni Teklif Oluşturuldu" : "Teklif Güncellendi";
        audit_log(
            $id == 0 ? "create" : "update",
            "offers",
            "$logAction: $offerNumber",
            "offer",
            $lastInsertId,
            [
                'offer_number' => $offerNumber,
                'customer_id' => (int) $_POST['customers'],
                'status' => (int) $_POST['offerstatu'],
                'currency' => $_POST['currency'],
                'item_count' => isset($_POST['urunAdi']) && is_array($_POST['urunAdi'])
                    ? count($_POST['urunAdi'])
                    : 0,
            ]
        );


        // Dosya yükleme işlemi
        if (isset($_FILES['offerFile'])) {
            //$uploadResult = $offer->uploadFile($_FILES['offerFile']);

            $file_path = $_FILES['offerFile']["tmp_name"];
            $path = "../files/offer/";
            $file_name = uniqid() . $_FILES['offerFile']["name"];
            move_uploaded_file($file_path, $path . $file_name);

        }

        $urunAdi = $_POST['urunAdi'];
        if (isset($urunAdi)) {
            $offer->deleteOfferProduct($id);
            for ($i = 0; $i < count($urunAdi); $i++) {
                $offer->saveOfferProduct([
                    "xid" => $_POST['customers'],
                    "oid" => $lastInsertId,
                    "title" => $urunAdi[$i],
                    "stokKodu" => $_POST['stokKodu'][$i],
                    "amount" => $_POST['amount'][$i],
                    "unit" => $_POST['unit'][$i],
                    "saleprice" => $_POST['saleprice'][$i],
                    "salecur" => $_POST['salecur'][$i],
                    "total_price" => $_POST['total'][$i],
                    "buyprice" => $_POST['buyprice'][$i],
                    "buycur" => $_POST['buycur'][$i],
                    "satirno" => $_POST['satirno'][$i],

                ]);
            }
        }

        // Tamamlandı yapıldığında servis oluştur (eğer daha önce oluşturulmadıysa ve checkbox işaretlendiyse)
        // if ($_POST['offerstatu'] == 2 && isset($_POST['createService']) && $_POST['createService'] == 1) {
        //     $checkService = $ac->prepare("SELECT id FROM projects WHERE poid = ?");
        //     $checkService->execute([$lastInsertId]);
        //     if (!$checkService->fetch()) {
        //         // Müşteri bilgilerini al
        //         $sql_cust = $ac->prepare("SELECT * FROM customers WHERE id = ?");
        //         $sql_cust->execute([$_POST['customers']]);
        //         $cust = $sql_cust->fetch(PDO::FETCH_ASSOC);

        //         // Servis Numarası
        //         $getNumber = setNumber("service");
        //         $service_number = "SRV" . str_pad($getNumber, 5, "0", STR_PAD_LEFT);

        //         $pdesc = $offerNumber . " numaralı teklife ait otomatik oluşturulan servis.";

        //         $regxs = $ac->prepare("INSERT INTO projects SET
        //             pcid = ?, poid = ?, servicestype = ?, service_number = ?,
        //             collectiontype = ?, address = ?, region = ?, pcreativer = ?,
        //             pdesc = ?, pstart_date = ?, pauthors = ?, price = ?,
        //             price_desc = ?, pnotes = ?, pstatu = ?, contract_statu = ?");

        //         $regxs->execute(array(
        //             $_POST['customers'],
        //             $lastInsertId,
        //             41, // Varsayılan SİSTEM KONTROL
        //             $service_number,
        //             'Cari', // Varsayılan Tahsilat Türü
        //             ($cust['address'] ?? '') . ' ' . ($cust['ilce'] ?? '') . ' / ' . ($cust['city'] ?? ''),
        //             $cust['region'] ?? '',
        //             $_SESSION['lid'],
        //             $pdesc,
        //             date('d.m.Y'),
        //             '', // pauthors
        //             Financial::formattedMoneyToNumber($_POST['tl_toplam_karsilik']),
        //             '', // price_desc
        //             '', // pnotes
        //             15, // Varsayılan Bekliyor
        //             1   // Sözleşme Bekliyor
        //         ));

        //         if ($regxs) {
        //             // Sayaç artır
        //             $getNumber += 1;
        //             $upquery = $ac->prepare("UPDATE define_numbers SET service = ?");
        //             $upquery->execute(array($getNumber));
        //         }
        //     }
        // }

        //Geri dönüş mesajı
        if ($id == 0) {
            //eğer is_template ise teklif numarasını güncelle
            if ($is_template == 0) {
                Helper::setDefineNumber("offer");
            } else {
                Helper::setDefineNumber("template_offer");
            }

            $status = 'success';
            $message = 'Teklif başarı ile kaydedildi.';
        } else {
            $status = 'success';
            $message = 'Teklif başarı ile guncellendi.';
        }
        //transaction commit
        //$offer->commit();


    } catch (PDOException $ex) {
        //transaction rollback
        //$offer->rollBack();
        $status = 'error';
        $message = $ex->getMessage();
    }

    $res = array(
        'status' => $status,
        'message' => $message
    );
    echo json_encode($res);
}

//Teklifi Silme 
if ($_POST['action'] == 'deleteOffer') {
    $id = $_POST['id'];
    try {

        $offer->deleteOffer($id);
        audit_log("delete", "offers", "Teklif silindi", "offer", $id);
        $status = 'success';
        $message = 'Teklif başarı ile silindi.';
    } catch (PDOException $ex) {
        $status = 'error';
        $message = $ex->getMessage();
    }

    $res = array(
        'status' => $status,
        'message' => $message
    );
    echo json_encode($res);
}




if ($_POST['action'] == 'convertToTry') {
    $id = $_POST['offer_id'];



    try {
        $offer->convertToTry($id);
        audit_log("update", "offers", "Teklif para birimi TL'ye çevrildi", "offer", $id);
        $status = "success";
        $message = "Teklif TRY'ye çevrildi.";


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
