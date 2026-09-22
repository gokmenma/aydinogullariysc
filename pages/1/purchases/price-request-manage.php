<?php

use App\Helper\customer;
use App\Helper\Financial;
use App\Helper\Helper;
use App\Model\PurchaseModel;

$Purchases = new PurchaseModel();

global $pdat;

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

// Güncelleme işlemi ise bilgileri getirir.
$purchase = $Purchases->find($id);

if (!$purchase) {
    $purchase = new stdClass();
}

if (!function_exists('getFileIcon')) {
    function getFileIcon($path) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return 'fa-image';
        if ($ext == 'pdf') return 'fa-file-pdf-o';
        if (in_array($ext, ['xls', 'xlsx', 'csv'])) return 'fa-file-excel-o';
        if (in_array($ext, ['doc', 'docx'])) return 'fa-file-word-o';
        if (in_array($ext, ['zip', 'rar', '7z'])) return 'fa-file-archive-o';
        return 'fa-paperclip';
    }
}

// Satın almanın ürünlerini getirir.
$purchaseItems = $Purchases->getPurchaseItems($id);

if ($id == 0) {
    $getNumber = setNumber("price_request");
    $siparisNo = "FT000" . $getNumber;
} else {
    $siparisNo = $purchase->siparisNo ?? '';
}

$alisToplam = $purchase->TLTotal ?? "0.00";
$genelToplam = $purchase->altToplam ?? "0.00";
?>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">

