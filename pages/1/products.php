<?php


use App\Model\ProductModel; 


$ProductModel = new ProductModel();

$products = $ProductModel->getAll(); // Assuming getAll() method exists to fetch all products



$pids = @$_GET["id"];
if ($pids && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
	permcontrol("productdelete");
	$qcont = $ac->prepare("SELECT * FROM products WHERE ID = ?");

	$qcont->execute(array($pids));
	$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
	if ($qkx) {
		$pdq = $ac->prepare("DELETE FROM products WHERE id = ?");
		$pdq->execute(array($pids));

		header("Location: index.php?p=products&type=delete&code=0882md25&pid=$pids");
	}
}

?>


<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h5 class="text-blue">Ürün/Hizmet Listesi</h5>

        </div>
        <?php if (permtrue("productadd")) { ?>
        <a href="index.php?p=product-new"><button type="button" class="btn btn-primary btn-sm float-right">
                <i class="fa fa-plus"></i> Yeni Ekle
            </button></a>
        <?php } ?><br><br>
    </div>
    <table id="tblProducts" class="table-hover table-responsive-sm table-bordered data-table">
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
                <th>İşlem</th>

            </tr>
        </thead>
        <tbody>
            <?php
			$siraNo = 1;
            foreach ($products as $product) {

				?>
            <tr>
                <td class="wrap text-center">
                    <?php echo $siraNo; ?>
                </td>
                <td>
                    <?php echo $product->StokKodu; ?>
                </td>
                <td class="text-nowrap" data-tooltip="<?php echo $product->Adi; ?>">
                    <?php echo shorted($product->Adi,40); ?>
                </td>
                <td>
                    <?php echo $product->birim; ?>
                </td>
                <td>
                    <?php echo $product->AlisFiyati . " " . $product->AlisParaBirimi; ?>
                </td>
                <td>
                    <?php echo $product->SatisFiyati . " " . $product->SatisParaBirimi; ?>
                </td>
                <td>
                    <?php echo $product->Aciklama; ?>
                </td>
                <td>
                    <?php echo !empty($product->OlusturmaTarihi) ? str_replace('-', '.', $product->OlusturmaTarihi) : '-'; ?>
                </td>
                <td class="text-center text-nowrap col-md-1 pl-3 pr-3">
                    <?php
						if (permtrue("productedit")) { ?>

                    <a class="btn btn-sm btn-outline-info" data-tooltip="Düzenle"
                        href="index.php?p=product-edit&id=<?php echo $as["ID"]; ?>">
                        <i class="fa fa-edit"></i></a>
                    <?php } ?>

                    <!-- <?php if (permtrue("productdel")) { ?><a onClick="return confirm('<?php echo $as["Adi"]; ?> isimli ürün/hizmeti sistemden kaldırmak istediğinize emin misiniz?')" href="index.php?p=products&mode=delete&code=04md177&reg=true&md=active&pid=<?php echo $as["id"]; ?>"><span class="badge badge-danger">Sil</span></a><?php } ?></td> -->
                    <a href="#" class="btn btn-sm btn-danger" data-tooltip="Sil!"
                        onClick="deleteRecord('<?php echo $product->Adi; ?> isimli ürün/hizmeti sistemden kaldırmak istediğinize emin misiniz?',<?php echo $product->ID; ?>,'products')">
                        <i class="fa fa-trash"></i></a>
                    <!-- <button class="badge badge-info" onclick="deleteProduct('<?php echo $as["Adi"]; ?> isimli ürün/hizmeti sistemden kaldırmak istediğinize emin misiniz?',<?php echo $as["ID"]; ?>)">swal</button> -->

            </tr>
            <?php
				$siraNo = $siraNo + 1;
			} ?>
        </tbody>
        <tfoot>
            <tr>
                <th style="width: 5%;">#Sıra</th>
                <th style="width: 15%;">Stok Kodu</th>
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

<script src="include/js/data-table.js"></script>