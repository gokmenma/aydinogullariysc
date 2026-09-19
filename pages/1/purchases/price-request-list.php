<?php

use App\Helper\Helper;

$pids = @$_GET['id'];

if ((@$_GET["st"] ?? "") == "success-mail") {
    showAlert("success", "Mail başarı ile gönderildi!");
} else if ((@$_GET["st"] ?? "") == "unsuccessful") {
    showAlert("alert", "Mail gönderilirken bir hata oluştu");
}

// Yetki Kapsamı
$where = "";
$whereParams = [];
if (!permtrue('tum_fiyat_taleplerini_gor')) {
    $where = " AND p.creator = ? ";
    $whereParams[] = (int)sesset('id');
}

// KPI İstatistikleri
$statsSql = "
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN p.state = 0 THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN p.state = 1 THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN p.state = 2 THEN 1 ELSE 0 END) as completed_count
    FROM purchases p
    WHERE p.type = 2 AND p.siparisNo LIKE 'FT%' {$where}
";
$statsStmt = $ac->prepare($statsSql);
$statsStmt->execute($whereParams);
$stats = $statsStmt ? $statsStmt->fetch(PDO::FETCH_ASSOC) : [];

$totalCount = (int)($stats['total_count'] ?? 0);
$pendingCount = (int)($stats['pending_count'] ?? 0);
$approvedCount = (int)($stats['approved_count'] ?? 0);
$completedCount = (int)($stats['completed_count'] ?? 0);
$completedRate = $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0;

// Verileri tek seferde JOIN ile çekme
$querySql = "
    SELECT 
        p.*,
        c.company AS customer_name,
        u.username AS creator_username,
        u.Unvan AS creator_title
    FROM purchases p
    LEFT JOIN customers c ON p.companyID = c.id
    LEFT JOIN users u ON p.creator = u.id
    WHERE p.type = 2 AND p.siparisNo LIKE 'FT%' {$where}
    ORDER BY p.id DESC
";
$query = $ac->prepare($querySql);
$query->execute($whereParams);
$priceRequests = $query ? $query->fetchAll(PDO::FETCH_ASSOC) : [];

