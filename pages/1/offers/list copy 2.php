<?php
require_once "App/Helper/date.php";
require_once "App/Model/OfferModel.php";

use App\Helper\Date;

$ois = @$_GET["id"];
$cid = @$_GET["cid"];


$OfferModel = new OfferModel();

$offers = $OfferModel->getOffersWithCompanyName();



$offerCount = $OfferModel->getOfferCountWaitingAndDone();
$bekleyen_teklif_sayisi = $offerCount->bekleyen_teklif;
$tamamlanan_teklif_sayisi = $offerCount->tamamlanan_teklif;


if (@$_GET["st"] == "offercopy") {
    $creator = sesset("lid");
    $newoffernumber = "TK" . newNumber("offers");
    $ofcopy = $ac->prepare("INSERT INTO offers (offerNumber, cid, company_authors,
											   total_price,mycompany,
									   		   authors,reg_date,tax,creativer,
											   notes,currency,statu,
											   dollar,euro,payment_period,
											   offer_header,offer_header_content,
											   offer_footer,offer_footer_content,
											   description,
											   file, kdv,iskonto, subdescription,
											   buyTotal,saleTotal,amounttotal,
											   curDollar,curEuro,
											   DolarTotal ,EuroTotal ,TLTotal ) 
										SELECT ? , cid, company_authors,
											   total_price,mycompany,
											   authors,reg_date,tax,?,
											   notes,currency,statu,
											   dollar,euro,payment_period,
											   offer_header,offer_header_content,
											   offer_footer,offer_footer_content,
											   description,
											   file, kdv, iskonto, subdescription,
											   buyTotal,saleTotal,amounttotal,
											   curDollar,curEuro,
											   DolarTotal ,EuroTotal ,TLTotal
						   FROM offers	WHERE id = ?;");
    $ofcopy->execute(array($newoffernumber, $creator, $ois));
    $lastid = $ac->lastInsertId();

    $offmat = $ac->prepare("SELECT * FROM offermatters WHERE oid = ?");
    $offmat->execute(array($ois));

    $items = $offmat->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $insq = $ac->prepare("INSERT INTO offermatters SET xid = ?,oid = ?,stokKodu = ? , 
														   title = ? ,unit = ? ,amount = ? , 
														   buyprice = ? ,buycur = ?,saleprice = ? ,
														   salecur = ? ,total_price = ?");

        $insq->execute(
            array(
                $item["xid"],
                $lastid,
                $item["stokKodu"],
                $item["title"],
                $item["unit"],
                $item["amount"],
                $item["buyprice"],
                $item["buycur"],
                $item["saleprice"],
                $item["salecur"],
                $item["total_price"]
            )
        );
    }
}

///
if ($_GET["st"] == "success-mail") {
    showAlert("success", "Mail başarı ile gönderildi!");
}

?>

<style>
    .responsive {
        overflow: auto;
    }
</style>

