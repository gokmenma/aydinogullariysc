<?php

use App\Model\SettingsModel;
use App\Helper\MaintenanceMode;

permcontrol("panelsettings");

$settingsModel = new SettingsModel();
$canManageMaintenance = MaintenanceMode::hasAccessPermission($ac);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST["title"] ?? '');
    $url           = trim($_POST["url"] ?? '');
    $company       = trim($_POST["company"] ?? '');
    $address       = trim($_POST["caddress"] ?? '');
    $city          = trim($_POST["city"] ?? '');
    $gsm1          = trim($_POST["gsm1"] ?? '');
    $gsm2          = trim($_POST["gsm2"] ?? '');
    $mail_host     = trim($_POST["mail_host"] ?? '');
    $mail_username = trim($_POST["mail_username"] ?? '');
    $mail_password = $_POST["mail_password"] ?? '';
    $mail_port     = trim($_POST["mail_port"] ?? '');
    $mail_admin    = trim($_POST["mail_admin"] ?? '');
    $sms_active    = (isset($_POST["sms_active"]) && $_POST["sms_active"] === 'on') ? 'on' : '';
    $sms_username  = trim($_POST["sms_username"] ?? '');
    $sms_pass      = $_POST["sms_pass"] ?? '';
    $sms_title     = trim($_POST["sms_title"] ?? '');
    $maintenanceMode = (isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] === '1') ? '1' : '0';
    $maintenanceAnnouncement = trim($_POST['maintenance_announcement'] ?? '');
    $maintenanceStartInput = trim($_POST['maintenance_start_at'] ?? '');
    $maintenanceEndInput = trim($_POST['maintenance_end_at'] ?? '');
    $maintenanceStartAt = '';
    $maintenanceEndAt = '';

    if ($canManageMaintenance && ($maintenanceStartInput !== '' || $maintenanceEndInput !== '')) {
        $startDate = DateTime::createFromFormat('Y-m-d\TH:i', $maintenanceStartInput);
        $endDate = DateTime::createFromFormat('Y-m-d\TH:i', $maintenanceEndInput);
        $validStart = $startDate && $startDate->format('Y-m-d\TH:i') === $maintenanceStartInput;
        $validEnd = $endDate && $endDate->format('Y-m-d\TH:i') === $maintenanceEndInput;

        if (!$validStart || !$validEnd || $endDate <= $startDate) {
            header("Location: index.php?p=settings&st=maintenance_date_error");
            exit;
        }

        $maintenanceStartAt = $startDate->format('Y-m-d H:i:s');
        $maintenanceEndAt = $endDate->format('Y-m-d H:i:s');
    }

    if (mb_strlen($maintenanceAnnouncement, 'UTF-8') > 500) {
        header("Location: index.php?p=settings&st=maintenance_message_error");
        exit;
    }

    if (empty($title) || empty($url) || empty($company) || empty($address) || empty($city) || empty($gsm1)) {
        header("Location: index.php?p=settings&st=empties");
        exit;
    }

    $updateData = [
        'site_title'      => $title,
        'panel_url'       => $url,
        'company_name'    => $company,
        'company_address' => $address,
        'company_city'    => $city,
        'company_phone1'  => $gsm1,
        'company_phone2'  => $gsm2,
        'mail_host'       => $mail_host,
        'mail_username'   => $mail_username,
        'mail_password'   => $mail_password,
        'mail_port'       => $mail_port,
        'admin_mail'      => $mail_admin,
        'sms_active'      => $sms_active,
        'sms_username'    => $sms_username,
        'sms_pass'        => $sms_pass,
        'sms_title'       => $sms_title
    ];

    // Bakım ayarı yalnızca özel yetkiye sahip kullanıcı tarafından değiştirilebilir.
    if ($canManageMaintenance) {
        $updateData['maintenance_mode'] = $maintenanceMode;
        $updateData['maintenance_announcement'] = $maintenanceAnnouncement;
        $updateData['maintenance_start_at'] = $maintenanceStartAt;
        $updateData['maintenance_end_at'] = $maintenanceEndAt;
    }

    $saved = $settingsModel->updateSettings($updateData);

    // Logo yükleme kontrolü
    if (isset($_FILES["logos"]) && !empty($_FILES["logos"]["name"])) {
        $logoResult = $settingsModel->updateLogo($_FILES["logos"]);
        if (!$logoResult['success']) {
            header("Location: index.php?p=settings&st=logo_error&msg=" . urlencode($logoResult['message']));
            exit;
        }
    }

    if ($saved) {
        if (function_exists('audit_log')) {
            audit_log('update', 'settings', 'Panel ve sistem ayarları güncellendi.', 'settings', 1, [
                'site_title'   => $title,
                'company_name' => $company,
                'panel_url'    => $url,
                'maintenance_mode' => $canManageMaintenance ? $maintenanceMode : null,
                'maintenance_start_at' => $canManageMaintenance ? $maintenanceStartAt : null,
                'maintenance_end_at' => $canManageMaintenance ? $maintenanceEndAt : null
            ]);
        }
        header("Location: index.php?p=settings&st=newsuccess");
        exit;
    } else {
        header("Location: index.php?p=settings&st=error");
        exit;
    }
}

