<?php

$pids = @$_GET['id'];

if ((@$_GET["st"] ?? "") == "success-mail") {
    showAlert("success", "Mail başarı ile gönderildi!");
} else if ((@$_GET["st"] ?? "") == "unsuccessful") {
    showAlert("alert", "Mail gönderilirken bir hata oluştu");
}



use App\Helper\Helper;


?>
<style>

</style>
<div class="form-card animate-fade-in">
    <div class="form-card-header d-flex justify-content-between align-items-center mb-4">
        <div class="header-left-inner">
            <div class="card-icon card-icon-blue" style="background: #eff6ff; color: #3b82f6;">
                <i class="fa fa-shopping-cart"></i>
            </div>
            <div>
                <h5 class="text-blue m-0">Satın Alma Talepleri</h5>
                <p class="mb-0 text-muted font-12" style="margin-top: 2px;">Satın alma taleplerini ve siparişlerini bu ekrandan yönetebilirsiniz.</p>
            </div>
        </div>
        <div>
            <a href="index.php?p=purchases/dashboard" class="btn btn-sm btn-info text-white mr-1" style="border-radius: 8px; padding: 8px 16px; font-weight: 500; height: 38px;">
                <i class="fa fa-dashboard mr-1"></i> Dashboard
            </a>
            <?php if (permtrue('purchase-demand-add')) { ?>
                <a href="index.php?p=purchase-demand-new" class="btn btn-sm btn-primary text-white" style="border-radius: 8px; padding: 8px 16px; font-weight: 500; height: 38px;">
                    <i class="fa fa-plus mr-1"></i> Yeni Talep
                </a>
            <?php } ?>
            <?php if (permtrue('purchaseadd')) { ?>
                <a href="index.php?p=purchases/manage" class="btn btn-sm btn-success text-white ml-1" style="border-radius: 8px; padding: 8px 16px; font-weight: 500; height: 38px;">
                    <i class="fa fa-plus mr-1"></i> Yeni Sipariş
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="d-flex mb-4">
        <button type="button" id="showdemand" class="btn btn-sm btn-outline-primary d-flex align-items-center" data-toggle="button" aria-pressed="false" autocomplete="off" style="border-radius: 8px; padding: 8px 16px; font-weight: 500;">
            <i class="fa fa-eye mr-2"></i> Tamamlanan Talepleri Göster
        </button>
    </div>


    <table id="myTable" class="data-table table-hover table-bordered table-responsive">
        <thead>
            <tr>
                <th scope="col">SiparisNo</th>
                <th>Firma Adı</th>
                <th>Kayıt Tarihi</th>
                <th>Termin Tarihi</th>
                <th>Toplam Fiyat</th>
                <th>Durum </th>
                <th>Ödeme Vadesi</th>
                <th>Fatura No</th>
                <th>Fatura Tarihi</th>
                <th>Oluşturan</th>

                <th style="width:1%">Tip</th>

                <th class="text-nowrap">İşlem</th>

            </tr>
        </thead>
        <tbody>
            <?php
            $sira = 1;
            $query = $ac->prepare('SELECT * FROM purchases ORDER BY id desc');
            $query->execute();

            while ($purc = $query->fetch(PDO::FETCH_ASSOC)) {
            ?>
                <tr>
                    <td>

                        <?php echo $purc['siparisNo']; ?>
                    </td>

                    <?php
                    $customer_name = getCustomerName($purc['companyID']);
                    $pid = $purc['id'];
                    ?>

                    <td data-tooltip="<?php echo $customer_name; ?>">
                        <?php
                        echo shorted($customer_name, 40);
                        ?>
                    </td>
                    <td>
                        <?php echo $purc['create_time'] ?>
                    </td>
                    <td>
                        <?php echo $purc['deadline'] ?>
                    </td>
                    <td>
                        <?php echo $purc['altToplam'] . ' ₺'; ?>
                    </td>
                    <td>
                        <?php
                        echo Helper::getStateBadge($purc['state']);
                        //echo $purc['state'] == 2 ? "<span class='badge badge-success'>Tamamlandı</span>" : "<span class='badge badge-warning'>Bekliyor</span>";
                        ?>
                    </td>
                    <td>
                        <?php echo $purc['payment_period'] ?>
                    </td>

                    <td>
                        <?php echo $purc['invoice_number'] ?>
                    </td>

                    <td>
                        <?php echo $purc['invoice_date'] ?>
                    </td>
                    <td>
                        <?php
                        $user = getUserName($purc['creator']); // Oluşturan kullanıcı
                        $updatedUser = getUserName($purc['updater']); // Güncelleyen kullanıcı
                        $createdDate = $purc['create_time'] ?? ''; // Oluşturulma tarihi
                        $updatedDate = $purc['updated_at'] ?? ''; // Güncelleme tarihi

                        echo "<span class='custom-tooltip' data-tooltip=\"Oluşturulma Tarihi: {$createdDate}\nGüncelleyen: {$updatedUser}\nGüncelleme Tarihi: {$updatedDate}\">";
                        if (!empty($user)) {
                            echo shorted($user, 20);
                        } else {
                            echo 'sistem';
                        }
                        echo '</span>';
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($purc['type'] == 1) {
                            $type = 'TALEP';
                        } else if ($purc['type'] == 2) {
                            $type = 'FİYAT TALEBİ';
                        } else {
                            $type = 'SİPARİŞ';
                        }

                        echo $type ?>
                    </td>
                    <td style="width:1%; white-space: nowrap;">
                        <?php
                        if ($purc['type'] == 1) {
                            $link = 'index.php?p=purchase-demand-edit&id=' . $pid;
                        } else {
                            $link = 'index.php?p=purchases/manage&id=' . $pid;
                        }

                        ?>

                        <a type="button" href="<?php echo $link ?>"
                            class="btn btn-sm btn-outline-info" data-tooltip="Düzenle"><i class="fa fa-pencil"></i></a>

                        <?php

                        if ($purc['state'] == 2) {
                            $deleteLink = '';
                            $deleteToolTip = 'Tamamlanmış Kayıtlar Silinemez!';
                            $toolTipLocation = 'left';
                        } else {
                            $deleteLink = "deleteRecord('" . $purc['siparisNo'] . " nolu satın almayı silmek istediğinize emin misiniz?'," . $purc['id'] . ",'purchases')";
                            $deleteToolTip = 'Sil';
                            $toolTipLocation = 'top';
                        };
                        ?>
                        <?php if(permtrue("purchasedelete")){;?>
                            
                        <button type="button" class="btn btn-sm btn-danger"
                            data-tooltip="<?php echo $deleteToolTip; ?>" data-tooltip-location="<?php echo $toolTipLocation; ?>"
                            onClick="<?php echo $deleteLink ?>">
                            <i class="fa fa-trash"></i></button>
                        <?php } ?>
                        <div class="dropdown d-inline">
                            <button class="btn btn-secondary btn-sm" type="button" id="dropdownMenu2"
                                data-toggle="dropdown">
                                <i class="fa fa-ellipsis-v ml-1 mr-1"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail"
                                aria-labelledby="dropdownMenu2">

                                <?php
                                if ($purc['type'] == 1 && $purc['state'] == 0) {
                                ?>

                                    <a href="index.php?p=purchases/manage&talep_id=<?php echo $pid ?>&demand=true" class="dropdown-item"
                                        type="button">
                                        <i class="fi fi-shopping-cart"></i>
                                        Sipariş Oluştur</a>
                                <?php
                                };
                                ?>


                                <a href="index.php?p=purchase-demand-detail&id=<?php echo $pid ?>" target="_blank"
                                    class="dropdown-item" type="button">
                                    <i class="fa fa-folder-o"></i>
                                    Talep Formunu Göster</a>
                                <a href="index.php?p=purchase-detail&id=<?php echo $pid ?>" target="_blank"
                                    class="dropdown-item" type="button">
                                    <i class="fa fa-folder-o"></i>
                                    Sipariş Formunu Göster</a>

                                <?php
                                if ($purc['type'] == 1) {
                                    if ($purc['state'] == 0) {
                                        $state = 'Tamamlandı Yap';
                                    } elseif ($purc['state'] == 1) {
                                        $state = 'Bekliyor Olarak İşaretle';
                                    }
                                ?>
                                    <a href="#" class="dropdown-item done-demand" data-id=" <?php echo $pid; ?>" type="button">
                                        <i class="fa fa-check"></i>
                                        <?php echo $state; ?></a>
                                <?php } ?>
                                <a href="index.php?p=report-send-as-mail&type=purchase&id=<?php echo $pid ?>"
                                    class="dropdown-item" type="button">
                                    <i class="fa fa-envelope"></i>
                                    Mail gönder</a>

                            </div>

                        </div>

                    </td>
                </tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col">SiparisNo</th>
                <th>Firma Adı</th>
                <th>Kayıt Tarihi</th>
                <th>Termin Tarihi</th>
                <th>Toplam Fiyat</th>
                <th>Durum </th>
                <th>Ödeme Vadesi</th>
                <th>Fatura No</th>
                <th>Fatura Tarihi</th>
                <th>Tip</th>

                <th class="text-nowrap">İşlem</th>

            </tr>
        </tfoot>
    </table>
</div>

<script src="include/js/data-table.js"></script>
<script>
    $(document).ready(function() {
        filterWaitingDemand();
    })

    //Siparis Talebini tamamlandı yap
    $(document).on('click', '.done-demand', function() {
        let purchaseId = $(this).data('id');

        let formData = new FormData();
        formData.append('id', purchaseId);
        formData.append('action', 'doneDemand');

        Swal.fire({
            title: "Emin misiniz?",
            text: "Satın Alma talebi kapatılacaktır!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Evet, Kapat!"
        }).then((result) => {
            if (result.isConfirmed) {
                fetch("App/api/purchase.php", {
                        method: "POST",
                        body: formData

                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status == 'success') {
                            swal.fire({
                                title: 'Başarılı!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'Tamam'
                            }).then((result) => {
                                location.reload();
                            })

                        }
                    })


            }
        });


    });

    function filterWaitingDemand() {
        var table = $('#myTable').DataTable();
        table.column(5).search('Bekliyor').draw();
        table.column(10).search('TALEP|SİPARİŞ', true, false).draw();
    }



    $("#showdemand").click(function() {
        var currentText = $(this).text(); // Mevcut buton metnini al

        var table = new DataTable('#myTable');
        // Mevcut metni kontrol et
        if (currentText.includes("Göster")) {
            $(this).text(currentText.replace("Göster", "Gizle")); // Metni değiştir
            // DataTable'da filtreleme işlemi

            table.columns([5, 10]).search('').draw();
        } else {
            $(this).text(currentText.replace("Gizle", "Göster")); // Metni değiştir
            filterWaitingDemand();
        }

    });
</script>