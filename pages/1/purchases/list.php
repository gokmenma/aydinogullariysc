<?php

$pids = @$_GET['id'];

if ($_GET["st"] == "success-mail") {
	showAlert("success", "Mail başarı ile gönderildi!");
}else if (@$_GET["st"] =="unsuccessful"){
    showAlert("alert","Mail gönderilirken bir hata oluştu" );
}

use App\Helper\Helper;

?>

<div class="content pd-20 bg-white border-radius-8 box-shadow mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h5 class="text-blue">Satın Alma Talepleri</h5>
        </div>
        <div class="float-right">
            <?php
            if (permtrue('purchase-demand-add')) {
            ?>
                <a href="index.php?p=purchase-demand-new"><button type="button" class="btn btn-primary btn-sm"><i
                            class="fa fa-plus"></i> Yeni Talep</button></a>
            <?php }
            if (permtrue('purchaseadd')) { ?>

                <a href="index.php?p=purchase-new"><button type="button" class="btn btn-success btn-sm"><i
                            class="fa fa-plus"></i> Yeni Sipariş</button></a>
            <?php } ?>
        </div>

    </div>

    <div class="clearfix mb-20">
        <button type="button" id="showdemand" class="btn btn-primary btn-sm" data-toggle="button" aria-pressed="false"
            autocomplete="off">
            Tamamlanan Talepleri Göster
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
                        echo $purc['state'];
                        //echo Helper::getStateBadge($purc['state']);
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
                    <?php if ($purc['type'] == 1) {
                        $demand = 'demand-';
                        $type = 'TALEP';
                    } else {
                        $demand = '';
                        $type = 'SİPARİŞ';
                    } ?>
                    <td>
                        <?php echo $type ?>
                    </td>
                    <td style="width:1%; white-space: nowrap;">

                        <a type="button" href="index.php?p=purchase-<?php echo $demand ?>edit&id=<?php echo $pid; ?>"
                            class="btn btn-sm btn-outline-info" data-tooltip="Düzenle"><i class="fa fa-pencil"></i></a>

                        <?php

                        if ($purc['state'] == 1) {
                            $deleteLink = "deleteRecord('" . $purc['siparisNo'] . " nolu satın almayı silmek istediğinize emin misiniz?'," . $purc['id'] . ",'purchases')";
                            $deleteToolTip = 'Sil';
                            $toolTipLocation = 'top';
                        } else {
                            $deleteLink = '';
                            $deleteToolTip = 'Tamamlanmış Kayıtlar Silinemez!';
                            $toolTipLocation = 'left';
                        };
                        ?>
                        <button type="button" class="btn btn-sm btn-danger"
                            data-tooltip="<?php echo $deleteToolTip; ?>" data-tooltip-location="<?php echo $toolTipLocation; ?>"
                            onClick="<?php echo $deleteLink ?>">
                            <i class="fa fa-trash"></i></button>

                        <div class="dropdown d-inline">
                            <button class="btn btn-secondary btn-sm" type="button" id="dropdownMenu2"
                                data-toggle="dropdown">
                                <i class="fa fa-ellipsis-v ml-1 mr-1"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail"
                                aria-labelledby="dropdownMenu2">

                                <?php
                                if ($purc['type'] == 1 && $purc['state'] == 1) {
                                ?>
                                    <a href="index.php?p=purchase-edit&id=<?php echo $pid ?>&demand=true" class="dropdown-item"
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
                                    } elseif ($purc['state'] == 2) {
                                        $state = 'Bekliyor Olarak İşaretle';
                                    }
                                ?>
                                    <a href="#" class="dropdown-item done-demand" data-id=" <?php echo $pid; ?>" type="button">
                                        <i class="fa fa-check"></i>
                                       sdfdfsdf</a>
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
                fetch("api/purchase.php", {
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
        table.column(9).search('TALEP|SİPARİŞ', true, false).draw();
    }



    $("#showdemand").click(function() {
        var currentText = $(this).text(); // Mevcut buton metnini al

        var table = new DataTable('#myTable');
        // Mevcut metni kontrol et
        if (currentText.includes("Göster")) {
            $(this).text(currentText.replace("Göster", "Gizle")); // Metni değiştir
            // DataTable'da filtreleme işlemi

            table.columns([5, 9]).search('').draw();
        } else {
            $(this).text(currentText.replace("Gizle", "Göster")); // Metni değiştir
            filterWaitingDemand();
        }

    });
</script>