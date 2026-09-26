<?php
use App\Model\OfferModel;

permcontrol("customeredit");
if (@!$_GET["id"]) {
	header("Location:index.php?p=customers");
	exit;
}

$cid = (int)$_GET["id"];

$cerq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$cerq->execute(array($cid));
$cc = $cerq->fetch(PDO::FETCH_ASSOC);

if (!$cc) {
	header("Location: index.php?p=customers&err=01735");
	exit;
}

$todos = $ac->prepare("SELECT COUNT(*) FROM projects WHERE pcid = ?");
$todos->execute(array($cid));
$pjs = $todos->fetchColumn();

$todoso = $ac->prepare("SELECT COUNT(*) FROM offers WHERE cid = ? AND is_template = 0");
$todoso->execute(array($cid));
$ojs = $todoso->fetchColumn();

// Son oluşturulan teklif
$sot = $ac->prepare("SELECT * FROM offers WHERE cid = ? AND is_template = 0 ORDER BY id DESC LIMIT 1");
$sot->execute(array($cid));
$sonteklif = $sot->fetch(PDO::FETCH_ASSOC);

// Son Oluşturulan Servis
$sos = $ac->prepare("SELECT * FROM projects WHERE pcid = ? ORDER BY id DESC LIMIT 1");
$sos->execute(array($cid));
$ojsp = $sos->fetch(PDO::FETCH_ASSOC);

// Servis Tipi getirilir
$servicestype = null;
if (!empty($ojsp["servicestype"])) {
	$sql = $ac->prepare("SELECT * FROM units WHERE id = ? ");
	$sql->execute(array($ojsp["servicestype"]));
	$servicestype = $sql->fetch(PDO::FETCH_ASSOC);
}

// Teklif İcmal Verilerini Çek
$offerModel = new OfferModel();
$offerSummaryData = $offerModel->getCustomerOfferSummary($cid);
$customerOffers = $offerSummaryData['offers'] ?? [];
$customerOffersSummary = $offerSummaryData['summary'] ?? [];





