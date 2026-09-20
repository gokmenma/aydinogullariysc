<?php
use App\Model\ReportsModel;

// Yetki kontrolü
if (!permtrue('report_dashboard') && !permtrue('reportview')) {
    echo '<div class="alert alert-danger m-4"><i class="fa fa-exclamation-triangle"></i> Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    return;
}

// Loglama
if (function_exists('audit_log')) {
    audit_log("view", "reports", "Raporlar Dashboard sayfası görüntülendi", "dashboard", 0);
}

$reportsModel = new ReportsModel();

// Tarih / Dönem Filtresi
$period = $_GET['period'] ?? 'all';
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$activeFilterLabel = "Tüm Zamanlar";

if ($period === 'this_month') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    $activeFilterLabel = "Bu Ay (" . date('m/Y') . ")";
} elseif ($period === 'last_month') {
    $startDate = date('Y-m-01', strtotime('-1 month'));
    $endDate = date('Y-m-t', strtotime('-1 month'));
    $activeFilterLabel = "Geçen Ay (" . date('m/Y', strtotime('-1 month')) . ")";
} elseif ($period === 'this_year') {
    $startDate = date('Y-01-01');
    $endDate = date('Y-12-31');
    $activeFilterLabel = "Bu Yıl (" . date('Y') . ")";
} elseif ($period === 'last_30_days') {
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate = date('Y-m-d');
    $activeFilterLabel = "Son 30 Gün";
} elseif ($period === 'last_90_days') {
    $startDate = date('Y-m-d', strtotime('-90 days'));
    $endDate = date('Y-m-d');
    $activeFilterLabel = "Son 90 Gün";
} elseif ($period === 'custom' && !empty($startDate) && !empty($endDate)) {
    $activeFilterLabel = date('d.m.Y', strtotime($startDate)) . " - " . date('d.m.Y', strtotime($endDate));
} else {
    $period = 'all';
    $startDate = null;
    $endDate = null;
}

// Modelden Verileri Çek
$summary = $reportsModel->getDashboardSummary($startDate, $endDate);
$typeDistribution = $reportsModel->getReportTypeDistribution($startDate, $endDate);
$monthlyTrends = $reportsModel->getMonthlyReportTrends(12);
$topCustomers = $reportsModel->getTopCustomers(10, $startDate, $endDate);
$topControllers = $reportsModel->getTopControllers(10, $startDate, $endDate);
$recentReports = $reportsModel->getRecentReports(10);
$expiringReports = $reportsModel->getExpiringReports(10);
$reportTypesList = $reportsModel->getReportTypesList();

// Türkçe Gün & Ay
$turkishMonths = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
    7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];
$turkishDays = [
    'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba',
    'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi', 'Sunday' => 'Pazar'
];
$curDayName = $turkishDays[date('l')] ?? date('l');
$curDateFormatted = date('d') . ' ' . ($turkishMonths[(int)date('m')] ?? date('F')) . ' ' . date('Y') . ', ' . $curDayName;

// ApexCharts Serileri İçin Veri Hazırlığı
$chartCategories = array_column($monthlyTrends, 'label');
$chartTotalSeries = array_column($monthlyTrends, 'total_count');
$chartYscSeries = array_column($monthlyTrends, 'ysc_count');
$chartHstSeries = array_column($monthlyTrends, 'hst_count');
$chartOtherSeries = array_column($monthlyTrends, 'other_count');

$typeLabels = [];
$typeCounts = [];
foreach ($typeDistribution as $td) {
    $typeLabels[] = $td->report_name;
    $typeCounts[] = (int)$td->report_count;
}
?>

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
/* Rapor Dashboard Özel Stilleri */
.report-dash-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 26px 28px;
    color: #ffffff !important;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
    position: relative;
    overflow: hidden;
}
.report-dash-hero .report-hero-title {
    color: #ffffff !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    letter-spacing: -0.3px;
    margin-bottom: 4px;
}
.report-dash-hero .report-hero-title i {
    color: #38bdf8 !important;
}
.report-dash-hero p, .report-dash-hero .report-hero-desc {
    color: rgba(255, 255, 255, 0.9) !important;
}
.report-dash-hero::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, rgba(56, 189, 248, 0) 70%);
    pointer-events: none;
}
.report-filter-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    background: #ffffff;
    padding: 8px 12px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    align-items: center;
}
.report-filter-pill {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.report-filter-pill:hover {
    color: #1e293b;
    background: #f1f5f9;
}
.report-filter-pill.active {
    background: #0284c7;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.35);
}
.flatpickr-custom-input {
    background-color: #ffffff !important;
    cursor: pointer;
    font-size: 13px;
    width: 115px !important;
}
.flatpickr-custom-input:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
}

