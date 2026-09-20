<?php
use App\Model\OfferModel;

// Yetki kontrolü
if (!permtrue('offer_dashboard') && !permtrue('offerview')) {
    echo '<div class="alert alert-danger m-4"><i class="fa fa-exclamation-triangle"></i> Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    return;
}

// Loglama
if (function_exists('audit_log')) {
    audit_log("view", "offers", "Teklifler Dashboard sayfası görüntülendi", "dashboard", 0);
}

$offerModel = new OfferModel();

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
$summary = $offerModel->getDashboardSummary($startDate, $endDate);
$topCustomers = $offerModel->getTopCustomers(10, $startDate, $endDate);
$topUsers = $offerModel->getTopUsers(10, $startDate, $endDate);
$monthlyTrends = $offerModel->getMonthlyTrends(12);
$statusDistribution = $offerModel->getStatusDistribution($startDate, $endDate);
$recentOffers = $offerModel->getRecentOffersSummary(10);
$topValueOffers = $offerModel->getTopValueOffers(5);

// Para Birimi Formatlayıcı
function formatCurrencyTR($amount) {
    return number_format((float)$amount, 2, ',', '.') . ' ₺';
}
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
/* Teklif Dashboard Özel Stilleri */
.offer-dash-hero {
    background: #ffffff;
    border-radius: 14px;
    padding: 20px 24px;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #4f46e5;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.03);
    position: relative;
}
.offer-dash-hero .offer-hero-title {
    color: #1e293b !important;
    font-size: 20px !important;
    font-weight: 700 !important;
    letter-spacing: -0.3px;
    margin-bottom: 2px;
}
.offer-dash-hero .offer-hero-icon-box {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #eef2ff;
    border: 1px solid #e0e7ff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #4f46e5;
    font-size: 19px;
    margin-right: 14px;
    flex-shrink: 0;
}
.offer-dash-hero p, .offer-dash-hero .offer-hero-desc {
    color: #64748b !important;
    font-size: 13px;
    margin: 0;
}
.btn-ghost-soft {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155 !important;
    font-size: 13px;
    font-weight: 600;
    padding: 7px 14px;
    border-radius: 8px;
    transition: all 0.2s ease;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.btn-ghost-soft:hover {
    background: #f8fafc;
    color: #0f172a !important;
    border-color: #94a3b8;
    transform: translateY(-1px);
}
.btn-action-primary {
    background: #10b981;
    border: 1px solid #059669;
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 600;
    padding: 7px 16px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
}
.btn-action-primary:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.35);
}
.offer-filter-pills {
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
.offer-filter-pill {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.offer-filter-pill:hover {
    color: #1e293b;
    background: #f1f5f9;
}
.offer-filter-pill.active {
    background: #4f46e5;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.35);
}
.flatpickr-custom-input {
    background-color: #ffffff !important;
    cursor: pointer;
    font-size: 13px;
    width: 115px !important;
}
.flatpickr-custom-input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
}

/* KPI Kartları */
.offer-kpi-card {
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
.offer-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 20px -4px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}
.offer-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}
.offer-kpi-card.kpi-indigo::before { background: linear-gradient(90deg, #6366f1, #818cf8); }
.offer-kpi-card.kpi-emerald::before { background: linear-gradient(90deg, #10b981, #34d399); }
.offer-kpi-card.kpi-amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.offer-kpi-card.kpi-purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }

.offer-kpi-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.kpi-icon-indigo { background: rgba(99, 102, 241, 0.12); color: #4f46e5; }
.kpi-icon-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }
.kpi-icon-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.kpi-icon-purple { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }

.offer-kpi-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
}
.offer-kpi-value {
    font-size: 26px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.offer-kpi-sub {
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
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
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

.offer-dashboard-container {
    width: 100%;
}
.offer-dashboard-container .row {
    margin-left: -8px;
    margin-right: -8px;
}
.offer-dashboard-container [class*="col-"] {
    padding-left: 8px;
    padding-right: 8px;
}

/* Dark mode uyumu */
.dark-mode .offer-dash-hero,
.dark-mode .offer-kpi-card,
.dark-mode .offer-filter-pills,
.dark-mode .crm-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
.dark-mode .offer-dash-hero {
    border-left-color: #6366f1 !important;
}
.dark-mode .offer-dash-hero .offer-hero-title {
    color: #f8fafc !important;
}
.dark-mode .offer-dash-hero .offer-hero-desc {
    color: #94a3b8 !important;
}
.dark-mode .offer-dash-hero .offer-hero-icon-box {
    background: #0f172a !important;
    color: #818cf8 !important;
    border-color: #334155 !important;
}
.dark-mode .btn-ghost-soft {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
.dark-mode .btn-ghost-soft:hover {
    background: #1e293b !important;
    color: #ffffff !important;
}
.dark-mode .offer-kpi-value {
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
.dark-mode .offer-filter-pill {
    color: #94a3b8;
}
.dark-mode .offer-filter-pill:hover {
    color: #ffffff;
    background: #334155;
}
</style>

<div class="offer-dashboard-container">
    
    <!-- 1. HERO BANNER & HIZLI AKSİYONLAR -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="offer-dash-hero mb-0">
                <div class="row align-items-center">
                    <div class="col-lg-7 col-md-12 mb-3 mb-lg-0">
                        <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                            <span class="badge" style="background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; padding: 5px 10px; border-radius: 6px;">
                                <i class="fa fa-calendar mr-1 text-muted"></i> <?php echo $curDateFormatted; ?>
                            </span>
                            <span class="badge" style="background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; font-size: 12px; font-weight: 600; padding: 5px 10px; border-radius: 6px;">
                                <i class="fa fa-filter mr-1"></i> <?php echo htmlspecialchars($activeFilterLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="offer-hero-icon-box">
                                <i class="fa fa-file-text-o"></i>
                            </div>
                            <div>
                                <h2 class="offer-hero-title">
                                    Teklif Yönetimi & Performans Paneli
                                </h2>
                                <p class="offer-hero-desc">
                                    Müşteri teklif dağılımları, satış temsilcisi performansları, onay trendleri ve finansal hacim analizi.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-12 text-lg-right">
                        <div class="d-flex flex-wrap justify-content-lg-end" style="gap: 8px;">
                            <?php if (permtrue('offeradd')) : ?>
                                <a href="index.php?p=offers/offer-manage" class="btn-action-primary">
                                    <i class="fa fa-plus mr-1"></i> Yeni Teklif
                                </a>
                            <?php endif; ?>
                            <a href="index.php?p=offers/list" class="btn-ghost-soft">
                                <i class="fa fa-list mr-1 text-muted"></i> Teklif Listesi
                            </a>
                            <a href="index.php?p=offers/items-list" class="btn-ghost-soft">
                                <i class="fa fa-cubes mr-1 text-muted"></i> Kalemler
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DÖNEM & TARİH FİLTRELEME ÇUBUĞU -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: 12px;">
                <div class="offer-filter-pills">
                    <span class="font-12 font-weight-bold text-muted mr-1"><i class="fa fa-sliders mr-1"></i> Dönem:</span>
                    <a href="index.php?p=offers/dashboard&period=all" class="offer-filter-pill <?php echo $period === 'all' ? 'active' : ''; ?>">Tümü</a>
                    <a href="index.php?p=offers/dashboard&period=this_year" class="offer-filter-pill <?php echo $period === 'this_year' ? 'active' : ''; ?>">Bu Yıl (<?php echo date('Y'); ?>)</a>
                    <a href="index.php?p=offers/dashboard&period=this_month" class="offer-filter-pill <?php echo $period === 'this_month' ? 'active' : ''; ?>">Bu Ay</a>
                    <a href="index.php?p=offers/dashboard&period=last_month" class="offer-filter-pill <?php echo $period === 'last_month' ? 'active' : ''; ?>">Geçen Ay</a>
                    <a href="index.php?p=offers/dashboard&period=last_30_days" class="offer-filter-pill <?php echo $period === 'last_30_days' ? 'active' : ''; ?>">Son 30 Gün</a>
                    <a href="index.php?p=offers/dashboard&period=last_90_days" class="offer-filter-pill <?php echo $period === 'last_90_days' ? 'active' : ''; ?>">Son 90 Gün</a>
                </div>

                <form method="GET" action="index.php" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                    <input type="hidden" name="p" value="offers/dashboard">
                    <input type="hidden" name="period" value="custom">
                    <div class="input-group input-group-sm" style="width: auto;">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="fa fa-calendar text-primary"></i></span>
                        </div>
                        <input type="text" id="offer_start_date" name="start_date" class="form-control form-control-sm flatpickr-custom-input border-left-0" placeholder="Başlangıç" value="<?php echo htmlspecialchars($startDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                        <div class="input-group-prepend input-group-append">
                            <span class="input-group-text bg-light">-</span>
                        </div>
                        <input type="text" id="offer_end_date" name="end_date" class="form-control form-control-sm flatpickr-custom-input" placeholder="Bitiş" value="<?php echo htmlspecialchars($endDate ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold"><i class="fa fa-search mr-1"></i> Filtrele</button>
                        </div>
                    </div>
                    <?php if ($period !== 'all') : ?>
                        <a href="index.php?p=offers/dashboard" class="btn btn-outline-secondary btn-sm" title="Filtreyi Sıfırla"><i class="fa fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. KPI KARTLARI (4'LÜ GRID) -->
    <div class="row mb-3">
        <!-- Toplam Teklif -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="offer-kpi-card kpi-indigo">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="offer-kpi-label">Toplam Teklif Sayısı</div>
                        <div class="offer-kpi-value"><?php echo number_format($summary->total_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="offer-kpi-icon kpi-icon-indigo">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                </div>
                <div class="offer-kpi-sub">
                    <div class="font-weight-bold text-dark">
                        Hacim: <span class="text-primary font-weight-bold"><?php echo formatCompactTR($summary->total_amount); ?></span>
                    </div>
                    <div class="font-11 text-muted mt-1">
                        Bu ay <strong><?php echo $summary->this_month->count; ?></strong> yeni teklif oluşturuldu
                    </div>
                </div>
            </div>
        </div>

        <!-- Kazanılan / Onaylanan Teklifler -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="offer-kpi-card kpi-emerald">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="offer-kpi-label">Kazanılan Teklifler</div>
                        <div class="offer-kpi-value text-success"><?php echo number_format($summary->won_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="offer-kpi-icon kpi-icon-emerald">
                        <i class="fa fa-check-circle-o"></i>
                    </div>
                </div>
                <div class="offer-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Ciro: <span class="text-success font-weight-bold"><?php echo formatCompactTR($summary->won_amount); ?></span></span>
                        <span class="badge badge-success px-2 py-1 font-11">%<?php echo $summary->win_rate; ?> Başarı</span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, $summary->win_rate); ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bekleyen Teklifler (Pipeline) -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="offer-kpi-card kpi-amber">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="offer-kpi-label">Bekleyen Teklifler</div>
                        <div class="offer-kpi-value text-warning"><?php echo number_format($summary->pending_count, 0, ',', '.'); ?></div>
                    </div>
                    <div class="offer-kpi-icon kpi-icon-amber">
                        <i class="fa fa-clock-o"></i>
                    </div>
                </div>
                <div class="offer-kpi-sub">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">Açık Pipeline: <span class="text-warning font-weight-bold"><?php echo formatCompactTR($summary->pending_amount); ?></span></span>
                        <span class="badge badge-warning px-2 py-1 font-11">%<?php echo $summary->total_count > 0 ? round(($summary->pending_count / $summary->total_count) * 100, 1) : 0; ?></span>
                    </div>
                    <div class="progress mt-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $summary->total_count > 0 ? min(100, round(($summary->pending_count / $summary->total_count) * 100, 1)) : 0; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ortalama Teklif Tutarı -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="offer-kpi-card kpi-purple">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="offer-kpi-label">Ortalama Teklif</div>
                        <div class="offer-kpi-value text-purple" style="font-size: 22px;"><?php echo formatCurrencyTR($summary->avg_amount); ?></div>
                    </div>
                    <div class="offer-kpi-icon kpi-icon-purple">
                        <i class="fa fa-line-chart"></i>
                    </div>
                </div>
                <div class="offer-kpi-sub">
                    <div class="font-weight-bold text-dark">
                        Bu Ay Ciro: <span class="text-purple font-weight-bold"><?php echo formatCompactTR($summary->this_month->won_amount); ?></span>
                    </div>
                    <div class="font-11 text-muted mt-1">
                        Aylık Teklif Değişimi: 
                        <span class="<?php echo $summary->month_growth_rate >= 0 ? 'text-success' : 'text-danger'; ?> font-weight-bold">
                            <i class="fa <?php echo $summary->month_growth_rate >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i> %<?php echo abs($summary->month_growth_rate); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. GRAFİKLER (TREND & DURUM DAĞILIMI) -->
    <div class="row mb-4">
        <!-- Aylık Teklif Trendi -->
        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="font-weight-bold mb-1"><i class="fa fa-bar-chart text-primary mr-2"></i> Aylık Teklif & Ciro Trendi (Son 12 Ay)</h5>
                            <p class="text-muted font-13 mb-0">Aylara göre açılan toplam teklifler, onaylanan teklifler ve toplam hacim dağılımı</p>
                        </div>
                        <div>
                            <span class="badge badge-light p-2 font-12 border"><i class="fa fa-info-circle text-info mr-1"></i> İnteraktif Grafiktir</span>
                        </div>
                    </div>
                    <div id="chart-monthly-trends" style="min-height: 330px;"></div>
                </div>
            </div>
        </div>

        <!-- Durum ve Başarı Dağılımı -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-body p-4 d-flex flex-direction-column flex-column justify-content-between">
                    <div>
                        <h5 class="font-weight-bold mb-1"><i class="fa fa-pie-chart text-warning mr-2"></i> Teklif Durum Dağılımı</h5>
                        <p class="text-muted font-13 mb-3">Tekliflerin onay ve bekleme oranları</p>
                        <div id="chart-status-donut" style="min-height: 230px;"></div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <span style="width: 12px; height: 12px; border-radius: 3px; background: #10b981; display: inline-block; margin-right: 8px;"></span>
                                <span class="font-13">Tamamlandı / Onaylandı</span>
                            </div>
                            <span class="font-13 font-weight-bold"><?php echo $summary->won_count; ?> Adet</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span style="width: 12px; height: 12px; border-radius: 3px; background: #f59e0b; display: inline-block; margin-right: 8px;"></span>
                                <span class="font-13">Bekleyen Teklifler</span>
                            </div>
                            <span class="font-13 font-weight-bold"><?php echo $summary->pending_count; ?> Adet</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. DETAYLI ÖZET TABLOLARI: EN ÇOK TEKLİF VERİLEN FİRMALAR & PERSONELLER -->
    <div class="row mb-4">
        <!-- En Çok Teklif Verilen Firmalar -->
        <div class="col-lg-6 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-building text-primary mr-1"></i> En Çok Teklif Verilen Firmalar
                        </h5>
                        <p class="text-muted font-12 mb-0">Teklif adedi ve portföy hacmine göre ilk 10 müşteri</p>
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
                                    <th style="width: 40%;">Firma Adı</th>
                                    <th style="width: 18%;" class="text-center">Teklif / Onay</th>
                                    <th style="width: 24%;" class="text-right">Toplam Tutar</th>
                                    <th style="width: 18%;" class="text-center">Başarı</th>
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
                                                <div class="cell-ellipsis font-weight-bold text-dark" title="<?php echo htmlspecialchars($c->company_name, ENT_QUOTES, 'UTF-8'); ?>">
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
                                            <td class="text-center">
                                                <span class="font-weight-bold text-dark font-12"><?php echo $c->total_offers; ?></span>
                                                <span class="font-11 text-muted"> / <span class="text-success font-weight-bold"><?php echo $c->won_offers; ?></span></span>
                                            </td>
                                            <td class="text-right font-weight-bold text-primary font-12">
                                                <?php echo formatCurrencyTR($c->total_amount); ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center" style="gap: 4px;">
                                                    <div class="progress flex-grow-1" style="height: 5px; width: 35px; border-radius: 3px; background: #e2e8f0;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, $c->win_rate); ?>%;"></div>
                                                    </div>
                                                    <span class="font-11 font-weight-bold text-dark">%<?php echo $c->win_rate; ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted font-13">Seçilen dönemde teklif kaydı bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- En Çok Teklif Hazırlayan Satış Temsilcileri / Kullanıcılar -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-users text-indigo mr-1"></i> En Çok Teklif Veren Personeller
                        </h5>
                        <p class="text-muted font-12 mb-0">Satış temsilcilerinin teklif sayısı ve başarı performansı</p>
                    </div>
                    <span class="badge badge-indigo text-white px-2 py-1 font-11" style="border-radius: 6px; background: #6366f1;">
                        <?php echo count($topUsers); ?> Temsilci
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 32px;" class="text-center">#</th>
                                    <th style="width: 40%;">Kullanıcı / Temsilci</th>
                                    <th style="width: 18%;" class="text-center">Teklif / Onay</th>
                                    <th style="width: 24%;" class="text-right">Oluşturulan Tutar</th>
                                    <th style="width: 18%;" class="text-center">Başarı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topUsers)) : ?>
                                    <?php foreach ($topUsers as $index => $u) : 
                                        $rankClass = $index === 0 ? 'rank-badge-1' : ($index === 1 ? 'rank-badge-2' : ($index === 2 ? 'rank-badge-3' : 'rank-badge-default'));
                                        $initials = mb_substr($u->username, 0, 2, 'UTF-8');
                                    ?>
                                        <tr>
                                            <td class="text-center p-1">
                                                <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $index + 1; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 8px;">
                                                    <div class="user-avatar-badge" style="width: 30px; height: 30px; font-size: 11px; flex-shrink: 0;">
                                                        <?php echo strtoupper($initials); ?>
                                                    </div>
                                                    <div style="min-width: 0; flex: 1;">
                                                        <div class="cell-ellipsis font-weight-bold text-dark font-12" title="<?php echo htmlspecialchars($u->username, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php echo htmlspecialchars($u->username, ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                        <div class="cell-ellipsis font-11 text-muted" title="<?php echo htmlspecialchars($u->user_title ?: 'Temsilci', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php echo htmlspecialchars($u->user_title ?: 'Temsilci', ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="font-weight-bold text-dark font-12"><?php echo $u->total_offers; ?></span>
                                                <span class="font-11 text-muted"> / <span class="text-success font-weight-bold"><?php echo $u->won_offers; ?></span></span>
                                            </td>
                                            <td class="text-right font-weight-bold text-indigo font-12" style="color: #4f46e5;">
                                                <?php echo formatCurrencyTR($u->total_amount); ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center" style="gap: 4px;">
                                                    <div class="progress flex-grow-1" style="height: 5px; width: 35px; border-radius: 3px; background: #e2e8f0;">
                                                        <div class="progress-bar bg-indigo" role="progressbar" style="background: #6366f1; width: <?php echo min(100, $u->win_rate); ?>%;"></div>
                                                    </div>
                                                    <span class="font-11 font-weight-bold text-dark">%<?php echo $u->win_rate; ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted font-13">Seçilen dönemde personel teklif verisi bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. EN YÜKSEK TUTARLI TEKLİFLER & SON OLUŞTURULAN TEKLİFLER -->
    <div class="row">
        <!-- Son Oluşturulan Teklifler -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                    <div>
                        <h5 class="font-weight-bold mb-1 font-16">
                            <i class="fa fa-history text-muted mr-1"></i> Son Oluşturulan Teklifler
                        </h5>
                        <p class="text-muted font-12 mb-0">Sistemde kaydedilen en son 10 teklif kaydı</p>
                    </div>
                    <a href="index.php?p=offers/list" class="btn btn-xs btn-primary py-1 px-3 font-12" style="border-radius: 6px;">
                        Tüm Teklifleri Görüntüle <i class="fa fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-no-scroll">
                        <table class="table table-modern table-hover m-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Teklif No</th>
                                    <th style="width: 24%;">Firma Adı</th>
                                    <th style="width: 20%;">Konu / Başlık</th>
                                    <th style="width: 14%;">Oluşturan</th>
                                    <th style="width: 10%;">Tarih</th>
                                    <th style="width: 12%;" class="text-right">Tutar (TL)</th>
                                    <th style="width: 10%;" class="text-center">Durum</th>
                                    <th style="width: 80px;" class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentOffers)) : ?>
                                    <?php foreach ($recentOffers as $ro) : ?>
                                        <tr>
                                            <td>
                                                <a href="index.php?p=offers/offer-manage&id=<?php echo $ro->id; ?>" class="font-weight-bold text-primary font-12 cell-ellipsis" title="<?php echo htmlspecialchars($ro->offerNumber, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($ro->offerNumber, ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis font-weight-bold text-dark font-12" title="<?php echo htmlspecialchars($ro->company_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($ro->company_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis text-muted font-12" title="<?php echo htmlspecialchars($ro->offer_subject ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($ro->offer_subject ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="cell-ellipsis font-11 text-dark" title="<?php echo htmlspecialchars($ro->creator_name ?: 'Bilinmiyor', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fa fa-user mr-1 text-muted"></i><?php echo htmlspecialchars($ro->creator_name ?: 'Bilinmiyor', ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </td>
                                            <td class="font-11 text-muted">
                                                <?php echo !empty($ro->created_at) ? date('d.m.Y', strtotime($ro->created_at)) : '-'; ?>
                                            </td>
                                            <td class="text-right font-weight-bold text-dark font-12">
                                                <?php echo formatCurrencyTR($ro->amount); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ((int)$ro->statu === 2) : ?>
                                                    <span class="badge badge-success px-2 py-1 font-10" style="border-radius: 4px;"><i class="fa fa-check mr-1"></i>Onay</span>
                                                <?php else : ?>
                                                    <span class="badge badge-warning px-2 py-1 font-10" style="border-radius: 4px;"><i class="fa fa-clock-o mr-1"></i>Bekliyor</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="index.php?p=offers/offer-manage&id=<?php echo $ro->id; ?>" class="btn btn-outline-primary btn-xs py-1 px-2" title="Düzenle">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                    <a href="index.php?p=offer-view&id=<?php echo $ro->id; ?>" target="_blank" class="btn btn-outline-secondary btn-xs py-1 px-2" title="Görüntüle">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted font-13">Henüz teklif kaydı bulunmamaktadır.</td>
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
    // 1. Aylık Teklif & Ciro Trend Grafiği
    var monthlyData = <?php echo json_encode($monthlyTrends, JSON_UNESCAPED_UNICODE); ?>;
    
    var trendOptions = {
        series: [{
            name: 'Toplam Teklif Adedi',
            type: 'column',
            data: monthlyData.total_offers
        }, {
            name: 'Kazanılan Teklif Adedi',
            type: 'column',
            data: monthlyData.won_offers
        }, {
            name: 'Toplam Teklif Hacmi (₺)',
            type: 'line',
            data: monthlyData.total_amount
        }],
        chart: {
            height: 330,
            type: 'line',
            stacked: false,
            toolbar: {
                show: false
            },
            fontFamily: 'Geist, sans-serif'
        },
        colors: ['#4f46e5', '#10b981', '#f59e0b'],
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
        labels: monthlyData.categories,
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
                    text: 'Teklif Adedi',
                    style: { color: '#4f46e5' }
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
                    text: 'Toplam Hacim (₺)',
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
    var wonCount = <?php echo (int)$summary->won_count; ?>;
    var pendingCount = <?php echo (int)$summary->pending_count; ?>;

    var donutOptions = {
        series: [wonCount, pendingCount],
        chart: {
            type: 'donut',
            height: 250,
            fontFamily: 'Geist, sans-serif'
        },
        labels: ['Tamamlandı / Onaylandı', 'Bekliyor'],
        colors: ['#10b981', '#f59e0b'],
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Toplam Teklif',
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
                    var total = wonCount + pendingCount;
                    var pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                    return val + " Teklif (" + pct + "%)";
                }
            }
        }
    };

    var donutChart = new ApexCharts(document.querySelector("#chart-status-donut"), donutOptions);
    donutChart.render();

    // 3. Flatpickr Tarih Seçici Başlatma
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#offer_start_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            altInputClass: "form-control form-control-sm flatpickr-custom-input border-left-0",
            locale: "tr",
            allowInput: true
        });
        flatpickr("#offer_end_date", {
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
