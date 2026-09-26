<?php
// require_once "App/Helper/date.php";
// require_once "App/Model/OfferModel.php";

use App\Helper\Date;
use App\Model\OfferModel;

$ois = @$_GET["id"];
$cid = @$_GET["cid"];
$sablonlari_goster = isset($_GET["sablon"]) ? true : false;
if($sablonlari_goster){
    $sayfa_basligi = "Şablon Teklifler";
}else{
    $sayfa_basligi = "Teklifleri Görüntüle";
};

$OfferModel = new OfferModel();


if ($sablonlari_goster) {
    $stmtTpl = $ac->query("SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(tl_toplam_karsilik), SUM(total_price), 0) as total_amount
    FROM offers WHERE is_template = 1");
    $tplData = $stmtTpl ? $stmtTpl->fetch(PDO::FETCH_OBJ) : null;
    $totalOffersCount = (int)($tplData->total_count ?? 0);
    $totalOffersSum = (float)($tplData->total_amount ?? 0);
    $pendingOffersCount = 0;
    $pendingOffersSum = 0;
    $wonOffersCount = 0;
    $wonOffersSum = 0;
    $offerWinRate = 0;
    $thisMonthOffersCount = 0;
    $thisMonthOffersSum = 0;
} else {
    $offerSummary = $OfferModel->getDashboardSummary(null, null);
    $totalOffersCount = (int)($offerSummary->total_count ?? 0);
    $totalOffersSum = (float)($offerSummary->total_amount ?? 0);
    $pendingOffersCount = (int)($offerSummary->pending_count ?? 0);
    $pendingOffersSum = (float)($offerSummary->pending_amount ?? 0);
    $wonOffersCount = (int)($offerSummary->won_count ?? 0);
    $wonOffersSum = (float)($offerSummary->won_amount ?? 0);
    $offerWinRate = (float)($offerSummary->win_rate ?? 0);
    $thisMonthOffersCount = (int)($offerSummary->this_month->count ?? 0);
    $thisMonthOffersSum = (float)($offerSummary->this_month->amount ?? 0);
}


if (@$_GET["st"] == "offercopy") {
    $creator = sesset("lid");
    $newoffernumber = "TK" . newNumber("offers");
    $ofcopy = $ac->prepare("INSERT INTO offers (offerNumber, cid, company_authors,
											   total_price,mycompany,
									   		   authors,reg_date,tax,creativer,
											   notes,currency,statu,
											   dollar,euro,payment_period,
											   offer_header,offer_header_content,
											   offer_footer,offer_footer_content,
											   description,
											   file, kdv,iskonto, subdescription,
											   buyTotal,saleTotal,amounttotal,
											   curDollar,curEuro,
											   DolarTotal ,EuroTotal ,TLTotal ) 
										SELECT ? , cid, company_authors,
											   total_price,mycompany,
											   authors,reg_date,tax,?,
											   notes,currency,statu,
											   dollar,euro,payment_period,
											   offer_header,offer_header_content,
											   offer_footer,offer_footer_content,
											   description,
											   file, kdv, iskonto, subdescription,
											   buyTotal,saleTotal,amounttotal,
											   curDollar,curEuro,
											   DolarTotal ,EuroTotal ,TLTotal
						   FROM offers	WHERE id = ?;");
    $ofcopy->execute(array($newoffernumber, $creator, $ois));
    $lastid = $ac->lastInsertId();

    $offmat = $ac->prepare("SELECT * FROM offermatters WHERE oid = ?");
    $offmat->execute(array($ois));

    $items = $offmat->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $insq = $ac->prepare("INSERT INTO offermatters SET xid = ?,oid = ?,stokKodu = ? , 
														   title = ? ,unit = ? ,amount = ? , 
														   buyprice = ? ,buycur = ?,saleprice = ? ,
														   salecur = ? ,total_price = ?");

        $insq->execute(
            array(
                $item["xid"],
                $lastid,
                $item["stokKodu"],
                $item["title"],
                $item["unit"],
                $item["amount"],
                $item["buyprice"],
                $item["buycur"],
                $item["saleprice"],
                $item["salecur"],
                $item["total_price"]
            )
        );
    }
}

///
if (@$_GET["st"] == "success-mail") {
    showAlert("success", "Mail başarı ile gönderildi!");
}

?>

<style>
    /* Premium offer list page styles */
    .offer-list-wrapper {
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

    .form-card .filters-form {
        padding: 16px 18px 0 18px;
    }

    .form-card .responsive {
        padding: 4px !important;
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

    .responsive {
        overflow-x: hidden;
        overflow-y: visible;
        width: 100%;
        min-height: 280px;
    }

    .filters-form .form-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }

    .filters-form .form-control,
    .filters-form .bootstrap-select .btn {
        border-radius: 8px !important;
        border: 1.5px solid #e5e7eb !important;
        padding: 8px 12px;
        font-size: 13.5px;
        background: #fafafa;
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
    .dark-mode .filters-form .form-label {
        color: #c4cdd8 !important;
    }
    .dark-mode .filters-form .form-control,
    .dark-mode .filters-form .bootstrap-select .btn {
        background: #1e1e1e !important;
        color: #e2e8f0 !important;
        border-color: #383838 !important;
    }
    .dark-mode .filters-form .bootstrap-select .btn .filter-option-inner-inner {
        color: #e2e8f0 !important;
    }
    .dark-mode #clearFilters.btn-outline-secondary {
        background-color: #383838 !important;
        color: #e2e8f0 !important;
        border-color: #4f4f50 !important;
    }
    .dark-mode #clearFilters.btn-outline-secondary:hover {
        background-color: #484848 !important;
    }
    .dark-mode .data-table .form-control {
        background: #1e1e1e !important;
        color: #e2e8f0 !important;
        border-color: #383838 !important;
    }
</style>

<div class="pd-ltr-20 xs-pd-20-10">
    <div class="offer-list-wrapper">
    <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap px-1" style="gap: 12px;">
        <div class="page-title-box">
            <div class="page-title-icon">
                <i class="fa fa-file-text-o"></i>
            </div>
            <div class="page-title-text">
                <h4><?php echo $sablonlari_goster ? 'Şablon Teklifler' : 'Teklif Yönetimi'; ?></h4>
                <p><?php echo $sablonlari_goster ? 'Sistemde kayıtlı şablon tekliflerin listesi ve yönetimi' : 'Sistemdeki tüm teklifler, onay süreçleri ve durum takibi'; ?></p>
            </div>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <?php if (permtrue("offers_dashboard") || permtrue("offersView") || permtrue("offerView")) { ?>
                <a href="index.php?p=offers/dashboard" class="btn btn-outline-primary btn-action-outline" title="Dashboard">
                    <i class="fa fa-dashboard"></i> <span class="d-none d-sm-inline">Dashboard</span>
                </a>
            <?php } ?>
            <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshOffers" title="Tabloyu Yenile">
                <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
            </button>
            <?php if (permtrue("data_export_offers")) { ?>
                <button type="button" class="btn btn-outline-success btn-action-outline" id="exportExcel" title="Excel Olarak İndir">
                    <i class="fa fa-file-excel-o"></i> <span class="d-none d-sm-inline">Excel'e Aktar</span>
                </button>
            <?php } ?>
            <?php if (permtrue("offerAdd")) { ?>
                <a href="index.php?p=offers/offer-manage" class="btn btn-action-primary">
                    <i class="fa fa-plus-circle"></i> <span>Yeni Teklif Oluştur</span>
                </a>
            <?php } ?>
        </div>
    </div>

    <!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
    <script>
        (function() {
            try {
                if (localStorage.getItem('aydinogullari_kpi_offers_collapsed') === 'true') {
                    document.documentElement.classList.add('kpi-offers-collapsed-early');
                }
            } catch(e) {}
        })();
    </script>

    <!-- Özet Bilgiler (CRM KPI Kartları) -->
    <div id="kpiSummarySection" class="row mx-0 mb-3 kpi-summary-collapse">
        <!-- Toplam Teklif -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Toplam Teklif</span>
                        <div class="crm-kpi-value"><?php echo number_format($totalOffersCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-primary">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Tutar: <strong class="text-primary"><?php echo tlFormat($totalOffersSum); ?></strong></span>
                    <span class="crm-badge-soft soft-primary">Tüm Kayıtlar</span>
                </div>
            </div>
        </div>

        <!-- Bekleyen Teklifler -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Bekleyen Teklifler</span>
                        <div class="crm-kpi-value"><?php echo number_format($pendingOffersCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-amber">
                        <i class="fa fa-hourglass-half"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Hacim: <strong class="text-warning"><?php echo tlFormat($pendingOffersSum); ?></strong></span>
                    <span class="crm-badge-soft soft-amber">Süreçte</span>
                </div>
            </div>
        </div>

        <!-- Kazanılan / Tamamlanan Teklifler -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Kazanılan Teklifler</span>
                        <div class="crm-kpi-value"><?php echo number_format($wonOffersCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-emerald">
                        <i class="fa fa-check-circle"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Ciro: <strong class="text-success"><?php echo tlFormat($wonOffersSum); ?></strong></span>
                    <span class="crm-badge-soft soft-emerald">%<?php echo $offerWinRate; ?> Başarı</span>
                </div>
            </div>
        </div>

        <!-- Bu Ay Açılan Teklifler -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Bu Ay Açılan</span>
                        <div class="crm-kpi-value"><?php echo number_format($thisMonthOffersCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-sky">
                        <i class="fa fa-calendar-check-o"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Tutar: <strong class="text-info"><?php echo tlFormat($thisMonthOffersSum); ?></strong></span>
                    <span class="crm-badge-soft soft-sky"><?php echo date('m/Y'); ?> Dönemi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste Card -->
    <div class="form-card animate-fade-in mx-1">
        <div class="form-card-header d-flex justify-content-between align-items-center">
            <div class="header-left-inner">
                <div class="card-icon">
                    <i class="fa fa-list"></i>
                </div>
                <div>
                    <h5><?php echo $sablonlari_goster ? 'Şablon Teklif Listesi' : 'Teklif Listesi'; ?></h5>
                    <p>Anlık arama, sütun filtreleme ve teklif yönetimi</p>
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap: 8px;">
                <button type="button" id="filtersToggle" class="btn btn-outline-secondary btn-action-outline" style="height: 34px;">
                    <i class="fa fa-filter"></i> <span class="d-none d-sm-inline">Detaylı Filtreleme</span>
                </button>
                <button type="button" id="toggleKpiSummary" class="btn btn-outline-secondary btn-sm" title="Özet Kartlarını Gizle / Göster" style="border-radius: 6px; width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fa fa-chevron-up"></i>
                </button>
            </div>
        </div>
        
        <div id="filtersCollapse" style="display:none; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 20px;" class="filters-form">
            <div class="row">
                <div class="col-md-3 mb-10">
                    <label class="form-label">Teklif No</label>
                    <input type="text" id="filter_offer_no" class="form-control" placeholder="Teklif numarasını yazınız.">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Firma</label>
                    <select id="filter_company" class="form-control select-picker" data-live-search="true" title="Firma seçin veya yazın"></select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Ürün / Konu</label>
                    <select id="filter_subject" class="form-control select-picker" data-live-search="true" title="Konu seçin veya yazın"></select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Durum</label>
                    <select id="filter_status" class="form-control select-picker" data-live-search="true" title="Durum seçin veya yazın"></select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Başlangıç Tarihi</label>
                    <input type="text" id="filter_date_start" class="form-control date-picker" placeholder="01.11.2024">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="text" id="filter_date_end" class="form-control date-picker" placeholder="01.01.2025">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Toplam (min)</label>
                    <input type="number" step="0.01" id="filter_total_min" class="form-control" placeholder="Başlangıç toplamını yazınız.">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Toplam (max)</label>
                    <input type="number" step="0.01" id="filter_total_max" class="form-control" placeholder="Bitiş toplamını yazınız.">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Para Birimi</label>
                    <select id="filter_currency" class="form-control select-picker" data-live-search="true" title="Para birimi seçin veya yazın">
                        <option value="">Tümü</option>
                    </select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Personel / Temsilci</label>
                    <select id="filter_creator" class="form-control select-picker" data-live-search="true" title="Personel seçin veya yazın"></select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Ödeme Vadesi</label>
                    <select id="filter_payment_period" class="form-control select-picker" data-live-search="true" title="Vade seçin veya yazın"></select>
                </div>
            </div>
            <div class="text-right mt-15">
                <button type="button" id="applyFilters" class="btn btn-success" style="border-radius: 8px; padding: 8px 20px;">ARA</button>
                <button type="button" id="clearFilters" class="btn btn-outline-secondary ml-1" style="border-radius: 8px; padding: 8px 20px;">Temizle</button>
            </div>
        </div>
        
        <div class="responsive">
            <table id="offerTable" class="data-table table-hover table-bordered" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Sıra No</th>
                        <th class="w-10">Oluşturma Tarihi</th>
                        <th>Teklif No</th>
                        <th>Müşteri</th>
                        <th>Toplam Tl Tutar</th>
                        <th>Durum</th>
                        <th>Onay Tarihi</th>
                        <th>Konusu</th>
                        <th>Ödeme Vadesi</th>
                        <th>Teklif Veren</th>
                        <th class="no-export text-center" style="width: 1%; white-space: nowrap;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="odd data-row text-center">
                        <td colspan="11">Veriler Yükleniyor...</td>
                    </tr>
                </tbody>
            </table>
    </div>
</div>
</div>

<!-- Teklif Log Kayıtları Modalı -->
<div class="modal fade" id="offerLogsModal" tabindex="-1" role="dialog" aria-labelledby="offerLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 850px;">
        <div class="modal-content custom-log-modal-content">
            <div class="modal-header custom-log-modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <div class="modal-icon-badge">
                        <i class="fa fa-history text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-16 weight-700 mb-0" id="offerLogsModalLabel">
                            Teklif İşlem & Log Kayıtları
                        </h5>
                        <small class="text-muted" id="offerLogsSubTitle">Kim, ne zaman, hangi işlemi yapmış geçmişi</small>
                    </div>
                </div>
                <button type="button" class="close btn-log-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Teklif Özet Kartı (Modal İçi) -->
            <div class="offer-log-summary-card px-4 py-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center" id="offerLogsSummaryCard" style="gap: 12px;">
                <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
                    <span class="offer-badge-no font-14 font-weight-bold text-primary" id="logOfferNo">-</span>
                    <span class="text-muted font-13 font-weight-500" id="logOfferCustomer">-</span>
                </div>
                <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
                    <div class="font-13"><span class="text-muted">Toplam Tutar:</span> <strong id="logOfferTotal" class="text-dark font-weight-bold">-</strong></div>
                    <div id="logOfferStatus">-</div>
                </div>
            </div>

            <div class="modal-body p-4" style="max-height: calc(80vh - 180px); overflow-y: auto;">
                <!-- Loading State -->
                <div id="offerLogsLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status" style="width: 2.2rem; height: 2.2rem;">
                        <span class="sr-only">Yükleniyor...</span>
                    </div>
                    <div class="text-muted font-13 font-weight-500">Log kayıtları yükleniyor...</div>
                </div>

                <!-- Error State -->
                <div id="offerLogsError" class="alert alert-danger d-none my-3" role="alert">
                    <i class="fa fa-exclamation-triangle mr-2"></i>
                    <span id="offerLogsErrorMessage">Kayıtlar yüklenirken bir sorun oluştu.</span>
                </div>

                <!-- Empty State -->
                <div id="offerLogsEmpty" class="text-center py-5 d-none">
                    <div class="empty-icon-circle mb-3">
                        <i class="fa fa-folder-open-o text-muted" style="font-size: 38px;"></i>
                    </div>
                    <h6 class="weight-600 text-dark mb-1">Kayıt Bulunamadı</h6>
                    <p class="text-muted font-13 mb-0">Bu teklife ait henüz detaylı bir aktivite kaydı bulunmuyor.</p>
                </div>

                <!-- Timeline / Log Container -->
                <div id="offerLogsContainer" class="offer-log-timeline d-none">
                    <!-- Dinamik log kartları JS ile eklenecek -->
                </div>
            </div>

            <div class="modal-footer custom-log-modal-footer d-flex justify-content-between align-items-center px-4 py-3">
                <div class="text-muted font-12" id="offerLogsCountText">Toplam 0 işlem kaydı</div>
                <div class="d-flex" style="gap: 8px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="btnRefreshOfferLogs">
                        <i class="fa fa-refresh mr-1"></i> Yenile
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-dismiss="modal" data-bs-dismiss="modal">
                        Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal & Timeline Stilleri */
#offerLogsModal .modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    overflow: hidden;
}
#offerLogsModal .modal-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 20px;
}
#offerLogsModal .modal-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(2, 132, 199, 0.1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
}
.offer-log-summary-card {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.offer-badge-no {
    display: inline-block;
    padding: 3px 8px;
    background: #e0f2fe;
    color: #0369a1 !important;
    border-radius: 6px;
    letter-spacing: 0.5px;
}
.offer-log-timeline {
    position: relative;
    padding-left: 26px;
}
.offer-log-timeline::before {
    content: '';
    position: absolute;
    top: 12px;
    bottom: 12px;
    left: 11px;
    width: 2px;
    background: #e2e8f0;
}
.offer-log-item {
    position: relative;
    margin-bottom: 18px;
}
.offer-log-item:last-child {
    margin-bottom: 0;
}
.offer-log-icon {
    position: absolute;
    left: -26px;
    top: 4px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    z-index: 2;
    border: 2px solid #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.offer-log-content {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.offer-log-content:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.offer-log-diff-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.dark-mode #offerLogsModal .modal-content {
    background: #1e293b;
    color: #f1f5f9;
}
.dark-mode #offerLogsModal .modal-header,
.dark-mode .offer-log-summary-card {
    background: #0f172a !important;
    border-color: #334155 !important;
}
.dark-mode .offer-badge-no {
    background: #1e3a5f;
    color: #38bdf8 !important;
}
.dark-mode #offerLogsModal .modal-footer {
    background: #0f172a;
    border-color: #334155;
}
.dark-mode .offer-log-timeline::before {
    background: #334155;
}
.dark-mode .offer-log-item .offer-log-icon {
    border-color: #1e293b;
}
.dark-mode .offer-log-content {
    background: #0f172a;
    border-color: #334155;
}
.dark-mode .offer-log-diff-box {
    background: #1e293b;
    border-color: #334155;
}
.dark-mode #logOfferTotal {
    color: #f8fafc !important;
}
.dark-mode #logOfferCustomer {
    color: #cbd5e1 !important;
}
.dark-mode #offerLogsModal .close {
    color: #cbd5e1;
    text-shadow: none;
}
    /* DataTables'ın sabit genişliklerini ezmek için */
