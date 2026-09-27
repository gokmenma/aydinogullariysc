<?php

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ((int) sesset('permission') !== 13) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $ac->prepare("SELECT COUNT(*) AS unread_count, COALESCE(MAX(id), 0) AS last_id FROM mailbox_messages WHERE folder = 'inbox' AND is_read_local = 0");
$stmt->execute();
$status = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['unread_count' => 0, 'last_id' => 0];
echo json_encode(['status' => 'success', 'unread_count' => (int) $status['unread_count'], 'last_id' => (int) $status['last_id']], JSON_UNESCAPED_UNICODE);
