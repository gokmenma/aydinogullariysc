<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

use App\Helper\UploadSecurity;

function serviceApiRespond(array $payload, int $statusCode = 200): void
{
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function serviceApiNextSequence(PDO $db): int
{
    $counterQuery = $db->prepare('SELECT service FROM define_numbers LIMIT 1');
    $counterQuery->execute();
    $counter = (int) $counterQuery->fetchColumn();

    $maxQuery = $db->prepare(
        "SELECT COALESCE(MAX(CAST(SUBSTRING(service_number, 4) AS UNSIGNED)), 0)
         FROM projects
         WHERE service_number REGEXP '^SRV[0-9]+$'"
    );
    $maxQuery->execute();

    return max($counter, ((int) $maxQuery->fetchColumn()) + 1, 1);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    serviceApiRespond(['success' => false, 'message' => 'Geçersiz istek yöntemi.'], 405);
}

if (empty($_SESSION['lid'])) {
    serviceApiRespond(['success' => false, 'message' => 'Oturum süreniz sona ermiş. Lütfen tekrar giriş yapın.'], 401);
}

$serviceId = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$isEdit = $serviceId !== false && $serviceId !== null;
$requiredPermission = $isEdit ? 'serviceEdit' : 'serviceAdd';

if (!permtrue($requiredPermission)) {
    serviceApiRespond(['success' => false, 'message' => 'Bu işlem için yetkiniz bulunmuyor.'], 403);
}

$company = filter_var($_POST['company'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$serviceType = filter_var($_POST['ServisKonusu'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$collectionType = filter_var($_POST['TahsilatTuru'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$region = filter_var($_POST['region'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$company || !$serviceType || !$collectionType || !$region) {
    serviceApiRespond([
        'success' => false,
        'message' => '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.',
    ], 422);
}

$offerId = filter_var($_POST['offerno'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$offerId = $offerId ?: null;
$address = trim((string) ($_POST['address'] ?? ''));
$description = trim((string) ($_POST['pdesc'] ?? ''));
$startDate = trim((string) ($_POST['pstartdate'] ?? '')) ?: null;
$secondDate = trim((string) ($_POST['pseconddate'] ?? '')) ?: null;
$price = ($_POST['price'] ?? '') !== '' ? $_POST['price'] : null;
$priceDescription = trim((string) ($_POST['price_desc'] ?? '')) ?: null;
$serviceNote = trim((string) ($_POST['servicesnote'] ?? ''));
$status = filter_var($_POST['pstatu'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$contractStatus = filter_var($_POST['contract_statu'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$authors = array_values(array_filter(array_map('intval', (array) ($_POST['permings'] ?? []))));
$authorIds = $authors ? implode('|', $authors) . '|' : '';
$userId = (int) sesset('id');
$uploadedPath = null;
$serviceNumberLockAcquired = false;

try {
    if ($isEdit) {
        $currentQuery = $ac->prepare('SELECT * FROM projects WHERE id = ?');
        $currentQuery->execute([$serviceId]);
        $current = $currentQuery->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            serviceApiRespond(['success' => false, 'message' => 'Güncellenecek servis kaydı bulunamadı.'], 404);
        }

        $contractUpdatedAt = $current['contract_updated_at'] ?? null;
        $contractUpdatedBy = $current['contract_updated_by'] ?? null;
        if ($contractStatus === 2 && (int) $current['contract_statu'] !== 2) {
            $contractUpdatedAt = date('Y-m-d H:i:s');
            $contractUpdatedBy = $userId;
        } elseif ($contractStatus !== 2) {
            $contractUpdatedAt = null;
            $contractUpdatedBy = null;
        }

        if ($status === 18) {
            $creatorQuery = $ac->prepare('SELECT username FROM users WHERE id = ?');
            $creatorQuery->execute([(int) $current['pcreativer']]);
            $creatorName = (string) ($creatorQuery->fetchColumn() ?: '-');
            $createdAt = date('d.m.Y H:i:s', strtotime($current['pregdate']));
            $serviceNote = 'Servis ' . $creatorName . ' adlı kullanıcı tarafından ' . $createdAt
                . ' tarihinde iptal edilmiştir. ' . $serviceNote;
        }

        $startDate = $startDate ?: ($current['pstart_date'] ?: null);
        $secondDate = $secondDate ?: ($current['psecond_date'] ?: null);

        $ac->beginTransaction();
        $update = $ac->prepare(
            'UPDATE projects SET
                pcid = ?, poid = ?, servicestype = ?, collectiontype = ?, address = ?, region = ?,
                update_at = ?, updater = ?, pdesc = ?, pstart_date = ?, psecond_date = ?, pauthors = ?,
                price = ?, price_desc = ?, pnotes = ?, pstatu = ?, contract_statu = ?,
                contract_updated_at = ?, contract_updated_by = ?
             WHERE id = ?'
        );
        $update->execute([
            $company, $offerId, $serviceType, $collectionType, $address, $region,
            date('Y-m-d H:i:s'), $userId, $description, $startDate, $secondDate, $authorIds,
            $price, $priceDescription, $serviceNote, $status, $contractStatus,
            $contractUpdatedAt, $contractUpdatedBy, $serviceId,
        ]);

        audit_log(
            'update',
            'services',
            'Servis güncellendi: ' . $current['service_number'],
            'service',
            $serviceId,
            [
                'service_number' => $current['service_number'],
                'customer_id' => (int) $company,
                'changed_fields' => audit_changes(
                    $current,
                    [
                        'pcid' => $company,
                        'poid' => $offerId,
                        'servicestype' => $serviceType,
                        'collectiontype' => $collectionType,
                        'address' => $address,
                        'region' => $region,
                        'pstart_date' => $startDate,
                        'price' => $price,
                        'pstatu' => $status,
                        'contract_statu' => $contractStatus,
                    ],
                    ['pcid', 'poid', 'servicestype', 'collectiontype', 'address', 'region', 'pstart_date', 'price', 'pstatu', 'contract_statu']
                ),
            ]
        );
        $ac->commit();

        serviceApiRespond([
            'success' => true,
            'message' => 'Servis başarıyla güncellendi!',
            'service_id' => (int) $serviceId,
            'service_number' => $current['service_number'],
        ]);
    }

    $lockQuery = $ac->prepare('SELECT GET_LOCK(?, 10)');
    $lockQuery->execute(['aydinogullari_service_number']);
    $serviceNumberLockAcquired = (int) $lockQuery->fetchColumn() === 1;
    if (!$serviceNumberLockAcquired) {
        serviceApiRespond(['success' => false, 'message' => 'Servis numarası oluşturulamadı. Lütfen tekrar deneyin.'], 409);
    }

    $ac->beginTransaction();
    $sequence = serviceApiNextSequence($ac);
    $serviceNumber = 'SRV' . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

    $contractUpdatedAt = $contractStatus === 2 ? date('Y-m-d H:i:s') : null;
    $contractUpdatedBy = $contractStatus === 2 ? $userId : null;

    $insert = $ac->prepare(
        'INSERT INTO projects SET
            pcid = ?, poid = ?, servicestype = ?, service_number = ?, collectiontype = ?,
            address = ?, region = ?, pcreativer = ?, pdesc = ?, pstart_date = ?, pauthors = ?,
            price = ?, price_desc = ?, teklifID = ?, pnotes = ?, pstatu = ?, contract_statu = ?,
            contract_updated_at = ?, contract_updated_by = ?'
    );
    $insert->execute([
        $company, $offerId, $serviceType, $serviceNumber, $collectionType,
        $address, $region, $userId, $description, $startDate, $authorIds,
        $price, $priceDescription, null, $serviceNote, $status, $contractStatus,
        $contractUpdatedAt, $contractUpdatedBy,
    ]);
    $newServiceId = (int) $ac->lastInsertId();

    if (isset($_FILES['dosya']) && ($_FILES['dosya']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['dosya']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Teklif dosyası yüklenemedi.');
        }

        $validatedFile = UploadSecurity::validate($_FILES['dosya']);
        $storedFilename = UploadSecurity::randomName($validatedFile);
        $uploadedPath = dirname(__DIR__, 2) . '/files/' . $storedFilename;
        if (!move_uploaded_file($_FILES['dosya']['tmp_name'], $uploadedPath)) {
            throw new RuntimeException('Teklif dosyası kaydedilemedi.');
        }

        $fileInsert = $ac->prepare('INSERT INTO files SET pid = ?, oid = ?, filename = ?, size = ?, creativer = ?');
        $fileInsert->execute([$company, $offerId, $storedFilename, (int) $_FILES['dosya']['size'], $userId]);
        $fileId = (int) $ac->lastInsertId();

        $fileLink = $ac->prepare('UPDATE projects SET teklifID = ? WHERE id = ?');
        $fileLink->execute([$fileId, $newServiceId]);
    }

    $counterUpdate = $ac->prepare('UPDATE define_numbers SET service = ?');
    $counterUpdate->execute([$sequence + 1]);

    audit_log(
        'create',
        'services',
        'Yeni servis oluşturuldu: ' . $serviceNumber,
        'service',
        $newServiceId,
        ['service_number' => $serviceNumber, 'customer_id' => (int) $company]
    );
    $ac->commit();

    $releaseLock = $ac->prepare('SELECT RELEASE_LOCK(?)');
    $releaseLock->execute(['aydinogullari_service_number']);
    $serviceNumberLockAcquired = false;

    serviceApiRespond([
        'success' => true,
        'message' => 'Servis başarıyla oluşturuldu!',
        'service_id' => $newServiceId,
        'service_number' => $serviceNumber,
    ], 201);
} catch (Throwable $exception) {
    if ($ac->inTransaction()) {
        $ac->rollBack();
    }
    if ($uploadedPath && is_file($uploadedPath)) {
        unlink($uploadedPath);
    }
    if ($serviceNumberLockAcquired) {
        $releaseLock = $ac->prepare('SELECT RELEASE_LOCK(?)');
        $releaseLock->execute(['aydinogullari_service_number']);
    }

    $isDuplicate = stripos($exception->getMessage(), 'duplicate service number') !== false;
    serviceApiRespond([
        'success' => false,
        'message' => $isDuplicate
            ? 'Bu servis numarası zaten kullanılıyor. Lütfen tekrar deneyin.'
            : 'İşlem sırasında bir hata oluştu. Lütfen tekrar deneyin.',
    ], $isDuplicate ? 409 : 500);
}
