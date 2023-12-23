<?php

if (@$_GET["type"] == "delete") {
	permcontrol("pdelete");
	$ois = @$_GET["pid"];

	$ofs = $ac->prepare("SELECT * FROM projects WHERE id = ?");
	$ofs->execute(array($ois));
	$of = $ofs->fetch(PDO::FETCH_ASSOC);
	$fsf = $of["filename"];

	$pqah = $ac->prepare("UPDATE offers SET statu = ? WHERE id = ?");
	$pqah->execute(array(2, $of["poid"]));
	$ppx = $pqah->fetch(PDO::FETCH_ASSOC);


	$deleteone = $ac->prepare("DELETE FROM projects WHERE id = ?");
	$deleteone->execute(array($ois));



	if ($fsf) {
		if (file_exists(PANEL_URL . "/projects/offers/$fsf")) {
			header("Location: projects/$fsf");
		} else {
			header("Location: index.php?p=all-projects&type=delete&pid=$ois&codes=mdac4343");
		}
		unlink("projects/$fsf");
	}
} else {
	//header("Location: index.php?p=errormd&errorcode=MD9999");
}
///



?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if (@$_GET["st"] == "newsuccess") {



	?>
		<div class="alert alert-success" role="alert">
			proje başarıyla oluşturuldu. Aşağıdaki listeden projeyi görüntüleyebilirsiniz.
		</div>
	<?php
	} else if (@$_GET["type"] == "delete" and $ois) {
	?>
		<div class="alert alert-success" role="alert">
			<?php echo "#" . $ois; ?> numaralı proje başarıyla silindi.
		</div>
	<?php
	}
	?>

	<!-- Simple Datatable start -->

	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Projeleri Görüntüle</h5>
			<p class="font-14">Oluşturulmuş projeleri aşağıda görüntüleyebilirsiniz. Sütun isimlerine tıklayarak, sıralama ölçütünüzü kendinize göre belirleyebilirsiniz. </p>
		</div>
	</div>
	<div class="pd-ltr-10 xs-pd-10-10">
		<table class="data-table stripe hover">
			<!-- <table class="stripe hover select-row data-table-export nowrap "> -->
			<thead>
				<tr>

					<th>#No</th>

					<th>Teklif No</th>
					<th>Müşteri</th>
					<th>Proje Başlığı</th>
					<th>Oluşturulma Tarihi</th>
					<th class="datatable-nosort">İşlem</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$ofqu = $ac->prepare("SELECT * FROM projects ORDER BY id DESC");
				$ofqu->execute();
				while ($of = $ofqu->fetch(PDO::FETCH_ASSOC)) {
					$findc = $ac->prepare("SELECT * FROM offers WHERE id = ?");
					$findc->execute(array($of["poid"]));
					$ffc = $findc->fetch(PDO::FETCH_ASSOC);

					$stats = $ac->prepare("SELECT * FROM customers WHERE id = ?");
					$stats->execute(array($of["pcid"]));
					$st = $stats->fetch(PDO::FETCH_ASSOC);

					$usqs = $ac->prepare("SELECT * FROM users WHERE id = ?");
					$usqs->execute(array($of["pcreativer"]));
					$usp = $usqs->fetch(PDO::FETCH_ASSOC);


				?>
					<tr>

						<td class="table-plus "><?php echo $of["id"]; ?></td>
						<td><a target="_blank" href="index.php?p=offer&oid=<?php echo $of["poid"]; ?>"><?php echo "#" . $of["poid"]; ?></a></td>
						<td><a href="index.php?p=customer&cid=<?php echo $st["id"]; ?>"><?php echo $st["name"]; ?></a></td>
						<td><?php echo $of["ptitle"]; ?></td>

						<td><?php echo $of["preg_date"]; ?></td>
						<td>
							<?php if (permtrue("pedit")) { ?><a href="index.php?p=edit-project&pid=<?php echo $of["id"]; ?>"><span class="badge badge-info">Düzenle</span></a>
								<a href="index.php?p=edit-project&type=fileupload&pid=<?php echo $of["id"]; ?>"><span class="badge badge-primary">Dökümanlar</span></a>

							<?php }
							if (permtrue("pdelete")) {
							?>
								&nbsp;&nbsp; <a onClick="return confirm('Projeyi tamamen kaldırmak istediğinize emin misiniz?')" href="index.php?p=all-projects&type=delete&pid=<?php echo $of["id"]; ?>&codes=mdac4343"><span class="badge badge-danger">Sil</span></a>
						</td>
					<?php } ?>
					</tr>
				<?php
				}
				?>

			</tbody>
		</table>
	</div>

</div>
<?php include('include/footer.php'); ?>