<?php
permcontrol("cadd");
define("MAXSX", set("max_sr"));

if ($_POST) {

    if (!$_POST["cname"] || !$_POST["cemail"]) {
        header("Location: index.php?p=new-customer&st=empties");
        exit;
    }

    $cname = @$_POST["cname"];
    $cemail = @$_POST["cemail"];
    $ccompany = @$_POST["ccompany"];
    $csector = @$_POST["csector"];
    $caddress = @$_POST["caddress"];
    $ccity = @$_POST["ccity"];
    $cnotes = @$_POST["cnotes"];
    $cgsm = @$_POST["cgsm"];
    $cgsm2 = @$_POST["cgsm2"];
    $yetkiliadi = @$_POST["yetkili"];
    $sunvan = @$_POST["sunvan"];
    $vdaire = @$_POST["vdaire"];
    $vno = @$_POST["vno"];
    $pword = "abc";
    $grp = @$_POST["grp"];

    $regg = $ac->prepare("INSERT INTO customers SET
	grp = ?,
    name = ?,
    email = ?,
    company = ?,
    sector = ?,
    address = ?,
    city = ?,
    cdesc = ?,
    gsm = ?,
    gsm2 = ?,
    yetkili = ?,
    sunvan = ?,
    vdaire = ?,
    vno = ?,
    reg_date = ?,
    creativer = ?");

    $asdfa = $regg->execute(array($grp, $cname, $cemail, $ccompany, $csector, $caddress, $ccity, $cnotes, $cgsm, $cgsm2, $yetkiliadi, $sunvan, $vdaire, $vno, TODAY, sesset("id")));

    $lidx = $ac->lastInsertId();
    if ($asdfa) {

        header("Location: index.php?p=new-customer&st=newsuccess");
    } else {
    }
}

if (@$_GET["st"] == "empties") {
    showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
    
} elseif (@$_GET["st"] == "newsuccess") {
   showAlert('success', 'Müşteri başarıyla eklendi!');
 
} 

?>
<!-- Default Basic Forms Start -->
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
    <div class="clearfix">
        <div class="pull-left">
            <h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
            <p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
                bırakmayın..<br></p>
        </div>
        <input type="submit" id="submitButton" value="Kaydet" class="float-right btn btn-primary">

    </div>
    <form enctype="multipart/form-data" action="" id="myForm" method="POST">

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> Ad-Soyad:
            </label>
            <div class="col-sm-12 col-md-4">
                <input required name="cname" type="text" class="form-control">
            </div>
            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> E-Posta:
            </label>
            <div class="col-sm-12 col-md-4"><input required name="cemail" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> Grup:
            </label>
            <div class="col-sm-12 col-md-4">
                <select name="grp" class="form-control">
                    <?php $cek = $ac->prepare("SELECT * FROM cgroups WHERE statu = ?");
                    $cek->execute(array(1));
                    while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                        <option value="<?php echo $dat["id"]; ?>"><?php echo $dat["title"]; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
        </div>



        <!-- <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> E-Posta:
            </label>
            <div class="col-sm-12 col-md-4"><input required name="cemail" type="text" class="form-control">
            </div>
        </div> -->
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Şirket İsmi:</label>
            <div class="col-sm-12 col-md-4"><input name="ccompany" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Sektör:</label>
            <div class="col-sm-12 col-md-4"><input name="csector" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label"> Adres:</label>
            <div class="col-sm-12 col-md-4"><input name="caddress" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Şehir:</label>
            <div class="col-sm-12 col-md-4"><input name="ccity" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">

            <label class="col-sm-12 col-md-2 col-form-label">Telefon:</label>
            <div class="col-sm-12 col-md-4">
                <input placeholder="05XXXXXXXXX" maxlength="11" minlength="11" name="cgsm" type="text" class="form-control">
            </div>

            <label class="col-sm-12 col-md-2 col-form-label"> Telefon 2:</label>
            <div class="col-sm-12 col-md-4">
                <input name="cgsm2" type="text" class="form-control">
            </div>

        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label"> Yetkili Ad-Soyad:</label>
            <div class="col-sm-12 col-md-4"><input name="yetkili" type="text" class="form-control">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label"> Şirket Unvanı:</label>
            <div class="col-sm-12 col-md-4"><input name="sunvan" type="text" class="form-control">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label"> Vergi Dairesi:</label>
            <div class="col-sm-12 col-md-4">
                <input name="vdaire" type="text" class="form-control">
            </div>

            <label class="col-sm-12 col-md-2 col-form-label"> Vergi No:</label>
            <div class="col-sm-12 col-md-4">
                <input name="vno" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">
                Açıklama:
            </label>
            <div class="col-sm-12 col-md-10">
                <textarea name="cnotes" placeholder="Müşteri hakkında yöneticilerin görebileceği bir not ekleyebilirsiniz." class="form-control"></textarea>
            </div>
        </div>


    </form>
</div>


<?php include 'include/app.js'; ?>
<?php include 'include/footer.php'; ?>