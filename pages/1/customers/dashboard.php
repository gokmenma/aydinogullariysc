<?php
use App\Model\CustomerModel;

// Yetki kontrolü
if (!permtrue('customer_dashboard') && !permtrue('customerview')) {
    echo '<div class="alert alert-danger m-4"><i class="fa fa-exclamation-triangle"></i> Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    return;
}

// Loglama
if (function_exists('audit_log')) {
    audit_log("view", "customers", "Firma & Müşteri Dashboard sayfası görüntülendi", "dashboard", 0);
}

$customerModel = new CustomerModel();

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
$summary = $customerModel->getDashboardSummary($startDate, $endDate);
$topCustomers = $customerModel->getTopCustomersByVolume(10, $startDate, $endDate);
$cityDistribution = $customerModel->getCityDistribution(8, $startDate, $endDate);
$groupDistribution = $customerModel->getGroupDistribution($startDate, $endDate);
$monthlyTrends = $customerModel->getMonthlyCustomerTrends(12);
$topCreators = $customerModel->getTopCustomerCreators(10, $startDate, $endDate);
$recentCustomers = $customerModel->getRecentCustomers(8);

// Para Birimi Formatlayıcı
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
?>

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
/* Müşteri Dashboard Özel Stilleri */
.cust-dash-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 26px 28px;
    color: #ffffff !important;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
    position: relative;
    overflow: hidden;
}
.cust-dash-hero .cust-hero-title {
    color: #ffffff !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    letter-spacing: -0.3px;
    margin-bottom: 4px;
}
.cust-dash-hero .cust-hero-title i {
    color: #38bdf8 !important;
}
.cust-dash-hero p, .cust-dash-hero .cust-hero-desc {
    color: rgba(255, 255, 255, 0.9) !important;
}
.cust-dash-hero::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, rgba(56, 189, 248, 0) 70%);
    pointer-events: none;
}
.cust-filter-pills {
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
.cust-filter-pill {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.cust-filter-pill:hover {
    color: #1e293b;
    background: #f1f5f9;
}
.cust-filter-pill.active {
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
.cust-kpi-card {
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
.cust-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 20px -4px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}
.cust-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}
.cust-kpi-card.kpi-sky::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
.cust-kpi-card.kpi-emerald::before { background: linear-gradient(90deg, #10b981, #34d399); }
.cust-kpi-card.kpi-amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.cust-kpi-card.kpi-purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }

.cust-kpi-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.kpi-icon-sky { background: rgba(2, 132, 199, 0.12); color: #0284c7; }
.kpi-icon-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }
.kpi-icon-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.kpi-icon-purple { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }

.cust-kpi-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
}
.cust-kpi-value {
    font-size: 26px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.cust-kpi-sub {
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

.user-avatar-badge {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
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
.dark-mode .cust-kpi-card,
.dark-mode .cust-filter-pills,
.dark-mode .crm-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
.dark-mode .cust-kpi-value {
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
.dark-mode .cust-filter-pill {
    color: #94a3b8;
}
.dark-mode .cust-filter-pill:hover {
    color: #ffffff;
    background: #334155;
}
</style>

<div class="pd-20">
    
    <!-- 1. HERO BANNER & HIZLI AKSİYONLAR -->
    <div class="cust-dash-hero">
        <div class="row align-items-center">
            <div class="col-lg-7 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                    <span class="badge badge-light text-dark px-3 py-2 font-12 font-weight-bold" style="border-radius: 8px;">
                        <i class="fa fa-calendar mr-1"></i> <?php echo $curDateFormatted; ?>
                    </span>
                    <span class="badge badge-primary px-3 py-2 font-12 font-weight-bold" style="border-radius: 8px; background: rgba(56, 189, 248, 0.35); border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fa fa-filter mr-1"></i> <?php echo htmlspecialchars($activeFilterLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <h2 class="cust-hero-title">
                    <i class="fa fa-users mr-2"></i> Müşteri & Cari Performans Paneli
                </h2>
                <p class="cust-hero-desc">
                    Müşteri portföy büyüklüğü, iletişim doluluk oranları, şehir ve grup dağılımları ile ticari işlem hacmi analizi.
                </p>
            </div>
            <div class="col-lg-5 col-md-12 text-lg-right">
                <div class="d-flex flex-wrap justify-content-lg-end" style="gap: 8px;">
                    <?php if (permtrue('customeradd')) : ?>
                        <a href="index.php?p=customers/manage" class="btn btn-success px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 8px;">
                            <i class="fa fa-plus mr-1"></i> Yeni Firma Ekle
                        </a>
                    <?php endif; ?>
                    <a href="index.php?p=customers/list" class="btn btn-outline-light px-3 py-2 font-weight-bold" style="border-radius: 8px;">
                        <i class="fa fa-list mr-1"></i> Firma Listesi
                    </a>
                    <?php if (permtrue('customerexport')) : ?>
                        <a href="api/customers_export.php" class="btn btn-outline-light px-3 py-2 font-weight-bold" style="border-radius: 8px;" target="_blank">
                            <i class="fa fa-file-excel-o mr-1"></i> Excel Dışa Aktar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DÖNEM & TARİH FİLTRELEME ÇUBUĞU -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4" style="gap: 12px;">
        <div class="cust-filter-pills">
            <span class="font-12 font-weight-bold text-muted mr-1"><i class="fa fa-sliders mr-1"></i> Kayıt Dönemi:</span>
            <a href="index.php?p=customers/dashboard&period=all" class="cust-filter-pill <?php echo $period === 'all' ? 'active' : ''; ?>">Tümü</a>
            <a href="index.php?p=customers/dashboard&period=this_year" class="cust-filter-pill <?php echo $period === 'this_year' ? 'active' : ''; ?>">Bu Yıl (<?php echo date('Y'); ?>)</a>
            <a href="index.php?p=customers/dashboard&period=this_month" class="cust-filter-pill <?php echo $period === 'this_month' ? 'active' : ''; ?>">Bu Ay</a>
            <a href="index.php?p=customers/dashboard&period=last_month" class="cust-filter-pill <?php echo $period === 'last_month' ? 'active' : ''; ?>">Geçen Ay</a>
            <a href="index.php?p=customers/dashboard&period=last_30_days" class="cust-filter-pill <?php echo $period === 'last_30_days' ? 'active' : ''; ?>">Son 30 Gün</a>
            <a href="index.php?p=customers/dashboard&period=last_90_days" class="cust-filter-pill <?php echo $period === 'last_90_days' ? 'active' : ''; ?>">Son 90 Gün</a>
        </div>

        <form method="GET" action="index.php" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <input type="hidden" name="p" value="customers/dashboard">
            <input type="hidden" name="period" value="custom">
            <div class="input-group input-group-sm" style="width: auto;">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0"><i class="fa fa-calendar text-primary"></i></span>
                </div>
                <input type="text" id="cust_start_date" name="start_date" class="form-control form-control-sm flatpickr-custom-input border-left-0" placeholder="Başlangıç" value="<?php echo htmlspecialchars($startDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                <div class="input-group-prepend input-group-append">
                    <span class="input-group-text bg-light">-</span>
                </div>
                <input type="text" id="cust_end_date" name="end_date" class="form-control form-control-sm flatpickr-custom-input" placeholder="Bitiş" value="<?php echo htmlspecialchars($endDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold"><i class="fa fa-search mr-1"></i> Filtrele</button>
                </div>
            </div>
            <?php if ($period !== 'all') : ?>
                <a href="index.php?p=customers/dashboard" class="btn btn-outline-secondary btn-sm" title="Filtreyi Sıfırla"><i class="fa fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 3. KPI KARTLARI (4'LÜ GRID) -->
    <div class="row mb-4">
        <!-- Toplam Firma Portföyü -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="cust-kpi-card kpi-sky">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="cust-kpi-label">Toplam Firma Sayısı</div>
                        <div class="cust-kpi-value text-primary"><?php echo number_format($summary->total_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="cust-kpi-icon kpi-icon-sky">
                        <i class="fa fa-building-o"></i>
                    </div>
                </div>
                <div class="cust-kpi-sub">
                    <div class="font-weight-bold text-dark">
                        Bu Ay: <span class="text-primary font-weight-bold">+<?php echo $summary->this_month_count; ?> Yeni Kayıt</span>
                    </div>
                    <div class="font-11 text-muted mt-1">
                        Şehirli Firma: <strong><?php echo number_format($summary->with_city_count, 0, ',', '.'); ?></strong> Adet
                    </div>
                </div>
            </div>
        </div>

        <!-- İletişim & Veri Kalitesi -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="cust-kpi-card kpi-emerald">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="cust-kpi-label">İletişim & Cari Kalitesi</div>
                        <div class="cust-kpi-value text-success">%<?php echo $summary->full_contact_rate; ?></div>
                    </div>
                    <div class="cust-kpi-icon kpi-icon-emerald">
                        <i class="fa fa-check-circle-o"></i>
                    </div>
                </div>
                <div class="cust-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">E-Posta: <span class="text-success font-weight-bold">%<?php echo $summary->email_rate; ?></span></span>
                        <span class="badge badge-success px-2 py-1 font-11">GSM: %<?php echo $summary->gsm_rate; ?></span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, $summary->full_contact_rate); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticari Etkileşim (Teklif Alan Müşteriler) -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="cust-kpi-card kpi-amber">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="cust-kpi-label">Teklif Verilen Firmalar</div>
                        <div class="cust-kpi-value text-warning"><?php echo number_format($summary->active_trading_customers, 0, ',', '.'); ?></div>
                    </div>
                    <div class="cust-kpi-icon kpi-icon-amber">
                        <i class="fa fa-handshake-o"></i>
                    </div>
                </div>
                <div class="cust-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Toplam Teklif: <span class="text-warning font-weight-bold"><?php echo number_format($summary->total_offers_count, 0, ',', '.'); ?></span></span>
                        <span class="badge badge-warning px-2 py-1 font-11">%<?php echo $summary->trading_rate; ?> Portföy</span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo min(100, $summary->trading_rate); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toplam Ticari Hacim & Büyüme -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="cust-kpi-card kpi-purple">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="cust-kpi-label">Kazanılan Teklif Cirosu</div>
                        <div class="cust-kpi-value text-purple" style="font-size: 22px;"><?php echo formatCompactTR($summary->won_trading_amount); ?></div>
                    </div>
                    <div class="cust-kpi-icon kpi-icon-purple">
                        <i class="fa fa-line-chart"></i>
                    </div>
                </div>
                <div class="cust-kpi-sub">
                    <div class="font-weight-bold text-dark">
                        Toplam Teklif Hacmi: <span class="text-purple font-weight-bold"><?php echo formatCompactTR($summary->total_trading_amount); ?></span>
                    </div>
                    <div class="font-11 text-muted mt-1">
                        Aylık Müşteri Artışı: 
                        <span class="<?php echo $summary->month_growth_rate >= 0 ? 'text-success' : 'text-danger'; ?> font-weight-bold">
                            <i class="fa <?php echo $summary->month_growth_rate >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i> %<?php echo abs($summary->month_growth_rate); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. GRAFİKLER (TREND & GRUP DAĞILIMI) -->
    <div class="row mb-4">
        <!-- Aylık Müşteri Kazanım ve Teklif Trendi -->
        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="font-weight-bold mb-1"><i class="fa fa-line-chart text-primary mr-2"></i> Aylık Müşteri Kazanımı & Ticari Etkileşim Trendi (Son 12 Ay)</h5>
                            <p class="text-muted font-13 mb-0">Aylara göre sisteme kaydedilen yeni müşteriler ve teklif verilen tekil firma adedi</p>
                        </div>
                        <div>
                            <span class="badge badge-light p-2 font-12 border"><i class="fa fa-info-circle text-info mr-1"></i> İnteraktif Grafiktir</span>
                        </div>
                    </div>
                    <div id="chart-customer-trends" style="min-height: 330px;"></div>
                </div>
            </div>
        </div>

        <!-- Müşteri Grubu Dağılımı -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-body p-4 d-flex flex-direction-column flex-column justify-content-between">
                    <div>
                        <h5 class="font-weight-bold mb-1"><i class="fa fa-pie-chart text-info mr-2"></i> Firma Grubu Dağılımı</h5>
                        <p class="text-muted font-13 mb-3">Kayıtlı firmaların sektör ve grup sınıfları</p>
                        <div id="chart-group-donut" style="min-height: 230px;"></div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <?php if (!empty($groupDistribution)) : ?>
                            <?php foreach (array_slice($groupDistribution, 0, 3) as $grp) : ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="font-13 text-muted"><?php echo htmlspecialchars($grp->group_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="font-13 font-weight-bold"><?php echo number_format($grp->count, 0, ',', '.'); ?> Firma</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. İKİNCİ GRAFİK SATIRI (ŞEHİR BAZLI DAĞILIM & TEMSİLCİ ÖZETİ) -->
    <div class="row mb-4">
        <!-- Şehirlere Göre Dağılım (Bar Chart) -->
        <div class="col-lg-7 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="font-weight-bold mb-1"><i class="fa fa-map-marker text-danger mr-2"></i> Şehirlere Göre Firma Yoğunluğu (İlk 8 Şehir)</h5>
                            <p class="text-muted font-13 mb-0">En çok müşterinin bulunduğu illerin portföy dağılımı</p>
                        </div>
                    </div>
                    <div id="chart-city-bar" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>

        <!-- En Çok Müşteri Ekleyen Personeller -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-user-circle text-primary mr-1"></i> Portföy Oluşturan Temsilciler
                        </h5>
                        <p class="text-muted font-12 mb-0">Sisteme en çok müşteri kaydı yapan personeller</p>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;" class="text-center">#</th>
                                    <th style="width: 48%;">Kullanıcı / Temsilci</th>
                                    <th style="width: 26%;" class="text-center">Kayıt Sayısı</th>
                                    <th style="width: 26%;" class="text-center">E-Posta / GSM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topCreators)) : ?>
                                    <?php foreach (array_slice($topCreators, 0, 5) as $index => $u) : 
                                        $rankClass = $index === 0 ? 'rank-badge-1' : ($index === 1 ? 'rank-badge-2' : ($index === 2 ? 'rank-badge-3' : 'rank-badge-default'));
                                    ?>
                                        <tr>
                                            <td class="text-center p-1">
                                                <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $index + 1; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 8px;">
                                                    <div class="user-avatar-badge" style="width: 30px; height: 30px; font-size: 11px;">
                                                        <?php 
                                                            $initials = mb_substr($u->username ?? 'U', 0, 2, 'UTF-8');
                                                            echo htmlspecialchars(mb_strtoupper($initials, 'UTF-8'), ENT_QUOTES, 'UTF-8');
                                                        ?>
                                                    </div>
                                                    <div style="min-width: 0;">
                                                        <div class="font-weight-bold text-dark cell-ellipsis font-12" title="<?php echo htmlspecialchars($u->username, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php echo htmlspecialchars($u->username, ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                        <div class="font-10 text-muted cell-ellipsis">
                                                            <?php echo htmlspecialchars($u->user_title ?: 'Temsilci', ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-primary px-2 py-1 font-11 font-weight-bold"><?php echo $u->total_customers; ?></span>
                                            </td>
                                            <td class="text-center font-11 text-muted">
                                                <span class="text-success font-weight-bold"><?php echo $u->with_email; ?></span> / 
                                                <span class="text-info font-weight-bold"><?php echo $u->with_gsm; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Veri bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. DETAYLI ÖZET TABLOLARI: EN YÜKSEK HACİMLİ FİRMALAR & SON EKLENENLER -->
    <div class="row mb-4">
        <!-- En Yüksek Hacimli Firmalar -->
        <div class="col-lg-7 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-trophy text-warning mr-1"></i> En Yüksek Ticari Hacimli Müşteriler
                        </h5>
                        <p class="text-muted font-12 mb-0">Teklif cirosu ve işlem hacmine göre ilk 10 müşteri</p>
                    </div>
                    <a href="index.php?p=customers/list" class="btn btn-xs btn-outline-primary py-1 px-2 font-12" style="border-radius: 6px;">
                        Tümü <i class="fa fa-angle-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;" class="text-center">#</th>
                                    <th style="width: 38%;">Firma Adı</th>
                                    <th style="width: 18%;" class="text-center">Şehir / Grup</th>
                                    <th style="width: 24%;" class="text-right">Teklif Tutarı</th>
                                    <th style="width: 20%;" class="text-center">Onay / Oran</th>
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
                                                <a href="index.php?p=customer-info&id=<?php echo $c->customer_id; ?>" class="font-weight-bold text-dark cell-ellipsis" title="<?php echo htmlspecialchars($c->company_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($c->company_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-light border text-muted font-11">
                                                    <?php echo htmlspecialchars($c->city ?: 'Belirtilmedi', ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td class="text-right font-weight-bold text-dark font-12">
                                                <?php echo formatCompactTR($c->total_amount); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-success px-2 py-1 font-11">
                                                    <?php echo $c->won_offers; ?> / %<?php echo $c->win_rate; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Bu filtreye uygun veri bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sisteme Son Eklenen Müşteriler -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-clock-o text-info mr-1"></i> Son Eklenen Müşteriler
                        </h5>
                        <p class="text-muted font-12 mb-0">Rehbere en son dahil edilen firma kayıtları</p>
                    </div>
                    <a href="index.php?p=customers/list" class="btn btn-xs btn-outline-primary py-1 px-2 font-12" style="border-radius: 6px;">
                        Tüm Liste <i class="fa fa-angle-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 48%;">Firma Adı</th>
                                    <th style="width: 26%;">İletişim</th>
                                    <th style="width: 26%;" class="text-right">Kayıt Tarihi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentCustomers)) : ?>
                                    <?php foreach ($recentCustomers as $rc) : ?>
                                        <tr>
                                            <td>
                                                <a href="index.php?p=customer-info&id=<?php echo $rc->id; ?>" class="font-weight-bold text-dark cell-ellipsis font-12" title="<?php echo htmlspecialchars($rc->company, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($rc->company, ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                                <div class="font-10 text-muted cell-ellipsis">
                                                    <?php echo htmlspecialchars(($rc->city ? $rc->city . ' / ' : '') . ($rc->yetkili ?: 'Yetkili Yok'), ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center font-11" style="gap: 4px;">
                                                    <?php if (!empty($rc->gsm)) : ?>
                                                        <a href="tel:<?php echo htmlspecialchars($rc->gsm, ENT_QUOTES, 'UTF-8'); ?>" class="text-success" title="<?php echo htmlspecialchars($rc->gsm, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-phone"></i></a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($rc->email)) : ?>
                                                        <a href="mailto:<?php echo htmlspecialchars($rc->email, ENT_QUOTES, 'UTF-8'); ?>" class="text-primary" title="<?php echo htmlspecialchars($rc->email, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-envelope-o"></i></a>
                                                    <?php endif; ?>
                                                    <span class="text-muted font-10"><?php echo htmlspecialchars($rc->group_title ?: 'Cari', ENT_QUOTES, 'UTF-8'); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-right text-muted font-11">
                                                <?php echo !empty($rc->regdate) ? date('d.m.Y', strtotime($rc->regdate)) : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Kayıt bulunamadı.</td>
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
    // 1. Flatpickr Başlatma
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#cust_start_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            locale: "tr"
        });
        flatpickr("#cust_end_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            locale: "tr"
        });
    }

    // 2. Aylık Trend Grafiği (Spline / Area)
    <?php
    $trendLabels = [];
    $trendNewCustomers = [];
    $trendTradingCustomers = [];
    $trendVolumes = [];

    foreach ($monthlyTrends as $m) {
        $trendLabels[] = $m['label'];
        $trendNewCustomers[] = $m['new_customers'];
        $trendTradingCustomers[] = $m['trading_customers'];
        $trendVolumes[] = round($m['total_volume'] / 1000, 1); // Bin TL cinsinden
    }
    ?>
    var trendLabels = <?php echo json_encode($trendLabels); ?>;
    var trendNewCust = <?php echo json_encode($trendNewCustomers); ?>;
    var trendTradeCust = <?php echo json_encode($trendTradingCustomers); ?>;

    var optionsTrends = {
        series: [{
            name: 'Yeni Eklenen Müşteri',
            type: 'column',
            data: trendNewCust
        }, {
            name: 'Teklif Alan Müşteri',
            type: 'line',
            data: trendTradeCust
        }],
        chart: {
            height: 330,
            type: 'line',
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        stroke: {
            width: [0, 3],
            curve: 'smooth'
        },
        plotOptions: {
            bar: {
                columnWidth: '40%',
                borderRadius: 4
            }
        },
        fill: {
            opacity: [0.85, 1],
            gradient: {
                inverseColors: false,
                shade: 'light',
                type: "vertical",
                opacityFrom: 0.85,
                opacityTo: 0.55
            }
        },
        colors: ['#0284c7', '#10b981'],
        labels: trendLabels,
        markers: {
            size: [0, 4]
        },
        yaxis: [{
            title: {
                text: 'Yeni Müşteri (Adet)',
                style: { fontSize: '11px', color: '#0284c7' }
            }
        }, {
            opposite: true,
            title: {
                text: 'Teklif Alan Firma (Adet)',
                style: { fontSize: '11px', color: '#10b981' }
            }
        }],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y) {
                    if (typeof y !== "undefined") {
                        return y.toFixed(0) + " Firma";
                    }
                    return y;
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        }
    };

    var chartTrends = new ApexCharts(document.querySelector("#chart-customer-trends"), optionsTrends);
    chartTrends.render();

    // 3. Firma Grubu Donut Grafiği
    <?php
    $grpLabels = [];
    $grpSeries = [];
    foreach ($groupDistribution as $g) {
        $grpLabels[] = $g->group_name;
        $grpSeries[] = (int)$g->count;
    }
    if (empty($grpSeries)) {
        $grpLabels = ['Tanımsız'];
        $grpSeries = [1];
    }
    ?>
    var optionsGroup = {
        series: <?php echo json_encode($grpSeries); ?>,
        labels: <?php echo json_encode($grpLabels); ?>,
        chart: {
            type: 'donut',
            height: 240,
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#0284c7', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
        legend: {
            show: true,
            position: 'bottom'
        },
        dataLabels: {
            enabled: false
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Toplam',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        }
    };

    var chartGroup = new ApexCharts(document.querySelector("#chart-group-donut"), optionsGroup);
    chartGroup.render();

    // 4. Şehir Bazlı Dağılım Çubuk Grafiği
    <?php
    $cityLabels = [];
    $cityCounts = [];
    foreach ($cityDistribution as $ct) {
        $cityLabels[] = $ct->city_name;
        $cityCounts[] = (int)$ct->count;
    }
    ?>
    var optionsCity = {
        series: [{
            name: 'Kayıtlı Firma',
            data: <?php echo json_encode($cityCounts); ?>
        }],
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: false,
                columnWidth: '45%',
            }
        },
        dataLabels: {
            enabled: false
        },
        colors: ['#0284c7'],
        xaxis: {
            categories: <?php echo json_encode($cityLabels); ?>,
            labels: {
                style: { fontSize: '11px' }
            }
        },
        yaxis: {
            title: {
                text: 'Firma Sayısı'
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " Firma";
                }
            }
        }
    };

    var chartCity = new ApexCharts(document.querySelector("#chart-city-bar"), optionsCity);
    chartCity.render();
});
</script>
