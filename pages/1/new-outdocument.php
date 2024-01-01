<?php
permcontrol("docoutadd");
define("MAXSX", set("max_sr"));

if ($_POST) {

    if (!$_POST["d_cname"] || !$_POST["d_cemail"]) {
     header("Location: index.php?p=new-outdocument&st=empties");
    exit;
    }



    $d_cname = @$_POST["d_cname"];

    $regg = $ac->prepare("INSERT INTO documents SET 
                                                    d_cname =  ?,
                                                    createtime= ?,
                                                    uid= ?");

    $isRecord = $regg->execute(array($d_cname, TODAY, sesset("id")));

    //$lidx = $ac->lastInsertId();
    if ($isRecord) {

        header("Location: index.php?p=new-outdocument&st=newsuccess");
    } else {
    }
}

if (@$_GET["st"] == "empties") {
    showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
} elseif (@$_GET["st"] == "newsuccess") {
    showAlert('success',  'kl Müşteri başarıyla eklendi!');
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "name") {
    showAlert('alert', 'Aynı adda bir dosya bulunuyor, lütfen ismini değiştirerek projeyi tekrar oluşturmayı deneyin.');
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "size") {
    showAlert('alert', 'Yüklediğiniz dosyanın boyutu <b>3 MB</b>\'dan daha büyük olamaz. Proje oluşturulamadı, tekrar deneyin.');
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "erno") {
    showAlert('warning', 'Proje oluşturuldu ancak, dosya yüklenirken bir problem yaşandı.');
}

?>
<!-- Default Basic Forms Start -->
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
    <div class="clearfix">
        <div class="pull-left">
            <h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
            <p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
                bırakmayın..<br></p>
        </div>
        <div class="form-group">
            <input type="submit" id="submitButton" value="Kaydet" class="float-right btn btn-primary">
        </div>
    </div>
    <form enctype="multipart/form-data" action="" id="myForm" method="POST">

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> Alıcı Adı :
            </label>
            <div class="col-sm-12 col-md-10">
                <input required name="d_cname" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> Firma Adı :
            </label>
            <div class="col-sm-12 col-md-10">
                <select name="grp" class="form-control">
                    <?php $cek = $ac->prepare("SELECT * FROM customers");
                    $cek->execute();
                    while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                        <option value="<?php echo $dat["id"]; ?>"><?php echo $dat["company"]; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
        </div>



        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> E-Posta:
            </label>
            <div class="col-sm-12 col-md-10"><input required name="cemail" type="text" class="form-control">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Şirket İsmi:</label>
            <div class="col-sm-12 col-md-10"><input name="ccompany" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Sektör:</label>
            <div class="col-sm-12 col-md-10"><input name="csector" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label"> Adres:</label>
            <div class="col-sm-12 col-md-10"><input name="caddress" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Şehir:</label>
            <div class="col-sm-12 col-md-10"><input name="ccity" type="text" class="form-control">
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
            <div class="col-sm-12 col-md-10"><input name="yetkili" type="text" class="form-control">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label"> Şirket Unvanı:</label>
            <div class="col-sm-12 col-md-10"><input name="sunvan" type="text" class="form-control">
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


<script>
    document.getElementById("submitButton").addEventListener('click', function() {
        var form = document.getElementById('myForm');
        form.submit();
    })
</script>
<?php include 'include/footer.php'; ?>