<?php
permcontrol('serviceEdit');


use App\Helper\Helper;

$sid = $_GET['id'];  // service id
$pis = $sid;
$poid = $_GET['poid'] ?? 0;

$cerq = $ac->prepare('SELECT * FROM projects WHERE id = ?');
$cerq->execute(array($_GET['id']));
$cc = $cerq->fetch(PDO::FETCH_ASSOC);

$pstatus = $_POST['pstatu'] ?? 0;

$secilen_pcid = $cc['pcreativer'];
$qct = $ac->prepare("SELECT * FROM users WHERE id=$secilen_pcid");
$qct->execute();
$cscs = $qct->fetch(PDO::FETCH_ASSOC);

if (!$cc) {
    header('Location: index.php?p=service/list&err=01735');
    exit;
}

if ($_POST) {
    if (!$_POST['company'] || !$_POST['ServisKonusu'] || !$_POST['TahsilatTuru'] || !$_POST["region"]) {
        header('Location: index.php?p=service-edit&st=empties&id=' . $sid);
        exit;
    }

    if (@$_POST['pstatu'] == 18) {
        $kadi = $cscs['username'];
        $datetime = $cc['pregdate'];
        $date = date('d.m.Y H:i:s', strtotime($datetime));
        $servissonucu = @$_POST['servicesnote'];
        $pnote = 'Servis ' . $kadi . ' adlı kullanıcı tarafından ' . $date . ' tarihinde iptal edilmiştir. ' . $servissonucu;
    } else {
        $pnote = addslashes(@$_POST['servicesnote']);
    }
    $company = $_POST['company'];
    $offerno = $_POST['offerno'];
    $servicestype = $_POST['ServisKonusu'];
    $collectiontype = $_POST['TahsilatTuru'];
    $address = $_POST['address'];
    $region = $_POST['region'];
    $updater = sesset('id');
    $update_at = date('Y-m-d H:i:s');
    $pdesc = $_POST['pdesc'];
    $pstartdate = isset($_POST["pstartdate"]) ? date_tr($_POST['pstartdate']) : $cc['pstart_date'];
    $psecond_date = isset($_POST["pseconddate"]) ? date_tr($_POST['pseconddate']) : $cc['psecond_date'];
    $price = $_POST['price'];
    $price_desc = $_POST['price_desc'];
    $teklifID = $soneklenen_dosyaid;
    $pstatu = $_POST['pstatu'];
    $contract_statu = $_POST['contract_statu'];
    $pps = '';
    foreach ($_POST['permings'] as $psx) {
        $pps .= $psx . '|';
    }
    $upxsx = $ac->prepare("UPDATE projects SET
\t\t\t\tpcid = ?,
                poid = ?,
\t\t\t\tservicestype = ?,
\t\t\t\tcollectiontype = ?,
\t\t\t\taddress = ?,
                region =  ?,
                update_at = ?,
                updater = ?,
                pdesc = ?,
\t\t\t\tpstart_date = ?,
\t\t\t\tpsecond_date = ?,
                pauthors = ?,
\t\t\t\tprice = ?,price_desc = ?,
\t\t\t\tpnotes = ?,
                pstatu = ? ,
                contract_statu = ?
                 WHERE id = ?");

    $upxsx->execute(array(
        $company,
        $offerno,
        $servicestype,
        $collectiontype,
        $address,
        $region,
        $update_at,
        $updater,
        $pdesc,
        $pstartdate,
        $psecond_date,
        $pps,
        $price,
        $price_desc,
        $pnote,
        $pstatu,
        $contract_statu,
        $sid
    ));

    if ($upxsx) {
        header("Location: index.php?p=service-edit&id=$sid&up=success&st=yes&mdcode=14");
    } else {
        header('Location: index.php?p=service-edit&st=newerror&code=acmd008');
    }
}
if (@$_GET['st'] == 'yes') {
    showAlert('success', 'Servis Başarı ile Güncellendi.');
}
if (@$_GET['st'] == 'empties') {
    showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
}
if (@$_GET['st'] == 'iptal') {
    showAlert('alert', 'Servis İPTAL edildiği için Servis Sonucu alanına kim tarafından neden iptal edildiği bilgisini giriniz.');
}
$ofinf = $ac->prepare('SELECT * FROM offers WHERE id = ?');
$ofinf->execute(array($cc['poid']));
$ofinfo = $ofinf->fetch(PDO::FETCH_ASSOC);

?>
<form name="myForm" enctype="multipart/form-data" method="POST">
    <div class="pd-ltr-20 xs-pd-20-10">
        <div class="min-height-200px">
            <!-- Default Basic Forms Start -->
            <div class="pd-20 bg-white border-radius-16 box-shadow mb-10">
                <div class="clearfix mb-30">
                    <div class="pull-left">
                        <h4 class="text-blue">
                            <?php echo $pdat['p_title']; ?>
                        </h4>
                        <br>
                    </div>
                    <div class="float-right">

                        <button id="submitButton" onclick="validateForm()" data-tooltip="Kaydet"
                            data-tooltip-location="bottom" class="btn btn-sm btn-primary"><i class="fa fa-save"></i>
                            Kaydet </button>
                        <a href="index.php?p=service/list" data-tooltip="Listeye Dön" data-tooltip-location="bottom"
                            class="btn btn-sm btn-secondary"><i class="fa fa-list"></i> Listeye Dön</a>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-2"> Servis Numarası : </label>
                    <div class="input-group col-md-4">
                        <h4>
                            <?php echo $cc['service_number']; ?>
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
                                <option <?php echo $mm1['id'] == $cc['servicestype'] ? 'selected' : ''; ?>
                                    value="<?php echo $mm1['id']; ?>">
                                    <?php echo $mm1['title']; ?>
                                </option>
                                <?php } ?>
                            </select>

                            <!-- Butonun bir 'input-group-append' içinde olması daha standarttır -->
                            <div class="input-group-append chooseitem">
                                <a type="button" href="index.php?p=servicestype" target="_blank" class="btn btn-success"
                                    data-toggle="tooltip" data-placement="top"
                                    title="Tahsilat Türü Eklemek için tıklayınız!">
                                    <i class="fa fa-plus-circle"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Span'i input-group'un dışına, altına taşıyoruz -->
                        <span class="m-0 p-0 form-text text-danger wait-span" style="display: none;">Sözleşme durumu
                            <strong>Sözleşme Bekliyor</strong> olarak
                            seçildi</span>

                    </div>





                </div>
                <div class="form-group row ">
                    <label class="col-md-2">
                        <font color="red">(*)</font>Firma :
                    </label>
                    <div class="input-group col-md-4">
                        <!-- <select required name="company" id="company" class="form-control" style="font-weight: bold;">
                           
                            <?php
                            $secilen_cid = $cc['pcid'];
                            $qct = $ac->prepare("SELECT * FROM customers WHERE id=$secilen_cid");
                            $qct->execute();
                            $cscs = $qct->fetch(PDO::FETCH_ASSOC)
                            ?>
                            <option value="<?php echo $cscs['id']; ?>">
                                <?php echo $cscs['company']; ?>
                            </option>
                        </select> -->
                        <?php echo customers('company', $cc['pcid']); ?>
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
                        <select required name="TahsilatTuru" id="TahsilatTuru" class="selectpicker form-control"
                            data-container="body" data-style="border bg-white" title="Tahsilat Türü Seçiniz!">
                            <?php
                            $tt = $ac->prepare("SELECT * FROM units WHERE statu='3' ");
                            $tt->execute();
                            while ($mm2 = $tt->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <option <?php echo $mm2['id'] == $cc['collectiontype'] ? 'selected' : ''; ?>
                                value="<?php echo $mm2['id']; ?>">
                                <?php echo $mm2['title']; ?>
                            </option>
                            <?php } ?>
                        </select>
                        <div class="chooseitem">
                            <!-- Button trigger modal -->
                            <a type="button" href="index.php?p=paytype" target="_blank" class="btn btn-success"
                                data-tooltip-location="left" data-tooltip="Tahsilat Türü Eklemek için tıklayınız!">
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
                        <?php echo Helper::selectRegion("region", $cc['region'] ?? ''); ?>

                    </div>
                    <label class="col-md-2"> Başlama Tarihi : </label>
                    <div class="input-group col-md-4">

                        <!-- Bu id'ye sahip kullanıcılar başlama tarihini değiştirebilir  -->
                        <?php if (in_array($_SESSION['lid'], array(4, 6, 12, 11, 28))) { ?>
                        <input name="pstartdate" autocomplete="off" class="form-control date-picker"
                            placeholder="Tarih Seçin" type="text" value="<?php echo $cc['pstart_date']; ?>">
                        <?php } else { ?>
                        <label><?php echo $cc['pstart_date']; ?></label>

                        <?php } ?>
                    </div>
                </div>
                <div class="form-group row ">
                    <label class="col-md-2"> Adres İl/İlçe : </label>
                    <div class="input-group col-md-4">
                        <input type="text" id="address" readonly name="address" value="<?php echo $cc['address'] ?>"
                            class=" form-control">
                    </div>
                    <label class="col-md-2"> 2.Planlama Tarihi : </label>
                    <div class="input-group col-md-4">
                        <?php //if (in_array($_SESSION['lid'], array(4, 6, 11, 12, 28))) { ?>
                        <?php if (permtrue("second_plan_edit")) { ?>
                            <!-- Bu id'ye sahip kullanıcılar 2.planlama tarihini değiştirebilir  -->
                        <input name="pseconddate" autocomplete="off" class="form-control date-picker"
                            placeholder="2.Planlama Tarihi Seçin" type="text"
                            value="<?php echo $cc['psecond_date']; ?>">
                        <?php } else { ?>
                        <label><?php echo $cc['psecond_date']; ?></label>

                        <?php } ?>

                    </div>
                </div>
                <div class="form-group row ">
                    <label class="col-md-2"> Servis Yetkilileri : </label>
                    <div class="input-group col-md-4">
                        <select name="permings[]" class="selectpicker form-control" class="selectpicker form-control"
                            data-container="body" data-style="border bg-white " multiple data-max-options="3">
                            <?php

                            //veritabanından yetkilileri çek
                            $selectedValues = explode('|', $cc['pauthors']);
                            $permq = $ac->prepare('SELECT * FROM perms ');
                            $permq->execute();
                            //while ($pp = $permq->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <optgroup label="<?php echo $pp['p_title']; ?>">
                                <?php
                                $permx = $ac->prepare('SELECT * FROM users ');
                                $permx->execute();
                                while ($px = $permx->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <option <?php
                                            $caks = explode('|', $cc['pauthors']);
                                            foreach ($caks as $kiks) {
                                                if ($kiks == $px['id']) {
                                                    echo 'selected ';
                                                }
                                            }
                                            ?> value="<?php echo $px['id']; ?>">
                                    <?php echo $px['username']; ?>
                                </option>
                                <?php } ?>
                            </optgroup>
                            <?php //} 
                            ?>
                        </select>
                    </div>
                    <?php if (in_array($_SESSION['lid'], array(1, 2, 3, 4, 6, 10, 11, 12,14))) { ?>
                    <label class="col-md-2"> Fiyat Bilgisi : </label>
                    <div class="input-group col-md-4">

                        <input name="price" class="form-control" type="text" value="<?php echo $cc['price']; ?>">
                    </div>
                    <?php } ?>
                </div>
                <?php if (in_array($_SESSION['lid'], array(1, 2, 3, 4, 6, 10, 11, 12,14))) { ?>
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

                        <select id="offerno" name="offerno" class="selectpicker form-control"
                            data-style="border bg-white">
                            <option selected value="<?php echo $ofinfo['id'] ?>">
                                <?php echo $ofinfo['offerNumber'] ?? '' ?>
                            </option>
                        </select>
                    </div>

                    <label class="col-md-2">Teklif Dosyası:</label>
                    <div class="col-md-4">

                        <?php
                        $cq = $ac->prepare('SELECT * FROM files WHERE pid = ? ORDER by id DESC');
                        $cq->execute(array($pis));
                        // $kx = 1;
                        $as = $cq->fetch(PDO::FETCH_ASSOC);
                        if ($as != NULL) {
                        ?>
                        <a href="servicefiles/<?php echo $as['filename']; ?>"><span
                                class="badge badge-success">İndir</span></a>
                        <?php
                        } else
                            echo 'Teklif Dosyası Yok';
                        ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="pdesc" class="col-md-2">
                        Açıklama :
                    </label>

                    <div class="col-md-4">
                        <div id="pdesc" class="pdesc">
                            <textarea id="pdesc" name="pdesc" rows="5"
                                class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ...">

                                <?php echo trim($cc['pdesc']); ?>
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

                                <?php echo trim($cc['pnotes']); ?>
                            </textarea>

                        </div>

                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2">Servis Durumu :
                    </label>
                    <div class="col-md-4">
                        <?php servisDurum('pstatu', $cc['pstatu']) ?>
                    </div>
                    <label class="col-md-2">Sözleşme Durumu : </label>
                    <div class="col-md-4">
                        <?php sozlesmeDurumu('contract_statu', $cc["contract_statu"]) ?>
                    </div>




                </div>

                <div class="row">

                    <div class="col-md-2">
                    </div>
                    <div class="col-md-10">
                        <div class="progress mb-20">
                            <div id="progress-bar" class="progress-bar" role="progressbar " style="width: 0%"
                                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2"></div>
                    <div id="uyari" class="col-md-4"><label for="">asdfas</label></div>
                </div>

            </div>






        </div>
    </div>
    <div class="pd-ltr-20 xs-pd-20-10">
        <div class="min-height-200px">
            <div class="pd-20 bg-white border-radius-16 box-shadow mb-10">
                <div class="row mb-3">
                    <div class="col-md-2">
                        Oluşturan
                    </div>
                    <div class="col-md-10">
                        <?php echo getUserName($cc['pcreativer']); ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-2">
                        Oluşturma Tarihi
                    </div>
                    <div class="col-md-10">
                        <?php echo date_tr($cc['pregdate']); ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-2">
                        Güncelleyen
                    </div>
                    <div class="col-md-10">
                        <?php echo getUserName($cc['updater']); ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-2">
                        Güncelleme Tarihi
                    </div>
                    <div class="col-md-10">
                        <?php echo date_tr($cc['update_at']); ?>
                    </div>
                </div>


            </div>
        </div>

    </div>
</form>
<style>
@keyframes blink {
    0% {
        opacity: 1;
    }

    50% {
        opacity: 0;
    }

    100% {
        opacity: 1;
    }
}

#uyari {
    color: red;
    animation: blink 4s infinite;
    /* Sonsuz döngüde blink animasyonu */
    font-size: 18px;
}
</style>