// Güncel ayarları çek
$currentSettings = $settingsModel->getAllSettings();

$siteTitle    = $currentSettings['site_title'] ?? '';
$panelUrl     = $currentSettings['panel_url'] ?? '';
$companyName  = $currentSettings['company_name'] ?? '';
$companyAddr  = $currentSettings['company_address'] ?? '';
$companyCity  = $currentSettings['company_city'] ?? '';
$phone1       = $currentSettings['company_phone1'] ?? '';
$phone2       = $currentSettings['company_phone2'] ?? '';
$mailHost     = $currentSettings['mail_host'] ?? '';
$mailUsername = $currentSettings['mail_username'] ?? '';
$mailPassword = $currentSettings['mail_password'] ?? '';
$mailPort     = $currentSettings['mail_port'] ?? '';
$adminMail    = $currentSettings['admin_mail'] ?? '';
$smsActive    = ($currentSettings['sms_active'] ?? '') === 'on';
$smsUsername  = $currentSettings['sms_username'] ?? '';
$smsPass      = $currentSettings['sms_pass'] ?? '';
$smsTitle     = $currentSettings['sms_title'] ?? '';
$currentLogo  = $currentSettings['logo'] ?? 'src/images/logo.png';
$maintenanceModeEnabled = ($currentSettings['maintenance_mode'] ?? '0') === '1';
$maintenanceAnnouncement = $currentSettings['maintenance_announcement'] ?? '';
$maintenanceStartAt = !empty($currentSettings['maintenance_start_at'])
    ? date('Y-m-d\TH:i', strtotime($currentSettings['maintenance_start_at']))
    : '';
$maintenanceEndAt = !empty($currentSettings['maintenance_end_at'])
    ? date('Y-m-d\TH:i', strtotime($currentSettings['maintenance_end_at']))
    : '';

// Bildirim Mesajları
if (@$_GET["st"] == "newsuccess") {
    showAlert("success", "Panel ayarları başarıyla kaydedildi!");
} elseif (@$_GET["st"] == "empties") {
    showAlert("alert", "Lütfen zorunlu alanları (*) boş bırakmayınız.");
} elseif (@$_GET["st"] == "logo_error") {
    $msg = !empty($_GET["msg"]) ? htmlspecialchars($_GET["msg"], ENT_QUOTES, 'UTF-8') : 'Logo yüklenirken bir hata oluştu.';
    showAlert("alert", $msg);
} elseif (@$_GET["st"] == "error") {
    showAlert("alert", "Ayarlar kaydedilirken bir hata oluştu. Lütfen tekrar deneyiniz.");
} elseif (@$_GET["st"] == "maintenance_date_error") {
    showAlert("alert", "Planlı bakım başlangıç ve bitiş tarihlerini kontrol edin. Bitiş tarihi başlangıçtan sonra olmalıdır.");
} elseif (@$_GET["st"] == "maintenance_message_error") {
    showAlert("alert", "Bakım duyurusu en fazla 500 karakter olabilir.");
}
?>

<style>
/* ==========================================
   PREMIUM SETTINGS PAGE STYLING
   ========================================== */
.settings-page-wrapper {
    width: 100%;
    margin-bottom: 40px;
}

/* Password reveal toggle button */
.input-group-password {
    position: relative;
    display: flex;
    align-items: stretch;
    width: 100%;
}
.input-group-password .form-control {
    padding-right: 42px !important;
    border-top-right-radius: 10px !important;
    border-bottom-right-radius: 10px !important;
}
.input-group-password .btn-pwd-toggle {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #64748b;
    padding: 6px 10px;
    cursor: pointer;
    font-size: 15px;
    z-index: 5;
    border-radius: 6px;
    transition: color 0.2s ease, background-color 0.2s ease;
}
.input-group-password .btn-pwd-toggle:hover {
    color: #1e293b;
    background: rgba(0, 0, 0, 0.05);
}

/* SMS Toggle Switch Card Styling */
.switch-box-wrapper {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    transition: all 0.25s ease;
}
.switch-box-wrapper:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}
.switch-box-label {
    display: flex;
    flex-direction: column;
}
.switch-box-label .title {
    font-size: 14.5px;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.switch-box-label .desc {
    font-size: 12.5px;
    color: #64748b;
    margin-top: 2px;
}

/* iOS Style Switch */
.custom-switch-ios {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
    flex-shrink: 0;
}
.custom-switch-ios input {
    opacity: 0;
    width: 0;
    height: 0;
}
.custom-switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 34px;
}
.custom-switch-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.custom-switch-ios input:checked + .custom-switch-slider {
    background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
}
.custom-switch-ios input:checked + .custom-switch-slider:before {
    transform: translateX(24px);
}

