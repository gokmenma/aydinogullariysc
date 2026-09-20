<?php
use App\Model\BackupModel;
use App\Service\BackupService;
use App\Helper\Security;

// Yetki Kontrolü
if (!permtrue("backupmanage") && sesset("id") != 1) {
    pfail();
    exit;
}

$backupModel = new BackupModel();
$backupService = new BackupService();
$settings = $backupModel->getBackupSettings();

// -------------------------------------------------------------
// AJAX ENDPOINT: CANLI DURUM SORGUSU
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'live_status') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    
    $active = $backupModel->getActiveBackup();
    $logs = $backupModel->getRecentLogs(20);
    $formattedLogs = [];

    foreach ($logs as $l) {
        $filePath = realpath(__DIR__ . '/../../' . $l['file_path']);
        $formattedLogs[] = [
            'id' => (int)$l['id'],
            'encrypted_id' => Security::encrypt((string)$l['id']),
            'backup_type' => $l['backup_type'],
            'file_name' => $l['file_name'],
            'file_size_formatted' => $backupService->formatBytes((int)$l['file_size']),
            'duration_sec' => (float)$l['duration_sec'],
            'status' => $l['status'],
            'remote_status' => $l['remote_status'],
            'mail_status' => $l['mail_status'],
            'sha256_hash' => $l['sha256_hash'] ?? '',
            'created_at' => date('d.m.Y H:i:s', strtotime($l['created_at'])),
            'file_exists' => ($filePath && file_exists($filePath))
        ];
    }

    echo json_encode([
        'is_running' => ($active !== null),
        'active_backup' => $active,
        'logs' => $formattedLogs
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// AJAX ENDPOINT: ARKA PLANDA YEDEK BAŞLAT
// -------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'ajax_start_backup') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $backupType = in_array($_POST['backup_type'] ?? '', ['full', 'db', 'files'], true) ? $_POST['backup_type'] : 'full';
    $res = $backupService->runBackupAsync($backupType, (int)sesset('id'));

    if (function_exists('audit_log')) {
        audit_log("create", "backup", "Arka planda yedek başlatıldı ({$backupType})", "backup_logs", "async");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Yedekleme işlemi arka planda başlatıldı. İşlemlerinize kesintisiz devam edebilirsiniz.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// AJAX ENDPOINT: SENKRON (CANLI EKRANDA) YEDEK AL
// -------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'ajax_start_sync_backup') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $backupType = in_array($_POST['backup_type'] ?? '', ['full', 'db', 'files'], true) ? $_POST['backup_type'] : 'full';
    $res = $backupService->runBackup($backupType, (int)sesset('id'));

    if (function_exists('audit_log')) {
        audit_log("create", "backup", "Canlı yedek tamamlandı ({$backupType})", "backup_logs", (string)($res['log_id'] ?? 'sync'));
    }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// AJAX ENDPOINT: ASKIYA ALINAN / TAKILAN İŞLEMİ SIFIRLA
// -------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'ajax_cancel_active') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $backupModel->markStaleBackupsAsFailed(0); // 0 minutes means force mark all in_progress as failed
    echo json_encode(['success' => true, 'message' => 'Askıda kalan işlemler sıfırlandı.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// AJAX ENDPOINT: MANUEL OLARAK BULUTA (GOOGLE DRIVE/FTP) GÖNDER
// -------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'ajax_upload_remote' && !empty($_POST['id'])) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $rawId = Security::decrypt($_POST['id']);
    if (!$rawId) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz dosya kimliği.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $log = $backupModel->getLogById((int)$rawId);
    if (!$log) {
        echo json_encode(['success' => false, 'error' => 'Yedek kaydı bulunamadı.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $filePath = realpath(__DIR__ . '/../../' . $log['file_path']);
    if (!$filePath || !file_exists($filePath)) {
        echo json_encode(['success' => false, 'error' => 'Fiziksel yedek dosyası sunucuda bulunamadı.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $uploadResult = $backupService->uploadToRemote($filePath, $log['file_name'], $settings);
    if ($uploadResult['success']) {
        $targetName = (($settings['backup_remote_type'] ?? 'ftp') === 'gdrive') ? 'Google Drive' : ($settings['backup_remote_host'] ?? 'FTP');
        $backupModel->updateLog((int)$rawId, [
            'remote_status' => 'uploaded'
        ]);

        if (function_exists('audit_log')) {
            audit_log("upload", "backup", "Yedek manuel olarak {$targetName}'a aktarıldı: " . $log['file_name'], "backup_logs", (string)$rawId);
        }

        echo json_encode([
            'success' => true,
            'message' => "Yedek dosyası başarıyla {$targetName}'a aktarıldı."
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $uploadResult['error'] ?? 'Bilinmeyen hata'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/index.php';
$oauthRedirectUri = $protocol . $domain . $currentPath . '?p=backups&action=oauth_callback';

// -------------------------------------------------------------
// OAUTH: GOOGLE DRIVE İLE BAĞLAN
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'oauth_connect') {
    $clientId = trim($settings['backup_gdrive_client_id'] ?? '');
    if (empty($clientId)) {
        header("Location: index.php?p=backups&st=oauth_no_client_id");
        exit;
    }
    $_SESSION['gdrive_oauth_state'] = bin2hex(random_bytes(16));
    $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $oauthRedirectUri,
        'response_type' => 'code',
        'scope' => 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/drive',
        'access_type' => 'offline',
        'prompt' => 'consent',
        'state' => $_SESSION['gdrive_oauth_state']
    ]);
    header("Location: " . $authUrl);
    exit;
}

// -------------------------------------------------------------
// OAUTH: GOOGLE CALLBACK VE TOKEN KAYDETME
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'oauth_callback') {
    if (!empty($_GET['error'])) {
        $errorMessage = "Google Yetkilendirme İptal Edildi veya Hata: " . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
    } elseif (!empty($_GET['code'])) {
        $clientId = trim($settings['backup_gdrive_client_id'] ?? '');
        $clientSecret = trim($settings['backup_gdrive_client_secret'] ?? '');

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $_GET['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $oauthRedirectUri
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($httpCode === 200 && (!empty($data['refresh_token']) || !empty($data['access_token']))) {
            $updates = [
                'backup_remote_enabled' => '1',
                'backup_remote_type'    => 'gdrive'
            ];
            if (!empty($data['refresh_token'])) {
                $updates['backup_gdrive_refresh_token'] = $data['refresh_token'];
            }
            if (!empty($data['access_token'])) {
                $updates['backup_gdrive_access_token'] = $data['access_token'];
            }
            $backupModel->updateBackupSettings($updates);
            $settings = $backupModel->getBackupSettings();
            
            if (function_exists('audit_log')) {
                audit_log("connect", "backup_gdrive", "Google Drive hesabı OAuth 2.0 ile bağlandı", "settings", "backup");
            }
            header("Location: index.php?p=backups&st=oauth_connected");
            exit;
        } else {
            $errorMessage = "Google Token alınamadı: " . ($data['error_description'] ?? $data['error'] ?? $response);
        }
    }
}

// -------------------------------------------------------------
// OAUTH: GOOGLE DRIVE BAĞLANTISINI KES
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'oauth_disconnect') {
    $backupModel->updateBackupSettings([
        'backup_gdrive_refresh_token' => '',
        'backup_gdrive_access_token'  => ''
    ]);
    $settings = $backupModel->getBackupSettings();
    if (function_exists('audit_log')) {
        audit_log("disconnect", "backup_gdrive", "Google Drive bağlantısı kaldırıldı", "settings", "backup");
    }
    header("Location: index.php?p=backups&st=oauth_disconnected");
    exit;
}

// 1. İNDİRME İŞLEMİ
if (isset($_GET['action']) && $_GET['action'] === 'download' && !empty($_GET['id'])) {
    $rawId = Security::decrypt($_GET['id']);
    if (!$rawId) {
        $errorMessage = "Geçersiz veya süresi dolmuş dosya kimliği.";
    } else {
        $log = $backupModel->getLogById((int)$rawId);
        if (!$log) {
            $errorMessage = "Yedek kaydı bulunamadı.";
        } else {
            $filePath = realpath(__DIR__ . '/../../' . $log['file_path']);
            $allowedDir = realpath(__DIR__ . '/../../backups');

            if ($filePath && str_starts_with($filePath, $allowedDir) && file_exists($filePath)) {
                if (function_exists('audit_log')) {
                    audit_log("download", "backup", "Yedek dosyası indirildi: " . $log['file_name'], "backup_logs", (string)$log['id']);
                }

                while (ob_get_level()) {
                    ob_end_clean();
                }

                header('Content-Description: File Transfer');
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            } else {
                $errorMessage = "Yedek dosyası fiziksel olarak sunucuda bulunamadı veya silinmiş.";
            }
        }
    }
}

// 2. SİLME İŞLEMİ
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $rawId = Security::decrypt($_GET['id']);
    if ($rawId) {
        $log = $backupModel->getLogById((int)$rawId);
        if ($log) {
            $filePath = realpath(__DIR__ . '/../../' . $log['file_path']);
            $allowedDir = realpath(__DIR__ . '/../../backups');
            if ($filePath && str_starts_with($filePath, $allowedDir) && file_exists($filePath)) {
                @unlink($filePath);
            }
            $backupModel->deleteLog((int)$rawId);
            if (function_exists('audit_log')) {
                audit_log("delete", "backup", "Yedek dosyası silindi: " . $log['file_name'], "backup_logs", (string)$rawId);
            }
            header("Location: index.php?p=backups&st=deleted");
            exit;
        }
    }
}

// 3. SENKRON MANUEL YEDEK (POST Form Fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_manual_backup'])) {
    $backupType = in_array($_POST['backup_type'] ?? '', ['full', 'db', 'files'], true) ? $_POST['backup_type'] : 'full';
    $backupService->runBackupAsync($backupType, (int)sesset('id'));
    header("Location: index.php?p=backups&st=backup_started");
    exit;
}

// 4. AYARLARI KAYDETME
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_backup_settings'])) {
    $newSettings = [
        'backup_notification_email'   => trim($_POST['backup_notification_email'] ?? ''),
        'backup_send_mail'            => isset($_POST['backup_send_mail']) ? '1' : '0',
        'backup_remote_enabled'       => isset($_POST['backup_remote_enabled']) ? '1' : '0',
        'backup_remote_type'          => in_array($_POST['backup_remote_type'] ?? '', ['ftp', 'gdrive'], true) ? $_POST['backup_remote_type'] : 'ftp',
        'backup_remote_host'          => trim($_POST['backup_remote_host'] ?? ''),
        'backup_remote_port'          => (int)($_POST['backup_remote_port'] ?? 21),
        'backup_remote_user'          => trim($_POST['backup_remote_user'] ?? ''),
        'backup_remote_path'          => trim($_POST['backup_remote_path'] ?? '/backups'),
        'backup_gdrive_client_id'     => trim($_POST['backup_gdrive_client_id'] ?? ''),
        'backup_gdrive_client_secret' => trim($_POST['backup_gdrive_client_secret'] ?? ''),
        'backup_gdrive_folder_id'     => trim($_POST['backup_gdrive_folder_id'] ?? ''),
        'backup_retention_days'       => max(1, (int)($_POST['backup_retention_days'] ?? 30))
    ];

    if (!empty($_POST['backup_remote_pass'])) {
        $newSettings['backup_remote_pass'] = $_POST['backup_remote_pass'];
    }

    if (isset($_FILES['gdrive_json_file']) && $_FILES['gdrive_json_file']['error'] === UPLOAD_ERR_OK) {
        $uploadedJson = file_get_contents($_FILES['gdrive_json_file']['tmp_name']);
        if (!empty($uploadedJson) && json_decode($uploadedJson) !== null) {
            $newSettings['backup_gdrive_service_account_json'] = $uploadedJson;
        }
    } elseif (isset($_POST['backup_gdrive_service_account_json']) && trim($_POST['backup_gdrive_service_account_json']) !== '') {
        $newSettings['backup_gdrive_service_account_json'] = trim($_POST['backup_gdrive_service_account_json']);
    }

    $backupModel->updateBackupSettings($newSettings);
    $settings = $backupModel->getBackupSettings();

    if (function_exists('audit_log')) {
        audit_log("update", "backup_settings", "Yedekleme ayarları güncellendi", "settings", "backup");
    }

    header("Location: index.php?p=backups&st=settings_saved");
    exit;
}

// 5. CRON TOKEN YENİLEME
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate_cron_token'])) {
    $newToken = bin2hex(random_bytes(16));
    $backupModel->updateBackupSettings(['backup_cron_token' => $newToken]);
    header("Location: index.php?p=backups&st=token_renewed");
    exit;
}

// Bildirim Mesajları
if (isset($_GET['st'])) {
    if ($_GET['st'] === 'backup_started') $successMessage = "Yedekleme işlemi arka planda başlatıldı. İşlemlerinize devam edebilirsiniz.";
    if ($_GET['st'] === 'backup_success') $successMessage = "Yedekleme işlemi başarıyla tamamlandı ve arşivlendi.";
    if ($_GET['st'] === 'deleted') $successMessage = "Yedek kaydı ve dosyası başarıyla silindi.";
    if ($_GET['st'] === 'settings_saved') $successMessage = "Yedekleme ve harici depolama ayarları başarıyla kaydedildi.";
    if ($_GET['st'] === 'token_renewed') $successMessage = "Cron güvenlik anahtarı başarıyla yenilendi.";
    if ($_GET['st'] === 'oauth_connected') $successMessage = "Google Drive hesabınız başarıyla bağlandı ve yetkilendirildi! Artık yedekleriniz kendi kişisel Google Drive kotanıza yüklenecektir.";
    if ($_GET['st'] === 'oauth_disconnected') $successMessage = "Google Drive bağlantısı kaldırıldı.";
    if ($_GET['st'] === 'oauth_no_client_id') $errorMessage = "Lütfen önce Google Client ID ve Client Secret alanlarını doldurup 'Ayarları Kaydet'e basınız.";
}

// Verileri hazırla
$logs = $backupModel->getRecentLogs(100);
$totalBackups = count($logs);
$lastBackup = !empty($logs) ? $logs[0] : null;
$activeBackup = $backupModel->getActiveBackup();

$cronCommand = "0 23 * * * /opt/lampp/bin/php " . realpath(__DIR__ . '/../../cron_backup.php') . " full > /dev/null 2>&1";
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$cronWebhookUrl = $protocol . $domain . "/cron_backup.php?token=" . ($settings['backup_cron_token'] ?? '');
?>

<div class="pd-ltr-20 xs-pd-20-10">
    <div class="min-height-200px">

        <!-- Premium Header & Hero Card -->
        <div class="backup-header-card box-shadow mb-4 animate-fade-in">
            <div class="header-overlay"></div>
            <div class="row align-items-center relative-layout">
                <div class="col-lg-5 col-md-12 mb-3 mb-lg-0">
                    <div class="header-icon-box">
                        <i class="fa fa-database text-white"></i>
                    </div>
                    <div class="header-title-box">
                        <h4 class="text-white weight-700 mb-1">Veritabanı & Sistem Yedekleme</h4>
                        <p class="text-light-blue mb-0">Otomatik yedekleme, 3-2-1 felaket kurtarma ve bulut aktarımı</p>
                    </div>
                </div>

                <div class="col-lg-7 col-md-12">
                    <div class="row stats-row align-items-center">
                        <!-- Son Yedekleme -->
                        <div class="col-sm-3 col-6 mb-2 mb-sm-0">
                            <div class="stat-item text-center">
                                <span class="d-block stat-num text-white font-18 weight-700">
                                    <?php echo $lastBackup ? date('d.m.Y H:i', strtotime($lastBackup['created_at'])) : '-'; ?>
                                </span>
                                <span class="d-block stat-label text-light-blue font-12">Son Yedek Zamanı</span>
                            </div>
                        </div>

                        <!-- Son Boyut -->
                        <div class="col-sm-3 col-6 mb-2 mb-sm-0">
                            <div class="stat-item text-center">
                                <span class="d-block stat-num text-white font-18 weight-700">
                                    <?php echo $lastBackup ? $backupService->formatBytes((int)$lastBackup['file_size']) : '-'; ?>
                                </span>
                                <span class="d-block stat-label text-light-blue font-12">Son Yedek Boyutu</span>
                            </div>
                        </div>

                        <!-- Toplam Arşiv -->
                        <div class="col-sm-3 col-6">
                            <div class="stat-item text-center">
                                <span class="d-block stat-num text-white font-18 weight-700"><?php echo $totalBackups; ?> Adet</span>
                                <span class="d-block stat-label text-light-blue font-12">Kayıtlı Arşiv</span>
                            </div>
                        </div>

                        <!-- Hızlı Aksiyon Butonu -->
                        <div class="col-sm-3 col-6 text-right">
                            <div class="dropdown">
                                <button class="btn btn-warning btn-block shadow-sm weight-600 dropdown-toggle text-dark font-13 py-2" type="button" id="backupDropdown" data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-play mr-1"></i> Yedek Al
                                </button>
                                <div class="dropdown-menu dropdown-menu-right shadow-lg border-0" aria-labelledby="backupDropdown">
                                    <h6 class="dropdown-header font-12 text-uppercase text-muted">Yedekleme Türü Seçin</h6>
                                    <a class="dropdown-item py-2" href="javascript:void(0);" onclick="startAsyncBackup('full')">
                                        <i class="fa fa-archive mr-2 text-primary font-14"></i> <strong>Tam Sistem Yedeği (Arka Plan)</strong>
                                        <small class="d-block text-muted font-11">Veritabanı + Fiziksel Evraklar</small>
                                    </a>
                                    <a class="dropdown-item py-2" href="javascript:void(0);" onclick="startSyncBackup('full')">
                                        <i class="fa fa-bolt mr-2 text-warning font-14"></i> <strong>Tam Sistem (Canlı Ekranda Al)</strong>
                                        <small class="d-block text-muted font-11">Hosting kısıtlaması varsa ekranda bekleyerek</small>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item py-2" href="javascript:void(0);" onclick="startSyncBackup('db')">
                                        <i class="fa fa-database mr-2 text-success font-14"></i> <strong>Sadece Veritabanı (Hızlı - 2 Sn)</strong>
                                        <small class="d-block text-muted font-11">SQL Tabloları & Veriler</small>
                                    </a>
                                    <a class="dropdown-item py-2" href="javascript:void(0);" onclick="startAsyncBackup('files')">
                                        <i class="fa fa-folder-open mr-2 text-info font-14"></i> <strong>Sadece Dosyalar</strong>
                                        <small class="d-block text-muted font-11">Uploads & Files Klasörleri</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Canlı Arka Plan Bildirim Çubuğu (Dinamik Gösterim) -->
        <div id="activeBackupBanner" class="alert alert-info border-0 shadow-sm p-3 mb-4 rounded-12 <?php echo $activeBackup ? '' : 'd-none'; ?>" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-left: 5px solid #0284c7 !important;">
            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                <div class="d-flex align-items-center">
                    <div class="mr-3">
                        <span class="spinner-grow spinner-grow-sm text-primary" role="status" aria-hidden="true"></span>
                    </div>
                    <div>
                        <strong class="text-dark font-14"><i class="fa fa-cogs mr-1 text-primary"></i> Yedekleme işlemi şu anda devam ediyor...</strong>
                        <div class="text-secondary font-12">Sistem yedeklerinizi hazırlarken diğer işlemlerinize devam edebilirsiniz. Durum otomatik güncellenmektedir.</div>
                    </div>
                </div>
                <div>
                    <button type="button" onclick="cancelActiveBackup()" class="btn btn-sm btn-outline-danger bg-white font-12 font-weight-bold shadow-sm py-1 px-3">
                        <i class="fa fa-times mr-1"></i> Askıda Kalanı Sıfırla
                    </button>
                </div>
            </div>
        </div>

        <!-- Başarı / Hata Bildirimleri -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-12" role="alert">
                <i class="fa fa-check-circle mr-2"></i> <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-12" role="alert">
                <i class="fa fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Sekmeler (Tabs Card) -->
        <div class="bg-white border-radius-16 box-shadow mb-30 p-4">
            <ul class="nav nav-pills customtab2 mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-14 weight-600 py-2 px-3" data-toggle="tab" href="#historyTab" role="tab">
                        <i class="fa fa-history mr-2"></i> Yedekleme Geçmişi ve İndirme
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-14 weight-600 py-2 px-3" data-toggle="tab" href="#settingsTab" role="tab">
                        <i class="fa fa-sliders mr-2"></i> Otomatik Yedekleme & Harici Depolama Ayarları
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-14 weight-600 py-2 px-3" data-toggle="tab" href="#cronTab" role="tab">
                        <i class="fa fa-clock-o mr-2"></i> Cron Job (23:00 Kurulumu)
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- TAB 1: GEÇMİŞ -->
                <div class="tab-pane fade show active" id="historyTab" role="tabpanel">
                    <div class="table-responsive">
                        <table class="data-table table stripe hover w-100" id="backupLogsTable">
                            <thead>
                                <tr>
                                    <th style="width: 18%;">Tarih / Tür</th>
                                    <th style="width: 44%;">Yedek Dosyası & Özet</th>
                                    <th style="width: 14%;">Boyut / Süre</th>
                                    <th style="width: 12%;">Durum</th>
                                    <th class="datatable-nosort text-right" style="width: 12%;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="backupLogsTbody">
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $row): 
                                        $encryptedId = Security::encrypt((string)$row['id']);
                                        $filePath = realpath(__DIR__ . '/../../' . $row['file_path']);
                                        $fileExists = ($filePath && file_exists($filePath));
                                    ?>
                                        <tr id="log-row-<?php echo $row['id']; ?>">
                                            <td>
                                                <div class="font-13 weight-600 text-dark"><?php echo date('d.m.Y', strtotime($row['created_at'])); ?> <small class="text-muted"><?php echo date('H:i:s', strtotime($row['created_at'])); ?></small></div>
                                                <div class="mt-1">
                                                    <?php if ($row['backup_type'] === 'full'): ?>
                                                        <span class="badge badge-primary px-2 py-1 font-11"><i class="fa fa-archive mr-1"></i> Tam Sistem</span>
                                                    <?php elseif ($row['backup_type'] === 'db'): ?>
                                                        <span class="badge badge-success px-2 py-1 font-11"><i class="fa fa-database mr-1"></i> Veritabanı</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning px-2 py-1 font-11"><i class="fa fa-folder-open mr-1"></i> Dosyalar</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td style="word-break: break-all;">
                                                <div class="font-13 weight-600 text-dark" title="<?php echo htmlspecialchars($row['file_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($row['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <?php if (!empty($row['sha256_hash'])): ?>
                                                    <small class="text-muted font-mono" title="SHA-256: <?php echo htmlspecialchars($row['sha256_hash'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="fa fa-shield mr-1 text-primary"></i>SHA: <?php echo substr($row['sha256_hash'], 0, 12); ?>...
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="font-13 weight-700 text-dark"><?php echo $backupService->formatBytes((int)$row['file_size']); ?></div>
                                                <small class="text-muted"><i class="fa fa-clock-o mr-1"></i><?php echo htmlspecialchars((string)$row['duration_sec'], ENT_QUOTES, 'UTF-8'); ?> sn</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center flex-wrap" style="gap: 4px;">
                                                    <?php if ($row['status'] === 'success'): ?>
                                                        <span class="badge badge-success px-2 py-1 font-11"><i class="fa fa-check-circle mr-1"></i> Başarılı</span>
                                                    <?php elseif ($row['status'] === 'failed'): ?>
                                                        <span class="badge badge-danger px-2 py-1 font-11" title="<?php echo htmlspecialchars($row['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-times-circle mr-1"></i> Hata</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-info px-2 py-1 font-11"><i class="fa fa-spinner fa-spin mr-1"></i> İşleniyor</span>
                                                    <?php endif; ?>
                                                    <?php if ($row['remote_status'] === 'uploaded'): ?>
                                                        <span class="badge badge-soft-success px-1" title="Harici sunucuya yüklendi"><i class="fa fa-cloud-upload"></i></span>
                                                    <?php endif; ?>
                                                    <?php if ($row['mail_status'] === 'sent'): ?>
                                                        <span class="badge badge-soft-success px-1" title="Bildirim e-postası gönderildi"><i class="fa fa-envelope"></i></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($fileExists && $row['status'] === 'success'): ?>
                                                        <button type="button" onclick="uploadRemoteBackup('<?php echo $encryptedId; ?>', '<?php echo htmlspecialchars($row['file_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="btn btn-outline-info font-11 py-1 px-2" title="Google Drive / Buluta Gönder">
                                                            <i class="fa fa-cloud-upload"></i>
                                                        </button>
                                                        <a href="index.php?p=backups&action=download&id=<?php echo $encryptedId; ?>" class="btn btn-outline-success font-11 py-1 px-2" title="Yedek İndir">
                                                            <i class="fa fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button type="button" onclick="confirmDeleteBackup('<?php echo $encryptedId; ?>', '<?php echo htmlspecialchars($row['file_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="btn btn-outline-danger font-11 py-1 px-2" title="Sil">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: AYARLAR -->
                <div class="tab-pane fade" id="settingsTab" role="tabpanel">
                    <form method="POST" action="index.php?p=backups" enctype="multipart/form-data">
                        <input type="hidden" name="save_backup_settings" value="1">
                        
                        <div class="row">
                            <!-- E-Posta Ayarları Kartı -->
                            <div class="col-lg-5 mb-4">
                                <div class="card h-100 border-0 shadow-sm rounded-12 bg-light p-4">
                                    <h5 class="weight-600 text-dark mb-3"><i class="fa fa-envelope-o mr-2 text-primary"></i> E-Posta Bildirim & DB Gönderimi</h5>
                                    
                                    <div class="form-group mb-3">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="backup_send_mail" name="backup_send_mail" value="1" <?php echo (!empty($settings['backup_send_mail']) && $settings['backup_send_mail'] === '1') ? 'checked' : ''; ?>>
                                            <label class="custom-control-label font-weight-bold" for="backup_send_mail">Yedekleme durumunu e-posta ile bildir ve DB yedeğini ekle</label>
                                        </div>
                                        <small class="form-text text-muted mt-1">20 MB'a kadar olan veritabanı yedeği otomatik olarak sıkıştırılıp e-postaya ek yapılır.</small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="font-13 weight-600 text-secondary">Bildirim Alıcı E-Posta Adresi</label>
                                        <input type="email" class="form-control" name="backup_notification_email" value="<?php echo htmlspecialchars($settings['backup_notification_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="admin@aydinogullari.com">
                                        <small class="form-text text-muted">Boş bırakılırsa paneldeki varsayılan SMTP kullanıcı adresi kullanılır.</small>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label class="font-13 weight-600 text-secondary">Yedek Saklama Süresi (Retention Days)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="backup_retention_days" min="1" max="365" value="<?php echo htmlspecialchars($settings['backup_retention_days'] ?? '30', ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text font-12 bg-white">Gün</span>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted mt-1">Belirlenen günden eski yerel yedekler disk dolmasını önlemek için otomatik temizlenir.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Harici Depolama Kartı (Google Drive & FTP) -->
                            <div class="col-lg-7 mb-4">
                                <div class="card h-100 border-0 shadow-sm rounded-12 bg-light p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="weight-600 text-dark mb-0"><i class="fa fa-cloud-upload mr-2 text-primary"></i> Harici Bulut / Uzak Depolama</h5>
                                    </div>
                                    <p class="font-12 text-muted mb-3">Sunucu arızalarında veri kaybını önlemek için yedekler otomatik olarak Google Drive veya harici FTP sunucunuza aktarılır.</p>

                                    <div class="form-group mb-3">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="backup_remote_enabled" name="backup_remote_enabled" value="1" <?php echo (!empty($settings['backup_remote_enabled']) && $settings['backup_remote_enabled'] === '1') ? 'checked' : ''; ?>>
                                            <label class="custom-control-label font-weight-bold" for="backup_remote_enabled">Harici Depolamaya Otomatik Yüklemeyi Aktif Et</label>
                                        </div>
                                    </div>

                                    <!-- Depolama Servis Türü Seçimi -->
                                    <div class="form-group mb-3">
                                        <label class="font-13 weight-600 text-secondary">Depolama Servis Sağlayıcısı</label>
                                        <div class="d-flex align-items-center flex-wrap" style="gap: 20px;">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="remote_type_gdrive" name="backup_remote_type" value="gdrive" class="custom-control-input" <?php echo (($settings['backup_remote_type'] ?? '') === 'gdrive') ? 'checked' : ''; ?> onchange="toggleRemoteTypeUI()">
                                                <label class="custom-control-label font-weight-bold text-dark cursor-pointer" for="remote_type_gdrive">
                                                    <i class="fa fa-google text-danger mr-1"></i> Google Drive (Önerilen)
                                                </label>
                                            </div>
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="remote_type_ftp" name="backup_remote_type" value="ftp" class="custom-control-input" <?php echo (($settings['backup_remote_type'] ?? 'ftp') === 'ftp') ? 'checked' : ''; ?> onchange="toggleRemoteTypeUI()">
                                                <label class="custom-control-label font-weight-bold text-dark cursor-pointer" for="remote_type_ftp">
                                                    <i class="fa fa-server text-primary mr-1"></i> Uzak FTP / SFTP
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- GOOGLE DRIVE AYARLARI (OAUTH 2.0) -->
                                    <div id="gdrive_fields_wrapper" class="<?php echo (($settings['backup_remote_type'] ?? '') === 'gdrive') ? '' : 'd-none'; ?>">
                                        
                                        <!-- Bağlantı Durumu Kartı -->
                                        <?php $isOauthConnected = !empty($settings['backup_gdrive_refresh_token']); ?>
                                        <div class="mb-3">
                                            <?php if ($isOauthConnected): ?>
                                                <div class="alert alert-success d-flex align-items-center justify-content-between p-3 rounded-8 mb-0 border-0" style="background: #dcfce7; color: #15803d;">
                                                    <div>
                                                        <strong class="font-13 d-block"><i class="fa fa-check-circle mr-1"></i> Google Drive Hesabınız Bağlı & Yetkili</strong>
                                                        <span class="font-11 text-muted">Kişisel Google Drive kotanız (15 GB) üzerinden kesintisiz yedek alabilirsiniz.</span>
                                                    </div>
                                                    <a href="index.php?p=backups&action=oauth_disconnect" onclick="return confirm('Google Drive bağlantısını kesmek istediğinize emin misiniz?');" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa fa-chain-broken mr-1"></i> Bağlantıyı Kes
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning p-3 rounded-8 mb-0 border-0" style="background: #fef3c7; color: #92400e;">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fa fa-exclamation-triangle mr-2 font-16"></i>
                                                        <strong class="font-13">Google Drive Hesabı Henüz Yetkilendirilmedi</strong>
                                                    </div>
                                                    <p class="font-11 mb-2">Aşağıdaki Google Client ID ve Client Secret bilgilerini girip <strong>"Ayarları Kaydet"</strong>e basın, ardından <strong>"Google Drive ile Bağlan"</strong> butonuna tıklayarak izin verin.</p>
                                                    <?php if (!empty($settings['backup_gdrive_client_id'])): ?>
                                                        <a href="index.php?p=backups&action=oauth_connect" class="btn btn-primary btn-sm weight-600 shadow-sm">
                                                            <i class="fa fa-google mr-1"></i> Google Drive ile Bağlan & Yetkilendir
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-13 weight-600 text-secondary">Google OAuth Client ID</label>
                                                    <input type="text" class="form-control font-mono font-12" name="backup_gdrive_client_id" value="<?php echo htmlspecialchars($settings['backup_gdrive_client_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="...apps.googleusercontent.com">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-13 weight-600 text-secondary">Google OAuth Client Secret</label>
                                                    <input type="password" class="form-control font-mono font-12" name="backup_gdrive_client_secret" value="<?php echo htmlspecialchars($settings['backup_gdrive_client_secret'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="GOCSPX-...">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="font-13 weight-600 text-secondary">Google Drive Hedef Klasör ID (Folder ID)</label>
                                            <input type="text" class="form-control font-mono font-13" name="backup_gdrive_folder_id" value="<?php echo htmlspecialchars($settings['backup_gdrive_folder_id'] ?? '1ElD5Hn7rVLBUc29cBmCRuBmJte9U4b1q', ENT_QUOTES, 'UTF-8'); ?>" placeholder="1ElD5Hn7rVLBUc29cBmCRuBmJte9U4b1q">
                                            <small class="form-text text-muted">Google Drive klasörünüzün adresindeki kod (<code>folders/<strong>BURASI</strong></code>).</small>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="font-13 weight-600 text-secondary">Google Cloud'a Eklenecek Yönlendirme URI (Redirect URI)</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-white font-mono font-12" id="oauthRedirectUriInput" readonly value="<?php echo htmlspecialchars($oauthRedirectUri, ENT_QUOTES, 'UTF-8'); ?>">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyToClipboard('oauthRedirectUriInput')">
                                                        <i class="fa fa-copy"></i> Kopyala
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">Google Cloud Console > Credentials > OAuth 2.0 Client ID alanındaki <strong>"Authorized redirect URIs (Yetkili yönlendirme URI'leri)"</strong> kısmına bu adresi ekleyin.</small>
                                        </div>

                                        <div class="alert alert-info border-0 p-3 rounded-8 font-12 mb-0" style="background: #eff6ff; color: #1e40af;">
                                            <h6 class="font-13 font-weight-bold text-primary mb-1"><i class="fa fa-info-circle mr-1"></i> Google Cloud OAuth 2.0 İstemcisi Nasıl Oluşturulur?</h6>
                                            <ol class="mb-0 pl-3 font-12 text-secondary" style="line-height: 1.6;">
                                                <li>[Google Cloud Console](https://console.cloud.google.com/)'a girip <em>APIs & Services > OAuth consent screen</em> bölümünden uygulamanızı kaydedin (User Type: External).</li>
                                                <li><em>APIs & Services > Credentials > Create Credentials > <strong>OAuth client ID</strong></em> seçin (Application type: <strong>Web application</strong>).</li>
                                                <li>Yukarıdaki <strong>Redirect URI</strong>'yi Google'a yapıştırın, oluşturulan <strong>Client ID</strong> ve <strong>Client Secret</strong>'ı buraya kaydedip <strong>"Google Drive ile Bağlan"</strong> butonuna basın.</li>
                                            </ol>
                                        </div>
                                    </div>

                                    <!-- FTP AYARLARI -->
                                    <div id="ftp_fields_wrapper" class="<?php echo (($settings['backup_remote_type'] ?? 'ftp') === 'ftp') ? '' : 'd-none'; ?>">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group mb-3">
                                                    <label class="font-13 weight-600 text-secondary">FTP Sunucu Host / IP</label>
                                                    <input type="text" class="form-control" name="backup_remote_host" value="<?php echo htmlspecialchars($settings['backup_remote_host'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="ftp.uzaksunucu.com">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label class="font-13 weight-600 text-secondary">Port</label>
                                                    <input type="number" class="form-control" name="backup_remote_port" value="<?php echo htmlspecialchars($settings['backup_remote_port'] ?? '21', ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-13 weight-600 text-secondary">FTP Kullanıcı Adı</label>
                                                    <input type="text" class="form-control" name="backup_remote_user" value="<?php echo htmlspecialchars($settings['backup_remote_user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="backup_user">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label class="font-13 weight-600 text-secondary">FTP Parolası</label>
                                                    <input type="password" class="form-control" name="backup_remote_pass" placeholder="Değiştirmek için girin">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label class="font-13 weight-600 text-secondary">Uzak Klasör Yolu</label>
                                            <input type="text" class="form-control" name="backup_remote_path" value="<?php echo htmlspecialchars($settings['backup_remote_path'] ?? '/backups', ENT_QUOTES, 'UTF-8'); ?>" placeholder="/backups">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary px-4 font-14 weight-600 shadow-sm rounded-8">
                                <i class="fa fa-save mr-1"></i> Ayarları Kaydet
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 3: CRON KURULUMU -->
                <div class="tab-pane fade" id="cronTab" role="tabpanel">
                    <h5 class="weight-600 text-dark mb-3"><i class="fa fa-terminal mr-2 text-primary"></i> Her Akşam Saat 23:00 Otomatik Çalıştırma</h5>
                    <p class="text-secondary font-14 mb-4">
                        Sisteminizin her gece 23:00'da düzenli yedek alıp e-posta ile iletmesi için aşağıdaki seçeneklerden birini tanımlayabilirsiniz:
                    </p>

                    <div class="card border-0 shadow-sm rounded-12 bg-light p-4 mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge badge-primary mr-2 px-2 py-1">Tavsiye Edilen</span>
                            <h6 class="font-weight-bold text-dark mb-0"><i class="fa fa-linux mr-1"></i> cPanel / Linux Sunucu Cron Job</h6>
                        </div>
                        <p class="font-13 text-muted mb-2">cPanel > <strong>Zamanlanmış Görevler (Cron Jobs)</strong> alanına veya terminalde <code>crontab -e</code> satırına ekleyin:</p>
                        <div class="input-group">
                            <input type="text" class="form-control bg-white font-weight-bold font-mono text-primary font-13" id="cronCmdInput" readonly value="<?php echo htmlspecialchars($cronCommand, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" onclick="copyToClipboard('cronCmdInput')">
                                    <i class="fa fa-copy mr-1"></i> Kopyala
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-12 bg-light p-4">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge badge-secondary mr-2 px-2 py-1">Alternatif</span>
                            <h6 class="font-weight-bold text-dark mb-0"><i class="fa fa-globe mr-1"></i> Harici Webhook / Web Cron (cron-job.org)</h6>
                        </div>
                        <p class="font-13 text-muted mb-2">Sunucuda doğrudan CLI erişimi yoksa ücretsiz web cron servislerine tanımlayabileceğiniz güvenli URL:</p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control bg-white font-weight-bold font-mono text-secondary font-13" id="cronUrlInput" readonly value="<?php echo htmlspecialchars($cronWebhookUrl, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" onclick="copyToClipboard('cronUrlInput')">
                                    <i class="fa fa-copy mr-1"></i> Kopyala
                                </button>
                            </div>
                        </div>
                        <div>
                            <form method="POST" action="index.php?p=backups" style="display:inline;">
                                <input type="hidden" name="regenerate_cron_token" value="1">
                                <button type="submit" onclick="return confirm('Güvenlik tokenini yenilemek istediğinize emin misiniz? Eski URL geçersiz kalacaktır.');" class="btn btn-sm btn-outline-warning">
                                    <i class="fa fa-refresh mr-1"></i> Güvenlik Anahtarını Yenile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Premium Styling */
.backup-header-card {
    background: linear-gradient(135deg, #0f2b48 0%, #1e4d79 50%, #2979bb 100%);
    border-radius: 16px;
    padding: 26px 30px;
    position: relative;
    z-index: 100 !important;
    overflow: visible !important;
    color: #ffffff;
}
.backup-header-card .header-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: url('src/images/pattern.png') repeat;
    opacity: 0.06;
    border-radius: 16px;
    pointer-events: none;
}
.backup-header-card .dropdown-menu {
    background-color: #ffffff !important;
    background: #ffffff !important;
    opacity: 1 !important;
    z-index: 99999 !important;
    min-width: 280px !important;
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
    margin-top: 8px !important;
    padding: 0 !important;
    overflow: hidden;
    box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.1) !important;
}
.backup-header-card .dropdown-menu .dropdown-header {
    background-color: #f8fafc !important;
    border-bottom: 1px solid #edf2f7 !important;
    color: #64748b !important;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 0.5px;
    padding: 10px 16px;
    margin: 0;
}
.backup-header-card .dropdown-menu .dropdown-item {
    color: #1e293b !important;
    padding: 12px 16px !important;
    background-color: #ffffff !important;
    transition: background-color 0.15s ease;
}
.backup-header-card .dropdown-menu .dropdown-item:hover {
    background-color: #f1f5f9 !important;
}
.backup-header-card .dropdown-menu .dropdown-divider {
    margin: 0 !important;
    border-top: 1px solid #f1f5f9 !important;
}
.backup-header-card .relative-layout {
    position: relative;
    z-index: 101;
}
.backup-header-card .header-icon-box {
    width: 58px;
    height: 58px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    float: left;
    margin-right: 18px;
    backdrop-filter: blur(10px);
}
.backup-header-card .header-title-box {
    overflow: hidden;
}
.backup-header-card .stats-row {
    border-left: 1px solid rgba(255, 255, 255, 0.18);
    padding-left: 15px;
}
@media (max-width: 991px) {
    .backup-header-card .stats-row {
        border-left: none;
        padding-left: 0;
        margin-top: 15px;
    }
}
.backup-header-card .stat-item {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 10px;
    backdrop-filter: blur(5px);
    transition: transform 0.2s ease, background 0.2s ease;
}
.backup-header-card .stat-item:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.14);
}
.text-light-blue {
    color: #93c5fd !important;
}
.rounded-12 {
    border-radius: 12px !important;
}
.rounded-16 {
    border-radius: 16px !important;
}
.rounded-8 {
    border-radius: 8px !important;
}
.badge-soft-success {
    background-color: #dcfce7;
    color: #15803d;
}
.badge-soft-danger {
    background-color: #fee2e2;
    color: #b91c1c;
}
.badge-soft-warning {
    background-color: #fef3c7;
    color: #b45309;
}
.badge-soft-secondary {
    background-color: #f1f5f9;
    color: #64748b;
}
.nav-pills.customtab2 .nav-link {
    border-radius: 8px;
    color: #64748b;
    margin-right: 8px;
    transition: all 0.2s ease;
}
.nav-pills.customtab2 .nav-link.active {
    background-color: #1e4d79;
    color: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(30, 77, 121, 0.2);
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .7; transform: scale(1.06); }
}

/* SweetAlert2 Kompakt Tasarım & Sağ Alt Buton Düzeni: İptal Solda, Evet Sil Sağda (Kırmızı) */
.swal2-popup {
    width: 380px !important;
    max-width: 90vw !important;
    padding: 18px 20px !important;
    border-radius: 14px !important;
}
.swal2-icon {
    margin: 4px auto 10px auto !important;
    transform: scale(0.7) !important;
}
.swal2-title {
    font-size: 16px !important;
    margin: 0 0 6px 0 !important;
    padding: 0 !important;
    line-height: 1.3 !important;
    font-weight: 700 !important;
}
.swal2-html-container {
    font-size: 13px !important;
    margin: 0 0 10px 0 !important;
    padding: 0 !important;
    color: #4b5563 !important;
    line-height: 1.4 !important;
}
.swal2-actions {
    display: flex !important;
    flex-direction: row !important;
    justify-content: flex-end !important;
    gap: 8px !important;
    width: 100% !important;
    padding: 6px 0 0 0 !important;
    margin: 8px 0 0 0 !important;
}
.swal2-cancel {
    order: 1 !important;
    background-color: #ffffff !important;
    border: 1px solid #d1d5db !important;
    color: #374151 !important;
    border-radius: 6px !important;
    padding: 6px 14px !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    margin: 0 !important;
}
.swal2-cancel:hover {
    background-color: #f3f4f6 !important;
    color: #111827 !important;
}
.swal2-confirm {
    order: 2 !important;
    background-color: #dc2626 !important;
    border: 1px solid #dc2626 !important;
    color: #ffffff !important;
    border-radius: 6px !important;
    padding: 6px 14px !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    box-shadow: 0 1px 2px 0 rgba(220, 38, 38, 0.2) !important;
    margin: 0 !important;
}
.swal2-confirm:hover {
    background-color: #b91c1c !important;
    border-color: #b91c1c !important;
}
</style>

<script>
var backupDataTable = null;

function initBackupDataTable() {
    if ($.fn.DataTable.isDataTable('#backupLogsTable')) {
        $('#backupLogsTable').DataTable().destroy();
    }
    backupDataTable = $('#backupLogsTable').DataTable({
        responsive: false,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tümü"]],
        order: [[0, "desc"]],
        columnDefs: [
            { width: "18%", targets: 0 },
            { width: "44%", targets: 1 },
            { width: "14%", targets: 2 },
            { width: "12%", targets: 3 },
            { width: "12%", targets: 4, orderable: false, className: "text-right" }
        ],
        language: {
            url: "include/js/tr.json",
            emptyTable: "<div class='text-center py-4 text-muted'><i class='fa fa-database fa-2x d-block mb-2 text-light-gray'></i>Henüz kayıtlı yedekleme bulunmuyor. Yukarıdaki <strong>\"Yedek Al\"</strong> butonundan ilk yedeğinizi oluşturabilirsiniz.</div>"
        },
        initComplete: function () {
            if (window.App && window.App.TableFilter) {
                App.TableFilter.attachToTable(this.api().table().node());
            }
        }
    });
}

function getSwalInstance() {
    if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
        return Swal;
    }
    if (typeof swal !== 'undefined' && typeof swal.fire === 'function') {
        return swal;
    }
    if (typeof swal !== 'undefined') {
        return {
            fire: function(opts) {
                return new Promise(function(resolve) {
                    swal({
                        title: opts.title || '',
                        text: opts.text || '',
                        type: opts.icon || 'info',
                        showCancelButton: opts.showCancelButton || false,
                        confirmButtonColor: opts.confirmButtonColor || '#dc2626',
                        cancelButtonColor: opts.cancelButtonColor || '#64748b',
                        confirmButtonText: opts.confirmButtonText || 'Tamam',
                        cancelButtonText: opts.cancelButtonText || 'İptal'
                    }, function(isConfirm) {
                        resolve({ isConfirmed: isConfirm, value: isConfirm });
                    });
                });
            }
        };
    }
    return null;
}

function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    if (input) {
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value);
        const s = getSwalInstance();
        if (s) {
            s.fire({
                icon: 'success',
                title: 'Kopyalandı',
                text: 'Metin panoya kopyalandı!',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            alert("Panoya kopyalandı!");
        }
    }
}

function startAsyncBackup(type) {
    const btn = document.getElementById('backupDropdown');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Başlatılıyor...';
        btn.disabled = true;
    }

    $.ajax({
        url: 'index.php?p=backups',
        type: 'POST',
        data: {
            action: 'ajax_start_backup',
            backup_type: type
        },
        dataType: 'json',
        success: function(response) {
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
            
            if (response.success) {
                $('#activeBackupBanner').removeClass('d-none');
                wasBackupRunning = true;
                pollBackupStatus(true);
                const s = getSwalInstance();
                if (s) {
                    s.fire({
                        icon: 'success',
                        title: 'Yedekleme Başlatıldı!',
                        text: 'Sistem yedeğiniz arka planda oluşturuluyor. İşlemlerinize kesintisiz devam edebilirsiniz.',
                        confirmButtonText: 'Tamam, Devam Et',
                        confirmButtonColor: '#1e4d79',
                        timer: 4500,
                        timerProgressBar: true
                    });
                } else {
                    alert('Yedekleme işlemi arka planda başlatıldı. İşlemlerinize devam edebilirsiniz.');
                }
            } else {
                const s = getSwalInstance();
                if (s) {
                    s.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: 'Yedekleme başlatılamadı: ' + (response.error || 'Bilinmeyen hata')
                    });
                } else {
                    alert('Yedekleme başlatılamadı: ' + (response.error || 'Bilinmeyen hata'));
                }
            }
        },
        error: function() {
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
            alert('Sunucuyla iletişim kurulurken bir hata oluştu.');
        }
    });
}

function startSyncBackup(type) {
    const s = getSwalInstance();
    const typeLabel = (type === 'db') ? 'Veritabanı Yedeği' : 'Tam Sistem Yedeği';

    if (s) {
        s.fire({
            title: typeLabel + ' Alınıyor...',
            text: 'Yedek hazırlanıyor ve seçilen depolama alanına aktarılıyor. Lütfen işlem tamamlanana kadar sayfayı kapatmayınız.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function() {
                if (typeof Swal !== 'undefined' && typeof Swal.showLoading === 'function') {
                    Swal.showLoading();
                }
            }
        });
    }

    $.ajax({
        url: 'index.php?p=backups',
        type: 'POST',
        data: {
            action: 'ajax_start_sync_backup',
            backup_type: type
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (s) {
                    let msg = (response.messages && response.messages.length > 0) ? response.messages.join('<br>') : 'Yedekleme başarıyla tamamlandı.';
                    s.fire({
                        icon: 'success',
                        title: 'Tebrikler, Yedek Alındı!',
                        html: '<div class="text-left font-13 p-2 bg-light rounded mt-2"><strong>Boyut:</strong> ' + response.file_size_formatted + '<br><strong>Süre:</strong> ' + response.duration_sec + ' sn<br><br>' + msg + '</div>',
                        confirmButtonText: 'Harika',
                        confirmButtonColor: '#1e4d79'
                    });
                } else {
                    alert('Yedek başarıyla alındı: ' + response.file_name);
                }
                pollBackupStatus(true);
            } else {
                if (s) {
                    s.fire({
                        icon: 'error',
                        title: 'Yedekleme Hatası',
                        text: response.error || 'Yedekleme işlemi tamamlanamadı.'
                    });
                } else {
                    alert('Yedekleme Hatası: ' + (response.error || 'Bilinmeyen hata'));
                }
            }
        },
        error: function(xhr) {
            if (s) {
                s.fire({
                    icon: 'error',
                    title: 'Sunucu İletişim Hatası',
                    text: 'Sunucu zaman aşımına uğradı veya yanıt vermedi (HTTP ' + xhr.status + ').'
                });
            } else {
                alert('Sunucu iletişim hatası (HTTP ' + xhr.status + ')');
            }
        }
    });
}

