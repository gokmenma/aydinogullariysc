<?php
use App\Model\ServiceModel;
use App\Helper\Security;

// Yetki kontrolü
if (!permtrue('service_dashboard') && !permtrue('serviceView')) {
    echo '<div class="alert alert-danger m-4"><i class="fa fa-exclamation-triangle"></i> Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    return;
}

// Loglama
if (function_exists('audit_log')) {
    audit_log("view", "service", "Servis Dashboard sayfası görüntülendi", "dashboard", 0);
}

$serviceModel = new ServiceModel();

// Tarih / Dönem Filtresi
$period = $_GET['period'] ?? 'all';
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$activeFilterLabel = "Tüm Zamanlar";

if ($period === 'today') {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');
    $activeFilterLabel = "Bugün (" . date('d.m.Y') . ")";
} elseif ($period === 'this_week') {
    $startDate = date('Y-m-d', strtotime('monday this week'));
    $endDate = date('Y-m-d', strtotime('sunday this week'));
    $activeFilterLabel = "Bu Hafta";
} elseif ($period === 'this_month') {
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
$summary = $serviceModel->getDashboardSummary($startDate, $endDate);
$topCustomers = $serviceModel->getTopCustomers(10, $startDate, $endDate);
$topUsers = $serviceModel->getTopUsers(10, $startDate, $endDate);
$monthlyTrends = $serviceModel->getMonthlyTrends(12);
$statusDist = $serviceModel->getStatusDistribution($startDate, $endDate);
$typeDist = $serviceModel->getServiceTypeDistribution(8, $startDate, $endDate);
$regionDist = $serviceModel->getRegionDistribution(8, $startDate, $endDate);
$paymentDist = $serviceModel->getPaymentTypeDistribution($startDate, $endDate);
$recentServices = $serviceModel->getRecentServicesSummary(10);
$pendingServices = $serviceModel->getPendingUrgentServices(10);

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
?>

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
/* Dashboard Grid & Hizalama Stilleri */
.service-dashboard-container {
    width: 100%;
    padding: 0;
    margin: 0;
}
.service-dashboard-container .row {
    margin-left: -8px !important;
    margin-right: -8px !important;
}
.service-dashboard-container .row > [class*="col-"] {
    padding-left: 8px !important;
    padding-right: 8px !important;
}

/* Servis Dashboard Özel Stilleri */
.service-dash-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0369a1 100%);
    border-radius: 12px;
    padding: 20px 24px;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.18);
    position: relative;
    overflow: hidden;
    width: 100%;
}
.service-dash-hero .service-hero-title {
    color: #ffffff !important;
    font-size: 21px !important;
    font-weight: 700 !important;
    letter-spacing: -0.3px;
    margin-bottom: 3px;
}
.service-dash-hero .service-hero-title i {
    color: #38bdf8 !important;
}
.service-dash-hero p, .service-dash-hero .service-hero-desc {
    color: rgba(255, 255, 255, 0.85) !important;
}
.service-dash-hero::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.18) 0%, rgba(56, 189, 248, 0) 70%);
    pointer-events: none;
}

.service-filter-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    background: #ffffff;
    padding: 7px 12px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    align-items: center;
    width: 100%;
}
.service-filter-pill {
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.service-filter-pill:hover {
    color: #0f172a;
    background: #f1f5f9;
}
.service-filter-pill.active {
    background: #0284c7;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.35);
}

.flatpickr-custom-input {
    background-color: #ffffff !important;
    cursor: pointer;
    font-size: 12.5px;
    width: 105px !important;
    padding: 4px 8px !important;
    height: 30px !important;
}
.flatpickr-custom-input:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
}

