<?php

$ID = @$_GET["id"];
if ($ID && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
	permcontrol("serdelete");
	$qcont = $ac->prepare("SELECT * FROM services WHERE id = ?");
	$qcont->execute(array($ID));
	$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
	if ($qkx) {
		$pdq = $ac->prepare("DELETE FROM services WHERE id = ?");
		$pdq->execute(array($ID));

		// header("Location: index.php?p=products&type=delete&code=0882md25&pid=$ID");
	}
}


?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Servis Listesi</h5>

		</div>
		<?php if (permtrue("seradd")) { ?>
			
			<a href="index.php?p=new-service">
				<button type="button" class="btn btn-primary float-right"> Yeni </button>
			</a>
			
		<?php } ?><br><br>
	</div>
	<table class="data-table stripe hover">
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
			$cq = $ac->prepare("SELECT * FROM services ORDER by id ASC");
			$cq->execute();
			$siraNo = 1;
			while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {



				$miq = $ac->prepare("SELECT * FROM mainservices WHERE id = ?");
				$miq->execute(array($as["id"]));
				$mms = $miq->fetch(PDO::FETCH_ASSOC);
			?>
				<tr>
					<td scope="row"><?php echo $siraNo; ?></td>
					<td><?php echo $as["Firma"]; ?></td>
					<td><?php echo $as["SubeAdresi"]; ?></td>
					<td><?php echo $as["Cihaz"]; ?></td>
					<td><?php echo $mms["Marka"]; ?></td>
					<td>
						<?php
						if (permtrue("seredit")) { ?><a href="index.php?p=edit-service&reg=true&md=update&pid=<?php echo $as["ID"]; ?>"><span class="badge badge-info">Düzenle</span></a>
						<?php } ?>
						&nbsp;&nbsp;

						<!-- <?php if (permtrue("serdelete")) { ?><a onClick="return confirm('<?php echo $as["Adi"]; ?> isimli ürün/hizmeti sistemden kaldırmak istediğinize emin misiniz?')" href="index.php?p=products&mode=delete&code=04md177&reg=true&md=active&pid=<?php echo $as["id"]; ?>"><span class="badge badge-danger">Sil</span></a><?php } ?></td> -->
						<a href="#" onClick="deleteRecord('<?php echo $as["Firma"]; ?>',<?php echo $as["id"]; ?>)"><span class="badge badge-danger">Sil</span></a>

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


<script>
function deleteRecord(msg,ID){
	var pLink="all-services";
	deleteRecord()
}

</script>




<script>
	function deleteRecord(msg, ID) {
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
					url: "index.php?p=" + +  "&mode=delete&code=04md177&reg=true&md=active&id=" + ID, // PHP script for deletion
					data: {
						id: ID
					},
					success: function(response) {
						// Handle success response (optional)
						Swal.fire({
							title: "Başarılı!",
							text: "Servis Başarılı ile silindi!",
							icon: "success"
						}).then(() => {
							// Redirect to products page
							window.location.href = "index.php?p=all-services";
						});
					},
					error: function(xhr, status, error) {
						// Handle error if deletion fails (optional)
						console.error(xhr.responseText);
						Swal.fire({
							title: "Hata!",
							text: "Bir şeyler ters gitti!",
							icon: "error"
						});
					}
				});
			}
		});
	}
</script>