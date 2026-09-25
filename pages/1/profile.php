<?php

use App\Model\ActivityLogModel;

$userId = (int) sesset('id');
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

// CSRF Token
if (!isset($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}

$status = $_GET['st'] ?? '';
$activeTab = in_array($_GET['tab'] ?? '', ['security', 'activities'], true) ? $_GET['tab'] : 'security';

// Kullanıcı Bilgilerini Çek
$userStmt = $ac->prepare("
    SELECT u.*, p.p_title as role_title 
    FROM users u 
    LEFT JOIN perms p ON u.permission = p.id 
    WHERE u.id = ?
");
$userStmt->execute([$userId]);
$currentUser = $userStmt->fetch(PDO::FETCH_OBJ);

// Şifre Güncelleme İşlemi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $newPasswordConfirmation = (string) ($_POST['new_password_confirmation'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['profile_csrf_token'], $csrfToken)) {
        $status = 'csrf_error';
    } elseif ($currentPassword === '' || $newPassword === '' || $newPasswordConfirmation === '') {
        $status = 'empties';
    } elseif (md5(md5(md5($currentPassword))) !== (string) sesset('password')) {
        $status = 'current_password_error';
    } elseif (strlen($newPassword) < 8) {
        $status = 'password_short';
    } elseif ($newPassword !== $newPasswordConfirmation) {
        $status = 'password_mismatch';
    } elseif ($currentPassword === $newPassword) {
        $status = 'password_same';
    } else {
        $passwordHash = md5(md5(md5($newPassword)));
        $update = $ac->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([$passwordHash, $userId]);

        $_SESSION['password'] = $passwordHash;
        $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));

        if (function_exists('audit_log')) {
            audit_log('update', 'profile', 'Kullanıcı kendi şifresini güncelledi.', 'user', $userId);
        }

        header('Location: index.php?p=profile&tab=security&st=success');
        exit;
    }
}

// Activity Log Modeli ve Verileri
$activityLogModel = new ActivityLogModel();
$userStats = $activityLogModel->getUserActivityStats($userId);
$lastLogin = $activityLogModel->getUserLastLogin($userId);
$userModules = $activityLogModel->getUserModules($userId);
$userActivities = $activityLogModel->getUserActivitiesList($userId, 1000);

$alerts = [
    'success' => ['success', 'Şifreniz başarıyla güncellendi.'],
    'empties' => ['alert', 'Lütfen tüm şifre alanlarını doldurun.'],
    'csrf_error' => ['alert', 'Oturum doğrulaması başarısız oldu. Lütfen formu yeniden gönderin.'],
    'current_password_error' => ['alert', 'Mevcut şifreniz hatalı.'],
    'password_short' => ['alert', 'Yeni şifreniz en az 8 karakter olmalıdır.'],
    'password_mismatch' => ['alert', 'Yeni şifre ile şifre tekrarı eşleşmiyor.'],
    'password_same' => ['alert', 'Yeni şifreniz mevcut şifrenizden farklı olmalıdır.'],
];

if (isset($alerts[$status])) {
    showAlert($alerts[$status][0], $alerts[$status][1]);
}
?>

