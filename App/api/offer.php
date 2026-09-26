<?php

require_once dirname(__DIR__, 2) . "/bootstrap.php";

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Financial;
use App\Model\OfferModel;

$offer = new OfferModel();

$action = $_POST['action'] ?? ($_GET['action'] ?? null);

if (!$action) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit;
}

if ($action === 'getOfferLogs') {
    if (empty($_SESSION['login'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Oturum açmanız gerekiyor.']);
        exit;
    }

    if (!permtrue('offerview') && !permtrue('offersView') && !permtrue('offeredit') && !permtrue('offer') && !checkAuth('offerview')) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.']);
        exit;
    }

    $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz teklif ID.']);
        exit;
    }

    try {
        $result = $offer->getOfferLogs($id);
        if (!$result || empty($result['offer'])) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Teklif bulunamadı.']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'offer' => $result['offer'],
            'logs' => $result['logs'],
        ]);
    } catch (\Throwable $ex) {
        error_log("Teklif logları getirme hatası: " . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Log kayıtları yüklenirken bir hata oluştu.']);
    }
    exit;
}

if ($_POST['action'] == 'copyOffer') {
    $id = (int) ($_POST['id'] ?? 0);

    // Teklif var mı kontrol et
    $offerExists = $offer->find($id);
    if (!$offerExists) {
        $res = [
            'status' => 404,
            'message' => 'Teklif bulunamadı.'
        ];
        echo json_encode($res);
        exit;
    }

    try {
        $newId = $offer->copyOffer($id);
        audit_log("copy", "offers", "Teklif kopyalandı", "offer", $newId, ['source_offer_id' => $id]);
        $res = [
            'status' => 200,
            'message' => 'Teklif kopyalandı.',
            'id' => $newId
        ];
    } catch (\Throwable $ex) {
        error_log("Teklif kopyalama hatası: " . $ex->getMessage());
        $res = [
            'status' => 'error',
            'message' => 'Teklif kopyalanırken bir hata oluştu.'
        ];
    }
    echo json_encode($res);
    exit;
}