table.dataTable {
    width: 100% !important;
}

.dataTables_length{
    margin-left: 10px;
}

/* Dropdown menünün tablonun dışına taşabilmesi için */
table.data-table,
table.dataTable {
    overflow: visible !important;
}

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
<script src="pages/1/offers/offer.js"></script>
<script src="src/plugins/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
<script src="src/plugins/bootstrap-select/dist/js/i18n/defaults-tr_TR.min.js"></script>

<script>
$(document).ready(function() {
    // Açılır menünün kesilmesini önlemek için tablonun sarmalayıcısının overflow'unu geçici olarak değiştiriyoruz
    $(document).on('show.bs.dropdown', '#offerTable .dropdown', function () {
        var $responsive = $('#offerTable').closest('.responsive');
        if ($responsive.length) {
            $responsive[0].style.setProperty('overflow', 'visible', 'important');
            $responsive[0].style.setProperty('overflow-x', 'visible', 'important');
            $responsive[0].style.setProperty('overflow-y', 'visible', 'important');
        }
    });

    $(document).on('hide.bs.dropdown', '#offerTable .dropdown', function () {
        var $responsive = $('#offerTable').closest('.responsive');
        if ($responsive.length) {
            $responsive[0].style.setProperty('overflow', '', '');
            $responsive[0].style.setProperty('overflow-x', '', '');
            $responsive[0].style.setProperty('overflow-y', '', '');
        }
    });

    $('.selectpicker').selectpicker();
    $.getJSON('App/api/get-offer-filter-options.php?sablon=<?php echo $sablonlari_goster ? 1 : 0; ?>', function(resp){
        function fillSelect(id, arr){
            var $s = $(id);
            $s.empty();
            $s.append('<option value=""></option>');
            if($s.attr('id') === 'filter_currency'){
                $s.append('<option value="">Tümü</option>');
            }
            (arr || []).forEach(function(v){
                if(v && v.trim() !== ''){
                    var esc = $('<div>').text(v).html();
                    $s.append('<option value="'+esc+'">'+esc+'</option>');
                }
            });
            $s.selectpicker('refresh');
            enableManualEntry('#'+$s.attr('id'));
        }
        fillSelect('#filter_company', resp.company_name);
        fillSelect('#filter_subject', resp.offer_subject);
        fillSelect('#filter_status', resp.durum);
        fillSelect('#filter_creator', resp.creator_name);
        fillSelect('#filter_payment_period', resp.payment_period);
        fillSelect('#filter_currency', resp.currency);
    });

    function enableManualEntry(sel){
        var $s = $(sel);
        var $wrap = $s.next('.bootstrap-select');
        $wrap.off('keydown.manual').on('keydown.manual', '.bs-searchbox input', function(e){
            if(e.key === 'Enter'){
                e.preventDefault();
                var q = $(this).val();
                if(!q) return;
                var exists = false;
                $s.find('option').each(function(){ if($(this).text() === q){ exists = true; } });
                var val = '__manual__:'+q;
                if(!exists){
                    $s.append('<option data-manual="1" value="'+val+'">'+q+'</option>');
                } else {
                    // if exists, set its value to its text
                    val = q;
                }
                $s.selectpicker('val', val);
            }
        });
    }
      
    // DataTable örneğini bir değişkende saklayın, böylece ona daha sonra erişebiliriz.
    var offerTable = $('#offerTable').DataTable({
        retrieve: true,
        "processing": false,
        "serverSide": true,
        "ajax": {
            "url": "App/api/get-offers.php?sablon=<?php echo $sablonlari_goster ? 1 : 0; ?>", 
            "type": "POST",
            "data": function(d){
                function sv(id){
                    var v = $(id).val();
                    if(typeof v === 'string' && v.indexOf('__manual__:') === 0){ v = v.replace('__manual__:', ''); }
                    return v;
                }
                d.filters = {
                    offer_no: $('#filter_offer_no').val(),
                    company: sv('#filter_company'),
                    subject: sv('#filter_subject'),
                    status: sv('#filter_status'),
                    date_start: $('#filter_date_start').val(),
                    date_end: $('#filter_date_end').val(),
                    total_min: $('#filter_total_min').val(),
                    total_max: $('#filter_total_max').val(),
                    currency: sv('#filter_currency'),
                    creator: sv('#filter_creator'),
                    payment_period: sv('#filter_payment_period')
                };
            },
            "error": function (xhr, error, thrown) {
                console.log("DataTables AJAX Hatası:", xhr, error, thrown);
                alert("Veriler yüklenirken bir hata oluştu. Sunucu yanıtını kontrol edin.");
            }
        },
        "columns": [
            { "data": "sira_no", "name": "sira_no", "orderable": false, "searchable": false },
            { "data": "islem_tarihi", "name": "created_at" }, 
            { "data": "teklif_no", "name": "offerNumber" },
            { "data": "musteri", "name": "company" },
            { "data": "toplam_tutar", "name": "total_price" },
            { "data": "durum", "name": "durum" },
            { "data": "onay_tarihi", "name": "onay_tarihi" },
            { "data": "konusu", "name": "offer_subject" },
            { "data": "odeme_vadesi", "name": "payment_period" },
            { "data": "teklif_veren", "name": "creator_name" },
            { "data": "islem", "orderable": false, "searchable": false, "className": "text-center text-nowrap" }
        ],
        "order": [[ 1, "desc" ]],
        "language": {
            "url": "include/js/tr.json"
        },
        initComplete: function () {
            if (window.App && window.App.TableFilter) {
                App.TableFilter.attachToTable(this.api().table().node());
            }
        }
    });
  
});



    function showExportLoadingNotification() {
        var swalObj = (typeof swal !== 'undefined') ? swal : ((typeof Swal !== 'undefined') ? Swal : null);
        if (swalObj) {
            swalObj.fire({
                title: "Excel Dosyası Hazırlanıyor",
                html: "Lütfen bekleyiniz, veriler indiriliyor...<br><small style='color:#888;'>İndirme işlemi birazdan otomatik başlayacaktır.</small>",
                icon: "info",
                showConfirmButton: false,
                allowOutsideClick: true,
                timer: 4000,
                timerProgressBar: true,
                didOpen: function() {
                    if (typeof swalObj.showLoading === 'function') {
                        swalObj.showLoading();
                    }
                }
            });
        }
    }

    // Excel'e aktar butonu için
    $('#exportExcel').on('click', function(e){
        e.preventDefault();
        showExportLoadingNotification();
        var form = $('<form>', { action: 'App/api/export-offers.php?sablon=<?php echo $sablonlari_goster ? 1 : 0; ?>', method: 'POST' });
        function sv(id){
            var v = $(id).val();
            if(typeof v === 'string' && v.indexOf('__manual__:') === 0){ v = v.replace('__manual__:', ''); }
            return v;
        }
        var filters = {
            offer_no: $('#filter_offer_no').val(),
            company: sv('#filter_company'),
            subject: sv('#filter_subject'),
            status: sv('#filter_status'),
            date_start: $('#filter_date_start').val(),
            date_end: $('#filter_date_end').val(),
            total_min: $('#filter_total_min').val(),
            total_max: $('#filter_total_max').val(),
            currency: sv('#filter_currency'),
            creator: sv('#filter_creator'),
            payment_period: sv('#filter_payment_period')
        };
        form.append($('<input>', { type: 'hidden', name: 'filters', value: JSON.stringify(filters) }));
        $('body').append(form);
        form.submit();
        setTimeout(function(){ form.remove(); }, 1000);
    });




    $(document).on('click', '.teklif-sil', function() {
    
        var teklif_id = $(this).data('id');
        swal.fire({
            title: "Teklifi Sil",
            text: "Bu teklifi silmek istediğinizden emin misiniz?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Evet, Sil!",
            cancelButtonText: "İptal"
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX ile teklif silme işlemi
                $.ajax({
                    url: 'App/api/offer.php',
                    type: 'POST',
                    data: { id: teklif_id , action: 'deleteOffer' },
                    success: function(response) {
                        response = JSON.parse(response); // JSON verisini ayrıştır
                        
                        if (response.status === 'success') {
                            swal.fire("Silindi!", response.message, "success");
                            // Tabloyu yeniden yükle
                            $('#offerTable').DataTable().ajax.reload();
                        } else {
                            swal.fire("Hata!", response.message, "error");
                        }
                    },
                    error: function() {
                        swal.fire("Hata!", "Teklif silinirken bir hata oluştu.", "error");
                    }
                });
            }
        });
    });
 
    // Tabloyu Yenile Butonu
    $(document).on('click', '#btnRefreshOffers', function() {
        var $btn = $(this);
        var $icon = $btn.find('i');
        $icon.addClass('fa-spin');
        $('#offerTable').DataTable().ajax.reload(function() {
            setTimeout(function() {
                $icon.removeClass('fa-spin');
            }, 300);
        }, false);
    });

    // Filtreleri uygula
    $(document).on('click', '#applyFilters', function() {
        $('#offerTable').DataTable().ajax.reload();
    });

    // Filtreleri temizle
    $(document).on('click', '#clearFilters', function() {
        $('#filter_offer_no, #filter_date_start, #filter_date_end, #filter_total_min, #filter_total_max')
            .val('');
        $('#filter_company, #filter_subject, #filter_status, #filter_currency, #filter_creator, #filter_payment_period')
            .find('option[data-manual="1"]').remove();
        $('#filter_company, #filter_subject, #filter_status, #filter_currency, #filter_creator, #filter_payment_period')
            .selectpicker('val', '')
            .selectpicker('render');
        $('#offerTable').DataTable().ajax.reload();
    });

    // KPI Summary Section Toggle & LocalStorage
    $('html').removeClass('kpi-offers-collapsed-early');
    var isKpiCollapsed = localStorage.getItem('aydinogullari_kpi_offers_collapsed') === 'true';
    if (isKpiCollapsed) {
        $('#kpiSummarySection').addClass('is-collapsed');
        $('#toggleKpiSummary i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    }

    $(document).on('click', '#toggleKpiSummary', function(){
        var $kpi = $('#kpiSummarySection');
        var willCollapse = !$kpi.hasClass('is-collapsed');
        
        if(willCollapse){
            $kpi.addClass('is-collapsed');
            $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            localStorage.setItem('aydinogullari_kpi_offers_collapsed', 'true');
        } else {
            $kpi.removeClass('is-collapsed');
            $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            localStorage.setItem('aydinogullari_kpi_offers_collapsed', 'false');
        }
    });

    // Accordion toggle
    $(document).on('click', '#filtersToggle', function(){
        var $c = $('#filtersCollapse');
        if($c.is(':visible')){
            $c.slideUp(150);
            $('#filtersToggle').html('<i class="fa fa-filter mr-1"></i> Detaylı Filtreleme');
        }else{
            $c.slideDown(150);
            $('#filtersToggle').html('<i class="fa fa-chevron-up mr-1"></i> Gizle');
        }
    });

    // Enter ile arama
    $(document).on('keydown', '#filtersCollapse input, #filtersCollapse select', function(e){
        if(e.key === 'Enter'){
            e.preventDefault();
            $('#applyFilters').trigger('click');
        }
    });
    $(document).on('input keyup change blur', '.date-picker', function(){
        var v = (this.value || '').replace(/[^0-9]/g,'');
        if(v.length > 2) v = v.slice(0,2) + '.' + v.slice(2);
        if(v.length > 5) v = v.slice(0,5) + '.' + v.slice(5);
        this.value = v.slice(0,10);
    });

    // Tabloda Sağ Tık (Context Menu) İşlemleri
    $(document).on('contextmenu', '#offerTable tbody tr', function(e) {
        // Eğer boş satır veya yükleniyor uyarısı ise dur
        if ($(this).find('td').length <= 1) return;

        e.preventDefault();
        
        var $tr = $(this);
        $('#offerTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var offerNo = $tr.find('td:nth-child(3)').text().trim() || 'Teklif İşlemleri';
        var $actionTd = $tr.find('td:last-child');
        
        var menuHtml = '<div class="cm-header"><i class="fa fa-file-text-o mr-1"></i> ' + $('<div>').text(offerNo).html() + '</div>';

        // 1. Düzenle Butonu Varsa
        var $editBtn = $actionTd.find('a[data-tooltip="Düzenle"], a.btn-outline-primary');
        if ($editBtn.length) {
            menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-primary mr-2"></i> Düzenle</a>';
        }

        // 2. Dropdown içindeki elemanlar
        var $dropdownItems = $actionTd.find('.dropdown-menu .dropdown-item');
        if ($dropdownItems.length) {
            $dropdownItems.each(function() {
                var $item = $(this);
                var href = $item.attr('href') || '#';
                var target = $item.attr('target') ? ' target="' + $item.attr('target') + '"' : '';
                var text = $item.html();
                var dataId = $item.attr('data-id') ? ' data-id="' + $item.attr('data-id') + '"' : '';
                var dataOfferNo = $item.attr('data-offer-no') ? ' data-offer-no="' + $item.attr('data-offer-no') + '"' : '';
                var classAttr = $item.attr('class') || '';

                menuHtml += '<a href="' + href + '"' + target + dataId + dataOfferNo + ' class="' + classAttr + '">' + text + '</a>';
            });
        }

        // 3. Sil Butonu Varsa
        var $deleteBtn = $actionTd.find('.teklif-sil');
        if ($deleteBtn.length) {
            menuHtml += '<div class="cm-divider"></div>';
            var delId = $deleteBtn.data('id');
            menuHtml += '<button type="button" class="teklif-sil cm-danger" data-id="' + delId + '"><i class="fa fa-trash text-danger mr-2"></i> Sil</button>';
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

    // Menü dışına tıklanınca veya sayfayı kaydırınca context menu kapat
    $(document).on('click scroll', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#offerTable tbody tr').removeClass('context-menu-active');
        }
    });

    // Menüdeki seçeneğe basılınca context menu kapat
    $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
        $('#customContextMenu').hide();
        $('#offerTable tbody tr').removeClass('context-menu-active');
    });

    // ESC basılınca kapat
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#customContextMenu').hide();
            $('#offerTable tbody tr').removeClass('context-menu-active');
        }
    });

    // ==========================================
    // Teklif Log Kayıtları İşlemleri
    // ==========================================
    var currentLogOfferId = null;
    var currentLogOfferNo = null;

    $(document).on('click', '.btn-offer-logs', function(e) {
        e.preventDefault();
        var offerId = $(this).attr('data-id') || $(this).data('id');
        var offerNo = $(this).attr('data-offer-no') || $(this).data('offer-no') || '';

        if (!offerId) {
            var $row = $(this).closest('tr');
            offerId = $row.find('.teklif-sil, .offer-copy, .btn-offer-logs').first().data('id');
            if (!offerNo) {
                offerNo = $row.find('td:nth-child(3)').text().trim();
            }
        }

        if (!offerId) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Teklif kimliği belirlenemedi.',
                    confirmButtonText: 'Tamam'
                });
            } else {
                alert('Teklif kimliği belirlenemedi.');
            }
            return;
        }

        currentLogOfferId = offerId;
        currentLogOfferNo = offerNo;
        loadOfferLogs(offerId, offerNo);
    });

    $('#btnRefreshOfferLogs').on('click', function() {
        if (currentLogOfferId) {
            loadOfferLogs(currentLogOfferId, currentLogOfferNo);
        }
    });

    function loadOfferLogs(offerId, offerNo) {
        $('#offerLogsModalTitle').text('Teklif İşlem & Log Kayıtları ' + (offerNo ? '(' + offerNo + ')' : ''));
        $('#logOfferNo').text(offerNo || ('#' + offerId));
        $('#logOfferCustomer').text('Yükleniyor...');
        $('#logOfferTotal').text('-');
        $('#logOfferStatus').html('');
        $('#offerLogsCountText').text('Kayıtlar getiriliyor...');

        $('#offerLogsLoading').removeClass('d-none');
        $('#offerLogsError').addClass('d-none');
        $('#offerLogsEmpty').addClass('d-none');
        $('#offerLogsContainer').addClass('d-none').empty();

        $('#offerLogsModal').modal('show');

        $.ajax({
            url: 'App/api/offer.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'getOfferLogs',
                id: offerId
            },
            success: function(response) {
                $('#offerLogsLoading').addClass('d-none');

                if (response && response.status === 'success') {
                    var offer = response.offer || {};
                    var logs = response.logs || [];

                    // Özet bilgileri güncelle
                    $('#logOfferNo').text(offer.offer_number || ('#' + offer.id));
                    $('#logOfferCustomer').text(offer.company_name || 'Firma Belirtilmemiş');
                    if (offer.total_price) {
                        $('#logOfferTotal').text('₺ ' + parseFloat(offer.total_price).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    } else {
                        $('#logOfferTotal').text('₺ 0,00');
                    }
                    if (offer.statu_label) {
                        $('#logOfferStatus').html('<span class="badge ' + (offer.statu_badge_class || 'badge-secondary') + '">' + offer.statu_label + '</span>');
                    }

                    $('#offerLogsCountText').text('Toplam ' + logs.length + ' işlem kaydı bulundu');

                    if (logs.length === 0) {
                        $('#offerLogsEmpty').removeClass('d-none');
                        return;
                    }

                    renderOfferLogsTimeline(logs);
                    $('#offerLogsContainer').removeClass('d-none');
                } else {
                    $('#offerLogsErrorMessage').text(response.message || 'Log kayıtları alınamadı.');
                    $('#offerLogsError').removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#offerLogsLoading').addClass('d-none');
                var errMsg = 'Log kayıtları yüklenirken sunucu hatası oluştu.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                $('#offerLogsErrorMessage').text(errMsg);
                $('#offerLogsError').removeClass('d-none');
            }
        });
    }

    function renderOfferLogsTimeline(logs) {
        var $container = $('#offerLogsContainer');
        $container.empty();

        logs.forEach(function(log) {
            var eventIcon = log.event_icon || 'fa fa-history';
            var badgeClass = log.badge_class || 'soft-blue';
            var eventLabel = log.event_label || 'İşlem';
            var summary = $('<div>').text(log.summary || '').html();
            var userName = $('<div>').text(log.user_name || 'Kullanıcı').html();
            var userUnvan = log.user_unvan ? '<span class="text-muted font-12">(' + $('<div>').text(log.user_unvan).html() + ')</span>' : '';
            var timeFormatted = $('<div>').text(log.created_at_formatted || '-').html();
            var relTime = log.relative_time ? '<span class="badge badge-light border text-muted ml-2 font-11"><i class="fa fa-clock-o mr-1"></i>' + $('<div>').text(log.relative_time).html() + '</span>' : '';
            var ipBadge = (log.ip_address && log.ip_address !== '-') ? '<span class="text-muted font-11 ml-auto"><i class="fa fa-globe mr-1"></i>IP: ' + $('<div>').text(log.ip_address).html() + '</span>' : '';

            // Değişen alanlar kartı
            var changesHtml = '';
            if (log.changed_fields && log.changed_fields.length > 0) {
                changesHtml += '<div class="offer-log-diff-box mt-2 p-2 rounded">';
                changesHtml += '<div class="font-12 font-weight-bold text-secondary mb-1"><i class="fa fa-exchange mr-1"></i> Değiştirilen Alanlar:</div>';
                changesHtml += '<table class="table table-sm table-borderless font-12 mb-0">';
                log.changed_fields.forEach(function(ch) {
                    changesHtml += '<tr>';
                    changesHtml += '<td style="width: 35%;" class="font-weight-600 text-muted">' + $('<div>').text(ch.label).html() + ':</td>';
                    changesHtml += '<td style="width: 30%;" class="text-danger"><del>' + ch.old + '</del></td>';
                    changesHtml += '<td style="width: 5%;" class="text-muted text-center"><i class="fa fa-arrow-right"></i></td>';
                    changesHtml += '<td style="width: 30%;" class="text-success font-weight-600">' + ch.new + '</td>';
                    changesHtml += '</tr>';
                });
                changesHtml += '</table></div>';
            }

            var logIdText = log.id > 0 ? '#' + log.id : 'İlk Kayıt';

            var itemHtml = `
                <div class="offer-log-item">
                    <div class="offer-log-icon ${badgeClass}">
                        <i class="${eventIcon}"></i>
                    </div>
                    <div class="offer-log-content shadow-sm">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-1" style="gap: 8px;">
                            <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                                <span class="crm-badge-soft ${badgeClass}">${eventLabel}</span>
                                <strong class="text-dark font-13">${userName}</strong>
                                ${userUnvan}
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="text-muted font-12">${timeFormatted}</span>
                                ${relTime}
                            </div>
                        </div>
                        <div class="offer-log-summary font-13 text-secondary mt-1">
                            ${summary}
                        </div>
                        ${changesHtml}
                        <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top border-light">
                            <span class="text-muted font-11"><i class="fa fa-shield mr-1"></i>${logIdText}</span>
                            ${ipBadge}
                        </div>
                    </div>
                </div>
            `;
            $container.append(itemHtml);
        });
    }
</script>
<!-- <script src="include/js/data-table.js"></script> -->

