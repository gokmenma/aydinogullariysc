<?php
permcontrol("customeredit");
if (@!$_GET["id"]) {
	header("Location:index.php?p=customers");
	exit;
}





$cerq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$cerq->execute(array($_GET["id"]));
$cc = $cerq->fetch(PDO::FETCH_ASSOC);

$cid = $_GET["id"];
if (!$cc) {
	header("Location: index.php?p=customers&err=01735");
	exit;
}

$todos = $ac->prepare("SELECT COUNT(*) FROM projects WHERE pcid = ?");
$todos->execute(array($cid));
$pjs = $todos->fetchColumn();

$todoso = $ac->prepare("SELECT COUNT(*) FROM offers WHERE cid = ?");
$todoso->execute(array($cid));
$ojs = $todoso->fetchColumn();

//son oluşturulan teklif
$sot = $ac->prepare("SELECT * FROM offers WHERE cid = ? ORDER BY id DESC");
$sot->execute(array($cid));
$sonteklif = $sot->fetch(PDO::FETCH_ASSOC);

//Son Oluşturulan Servis
$sos = $ac->prepare("SELECT * FROM projects WHERE pcid = ? ORDER BY id DESC");
$sos->execute(array($cid));
$ojsp = $sos->fetch(PDO::FETCH_ASSOC);

//Servis Tipi getirilir
$sql = $ac->prepare("SELECT * FROM units WHERE id = ? ");
$sql->execute(array($ojsp["servicestype"]));
$servicestype = $sql->fetch(PDO::FETCH_ASSOC);




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

	$ahce->execute(array($ccompany, $cemail,$address, $il, $ilce, $cdesc, $cgsm, $yetkiliadi, 
                                    $categoryName, $OdemeVade,$region,$updater,$updated_at, $cid));

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
if ($_GET["st"] == "newsuccess") {
	showAlert("success", "İşlem Başarı ile tamamlandı!");
}
?>