if ($_POST['action'] == 'saveOffer') {
    $id = (int) ($_POST['offer_id'] ?? 0);
    $offerstatu = (int) ($_POST['offerstatu'] ?? 1);

    // Eğer şablon teklif ise Ş ile başlayan teklif numarasını al
    if (isset($_POST['is_template'])) {
        $is_template = 1;
        $offerNumber = $_POST['templateOfferNumber'] ?? '';
    } else {
        $is_template = 0;
        $offerNumber = $_POST['offerNumber'] ?? '';
    }

    /** Teklif numarası var mı kontrol et */
    if ($offer->checkOfferNumberExists($offerNumber, $id)) {
        $offerNumber = preg_replace('/[^0-9]/', '', $offerNumber);

        // Teklif numarası en yüksek numarayı bul
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
        $existingOffer = $id > 0 ? $offer->find($id) : null;

        $fileName = null;
        if (isset($_FILES['offerFile']) && !empty($_FILES['offerFile']['name'])) {
            $fileName = $_FILES['offerFile']['name'];
        } elseif ($existingOffer && isset($existingOffer->file)) {
            $fileName = $existingOffer->file;
        }

        $rejectReason = trim($_POST['reject_reason'] ?? '');
        $rejectDetail = trim($_POST['reject_detail'] ?? '');

        $data = [
            'id' => $id,
            "offerNumber" => $offerNumber,
            "cid" => $_POST['customers'] ?? 0,
            "company_authors" => $_POST['compAuths'] ?? '',
            "offer_subject" => $_POST['offer_subject'] ?? '',
            "currency" => $_POST["currency"] ?? 'TRY',
            "payment_period" => $_POST['payPeriod'] ?? '',
            "statu" => $offerstatu,
            "reject_reason" => $offerstatu == 3 ? $rejectReason : null,
            "reject_detail" => $offerstatu == 3 ? $rejectDetail : null,
            "description" => $_POST['description'] ?? '',
            "offer_header" => $_POST['offerHeader'] ?? null,
            "offer_header_content" => $_POST['offerHeaderContent'] ?? '',
            "offer_footer" => $_POST['offerFooter'] ?? null,
            "offer_footer_content" => $_POST['offerFooterContent'] ?? '',
            "offer_date" => $_POST['offer_date'] ?? date('Y-m-d'),
            "euro_alt_toplam" => (float) ($_POST['euro_alt_toplam'] ?? 0),
            "dolar_alt_toplam" => (float) ($_POST['dolar_alt_toplam'] ?? 0),
            "tl_alt_toplam" => (float) ($_POST['tl_alt_toplam'] ?? 0),
            "euro_iskonto" => (float) ($_POST['euro_iskonto'] ?? 0),
            "dolar_iskonto" => (float) ($_POST['dolar_iskonto'] ?? 0),
            "tl_iskonto" => (float) ($_POST['tl_iskonto'] ?? 0),
            "curEuro" => (float) ($_POST['cur-Euro'] ?? 0),
            "curDollar" => (float) ($_POST['cur-Dollar'] ?? 0),
            "Kdv" => (float) ($_POST['Kdv'] ?? 0),
            "euro_ara_toplam" => (float) ($_POST['euro_ara_toplam'] ?? 0),
            "dolar_ara_toplam" => (float) ($_POST['dolar_ara_toplam'] ?? 0),
            "tl_ara_toplam" => (float) ($_POST['tl_ara_toplam'] ?? 0),
            "euro_kdv" => (float) ($_POST['euro_kdv'] ?? 0),
            "dolar_kdv" => (float) ($_POST['dolar_kdv'] ?? 0),
            "tl_kdv" => (float) ($_POST['tl_kdv'] ?? 0),
            "euro_kdvli_toplam" => (float) ($_POST['euro_kdvli_toplam'] ?? 0),
            "dolar_kdvli_toplam" => (float) ($_POST['dolar_kdvli_toplam'] ?? 0),
            "tl_kdvli_toplam" => (float) ($_POST['tl_kdvli_toplam'] ?? 0),
            "tl_toplam_karsilik" => Financial::formattedMoneyToNumber($_POST['tl_toplam_karsilik'] ?? 0),
            "file" => $fileName,
            "total_price" => Financial::formattedMoneyToNumber($_POST['tl_toplam_karsilik'] ?? 0),
            "tl_alis_toplam" => (float) ($_POST['buy-tl-input'] ?? 0),
            "tl_satis_toplam" => (float) ($_POST['sale-tl-input'] ?? 0),
            "mycompany" => trim($_POST['mycompany'] ?? ''),
            "authors" => trim($_POST['authors'] ?? ''),
            "notes" => $_POST['notes'] ?? '',
            "subdescription" => $_POST['subdescription'] ?? '',
            "tax" => (int) ($_POST['tax'] ?? 0),
            "dollar" => (float) ($_POST['dollar'] ?? 0),
            "euro" => (float) ($_POST['euro'] ?? 0),
        ];

        // Durum tarihlerini ayarla
        if ($offerstatu == 2) {
            $data['onay_tarihi'] = ($existingOffer && $existingOffer->statu == 2 && !empty($existingOffer->onay_tarihi))
                ? $existingOffer->onay_tarihi
                : date("Y-m-d H:i:s");
            $data['reject_date'] = null;
        } elseif ($offerstatu == 3) {
            $data['reject_date'] = ($existingOffer && $existingOffer->statu == 3 && !empty($existingOffer->reject_date))
                ? $existingOffer->reject_date
                : date("Y-m-d H:i:s");
            $data['onay_tarihi'] = null;
        } else {
            $data['onay_tarihi'] = null;
            $data['reject_date'] = null;
        }

        // Yeni kayıt ise oluşturan creativer alanını ekle
        if ($id == 0) {
            $data["created_at"] = date("Y-m-d H:i:s");
            $data['creativer'] = $_SESSION["lid"] ?? null;
        } else {
            $data['updater'] = $_SESSION["lid"] ?? null;
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        if (permtrue("template_offer_create")) {
            $data['is_template'] = $is_template;
        } else {
            $data['is_template'] = 0;
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
                'customer_id' => (int) ($_POST['customers'] ?? 0),
                'status' => $offerstatu,
                'reject_reason' => $offerstatu == 3 ? $rejectReason : null,
                'currency' => $_POST['currency'] ?? 'TRY',
                'item_count' => isset($_POST['urunAdi']) && is_array($_POST['urunAdi'])
                    ? count($_POST['urunAdi'])
                    : 0,
                'changed_fields' => $id > 0
                    ? audit_changes(
                        $existingOffer,
                        $data,
                        [
                            'offerNumber', 'cid', 'company_authors', 'offer_subject',
                            'currency', 'payment_period', 'statu', 'reject_reason', 'reject_detail',
                            'description', 'offer_date', 'Kdv', 'total_price', 'tl_alis_toplam',
                            'tl_satis_toplam', 'is_template'
                        ]
                    )
                    : [],
            ]
        );

        // Dosya yükleme işlemi
        if (isset($_FILES['offerFile']) && !empty($_FILES['offerFile']['tmp_name'])) {
            $file_path = $_FILES['offerFile']["tmp_name"];
            $path = dirname(__DIR__, 2) . "/files/offer/";
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            $file_name = uniqid() . '_' . basename($_FILES['offerFile']["name"]);
            move_uploaded_file($file_path, $path . $file_name);
        }

        $urunAdi = $_POST['urunAdi'] ?? null;
        if (isset($urunAdi) && is_array($urunAdi)) {
            $offer->deleteOfferProduct($id);
            for ($i = 0; $i < count($urunAdi); $i++) {
                if (empty($urunAdi[$i])) {
                    continue;
                }
                $offer->saveOfferProduct([
                    "xid" => $_POST['customers'] ?? 0,
                    "oid" => $lastInsertId,
                    "title" => $urunAdi[$i],
                    "stokKodu" => $_POST['stokKodu'][$i] ?? '',
                    "amount" => $_POST['amount'][$i] ?? 1,
                    "unit" => $_POST['unit'][$i] ?? '',
                    "saleprice" => (float) ($_POST['saleprice'][$i] ?? 0),
                    "salecur" => $_POST['salecur'][$i] ?? 'TRY',
                    "total_price" => (float) ($_POST['total'][$i] ?? 0),
                    "buyprice" => (float) ($_POST['buyprice'][$i] ?? 0),
                    "buycur" => $_POST['buycur'][$i] ?? 'TRY',
                    "satirno" => (int) ($_POST['satirno'][$i] ?? ($i + 1)),
                ]);
            }
        }

        // Geri dönüş mesajı
        if ($id == 0) {
            if ($is_template == 0) {
                Helper::setDefineNumber("offer");
            } else {
                Helper::setDefineNumber("template_offer");
            }

            $status = 'success';
            $message = 'Teklif başarı ile kaydedildi.';
        } else {
            $status = 'success';
            $message = 'Teklif başarı ile güncellendi.';
        }
    } catch (\Throwable $ex) {
        error_log("Teklif kaydetme hatası: " . $ex->getMessage());
        $status = 'error';
        $message = 'Teklif kaydedilirken bir veritabanı hatası oluştu. Lütfen tekrar deneyiniz.';
    }

    $res = array(
        'status' => $status,
        'message' => $message,
        'id' => $lastInsertId ?? $id
    );
    echo json_encode($res);
    exit;
}

