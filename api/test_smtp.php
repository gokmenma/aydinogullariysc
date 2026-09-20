<?php
// Buffer all outputs to guarantee clean JSON
ob_start();

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use App\Helper\Security;

// 1. Yetki Kontrolü
$canManage = permtrue("panelsettings") || (isset($_SESSION['perm']) && $_SESSION['perm'] == 1) || (isset($_SESSION['lid']) && in_array($_SESSION['lid'], [1, 12]));

if (!$canManage) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Bu işlem için yetkiniz bulunmamaktadır.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Geçersiz istek türü.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Parametreleri al (Formdan gelen anlık değerler veya sistem ayarları fallback)
$mailHost     = trim($_POST['mail_host'] ?? '') ?: trim((string)set('mail_host'));
$mailPort     = (int)(trim($_POST['mail_port'] ?? '') ?: trim((string)set('mail_port')));
$mailUsername = trim($_POST['mail_username'] ?? '') ?: trim((string)set('mail_username'));
$mailPassword = $_POST['mail_password'] ?? (string)set('mail_password');
$testTo       = trim($_POST['test_email'] ?? '') ?: (trim($_POST['mail_admin'] ?? '') ?: trim((string)set('admin_mail')));
$companyName  = trim((string)set('company_name')) ?: 'Aydınoğulları YSC';

// Doğrulamalar
if (empty($mailHost)) {
    jsonResponse('error', 'Lütfen SMTP Mail Sunucusu (Host) adresini giriniz.');
}

if ($mailPort <= 0) {
    jsonResponse('error', 'Lütfen geçerli bir SMTP Port numarası giriniz (örn: 587 veya 465).');
}

if (empty($mailUsername)) {
    jsonResponse('error', 'Lütfen gönderici e-posta kullanıcı adını giriniz.');
}

if (empty($mailPassword)) {
    jsonResponse('error', 'Lütfen gönderici e-posta şifresini giriniz.');
}

if (empty($testTo) || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
    jsonResponse('error', 'Lütfen geçerli bir test alıcı e-posta adresi giriniz.');
}

// PHPMailer ile gönderim testi
$debugOutput = '';
$mail = new PHPMailer(true);

