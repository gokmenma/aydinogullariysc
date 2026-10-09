<?php

use App\Model\CustomerModel;

$CustomerModel = new CustomerModel();

if (($_GET['st'] ?? '') === 'customer-deleted') {
    showAlert("alert", "Bu firma silinmiş olduğu için düzenleme sayfası açılamaz.");
}

if (@$_GET["id"] && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
    permcontrol("customerdelete");
    $cdid = (int)$_GET["id"];
    $contq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
    $contq->execute(array($cdid));
    $custData = $contq->fetch(PDO::FETCH_ASSOC);
    if ($custData) {
        $CustomerModel->softDelete($cdid, $_SESSION['lid'] ?? 0);
        if (function_exists('audit_log')) {
            $custContext = [
                'id' => $cdid,
                'company' => $custData['company'] ?? null,
                'email' => $custData['email'] ?? null,
                'gsm' => $custData['gsm'] ?? null,
                'yetkili' => $custData['yetkili'] ?? null,
                'city' => $custData['city'] ?? null,
                'ilce' => $custData['ilce'] ?? null,
                'address' => $custData['address'] ?? null,
                'tax_office' => $custData['tax_office'] ?? null,
                'tax_number' => $custData['tax_number'] ?? null,
                'region' => $custData['region'] ?? null,
                'group' => $custData['grp'] ?? null,
                'regdate' => $custData['regdate'] ?? null,
                'deleted_by_user_id' => $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0),
                'deleted_by_username' => $_SESSION['username'] ?? null,
            ];
            audit_log("delete", "customers", "Firma pasife alındı: " . ($custData['company'] ?? ('#' . $cdid)), "customer", $cdid, $custContext);
        }
        header("Location: index.php?p=customers&id=$cdid&type=delete");
        exit;
    }
}

// KPI İstatistikleri
$stats = $CustomerModel->getSummaryStats();
$totalCustomers = (int) ($stats['total_customers'] ?? 0);
$withEmailCount = (int) ($stats['with_email_count'] ?? 0);
$withGsmCount = (int) ($stats['with_gsm_count'] ?? 0);
$recentCount = (int) ($stats['recent_30_days_count'] ?? 0);
$emailRate = $totalCustomers > 0 ? round(($withEmailCount / $totalCustomers) * 100, 1) : 0;
$gsmRate = $totalCustomers > 0 ? round(($withGsmCount / $totalCustomers) * 100, 1) : 0;

