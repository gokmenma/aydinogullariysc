<?php
use App\Model\CustomerModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

permcontrol("mailandsmssend");
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
                            <?php if ($preselectedCustomer && !empty($preselectedCustomer['email'])): 
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
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-tooltip="Şablon Olarak Kaydet"
                        data-tooltip-location="bottom" style="border-radius: 8px; padding: 6px 12px; font-size: 13px;">
                        <i class="fa fa-save"></i> Şablon Kaydet
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-tooltip="Şablondan Aktar"
                        data-tooltip-location="bottom" style="border-radius: 8px; padding: 6px 12px; font-size: 13px;">
                        <i class="fa fa-hand-o-right"></i> Şablondan Yükle
                    </button>
                </div>
            </div>
            
            <!-- Editör -->
            <div class="editor-wrapper">
                <textarea required class="textarea_editor form-control border-radius-8" name="mailicerik" placeholder="E-posta içeriğinizi yazınız..."></textarea>
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

<script>
$(document).ready(function() {
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

        var company = data.company || '';
        var email = data.email || data.id;
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

        var company = data.company || '';
        var email = data.email || data.id;

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
});
</script>