<div class="content pd-20 bg-white border-radius-8 box-shadow mb-30">
  <div class="clearfix mb-20">
    <div class="pull-left">
      <h5 class="text-blue">Satın Alma Talepleri</h5>
    </div>

    <a href="index.php?p=new-sales&cc=087s3"><button style="float:right;" type="button" class="btn btn-success">Yeni
        Giriş Yap</button></a> <br><br>
    <?php ?>

  </div>
  <table class="data-table select-row table-hover table-bordered">
    <thead>
      <tr>
        <th scope="col">#Sıra</th>
        <th>Firma Adı</th>
        <th>Termin Tarihi</th>
        <th>Kur </th>
        <th>Ödeme Vadesi</th>
        <th>Toplam Fiyat</th>
        <th class="text-nowrap">İşlem</th>

      </tr>
    </thead>
    <tbody>
      <?php
      $sira = 1;
      $query = $ac->prepare("SELECT * FROM purchases ORDER BY id");
      $query->execute();

      while ($purc = $query->fetch(PDO::FETCH_ASSOC)) {
        ?>
        <tr>
          <td>
            <?php echo $sira; ?>
          </td>

          <td>
            <?php
            // Firma id'si ile eşleşen kaydın Firma Adı getirilir 
            $compid = $purc["companyID"];
            $sql = $ac->prepare("SELECT * FROM customers WHERE id = ? ");
            $sql->execute(array($compid));
            $company = $sql->fetch(PDO::FETCH_ASSOC);
            $pid = $purc["id"];
            $encrypted_id = str_rot13($pid); // ID'yi şifrele
          

            $number = $pid; // Şifrelemek istediğiniz sayı
            $key = 98765; // Şifreleme için kullanılacak anahtar
          
            $encrypted_id = encrypt($number);
            //echo "Şifrelenmiş sayı: " . $encrypted_id . "<br>";
          



            //Firma Adı tabloya yazılır
            echo $company["company"]; ?>
          </td>
          <td>
            <?php echo $purc["deadline"] ?>
          </td>
          <td>
            <?php echo $purc["currency"] ?>
          </td>
          <td>
            <?php echo $purc["payment_period"] ?>
          </td>
          <td>
            <?php echo tlFormat($purc["altToplam"]) . ' ₺';?>
          </td>
          <td style="width:10%; white-space: nowrap;">

            <button type="button" class="btn btn-sm btn-gray"><i class="fa fa-info-circle"></i></button>
            <a type="button" href="index.php?p=purchase-edit&pid=<?php echo $encrypted_id; ?>"
              class="btn btn-sm btn-info text-white"><i class="fa fa-edit"></i></a>
            <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>

          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>