/* SMS Section Collapse Container */
#smsFieldsContainer {
    transition: all 0.35s ease;
    overflow: hidden;
}

/* Logo Upload Area */
.logo-management-grid {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 24px;
    align-items: start;
}
@media (max-width: 768px) {
    .logo-management-grid {
        grid-template-columns: 1fr;
    }
}
.logo-preview-box {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 160px;
    position: relative;
    transition: border-color 0.2s ease;
}
.logo-preview-box img {
    max-width: 100%;
    max-height: 100px;
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.08));
    transition: transform 0.2s ease;
}
.logo-preview-box img:hover {
    transform: scale(1.05);
}
.logo-preview-tag {
    position: absolute;
    bottom: 8px;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    background: rgba(255, 255, 255, 0.85);
    padding: 2px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}
.logo-upload-dropzone {
    border: 2px dashed #93c5fd;
    background: #eff6ff;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.logo-upload-dropzone:hover {
    background: #dbeafe;
    border-color: #3b82f6;
}
.logo-upload-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #ffffff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 10px;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15);
}

/* Sticky Action Bar */
.settings-sticky-bar {
    position: sticky;
    bottom: 15px;
    z-index: 99;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(12px);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 24px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 25px;
    animation: fadeInUp 0.4s ease;
}

/* ==========================================
   DARK MODE OVERRIDES
   ========================================== */
