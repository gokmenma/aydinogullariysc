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


<style>
    /* Premium customer form styles */
    .customer-manage-wrapper {
        max-width: 1400px;
        margin: 0 auto;
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
    <div class="row mb-4">
        <!-- Toplam Servis Sayısı -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
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
        </div>

        <!-- Toplam Teklif Sayısı -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
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
        </div>

        <!-- Son Oluşturulan Teklif -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
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
        </div>

        <!-- Son Oluşturulan Servis -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
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
    </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="form-card animate-fade-in">
        <div class="form-card-header">
            <div class="card-icon">
                <i class="fa fa-user-plus"></i>
            </div>
            <div>
                <h5>Firma Bilgileri</h5>
                <p>Lütfen firma detaylarını ve iletişim bilgilerini eksiksiz doldurunuz.</p>
            </div>
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
                    <?php echo customer::getCustomerGroups("categoryName", $customer->grp ?? ''); ?>
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
                            <?php echo Helper::selectCity("il", $customer->city ?? 0); ?>
                        </div>
                        <div>
                            <select name="ilce" id="ilce" class="form-control selectpicker" data-live-search="true" data-size="5" data-none-selected-text="Seçim Yapılmadı" data-style="border bg-white" data-container="body">
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
                    <?php echo Helper::selectRegion("region", $customer->region ?? ''); ?>
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
                    <label for="customer_address"><font color="red">(*)</font> Adres</label>
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

<script src="pages/1/customers/customer.js"></script>