<div class="pd-ltr-20 xs-pd-20-10 profile-page-wrapper">
    <div class="min-height-200px">

        <!-- 1. PROFİL HERO & SEKME BAŞLIĞI (STANDART PREMIUM HEADER) -->
        <div class="premium-header-card profile-hero-card animate-fade-in mb-4">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon profile-hero-avatar">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo htmlspecialchars((string)($currentUser->username ?? sesset('username')), ENT_QUOTES, 'UTF-8'); ?></h4>
                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px 12px; margin-top: 5px;">
                            <?php if (!empty($currentUser->role_title)) : ?>
                                <span class="header-number-badge">
                                    <i class="fa fa-shield"></i>
                                    <?php echo htmlspecialchars((string)$currentUser->role_title, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($currentUser->Unvan)) : ?>
                                <span class="text-muted font-12"><i class="fa fa-briefcase mr-1"></i><?php echo htmlspecialchars((string)$currentUser->Unvan, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($currentUser->email)) : ?>
                                <span class="text-muted font-12"><i class="fa fa-envelope-o mr-1"></i><?php echo htmlspecialchars((string)$currentUser->email, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <!-- Sekme Değiştirici Butonlar -->
                    <div class="profile-tab-switch-group">
                        <button type="button" class="profile-switch-btn <?php echo $activeTab === 'security' ? 'active' : ''; ?>" id="tabBtnSecurity" data-target="paneSecurity">
                            <i class="fa fa-lock mr-1"></i> Güvenlik / Şifre
                        </button>
                        <button type="button" class="profile-switch-btn <?php echo $activeTab === 'activities' ? 'active' : ''; ?>" id="tabBtnActivities" data-target="paneActivities">
                            <i class="fa fa-history mr-1"></i> Sistem Aktiviteleri
                            <span class="badge badge-tab-count ml-1"><?php echo number_format($userStats['total'], 0, ',', '.'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SEKME 1: GÜVENLİK & ŞİFRE                                                 -->
        <!-- ========================================================================= -->
        <div id="paneSecurity" class="profile-tab-pane <?php echo $activeTab === 'security' ? 'active' : ''; ?>">
            <div class="row">
                <!-- Şifre Güncelleme Formu -->
                <div class="col-lg-7 col-md-12 mb-4">
                    <div class="form-card profile-card h-100 animate-fade-in">
                        <div class="form-card-header">
                            <div class="header-left-inner">
                                <div class="card-icon profile-card-icon-theme"><i class="fa fa-key"></i></div>
                                <div>
                                    <h5>Şifre Değiştir</h5>
                                    <p>Hesabınızın güvenliğini korumak için şifrenizi düzenli olarak güncelleyin.</p>
                                </div>
                            </div>
                        </div>

                        <form method="POST" autocomplete="off" id="formChangePassword" class="profile-form mt-2">
                            <input type="hidden" name="action" value="update_password">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['profile_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                            <div class="form-group mb-3">
                                <label for="current_password" class="form-label">
                                    <span class="text-danger font-weight-bold">(*)</span> Mevcut Şifre
                                </label>
                                <div class="input-group">
                                    <input required type="password" name="current_password" id="current_password" class="form-control" autocomplete="current-password" placeholder="Mevcut şifrenizi girin">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btn-toggle-pw" type="button" tabindex="-1" data-target="#current_password">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="new_password" class="form-label">
                                    <span class="text-danger font-weight-bold">(*)</span> Yeni Şifre
                                </label>
                                <div class="input-group">
                                    <input required minlength="8" type="password" name="new_password" id="new_password" class="form-control" autocomplete="new-password" placeholder="En az 8 karakter">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btn-toggle-pw" type="button" tabindex="-1" data-target="#new_password">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Şifre Gücü Çubuğu & Bilgi -->
                                <div class="password-strength-wrapper mt-2">
                                    <div class="strength-meter-bar">
                                        <div class="strength-meter-fill" id="strengthMeterFill"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="font-11 text-muted" id="strengthText">Şifre karmaşıklığı: Çok zayıf</span>
                                        <span class="font-11 text-muted" id="charCountText">0 / 8+ karakter</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="new_password_confirmation" class="form-label">
                                    <span class="text-danger font-weight-bold">(*)</span> Yeni Şifre Tekrar
                                </label>
                                <div class="input-group">
                                    <input required minlength="8" type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" autocomplete="new-password" placeholder="Yeni şifrenizi tekrar girin">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btn-toggle-pw" type="button" tabindex="-1" data-target="#new_password_confirmation">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="matchFeedback" class="font-11 mt-1 text-muted"></div>
                            </div>

                            <div class="d-flex justify-content-end pt-2 border-top">
                                <button type="submit" class="btn btn-primary px-4 py-2" id="btnSubmitPassword">
                                    <i class="fa fa-save mr-2"></i> Şifreyi Güncelle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Güvenlik & Oturum Bilgileri Kartı -->
                <div class="col-lg-5 col-md-12 mb-4">
                    <div class="form-card profile-card h-100 animate-fade-in">
                        <div class="form-card-header">
                            <div class="header-left-inner">
                                <div class="card-icon card-icon-emerald"><i class="fa fa-shield"></i></div>
                                <div>
                                    <h5>Oturum & Güvenlik Durumu</h5>
                                    <p>Hesap erişim ve güvenlik bilgileri</p>
                                </div>
                            </div>
                        </div>

                        <div class="security-info-list mt-3">
                            <div class="security-info-item">
                                <div class="info-icon text-primary"><i class="fa fa-globe"></i></div>
                                <div class="info-content">
                                    <span class="info-label">Mevcut IP Adresi</span>
                                    <strong class="info-value text-dark font-monospace"><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                            </div>

                            <div class="security-info-item">
                                <div class="info-icon text-success"><i class="fa fa-sign-in"></i></div>
                                <div class="info-content">
                                    <span class="info-label">Son Başarılı Giriş</span>
                                    <strong class="info-value text-dark">
                                        <?php if ($lastLogin) : ?>
                                            <?php echo htmlspecialchars(ActivityLogModel::formatRelativeTime($lastLogin->created_at, $lastLogin->dates, $lastLogin->clock), ENT_QUOTES, 'UTF-8'); ?>
                                            <span class="font-11 text-muted d-block mt-0.5">
                                                (<?php echo htmlspecialchars(!empty($lastLogin->created_at) ? date('d.m.Y H:i', strtotime($lastLogin->created_at)) : ($lastLogin->dates . ' ' . $lastLogin->clock), ENT_QUOTES, 'UTF-8'); ?>
                                                <?php echo !empty($lastLogin->ip_address) ? ' - ' . htmlspecialchars($lastLogin->ip_address, ENT_QUOTES, 'UTF-8') : ''; ?>)
                                            </span>
                                        <?php else : ?>
                                            Mevcut oturum aktif
                                        <?php endif; ?>
                                    </strong>
                                </div>
                            </div>

                            <div class="security-info-item">
                                <div class="info-icon text-info"><i class="fa fa-check-circle"></i></div>
                                <div class="info-content">
                                    <span class="info-label">Hesap Durumu</span>
                                    <div>
                                        <span class="badge badge-success px-2 py-1" style="border-radius: 6px; font-weight: 600;">
                                            <i class="fa fa-check mr-1"></i> Aktif & Güvenli
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="password-guidelines-box mt-4 p-3">
                            <h6 class="font-12 font-weight-bold text-dark mb-2">
                                <i class="fa fa-lightbulb-o text-warning mr-1"></i> Güçlü Şifre Önerileri:
                            </h6>
                            <ul class="font-11 text-muted mb-0 pl-3">
                                <li>En az 8 karakter uzunluğunda olmalıdır.</li>
                                <li>Büyük harf, küçük harf ve rakam kombinasyonu içeriniz.</li>
                                <li>Kişisel bilgilerinizi (doğum tarihi, isim) içermemelidir.</li>
                                <li>Şifrenizi başkalarıyla paylaşmayınız.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SEKME 2: SİSTEM AKTİVİTELERİ (DATATABLE)                                   -->
        <!-- ========================================================================= -->
        <div id="paneActivities" class="profile-tab-pane <?php echo $activeTab === 'activities' ? 'active' : ''; ?>">
            
            <!-- KPI ÖZET KARTLARI (İkon Solda, Yatay Liste Tasarımı, Tam Genişlik) -->
            <div class="kpi-summary-wrapper mb-3" id="kpiSummarySection">
                <div class="profile-kpi-grid">
                    <!-- KPI 1: Toplam Aktivite -->
                    <div class="profile-kpi-card kpi-blue">
                        <div class="kpi-icon-box"><i class="fa fa-list-alt"></i></div>
                        <div class="kpi-content-box">
                            <span class="kpi-value-num"><?php echo number_format($userStats['total'], 0, ',', '.'); ?></span>
                            <span class="kpi-label-text">Toplam Hareketim</span>
                        </div>
                    </div>

                    <!-- KPI 2: Bugünkü İşlemler -->
                    <div class="profile-kpi-card kpi-emerald">
                        <div class="kpi-icon-box"><i class="fa fa-bolt"></i></div>
                        <div class="kpi-content-box">
                            <span class="kpi-value-num"><?php echo number_format($userStats['today'], 0, ',', '.'); ?></span>
                            <span class="kpi-label-text">Bugünkü İşlemlerim</span>
                        </div>
                    </div>

                    <!-- KPI 3: Giriş Kayıtları -->
                    <div class="profile-kpi-card kpi-purple">
                        <div class="kpi-icon-box"><i class="fa fa-sign-in"></i></div>
                        <div class="kpi-content-box">
                            <span class="kpi-value-num"><?php echo number_format($userStats['logins'], 0, ',', '.'); ?></span>
                            <span class="kpi-label-text">Başarılı Girişler</span>
                        </div>
                    </div>

                    <!-- KPI 4: Veri İşlemleri -->
                    <div class="profile-kpi-card kpi-amber">
                        <div class="kpi-icon-box"><i class="fa fa-pencil-square-o"></i></div>
                        <div class="kpi-content-box">
                            <span class="kpi-value-num"><?php echo number_format($userStats['operations'], 0, ',', '.'); ?></span>
                            <span class="kpi-label-text">Veri Değişikliği (CRUD)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AKTİVİTE TABLO KARTI (DATATABLE) -->
            <div class="form-card profile-card animate-fade-in">
                <!-- Standart Header: Arama Kutusu & KPI Daraltma Butonu (Arama Kutusunun Sağında) -->
                <div class="form-card-header">
                    <div class="header-left-inner">
                        <div class="card-icon profile-card-icon-theme"><i class="fa fa-history"></i></div>
                        <div>
                            <h5>Aktivite Geçmişim</h5>
                            <p>Sistem üzerinde gerçekleştirdiğiniz tüm işlemler, gezintiler ve oturum kayıtları.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                        <!-- DataTables Arama Filtre Taşıma Kutusu -->
                        <div class="dt-header-filter-box d-flex align-items-center"></div>

                        <!-- KPI Toggle Butonu Standardı (HER ZAMAN ARAMA KUTUSUNUN SAĞINDA) -->
                        <button type="button" id="toggleKpiSummary" class="btn btn-outline-secondary btn-sm" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa fa-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Hızlı Olay Filtre Şeridi (Pills) & Modül Seçimi -->
                <div class="profile-quick-actions mb-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 10px;">
                    <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                        <span class="quick-actions-label"><i class="fa fa-filter"></i> Hızlı Filtre:</span>
                        <div class="quick-actions-list">
                            <button type="button" class="profile-quick-btn active" data-event-filter="all">
                                <i class="fa fa-bars"></i> Tümü (<?php echo number_format($userStats['total'], 0, ',', '.'); ?>)
                            </button>
                            <button type="button" class="profile-quick-btn" data-event-filter="operations">
                                <i class="fa fa-hand-pointer-o"></i> İşlemler (<?php echo number_format($userStats['operations'], 0, ',', '.'); ?>)
                            </button>
                            <button type="button" class="profile-quick-btn" data-event-filter="login">
                                <i class="fa fa-sign-in"></i> Giriş / Çıkış (<?php echo number_format($userStats['logins'], 0, ',', '.'); ?>)
                            </button>
                            <button type="button" class="profile-quick-btn" data-event-filter="view">
                                <i class="fa fa-eye"></i> Sayfa Gezinmeleri
                            </button>
                        </div>
                    </div>
                    <?php if (!empty($userModules)) : ?>
                        <div class="d-flex align-items-center" style="gap: 6px;">
                            <span class="font-12 font-weight-600 text-muted">Modül:</span>
                            <select id="dtModuleFilter" class="form-control form-control-sm" style="width: auto; min-width: 140px; border-radius: 8px;">
                                <option value="">Tüm Modüller</option>
                                <?php foreach ($userModules as $mod) : ?>
                                    <option value="<?php echo htmlspecialchars(ActivityLogModel::getModuleLabel($mod), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars(ActivityLogModel::getModuleLabel($mod), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- DataTable Tablosu -->
                <div class="table-responsive" style="padding: 0; margin: 0;">
                    <table id="tblProfileActivities" class="data-table select-row table-bordered table-hover" style="width: 100%; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center no-filter">#Sıra</th>
                                <th style="width: 140px;" class="text-center" data-filter-type="select">Olay Türü</th>
                                <th style="width: 130px;" class="text-center" data-filter-type="select">Modül</th>
                                <th data-filter-type="text">İşlem / Aktivite Özeti</th>
                                <th style="width: 130px;" class="text-center" data-filter-type="text">IP Adresi</th>
                                <th style="width: 170px;" class="text-center" data-filter-type="date">Tarih & Zaman</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $moduleIcons = [
                                'services' => 'fa fa-wrench',
                                'service' => 'fa fa-wrench',
                                'offers' => 'fa fa-file-text-o',
                                'offer' => 'fa fa-file-text-o',
                                'customers' => 'fa fa-building-o',
                                'customer' => 'fa fa-building-o',
                                'purchases' => 'fa fa-shopping-cart',
                                'purchase' => 'fa fa-shopping-cart',
                                'kesif' => 'fa fa-search',
                                'products' => 'fa fa-cube',
                                'reports' => 'fa fa-bar-chart',
                                'report' => 'fa fa-bar-chart',
                                'auth' => 'fa fa-shield',
                                'backup' => 'fa fa-database',
                                'navigation' => 'fa fa-compass',
                                'tasks' => 'fa fa-tasks',
                                'task' => 'fa fa-tasks',
                                'personnel' => 'fa fa-users',
                                'settings' => 'fa fa-cogs',
                                'profile' => 'fa fa-user',
                            ];
                            $rowNum = 1;
                            foreach ($userActivities as $act) :
                                $evIcon = ActivityLogModel::getEventIcon($act->event_type);
                                $evBadgeClass = ActivityLogModel::getEventBadgeClass($act->event_type);
                                $evLabel = ActivityLogModel::getEventLabel($act->event_type);
                                $moduleLabel = ActivityLogModel::getModuleLabel($act->module);
                                $modIcon = $moduleIcons[$act->module ?? ''] ?? 'fa fa-folder-o';
                                $relTime = ActivityLogModel::formatRelativeTime($act->created_at, $act->dates, $act->clock);
                                $exactTime = !empty($act->created_at) ? date('d.m.Y H:i:s', strtotime($act->created_at)) : ($act->dates . ' ' . $act->clock);
                                $summaryText = $act->summary ?: ($act->action ?: ($act->message ?: 'İşlem gerçekleştirildi'));
                            ?>
                                <tr data-event-type="<?php echo htmlspecialchars((string)$act->event_type, ENT_QUOTES, 'UTF-8'); ?>" data-module="<?php echo htmlspecialchars((string)$moduleLabel, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="text-center font-weight-bold text-muted font-12"><?php echo $rowNum++; ?></td>
                                    <td class="text-center">
                                        <span class="crm-badge-soft <?php echo $evBadgeClass; ?>" style="font-size: 11px; padding: 3px 8px;">
                                            <i class="<?php echo $evIcon; ?> mr-1"></i> <?php echo htmlspecialchars($evLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="module-tag font-11">
                                            <i class="<?php echo $modIcon; ?> mr-1"></i> <?php echo htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-weight-600 text-dark font-13"><?php echo htmlspecialchars($summaryText, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php if (!empty($act->action) && $act->action !== $summaryText && $act->action !== $act->event_type) : ?>
                                            <small class="text-muted d-block font-11 mt-0.5"><?php echo htmlspecialchars($act->action, ENT_QUOTES, 'UTF-8'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($act->ip_address)) : ?>
                                            <span class="font-monospace font-11 text-muted"><i class="fa fa-globe mr-1"></i><?php echo htmlspecialchars($act->ip_address, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else : ?>
                                            <span class="text-muted font-11">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="font-weight-600 text-dark font-12" title="<?php echo htmlspecialchars($exactTime, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fa fa-clock-o mr-1 text-muted"></i><?php echo htmlspecialchars($relTime, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <small class="text-muted d-block font-11"><?php echo htmlspecialchars($exactTime, ENT_QUOTES, 'UTF-8'); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<style>
/* ========================================================================= */
/* PROFİL & AKTİVİTELER ÖZEL STİLLERİ (TEMA UYUMLU)                         */
/* ========================================================================= */
.profile-page-wrapper {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
}

/* Header Avatar Icon - Varsayılan Tema Rengi */
.profile-hero-avatar {
    background: var(--theme-primary-light, #eff6ff) !important;
    color: var(--theme-primary, #2563eb) !important;
    border-color: var(--theme-primary-light, #dbeafe) !important;
}

.profile-card-icon-theme {
    background: var(--theme-primary-light, #eff6ff) !important;
    color: var(--theme-primary, #2563eb) !important;
}

/* Sekme Değiştirici Buton Grubu */
.profile-tab-switch-group {
    display: inline-flex;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 4px;
    border-radius: 10px;
    gap: 4px;
}

.dark-mode .profile-tab-switch-group {
    background: #0f172a;
    border-color: #334155;
}

.profile-switch-btn {
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
}

.profile-switch-btn:hover {
    color: #0f172a;
    background: rgba(0, 0, 0, 0.04);
}

.dark-mode .profile-switch-btn {
    color: #94a3b8;
}

.dark-mode .profile-switch-btn:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
}

.profile-switch-btn.active {
    background: var(--theme-primary, #2563eb) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px var(--theme-primary-shadow, rgba(37, 99, 235, 0.35)) !important;
}

.badge-tab-count {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
}

/* Sekme İçerik Panelleri */
.profile-tab-pane {
    display: none;
}

.profile-tab-pane.active {
    display: block;
}

/* Kartlar */
.profile-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}

.profile-card .form-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 16px;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 12px;
}

.header-left-inner {
    display: flex;
    align-items: center;
    gap: 12px;
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

.card-icon-emerald { background: #dcfce7; color: #16a34a; }

.form-card-header h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}

.form-card-header p {
    margin: 2px 0 0 0;
    font-size: 12px;
    color: #64748b;
}

/* Form Elemanları */
.profile-form .form-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}

.profile-form .form-control {
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    padding: 9px 13px;
    font-size: 13.5px;
    transition: all 0.2s;
}

.profile-form .form-control:focus {
    border-color: var(--theme-primary, #2563eb);
    box-shadow: 0 0 0 3px var(--theme-primary-shadow, rgba(37, 99, 235, 0.15));
}

.btn-toggle-pw {
    border-radius: 0 8px 8px 0 !important;
    border: 1.5px solid #e2e8f0;
    border-left: none;
    background: #f8fafc;
    color: #64748b;
}

.btn-toggle-pw:hover {
    background: #f1f5f9;
    color: #0f172a;
}

/* Şifre Gücü Çubuğu */
.password-strength-wrapper {
    margin-top: 6px;
}

.strength-meter-bar {
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
}

.strength-meter-fill {
    height: 100%;
    width: 0%;
    background: #ef4444;
    transition: all 0.3s ease;
}

/* Güvenlik Bilgi Listesi */
.security-info-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.security-info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #edf2f7;
}

.security-info-item .info-icon {
    font-size: 18px;
    margin-top: 2px;
}

.security-info-item .info-label {
    display: block;
    font-size: 11.5px;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 2px;
}

.security-info-item .info-value {
    font-size: 13px;
    color: #1e293b;
}

.password-guidelines-box {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 10px;
}

/* ========================================================================= */
/* KPI KARTLARI (İkon Solda, Yatay Liste Tasarımı, %100 Tam Genişlik)        */
/* ========================================================================= */
.profile-kpi-grid {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 14px !important;
    width: 100% !important;
}

.profile-kpi-card {
    background: #ffffff !important;
    border-radius: 12px !important;
    padding: 16px 20px !important;
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: flex-start !important;
    text-align: left !important;
    gap: 16px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03) !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease !important;
}

.profile-kpi-card:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06) !important;
}

.profile-kpi-card .kpi-icon-box {
    width: 48px !important;
    height: 48px !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 20px !important;
    flex-shrink: 0 !important;
}

.profile-kpi-card.kpi-blue .kpi-icon-box { background: #eff6ff !important; color: #2563eb !important; border: 1px solid #dbeafe !important; }
.profile-kpi-card.kpi-emerald .kpi-icon-box { background: #ecfdf5 !important; color: #059669 !important; border: 1px solid #a7f3d0 !important; }
.profile-kpi-card.kpi-purple .kpi-icon-box { background: #f5f3ff !important; color: #7c3aed !important; border: 1px solid #ddd6fe !important; }
.profile-kpi-card.kpi-amber .kpi-icon-box { background: #fffbeb !important; color: #d97706 !important; border: 1px solid #fde68a !important; }

.profile-kpi-card .kpi-content-box {
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    justify-content: center !important;
    text-align: left !important;
    flex: 1 !important;
    min-width: 0 !important;
}

.profile-kpi-card .kpi-content-box .kpi-value-num {
    font-size: 22px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1.1 !important;
    margin: 0 !important;
}

.profile-kpi-card .kpi-content-box .kpi-label-text {
    font-size: 12px !important;
    color: #64748b !important;
    font-weight: 500 !important;
    margin-top: 4px !important;
    line-height: 1.2 !important;
}

/* Hızlı Filtre Butonları (Pills) - Tema Rengiyle Uyumlu */
.profile-quick-actions {
    background: #f8fafc;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #edf2f7;
}

.quick-actions-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}

.quick-actions-list {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.profile-quick-btn {
    font-size: 12px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 6px;
    background: #ffffff;
    color: #475569;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    line-height: 1.2;
}

.profile-quick-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #cbd5e1;
}

.profile-quick-btn.active,
.profile-quick-btn.btn-primary-action {
    background: var(--theme-primary, #2563eb) !important;
    color: #ffffff !important;
    border-color: var(--theme-primary, #2563eb) !important;
    box-shadow: 0 2px 6px var(--theme-primary-shadow, rgba(37, 99, 235, 0.3)) !important;
}

/* Soft Badges */
.crm-badge-soft {
    font-weight: 600;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
}

.crm-badge-soft.soft-blue { background: #e0f2fe; color: #0284c7; }
.crm-badge-soft.soft-emerald { background: #dcfce7; color: #16a34a; }
.crm-badge-soft.soft-rose { background: #ffe4e6; color: #e11d48; }
.crm-badge-soft.soft-purple { background: #f3e8ff; color: #9333ea; }
.crm-badge-soft.soft-cyan { background: #cffafe; color: #0891b2; }
.crm-badge-soft.soft-amber { background: #fef3c7; color: #d97706; }

/* Modül Etiketi (Module Tag) - Yüksek Kontrast & Net Okunabilirlik */
.module-tag {
    background: #f1f5f9 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    padding: 3px 9px !important;
    color: #1e293b !important;
    font-size: 11.5px !important;
    font-weight: 600 !important;
    display: inline-flex !important;
    align-items: center !important;
    line-height: 1.2 !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
}

.module-tag i {
    color: #64748b !important;
}

.dark-mode .module-tag {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}

.dark-mode .module-tag i {
    color: #94a3b8 !important;
}

/* Dark Mode Desteği */
.dark-mode .profile-card,
.dark-mode .crm-kpi-card {
    background: #1e293b;
    border-color: #334155;
    color: #f1f5f9;
}

.dark-mode .form-card-header {
    border-bottom-color: #334155;
}

.dark-mode .form-card-header h5,
.dark-mode .crm-kpi-card .kpi-count {
    color: #f8fafc !important;
}

.dark-mode .form-card-header p,
.dark-mode .crm-kpi-card .kpi-label,
.dark-mode .security-info-item .info-label {
    color: #94a3b8 !important;
}

.dark-mode .security-info-item,
.dark-mode .profile-quick-actions {
    background: #0f172a;
    border-color: #334155;
}

.dark-mode .security-info-item .info-value {
    color: #f8fafc !important;
}

.dark-mode .profile-form .form-control,
.dark-mode #dtModuleFilter {
    background-color: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

.dark-mode .btn-toggle-pw {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
}

.dark-mode .password-guidelines-box {
    background: #1e293b;
    border-color: #475569;
}

.dark-mode .profile-quick-btn {
    background: #0f172a;
    border-color: #334155;
    color: #cbd5e1;
}

.dark-mode .profile-quick-btn.active,
.dark-mode .profile-quick-btn.btn-primary-action {
    background: var(--theme-primary, #2563eb) !important;
    color: #ffffff !important;
    border-color: var(--theme-primary, #2563eb) !important;
}

/* Mobil Uyumluluk */
@media (max-width: 991px) {
    .profile-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .profile-kpi-grid {
        grid-template-columns: 1fr;
    }
    .profile-tab-switch-group {
        width: 100%;
        display: flex;
    }
    .profile-switch-btn {
        flex: 1;
        justify-content: center;
        padding: 7px 10px;
        font-size: 12px;
    }
}
</style>

<script>
$(document).ready(function() {
    // 1. DataTables Kurulumu
    var activitiesTable = $('#tblProfileActivities').DataTable({
        responsive: false,
        autoWidth: false,
        scrollX: false,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        language: {
            url: 'include/js/tr.json',
            processing: '<div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"><span class="sr-only">Yükleniyor...</span></div>'
        },
        order: [[0, 'asc']],
        orderCellsTop: true,
        initComplete: function () {
            var api = this.api();
            
            // Arama kutusunu Form Card Header içine taşıma
            var $filterContainer = $('.form-card-header .dt-header-filter-box');
            var $searchBox = $('#tblProfileActivities_filter');
            if ($filterContainer.length && $searchBox.length) {
                $searchBox.detach().appendTo($filterContainer);
                $searchBox.find('input').addClass('form-control form-control-sm').css({
                    'border-radius': '8px',
                    'height': '36px',
                    'width': '220px',
                    'padding': '6px 12px'
                });
            }

            if (window.App && window.App.TableFilter) {
                App.TableFilter.attachToTable(api.table().node());
            }
        }
    });

    // 2. Sekme Geçiş Mantığı (Tab Switching)
    $('.profile-switch-btn').on('click', function() {
        var targetPaneId = $(this).data('target');
        var tabKey = targetPaneId === 'paneActivities' ? 'activities' : 'security';

        $('.profile-switch-btn').removeClass('active');
        $(this).addClass('active');

        $('.profile-tab-pane').removeClass('active');
        $('#' + targetPaneId).addClass('active');

        // DataTables kolon genişliklerini yeniden hesapla
        if (targetPaneId === 'paneActivities' && activitiesTable) {
            setTimeout(function() {
                activitiesTable.columns.adjust().draw(false);
            }, 50);
        }

        // URL query parametresini history API ile güncelle (Sayfa yenilenmeden)
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tabKey);
        window.history.pushState({}, '', url.toString());
    });

    // 3. Hızlı Filtre Butonları (Pills)
    $('.profile-quick-btn').on('click', function() {
        $('.profile-quick-btn').removeClass('active');
        $(this).addClass('active');

        var filterType = $(this).data('event-filter');
        if (filterType === 'all') {
            activitiesTable.column(1).search('').draw();
        } else if (filterType === 'operations') {
            activitiesTable.column(1).search('Oluşturma|Güncelleme|Silme|Dışa Aktarma|Dosya|Durum|Kopyalama', true, false).draw();
        } else if (filterType === 'login') {
            activitiesTable.column(1).search('Giriş|Çıkış', true, false).draw();
        } else if (filterType === 'view') {
            activitiesTable.column(1).search('Sayfa Ziyareti', true, false).draw();
        }
    });

    // 4. Modül Dropdown Filtresi
    $('#dtModuleFilter').on('change', function() {
        var modVal = $(this).val();
        if (modVal) {
            activitiesTable.column(2).search('^' + modVal + '$', true, false).draw();
        } else {
            activitiesTable.column(2).search('').draw();
        }
    });

    // 5. Şifre Göster / Gizle (Toggle Password Visibility)
    $('.btn-toggle-pw').on('click', function(e) {
        e.preventDefault();
        var targetInput = $($(this).data('target'));
        var icon = $(this).find('i');
        if (targetInput.attr('type') === 'password') {
            targetInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            targetInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // 6. Şifre Gücü & Eşleşme Kontrolü
    $('#new_password').on('input', function() {
        var pw = $(this).val();
        var length = pw.length;
        $('#charCountText').text(length + ' / 8+ karakter');

        var score = 0;
        if (length >= 8) score += 25;
        if (length >= 12) score += 15;
        if (/[A-Z]/.test(pw)) score += 20;
        if (/[a-z]/.test(pw)) score += 20;
        if (/[0-9]/.test(pw)) score += 20;
        if (/[^A-Za-z0-9]/.test(pw)) score += 20;
        score = Math.min(100, score);

        var $meter = $('#strengthMeterFill');
        var $text = $('#strengthText');

        $meter.css('width', score + '%');
        if (score < 40) {
            $meter.css('background', '#ef4444');
            $text.text('Şifre gücü: Zayıf').css('color', '#ef4444');
        } else if (score < 75) {
            $meter.css('background', '#f59e0b');
            $text.text('Şifre gücü: Orta').css('color', '#f59e0b');
        } else {
            $meter.css('background', '#10b981');
            $text.text('Şifre gücü: Güçlü').css('color', '#10b981');
        }

        checkPasswordMatch();
    });

    $('#new_password_confirmation').on('input', function() {
        checkPasswordMatch();
    });

    function checkPasswordMatch() {
        var p1 = $('#new_password').val();
        var p2 = $('#new_password_confirmation').val();
        var $feedback = $('#matchFeedback');

        if (p2.length === 0) {
            $feedback.text('');
            return;
        }

        if (p1 === p2) {
            $feedback.html('<span class="text-success"><i class="fa fa-check-circle mr-1"></i> Şifreler eşleşiyor.</span>');
        } else {
            $feedback.html('<span class="text-danger"><i class="fa fa-times-circle mr-1"></i> Şifreler henüz eşleşmiyor.</span>');
        }
    }

    // 7. KPI Kartları Göster / Gizle Standardı (localStorage)
    var KPI_STORAGE_KEY = 'aydinogullari_kpi_profile_collapsed';
    var $kpiSection = $('#kpiSummarySection');
    var $toggleBtn = $('#toggleKpiSummary');

    function updateKpiToggleState(isCollapsed, animate) {
        if (isCollapsed) {
            if (animate) {
                $kpiSection.slideUp(200);
            } else {
                $kpiSection.hide();
            }
            $toggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $toggleBtn.attr('title', 'Özet Kartlarını Göster');
        } else {
            if (animate) {
                $kpiSection.slideDown(200);
            } else {
                $kpiSection.show();
            }
            $toggleBtn.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $toggleBtn.attr('title', 'Özet Kartlarını Gizle');
        }
    }

    var savedState = localStorage.getItem(KPI_STORAGE_KEY) === 'true';
    updateKpiToggleState(savedState, false);

    $toggleBtn.on('click', function() {
        var currentState = $kpiSection.is(':visible');
        var newState = currentState;
        localStorage.setItem(KPI_STORAGE_KEY, newState ? 'true' : 'false');
        updateKpiToggleState(newState, true);
    });
});
</script>
