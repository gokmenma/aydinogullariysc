<?php

use App\Model\CustomerModel;
use App\Model\SettingsModel;

permcontrol("mailandsmssend");

$settingsModel = new SettingsModel();
$customerModel = new CustomerModel();

// SMS Entegrasyon Ayarlarını Kontrol Et
$smsActive   = ($settingsModel->getSetting('sms_active') === 'on');
$smsUsername = trim((string)$settingsModel->getSetting('sms_username', ''));
$smsTitle    = trim((string)$settingsModel->getSetting('sms_title', ''));
$smsPass     = (string)$settingsModel->getSetting('sms_pass', '');

$isConfigured = ($smsActive && !empty($smsUsername) && !empty($smsTitle) && !empty($smsPass));

$userId = (int)($_SESSION['lid'] ?? 0);
$userPerm = (int)($_SESSION['perms'] ?? 0);
$canManageSettings = (permtrue("panelsettings") || $userId === 1 || $userPerm === 1);

$feedback = null;

// Form Gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['post'])) {
    if (!$isConfigured) {
        $feedback = [
            'type' => 'danger',
            'title' => 'İşlem Engellendi!',
            'message' => 'Panel ayarlarında SMS entegrasyon bilgileri eksik veya servis pasif olduğu için SMS gönderilemez. Lütfen önce ayarları tamamlayınız.'
        ];
    } else {
        $rawCustomers = $_POST['customers'] ?? [];
        $message = trim((string)($_POST['message'] ?? ''));

        if (empty($rawCustomers)) {
            $feedback = [
                'type' => 'warning',
                'title' => 'Eksik Seçim!',
                'message' => 'Lütfen mesaj göndermek istediğiniz en az bir müşteri seçiniz.'
            ];
        } elseif (empty($message)) {
            $feedback = [
                'type' => 'warning',
                'title' => 'Boş Mesaj!',
                'message' => 'Lütfen iletmek istediğiniz SMS mesaj içeriğini yazınız.'
            ];
        } else {
            // SMS Gönderimini Başlat
            $result = send_sms($rawCustomers, $message);

            if (!empty($result['success'])) {
                $recipientCount = (int)($result['count'] ?? count($rawCustomers));
                if (function_exists('audit_log')) {
                    audit_log(
                        'send',
                        'sms',
                        "Toplu SMS başarıyla iletildi. ({$recipientCount} alıcı, Başlık: {$smsTitle})",
                        'customers',
                        (string)$recipientCount,
                        [
                            'recipients_count' => $recipientCount,
                            'title' => $smsTitle,
                            'job_id' => $result['job_id'] ?? ''
                        ]
                    );
                }
                $feedback = [
                    'type' => 'success',
                    'title' => 'SMS Gönderildi!',
                    'message' => "Mesajınız seçilen {$recipientCount} müşteriye başarıyla iletildi."
                ];
            } else {
                $errMsg = $result['message'] ?? 'SMS gönderimi sırasında bilinmeyen bir hata oluştu.';
                if (function_exists('audit_log')) {
                    audit_log(
                        'send_failed',
                        'sms',
                        "SMS gönderimi başarısız oldu: {$errMsg}",
                        'customers',
                        '0'
                    );
                }
                $feedback = [
                    'type' => 'danger',
                    'title' => 'Gönderim Başarısız!',
                    'message' => $errMsg
                ];
            }
        }
    }
}

// Aktif ve telefon numarası olan müşterileri getir
$customerList = $customerModel->getActiveCustomersWithPhone();
?>

<style>
/* ==========================================================
   PREMIUM THEME - SMS GÖNDERME SAYFASI STİLLERİ
   ========================================================== */
.sms-page-container {
    padding-bottom: 30px;
}

/* Sayfa Başlığı ve Hero Banner */
.sms-hero-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    overflow: hidden;
}

.sms-hero-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: linear-gradient(180deg, #4f46e5 0%, #06b6d4 100%);
}

.sms-hero-info {
    display: flex;
    align-items: center;
    gap: 18px;
}

.sms-hero-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.35);
    flex-shrink: 0;
}

.sms-hero-text h4 {
    margin: 0 0 4px 0;
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.sms-hero-text p {
    margin: 0;
    font-size: 13.5px;
    color: #64748b;
}

.sms-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 600;
}