function cancelActiveBackup() {
    $.post('index.php?p=backups', { action: 'ajax_cancel_active' }, function(res) {
        $('#activeBackupBanner').addClass('d-none');
        location.reload();
    }, 'json');
}

function confirmDeleteBackup(encryptedId, fileName) {
    const s = getSwalInstance();
    const displayName = fileName ? '"' + fileName + '"' : 'Bu yedek';

    if (s) {
        s.fire({
            title: 'Yedek Silinsin mi?',
            text: displayName + ' dosyası ve kaydı kalıcı olarak silinecektir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#ffffff',
            confirmButtonText: '<i class="fa fa-trash mr-1"></i> Evet, Sil',
            cancelButtonText: 'İptal',
            reverseButtons: false
        }).then(function(result) {
            if (result && (result.isConfirmed || result.value === true)) {
                window.location.href = 'index.php?p=backups&action=delete&id=' + encryptedId;
            }
        });
    } else {
        if (confirm(displayName + ' dosyasını silmek istediğinize emin misiniz?')) {
            window.location.href = 'index.php?p=backups&action=delete&id=' + encryptedId;
        }
    }
}

// Canlı Durum Yoklama (Polling)
let pollTimer = null;
let backupLogsSignature = null;
let wasBackupRunning = false;

function getBackupLogsSignature(logs) {
    return JSON.stringify((logs || []).map(function(row) {
        return [row.id, row.status, row.remote_status, row.mail_status, row.file_exists];
    }));
}

