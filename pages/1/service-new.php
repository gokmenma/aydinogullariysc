<?php

permcontrol("serviceAdd");
define("MAXSX", set("max_sr"));


use App\Helper\Helper;


// $getNumber = setNumber("service");

// $service_number = "SRV000" . $getNumber;

$getNumber = setNumber("service");

$service_number = "SRV" . str_pad($getNumber, 5, "0", STR_PAD_LEFT);

$comp_name = "";
$comp_city = "";
$comp_region = "";
$comp_ilce = "";


$oid = @$_GET["oid"];
$isOffer = $oid ? "true" : "false";
if ($oid) {
    // OFFER INFO
    $qct = $ac->prepare("SELECT * FROM offers WHERE id= $oid");
    $qct->execute();
    $offer = $qct->fetch(PDO::FETCH_ASSOC);
    // OFFER INFO

    $description = $oid ? $offer["offerNumber"] . " numaralı teklife ait servis." : "";

    // COMPANY INFO
    $cust_id = $offer["cid"];

    $sql = $ac->prepare("SELECT * FROM customers WHERE id = ?");
    $sql->execute(array($cust_id));
    $cust = $sql->fetch(PDO::FETCH_ASSOC);

    $comp_name = $cust["company"];
    $comp_city = $cust["city"];
    $comp_ilce = $cust["ilce"];
    $comp_id = $cust["id"];
    $comp_region = $cust["region"];
    // COMPANY INFO



};