// Teklifi Silme 
if ($_POST['action'] == 'deleteOffer') {
    $id = (int) ($_POST['id'] ?? 0);
    try {
        $offer->deleteOffer($id);
        audit_log("delete", "offers", "Teklif silindi", "offer", $id);
        $status = 'success';
        $message = 'Teklif başarı ile silindi.';
    } catch (\Throwable $ex) {
        error_log("Teklif silme hatası: " . $ex->getMessage());
        $status = 'error';
        $message = 'Teklif silinirken bir hata oluştu.';
    }

    $res = array(
        'status' => $status,
        'message' => $message
    );
    echo json_encode($res);
    exit;
}

if ($_POST['action'] == 'convertToTry') {
    $id = (int) ($_POST['offer_id'] ?? 0);

    try {
        $offer->convertToTry($id);
        audit_log("update", "offers", "Teklif para birimi TL'ye çevrildi", "offer", $id);
        $status = "success";
        $message = "Teklif TRY'ye çevrildi.";
    } catch (\Throwable $ex) {
        error_log("Teklif TL'ye çevirme hatası: " . $ex->getMessage());
        $status = "error";
        $message = "Teklif TRY'ye çevrilirken bir hata oluştu.";
    }

    $res = [
        'status' => $status,
        'message' => $message
    ];
    echo json_encode($res);
    exit;
}
