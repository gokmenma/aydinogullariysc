<?php
use App\Model\PurchaseModel;

// Yetki kontrolü
if (!permtrue('purchase_dashboard') && !permtrue('purchaseadd')) {
    echo '<div class="alert alert-danger m-4"><i class="fa fa-exclamation-triangle"></i> Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    return;
}

// Loglama
if (function_exists('audit_log')) {
    audit_log("view", "purchases", "Satın Alma Dashboard sayfası görüntülendi", "dashboard", 0);
}

$purchaseModel = new PurchaseModel();

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
$summary = $purchaseModel->getDashboardSummary($startDate, $endDate);
$statusDistribution = $purchaseModel->getStatusDistribution($startDate, $endDate);
$typeDistribution = $purchaseModel->getTypeDistribution($startDate, $endDate);
$monthlyTrends = $purchaseModel->getMonthlyPurchaseTrends(12);
$topSuppliers = $purchaseModel->getTopSuppliers(10, $startDate, $endDate);
$topRequesters = $purchaseModel->getTopRequesters(10, $startDate, $endDate);
$recentPurchases = $purchaseModel->getRecentPurchases(10);

// Para Birimi Formatlayıcılar
if (!function_exists('formatCurrencyTR')) {
    function formatCurrencyTR($amount) {
        return number_format((float)$amount, 2, ',', '.') . ' ₺';
    }
}
if (!function_exists('formatCompactTR')) {
    function formatCompactTR($amount) {
        $num = (float)$amount;
        if ($num >= 1000000000) {
            return number_format($num / 1000000000, 2, ',', '.') . ' Milyar ₺';
        } elseif ($num >= 1000000) {
            return number_format($num / 1000000, 2, ',', '.') . ' Milyon ₺';
        } elseif ($num >= 1000) {
            return number_format($num / 1000, 1, ',', '.') . ' Bin ₺';
        }
        return number_format($num, 2, ',', '.') . ' ₺';
    }
}

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
$chartTotalOrders = array_column($monthlyTrends, 'total_orders');
$chartCompletedOrders = array_column($monthlyTrends, 'completed_orders');
$chartTotalAmounts = array_column($monthlyTrends, 'total_amount');