try {
    $logger = \getLogger("Müşteriler");
    $logger->info("Müşteri listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_customers_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-customers-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* ==========================================
       PREMIUM CUSTOMERS LIST THEME
       ========================================== */
    .kpi-customers-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    .customers-list-wrapper {
        width: 100%;
        padding: 0;
        margin: 0;
    }

    .customers-list-page-container {
        padding: 0;
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
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
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
    .icon-cyan    { background: #ecfeff; color: #0891b2; }
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
    .soft-cyan    { background: #cffafe; color: #0e7490; }
    .soft-amber   { background: #fef3c7; color: #92400e; }

    /* Form & Table Card styling */
    .form-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 4px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
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
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff !important;
        border: none;
        border-radius: 8px;
        padding: 8px 18px;
        font-weight: 600;
        font-size: 13.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
        transition: all 0.2s ease;
        height: 38px;
        text-decoration: none;
    }
    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(2, 132, 199, 0.38);
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

    /* DataTables Container & Reset Spacing */
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
        padding: 12px 18px;
        margin: 0;
        border-top: 1px solid #f1f5f9;
        background: #fafafa;
    }

    /* Table Base Styling */
    #customerlist {
        margin: 0 !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        width: 100% !important;
        table-layout: auto !important;
    }
    #customerlist thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 10px;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        vertical-align: middle;
        white-space: nowrap;
        position: relative;
    }
    #customerlist thead th.sorting,
    #customerlist thead th.sorting_asc,
    #customerlist thead th.sorting_desc {
        padding-left: 28px !important;
        padding-right: 28px !important;
    }
    #customerlist thead th:not(.sorting):not(.sorting_asc):not(.sorting_desc) {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
    #customerlist tbody td {
        padding: 10px 10px;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
        border-top: 1px solid #f1f5f9;
        min-width: 0 !important;
    }
    #customerlist tbody tr:hover {
        background-color: #f8fafc;
    }
    #customerlist tbody tr.context-menu-active {
        background-color: rgba(2, 132, 199, 0.08) !important;
    }

    /* Row Index Badge */
    .row-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 26px;
        height: 22px;
        padding: 0 6px;
        border-radius: 5px;
        background: #334155;
        color: #ffffff !important;
        font-size: 11.5px;
        font-weight: 700;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        letter-spacing: -0.3px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    }
    .dark-mode .row-index-badge {
        background: #0f172a !important;
        color: #38bdf8 !important;
        border: 1px solid #334155;
    }

    /* Badges & Tags */
    .customer-title-cell a {
        color: #0f172a;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .customer-title-cell a:hover {
        color: #0284c7;
        text-decoration: underline;
    }
    .badge-group {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 2px 7px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-represant {
        display: inline-flex;
        align-items: center;
        background: #f8fafc;
        color: #334155;
        border-radius: 5px;
        padding: 2px 6px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }
    .badge-stat-tag {
        display: inline-flex;
        align-items: center;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-stat-offers {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .badge-stat-services {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .badge-stat-reports {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    .badge-date {
        display: inline-flex;
        align-items: center;
        color: #64748b;
        font-size: 11.5px;
        font-weight: 500;
        white-space: nowrap;
    }

    /* Action buttons in Table */
    .action-btn-group {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        justify-content: center;
        white-space: nowrap;
    }
    .action-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        font-size: 11.5px;
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
        min-width: 220px;
        backdrop-filter: blur(8px);
        transition: opacity 0.15s ease, transform 0.15s ease;
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
        font-size: 13px;
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
        color: #0284c7;
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

    /* Modal Styling */
    #customerdetails .modal-content {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    #customerdetails .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 16px 20px;
    }
    #customerdetails .modal-body {
        padding: 20px;
    }
    #customerdetails .modal-footer {
        border-top: 1px solid #f1f5f9;
        padding: 12px 20px;
    }
    .detail-info-table td {
        padding: 8px 12px;
        font-size: 13.5px;
    }
    .detail-info-table td:first-child {
        font-weight: 600;
        color: #64748b;
        width: 40%;
    }
    .detail-info-table td:last-child {
        color: #1e293b;
        font-weight: 500;
    }

    /* ==========================================
       CUSTOMER SUMMARY DASHBOARD MODAL STYLES
       ========================================== */
    #customerDashboardModal .modal-dialog {
        max-width: 1180px;
        margin: 1.75rem auto;
    }
    #customerDashboardModal .modal-content {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        background: #f8fafc;
        overflow: hidden;
    }
    #customerDashboardModal .modal-header {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 18px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    #customerDashboardModal .modal-body {
        padding: 20px 24px;
        max-height: calc(88vh - 140px);
        overflow-y: auto;
    }
    #customerDashboardModal .modal-footer {
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        padding: 14px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Modal Header Info */
    .cdm-header-title-box {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .cdm-header-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
    }
    .cdm-header-title-box h4 {
        margin: 0;
        font-size: 19px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.3px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .cdm-badge-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    /* Nav Pills / Tabs inside Modal */
    .cdm-nav-pills {
        display: flex;
        gap: 8px;
        background: #ffffff;
        padding: 6px 8px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
        overflow-x: auto;
    }
    .cdm-nav-pills .nav-link {
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        border: none;
        background: transparent;
    }
    .cdm-nav-pills .nav-link:hover {
        color: #0f172a;
        background: #f1f5f9;
    }
    .cdm-nav-pills .nav-link.active {
        background: #f1f5f9;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #cbd5e1;
    }
    .cdm-nav-pills .nav-link .badge-counter {
        background: #e2e8f0;
        color: #475569;
        font-size: 11px;
        padding: 2px 7px;
        border-radius: 10px;
        font-weight: 700;
    }
    .cdm-nav-pills .nav-link.active .badge-counter {
        background: #0284c7;
        color: #ffffff;
    }

    /* KPI Grid in Modal */
    .cdm-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }
    .cdm-kpi-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 16px 18px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .cdm-kpi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .cdm-kpi-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        margin: 0;
    }
    .cdm-kpi-badge {
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
    }
    .cdm-kpi-value {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        margin: 4px 0;
        line-height: 1.2;
    }
    .cdm-kpi-subtext {
        font-size: 12px;
        color: #64748b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Modal 2-Column Boxes */
    .cdm-box-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        padding: 18px 20px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .cdm-box-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f1f5f9;
    }
    .cdm-box-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cdm-box-title i {
        color: #0284c7;
    }
    .cdm-box-link {
        font-size: 12px;
        font-weight: 600;
        color: #0284c7;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
    }
    .cdm-box-link:hover {
        text-decoration: underline;
        color: #0369a1;
    }

    /* Donut Chart Layout */
    .cdm-donut-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex: 1;
    }
    .cdm-donut-chart-wrap {
        width: 170px;
        height: 170px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .cdm-donut-legend {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .cdm-legend-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12.5px;
        color: #475569;
    }
    .cdm-legend-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cdm-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .cdm-legend-val {
        font-weight: 700;
        color: #0f172a;
    }

    /* Bar Chart Box */
    .cdm-bar-chart-wrap {
        width: 100%;
        min-height: 180px;
        flex: 1;
    }

    /* Info Blocks (Konum, İletişim) */
    .cdm-info-block {
        background: #f8fafc;
        border-radius: 10px;
        border: 1px solid #f1f5f9;
        padding: 12px 14px;
        margin-bottom: 12px;
    }
    .cdm-info-block-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .cdm-info-block-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
    }
    .cdm-info-block-badge {
        font-size: 10.5px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
        background: #e2e8f0;
        color: #475569;
    }
    .cdm-info-block-content {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.4;
    }
    .cdm-info-block-desc {
        font-size: 12px;
        font-weight: 400;
        color: #64748b;
        margin-top: 2px;
    }

    /* Micro Badges in Info Card */
    .cdm-micro-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }
    .cdm-micro-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 11.5px;
        color: #475569;
        font-weight: 500;
    }

    /* Recent Activity Item */
    .cdm-activity-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
    }
    .cdm-activity-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        transition: all 0.15s ease;
    }
    .cdm-activity-item:hover {
        background: #f1f5f9;
        border-color: #e2e8f0;
    }
    .cdm-activity-left {
        display: flex;
        align-items: center;
        gap: 10px;
        overflow: hidden;
    }
    .cdm-type-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }
    .cdm-type-offer   { background: #dbeafe; color: #1e40af; }
    .cdm-type-service { background: #d1fae5; color: #065f46; }
    .cdm-type-report  { background: #fef3c7; color: #92400e; }
    .cdm-activity-text {
        font-size: 12.5px;
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 250px;
    }
    .cdm-activity-date {
        font-size: 11.5px;
        color: #64748b;
        margin-left: 6px;
    }
    .cdm-activity-amount {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }
    .cdm-activity-status {
        font-size: 11.5px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
    }

    /* Modal Tables */
    .cdm-table {
        width: 100%;
        margin-bottom: 0;
        font-size: 12.5px;
    }
    .cdm-table thead th {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        color: #475569;
        font-weight: 700;
        padding: 10px 12px;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.4px;
    }
    .cdm-table tbody td {
        padding: 10px 12px;
        vertical-align: middle;
        border-top: 1px solid #f1f5f9;
        color: #334155;
    }
    .cdm-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Responsive Grid */
    @media (max-width: 991px) {
        .cdm-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .cdm-donut-container {
            flex-direction: column;
            align-items: center;
        }
    }
    @media (max-width: 576px) {
        .cdm-kpi-grid {
            grid-template-columns: 1fr;
        }
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
    .dark-mode .icon-cyan    { background: rgba(8, 145, 178, 0.15) !important; color: #38bdf8 !important; }
    .dark-mode .icon-amber   { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }

    .dark-mode .soft-primary { background: rgba(59, 130, 246, 0.2) !important; color: #93c5fd !important; }
    .dark-mode .soft-emerald { background: rgba(16, 185, 129, 0.2) !important; color: #6ee7b7 !important; }
    .dark-mode .soft-cyan    { background: rgba(8, 145, 178, 0.2) !important; color: #7dd3fc !important; }
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
        border-top-color: #334155 !important;
    }
    .dark-mode #customerlist thead th {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode #customerlist tbody td {
        border-top-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode #customerlist tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    .dark-mode .customer-title-cell a { color: #f1f5f9 !important; }
    .dark-mode .customer-title-cell a:hover { color: #38bdf8 !important; }
    .dark-mode .badge-group {
        background: #0f172a !important;
        color: #cbd5e1 !important;
        border-color: #334155 !important;
    }
    .dark-mode .badge-represant {
        background: #0f172a !important;
        color: #94a3b8 !important;
    }
    .dark-mode .badge-date { color: #94a3b8 !important; }

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
    .dark-mode .custom-context-menu button { color: #e2e8f0 !important; }
    .dark-mode .custom-context-menu a:hover,
    .dark-mode .custom-context-menu button:hover {
        background: #334155 !important;
        color: #38bdf8 !important;
    }
    .dark-mode .custom-context-menu a.cm-danger:hover,
    .dark-mode .custom-context-menu button.cm-danger:hover {
        background: rgba(239, 68, 68, 0.15) !important;
        color: #f87171 !important;
    }
    .dark-mode .custom-context-menu .cm-divider { background: #334155 !important; }

    .dark-mode #customerdetails .modal-content {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode #customerdetails .modal-header { border-bottom-color: #334155 !important; }
    .dark-mode #customerdetails .modal-footer { border-top-color: #334155 !important; }
    .dark-mode .detail-info-table td:first-child { color: #94a3b8 !important; }
    .dark-mode .detail-info-table td:last-child { color: #f1f5f9 !important; }

    /* Customer Dashboard Modal Dark Mode */
    .dark-mode #customerDashboardModal .modal-content {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark-mode #customerDashboardModal .modal-header,
    .dark-mode #customerDashboardModal .modal-footer {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark-mode .cdm-header-title-box h4 {
        color: #f1f5f9 !important;
    }
    .dark-mode .cdm-nav-pills {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark-mode .cdm-nav-pills .nav-link {
        color: #94a3b8 !important;
    }
    .dark-mode .cdm-nav-pills .nav-link:hover {
        background: #334155 !important;
        color: #f1f5f9 !important;
    }
    .dark-mode .cdm-nav-pills .nav-link.active {
        background: #0f172a !important;
        color: #38bdf8 !important;
        border-color: #475569 !important;
    }
    .dark-mode .cdm-kpi-card,
    .dark-mode .cdm-box-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
    }
    .dark-mode .cdm-box-header {
        border-bottom-color: #334155 !important;
    }
    .dark-mode .cdm-box-title {
        color: #f1f5f9 !important;
    }
    .dark-mode .cdm-kpi-value {
        color: #f8fafc !important;
    }
    .dark-mode .cdm-kpi-label,
    .dark-mode .cdm-kpi-subtext {
        color: #94a3b8 !important;
    }
    .dark-mode .cdm-info-block {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark-mode .cdm-info-block-content {
        color: #f1f5f9 !important;
    }
    .dark-mode .cdm-info-block-desc {
        color: #94a3b8 !important;
    }
    .dark-mode .cdm-micro-badge {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #cbd5e1 !important;
    }
    .dark-mode .cdm-activity-item {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark-mode .cdm-activity-item:hover {
        background: #1e293b !important;
    }
    .dark-mode .cdm-activity-text {
        color: #f1f5f9 !important;
    }
    .dark-mode .cdm-activity-date {
        color: #94a3b8 !important;
    }
    .dark-mode .cdm-activity-amount {
        color: #38bdf8 !important;
    }
    .dark-mode .cdm-legend-item {
        color: #cbd5e1 !important;
    }
    .dark-mode .cdm-legend-val {
        color: #f1f5f9 !important;
    }
    .dark-mode .cdm-table thead th {
        background: #0f172a !important;
        border-bottom-color: #334155 !important;
        color: #94a3b8 !important;
    }
    .dark-mode .cdm-table tbody td {
        border-top-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode .cdm-table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
</style>

<div class="customers-list-page-container">
    <div class="customers-list-wrapper">
        
        <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 12px; padding: 0;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-users"></i>
                </div>
                <div class="page-title-text">
                    <h4>Müşteri & Cari Yönetimi</h4>
                    <p>Sistemde kayıtlı kurumsal ve bireysel müşteri listesi, iletişim ve geçmiş kayıtları</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <?php if (permtrue("customer_dashboard") || permtrue("customerview")) { ?>
                    <a href="index.php?p=customers/dashboard" class="btn btn-outline-primary btn-action-outline" title="Dashboard">
                        <i class="fa fa-dashboard"></i> <span class="d-none d-sm-inline">Dashboard</span>
                    </a>
                <?php } ?>
                <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshCustomers" title="Tabloyu Yenile">
                    <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
                </button>
                <?php if (permtrue("customerexport")) { ?>
                    <button type="button" class="btn btn-outline-success btn-action-outline" id="exportCustomers" title="Excel Olarak İndir">
                        <i class="fa fa-file-excel-o"></i> <span class="d-none d-sm-inline">Excel'e Aktar</span>
                    </button>
                <?php } ?>
                <?php if (permtrue("customeradd")) { ?>
                    <a href="index.php?p=customers/manage" class="btn btn-action-primary">
                        <i class="fa fa-plus-circle"></i> <span>Yeni Müşteri Ekle</span>
                    </a>
                <?php } ?>
            </div>
        </div>

        <!-- KPI Özet / İstatistik Kartları -->
        <div id="kpiSummarySection" class="row mx-0 mb-2 kpi-summary-collapse">
            <!-- Toplam Müşteri -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Müşteri</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalCustomers, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-users"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Aktif Müşteri Kaydı</span>
                        <span class="crm-badge-soft soft-primary">Cari Rehber</span>
                    </div>
                </div>
            </div>

            <!-- E-Posta Tanımlı Müşteriler -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">E-Posta Tanımlı</span>
                            <div class="crm-kpi-value"><?php echo number_format($withEmailCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-cyan">
                            <i class="fa fa-envelope-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">%<?php echo $emailRate; ?> İletişim Oranı</span>
                        <span class="crm-badge-soft soft-cyan">E-Posta</span>
                    </div>
                </div>
            </div>

            <!-- GSM / Telefon Tanımlı -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">GSM / Telefon Tanımlı</span>
                            <div class="crm-kpi-value"><?php echo number_format($withGsmCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-phone"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">%<?php echo $gsmRate; ?> Telefon Oranı</span>
                        <span class="crm-badge-soft soft-emerald">Mobil / Sabit</span>
                    </div>
                </div>
            </div>

            <!-- Son 30 Günde Eklenenler -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Son 30 Gün</span>
                            <div class="crm-kpi-value"><?php echo number_format($recentCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-amber">
                            <i class="fa fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Yeni Eklenen Firmalar</span>
                        <span class="crm-badge-soft soft-amber">Son Kayıtlar</span>
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
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            <h5>Müşteri ve Cari Listesi</h5>
                            <?php if (permtrue("customeradd")) { ?>
                                <a href="index.php?p=customers/manage" class="btn-card-header-add" title="Yeni Müşteri Ekle" data-toggle="tooltip">
                                    <i class="fa fa-plus"></i>
                                </a>
                            <?php } ?>
                        </div>
                        <p>Anlık arama, sütun filtreleme ve müşteri yönetimi</p>
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
                <table id="customerlist" class="data-table table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 42px;" class="no-sort text-center">#Sıra</th>
                            <th style="min-width: 190px;">Firma Adı</th>
                            <th style="width: 105px;">Grup</th>
                            <th style="width: 120px;">Satış Temsilcisi</th>
                            <th style="width: 135px;" class="no-sort text-center">Teklif / Servis / Rapor</th>
                            <th style="width: 140px;">E-Posta Adresi</th>
                            <th style="width: 110px;">GSM</th>
                            <th style="width: 95px;" class="text-center">Kayıt Tarihi</th>
                            <th style="width: 85px;" class="no-sort text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-center">#Sıra</th>
                            <th>Firma Adı</th>
                            <th>Grup</th>
                            <th>Satış Temsilcisi</th>
                            <th class="text-center">Teklif / Servis / Rapor</th>
                            <th>E-Posta Adresi</th>
                            <th>GSM</th>
                            <th class="text-center">Kayıt Tarihi</th>
                            <th class="text-center">İşlem</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Detay Modalı -->
<div class="modal fade" id="customerdetails" tabindex="-1" role="dialog" aria-labelledby="customerdetailsCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="customerdeteailHeader">
                    <i class="fa fa-info-circle text-primary mr-1"></i> Müşteri Kayıt Detayı
                </h5>
                <button type="button" class="close closeModal" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped detail-info-table mb-0">
                    <tbody>
                        <tr>
                            <td><i class="fa fa-user-plus text-muted mr-1"></i> Kayıt Yapan:</td>
                            <td><span id="creator" class="font-weight-600">-</span></td>
                        </tr>
                        <tr>
                            <td><i class="fa fa-calendar-check-o text-muted mr-1"></i> Kayıt Tarihi:</td>
                            <td><span id="create_time">-</span></td>
                        </tr>
                        <tr>
                            <td><i class="fa fa-pencil text-muted mr-1"></i> Güncelleyen:</td>
                            <td><span id="updater" class="font-weight-600">-</span></td>
                        </tr>
                        <tr>
                            <td><i class="fa fa-clock-o text-muted mr-1"></i> Güncelleme Tarihi:</td>
                            <td><span id="updated_at">-</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="closeModal btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius: 6px; padding: 6px 16px;">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Özet Dashboard Modalı (2. Resim Tasarımı) -->
<div class="modal fade" id="customerDashboardModal" tabindex="-1" role="dialog" aria-labelledby="customerDashboardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <div class="cdm-header-title-box">
                    <div class="cdm-header-icon">
                        <i class="fa fa-building"></i>
                    </div>
                    <div>
                        <h4 id="cdm_company_title">Firma Yükleniyor...</h4>
                        <div class="d-flex align-items-center flex-wrap" style="gap: 6px; margin-top: 3px;">
                            <span class="cdm-badge-status" id="cdm_status_badge"><i class="fa fa-check-circle mr-1"></i> Aktif</span>
                            <span class="badge-group" id="cdm_group_badge" style="font-size: 11px;">Müşteri</span>
                            <span class="text-muted font-12 ml-1" id="cdm_city_badge"><i class="fa fa-map-marker text-danger mr-1"></i> -</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; color: #64748b; opacity: 0.8; padding: 10px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Sekme Menüsü (Pills) -->
                <ul class="nav cdm-nav-pills" id="cdmTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-cdm-summary" data-toggle="pill" href="#pill-cdm-summary" role="tab">
                            <i class="fa fa-th-large"></i> Özet
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-cdm-offers" data-toggle="pill" href="#pill-cdm-offers" role="tab">
                            <i class="fa fa-file-text-o"></i> Teklifler <span class="badge-counter" id="cdm_tab_offers_cnt">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-cdm-services" data-toggle="pill" href="#pill-cdm-services" role="tab">
                            <i class="fa fa-wrench"></i> Servis & Bakımlar <span class="badge-counter" id="cdm_tab_services_cnt">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-cdm-reports" data-toggle="pill" href="#pill-cdm-reports" role="tab">
                            <i class="fa fa-clipboard"></i> Keşif & Raporlar <span class="badge-counter" id="cdm_tab_reports_cnt">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-cdm-info" data-toggle="pill" href="#pill-cdm-info" role="tab">
                            <i class="fa fa-info-circle"></i> Firma Künyesi
                        </a>
                    </li>
                </ul>

                <!-- Yükleniyor Spinner -->
                <div id="cdm_loader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Yükleniyor...</span>
                    </div>
                    <p class="text-muted mt-2 font-13 font-weight-500">Müşteri verileri ve hareketler hazırlanıyor...</p>
                </div>

                <!-- Sekme İçerikleri -->
                <div class="tab-content" id="cdmTabContent" style="display: none;">
                    
                    <!-- 1. SEKME: ÖZET DASHBOARD -->
                    <div class="tab-pane fade show active" id="pill-cdm-summary" role="tabpanel">
                        <!-- Üst 4'lü KPI Grid -->
                        <div class="cdm-kpi-grid">
                            <!-- Kart 1: Teklif Portföyü -->
                            <div class="cdm-kpi-card">
                                <div class="cdm-kpi-header">
                                    <span class="cdm-kpi-label">Toplam Teklif Hacmi</span>
                                    <span class="cdm-kpi-badge soft-cyan">Portföy</span>
                                </div>
                                <div class="cdm-kpi-value text-primary" id="cdm_kpi_total_offers_amount">0,00 ₺</div>
                                <p class="cdm-kpi-subtext" id="cdm_kpi_total_offers_desc">0 toplam teklif kaydı</p>
                            </div>

                            <!-- Kart 2: Kazanılan / Onaylı Ciro -->
                            <div class="cdm-kpi-card">
                                <div class="cdm-kpi-header">
                                    <span class="cdm-kpi-label">Kazanılan Ciro</span>
                                    <span class="cdm-kpi-badge soft-emerald" id="cdm_kpi_win_rate">%0 Başarı</span>
                                </div>
                                <div class="cdm-kpi-value text-success" id="cdm_kpi_won_amount">0,00 ₺</div>
                                <p class="cdm-kpi-subtext" id="cdm_kpi_won_desc">0 onaylanan teklif</p>
                            </div>

                            <!-- Kart 3: Servis & Bakım -->
                            <div class="cdm-kpi-card">
                                <div class="cdm-kpi-header">
                                    <span class="cdm-kpi-label">Servis & Bakım</span>
                                    <span class="cdm-kpi-badge soft-primary" id="cdm_kpi_services_badge">0 Servis</span>
                                </div>
                                <div class="cdm-kpi-value" id="cdm_kpi_completed_services">0 Tamamlanan</div>
                                <p class="cdm-kpi-subtext" id="cdm_kpi_services_desc">0 devam eden iş</p>
                            </div>

                            <!-- Kart 4: Muayene & Raporlar -->
                            <div class="cdm-kpi-card">
                                <div class="cdm-kpi-header">
                                    <span class="cdm-kpi-label">Keşif & Muayene</span>
                                    <span class="cdm-kpi-badge soft-amber">Aktif Kayıt</span>
                                </div>
                                <div class="cdm-kpi-value" id="cdm_kpi_reports_value">0 Rapor · 0 Keşif</div>
                                <p class="cdm-kpi-subtext" id="cdm_kpi_reports_desc">Periyodik kontrol kayıtları</p>
                            </div>
                        </div>

                        <!-- Orta 2'li Grid (Grafikler) -->
                        <div class="row mb-3">
                            <!-- Sol: Teklif & Süreç Dağılımı Donut -->
                            <div class="col-lg-5 mb-3 mb-lg-0">
                                <div class="cdm-box-card">
                                    <div class="cdm-box-header">
                                        <h5 class="cdm-box-title"><i class="fa fa-pie-chart"></i> Teklif & Faaliyet Dağılımı</h5>
                                        <span class="text-muted font-11">Oransal Dağılım</span>
                                    </div>
                                    <div class="cdm-donut-container">
                                        <div class="cdm-donut-chart-wrap">
                                            <div id="cdmDonutChart"></div>
                                        </div>
                                        <div class="cdm-donut-legend">
                                            <div class="cdm-legend-item">
                                                <div class="cdm-legend-left">
                                                    <span class="cdm-dot" style="background: #10b981;"></span>
                                                    <span>Onaylanan Teklif:</span>
                                                </div>
                                                <span class="cdm-legend-val" id="cdm_leg_won">0</span>
                                            </div>
                                            <div class="cdm-legend-item">
                                                <div class="cdm-legend-left">
                                                    <span class="cdm-dot" style="background: #f59e0b;"></span>
                                                    <span>Bekleyen Teklif:</span>
                                                </div>
                                                <span class="cdm-legend-val" id="cdm_leg_pending">0</span>
                                            </div>
                                            <div class="cdm-legend-item">
                                                <div class="cdm-legend-left">
                                                    <span class="cdm-dot" style="background: #ef4444;"></span>
                                                    <span>Reddedilen Teklif:</span>
                                                </div>
                                                <span class="cdm-legend-val" id="cdm_leg_rejected">0</span>
                                            </div>
                                            <div class="cdm-legend-item">
                                                <div class="cdm-legend-left">
                                                    <span class="cdm-dot" style="background: #0284c7;"></span>
                                                    <span>Servis Kayıtları:</span>
                                                </div>
                                                <span class="cdm-legend-val" id="cdm_leg_services">0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sağ: Aylık Teklif Hacmi & Akış Bar Chart -->
                            <div class="col-lg-7">
                                <div class="cdm-box-card">
                                    <div class="cdm-box-header">
                                        <h5 class="cdm-box-title"><i class="fa fa-bar-chart"></i> Aylık Faaliyet Akışı (Son 6 Ay)</h5>
                                        <div class="d-flex align-items-center font-11" style="gap: 10px;">
                                            <span><span class="cdm-dot mr-1" style="background: #0284c7;"></span> Teklif Hacmi</span>
                                            <span><span class="cdm-dot mr-1" style="background: #10b981;"></span> Onaylanan Ciro</span>
                                        </div>
                                    </div>
                                    <div class="cdm-bar-chart-wrap">
                                        <div id="cdmBarChart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alt 2'li Grid (Künye & Son Hareketler) -->
                        <div class="row">
                            <!-- Sol: Firma Yapı & İletişim Özeti -->
                            <div class="col-lg-6 mb-3 mb-lg-0">
                                <div class="cdm-box-card">
                                    <div class="cdm-box-header">
                                        <h5 class="cdm-box-title"><i class="fa fa-id-card-o"></i> Firma Yapı & İletişim Özeti</h5>
                                        <a href="#pill-cdm-info" class="cdm-box-link js-go-tab" data-tab-target="#tab-cdm-info">Detaylı Künye <i class="fa fa-chevron-right font-10"></i></a>
                                    </div>
                                    
                                    <!-- Konum & Bölge -->
                                    <div class="cdm-info-block">
                                        <div class="cdm-info-block-header">
                                            <span class="cdm-info-block-label">Konum & Bölge</span>
                                            <span class="cdm-info-block-badge" id="cdm_info_city_ilce">-</span>
                                        </div>
                                        <div class="cdm-info-block-content" id="cdm_info_location">Belirtilmedi</div>
                                        <div class="cdm-info-block-desc" id="cdm_info_address">Açık adres girilmedi</div>
                                    </div>

                                    <!-- İletişim Bilgileri -->
                                    <div class="cdm-info-block mb-2">
                                        <div class="cdm-info-block-header">
                                            <span class="cdm-info-block-label">İletişim & Yetkili</span>
                                            <span class="cdm-info-block-badge" id="cdm_info_official">Yetkili Yok</span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 8px;">
                                            <div class="font-13 text-dark font-weight-600" id="cdm_info_phone">
                                                <i class="fa fa-phone text-success mr-1"></i> -
                                            </div>
                                            <div class="font-12 text-muted" id="cdm_info_email">
                                                <i class="fa fa-envelope-o text-primary mr-1"></i> -
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mikro Rozetler -->
                                    <div class="cdm-micro-badges">
                                        <span class="cdm-micro-badge"><i class="fa fa-tag text-muted"></i> Grup: <strong id="cdm_micro_group">-</strong></span>
                                        <span class="cdm-micro-badge"><i class="fa fa-user-circle text-muted"></i> Temsilci: <strong id="cdm_micro_rep">-</strong></span>
                                        <span class="cdm-micro-badge"><i class="fa fa-calendar-check-o text-muted"></i> Kayıt: <strong id="cdm_micro_reg">-</strong></span>
                                        <span class="cdm-micro-badge"><i class="fa fa-credit-card text-muted"></i> Vade: <strong id="cdm_micro_vade">-</strong></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Sağ: Son Hareketler & İşlemler -->
                            <div class="col-lg-6">
                                <div class="cdm-box-card">
                                    <div class="cdm-box-header">
                                        <h5 class="cdm-box-title"><i class="fa fa-history"></i> Son Hareketler & İşlemler</h5>
                                        <a href="#pill-cdm-offers" class="cdm-box-link js-go-tab" data-tab-target="#tab-cdm-offers">Tüm Teklifler <i class="fa fa-chevron-right font-10"></i></a>
                                    </div>
                                    <div class="cdm-activity-list" id="cdm_activity_container">
                                        <!-- Dinamik doldurulacak -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. SEKME: TEKLİFLER LİSTESİ -->
                    <div class="tab-pane fade" id="pill-cdm-offers" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-dark mb-0"><i class="fa fa-file-text-o text-primary mr-1"></i> Firmanın Teklif Geçmişi</h6>
                            <a href="#" id="cdm_tab_new_offer_btn" class="btn btn-sm btn-primary" target="_blank" style="border-radius: 6px;">
                                <i class="fa fa-plus-circle mr-1"></i> Yeni Teklif Hazırla
                            </a>
                        </div>
                        <div class="table-responsive bg-white rounded border">
                            <table class="table cdm-table">
                                <thead>
                                    <tr>
                                        <th>Teklif No</th>
                                        <th>Konu / Başlık</th>
                                        <th>Tarih</th>
                                        <th>Tutar</th>
                                        <th class="text-center">Durum</th>
                                        <th class="text-center">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="cdm_offers_tbody">
                                    <!-- Dinamik -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. SEKME: SERVİS & PROJELER -->
                    <div class="tab-pane fade" id="pill-cdm-services" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-dark mb-0"><i class="fa fa-wrench text-success mr-1"></i> Firmanın Servis & Bakım Kayıtları</h6>
                            <a href="#" id="cdm_tab_new_service_btn" class="btn btn-sm btn-success" target="_blank" style="border-radius: 6px;">
                                <i class="fa fa-plus-circle mr-1"></i> Yeni Servis Aç
                            </a>
                        </div>
                        <div class="table-responsive bg-white rounded border">
                            <table class="table cdm-table">
                                <thead>
                                    <tr>
                                        <th>Servis No</th>
                                        <th>Servis / İş Türü</th>
                                        <th>Başlangıç Tarihi</th>
                                        <th>Tutar</th>
                                        <th class="text-center">Durum</th>
                                        <th class="text-center">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="cdm_services_tbody">
                                    <!-- Dinamik -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. SEKME: RAPOR & KEŞİFLER -->
                    <div class="tab-pane fade" id="pill-cdm-reports" role="tabpanel">
                        <h6 class="font-weight-bold text-dark mb-3"><i class="fa fa-clipboard text-warning mr-1"></i> Muayene & Periyodik Kontrol Raporları</h6>
                        <div class="table-responsive bg-white rounded border mb-4">
                            <table class="table cdm-table">
                                <thead>
                                    <tr>
                                        <th>Rapor No</th>
                                        <th>Rapor Türü</th>
                                        <th>Kontrol Tarihi</th>
                                        <th>Geçerlilik / Sonraki Kontrol</th>
                                        <th class="text-center">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="cdm_reports_tbody">
                                    <!-- Dinamik -->
                                </tbody>
                            </table>
                        </div>

                        <h6 class="font-weight-bold text-dark mb-3"><i class="fa fa-binoculars text-info mr-1"></i> Keşif Kayıtları</h6>
                        <div class="table-responsive bg-white rounded border">
                            <table class="table cdm-table">
                                <thead>
                                    <tr>
                                        <th>Keşif Tarihi</th>
                                        <th>Yapılacak İş</th>
                                        <th>Görevli Personel</th>
                                        <th>Sonuç / Not</th>
                                        <th class="text-center">Durum</th>
                                    </tr>
                                </thead>
                                <tbody id="cdm_kesif_tbody">
                                    <!-- Dinamik -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 5. SEKME: FİRMA KÜNYESİ -->
                    <div class="tab-pane fade" id="pill-cdm-info" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="bg-white p-3 rounded border">
                                    <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3"><i class="fa fa-building-o mr-1 text-primary"></i> Temel Şirket Bilgileri</h6>
                                    <table class="table table-sm detail-info-table mb-0">
                                        <tbody>
                                            <tr>
                                                <td>Firma Tam Ünvanı:</td>
                                                <td id="cdm_det_company">-</td>
                                            </tr>
                                            <tr>
                                                <td>Kısa / Ticari Ünvan:</td>
                                                <td id="cdm_det_sunvan">-</td>
                                            </tr>
                                            <tr>
                                                <td>Müşteri Grubu:</td>
                                                <td id="cdm_det_group">-</td>
                                            </tr>
                                            <tr>
                                                <td>Sektör:</td>
                                                <td id="cdm_det_sector">-</td>
                                            </tr>
                                            <tr>
                                                <td>Satış Temsilcisi:</td>
                                                <td id="cdm_det_represant">-</td>
                                            </tr>
                                            <tr>
                                                <td>Ödeme Vadesi:</td>
                                                <td id="cdm_det_vade">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="bg-white p-3 rounded border">
                                    <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3"><i class="fa fa-map-marker mr-1 text-danger"></i> İletişim & Konum Bilgileri</h6>
                                    <table class="table table-sm detail-info-table mb-0">
                                        <tbody>
                                            <tr>
                                                <td>İl / İlçe:</td>
                                                <td id="cdm_det_city_ilce">-</td>
                                            </tr>
                                            <tr>
                                                <td>Bölge / Konum:</td>
                                                <td id="cdm_det_location">-</td>
                                            </tr>
                                            <tr>
                                                <td>Açık Adres:</td>
                                                <td id="cdm_det_address">-</td>
                                            </tr>
                                            <tr>
                                                <td>Firma Yetkilisi:</td>
                                                <td id="cdm_det_yetkili">-</td>
                                            </tr>
                                            <tr>
                                                <td>GSM / Telefon:</td>
                                                <td id="cdm_det_gsm">-</td>
                                            </tr>
                                            <tr>
                                                <td>İkincil Telefon:</td>
                                                <td id="cdm_det_gsm2">-</td>
                                            </tr>
                                            <tr>
                                                <td>E-Posta Adresi:</td>
                                                <td id="cdm_det_email">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="bg-white p-3 rounded border">
                                    <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3"><i class="fa fa-clock-o mr-1 text-muted"></i> Sistem Kayıt ve Denetim Bilgisi</h6>
                                    <table class="table table-sm detail-info-table mb-0">
                                        <tbody>
                                            <tr>
                                                <td>Kayıt Yapan Kullanıcı:</td>
                                                <td id="cdm_det_creator">-</td>
                                            </tr>
                                            <tr>
                                                <td>Kayıt Tarihi:</td>
                                                <td id="cdm_det_regdate">-</td>
                                            </tr>
                                            <tr>
                                                <td>Son Güncelleyen:</td>
                                                <td id="cdm_det_updater">-</td>
                                            </tr>
                                            <tr>
                                                <td>Son Güncelleme Tarihi:</td>
                                                <td id="cdm_det_updated_at">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                    <a href="#" id="cdm_btn_new_offer" class="btn btn-primary btn-sm" target="_blank" style="border-radius: 6px; padding: 6px 14px;">
                        <i class="fa fa-plus-circle mr-1"></i> Yeni Teklif
                    </a>
                    <a href="#" id="cdm_btn_new_service" class="btn btn-success btn-sm" target="_blank" style="border-radius: 6px; padding: 6px 14px;">
                        <i class="fa fa-wrench mr-1"></i> Yeni Servis
                    </a>
                    <a href="#" id="cdm_btn_label" class="btn btn-outline-secondary btn-sm" target="_blank" style="border-radius: 6px; padding: 6px 12px;">
                        <i class="fa fa-print mr-1"></i> Etiket
                    </a>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <a href="#" id="cdm_btn_edit" class="btn btn-outline-primary btn-sm" style="border-radius: 6px; padding: 6px 16px;">
                        <i class="fa fa-pencil mr-1"></i> Düzenle
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius: 6px; padding: 6px 16px;">
                        Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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

    // Para formatlayıcı
    function formatMoneyTR(amount) {
        var num = parseFloat(amount) || 0;
        return num.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
    }

    // ApexCharts global instance'ları
    var cdmDonutChartInstance = null;
    var cdmBarChartInstance = null;

    function renderCdmDonutChart(won, pending, rejected, services) {
        var $el = document.querySelector("#cdmDonutChart");
        if (!$el) return;

        if (cdmDonutChartInstance) {
            try { cdmDonutChartInstance.destroy(); } catch(e) {}
            cdmDonutChartInstance = null;
        }

        var total = won + pending + rejected + services;
        var series = total > 0 ? [won, pending, rejected, services] : [0, 0, 0, 0];
        
        var options = {
            chart: {
                type: 'donut',
                width: 170,
                height: 170,
                fontFamily: 'inherit',
                sparkline: { enabled: true }
            },
            series: series,
            labels: ['Onaylandı', 'Bekleyen', 'Reddedildi', 'Servisler'],
            colors: ['#10b981', '#f59e0b', '#ef4444', '#0284c7'],
            stroke: { width: 2, colors: ['#ffffff'] },
            dataLabels: { enabled: false },
            legend: { show: false },
            tooltip: {
                enabled: true,
                y: {
                    formatter: function(val) {
                        return val + " Adet";
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            name: { show: false },
                            value: {
                                show: true,
                                fontSize: '18px',
                                fontWeight: 700,
                                color: '#0f172a',
                                offsetY: 6,
                                formatter: function(val) {
                                    return total > 0 ? total : '0';
                                }
                            },
                            total: {
                                show: true,
                                label: 'Toplam',
                                formatter: function(w) {
                                    return total > 0 ? total : '0';
                                }
                            }
                        }
                    }
                }
            }
        };

        cdmDonutChartInstance = new ApexCharts($el, options);
        cdmDonutChartInstance.render();
    }

    function renderCdmBarChart(monthlyFlow) {
        var $el = document.querySelector("#cdmBarChart");
        if (!$el) return;

        if (cdmBarChartInstance) {
            try { cdmBarChartInstance.destroy(); } catch(e) {}
            cdmBarChartInstance = null;
        }

        var categories = [];
        var totalVolumes = [];
        var wonVolumes = [];

        if (Array.isArray(monthlyFlow)) {
            monthlyFlow.forEach(function(item) {
                categories.push(item.label || item.period);
                totalVolumes.push(parseFloat(item.total_volume) || 0);
                wonVolumes.push(parseFloat(item.won_volume) || 0);
            });
        }

        var options = {
            chart: {
                type: 'bar',
                height: 180,
                toolbar: { show: false },
                fontFamily: 'inherit',
                animations: { enabled: true }
            },
            series: [
                { name: 'Teklif Hacmi', data: totalVolumes },
                { name: 'Onaylanan Ciro', data: wonVolumes }
            ],
            colors: ['#0284c7', '#10b981'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '45%',
                    borderRadius: 4
                }
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: categories,
                labels: {
                    style: { fontSize: '11px', colors: '#64748b' }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M ₺';
                        if (val >= 1000) return (val / 1000).toFixed(0) + 'K ₺';
                        return val.toFixed(0) + ' ₺';
                    },
                    style: { fontSize: '10.5px', colors: '#64748b' }
                }
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 3,
                padding: { top: 0, right: 0, bottom: 0, left: 0 }
            },
            legend: { show: false },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return formatMoneyTR(val);
                    }
                }
            }
        };

        cdmBarChartInstance = new ApexCharts($el, options);
        cdmBarChartInstance.render();
    }

    // Modal Açma Fonksiyonu
    function openCustomerDashboardModal(customerId) {
        var $modal = $('#customerDashboardModal');
        var $loader = $('#cdm_loader');
        var $content = $('#cdmTabContent');

        // Varsayılan sekmeye dön
        $('#tab-cdm-summary').tab('show');

        $modal.modal('show');
        $loader.show();
        $content.hide();

        $.ajax({
            url: 'App/api/customer.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'get_dashboard_summary',
                id: customerId
            },
            success: function (res) {
                if (res.status === 'success' && res.data) {
                    var d = res.data;
                    var cust = d.customer || {};
                    var kpi = d.kpi || {};

                    // Header
                    $('#cdm_company_title').text(cust.company || 'Firma Bilgisi');
                    $('#cdm_group_badge').text(cust.group_title || 'Müşteri');
                    var cityText = (cust.city ? cust.city : '') + (cust.ilce ? ' / ' + cust.ilce : '');
                    $('#cdm_city_badge').html('<i class="fa fa-map-marker text-danger mr-1"></i> ' + (cityText || 'Şehir Belirtilmemiş'));

                    // Buton Linkleri
                    $('#cdm_btn_edit').attr('href', d.edit_url || '#');
                    $('#cdm_btn_new_offer, #cdm_tab_new_offer_btn').attr('href', d.new_offer_url || '#');
                    $('#cdm_btn_new_service, #cdm_tab_new_service_btn').attr('href', d.new_service_url || '#');
                    $('#cdm_btn_label').attr('href', d.label_url || '#');

                    // Tab Sayaçları
                    $('#cdm_tab_offers_cnt').text(kpi.total_offers || 0);
                    $('#cdm_tab_services_cnt').text(kpi.total_projects || 0);
                    $('#cdm_tab_reports_cnt').text((kpi.total_reports || 0) + (kpi.total_kesif || 0));

                    // KPI Kartları
                    $('#cdm_kpi_total_offers_amount').text(formatMoneyTR(kpi.total_offers_amount || 0));
                    $('#cdm_kpi_total_offers_desc').text((kpi.total_offers || 0) + ' teklif (' + (kpi.won_offers || 0) + ' onaylı, ' + (kpi.pending_offers || 0) + ' beklemede)');
                    
                    $('#cdm_kpi_win_rate').text('%' + (kpi.win_rate || 0) + ' Başarı');
                    $('#cdm_kpi_won_amount').text(formatMoneyTR(kpi.won_offers_amount || 0));
                    $('#cdm_kpi_won_desc').text((kpi.won_offers || 0) + ' adet onaylanan teklif');

                    $('#cdm_kpi_services_badge').text((kpi.total_projects || 0) + ' Servis');
                    $('#cdm_kpi_completed_services').text((kpi.completed_projects || 0) + ' Tamamlanan');
                    $('#cdm_kpi_services_desc').text((kpi.active_projects || 0) + ' devam eden / açık servis');

                    $('#cdm_kpi_reports_value').text((kpi.total_reports || 0) + ' Rapor · ' + (kpi.total_kesif || 0) + ' Keşif');
                    $('#cdm_kpi_reports_desc').text('Sistem periyodik kayıtları aktif');

                    // Donut Lejantı
                    $('#cdm_leg_won').text(kpi.won_offers || 0);
                    $('#cdm_leg_pending').text(kpi.pending_offers || 0);
                    $('#cdm_leg_rejected').text(kpi.rejected_offers || 0);
                    $('#cdm_leg_services').text(kpi.total_projects || 0);

                    // Künye Kutusu (Özet)
                    $('#cdm_info_city_ilce').text((cust.city || '-') + ' / ' + (cust.ilce || '-'));
                    $('#cdm_info_location').text(cust.location || cust.region || 'Bölge Tanımlanmamış');
                    $('#cdm_info_address').text(cust.address || 'Açık adres girilmedi');
                    $('#cdm_info_official').text(cust.yetkili || 'Yetkili Tanımsız');
                    $('#cdm_info_phone').html('<i class="fa fa-phone text-success mr-1"></i> ' + (cust.gsm || cust.gsm2 || 'Telefon Yok'));
                    $('#cdm_info_email').html('<i class="fa fa-envelope-o text-primary mr-1"></i> ' + (cust.email || 'E-Posta Yok'));

                    // Mikro Rozetler
                    $('#cdm_micro_group').text(cust.group_title || '-');
                    $('#cdm_micro_rep').text(cust.represant || '-');
                    $('#cdm_micro_reg').text(cust.regdate ? cust.regdate.substring(0, 10) : '-');
                    $('#cdm_micro_vade').text(cust.OdemeVade ? cust.OdemeVade + ' Gün' : '-');

                    // Son Hareketler Listesi
                    var $actList = $('#cdm_activity_container');
                    $actList.empty();
                    var activities = d.recent_activities || [];
                    if (activities.length === 0) {
                        $actList.html('<div class="text-center py-4 text-muted font-12"><i class="fa fa-inbox fa-2x mb-2 d-block"></i>Henüz bir hareket kaydı bulunmuyor.</div>');
                    } else {
                        activities.forEach(function(act) {
                            var typeClass = act.type === 'offer' ? 'cdm-type-offer' : (act.type === 'service' ? 'cdm-type-service' : 'cdm-type-report');
                            var amountHtml = act.amount !== null && act.amount !== undefined ? '<span class="cdm-activity-amount">' + formatMoneyTR(act.amount) + '</span>' : '<span class="text-muted font-11">Muayene</span>';
                            
                            var itemHtml = '<div class="cdm-activity-item">' +
                                '<div class="cdm-activity-left">' +
                                    '<span class="cdm-type-pill ' + typeClass + '">' + (act.type_label || act.type) + '</span>' +
                                    '<div class="cdm-activity-text" title="' + (act.title || '') + '">' +
                                        '<strong>' + (act.number || '') + '</strong> ' + (act.title ? '· ' + act.title : '') +
                                    '</div>' +
                                '</div>' +
                                '<div class="d-flex align-items-center" style="gap: 10px;">' +
                                    amountHtml +
                                    '<span class="cdm-activity-date">' + (act.date || '-') + '</span>' +
                                '</div>' +
                            '</div>';
                            $actList.append(itemHtml);
                        });
                    }

                    // Teklifler Tablosu
                    var $offTbody = $('#cdm_offers_tbody');
                    $offTbody.empty();
                    var offers = d.offers || [];
                    if (offers.length === 0) {
                        $offTbody.html('<tr><td colspan="6" class="text-center py-3 text-muted">Kayıtlı teklif bulunamadı.</td></tr>');
                    } else {
                        offers.forEach(function(o) {
                            var statuBadge = '<span class="badge badge-warning">Beklemede</span>';
                            if (o.statu == 2) statuBadge = '<span class="badge badge-success">Onaylandı</span>';
                            if (o.statu == 3) statuBadge = '<span class="badge badge-danger">Reddedildi</span>';
                            
                            var offDate = o.created_at ? o.created_at.substring(0, 10) : (o.offer_date || '-');
                            var tr = '<tr>' +
                                '<td><strong>' + (o.offerNumber || ('#' + o.id)) + '</strong></td>' +
                                '<td>' + (o.offer_subject || '-') + '</td>' +
                                '<td>' + offDate + '</td>' +
                                '<td><strong>' + formatMoneyTR(o.tl_toplam_karsilik || o.total_price) + '</strong></td>' +
                                '<td class="text-center">' + statuBadge + '</td>' +
                                '<td class="text-center"><a href="index.php?p=offers/view&id=' + o.id + '" target="_blank" class="btn btn-xs btn-outline-info" title="İncele"><i class="fa fa-eye"></i></a></td>' +
                            '</tr>';
                            $offTbody.append(tr);
                        });
                    }

                    // Servisler Tablosu
                    var $servTbody = $('#cdm_services_tbody');
                    $servTbody.empty();
                    var services = d.projects || [];
                    if (services.length === 0) {
                        $servTbody.html('<tr><td colspan="6" class="text-center py-3 text-muted">Kayıtlı servis bulunamadı.</td></tr>');
                    } else {
                        services.forEach(function(s) {
                            var statBadge = '<span class="badge badge-secondary">İşlemde</span>';
                            if (s.pstatu == 17) statBadge = '<span class="badge badge-success">Tamamlandı</span>';
                            if (s.pstatu == 15) statBadge = '<span class="badge badge-warning">Bekliyor</span>';
                            if (s.pstatu == 34) statBadge = '<span class="badge badge-primary">Faturalandırıldı</span>';
                            
                            var sDate = s.pstart_date || '-';
                            var tr = '<tr>' +
                                '<td><strong>' + (s.service_number || ('#' + s.id)) + '</strong></td>' +
                                '<td>' + (s.service_title || '-') + '</td>' +
                                '<td>' + sDate + '</td>' +
                                '<td>' + (s.price > 0 ? formatMoneyTR(s.price) : '-') + '</td>' +
                                '<td class="text-center">' + statBadge + '</td>' +
                                '<td class="text-center"><a href="index.php?p=service-edit&id=' + s.id + '" target="_blank" class="btn btn-xs btn-outline-info" title="İncele"><i class="fa fa-eye"></i></a></td>' +
                            '</tr>';
                            $servTbody.append(tr);
                        });
                    }

                    // Raporlar Tablosu
                    var $repTbody = $('#cdm_reports_tbody');
                    $repTbody.empty();
                    var reports = d.reports || [];
                    if (reports.length === 0) {
                        $repTbody.html('<tr><td colspan="5" class="text-center py-3 text-muted">Kayıtlı rapor bulunamadı.</td></tr>');
                    } else {
                        reports.forEach(function(r) {
                            var tr = '<tr>' +
                                '<td><strong>' + (r.report_number || ('#' + r.id)) + '</strong></td>' +
                                '<td>' + (r.report_type_name || '-') + '</td>' +
                                '<td>' + (r.control_date || '-') + '</td>' +
                                '<td>' + (r.next_control_date || r.last_control_date || '-') + '</td>' +
                                '<td class="text-center"><a href="index.php?p=reports/view&id=' + r.id + '" target="_blank" class="btn btn-xs btn-outline-info"><i class="fa fa-eye"></i></a></td>' +
                            '</tr>';
                            $repTbody.append(tr);
                        });
                    }

                    // Keşif Tablosu
                    var $kesifTbody = $('#cdm_kesif_tbody');
                    $kesifTbody.empty();
                    var kesifler = d.kesifler || [];
                    if (kesifler.length === 0) {
                        $kesifTbody.html('<tr><td colspan="5" class="text-center py-3 text-muted">Kayıtlı keşif bulunamadı.</td></tr>');
                    } else {
                        kesifler.forEach(function(k) {
                            var tr = '<tr>' +
                                '<td>' + (k.kesif_tarihi || '-') + '</td>' +
                                '<td>' + (k.yapilacak_is || '-') + '</td>' +
                                '<td>' + (k.gidecek_kisi || '-') + '</td>' +
                                '<td>' + (k.kesif_sonu_notu || '-') + '</td>' +
                                '<td class="text-center"><span class="badge badge-info">' + (k.durum || 'Tamamlandı') + '</span></td>' +
                            '</tr>';
                            $kesifTbody.append(tr);
                        });
                    }

                    // Detaylı Künye
                    $('#cdm_det_company').text(cust.company || '-');
                    $('#cdm_det_sunvan').text(cust.sunvan || '-');
                    $('#cdm_det_group').text(cust.group_title || '-');
                    $('#cdm_det_sector').text(cust.sector || '-');
                    $('#cdm_det_represant').text(cust.represant || '-');
                    $('#cdm_det_vade').text(cust.OdemeVade ? cust.OdemeVade + ' Gün' : '-');
                    $('#cdm_det_city_ilce').text((cust.city || '-') + ' / ' + (cust.ilce || '-'));
                    $('#cdm_det_location').text(cust.location || cust.region || '-');
                    $('#cdm_det_address').text(cust.address || '-');
                    $('#cdm_det_yetkili').text(cust.yetkili || '-');
                    $('#cdm_det_gsm').text(cust.gsm || '-');
                    $('#cdm_det_gsm2').text(cust.gsm2 || '-');
                    $('#cdm_det_email').text(cust.email || '-');
                    $('#cdm_det_creator').text(cust.creator_name || '-');
                    $('#cdm_det_regdate').text(cust.regdate || '-');
                    $('#cdm_det_updater').text(cust.updater || '-');
                    $('#cdm_det_updated_at').text(cust.updated_at || '-');

                    $loader.hide();
                    $content.show();

                    // Grafikleri Çiz
                    setTimeout(function() {
                        renderCdmDonutChart(kpi.won_offers || 0, kpi.pending_offers || 0, kpi.rejected_offers || 0, kpi.total_projects || 0);
                        renderCdmBarChart(d.monthly_flow || []);
                    }, 150);

                } else {
                    $loader.html('<div class="alert alert-danger mx-3"><i class="fa fa-exclamation-triangle mr-1"></i> ' + (res.message || 'Müşteri bilgileri alınamadı.') + '</div>');
                }
            },
            error: function() {
                $loader.html('<div class="alert alert-danger mx-3"><i class="fa fa-exclamation-triangle mr-1"></i> Sunucu ile iletişim kurulurken bir hata oluştu.</div>');
            }
        });
    }

    $(document).ready(function () {
        // KPI Kartları Göster / Gizle Mantığı
        var KPI_STORAGE_KEY = 'aydinogullari_kpi_customers_collapsed';
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
            var newState = currentState; // visible ise collapsed (true) olacak
            localStorage.setItem(KPI_STORAGE_KEY, newState ? 'true' : 'false');
            updateKpiToggleState(newState, true);
        });

        // DataTables Kurulumu
        var customerTable = $('#customerlist').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            scrollX: false,
            ajax: {
                url: 'api/customers_datatables.php',
                type: 'GET'
            },
            columns: [
                { data: 0, className: 'text-center', width: '42px', orderable: true },
                { data: 1, width: '22%' },
                { data: 2, width: '11%' },
                { data: 3, width: '13%' },
                { data: 4, orderable: false, className: 'text-center', width: '13%' },
                { data: 5, width: '15%' },
                { data: 6, width: '12%' },
                { data: 7, className: 'text-center text-nowrap', width: '95px', orderable: true },
                { data: 8, orderable: false, className: 'text-center no-export', width: '85px' }
            ],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                url: 'include/js/tr.json',
                processing: '<div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"><span class="sr-only">Yükleniyor...</span></div>'
            },
            order: [[0, 'desc']],
            orderCellsTop: true,
            initComplete: function () {
                var api = this.api();
                
                // Arama kutusunu Form Card Header içine taşıma
                var $filterContainer = $('.form-card-header .dt-header-filter-box');
                var $searchBox = $('#customerlist_filter');
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

        // Yenile Butonu
        $('#btnRefreshCustomers').on('click', function() {
            var $icon = $(this).find('i');
            $icon.addClass('fa-spin');
            customerTable.ajax.reload(function() {
                setTimeout(function() {
                    $icon.removeClass('fa-spin');
                }, 300);
            }, false);
        });

        // Excel Export
        $('#exportCustomers').on('click', function () {
            showExportLoadingNotification();
            var query = $.param(customerTable.ajax.params());
            window.location.href = 'api/customers_export.php?' + query;
        });

        // 1. Firma Adına Tıklayınca Özet Dashboard Modalı Açma
        $(document).on('click', '.btn-customer-dashboard, a.customer-modal-trigger', function(e) {
            e.preventDefault();
            var customerId = $(this).data('id') || $(this).data('enc-id');
            if (customerId) {
                openCustomerDashboardModal(customerId);
            }
        });

        // Sekme içi yönlendirme linkleri
        $(document).on('click', '.js-go-tab', function(e) {
            e.preventDefault();
            var tabTarget = $(this).data('tab-target');
            if (tabTarget) {
                $(tabTarget).tab('show');
            }
        });

        // Sekme değişiminde ApexCharts resize tetikleme
        $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
            window.dispatchEvent(new Event('resize'));
        });

        // Tabloda Sağ Tık (Context Menu) İşlemleri
        $(document).on('contextmenu', '#customerlist tbody tr', function(e) {
            if ($(this).find('td').length <= 1) return;

            e.preventDefault();
            
            var $tr = $(this);
            $('#customerlist tbody tr').removeClass('context-menu-active');
            $tr.addClass('context-menu-active');

            var companyName = $tr.find('td:nth-child(2)').text().trim() || 'Müşteri İşlemleri';
            var $companyLink = $tr.find('.btn-customer-dashboard');
            var customerId = $companyLink.data('id') || $tr.find('td:first-child .row-index-badge').text().trim();
            var $actionTd = $tr.find('td:last-child');
            
            var menuHtml = '<div class="cm-header"><i class="fa fa-building-o mr-1"></i> ' + $('<div>').text(companyName).html() + '</div>';

            // 0. Özet Dashboard Seçeneği (En başta)
            if (customerId) {
                menuHtml += '<a href="#" class="btn-context-dashboard" data-id="' + customerId + '"><i class="fa fa-dashboard text-primary mr-2"></i> Özet Dashboard</a>';
            }

            // 1. Düzenle Butonu Varsa
            var $editBtn = $actionTd.find('a[data-tooltip="Görüntüle / Düzenle"], a.btn-outline-info');
            if ($editBtn.length) {
                menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-info mr-2"></i> Görüntüle / Düzenle</a>';
            }

            // 2. Dropdown içindeki elemanlar
            var $dropdownItems = $actionTd.find('.dropdown-menu .dropdown-item');
            if ($dropdownItems.length) {
                $dropdownItems.each(function() {
                    var $item = $(this);
                    var href = $item.attr('href') || '#';
                    var target = $item.attr('target') ? ' target="' + $item.attr('target') + '"' : '';
                    var text = $item.html();
                    var dataId = $item.attr('data-id') ? ' data-id="' + $item.attr('data-id') + '"' : '';
                    var classAttr = $item.attr('class') || '';

                    menuHtml += '<a href="' + href + '"' + target + dataId + ' class="' + classAttr + '">' + text + '</a>';
                });
            }

            // 3. Sil Butonu Varsa
            var $deleteBtn = $actionTd.find('a[data-tooltip="Sil"], a.btn-outline-danger');
            if ($deleteBtn.length) {
                menuHtml += '<div class="cm-divider"></div>';
                var onClickAttr = $deleteBtn.attr('onclick') || $deleteBtn.attr('onClick') || '';
                menuHtml += '<a href="#" class="cm-danger" onclick="' + onClickAttr + '; return false;"><i class="fa fa-trash text-danger mr-2"></i> Müşteriyi Sil</a>';
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

        // Context Menüden Dashboard Açma
        $(document).on('click', '.btn-context-dashboard', function(e) {
            e.preventDefault();
            var customerId = $(this).data('id');
            if (customerId) {
                openCustomerDashboardModal(customerId);
            }
        });

        // Menü dışına tıklanınca kapat
        $(document).on('click scroll', function(e) {
            if (!$(e.target).closest('#customContextMenu').length) {
                $('#customContextMenu').hide();
                $('#customerlist tbody tr').removeClass('context-menu-active');
            }
        });

        // Menüdeki seçeneğe basılınca kapat
        $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
            $('#customContextMenu').hide();
            $('#customerlist tbody tr').removeClass('context-menu-active');
        });

        // ESC basılınca kapat
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#customContextMenu').hide();
                $('#customerlist tbody tr').removeClass('context-menu-active');
            }
        });

        // Detay butonu AJAX
        $(document).on("click", ".btn-detail", function () {
            var id = $(this).data("id");
            $.ajax({
                method: "POST",
                url: "pages/1/ajax.php?type=customer-detail",
                dataType: "json",
                data: { id: id },
                success: function (response) {
                    $("#customerdetails").modal("show");
                    $("#creator").text(response.creator || '-');
                    $("#create_time").text(response.create_time || '-');
                    $("#updater").text(response.updater || '-');
                    $("#updated_at").text(response.updated_at || '-');
                }
            });
        });

        $(".closeModal").click(function () {
            $("#customerdetails").modal("hide");
        });
    });
</script>