.sms-status-pill.pill-active {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.sms-status-pill.pill-inactive {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.sms-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.pill-active .sms-status-dot {
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
}

.pill-inactive .sms-status-dot {
    background: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
}

/* Entegrasyon Eksik / Pasif Uyarı Kartı */
.sms-warning-card {
    background: #fffbeb;
    border: 1.5px solid #fde68a;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px -2px rgba(245, 158, 11, 0.08);
    position: relative;
}

.sms-warning-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 18px;
}

.sms-warning-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #fef3c7;
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.sms-warning-content h5 {
    margin: 0 0 4px 0;
    font-size: 16.5px;
    font-weight: 700;
    color: #92400e;
}

.sms-warning-content p {
    margin: 0;
    font-size: 13.5px;
    color: #b45309;
    line-height: 1.5;
}

.sms-checklist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.sms-check-item {
    background: #ffffff;
    border: 1px solid #fed7aa;
    border-radius: 10px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
}

.sms-check-label {
    font-weight: 500;
    color: #475569;
}

.sms-check-badge {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
}

.sms-check-badge.badge-ok {
    background: #dcfce7;
    color: #15803d;
}

.sms-check-badge.badge-missing {
    background: #fee2e2;
    color: #b91c1c;
}

.sms-warning-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    padding-top: 10px;
    border-top: 1px dashed #fcd34d;
}

/* Ana Form Kartı */
.sms-main-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
    overflow: hidden;
}

.sms-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fafafa;
    flex-wrap: wrap;
    gap: 12px;
}

.sms-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15.5px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.sms-card-body {
    padding: 24px 28px;
}

/* Gönderici Bilgi Çubuğu */
.sms-sender-infobar {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.sms-sender-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #475569;
}

.sms-sender-item strong {
    color: #0f172a;
}

.sms-sender-item i {
    color: #4f46e5;
}

/* Form Elemanları */
.sms-field-group {
    margin-bottom: 22px;
}

.sms-field-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: #1e293b;
}

.sms-field-label .required-mark {
    color: #ef4444;
    margin-left: 2px;
}

.sms-counter-badge {
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    background: #f1f5f9;
    color: #475569;
    transition: all 0.2s ease;
}

.sms-counter-badge.limit-warning {
    background: #fef3c7;
    color: #d97706;
}

.sms-textarea {
    width: 100%;
    min-height: 140px;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 14px;
    line-height: 1.5;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.2s ease;
    resize: vertical;
}

.sms-textarea:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3.5px rgba(79, 70, 229, 0.12);
    outline: none;
    background: #ffffff;
}

.sms-textarea:disabled {
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
    border-color: #e2e8f0;
}

