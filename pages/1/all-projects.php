<?php

$pids = @$_GET["id"];
if ($pids && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
  permcontrol("serviceDel");
  $stItem = $ac->prepare("
      SELECT p.*, 
             c.company as customer_name,
             u.username as creator_username,
             st.title as service_type_name,
             ss.title as status_name,
             sr.title as region_name
      FROM projects p
      LEFT JOIN customers c ON c.id = p.pcid
      LEFT JOIN users u ON u.id = p.creativer
      LEFT JOIN units st ON st.id = p.p_type AND st.statu = 'servicestype'
      LEFT JOIN units ss ON ss.id = p.pstatu AND ss.statu = 'servicestatus'
      LEFT JOIN units sr ON sr.id = p.pregion AND sr.statu = 'serviceregion'
      WHERE p.id = ? AND p.deleted_at IS NULL
  ");
  $stItem->execute([$pids]);
  $itemData = $stItem->fetch(PDO::FETCH_ASSOC);

  $serviceModel = new \App\Model\ServiceModel();
  $deletedBy = $_SESSION['lid'] ?? ($_SESSION['id'] ?? 0);
  $serviceModel->softDelete($pids, $deletedBy);

  if ($itemData && function_exists('audit_log')) {
      $serviceContext = [
          'id' => $pids,
          'service_number' => $itemData['service_number'] ?? null,
          'customer_id' => $itemData['pcid'] ?? null,
          'customer_name' => $itemData['customer_name'] ?? null,
          'service_type' => $itemData['service_type_name'] ?? ($itemData['p_type'] ?? null),
          'status' => $itemData['status_name'] ?? ($itemData['pstatu'] ?? null),
          'region' => $itemData['region_name'] ?? ($itemData['pregion'] ?? null),
          'price' => $itemData['price'] ?? null,
          'currency' => $itemData['currency'] ?? null,
          'start_date' => $itemData['start_date'] ?? null,
          'finish_date' => $itemData['finish_date'] ?? null,
          'description' => $itemData['description'] ?? null,
          'creator' => $itemData['creator_username'] ?? null,
          'created_at' => $itemData['created_at'] ?? null,
          'deleted_by_user_id' => $deletedBy,
          'deleted_by_username' => $_SESSION['username'] ?? null,
      ];
      audit_log(
          "delete",
          "services",
          "Servis silindi: " . ($itemData['service_number'] ?? '#' . $pids) . (!empty($itemData['customer_name']) ? " (" . $itemData['customer_name'] . ")" : ""),
          "service",
          $pids,
          $serviceContext
      );
  }
}
if (@$_GET["st"] == "yes") {
  showAlert('success', 'Numaralı Servis Başarı ile Güncellendi.');
}

?>
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
  <div class="clearfix mb-20">
    <div class="pull-left">
      <h5 class="text-blue">Oluşturulan Tüm Servisler</h5>
    </div>

    <a href="index.php?p=new-project"><button style="float:right;" type="button" class="btn btn-success">Yeni
        Giriş Yap</button></a> <br><br>
    <?php ?>

  </div>
  <table class="data-table select-row table-hover table-bordered table-responsive-sm">
    <thead>
      <tr>
        <th scope="col">Servis No</th>
        <th>Firma Adı</th>
        <th>Bölge/İlçe</th>
        <th>Servis Konusu </th>
        <th>Servis Açma Tarihi</th>
        <th>Durum</th>
        <th class="text-nowrap">İşlem</th>

      </tr>
    </thead>
    <tbody>
      <?php
      $sira = 1;
      $query = $ac->prepare("SELECT * FROM projects WHERE deleted_at IS NULL ORDER BY id desc");
      $query->execute();

      while ($purc = $query->fetch(PDO::FETCH_ASSOC)) {
        ?>
        <tr>
          <td>
            <?php echo "SA" . $purc["id"]; ?>
          </td>

          <td>
            <?php
            // Firma id'si ile eşleşen kaydın Firma Adı getirilir 
            $compid = $purc["pcid"];
            $sql = $ac->prepare("SELECT * FROM customers WHERE id = ? ");
            $sql->execute(array($compid));
            $company = $sql->fetch(PDO::FETCH_ASSOC);
            $pid = $purc["id"];

            //$encrypted_id = encrypt($pid);
            //echo "Şifrelenmiş sayı: " . $encrypted_id . "<br>";
            //Firma Adı tabloya yazılır
            echo $company["company"]; ?>
          </td>
          <td>
            <?php echo $purc["address"] ?>
          </td>
          <td>
            <?php
            // Firma id'si ile eşleşen kaydın Firma Adı getirilir 
            $compid = $purc["servicestype"];
            $sql = $ac->prepare("SELECT * FROM units WHERE id = ? ");
            $sql->execute(array($compid));
            $servicestype = $sql->fetch(PDO::FETCH_ASSOC);
            $pid = $purc["id"];

            //$encrypted_id = encrypt($pid);
            //echo "Şifrelenmiş sayı: " . $encrypted_id . "<br>";
            //Firma Adı tabloya yazılır
            echo $servicestype["title"]; ?>
          </td>
          <td>
            <?php echo $purc["pstart_date"] ?>
          </td>
          <td>
            <?php

            if ($purc["pstatu"] == 0) {
              echo "<span class='badge '>Seçim Yapılmamış</span>";
            } else if ($purc["pstatu"] == 1) {
              echo "<span class='badge badge-warning'>Bekliyor</span>";
            } else if ($purc["pstatu"] == 2) {
              echo "<span class='badge badge-primary'>Çalışıyor</span>";
            } else if ($purc["pstatu"] == 3) {
              echo "<span class='badge badge-success'>Tamamlandı</span>";
            } else if ($purc["pstatu"] == 4) {
              echo "<span class='badge badge-danger'>İptal Edildi</span>";
            }
            ?>
          </td>
          <td style="width:10%; white-space: nowrap;">


            <a type="button" href="index.php?p=edit-project&pid=<?php echo $pid; ?>"
              class="btn btn-sm btn-info text-white" data-tooltip="Düzenle"><i class="fa fa-edit"></i></a>

            <button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil"
              onClick="deleteRecord('<?php echo $purc["id"]; ?> nolu Servisi silmek istediğinize emin misiniz?',<?php echo $purc["id"]; ?>,'all-projects')"><i
                class="fa fa-trash"></i></button>
            <a type="button" href="index.php?p=services-detail&pid=<?php echo $pid ?>" class="btn btn-sm btn-secondary"
              data-tooltip="Detay"><i class="fa fa-info-circle"></i></a>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>