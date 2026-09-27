<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';

use App\Model\CustomerModel;
use App\Model\ReportsModel;

if (!isset($_SESSION['login']) || (!permtrue('customeredit') && !permtrue('reportview'))) {
    http_response_code(403);
    exit('<div class="alert alert-danger font-13 m-3"><i class="fa fa-lock mr-2"></i>Bu içeriği görüntüleme yetkiniz bulunmamaktadır.</div>');
}

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    exit('<div class="alert alert-warning font-13 m-3">Geçersiz müşteri kimliği.</div>');
}

$Customer = new CustomerModel();
$customer = $Customer->find($id);
if (!$customer) {
    exit('<div class="alert alert-warning font-13 m-3">Müşteri kaydı bulunamadı.</div>');
}

$reportsModel = new ReportsModel();
$reportSummaryData = $reportsModel->getCustomerReportSummary($id);
$customerReports = $reportSummaryData['reports'] ?? [];
$customerReportsSummary = $reportSummaryData['summary'] ?? [];
?>
<style>
    .icmal-kpi-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 15px !important;
        width: 100% !important;
        margin-bottom: 20px !important;
    }
    @media (max-width: 992px) {
        .icmal-kpi-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
    @media (max-width: 576px) {
        .icmal-kpi-grid {
            grid-template-columns: 1fr !important;
        }
    }
    .icmal-kpi-card {
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 16px !important;
        display: flex !important;
        align-items: center !important;
        gap: 14px !important;
        transition: all 0.2s ease !important;
    }
    .icmal-kpi-card:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        transform: translateY(-2px) !important;
    }
    .icmal-kpi-icon {
        width: 48px !important;
        height: 48px !important;
        border-radius: 10px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 20px !important;
        flex-shrink: 0 !important;
    }
    .icmal-kpi-info {
        flex-grow: 1 !important;
        min-width: 0 !important;
    }
    .icmal-kpi-title {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        margin-bottom: 4px !important;
    }
    .icmal-kpi-value {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    .icmal-kpi-sub {
        font-size: 11px !important;
        color: #94a3b8 !important;
        margin-top: 3px !important;
    }
</style>

<div class="form-card animate-fade-in" id="customerReportsIcmalCard">
    <!-- Header / Action Toolbar -->
    <div class="form-card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 12px; padding: 16px 20px;">
        <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
            <div class="card-icon" style="background: rgba(239, 68, 68, 0.12); color: #dc2626; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                <i class="fa fa-file-text-o"></i>
            </div>
            <div>
                <h5 class="mb-0 font-16 weight-600">Firma Rapor İcmali & Ekipman Dökümü</h5>
                <p class="mb-0 text-muted font-12">İcmale dahil etmek istediğiniz kontrol ve test raporlarını seçip resmi Firma Rapor & Ekipman İcmal Formu çıktısı alabilirsiniz.</p>
            </div>
            <div class="d-flex align-items-center" style="gap: 6px;">
                <span class="badge badge-danger font-12 py-1 px-2 border-radius-5" id="icmalReportCountBadge"><?= count($customerReports) ?> Rapor</span>
                <span class="badge badge-info font-12 py-1 px-2 border-radius-5" id="icmalReportItemsBadge"><?= (int)($customerReportsSummary['total_items'] ?? 0) ?> Ekipman / Kalem</span>
                <span class="badge badge-success font-12 py-1 px-2 border-radius-5" id="icmalReportSelectedCountBadge"><?= count($customerReports) ?> Seçili</span>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnReloadReportsIcmal" title="Rapor İcmalini Yeniden Yükle" style="border-radius: 8px; height: 35px; font-weight: 500;">
                <i class="fa fa-refresh mr-1"></i> Yenile
            </button>

            <button type="button" class="btn btn-sm btn-outline-info" id="btnToggleAllReportRows" title="Tüm Rapor Kalemlerini Genişlet / Daralt" style="border-radius: 8px; height: 35px; font-weight: 500;">
                <i class="fa fa-expand mr-1"></i> Tümünü Aç
            </button>

            <!-- Resmi İcmal Yazdır Butonu -->
            <button type="button" class="btn btn-sm btn-danger" id="btnOpenOfficialReportIcmal" title="Seçilen Raporları Resmi İcmal Formatında Yazdır" style="border-radius: 8px; height: 35px; font-weight: 600; box-shadow: 0 2px 6px rgba(239,68,68,0.3);">
                <i class="fa fa-print mr-1"></i> İcmal Yazdır
            </button>

            <?php if (permtrue('reportview')): ?>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportOfficialReportIcmalExcel()" title="Seçilen Raporları ve Alt Ekipmanlarını Excel'e Aktar" style="border-radius: 8px; height: 35px; font-weight: 600;">
                <i class="fa fa-file-excel-o mr-1"></i> Excel İndir
            </button>
            <?php endif; ?>

            <?php if (permtrue("reportnew")): ?>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="border-radius: 8px; height: 35px; display: inline-flex; align-items: center; font-weight: 500;">
                    <i class="fa fa-plus mr-1"></i> Yeni Rapor Oluştur
                </button>
                <div class="dropdown-menu dropdown-menu-right" style="border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; padding: 6px;">
                    <a class="dropdown-item py-2 px-3 font-13" href="index.php?p=reports/ysc/report-new-ysc&cid=<?= $id ?>" target="_blank">
                        <i class="fa fa-fire-extinguisher mr-2 text-danger"></i> Yangın Söndürme Tüpü Raporu (YSC)
                    </a>
                    <a class="dropdown-item py-2 px-3 font-13" href="index.php?p=reports/hst/report-new-hst&cid=<?= $id ?>" target="_blank">
                        <i class="fa fa-flask mr-2 text-primary"></i> Hidrostatik Test Raporu (HST)
                    </a>
                    <a class="dropdown-item py-2 px-3 font-13" href="index.php?p=reports/met/report-new-met&cid=<?= $id ?>" target="_blank">
                        <i class="fa fa-cogs mr-2 text-purple"></i> Mekanik Tesisat Raporu (MET)
                    </a>
                    <a class="dropdown-item py-2 px-3 font-13" href="index.php?p=reports/yas/report-new-yas&cid=<?= $id ?>" target="_blank">
                        <i class="fa fa-bell-o mr-2 text-warning"></i> Yangın Algılama Raporu (YAS)
                    </a>
                    <a class="dropdown-item py-2 px-3 font-13" href="index.php?p=reports/oys/report-new-oys&cid=<?= $id ?>" target="_blank">
                        <i class="fa fa-snowflake-o mr-2 text-info"></i> Otomatik Söndürme Raporu (OYS)
                    </a>
                    <a class="dropdown-item py-2 px-3 font-13" href="index.php?p=reports/aas/report-new-aas&cid=<?= $id ?>" target="_blank">
                        <i class="fa fa-lightbulb-o mr-2 text-success"></i> Acil Aydınlatma Raporu (AAS)
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="p-3">
        <!-- KPI Kartları Grid -->
        <div id="reportIcmalKpiContainer" class="mb-20">
            <div class="icmal-kpi-grid">
                <!-- 1. Toplam Rapor & Ekipman -->
                <div class="icmal-kpi-card">
                    <div class="icmal-kpi-icon" style="background: #fee2e2; color: #dc2626;">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <div class="icmal-kpi-info">
                        <div class="icmal-kpi-title">Toplam Rapor</div>
                        <div class="icmal-kpi-value" id="kpiTotalReports"><?= (int)($customerReportsSummary['total_reports'] ?? 0) ?> Adet</div>
                        <div class="icmal-kpi-sub"><?= (int)($customerReportsSummary['total_items'] ?? 0) ?> Ekipman / Kalem</div>
                    </div>
                </div>

                <!-- 2. Yangın Söndürme Tüpleri (YSC) -->
                <div class="icmal-kpi-card">
                    <div class="icmal-kpi-icon" style="background: #fef2f2; color: #ef4444;">
                        <i class="fa fa-fire-extinguisher"></i>
                    </div>
                    <div class="icmal-kpi-info">
                        <div class="icmal-kpi-title">YSC Raporları</div>
                        <div class="icmal-kpi-value"><?= (int)($customerReportsSummary['ysc_reports_count'] ?? 0) ?> Adet</div>
                        <div class="icmal-kpi-sub"><?= (int)($customerReportsSummary['ysc_items_count'] ?? 0) ?> Yangın Tüpü</div>
                    </div>
                </div>

                <!-- 3. Hidrostatik Basınç Testi (HST) -->
                <div class="icmal-kpi-card">
                    <div class="icmal-kpi-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fa fa-flask"></i>
                    </div>
                    <div class="icmal-kpi-info">
                        <div class="icmal-kpi-title">Hidrostatik Test (HST)</div>
                        <div class="icmal-kpi-value"><?= (int)($customerReportsSummary['hst_reports_count'] ?? 0) ?> Adet</div>
                        <div class="icmal-kpi-sub"><?= (int)($customerReportsSummary['hst_items_count'] ?? 0) ?> Test Edilen Tüp</div>
                    </div>
                </div>

                <!-- 4. Diğer Sistem Raporları (MET/YAS/OYS/AAS) -->
                <div class="icmal-kpi-card">
                    <div class="icmal-kpi-icon" style="background: #f5f3ff; color: #8b5cf6;">
                        <i class="fa fa-cogs"></i>
                    </div>
                    <div class="icmal-kpi-info">
                        <div class="icmal-kpi-title">Diğer Sistem Raporları</div>
                        <div class="icmal-kpi-value"><?= (int)($customerReportsSummary['other_reports_count'] ?? 0) ?> Adet</div>
                        <div class="icmal-kpi-sub"><?= (int)($customerReportsSummary['other_items_count'] ?? 0) ?> Tesisat / Eleman</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtreleme & Arama Araç Çubuğu -->
        <div class="icmal-filter-toolbar mb-3">
            <div class="row align-items-center g-2">
                <div class="col-lg-4 col-md-5 mb-2 mb-md-0">
                    <div class="d-flex align-items-center" style="gap: 8px;">
                        <label class="mb-0 font-12 weight-600 text-secondary text-nowrap" for="icmalReportTypeFilter">
                            <i class="fa fa-filter mr-1"></i> Rapor Türü:
                        </label>
                        <select id="icmalReportTypeFilter" class="form-control select2" style="width: 100%;">
                            <option value="all">Tüm Rapor Türleri</option>
                            <option value="ysc">Yangın Söndürme Tüpü (YSC)</option>
                            <option value="hst">Hidrostatik Test Raporu (HST)</option>
                            <option value="met">Mekanik Tesisat Raporu (MET)</option>
                            <option value="yas">Yangın Algılama (YAS)</option>
                            <option value="oys">Otomatik Söndürme (OYS)</option>
                            <option value="aas">Acil Aydınlatma (AAS)</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-8 col-md-7">
                    <div class="d-flex align-items-center justify-content-md-end" style="gap: 8px;">
                        <div class="input-group" style="max-width: 380px;">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1;">
                                    <i class="fa fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" id="icmalReportSearchInput" class="form-control border-left-0" placeholder="Rapor No, İş Emri, Cihaz No, Kontrolör ara..." style="border-radius: 0 8px 8px 0; border-color: #cbd5e1; font-size: 13px;">
                        </div>

                        <!-- Standart Kural: Arama Kutusunun Sağında KPI Daraltma Butonu -->
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-kpi-toggle" id="toggleReportIcmalKpi" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; height: 36px; width: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa fa-chevron-up font-12"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($customerReports)): ?>
            <div class="alert alert-light text-center border p-4" style="border-radius: 12px; background: #f8fafc;">
                <div style="font-size: 36px; color: #94a3b8;" class="mb-2"><i class="fa fa-file-text-o"></i></div>
                <h6 class="weight-600 text-secondary mb-1">Kayıtlı Rapor Bulunamadı</h6>
                <p class="text-muted font-13 mb-3">Bu firmaya ait henüz kaydedilmiş herhangi bir kontrol veya test raporu bulunmamaktadır.</p>
                <?php if (permtrue("reportnew")): ?>
                <a href="index.php?p=reports/ysc/report-new-ysc&cid=<?= $id ?>" target="_blank" class="btn btn-sm btn-danger font-13" style="border-radius: 8px;">
                    <i class="fa fa-plus mr-1"></i> İlk Raporu Oluştur
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Master Detail Rapor Tablosu -->
            <div class="table-responsive">
                <table class="table table-icmal table-report-icmal mb-0">
                    <thead>
                        <tr>
                            <th style="width: 38px;" class="text-center">
                                <input type="checkbox" id="selectAllReports" checked title="Tümünü Seç / Kaldır">
                            </th>
                            <th style="width: 38px;" class="text-center"></th>
                            <th style="width: 140px;">Rapor No</th>
                            <th>Rapor Türü</th>
                            <th style="width: 110px;" class="text-center">İş Emri No</th>
                            <th style="width: 120px;" class="text-center">Kontrol Tarihi</th>
                            <th style="width: 120px;" class="text-center">Geçerlilik Tarihi</th>
                            <th style="width: 110px;" class="text-center">Ekipman</th>
                            <th style="width: 130px;" class="text-center">Kayıt Tarihi</th>
                            <th style="width: 130px;">Kontrolör</th>
                            <th style="width: 120px;" class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $runningReportItemsTotal = 0;
                        foreach ($customerReports as $rep): 
                            $rid = (int)$rep['id'];
                            $pageLink = strtolower(trim((string)($rep['page_link'] ?? 'ysc')));
                            $reportType = (int)($rep['report_type'] ?? 1);
                            $reportNoClean = $rep['report_number'] ?? ('REP-' . $rid);
                            $reportName = $rep['report_name'] ?? 'Rapor';
                            $itemCount = count($rep['items'] ?? []);
                            $runningReportItemsTotal += $itemCount;

                            // Rapor Türü Rozet Rengi
                            $badgeClass = 'badge-secondary';
                            $typeIcon = 'fa-file-text-o';
                            if ($pageLink === 'ysc' || $reportType === 1) {
                                $badgeClass = 'badge-danger';
                                $typeIcon = 'fa-fire-extinguisher';
                            } elseif ($pageLink === 'hst' || $reportType === 2) {
                                $badgeClass = 'badge-primary';
                                $typeIcon = 'fa-flask';
                            } elseif ($pageLink === 'met' || $reportType === 3) {
                                $badgeClass = 'badge-info';
                                $typeIcon = 'fa-cogs';
                            } elseif ($pageLink === 'yas' || $reportType === 4) {
                                $badgeClass = 'badge-warning';
                                $typeIcon = 'fa-bell-o';
                            } elseif ($pageLink === 'oys' || $reportType === 5) {
                                $badgeClass = 'badge-dark';
                                $typeIcon = 'fa-snowflake-o';
                            } elseif ($pageLink === 'aas' || $reportType === 6) {
                                $badgeClass = 'badge-success';
                                $typeIcon = 'fa-lightbulb-o';
                            }

                            // Tarih formatlama
                            $createTimeRaw = $rep['create_time'] ?? '';
                            $createDateFormatted = '-';
                            $createTimeFormatted = '';
                            if ($createTimeRaw) {
                                $ts = strtotime($createTimeRaw);
                                if ($ts) {
                                    $createDateFormatted = date('d.m.Y', $ts);
                                    $createTimeFormatted = date('H:i', $ts);
                                }
                            }

                            $viewLink = "index.php?p=reports/{$pageLink}/report-view-{$pageLink}&id={$rid}";
                            $editFile = ($pageLink === 'yas') ? 'report-new-' : 'report-edit-';
                            $editLink = "index.php?p=reports/{$pageLink}/{$editFile}{$pageLink}&id={$rid}";
                            $mailLink = "index.php?p=report-send-as-mail&type={$pageLink}&id={$rid}";
                        ?>
                            <!-- Ana Rapor Satırı (Master) -->
                            <tr class="report-row" 
                                data-report-id="<?= $rid ?>" 
                                data-report-type="<?= $reportType ?>"
                                data-page-link="<?= htmlspecialchars($pageLink, ENT_QUOTES, 'UTF-8') ?>"
                                data-items="<?= $itemCount ?>"
                                data-report-number="<?= htmlspecialchars($reportNoClean, ENT_QUOTES, 'UTF-8') ?>"
                                data-report-name="<?= htmlspecialchars($reportName, ENT_QUOTES, 'UTF-8') ?>"
                                data-isemrino="<?= htmlspecialchars($rep['isemrino'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-control-date="<?= htmlspecialchars($rep['control_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-validity-date="<?= htmlspecialchars($rep['validity_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-controller="<?= htmlspecialchars($rep['controller_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                
                                <td class="text-center" onclick="event.stopPropagation();">
                                    <input type="checkbox" class="report-select-cb" checked 
                                           data-report-id="<?= $rid ?>"
                                           data-report-number="<?= htmlspecialchars($reportNoClean, ENT_QUOTES, 'UTF-8') ?>"
                                           data-report-name="<?= htmlspecialchars($reportName, ENT_QUOTES, 'UTF-8') ?>"
                                           data-isemrino="<?= htmlspecialchars($rep['isemrino'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           data-control-date="<?= htmlspecialchars($rep['control_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           data-validity-date="<?= htmlspecialchars($rep['validity_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           data-items="<?= $itemCount ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn-expand-row btn-expand-report-row" title="Ekipman ve Kalem Dökümünü Göster/Gizle">
                                        <i class="fa fa-chevron-right font-10"></i>
                                    </button>
                                </td>
                                <td>
                                    <a href="<?= htmlspecialchars($viewLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="font-weight-bold text-danger" style="text-decoration: none;">
                                        <?= htmlspecialchars($reportNoClean, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?> font-11 py-1 px-2 border-radius-5">
                                        <i class="fa <?= $typeIcon ?> mr-1"></i><?= htmlspecialchars($reportName, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="font-weight-500 text-dark"><?= !empty($rep['isemrino']) ? htmlspecialchars($rep['isemrino'], ENT_QUOTES, 'UTF-8') : '-' ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="text-muted font-12"><i class="fa fa-calendar-o mr-1"></i><?= !empty($rep['control_date']) ? htmlspecialchars($rep['control_date'], ENT_QUOTES, 'UTF-8') : '-' ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="text-muted font-12"><i class="fa fa-calendar-check-o mr-1"></i><?= !empty($rep['validity_date']) ? htmlspecialchars($rep['validity_date'], ENT_QUOTES, 'UTF-8') : '-' ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light border font-11 font-weight-bold"><?= $itemCount ?> Adet</span>
                                </td>
                                <td class="text-center font-12" style="line-height: 1.25;">
                                    <span class="text-dark font-weight-500"><?= $createDateFormatted ?></span>
                                    <?php if ($createTimeFormatted): ?>
                                        <br><span class="text-muted font-11"><?= $createTimeFormatted ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($rep['controller_name'])): ?>
                                        <span class="font-weight-500 text-dark font-12"><i class="fa fa-user-circle text-secondary mr-1"></i><?= htmlspecialchars($rep['controller_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted font-12">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center action-cell" onclick="event.stopPropagation();">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= htmlspecialchars($viewLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-danger btn-sm" title="Raporu Görüntüle">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="<?= htmlspecialchars($editLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Düzenle">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        <a href="<?= htmlspecialchars($mailLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-info btn-sm" title="E-Posta Gönder">
                                            <i class="fa fa-envelope-o"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Detay Kalem Satırı (Accordion Child) -->
                            <tr class="report-details-row" id="report-details-<?= $rid ?>" style="display: none;">
                                <td colspan="11" class="p-0 border-0">
                                    <div class="details-subtable-wrapper">
                                        <div class="d-flex align-items-center justify-content-between mb-10">
                                            <strong class="font-12 text-secondary">
                                                <i class="fa fa-list-ul mr-1 text-danger"></i> <?= htmlspecialchars($reportNoClean, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($reportName, ENT_QUOTES, 'UTF-8') ?> Ekipman Dökümü (<?= $itemCount ?> Adet)
                                            </strong>
                                            <span class="font-12 text-muted">
                                                İş Emri: <strong class="text-dark"><?= !empty($rep['isemrino']) ? htmlspecialchars($rep['isemrino'], ENT_QUOTES, 'UTF-8') : '-' ?></strong>
                                                &nbsp;|&nbsp; Kontrol: <strong class="text-dark"><?= !empty($rep['control_date']) ? htmlspecialchars($rep['control_date'], ENT_QUOTES, 'UTF-8') : '-' ?></strong>
                                            </span>
                                        </div>

                                        <?php if (empty($rep['items'])): ?>
                                            <div class="alert alert-light border font-12 py-2 mb-0">Bu rapora ait alt ekipman/test kalemi bulunmamaktadır.</div>
                                        <?php else: ?>
                                            <table class="subtable-items">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 35px;" class="text-center">#</th>
                                                        <?php if ($pageLink === 'ysc' || $reportType === 1): ?>
                                                            <th style="width: 100px;">Cihaz No</th>
                                                            <th>Bulunduğu Bölge</th>
                                                            <th style="width: 140px;">Cihaz Cinsi</th>
                                                            <th style="width: 100px;" class="text-center">Dolum Trh</th>
                                                            <th style="width: 100px;" class="text-center">Son Kull. Trh</th>
                                                            <th style="width: 100px;" class="text-center">1. Kontrol</th>
                                                            <th style="width: 100px;" class="text-center">2. Kontrol</th>
                                                            <th style="width: 140px;" class="text-center">Dış Muhafaza / Pim</th>
                                                        <?php elseif ($pageLink === 'hst' || $reportType === 2): ?>
                                                            <th style="width: 90px;" class="text-center">Test No</th>
                                                            <th style="width: 70px;" class="text-center">KG</th>
                                                            <th>Cihaz Cinsi</th>
                                                            <th style="width: 140px;">İmalatçı Firma</th>
                                                            <th style="width: 90px;" class="text-center">İmal Tarihi</th>
                                                            <th style="width: 100px;">Seri No</th>
                                                            <th style="width: 90px;" class="text-center">Sızdırmazlık</th>
                                                            <th style="width: 90px;" class="text-center">Esneme Deneyi</th>
                                                        <?php elseif ($pageLink === 'met' || $reportType === 3): ?>
                                                            <th>Cinsi</th>
                                                            <th>Bulunduğu Kısım</th>
                                                            <th>Özellikler</th>
                                                            <th style="width: 110px;" class="text-center">Kontrol Trh</th>
                                                            <th style="width: 110px;" class="text-center">Sonraki Kontrol</th>
                                                            <th style="width: 90px;" class="text-center">Basınç</th>
                                                        <?php else: ?>
                                                            <th>Algılama / Sistem Cinsi</th>
                                                            <th>Bulunduğu Bölge</th>
                                                            <th style="width: 120px;" class="text-center">Çevre Kontrolü</th>
                                                            <th style="width: 120px;" class="text-center">Dış Muhafaza</th>
                                                            <th style="width: 140px;" class="text-center">Çalışabilirlik</th>
                                                        <?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($rep['items'] as $itemIndex => $item): ?>
                                                        <tr>
                                                            <td class="text-center text-muted"><?= $itemIndex + 1 ?></td>
                                                            <?php if ($pageLink === 'ysc' || $reportType === 1): ?>
                                                                <td><span class="badge badge-light border font-11 font-weight-bold"><?= htmlspecialchars($item['cihaz_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                                                <td class="font-weight-500 text-dark"><?= htmlspecialchars($item['bulundugu_bolge'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars($item['cinsi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center text-muted"><?= htmlspecialchars($item['cihaz_dolum_tarihi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center text-muted"><?= htmlspecialchars($item['cihaz_sonkullanma_tarihi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center"><?= htmlspecialchars($item['kontrol_tarihi_1'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center"><?= htmlspecialchars($item['kontrol_tarihi_2'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center font-11"><span class="badge badge-success">Uygun</span></td>
                                                            <?php elseif ($pageLink === 'hst' || $reportType === 2): ?>
                                                                <td class="text-center font-weight-bold"><?= htmlspecialchars($item['testno'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center"><?= htmlspecialchars($item['kg'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="font-weight-500 text-dark"><?= htmlspecialchars($item['cinsi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars($item['imalatci_firma'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center text-muted"><?= htmlspecialchars($item['imal_tarihi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><span class="badge badge-light border font-11"><?= htmlspecialchars($item['serino'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                                                <td class="text-center"><?= ($item['sizdirmazlik_deneyi'] ?? '') == '1' ? '<span class="badge badge-success font-11">Geçti</span>' : '<span class="badge badge-danger font-11">Kaldı</span>' ?></td>
                                                                <td class="text-center"><?= ($item['esneme_deneyi'] ?? '') == '1' ? '<span class="badge badge-success font-11">Geçti</span>' : '<span class="badge badge-danger font-11">Kaldı</span>' ?></td>
                                                            <?php elseif ($pageLink === 'met' || $reportType === 3): ?>
                                                                <td class="font-weight-500 text-dark"><?= htmlspecialchars($item['cinsi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars($item['bulundugu_kisim'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-muted"><?= htmlspecialchars($item['ozellikler'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center"><?= htmlspecialchars($item['control_date_closet'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center"><?= htmlspecialchars($item['next_control_date_closet'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center font-weight-bold"><?= htmlspecialchars($item['basinc_degeri'] ?? '-', ENT_QUOTES, 'UTF-8') ?> Bar</td>
                                                            <?php else: ?>
                                                                <td class="font-weight-500 text-dark"><?= htmlspecialchars($item['algilama_cinsi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars($item['bulundugu_bolge'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center"><span class="badge badge-success">Uygun</span></td>
                                                                <td class="text-center"><span class="badge badge-success">Uygun</span></td>
                                                                <td class="text-center"><?= ($item['calisabilirlik_testi'] ?? 1) == 1 ? '<span class="badge badge-success">Çalışır</span>' : '<span class="badge badge-danger">Kusurlu</span>' ?></td>
                                                            <?php endif; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot style="background: #f8fafc; border-top: 2px solid #cbd5e1;">
                        <tr class="font-weight-bold">
                            <td colspan="5" class="text-right font-13 text-uppercase text-secondary py-12">
                                SEÇİLEN VE FİLTRELENEN RAPOR TOPLAMI:
                            </td>
                            <td colspan="2" class="text-center font-13 text-dark" id="icmalReportFooterItems">
                                <?= $runningReportItemsTotal ?> Ekipman / Kalem
                            </td>
                            <td colspan="3" class="text-right font-12 text-muted">
                                <span id="icmalReportFooterCount"><?= count($customerReports) ?></span> Rapor Seçili
                            </td>
                            <td class="action-cell"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Rapor İcmali Dipnot Akordiyon Kartı -->
            <div class="card mt-3 mb-2 border-radius-10 border icmal-accordion-card" style="border-color: #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                <div class="card-header d-flex align-items-center justify-content-between p-3" 
                     style="background: #ffffff; cursor: pointer; border-bottom: 1px solid #e2e8f0;" 
                     data-toggle="collapse" data-target="#collapseReportIcmalNotes" aria-expanded="false" aria-controls="collapseReportIcmalNotes">
                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                            <i class="fa fa-pencil-square-o"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 font-14 weight-600 text-dark">Rapor İcmali Dipnot / Şartlar ve Açıklamalar</h6>
                            <small class="text-muted font-11">İcmal çıktısının altında yer almasını istediğiniz özel not ve açıklamaları buraya yazabilirsiniz.</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <span class="badge badge-light border font-11 text-muted">Açıklama / Dipnot</span>
                        <i class="fa fa-chevron-down font-12 text-muted accordion-chevron"></i>
                    </div>
                </div>
                <div id="collapseReportIcmalNotes" class="collapse">
                    <div class="card-body p-3" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
                        <div class="form-group mb-0">
                            <label class="weight-600 font-12 mb-1 text-secondary" for="reportIcmalNotesContent">İcmal Belgesi Alt Dipnot Metni</label>
                            <textarea id="reportIcmalNotesContent" name="reportIcmalNotesContent" class="form-control" rows="3" placeholder="Yukarıda künyesi belirtilen cihazların kontrol ve muayene işlemleri TS ISO 11602-2 ve ilgili standartlara uygun olarak tamamlanmıştır."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
