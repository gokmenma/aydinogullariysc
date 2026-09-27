<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Service\MailboxService;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!MailboxService::isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$accountId = (int) ($_GET['account'] ?? 0);
$folder = ($_GET['folder'] ?? 'inbox') === 'sent' ? 'sent' : 'inbox';

if ($accountId < 1) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz hesap ID.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$mailboxService = new MailboxService($ac);
$messages = $mailboxService->messages($accountId, $folder, 100);
$unreadCount = count(array_filter($messages, static fn(array $m): bool => empty($m['is_read_local'])));

$formatDate = static function (?string $date): string {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    if (!$timestamp) return $date;
    return date('Y-m-d', $timestamp) === date('Y-m-d') ? date('H:i', $timestamp) : date('d.m.Y', $timestamp);
};

$initials = static function (string $value): string {
    $value = trim($value);
    if ($value === '') return '?';
    $parts = preg_split('/\s+/u', $value) ?: [];
    $letters = mb_substr((string) ($parts[0] ?? ''), 0, 1, 'UTF-8');
    if (count($parts) > 1) $letters .= mb_substr((string) end($parts), 0, 1, 'UTF-8');
    return mb_strtoupper($letters, 'UTF-8');
};

$items = array_map(static function (array $m) use ($folder, $formatDate, $initials): array {
    $party = $folder === 'sent' ? (string) $m['to_addresses'] : (string) ($m['from_name'] ?: $m['from_address']);
    return [
        'id' => (int) $m['id'],
        'party' => $party,
        'initials' => $initials($party),
        'subject' => (string) ($m['subject'] ?: '(Konu yok)'),
        'from_address' => (string) $m['from_address'],
        'from_name' => (string) $m['from_name'],
        'to_addresses' => (string) $m['to_addresses'],
        'received_at' => (string) $m['received_at'],
        'formatted_date' => $formatDate($m['received_at']),
        'is_read' => (bool) $m['is_read_local'],
        'has_attachments' => (bool) $m['has_attachments'],
        'search' => mb_strtolower($party . ' ' . $m['subject'], 'UTF-8'),
    ];
}, $messages);

echo json_encode([
    'status' => 'success',
    'total' => count($messages),
    'unread_count' => $unreadCount,
    'messages' => $items,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
