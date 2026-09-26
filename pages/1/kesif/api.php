<?php

require_once dirname(__DIR__, 3) . '/bootstrap.php';

use App\Helper\Security;
use App\Helper\Date;
use App\Logging\LoggerFactory;
use App\Model\KesifModel;

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

// Yetki kontrolü login_id
if (!isset($_SESSION['lid'])) {
    $response['message'] = 'Yetkiniz bulunmamaktadır. (Session: ' . implode(', ', array_keys($_SESSION)) . ')';
    echo json_encode($response);
    exit;
}

global $ac;
$logger = LoggerFactory::kesif($ac, $_SESSION['lid'] ?? 0, $_SESSION['username'] ?? 'Guest');

$kesifObj = new KesifModel();
$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['lid'];

// Root directory resolve
$root = dirname(__DIR__, 3);
$log_file = $root . '/logs/kesif_api_debug.log';

/**
 * Keşif eklerini doğrular ve web sunucusunun erişebileceği klasöre kaydeder.
 * Herhangi bir dosya başarısız olursa kısmi yüklemeler temizlenir ve kayıt durdurulur.
 */
function uploadKesifFiles(array $files, string $uploadDir, string $logFile): array
{
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Keşif dosyaları için yükleme klasörü oluşturulamadı.');
    }

    if (!is_writable($uploadDir)) {
        throw new RuntimeException('Keşif dosyaları yükleme klasörüne yazılamıyor. Lütfen sistem yöneticisine bildirin.');
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $uploadedFiles = [];
    $uploadedDiskPaths = [];

    try {
        foreach (($files['name'] ?? []) as $index => $originalName) {
            $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException('"' . basename((string) $originalName) . '" dosyası yüklenemedi (hata kodu: ' . $error . ').');
            }

            $extension = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                throw new RuntimeException('"' . basename((string) $originalName) . '" dosya türü desteklenmiyor.');
            }

            $newName = 'kesif_' . bin2hex(random_bytes(12)) . '.' . $extension;
            $targetPath = $uploadDir . $newName;
            if (!move_uploaded_file((string) $files['tmp_name'][$index], $targetPath)) {
                throw new RuntimeException('"' . basename((string) $originalName) . '" dosyası sunucuya kaydedilemedi.');
            }

            $uploadedDiskPaths[] = $targetPath;
            $uploadedFiles[] = 'uploads/kesif/' . $newName;
        }
    } catch (Throwable $e) {
        foreach ($uploadedDiskPaths as $uploadedDiskPath) {
            if (is_file($uploadedDiskPath)) {
                unlink($uploadedDiskPath);
            }
        }
        file_put_contents($logFile, 'ERROR: Keşif eki yüklenemedi: ' . $e->getMessage() . "\n", FILE_APPEND);
        throw $e;
    }

    return $uploadedFiles;
}

if (!is_dir($root . '/logs/')) {
    mkdir($root . '/logs/', 0777, true);
}

