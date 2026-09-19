<?php

$pids = @$_GET['id'];

if ((@$_GET["st"] ?? "") == "success-mail") {
    showAlert("success", "Mail başarı ile gönderildi!");
} else if ((@$_GET["st"] ?? "") == "unsuccessful") {
    showAlert("alert", "Mail gönderilirken bir hata oluştu");
}

use App\Helper\Helper;

// KPI İstatistikleri
$statsQuery = $ac->query("
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN state = 0 THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN state = 1 THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN state = 2 THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as demand_count,
        SUM(CASE WHEN type = 2 THEN 1 ELSE 0 END) as price_req_count,
        SUM(CASE WHEN type = 0 OR (type != 1 AND type != 2) THEN 1 ELSE 0 END) as order_count
    FROM purchases
");
$stats = $statsQuery ? $statsQuery->fetch(PDO::FETCH_ASSOC) : [];
$totalCount = (int)($stats['total_count'] ?? 0);
$pendingCount = (int)($stats['pending_count'] ?? 0);
$approvedCount = (int)($stats['approved_count'] ?? 0);
$completedCount = (int)($stats['completed_count'] ?? 0);
$demandCount = (int)($stats['demand_count'] ?? 0);
$priceReqCount = (int)($stats['price_req_count'] ?? 0);
$orderCount = (int)($stats['order_count'] ?? 0);
$completedRate = $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0;

// Verileri tek seferde JOIN ile performanslı çekme
$query = $ac->query("
    SELECT 
        p.*,
        c.company AS customer_name,
        u_create.username AS creator_username,
        u_create.Unvan AS creator_title,
        u_update.username AS updater_username,
        u_update.Unvan AS updater_title
    FROM purchases p
    LEFT JOIN customers c ON p.companyID = c.id
    LEFT JOIN users u_create ON p.creator = u_create.id
    LEFT JOIN users u_update ON p.updater = u_update.id
    ORDER BY p.id DESC
");
$purchases = $query ? $query->fetchAll(PDO::FETCH_ASSOC) : [];

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
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        padding: 4px !important;
        margin-bottom: 25px;
    }

    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        margin-bottom: 0;
        border-bottom: 1px solid #f1f5f9;
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
        padding: 0 !important;
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
        padding: 10px 14px;
        margin: 0;
        border-top: 1px solid #f1f5f9;
        background: #fafafa;
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
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        width: 100% !important;
        table-layout: fixed !important;
    }
    #purchasesTable thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        padding: 9px 4px !important;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        vertical-align: middle;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #purchasesTable thead th.sorting,
    #purchasesTable thead th.sorting_asc,
    #purchasesTable thead th.sorting_desc {
        padding-left: 4px !important;
        padding-right: 14px !important;
    }
    #purchasesTable thead th:not(.sorting):not(.sorting_asc):not(.sorting_desc) {
        padding-left: 4px !important;
        padding-right: 4px !important;
    }
    #purchasesTable tbody td {
        padding: 8px 4px !important;
        vertical-align: middle;
        font-size: 12px;
        color: #334155;
        border-top: 1px solid #f1f5f9;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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
                    <a href="index.php?p=purchases/manage" class="btn btn-success shadow-sm">
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
                    <span class="badge badge-secondary"><?php echo count($purchases); ?> Kayıt</span>
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
                <table id="purchasesTable" class="table table-hover table-striped w-100 no-filter">
                    <thead>
                        <tr>
                            <th style="width: 3.5%;" class="text-center">#</th>
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
                            <th style="width: 10%;" class="text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sira = 1;
                        foreach ($purchases as $purc): 
                            $pid = (int)$purc['id'];
                            $companyName = !empty($purc['customer_name']) ? $purc['customer_name'] : (!empty($purc['companyID']) ? getCustomerName($purc['companyID']) : '-');
                            $siparisNo = htmlspecialchars($purc['siparisNo'] ?? '', ENT_QUOTES, 'UTF-8');
                            $rawCreateTime = $purc['create_time'] ?? '';
                            $createTimeFormatted = !empty($rawCreateTime) ? date('d.m.Y', strtotime($rawCreateTime)) : '-';
                            $rawDeadline = $purc['deadline'] ?? '';
                            $deadlineFormatted = !empty($rawDeadline) ? date('d.m.Y', strtotime($rawDeadline)) : '-';
                            $altToplam = htmlspecialchars($purc['altToplam'] ?? '0,00', ENT_QUOTES, 'UTF-8');
                            $state = (int)($purc['state'] ?? 0);
                            $type = (int)($purc['type'] ?? 0);
                            $paymentPeriod = htmlspecialchars($purc['payment_period'] ?? '', ENT_QUOTES, 'UTF-8');
                            $invoiceNumber = htmlspecialchars($purc['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8');
                            $rawInvoiceDate = $purc['invoice_date'] ?? '';
                            $invoiceDateFormatted = !empty($rawInvoiceDate) ? date('d.m.Y', strtotime($rawInvoiceDate)) : '-';
                            
                            $creator = !empty($purc['creator_username']) ? $purc['creator_username'] : (!empty($purc['creator']) ? getUserName($purc['creator']) : 'Sistem');
                            $updater = !empty($purc['updater_username']) ? $purc['updater_username'] : (!empty($purc['updater']) ? getUserName($purc['updater']) : '');
                            $updatedDate = $purc['updated_at'] ?? '';

                            // Tip metni ve rozeti
                            if ($type == 1) {
                                $typeLabel = 'TALEP';
                                $typeBadge = '<span class="badge-type-demand">TALEP</span>';
                                $editLink = 'index.php?p=purchase-demand-edit&id=' . $pid;
                                $detailLink = 'index.php?p=purchase-demand-detail&id=' . $pid;
                            } else if ($type == 2) {
                                $typeLabel = 'FİYAT TALEBİ';
                                $typeBadge = '<span class="badge-type-price">FİYAT</span>';
                                $editLink = 'index.php?p=purchases/manage&id=' . $pid;
                                $detailLink = 'index.php?p=purchase-detail&id=' . $pid;
                            } else {
                                $typeLabel = 'SİPARİŞ';
                                $typeBadge = '<span class="badge-type-order">SİPARİŞ</span>';
                                $editLink = 'index.php?p=purchases/manage&id=' . $pid;
                                $detailLink = 'index.php?p=purchase-detail&id=' . $pid;
                            }

                            // Durum rozeti
                            if ($state == 0) {
                                $statusBadge = '<span class="badge badge-soft-warning font-weight-bold">Bekliyor</span>';
                                $statusText = 'Bekliyor';
                            } elseif ($state == 1) {
                                $statusBadge = '<span class="badge badge-soft-info font-weight-bold">Onaylandı</span>';
                                $statusText = 'Onaylandı';
                            } elseif ($state == 2) {
                                $statusBadge = '<span class="badge badge-soft-success font-weight-bold">Tamamlandı</span>';
                                $statusText = 'Tamamlandı';
                            } elseif ($state == 3) {
                                $statusBadge = '<span class="badge badge-soft-danger font-weight-bold">Reddedildi</span>';
                                $statusText = 'Reddedildi';
                            } else {
                                $statusBadge = Helper::getStateBadge($state);
                                $statusText = Helper::getState($state) ?? '';
                            }

                            $creatorTooltip = "Oluşturulma: " . $rawCreateTime;
                            if (!empty($updater)) {
                                $creatorTooltip .= "\nGüncelleyen: " . $updater . " (" . $updatedDate . ")";
                            }
                        ?>
                            <tr data-id="<?php echo $pid; ?>" 
                                data-siparis-no="<?php echo $siparisNo; ?>" 
                                data-type="<?php echo $type; ?>" 
                                data-type-label="<?php echo $typeLabel; ?>"
                                data-state="<?php echo $state; ?>"
                                data-company="<?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>"
                                data-edit-link="<?php echo $editLink; ?>">
                                
                                <td class="text-center">
                                    <span class="row-index-badge"><?php echo $sira++; ?></span>
                                </td>

                                <td>
                                    <span class="font-weight-bold text-dark"><?php echo $siparisNo; ?></span>
                                </td>

                                <td class="company-name-cell" data-tooltip="<?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="font-weight-600 text-dark"><?php echo htmlspecialchars(shorted($companyName, 20), ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>

                                <td class="text-muted text-center" title="<?php echo htmlspecialchars($rawCreateTime, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $createTimeFormatted; ?></td>

                                <td class="text-center"><?php echo $deadlineFormatted; ?></td>

                                <td class="text-right font-weight-bold text-dark">
                                    <?php echo $altToplam . ' ₺'; ?>
                                </td>

                                <td class="text-center">
                                    <?php echo $statusBadge; ?>
                                </td>

                                <td class="text-center"><?php echo $paymentPeriod ?: '-'; ?></td>

                                <td><?php echo $invoiceNumber ?: '-'; ?></td>

                                <td class="text-center"><?php echo $invoiceDateFormatted; ?></td>

                                <td>
                                    <span class="custom-tooltip" data-tooltip="<?php echo htmlspecialchars($creatorTooltip, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($creatorTooltip, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-user-circle text-muted mr-1"></i><?php echo htmlspecialchars(shorted($creator, 12), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <?php echo $typeBadge; ?>
                                </td>

                                <td class="text-center text-nowrap" style="width: 105px; min-width: 105px; white-space: nowrap;">
                                    <div class="action-btn-group">
                                        <a href="<?php echo $detailLink; ?>" target="_blank" class="btn btn-sm btn-outline-primary action-btn" title="Detay / Form Görüntüle" data-tooltip="Detay / Form">
                                            <i class="fa fa-eye"></i>
                                        </a>

                                        <a href="<?php echo $editLink; ?>" class="btn btn-sm btn-outline-info action-btn" title="Düzenle" data-tooltip="Düzenle">
                                            <i class="fa fa-pencil"></i>
                                        </a>

                                        <?php if (permtrue("purchasedelete")) { ?>
                                            <?php if ($state == 2) { ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger action-btn disabled opacity-50" title="Tamamlanmış Kayıt Silinemez" data-tooltip="Tamamlanmış Kayıt Silinemez" disabled>
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            <?php } else { ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger action-btn" title="Sil" data-tooltip="Sil" onclick="deleteRecord('<?php echo $siparisNo; ?> nolu kaydı silmek istediğinize emin misiniz?', <?php echo $pid; ?>, 'purchases')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            <?php } ?>
                                        <?php } ?>

                                        <div class="dropdown d-inline">
                                            <button class="btn btn-sm btn-outline-secondary action-btn" type="button" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Diğer İşlemler" data-tooltip="Diğer">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right shadow border-0" style="border-radius: 8px; z-index: 1050;">
                                                <?php if ($type == 1 && $state == 0) { ?>
                                                    <a href="index.php?p=purchases/manage&talep_id=<?php echo $pid; ?>&demand=true" class="dropdown-item py-2 font-13">
                                                        <i class="fa fa-shopping-cart text-primary mr-2"></i> Sipariş Oluştur
                                                    </a>
                                                <?php } ?>

                                                <a href="index.php?p=purchase-demand-detail&id=<?php echo $pid; ?>" target="_blank" class="dropdown-item py-2 font-13">
                                                    <i class="fa fa-file-text-o text-info mr-2"></i> Talep Formunu Göster
                                                </a>
                                                <a href="index.php?p=purchase-detail&id=<?php echo $pid; ?>" target="_blank" class="dropdown-item py-2 font-13">
                                                    <i class="fa fa-file-text text-success mr-2"></i> Sipariş Formunu Göster
                                                </a>

                                                <?php if ($type == 1) { 
                                                    $toggleStateText = ($state == 0) ? 'Tamamlandı Olarak İşaretle' : 'Bekliyor Olarak İşaretle';
                                                ?>
                                                    <a href="#" class="dropdown-item py-2 font-13 done-demand" data-id="<?php echo $pid; ?>">
                                                        <i class="fa fa-check-square-o text-warning mr-2"></i> <?php echo $toggleStateText; ?>
                                                    </a>
                                                <?php } ?>

                                                <a href="index.php?p=report-send-as-mail&type=purchase&id=<?php echo $pid; ?>" class="dropdown-item py-2 font-13">
                                                    <i class="fa fa-envelope text-secondary mr-2"></i> Mail Gönder
                                                </a>
                                            </div>
                                        </div>
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

<!-- SheetJS / XLSX Kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
$(document).ready(function() {
    var isFilteredWaiting = false;

    // DataTable Başlatma
    var table = $('#purchasesTable').DataTable({
        responsive: false,
        scrollX: false,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tümü"]],
        language: {
            url: "include/js/tr.json",
            search: "",
            searchPlaceholder: "Listede ara..."
        },
        dom: "<'row'<'col-sm-12'tr>>" +
             "<'row align-items-center mt-2 px-2 pb-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
        order: [[1, 'desc']],
        columnDefs: [
            { targets: [0, 11, 12], orderable: false }
        ]
    });

    function filterWaitingDemands() {
        table.column(6).search('Bekliyor').draw();
        $('#showdemand').find('span').text('Tüm Kayıtları Göster');
        $('#showdemand').removeClass('btn-outline-primary').addClass('btn-primary');
        isFilteredWaiting = true;
    }

    function showAllDemands() {
        table.column(6).search('').draw();
        $('#showdemand').find('span').text('Sadece Bekleyenleri Göster');
        $('#showdemand').removeClass('btn-primary').addClass('btn-outline-primary');
        isFilteredWaiting = false;
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

    // Excel Dışa Aktarma (SheetJS / XLSX)
    $('#btnExportExcel').on('click', function() {
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
                    deleteRecord(siparisNo + ' nolu kaydı silmek istediğinize emin misiniz?', pid, 'purchases');
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