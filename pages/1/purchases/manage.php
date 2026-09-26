<?php


use App\Helper\customer;
use App\Helper\Financial;
use App\Helper\Helper;
use App\Model\PurchaseModel;

$Purchases = new PurchaseModel();

global $pdat; // IDE desteği ve global erişim için

//güncelleme işlem ise id alınır yoksa 0 atanır.
$id = isset($_GET["id"]) ? $_GET["id"] : 0;

$talep_id = isset($_GET["talep_id"]) ? $_GET["talep_id"] : 0;

//Satın alma talebinden mi geldi yoksa yeni bir satın alma işlemi mi yapılacak
$demand = isset($_GET["demand"]) ? true : false;

if ($demand) {
    $id = $talep_id;
}

//Güncelleme işlemi ise satın alma bilgilerini getirir.
$purchase = $Purchases->find($id);

if (!$purchase) {
    $purchase = new stdClass();
}


//Satın almanın ürünlerini getirir.
$purchaseItems = $Purchases->getPurchaseItems($id);


//eğer satın alma talebinden geldiyse veya yeni bir satın alma işlemi ise
//yeni bir sipariş numarası oluşturulur.
if ($demand == true || $id == 0) {
    $getNumber = setNumber("purchase");
    $siparisNo = "SA000" . $getNumber;
} else {
    $siparisNo = $purchase->siparisNo ?? '';
}

