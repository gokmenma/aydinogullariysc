<?php
$pids = @$_GET["id"];
if ($pids && @$_GET["mode"] == "delete") {

  permcontrol("sdelete");
  $pdq = $ac->prepare("DELETE FROM sales WHERE id = ?");
  $pdq->execute(array($pids));

  $aca = $ac->prepare("DELETE FROM payments WHERE saleid = ?");
  $aca->execute(array($pids));
  header("Location: index.php?p=sales&deleted=true");
}


?>

  </div>
  <table class="data-table stripe hover">
    <thead>
      <tr>
        <th scope="col">#Sıra</th>
        <th>Müşteri</th>
        <th>Hizmet</th>
        <th>Sonraki Ödeme</th>
        <th>Hatırlatma</th>
        <th>Ücret</th>
        <th>İşlem</th>

      </tr>
    </thead>
    <tbody>

      <?php
      $cq = $ac->prepare("SELECT * FROM sales WHERE deleted = ? ORDER by id ASC");
      $cq->execute(array(0));
      $kx = 1;
      while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {
        $miq = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
        $miq->execute(array($as["pay_method"]));
        $mp = $miq->fetch(PDO::FETCH_ASSOC);

        $c = $ac->prepare("SELECT * FROM customers WHERE id = ?");
        $c->execute(array($as["cid"]));
        $mc = $c->fetch(PDO::FETCH_ASSOC);

        $s = $ac->prepare("SELECT * FROM services WHERE id = ?");
        $s->execute(array($as["sid"]));
        $ms = $s->fetch(PDO::FETCH_ASSOC);

        $pyq = $ac->prepare("SELECT * FROM payments WHERE saleid = ? AND okey = ? ORDER BY pid ASC LIMIT 1");
        $pyq->execute(array($as["id"], 0));
        $pym = $pyq->fetch(PDO::FETCH_ASSOC);


        $dtt = dtf(TODAY, $pym["lastdate"]);

        $rccont = $ac->prepare("SELECT * FROM reminders WHERE sid = ?");
        $rccont->execute(array($as["id"]));
        $rcs = $rccont->fetch(PDO::FETCH_ASSOC);

        if ($rcs["mail"] == 1 and $rcs["sms"] == 1) {
          $tc = "E-Mail,SMS";
        } elseif ($rcs["mail"] == 1 and $rcs["sms"] == 0) {
          $tc = "E-Mail";
        } elseif ($rcs["mail"] == 0 and $rcs["sms"] == 1) {
          $tc = "SMS";
        } else {
          $tc = "Hiçbiri";
        }

        $asd = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
        $asd->execute(array($as["pay_method"]));
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
        <tr>
          <td scope="row"><?php echo $kx; ?></td>
          <td><?php echo $mc["name"]; ?></td>
          <td><?php echo $ms["stitle"]; ?></td>
          <?php if ($dtt > -10) { ?>
            <td style="<?php echo $dtt <= 3 ? 'color:red' : ''; ?>"><?php echo $dtt; ?> Gün Sonra</td>
          <?php } else { ?>
            <td>
              -
            </td>
          <?php } ?>
          <td><?php echo $tc; ?></td>

          <td style="font-weight: bold"><?php echo $as["price"] . " $prx"; ?></td>
          <td>
            <?php if (permtrue("sedit")) { ?>
              <a onClick="return confirm('Alınan ödemelerin sıfırlanmasına karşın, düzenle bölümü pasif edilmiştir. Satışı silerek yeniden oluşturabilirsiniz.')" href="#"><span class="badge badge-info">Düzenle</span></a><?php
                                                                                                                                                                                                                          }
                                                                                                                                                                                                                          if (permtrue("sreminder")) {
                                                                                                                                                                                                                            ?><a href="index.php?p=reminder-planner&reg=true&sid=<?php echo $as["id"]; ?>"><span class="badge badge-success">Hatırlatıcı</span></a>
            <?php
                                                                                                                                                                                                                          }

                                                                                                                                                                                                                          if (permtrue("sdelete")) {
            ?>
              <a onClick="return confirm('Bu işlem sonrası, satışa ait tüm ödemeler silinecektir. Silmek istediğinize emin misiniz?')" href="index.php?p=sales&reg=true&mode=delete&id=<?php echo $as["id"]; ?>"><span class="badge badge-danger">Sil</span></a>
            <?php
                                                                                                                                                                                                                          }
            ?>
            &nbsp;&nbsp;
          </td>

        </tr>
      <?php
        $kx = $kx + 1;
      } ?>
    </tbody>
  </table>
</div>