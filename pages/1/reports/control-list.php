<?php

use App\Model\ReportControlModel;
use App\Helper\Date;
use App\Helper\Security;

$control = new ReportControlModel();

$month = $_POST['control_month'] ?? ($_GET['month'] ?? Date::getThisMonth());
$year = $_POST['control_year'] ?? ($_GET['year'] ?? Date::getThisYear());
$controlList = $control->getReportControlList($month, $year);

$totalCount = count($controlList);
$uniqueCompanies = count(array_unique(array_filter(array_map(function($item) { return $item->firma_adi ?? null; }, $controlList))));
$uniqueReports = count(array_unique(array_filter(array_map(function($item) { return $item->report_number ?? null; }, $controlList))));

$monthNames = [
    '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
    '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
    '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık',
    '' => 'Tüm Aylar'
];
$selectedMonthName = $monthNames[$month] ?? ($month ? $month . '. Ay' : 'Tüm Aylar');
$selectedYearName = $year ?: 'Tüm Yıllar';

try {
    $logger = \getLogger("Raporlar");
    $logger->info("Cihaz kontrol listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown',
        'month' => $month,
        'year' => $year,
        'count' => $totalCount
    ]);
} catch (\Throwable $e) {}
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_control_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-control-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* Premium Kontrol Listesi Sayfa Stilleri (reports.php ile tam uyumlu) */
    .control-list-wrapper {
        width: 100%;
    }

    /* Page Header */
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

    /* KPI Collapse */
    .kpi-summary-collapse {
        transition: all 0.3s ease;
    }
    .kpi-summary-collapse.is-collapsed {
        display: none !important;
    }
    .kpi-control-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    /* Form & Filter Cards */
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
    .form-card-body {
        padding: 16px 18px;
    }

    /* Table & Datatable styling - reports.php standartları */
    .form-card .table-responsive,
    .form-card .responsive {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
    }
    .responsive {
        overflow-x: hidden;
        overflow-y: visible;
        width: 100%;
        min-height: 280px;
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
        width: 220px !important;
        padding: 6px 12px !important;
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
</style>

<div class="pd-ltr-20 xs-pd-20-10">
    <div class="control-list-wrapper">
        <!-- Sayfa Üst Başlık ve Hızlı Aksiyonlar -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap px-1" style="gap: 12px;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-calendar-check-o"></i>
                </div>
                <div class="page-title-text">
                    <h4>Cihaz Kontrol & Geçerlilik Listesi</h4>
                    <p>Son geçerlilik ve kontrol tarihi gelmiş yangın söndürme cihazlarının periyodik takibi</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <a href="index.php?p=reports/reports" class="btn btn-outline-primary btn-action-outline" title="Tüm Raporlar">
                    <i class="fa fa-file-text-o"></i> <span class="d-none d-sm-inline">Rapor Listesi</span>
                </a>
                <a href="index.php?p=reports/filling-list" class="btn btn-outline-info btn-action-outline" title="Dolum Listesi">
                    <i class="fa fa-fire-extinguisher"></i> <span class="d-none d-sm-inline">Dolum Listesi</span>
                </a>
                <button type="button" class="btn btn-outline-secondary btn-action-outline" onclick="window.location.reload();" title="Listeyi Yenile">
                    <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
                </button>
                <button type="button" id="control_list-toxls-top" class="btn btn-success btn-action-outline" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; color: #fff;" title="Excel'e Aktar">
                    <i class="fa fa-file-excel-o"></i> <span>Excel'e Aktar</span>
                </button>
            </div>
        </div>

        <!-- Özet Bilgiler (CRM KPI Kartları) -->
        <div id="kpiSummarySection" class="row mx-0 mb-3 kpi-summary-collapse">
            <!-- Toplam Kontrol Kaydı -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Kontrol / Geçerlilik Kaydı</span>
                            <div class="crm-kpi-value"><?= number_format($totalCount, 0, ',', '.') ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-calendar-check-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Listelenen Kayıt</span>
                        <span class="crm-badge-soft soft-primary"><?= htmlspecialchars($selectedMonthName, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>

            <!-- Farklı Firma Sayısı -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">İlgili Müşteri / Firma</span>
                            <div class="crm-kpi-value"><?= number_format($uniqueCompanies, 0, ',', '.') ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-building-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Etkilenen İşletme</span>
                        <span class="crm-badge-soft soft-emerald">Farklı Firma</span>
                    </div>
                </div>
            </div>

            <!-- Rapor Sayısı -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Bağlı Rapor Sayısı</span>
                            <div class="crm-kpi-value"><?= number_format($uniqueReports, 0, ',', '.') ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-rose">
                            <i class="fa fa-file-text-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Kaynak Raporlar</span>
                        <span class="crm-badge-soft soft-rose">YSC Muayene</span>
                    </div>
                </div>
            </div>

            <!-- Seçili Dönem -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Seçili Filtre Dönemi</span>
                            <div class="crm-kpi-value" style="font-size: 19px;"><?= htmlspecialchars($selectedMonthName, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-amber">
                            <i class="fa fa-calendar"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Yıl: <strong class="text-warning"><?= htmlspecialchars($selectedYearName, ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <span class="crm-badge-soft soft-amber">Dönem</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtre Kartı -->
        <div class="form-card mb-3">
            <div class="form-card-header">
                <div class="header-left-inner">
                    <div class="card-icon">
                        <i class="fa fa-filter"></i>
                    </div>
                    <div>
                        <h5>Dönem ve Filtre Seçimi</h5>
                        <p>İncelemek istediğiniz kontrol ve muayene periyoduna ait ay ve yılı belirleyin</p>
                    </div>
                </div>
            </div>
            <div class="form-card-body">
                <form method="POST" action="index.php?p=reports/control-list" id="controlFilterForm">
                    <div class="row align-items-end">
                        <div class="col-lg-4 col-md-5 col-sm-12 mb-3 mb-md-0">
                            <label class="font-weight-600 font-12 text-muted mb-1 d-block"><i class="fa fa-calendar-o mr-1"></i> Kontrol Ayı</label>
                            <?= Date::getMonthSelect('control_month', $month) ?>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12 mb-3 mb-md-0">
                            <label class="font-weight-600 font-12 text-muted mb-1 d-block"><i class="fa fa-calendar mr-1"></i> Kontrol Yılı</label>
                            <?= Date::getYearSelect('control_year', $year) ?>
                        </div>
                        <div class="col-lg-4 col-md-3 col-sm-12">
                            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                <button type="submit" class="btn btn-action-primary flex-grow-1 justify-content-center">
                                    <i class="fa fa-search"></i> <span>Filtrele</span>
                                </button>
                                <a href="index.php?p=reports/control-list" class="btn btn-outline-secondary btn-action-outline" title="Bu Ay / Filtreyi Sıfırla">
                                    <i class="fa fa-undo"></i>
                                </a>
                                <button type="button" id="control_list-toxls" class="btn btn-outline-success btn-action-outline" title="Excel'e Aktar">
                                    <i class="fa fa-file-excel-o"></i> <span>Excel</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
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
                        <h5>Geçerlilik ve Kontrol Tarihi Gelenler</h5>
                        <p><?= htmlspecialchars($selectedMonthName . ' ' . $selectedYearName, ENT_QUOTES, 'UTF-8') ?> döneminde geçerliliği dolan ve kontrolü gereken kayıtlar</p>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <div class="dt-header-filter-box d-flex align-items-center"></div>
                    <button type="button" id="toggleKpiSummary" class="btn btn-outline-secondary btn-sm" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa fa-chevron-up"></i>
                    </button>
                </div>
            </div>

            <div class="responsive">
                <table id="controlTable" class="data-table table-hover table-bordered text-nowrap" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="w-10 text-nowrap text-center">ID</th>
                            <th>Firma Adı</th>
                            <th class="w-10 text-nowrap text-center">Rapor No</th>
                            <th>Geçerlilik Tarihi</th>
                            <th class="text-center" style="width: 70px;">Ay</th>
                            <th class="text-center" style="width: 70px;">Yıl</th>
                            <th class="text-center" style="width: 80px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        foreach ($controlList as $list) : 
                            $repId = (int)($list->report_id ?? 0);
                            $repNum = htmlspecialchars($list->report_number ?? '-', ENT_QUOTES, 'UTF-8');
                            $firmName = htmlspecialchars($list->firma_adi ?? '-', ENT_QUOTES, 'UTF-8');
                            $validityDate = htmlspecialchars($list->validity_date ?? '-', ENT_QUOTES, 'UTF-8');
                            $ay = htmlspecialchars($list->ay ?? '-', ENT_QUOTES, 'UTF-8');
                            $yil = htmlspecialchars($list->yil ?? '-', ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td class="text-center font-weight-600 text-muted"><?= $i ?></td>
                                <td><?= $firmName ?></td>
                                <td class="text-center">
                                    <?php if ($repId > 0) { ?>
                                        <a href="index.php?p=reports/ysc/report-view-ysc&id=<?= $repId ?>" target="_blank" class="text-primary font-weight-bold" title="Raporu Görüntüle"><?= $repNum ?></a>
                                    <?php } else { ?>
                                        <?= $repNum ?>
                                    <?php } ?>
                                </td>
                                <td><?= $validityDate ?></td>
                                <td class="text-center"><?= $ay ?></td>
                                <td class="text-center"><?= $yil ?></td>
                                <td class="text-center">
                                    <?php if ($repId > 0) { ?>
                                        <a href="index.php?p=reports/ysc/report-view-ysc&id=<?= $repId ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 6px; padding: 3px 8px;" title="Rapor Detayını Görüntüle">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    <?php } else { ?>
                                        -
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php 
                            $i++; 
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // DataTable Başlatma
    if ($("#controlTable").length) {
        var cTable = $("#controlTable").DataTable({
            autoWidth: false,
            responsive: true,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tümü"]
            ],
            language: {
                url: "include/js/tr.json"
            },
            stateSave: true,
            order: [[0, "asc"]],
            initComplete: function () {
                var api = this.api();

                // Arama kutusunu Form Card Header içine taşıma
                var $filterContainer = $('.form-card-header .dt-header-filter-box');
                var $searchBox = $('#controlTable_filter');
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

    // KPI Özet Bölümü Aç/Kapa
    $('html').removeClass('kpi-control-collapsed-early');
    var isKpiCollapsed = localStorage.getItem('aydinogullari_kpi_control_collapsed') === 'true';
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
            localStorage.setItem('aydinogullari_kpi_control_collapsed', 'true');
        } else {
            $kpi.removeClass('is-collapsed');
            $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $(this).attr('title', 'Özet Kartlarını Gizle');
            localStorage.setItem('aydinogullari_kpi_control_collapsed', 'false');
        }
    });

    // Excel Dışa Aktarma
    $(document).on('click', '#control_list-toxls, #control_list-toxls-top', function() {
        var month = $('#control_month').val() || '';
        var year = $('#control_year').val() || '';
        var url = 'pages/1/reports/ysc/control-list-export-toxls.php?month=' + encodeURIComponent(month) + '&year=' + encodeURIComponent(year);
        window.location.href = url;
    });
});
</script>