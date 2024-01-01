<?php
permcontrol("uperm");


if (@!$_GET["id"]) {
  header("Location:index.php");
  exit;
  die;
}

$contpid = $ac->prepare("SELECT * FROM perms WHERE id = ?");
$contpid->execute(array($_GET["id"]));
$cc = $contpid->fetch(PDO::FETCH_ASSOC);
if (!$cc) {
  header("Location: index.php?p=permission-settings&noquery=true");
  die;
  exit;
}

if ($_POST) {


  $title = @$_POST["p_title"];

  // $ekle = $ac->prepare("UPDATE perms SET
  // p_title = ?,
  // cadd = ?,
  // cedit = ?,
  // cdelete = ?,
  // sadd = ?,
  // sedit = ?,
  // sreminder = ?,
  // sdelete = ?,
  // pmadd = ?,
  // pmview = ?,
  // pmdelete = ?,
  // oadd = ?,
  // oview = ?,
  // oedit = ?,
  // odelete = ?,
  // padd = ?,
  // pedit = ?,
  // pdelete = ?,
  // payadd = ?,
  // payview = ?,
  // paydelete = ?,
  // seradd = ?,
  // seredit = ?,
  // serdelete = ?,
  // sercategory = ?,
  // exadd = ?,
  // exview = ?,
  // excategory = ?,
  // exdelete = ?,
  // todoadd = ?,
  // todoedit = ?,
  // tododelete = ?,
  // noteadd = ?,
  // noteedit = ?,
  // notedelete = ?,
  // uadd = ?,
  // uedit = ?,
  // udelete = ?,
  // uperm = ?,
  // misadd = ?,
  // mistake = ?,
  // allmisview = ?,
  // mlogview = ?,
  // setview = ?,
  // fadd = ?,
  // fview = ?,
  // fdelete = ?
  // proadd = ?,
  // prodelete = ?,
  // proview = ?
  // WHERE id = ?");

  // $ekle->execute(array($title,@$_POST["cadd"],@$_POST["cedit"],@$_POST["cdelete"],@$_POST["sadd"],"on",@$_POST["sreminder"],@$_POST["sdelete"],@$_POST["pmadd"],@$_POST["pmview"],@$_POST["pmdelete"],@$_POST["oadd"],@$_POST["oview"],@$_POST["oedit"],@$_POST["odelete"],@$_POST["padd"],@$_POST["pedit"],@$_POST["pdelete"],@$_POST["payadd"],@$_POST["payview"],@$_POST["paydelete"],@$_POST["seradd"],@$_POST["seredit"],@$_POST["serdelete"],@$_POST["sercategory"],@$_POST["exadd"],@$_POST["exview"],@$_POST["excategory"],@$_POST["exdelete"],@$_POST["todoadd"],@$_POST["todoedit"],@$_POST["tododelete"],@$_POST["noteadd"],@$_POST["noteedit"],@$_POST["notedelete"],@$_POST["uadd"],@$_POST["uedit"],@$_POST["udelete"],@$_POST["uperm"],@$_POST["misadd"],@$_POST["mistake"],@$_POST["allmisview"],@$_POST["mlogview"],@$_POST["setview"],@$_POST["fadd"],@$_POST["fview"],@$_POST["fdelete"],@$_GET["pid"]));
  //   $pixd = $_GET["pid"];
  // header("Location:index.php?p=edit-perm&pid=$pixd&st=newsuccess");

  $cadd =       @$_POST["cadd"];
  $cedit =      @$_POST["cedit"];
  $cdelete =    @$_POST["cdelete"];
  $sadd =       @$_POST["sadd"];
  $sedit =      @$_POST["sedit"];
  $sreminder =  @$_POST["sreminder"];
  $sdelete =    @$_POST["sdelete"];
  $pmadd =      @$_POST["pmadd"];
  $pmview =     @$_POST["pmview"];
  $pmdelete =   @$_POST["pmdelete"];
  $oadd =       @$_POST["oadd"];
  $oview =      @$_POST["oview"];
  $oedit =      @$_POST["oedit"];
  $odelete =    @$_POST["odelete"];
  $padd =       @$_POST["padd"];
  $pedit =      @$_POST["pedit"];
  $pdelete =    @$_POST["pdelete"];
  $payadd =     @$_POST["payadd"];
  $payview =    @$_POST["payview"];
  $paydelete =  @$_POST["paydelete"];
  $seradd =     @$_POST["seradd"];
  $seredit =    @$_POST["seredit"];
  $serdelete =  @$_POST["serdelete"];
  $sercategory = @$_POST["sercategory"];
  $exadd =      @$_POST["exadd"];
  $exview =     @$_POST["exview"];
  $excategory = @$_POST["excategory"];
  $exdelete =   @$_POST["exdelete"];
  $todoadd =    @$_POST["todoadd"];
  $todoedit =   @$_POST["todoedit"];
  $tododelete = @$_POST["tododelete"];
  $noteadd =    @$_POST["noteadd"];
  $noteedit =   @$_POST["noteedit"];
  $notedelete = @$_POST["notedelete"];
  $uadd =       @$_POST["uadd"];
  $uedit =      @$_POST["uedit"];
  $udelete =    @$_POST["udelete"];
  $uperm =      @$_POST["uperm"];
  $misadd =     @$_POST["misadd"];
  $mistake =    @$_POST["mistake"];
  $allmisview = @$_POST["allmisview"];
  $mlogview =   @$_POST["mlogview"];
  $setview =    @$_POST["setview"];
  $fadd =       @$_POST["fadd"];
  $fview =      @$_POST["fview"];
  $fdelete =    @$_POST["fdelete"];
  $docdelete =  @$_POST["docdelete"];
  $docview =    @$_POST["docview"];
  $docinadd =   @$_POST["docinadd"];
  $docoutadd =  @$_POST["docoutadd"];
  $servadd =    @$_POST["servadd"];
  $servview =   @$_POST["servview"];
  $servdelete = @$_POST["servdelete"];
  $proadd =     @$_POST["proadd"];
  $prodelete =  @$_POST["prodelete"];
  $proview =    @$_POST["proview"];

  $id = @$_GET["id"];

  $insq = $ac->prepare("UPDATE perms SET  
                                cadd = ? ,      cedit = ? , 
                                cdelete = ? ,   sadd = ? ,      sedit = ? , 
                                sreminder = ? , sdelete = ? ,   pmadd = ? , 
                                pmview = ? ,    pmdelete = ? ,  oadd = ? , 
                                oview = ? ,     oedit = ? ,     odelete = ? , 
                                padd = ? ,      pedit = ? ,     pdelete = ? , 
                                payadd = ? ,    payview = ? ,   paydelete = ? , 
                                seradd = ? ,    seredit = ? ,   serdelete = ? , 
                                sercategory = ? , exadd = ? ,   exview = ? , 
                                excategory = ? , exdelete = ? , todoadd = ? , 
                                todoedit = ? ,  tododelete = ? , noteadd = ? , 
                                noteedit = ? ,  notedelete = ? , uadd = ? , 
                                uedit = ? ,     udelete = ? ,   uperm = ? , 
                                misadd = ? ,    mistake = ? ,   allmisview = ? , 
                                mlogview = ? ,  setview = ? ,   fadd = ? , 
                                fview = ? ,     fdelete = ? ,   docdelete = ? , 
                                docview = ? ,   docinadd = ? ,  docoutadd = ? , 
                                servadd = ? ,   servview = ? ,  servdelete = ? , 
                                proadd = ? ,    prodelete = ? , proview = ?  
                                WHERE id = ? ");

  $insq->execute(array(
    $cadd, $cedit,
    $cdelete, $sadd, $sedit,
    $sreminder, $sdelete, $pmadd,
    $pmview, $pmdelete, $oadd,
    $oview, $oedit,
    $odelete, $padd, $pedit,
    $pdelete, $payadd, $payview,
    $paydelete, $seradd, $seredit,
    $serdelete, $sercategory, $exadd,
    $exview, $excategory, $exdelete,
    $todoadd, $todoedit, $tododelete,
    $noteadd, $noteedit, $notedelete,
    $uadd, $uedit, $udelete,
    $uperm, $misadd, $mistake,
    $allmisview, $mlogview, $setview,
    $fadd, $fview, $fdelete,
    $docdelete, $docview, $docinadd,
    $docoutadd, $servadd, $servview,
    $servdelete, $proadd, $prodelete,
    $proview, @$_GET["id"]
  ));
}





if ($insq) {
  echo '<script> console.log(`' . json_encode($id) . '`); </script>';
  header("Location:index.php?p=edit-perm&reg=true&md=update&id=$id");
}
?>
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">

  <div class="clearfix mb-20">
    <div class="pull-left">
      <h5 class="text-blue">Yetkileri Düzenle</h5>

    </div>
    <div class="float-right">
      <button type="submit" onclick="validateForm()" type="button" class="mr-2 btn btn-primary">Kaydet</button>
      <a class="btn btn-secondary" href="index.php?p=permission-settings">Pozisyon Listesi</a>

    </div>

  </div>
  <form enctype="multipart/form-data" id="myForm" action="" method="POST" action="">

    <!-- <div class="row"> -->
      <br><br>

      <!-- <div class="col-sm-12 col-md-12"> -->



        <div class="form-group">
          <label for="title">
            <font color="red">(*)</font> Pozisyon Unvanı:
          </label>
          <input required disabled name="title" value="<?php echo $cc["p_title"]; ?>" class="form-control" type="text"><br>
          <div class="form-group">
            <h5>İzinler</h5><br>
            <div class="row">

              <div class="col-md-4 col-sm-12">

                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["cadd"] == "on" ? "checked" : ""; ?> name="cadd" type="checkbox" class="custom-control-input" id="customCheck1-1">
                  <label class="custom-control-label" for="customCheck1-1">Müşteri Ekle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["cedit"] == "on" ? "checked" : ""; ?> name="cedit" type="checkbox" class="custom-control-input" id="customCheck2-1">
                  <label class="custom-control-label" for="customCheck2-1">Müşteri Düzenle </label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["cdelete"] == "on" ? "checked" : ""; ?> name="cdelete" type="checkbox" class="custom-control-input" id="customCheck3-1">
                  <label class="custom-control-label" for="customCheck3-1">Müşteri Sil </label>
                </div><br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["pmadd"] == "on" ? "checked" : ""; ?> name="pmadd" type="checkbox" class="custom-control-input" id="customCheck4-1">
                  <label class="custom-control-label" for="customCheck4-1">Ödeme Hesabı Ekle </label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["pmview"] == "on" ? "checked" : ""; ?> name="pmview" type="checkbox" class="custom-control-input" id="customCheck5-1">
                  <label class="custom-control-label" for="customCheck5-1">Hesap Bilgileri Görüntüle </label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["pmdelete"] == "on" ? "checked" : ""; ?> name="pmdelete" type="checkbox" class="custom-control-input" id="customCheck6-1">
                  <label class="custom-control-label" for="customCheck6-1">Ödeme Hesabı Sil </label>
                </div><br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["oadd"] == "on" ? "checked" : ""; ?> name="oadd" type="checkbox" class="custom-control-input" id="customCheck7-1">
                  <label class="custom-control-label" for="customCheck7-1">Teklif Oluştur </label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["oview"] == "on" ? "checked" : ""; ?> name="oview" type="checkbox" class="custom-control-input" id="customCheck8-1">
                  <label class="custom-control-label" for="customCheck8-1">Teklif Görüntüle </label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["oedit"] == "on" ? "checked" : ""; ?> name="oedit" type="checkbox" class="custom-control-input" id="customCheck9-1">
                  <label class="custom-control-label" for="customCheck9-1">Teklif Düzenle </label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["odelete"] == "on" ? "checked" : ""; ?> name="odelete" type="checkbox" class="custom-control-input" id="customCheck10-1">
                  <label class="custom-control-label" for="customCheck10-1">Teklif Sil </label>
                </div><br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["sercategory"] == "on" ? "checked" : ""; ?> name="sercategory" type="checkbox" class="custom-control-input" id="customCheck11-1">
                  <label class="custom-control-label" for="customCheck11-1">Ürün/Hizmet Kategori Yönetimi</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["seradd"] == "on" ? "checked" : ""; ?> name="seradd" type="checkbox" class="custom-control-input" id="customCheck12-1">
                  <label class="custom-control-label" for="customCheck12-1">Ürün/Hizmet Ekle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["seredit"] == "on" ? "checked" : ""; ?> name="seredit" type="checkbox" class="custom-control-input" id="customCheck13-1">
                  <label class="custom-control-label" for="customCheck13-1">Ürün/Hizmet Düzenle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["serdelete"] == "on" ? "checked" : ""; ?> name="serdelete" type="checkbox" class="custom-control-input" id="customCheck14-1">
                  <label class="custom-control-label" for="customCheck14-1">Ürün/Hizmet Sil</label>
                </div><br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["noteadd"] == "on" ? "checked" : ""; ?> name="noteadd" type="checkbox" class="custom-control-input" id="customCheck15-1">
                  <label class="custom-control-label" for="customCheck15-1">Not Oluştur</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["noteedit"] == "on" ? "checked" : ""; ?> name="noteedit" type="checkbox" class="custom-control-input" id="customCheck16-1">
                  <label class="custom-control-label" for="customCheck16-1">Not Düzenle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["notedelete"] == "on" ? "checked" : ""; ?> name="notedelete" type="checkbox" class="custom-control-input" id="customCheck17-1">
                  <label class="custom-control-label" for="customCheck17-1">Not Sil</label>
                </div>
                <br>


                <br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["mlogview"] == "on" ? "checked" : ""; ?> name="mlogview" type="checkbox" class="custom-control-input" id="customCheck18-1">
                  <label class="custom-control-label" for="customCheck18-1">Mail & SMS Gönder</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["setview"] == "on" ? "checked" : ""; ?> name="setview" type="checkbox" class="custom-control-input" id="customCheck19-1">
                  <label class="custom-control-label" for="customCheck19-1">Panel Ayarlarını Yönet</label>
                </div>
                <br>



                <br>



              </div>
              <div class="col-md-4 col-sm-12">
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["sadd"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="sadd" id="customCheck40">
                  <label class="custom-control-label" for="customCheck40">Satış Oluştur</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["sdelete"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="sdelete" id="customCheck60">
                  <label class="custom-control-label" for="customCheck60">Satış Sil</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["sreminder"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="sreminder" id="customCheck70">
                  <label class="custom-control-label" for="customCheck70">Satış Hatırlatıcı Planla</label>
                </div><br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["padd"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="padd" id="customCheck5">
                  <label class="custom-control-label" for="customCheck5">Proje Oluştur</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["pedit"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="pedit" id="customCheck6">
                  <label class="custom-control-label" for="customCheck6">Proje Düzenle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["pdelete"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="pdelete" id="customCheck7">
                  <label class="custom-control-label" for="customCheck7">Proje Sil</label>
                </div><br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["payadd"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="payadd" id="customCheck8">
                  <label class="custom-control-label" for="customCheck8">Ödeme Al</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["payview"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="payview" id="customCheck9">
                  <label class="custom-control-label" for="customCheck9">Ödeme Görüntüle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["paydelete"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="paydelete" id="customCheck10">
                  <label class="custom-control-label" for="customCheck10">Ödeme Sil</label>
                </div><br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["excategory"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="excategory" id="customCheck14">
                  <label class="custom-control-label" for="customCheck14">Gelir/Gider Kategori Yönetimi</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["exadd"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="exadd" id="customCheck11">
                  <label class="custom-control-label" for="customCheck11">Gelir/Gider Ekle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["exview"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="exview" id="customCheck12">
                  <label class="custom-control-label" for="customCheck12">Gelir/Gider Görüntüle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["exdelete"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="exdelete" id="customCheck13">
                  <label class="custom-control-label" for="customCheck13">Gelir/Gider Sil</label>
                </div><br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["todoadd"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="todoadd" id="customCheck15">
                  <label class="custom-control-label" for="customCheck15">Yapılacaklar Listesi Ekle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["todoedit"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="todoedit" id="customCheck16">
                  <label class="custom-control-label" for="customCheck16">Yapılacaklar Listesi Düzenle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["tododelete"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="tododelete" id="customCheck17">
                  <label class="custom-control-label" for="customCheck17">Yapılacaklar Listesi Sil</label>
                </div><br>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["uadd"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="uadd" id="customCheck18">
                  <label class="custom-control-label" for="customCheck18">Ekip Üyesi Oluştur</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["uedit"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="uedit" id="customCheck19">
                  <label class="custom-control-label" for="customCheck19">Ekip Üyesi Düzenle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["udelete"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="udelete" id="customCheck20">
                  <label class="custom-control-label" for="customCheck20">Ekip Üyesi Sil</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["uperm"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="uperm" id="customCheck21">
                  <label class="custom-control-label" for="customCheck21">İzinleri Yönet</label>
                </div>
                <br>


              </div>

              <div class="col-md-4 col-sm-12">
                <!-- Servis Yönetimi -->
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["servadd"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="servadd" id="customCheck230">
                  <label class="custom-control-label" for="customCheck230">Servis Ekle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["servview"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="servview" id="customCheck231">
                  <label class="custom-control-label" for="customCheck231">Servis Görüntüle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["servdelete"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="servdelete" id="customCheck232">
                  <label class="custom-control-label" for="customCheck232">Servis Sil</label>
                </div>
                <!-- Servis Yönetimi -->
                <br>


                <!-- Dosya Yönetimi -->
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["fadd"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="fadd" id="customCheck119">
                  <label class="custom-control-label" for="customCheck119">Dosya Yükle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["fview"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="fview" id="customCheck210">
                  <label class="custom-control-label" for="customCheck210">Dosyaları Görüntüle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["fdelete"] == "on" ? "checked" : ""; ?> type="checkbox" class="custom-control-input" name="fdelete" id="customCheck211">
                  <label class="custom-control-label" for="customCheck211">Dosya Sil</label>
                </div>
                <!-- Dosya Yönetimi -->
                <br>


                <!-- Görev Yönetimi -->
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["misadd"] == "on" ? "checked" : ""; ?> name="misadd" type="checkbox" class="custom-control-input" id="customCheck180-1">
                  <label class="custom-control-label" for="customCheck180-1">Görev Ver</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["mistake"] == "on" ? "checked" : ""; ?> name="mistake" type="checkbox" class="custom-control-input" id="customCheck190-1">
                  <label class="custom-control-label" for="customCheck190-1">Görev Al</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["allmisview"] == "on" ? "checked" : ""; ?> name="allmisview" type="checkbox" class="custom-control-input" id="customCheck190-10">
                  <label class="custom-control-label" for="customCheck190-10">Tüm Görevleri Görüntüle</label>
                </div>
                <!-- Görev Yönetimi -->
                <br>

                <!-- Evrak Yönetimi -->
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["docoutadd"] == "on" ? "checked" : ""; ?> name="docoutadd" type="checkbox" class="custom-control-input" id="customCheck20-1">
                  <label class="custom-control-label" for="customCheck20-1">Giden Evrak Ekle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["docoinadd"] == "on" ? "checked" : ""; ?> name="docoinadd" type="checkbox" class="custom-control-input" id="customCheck21-1">
                  <label class="custom-control-label" for="customCheck21-1">Gelen Evrak Ekle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["docview"] == "on" ? "checked" : ""; ?> name="docview" type="checkbox" class="custom-control-input" id="customCheck22-1">
                  <label class="custom-control-label" for="customCheck22-1">Evrak Görüntüle</label>
                </div>
                <div class="custom-control custom-checkbox mb-5">
                  <input <?php echo $cc["docdelete"] == "on" ? "checked" : ""; ?> name="docdelete" type="checkbox" class="custom-control-input" id="customCheck23-1">
                  <label class="custom-control-label" for="customCheck23-1">Evrak Sil</label>
                </div>
                <!-- Evrak Yönetimi -->



              </div>



            </div>
          </div> 
  </form>
</div>