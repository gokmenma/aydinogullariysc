<?php
permcontrol("uperm");

if(@!$_GET["pid"]){
header("Location:index.php");
  exit;
  die;
}

$contpid = $ac->prepare("SELECT * FROM perms WHERE id = ?");
$contpid->execute(array($_GET["pid"]));
$cc = $contpid->fetch(PDO::FETCH_ASSOC);
if(!$cc){
  header("Location: index.php?p=permission-settings&noquery=true");
  die;
  exit;
}
if($_POST && $_GET["mode"] == "new"){


  $title = @$_POST["title"];
  
  $ekle = $ac->prepare("UPDATE perms SET
  p_title = ?,
  cadd = ?,
  cedit = ?,
  cdelete = ?,
  sadd = ?,
  sedit = ?,
  sreminder = ?,
  sdelete = ?,
  pmadd = ?,
  pmview = ?,
  pmdelete = ?,
  oadd = ?,
  oview = ?,
  oedit = ?,
  odelete = ?,
  padd = ?,
  pedit = ?,
  pdelete = ?,
  payadd = ?,
  payview = ?,
  paydelete = ?,
  seradd = ?,
  seredit = ?,
  serdelete = ?,
  sercategory = ?,
  exadd = ?,
  exview = ?,
  excategory = ?,
  exdelete = ?,
  todoadd = ?,
  todoedit = ?,
  tododelete = ?,
  noteadd = ?,
  noteedit = ?,
  notedelete = ?,
  uadd = ?,
  uedit = ?,
  udelete = ?,
  uperm = ?,
  misadd = ?,
  mistake = ?,
  allmisview = ?,
  mlogview = ?,
  setview = ?,
  fadd = ?,
  fview = ?,
  fdelete = ? WHERE id = ?");
  $ekle->execute(array($title,@$_POST["cadd"],@$_POST["cedit"],@$_POST["cdelete"],@$_POST["sadd"],"on",@$_POST["sreminder"],@$_POST["sdelete"],@$_POST["pmadd"],@$_POST["pmview"],@$_POST["pmdelete"],@$_POST["oadd"],@$_POST["oview"],@$_POST["oedit"],@$_POST["odelete"],@$_POST["padd"],@$_POST["pedit"],@$_POST["pdelete"],@$_POST["payadd"],@$_POST["payview"],@$_POST["paydelete"],@$_POST["seradd"],@$_POST["seredit"],@$_POST["serdelete"],@$_POST["sercategory"],@$_POST["exadd"],@$_POST["exview"],@$_POST["excategory"],@$_POST["exdelete"],@$_POST["todoadd"],@$_POST["todoedit"],@$_POST["tododelete"],@$_POST["noteadd"],@$_POST["noteedit"],@$_POST["notedelete"],@$_POST["uadd"],@$_POST["uedit"],@$_POST["udelete"],@$_POST["uperm"],@$_POST["misadd"],@$_POST["mistake"],@$_POST["allmisview"],@$_POST["mlogview"],@$_POST["setview"],@$_POST["fadd"],@$_POST["fview"],@$_POST["fdelete"],@$_GET["pid"]));
    $pixd = $_GET["pid"];
  header("Location:index.php?p=edit-perm&pid=$pixd&st=newsuccess");
}



  $pidxs = $_GET["pid"];
?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
  <?php
  if(@$_GET["st"] == "newsuccess" ){



?>
<div class="alert alert-success" role="alert">
               Kayıt başarıyla güncellendi.
              </div>
  <?php
}
  if(@$_GET["type"] == "delete" AND @$_GET["cid"]){
  ?>
    
  <?php
}
  ?>
  <div class="clearfix mb-20">
            <div class="pull-left">
              <h5 class="text-blue">Yeni Yetkilendirme</h5>
              
            </div>
            
          </div><form method="POST" action="index.php?p=edit-perm&pid=<?php echo $pidxs; ?>&mode=new&code=38&cc=087s3">
  
  <div class="row">
    <br><br>

    <div class="col-sm-12 col-md-12">

    
  
      <div class="form-group"> 
        <label>Pozisyon Unvanı:</label>
        <input required name="title" value="<?php echo $cc["p_title"]; ?>" class="form-control" type="text" ><br>
        <div class="form-group"><h5>İzinler</h5><br>
              <div class="row">

                <div class="col-md-6 col-sm-12">
                  
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
                  </div><br>
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

                  <br>  
                  <div class="custom-control custom-checkbox mb-5">
                    <input <?php echo $cc["mlogview"] == "on" ? "checked" : ""; ?> name="mlogview" type="checkbox" class="custom-control-input" id="customCheck18-1">
                    <label class="custom-control-label" for="customCheck18-1">Mail & SMS Gönder</label>
                  </div>
                  <div class="custom-control custom-checkbox mb-5">
                    <input <?php echo $cc["setview"] == "on" ? "checked" : ""; ?> name="setview" type="checkbox" class="custom-control-input" id="customCheck19-1">
                    <label class="custom-control-label" for="customCheck19-1">Panel Ayarlarını Yönet</label>
                  </div>
                  
                </div>
                <div class="col-md-6 col-sm-12">
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
                  <br><div class="custom-control custom-checkbox mb-5">
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
                </div>
            
            
        
      </div><button type="submit" style="float:right;" type="button" class="btn btn-success">Kaydet</button><br><br><br>
    </div></form>
</div>