<?php

$pids = @$_GET['id'];

if ((@$_GET["st"] ?? "") == "success-mail") {
    showAlert("success", "Mail başarı ile gönderildi!");
} else if ((@$_GET["st"] ?? "") == "unsuccessful") {
    showAlert("alert", "Mail gönderilirken bir hata oluştu");
}

use App\Helper\Helper;
use App\Model\PurchaseModel;

$purchaseModel = new PurchaseModel();

// KPI İstatistikleri
$stats = $purchaseModel->getListKPIStats();
$totalCount = (int)($stats['total_count'] ?? 0);
$pendingCount = (int)($stats['pending_count'] ?? 0);
$approvedCount = (int)($stats['approved_count'] ?? 0);
$completedCount = (int)($stats['completed_count'] ?? 0);
$demandCount = (int)($stats['demand_count'] ?? 0);
$priceReqCount = (int)($stats['price_req_count'] ?? 0);
$orderCount = (int)($stats['order_count'] ?? 0);
$completedRate = $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0;

try {
    $logger = \getLogger("Satın Alma");
    $logger->info("Satın alma listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_purchases_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-purchases-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* ==========================================
       PREMIUM PURCHASES LIST THEME
       ========================================== */
    .kpi-purchases-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    .purchases-list-wrapper {
        width: 100%;
        padding: 0;
        margin: 0;
    }

    .purchases-list-page-container {
        padding: 0;
        width: 100%;
    }

    /* Page Header Styles */
    .page-title-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        padding: 4px 0 10px 0;
        margin-bottom: 12px !important;
    }
    .page-title-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .page-title-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        flex-shrink: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    .page-title-text h4 {
        margin: 0;
        font-size: 19px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.3px;
        line-height: 1.2;
    }
    .page-title-text p {
        margin: 3px 0 0 0;
        font-size: 13px;
        color: #64748b;
        line-height: 1.3;
    }

    .page-title-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .page-title-actions .btn {
        height: 36px;
        padding: 0 14px;
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }

    /* KPI Summary Cards */
    .kpi-summary-collapse {
        margin-bottom: 14px !important;
    }
    .crm-kpi-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 16px 18px;
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
        margin-bottom: 10px;
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
        font-size: 23px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .crm-kpi-icon-box {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
    }
    .crm-kpi-icon-blue { background: #eff6ff; color: #2563eb; }
    .crm-kpi-icon-amber { background: #fffbeb; color: #d97706; }
    .crm-kpi-icon-indigo { background: #e0e7ff; color: #4338ca; }
    .crm-kpi-icon-emerald { background: #ecfdf5; color: #059669; }

    .crm-kpi-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11.5px;
        color: #64748b;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
        margin-top: 4px;
    }

    /* Form Card & Table Integration */
    .form-card {
        background: #ffffff;
        border-radius: 14px !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        padding: 0 !important;
        overflow: hidden !important;
        margin-bottom: 25px;
    }

    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px 4px 14px;
        margin-bottom: 0;
        border-bottom: none !important;
        flex-wrap: wrap;
        gap: 12px;
    }

    .header-left-inner {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .header-left-inner h5 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
    }
    .header-left-inner .badge {
        font-size: 12px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .form-card .table-responsive,
    .purchases-list-wrapper .table-responsive,
    .purchases-list-page-container .table-responsive,
    .table-responsive {
        padding: 4px 8px 10px 8px !important;
        margin: 0 !important;
        border: none !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch !important;
        width: 100% !important;
        display: block !important;
    }
    .company-name-cell,
    #purchasesTable th.col-company,
    #purchasesTable td.company-name-cell {
        max-width: 170px !important;
        width: 170px !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .form-card .dataTables_wrapper {
        padding: 0 !important;
        width: 100% !important;
    }
    .form-card .dataTables_wrapper .dataTables_filter,
    .form-card .dataTables_wrapper .dataTables_length {
        display: none !important;
    }
    .form-card .dataTables_wrapper .row:last-child {
        padding: 12px 0 0 0 !important;
        margin: 0 !important;
        border-top: none !important;
        background: transparent !important;
    }

    /* Dark Mode Table Card Support */
    .dark-mode .form-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark-mode .form-card-header {
        border-bottom: none !important;
    }
    .dark-mode .form-card-header h5 {
        color: #f8fafc !important;
    }
    .dark-mode .form-card .dataTables_wrapper .row:last-child {
        background: transparent !important;
        border-top: none !important;
    }

    /* Search & Toggle Button */
    .dt-header-filter-box {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dt-custom-search-input {
        height: 34px;
        font-size: 13px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 6px 12px;
        width: 220px;
        transition: all 0.2s ease;
    }
    .dt-custom-search-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        width: 260px;
    }
    .btn-toggle-kpi {
        height: 34px;
        width: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #64748b;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-toggle-kpi:hover {
        background: #f8fafc;
        color: #1e293b;
        border-color: #94a3b8;
    }

    /* Filter Button Active States */
    .filter-demand-toggle {
        height: 32px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 12px;
    }

    /* Table Base Styling */
    #purchasesTable {
        margin: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        border-radius: 10px !important;
        border: 1px solid #cbd5e1 !important;
        width: 100% !important;
        table-layout: fixed !important;
        overflow: hidden !important;
    }
    #purchasesTable thead th {
        position: relative !important;
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        padding: 9px 4px !important;
        border-bottom: 1px solid #cbd5e1 !important;
        border-top: none !important;
        border-left: none !important;
        border-right: 1px solid #e2e8f0 !important;
        vertical-align: middle;
        white-space: nowrap;
        overflow: visible;
    }
    #purchasesTable thead th:first-child {
        border-top-left-radius: 9px !important;
    }
    #purchasesTable thead th:last-child {
        border-top-right-radius: 9px !important;
        border-right: none !important;
    }
    #purchasesTable thead th.tf-header-cell {
        padding-right: 24px !important;
    }
    #purchasesTable thead th .tf-trigger {
        right: 2px !important;
        width: 18px !important;
        height: 18px !important;
    }
    #purchasesTable thead th.sorting,
    #purchasesTable thead th.sorting_asc,
    #purchasesTable thead th.sorting_desc {
        padding-left: 4px !important;
    }
    #purchasesTable thead th:not(.sorting):not(.sorting_asc):not(.sorting_desc) {
        padding-left: 4px !important;
    }
    #purchasesTable tbody td {
        padding: 8px 4px !important;
        vertical-align: middle;
        font-size: 12px;
        color: #334155;
        border-top: none !important;
        border-bottom: 1px solid #f1f5f9 !important;
        border-left: none !important;
        border-right: 1px solid #f1f5f9 !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #purchasesTable tbody td:last-child {
        border-right: none !important;
    }
    #purchasesTable tbody tr:last-child td {
        border-bottom: none !important;
    }
    #purchasesTable tbody tr:last-child td:first-child {
        border-bottom-left-radius: 9px !important;
    }
    #purchasesTable tbody tr:last-child td:last-child {
        border-bottom-right-radius: 9px !important;
    }
    #purchasesTable tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Row Index Badge */
    .row-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 4px;
        background: #334155;
        color: #ffffff !important;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .dark-mode .row-index-badge {
        background: #0f172a !important;
        color: #38bdf8 !important;
        border: 1px solid #334155;
    }

    /* Document Type Badges */
    .badge-type-demand {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        font-weight: 600;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .badge-type-price {
        background-color: #ede9fe;
        color: #6d28d9;
        border: 1px solid #ddd6fe;
        font-weight: 600;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .badge-type-order {
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        font-weight: 600;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
    }

    /* Soft Status Badges */
    .badge-soft-warning {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        font-size: 11px;
        padding: 3px 6px;
    }
    .badge-soft-info {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        font-size: 11px;
        padding: 3px 6px;
    }
    .badge-soft-success {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
        font-size: 11px;
        padding: 3px 6px;
    }
    .badge-soft-danger {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
        font-size: 11px;
        padding: 3px 6px;
    }

    /* Action Buttons in Row */
    .action-btn-group {
        display: inline-flex !important;
        align-items: center !important;
        gap: 3px !important;
        justify-content: center !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }
    .action-btn {
        width: 26px !important;
        height: 26px !important;
        min-width: 26px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 5px !important;
        font-size: 11.5px !important;
        line-height: 1 !important;
        transition: all 0.15s ease !important;
        cursor: pointer !important;
        text-decoration: none !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.12);
    }
    .action-btn i {
        font-size: 11.5px !important;
        line-height: 1 !important;
        pointer-events: none;
    }

    /* Context Menu */
    .custom-context-menu {
        position: fixed;
        display: none;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
        min-width: 200px;
        padding: 6px 0;
        z-index: 99999;
        animation: ctxMenuFadeIn 0.15s ease-out;
    }
    @keyframes ctxMenuFadeIn {
        from { opacity: 0; transform: scale(0.96); }
        to { opacity: 1; transform: scale(1); }
    }
    .custom-context-menu .ctx-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 16px;
        font-size: 13px;
        color: #334155;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .custom-context-menu .ctx-item:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .custom-context-menu .ctx-item i {
        font-size: 14px;
        width: 16px;
        text-align: center;
        color: #64748b;
    }
    .custom-context-menu .ctx-item:hover i {
        color: #2563eb;
    }
    .custom-context-menu .ctx-item.text-danger:hover i {
        color: #dc2626;
    }
    .custom-context-menu .ctx-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 4px 0;
    }
