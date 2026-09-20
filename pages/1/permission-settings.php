<?php

use App\Helper\Helper;
use App\Helper\Security;
use App\Model\PermissionModel;

permcontrol("authdefine");

$permModel = new PermissionModel();

// KPI İstatistikleri
$stats = $permModel->getSummaryStats();
$totalRoles = (int) ($stats['total_roles'] ?? 0);
$totalAuths = (int) ($stats['total_auths'] ?? 0);
$assignedUsers = (int) ($stats['assigned_users'] ?? 0);
$topRoleName = $stats['top_role_name'] ?? 'Admin';
$topRoleAuthCount = (int) ($stats['top_role_auth_count'] ?? 0);
$topRoleRate = $totalAuths > 0 ? round(($topRoleAuthCount / $totalAuths) * 100, 1) : 0;

// Tüm Roller ve Detayları
$roles = $permModel->getRolesWithDetails();

// Gruplandırılmış Tüm Yetkiler
$groupedAuths = $permModel->getAllAuthoritiesGrouped();

// Modül / Grup Tanımları (PermissionModel üzerinden merkezi tanım)
$groupDefs = PermissionModel::getGroupDefinitions();

try {
    $logger = \getLogger("Pozisyon & Yetkiler");
    $logger->info("Pozisyon ve yetki listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_permissions_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-permissions-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* ==========================================
       PREMIUM PERMISSION SETTINGS THEME
       ========================================== */
    .kpi-permissions-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    .permissions-list-wrapper {
        width: 100%;
    }

    /* Page Header Styles */
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

    /* KPI Summary Cards */
    .crm-kpi-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 18px 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .crm-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.07);
    }
    .crm-kpi-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .crm-kpi-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        display: block;
        margin-bottom: 4px;
    }
    .crm-kpi-value {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .crm-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .icon-primary { background: #eff6ff; color: #2563eb; }
    .icon-emerald { background: #ecfdf5; color: #059669; }
    .icon-purple  { background: #f5f3ff; color: #7c3aed; }
    .icon-amber   { background: #fffbeb; color: #d97706; }

    .crm-kpi-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
        font-size: 11.5px;
    }
    .crm-badge-soft {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 11px;
    }
    .soft-primary { background: #dbeafe; color: #1e40af; }
    .soft-emerald { background: #d1fae5; color: #065f46; }
    .soft-purple  { background: #ede9fe; color: #5b21b6; }
    .soft-amber   { background: #fef3c7; color: #92400e; }

    /* Form & Table Card styling */
    .form-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 4px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        margin-bottom: 0;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 12px;
    }
    .form-card-header .header-left-inner {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .form-card-header .card-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        background: #f1f5f9;
        color: #475569;
    }
    .form-card-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    .form-card-header p {
        margin: 2px 0 0 0;
        font-size: 12.5px;
        color: #64748b;
    }

    /* Action Buttons in Header */
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

    /* DataTables Container & Reset Spacing (Eliminates the empty top bar gap) */
    .form-card .table-responsive {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
    }
    .form-card .dataTables_wrapper {
        padding: 0 !important;
    }
    .form-card .dataTables_wrapper .row:first-child {
        display: none !important;
        margin: 0 !important;
    }
    .form-card .dataTables_wrapper .row:last-child {
        padding: 12px 20px !important;
        margin: 0 !important;
        border-top: 1px solid #f1f5f9;
        background: #fafafa;
    }

    /* Table Styles */
    #tblPermissions {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
    }
    #tblPermissions thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 11px 12px !important;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        vertical-align: middle;
        white-space: nowrap;
    }
    #tblPermissions tbody td {
        padding: 10px 12px !important;
        vertical-align: middle;
        font-size: 13px;
        border-top: 1px solid #f1f5f9;
    }
    #tblPermissions tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Row Index Badge */
    .row-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 22px;
        padding: 0 5px;
        background: #e2e8f0;
        color: #0f172a !important;
        font-weight: 700;
        font-size: 12px;
        border-radius: 5px;
        border: 1px solid #cbd5e1;
    }

    /* Role Badges */
    .role-title-cell {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #1e293b;
    }
    .badge-role-admin {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #fff;
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .badge-role-tag {
        background: #f1f5f9;
        color: #475569;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
    }

    /* User Count Badge */
    .badge-user-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .badge-user-pill:hover {
        background: #dcfce7;
        transform: translateY(-1px);
    }
    .badge-user-empty {
        background: #f8fafc;
        color: #94a3b8;
        border-color: #e2e8f0;
        cursor: default;
    }
    .badge-user-empty:hover {
        transform: none;
    }

    /* Auth Scope Badge & Progress */
    .auth-scope-container {
        max-width: 380px;
    }
    .auth-percent-badge {
        font-size: 11.5px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
        display: inline-flex;
        align-items: center;
        line-height: 1.2;
    }
    .auth-percent-badge.is-full {
        background: #ecfdf5;
        color: #065f46;
        border-color: #a7f3d0;
    }
    .auth-scope-progress {
        height: 6px;
        border-radius: 3px;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: 5px;
    }
    .auth-scope-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #4f46e5 0%, #06b6d4 100%);
        border-radius: 3px;
    }
    .auth-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 7px;
        background: #f1f5f9;
        color: #334155;
        border-radius: 4px;
        font-size: 11px;
        margin: 1px;
        border: 1px solid #e2e8f0;
    }
    .auth-more-pill {
        display: inline-flex;
        align-items: center;
        padding: 2px 6px;
        background: #ede9fe;
        color: #6d28d9;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #ddd6fe;
    }
    .auth-more-pill:hover {
        background: #ddd6fe;
    }

    /* Action Buttons in Table */
    .action-btn-group {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        justify-content: center;
        white-space: nowrap;
    }
    .action-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 13px;
        transition: all 0.15s ease;
    }
    .action-btn:hover {
        transform: translateY(-1px);
    }

    /* Context Menu */
    .custom-context-menu {
        display: none;
        position: fixed;
        z-index: 99999;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.08);
        padding: 8px 0;
        min-width: 230px;
        backdrop-filter: blur(8px);
    }
    .custom-context-menu .cm-header {
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 260px;
    }
    .custom-context-menu a,
    .custom-context-menu button {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 9px 16px;
        font-size: 13.5px;
        color: #334155;
        background: transparent;
        border: none;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .custom-context-menu a:hover,
    .custom-context-menu button:hover {
        background: #f1f5f9;
        color: #4f46e5;
    }
    .custom-context-menu a.cm-danger,
    .custom-context-menu button.cm-danger {
        color: #ef4444;
    }
    .custom-context-menu a.cm-danger:hover,
    .custom-context-menu button.cm-danger:hover {
        background: #fef2f2;
        color: #dc2626;
    }
    .custom-context-menu i {
        width: 20px;
        font-size: 14px;
        margin-right: 10px;
        text-align: center;
    }
    .custom-context-menu .cm-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 4px 0;
    }
    tr.context-menu-active {
        background-color: rgba(79, 70, 229, 0.08) !important;
    }

    /* Modal Styling */
    .modal-permission-header {
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: #fff;
        border-top-left-radius: calc(0.3rem - 1px);
        border-top-right-radius: calc(0.3rem - 1px);
        padding: 16px 20px;
    }
    .modal-permission-header .close {
        color: #fff;
        opacity: 0.8;
        text-shadow: none;
    }
    .modal-permission-header .close:hover {
        opacity: 1;
    }
    .modal-auth-group-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569;
        margin-bottom: 8px;
        padding-bottom: 4px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .modal-auth-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12.5px;
        margin-bottom: 4px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        transition: all 0.15s ease;
    }
    .modal-auth-item.is-granted {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
        font-weight: 600;
    }
    .modal-auth-item.is-missing {
        color: #94a3b8;
        opacity: 0.65;
    }
    .user-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        background: #f1f5f9;
        color: #1e293b;
        font-size: 12px;
        font-weight: 500;
        margin: 3px;
        border: 1px solid #e2e8f0;
    }

    /* ==========================================
       DARK MODE OVERRIDES
       ========================================== */
    .dark-mode .page-title-text h4 { color: #f1f5f9 !important; }
    .dark-mode .page-title-text p { color: #94a3b8 !important; }
    .dark-mode .crm-kpi-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    }
    .dark-mode .crm-kpi-label { color: #94a3b8 !important; }
    .dark-mode .crm-kpi-value { color: #f8fafc !important; }
    .dark-mode .crm-kpi-footer { border-top-color: #334155 !important; }
    .dark-mode .icon-primary { background: rgba(59, 130, 246, 0.15) !important; color: #60a5fa !important; }
    .dark-mode .icon-emerald { background: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; }
    .dark-mode .icon-purple  { background: rgba(139, 92, 246, 0.15) !important; color: #a78bfa !important; }
    .dark-mode .icon-amber   { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }

    .dark-mode .soft-primary { background: rgba(59, 130, 246, 0.2) !important; color: #93c5fd !important; }
    .dark-mode .soft-emerald { background: rgba(16, 185, 129, 0.2) !important; color: #6ee7b7 !important; }
    .dark-mode .soft-purple  { background: rgba(139, 92, 246, 0.2) !important; color: #c4b5fd !important; }
    .dark-mode .soft-amber   { background: rgba(245, 158, 11, 0.2) !important; color: #fde68a !important; }

    .dark-mode .form-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
    }
    .dark-mode .form-card-header { border-bottom-color: #334155 !important; }
    .dark-mode .form-card-header .card-icon { background: #0f172a !important; color: #94a3b8 !important; }
    .dark-mode .form-card-header h5 { color: #f1f5f9 !important; }
    .dark-mode .form-card-header p { color: #94a3b8 !important; }
    .dark-mode .form-card .dataTables_wrapper .row:last-child {
        background: #151c27 !important;
        border-color: #334155 !important;
    }

    .dark-mode #tblPermissions thead th {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode #tblPermissions tbody td {
        border-top-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode #tblPermissions tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    .dark-mode .row-index-badge {
        background: #1e293b !important;
        color: #f8fafc !important;
        border-color: #475569 !important;
    }
    .dark-mode .role-title-cell { color: #f1f5f9 !important; }
    .dark-mode .badge-role-tag {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-color: #334155 !important;
    }
    .dark-mode .badge-user-pill {
        background: rgba(16, 185, 129, 0.15) !important;
        color: #6ee7b7 !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }
    .dark-mode .badge-user-empty {
        background: #0f172a !important;
        color: #64748b !important;
        border-color: #334155 !important;
    }
    .dark-mode .auth-scope-progress { background: #334155 !important; }
    .dark-mode .auth-percent-badge {
        background: rgba(79, 70, 229, 0.2) !important;
        color: #a5b4fc !important;
        border-color: rgba(79, 70, 229, 0.4) !important;
    }
    .dark-mode .auth-percent-badge.is-full {
        background: rgba(16, 185, 129, 0.2) !important;
        color: #6ee7b7 !important;
        border-color: rgba(16, 185, 129, 0.4) !important;
    }
    .dark-mode .auth-badge-pill {
        background: #0f172a !important;
        color: #cbd5e1 !important;
        border-color: #334155 !important;
    }
    .dark-mode .auth-more-pill {
        background: rgba(124, 58, 237, 0.2) !important;
        color: #c4b5fd !important;
        border-color: rgba(124, 58, 237, 0.4) !important;
    }

    .dark-mode .custom-context-menu {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
    }
    .dark-mode .custom-context-menu .cm-header {
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode .custom-context-menu a,
    .dark-mode .custom-context-menu button {
        color: #e2e8f0 !important;
    }
    .dark-mode .custom-context-menu a:hover,
    .dark-mode .custom-context-menu button:hover {
        background: #334155 !important;
        color: #818cf8 !important;
    }
    .dark-mode .custom-context-menu .cm-divider {
        background: #334155 !important;
    }
    .dark-mode .modal-content {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode .modal-auth-group-title {
        color: #cbd5e1 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode .modal-auth-item {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark-mode .modal-auth-item.is-granted {
        background: rgba(16, 185, 129, 0.15) !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
        color: #6ee7b7 !important;
    }
    .dark-mode .user-chip {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
</style>

<div class="permissions-list-page-container">
    <div class="permissions-list-wrapper">
        
        <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 12px;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-shield"></i>
                </div>
                <div class="page-title-text">
                    <h4>Pozisyon Adlandırmaları & İzin Yönetimi</h4>
                    <p>Sistem rolleri, yetki matrisleri ve kullanıcı pozisyon tanımları</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshPermissions" title="Tabloyu Yenile">
                    <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
                </button>
                <button type="button" class="btn btn-outline-success btn-action-outline" id="btnExportPermissions" title="Excel Olarak İndir">
                    <i class="fa fa-file-excel-o"></i> <span class="d-none d-sm-inline">Excel'e Aktar</span>
                </button>
                <?php if (permtrue("authdefine")) { ?>
                    <a href="index.php?p=permission-new&cc=0014" class="btn btn-action-primary">
                        <i class="fa fa-plus-circle"></i> <span>Yeni Pozisyon Ekle</span>
                    </a>
                <?php } ?>
            </div>
        </div>

        <!-- KPI Özet / İstatistik Kartları -->
        <div id="kpiSummarySection" class="row mx-0 mb-3 kpi-summary-collapse">
            <!-- Toplam Pozisyon & Rol -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Tanımlı Pozisyon / Rol</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalRoles, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-users"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Sistem Rolleri</span>
                        <span class="crm-badge-soft soft-primary">Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Toplam Sistem Yetkisi -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Sistem İzni</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalAuths, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-key"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11"><?php echo count($groupedAuths); ?> Modül Grubu</span>
                        <span class="crm-badge-soft soft-emerald">Modül Yetkisi</span>
                    </div>
                </div>
            </div>

            <!-- Rol Atanmış Personel -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Rol Atanmış Personel</span>
                            <div class="crm-kpi-value"><?php echo number_format($assignedUsers, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-purple">
                            <i class="fa fa-id-badge"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Aktif Kullanıcılar</span>
                        <span class="crm-badge-soft soft-purple">Atama Yapılmış</span>
                    </div>
                </div>
            </div>

            <!-- En Kapsamlı Rol -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">En Kapsamlı Rol</span>
                            <div class="crm-kpi-value" style="font-size: 19px; word-break: break-word;"><?php echo htmlspecialchars($topRoleName, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-amber">
                            <i class="fa fa-star"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">%<?php echo $topRoleRate; ?> Yetki Kapsamı</span>
                        <span class="crm-badge-soft soft-amber"><?php echo $topRoleAuthCount; ?> İzin</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tablo Kartı -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header">
                <div class="header-left-inner">
                    <div class="card-icon">
                        <i class="fa fa-lock"></i>
                    </div>
                    <div>
                        <h5>Rol & Yetki Matrisi Listesi</h5>
                        <p>Tanımlı kullanıcı rolleri, izin dağılımları ve atanan personeller</p>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <div class="dt-header-filter-box d-flex align-items-center"></div>
                    <button type="button" id="toggleKpiSummary" class="btn btn-outline-secondary btn-sm" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa fa-chevron-up"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tblPermissions" class="data-table table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 45px;" class="no-sort text-center">#Sıra</th>
                            <th style="width: 220px;">Pozisyon / Rol Adı</th>
                            <th style="min-width: 180px;">Açıklama</th>
                            <th style="width: 140px;" class="text-center">Atanan Personel</th>
                            <th style="min-width: 260px;">Yetki Dağılımı & Kapsam</th>
                            <th style="width: 100px;" class="no-sort text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rowIndex = 1;
                        foreach ($roles as $role): 
                            $roleId = (int)$role['id'];
                            $roleName = $role['roleName'];
                            $roleDesc = $role['roleDescription'];
                            $userCount = (int)$role['user_count'];
                            $authCount = (int)$role['auth_count'];
                            $authList = $role['authorities'];
                            $userList = $role['users'];
                            $coverageRate = $totalAuths > 0 ? round(($authCount / $totalAuths) * 100, 0) : 0;
                            $isAdmin = ($roleId === 1);
                        ?>
                        <tr data-role-id="<?php echo $roleId; ?>" data-role-name="<?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>">
                            <td class="text-center">
                                <span class="row-index-badge"><?php echo $rowIndex++; ?></span>
                            </td>
                            <td>
                                <div class="role-title-cell">
                                    <span class="badge-role-tag">#<?php echo $roleId; ?></span>
                                    <span><?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($isAdmin): ?>
                                        <span class="badge-role-admin"><i class="fa fa-shield"></i> Admin</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($roleDesc)): ?>
                                    <span class="text-secondary"><?php echo htmlspecialchars($roleDesc, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted font-italic" style="font-size: 12px;">Açıklama belirtilmemiş</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($userCount > 0): ?>
                                    <span class="badge-user-pill btn-view-users" data-role-id="<?php echo $roleId; ?>" title="Atanmış kullanıcıları gör">
                                        <i class="fa fa-user"></i> <?php echo $userCount; ?> Kullanıcı
                                    </span>
                                <?php else: ?>
                                    <span class="badge-user-pill badge-user-empty">
                                        <i class="fa fa-user-o"></i> Atama Yok
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="auth-scope-container">
                                    <div class="d-flex justify-content-between align-items-center font-12 mb-1">
                                        <span class="font-weight-bold" style="color: #4f46e5;">
                                             <i class="fa fa-check-circle mr-1"></i> <?php echo $authCount; ?> / <?php echo $totalAuths; ?> İzin
                                         </span>
                                        <span class="auth-percent-badge <?php echo ($coverageRate >= 100) ? 'is-full' : ''; ?>">%<?php echo $coverageRate; ?></span>
                                    </div>
                                    <div class="auth-scope-progress">
                                        <div class="auth-scope-progress-bar" style="width: <?php echo min(100, $coverageRate); ?>%;"></div>
                                    </div>
                                    <div class="mt-2 d-flex flex-wrap align-items-center" style="gap: 3px;">
                                        <?php 
                                        $previewLimit = 3;
                                        $previewAuths = array_slice($authList, 0, $previewLimit);
                                        foreach ($previewAuths as $pa): ?>
                                            <span class="auth-badge-pill"><?php echo htmlspecialchars($pa['authTitle'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($authList) > $previewLimit): ?>
                                            <span class="auth-more-pill btn-view-auths" data-role-id="<?php echo $roleId; ?>" title="Tüm yetkileri listele">
                                                +<?php echo count($authList) - $previewLimit; ?> daha
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="action-btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-info action-btn btn-view-details" data-role-id="<?php echo $roleId; ?>" title="Pozisyon & Yetki Detayları">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <?php if (permtrue("authEdit")): ?>
                                        <a href="index.php?p=permission-edit&reg=true&md=update&id=<?php echo $roleId; ?>" class="btn btn-sm btn-outline-primary action-btn" title="Pozisyonu Düzenle">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$isAdmin && permtrue("authDel")): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger action-btn btn-delete-role" data-role-id="<?php echo $roleId; ?>" data-role-name="<?php echo htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>" title="Pozisyonu Sil">
                                            <i class="fa fa-trash-o"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Pozisyon & Yetki Detay Modalı -->
<div class="modal fade" id="roleDetailModal" tabindex="-1" role="dialog" aria-labelledby="roleDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header modal-permission-header">
                <div>
                    <h5 class="modal-title font-weight-bold text-white mb-0" id="modalRoleTitle">
                        <i class="fa fa-shield mr-2"></i> Rol Detayları
                    </h5>
                    <p class="mb-0 text-white-50 font-12" id="modalRoleSubtitle">Yetki kapsamı ve atanan kullanıcılar</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                
                <!-- Rol Bilgi Özeti -->
                <div class="p-3 mb-3 rounded border" style="background: rgba(79, 70, 229, 0.04); border-color: rgba(79, 70, 229, 0.15) !important;">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="font-weight-bold mb-1" id="modalInfoRoleName"></h6>
                            <p class="text-muted font-13 mb-0" id="modalInfoRoleDesc"></p>
                        </div>
                        <div class="col-md-4 text-md-right mt-2 mt-md-0">
                            <span class="badge badge-primary font-12 p-2 px-3" id="modalInfoAuthCount"></span>
                        </div>
                    </div>
                </div>

                <!-- Atanmış Kullanıcılar Bölümü -->
                <div class="mb-4">
                    <h6 class="font-weight-bold text-uppercase font-12 text-muted mb-2">
                        <i class="fa fa-users mr-1"></i> Atanmış Personeller (<span id="modalUserCount">0</span>)
                    </h6>
                    <div id="modalUsersList" class="d-flex flex-wrap align-items-center">
                        <!-- JS ile doldurulur -->
                    </div>
                </div>

                <!-- Yetki Matrisi Bölümü -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 8px;">
                        <h6 class="font-weight-bold text-uppercase font-12 text-muted mb-0">
                            <i class="fa fa-th-large mr-1"></i> Modül Yetki Matrisi
                        </h6>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary active" id="btnFilterAllAuths">Tümü</button>
                            <button type="button" class="btn btn-outline-primary" id="btnFilterGrantedAuths">Sadece Tanımlılar</button>
                        </div>
                    </div>
                    <div id="modalAuthGrid" class="row">
                        <!-- JS ile doldurulur -->
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Kapat</button>
                <a href="#" id="modalBtnEditRole" class="btn btn-primary btn-sm">
                    <i class="fa fa-pencil mr-1"></i> Pozisyonu Düzenle
                </a>
            </div>
        </div>
    </div>
</div>

<!-- JSON Veri Deposu (Roller ve Tüm Yetkiler) -->
<script>
    var ROLES_DATA = <?php echo json_encode($roles, JSON_UNESCAPED_UNICODE); ?>;
    var GROUPED_AUTHS = <?php echo json_encode($groupedAuths, JSON_UNESCAPED_UNICODE); ?>;
    var GROUP_DEFS = <?php echo json_encode($groupDefs, JSON_UNESCAPED_UNICODE); ?>;
</script>

<script>
$(document).ready(function () {
    // 1. KPI Summary Toggle & LocalStorage
    $("html").removeClass("kpi-permissions-collapsed-early");
    var isKpiCollapsed = localStorage.getItem("aydinogullari_kpi_permissions_collapsed") === "true";
    if (isKpiCollapsed) {
        $("#kpiSummarySection").addClass("is-collapsed");
        $("#toggleKpiSummary i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
    }

    $(document).on("click", "#toggleKpiSummary", function () {
        var $kpi = $("#kpiSummarySection");
        var willCollapse = !$kpi.hasClass("is-collapsed");

        if (willCollapse) {
            $kpi.addClass("is-collapsed");
            $(this).find("i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
            localStorage.setItem("aydinogullari_kpi_permissions_collapsed", "true");
        } else {
            $kpi.removeClass("is-collapsed");
            $(this).find("i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
            localStorage.setItem("aydinogullari_kpi_permissions_collapsed", "false");
        }
    });

    // 2. DataTable Başlatma
    var dtPermissions = $("#tblPermissions").DataTable({
        autoWidth: false,
        responsive: false,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        language: {
            url: "include/js/tr.json"
        },
        order: [[0, "asc"]],
        columnDefs: [
            { orderable: false, targets: [0, 5] }
        ],
        initComplete: function () {
            if (window.App && window.App.TableFilter) {
                App.TableFilter.attachToTable(this.api().table().node());
                App.TableFilter.relocateSearchInput(this.api().table().node());
            }
        }
    });

    // 3. Tabloyu Yenile Butonu
    $("#btnRefreshPermissions").on("click", function () {
        var $icon = $(this).find("i");
        $icon.addClass("fa-spin");
        setTimeout(function() {
            window.location.reload();
        }, 300);
    });

    // 4. Excel Dışa Aktarma
    $("#btnExportPermissions").on("click", function () {
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Hazırlanıyor...');

        try {
            var excelData = [
                ['Sıra', 'Pozisyon / Rol ID', 'Pozisyon Adı', 'Açıklama', 'Atanan Personel Sayısı', 'Yetki Sayısı', 'Yetkiler Listesi']
            ];

            ROLES_DATA.forEach(function (r, index) {
                var authTitles = (r.authorities || []).map(function(a) { return a.authTitle; }).join(', ');
                excelData.push([
                    index + 1,
                    r.id,
                    r.roleName,
                    r.roleDescription || '',
                    r.user_count,
                    r.auth_count,
                    authTitles
                ]);
            });

            if (typeof XLSX !== 'undefined') {
                var ws = XLSX.utils.aoa_to_sheet(excelData);
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Pozisyonlar & Yetkiler");
                var today = new Date().toISOString().slice(0, 10);
                XLSX.writeFile(wb, "Pozisyon_Yetki_Listesi_" + today + ".xlsx");
            } else {
                // Fallback CSV
                var csvContent = "data:text/csv;charset=utf-8,\uFEFF" + excelData.map(function(e) {
                    return e.map(function(cell) { return '"' + String(cell).replace(/"/g, '""') + '"'; }).join(";");
                }).join("\r\n");
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "Pozisyon_Yetki_Listesi.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        } catch(e) {
            console.error("Export Error:", e);
        } finally {
            $btn.prop("disabled", false).html(originalHtml);
        }
    });

    // 5. Rol Detay Modalı Gösterimi
    function openRoleDetailModal(roleId) {
        var role = ROLES_DATA.find(function(r) { return parseInt(r.id) === parseInt(roleId); });
        if (!role) return;

        $("#modalRoleTitle").html('<i class="fa fa-shield mr-2"></i> ' + $("<div>").text(role.roleName).html());
        $("#modalInfoRoleName").text(role.roleName + (parseInt(role.id) === 1 ? " (Süper Admin)" : ""));
        $("#modalInfoRoleDesc").text(role.roleDescription || "Bu pozisyon için özel açıklama girilmemiş.");
        $("#modalInfoAuthCount").text(role.auth_count + " / <?php echo $totalAuths; ?> İzin Tanımlı");
        $("#modalBtnEditRole").attr("href", "index.php?p=permission-edit&reg=true&md=update&id=" + role.id);

        // Kullanıcılar
        $("#modalUserCount").text(role.user_count);
        var $usersContainer = $("#modalUsersList").empty();
        if (role.users && role.users.length > 0) {
            role.users.forEach(function(u) {
                var title = u.Unvan ? (" (" + u.Unvan + ")") : "";
                $usersContainer.append(
                    '<span class="user-chip"><i class="fa fa-user-circle text-primary"></i> ' + 
                    $("<div>").text(u.username + title).html() + '</span>'
                );
            });
        } else {
            $usersContainer.append('<span class="text-muted font-12 font-italic">Bu pozisyona henüz atanmış personel bulunmuyor.</span>');
        }

        // Yetkiler Matrisi
        renderAuthGrid(role, $("#btnFilterGrantedAuths").hasClass("active"));

        // Modal Göster
        $("#roleDetailModal").modal("show");
    }

    function renderAuthGrid(role, onlyGranted) {
        var $grid = $("#modalAuthGrid").empty();
        var grantedIds = (role.authorities || []).map(function(a) { return parseInt(a.id); });

        Object.keys(GROUPED_AUTHS).forEach(function(groupKey) {
            var auths = GROUPED_AUTHS[groupKey] || [];
            var gDef = GROUP_DEFS[groupKey] || { title: "Grup #" + groupKey, icon: "fa fa-folder-open-o", color: "#475569" };
            var groupName = gDef.title || ("Grup #" + groupKey);
            var groupIcon = gDef.icon || "fa fa-folder-open-o";

            var groupItemsHtml = "";

            auths.forEach(function(auth) {
                var isGranted = grantedIds.indexOf(parseInt(auth.id)) !== -1;

                if (!onlyGranted || isGranted) {
                    var badgeClass = isGranted ? "is-granted" : "is-missing";
                    var iconHtml = isGranted 
                        ? '<i class="fa fa-check-circle text-success mr-1"></i>' 
                        : '<i class="fa fa-times-circle text-muted mr-1"></i>';

                    groupItemsHtml += '<div class="col-sm-6 mb-1">' +
                        '<div class="modal-auth-item ' + badgeClass + '">' +
                            iconHtml + '<span>' + $("<div>").text(auth.authTitle).html() + '</span>' +
                        '</div>' +
                    '</div>';
                }
            });

            if (groupItemsHtml !== "") {
                var colHtml = '<div class="col-md-12 mb-3">' +
                    '<div class="modal-auth-group-title">' +
                        '<span><i class="' + groupIcon + ' mr-1"></i> ' + $("<div>").text(groupName).html() + '</span>' +
                    '</div>' +
                    '<div class="row">' + groupItemsHtml + '</div>' +
                '</div>';
                $grid.append(colHtml);
            }
        });

        if ($grid.children().length === 0) {
            $grid.html('<div class="col-12 text-center text-muted py-4"><i class="fa fa-info-circle mr-1"></i> Görüntülenecek izin bulunamadı.</div>');
        }
    }

    // Modal Yetki Filtre Butonları
    $("#btnFilterAllAuths").on("click", function() {
        $(this).addClass("active");
        $("#btnFilterGrantedAuths").removeClass("active");
        var roleId = $("#modalBtnEditRole").attr("href").split("id=")[1];
        var role = ROLES_DATA.find(function(r) { return parseInt(r.id) === parseInt(roleId); });
        if (role) renderAuthGrid(role, false);
    });

    $("#btnFilterGrantedAuths").on("click", function() {
        $(this).addClass("active");
        $("#btnFilterAllAuths").removeClass("active");
        var roleId = $("#modalBtnEditRole").attr("href").split("id=")[1];
        var role = ROLES_DATA.find(function(r) { return parseInt(r.id) === parseInt(roleId); });
        if (role) renderAuthGrid(role, true);
    });

    // Detay Butonları Tıklamaları
    $(document).on("click", ".btn-view-details, .btn-view-auths, .btn-view-users", function(e) {
        e.preventDefault();
        var roleId = $(this).data("role-id");
        openRoleDetailModal(roleId);
    });

    // 6. Rol Silme İşlemi (SweetAlert2 & Model/AJAX)
    $(document).on("click", ".btn-delete-role", function() {
        var roleId = $(this).data("role-id");
        var roleName = $(this).data("role-name") || "Bu pozisyon";

        if (parseInt(roleId) === 1) {
            Swal.fire({
                icon: "warning",
                title: "İşlem Engellendi",
                text: "Yönetici (Admin) rolü sistem güvenliği gereği silinemez!"
            });
            return;
        }

        Swal.fire({
            title: "Pozisyonu Silmek İstediğinize Emin misiniz?",
            html: "<b>" + $("<div>").text(roleName).html() + "</b> pozisyonunu silmeniz durumunda bu role sahip tüm personellerin hesap yetkileri dondurulacaktır.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#64748b",
            confirmButtonText: '<i class="fa fa-trash"></i> Evet, Sil!',
            cancelButtonText: "Vazgeç"
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "pages/1/ajax.php?mode=delete&code=04md177&id=" + roleId,
                    data: {
                        id: roleId,
                        page: "permission-settings"
                    },
                    dataType: "json",
                    success: function (res) {
                        if (res.status == 200) {
                            Swal.fire({
                                title: "Başarılı!",
                                text: res.message || "Pozisyon başarıyla silindi.",
                                icon: "success"
                            }).then(function() {
                                window.location.href = "index.php?p=permission-settings";
                            });
                        } else {
                            Swal.fire({
                                title: "Hata!",
                                text: res.message || "Pozisyon silinemedi.",
                                icon: "error"
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            title: "Hata!",
                            text: "İşlem sırasında bir hata oluştu.",
                            icon: "error"
                        });
                    }
                });
            }
        });
    });

    // 7. Sağ Tık (Context Menu)
    $(document).on("contextmenu", "#tblPermissions tbody tr", function (e) {
        if ($(this).find("td").length <= 1) return;

        e.preventDefault();

        var $tr = $(this);
        $("#tblPermissions tbody tr").removeClass("context-menu-active");
        $tr.addClass("context-menu-active");

        var roleId = $tr.data("role-id");
        var roleName = $tr.data("role-name") || "Pozisyon";
        var isAdmin = (parseInt(roleId) === 1);

        var menuHtml = '<div class="cm-header"><i class="fa fa-shield mr-1 text-primary"></i> ' + $("<div>").text(roleName).html() + '</div>';

        menuHtml += '<button type="button" class="cm-action-view" data-role-id="' + roleId + '"><i class="fa fa-eye text-info mr-2"></i> Detayları & Yetkileri Gör</button>';

        <?php if (permtrue("authEdit")): ?>
            menuHtml += '<a href="index.php?p=permission-edit&reg=true&md=update&id=' + roleId + '"><i class="fa fa-pencil text-primary mr-2"></i> Pozisyonu Düzenle</a>';
        <?php endif; ?>

        <?php if (permtrue("authdefine")): ?>
            menuHtml += '<a href="index.php?p=permission-new&cc=0014"><i class="fa fa-plus-circle text-success mr-2"></i> Yeni Pozisyon Oluştur</a>';
        <?php endif; ?>

        if (!isAdmin && <?php echo permtrue("authDel") ? "true" : "false"; ?>) {
            menuHtml += '<div class="cm-divider"></div>';
            menuHtml += '<button type="button" class="btn-delete-role cm-danger" data-role-id="' + roleId + '" data-role-name="' + $("<div>").text(roleName).html() + '"><i class="fa fa-trash-o text-danger mr-2"></i> Pozisyonu Sil</button>';
        }

        var $contextMenu = $("#customContextMenu");
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo("body");
        }

        $contextMenu.html(menuHtml);

        var mouseX = e.clientX;
        var mouseY = e.clientY;

        $contextMenu.css({ display: "block", visibility: "hidden" });
        var menuWidth = $contextMenu.outerWidth();
        var menuHeight = $contextMenu.outerHeight();
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();

        if (mouseX + menuWidth > windowWidth) mouseX = windowWidth - menuWidth - 10;
        if (mouseY + menuHeight > windowHeight) mouseY = windowHeight - menuHeight - 10;

        $contextMenu.css({
            top: mouseY + "px",
            left: mouseX + "px",
            visibility: "visible",
            opacity: "1"
        });
    });

    $(document).on("click", ".cm-action-view", function(e) {
        e.preventDefault();
        var roleId = $(this).data("role-id");
        openRoleDetailModal(roleId);
    });

    // Menü dışına tıklanınca kapat
    $(document).on("click scroll", function (e) {
        if (!$(e.target).closest("#customContextMenu").length) {
            $("#customContextMenu").hide();
            $("#tblPermissions tbody tr").removeClass("context-menu-active");
        }
    });

    $(document).on("click", "#customContextMenu a, #customContextMenu button", function () {
        $("#customContextMenu").hide();
        $("#tblPermissions tbody tr").removeClass("context-menu-active");
    });

    $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
            $("#customContextMenu").hide();
            $("#tblPermissions tbody tr").removeClass("context-menu-active");
        }
    });
});
</script>