<script src="include/js/service.js"></script>
<script>
$(document).ready(function() {
    kontrolEt();
})



$("#pstatu").on("change", function() {
    kontrolEt();
})

function kontrolEt() {
    var servicesNote = $("#servicesnote");
    var submitButton = $("#submitButton");
    var selectedStatus = $("#pstatu option:selected").text();
    var yuzde, renk;

    //  alert(selectedStatus);

    if (selectedStatus == 0) {
        yuzde = 0;
        renk = "";
    } else if (selectedStatus === "Bekliyor") {
        yuzde = 33;
        renk = "bg-warning";
    } else if (selectedStatus === "Çalışıyor") {
        yuzde = 66;
        renk = "bg-primary";
    } else if (selectedStatus == "Tamamlandı") {
        yuzde = 100;
        renk = "bg-success";
    } else if (selectedStatus === "İptal Edildi") {
        yuzde = 100;
        renk = "bg-danger"; // Gri renk için boş string
        $("#uyari").html("<b>İptal Edildi</b> seçildiği için <b>Servis Sonucu</b> alanını mutlaka doldurunuz...!")
        if (servicesNote.val() !== '') {
            $("#submitButton").prop("disabled", true); // Butonu pasif yap
        } else {
            $("#submitButton").prop("disabled", false); // Butonu pasif yap
        }


    }
    if (selectedStatus !== "İptal Edildi") {
        $("#uyari").html('');
        $("#submitButton").prop("disabled", false); // Butonu pasif yap
        //servicesNote.val('');
    }
    $('#progress-bar').css('width', yuzde + '%').attr('aria-valuenow', yuzde).removeClass().addClass('progress-bar ' +
        renk);

};


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