<?php

use App\Helper\Helper;
use App\Helper\Security;
use App\Model\DefineModel;

//DefineModel sınıfı çağrıldı
$Define = new DefineModel();

$logger = \getLogger("Ürünler");
$logger->info("Ürün/Hizmet listesi görüntülendi.",[
    'username' => $_SESSION['username']
]);
?>

<div class="bg-white premium-section-card box-shadow mb-30 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-30" style="flex-wrap: wrap; gap: 15px;">
        <div>
            <h4 class="text-blue weight-600 mb-0">Ürün/Hizmet Listesi</h4>
        </div>
        <div>
            <?php if (permtrue("productadd")) { ?>
                <a href="index.php?p=products/manage" class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> Yeni Ekle</a>
            <?php } ?>
        </div>
    </div>
    <div class="table-responsive">
        <table id="tblProducts" class="data-table table-hover table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%;">#Sıra</th>
                    <th style="width: 15%;">Stok Kodu</th>
                    <th>Ürün/Hizmet Adı</th>
                    <th>Birimi</th>
                    <th>Alış Fiyatı</th>
                    <th>Satış Fiyatı</th>
                    <th>Açıklama</th>
                    <th>Kayıt Tarihi</th>
                    <th style="width: 10%;">İşlem</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th>#Sıra</th>
                    <th>Stok Kodu</th>
                    <th>Ürün/Hizmet Adı</th>
                    <th>Birimi</th>
                    <th>Alış Fiyatı</th>
                    <th>Satış Fiyatı</th>
                    <th>Açıklama</th>
                    <th>Kayıt Tarihi</th>
                    <th>İşlem</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script src="include/js/data-table.js"></script>