.sms-help-text {
    font-size: 12.5px;
    color: #64748b;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Müşteri Seçimi & Butonlar */
.sms-select-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.sms-btn-tool {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.sms-btn-tool:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* Select2 Özel Şekillendirme */
.select2-container--default .select2-selection--multiple {
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 12px !important;
    min-height: 48px !important;
    padding: 4px 8px !important;
    background-color: #ffffff !important;
    transition: all 0.2s ease;
}

.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #4f46e5 !important;
    box-shadow: 0 0 0 3.5px rgba(79, 70, 229, 0.12) !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background: #e0e7ff !important;
    border: 1px solid #c7d2fe !important;
    color: #3730a3 !important;
    border-radius: 6px !important;
    padding: 4px 10px !important;
    font-size: 12.5px !important;
    font-weight: 500 !important;
    margin-top: 4px !important;
    margin-right: 6px !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #4338ca !important;
    margin-right: 6px !important;
    font-weight: bold;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #dc2626 !important;
}

.select2-dropdown {
    border: 1px solid #cbd5e1 !important;
    border-radius: 12px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
    overflow: hidden !important;
    z-index: 9999;
}

.select2-results__option {
    padding: 8px 12px !important;
    font-size: 13px !important;
}

.select2-results__option--highlighted[aria-selected] {
    background-color: var(--topbar-color, var(--theme-primary, #4f46e5)) !important;
    color: #ffffff !important;
}

.select2-customer-option {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.select2-customer-title {
    font-weight: 600;
    color: #1e293b;
}

.select2-results__option--highlighted .select2-customer-title {
    color: #ffffff;
}

.select2-customer-sub {
    font-size: 11.5px;
    color: #64748b;
}

.select2-results__option--highlighted .select2-customer-sub {
    color: rgba(255, 255, 255, 0.85);
}

/* Gönder Butonu */
.sms-btn-submit {
    background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
    border: none;
    color: #ffffff;
    font-size: 14.5px;
    font-weight: 600;
    padding: 12px 28px;
    border-radius: 12px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.sms-btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.45);
    background: linear-gradient(135deg, #4338ca 0%, #312e81 100%);
    color: #ffffff;
}

.sms-btn-submit:disabled {
    background: #cbd5e1;
    color: #64748b;
    box-shadow: none;
    cursor: not-allowed;
    transform: none;
}

/* Toast/Alert Mesajları */
.sms-alert {
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    font-size: 14px;
    line-height: 1.5;
}

.sms-alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.sms-alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.sms-alert-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
}

.sms-alert-icon {
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 2px;
}

/* Dark Mode Uyumları */
.dark-mode .sms-hero-card,
.dark-mode .sms-main-card {
    background: #1e293b;
    border-color: #334155;
}

.dark-mode .sms-card-header {
    background: #0f172a;
    border-color: #334155;
}

.dark-mode .sms-hero-text h4,
.dark-mode .sms-card-title,
.dark-mode .sms-field-label,
.dark-mode .sms-sender-item strong {
    color: #f1f5f9;
}

.dark-mode .sms-hero-text p,
.dark-mode .sms-help-text,
.dark-mode .sms-sender-item {
    color: #94a3b8;
}

.dark-mode .sms-sender-infobar {
    background: #0f172a;
    border-color: #334155;
}

.dark-mode .sms-textarea {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

.dark-mode .sms-textarea:disabled {
    background: #1e293b;
    border-color: #334155;
    color: #64748b;
}

.dark-mode .sms-btn-tool {
    background: #334155;
    border-color: #475569;
    color: #cbd5e1;
}

.dark-mode .sms-btn-tool:hover {
    background: #475569;
    color: #ffffff;
}

.dark-mode .select2-container--default .select2-selection--multiple {
    background-color: #0f172a !important;
    border-color: #334155 !important;
}

.dark-mode .select2-dropdown {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}

.dark-mode .select2-customer-title {
    color: #f1f5f9;
}

.dark-mode .select2-customer-sub {
    color: #94a3b8;
}
</style>

<div class="sms-page-container">

    <!-- Hero Header Banner -->
    <div class="sms-hero-card">
        <div class="sms-hero-info">
            <div class="sms-hero-icon">
                <i class="fa fa-paper-plane-o"></i>
            </div>
            <div class="sms-hero-text">
                <h4>SMS Gönderimi</h4>
                <p>Müşterilerinize doğrudan NetGSM altyapısı üzerinden toplu veya bireysel SMS gönderin.</p>
            </div>
        </div>
        <div>
            <?php if ($isConfigured): ?>
                <div class="sms-status-pill pill-active">
                    <span class="sms-status-dot"></span>
                    <span>NetGSM Servisi Aktif (Başlık: <strong><?php echo htmlspecialchars($smsTitle, ENT_QUOTES, 'UTF-8'); ?></strong>)</span>
                </div>
            <?php else: ?>
                <div class="sms-status-pill pill-inactive">
                    <span class="sms-status-dot"></span>
                    <span>Entegrasyon Yapılandırılmamış</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bildirim / Alert Mesajı -->
    <?php if ($feedback): ?>
        <div class="sms-alert sms-alert-<?php echo $feedback['type']; ?>">
            <div class="sms-alert-icon">
                <?php if ($feedback['type'] === 'success'): ?>
                    <i class="fa fa-check-circle"></i>
                <?php elseif ($feedback['type'] === 'warning'): ?>
                    <i class="fa fa-exclamation-triangle"></i>
                <?php else: ?>
                    <i class="fa fa-times-circle"></i>
                <?php endif; ?>
            </div>
            <div>
                <strong><?php echo htmlspecialchars($feedback['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <div><?php echo htmlspecialchars($feedback['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ENTEGRASYON EKSİK/PASİF UYARI KARTI -->
    <?php if (!$isConfigured): ?>
        <div class="sms-warning-card">
            <div class="sms-warning-header">
                <div class="sms-warning-icon">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div class="sms-warning-content">
                    <h5>SMS Entegrasyon Bilgileri Eksik veya Servis Pasif!</h5>
                    <p>
                        Sistem üzerinden SMS gönderebilmeniz için <strong>Panel Ayarları</strong> sayfasındaki <strong>NetGSM SMS Bildirim Entegrasyonu</strong> alanından gerekli API bilgilerinin (Kullanıcı Adı, Başlık, API Şifresi) tanımlanması ve servisin aktif edilmesi gerekmektedir. Bilgiler tamamlanmadan SMS gönderimi yapılamamaktadır.
                    </p>
                </div>
            </div>

            <div class="sms-checklist-grid">
                <div class="sms-check-item">
                    <span class="sms-check-label"><i class="fa fa-toggle-on mr-1"></i> SMS Servis Durumu:</span>
                    <?php if ($smsActive): ?>
                        <span class="sms-check-badge badge-ok"><i class="fa fa-check"></i> Aktif</span>
                    <?php else: ?>
                        <span class="sms-check-badge badge-missing"><i class="fa fa-times"></i> Pasif</span>
                    <?php endif; ?>
                </div>

                <div class="sms-check-item">
                    <span class="sms-check-label"><i class="fa fa-user mr-1"></i> Kullanıcı Adı / Abone:</span>
                    <?php if (!empty($smsUsername)): ?>
                        <span class="sms-check-badge badge-ok"><i class="fa fa-check"></i> Tanımlı</span>
                    <?php else: ?>
                        <span class="sms-check-badge badge-missing"><i class="fa fa-times"></i> Eksik</span>
                    <?php endif; ?>
                </div>

                <div class="sms-check-item">
                    <span class="sms-check-label"><i class="fa fa-tag mr-1"></i> Onaylı Başlık:</span>
                    <?php if (!empty($smsTitle)): ?>
                        <span class="sms-check-badge badge-ok"><i class="fa fa-check"></i> <?php echo htmlspecialchars($smsTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php else: ?>
                        <span class="sms-check-badge badge-missing"><i class="fa fa-times"></i> Eksik</span>
                    <?php endif; ?>
                </div>

                <div class="sms-check-item">
                    <span class="sms-check-label"><i class="fa fa-key mr-1"></i> API Parolası / Şifresi:</span>
                    <?php if (!empty($smsPass)): ?>
                        <span class="sms-check-badge badge-ok"><i class="fa fa-check"></i> Tanımlı</span>
                    <?php else: ?>
                        <span class="sms-check-badge badge-missing"><i class="fa fa-times"></i> Eksik</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($canManageSettings): ?>
                <div class="sms-warning-actions">
                    <a href="index.php?p=settings" class="btn btn-warning btn-sm font-weight-bold" style="border-radius: 8px;">
                        <i class="fa fa-cog mr-1"></i> Panel Ayarlarına Git ve NetGSM Bilgilerini Gir
                    </a>
                    <span class="text-muted font-12">
                        <i class="fa fa-info-circle mr-1"></i> Ayarları tamamladıktan sonra sayfayı yenileyerek SMS gönderebilirsiniz.
                    </span>
                </div>
            <?php else: ?>
                <div class="sms-warning-actions">
                    <span class="text-muted font-13">
                        <i class="fa fa-lock mr-1"></i> SMS entegrasyon ayarlarını yapılandırmak için lütfen sistem yöneticiniz ile iletişime geçiniz.
                    </span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- SMS GÖNDERME FORMU -->
    <div class="sms-main-card">
        <div class="sms-card-header">
            <h5 class="sms-card-title">
                <i class="fa fa-pencil-square-o text-primary"></i> Yeni SMS Mesajı Oluştur
            </h5>
            <div>
                <span class="badge badge-light border text-muted font-12">
                    <i class="fa fa-users mr-1"></i> Toplam <?php echo count($customerList); ?> Müşteri GSM Kayıtlı
                </span>
            </div>
        </div>

        <div class="sms-card-body">
            <?php if ($isConfigured): ?>
                <!-- Gönderici Bilgi Çubuğu -->
                <div class="sms-sender-infobar">
                    <div class="sms-sender-item">
                        <i class="fa fa-id-card-o"></i>
                        <span>Gönderici Başlığı: <strong><?php echo htmlspecialchars($smsTitle, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                    </div>
                    <div class="sms-sender-item">
                        <i class="fa fa-user-circle-o"></i>
                        <span>Abone No: <strong><?php echo htmlspecialchars($smsUsername, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                    </div>
                    <div class="sms-sender-item">
                        <i class="fa fa-server"></i>
                        <span>Entegrasyon: <strong>NetGSM SMS API (SOAP/REST)</strong></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?p=send-sms&post=true" id="smsSendForm">
                <div class="row">
                    <!-- Mesaj İçeriği -->
                    <div class="col-lg-7 col-md-12">
                        <div class="sms-field-group">
                            <label class="sms-field-label" for="smsMessageText">
                                <span>
                                    <i class="fa fa-commenting-o mr-1 text-primary"></i> Mesaj İçeriği <span class="required-mark">*</span>
                                </span>
                                <span class="sms-counter-badge" id="smsCounterBadge">
                                    0 / 160 Karakter • 1 SMS
                                </span>
                            </label>
                            <textarea 
                                name="message" 
                                id="smsMessageText" 
                                class="sms-textarea" 
                                placeholder="Müşterilerinize iletmek istediğiniz SMS mesajını buraya yazınız..." 
                                <?php echo !$isConfigured ? 'disabled' : ''; ?>
                                required></textarea>
                            <div class="sms-help-text">
                                <i class="fa fa-info-circle text-primary"></i>
                                <span>160 karaktere kadar 1 SMS, 161-306 karakter 2 SMS, 307-459 karakter 3 SMS olarak hesaplanır.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Müşteri Seçimi -->
                    <div class="col-lg-5 col-md-12">
                        <div class="sms-field-group">
                            <div class="sms-field-label">
                                <span>
                                    <i class="fa fa-address-book-o mr-1 text-primary"></i> Alıcı Müşteriler <span class="required-mark">*</span>
                                </span>
                                <span class="badge badge-primary-soft font-12" id="selectedCountBadge">
                                    0 Seçildi
                                </span>
                            </div>

                            <div class="sms-select-toolbar">
                                <button type="button" class="sms-btn-tool" id="btnSelectAllCustomers" <?php echo !$isConfigured ? 'disabled' : ''; ?>>
                                    <i class="fa fa-check-square-o"></i> Tümünü Seç
                                </button>
                                <button type="button" class="sms-btn-tool" id="btnClearCustomers" <?php echo !$isConfigured ? 'disabled' : ''; ?>>
                                    <i class="fa fa-trash-o"></i> Seçimi Temizle
                                </button>
                            </div>

                            <select 
                                name="customers[]" 
                                id="customerSelectBox" 
                                class="form-control" 
                                multiple="multiple" 
                                style="width: 100%;" 
                                data-placeholder="Listeden SMS gönderilecek müşterileri seçiniz..."
                                <?php echo !$isConfigured ? 'disabled' : ''; ?>
                                required>
                                <?php foreach ($customerList as $cust): ?>
                                    <?php 
                                        $phoneVal = !empty($cust['gsm']) ? $cust['gsm'] : $cust['gsm2'];
                                        $phoneFormatted = htmlspecialchars($phoneVal, ENT_QUOTES, 'UTF-8');
                                        $companyFormatted = htmlspecialchars($cust['company'] ?? 'Bilinmeyen Firma', ENT_QUOTES, 'UTF-8');
                                        $yetkiliFormatted = htmlspecialchars($cust['yetkili'] ?? '', ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <option 
                                        value="<?php echo $phoneFormatted; ?>"
                                        data-company="<?php echo $companyFormatted; ?>"
                                        data-yetkili="<?php echo $yetkiliFormatted; ?>"
                                        data-gsm="<?php echo $phoneFormatted; ?>">
                                        <?php echo $companyFormatted . (!empty($yetkiliFormatted) ? " ({$yetkiliFormatted})" : "") . " - {$phoneFormatted}"; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="sms-help-text">
                                <i class="fa fa-search text-primary"></i>
                                <span>Firma adı, yetkili veya telefon numarasına göre anlık arama yapabilirsiniz.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alt İşlem Çubuğu -->
                <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-3 flex-wrap gap-2">
                    <div class="text-muted font-13">
                        <?php if ($isConfigured): ?>
                            <i class="fa fa-shield text-success mr-1"></i> Güvenli NetGSM SMS Gönderim Protokolü Aktif.
                        <?php else: ?>
                            <i class="fa fa-lock text-danger mr-1"></i> SMS gönderimi için entegrasyon yapılandırması bekleniyor.
                        <?php endif; ?>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            id="btnSubmitSms" 
                            class="sms-btn-submit" 
                            <?php echo !$isConfigured ? 'disabled' : ''; ?>>
                            <i class="fa fa-send"></i>
                            <span>SMS Mesajını Gönder</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
$(document).ready(function() {
    // Select2 Başlat
    var $customerSelect = $('#customerSelectBox');

    function formatCustomerOption(state) {
        if (!state.id) {
            return state.text;
        }
        var element = state.element;
        if (!element) {
            return state.text;
        }
        var company = $(element).data('company') || state.text;
        var yetkili = $(element).data('yetkili') || '';
        var gsm = $(element).data('gsm') || '';

        var $html = $(
            '<div class="select2-customer-option">' +
                '<span class="select2-customer-title">' + company + '</span>' +
                '<span class="select2-customer-sub">' + (yetkili ? '<i class="fa fa-user-o mr-1"></i>' + yetkili + ' &bull; ' : '') + '<i class="fa fa-phone mr-1"></i>' + gsm + '</span>' +
            '</div>'
        );
        return $html;
    }

    $customerSelect.select2({
        placeholder: "Listeden SMS gönderilecek müşterileri seçiniz...",
        allowClear: true,
        templateResult: formatCustomerOption,
        templateSelection: function(state) {
            if (!state.id) return state.text;
            var element = state.element;
            var company = $(element).data('company') || state.text;
            var gsm = $(element).data('gsm') || '';
            return company + ' (' + gsm + ')';
        }
    });

    // Seçilen müşteri sayısını güncelle
    function updateSelectedCustomerCount() {
        var selectedCount = ($customerSelect.val() || []).length;
        $('#selectedCountBadge').text(selectedCount + ' Müşteri Seçildi');
    }

    $customerSelect.on('change', updateSelectedCustomerCount);
    updateSelectedCustomerCount();

    // Tümünü Seç Butonu
    $('#btnSelectAllCustomers').on('click', function() {
        var allValues = [];
        $customerSelect.find('option').each(function() {
            allValues.push($(this).val());
        });
        $customerSelect.val(allValues).trigger('change');
    });

    // Seçimi Temizle Butonu
    $('#btnClearCustomers').on('click', function() {
        $customerSelect.val(null).trigger('change');
    });

    // Karakter Sayacı ve SMS Parça Hesabı
    var $msgText = $('#smsMessageText');
    var $counterBadge = $('#smsCounterBadge');

    function updateSmsCounter() {
        var text = $msgText.val() || '';
        var charCount = text.length;
        var smsParts = 1;

        if (charCount > 160) {
            smsParts = Math.ceil(charCount / 153);
        } else if (charCount === 0) {
            smsParts = 1;
        }

        var label = charCount + ' / 160 Karakter • ' + smsParts + ' SMS';
        $counterBadge.text(label);

        if (smsParts > 1) {
            $counterBadge.addClass('limit-warning');
        } else {
            $counterBadge.removeClass('limit-warning');
        }
    }

    $msgText.on('input keyup paste change', updateSmsCounter);
    updateSmsCounter();

    // Form Gönderim İşlemi ve Loading Durumu
    $('#smsSendForm').on('submit', function(e) {
        var selectedCount = ($customerSelect.val() || []).length;
        var msg = $.trim($msgText.val());

        if (selectedCount === 0) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') {
                toastr.warning('Lütfen en az bir alıcı müşteri seçiniz.', 'Eksik Seçim');
            } else {
                alert('Lütfen en az bir alıcı müşteri seçiniz.');
            }
            return false;
        }

        if (msg === '') {
            e.preventDefault();
            if (typeof toastr !== 'undefined') {
                toastr.warning('Lütfen mesaj içeriğini yazınız.', 'Boş Mesaj');
            } else {
                alert('Lütfen mesaj içeriğini yazınız.');
            }
            return false;
        }

        var $btn = $('#btnSubmitSms');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <span>SMS Gönderiliyor...</span>');
        return true;
    });
});
</script>