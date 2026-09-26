<?php

use App\Model\ServiceModel;
use App\Model\ActivityLogModel;
use App\Model\DashboardLayoutModel;
use App\Helper\Date;
use App\Dashboard\DashboardWidgetRegistry;

$userId = (int)($_SESSION['lid'] ?? ($_SESSION['id'] ?? (function_exists('sesset') ? sesset("id") : 0)));
$services = new ServiceModel();
$dashboardModel = new DashboardLayoutModel();

$canViewHomeFinancialData = permtrue('home_financial_data_view');
$canViewSystemLogs = in_array($userId, [1, 12]);
$canViewOffers = permtrue('offerview');

// Widget Kataloğu ve Kullanıcı Yerleşim Düzeni
$widgetCatalog = $dashboardModel->getWidgetCatalog($userId);
$effectiveLayout = $dashboardModel->getEffectiveLayout($userId);
$dashboardWidgetData = (new DashboardWidgetRegistry($ac))->data($userId);

$layoutMap = [];
foreach ($effectiveLayout as $wItem) {
    $layoutMap[$wItem['id']] = $wItem;
}

$latestActivities = [];
$latestLogins = [];
if ($canViewSystemLogs) {
    $activityLogModel = new ActivityLogModel();
    $latestActivities = $activityLogModel->getLatestActivities(10);
    $latestLogins = $activityLogModel->getLatestLogins(10);
}

// 1. KPI Metrikleri - Servisler
$waitingServicesQuery = $ac->prepare('SELECT COUNT(*) FROM projects WHERE pstatu = ?');
$waitingServicesQuery->execute([15]);
$waitingCount = (int) $waitingServicesQuery->fetchColumn();

$inProgressServicesQuery = $ac->prepare('SELECT COUNT(*) FROM projects WHERE pstatu = ?');
$inProgressServicesQuery->execute([16]);
$inProgressCount = (int) $inProgressServicesQuery->fetchColumn();

$activeServices = $waitingCount + $inProgressCount;

$completedServicesQuery = $ac->prepare('SELECT COUNT(*) FROM projects WHERE pstatu = ?');
$completedServicesQuery->execute([17]);
$completedCount = (int) $completedServicesQuery->fetchColumn();

$totalServicesQuery = $ac->query('SELECT COUNT(*) FROM projects');
$totalServices = (int) $totalServicesQuery->fetchColumn();
$serviceCompRate = $totalServices > 0 ? round(($completedCount / $totalServices) * 100, 1) : 0;

// 2. KPI Metrikleri - Teklifler
$pendingOffersQuery = $ac->query('SELECT COUNT(*) as cnt, COALESCE(SUM(total_price), 0) as total FROM offers WHERE statu = 1');
$pendingOffersData = $pendingOffersQuery->fetch(PDO::FETCH_ASSOC);
$pendingOffersCount = (int) ($pendingOffersData['cnt'] ?? 0);
$pendingOffersSum = (float) ($pendingOffersData['total'] ?? 0);

$wonOffersQuery = $ac->query('SELECT COUNT(*) as cnt, COALESCE(SUM(total_price), 0) as total FROM offers WHERE statu = 2');
$wonOffersData = $wonOffersQuery->fetch(PDO::FETCH_ASSOC);
$wonOffersCount = (int) ($wonOffersData['cnt'] ?? 0);
$wonOffersSum = (float) ($wonOffersData['total'] ?? 0);

$totalOffers = $pendingOffersCount + $wonOffersCount;
$offerWinRate = $totalOffers > 0 ? round(($wonOffersCount / $totalOffers) * 100, 1) : 0;

// 3. KPI Metrikleri - Müşteriler & Görevler
$customersQuery = $ac->query('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL');
$activeCustomers = (int) $customersQuery->fetchColumn();

$todoQuery = $ac->prepare('SELECT COUNT(*) FROM todolist WHERE okey = ?');
$todoQuery->execute([0]);
$openTasksCount = (int) $todoQuery->fetchColumn();

// Türkçe Gün & Ay İsimleri
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
$loggedUser = htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı', ENT_QUOTES, 'UTF-8');

// 15 Günlük Tek Sıra Kolon Verisi Hazırlığı
$todayDate = date('Y-m-d');
$startDate = new DateTime();
$startDate->modify('-3 days');

$planningDays = [];
$totalPeriodServices = 0;

$iter = clone $startDate;
for ($i = 0; $i < 15; $i++) {
    $dateStr = $iter->format('Y-m-d');
    $dayEng = $iter->format('l');
    $dayTr = $turkishDays[$dayEng] ?? $dayEng;
    $displayDate = $iter->format('d.m.Y');
    $isToday = ($dateStr === $todayDate);

    $dailyServices = $services->getDailyServiceList($dateStr);
    $serviceCount = count($dailyServices);
    $totalPeriodServices += $serviceCount;

    $planningDays[] = [
        'dateStr' => $dateStr,
        'displayDate' => $displayDate,
        'dayTr' => $dayTr,
        'isToday' => $isToday,
        'services' => $dailyServices,
        'count' => $serviceCount
    ];

    $iter->modify('+1 day');
}

// Aylık Takvim Verisi Hazırlığı (Geçerli Ay)
$currentCalYear = (int) date('Y');
$currentCalMonth = (int) date('n');
$currentCalMonthName = $turkishMonths[$currentCalMonth] ?? date('F');
$daysInMonth = (int) date('t', strtotime("$currentCalYear-$currentCalMonth-01"));
$firstDayWeekday = (int) date('N', strtotime("$currentCalYear-$currentCalMonth-01"));

$monthDaysData = [];
$monthDaysJson = [];
$totalMonthServices = 0;
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dateStr = sprintf('%04d-%02d-%02d', $currentCalYear, $currentCalMonth, $d);
    $dServices = $services->getDailyServiceList($dateStr);
    $cnt = count($dServices);
    $totalMonthServices += $cnt;

    $clientMonthServices = [];
    foreach ($dServices as $ds) {
        $statusObj = $services->getServiceBackColour($ds->pstatu);
        $statusColor = !empty($statusObj->colour) ? $statusObj->colour : '#3b82f6';
        $statusTitle = !empty($statusObj->title) ? $statusObj->title : 'Durum Belirtilmemiş';

        $authorNames = [];
        if (!empty($ds->pauthors)) {
            $authorList = explode('|', $ds->pauthors);
            foreach ($authorList as $authId) {
                $name = getUsername($authId);
                if (!empty($name)) {
                    $authorNames[] = $name;
                }
            }
        }

        $clientMonthServices[] = [
            'id' => (int) $ds->id,
            'service_number' => (string) ($ds->service_number ?? ''),
            'title' => (string) ($ds->title ?? ''),
            'company' => (string) ($ds->firma_adi ?? 'Firma Belirtilmemiş'),
            'authors' => implode(', ', $authorNames),
            'status_color' => $statusColor,
            'status_title' => $statusTitle,
        ];
    }

    $dayEng = date('l', strtotime($dateStr));
    $dayTr = $turkishDays[$dayEng] ?? $dayEng;
    $displayDate = date('d.m.Y', strtotime($dateStr));

    $monthDaysData[$d] = [
        'dayNum' => $d,
        'dateStr' => $dateStr,
        'displayDate' => $displayDate,
        'dayTr' => $dayTr,
        'isToday' => ($dateStr === $todayDate),
        'services' => $dServices,
        'count' => $cnt
    ];

    $monthDaysJson[$dateStr] = [
        'dayNum' => $d,
        'dateStr' => $dateStr,
        'displayDate' => $displayDate,
        'dayTr' => $dayTr,
        'isToday' => ($dateStr === $todayDate),
        'count' => $cnt,
        'services' => $clientMonthServices
    ];
}
?>

<!-- GridStack CSS -->
<link rel="stylesheet" href="src/plugins/gridstack/gridstack.min.css">

<style>
/* Dashboard Toolbar & Kontrol Çubuğu */
.crm-dashboard-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 18px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}
.crm-toolbar-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.btn-dashboard-control {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 13px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    transition: all 0.2s ease;
    cursor: pointer;
    line-height: 1.3;
}
.btn-dashboard-control:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}
.btn-dashboard-control.active {
    background: #4f46e5;
    border-color: #4f46e5;
    color: #ffffff;
}
.btn-dashboard-control.btn-reset-layout:hover {
    background: #fff1f2;
    border-color: #fecdd3;
    color: #e11d48;
}
.crm-sync-indicator {
    font-size: 11px;
    font-weight: 600;
    color: #10b981;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.crm-sync-indicator.is-saving {
    opacity: 1;
    color: #f59e0b;
}
.crm-sync-indicator.is-saved {
    opacity: 1;
    color: #10b981;
}
.crm-dashboard-hero {
    margin-bottom: 6px !important;
    overflow: visible !important;
    z-index: 30;
}
.crm-dashboard-hero::before {
    display: none !important;
}
.crm-hero-widget-menu {
    position: relative;
    z-index: 20;
}
.btn-dashboard-widget-menu {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 30px;
    padding: 5px 11px;
    border: 1px solid #cbd5e1;
    border-radius: 20px;
    background: #ffffff;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}
.btn-dashboard-widget-menu:hover,
.btn-dashboard-widget-menu:focus {
    border-color: #94a3b8;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
}
.crm-widget-actions-menu {
    min-width: 220px;
    padding: 7px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
}
.crm-widget-actions-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 9px;
    width: 100%;
    padding: 8px 10px;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: #334155;
    font-size: 12px;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
}
.crm-widget-actions-menu .dropdown-item:hover {
    background: #f1f5f9;
}
.crm-widget-actions-menu .dropdown-item.active {
    background: #eef2ff;
    color: #4338ca;
}
.crm-widget-actions-menu .btn-reset-layout:hover {
    background: #fff1f2;
    color: #e11d48;
}
.dark-mode .btn-dashboard-widget-menu,
.dark-mode .crm-widget-actions-menu {
    background: #282828;
    border-color: #3f3f46;
    color: #e2e8f0;
}
.dark-mode .btn-dashboard-widget-menu:hover,
.dark-mode .crm-widget-actions-menu .dropdown-item:hover {
    background: #303030;
    color: #ffffff;
}
.dark-mode .crm-widget-actions-menu .dropdown-item {
    color: #d7dde6;
}
.dark-mode .crm-widget-actions-menu .dropdown-item.active {
    background: rgba(79, 70, 229, 0.22);
    color: #c7d2fe;
}

/* GridStack Özelleştirmeleri & Dış Kenar Standardı */
.crm-dashboard-wrapper {
    overflow-x: hidden !important;
}
.grid-stack {
    margin: 0 -6px !important;
    width: calc(100% + 12px) !important;
    padding: 0 !important;
    box-sizing: border-box !important;
}
.grid-stack.crm-grid-preparing {
    visibility: hidden;
    opacity: 0;
    animation: crm-grid-failsafe 0s 2s forwards;
}
.grid-stack.crm-grid-ready {
    visibility: visible;
    opacity: 1;
    transition: opacity 0.12s ease-out;
}
@keyframes crm-grid-failsafe {
    to { visibility: visible; opacity: 1; }
}
.grid-stack-item {
    box-sizing: border-box !important;
}
.grid-stack-item-content {
    background: transparent !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    border-radius: 14px !important;
    box-sizing: border-box !important;
}
.grid-stack-item-content > .crm-card {
    height: 100% !important;
    width: 100% !important;
    margin: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    border-radius: 14px !important;
    overflow: hidden !important;
    box-sizing: border-box !important;
}
.grid-stack-item-content > .crm-card > .crm-card-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow-y: hidden !important;
    box-sizing: border-box !important;
}
.grid-stack-item-content > .crm-kpi-grid {
    height: 100% !important;
    margin: 0 !important;
    width: calc(100% - 12px) !important;
    max-width: calc(100% - 12px) !important;
}

/* Widget Başlık Butonları (Drag Handle & Close) */
.crm-widget-controls {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
}
.crm-widget-drag-handle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 6px;
    color: #94a3b8;
    cursor: move;
    transition: all 0.15s ease;
}
.crm-widget-drag-handle:hover {
    background: #f1f5f9;
    color: #334155;
}
.btn-widget-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
}
.btn-widget-close:hover {
    background: #fee2e2;
    color: #ef4444;
}

