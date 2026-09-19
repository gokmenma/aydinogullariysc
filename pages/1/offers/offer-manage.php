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
                </div>
                <div class="header-actions">
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
                max-width: 1400px;
                margin: 0 auto;
            }

            .offer-header-card {
                background: linear-gradient(135deg, #1e3a5f 0%, #2d5986 50%, #3b7dd8 100%);
                border-radius: 16px;
                padding: 24px 30px;
                margin-bottom: 25px;
                box-shadow: 0 8px 32px rgba(30, 58, 95, 0.2);
                position: relative;
                overflow: hidden;
            }

            .offer-header-card::after {
                content: '';
                position: absolute;
                top: -50%;
                right: -20%;
                width: 300px;
                height: 300px;
                background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
                border-radius: 50%;
            }

            .offer-header-card .header-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                position: relative;
                z-index: 1;
                flex-wrap: wrap;
                gap: 15px;
            }

            .offer-header-card .header-left {
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .offer-header-card .header-icon {
                width: 52px;
                height: 52px;
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.15);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                color: #fff;
                backdrop-filter: blur(10px);
            }

            .offer-header-card .header-title h4 {
                color: #fff;
                margin: 0;
                font-size: 20px;
                font-weight: 600;
            }

            .offer-header-card .header-title .offer-number-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: rgba(255, 255, 255, 0.2);
                padding: 4px 14px;
                border-radius: 20px;
                color: #e0ecff;
                font-size: 13px;
                margin-top: 6px;
                backdrop-filter: blur(10px);
            }

            .offer-header-card .header-actions {
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
            }

            .offer-header-card .btn-header {
                padding: 10px 20px;
                border-radius: 10px;
                font-weight: 500;
                font-size: 14px;
                border: none;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                cursor: pointer;
                color: #fff !important;
                text-decoration: none;
            }

            .btn-header-save {
                background: linear-gradient(135deg, #22c55e, #16a34a);
                color: #fff;
                box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
            }

            .btn-header-save:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(34, 197, 94, 0.5);
            }

            .btn-header-list {
                background: rgba(255, 255, 255, 0.15);
                color: #fff;
                backdrop-filter: blur(10px);
            }

            .btn-header-list:hover {
                background: rgba(255, 255, 255, 0.25);
                transform: translateY(-2px);
            }

            .btn-header-action {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }
            .btn-header-action:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: translateY(-2px);
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

            .form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 25px 30px;
            }

            @media (max-width: 768px) {
                .form-grid {
                    grid-template-columns: 1fr;
                }
            }

            .form-grid .full-width {
                grid-column: 1 / -1;
            }

            .form-field {
                display: flex;
                flex-direction: column;
                gap: 8px;
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

            .form-field .form-control,
            .form-field .bootstrap-select .btn {
                border-radius: 10px !important;
                border: 1.5px solid #e5e7eb !important;
                padding: 10px 14px;
                font-size: 14px;
                transition: all 0.25s ease;
                background: #fafafa;
            }

            .form-field .form-control:focus {
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12) !important;
                background: #fff;
            }

            .form-field textarea.form-control {
                min-height: 100px;
                resize: vertical;
            }

            /* Input group alignments */
            .form-field .input-group {
                display: flex;
                align-items: stretch;
                width: 100%;
            }

            .form-field .input-group .bootstrap-select {
                flex: 1;
            }

            .form-field .input-group .bootstrap-select .btn {
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
            }

            .form-field .input-group a.btn {
                border-radius: 0 10px 10px 0 !important;
                border: 1.5px solid #e5e7eb !important;
                border-left: none !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 10px 15px;
                height: auto;
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
                    <p>Teklif genel bilgilerini ve şablon tercihlerini bu alandan yönetebilirsiniz.</p>
                </div>
            </div>

            <div class="form-grid">
                
                <!-- Firma Adı -->
                <div class="form-field">
                    <label for="customers"><font color="red">(*)</font> Firma Adı</label>
                    <div class="input-group">
                        <select required name="customers" id="customers" title="Seçiniz..." class="selectpicker form-control" data-style="bg-white" data-size="8" data-live-search="true">
                            <?php
                            $customer_id = $offer->cid ?? 0;
                            $qct = $ac->prepare(
                                'SELECT * FROM customers
                                 WHERE deleted_at IS NULL OR id = ?
                                 ORDER BY id DESC'
                            );
                            $qct->execute([$customer_id]);
                            while ($cscs = $qct->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <option <?php echo $customer_id == $cscs['id'] ? ' selected' : '' ?> value="<?php echo $cscs['id']; ?>">
                                    <?php echo $cscs['company']; ?>
                                </option>
                            <?php } ?>
                        </select>
                        <?php if (permtrue('customeradd')) { ?>
                            <a href="index.php?p=new-customer" target="_blank" class="btn btn-info btn-sm d-flex align-items-center" data-tooltip="Yeni Firma Eklemek için tıklayınız!">
                                <i class="fa fa-plus"></i>
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <!-- Üst Bilgi Seç -->
                <div class="form-field">
                    <label for="offerHeader">Üst Bilgi Şablonu Seç</label>
                    <div class="input-group">
                        <?php offerTemplate('offerHeader', $offer->offer_header ?? 12, 'Header'); ?>
                        <a href="index.php?p=offer-templates&type=Header" target="_blank" class="btn btn-secondary btn-sm d-flex align-items-center" type="button" data-tooltip="Yeni Şablon Eklemek için tıklayınız!" data-tooltip-location="left"><i class="fa fa-plus"></i></a>
                    </div>
                </div>

                <!-- Firma Yetkilisi -->
                <div class="form-field">
                    <label for="compAuths"><font color="red">(*)</font> Firma Yetkilisi</label>
                    <input type="text" class="form-control" placeholder="Yetkili ad soyad" id="compAuths" name="compAuths" value="<?php echo $offer->company_authors ?? ''; ?>" required />
                </div>

                <!-- Alt Bilgi Seç -->
                <div class="form-field">
                    <label for="offerFooter">Alt Bilgi Şablonu Seç</label>
                    <div class="input-group">
                        <?php offerTemplate('offerFooter', $offer->offer_footer ?? 11, 'Footer'); ?>
                        <a href="index.php?p=offer-templates&type=Footer" target="_blank" class="btn btn-secondary btn-sm d-flex align-items-center" type="button" data-tooltip="Yeni Şablon Eklemek için tıklayınız!" data-tooltip-location="left"><i class="fa fa-plus"></i></a>
                    </div>
                </div>

                <!-- Ödeme Vadesi -->
                <div class="form-field">
                    <label for="payPeriod">Ödeme Vadesi</label>
                    <input type="text" id="payPeriod" name="payPeriod" class="form-control" value="<?php echo $offer->payment_period ?? '' ?>" placeholder="Vade giriniz!">
                </div>

                <!-- Alt Bilgi İçerik (Full Width) -->
                <div class="form-field full-width">
                    <label for="offerFooterContent">Alt Bilgi Açıklaması</label>
                    <div id="offerFooterContent" class="offerFooterContent html-editor">
                        <textarea required name="offerFooterContent" class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ...">
                            <?php echo $offer->offer_footer_content ?? offerTemplateContent($offer->offer_footer ?? 11); ?>
                        </textarea>
                    </div>
                </div>

                <!-- Teklif Konusu -->
                <div class="form-field">
                    <label for="offer_subject">Teklif Konusu</label>
                    <input type="text" id="offer_subject" name="offer_subject" class="form-control" value="<?php echo $offer->offer_subject ?? '' ?>" placeholder="Örn: Yeni Teklif">
                </div>

                <!-- Tarih -->
                <div class="form-field">
                    <label for="offer_date"><font color="red">(*)</font> Tarih</label>
                    <input type="text" id="offer_date" name="offer_date" value="<?php echo $offer->offer_date ?? date('d.m.Y'); ?>" class="form-control date-picker" placeholder="">
                </div>

                <!-- Hazırlayan -->
                <div class="form-field">
                    <label><font color="red">(*)</font> Hazırlayan</label>
                    <input readonly class="form-control" value="<?php echo getUsername($offer->creativer ?? sesset("id")); ?>" type="text">
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

                <!-- Teklif Durumu -->
                <div class="form-field">
                    <label for="offerstatu"><font color="red">(*)</font> Teklif Durumu</label>
                    <?php $offer_statu = $offer->statu ?? 1; ?>
                    <select name="offerstatu" id="offerstatu" data-style="bg-white" class="selectpicker form-control">
                        <option <?php echo $offer_statu == 1 ? ' selected' : '' ?> value="1">Bekleyen</option>
                        <option <?php echo $offer_statu == 2 ? ' selected' : '' ?> value="2">Tamamlandı</option>
                    </select>
                </div>

                <!-- Şablon Yap / Notlar -->
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



        <br>

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
                                <th style="width: 75px;">İşlem</th>
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
                                <td colspan="12">
                                    <button type="button" id="ekle" class="btn btn-sm btn-primary mt-3 mb-3" style="border-radius: 8px;">
                                        <i class="fa fa-plus"></i> Yeni Satır
                                    </button>
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
                        <span class="text-muted mr-3" style="width: 150px; font-weight: 500;">Oluşturan:</span>
                        <span class="text-dark font-weight-bold"><?php echo getUserName($offer->creativer ?? 0); ?></span>
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

    <!-- Tablonun içine eklendiği zaman satır silince diğer satırlarda çalışmıyor -->
    <?php include_once 'offer-modal.php'; ?>
</form>

<!--buradan başlıyor-->
<script src="include/js/offer.js"></script>
<script src="pages/1/offers/offer.js?v=<?php echo filemtime("pages/1/offers/offer.js"); ?>"></script>

<script>
$(document).ready(function() {
    updateAltToplam();

    // Teklif durumu tamamlandı seçildiğinde otomatik servis oluştur checkbox'ını göster
    // $("#offerstatu").on("change", function() {
    //     if ($(this).val() == 2) {
    //         $("#create_service_div").show();
    //     } else {
    //         $("#create_service_div").hide();
    //         $("#createService").prop("checked", false);
    //     }
    // });
});

$(function() {

    //date-picker ile çakıştığı için sortable kütüphaesi kullanıldı
    // $("#sortable").sortable({
    //     update: function(event, ui) {
    //         // Sıralama sonrası numaralandırma
    //         $("#kalem_ekle tbody tr").each(function(index) {
    //             // Numara hücresini güncelle (örneğin ilk <td>)
    //             $(this).find("input[name='satirno[]']").val(index + 1);
    //         });
    //     }
    // });


    var el = document.getElementById('sortable');
    var sortable = Sortable.create(el, {
        onUpdate: function( /**Event*/ evt) {
            // Sıralama sonrası numaralandırma
            $("#kalem_ekle tbody tr").each(function(index) {
                // Numara hücresini güncelle (örneğin ilk <td>)
                $(this).find("input[name='satirno[]']").val(index + 1);
            });
        }
    });
});
</script>