</style>

<div class="purchases-list-wrapper">
    <div class="purchases-list-page-container">

        <!-- Header Section -->
        <div class="page-title-box">
            <div class="page-title-left">
                <div class="page-title-icon">
                    <i class="fa fa-shopping-cart"></i>
                </div>
                <div class="page-title-text">
                    <h4>Satın Alma Yönetimi</h4>
                    <p>Satın alma taleplerini, fiyat taleplerini ve sipariş süreçlerini yönetin</p>
                </div>
            </div>

            <div class="page-title-actions">
                <a href="index.php?p=purchases/dashboard" class="btn btn-outline-info shadow-sm">
                    <i class="fa fa-dashboard"></i> Dashboard
                </a>
                <?php if (permtrue('purchase-demand-add')) { ?>
                    <a href="index.php?p=purchase-demand-new" class="btn btn-primary shadow-sm">
                        <i class="fa fa-plus"></i> Yeni Talep
                    </a>
                <?php } ?>
                <?php if (permtrue('purchaseadd')) { ?>
                    <a href="yeni-siparis" class="btn btn-success shadow-sm">
                        <i class="fa fa-plus"></i> Yeni Sipariş
                    </a>
                <?php } ?>
                <button type="button" class="btn btn-outline-secondary shadow-sm" id="btnExportExcel">
                    <i class="fa fa-file-excel-o text-success"></i> Excel
                </button>
                <button type="button" class="btn btn-outline-secondary shadow-sm" id="btnRefreshTable" style="padding: 0 11px;" title="Tabloyu Yenile">
                    <i class="fa fa-refresh"></i>
                </button>
            </div>
        </div>

        <!-- KPI Summary Section -->
        <div id="kpiSummarySection" class="kpi-summary-collapse">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">
                
                <!-- 1. Toplam Kayıt -->
                <div class="col">
                    <div class="crm-kpi-card">
                        <div class="crm-kpi-header">
                            <div>
                                <span class="crm-kpi-label">Toplam İşlem</span>
                                <div class="crm-kpi-value"><?php echo number_format($totalCount, 0, ',', '.'); ?></div>
                            </div>
                            <div class="crm-kpi-icon-box crm-kpi-icon-blue">
                                <i class="fa fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="crm-kpi-footer">
                            <span><i class="fa fa-tag text-muted mr-1"></i> <?php echo $demandCount; ?> Talep / <?php echo $orderCount; ?> Sipariş</span>
                            <span class="text-primary font-weight-bold"><?php echo $priceReqCount; ?> FT</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Bekleyen Talepler/Siparişler -->
                <div class="col">
                    <div class="crm-kpi-card">
                        <div class="crm-kpi-header">
                            <div>
                                <span class="crm-kpi-label">Bekleyen İşlemler</span>
                                <div class="crm-kpi-value text-warning"><?php echo number_format($pendingCount, 0, ',', '.'); ?></div>
                            </div>
                            <div class="crm-kpi-icon-box crm-kpi-icon-amber">
                                <i class="fa fa-clock-o"></i>
                            </div>
                        </div>
                        <div class="crm-kpi-footer">
                            <span>İşlem Bekleyen Kayıtlar</span>
                            <span class="badge badge-soft-warning font-weight-bold"><?php echo $totalCount > 0 ? round(($pendingCount / $totalCount) * 100, 1) : 0; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Onaylanan / Süreçte -->
                <div class="col">
                    <div class="crm-kpi-card">
                        <div class="crm-kpi-header">
                            <div>
                                <span class="crm-kpi-label">Onaylanan / Süreçte</span>
                                <div class="crm-kpi-value text-indigo" style="color: #4338ca;"><?php echo number_format($approvedCount, 0, ',', '.'); ?></div>
                            </div>
                            <div class="crm-kpi-icon-box crm-kpi-icon-indigo">
                                <i class="fa fa-hourglass-half"></i>
                            </div>
                        </div>
                        <div class="crm-kpi-footer">
                            <span>Tedarik / Sipariş Aşaması</span>
                            <span class="badge badge-soft-info font-weight-bold"><?php echo $totalCount > 0 ? round(($approvedCount / $totalCount) * 100, 1) : 0; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Tamamlanan -->
                <div class="col">
                    <div class="crm-kpi-card">
                        <div class="crm-kpi-header">
                            <div>
                                <span class="crm-kpi-label">Tamamlanan</span>
                                <div class="crm-kpi-value text-success"><?php echo number_format($completedCount, 0, ',', '.'); ?></div>
                            </div>
                            <div class="crm-kpi-icon-box crm-kpi-icon-emerald">
                                <i class="fa fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="crm-kpi-footer">
                            <span>Sonuçlandırılmış Süreçler</span>
                            <span class="badge badge-soft-success font-weight-bold"><?php echo $completedRate; ?>%</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Table Card -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="header-left-inner">
                    <h5 class="m-0 font-weight-bold">Satın Alma & Talep Listesi</h5>
                    <span class="badge badge-secondary" id="purchasesCountBadge"><?php echo $totalCount; ?> Kayıt</span>
                    <button type="button" id="showdemand" class="btn btn-sm btn-outline-primary filter-demand-toggle ml-2" data-toggle="button" aria-pressed="false">
                        <i class="fa fa-filter mr-1"></i> <span>Sadece Bekleyenleri Göster</span>
                    </button>
                </div>

                <div class="dt-header-filter-box">
                    <input type="text" id="purchasesCustomSearch" class="dt-custom-search-input" placeholder="Listede ara..." autocomplete="off">
                    <button type="button" class="btn-toggle-kpi" id="toggleKpiSummary" title="Özet Kartlarını Gizle/Göster">
                        <i class="fa fa-chevron-up"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="purchasesTable" class="table table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th style="width: 3.5%;" class="text-center no-filter">#</th>
                            <th style="width: 8%;">Sipariş No</th>
                            <th style="width: 18%;">Firma Adı</th>
                            <th style="width: 8%;" class="text-center">Kayıt Tarihi</th>
                            <th style="width: 7.5%;" class="text-center">Termin</th>
                            <th style="width: 8.5%;" class="text-right">Toplam</th>
                            <th style="width: 8%;" class="text-center">Durum</th>
                            <th style="width: 5%;" class="text-center">Vade</th>
                            <th style="width: 7%;">Fatura No</th>
                            <th style="width: 8%;" class="text-center">Fatura Tarihi</th>
                            <th style="width: 8.5%;">Oluşturan</th>
                            <th style="width: 6%;" class="text-center">Tip</th>
                            <th style="width: 10%;" class="text-center no-filter">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Server-side AJAX ile doldurulur -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Context Menu (Sağ Tık Menüsü) -->
