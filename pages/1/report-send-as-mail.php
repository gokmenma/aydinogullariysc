<?php
permcontrol("mailandsmssend");

use App\Helper\Date;
use App\Model\PurchaseModel;
use App\Model\CustomerModel;
use App\Model\OfferModel;
use App\Model\ReportsModel;

$type = $_GET["type"] ?? '';
$id = (int)($_GET["id"] ?? 0);

$customerObj = new CustomerModel();
$customer = null;
$mail_subject = "";
$file_type = "";
$action_url = "";
$doc_number = "";
$page_heading = "Raporu Mail Olarak Gönder";
$header_badge_icon = "fa-file-text-o";
$doc_desc_text = "Bu rapor, gönderim anında sistem tarafından en güncel veriler ve resmi antetle PDF olarak üretilip e-postaya otomatik eklenecektir.";

if ($type === "purchase") {
    $page_heading = "Satın Alma Siparişini Mail Olarak Gönder";
    $file_type = "Satın Alma Formu";
    $header_badge_icon = "fa-shopping-cart";
    $doc_desc_text = "Bu satın alma siparişi, gönderim anında sistem tarafından en güncel veriler ve resmi antetle PDF olarak üretilip e-postaya otomatik eklenecektir.";
    $purchaseObj = new PurchaseModel();
    $purchase = $purchaseObj->find($id);
    if ($purchase) {
        $customer = $customerObj->find($purchase->companyID ?? 0);
        $doc_number = $purchase->siparisNo ?? '';
        $mail_subject = "Satın Alma Formu" . ($doc_number ? " - " . $doc_number : "");
    }
    $action_url = "index.php?p=purchase-detail&id=" . urlencode((string)$id) . "&send-mail=true";
} elseif ($type === "offer") {
    $page_heading = "Teklifi Mail Olarak Gönder";
    $file_type = "Teklif Formu";
    $header_badge_icon = "fa-file-text-o";
    $doc_desc_text = "Bu teklif formu, gönderim anında sistem tarafından en güncel veriler ve resmi antetle PDF olarak üretilip e-postaya otomatik eklenecektir.";
    $offerObj = new OfferModel();
    $offer = $offerObj->find($id);
    if ($offer) {
        $customer = $customerObj->find($offer->cid ?? 0);
        $doc_number = $offer->teklifNo ?? '';
        $mail_subject = "Teklif Formu" . ($doc_number ? " - " . $doc_number : "");
    }
    $action_url = "index.php?p=offer-view&id=" . urlencode((string)$id) . "&send-mail=true";
} else {
    // Reports table types: ysc, hst, met, yas, oys, aas vb.
    $page_heading = "Raporu Mail Olarak Gönder";
    $reportObj = new ReportsModel();
    $report = $reportObj->find($id);
    if ($report) {
        $customer = $customerObj->find($report->customer_id ?? 0);
        $doc_number = $report->report_number ?? '';
    }

    // Rapor tipini veritabanından sorgula
    $stmtRt = $ac->prepare("SELECT reportName, deviceType FROM report_types WHERE page_link = ? LIMIT 1");
    $stmtRt->execute([$type]);
    $rtInfo = $stmtRt->fetch(PDO::FETCH_ASSOC);

    if ($rtInfo && !empty($rtInfo['reportName'])) {
        $file_type = $rtInfo['reportName'];
    } elseif ($type === 'ysc') {
        $file_type = "Yangın Söndürme Tüpü Kontrol Raporu";
    } elseif ($type === 'hst') {
        $file_type = "Hidrostatik Test Raporu";
    } elseif ($type === 'met') {
        $file_type = "Mekanik Tesisat Kontrol Raporu";
    } elseif ($type === 'yas') {
        $file_type = "Yangın Algılama Sistemi Kontrol Raporu";
    } elseif ($type === 'oys') {
        $file_type = "Yangın Otomatik Söndürme Kontrol Raporu";
    } elseif ($type === 'aas') {
        $file_type = "Acil Aydınlatma Kontrol Raporu";
    } else {
        $file_type = "Kontrol Raporu";
    }

    $mail_subject = $file_type . ($doc_number ? " - " . $doc_number : "");
    $action_url = "index.php?p=reports/" . urlencode($type) . "/report-view-" . urlencode($type) . "&id=" . urlencode((string)$id) . "&send-mail=true";
}