if ($_POST) {

    if (!$_POST["company"] || !$_POST["ServisKonusu"] || !$_POST["TahsilatTuru"] || !$_POST["region"]) {
        header("Location: index.php?p=service-new&st=empties");
        exit;
    }
    // Dosya yükleme başlangıç
    if (@$_POST && @$_FILES["dosya"]["name"]) {
        $dizin = "files/";
        $kaynak = $_FILES["dosya"]["tmp_name"];
        $rast1 = rand(1, 100);
        $hedef = $dizin . $rast1 . "_" . basename($_FILES["dosya"]["name"]);
        $upx = move_uploaded_file($kaynak, $hedef);
        if (@$upx) {
            $ins = $ac->prepare("INSERT INTO files SET
					pid = ?,
					oid = ?,
					filename = ?,
					size = ?,
					creativer = ?");
            $ins->execute(array(@$_POST["company"], @$_POST["offerno"], $rast1 . "_" . basename($_FILES["dosya"]["name"]), $_FILES["dosya"]["size"], sesset("id")));
            //header("Location: index.php?p=all-files&st=newsuccess");

            $soneklenen_dosyaid = $ac->lastInsertId();
        }
    }
    // Dosya yükleme bitiş
    $ost = $ac->prepare("SELECT * FROM offers WHERE id = ?");
    $ost->execute(array($_POST["offerno"]));
    $oms = $ost->fetch(PDO::FETCH_ASSOC);

    $mstm = $ac->prepare("SELECT * FROM customers WHERE id = ?");
    $mstm->execute(array($oms["cid"]));
    $mxm = $mstm->fetch(PDO::FETCH_ASSOC);

    $company = $_POST["company"];
    $offerno = $_POST["offerno"];
    $region = $_POST["region"];
    $servicestype = $_POST["ServisKonusu"];
    $collectiontype = $_POST["TahsilatTuru"];
    $address = $_POST["address"];
    $creativerx = sesset("id");
    $pdesc = $_POST["pdesc"];
    $pstartdate = date_tr($_POST["pstartdate"]);
    $pps = "";
    $price = $_POST["price"];
    $price_desc = $_POST["price_desc"];
    $teklifID = $soneklenen_dosyaid;
    $pnote = addslashes(@$_POST["servicesnote"]);
    $pstatu = $_POST["pstatu"];
    $contract_statu = $_POST["contract_statu"];

    foreach ($_POST["permings"] as $psx) {
        $pps .= $psx . "|";
    }
    $regxs = $ac->prepare("INSERT INTO projects SET
                pcid = ?,
                poid = ?,
				servicestype = ?,
                service_number = ?,
				collectiontype = ?,
				address = ?,
                region = ?,
                pcreativer = ?,
                pdesc = ?,
                pstart_date = ?,
                pauthors = ?,
				price = ?,
                price_desc = ?,
				teklifID = ?,
                pnotes = ?,
                pstatu = ?,
                contract_statu = ?");

    $regxs->execute(array($company, $offerno, $servicestype, $service_number, $collectiontype, $address, $region, $creativerx, $pdesc, $pstartdate, $pps, $price, $price_desc, $teklifID, $pnote, $pstatu, $contract_statu));

    if ($regxs) {
        // $upof = $ac->prepare("UPDATE offers SET statu = ? WHERE id = ?");
        // $upof->execute(array(3, $offerno));
        $getNumber += 1;
        $upquery = $ac->prepare("UPDATE define_numbers SET service = ?");
        $upquery->execute(array($getNumber));
        header("Location:index.php?p=service-new&st=newsuccess");
    } else {
        header("Location: index.php?p=service/list&st=newerror&code=acmd008");
    }
}
if (@$_GET["st"] == "newsuccess") {
 echo Helper::alert('success', 'Servis Başarı ile oluşturuldu!', 'Başarılı!');

    // showAlert('success', 'Servis Başarı ile Oluşturuldu.');
} elseif (@$_GET["st"] == "empties") {
 echo Helper::alert('danger', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.', 'Hata!');

} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "name") {
?>
<div class="alert alert-warning" role="alert">
    Aynı adda bir dosya bulunuyor, lütfen ismini değiştirerek projeyi tekrar oluşturmayı deneyin.
</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "size") {
?>
<div class="alert alert-warning" role="alert">
    <?php echo Helper::alert('danger', 'Yüklediğiniz dosyaın boyutu <b>3 MB</b>\'dan daha büyük olamaz. Servis oluşturulamadı, tekrar deneyin.', 'Hata!');; ?>
    
</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "erno") {
?>
<div class="alert alert-danger" role="alert">
    Servis oluşturuldu ancak, dosya yüklenirken bir problem yaşandı.
</div>
<?php
}

// Servis No oluşturmak
$query = "SELECT id FROM projects ORDER BY id DESC LIMIT 1";
$statement = $ac->prepare($query);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
$last_sno = $result['id'] + 1;


?>

<div class="pd-ltr-20 xs-pd-20-10">
    <div class="min-height-200px">

        <!-- Default Basic Forms Start -->
        <div class="pd-20 bg-white border-radius-16 box-shadow mb-30">
            <div class="clearfix mb-30">
                <div class="pull-left">
                    <h4 class="text-blue">
                        <?php echo $pdat["p_title"]; ?>
                    </h4>
                    <br>
                </div>
                <div class="float-right">

                    <button type="submit" id="submitButton" onclick="validateForm()" class="btn btn-sm btn-primary"><i
                            class="fa fa-save"></i> Kaydet </button>

                    <?php if (permtrue("serviceView")) { ?>
                    <a type="submit" href="index.php?p=service/list" class="btn btn-sm btn-secondary"><i
                            class="fa fa-list"></i> Listeye Dön </a>
                    <?php } ?>
                </div>
            </div>
            <form enctype="multipart/form-data" method="POST" id="myForm">
                <div class="form-group row">
                    <label class="col-md-2"> Servis Numarası : </label>
                    <div class="input-group col-md-4">
                        <h4>
                            <?php echo $service_number; ?>
                        </h4>
                    </div>
                    <label class="col-md-2">
                        <font color="red">(*)</font>Servis Konusu :
                    </label>

                    <!-- Ana sarmalayıcı div -->
                    <div class="col-md-4">

                        <!-- Sadece select ve butonu içeren input-group -->
                        <div class="input-group" style="margin-bottom:0px">
                            <select required name="ServisKonusu" data-live-search="true" data-size="12"
                                id="ServisKonusu" class="selectpicker form-control" data-style="border bg-white"
                                data-max-options="3" title="Servis Konusu Seçiniz!">
                                <?php
                                $sk = $ac->prepare("SELECT * FROM units WHERE statu='2' ");
                                $sk->execute();
                                while ($mm1 = $sk->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <option value="<?php echo $mm1["id"]; ?>">
                                    <?php echo $mm1["title"]; ?>
                                </option>
                                <?php } ?>
                            </select>

                            <!-- Butonun bir 'input-group-append' içinde olması daha standarttır -->
                            <div class="input-group-append">
                                <a type="button" href="index.php?p=servicestype" target="_blank" class="btn btn-success"
                                    data-toggle="tooltip" data-placement="top"
                                    title="Tahsilat Türü Eklemek için tıklayınız!">
                                    <i class="fa fa-plus-circle"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Span'i input-group'un dışına, altına taşıyoruz -->
                        <span class="m-0 p-0 form-text text-danger wait-span" style="display: none;">Sözleşme durumu <strong>Sözleşme Bekliyor</strong> olarak
                            seçildi</span>

                    </div>
                </div>
                <div class="form-group row ">
                    <label class="col-md-2">
                        <font color="red">(*)</font>Firma :

                    </label>
                    <div class="input-group col-md-4">
                        <?php echo customers("company",  $comp_id) ?>

                        <div class="chooseitem">
                            <!-- Button trigger modal -->
                            <a type="button" href="index.php?p=customers/manage" target="_blank" class="btn btn-success"
                                data-tooltip="Yeni Firma Eklemek için tıklayınız!">
                                <i class="fa fa-plus-circle"></i>
                            </a>
                        </div>
                    </div>
                    <label class="col-md-2">
                        <font color="red">(*)</font>Tahsilat Türü :
                    </label>
                    <div class="input-group col-md-4">
                        <select required name="TahsilatTuru" class="selectpicker form-control" data-container="body"
                            data-style="border bg-white" title="Tahsilat Türü Seçiniz!">
                            <?php
                            $tt = $ac->prepare("SELECT * FROM units WHERE statu='3' ");
                            $tt->execute();
                            while ($mm2 = $tt->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <option value="<?php echo $mm2["id"]; ?>">
                                <?php echo $mm2["title"]; ?>
                            </option>
                            <?php } ?>
                        </select>
                        <div class="chooseitem">
                            <!-- Button trigger modal -->
                            <a type="button" href="index.php?p=paytype" target="_blank" class="btn btn-success"
                                data-toggle="tooltip" data-placement="top"
                                title="Tahsilat Türü Eklemek için tıklayınız!">
                                <i class="fa fa-plus-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>


                <div class="form-group row ">
                    <label class="col-md-2">
                        <font color="red">(*)</font>Adres Bölge :
                    </label>
                    <div class="input-group col-md-4">
                        <?php echo Helper::selectRegion("region", $comp_region ?? ''); ?>

                    </div>
                    <label class="col-md-2"> Başlama Tarihi : </label>
                    <div class="input-group col-md-4">
                        <input name="pstartdate" class="form-control date-picker" autocomplete="off"
                            placeholder="Tarih Seçin" type="text">
                    </div>
                </div>
                <div class="form-group row ">
                    <label class="col-md-2"> Adres İl/İlçe : </label>
                    <div class="input-group col-md-4">
                        <input type="text" id="address" readonly name="address"
                            value="<?php echo $comp_city . " / " .  $comp_ilce ?>" class=" form-control">
                    </div>
                </div>
                <div class="form-group row ">
                    <label class="col-md-2"> Servis Yetkilileri : </label>
                    <div class="input-group col-md-4">
                        <select name="permings[]" class="selectpicker form-control" data-style="border bg-white"
                            multiple data-max-options="3">
                            <?php
                            $permq = $ac->prepare("SELECT * FROM userroles ");
                            $permq->execute();
                            while ($pp = $permq->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <optgroup label="<?php echo $pp["roleName"]; ?>">
                                <?php
                                    $permx = $ac->prepare("SELECT * FROM users WHERE permission = ? ");
                                    $permx->execute(array($pp["id"]));
                                    while ($px = $permx->fetch(PDO::FETCH_ASSOC)) { ?>
                                <option value="<?php echo $px["id"]; ?>">
                                    <?php echo $px["username"]; ?>
                                </option>
                                <?php } ?>
                            </optgroup>
                            <?php } ?>
                        </select>
                    </div>
                    <?php if (in_array($_SESSION['lid'], array(1, 2, 3, 4, 6, 10, 11, 12))) { ?>
                    <label class="col-md-2"> Fiyat Bilgisi : </label>
                    <div class="input-group col-md-4">
                        <input name="price" class="form-control" type="text"
                            value="<?php echo $offer["total_price"] ?>">
                    </div>
                </div>
                <?php } ?>
                <?php if (in_array($_SESSION['lid'], array(1, 2, 3, 4, 6, 10, 11, 12))) { ?>
                <div class="form-group row">
                    <div class="col-md-6">
                    </div>
                    <div class="col-md-6 d-flex">
                        <!-- fiyat açıklama -->
                        <label class="col-md-4">Fiyat Açıklaması</label>
                        <div class="col-md-8">

                            <textarea name="price_desc" class="form-control"
                                type="text"><?php echo $cc['price_desc']; ?></textarea>
                        </div>
                    </div>

                </div>
                <?php } ?>
                <div class="form-group row">

                    <label class="col-md-2">Teklif Numarası:</label>
                    <div class="col-md-4">
                        <?php
                    if ($isOffer == "false") { ?>
                        <select id="offerno" name="offerno" class="selectpicker form-control"
                            data-style="border bg-white">
                            <option selected value="<?php echo $oid ?>">
                                <?php echo $offer["offerNumber"] ?>
                            </option>
                        </select>
                        <?php } else { ?>
                        <input name="offerno" class="form-control" type="text" readonly
                            value="<?php echo $offer["offerNumber"] ?>">

                        <?php } ?>

                    </div>

                    <label class="col-md-2">Teklif Dosyası:</label>
                    <div class="col-md-4">
                        <input name="dosya" type="file" class="form-control form-control-sm height-auto">
                    </div>
                    <input type="hidden" name="posted" value="true">
                </div>



                <div class="form-group row">
                    <label for="pdesc" class="col-md-2">
                        Açıklama :
                    </label>

                    <div class="col-md-4">
                        <div id="pdesc" class="pdesc">
                            <textarea id="pdesc" name="pdesc" rows="5"
                                class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ...">
                                <?php echo $description ?>
                              
                            </textarea>

                        </div>

                    </div>

                    <label for="servicesnote" class="col-md-2">
                        Servis Durumu :
                    </label>

                    <div class="col-md-4">
                        <div id="servicesnote" class="servicesnote">
                            <textarea oninput="kontrolEt()" id="servicesnote" name="servicesnote" rows="5"
                                class="textarea_editor form-control border-radius-0"
                                placeholder="Servis sonucu hakkında  bir açıklama ekleyiniz.">


                            </textarea>

                        </div>

                    </div>
                </div>



                <div class="form-group row">
                    <label class="col-md-2">Servis Durumu : </label>
                    <div class="col-md-4">
                        <?php servisDurum("pstatu", "") ?>
                    </div>
                    <label class="col-md-2">Sözleşme Durumu : </label>
                    <div class="col-md-4">
                        <?php sozlesmeDurumu("contract_statu", 4) ?>
                    </div>
                </div>
        </div>
    </div>
</div>



</form>
<input type="hidden" value="<?php echo $isOffer ?>" id="isOffer">


<!-- Şirket seçildiğinde il,ilçe seçimi ve o şirkete ait varsa teklif no seçimi sayfa yenilenmeden -->
<script src="include/js/service.js"></script>

<script>
$(document).ready(function() {
    $(".selectpicker").selectpicker({
        liveSearchPlaceholder: "Ara..",
        noneResultsText: 'Eşleşen kayıt yok {0}',
        noneSelectedText: "Seçim Yapılmadı",
        size: 5,

    });

})

$("#ServisKonusu").change(function() {
    var servisKonusu = $(this).find('option:selected').text().trim();
    if (servisKonusu.toLowerCase().includes('kontrol/raporlama')) {
        $("#contract_statu").val(1);
        $(".wait-span").show();
    } else {
        $("#contract_statu").val(4);
        $(".wait-span").hide();
    }
    $("#contract_statu").selectpicker('refresh');
});
</script>