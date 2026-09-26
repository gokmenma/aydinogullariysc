<?php
permcontrol('offeredit');

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Financial;
use App\Helper\Security;
use App\Model\OfferModel;

$offerObj = new OfferModel();

$oid = $_GET['id'] ?? 0;
$offer = $offerObj->find($oid);

if ($oid != 0 && (!permtrue('template_offer_edit') && $offer->is_template == 1)) {
    header("Location: index.php?p=offers/list&sablon=true");
    exit;
}

$offer_number = $offer->offerNumber ?? Helper::generateNumber("offer", "TK");
$template_offer_number = $oid != 0 ? $offer->offerNumber : Helper::generateNumber("template_offer", "Ş");

$enc_id = Security::encrypt($oid);

$offer_header_id = $offer->offer_header ?? 12;
$offer_header_content = "";
if ($oid != 0 && isset($offer->offer_header_content) && $offer->offer_header_content !== "") {
    $offer_header_content = $offer->offer_header_content;
} else if ($offer_header_id > 0) {
    $offer_header_content = offerTemplateContent($offer_header_id);
}

$offer_footer_id = $offer->offer_footer ?? 11;
$offer_footer_content = "";
if ($oid != 0 && isset($offer->offer_footer_content) && $offer->offer_footer_content !== "") {
    $offer_footer_content = $offer->offer_footer_content;
} else if ($offer_footer_id > 0) {
    $offer_footer_content = offerTemplateContent($offer_footer_id);
}
?>
<form enctype="multipart/form-data" id="myForm" method="POST">
    <input type="hidden" class="form-control" name="offer_id" id="offer_id" value="<?php echo $oid; ?>">
    <input type="hidden" class="form-control" name="offerNumber" id="offerNumber" value="<?php echo $offer_number; ?>">
    <input type="hidden" class="form-control" name="templateOfferNumber" id="templateOfferNumber" value="<?php echo $template_offer_number; ?>">
    <div class="offer-manage-wrapper">
        <!-- Header Card -->
        <div class="offer-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $oid != 0 ? 'Teklifi Düzenle' : 'Yeni Teklif Girişi'; ?></h4>
                        <span class="offer-number-badge">
                            <i class="fa fa-tag"></i> Teklif No: <?php echo $offer_number; ?>
                        </span>
                    </div>
                    <div class="header-actions-left d-flex align-items-center" style="gap: 8px; margin-left: 12px; flex-wrap: wrap;">
                        <!-- Quick action buttons -->
                        <a type="button" data-tooltip="Teklifi TL'ye Çevir" data-tooltip-location="bottom" id="convert_to_try" class="btn-header btn-header-action"><i class="fa fa-dollar"></i> TL'ye Çevir</a>

                        <?php
                        $servicelink = 'javascript:;';
                        $statu = $offer->statu ?? 1;
                        if ($statu == '2') {
                            $servicelink = 'index.php?p=service/manage&oid=' . $oid;
                        } ?>
                        <?php if ($statu == '2'): ?>
                            <a id="servicebutton" href="<?php echo $servicelink ?>" data-tooltip="Servis Oluştur" data-tooltip-location="bottom" class="btn-header btn-header-action"><i class="fa fa-gear"></i> Servis Oluştur</a>
                        <?php endif; ?>

                        <a href="pages/1/offers/offer-to-xls.php?id=<?php echo $enc_id ?>" data-tooltip="Teklifi Excele Aktar" data-tooltip-location="bottom" class="btn-header btn-header-action"><i class="fa fa-file-excel-o"></i> Excel</a>

                        <?php if (permtrue('offerview')): ?>
                            <a href="index.php?p=offer-view&id=<?php echo $oid; ?>" target="_blank" class="btn-header btn-header-action" data-tooltip="Teklifi Göster" data-tooltip-location="bottom"><i class="fa fa-eye"></i> Göster</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=offers/list" class="btn-header btn-header-list">
                        <i class="fa fa-list"></i> Listeye Dön
                    </a>
                    <button type="button" id="btn_save_offer" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>

        <style>
            /* Premium offer management styles */
            .offer-manage-wrapper {
                width: 100%;
                margin: 0;
            }

            /* Summary info styling */
            .sum-primary, .sum-success, .sum-warning, .sum-danger {
                border-radius: 12px;
                padding: 20px 24px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
                border: 1px solid #f1f5f9;
                display: flex;
                flex-direction: column;
                height: 100%;
                transition: transform 0.3s, box-shadow 0.3s;
            }
            
            .sum-primary:hover, .sum-success:hover, .sum-warning:hover, .sum-danger:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
            }

            .sum-primary { border-left: 4px solid #3b82f6; background: #eff6ff; }
            .sum-success { border-left: 4px solid #10b981; background: #ecfdf5; }
            .sum-warning { border-left: 4px solid #f59e0b; background: #fffbeb; }
            .sum-danger { border-left: 4px solid #ef4444; background: #fef2f2; }

            .sum-primary label, .sum-success label, .sum-warning label, .sum-danger label {
                margin: 0;
            }

            .sum-primary label:first-child { color: #1e40af; font-size: 13.5px; }
            .sum-success label:first-child { color: #065f46; font-size: 13.5px; }
            .sum-warning label:first-child { color: #92400e; font-size: 13.5px; }
            .sum-danger label:first-child { color: #991b1b; font-size: 13.5px; }

            .sum-primary label:last-child { color: #1d4ed8; font-size: 24px; font-weight: 700; margin-top: 6px; }
            .sum-success label:last-child { color: #047857; font-size: 24px; font-weight: 700; margin-top: 6px; }
            .sum-warning label:last-child { color: #b45309; font-size: 24px; font-weight: 700; margin-top: 6px; }
            .sum-danger label:last-child { color: #b91c1c; font-size: 24px; font-weight: 700; margin-top: 6px; }

            /* Form Card styling */
            .form-card {
                background: #fff;
                border-radius: 16px;
                padding: 30px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                margin-bottom: 25px;
                border: 1px solid #f0f0f0;
                width: 100%;
            }

            .form-card-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 25px;
                padding-bottom: 16px;
                border-bottom: 2px solid #f3f4f6;
            }

            .form-card-header .card-icon {
                width: 44px;
                height: 44px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                background: #eff6ff;
                color: #3b82f6;
            }

            .form-card-header h5 {
                margin: 0;
                font-size: 18px;
                font-weight: 700;
                color: #1e3a5f;
            }

            .form-card-header p {
                margin: 4px 0 0;
                font-size: 13.5px;
                color: #64748b;
            }

            .offer-status-control {
                margin-left: auto;
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 4px;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
            }

            .offer-status-control .status-option {
                border: 0;
                border-radius: 7px;
                padding: 7px 12px;
                background: transparent;
                color: #64748b;
                font-size: 12.5px;
                font-weight: 600;
                line-height: 1;
                cursor: pointer;
                transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
            }

            .offer-status-control .status-option i {
                margin-right: 5px;
            }

            .offer-status-control .status-option[data-status="1"].active {
                color: #92400e;
                background: #fef3c7;
                box-shadow: 0 1px 3px rgba(146, 64, 14, 0.15);
            }

            .offer-status-control .status-option[data-status="2"].active {
                color: #047857;
                background: #d1fae5;
                box-shadow: 0 1px 3px rgba(4, 120, 87, 0.15);
            }

            .offer-status-control .status-option[data-status="3"].active {
                color: #b91c1c;
                background: #fee2e2;
                box-shadow: 0 1px 3px rgba(185, 28, 28, 0.15);
            }

            .dark-mode .offer-status-control {
                border-color: #475569;
                background: #1e293b;
            }

            .dark-mode .offer-status-control .status-option {
                color: #cbd5e1;
            }

            @media (max-width: 767.98px) {
                .form-card-header {
                    flex-wrap: wrap;
                }

                .offer-status-control {
                    width: 100%;
                    margin-left: 0;
                }

                .offer-status-control .status-option {
                    flex: 1;
                }
            }

            .form-field {
                display: flex;
                flex-direction: column;
                gap: 8px;
                position: relative;
            }

            .form-field:focus-within,
            .form-field.select-open,
            .form-field:has(.bootstrap-select.show),
            .form-field:has(.bootstrap-select.open) {
                position: relative;
                z-index: 1060 !important;
            }

            .form-field.select-open .bootstrap-select,
            .form-field.select-open .dropdown-menu,
            .form-field .bootstrap-select.show .dropdown-menu,
            .form-field .bootstrap-select.open .dropdown-menu {
                z-index: 1070 !important;
            }

            .status-select-field {
                position: relative;
                z-index: 100;
            }

            .status-select-field.select-open,
            .status-select-field:focus-within,
            .status-select-field .bootstrap-select.show,
            .status-select-field .bootstrap-select.open {
                z-index: 1100 !important;
            }

            .status-select-field .dropdown-menu {
                z-index: 1105 !important;
            }

            #reject_reason_wrapper {
                position: relative;
                z-index: 10;
            }

            #reject_reason_wrapper.select-open,
            #reject_reason_wrapper:focus-within,
            #reject_reason_wrapper .bootstrap-select.show,
            #reject_reason_wrapper .bootstrap-select.open {
                z-index: 1050 !important;
            }

            #reject_reason_wrapper .dropdown-menu {
                z-index: 1055 !important;
            }

            .customer-select-field {
                position: relative;
                z-index: 2000;
            }

            .customer-select-field .bootstrap-select,
            .customer-select-field .bootstrap-select.show,
            .customer-select-field .bootstrap-select.open,
            .customer-select-field .dropdown-menu {
                z-index: 2001 !important;
            }

            .form-field label {
                font-size: 13.5px;
                font-weight: 600;
                color: #334155;
                display: flex;
                align-items: center;
                margin-bottom: 0;
            }

            .form-field label font[color="red"] {
                color: #ef4444;
                margin-right: 4px;
                font-weight: bold;
            }

            .offer-inline-fields {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 15px;
            }

            @media (max-width: 575.98px) {
                .offer-inline-fields {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
            }

            .offer-totals-drawer {
                position: fixed;
                top: 50%;
                right: 0;
                z-index: 1065;
                width: 240px;
                max-width: calc(100vw - 24px);
                transform: translate(100%, -50%);
                transition: transform 0.28s ease;
            }

            .offer-totals-drawer.is-open {
                transform: translate(0, -50%);
            }

            .offer-totals-toggle {
                position: absolute;
                top: 50%;
                right: 100%;
                display: flex;
                align-items: center;
                gap: 5px;
                min-height: 112px;
                padding: 10px 6px;
                border: 0;
                border-radius: 12px 0 0 12px;
                background: linear-gradient(180deg, #2563eb, #1e3a5f);
                color: #fff;
                box-shadow: -6px 8px 22px rgba(30, 58, 95, 0.25);
                transform: translateY(-50%);
                cursor: pointer;
            }

            .offer-totals-toggle span {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .04em;
                line-height: 1;
                writing-mode: vertical-rl;
            }

            .offer-totals-toggle i {
                transition: transform 0.28s ease;
            }

            .offer-totals-drawer.is-open .offer-totals-toggle i {
                transform: rotate(180deg);
            }

            .offer-totals-panel {
                overflow: hidden;
                border: 1px solid rgba(148, 163, 184, .32);
                border-right: 0;
                border-radius: 18px 0 0 18px;
                background: rgba(255, 255, 255, .97);
                box-shadow: -12px 16px 38px rgba(15, 23, 42, .18);
                backdrop-filter: blur(12px);
            }

            .offer-totals-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 18px 20px;
                background: linear-gradient(135deg, #1e3a5f, #2d5986);
                color: #fff;
            }

            .offer-totals-head h5,
            .offer-totals-head p {
                margin: 0;
                color: inherit;
            }

            .offer-totals-head h5 { font-size: 16px; font-weight: 700; }
            .offer-totals-head p { margin-top: 3px; font-size: 11.5px; opacity: .72; }
            .offer-totals-head i { font-size: 22px; opacity: .9; }
            .offer-totals-body { padding: 16px 18px 18px; }

            .offer-total-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 10px 2px;
                border-bottom: 1px solid #edf2f7;
            }

            .offer-total-row:last-of-type { border-bottom: 0; }
            .offer-total-row span { color: #64748b; font-size: 13px; }
            .offer-total-row strong { color: #1e3a5f; font-size: 14px; }

            .offer-grand-total {
                margin-top: 12px;
                padding: 15px;
                border-radius: 12px;
                background: #eff6ff;
                text-align: center;
            }

            .offer-grand-total span {
                display: block;
                color: #64748b;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .06em;
                text-transform: uppercase;
            }

            .offer-grand-total strong {
                display: block;
                margin-top: 4px;
                color: #1d4ed8;
                font-size: 24px;
                line-height: 1.2;
            }

            .offer-totals-backdrop {
                position: fixed;
                inset: 0;
                z-index: 1060;
                display: none;
                background: rgba(15, 23, 42, .38);
            }

            .dark-mode .offer-totals-panel { background: rgba(15, 23, 42, .97); border-color: #334155; }
            .dark-mode .offer-total-row { border-color: #334155; }
            .dark-mode .offer-total-row span { color: #94a3b8; }
            .dark-mode .offer-total-row strong { color: #e2e8f0; }
            .dark-mode .offer-grand-total { background: #172554; }
            .dark-mode .offer-grand-total span { color: #93c5fd; }
            .dark-mode .offer-grand-total strong { color: #dbeafe; }

            @media (max-width: 575.98px) {
                .offer-totals-drawer { width: min(235px, calc(100vw - 54px)); }
                .offer-totals-drawer.is-open + .offer-totals-backdrop { display: block; }
            }

            @media (prefers-reduced-motion: reduce) {
                .offer-totals-drawer,
                .offer-totals-toggle i { transition: none; }
            }

            .form-field .form-control,
            .form-field .bootstrap-select .btn,
            .form-field .select2-container--default .select2-selection--single {
                border-radius: 10px !important;
                border: 1.5px solid #e5e7eb !important;
                padding: 10px 14px !important;
                font-size: 14px !important;
                transition: all 0.25s ease !important;
                background: #fafafa !important;
                height: 46px !important;
                display: flex !important;
                align-items: center !important;
            }

            .form-field .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #1e293b !important;
                line-height: normal !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                font-size: 14px !important;
            }

            .form-field .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 44px !important;
                right: 12px !important;
            }

            .form-field .form-control:focus,
            .form-field .select2-container--default.select2-container--focus .select2-selection--single,
            .form-field .select2-container--default.select2-container--open .select2-selection--single {
                border-color: var(--focus-color, var(--topbar-color, var(--theme-primary, #3b82f6))) !important;
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--focus-color, var(--topbar-color, var(--theme-primary, #3b82f6))) 18%, transparent) !important;
                background: #fff !important;
            }

            .form-field.is-invalid .form-control,
            .form-field.is-invalid .select2-container--default .select2-selection,
            .select2-container.is-invalid .select2-selection {
                border-color: #ef4444 !important;
                background: #fef2f2 !important;
                box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
            }

            .form-field textarea.form-control:not(.textarea_editor) {
                min-height: 100px;
                resize: vertical;
            }

            /* Input group alignments */
            .form-field .input-group {
                display: flex;
                flex-wrap: nowrap;
                align-items: stretch;
                width: 100%;
            }

            .form-field .input-group .bootstrap-select,
            .form-field .input-group span.select2.select2-container {
                flex: 1 1 auto;
                width: 1% !important;
                min-width: 0;
            }

            .form-field .input-group .bootstrap-select .btn,
            .form-field .input-group .select2-container .select2-selection {
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
            }

            .form-field .input-group a.btn {
                flex: 0 0 auto;
                border-radius: 0 10px 10px 0 !important;
                border: 1.5px solid #e5e7eb !important;
                border-left: none !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 10px 15px;
                height: auto;
            }

            /* WYSIHTML5 Rich Text Editor Custom Styling */
            .form-field textarea.textarea_editor,
            .html-editor textarea,
            .html-editor textarea.textarea_editor,
            .html-editor textarea.form-control,
            textarea.textarea_editor {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                min-height: 0 !important;
                max-height: 0 !important;
                padding: 0 !important;
                border: none !important;
                margin: 0 !important;
                position: absolute !important;
                pointer-events: none !important;
                opacity: 0 !important;
            }

            .offerHeaderContent iframe.wysihtml5-sandbox,
            .offerFooterContent iframe.wysihtml5-sandbox {
                padding: 0 !important;
            }

            .offerHeaderContent iframe.wysihtml5-sandbox {
                height: 190px !important;
                min-height: 190px !important;
            }

            .offerFooterContent iframe.wysihtml5-sandbox {
                height: 220px !important;
                min-height: 220px !important;
            }

            /* Dinamik Müşteri Select2 Stilleri (E-posta / Alıcı Seçimi Standardı ile birebir aynı) */
            .customer-select-box .select2-container--default .select2-selection--single {
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                height: 42px !important;
                min-height: 42px !important;
                padding: 6px 12px !important;
                background-color: #ffffff !important;
                display: flex !important;
                align-items: center !important;
                transition: all 0.2s ease;
            }

            .customer-select-box .select2-container--default.select2-container--focus .select2-selection--single,
            .customer-select-box .select2-container--default.select2-container--open .select2-selection--single {
                border-color: var(--focus-color, var(--topbar-color, var(--theme-primary, #3b82f6))) !important;
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--focus-color, var(--topbar-color, var(--theme-primary, #3b82f6))) 18%, transparent) !important;
            }

            .customer-select-box .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #0f172a !important;
                font-weight: 500 !important;
                font-size: 13.5px !important;
                line-height: normal !important;
                padding-left: 0 !important;
                padding-right: 20px !important;
            }

            .customer-select-box .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 40px !important;
                right: 10px !important;
            }

            .select2-dropdown {
                border: 1px solid #cbd5e1 !important;
                border-radius: 10px !important;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
                overflow: hidden !important;
                z-index: 99999 !important;
            }

            .select2-results__option {
                padding: 9px 14px !important;
                font-size: 13px !important;
                border-bottom: 1px solid #f1f5f9;
                background-color: #ffffff !important;
                transition: background 0.15s ease, color 0.15s ease;
            }

            .select2-results__option:last-child {
                border-bottom: none;
            }

            .select2-customer-option {
                display: flex;
                flex-direction: column;
                gap: 3px;
            }

            .customer-title {
                font-weight: 600;
                color: #0f172a !important;
                display: flex;
                align-items: center;
                gap: 7px;
                font-size: 13.5px;
            }

            .customer-title i {
                color: var(--focus-color, var(--topbar-color, var(--theme-primary, #2563eb))) !important;
            }

            .customer-sub {
                font-size: 12px;
                color: #475569 !important;
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 6px;
            }

            .customer-sub i {
                color: #64748b !important;
            }

            /* HOVER & HIGHLIGHTED DURUM: Dinamik Tema Rengi */
            .select2-container--default .select2-results__option--highlighted[aria-selected],
            .select2-container--default .select2-results__option--highlighted,
            .select2-results__option:hover {
                background: var(--focus-color, var(--topbar-color, var(--theme-primary, #1d4ed8))) !important;
                color: #ffffff !important;
            }

            .select2-container--default .select2-results__option--highlighted .customer-title,
            .select2-container--default .select2-results__option--highlighted .customer-title i,
            .select2-results__option:hover .customer-title,
            .select2-results__option:hover .customer-title i {
                color: #ffffff !important;
            }

            .select2-container--default .select2-results__option--highlighted .customer-sub,
            .select2-container--default .select2-results__option--highlighted .customer-sub i,
            .select2-results__option:hover .customer-sub,
            .select2-results__option:hover .customer-sub i {
                color: rgba(255, 255, 255, 0.85) !important;
            }

            /* Satır İçi Ürün Otomatik Tamamlama (Inline Autocomplete) */
            .product-autocomplete-wrap {
                position: relative;
                width: 100%;
            }

            .product-autocomplete-results {
                position: fixed !important;
                max-height: 280px;
                overflow-y: auto;
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                box-shadow: 0 14px 35px -5px rgba(15, 23, 42, 0.25);
                z-index: 9999999 !important;
                padding: 4px;
            }

            .product-autocomplete-item {
                padding: 8px 12px;
                border-radius: 6px;
                cursor: pointer;
                border-bottom: 1px solid #f1f5f9;
                transition: all 0.15s ease;
            }

            .product-autocomplete-item:last-child {
                border-bottom: none;
            }

            .product-autocomplete-item:hover,
            .product-autocomplete-item.active {
                background: var(--theme-primary-light, #eff6ff);
                border-left: 3px solid var(--theme-primary, #2563eb);
            }

            .product-autocomplete-item .product-item-title {
                font-size: 13px;
                font-weight: 600;
                color: #0f172a;
                line-height: 1.25;
            }

            .product-autocomplete-item .product-item-stock {
                font-size: 11px;
                font-weight: 700;
                color: #475569;
                background: #f1f5f9;
                padding: 2px 6px;
                border-radius: 4px;
                border: 1px solid #e2e8f0;
            }

            .product-autocomplete-item .product-item-meta {
                font-size: 11.5px;
                color: #64748b;
                margin-top: 4px;
            }

            /* Dark Mode Autocomplete */
            .dark-mode .product-autocomplete-results {
                background: #1e293b !important;
                border-color: #334155 !important;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
            }

            .dark-mode .product-autocomplete-item {
                border-bottom-color: #334155 !important;
            }

            .dark-mode .product-autocomplete-item:hover,
            .dark-mode .product-autocomplete-item.active {
                background: #334155 !important;
                border-left-color: var(--theme-primary, #3b82f6) !important;
            }

            .dark-mode .product-autocomplete-item .product-item-title {
                color: #f8fafc !important;
            }

            .dark-mode .product-autocomplete-item .product-item-stock {
                background: #0f172a !important;
                color: #cbd5e1 !important;
                border-color: #334155 !important;
            }

            .dark-mode .product-autocomplete-item .product-item-meta {
                color: #94a3b8 !important;
            }
        </style>

        <!-- Form Card -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div>
                    <h5>Teklif Detayları</h5>
                    <p>Teklif genel bilgilerini ve üst/alt bilgi şablon tercihlerini bu alandan yönetebilirsiniz.</p>
                </div>
                <?php $offer_statu = $offer->statu ?? 1; ?>
                <div class="offer-status-control" role="group" aria-label="Teklif durumu">
                    <button type="button" class="status-option<?php echo $offer_statu == 1 ? ' active' : ''; ?>" data-status="1" aria-pressed="<?php echo $offer_statu == 1 ? 'true' : 'false'; ?>">
                        <i class="fa fa-clock-o"></i>Bekleyen
                    </button>
                    <button type="button" class="status-option<?php echo $offer_statu == 2 ? ' active' : ''; ?>" data-status="2" aria-pressed="<?php echo $offer_statu == 2 ? 'true' : 'false'; ?>">
                        <i class="fa fa-check"></i>Tamamlandı
                    </button>
                    <button type="button" class="status-option<?php echo $offer_statu == 3 ? ' active' : ''; ?>" data-status="3" aria-pressed="<?php echo $offer_statu == 3 ? 'true' : 'false'; ?>">
                        <i class="fa fa-times-circle"></i>Kabul Edilmedi
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Sol Kolon: Genel Teklif Bilgileri -->
                <div class="col-lg-6 col-md-12">
                    <div class="d-flex flex-column" style="gap: 20px;">
                        
                        <!-- Firma Adı -->
                        <div class="form-field customer-select-field">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="customers" class="mb-0 font-14 weight-600"><font color="red">(*)</font> Firma Adı</label>
                                <?php if (permtrue('customeradd')) { ?>
                                    <button type="button" id="btnOpenQuickCustomerModal" class="btn btn-outline-primary btn-sm" style="border-radius: 6px; padding: 2px 10px; font-size: 12px; font-weight: 600; line-height: 1.4;">
                                        <i class="fa fa-plus-circle mr-1"></i> Hızlı Firma Ekle
                                    </button>
                                <?php } ?>
                            </div>
                            <div class="customer-select-box" style="width: 100%;">
                                <select required name="customers" id="customers" data-placeholder="Firma adı veya yetkili yazarak arayın..." class="form-control select2-customer-select" style="width: 100%;">
                                    <option value="">Firma adı veya yetkili yazarak arayın...</option>
                                    <?php
                                    $selectedId = (int)($offer->cid ?? 0);
                                    if ($selectedId > 0) {
                                        $stmtSelected = $ac->prepare("SELECT id, company, yetkili, email, gsm, city, ilce, OdemeVade FROM customers WHERE id = :id AND deleted_at IS NULL LIMIT 1");
                                        $stmtSelected->execute([':id' => $selectedId]);
                                        $cSel = $stmtSelected->fetch(PDO::FETCH_ASSOC);
                                        if ($cSel) {
                                            ?>
                                            <option value="<?php echo (int)$cSel['id']; ?>" selected
                                                data-company="<?php echo htmlspecialchars($cSel['company'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-author="<?php echo htmlspecialchars($cSel['yetkili'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-payperiod="<?php echo htmlspecialchars($cSel['OdemeVade'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-email="<?php echo htmlspecialchars($cSel['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-gsm="<?php echo htmlspecialchars($cSel['gsm'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-city="<?php echo htmlspecialchars($cSel['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-ilce="<?php echo htmlspecialchars($cSel['ilce'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($cSel['company']); ?>
                                            </option>
                                            <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Firma Yetkilisi -->
                        <div class="form-field">
                            <label for="compAuths"><font color="red">(*)</font> Firma Yetkilisi</label>
                            <input type="text" class="form-control" placeholder="Yetkili ad soyad" id="compAuths" name="compAuths" value="<?php echo $offer->company_authors ?? ''; ?>" required />
                        </div>

                        <!-- Teklif Konusu -->
                        <div class="form-field">
                            <label for="offer_subject">Teklif Konusu</label>
                            <input type="text" id="offer_subject" name="offer_subject" class="form-control" value="<?php echo $offer->offer_subject ?? '' ?>" placeholder="Örn: Yeni Teklif">
                        </div>

                        <div class="offer-inline-fields">
                            <!-- Ödeme Vadesi -->
                            <div class="form-field">
                                <label for="payPeriod">Ödeme Vadesi</label>
                                <input type="text" id="payPeriod" name="payPeriod" class="form-control" value="<?php echo $offer->payment_period ?? '' ?>" placeholder="Vade giriniz!">
                            </div>

                            <!-- Tarih -->
                            <div class="form-field">
                                <label for="offer_date"><font color="red">(*)</font> Tarih</label>
                                <input type="text" id="offer_date" name="offer_date" value="<?php echo $offer->offer_date ?? date('d.m.Y'); ?>" class="form-control date-picker" placeholder="">
                            </div>
                        </div>

                        <!-- Teklif Durumu -->
                        <div class="form-field status-select-field">
                            <label for="offerstatu"><font color="red">(*)</font> Teklif Durumu</label>
                            <select name="offerstatu" id="offerstatu" data-placeholder="Teklif Durumu Seçiniz..." class="form-control select2">
                                <option <?php echo $offer_statu == 1 ? ' selected' : '' ?> value="1">Bekleyen</option>
                                <option <?php echo $offer_statu == 2 ? ' selected' : '' ?> value="2">Tamamlandı</option>
                                <option <?php echo $offer_statu == 3 ? ' selected' : '' ?> value="3">Kabul Edilmedi</option>
                            </select>
                        </div>

                        <!-- Kabul Edilmeme Bilgileri (Kabul Edilmedi seçildiğinde görünür) -->
                        <div id="reject_reason_wrapper" class="form-field p-3 rounded" style="background: #fef2f2; border: 1px solid #fecaca; <?php echo $offer_statu == 3 ? '' : 'display: none;'; ?>">
                            <label for="reject_reason" class="text-danger font-weight-bold mb-2">
                                <i class="fa fa-exclamation-circle mr-1"></i> Kabul Edilmeme Nedeni
                            </label>
                            <?php
                            $reject_reasons = [
                                'Fiyat Yüksek Bulundu',
                                'Rakip Firma Tercih Edildi',
                                'Bütçe Yetersizliği / Bütçe İptali',
                                'Proje / İhtiyaç İptal Edildi',
                                'Teslimat Süresi / Termin Uyuşmazlığı',
                                'Teknik / Şartname Uyuşmazlığı',
                                'Müşteriye Ulaşılamadı / Yanıt Alınamadı',
                                'Diğer'
                            ];
                            $current_reject_reason = $offer->reject_reason ?? '';
                            ?>
                            <select name="reject_reason" id="reject_reason" data-placeholder="Kabul edilmeme nedeni seçiniz..." class="form-control select2 mb-2">
                                <option value="">-- Neden Seçiniz --</option>
                                <?php foreach ($reject_reasons as $reason): ?>
                                    <option value="<?php echo htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $current_reject_reason === $reason ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="reject_detail" class="text-muted font-weight-bold mt-2 mb-1" style="font-size:12px;">Kabul Edilmeme Açıklaması / Detaylar</label>
                            <textarea name="reject_detail" id="reject_detail" rows="2" class="form-control" placeholder="Müşterinin geri bildirimi, fiyat farkı veya detaylı açıklama yazınız..."><?php echo htmlspecialchars($offer->reject_detail ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <!-- Dosya -->
                        <div class="form-field">
                            <label for="offerFile"><font color="red">(*)</font> Dosya Ekipmanı</label>
                            <div class="input-group">
                                <?php
                                $offer_file = $offer->file ?? '';
                                $file_input_type = $offer_file != '' ? 'text' : 'file';
                                ?>
                                <input type="<?php echo $file_input_type; ?>" id="offerFile" name="offerFile" value="<?php echo $offer_file ?? '' ?>" class="form-control">
                                <?php if ($offer_file != ''): ?>
                                    <a type="button" id="downloadfile" href="files/offer/<?php echo $offer_file; ?>" target="_blank" class="btn btn-info btn-sm d-flex align-items-center ml-1">Dosyayı İndir</a>
                                    <button id="deleteFile" onclick="DeleteFile(<?php echo $oid ?>)" type="button" class="btn btn-danger btn-sm d-flex align-items-center ml-1" data-tooltip="Dosyayı Sil"><i class="fa fa-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Notlar -->
                        <div class="form-field">
                            <label for="description">Notlar</label>
                            <textarea name="description" id="description" placeholder="Teklif hakkında bilgilendirici nitelikte not ekleyiniz." class="form-control"><?php echo $offer->description ?? ''; ?></textarea>
                        </div>

                        <?php if (permtrue('template_offer_create')) { ?>
                            <div class="form-field">
                                <label>Şablon Teklif Yap</label>
                                <div class="custom-control custom-checkbox mt-2">
                                    <?php
                                    $checked = '';
                                    $is_template = $offer->is_template ?? 0;
                                    if (isset($is_template) && $is_template == 1) {
                                        $checked = 'checked';
                                    }
                                    ?>
                                    <input class="custom-control-input" type="checkbox" value="<?php echo $is_template ?>" name="is_template" id="is_template" <?php echo $checked; ?>>
                                    <label class="custom-control-label" for="is_template">Evet, bu teklifi şablon yap.</label>
                                </div>
                            </div>
                        <?php } ?>

                    </div>
                </div>

                <!-- Sağ Kolon: Üst ve Alt Bilgi Şablon & Açıklamaları -->
                <div class="col-lg-6 col-md-12">
                    <div class="d-flex flex-column" style="gap: 20px;">
                        
                        <!-- Üst Bilgi Şablonu Seç -->
                        <div class="form-field">
                            <label for="offerHeader">Üst Bilgi Şablonu Seç</label>
                            <div class="input-group">
                                <?php offerTemplate('offerHeader', $offer_header_id, 'Header', 'form-control select2'); ?>
                                <a href="index.php?p=offer-templates&type=Header" target="_blank" class="btn btn-secondary btn-sm d-flex align-items-center" type="button" data-tooltip="Yeni Şablon Eklemek için tıklayınız!" data-tooltip-location="left"><i class="fa fa-plus"></i></a>
                            </div>
                        </div>

                        <!-- Üst Bilgi Açıklaması -->
                        <div class="form-field">
                            <label for="offerHeaderContent">Üst Bilgi Açıklaması</label>
                            <div id="offerHeaderContent" class="offerHeaderContent html-editor">
                                <textarea name="offerHeaderContent" class="textarea_editor form-control" style="display: none !important;" placeholder="Üst bilgi açıklaması..."><?php echo htmlspecialchars($offer_header_content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>

                        <!-- Alt Bilgi Şablonu Seç -->
                        <div class="form-field">
                            <label for="offerFooter">Alt Bilgi Şablonu Seç</label>
                            <div class="input-group">
                                <?php offerTemplate('offerFooter', $offer_footer_id, 'Footer', 'form-control select2'); ?>
                                <a href="index.php?p=offer-templates&type=Footer" target="_blank" class="btn btn-secondary btn-sm d-flex align-items-center" type="button" data-tooltip="Yeni Şablon Eklemek için tıklayınız!" data-tooltip-location="left"><i class="fa fa-plus"></i></a>
                            </div>
                        </div>

                        <!-- Alt Bilgi Açıklaması -->
                        <div class="form-field">
                            <label for="offerFooterContent">Alt Bilgi Açıklaması</label>
                            <div id="offerFooterContent" class="offerFooterContent html-editor">
                                <textarea name="offerFooterContent" class="textarea_editor form-control" style="display: none !important;" placeholder="Alt bilgi açıklaması..."><?php echo htmlspecialchars($offer_footer_content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>


    <!-- TEKLİF KALEMLERİ ÖZET BİLGİ -->
        <!-- TEKLİF KALEMLERİ CARD -->
        <div class="form-card animate-fade-in mt-4">
            <div class="form-card-header">
                <div class="card-icon">
                    <i class="fa fa-list"></i>
                </div>
                <div>
                    <h5>Teklif Kalemleri</h5>
                    <p>Teklifteki ürün ve hizmetlerin detaylarını ve miktarlarını buradan düzenleyebilirsiniz.</p>
                </div>
            </div>
            <?php

            $alisToplam = $offer->tl_alis_toplam ?? 0;
            $satisToplam = $offer->tl_satis_toplam ?? 0;

            if (isset($satisToplam) && isset($alisToplam)) {
                $KarTL = $satisToplam - $alisToplam;
            }

            if (isset($satisToplam) && isset($alisToplam) && $alisToplam > 0) {
                $KarOrani = number_format(($satisToplam - $alisToplam) / $alisToplam * 100, 2);
            } else {
                $KarOrani = '0.00 TL';
            }

            //eğer alış tutarı 0 ve satış tutarı 0'dan büyükse kar oranı 100 olacak
            if ($alisToplam == 0 && $satisToplam > 0) {
                $KarOrani = '100';
            }
            ?>

            <!-- ÖZET ALANLARI -->
            <div class="row ml-0 mr-0 mb-30">
                <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                    <div class="sum-primary">
                        <label style="font-weight: 600;" for="">Alış TL</label>
                        <label id="buy-tl" for="">
                            <?php echo tlFormat($alisToplam ?? 0) ?>
                        </label>
                        <input type="hidden" name="buy-tl-input" id="buy-tl-input" value="<?php echo $alisToplam ?? 0 ?>">
                    </div>
                </div>
                <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                    <div class="sum-success">
                        <label style="font-weight: 600;" for="">Satış TL</label>
                        <label id="sale-tl" for="">
                            <?php echo tlFormat($satisToplam ?? 0) ?>
                        </label>
                        <input type="hidden" name="sale-tl-input" id="sale-tl-input" value="<?php echo $satisToplam ?? 0 ?>">
                    </div>
                </div>
                <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                    <div class="sum-warning">
                        <label style="font-weight: 600;" for="">Kâr TL</label>
                        <label id="profit-tl" for="">
                            <?php echo tlFormat($KarTL ?? 0) ?>
                        </label>
                    </div>
                </div>
                <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                    <div class="sum-danger">
                        <label style="font-weight: 600;" for="">Kâr Oranı</label>
                        <label name="profit-rate" id="profit-rate" for="">
                            <?php echo $KarOrani . ' %' ?>
                        </label>
                    </div>
                </div>
            </div>
            <!-- ÖZET ALANLARI -->

            <style>
            .premium-table {
                width: 100%;
                table-layout: fixed !important;
                border-collapse: separate;
                border-spacing: 0;
                margin-top: 15px;
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #e2e8f0 !important;
            }
            .premium-table thead {
                background: #f8fafc;
            }
            .premium-table th {
                color: #475569;
                font-weight: 600;
                font-size: 11px !important;
                padding: 10px 4px !important;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-bottom: 2px solid #e2e8f0;
                background: #f8fafc;
                text-align: center;
                vertical-align: middle;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .premium-table td {
                padding: 6px 4px !important;
                vertical-align: middle;
                border-bottom: 1px solid #f1f5f9;
                background: #fff;
            }
            .premium-table tbody tr:hover td {
                background: #f8fafc;
            }
            .premium-table tfoot td {
                background: #f8fafc;
                border-top: 2px solid #e2e8f0;
                padding: 12px 16px;
            }
            
            /* Compact Inputs inside premium-table */
            .premium-table .form-control {
                height: 32px !important;
                padding: 4px 6px !important;
                font-size: 13px !important;
                border-radius: 6px !important;
                text-align: center;
            }
            .premium-table .urunAdi.form-control,
            .premium-table .stokKodu.form-control,
            .premium-table input[name="stokKodu[]"] {
                text-align: left !important;
            }
            .premium-table select.form-control,
            .premium-table .bootstrap-select .btn {
                height: 32px !important;
                padding: 4px 6px !important;
                font-size: 13px !important;
                border-radius: 6px !important;
            }

            .premium-table .bootstrap-select.form-control {
                display: block;
                width: 100% !important;
                height: 32px !important;
                min-height: 32px !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                background: transparent !important;
            }

            .premium-table .bootstrap-select.form-control > .dropdown-toggle {
                margin: 0 !important;
                vertical-align: top;
            }
            .premium-table .btn-sm {
                padding: 4px 6px !important;
                height: 32px !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .premium-table .input-group-append .btn,
            .premium-table .input-group .btn {
                height: 32px !important;
                padding: 4px 8px !important;
                font-size: 13px !important;
            }

            .premium-table td:nth-child(5) .input-group {
                display: flex;
                flex-wrap: nowrap;
                align-items: stretch;
                width: 100%;
            }

            .premium-table td:nth-child(5) .input-group .form-control {
                flex: 1 1 auto;
                width: 1%;
                min-width: 0;
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
                text-align: left;
            }

            .premium-table td:nth-child(5) .input-group .selectProduct {
                flex: 0 0 34px;
                width: 34px;
                margin-left: -1px;
                border-top-left-radius: 0 !important;
                border-bottom-left-radius: 0 !important;
                border-top-right-radius: 6px !important;
                border-bottom-right-radius: 6px !important;
            }
            
            .hack1 {
                display: table;
                table-layout: fixed;
                width: 100%;
            }

            .hack2 {
                display: table-cell;
                overflow-x: auto;
                width: 100%;
            }
            </style>

            <!-- TEKLİF KALEMLERİ TABLOSU -->
            <div class="hack1">
                <div class="hack2">
                    <table id="kalem_ekle" class="table premium-table">
                        <thead>
                            <tr>
                                <th style="width: 35px;">Taşı</th>
                                <th style="width: 65px;">İşlem</th>
                                <th class="text-center" style="width: 45px;">Sıra</th>
                                <th style="width: 100px;">Stok Kodu</th>
                                <th style="width: 250px;">Ürün/Malzeme</th>
                                <th style="width: 55px;"><label for="amount[]" class="m-0">Miktar</label> </th>
                                <th style="width: 80px;">Birim</th>
                                <th class="text-center" style="width: 85px;"><label for="price[]" class="m-0">Satış</label> </th>
                                <th style="width: 70px;">Sat.Para</th>
                                <th style="width: 90px;">Tutar</th>
                                <th class="text-center" style="width: 85px;"><label for="price[]" class="m-0">Alış</label> </th>
                                <th style="width: 70px;">Al.Para</th>
                            </tr>
                        </thead>

                        <tbody id="sortable">
                            <?php
                            $items = $ac->prepare('SELECT * FROM offermatters WHERE oid = ? ORDER BY satirno');
                            $items->execute(array($oid));
                            $satirNo = 0;
                            while ($item = $items->fetch(PDO::FETCH_ASSOC)) {
                                $satirNo += 1;
                                ?>
                            <tr class="ui-state-default">
                                <?php
                                    $stokKodu = $item['stokKodu'];
                                    $urunAdi = $item['title'];
                                    $amount = $item['amount'];
                                    $unit = $item['unit'];
                                    $buyprice = $item['buyprice'];
                                    $buycur = $item['buycur'];
                                    $saleprice = $item['saleprice'];
                                    $salecur = $item['salecur'];
                                    $rowTotal = $item['total_price'];

                                    include 'offer-row.php'
                                        ?>
                            </tr>
                            <?php
                            }
                            if ($satirNo == 0) {
                                $satirNo = 1;
                                $stokKodu = '';
                                $urunAdi = '';
                                $buyprice = '';
                                $saleprice = '';
                                $unit = '';
                                $amount = '';
                                $buycur = '';
                                $salecur = '';
                                $rowTotal = '0.00';

                                include_once 'offer-row.php';
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="12" class="py-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" id="ekle" class="btn btn-primary btn-sm" style="border-radius: 8px; font-weight: 600; padding: 7px 18px;">
                                                <i class="fa fa-plus-circle mr-1"></i> Yeni Satır Ekle
                                            </button>
                                            <button type="button" id="btnOpenMultiProductModal" class="btn btn-outline-primary btn-sm" style="border-radius: 8px; font-weight: 600; padding: 7px 18px;">
                                                <i class="fa fa-th-list mr-1"></i> Toplu Ürün Ekle
                                            </button>
                                        </div>
                                        <div class="text-muted font-12 d-flex align-items-center">
                                            <i class="fa fa-keyboard-o mr-1 text-primary"></i> İpucu: Satırda <b>Tab</b> tuşuyla sonraki alana geçebilir, ürün adını yazarak anında seçebilirsiniz.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <input type="hidden" id="rowNumberId" value="<?php echo $satirNo + 1 ?>">

        <!-- ALT TOPLAMLAR CARD -->
        <div class="form-card animate-fade-in mt-4">
            <div class="form-card-header">
                <div class="card-icon">
                    <i class="fa fa-calculator"></i>
                </div>
                <div>
                    <h5>Alt Toplamlar</h5>
                    <p>İskonto, KDV ve döviz kurlarına göre hesaplanan alt toplam bilgileri.</p>
                </div>
            </div>
            <div class="hack1">
                <div class="hack2">
                    <table id="tblAltToplam" class="table premium-table">
                        <thead>
                            <th style="min-width:120px">Teklifi Göster</th>
                            <th>Euro</th>
                            <th>Dolar</th>
                            <th>TL</th>
                        </thead>
                        <tbody>
                            <tr>
                                <!-- **********************ALT TOPLAM *****************************-->
                                <td style="font-weight: 600; color: #475569;">
                                    Alt Toplam
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control text-center" name="euro_alt_toplam" id="euro_alt_toplam" value="<?php echo $offer->euro_alt_toplam ?? 0 ?>" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control text-center" name="dolar_alt_toplam" id="dolar_alt_toplam" value="<?php echo $offer->dolar_alt_toplam ?? 0 ?>" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control text-center" name="tl_alt_toplam" id="tl_alt_toplam" value="<?php echo $offer->tl_alt_toplam ?? 0 ?>" style="border-radius: 8px;">
                                </td>
                            </tr>
                            <!-- **********************ALT TOPLAM *****************************-->

                            <!-- *************************İSKONTO *****************************-->
                            <tr>
                                <td style="font-weight: 600; color: #475569;">
                                    İskonto
                                </td>
                                <td>
                                    <input type="number" autocomplete="off" class="form-control text-center" name="euro_iskonto" value="<?php echo $offer->euro_iskonto ?? 0 ?>" id="euro_iskonto" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="number" autocomplete="off" class="form-control text-center" name="dolar_iskonto" value="<?php echo $offer->dolar_iskonto ?? 0 ?>" id="dolar_iskonto" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="number" autocomplete="off" class="form-control text-center" name="tl_iskonto" value="<?php echo $offer->tl_iskonto ?? 0 ?>" id="tl_iskonto" style="border-radius: 8px;">
                                </td>
                            </tr>
                            <!-- **********************İSKONTO *****************************-->

                            <!-- *************************ARA TOPLAM *****************************-->
                            <tr>
                                <td style="font-weight: 600; color: #475569;">
                                    Ara Toplam
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control text-center" name="euro_ara_toplam" value="<?php echo $offer->euro_ara_toplam ?? 0 ?>" id="euro_ara_toplam" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control text-center" name="dolar_ara_toplam" value="<?php echo $offer->dolar_ara_toplam ?? 0 ?>" id="dolar_ara_toplam" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control text-center" name="tl_ara_toplam" value="<?php echo $offer->tl_ara_toplam ?? 0 ?>" id="tl_ara_toplam" style="border-radius: 8px;">
                                </td>
                            </tr>
                            <!-- **********************ARA TOPLAM *****************************-->

                            <!-- *************************KDV *****************************-->
                            <tr>
                                <td style="font-weight: 600; color: #475569;">
                                    <div class="d-flex align-items-center">
                                        <label class="mr-2 mb-0">Kdv</label>
                                        <?php KdvOranları('Kdv', $offer->Kdv ?? 20) ?>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" autocomplete="off" class="form-control text-center" name="euro_kdv" readonly value="<?php echo $offer->euro_kdv ?? 0 ?>" id="euro_kdv" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="text" autocomplete="off" class="form-control text-center" name="dolar_kdv" readonly value="<?php echo $offer->dolar_kdv ?? 0 ?>" id="dolar_kdv" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="text" autocomplete="off" class="form-control text-center" name="tl_kdv" readonly value="<?php echo $offer->tl_kdv ?? 0 ?>" id="tl_kdv" style="border-radius: 8px;">
                                </td>
                            </tr>
                            <!-- **********************KDV *****************************-->

                            <!-- *************************KDVLİ TOPLAM *****************************-->
                            <tr>
                                <td style="font-weight: 600; color: #475569;">
                                    KDV'li Toplam
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control text-center" name="euro_kdvli_toplam" value="<?php echo $offer->euro_kdvli_toplam ?? 0 ?>" id="euro_kdvli_toplam" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control text-center" name="dolar_kdvli_toplam" value="<?php echo $offer->dolar_kdvli_toplam ?? 0 ?>" id="dolar_kdvli_toplam" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input type="text" readonly class="form-control text-center" name="tl_kdvli_toplam" value="<?php echo $offer->tl_kdvli_toplam ?? 0 ?>" id="tl_kdvli_toplam" style="border-radius: 8px;">
                                </td>
                            </tr>
                            <!-- **********************KDVLİ TOPLAM *****************************-->

                            <!-- *************************KUR BİLGİLERİ *****************************-->
                            <tr>
                                <td style="font-weight: 600; color: #475569;">
                                    <div class="d-flex align-items-center">
                                        <label class="mr-2 mb-0">Kur</label>
                                        <?php KurTuru('currency', $offer->currency ?? "Döviz Alış") ?>
                                    </div>
                                </td>
                                <td>
                                    <input id="cur-Euro" name="cur-Euro" value="<?php echo $offer->curEuro ?? 0 ?>" readonly class="form-control text-center" type="text" style="border-radius: 8px;">
                                </td>
                                <td>
                                    <input id="cur-Dollar" name="cur-Dollar" value="<?php echo $offer->curDollar ?? 0 ?>" readonly class="form-control text-center" type="text" style="border-radius: 8px;">
                                </td>
                                <td></td>
                            </tr>
                            <!-- **********************KUR BİLGİLERİ *****************************-->

                            <!-- *************************TOPLAM TL KARŞILIK *****************************-->
                            <tr>
                                <td style="font-weight: 700; color: #1e3a5f;">
                                    Toplam Tutar
                                </td>
                                <td colspan="3">
                                    <input type="text" readonly class="form-control text-center font-weight-bold" name="tl_toplam_karsilik" value="<?php echo tlFormat($offer->tl_toplam_karsilik ?? 0) ?? 0 ?>" id="tl_toplam_karsilik" style="border-radius: 8px; font-size: 16px; background-color: #f8fafc; color: #1e3a5f;">
                                </td>
                            </tr>
                            <!-- *************************TOPLAM TL KARŞILIK *****************************-->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SİSTEM BİLGİLERİ CARD -->
        <div class="form-card animate-fade-in mt-4">
            <div class="form-card-header">
                <div class="card-icon">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div>
                    <h5>Sistem Bilgileri</h5>
                    <p>Kayıt geçmişi ve güncelleyen kullanıcı detayları.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="text-muted mr-3" style="width: 150px; font-weight: 500;">Hazırlayan:</span>
                        <span class="text-dark font-weight-bold"><?php echo getUsername($offer->creativer ?? sesset("id")); ?></span>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="text-muted mr-3" style="width: 150px; font-weight: 500;">Oluşturma Tarihi:</span>
                        <span class="text-dark"><?php echo date_tr($offer->created_at ?? ''); ?></span>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="text-muted mr-3" style="width: 150px; font-weight: 500;">Güncelleyen:</span>
                        <span class="text-dark font-weight-bold"><?php echo getUserName($offer->updater ?? 0); ?></span>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="text-muted mr-3" style="width: 150px; font-weight: 500;">Güncelleme Tarihi:</span>
                        <span class="text-dark"><?php echo ($offer->updated_at ?? ''); ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- Close .offer-manage-wrapper -->

    <aside id="offerTotalsDrawer" class="offer-totals-drawer" aria-label="Teklif toplamları">
        <button type="button" id="offerTotalsToggle" class="offer-totals-toggle" aria-controls="offerTotalsDrawer" aria-expanded="false">
            <i class="fa fa-chevron-left" aria-hidden="true"></i>
            <span>Toplamlar</span>
        </button>
        <div class="offer-totals-panel">
            <div class="offer-totals-head">
                <div>
                    <h5>Teklif Özeti</h5>
                    <p>Değerler anlık güncellenir</p>
                </div>
                <i class="fa fa-calculator" aria-hidden="true"></i>
            </div>
            <div class="offer-totals-body">
                <div class="offer-total-row"><span>Euro toplam</span><strong id="drawerEuroTotal">0,00 €</strong></div>
                <div class="offer-total-row"><span>Dolar toplam</span><strong id="drawerDollarTotal">0,00 $</strong></div>
                <div class="offer-total-row"><span>TL toplam</span><strong id="drawerTryTotal">0,00 ₺</strong></div>
                <div class="offer-total-row"><span>KDV</span><strong id="drawerVatRate">%0</strong></div>
                <div class="offer-grand-total">
                    <span>Genel Toplam</span>
                    <strong id="drawerGrandTotal">0,00 ₺</strong>
                </div>
            </div>
        </div>
    </aside>
    <div id="offerTotalsBackdrop" class="offer-totals-backdrop" aria-hidden="true"></div>

</form>

<!-- Modallar (HTML standartlarına uygun şekilde form dışında tanımlanır) -->
<?php include_once __DIR__ . '/offer-modal.php'; ?>
<?php include_once __DIR__ . '/offer-quick-customer-modal.php'; ?>
<?php include_once __DIR__ . '/offer-multi-product-modal.php'; ?>

<!--buradan başlıyor-->
<script src="include/js/offer.js"></script>
<script src="pages/1/offers/offer.js?v=<?php echo filemtime("pages/1/offers/offer.js"); ?>"></script>

<script>
$(document).ready(function() {
    updateAltToplam();

    var $totalsDrawer = $('#offerTotalsDrawer');
    var $totalsToggle = $('#offerTotalsToggle');

    function setTotalsDrawer(open) {
        $totalsDrawer.toggleClass('is-open', open);
        $totalsToggle.attr('aria-expanded', open ? 'true' : 'false');
    }

    $totalsToggle.on('click', function() {
        setTotalsDrawer(!$totalsDrawer.hasClass('is-open'));
    });

    $('#offerTotalsBackdrop').on('click', function() {
        setTotalsDrawer(false);
    });

    $(document).on('keydown', function(event) {
        if (event.key === 'Escape' && $totalsDrawer.hasClass('is-open')) {
            setTotalsDrawer(false);
            $totalsToggle.trigger('focus');
        }
    });

    // -------------------------------------------------------------
    // 1. Dinamik AJAX Firma Seçimi (Ultra Hızlı, 60 FPS, E-Posta Stili)
    // -------------------------------------------------------------
    function formatCustomerResult(item) {
        if (item.loading) {
            return item.text;
        }
        if (!item.id) {
            return item.text;
        }

        var company = item.company || item.text || '';
        var yetkili = item.yetkili || '';
        var gsm = item.gsm || '';
        var email = item.email || '';
        var city = item.city || '';
        var ilce = item.ilce || '';

        var subParts = [];
        if (yetkili && yetkili !== '.' && yetkili !== '') {
            subParts.push('<i class="fa fa-user mr-1"></i>' + $('<div>').text(yetkili).html());
        }
        if (gsm && gsm !== '.' && gsm !== '') {
            subParts.push('<i class="fa fa-phone mr-1"></i>' + $('<div>').text(gsm).html());
        }
        if (email && email !== '.' && email !== '') {
            subParts.push('<i class="fa fa-envelope mr-1"></i>' + $('<div>').text(email).html());
        }
        if (city && city !== '') {
            var cityText = city + (ilce ? ' / ' + ilce : '');
            subParts.push('<i class="fa fa-map-marker mr-1"></i>' + $('<div>').text(cityText).html());
        }
        var subHtml = subParts.length > 0 ? subParts.join(' &bull; ') : '<span style="opacity: 0.7;">Ek iletişim bilgisi bulunmuyor</span>';

        return $(
            '<div class="select2-customer-option">' +
                '<div class="customer-title"><i class="fa fa-building"></i> ' + $('<div>').text(company).html() + '</div>' +
                '<div class="customer-sub">' + subHtml + '</div>' +
            '</div>'
        );
    }

    function formatCustomerSelection(item) {
        if (!item.id) {
            return item.text || 'Firma adı veya yetkili yazarak arayın...';
        }
        var company = item.company || (item.element ? $(item.element).data('company') : '') || item.text;
        return company;
    }

    var $customerSelect = $('#customers');
    if ($customerSelect.length && $.fn.select2) {
        if ($customerSelect.data('select2')) {
            $customerSelect.select2('destroy');
        }

        $customerSelect.select2({
            placeholder: 'Firma adı veya yetkili yazarak arayın...',
            allowClear: false,
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: 'api/search_customers.php',
                dataType: 'json',
                delay: 100,
                data: function(params) {
                    return {
                        q: params.term || '',
                        limit: 30
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            },
            templateResult: formatCustomerResult,
            templateSelection: formatCustomerSelection,
            language: {
                searching: function() { return "Aranıyor..."; },
                noResults: function() { return "Eşleşen firma bulunamadı"; },
                loadingMore: function() { return "Daha fazla yükleniyor..."; },
                inputTooShort: function() { return "Aramak için yazmaya başlayın..."; }
            }
        });

        // Tıklandığında anında ilk 30 firmayı getir
        $customerSelect.on('select2:open', function() {
            var s2 = $customerSelect.data('select2');
            if (s2 && s2.dataAdapter) {
                s2.dataAdapter.query({ term: '' }, function(data) {
                    s2.results.append(data);
                });
            }
        });

        // Seçim yapıldığında yetkili ve vadeyi otomatik doldur / yoksa temizle
        $customerSelect.on('select2:select', function(e) {
            var data = e.params ? e.params.data : null;
            if (data) {
                var author = data.yetkili || $(this).find('option:selected').data('author') || '';
                var payPeriod = data.odemevadesi || data.payperiod || $(this).find('option:selected').data('payperiod') || '';
                if (author && author !== '.' && author !== '-') {
                    $('#compAuths').val(author);
                } else {
                    $('#compAuths').val('');
                }
                if (payPeriod && payPeriod !== '') {
                    $('#payPeriod').val(payPeriod);
                } else {
                    $('#payPeriod').val('');
                }
            }
        });

        $customerSelect.on('select2:clear select2:unselect', function() {
            $('#compAuths').val('');
            $('#payPeriod').val('');
        });
    }

    // Diğer normal select2 elemanlarını başlat (Modal ve müşteri hariç)
    if ($.fn.select2) {
        $('select.select2:not(#customers):not(.select2-customer-select):not(.modal-select2):not(.modal-select2-tags)').each(function () {
            var $this = $(this);
            $this.select2({
                placeholder: $this.attr('placeholder') || $this.data('placeholder') || 'Seçim Yapınız',
                allowClear: !$this.prop('required') && !$this.prop('multiple'),
                width: '100%'
            });
        });
    }

    // -------------------------------------------------------------
    // 2. Hızlı Firma Ekleme Modalı İşlemleri
    // -------------------------------------------------------------
    $(document).on('click', '#btnOpenQuickCustomerModal', function(e) {
        e.preventDefault();
        var formEl = document.getElementById('quickCustomerForm');
        if (formEl) {
            formEl.reset();
        }

        // İl seçenekleri eksikse dinamik yükle (güvenlik ağı)
        var $ilSelect = $('#quick_il');
        if ($ilSelect.find('option').length <= 1) {
            $.getJSON('src/scripts/il-bolge.json', function(provinces) {
                $.each(provinces, function(i, p) {
                    $ilSelect.append($('<option>', {
                        value: p.il,
                        text: p.il
                    }));
                });
                $ilSelect.val('').trigger('change');
            });
        } else {
            $ilSelect.val('').trigger('change');
        }

        $('#quick_ilce').empty().append('<option value="">İlçe Seçiniz</option>').trigger('change');
        $('#categoryName, #quick_categoryName').val('').trigger('change');
        $('#quick_vade').val('').trigger('change');
        $('#quickAddCustomerModal').modal('show');
    });

    $('#quickAddCustomerModal').on('shown.bs.modal', function() {
        if ($.fn.select2) {
            $('#quickAddCustomerModal .modal-select2').select2({
                dropdownParent: $('#quickAddCustomerModal'),
                width: '100%'
            });
            $('#quickAddCustomerModal .modal-select2-tags').select2({
                dropdownParent: $('#quickAddCustomerModal'),
                width: '100%',
                tags: true
            });
        }
        $('#quick_company').focus();
    });

    $(document).on('change', '#quick_il', function() {
        var selectedIl = $(this).val();
        var $ilceSelect = $('#quick_ilce');
        $ilceSelect.empty().append('<option value="">İlçe Seçiniz</option>');

        if (selectedIl) {
            $.getJSON('src/scripts/il-ilce.json', function(data) {
                $.each(data, function(i, item) {
                    if (item.il === selectedIl) {
                        $ilceSelect.append($('<option>', {
                            value: item.ilce,
                            text: item.ilce
                        }));
                    }
                });
                if ($.fn.select2 && $ilceSelect.hasClass('select2-hidden-accessible')) {
                    $ilceSelect.trigger('change');
                }
            });
        } else {
            if ($.fn.select2 && $ilceSelect.hasClass('select2-hidden-accessible')) {
                $ilceSelect.trigger('change');
            }
        }
    });

    $(document).on('click', '#btnSaveQuickCustomer', function() {
        var $btn = $(this);
        var $form = $('#quickCustomerForm');
        var company = $.trim($('#quick_company').val());
        var cgsm = $.trim($('#quick_cgsm').val());
        var address = $.trim($('#quick_customer_address').val());
        var il = $('#quick_il').val();

        if (!company) {
            swal.fire({
                title: 'Eksik Bilgi',
                text: 'Lütfen firma adını giriniz.',
                icon: 'warning'
            });
            $('#quick_company').focus();
            return;
        }

        if (!cgsm) {
            swal.fire({
                title: 'Eksik Bilgi',
                text: 'Lütfen telefon (GSM) bilgisini giriniz.',
                icon: 'warning'
            });
            $('#quick_cgsm').focus();
            return;
        }

        if (!il) {
            swal.fire({
                title: 'Eksik Bilgi',
                text: 'Lütfen il seçimi yapınız.',
                icon: 'warning'
            });
            $('#quick_il').focus();
            return;
        }

        if (!address) {
            swal.fire({
                title: 'Eksik Bilgi',
                text: 'Lütfen açık adres bilgisini giriniz.',
                icon: 'warning'
            });
            $('#quick_customer_address').focus();
            return;
        }

        var formData = new FormData($form[0]);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Kaydediliyor...');

        fetch('App/api/customer.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            $btn.prop('disabled', false).html('<i class="fa fa-check"></i> <span>Firmayı Kaydet & Seç</span>');
            if (data.status === 'success') {
                var newId = data.id;
                var newCompany = data.company || company;
                var newYetkili = data.yetkili || $('#quick_yetkili').val() || '';
                var newVade = data.vade || $('#quick_vade').val() || '';
                var newEmail = data.email || $('#quick_cemail').val() || '';
                var newGsm = data.gsm || cgsm;
                var newCity = data.city || il;

                // Select2'ye yeni option ekle ve seç
                var newOption = new Option(newCompany, newId, true, true);
                $(newOption).attr({
                    'data-company': newCompany,
                    'data-author': newYetkili,
                    'data-payperiod': newVade,
                    'data-email': newEmail,
                    'data-gsm': newGsm,
                    'data-city': newCity
                });
                $('#customers').append(newOption).val(newId).trigger('change');

                // Firma yetkilisi ve vade alanlarını doldur
                if (newYetkili) {
                    $('#compAuths').val(newYetkili);
                }
                if (newVade) {
                    $('#payPeriod').val(newVade);
                }

                $('#quickAddCustomerModal').modal('hide');

                swal.fire({
                    title: 'Başarılı!',
                    text: 'Firma oluşturuldu ve teklife seçildi.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                swal.fire({
                    title: 'Hata!',
                    text: data.message || 'Firma kaydedilirken bir hata oluştu.',
                    icon: 'error'
                });
            }
        })
        .catch(function(err) {
            $btn.prop('disabled', false).html('<i class="fa fa-check"></i> <span>Firmayı Kaydet & Seç</span>');
            swal.fire({
                title: 'Hata!',
                text: 'Sunucu ile iletişim kurulurken bir sorun oluştu.',
                icon: 'error'
            });
            console.error('Quick customer error:', err);
        });
    });

    if ($.fn.selectpicker) {
        $('.selectpicker').selectpicker();
    }

    function syncOfferStatusControl(status) {
        var normalizedStatus = String(status || '1');

        $('.offer-status-control .status-option').each(function() {
            var isActive = String($(this).data('status')) === normalizedStatus;
            $(this).toggleClass('active', isActive).attr('aria-pressed', isActive ? 'true' : 'false');
        });

        if (normalizedStatus === '3') {
            $('#reject_reason_wrapper').slideDown(200);
        } else {
            $('#reject_reason_wrapper').slideUp(200);
        }
    }

    $('.offer-status-control .status-option').on('click', function() {
        var status = String($(this).data('status'));
        $('#offerstatu').val(status).trigger('change');

        if (typeof $('#offerstatu').selectpicker === 'function') {
            $('#offerstatu').selectpicker('refresh');
        }

        syncOfferStatusControl(status);
    });

    $('#offerstatu').on('change', function() {
        syncOfferStatusControl($(this).val());
    });

    syncOfferStatusControl($('#offerstatu').val());

    $(document)
        .on('shown.bs.select', '.selectpicker', function() {
            $(this).closest('.form-field').addClass('select-open');
        })
        .on('hidden.bs.select', '.selectpicker', function() {
            $(this).closest('.form-field').removeClass('select-open');
        });

    if (typeof $.fn.wysihtml5 !== 'undefined') {
        $('.textarea_editor').each(function() {
            if (!$(this).data('wysihtml5')) {
                $(this).wysihtml5();
            }
            $(this).hide();
        });
    }
});

function syncOfferTotalsDrawer() {
    function value(selector) {
        return $(selector).val() || '0.00';
    }

    function localized(rawValue) {
        var numericValue = Number(rawValue);
        return Number.isFinite(numericValue)
            ? numericValue.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : '0,00';
    }

    $('#drawerEuroTotal').text(localized(value('#euro_kdvli_toplam')) + ' €');
    $('#drawerDollarTotal').text(localized(value('#dolar_kdvli_toplam')) + ' $');
    $('#drawerTryTotal').text(localized(value('#tl_kdvli_toplam')) + ' ₺');
    $('#drawerVatRate').text('%' + (value('#Kdv') || '0'));
    $('#drawerGrandTotal').text(value('#tl_toplam_karsilik') + ' ₺');
}

$(function() {
    var el = document.getElementById('sortable');
    if (el && typeof Sortable !== 'undefined') {
        var sortable = Sortable.create(el, {
            onUpdate: function(evt) {
                $("#kalem_ekle tbody tr").each(function(index) {
                    $(this).find("input[name='satirno[]']").val(index + 1);
                });
            }
        });
    }
});
</script>