$pdf_file_preview_name = (($customer->company ?? '') ? ($customer->company . '-') : '') . ($doc_number ?: 'Belge') . '.pdf';

// Durum Mesajları Hazırlığı
$feedback = null;
$statusParam = $_GET["st"] ?? '';

if ($statusParam === "success-mail") {
    $feedback = [
        'type' => 'success',
        'icon' => 'fa-check-circle',
        'title' => 'E-Posta Başarıyla Gönderildi!',
        'message' => 'Rapor PDF dosyası ilgili e-posta adreslerine başarıyla iletildi ve işlem geçmişine kaydedildi.'
    ];
    $link = "&type=" . urlencode($type);
    showAlert("success", "Mail başarıyla gönderildi", $link);
} elseif ($statusParam === "unsuccessful") {
    $err = $_SESSION['mail_last_error'] ?? 'Mail gönderimi sırasında bir hata meydana geldi.';
    unset($_SESSION['mail_last_error']);
    $feedback = [
        'type' => 'danger',
        'icon' => 'fa-times-circle',
        'title' => 'E-Posta Gönderilemedi!',
        'message' => htmlspecialchars($err, ENT_QUOTES, 'UTF-8')
    ];
    showAlert("danger", "Mail gönderilemedi: " . htmlspecialchars($err, ENT_QUOTES, 'UTF-8'), "&type=" . urlencode($type));
}
?>

<style>
/* Send Mail & Report Mail Premium Theme Styles */
.send-mail-manage-wrapper {
    max-width: 100%;
    margin: 0 auto;
}

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

/* Premium Header Card */
.premium-header-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.04);
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.header-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 6px 14px -3px rgba(37, 99, 235, 0.35);
}

.header-title h4 {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px 0;
    letter-spacing: -0.01em;
}

.header-number-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    padding: 3px 10px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-header {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    font-size: 13.5px;
    font-weight: 600;
    border-radius: 10px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-header-secondary {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #475569;
}

.btn-header-secondary:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.btn-header-save {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.btn-header-save:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
    transform: translateY(-1px);
}

/* Form Card */
.form-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.04);
}

.form-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 22px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
}

.card-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.card-icon-blue {
    background: #eff6ff;
    color: #2563eb;
}

.card-icon-purple {
    background: #faf5ff;
    color: #9333ea;
}

.form-card-header h5 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 3px 0;
}

.form-card-header p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

@media (max-width: 991px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-field label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 7px;
}

.form-field .form-control {
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13.5px;
    color: #1e293b;
    background-color: #ffffff;
    transition: all 0.2s ease;
    height: auto;
}

.form-field .form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    outline: none;
}

.form-field .form-control[readonly],
.form-field .form-control:disabled {
    background-color: #f8fafc;
    color: #475569;
    border-color: #e2e8f0;
    cursor: default;
}

.helper-text-hint {
    font-size: 12px;
    color: #64748b;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Bootstrap Select Customization */
.bootstrap-select .btn.dropdown-toggle {
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important;
    padding: 10px 14px !important;
    font-size: 13.5px !important;
    background: #ffffff !important;
    color: #1e293b !important;
    box-shadow: none !important;
}

.bootstrap-select .btn.dropdown-toggle:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
}

/* Attachment Box */
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
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.mail-attachment-info {
    flex: 1;
    min-width: 220px;
}

.attachment-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 14px;
    color: #1e293b;
    margin-bottom: 2px;
}

.attachment-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 8px;
    border: 1px solid #bae6fd;
}

.attachment-desc {
    font-size: 12.5px;
    color: #64748b;
    margin: 0;
}