.dark-mode .input-group-password .btn-pwd-toggle {
    color: #94a3b8;
}
.dark-mode .input-group-password .btn-pwd-toggle:hover {
    color: #f1f5f9;
    background: rgba(255, 255, 255, 0.1);
}
.dark-mode .switch-box-wrapper {
    background: #151c27;
    border-color: #334155;
}
.dark-mode .switch-box-wrapper:hover {
    background: #1e293b;
    border-color: #475569;
}
.dark-mode .switch-box-label .title {
    color: #f1f5f9;
}
.dark-mode .switch-box-label .desc {
    color: #94a3b8;
}
.dark-mode .logo-preview-box {
    background: #151c27;
    border-color: #334155;
}
.dark-mode .logo-preview-tag {
    background: #1e293b;
    color: #cbd5e1;
    border-color: #334155;
}
.dark-mode .logo-upload-dropzone {
    background: rgba(59, 130, 246, 0.08);
    border-color: rgba(59, 130, 246, 0.3);
}
.dark-mode .logo-upload-dropzone:hover {
    background: rgba(59, 130, 246, 0.15);
    border-color: #3b82f6;
}
.dark-mode .logo-upload-icon {
    background: #1e293b;
    color: #60a5fa;
}
.dark-mode .settings-sticky-bar {
    background: rgba(30, 41, 59, 0.92);
    border-color: #334155;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}
</style>

<div class="settings-page-wrapper">
    <form enctype="multipart/form-data" id="settingsForm" action="index.php?p=settings" method="POST">

        <!-- ==========================================
             PREMIUM HEADER CARD
             ========================================== -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-sliders"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo htmlspecialchars($pdat["p_title"] ?? 'Panel & Sistem Ayarları', ENT_QUOTES, 'UTF-8'); ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-cogs"></i> Sistem Konfigürasyonu
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <button type="submit" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Değişiklikleri Kaydet
                    </button>
                </div>
            </div>
        </div>

        <!-- ==========================================
             KART 1: GENEL & PANEL YAPILANDIRMASI
             ========================================== -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-globe"></i>
                </div>
                <div>
                    <h5>Genel Panel Yapılandırması</h5>
                    <p>Sistem başlığı, erişim URL adresi ve temel panel parametreleri</p>
                </div>
            </div>

            <div class="form-grid">
                <!-- Panel Başlığı -->
                <div class="form-field">
                    <label for="site_title">
                        <span class="required-dot"></span> Panel Başlığı (Site Title):
                    </label>
                    <input required type="text" name="title" id="site_title" class="form-control"
                        value="<?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: Aydınoğulları YSC">
                    <small class="text-muted font-12">Tarayıcı sekmesinde ve sistem başlıklarında görünen isim.</small>
                </div>

                <!-- Panel URL -->
                <div class="form-field">
                    <label for="panel_url">
                        <span class="required-dot"></span> Panel URL Adresi:
                    </label>
                    <input required type="url" name="url" id="panel_url" class="form-control"
                        value="<?php echo htmlspecialchars($panelUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: https://aydinogullari.com">
                    <small class="text-muted font-12">Sistem linkleri, e-posta şablonları ve bildirimlerdeki ana URL.</small>
                </div>
            </div>
        </div>

        <!-- ==========================================
             KART 2: ŞİRKET & İLETİŞİM BİLGİLERİ
             ========================================== -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-green">
                    <i class="fa fa-building-o"></i>
                </div>
                <div>
                    <h5>Kurumsal Firma & İletişim Bilgileri</h5>
                    <p>Teklifler, servis formları ve rapor çıktılarında yer alan resmi firma bilgileri</p>
                </div>
            </div>

            <div class="form-grid">
                <!-- Şirket İsmi -->
                <div class="form-field">
                    <label for="company_name">
                        <span class="required-dot"></span> Şirket Tam Ünvanı:
                    </label>
                    <input required type="text" name="company" id="company_name" class="form-control"
                        value="<?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: Aydınoğulları Yangın Söndürme San. ve Tic. Ltd. Şti.">
                </div>

                <!-- Şehir / Ülke -->
                <div class="form-field">
                    <label for="company_city">
                        <span class="required-dot"></span> Şehir / Ülke:
                    </label>
                    <input required type="text" name="city" id="company_city" class="form-control"
                        value="<?php echo htmlspecialchars($companyCity, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: BURSA">
                </div>

                <!-- Şirket Telefon 1 -->
                <div class="form-field">
                    <label for="company_phone1">
                        <span class="required-dot"></span> Şirket Telefon 1 (Ana Hat):
                    </label>
                    <input required type="text" name="gsm1" id="company_phone1" class="form-control"
                        value="<?php echo htmlspecialchars($phone1, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: 02244436021">
                </div>

                <!-- Şirket Telefon 2 -->
                <div class="form-field">
                    <label for="company_phone2">Şirket Telefon 2 (Opsiyonel):</label>
                    <input type="text" name="gsm2" id="company_phone2" class="form-control"
                        value="<?php echo htmlspecialchars($phone2, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: 02244436022">
                </div>

                <!-- Şirket Adres -->
                <div class="form-field full-width">
                    <label for="company_address">
                        <span class="required-dot"></span> Şirket Açık Adresi:
                    </label>
                    <textarea required name="caddress" id="company_address" class="form-control" rows="2"
                        placeholder="Firma açık adresini giriniz"><?php echo htmlspecialchars($companyAddr, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- ==========================================
             KART 3: E-POSTA & SMTP YAPILANDIRMASI
             ========================================== -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <div class="card-icon card-icon-purple">
                        <i class="fa fa-envelope-o"></i>
                    </div>
                    <div>
                        <h5>E-Posta & SMTP Sunucu Yapılandırması</h5>
                        <p>Sistem üzerinden gönderilen teklif, servis ve bildirim mailleri için SMTP ayarları</p>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm font-12 font-weight-bold" id="btnOpenTestSmtpModal" style="border-width: 1.5px;">
                    <i class="fa fa-paper-plane mr-1"></i> E-Posta Gönderimini Test Et
                </button>
            </div>

            <div class="form-grid">
                <!-- Mail Sunucusu (Host) -->
                <div class="form-field">
                    <label for="mail_host">
                        <span class="required-dot"></span> Mail Sunucusu (Host):
                    </label>
                    <input required type="text" name="mail_host" id="mail_host" class="form-control"
                        value="<?php echo htmlspecialchars($mailHost, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: mail.alanadiniz.com veya mt-solar.guzelhosting.com">
                </div>

                <!-- Mail PORT -->
                <div class="form-field">
                    <label for="mail_port">
                        <span class="required-dot"></span> SMTP Port Numarası:
                    </label>
                    <input required type="text" name="mail_port" id="mail_port" class="form-control"
                        value="<?php echo htmlspecialchars($mailPort, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: 587, 465 veya 25">
                </div>

                <!-- Gönderici Mail Kullanıcı Adı -->
                <div class="form-field">
                    <label for="mail_username">
                        <span class="required-dot"></span> Gönderici E-Posta Kullanıcı Adı:
                    </label>
                    <input required type="email" name="mail_username" id="mail_username" class="form-control"
                        value="<?php echo htmlspecialchars($mailUsername, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: teklif@aydinogullariysc.com">
                </div>

                <!-- Gönderici Mail Şifre -->
                <div class="form-field">
                    <label for="mail_password">
                        <span class="required-dot"></span> Gönderici E-Posta Şifresi:
                    </label>
                    <div class="input-group-password">
                        <input required type="password" name="mail_password" id="mail_password" class="form-control"
                            value="<?php echo htmlspecialchars($mailPassword, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="SMTP E-Posta parolasını giriniz">
                        <button type="button" class="btn-pwd-toggle" onclick="togglePasswordVisibility('mail_password', this)" title="Şifreyi Göster/Gizle">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Bilgilendirme Mail Alıcı -->
                <div class="form-field full-width">
                    <label for="mail_admin">
                        <span class="required-dot"></span> Sistem Bildirimleri Alıcı E-Postası:
                    </label>
                    <input required type="email" name="mail_admin" id="mail_admin" class="form-control"
                        value="<?php echo htmlspecialchars($adminMail, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Örn: info@aydinogullari.com">
                    <small class="text-muted font-12">Sistem alarmları, geri bildirimler ve yönetici bilgilendirmeleri bu adrese iletilir.</small>
                </div>
            </div>
        </div>

        <!-- ==========================================
             KART 4: SMS (NETGSM) ENTEGRASYONU
             ========================================== -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-orange">
                    <i class="fa fa-commenting-o"></i>
                </div>
                <div>
                    <h5>NetGSM SMS Bildirim Entegrasyonu</h5>
                    <p>Müşterilere ve personele otomatik SMS bildirimleri göndermek için API yapılandırması</p>
                </div>
            </div>

            <!-- SMS Aç/Kapa Switch Kartı -->
            <div class="switch-box-wrapper">
                <div class="switch-box-label">
                    <span class="title">
                        <i class="fa fa-paper-plane-o text-primary"></i> NetGSM SMS Servisini Etkinleştir
                    </span>
                    <span class="desc">Açık olduğunda sistem görev ve servis bildirimlerini otomatik SMS olarak iletir.</span>
                </div>
                <label class="custom-switch-ios">
                    <input type="checkbox" name="sms_active" id="sms_active" <?php echo $smsActive ? "checked" : ""; ?> onchange="handleSmsToggle(this)">
                    <span class="custom-switch-slider"></span>
                </label>
            </div>

            <!-- SMS Alanları (Dinamik Açılır / Kapanır) -->
            <div id="smsFieldsContainer" style="<?php echo $smsActive ? '' : 'display: none;'; ?>">
                <div class="form-grid">
                    <!-- NETGSM SMS Kullanıcı Adı -->
                    <div class="form-field">
                        <label for="sms_username">NetGSM Kullanıcı Adı / Abone No:</label>
                        <input type="text" name="sms_username" id="sms_username" class="form-control"
                            value="<?php echo htmlspecialchars($smsUsername, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Örn: 850XXXXXXX veya Kullanıcı Adı">
                    </div>

                    <!-- NETGSM SMS Başlığı -->
                    <div class="form-field">
                        <label for="sms_title">NetGSM Onaylı Gönderici Başlığı (Originator):</label>
                        <input type="text" name="sms_title" id="sms_title" class="form-control"
                            value="<?php echo htmlspecialchars($smsTitle, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Örn: AYDINOGULLARI">
                    </div>

                    <!-- NETGSM SMS Şifre -->
                    <div class="form-field full-width">
                        <label for="sms_pass">NetGSM API Parolası / Şifresi:</label>
                        <div class="input-group-password">
                            <input type="password" name="sms_pass" id="sms_pass" class="form-control"
                                value="<?php echo htmlspecialchars($smsPass, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="NetGSM API Şifrenizi giriniz">
                            <button type="button" class="btn-pwd-toggle" onclick="togglePasswordVisibility('sms_pass', this)" title="Şifreyi Göster/Gizle">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             KART 5: KURUMSAL LOGO & MARKA GÖRSELİ
             ========================================== -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-red">
                    <i class="fa fa-picture-o"></i>
                </div>
                <div>
                    <h5>Kurumsal Logo & Marka Görseli</h5>
                    <p>Giriş ekranında, sol menüde ve PDF çıktılarında kullanılan ana firma logosu</p>
                </div>
            </div>

            <div class="logo-management-grid">
                <!-- Mevcut Logo / Önizleme -->
                <div class="logo-preview-box" id="logoPreviewBox">
                    <img id="logoPreviewImg" src="<?php echo htmlspecialchars($currentLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="Firma Logosu" onerror="this.src='src/images/logo.png'">
                    <span class="logo-preview-tag" id="logoPreviewTag">Mevcut Logo</span>
                </div>

                <!-- Yeni Logo Yükleme Alanı -->
                <div>
                    <label for="logoInput" class="logo-upload-dropzone w-100 mb-2" id="dropZone">
                        <div class="logo-upload-icon">
                            <i class="fa fa-cloud-upload"></i>
                        </div>
                        <h6 class="weight-600 mb-1" style="color: #1e293b;">Yeni Logo Seçmek İçin Tıklayın veya Sürükleyin</h6>
                        <p class="font-12 text-muted mb-0">Desteklenen formatlar: PNG, JPG, JPEG, SVG, WEBP (Maks. 5MB)</p>
                        <input type="file" name="logos" id="logoInput" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" style="display: none;" onchange="previewLogo(this)">
                    </label>
                    <div id="selectedFileInfo" class="d-none mt-2 p-2 bg-light border-radius-8 font-13 text-muted d-flex align-items-center justify-content-between">
                        <span><i class="fa fa-file-image-o text-primary mr-1"></i> <strong id="selectedFileName"></strong></span>
                        <button type="button" class="btn btn-sm text-danger p-0" onclick="cancelLogoSelection()"><i class="fa fa-times"></i> İptal</button>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($canManageMaintenance): ?>
        <div class="form-card animate-fade-in" style="border-left: 4px solid #f59e0b;">
            <div class="form-card-header">
                <div class="card-icon card-icon-orange">
                    <i class="fa fa-wrench"></i>
                </div>
                <div>
                    <h5>Bakım Modu</h5>
                    <p>Bakım sırasında yalnızca “Bakım Modunda Sisteme Erişim” yetkisine sahip kullanıcılar sisteme girebilir.</p>
                </div>
            </div>
            <div class="switch-box-wrapper">
                <div class="switch-box-label">
                    <span class="title"><i class="fa fa-exclamation-triangle text-warning"></i> Sistemi Hemen Bakım Moduna Al</span>
                    <span class="desc">Etkinleştirildiğinde planlanan saati beklemeden diğer kullanıcıların erişimi durdurulur.</span>
                </div>
                <label class="custom-switch-ios">
                    <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" <?php echo $maintenanceModeEnabled ? 'checked' : ''; ?>>
                    <span class="custom-switch-slider"></span>
                </label>
            </div>
            <div class="row mt-3">
                <div class="col-md-6 mb-3">
                    <label for="maintenance_start_at" class="font-13 weight-600 text-secondary">Planlanan Başlangıç</label>
                    <input type="datetime-local" class="form-control" name="maintenance_start_at" id="maintenance_start_at" value="<?php echo htmlspecialchars($maintenanceStartAt, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="maintenance_end_at" class="font-13 weight-600 text-secondary">Tahmini Bitiş</label>
                    <input type="datetime-local" class="form-control" name="maintenance_end_at" id="maintenance_end_at" value="<?php echo htmlspecialchars($maintenanceEndAt, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-12">
                    <label for="maintenance_announcement" class="font-13 weight-600 text-secondary">Kullanıcılara Gösterilecek Duyuru</label>
                    <textarea class="form-control" name="maintenance_announcement" id="maintenance_announcement" rows="3" maxlength="500" placeholder="Örn: Planlı bakım sırasında sistem geçici olarak kullanılamayacaktır. Lütfen çalışmalarınızı önceden kaydedin."><?php echo htmlspecialchars($maintenanceAnnouncement, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <small class="text-muted font-12">Başlangıç ve bitiş birlikte girildiğinde duyuru hemen görünür; başlangıç anında bakım otomatik devreye girer ve bitişte kapanır.</small>
                </div>
            </div>
            <?php if ($maintenanceModeEnabled): ?>
                <div class="alert alert-warning mt-3 mb-0 font-13">
                    <i class="fa fa-info-circle mr-1"></i> Bakım modu şu anda açık. Kapatıp değişiklikleri kaydettiğinizde normal erişim geri gelir.
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ==========================================
             STICKY KAYDET BAR
             ========================================== -->
        <div class="settings-sticky-bar">
            <div class="d-flex align-items-center gap-2">
                <i class="fa fa-info-circle text-primary font-16"></i>
                <span class="font-13 text-muted d-none d-md-inline">Değişikliklerin geçerli olması için kaydetmeyi unutmayınız.</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="reset" class="btn btn-outline-secondary btn-sm px-3" onclick="resetFormState()">
                    <i class="fa fa-undo mr-1"></i> Sıfırla
                </button>
                <button type="submit" class="btn btn-success btn-sm px-4 font-14 weight-600" style="background: linear-gradient(135deg, #22c55e, #16a34a); border: none; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.35);">
                    <i class="fa fa-save mr-1"></i> Değişiklikleri Kaydet
                </button>
            </div>
        </div>

    </form>
</div>

<!-- ==========================================
     MODAL: SMTP E-POSTA TESTİ
     ========================================== -->
<div class="modal fade" id="testSmtpModal" tabindex="-1" role="dialog" aria-labelledby="testSmtpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.12);">
            <div class="modal-header d-flex align-items-center justify-content-between" style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; background: #fafafa;">
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 9px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 3px 8px rgba(2, 132, 199, 0.25);">
                        <i class="fa fa-paper-plane-o"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold mb-0" id="testSmtpModalLabel" style="font-size: 15px; color: #1e293b;">SMTP E-Posta Gönderim Testi</h5>
                        <small class="text-muted font-11">Sunucu bağlantısı ve test iletisi doğrulama</small>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat" style="font-size: 22px; color: #94a3b8; outline: none; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-4" style="background: #ffffff;">
                <div class="alert alert-info py-2 px-3 mb-3 border-0 d-flex align-items-center" style="border-radius: 8px; font-size: 12px; background: #f0f9ff; color: #0369a1; border-left: 3px solid #0284c7 !important;">
                    <i class="fa fa-info-circle mr-2 font-16"></i>
                    <span>Formda girili olan güncel sunucu bilgileriyle anlık test yapılır.</span>
                </div>

                <div class="form-group mb-3">
                    <label class="font-weight-bold text-dark font-12 mb-1" for="test_recipient_email">
                        Alıcı Test E-Posta Adresi <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light text-muted border-right-0" style="border-radius: 6px 0 0 6px;">
                                <i class="fa fa-envelope-o"></i>
                            </span>
                        </div>
                        <input type="email" class="form-control" id="test_recipient_email" placeholder="ornek@alanadiniz.com" style="border-radius: 0 6px 6px 0; font-size: 13px;">
                    </div>
                    <small class="text-muted font-11 mt-1 d-block">Test e-postasının gönderileceği gelen kutusu adresi.</small>
                </div>

                <div class="bg-light p-3 rounded mb-2 border" style="border-color: #e2e8f0 !important; font-size: 12px;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted font-weight-500">SMTP Host:</span>
                        <strong id="preview_smtp_host" class="text-dark">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted font-weight-500">SMTP Port:</span>
                        <strong id="preview_smtp_port" class="text-dark">-</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted font-weight-500">Gönderici Hesap:</span>
                        <strong id="preview_smtp_user" class="text-dark text-truncate" style="max-width: 250px;">-</strong>
                    </div>
                </div>

                <div id="smtpTestResultAlert" class="mt-3 d-none"></div>
            </div>

            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-dismiss="modal" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-4" id="btnExecuteSmtpTest">
                    <i class="fa fa-paper-plane mr-1"></i> <span id="btnExecuteSmtpTestText">Test Maili Gönder</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     PAGE SCRIPTS (UX ENHANCEMENTS)
     ========================================== -->
<script>
/**
 * Parola Göster/Gizle Toggle
 */
function togglePasswordVisibility(fieldId, btn) {
    var field = document.getElementById(fieldId);
    var icon = btn.querySelector('i');
    if (!field) return;

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

/**
 * SMS Aç / Kapa Dinamik Görünüm
 */
function handleSmsToggle(checkbox) {
    var container = document.getElementById('smsFieldsContainer');
    if (!container) return;

    if (checkbox.checked) {
        container.style.display = 'block';
        container.style.opacity = '0';
        setTimeout(function() {
            container.style.opacity = '1';
        }, 10);
    } else {
        container.style.opacity = '0';
        setTimeout(function() {
            container.style.display = 'none';
        }, 300);
    }
}

/**
 * Logo Canlı Önizleme
 */
var originalLogoSrc = "<?php echo htmlspecialchars($currentLogo, ENT_QUOTES, 'UTF-8'); ?>";

function previewLogo(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        
        // Boyut kontrolü (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert("Seçilen logo dosyası 5MB'dan büyük olamaz!");
            input.value = "";
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            var previewImg = document.getElementById('logoPreviewImg');
            var previewTag = document.getElementById('logoPreviewTag');
            var infoBox = document.getElementById('selectedFileInfo');
            var nameSpan = document.getElementById('selectedFileName');

            if (previewImg) previewImg.src = e.target.result;
            if (previewTag) {
                previewTag.textContent = 'Yeni Logo Önizleme';
                previewTag.style.color = '#2563eb';
                previewTag.style.borderColor = '#93c5fd';
            }
            if (infoBox && nameSpan) {
                nameSpan.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                infoBox.classList.remove('d-none');
            }
        };
        reader.readAsDataURL(file);
    }
}

function cancelLogoSelection() {
    var input = document.getElementById('logoInput');
    var previewImg = document.getElementById('logoPreviewImg');
    var previewTag = document.getElementById('logoPreviewTag');
    var infoBox = document.getElementById('selectedFileInfo');

    if (input) input.value = '';
    if (previewImg) previewImg.src = originalLogoSrc;
    if (previewTag) {
        previewTag.textContent = 'Mevcut Logo';
        previewTag.style.color = '#64748b';
        previewTag.style.borderColor = '#e2e8f0';
    }
    if (infoBox) infoBox.classList.add('d-none');
}

function resetFormState() {
    setTimeout(function() {
        cancelLogoSelection();
        var smsCheckbox = document.getElementById('sms_active');
        if (smsCheckbox) {
            handleSmsToggle(smsCheckbox);
        }
    }, 50);
}

// Drag and drop event listeners
document.addEventListener('DOMContentLoaded', function() {
    var dropZone = document.getElementById('dropZone');
    var logoInput = document.getElementById('logoInput');

    if (dropZone && logoInput) {
        ['dragenter', 'dragover'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#2563eb';
                dropZone.style.background = '#dbeafe';
            }, false);
        });

        ['dragleave', 'drop'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#93c5fd';
                dropZone.style.background = '#eff6ff';
            }, false);
        });

        dropZone.addEventListener('drop', function(e) {
            var dt = e.dataTransfer;
            var files = dt.files;
            if (files && files.length) {
                logoInput.files = files;
                previewLogo(logoInput);
            }
        }, false);
    }

    // SMTP Test Modal Açılışı
    var btnOpenTestSmtp = document.getElementById('btnOpenTestSmtpModal');
    if (btnOpenTestSmtp) {
        btnOpenTestSmtp.addEventListener('click', function(e) {
            e.preventDefault();
            
            var host = (document.getElementById('mail_host') ? document.getElementById('mail_host').value.trim() : '') || '-';
            var port = (document.getElementById('mail_port') ? document.getElementById('mail_port').value.trim() : '') || '-';
            var user = (document.getElementById('mail_username') ? document.getElementById('mail_username').value.trim() : '') || '-';
            var adminMail = (document.getElementById('mail_admin') ? document.getElementById('mail_admin').value.trim() : '');

            document.getElementById('preview_smtp_host').textContent = host;
            document.getElementById('preview_smtp_port').textContent = port;
            document.getElementById('preview_smtp_user').textContent = user;

            var recInput = document.getElementById('test_recipient_email');
            if (recInput && !recInput.value) {
                recInput.value = adminMail || (user !== '-' ? user : '');
            }

            var alertBox = document.getElementById('smtpTestResultAlert');
            if (alertBox) {
                alertBox.className = 'mt-3 d-none';
                alertBox.innerHTML = '';
            }

            $('#testSmtpModal').modal('show');
        });
    }

    // SMTP Test Gönderimi (AJAX)
    var btnExecuteSmtp = document.getElementById('btnExecuteSmtpTest');
    if (btnExecuteSmtp) {
        btnExecuteSmtp.addEventListener('click', function(e) {
            e.preventDefault();

            var host = document.getElementById('mail_host') ? document.getElementById('mail_host').value.trim() : '';
            var port = document.getElementById('mail_port') ? document.getElementById('mail_port').value.trim() : '';
            var user = document.getElementById('mail_username') ? document.getElementById('mail_username').value.trim() : '';
            var pass = document.getElementById('mail_password') ? document.getElementById('mail_password').value : '';
            var recipient = document.getElementById('test_recipient_email') ? document.getElementById('test_recipient_email').value.trim() : '';
            var alertBox = document.getElementById('smtpTestResultAlert');

            if (!recipient) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Eksik Bilgi',
                        text: 'Lütfen test e-postasının gönderileceği alıcı adresini giriniz.'
                    });
                } else {
                    alert('Lütfen test alıcı e-posta adresini giriniz.');
                }
                return;
            }

            var originalBtnHtml = btnExecuteSmtp.innerHTML;
            btnExecuteSmtp.disabled = true;
            btnExecuteSmtp.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Gönderiliyor...';

            if (alertBox) {
                alertBox.className = 'alert alert-warning py-2 px-3 mt-3 d-flex align-items-center';
                alertBox.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> SMTP sunucusuna bağlanılıyor ve test e-postası gönderiliyor... Lütfen bekleyiniz.';
            }

            $.ajax({
                url: 'api/test_smtp.php',
                type: 'POST',
                data: {
                    mail_host: host,
                    mail_port: port,
                    mail_username: user,
                    mail_password: pass,
                    test_email: recipient
                },
                dataType: 'json',
                success: function(res) {
                    btnExecuteSmtp.disabled = false;
                    btnExecuteSmtp.innerHTML = originalBtnHtml;

                    if (res.status === 'success') {
                        if (alertBox) {
                            alertBox.className = 'alert alert-success py-2 px-3 mt-3';
                            alertBox.innerHTML = '<div class="d-flex align-items-center font-13 weight-600 mb-1"><i class="fa fa-check-circle mr-2 font-16"></i> Başarılı!</div><div class="font-12">' + res.message + '</div>';
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'SMTP Bağlantısı Başarılı!',
                                html: res.message,
                                confirmButtonText: 'Tamam'
                            });
                        }
                    } else {
                        if (alertBox) {
                            alertBox.className = 'alert alert-danger py-2 px-3 mt-3';
                            alertBox.innerHTML = '<div class="d-flex align-items-center font-13 weight-600 mb-1"><i class="fa fa-exclamation-triangle mr-2 font-16"></i> Gönderim Başarısız!</div><div class="font-12">' + (res.message || 'SMTP hatası oluştu.') + '</div>';
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'SMTP Hatası',
                                html: res.message || 'E-Posta gönderimi gerçekleştirilemedi.',
                                confirmButtonText: 'Kapat'
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    btnExecuteSmtp.disabled = false;
                    btnExecuteSmtp.innerHTML = originalBtnHtml;

                    var errMsg = 'Sunucuyla iletişim kurulurken bir hata oluştu.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }

                    if (alertBox) {
                        alertBox.className = 'alert alert-danger py-2 px-3 mt-3';
                        alertBox.innerHTML = '<div class="d-flex align-items-center font-13 weight-600 mb-1"><i class="fa fa-times-circle mr-2 font-16"></i> Hata!</div><div class="font-12">' + errMsg + '</div>';
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Sunucu Hatası',
                            text: errMsg
                        });
                    }
                }
            });
        });
    }
});
</script>
