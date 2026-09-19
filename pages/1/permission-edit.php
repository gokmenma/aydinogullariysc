<?php

use App\Helper\Helper;
use App\Helper\Security;
use App\Model\PermissionModel;

permcontrol("authdefine");

$permModel = new PermissionModel();

// ID Çözümleme (Hem ham ID hem şifrelenmiş ID desteği)
$rawId = $_GET["id"] ?? 0;
$roleId = is_numeric($rawId) ? (int)$rawId : (int)Security::decrypt($rawId);

if ($roleId <= 0) {
    header("Location: index.php?p=permission-settings&err=invalid_id");
    exit;
}

// Rol Detaylarını Getir
$role = $permModel->getRoleDetails($roleId);
if (!$role) {
    header("Location: index.php?p=permission-settings&err=not_found");
    exit;
}

$roleName = $role["roleName"] ?? '';
$roleDesc = $role["roleDescription"] ?? '';
$grantedAuthIds = $role["auth_ids"] ?? [];
$assignedUsers = $role["users"] ?? [];
$isAdminRole = ($roleId === 1);

// AJAX veya Form POST İşlemi (Geleneksel POST desteği)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authName = trim($_POST["authName"] ?? '');
    $authDescription = trim($_POST["authDescription"] ?? '');
    
    // Checkbox dizisini al (auth[] veya checkedDataIds[])
    $authIds = [];
    if (!empty($_POST["auth"]) && is_array($_POST["auth"])) {
        $authIds = $_POST["auth"];
    } elseif (!empty($_POST["checkedDataIds"]) && is_array($_POST["checkedDataIds"])) {
        $authIds = $_POST["checkedDataIds"];
    }

    $result = $permModel->updateRole($roleId, $authName, $authDescription, $authIds);

    if ($result['success']) {
        echo "<script>window.location.href='index.php?p=permission-settings&st=success';</script>";
        exit;
    } else {
        $msg = htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
        showAlert("alert", $msg);
    }
}

// Bildirim Mesajları
if (isset($_GET["st"])) {
    if ($_GET["st"] === "empties") {
        showAlert("alert", "Lütfen Pozisyon / Rol adını giriniz.");
    } elseif ($_GET["st"] === "authempties") {
        showAlert("alert", "Lütfen bu pozisyon için en az bir yetki seçiniz.");
    } elseif ($_GET["st"] === "success") {
        showAlert("success", "Pozisyon bilgileri ve yetki tanımları başarıyla kaydedildi!");
    }
}

// Gruplandırılmış Yetkiler ve Tanımlar
$groupedAuths = $permModel->getAllAuthoritiesGrouped();
$groupDefs = PermissionModel::getGroupDefinitions();

// Toplam Yetki Sayısı
$totalAuthCount = 0;
foreach ($groupedAuths as $gAuths) {
    $totalAuthCount += count($gAuths);
}
$selectedAuthCount = count($grantedAuthIds);
$initialCoverageRate = $totalAuthCount > 0 ? round(($selectedAuthCount / $totalAuthCount) * 100, 1) : 0;

