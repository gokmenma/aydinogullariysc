<?php
use App\Model\ProductModel;
use App\Helper\Security;

// Yetki kontrolü
if (!permtrue('product_dashboard') && !permtrue('productcategory') && !permtrue('productadd') && !permtrue('productedit')) {
    echo '<div class="alert alert-danger m-4"><i class="fa fa-exclamation-triangle"></i> Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    return;
}

// Loglama
if (function_exists('audit_log')) {
    audit_log("view", "products", "Ürün & Hizmet Dashboard sayfası görüntülendi", "dashboard", 0);
}

$productModel = new ProductModel();

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
$summary = $productModel->getDashboardSummary($startDate, $endDate);
$topUsedProducts = $productModel->getTopUsedProducts(10, $startDate, $endDate);
$currencyDist = $productModel->getCurrencyDistribution($startDate, $endDate);
$unitDist = $productModel->getUnitDistribution(8, $startDate, $endDate);
$monthlyTrends = $productModel->getMonthlyProductTrends(12);
$topValueProducts = $productModel->getTopValueProducts(6);
$recentProducts = $productModel->getRecentProductsSummary(10);

// Para Birimi Formatlayıcı
if (!function_exists('formatCurrencyTR')) {
    function formatCurrencyTR($amount, $currency = '₺') {
        return number_format((float)$amount, 2, ',', '.') . ' ' . $currency;
    }
}
if (!function_exists('formatCompactTR')) {
    function formatCompactTR($amount, $currency = '₺') {
        $num = (float)$amount;
        if ($num >= 1000000000) {
            return number_format($num / 1000000000, 2, ',', '.') . ' Milyar ' . $currency;
        } elseif ($num >= 1000000) {
            return number_format($num / 1000000, 2, ',', '.') . ' Milyon ' . $currency;
        } elseif ($num >= 1000) {
            return number_format($num / 1000, 1, ',', '.') . ' Bin ' . $currency;
        }
        return number_format($num, 2, ',', '.') . ' ' . $currency;
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
/* Ürün Dashboard Özel Stilleri */
.product-dash-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 26px 28px;
    color: #ffffff !important;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
    position: relative;
    overflow: hidden;
}
.product-dash-hero .product-hero-title {
    color: #ffffff !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    letter-spacing: -0.3px;
    margin-bottom: 4px;
}
.product-dash-hero .product-hero-title i {
    color: #38bdf8 !important;
}
.product-dash-hero p, .product-dash-hero .product-hero-desc {
    color: rgba(255, 255, 255, 0.9) !important;
}
.product-dash-hero::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, rgba(56, 189, 248, 0) 70%);
    pointer-events: none;
}
.product-filter-pills {
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
.product-filter-pill {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.product-filter-pill:hover {
    color: #1e293b;
    background: #f1f5f9;
}
.product-filter-pill.active {
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
.product-kpi-card {
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
.product-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 20px -4px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}
.product-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}
.product-kpi-card.kpi-blue::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
.product-kpi-card.kpi-purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
.product-kpi-card.kpi-emerald::before { background: linear-gradient(90deg, #10b981, #34d399); }
.product-kpi-card.kpi-amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

.product-kpi-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.kpi-icon-blue { background: rgba(2, 132, 199, 0.12); color: #0284c7; }
.kpi-icon-purple { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }
.kpi-icon-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }
.kpi-icon-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }

.product-kpi-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
}
.product-kpi-value {
    font-size: 26px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.product-kpi-sub {
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
.dark-mode .product-kpi-card,
.dark-mode .product-filter-pills,
.dark-mode .crm-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
.dark-mode .product-kpi-value {
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
.dark-mode .product-filter-pill {
    color: #94a3b8;
}
.dark-mode .product-filter-pill:hover {
    color: #ffffff;
    background: #334155;
}
</style>

<div class="pd-20">
    
    <!-- 1. HERO BANNER & HIZLI AKSİYONLAR -->
    <div class="product-dash-hero">
        <div class="row align-items-center">
            <div class="col-lg-7 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                    <span class="badge badge-light text-dark px-3 py-2 font-12 font-weight-bold" style="border-radius: 8px;">
                        <i class="fa fa-calendar mr-1"></i> <?php echo $curDateFormatted; ?>
                    </span>
                    <span class="badge badge-primary px-3 py-2 font-12 font-weight-bold" style="border-radius: 8px; background: rgba(56, 189, 248, 0.4); border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fa fa-filter mr-1"></i> <?php echo htmlspecialchars($activeFilterLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <h2 class="product-hero-title">
                    <i class="fa fa-cubes mr-2"></i> Ürün & Hizmet Yönetim Paneli
                </h2>
                <p class="product-hero-desc">
                    Katalog portföyü, para birimi dağılımları, birim analizleri, tekliflerde en çok talep gören kalemler ve hareket trendleri.
                </p>
            </div>
            <div class="col-lg-5 col-md-12 text-lg-right">
                <div class="d-flex flex-wrap justify-content-lg-end" style="gap: 8px;">
                    <?php if (permtrue('productadd')) : ?>
                        <a href="index.php?p=products/manage" class="btn btn-success px-3 py-2 font-weight-bold shadow-sm" style="border-radius: 8px;">
                            <i class="fa fa-plus mr-1"></i> Yeni Ürün Ekle
                        </a>
                    <?php endif; ?>
                    <a href="index.php?p=products/list" class="btn btn-outline-light px-3 py-2 font-weight-bold" style="border-radius: 8px;">
                        <i class="fa fa-list mr-1"></i> Ürün Listesi
                    </a>
                    <?php if (permtrue('productcategory')) : ?>
                        <a href="index.php?p=products-categories" class="btn btn-outline-light px-3 py-2 font-weight-bold" style="border-radius: 8px;">
                            <i class="fa fa-tags mr-1"></i> Kategoriler
                        </a>
                    <?php endif; ?>
                    <a href="index.php?p=offers/items-list" class="btn btn-outline-light px-3 py-2 font-weight-bold" style="border-radius: 8px;">
                        <i class="fa fa-file-text-o mr-1"></i> Teklif Kalemleri
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DÖNEM & TARİH FİLTRELEME ÇUBUĞU -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4" style="gap: 12px;">
        <div class="product-filter-pills">
            <span class="font-12 font-weight-bold text-muted mr-1"><i class="fa fa-sliders mr-1"></i> Dönem:</span>
            <a href="index.php?p=products/dashboard&period=all" class="product-filter-pill <?php echo $period === 'all' ? 'active' : ''; ?>">Tümü</a>
            <a href="index.php?p=products/dashboard&period=this_year" class="product-filter-pill <?php echo $period === 'this_year' ? 'active' : ''; ?>">Bu Yıl (<?php echo date('Y'); ?>)</a>
            <a href="index.php?p=products/dashboard&period=this_month" class="product-filter-pill <?php echo $period === 'this_month' ? 'active' : ''; ?>">Bu Ay</a>
            <a href="index.php?p=products/dashboard&period=last_month" class="product-filter-pill <?php echo $period === 'last_month' ? 'active' : ''; ?>">Geçen Ay</a>
            <a href="index.php?p=products/dashboard&period=last_30_days" class="product-filter-pill <?php echo $period === 'last_30_days' ? 'active' : ''; ?>">Son 30 Gün</a>
            <a href="index.php?p=products/dashboard&period=last_90_days" class="product-filter-pill <?php echo $period === 'last_90_days' ? 'active' : ''; ?>">Son 90 Gün</a>
        </div>

        <form method="GET" action="index.php" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <input type="hidden" name="p" value="products/dashboard">
            <input type="hidden" name="period" value="custom">
            <div class="input-group input-group-sm" style="width: auto;">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0"><i class="fa fa-calendar text-primary"></i></span>
                </div>
                <input type="text" id="product_start_date" name="start_date" class="form-control form-control-sm flatpickr-custom-input border-left-0" placeholder="Başlangıç" value="<?php echo htmlspecialchars($startDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                <div class="input-group-prepend input-group-append">
                    <span class="input-group-text bg-light">-</span>
                </div>
                <input type="text" id="product_end_date" name="end_date" class="form-control form-control-sm flatpickr-custom-input" placeholder="Bitiş" value="<?php echo htmlspecialchars($endDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold"><i class="fa fa-search mr-1"></i> Filtrele</button>
                </div>
            </div>
            <?php if ($period !== 'all') : ?>
                <a href="index.php?p=products/dashboard" class="btn btn-outline-secondary btn-sm" title="Filtreyi Sıfırla"><i class="fa fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 3. KPI KARTLARI (4'LÜ GRID) -->
    <div class="row mb-4">
        <!-- Toplam Ürün & Hizmet -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="product-kpi-card kpi-blue">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="product-kpi-label">Toplam Ürün / Hizmet</div>
                        <div class="product-kpi-value"><?php echo number_format($summary->total_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="product-kpi-icon kpi-icon-blue">
                        <i class="fa fa-cubes"></i>
                    </div>
                </div>
                <div class="product-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Stok Kodu: <span class="text-primary font-weight-bold">%<?php echo $summary->sku_rate; ?></span></span>
                        <span class="badge badge-primary px-2 py-1 font-11"><?php echo $summary->with_sku_count; ?> Kalem</span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, $summary->sku_rate); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dövizli & TRY Portföyü -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="product-kpi-card kpi-purple">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="product-kpi-label">Dövizli Kalemler</div>
                        <div class="product-kpi-value text-purple" style="color: #7c3aed;"><?php echo number_format($summary->foreign_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="product-kpi-icon kpi-icon-purple">
                        <i class="fa fa-globe"></i>
                    </div>
                </div>
                <div class="product-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Döviz Ağırlığı: <span class="text-purple font-weight-bold">%<?php echo $summary->foreign_ratio; ?></span></span>
                        <span class="badge badge-purple px-2 py-1 font-11" style="background: #ede9fe; color: #5b21b6;"><?php echo $summary->eur_count; ?> EUR / <?php echo $summary->usd_count; ?> USD</span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar" role="progressbar" style="background: #8b5cf6; width: <?php echo min(100, $summary->foreign_ratio); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teklif Hareketliliği (Talep Gören Ürünler) -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="product-kpi-card kpi-emerald">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="product-kpi-label">Talep Gören Ürün Çeşidi</div>
                        <div class="product-kpi-value text-success"><?php echo number_format($summary->unique_offered_products, 0, ',', '.'); ?></div>
                    </div>
                    <div class="product-kpi-icon kpi-icon-emerald">
                        <i class="fa fa-shopping-basket"></i>
                    </div>
                </div>
                <div class="product-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Teklif Kalemi: <span class="text-success font-weight-bold"><?php echo number_format($summary->total_offer_items_count, 0, ',', '.'); ?></span></span>
                        <span class="badge badge-success px-2 py-1 font-11"><?php echo $summary->total_offers_with_items; ?> Teklifte</span>
                    </div>
                    <div class="font-11 text-muted mt-1">
                        Toplam Teklif Hacmi: <strong><?php echo formatCompactTR($summary->total_offered_amount); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Birim & Katalog Tanımlılık Oranı -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="product-kpi-card kpi-amber">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="product-kpi-label">Birim Tanımlılık Oranı</div>
                        <div class="product-kpi-value text-warning">%<?php echo $summary->unit_rate; ?></div>
                    </div>
                    <div class="product-kpi-icon kpi-icon-amber">
                        <i class="fa fa-tags"></i>
                    </div>
                </div>
                <div class="product-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Birimli Ürün: <span class="text-warning font-weight-bold"><?php echo $summary->with_unit_count; ?></span></span>
                        <span class="badge badge-warning px-2 py-1 font-11">%<?php echo $summary->unit_rate; ?></span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo min(100, $summary->unit_rate); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. GRAFİKLER (TREND & DAĞILIM) -->
    <div class="row mb-4">
        <!-- Aylık Teklif Kalemleri Hacim Trendi -->
        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="font-weight-bold mb-1"><i class="fa fa-bar-chart text-primary mr-2"></i> Tekliflerde Ürün Hareket Trendi (Son 12 Ay)</h5>
                            <p class="text-muted font-13 mb-0">Aylara göre teklif edilen toplam ürün kalemi sayısı ve benzersiz ürün çeşitliliği</p>
                        </div>
                        <div>
                            <span class="badge badge-light p-2 font-12 border"><i class="fa fa-info-circle text-info mr-1"></i> İnteraktif Grafiktir</span>
                        </div>
                    </div>
                    <div id="chart-product-trends" style="min-height: 330px;"></div>
                </div>
            </div>
        </div>

        <!-- Para Birimi Dağılımı (Donut) -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-body p-4 d-flex flex-direction-column flex-column justify-content-between">
                    <div>
                        <h5 class="font-weight-bold mb-1"><i class="fa fa-pie-chart text-warning mr-2"></i> Para Birimi Dağılımı</h5>
                        <p class="text-muted font-13 mb-3">Katalogdaki ürünlerin para birimi oranları</p>
                        <div id="chart-currency-donut" style="min-height: 230px;"></div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <?php foreach ($currencyDist as $cDist) : ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span style="width: 12px; height: 12px; border-radius: 3px; background: <?php echo $cDist->currency === 'TRY' ? '#0284c7' : ($cDist->currency === 'EUR' ? '#10b981' : '#f59e0b'); ?>; display: inline-block; margin-right: 8px;"></span>
                                    <span class="font-13"><?php echo htmlspecialchars($cDist->currency, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <span class="font-13 font-weight-bold"><?php echo $cDist->count; ?> Kalem</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. DETAYLI ÖZET TABLOLARI: EN ÇOK KULLANILAN ÜRÜNLER & BİRİM DAĞILIMI -->
    <div class="row mb-4">
        <!-- Tekliflerde En Çok Kullanılan Ürünler -->
        <div class="col-lg-7 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-fire text-danger mr-1"></i> Tekliflerde En Çok Talep Gören Ürünler
                        </h5>
                        <p class="text-muted font-12 mb-0">Teklif adedi ve talep yoğunluğuna göre ilk 10 ürün/hizmet</p>
                    </div>
                    <a href="index.php?p=offers/items-list" class="btn btn-xs btn-outline-primary py-1 px-2 font-12" style="border-radius: 6px;">
                        Tümü <i class="fa fa-angle-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;" class="text-center">#</th>
                                    <th style="width: 44%;">Ürün / Hizmet Adı</th>
                                    <th style="width: 18%;" class="text-center">Teklif / Miktar</th>
                                    <th style="width: 24%;" class="text-right">Toplam Tutar</th>
                                    <th style="width: 14%;" class="text-center">Ort. Fiyat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topUsedProducts)) : ?>
                                    <?php foreach ($topUsedProducts as $index => $p) : 
                                        $rankClass = $index === 0 ? 'rank-badge-1' : ($index === 1 ? 'rank-badge-2' : ($index === 2 ? 'rank-badge-3' : 'rank-badge-default'));
                                    ?>
                                        <tr>
                                            <td class="text-center p-1">
                                                <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $index + 1; ?></span>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis font-weight-bold text-dark" title="<?php echo htmlspecialchars($p->title, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($p->title, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div class="cell-ellipsis font-11 text-muted">
                                                    <?php if (!empty($p->stokKodu)) : ?>
                                                        <span class="badge badge-light border font-10 px-1 mr-1"><?php echo htmlspecialchars($p->stokKodu, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($p->unit ?: 'Adet', ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="font-weight-bold text-dark font-12"><?php echo $p->offer_count; ?></span> Teklif
                                                <div class="font-11 text-muted"><?php echo number_format($p->total_qty, 0, ',', '.'); ?> <?php echo htmlspecialchars($p->unit ?: '', ENT_QUOTES, 'UTF-8'); ?></div>
                                            </td>
                                            <td class="text-right font-weight-bold text-primary font-12">
                                                <?php echo formatCurrencyTR($p->total_revenue, $p->salecur ?: '₺'); ?>
                                            </td>
                                            <td class="text-center font-12 text-muted">
                                                <?php echo number_format($p->avg_price, 2, ',', '.'); ?> <?php echo htmlspecialchars($p->salecur ?: '₺', ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted font-13">Seçilen dönemde ürün hareket kaydı bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Birim Dağılımı Tablosu -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-balance-scale text-primary mr-1"></i> Birim Türü Dağılımı
                        </h5>
                        <p class="text-muted font-12 mb-0">Ürünlerin tanımlı ölçü birimlerine göre kırılımı</p>
                    </div>
                    <span class="badge badge-primary px-2 py-1 font-11" style="border-radius: 6px;">
                        <?php echo count($unitDist); ?> Birim
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Birim</th>
                                    <th style="width: 25%;" class="text-center">Ürün Adedi</th>
                                    <th style="width: 40%;">Dağılım Oranı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($unitDist)) : ?>
                                    <?php foreach ($unitDist as $u) : 
                                        $ratio = $summary->total_count > 0 ? round(($u->count / $summary->total_count) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td class="font-weight-bold text-dark">
                                                <i class="fa fa-tag text-muted mr-1"></i> <?php echo htmlspecialchars($u->unit_title, ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="font-weight-bold text-primary"><?php echo $u->count; ?></span> Kalem
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 8px;">
                                                    <div class="progress flex-grow-1" style="height: 6px; border-radius: 3px; background: #e2e8f0;">
                                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min(100, $ratio); ?>%;"></div>
                                                    </div>
                                                    <span class="font-11 font-weight-bold text-muted" style="min-width: 38px;">%<?php echo $ratio; ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted font-13">Birim verisi bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. KATMA DEĞERLİ ÜRÜNLER & EN SON EKLENEN ÜRÜNLER -->
    <div class="row">
        <!-- En Yüksek Fiyatlı Ürünler -->
        <div class="col-lg-6 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-star text-warning mr-1"></i> En Yüksek Fiyatlı Katalog Ürünleri
                        </h5>
                        <p class="text-muted font-12 mb-0">Birim satış fiyatı en yüksek katma değerli ürünler</p>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Ürün Adı</th>
                                    <th style="width: 25%;" class="text-right">Alış Fiyatı</th>
                                    <th style="width: 25%;" class="text-right">Satış Fiyatı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topValueProducts)) : ?>
                                    <?php foreach ($topValueProducts as $vp) : ?>
                                        <tr>
                                            <td>
                                                <div class="cell-ellipsis font-weight-bold text-dark" title="<?php echo htmlspecialchars($vp->Adi, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($vp->Adi, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div class="cell-ellipsis font-11 text-muted">
                                                    <?php if (!empty($vp->StokKodu)) : ?>
                                                        <span class="badge badge-light border font-10 px-1 mr-1"><?php echo htmlspecialchars($vp->StokKodu, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($vp->birim_adi ?: 'Adet', ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td class="text-right text-muted font-12">
                                                <?php echo !empty($vp->AlisFiyati) ? formatCurrencyTR($vp->AlisFiyati, $vp->AlisParaBirimi ?: '₺') : '-'; ?>
                                            </td>
                                            <td class="text-right font-weight-bold text-success font-12">
                                                <?php echo !empty($vp->SatisFiyati) ? formatCurrencyTR($vp->SatisFiyati, $vp->SatisParaBirimi ?: '₺') : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted font-13">Fiyatlı ürün kaydı bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kataloğa Son Eklenen Ürünler -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-history text-muted mr-1"></i> Son Eklenen / Güncellenen Ürünler
                        </h5>
                        <p class="text-muted font-12 mb-0">Kataloğa en son eklenen 10 ürün kaydı</p>
                    </div>
                    <a href="index.php?p=products/list" class="btn btn-xs btn-primary py-1 px-3 font-12" style="border-radius: 6px;">
                        Tüm Ürünler <i class="fa fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Ürün Adı</th>
                                    <th style="width: 25%;" class="text-center">Tarih</th>
                                    <th style="width: 25%;" class="text-right">Satış Fiyatı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentProducts)) : ?>
                                    <?php foreach ($recentProducts as $rp) : ?>
                                        <tr>
                                            <td>
                                                <div class="cell-ellipsis font-weight-bold text-dark" title="<?php echo htmlspecialchars($rp->Adi, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($rp->Adi, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div class="cell-ellipsis font-11 text-muted">
                                                    <?php if (!empty($rp->StokKodu)) : ?>
                                                        <span class="badge badge-light border font-10 px-1 mr-1"><?php echo htmlspecialchars($rp->StokKodu, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($rp->birim_adi ?: 'Birim Yok', ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td class="text-center font-12 text-muted">
                                                <?php echo htmlspecialchars($rp->OlusturmaTarihi ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="text-right font-weight-bold text-primary font-12">
                                                <?php echo !empty($rp->SatisFiyati) ? formatCurrencyTR($rp->SatisFiyati, $rp->SatisParaBirimi ?: '₺') : '<span class="text-muted">-</span>'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted font-13">Ürün kaydı bulunamadı.</td>
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

<!-- ApexCharts Script Başlatıcı -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Aylık Trend Grafiği (Son 12 Ay)
    var monthlyLabels = <?php echo json_encode(array_column($monthlyTrends, 'label')); ?>;
    var itemCountData = <?php echo json_encode(array_column($monthlyTrends, 'item_count')); ?>;
    var productVarietyData = <?php echo json_encode(array_column($monthlyTrends, 'product_variety')); ?>;

    var optionsTrends = {
        series: [
            {
                name: 'Teklif Kalemi Hacmi (Adet)',
                type: 'column',
                data: itemCountData
            },
            {
                name: 'Talep Gören Ürün Çeşitliliği',
                type: 'line',
                data: productVarietyData
            }
        ],
        chart: {
            height: 330,
            type: 'line',
            toolbar: { show: false },
            fontFamily: 'inherit'
        },
        stroke: {
            width: [0, 3],
            curve: 'smooth'
        },
        plotOptions: {
            bar: {
                columnWidth: '45%',
                borderRadius: 4
            }
        },
        colors: ['#0284c7', '#10b981'],
        labels: monthlyLabels,
        xaxis: {
            type: 'category',
            labels: {
                style: { colors: '#64748b', fontSize: '11px' }
            }
        },
        yaxis: [
            {
                title: { text: 'Kalem Adedi', style: { color: '#0284c7', fontSize: '11px' } },
                labels: { style: { colors: '#64748b' } }
            },
            {
                opposite: true,
                title: { text: 'Ürün Çeşidi', style: { color: '#10b981', fontSize: '11px' } },
                labels: { style: { colors: '#64748b' } }
            }
        ],
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '12px',
            markers: { radius: 12 }
        },
        tooltip: {
            shared: true,
            intersect: false
        }
    };

    var chartTrends = new ApexCharts(document.querySelector("#chart-product-trends"), optionsTrends);
    chartTrends.render();

    // 2. Para Birimi Donut Grafiği
    var currencyLabels = <?php echo json_encode(array_column($currencyDist, 'currency')); ?>;
    var currencyCounts = <?php echo json_encode(array_map('intval', array_column($currencyDist, 'count'))); ?>;

    var optionsDonut = {
        series: currencyCounts,
        labels: currencyLabels,
        chart: {
            type: 'donut',
            height: 230,
            fontFamily: 'inherit'
        },
        colors: ['#0284c7', '#10b981', '#f59e0b', '#8b5cf6'],
        legend: {
            position: 'bottom',
            fontSize: '12px'
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return "%" + val.toFixed(1);
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: { width: 200 },
                legend: { position: 'bottom' }
            }
        }]
    };

    var chartDonut = new ApexCharts(document.querySelector("#chart-currency-donut"), optionsDonut);
    chartDonut.render();

    // Flatpickr Başlatıcı
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#product_start_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            allowInput: true
        });
        flatpickr("#product_end_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            allowInput: true
        });
    }
});
</script>
