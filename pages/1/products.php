<?php

$pids = @$_GET["pid"];
if ($pids && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
	permcontrol("prodelete");
	$qcont = $ac->prepare("SELECT * FROM products WHERE id = ?");
	$qcont->execute(array($pids));
	$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
	if ($qkx) {
		$pdq = $ac->prepare("DELETE FROM products WHERE id = ?");
		$pdq->execute(array($pids));

		header("Location: index.php?p=products&type=delete&code=0882md25&pid=$pids");
	}
}


?>
<div class="pd-20 bg-white border-radius-8 box-shadow mb-30">
	<?php
	if (@$_GET["st"] == "newsuccess") {



	?>
		<div class="alert alert-success" role="alert">
			Müşteri başarıyla sisteme eklendi. Aşağıdaki listeden görüntüleyebilirsiniz.
		</div>
	<?php
	}
	if (@$_GET["type"] == "delete" and @$_GET["cid"]) {
	?>
		<div class="alert alert-success" role="alert">
			<?php echo "#" . $_GET["pid"]; ?> no'lu hizmetinize ait bilgiler başarıyla silindi.
		</div>
	<?php
	}
	?>
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Ürün/Hizmet Listesi</h5>

		</div>
		<?php if (permtrue("proadd")) { ?>
			<a href="index.php?p=new-product&cc=087s3"><button type="button" class="btn btn-success float-right"> Yeni </button></a>
		<?php } ?><br><br>
	</div>
	<table class=" table table-hover table-bordered data-table">
		<thead>
			<tr>
				<th scope="col">#Sıra</th>
				<th>Ürün/Hizmet Adı</th>
				<th>Türü</th>
				<th>Tedarikçi</th>
				<th>Kategori</th>
				<th>İşlem</th>

			</tr>
		</thead>
		<tbody>
			<?php
			$cq = $ac->prepare("SELECT * FROM products ORDER by ID ASC");
			$cq->execute();
			$siraNo = 1;
			while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {



				$miq = $ac->prepare("SELECT * FROM mainservices WHERE id = ?");
				$miq->execute(array($as["ID"]));
				$mms = $miq->fetch(PDO::FETCH_ASSOC);
			?>
				<tr>
					<td scope="row"><?php echo $siraNo; ?></td>
					<td><?php echo $as["Adi"]; ?></td>
					<td><?php echo $as["Turu"]; ?></td>
					<td><?php echo $as["TedariciId"]; ?></td>
					<td><?php echo $mms["stitle"]; ?></td>
					<td>
						<?php
						if (permtrue("seredit")) { ?><a href="index.php?p=edit-product&reg=true&md=update&pid=<?php echo $as["ID"]; ?>"><span class="badge badge-info">Düzenle</span></a>
						<?php } ?>
						&nbsp;&nbsp;

						<!-- <?php if (permtrue("serdelete")) { ?><a onClick="return confirm('<?php echo $as["Adi"]; ?> isimli ürün/hizmeti sistemden kaldırmak istediğinize emin misiniz?')" href="index.php?p=products&mode=delete&code=04md177&reg=true&md=active&pid=<?php echo $as["id"]; ?>"><span class="badge badge-danger">Sil</span></a><?php } ?></td> -->
						<a href="#" onClick="deleteRecord('<?php echo $as["Adi"]; ?> isimli ürün/hizmeti sistemden kaldırmak istediğinize emin misiniz?',<?php echo $as["ID"]; ?>,'products')"><span class="badge badge-danger">Sil</span></a>
						<!-- <button class="badge badge-info" onclick="deleteProduct('<?php echo $as["Adi"]; ?> isimli ürün/hizmeti sistemden kaldırmak istediğinize emin misiniz?',<?php echo $as["ID"]; ?>)">swal</button> -->

				</tr>
			<?php
				$siraNo = $siraNo + 1;
			} ?>
		</tbody>
	</table>
</div>



<!-- Include SweetAlert and jQuery -->
<!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<!-- 
<script>
    function deleteProduct(msg,ID) {
		console.log(ID);
        Swal.fire({
            title: "Emin misiniz?",
            text: msg,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Evet,Sil!",
			cancelButtonText: "Vazgeç!"
			
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, trigger AJAX request to delete product
                $.ajax({
                    type: "POST",
                    url: "index.php?p=products&mode=delete&code=04md177&reg=true&md=active&pid=" + ID, // PHP script for deletion
                    data: { id: ID },
                    success: function(response) {
                        // Handle success response (optional)
                        Swal.fire({
                            title: "Başarılı!",
                            text: "Ürün/Hizmet Başarılı ile silindi!",
                            icon: "success"
                        }).then(() => {
                            // Redirect to products page
                            window.location.href = "index.php?p=products";
                        });
                    },
                    error: function(xhr, status, error) {
                        // Handle error if deletion fails (optional)
                        console.error(xhr.responseText);
                        Swal.fire({
                            title: "Error!",
                            text: "Something went wrong.",
                            icon: "error"
                        });
                    }
                });
            }
        });
    }
</script> -->
