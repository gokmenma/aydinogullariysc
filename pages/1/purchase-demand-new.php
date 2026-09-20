<?php

permcontrol("purchase-demand-add");


$getNumber = setNumber("purchase_demand");
$siparisNo = "ST000" . $getNumber;

if ($_POST) {

    $customer = @$_POST["customer"];
    $aciliyet = @$_POST["aciliyet"];
    $altToplam = @$_POST["altToplam"];
    $description = @$_POST["description"];
    $curEuro = @$_POST["curEuro"];
    $DolarTotal = @$_POST["DolarAlttoplam"];
    $EuroTotal = @$_POST["EuroAlttoplam"];
    $TLTotal = @$_POST["TLAlttoplam"];
    $Kdv = @$_POST["Kdv"];
    $iskonto = @$_POST["iskonto"];
    $ToplamTL = @$_POST["altToplam"];
    $creator =  $_SESSION["lid"];

    // Ürün Bilgileri
    $urunAdi = $_POST['urunAdi'];
    $stokKodu = $_POST["stokKodu"];
    $amounts = $_POST["amount"];
    $units = $_POST["unit"];
    $buyprices = $_POST["buyprice"];
    $buycur = $_POST["buycur"];
    $type = 1;


    // if (
    //     $altToplam < 1 || $urunAdi == null
    // ) {
    //     header("Location: index.php?p=purchase-demand-new&st=empties");
    //     exit();
    // }



    try {

        $insq = $ac->prepare("INSERT INTO purchases SET companyID = ? , siparisNo = ? , aciliyeti = ? ,
													description1 = ? ,creator = ? , altToplam = ? ,
													DolarTotal = ? ,EuroTotal = ? ,TLTotal = ? ,
													state= ? , type= ?  ");
        $insq->execute(
            array(
                $customer,
                $siparisNo,
                $aciliyet,
                $description,
                sesset("id"),
                $altToplam,
                $DolarTotal,
                $EuroTotal,
                $TLTotal,
                0,
                $type
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
            header("Location: index.php?p=purchase-demand-new&st=newsuccess");
        };
        $getNumber += 1;
        $upquery = $ac->prepare("UPDATE define_numbers SET purchase_demand = ?");
        $upquery->execute(array($getNumber));

    } catch (PDOException $e) {
        echo "Hata : " . $e->getMessage();
    }

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
<style>
    /* Table column overrides to make first 3 columns minimum size */
    #tProduct th:nth-child(1),
    #tProduct td:nth-child(1) {
        width: 28px !important;
        min-width: 28px !important;
        max-width: 28px !important;
        text-align: center;
        padding: 4px 2px !important;
        vertical-align: middle !important;
    }
    #tProduct td:nth-child(1) a {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        background: transparent !important;
        color: #888 !important;
        width: 24px !important;
        height: 24px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: none !important;
    }
    
    #tProduct th:nth-child(2),
    #tProduct td:nth-child(2) {
        width: 32px !important;
        min-width: 32px !important;
        max-width: 32px !important;
        text-align: center;
        padding: 4px 2px !important;
        vertical-align: middle !important;
    }
    #tProduct td:nth-child(2) a.sil {
        padding: 0 !important;
        margin: 0 !important;
        width: 24px !important;
        height: 24px !important;
        line-height: 24px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 4px !important;
        font-size: 11px !important;
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #fff !important;
    }
    
    #tProduct th:nth-child(3),
    #tProduct td:nth-child(3) {
        width: 40px !important;
        min-width: 40px !important;
        max-width: 40px !important;
        text-align: center;
        padding: 4px 2px !important;
        vertical-align: middle !important;
    }
    #tProduct td:nth-child(3) input {
        text-align: center !important;
        padding: 2px !important;
        height: 24px !important;
        width: 100% !important;
        font-size: 11px !important;
        margin: 0 auto !important;
        display: block !important;
    }
    
    #tProduct td {
        vertical-align: middle !important;
    }
    
    .app-item-name {
        min-width: 180px !important;
        width: 25% !important;
    }
    .input-group {
        margin-bottom: 0px !important;
    }
</style>