<!-- Default Basic Forms Start -->
<div class="pd-20 bg-white border-radius-16 box-shadow mb-30">
    <div class="clearfix mb-30">

        <div class="pull-left">
            <div class="d-flex">
                <h4 class="text-blue ml-2">
                    <?php echo $pdat["p_title"]; ?>
                </h4><br>
            </div>
        </div>

        <div class="float-right">
            <button type="button" id="submitButton" onclick="validateForm()" class="btn btn-sm btn-primary">
                <i class="fa fa-save"></i> Kaydet</button>

            <a href="index.php?p=customers" data-tooltip="Listeye Dön" data-tooltip-location="bottom"
                class="btn btn-sm btn-secondary text-white mr-3">
                <i class="fa fa-list mr-1"></i>Listeye Dön</a>


        </div>



    </div>


    <style>
        .sum-customer{
            background: #2e2e2e !important;
            opacity: 0.8;
            border-radius: 12px;
          
        }
        .col-md-6,.col-md-3{
            padding-right: 5px !important;
            padding-left: 5px !important;
        }
    </style>
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-12 mb-30">
            <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                <div class="project-info">
                    <div class="project-info-left">
                        <div class="icon box-shadow bg-blue text-white">
                            <i class="fa fa-gears"></i>
                        </div>
                    </div>
                    <div class="project-info-right">
                        <span class="no text-blue weight-500 font-24">
                            <?php echo $pjs; ?>
                        </span>
                        <p>
                            <a target="_blank" class="weight-400 font-18"
                                href="index.php?p=service/list&cid=<?php echo $cid ?>">Toplam Servis
                                Sayısı</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-30">
            <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                <div class="project-info">
                    <div class="project-info-left">
                        <div class="icon box-shadow bg-light-green text-white">
                            <i class="fa fa-handshake-o"></i>
                        </div>
                    </div>
                    <div class="project-info-right">
                        <span class="no text-blue weight-500 font-24">
                            <?php echo $ojs; ?>
                        </span>
                        <p>
                            <a target="_blank" class="weight-400 font-18"
                                href="index.php?p=offers&cid=<?php echo $cid ?>">Toplam Teklif
                                Sayısı:</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-30">
            <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                <div class="project-info clearfix">
                    <div class="project-info-left">
                        <div class="icon box-shadow bg-danger text-white">
                            <i class="fa fa-file"></i>
                        </div>
                    </div>
                    <div class="project-info-right">

                        <span class="no text-blue weight-500 font-24">
                            <?php echo $sonteklif["offerNumber"]; ?>

                        </span>
                        <p class="weight-400 font-18">
                            <a target="_blank" href="index.php?p=offers&id=<?php echo $sonteklif["id"]; ?>">
                                Son Oluşturulan Teklif
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 mb-30">
            <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                <div class="project-info clearfix">
                    <div class="project-info-left">
                        <div class="icon box-shadow bg-light-orange text-white">
                            <i class="fa fa-gear"></i>
                        </div>
                    </div>
                    <div class="project-info-right">
                        <span class="no text-blue weight-500 font-24">
                            <?php echo $servicestype["title"] ?>
                        </span>
                        <p class="weight-400 font-18">
                            <a target="_blank" href="index.php?p=service/list&id=<?php echo $servicestype["id"] ?>">
                                Son Oluşturulan Servis
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>








    <form enctype="multipart/form-data" action="" id="myForm" method="POST">



        <div class="form-group row">

            <label for="company" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Firma Adı:
            </label>

            <div class="col-sm-12 col-md-4">

                <input required name="company" type="text" value="<?php echo $cc["company"]; ?>" class="form-control">

            </div>

            <label for="cemail" class="col-sm-12 col-md-2 col-form-label">

                <font color="red">(*)</font> E-Posta:

            </label>

            <div class="col-sm-12 col-md-4"><input required name="cemail" type="text"
                    value="<?php echo $cc["email"]; ?>" class="form-control">

            </div>

        </div>


        <div class="form-group row">

            <label for="categoryName" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> Grup:
            </label>

            <div class="input-group col-md-4">
                <select required name="categoryName" id="categoryName" class="selectpicker form-control"
                    data-style="border bg-white">

                    <!-- Müşteri grubu veritabanından getiriliyor -->
                    <?php

					$cek = $ac->prepare("SELECT * FROM cgroups WHERE statu = ? ");
					$cek->execute(array(1));

					while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
						if ($cc["grp"] == $dat["id"]) {
							$selected = 'selected';
						} else {
							$selected = '';
						}
						;
						echo '<option ' . $selected . ' value="' . $dat["id"] . '">' . $dat["title"] . ' </option>';
					}
					?>
                </select>
            </div>

            <label class="col-sm-12 col-md-2 col-form-label"> Yetkili Ad-Soyad:</label>

            <div class="col-sm-12 col-md-4">

                <input name="yetkili" type="text" class="form-control" value="<?php echo $cc["yetkili"]; ?>">

            </div>


        </div>


        <div class="form-group row">

            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>İl:
            </label>

            <div class="col-sm-12 col-md-4">

                <select name="il" id="il" class="selectpicker form-control" data-live-search="true" data-size="5"
                    data-style="border bg-white" title="<?php echo $cc["city"]; ?>">

                </select>

            </div>

            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>İlçe:
            </label>

            <div class="col-sm-12 col-md-4">

                <select name="ilce" id="ilce" class="form-control selectpicker" data-live-search="true" data-size="5"
                    data-none-Selected-Text="Seçim Yapılmadı" data-style="border bg-white">
                    <option value="<?php echo $cc["ilce"]; ?>">
                        <?php echo $cc["ilce"]; ?>
                    </option>
                </select>

            </div>

        </div>
        <div class="form-group row">
            <!-- Bölge Alanı -->
            <label for="region" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Bölge:
            </label>

            <div class="col-sm-12 col-md-4">

                <input required placeholder="Bölge yazınız" name="region" id="region" type="text"
                    class="form-control" value="<?php echo $cc["region"] ;?>">
            </div>
            <!-- Telefon Alanı -->

        </div>


        <div class="form-group row">
            <!-- Telefon Alanı -->
            <label for="cgsm" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Telefon:
            </label>

            <div class="col-sm-12 col-md-4">

                <input required placeholder="05XXXXXXXXX" maxlength="11" minlength="11" name="cgsm" type="text"
                    value="<?php echo $cc["gsm"]; ?>" class="form-control">
            </div>
            <!-- Telefon Alanı -->


            <div class="col-md-2 col-sm-12">
                <label class="col-form-label"> Ödeme Vadesi:</label>
            </div>

            <div class="col-sm-12 col-md-4">
                <input type="text" class="form-control" name="vade" id="vade" value="<?php echo $cc["OdemeVade"] ?>">

            </div>

        </div>
         <div class="form-group row">
            <label for="customer_address" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Adres :
            </label>

            <div class="col-sm-12 col-md-10">
                <textarea required name="customer_address" placeholder="Firma adresi" class="form-control" rows="3"
                    style="height:100%;"><?php echo $cc["address"] ?></textarea>
            </div>
        </div>

        <div class="form-group row">

            <label class="col-sm-12 col-md-2 col-form-label">

                Açıklama:

            </label>

            <div class="col-sm-12 col-md-10">

                <textarea name="cdesc" placeholder="Firma hakkında yöneticilerin görebileceği bir not ekleyebilirsiniz."
                    class="form-control" text=""><?php echo $cc["cdesc"]; ?></textarea>

            </div>

        </div>

    </form>






    <script>
    $.getJSON("src/scripts/il-bolge.json", function(sonuc) {
        $.each(sonuc, function(index, value) {
            var option = '<option value="' + value.il + '" data-subtext="' + value.bolge +'"';
            // Eğer JSON'dan alınan il, veritabanından alınan il ile eşleşiyorsa, seçili yap
            if (value.il === "<?php echo $cc["city"]; ?>") {
                option += ' selected';
            }
            option += '>' + value.il + '</option>';
            $("#il").append(option);
        });
    });


    $("#il").on("change", function() {

        var il = $(this).val();
        $("#ilce").prop("disabled", false) // Seçimi etkinleştir
        var bolge = $(this).find('option:selected').data('subtext');
        $("#region").val(bolge);

        //$("#ilce").attr("disabled", false).html("<option value=''>Seçin..</option>");

        $.getJSON("src/scripts/il-ilce.json", function(sonuc) {
            $("#ilce").empty(); // İlçe seçimlerini temizle
            $.each(sonuc, function(index, value) {

                var row = "";

                if (value.il == il) {
                    row += '<option value="' + value.ilce + '">' + value.ilce + '</option>';
                    $("#ilce").append(row);
                }
               
            });
   
            $('#ilce').selectpicker('refresh');

        });

    });
    </script>