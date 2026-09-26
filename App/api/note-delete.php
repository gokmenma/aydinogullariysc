<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function noteDeleteResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['login'])) {
    noteDeleteResponse(401, ['success' => false, 'message' => 'Oturumunuz sona ermiş. Lütfen yeniden giriş yapın.']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    noteDeleteResponse(405, ['success' => false, 'message' => 'Geçersiz istek yöntemi.']);
}

if (!function_exists('permtrue') || !permtrue('notedelete')) {
    noteDeleteResponse(403, ['success' => false, 'message' => 'Bu notu silme yetkiniz bulunmuyor.']);
}

$noteId = filter_input(INPUT_POST, 'note_id', FILTER_VALIDATE_INT);
if (!$noteId || $noteId < 1) {
    noteDeleteResponse(422, ['success' => false, 'message' => 'Geçersiz not kaydı.']);
}

$currentUserId = (int)(function_exists('sesset') ? sesset('id') : ($_SESSION['lid'] ?? ($_SESSION['id'] ?? 0)));
$noteQuery = $ac->prepare("SELECT id, title, urgency FROM notes WHERE id = ? AND (visibility = 'general' OR creativer = ?)");
$noteQuery->execute([$noteId, $currentUserId]);
$note = $noteQuery->fetch(PDO::FETCH_ASSOC);

if (!$note) {
    noteDeleteResponse(404, ['success' => false, 'message' => 'Not bulunamadı veya bu notu silme yetkiniz yok.']);
}

$deleteQuery = $ac->prepare('DELETE FROM notes WHERE id = ?');
if (!$deleteQuery->execute([$noteId]) || $deleteQuery->rowCount() !== 1) {
    noteDeleteResponse(500, ['success' => false, 'message' => 'Not silinirken bir hata oluştu.']);
}

if (function_exists('audit_log')) {
    audit_log(
        'delete',
        'notes',
        'Not silindi: ' . ($note['title'] ?? ''),
        'note',
        $noteId,
        ['deleted_title' => $note['title'] ?? '']
    );
}

noteDeleteResponse(200, [
    'success' => true,
    'message' => 'Not başarıyla silindi.',
    'deleted_id' => $noteId,
    'urgency' => trim($note['urgency'] ?? 'Orta'),
]);
