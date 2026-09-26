<?php
require_once "App/Helper/date.php";
require_once "App/Model/OfferModel.php";
use App\Helper\Date;

$ois = @$_GET["id"];
$cid = @$_GET["cid"];


$OfferModel = new OfferModel();

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
				if ($cid) {
					$ofqu = $ac->prepare("SELECT * FROM offers WHERE cid = ? ORDER BY id DESC");
					$ofqu->execute(array($cid));
				} else if ($ois) {
					$ofqu = $ac->prepare("SELECT * FROM offers WHERE id = ? ORDER BY id DESC");
					$ofqu->execute(array($ois));
				} else {
					$ofqu = $ac->prepare("SELECT * FROM offers ORDER BY id DESC");
					$ofqu->execute();
				}
				$sirano = 1;
				while ($of = $ofqu->fetch(PDO::FETCH_ASSOC)) {

					$findc = $ac->prepare("SELECT * FROM customers WHERE id = ?");
					$findc->execute(array($of["cid"]));
					$ffc = $findc->fetch(PDO::FETCH_ASSOC);

					$stats = $ac->prepare("SELECT * FROM ofstats WHERE sid = ?");
					$stats->execute(array($of["statu"]));
					$st = $stats->fetch(PDO::FETCH_ASSOC);

					$usqs = $ac->prepare("SELECT * FROM users WHERE id = ?");
					$usqs->execute(array($of["creativer"]));
					$usp = $usqs->fetch(PDO::FETCH_ASSOC);

					if ($of["currency"] == "tl") {
						$prx = "₺";
					} elseif ($of["currency"] == "dollar") {
						$prx = "$";
					} elseif ($of["currency"] == "euro") {
						$prx = "€";
					} else {
						$prx = "₺";
					} ?>

                <tr>
                    <td class="text-center"><?php echo $sirano; ?></td>
                    <td class="table-plus text-center">

                        <?php 
							//Eğer güncelleme tarihi bboş ise oluştrulma tarihini al, değilse güncelleme tarihini al
							if($of["updated_at"] == NULL || $of["updated_at"] == "0000-00-00 00:00:00"){
								echo ($of["created_at"]);
							}else{
								echo ($of["updated_at"]);
							}
							//echo Date::dmy($of["offer_date"]); 
							?>
                    </td>

                    <td class="text-center">
                        <?php echo $of["offerNumber"]; ?>
                    </td>

                    <td><a href="index.php?p=customer-edit&id=<?php echo $ffc["id"]; ?>"
                            data-tooltip="Firmayı Görüntüle/Düzenle">
                            <?php echo shorted($ffc["company"], 40); ?>
                        </a>
                    </td>

                    <td>
                        <?php echo tlFormat($of["total_price"] ?? 0) . " " . $prx; ?>
                    </td>

                    <!-- <td style="color:<?php echo $st["color"]; ?>" title="<?php echo $st["description"]; ?>">
						<?php echo $st["title"]; ?>&nbsp;&nbsp;<i class="<?php echo $st["icon"]; ?>"></i>
					</td> -->
                    <td>
                        <?php
							echo $of["statu"] == 2 ? "<span class='badge badge-success'>Tamamlandı</span>" : "<span class='badge badge-warning'>Bekliyor</span>";
							?>
                    </td>
                    <!-- Teklif Konusu -->
                    <td class="text-center">
                        <?php echo ($of["offer_subject"]); ?>
                    </td>

                    <!-- Ödeme Vadesi -->
                    <td class="text-center">
                        <?php echo ($of["payment_period"]); ?>
                    </td>

                    <!-- Teklif Veren -->
                    <td class="text-center">
                        <?php echo (getUsername($of["creativer"])); ?>
                    </td>

                    <td class="text-center app-item-action-3 text-nowrap">

                        <?php
							//Eğer teklif şablon ise, sadece yetkili kullanıcılara düzenleme ve silme izni ver
							if ($of["is_template"] == 1) {

								if (permtrue('template_offer_create')) {

									if (permtrue("offeredit")) { ?>
                        <a type="button" href="index.php?p=offers/offer-manage&id=<?php echo $of["id"]; ?>"
                            class="btn btn-sm btn-outline-primary" data-tooltip="Düzenle"><i
                                class="fa fa-pencil"></i></a>

                        <?php }
									if (permtrue("offerdelete")) { ?>
                        <button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil"
                            onClick="deleteRecord('<?php echo $ffc["company"]; ?> firmasına ait teklifi silmek istediğinize emin misiniz?','<?php echo $of["id"]; ?>','offers')"><i
                                class="fa fa-trash"></i></button>


                        <?php }
								}
							} 
							//Eğer teklif şablon değilse, tüm kullanıcılara düzenleme ve silme izni ver
							else {
								if (permtrue("offeredit")) { ?>
                        <a type="button" href="index.php?p=offers/offer-manage&id=<?php echo $of["id"]; ?>"
                            class="btn btn-sm btn-outline-primary" data-tooltip="Düzenle"><i
                                class="fa fa-pencil"></i></a>

                        <?php }
								if (permtrue("offerdelete")) { ?>
                        <button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil"
                            onClick="deleteRecord('<?php echo $ffc["company"]; ?> firmasına ait teklifi silmek istediğinize emin misiniz?','<?php echo $of["id"]; ?>','offers/list','offers')"><i
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
                                <a href="index.php?p=offer-view&id=<?php echo $of["id"] ?>" target="_blank"
                                    class="dropdown-item" type="button">
                                    <i class="fa fa-file-text-o mr-2"></i>
                                    Standart Teklifi Göster</a>
                                <a href="index.php?p=offer-view&id=<?php echo $of["id"] ?>&summary=false"
                                    target="_blank" class="dropdown-item" type="button">
                                    <i class="fa fa-copy mr-2"></i>
                                    Toplamsız Şablonu Göster</a>
                                <a href="index.php?p=offer-view&id=<?php echo $of["id"] ?>&all_currency=true"
                                    target="_blank" class="dropdown-item" type="button">
                                    <i class="fa fa-copy mr-2"></i>
                                    Çoklu Döviz Şablonunu Göster</a>
                                <a href="index.php?p=offer-view&id=<?php echo $of["id"] ?>&proforma=true"
                                    target="_blank" class="dropdown-item" type="button">
                                    <i class="fa fa-copy mr-2"></i>
                                    Proforma Göster</a>

                                <?php }
									if (permtrue("mailandsmssend")) { ?>
                                <a href="index.php?p=report-send-as-mail&type=offer&id=<?php echo $of["id"] ?>"
                                    class="dropdown-item" type="button">
                                    <i class="fa fa-envelope-o mr-2"></i>
                                    Mail Gönder</a>
                                <?php }
									if (permtrue("offercopy")) { ?>
                                <a href="#" class="dropdown-item offer-copy" type="button"
                                    data-id="<?php echo $of["id"] ?>">
                                    <i class="fa fa-copy mr-2"></i>
                                    Teklifi Kopyala</a>
                                <?php } ?>
                            </div>

                        </div>
                        <!-- 					
					
					<button type="button" data-tooltip="Teklifi Kopyala" data-tooltip-location="bottom"
						class="btn btn-sm btn-secondary mr-1" onclick="offercopy(<?php echo $of['id'] ?>)">
						<i class="fa fa-copy"></i></button> -->


                    </td>

                    <?php
						$sirano += 1;
				}
				?>

                </tr>


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