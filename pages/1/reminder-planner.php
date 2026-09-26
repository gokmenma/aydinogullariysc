<?php
permcontrol("sreminder");
if (set("reminder_view") != "1") {
?>
  <div class="alert alert-warning" role="alert">
    <b>Dikkat!</b> Hatırlatma mail'inin içeriğini ana dizin / pages / 1 / reminder-planner.php dosyası içinde belirtildiği şekilde düzenleyebilirsiniz. Bu mesaj sadece 1 kez gösterilir.
  </div>
<?php


  $regupa = $ac->prepare("UPDATE settings SET val = ? WHERE var = ?");
  $regupa->execute(array("1", "reminder_view"));
}
if (!@$_GET["sid"]) {
  header("Location:index.php");
  exit;
  die;
}
$sid = $_GET["sid"];
$acs = $ac->prepare("SELECT * FROM sales WHERE id = ? AND statu = ?");
$acs->execute(array($_GET["sid"], 1));
$aa = $acs->fetch(PDO::FETCH_ASSOC);

if (!$aa) {
  header("Location:index.php?p=sales&errrooorr");
  exit;
  die;
}

$ab = $ac->prepare("SELECT COUNT(*) FROM reminders WHERE sid = ?");
$ab->execute(array($sid));
$ssx = $ab->fetchColumn();

$qazx = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$qazx->execute(array($aa["cid"]));
$ci = $qazx->fetch(PDO::FETCH_ASSOC);

if ($_POST) {

  $remind_date = date_tr($_POST["reminder-date"]);

  if (empty($remind_date)) {
    header("Location:index.php?p=reminder-planner&sid=$sid&error=empty");
    exit;
  }

  $hat = 2;
  if ($hat == 1) {
    $sms = 1;
    $mail = 0;
  } elseif ($hat == 2) {
    $sms = 0;
    $mail = 1;
  } elseif ($hat == 3) {
    $sms = 1;
    $mail = 1;
  }

  $insa = $ac->prepare("INSERT INTO reminders SET cid = ?, sid = ?, dates = ?, mail = ?, sms = ?, type = ?, statu = ?");

  $insa->execute(array($aa["cid"], $sid, $remind_date, $mail, $sms, "satishatirlatici", 0));
  if (!$insa) {
  }
  header("Location: index.php?p=sales&st=newsuccess");
}



$dat = $ac->prepare("SELECT * FROM mainservices ");
$dat->execute();

if (@$_GET["st"] == "empties") {
?>
  <div class="alert alert-danger" role="alert">
    (*) ile işaretli alanları boş bırakmadan tekrar deneyin.
  </div>
<?php
}
if (@$_GET["st"] == "newsuccess") {
?>
  <div class="alert alert-success" role="alert">
    Bilgiler kaydedildi.
  </div>
<?php
} else if (@$_GET["st"] == "numericerror") {
?>
  <div class="alert alert-danger" role="alert">
    Fiyat kısmına sadece rakamlardan oluşan değer girebilirsiniz.
  </div>
<?php
}
?>
<div class="alert alert-warning" role="alert">
  Bu satış ile ilgili, müşteriye daha önce <u><?php echo $ssx; ?></u> hatırlatıcı oluşturulmuş.
</div>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
  <div class="clearfix">
    <div class="pull-left">
      <h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
      <p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br>Ödemelerin 3 gün öncesinde ve ödeme günleri hatırlatma mesajı otomatik olarak gönderilir.</p>
    </div>

  </div>
  <form method="POST" action="">

    <div class="row">
      <div class="col-md-6 col-sm-12">
        <div class="form-group">
          <label>
            <font color="red">(*)</font>Müşteri Adı
          </label>
          <input disabled name="cname" value="<?php echo $ci["name"]; ?>" class="form-control" type="text">

        </div>
      </div>

      <div class="form-group row col-md-6 col-sm-12">

        <div class="col-sm-12 col-md-12"><label>
            <font color="red">(*)</font>Hizmet Adı
          </label>
          <select disabled name="ss1" class="custom-select col-12">
            <?php
            $ms = $ac->prepare("SELECT * FROM services ");
            $ms->execute();
            while ($mm = $ms->fetch(PDO::FETCH_ASSOC)) {
            ?>
              <option <?php echo $mm["id"] == $aa["sid"] ? "selected" : ""; ?> value="<?php echo $mm["id"]; ?>"><?php echo $mm["stitle"]; ?></option>
            <?php

            }
            ?>
          </select>
        </div>
      </div>


      <?php

      $asd = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
      $asd->execute(array($aa["pay_method"]));
      $asdv = $asd->fetch(PDO::FETCH_ASSOC);

      if ($asdv["currency"] == "tl") {
        $prx = "₺";
      } elseif ($asdv["currency"] == "dollar") {
        $prx = "$";
      } elseif ($asdv["currency"] == "euro") {
        $prx = "€";
      } else {
        $prx = "₺";
      }

      ?>
      <div class="col-md-6 col-sm-12">
        <div class="form-group">
          <label>
            <font color="red">(*)</font>Fiyat
          </label>
          <input disabled name="cprice" value="<?php echo $aa["price"] . " " . $prx; ?>" class="form-control" type="text">

        </div>
      </div>

      <div class="form-group row col-md-6 col-sm-12">

        <div class="col-sm-12 col-md-12"><label>
            <font color="red">(*)</font>Sonraki Ödeme Tarihi
          </label>
          <select disabled name="paydate" class="custom-select col-12">
            <?php
            $tre = explode("-", $aa["start_date"]);
            if ($aa["pay_type"] == 1) {
            ?>
              <option value="">Her Ayın <?php echo $tre[0]; ?>'ünde</option>
            <?php

            } else {
            ?>
              <option value="">Her Yıl <?php echo $tre[0] . "-" . $tre[1] . "-XXXX"; ?>'de</option>
            <?php
            }
            ?>
          </select><br><br>
          <div class="form-group">
            <label>
              <font color="red">(*)</font>Hatırlatma Bildirimi Gönderim Tarihi
            </label>
            <input name="reminder-date" class="form-control date-picker" placeholder="GG-AA-YYYY" type="text">
          </div>
        </div>
      </div>
    </div><br>



    <input type="submit" value=" Kaydet" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
  </form>
</div>