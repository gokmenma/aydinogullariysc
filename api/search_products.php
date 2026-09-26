<?php
// Buffer all outputs to guarantee clean JSON
ob_start();

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

// Yetki & Oturum Kontrolü
$userId = (int)($_SESSION['lid'] ?? 0);
if ($userId <= 0 || !isset($_SESSION['login'])) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Oturum süresi dolmuş veya yetkisiz erişim.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? 'search';

// Tek ürün getirme
if ($action === 'get_by_id') {
    $id = (int)($_GET['id'] ?? 0);
    $sql = "SELECT 
                p.ID as id,
                p.Adi as title,
                COALESCE(p.StokKodu, '') as stock_code,
                COALESCE(p.AlisFiyati, '') as buy_price,
                COALESCE(p.AlisParaBirimi, 'TRY') as buy_cur,
                COALESCE(p.SatisFiyati, '') as sale_price,
                COALESCE(p.SatisParaBirimi, 'TRY') as sale_cur,
                COALESCE(u.title, p.Birimi, 'Adet') as unit
            FROM products p
            LEFT JOIN units u ON (p.Birimi = u.id OR p.Birimi = u.title)
            WHERE p.ID = :id LIMIT 1";
    
    $stmt = $ac->prepare($sql);
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');

    if ($product) {
        echo json_encode([
            'status' => 'success',
            'data'   => $product
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Ürün bulunamadı.'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Tüm liste veya Arama
$term = trim((string)($_GET['q'] ?? ''));
$limit = isset($_GET['limit']) ? min(max((int)$_GET['limit'], 1), 1000) : 30;

$sql = "SELECT 
            p.ID as id,
            p.Adi as title,
            COALESCE(p.StokKodu, '') as stock_code,
            COALESCE(p.AlisFiyati, '') as buy_price,
            COALESCE(p.AlisParaBirimi, 'TRY') as buy_cur,
            COALESCE(p.SatisFiyati, '') as sale_price,
            COALESCE(p.SatisParaBirimi, 'TRY') as sale_cur,
            COALESCE(u.title, p.Birimi, 'Adet') as unit
        FROM products p
        LEFT JOIN units u ON (p.Birimi = u.id OR p.Birimi = u.title)
        WHERE (p.Durum = 1 OR p.Durum IS NULL OR p.Durum = '')";

$params = [];
if ($term !== '') {
    // Türkçe karakter varyasyonları (ı-i, ş-s, ğ-g, ü-u, ö-o, ç-c)
    $termVariants = [$term];
    $altTr = str_replace(
        ['ı', 'I', 'İ', 'i', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç'],
        ['i', 'i', 'i', 'ı', 'g', 'G', 'u', 'U', 's', 'S', 'o', 'O', 'c', 'C'],
        $term
    );
    if ($altTr !== $term) {
        $termVariants[] = $altTr;
    }

    $whereParts = [];
    foreach ($termVariants as $idx => $t) {
        $pA = ':qa_' . $idx;
        $pS = ':qs_' . $idx;
        $pB = ':qb_' . $idx;
        $whereParts[] = "(p.Adi LIKE {$pA} OR p.StokKodu LIKE {$pS} OR p.Barkod LIKE {$pB})";
        $like = '%' . $t . '%';
        $params[$pA] = $like;
        $params[$pS] = $like;
        $params[$pB] = $like;
    }
    $sql .= " AND (" . implode(" OR ", $whereParts) . ")";
}

$sql .= " ORDER BY p.Adi ASC LIMIT " . (int)$limit;

$stmt = $ac->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'  => 'success',
    'results' => $rows,
    'count'   => count($rows)
], JSON_UNESCAPED_UNICODE);
exit;