try {
    $logger = \getLogger("Fiyat Talepleri");
    $logger->info("Fiyat talepleri listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_pricereq_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-pricereq-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* ==========================================
       PREMIUM PRICE REQUESTS LIST THEME
       ========================================== */
    .kpi-pricereq-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    .pricereq-list-wrapper {
        width: 100%;
        padding: 0;
        margin: 0;
    }

    .pricereq-list-page-container {
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
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.28);
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
    .crm-kpi-icon-purple { background: #f3e8ff; color: #7c3aed; }
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
    .pricereq-list-wrapper .table-responsive,
    .pricereq-list-page-container .table-responsive,
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
    #priceRequestTable th.col-company,
    #priceRequestTable td.company-name-cell {
        max-width: 220px !important;
        width: 220px !important;
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
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
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

    /* Table Base Styling */
    #priceRequestTable {
        margin: 0 !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        width: 100% !important;
        table-layout: fixed !important;
    }
    #priceRequestTable thead th {
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
    #priceRequestTable thead th.sorting,
    #priceRequestTable thead th.sorting_asc,
    #priceRequestTable thead th.sorting_desc {
        padding-left: 4px !important;
        padding-right: 14px !important;
    }
    #priceRequestTable thead th:not(.sorting):not(.sorting_asc):not(.sorting_desc) {
        padding-left: 4px !important;
        padding-right: 4px !important;
    }
    #priceRequestTable tbody td {
        padding: 8px 4px !important;
        vertical-align: middle;
        font-size: 12px;
        color: #334155;
        border-top: 1px solid #f1f5f9;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #priceRequestTable tbody tr:hover {
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
        width: 28px !important;
        height: 28px !important;
        min-width: 28px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 5px !important;
        font-size: 12px !important;
        line-height: 1 !important;
        transition: all 0.15s ease !important;
        cursor: pointer !important;
        text-decoration: none !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.12);
    }
    .action-btn i {
        font-size: 12px !important;
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
        color: #7c3aed;
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

<div class="pricereq-list-wrapper">
    <div class="pricereq-list-page-container">

        <!-- Header Section -->
        <div class="page-title-box">
            <div class="page-title-left">
                <div class="page-title-icon">
                    <i class="fa fa-tag"></i>
                </div>
                <div class="page-title-text">
                    <h4>Fiyat Talepleri</h4>
                    <p>Tedarikçi fiyat taleplerini ve teklif süreçlerini yönetin</p>
                </div>
            </div>

            <div class="page-title-actions">
                <a href="index.php?p=purchases/dashboard" class="btn btn-outline-info shadow-sm">
                    <i class="fa fa-dashboard"></i> Dashboard
                </a>
                <a href="index.php?p=purchases" class="btn btn-outline-secondary shadow-sm">
                    <i class="fa fa-shopping-cart"></i> Satın Almalar
                </a>
                <a href="index.php?p=purchases/price-request-manage" class="btn btn-primary shadow-sm" style="background: #7c3aed; border-color: #7c3aed;">
                    <i class="fa fa-plus"></i> Yeni Fiyat Talebi
                </a>
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
                
                <!-- 1. Toplam Talep -->
                <div class="col">
                    <div class="crm-kpi-card">
                        <div class="crm-kpi-header">
                            <div>
                                <span class="crm-kpi-label">Toplam Talep</span>
                                <div class="crm-kpi-value text-purple" style="color: #7c3aed;"><?php echo number_format($totalCount, 0, ',', '.'); ?></div>
                            </div>
                            <div class="crm-kpi-icon-box crm-kpi-icon-purple">
                                <i class="fa fa-tag"></i>
                            </div>
                        </div>
                        <div class="crm-kpi-footer">
                            <span>Kayıtlı Fiyat Talepleri</span>
                            <span class="text-purple font-weight-bold" style="color: #7c3aed;">FT Listesi</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Bekleyen Talepler -->
                <div class="col">
                    <div class="crm-kpi-card">
                        <div class="crm-kpi-header">
                            <div>
                                <span class="crm-kpi-label">Bekleyen Talepler</span>
                                <div class="crm-kpi-value text-warning"><?php echo number_format($pendingCount, 0, ',', '.'); ?></div>
                            </div>
                            <div class="crm-kpi-icon-box crm-kpi-icon-amber">
                                <i class="fa fa-clock-o"></i>
                            </div>
                        </div>
                        <div class="crm-kpi-footer">
                            <span>Fiyat Bekleyenler</span>
                            <span class="badge badge-soft-warning font-weight-bold"><?php echo $totalCount > 0 ? round(($pendingCount / $totalCount) * 100, 1) : 0; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- 3. İşlemdeki / Onaylanan -->
                <div class="col">
                    <div class="crm-kpi-card">
                        <div class="crm-kpi-header">
                            <div>
                                <span class="crm-kpi-label">İşlemdeki / Onaylanan</span>
                                <div class="crm-kpi-value text-indigo" style="color: #4338ca;"><?php echo number_format($approvedCount, 0, ',', '.'); ?></div>
                            </div>
                            <div class="crm-kpi-icon-box crm-kpi-icon-indigo">
                                <i class="fa fa-hourglass-half"></i>
                            </div>
                        </div>
                        <div class="crm-kpi-footer">
                            <span>Değerlendirme Aşamasında</span>
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
                            <span>Sonuçlanan Talepler</span>
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
                    <h5 class="m-0 font-weight-bold">Fiyat Talebi Listesi</h5>
                    <span class="badge badge-secondary"><?php echo count($priceRequests); ?> Kayıt</span>
                </div>

                <div class="dt-header-filter-box">
                    <input type="text" id="priceRequestCustomSearch" class="dt-custom-search-input" placeholder="Taleplerde ara..." autocomplete="off">
                    <button type="button" class="btn-toggle-kpi" id="toggleKpiSummary" title="Özet Kartlarını Gizle/Göster">
                        <i class="fa fa-chevron-up"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="priceRequestTable" class="table table-hover table-striped w-100 no-filter">
                    <thead>
                        <tr>
                            <th style="width: 4%;" class="text-center">#</th>
                            <th style="width: 10%;">Talep No</th>
                            <th style="width: 22%;">Firma Adı</th>
                            <th style="width: 10%;" class="text-center">Kayıt Tarihi</th>
                            <th style="width: 10%;" class="text-center">Termin</th>
                            <th style="width: 11%;" class="text-right">Toplam Fiyat</th>
                            <th style="width: 10%;" class="text-center">Durum</th>
                            <th style="width: 11%;">Oluşturan</th>
                            <th style="width: 12%;" class="text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sira = 1;
                        foreach ($priceRequests as $purc): 
                            $pid = (int)$purc['id'];
                            $companyName = !empty($purc['customer_name']) ? $purc['customer_name'] : (!empty($purc['companyID']) ? getCustomerName($purc['companyID']) : '-');
                            $siparisNo = htmlspecialchars($purc['siparisNo'] ?? '', ENT_QUOTES, 'UTF-8');
                            $rawCreateTime = $purc['create_time'] ?? '';
                            $createTimeFormatted = !empty($rawCreateTime) ? date('d.m.Y', strtotime($rawCreateTime)) : '-';
                            $rawDeadline = $purc['deadline'] ?? '';
                            $deadlineFormatted = !empty($rawDeadline) ? date('d.m.Y', strtotime($rawDeadline)) : '-';
                            $altToplam = number_format((float)($purc['altToplam'] ?? 0), 2, ',', '.') . ' ₺';
                            $state = (int)($purc['state'] ?? 0);
                            $creator = !empty($purc['creator_username']) ? $purc['creator_username'] : (!empty($purc['creator']) ? getUserName($purc['creator']) : 'Sistem');

                            // Durum rozeti
                            if ($state == 0) {
                                $statusBadge = '<span class="badge badge-soft-warning font-weight-bold">Bekliyor</span>';
                            } elseif ($state == 1) {
                                $statusBadge = '<span class="badge badge-soft-info font-weight-bold">Onaylandı</span>';
                            } elseif ($state == 2) {
                                $statusBadge = '<span class="badge badge-soft-success font-weight-bold">Tamamlandı</span>';
                            } elseif ($state == 3) {
                                $statusBadge = '<span class="badge badge-soft-danger font-weight-bold">Reddedildi</span>';
                            } else {
                                $statusBadge = Helper::getStateBadge($state);
                            }
                        ?>
                            <tr data-id="<?php echo $pid; ?>" 
                                data-siparis-no="<?php echo $siparisNo; ?>" 
                                data-company="<?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>">
                                
                                <td class="text-center">
                                    <span class="row-index-badge"><?php echo $sira++; ?></span>
                                </td>

                                <td>
                                    <span class="font-weight-bold text-dark"><?php echo $siparisNo; ?></span>
                                </td>

                                <td class="company-name-cell" data-tooltip="<?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="font-weight-600 text-dark"><?php echo htmlspecialchars(shorted($companyName, 24), ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>

                                <td class="text-muted text-center" title="<?php echo htmlspecialchars($rawCreateTime, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $createTimeFormatted; ?></td>

                                <td class="text-center"><?php echo $deadlineFormatted; ?></td>

                                <td class="text-right font-weight-bold text-dark">
                                    <?php echo $altToplam; ?>
                                </td>

                                <td class="text-center">
                                    <?php echo $statusBadge; ?>
                                </td>

                                <td>
                                    <span title="<?php echo htmlspecialchars($creator, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-user-circle text-muted mr-1"></i><?php echo htmlspecialchars(shorted($creator, 14), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>

                                <td class="text-center text-nowrap" style="width: 105px; min-width: 105px; white-space: nowrap;">
                                    <div class="action-btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary action-btn view-detail" data-id="<?php echo $pid; ?>" title="Detayı Görüntüle" data-tooltip="Görüntüle">
                                            <i class="fa fa-eye"></i>
                                        </button>

                                        <a href="index.php?p=purchases/price-request-manage&id=<?php echo $pid; ?>" class="btn btn-sm btn-outline-info action-btn" title="Düzenle" data-tooltip="Düzenle">
                                            <i class="fa fa-pencil"></i>
                                        </a>

                                        <?php if (permtrue("tum_fiyat_taleplerini_gor") || permtrue("purchasedelete") || ((int)($purc['creator'] ?? 0) === (int)sesset('id'))) { ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger action-btn" title="Sil" data-tooltip="Sil" onclick="deleteRecord('<?php echo $siparisNo; ?> nolu fiyat talebini silmek istediğinize emin misiniz?', <?php echo $pid; ?>, 'purchases')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        <?php } ?>
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

<!-- Görüntüleme Modalı -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 95%;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <h5 class="modal-title font-weight-bold text-dark" style="font-size: 1.1rem;">
                    <i class="fa fa-file-text-o mr-2 text-primary"></i> Fiyat Talebi Detayı
                </h5>
                <button type="button" class="close" data-dismiss="modal" onclick="$('#detailModal').modal('hide')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" id="detailBody" style="background: #fff; min-height: 400px; overflow-x: hidden;">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Yükleniyor...</span>
                    </div>
                    <p class="mt-2 text-muted">Veriler hazırlanıyor...</p>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-3 px-4">
                <div class="ml-auto d-flex gap-2">
                    <button type="button" class="btn btn-outline-info px-4 font-weight-bold" id="btnPrintModal">
                        <i class="fa fa-print mr-1"></i> Yazdır
                    </button>
                    <button type="button" class="btn btn-danger px-4 font-weight-bold shadow-sm" id="btnPdfModal">
                        <i class="fa fa-file-pdf-o mr-1"></i> PDF Olarak İndir
                    </button>
                    <button type="button" class="btn btn-light px-4 font-weight-bold border ml-2" data-dismiss="modal" onclick="$('#detailModal').modal('hide')">Kapat</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Context Menu (Sağ Tık Menüsü) -->
<div id="priceReqContextMenu" class="custom-context-menu">
    <a href="#" class="ctx-item" id="ctxView">
        <i class="fa fa-eye text-primary"></i> Detay Görüntüle
    </a>
    <a href="#" class="ctx-item" id="ctxEdit">
        <i class="fa fa-pencil text-success"></i> Düzenle
    </a>
    <a href="#" class="ctx-item" id="ctxPrint" target="_blank">
        <i class="fa fa-print text-info"></i> Yazdır / Görüntüle
    </a>
    <a href="#" class="ctx-item" id="ctxPdf" target="_blank">
        <i class="fa fa-file-pdf-o text-danger"></i> PDF İndir
    </a>
    <?php if (permtrue('tum_fiyat_taleplerini_gor')) { ?>
        <div class="ctx-divider"></div>
        <a href="#" class="ctx-item text-danger" id="ctxDelete">
            <i class="fa fa-trash"></i> Sil
        </a>
    <?php } ?>
</div>

<!-- SheetJS / XLSX Kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
var activeDetailId = null;

$(document).ready(function() {
    // DataTable Başlatma
    var table = $('#priceRequestTable').DataTable({
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
            { targets: [0, 8], orderable: false }
        ]
    });

    // Özel Arama Kutusu
    $('#priceRequestCustomSearch').on('keyup input', function() {
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
    var kpiKey = 'aydinogullari_kpi_pricereq_collapsed';
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
            $('html').addClass('kpi-pricereq-collapsed-early');
        } else {
            if (animate) {
                $kpiSection.slideDown(200);
            } else {
                $kpiSection.show();
            }
            $toggleBtn.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $toggleBtn.attr('title', 'Özet Kartlarını Gizle');
            $('html').removeClass('kpi-pricereq-collapsed-early');
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

    // Detay Görüntüleme Modalı
    $(document).on('click', '.view-detail', function() {
        var id = $(this).data('id');
        activeDetailId = id;
        $('#detailModal').modal('show');
        $('#detailBody').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Yükleniyor...</p></div>');
        
        $.ajax({
            url: 'pages/1/purchases/price-request-detail-modal.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                $('#detailBody').html(response);
            },
            error: function() {
                $('#detailBody').html('<div class="alert alert-danger m-3">Veriler yüklenirken bir hata oluştu!</div>');
            }
        });
    });

    $('#btnPrintModal').on('click', function() {
        var printContents = document.getElementById('detailBody').innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    });

    $('#btnPdfModal').on('click', function() {
        if (activeDetailId) {
            window.open('pages/1/purchases/price-request-print.php?id=' + activeDetailId + '&pdf=1', '_blank');
        }
    });

    // Excel Dışa Aktarma (SheetJS / XLSX)
    $('#btnExportExcel').on('click', function() {
        var data = [];
        data.push([
            "Sıra",
            "Talep No",
            "Firma Adı",
            "Kayıt Tarihi",
            "Termin Tarihi",
            "Toplam Fiyat",
            "Durum",
            "Oluşturan"
        ]);

        $('#priceRequestTable tbody tr').each(function(idx) {
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
                data.push(cols);
            }
        });

        var ws = XLSX.utils.aoa_to_sheet(data);
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Fiyat Talepleri");
        var filename = "Fiyat_Talepleri_" + new Date().toISOString().slice(0, 10) + ".xlsx";
        XLSX.writeFile(wb, filename);
    });

    // ==========================================
    // SAĞ TIK MENÜSÜ (CONTEXT MENU)
    // ==========================================
    var $contextMenu = $('#priceReqContextMenu');

    $('#priceRequestTable tbody').on('contextmenu', 'tr', function(e) {
        var $row = $(this);
        if ($row.find('td').length <= 1) return;

        e.preventDefault();
        var pid = $row.data('id');
        var siparisNo = $row.data('siparis-no');
        activeDetailId = pid;

        // Aksiyon Linkleri
        $('#ctxView').off('click').on('click', function(ev) {
            ev.preventDefault();
            $contextMenu.hide();
            $('.view-detail[data-id="' + pid + '"]').trigger('click');
        });

        $('#ctxEdit').attr('href', 'index.php?p=purchases/price-request-manage&id=' + pid);
        $('#ctxPrint').attr('href', 'pages/1/purchases/price-request-print.php?id=' + pid);
        $('#ctxPdf').attr('href', 'pages/1/purchases/price-request-print.php?id=' + pid + '&pdf=1');

        // Sil
        $('#ctxDelete').off('click').on('click', function(ev) {
            ev.preventDefault();
            $contextMenu.hide();
            deleteRecord(siparisNo + ' nolu fiyat talebini silmek istediğinize emin misiniz?', pid, 'purchases');
        });

        // Menü Konumlandırma
        var mouseX = e.pageX;
        var mouseY = e.pageY;
        var menuWidth = 200;
        var menuHeight = 200;
        var winWidth = $(window).width();
        var winHeight = $(window).height();

        if (mouseX + menuWidth > winWidth) mouseX = winWidth - menuWidth - 10;
        if (mouseY + menuHeight > winHeight + $(window).scrollTop()) mouseY = mouseY - menuHeight;

        $contextMenu.css({
            left: mouseX + 'px',
            top: mouseY + 'px'
        }).fadeIn(120);
    });

    $(document).on('click', function() {
        $contextMenu.hide();
    });

    $(window).on('scroll resize', function() {
        $contextMenu.hide();
    });
});
</script>