.mail-attachment-action-badge {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Dark Mode Uyum Stilleri */
body.dark-mode .premium-header-card,
body.dark-mode .form-card {
    background: #0f172a;
    border-color: #1e293b;
}

body.dark-mode .header-title h4,
body.dark-mode .form-card-header h5,
body.dark-mode .form-field label,
body.dark-mode .attachment-label {
    color: #f8fafc;
}

body.dark-mode .header-number-badge,
body.dark-mode .btn-header-secondary {
    background: #1e293b;
    border-color: #334155;
    color: #94a3b8;
}

body.dark-mode .form-card-header {
    border-bottom-color: #1e293b;
}

body.dark-mode .form-field .form-control {
    background: #1e293b;
    border-color: #334155;
    color: #f8fafc;
}

body.dark-mode .form-field .form-control[readonly],
body.dark-mode .form-field .form-control:disabled {
    background: #0f172a;
    color: #94a3b8;
    border-color: #334155;
}

body.dark-mode .bootstrap-select .btn.dropdown-toggle {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}

body.dark-mode .mail-attachment-box {
    background: #1e293b;
    border-color: #334155;
}

body.dark-mode .mail-attachment-box:hover {
    background: #0f172a;
    border-color: #3b82f6;
}

body.dark-mode .mail-attachment-section {
    border-top-color: #334155;
}
</style>

<div class="send-mail-manage-wrapper">
    <!-- Bildirim Durum Kartı -->
    <?php if (!empty($feedback)): ?>
        <div class="mail-feedback-alert mail-alert-<?php echo htmlspecialchars($feedback['type'], ENT_QUOTES, 'UTF-8'); ?> animate-fade-in">
            <i class="fa <?php echo $feedback['icon']; ?> alert-icon"></i>
            <div>
                <div class="alert-title"><?php echo htmlspecialchars($feedback['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="alert-body"><?php echo htmlspecialchars($feedback['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" id="myForm" action="<?php echo htmlspecialchars($action_url, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="id" id="id" value="<?php echo htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="type" id="type" value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-paper-plane"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo htmlspecialchars($page_heading, ENT_QUOTES, 'UTF-8'); ?></h4>
                        <span class="header-number-badge">
                            <i class="fa <?php echo htmlspecialchars($header_badge_icon, ENT_QUOTES, 'UTF-8'); ?>"></i> <?php echo htmlspecialchars($file_type . ($doc_number ? " [{$doc_number}]" : ""), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <button onclick="window.history.back()" type="button" class="btn-header btn-header-secondary">
                        <i class="fa fa-arrow-left"></i> Geri
                    </button>
                    <button type="submit" id="submitButton" class="btn-header btn-header-save">
                        <i class="fa fa-paper-plane"></i> Mail Gönder
                    </button>
                </div>
            </div>
        </div>

        <!-- Kart 1: Alıcı & Belge Konfigürasyonu -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-envelope-o"></i>
                </div>
                <div>
                    <h5>Alıcı ve Belge Bilgileri</h5>
                    <p>Firma e-posta adresini ve CC kopya alıcılarını kontrol edin, konuyu belirleyin</p>
                </div>
            </div>

            <div class="form-grid">
                <!-- 1. Satır Sol: Alıcı Firma Mail Adresi -->
                <div class="form-field">
                    <label for="customer_mail_address"><font color="red">(*)</font> Firma / Alıcı Mail Adresi:</label>
                    <input type="text" class="form-control" name="customer_mail_address" id="customer_mail_address" required
                        value="<?php echo htmlspecialchars($customer->email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="ornek@firma.com, muhasebe@firma.com">
                    <div class="helper-text-hint">
                        <i class="fa fa-info-circle text-primary"></i> Birden fazla alıcı eklemek için aralarına virgül koyunuz.
                    </div>
                </div>

                <!-- 1. Satır Sağ: Kopya Olarak Gönder (CC) -->
                <div class="form-field">
                    <label for="mail_address">Kopya Olarak Gönder (CC):</label>
                    <select name="mail_address[]" id="mail_address" class="selectpicker form-control" multiple data-actions-box="true"
                        data-style="border bg-white" data-selected-text-format="count > 2">
                        <?php
                        $sql = $ac->prepare("SELECT email, username, Unvan from users WHERE email IS NOT NULL AND email != '' ORDER BY username ASC");
                        $sql->execute();
                        while ($row = $sql->fetch(PDO::FETCH_OBJ)) {
                            $selected = sesset("email") == $row->email ? "selected" : "";
                            echo "<option value=\"" . htmlspecialchars($row->email, ENT_QUOTES, 'UTF-8') . "\" $selected>" . htmlspecialchars($row->username . " (" . ($row->Unvan ? $row->Unvan . " - " : "") . $row->email . ")", ENT_QUOTES, 'UTF-8') . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- 2. Satır Sol: Konu Başlığı -->
                <div class="form-field">
                    <label for="mailkonu"><font color="red">(*)</font> Konu Başlığı:</label>
                    <input autocomplete="off" required type="text" class="form-control" name="mailkonu" id="mailkonu"
                        value="<?php echo htmlspecialchars($mail_subject, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="E-posta konu başlığını giriniz">
                </div>

                <!-- 2. Satır Sağ: Gönderilecek Belge Türü -->
                <div class="form-field">
                    <label for="file_type_display">Gönderilecek Belge Türü:</label>
                    <input class="form-control" name="file_type_display" id="file_type_display" type="text" 
                        value="<?php echo htmlspecialchars($file_type, ENT_QUOTES, 'UTF-8'); ?>" readonly disabled>
                </div>
            </div>
        </div>

        <!-- Kart 2: Mail İçeriği & Otomatik PDF Eki -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-purple">
                    <i class="fa fa-pencil-square-o"></i>
                </div>
                <div>
                    <h5>Mail İçeriği ve Açıklama</h5>
                    <p>Gönderilecek e-posta metninin altına not veya açıklama ekleyebilirsiniz</p>
                </div>
            </div>

            <!-- Editör -->
            <div class="editor-wrapper mb-3">
                <textarea class="textarea_editor form-control border-radius-8" name="mail_body" id="mail_body"
                    placeholder="Gönderilecek belgenin altına iletmek istediğiniz özel not veya açıklamayı buraya yazabilirsiniz..."></textarea>
            </div>

            <!-- Otomatik Belge Eki Bilgi Kutusu -->
            <div class="mail-attachment-section">
                <div class="mail-attachment-box">
                    <div class="mail-attachment-icon">
                        <i class="fa fa-file-pdf-o"></i>
                    </div>
                    <div class="mail-attachment-info">
                        <div class="attachment-label">
                            <span><?php echo htmlspecialchars($pdf_file_preview_name, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="attachment-badge">Otomatik PDF Eki</span>
                        </div>
                        <p class="attachment-desc">
                            <?php echo htmlspecialchars($doc_desc_text, ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>
                    <div class="mail-attachment-action">
                        <span class="mail-attachment-action-badge">
                            <i class="fa fa-check-circle"></i> Eklenmeye Hazır
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<script>
$(document).ready(function () {
    // Sayfa ve breadcrumb başlığını belge türüne göre dinamik güncelle
    var pageHeading = <?php echo json_encode($page_heading); ?>;
    var siteTitle = <?php echo json_encode(set("site_title") ?: 'Aydınoğulları YSC'); ?>;
    document.title = pageHeading + ' - ' + siteTitle;
    $('.header-breadcrumb .breadcrumb-item.active').text(pageHeading);

    $("#myForm").on("submit", function (e) {
        var custMail = $("#customer_mail_address").val().trim();
        if (!custMail) {
            alert("Lütfen alıcı firma mail adresini giriniz.");
            $("#customer_mail_address").focus();
            e.preventDefault();
            return false;
        }

        var subject = $("#mailkonu").val().trim();
        if (!subject) {
            alert("Lütfen bir konu başlığı giriniz.");
            $("#mailkonu").focus();
            e.preventDefault();
            return false;
        }

        // Butonu gönderim durumuna al
        $("#submitButton").prop("disabled", true).html('<i class="fa fa-circle-o-notch fa-spin"></i> Gönderiliyor...');
    });

    $(".selectpicker").selectpicker({
        noneSelectedText: "Kullanıcı Seçin...",
        size: 8,
        deselectAllText: "Seçimi Temizle",
        selectAllText: "Tümünü Seç",
        countSelectedText: "{0} kullanıcı seçildi",
        liveSearch: true
    });
});
</script>