/* Resize Tutamacı Stili (Sadece Sağ-Alt Köşe) */
.grid-stack > .grid-stack-item > .ui-resizable-se {
    right: 12px;
    bottom: 12px;
    width: 14px;
    height: 14px;
    background-image: radial-gradient(circle, #94a3b8 1.5px, transparent 1.5px);
    background-size: 4px 4px;
    opacity: 0.5;
    transition: opacity 0.2s ease;
    z-index: 100;
}
.grid-stack > .grid-stack-item:hover > .ui-resizable-se {
    opacity: 1;
}
.grid-stack > .grid-stack-item > .ui-resizable-sw,
.grid-stack > .grid-stack-item > .ui-resizable-w,
.grid-stack > .grid-stack-item > .ui-resizable-nw,
.grid-stack > .grid-stack-item > .ui-resizable-n,
.grid-stack > .grid-stack-item > .ui-resizable-ne {
    display: none !important;
}
.grid-stack > .grid-stack-item > .ui-resizable-e,
.grid-stack > .grid-stack-item > .ui-resizable-s {
    display: block !important;
}

/* Grid Kilitli İken Tutamaçları Gizle */
.grid-stack.is-locked .crm-widget-drag-handle {
    display: none !important;
}
.grid-stack.is-locked .ui-resizable-handle {
    display: none !important;
}

/* KPI Kartları Grid İçinde Otomatik Sığdırma */
.crm-kpi-grid.widget-kpi-inner {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 12px !important;
    height: 100% !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
}
@media (max-width: 1200px) {
    .crm-kpi-grid.widget-kpi-inner {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (max-width: 576px) {
    .crm-kpi-grid.widget-kpi-inner {
        grid-template-columns: 1fr !important;
    }
}
.crm-kpi-grid.widget-kpi-inner .crm-kpi-card {
    height: 100% !important;
    min-width: 0 !important;
    margin: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    box-sizing: border-box !important;
}

/* Modal Widget Listesi Stilleri */
.crm-widget-catalog-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.crm-widget-catalog-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    transition: all 0.2s ease;
}
.crm-widget-catalog-item:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.crm-widget-cat-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

/* Özel Switch Toggle */
.crm-toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    margin: 0;
}
.crm-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.crm-toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: #cbd5e1;
    transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 24px;
}
.crm-toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.crm-toggle-switch input:checked + .crm-toggle-slider {
    background-color: #10b981;
}
.crm-toggle-switch input:checked + .crm-toggle-slider:before {
    transform: translateX(20px);
}
.crm-provider-card { height: 100%; display: flex; flex-direction: column; background: #fff; border: 1px solid #e2e8f0; border-radius: 13px; overflow: hidden; }
.crm-provider-header { min-height: 58px; padding: 12px 16px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.crm-provider-title { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; }
.crm-provider-body { padding: 13px 16px; flex: 1; min-height: 0; overflow: hidden; }
.crm-provider-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 10px; }
.crm-provider-metric { padding: 9px 10px; border-radius: 9px; background: #f8fafc; border: 1px solid #eef2f7; }
.crm-provider-metric strong { display: block; font-size: 19px; line-height: 1.1; color: #0f172a; }
.crm-provider-metric span { font-size: 10px; color: #64748b; }
.crm-provider-list { display: flex; flex-direction: column; gap: 6px; }
.crm-provider-list a { min-width: 0; display: flex; align-items: center; gap: 8px; padding: 7px 9px; color: #334155; background: #f8fafc; border-radius: 8px; font-size: 11px; text-decoration: none; }
.crm-provider-list a span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.crm-provider-empty { color: #94a3b8; font-size: 12px; text-align: center; padding: 22px 8px; }
.crm-rate-row { display: grid; grid-template-columns: 1fr 76px 76px; gap: 8px; align-items: center; padding: 9px 0; border-bottom: 1px solid #eef2f7; font-size: 12px; }
.crm-rate-row:last-child { border-bottom: 0; }
.crm-rate-value { text-align: right; font-weight: 700; color: #0f172a; }
.dark-mode .crm-provider-card { background: #172033; border-color: #2b3952; }
.dark-mode .crm-provider-header { border-color: #2b3952; }
.dark-mode .crm-provider-title, .dark-mode .crm-provider-metric strong, .dark-mode .crm-rate-value { color: #e5edf8; }
.dark-mode .crm-provider-metric, .dark-mode .crm-provider-list a { background: #111827; border-color: #2b3952; color: #cbd5e1; }
</style>

<div class="main-container" id="content">
	<div id="maincontainer" class="content crm-dashboard-wrapper animate-fade-in">
		
		<!-- 1. CRM HERO / KARŞILAMA VE HIZLI AKSİYON ÇUBUĞU -->
		<div class="crm-hero-banner crm-dashboard-hero">
			<div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
				<div class="crm-hero-content">
					<div class="d-flex align-items-center flex-wrap mb-2" style="gap: 8px;">
						<span class="crm-date-chip">
							<i class="fa fa-calendar-o"></i> <?php echo $curDateFormatted; ?>
						</span>
						<div class="dropdown crm-hero-widget-menu">
							<button type="button" class="btn-dashboard-widget-menu dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Widget yerleşim işlemleri">
								<i class="fa fa-th-large text-primary"></i> Widget İşlemleri
							</button>
							<div class="dropdown-menu crm-widget-actions-menu">
								<button type="button" class="dropdown-item" id="btnOpenWidgetManager" title="Kartları göster veya gizle">
									<i class="fa fa-puzzle-piece text-primary"></i> Kartları Yönet
								</button>
								<button type="button" class="dropdown-item active" id="btnToggleEditMode" title="Düzenleme modunu aç/kapat">
									<i class="fa fa-pencil"></i> <span id="btnEditModeText">Düzenleme Modu</span>
								</button>
								<div class="dropdown-divider"></div>
								<button type="button" class="dropdown-item btn-reset-layout" id="btnResetDashboardLayout" title="Tüm kartları varsayılan yerleşime döndür">
									<i class="fa fa-refresh text-danger"></i> Varsayılana Dön
								</button>
							</div>
						</div>
						<span class="crm-sync-indicator" id="crmSyncIndicator"><i class="fa fa-check-circle"></i> Kaydedildi</span>
					</div>
					<h2 class="crm-hero-title">Hoş Geldiniz, <?php echo $loggedUser; ?> 👋</h2>
					<p class="crm-hero-subtitle m-0">Operasyonel süreçler, servis takibi ve aktif tekliflerinize genel bakış.</p>
				</div>
				<div class="crm-quick-actions-square-list">
					<?php if (permtrue('offeradd')) : ?>
						<a href="index.php?p=offers/offer-manage" class="crm-quick-square-btn" title="Yeni Teklif">
							<div class="crm-quick-square-icon"><i class="fa fa-file-text-o"></i></div>
							<span class="crm-quick-square-label">Yeni Teklif</span>
						</a>
					<?php endif; ?>
					<?php if (permtrue('serviceAdd')) : ?>
						<a href="index.php?p=service/manage" class="crm-quick-square-btn" title="Yeni Servis">
							<div class="crm-quick-square-icon"><i class="fa fa-plus-circle"></i></div>
							<span class="crm-quick-square-label">Yeni Servis</span>
						</a>
					<?php endif; ?>
					<?php if (permtrue('customeradd')) : ?>
						<a href="index.php?p=customers/manage" class="crm-quick-square-btn" title="Yeni Firma">
							<div class="crm-quick-square-icon"><i class="fa fa-building-o"></i></div>
							<span class="crm-quick-square-label">Yeni Firma</span>
						</a>
					<?php endif; ?>
					<?php if (permtrue('todoadd')) : ?>
						<a href="index.php?p=task-new" class="crm-quick-square-btn" title="Görev Ekle">
							<div class="crm-quick-square-icon"><i class="fa fa-check-square-o"></i></div>
							<span class="crm-quick-square-label">Görev Ekle</span>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- 2. GRIDSTACK ANA IZGARA ALANI -->
		<div class="grid-stack crm-grid-preparing" id="crmDashboardGrid" aria-busy="true">

			<!-- WIDGET 1: KPI SUMMARY CARDS -->
			<?php 
			$wKpi = $layoutMap['widget_kpi_summary'] ?? ['w' => 12, 'h' => 2, 'x' => 0, 'y' => 0, 'visible' => true];
			$isKpiVisible = !empty($wKpi['visible']);
			?>
			<div class="grid-stack-item <?php echo $isKpiVisible ? '' : 'd-none'; ?>" 
				 id="grid_item_widget_kpi_summary"
				 gs-id="widget_kpi_summary"
				 gs-x="<?php echo (int)($wKpi['x'] ?? 0); ?>"
				 gs-y="<?php echo (int)($wKpi['y'] ?? 0); ?>"
				 gs-w="<?php echo (int)($wKpi['w'] ?? 12); ?>"
				 gs-h="<?php echo (int)($wKpi['h'] ?? 2); ?>"
				 gs-min-w="4"
				 gs-min-h="2">
				<div class="grid-stack-item-content position-relative">
					<div class="crm-kpi-grid widget-kpi-inner">
						<!-- Devam Eden Servisler -->
						<div class="crm-kpi-card position-relative">
							<div class="crm-kpi-header">
								<div>
									<span class="crm-kpi-label">Aktif Servisler</span>
									<div class="crm-kpi-value"><?php echo $activeServices; ?></div>
								</div>
								<div class="crm-kpi-icon icon-blue">
									<i class="fa fa-wrench"></i>
								</div>
							</div>
							<div class="crm-kpi-footer">
								<div class="d-flex align-items-center" style="gap: 4px;">
									<span class="crm-badge-soft soft-amber">Bekleyen: <?php echo $waitingCount; ?></span>
									<span class="crm-badge-soft soft-blue">Sahada: <?php echo $inProgressCount; ?></span>
								</div>
								<a href="index.php?p=service/list" class="crm-card-link">Tümü <i class="fa fa-angle-right"></i></a>
							</div>
						</div>

						<!-- Bekleyen Teklifler -->
						<div class="crm-kpi-card position-relative">
							<div class="crm-kpi-header">
								<div>
									<span class="crm-kpi-label">Bekleyen Teklifler</span>
									<div class="crm-kpi-value"><?php echo $pendingOffersCount; ?></div>
								</div>
								<div class="crm-kpi-icon icon-amber">
									<i class="fa fa-file-text-o"></i>
								</div>
							</div>
							<div class="crm-kpi-footer">
								<span class="weight-600 text-dark" style="font-size: 11px;">
									Hacim:
									<?php if ($canViewHomeFinancialData) : ?>
										<span class="text-primary"><?php echo tlFormat($pendingOffersSum); ?></span>
									<?php else : ?>
										<span class="crm-financial-hidden" title="Bu finansal veriyi görüntüleme yetkiniz bulunmuyor">
											<i class="fa fa-eye-slash" aria-hidden="true"></i>
											<span class="sr-only">Finansal veri gizli</span>
										</span>
									<?php endif; ?>
								</span>
								<span class="crm-badge-soft soft-amber">Pipeline</span>
							</div>
						</div>

						<!-- Kazanılan Teklifler & Ciro -->
						<div class="crm-kpi-card position-relative">
							<div class="crm-kpi-header">
								<div>
									<span class="crm-kpi-label">Kazanılan Teklifler</span>
									<div class="crm-kpi-value"><?php echo $wonOffersCount; ?></div>
								</div>
								<div class="crm-kpi-icon icon-emerald">
									<i class="fa fa-trophy"></i>
								</div>
							</div>
							<div class="crm-kpi-footer">
								<span class="weight-600 text-dark" style="font-size: 11px;">
									Ciro:
									<?php if ($canViewHomeFinancialData) : ?>
										<span class="text-success"><?php echo tlFormat($wonOffersSum); ?></span>
									<?php else : ?>
										<span class="crm-financial-hidden" title="Bu finansal veriyi görüntüleme yetkiniz bulunmuyor">
											<i class="fa fa-eye-slash" aria-hidden="true"></i>
											<span class="sr-only">Finansal veri gizli</span>
										</span>
									<?php endif; ?>
								</span>
								<span class="crm-badge-soft soft-emerald">%<?php echo $offerWinRate; ?> Başarı</span>
							</div>
						</div>

						<!-- Müşteri Portföyü & Görevler -->
						<div class="crm-kpi-card position-relative">
							<div class="crm-kpi-header">
								<div>
									<span class="crm-kpi-label">Kayıtlı Portföy</span>
									<div class="crm-kpi-value"><?php echo number_format($activeCustomers, 0, ',', '.'); ?></div>
								</div>
								<div class="crm-kpi-icon icon-purple">
									<i class="fa fa-building-o"></i>
								</div>
							</div>
							<div class="crm-kpi-footer">
								<span class="weight-600 text-dark" style="font-size: 11px;">
									<i class="fa fa-tasks text-muted mr-1"></i> <?php echo $openTasksCount; ?> Bekleyen Görev
								</span>
								<span class="crm-badge-soft soft-purple">Aktif CRM</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- WIDGET 2: SERVİS PLANLAMA PANOSU -->
			<?php 
			$wSvc = $layoutMap['widget_service_board'] ?? ['w' => 12, 'h' => 4, 'x' => 0, 'y' => 2, 'visible' => true];
			$isSvcVisible = !empty($wSvc['visible']);
			?>
			<div class="grid-stack-item <?php echo $isSvcVisible ? '' : 'd-none'; ?>" 
				 id="grid_item_widget_service_board"
				 gs-id="widget_service_board"
				 gs-x="<?php echo (int)($wSvc['x'] ?? 0); ?>"
				 gs-y="<?php echo (int)($wSvc['y'] ?? 2); ?>"
				 gs-w="<?php echo (int)($wSvc['w'] ?? 12); ?>"
				 gs-h="<?php echo (int)($wSvc['h'] ?? 4); ?>"
				 gs-min-w="6"
				 gs-min-h="4">
				<div class="grid-stack-item-content">
					<div class="crm-card position-relative">
						<div class="crm-card-header flex-wrap" style="gap: 12px;">
							<div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
								<span class="crm-widget-drag-handle" title="Taşımak için sürükleyin"><i class="fa fa-arrows"></i></span>
								<h3 class="crm-card-title m-0">
									<i class="fa fa-calendar text-primary"></i> Servis Planlama Panosu
								</h3>
								<span class="crm-badge-soft soft-blue font-12" id="crmHeaderCountBadge">
									<?php echo $totalPeriodServices; ?> Servis
								</span>
							</div>
							<div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
								<!-- Görünüm Seçici -->
								<div class="crm-view-switcher-group" role="group" aria-label="Görünüm Seçimi">
									<button type="button" class="btn-view-toggle active" id="btnViewSingleRow" title="15 Günlük Tek Sıra Kolon Görünümü">
										<i class="fa fa-columns"></i> 15 Günlük Liste
									</button>
									<button type="button" class="btn-view-toggle" id="btnViewMonthCal" title="Aylık Takvim Görünümü">
										<i class="fa fa-calendar-o"></i> Aylık Takvim
									</button>
								</div>

								<!-- Servisleri Genişlet Butonu -->
								<button type="button" class="btn-toggle-expand d-none" id="btnToggleExpandServices" title="Tüm servisleri açık göster / kompakt takvim moduna dön">
									<i class="fa fa-arrows-v"></i> <span id="btnExpandText">Servisleri Genişlet</span>
								</button>

								<!-- Lejant -->
								<div class="d-none d-md-flex align-items-center" style="gap: 6px; font-size: 11px;">
									<span class="crm-badge-soft soft-amber"><i class="fa fa-circle"></i> Bekliyor</span>
									<span class="crm-badge-soft soft-blue"><i class="fa fa-circle"></i> Çalışıyor</span>
									<span class="crm-badge-soft soft-emerald"><i class="fa fa-circle"></i> Tamamlandı</span>
								</div>

								<a href="index.php?p=service/list" class="crm-card-link ml-1">Servis Listesi <i class="fa fa-arrow-right"></i></a>

								<div class="crm-widget-controls">
									<button type="button" class="btn-widget-close" data-widget-id="widget_service_board" title="Bu kartı gizle">
										<i class="fa fa-times"></i>
									</button>
								</div>
							</div>
						</div>
						<div class="crm-card-body p-3 position-relative">
							<!-- GÖRÜNÜM 1: 15 GÜNLÜK TEK SIRA KOLON LİSTESİ -->
							<div id="crmSingleRowWrapper" class="crm-view-container position-relative">
								<button type="button" class="crm-timeline-nav-btn btn-nav-left" id="crmTimelinePrev" title="Önceki Günler" aria-label="Önceki Günler">
									<i class="fa fa-chevron-left"></i>
								</button>

								<div class="crm-timeline-wrapper" id="crmTimelineScroll">
									<div class="crm-timeline-grid">
										<?php foreach ($planningDays as $day) : 
											$currentDateStr = $day['dateStr'];
											$dailyServices = $day['services'];
											$isToday = $day['isToday'];
										?>
											<div class="crm-day-column <?php echo $isToday ? 'is-today' : ''; ?>" data-column-date="<?php echo $currentDateStr; ?>" id="crmCol_<?php echo $currentDateStr; ?>">
												<div class="crm-day-header">
													<div>
														<div class="crm-day-title">
															<?php echo $day['dayTr']; ?>
															<?php if ($isToday) : ?>
																<span class="crm-badge-soft soft-blue ml-1" style="font-size: 9px; padding: 1px 5px;">Bugün</span>
															<?php endif; ?>
														</div>
														<div class="crm-day-date"><?php echo $day['displayDate']; ?></div>
													</div>
													<span class="badge badge-pill <?php echo count($dailyServices) > 0 ? 'badge-primary' : 'badge-light text-muted'; ?>" style="font-size: 11px;">
														<?php echo count($dailyServices); ?>
													</span>
												</div>
												<div class="crm-day-body">
													<?php if (empty($dailyServices)) : ?>
														<div class="crm-service-empty">
															<i class="fa fa-calendar-check-o d-block mb-1 font-16 text-muted" style="opacity: 0.5;"></i>
															Kayıt Yok
														</div>
													<?php else : ?>
														<?php foreach ($dailyServices as $item) : 
															$statusObj = $services->getServiceBackColour($item->pstatu);
															$statusColor = !empty($statusObj->colour) ? $statusObj->colour : '#3b82f6';
															$statusBg = $statusColor . '14';
															$statusBorder = $statusColor . '35';
															
															$itemDateFormatted = !empty($item->psecond_date) ? (new DateTime($item->psecond_date))->format('Y-m-d') : null;
															$isSecondary = ($itemDateFormatted === $currentDateStr);
														?>
															<a href="index.php?p=service/list&id=<?php echo $item->id; ?>" class="crm-service-item" style="background: <?php echo $statusBg; ?>; border-color: <?php echo $statusBorder; ?>; border-left: 4px solid <?php echo $statusColor; ?>; <?php echo $isSecondary ? 'box-shadow: 0 0 0 1px #8b5cf6;' : ''; ?>">
																<div class="d-flex justify-content-between align-items-center">
																	<span class="crm-service-num" style="color: <?php echo $statusColor; ?>;"><?php echo htmlspecialchars($item->service_number, ENT_QUOTES, 'UTF-8'); ?></span>
																	<?php if (!empty($item->title)) : ?>
																		<span class="crm-badge-soft" style="background: <?php echo $statusColor; ?>22; color: <?php echo $statusColor; ?>; border: 1px solid <?php echo $statusColor; ?>40; font-size: 10px; padding: 1px 4px; max-width: 90px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
																			<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
																		</span>
																	<?php endif; ?>
																</div>
																<div class="crm-service-company" title="<?php echo htmlspecialchars($item->firma_adi, ENT_QUOTES, 'UTF-8'); ?>">
																	<?php echo htmlspecialchars(shorted($item->firma_adi, 28), ENT_QUOTES, 'UTF-8'); ?>
																</div>
																<?php if (!empty($item->pauthors)) : ?>
																	<div class="crm-service-meta">
																		<i class="fa fa-user-o"></i>
																		<?php
																		$authorList = explode('|', $item->pauthors);
																		$authorNames = [];
																		foreach ($authorList as $authId) {
																			$name = getUsername($authId);
																			if (!empty($name)) {
																				$authorNames[] = $name;
																			}
																		}
																		echo htmlspecialchars(shorted(implode(', ', $authorNames), 24), ENT_QUOTES, 'UTF-8');
																		?>
																	</div>
																<?php endif; ?>
															</a>
														<?php endforeach; ?>
													<?php endif; ?>
												</div>
											</div>
										<?php endforeach; ?>
									</div>
								</div>

								<button type="button" class="crm-timeline-nav-btn btn-nav-right" id="crmTimelineNext" title="Sonraki Günler" aria-label="Sonraki Günler">
									<i class="fa fa-chevron-right"></i>
								</button>
							</div>

							<!-- GÖRÜNÜM 2: AYLIK TAKVİM -->
							<div id="crmMonthCalWrapper" class="crm-view-container d-none">
								<div class="crm-month-calendar-layout" id="crmMonthCalLayout">
									<div class="crm-month-cal-sidebar">
										<div class="crm-month-cal-year-circle">(<?php echo $currentCalYear; ?>)</div>
										<div class="crm-month-cal-v-month"><?php echo mb_strtoupper($currentCalMonthName, 'UTF-8'); ?></div>
										<div class="crm-month-cal-side-summary"><?php echo $totalMonthServices; ?> Servis</div>
									</div>

									<div class="crm-month-cal-main">
										<div class="crm-month-cal-header-row">
											<div class="crm-month-cal-header-cell">Pazartesi</div>
											<div class="crm-month-cal-header-cell">Salı</div>
											<div class="crm-month-cal-header-cell">Çarşamba</div>
											<div class="crm-month-cal-header-cell">Perşembe</div>
											<div class="crm-month-cal-header-cell">Cuma</div>
											<div class="crm-month-cal-header-cell">Cumartesi</div>
											<div class="crm-month-cal-header-cell">Pazar</div>
										</div>

										<div class="crm-month-cal-grid">
											<?php
											$emptyLeading = $firstDayWeekday - 1;
											for ($k = 0; $k < $emptyLeading; $k++) {
												echo '<div class="crm-month-cal-cell is-other-month"></div>';
											}

											for ($d = 1; $d <= $daysInMonth; $d++) {
												$dayData = $monthDaysData[$d];
												$dServices = $dayData['services'];
												$isToday = $dayData['isToday'];
												?>
												<div class="crm-month-cal-cell <?php echo $isToday ? 'is-today' : ''; ?>">
													<div class="crm-month-cal-day-num">
														<span class="crm-cal-num-badge"><?php echo $d; ?></span>
														<div class="d-flex align-items-center" style="gap: 4px;">
															<?php if ($isToday) : ?>
																<span class="crm-month-cal-today-pill">Bugün</span>
															<?php endif; ?>
															<?php if (count($dServices) > 0) : ?>
																<span class="crm-cal-count-pill" 
																	  data-date="<?php echo $dayData['dateStr']; ?>" 
																	  data-day-tr="<?php echo $dayData['dayTr']; ?>" 
																	  data-display-date="<?php echo $dayData['displayDate']; ?>" 
																	  title="Bu günün servislerini görüntüle">
																	<i class="fa fa-wrench"></i> <?php echo count($dServices); ?> Servis
																</span>
															<?php endif; ?>
														</div>
													</div>
													<div class="crm-month-cal-services-list">
														<?php if (!empty($dServices)) : ?>
															<?php foreach ($dServices as $sItem) : 
																$statusObj = $services->getServiceBackColour($sItem->pstatu);
																$statusColor = !empty($statusObj->colour) ? $statusObj->colour : '#3b82f6';
																$statusBg = $statusColor . '15';
																$statusBorder = $statusColor . '35';
															?>
																<a href="index.php?p=service/list&id=<?php echo $sItem->id; ?>" 
																   class="crm-month-cal-service-badge" 
																   style="background: <?php echo $statusBg; ?>; border-color: <?php echo $statusBorder; ?>; border-left: 3.5px solid <?php echo $statusColor; ?>;" 
																   title="<?php echo htmlspecialchars($sItem->service_number . ' - ' . $sItem->firma_adi, ENT_QUOTES, 'UTF-8'); ?>">
																	<span class="crm-cal-s-num" style="color: <?php echo $statusColor; ?>;"><?php echo htmlspecialchars($sItem->service_number, ENT_QUOTES, 'UTF-8'); ?></span>
																	<span class="crm-cal-s-company"><?php echo htmlspecialchars(shorted($sItem->firma_adi, 18), ENT_QUOTES, 'UTF-8'); ?></span>
																</a>
															<?php endforeach; ?>
														<?php endif; ?>
													</div>
												</div>
												<?php
											}

											$totalCells = $emptyLeading + $daysInMonth;
											$trailingEmpty = (7 - ($totalCells % 7)) % 7;
											for ($k = 0; $k < $trailingEmpty; $k++) {
												echo '<div class="crm-month-cal-cell is-other-month"></div>';
											}
											?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- SAĞLAYICI TABANLI WIDGET'LAR -->
			<?php
			$providerWidgets = [
				'widget_currency_rates' => ['fallback' => ['x' => 0, 'y' => 6, 'w' => 4, 'h' => 5], 'minW' => 4, 'minH' => 5],
				'widget_today_work' => ['fallback' => ['x' => 4, 'y' => 6, 'w' => 4, 'h' => 5], 'minW' => 4, 'minH' => 5],
				'widget_upcoming_work' => ['fallback' => ['x' => 8, 'y' => 6, 'w' => 4, 'h' => 5], 'minW' => 4, 'minH' => 5],
				'widget_purchase_approvals' => ['fallback' => ['x' => 0, 'y' => 11, 'w' => 12, 'h' => 4], 'minW' => 3, 'minH' => 3],
			];
			foreach ($providerWidgets as $providerId => $providerConfig) :
				if (empty($widgetCatalog[$providerId]['is_allowed'])) { continue; }
				$providerLayout = $layoutMap[$providerId] ?? array_merge($providerConfig['fallback'], ['visible' => true]);
				$providerMeta = $widgetCatalog[$providerId];
				$providerData = $dashboardWidgetData[$providerId] ?? ['available' => false, 'message' => 'Veri bulunamadı.'];
			?>
			<div class="grid-stack-item <?php echo !empty($providerLayout['visible']) ? '' : 'd-none'; ?>" id="grid_item_<?php echo htmlspecialchars($providerId, ENT_QUOTES, 'UTF-8'); ?>" gs-id="<?php echo htmlspecialchars($providerId, ENT_QUOTES, 'UTF-8'); ?>" gs-x="<?php echo (int)$providerLayout['x']; ?>" gs-y="<?php echo (int)$providerLayout['y']; ?>" gs-w="<?php echo (int)$providerLayout['w']; ?>" gs-h="<?php echo (int)$providerLayout['h']; ?>" gs-min-w="<?php echo (int)$providerConfig['minW']; ?>" gs-min-h="<?php echo (int)$providerConfig['minH']; ?>">
				<div class="grid-stack-item-content"><div class="crm-provider-card">
					<div class="crm-provider-header"><div class="d-flex align-items-center" style="gap:8px;min-width:0"><span class="crm-widget-drag-handle" title="Taşımak için sürükleyin"><i class="fa fa-arrows"></i></span><i class="<?php echo htmlspecialchars($providerMeta['icon'], ENT_QUOTES, 'UTF-8'); ?> text-primary"></i><h3 class="crm-provider-title"><?php echo htmlspecialchars($providerMeta['title'], ENT_QUOTES, 'UTF-8'); ?></h3></div><div class="crm-widget-controls"><button type="button" class="btn-widget-close" data-widget-id="<?php echo htmlspecialchars($providerId, ENT_QUOTES, 'UTF-8'); ?>" title="Bu kartı gizle"><i class="fa fa-times"></i></button></div></div>
					<div class="crm-provider-body">
					<?php if (empty($providerData['available'])) : ?>
						<div class="crm-provider-empty"><i class="fa fa-cloud mr-1"></i><?php echo htmlspecialchars($providerData['message'] ?? 'Veri alınamadı.', ENT_QUOTES, 'UTF-8'); ?></div>
					<?php elseif ($providerId === 'widget_currency_rates') : ?>
						<div class="crm-rate-row text-muted"><strong>Kur</strong><span class="text-right">Alış</span><span class="text-right">Satış</span></div>
						<?php foreach (($providerData['rates'] ?? []) as $rate) : ?><div class="crm-rate-row"><span><strong><?php echo htmlspecialchars($rate['code'], ENT_QUOTES, 'UTF-8'); ?></strong> <small class="text-muted"><?php echo htmlspecialchars($rate['name'], ENT_QUOTES, 'UTF-8'); ?></small></span><span class="crm-rate-value"><?php echo htmlspecialchars($rate['buying'], ENT_QUOTES, 'UTF-8'); ?></span><span class="crm-rate-value"><?php echo htmlspecialchars($rate['selling'], ENT_QUOTES, 'UTF-8'); ?></span></div><?php endforeach; ?>
						<div class="text-muted mt-2" style="font-size:10px">TCMB · <?php echo htmlspecialchars($providerData['date'] ?? '-', ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($providerData['cached']) ? ' · önbellek' : ''; ?></div>
					<?php elseif ($providerId === 'widget_today_work') : ?>
						<div class="crm-provider-metrics"><div class="crm-provider-metric"><strong><?php echo (int)$providerData['mission_count']; ?></strong><span>Bugünkü görev</span></div><div class="crm-provider-metric"><strong><?php echo (int)$providerData['service_count']; ?></strong><span>Bugünkü servis</span></div></div>
						<div class="crm-provider-list"><?php foreach (($providerData['items'] ?? []) as $item) : ?><a href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?> text-primary"></i><span><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></span></a><?php endforeach; ?></div><?php if (empty($providerData['items'])) : ?><div class="crm-provider-empty">Bugün için atanmış iş bulunmuyor.</div><?php endif; ?>
					<?php elseif ($providerId === 'widget_upcoming_work') : ?>
						<div class="crm-provider-metrics"><div class="crm-provider-metric"><strong class="text-danger"><?php echo (int)$providerData['overdue_count']; ?></strong><span>Geciken görev</span></div><div class="crm-provider-metric"><strong><?php echo (int)$providerData['upcoming_mission_count'] + (int)$providerData['upcoming_service_count']; ?></strong><span>7 gün içinde</span></div></div>
						<div class="crm-provider-list"><?php foreach (($providerData['overdue'] ?? []) as $item) : ?><a href="index.php?p=view-mission&mid=<?php echo (int)$item['id']; ?>"><i class="fa fa-exclamation-circle text-danger"></i><span><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></span><small class="text-danger ml-auto text-nowrap"><?php echo htmlspecialchars(date('d.m', strtotime($item['due_date'])), ENT_QUOTES, 'UTF-8'); ?></small></a><?php endforeach; ?></div>
					<?php else : ?>
						<div class="crm-provider-metrics"><div class="crm-provider-metric"><strong class="text-warning"><?php echo (int)$providerData['pending']; ?></strong><span>Onay bekliyor</span></div><div class="crm-provider-metric"><strong class="text-primary"><?php echo (int)$providerData['approved']; ?></strong><span>Onaylandı</span></div><div class="crm-provider-metric"><strong class="text-success"><?php echo (int)$providerData['completed']; ?></strong><span>Tamamlandı</span></div><div class="crm-provider-metric"><strong class="text-danger"><?php echo (int)$providerData['rejected']; ?></strong><span>Reddedildi</span></div></div><a href="index.php?p=purchases/dashboard" class="crm-card-link">Satın alma paneline git <i class="fa fa-angle-right"></i></a>
					<?php endif; ?>
					</div>
				</div></div>
			</div>
			<?php endforeach; ?>

			<!-- WIDGET 3: SON TEKLİFLER -->
			<?php if ($canViewOffers) : 
				$wOff = $layoutMap['widget_recent_offers'] ?? ['w' => 6, 'h' => 6, 'x' => 0, 'y' => 6, 'visible' => true];
				$isOffVisible = !empty($wOff['visible']);
			?>
				<div class="grid-stack-item <?php echo $isOffVisible ? '' : 'd-none'; ?>" 
					 id="grid_item_widget_recent_offers"
					 gs-id="widget_recent_offers"
					 gs-x="<?php echo (int)($wOff['x'] ?? 0); ?>"
					 gs-y="<?php echo (int)($wOff['y'] ?? 6); ?>"
					 gs-w="<?php echo (int)($wOff['w'] ?? 6); ?>"
					 gs-h="<?php echo (int)($wOff['h'] ?? 6); ?>"
					 gs-min-w="4"
					 gs-min-h="6">
					<div class="grid-stack-item-content">
						<div class="crm-card h-100 mb-0">
							<div class="crm-card-header">
								<div class="d-flex align-items-center" style="gap: 8px;">
									<span class="crm-widget-drag-handle" title="Taşımak için sürükleyin"><i class="fa fa-arrows"></i></span>
									<h3 class="crm-card-title m-0">
										<i class="fa fa-file-text-o text-success"></i> Son Teklifler
									</h3>
								</div>
								<div class="d-flex align-items-center" style="gap: 10px;">
									<a href="index.php?p=offers/list" class="crm-card-link">Tüm Teklifler <i class="fa fa-arrow-right"></i></a>
									<div class="crm-widget-controls">
										<button type="button" class="btn-widget-close" data-widget-id="widget_recent_offers" title="Bu kartı gizle">
											<i class="fa fa-times"></i>
										</button>
									</div>
								</div>
							</div>
							<div class="crm-card-body p-3">
								<div class="crm-feed-list">
									<?php
									$latestOffers = $ac->prepare('SELECT o.*, c.company as customer_company 
																 FROM offers o 
																 LEFT JOIN customers c ON o.cid = c.id 
																 ORDER BY o.id DESC LIMIT 5');
									$latestOffers->execute();
									$hasOffers = false;
									while ($offer = $latestOffers->fetch(PDO::FETCH_ASSOC)) {
										$hasOffers = true;
										$isWon = ($offer['statu'] == 2);
										?>
										<a href="index.php?p=offers/offer-manage&id=<?php echo $offer['id']; ?>" class="crm-feed-item border-left-accent-emerald">
											<div style="flex: 1; min-width: 0; padding-right: 12px;">
												<div class="crm-feed-title text-truncate">
													<?php echo htmlspecialchars($offer['customer_company'] ?: 'Müşteri Belirtilmemiş', ENT_QUOTES, 'UTF-8'); ?>
												</div>
												<div class="crm-feed-subtitle">
													<span><i class="fa fa-hashtag mr-1"></i><?php echo htmlspecialchars($offer['offerNumber'] ?: 'TK-' . $offer['id'], ENT_QUOTES, 'UTF-8'); ?></span>
													<span>•</span>
													<span><i class="fa fa-calendar mr-1"></i><?php echo htmlspecialchars($offer['reg_date'] ?: date('d.m.Y', strtotime($offer['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8'); ?></span>
												</div>
											</div>
											<div class="text-right flex-shrink-0">
												<div class="crm-feed-amount text-success">
													<?php echo tlFormat($offer['total_price']); ?>
												</div>
												<span class="crm-badge-soft <?php echo $isWon ? 'soft-emerald' : 'soft-amber'; ?> mt-1">
													<?php echo $isWon ? 'Kazanıldı' : 'Bekliyor'; ?>
												</span>
											</div>
										</a>
									<?php } 
									if (!$hasOffers) {
										echo '<div class="text-center py-4 text-muted font-13">Henüz teklif kaydı bulunmuyor.</div>';
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- WIDGET 4: SON EKLENEN SERVİSLER -->
			<?php 
			$wRecSvc = $layoutMap['widget_recent_services'] ?? ['w' => 6, 'h' => 6, 'x' => 6, 'y' => 6, 'visible' => true];
			$isRecSvcVisible = !empty($wRecSvc['visible']);
			?>
			<div class="grid-stack-item <?php echo $isRecSvcVisible ? '' : 'd-none'; ?>" 
				 id="grid_item_widget_recent_services"
				 gs-id="widget_recent_services"
				 gs-x="<?php echo (int)($wRecSvc['x'] ?? 6); ?>"
				 gs-y="<?php echo (int)($wRecSvc['y'] ?? 6); ?>"
				 gs-w="<?php echo (int)($wRecSvc['w'] ?? 6); ?>"
				 gs-h="<?php echo (int)($wRecSvc['h'] ?? 6); ?>"
				 gs-min-w="4"
				 gs-min-h="6">
				<div class="grid-stack-item-content">
					<div class="crm-card h-100 mb-0">
						<div class="crm-card-header">
							<div class="d-flex align-items-center" style="gap: 8px;">
								<span class="crm-widget-drag-handle" title="Taşımak için sürükleyin"><i class="fa fa-arrows"></i></span>
								<h3 class="crm-card-title m-0">
									<i class="fa fa-wrench text-primary"></i> Son Eklenen Servisler
								</h3>
							</div>
							<div class="d-flex align-items-center" style="gap: 10px;">
								<a href="index.php?p=service/list" class="crm-card-link">Tüm Servisler <i class="fa fa-arrow-right"></i></a>
								<div class="crm-widget-controls">
									<button type="button" class="btn-widget-close" data-widget-id="widget_recent_services" title="Bu kartı gizle">
										<i class="fa fa-times"></i>
									</button>
								</div>
							</div>
						</div>
						<div class="crm-card-body p-3">
							<div class="crm-feed-list">
								<?php
								$latestProjects = $ac->prepare('SELECT p.*, c.company as customer_company, u.title as service_type_title, st.title as status_title, st.colour as status_colour
																FROM projects p 
																LEFT JOIN customers c ON p.pcid = c.id 
																LEFT JOIN units u ON p.servicestype = u.id 
																LEFT JOIN units st ON p.pstatu = st.id
																ORDER BY p.id DESC LIMIT 5');
								$latestProjects->execute();
								$hasProjects = false;
								while ($proj = $latestProjects->fetch(PDO::FETCH_ASSOC)) {
									$hasProjects = true;
									$stColour = !empty($proj['status_colour']) ? $proj['status_colour'] : '#3b82f6';
									?>
									<a href="index.php?p=service/list&id=<?php echo $proj['id']; ?>" class="crm-feed-item border-left-accent-blue">
										<div style="flex: 1; min-width: 0; padding-right: 12px;">
											<div class="crm-feed-title text-truncate">
												<?php echo htmlspecialchars($proj['customer_company'] ?: 'Müşteri Belirtilmemiş', ENT_QUOTES, 'UTF-8'); ?>
											</div>
											<div class="crm-feed-subtitle">
												<span class="text-primary weight-600"><?php echo htmlspecialchars($proj['service_number'], ENT_QUOTES, 'UTF-8'); ?></span>
												<span>•</span>
												<span><?php echo htmlspecialchars($proj['service_type_title'] ?: 'Genel Servis', ENT_QUOTES, 'UTF-8'); ?></span>
											</div>
										</div>
										<div class="text-right flex-shrink-0">
											<span class="crm-badge-soft" style="background: <?php echo $stColour; ?>18; color: <?php echo $stColour; ?>; border: 1px solid <?php echo $stColour; ?>30;">
												<?php echo htmlspecialchars($proj['status_title'] ?: 'Durum Belirtilmemiş', ENT_QUOTES, 'UTF-8'); ?>
											</span>
											<div class="text-muted mt-1" style="font-size: 11px;">
												<i class="fa fa-calendar mr-1"></i><?php echo htmlspecialchars($proj['pstart_date'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
											</div>
										</div>
									</a>
								<?php } 
								if (!$hasProjects) {
									echo '<div class="text-center py-4 text-muted font-13">Henüz servis kaydı bulunmuyor.</div>';
								}
								?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- WIDGET 5: EKİP VE OPERASYON DURUMU -->
			<?php 
			$wTeam = $layoutMap['widget_team_status'] ?? ['w' => 6, 'h' => 6, 'x' => 0, 'y' => 12, 'visible' => true];
			$isTeamVisible = !empty($wTeam['visible']);
			?>
			<div class="grid-stack-item <?php echo $isTeamVisible ? '' : 'd-none'; ?>" 
				 id="grid_item_widget_team_status"
				 gs-id="widget_team_status"
				 gs-x="<?php echo (int)($wTeam['x'] ?? 0); ?>"
				 gs-y="<?php echo (int)($wTeam['y'] ?? 12); ?>"
				 gs-w="<?php echo (int)($wTeam['w'] ?? 6); ?>"
				 gs-h="<?php echo (int)($wTeam['h'] ?? 6); ?>"
				 gs-min-w="4"
				 gs-min-h="6">
				<div class="grid-stack-item-content">
					<div class="crm-card h-100 mb-0">
						<div class="crm-card-header">
							<div class="d-flex align-items-center" style="gap: 8px;">
								<span class="crm-widget-drag-handle" title="Taşımak için sürükleyin"><i class="fa fa-arrows"></i></span>
								<h3 class="crm-card-title m-0">
									<i class="fa fa-users text-primary"></i> Ekip ve Operasyon Durumu
								</h3>
							</div>
							<div class="d-flex align-items-center" style="gap: 10px;">
								<a href="index.php?p=all-users" class="crm-card-link">Tüm Ekip <i class="fa fa-arrow-right"></i></a>
								<div class="crm-widget-controls">
									<button type="button" class="btn-widget-close" data-widget-id="widget_team_status" title="Bu kartı gizle">
										<i class="fa fa-times"></i>
									</button>
								</div>
							</div>
						</div>
						<div class="crm-card-body p-0">
							<div class="table-responsive">
								<table class="crm-team-table">
									<thead>
										<tr>
											<th>Kullanıcı / Departman</th>
											<th class="text-center">Aktif / Toplam Görev</th>
											<th class="text-right">Durum</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$teamQuery = $ac->query('SELECT u.id, u.username, u.Unvan, p.p_title as role_title 
																FROM users u 
																LEFT JOIN perms p ON u.permission = p.id 
																WHERE u.statu = 1 
																ORDER BY u.id ASC LIMIT 6');
										while ($member = $teamQuery->fetch(PDO::FETCH_ASSOC)) {
											$mActive = $ac->prepare('SELECT COUNT(*) FROM missions WHERE authors = ? AND statu = 0');
											$mActive->execute([$member['id']]);
											$activeMissions = (int) $mActive->fetchColumn();

											$mTotal = $ac->prepare('SELECT COUNT(*) FROM missions WHERE authors = ?');
											$mTotal->execute([$member['id']]);
											$totalMissions = (int) $mTotal->fetchColumn();

											$initials = strtoupper(mb_substr($member['username'], 0, 2, 'UTF-8'));
											?>
											<tr>
												<td>
													<div class="d-flex align-items-center" style="gap: 10px;">
														<div class="crm-avatar-initials"><?php echo $initials; ?></div>
														<div>
															<div class="weight-600 text-dark" style="font-size: 13px;"><?php echo htmlspecialchars($member['username'], ENT_QUOTES, 'UTF-8'); ?></div>
															<div class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($member['role_title'] ?: ($member['Unvan'] ?: 'Ekip Üyesi'), ENT_QUOTES, 'UTF-8'); ?></div>
														</div>
													</div>
												</td>
												<td class="text-center">
													<span class="badge badge-pill badge-light px-2 py-1 font-12 weight-600" style="background: #f1f5f9; color: #475569;">
														<?php echo $activeMissions . ' / ' . $totalMissions; ?>
													</span>
												</td>
												<td class="text-right">
													<?php if ($activeMissions > 0) : ?>
														<span class="crm-badge-soft soft-amber"><i class="fa fa-clock-o"></i> Görevde</span>
													<?php else : ?>
														<span class="crm-badge-soft soft-emerald"><i class="fa fa-check"></i> Müsait</span>
													<?php endif; ?>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- WIDGET 6: YAPILACAKLAR (TO-DO) LİSTESİ -->
			<?php 
			$wTodo = $layoutMap['widget_todo_list'] ?? ['w' => 6, 'h' => 6, 'x' => 6, 'y' => 12, 'visible' => true];
			$isTodoVisible = !empty($wTodo['visible']);
			?>
			<div class="grid-stack-item <?php echo $isTodoVisible ? '' : 'd-none'; ?>" 
				 id="grid_item_widget_todo_list"
				 gs-id="widget_todo_list"
				 gs-x="<?php echo (int)($wTodo['x'] ?? 6); ?>"
				 gs-y="<?php echo (int)($wTodo['y'] ?? 12); ?>"
				 gs-w="<?php echo (int)($wTodo['w'] ?? 6); ?>"
				 gs-h="<?php echo (int)($wTodo['h'] ?? 6); ?>"
				 gs-min-w="4"
				 gs-min-h="6">
				<div class="grid-stack-item-content">
					<div class="crm-card h-100 mb-0">
						<div class="crm-card-header">
							<div class="d-flex align-items-center" style="gap: 8px;">
								<span class="crm-widget-drag-handle" title="Taşımak için sürükleyin"><i class="fa fa-arrows"></i></span>
								<h3 class="crm-card-title m-0">
									<i class="fa fa-check-square-o text-purple"></i> Yapılacaklar & Hatırlatıcılar
								</h3>
							</div>
							<div class="d-flex align-items-center" style="gap: 10px;">
								<a href="index.php?p=tasks" class="crm-card-link">Tüm Görevler <i class="fa fa-arrow-right"></i></a>
								<div class="crm-widget-controls">
									<button type="button" class="btn-widget-close" data-widget-id="widget_todo_list" title="Bu kartı gizle">
										<i class="fa fa-times"></i>
									</button>
								</div>
							</div>
						</div>
						<div class="crm-card-body p-3">
							<div class="crm-feed-list">
								<?php
								$todosListQuery = $ac->prepare('SELECT t.*, u.username as creator_name 
																FROM todolist t 
																LEFT JOIN users u ON t.creativer = u.id 
																WHERE t.okey = 0 
																ORDER BY t.id DESC LIMIT 5');
								$todosListQuery->execute();
								$hasTodos = false;
								while ($todo = $todosListQuery->fetch(PDO::FETCH_ASSOC)) {
									$hasTodos = true;
									?>
									<a href="index.php?p=task-edit&reg=true&id=<?php echo $todo['id']; ?>" class="crm-feed-item border-left-accent-purple">
										<div style="flex: 1; min-width: 0; padding-right: 12px;">
											<div class="crm-feed-title text-truncate">
												<?php echo htmlspecialchars($todo['title'], ENT_QUOTES, 'UTF-8'); ?>
											</div>
											<div class="crm-feed-subtitle">
												<span><i class="fa fa-user-circle mr-1"></i><?php echo htmlspecialchars($todo['creator_name'] ?: 'Sistem', ENT_QUOTES, 'UTF-8'); ?></span>
											</div>
										</div>
										<div class="text-right flex-shrink-0">
											<span class="crm-badge-soft soft-purple">
												<i class="fa fa-clock-o mr-1"></i><?php echo htmlspecialchars($todo['last_date'] ?: 'Tarihsiz', ENT_QUOTES, 'UTF-8'); ?>
											</span>
										</div>
									</a>
								<?php } 
								if (!$hasTodos) {
									echo '<div class="text-center py-4 text-muted font-13"><i class="fa fa-check-circle-o font-20 text-success d-block mb-1"></i>Tüm yapılacaklar tamamlandı!</div>';
								}
								?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- WIDGET 7: SİSTEM AKTİVİTELERİ & GİRİŞLER -->
			<?php 
				$wLogs = $layoutMap['widget_system_logs'] ?? ['w' => 12, 'h' => 10, 'x' => 0, 'y' => 18, 'visible' => true];
				$isLogsVisible = !empty($wLogs['visible']);
			?>
				<div class="grid-stack-item <?php echo $isLogsVisible ? '' : 'd-none'; ?>" 
					 id="grid_item_widget_system_logs"
					 gs-id="widget_system_logs"
					 gs-x="<?php echo (int)($wLogs['x'] ?? 0); ?>"
					 gs-y="<?php echo (int)($wLogs['y'] ?? 18); ?>"
					 gs-w="<?php echo (int)($wLogs['w'] ?? 12); ?>"
					 gs-h="<?php echo (int)($wLogs['h'] ?? 10); ?>"
					 gs-min-w="4"
					 gs-min-h="10">
					<div class="grid-stack-item-content">
						<div class="crm-card h-100 mb-0">
							<div class="crm-card-header flex-wrap" style="gap: 12px;">
								<div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
									<span class="crm-widget-drag-handle" title="Taşımak için sürükleyin"><i class="fa fa-arrows"></i></span>
									<h3 class="crm-card-title m-0">
										<i class="fa fa-history text-primary"></i> Sistem Aktiviteleri & Girişler
									</h3>
									<span class="crm-badge-soft soft-blue font-12" id="crmLogHeaderBadge">
										Son 10 Kayıt
									</span>
								</div>
								<div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
									<div class="crm-view-switcher-group" role="group" aria-label="Aktivite Türü">
										<button type="button" class="btn-view-toggle active" id="btnLogTabActivities" title="Son Sistem Aktiviteleri">
											<i class="fa fa-bolt"></i> Aktiviteler
										</button>
										<button type="button" class="btn-view-toggle" id="btnLogTabLogins" title="Son Giriş Kayıtları">
											<i class="fa fa-sign-in"></i> Girişler
										</button>
									</div>

									<a href="index.php?p=logs/index" class="crm-card-link ml-1">
										Tüm Aktiviteler <i class="fa fa-arrow-right"></i>
									</a>

									<div class="crm-widget-controls">
										<button type="button" class="btn-widget-close" data-widget-id="widget_system_logs" title="Bu kartı gizle">
											<i class="fa fa-times"></i>
										</button>
									</div>
								</div>
							</div>
							<div class="crm-card-body p-3">
								<!-- TAB 1: AKTİVİTELER -->
								<div id="crmLogActivitiesWrapper">
									<?php if (empty($latestActivities)) : ?>
										<div class="text-center py-4 text-muted font-13">
											<i class="fa fa-history font-24 d-block mb-2 text-muted" style="opacity: 0.5;"></i>
											Henüz sistem aktivite kaydı bulunmuyor.
										</div>
									<?php else : ?>
										<div class="crm-feed-list">
											<?php foreach ($latestActivities as $act) :
												$evIcon = ActivityLogModel::getEventIcon($act->event_type);
												$evBadge = ActivityLogModel::getEventBadgeClass($act->event_type);
												$evLabel = ActivityLogModel::getEventLabel($act->event_type);
												$moduleLabel = ActivityLogModel::getModuleLabel($act->module);
												$relTime = ActivityLogModel::formatRelativeTime($act->created_at, $act->dates, $act->clock);
												$userName = $act->username ?: 'Sistem';
												$summaryText = $act->summary ?: ($act->action ?: ($act->message ?: 'İşlem gerçekleştirildi'));
												$borderAccent = 'border-left-accent-blue';
												if (in_array($act->event_type, ['create', 'download'])) $borderAccent = 'border-left-accent-emerald';
												elseif (in_array($act->event_type, ['delete', 'error'])) $borderAccent = 'border-left-accent-rose';
												elseif (in_array($act->event_type, ['status_change', 'copy'])) $borderAccent = 'border-left-accent-purple';
												elseif (in_array($act->event_type, ['export', 'logout'])) $borderAccent = 'border-left-accent-amber';
											?>
												<a href="index.php?p=logs/index&tab=logs" class="crm-feed-item <?php echo $borderAccent; ?>">
													<div class="d-flex align-items-center" style="gap: 12px; flex: 1; min-width: 0;">
														<div class="crm-log-item-icon <?php echo $evBadge; ?>">
															<i class="<?php echo $evIcon; ?>"></i>
														</div>
														<div style="flex: 1; min-width: 0; padding-right: 12px;">
															<div class="crm-feed-title text-truncate">
																<strong class="text-dark"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></strong>
																<span class="text-muted font-weight-normal font-12 ml-1">— <?php echo htmlspecialchars($summaryText, ENT_QUOTES, 'UTF-8'); ?></span>
															</div>
															<div class="crm-feed-subtitle flex-wrap" style="gap: 6px 8px;">
																<span class="crm-badge-soft <?php echo $evBadge; ?>" style="font-size: 10px; padding: 1px 6px;">
																	<?php echo htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8'); ?>
																</span>
																<span>•</span>
																<span><i class="fa fa-clock-o mr-1"></i><?php echo htmlspecialchars($relTime, ENT_QUOTES, 'UTF-8'); ?></span>
																<?php if (!empty($act->ip_address)) : ?>
																	<span>•</span>
																	<span class="text-muted"><i class="fa fa-globe mr-1"></i><?php echo htmlspecialchars($act->ip_address, ENT_QUOTES, 'UTF-8'); ?></span>
																<?php endif; ?>
															</div>
														</div>
													</div>
													<div class="text-right flex-shrink-0 d-none d-sm-block">
														<span class="crm-badge-soft <?php echo $evBadge; ?>">
															<?php echo htmlspecialchars($evLabel, ENT_QUOTES, 'UTF-8'); ?>
														</span>
													</div>
												</a>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>

								<!-- TAB 2: GİRİŞLER -->
								<div id="crmLogLoginsWrapper" class="d-none">
									<?php if (empty($latestLogins)) : ?>
										<div class="text-center py-4 text-muted font-13">
											<i class="fa fa-sign-in font-24 d-block mb-2 text-muted" style="opacity: 0.5;"></i>
											Henüz giriş kaydı bulunmuyor.
										</div>
									<?php else : ?>
										<div class="crm-feed-list">
											<?php foreach ($latestLogins as $login) :
												$relTime = ActivityLogModel::formatRelativeTime($login->created_at, $login->dates, $login->clock);
												$userName = $login->username ?: 'Sistem';
												$exactTime = !empty($login->created_at) ? date('d.m.Y H:i:s', strtotime($login->created_at)) : ($login->dates . ' ' . $login->clock);
											?>
												<a href="index.php?p=logs/index&tab=logs&filter_event=login" class="crm-feed-item border-left-accent-emerald">
													<div class="d-flex align-items-center" style="gap: 12px; flex: 1; min-width: 0;">
														<div class="crm-log-item-icon soft-emerald">
															<i class="fa fa-sign-in"></i>
														</div>
														<div style="flex: 1; min-width: 0; padding-right: 12px;">
															<div class="crm-feed-title text-truncate">
																<strong class="text-dark"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></strong>
																<span class="text-muted font-weight-normal font-12 ml-1">— Sisteme başarılı giriş yaptı</span>
															</div>
															<div class="crm-feed-subtitle flex-wrap" style="gap: 6px 8px;">
																<span><i class="fa fa-clock-o mr-1"></i><?php echo htmlspecialchars($relTime, ENT_QUOTES, 'UTF-8'); ?></span>
																<span>•</span>
																<span class="text-muted font-11" title="Kayıt Tarihi"><i class="fa fa-calendar mr-1"></i><?php echo htmlspecialchars($exactTime, ENT_QUOTES, 'UTF-8'); ?></span>
																<?php if (!empty($login->ip_address)) : ?>
																	<span>•</span>
																	<span class="text-muted"><i class="fa fa-globe mr-1"></i><?php echo htmlspecialchars($login->ip_address, ENT_QUOTES, 'UTF-8'); ?></span>
																<?php endif; ?>
															</div>
														</div>
													</div>
													<div class="text-right flex-shrink-0 d-none d-sm-block">
														<span class="crm-badge-soft soft-emerald">
															<i class="fa fa-check-circle mr-1"></i> Başarılı Giriş
														</span>
													</div>
												</a>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>

		</div>
	</div>
</div>

<!-- GÜNLÜK SERVİSLER DETAY MODAL'I -->
<div class="modal fade crm-modal-day-services" id="crmDailyServicesModal" tabindex="-1" role="dialog" aria-labelledby="crmDailyModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header d-flex align-items-center justify-content-between">
				<div class="d-flex align-items-center" style="gap: 10px;">
					<h5 class="modal-title font-16 weight-700 text-dark m-0" id="crmDailyModalTitle">
						<i class="fa fa-calendar-check-o text-primary mr-1"></i> Servis Listesi
					</h5>
					<span class="badge badge-pill badge-primary font-12 px-2 py-1" id="crmDailyModalCount">0 Servis</span>
				</div>
				<button type="button" class="close btn-crm-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat" style="outline: none; cursor: pointer;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="crmDailyModalBody">
				<!-- Dinamik render edilir -->
			</div>
			<div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between align-items-center">
				<?php if (permtrue('servicenew')) : ?>
					<a href="index.php?p=service-new" class="btn btn-sm btn-outline-primary" style="font-size: 12px; border-radius: 6px;">
						<i class="fa fa-plus-circle mr-1"></i> Yeni Servis Ekle
					</a>
				<?php else: ?>
					<span></span>
				<?php endif; ?>
				<button type="button" class="btn btn-sm btn-secondary btn-crm-modal-close" data-dismiss="modal" data-bs-dismiss="modal" style="font-size: 12px; border-radius: 6px; cursor: pointer;">Kapat</button>
			</div>
		</div>
	</div>
</div>

<!-- WIDGET YÖNETİMİ (KATALOG) MODAL'I -->
<div class="modal fade" id="crmWidgetCatalogModal" tabindex="-1" role="dialog" aria-labelledby="crmWidgetModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-md" role="document">
		<div class="modal-content" style="border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.12);">
			<div class="modal-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
				<div class="d-flex align-items-center" style="gap: 10px;">
					<div class="crm-widget-cat-icon" style="background: #eef2ff; color: #4f46e5;">
						<i class="fa fa-th-large"></i>
					</div>
					<div>
						<h5 class="modal-title font-16 weight-700 text-dark m-0" id="crmWidgetModalTitle">
							Pano Kartlarını Yönet
						</h5>
						<p class="text-muted font-12 m-0">Ana sayfanızda görmek istediğiniz kartları seçin</p>
					</div>
				</div>
				<button type="button" class="close btn-crm-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat" style="outline: none; cursor: pointer;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body p-4" style="max-height: 60vh; overflow-y: auto;">
				<div class="crm-widget-catalog-list">
					<?php foreach ($widgetCatalog as $wId => $meta) : 
						if (empty($meta['is_allowed'])) continue;
						$curW = $layoutMap[$wId] ?? null;
						$isChecked = !empty($curW['visible']);
					?>
						<div class="crm-widget-catalog-item">
							<div class="d-flex align-items-center" style="gap: 12px; flex: 1; min-width: 0; padding-right: 12px;">
								<div class="crm-widget-cat-icon <?php echo htmlspecialchars($meta['badge_class'], ENT_QUOTES, 'UTF-8'); ?>">
									<i class="<?php echo htmlspecialchars($meta['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
								</div>
								<div style="flex: 1; min-width: 0;">
									<div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
										<span class="weight-600 text-dark font-13"><?php echo htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8'); ?></span>
										<span class="crm-badge-soft <?php echo htmlspecialchars($meta['badge_class'], ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 10px; padding: 1px 6px;">
											<?php echo htmlspecialchars($meta['badge'], ENT_QUOTES, 'UTF-8'); ?>
										</span>
									</div>
									<div class="text-muted font-11 text-truncate mt-1">
										<?php echo htmlspecialchars($meta['subtitle'], ENT_QUOTES, 'UTF-8'); ?>
									</div>
								</div>
							</div>
							<div class="flex-shrink-0">
								<label class="crm-toggle-switch">
									<input type="checkbox" class="crm-widget-toggle-input" data-widget-id="<?php echo htmlspecialchars($wId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
									<span class="crm-toggle-slider"></span>
								</label>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="modal-footer bg-light py-2 px-4 border-top d-flex justify-content-between align-items-center">
				<button type="button" class="btn btn-sm btn-outline-danger" id="btnModalResetLayout" style="font-size: 12px; border-radius: 6px;">
					<i class="fa fa-refresh mr-1"></i> Varsayılana Sıfırla
				</button>
				<button type="button" class="btn btn-sm btn-primary btn-crm-modal-close" data-dismiss="modal" data-bs-dismiss="modal" style="font-size: 12px; border-radius: 6px; padding: 6px 16px;">
					Tamam
				</button>
			</div>
		</div>
	</div>
</div>

<!-- GridStack JS Kütüphanesi -->
<script src="src/plugins/gridstack/gridstack-all.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
	var monthDaysJson = <?php echo json_encode($monthDaysJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?> || {};
	var initialDashboardLayout = <?php echo json_encode($effectiveLayout, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?> || [];
	var dashboardApiUrl = <?php echo json_encode(rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/') . '/api/dashboard-layout.php', JSON_UNESCAPED_SLASHES); ?>;
	var widgetSettings = {};
	initialDashboardLayout.forEach(function(item) {
		widgetSettings[item.id] = item.settings || {};
	});
	var btnSingleRow = document.getElementById('btnViewSingleRow');
	var btnMonthCal = document.getElementById('btnViewMonthCal');
	var btnExpand = document.getElementById('btnToggleExpandServices');
	var btnExpandText = document.getElementById('btnExpandText');
	var calLayout = document.getElementById('crmMonthCalLayout');
	var singleRowWrapper = document.getElementById('crmSingleRowWrapper');
	var monthCalWrapper = document.getElementById('crmMonthCalWrapper');
	var headerBadge = document.getElementById('crmHeaderCountBadge');

	var timeline = document.getElementById('crmTimelineScroll');
	var prevBtn = document.getElementById('crmTimelinePrev');
	var nextBtn = document.getElementById('crmTimelineNext');

	var modalEl = document.getElementById('crmDailyServicesModal');
	var modalTitle = document.getElementById('crmDailyModalTitle');
	var modalCount = document.getElementById('crmDailyModalCount');
	var modalBody = document.getElementById('crmDailyModalBody');

	var periodCountText = '<?php echo $totalPeriodServices; ?> Servis';
	var monthCountText = '<?php echo $totalMonthServices; ?> Servis';

	function escapeHtml(str) {
		if (!str) return '';
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function setView(viewMode, persist) {
		var serviceSettings = widgetSettings.widget_service_board || {};
		if (viewMode === 'month_cal') {
			if (btnMonthCal) btnMonthCal.classList.add('active');
			if (btnSingleRow) btnSingleRow.classList.remove('active');
			if (monthCalWrapper) monthCalWrapper.classList.remove('d-none');
			if (singleRowWrapper) singleRowWrapper.classList.add('d-none');
			if (btnExpand) btnExpand.classList.remove('d-none');
			if (headerBadge) headerBadge.textContent = monthCountText;
			serviceSettings.view = 'month_cal';
		} else {
			if (btnSingleRow) btnSingleRow.classList.add('active');
			if (btnMonthCal) btnMonthCal.classList.remove('active');
			if (singleRowWrapper) singleRowWrapper.classList.remove('d-none');
			if (monthCalWrapper) monthCalWrapper.classList.add('d-none');
			if (btnExpand) btnExpand.classList.add('d-none');
			if (headerBadge) headerBadge.textContent = periodCountText;
			serviceSettings.view = 'single_row';
			serviceSettings.expanded = false;
			scrollToToday();
		}
		widgetSettings.widget_service_board = serviceSettings;
		if (persist) {
			applyServiceViewHeight(serviceSettings.view);
			triggerAutoSave();
		}
	}

	function toggleExpandServices(persist) {
		if (!calLayout) return;
		var isExpanded = calLayout.classList.toggle('crm-cal-expanded');
		if (btnExpand) btnExpand.classList.toggle('is-expanded', isExpanded);
		if (btnExpandText) btnExpandText.textContent = isExpanded ? 'Kompakt Takvim' : 'Servisleri Genişlet';
		widgetSettings.widget_service_board = widgetSettings.widget_service_board || {};
		widgetSettings.widget_service_board.expanded = isExpanded;
		if (persist) {
			triggerAutoSave();
		}
	}

	if (btnExpand) {
		btnExpand.addEventListener('click', function(e) {
			e.preventDefault();
			toggleExpandServices(true);
		});
	}

	function scrollToToday() {
		if (timeline) {
			var todayCol = timeline.querySelector('.crm-day-column.is-today');
			if (todayCol) {
				setTimeout(function() {
					var scrollLeftPos = todayCol.offsetLeft - (timeline.clientWidth / 2) + (todayCol.clientWidth / 2);
					if (scrollLeftPos > 0) {
						timeline.scrollTo({ left: scrollLeftPos, behavior: 'smooth' });
					}
				}, 100);
			}
		}
	}

	if (btnSingleRow && btnMonthCal) {
		btnSingleRow.addEventListener('click', function(e) {
			e.preventDefault();
			setView('single_row', true);
		});

		btnMonthCal.addEventListener('click', function(e) {
			e.preventDefault();
			setView('month_cal', true);
		});
	}

	if (timeline && prevBtn && nextBtn) {
		var scrollAmount = 450;
		prevBtn.addEventListener('click', function(e) {
			e.preventDefault();
			timeline.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
		});
		nextBtn.addEventListener('click', function(e) {
			e.preventDefault();
			timeline.scrollBy({ left: scrollAmount, behavior: 'smooth' });
		});
	}

	function hideModal(targetModal) {
		if (typeof $ !== 'undefined' && $(targetModal).length) {
			try {
				$(targetModal).modal('hide');
			} catch (err) {}
		}
		if (targetModal) {
			targetModal.classList.remove('show');
			targetModal.style.display = 'none';
			document.body.classList.remove('modal-open');
			document.body.style.paddingRight = '';
			var backdrops = document.querySelectorAll('.modal-backdrop');
			backdrops.forEach(function(b) {
				b.remove();
			});
		}
	}

	function showModal(targetModal) {
		if (typeof $ !== 'undefined' && $(targetModal).length) {
			try {
				$(targetModal).modal('show');
				return;
			} catch (err) {}
		}
		if (targetModal) {
			targetModal.classList.add('show');
			targetModal.style.display = 'block';
			document.body.classList.add('modal-open');
		}
	}

	document.querySelectorAll('.btn-crm-modal-close, [data-dismiss="modal"], [data-bs-dismiss="modal"]').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			var parentModal = this.closest('.modal');
			if (parentModal) {
				hideModal(parentModal);
			}
		});
	});

	// Günlük Servis Modal Açıcı
	document.querySelectorAll('.crm-cal-count-pill').forEach(function(pill) {
		pill.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var dateStr = this.getAttribute('data-date');
			var dayData = monthDaysJson[dateStr];
			if (!dayData) return;

			if (modalTitle) {
				modalTitle.innerHTML = '<i class="fa fa-calendar text-primary mr-2"></i>' + escapeHtml(dayData.dayTr) + ', ' + escapeHtml(dayData.displayDate) + (dayData.isToday ? ' <span class="crm-badge-soft soft-blue font-11 ml-1">Bugün</span>' : '');
			}
			if (modalCount) {
				modalCount.textContent = (dayData.count || 0) + ' Servis';
			}
			if (modalBody) {
				modalBody.innerHTML = '';
				if (!dayData.services || dayData.services.length === 0) {
					modalBody.innerHTML = '<div class="text-center py-5 text-muted font-13"><i class="fa fa-calendar-check-o font-30 d-block mb-2 text-muted" style="opacity: 0.5;"></i>Bu tarihte planlanmış servis bulunmuyor.</div>';
				} else {
					var listHtml = '<div class="row">';
					dayData.services.forEach(function(item) {
						var stCol = item.status_color || '#3b82f6';
						listHtml += `
							<div class="col-md-6 col-12 mb-3">
								<a href="index.php?p=service/list&id=${item.id}" class="crm-modal-service-card" style="background: ${escapeHtml(stCol)}14; border-color: ${escapeHtml(stCol)}35; border-left: 4px solid ${escapeHtml(stCol)};">
									<div class="d-flex justify-content-between align-items-center mb-2">
										<span class="crm-service-num" style="color: ${escapeHtml(stCol)}; font-weight: 700; font-size: 12px;">${escapeHtml(item.service_number)}</span>
										<span class="crm-badge-soft" style="background: ${escapeHtml(stCol)}22; color: ${escapeHtml(stCol)}; border: 1px solid ${escapeHtml(stCol)}40; font-size: 10px; padding: 2px 7px;">
											${escapeHtml(item.status_title)}
										</span>
									</div>
									<div class="crm-service-company font-13 weight-600 mb-2 text-dark" title="${escapeHtml(item.company)}">
										${escapeHtml(item.company)}
									</div>
									${item.title ? `<div class="text-muted font-11 mb-2"><i class="fa fa-tag text-primary mr-1"></i>${escapeHtml(item.title)}</div>` : ''}
									${item.authors ? `
										<div class="crm-service-meta pt-2 border-top" style="border-color: ${escapeHtml(stCol)}25 !important; font-size: 11px;">
											<i class="fa fa-user-o mr-1"></i> ${escapeHtml(item.authors)}
										</div>
									` : ''}
								</a>
							</div>
						`;
					});
					listHtml += '</div>';
					modalBody.innerHTML = listHtml;
				}
			}
			showModal(modalEl);
		});
	});

	// Servis panosu tercihleri kullanıcı hesabından gelir; böylece cihazlar arasında aynı kalır.
	var initialServiceSettings = widgetSettings.widget_service_board || {};
	if (initialServiceSettings.expanded && calLayout) {
		calLayout.classList.add('crm-cal-expanded');
		if (btnExpand) btnExpand.classList.add('is-expanded');
		if (btnExpandText) btnExpandText.textContent = 'Kompakt Takvim';
	}
	setView(initialServiceSettings.view === 'month_cal' ? 'month_cal' : 'single_row', false);

	// Sistem Aktiviteleri & Girişler Tab Değişimi
	var btnLogAct = document.getElementById('btnLogTabActivities');
	var btnLogLgn = document.getElementById('btnLogTabLogins');
	var wrapLogAct = document.getElementById('crmLogActivitiesWrapper');
	var wrapLogLgn = document.getElementById('crmLogLoginsWrapper');

	function setLogTab(tab) {
		if (!btnLogAct || !btnLogLgn || !wrapLogAct || !wrapLogLgn) return;
		if (tab === 'logins') {
			btnLogAct.classList.remove('active');
			btnLogLgn.classList.add('active');
			wrapLogAct.classList.add('d-none');
			wrapLogLgn.classList.remove('d-none');
			try { localStorage.setItem('crm_home_log_tab', 'logins'); } catch(e) {}
		} else {
			btnLogLgn.classList.remove('active');
			btnLogAct.classList.add('active');
			wrapLogLgn.classList.add('d-none');
			wrapLogAct.classList.remove('d-none');
			try { localStorage.setItem('crm_home_log_tab', 'activities'); } catch(e) {}
		}
	}

	if (btnLogAct && btnLogLgn) {
		btnLogAct.addEventListener('click', function(e) { e.preventDefault(); setLogTab('activities'); });
		btnLogLgn.addEventListener('click', function(e) { e.preventDefault(); setLogTab('logins'); });
		try {
			if (localStorage.getItem('crm_home_log_tab') === 'logins') setLogTab('logins');
		} catch(e) {}
	}

	/* ==========================================================================
	   GRIDSTACK VE DİNAMİK WIDGET YÖNETİM MOTORU
	   ========================================================================== */
	var gridEl = document.getElementById('crmDashboardGrid');
	var syncIndicator = document.getElementById('crmSyncIndicator');
	var saveTimeout = null;
	var saveInFlight = false;
	var savePending = false;
	var suppressLayoutSave = false;
	var currentSaveController = null;
	var isEditMode = true;
	var csrfMeta = document.querySelector('meta[name="csrf-token"]');
	var CSRF_TOKEN = csrfMeta ? csrfMeta.getAttribute('content') : <?php echo json_encode(\App\Helper\Security::csrf()); ?>;

	function revealDashboardGrid() {
		if (!gridEl) return;
		gridEl.classList.remove('crm-grid-preparing');
		gridEl.classList.add('crm-grid-ready');
		gridEl.setAttribute('aria-busy', 'false');
	}

	// GridStack Başlatma
	var grid = null;
	if (typeof GridStack !== 'undefined' && gridEl) {
		grid = GridStack.init({
			column: 12,
			cellHeight: '85px',
			minRow: 1,
			margin: 6,
			handle: '.crm-widget-drag-handle',
			resizable: {
				handles: 'e, s, se'
			},
			// İlk yerleşimde animasyon kapalıdır; kayıtlı konumlar ekranda sıçramadan uygulanır.
			animate: false,
			disableOneColumnMode: false,
			float: false
		}, gridEl);

		// Gizli kartlar ızgara motorunda yer kaplamamalı; DOM'da katalogdan tekrar eklenmek üzere korunur.
		gridEl.querySelectorAll('.grid-stack-item.d-none').forEach(function(hiddenItem) {
			grid.removeWidget(hiddenItem, false);
		});

		// Grid değişikliklerini dinle ve sunucuya kaydet (taşıma ve boyutlandırma dahil)
		grid.on('change added removed dragstop', function() {
			triggerAutoSave();
		});
		grid.on('resizestop', function(event, itemEl) {
			var resizedId = itemEl && itemEl.getAttribute ? itemEl.getAttribute('gs-id') : '';
			if (resizedId) {
				widgetSettings[resizedId] = widgetSettings[resizedId] || {};
				if (resizedId === 'widget_service_board' && itemEl.gridstackNode) {
					var serviceView = widgetSettings[resizedId].view === 'month_cal' ? 'month_cal' : 'single_row';
					widgetSettings[resizedId].heights = widgetSettings[resizedId].heights || { single_row: 4, month_cal: 11 };
					widgetSettings[resizedId].heights[serviceView] = itemEl.gridstackNode.h;
				}
			}
			triggerAutoSave();
		});
		applyServiceViewHeight((widgetSettings.widget_service_board || {}).view);
		requestAnimationFrame(function() {
			requestAnimationFrame(function() {
				revealDashboardGrid();
				if (grid && typeof grid.setAnimation === 'function') grid.setAnimation(true);
			});
		});
	} else {
		// Kütüphane yüklenemezse içerik erişilebilir kalır.
		revealDashboardGrid();
	}

	function applyServiceViewHeight(viewMode) {
		if (!grid || !gridEl) return;
		var settings = widgetSettings.widget_service_board || {};
		var itemEl = document.getElementById('grid_item_widget_service_board');
		if (!itemEl || itemEl.classList.contains('d-none')) return;
		settings.heights = settings.heights || { single_row: 4, month_cal: 11 };
		var normalizedView = viewMode === 'month_cal' ? 'month_cal' : 'single_row';
		var targetHeight = parseInt(settings.heights[normalizedView], 10);
		var minimumHeight = normalizedView === 'month_cal' ? 11 : 4;
		if (!Number.isFinite(targetHeight)) targetHeight = minimumHeight;
		targetHeight = Math.max(minimumHeight, targetHeight);
		var node = itemEl.gridstackNode;
		if (node && (node.h !== targetHeight || node.minH !== minimumHeight)) {
			grid.update(itemEl, { h: targetHeight, minH: minimumHeight });
		}
	}

	function showSyncState(state, detail) {
		if (!syncIndicator) return;
		if (state === 'saving') {
			syncIndicator.className = 'crm-sync-indicator is-saving';
			syncIndicator.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Kaydediliyor...';
		} else if (state === 'saved') {
			syncIndicator.className = 'crm-sync-indicator is-saved';
			syncIndicator.innerHTML = '<i class="fa fa-check-circle"></i> Kaydedildi';
			setTimeout(function() {
				syncIndicator.classList.remove('is-saved');
			}, 3000);
		} else if (state === 'error') {
			syncIndicator.className = 'crm-sync-indicator text-danger';
			syncIndicator.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Kaydedilemedi';
			syncIndicator.title = detail || 'Pano yerleşimi kaydedilemedi.';
		}
	}

	// Otomatik Kayıt Tetikleyici (Debounce)
	function triggerAutoSave() {
		if (!grid || suppressLayoutSave) return;
		showSyncState('saving');
		savePending = true;
		clearTimeout(saveTimeout);
		saveTimeout = setTimeout(function() {
			flushLayoutSave();
		}, 400);
	}

	function collectCurrentLayout() {
		var savedNodes = grid.save(false) || [];
		var savedMap = {};
		savedNodes.forEach(function(n) {
			if (n && n.id) {
				savedMap[n.id] = n;
			}
		});

		var layoutPayload = [];
		var gridItems = document.querySelectorAll('.grid-stack-item');

		gridItems.forEach(function(itemEl) {
			var wId = itemEl.getAttribute('gs-id');
			if (!wId) return;

			var isVisible = !itemEl.classList.contains('d-none');
			var node = savedMap[wId] || itemEl.gridstackNode || {};

			var x = typeof node.x !== 'undefined' ? parseInt(node.x, 10) : parseInt(itemEl.getAttribute('gs-x') || 0, 10);
			var y = typeof node.y !== 'undefined' ? parseInt(node.y, 10) : parseInt(itemEl.getAttribute('gs-y') || 0, 10);
			var w = typeof node.w !== 'undefined' ? parseInt(node.w, 10) : parseInt(itemEl.getAttribute('gs-w') || 12, 10);
			var h = typeof node.h !== 'undefined' ? parseInt(node.h, 10) : parseInt(itemEl.getAttribute('gs-h') || 4, 10);
			var minW = typeof node.minW !== 'undefined' ? parseInt(node.minW, 10) : parseInt(itemEl.getAttribute('gs-min-w') || 4, 10);
			var minH = typeof node.minH !== 'undefined' ? parseInt(node.minH, 10) : parseInt(itemEl.getAttribute('gs-min-h') || 2, 10);

			layoutPayload.push({
				id: wId,
				x: x,
				y: y,
				w: w,
				h: h,
				minW: minW,
				minH: minH,
				visible: isVisible,
				settings: widgetSettings[wId] || {}
			});
		});
		return layoutPayload;
	}

	function readJsonResponse(res) {
		return res.json().catch(function() {
			throw new Error('Sunucudan geçersiz yanıt alındı.');
		}).then(function(data) {
			if (!res.ok || !data || data.status !== 'success') {
				throw new Error((data && data.message) || 'Pano yerleşimi kaydedilemedi.');
			}
			return data;
		});
	}

	// Kayıtlar seri gönderilir; eski bir isteğin yeni yerleşimi ezmesi engellenir.
	function flushLayoutSave() {
		if (!grid || suppressLayoutSave || !savePending) return;
		if (saveInFlight) return;
		savePending = false;
		saveInFlight = true;
		currentSaveController = typeof AbortController !== 'undefined' ? new AbortController() : null;
		var layoutPayload = collectCurrentLayout();

		fetch(dashboardApiUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-Token': CSRF_TOKEN
			},
			body: JSON.stringify({
				action: 'save',
				csrf_token: CSRF_TOKEN,
				layout: layoutPayload
			}),
			signal: currentSaveController ? currentSaveController.signal : undefined
		})
		.then(readJsonResponse)
		.then(function(data) {
			showSyncState('saved');
		})
		.catch(function(err) {
			if (!err || err.name !== 'AbortError') {
				showSyncState('error', err && err.message ? err.message : 'Pano yerleşimi kaydedilemedi.');
			}
		})
		.finally(function() {
			saveInFlight = false;
			currentSaveController = null;
			if (savePending && !suppressLayoutSave) flushLayoutSave();
		});
	}

	// Kart Kapatma Butonları (X)
	document.querySelectorAll('.btn-widget-close').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var wId = this.getAttribute('data-widget-id');
			if (!wId) return;

			var itemEl = document.getElementById('grid_item_' + wId);
			if (itemEl) {
				itemEl.classList.add('d-none');
				if (grid) {
					grid.removeWidget(itemEl, false); // DOM'dan silme, sadece grid'den çıkar
				}
				// Katalog modalındaki switch'i kapat
				var toggleInput = document.querySelector('.crm-widget-toggle-input[data-widget-id="' + wId + '"]');
				if (toggleInput) {
					toggleInput.checked = false;
				}
				triggerAutoSave();
			}
		});
	});

	// Widget Yönetimi Modalı Açma
	var widgetModalEl = document.getElementById('crmWidgetCatalogModal');
	var btnOpenWidgetMgr = document.getElementById('btnOpenWidgetManager');
	if (btnOpenWidgetMgr && widgetModalEl) {
		btnOpenWidgetMgr.addEventListener('click', function(e) {
			e.preventDefault();
			showModal(widgetModalEl);
		});
	}

	// Modal İçindeki Switch Toggle Değişimi
	document.querySelectorAll('.crm-widget-toggle-input').forEach(function(input) {
		input.addEventListener('change', function() {
			var wId = this.getAttribute('data-widget-id');
			var isChecked = this.checked;
			var itemEl = document.getElementById('grid_item_' + wId);

			if (itemEl) {
				if (isChecked) {
					itemEl.classList.remove('d-none');
					if (grid) {
						grid.makeWidget(itemEl);
					}
				} else {
					itemEl.classList.add('d-none');
					if (grid) {
						grid.removeWidget(itemEl, false);
					}
				}
				triggerAutoSave();
			}
		});
	});

	// Düzenleme / Kilitleme Modu
	var btnToggleEdit = document.getElementById('btnToggleEditMode');
	var btnEditModeText = document.getElementById('btnEditModeText');

	if (btnToggleEdit && grid) {
		btnToggleEdit.addEventListener('click', function(e) {
			e.preventDefault();
			isEditMode = !isEditMode;

			if (isEditMode) {
				btnToggleEdit.classList.add('active');
				if (btnEditModeText) btnEditModeText.textContent = 'Düzenleme Modu';
				gridEl.classList.remove('is-locked');
				grid.enableMove(true);
				grid.enableResize(true);
			} else {
				btnToggleEdit.classList.remove('active');
				if (btnEditModeText) btnEditModeText.textContent = 'Düzen Kilitli';
				gridEl.classList.add('is-locked');
				grid.enableMove(false);
				grid.enableResize(false);
			}
		});
	}

	// Varsayılana Dönme Fonksiyonu
	function resetToDefaultLayout() {
		var doReset = function() {
			suppressLayoutSave = true;
			savePending = false;
			clearTimeout(saveTimeout);
			if (currentSaveController) currentSaveController.abort();
			showSyncState('saving');
			fetch(dashboardApiUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-Token': CSRF_TOKEN
				},
				body: JSON.stringify({
					action: 'reset',
					csrf_token: CSRF_TOKEN
				})
			})
			.then(readJsonResponse)
			.then(function(data) {
				if (data && data.status === 'success') {
					showSyncState('saved');
					if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
						Swal.fire({
							title: 'Başarılı!',
							text: 'Pano yerleşimi varsayılana sıfırlandı. Sayfa yenileniyor...',
							icon: 'success',
							timer: 1200,
							showConfirmButton: false
						});
					}
					setTimeout(function() {
						window.location.reload();
					}, 1200);
				} else {
					suppressLayoutSave = false;
					showSyncState('error');
					alert('Sıfırlama sırasında bir hata oluştu.');
				}
			})
			.catch(function(err) {
				suppressLayoutSave = false;
				showSyncState('error', err && err.message ? err.message : 'Bağlantı hatası oluştu.');
				alert(err && err.message ? err.message : 'Bağlantı hatası oluştu.');
			});
		};

		if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
			Swal.fire({
				title: 'Varsayılan Düzene Dönülsün mü?',
				text: 'Tüm kartların sıralaması, boyutları, görünürlükleri ve servis panosu tercihleri sıfırlanacaktır.',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#ef4444',
				cancelButtonColor: '#64748b',
				confirmButtonText: 'Evet, Sıfırla',
				cancelButtonText: 'İptal',
				focusCancel: true
			}).then(function(result) {
				if (result.isConfirmed) doReset();
			});
		} else {
			if (confirm("Pano yerleşimi varsayılana sıfırlansın mı?")) {
				doReset();
			}
		}
	}

	var btnReset = document.getElementById('btnResetDashboardLayout');
	if (btnReset) {
		btnReset.addEventListener('click', function(e) {
			e.preventDefault();
			resetToDefaultLayout();
		});
	}

	var btnModalReset = document.getElementById('btnModalResetLayout');
	if (btnModalReset) {
		btnModalReset.addEventListener('click', function(e) {
			e.preventDefault();
			resetToDefaultLayout();
		});
	}
});
</script>

<style>
.crm-log-item-icon {
	width: 36px;
	height: 36px;
	border-radius: 9px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 14px;
	flex-shrink: 0;
}
.crm-feed-item.border-left-accent-rose {
	border-left: 3px solid #f43f5e;
}
.crm-feed-item.border-left-accent-cyan {
	border-left: 3px solid #06b6d4;
}
</style>
