<?php
use App\Model\ReportsModel;

$ris = $_GET["id"] ?? null;
if (($_GET["st"] ?? "") == "success-mail") {
	showAlert("success", "Mail başarı ile gönderildi!");
}

$reportsModel = new ReportsModel();
$summary = $reportsModel->getDashboardSummary();
$totalReportsCount = (int)($summary->total_count ?? 0);
$uniqueCustomers = (int)($summary->unique_customers ?? 0);
$thisMonthReportsCount = (int)($summary->this_month->count ?? 0);
$monthGrowthRate = (float)($summary->month_growth_rate ?? 0);
$yscReportsCount = (int)($summary->ysc_count ?? 0);
$yscRate = (float)($summary->ysc_rate ?? 0);
$hstReportsCount = (int)($summary->hst_count ?? 0);
$otherReportsCount = (int)($summary->other_count ?? 0);
$totalOtherHstCount = $hstReportsCount + $otherReportsCount;
$uniqueControllers = (int)($summary->unique_controllers ?? 0);

$reportTypesList = $reportsModel->getReportTypesList();
$reportTypeMeta = [
    'ysc' => ['icon' => 'fa fa-fire-extinguisher', 'color' => '#ef4444', 'bg' => '#fef2f2', 'code' => 'YSC', 'desc' => 'Yangın Söndürme Tüpleri'],
    'hst' => ['icon' => 'fa fa-flask', 'color' => '#0284c7', 'bg' => '#f0f9ff', 'code' => 'HST', 'desc' => 'Hidrostatik Basınç Testi'],
    'met' => ['icon' => 'fa fa-cogs', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'code' => 'MET', 'desc' => 'Mekanik Tesisat Sistemleri'],
    'yas' => ['icon' => 'fa fa-bell-o', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'code' => 'YAS', 'desc' => 'Yangın Algılama & İhbar'],
    'oys' => ['icon' => 'fa fa-snowflake-o', 'color' => '#06b6d4', 'bg' => '#ecfeff', 'code' => 'OYS', 'desc' => 'Otomatik Söndürme Sistemleri'],
    'aas' => ['icon' => 'fa fa-lightbulb-o', 'color' => '#10b981', 'bg' => '#ecfdf5', 'code' => 'AAS', 'desc' => 'Acil Aydınlatma Sistemleri'],
];
?>

