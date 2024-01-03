<?php

$xid = @$_GET["pid"];
if ($xid && @$_GET["mode"] == "delete") {
  $stcontrol = $ac->prepare("SELECT * FROM perms WHERE id = ?");
  $stcontrol->execute(array($xid));
  $sts = $stcontrol->fetch(PDO::FETCH_ASSOC);
  if (!$sts) {
    header("Location: index.php?p=permission-settings&err=true");
    exit;
    die;
  }

  $ucek = $ac->prepare("SELECT * FROM users WHERE permission = ? ");
  $ucek->execute(array($xid));
  while ($usay = $ucek->fetch(PDO::FETCH_ASSOC)) {

    $upuser = $ac->prepare("UPDATE users SET permission = ?, statu = ? WHERE id = ?");
    $upuser->execute(array(0, 0, $usay["id"]));
  }
  $stdel = $ac->prepare("DELETE FROM perms WHERE id = ?");
  $stdel->execute(array($xid));

  header("Location: index.php?p=permission-settings&type=delete&code=0882md25&pid=$xid");
}


?>
<?php if (@$_GET["st"] == "newsuccess") {
?>
  <div class="alert alert-success" role="alert">
    Kayıt oluşturuldu.
  </div>
<?php
} ?>

<div class="content pd-20 bg-white border-radius-8 box-shadow mb-30">
  <div class="clearfix mb-20">
    <div class="pull-left">
      <h5 class="text-blue">Pozisyon Adlandırmaları & İzin Yönetimi</h5>
      
    </div>
    <a href="index.php?p=new-perm&cc=0014" class="float-right btn btn-success">Yeni Oluştur</a>
  </div>

  <table class="data-table table-bordered select-row table-hover ">
    <thead>
      <tr>
        <th>#Sıra</th>
        <th>Pozisyon Adı</th>
        <th>İzinler</th>
        <th>İşlem</th>

      </tr>
    </thead>
    <tbody>
      
      <tr>
        <?php
        $pxc = $ac->prepare("SELECT * FROM perms");
        $pxc->execute();
        $kx = 1;
        while ($px = $pxc->fetch(PDO::FETCH_ASSOC)) {
        ?>

          <td scope="row"><?php echo $kx; ?></td>
          <td><?php echo $px["p_title"]; ?></td>
          <td><?php echo $px["cadd"] ? "Müşteri ekle, " : ""; ?><?php echo $px["cedit"] ? "Müşteri düzenle, " : ""; ?><?php echo $px["cdelete"] ? "Müşteri sil, " : ""; ?><?php echo $px["sadd"] ? "Satış oluştur, " : ""; ?><?php echo $px["sedit"] ? "Satış Düzenle, " : ""; ?><?php echo $px["sdelete"] ? "Satış sil... " : "..."; ?></td>
          <td> <a href="index.php?p=edit-perm&reg=true&md=update&id=<?php echo $px["id"]; ?>"><span class="badge badge-info">Düzenle</span></a>

            &nbsp;&nbsp; <a onClick="return confirm('Bu yetkiyi silmenizle birlikte, bu yetkiye sahip tüm kullanıcıların hesapları dondurulacaktır. Üye düzenleme sayfasından tekrar aktifleştirebilirsiniz.')" href="index.php?p=permission-settings&mode=delete&pid=<?php echo $px["id"]; ?>"><span class="badge badge-danger">Sil</span></a></td>
      </tr>
    <?php $kx++;
        } ?>


    </tbody>
  </table>
</div>
		
