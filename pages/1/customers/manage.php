<?php
$rawCustomerId = $_GET['id'] ?? 0;
permcontrol((int)$rawCustomerId > 0 ? 'customeredit' : 'customeradd');

use App\Helper\customer;
use App\Helper\Helper;
use App\Model\CustomerModel;
use App\Model\OfferModel;
use App\Model\ReportsModel;

$Customer = new CustomerModel();

$id = isset($_GET["id"]) ? $_GET["id"] : 0;
if (!is_numeric($id)) {
    header("Location:index.php?p=customers/list");
    exit;
}

$customer = $Customer->find($id);

if ($id > 0 && (!$customer || !empty($customer->deleted_at))) {
    header("Location:index.php?p=customers/list&st=customer-deleted");
    exit;
}

$cerq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$cerq->execute(array($_GET["id"] ?? 0));
$cc = $cerq->fetch(PDO::FETCH_ASSOC);

$todos = $ac->prepare("SELECT COUNT(*) FROM projects WHERE pcid = ? AND deleted_at IS NULL");
$todos->execute(array($id));
$pjs = $todos->fetchColumn();

$todoso = $ac->prepare("SELECT COUNT(*) FROM offers WHERE cid = ? AND is_template = 0");
$todoso->execute(array($id));
$ojs = $todoso->fetchColumn();

// Son oluşturulan teklif
$sot = $ac->prepare("SELECT * FROM offers WHERE cid = ? AND is_template = 0 ORDER BY id DESC LIMIT 1");
$sot->execute(array($id));
$sonteklif = $sot->fetch(PDO::FETCH_ASSOC);