try {
    $logger = \getLogger("Pozisyon & Yetkiler");
    $logger->info("Pozisyon yetki düzenleme sayfası açıldı.", [
        'role_id'   => $roleId,
        'role_name' => $roleName,
        'username'  => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}
?>

<style>
    /* ==========================================
       PREMIUM PERMISSION EDIT STYLES
       ========================================== */
    .perm-edit-container {
        width: 100%;
        margin-bottom: 70px;
    }

    /* Page Header */
    .page-title-box {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .page-title-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.28);
    }
    .page-title-text h4 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.3px;
    }
    .page-title-text p {
        margin: 2px 0 0 0;
        font-size: 13px;
        color: #64748b;
    }

    /* Action Buttons */
    .btn-action-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: #fff !important;
        border: none;
        border-radius: 8px;
        padding: 8px 18px;
        font-weight: 600;
        font-size: 13.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.28);
        transition: all 0.2s ease;
        height: 38px;
        text-decoration: none;
        cursor: pointer;
    }
    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.38);
        color: #fff !important;
    }
    .btn-action-outline {
        border-radius: 8px;
        padding: 8px 14px;
        height: 38px;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }

    /* Role Info Form Card */
    .form-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 22px 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }
    .form-card-title {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .form-control-premium {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 10px 14px;
        font-size: 13.5px;
        transition: all 0.2s ease;
        background-color: #f8fafc;
    }
    .form-control-premium:focus {
        background-color: #ffffff;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        outline: none;
    }

    /* Role Quick Stats Pill */
    .role-stat-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
    }
    .role-stat-chip.stat-admin {
        background: #fef3c7;
        color: #92400e;
        border-color: #fde68a;
    }
    .role-stat-chip.stat-users {
        background: #ecfdf5;
        color: #065f46;
        border-color: #a7f3d0;
    }

    /* Toolbar / Filter Bar */
    .perm-toolbar-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 14px 18px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        position: sticky;
        top: 70px;
        z-index: 99;
        backdrop-filter: blur(10px);
    }
    .perm-search-box {
        position: relative;
        flex: 1 1 280px;
        max-width: 380px;
    }
    .perm-search-box i.search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
    }
    .perm-search-box input {
        width: 100%;
        padding: 9px 36px 9px 38px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-size: 13px;
        background: #f8fafc;
        transition: all 0.2s ease;
    }
    .perm-search-box input:focus {
        background: #fff;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        outline: none;
    }
    .perm-search-box .btn-clear-search {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        display: none;
        padding: 4px;
    }
    .perm-search-box .btn-clear-search:hover {
        color: #ef4444;
    }

    .filter-tabs {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 8px;
        gap: 2px;
    }
    .filter-tab-btn {
        border: none;
        background: transparent;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .filter-tab-btn.active {
        background: #ffffff;
        color: #4f46e5;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .live-progress-container {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 190px;
    }
    .live-progress-bar {
        flex: 1;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }
    .live-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #4f46e5 0%, #06b6d4 100%);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    /* Modular Permission Cards (Grid) */
    .perm-group-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        margin-bottom: 20px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: calc(100% - 20px);
        display: flex;
        flex-direction: column;
    }
    .perm-group-card:hover {
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
    }
    .perm-group-header {
        padding: 12px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .perm-group-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
        overflow: hidden;
    }
    .perm-group-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }
    .perm-group-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .perm-group-count-badge {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 12px;
        background: #ede9fe;
        color: #6d28d9;
        margin-left: 6px;
    }
    .btn-group-toggle {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 4px 9px;
        font-size: 11.5px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-group-toggle:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #94a3b8;
    }

    .perm-group-body {
        padding: 12px 14px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    /* Permission Item Box */
    .perm-item-box {
        display: flex;
        align-items: flex-start;
        padding: 9px 12px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
        gap: 10px;
    }
    .perm-item-box:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        transform: translateX(2px);
    }
    .perm-item-box.is-checked {
        background: #ecfdf5;
        border-color: #a7f3d0;
    }
    .perm-item-box.is-checked .perm-item-title {
        color: #065f46;
        font-weight: 600;
    }

    /* Custom Stylish Checkbox */
    .custom-checkbox-wrapper {
        position: relative;
        padding-top: 1px;
    }
    .custom-checkbox-wrapper input[type="checkbox"] {
        cursor: pointer;
        width: 17px;
        height: 17px;
        accent-color: #059669;
    }

    .perm-item-content {
        flex: 1;
        min-width: 0;
    }
    .perm-item-title {
        font-size: 13px;
        color: #334155;
        line-height: 1.3;
        margin-bottom: 2px;
    }
    .perm-item-code {
        display: inline-block;
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 10.5px;
        color: #64748b;
        background: #e2e8f0;
        padding: 1px 5px;
        border-radius: 4px;
    }
    .perm-item-box.is-checked .perm-item-code {
        background: #d1fae5;
        color: #047857;
    }

    /* Sticky Bottom Floating Bar */
    .sticky-save-bar {
        position: fixed;
        bottom: 16px;
        left: 50%;
        transform: translateX(-50%);
        background: #1e293b;
        color: #ffffff;
        padding: 10px 20px;
        border-radius: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        display: flex;
        align-items: center;
        gap: 16px;
        z-index: 1000;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sticky-save-bar .sticky-text {
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sticky-save-bar .sticky-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        color: #34d399;
    }
    .sticky-btn-save {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
        border: none;
        border-radius: 20px;
        padding: 7px 18px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        transition: all 0.2s ease;
    }
    .sticky-btn-save:hover {
        transform: scale(1.03);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }
    .sticky-btn-cancel {
        color: #cbd5e1;
        font-size: 12.5px;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .sticky-btn-cancel:hover {
        color: #ffffff;
        text-decoration: underline;
    }

    /* No search results */
    #noSearchResults {
        display: none;
        text-align: center;
        padding: 40px 20px;
        background: #ffffff;
        border-radius: 14px;
        border: 1px dashed #cbd5e1;
        color: #64748b;
    }

    /* ==========================================
       DARK MODE OVERRIDES
       ========================================== */
    .dark-mode .page-title-text h4 { color: #f1f5f9 !important; }
    .dark-mode .page-title-text p { color: #94a3b8 !important; }
    .dark-mode .form-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }
    .dark-mode .form-card-title { color: #f1f5f9 !important; }
    .dark-mode .form-control-premium {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    .dark-mode .form-control-premium:focus {
        border-color: #818cf8 !important;
    }
    .dark-mode .role-stat-chip {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #cbd5e1 !important;
    }
    .dark-mode .role-stat-chip.stat-admin {
        background: rgba(245, 158, 11, 0.15) !important;
        color: #fbbf24 !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }
    .dark-mode .role-stat-chip.stat-users {
        background: rgba(16, 185, 129, 0.15) !important;
        color: #6ee7b7 !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }

    .dark-mode .perm-toolbar-card {
        background: rgba(30, 41, 59, 0.95) !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
    }
    .dark-mode .perm-search-box input {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    .dark-mode .filter-tabs {
        background: #0f172a !important;
    }
    .dark-mode .filter-tab-btn {
        color: #94a3b8 !important;
    }
    .dark-mode .filter-tab-btn.active {
        background: #1e293b !important;
        color: #818cf8 !important;
    }
    .dark-mode .live-progress-bar {
        background: #334155 !important;
    }

    .dark-mode .perm-group-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark-mode .perm-group-header {
        background: #0f172a !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode .perm-group-title { color: #f1f5f9 !important; }
    .dark-mode .btn-group-toggle {
        background: #1e293b !important;
        border-color: #475569 !important;
        color: #cbd5e1 !important;
    }
    .dark-mode .btn-group-toggle:hover {
        background: #334155 !important;
        color: #ffffff !important;
    }

    .dark-mode .perm-item-box {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark-mode .perm-item-box:hover {
        background: #1e293b !important;
        border-color: #475569 !important;
    }
    .dark-mode .perm-item-box.is-checked {
        background: rgba(16, 185, 129, 0.15) !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
    }
    .dark-mode .perm-item-title { color: #e2e8f0 !important; }
    .dark-mode .perm-item-box.is-checked .perm-item-title { color: #6ee7b7 !important; }
    .dark-mode .perm-item-code {
        background: #1e293b !important;
        color: #94a3b8 !important;
    }
    .dark-mode .perm-item-box.is-checked .perm-item-code {
        background: rgba(16, 185, 129, 0.25) !important;
        color: #a7f3d0 !important;
    }
    .dark-mode #noSearchResults {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #94a3b8 !important;
    }
</style>

<div class="perm-edit-container">
    <form action="" id="permEditForm" method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="role_id" value="<?php echo $roleId; ?>">

        <!-- Sayfa Üst Başlık ve Aksiyon Barı -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 12px;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-shield"></i>
                </div>
                <div class="page-title-text">
                    <h4>Pozisyon / Rol Yetki Düzenleme</h4>
                    <p>Sistem erişim izinleri, modül yetkileri ve kullanıcı pozisyon tanımları</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <a href="index.php?p=permission-settings" class="btn btn-outline-secondary btn-action-outline">
                    <i class="fa fa-arrow-left"></i> <span>Listeye Dön</span>
                </a>
                <button type="button" class="btn btn-outline-primary btn-action-outline" id="btnSelectAllGlobal">
                    <i class="fa fa-check-square-o"></i> <span class="d-none d-sm-inline">Tümünü Seç</span>
                </button>
                <button type="button" class="btn btn-outline-warning btn-action-outline" id="btnDeselectAllGlobal">
                    <i class="fa fa-square-o"></i> <span class="d-none d-sm-inline">Temizle</span>
                </button>
                <button type="button" class="btn btn-action-primary" id="btnSaveTop">
                    <i class="fa fa-save"></i> <span>Değişiklikleri Kaydet</span>
                </button>
            </div>
        </div>

        <!-- Pozisyon Temel Bilgileri Kartı -->
        <div class="form-card animate-fade-in">
            <div class="form-card-title">
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <i class="fa fa-id-card-o text-primary"></i>
                    <span>Pozisyon & Rol Bilgileri</span>
                    <span class="role-stat-chip">#<?php echo $roleId; ?></span>
                    <?php if ($isAdminRole): ?>
                        <span class="role-stat-chip stat-admin"><i class="fa fa-star"></i> Süper Yönetici Pozisyonu</span>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="role-stat-chip stat-users">
                        <i class="fa fa-users"></i> <?php echo count($assignedUsers); ?> Atanmış Personel
                    </span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label class="font-weight-600 font-13 mb-1" for="authName">
                        Pozisyon / Rol Adı <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="authName" id="authName" required
                           value="<?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>"
                           class="form-control form-control-premium"
                           placeholder="Örn: Teknik Servis Yöneticisi, Muhasebe Sorumlusu">
                    <small class="text-muted">Kullanıcılara atanacak pozisyonun genel tanımı</small>
                </div>
                <div class="col-md-7">
                    <label class="font-weight-600 font-13 mb-1" for="authDescription">
                        Açıklama / Görev Kapsamı
                    </label>
                    <input type="text" name="authDescription" id="authDescription"
                           value="<?php echo htmlspecialchars($roleDesc, ENT_QUOTES, 'UTF-8'); ?>"
                           class="form-control form-control-premium"
                           placeholder="Örn: Saha operasyonlarını ve servis formlarını düzenleme yetkisine sahiptir.">
                    <small class="text-muted">Bu pozisyonun sistemdeki görev ve sorumluluk notu</small>
                </div>
            </div>
        </div>

        <!-- Canlı Arama ve Akıllı Filtreleme Araç Çubuğu -->
        <div class="perm-toolbar-card animate-fade-in">
            <!-- Canlı Arama -->
            <div class="perm-search-box">
                <i class="fa fa-search search-icon"></i>
                <input type="text" id="permSearchInput" placeholder="Yetki adı veya teknik kod ara... (örn: teklif, sil, service)">
                <button type="button" class="btn-clear-search" id="btnClearSearch" title="Aramayı Temizle">
                    <i class="fa fa-times-circle"></i>
                </button>
            </div>

            <!-- Filtre Tabları -->
            <div class="filter-tabs">
                <button type="button" class="filter-tab-btn active" data-filter="all">
                    Tümü (<span id="countTotalFilter"><?php echo $totalAuthCount; ?></span>)
                </button>
                <button type="button" class="filter-tab-btn" data-filter="granted">
                    Seçililer (<span id="countGrantedFilter"><?php echo $selectedAuthCount; ?></span>)
                </button>
                <button type="button" class="filter-tab-btn" data-filter="ungranted">
                    Seçilmeyenler (<span id="countUngrantedFilter"><?php echo $totalAuthCount - $selectedAuthCount; ?></span>)
                </button>
            </div>

            <!-- Canlı Kapsam İlerleme Çubuğu -->
            <div class="live-progress-container">
                <div class="live-progress-bar">
                    <div class="live-progress-fill" id="liveProgressBar" style="width: <?php echo min(100, $initialCoverageRate); ?>%;"></div>
                </div>
                <span class="font-weight-bold font-12" id="liveProgressText" style="color: #4f46e5;">
                    %<?php echo $initialCoverageRate; ?>
                </span>
            </div>
        </div>

        <!-- Arama Sonucu Bulunamadı Uyarısı -->
        <div id="noSearchResults">
            <i class="fa fa-search fa-2x mb-2 text-muted"></i>
            <h5>Aramanızla Eşleşen Yetki Bulunamadı</h5>
            <p class="mb-2">Arama kriterlerinizi değiştirerek tekrar deneyebilirsiniz.</p>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnResetSearchFilter">
                <i class="fa fa-refresh mr-1"></i> Aramayı Sıfırla
            </button>
        </div>

        <!-- Modüler Yetki Matrisi (Kartlar Grid) -->
        <div class="row" id="permModuleGrid">
            <?php
            foreach ($groupedAuths as $groupId => $authList):
                $groupDef = $groupDefs[$groupId] ?? [
                    'title' => "Modül Grubu #{$groupId}",
                    'icon'  => 'fa fa-folder-open-o',
                    'color' => '#475569',
                    'bg'    => '#f8fafc'
                ];

                $groupTotal = count($authList);
                $groupGranted = 0;
                foreach ($authList as $a) {
                    if (in_array((int)$a['id'], $grantedAuthIds, true)) {
                        $groupGranted++;
                    }
                }
            ?>
            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-3 perm-group-col" data-group-id="<?php echo $groupId; ?>">
                <div class="perm-group-card">
                    <!-- Kart Başlığı -->
                    <div class="perm-group-header">
                        <div class="perm-group-header-left">
                            <div class="perm-group-icon" style="background: <?php echo $groupDef['bg']; ?>; color: <?php echo $groupDef['color']; ?>;">
                                <i class="<?php echo $groupDef['icon']; ?>"></i>
                            </div>
                            <div>
                                <h6 class="perm-group-title" title="<?php echo htmlspecialchars($groupDef['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($groupDef['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </h6>
                            </div>
                            <span class="perm-group-count-badge" id="badgeGroup_<?php echo $groupId; ?>">
                                <?php echo $groupGranted; ?> / <?php echo $groupTotal; ?>
                            </span>
                        </div>
                        <div>
                            <button type="button" class="btn-group-toggle" data-group-id="<?php echo $groupId; ?>" title="Tüm grubu seç / kaldır">
                                <span><?php echo ($groupGranted === $groupTotal) ? 'Kaldır' : 'Tümünü Seç'; ?></span>
                            </button>
                        </div>
                    </div>

                    <!-- İzinler Listesi -->
                    <div class="perm-group-body">
                        <?php foreach ($authList as $auth):
                            $authId = (int)$auth['id'];
                            $authKey = $auth['authName'];
                            $authTitle = $auth['authTitle'];
                            $isChecked = in_array($authId, $grantedAuthIds, true);
                            $inputCheckId = "chk_auth_" . $groupId . "_" . $authId;
                        ?>
                        <label class="perm-item-box <?php echo $isChecked ? 'is-checked' : ''; ?>"
                               for="<?php echo $inputCheckId; ?>"
                               data-auth-id="<?php echo $authId; ?>"
                               data-auth-name="<?php echo htmlspecialchars(strtolower($authKey), ENT_QUOTES, 'UTF-8'); ?>"
                               data-auth-title="<?php echo htmlspecialchars(mb_strtolower($authTitle, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
                               data-is-checked="<?php echo $isChecked ? '1' : '0'; ?>">
                            <div class="custom-checkbox-wrapper">
                                <input type="checkbox" name="auth[]" value="<?php echo $authId; ?>"
                                       id="<?php echo $inputCheckId; ?>"
                                       class="perm-checkbox"
                                       data-group-id="<?php echo $groupId; ?>"
                                       <?php echo $isChecked ? 'checked' : ''; ?>>
                            </div>
                            <div class="perm-item-content">
                                <div class="perm-item-title"><?php echo htmlspecialchars($authTitle, ENT_QUOTES, 'UTF-8'); ?></div>
                                <span class="perm-item-code"><?php echo htmlspecialchars($authKey, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Yapışkan Alt Aksiyon Çubuğu (Sticky Save Bar) -->
        <div class="sticky-save-bar animate-fade-in" id="stickySaveBar">
            <div class="sticky-text">
                <i class="fa fa-shield text-primary"></i>
                <span class="d-none d-md-inline"><?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="sticky-badge" id="stickySelectedCount">
                    <?php echo $selectedAuthCount; ?> / <?php echo $totalAuthCount; ?> İzin Seçili
                </span>
            </div>
            <div class="d-flex align-items-center" style="gap: 12px;">
                <a href="index.php?p=permission-settings" class="sticky-btn-cancel">Vazgeç</a>
                <button type="button" class="sticky-btn-save" id="btnSaveSticky">
                    <i class="fa fa-save"></i> <span>Kaydet</span>
                </button>
            </div>
        </div>

    </form>
</div>

<script>
$(document).ready(function () {
    var TOTAL_AUTHS = <?php echo $totalAuthCount; ?>;

    // 1. Canlı Sayaç ve İlerleme Çubuğu Güncellemesi
    function updatePermissionCounters() {
        var totalChecked = 0;
        var groupCounts = {};

        // Grup bazlı sayımlar
        $(".perm-checkbox").each(function () {
            var groupId = $(this).data("group-id");
            if (!groupCounts[groupId]) {
                groupCounts[groupId] = { total: 0, checked: 0 };
            }
            groupCounts[groupId].total++;
            if ($(this).is(":checked")) {
                groupCounts[groupId].checked++;
                totalChecked++;
                $(this).closest(".perm-item-box").addClass("is-checked").attr("data-is-checked", "1");
            } else {
                $(this).closest(".perm-item-box").removeClass("is-checked").attr("data-is-checked", "0");
            }
        });

        // Üst Sayaçlar
        var ungrantedCount = TOTAL_AUTHS - totalChecked;
        var coverageRate = TOTAL_AUTHS > 0 ? ((totalChecked / TOTAL_AUTHS) * 100).toFixed(1) : 0;

        $("#countGrantedFilter").text(totalChecked);
        $("#countUngrantedFilter").text(ungrantedCount);
        $("#liveProgressBar").css("width", Math.min(100, coverageRate) + "%");
        $("#liveProgressText").text("%" + coverageRate);
        $("#stickySelectedCount").text(totalChecked + " / " + TOTAL_AUTHS + " İzin Seçili");

        // Grup Rozetleri ve Toggle Butonları Güncelleme
        Object.keys(groupCounts).forEach(function (gId) {
            var g = groupCounts[gId];
            $("#badgeGroup_" + gId).text(g.checked + " / " + g.total);

            var $btnToggle = $(".btn-group-toggle[data-group-id='" + gId + "']");
            if (g.checked === g.total && g.total > 0) {
                $btnToggle.find("span").text("Kaldır");
            } else {
                $btnToggle.find("span").text("Tümünü Seç");
            }
        });

        // Aktif filtre sekmesini uygula
        applyActiveFilter();
    }

    // 2. Checkbox Değişim Dinleyicisi
    $(document).on("change", ".perm-checkbox", function () {
        updatePermissionCounters();
    });

    // 3. Grup Bazlı Toplu Seçim / Kaldırma
    $(document).on("click", ".btn-group-toggle", function (e) {
        e.preventDefault();
        e.stopPropagation();

        var groupId = $(this).data("group-id");
        var $groupCard = $(this).closest(".perm-group-card");
        var $checkboxes = $groupCard.find(".perm-checkbox");
        var allChecked = true;

        $checkboxes.each(function () {
            if (!$(this).is(":checked")) {
                allChecked = false;
                return false;
            }
        });

        $checkboxes.prop("checked", !allChecked);
        updatePermissionCounters();
    });

    // 4. Genel (Global) Tümünü Seç / Tümünü Kaldır
    $("#btnSelectAllGlobal").on("click", function () {
        $(".perm-checkbox").prop("checked", true);
        updatePermissionCounters();
        showToast("Tüm izinler seçildi.", "info");
    });

    $("#btnDeselectAllGlobal").on("click", function () {
        $(".perm-checkbox").prop("checked", false);
        updatePermissionCounters();
        showToast("Tüm izin seçimleri kaldırıldı.", "warning");
    });

    // 5. Canlı Arama ve Filtreleme
    function applyActiveFilter() {
        var query = ($("#permSearchInput").val() || "").trim().toLowerCase();
        var activeFilter = $(".filter-tab-btn.active").data("filter") || "all";
        var visibleGroupCount = 0;

        $(".perm-group-col").each(function () {
            var $groupCol = $(this);
            var visibleItemInGroup = 0;

            $groupCol.find(".perm-item-box").each(function () {
                var $item = $(this);
                var isChecked = $item.find(".perm-checkbox").is(":checked");
                var title = ($item.attr("data-auth-title") || "").toLowerCase();
                var code = ($item.attr("data-auth-name") || "").toLowerCase();

                // Sekme filtre kontrolü
                var matchesTab = true;
                if (activeFilter === "granted" && !isChecked) matchesTab = false;
                if (activeFilter === "ungranted" && isChecked) matchesTab = false;

                // Arama sorgusu kontrolü
                var matchesQuery = true;
                if (query !== "") {
                    matchesQuery = (title.indexOf(query) !== -1 || code.indexOf(query) !== -1);
                }

                if (matchesTab && matchesQuery) {
                    $item.show();
                    visibleItemInGroup++;
                } else {
                    $item.hide();
                }
            });

            if (visibleItemInGroup > 0) {
                $groupCol.show();
                visibleGroupCount++;
            } else {
                $groupCol.hide();
            }
        });

        if (visibleGroupCount === 0) {
            $("#noSearchResults").show();
        } else {
            $("#noSearchResults").hide();
        }
    }

    $("#permSearchInput").on("input keyup", function () {
        var val = $(this).val();
        if (val && val.length > 0) {
            $("#btnClearSearch").show();
        } else {
            $("#btnClearSearch").hide();
        }
        applyActiveFilter();
    });

    $("#btnClearSearch, #btnResetSearchFilter").on("click", function () {
        $("#permSearchInput").val("");
        $("#btnClearSearch").hide();
        $(".filter-tab-btn[data-filter='all']").trigger("click");
        applyActiveFilter();
    });

    // Filtre Tab Tıklamaları
    $(".filter-tab-btn").on("click", function () {
        $(".filter-tab-btn").removeClass("active");
        $(this).addClass("active");
        applyActiveFilter();
    });

    // 6. Form Kaydetme (AJAX & SweetAlert2)
    function submitPermissionForm() {
        var roleName = $("#authName").val().trim();
        if (!roleName) {
            Swal.fire({
                icon: "warning",
                title: "Eksik Bilgi",
                text: "Lütfen Pozisyon / Rol adını giriniz."
            });
            $("#authName").focus();
            return;
        }

        var checkedCount = $(".perm-checkbox:checked").length;
        if (checkedCount === 0) {
            Swal.fire({
                icon: "warning",
                title: "Yetki Seçilmedi",
                text: "Lütfen bu pozisyon için en az 1 yetki seçiniz."
            });
            return;
        }

        // Butonları yükleniyor durumuna getir
        var $btnTop = $("#btnSaveTop");
        var $btnSticky = $("#btnSaveSticky");
        var origTopHtml = $btnTop.html();
        var origStickyHtml = $btnSticky.html();

        $btnTop.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Kaydediliyor...');
        $btnSticky.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Kaydediliyor...');

        // Checkbox değerlerini topla
        var formData = $("#permEditForm").serialize();

        $.ajax({
            type: "POST",
            url: "api/permission_save.php",
            data: formData,
            dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Başarılı!",
                        text: res.message || "Pozisyon yetkileri güncellendi.",
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.href = "index.php?p=permission-settings&st=success";
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Hata!",
                        text: res.message || "Kaydetme sırasında bir hata oluştu."
                    });
                }
            },
            error: function (xhr) {
                var errorMsg = "Kaydetme sırasında bir hata oluştu.";
                try {
                    var jsonErr = JSON.parse(xhr.responseText);
                    if (jsonErr && jsonErr.message) {
                        errorMsg = jsonErr.message;
                    }
                } catch(e) {}
                Swal.fire({
                    icon: "error",
                    title: "Hata!",
                    text: errorMsg
                });
            },
            complete: function () {
                $btnTop.prop("disabled", false).html(origTopHtml);
                $btnSticky.prop("disabled", false).html(origStickyHtml);
            }
        });
    }

    $("#btnSaveTop, #btnSaveSticky").on("click", function (e) {
        e.preventDefault();
        submitPermissionForm();
    });

    $("#permEditForm").on("submit", function (e) {
        e.preventDefault();
        submitPermissionForm();
    });

    // Basit Toast Yardımcısı
    function showToast(message, icon) {
        if (typeof Swal !== "undefined" && Swal.mixin) {
            var Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: icon || "info",
                title: message
            });
        }
    }
});
</script>