<?php
require_once dirname(__DIR__, 2) . "/bootstrap.php";

use App\Model\VersionNoteModel;
use App\Helper\Security;

header('Content-Type: application/json; charset=utf-8');

// Oturum kontrolü
if (!isset($_SESSION["login"])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim. Lütfen giriş yapın.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$versionModel = new VersionNoteModel();

if ($action === 'save') {
    // Sadece yönetici ekleyebilir/düzenleyebilir (perm == 1)
    if (sesset("perm") != 1) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.']);
        exit;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $version_tag = trim($_POST['version_tag'] ?? '');
    $category = trim($_POST['category'] ?? 'feature');
    $description = trim($_POST['description'] ?? '');
    $created_at = trim($_POST['created_at'] ?? '');

    if (empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'Başlık alanı zorunludur.']);
        exit;
    }

    if (empty($description)) {
        echo json_encode(['status' => 'error', 'message' => 'Açıklama alanı zorunludur.']);
        exit;
    }

    $validCategories = ['feature', 'improvement', 'bugfix', 'security', 'other'];
    if (!in_array($category, $validCategories)) {
        $category = 'feature';
    }

    $data = [
        'title' => $title,
        'version_tag' => !empty($version_tag) ? $version_tag : null,
        'category' => $category,
        'description' => $description,
        'author' => sesset("username") ?: 'Admin',
    ];

    if (!empty($created_at)) {
        $data['created_at'] = $created_at;
    } else if ($id <= 0) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }

    if ($id > 0) {
        $data['id'] = $id;
        $versionModel->save($data);
        audit_log("update", "version_notes", "Sürüm notu güncellendi: " . $title, "version_notes", $id, $data);
        echo json_encode(['status' => 'success', 'message' => 'Sürüm notu başarıyla güncellendi.']);
    } else {
        $newId = $versionModel->save($data);
        audit_log("create", "version_notes", "Yeni sürüm notu eklendi: " . $title, "version_notes", $newId, $data);
        echo json_encode(['status' => 'success', 'message' => 'Sürüm notu başarıyla eklendi.', 'id' => $newId]);
    }
    exit;
}

if ($action === 'delete') {
    if (sesset("perm") != 1) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz kayıt ID.']);
        exit;
    }

    $existing = $versionModel->find($id);
    if (!$existing) {
        echo json_encode(['status' => 'error', 'message' => 'Kayıt bulunamadı.']);
        exit;
    }

    $versionModel->delete($id);
    audit_log("delete", "version_notes", "Sürüm notu silindi ID: " . $id, "version_notes", $id);
    echo json_encode(['status' => 'success', 'message' => 'Sürüm notu silindi.']);
    exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $item = $versionModel->find($id);
    if (!$item) {
        echo json_encode(['status' => 'error', 'message' => 'Kayıt bulunamadı.']);
        exit;
    }
    echo json_encode(['status' => 'success', 'data' => $item]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
