<?php

use App\Helper\Security;
use App\Helper\Date;
use App\Model\KesifModel;

$KesifModel = new KesifModel();
$kesifler = $KesifModel->getAllActive();
$stats = $KesifModel->getSummaryStats();

$toplam_kesif = (int) ($stats['total_count'] ?? 0);
$bekleyen_kesif = (int) ($stats['bekleyen_count'] ?? 0);
$iptal_kesif = (int) ($stats['iptal_count'] ?? 0);
$teklif_kesif = (int) ($stats['teklif_count'] ?? 0);
$tamamlanan_kesif = (int) ($stats['tamamlanan_count'] ?? 0);
$bu_ay_kesif = (int) ($stats['this_month_count'] ?? 0);

try {
    $logger = \getLogger("Keşif");
    $logger->info("Keşif listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_kesif_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-kesif-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* ==========================================
       PREMIUM KEŞİF LIST THEME & RESPONSIVE LAYOUT
       ========================================== */
    .kpi-kesif-collapsed-early #kpiSummarySection {
        display: none !important;
    }
    #kpiSummarySection.is-collapsed {
        display: none !important;
    }

    .kesif-list-wrapper {
        width: 100%;
    }

    /* Page Header Styles */
    .page-title-box {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .page-title-icon {
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.22);
    }
    .page-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .page-breadcrumb {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        color: #64748b;
    }
    .page-breadcrumb a {
        color: #64748b;
        text-decoration: none;
        transition: color 0.15s;
    }
    .page-breadcrumb a:hover {
        color: #0284c7;
    }
    .page-breadcrumb .divider {
        color: #cbd5e1;
    }
    .page-breadcrumb .current {
        color: #0284c7;
        font-weight: 600;
    }

    /* Header Action Group */
    .header-action-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-action-primary {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff !important;
        border: none;
        padding: 7px 14px;
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 5px rgba(2, 132, 199, 0.2);
        transition: all 0.2s;
    }
    .btn-action-primary:hover {
        background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(2, 132, 199, 0.3);
    }
    .btn-action-outline {
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 500;
        padding: 6px 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    /* KPI Summary Cards */
    .crm-kpi-card {
        background: #ffffff;
        border-radius: 10px;
        padding: 14px 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .crm-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(0,0,0,0.05);
    }
    .crm-kpi-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
    }
    .crm-kpi-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 2px;
        display: block;
    }
    .crm-kpi-value {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .crm-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .crm-kpi-icon.icon-primary { background: #e0f2fe; color: #0284c7; }
    .crm-kpi-icon.icon-amber { background: #fef3c7; color: #d97706; }
    .crm-kpi-icon.icon-purple { background: #f3e8ff; color: #9333ea; }
    .crm-kpi-icon.icon-emerald { background: #d1fae5; color: #059669; }

    .crm-kpi-footer {
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px dashed #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .crm-badge-soft {
        font-size: 10.5px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 4px;
    }
    .crm-badge-soft.soft-primary { background: #e0f2fe; color: #0369a1; }
    .crm-badge-soft.soft-amber { background: #fef3c7; color: #b45309; }
    .crm-badge-soft.soft-purple { background: #f3e8ff; color: #7e22ce; }
    .crm-badge-soft.soft-emerald { background: #d1fae5; color: #047857; }

    /* Form Card & Header */
    .form-card {
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .form-card-header {
        padding: 14px 18px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .header-left-inner {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-icon {
        width: 34px;
        height: 34px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0284c7;
        font-size: 15px;
    }
    .form-card-header h5 {
        font-size: 14.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .form-card-header p {
        font-size: 12px;
        color: #64748b;
        margin: 0;
    }

    /* Table Styles (Fit to Container & Normal Font Size) */
    .table-responsive {
        width: 100% !important;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #kesifTable {
        width: 100% !important;
        table-layout: fixed !important;
        margin-bottom: 0;
    }
    #kesifTable th,
    #kesifTable td {
        min-width: 0 !important;
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }
    #kesifTable thead th {
        background: #f8fafc !important;
        color: #475569 !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        border-bottom: 2px solid #e2e8f0 !important;
        padding: 10px 5px !important;
        vertical-align: middle !important;
        line-height: 1.25;
    }
    #kesifTable tbody td {
        font-size: 12.5px !important;
        color: #334155;
        padding: 8px 5px !important;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9 !important;
        line-height: 1.35;
    }
    #kesifTable tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Table Filter Inputs (Gizlendi - Zaten kolon filtreleri ve üst arama kutusu mevcut) */
    #kesifTable thead tr.search-input-row,
    #kesifTable thead tr:not(:first-child),
    #kesifTable thead input {
        display: none !important;
    }

    /* Table Tooltip Overlap Fix (Tablo içinde ve kart sınırlarında kesilmeyi önler) */
    #kesifTable [data-tooltip]::before,
    #kesifTable [data-tooltip]::after,
    #kesifTable [data-tooltip]:before,
    #kesifTable [data-tooltip]:after {
        display: none !important;
        content: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
    }

    /* Bootstrap Tooltip Şık Görünüm */
    .tooltip {
        z-index: 10600 !important;
        pointer-events: none;
    }
    .tooltip .tooltip-inner {
        max-width: 380px !important;
        padding: 8px 12px !important;
        font-size: 12px !important;
        line-height: 1.45 !important;
        background-color: #1e293b !important;
        color: #f8fafc !important;
        border-radius: 6px !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25) !important;
        text-align: left !important;
        white-space: pre-wrap !important;
        word-break: break-word !important;
    }
    .tooltip.bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow::before,
    .tooltip.bs-tooltip-top .tooltip-arrow::before {
        border-top-color: #1e293b !important;
    }
    .tooltip.bs-tooltip-auto[data-popper-placement^=bottom] .tooltip-arrow::before,
    .tooltip.bs-tooltip-bottom .tooltip-arrow::before {
        border-bottom-color: #1e293b !important;
    }
    .tooltip.bs-tooltip-auto[data-popper-placement^=left] .tooltip-arrow::before,
    .tooltip.bs-tooltip-left .tooltip-arrow::before {
        border-left-color: #1e293b !important;
    }
    .tooltip.bs-tooltip-auto[data-popper-placement^=right] .tooltip-arrow::before,
    .tooltip.bs-tooltip-right .tooltip-arrow::before {
        border-right-color: #1e293b !important;
    }

    /* Cell Elements */
    .row-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        background: #f1f5f9;
        color: #64748b;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .col-date {
        display: flex;
        flex-direction: column;
        align-items: center;
        line-height: 1.2;
    }
    .col-date .date-dmy {
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap !important;
    }
    .col-date .date-his {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap !important;
    }

    .col-company {
        font-size: 12.5px;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.3;
        word-break: break-word;
    }

    .col-job {
        font-size: 12px;
        color: #334155;
        line-height: 1.3;
        word-break: break-word;
        transition: color 0.15s;
    }
    .col-job.clickable-job {
        cursor: pointer;
    }
    .col-job.clickable-job:hover {
        color: #0284c7;
        text-decoration: underline;
    }

    .col-konum {
        font-size: 11.5px;
        color: #0284c7;
        line-height: 1.3;
        word-break: break-word;
        text-decoration: none;
    }
    .col-konum:hover {
        color: #0369a1;
        text-decoration: underline;
    }

    .col-note {
        font-size: 11.5px;
        color: #475569;
        line-height: 1.3;
        word-break: break-word;
    }

    /* Soft Status Badges */
    .badge-soft-warning {
        background-color: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
        font-weight: 600;
        font-size: 11px;
        padding: 3px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap !important;
    }
    .badge-soft-danger {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
        font-weight: 600;
        font-size: 11px;
        padding: 3px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap !important;
    }
    .badge-soft-info {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        font-weight: 600;
        font-size: 11px;
        padding: 3px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap !important;
    }
    .badge-soft-purple {
        background-color: #f3e8ff;
        color: #7e22ce;
        border: 1px solid #e9d5ff;
        font-weight: 600;
        font-size: 11px;
        padding: 3px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap !important;
    }
    .badge-soft-success {
        background-color: #d1fae5;
        color: #047857;
        border: 1px solid #a7f3d0;
        font-weight: 600;
        font-size: 11px;
        padding: 3px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap !important;
    }
    .badge-person {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        font-size: 11.5px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        max-width: 100%;
        word-break: break-word;
        white-space: normal;
        line-height: 1.25;
        text-align: left;
    }
    .badge-form-kimde {
        background: #ede9fe;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
        font-size: 11.5px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        max-width: 100%;
        word-break: break-word;
        white-space: normal;
        line-height: 1.25;
        text-align: left;
    }

    /* Thumbnail and gallery styles */
    .gorsel-thumb-group {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 2px;
    }
    .gorsel-thumb {
        width: 26px;
        height: 26px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        transition: transform 0.15s;
    }
    .gorsel-thumb:hover {
        transform: scale(1.15);
    }
    .gorsel-more-badge {
        font-size: 9.5px;
        font-weight: 700;
        background: #e2e8f0;
        color: #475569;
        border-radius: 4px;
        padding: 1px 4px;
    }

    /* Action Buttons in Row */
    .action-btn-group {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 3px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }
    .action-btn {
        width: 26px !important;
        height: 26px !important;
        min-width: 26px !important;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-size: 11px;
        transition: all 0.15s;
    }
    .action-btn.dropdown-toggle-split::after {
        display: none !important;
    }
    .action-btn:hover {
        transform: translateY(-1px);
    }
    #kesifTable th:last-child,
    #kesifTable td:last-child {
        width: 100px !important;
        min-width: 100px !important;
        white-space: nowrap !important;
        padding-left: 4px !important;
        padding-right: 4px !important;
    }

    /* Context Menu */
    .custom-context-menu {
        position: fixed;
        z-index: 9999;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        padding: 5px 0;
        min-width: 190px;
        display: none;
    }
    .custom-context-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 14px;
        font-size: 12px;
        color: #334155;
        text-decoration: none;
        transition: background 0.15s;
    }
    .custom-context-menu a:hover {
        background: #f1f5f9;
        color: #0284c7;
    }
    .custom-context-menu a.cm-danger:hover {
        background: #fef2f2;
        color: #dc2626;
    }
    .custom-context-menu .cm-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 4px 0;
    }
    .custom-context-menu .cm-header {
        padding: 3px 14px 5px;
        font-size: 10.5px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .context-menu-active {
        background-color: #e0f2fe !important;
    }

    /* ==========================================
       PREMIUM MODAL STYLES (Keşif Form & Detay)
       ========================================== */
    #kesifModal .modal-dialog,
    #detaylarModal .modal-dialog {
        max-width: 1040px !important;
        width: 95% !important;
        margin: 1.75rem auto;
    }
    @media (max-width: 991.98px) {
        #kesifModal .modal-dialog,
        #detaylarModal .modal-dialog {
            max-width: 96% !important;
            margin: 0.75rem auto;
        }
    }
    #kesifModal .modal-content,
    #detaylarModal .modal-content,
    #mapModal .modal-content {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.25);
        overflow: hidden;
        background: #ffffff;
    }
    .modal-header-premium {
        padding: 16px 20px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .modal-header-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
        flex-shrink: 0;
    }
    .modal-header-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.25;
    }
    .modal-header-sub {
        font-size: 12px;
        color: #64748b;
        margin: 2px 0 0 0;
    }
    .modal-close-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f1f5f9;
        border: none;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s;
        padding: 0;
    }
    .modal-close-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .modal-body-premium {
        padding: 18px 20px;
        background: #f8fafc;
    }
    .modal-footer-premium {
        padding: 12px 20px;
        background: #ffffff;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Form Section Cards */
    .form-section-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
        height: 100%;
        display: flex;
        flex-direction: column;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .form-section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        padding-bottom: 10px;
        margin-bottom: 12px;
        border-bottom: 1px dashed #e2e8f0;
    }
    .form-section-header .sec-icon {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    .sec-icon.blue { background: #e0f2fe; color: #0284c7; }
    .sec-icon.purple { background: #f3e8ff; color: #9333ea; }

    /* Inputs and Labels */
    .field-label {
        font-size: 11.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 4px;
        display: block;
    }
    .field-label .req {
        color: #e11d48;
    }
    .kesif-input-group {
        position: relative;
    }
    .kesif-input-group .input-group-text {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #64748b;
        font-size: 13px;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
        padding: 6px 10px;
        min-width: 38px;
        justify-content: center;
    }
    .kesif-input-group .form-control {
        border-color: #cbd5e1;
        font-size: 12.5px;
        border-radius: 8px;
        height: 38px;
        color: #1e293b;
    }
    .kesif-input-group .input-group-prepend + .form-control {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .kesif-input-group .form-control:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }
    .kesif-input-group .form-control::placeholder {
        color: #94a3b8;
        font-size: 12px;
    }
    textarea.form-control {
        border-color: #cbd5e1;
        border-radius: 8px;
        font-size: 12.5px;
        color: #1e293b;
    }
    textarea.form-control:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }

    /* Dropzone Upload Area */
    .dropzone-upload-box {
        border: 2px dashed #93c5fd;
        background: #f0f9ff;
        border-radius: 8px;
        padding: 14px 10px;
        text-align: center;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
    }
    .dropzone-upload-box:hover,
    .dropzone-upload-box.dragover {
        border-color: #0284c7;
        background: #e0f2fe;
    }
    .dropzone-icon {
        font-size: 24px;
        color: #0284c7;
        margin-bottom: 2px;
    }
    .dropzone-title {
        font-size: 12px;
        font-weight: 600;
        color: #0f172a;
    }
    .dropzone-subtitle {
        font-size: 10.5px;
        color: #64748b;
    }
    .dropzone-input-hidden {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    /* Selected files and current images */
    .selected-files-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }
    .selected-file-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 8px;
        background: #ffffff;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 500;
        color: #1e3a8a;
    }
    .selected-file-chip .chip-name {
        max-width: 130px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .selected-file-chip .chip-size {
        font-size: 10px;
        color: #64748b;
    }
    .current-gorseller-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }
    .gorsel-item-card {
        position: relative;
        width: 70px;
        height: 70px;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .gorsel-item-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        cursor: pointer;
    }
    .gorsel-item-card .doc-preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        text-decoration: none;
    }
    .btn-delete-gorsel {
        position: absolute;
        top: 2px;
        right: 2px;
        background: rgba(220, 38, 38, 0.9);
        color: #fff;
        border: 1px solid #fff;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .btn-delete-gorsel:hover {
        background: #b91c1c;
    }

    /* Detail Modal Styles */
    .detail-hero-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    .detail-hero-company {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }
    .detail-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }
    .detail-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    .detail-stat-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }
    .detail-stat-icon.icon-blue { background: #e0f2fe; color: #0284c7; }
    .detail-stat-icon.icon-purple { background: #f3e8ff; color: #9333ea; }
    .detail-stat-icon.icon-amber { background: #fef3c7; color: #d97706; }
    .detail-stat-icon.icon-emerald { background: #d1fae5; color: #059669; }
    .detail-stat-content {
        flex: 1;
        min-width: 0;
    }
    .detail-stat-label {
        font-size: 10.5px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 2px;
    }
    .detail-stat-value {
        font-size: 12.5px;
        font-weight: 600;
        color: #0f172a;
    }
    .detail-text-block {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 12px;
    }
    .detail-text-block-title {
        font-size: 11.5px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .detail-text-block-content {
        font-size: 12.5px;
        color: #1e293b;
        line-height: 1.5;
        white-space: pre-wrap;
    }
</style>

<div class="kesif-list-wrapper">

    <!-- Başlık ve Üst Butonlar -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div class="page-title-box">
            <div class="page-title-icon">
                <i class="fa fa-compass"></i>
            </div>
            <div>
                <h4 class="page-title mb-1">Keşif Listesi</h4>
                <div class="page-breadcrumb">
                    <a href="/index.php"><i class="fa fa-home"></i> Ana Sayfa</a>
                    <span class="divider">/</span>
                    <span>Saha & Keşif</span>
                    <span class="divider">/</span>
                    <span class="current">Keşif Listesi</span>
                </div>
            </div>
        </div>
        <div class="header-action-group">
            <?php if (permtrue('kesif_dashboard') || permtrue('kesifView')) { ?>
                <a href="index.php?p=kesif/dashboard" class="btn btn-outline-primary btn-action-outline" title="Keşif Dashboard">
                    <i class="fa fa-dashboard"></i> <span class="d-none d-sm-inline">Dashboard</span>
                </a>
            <?php } ?>
            <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshKesif" title="Tabloyu Yenile">
                <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
            </button>
            <?php if (permtrue('kesifExport')) { ?>
                <a href="/pages/1/kesif/export.php" target="_blank" class="btn btn-outline-success btn-action-outline" id="btnExportKesif" title="Excel Olarak İndir">
                    <i class="fa fa-file-excel-o"></i> <span class="d-none d-sm-inline">Excel'e Aktar</span>
                </a>
            <?php } ?>
            <?php if (permtrue('kesifCreate')) { ?>
                <button type="button" class="btn btn-action-primary" data-toggle="modal" data-target="#kesifModal" id="btnNewKesif">
                    <i class="fa fa-plus-circle"></i> <span>Yeni Keşif Ekle</span>
                </button>
            <?php } ?>
        </div>
    </div>

    <!-- KPI Özet / İstatistik Kartları -->
    <div id="kpiSummarySection" class="row mx-0 mb-3 kpi-summary-collapse">
        <!-- Toplam Keşif Sayısı -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Toplam Keşif</span>
                        <div class="crm-kpi-value"><?php echo number_format($toplam_kesif, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-primary">
                        <i class="fa fa-list-alt"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Bu Ay: <strong><?php echo $bu_ay_kesif; ?></strong> adet</span>
                    <span class="crm-badge-soft soft-primary">Aktif Liste</span>
                </div>
            </div>
        </div>

        <!-- Bekleyen Keşif -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Bekleyen Keşif</span>
                        <div class="crm-kpi-value"><?php echo number_format($bekleyen_kesif, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-amber">
                        <i class="fa fa-hourglass-half"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Saha Ziyareti Bekleniyor</span>
                    <span class="crm-badge-soft soft-amber">Beklemede</span>
                </div>
            </div>
        </div>

        <!-- Teklif Sürecinde -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Teklif Sürecinde</span>
                        <div class="crm-kpi-value"><?php echo number_format($teklif_kesif, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-purple">
                        <i class="fa fa-paper-plane"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Teklif Hazırlandı / Gönderildi</span>
                    <span class="crm-badge-soft soft-purple">Fiyatlandırma</span>
                </div>
            </div>
        </div>

        <!-- Tamamlanan Keşif -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Tamamlanan Keşif</span>
                        <div class="crm-kpi-value"><?php echo number_format($tamamlanan_kesif, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-emerald">
                        <i class="fa fa-check-circle"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11"><?php echo $iptal_kesif > 0 ? "İptal: {$iptal_kesif}" : "Başarılı Tamamlama"; ?></span>
                    <span class="crm-badge-soft soft-emerald">Tamamlandı</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="form-card animate-fade-in">
        <div class="form-card-header">
            <div class="header-left-inner">
                <div class="card-icon">
                    <i class="fa fa-map-marker"></i>
                </div>
                <div>
                    <h5>Keşif Talep ve Saha Listesi</h5>
                    <p>Saha keşifleri, görevli personel, form durumu ve görsel kayıtları</p>
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap: 8px;">
                <div class="dt-header-filter-box d-flex align-items-center"></div>
                <button type="button" id="toggleKpiSummary" class="btn btn-outline-secondary btn-sm" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fa fa-chevron-up"></i>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="kesifTable" class="data-table table table-hover">
                <thead>
                    <tr>
                        <th style="width: 35px;" class="text-center no-sort">#Sıra</th>
                        <th style="width: 85px;" class="text-center">Keşif Tarihi</th>
                        <th style="width: 135px;">Firma Adı</th>
                        <th style="width: 165px;">Yapılacak İş</th>
                        <th style="width: 110px;" class="text-center">Görevli Kişi</th>
                        <th style="width: 85px;" class="text-center">Form Kimde?</th>
                        <th style="width: 105px;" class="text-center">Konum</th>
                        <th style="width: 55px;" class="text-center no-sort">Görseller</th>
                        <th style="width: 85px;" class="text-center">Durum</th>
                        <th style="width: 105px;">Keşif Sonu Notu</th>
                        <th style="width: 85px;" class="text-center">Kayıt Tarihi</th>
                        <th style="width: 85px;" class="text-center">Kayıt Yapan</th>
                        <th style="width: 100px;" class="text-center no-sort no-export">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sira = 1;
                    if (!empty($kesifler)) {
                        foreach ($kesifler as $kesif) {
                            $enc_id = Security::encrypt($kesif->id);

                            $kesif_ts = strtotime(str_replace('.', '-', $kesif->kesif_tarihi));
                            $kesif_dmy = $kesif_ts ? date('d.m.Y', $kesif_ts) : $kesif->kesif_tarihi;
                            $kesif_his = $kesif_ts ? date('H:i:s', $kesif_ts) : '';
                            $sort_kesif_date = date('Y-m-d H:i:s', $kesif_ts ?: time());

                            $kayit_ts = strtotime(str_replace('.', '-', $kesif->kayit_tarihi));
                            $kayit_dmy = $kayit_ts ? date('d.m.Y', $kayit_ts) : $kesif->kayit_tarihi;
                            $kayit_his = $kayit_ts ? date('H:i:s', $kayit_ts) : '';
                            $sort_kayit_date = date('Y-m-d H:i:s', $kayit_ts ?: time());

                            $raw_yapilacak_is = $kesif->yapilacak_is ?? '';
                            $yapilacak_is_full = htmlspecialchars($raw_yapilacak_is, ENT_QUOTES, 'UTF-8');
                            $yapilacak_is_short = (mb_strlen($raw_yapilacak_is, 'UTF-8') > 150)
                                ? htmlspecialchars(mb_substr($raw_yapilacak_is, 0, 150, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '...'
                                : $yapilacak_is_full;

                            $formatted_gidecek_kisi = htmlspecialchars($kesif->gidecek_kisi ?? '', ENT_QUOTES, 'UTF-8');
                            $raw_not = $kesif->kesif_sonu_notu ?? '';
                            $kesif_sonu_notu = htmlspecialchars($raw_not, ENT_QUOTES, 'UTF-8');
                            $raw_not_short = (mb_strlen($raw_not, 'UTF-8') > 150)
                                ? htmlspecialchars(mb_substr($raw_not, 0, 150, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '...'
                                : $kesif_sonu_notu;

                            $durum = $kesif->durum ?? 'bekliyor';
                            ?>
                            <tr data-id="<?php echo $kesif->id; ?>" data-enc-id="<?php echo $enc_id; ?>" data-firma="<?php echo htmlspecialchars($kesif->firma, ENT_QUOTES, 'UTF-8'); ?>" data-konum="<?php echo htmlspecialchars($kesif->konum, ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="text-center">
                                    <span class="row-index-badge"><?php echo $sira; ?></span>
                                </td>
                                <td class="text-center" data-sort="<?php echo $sort_kesif_date; ?>">
                                    <div class="col-date">
                                        <span class="date-dmy"><?php echo $kesif_dmy; ?></span>
                                        <span class="date-his"><?php echo $kesif_his; ?></span>
                                    </div>
                                </td>
                                <td data-tooltip="<?php echo htmlspecialchars($kesif->firma, ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="col-company">
                                        <i class="fa fa-building-o text-muted mr-1"></i><?php echo htmlspecialchars($kesif->firma, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>
                                <td data-export="<?php echo $yapilacak_is_full; ?>" data-tooltip="<?php echo $yapilacak_is_full; ?>">
                                    <?php if (permtrue('kesifEdit')) { ?>
                                        <div class="col-job clickable-job edit-btn" data-id="<?php echo $kesif->id; ?>" role="button" tabindex="0" title="Düzenlemek için tıklayın">
                                            <?php echo $yapilacak_is_short; ?>
                                        </div>
                                    <?php } else { ?>
                                        <div class="col-job">
                                            <?php echo $yapilacak_is_short; ?>
                                        </div>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($kesif->gidecek_kisi) && trim($kesif->gidecek_kisi) !== '.') { ?>
                                        <span class="badge-person" data-tooltip="<?php echo $formatted_gidecek_kisi; ?>">
                                            <i class="fa fa-user-circle text-primary"></i> <?php echo $formatted_gidecek_kisi; ?>
                                        </span>
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($kesif->formun_bulundugu_kisi) && trim($kesif->formun_bulundugu_kisi) !== '.') { ?>
                                        <span class="badge-form-kimde" data-tooltip="<?php echo htmlspecialchars($kesif->formun_bulundugu_kisi, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fa fa-file-text-o"></i> <?php echo htmlspecialchars($kesif->formun_bulundugu_kisi, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($kesif->konum) && trim($kesif->konum) !== '.') { ?>
                                        <a href="#" class="map-view-btn col-konum d-inline-block text-center" data-id="<?php echo $kesif->id; ?>" data-tooltip="<?php echo htmlspecialchars($kesif->konum, ENT_QUOTES, 'UTF-8'); ?> (Haritada Gör)">
                                            <i class="fa fa-map-marker text-danger mr-1"></i><?php echo htmlspecialchars($kesif->konum, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    if (!empty($kesif->gorseller)) {
                                        $gorseller = json_decode($kesif->gorseller, true);
                                        if (!empty($gorseller) && is_array($gorseller)) {
                                            $docRoot = dirname(__DIR__, 3);
                                            $validGorseller = [];
                                            foreach ($gorseller as $img) {
                                                $diskPath = $docRoot . '/' . ltrim($img, '/');
                                                if (file_exists($diskPath)) {
                                                    $validGorseller[] = $img;
                                                }
                                            }

                                            if (!empty($validGorseller)) {
                                                echo '<div class="gorsel-thumb-group">';
                                                $shownCount = 0;
                                                foreach ($validGorseller as $img) {
                                                    $imgUrl = '/' . ltrim($img, '/');
                                                    $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
                                                    $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                                                    
                                                    if ($shownCount < 2) {
                                                        if ($isImg) {
                                                            echo '<a href="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">
                                                                    <img src="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" class="gorsel-thumb" onerror="this.onerror=null;this.style.display=\'none\';" alt="Görsel">
                                                                  </a>';
                                                        } elseif ($ext === 'docx' || $ext === 'doc') {
                                                            echo '<a href="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" class="badge badge-light border p-1" title="Word"><i class="fa fa-file-word-o text-primary"></i></a>';
                                                        } elseif ($ext === 'pdf') {
                                                            echo '<a href="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" class="badge badge-light border p-1" title="PDF"><i class="fa fa-file-pdf-o text-danger"></i></a>';
                                                        } else {
                                                            echo '<a href="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" class="badge badge-light border p-1" title="Ek"><i class="fa fa-paperclip text-muted"></i></a>';
                                                        }
                                                        $shownCount++;
                                                    }
                                                }
                                                if (count($validGorseller) > 2) {
                                                    echo '<span class="gorsel-more-badge">+' . (count($validGorseller) - 2) . '</span>';
                                                }
                                                echo '</div>';
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    if ($durum == 'bekliyor') {
                                        echo '<span class="badge-soft-warning"><i class="fa fa-clock-o"></i> Bekliyor</span>';
                                    } elseif ($durum == 'iptal_edildi') {
                                        echo '<span class="badge-soft-danger"><i class="fa fa-times-circle"></i> İptal</span>';
                                    } elseif ($durum == 'kesif_tamamlandi') {
                                        echo '<span class="badge-soft-info"><i class="fa fa-check"></i> Tamamlandı</span>';
                                    } elseif ($durum == 'teklif_hazirlandi') {
                                        echo '<span class="badge-soft-purple"><i class="fa fa-pencil-square-o"></i> Hazırlandı</span>';
                                    } elseif ($durum == 'teklif_gonderildi') {
                                        echo '<span class="badge-soft-success"><i class="fa fa-paper-plane"></i> Gönderildi</span>';
                                    } else {
                                        echo '<span class="badge badge-secondary">' . htmlspecialchars($durum, ENT_QUOTES, 'UTF-8') . '</span>';
                                    }
                                    ?>
                                </td>
                                <td data-export="<?php echo htmlspecialchars($raw_not, ENT_QUOTES, 'UTF-8'); ?>" data-tooltip="<?php echo $kesif_sonu_notu; ?>">
                                    <?php if (!empty($raw_not) && trim($raw_not) !== '.') { ?>
                                        <div class="col-note">
                                            <i class="fa fa-sticky-note-o text-warning mr-1"></i><?php echo $raw_not_short; ?>
                                        </div>
                                    <?php } else { ?>
                                        <span class="text-muted text-center d-block">-</span>
                                    <?php } ?>
                                </td>
                                <td class="text-center" data-sort="<?php echo $sort_kayit_date; ?>">
                                    <div class="col-date">
                                        <span class="date-dmy"><?php echo $kayit_dmy; ?></span>
                                        <span class="date-his"><?php echo $kayit_his; ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="font-weight-500 text-dark"><?php echo htmlspecialchars($kesif->kullanici_adi ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="action-btn-group">
                                        <?php if (permtrue('kesifEdit')) { ?>
                                            <button type="button" class="btn btn-sm btn-outline-info action-btn edit-btn" data-id="<?php echo $kesif->id; ?>" data-tooltip="Düzenle">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                        <?php } ?>

                                        <?php if (permtrue('kesifDelete')) { ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger action-btn delete-btn" data-id="<?php echo $kesif->id; ?>" data-tooltip="Sil">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        <?php } ?>

                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary action-btn dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-tooltip="Diğer">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail">
                                                <a href="#" class="dropdown-item view-btn" data-id="<?php echo $kesif->id; ?>">
                                                    <i class="fa fa-eye text-primary mr-2"></i> Detayları Görüntüle
                                                </a>
                                                <a href="/pages/1/kesif/view-pdf.php?id=<?php echo $enc_id; ?>" class="dropdown-item" target="_blank" rel="noopener">
                                                    <i class="fa fa-file-pdf-o text-danger mr-2"></i> PDF Görüntüle
                                                </a>
                                                <?php if (!empty($kesif->konum)) { ?>
                                                    <a href="#" class="dropdown-item map-view-btn" data-id="<?php echo $kesif->id; ?>">
                                                        <i class="fa fa-map-marker text-success mr-2"></i> Haritada Gör
                                                    </a>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php
                            $sira++;
                        }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Sağ Tık (Context Menu) -->
<div id="kesifContextMenu" class="custom-context-menu">
    <div class="cm-header" id="cmKesifTitle">Keşif İşlemleri</div>
    <a href="#" id="cmActionView">
        <i class="fa fa-eye text-primary"></i> Detayları Görüntüle
    </a>
    <a href="#" id="cmActionPdf" target="_blank" rel="noopener">
        <i class="fa fa-file-pdf-o text-danger"></i> PDF Görüntüle / İndir
    </a>
    <a href="#" id="cmActionMap">
        <i class="fa fa-map-marker text-success"></i> Haritada Aç
    </a>
    <div class="cm-divider"></div>
    <?php if (permtrue('kesifEdit')) { ?>
        <a href="#" id="cmActionEdit">
            <i class="fa fa-pencil text-info"></i> Keşfi Düzenle
        </a>
    <?php } ?>
    <?php if (permtrue('kesifDelete')) { ?>
        <a href="#" id="cmActionDelete" class="cm-danger">
            <i class="fa fa-trash text-danger"></i> Keşfi Sil
        </a>
    <?php } ?>
</div>

<!-- Keşif Form Modal (Ekle / Düzenle) -->
<div class="modal fade" id="kesifModal" tabindex="-1" role="dialog" aria-labelledby="kesifModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <form id="kesifForm" method="POST" enctype="multipart/form-data" class="w-100">
            <div class="modal-content">
                <div class="modal-header-premium">
                    <div class="d-flex align-items-center gap-3">
                        <div class="modal-header-icon">
                            <i class="fa fa-compass"></i>
                        </div>
                        <div>
                            <h5 class="modal-header-title" id="kesifModalLabel">Yeni Keşif Ekle</h5>
                            <p class="modal-header-sub">Saha keşfi detaylarını, personel görevlendirmesini ve keşif notlarını yönetin.</p>
                        </div>
                    </div>
                    <button type="button" class="modal-close-btn" data-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body-premium">
                    <input type="hidden" id="kesif_id" name="id" value="">

                    <div class="row">
                        <!-- SOL KOLON: Keşif & Müşteri Bilgileri -->
                        <div class="col-lg-6 mb-3 mb-lg-0">
                            <div class="form-section-card">
                                <div class="form-section-header">
                                    <span class="sec-icon blue"><i class="fa fa-building"></i></span>
                                    <span>Keşif & Müşteri Bilgileri</span>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group mb-3">
                                            <label class="field-label" for="kesif_tarihi">
                                                Keşif Tarihi & Saati <span class="req">*</span>
                                            </label>
                                            <div class="input-group kesif-input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-calendar text-primary"></i></span>
                                                </div>
                                                <input type="text" id="kesif_tarihi" name="kesif_tarihi" autocomplete="off"
                                                    class="form-control datetimepicker" placeholder="GG.AA.YYYY SS:DD" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group mb-3">
                                            <label class="field-label" for="firma">
                                                Firma / Müşteri <span class="req">*</span>
                                            </label>
                                            <div class="input-group kesif-input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-briefcase text-primary"></i></span>
                                                </div>
                                                <input type="text" id="firma" name="firma" class="form-control" required
                                                    placeholder="Firma adını girin veya seçin" list="kesif_firma_listesi">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="field-label" for="konum">
                                        Konum / Saha Adresi <span class="req">*</span>
                                    </label>
                                    <div class="input-group kesif-input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-map-marker text-danger"></i></span>
                                        </div>
                                        <input type="text" id="konum" name="konum" class="form-control" required
                                            placeholder="İl, İlçe veya Açık Adres giriniz">
                                    </div>
                                </div>

                                <div class="form-group mb-0 flex-grow-1 d-flex flex-column">
                                    <label class="field-label" for="yapilacak_is">
                                        Yapılacak İş ve Keşif Kapsamı <span class="req">*</span>
                                    </label>
                                    <textarea id="yapilacak_is" name="yapilacak_is" class="form-control flex-grow-1" rows="4"
                                        required placeholder="Yapılacak keşif çalışması, cihaz kontrolleri ve keşif kapsamını açıklayınız..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- SAĞ KOLON: Görevli, Durum & Medya -->
                        <div class="col-lg-6">
                            <div class="form-section-card">
                                <div class="form-section-header">
                                    <span class="sec-icon purple"><i class="fa fa-user-circle"></i></span>
                                    <span>Görevlendirme, Durum & Notlar</span>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group mb-3">
                                            <label class="field-label" for="gidecek_kisi">
                                                Keşife Gidecek Kişi <span class="req">*</span>
                                            </label>
                                            <div class="input-group kesif-input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-user text-primary"></i></span>
                                                </div>
                                                <input type="text" id="gidecek_kisi" name="gidecek_kisi" required
                                                    class="form-control" list="kesif_personel_listesi" placeholder="Personel seçiniz / yazınız">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group mb-3">
                                            <label class="field-label" for="formun_bulundugu_kisi">
                                                Formun Bulunduğu Kişi
                                            </label>
                                            <div class="input-group kesif-input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-folder-open text-warning"></i></span>
                                                </div>
                                                <input type="text" id="formun_bulundugu_kisi" name="formun_bulundugu_kisi"
                                                    class="form-control" list="kesif_personel_listesi"
                                                    placeholder="Seçiniz veya yazınız">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="field-label" for="durum">
                                        Keşif Durumu <span class="req">*</span>
                                    </label>
                                    <div class="input-group kesif-input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-flag text-info"></i></span>
                                        </div>
                                        <select id="durum" name="durum" class="form-control" required>
                                            <option value="bekliyor">Bekliyor (Sarı)</option>
                                            <option value="kesif_tamamlandi">Keşif Tamamlandı (Mavi)</option>
                                            <option value="teklif_hazirlandi">Teklif Hazırlandı (Mor)</option>
                                            <option value="teklif_gonderildi">Teklif Gönderildi (Yeşil)</option>
                                            <option value="iptal_edildi">İptal Edildi (Kırmızı)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Sürükle Bırak Dosya Yükleme Alanı -->
                                <div class="form-group mb-3">
                                    <label class="field-label d-flex justify-content-between">
                                        <span>Görsel / Belge Ekleri</span>
                                        <small class="text-muted">JPG, PNG, WEBP, PDF, DOCX, XLSX</small>
                                    </label>
                                    <div class="dropzone-upload-box" id="kesifDropzone">
                                        <input type="file" id="kesif_gorseller" name="gorseller[]" class="dropzone-input-hidden"
                                            multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
                                        <div class="dropzone-icon">
                                            <i class="fa fa-cloud-upload"></i>
                                        </div>
                                        <div class="dropzone-title">Dosyaları buraya sürükleyin veya tıklayın</div>
                                        <div class="dropzone-subtitle">Görsel veya teknik rapor yükleyebilirsiniz</div>
                                    </div>
                                    <!-- Yeni Seçilen Dosyaların Önizleme Çipleri -->
                                    <div id="selected_files_list" class="selected-files-list"></div>
                                    <!-- Mevcut Kayıtlı Görseller -->
                                    <div id="current_gorseller" class="current-gorseller-grid"></div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="field-label" for="kesif_sonu_notu">
                                        Keşif Sonu Notu / Saha Raporu
                                    </label>
                                    <textarea id="kesif_sonu_notu" name="kesif_sonu_notu" class="form-control" rows="3"
                                        placeholder="Keşif sonrası teknik bulgular, montaj şartları ve saha notlarınızı buraya ekleyin..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-premium">
                    <div class="text-muted font-11 d-none d-sm-block">
                        <i class="fa fa-info-circle text-primary mr-1"></i> <span class="req text-danger">*</span> işaretli alanların doldurulması zorunludur.
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary px-3 py-2 font-12 font-weight-600 rounded-lg" data-dismiss="modal">
                            İptal
                        </button>
                        <button type="submit" class="btn btn-action-primary px-4 py-2 font-12" id="btnSaveKesif">
                            <i class="fa fa-check mr-1"></i> <span class="btn-text">Kaydet</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Müşteri ve Personel Datalistleri -->
<datalist id="kesif_firma_listesi">
    <?php foreach ($customersList as $custName) { ?>
        <option value="<?php echo htmlspecialchars($custName, ENT_QUOTES, 'UTF-8'); ?>">
    <?php } ?>
</datalist>

<datalist id="kesif_personel_listesi">
    <?php foreach ($personnelList as $pName) { ?>
        <option value="<?php echo htmlspecialchars($pName, ENT_QUOTES, 'UTF-8'); ?>">
    <?php } ?>
</datalist>

<!-- Keşif Detaylar Modal -->
<div class="modal fade" id="detaylarModal" tabindex="-1" role="dialog" aria-labelledby="detaylarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content w-100">
            <div class="modal-header-premium">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
                        <i class="fa fa-eye"></i>
                    </div>
                    <div>
                        <h5 class="modal-header-title" id="detaylarModalLabel">Keşif Kaydı Detayları</h5>
                        <p class="modal-header-sub">Keşif kaydının tüm teknik detayları, personelleri ve ekli belgeleri.</p>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" data-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-body-premium">
                <!-- Hero Firma & Durum Şeridi -->
                <div class="detail-hero-box">
                    <div class="detail-hero-company">
                        <i class="fa fa-building text-primary mr-2"></i>
                        <span id="detail_firma">-</span>
                    </div>
                    <div id="detail_durum"></div>
                </div>

                <!-- 4'lü Stat Grid -->
                <div class="detail-info-grid">
                    <div class="detail-stat-card">
                        <div class="detail-stat-icon icon-blue">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <div class="detail-stat-content">
                            <div class="detail-stat-label">Keşif Tarihi</div>
                            <div class="detail-stat-value" id="detail_kesif_tarihi">-</div>
                        </div>
                    </div>
                    <div class="detail-stat-card">
                        <div class="detail-stat-icon icon-purple">
                            <i class="fa fa-user-circle"></i>
                        </div>
                        <div class="detail-stat-content">
                            <div class="detail-stat-label">Görevli Personel</div>
                            <div class="detail-stat-value" id="detail_gidecek_kisi">-</div>
                        </div>
                    </div>
                    <div class="detail-stat-card">
                        <div class="detail-stat-icon icon-amber">
                            <i class="fa fa-folder-open"></i>
                        </div>
                        <div class="detail-stat-content">
                            <div class="detail-stat-label">Formun Bulunduğu Kişi</div>
                            <div class="detail-stat-value" id="detail_form_kimde">-</div>
                        </div>
                    </div>
                    <div class="detail-stat-card">
                        <div class="detail-stat-icon icon-emerald">
                            <i class="fa fa-map-marker"></i>
                        </div>
                        <div class="detail-stat-content">
                            <div class="detail-stat-label">Keşif Konumu</div>
                            <div class="detail-stat-value" id="detail_konum">-</div>
                        </div>
                    </div>
                </div>

                <!-- Yapılacak İş & Kapsam -->
                <div class="detail-text-block">
                    <div class="detail-text-block-title">
                        <i class="fa fa-tasks text-primary"></i> Yapılacak İş / Keşif Kapsamı
                    </div>
                    <div class="detail-text-block-content" id="detail_yapilacak_is">-</div>
                </div>

                <!-- Keşif Sonu Notu / Rapor -->
                <div class="detail-text-block">
                    <div class="detail-text-block-title">
                        <i class="fa fa-sticky-note-o text-warning"></i> Keşif Sonu Notu / Saha Raporu
                    </div>
                    <div class="detail-text-block-content" id="detail_kesif_sonu_notu">-</div>
                </div>

                <!-- Saha Görselleri & Belgeler -->
                <div class="detail-text-block mb-0">
                    <div class="detail-text-block-title">
                        <i class="fa fa-paperclip text-info"></i> Saha Görselleri ve Ekli Belgeler
                    </div>
                    <div id="detail_gorseller" class="d-flex flex-wrap gap-2 pt-2"></div>
                </div>

                <!-- Meta Bilgiler (Kayıt / Güncelleme) -->
                <div class="row pt-3 mt-3 border-top text-muted font-11">
                    <div class="col-sm-6">
                        <span><i class="fa fa-user-plus mr-1 text-secondary"></i> Kayıt Yapan: </span>
                        <strong class="text-dark" id="detail_kayit_yapan">-</strong> · 
                        <span id="detail_kayit_tarihi">-</span>
                    </div>
                    <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
                        <span><i class="fa fa-history mr-1 text-secondary"></i> Son Güncelleme: </span>
                        <strong class="text-dark" id="detail_guncelleyen_kullanici">-</strong> · 
                        <span id="detail_guncelleme_tarihi">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer-premium">
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="detail_pdf_btn" target="_blank" rel="noopener" class="btn btn-outline-danger btn-sm rounded-lg">
                        <i class="fa fa-file-pdf-o mr-1"></i> PDF Raporu
                    </a>
                    <a href="#" id="detail_map_btn" class="btn btn-outline-success btn-sm rounded-lg map-view-btn">
                        <i class="fa fa-map-marker mr-1"></i> Haritada Gör
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-info btn-sm rounded-lg" id="detail_edit_btn">
                        <i class="fa fa-pencil mr-1"></i> Düzenle
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm rounded-lg px-3" data-dismiss="modal">
                        Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Harita Modal (Google Maps Embed) -->
<div class="modal fade" id="mapModal" tabindex="-1" role="dialog" aria-labelledby="mapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header-premium">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                        <i class="fa fa-map-marker"></i>
                    </div>
                    <div>
                        <h5 class="modal-header-title" id="mapModalLabel">Harita ve Konum Bilgisi</h5>
                        <p class="modal-header-sub">Keşif lokasyonu Google Haritalar üzerinden gösterilmektedir.</p>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" data-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <iframe id="mapFrame" width="100%" height="450" frameborder="0" style="border:0; display:block;" allowfullscreen="" loading="lazy"></iframe>
            </div>
            <div class="modal-footer-premium">
                <a href="#" id="mapsLink" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm rounded-lg">
                    <i class="fa fa-external-link mr-1"></i> Google Haritalar'da Aç
                </a>
                <button type="button" class="btn btn-secondary btn-sm rounded-lg px-3" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script src="include/js/data-table.js"></script>
<script src="pages/1/kesif/kesif.js?v=<?= time() ?>"></script>
<?php if (($_GET['action'] ?? '') === 'new' && permtrue('kesifCreate')) { ?>
<script>
    window.addEventListener('load', function () {
        var newKesifButton = document.getElementById('btnNewKesif');
        if (newKesifButton) newKesifButton.click();
    });
</script>
<?php } ?>