/* KPI Kartları */
.report-kpi-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 22px 20px;
    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.04);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.report-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 20px -4px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}
.report-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}
.report-kpi-card.kpi-sky::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
.report-kpi-card.kpi-rose::before { background: linear-gradient(90deg, #e11d48, #fb7185); }
.report-kpi-card.kpi-indigo::before { background: linear-gradient(90deg, #6366f1, #818cf8); }
.report-kpi-card.kpi-emerald::before { background: linear-gradient(90deg, #10b981, #34d399); }

.report-kpi-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.kpi-icon-sky { background: rgba(2, 132, 199, 0.12); color: #0284c7; }
.kpi-icon-rose { background: rgba(225, 29, 72, 0.12); color: #e11d48; }
.kpi-icon-indigo { background: rgba(99, 102, 241, 0.12); color: #4f46e5; }
.kpi-icon-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }

.report-kpi-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
}
.report-kpi-value {
    font-size: 26px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.report-kpi-sub {
    font-size: 13px;
    color: #64748b;
    margin-top: 6px;
}

/* Rank & Avatar Badge */
.rank-badge {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
}
.rank-badge-1 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.rank-badge-2 { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.rank-badge-3 { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
.rank-badge-default { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

.tech-avatar-badge {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 13px;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
}

.table-modern {
    table-layout: fixed;
    width: 100% !important;
    margin-bottom: 0 !important;
}
.table-modern thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border-bottom: 2px solid #e2e8f0;
    padding: 10px 8px;
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.table-modern tbody td {
    padding: 10px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.table-modern tbody tr:hover td {
    background: #f8fafc;
}
.table-no-scroll {
    overflow-x: hidden !important;
    width: 100%;
}
.cell-ellipsis {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    width: 100%;
}

/* Dark mode uyumu */
.dark-mode .report-kpi-card,
.dark-mode .report-filter-pills,
.dark-mode .card {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
.dark-mode .report-kpi-value {
    color: #f8fafc !important;
}
.dark-mode .table-modern thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-color: #334155 !important;
}
.dark-mode .table-modern tbody td {
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}
.dark-mode .table-modern tbody tr:hover td {
    background: #243044 !important;
}
.dark-mode .report-filter-pill {
    color: #94a3b8;
}
.dark-mode .report-filter-pill:hover {
    color: #ffffff;
    background: #334155;
}
</style>
<link rel="stylesheet" href="vendors/styles/dashboard-unified.css?v=20260920">

<div class="pd-20 unified-dashboard">
    
    <!-- 1. HERO BANNER & HIZLI AKSİYONLAR -->
    <div class="report-dash-hero">
        <div class="row align-items-center">
            <div class="col-lg-7 col-md-12 mb-3 mb-lg-0">
                <div class="dashboard-hero-badges">
                    <span class="dashboard-hero-badge">
                        <i class="fa fa-calendar mr-1"></i> <?php echo $curDateFormatted; ?>
                    </span>
                    <span class="dashboard-hero-badge is-filter">
                        <i class="fa fa-filter mr-1"></i> <?php echo htmlspecialchars($activeFilterLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <h2 class="report-hero-title">
                    <i class="fa fa-bar-chart mr-2"></i> Rapor Yönetimi & Analiz Paneli
                </h2>
                <p class="report-hero-desc">
                    Yangın söndürme tüpü (YSC) kontrolleri, hidrostatik testler, mekanik/algılama denetimleri ve teknisyen performans takibi.
                </p>
            </div>
            <div class="col-lg-5 col-md-12 text-lg-right">
                <div class="dashboard-hero-actions">
                    <?php if (permtrue('reportadd')) : ?>
                        <button type="button" class="dashboard-action-primary" data-toggle="modal" data-target="#reporttypeModal">
                            <i class="fa fa-plus mr-1"></i> Yeni Rapor Oluştur
                        </button>
                    <?php endif; ?>
                    <a href="index.php?p=reports/reports" class="dashboard-action-secondary">
                        <i class="fa fa-list mr-1"></i> Rapor Listesi
                    </a>
                    <a href="index.php?p=reports/filling-list" class="dashboard-action-secondary">
                        <i class="fa fa-tint mr-1"></i> Dolum Listesi
                    </a>
                    <a href="index.php?p=reports/control-list" class="dashboard-action-secondary">
                        <i class="fa fa-check-square-o mr-1"></i> Kontrol Listesi
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DÖNEM & TARİH FİLTRELEME ÇUBUĞU -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4" style="gap: 12px;">
        <div class="report-filter-pills">
            <span class="font-12 font-weight-bold text-muted mr-1"><i class="fa fa-sliders mr-1"></i> Dönem:</span>
            <a href="index.php?p=reports/dashboard&period=all" class="report-filter-pill <?php echo $period === 'all' ? 'active' : ''; ?>">Tümü</a>
            <a href="index.php?p=reports/dashboard&period=this_year" class="report-filter-pill <?php echo $period === 'this_year' ? 'active' : ''; ?>">Bu Yıl (<?php echo date('Y'); ?>)</a>
            <a href="index.php?p=reports/dashboard&period=this_month" class="report-filter-pill <?php echo $period === 'this_month' ? 'active' : ''; ?>">Bu Ay</a>
            <a href="index.php?p=reports/dashboard&period=last_month" class="report-filter-pill <?php echo $period === 'last_month' ? 'active' : ''; ?>">Geçen Ay</a>
            <a href="index.php?p=reports/dashboard&period=last_30_days" class="report-filter-pill <?php echo $period === 'last_30_days' ? 'active' : ''; ?>">Son 30 Gün</a>
            <a href="index.php?p=reports/dashboard&period=last_90_days" class="report-filter-pill <?php echo $period === 'last_90_days' ? 'active' : ''; ?>">Son 90 Gün</a>
        </div>

        <form method="GET" action="index.php" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <input type="hidden" name="p" value="reports/dashboard">
            <input type="hidden" name="period" value="custom">
            <div class="input-group input-group-sm" style="width: auto;">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0"><i class="fa fa-calendar text-info"></i></span>
                </div>
                <input type="text" id="report_start_date" name="start_date" class="form-control form-control-sm flatpickr-custom-input border-left-0" placeholder="Başlangıç" value="<?php echo htmlspecialchars($startDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                <div class="input-group-prepend input-group-append">
                    <span class="input-group-text bg-light">-</span>
                </div>
                <input type="text" id="report_end_date" name="end_date" class="form-control form-control-sm flatpickr-custom-input" placeholder="Bitiş" value="<?php echo htmlspecialchars($endDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                <div class="input-group-append">
                    <button type="submit" class="btn btn-info btn-sm px-3 font-weight-bold" style="background-color: #0284c7; border-color: #0284c7;"><i class="fa fa-search mr-1"></i> Filtrele</button>
                </div>
            </div>
            <?php if ($period !== 'all') : ?>
                <a href="index.php?p=reports/dashboard" class="btn btn-outline-secondary btn-sm" title="Filtreyi Sıfırla"><i class="fa fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 3. KPI KARTLARI (4'LÜ GRID) -->
    <div class="row mb-4">
        <!-- Toplam Rapor -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="report-kpi-card kpi-sky">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="report-kpi-label">Toplam Rapor Sayısı</div>
                        <div class="report-kpi-value text-info" style="color: #0284c7 !important;"><?php echo number_format($summary->total_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="report-kpi-icon kpi-icon-sky">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                </div>
                <div class="report-kpi-sub">
                    <div class="font-weight-bold text-dark">
                        Denetlenen Müşteri: <span class="text-info font-weight-bold"><?php echo number_format($summary->unique_customers, 0, ',', '.'); ?> Firma</span>
                    </div>
                    <div class="font-11 text-muted mt-1">
                        Bu ay <strong><?php echo $summary->this_month->count; ?></strong> yeni rapor düzenlendi
                    </div>
                </div>
            </div>
        </div>

        <!-- YSC Kontrol Raporları -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="report-kpi-card kpi-rose">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="report-kpi-label">YSC Kontrol Raporları</div>
                        <div class="report-kpi-value" style="color: #e11d48;"><?php echo number_format($summary->ysc_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="report-kpi-icon kpi-icon-rose">
                        <i class="fa fa-fire-extinguisher"></i>
                    </div>
                </div>
                <div class="report-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Oran: <span class="font-weight-bold" style="color: #e11d48;">%<?php echo $summary->ysc_rate; ?></span></span>
                        <span class="badge badge-light px-2 py-1 font-11 border">Tüp Kontrol</span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar" role="progressbar" style="background: #e11d48; width: <?php echo min(100, $summary->ysc_rate); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidrostatik Test Raporları -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="report-kpi-card kpi-indigo">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="report-kpi-label">Hidrostatik Test (HST)</div>
                        <div class="report-kpi-value" style="color: #4f46e5;"><?php echo number_format($summary->hst_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="report-kpi-icon kpi-icon-indigo">
                        <i class="fa fa-tachometer"></i>
                    </div>
                </div>
                <div class="report-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Oran: <span class="font-weight-bold" style="color: #4f46e5;">%<?php echo $summary->hst_rate; ?></span></span>
                        <span class="badge badge-light px-2 py-1 font-11 border">Basınç Testi</span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar" role="progressbar" style="background: #4f46e5; width: <?php echo min(100, $summary->hst_rate); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mekanik / Algılama / Otomatik Sistemler -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="report-kpi-card kpi-emerald">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="report-kpi-label">Tesisat & Sistem Raporları</div>
                        <div class="report-kpi-value text-success"><?php echo number_format($summary->other_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="report-kpi-icon kpi-icon-emerald">
                        <i class="fa fa-cogs"></i>
                    </div>
                </div>
                <div class="report-kpi-sub">
                    <div class="font-weight-bold text-dark">
                        Mekanik / Algılama / AAS: <span class="text-success font-weight-bold"><?php echo ($summary->met_count + $summary->yas_count + $summary->oys_count + $summary->aas_count); ?> Adet</span>
                    </div>
                    <div class="font-11 text-muted mt-1">
                        Aktif Kontrolör Sayısı: <strong><?php echo $summary->unique_controllers; ?> Uzman</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. GRAFİKLER (TREND & RAPOR TÜRÜ DAĞILIMI) -->
    <div class="row mb-4">
        <!-- Aylık Rapor Üretim Trendi -->
        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="font-weight-bold mb-1"><i class="fa fa-line-chart text-info mr-2"></i> Aylık Rapor Üretim Trendi (Son 12 Ay)</h5>
                            <p class="text-muted font-13 mb-0">Aylara göre YSC kontrolleri, Hidrostatik testler ve diğer sistem raporlarının hacmi</p>
                        </div>
                        <div>
                            <span class="badge badge-light p-2 font-12 border"><i class="fa fa-info-circle text-info mr-1"></i> İnteraktif Grafiktir</span>
                        </div>
                    </div>
                    <div id="chart-monthly-trends" style="min-height: 330px;"></div>
                </div>
            </div>
        </div>

        <!-- Rapor Türü Dağılımı Donut Grafiği -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-body p-4 d-flex flex-direction-column flex-column justify-content-between">
                    <div>
                        <h5 class="font-weight-bold mb-1"><i class="fa fa-pie-chart text-info mr-2"></i> Rapor Türü Dağılımı</h5>
                        <p class="text-muted font-13 mb-3">Tüm düzenlenen raporların kategori payları</p>
                        <div id="chart-type-donut" style="min-height: 230px;"></div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <?php foreach (array_slice($typeDistribution, 0, 4) as $td) : ?>
                            <div class="d-flex justify-content-between align-items-center mb-1 font-13">
                                <span class="cell-ellipsis text-muted mr-2" title="<?php echo htmlspecialchars($td->report_name, ENT_QUOTES, 'UTF-8'); ?>">
                                    &bull; <?php echo htmlspecialchars($td->report_name, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <span class="font-weight-bold text-dark text-nowrap"><?php echo number_format($td->report_count, 0, ',', '.'); ?> (%<?php echo $td->percentage; ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. DETAYLI ÖZET TABLOLARI: EN ÇOK RAPOR DÜZENLENEN FİRMALAR & TEKNİSYENLER -->
    <div class="row mb-4">
        <!-- En Çok Rapor Düzenlenen Müşteriler -->
        <div class="col-lg-6 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-building text-info mr-1"></i> En Çok Rapor Düzenlenen Firmalar
                        </h5>
                        <p class="text-muted font-12 mb-0">Rapor ve denetim hacmine göre ilk 10 müşteri</p>
                    </div>
                    <a href="index.php?p=reports/reports" class="btn btn-xs btn-outline-info py-1 px-2 font-12" style="border-radius: 6px;">
                        Tümü <i class="fa fa-angle-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;" class="text-center">#</th>
                                    <th style="width: 44%;">Firma Adı</th>
                                    <th style="width: 18%;" class="text-center">YSC / HST</th>
                                    <th style="width: 18%;" class="text-center">Toplam Rapor</th>
                                    <th style="width: 20%;" class="text-right">Son Rapor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topCustomers)) : ?>
                                    <?php foreach ($topCustomers as $index => $c) : 
                                        $rankClass = $index === 0 ? 'rank-badge-1' : ($index === 1 ? 'rank-badge-2' : ($index === 2 ? 'rank-badge-3' : 'rank-badge-default'));
                                    ?>
                                        <tr>
                                            <td class="text-center p-1">
                                                <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $index + 1; ?></span>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis font-weight-bold text-dark font-12" title="<?php echo htmlspecialchars($c->company_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($c->company_name, ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if (!empty($c->deleted_at)) : ?>
                                                        <span class="badge badge-danger font-10 py-0 px-1">Pasif</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="cell-ellipsis font-11 text-muted">
                                                    <?php echo htmlspecialchars($c->city ?: 'Şehir Belirtilmemiş', ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if (!empty($c->sector)) : ?>
                                                        &bull; <?php echo htmlspecialchars($c->sector, ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-center font-12">
                                                <span class="text-danger font-weight-bold"><?php echo $c->ysc_reports; ?></span>
                                                <span class="text-muted"> / </span>
                                                <span class="text-primary font-weight-bold"><?php echo $c->hst_reports; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-light px-2 py-1 font-12 font-weight-bold border"><?php echo $c->total_reports; ?></span>
                                            </td>
                                            <td class="text-right font-11 text-muted">
                                                <?php echo !empty($c->last_report_date) ? date('d.m.Y', strtotime($c->last_report_date)) : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted font-13">Seçilen dönemde rapor kaydı bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- En Çok Rapor Düzenleyen Kontrolörler / Teknisyenler -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-user-circle text-info mr-1"></i> Kontrolör & Teknisyen Performansı
                        </h5>
                        <p class="text-muted font-12 mb-0">Teknik personelin hazırladığı rapor sayısı ve denetim hacmi</p>
                    </div>
                    <span class="badge badge-info text-white px-2 py-1 font-11" style="border-radius: 6px; background-color: #0284c7;">
                        <?php echo count($topControllers); ?> Uzman
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;" class="text-center">#</th>
                                    <th style="width: 44%;">Kontrolör / Uzman</th>
                                    <th style="width: 18%;" class="text-center">YSC / HST</th>
                                    <th style="width: 18%;" class="text-center">Toplam Rapor</th>
                                    <th style="width: 20%;" class="text-right">Son Aktivite</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topControllers)) : ?>
                                    <?php foreach ($topControllers as $index => $u) : 
                                        $rankClass = $index === 0 ? 'rank-badge-1' : ($index === 1 ? 'rank-badge-2' : ($index === 2 ? 'rank-badge-3' : 'rank-badge-default'));
                                        $initials = mb_substr($u->controller_name, 0, 2, 'UTF-8');
                                    ?>
                                        <tr>
                                            <td class="text-center p-1">
                                                <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $index + 1; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 8px;">
                                                    <div class="tech-avatar-badge" style="width: 30px; height: 30px; font-size: 11px; flex-shrink: 0;">
                                                        <?php echo strtoupper($initials); ?>
                                                    </div>
                                                    <div style="min-width: 0; flex: 1;">
                                                        <div class="cell-ellipsis font-weight-bold text-dark font-12" title="<?php echo htmlspecialchars($u->controller_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php echo htmlspecialchars($u->controller_name, ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                        <div class="cell-ellipsis font-11 text-muted" title="<?php echo htmlspecialchars($u->user_title ?: 'Kontrolör', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php echo htmlspecialchars($u->user_title ?: 'Kontrolör', ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center font-12">
                                                <span class="text-danger font-weight-bold"><?php echo $u->ysc_reports; ?></span>
                                                <span class="text-muted"> / </span>
                                                <span class="text-primary font-weight-bold"><?php echo $u->hst_reports; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-light px-2 py-1 font-12 font-weight-bold border" style="color: #0284c7;"><?php echo $u->total_reports; ?></span>
                                            </td>
                                            <td class="text-right font-11 text-muted">
                                                <?php echo !empty($u->last_report_date) ? date('d.m.Y', strtotime($u->last_report_date)) : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted font-13">Seçilen dönemde kontrolör verisi bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. SON OLUŞTURULAN RAPORLAR & YAKLAŞAN GEÇERLİLİKLER -->
    <div class="row">
        <!-- Son Oluşturulan Raporlar -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-history text-muted mr-1"></i> Son Oluşturulan Raporlar
                        </h5>
                        <p class="text-muted font-12 mb-0">Sistemde kaydedilen en son 10 denetim ve kontrol raporu</p>
                    </div>
                    <a href="index.php?p=reports/reports" class="btn btn-xs btn-info py-1 px-3 font-12" style="border-radius: 6px; background-color: #0284c7; border-color: #0284c7;">
                        Tüm Raporları Görüntüle <i class="fa fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 11%;">Rapor No</th>
                                    <th style="width: 25%;">Firma Adı</th>
                                    <th style="width: 20%;">Rapor Türü</th>
                                    <th style="width: 10%;">İş Emri</th>
                                    <th style="width: 11%;">Kontrol Tarihi</th>
                                    <th style="width: 11%;">Geçerlilik</th>
                                    <th style="width: 12%;">Kontrolör</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentReports)) : ?>
                                    <?php foreach ($recentReports as $rr) : 
                                        $viewLink = !empty($rr->page_link) ? "index.php?p=reports/{$rr->page_link}/report-view-{$rr->page_link}&id={$rr->id}" : "#";
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $viewLink; ?>" class="font-weight-bold text-info font-12 cell-ellipsis" title="<?php echo htmlspecialchars($rr->report_number ?: ('#'.$rr->id), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($rr->report_number ?: ('#'.$rr->id), ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis font-weight-bold text-dark font-12" title="<?php echo htmlspecialchars($rr->company_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($rr->company_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <?php if (!empty($rr->city)) : ?>
                                                    <div class="cell-ellipsis font-10 text-muted"><?php echo htmlspecialchars($rr->city, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-light px-2 py-1 font-11 border text-truncate d-inline-block" style="max-width: 100%;">
                                                    <?php echo htmlspecialchars($rr->report_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td class="font-11 text-muted">
                                                <?php echo htmlspecialchars($rr->isemrino ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="font-11 text-dark font-weight-bold">
                                                <?php echo htmlspecialchars($rr->control_date ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="font-11 text-muted">
                                                <?php echo htmlspecialchars($rr->validity_date ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis font-11 text-dark" title="<?php echo htmlspecialchars($rr->controller_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fa fa-user-circle mr-1 text-muted"></i><?php echo htmlspecialchars($rr->controller_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted font-13">Henüz rapor kaydı bulunmamaktadır.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Rapor Türü Seçim Modalı -->
<div class="modal fade" id="reporttypeModal" tabindex="-1" role="dialog" aria-labelledby="reporttypeModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="reporttypeModalLongTitle"><i class="fa fa-plus-circle text-info mr-1"></i> Yeni Rapor Türü Seçiniz</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="font-weight-bold font-13 text-dark">Oluşturulacak Rapor Formatı:</label>
                    <select name="reporttype_select" id="reporttype_select" class="form-control selectpicker" data-style="bg-white border">
                        <?php foreach ($reportTypesList as $type) : 
                            $newpagelink = "reports/" . $type->page_link . "/report-new-" . $type->page_link;
                        ?>
                            <option value="<?php echo $type->id; ?>" data-new="<?php echo $newpagelink; ?>">
                                <?php echo htmlspecialchars($type->reportName, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-dismiss="modal">Kapat</button>
                <button type="button" id="forwardToNewReport" class="btn btn-info btn-sm px-3 font-weight-bold" style="background-color: #0284c7; border-color: #0284c7;">
                    Devam Et <i class="fa fa-arrow-right ml-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ApexCharts & Flatpickr Script Başlatma -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Aylık Rapor Trend Grafiği (Stacked Column / Line)
    var categories = <?php echo json_encode($chartCategories, JSON_UNESCAPED_UNICODE); ?>;
    var yscSeries = <?php echo json_encode($chartYscSeries); ?>;
    var hstSeries = <?php echo json_encode($chartHstSeries); ?>;
    var otherSeries = <?php echo json_encode($chartOtherSeries); ?>;

    var trendOptions = {
        series: [{
            name: 'Yangın Söndürme Tüpü (YSC)',
            type: 'column',
            data: yscSeries
        }, {
            name: 'Hidrostatik Test (HST)',
            type: 'column',
            data: hstSeries
        }, {
            name: 'Tesisat / Algılama / Diğer',
            type: 'column',
            data: otherSeries
        }],
        chart: {
            height: 330,
            type: 'bar',
            stacked: true,
            toolbar: {
                show: false
            },
            fontFamily: 'Geist, sans-serif'
        },
        colors: ['#e11d48', '#4f46e5', '#10b981'],
        plotOptions: {
            bar: {
                columnWidth: '45%',
                borderRadius: 4
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    colors: '#64748b',
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Rapor Sayısı',
                style: { color: '#0284c7' }
            },
            labels: {
                style: { colors: '#64748b' }
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y) {
                    return y + " Rapor";
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            offsetY: -10
        }
    };

    var trendChart = new ApexCharts(document.querySelector("#chart-monthly-trends"), trendOptions);
    trendChart.render();

    // 2. Rapor Türü Dağılım Donut Grafiği
    var typeLabels = <?php echo json_encode($typeLabels, JSON_UNESCAPED_UNICODE); ?>;
    var typeCounts = <?php echo json_encode($typeCounts); ?>;

    var donutOptions = {
        series: typeCounts,
        chart: {
            type: 'donut',
            height: 250,
            fontFamily: 'Geist, sans-serif'
        },
        labels: typeLabels,
        colors: ['#4f46e5', '#e11d48', '#10b981', '#f59e0b', '#06b6d4', '#8b5cf6'],
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Toplam Rapor',
                            fontSize: '13px',
                            fontWeight: 600,
                            color: '#64748b',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            show: false
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    var total = typeCounts.reduce((a, b) => a + b, 0);
                    var pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                    return val + " Rapor (" + pct + "%)";
                }
            }
        }
    };

    var donutChart = new ApexCharts(document.querySelector("#chart-type-donut"), donutOptions);
    donutChart.render();

    // 3. Flatpickr Tarih Seçici Başlatma
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#report_start_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            altInputClass: "form-control form-control-sm flatpickr-custom-input border-left-0",
            locale: "tr",
            allowInput: true
        });
        flatpickr("#report_end_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            altInputClass: "form-control form-control-sm flatpickr-custom-input",
            locale: "tr",
            allowInput: true
        });
    }

    // 4. Modal Yönlendirme
    var forwardBtn = document.getElementById("forwardToNewReport");
    if (forwardBtn) {
        forwardBtn.addEventListener("click", function () {
            var sel = document.getElementById("reporttype_select");
            var selectedOption = sel.options[sel.selectedIndex];
            var link = selectedOption.getAttribute("data-new");
            if (link) {
                window.location.href = "index.php?p=" + link;
            }
        });
    }
});
</script>
