<?php

$cid = @$_GET["cid"]; // customer id
$sid = @$_GET["id"];
permcontrol("serviceView");

use App\Helper\Helper;
use App\Helper\Security;
use App\Model\ServiceModel;
use App\Model\UnitsModel;

$ServiceModel = new ServiceModel();
$Units = new UnitsModel();

// KPI İstatistikleri
$stats = $ServiceModel->getSummaryStats();
$totalCount = (int) ($stats['total_count'] ?? 0);
$bekleyenCount = (int) ($stats['bekleyen_count'] ?? 0);
$calisilanCount = (int) ($stats['calisilan_count'] ?? 0);
$tamamlananCount = (int) ($stats['tamamlanan_count'] ?? 0);
$iptalCount = (int) ($stats['iptal_count'] ?? 0);

// Yetki kontrolleri
$canEdit = permtrue("serviceEdit");
$canDel = permtrue("serviceDel");
$canAccountingReceipt = permtrue("muhasebe_teslim_alma_yetkisi");

try {
    $logger = \getLogger("Servisler");
    $logger->info("Servis listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}

try {
    $ac->exec("CREATE TABLE IF NOT EXISTS service_accounting_receipt_logs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id INT UNSIGNED NOT NULL,
        action VARCHAR(20) NOT NULL,
        action_by INT UNSIGNED NOT NULL,
        action_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_service_id (service_id),
        KEY idx_action_at (action_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Tablo oluşturulamasa da liste ekranı çalışmaya devam etsin.
}

// Optimize edilmiş tek sorgu ile tüm verileri çek
if ($cid) {
    $query = $ac->prepare("
        SELECT p.*,
               c.company as company_name,
               c.deleted_at as customer_deleted_at,
               r.title as region_name,
               s.title as service_title, 
               u.username as creator_username,
             uu.username as updater_username,
             ar.action as accounting_action,
             ar.action_at as accounting_action_at,
             au.username as accounting_actor_username,
               cs.title as contract_status_title,
               cs.colour as contract_status_color,
               st.title as status_title,
               st.colour as status_color
        FROM projects p
        LEFT JOIN customers c ON c.id = p.pcid
        LEFT JOIN units r ON r.id = p.region
        LEFT JOIN units s ON s.id = p.servicestype
        LEFT JOIN users u ON u.id = p.pcreativer
        LEFT JOIN users uu ON uu.id = p.updater
        LEFT JOIN (
            SELECT l.service_id, l.action, l.action_by, l.action_at
            FROM service_accounting_receipt_logs l
            INNER JOIN (
                SELECT service_id, MAX(id) as max_id
                FROM service_accounting_receipt_logs
                GROUP BY service_id
            ) lm ON lm.max_id = l.id
        ) ar ON ar.service_id = p.id
        LEFT JOIN users au ON au.id = ar.action_by
        LEFT JOIN units cs ON cs.id = p.contract_statu AND cs.statu = 4
        LEFT JOIN units st ON st.id = p.pstatu AND st.statu = 4
        WHERE p.pcid = ? 
        ORDER BY p.id desc
    ");
    $query->execute(array($cid));
} else if ($sid) {
    $query = $ac->prepare("
        SELECT p.*,
               c.company as company_name,
               c.deleted_at as customer_deleted_at,
               r.title as region_name,
               s.title as service_title, 
               u.username as creator_username,
             uu.username as updater_username,
             ar.action as accounting_action,
             ar.action_at as accounting_action_at,
             au.username as accounting_actor_username,
               cs.title as contract_status_title,
               cs.colour as contract_status_color,
               st.title as status_title,
               st.colour as status_color
        FROM projects p
        LEFT JOIN customers c ON c.id = p.pcid
        LEFT JOIN units r ON r.id = p.region
        LEFT JOIN units s ON s.id = p.servicestype
        LEFT JOIN users u ON u.id = p.pcreativer
        LEFT JOIN users uu ON uu.id = p.updater
        LEFT JOIN (
            SELECT l.service_id, l.action, l.action_by, l.action_at
            FROM service_accounting_receipt_logs l
            INNER JOIN (
                SELECT service_id, MAX(id) as max_id
                FROM service_accounting_receipt_logs
                GROUP BY service_id
            ) lm ON lm.max_id = l.id
        ) ar ON ar.service_id = p.id
        LEFT JOIN users au ON au.id = ar.action_by
        LEFT JOIN units cs ON cs.id = p.contract_statu AND cs.statu = 4
        LEFT JOIN units st ON st.id = p.pstatu AND st.statu = 4
        WHERE p.id = ? 
        ORDER BY p.id desc
    ");
    $query->execute(array($sid));
} else {
    $query = $ac->prepare("
        SELECT p.*,
               c.company as company_name,
               c.deleted_at as customer_deleted_at,
               r.title as region_name,
               s.title as service_title, 
               u.username as creator_username,
             uu.username as updater_username,
             ar.action as accounting_action,
             ar.action_at as accounting_action_at,
             au.username as accounting_actor_username,
               cs.title as contract_status_title,
               cs.colour as contract_status_color,
               st.title as status_title,
               st.colour as status_color
        FROM projects p
        LEFT JOIN customers c ON c.id = p.pcid
        LEFT JOIN units r ON r.id = p.region
        LEFT JOIN units s ON s.id = p.servicestype
        LEFT JOIN users u ON u.id = p.pcreativer
        LEFT JOIN users uu ON uu.id = p.updater
        LEFT JOIN (
            SELECT l.service_id, l.action, l.action_by, l.action_at
            FROM service_accounting_receipt_logs l
            INNER JOIN (
                SELECT service_id, MAX(id) as max_id
                FROM service_accounting_receipt_logs
                GROUP BY service_id
            ) lm ON lm.max_id = l.id
        ) ar ON ar.service_id = p.id
        LEFT JOIN users au ON au.id = ar.action_by
        LEFT JOIN units cs ON cs.id = p.contract_statu AND cs.statu = 4
        LEFT JOIN units st ON st.id = p.pstatu AND st.statu = 4
        ORDER BY p.id desc
    ");
    $query->execute();
}

$projects = $query->fetchAll(PDO::FETCH_ASSOC);



// Server-side processing için gerekli değişkenleri tanımla
$use_server_side = true; // Server-side processing aktif
$ajax_url = "api/services_datatables.php";

// Eğer spesifik bir müşteri veya servis ID'si varsa, server-side processing'i devre dışı bırak
if ($cid || $sid) {
    $use_server_side = false;
}

?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_services_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-services-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* ==========================================
       PREMIUM SERVICES LIST THEME (NO-SCROLL OPTIMIZED)
       ========================================== */
    .kpi-services-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    .services-list-wrapper {
        width: 100%;
        margin: 0;
        padding: 0;
    }

    /* Page Header Styles */
    .page-title-box {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .page-title-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
    }
    .page-title-text h4 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.3px;
    }
    .page-title-text p {
        margin: 1px 0 0 0;
        font-size: 12px;
        color: #64748b;
    }

    /* KPI Summary Cards */
    .crm-kpi-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 14px 16px;
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
        margin-bottom: 8px;
    }
    .crm-kpi-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        display: block;
        margin-bottom: 2px;
    }
    .crm-kpi-value {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.1;
    }
    .crm-kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .icon-primary { background: #eff6ff; color: #2563eb; }
    .icon-emerald { background: #ecfdf5; color: #059669; }
    .icon-sky     { background: #f0f9ff; color: #0284c7; }
    .icon-amber   { background: #fffbeb; color: #d97706; }
    .icon-rose    { background: #fff1f2; color: #e11d48; }

    .crm-kpi-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
        font-size: 11px;
    }
    .crm-badge-soft {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 10.5px;
    }
    .soft-primary { background: #dbeafe; color: #1e40af; }
    .soft-emerald { background: #d1fae5; color: #065f46; }
    .soft-sky     { background: #e0f2fe; color: #0369a1; }
    .soft-amber   { background: #fef3c7; color: #92400e; }
    .soft-rose    { background: #ffe4e6; color: #9f1239; }

    /* Form & Table Card styling */
    .form-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 0 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        margin-bottom: 0;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 10px;
    }
    .form-card-header .header-left-inner {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-card-header .card-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        background: #f1f5f9;
        color: #475569;
    }
    .form-card-header h5 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
    }
    .form-card-header p {
        margin: 1px 0 0 0;
        font-size: 11.5px;
        color: #64748b;
    }

    /* Action Buttons in Header */
    .btn-action-primary {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff !important;
        border: none;
        border-radius: 6px;
        padding: 6px 14px;
        font-weight: 600;
        font-size: 12.5px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);
        transition: all 0.2s ease;
        height: 34px;
        text-decoration: none;
    }
    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(2, 132, 199, 0.35);
        color: #fff !important;
    }
    .btn-action-outline {
        border-radius: 6px;
        padding: 6px 12px;
        height: 34px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
    }

    /* DataTables Container & Reset Spacing */
    .form-card .table-responsive {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
    }
    .form-card .dataTables_wrapper {
        padding: 0 !important;
        width: 100% !important;
    }
    .form-card .dataTables_wrapper .row:first-child {
        display: none !important;
        margin: 0 !important;
    }
    .form-card .dataTables_wrapper .row:last-child {
        padding: 10px 16px !important;
        margin: 0 !important;
        border-top: 1px solid #f1f5f9;
        background: #fafafa;
    }

    /* Force Reset min-width imposed by global styles */
    .services-list-wrapper table,
    .services-list-wrapper table th,
    .services-list-wrapper table td,
    .services-list-wrapper .table th,
    .services-list-wrapper .table td,
    .services-list-wrapper .data-table th,
    .services-list-wrapper .data-table td,
    #service-table th,
    #service-table td {
        min-width: 0 !important;
        box-sizing: border-box !important;
    }

    /* Custom Table & Badge Styles (Compact & No-Scroll) */
    #service-table {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        table-layout: fixed !important;
    }
    #service-table thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.1px;
        padding: 7px 3px !important;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        vertical-align: middle;
        position: relative !important;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #service-table thead th.sorting,
    #service-table thead th.sorting_asc,
    #service-table thead th.sorting_desc {
        padding-left: 18px !important;
        padding-right: 18px !important;
    }
    #service-table thead th.no-sort {
        padding-left: 3px !important;
        padding-right: 3px !important;
    }
    #service-table thead th.sorting:before,
    #service-table thead th.sorting_asc:before,
    #service-table thead th.sorting_desc:before {
        left: 4px !important;
        right: auto !important;
        bottom: 50% !important;
        transform: translateY(50%) !important;
        line-height: 1 !important;
        opacity: 0.4;
        font-size: 9px !important;
    }
    #service-table thead th.sorting:after,
    #service-table thead th.sorting_asc:after,
    #service-table thead th.sorting_desc:after {
        left: 10px !important;
        right: auto !important;
        bottom: 50% !important;
        transform: translateY(50%) !important;
        line-height: 1 !important;
        opacity: 0.4;
        font-size: 9px !important;
    }
    #service-table thead th .tf-trigger {
        position: absolute !important;
        right: 2px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin: 0 !important;
        padding: 1px 3px !important;
        font-size: 10px !important;
    }

    #service-table th:first-child,
    #service-table td:first-child {
        width: 36px !important;
        min-width: 36px !important;
        max-width: 36px !important;
        padding: 5px 2px !important;
        text-align: center !important;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
    }
    .dark-mode #service-table td:first-child {
        color: #e2e8f0 !important;
    }

    #service-table tbody td {
        padding: 5px 3px !important;
        vertical-align: middle !important;
        font-size: 12px;
        border-top: 1px solid #f1f5f9;
        word-break: break-word;
    }
    #service-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Badges */
    .badge-sku {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 2px 5px;
        font-size: 11.5px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-unit {
        display: inline-flex;
        align-items: center;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 4px;
        padding: 1px 5px;
        font-size: 11px;
        font-weight: 500;
    }

    .service-company-cell {
        font-size: 12.5px;
        line-height: 1.25;
        color: #1e293b;
    }
    .service-title-cell {
        font-size: 12px;
        color: #334155;
        font-weight: 500;
        line-height: 1.25;
    }

    /* Dropdown Menü Stilleri */
    .action-dropdown-btn {
        padding: 3px 8px !important;
        font-size: 11.5px !important;
        font-weight: 500;
        border-radius: 5px !important;
        transition: all 0.15s ease;
    }
    .dropdown-menu-detail {
        box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        border: 1px solid rgba(0,0,0,0.08);
        padding: 6px 0;
        min-width: 175px;
    }
    .dropdown-menu-detail .dropdown-item {
        padding: 7px 14px;
        font-size: 12px;
        font-weight: 500;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: all 0.15s ease;
        background: transparent;
        border: none;
        width: 100%;
        text-align: left;
    }
    .dropdown-menu-detail .dropdown-item i {
        width: 18px;
        font-size: 13px;
        margin-right: 8px;
        text-align: center;
    }
    .dropdown-menu-detail .dropdown-item:hover {
        background-color: #f1f5f9;
        color: #0284c7;
    }
    .dropdown-menu-detail .dropdown-divider {
        margin: 4px 0;
        border-top: 1px solid #e2e8f0;
    }

    /* Sağ Tık (Context Menu) Stilleri */
    .custom-context-menu {
        display: none;
        position: fixed;
        z-index: 99999;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.08);
        padding: 8px 0;
        min-width: 220px;
        backdrop-filter: blur(8px);
        transition: opacity 0.15s ease, transform 0.15s ease;
    }
    .dark-mode .custom-context-menu {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
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
        max-width: 280px;
    }
    .dark-mode .custom-context-menu .cm-header {
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .custom-context-menu a,
    .custom-context-menu button {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 9px 16px;
        font-size: 13px;
        color: #334155;
        background: transparent;
        border: none;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .dark-mode .custom-context-menu a,
    .dark-mode .custom-context-menu button {
        color: #e2e8f0 !important;
    }
    .custom-context-menu a:hover,
    .custom-context-menu button:hover {
        background: #f1f5f9;
        color: #0284c7;
    }
    .dark-mode .custom-context-menu a:hover,
    .dark-mode .custom-context-menu button:hover {
        background: #334155 !important;
        color: #38bdf8 !important;
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
    .dark-mode .custom-context-menu a.cm-danger:hover,
    .dark-mode .custom-context-menu button.cm-danger:hover {
        background: rgba(239, 68, 68, 0.15) !important;
        color: #f87171 !important;
    }
    .custom-context-menu i {
        width: 20px;
        font-size: 13.5px;
        margin-right: 10px;
        text-align: center;
    }
    .custom-context-menu .cm-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 4px 0;
    }
    .dark-mode .custom-context-menu .cm-divider {
        background: #334155 !important;
    }
    tr.context-menu-active {
        background-color: rgba(2, 132, 199, 0.08) !important;
    }

    /* Dropdown Dark Mode */
    .dark-mode .dropdown-menu-detail {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important;
    }
    .dark-mode .dropdown-menu-detail .dropdown-item {
        color: #e2e8f0 !important;
    }
    .dark-mode .dropdown-menu-detail .dropdown-item:hover {
        background-color: #334155 !important;
        color: #38bdf8 !important;
    }
    .dark-mode .dropdown-menu-detail .dropdown-divider {
        border-top-color: #334155 !important;
    }

    /* ==========================================
       DARK MODE OVERRIDES
       ========================================== */
    .dark-mode .page-title-text h4 {
        color: #f1f5f9 !important;
    }
    .dark-mode .page-title-text p {
        color: #94a3b8 !important;
    }
    .dark-mode .crm-kpi-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    }
    .dark-mode .crm-kpi-label {
        color: #94a3b8 !important;
    }
    .dark-mode .crm-kpi-value {
        color: #f8fafc !important;
    }
    .dark-mode .crm-kpi-footer {
        border-top-color: #334155 !important;
    }
    .dark-mode .icon-primary { background: rgba(59, 130, 246, 0.15) !important; color: #60a5fa !important; }
    .dark-mode .icon-emerald { background: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; }
    .dark-mode .icon-sky     { background: rgba(2, 132, 199, 0.15) !important; color: #38bdf8 !important; }
    .dark-mode .icon-amber   { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }
    .dark-mode .icon-rose    { background: rgba(225, 29, 72, 0.15) !important; color: #fb7185 !important; }

    .dark-mode .soft-primary { background: rgba(59, 130, 246, 0.2) !important; color: #93c5fd !important; }
    .dark-mode .soft-emerald { background: rgba(16, 185, 129, 0.2) !important; color: #6ee7b7 !important; }
    .dark-mode .soft-sky     { background: rgba(2, 132, 199, 0.2) !important; color: #7dd3fc !important; }
    .dark-mode .soft-amber   { background: rgba(245, 158, 11, 0.2) !important; color: #fde68a !important; }
    .dark-mode .soft-rose    { background: rgba(225, 29, 72, 0.2) !important; color: #fecdd3 !important; }

    .dark-mode .form-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
    }
    .dark-mode .form-card-header {
        border-bottom-color: #334155 !important;
    }
    .dark-mode .form-card-header .card-icon {
        background: #0f172a !important;
        color: #94a3b8 !important;
    }
    .dark-mode .form-card-header h5 {
        color: #f1f5f9 !important;
    }
    .dark-mode .form-card-header p {
        color: #94a3b8 !important;
    }

    .dark-mode .form-card .dataTables_wrapper .row:last-child {
        background: #151c27 !important;
        border-top-color: #334155 !important;
    }

    .dark-mode #service-table thead th {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode #service-table tbody td {
        border-top-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode #service-table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    .dark-mode .badge-sku {
        background: #0f172a !important;
        color: #cbd5e1 !important;
        border-color: #334155 !important;
    }
    .dark-mode .badge-unit {
        background: rgba(59, 130, 246, 0.15) !important;
        color: #93c5fd !important;
        border-color: rgba(59, 130, 246, 0.3) !important;
    }
    .dark-mode .service-company-cell span {
        color: #f1f5f9 !important;
    }
    .dark-mode .service-title-cell {
        color: #cbd5e1 !important;
    }
    .services-list-page-container {
        padding: 0;
        width: 100%;
    }
</style>

<div class="services-list-page-container">
    <div class="services-list-wrapper">
        
        <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 12px; padding: 0;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-wrench"></i>
                </div>
                <div class="page-title-text">
                    <h4>Servis & İş Emirleri Yönetimi</h4>
                    <p>Sistemdeki tüm servis talepleri, iş emirleri, planlama ve durum takibi</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshServices" title="Tabloyu Yenile">
                    <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
                </button>
                <?php if (permtrue("data_export_service")) { ?>
                    <button type="button" class="btn btn-outline-success btn-action-outline" id="exportExcel" title="Excel Olarak İndir">
                        <i class="fa fa-file-excel-o"></i> <span class="d-none d-sm-inline">Excel'e Aktar</span>
                    </button>
                <?php } ?>
                <?php if (permtrue("serviceAdd")) { ?>
                    <a href="index.php?p=service/manage" class="btn btn-action-primary">
                        <i class="fa fa-plus-circle"></i> <span>Yeni Servis Oluştur</span>
                    </a>
                <?php } ?>
            </div>
        </div>

        <!-- KPI Özet / İstatistik Kartları -->
        <div id="kpiSummarySection" class="row mx-0 mb-3 kpi-summary-collapse">
            <!-- Toplam Servis -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Servis</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-wrench"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Kayıtlı İş Emri</span>
                        <span class="crm-badge-soft soft-primary"><?php echo $iptalCount; ?> İptal</span>
                    </div>
                </div>
            </div>

            <!-- Bekleyen Servisler -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Bekleyen Servisler</span>
                            <div class="crm-kpi-value"><?php echo number_format($bekleyenCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-amber">
                            <i class="fa fa-hourglass-half"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">İşlem Bekliyor</span>
                        <span class="crm-badge-soft soft-amber">Beklemede</span>
                    </div>
                </div>
            </div>

            <!-- Çalışılan Servisler -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Çalışılan Servisler</span>
                            <div class="crm-kpi-value"><?php echo number_format($calisilanCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-sky">
                            <i class="fa fa-cogs"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Sahada / İşlemde</span>
                        <span class="crm-badge-soft soft-sky">Devam Eden</span>
                    </div>
                </div>
            </div>

            <!-- Tamamlanan Servisler -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Tamamlanan Servisler</span>
                            <div class="crm-kpi-value"><?php echo number_format($tamamlananCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Sonuçlanan</span>
                        <span class="crm-badge-soft soft-emerald">Tamamlandı</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tablo Kartı -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header">
                <div class="header-left-inner">
                    <div class="card-icon">
                        <i class="fa fa-list"></i>
                    </div>
                    <div>
                        <h5>Oluşturulan Tüm Servisler</h5>
                        <p>Anlık arama, sütun filtreleme ve iş emri yönetimi</p>
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
                <table id="service-table" class="data-table table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 36px; min-width: 36px; max-width: 36px; text-align: center;" class="no-sort" data-filter="false">SIRA</th>
                            <th style="width: 82px; text-align: center;">Servis No</th>
                            <th style="width: 145px;">Firma Adı</th>
                            <th style="width: 78px;">Bölge</th>
                            <th style="width: 105px;">Servis Konusu</th>
                            <th style="width: 80px; text-align: center;">İş Emri Tarihi</th>
                            <th style="width: 72px; text-align: center;">Planlama</th>
                            <th style="width: 80px; text-align: center;">Sözleşme</th>
                            <th style="width: 72px; text-align: center;">Durum</th>
                            <th style="width: 75px;">Oluşturan</th>
                            <th style="width: 75px;">Son İşlem</th>
                            <th style="width: 75px; text-align: center;">Muhasebe</th>
                            <th style="width: 75px; text-align: center;" class="no-sort" data-filter="false">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$use_server_side): ?>
                            <?php $sirano = 1;
                            foreach ($projects as $purc) {
                                $pid = $purc["id"]; 
                                $isAccountingReceived = ($purc['accounting_action'] ?? '') === 'received';
                                $contractStatu = (int)($purc['contract_statu'] ?? 0);
                                $regDateRaw = $purc['pregdate'] ?? '';
                                $dateFormatted = $regDateRaw ? date('d.m.Y', strtotime($regDateRaw)) : '-';
                                $timeFormatted = $regDateRaw ? date('H:i', strtotime($regDateRaw)) : '';
                                $startDateRaw = $purc['pstart_date'] ?? '';
                                $startDateFormatted = ($startDateRaw && $startDateRaw !== '-') ? date('d.m.Y', strtotime($startDateRaw)) : '-';
                                ?>
                                <tr>
                                    <td class="text-center align-middle"><?php echo $sirano; ?></td>
                                    <td class="text-center"><span class="badge-sku"><i class="fa fa-wrench mr-1 text-primary"></i><?php echo htmlspecialchars($purc["service_number"]); ?></span></td>
                                    <td>
                                        <div class="service-company-cell" data-toggle="tooltip" title="<?php echo htmlspecialchars($purc['company_name']); ?>">
                                            <?php if (!empty($purc['customer_deleted_at'])): ?>
                                                <span class="text-muted"><?php echo htmlspecialchars($purc['company_name']); ?></span>
                                                <small class="crm-badge-soft soft-amber font-10">Silinmiş</small>
                                            <?php else: ?>
                                                <span class="font-weight-600 text-dark"><?php echo htmlspecialchars($purc['company_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($purc['region_name'])): ?>
                                            <div class="font-11 text-dark" style="line-height:1.2;"><?php echo htmlspecialchars($purc['region_name']); ?></div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><div class="service-title-cell" style="line-height:1.2;" data-toggle="tooltip" title="<?php echo htmlspecialchars($purc['service_title'] ?? ''); ?>"><?php echo htmlspecialchars($purc['service_title'] ?? ''); ?></div></td>
                                    <td class="text-center">
                                        <?php if ($regDateRaw): ?>
                                            <div class="font-11" style="line-height:1.25;"><span class="text-dark"><?php echo $dateFormatted; ?></span><?php if ($timeFormatted): ?><br><span class="text-muted font-10"><?php echo $timeFormatted; ?></span><?php endif; ?></div>
                                        <?php else: ?>
                                            <span class="text-muted text-center d-block">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($startDateRaw && $startDateRaw !== '-'): ?>
                                            <div class="font-11 text-muted"><i class="fa fa-clock-o mr-1"></i><?php echo $startDateFormatted; ?></div>
                                        <?php else: ?>
                                            <span class="text-muted text-center d-block">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        if ($contractStatu === 4) {
                                            echo '<span class="crm-badge-soft soft-rose font-10" style="padding:2px 5px; display:inline-block; line-height:1.2;">S.Kapsamında Değildir</span>';
                                        } elseif ($contractStatu === 2) {
                                            echo '<span class="crm-badge-soft soft-emerald font-10" style="padding:2px 5px; display:inline-block;">Sözleşmeli</span>';
                                        } elseif ($contractStatu === 1) {
                                            echo '<span class="crm-badge-soft soft-amber font-10" style="padding:2px 5px; display:inline-block;">Bekliyor</span>';
                                        } elseif ($contractStatu === 3) {
                                            echo '<span class="crm-badge-soft soft-rose font-10" style="padding:2px 5px; display:inline-block;">Yapılmadı</span>';
                                        } else {
                                            echo getSozlesmeStatusBadge($purc['contract_statu']);
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $color = (!empty($purc['status_color'])) ? $purc['status_color'] : '#64748b';
                                        $title = htmlspecialchars($purc['status_title'] ?? '-');
                                        echo "<span class='badge' style='background-color:{$color}; color:#fff; font-weight:600; padding:3px 6px; font-size:10.5px; border-radius:4px; display:inline-block;'>{$title}</span>"; 
                                        ?>
                                    </td>
                                    <td><div class="font-11 text-dark" style="line-height:1.2;" data-toggle="tooltip" title="<?php echo htmlspecialchars($purc['creator_username']); ?>"><?php echo htmlspecialchars($purc['creator_username']); ?></div></td>
                                    <td><div class="font-11 text-muted" style="line-height:1.2;" data-toggle="tooltip" title="<?php echo htmlspecialchars($purc['updater_username'] ?: $purc['creator_username']); ?>"><?php echo htmlspecialchars($purc['updater_username'] ?: $purc['creator_username']); ?></div></td>
                                    <td class="text-center">
                                        <?php
                                        $accLabel = $isAccountingReceived ? 'Teslim Alındı' : 'Teslim Bekliyor';
                                        $accSoftClass = $isAccountingReceived ? 'soft-emerald' : 'soft-amber';
                                        $accIcon = $isAccountingReceived ? 'fa-check' : 'fa-clock-o';
                                        echo "<span class='crm-badge-soft {$accSoftClass}' style='padding:2px 6px; font-size:10.5px; display:inline-block; line-height:1.2;'><i class='fa {$accIcon} mr-1'></i>{$accLabel}</span>";
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown d-inline-block text-center">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle action-dropdown-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-cog mr-1 text-muted"></i>İşlem
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right shadow border-0 dropdown-menu-detail">
                                                <?php if ($canEdit): ?>
                                                    <a href="index.php?p=service/manage&id=<?php echo $pid; ?>" class="dropdown-item"><i class="fa fa-pencil text-primary mr-2"></i> Düzenle</a>
                                                <?php endif; ?>
                                                <a href="index.php?p=service-view&id=<?php echo Security::encrypt($pid); ?>" target="_blank" class="dropdown-item"><i class="fa fa-info-circle text-info mr-2"></i> Detay Görüntüle</a>
                                                <?php if ($canAccountingReceipt): ?>
                                                    <?php if ($isAccountingReceived): ?>
                                                        <?php $confirmText = 'Bu servis için muhasebe teslim kaydını iade almak istediğinize emin misiniz?'; ?>
                                                        <button type="button" class="dropdown-item js-accounting-receipt-toggle text-warning" data-service-id="<?php echo (int) $pid; ?>" data-confirm="<?php echo htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-undo text-warning mr-2"></i> Muhasebe İade Al</button>
                                                    <?php else: ?>
                                                        <?php $confirmText = 'Bu servisi muhasebe teslim alındı olarak işaretlemek istediğinize emin misiniz?'; ?>
                                                        <button type="button" class="dropdown-item js-accounting-receipt-toggle text-success" data-service-id="<?php echo (int) $pid; ?>" data-confirm="<?php echo htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-check text-success mr-2"></i> Muhasebe Teslim Al</button>
                                                    <?php endif; ?>
                                                    <button type="button" class="dropdown-item js-accounting-log" data-service-id="<?php echo (int) $pid; ?>" data-service-number="<?php echo htmlspecialchars($purc['service_number'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-history text-dark mr-2"></i> Muhasebe Logları</button>
                                                <?php endif; ?>
                                                <?php if ($canDel): ?>
                                                    <div class="dropdown-divider"></div>
                                                    <button type="button" class="dropdown-item text-danger" onClick="deleteRecord('<?php echo $purc["id"]; ?> nolu Servisi silmek istediğinize emin misiniz?','<?php echo $pid; ?>','services','projects')"><i class="fa fa-trash text-danger mr-2"></i> Sil</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php $sirano++;
                            } ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Muhasebe Teslim Logları Modalı -->
<div class="modal fade" id="accountingReceiptLogModal" tabindex="-1" role="dialog" aria-labelledby="accountingReceiptLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountingReceiptLogModalLabel">
                    <i class="fa fa-history mr-2 text-primary"></i>Muhasebe Teslim Logları
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="accountingReceiptLogTable">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 60px;" class="text-center">#</th>
                                <th style="width: 150px;">İşlem</th>
                                <th>İşlem Yapan</th>
                                <th style="width: 170px;">İşlem Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Yükleniyor...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script src="include/js/data-table.js"></script>
<script>
    function showExportLoadingNotification() {
        var swalObj = (typeof swal !== 'undefined') ? swal : ((typeof Swal !== 'undefined') ? Swal : null);
        if (swalObj) {
            swalObj.fire({
                title: "Excel Dosyası Hazırlanıyor",
                html: "Lütfen bekleyiniz, veriler indiriliyor...<br><small style='color:#888;'>İndirme işlemi birazdan otomatik başlayacaktır.</small>",
                icon: "info",
                showConfirmButton: false,
                allowOutsideClick: true,
                timer: 4000,
                timerProgressBar: true,
                didOpen: function() {
                    if (typeof swalObj.showLoading === 'function') {
                        swalObj.showLoading();
                    }
                }
            });
        }
    }

    $(document).ready(function () {
        function showSwal(options) {
            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                return Swal.fire(options);
            }
            if (typeof swal !== 'undefined' && typeof swal.fire === 'function') {
                return swal.fire(options);
            }
            return null;
        }

        function showSimpleMessage(icon, title, text) {
            var instance = showSwal({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'Tamam'
            });

            if (!instance) {
                window.alert(text || title);
            }
        }

        // ==========================================
        // KPI Kartları Daraltma / Genişletme
        // ==========================================
        var $kpiSection = $('#kpiSummarySection');
        var $toggleBtn = $('#toggleKpiSummary');
        
        var isCollapsed = localStorage.getItem('aydinogullari_kpi_services_collapsed') === 'true';
        if (isCollapsed) {
            $kpiSection.hide();
            $toggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        }

        $toggleBtn.on('click', function() {
            var isCurrentlyHidden = $kpiSection.is(':hidden');
            if (isCurrentlyHidden) {
                $kpiSection.slideDown(200);
                $toggleBtn.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                localStorage.setItem('aydinogullari_kpi_services_collapsed', 'false');
            } else {
                $kpiSection.slideUp(200);
                $toggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                localStorage.setItem('aydinogullari_kpi_services_collapsed', 'true');
            }
        });

        // ==========================================
        // DataTables Konfigürasyonu
        // ==========================================
        var useServerSide = <?php echo $use_server_side ? 'true' : 'false'; ?>;
        
        var dtOptions = {
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                url: 'include/js/tr.json',
                processing: '<div class="p-3 text-center"><i class="fa fa-spinner fa-spin fa-2x fa-fw text-primary"></i><span class="d-block mt-2 font-13 font-weight-600">Yükleniyor...</span></div>'
            },
            responsive: false,
            order: [
                [1, 'desc']
            ],
            orderCellsTop: true,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, width: '36px' },
                { targets: 12, orderable: false, searchable: false, width: '75px' }
            ],
            initComplete: function () {
                if (window.App && window.App.TableFilter) {
                    App.TableFilter.attachToTable(this.api().table().node());
                }
                $('[data-toggle="tooltip"]').tooltip({
                    container: 'body'
                });
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip({
                    container: 'body'
                });
            }
        };

        if (useServerSide) {
            dtOptions.processing = true;
            dtOptions.serverSide = true;
            dtOptions.ajax = {
                url: '<?php echo $ajax_url; ?>',
                type: 'GET'
            };
            dtOptions.columns = [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    width: '36px',
                    className: 'text-center align-middle',
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: 1, className: 'align-middle' }, // service_number
                { data: 2, className: 'align-middle' }, // company_name
                { data: 3, className: 'align-middle' }, // region_name
                { data: 4, className: 'align-middle' }, // service_title
                { data: 5, className: 'align-middle' }, // pregdate
                { data: 6, className: 'align-middle' }, // pstart_date
                { data: 7, className: 'text-center align-middle' }, // contract_status
                { data: 8, className: 'text-center align-middle' }, // status
                { data: 9, className: 'align-middle' }, // creator_username
                { data: 10, className: 'align-middle' }, // updater_username
                { data: 11, className: 'text-center align-middle' }, // accounting status
                {
                    data: 12,
                    orderable: false,
                    searchable: false,
                    width: '70px',
                    className: 'text-center align-middle no-sort'
                } // actions
            ];
        }

        var serviceTable = $('#service-table').DataTable(dtOptions);

        // Tabloyu Yenile Butonu
        $('#btnRefreshServices').on('click', function() {
            var $btn = $(this);
            var $icon = $btn.find('i');
            $icon.addClass('fa-spin');
            $btn.prop('disabled', true);

            if (useServerSide) {
                serviceTable.ajax.reload(function() {
                    $icon.removeClass('fa-spin');
                    $btn.prop('disabled', false);
                }, false);
            } else {
                window.location.reload();
            }
        });

        // Excel Export
        $('#exportExcel').off('click').on('click', function (e) {
            e.preventDefault();
            showExportLoadingNotification();
            var params = {};
            if (serviceTable) {
                var order = serviceTable.order();
                if (order && order.length) {
                    params['order[0][column]'] = order[0][0];
                    params['order[0][dir]'] = order[0][1];
                }
                var gs = serviceTable.search();
                if (gs) params['search[value]'] = gs;
                serviceTable.columns().every(function (index) {
                    var v = this.search();
                    if (v) params['columns[' + index + '][search][value]'] = v;
                });
            }
            <?php if ($cid) { ?> params['cid'] = '<?php echo $cid; ?>'; <?php } ?>
            <?php if ($sid) { ?> params['sid'] = '<?php echo $sid; ?>'; <?php } ?>
            var qs = $.param(params);
            window.location = 'api/services_export.php' + (qs ? ('?' + qs) : '');
        });

        // Muhasebe Teslim / İade Toggle
        $(document).on('click', '.js-accounting-receipt-toggle', function () {
            var $btn = $(this);
            var serviceId = parseInt($btn.data('service-id'), 10);
            var confirmText = $btn.data('confirm') || 'Bu işlemi yapmak istediğinize emin misiniz?';

            if (!serviceId) {
                return;
            }

            var swalConfirm = showSwal({
                icon: 'warning',
                title: 'Emin misiniz?',
                text: confirmText,
                showCancelButton: true,
                confirmButtonText: 'Evet',
                cancelButtonText: 'Vazgeç'
            });

            var proceed = function () {
                $btn.prop('disabled', true);

                $.ajax({
                    url: 'api/services_datatables.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'toggle_accounting_receipt',
                        service_id: serviceId
                    }
                }).done(function (response) {
                    if (response && response.success) {
                        showSimpleMessage('success', 'Başarılı', response.message || 'İşlem tamamlandı.');
                        if (useServerSide) {
                            serviceTable.ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        showSimpleMessage('error', 'Hata', (response && response.message) ? response.message : 'İşlem başarısız oldu.');
                    }
                }).fail(function () {
                    showSimpleMessage('error', 'Hata', 'İşlem sırasında bir hata oluştu.');
                }).always(function () {
                    $btn.prop('disabled', false);
                });
            };

            if (swalConfirm && typeof swalConfirm.then === 'function') {
                swalConfirm.then(function (result) {
                    if (result && (result.isConfirmed || result.value === true)) {
                        proceed();
                    }
                });
            } else if (window.confirm(confirmText)) {
                proceed();
            }
        });

        // Muhasebe Log Modalı
        $(document).on('click', '.js-accounting-log', function () {
            var serviceId = parseInt($(this).data('service-id'), 10);
            var serviceNumber = $(this).data('service-number') || '';

            if (!serviceId) {
                return;
            }

            $('#accountingReceiptLogModalLabel').html('<i class="fa fa-history mr-2 text-primary"></i>Muhasebe Teslim Logları - Servis No: ' + serviceNumber);
            $('#accountingReceiptLogTable tbody').html('<tr><td colspan="4" class="text-center py-3"><i class="fa fa-spinner fa-spin mr-1"></i> Yükleniyor...</td></tr>');
            $('#accountingReceiptLogModal').modal('show');

            $.ajax({
                url: 'api/services_datatables.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_accounting_receipt_logs',
                    service_id: serviceId
                }
            }).done(function (response) {
                if (!response || !response.success) {
                    $('#accountingReceiptLogTable tbody').html('<tr><td colspan="4" class="text-center text-danger py-3">Loglar alınamadı.</td></tr>');
                    return;
                }

                var logs = response.logs || [];
                if (!logs.length) {
                    $('#accountingReceiptLogTable tbody').html('<tr><td colspan="4" class="text-center text-muted py-3">Kayıt bulunamadı.</td></tr>');
                    return;
                }

                var html = '';
                for (var i = 0; i < logs.length; i++) {
                    var log = logs[i];
                    var isReceived = log.action === 'received';
                    var actionBadge = isReceived 
                        ? '<span class="crm-badge-soft soft-emerald"><i class="fa fa-check-circle mr-1"></i>Teslim Alındı</span>' 
                        : '<span class="crm-badge-soft soft-rose"><i class="fa fa-undo mr-1"></i>İade Alındı</span>';
                    
                    html += '<tr>' +
                        '<td class="text-center font-weight-bold">' + (i + 1) + '</td>' +
                        '<td>' + actionBadge + '</td>' +
                        '<td><i class="fa fa-user-circle-o mr-1 text-muted"></i>' + (log.action_by_name || '-') + '</td>' +
                        '<td><span class="text-muted font-12"><i class="fa fa-clock-o mr-1"></i>' + (log.action_at || '-') + '</span></td>' +
                        '</tr>';
                }
                $('#accountingReceiptLogTable tbody').html(html);
            }).fail(function () {
                $('#accountingReceiptLogTable tbody').html('<tr><td colspan="4" class="text-center text-danger py-3">Loglar alınırken hata oluştu.</td></tr>');
            });
        });

        // ==========================================
        // Servisler Tablosunda Sağ Tık (Context Menu)
        // ==========================================
        $(document).on('contextmenu', '#service-table tbody tr', function(e) {
            if ($(this).find('td').length <= 1) return;

            e.preventDefault();
            
            var $tr = $(this);
            $('#service-table tbody tr').removeClass('context-menu-active');
            $tr.addClass('context-menu-active');

            var serviceNo = $tr.find('td:nth-child(2)').text().trim() || 'Servis İşlemleri';
            var companyName = $tr.find('td:nth-child(3)').text().trim() || '';
            var $actionTd = $tr.find('td:last-child');
            
            var menuHtml = '<div class="cm-header"><i class="fa fa-wrench mr-1 text-primary"></i> ' + $('<div>').text(serviceNo).html();
            if (companyName) {
                var compShort = companyName.replace(/Silinmiş/g, '').trim();
                menuHtml += '<div style="font-size:11px; font-weight:normal; color:#64748b; margin-top:2px; text-transform:none;" class="text-truncate">' + $('<div>').text(compShort).html() + '</div>';
            }
            menuHtml += '</div>';

            var $dropdownItems = $actionTd.find('.dropdown-menu .dropdown-item, .dropdown-menu .dropdown-divider');
            if ($dropdownItems.length) {
                $dropdownItems.each(function() {
                    var $item = $(this);
                    if ($item.hasClass('dropdown-divider')) {
                        menuHtml += '<div class="cm-divider"></div>';
                        return;
                    }
                    var isLink = $item.is('a');
                    var isDelete = $item.hasClass('text-danger') || $item.find('.fa-trash').length > 0;
                    var dangerClass = isDelete ? ' cm-danger' : '';
                    
                    if (isLink) {
                        var href = $item.attr('href') || '#';
                        var target = $item.attr('target') ? ' target="' + $item.attr('target') + '"' : '';
                        menuHtml += '<a href="' + href + '"' + target + ' class="' + dangerClass + '">' + $item.html() + '</a>';
                    } else {
                        var onClickAttr = $item.attr('onclick') || $item.attr('onClick') || '';
                        var onclickStr = onClickAttr ? ' onclick="' + onClickAttr + '; return false;"' : '';
                        var dataServiceId = $item.attr('data-service-id') ? ' data-service-id="' + $item.attr('data-service-id') + '"' : '';
                        var dataConfirm = $item.attr('data-confirm') ? ' data-confirm="' + $('<div>').text($item.attr('data-confirm')).html() + '"' : '';
                        var dataServiceNum = $item.attr('data-service-number') ? ' data-service-number="' + $('<div>').text($item.attr('data-service-number')).html() + '"' : '';
                        var itemClass = $item.attr('class') || '';
                        
                        menuHtml += '<button type="button" class="' + itemClass + dangerClass + '"' + onclickStr + dataServiceId + dataConfirm + dataServiceNum + '>' + $item.html() + '</button>';
                    }
                });
            }

            var $contextMenu = $('#customContextMenu');
            if (!$contextMenu.length) {
                $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
            }
            
            $contextMenu.html(menuHtml);

            var mouseX = e.clientX;
            var mouseY = e.clientY;
            
            $contextMenu.css({ display: 'block', visibility: 'hidden' });
            var menuWidth = $contextMenu.outerWidth();
            var menuHeight = $contextMenu.outerHeight();
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();

            if (mouseX + menuWidth > windowWidth) {
                mouseX = windowWidth - menuWidth - 10;
            }
            if (mouseY + menuHeight > windowHeight) {
                mouseY = windowHeight - menuHeight - 10;
            }

            $contextMenu.css({
                top: mouseY + 'px',
                left: mouseX + 'px',
                visibility: 'visible',
                opacity: '1'
            });
        });

        // Menü dışına tıklanınca veya kaydırılınca kapat
        $(document).on('click scroll', function(e) {
            if (!$(e.target).closest('#customContextMenu').length) {
                $('#customContextMenu').hide();
                $('#service-table tbody tr').removeClass('context-menu-active');
            }
        });

        // Menüdeki seçeneğe basılınca kapat
        $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
            $('#customContextMenu').hide();
            $('#service-table tbody tr').removeClass('context-menu-active');
        });

        // ESC basılınca kapat
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#customContextMenu').hide();
                $('#service-table tbody tr').removeClass('context-menu-active');
            }
        });
    });
</script>
