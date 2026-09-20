<?php
permcontrol("purchaseedit");

$pid = $_GET['id']; // GET parametresinden şifrelenmiş ID'yi al
$demand = @$_GET["demand"];


if ($_POST) {

    $companyID = @$_POST["company"];
    $siparisNo = @$_POST["siparisNo"];
    $altToplam = @$_POST["altToplam"]; //iskonto düşülüp Kdv eklendikten sonraki son fiyat 
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
    $state = @$_POST["state"];
    $invoice_date = @$_POST["invoice_date"];
    $invoice_number = @$_POST["invoice_number"];
    $purchase_type = 2;
    $updater = $_SESSION["lid"];

    // Ürün Bilgileri
    $urunAdi = $_POST['urunAdi'];
    $stokKodu = $_POST["stokKodu"];
    $amounts = $_POST["amount"];
    $units = $_POST["unit"];
    $buyprices = $_POST["buyprice"];
    $buycur = $_POST["buycur"];
    try {
        // if ($companyID == 0 || $altToplam < 1) {
        //     header("Location: index.php?p=purchase-edit&st=empties&id=" . $pid);
        //     exit();

        // }

        if ($demand == true) {
           
            

            $insq = $ac->prepare("INSERT INTO purchases SET siparisNo = ?,  companyID = ?,currency = ? ,deadline = ? , 
												payment_period = ? ,description1 = ? ,description2 = ? ,
												altToplam = ? ,vadeGun= ?,	Dollar = ? ,Euro = ? ,
                                                DolarTotal = ? ,EuroTotal = ? ,TLTotal = ? ,
												Kdv = ? , iskonto = ?, ToplamTL = ?, updater = ?,
                                                state= ?,invoice_date= ?, invoice_number = ? , type = ?");
            $insq->execute(
                array(
                    $siparisNo,
                    $companyID,
                    $currency,
                    $deadline,
                    $payment_period,
                    $description1,
                    $description2,
                    $altToplam,
                    $vadeGun,
                    $curDollar,
                    $curEuro,
                    $DolarTotal,
                    $EuroTotal,
                    $TLTotal,
                    $Kdv,
                    $iskonto,
                    $araToplam,
                    $updater,
                    $state,
                    $invoice_date,
                    $invoice_number,
                    $purchase_type
                )
            );

            //Sipariş Talebi Tamamlandı olarak güncellenir
            $query = $ac->prepare("UPDATE purchases SET state = ? where id = ?");
            $query->execute(array(2, $pid));
            $getNumber += 1;
            $upquery = $ac->prepare("UPDATE define_numbers SET purchase = ?");
            $upquery->execute(array($getNumber));
            //son eklenen kaydın id sini al
            $pid = $ac->lastInsertId();


        } else {
            $insq = $ac->prepare("UPDATE purchases SET  companyID = ?,currency = ? ,deadline = ? , 
                                                payment_period = ? ,description1 = ? ,description2 = ? ,
                                                altToplam = ? ,vadeGun= ?,	Dollar = ? ,Euro = ? ,
                                                DolarTotal = ? ,EuroTotal = ? ,TLTotal = ? ,
                                                Kdv = ? , iskonto = ?, ToplamTL = ?,
                                                state= ?,invoice_date= ?, invoice_number = ? , type = ?
                                                where id = ? ");


            $insq->execute(
                array(
                    $companyID,
                    $currency,
                    $deadline,
                    $payment_period,
                    $description1,
                    $description2,
                    $altToplam,
                    $vadeGun,
                    $curDollar,
                    $curEuro,
                    $DolarTotal,
                    $EuroTotal,
                    $TLTotal,
                    $Kdv,
                    $iskonto,
                    $araToplam,
                    $state,
                    $invoice_date,
                    $invoice_number,
                    $purchase_type,
                    $pid
                )
            );
        }


        if ($insq) {

            //Tablodaki tüm ürünleri sil ve yeni ürünleri ekle
            $sql = $ac->prepare("DELETE FROM purchase_items WHERE purID = ?");
            $sql->execute(array($pid));

            //tablodaki ürünleri say
            $pro = count($urunAdi);
            for ($i = 0; $i < $pro; $i++) {
                $insq = $ac->prepare("INSERT INTO purchase_items SET purID = ?, 
																product = ? ,
                                                                stokKodu = ? , 
																amount = ? , 
																unit = ? , 
																price = ? ,
																currency = ? ");
                $insq->execute(array($pid, $urunAdi[$i], $stokKodu[$i], $amounts[$i], $units[$i], $buyprices[$i], $buycur[$i]));



            };
            
            header("Location: index.php?p=purchase-edit&st=newsuccess&id=" . $pid);

        }
    } catch (PDOException $e) {
        echo "Hata :" . $e->getMessage();
    }
}

if (@$_GET["st"] == "newsuccess") {
    showAlert("success", "Satın Alma Başarı ile güncellendi!");
}

if (@$_GET["st"] == "empties") {
    showAlert('alert', "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.");
}

//güncellenmi verileri tekrar çek
$pid = $_GET['id'];
$sql = $ac->prepare("SELECT * FROM purchases WHERE id = ?");
$sql->execute(array($pid));
$purc = $sql->fetch(PDO::FETCH_ASSOC);

if ($demand == true) {
    $getNumber = setNumber("purchase");
    $siparisNo = "SA000" . $getNumber;
} else {
    $siparisNo = $purc["siparisNo"];
}

?>
<form enctype="multipart/form-data" method="POST" id="myForm">
    <div class="content pd-20 bg-white border-radius-8 box-shadow mb-20">
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
                <a href="index.php?p=purchases" class="btn btn-sm btn-secondary" data-tooltip="Listeye Dön"
                    data-tooltip-location="bottom"><i class="fa fa-list"></i> Listeye Dön</a>
                <button id="submitButton" onclick="validateForm()" data-tooltip="Kaydet" data-tooltip-location="bottom"
                    class="btn btn-sm btn-primary text-white">
                    <i class="fa fa-save"></i>
                    Kaydet</button>

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
                        <input type="text" name="siparisNo" value="<?php echo $siparisNo; ?>" hidden>
                    </div>
                </div>

                <!-- Firma Bilgileri -->
                <div class="form-group row">
                    <label for="company" class="col-md-4">
                        <font color="red">(*)</font>Firma :
                    </label>
                    <div class="input-group col-md-8">
                        <?php customers("company", $purc["companyID"]); ?>



                        <a href="index.php?p=new-customer" target="_blank"
                            class="btn btn-secondary btn-sm d-flex align-items-center" type="button"
                            data-tooltip="Yeni Firma Eklemek için tıklayınız!"><i class="fa fa-plus"></i></a>
                        </a>

                    </div>
                </div>
                <!-- Firma Bilgileri -->

                <!-- Termin Tarihi -->
                <div class="form-group row">
                    <label for="Deadline" class="col-md-4">
                        <font color="red">(*)</font>Termin Tarihi:
                    </label>
                    <div class="col-md-8">
                        <input required class="form-control date-picker" type="text"
                            value="<?php echo $purc["deadline"] ?>" name="deadline" autocomplete="off"
                            placeholder="gg-aa-yyyy">

                    </div>

                </div>
                <!-- Termin Tarihi -->

                <!-- Ödeme Vadesi -->
                <div class="form-group row">
                    <label for="payment_period" class="col-md-4">
                        <font color="red">(*)</font> Ödeme Vadesi:
                    </label>
                    <div class="col-md-4">
                        <input type="number"  required id="payPeriod" name="vadeGun" class="form-control"
                            autocomplete="off" value="<?php echo $purc["vadeGun"] ?>" placeholder="Gün giriniz!">
                    </div>
                    <div class="col-md-4">
                        <input type="text" readonly id="payment_date" name="payment_date" class="form-control"
                            value="<?php echo $purc["payment_period"] ?>">
                    </div>
                </div>
                <!-- Ödeme Vadesi -->


                <!-- Açıklama -->
                <div class="form-group row">
                    <label class="col-md-4">
                        Açıklama 1 :
                    </label>
                    <div class="col-md-8">
                        <textarea name="description1" placeholder="Siparis formunda görünecek açıklama giriniz"
                            class="form-control" type="text"><?php echo $purc["description1"] ?></textarea>
                    </div>
                </div>
                <!-- Açıklama -->

            </div>
            <!-- COLUMN ONE -->

            <!-- COLUMN TWO -->
            <div class="col-md-6">


                <!-- Satın Alma Durumu -->
                <div class="form-group row">
                    <div class="col-md-4 col-sm-12">
                        <label for="AlisFiyati">
                            Durumu :
                        </label>
                    </div>

                    <div class="col-sm-12 col-md-8">
                        <select name="state" id="state" class="selectpicker form-control" data-style="border bg-white">
                            <option <?php echo $purc['state'] == 1 ? 'selected' : '' ?> value="1">Bekliyor</option>
                            <option <?php echo $purc['state'] == 2 ? 'selected' : '' ?> value="2">Tamamlandı</option>
                        </select>
                    </div>
                </div>
                <!-- Satın Alma Durumu -->

                <!-- Fatura Tarihi -->
                <div class="form-group row">
                    <div class="col-md-4 col-sm-12">
                        <label for="invoice_date">
                            Fatura Tarihi :
                        </label>
                    </div>

                    <div class="col-sm-12 col-md-8">
                        <input type="text" class="form-control date-picker" name="invoice_date"
                            value="<?php echo $purc["invoice_date"] ?? ''; ?>">
                    </div>
                </div>
                <!-- Fatura Tarihi -->

                <!-- Fatura Numarası -->
                <div class="form-group row">
                    <div class="col-md-4 col-sm-12">
                        <label for="invoice_number">
                            Fatura Numarası :
                        </label>
                    </div>

                    <div class="col-sm-12 col-md-8">
                        <input type="text" class="form-control" name="invoice_number"
                            value="<?php echo $purc["invoice_number"]; ?>">
                    </div>
                </div>
                <!-- Fatura Numarası -->

                <!-- Para Birimi -->
                <div class="form-group row">
                    <div class="col-md-4 col-sm-12">
                        <label for="AlisFiyati">
                            Kur Türü :
                        </label>
                    </div>

                    <div class="col-sm-12 col-md-8">
                        <?php KurTuru('currency', $purc["currency"]) ?>
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
                        <textarea name="description2" class="form-control"
                            type="text"><?php echo $purc["description2"] ?></textarea>
                    </div>
                </div>
                <!-- Açıklama -->

            </div>
            <!-- COLUMN TWO -->
        </div>
    </div>
    <div class="content pd-20 bg-white border-radius-8 box-shadow mb-20">

        <?php

        $alisToplam = $purc["altToplam"];
        $iskontoToplam = $purc["iskonto"];
        $kdv = $purc["Kdv"];
        $kdvToplam = $purc["ToplamTL"];

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

        <div class="row margin-5 pd-10 justify-content-between">
            <h4 class="text-blue">
                Ürün Bilgileri
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

          /*   .table>thead {
                background-color: #111; 
            }*/
        </style>
        <div class="hack1">
            <div class="hack2">

                <table id="tProduct" class="table">
                    <thead>
                        <tr>
                        <tr>
                        <tr>
                            <th>İşlem</th>
                            <th>Sıra</th>
                            <th>Stok Kodu</th>
                            <th>Ürün Adı</th>
                            <th>Miktar</th>
                            <th>Birim</th>
                            <th>Fiyat</th>
                            <th>Para Birimi</th>
                        </tr>
                        </tr>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        $sql = $ac->prepare("SELECT * FROM purchase_items WHERE purID = ?");
                        $sql->execute(array($pid));
                        $satirNo = 0;
                        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
                            $satirNo += 1;
                            ?>
                            <tr>
                                <?php
                                $stokKodu = $row["stokKodu"];
                                $urunAdi = $row["product"];
                                $buyprice = $row["price"];
                                $unit = $row["unit"];
                                $amount = $row["amount"];
                                $buycur = $row["currency"];
                                include "purchase-row.php";
                                ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8">
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
                <table id="tblAltToplam" class="table">
                    <thead>
                        <th style="min-width:120px">Göster</th>
                        <th>Euro Toplam</th>
                        <th>Dolar Toplam</th>
                        <th>TL Toplam</th>
                        <th>İskonto Toplam</th>
                        <th>Kdv (%)</th>
                        <th>Toplam Tutar(TL) </th>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="sub-item-view">
                                <span class="badge badge-primary">Göster</span>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="EuroAlttoplam" id="EuroAlttoplam"
                                    value="<?php echo $purc["EuroTotal"] ?>">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="DolarAlttoplam" id="DolarAlttoplam"
                                    value="<?php echo $purc["DolarTotal"] ?>">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="TLAlttoplam" id="TLAlttoplam"
                                    value="<?php echo $purc["TLTotal"] ?>">
                            </td>

                            <td>
                                <input type="number" autocomplete="off" class="form-control text-center" id="iskonto" name="iskonto"
                                    value="<?php echo $purc["iskonto"] ?>" id="iskonto">
                            </td>
                            <td>

                                <?php KdvOranları("Kdv", $purc["Kdv"]) ?>
                            </td>
                            <td>
                                <input type="text" autocomplete="off" class="form-control text-center" name="altToplam"
                                    id="altToplamInput" value="<?php echo $purc["altToplam"] ?>">
                                <input type="hidden" id="araToplam" name="araToplam" value="<?php echo $purc["ToplamTL"] ?>">
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


<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> -->
<script src="../../include/js/purchase.js"></script>

<script>
    $(document).ready(function () {

        getCurrencyData();
        $("table").on("input change", "tr input, tr select", function () {
            updateToplamPurchase();
        });

           $("#company").on("change", function () {
        
        getcustomerInfo(this);
    })

        $("#tProduct").on("click", ".sil", function (e) {
            e.preventDefault();
            $(this).closest("tr").remove();
            updateToplamPurchase();
        })

        $("#addRow").click(function () {
            var sayac = $("#rowNumberId");
            purchaseRowAdd(sayac.val());
            sayac.val(parseInt(sayac.val(), 10) + 1);
        })

        $("#currency").change(function () {
            getCurrencyData();
        })

        $("[id^='buycur']").each(function () {
            $(this).on("change", function () {
                updateToplamPurchase();
            });
        });


        $("#payPeriod").on("keyup", function () {
            var paymentDays = parseInt($("#payPeriod").val());
            var futureDate = new Date();
            futureDate.setDate(futureDate.getDate() + paymentDays);
            var formattedDate = formatDate(futureDate);
            $("#payment_date").val(formattedDate);
        });

    });
</script>
<script>
    $(document).on('click', '.selectProduct', function () {
        var buttonId = $(this).attr('id');
        //var idNumarasi = buttonId.replace('rowID', '');
        $('#rowID').val(buttonId);


    });
</script>