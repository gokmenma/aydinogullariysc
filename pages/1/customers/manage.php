<?php
permcontrol("customeredit");


use App\Helper\customer;
use App\Helper\Helper;
use App\Model\CustomerModel;

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

// $cid = $_GET["id"];
// if (!$cc) {
//     header("Location: index.php?p=customers&err=01735");
//     exit;
// }

$todos = $ac->prepare("SELECT COUNT(*) FROM projects WHERE pcid = ?");
$todos->execute(array($id));
$pjs = $todos->fetchColumn();

$todoso = $ac->prepare("SELECT COUNT(*) FROM offers WHERE cid = ?");
$todoso->execute(array($id));
$ojs = $todoso->fetchColumn();

//son oluşturulan teklif
$sot = $ac->prepare("SELECT * FROM offers WHERE cid = ? ORDER BY id DESC");
$sot->execute(array($id));
$sonteklif = $sot->fetch(PDO::FETCH_ASSOC);

//Son Oluşturulan Servis
$sos = $ac->prepare("SELECT * FROM projects WHERE pcid = ? ORDER BY id DESC");
$sos->execute(array($id));
$ojsp = $sos->fetch(PDO::FETCH_ASSOC);

//Servis Tipi getirilir
if ($ojsp) {
    $sql = $ac->prepare("SELECT * FROM units WHERE id = ? ");
    $sql->execute(array($ojsp["servicestype"]));
    $servicestype = $sql->fetch(PDO::FETCH_ASSOC);
}




