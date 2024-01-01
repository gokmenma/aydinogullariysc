<?php
permcontrol("cadd");
define("MAXSX", set("max_sr"));
getTableColumns('cgroups'); 
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
    if ($lidx){
        header("Location: index.php?p=new-customer&st=newsuccess");
    }
}

if (@$_GET["st"] == "empties") {
    showAlert("alert", "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.");
} 
if (@$_GET["st"] == "newsuccess") {
    showAlert("success", "İşlem Başarı ile tamamlandı!");
}

?>
<!-- Default Basic Forms Start -->
<div class="content pd-20 bg-white border-radius-8 box-shadow mb-30">
    <div class="clearfix">
        <div class="pull-left">
            <h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
            <p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
                bırakmayın..<br></p>
        </div>
        <input type="submit" id="submitButton" onclick="validateForm()" value="Kaydet" class="float-right btn btn-primary">

    </div>
    <form enctype="multipart/form-data" action="" id="myForm" method="POST">

        <div class="form-group row">

            <label for="cname" class="col-sm-12 col-md-2 col-form-label">
                <font color="red">(*)</font> Ad-Soyad:
            </label>
            <div class="col-sm-12 col-md-4">
                <input required name="cname" type="text" class="form-control">
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
                <select required name="categoryName" id="categoryName" class="form-control">
                    <?php $cek = $ac->prepare("SELECT * FROM cgroups WHERE statu = ?");
                    $cek->execute(array(1));
                    while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                        <option value="<?php echo $dat["id"]; ?>"><?php echo $dat["title"]; ?></option>
                    <?php
                    }
                    ?>
                </select>

                <?php if (permtrue("cedit")) { ?>
                    <div class="chooseitem">
                        <!-- Button trigger modal -->

                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exampleModal2">
                            <i class="fa fa-plus-circle"></i>
                        </button>




                        <!-- Modal -->
                        <div class="modal fade" id="exampleModal2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel">Kategori Adı:</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body m-2">
                                        <input type="text" class="form-control" value="" name="Addcategory" id="Addcategory" placeholder="Eklenecek kategori adını yazınız...">
                                    </div>
                                    <div class="modal-footer mb-2">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>

                                        <button type="button" id="ModalSaveButton" onclick="SaveNewKategory('customer-groups','categoryName')" data-bs-dismiss="modal" class="btn btn-primary">Kaydet</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Modal -->
                    </div>
                <?php  } ?>
            </div>
        </div>


        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Şirket İsmi:</label>
            <div class="col-sm-12 col-md-4">
                <input name="ccompany" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Sektör:</label>
            <div class="col-sm-12 col-md-4">
                <input name="csector" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label"> Adres:</label>
            <div class="col-sm-12 col-md-4">
                <input name="caddress" type="text" class="form-control">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Şehir:</label>
            <div class="col-sm-12 col-md-4">
                <input name="ccity" type="text" class="form-control">
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
            <div class="col-sm-12 col-md-4">
                <input name="yetkili" type="text" class="form-control">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label"> Şirket Unvanı:</label>
            <div class="col-sm-12 col-md-4">
                <input name="sunvan" type="text" class="form-control">
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