<div id="purchasesContextMenu" class="custom-context-menu">
    <a href="#" class="ctx-item" id="ctxEdit">
        <i class="fa fa-pencil"></i> Düzenle
    </a>
    <a href="#" class="ctx-item" id="ctxCreateOrder" style="display: none;">
        <i class="fa fa-shopping-cart text-primary"></i> Sipariş Oluştur
    </a>
    <a href="#" class="ctx-item" id="ctxViewDemand" target="_blank">
        <i class="fa fa-file-text-o text-info"></i> Talep Formunu Göster
    </a>
    <a href="#" class="ctx-item" id="ctxViewOrder" target="_blank">
        <i class="fa fa-file-text text-success"></i> Sipariş Formunu Göster
    </a>
    <a href="#" class="ctx-item" id="ctxToggleDone" style="display: none;">
        <i class="fa fa-check-square-o text-warning"></i> Durumu Güncelle
    </a>
    <a href="#" class="ctx-item" id="ctxSendMail">
        <i class="fa fa-envelope text-secondary"></i> Mail Gönder
    </a>
    <div class="ctx-divider"></div>
    <a href="#" class="ctx-item text-danger" id="ctxDelete">
        <i class="fa fa-trash"></i> Sil
    </a>
</div>

<script>
$(document).ready(function() {
    var isFilteredWaiting = false;

    // DataTable Başlatma (Server-Side AJAX)
    var table = $('#purchasesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "api/purchases_datatables.php",
            type: "GET",
            data: function(d) {
                d.only_pending = isFilteredWaiting ? 1 : 0;
            },
            error: function(xhr, error, thrown) {
                console.error("Purchases DataTable Ajax Error:", error, thrown, xhr.responseText);
            }
        },
        columns: [
            { data: 0, orderable: false, searchable: false, className: "text-center" },
            { data: 1 },
            { data: 2, className: "company-name-cell" },
            { data: 3, className: "text-center" },
            { data: 4, className: "text-center" },
            { data: 5, className: "text-right" },
            { data: 6, className: "text-center" },
            { data: 7, className: "text-center" },
            { data: 8, className: "text-center" },
            { data: 9, className: "text-center" },
            { data: 10 },
            { data: 11, className: "text-center" },
            { data: 12, orderable: false, searchable: false, className: "text-center" }
        ],
        createdRow: function(row, data, dataIndex) {
            if (data && data.DT_RowAttr) {
                $.each(data.DT_RowAttr, function(key, val) {
                    $(row).attr(key, val);
                });
            }
        },
        responsive: false,
        scrollX: false,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tümü"]],
        order: [[3, 'desc']],
        language: {
            url: "include/js/tr.json",
            search: "",
            searchPlaceholder: "Listede ara..."
        },
        dom: "<'row'<'col-sm-12'tr>>" +
             "<'row align-items-center mt-2 px-2 pb-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
        initComplete: function() {
            if (window.App && window.App.TableFilter) {
                App.TableFilter.attachToTable(document.getElementById('purchasesTable'));
            }
        },
        drawCallback: function(settings) {
            var api = this.api();
            var total = api.page.info().recordsTotal;
            $('#purchasesCountBadge').text(total + ' Kayıt');
            if (typeof $('[data-toggle="tooltip"]').tooltip === 'function') {
                $('[data-toggle="tooltip"]').tooltip();
            }
        }
    });

    function filterWaitingDemands() {
        isFilteredWaiting = true;
        table.ajax.reload();
        $('#showdemand').find('span').text('Tüm Kayıtları Göster');
        $('#showdemand').removeClass('btn-outline-primary').addClass('btn-primary');
    }

    function showAllDemands() {
        isFilteredWaiting = false;
        table.ajax.reload();
        $('#showdemand').find('span').text('Sadece Bekleyenleri Göster');
        $('#showdemand').removeClass('btn-primary').addClass('btn-outline-primary');
    }

    // Filtre Butonu
    $('#showdemand').on('click', function(e) {
        e.preventDefault();
        if (isFilteredWaiting) {
            showAllDemands();
        } else {
            filterWaitingDemands();
        }
    });

    // Özel Arama Kutusu Entegrasyonu
    $('#purchasesCustomSearch').on('keyup input', function() {
        table.search(this.value).draw();
    });

    // Yenile Butonu
    $('#btnRefreshTable').on('click', function() {
        var $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        setTimeout(function() {
            location.reload();
        }, 300);
    });

    // KPI Summary Gizle / Göster (Toggle)
    var kpiKey = 'aydinogullari_kpi_purchases_collapsed';
    var $kpiSection = $('#kpiSummarySection');
    var $toggleBtn = $('#toggleKpiSummary');

    function updateKpiToggleState(collapsed, animate) {
        if (collapsed) {
            if (animate) {
                $kpiSection.slideUp(200);
            } else {
                $kpiSection.hide();
            }
            $toggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $toggleBtn.attr('title', 'Özet Kartlarını Göster');
            $('html').addClass('kpi-purchases-collapsed-early');
        } else {
            if (animate) {
                $kpiSection.slideDown(200);
            } else {
                $kpiSection.show();
            }
            $toggleBtn.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $toggleBtn.attr('title', 'Özet Kartlarını Gizle');
            $('html').removeClass('kpi-purchases-collapsed-early');
        }
    }

    if (localStorage.getItem(kpiKey) === 'true') {
        updateKpiToggleState(true, false);
    }

    $toggleBtn.on('click', function() {
        var isCurrentlyCollapsed = localStorage.getItem(kpiKey) === 'true';
        var newState = !isCurrentlyCollapsed;
        localStorage.setItem(kpiKey, newState);
        updateKpiToggleState(newState, true);
    });

    // Excel Dışa Aktarma (SheetJS / XLSX - Lazy Loading)
    $('#btnExportExcel').on('click', function() {
        function exportPurchasesToExcel() {
            var data = [];
            data.push([
                "Sıra",
                "Sipariş / Talep No",
                "Firma Adı",
                "Kayıt Tarihi",
                "Termin Tarihi",
                "Toplam Fiyat",
                "Durum",
                "Ödeme Vadesi",
                "Fatura No",
                "Fatura Tarihi",
                "Oluşturan",
                "Tip"
            ]);

            $('#purchasesTable tbody tr').each(function(idx) {
                var $row = $(this);
                if ($row.find('td').length > 1) {
                    var cols = [];
                    cols.push(idx + 1);
                    cols.push($row.find('td').eq(1).text().trim());
                    cols.push($row.find('td').eq(2).text().trim());
                    cols.push($row.find('td').eq(3).text().trim());
                    cols.push($row.find('td').eq(4).text().trim());
                    cols.push($row.find('td').eq(5).text().trim());
                    cols.push($row.find('td').eq(6).text().trim());
                    cols.push($row.find('td').eq(7).text().trim());
                    cols.push($row.find('td').eq(8).text().trim());
                    cols.push($row.find('td').eq(9).text().trim());
                    cols.push($row.find('td').eq(10).text().trim());
                    cols.push($row.find('td').eq(11).text().trim());
                    data.push(cols);
                }
            });

            var ws = XLSX.utils.aoa_to_sheet(data);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Satın Alma Listesi");
            var filename = "Satin_Alma_Listesi_" + new Date().toISOString().slice(0, 10) + ".xlsx";
            XLSX.writeFile(wb, filename);
        }

        if (typeof XLSX === 'undefined') {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
            script.onload = exportPurchasesToExcel;
            document.head.appendChild(script);
        } else {
            exportPurchasesToExcel();
        }
    });

    // Sipariş Talebini Tamamlandı Yap / Güncelle
    $(document).on('click', '.done-demand', function(e) {
        e.preventDefault();
        let purchaseId = $(this).data('id');

        let formData = new FormData();
        formData.append('id', purchaseId);
        formData.append('action', 'doneDemand');

        Swal.fire({
            title: "Emin misiniz?",
            text: "Satın alma talep durumu güncellenecektir!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#2563eb",
            cancelButtonColor: "#d33",
            confirmButtonText: "Evet, Güncelle!",
            cancelButtonText: "Vazgeç"
        }).then((result) => {
            if (result.isConfirmed) {
                fetch("App/api/purchase.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Başarılı!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Tamam'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Hata!', data.message || 'İşlem başarısız oldu.', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Hata!', 'Sunucuyla iletişim kurulurken bir sorun oluştu.', 'error');
                });
            }
        });
    });

    // ==========================================
    // SAĞ TIK MENÜSÜ (CONTEXT MENU)
    // ==========================================
    var $contextMenu = $('#purchasesContextMenu');
    var activeContextRow = null;

    $('#purchasesTable tbody').on('contextmenu', 'tr', function(e) {
        var $row = $(this);
        if ($row.find('td').length <= 1) return;

        e.preventDefault();
        activeContextRow = $row;

        var pid = $row.data('id');
        var siparisNo = $row.data('siparis-no');
        var editLink = $row.data('edit-link');
        var type = parseInt($row.data('type')) || 0;
        var state = parseInt($row.data('state')) || 0;

        // Düzenle
        $('#ctxEdit').attr('href', editLink);

        // Sipariş Oluştur (Talep ve Bekliyor ise)
        if (type === 1 && state === 0) {
            $('#ctxCreateOrder').attr('href', 'index.php?p=purchases/manage&talep_id=' + pid + '&demand=true').show();
        } else {
            $('#ctxCreateOrder').hide();
        }

        // Form Görüntüleme
        $('#ctxViewDemand').attr('href', 'index.php?p=purchase-demand-detail&id=' + pid);
        $('#ctxViewOrder').attr('href', 'index.php?p=purchase-detail&id=' + pid);

        // Durumu Tamamla / Güncelle (Talep ise)
        if (type === 1) {
            var toggleText = (state === 0) ? 'Tamamlandı Olarak İşaretle' : 'Bekliyor Olarak İşaretle';
            $('#ctxToggleDone').html('<i class="fa fa-check-square-o text-warning"></i> ' + toggleText)
                              .data('id', pid)
                              .show();
        } else {
            $('#ctxToggleDone').hide();
        }

        // Mail Gönder
        $('#ctxSendMail').attr('href', 'index.php?p=report-send-as-mail&type=purchase&id=' + pid);

        // Sil
        <?php if (permtrue('purchasedelete')) { ?>
            if (state === 2) {
                $('#ctxDelete').addClass('text-muted disabled').removeClass('text-danger').off('click').on('click', function(ev) {
                    ev.preventDefault();
                    Swal.fire('Bilgi', 'Tamamlanmış kayıtlar silinemez!', 'info');
                });
            } else {
                $('#ctxDelete').removeClass('text-muted disabled').addClass('text-danger').off('click').on('click', function(ev) {
                    ev.preventDefault();
                    $contextMenu.hide();
                    deleteRecord(siparisNo + ' nolu kaydı silmek istediğinize emin misiniz?', pid, 'purchases', null, '/satin-almalar');
                });
            }
        <?php } else { ?>
            $('#ctxDelete').hide();
        <?php } ?>

        // Menü Konumlandırma
        var mouseX = e.pageX;
        var mouseY = e.pageY;
        var menuWidth = 220;
        var menuHeight = 220;
        var winWidth = $(window).width();
        var winHeight = $(window).height();

        if (mouseX + menuWidth > winWidth) mouseX = winWidth - menuWidth - 10;
        if (mouseY + menuHeight > winHeight + $(window).scrollTop()) mouseY = mouseY - menuHeight;

        $contextMenu.css({
            left: mouseX + 'px',
            top: mouseY + 'px'
        }).fadeIn(120);
    });

    $('#ctxToggleDone').on('click', function(e) {
        e.preventDefault();
        $contextMenu.hide();
        var pid = $(this).data('id');
        $('.done-demand[data-id="' + pid + '"]').trigger('click');
    });

    $(document).on('click', function() {
        $contextMenu.hide();
    });

    $(window).on('scroll resize', function() {
        $contextMenu.hide();
    });
});
</script>
