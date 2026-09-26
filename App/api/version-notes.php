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

// Kategori yardımcı fonksiyonu
function apiGetCategoryInfo($cat, $title = '', $desc = '') {
    $cat = strtolower(trim((string)$cat));
    if (empty($cat) || $cat === 'feature') {
        $checkText = mb_strtolower($title . ' ' . $desc, 'UTF-8');
        if (strpos($checkText, 'hata') !== false || strpos($checkText, 'düzeltme') !== false || strpos($checkText, 'fix') !== false) {
            $cat = 'bugfix';
        } elseif (strpos($checkText, 'iyileştir') !== false || strpos($checkText, 'düzenleme') !== false || strpos($checkText, 'güncelle') !== false) {
            $cat = 'improvement';
        } elseif (strpos($checkText, 'güvenlik') !== false || strpos($checkText, 'yetki') !== false || strpos($checkText, 'şifre') !== false) {
            $cat = 'security';
        } else {
            $cat = 'feature';
        }
    }

    switch ($cat) {
        case 'bugfix':
            return [
                'name' => 'Hata Düzeltme',
                'class' => 'badge-vn-bugfix',
                'icon' => 'fa-wrench',
                'color' => '#f59e0b'
            ];
        case 'improvement':
            return [
                'name' => 'İyileştirme',
                'class' => 'badge-vn-improvement',
                'icon' => 'fa-magic',
                'color' => '#3b82f6'
            ];
        case 'security':
            return [
                'name' => 'Güvenlik',
                'class' => 'badge-vn-security',
                'icon' => 'fa-shield',
                'color' => '#ef4444'
            ];
        case 'other':
            return [
                'name' => 'Genel',
                'class' => 'badge-vn-other',
                'icon' => 'fa-info-circle',
                'color' => '#64748b'
            ];
        case 'feature':
        default:
            return [
                'name' => 'Yeni Özellik',
                'class' => 'badge-vn-feature',
                'icon' => 'fa-rocket',
                'color' => '#10b981'
            ];
    }
}

function apiFormatTurkishDate($dateStr) {
    if (empty($dateStr)) return '';
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8');
    
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];
    
    $day = date('j', $timestamp);
    $monthNum = (int)date('n', $timestamp);
    $year = date('Y', $timestamp);
    $time = date('H:i', $timestamp);
    
    $monthName = $months[$monthNum] ?? date('M', $timestamp);
    
    if (strpos($dateStr, ':') !== false) {
        return "{$day} {$monthName} {$year}, {$time}";
    }
    return "{$day} {$monthName} {$year}";
}

function apiRenderDescription($text) {
    if (empty($text)) return '';
    $text = str_replace(['\r\n', '\r'], "\n", $text);
    $lines = preg_split('/<br\s*\/?>|\n/i', $text);
    
    $output = [];
    $inList = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;
        
        if (preg_match('/^[-*•]\s+(.*)$/u', $trimmed, $matches)) {
            if (!$inList) {
                $output[] = '<ul class="vn-desc-list">';
                $inList = true;
            }
            $output[] = '<li><i class="fa fa-angle-right vn-bullet-icon"></i> ' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</li>';
        } else {
            if ($inList) {
                $output[] = '</ul>';
                $inList = false;
            }
            $output[] = '<p class="vn-desc-paragraph">' . htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }
    
    if ($inList) {
        $output[] = '</ul>';
    }
    
    return implode("\n", $output);
}

if ($action === 'list') {
    $page = max(1, (int)($_GET['page'] ?? $_POST['page'] ?? 1));
    $perPage = max(5, min(100, (int)($_GET['per_page'] ?? $_POST['per_page'] ?? 15)));
    $category = trim($_GET['category'] ?? $_POST['category'] ?? 'all');
    $search = trim($_GET['search'] ?? $_POST['search'] ?? '');
    $startDate = trim($_GET['start_date'] ?? $_POST['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? $_POST['end_date'] ?? '');

    $offset = ($page - 1) * $perPage;

    $total = $versionModel->countNotes($category, $search, $startDate, $endDate);
    $notes = $versionModel->getNotes($perPage, $offset, $category, $search, $startDate, $endDate);
    $totalPages = ceil($total / $perPage);

    $formattedNotes = [];
    foreach ($notes as $note) {
        $catInfo = apiGetCategoryInfo($note->category ?? '', $note->title, $note->description);
        $formattedNotes[] = [
            'id' => (int)$note->id,
            'title' => htmlspecialchars($note->title, ENT_QUOTES, 'UTF-8'),
            'version_tag' => !empty($note->version_tag) ? htmlspecialchars($note->version_tag, ENT_QUOTES, 'UTF-8') : null,
            'category' => $note->category ?? 'feature',
            'category_info' => $catInfo,
            'author' => !empty($note->author) ? htmlspecialchars($note->author, ENT_QUOTES, 'UTF-8') : 'Admin',
            'created_at' => $note->created_at,
            'formatted_date' => apiFormatTurkishDate($note->created_at),
            'rendered_description' => apiRenderDescription($note->description),
            'raw_description' => $note->description
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $formattedNotes,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages
        ]
    ]);
    exit;
}

if ($action === 'save') {
    // Sadece yetkili kullanıcılar ekleyebilir/düzenleyebilir
    $userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
    $userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? ($_SESSION['perm'] ?? 0)));
    $canManage = in_array($userId, [1, 12], true) || in_array($userPerm, [1, 13], true) || (function_exists('permtrue') && (permtrue("panelsettings") || permtrue("authdefine")));

    if (!$canManage) {
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
    $userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
    $userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? ($_SESSION['perm'] ?? 0)));
    $canManage = in_array($userId, [1, 12], true) || in_array($userPerm, [1, 13], true) || (function_exists('permtrue') && (permtrue("panelsettings") || permtrue("authdefine")));

    if (!$canManage) {
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
