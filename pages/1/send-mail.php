<?php
use App\Model\CustomerModel;
use App\Helper\Security;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

permcontrol("mailandsmssend");
$mailTemplateCsrfToken = Security::csrf();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

$customerModel = new CustomerModel();

// Müşteriler sayfasından doğrudan mail gönderme parametresi
$customer_id = isset($_GET['customer']) ? (int)decrypt($_GET['customer']) : 0;
$preselectedCustomer = null;
if ($customer_id > 0) {
    $preselectedCustomer = $customerModel->find($customer_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dosya_adi = "";
    $hedef = "";

    // Dosya yükleme işlemi
    if (isset($_FILES["dosya"]) && !empty($_FILES["dosya"]["name"])) {
        if ($_FILES["dosya"]["error"] === UPLOAD_ERR_OK) {
            $dosya_adi = $_FILES["dosya"]["name"];
            $dosya_yolu = $_FILES["dosya"]["tmp_name"];

            $dizin = "files/";
            if (!is_dir($dizin)) {
                mkdir($dizin, 0755, true);
            }
            $rast1 = rand(1000, 9999);
            $hedef = $dizin . $rast1 . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", basename($dosya_adi));

            if (!move_uploaded_file($dosya_yolu, $hedef)) {
                $hedef = "";
                $dosya_adi = "";
            }
        }
    }

    try {
        $rawCustomers = $_POST["customers"] ?? [];
        $validEmails = [];
        $mailto = "";

        // Gelen alıcı adreslerini ayrıştır ve valide et
        if (is_array($rawCustomers)) {
            foreach ($rawCustomers as $rawCust) {
                $parts = preg_split('/[,;\s]+/', trim($rawCust), -1, PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $part) {
                    $cleaned = filter_var(trim($part), FILTER_VALIDATE_EMAIL);
                    if ($cleaned && !in_array($cleaned, $validEmails, true)) {
                        $validEmails[] = $cleaned;
                    }
                }
            }
        }

        if (empty($validEmails)) {
            header("Location: index.php?p=send-mail&st=nocustom");
            exit;
        }

        $mailkonu   = trim((string)($_POST["mailkonu"] ?? ''));
        $mailicerik = trim((string)($_POST["mailicerik"] ?? ''));
        $mail_from  = trim((string)($_POST["mail_address"] ?? ''));

        if (empty($mailkonu)) {
            header("Location: index.php?p=send-mail&st=empty_subject");
            exit;
        }

        if (empty($mailicerik)) {
            header("Location: index.php?p=send-mail&st=empty_body");
            exit;
        }

        $mail = get_configured_mailer($mail_from);

        foreach ($validEmails as $recipientEmail) {
            $mail->addAddress($recipientEmail);
            $mailto .= $recipientEmail . "|";
        }

        if (!empty($hedef) && file_exists($hedef)) {
            $mail->addAttachment($hedef);
        }

        $mail->Subject = $mailkonu;
        $mail->Body    = $mailicerik;

        if ($mail->send()) {
            // Veritabanına log kaydet (statu = 1: Başarılı)
            try {
                $sql = $ac->prepare("INSERT INTO mail_logs (tomail, from_mail, mail_file, mail_body, statu, sender) VALUES (?, ?, ?, ?, 1, ?)");
                $sql->execute([$mailto, $mail_from, $dosya_adi, $mailicerik, (int)sesset("id")]);
            } catch (Exception $dbEx) {
                error_log("Mail log db error: " . $dbEx->getMessage());
            }

            if (function_exists('audit_log')) {
                audit_log(
                    'send',
                    'mail',
                    "E-posta başarıyla iletildi. (" . count($validEmails) . " alıcı, Konu: {$mailkonu})",
                    'customers',
                    (string)count($validEmails),
                    [
                        'recipients_count' => count($validEmails),
                        'subject' => $mailkonu,
                        'from' => $mail_from
                    ]
                );
            }

            header("Location: index.php?p=send-mail&send=true&count=" . count($validEmails));
            exit;
        } else {
            $_SESSION['mail_last_error'] = $mail->ErrorInfo ?? 'Bilinmeyen SMTP iletim hatası.';
            header("Location: index.php?p=send-mail&st=unsuccessful");
            exit;
        }
    } catch (Exception $e) {
        error_log("Mail Send Error: " . $e->getMessage());
        $_SESSION['mail_last_error'] = $e->getMessage();
        header("Location: index.php?p=send-mail&st=unsuccessful");
        exit;
    } catch (PDOException $e) {
        error_log("Mail DB Error: " . $e->getMessage());
        $_SESSION['mail_last_error'] = "Veritabanı kayıt hatası oluştu.";
        header("Location: index.php?p=send-mail&st=unsuccessful");
        exit;
    }
}

// Durum Mesajları Hazırlığı
$feedback = null;
$statusParam = $_GET["st"] ?? ($_GET["send"] ?? '');
$recipientCount = isset($_GET['count']) ? (int)$_GET['count'] : 0;

if ($statusParam === 'true' || $statusParam === 'success') {
    $countText = $recipientCount > 0 ? " ($recipientCount alıcıya iletildi)" : "";
    $feedback = [
        'type' => 'success',
        'icon' => 'fa-check-circle',
        'title' => 'E-Posta Başarıyla Gönderildi!',
        'message' => "E-postanız seçilen alıcılara başarıyla iletildi{$countText} ve işlem geçmişine kaydedildi."
    ];
    showAlert("success", "E-posta başarıyla gönderildi!");
} elseif ($statusParam === 'nocustom') {
    $feedback = [
        'type' => 'warning',
        'icon' => 'fa-exclamation-triangle',
        'title' => 'Alıcı Seçimi Eksik!',
        'message' => 'Lütfen e-posta göndermek için listeden en az 1 firma seçiniz veya geçerli bir alıcı e-posta adresi yazıp ekleyiniz.'
    ];
    showAlert("alert", "Lütfen en az 1 alıcı e-posta adresi seçiniz.");
} elseif ($statusParam === 'empty_subject') {
    $feedback = [
        'type' => 'warning',
        'icon' => 'fa-exclamation-triangle',
        'title' => 'Konu Başlığı Eksik!',
        'message' => 'Lütfen e-postanız için bir konu başlığı belirleyiniz.'
    ];
    showAlert("alert", "Konu başlığı boş bırakılamaz.");
} elseif ($statusParam === 'empty_body') {
    $feedback = [
        'type' => 'warning',
        'icon' => 'fa-exclamation-triangle',
        'title' => 'Mesaj İçeriği Boş!',
        'message' => 'Lütfen iletmek istediğiniz e-posta metnini yazınız.'
    ];
    showAlert("alert", "E-posta içeriği boş bırakılamaz.");
} elseif ($statusParam === 'unsuccessful') {
    $errDetail = $_SESSION['mail_last_error'] ?? 'E-posta sunucusu yanıt vermedi veya kimlik doğrulaması başarısız oldu. Lütfen giden posta (SMTP) ayarlarınızı kontrol ediniz.';
    unset($_SESSION['mail_last_error']);
    $feedback = [
        'type' => 'danger',
        'icon' => 'fa-times-circle',
        'title' => 'E-Posta Gönderilemedi!',
        'message' => htmlspecialchars($errDetail, ENT_QUOTES, 'UTF-8')
    ];
    showAlert("alert", "E-posta gönderimi başarısız oldu.");
}
?>

<style>
/* Send Mail Özelleştirilmiş Alert ve Select2 Stilleri */
.mail-feedback-alert {
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    font-size: 14px;
    line-height: 1.5;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.mail-alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.mail-alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.mail-alert-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
}

.mail-feedback-alert .alert-icon {
    font-size: 24px;
    flex-shrink: 0;
    margin-top: 2px;
}

.mail-feedback-alert .alert-title {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 3px;
}

.mail-feedback-alert .alert-body {
    font-size: 13.5px;
    opacity: 0.92;
}

/* Mail Seçim Alanı Stilleri */
.mail-select-container {
    position: relative;
    width: 100%;
}

.mail-select-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.mail-toolbar-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.mail-btn-tool {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.mail-btn-tool:hover {
    background: #e2e8f0;
    color: #0f172a;
    border-color: #94a3b8;
}

.mail-badge-count {
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 12px;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.select2-container--default .select2-selection--multiple {
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important;
    min-height: 44px !important;
    padding: 4px 8px !important;
    background-color: #ffffff !important;
    transition: all 0.2s ease;
}

.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__rendered {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    gap: 4px !important;
}

.select2-container--default .select2-selection--multiple .select2-search--inline {
    flex: 1 1 auto !important;
    min-width: 260px !important;
    display: inline-flex !important;
    margin: 2px 0 !important;
    float: none !important;
}

.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 220px !important;
    margin: 0 !important;
    padding: 4px 6px !important;
    font-size: 13px !important;
    line-height: 1.5 !important;
    color: #334155 !important;
    box-sizing: border-box !important;
}

.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field::placeholder {
    color: #94a3b8 !important;
    opacity: 1 !important;
    font-size: 13px !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background: #f1f5f9 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    padding: 4px 10px 4px 24px !important;
    font-size: 12.5px !important;
    font-weight: 500 !important;
    color: #1e293b !important;
    margin: 3px 4px 3px 0 !important;
    position: relative;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice.select2-is-external {
    background: #ecfdf5 !important;
    border-color: #a7f3d0 !important;
    color: #065f46 !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #ef4444 !important;
    position: absolute;
    left: 6px;
    top: 50%;
    transform: translateY(-50%);
    font-weight: bold;
    border: none !important;
    background: none !important;
    cursor: pointer;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #b91c1c !important;
}

.select2-dropdown {
    border: 1px solid #cbd5e1 !important;
    border-radius: 10px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
    overflow: hidden !important;
    z-index: 99999 !important;
}

/* Dropdown Sonuç Öğeleri */
.select2-results__option {
    padding: 9px 14px !important;
    font-size: 13px !important;
    border-bottom: 1px solid #f1f5f9;
    background-color: #ffffff !important;
    transition: background 0.15s ease, color 0.15s ease;
}

.select2-results__option:last-child {
    border-bottom: none;
}

.select2-recipient-option {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.recipient-title {
    font-weight: 600;
    color: #0f172a !important;
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13.5px;
}

.recipient-title i {
    color: #2563eb !important;
}

.recipient-sub {
    font-size: 12px;
    color: #475569 !important;
    display: flex;
    align-items: center;
    gap: 6px;
}

.recipient-sub i {
    color: #64748b !important;
}

/* HOVER & HIGHLIGHTED DURUM: Yüksek Kontrastlı Koyu Mavi Arka Plan */
.select2-container--default .select2-results__option--highlighted[aria-selected],
.select2-container--default .select2-results__option--highlighted,
.select2-results__option:hover {
    background: #1d4ed8 !important;
    color: #ffffff !important;
}

.select2-container--default .select2-results__option--highlighted .recipient-title,
.select2-container--default .select2-results__option--highlighted .recipient-title i,
.select2-results__option:hover .recipient-title,
.select2-results__option:hover .recipient-title i {
    color: #ffffff !important;
}

.select2-container--default .select2-results__option--highlighted .recipient-sub,
.select2-container--default .select2-results__option--highlighted .recipient-sub i,
.select2-results__option:hover .recipient-sub,
.select2-results__option:hover .recipient-sub i {
    color: #dbeafe !important;
}

/* Yeni Tag Ekleme Seçeneği */
.select2-new-email-tag {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #059669 !important;
}

.select2-container--default .select2-results__option--highlighted .select2-new-email-tag,
.select2-results__option:hover .select2-new-email-tag {
    color: #ffffff !important;
}

.helper-text-hint {
    font-size: 12px;
    color: #64748b;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.helper-text-hint kbd {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 11px;
}

/* Şık Dosya Eki Alanı */
.mail-attachment-section {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px dashed #e2e8f0;
}

.mail-attachment-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 12px;
    padding: 14px 20px;
    transition: all 0.2s ease;
    gap: 14px;
    flex-wrap: wrap;
}

.mail-attachment-box:hover {
    border-color: #3b82f6;
    background: #f0f7ff;
}

.mail-attachment-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.mail-attachment-info {
    flex: 1;
    min-width: 200px;
}

.attachment-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #1e293b;
    margin-bottom: 2px;
    cursor: pointer;
}

.attachment-badge {
    font-size: 11px;
    font-weight: 500;
    padding: 2px 7px;
    background: #e2e8f0;
    color: #64748b;
    border-radius: 10px;
}

.attachment-desc {
    font-size: 12.5px;
    color: #64748b;
    margin: 0;
}

.hidden-file-input {
    display: none !important;
}

.btn-attachment-choose {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0;
}

.btn-attachment-choose:hover {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
}

.selected-file-preview {
    margin-top: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.file-preview-left {
    display: flex;
    align-items: center;
    gap: 12px;
    overflow: hidden;
}

.file-preview-left .file-icon {
    font-size: 24px;
}

.file-preview-left .file-name {
    font-weight: 600;
    font-size: 13.5px;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 450px;
}

.file-preview-left .file-size {
    font-size: 11.5px;
    color: #64748b;
}

.btn-remove-file {
    background: #fee2e2;
    border: none;
    color: #ef4444;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-remove-file:hover {
    background: #ef4444;
    color: #ffffff;
}

.mail-template-list {
    max-height: 430px;
    overflow-y: auto;
}

.mail-template-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    margin-bottom: 8px;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    background: #fff;
}

.mail-template-item-info {
    min-width: 0;
}

.mail-template-item-name,
.mail-template-item-subject {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mail-template-item-name {
    color: #1e293b;
    font-weight: 600;
}

.mail-template-item-subject {
    color: #64748b;
    font-size: 12px;
}

.mail-template-item-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

body.dark-mode .mail-template-item {
    background: #1e293b;
    border-color: #334155;
}

body.dark-mode .mail-template-item-name {
    color: #f8fafc;
}
</style>

<div class="send-mail-manage-wrapper">
    <!-- Bildirim Durum Kartı -->
    <?php if (!empty($feedback)): ?>
        <div class="mail-feedback-alert mail-alert-<?= htmlspecialchars($feedback['type']) ?> animate-fade-in">
            <i class="fa <?= $feedback['icon'] ?> alert-icon"></i>
            <div>
                <div class="alert-title"><?= htmlspecialchars($feedback['title']) ?></div>
                <div class="alert-body"><?= htmlspecialchars($feedback['message']) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" id="myForm" enctype="multipart/form-data">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-paper-plane"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $pdat["p_title"] ?? 'Mail Gönder'; ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-info-circle"></i> Toplu & Bireysel E-Posta Gönderim Paneli
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <button type="submit" id="submitButton" class="btn-header btn-header-save">
                        <i class="fa fa-paper-plane"></i> Mail Gönder
                    </button>
                </div>
            </div>
        </div>

        <!-- Kart 1: Alıcı & Gönderici Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-envelope-o"></i>
                </div>
                <div>
                    <h5>E-Posta Konfigürasyonu</h5>
                    <p>Gönderici hesabı ve alıcı firmaları seçin, e-posta konusunu belirleyin</p>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- 1. Satır Sol: Gönderen Mail Adresi -->
                <div class="form-field">
                    <label for="mail_address"><font color="red">(*)</font> Gönderen Mail Adresi:</label>
                    <select name="mail_address" id="mail_address" class="form-control select2-single" style="width: 100%;">
                        <?php 
                        $currentUserId = (int)sesset("id");
                        $sql = $ac->prepare("SELECT * FROM mail_accounts WHERE account_type = 1 OR mail_user = 1 OR mail_user = ? ORDER BY account_type ASC, mail_address ASC");
                        $sql->execute([$currentUserId]);
                        while ($row = $sql->fetch(PDO::FETCH_ASSOC)){
                            $emailVal = htmlspecialchars($row["mail_address"], ENT_QUOTES, 'UTF-8');
                            $typeLabel = ((int)$row["account_type"] === 1) ? 'Genel' : 'Bireysel';
                            $passBadge = !empty($row["mail_password"]) ? ' [Özel SMTP]' : '';
                            echo "<option value='{$emailVal}'>{$emailVal} ({$typeLabel}){$passBadge}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- 1. Satır Sağ: Konu Başlığı -->
                <div class="form-field">
                    <label for="mailkonu"><font color="red">(*)</font> Konu Başlığı :</label>
                    <input autocomplete="off" required type="text" class="form-control" name="mailkonu" id="mailkonu" placeholder="E-posta konusunu giriniz">
                </div>

                <!-- 2. Satır: Firma & E-Posta Seçimi (Tam Genişlik) -->
                <div class="form-field" style="grid-column: 1 / -1;">
                    <div class="mail-select-toolbar">
                        <label for="customers" style="margin-bottom: 0;">
                            <font color="red">(*)</font> Alıcı Firma & E-Posta Seçimi:
                        </label>
                        <div class="mail-toolbar-actions">
                            <span class="mail-badge-count" id="selectedCountBadge">0 Alıcı Seçildi</span>
                            <button type="button" class="mail-btn-tool" id="btnSelectAllCustomers">
                                <i class="fa fa-check-square-o"></i> Tümünü Seç
                            </button>
                            <button type="button" class="mail-btn-tool" id="btnClearCustomers">
                                <i class="fa fa-times"></i> Temizle
                            </button>
                        </div>
                    </div>

                    <div class="mail-select-container">
                        <select required name="customers[]" id="customers" class="form-control select2-recipients" multiple="multiple" style="width: 100%;">
                            <?php if ($preselectedCustomer && !empty($preselectedCustomer['email']) && filter_var(trim($preselectedCustomer['email']), FILTER_VALIDATE_EMAIL)):
                                $pEmail = htmlspecialchars(trim($preselectedCustomer['email']), ENT_QUOTES, 'UTF-8');
                                $pCompany = htmlspecialchars($preselectedCustomer['company'] ?? '', ENT_QUOTES, 'UTF-8');
                                $pYetkili = htmlspecialchars($preselectedCustomer['yetkili'] ?? '', ENT_QUOTES, 'UTF-8');
                                $pCity = htmlspecialchars($preselectedCustomer['city'] ?? '', ENT_QUOTES, 'UTF-8');
                            ?>
                                <option value="<?php echo $pEmail; ?>" selected
                                    data-company="<?php echo $pCompany; ?>" 
                                    data-email="<?php echo $pEmail; ?>" 
                                    data-yetkili="<?php echo $pYetkili; ?>" 
                                    data-city="<?php echo $pCity; ?>">
                                    <?php echo $pCompany; ?> (<?php echo $pEmail; ?>)
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="helper-text-hint">
                        <i class="fa fa-info-circle text-primary"></i> 
                        Listeden hem <strong>firma adı</strong> hem de <strong>e-posta</strong> ile arama yapabilir veya kayıtlı olmayan bir adresi doğrudan yazıp <kbd>Enter</kbd> tuşuna basarak ekleyebilirsiniz.
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Mail İçeriği & Dosya Eki -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="card-icon card-icon-purple">
                        <i class="fa fa-pencil-square-o"></i>
                    </div>
                    <div>
                        <h5>Mail İçeriği</h5>
                        <p>Gönderilecek e-posta metnini ve eklerini hazırlayın</p>
                    </div>
                </div>
                <!-- Şablon Yönetimi -->
                <div style="display: flex; gap: 8px;">
                    <button type="button" id="btnSaveMailTemplate" class="btn btn-sm btn-outline-secondary" data-tooltip="Şablon Olarak Kaydet"
                        data-tooltip-location="bottom" style="border-radius: 8px; padding: 6px 12px; font-size: 13px;">
                        <i class="fa fa-save"></i> Şablon Kaydet
                    </button>
                    <button type="button" id="btnLoadMailTemplate" class="btn btn-sm btn-outline-primary" data-tooltip="Şablondan Aktar"
                        data-tooltip-location="bottom" style="border-radius: 8px; padding: 6px 12px; font-size: 13px;">
                        <i class="fa fa-hand-o-right"></i> Şablondan Yükle
                    </button>
                </div>
            </div>
            
            <!-- Editör -->
            <div class="editor-wrapper">
                <textarea required id="mailicerik" class="textarea_editor form-control border-radius-8" name="mailicerik" placeholder="E-posta içeriğinizi yazınız..."></textarea>
            </div>

            <!-- Şık Dosya Eki Alanı (İçeriğin Altında) -->
            <div class="mail-attachment-section">
                <div class="mail-attachment-box">
                    <div class="mail-attachment-icon">
                        <i class="fa fa-paperclip"></i>
                    </div>
                    <div class="mail-attachment-info">
                        <label class="attachment-label" for="dosya">
                            <span>Dosya Eki Ekle</span>
                            <span class="attachment-badge">Opsiyonel</span>
                        </label>
                        <p class="attachment-desc">PDF, Word, Excel, Görsel veya Arşiv dosyaları ekleyebilirsiniz</p>
                    </div>
                    <div class="mail-attachment-action">
                        <label for="dosya" class="btn-attachment-choose">
                            <i class="fa fa-upload mr-1"></i> Dosya Seç
                        </label>
                        <input name="dosya" id="dosya" type="file" class="hidden-file-input">
                    </div>
                </div>

                <!-- Seçilen dosya önizleme kartı -->
                <div id="filePreviewCard" class="selected-file-preview" style="display: none;">
                    <div class="file-preview-left">
                        <i class="fa fa-file-pdf-o file-icon" id="previewFileIcon"></i>
                        <div class="file-details">
                            <div class="file-name" id="previewFileName">dosya.pdf</div>
                            <div class="file-size" id="previewFileSize">0 KB</div>
                        </div>
                    </div>
                    <button type="button" class="btn-remove-file" id="btnRemoveFile" title="Dosyayı Kaldır">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="saveMailTemplateModal" tabindex="-1" role="dialog" aria-labelledby="saveMailTemplateTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveMailTemplateTitle"><i class="fa fa-save text-primary mr-2"></i>Mail Şablonu Kaydet</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label for="mailTemplateName">Şablon Adı</label>
                    <input type="text" id="mailTemplateName" class="form-control" maxlength="150" autocomplete="off" placeholder="Örn. Periyodik bakım bilgilendirmesi">
                </div>
                <small class="text-muted">Aynı addaki kişisel şablonunuz varsa konu, gönderen, firma/alıcılar ve içerik güncellenir.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="confirmSaveMailTemplate"><i class="fa fa-save mr-1"></i> Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loadMailTemplateModal" tabindex="-1" role="dialog" aria-labelledby="loadMailTemplateTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loadMailTemplateTitle"><i class="fa fa-folder-open-o text-primary mr-2"></i>Kayıtlı Mail Şablonları</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="mailTemplateList" class="mail-template-list"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var mailTemplateCsrfToken = <?php echo json_encode($mailTemplateCsrfToken, JSON_UNESCAPED_UNICODE); ?>;
    // Gönderen mail seçimi için standart Select2
    $('.select2-single').select2({
        minimumResultsForSearch: 6,
        width: '100%'
    });

    var $recipients = $('#customers');
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Seçenek öğesini formatla (Dropdown listesi)
    function formatRecipientResult(data) {
        if (!data.id) {
            return data.text;
        }

        // Kullanıcının serbest girdiği yeni e-posta etiketi
        if (data.isNew) {
            return $(
                '<div class="select2-new-email-tag">' +
                    '<i class="fa fa-plus-circle"></i>' +
                    '<span>Yeni E-Posta Ekle: <strong>' + $('<div>').text(data.text).html() + '</strong> (Harici Alıcı)</span>' +
                '</div>'
            );
        }

        var email = (data.email || data.id || '').toString().trim();
        if (!email || !emailRegex.test(email)) {
            return null;
        }

        var company = data.company || '';
        var yetkili = data.yetkili || '';
        var city = data.city || '';

        // Eğer data içinde company yoksa element üzerinden bak
        if (!company && data.element) {
            company = $(data.element).data('company') || data.text;
            email = $(data.element).data('email') || data.id;
            yetkili = $(data.element).data('yetkili') || '';
            city = $(data.element).data('city') || '';
        }

        if (!company) {
            company = data.text;
        }

        var subInfo = '<i class="fa fa-envelope mr-1"></i>' + $('<div>').text(email).html();
        if (yetkili) {
            subInfo += ' &bull; <i class="fa fa-user mr-1"></i>' + $('<div>').text(yetkili).html();
        }
        if (city) {
            subInfo += ' &bull; <i class="fa fa-map-marker mr-1"></i>' + $('<div>').text(city).html();
        }

        return $(
            '<div class="select2-recipient-option">' +
                '<div class="recipient-title"><i class="fa fa-building"></i> ' + $('<div>').text(company).html() + '</div>' +
                '<div class="recipient-sub">' + subInfo + '</div>' +
            '</div>'
        );
    }

    // Seçili etiketi formatla (Seçilen kutucuklar)
    function formatRecipientSelection(data) {
        if (!data.id) {
            return data.text;
        }

        var email = (data.email || data.id || '').toString().trim();
        if (!email || !emailRegex.test(email)) {
            return null;
        }

        var company = data.company || '';
        if (!company && data.element) {
            company = $(data.element).data('company') || '';
            email = $(data.element).data('email') || data.id;
        }

        if (company) {
            return company + ' (' + email + ')';
        }

        if (data.isNew || emailRegex.test(data.text)) {
            return data.text + ' [Harici]';
        }

        return data.text;
    }

    // AJAX Tabanlı Ultra Hızlı Select2 Başlat
    $recipients.select2({
        placeholder: "Firma adı veya e-posta adresi yazarak arayın ya da yeni e-posta girin...",
        allowClear: true,
        tags: true,
        tokenSeparators: [',', ';', ' '],
        minimumInputLength: 0,
        ajax: {
            url: 'api/search_recipients.php',
            dataType: 'json',
            delay: 150, // 150ms hızlı arama
            data: function(params) {
                return {
                    q: params.term || '',
                    limit: 30
                };
            },
            processResults: function(data) {
                return {
                    results: data.results || []
                };
            },
            cache: true
        },
        templateResult: formatRecipientResult,
        templateSelection: formatRecipientSelection,
        createTag: function(params) {
            var term = $.trim(params.term);
            if (term === '') {
                return null;
            }

            // Geçerli e-posta ise yeni tag oluşturulmasına izin ver
            if (emailRegex.test(term)) {
                return {
                    id: term,
                    text: term,
                    isNew: true
                };
            }

            return null;
        },
        language: {
            searching: function() { return "Aranıyor..."; },
            noResults: function() { return "Eşleşen firma bulunamadı. Yeni bir e-posta adresi yazıp Enter'a basabilirsiniz."; },
            inputTooShort: function() { return "Aramak için yazmaya başlayın..."; }
        }
    });

    // Seçilen alıcı sayısını güncelle
    function updateSelectedCount() {
        var values = $recipients.val() || [];
        var count = values.length;
        $('#selectedCountBadge').text(count + ' Alıcı Seçildi');
    }

    $recipients.on('change', updateSelectedCount);
    updateSelectedCount();

    // Tümünü Seç Butonu (AJAX ile tüm aktif e-postaları yükler)
    $('#btnSelectAllCustomers').on('click', function() {
        var $btn = $(this);
        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Yükleniyor...');

        $.ajax({
            url: 'api/search_recipients.php?action=all_emails',
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.status === 'success' && resp.results) {
                    var currentValues = $recipients.val() || [];
                    var newValues = [];

                    resp.results.forEach(function(item) {
                        newValues.push(item.id);
                        if ($recipients.find("option[value='" + item.id + "']").length === 0) {
                            var newOption = new Option(item.company + ' (' + item.email + ')', item.id, true, true);
                            $(newOption).data('company', item.company);
                            $(newOption).data('email', item.email);
                            $(newOption).data('yetkili', item.yetkili);
                            $(newOption).data('city', item.city);
                            $recipients.append(newOption);
                        }
                    });

                    // Birleştirilmiş değerleri ata
                    var merged = Array.from(new Set(currentValues.concat(newValues)));
                    $recipients.val(merged).trigger('change');

                    if (typeof toastr !== 'undefined') {
                        toastr.success(resp.results.length + ' aktif firma alıcı olarak eklendi.', 'Tümü Seçildi');
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Alıcı listesi alınamadı.', 'Hata');
                    }
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Sunucu bağlantı hatası oluştu.', 'Hata');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    // Seçimi Temizle Butonu
    $('#btnClearCustomers').on('click', function() {
        $recipients.val(null).trigger('change');
    });

    function showMailTemplateMessage(type, message, title) {
        if (typeof toastr !== 'undefined' && typeof toastr[type] === 'function') {
            toastr[type](message, title || 'Mail Şablonu');
            return;
        }
        alert(message);
    }

    function getMailEditorContent() {
        var $editor = $('#mailicerik');
        var editorData = $editor.data('wysihtml5');
        if (editorData && editorData.editor && typeof editorData.editor.getValue === 'function') {
            return $.trim(editorData.editor.getValue());
        }
        var $iframe = $editor.siblings('iframe.wysihtml5-sandbox');
        if ($iframe.length) {
            try {
                return $.trim($iframe[0].contentDocument.body.innerHTML);
            } catch (error) {}
        }
        return $.trim($editor.val());
    }

    function setMailEditorContent(content) {
        var $editor = $('#mailicerik');
        var editorData = $editor.data('wysihtml5');
        $editor.val(content);
        if (editorData && editorData.editor && typeof editorData.editor.setValue === 'function') {
            editorData.editor.setValue(content);
        } else {
            var $iframe = $editor.siblings('iframe.wysihtml5-sandbox');
            if ($iframe.length) {
                try {
                    $iframe[0].contentDocument.body.innerHTML = content;
                } catch (error) {}
            }
        }
        $editor.trigger('change');
    }

    function collectTemplateRecipients() {
        return ($recipients.select2('data') || []).map(function(item) {
            var $option = item.element ? $(item.element) : $();
            return {
                email: $.trim(item.id || ''),
                company: $.trim(item.company || $option.data('company') || ''),
                yetkili: $.trim(item.yetkili || $option.data('yetkili') || ''),
                city: $.trim(item.city || $option.data('city') || '')
            };
        });
    }

    $('#btnSaveMailTemplate').on('click', function() {
        var recipients = collectTemplateRecipients();
        if (!$('#mail_address').val() || !$.trim($('#mailkonu').val()) || !getMailEditorContent() || recipients.length === 0) {
            showMailTemplateMessage('warning', 'Şablon kaydetmeden önce gönderen, konu, alıcılar ve mail içeriğini eksiksiz doldurunuz.', 'Eksik Alan');
            return;
        }
        $('#mailTemplateName').val('');
        $('#saveMailTemplateModal').modal('show');
        setTimeout(function() { $('#mailTemplateName').focus(); }, 250);
    });

    $('#confirmSaveMailTemplate').on('click', function() {
        var name = $.trim($('#mailTemplateName').val());
        if (!name) {
            showMailTemplateMessage('warning', 'Lütfen şablon adını giriniz.', 'Eksik Şablon Adı');
            return;
        }

        var $button = $(this);
        var originalHtml = $button.html();
        $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');
        $.ajax({
            url: 'api/mail_templates.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'save',
                csrf_token: mailTemplateCsrfToken,
                name: name,
                sender_email: $('#mail_address').val(),
                subject: $.trim($('#mailkonu').val()),
                recipients: JSON.stringify(collectTemplateRecipients()),
                body_html: getMailEditorContent()
            }
        }).done(function(response) {
            $('#saveMailTemplateModal').modal('hide');
            showMailTemplateMessage('success', response.message || 'Şablon kaydedildi.');
        }).fail(function(xhr) {
            var response = xhr.responseJSON || {};
            showMailTemplateMessage('error', response.message || 'Şablon kaydedilemedi.', 'Hata');
        }).always(function() {
            $button.prop('disabled', false).html(originalHtml);
        });
    });

    $('#mailTemplateName').on('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            $('#confirmSaveMailTemplate').trigger('click');
        }
    });

    function renderMailTemplateList(templates) {
        var $list = $('#mailTemplateList').empty();
        if (!templates.length) {
            $('<div>', { class: 'text-center text-muted py-4', text: 'Henüz kayıtlı mail şablonunuz bulunmuyor.' }).appendTo($list);
            return;
        }

        templates.forEach(function(template) {
            var $item = $('<div>', { class: 'mail-template-item' });
            var $info = $('<div>', { class: 'mail-template-item-info' });
            $('<div>', { class: 'mail-template-item-name', text: template.name }).appendTo($info);
            $('<div>', { class: 'mail-template-item-subject', text: template.subject + ' · ' + template.sender_email }).appendTo($info);
            var $actions = $('<div>', { class: 'mail-template-item-actions' });
            $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-primary btn-apply-mail-template',
                'data-id': template.id,
                html: '<i class="fa fa-check mr-1"></i> Yükle'
            }).appendTo($actions);
            $item.append($info, $actions).appendTo($list);
        });
    }

    $('#btnLoadMailTemplate').on('click', function() {
        $('#mailTemplateList').html('<div class="text-center text-muted py-4"><i class="fa fa-spinner fa-spin mr-1"></i> Şablonlar yükleniyor...</div>');
        $('#loadMailTemplateModal').modal('show');
        $.getJSON('api/mail_templates.php', { action: 'list' })
            .done(function(response) {
                renderMailTemplateList(response.templates || []);
            })
            .fail(function(xhr) {
                var response = xhr.responseJSON || {};
                $('#mailTemplateList').empty();
                showMailTemplateMessage('error', response.message || 'Şablon listesi alınamadı.', 'Hata');
            });
    });

    $('#mailTemplateList').on('click', '.btn-apply-mail-template', function() {
        var templateId = $(this).data('id');
        var $button = $(this);
        $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.getJSON('api/mail_templates.php', { action: 'get', id: templateId })
            .done(function(response) {
                var template = response.template || {};
                var senderAvailable = $('#mail_address option').filter(function() {
                    return $(this).val() === (template.sender_email || '');
                }).length > 0;
                if (senderAvailable) {
                    $('#mail_address').val(template.sender_email).trigger('change');
                } else {
                    showMailTemplateMessage('warning', 'Şablondaki gönderici hesabı artık kullanılamıyor; mevcut gönderici seçimi korundu.', 'Gönderici Bulunamadı');
                }
                $('#mailkonu').val(template.subject || '');

                $recipients.empty();
                (template.recipients || []).forEach(function(recipient) {
                    var label = recipient.company ? recipient.company + ' (' + recipient.email + ')' : recipient.email;
                    var option = new Option(label, recipient.email, true, true);
                    $(option)
                        .data('company', recipient.company || '')
                        .data('email', recipient.email || '')
                        .data('yetkili', recipient.yetkili || '')
                        .data('city', recipient.city || '');
                    $recipients.append(option);
                });
                $recipients.trigger('change');
                setMailEditorContent(template.body_html || '');
                $('#loadMailTemplateModal').modal('hide');
                showMailTemplateMessage('success', '“' + (template.name || 'Şablon') + '” tüm mail alanlarıyla yüklendi.');
            })
            .fail(function(xhr) {
                var response = xhr.responseJSON || {};
                showMailTemplateMessage('error', response.message || 'Şablon yüklenemedi.', 'Hata');
            })
            .always(function() {
                $button.prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Yükle');
            });
    });

    // Dosya Eki Seçim & Önizleme Yönetimi
    $('#dosya').on('change', function() {
        var file = this.files[0];
        if (file) {
            var fileName = file.name;
            var fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            if (file.size < 1024 * 1024) {
                fileSize = (file.size / 1024).toFixed(1) + ' KB';
            }
            $('#previewFileName').text(fileName);
            $('#previewFileSize').text(fileSize);

            // Uzantıya göre ikon ve renk
            var ext = fileName.split('.').pop().toLowerCase();
            var iconClass = 'fa fa-file-text-o text-primary';
            if (['pdf'].indexOf(ext) > -1) {
                iconClass = 'fa fa-file-pdf-o text-danger';
            } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].indexOf(ext) > -1) {
                iconClass = 'fa fa-file-image-o text-success';
            } else if (['xls', 'xlsx', 'csv'].indexOf(ext) > -1) {
                iconClass = 'fa fa-file-excel-o text-success';
            } else if (['doc', 'docx'].indexOf(ext) > -1) {
                iconClass = 'fa fa-file-word-o text-primary';
            } else if (['zip', 'rar', '7z', 'tar'].indexOf(ext) > -1) {
                iconClass = 'fa fa-file-archive-o text-warning';
            }

            $('#previewFileIcon').attr('class', iconClass + ' file-icon');
            $('#filePreviewCard').slideDown(200);
        } else {
            $('#filePreviewCard').slideUp(200);
        }
    });

    $('#btnRemoveFile').on('click', function() {
        $('#dosya').val('');
        $('#filePreviewCard').slideUp(200);
    });

    // Form Gönderim & Doğrulama
    $('#myForm').on('submit', function(e) {
        var selected = $recipients.val() || [];
        var subject = $.trim($('#mailkonu').val());

        if (selected.length === 0) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') {
                toastr.warning('Lütfen en az bir alıcı firma veya e-posta adresi seçiniz.', 'Eksik Alıcı');
            } else {
                alert('Lütfen en az bir alıcı firma veya e-posta adresi seçiniz.');
            }
            return false;
        }

        if (subject === '') {
            e.preventDefault();
            if (typeof toastr !== 'undefined') {
                toastr.warning('Lütfen e-posta konusunu giriniz.', 'Boş Konu');
            } else {
                alert('Lütfen e-posta konusunu giriniz.');
            }
            return false;
        }

        var $btn = $('#submitButton');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mail Gönderiliyor...');
        return true;
    });

    // WYSIHTML5 / Metin Editörü Placeholder ve Padding Hizalama
    function applyEditorPadding() {
        $('iframe.wysihtml5-sandbox').each(function () {
            try {
                var doc = this.contentDocument || this.contentWindow.document;
                if (doc && doc.body) {
                    if (!doc.getElementById('wysi-mail-iframe-style')) {
                        var style = doc.createElement('style');
                        style.id = 'wysi-mail-iframe-style';
                        style.innerHTML = 'html, body { padding: 10px 14px !important; margin: 0 !important; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important; font-size: 13.5px !important; line-height: 1.5 !important; color: #334155 !important; box-sizing: border-box !important; } body.placeholder { color: #94a3b8 !important; padding: 10px 14px !important; margin: 0 !important; }';
                        doc.head.appendChild(style);
                    }
                    doc.body.style.padding = '10px 14px';
                }
            } catch (e) {}
        });
    }

    applyEditorPadding();
    setTimeout(applyEditorPadding, 100);
    setTimeout(applyEditorPadding, 300);
    setTimeout(applyEditorPadding, 700);
    setTimeout(applyEditorPadding, 1500);
});
</script>
