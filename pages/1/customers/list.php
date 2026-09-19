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
    if ($contq->fetch(PDO::FETCH_ASSOC)) {
        $CustomerModel->softDelete($cdid, $_SESSION['lid'] ?? 0);
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
                        <h5>Müşteri ve Cari Listesi</h5>
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
                            <th style="width: 120px;" class="no-sort text-center">Teklif / Servis</th>
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
                            <th class="text-center">Teklif / Servis</th>
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

        // Tabloda Sağ Tık (Context Menu) İşlemleri
        $(document).on('contextmenu', '#customerlist tbody tr', function(e) {
            if ($(this).find('td').length <= 1) return;

            e.preventDefault();
            
            var $tr = $(this);
            $('#customerlist tbody tr').removeClass('context-menu-active');
            $tr.addClass('context-menu-active');

            var companyName = $tr.find('td:nth-child(2)').text().trim() || 'Müşteri İşlemleri';
            var $actionTd = $tr.find('td:last-child');
            
            var menuHtml = '<div class="cm-header"><i class="fa fa-building-o mr-1"></i> ' + $('<div>').text(companyName).html() + '</div>';

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