$statusLabels = [];
$statusCounts = [];
$statusColors = [];
foreach ($statusDistribution as $sd) {
    $statusLabels[] = $sd->label;
    $statusCounts[] = (int)$sd->count;
    $statusColors[] = $sd->color;
}
?>

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
/* Satın Alma Dashboard Özel Stilleri */
.purchase-dash-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 26px 28px;
    color: #ffffff !important;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
    position: relative;
    overflow: hidden;
}
.purchase-dash-hero .purchase-hero-title {
    color: #ffffff !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    letter-spacing: -0.3px;
    margin-bottom: 4px;
}
.purchase-dash-hero .purchase-hero-title i {
    color: #3b82f6 !important;
}
.purchase-dash-hero p, .purchase-dash-hero .purchase-hero-desc {
    color: rgba(255, 255, 255, 0.9) !important;
}
.purchase-dash-hero::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.22) 0%, rgba(59, 130, 246, 0) 70%);
    pointer-events: none;
}
.purchase-filter-pills {
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
.purchase-filter-pill {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.purchase-filter-pill:hover {
    color: #1e293b;
    background: #f1f5f9;
}
.purchase-filter-pill.active {
    background: #2563eb;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
}
.flatpickr-custom-input {
    background-color: #ffffff !important;
    cursor: pointer;
    font-size: 13px;
    width: 115px !important;
}
.flatpickr-custom-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

/* KPI Kartları */
.purchase-kpi-card {
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
.purchase-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 20px -4px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}
.purchase-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}
.purchase-kpi-card.kpi-blue::before { background: linear-gradient(90deg, #2563eb, #60a5fa); }
.purchase-kpi-card.kpi-emerald::before { background: linear-gradient(90deg, #10b981, #34d399); }
.purchase-kpi-card.kpi-amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.purchase-kpi-card.kpi-indigo::before { background: linear-gradient(90deg, #6366f1, #818cf8); }

.purchase-kpi-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.kpi-icon-blue { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
.kpi-icon-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }
.kpi-icon-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.kpi-icon-indigo { background: rgba(99, 102, 241, 0.12); color: #4f46e5; }

.purchase-kpi-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
}
.purchase-kpi-value {
    font-size: 26px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.purchase-kpi-sub {
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

.pur-avatar-badge {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 13px;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
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
.dark-mode .purchase-kpi-card,
.dark-mode .purchase-filter-pills,
.dark-mode .card {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
.dark-mode .purchase-kpi-value {
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
.dark-mode .purchase-filter-pill {
    color: #94a3b8;
}
.dark-mode .purchase-filter-pill:hover {
    color: #ffffff;
    background: #334155;
}
</style>
<link rel="stylesheet" href="vendors/styles/dashboard-unified.css?v=20260920-2">

<div class="pd-20 unified-dashboard">
    
    <!-- 1. HERO BANNER & HIZLI AKSİYONLAR -->
    <div class="purchase-dash-hero">
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
                <h2 class="purchase-hero-title">
                    <i class="fa fa-shopping-cart mr-2"></i> Satın Alma & Tedarik Yönetimi Paneli
                </h2>
                <p class="purchase-hero-desc">
                    Satın alma siparişleri, departman talepleri, tedarikçi harcamaları, onay süreçleri ve bütçe analizleri.
                </p>
            </div>
            <div class="col-lg-5 col-md-12 text-lg-right">
                <div class="dashboard-hero-actions">
                    <?php if (permtrue('purchaseadd')) : ?>
                        <a href="index.php?p=purchases/manage" class="dashboard-action-primary">
                            <i class="fa fa-plus mr-1"></i> Yeni Sipariş
                        </a>
                    <?php endif; ?>
                    <?php if (permtrue('purchase-demand-add')) : ?>
                        <a href="index.php?p=purchase-demand-new" class="dashboard-action-secondary">
                            <i class="fa fa-file-text-o mr-1"></i> Satın Alma Talebi
                        </a>
                    <?php endif; ?>
                    <a href="index.php?p=purchases/price-request-list" class="dashboard-action-secondary">
                        <i class="fa fa-tags mr-1"></i> Fiyat Talepleri
                    </a>
                    <a href="index.php?p=purchases" class="dashboard-action-secondary">
                        <i class="fa fa-list mr-1"></i> Tüm Kayıtlar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DÖNEM & TARİH FİLTRELEME ÇUBUĞU -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4" style="gap: 12px;">
        <div class="purchase-filter-pills">
            <span class="font-12 font-weight-bold text-muted mr-1"><i class="fa fa-sliders mr-1"></i> Dönem:</span>
            <a href="index.php?p=purchases/dashboard&period=all" class="purchase-filter-pill <?php echo $period === 'all' ? 'active' : ''; ?>">Tümü</a>
            <a href="index.php?p=purchases/dashboard&period=this_year" class="purchase-filter-pill <?php echo $period === 'this_year' ? 'active' : ''; ?>">Bu Yıl (<?php echo date('Y'); ?>)</a>
            <a href="index.php?p=purchases/dashboard&period=this_month" class="purchase-filter-pill <?php echo $period === 'this_month' ? 'active' : ''; ?>">Bu Ay</a>
            <a href="index.php?p=purchases/dashboard&period=last_month" class="purchase-filter-pill <?php echo $period === 'last_month' ? 'active' : ''; ?>">Geçen Ay</a>
            <a href="index.php?p=purchases/dashboard&period=last_30_days" class="purchase-filter-pill <?php echo $period === 'last_30_days' ? 'active' : ''; ?>">Son 30 Gün</a>
            <a href="index.php?p=purchases/dashboard&period=last_90_days" class="purchase-filter-pill <?php echo $period === 'last_90_days' ? 'active' : ''; ?>">Son 90 Gün</a>
        </div>

        <form method="GET" action="index.php" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <input type="hidden" name="p" value="purchases/dashboard">
            <input type="hidden" name="period" value="custom">
            <div class="input-group input-group-sm" style="width: auto;">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0"><i class="fa fa-calendar text-primary"></i></span>
                </div>
                <input type="text" id="purchase_start_date" name="start_date" class="form-control form-control-sm flatpickr-custom-input border-left-0" placeholder="Başlangıç" value="<?php echo htmlspecialchars($startDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                <div class="input-group-prepend input-group-append">
                    <span class="input-group-text bg-light">-</span>
                </div>
                <input type="text" id="purchase_end_date" name="end_date" class="form-control form-control-sm flatpickr-custom-input" placeholder="Bitiş" value="<?php echo htmlspecialchars($endDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold"><i class="fa fa-search mr-1"></i> Filtrele</button>
                </div>
            </div>
            <?php if ($period !== 'all') : ?>
                <a href="index.php?p=purchases/dashboard" class="btn btn-outline-secondary btn-sm" title="Filtreyi Sıfırla"><i class="fa fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 3. KPI KARTLARI (4'LÜ GRID) -->
    <div class="row mb-4">
        <!-- Toplam Harcama Hacmi -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="purchase-kpi-card kpi-blue">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="purchase-kpi-label">Toplam Satın Alma Hacmi</div>
                        <div class="purchase-kpi-value text-primary"><?php echo formatCompactTR($summary->total_amount); ?></div>
                    </div>
                    <div class="purchase-kpi-icon kpi-icon-blue">
                        <i class="fa fa-money"></i>
                    </div>
                </div>
                <div class="purchase-kpi-sub">
                    <div class="font-weight-bold text-dark">
                        Toplam İşlem: <span class="text-primary font-weight-bold"><?php echo number_format($summary->total_count, 0, ',', '.'); ?> Adet</span>
                    </div>
                    <div class="font-11 text-muted mt-1">
                        Bu ay <strong><?php echo formatCompactTR($summary->this_month->amount); ?></strong> harcama yapıldı
                    </div>
                </div>
            </div>
        </div>

        <!-- Tamamlanan / Teslim Alınan -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="purchase-kpi-card kpi-emerald">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="purchase-kpi-label">Tamamlanan Alımlar</div>
                        <div class="purchase-kpi-value text-success"><?php echo formatCompactTR($summary->completed_amount); ?></div>
                    </div>
                    <div class="purchase-kpi-icon kpi-icon-emerald">
                        <i class="fa fa-check-circle"></i>
                    </div>
                </div>
                <div class="purchase-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Adet: <span class="text-success font-weight-bold"><?php echo $summary->completed_count; ?> Sipariş</span></span>
                        <span class="badge badge-success px-2 py-1 font-11">%<?php echo $summary->completion_rate; ?> Tamamlandı</span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, $summary->completion_rate); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bekleyen / Onaydaki Siparişler -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="purchase-kpi-card kpi-amber">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="purchase-kpi-label">Açık / Bekleyen Siparişler</div>
                        <div class="purchase-kpi-value text-warning"><?php echo formatCompactTR($summary->pending_amount); ?></div>
                    </div>
                    <div class="purchase-kpi-icon kpi-icon-amber">
                        <i class="fa fa-clock-o"></i>
                    </div>
                </div>
                <div class="purchase-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Açık İşlem: <span class="text-warning font-weight-bold"><?php echo ($summary->pending_count + $summary->approved_count); ?> Adet</span></span>
                        <span class="badge badge-warning px-2 py-1 font-11"><?php echo $summary->pending_count; ?> Bekleyen</span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $summary->total_count > 0 ? min(100, round((($summary->pending_count + $summary->approved_count) / $summary->total_count) * 100, 1)) : 0; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ortalama Sipariş & Tedarikçi -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="purchase-kpi-card kpi-indigo">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="purchase-kpi-label">Ortalama Sipariş Tutarı</div>
                        <div class="purchase-kpi-value text-indigo" style="font-size: 22px; color: #4f46e5;"><?php echo formatCurrencyTR($summary->avg_amount); ?></div>
                    </div>
                    <div class="purchase-kpi-icon kpi-icon-indigo">
                        <i class="fa fa-building-o"></i>
                    </div>
                </div>
                <div class="purchase-kpi-sub">
                    <div class="font-weight-bold text-dark">
                        Aktif Tedarikçi: <span class="text-indigo font-weight-bold" style="color: #4f46e5;"><?php echo $summary->unique_suppliers; ?> Firma</span>
                    </div>
                    <div class="font-11 text-muted mt-1">
                        Talep Sayısı: <strong><?php echo $summary->demand_count; ?></strong> | Fiyat Talebi: <strong><?php echo $summary->price_request_count; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. GRAFİKLER (TREND & DURUM DAĞILIMI) -->
    <div class="row mb-4">
        <!-- Aylık Harcama ve Sipariş Trendi -->
        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="font-weight-bold mb-1"><i class="fa fa-line-chart text-primary mr-2"></i> Aylık Satın Alma Harcama Trendi (Son 12 Ay)</h5>
                            <p class="text-muted font-13 mb-0">Aylara göre toplam harcama hacmi (₺) ve sipariş/talep işlem adetleri</p>
                        </div>
                        <div>
                            <span class="badge badge-light p-2 font-12 border"><i class="fa fa-info-circle text-info mr-1"></i> İnteraktif Grafiktir</span>
                        </div>
                    </div>
                    <div id="chart-monthly-trends" style="min-height: 330px;"></div>
                </div>
            </div>
        </div>

        <!-- Durum Dağılımı Donut Grafiği -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-body p-4 d-flex flex-direction-column flex-column justify-content-between">
                    <div>
                        <h5 class="font-weight-bold mb-1"><i class="fa fa-pie-chart text-warning mr-2"></i> Sipariş Durum Dağılımı</h5>
                        <p class="text-muted font-13 mb-3">Satın alma işlemlerinin onay ve tamamlanma durumları</p>
                        <div id="chart-status-donut" style="min-height: 230px;"></div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <?php foreach ($statusDistribution as $sd) : ?>
                            <div class="d-flex justify-content-between align-items-center mb-1 font-13">
                                <div class="d-flex align-items-center">
                                    <span style="width: 10px; height: 10px; border-radius: 3px; background: <?php echo $sd->color; ?>; display: inline-block; margin-right: 8px;"></span>
                                    <span class="text-muted"><?php echo htmlspecialchars($sd->label, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <span class="font-weight-bold text-dark"><?php echo number_format($sd->count, 0, ',', '.'); ?> Adet</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. DETAYLI ÖZET TABLOLARI: EN ÇOK ALIM YAPILAN TEDARİKÇİLER & TALEP AÇAN PERSONELLER -->
    <div class="row mb-4">
        <!-- En Çok Satın Alma Yapılan Tedarikçiler -->
        <div class="col-lg-6 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-building text-primary mr-1"></i> En Çok Alım Yapılan Tedarikçiler
                        </h5>
                        <p class="text-muted font-12 mb-0">Harcama hacmi ve sipariş sayısına göre ilk 10 tedarikçi</p>
                    </div>
                    <a href="index.php?p=purchases" class="btn btn-xs btn-outline-primary py-1 px-2 font-12" style="border-radius: 6px;">
                        Tümü <i class="fa fa-angle-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;" class="text-center">#</th>
                                    <th style="width: 44%;">Tedarikçi Firma</th>
                                    <th style="width: 18%;" class="text-center">Sipariş</th>
                                    <th style="width: 24%;" class="text-right">Toplam Harcama</th>
                                    <th style="width: 14%;" class="text-right">Son Alım</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topSuppliers)) : ?>
                                    <?php foreach ($topSuppliers as $index => $s) : 
                                        $rankClass = $index === 0 ? 'rank-badge-1' : ($index === 1 ? 'rank-badge-2' : ($index === 2 ? 'rank-badge-3' : 'rank-badge-default'));
                                    ?>
                                        <tr>
                                            <td class="text-center p-1">
                                                <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $index + 1; ?></span>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis font-weight-bold text-dark font-12" title="<?php echo htmlspecialchars($s->company_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($s->company_name, ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if (!empty($s->deleted_at)) : ?>
                                                        <span class="badge badge-danger font-10 py-0 px-1">Pasif</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="cell-ellipsis font-11 text-muted">
                                                    <?php echo htmlspecialchars($s->city ?: 'Şehir Belirtilmemiş', ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="font-weight-bold text-dark font-12"><?php echo $s->total_orders; ?></span>
                                                <span class="font-11 text-muted"> / <span class="text-success font-weight-bold"><?php echo $s->completed_orders; ?></span></span>
                                            </td>
                                            <td class="text-right font-weight-bold text-primary font-12">
                                                <?php echo formatCurrencyTR($s->total_amount); ?>
                                            </td>
                                            <td class="text-right font-11 text-muted">
                                                <?php echo !empty($s->last_order_date) ? date('d.m.Y', strtotime($s->last_order_date)) : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted font-13">Seçilen dönemde tedarikçi alım kaydı bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- En Çok Talep / Sipariş Açan Personeller -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-users text-indigo mr-1"></i> Talep & Sipariş Oluşturan Personeller
                        </h5>
                        <p class="text-muted font-12 mb-0">Satın alma talebi ve sipariş açan personel istatistikleri</p>
                    </div>
                    <span class="badge badge-indigo text-white px-2 py-1 font-11" style="border-radius: 6px; background: #6366f1;">
                        <?php echo count($topRequesters); ?> Personel
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;" class="text-center">#</th>
                                    <th style="width: 44%;">Personel</th>
                                    <th style="width: 18%;" class="text-center">İşlem / Tamamlanan</th>
                                    <th style="width: 24%;" class="text-right">Toplam Hacim</th>
                                    <th style="width: 14%;" class="text-right">Son Aktivite</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topRequesters)) : ?>
                                    <?php foreach ($topRequesters as $index => $u) : 
                                        $rankClass = $index === 0 ? 'rank-badge-1' : ($index === 1 ? 'rank-badge-2' : ($index === 2 ? 'rank-badge-3' : 'rank-badge-default'));
                                        $initials = mb_substr($u->user_name, 0, 2, 'UTF-8');
                                    ?>
                                        <tr>
                                            <td class="text-center p-1">
                                                <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $index + 1; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 8px;">
                                                    <div class="pur-avatar-badge" style="width: 30px; height: 30px; font-size: 11px; flex-shrink: 0;">
                                                        <?php echo strtoupper($initials); ?>
                                                    </div>
                                                    <div style="min-width: 0; flex: 1;">
                                                        <div class="cell-ellipsis font-weight-bold text-dark font-12" title="<?php echo htmlspecialchars($u->user_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php echo htmlspecialchars($u->user_name, ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                        <div class="cell-ellipsis font-11 text-muted" title="<?php echo htmlspecialchars($u->user_title ?: 'Personel', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php echo htmlspecialchars($u->user_title ?: 'Personel', ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="font-weight-bold text-dark font-12"><?php echo $u->total_orders; ?></span>
                                                <span class="font-11 text-muted"> / <span class="text-success font-weight-bold"><?php echo $u->completed_orders; ?></span></span>
                                            </td>
                                            <td class="text-right font-weight-bold font-12" style="color: #4f46e5;">
                                                <?php echo formatCurrencyTR($u->total_amount); ?>
                                            </td>
                                            <td class="text-right font-11 text-muted">
                                                <?php echo !empty($u->last_order_date) ? date('d.m.Y', strtotime($u->last_order_date)) : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted font-13">Seçilen dönemde personel işlem kaydı bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. SON OLUŞTURULAN SATIN ALMA İŞLEMLERİ -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-history text-muted mr-1"></i> Son Satın Alma & Talep İşlemleri
                        </h5>
                        <p class="text-muted font-12 mb-0">Sistemde kaydedilen en son 10 satın alma, talep ve fiyat teklifi kaydı</p>
                    </div>
                    <a href="index.php?p=purchases" class="btn btn-xs btn-primary py-1 px-3 font-12" style="border-radius: 6px;">
                        Tüm Satın Almaları Görüntüle <i class="fa fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Sipariş No</th>
                                    <th style="width: 25%;">Tedarikçi Firma</th>
                                    <th style="width: 12%;" class="text-center">İşlem Tipi</th>
                                    <th style="width: 14%;">Oluşturan</th>
                                    <th style="width: 10%;">Tarih</th>
                                    <th style="width: 13%;" class="text-right">Tutar (TL)</th>
                                    <th style="width: 10%;" class="text-center">Durum</th>
                                    <th style="width: 60px;" class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentPurchases)) : ?>
                                    <?php foreach ($recentPurchases as $rp) : 
                                        $typeLabel = 'Sipariş';
                                        $typeBadge = 'badge-success';
                                        $editLink = 'index.php?p=purchases/manage&id=' . $rp->id;

                                        if ((int)$rp->type === 1) {
                                            $typeLabel = 'Talep';
                                            $typeBadge = 'badge-primary';
                                            $editLink = 'index.php?p=purchase-demand-edit&id=' . $rp->id;
                                        } elseif ((int)$rp->type === 2) {
                                            $typeLabel = 'Fiyat Talebi';
                                            $typeBadge = 'badge-warning';
                                            $editLink = 'index.php?p=purchases/manage&id=' . $rp->id;
                                        }

                                        $st = (int)($rp->state ?? 0);
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $editLink; ?>" class="font-weight-bold text-primary font-12 cell-ellipsis" title="<?php echo htmlspecialchars($rp->siparisNo ?: ('#'.$rp->id), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($rp->siparisNo ?: ('#'.$rp->id), ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis font-weight-bold text-dark font-12" title="<?php echo htmlspecialchars($rp->company_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($rp->company_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <?php if (!empty($rp->city)) : ?>
                                                    <div class="cell-ellipsis font-10 text-muted"><?php echo htmlspecialchars($rp->city, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $typeBadge; ?> px-2 py-1 font-10" style="border-radius: 4px;">
                                                    <?php echo $typeLabel; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis font-11 text-dark" title="<?php echo htmlspecialchars($rp->creator_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fa fa-user mr-1 text-muted"></i><?php echo htmlspecialchars($rp->creator_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td class="font-11 text-muted">
                                                <?php echo !empty($rp->create_time) ? date('d.m.Y', strtotime($rp->create_time)) : '-'; ?>
                                            </td>
                                            <td class="text-right font-weight-bold text-dark font-12">
                                                <?php echo formatCurrencyTR($rp->parsed_amount); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($st === 2) : ?>
                                                    <span class="badge badge-success px-2 py-1 font-10" style="border-radius: 4px;"><i class="fa fa-check mr-1"></i>Tamam</span>
                                                <?php elseif ($st === 1) : ?>
                                                    <span class="badge badge-primary px-2 py-1 font-10" style="border-radius: 4px;"><i class="fa fa-thumbs-up mr-1"></i>Onay</span>
                                                <?php elseif ($st === 3) : ?>
                                                    <span class="badge badge-danger px-2 py-1 font-10" style="border-radius: 4px;"><i class="fa fa-times mr-1"></i>Red</span>
                                                <?php else : ?>
                                                    <span class="badge badge-warning px-2 py-1 font-10" style="border-radius: 4px;"><i class="fa fa-clock-o mr-1"></i>Bekliyor</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?php echo $editLink; ?>" class="btn btn-outline-primary btn-xs py-1 px-2" title="Düzenle / Detay">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted font-13">Henüz satın alma kaydı bulunmamaktadır.</td>
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

<!-- ApexCharts Script Başlatma -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    var dashboardDark = document.body.classList.contains('dark-mode');
    var dashboardText = dashboardDark ? '#cbd5e1' : '#64748b';
    var dashboardGrid = dashboardDark ? '#334155' : '#e5e7eb';
    // 1. Aylık Satın Alma Harcama & Sipariş Trend Grafiği
    var categories = <?php echo json_encode($chartCategories, JSON_UNESCAPED_UNICODE); ?>;
    var totalOrders = <?php echo json_encode($chartTotalOrders); ?>;
    var completedOrders = <?php echo json_encode($chartCompletedOrders); ?>;
    var totalAmounts = <?php echo json_encode($chartTotalAmounts); ?>;

    var trendOptions = {
        series: [{
            name: 'Toplam Sipariş / Talep Adedi',
            type: 'column',
            data: totalOrders
        }, {
            name: 'Tamamlanan Sipariş Adedi',
            type: 'column',
            data: completedOrders
        }, {
            name: 'Toplam Harcama Tutarı (₺)',
            type: 'line',
            data: totalAmounts
        }],
        chart: {
            height: 330,
            type: 'line',
            stacked: false,
            toolbar: {
                show: false
            },
            fontFamily: 'Geist, sans-serif',
            foreColor: dashboardText
        },
        theme: { mode: dashboardDark ? 'dark' : 'light' },
        grid: { borderColor: dashboardGrid },
        colors: ['#3b82f6', '#10b981', '#f59e0b'],
        stroke: {
            width: [0, 0, 3],
            curve: 'smooth'
        },
        plotOptions: {
            bar: {
                columnWidth: '40%',
                borderRadius: 4
            }
        },
        fill: {
            opacity: [0.85, 0.85, 1],
            gradient: {
                inverseColors: false,
                shade: 'light',
                type: "vertical",
                opacityFrom: 0.85,
                opacityTo: 0.55
            }
        },
        labels: categories,
        markers: {
            size: 4
        },
        xaxis: {
            type: 'category',
            labels: {
                style: {
                    colors: '#64748b',
                    fontSize: '12px'
                }
            }
        },
        yaxis: [
            {
                title: {
                    text: 'İşlem Adedi',
                    style: { color: '#3b82f6' }
                },
                labels: {
                    style: { colors: '#64748b' }
                }
            },
            {
                show: false
            },
            {
                opposite: true,
                title: {
                    text: 'Toplam Harcama (₺)',
                    style: { color: '#f59e0b' }
                },
                labels: {
                    formatter: function (val) {
                        if (val >= 1000000) {
                            return (val / 1000000).toFixed(1) + 'M ₺';
                        } else if (val >= 1000) {
                            return (val / 1000).toFixed(0) + 'K ₺';
                        }
                        return val + ' ₺';
                    },
                    style: { colors: '#64748b' }
                }
            }
        ],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y, { seriesIndex }) {
                    if (seriesIndex === 2) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(y);
                    }
                    return y + " Adet";
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

    // 2. Durum Dağılımı Donut Grafiği
    var statusLabels = <?php echo json_encode($statusLabels, JSON_UNESCAPED_UNICODE); ?>;
    var statusCounts = <?php echo json_encode($statusCounts); ?>;
    var statusColors = <?php echo json_encode($statusColors); ?>;

    var donutOptions = {
        series: statusCounts,
        chart: {
            type: 'donut',
            height: 250,
            fontFamily: 'Geist, sans-serif',
            foreColor: dashboardText
        },
        theme: { mode: dashboardDark ? 'dark' : 'light' },
        stroke: { colors: [dashboardDark ? '#1e293b' : '#ffffff'] },
        labels: statusLabels,
        colors: statusColors,
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: { color: dashboardText },
                        value: { color: dashboardDark ? '#f8fafc' : '#1e293b' },
                        total: {
                            show: true,
                            label: 'Toplam İşlem',
                            fontSize: '13px',
                            fontWeight: 600,
                            color: dashboardText,
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
                    var total = statusCounts.reduce((a, b) => a + b, 0);
                    var pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                    return val + " Adet (" + pct + "%)";
                }
            }
        }
    };

    var donutChart = new ApexCharts(document.querySelector("#chart-status-donut"), donutOptions);
    donutChart.render();

    // 3. Flatpickr Tarih Seçici Başlatma
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#purchase_start_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            altInputClass: "form-control form-control-sm flatpickr-custom-input border-left-0",
            locale: "tr",
            allowInput: true
        });
        flatpickr("#purchase_end_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            altInputClass: "form-control form-control-sm flatpickr-custom-input",
            locale: "tr",
            allowInput: true
        });
    }
});
</script>