<form enctype="multipart/form-data" method="POST" id="myForm">
    <div class="purchase-demand-manage-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php global $pdat; echo $pdat["p_title"] ?? 'Yeni Satın Alma Talebi'; ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-tag"></i> Sipariş No: <?php echo $siparisNo; ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=purchases" class="btn-header btn-header-list mr-2">
                        <i class="fa fa-list"></i> Listeye Dön
                    </a>
                    <button type="button" id="submitButton" onclick="validateForm()" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>

        <!-- Kart 1: Talep Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div>
                    <h5>Talep Bilgileri</h5>
                    <p>Satın alma talebine ait genel firma ve kur detayları</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="customer">Firma</label>
                    <div style="width: 100%;">
                        <?php customers("customer", "", ""); ?>
                    </div>
                </div>
                <div class="form-field">
                    <label for="currency">Kur Türü</label>
                    <div style="width: 100%;">
                        <?php KurTuru('currency', "") ?>
                    </div>
                </div>
                <div class="form-field">
                    <label for="aciliyet">Aciliyet Durumu</label>
                    <div style="width: 100%;">
                        <?php aciliyet_durumu('aciliyet', "") ?>
                    </div>
                </div>
                <div class="form-field">
                    <label>Dolar / Euro</label>
                    <div class="input-group" style="margin-bottom: 0px !important;">
                        <input type="text" readonly class="form-control" id="cur-Dollar" name="curDollar" placeholder="Dolar" style="margin-right: 10px;">
                        <input type="text" readonly class="form-control" id="cur-Euro" name="curEuro" placeholder="Euro">
                    </div>
                </div>
                <div class="form-field full-width">
                    <label for="description">Açıklama</label>
                    <textarea name="description" id="description" placeholder="Talep formunda görünecek açıklama giriniz" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>

        <!-- Kart 2: Ürün Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-green">
                    <i class="fa fa-cubes"></i>
                </div>
                <div>
                    <h5>Ürün Bilgileri</h5>
                    <p>Talep edilen ürünlerin listesi, miktar ve fiyatları</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="tProduct" class="table premium-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">Taşı</th>
                            <th style="width: 8%;">İşlem</th>
                            <th style="width: 5%;">Sıra</th>
                            <th style="width: 15%;">Stok Kodu</th>
                            <th>Ürün Adı</th>
                            <th style="width: 10%;">Miktar</th>
                            <th style="width: 10%;">Birim</th>
                            <th style="width: 10%;">Fiyat</th>
                            <th style="width: 10%;">Para Birimi</th>
                            <th style="width: 20%;">Açıklama</th>
                        </tr>
                    </thead>
                    <tbody id="sortable">
                        <tr>
                            <?php
                            $satirNo = 1;
                            $stokKodu = '';
                            $urunAdi = '';
                            $buyprice = '';
                            $saleprice = '';
                            $unit = "";
                            $amount = "";
                            $buycur = "";
                            $rowdescription = "";
                            include_once "purchase-demand-row.php";
                            ?>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="10">
                                <button type="button" id="addRow" class="btn btn-sm btn-primary mt-2">
                                    <i class="fa fa-plus-circle"></i> Yeni Satır Ekle
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <input type="hidden" id="rowNumberId" value="<?php echo $satirNo + 1 ?>">
            </div>
        </div>

        <!-- Kart 3: Alt Toplamlar -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-purple">
                    <i class="fa fa-calculator"></i>
                </div>
                <div>
                    <h5>Alt Toplamlar</h5>
                    <p>Kurlar ve KDV dahil edilmiş toplam tutar hesaplamaları</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="tblAltToplam" class="table premium-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">Göster</th>
                            <th>Euro Toplam</th>
                            <th>Dolar Toplam</th>
                            <th>TL Toplam</th>
                            <th>İskonto Toplam</th>
                            <th>Kdv (%)</th>
                            <th>Toplam Tutar (TL)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="sub-item-view text-center">
                                <span class="badge badge-primary" style="cursor: pointer; padding: 6px 12px; font-size: 0.85rem;">Göster</span>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="EuroAlttoplam" id="EuroAlttoplam" value="<?php echo $offer["EuroTotal"] ?? '' ?>">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="DolarAlttoplam" id="DolarAlttoplam" value="<?php echo $offer["DolarTotal"] ?? '' ?>">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="TLAlttoplam" id="TLAlttoplam" value="<?php echo $offer["TLTotal"] ?? '' ?>">
                            </td>
                            <td>
                                <input type="number" autocomplete="off" class="form-control text-center" name="iskonto" value="" id="iskonto" placeholder="0.00">
                            </td>
                            <td>
                                <?php KdvOranları("Kdv","20") ?>
                            </td>
                            <td>
                                <input type="text" autocomplete="off" class="form-control text-center weight-700 text-blue font-18" name="altToplam" id="altToplamInput" value="<?php echo $offer["total_price"] ?? '' ?>" readonly>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
    $(document).ready(function () {

        getCurrencyData();
        $("table").on("input change", "tr input, tr select", function () {
            updateToplamPurchase();
        });

        $("#tProduct").on("click", ".sil", function (e) {
            e.preventDefault();
            $(this).closest("tr").remove();
            updateToplamPurchase();
        })

        $("#addRow").click(function () {
            var sayac = $("#rowNumberId");
            purchaseRowAdd(sayac.val(), true);
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


        $("#payment_period").on("keyup", function () {
            var paymentDays = parseInt($("#payment_period").val());
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


<script>
    $(function() {
       // $("#sortable").sortable();

            var el = document.getElementById('sortable');
        var sortable = Sortable.create(el, {
            onUpdate: function (/**Event*/evt) {
                // Sıralama sonrası numaralandırma
                $("#tProduct tbody tr").each(function(index) {
                    // Numara hücresini güncelle (örneğin ilk <td>)
                    $(this).find("input[name='satirno[]']").val(index + 1);
                });
            }
        });
    });
</script>