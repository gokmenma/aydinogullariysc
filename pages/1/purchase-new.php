<?php

permcontrol("purchaseadd");
//$siparisNo = "SA" . newNumber("purchases");
$getNumber = setNumber("purchase");
$siparisNo = "SA000" . $getNumber;

if ($_POST) {


	$companyID = @$_POST["company"];
	$altToplam = @$_POST["altToplam"];
	$currency = @$_POST["currency"];
	$deadline = @$_POST["deadline"];
	$description1 = @$_POST["description1"];
	$description2 = @$_POST["description2"];
	$curDollar = @$_POST["curDollar"];
	$vadeGun = @$_POST["vadeGun"];
	$payment_period = @$_POST["payment_date"];
	$curEuro = @$_POST["curEuro"];
	$DolarTotal = @$_POST["DolarAlttoplam"];
	$EuroTotal = @$_POST["EuroAlttoplam"];
	$TLTotal = @$_POST["TLAlttoplam"];
	$Kdv = @$_POST["Kdv"];
	$iskonto = @$_POST["iskonto"];
    $araToplam = @$_POST["araToplam"]; // Kdv ve iskontosuz fiyat
    $creator =  $_SESSION["lid"];
	

	// Ürün Bilgileri
	$urunAdi = $_POST['urunAdi'];
	$stokKodu = $_POST["stokKodu"];
	$amounts = $_POST["amount"];
	$units = $_POST["unit"];
	$buyprices = $_POST["buyprice"];
	$buycur = $_POST["buycur"];


	if (
		$companyID == 0 || $altToplam < 1 || $urunAdi == null ||
		$amounts == null ||
		$vadeGun == null
	) {
		header("Location: index.php?p=purchase-new&st=empties");
		exit();
	}


	$insq = $ac->prepare("INSERT INTO purchases SET companyID = ? , currency = ? ,siparisNo= ? , 
													deadline = ? , payment_period = ? , description1 = ? , 
													description2 = ? ,creator = ? , altToplam = ? ,ToplamTL = ? ,
													vadeGun= ?,	Dollar = ? ,Euro = ? ,
													DolarTotal = ? ,EuroTotal = ? ,TLTotal = ? ,
													Kdv = ? , iskonto = ?, 
													state= ? ");
	$insq->execute(
		array(
			$companyID,
			$currency,
			$siparisNo,
			$deadline,
			$payment_period,
			$description1,
			$description2,
			$creator,
			$altToplam,
            $araToplam ,
			$vadeGun,
			$curDollar,
			$curEuro,
			$DolarTotal,
			$EuroTotal,
			$TLTotal,
			$Kdv,
			$iskonto,
			1
		)
	);
	$lastid = $ac->lastInsertId();
	// Veritabanı işlemleri
	if ($lastid != null) {
		for ($i = 0; $i < count($urunAdi); $i++) {
			$insq = $ac->prepare("INSERT INTO purchase_items SET purID = ?, 
																stokKodu = ? ,
																product = ? , 
																amount = ? , 
																unit = ? , 
																price = ? ,
																currency = ? ");
			$insq->execute(array($lastid, $stokKodu[$i], $urunAdi[$i], $amounts[$i], $units[$i], $buyprices[$i], $buycur[$i]));
		}
	}
	if ($insq) {
		header("Location: index.php?p=purchase-new&st=newsuccess");
	}
	$getNumber += 1;
        $upquery = $ac->prepare("UPDATE define_numbers SET purchase = ?");
        $upquery->execute(array($getNumber));

}


if (@$_GET["st"] == "empties") {

	showAlert('alert', "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.");
}
if (@$_GET["st"] == "newsuccess") {

	showAlert('success', "Bilgiler kaydedildi.");

}
if (@$_GET["st"] == "numericerror") {

	showAlert('warning', "Fiyat kısmına sadece rakamlardan oluşan değer girebilirsiniz.");
}
?>


<form enctype="multipart/form-data" method="POST" id="myForm">
    <div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
        <div class="clearfix">
            <div class="pull-left">
                <h4 class="text-blue">
                    <?php global $pdat; echo $pdat["p_title"] ?? 'Yeni Satın Alma'; ?>
                </h4>
                <p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
                    bırakmayın..<br>
                </p>
            </div>
            <div class="float-right">
                <button id="submitButton" onclick="validateForm()" data-tooltip="Kaydet" data-tooltip-location="bottom"
                    class="btn btn-sm btn-primary text-white">
                    <i class="fa fa-save"></i>
                    Kaydet</button>
                <a href="index.php?p=purchases" class="btn btn-sm btn-secondary" data-tooltip="Listeye Dön"
                    data-tooltip-location="bottom"><i class="fa fa-list"></i> Listeye Dön</a>

            </div>
        </div>


        <div class="row">


            <!-- COLUMN ONE -->
            <div class="col-md-6">
                <div class="form-group row">
                    <label for="" class="col-md-4">
                        <font color="red">(*)</font>Sipariş Numarası :
                    </label>
                    <div class="input-group col-md-8">
                        <h5>
                            <?php echo $siparisNo; ?>
                        </h5>
                    </div>
                </div>

                <!-- Firma Bilgileri -->
                <div class="form-group row">
                    <label for="company" class="col-md-4">
                        <font color="red">(*)</font>Firma :
                    </label>
                    <div class="input-group col-md-8">

                        <select required name="company" id="company" class="selectpicker form-control" data-live-search="true"
                            data-style="border bg-white" data-size="8" data-container="body">
                            <?php
							$ms = $ac->prepare("SELECT * FROM customers ");
							$ms->execute();
							echo "<option selected disabled value=''>Firma Seçiniz</option>";
							while ($mm = $ms->fetch(PDO::FETCH_ASSOC)) {

								?>
                            <option value="<?php echo $mm["id"]; ?>">
                                <?php echo $mm["company"]; ?>
                            </option>
                            <?php

							}
							?>
                        </select>

                        <a href="index.php?p=new-customer" target="_blank"
                            class="btn btn-secondary btn-sm d-flex align-items-center" type="button"
                            data-tooltip="Yeni Firma Eklemek için tıklayınız!"><i class="fa fa-plus"></i></a>
                        </a>

                    </div>
                </div>
                <!-- Firma Bilgileri -->

                <!-- Termin Tarihi -->
                <div class="form-group row">
                    <label for="deadline" class="col-md-4">
                        <font color="red">(*)</font>Termin Tarihi:
                    </label>
                    <div class="col-md-8">
                        <input required  class="form-control date-picker" type="text" value="<?php echo date("d-m-Y") ?>"
                            name="deadline" autocomplete="off" placeholder="gg-aa-yyyy">

                    </div>

                </div>
                <!-- Termin Tarihi -->

                <!-- Ödeme Vadesi -->
                <div class="form-group row">
                    <label for="vadeGun" class="col-md-4">
                        <font color="red">(*)</font> Ödeme Vadesi:
                    </label>
                    <div class="col-md-4">
                        <input type="number" required id="payPeriod" name="vadeGun" class="form-control"
                            autocomplete="off" placeholder="Gün giriniz!">
                    </div>
                    <div class="col-md-4">
                        <input type="text" readonly id="payment_date" name="payment_date" class="form-control"
                            value="<?php echo date("d-m-Y") ?>">
                    </div>
                </div>
                <!-- Ödeme Vadesi -->


                <!-- Açıklama -->
                <div class="form-group row">
                    <label class="col-md-4">
                        Açıklama 1 :
                    </label>
                    <div class="col-md-8">
                        <textarea name="description1" value="" placeholder="Siparis formunda görünecek açıklama giriniz"
                            class="form-control" type="text"></textarea>
                    </div>
                </div>
                <!-- Açıklama -->

            </div>
            <!-- COLUMN ONE -->

            <!-- COLUMN TWO -->
            <div class="col-md-6">

                <!-- Para Birimi -->
                <div class="form-group row">
                    <div class="col-md-4 col-sm-12">
                        <label for="AlisFiyati">
                            Kur Türü :
                        </label>
                    </div>

                    <div class="col-sm-12 col-md-8">
                        <?php KurTuru('currency', "") ?>
                    </div>
                </div>
                <!-- Para Birimi -->

                <!-- Kur Bilgileri -->
                <div class="form-group row">
                    <div class="col-md-4 col-sm-12">
                        <label for="AlisFiyati">
                            Dolar / Euro :
                        </label>
                    </div>

                    <div class="col-sm-12 col-md-4">
                        <input type="text" readonly class="form-control" id="cur-Dollar" name="curDollar">
                    </div>
                    <div class="col-sm-12 col-md-4">
                        <input type="text" readonly class="form-control" id="cur-Euro" name="curEuro">
                    </div>
                </div>
                <!-- Kur Bilgileri -->

                <!-- Açıklama -->
                <div class="form-group row">
                    <label class="col-md-4">
                        Açıklama 2 :
                    </label>
                    <div class="col-md-8">
                        <textarea name="description2" value="" class="form-control" type="text"></textarea>
                    </div>
                </div>
                <!-- Açıklama -->

            </div>
            <!-- COLUMN TWO -->
        </div>
    </div>


    <div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">

        <?php

		$alisToplam = "0.00";
		$iskontoToplam = "0.00";
		$kdv = "0.00";
		$kdvToplam = "0";

		?>
        <div class="row ml-0 mr-0 mb-30">
            <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                <div class="sum-primary">

                    <label style="font-weight: 600;" for="">Tutar TL</label>
                    <label id="buy-tl" for="">
                        <?php echo $alisToplam ?>
                    </label>
                </div>
            </div>

            <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                <div class="sum-warning">

                    <label style="font-weight: 600;" for="">KDV Oranı(%)</label>
                    <label id="kdv-rate" for="">
                        <?php echo $kdv ?>
                    </label>
                </div>
            </div>
            <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                <div class="sum-success">

                    <label style="font-weight: 600;" for="">İskonto TL</label>
                    <label id="discount" for="">
                        <?php echo $iskontoToplam ?>
                    </label>
                </div>
            </div>

            <div class="pd-5 col-lg-3 col-md-6 col-sm-12 mb-5">
                <div class="sum-danger">

                    <label style="font-weight: 600;" for="">KDV Dahil TL</label>
                    <label name="lblTotalTL" id="lblTotalTL" for="">
                        <?php echo $kdvToplam ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Sipariş ürünleri -->

        <div class="row margin-5 pd-10 justify-content-between">
            <h4 class="text-blue">
                Ürün Bilgileri
            </h4>

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
        <div class="hack1">
            <div class="hack2">

                <table id="tProduct" class="table no-filter">
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

                    <tbody>
                        <tr>

                            <?php
							$satirNo = 1;
							$satirNo = 1;

							$stokKodu = '';
							$urunAdi = '';
							$buyprice = '';
							$saleprice = '';
							$unit = "";
							$amount = "";
							$buycur = "";
							include_once "purchase-row.php" ?>

                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="9">
                                <button type="button" id="addRow" class="btn float-left btn-sm btn-primary mt-3 mb-3">
                                    <i class="fa fa-plus"></i> Yeni Satır
                                </button>

                            </td>
                        </tr>

                    </tfoot>
                </table>
                <input type="hidden" id="rowNumberId" value="<?php echo $satirNo + 1 ?>">

            </div>
        </div>
    </div>
    <div class="content pd-20 bg-white border-radius-16 box-shadow mb-15">
        <div class="row margin-5 pd-10 justify-content-between">
            <div class="float-left">
                <h4 class="text-blue">
                    Alt Toplamlar </h4>
            </div>

        </div>
        <div class="hack1">
            <div class="hack2">
                <table id="tblAltToplam" class="table no-filter">
                    <thead>
                        <th style="min-width:120px" class="no-filter">Göster</th>
                        <th class="no-filter">Euro Toplam</th>
                        <th class="no-filter">Dolar Toplam</th>
                        <th class="no-filter">TL Toplam</th>
                        <th class="no-filter">İskonto Toplam</th>
                        <th class="no-filter">Kdv (%)</th>
                        <th class="no-filter">Toplam Tutar(TL)</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="sub-item-view">
                                <span class="badge badge-primary">Göster</span>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="EuroAlttoplam" id="EuroAlttoplam"
                                    value="<?php echo $offer["EuroTotal"] ?>">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="DolarAlttoplam" id="DolarAlttoplam"
                                    value="<?php echo $offer["DolarTotal"] ?>">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="TLAlttoplam" id="TLAlttoplam"
                                    value="<?php echo $offer["TLTotal"] ?>">
                            </td>
                           
                            <td>
                                <input type="number" autocomplete="off" class="form-control text-center" name="iskonto"
                                    value="" id="iskonto">
                            </td>
                            <td>
                               
                               <?php KdvOranları("Kdv","20") ?>
                       </td>
                            <td>
                            <input type="text" autocomplete="off" class="form-control text-center" name="altToplam"
                                    id="altToplamInput" value="">
                                    <input type="hidden" id="araToplam" name="araToplam" value="">
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>
</form>

<!-- Modal: Ürün Seçim Modali -->
<div class="modal fade" id="staticBackdrop" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel">Listeden ürün seçiniz!</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php generateProductSelect("productName[]", null) ?>
                <input type="hidden" id="rowID">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-danger" onclick="getProductInfoPurchase()">Seç</button>
            </div>
        </div>
    </div>
</div>


<script src="../../include/js/purchase.js"></script>
<script>
$(document).ready(function() {

    $("#company").on("change", function () {
        getcustomerInfo(this);
    })

    getCurrencyData();
    $("table").on("input change", "tr input, tr select", function() {
        updateToplamPurchase();
    });

    $("#tProduct").on("click", ".sil", function(e) {
        e.preventDefault();
        $(this).closest("tr").remove();
        updateToplamPurchase();
    })

    $("#addRow").click(function() {
        var sayac = $("#rowNumberId");
        purchaseRowAdd(sayac.val());
        sayac.val(parseInt(sayac.val(), 10) + 1);
    })

    $("#currency").change(function() {
        getCurrencyData();
    })

    $("[id^='buycur']").each(function() {
        $(this).on("change", function() {
            updateToplamPurchase();
        });
    });


    $("#payment_period").on("keyup", function() {
        var paymentDays = parseInt($("#payment_period").val());
        var futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + paymentDays);
        var formattedDate = formatDate(futureDate);
        $("#payment_date").val(formattedDate);
    });

});
</script>
<script>
$(document).on('click', '.selectProduct', function() {
    var buttonId = $(this).attr('id');
    //var idNumarasi = buttonId.replace('rowID', '');
    $('#rowID').val(buttonId);


});
</script>