<style>
    /* ==============================================================
       PREMIUM PRICE REQUEST MANAGE STYLES (NO HORIZONTAL SCROLL)
       ============================================================== */
    .pricereq-manage-wrapper {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
    }

    .pricereq-header-card {
        background: linear-gradient(135deg, #1e3a5f 0%, #2d5986 50%, #3b7dd8 100%);
        border-radius: 16px;
        padding: 22px 28px;
        margin-bottom: 22px;
        box-shadow: 0 8px 32px rgba(30, 58, 95, 0.2);
        position: relative;
        overflow: hidden;
    }

    .pricereq-header-card::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .pricereq-header-card .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        z-index: 1;
        flex-wrap: wrap;
        gap: 15px;
    }

    .pricereq-header-card .header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .pricereq-header-card .header-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #fff;
        backdrop-filter: blur(10px);
    }

    .pricereq-header-card .header-title h4 {
        color: #fff;
        margin: 0;
        font-size: 19px;
        font-weight: 600;
        letter-spacing: -0.01em;
    }

    .pricereq-header-card .header-title .pricereq-number-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.2);
        padding: 3px 12px;
        border-radius: 20px;
        color: #e0ecff;
        font-size: 12.5px;
        font-weight: 500;
        margin-top: 5px;
        backdrop-filter: blur(10px);
    }

    .pricereq-header-card .header-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .pricereq-header-card .btn-header {
        padding: 9px 18px;
        border-radius: 9px;
        font-weight: 500;
        font-size: 13.5px;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        color: #fff !important;
        text-decoration: none;
    }

    .pricereq-header-card .btn-header-save {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
    }

    .pricereq-header-card .btn-header-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.5);
    }

    .pricereq-header-card .btn-header-list {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        backdrop-filter: blur(10px);
    }

    .pricereq-header-card .btn-header-list:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
    }

    .pricereq-header-card .btn-header-print {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #fff;
        backdrop-filter: blur(10px);
    }

    .pricereq-header-card .btn-header-print:hover {
        background: rgba(255, 255, 255, 0.22);
        transform: translateY(-2px);
    }

    /* KPI Summary Cards */
    .summary-kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .summary-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    }

    .summary-kpi-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
    }

    .summary-kpi-card.kpi-subtotal::before { background: #3b82f6; }
    .summary-kpi-card.kpi-grandtotal::before { background: #10b981; }

    .summary-kpi-card .kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 3px;
    }

    .summary-kpi-card .kpi-val {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
    }

    .summary-kpi-card .kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
    }

    /* Table Container - Strictly Fit Width Without Horizontal Scroll */
    .pricereq-table-wrapper {
        width: 100%;
        overflow: visible;
    }

    .pricereq-manage-wrapper .premium-table {
        width: 100% !important;
        table-layout: auto !important;
        margin-top: 10px;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 10px;
        border: 1px solid #e2e8f0 !important;
    }

    .pricereq-manage-wrapper .premium-table th {
        font-size: 11px !important;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 8px 4px !important;
        background: #f8fafc;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    .pricereq-manage-wrapper .premium-table td {
        padding: 5px 3px !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
    }

    .pricereq-manage-wrapper .premium-table .form-control {
        height: 30px !important;
        padding: 3px 5px !important;
        font-size: 12.5px !important;
        border-radius: 6px !important;
    }

    .pricereq-manage-wrapper .premium-table .bootstrap-select .btn {
        height: 30px !important;
        padding: 3px 5px !important;
        font-size: 12px !important;
        border-radius: 6px !important;
    }

    .pricereq-manage-wrapper .premium-table .bootstrap-select {
        width: 100% !important;
        min-width: unset !important;
    }

    /* Attachments Cell */
    .attachments-cell {
        width: 125px;
        max-width: 135px;
    }
    .file-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    .file-input-wrapper input[type="file"] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
        z-index: 10;
    }
    .btn-attachment-toggle {
        width: 100%;
        text-align: left;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 4px 6px;
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 6px;
        font-size: 11px;
        color: #64748b;
        transition: all 0.2s;
        cursor: pointer;
        height: 30px;
    }
    .btn-attachment-toggle:hover {
        border-color: #3b82f6;
        background: #eff6ff;
        color: #3b82f6;
    }
    .status-indicator { 
        font-size: 10px; 
        font-weight: 500; 
        display: flex; 
        align-items: center; 
        gap: 4px; 
        text-decoration: none !important;
        position: relative;
        z-index: 20 !important;
        margin-top: 3px;
        justify-content: center;
    }
    .delete-file-btn {
        font-size: 12px !important;
        padding: 1px 3px;
        border-radius: 3px;
        transition: all 0.2s;
        cursor: pointer;
        color: #ef4444;
    }
    .delete-file-btn:hover {
        background-color: rgba(239, 68, 68, 0.1);
        transform: scale(1.15);
    }
    .bg-blue-light { background-color: #eff6ff !important; }

    /* Dark Mode Overrides */
    .dark-mode .pricereq-header-card {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    }
    .dark-mode .summary-kpi-card {
        background: #1e293b;
        border-color: #334155;
    }
    .dark-mode .summary-kpi-card .kpi-label {
        color: #94a3b8;
    }
    .dark-mode .btn-attachment-toggle {
        background: #0f172a;
        border-color: #334155;
        color: #94a3b8;
    }
    .dark-mode .btn-attachment-toggle:hover {
        background: #1e293b;
        border-color: #60a5fa;
        color: #60a5fa;
    }
    .dark-mode .bg-blue-light {
        background-color: rgba(59, 130, 246, 0.15) !important;
    }
</style>

<form enctype="multipart/form-data" method="POST" id="myForm">
    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>">
    <input type="hidden" name="type" value="2"> <!-- 2 = Fiyat Talebi -->
    <input type="hidden" name="siparisNo" id="siparisNo" value="<?php echo $siparisNo; ?>">
    <input type="hidden" name="altToplam" id="altToplamInput" value="<?php echo $genelToplam; ?>">
    <input type="hidden" name="araToplam" id="araToplam" value="<?php echo $alisToplam; ?>">
    <input type="hidden" name="TLAlttoplam" id="TLAlttoplam" value="<?php echo $alisToplam; ?>">
    <input type="hidden" name="EuroAlttoplam" id="EuroAlttoplam" value="<?php echo $purchase->EuroTotal ?? '0.00'; ?>">
    <input type="hidden" name="DolarAlttoplam" id="DolarAlttoplam" value="<?php echo $purchase->DolarTotal ?? '0.00'; ?>">

    <div class="pricereq-manage-wrapper">
        <!-- Header Card -->
        <div class="pricereq-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-tag"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $id != 0 ? 'Fiyat Talebini Düzenle' : 'Yeni Fiyat Talebi Girişi'; ?></h4>
                        <span class="pricereq-number-badge">
                            <i class="fa fa-barcode"></i> Talep No: <?php echo $siparisNo; ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <?php if ($id > 0): ?>
                        <a href="index.php?p=purchases/price-request-print&id=<?php echo $id; ?>" target="_blank" class="btn-header btn-header-print">
                            <i class="fa fa-print"></i> Yazdır / PDF
                        </a>
                    <?php endif; ?>
                    <a href="index.php?p=purchases/price-request-list" class="btn-header btn-header-list">
                        <i class="fa fa-list"></i> Listeye Dön
                    </a>
                    <button type="button" id="savePriceRequestButton" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Card: Talep Genel Bilgileri -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header d-flex justify-content-between align-items-center">
                <div class="header-left-inner d-flex align-items-center">
                    <div class="card-icon card-icon-blue" style="background: #eff6ff; color: #3b82f6; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 17px; margin-right: 12px;">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-weight: 600; font-size: 15.5px;">Talep Genel Bilgileri</h5>
                        <p class="mb-0 text-muted font-12" style="margin-top: 2px;">
                            Tedarikçi firma, teslimat vadesi, kur tercihleri ve talep açıklamalarını bu alandan belirleyebilirsiniz.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Sol Kolon -->
                <div class="col-md-6">
                    <div class="form-group row align-items-center">
                        <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                            <span class="text-danger">*</span> Talep Numarası
                        </label>
                        <div class="col-md-8">
                            <span class="badge badge-light text-dark font-14 weight-600 px-3 py-2" style="border-radius: 6px; background: #f1f5f9; border: 1px solid #cbd5e1;">
                                <i class="fa fa-tag text-primary mr-1"></i> <?php echo $siparisNo; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Firma Seçimi -->
                    <div class="form-group row align-items-center">
                        <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                            <span class="text-danger">*</span> Tedarikçi Firma
                        </label>
                        <div class="col-md-8 d-flex align-items-center">
                            <div class="w-100 mr-2">
                                <?php echo customer::getCustomerSelect('customers', $purchase->companyID ?? 0); ?>
                            </div>
                            <a href="index.php?p=new-customer" target="_blank"
                                class="btn btn-outline-primary d-flex align-items-center justify-content-center" style="height: 38px; width: 38px; border-radius: 8px; padding: 0;"
                                data-tooltip="Yeni Firma Ekle"><i class="fa fa-plus"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Termin Tarihi -->
                    <div class="form-group row align-items-center">
                        <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                            <span class="text-danger">*</span> Termin Tarihi
                        </label>
                        <div class="col-md-8">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1;">
                                        <i class="fa fa-calendar-o text-muted"></i>
                                    </span>
                                </div>
                                <input required class="form-control date-picker border-left-0" style="border-radius: 0 8px 8px 0; border-color: #cbd5e1;" type="text"
                                    value="<?php echo $purchase->deadline ?? date("d-m-Y") ?>" name="deadline"
                                    autocomplete="off" placeholder="gg-aa-yyyy">
                            </div>
                        </div>
                    </div>

                    <!-- Açıklama -->
                    <div class="form-group row">
                        <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                            Talep Açıklaması
                        </label>
                        <div class="col-md-8">
                            <textarea name="description1" placeholder="Fiyat talebi ile ilgili tedarikçiye iletilecek veya iç notları giriniz..."
                                class="form-control" style="border-radius: 8px; border-color: #cbd5e1; min-height: 85px;"><?php echo $purchase->description1 ?? '' ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon -->
                <div class="col-md-6">
                    <!-- Durum -->
                    <div class="form-group row align-items-center">
                        <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                            Talep Durumu
                        </label>
                        <div class="col-md-8">
                            <?php echo Helper::selectState("state", $purchase->state ?? 1); ?>
                        </div>
                    </div>

                    <!-- Kur Türü -->
                    <div class="form-group row align-items-center">
                        <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                            Kur Türü
                        </label>
                        <div class="col-md-8">
                            <?php KurTuru('cur_type', $purchase->currency ?? '') ?>
                        </div>
                    </div>

                    <!-- Döviz Kurları -->
                    <div class="form-group row align-items-center">
                        <label class="col-form-label col-md-4 font-weight-600 text-slate" style="font-size: 13.5px;">
                            Döviz Kurları
                        </label>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 pr-1">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border-right-0 text-muted" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1; font-weight: 600;">$</span>
                                        </div>
                                        <input type="text" readonly class="form-control bg-light border-left-0" style="border-radius: 0 8px 8px 0; border-color: #cbd5e1;" id="cur-Dollar" name="Dollar" value="<?php echo $purchase->Dollar ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 pl-1">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border-right-0 text-muted" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1; font-weight: 600;">€</span>
                                        </div>
                                        <input type="text" readonly class="form-control bg-light border-left-0" style="border-radius: 0 8px 8px 0; border-color: #cbd5e1;" id="cur-Euro" name="Euro" value="<?php echo $purchase->Euro ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Özet KPI Kutuları -->
                    <div class="row mt-3">
                        <div class="col-sm-6 mb-2">
                            <div class="summary-kpi-card kpi-subtotal">
                                <div>
                                    <div class="kpi-label">ARA TOPLAM (TL)</div>
                                    <div class="kpi-val text-primary" id="buy-tl"><?php echo $alisToplam; ?></div>
                                </div>
                                <div class="kpi-icon bg-light text-primary">
                                    <i class="fa fa-calculator"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <div class="summary-kpi-card kpi-grandtotal">
                                <div>
                                    <div class="kpi-label">GENEL TOPLAM (TL)</div>
                                    <div class="kpi-val text-success" id="lblTotalTL"><?php echo $genelToplam; ?></div>
                                </div>
                                <div class="kpi-icon bg-light text-success">
                                    <i class="fa fa-money"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Ürünler Tablo Kartı -->
        <div class="form-card animate-fade-in mt-4 mb-4">
            <div class="form-card-header d-flex justify-content-between align-items-center">
                <div class="header-left-inner d-flex align-items-center">
                    <div class="card-icon card-icon-indigo" style="background: #e0e7ff; color: #4f46e5; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 17px; margin-right: 12px;">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-weight: 600; font-size: 15.5px;">Talep Edilen Ürünler</h5>
                        <p class="mb-0 text-muted font-12" style="margin-top: 2px;">
                            Teklif veya fiyat alınacak malzeme/ürün kalemlerini, adetlerini ve varsa ek teknik belgelerini ekleyiniz.
                        </p>
                    </div>
                </div>
                <div>
                    <button type="button" id="addRow" class="btn btn-sm btn-primary shadow-sm" style="border-radius: 8px; font-weight: 500;">
                        <i class="fa fa-plus-circle mr-1"></i> Yeni Ürün Ekle
                    </button>
                </div>
            </div>

            <div class="pricereq-table-wrapper">
                <table id="tProduct" class="table premium-table">
                    <thead>
                        <tr>
                            <th style="width: 28px;"><i class="fa fa-arrows-alt text-muted"></i></th>
                            <th style="width: 42px;">İşlem</th>
                            <th style="width: 38px;">Sıra</th>
                            <th style="width: 105px;">Stok Kodu</th>
                            <th>Ürün Adı / Kalem Açıklaması</th>
                            <th style="width: 65px;">Miktar</th>
                            <th style="width: 85px;">Birim</th>
                            <th style="width: 88px;">Alış Fiyat</th>
                            <th style="width: 78px;">Para Birimi</th>
                            <th class="attachments-cell">Ekler (Resim/Excel)</th>
                        </tr>
                    </thead>

                    <tbody id="sortable">
                        <?php
                        $i = 0;
                        if (empty($purchaseItems)) {
                            $purchaseItems = [new stdClass()];
                        }
                        foreach ($purchaseItems as $index => $item) {
                            $i++;
                            $hasFile = !empty($item->image) || !empty($item->excel_file);
                            $fileUrl = !empty($item->image) ? $item->image : ($item->excel_file ?? '#');
                        ?>
                            <tr class="ui-state-default">
                                <td class="text-center" style="vertical-align: middle; cursor: grab;">
                                    <i class="fa fa-ellipsis-v text-muted" style="opacity: 0.6;"></i>
                                    <i class="fa fa-ellipsis-v text-muted" style="opacity: 0.6; margin-left: -2px;"></i>
                                </td>

                                <td class="text-center app-item-action" style="vertical-align: middle;">
                                    <a type="button" class="sil btn btn-sm btn-outline-danger border-0" style="border-radius: 6px; padding: 2px 5px;" title="Satırı Sil">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>

                                <!-- Sıra no -->
                                <td class="text-center app-item-number" style="vertical-align: middle;">
                                    <span class="text-muted font-weight-bold row-index-label"><?php echo $i; ?></span>
                                    <input name="satirno[]" type="hidden" value="<?php echo $i; ?>">
                                </td>

                                <!-- Stok Kodu -->
                                <td class="app-item-stock" style="vertical-align: middle;">
                                    <input type="text" id="stokKodu<?php echo $i; ?>"
                                        value="<?php echo htmlspecialchars($item->stokKodu ?? '', ENT_QUOTES, 'UTF-8'); ?>" name="stokKodu[]" class="form-control"
                                        placeholder="Kodu" style="border-radius: 6px; border-color: #cbd5e1;">
                                </td>

                                <!-- Ürün Adı / Açıklama -->
                                <td class="app-item-name" style="vertical-align: middle;">
                                    <div class="input-group mb-1">
                                        <input type="text" class="urunAdi form-control" name="urunAdi[]"
                                            id="urunAdi<?php echo $i; ?>" value="<?php echo htmlspecialchars($item->product ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            placeholder="Ürün adı" required style="border-radius: 6px 0 0 6px; border-color: #cbd5e1;">
                                        <div class="input-group-append">
                                            <button type="button" id="<?php echo $i; ?>"
                                                class="btn btn-sm btn-info selectProduct text-white" data-bs-toggle="modal"
                                                data-bs-target="#staticBackdrop" style="border-radius: 0 6px 6px 0; height: 30px; padding: 0 8px;" title="Ürün Listesinden Seç">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <input type="text" name="rowdescription[]" value="<?php echo htmlspecialchars($item->description ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        class="form-control" placeholder="Kalem açıklaması..." style="border-radius: 6px; border-color: #cbd5e1; font-size: 11.5px !important; height: 26px !important;">
                                </td>

                                <!-- Miktar -->
                                <td class="app-item-amount" style="vertical-align: middle;">
                                    <input type="number" autocomplete="off" required id="amount<?php echo $i; ?>" name="amount[]"
                                        value="<?php echo htmlspecialchars($item->amount ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control text-center" style="border-radius: 6px; border-color: #cbd5e1;">
                                </td>

                                <!-- Birim -->
                                <td class="app-item-unit" style="vertical-align: middle;">
                                    <?php OlcuBirimleri('unit[]', $item->unit ?? '', "required", "unit" . $i) ?>
                                </td>

                                <!-- Fiyat -->
                                <td class="app-item-price" style="vertical-align: middle;">
                                    <input required id="price<?php echo $i; ?>" name="price[]" type="number" step="0.01"
                                        value="<?php echo htmlspecialchars($item->price ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control text-right" autocomplete="off" style="border-radius: 6px; border-color: #cbd5e1;">
                                </td>

                                <!-- Para Birimi -->
                                <td class="app-item-cur" style="vertical-align: middle;">
                                    <?php echo Financial::getCurrencySelect("currency[]", $item->currency ?? '', "currency" . $i) ?>
                                </td>

                                <!-- Ekler -->
                                <td class="text-center align-middle attachments-cell">
                                    <div class="file-input-wrapper">
                                        <div class="btn-attachment-toggle <?php echo $hasFile ? 'border-primary bg-blue-light' : ''; ?>">
                                            <span class="file-label text-truncate"><?php echo $hasFile ? (strlen(basename($fileUrl)) > 13 ? substr(basename($fileUrl), 0, 10) . '...' : basename($fileUrl)) : 'Dosya Seç...'; ?></span>
                                            <i class="fa <?php echo $hasFile ? getFileIcon($fileUrl) . ' text-primary' : 'fa-paperclip'; ?>"></i>
                                        </div>
                                        <input type="file" name="row_file_<?php echo $index; ?>" accept=".jpg,.jpeg,.png,.pdf,.xls,.xlsx,.csv,.doc,.docx" onchange="handleSingleFileChange(this)">
                                        
                                        <!-- Hidden inputs for existing file tracking -->
                                        <input type="hidden" name="existing_image[]" value="<?php echo htmlspecialchars($item->image ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="existing_excel_file[]" value="<?php echo htmlspecialchars($item->excel_file ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php if($hasFile): ?>
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <a href="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="status-indicator text-primary" title="<?php echo htmlspecialchars(basename($fileUrl), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fa fa-external-link"></i> <?php echo strlen(basename($fileUrl)) > 11 ? substr(basename($fileUrl), 0, 8) . '...' : basename($fileUrl); ?>
                                                </a>
                                                <a href="javascript:void(0)" class="status-indicator text-danger delete-file-btn" onclick="deleteItemFile(<?php echo $id; ?>, <?php echo (int)($item->id ?? 0); ?>, this)" title="Dosyayı Sil">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="status-indicator text-muted">Dosya yok</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="10" style="padding: 10px 12px !important; background: #f8fafc; border-top: 2px solid #e2e8f0;">
                                <button type="button" id="addRowFooter" class="btn btn-sm btn-outline-primary" style="border-radius: 8px; font-weight: 500;">
                                    <i class="fa fa-plus"></i> Yeni Satır Ekle
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <input type="hidden" id="rowNumberId" value="<?php echo $i + 1 ?>">
            </div>
        </div>
    </div>
</form>

<!-- Modal: Ürün Seçimi -->
<div class="modal fade" id="staticBackdrop" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; padding: 16px 20px;">
                <h6 class="modal-title font-weight-600 text-slate" id="staticBackdropLabel">
                    <i class="fa fa-cube text-primary mr-1"></i> Listeden Ürün Seçiniz
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <?php generateProductSelect("productName[]", '') ?>
                <input type="hidden" id="rowID">
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 12px 20px;">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" style="border-radius: 8px;">Kapat</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="getProductInfoPurchase()" style="border-radius: 8px;">Seç ve Ekle</button>
            </div>
        </div>
    </div>
</div>

<script src="pages/1/purchases/script.js"></script>
<script>
    function getFileIconClass(fileName) {
        const ext = fileName.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'fa-image';
        if (ext === 'pdf') return 'fa-file-pdf-o';
        if (['xls', 'xlsx', 'csv'].includes(ext)) return 'fa-file-excel-o';
        if (['doc', 'docx'].includes(ext)) return 'fa-file-word-o';
        return 'fa-file-o';
    }

    function handleSingleFileChange(input) {
        const wrapper = $(input).closest('.file-input-wrapper');
        const toggle = wrapper.find('.btn-attachment-toggle');
        const label = toggle.find('.file-label');
        const icon = toggle.find('i');
        const indicator = wrapper.find('.status-indicator');
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (file.size > maxSize) {
                Swal.fire('Hata', 'Dosya boyutu 5MB\'tan büyük olamaz. Lütfen daha küçük bir dosya seçin.', 'error');
                input.value = '';
                label.text('Dosya Seç...').css('color', '');
                toggle.removeClass('border-primary bg-blue-light');
                icon.attr('class', 'fa fa-paperclip');
                indicator.html('<span class="status-indicator text-muted">Dosya yok</span>');
                return;
            }

            const fileName = file.name;
            const shortName = fileName.length > 13 ? fileName.substring(0, 10) + '...' : fileName;
            label.text(shortName).css('color', '#3b82f6');
            toggle.addClass('border-primary bg-blue-light');
            
            const iconClass = getFileIconClass(fileName);
            icon.attr('class', 'fa ' + iconClass + ' text-primary');
            
            indicator.html('<i class="fa fa-check text-success"></i> ' + shortName).removeClass('text-muted').addClass('text-primary');
        }
    }

    function deleteItemFile(purchaseId, itemId, btn) {
        if (!itemId || itemId === 0) {
            const wrapper = $(btn).closest('.file-input-wrapper');
            wrapper.find("input[type='file']").val('');
            wrapper.find("input[name='existing_image[]']").val('');
            wrapper.find("input[name='existing_excel_file[]']").val('');
            wrapper.find('.file-label').text('Dosya Seç...').css('color', '');
            wrapper.find('.btn-attachment-toggle').removeClass('border-primary bg-blue-light');
            wrapper.find('.btn-attachment-toggle i').attr('class', 'fa fa-paperclip');
            $(btn).closest('.d-flex').html('<span class="status-indicator text-muted">Dosya yok</span>');
            return;
        }

        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu dosyayı silmek istediğinize emin misiniz? Bu işlem geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Evet, Sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'deleteItemFile');
                formData.append('purchaseId', purchaseId);
                formData.append('itemId', itemId);

                fetch('App/api/purchase.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const wrapper = $(btn).closest('.file-input-wrapper');
                        const toggle = wrapper.find('.btn-attachment-toggle');
                        const label = toggle.find('.file-label');
                        const icon = toggle.find('i');
                        
                        wrapper.find("input[name='existing_image[]']").val("");
                        wrapper.find("input[name='existing_excel_file[]']").val("");
                        
                        label.text('Dosya Seç...').css('color', '');
                        toggle.removeClass('border-primary bg-blue-light');
                        icon.attr('class', 'fa fa-paperclip');
                        
                        $(btn).closest('.d-flex').html('<span class="status-indicator text-muted">Dosya yok</span>');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Silindi!',
                            text: 'Dosya başarıyla silindi.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Hata!', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Hata!', 'Sunucuyla iletişim kurulurken bir sorun oluştu.', 'error');
                });
            }
        });
    }

    async function priceRequestRowAdd(sayac) {
        $("#preloader").show();
        
        let selectUnit = '';
        let selectmoneys = '';
        
        let formData = new FormData();
        formData.append("action", "getUnits");
        
        try {
            const response = await fetch("App/api/units.php", {
                method: "POST",
                body: formData,
            });
            const data = await response.json();
            if (data.status === "success") {
                selectUnit = `<select required id="unit${sayac}" name="unit[]" class="selectpicker form-control form-control-sm" data-style="bg-white">`;
                data.data.forEach(u => {
                    selectUnit += `<option value="${u.title}">${u.title}</option>`;
                });
                selectUnit += `</select>`;
            }
        } catch (e) { console.error(e); }

        selectmoneys = `<select required id="currency${sayac}" name="currency[]" class="selectpicker form-control form-control-sm" data-style="bg-white">`;
        ["TRY", "USD", "EUR"].forEach(m => {
            selectmoneys += `<option value="${m}">${m}</option>`;
        });
        selectmoneys += `</select>`;

        const rowIndex = parseInt(sayac) - 1;
        
        let newRow = `
            <tr class="ui-state-default">
                <td class="text-center" style="vertical-align: middle; cursor: grab;">
                    <i class="fa fa-ellipsis-v text-muted" style="opacity: 0.6;"></i>
                    <i class="fa fa-ellipsis-v text-muted" style="opacity: 0.6; margin-left: -2px;"></i>
                </td>
                <td class="text-center app-item-action" style="vertical-align: middle;">
                    <a type="button" class="sil btn btn-sm btn-outline-danger border-0" style="border-radius: 6px; padding: 2px 5px;" title="Satırı Sil">
                        <i class="fa fa-trash"></i>
                    </a>
                </td>
                <td class="text-center app-item-number" style="vertical-align: middle;">
                    <span class="text-muted font-weight-bold row-index-label">${sayac}</span>
                    <input name="satirno[]" type="hidden" value="${sayac}">
                </td>
                <td class="app-item-stock" style="vertical-align: middle;">
                    <input type="text" id="stokKodu${sayac}" name="stokKodu[]" class="form-control" placeholder="Kodu" style="border-radius: 6px; border-color: #cbd5e1;">
                </td>
                <td class="app-item-name" style="vertical-align: middle;">
                    <div class="input-group mb-1">
                        <input type="text" required name="urunAdi[]" id="urunAdi${sayac}" class="urunAdi form-control" placeholder="Ürün Adı" style="border-radius: 6px 0 0 6px; border-color: #cbd5e1;">
                        <div class="input-group-append">
                            <button type="button" id="${sayac}" class="btn btn-sm btn-info selectProduct text-white" data-bs-toggle="modal" data-bs-target="#staticBackdrop" style="border-radius: 0 6px 6px 0; height: 30px; padding: 0 8px;" title="Ürün Listesinden Seç">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <input type="text" name="rowdescription[]" class="form-control" placeholder="Kalem açıklaması..." style="border-radius: 6px; border-color: #cbd5e1; font-size: 11.5px !important; height: 26px !important;">
                </td>
                <td class="app-item-amount" style="vertical-align: middle;">
                    <input type="number" required id="amount${sayac}" name="amount[]" class="form-control text-center" style="border-radius: 6px; border-color: #cbd5e1;">
                </td>
                <td class="app-item-unit" style="vertical-align: middle;">${selectUnit}</td>
                <td class="app-item-price" style="vertical-align: middle;">
                    <input type="number" step="0.01" required id="price${sayac}" name="price[]" class="form-control text-right" style="border-radius: 6px; border-color: #cbd5e1;">
                </td>
                <td class="app-item-cur" style="vertical-align: middle;">${selectmoneys}</td>
                <td class="text-center align-middle attachments-cell">
                    <div class="file-input-wrapper">
                        <div class="btn-attachment-toggle">
                            <span class="file-label text-truncate">Dosya Seç...</span>
                            <i class="fa fa-paperclip"></i>
                        </div>
                        <input type="file" name="row_file_${rowIndex}" accept=".jpg,.jpeg,.png,.pdf,.xls,.xlsx,.csv,.doc,.docx" onchange="handleSingleFileChange(this)">
                        <input type="hidden" name="existing_image[]" value="">
                        <input type="hidden" name="existing_excel_file[]" value="">
                        <span class="status-indicator text-muted">Dosya yok</span>
                    </div>
                </td>
            </tr>
        `;
        
        $("#tProduct tbody").append(newRow);
        $(".selectpicker").selectpicker("refresh");
        $("#preloader").hide();
        updateRowIndexes();
        updateToplamPurchase();
    }

    function updateRowIndexes() {
        $("#tProduct tbody tr").each(function(index) {
            const num = index + 1;
            $(this).find(".row-index-label").text(num);
            $(this).find("input[name='satirno[]']").val(num);
            $(this).find("input[type='file']").attr("name", "row_file_" + index);
        });
        $("#rowNumberId").val($("#tProduct tbody tr").length + 1);
    }

    $(document).ready(function() {
        // Initialize sortable for drag and drop reordering
        if (typeof Sortable !== 'undefined') {
            const el = document.getElementById('sortable');
            if (el) {
                Sortable.create(el, {
                    handle: 'td:first-child',
                    animation: 150,
                    onUpdate: function () {
                        updateRowIndexes();
                    }
                });
            }
        }

        // Add row triggers
        $("#addRow, #addRowFooter").off("click").on("click", function (e) {
            e.preventDefault();
            var sayac = $("#rowNumberId");
            priceRequestRowAdd(sayac.val());
            sayac.val(parseInt(sayac.val(), 10) + 1);
        });

        // Row removal
        $("#tProduct").on("click", ".sil", function(e) {
            e.preventDefault();
            if ($("#tProduct tbody tr").length > 1) {
                $(this).closest("tr").remove();
                updateRowIndexes();
                updateToplamPurchase();
            } else {
                Swal.fire('Bilgi', 'En az 1 ürün satırı bulunmalıdır.', 'info');
            }
        });

        // Auto currency calculation on row edits
        $("#tProduct").on("input change", "input, select", function() {
            updateToplamPurchase();
        });

        // Save Price Request
        $(document).off("click", "#savePriceRequestButton").on("click", "#savePriceRequestButton", function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            const customerVal = $("select[name='customers']").val();
            if (!customerVal || customerVal === "") {
                Swal.fire({
                    icon: "warning",
                    title: "Eksik Alan",
                    text: "Lütfen bir tedarikçi firma seçiniz!"
                });
                return;
            }

            // Check if at least one product has title
            let hasValidProduct = false;
            $("input[name='urunAdi[]']").each(function() {
                if ($(this).val().trim() !== "") {
                    hasValidProduct = true;
                }
            });

            if (!hasValidProduct) {
                Swal.fire({
                    icon: "warning",
                    title: "Ürün Bilgisi Gerekli",
                    text: "Lütfen en az bir ürün adı giriniz!"
                });
                return;
            }

            // Sync row file indexes before upload
            updateRowIndexes();

            var form = $("#myForm");
            var formData = new FormData(form[0]);
            formData.append("action", "savePurchases");

            Swal.fire({
                title: 'Fiyat Talebi Kaydediliyor...',
                text: 'Lütfen bekleyiniz, veriler sunucuya aktarılıyor.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("App/api/purchase.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    Swal.fire({
                        title: "Başarılı",
                        text: data.message || "Fiyat talebi başarıyla kaydedildi.",
                        icon: "success",
                        confirmButtonText: "Tamam"
                    }).then((result) => {
                        window.location.href = "index.php?p=purchases/price-request-list";
                    });
                } else {
                    Swal.fire({
                        title: "Hata",
                        text: data.message || "Kaydetme sırasında bir hata oluştu.",
                        icon: "error",
                        confirmButtonText: "Tamam"
                    });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({
                    title: "Hata",
                    text: "Sunucu hatası oluştu. Lütfen tekrar deneyiniz.",
                    icon: "error",
                    confirmButtonText: "Tamam"
                });
            });
        });

        // Trigger currency fetch and calculations on load
        if (typeof getCurrencyData === 'function') {
            getCurrencyData();
        }
        setTimeout(updateToplamPurchase, 300);
    });
</script>