<div class="content pd-20 bg-white border-radius-16 box-shadow mb-10">
    <div class="clearfix mb-10">

        <!-- Özet bilgiler -->
        <div class="row">

            <div class="col-lg-6 col-md-6 col-sm-12 mb-10">
                <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                    <div class="project-info">
                        <div class="project-info-left">
                            <div class="icon box-shadow bg-warning text-white">
                                <i class="fa fa-wrench"></i>
                            </div>
                        </div>
                        <div class="project-info-right">
                            <span class="no text-blue weight-500 font-24">
                                <?php echo $bekleyen_teklif_sayisi; ?>
                            </span>
                            <p>
                                <a target="_blank" class="weight-400 font-18" href="">Bekleyen Teklif Sayısı:</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 mb-10">
                <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                    <div class="project-info clearfix">
                        <div class="project-info-left">
                            <div class="icon box-shadow bg-green text-white">
                                <i class="fa fa-check"></i>
                            </div>
                        </div>
                        <div class="project-info-right">

                            <span class="no text-blue weight-500 font-24">
                                <?php echo $tamamlanan_teklif_sayisi; ?>

                            </span>
                            <p class="weight-400 font-18">
                                <a target="_blank" href="">
                                    Tamamlanan Teklif Sayısı
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
    <div class="clearfix mb-30">
        <div class="pull-left">
            <h5 class="text-blue">Teklifleri Görüntüle</h5>

        </div>

        <div class="float-right gap-2">

            <!-- Excele Aktar -->
            <?php if (permtrue("data_export_offers")) { ?>
                <a href="#" class="btn btn-secondary mr-1" id="exportExcel"><i class="fa fa-file-excel-o"></i>
                    Excele Aktar</a>
                <!-- Excele Aktar -->
            <?php } ?>

            <?php if (permtrue("offerAdd")) { ?>
                <a href="index.php?p=offers/offer-manage"><button style="float:right;" type="button"
                        class="btn btn-primary">
                        <i class="fa fa-plus pr-1"></i>Yeni Teklif Oluştur</button></a> <br><br>
            <?php } ?>
        </div>
    </div>

    <?php



    ?>
    <div class="responsive">

        <table id="offerTable" class="data-table table-hover table-bordered">
            <thead>
                <tr>

                    <th>Sıra No</th>
                    <th class="w-10">İşlem Tarihi</th>
                    <th>Teklif No</th>
                    <th>Müşteri</th>
                    <th>Toplam Tutar</th>
                    <th>Durum</th>
                    <th>Konusu</th>
                    <th>Ödeme Vadesi</th>
                    <th>Teklif Veren</th>
                    <th class="no-export">İşlem</th>

                </tr>
            </thead>
            <tbody>
                <?php
                // if ($cid) {
                // 	$ofqu = $ac->prepare("SELECT * FROM offers WHERE cid = ? ORDER BY id DESC");
                // 	$ofqu->execute(array($cid));
                // } else if ($ois) {
                // 	$ofqu = $ac->prepare("SELECT * FROM offers WHERE id = ? ORDER BY id DESC");
                // 	$ofqu->execute(array($ois));
                // } else {
                // 	$ofqu = $ac->prepare("SELECT * FROM offers ORDER BY id DESC");
                // 	$ofqu->execute();
                // }
                $sirano = 1;
                foreach ($offers as $offer) { ?>
                    <tr>
                        <td class="text-center"><?php echo $sirano; ?></td>
                        <td class="table-plus text-center">

                            <?php
                            //Eğer güncelleme tarihi bboş ise oluştrulma tarihini al, değilse güncelleme tarihini al
                            if ($offer->updated_at == NULL || $offer->updated_at == "0000-00-00 00:00:00") {
                                echo ($offer->created_at);
                            } else {
                                echo ($offer->updated_at);
                            }
                            //echo Date::dmy($of["offer_date"]); 
                            ?>
                        </td>
                        <td class="text-center">
                            <?php echo $offer->offerNumber; ?>

                        </td>
                        <td><a href="index.php?p=customer-edit&id=<?php echo $offer->customer_id; ?>"
                                data-tooltip="Firmayı Görüntüle/Düzenle">
                                <?php echo shorted($offer->company_name, 40); ?>
                            </a>
                        </td>
                        <td class="text-right">
                            <?php echo tlFormat($offer->total_price ?? 0) . " ₺"; ?>
                        </td>
                        <td class="text-center">
                            <?php
                            echo $offer->statu == 2 ? "<span class='badge badge-success'>Tamamlandı</span>" : "<span class='badge badge-warning'>Bekliyor</span>";
                            ?>
                        </td>
                        <!-- Teklif Konusu -->
                        <td class="text-left">
                            <?php echo ($offer->offer_subject); ?>
                        </td>
                        <!-- Ödeme Vadesi -->
                        <td class="text-center">
                            <?php echo ($offer->payment_period); ?>
                        </td>

                        <!-- Teklif Veren -->
                        <td class="text-center">
                            <?php echo (($offer->creator_name)); ?>
                        </td>
                        <td class="text-center app-item-action-3 text-nowrap">

                            <?php
                            //Eğer teklif şablon ise, sadece yetkili kullanıcılara düzenleme ve silme izni ver
                            if ($offer->is_template == 1) {

                                if (permtrue('template_offer_create')) {

                                    if (permtrue("offeredit")) { ?>
                                        <a type="button" href="index.php?p=offers/offer-manage&id=<?php echo $offer->id; ?>"
                                            class="btn btn-sm btn-outline-primary" data-tooltip="Düzenle"><i
                                                class="fa fa-pencil"></i></a>

                                    <?php }
                                    if (permtrue("offerdelete")) { ?>
                                        <button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil"
                                            onClick="deleteRecord('<?php echo $offer->company_name; ?> firmasına ait teklifi silmek istediğinize emin misiniz?','<?php echo $offer->id; ?>','offers')"><i
                                                class="fa fa-trash"></i></button>


                                    <?php }
                                }
                            }
                            //Eğer teklif şablon değilse, tüm kullanıcılara düzenleme ve silme izni ver
                            else {
                                if (permtrue("offeredit")) { ?>
                                    <a type="button" href="index.php?p=offers/offer-manage&id=<?php echo $offer->id; ?>"
                                        class="btn btn-sm btn-outline-primary" data-tooltip="Düzenle"><i
                                            class="fa fa-pencil"></i></a>

                                <?php }
                                if (permtrue("offerdelete")) { ?>
                                    <button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil"
                                        onClick="deleteRecord('<?php echo $offer->company_name; ?> firmasına ait teklifi silmek istediğinize emin misiniz?','<?php echo $offer->id; ?>','offers/list','offers')"><i
                                            class="fa fa-trash"></i></button>


                            <?php }
                            } ?>

                            <div class="dropdown d-inline">
                                <button class="btn btn-secondary btn-sm" type="button" id="dropdownMenu2"
                                    data-toggle="dropdown">
                                    <i class="fa fa-ellipsis-v ml-1 mr-1"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail"
                                    aria-labelledby="dropdownMenu2">

                                    <?php


                                    if (permtrue("offerview")) { ?>
                                        <a href="index.php?p=offer-view&id=<?php echo $offer->id ?>" target="_blank"
                                            class="dropdown-item" type="button">
                                            <i class="fa fa-file-text-o mr-2"></i>
                                            Standart Teklifi Göster</a>
                                        <a href="index.php?p=offer-view&id=<?php echo $offer->id ?>&summary=false"
                                            target="_blank" class="dropdown-item" type="button">
                                            <i class="fa fa-copy mr-2"></i>
                                            Toplamsız Şablonu Göster</a>
                                        <a href="index.php?p=offer-view&id=<?php echo $offer->id ?>&all_currency=true"
                                            target="_blank" class="dropdown-item" type="button">
                                            <i class="fa fa-copy mr-2"></i>
                                            Çoklu Döviz Şablonunu Göster</a>
                                        <a href="index.php?p=offer-view&id=<?php echo $offer->id ?>&proforma=true"
                                            target="_blank" class="dropdown-item" type="button">
                                            <i class="fa fa-copy mr-2"></i>
                                            Proforma Göster</a>

                                    <?php }
                                    if (permtrue("mailandsmssend")) { ?>
                                        <a href="index.php?p=report-send-as-mail&type=offer&id=<?php echo $offer->id ?>"
                                            class="dropdown-item" type="button">
                                            <i class="fa fa-envelope-o mr-2"></i>
                                            Mail Gönder</a>
                                    <?php }
                                    if (permtrue("offercopy")) { ?>
                                        <a href="#" class="dropdown-item offer-copy" type="button"
                                            data-id="<?php echo $offer->id ?>">
                                            <i class="fa fa-copy mr-2"></i>
                                            Teklifi Kopyala</a>
                                    <?php } ?>
                                </div>

                            </div>





                        </td>
                    </tr>

                <?php } ?>




            </tbody>
            <tfoot>
                <tr>

                    <th>Sıra No</th>
                    <th class="w-10">İşlem Tarihi</th>
                    <th>Teklif No</th>
                    <th>Müşteri</th>
                    <th>Toplam Tutar</th>
                    <th>Durum</th>
                    <th>Konusu</th>
                    <th>Ödeme Vadesi</th>
                    <th>Teklif Veren</th>
                    <th>İşlem</th>

                </tr>
            </tfoot>
        </table>

    </div>