<style>
    /* Premium report list page styles */
    .report-list-wrapper {
        width: 100%;
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

    /* KPI Collapse Animation */
    .kpi-summary-collapse {
        transition: all 0.3s ease;
    }
    .kpi-summary-collapse.is-collapsed {
        display: none !important;
    }
    .kpi-reports-collapsed-early #kpiSummarySection {
        display: none !important;
    }

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

    .form-card .table-responsive,
    .form-card .responsive {
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

    .dt-header-filter-box .dataTables_filter {
        margin: 0 !important;
    }

    .dt-header-filter-box .dataTables_filter label {
        margin: 0 !important;
        display: flex;
        align-items: center;
        position: relative;
    }

    .dt-header-filter-box .dataTables_filter input {
        border-radius: 8px !important;
        height: 36px !important;
        width: 240px !important;
        padding: 6px 34px 6px 36px !important;
        border: 1px solid #cbd5e1 !important;
        font-size: 13px !important;
        background: #fafafa;
        margin-left: 0 !important;
    }

    .dt-header-filter-box .dataTables_filter input:focus {
        background: #ffffff;
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        outline: none;
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

    .responsive {
        overflow-x: hidden;
        overflow-y: visible;
        width: 100%;
        min-height: 280px;
    }

    /* Dark Mode Overrides */
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
        background: #282828 !important;
        border-color: #383838 !important;
    }
    .dark-mode .form-card-header {
        border-bottom: 2px solid #383838 !important;
    }
    .dark-mode .form-card-header h5 {
        color: #60a5fa !important;
    }
    .dark-mode .form-card-header p {
        color: #94a3b8 !important;
    }
    .dark-mode .form-card-header .card-icon {
        background: #1e293b !important;
        color: #60a5fa !important;
    }
    .dark-mode .dt-header-filter-box .dataTables_filter input {
        background: #1e1e1e !important;
        color: #e2e8f0 !important;
        border-color: #383838 !important;
    }
    .dark-mode #toggleKpiSummary {
        background-color: #383838 !important;
        color: #e2e8f0 !important;
        border-color: #4f4f50 !important;
    }
    .dark-mode #toggleKpiSummary:hover {
        background-color: #484848 !important;
    }
    .dark-mode .form-card .dataTables_wrapper .row:last-child {
        background: #232323 !important;
        border-top-color: #383838 !important;
    }
    .dark-mode .data-table .form-control {
        background: #1e1e1e !important;
        color: #e2e8f0 !important;
        border-color: #383838 !important;
    }

    /* Rapor No ve Firma Link Stilleri */
    #reportTable a.report-num-link {
        color: #0284c7;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.15s ease, text-decoration 0.15s ease;
    }
    #reportTable a.report-num-link:hover {
        color: #0369a1;
        text-decoration: underline;
    }
    #reportTable a.report-company-link {
        color: #1e293b;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.15s ease, text-decoration 0.15s ease;
    }
    #reportTable a.report-company-link:hover {
        color: #0284c7;
        text-decoration: underline;
    }
    .dark-mode #reportTable a.report-num-link {
        color: #38bdf8 !important;
    }
    .dark-mode #reportTable a.report-num-link:hover {
        color: #7dd3fc !important;
    }
    .dark-mode #reportTable a.report-company-link {
        color: #f1f5f9 !important;
    }
    .dark-mode #reportTable a.report-company-link:hover {
        color: #38bdf8 !important;
    }

    /* PDF Butonu Stilleri */
    .offer-pdf-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626 !important;
        font-size: 12px;
        line-height: 1;
        transition: all 0.15s ease;
        text-decoration: none !important;
        cursor: pointer;
        flex-shrink: 0;
        padding: 0;
        box-shadow: 0 1px 2px rgba(220, 38, 38, 0.08);
    }
    .offer-pdf-btn:hover {
        background: #dc2626;
        color: #ffffff !important;
        border-color: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(220, 38, 38, 0.28);
    }
    .offer-pdf-btn:active {
        transform: translateY(0);
    }
    .dark-mode .offer-pdf-btn {
        background: rgba(220, 38, 38, 0.15);
        border-color: rgba(220, 38, 38, 0.4);
        color: #f87171 !important;
    }
    .dark-mode .offer-pdf-btn:hover {
        background: #dc2626;
        color: #ffffff !important;
        border-color: #dc2626;
    }

    /* PDF Modal Stilleri */
    #reportPdfModal .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        overflow: hidden;
    }
    #reportPdfModal .modal-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 20px;
    }
    #reportPdfModal .modal-icon-badge.pdf-modal-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: rgba(239, 68, 68, 0.1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
    }
    #reportPdfModal .badge-report-no {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-size: 13px;
    }
    #reportPdfModal .modal-body {
        background: #525659;
        height: 78vh;
        min-height: 520px;
    }
    #reportPdfModal .pdf-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(248, 250, 252, 0.95);
        z-index: 20;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }
    #reportPdfModal .pdf-loading-overlay.d-none {
        display: none !important;
    }
    .dark-mode #reportPdfModal .modal-content {
        background: #1e293b;
        color: #f1f5f9;
    }
    .dark-mode #reportPdfModal .modal-header,
    .dark-mode #reportPdfModal .modal-footer {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark-mode #reportPdfModal .badge-report-no {
        background: #1e3a8a !important;
        color: #93c5fd !important;
        border-color: #1d4ed8 !important;
    }
    .dark-mode #reportPdfModal .modal-title {
        color: #f1f5f9 !important;
    }
    .dark-mode #reportPdfModal .close {
        color: #cbd5e1;
        text-shadow: none;
    }
    .dark-mode #pdfModalLoading {
        background: rgba(15, 23, 42, 0.95) !important;
    }
    .dark-mode #pdfModalLoading .text-dark {
        color: #f8fafc !important;
    }

    /* Report Action Dropdowns */
    .report-action-dropdown {
        min-width: 480px;
        padding: 6px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), 0 4px 10px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
        margin-top: 6px;
        z-index: 1050;
    }
    .report-action-dropdown .dropdown-header {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
        padding: 8px 12px 6px 12px;
        margin-bottom: 4px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
    }
    .report-action-dropdown .report-dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: 8px;
        color: #1e293b;
        text-decoration: none;
        transition: all 0.15s ease;
        margin-bottom: 2px;
    }
    .report-action-dropdown .report-dropdown-item:last-child {
        margin-bottom: 0;
    }
    .report-action-dropdown .report-dropdown-item:hover {
        background-color: #f1f5f9;
        transform: translateX(3px);
    }
    .report-dropdown-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }
    .report-action-dropdown .report-dropdown-item:hover .report-dropdown-icon {
        transform: scale(1.08);
    }
    .report-dropdown-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .report-dropdown-title {
        font-size: 13px;
        font-weight: 600;
        line-height: 1.25;
        color: #1e293b;
        white-space: normal;
    }
    .report-dropdown-sub {
        font-size: 11px;
        color: #64748b;
        line-height: 1.2;
        margin-top: 2px;
    }

    /* Dark Mode Dropdown Overrides */
    .dark-mode .report-action-dropdown {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
    }
    .dark-mode .report-action-dropdown .dropdown-header {
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode .report-action-dropdown .report-dropdown-item {
        color: #f1f5f9 !important;
    }
    .dark-mode .report-action-dropdown .report-dropdown-item:hover {
        background-color: #334155 !important;
    }
    .dark-mode .report-action-dropdown .report-dropdown-title {
        color: #f1f5f9 !important;
    }
    .dark-mode .report-action-dropdown .report-dropdown-sub {
        color: #94a3b8 !important;
    }
</style>

<div class="pd-ltr-20 xs-pd-20-10">
    <div class="report-list-wrapper">
        <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap px-1" style="gap: 12px;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div class="page-title-text">
                    <h4>Rapor Yönetimi</h4>
                    <p>Sistemdeki tüm kontrol, muayene ve bakım raporlarının listesi ve takibi</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <?php if (permtrue("report_dashboard") || permtrue("reportview")) { ?>
                    <a href="index.php?p=reports/dashboard" class="btn btn-outline-primary btn-action-outline" title="Dashboard">
                        <i class="fa fa-dashboard"></i> <span class="d-none d-sm-inline">Dashboard</span>
                    </a>
                <?php } ?>
                <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshReports" title="Tabloyu Yenile">
                    <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
                </button>
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-outline-success btn-action-outline dropdown-toggle" id="dropdownContentView" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="İçerik Listesi">
                        <i class="fa fa-folder-open-o"></i> <span class="d-none d-sm-inline">İçerik Listesi</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right report-action-dropdown shadow border-0" aria-labelledby="dropdownContentView">
                        <div class="dropdown-header">
                            <i class="fa fa-folder-open mr-1"></i> İçerik Listesi Seçiniz
                        </div>
                        <?php foreach ($reportTypesList as $type) : 
                            $meta = $reportTypeMeta[$type->page_link] ?? ['icon' => 'fa fa-folder-o', 'color' => '#059669', 'bg' => '#ecfdf5', 'code' => strtoupper($type->page_link), 'desc' => ''];
                            $contentLink = "index.php?p=reports/" . htmlspecialchars($type->page_link, ENT_QUOTES, 'UTF-8') . "/report-content-" . htmlspecialchars($type->page_link, ENT_QUOTES, 'UTF-8') . "&type=" . (int)$type->id;
                        ?>
                            <a class="dropdown-item report-dropdown-item" href="<?php echo $contentLink; ?>">
                                <div class="report-dropdown-icon" style="background-color: <?php echo $meta['bg']; ?>; color: <?php echo $meta['color']; ?>;">
                                    <i class="<?php echo $meta['icon']; ?>"></i>
                                </div>
                                <div class="report-dropdown-text">
                                    <span class="report-dropdown-title"><?php echo htmlspecialchars($type->reportName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="report-dropdown-sub"><?php echo htmlspecialchars($meta['desc'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-action-primary dropdown-toggle" id="dropdownNewReport" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-plus-circle"></i> <span>Yeni Rapor Oluştur</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right report-action-dropdown shadow border-0" aria-labelledby="dropdownNewReport">
                        <div class="dropdown-header">
                            <i class="fa fa-plus-square mr-1"></i> Rapor Türü Seçiniz
                        </div>
                        <?php foreach ($reportTypesList as $type) : 
                            $meta = $reportTypeMeta[$type->page_link] ?? ['icon' => 'fa fa-file-text-o', 'color' => '#0284c7', 'bg' => '#f0f9ff', 'code' => strtoupper($type->page_link), 'desc' => ''];
                            $newLink = "index.php?p=reports/" . htmlspecialchars($type->page_link, ENT_QUOTES, 'UTF-8') . "/report-new-" . htmlspecialchars($type->page_link, ENT_QUOTES, 'UTF-8') . "&type=" . (int)$type->id;
                        ?>
                            <a class="dropdown-item report-dropdown-item" href="<?php echo $newLink; ?>">
                                <div class="report-dropdown-icon" style="background-color: <?php echo $meta['bg']; ?>; color: <?php echo $meta['color']; ?>;">
                                    <i class="<?php echo $meta['icon']; ?>"></i>
                                </div>
                                <div class="report-dropdown-text">
                                    <span class="report-dropdown-title"><?php echo htmlspecialchars($type->reportName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="report-dropdown-sub"><?php echo htmlspecialchars($meta['desc'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
        <script>
            (function() {
                try {
                    if (localStorage.getItem('aydinogullari_kpi_reports_collapsed') === 'true') {
                        document.documentElement.classList.add('kpi-reports-collapsed-early');
                    }
                } catch(e) {}
            })();
        </script>

        <!-- Özet Bilgiler (CRM KPI Kartları) -->
        <div id="kpiSummarySection" class="row mx-0 mb-3 kpi-summary-collapse">
            <!-- Toplam Rapor -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Rapor</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalReportsCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-file-text-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Farklı Müşteri: <strong class="text-primary"><?php echo number_format($uniqueCustomers, 0, ',', '.'); ?></strong></span>
                        <span class="crm-badge-soft soft-primary">Tüm Kayıtlar</span>
                    </div>
                </div>
            </div>

            <!-- Bu Ay Düzenlenen -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Bu Ay Düzenlenen</span>
                            <div class="crm-kpi-value"><?php echo number_format($thisMonthReportsCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-calendar-check-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Aylık Değişim: <strong class="<?php echo $monthGrowthRate >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($monthGrowthRate >= 0 ? '+' : '') . $monthGrowthRate; ?>%</strong></span>
                        <span class="crm-badge-soft soft-emerald">Bu Ay</span>
                    </div>
                </div>
            </div>

            <!-- YSC Kontrol Raporları -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">YSC Kontrol Raporu</span>
                            <div class="crm-kpi-value"><?php echo number_format($yscReportsCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-rose">
                            <i class="fa fa-fire-extinguisher"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Oran: <strong class="text-danger"><?php echo $yscRate; ?>%</strong></span>
                        <span class="crm-badge-soft soft-rose">Yangın Söndürme</span>
                    </div>
                </div>
            </div>

            <!-- HST & Diğer Sistem Raporları -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">HST & Diğer Muayene</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalOtherHstCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-sky">
                            <i class="fa fa-shield"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Denetmen: <strong class="text-info"><?php echo number_format($uniqueControllers, 0, ',', '.'); ?> Kişi</strong></span>
                        <span class="crm-badge-soft soft-sky">Tesisat / Diğer</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tablo Kartı -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="header-left-inner">
                    <div class="card-icon">
                        <i class="fa fa-table"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            <h5>Rapor Kayıtları</h5>
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn-card-header-add dropdown-toggle" id="dropdownHeaderNewReport" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Yeni Rapor Oluştur">
                                    <i class="fa fa-plus"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-left report-action-dropdown shadow border-0" aria-labelledby="dropdownHeaderNewReport">
                                    <div class="dropdown-header">
                                        <i class="fa fa-plus-square mr-1"></i> Rapor Türü Seçiniz
                                    </div>
                                    <?php foreach ($reportTypesList as $type) : 
                                        $meta = $reportTypeMeta[$type->page_link] ?? ['icon' => 'fa fa-file-text-o', 'color' => '#0284c7', 'bg' => '#f0f9ff', 'code' => strtoupper($type->page_link), 'desc' => ''];
                                        $newLink = "index.php?p=reports/" . htmlspecialchars($type->page_link, ENT_QUOTES, 'UTF-8') . "/report-new-" . htmlspecialchars($type->page_link, ENT_QUOTES, 'UTF-8') . "&type=" . (int)$type->id;
                                    ?>
                                        <a class="dropdown-item report-dropdown-item" href="<?php echo $newLink; ?>">
                                            <div class="report-dropdown-icon" style="background-color: <?php echo $meta['bg']; ?>; color: <?php echo $meta['color']; ?>;">
                                                <i class="<?php echo $meta['icon']; ?>"></i>
                                            </div>
                                            <div class="report-dropdown-text">
                                                <span class="report-dropdown-title"><?php echo htmlspecialchars($type->reportName, ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="report-dropdown-sub"><?php echo htmlspecialchars($meta['desc'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <p>Sistemdeki tüm kayıtlı kontrol ve muayene raporları</p>
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
                <table id="reportTable" class="data-table table-hover table-bordered text-nowrap" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="w-10 text-nowrap">ID</th>
                            <th class="w-10 text-nowrap">Rapor No</th>
                            <th>Firma</th>
                            <th>Rapor Türü</th>
                            <th>İş Emri No</th>
                            <th>Kontrol Tarihi</th>
                            <th>Geçerlilik Tarihi</th>
                            <th>Kayıt Tarihi</th>
                            <th>Kayıt Yapan</th>
                            <th class="text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Rapor PDF Önizleme Modalı -->
<div class="modal fade" id="reportPdfModal" tabindex="-1" role="dialog" aria-labelledby="reportPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1100px; width: 95vw;">
        <div class="modal-content custom-pdf-modal-content">
            <div class="modal-header custom-pdf-modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center" style="gap: 12px; min-width: 0;">
                    <div class="modal-icon-badge pdf-modal-icon">
                        <i class="fa fa-file-pdf-o text-danger"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                            <h5 class="modal-title font-16 weight-700 mb-0" id="reportPdfModalLabel">
                                Rapor Önizleme
                            </h5>
                            <span class="badge-report-no font-13 font-weight-bold" id="pdfModalReportBadge">-</span>
                        </div>
                        <small class="text-muted text-truncate d-block" id="pdfModalCustomerTitle">-</small>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <a id="btnPdfOpenExternal" href="#" target="_blank" class="btn btn-outline-secondary btn-sm" title="Yeni Sekmede Aç" data-toggle="tooltip">
                        <i class="fa fa-external-link"></i> <span class="d-none d-sm-inline ml-1">Yeni Sekme</span>
                    </a>
                    <button type="button" id="btnPdfPrintModal" class="btn btn-outline-primary btn-sm" title="Yazdır" data-toggle="tooltip">
                        <i class="fa fa-print"></i> <span class="d-none d-sm-inline ml-1">Yazdır</span>
                    </button>
                    <button type="button" class="close btn-pdf-modal-close ml-2" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>

            <div class="modal-body p-0 position-relative" style="height: 78vh; min-height: 520px; background: #525659;">
                <!-- Loading State -->
                <div id="pdfModalLoading" class="pdf-loading-overlay text-center d-none">
                    <div class="spinner-border text-danger mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                        <span class="sr-only">Yükleniyor...</span>
                    </div>
                    <div class="text-dark font-14 font-weight-600">Rapor PDF Hazırlanıyor...</div>
                    <small class="text-muted mt-1">Lütfen bekleyiniz, belge yükleniyor.</small>
                </div>

                <!-- PDF Iframe -->
                <iframe id="reportPdfIframe" src="about:blank" style="width: 100%; height: 100%; border: none; display: block;" allowfullscreen></iframe>
            </div>

            <div class="modal-footer custom-pdf-modal-footer d-flex justify-content-between align-items-center px-4 py-2 bg-light">
                <div class="text-muted font-12" id="pdfModalFooterInfo">Kontrol ve Muayene Raporu</div>
                <button type="button" class="btn btn-secondary btn-sm px-4" data-dismiss="modal" data-bs-dismiss="modal">
                    Kapat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="reportdetail" tabindex="-1" role="dialog" aria-labelledby="reportdetailCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportdetailLongTitle">Detay Bilgisi</h5>
                <button type="button" class="closeModal close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row ml-2 mt-4">
                    Kayıt Yapan Personel : <label for="" id="creator"></label>
                </div>
                <div class="row ml-2 mb-4">
                    Kayıt Tarihi : <label for="" id="create_time"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="closeModal btn btn-primary" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>


<style>
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
    font-size: 13.5px;
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
    font-size: 14px;
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
    background-color: rgba(59, 130, 246, 0.1) !important;
}
</style>

<script src="include/js/data-table.js"></script>
<script src="include/js/report.js"></script>
<script>
    $(document).ready(function () {
        if ($("#reportTable").length) {
            $("#reportTable").DataTable({
                processing: true,
                serverSide: true,
                stateSave: true,
                stateLoadParams: function (settings, data) {
                    // Sütun sayısı uyuşmazlığı varsa eski state'i sıfırla
                    if (data && data.columns && data.columns.length !== settings.aoColumns.length) {
                        return false;
                    }
                },
                autoWidth: false,
                responsive: false,
                ajax: {
                    url: "api/reports_datatables.php",
                    type: "GET"
                },
                columns: [
                    { data: 0, className: "text-center", width: "45px" }, // ID
                    { data: 1, className: "text-center", width: "110px" }, // Rapor No
                    { data: 2 }, // Firma
                    { data: 3 }, // Rapor Türü
                    { data: 4, className: "text-center", width: "90px" }, // İş Emri No
                    { data: 5, className: "text-center", width: "100px" }, // Kontrol Tarihi
                    { data: 6, className: "text-center", width: "100px" }, // Geçerlilik Tarihi
                    { data: 7, className: "text-center", width: "105px" }, // Kayıt Tarihi
                    { data: 8, className: "text-center", width: "120px" }, // Kayıt Yapan
                    { data: 9, orderable: false, searchable: false, className: "text-center", width: "110px" } // İşlem
                ],
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    url: "include/js/tr.json",
                    processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Yükleniyor...</span>'
                },
                order: [[0, "desc"]],
                orderCellsTop: true,
                drawCallback: function () {
                    $('[data-toggle="tooltip"]').tooltip({
                        container: 'body'
                    });
                },
                initComplete: function () {
                    var api = this.api();

                    // Arama kutusunu Form Card Header içine taşıma
                    var $filterContainer = $('.form-card-header .dt-header-filter-box');
                    var $searchBox = $('#reportTable_filter');
                    if ($filterContainer.length && $searchBox.length) {
                        $searchBox.detach().appendTo($filterContainer);
                        $searchBox.find('label').css({
                            'margin-bottom': '0',
                            'display': 'flex',
                            'align-items': 'center',
                            'position': 'relative'
                        });
                        $searchBox.find('input').addClass('form-control form-control-sm').attr('placeholder', 'Arayın...').css({
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
        }

        // KPI Summary Section Toggle & LocalStorage
        $('html').removeClass('kpi-reports-collapsed-early');
        var isKpiCollapsed = localStorage.getItem('aydinogullari_kpi_reports_collapsed') === 'true';
        if (isKpiCollapsed) {
            $('#kpiSummarySection').addClass('is-collapsed');
            $('#toggleKpiSummary i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $('#toggleKpiSummary').attr('title', 'Özet Kartlarını Göster');
        } else {
            $('#kpiSummarySection').removeClass('is-collapsed');
            $('#toggleKpiSummary i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $('#toggleKpiSummary').attr('title', 'Özet Kartlarını Gizle');
        }

        $(document).on('click', '#toggleKpiSummary', function(){
            var $kpi = $('#kpiSummarySection');
            var willCollapse = !$kpi.hasClass('is-collapsed');
            
            if (willCollapse) {
                $kpi.addClass('is-collapsed');
                $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $(this).attr('title', 'Özet Kartlarını Göster');
                localStorage.setItem('aydinogullari_kpi_reports_collapsed', 'true');
            } else {
                $kpi.removeClass('is-collapsed');
                $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $(this).attr('title', 'Özet Kartlarını Gizle');
                localStorage.setItem('aydinogullari_kpi_reports_collapsed', 'false');
            }
        });

        $("#btnRefreshReports").on("click", function () {
            if ($.fn.DataTable.isDataTable("#reportTable")) {
                $("#reportTable").DataTable().ajax.reload(null, false);
            }
        });

        $(document).on("click", ".btn-report-detail", function () {
            var id = $(this).data("id");
            $.ajax({
                method: "POST",
                url: "pages/1/ajax.php?type=report-detail",
                dataType: "json",
                data: {
                    id: id
                },
                success: function (data) {
                    $("#reportdetail").modal("show");
                    $("#creator").text(data.creator);
                    $("#create_time").text(data.create_time);
                }
            });
        });

        // ==========================================
        // Rapor PDF Önizleme Modalı İşlemleri
        // ==========================================
        var reportPdfLoadTimer = null;

        function hideReportPdfLoader() {
            if (reportPdfLoadTimer) {
                clearTimeout(reportPdfLoadTimer);
                reportPdfLoadTimer = null;
            }
            $('#reportPdfModal #pdfModalLoading').addClass('d-none');
        }

        function showReportPdfLoader() {
            if (reportPdfLoadTimer) {
                clearTimeout(reportPdfLoadTimer);
                reportPdfLoadTimer = null;
            }
            $('#reportPdfModal #pdfModalLoading').removeClass('d-none');
        }

        function openReportPdfModal(pdfUrl, reportNo, customer, reportType) {
            if (!pdfUrl) return;

            $('#pdfModalReportBadge').text(reportNo || '-');
            var subTitle = (customer ? customer : 'Müşteri Belirtilmemiş') + (reportType ? ' (' + reportType + ')' : '');
            $('#pdfModalCustomerTitle').text(subTitle);
            $('#btnPdfOpenExternal').attr('href', pdfUrl);
            $('#pdfModalFooterInfo').text('Rapor No: ' + (reportNo || '-') + (customer ? ' | ' + customer : '') + (reportType ? ' | ' + reportType : ''));

            showReportPdfLoader();

            var iframeEl = document.getElementById('reportPdfIframe');
            if (iframeEl) {
                iframeEl.onload = function() {
                    hideReportPdfLoader();
                };
            }

            var $iframe = $('#reportPdfIframe');
            $iframe.off('load').on('load', function() {
                hideReportPdfLoader();
            });

            // 400ms sonra her koşulda yükleyiciyi gizle
            reportPdfLoadTimer = setTimeout(function() {
                hideReportPdfLoader();
            }, 400);

            $iframe.attr('src', pdfUrl);
            $('#reportPdfModal').modal('show');
        }

        $(document).on('click', '.btn-report-pdf', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var pdfUrl = $(this).data('url') || $(this).attr('data-url');
            var reportNo = $(this).data('report-no') || $(this).attr('data-report-no') || '';
            var customer = $(this).data('customer') || $(this).attr('data-customer') || '';
            var reportType = $(this).data('report-type') || $(this).attr('data-report-type') || '';

            openReportPdfModal(pdfUrl, reportNo, customer, reportType);
        });

        $(document).on('click', '.btn-context-report-pdf', function(e) {
            e.preventDefault();
            var pdfUrl = $(this).data('url');
            var reportNo = $(this).data('report-no') || '';
            var customer = $(this).data('customer') || '';
            var reportType = $(this).data('report-type') || '';
            openReportPdfModal(pdfUrl, reportNo, customer, reportType);
        });

        $('#reportPdfModal').on('shown.bs.modal', function() {
            setTimeout(function() {
                hideReportPdfLoader();
            }, 250);
        });

        // Modal kapandığında iframe içeriğini temizle
        $('#reportPdfModal').on('hidden.bs.modal', function() {
            hideReportPdfLoader();
            $('#reportPdfIframe').attr('src', 'about:blank');
        });

        // Yazdır butonu
        $('#btnPdfPrintModal').on('click', function() {
            var iframeEl = document.getElementById('reportPdfIframe');
            if (iframeEl && iframeEl.contentWindow) {
                try {
                    iframeEl.contentWindow.focus();
                    iframeEl.contentWindow.print();
                } catch (err) {
                    var url = $('#btnPdfOpenExternal').attr('href');
                    if (url && url !== '#') {
                        window.open(url, '_blank');
                    }
                }
            }
        });

        // Tabloda Sağ Tık (Context Menu) İşlemleri
        $(document).on('contextmenu', '#reportTable tbody tr', function(e) {
            if ($(this).find('td').length <= 1) return;

            e.preventDefault();
            
            var $tr = $(this);
            $('#reportTable tbody tr').removeClass('context-menu-active');
            $tr.addClass('context-menu-active');

            var $pdfBtn = $tr.find('.btn-report-pdf');
            var reportNo = $tr.find('.report-num-link').text().trim() || $pdfBtn.data('report-no') || $tr.find('td:nth-child(2)').text().trim() || 'Rapor İşlemleri';
            var $actionTd = $tr.find('td:last-child');
            
            var menuHtml = '<div class="cm-header"><i class="fa fa-file-text-o mr-1"></i> ' + $('<div>').text(reportNo).html() + '</div>';

            // PDF Önizleme seçeneği
            if ($pdfBtn.length) {
                var pdfUrl = $pdfBtn.data('url') || $pdfBtn.attr('data-url');
                var reportNoVal = $pdfBtn.data('report-no') || $pdfBtn.attr('data-report-no') || '';
                var custVal = $pdfBtn.data('customer') || $pdfBtn.attr('data-customer') || '';
                var repTypeVal = $pdfBtn.data('report-type') || $pdfBtn.attr('data-report-type') || '';
                
                menuHtml += '<button type="button" class="btn-context-report-pdf" data-url="' + $('<div>').text(pdfUrl).html() + '" data-report-no="' + $('<div>').text(reportNoVal).html() + '" data-customer="' + $('<div>').text(custVal).html() + '" data-report-type="' + $('<div>').text(repTypeVal).html() + '"><i class="fa fa-file-pdf-o text-danger mr-2"></i> PDF Önizleme</button>';
            }

            // 1. Düzenle Butonu Varsa
            var $editBtn = $actionTd.find('a[data-tooltip="Düzenle"], a.btn-outline-primary');
            if ($editBtn.length) {
                menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-primary mr-2"></i> Düzenle</a>';
            }

            // 2. Dropdown içindeki elemanlar (Raporu Göster, İmzasız Raporu Göster, Mail gönder, Detay Bilgisi vb.)
            var $dropdownItems = $actionTd.find('.dropdown-menu .dropdown-item');
            if ($dropdownItems.length) {
                $dropdownItems.each(function() {
                    var $item = $(this);
                    if ($item.hasClass('btn-report-pdf')) return; // Zaten yukarıda eklendi
                    var href = $item.attr('href');
                    var isLink = href && href !== '#' && href !== 'javascript:void(0);';
                    var target = $item.attr('target') ? ' target="' + $item.attr('target') + '"' : '';
                    var text = $item.html();
                    var dataId = $item.attr('data-id') ? ' data-id="' + $item.attr('data-id') + '"' : '';
                    var classAttr = $item.attr('class') || '';

                    if (isLink) {
                        menuHtml += '<a href="' + href + '"' + target + dataId + ' class="' + classAttr + '">' + text + '</a>';
                    } else {
                        menuHtml += '<button type="button" class="' + classAttr + '"' + dataId + '>' + text + '</button>';
                    }
                });
            }

            // 3. Sil Butonu Varsa
            var $deleteBtn = $actionTd.find('button.btn-danger, a.btn-danger');
            if ($deleteBtn.length) {
                menuHtml += '<div class="cm-divider"></div>';
                var onClickAttr = $deleteBtn.attr('onclick') || $deleteBtn.attr('onClick') || '';
                menuHtml += '<button type="button" class="cm-danger" onclick="' + $('<div>').text(onClickAttr).html() + '; return false;"><i class="fa fa-trash text-danger mr-2"></i> Sil</button>';
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

        // Menü dışına tıklanınca veya kaydırılınca context menu kapat
        $(document).on('click scroll', function(e) {
            if (!$(e.target).closest('#customContextMenu').length) {
                $('#customContextMenu').hide();
                $('#reportTable tbody tr').removeClass('context-menu-active');
            }
        });

        // Menüdeki seçeneğe basılınca context menu kapat
        $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
            $('#customContextMenu').hide();
            $('#reportTable tbody tr').removeClass('context-menu-active');
        });

        // ESC basılınca kapat
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#customContextMenu').hide();
                $('#reportTable tbody tr').removeClass('context-menu-active');
            }
        });
    });
</script>