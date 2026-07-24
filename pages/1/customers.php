<?php

if (@$_GET["id"] && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
    permcontrol("customerdelete");
    $cdid = $_GET["id"];
    $contq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
    $contq->execute(array($cdid));
    if ($contq->fetch(PDO::FETCH_ASSOC)) {
        $deletq = $ac->prepare(
            "UPDATE customers
             SET deleted_at = ?, deleted_by = ?
             WHERE id = ? AND deleted_at IS NULL"
        );
        $deletq->execute(array(date('Y-m-d H:i:s'), $_SESSION['lid'] ?? 0, $cdid));


        if ($deletq) {
            header("Location: index.php?p=customers&id=$cdid&type=delete");
        }
    }
}

?>
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
    <!-- Modal -->
    <div class="modal fade" id="customerdetails" tabindex="-1" role="dialog"
        aria-labelledby="customerdetailsCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerdeteailHeader"> Detay Bilgisi</h5>
                    <button type="button" class="closeModal close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <style>
                    table tr td {
                        padding: 5px;
                    }
                    </style>
                    <table>
                        <tr>
                            <td><label for="">Kayıt Yapan Personel :</label></td>
                            <td><label for="" id="creator"></label></td>
                        </tr>
                        <tr>
                            <td><label for="">Kayıt Tarihi :</label></td>
                            <td><label for="" id="create_time"></label></td>
                        </tr>
                        <tr>
                            <td><label for="">Güncelleme Yapan Personel :</label></td>
                            <td><label for="" id="updater"></label></td>
                        </tr>
                        <tr>
                            <td><label for="">Güncelleme Tarihi :</label></td>
                            <td><label for="" id="updated_at"></label></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="closeModal btn btn-primary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->

    <div class="clearfix mb-20">
        <div class="pull-left">
            <h5 class="text-blue">Müşteri Listesi</h5>
            <p class="font-14"> </p>
        </div>
        <?php if (permtrue("customeradd")) { ?>
        <a href="index.php?p=customer-new"><button type="button" class="btn btn-primary btn-sm float-right"><i
                    class="fa fa-plus"></i> Yeni
                Müşteri</button></a>
        <?php } ?>
    </div>

    <table id="customerlist" class="data-table table-bordered table-hover">
        <thead>
            <tr>
                <th scope="col" class="app-item-number">Sıra</th>
                <th style="width:80%">Firma Adı</th>
                <th>Grup</th>
                <th>Teklif/Servis Sayısı</th>
                <th>E-Posta Adresi</th>
                <th>GSM</th>
                <th class="datatable-nosort" style="min-width:90px">İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $cq = $ac->prepare("SELECT * FROM customers WHERE deleted_at IS NULL ORDER by id DESC");
            $cq->execute();
            while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {
                $tqm = $ac->prepare("SELECT * FROM offers WHERE cid = ?");
                $tqm->execute(array($as["id"]));
                $tsay = $tqm->rowCount();

                $pqm = $ac->prepare("SELECT * FROM projects WHERE pcid = ?");
                $pqm->execute(array($as["id"]));
                $servissay = $pqm->rowCount();

                $tsayi = $tsay > 0 ? $tsay : "0";
                $servissayi = $servissay > 0 ? $servissay : "0";
                $tps = $tsayi . " / " . $servissayi;

                $grcek = $ac->prepare("SELECT * FROM cgroups WHERE id = ?");
                $grcek->execute(array($as["grp"]));
                $gaar = $grcek->fetch(PDO::FETCH_ASSOC);
                ?>
                <tr>
                    <td scope="row" class="text-center">
                        <?php echo $as["id"]; ?>
                    </td>
                    <td>
                        <?php if (permtrue("customeredit")) {
                            $link = "index.php?p=customer-edit&id=" . $as["id"];
                        } else {
                            $link = "#";
                        }
                        ?>
                        <a href="<?php echo $link ?>" data-toggle="tooltip"
                            data-tooltip="<?php echo $as["company"]; ?>">
                            <?php echo shorted($as["company"], 40); ?>
                        </a>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($gaar["title"] ?? ''); ?>
                    </td>
                    <td>
                        <?php echo $tps; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($as["email"] ?? ''); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($as["gsm"] ?? ''); ?>
                    </td>
                    <td>
                        <?php if (permtrue("customeredit")) { ?>
                        <a href="index.php?p=customer-edit&id=<?php echo $as["id"]; ?>"
                            data-tooltip="Görüntüle-Düzenle">
                            <span class="btn btn-sm btn-outline-info">
                                <i class="fa fa-pencil"></i>
                            </span>
                        </a>
                        <?php } ?>
                        <?php if (permtrue("customerdelete")) { ?>
                        <a href="#" data-tooltip="Sil"
                            onClick="deleteRecord('Devam ettiğiniz takdirde, müşteriye ait tüm bilgiler ve müşterinin adına düzenlenmiş olan teklif & projeler tamamen silinecektir. Devam etmek istiyor musunuz?','<?php echo $as['id']; ?>','customers')">
                            <span class="btn btn-sm btn-danger">
                                <i class="fa fa-trash"></i>
                            </span>
                        </a>
                        <?php } ?>

                        <div class="dropdown d-inline">
                            <button class="btn btn-secondary btn-sm" type="button" id="dropdownMenu_<?php echo $as['id']; ?>" data-toggle="dropdown">
                                <i class="fa fa-ellipsis-v ml-1 mr-1"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail" aria-labelledby="dropdownMenu_<?php echo $as['id']; ?>">
                                <a href="index.php?p=customer-label&id=<?php echo $as["id"]; ?>" target="_blank" class="dropdown-item" type="button">
                                    <i class="fa fa-print mr-2"></i>
                                    Etiket Göster</a>
                                <a href="index.php?p=send-mail&customer=<?php echo encrypt($as["id"]) ; ?>" target="_blank" class="dropdown-item" type="button">
                                    <i class="fa fa-envelope-o mr-2"></i>
                                    Email Gönder</a>
                                <a class="btn-detail btn dropdown-item" data-id="<?php echo $as["id"]; ?>" type="button">
                                    <i class="fa fa-copy mr-2"></i>
                                    Detay Bilgisi</a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col">Sıra</th>
                <th>Firma Adı</th>
                <th>Grup</th>
                <th>Teklif/Servis Sayısı</th>
                <th>E-Posta Adresi</th>
                <th>GSM</th>
                <th>İşlem</th>
            </tr>
        </tfoot>
    </table>
</div>
<script src="include/js/data-table.js"></script>
<script>
$(document).ready(function() {
    $(".btn-detail").click(function() {
        var id = $(this).data("id");
        $.ajax({
            method: "POST",
            url: "pages/1/ajax.php?type=customer-detail",
            dataType: "json",
            data: {
                id: id
            },
            success: function(response) {
                $("#customerdetails").modal("show");
                $("#creator").text(response.creator);
                $("#create_time").text(response.create_time);
                $("#updater").text(response.updater);
                $("#updated_at").text(response.updated_at);
            }
        });
    });
});

$(".closeModal").click(function() {
    $("#customerdetails").modal("hide");
});
</script>