try {
    $debug_info = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'post' => $_POST,
        'files' => $_FILES
    ];
    file_put_contents($log_file, "--- NEW REQUEST ---\n" . print_r($debug_info, true) . "\n", FILE_APPEND);

    switch ($action) {
        case 'get':
            // Keşifi getir (Düzenleme için)
            if (empty($_GET['id'])) {
                throw new Exception('ID belirtilmesi gerekli');
            }

            $kesif = $kesifObj->findActive($_GET['id']);
            if (!$kesif) {
                throw new Exception('Keşif bulunamadı');
            }

            $response['success'] = true;
            $response['data'] = $kesif;
            break;

        case 'create':
            // Yeni keşif ekle
            if (!permtrue('kesifCreate')) {
                throw new Exception('Bu işlem için yetkiniz bulunmamaktadır.');
            }

            $raw_date = $_POST['kesif_tarihi'] ?? '';
            $formatted_date = Date::YmdHis($raw_date);

            $data = [
                'kesif_tarihi' => $formatted_date,
                'gidecek_kisi' => trim((string) ($_POST['gidecek_kisi'] ?? '')),
                'firma' => trim((string) ($_POST['firma'] ?? '')),
                'yapilacak_is' => $_POST['yapilacak_is'] ?? '',
                'konum' => $_POST['konum'] ?? '',
                'durum' => $_POST['durum'] ?? 'bekliyor',
                'formun_bulundugu_kisi' => trim((string) ($_POST['formun_bulundugu_kisi'] ?? '')),
                'kesif_sonu_notu' => $_POST['kesif_sonu_notu'] ?? '',
                'kayit_yapan' => $user_id
            ];

            // Görsel yükleme
            if (isset($_FILES['gorseller']) && !empty($_FILES['gorseller']['name'][0])) {
                $upload_dir = $root . '/uploads/kesif/';
                $uploaded_files = uploadKesifFiles($_FILES['gorseller'], $upload_dir, $log_file);
                $data['gorseller'] = json_encode($uploaded_files, JSON_UNESCAPED_SLASHES);
            }

            if (empty($data['firma']) || empty($data['yapilacak_is'])) {
                throw new Exception('Gerekli alanları doldurunuz.');
            }

            $kesif_id = $kesifObj->save($data);
            $logger->info('Keşif oluşturuldu', ['id' => $kesif_id, 'firma' => $data['firma']]);

            $response['success'] = true;
            $response['message'] = 'Keşif başarıyla eklendi.';
            $response['data'] = ['id' => $kesif_id];
            break;

        case 'update':
            // Keşifi güncelle
            if (!permtrue('kesifEdit')) {
                throw new Exception('Bu işlem için yetkiniz bulunmamaktadır.');
            }

            $id = $_POST['id'] ?? 0;
            if (empty($id)) {
                throw new Exception('ID belirtilmesi gerekli');
            }

            $kesif = $kesifObj->findActive($id);
            if (!$kesif) {
                throw new Exception('Keşif bulunamadı');
            }

            $raw_date = $_POST['kesif_tarihi'] ?? '';
            $formatted_date = Date::YmdHis($raw_date);

            $data = [
                'id' => $id,
                'kesif_tarihi' => $formatted_date,
                'gidecek_kisi' => trim((string) ($_POST['gidecek_kisi'] ?? '')),
                'firma' => trim((string) ($_POST['firma'] ?? '')),
                'yapilacak_is' => $_POST['yapilacak_is'] ?? '',
                'konum' => $_POST['konum'] ?? '',
                'durum' => $_POST['durum'] ?? 'bekliyor',
                'formun_bulundugu_kisi' => trim((string) ($_POST['formun_bulundugu_kisi'] ?? '')),
                'kesif_sonu_notu' => $_POST['kesif_sonu_notu'] ?? '',
                'guncelleyen_kullanici' => $user_id
            ];

            // Görsel yükleme
            if (isset($_FILES['gorseller']) && !empty($_FILES['gorseller']['name'][0])) {
                $upload_dir = $root . '/uploads/kesif/';
                $uploaded_files = uploadKesifFiles($_FILES['gorseller'], $upload_dir, $log_file);

                $existing_gorseller = [];
                if (!empty($kesif->gorseller)) {
                    $existing_gorseller = json_decode($kesif->gorseller, true) ?: [];
                }
                $all_gorseller = array_merge($existing_gorseller, $uploaded_files);
                $data['gorseller'] = json_encode($all_gorseller, JSON_UNESCAPED_SLASHES);
            }

            $kesifObj->update($data);
            $logger->info('Keşif güncellendi', ['id' => $id, 'firma' => $data['firma']]);

            $response['success'] = true;
            $response['message'] = 'Keşif başarıyla güncellendi.';
            break;

        case 'delete':
            if (!permtrue('kesifDelete')) {
                throw new Exception('Bu işlem için yetkiniz bulunmamaktadır.');
            }

            $id = $_POST['id'] ?? 0;
            if (empty($id)) {
                throw new Exception('ID belirtilmesi gerekli');
            }

            $kesifObj->softDelete($id, $user_id);
            $logger->info('Keşif silindi', ['id' => $id]);

            $response['success'] = true;
            $response['message'] = 'Keşif başarıyla silindi.';
            break;

        case 'delete_image':
            // Tek bir görseli sil
            if (!permtrue('kesifEdit')) {
                throw new Exception('Bu işlem için yetkiniz bulunmamaktadır.');
            }

            $id = $_POST['id'] ?? 0;
            $image_path = $_POST['image_path'] ?? '';

            if (empty($id) || empty($image_path)) {
                throw new Exception('Eksik parametreler.');
            }

            $kesif = $kesifObj->findActive($id);
            if (!$kesif) {
                throw new Exception('Keşif bulunamadı');
            }

            // Veritabanından görseli kaldır
            $gorseller = [];
            if (!empty($kesif->gorseller)) {
                $gorseller = json_decode($kesif->gorseller, true) ?: [];
            }

            $new_gorseller = array_values(array_filter($gorseller, function ($g) use ($image_path) {
                return $g !== $image_path;
            }));

            // Dosyayı sil
            $file_path = $root . '/' . $image_path;
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Veritabanını güncelle
            $update_data = [
                'id' => $id,
                'gorseller' => !empty($new_gorseller) ? json_encode($new_gorseller) : null
            ];
            $kesifObj->update($update_data);

            $logger->info('Keşif görseli silindi', ['id' => $id, 'image' => $image_path]);

            $response['success'] = true;
            $response['message'] = 'Görsel başarıyla silindi.';
            break;

        default:
            throw new Exception('Geçersiz işlem');
    }

} catch (Exception $e) {
    file_put_contents($log_file, "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
