<?php
permcontrol("offeradd");
ini_set('display_errors', 'On');
error_reporting(E_ALL);

//$newoffernumber = "TK" . newNumber("offers");

$getNumber = setNumber("offer");
$newoffernumber = "TK000" . $getNumber;


$regDate = date("d-m-Y");
$offerstatu = 1;
if ($_POST) {

    $urunAdi = $_POST['urunAdi'];
    $urunSayisi = count($urunAdi);

    if (!$_POST["customers"] || $urunSayisi < 1) {
        header("Location: index.php?p=offer-new&st=empties");
        exit;
    }


    $cid = $_POST["customers"];
    $compAuths = $_POST["compAuths"];

    $regDate = date("d-m-Y", strtotime($_POST["regDate"]));
    $creator = sesset("id");
    $currency = $_POST["currency"];
    $payPeriod = $_POST["payPeriod"];
    $offerHeader = $_POST["offerHeader"];
    $offerHeaderContent = $_POST["offerHeaderContent"];
    $offerFooter = $_POST["offerFooter"];
    $offerFooterContent = $_POST["offerFooterContent"];
    $description = $_POST["description"];
    $Kdv = $_POST["Kdv"];
    $iskonto = $_POST["iskonto"];
    $total = $_POST["altToplamInput"];
    $file = $_FILES["offerFile"]["name"];
    $amountTotal = $_POST['amountTotal'];
    $buyTotal = $_POST['buypriceTotal'];
    $saleTotal = $_POST['salepriceTotal'];
    $curDollar = $_POST['cur-Dollar'];
    $curEuro = $_POST['cur-Euro'];
    $DollarTotal = $_POST['DolarAlttoplam'];
    $EuroTotal = $_POST['EuroAlttoplam'];
    $TLTotal = $_POST['TLAlttoplam'];

    $regxs = $ac->prepare("INSERT INTO offers SET cid = ?  , 
												  offerNumber = ? ,
												  company_authors	= ? ,
												  reg_date = ? ,
												  creativer= ? ,
												  currency = ? , 
												  payment_period = ? ,
												  offer_header = ? , 
												  offer_header_content = ? , 
												  offer_footer = ? ,
												  offer_footer_content = ? ,
												  description = ? ,
												  file = ? ,
												  Kdv = ? ,
												  iskonto = ? ,
												  total_price = ? ,
                                                  statu = ? , 
                                                  buyTotal = ? ,
												  saleTotal = ? , 
                                                  amountTotal = ?,
                                                  curDollar = ? ,
                                                  curEuro = ? ,
                                                  DolarTotal = ? ,
                                                  EuroTotal = ? ,
                                                  TLTotal = ? ");

    $regxs->execute(
        array(
            $cid,
            $newoffernumber,
            $compAuths,
            $regDate,
            $creator,
            $currency,
            $payPeriod,
            $offerHeader,
            $offerHeaderContent,
            $offerFooter,
            $offerFooterContent,
            $description,
            $file,
            $Kdv,
            $iskonto,
            $total,
            $offerstatu,
            $buyTotal,
            $saleTotal,
            $amountTotal,
            $curDollar,
            $curEuro,
            $DollarTotal,
            $EuroTotal,
            $TLTotal

        )
    );

    $lastid = $ac->lastInsertId();

    $urunAdi = $_POST['urunAdi'];
    $stokKodu = $_POST['stokKodu'];
    $amount = $_POST['amount'];
    $unit = $_POST['unit'];
    $buyprice = $_POST['buyprice'];
    $buycur = $_POST['buycur'];
    $saleprice = $_POST['saleprice'];
    $salecur = $_POST['salecur'];
    $rowTotal = $_POST['total'];
    $satirno = $_POST['satirno'];

    try {

        if ($lastid > 0) {
            for ($i = 0; $i < count($urunAdi); $i++) {
                if ($urunAdi[$i] != null || $stokKodu[$i] != null || $amount[$i] != null || $saleprice[$i] != null) {

                    $sql = $ac->prepare("INSERT INTO offermatters SET oid = ? ,
																	xid= ? ,
																	stokKodu = ? ,
																	title = ? ,
																	unit = ? ,
																	amount = ? ,
																	buyprice = ? ,
																	buycur = ? ,
																	saleprice = ? ,
																	salecur = ? ,
																	total_price = ? ,
                                                                    satirno = ?");

                    $sql->execute(
                        array(
                            $lastid,
                            $cid,
                            $stokKodu[$i],
                            $urunAdi[$i],
                            $unit[$i],
                            $amount[$i],
                            $buyprice[$i],
                            $buycur[$i],
                            $saleprice[$i],
                            $salecur[$i],
                            $rowTotal[$i],
                            $satirno[$i]
                        )
                    );
                }
            }
        }
    } catch (PDOException $ex) {
        echo "Error: " . $ex->getMessage();
        exit;
    }



    if ($regxs) {
        if (isset($file)) {
            $uploadDir = 'files/offer/'; // Değiştirilmesi gereken dizin
            $uploadPath = $uploadDir . basename($file);
            // Dosyayı belirtilen dizine taşı
            if (move_uploaded_file($_FILES["offerFile"]["tmp_name"], $uploadPath)) {
                echo 'Dosya başarıyla yüklendi ve kaydedildi.';
            }


            header("Location: index.php?p=offer-new&st=newsuccess");
        }
    } else {
        //header("Location: index.php?p=all-offers&st=newerror&code=acmd008");
    }
    $getNumber += 1;
    $upquery = $ac->prepare("UPDATE define_numbers SET offer = ?");
    $upquery->execute(array($getNumber));
}
if (@$_GET["st"] == "newsuccess") {
    showAlert("success", "Teklif Başarı ile eklendi!");
}

if (@$_GET["st"] == "empties") {
    showAlert("alert", "Teklife Ürün eklemeniz gerekmektedir!");
}


?>
<form enctype="multipart/form-data" id="myForm" method="POST">
    <div class="content pd-20 bg-white border-radius-16 box-shadow mb-15">
        <div class="clearfix justify-content-between">
            <div class="pull-left ">

                <style>
                    .btn-xls {
                        background-color: #1F8A70;
                    }

                    .btn-tl {
                        background-color: #4CB9E7;
                    }
                </style>
                <div class="form-group row ml-2">
                    <div class="d-flex flex-column">

                        <h4 class="text-blue m-2">
                            <?php echo $pdat["p_title"] ?>
                        </h4>

                        <div class="">


                            <a type="button" data-tooltip="Teklifi TL'ye Çevir" data-tooltip-location="right"
                                class="float-left btn btn-tl mr-1 text-white"><i class="fa fa-dollar"></i></a>

                            <?php
                            if ($offerstatu == "2") {
                                ?>
                                <a type="button" href="index.php?p=service/manage&oid=<?php echo $oid ?>"
                                    data-tooltip="Servis Oluştur" data-tooltip-location="bottom"
                                    class="float-left btn btn-info mr-1 text-white"><i class="fa fa-gear"></i></a>

                            <?php } ?>
                            <a type="button" data-tooltip="Teklifi Excele Aktar" data-tooltip-location="bottom"
                                class="float-left btn btn-xls mr-1 text-white"><i class="fa fa-file-excel-o"></i></a>

                            <?php
                            if (permtrue("offerview")) { ?>
                                <a type="button" href="index.php?p=offer-detail&id=<?php echo $offer["id"]; ?>"
                                    target="_blank" class="float-left btn btn-warning mr-1" data-tooltip="Teklifi Göster"
                                    data-tooltip-location="bottom"><i class="fa fa-file"></i></a>
                            <?php } ?>
                            <!-- <a type="button" data-tooltip="Teklifi Göster" data-tooltip-location="bottom"
                                class="float-left btn btn-warning mr-1"><i class="fa fa-file"></i></a> -->

                        </div>
                    </div>

                </div>

            </div>

            <div class="pull-right row">

                <!-- 
                    <a id="submitButton" onclick="validateForm()" data-tooltip="Teklifi Kaydet"
                        data-tooltip-location="bottom" class="btn btn-primary text-white">Kaydet</a> -->
                <button id="submitButton" onclick="validateForm()" data-tooltip="Teklifi Kaydet"
                    data-tooltip-location="bottom" class="btn btn-sm btn-primary text-white">
                    <i class="fa fa-save"></i>
                    Kaydet</button>

                <a href="index.php?p=offers" data-tooltip="Listeye Dön" data-tooltip-location="bottom"
                    class="btn btn-sm btn-secondary text-white ml-2 mr-3">
                    <i class="fa fa-list mr-1"></i>Listeye Dön</a>


            </div>
        </div>
        <div class="row">


            <div class="col-md-6 col-sm-12">
                <div class="form-group row">
                    <label for="company" class="col-md-4">
                        <font color="red">(*)</font>Teklif Numarası :
                    </label>
                    <div class="input-group col-md-8">
                        <h5>
                            <?php

                            echo $newoffernumber;
                            ?>
                        </h5>
                    </div>
                    <!-- <input type="text" class="form-control"> -->
                </div>


                <!-- FİRMA ADI -->
                <div class="form-group row">
                    <label for="customers" class="col-sm-12 col-md-4 col-form-label">
                        <font color="red">(*)</font>Firma Adı:
                    </label>
                    <div class="input-group col-sm-12 col-md-8">
                        <select required name="customers" id="customers" title="Seçiniz..."
                            class="selectpicker form-control" data-style="border bg-white" data-size="8"
                            data-live-search="true">
                            <?php
                            $qct = $ac->prepare("SELECT * FROM customers WHERE deleted_at IS NULL ORDER BY id DESC");
                            $qct->execute();
                            while ($cscs = $qct->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <option value="<?php echo $cscs["id"]; ?>">
                                    <?php echo $cscs["company"]; ?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>
                        <?php if (permtrue("customeradd")) { ?>
                            <a href="index.php?p=new-customer" target="_blank"
                                class="btn btn-info btn-sm d-flex align-items-center"
                                data-tooltip="Yeni Firma Eklemek için tıklayınız!">
                                <i class="fa fa-plus"></i></a>
                        <?php } ?>
                    </div>

                </div>


                <!-- FİRMA YETKİLİSİ -->
                <div class="form-group row">
                    <div class="col-md-4">
                        <label for="compAuths" class="col-form-label">
                            <font color="red">(*)</font>Firma Yetkilisi :
                        </label>
                    </div>
                    <div class="col-md-8">

                        <input type="text" class="form-control" placeholder="Yetkili ad soyad" id="compAuths"
                            name="compAuths" value="" required />
                        <!-- <button class="btn btn-info btn-sm" type="button" data-tooltip="Ekle"><i
                        class="fa fa-plus"></i></button> -->
                    </div>
                </div>


                <!-- ÖDEME VADESİ -->
                <div class="form-group row">
                    <div class="col-sm-12 col-md-4">
                        <label for="vade" class="col-form-label">Ödemesi Vadesi :</label>

                    </div>
                    <div class="col-sm-12 col-md-8">
                        <input type="text" id="payPeriod" name="payPeriod" class="form-control" value=""
                            placeholder="Vade giriniz!">

                    </div>
                </div>
                <!-- TARİH -->
                <div class="form-group row">
                    <div class="col-md-4">
                        <label class="col-form-label">
                            <font color="red">(*)</font>Tarih :
                        </label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" id="regDate" name="regDate" value="<?php echo $regDate; ?>"
                            class="form-control date-picker" placeholder="yyyy-aa-gg">
                    </div>
                </div>


                <!-- HAZIRLAYAN -->
                <div class="form-group row">
                    <div class="col-sm-12 col-md-4">
                        <label class="col-form-label">
                            <font color="red">(*)</font>Hazırlayan
                        </label>
                    </div>
                    <div class="col-sm-12 col-md-8">
                        <input disabled readonly class="form-control" value="<?php echo getUsername(sesset("id")); ?>"
                            type="text">
                    </div>

                </div>

                <!-- KUR TÜRÜ -->
                <div class="form-group row">
                    <div class="col-sm-12 col-md-4">
                        <label for="cur" class="col-form-label">Kur Türü :</label>
                    </div>
                    <div class="col-sm-12 col-md-8">
                        <?php KurTuru('currency', "") ?>

                    </div>
                </div>
                <div class="form-group row">

                    <div class="col-sm-12 col-md-4">
                        <label class="col-form-label"> Dolar/Euro : </label>
                    </div>
                    <div class="col-md-4">
                        <input id="cur-Dollar" name="cur-Dollar" value="" readonly value="" class="form-control"
                            type="text">
                    </div>

                    <div class="col-md-4">
                        <input id="cur-Euro" name="cur-Euro" value="" readonly value="" class="form-control"
                            type="text">
                    </div>

                </div>

                <!-- DOSYA -->
                <div class="form-group row">
                    <div class="col-md-4">
                        <label class="col-form-label mt-4">
                            <font color="red">(*)</font>Dosya:
                        </label>
                    </div>
                    <div class="col-md-8">
                        <input type="file" name="offerFile" class="form-control form-control-sm mt-4">
                    </div>
                </div>


                <!-- TEKLİF DURUMU -->
                <div class="form-group row">
                    <div class="col-md-4">
                        <label class="col-form-label mt-4">
                            <font color="red">(*)</font>Teklif Durumu:
                        </label>
                    </div>
                    <div class="col-md-8">
                        <select disabled name="offerstatu" data-style="border bg-white" id="offerstatu"
                            class="selectpicker form-control mt-4">
                            <option selected value="1">Bekleyen</option>
                            <option value="2">Tamamlandı</option>

                        </select>
                    </div>
                </div>

                <!-- NOTLAR -->
                <div class="form-group row">
                    <div class="col-md-4">
                        <label>Notlar</label>
                    </div>
                    <div class="col-md-8">
                        <textarea name="description" value=""
                            placeholder="Teklif hakkında bilgilendirici nitelikte not ekleyiniz."
                            class="form-control"></textarea>
                    </div>
                </div>
            </div>

            <!-- <>
            |
            |İKİNCİ KOLON
            |
            <> -->


            <!-- ÜST BİLGİ -->

            <?php



            ?>
            <div class="col-md-6 col-sm-12">
                <div class="form-group row">
                    <label for="company" class="col-md-4">
                        Üst Bilgi Seç:
                    </label>
                    <div class="input-group col-md-8">

                        <?php offerTemplate("offerHeader", "", "Header"); ?>
                        <a href="index.php?p=offer-templates&type=Header" target="_blank"
                            class="btn btn-secondary btn-sm d-flex align-items-center" type="button"
                            data-tooltip="Yeni Şablon Eklemek için tıklayınız!" data-tooltip-location="left"><i
                                class="fa fa-plus"></i></a>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="offerHeaderContent" class="col-md-4">
                        Üst Bilgi :
                    </label>
                    <div class="col-md-8">
                        <div id="offerHeaderContent" class="offerHeaderContent html-editor">
                            <textarea required id="offerHeaderContent" name="offerHeaderContent" rows="5"
                                class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ...">

                            </textarea>

                        </div>

                    </div>
                </div>

                <!-- ALT BİLGİ -->
                <div class="form-group row">
                    <label for="offerFooter" class="col-md-4">
                        Alt Bilgi Seç:
                    </label>

                    <div class="input-group col-md-8">
                        <?php offerTemplate("offerFooter", "", "Footer"); ?>

                        <a href="index.php?p=offer-templates&type=Footer" target="_blank"
                            class="btn btn-secondary btn-sm d-flex align-items-center" type="button"
                            data-tooltip="Yeni Şablon Eklemek için tıklayınız!" data-tooltip-location="left"><i
                                class="fa fa-plus"></i></a>
                    </div>

                </div>
                <div class="form-group row">
                    <label for="offerFooterContent" class="col-md-4">
                        Alt Bilgi :
                    </label>
                    <div class="col-md-8">
                        <div id="offerFooterContent" class="offerFooterContent html-editor">
                            <textarea required name="offerFooterContent"
                                class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ...">

                            </textarea>
                        </div>
                    </div>
                </div>

            </div>
        </div>



        <br>

    </div>


    <!-- TEKLİF KALEMLERİ ÖZET BİLGİ -->
    <div class="content pd-20 bg-white border-radius-16 box-shadow mb-15">
        <div class="row margin-5 pd-10 justify-content-between">
            <div class="float-left">
                <h4 class="text-blue">
                    Teklif Kalemleri</h4>
            </div>

        </div>
        <?php

        $alisToplam = "0.00";
        $satisToplam = "0.00";
        $KarTL = "0.00";
        $KarOrani = "0 %";

        ?>
        <div class="row ml-0 mr-0 mb-30">
            <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                <div class="sum-primary">

                    <label style="font-weight: 600;" for="">Alış TL</label>
                    <label id="buy-tl" for="">
                        <?php echo tlFormat($alisToplam) ?>
                    </label>
                </div>
            </div>
            <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                <div class="sum-success">

                    <label style="font-weight: 600;" for="">Satış TL</label>
                    <label id="sale-tl" for="">
                        <?php echo tlFormat($satisToplam) ?>
                    </label>
                </div>
            </div>
            <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                <div class="sum-warning">

                    <label style="font-weight: 600;" for="">Kâr TL</label>
                    <label id="profit-tl" for="">
                        <?php echo tlFormat($KarTL) ?>
                    </label>
                </div>
            </div>
            <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                <div class="sum-danger">

                    <label style="font-weight: 600;" for="">Kâr Oranı</label>
                    <label name="profit-rate" id="profit-rate" for="">
                        <?php echo $KarOrani . " %" ?>
                    </label>
                </div>
            </div>
        </div>

        <style>
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
                border: 1px solid #444;

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

            .table>thead {
                background-color: #111;
            }
        </style>

        <!-- TEKLİF KALEMLERİ TABLOSU -->
        <div class="hack1">
            <div class="hack2">
                <table id="kalem_ekle" class="table">
                    <thead>
                        <tr>
                            <th>İşlem</th>
                            <th class="text-center">Sıra</th>
                            <th>Stok Kodu</th>
                            <th>Ürün/Malzeme</th>
                            <th><label for="amount[]" class="m-0">Miktar</label> </th>
                            <th>Birim</th>
                            <th class="text-center"><label for="price[]" class="m-0">Satış Fiyat</label> </th>
                            <th>Para Birimi</th>
                            <th>Toplam Tutar</th>
                            <th class="text-center"><label for="price[]" class="m-0">Alış Fiyat</label> </th>
                            <th>Para Birimi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        $satirNo = 1;

                        $stokKodu = '';
                        $urunAdi = '';
                        $buyprice = '';
                        $saleprice = '';
                        $unit = "";
                        $amount = "";
                        $buycur = "";
                        $salecur = "";
                        $rowTotal = "0.00";
                        include_once "offer-row.php"
                            ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="10">
                                <button type="button" id="ekle" class="btn float-left btn-sm btn-primary mt-3 mb-3">
                                    <i class="fa fa-plus"></i> Yeni Satır
                                </button>

                            </td>
                        </tr>

                        <tr>
                            <input type="hidden" autocomplete="off" class="form-control text-center mr-1"
                                name="buypriceTotal" id="buypriceTotal" value="<?php echo $offer["buyTotal"]; ?>">


                            <input type="hidden" autocomplete="off" class="form-control text-center"
                                name="salepriceTotal" id="salepriceTotal" value="<?php echo $offer["saleTotal"]; ?>">

                            <input type="hidden" autocomplete="off" class="form-control text-center" name="amountTotal"
                                id="amountTotal" value="">

                        </tr>

                    </tfoot>


                </table>

            </div>
        </div>
    </div>
    <input type="hidden" id="rowNumberId" value="<?php echo $satirNo + 1 ?>">



    <div class="content pd-20 bg-white border-radius-16 box-shadow mb-15">
        <div class="row margin-5 pd-10 justify-content-between">
            <div class="float-left">
                <h4 class="text-blue">
                    Alt Toplamlar </h4>
            </div>

        </div>
        <div class="hack1">
            <div class="hack2">
                <table id="tblAltToplam" class="table">
                    <thead>
                        <th style="min-width:120px">Teklifi Göster</th>
                        <th>Euro Toplam</th>
                        <th>Dolar Toplam</th>
                        <th>TL Toplam</th>
                        <th>İskonto Tutarı</th>
                        <th>Ara Toplam</th>
                        <th>Kdv (%)</th>
                        <th>Toplam Tutar(TL)</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="sub-item-view">
                                <span class="badge badge-primary">Teklifi Göster</span>
                            </td>
                            <td>
                                <input type="text" readonly disabled class="form-control" name="EuroAlttoplam"
                                    id="EuroAlttoplam">
                            </td>
                            <td>
                                <input type="text" readonly disabled class="form-control" name="DolarAlttoplam"
                                    id="DolarAlttoplam">
                            </td>
                            <td>
                                <input type="text" readonly disabled class="form-control" name="TLAlttoplam"
                                    id="TLAlttoplam">
                            </td>
                            <td>
                                <input type="text" autocomplete="off" class="form-control text-center" name="iskonto"
                                    value="" id="iskonto">
                            </td>
                            <td>
                                <input type="text" readonly disabled autocomplete="off" class="form-control text-center"
                                    name="araToplam" value="" id="araToplam">
                            </td>
                            <td>

                                <?php KdvOranları("Kdv", "20") ?>
                            </td>

                            <td>
                                <input type="text" autocomplete="off" class="form-control text-center"
                                    name="altToplamInput" id="altToplamInput" value="">
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
    <!-- Tablonun içine eklendiği zaman satır silince diğer satırlarda çalışmıyor -->
    <?php include_once "offer-modal.php"; ?>
</form>
<!--buradan başlıyor-->


<script src="../../include/js/offer.js"></script>
<script>
    $(document).ready(function () {


        $("#kalem_ekle").on("input change", "tr input, tr select", function () {
            updateAltToplam();
        });

        $("#tblAltToplam").on("input change", "tr input, tr select", function () {
            updateAltToplam();
        });

        $("#currency").change(function () {
            getCurrencyData();
            updateAltToplam();
        })
        getCurrencyData();

        $("[id^='buycur'], [id^='salecur']").each(function () {
            $(this).on("change", function () {
                getCurrencyData();
            });
        });

        $('#offerHeader').change(function () {
            var content = $(this); // olay tetiklendiğinde 'this' kullanarak #offerHeader öğesini alıyoruz
            getOfferTemplate(content, "Header");
        });

        $('#offerFooter').change(function () {
            var content = $(this); // olay tetiklendiğinde 'this' kullanarak #offerHeader öğesini alıyoruz
            getOfferTemplate(content, "Footer");
        });
        getOfferTemplate($('#offerHeader'), "Header");
        getOfferTemplate($('#offerFooter'), "Footer");



    });

    $("#customers").on("change", function () {
        getcustomerInfo(this);
    })
</script>