try {
    // Debug yakalama
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
        $debugOutput .= "[" . date('H:i:s') . "] " . trim($str) . "\n";
    };

    // Sunucu Ayarları
    $mail->isSMTP();
    $mail->Host       = $mailHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $mailUsername;
    $mail->Password   = $mailPassword;
    $mail->Port       = $mailPort;
    $mail->Timeout    = 15; // 15 saniye zaman aşımı

    // Şifreleme Protokolü Seçimi (Porta ve sunucuya göre akıllı belirleme)
    if ($mailPort == 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($mailPort == 587) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = true;
    }

    // Karakter seti ve dil
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    // Gönderici ve Alıcı
    $mail->setFrom($mailUsername, $companyName);
    $mail->addAddress($testTo);
    $mail->addReplyTo($mailUsername, $companyName);

    // E-Posta İçeriği
    $currentDateTime = date('d.m.Y H:i:s');
    $mail->isHTML(true);
    $mail->Subject = "SMTP Test E-Postası - {$companyName} ({$currentDateTime})";

    $htmlBody = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
        <div style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 25px; text-align: center;">
            <h2 style="margin: 0; font-size: 20px;">' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">SMTP E-Posta Yapılandırma Testi</p>
        </div>
        <div style="padding: 25px; background: #ffffff; color: #334155; font-size: 14px; line-height: 1.6;">
            <div style="background: #f0fdf4; border-left: 4px solid #16a34a; padding: 14px 18px; border-radius: 6px; margin-bottom: 20px;">
                <strong style="color: #166534; font-size: 15px;">✓ Tebrikler! SMTP Bağlantısı Başarılı</strong>
                <p style="margin: 4px 0 0 0; color: #15803d; font-size: 13px;">Panel e-posta ayarlarınız doğru yapılandırılmıştır. Sistem teklif, servis ve bildirim maillerini başarıyla iletebilir.</p>
            </div>
            <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px;">
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px 0; color: #64748b; font-weight: bold; width: 140px;">Mail Sunucusu:</td>
                    <td style="padding: 8px 0; color: #1e293b;">' . htmlspecialchars($mailHost, ENT_QUOTES, 'UTF-8') . '</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px 0; color: #64748b; font-weight: bold;">SMTP Port:</td>
                    <td style="padding: 8px 0; color: #1e293b;">' . $mailPort . ' (' . ($mail->SMTPSecure ?: 'Standart') . ')</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px 0; color: #64748b; font-weight: bold;">Gönderici Hesabı:</td>
                    <td style="padding: 8px 0; color: #1e293b;">' . htmlspecialchars($mailUsername, ENT_QUOTES, 'UTF-8') . '</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px 0; color: #64748b; font-weight: bold;">Test Alıcısı:</td>
                    <td style="padding: 8px 0; color: #1e293b;">' . htmlspecialchars($testTo, ENT_QUOTES, 'UTF-8') . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; font-weight: bold;">Gönderim Zamanı:</td>
                    <td style="padding: 8px 0; color: #1e293b;">' . $currentDateTime . '</td>
                </tr>
            </table>
        </div>
        <div style="background: #f8fafc; padding: 12px 25px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0;">
            Bu mesaj Aydınoğulları YSC Paneli üzerinden otomatik test amacıyla gönderilmiştir.
        </div>
    </div>
    ';

    $mail->Body = $htmlBody;
    $mail->AltBody = "Tebrikler! SMTP Bağlantısı Başarılı.\nSunucu: {$mailHost}\nPort: {$mailPort}\nGönderici: {$mailUsername}\nTarih: {$currentDateTime}";

    $sent = $mail->send();

    if ($sent) {
        // Audit log
        if (function_exists('audit_log')) {
            audit_log('send', 'settings', "SMTP test e-postası başarıyla gönderildi: {$testTo}", 'settings', 1, [
                'to'   => $testTo,
                'host' => $mailHost,
                'port' => $mailPort
            ]);
        }

        // Mail log tablosuna ekleme (varsa)
        try {
            global $ac;
            if ($ac instanceof PDO) {
                $chk = $ac->query("SHOW TABLES LIKE 'mail_logs'")->fetch();
                if ($chk) {
                    $ins = $ac->prepare("INSERT INTO mail_logs (tomail, from_mail, mail_body, statu, sender, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                    $ins->execute([$testTo, $mailUsername, 'SMTP Yapılandırma Test E-Postası', 1, $_SESSION['lid'] ?? 1, date('Y-m-d H:i:s')]);
                }
            }
        } catch (\Throwable $t) {
            // Mail log insert hatası ana işlemi engellemesin
        }

        jsonResponse('success', "Test e-postası <strong>{$testTo}</strong> adresine başarıyla iletildi!", [
            'to'    => $testTo,
            'host'  => $mailHost,
            'port'  => $mailPort,
            'debug' => $debugOutput
        ]);
    } else {
        jsonResponse('error', 'E-Posta gönderilemedi: ' . $mail->ErrorInfo, ['debug' => $debugOutput]);
    }

} catch (Exception $e) {
    $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
    
    // Anlaşılır Türkçe hata yorumlaması
    $friendlyError = $errorMsg;
    if (stripos($errorMsg, 'authenticate') !== false || stripos($errorMsg, 'Invalid login') !== false) {
        $friendlyError = 'Kimlik doğrulama başarısız! E-Posta kullanıcı adı veya şifreniz hatalı.';
    } elseif (stripos($errorMsg, 'connect') !== false || stripos($errorMsg, 'Connection refused') !== false || stripos($errorMsg, 'timed out') !== false) {
        $friendlyError = 'Sunucuya bağlanılamadı! Mail sunucu adresi (Host) veya Port numarası hatalı veya güvenlik duvarı tarafından engelleniyor.';
    } elseif (stripos($errorMsg, 'starttls') !== false || stripos($errorMsg, 'certificate') !== false) {
        $friendlyError = 'SSL/TLS güvenlik protokolü hatası! Port (465/587) ve SSL/TLS ayarlarını kontrol ediniz.';
    }

    if (function_exists('audit_log')) {
        audit_log('error', 'settings', "SMTP test e-postası gönderilemedi: {$errorMsg}", 'settings', 1, [
            'to'    => $testTo,
            'error' => $errorMsg
        ]);
    }

    jsonResponse('error', "E-Posta gönderilemedi: {$friendlyError}", [
        'detail' => $errorMsg,
        'debug'  => $debugOutput
    ]);
} catch (\Throwable $t) {
    jsonResponse('error', 'Beklenmeyen sistem hatası: ' . $t->getMessage(), [
        'debug' => $debugOutput
    ]);
}

/**
 * Temiz JSON yanıt yardımcısı
 */
function jsonResponse($status, $message, $extra = []) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'status'  => $status,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