/* KPI Kartları */
.service-kpi-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 16px 18px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.service-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.07);
}
.service-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3.5px;
}
.service-kpi-card.kpi-sky::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
.service-kpi-card.kpi-amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.service-kpi-card.kpi-indigo::before { background: linear-gradient(90deg, #6366f1, #818cf8); }
.service-kpi-card.kpi-emerald::before { background: linear-gradient(90deg, #10b981, #34d399); }

.service-kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
}
.kpi-icon-sky { background: rgba(2, 132, 199, 0.12); color: #0284c7; }
.kpi-icon-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.kpi-icon-indigo { background: rgba(99, 102, 241, 0.12); color: #4f46e5; }
.kpi-icon-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }

.service-kpi-label {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 2px;
}
.service-kpi-value {
    font-size: 23px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.service-kpi-sub {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 5px;
}

/* Rank & Avatar Badge */
.rank-badge {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 11px;
}
.rank-badge-1 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.rank-badge-2 { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.rank-badge-3 { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
.rank-badge-default { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

.avatar-sm {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    object-fit: cover;
    background: #e2e8f0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    flex-shrink: 0;
}

/* Chart & Section Cards */
.dash-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    margin-bottom: 0;
    overflow: hidden;
}
.dash-card-header {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
    flex-wrap: wrap;
    gap: 8px;
}
.dash-card-title {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 7px;
}
.dash-card-title i {
    color: #0284c7;
    font-size: 15px;
}
.dash-card-body {
    padding: 16px;
}

/* Table styles inside dashboard (NO SCROLL OPTIMIZED) */
.dash-card .table-responsive {
    overflow-x: hidden !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
}
.dash-table {
    table-layout: fixed !important;
    width: 100% !important;
    margin-bottom: 0 !important;
    border-collapse: collapse !important;
}
.dash-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 8px 6px !important;
    border-bottom: 1px solid #e2e8f0;
    border-top: none;
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dash-table td {
    padding: 7px 6px !important;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
    color: #334155;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dash-table tbody tr:hover {
    background-color: #f8fafc;
}

.table-text-truncate {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

.service-code-badge {
    background: #f1f5f9;
    color: #334155;
    padding: 2px 5px;
    border-radius: 4px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 600;
    font-size: 11px;
    border: 1px solid #e2e8f0;
    text-decoration: none !important;
    display: inline-block;
    line-height: 1.2;
}
.service-code-badge:hover {
    background: #e2e8f0;
    color: #0284c7;
}

.badge-status {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10.5px;
    font-weight: 600;
    line-height: 1.2;
}

/* Dark Mode Uyum */
.dark-mode .service-dash-hero {
    background: linear-gradient(135deg, #020617 0%, #0f172a 50%, #075985 100%);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
}
.dark-mode .service-filter-pills {
    background: #1e293b;
    border-color: #334155;
}
.dark-mode .service-filter-pill {
    color: #94a3b8;
}
.dark-mode .service-filter-pill:hover {
    background: #334155;
    color: #f8fafc;
}
.dark-mode .service-filter-pill.active {
    background: #0284c7;
    color: #ffffff !important;
}
.dark-mode .flatpickr-custom-input {
    background-color: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
.dark-mode .service-kpi-card,
.dark-mode .dash-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.3);
}
.dark-mode .dash-card-header {
    background: #1e293b;
    border-bottom-color: #334155;
}
.dark-mode .dash-card-title {
    color: #f8fafc;
}
.dark-mode .service-kpi-label {
    color: #94a3b8;
}
.dark-mode .service-kpi-value {
    color: #f8fafc;
}
.dark-mode .service-kpi-sub {
    color: #94a3b8;
}
.dark-mode .dash-table th {
    background: #0f172a;
    color: #94a3b8;
    border-bottom-color: #334155;
}
.dark-mode .dash-table td {
    color: #cbd5e1;
    border-top-color: #334155;
}
.dark-mode .dash-table tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.02);
}
.dark-mode .service-code-badge {
    background: #0f172a;
    border-color: #334155;
    color: #38bdf8;
}
.dark-mode .rank-badge-2,
.dark-mode .rank-badge-default {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
}
.dark-mode .avatar-sm {
    background: #334155;
    color: #cbd5e1;
}
</style>

<div class="service-dashboard-container">
    
    <!-- 1. Hero Banner Satırı -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="service-dash-hero">
                <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap: 14px;">
                    <div>
                        <h3 class="service-hero-title">
                            <i class="fa fa-wrench mr-2"></i> Servis & İş Emirleri Dashboard
                        </h3>
                        <p class="service-hero-desc mb-2" style="font-size: 13px;">
                            Saha operasyonları, iş emirleri, servis durumları ve ekip performans analizi
                        </p>
                        <div class="d-flex align-items-center flex-wrap" style="gap: 14px; font-size: 12px; opacity: 0.95;">
                            <span><i class="fa fa-calendar-o mr-1"></i> <?php echo $curDateFormatted; ?></span>
                            <span>•</span>
                            <span><i class="fa fa-filter mr-1"></i> Filtre: <strong><?php echo htmlspecialchars($activeFilterLabel, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                            <span>•</span>
                            <span><i class="fa fa-building-o mr-1"></i> <strong><?php echo number_format($summary['unique_customers'], 0, ',', '.'); ?></strong> Farklı Cari</span>
                            <span>•</span>
                            <span><i class="fa fa-file-text-o mr-1"></i> <strong><?php echo number_format($summary['offer_linked_services'], 0, ',', '.'); ?></strong> Teklif Bağlantılı İş</span>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                        <a href="index.php?p=service/list" class="btn btn-light btn-sm font-weight-bold" style="border-radius: 6px; padding: 6px 12px; font-size: 12.5px;">
                            <i class="fa fa-list mr-1"></i> Servis Listesi
                        </a>
                        <?php if (permtrue('serviceAdd')) { ?>
                            <a href="index.php?p=service/manage" class="btn btn-info btn-sm font-weight-bold" style="border-radius: 6px; padding: 6px 12px; font-size: 12.5px; background: #0284c7; border-color: #0284c7;">
                                <i class="fa fa-plus mr-1"></i> Yeni Servis Oluştur
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Filtreleme Çubuğu Satırı -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="service-filter-pills d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                <div class="d-flex flex-wrap align-items-center" style="gap: 4px;">
                    <span class="mr-2 text-muted font-weight-bold font-11"><i class="fa fa-filter"></i> DÖNEM:</span>
                    <a href="index.php?p=service/dashboard&period=today" class="service-filter-pill <?php echo $period === 'today' ? 'active' : ''; ?>">Bugün</a>
                    <a href="index.php?p=service/dashboard&period=this_week" class="service-filter-pill <?php echo $period === 'this_week' ? 'active' : ''; ?>">Bu Hafta</a>
                    <a href="index.php?p=service/dashboard&period=this_month" class="service-filter-pill <?php echo $period === 'this_month' ? 'active' : ''; ?>">Bu Ay</a>
                    <a href="index.php?p=service/dashboard&period=last_month" class="service-filter-pill <?php echo $period === 'last_month' ? 'active' : ''; ?>">Geçen Ay</a>
                    <a href="index.php?p=service/dashboard&period=last_30_days" class="service-filter-pill <?php echo $period === 'last_30_days' ? 'active' : ''; ?>">Son 30 Gün</a>
                    <a href="index.php?p=service/dashboard&period=last_90_days" class="service-filter-pill <?php echo $period === 'last_90_days' ? 'active' : ''; ?>">Son 90 Gün</a>
                    <a href="index.php?p=service/dashboard&period=this_year" class="service-filter-pill <?php echo $period === 'this_year' ? 'active' : ''; ?>">Bu Yıl</a>
                    <a href="index.php?p=service/dashboard&period=all" class="service-filter-pill <?php echo $period === 'all' ? 'active' : ''; ?>">Tüm Zamanlar</a>
                </div>

                <!-- Özel Tarih Seçici Formu -->
                <form method="GET" action="index.php" class="d-flex align-items-center flex-wrap" style="gap: 4px;">
                    <input type="hidden" name="p" value="service/dashboard">
                    <input type="hidden" name="period" value="custom">
                    
                    <span class="text-muted font-11 font-weight-bold ml-md-2">Özel:</span>
                    <input type="text" name="start_date" id="dash_start_date" class="form-control form-control-sm flatpickr-custom-input" placeholder="Başlangıç" value="<?php echo htmlspecialchars($startDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    <span class="text-muted">-</span>
                    <input type="text" name="end_date" id="dash_end_date" class="form-control form-control-sm flatpickr-custom-input" placeholder="Bitiş" value="<?php echo htmlspecialchars($endDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 6px; padding: 4px 10px; height: 30px;">
                        <i class="fa fa-arrow-right font-11"></i>
                    </button>
                    <?php if ($period === 'custom') { ?>
                        <a href="index.php?p=service/dashboard&period=all" class="btn btn-sm btn-outline-secondary" title="Filtreyi Temizle" style="border-radius: 6px; padding: 4px 8px; height: 30px;">
                            <i class="fa fa-times font-11"></i>
                        </a>
                    <?php } ?>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. 4 Ana KPI Kartı Satırı -->
    <div class="row mb-3">
        <!-- Toplam Servis -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0">
            <div class="service-kpi-card kpi-sky">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="service-kpi-label">Toplam İş Emri</span>
                        <div class="service-kpi-icon kpi-icon-sky">
                            <i class="fa fa-wrench"></i>
                        </div>
                    </div>
                    <div class="service-kpi-value">
                        <?php echo number_format($summary['total_services'], 0, ',', '.'); ?>
                    </div>
                </div>
                <div class="service-kpi-sub d-flex justify-content-between align-items-center">
                    <span>Teklif Bağlantılı: <strong><?php echo $summary['offer_linked_services']; ?></strong></span>
                    <span class="badge badge-primary badge-pill font-11">%<?php echo $summary['offer_linked_rate']; ?></span>
                </div>
            </div>
        </div>

        <!-- Bekleyen Servisler -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0">
            <div class="service-kpi-card kpi-amber">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="service-kpi-label">Bekleyen Servisler</span>
                        <div class="service-kpi-icon kpi-icon-amber">
                            <i class="fa fa-hourglass-half"></i>
                        </div>
                    </div>
                    <div class="service-kpi-value text-warning">
                        <?php echo number_format($summary['pending_services'], 0, ',', '.'); ?>
                    </div>
                </div>
                <div class="service-kpi-sub d-flex justify-content-between align-items-center">
                    <span>İşlem Sırası Bekliyor</span>
                    <span class="badge badge-warning text-dark font-11">%<?php echo $summary['pending_rate']; ?> Oran</span>
                </div>
            </div>
        </div>

        <!-- Çalışılan / Sahada -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0">
            <div class="service-kpi-card kpi-indigo">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="service-kpi-label">Çalışılan / Sahada</span>
                        <div class="service-kpi-icon kpi-icon-indigo">
                            <i class="fa fa-cogs"></i>
                        </div>
                    </div>
                    <div class="service-kpi-value text-info">
                        <?php echo number_format($summary['working_services'], 0, ',', '.'); ?>
                    </div>
                </div>
                <div class="service-kpi-sub d-flex justify-content-between align-items-center">
                    <span>Aktif Süreçte</span>
                    <span class="badge badge-info font-11">%<?php echo $summary['working_rate']; ?> Aktif</span>
                </div>
            </div>
        </div>

        <!-- Tamamlanan / Faturalanan -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0">
            <div class="service-kpi-card kpi-emerald">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="service-kpi-label">Faturalanan & Tamamlanan</span>
                        <div class="service-kpi-icon kpi-icon-emerald">
                            <i class="fa fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="service-kpi-value text-success">
                        <?php echo number_format($summary['invoiced_services'] + $summary['completed_services'], 0, ',', '.'); ?>
                    </div>
                </div>
                <div class="service-kpi-sub d-flex justify-content-between align-items-center">
                    <span><?php echo number_format($summary['invoiced_services'], 0, ',', '.'); ?> Fatura + <?php echo $summary['completed_services']; ?> Tamamlanan</span>
                    <span class="badge badge-success font-11">%<?php echo round((($summary['invoiced_services'] + $summary['completed_services']) / max(1, $summary['total_services'])) * 100, 1); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Grafikler Satırı 1: Aylık Trend & Durum Dağılımı -->
    <div class="row mb-3">
        <!-- Aylık Trend Grafiği -->
        <div class="col-xl-8 col-lg-7 mb-3 mb-lg-0">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">
                        <i class="fa fa-line-chart"></i> Son 12 Ayın Servis Hacmi & Trend Analizi
                    </h5>
                    <span class="badge badge-light text-muted font-11">Aylık Açılan ve Tamamlanan</span>
                </div>
                <div class="dash-card-body">
                    <div id="chartMonthlyTrends" style="min-height: 310px;"></div>
                </div>
            </div>
        </div>

        <!-- Servis Durum Dağılımı Donut -->
        <div class="col-xl-4 col-lg-5">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">
                        <i class="fa fa-pie-chart"></i> Servis Durum Dağılımı
                    </h5>
                    <span class="badge badge-light text-muted font-11"><?php echo htmlspecialchars($activeFilterLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="dash-card-body">
                    <div id="chartStatusDist" style="min-height: 230px;"></div>
                    <div class="mt-2" style="max-height: 100px; overflow-y: auto;">
                        <ul class="list-group list-group-flush font-11">
                            <?php foreach (array_slice($statusDist, 0, 5) as $st) { ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-0">
                                    <span class="text-truncate mr-2">
                                        <i class="fa fa-circle mr-1" style="color: <?php echo !empty($st->status_color) ? htmlspecialchars($st->status_color, ENT_QUOTES, 'UTF-8') : '#0284c7'; ?>"></i>
                                        <?php echo htmlspecialchars($st->status_title, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <span class="font-weight-bold flex-shrink-0"><?php echo number_format($st->count, 0, ',', '.'); ?> (%<?php echo $st->percentage; ?>)</span>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Grafikler Satırı 2: Servis Türleri, Bölge Dağılımı & Ödeme Türü -->
    <div class="row mb-3">
        <!-- Servis Türleri Dağılımı -->
        <div class="col-xl-4 col-lg-6 mb-3 mb-xl-0">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">
                        <i class="fa fa-tags"></i> Servis Konuları / Türleri
                    </h5>
                    <span class="badge badge-light text-muted font-11">En Çok Yapılan İşler</span>
                </div>
                <div class="dash-card-body">
                    <div id="chartTypeDist" style="min-height: 250px;"></div>
                </div>
            </div>
        </div>

        <!-- Bölge Dağılımı -->
        <div class="col-xl-4 col-lg-6 mb-3 mb-xl-0">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">
                        <i class="fa fa-map-marker"></i> Bölgesel Servis Yoğunluğu
                    </h5>
                    <span class="badge badge-light text-muted font-11">Bölge & Sanayi Siteleri</span>
                </div>
                <div class="dash-card-body">
                    <div id="chartRegionDist" style="min-height: 250px;"></div>
                </div>
            </div>
        </div>

        <!-- Ödeme & Tahsilat Türü Dağılımı -->
        <div class="col-xl-4 col-lg-12">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">
                        <i class="fa fa-credit-card"></i> Tahsilat / Ödeme Türü
                    </h5>
                    <span class="badge badge-light text-muted font-11">Peşin / Vadeli</span>
                </div>
                <div class="dash-card-body">
                    <div id="chartPaymentDist" style="min-height: 250px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. Tablolar Satırı 1: En Çok Servis Alan Müşteriler & En Çok Servis Açan Temsilciler -->
    <div class="row mb-3">
        <!-- Top Müşteriler -->
        <div class="col-xl-6 col-lg-12 mb-3 mb-xl-0">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">
                        <i class="fa fa-trophy text-warning"></i> En Çok Servis Alan Müşteriler (Top 10)
                    </h5>
                    <a href="index.php?p=customers/list" class="btn btn-outline-primary btn-sm" style="border-radius: 6px; font-size: 11px; padding: 2px 7px;">
                        Tüm Cariler
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table dash-table mb-0">
                        <colgroup>
                            <col style="width: 28px;">
                            <col style="width: 48%;">
                            <col style="width: 18%;">
                            <col style="width: 14%;">
                            <col style="width: 20%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Firma / Cari</th>
                                <th>Şehir</th>
                                <th class="text-center">İşlem</th>
                                <th class="text-right">Son Servis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topCustomers)) { ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Kayıt bulunamadı.</td>
                                </tr>
                            <?php } else { 
                                foreach ($topCustomers as $idx => $cust) { 
                                    $rank = $idx + 1;
                                    $rankClass = $rank === 1 ? 'rank-badge-1' : ($rank === 2 ? 'rank-badge-2' : ($rank === 3 ? 'rank-badge-3' : 'rank-badge-default'));
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $rank; ?></span>
                                    </td>
                                    <td>
                                        <a href="index.php?p=service/list&cid=<?php echo (int)$cust->customer_id; ?>" class="font-weight-600 text-dark table-text-truncate" title="<?php echo htmlspecialchars($cust->company, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($cust->company, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="text-muted table-text-truncate"><?php echo htmlspecialchars($cust->city ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary font-11 px-1"><?php echo $cust->total_services; ?></span>
                                    </td>
                                    <td class="text-right">
                                        <span class="text-muted font-11"><?php echo !empty($cust->last_service_date) ? date('d.m.Y', strtotime($cust->last_service_date)) : '-'; ?></span>
                                    </td>
                                </tr>
                            <?php }} ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Personeller / Temsilciler -->
        <div class="col-xl-6 col-lg-12">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">
                        <i class="fa fa-users text-info"></i> Servis Açan Personel / Ekip Performansı
                    </h5>
                    <span class="badge badge-light text-muted font-11"><?php echo count($topUsers); ?> Aktif Kullanıcı</span>
                </div>
                <div class="table-responsive">
                    <table class="table dash-table mb-0">
                        <colgroup>
                            <col style="width: 28px;">
                            <col style="width: 36%;">
                            <col style="width: 24%;">
                            <col style="width: 14%;">
                            <col style="width: 26%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Kullanıcı</th>
                                <th>Ünvan</th>
                                <th class="text-center">Açılan</th>
                                <th class="text-right">Başarı Oranı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topUsers)) { ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Kayıt bulunamadı.</td>
                                </tr>
                            <?php } else { 
                                foreach ($topUsers as $idx => $usr) { 
                                    $rank = $idx + 1;
                                    $rankClass = $rank === 1 ? 'rank-badge-1' : ($rank === 2 ? 'rank-badge-2' : ($rank === 3 ? 'rank-badge-3' : 'rank-badge-default'));
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $rank; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center overflow-hidden">
                                            <span class="avatar-sm mr-1">
                                                <?php echo strtoupper(mb_substr($usr->username, 0, 1, 'UTF-8')); ?>
                                            </span>
                                            <span class="font-weight-600 table-text-truncate" title="<?php echo htmlspecialchars($usr->username, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($usr->username, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted table-text-truncate" title="<?php echo htmlspecialchars($usr->user_title ?: 'Personel', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($usr->user_title ?: 'Personel', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary font-11 px-1"><?php echo $usr->total_services; ?></span>
                                    </td>
                                    <td class="text-right">
                                        <div class="d-inline-flex align-items-center justify-content-end" style="width: 100%;">
                                            <div class="progress mr-1" style="width: 45px; height: 5px; background-color: #e2e8f0; border-radius: 3px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $usr->completion_rate; ?>%;"></div>
                                            </div>
                                            <span class="font-11 font-weight-bold">%<?php echo $usr->completion_rate; ?></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php }} ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 7. Tablolar Satırı 2: Öncelikli Bekleyen Servisler & Son Eklenen Servisler -->
    <div class="row mb-3">
        <!-- Öncelikli Bekleyen Servisler (Tablo Görünümü) -->
        <div class="col-xl-6 col-lg-12 mb-3 mb-xl-0">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">
                        <i class="fa fa-clock-o text-warning"></i> Beklemede Olan Kritik Servisler
                    </h5>
                    <span class="badge badge-warning text-dark font-11">Öncelikli Takip</span>
                </div>
                <div class="table-responsive">
                    <table class="table dash-table mb-0">
                        <colgroup>
                            <col style="width: 78px;">
                            <col style="width: 38%;">
                            <col style="width: 24%;">
                            <col style="width: 20%;">
                            <col style="width: 32px;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Servis No</th>
                                <th>Müşteri</th>
                                <th>Konu / Tür</th>
                                <th>Bölge</th>
                                <th class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingServices)) { ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Beklemede servis bulunmamaktadır.</td>
                                </tr>
                            <?php } else { 
                                foreach ($pendingServices as $ps) { 
                                    $encId = Security::encrypt($ps->id);
                            ?>
                                <tr>
                                    <td>
                                        <a href="index.php?p=service-view&id=<?php echo $encId; ?>" class="service-code-badge" title="Servisi Görüntüle">
                                            <?php echo htmlspecialchars($ps->service_number ?: ('SRV-' . $ps->id), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="index.php?p=service/list&cid=<?php echo (int)$ps->pcid; ?>" class="font-weight-600 text-dark table-text-truncate" title="<?php echo htmlspecialchars($ps->company_name, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($ps->company_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="table-text-truncate font-11 text-muted" title="<?php echo htmlspecialchars($ps->service_type_title, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($ps->service_type_title, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="table-text-truncate font-11 text-muted" title="<?php echo htmlspecialchars($ps->region_title, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($ps->region_title, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="index.php?p=service-view&id=<?php echo $encId; ?>" class="btn btn-sm btn-outline-info p-0" title="İncele" style="width: 22px; height: 22px; line-height: 20px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fa fa-eye font-11"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php }} ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Son Eklenen Servisler (Tablo Görünümü) -->
        <div class="col-xl-6 col-lg-12">
            <div class="dash-card h-100">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">
                        <i class="fa fa-history text-primary"></i> Son Eklenen Servis Kayıtları
                    </h5>
                    <a href="index.php?p=service/list" class="btn btn-outline-primary btn-sm" style="border-radius: 6px; font-size: 11px; padding: 2px 7px;">
                        Tümünü Gör
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table dash-table mb-0">
                        <colgroup>
                            <col style="width: 78px;">
                            <col style="width: 36%;">
                            <col style="width: 24%;">
                            <col style="width: 22%;">
                            <col style="width: 32px;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Servis No</th>
                                <th>Müşteri</th>
                                <th>Konu / Tür</th>
                                <th>Durum</th>
                                <th class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentServices)) { ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Kayıt bulunamadı.</td>
                                </tr>
                            <?php } else { 
                                foreach ($recentServices as $rs) {
                                    $encId = Security::encrypt($rs->id);
                            ?>
                                <tr>
                                    <td>
                                        <a href="index.php?p=service-view&id=<?php echo $encId; ?>" class="service-code-badge" title="Görüntüle">
                                            <?php echo htmlspecialchars($rs->service_number ?: ('SRV-' . $rs->id), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="index.php?p=service/list&cid=<?php echo (int)$rs->pcid; ?>" class="font-weight-600 text-dark table-text-truncate" title="<?php echo htmlspecialchars($rs->company_name, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($rs->company_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="table-text-truncate font-11 text-muted" title="<?php echo htmlspecialchars($rs->service_type_title, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($rs->service_type_title, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status table-text-truncate" style="background-color: <?php echo !empty($rs->status_color) ? htmlspecialchars($rs->status_color, ENT_QUOTES, 'UTF-8') . '22' : '#e2e8f0'; ?>; color: <?php echo !empty($rs->status_color) ? htmlspecialchars($rs->status_color, ENT_QUOTES, 'UTF-8') : '#475569'; ?>;" title="<?php echo htmlspecialchars($rs->status_title, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fa fa-circle" style="font-size: 6px;"></i>
                                            <?php echo htmlspecialchars($rs->status_title, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="index.php?p=service-view&id=<?php echo $encId; ?>" class="btn btn-sm btn-outline-info p-0" title="İncele" style="width: 22px; height: 22px; line-height: 20px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fa fa-eye font-11"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php }} ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ApexCharts Scriptleri -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Flatpickr Başlatma
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#dash_start_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            locale: "tr",
            allowInput: true
        });
        flatpickr("#dash_end_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            locale: "tr",
            allowInput: true
        });
    }

    const isDarkMode = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
    const themeMode = isDarkMode ? 'dark' : 'light';
    const textMutedColor = isDarkMode ? '#94a3b8' : '#64748b';
    const gridBorderColor = isDarkMode ? '#334155' : '#f1f5f9';

    // 1. Aylık Servis Hacmi & Trend Grafiği
    const monthlyTrends = <?php echo json_encode($monthlyTrends, JSON_UNESCAPED_UNICODE); ?>;
    const optionsMonthly = {
        series: [
            {
                name: 'Toplam Servis',
                type: 'column',
                data: monthlyTrends.total_services || []
            },
            {
                name: 'Tamamlanan Servis',
                type: 'area',
                data: monthlyTrends.completed_services || []
            },
            {
                name: 'Bekleyen Servis',
                type: 'line',
                data: monthlyTrends.pending_services || []
            }
        ],
        chart: {
            height: 310,
            type: 'line',
            toolbar: { show: false },
            fontFamily: 'inherit',
            background: 'transparent'
        },
        theme: { mode: themeMode },
        stroke: {
            width: [0, 2.5, 2.5],
            curve: 'smooth'
        },
        fill: {
            opacity: [0.85, 0.25, 1],
            gradient: {
                inverseColors: false,
                shade: 'light',
                type: "vertical",
                opacityFrom: 0.85,
                opacityTo: 0.15,
                stops: [0, 100]
            }
        },
        colors: ['#0284c7', '#10b981', '#f59e0b'],
        labels: monthlyTrends.categories || [],
        markers: { size: [0, 4, 4] },
        xaxis: {
            labels: { style: { colors: textMutedColor, fontSize: '11px' } }
        },
        yaxis: {
            title: { text: 'Servis Sayısı', style: { color: textMutedColor, fontSize: '11px' } },
            labels: { style: { colors: textMutedColor } }
        },
        grid: {
            borderColor: gridBorderColor,
            strokeDashArray: 4
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            labels: { colors: textMutedColor }
        },
        tooltip: {
            shared: true,
            intersect: false
        }
    };
    new ApexCharts(document.querySelector("#chartMonthlyTrends"), optionsMonthly).render();

    // 2. Servis Durum Dağılımı Donut
    const statusData = <?php echo json_encode($statusDist, JSON_UNESCAPED_UNICODE); ?>;
    const statusLabels = statusData.map(item => item.status_title);
    const statusSeries = statusData.map(item => parseInt(item.count));
    const statusColors = statusData.map((item, idx) => {
        if (item.status_color && item.status_color.trim() !== '') return item.status_color;
        const defaultPalette = ['#0284c7', '#10b981', '#f59e0b', '#6366f1', '#ef4444', '#06b6d4', '#ec4899', '#8b5cf6'];
        return defaultPalette[idx % defaultPalette.length];
    });

    const optionsStatus = {
        series: statusSeries.length > 0 ? statusSeries : [1],
        labels: statusLabels.length > 0 ? statusLabels : ['Kayıt Yok'],
        chart: {
            type: 'donut',
            height: 230,
            fontFamily: 'inherit',
            background: 'transparent'
        },
        colors: statusColors.length > 0 ? statusColors : ['#cbd5e1'],
        theme: { mode: themeMode },
        stroke: { show: true, width: 2, colors: [isDarkMode ? '#1e293b' : '#ffffff'] },
        legend: { show: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Toplam',
                            color: textMutedColor,
                            fontSize: '12px',
                            fontWeight: 600,
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: false }
    };
    new ApexCharts(document.querySelector("#chartStatusDist"), optionsStatus).render();

    // 3. Servis Konuları / Türleri Donut
    const typeData = <?php echo json_encode($typeDist, JSON_UNESCAPED_UNICODE); ?>;
    const typeLabels = typeData.map(item => item.type_title);
    const typeSeries = typeData.map(item => parseInt(item.count));
    const typeColors = ['#0284c7', '#38bdf8', '#0ea5e9', '#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ec4899'];

    const optionsType = {
        series: typeSeries.length > 0 ? typeSeries : [1],
        labels: typeLabels.length > 0 ? typeLabels : ['Kayıt Yok'],
        chart: {
            type: 'donut',
            height: 250,
            fontFamily: 'inherit',
            background: 'transparent'
        },
        colors: typeColors,
        theme: { mode: themeMode },
        stroke: { show: true, width: 2, colors: [isDarkMode ? '#1e293b' : '#ffffff'] },
        legend: {
            position: 'bottom',
            fontSize: '11px',
            labels: { colors: textMutedColor }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Türler',
                            color: textMutedColor,
                            fontSize: '11.5px'
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: false }
    };
    new ApexCharts(document.querySelector("#chartTypeDist"), optionsType).render();

    // 4. Bölgesel Dağılım Horizontal Bar
    const regionData = <?php echo json_encode($regionDist, JSON_UNESCAPED_UNICODE); ?>;
    const regionLabels = regionData.map(item => item.region_title);
    const regionSeries = regionData.map(item => parseInt(item.count));

    const optionsRegion = {
        series: [{
            name: 'Servis Sayısı',
            data: regionSeries
        }],
        chart: {
            type: 'bar',
            height: 250,
            toolbar: { show: false },
            fontFamily: 'inherit',
            background: 'transparent'
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: true,
                distributed: true,
                barHeight: '60%'
            }
        },
        colors: ['#0284c7', '#0369a1', '#0284c7', '#38bdf8', '#0ea5e9', '#6366f1', '#8b5cf6', '#64748b'],
        theme: { mode: themeMode },
        dataLabels: {
            enabled: true,
            style: { fontSize: '10.5px', colors: ['#fff'] }
        },
        xaxis: {
            categories: regionLabels,
            labels: { style: { colors: textMutedColor, fontSize: '10.5px' } }
        },
        yaxis: {
            labels: { style: { colors: textMutedColor, fontSize: '11px' } }
        },
        grid: {
            borderColor: gridBorderColor,
            strokeDashArray: 4
        },
        legend: { show: false },
        tooltip: {
            y: { formatter: val => val + ' Adet Servis' }
        }
    };
    new ApexCharts(document.querySelector("#chartRegionDist"), optionsRegion).render();

    // 5. Tahsilat Türü Pie
    const payData = <?php echo json_encode($paymentDist, JSON_UNESCAPED_UNICODE); ?>;
    const payLabels = payData.map(item => item.pay_title);
    const paySeries = payData.map(item => parseInt(item.count));
    const payColors = ['#10b981', '#f59e0b', '#6366f1', '#0284c7', '#64748b'];

    const optionsPayment = {
        series: paySeries.length > 0 ? paySeries : [1],
        labels: payLabels.length > 0 ? payLabels : ['Kayıt Yok'],
        chart: {
            type: 'pie',
            height: 250,
            fontFamily: 'inherit',
            background: 'transparent'
        },
        colors: payColors,
        theme: { mode: themeMode },
        stroke: { show: true, width: 2, colors: [isDarkMode ? '#1e293b' : '#ffffff'] },
        legend: {
            position: 'bottom',
            fontSize: '11px',
            labels: { colors: textMutedColor }
        },
        dataLabels: {
            enabled: true,
            formatter: (val, opts) => opts.w.config.series[opts.seriesIndex]
        }
    };
    new ApexCharts(document.querySelector("#chartPaymentDist"), optionsPayment).render();
});
</script>