if ($_POST) {

	if (!$_POST["company"]) {
		header("Location: index.php?p=customer-edit&cid=$cid&st=empties");
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

	$ahce->execute(array($ccompany, $cemail, $address, $location, $il, $ilce, $cdesc, $cgsm, $yetkiliadi,
                                    $categoryName, $OdemeVade,$region,$updater,$updated_at, $cid));

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
if ($_GET["st"] == "newsuccess") {
	showAlert("success", "İşlem Başarı ile tamamlandı!");
}
?>


<!-- Default Basic Forms Start -->
<div class="pd-20 bg-white border-radius-16 box-shadow mb-30">
    <div class="clearfix mb-30">

        <div class="pull-left">
            <div class="d-flex">
                <h4 class="text-blue ml-2">
                    <?php echo $pdat["p_title"]; ?>
                </h4><br>
            </div>
        </div>

        <div class="float-right">
            <button type="button" id="submitButton" onclick="validateForm()" class="btn btn-sm btn-primary">
                <i class="fa fa-save"></i> Kaydet</button>

            <a href="index.php?p=customers" data-tooltip="Listeye Dön" data-tooltip-location="bottom"
                class="btn btn-sm btn-secondary text-white mr-3">
                <i class="fa fa-list mr-1"></i>Listeye Dön</a>


        </div>



    </div>


    <style>
        .sum-customer{
            background: #2e2e2e !important;
            opacity: 0.8;
            border-radius: 12px;
          
        }
        .col-md-6,.col-md-3{
            padding-right: 5px !important;
            padding-left: 5px !important;
        }
    </style>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-12 mb-30">
            <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                <div class="project-info">
                    <div class="project-info-left">
                        <div class="icon box-shadow bg-blue text-white">
                            <i class="fa fa-gears"></i>
                        </div>
                    </div>
                    <div class="project-info-right">
                        <span class="no text-blue weight-500 font-24">
                            <?php echo $pjs; ?>
                        </span>
                        <p>
                            <a target="_blank" class="weight-400 font-18"
                                href="index.php?p=service/list&cid=<?php echo $cid ?>">Toplam Servis
                                Sayısı</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-30">
            <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                <div class="project-info">
                    <div class="project-info-left">
                        <div class="icon box-shadow bg-light-green text-white">
                            <i class="fa fa-handshake-o"></i>
                        </div>
                    </div>
                    <div class="project-info-right">
                        <span class="no text-blue weight-500 font-24">
                            <?php echo $ojs; ?>
                        </span>
                        <p class="mb-0">
                            <a href="#customerOffersIcmalCard" onclick="$('html, body').animate({scrollTop: $('#customerOffersIcmalCard').offset().top - 20}, 400); return false;" class="weight-400 font-18 text-white" title="İcmal Tablosuna Git">
                                Toplam Teklif Sayısı:
                            </a>
                        </p>
                        <small>
                            <a href="#customerOffersIcmalCard" onclick="$('html, body').animate({scrollTop: $('#customerOffersIcmalCard').offset().top - 20}, 400); return false;" class="text-light-green font-12 mr-2 font-weight-bold">
                                <i class="fa fa-arrow-down mr-1"></i>İcmale Git
                            </a>
                            <a target="_blank" href="index.php?p=offers&cid=<?php echo $cid ?>" class="text-white-50 font-12" title="Tüm Teklifler Listesini Yeni Sekmede Aç">
                                <i class="fa fa-external-link"></i> Liste
                            </a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-30">
            <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                <div class="project-info clearfix">
                    <div class="project-info-left">
                        <div class="icon box-shadow bg-danger text-white">
                            <i class="fa fa-file"></i>
                        </div>
                    </div>
                    <div class="project-info-right">

                        <span class="no text-blue weight-500 font-24">
                            <?php echo !empty($sonteklif["offerNumber"]) ? htmlspecialchars($sonteklif["offerNumber"], ENT_QUOTES, 'UTF-8') : '-'; ?>
                        </span>
                        <p class="weight-400 font-18">
                            <?php if (!empty($sonteklif["id"])): ?>
                                <a target="_blank" href="index.php?p=offer-view&id=<?php echo (int)$sonteklif["id"]; ?>">
                                    Son Oluşturulan Teklif
                                </a>
                            <?php else: ?>
                                <span>Son Oluşturulan Teklif</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-30">
            <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                <div class="project-info clearfix">
                    <div class="project-info-left">
                        <div class="icon box-shadow bg-light-orange text-white">
                            <i class="fa fa-gear"></i>
                        </div>
                    </div>
                    <div class="project-info-right">
                        <span class="no text-blue weight-500 font-24">
                            <?php echo $servicestype["title"] ?>
                        </span>
                        <p class="weight-400 font-18">
                            <a target="_blank" href="index.php?p=service/list&id=<?php echo $servicestype["id"] ?>">
                                Son Oluşturulan Servis
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>








    <form enctype="multipart/form-data" action="" id="myForm" method="POST">



        <div class="form-group row">

            <label for="company" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Firma Adı:
            </label>

            <div class="col-sm-12 col-md-4">

                <input required name="company" type="text" value="<?php echo $cc["company"]; ?>" class="form-control">

            </div>

            <label for="cemail" class="col-sm-12 col-md-2 col-form-label">

                <font color="red">(*)</font> E-Posta:

            </label>

            <div class="col-sm-12 col-md-4"><input required name="cemail" type="text"
                    value="<?php echo $cc["email"]; ?>" class="form-control">

            </div>

        </div>


        <div class="form-group row">

            <label for="categoryName" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> Grup:
            </label>

            <div class="input-group col-md-4">
                <select required name="categoryName" id="categoryName" class="selectpicker form-control"
                    data-style="border bg-white">

                    <!-- Müşteri grubu veritabanından getiriliyor -->
                    <?php

					$cek = $ac->prepare("SELECT * FROM cgroups WHERE statu = ? ");
					$cek->execute(array(1));

					while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
						if ($cc["grp"] == $dat["id"]) {
							$selected = 'selected';
						} else {
							$selected = '';
						}
						;
						echo '<option ' . $selected . ' value="' . $dat["id"] . '">' . $dat["title"] . ' </option>';
					}
					?>
                </select>
            </div>

            <label class="col-sm-12 col-md-2 col-form-label"> Yetkili Ad-Soyad:</label>

            <div class="col-sm-12 col-md-4">

                <input name="yetkili" type="text" class="form-control" value="<?php echo $cc["yetkili"]; ?>">

            </div>


        </div>


        <div class="form-group row">

            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>İl:
            </label>

            <div class="col-sm-12 col-md-4">

                <select name="il" id="il" class="selectpicker form-control" data-live-search="true" data-size="5"
                    data-style="border bg-white" title="<?php echo $cc["city"]; ?>">

                </select>

            </div>

            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>İlçe:
            </label>

            <div class="col-sm-12 col-md-4">

                <select name="ilce" id="ilce" class="form-control selectpicker" data-live-search="true" data-size="5"
                    data-none-Selected-Text="Seçim Yapılmadı" data-style="border bg-white">
                    <option value="<?php echo $cc["ilce"]; ?>">
                        <?php echo $cc["ilce"]; ?>
                    </option>
                </select>

            </div>

        </div>
        <div class="form-group row">
            <!-- Bölge Alanı -->
            <label for="region" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Bölge:
            </label>

            <div class="col-sm-12 col-md-4">

                <input required placeholder="Bölge yazınız" name="region" id="region" type="text"
                    class="form-control" value="<?php echo $cc["region"] ;?>">
            </div>
            <!-- Telefon Alanı -->

        </div>


        <div class="form-group row">
            <!-- Telefon Alanı -->
            <label for="cgsm" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Telefon:
            </label>

            <div class="col-sm-12 col-md-4">

                <input required placeholder="05XXXXXXXXX" maxlength="11" minlength="11" name="cgsm" type="text"
                    value="<?php echo $cc["gsm"]; ?>" class="form-control">
            </div>
            <!-- Telefon Alanı -->


            <div class="col-md-2 col-sm-12">
                <label class="col-form-label"> Ödeme Vadesi:</label>
            </div>

            <div class="col-sm-12 col-md-4">
                <input type="text" class="form-control" name="vade" id="vade" value="<?php echo $cc["OdemeVade"] ?>">

            </div>

        </div>
         <div class="form-group row">
            <label for="customer_address" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Adres :
            </label>

            <div class="col-sm-12 col-md-10">
                <textarea required name="customer_address" placeholder="Firma adresi" class="form-control" rows="3"
                    style="height:100%;"><?php echo $cc["address"] ?></textarea>
            </div>
        </div>

        <div class="form-group row">
            <label for="location" class="col-sm-12 col-md-2 col-form-label">Keşif / Saha Konumu:</label>
            <div class="col-sm-12 col-md-10">
                <input name="location" type="text" class="form-control"
                    value="<?php echo htmlspecialchars($cc['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="Keşiflerde otomatik kullanılacak saha adresi veya konumu">
            </div>
        </div>

        <div class="form-group row">

            <label class="col-sm-12 col-md-2 col-form-label">

                Açıklama:

            </label>

            <div class="col-sm-12 col-md-10">

                <textarea name="cdesc" placeholder="Firma hakkında yöneticilerin görebileceği bir not ekleyebilirsiniz."
                    class="form-control" text=""><?php echo $cc["cdesc"]; ?></textarea>

            </div>

        </div>

    </form>
</div>

<!-- ================================================================= -->
<!-- FIRMA TEKLİF İCMALİ KARTI -->
<!-- ================================================================= -->
<div class="pd-20 bg-white border-radius-16 box-shadow mb-30" id="customerOffersIcmalCard">
    <!-- Header / Toolbar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-20 pb-15 border-bottom" style="gap: 12px;">
        <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
            <div class="avatar-icon bg-light-primary text-primary border-radius-8 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #e8f3ff; color: #1b00ff; font-size: 18px;">
                <i class="fa fa-calculator"></i>
            </div>
            <div>
                <h4 class="text-blue font-16 font-weight-bold mb-0">Firma Teklif İcmali & Kalem Dökümü</h4>
                <small class="text-muted">İcmale dahil etmek istediğiniz teklifleri seçip resmi Fiyat Teklif Formu çıktısı alabilirsiniz.</small>
            </div>
            <div class="d-flex align-items-center" style="gap: 6px;">
                <span class="badge badge-primary font-12 py-1 px-2 border-radius-5" id="icmalOfferCountBadge"><?= count($customerOffers) ?> Teklif</span>
                <span class="badge badge-success font-12 py-1 px-2 border-radius-5" id="icmalSelectedCountBadge"><?= count($customerOffers) ?> Seçili</span>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <button type="button" class="btn btn-sm btn-outline-info" id="btnToggleAllRows" title="Tüm Kalemleri Genişlet / Daralt" style="border-radius: 8px; height: 35px; font-weight: 500;">
                <i class="fa fa-expand mr-1"></i> Tümünü Aç
            </button>

            <!-- Resmi İcmal Yazdır Butonu -->
            <button type="button" class="btn btn-sm btn-primary" id="btnOpenOfficialIcmal" title="Seçilen Teklifleri Resmi İcmal Formatında Yazdır" style="border-radius: 8px; height: 35px; font-weight: 600; box-shadow: 0 2px 6px rgba(59,130,246,0.3);">
                <i class="fa fa-print mr-1"></i> İcmal Yazdır
            </button>

            <?php if (permtrue('data_export_offers')): ?>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportOfficialIcmalExcel()" title="Seçilen Teklifleri Kurumsal İcmal Formatında Excel'e Aktar" style="border-radius: 8px; height: 35px; font-weight: 600;">
                <i class="fa fa-file-excel-o mr-1"></i> Excel İndir
            </button>
            <?php endif; ?>

            <?php if (permtrue("offernew")): ?>
            <a href="index.php?p=offer-new&cid=<?= $cid ?>" target="_blank" class="btn btn-sm btn-success" style="border-radius: 8px; height: 35px; display: inline-flex; align-items: center; font-weight: 500;">
                <i class="fa fa-plus mr-1"></i> Yeni Teklif
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Kartları Grid -->
    <div id="offerIcmalKpiContainer" class="mb-20">
        <style>
            .icmal-kpi-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                width: 100%;
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

            /* Accordion and WYSIHTML5 Styling */
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
                padding: 0 !important;
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

            /* Master Detail Table Styling */
            .table-icmal {
                border: 1px solid #e2e8f0;
                border-collapse: separate;
                border-spacing: 0;
                border-radius: 10px;
                overflow: hidden;
                width: 100%;
            }
            .table-icmal th {
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
            .table-icmal td {
                padding: 11px 8px;
                font-size: 13px;
                vertical-align: middle;
                border-bottom: 1px solid #e2e8f0;
            }

            /* Rounded Stylish Checkboxes */
            .table-icmal input[type="checkbox"],
            .table-icmal .offer-select-cb,
            #selectAllOffers {
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
            #selectAllOffers:hover {
                border-color: #3b82f6 !important;
                background-color: #f8fafc !important;
            }
            .table-icmal input[type="checkbox"]:checked,
            #selectAllOffers:checked {
                background-color: #2563eb !important;
                border-color: #2563eb !important;
            }
            .table-icmal input[type="checkbox"]:checked::after,
            #selectAllOffers:checked::after {
                content: "" !important;
                display: block !important;
                width: 5px !important;
                height: 9px !important;
                border: solid #ffffff !important;
                border-width: 0 2px 2px 0 !important;
                transform: rotate(45deg) !important;
                margin-bottom: 2px !important;
            }
            .table-icmal input[type="checkbox"]:focus-visible,
            #selectAllOffers:focus-visible {
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2) !important;
            }

            .table-icmal tr.offer-row {
                cursor: pointer;
                transition: background-color 0.15s ease;
            }
            .table-icmal tr.offer-row:hover {
                background-color: #f8fafc;
            }
            .table-icmal tr.offer-row.is-open {
                background-color: #eff6ff !important;
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

            /* Official Quote Modal Paper Styling (offer-view.php ile birebir aynı) */
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

            /* Print Isolation Mode */
            @media print {
                html, body {
                    background: #ffffff !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    height: auto !important;
                    min-height: 100% !important;
                    overflow: visible !important;
                }
                .header, .left-side-bar, .main-container > *:not(#modalOfficialIcmalQuote),
                .footer-wrap, .page-header, .mobile-menu-overlay,
                .modal-backdrop, .modal-header, .modal-footer {
                    display: none !important;
                }
                #modalOfficialIcmalQuote {
                    position: static !important;
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    overflow: visible !important;
                    background: transparent !important;
                    box-shadow: none !important;
                    border: none !important;
                    z-index: 999999 !important;
                }
                #modalOfficialIcmalQuote .modal-dialog {
                    max-width: 100% !important;
                    width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    transform: none !important;
                }
                #modalOfficialIcmalQuote .modal-content {
                    border: none !important;
                    box-shadow: none !important;
                    background: transparent !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }
                #modalOfficialIcmalQuote .modal-body {
                    background: transparent !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    overflow: visible !important;
                    max-height: none !important;
                }
                #officialIcmalDocument {
                    position: static !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    border: none !important;
                    box-shadow: none !important;
                    background: transparent !important;
                }
                body * {
                    visibility: visible;
                }
            }
        </style>

        <div class="icmal-kpi-grid">
            <!-- 1. Toplam Teklif & Kalem -->
            <div class="icmal-kpi-card">
                <div class="icmal-kpi-icon" style="background: #e0f2fe; color: #0284c7;">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div class="icmal-kpi-info">
                    <div class="icmal-kpi-title">Toplam Teklif</div>
                    <div class="icmal-kpi-value" id="kpiTotalOffers"><?= (int)($customerOffersSummary['total_count'] ?? 0) ?> Adet</div>
                    <div class="icmal-kpi-sub"><?= (int)($customerOffersSummary['total_items'] ?? 0) ?> Kalem / Hizmet</div>
                </div>
            </div>

            <!-- 2. Teklif Başarı Oranı -->
            <div class="icmal-kpi-card">
                <div class="icmal-kpi-icon" style="background: #dcfce7; color: #16a34a;">
                    <i class="fa fa-check-circle-o"></i>
                </div>
                <div class="icmal-kpi-info">
                    <div class="icmal-kpi-title">Kabul / Onay Durumu</div>
                    <div class="icmal-kpi-value">%<?= number_format((float)($customerOffersSummary['win_rate'] ?? 0), 1) ?></div>
                    <div class="icmal-kpi-sub">
                        <span class="text-success font-weight-bold"><?= (int)($customerOffersSummary['won_count'] ?? 0) ?> Kabul</span> · 
                        <span class="text-warning font-weight-bold"><?= (int)($customerOffersSummary['pending_count'] ?? 0) ?> Bekleyen</span> · 
                        <span class="text-danger font-weight-bold"><?= (int)($customerOffersSummary['lost_count'] ?? 0) ?> Red</span>
                    </div>
                </div>
            </div>

            <!-- 3. Döviz Kırılımı -->
            <div class="icmal-kpi-card">
                <div class="icmal-kpi-icon" style="background: #fef3c7; color: #d97706;">
                    <i class="fa fa-money"></i>
                </div>
                <div class="icmal-kpi-info">
                    <div class="icmal-kpi-title">Döviz Dağılımı</div>
                    <div class="icmal-kpi-value font-15" style="font-size: 14px;">
                        <?php 
                        $eurSum = (float)($customerOffersSummary['total_eur'] ?? 0);
                        $usdSum = (float)($customerOffersSummary['total_usd'] ?? 0);
                        $trySum = (float)($customerOffersSummary['total_try_direct'] ?? 0);
                        echo number_format($trySum, 2, ',', '.') . ' ₺';
                        ?>
                    </div>
                    <div class="icmal-kpi-sub">
                        <?= number_format($eurSum, 2, ',', '.') ?> € · <?= number_format($usdSum, 2, ',', '.') ?> $
                    </div>
                </div>
            </div>

            <!-- 4. Konsolide Toplam -->
            <div class="icmal-kpi-card">
                <div class="icmal-kpi-icon" style="background: #f3e8ff; color: #9333ea;">
                    <i class="fa fa-calculator"></i>
                </div>
                <div class="icmal-kpi-info">
                    <div class="icmal-kpi-title">Konsolide Genel Toplam</div>
                    <div class="icmal-kpi-value text-primary" id="kpiTotalTlAmount" style="color: #6366f1 !important;">
                        <?= number_format((float)($customerOffersSummary['total_tl'] ?? 0), 2, ',', '.') ?> ₺
                    </div>
                    <div class="icmal-kpi-sub">KDV Dahil Net TL Değeri</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreleme & Arama Araç Çubuğu (Özet Kartların Altında) -->
    <div class="icmal-filter-toolbar d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap: 12px; background: #f8fafc; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;">
        <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
            <span class="font-12 weight-600 text-secondary d-flex align-items-center"><i class="fa fa-filter mr-1 text-primary"></i> Durum Filtresi:</span>
            <div style="min-width: 170px;">
                <select id="icmalStatusFilter" class="form-control form-control-sm select2" style="width: 100%;">
                    <option value="all">Tüm Durumlar</option>
                    <option value="1">Bekleyenler</option>
                    <option value="2">Kabul Edilenler</option>
                    <option value="3">Kabul Edilmeyenler</option>
                </select>
            </div>
        </div>

        <div class="d-flex align-items-center" style="gap: 8px;">
            <div class="input-group input-group-sm" style="width: 240px;">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none; border-color: #cbd5e1;"><i class="fa fa-search text-muted"></i></span>
                </div>
                <input type="text" id="icmalSearchInput" class="form-control form-control-sm" placeholder="Teklif no / konu ara..." style="height: 35px; border-radius: 0 8px 8px 0; border-color: #cbd5e1;">
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleOfferIcmalKpi" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; height: 35px; width: 35px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                <i class="fa fa-chevron-up"></i>
            </button>
        </div>
    </div>

    <!-- Üst Bilgi Akordiyon Kartı -->
    <div class="card mb-3 border icmal-accordion-card" style="border-color: #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
        <div class="card-header d-flex align-items-center justify-content-between p-3" 
             style="cursor: pointer;" 
             data-toggle="collapse" data-target="#collapseIcmalHeader" aria-expanded="false" aria-controls="collapseIcmalHeader">
            <div class="d-flex align-items-center" style="gap: 12px;">
                <div style="width: 34px; height: 34px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                    <i class="fa fa-align-left"></i>
                </div>
                <div>
                    <h6 class="mb-0 font-14 weight-600 text-dark">Teklif İcmali Üst Bilgi / Giriş Metni</h6>
                    <small class="text-muted font-11">İcmal belgesi başında yer alacak giriş açıklamasını şablondan seçebilir veya düzenleyebilirsiniz.</small>
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap: 10px;">
                <span class="badge badge-light border font-11 text-muted">Şablon & Düzenleyici</span>
                <i class="fa fa-chevron-down font-12 text-muted accordion-chevron"></i>
            </div>
        </div>
        <div id="collapseIcmalHeader" class="collapse">
            <div class="card-body">
                <div class="form-group mb-3">
                    <label class="weight-600 font-12 mb-1 text-secondary" for="icmalHeaderTemplate">Üst Bilgi Şablonu Seç</label>
                    <div class="input-group">
                        <?php offerTemplate('icmalHeaderTemplate', '', 'Header', 'form-control select2'); ?>
                        <a href="index.php?p=offer-templates&type=Header" target="_blank" class="btn btn-add-template" data-tooltip="Yeni Şablon Eklemek için tıklayınız!" data-tooltip-location="left">
                            <i class="fa fa-plus"></i>
                        </a>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="weight-600 font-12 mb-1 text-secondary" for="icmalHeaderContent">Üst Bilgi Açıklama Metni</label>
                    <div id="icmalHeaderContentWrapper" class="offerHeaderContent html-editor">
                        <textarea id="icmalHeaderContent" name="icmalHeaderContent" class="textarea_editor form-control" placeholder="Üst bilgi açıklaması...">Sayın talep etmiş olduğunuz ürün/hizmetlere ilişkin fiyat teklifimiz aşağıda bilgilerinize sunulmuştur. Fiyatlarımızın makul gelmesini umut eder, iyi çalışmalar dileriz.</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teklif İcmali Tablosu -->
    <?php if (empty($customerOffers)): ?>
        <div class="text-center py-40 border border-radius-8 bg-light">
            <i class="fa fa-folder-open-o text-muted font-40 mb-15"></i>
            <h5 class="text-muted font-16">Bu firmaya ait kayıtlı teklif bulunmamaktadır.</h5>
            <p class="text-muted font-13 mb-15">Yeni bir teklif oluşturarak icmal tablosunu başlatabilirsiniz.</p>
            <?php if (permtrue("offernew")): ?>
                <a href="index.php?p=offer-new&cid=<?= $cid ?>" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fa fa-plus mr-1"></i> İlk Teklifi Oluştur
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-icmal" id="customerOfferSummaryTable">
                <thead>
                    <tr>
                        <th style="width: 36px;" class="text-center">
                            <input type="checkbox" id="selectAllOffers" checked title="Tümünü Seç / Kaldır" style="cursor: pointer; width: 16px; height: 16px;">
                        </th>
                        <th style="width: 36px;" class="text-center">#</th>
                        <th style="width: 105px;">Teklif No</th>
                        <th>Teklif Konusu / Açıklama</th>
                        <th style="width: 95px;">Tarih</th>
                        <th style="width: 75px;" class="text-center">Kalem</th>
                        <th style="width: 105px;" class="text-center">Durum</th>
                        <th style="width: 110px;" class="text-right">KDV / İskonto</th>
                        <th style="width: 125px;" class="text-right">Teklif Tutarı</th>
                        <th style="width: 145px;" class="text-right">Konsolide TL</th>
                        <th style="width: 105px;" class="text-center action-cell">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $runningConsolidatedTotal = 0;
                    $runningItemsTotal = 0;

                    foreach ($customerOffers as $idx => $of): 
                        $oid = (int)$of['id'];
                        $statu = (int)($of['statu'] ?? 1);
                        $itemCount = count($of['items'] ?? []);
                        $runningItemsTotal += $itemCount;

                        $rawTl = !empty($of['tl_toplam_karsilik']) && (float)$of['tl_toplam_karsilik'] > 0
                            ? (float)$of['tl_toplam_karsilik']
                            : (float)($of['total_price'] ?? 0);
                        $runningConsolidatedTotal += $rawTl;

                        // Durum etiketi
                        $statuBadge = '<span class="badge badge-warning text-dark font-11"><i class="fa fa-clock-o mr-1"></i>Bekliyor</span>';
                        if ($statu === 2) {
                            $statuBadge = '<span class="badge badge-success font-11"><i class="fa fa-check mr-1"></i>Kabul Edildi</span>';
                        } elseif ($statu === 3) {
                            $statuBadge = '<span class="badge badge-danger font-11"><i class="fa fa-times mr-1"></i>Kabul Edilmedi</span>';
                        }

                        // Döviz ve KDV tutarı
                        $currencyText = !empty($of['currency']) ? $of['currency'] : 'TRY';
                        $originalPrice = (float)($of['total_price'] ?? 0);
                        $kdvRate = (float)($of['Kdv'] ?? $of['tl_kdv'] ?? 20);
                        if ($kdvRate <= 0) $kdvRate = 20;
                        $iskontoVal = (float)($of['iskonto'] ?? $of['tl_iskonto'] ?? 0);
                        $dateFormatted = !empty($of['created_at']) ? date('d.m.Y', strtotime($of['created_at'])) : '-';
                        $subjectText = !empty($of['offer_subject']) ? $of['offer_subject'] : (!empty($of['description']) ? $of['description'] : 'Teklif #' . $of['offerNumber']);
                        $offerNoClean = $of['offerNumber'] ?? 'TK'.$oid;
                    ?>
                        <!-- Master Teklif Satırı -->
                        <tr class="offer-row" data-offer-id="<?= $oid ?>" data-statu="<?= $statu ?>" data-amount="<?= $rawTl ?>" data-items="<?= $itemCount ?>">
                            <td class="text-center" onclick="event.stopPropagation();">
                                <input type="checkbox" class="offer-select-cb" 
                                       value="<?= $oid ?>" 
                                       data-offer-number="<?= htmlspecialchars($offerNoClean, ENT_QUOTES, 'UTF-8') ?>" 
                                       data-subject="<?= htmlspecialchars($subjectText, ENT_QUOTES, 'UTF-8') ?>" 
                                       data-amount="<?= $rawTl ?>" 
                                       data-currency="<?= htmlspecialchars($currencyText, ENT_QUOTES, 'UTF-8') ?>" 
                                       data-original-price="<?= $originalPrice ?>" 
                                       data-kdv-rate="<?= $kdvRate ?>" 
                                       data-date="<?= $dateFormatted ?>" 
                                       checked 
                                       style="cursor: pointer; width: 16px; height: 16px;">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-expand-row" title="Kalemleri Göster/Gizle">
                                    <i class="fa fa-chevron-right font-10"></i>
                                </button>
                            </td>
                            <td>
                                <a href="index.php?p=offer-view&id=<?= $oid ?>" target="_blank" class="font-weight-bold text-primary" style="text-decoration: none;">
                                    <?= htmlspecialchars($offerNoClean, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td>
                                <div class="font-weight-600 text-dark"><?= htmlspecialchars($subjectText, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($of['creator_name'])): ?>
                                    <small class="text-muted"><i class="fa fa-user mr-1"></i><?= htmlspecialchars($of['creator_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-muted font-12"><i class="fa fa-calendar-o mr-1"></i><?= $dateFormatted ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light border font-11"><?= $itemCount ?> Kalem</span>
                            </td>
                            <td class="text-center">
                                <?= $statuBadge ?>
                            </td>
                            <td class="text-right font-12 text-muted">
                                <?php if ($iskontoVal > 0): ?>
                                    <div class="text-danger" title="İskonto">-%<?= number_format($iskontoVal, 0) ?></div>
                                <?php endif; ?>
                                <div>KDV: %<?= number_format($kdvRate, 0) ?></div>
                            </td>
                            <td class="text-right">
                                <span class="font-weight-600 text-dark">
                                    <?= number_format($originalPrice, 2, ',', '.') ?>
                                </span>
                                <small class="text-muted d-block font-11"><?= htmlspecialchars($currencyText, ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td class="text-right">
                                <span class="font-weight-bold text-primary font-14">
                                    <?= number_format($rawTl, 2, ',', '.') ?> ₺
                                </span>
                            </td>
                            <td class="text-center action-cell">
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?p=offer-view&id=<?= $oid ?>" target="_blank" class="btn btn-outline-primary btn-sm" title="Teklifi Görüntüle">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="index.php?p=offers/offer-manage&id=<?= $oid ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Düzenle / Yönet">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <a href="index.php?p=generate_pdf&id=<?= $oid ?>" target="_blank" class="btn btn-outline-danger btn-sm" title="PDF İndir">
                                        <i class="fa fa-file-pdf-o"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Detay Kalem Satırı (Accordion Child) -->
                        <tr class="offer-details-row" id="offer-details-<?= $oid ?>" style="display: none;">
                            <td colspan="11" class="p-0 border-0">
                                <div class="details-subtable-wrapper">
                                    <div class="d-flex align-items-center justify-content-between mb-10">
                                        <strong class="font-12 text-secondary">
                                            <i class="fa fa-list-ul mr-1 text-primary"></i> <?= htmlspecialchars($offerNoClean, ENT_QUOTES, 'UTF-8') ?> Kalem Dökümü (<?= $itemCount ?> Adet)
                                        </strong>
                                        <span class="font-12 text-muted">
                                            Teklif Toplamı: <strong class="text-dark"><?= number_format($rawTl, 2, ',', '.') ?> ₺</strong>
                                        </span>
                                    </div>

                                    <?php if (empty($of['items'])): ?>
                                        <div class="alert alert-light border font-12 py-2 mb-0">Bu teklife ait alt ürün/hizmet kalemi bulunmamaktadır.</div>
                                    <?php else: ?>
                                        <table class="subtable-items">
                                            <thead>
                                                <tr>
                                                    <th style="width: 35px;" class="text-center">#</th>
                                                    <th style="width: 120px;">Stok Kodu</th>
                                                    <th>Ürün / Hizmet Açıklaması</th>
                                                    <th style="width: 90px;" class="text-right">Miktar</th>
                                                    <th style="width: 70px;">Birim</th>
                                                    <th style="width: 120px;" class="text-right">Birim Fiyat</th>
                                                    <th style="width: 130px;" class="text-right">Satır Toplamı</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach ($of['items'] as $itemIndex => $mat): 
                                                    $itemAmount = (float)($mat['amount'] ?? 1);
                                                    $itemSalePrice = (float)($mat['saleprice'] ?? 0);
                                                    $itemSaleCur = $mat['salecur'] ?? 'TRY';
                                                    $itemTotal = (float)($mat['total_price'] ?? ($itemAmount * $itemSalePrice));
                                                ?>
                                                    <tr>
                                                        <td class="text-center text-muted"><?= $itemIndex + 1 ?></td>
                                                        <td>
                                                            <span class="badge badge-light border font-11">
                                                                <?= !empty($mat['stokKodu']) ? htmlspecialchars($mat['stokKodu'], ENT_QUOTES, 'UTF-8') : '-' ?>
                                                            </span>
                                                        </td>
                                                        <td class="font-weight-500 text-dark">
                                                            <?= htmlspecialchars($mat['title'] ?? 'Ürün / Hizmet', ENT_QUOTES, 'UTF-8') ?>
                                                        </td>
                                                        <td class="text-right font-weight-bold text-dark"><?= number_format($itemAmount, 2, ',', '.') ?></td>
                                                        <td class="text-muted"><?= htmlspecialchars($mat['unit'] ?? 'Adet', ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td class="text-right"><?= number_format($itemSalePrice, 2, ',', '.') ?> <?= htmlspecialchars($itemSaleCur, ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td class="text-right font-weight-bold text-dark"><?= number_format($itemTotal, 2, ',', '.') ?> <?= htmlspecialchars($itemSaleCur, ENT_QUOTES, 'UTF-8') ?></td>
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
                            SEÇİLEN VE FİLTRELENEN İCMAL TOPLAMI:
                        </td>
                        <td class="text-center font-13" id="icmalFooterItems">
                            <?= $runningItemsTotal ?> Kalem
                        </td>
                        <td colspan="3" class="text-right font-12 text-muted">
                            <span id="icmalFooterOffersCount"><?= count($customerOffers) ?></span> Teklif Seçili
                        </td>
                        <td class="text-right font-16 text-primary" id="icmalFooterTotal">
                            <?= number_format($runningConsolidatedTotal, 2, ',', '.') ?> ₺
                        </td>
                        <td class="action-cell"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Alt Bilgi Akordiyon Kartı -->
        <div class="card mt-3 mb-2 border icmal-accordion-card" style="border-color: #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <div class="card-header d-flex align-items-center justify-content-between p-3" 
                 style="cursor: pointer;" 
                 data-toggle="collapse" data-target="#collapseIcmalFooter" aria-expanded="false" aria-controls="collapseIcmalFooter">
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <div style="width: 34px; height: 34px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                        <i class="fa fa-list-ol"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 font-14 weight-600 text-dark">Teklif İcmali Alt Bilgi / Şartlar ve Notlar</h6>
                        <small class="text-muted font-11">İcmal belgesi altında yer alacak şartlar, ödeme ve dipnotları şablondan seçebilir veya düzenleyebilirsiniz.</small>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <span class="badge badge-light border font-11 text-muted">Şablon & Düzenleyici</span>
                    <i class="fa fa-chevron-down font-12 text-muted accordion-chevron"></i>
                </div>
            </div>
            <div id="collapseIcmalFooter" class="collapse">
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="weight-600 font-12 mb-1 text-secondary" for="icmalFooterTemplate">Alt Bilgi Şablonu Seç</label>
                        <div class="input-group">
                            <?php offerTemplate('icmalFooterTemplate', '', 'Footer', 'form-control select2'); ?>
                            <a href="index.php?p=offer-templates&type=Footer" target="_blank" class="btn btn-add-template" data-tooltip="Yeni Şablon Eklemek için tıklayınız!" data-tooltip-location="left">
                                <i class="fa fa-plus"></i>
                            </a>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="weight-600 font-12 mb-1 text-secondary" for="icmalFooterContent">Alt Bilgi Açıklama Metni</label>
                        <div id="icmalFooterContentWrapper" class="offerFooterContent html-editor">
                            <textarea id="icmalFooterContent" name="icmalFooterContent" class="textarea_editor form-control" placeholder="Alt bilgi açıklaması..."><p><strong>1.</strong> Fiyatlarımıza KDV dahildir/dahil edilmiştir.</p><p><strong>2.</strong> Ödeme Vadesi: Sipariş onayı ile birlikte belirlenen ödeme planına göredir.</p><p><strong>3.</strong> Teklif Geçerlilik Süresi: Teklif tarihinden itibaren 15 gündür.</p></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
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
                            <td style="width: 50%; padding: 2px 4px;" id="docMetaCompany"><?= htmlspecialchars($cc['company'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="width: 15%; font-weight: bold; padding: 2px 4px;">Teklif No :</td>
                            <td style="width: 25%; padding: 2px 4px;" id="docMetaOfferNo">İCMAL DOSYASI</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 4px;">Telefon :</td>
                            <td style="padding: 2px 4px;" id="docMetaGsm"><?= htmlspecialchars($cc['gsm'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: bold; padding: 2px 4px;">Tarih :</td>
                            <td style="padding: 2px 4px;" id="docMetaDate"><?= date('d.m.Y') ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 4px;">E Posta :</td>
                            <td style="padding: 2px 4px;" id="docMetaEmail"><?= htmlspecialchars($cc['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: bold; padding: 2px 4px;">Referans :</td>
                            <td style="padding: 2px 4px;">İCMAL DOSYASI</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 2px 4px;">İlgili :</td>
                            <td style="padding: 2px 4px;" id="docMetaYetkili"><?= htmlspecialchars($cc['yetkili'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: bold; padding: 2px 4px;">Teklif Konusu :</td>
                            <td style="padding: 2px 4px;" id="docMetaSubject"><?= htmlspecialchars($cc['company'] ?? '', ENT_QUOTES, 'UTF-8') ?> - İCMAL DOSYASI</td>
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

<script>
$(document).ready(function() {
    // İl ve İlçe Yükleme
    $.getJSON("src/scripts/il-bolge.json", function(sonuc) {
        $.each(sonuc, function(index, value) {
            var option = '<option value="' + value.il + '" data-subtext="' + value.bolge +'"';
            if (value.il === "<?php echo $cc["city"]; ?>") {
                option += ' selected';
            }
            option += '>' + value.il + '</option>';
            $("#il").append(option);
        });
        if ($.fn.selectpicker) {
            $('#il').selectpicker('refresh');
        }
    });

    $("#il").on("change", function() {
        var il = $(this).val();
        $("#ilce").prop("disabled", false);
        var bolge = $(this).find('option:selected').data('subtext');
        $("#region").val(bolge);

        $.getJSON("src/scripts/il-ilce.json", function(sonuc) {
            $("#ilce").empty();
            $.each(sonuc, function(index, value) {
                if (value.il == il) {
                    var row = '<option value="' + value.ilce + '">' + value.ilce + '</option>';
                    $("#ilce").append(row);
                }
            });
            if ($.fn.selectpicker) {
                $('#ilce').selectpicker('refresh');
            }
        });
    });

    // ==========================================
    // TEKLİF İCMALİ JAVASCRIPT ETKİLEŞİMLERİ
    // ==========================================

    // 1. KPI Kartları Toggle & LocalStorage Kalıcılığı
    var icmalKpiStorageKey = 'aydinogullari_kpi_customer_offers_collapsed';
    var isIcmalKpiCollapsed = localStorage.getItem(icmalKpiStorageKey) === 'true';

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

    applyIcmalKpiState(isIcmalKpiCollapsed, false);

    $('#toggleOfferIcmalKpi').on('click', function() {
        var currentCollapsed = $('#offerIcmalKpiContainer').is(':hidden');
        var newCollapsed = !currentCollapsed;
        applyIcmalKpiState(newCollapsed, true);
        localStorage.setItem(icmalKpiStorageKey, newCollapsed);
    });

    // 2. Satır Genişletme / Daraltma (Accordion)
    $('.table-icmal').on('click', '.offer-row', function(e) {
        if ($(e.target).closest('a, button.btn-outline-primary, button.btn-outline-secondary, button.btn-outline-danger, input[type="checkbox"]').length) {
            return;
        }

        var $row = $(this);
        var offerId = $row.data('offer-id');
        var $detailRow = $('#offer-details-' + offerId);

        if ($detailRow.is(':visible')) {
            $detailRow.slideUp(150, function() {
                $detailRow.hide();
            });
            $row.removeClass('is-open');
        } else {
            $detailRow.show().find('.details-subtable-wrapper').hide().slideDown(150);
            $row.addClass('is-open');
        }
    });

    // 3. Tümünü Aç / Kapat Butonu
    var allOpen = false;
    $('#btnToggleAllRows').on('click', function() {
        allOpen = !allOpen;
        if (allOpen) {
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

    // 4. Checkbox Seçim Yönetimi (Tümünü Seç / Kaldır)
    $('#selectAllOffers').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.offer-row:visible .offer-select-cb').prop('checked', isChecked);
        recalculateIcmalTotals();
    });

    $('.table-icmal').on('change', '.offer-select-cb', function() {
        var totalVisibleCb = $('.offer-row:visible .offer-select-cb').length;
        var checkedVisibleCb = $('.offer-row:visible .offer-select-cb:checked').length;
        $('#selectAllOffers').prop('checked', totalVisibleCb > 0 && totalVisibleCb === checkedVisibleCb);
        recalculateIcmalTotals();
    });

    // 5. Dip Toplam ve Seçim Sayaçlarını Yeniden Hesapla
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

    // 6. Select2 ve WYSIHTML5 Zengin Metin Editörü Başlatma
    if ($.fn.select2) {
        $('#icmalStatusFilter').select2({
            minimumResultsForSearch: Infinity,
            width: '100%'
        });
        $('#icmalHeaderTemplate, #icmalFooterTemplate').select2({
            width: '100%'
        });
    }

    if (typeof $.fn.wysihtml5 !== 'undefined') {
        $('#icmalHeaderContent, #icmalFooterContent').each(function() {
            if (!$(this).data('wysihtml5')) {
                $(this).wysihtml5({
                    html: true,
                    fa: true
                });
            }
            $(this).hide();
        });
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

    // Şablon Seçimi Değiştiğinde Ajax ile İçeriği Yükle
    $('#icmalHeaderTemplate').on('change', function() {
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

    $('#icmalFooterTemplate').on('change', function() {
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

    // 7. Arama ve Durum Filtreleme
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

    $('#icmalSearchInput').on('keyup input', filterOfferTable);
    $('#icmalStatusFilter').on('change', filterOfferTable);

    // ==========================================
    // 8. RESMİ İCMAL FİYAT TEKLİF FORMU OLUŞTURMA & YAZDIRMA
    // ==========================================
    $('#btnOpenOfficialIcmal').on('click', function() {
        var $checkedRows = $('.offer-row:visible .offer-select-cb:checked');

        if ($checkedRows.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Teklif Seçilmedi',
                    text: 'Lütfen icmal dökümüne dahil etmek için tablodan en az bir teklif seçiniz.',
                    confirmButtonText: 'Tamam'
                });
            } else {
                alert('Lütfen icmal dökümüne dahil etmek için tablodan en az bir teklif seçiniz.');
            }
            return;
        }

        // Editör içeriklerini modal önizlemesine aktar
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

            var titleText = subject;

            var rowHtml = '<tr class="doc-item-row" style="border-top: 1px solid #808080; border-bottom: 1px solid #808080;">' +
                '<td style="width: 5%; text-align: center;">' + index + '</td>' +
                '<td style="width: 45%; text-align: left; text-transform: uppercase;">' + titleText + '</td>' +
                '<td style="width: 10%; text-align: right;">1 SET</td>' +
                '<td style="width: 20%; text-align: right;">' + amount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TRY</td>' +
                '<td style="width: 20%; text-align: right;">' + amount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TRY</td>' +
                '</tr>';

            $tbody.append(rowHtml);
            index++;
        });

        // Dip toplamları güncelle
        $('#docSubTotalVal').text(totalSubAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TRY');
        $('#docKdvVal').text(totalKdvAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TRY');
        $('#docKdvDahilVal').text(totalGrandAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TRY');
        $('#docGrandTotalVal').text(totalGrandAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TRY');

        // Modalı göster
        $('#modalOfficialIcmalQuote').modal('show');
    });
});

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
        alert('Excel dosyası için en az bir teklif seçiniz.');
        return;
    }

    var form = $('<form>', {
        method: 'POST',
        action: 'pages/1/offers/icmal-to-xls.php',
        target: '_blank'
    });
    form.append($('<input>', { type: 'hidden', name: 'cid', value: '<?= (int)$cid ?>' }));
    form.append($('<input>', { type: 'hidden', name: 'header_content', value: getEditorContent('#icmalHeaderContentWrapper') }));
    form.append($('<input>', { type: 'hidden', name: 'footer_content', value: getEditorContent('#icmalFooterContentWrapper') }));
    selectedIds.forEach(function(id) {
        form.append($('<input>', { type: 'hidden', name: 'offers[]', value: id }));
    });
    form.appendTo('body').trigger('submit').remove();
}

// PDF Olarak Aç / İndir (Dompdf ile doğrudan resmi format ve özel üst/alt bilgi aktarımı)
function openOfficialIcmalPdf() {
    var selectedIds = getSelectedOfferIds();
    var customerId = '<?= $cid ?>';
    
    if (selectedIds.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Teklif Seçilmedi',
                text: 'Lütfen PDF oluşturmak için en az bir teklif seçiniz.'
            });
        } else {
            alert('Lütfen PDF oluşturmak için en az bir teklif seçiniz.');
        }
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

    // POST form submit ile PDF aç
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
    
    // Standart offer-view stillerini yazdırılan iframe içine aktar
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
