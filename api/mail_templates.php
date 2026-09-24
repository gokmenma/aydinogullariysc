<?php
ob_start();

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Helper\Security;

function mailTemplateResponse($statusCode, array $payload)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$userId = (int) sesset('id');
$canSend = permtrue('mailandsmssend')
    || (isset($_SESSION['perm']) && (int) $_SESSION['perm'] === 1)
    || in_array($userId, [1, 12], true);

if ($userId < 1 || !$canSend) {
    mailTemplateResponse(403, ['status' => 'error', 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.']);
}

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'list');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receivedToken = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    if ($receivedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $receivedToken)) {
        mailTemplateResponse(419, ['status' => 'error', 'message' => 'Oturum doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyiniz.']);
    }
}

try {
    if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $ac->prepare(
            'SELECT id, name, sender_email, subject, updated_at
             FROM mail_templates
             WHERE created_by = ?
             ORDER BY updated_at DESC, id DESC'
        );
        $stmt->execute([$userId]);
        mailTemplateResponse(200, ['status' => 'success', 'templates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $templateId = (int) ($_GET['id'] ?? 0);
        $stmt = $ac->prepare(
            'SELECT id, name, sender_email, subject, recipients_json, body_html, updated_at
             FROM mail_templates
             WHERE id = ? AND created_by = ?
             LIMIT 1'
        );
        $stmt->execute([$templateId, $userId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$template) {
            mailTemplateResponse(404, ['status' => 'error', 'message' => 'Şablon bulunamadı.']);
        }
        $template['recipients'] = json_decode($template['recipients_json'], true) ?: [];
        unset($template['recipients_json']);
        mailTemplateResponse(200, ['status' => 'success', 'template' => $template]);
    }

    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $senderEmail = trim((string) ($_POST['sender_email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $bodyHtml = trim((string) ($_POST['body_html'] ?? ''));
        $rawRecipients = json_decode((string) ($_POST['recipients'] ?? '[]'), true);

        if ($name === '' || mb_strlen($name) > 150) {
            mailTemplateResponse(422, ['status' => 'error', 'message' => 'Şablon adı zorunludur ve en fazla 150 karakter olabilir.']);
        }
        if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL) || $subject === '' || mb_strlen($subject) > 255 || $bodyHtml === '') {
            mailTemplateResponse(422, ['status' => 'error', 'message' => 'Gönderen, konu ve mail içeriği eksiksiz olmalıdır.']);
        }
        if (!is_array($rawRecipients) || count($rawRecipients) > 1000) {
            mailTemplateResponse(422, ['status' => 'error', 'message' => 'Alıcı listesi geçersiz veya izin verilen sınırın üzerindedir.']);
        }

        $accountStmt = $ac->prepare(
            'SELECT COUNT(*) FROM mail_accounts
             WHERE mail_address = ? AND (account_type = 1 OR mail_user = 1 OR mail_user = ?)'
        );
        $accountStmt->execute([$senderEmail, $userId]);
        if ((int) $accountStmt->fetchColumn() < 1) {
            mailTemplateResponse(422, ['status' => 'error', 'message' => 'Seçilen gönderici hesabını kullanma yetkiniz bulunmamaktadır.']);
        }

        $recipients = [];
        $seenEmails = [];
        foreach ($rawRecipients as $recipient) {
            if (!is_array($recipient)) {
                continue;
            }
            $email = trim((string) ($recipient['email'] ?? ''));
            $emailKey = mb_strtolower($email, 'UTF-8');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seenEmails[$emailKey])) {
                continue;
            }
            $seenEmails[$emailKey] = true;
            $recipients[] = [
                'email' => mb_substr($email, 0, 255),
                'company' => mb_substr(trim((string) ($recipient['company'] ?? '')), 0, 255),
                'yetkili' => mb_substr(trim((string) ($recipient['yetkili'] ?? '')), 0, 255),
                'city' => mb_substr(trim((string) ($recipient['city'] ?? '')), 0, 100),
            ];
        }
        if (!$recipients) {
            mailTemplateResponse(422, ['status' => 'error', 'message' => 'Şablonda en az bir geçerli alıcı bulunmalıdır.']);
        }

        $recipientsJson = json_encode($recipients, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt = $ac->prepare(
            'INSERT INTO mail_templates (name, sender_email, subject, recipients_json, body_html, created_by)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                sender_email = VALUES(sender_email),
                subject = VALUES(subject),
                recipients_json = VALUES(recipients_json),
                body_html = VALUES(body_html),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$name, $senderEmail, $subject, $recipientsJson, $bodyHtml, $userId]);

        if (function_exists('audit_log')) {
            audit_log('save', 'mail-template', 'Mail şablonu kaydedildi: ' . $name, 'mail_templates', (string) $ac->lastInsertId());
        }
        mailTemplateResponse(200, ['status' => 'success', 'message' => 'Şablon tüm mail alanlarıyla kaydedildi.']);
    }

    mailTemplateResponse(405, ['status' => 'error', 'message' => 'Geçersiz şablon işlemi.']);
} catch (PDOException $exception) {
    error_log('Mail template database error: ' . $exception->getMessage());
    mailTemplateResponse(500, ['status' => 'error', 'message' => 'Şablon işlemi sırasında veritabanı hatası oluştu.']);
}