if ($_POST) {

    if (!$_POST["company"]) {
        header("Location: index.php?p=customer-edit&cid=$cid&st=empties");
        exit;
    }


    $ccompany = @$_POST["company"];
    $cemail = @$_POST["cemail"];
    $address = @$_POST["customer_address"];
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


<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_customer_manage_stats_collapsed') === 'true') {
                document.documentElement.classList.add('customer-stats-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* Early collapse CSS to prevent flicker */
    .customer-stats-collapsed-early #customerStatsGrid {
        display: none !important;
    }

    /* Premium customer form styles */
    .customer-manage-wrapper {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Stats Grid: header ve form card ile birebir aynı hizada */
    .customer-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    @media (max-width: 1200px) {
        .customer-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .customer-stats-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Inline row within form field (e.g. city/district) */
    .form-field .row-inline {
        display: flex;
        gap: 15px;
    }

    .form-field .row-inline > div {
        flex: 1;
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
        <!-- Toplam Servis Sayısı -->
        <div class="dashboard-card card-blue">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="d-block text-muted font-14 weight-500 mb-1">Toplam Servis Sayısı</span>
                    <span class="no text-blue weight-700 font-30">
                        <?php echo $pjs; ?>
                    </span>
                </div>
                <div class="icon bg-blue text-white box-shadow">
                    <i class="fa fa-gears"></i>
                </div>
            </div>
            <div class="mt-2">
                <a target="_blank" class="small weight-600 font-14 text-blue" href="index.php?p=service/list&cid=<?php echo $id ?>">Tümünü Görüntüle <i class="fa fa-arrow-right ml-1"></i></a>
            </div>
        </div>

        <!-- Toplam Teklif Sayısı -->
        <div class="dashboard-card card-green">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="d-block text-muted font-14 weight-500 mb-1">Toplam Teklif Sayısı</span>
                    <span class="no text-success weight-700 font-30">
                        <?php echo $ojs; ?>
                    </span>
                </div>
                <div class="icon bg-success text-white box-shadow">
                    <i class="fa fa-handshake-o"></i>
                </div>
            </div>
            <div class="mt-2">
                <a target="_blank" class="small weight-600 font-14 text-success" href="index.php?p=offers&cid=<?php echo $id ?>">Tümünü Görüntüle <i class="fa fa-arrow-right ml-1"></i></a>
            </div>
        </div>

        <!-- Son Oluşturulan Teklif -->
        <div class="dashboard-card card-orange">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="d-block text-muted font-14 weight-500 mb-1">Son Oluşturulan Teklif</span>
                    <span class="no text-warning weight-700 font-22">
                        <?php echo $sonteklif["offerNumber"] ?? '-'; ?>
                    </span>
                </div>
                <div class="icon bg-warning text-white box-shadow">
                    <i class="fa fa-file"></i>
                </div>
            </div>
            <div class="mt-2">
                <?php if (!empty($sonteklif["id"])): ?>
                    <a target="_blank" class="small weight-600 font-14 text-warning" href="index.php?p=offers/offer-manage&id=<?php echo $sonteklif["id"]; ?>">Teklife Git <i class="fa fa-arrow-right ml-1"></i></a>
                <?php else: ?>
                    <span class="text-muted small">Teklif bulunamadı</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Son Oluşturulan Servis -->
        <div class="dashboard-card card-purple">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="d-block text-muted font-14 weight-500 mb-1">Son Oluşturulan Servis</span>
                    <span class="no text-purple weight-700 font-22">
                        <?php echo $servicestype["title"] ?? '-'; ?>
                    </span>
                </div>
                <div class="icon bg-purple text-white box-shadow">
                    <i class="fa fa-gear"></i>
                </div>
            </div>
            <div class="mt-2">
                <?php if (!empty($ojsp["id"])): ?>
                    <a target="_blank" class="small weight-600 font-14 text-purple" href="index.php?p=service/manage&id=<?php echo $ojsp["id"]; ?>">Servise Git <i class="fa fa-arrow-right ml-1"></i></a>
                <?php else: ?>
                    <span class="text-muted small">Servis bulunamadı</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="form-card animate-fade-in">
        <div class="form-card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center" style="gap: 12px;">
                <div class="card-icon">
                    <i class="fa fa-user-plus"></i>
                </div>
                <div>
                    <h5>Firma Bilgileri</h5>
                    <p>Lütfen firma detaylarını ve iletişim bilgilerini eksiksiz doldurunuz.</p>
                </div>
            </div>
            <?php if ($id > 0): ?>
            <div>
                <button type="button" id="toggleCustomerStats" class="btn btn-outline-secondary btn-sm" title="Özet Kartlarını Gizle" style="border-radius: 8px; width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;">
                    <i class="fa fa-chevron-up"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>

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

                <!-- Açıklama -->
                <div class="form-field full-width">
                    <label for="cdesc">Açıklama</label>
                    <textarea name="cdesc" id="cdesc" placeholder="Firma hakkında yöneticilerin görebileceği bir not ekleyebilirsiniz." class="form-control" rows="3"><?php echo $customer->cdesc ?? ''; ?></textarea>
                </div>

            </div>
        </form>
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
$(document).ready(function () {
    var STATS_STORAGE_KEY = 'aydinogullari_customer_manage_stats_collapsed';
    var $statsSection = $('#customerStatsGrid');
    var $toggleBtn = $('#toggleCustomerStats');

    function updateStatsToggleState(isCollapsed, animate) {
        document.documentElement.classList.remove('customer-stats-collapsed-early');
        if (isCollapsed) {
            if (animate) {
                $statsSection.stop(true, true).slideUp(200);
            } else {
                $statsSection.hide();
            }
            $toggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $toggleBtn.attr('title', 'Özet Kartlarını Göster');
        } else {
            if (animate) {
                $statsSection.stop(true, true).slideDown(200, function() {
                    $(this).css('display', 'grid');
                });
            } else {
                $statsSection.css('display', 'grid').show();
            }
            $toggleBtn.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $toggleBtn.attr('title', 'Özet Kartlarını Gizle');
        }
    }

    var isSavedCollapsed = localStorage.getItem(STATS_STORAGE_KEY) === 'true';
    updateStatsToggleState(isSavedCollapsed, false);

    $(document).off('click', '#toggleCustomerStats').on('click', '#toggleCustomerStats', function (e) {
        e.preventDefault();
        var currentlyCollapsed = $statsSection.is(':hidden');
        var newState = !currentlyCollapsed;
        localStorage.setItem(STATS_STORAGE_KEY, newState ? 'true' : 'false');
        updateStatsToggleState(newState, true);
    });
});
</script>