// Son Oluşturulan Servis
$sos = $ac->prepare("SELECT * FROM projects WHERE pcid = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
$sos->execute(array($id));
$ojsp = $sos->fetch(PDO::FETCH_ASSOC);

// Servis Tipi getirilir
$servicestype = null;
if ($ojsp) {
    $sql = $ac->prepare("SELECT * FROM units WHERE id = ? ");
    $sql->execute(array($ojsp["servicestype"]));
    $servicestype = $sql->fetch(PDO::FETCH_ASSOC);
}

// Rapor Sayısı (Hafif Sorgu)
$todosoR = $ac->prepare("SELECT COUNT(*) FROM reports WHERE customer_id = ?");
$todosoR->execute(array($id));
$reportCount = (int)$todosoR->fetchColumn();

// Son Oluşturulan Rapor
$sor = $ac->prepare("SELECT r.*, rt.reportName FROM reports r LEFT JOIN report_types rt ON r.report_type = rt.id WHERE r.customer_id = ? ORDER BY r.id DESC LIMIT 1");
$sor->execute(array($id));
$sonrapor = $sor->fetch(PDO::FETCH_ASSOC);






if ($_POST) {

    if (empty($_POST["company"])) {
        header("Location: index.php?p=customer-edit&cid=" . (int)$id . "&st=empties");
        exit;
    }


    $ccompany = @$_POST["company"];
    $cemail = @$_POST["cemail"];
    $address = @$_POST["customer_address"];
    $location = trim((string) ($_POST["location"] ?? ''));
    $il = @$_POST["il"];
    $ilce = @$_POST["ilce"];
    $cdesc = @$_POST["cdesc"];
    $cgsm = @$_POST["cgsm"];
    $yetkiliadi = @$_POST["yetkili"];
    $categoryName = @$_POST["categoryName"];
    $OdemeVade = @$_POST["vade"];
    $region = @$_POST["region"];
    $updater = sesset("id");
    $updated_at = date("Y-m-d H:i:s");

    $ahce = $ac->prepare("UPDATE customers SET
    company = ?,
    email = ?,
    address = ? ,
    location = ?,
    city = ?,
    ilce = ?,
    cdesc = ?,
    gsm = ?,
    yetkili = ?,
	grp = ? ,
	OdemeVade = ? ,
    region = ?,
    updater = ?,
    updated_at = ?
    WHERE id = ?");

    $ahce->execute(array(
        $ccompany,
        $cemail,
        $address,
        $location,
        $il,
        $ilce,
        $cdesc,
        $cgsm,
        $yetkiliadi,
        $categoryName,
        $OdemeVade,
        $region,
        $updater,
        $updated_at,
        $cid
    ));

    // if ($cpass) {

    // 	$sifre = md5(md5(md5($cpass)));
    // 	$upcus = $ac->prepare("UPDATE customers SET password = ? WHERE id = ?");
    // 	$upcus->execute(array($sifre, $cid));

    // 	$upcus = $ac->prepare("UPDATE users SET password = ? WHERE cid = ?");
    // 	$upcus->execute(array($sifre, $cid));
    // }


    if ($ahce) {
        header("Location:index.php?p=customer-edit&id=$cid&st=newsuccess");
    } else {
    }


}

//Uyarı mesajları
if (@$_GET["st"] == "empties") {
    showAlert("alert", "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.");
}
if (@$_POST["status"] == "success") {
    showAlert("success", "İşlem Başarı ile tamamlandı!");
}
?>


<!-- Sayfa Yenilendiğinde En Üstten Başlama Kontrolü -->
<script>
    (function() {
        try {
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
            window.scrollTo(0, 0);
            if (window.location.hash) {
                history.replaceState(null, null, window.location.pathname + window.location.search);
            }
        } catch(e) {}
    })();
</script>

<style>

    /* Premium customer form styles */
    .customer-manage-wrapper {
        width: 100%;
        max-width: 100%;
        margin: 0;
    }

    /* Stats Grid: header ve form card ile birebir aynı hizada */
    .customer-stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        width: 100%;
        margin-bottom: 20px;
    }
    @media (max-width: 1200px) {
        .customer-stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 768px) {
        .customer-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 576px) {
        .customer-stats-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Minimal Stats Card Styling */
    .customer-stat-minimal {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .customer-stat-minimal:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        transform: translateY(-1px);
    }
    .customer-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .customer-stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .customer-stat-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 2px;
    }
    .customer-stat-number {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .customer-stat-bottom {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
    }

    /* Inline row within form field (e.g. city/district) */
    .form-field .row-inline {
        display: flex;
        gap: 15px;
    }

    .form-field .row-inline > div {
        flex: 1;
    }

    /* Modern Segment Pill Tabs for Customer Detail (Matching users.php style) */
    .customer-status-tabs {
        display: inline-flex !important;
        align-items: center !important;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 9px;
        border: 1px solid #cbd5e1;
        gap: 3px;
        height: 38px !important;
        box-sizing: border-box;
        user-select: none;
        margin-bottom: 20px !important;
        vertical-align: middle;
    }
    .customer-status-tab {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px;
        padding: 0 14px;
        height: 30px !important;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
        white-space: nowrap;
        line-height: 1;
        margin: 0 !important;
    }
    .customer-status-tab:hover {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.6);
        text-decoration: none !important;
    }
    .customer-status-tab.active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .customer-status-tab.active#tab-btn-offers {
        color: #2563eb;
    }
    .customer-status-tab.active#tab-btn-reports {
        color: #dc2626;
    }
    .customer-tab-badge {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        background: #e2e8f0;
        color: #475569;
        line-height: 1;
        transition: all 0.15s ease;
    }
    .customer-status-tab.active .customer-tab-badge {
        background: rgba(79, 70, 229, 0.12);
        color: #4f46e5;
    }
    .customer-status-tab.active#tab-btn-offers .customer-tab-badge {
        background: rgba(37, 99, 235, 0.15);
        color: #2563eb;
    }
    .customer-status-tab.active#tab-btn-reports .customer-tab-badge {
        background: rgba(220, 38, 38, 0.15);
        color: #dc2626;
    }

    /* Dark Mode Desteği */
    .dark-mode .customer-status-tabs {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark-mode .customer-status-tab {
        color: #94a3b8 !important;
    }
    .dark-mode .customer-status-tab:hover {
        color: #f8fafc !important;
        background: rgba(255, 255, 255, 0.05) !important;
    }
    .dark-mode .customer-status-tab.active {
        background: #1e293b !important;
        color: #f8fafc !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
    }
    .dark-mode .customer-tab-badge {
        background: #334155 !important;
        color: #94a3b8 !important;
    }
    .dark-mode .customer-status-tab.active .customer-tab-badge {
        background: rgba(79, 70, 229, 0.3) !important;
        color: #c7d2fe !important;
    }
    .dark-mode .customer-status-tab.active#tab-btn-offers .customer-tab-badge {
        background: rgba(37, 99, 235, 0.3) !important;
        color: #93c5fd !important;
    }
    .dark-mode .customer-status-tab.active#tab-btn-reports .customer-tab-badge {
        background: rgba(220, 38, 38, 0.3) !important;
        color: #fca5a5 !important;
    }

    /* Form Card Accordion Collapse Styling */
    .form-card.is-collapsed .form-card-header {
        border-bottom: none !important;
        border-radius: 16px !important;
    }
    #toggleCustomerFormHeader {
        transition: background 0.15s ease;
    }
    #toggleCustomerFormHeader:hover {
        background: #f8fafc;
    }

    /* ================================================================= */
    /* İCMAL MODÜLLERİ (TEKLİF & RAPOR) ORTAK STİLLERİ                  */
    /* ================================================================= */
    .icmal-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        width: 100%;
        margin-bottom: 20px;
    }
    @media (max-width: 992px) {
        .icmal-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 576px) {
        .icmal-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
    .icmal-kpi-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: all 0.2s ease;
    }
    .icmal-kpi-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transform: translateY(-2px);
    }
    .icmal-kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .icmal-kpi-info {
        flex-grow: 1;
        min-width: 0;
    }
    .icmal-kpi-title {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .icmal-kpi-value {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .icmal-kpi-sub {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 3px;
    }

    /* Accordion Card Rounding & Polish */
    .icmal-accordion-card {
        border-radius: 16px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03) !important;
        overflow: hidden !important;
        background: #ffffff !important;
        margin-bottom: 20px !important;
        transition: all 0.2s ease;
    }
    .icmal-accordion-card .card-header {
        border-radius: 16px !important;
        border-bottom: 1px solid transparent !important;
        background: #ffffff !important;
        padding: 14px 18px !important;
        transition: background 0.15s ease;
    }
    .icmal-accordion-card .card-header:not(.collapsed) {
        border-radius: 16px 16px 0 0 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        background: #f8fafc !important;
    }
    .icmal-accordion-card .card-header:hover {
        background: #f1f5f9 !important;
    }
    .icmal-accordion-card [data-toggle="collapse"][aria-expanded="true"] .accordion-chevron {
        transform: rotate(180deg);
    }
    .accordion-chevron {
        transition: transform 0.2s ease-in-out;
    }
    .icmal-accordion-card .card-body {
        border-radius: 0 0 16px 16px !important;
        padding: 18px !important;
        background: #ffffff !important;
    }

    /* Template Select2 and Plus Button Input Group alignment */
    .icmal-accordion-card .input-group {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: stretch !important;
        width: 100% !important;
    }
    .icmal-accordion-card .input-group span.select2.select2-container,
    .icmal-accordion-card .input-group .select2.select2-container {
        flex: 1 1 auto !important;
        width: 1% !important;
        min-width: 0 !important;
    }
    .icmal-accordion-card .input-group .select2-container--default .select2-selection--single {
        height: 38px !important;
        border-radius: 8px 0 0 8px !important;
        border: 1px solid #cbd5e1 !important;
        display: flex !important;
        align-items: center !important;
        background: #ffffff !important;
    }
    .icmal-accordion-card .input-group .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 12px !important;
        font-size: 13px !important;
    }
    .icmal-accordion-card .input-group .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
    }
    .icmal-accordion-card .input-group .btn-add-template {
        flex: 0 0 auto !important;
        border-radius: 0 8px 8px 0 !important;
        border: 1px solid #cbd5e1 !important;
        border-left: none !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 16px !important;
        height: 38px !important;
        font-size: 14px !important;
        transition: all 0.2s ease !important;
    }
    .icmal-accordion-card .input-group .btn-add-template:hover {
        background: #e2e8f0 !important;
        color: #1e293b !important;
    }

    /* Filter Toolbar Alignment & Rounding */
    .icmal-filter-toolbar {
        background: #f8fafc;
        padding: 10px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
    }
    .icmal-filter-toolbar .select2-container--default .select2-selection--single {
        height: 36px !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        display: flex !important;
        align-items: center !important;
        background: #ffffff !important;
    }
    .icmal-filter-toolbar .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 34px !important;
        padding-left: 10px !important;
        font-size: 13px !important;
    }
    .icmal-filter-toolbar .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 34px !important;
        right: 8px !important;
    }

    /* WYSIHTML5 Iframe and Toolbar */
    .offerHeaderContent iframe.wysihtml5-sandbox,
    .offerFooterContent iframe.wysihtml5-sandbox {
        width: 100% !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        background: #ffffff !important;
    }
    .offerHeaderContent iframe.wysihtml5-sandbox {
        height: 140px !important;
        min-height: 140px !important;
    }
    .offerFooterContent iframe.wysihtml5-sandbox {
        height: 160px !important;
        min-height: 160px !important;
    }
    .offerHeaderContent textarea.textarea_editor,
    .offerFooterContent textarea.textarea_editor {
        display: none !important;
        visibility: hidden !important;
    }
    ul.wysihtml5-toolbar {
        margin-bottom: 8px !important;
        padding: 0 !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 4px !important;
    }
    ul.wysihtml5-toolbar > li {
        margin-right: 0 !important;
        margin-bottom: 4px !important;
    }
    ul.wysihtml5-toolbar .btn {
        border-radius: 6px !important;
        font-size: 12px !important;
        padding: 4px 10px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        color: #334155 !important;
    }
    ul.wysihtml5-toolbar .btn:hover {
        background: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
    }

    /* Master Detail Table Styling (Teklif & Rapor İcmali) */
    .table-icmal, .table-report-icmal {
        border: 1px solid #e2e8f0;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 10px;
        overflow: hidden;
        width: 100%;
    }
    .table-icmal th, .table-report-icmal th {
        background: #f1f5f9;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 8px;
        border-bottom: 2px solid #cbd5e1;
        vertical-align: middle;
    }
    .table-icmal td, .table-report-icmal td {
        padding: 11px 8px;
        font-size: 13px;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
    }

    /* Rounded Stylish Checkboxes */
    .table-icmal input[type="checkbox"],
    .table-report-icmal input[type="checkbox"],
    .offer-select-cb,
    .report-select-cb,
    #selectAllOffers,
    #selectAllReports {
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        width: 18px !important;
        height: 18px !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 5px !important;
        background-color: #ffffff !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        position: relative !important;
        vertical-align: middle !important;
        margin: 0 !important;
        transition: all 0.15s ease-in-out !important;
        outline: none !important;
    }
    .table-icmal input[type="checkbox"]:hover,
    .table-report-icmal input[type="checkbox"]:hover,
    #selectAllOffers:hover,
    #selectAllReports:hover {
        border-color: #3b82f6 !important;
        background-color: #f8fafc !important;
    }
    .table-icmal input[type="checkbox"]:checked,
    .offer-select-cb:checked,
    #selectAllOffers:checked {
        background-color: #2563eb !important;
        border-color: #2563eb !important;
    }
    .table-report-icmal input[type="checkbox"]:checked,
    .report-select-cb:checked,
    #selectAllReports:checked {
        background-color: #dc2626 !important;
        border-color: #dc2626 !important;
    }
    .table-icmal input[type="checkbox"]:checked::after,
    .table-report-icmal input[type="checkbox"]:checked::after,
    .offer-select-cb:checked::after,
    .report-select-cb:checked::after,
    #selectAllOffers:checked::after,
    #selectAllReports:checked::after {
        content: "" !important;
        display: block !important;
        width: 5px !important;
        height: 9px !important;
        border: solid #ffffff !important;
        border-width: 0 2px 2px 0 !important;
        transform: rotate(45deg) !important;
        margin-bottom: 2px !important;
    }

    .table-icmal tr.offer-row,
    .table-report-icmal tr.report-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .table-icmal tr.offer-row:hover,
    .table-report-icmal tr.report-row:hover {
        background-color: #f8fafc;
    }
    .table-icmal tr.offer-row.is-open {
        background-color: #eff6ff !important;
    }
    .table-report-icmal tr.report-row.is-open {
        background-color: #fef2f2 !important;
    }
    .btn-expand-row {
        width: 26px;
        height: 26px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 12px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        transition: all 0.2s;
    }
    .table-icmal tr.offer-row.is-open .btn-expand-row {
        background: #3b82f6;
        color: #ffffff;
        border-color: #3b82f6;
        transform: rotate(90deg);
    }
    .table-report-icmal tr.report-row.is-open .btn-expand-row {
        background: #ef4444;
        color: #ffffff;
        border-color: #ef4444;
        transform: rotate(90deg);
    }
    .details-subtable-wrapper {
        background: #f8fafc;
        padding: 14px 18px;
        border-top: 1px dashed #cbd5e1;
        border-bottom: 1px solid #cbd5e1;
    }
    .subtable-items {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        margin-bottom: 0;
    }
    .subtable-items th {
        background: #e2e8f0;
        color: #334155;
        font-size: 11px;
        font-weight: 600;
        padding: 8px 10px;
        border: none;
    }
    .subtable-items td {
        font-size: 12px;
        padding: 8px 10px;
        border-top: 1px solid #f1f5f9;
    }
    .subtable-items tr:hover td {
        background: #f8fafc;
    }

    /* Official Quote & Report Modal Paper Styling */
    .official-icmal-paper {
        background: #ffffff;
        color: #000000;
        font-family: "DejaVu Sans", sans-serif;
        font-size: 10px;
        line-height: 1.55;
        padding: 35px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        max-width: 820px;
        margin: 0 auto;
    }
    .doc-main-table {
        width: 100%;
        border-collapse: collapse;
        max-width: 790px;
        margin: 0 auto;
    }
    .doc-main-table td {
        white-space: wrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .doc-brand {
        text-align: right;
    }
    .doc-brand strong {
        display: block;
        line-height: 1.55;
        margin-bottom: 4px;
    }
    .doc-brand p {
        margin: 0;
    }
    .doc-meta-table td {
        padding: 0 !important;
        height: 19px;
    }
    .doc-header-strong {
        border-bottom: 2px solid #808080;
        border-top: 2px solid #808080;
        padding: 5px;
        font-size: 16px;
        display: block;
        margin: 10px 0;
        text-align: center;
        font-weight: bold;
    }
    .doc-table-header {
        font-weight: bold;
        background: #bbb !important;
        border-bottom: 1px solid #808080;
    }
    .doc-table-header td {
        border-bottom: 1px solid #808080;
    }
    .doc-item-row {
        border-top: 1px solid #808080;
        border-bottom: 1px solid #808080;
    }
    .doc-item-row td {
        border-top: 1px solid #808080;
        border-bottom: 1px solid #808080;
        font-size: 10px;
        height: 30px;
        line-height: 1.55;
        padding-top: 3px;
        padding-bottom: 3px;
        vertical-align: middle;
    }
    .doc-alt-toplam-table {
        width: 100%;
        border-collapse: collapse;
    }
    .doc-alt-toplam-table tr {
        border-bottom: 1px solid #808080;
    }
    .doc-alt-toplam-table td {
        height: 22px;
        vertical-align: middle;
    }
    .doc-border-bottom-1 {
        border-bottom: 1px solid #808080;
    }
    .doc-border-none {
        border: none !important;
    }
</style>

<div class="customer-manage-wrapper">
    <!-- Header Card -->
    <div class="customer-header-card animate-fade-in">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fa <?php echo $id > 0 ? 'fa-pencil-square-o' : 'fa-plus-circle'; ?>"></i>
                </div>
                <div class="header-title">
                    <h4><?php echo $id > 0 ? 'Müşteri Düzenle' : 'Yeni Müşteri Ekle'; ?></h4>
                    <?php if ($id > 0): ?>
                        <span class="customer-id-badge">
                            <i class="fa fa-tag"></i> Firma ID: #<?php echo $id; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-actions">
                <a href="index.php?p=customers/list" class="btn-header btn-header-list">
                    <i class="fa fa-list"></i> Listeye Dön
                </a>
                <button type="button" id="saveCustomer" class="btn-header btn-header-save">
                    <i class="fa fa-save"></i> Kaydet
                </button>
            </div>
        </div>
    </div>

    <!-- Özet Bilgiler (Sadece Düzenleme Modunda Gösterilir) -->
    <?php if ($id > 0): ?>
    <div id="customerStatsGrid" class="customer-stats-grid animate-fade-in">
        <!-- 1. Toplam Servis Sayısı -->
        <div class="customer-stat-minimal">
            <div class="customer-stat-top">
                <div>
                    <div class="customer-stat-label">Toplam Servis Sayısı</div>
                    <div class="customer-stat-number text-primary"><?php echo $pjs; ?></div>
                </div>
                <div class="customer-stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #2563eb;">
                    <i class="fa fa-gears"></i>
                </div>
            </div>
            <div class="customer-stat-bottom">
                <a target="_blank" class="small weight-600 text-primary d-inline-flex align-items-center" href="servisler?cid=<?php echo $id ?>" style="text-decoration: none;">
                    Tümünü Görüntüle <i class="fa fa-arrow-right ml-1 font-10"></i>
                </a>
            </div>
        </div>

        <!-- 2. Toplam Teklif Sayısı -->
        <div class="customer-stat-minimal">
            <div class="customer-stat-top">
                <div>
                    <div class="customer-stat-label">Toplam Teklif Sayısı</div>
                    <div class="customer-stat-number text-success"><?php echo $ojs; ?></div>
                </div>
                <div class="customer-stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
                    <i class="fa fa-handshake-o"></i>
                </div>
            </div>
            <div class="customer-stat-bottom">
                <a target="_blank" class="small weight-600 text-success d-inline-flex align-items-center" href="index.php?p=offers&cid=<?php echo $id ?>" style="text-decoration: none;">
                    Tümünü Görüntüle <i class="fa fa-arrow-right ml-1 font-10"></i>
                </a>
            </div>
        </div>

        <!-- 3. Toplam Rapor Sayısı -->
        <div class="customer-stat-minimal">
            <div class="customer-stat-top">
                <div>
                    <div class="customer-stat-label">Toplam Rapor Sayısı</div>
                    <div class="customer-stat-number text-danger"><?= (int)$reportCount ?></div>
                </div>
                <div class="customer-stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;">
                    <i class="fa fa-file-text-o"></i>
                </div>
            </div>
            <div class="customer-stat-bottom">
                <a target="_blank" class="small weight-600 text-danger d-inline-flex align-items-center" href="index.php?p=reports/reports" style="text-decoration: none;">
                    Tümünü Görüntüle <i class="fa fa-arrow-right ml-1 font-10"></i>
                </a>
            </div>
        </div>

        <!-- 4. Son Oluşturulan Teklif -->
        <div class="customer-stat-minimal">
            <div class="customer-stat-top">
                <div style="min-width: 0; flex: 1;">
                    <div class="customer-stat-label">Son Oluşturulan Teklif</div>
                    <div class="customer-stat-number text-warning" style="font-size: 16px;">
                        <?php echo !empty($sonteklif["offerNumber"]) ? htmlspecialchars($sonteklif["offerNumber"], ENT_QUOTES, 'UTF-8') : '-'; ?>
                    </div>
                </div>
                <div class="customer-stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #d97706;">
                    <i class="fa fa-file-text-o"></i>
                </div>
            </div>
            <div class="customer-stat-bottom">
                <?php if (!empty($sonteklif["id"])): ?>
                    <a target="_blank" class="small weight-600 text-warning d-inline-flex align-items-center" href="index.php?p=offers/offer-manage&id=<?php echo $sonteklif["id"]; ?>" style="text-decoration: none;">
                        Teklife Git <i class="fa fa-arrow-right ml-1 font-10"></i>
                    </a>
                <?php else: ?>
                    <span class="text-muted font-11">Teklif bulunamadı</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 5. Son Oluşturulan Servis -->
        <div class="customer-stat-minimal">
            <div class="customer-stat-top">
                <div style="min-width: 0; flex: 1;">
                    <div class="customer-stat-label">Son Oluşturulan Servis</div>
                    <div class="customer-stat-number text-purple" style="font-size: 15px;">
                        <?php echo !empty($servicestype["title"]) ? htmlspecialchars($servicestype["title"], ENT_QUOTES, 'UTF-8') : '-'; ?>
                    </div>
                </div>
                <div class="customer-stat-icon" style="background: rgba(147, 51, 234, 0.1); color: #7c3aed;">
                    <i class="fa fa-wrench"></i>
                </div>
            </div>
            <div class="customer-stat-bottom">
                <?php if (!empty($ojsp["id"])): ?>
                    <a target="_blank" class="small weight-600 text-purple d-inline-flex align-items-center" href="index.php?p=service/manage&id=<?php echo $ojsp["id"]; ?>" style="text-decoration: none;">
                        Servise Git <i class="fa fa-arrow-right ml-1 font-10"></i>
                    </a>
                <?php else: ?>
                    <span class="text-muted font-11">Servis bulunamadı</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- SEKMELİ YAPI BAŞLIĞI (EKİP ÜYELERİ STİLİNDE SEGMENT PILLS) -->
    <?php if ($id > 0): ?>
    <div class="customer-status-tabs mb-3 animate-fade-in" id="customerDetailTabs" role="tablist">
        <button type="button" class="customer-status-tab active" id="tab-btn-info" data-tab-target="#tab-pane-info" role="tab" aria-selected="true" title="Firma Bilgilerini Görüntüle ve Düzenle">
            <i class="fa fa-building-o"></i>
            <span>Firma Bilgileri & Düzenleme</span>
        </button>
        <button type="button" class="customer-status-tab" id="tab-btn-offers" data-tab-target="#tab-pane-offers" role="tab" aria-selected="false" title="Teklif İcmali ve Alt Kalem Dökümü">
            <i class="fa fa-calculator text-primary"></i>
            <span>Teklif İcmali & Kalem Dökümü</span>
            <span class="customer-tab-badge"><?= (int)$ojs ?></span>
        </button>
        <button type="button" class="customer-status-tab" id="tab-btn-reports" data-tab-target="#tab-pane-reports" role="tab" aria-selected="false" title="Rapor İcmali ve Alt Ekipman Dökümü">
            <i class="fa fa-file-text-o text-danger"></i>
            <span>Rapor İcmali & Ekipman Dökümü</span>
            <span class="customer-tab-badge"><?= (int)$reportCount ?></span>
        </button>
    </div>
    <?php endif; ?>

    <!-- SEKMELİ İÇERİK ALANI -->
    <div class="tab-content" id="customerDetailTabContent">
        <!-- 1. SEKME: FİRMA BİLGİLERİ -->
        <div class="tab-pane fade show active" id="tab-pane-info" role="tabpanel" aria-labelledby="tab-btn-info">

    <!-- Form Card -->
    <div class="form-card animate-fade-in" id="customerFormCard">
        <div class="form-card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center" style="gap: 12px; cursor: pointer;" id="toggleCustomerFormTitle" title="Firma Bilgileri Formunu Daralt / Genişlet">
                <div class="card-icon">
                    <i class="fa fa-user-plus"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-16 weight-600">Firma Bilgileri</h5>
                    <p class="mb-0 text-muted font-12">Lütfen firma detaylarını ve iletişim bilgilerini eksiksiz doldurunuz.</p>
                </div>
            </div>
            <?php if ($id > 0): ?>
            <div>
                <button type="button" id="toggleCustomerStats" class="btn btn-outline-secondary btn-sm" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;">
                    <i class="fa fa-chevron-up"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div id="customerFormBody" class="customer-form-body">
            <form enctype="multipart/form-data" action="" id="customerForm" method="POST">
            <input type="hidden" name="company_id" id="company_id" value="<?php echo $id ?>">
            


            <div class="form-grid">
                
                <!-- Firma Adı -->
                <div class="form-field">
                    <label for="company"><font color="red">(*)</font> Firma Adı</label>
                    <input required name="company" id="company" type="text" value="<?php echo $customer->company ?? ''; ?>" class="form-control">
                </div>

                <!-- E-Posta -->
                <div class="form-field">
                    <label for="cemail"><font color="red">(*)</font> E-Posta</label>
                    <input required name="cemail" id="cemail" type="text" value="<?php echo $customer->email ?? ''; ?>" class="form-control">
                </div>

                <!-- Grup -->
                <div class="form-field">
                    <label for="categoryName"><font color="red">(*)</font> Grup</label>
                    <?php echo customer::getCustomerGroups("categoryName", $customer->grp ?? '', 'form-control select2'); ?>
                </div>

                <!-- Yetkili Ad-Soyad -->
                <div class="form-field">
                    <label for="yetkili">Yetkili Ad-Soyad</label>
                    <input name="yetkili" id="yetkili" type="text" class="form-control" value="<?php echo $customer->yetkili ?? '' ?>">
                </div>

                <!-- İl / İlçe -->
                <div class="form-field">
                    <label><font color="red">(*)</font> İl / İlçe</label>
                    <div class="row-inline">
                        <div>
                            <?php echo Helper::selectCity("il", $customer->city ?? '', 'form-control select2'); ?>
                        </div>
                        <div>
                            <select name="ilce" id="ilce" class="form-control select2" data-placeholder="İlçe Seçiniz">
                                <option value="<?php echo $customer->ilce ?? ''; ?>">
                                    <?php echo $customer->ilce ?? ''; ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Satış Temsilcisi -->
                <div class="form-field">
                    <label for="represant"><font color="red">(*)</font> Satış Temsilcisi</label>
                    <input placeholder="Temsilci giriniz!" name="represant" id="represant" type="text" class="form-control" value="<?php echo $customer->represant ?? ''; ?>">
                </div>

                <!-- Bölge -->
                <div class="form-field">
                    <label for="region"><font color="red">(*)</font> Bölge</label>
                    <?php echo Helper::selectRegion("region", $customer->region ?? '', 'form-control select2'); ?>
                </div>

                <!-- Telefon -->
                <div class="form-field">
                    <label for="cgsm"><font color="red">(*)</font> Telefon</label>
                    <input required placeholder="05XXXXXXXXX" maxlength="11" minlength="10" name="cgsm" id="cgsm" type="text" value="<?php echo $customer->gsm ?? ''; ?>" class="form-control">
                </div>

                <!-- Ödeme Vadesi -->
                <div class="form-field">
                    <label for="vade">Ödeme Vadesi</label>
                    <input type="text" class="form-control" name="vade" id="vade" value="<?php echo $customer->OdemeVade ?? '' ?>">
                </div>

                <!-- Adres -->
                <div class="form-field full-width">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="customer_address" class="mb-0"><font color="red">(*)</font> Adres</label>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnOpenCustomerMap" style="border-radius: 8px; font-weight: 600; font-size: 12px; padding: 3px 10px; display: inline-flex; align-items: center; gap: 5px; border-color: #3b82f6; color: #1d4ed8; background: #eff6ff;">
                            <i class="fa fa-map-marker" style="color: #ef4444; font-size: 13px;"></i> Haritadan Seç
                        </button>
                    </div>
                    <textarea required name="customer_address" id="customer_address" placeholder="Firma adresi" class="form-control" rows="3"><?php echo $customer->address ?? '' ?></textarea>
                </div>

                <!-- Keşif / Saha Konumu -->
                <div class="form-field full-width">
                    <label for="location">Keşif / Saha Konumu</label>
                    <input name="location" id="location" type="text" class="form-control"
                        placeholder="Keşiflerde otomatik kullanılacak saha adresi veya konumu"
                        value="<?php echo htmlspecialchars($customer->location ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <small class="text-muted">Firma keşif formunda seçildiğinde konum alanına otomatik aktarılır.</small>
                </div>

                <!-- Açıklama -->
                <div class="form-field full-width">
                    <label for="cdesc">Açıklama</label>
                    <textarea name="cdesc" id="cdesc" placeholder="Firma hakkında yöneticilerin görebileceği bir not ekleyebilirsiniz." class="form-control" rows="3"><?php echo $customer->cdesc ?? ''; ?></textarea>
                </div>

            </div>
        </form>
        </div>
    </div>
        </div> <!-- /#tab-pane-info -->

        <?php if ($id > 0): ?>
        <!-- 2. SEKME: TEKLİF İCMALİ -->
        <div class="tab-pane fade" id="tab-pane-offers" role="tabpanel" aria-labelledby="tab-btn-offers">
            <div id="offersIcmalContainer">
                <div class="text-center py-5" id="offersIcmalLoader">
                    <i class="fa fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <div class="text-muted font-14 font-weight-500">Teklif icmali ve alt kalemleri yükleniyor, lütfen bekleyiniz...</div>
                </div>
            </div>
        </div>

        <!-- 3. SEKME: RAPOR İCMALİ -->
        <div class="tab-pane fade" id="tab-pane-reports" role="tabpanel" aria-labelledby="tab-btn-reports">
            <div id="reportsIcmalContainer">
                <div class="text-center py-5" id="reportsIcmalLoader">
                    <i class="fa fa-spinner fa-spin fa-2x text-danger mb-3"></i>
                    <div class="text-muted font-14 font-weight-500">Rapor icmali ve alt ekipman dökümü yükleniyor, lütfen bekleyiniz...</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div> <!-- /#customerDetailTabContent -->
</div> <!-- /.customer-manage-wrapper -->

<!-- ================================================================= -->
<!-- RESMİ FİRMA RAPOR & EKİPMAN İCMALİ - ÖNİZLEME & YAZDIRMA MODALI -->
<!-- ================================================================= -->
<div class="modal fade" id="modalOfficialReportIcmal" tabindex="-1" role="dialog" aria-labelledby="modalOfficialReportIcmalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 950px;">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 25px 60px rgba(0,0,0,0.3); overflow: hidden;">
            <div class="modal-header d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #1e293b, #334155); color: #fff; padding: 14px 20px;">
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(239, 68, 68, 0.2); display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 18px;">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <div>
                        <h5 class="modal-title text-white font-16 weight-600 mb-0" id="modalOfficialReportIcmalLabel">Firma Rapor & Ekipman İcmal Formu</h5>
                        <p class="text-white-50 font-12 mb-0">Seçilen kontrol ve muayene raporları konsolide edilerek resmi icmal formatında sunulur.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="openOfficialReportIcmalPdf()" style="border-radius: 8px; font-weight: 600; padding: 6px 14px;">
                        <i class="fa fa-file-pdf-o mr-1"></i> PDF Aç / İndir
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="printOfficialReportIcmalDirect()" style="border-radius: 8px; font-weight: 600; padding: 6px 14px;">
                        <i class="fa fa-print mr-1"></i> Yazdır
                    </button>
                    <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat" style="opacity: 0.8; outline: none; margin-left: 10px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>

            <div class="modal-body p-4" style="background: #cbd5e1; max-height: 80vh; overflow-y: auto;">
                <!-- Resmi Rapor İcmal Belgesi (Paper) -->
                <div id="officialReportIcmalDocument" class="official-icmal-paper">
                    <!-- 1. Header (Logo & Firma Başlığı) -->
                    <table style="width: 100%; margin-bottom: 6px; border-collapse: collapse;">
                        <tr>
                            <td style="width: 45%; vertical-align: middle;">
                                <img src="src/images/logo.png" style="max-width: 180px; height: auto;" id="reportLogo" alt="company logo">
                            </td>
                            <td style="width: 55%; vertical-align: middle; text-align: right;" class="doc-brand">
                                <strong><?= format_company_header_title(set('company_name')) ?></strong>
                                <p><?= htmlspecialchars(set('company_address') ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                <p>Tel: <?= htmlspecialchars(set('company_phone1') ?? '', ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars(set('company_phone2') ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                <p><?= htmlspecialchars(set('admin_mail') ?? '', ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars(set('panel_url') ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                        </tr>
                    </table>

                    <!-- 2. Form Başlığı -->
                    <div class="doc-header-strong">
                        FİRMA KONTROL VE MUAYENE RAPORLARI İCMAL FORMU
                    </div>

                    <!-- 3. Müşteri & İcmal Meta Bilgileri -->
                    <table class="doc-meta-table" style="width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10px;">
                        <tr>
                            <td style="width: 12%; font-weight: bold; padding: 2px 4px;">Firma :</td>
                            <td style="width: 48%; padding: 2px 4px;"><?= htmlspecialchars($customer->company ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="width: 15%; font-weight: bold; padding: 2px 4px;">İcmal Tarihi :</td>
                            <td style="width: 25%; padding: 2px 4px;"><?= date('d.m.Y H:i') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 4px;">Telefon :</td>
                            <td style="padding: 2px 4px;"><?= htmlspecialchars($customer->gsm ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: bold; padding: 2px 4px;">Rapor Sayısı :</td>
                            <td style="padding: 2px 4px;" id="docReportModalCountBadge">0 Adet</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 4px;">E-Posta :</td>
                            <td style="padding: 2px 4px;"><?= htmlspecialchars($customer->email ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: bold; padding: 2px 4px;">Yetkili :</td>
                            <td style="padding: 2px 4px;"><?= htmlspecialchars($customer->yetkili ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 4px;">Adres :</td>
                            <td style="padding: 2px 4px;" colspan="3"><?= htmlspecialchars(($customer->address ?? '') . ' ' . ($customer->city ?? '') . '/' . ($customer->ilce ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    </table>

                    <!-- 4. Raporlar Tablosu -->
                    <div style="font-weight: bold; font-size: 11px; margin-bottom: 4px; border-bottom: 1px solid #808080; padding-bottom: 2px;">
                        1. SEÇİLEN KONTROL VE MUAYENE RAPORLARI
                    </div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 15px;">
                        <colgroup>
                            <col style="width: 5%;">
                            <col style="width: 22%;">
                            <col style="width: 33%;">
                            <col style="width: 15%;">
                            <col style="width: 15%;">
                            <col style="width: 10%;">
                        </colgroup>
                        <thead>
                            <tr class="doc-table-header" style="background-color: #bbbbbb; font-weight: bold; border-bottom: 1px solid #808080;">
                                <td style="text-align: center;">NO</td>
                                <td>RAPOR NO</td>
                                <td>RAPOR TÜRÜ</td>
                                <td style="text-align: center;">İŞ EMRİ NO</td>
                                <td style="text-align: center;">KONTROL TRH</td>
                                <td style="text-align: center;">EKİPMAN</td>
                            </tr>
                        </thead>
                        <tbody id="docReportSummaryTableBody">
                            <!-- JS ile doldurulacak -->
                        </tbody>
                    </table>

                    <!-- 5. Detaylı Ekipman Dökümü Tablosu -->
                    <div style="font-weight: bold; font-size: 11px; margin-bottom: 4px; border-bottom: 1px solid #808080; padding-bottom: 2px;">
                        2. DETAYLI EKİPMAN, CİHAZ VE TEST KALEMLERİ DÖKÜMÜ
                    </div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <colgroup>
                            <col style="width: 5%;">
                            <col style="width: 18%;">
                            <col style="width: 15%;">
                            <col style="width: 32%;">
                            <col style="width: 20%;">
                            <col style="width: 10%;">
                        </colgroup>
                        <thead>
                            <tr class="doc-table-header" style="background-color: #bbbbbb; font-weight: bold; border-bottom: 1px solid #808080;">
                                <td style="text-align: center;">NO</td>
                                <td>RAPOR NO</td>
                                <td style="text-align: center;">CİHAZ / TEST NO</td>
                                <td>CİHAZ CİNSİ / AÇIKLAMA</td>
                                <td>BULUNDUĞU BÖLGE</td>
                                <td style="text-align: center;">DURUM</td>
                            </tr>
                        </thead>
                        <tbody id="docReportItemsTableBody">
                            <!-- JS ile doldurulacak -->
                        </tbody>
                    </table>

                    <!-- 6. Dip Notlar -->
                    <div id="docReportNotesDisplay" style="margin-top: 15px; padding: 6px 10px; border: 1px dashed #808080; font-size: 9px; line-height: 1.4; display: none;">
                        <strong>İCMAL NOTLARI:</strong><br>
                        <span id="docReportNotesText"></span>
                    </div>

                    <!-- 7. İmzalar -->
                    <table style="width: 100%; margin-top: 25px; border-collapse: collapse; border: none;">
                        <tr>
                            <td style="width: 45%; text-align: center; border: none; font-size: 9px; vertical-align: top;">
                                <strong>KONTROLÜ YAPAN / TEKNİSYEN</strong><br><br><br>
                                <span>İmza / Kaşe</span>
                            </td>
                            <td style="width: 10%; border: none;"></td>
                            <td style="width: 45%; text-align: center; border: none; font-size: 9px; vertical-align: top;">
                                <strong>FİRMA YETKİLİSİ</strong><br><br><br>
                                <span>Teslim Alan İmza</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ================================================================= -->
<!-- RESMİ FİYAT TEKLİF FORMU - İCMAL ÖNİZLEME & YAZDIRMA MODALI -->
<!-- ================================================================= -->
<div class="modal fade" id="modalOfficialIcmalQuote" tabindex="-1" role="dialog" aria-labelledby="modalOfficialIcmalQuoteLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 950px;">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 25px 60px rgba(0,0,0,0.3); overflow: hidden;">
            <div class="modal-header d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #1e293b, #334155); color: #fff; padding: 14px 20px;">
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; color: #60a5fa; font-size: 18px;">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <div>
                        <h5 class="modal-title text-white font-16 weight-600 mb-0" id="modalOfficialIcmalQuoteLabel">Fiyat Teklif Formu (İcmal Raporu)</h5>
                        <p class="text-white-50 font-12 mb-0">Seçilen teklifler tek satırda birleştirilerek resmi teklif formatında sunulur.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="openOfficialIcmalPdf()" style="border-radius: 8px; font-weight: 600; padding: 6px 14px;">
                        <i class="fa fa-file-pdf-o mr-1"></i> PDF Aç / İndir
                    </button>
                    <button type="button" class="btn btn-success btn-sm" onclick="printOfficialIcmalDirect()" style="border-radius: 8px; font-weight: 600; padding: 6px 14px;">
                        <i class="fa fa-print mr-1"></i> Yazdır
                    </button>
                    <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat" style="opacity: 0.8; outline: none; margin-left: 10px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>

            <div class="modal-body p-4" style="background: #cbd5e1; max-height: 80vh; overflow-y: auto;">
                <!-- Resmi Teklif Sayfası (Paper) -->
                <div id="officialIcmalDocument" class="official-icmal-paper">
                    <!-- 1. Header (Logo & Firma Başlığı) -->
                    <table style="width: 100%; margin-bottom: 6px; border-collapse: collapse;">
                        <tr>
                            <td style="width: 45%; vertical-align: middle;">
                                <img src="src/images/logo.png" style="max-width: 180px; height: auto;" id="logo" alt="company logo">
                            </td>
                            <td style="width: 55%; vertical-align: middle; text-align: right;" class="doc-brand">
                                <strong><?= format_company_header_title(set('company_name')) ?></strong>
                                <p><?= htmlspecialchars(set('company_address') ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                <p>Tel: <?= htmlspecialchars(set('company_phone1') ?? '', ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars(set('company_phone2') ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                <p><?= htmlspecialchars(set('admin_mail') ?? '', ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars(set('panel_url') ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                        </tr>
                    </table>

                    <!-- 2. Form Başlığı -->
                    <div class="doc-header-strong">
                        FİYAT TEKLİF FORMU
                    </div>

                    <!-- 3. Müşteri & Teklif Meta Bilgileri -->
                    <table class="doc-meta-table" style="width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10px;">
                        <tr>
                            <td style="width: 10%; font-weight: bold; padding: 2px 4px;">Firma :</td>
                            <td style="width: 50%; padding: 2px 4px;" id="docMetaCompany"><?= htmlspecialchars($customer->company ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="width: 15%; font-weight: bold; padding: 2px 4px;">Teklif No :</td>
                            <td style="width: 25%; padding: 2px 4px;" id="docMetaOfferNo">İCMAL DOSYASI</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 4px;">Telefon :</td>
                            <td style="padding: 2px 4px;" id="docMetaGsm"><?= htmlspecialchars($customer->gsm ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: bold; padding: 2px 4px;">Tarih :</td>
                            <td style="padding: 2px 4px;" id="docMetaDate"><?= date('d.m.Y') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 4px;">E Posta :</td>
                            <td style="padding: 2px 4px;" id="docMetaEmail"><?= htmlspecialchars($customer->email ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: bold; padding: 2px 4px;">Referans :</td>
                            <td style="padding: 2px 4px;">İCMAL DOSYASI</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 4px;">İlgili :</td>
                            <td style="padding: 2px 4px;" id="docMetaYetkili"><?= htmlspecialchars($customer->yetkili ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: bold; padding: 2px 4px;">Teklif Konusu :</td>
                            <td style="padding: 2px 4px;" id="docMetaSubject"><?= htmlspecialchars($customer->company ?? '', ENT_QUOTES, 'UTF-8') ?> - İCMAL DOSYASI</td>
                        </tr>
                    </table>

                    <!-- 4. Giriş Açıklama Metni -->
                    <div id="docHeaderContentDisplay" style="padding: 30px 0; font-size: 10px;">
                        Sayın talep etmiş olduğunuz ürün/hizmetlere ilişkin fiyat teklifimiz aşağıda bilgilerinize sunulmuştur. Fiyatlarımızın makul gelmesini umut eder, iyi çalışmalar dileriz.
                    </div>

                    <!-- 5. Teklif Kalemleri Tablosu -->
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <colgroup>
                            <col style="width: 5%;">
                            <col style="width: 45%;">
                            <col style="width: 10%;">
                            <col style="width: 20%;">
                            <col style="width: 20%;">
                        </colgroup>
                        <tbody>
                            <tr class="doc-table-header" style="background-color: #bbbbbb; font-weight: bold; border-bottom: 1px solid #808080;">
                                <td style="width: 5%; text-align: left; font-weight: bold; border-bottom: 1px solid #808080;">NO</td>
                                <td style="width: 45%; text-align: left; font-weight: bold; border-bottom: 1px solid #808080;">ÜRÜN / HİZMET AÇIKLAMASI</td>
                                <td style="width: 10%; text-align: right; font-weight: bold; border-bottom: 1px solid #808080;">MİKTAR</td>
                                <td style="width: 20%; text-align: right; font-weight: bold; border-bottom: 1px solid #808080;">BİRİM FİYAT</td>
                                <td style="width: 20%; text-align: right; font-weight: bold; border-bottom: 1px solid #808080;">TUTAR</td>
                            </tr>
                        </tbody>
                        <tbody id="docItemsTableBody">
                            <!-- JS Satırları -->
                        </tbody>
                    </table>

                    <!-- 6. Dip Toplamlar Tablosu -->
                    <table class="doc-alt-toplam-table" style="width: 100%; margin-top: 15px; border-collapse: collapse;">
                        <tr class="doc-border-none text-right">
                            <td style="width: 60%;"></td>
                            <td style="width: 20%; padding: 4px 6px;" class="doc-border-bottom-1">ARA TOPLAM</td>
                            <td style="width: 20%; padding: 4px 6px;" class="doc-border-bottom-1" id="docSubTotalVal">0,00 TRY</td>
                        </tr>
                        <tr class="doc-border-none text-right">
                            <td style="width: 60%;"></td>
                            <td style="width: 20%; padding: 4px 6px;" class="doc-border-bottom-1">KDV %20</td>
                            <td style="width: 20%; padding: 4px 6px;" class="doc-border-bottom-1" id="docKdvVal">0,00 TRY</td>
                        </tr>
                        <tr class="doc-border-none text-right">
                            <td style="width: 60%;"></td>
                            <td style="width: 20%; padding: 4px 6px;" class="doc-border-bottom-1">KDV DAHİL</td>
                            <td style="width: 20%; padding: 4px 6px;" class="doc-border-bottom-1" id="docKdvDahilVal">0,00 TRY</td>
                        </tr>
                        <tr class="doc-border-none text-right">
                            <td style="width: 60%;"></td>
                            <td style="width: 20%; padding: 5px 6px; background: #bbb; font-weight: bold;" class="doc-border-bottom-1">GENEL TOPLAM</td>
                            <td style="width: 20%; padding: 5px 6px; background: #bbb; font-weight: bold;" class="doc-border-bottom-1" id="docGrandTotalVal">0,00 TRY</td>
                        </tr>
                    </table>

                    <!-- 7. Dipnot & Şartlar -->
                    <div id="docFooterContentDisplay" style="padding: 15px 0 20px 0; font-size: 8.5px; color: #475569;">
                        <p><strong>1.</strong> Fiyatlarımıza KDV dahildir/dahil edilmiştir.</p>
                        <p><strong>2.</strong> Ödeme Vadesi: Sipariş onayı ile birlikte belirlenen ödeme planına göredir.</p>
                        <p><strong>3.</strong> Teklif Geçerlilik Süresi: Teklif tarihinden itibaren 15 gündür.</p>
                    </div>

                    <!-- 8. İmzalar -->
                    <table style="width: 100%; border-collapse: collapse; text-align: center; margin-top: 10px;">
                        <tr>
                            <td style="width: 50%; font-weight: bold;">Oluşturan</td>
                            <td style="width: 50%; font-weight: bold;">Sipariş Onayı</td>
                        </tr>
                        <tr>
                            <td style="width: 50%; padding-top: 5px;"><?= htmlspecialchars($_SESSION['name'] ?? 'Yetkili', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="width: 50%; padding-top: 5px;">Firma Kaşesi / İmza</td>
                        </tr>
                        <tr>
                            <td style="width: 50%; color: #64748b; font-size: 9px;"><?= htmlspecialchars($_SESSION['title'] ?? 'Müşteri Temsilcisi', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="width: 50%; color: #64748b; font-size: 9px;">.</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="modal-footer" style="background: #f1f5f9; border-top: 1px solid #e2e8f0; padding: 12px 20px;">
                <button type="button" class="btn btn-light" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 500;">Kapat</button>
                <button type="button" class="btn btn-primary" onclick="printOfficialIcmalDirect()" style="border-radius: 8px; font-weight: 600; padding: 8px 24px;">
                    <i class="fa fa-print mr-1"></i> Yazdır / PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Haritadan Adres Seçim Modalı -->
<div class="modal fade" id="customerMapModal" tabindex="-1" role="dialog" aria-labelledby="customerMapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 850px;">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.25); overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #334155); color: #fff; padding: 16px 20px;">
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(239, 68, 68, 0.2); display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 18px;">
                        <i class="fa fa-map-marker"></i>
                    </div>
                    <div>
                        <h5 class="modal-title text-white font-16 weight-600 mb-0" id="customerMapModalLabel">Haritadan Konum & Adres Seçimi</h5>
                        <p class="text-white-50 font-12 mb-0">Haritada tıklayarak veya arama yaparak adresi otomatik belirleyin.</p>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" id="btnCloseCustomerMapModal" aria-label="Kapat" style="opacity: 0.8; outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3" style="background: #f8fafc;">
                <!-- Arama ve Konum Barı -->
                <div class="row g-2 mb-2">
                    <div class="col-md-8 mb-2 mb-md-0">
                        <div class="input-group">
                            <input type="text" id="mapSearchInput" class="form-control" placeholder="Örn: Nilüfer Bursa, Çalı Eflatun Cad. veya firma adı..." style="border-radius: 8px 0 0 8px; border: 1px solid #cbd5e1;">
                            <div class="input-group-append">
                                <button type="button" id="btnMapSearch" class="btn btn-primary" style="border-radius: 0 8px 8px 0; font-weight: 500;">
                                    <i class="fa fa-search"></i> Ara
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="button" id="btnMapLocateMe" class="btn btn-outline-secondary btn-block" style="border-radius: 8px; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 6px; height: 38px;">
                            <i class="fa fa-crosshairs text-primary"></i> Konumumu Bul
                        </button>
                    </div>
                </div>

                <!-- Arama Sonuçları Listesi (Varsa) -->
                <div id="mapSearchResults" class="list-group mb-2 d-none" style="max-height: 150px; overflow-y: auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);"></div>

                <!-- Harita Konteyneri -->
                <div style="position: relative; border-radius: 12px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
                    <div id="customerAddressMap" style="height: 380px; width: 100%; background: #e2e8f0;"></div>
                    <div id="mapLoadingSpinner" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); z-index: 1000; align-items: center; justify-content: center; font-weight: 600; color: #1e293b; gap: 10px;">
                        <i class="fa fa-circle-o-notch fa-spin fa-2x text-primary"></i> <span>Adres çözümleniyor...</span>
                    </div>
                </div>

                <!-- Seçilen Adres Önizleme Kartı -->
                <div class="mt-3 p-3 rounded" style="background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="weight-600 font-13 text-dark">
                            <i class="fa fa-check-circle text-success mr-1"></i> Tespit Edilen Adres:
                        </span>
                        <span id="mapSelectedCoords" class="badge badge-light text-muted font-11">Koordinat: -</span>
                    </div>
                    <div id="mapSelectedAddressText" class="text-dark font-13" style="line-height: 1.4; min-height: 36px; word-break: break-word;">
                        Haritadan bir nokta seçiniz veya arama yapınız.
                    </div>
                    <div class="d-flex flex-wrap mt-2" id="mapAddressBadges" style="gap: 8px;">
                        <span class="badge badge-primary py-1 px-2" id="badgeIl" style="display:none; font-weight: 500;">İl: -</span>
                        <span class="badge badge-info py-1 px-2" id="badgeIlce" style="display:none; font-weight: 500;">İlçe: -</span>
                        <span class="badge badge-secondary py-1 px-2" id="badgeMahalle" style="display:none; font-weight: 500;">Mahalle: -</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f1f5f9; border-top: 1px solid #e2e8f0; padding: 12px 20px;">
                <button type="button" class="btn btn-light" data-dismiss="modal" data-bs-dismiss="modal" id="btnCancelCustomerMapModal" style="border-radius: 8px; font-weight: 500;">İptal</button>
                <button type="button" id="btnApplyMapAddress" class="btn btn-primary" style="border-radius: 8px; font-weight: 600; padding: 8px 20px;" disabled>
                    <i class="fa fa-check mr-1"></i> Bu Adresi Aktar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Leaflet Harita Kütüphanesi -->
<link rel="stylesheet" href="src/plugins/leaflet/leaflet.css?v=1.9.4" />
<script src="src/plugins/leaflet/leaflet.js?v=1.9.4"></script>
<script src="pages/1/customers/customer.js?v=<?php echo file_exists(__DIR__ . '/customer.js') ? filemtime(__DIR__ . '/customer.js') : time(); ?>"></script>
<script>
var customerId = <?= (int)$id ?>;
var offersLoaded = false;
var reportsLoaded = false;

// ==========================================
// AJAX İLE TEKLİF İCMALİ YÜKLEME
// ==========================================
function loadOffersIcmal(force) {
    if (customerId <= 0) return;
    if (offersLoaded && !force) return;

    var $container = $('#offersIcmalContainer');
    $container.html(
        '<div class="text-center py-5" id="offersIcmalLoader">' +
        '    <i class="fa fa-spinner fa-spin fa-2x text-primary mb-3"></i>' +
        '    <div class="text-muted font-14 font-weight-500">Teklif icmali ve alt kalemleri yükleniyor, lütfen bekleyiniz...</div>' +
        '</div>'
    );

    $.ajax({
        url: 'pages/1/customers/ajax-offers-icmal.php',
        type: 'POST',
        data: { id: customerId },
        success: function(html) {
            $container.html(html);
            offersLoaded = true;

            // Select2 & WYSIHTML5 başlat
            if ($.fn.select2) {
                $('#icmalStatusFilter').select2({ minimumResultsForSearch: Infinity, width: '100%' });
                $('#icmalHeaderTemplate, #icmalFooterTemplate').select2({ width: '100%' });
            }
            if (typeof $.fn.wysihtml5 !== 'undefined') {
                $('#icmalHeaderContent, #icmalFooterContent').each(function() {
                    if (!$(this).data('wysihtml5')) {
                        $(this).wysihtml5({ html: true, fa: true });
                    }
                    $(this).hide();
                });
            }

            // KPI Durumunu uygula
            var icmalKpiStorageKey = 'aydinogullari_kpi_customer_offers_collapsed';
            var isIcmalKpiCollapsed = localStorage.getItem(icmalKpiStorageKey) === 'true';
            applyIcmalKpiState(isIcmalKpiCollapsed, false);
            recalculateIcmalTotals();
        },
        error: function(xhr) {
            $container.html(
                '<div class="alert alert-danger font-13 m-3">' +
                '    <i class="fa fa-exclamation-triangle mr-2"></i>Teklif icmali yüklenirken bir hata oluştu. (Hata Kodu: ' + xhr.status + ')' +
                '    <button type="button" class="btn btn-sm btn-outline-danger ml-3" onclick="loadOffersIcmal(true)"><i class="fa fa-refresh mr-1"></i>Tekrar Dene</button>' +
                '</div>'
            );
        }
    });
}

// ==========================================
// AJAX İLE RAPOR İCMALİ YÜKLEME
// ==========================================
function loadReportsIcmal(force) {
    if (customerId <= 0) return;
    if (reportsLoaded && !force) return;

    var $container = $('#reportsIcmalContainer');
    $container.html(
        '<div class="text-center py-5" id="reportsIcmalLoader">' +
        '    <i class="fa fa-spinner fa-spin fa-2x text-danger mb-3"></i>' +
        '    <div class="text-muted font-14 font-weight-500">Rapor icmali ve alt ekipman dökümü yükleniyor, lütfen bekleyiniz...</div>' +
        '</div>'
    );

    $.ajax({
        url: 'pages/1/customers/ajax-reports-icmal.php',
        type: 'POST',
        data: { id: customerId },
        success: function(html) {
            $container.html(html);
            reportsLoaded = true;

            // Select2 başlat
            if ($.fn.select2) {
                $('#icmalReportTypeFilter').select2({ minimumResultsForSearch: Infinity, width: '100%' });
            }

            // KPI Durumunu uygula
            var reportKpiStorageKey = 'aydinogullari_kpi_customer_reports_collapsed';
            var isReportKpiCollapsed = localStorage.getItem(reportKpiStorageKey) === 'true';
            applyReportKpiState(isReportKpiCollapsed, false);
            recalculateReportIcmalTotals();
        },
        error: function(xhr) {
            $container.html(
                '<div class="alert alert-danger font-13 m-3">' +
                '    <i class="fa fa-exclamation-triangle mr-2"></i>Rapor icmali yüklenirken bir hata oluştu. (Hata Kodu: ' + xhr.status + ')' +
                '    <button type="button" class="btn btn-sm btn-outline-danger ml-3" onclick="loadReportsIcmal(true)"><i class="fa fa-refresh mr-1"></i>Tekrar Dene</button>' +
                '</div>'
            );
        }
    });
}

function applyIcmalKpiState(collapsed, animate) {
    var container = $('#offerIcmalKpiContainer');
    var btnIcon = $('#toggleOfferIcmalKpi i');
    if (collapsed) {
        if (animate) container.slideUp(200); else container.hide();
        btnIcon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    } else {
        if (animate) container.slideDown(200); else container.show();
        btnIcon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }
}

function applyReportKpiState(collapsed, animate) {
    var container = $('#reportIcmalKpiContainer');
    var btnIcon = $('#toggleReportIcmalKpi i');
    if (collapsed) {
        if (animate) container.slideUp(200); else container.hide();
        btnIcon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    } else {
        if (animate) container.slideDown(200); else container.show();
        btnIcon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }
}

// Editör İçerik Okuma ve Yazma Yardımcıları
window.getEditorContent = function(wrapperSelector) {
    var $wrapper = $(wrapperSelector);
    var $textarea = $wrapper.find('textarea');
    var editorData = $textarea.data('wysihtml5');
    if (editorData && editorData.editor) {
        return editorData.editor.getValue();
    }
    var iframeBody = $wrapper.find('.wysihtml5-sandbox').contents().find('body').html();
    if (iframeBody !== undefined && iframeBody !== '') {
        return iframeBody;
    }
    return $textarea.val() || '';
};

function setEditorContent(wrapperSelector, content) {
    var $wrapper = $(wrapperSelector);
    var $textarea = $wrapper.find('textarea');
    $textarea.val(content);
    var editorData = $textarea.data('wysihtml5');
    if (editorData && editorData.editor) {
        editorData.editor.setValue(content);
    } else {
        $wrapper.find('.wysihtml5-sandbox').contents().find('body').html(content);
    }
}

// Teklif İcmali Dip Toplam ve Seçim Sayaçlarını Yeniden Hesapla
function recalculateIcmalTotals() {
    var visibleCount = 0;
    var selectedCount = 0;
    var totalSelectedAmount = 0;
    var totalSelectedItems = 0;

    $('.offer-row:visible').each(function() {
        visibleCount++;
        var $cb = $(this).find('.offer-select-cb');
        if ($cb.is(':checked')) {
            selectedCount++;
            totalSelectedAmount += parseFloat($(this).data('amount')) || 0;
            totalSelectedItems += parseInt($(this).data('items')) || 0;
        }
    });

    $('#icmalFooterOffersCount').text(selectedCount + ' / ' + visibleCount);
    $('#icmalFooterItems').text(totalSelectedItems + ' Kalem');
    $('#icmalFooterTotal').text(totalSelectedAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺');
    $('#icmalSelectedCountBadge').text(selectedCount + ' Seçili');
}

// Teklif Arama ve Filtreleme
function filterOfferTable() {
    var searchTerm = $('#icmalSearchInput').val().toLowerCase().trim();
    var statusFilter = $('#icmalStatusFilter').val();
    var visibleCount = 0;

    $('.offer-row').each(function() {
        var $row = $(this);
        var offerId = $row.data('offer-id');
        var statu = $row.data('statu').toString();
        var rowText = $row.text().toLowerCase();

        var matchesSearch = searchTerm === '' || rowText.indexOf(searchTerm) > -1;
        var matchesStatus = statusFilter === 'all' || statu === statusFilter;

        if (matchesSearch && matchesStatus) {
            $row.show();
            visibleCount++;
        } else {
            $row.hide();
            $row.removeClass('is-open');
            $('#offer-details-' + offerId).hide();
        }
    });

    $('#icmalOfferCountBadge').text(visibleCount + ' Teklif');
    recalculateIcmalTotals();
}

// Rapor İcmali Dip Toplam ve Seçim Sayaçlarını Yeniden Hesapla
function recalculateReportIcmalTotals() {
    var visibleCount = 0;
    var selectedCount = 0;
    var totalSelectedItems = 0;

    $('.report-row:visible').each(function() {
        visibleCount++;
        var $cb = $(this).find('.report-select-cb');
        if ($cb.is(':checked')) {
            selectedCount++;
            totalSelectedItems += parseInt($(this).data('items')) || 0;
        }
    });

    $('#icmalFooterReportsCount').text(selectedCount + ' / ' + visibleCount);
    $('#icmalFooterReportItems').text(totalSelectedItems + ' Ekipman');
    $('#icmalReportSelectedCountBadge').text(selectedCount + ' Seçili');
}

// Rapor Arama ve Filtreleme
function filterReportTable() {
    var searchTerm = $('#icmalReportSearchInput').val().toLowerCase().trim();
    var typeFilter = $('#icmalReportTypeFilter').val();
    var visibleCount = 0;

    $('.report-row').each(function() {
        var $row = $(this);
        var reportId = $row.data('report-id');
        var reportType = $row.data('type').toString();
        var rowText = $row.text().toLowerCase();

        var matchesSearch = searchTerm === '' || rowText.indexOf(searchTerm) > -1;
        var matchesType = typeFilter === 'all' || reportType === typeFilter;

        if (matchesSearch && matchesType) {
            $row.show();
            visibleCount++;
        } else {
            $row.hide();
            $row.removeClass('is-open');
            $('#report-details-' + reportId).hide();
        }
    });

    $('#icmalReportCountBadge').text(visibleCount + ' Rapor');
    recalculateReportIcmalTotals();
}

$(document).ready(function () {
    // ==========================================
    // 1. ÜST ÖZET (KPI) KARTLARI GİZLE / GÖSTER TOGGLE
    // ==========================================
    var STATS_STORAGE_KEY = 'aydinogullari_customer_manage_stats_collapsed';
    var $statsSection = $('#customerStatsGrid');
    var $toggleStatsBtn = $('#toggleCustomerStats');

    function updateStatsToggleState(isCollapsed, animate) {
        if (!$statsSection.length) return;
        if (isCollapsed) {
            if (animate) {
                $statsSection.stop(true, true).slideUp(200);
            } else {
                $statsSection.hide();
            }
            $toggleStatsBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $toggleStatsBtn.attr('title', 'Özet Kartlarını Göster');
        } else {
            if (animate) {
                $statsSection.stop(true, true).slideDown(200, function() {
                    $(this).css('display', 'grid');
                });
            } else {
                $statsSection.css('display', 'grid').show();
            }
            $toggleStatsBtn.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $toggleStatsBtn.attr('title', 'Özet Kartlarını Gizle');
        }
    }

    var isSavedStatsCollapsed = localStorage.getItem(STATS_STORAGE_KEY) === 'true';
    updateStatsToggleState(isSavedStatsCollapsed, false);

    $(document).off('click', '#toggleCustomerStats').on('click', '#toggleCustomerStats', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var currentlyCollapsed = $statsSection.is(':hidden');
        var newState = !currentlyCollapsed;
        localStorage.setItem(STATS_STORAGE_KEY, newState ? 'true' : 'false');
        updateStatsToggleState(newState, true);
    });

    // ==========================================
    // 2. FİRMA BİLGİLERİ FORM AKORDİYONU
    // ==========================================
    function setCustomerFormCollapsed(isCollapsed, animate) {
        var $card = $('#customerFormCard');
        var $body = $('#customerFormBody');

        if (isCollapsed) {
            if (animate) {
                $body.stop(true, true).slideUp(250, function() {
                    $card.addClass('is-collapsed');
                });
            } else {
                $body.hide();
                $card.addClass('is-collapsed');
            }
        } else {
            $card.removeClass('is-collapsed');
            if (animate) {
                $body.stop(true, true).slideDown(250);
            } else {
                $body.show();
            }
        }
    }

    $(document).off('click', '#toggleCustomerFormTitle').on('click', '#toggleCustomerFormTitle', function (e) {
        e.preventDefault();
        var currentlyCollapsed = $('#customerFormBody').is(':hidden');
        setCustomerFormCollapsed(!currentlyCollapsed, true);
    });

    // ==========================================
    // 3. SEKMELİ YAPI (TABS) & LAZY LOAD GEÇİŞLERİ
    // ==========================================
    window.switchCustomerTab = function(targetTabPane) {
        if (!targetTabPane) return;

        // 1. Segment pill butonlarının aktifliğini güncelle
        $('.customer-status-tab').removeClass('active').attr('aria-selected', 'false');
        $('.customer-status-tab[data-tab-target="' + targetTabPane + '"]').addClass('active').attr('aria-selected', 'true');

        // 2. Tab-pane içeriklerini göster / gizle
        $('#customerDetailTabContent > .tab-pane').removeClass('show active').hide();
        $(targetTabPane).addClass('show active').fadeIn(150);

        // 3. Lazy Load ve URL Hash yönetimi
        if (targetTabPane === '#tab-pane-offers') {
            loadOffersIcmal(false);
            if (history.pushState) {
                history.pushState(null, null, '#offers');
            } else {
                window.location.hash = '#offers';
            }
        } else if (targetTabPane === '#tab-pane-reports') {
            loadReportsIcmal(false);
            if (history.pushState) {
                history.pushState(null, null, '#reports');
            } else {
                window.location.hash = '#reports';
            }
        } else if (targetTabPane === '#tab-pane-info') {
            if (history.pushState) {
                history.pushState(null, null, window.location.pathname + window.location.search);
            }
        }
    };

    // Sekme Butonlarına Tıklandığında
    $(document).on('click', '.customer-status-tab', function(e) {
        e.preventDefault();
        var targetPane = $(this).data('tab-target');
        switchCustomerTab(targetPane);
    });

    // Sayfa Dışından / Butonlarla Sekme Değiştirme
    $(document).on('click', '.btn-switch-tab', function(e) {
        e.preventDefault();
        var tabTarget = $(this).data('target-tab');
        if (tabTarget === 'offers') {
            switchCustomerTab('#tab-pane-offers');
            $('html, body').animate({ scrollTop: $('#customerDetailTabs').offset().top - 70 }, 300);
        } else if (tabTarget === 'reports') {
            switchCustomerTab('#tab-pane-reports');
            $('html, body').animate({ scrollTop: $('#customerDetailTabs').offset().top - 70 }, 300);
        } else if (tabTarget === 'info') {
            switchCustomerTab('#tab-pane-info');
            $('html, body').animate({ scrollTop: 0 }, 300);
        }
    });

    // URL Hash Kontrolü (Sayfa ilk açıldığında)
    var initialHash = window.location.hash;
    if (initialHash === '#offers' || initialHash === '#tab-pane-offers' || initialHash === '#customerOffersIcmalCard') {
        switchCustomerTab('#tab-pane-offers');
    } else if (initialHash === '#reports' || initialHash === '#tab-pane-reports' || initialHash === '#customerReportsIcmalCard') {
        switchCustomerTab('#tab-pane-reports');
    } else {
        switchCustomerTab('#tab-pane-info');
    }

    // Yenileme Butonları
    $(document).on('click', '#btnReloadOffersIcmal', function(e) {
        e.preventDefault();
        loadOffersIcmal(true);
    });

    $(document).on('click', '#btnReloadReportsIcmal', function(e) {
        e.preventDefault();
        loadReportsIcmal(true);
    });

    // ==========================================
    // 4. TEKLİF İCMALİ ETKİLEŞİMLERİ (DELEGATED)
    // ==========================================
    $(document).on('click', '#toggleOfferIcmalKpi', function() {
        var currentCollapsed = $('#offerIcmalKpiContainer').is(':hidden');
        var newCollapsed = !currentCollapsed;
        applyIcmalKpiState(newCollapsed, true);
        localStorage.setItem('aydinogullari_kpi_customer_offers_collapsed', newCollapsed);
    });

    $(document).on('click', '.table-icmal .offer-row', function(e) {
        if ($(e.target).closest('a, button, input[type="checkbox"]').length) {
            return;
        }
        var $row = $(this);
        var offerId = $row.data('offer-id');
        var $detailRow = $('#offer-details-' + offerId);

        if ($detailRow.is(':visible')) {
            $detailRow.slideUp(150, function() { $detailRow.hide(); });
            $row.removeClass('is-open');
        } else {
            $detailRow.show().find('.details-subtable-wrapper').hide().slideDown(150);
            $row.addClass('is-open');
        }
    });

    var allOffersOpen = false;
    $(document).on('click', '#btnToggleAllRows', function() {
        allOffersOpen = !allOffersOpen;
        if (allOffersOpen) {
            $('.offer-row:visible').addClass('is-open');
            $('.offer-details-row').each(function() {
                var offerId = $(this).attr('id').replace('offer-details-', '');
                if ($('.offer-row[data-offer-id="' + offerId + '"]').is(':visible')) {
                    $(this).show().find('.details-subtable-wrapper').show();
                }
            });
            $(this).html('<i class="fa fa-compress mr-1"></i> Tümünü Kapat');
        } else {
            $('.offer-row').removeClass('is-open');
            $('.offer-details-row').hide();
            $(this).html('<i class="fa fa-expand mr-1"></i> Tümünü Aç');
        }
    });

    $(document).on('change', '#selectAllOffers', function() {
        var isChecked = $(this).is(':checked');
        $('.offer-row:visible .offer-select-cb').prop('checked', isChecked);
        recalculateIcmalTotals();
    });

    $(document).on('change', '.table-icmal .offer-select-cb', function() {
        var totalVisibleCb = $('.offer-row:visible .offer-select-cb').length;
        var checkedVisibleCb = $('.offer-row:visible .offer-select-cb:checked').length;
        $('#selectAllOffers').prop('checked', totalVisibleCb > 0 && totalVisibleCb === checkedVisibleCb);
        recalculateIcmalTotals();
    });

    $(document).on('keyup input', '#icmalSearchInput', filterOfferTable);
    $(document).on('change', '#icmalStatusFilter', filterOfferTable);

    $(document).on('change', '#icmalHeaderTemplate', function() {
        var tplId = $(this).val();
        if (!tplId) return;
        $.ajax({
            type: 'POST',
            url: 'pages/1/offer-get-template.php',
            data: { id: tplId },
            dataType: 'json',
            success: function(res) {
                if (res && res.status === 'success' && res.content) {
                    setEditorContent('#icmalHeaderContentWrapper', res.content);
                }
            }
        });
    });

    $(document).on('change', '#icmalFooterTemplate', function() {
        var tplId = $(this).val();
        if (!tplId) return;
        $.ajax({
            type: 'POST',
            url: 'pages/1/offer-get-template.php',
            data: { id: tplId },
            dataType: 'json',
            success: function(res) {
                if (res && res.status === 'success' && res.content) {
                    setEditorContent('#icmalFooterContentWrapper', res.content);
                }
            }
        });
    });

    $(document).on('click', '#btnOpenOfficialIcmal', function() {
        var $checkedRows = $('.offer-row:visible .offer-select-cb:checked');

        if ($checkedRows.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Teklif Seçilmedi',
                text: 'Lütfen icmal dökümüne dahil etmek için tablodan en az bir teklif seçiniz.',
                confirmButtonText: 'Tamam'
            });
            return;
        }

        var headerHtml = getEditorContent('#icmalHeaderContentWrapper');
        var footerHtml = getEditorContent('#icmalFooterContentWrapper');
        if (headerHtml) $('#docHeaderContentDisplay').html(headerHtml);
        if (footerHtml) $('#docFooterContentDisplay').html(footerHtml);

        var $tbody = $('#docItemsTableBody');
        $tbody.empty();

        var totalGrandAmount = 0;
        var totalKdvAmount = 0;
        var totalSubAmount = 0;
        var index = 1;

        $checkedRows.each(function() {
            var $cb = $(this);
            var offerNo = $cb.data('offer-number') || '';
            var subject = $cb.data('subject') || '';
            var amount = parseFloat($cb.data('amount')) || 0;
            var kdvRate = parseFloat($cb.data('kdv-rate')) || 20;

            var itemSub = amount / (1 + (kdvRate / 100));
            var itemKdv = amount - itemSub;

            totalGrandAmount += amount;
            totalSubAmount += itemSub;
            totalKdvAmount += itemKdv;

            var rowHtml = '<tr class="doc-item-row">' +
                '<td style="text-align: center;">' + index + '</td>' +
                '<td><strong>' + offerNo + '</strong> - ' + subject + '</td>' +
                '<td style="text-align: right;">1 Adet</td>' +
                '<td style="text-align: right;">' + itemSub.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺</td>' +
                '<td style="text-align: right; font-weight: bold;">' + itemSub.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺</td>' +
                '</tr>';

            $tbody.append(rowHtml);
            index++;
        });

        $('#docSubTotalVal').text(totalSubAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺');
        $('#docKdvVal').text(totalKdvAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺');
        $('#docKdvDahilVal').text(totalGrandAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺');
        $('#docGrandTotalVal').text(totalGrandAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺');

        $('#modalOfficialIcmalQuote').modal('show');
    });

    // ==========================================
    // 5. RAPOR İCMALİ ETKİLEŞİMLERİ (DELEGATED)
    // ==========================================
    $(document).on('click', '#toggleReportIcmalKpi', function() {
        var currentCollapsed = $('#reportIcmalKpiContainer').is(':hidden');
        var newCollapsed = !currentCollapsed;
        applyReportKpiState(newCollapsed, true);
        localStorage.setItem('aydinogullari_kpi_customer_reports_collapsed', newCollapsed);
    });

    $(document).on('click', '.table-report-icmal .report-row', function(e) {
        if ($(e.target).closest('a, button, input[type="checkbox"]').length) {
            return;
        }
        var $row = $(this);
        var reportId = $row.data('report-id');
        var $detailRow = $('#report-details-' + reportId);

        if ($detailRow.is(':visible')) {
            $detailRow.slideUp(150, function() { $detailRow.hide(); });
            $row.removeClass('is-open');
        } else {
            $detailRow.show().find('.details-subtable-wrapper').hide().slideDown(150);
            $row.addClass('is-open');
        }
    });

    var allReportsOpen = false;
    $(document).on('click', '#btnToggleAllReportRows', function() {
        allReportsOpen = !allReportsOpen;
        if (allReportsOpen) {
            $('.report-row:visible').addClass('is-open');
            $('.report-details-row').each(function() {
                var reportId = $(this).attr('id').replace('report-details-', '');
                if ($('.report-row[data-report-id="' + reportId + '"]').is(':visible')) {
                    $(this).show().find('.details-subtable-wrapper').show();
                }
            });
            $(this).html('<i class="fa fa-compress mr-1"></i> Tümünü Kapat');
        } else {
            $('.report-row').removeClass('is-open');
            $('.report-details-row').hide();
            $(this).html('<i class="fa fa-expand mr-1"></i> Tümünü Aç');
        }
    });

    $(document).on('change', '#selectAllReports', function() {
        var isChecked = $(this).is(':checked');
        $('.report-row:visible .report-select-cb').prop('checked', isChecked);
        recalculateReportIcmalTotals();
    });

    $(document).on('change', '.table-report-icmal .report-select-cb', function() {
        var totalVisibleCb = $('.report-row:visible .report-select-cb').length;
        var checkedVisibleCb = $('.report-row:visible .report-select-cb:checked').length;
        $('#selectAllReports').prop('checked', totalVisibleCb > 0 && totalVisibleCb === checkedVisibleCb);
        recalculateReportIcmalTotals();
    });

    $(document).on('keyup input', '#icmalReportSearchInput', filterReportTable);
    $(document).on('change', '#icmalReportTypeFilter', filterReportTable);

    $(document).on('click', '#btnOpenOfficialReportIcmal', function() {
        var $checkedRows = $('.report-row:visible .report-select-cb:checked');

        if ($checkedRows.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Rapor Seçilmedi',
                text: 'Lütfen icmal dökümüne dahil etmek için tablodan en az bir rapor seçiniz.',
                confirmButtonText: 'Tamam'
            });
            return;
        }

        var notesContent = $('#reportIcmalNotesContent').val() || '';
        if (notesContent.trim() !== '') {
            $('#docReportNotesText').text(notesContent);
            $('#docReportNotesDisplay').show();
        } else {
            $('#docReportNotesDisplay').hide();
        }

        var $summaryBody = $('#docReportSummaryTableBody');
        var $itemsBody = $('#docReportItemsTableBody');
        $summaryBody.empty();
        $itemsBody.empty();

        var reportSeq = 1;
        var itemSeq = 1;

        $checkedRows.each(function() {
            var $row = $(this).closest('.report-row');
            var reportId = $row.data('report-id');
            var reportNo = $row.find('td:nth-child(3)').text().trim();
            var reportType = $row.find('td:nth-child(4)').text().trim();
            var workOrder = $row.find('td:nth-child(5)').text().trim();
            var reportDate = $row.find('td:nth-child(6)').text().trim();
            var itemsCount = parseInt($row.data('items')) || 0;

            var summaryRowHtml = '<tr class="doc-item-row">' +
                '<td style="text-align: center;">' + reportSeq + '</td>' +
                '<td style="font-weight: bold;">' + reportNo + '</td>' +
                '<td>' + reportType + '</td>' +
                '<td style="text-align: center;">' + (workOrder !== '-' ? workOrder : '-') + '</td>' +
                '<td style="text-align: center;">' + reportDate + '</td>' +
                '<td style="text-align: center; font-weight: bold;">' + itemsCount + ' Adet</td>' +
                '</tr>';
            $summaryBody.append(summaryRowHtml);
            reportSeq++;

            var $detailSubtable = $('#report-details-' + reportId).find('.subtable-items tbody tr');
            if ($detailSubtable.length > 0) {
                $detailSubtable.each(function() {
                    var $subTr = $(this);
                    var $tds = $subTr.find('td');
                    if ($tds.length >= 4) {
                        var colCihaz = $tds.eq(1).text().trim() || '-';
                        var colBolge = $tds.eq(2).text().trim() || '-';
                        var colCinsi = $tds.eq(3).text().trim() || '-';
                        var itemRowHtml = '<tr class="doc-item-row" style="border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">' +
                            '<td style="text-align: center;">' + itemSeq + '</td>' +
                            '<td style="font-weight: bold;">' + reportNo + '</td>' +
                            '<td style="text-align: center;">' + colCihaz + '</td>' +
                            '<td>' + colCinsi + '</td>' +
                            '<td>' + colBolge + '</td>' +
                            '<td style="text-align: center; font-weight: bold;">Uygun</td>' +
                            '</tr>';
                        $itemsBody.append(itemRowHtml);
                        itemSeq++;
                    }
                });
            }
        });

        if (itemSeq === 1) {
            $itemsBody.append('<tr><td colspan="6" style="text-align:center; padding:10px; color:#64748b;">Seçilen raporlarda kayıtlı alt ekipman bulunmamaktadır.</td></tr>');
        }

        $('#modalOfficialReportIcmal').modal('show');
    });
});

// Seçili Raporların ID Listesini Al
function getSelectedReportIds() {
    var ids = [];
    $('.report-row:visible .report-select-cb:checked').each(function() {
        var reportId = $(this).closest('.report-row').data('report-id');
        if (reportId) ids.push(reportId);
    });
    return ids;
}

// Seçili Raporları ve Ekipmanlarını Excel'e Aktar
function exportOfficialReportIcmalExcel() {
    var selectedIds = getSelectedReportIds();
    if (selectedIds.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Rapor Seçilmedi', text: 'Excel dosyası oluşturmak için en az bir rapor seçiniz.' });
        return;
    }

    var form = $('<form>', {
        method: 'POST',
        action: 'pages/1/reports/icmal-to-xls.php',
        target: '_blank'
    });
    form.append($('<input>', { type: 'hidden', name: 'cid', value: '<?= (int)$id ?>' }));
    form.append($('<input>', { type: 'hidden', name: 'notes', value: $('#reportIcmalNotesContent').val() || '' }));
    selectedIds.forEach(function(reportId) {
        form.append($('<input>', { type: 'hidden', name: 'reports[]', value: reportId }));
    });
    form.appendTo('body').trigger('submit').remove();
}

// Rapor İcmali PDF Aç / İndir
function openOfficialReportIcmalPdf() {
    var selectedIds = getSelectedReportIds();
    var customerId = '<?= $id ?>';
    
    if (selectedIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Rapor Seçilmedi',
            text: 'Lütfen PDF oluşturmak için en az bir rapor seçiniz.'
        });
        return;
    }

    var notesText = $('#reportIcmalNotesContent').val() || '';

    var $form = $('<form>', {
        action: 'index.php?p=reports/icmal-view',
        method: 'POST',
        target: '_blank'
    });
    $form.append($('<input>', { type: 'hidden', name: 'cid', value: customerId }));
    $form.append($('<input>', { type: 'hidden', name: 'reports', value: selectedIds.join(',') }));
    $form.append($('<input>', { type: 'hidden', name: 'notes', value: notesText }));

    $('body').append($form);
    $form.submit();
    $form.remove();
}

// Resmi Rapor İcmal Formunu Doğrudan Yazdır
function printOfficialReportIcmalDirect() {
    var content = document.getElementById('officialReportIcmalDocument');
    if (!content) {
        window.print();
        return;
    }

    var printFrame = document.getElementById('printReportIcmalIframe');
    if (!printFrame) {
        printFrame = document.createElement('iframe');
        printFrame.id = 'printReportIcmalIframe';
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = '0';
        document.body.appendChild(printFrame);
    }

    var frameDoc = printFrame.contentWindow || printFrame.contentDocument.document || printFrame.contentDocument;
    var doc = frameDoc.document || frameDoc;
    doc.open();
    doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Firma Rapor İcmal Formu</title>');
    
    doc.write('<style>' +
        '@page { size: A4 portrait; margin: 30px; font-size: 8px !important; }' +
        'body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; line-height: 1.45; color: #000000; margin: 0; padding: 0; }' +
        'table { width: 100%; border-collapse: collapse; }' +
        'td { white-space: wrap; overflow: hidden; text-overflow: ellipsis; }' +
        '.official-icmal-paper { padding: 0; max-width: 100%; background: #fff; }' +
        '.doc-brand { text-align: right; }' +
        '.doc-brand strong { display: block; line-height: 1.45; margin-bottom: 2px; }' +
        '.doc-brand p { margin: 0; }' +
        '.doc-meta-table td { padding: 0 !important; height: 18px; }' +
        '.doc-header-strong { border-bottom: 2px solid #808080; border-top: 2px solid #808080; padding: 5px; font-size: 14px; display: block; margin: 8px 0; text-align: center; font-weight: bold; }' +
        '.doc-table-header { font-weight: bold; background: #bbb !important; border-bottom: 1px solid #808080; }' +
        '.doc-table-header td { border-bottom: 1px solid #808080; }' +
        '.doc-item-row { border-top: 1px solid #808080; border-bottom: 1px solid #808080; }' +
        '.doc-item-row td { border-top: 1px solid #808080; border-bottom: 1px solid #808080; font-size: 9px; height: 26px; line-height: 1.4; padding-top: 2px; padding-bottom: 2px; vertical-align: middle; }' +
        '.text-right { text-align: right; }' +
        '.text-center { text-align: center; }' +
        '</style>');
    
    doc.write('</head><body>');
    doc.write(content.outerHTML);
    doc.write('</body></html>');
    doc.close();

    setTimeout(function() {
        printFrame.contentWindow.focus();
        printFrame.contentWindow.print();
    }, 250);
}

// Seçili Tekliflerin ID listesini al
function getSelectedOfferIds() {
    var ids = [];
    $('.offer-row:visible .offer-select-cb:checked').each(function() {
        var offerId = $(this).closest('.offer-row').data('offer-id');
        if (offerId) ids.push(offerId);
    });
    return ids;
}

function exportOfficialIcmalExcel() {
    var selectedIds = getSelectedOfferIds();
    if (selectedIds.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Teklif Seçilmedi', text: 'Excel dosyası için en az bir teklif seçiniz.' });
        return;
    }

    var form = $('<form>', {
        method: 'POST',
        action: 'pages/1/offers/icmal-to-xls.php',
        target: '_blank'
    });
    form.append($('<input>', { type: 'hidden', name: 'cid', value: '<?= (int)$id ?>' }));
    form.append($('<input>', { type: 'hidden', name: 'header_content', value: getEditorContent('#icmalHeaderContentWrapper') }));
    form.append($('<input>', { type: 'hidden', name: 'footer_content', value: getEditorContent('#icmalFooterContentWrapper') }));
    selectedIds.forEach(function(offerId) {
        form.append($('<input>', { type: 'hidden', name: 'offers[]', value: offerId }));
    });
    form.appendTo('body').trigger('submit').remove();
}

// PDF Olarak Aç / İndir (Dompdf ile doğrudan resmi format ve özel üst/alt bilgi aktarımı)
function openOfficialIcmalPdf() {
    var selectedIds = getSelectedOfferIds();
    var customerId = '<?= $id ?>';
    
    if (selectedIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Teklif Seçilmedi',
            text: 'Lütfen PDF oluşturmak için en az bir teklif seçiniz.'
        });
        return;
    }

    var headerHtml = '';
    var footerHtml = '';
    var $headerWrapper = $('#icmalHeaderContentWrapper');
    var $footerWrapper = $('#icmalFooterContentWrapper');

    var headerEditor = $headerWrapper.find('textarea').data('wysihtml5');
    if (headerEditor && headerEditor.editor) {
        headerHtml = headerEditor.editor.getValue();
    } else {
        headerHtml = $headerWrapper.find('.wysihtml5-sandbox').contents().find('body').html() || $headerWrapper.find('textarea').val() || '';
    }

    var footerEditor = $footerWrapper.find('textarea').data('wysihtml5');
    if (footerEditor && footerEditor.editor) {
        footerHtml = footerEditor.editor.getValue();
    } else {
        footerHtml = $footerWrapper.find('.wysihtml5-sandbox').contents().find('body').html() || $footerWrapper.find('textarea').val() || '';
    }

    var $form = $('<form>', {
        action: 'index.php?p=offers/icmal-view',
        method: 'POST',
        target: '_blank'
    });
    $form.append($('<input>', { type: 'hidden', name: 'cid', value: customerId }));
    $form.append($('<input>', { type: 'hidden', name: 'offers', value: selectedIds.join(',') }));
    $form.append($('<input>', { type: 'hidden', name: 'header_content', value: headerHtml }));
    $form.append($('<input>', { type: 'hidden', name: 'footer_content', value: footerHtml }));

    $('body').append($form);
    $form.submit();
    $form.remove();
}

// Resmi İcmal Formunu Doğrudan Yazdır (iframe izolasyonu ile standart teklif formatı)
function printOfficialIcmalDirect() {
    var content = document.getElementById('officialIcmalDocument');
    if (!content) {
        window.print();
        return;
    }

    var printFrame = document.getElementById('printIcmalIframe');
    if (!printFrame) {
        printFrame = document.createElement('iframe');
        printFrame.id = 'printIcmalIframe';
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = '0';
        document.body.appendChild(printFrame);
    }

    var frameDoc = printFrame.contentWindow || printFrame.contentDocument.document || printFrame.contentDocument;
    var doc = frameDoc.document || frameDoc;
    doc.open();
    doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fiyat Teklif Formu</title>');
    
    doc.write('<style>' +
        '@page { size: A4 portrait; margin: 40px; font-size: 8px !important; }' +
        'body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; line-height: 1.55; color: #000000; margin: 0; padding: 0; }' +
        'table { width: 100%; border-collapse: collapse; max-width: 790px; }' +
        'td { white-space: wrap; overflow: hidden; text-overflow: ellipsis; }' +
        '.official-icmal-paper { padding: 0; max-width: 100%; background: #fff; }' +
        '.doc-main-table { width: 100%; border-collapse: collapse; }' +
        '.doc-brand { text-align: right; }' +
        '.doc-brand strong { display: block; line-height: 1.55; margin-bottom: 4px; }' +
        '.doc-brand p { margin: 0; }' +
        '.doc-meta-table td { padding: 0 !important; height: 19px; }' +
        '.doc-header-strong { border-bottom: 2px solid #808080; border-top: 2px solid #808080; padding: 5px; font-size: 16px; display: block; margin: 10px 0; text-align: center; font-weight: bold; }' +
        '.doc-table-header { font-weight: bold; background: #bbb !important; border-bottom: 1px solid #808080; }' +
        '.doc-table-header td { border-bottom: 1px solid #808080; }' +
        '.doc-item-row { border-top: 1px solid #808080; border-bottom: 1px solid #808080; }' +
        '.doc-item-row td { border-top: 1px solid #808080; border-bottom: 1px solid #808080; font-size: 10px; height: 30px; line-height: 1.55; padding-top: 3px; padding-bottom: 3px; vertical-align: middle; }' +
        '.doc-alt-toplam-table { width: 100%; border-collapse: collapse; }' +
        '.doc-alt-toplam-table tr { border-bottom: 1px solid #808080; }' +
        '.doc-alt-toplam-table td { height: 22px; vertical-align: middle; }' +
        '.doc-border-bottom-1 { border-bottom: 1px solid #808080; }' +
        '.doc-border-none { border: none !important; }' +
        '.text-right { text-align: right; }' +
        '.text-center { text-align: center; }' +
        '</style>');
    
    doc.write('</head><body>');
    doc.write(content.outerHTML);
    doc.write('</body></html>');
    doc.close();

    setTimeout(function() {
        printFrame.contentWindow.focus();
        printFrame.contentWindow.print();
    }, 250);
}
</script>