</div>
<script src="include/js/data-table.js"></script>
<script src="pages/1/offers/offer.js"></script>

<!-- ÖNCEKİ `data-table.js` YERİNE DAHA DETAYLI BİR SCRIPT -->
<script>
$(document).ready(function() {
    var offerTable = $('#offerTable').DataTable({
        // === SUNUCU TARAFLI AYARLAR ===
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "api/get-offers.php", // Veriyi çekeceğimiz API adresi
            "type": "POST"
            // İsterseniz ek parametre gönderebilirsiniz
            // "data": function ( d ) {
            //     d.cid = '<?php echo $cid; ?>'; // Sadece belirli bir müşterinin tekliflerini listelemek için
            // }
        },

        // === SÜTUN TANIMLAMALARI ===
        // API'den dönen JSON anahtarları ile eşleşir
        "columns": [
            { "data": "sira_no", "name": "sira_no", "orderable": false, "searchable": false },
            { "data": "islem_tarihi", "name": "updated_at" }, // `name` sıralama için veritabanı sütun adını belirtir
            { "data": "teklif_no", "name": "offerNumber" },
            { "data": "musteri", "name": "company_name" },
            { "data": "toplam_tutar", "name": "total_price" },
            { "data": "durum", "name": "statu" },
            { "data": "konusu", "name": "offer_subject" },
            { "data": "odeme_vadesi", "name": "payment_period" },
            { "data": "teklif_veren", "name": "creator_name" },
            { "data": "islem", "orderable": false, "searchable": false }
        ],
        
        // === DİĞER AYARLAR ===
        "order": [[ 1, "desc" ]], // Başlangıçta 2. sütuna (İşlem Tarihi) göre tersten sıralı gelsin
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/tr.json" // Türkçe dil paketi
        }
    });

    // === EVENT DELEGATION ===
    // AJAX ile sonradan yüklenen butonların da çalışması için bu yöntem şarttır.
    $('#offerTable tbody').on('click', '.offer-copy', function () {
        var offerId = $(this).data('id');
        // Kopyalama işlemi için onay sorusu
        Swal.fire({
            title: 'Teklifi Kopyala',
            text: "Bu teklifin bir kopyasını oluşturmak istediğinizden emin misiniz?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, Kopyala!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Onaylanırsa kopyalama URL'sine yönlendir
                window.location.href = 'index.php?p=offers/list&st=offercopy&id=' + offerId;
            }
        });
    });

    // Excel'e aktar butonu için
    $('#exportExcel').on('click', function(e){
        e.preventDefault();
        // Bu özellik için DataTables'ın Buttons eklentisi gerekir
        // Veya kendi Excel export mantığınızı burada tetikleyebilirsiniz.
        alert("Excel export için DataTables Buttons eklentisi gereklidir.");
    });
});
</script>