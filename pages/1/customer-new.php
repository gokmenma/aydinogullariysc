<?php

permcontrol("customeradd");

define("MAXSX", set("max_sr"));


if ($_POST) {


    if (!$_POST["ccompany"] || !$_POST["cemail"]) {

        header("Location: index.php?p=new-customer&st=empties");

        exit;

    }





    $cemail = @$_POST["cemail"];

    $ccompany = @$_POST["ccompany"];

    $csector = @$_POST["csector"];

    $ccity = @$_POST["il"];

    $cilce = @$_POST["ilce"];

    $cnotes = @$_POST["cnotes"];
    $customer_address = @$_POST["customer_address"];
    $region = @$_POST["region"];

    $cgsm = @$_POST["cgsm"];

    $cgsm2 = @$_POST["cgsm2"];

    $yetkiliadi = @$_POST["yetkili"];

    $sunvan = @$_POST["sunvan"];

    $pword = "abc";

    $grp = @$_POST["categoryName"];

    $duplicate = $ac->prepare(
        "SELECT 1 FROM customers
         WHERE deleted_at IS NULL
           AND LOWER(TRIM(company)) = LOWER(TRIM(?))
         LIMIT 1"
    );
    $duplicate->execute([trim($ccompany)]);
    if ($duplicate->fetchColumn()) {
        showAlert("alert", "Aynı isimde aktif bir firma kaydı zaten mevcut.");
    } else {


    $regg = $ac->prepare("INSERT INTO customers SET
	grp = ?,
    email = ?,
    company = ?,
    address = ?,
    city = ?,
    ilce = ?,
    cdesc = ?,
    gsm = ?,
    gsm2 = ?,
    yetkili = ?,
    sunvan = ?,
    reg_date = ?,
    creativer = ? , 
    region = ? ");



    $asdfa = $regg->execute(
        array(
            $grp,
            $cemail,
            $ccompany,
            $customer_address,
            $ccity,
            $cilce,
            $cnotes,
            $cgsm,
            $cgsm2,
            $yetkiliadi,
            $sunvan,
            TODAY,
            sesset("id"),
            $region
        )
    );



    $lidx = $ac->lastInsertId();

    if ($lidx) {

        header("Location: index.php?p=customer-new&st=newsuccess");

    }
    }


}

if (@$_GET["st"] == "empties") {

    showAlert("alert", "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.");

}

if ($_GET["st"] == "newsuccess") {
    showAlert("success", "İşlem Başarı ile tamamlandı!");
}
?>



<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
    <form enctype="multipart/form-data" action="" id="myForm" method="POST">

        <!-- Default Basic Forms Start -->

        <div class="clearfix">

            <div class="pull-left">

                <h4 class="text-blue">
                    <?php echo $pdat["p_title"]; ?>
                </h4>

                <p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
                    bırakmayın..<br></p>

            </div>
            <div class="float-right">

                <button type="button" id="submitButton" onclick="validateForm()" class="btn btn-sm btn-primary">
                    <i class="fa fa-save"></i> Kaydet</button>

                <a href="index.php?p=customers" data-tooltip="Listeye Dön" data-tooltip-location="bottom"
                    class="btn btn-sm btn-secondary text-white">
                    <i class="fa fa-list mr-1"></i>Listeye Dön</a>
            </div>
        </div>



        <div class="form-group row">

            <label for="ccompany" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Firma Adı:
            </label>

            <div class="col-sm-12 col-md-4">

                <input required name="ccompany" type="text" class="form-control">

            </div>

            <label for="cemail" class="col-sm-12 col-md-2 col-form-label">

                <font color="red">(*)</font> E-Posta:

            </label>

            <div class="col-sm-12 col-md-4"><input required name="cemail" type="text" class="form-control">

            </div>

        </div>


        <div class="form-group row">

            <label for="grp" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> Grup:
            </label>

            <div class="input-group col-md-4">
                <select required name="categoryName" id="categoryName" class="selectpicker form-control"
                    data-style="border bg-white">

                    <!-- Müşteri grubu veritabanından getiriliyor -->
                    <?php $cek = $ac->prepare("SELECT * FROM cgroups WHERE statu = ? ");
                    $cek->execute(array(1));

                    while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $dat["id"] . '">' . $dat["title"] . ' </option>';
                    }
                    ?>
                </select>
            </div>


            <label class="col-sm-12 col-md-2 col-form-label"> Yetkili Ad-Soyad:</label>

            <div class="col-sm-12 col-md-4">

                <input name="yetkili" type="text" class="form-control">

            </div>


        </div>


        <div class="form-group row">

            <label for="il" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>İl:
            </label>

            <div class="col-sm-12 col-md-4">

                <select required name="il" id="il" class="selectpicker form-control" data-live-search="true"
                    data-size="5" data-style="border bg-white" title="İl Seçiniz!">

                </select>

            </div>

            <label for="ilce" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>İlçe:
            </label>

            <div class="col-sm-12 col-md-4">

                <select required name="ilce" id="ilce" title="İlçe Seçiniz!" disabled="disabled"
                    class="form-control selectpicker" data-live-search="true" data-size="5"
                    data-style="border bg-white">
                </select>

            </div>

        </div>

        <div class="form-group row">
            <!-- Telefon Alanı -->
            <label for="region" class="col-sm-12 col-md-2 col-form-label">
               <font color="red">(*)</font> Bölge:
            </label>

            <div class="col-sm-12 col-md-4">

                <input required placeholder="Firma Bölgesi" name="region" id="region" type="text" class="form-control">
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
                    class="form-control">
            </div>
            <!-- Telefon Alanı -->


            <div class="col-md-2 col-sm-12">
                <label class="col-form-label"> Ödeme Vadesi:</label>
            </div>

            <div class="col-sm-12 col-md-4">
                <input type="text" class="form-control" name="vade" id="vade">
            </div>

        </div>

        <div class="form-group row">
            <label for="customer_address" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font>Adres :
            </label>

            <div class="col-sm-12 col-md-10">
                <textarea required name="customer_address" placeholder="Firma adresi" class="form-control" rows="3"
                    style="height:100%;"></textarea>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">
                Açıklama:
            </label>

            <div class="col-sm-12 col-md-10">
                <textarea name="cnotes"
                    placeholder="Firma hakkında yöneticilerin görebileceği bir not ekleyebilirsiniz."
                    class="form-control" rows="5" style="height:100%;"></textarea>
            </div>
        </div>

    </form>

</div>



<script>
    $.getJSON("src/scripts/il-bolge.json", function (sonuc) {

        $.each(sonuc, function (index, value) {

            var row = "";

            row += '<option value="' + value.il + '" data-subtext="' + value.bolge + '">' + value.il + '</option>';

            $("#il").append(row);


        })

    });

    $("#il").on("change", function () {

        var il = $(this).val();
        $("#ilce").empty(); // İlçe seçimini temizle

        $("#ilce").prop("disabled", false) // Seçimi etkinleştir
        
        var bolge = $(this).find("option:selected").data("subtext");
        $("#region").val(bolge);    

        $.getJSON("src/scripts/il-ilce.json", function (sonuc) {
            $.each(sonuc, function (index, value) {

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
