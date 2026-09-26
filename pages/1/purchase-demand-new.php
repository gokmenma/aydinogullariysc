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
													state= ? , type= ? , emailState = '0' ");
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
                $insq->execute(array($lastid, $stokKodu[$i] ?? '', $urunAdi[$i] ?? '', $amounts[$i] ?? 0, $units[$i] ?? '', $buyprices[$i] ?? 0, $buycur[$i] ?? ''));
            }
        }
        if ($insq) {
            header("Location: index.php?p=purchase-demand-new&st=newsuccess");
            exit();
        };
        $getNumber += 1;
        $upquery = $ac->prepare("UPDATE define_numbers SET purchase_demand = ?");
        $upquery->execute(array($getNumber));

    } catch (PDOException $e) {
        error_log("Satın alma talebi ekleme hatası: " . $e->getMessage());
        showAlert('danger', "Talep kaydedilirken bir hata oluştu: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
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
    #tProduct th, #tProduct td {
        vertical-align: middle !important;
    }
    #tProduct .btn-group .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    #tProduct tfoot td {
        text-align: left !important;
        padding: 10px 12px !important;
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
                <table id="tProduct" class="table premium-table no-filter">
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
                            <th style="min-width: 180px;" class="no-filter">Açıklama</th>
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
                            <td colspan="10" class="text-left" style="text-align: left !important; padding: 10px 12px !important; background: #f8fafc; border-top: 2px solid #e2e8f0;">
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" id="addRow" class="btn btn-sm btn-primary" style="border-radius: 8px; font-weight: 600; padding: 7px 18px;">
                                        <i class="fa fa-plus-circle mr-1"></i> Yeni Satır Ekle
                                    </button>
                                    <button type="button" id="btnOpenMultiProductModal" class="btn btn-sm btn-outline-primary" style="border-radius: 8px; font-weight: 600; padding: 7px 18px;">
                                        <i class="fa fa-th-list mr-1"></i> Toplu Ürün Ekle
                                    </button>
                                </div>
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
                <table id="tblAltToplam" class="table premium-table no-filter">
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

<?php include_once __DIR__ . '/../../components/modals/multi-product-modal.php'; ?>

<script src="include/js/purchase.js"></script>
<script>
    $(document).ready(function () {
        if (window.ProductPicker) {
            ProductPicker.init({
                tableSelector: '#tProduct, #sortable',
                fields: {
                    title: 'input[name="urunAdi[]"]',
                    stock: 'input[name="stokKodu[]"]',
                    buyprice: 'input[name="buyprice[]"]',
                    buycur: 'select[name="buycur[]"]',
                    unit: 'select[name="unit[]"]',
                    amount: 'input[name="amount[]"]',
                    description: 'input[name="rowdescription[]"]'
                },
                onSelect: function($row, data) {
                    updateToplamPurchase();
                }
            });
        }

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