?>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
<form enctype="multipart/form-data" method="POST" id="myForm">
    <input type="hidden" name="satinAlmaTalebiniKapat" value="<?php echo $demand ? 1 : 0; ?>">
    <input type="hidden" name="talep_id" value="<?php echo $talep_id; ?>">
    
    <div class="purchase-manage-wrapper">
        <!-- Header Card -->
        <div class="purchase-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $id != 0 ? 'Siparişi Düzenle' : 'Yeni Sipariş Girişi'; ?></h4>
                        <span class="purchase-number-badge">
                            <i class="fa fa-tag"></i> Sipariş No: <?php echo $siparisNo; ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=purchases" class="btn-header btn-header-list">
                        <i class="fa fa-list"></i> Listeye Dön
                    </a>
                    <button type="button" id="saveButton" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>

        <style>
            /* Sipariş detayları: sayfaya özel, dengeli iki sütunlu form düzeni */
            .purchase-manage-wrapper {
                width: 100%;
                max-width: none;
                margin-right: 0;
                margin-left: 0;
            }
            .purchase-manage-wrapper > .purchase-header-card,
            .purchase-manage-wrapper > .form-card {
                width: 100%;
            }
            .purchase-products-card,
            .purchase-totals-card {
                padding: 0 !important;
                overflow: hidden !important;
            }
            .purchase-products-card .form-card-header,
            .purchase-totals-card .form-card-header {
                margin: 0 !important;
                padding: 22px 28px 18px !important;
                border-bottom: 1px solid #e8eef5 !important;
                border-radius: 16px 16px 0 0;
                background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
            }
            .purchase-products-card .header-left-inner,
            .purchase-totals-card .header-left-inner {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .purchase-products-body,
            .purchase-totals-body {
                padding: 20px 24px 24px;
            }
            .purchase-summary-row {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 15px;
                width: 100%;
                margin: 0 0 24px !important;
            }
            .purchase-kpi-card {
                display: flex;
                min-width: 0;
                min-height: 98px;
                align-items: center;
                gap: 14px;
                padding: 16px;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: #f8fafc;
                transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
            }
            .purchase-kpi-card:hover {
                border-color: #cbd5e1;
                box-shadow: 0 4px 12px rgba(15, 23, 42, .05);
                transform: translateY(-2px);
            }
            .purchase-kpi-icon {
                display: flex;
                width: 48px;
                height: 48px;
                flex: 0 0 48px;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                font-size: 20px;
            }
            .purchase-kpi-icon.is-blue { background: #e0f2fe; color: #0284c7; }
            .purchase-kpi-icon.is-amber { background: #fef3c7; color: #d97706; }
            .purchase-kpi-icon.is-green { background: #dcfce7; color: #16a34a; }
            .purchase-kpi-icon.is-purple { background: #f3e8ff; color: #9333ea; }
            .purchase-kpi-info { min-width: 0; flex: 1; }
            .purchase-kpi-title {
                margin-bottom: 4px;
                color: #64748b;
                font-size: 12px;
                font-weight: 600;
                line-height: 1.2;
                text-transform: uppercase;
            }
            .purchase-kpi-value {
                overflow: hidden;
                margin: 0;
                color: #1e293b;
                font-size: 18px;
                font-weight: 700;
                line-height: 1.2;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .purchase-kpi-sub {
                overflow: hidden;
                margin-top: 4px;
                color: #94a3b8;
                font-size: 11px;
                line-height: 1.25;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .purchase-details-card { padding: 0; overflow: visible; }
            .purchase-details-card .form-card-header {
                margin: 0;
                padding: 22px 28px 18px;
                border-bottom: 1px solid #e8eef5;
                border-radius: 16px 16px 0 0;
                background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
            }
            .purchase-details-card .header-left-inner {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .purchase-details-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 20px 34px;
                padding: 24px 28px 28px;
            }
            .purchase-details-column {
                display: flex;
                min-width: 0;
                flex-direction: column;
                gap: 15px;
            }
            .purchase-details-column > .form-group {
                display: grid;
                grid-template-columns: 190px minmax(0, 1fr);
                align-items: center;
                min-height: 42px;
                margin: 0;
            }
            .purchase-details-column > .purchase-notes-field { align-items: start; }
            .purchase-details-column > .form-group > label,
            .purchase-details-column > .form-group > div {
                width: auto;
                max-width: none;
                margin: 0;
                padding: 0;
                flex: none;
            }
            .purchase-details-column > .form-group > label {
                padding-right: 18px;
                color: #334155;
                font-size: 13px !important;
                line-height: 1.35;
            }
            .purchase-details-column .form-control,
            .purchase-details-column .bootstrap-select > .dropdown-toggle {
                min-height: 42px !important;
                border-color: #cbd5e1 !important;
                border-radius: 8px !important;
                background-color: #fff;
                font-size: 13.5px !important;
                box-shadow: none !important;
            }
            .purchase-details-column .form-control:focus,
            .purchase-details-column .bootstrap-select.show > .dropdown-toggle {
                border-color: var(--focus-color, var(--theme-primary, #2563eb)) !important;
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--focus-color, var(--theme-primary, #2563eb)) 16%, transparent) !important;
            }
            .purchase-details-column .input-group-text {
                min-width: 40px;
                min-height: 42px;
                justify-content: center;
            }
            .purchase-details-column .input-group {
                overflow: hidden;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                background: #fff;
                transition: border-color .2s ease, box-shadow .2s ease;
            }
            .purchase-details-column .input-group:focus-within {
                border-color: var(--focus-color, var(--theme-primary, #2563eb));
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--focus-color, var(--theme-primary, #2563eb)) 16%, transparent);
            }
            .purchase-details-column .input-group > .input-group-prepend .input-group-text,
            .purchase-details-column .input-group > .form-control {
                border: 0 !important;
                border-radius: 0 !important;
            }
            .purchase-details-column .input-group > .form-control:focus {
                box-shadow: none !important;
            }
            .purchase-details-column > .form-group > div > .row { margin-right: -5px; margin-left: -5px; }
            .purchase-details-column > .form-group > div > .row > [class*="col-"] { padding-right: 5px !important; padding-left: 5px !important; }
            .purchase-details-column textarea.form-control {
                min-height: 118px !important;
                padding: 12px 14px;
                resize: vertical;
            }
            .purchase-number-value {
                display: inline-flex;
                align-items: center;
                min-height: 36px;
                padding: 7px 14px;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                background: #f1f5f9;
                color: #334155;
                font-size: 13px;
                font-weight: 700;
                letter-spacing: .02em;
            }
            .purchase-customer-control {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 42px;
                align-items: center;
                gap: 8px;
            }
            .purchase-customer-control > .w-100 {
                display: block;
                width: 100% !important;
                min-width: 0;
                margin: 0 !important;
            }
            .purchase-customer-control .bootstrap-select,
            .purchase-customer-control .bootstrap-select.form-control {
                display: block !important;
                width: 100% !important;
                min-width: 0 !important;
                height: 42px !important;
            }
            .purchase-customer-control .bootstrap-select > .dropdown-toggle {
                display: flex !important;
                width: 100% !important;
                height: 42px !important;
                align-items: center;
                justify-content: space-between;
            }
            .purchase-customer-control .bootstrap-select .filter-option {
                display: flex;
                min-width: 0;
                align-items: center;
            }
            .purchase-customer-control > .btn { width: 42px !important; height: 42px !important; }

            .dark-mode .purchase-details-card .form-card-header {
                border-color: #334155;
                background: linear-gradient(135deg, #111827 0%, #172033 100%);
            }
            .dark-mode .purchase-products-card .form-card-header,
            .dark-mode .purchase-totals-card .form-card-header {
                border-color: #334155 !important;
                background: linear-gradient(135deg, #111827 0%, #172033 100%);
            }
            .dark-mode .purchase-details-column > .form-group > label { color: #cbd5e1; }
            .dark-mode .purchase-number-value { border-color: #475569; background: #1e293b; color: #e2e8f0; }
            .dark-mode .purchase-details-column .form-control,
            .dark-mode .purchase-details-column .input-group-text,
            .dark-mode .purchase-details-column .bootstrap-select > .dropdown-toggle {
                border-color: #475569 !important;
                background-color: #111827 !important;
                color: #e2e8f0 !important;
            }
            .dark-mode .purchase-details-column .input-group {
                border-color: #475569;
                background: #111827;
            }
            .dark-mode .purchase-details-column .input-group:focus-within {
                border-color: var(--focus-color, var(--theme-primary, #6366f1));
            }
            .dark-mode .purchase-details-column .form-control[readonly] {
                background-color: #1e293b !important;
                color: #94a3b8 !important;
            }
            .dark-mode .purchase-kpi-card { border-color: #334155; background: #1e293b; }
            .dark-mode .purchase-kpi-card:hover { border-color: #475569; box-shadow: 0 4px 14px rgba(0, 0, 0, .2); }
            .dark-mode .purchase-kpi-title { color: #94a3b8; }
            .dark-mode .purchase-kpi-value { color: #f1f5f9; }
            .dark-mode .purchase-kpi-sub { color: #64748b; }

            @media (max-width: 1199.98px) {
                .purchase-details-grid { grid-template-columns: 1fr; }
                .purchase-summary-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (max-width: 575.98px) {
                .purchase-details-card .form-card-header,
                .purchase-details-grid { padding: 18px; }
                .purchase-products-card .form-card-header,
                .purchase-totals-card .form-card-header,
                .purchase-products-body,
                .purchase-totals-body { padding: 16px !important; }
                .purchase-details-column > .form-group { grid-template-columns: 1fr; gap: 7px; }
                .purchase-details-column > .form-group > label { padding-right: 0; }
                .purchase-details-column > .form-group > div > .row > [class*="col-"] { margin-bottom: 8px; }
                .purchase-details-column > .form-group > div > .row > [class*="col-"]:last-child { margin-bottom: 0; }
                .purchase-summary-row { grid-template-columns: 1fr; gap: 10px; }
            }
        </style>

        <!-- Form Card -->
        <div class="form-card purchase-details-card animate-fade-in">
            <div class="form-card-header d-flex justify-content-between align-items-center">
                <div class="header-left-inner">
                    <div class="card-icon">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <div>
                        <h5>Sipariş Detayları</h5>
                        <p class="mb-0 text-muted font-12" style="margin-top: 2px;">
                            Sipariş genel bilgilerini ve teslimat tercihlerini bu alandan yönetebilirsiniz.
                        </p>
                    </div>
                </div>
            </div>

        <div class="purchase-details-grid">
            <!-- COLUMN ONE -->
            <div class="purchase-details-column">
                <div class="form-group row align-items-center">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        <span class="text-danger">*</span> Sipariş Numarası
                    </label>
                    <div class="col-md-8">
                        <span class="purchase-number-value">
                            <?php echo $siparisNo; ?>
                        </span>
                        <input type="hidden" name="siparisNo" id="siparisNo" value="<?php echo $siparisNo; ?>">
                        <input type="hidden" name="id" id="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="demand" id="demand" value="<?php echo $demand; ?>">
                    </div>
                </div>

                <!-- Firma Bilgileri -->
                <div class="form-group row align-items-center">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        <span class="text-danger">*</span> Firma
                    </label>
                    <div class="col-md-8 purchase-customer-control">
                        <div class="w-100 mr-2">
                            <?php echo customer::getCustomerSelect('customers', $purchase->companyID ?? 0); ?>
                        </div>
                        <a href="index.php?p=new-customer" target="_blank"
                            class="btn btn-outline-primary d-flex align-items-center justify-content-center" style="height: 38px; width: 38px; border-radius: 8px; padding: 0;"
                            data-tooltip="Yeni Firma Ekle"><i class="fa fa-plus"></i>
                        </a>
                    </div>
                </div>
                <!-- Firma Bilgileri -->

                <!-- Termin Tarihi -->
                <div class="form-group row align-items-center">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        <span class="text-danger">*</span> Termin Tarihi
                    </label>
                    <div class="col-md-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1;"><i class="fa fa-calendar-o text-muted"></i></span>
                            </div>
                            <input required class="form-control date-picker border-left-0" style="border-radius: 0 8px 8px 0; border-color: #cbd5e1;" type="text"
                                value="<?php echo $purchase->deadline ?? date("d-m-Y") ?>" name="deadline"
                                autocomplete="off" placeholder="gg-aa-yyyy">
                        </div>
                    </div>
                </div>
                <!-- Termin Tarihi -->

                <!-- Ödeme Vadesi -->
                <div class="form-group row align-items-center">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        <span class="text-danger">*</span> Ödeme Vadesi
                    </label>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 pr-1">
                                <input type="text" required id="payPeriod" name="vadeGun" class="form-control"
                                    autocomplete="off" placeholder="Gün giriniz" style="border-radius: 8px; border-color: #cbd5e1;"
                                    value="<?php echo $purchase->vadeGun ?? date("d-m-Y") ?>">
                            </div>
                            <div class="col-md-6 pl-1">
                                <input type="text" readonly id="payment_date" name="payment_date" class="form-control bg-light" style="border-radius: 8px; border-color: #cbd5e1;"
                                    value="<?php echo $purchase->payment_date ?? date("d-m-Y") ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ödeme Vadesi -->

                <!-- Açıklama -->
                <div class="form-group row purchase-notes-field">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        Açıklama 1
                    </label>
                    <div class="col-md-8">
                        <textarea name="description1" placeholder="Sipariş formunda görünecek açıklama giriniz"
                            class="form-control" style="border-radius: 8px; border-color: #cbd5e1; min-height: 80px;" type="text"><?php echo $purchase->description1 ?? '' ?></textarea>
                    </div>
                </div>
                <!-- Açıklama -->

            </div>
            <!-- COLUMN ONE -->

            <!-- COLUMN TWO -->
            <div class="purchase-details-column">
                <!-- Satın Alma Durumu -->
                <div class="form-group row align-items-center">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        Durumu
                    </label>
                    <div class="col-md-8">
                        <?php
                        $state = $purchase->state ?? 0;
                        echo Helper::selectState("state", $state);
                        ?>
                    </div>
                </div>
                <!-- Satın Alma Durumu -->
                
                <!-- Fatura Tarihi -->
                <div class="form-group row align-items-center">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        Fatura Tarihi/Numarası
                    </label>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 pr-1">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1;"><i class="fa fa-calendar-o text-muted"></i></span>
                                    </div>
                                    <input type="text" class="form-control date-picker border-left-0" style="border-radius: 0 8px 8px 0; border-color: #cbd5e1;" name="invoice_date"
                                        value="<?php echo $purchase->invoice_date ?? ''; ?>" placeholder="Fatura Tarihi">
                                </div>
                            </div>
                            <div class="col-md-6 pl-1">
                                <input type="text" class="form-control" style="border-radius: 8px; border-color: #cbd5e1;" name="invoice_number"
                                    value="<?php echo $purchase->invoice_number ?? ''; ?>" placeholder="Fatura Numarası">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Fatura Tarihi -->

                <!-- Para Birimi -->
                <div class="form-group row align-items-center">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        Kur Türü
                    </label>
                    <div class="col-md-8">
                        <?php KurTuru('cur_type', $purchase->currency ?? '') ?>
                    </div>
                </div>
                <!-- Para Birimi -->

                <!-- Kur Bilgileri -->
                <div class="form-group row align-items-center">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        Dolar / Euro
                    </label>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 pr-1">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-right-0 text-muted" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1; font-weight: 600;">$</span>
                                    </div>
                                    <input type="text" readonly class="form-control bg-light border-left-0" style="border-radius: 0 8px 8px 0; border-color: #cbd5e1;" id="cur-Dollar" name="curDollar">
                                </div>
                            </div>
                            <div class="col-md-6 pl-1">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-right-0 text-muted" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1; font-weight: 600;">€</span>
                                    </div>
                                    <input type="text" readonly class="form-control bg-light border-left-0" style="border-radius: 0 8px 8px 0; border-color: #cbd5e1;" id="cur-Euro" name="curEuro">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Kur Bilgileri -->

                <!-- Açıklama -->
                <div class="form-group row purchase-notes-field">
                    <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                        Açıklama 2
                    </label>
                    <div class="col-md-8">
                        <textarea name="description2" class="form-control" style="border-radius: 8px; border-color: #cbd5e1; min-height: 80px;"
                            type="text" placeholder="İç açıklama giriniz"><?php echo $purchase->description2 ?? ''; ?></textarea>
                    </div>
                </div>
                <!-- Açıklama -->

            </div>
            <!-- COLUMN TWO -->
        </div>
    </div>


    <div class="form-card purchase-products-card animate-fade-in mt-4">
        <div class="form-card-header">
            <div class="header-left-inner">
                <div class="card-icon">
                    <i class="fa fa-shopping-basket"></i>
                </div>
                <div>
                    <h5>Ürün Bilgileri</h5>
                    <p>Sipariş kalemlerini, miktarları ve fiyat bilgilerini bu alandan yönetebilirsiniz.</p>
                </div>
            </div>
        </div>

        <div class="purchase-products-body">

        <?php

        $alisToplam = $purchase->TLTotal ?? "0.00"; // Alış toplamı
        $iskontoToplam = $purchase->iskonto ?? "0.00"; // İskonto toplamı
        $kdv = $purchase->Kdv ?? "20"; // KDV oranı
        $kdvToplam = $purchase->altToplam ?? "0.00"; // KDV dahil toplam tutar

        ?>
        <div class="row purchase-summary-row">
            <div class="purchase-kpi-card">
                <div class="purchase-kpi-icon is-blue" aria-hidden="true">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div class="purchase-kpi-info">
                    <div class="purchase-kpi-title">Tutar TL</div>
                    <div class="purchase-kpi-value" id="buy-tl"><?php echo $alisToplam ?></div>
                    <div class="purchase-kpi-sub">Vergi öncesi sipariş tutarı</div>
                </div>
            </div>

            <div class="purchase-kpi-card">
                <div class="purchase-kpi-icon is-amber" aria-hidden="true">
                    <i class="fa fa-percent"></i>
                </div>
                <div class="purchase-kpi-info">
                    <div class="purchase-kpi-title">KDV Oranı (%)</div>
                    <div class="purchase-kpi-value" id="kdv-rate"><?php echo $kdv ?></div>
                    <div class="purchase-kpi-sub">Uygulanan vergi oranı</div>
                </div>
            </div>

            <div class="purchase-kpi-card">
                <div class="purchase-kpi-icon is-green" aria-hidden="true">
                    <i class="fa fa-tags"></i>
                </div>
                <div class="purchase-kpi-info">
                    <div class="purchase-kpi-title">İskonto TL</div>
                    <div class="purchase-kpi-value" id="discount"><?php echo $iskontoToplam ?></div>
                    <div class="purchase-kpi-sub">Toplam indirim tutarı</div>
                </div>
            </div>

            <div class="purchase-kpi-card">
                <div class="purchase-kpi-icon is-purple" aria-hidden="true">
                    <i class="fa fa-calculator"></i>
                </div>
                <div class="purchase-kpi-info">
                    <div class="purchase-kpi-title">KDV Dahil TL</div>
                    <div class="purchase-kpi-value" id="lblTotalTL"><?php echo $kdvToplam ?></div>
                    <div class="purchase-kpi-sub">KDV dahil genel toplam</div>
                </div>
            </div>
        </div>

        <!-- Sipariş ürünleri -->
        <div class="hack1">
            <div class="hack2">

                <table id="tProduct" class="table premium-table no-filter">
                    <thead>
                        <tr>
                            <th style="width: 35px; min-width: 35px;" class="text-center no-filter">Taşı</th>
                            <th style="width: 80px; min-width: 80px;" class="text-center no-filter">İşlem</th>
                            <th style="width: 55px; min-width: 55px;" class="text-center no-filter">Sıra</th>
                            <th style="width: 140px; min-width: 120px;" class="no-filter">Stok Kodu</th>
                            <th style="min-width: 220px;" class="no-filter">Ürün Adı</th>
                            <th style="width: 90px; min-width: 80px;" class="text-center no-filter">Miktar</th>
                            <th style="width: 110px; min-width: 100px;" class="no-filter">Birim</th>
                            <th style="width: 120px; min-width: 100px;" class="text-right no-filter">Fiyat</th>
                            <th style="width: 100px; min-width: 90px;" class="no-filter">Para Birimi</th>
                        </tr>
                    </thead>

                    <tbody id="sortable">
                        <?php
                        $i = 0;
                        //Eğer purchaseItems boş ise yeni bir satır eklenir.
                        if (empty($purchaseItems)) {
                            $purchaseItems = [new stdClass()];
                        }
                        foreach ($purchaseItems as $item) {
                            $i++;
                        ?>
                            <tr class="ui-state-default">
                                <td style="width: 35px; min-width: 35px; text-align: center; vertical-align: middle;">
                                    <span class="btn btn-sm text-muted p-0 drag-handle" style="cursor: grab;" title="Sıralamayı Değiştirmek İçin Sürükleyin">
                                        <i class="fa fa-arrows-alt"></i>
                                    </span>
                                </td>

                                <td class="app-item-action-2 text-center" style="width: 80px; min-width: 80px; vertical-align: middle; white-space: nowrap;">
                                    <div class="btn-group btn-group-sm" role="group" style="display: inline-flex;">
                                        <a type="button" class="sil btn btn-sm btn-danger text-white" title="Satırı Sil" style="padding: 4px 8px; border-radius: 6px 0 0 6px;">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-clone-row" title="Satırı Klonla (Kopyala)" style="padding: 4px 8px; border-radius: 0 6px 6px 0;">
                                            <i class="fa fa-clone"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- Sırano -->
                                <td class="app-item-number text-center" style="width: 55px; min-width: 55px; vertical-align: middle;">
                                    <input class="form-control text-center font-weight-bold" name="satirno[]" type="text" value="<?php echo $i; ?>" readonly style="background: #f8fafc; border-radius: 6px; width: 45px; margin: 0 auto;">
                                </td>

                                <!-- Stok Kodu -->
                                <td class="app-item-stock" style="width: 140px; min-width: 120px; vertical-align: middle;">
                                    <div class="product-autocomplete-wrap">
                                        <input type="text" id="stokKodu<?php echo $i; ?>"
                                            value="<?php echo htmlspecialchars($item->stokKodu ?? ''); ?>" name="stokKodu[]" class="form-control stokKodu-input"
                                            placeholder="Stok Kodu" autocomplete="off" style="border-radius: 6px; border-color: #cbd5e1;">
                                    </div>
                                </td>

                                <!-- Ürün Adı -->
                                <td class="app-item-name" style="min-width: 220px; vertical-align: middle;">
                                    <div class="product-autocomplete-wrap position-relative">
                                        <input type="text" class="urunAdi form-control urunAdi-input" name="urunAdi[]"
                                            id="urunAdi<?php echo $i; ?>" value="<?php echo htmlspecialchars($item->product ?? ''); ?>"
                                            placeholder="Ürün adı yazarak arayın veya seçin..." autocomplete="off" style="border-radius: 6px; border-color: #cbd5e1;">
                                    </div>
                                </td>

                                <!-- MİKTAR -->
                                <td class="app-item-amount text-center" style="width: 90px; min-width: 80px; vertical-align: middle;">
                                    <input type="number" step="any" min="0" autocomplete="off" required id="amount<?php echo $i; ?>" name="amount[]"
                                        value="<?php echo $item->amount ?? ''; ?>" class="Adet form-control amount-input text-center" placeholder="0" style="border-radius: 6px; border-color: #cbd5e1;">
                                </td>

                                <!-- ÖLÇÜ BİRİMLERİ -->
                                <td class="app-item-unit" style="width: 110px; min-width: 100px; vertical-align: middle;">
                                    <?php OlcuBirimleri('unit[]', $item->unit ?? '', "required", "unit" . $i) ?>
                                </td>

                                <!-- FİYAT -->
                                <td class="app-item-price" style="width: 120px; min-width: 100px; vertical-align: middle;">
                                    <input required id="price<?php echo $i; ?>" name="price[]" type="text"
                                        value="<?php echo $item->price ?? ''; ?>" class="form-control price-input text-right" autocomplete="off" placeholder="0.00" style="border-radius: 6px; border-color: #cbd5e1;">
                                </td>

                                <!-- PARA BİRİMLERİ -->
                                <td class="app-item-cur" style="width: 100px; min-width: 90px; vertical-align: middle;">
                                    <?php echo Financial::getCurrencySelect("currency[]", $item->currency ?? '', "currency" . $i) ?>
                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="9" style="padding: 10px 12px !important; background: #f8fafc; border-top: 2px solid #e2e8f0;">
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" id="addRow" class="btn btn-sm btn-primary" style="border-radius: 8px; font-weight: 600; padding: 7px 18px;">
                                        <i class="fa fa-plus-circle mr-1"></i> Yeni Satır Ekle
                                    </button>
                                    <button type="button" id="btnOpenMultiProductModal" class="btn btn-sm btn-outline-primary" style="border-radius: 8px; font-weight: 600; padding: 7px 18px;">
                                        <i class="fa fa-th-list mr-1"></i> Toplu Ürün Ekle
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <input type="hidden" id="rowNumberId" value="<?php echo $i + 1 ?>">

            </div>
        </div>
        </div>
    </div>
    <div class="form-card purchase-totals-card animate-fade-in mt-4 mb-4">
        <div class="form-card-header">
            <div class="header-left-inner">
                <div class="card-icon">
                    <i class="fa fa-calculator"></i>
                </div>
                <div>
                    <h5>Alt Toplamlar</h5>
                    <p>Döviz, iskonto ve KDV dahil sipariş toplamlarını bu alandan yönetebilirsiniz.</p>
                </div>
            </div>
        </div>
        <div class="purchase-totals-body">
        <div class="hack1">
            <div class="hack2">
                <table id="tblAltToplam" class="table premium-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Göster</th>
                            <th style="width: 120px;">Euro Toplam</th>
                            <th style="width: 120px;">Dolar Toplam</th>
                            <th style="width: 120px;">TL Toplam</th>
                            <th style="width: 120px;">İskonto Toplam</th>
                            <th style="width: 100px;">Kdv (%)</th>
                            <th style="width: 130px;">Toplam Tutar (TL)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="sub-item-view" style="vertical-align: middle;">
                                <span class="badge badge-primary px-3 py-2" style="border-radius: 6px;">Göster</span>
                            </td>
                            <td style="vertical-align: middle;">
                                <input type="text" class="form-control text-center" name="EuroAlttoplam" id="EuroAlttoplam"
                                    value="<?php echo $purchase->EuroTotal ?? '0.00' ?>" style="border-radius: 6px; border-color: #cbd5e1;">
                            </td>
                            <td style="vertical-align: middle;">
                                <input type="text" class="form-control text-center" name="DolarAlttoplam" id="DolarAlttoplam"
                                    value="<?php echo $purchase->DolarTotal ?? '0.00' ?>" style="border-radius: 6px; border-color: #cbd5e1;">
                            </td>
                            <td style="vertical-align: middle;">
                                <input type="text" class="form-control text-center" name="TLAlttoplam" id="TLAlttoplam"
                                    value="<?php echo $purchase->TLTotal ?? '0.00' ?>" style="border-radius: 6px; border-color: #cbd5e1;">
                            </td>

                            <td style="vertical-align: middle;">
                                <input type="number" autocomplete="off" class="form-control text-center" name="iskonto"
                                    value="<?php echo $purchase->iskonto ?? '0.00'; ?>" id="iskonto" style="border-radius: 6px; border-color: #cbd5e1;">
                            </td>
                            <td style="vertical-align: middle;">
                                <?php KdvOranları("Kdv", $purchase->Kdv ?? 20) ?>
                            </td>
                            <td style="vertical-align: middle;">
                                <input type="text" autocomplete="off" class="form-control text-center" name="altToplam"
                                    id="altToplamInput" value="<?php echo $purchase->altToplam ?? '0.00'; ?>" style="border-radius: 6px; border-color: #cbd5e1;">
                                <input type="hidden" id="araToplam" name="araToplam" value="">
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>
        </div>
        </div>
    </div>
</div>
</form>



<?php include_once __DIR__ . '/../../../components/modals/multi-product-modal.php'; ?>

<script>
    $(document).ready(function () {
        if (window.ProductPicker) {
            ProductPicker.init({
                tableSelector: '#tProduct, #sortable',
                fields: {
                    title: 'input[name="urunAdi[]"]',
                    stock: 'input[name="stokKodu[]"]',
                    price: 'input[name="price[]"]',
                    currency: 'select[name="currency[]"]',
                    unit: 'select[name="unit[]"]',
                    amount: 'input[name="amount[]"]'
                },
                onSelect: function($row, data) {
                    if (typeof updateToplamPurchase === 'function') {
                        updateToplamPurchase();
                    }
                }
            });
        }
    });

    $(function() {
        var el = document.getElementById('sortable');
        if (el && typeof Sortable !== 'undefined') {
            var sortable = Sortable.create(el, {
                onUpdate: function (/**Event*/evt) {
                    // Sıralama sonrası numaralandırma
                    $("#tProduct tbody tr").each(function(index) {
                        $(this).find("input[name='satirno[]']").val(index + 1);
                        $(this).find(".app-item-number input").val(index + 1);
                    });
                }
            });
        }
    });
</script>