function pollBackupStatus(forceRender) {
    $.ajax({
        url: 'index.php?p=backups&action=live_status',
        type: 'GET',
        dataType: 'json',
        cache: false,
        data: { _: Date.now() },
        success: function(data) {
            const newSignature = getBackupLogsSignature(data.logs);
            const hasJustFinished = wasBackupRunning && !data.is_running;

            if (data.is_running) {
                $('#activeBackupBanner').removeClass('d-none');
            } else {
                $('#activeBackupBanner').addClass('d-none');
            }

            if (forceRender === true || hasJustFinished || backupLogsSignature !== newSignature) {
                renderBackupTable(data.logs);
                backupLogsSignature = newSignature;
            }

            wasBackupRunning = data.is_running;
        }
    });
}

function escapeBackupHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function renderBackupTable(logs) {
    if ($.fn.DataTable.isDataTable('#backupLogsTable')) {
        $('#backupLogsTable').DataTable().destroy();
    }

    if (!logs || logs.length === 0) {
        $('#backupLogsTbody').html('');
        initBackupDataTable();
        return;
    }

    let html = '';
    logs.forEach(function(row) {
        const safeEncryptedId = encodeURIComponent(row.encrypted_id || '').replace(/'/g, '%27');
        const safeNameArgument = encodeURIComponent(row.file_name || '').replace(/'/g, '%27');
        const safeFileName = escapeBackupHtml(row.file_name || '');
        const safeFileSize = escapeBackupHtml(row.file_size_formatted || '-');
        const safeDuration = escapeBackupHtml(row.duration_sec || 0);
        const safeCreatedAt = escapeBackupHtml(row.created_at || '');
        const dateParts = safeCreatedAt.split(' ');
        const safeHash = escapeBackupHtml(row.sha256_hash || '');
        let typeBadge = '';
        if (row.backup_type === 'full') typeBadge = '<span class="badge badge-primary px-2 py-1 font-11"><i class="fa fa-archive mr-1"></i> Tam Sistem</span>';
        else if (row.backup_type === 'db') typeBadge = '<span class="badge badge-success px-2 py-1 font-11"><i class="fa fa-database mr-1"></i> Veritabanı</span>';
        else typeBadge = '<span class="badge badge-warning px-2 py-1 font-11"><i class="fa fa-folder-open mr-1"></i> Dosyalar</span>';

        let statusBadge = '';
        if (row.status === 'success') statusBadge = '<span class="badge badge-success px-2 py-1 font-11"><i class="fa fa-check-circle mr-1"></i> Başarılı</span>';
        else if (row.status === 'failed') statusBadge = '<span class="badge badge-danger px-2 py-1 font-11"><i class="fa fa-times-circle mr-1"></i> Hata</span>';
        else statusBadge = '<span class="badge badge-info px-2 py-1 font-11"><i class="fa fa-spinner fa-spin mr-1"></i> İşleniyor</span>';

        let remoteBadge = (row.remote_status === 'uploaded') 
            ? '<span class="badge badge-soft-success px-1" title="Harici sunucuya yüklendi"><i class="fa fa-cloud-upload"></i></span>' 
            : '';

        let mailBadge = (row.mail_status === 'sent')
            ? '<span class="badge badge-soft-success px-1" title="Bildirim e-postası gönderildi"><i class="fa fa-envelope"></i></span>'
            : '';

        let uploadBtn = (row.file_exists && row.status === 'success')
            ? '<button type="button" onclick="uploadRemoteBackup(decodeURIComponent(\'' + safeEncryptedId + '\'), decodeURIComponent(\'' + safeNameArgument + '\'))" class="btn btn-outline-info font-11 py-1 px-2" title="Google Drive / Buluta Gönder"><i class="fa fa-cloud-upload"></i></button>'
            : '';

        let downloadBtn = (row.file_exists && row.status === 'success')
            ? '<a href="index.php?p=backups&action=download&id=' + safeEncryptedId + '" class="btn btn-outline-success font-11 py-1 px-2" title="Yedek İndir"><i class="fa fa-download"></i></a>'
            : '';

        let hashHtml = safeHash
            ? '<small class="text-muted font-mono" title="SHA-256: ' + safeHash + '"><i class="fa fa-shield mr-1 text-primary"></i>SHA: ' + safeHash.substring(0, 12) + '...</small>'
            : '';

        html += '<tr id="log-row-' + row.id + '">' +
            '<td><div class="font-13 weight-600 text-dark">' + dateParts[0] + ' <small class="text-muted">' + (dateParts[1] || '') + '</small></div><div class="mt-1">' + typeBadge + '</div></td>' +
            '<td style="word-break: break-all;"><div class="font-13 weight-600 text-dark" title="' + safeFileName + '">' + safeFileName + '</div>' + hashHtml + '</td>' +
            '<td><div class="font-13 weight-700 text-dark">' + safeFileSize + '</div><small class="text-muted"><i class="fa fa-clock-o mr-1"></i>' + safeDuration + ' sn</small></td>' +
            '<td><div class="d-flex align-items-center flex-wrap" style="gap: 4px;">' + statusBadge + ' ' + remoteBadge + ' ' + mailBadge + '</div></td>' +
            '<td class="text-right"><div class="btn-group btn-group-sm">' + uploadBtn + downloadBtn +
            '<button type="button" onclick="confirmDeleteBackup(decodeURIComponent(\'' + safeEncryptedId + '\'), decodeURIComponent(\'' + safeNameArgument + '\'))" class="btn btn-outline-danger font-11 py-1 px-2" title="Sil"><i class="fa fa-trash"></i></button>' +
            '</div></td></tr>';
    });
    $('#backupLogsTbody').html(html);
    initBackupDataTable();
}

function uploadRemoteBackup(encryptedId, fileName) {
    const s = getSwalInstance();
    const displayName = fileName ? '"' + fileName + '"' : 'Bu yedek';
    
    if (s) {
        s.fire({
            title: 'Buluta Gönderilsin mi?',
            text: displayName + ' dosyası Google Drive / Harici Depolama alanına aktarılacaktır.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0284c7',
            cancelButtonColor: '#ffffff',
            confirmButtonText: '<i class="fa fa-cloud-upload mr-1"></i> Evet, Gönder',
            cancelButtonText: 'İptal'
        }).then(function(result) {
            if (result && (result.isConfirmed || result.value === true)) {
                doUploadRemote(encryptedId);
            }
        });
    } else {
        if (confirm(displayName + ' dosyasını Google Drive\'a göndermek istiyor musunuz?')) {
            doUploadRemote(encryptedId);
        }
    }
}

function doUploadRemote(encryptedId) {
    const s = getSwalInstance();
    if (s) {
        s.fire({
            title: 'Aktarılıyor...',
            text: 'Yedek dosyası Google Drive / Harici Depolama sunucusuna yükleniyor, lütfen bekleyin.',
            allowOutsideClick: false,
            didOpen: function() {
                if (typeof Swal !== 'undefined' && typeof Swal.showLoading === 'function') {
                    Swal.showLoading();
                }
            }
        });
    }

    $.ajax({
        url: 'index.php?p=backups',
        type: 'POST',
        data: {
            action: 'ajax_upload_remote',
            id: encryptedId
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                if (s) {
                    s.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: res.message || 'Yedek dosyası buluta aktarıldı.',
                        confirmButtonColor: '#1e4d79'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    alert(res.message);
                    location.reload();
                }
            } else {
                if (s) {
                    s.fire({
                        icon: 'error',
                        title: 'Yükleme Hatası',
                        text: res.error || 'Bilinmeyen hata'
                    });
                } else {
                    alert('Hata: ' + (res.error || 'Bilinmeyen hata'));
                }
            }
        },
        error: function() {
            if (s) {
                s.fire({
                    icon: 'error',
                    title: 'Bağlantı Hatası',
                    text: 'Sunucu ile iletişim kurulamadı.'
                });
            } else {
                alert('Sunucu ile iletişim kurulamadı.');
            }
        }
    });
}

function toggleRemoteTypeUI() {
    const isGdrive = document.getElementById('remote_type_gdrive') && document.getElementById('remote_type_gdrive').checked;
    if (isGdrive) {
        $('#gdrive_fields_wrapper').removeClass('d-none');
        $('#ftp_fields_wrapper').addClass('d-none');
    } else {
        $('#gdrive_fields_wrapper').addClass('d-none');
        $('#ftp_fields_wrapper').removeClass('d-none');
    }
}

$(document).ready(function() {
    initBackupDataTable();
    wasBackupRunning = !$('#activeBackupBanner').hasClass('d-none');
    pollBackupStatus(false);
    pollTimer = setInterval(pollBackupStatus, 4000);

    // Dosya seçildiğinde input etiketini güncelle
    $('#gdrive_json_file').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName || "Dosya seçildi");
    });
});
</script>
