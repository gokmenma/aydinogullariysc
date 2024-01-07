<?php

if (@$_GET["type"] == "delete") {
	permcontrol("odelete");
	$ois = $_GET["oid"];

	$ofs = $ac->prepare("SELECT * FROM offers WHERE id = ?");
	$ofs->execute(array($ois));
	$of = $ofs->fetch(PDO::FETCH_ASSOC);

	$fileq = $ac->prepare("SELECT * FROM files WHERE oid = ?");
	$fileq->execute(array($ois));
	while ($ff = $fileq->fetch(PDO::FETCH_ASSOC)) {
		unlink("projects/offers/" . $ff["filename"]);
	}
	$delets = $ac->prepare("DELETE FROM files WHERE oid = ?");
	$delets->execute(array($ois));

	if ($of["statu"] == 3 || $of["statu"] == 5) {

		$deleteproj = $ac->prepare("DELETE FROM projects WHERE poid = ?");
		$deleteproj->execute(array($of["id"]));
	}
	$deleteone = $ac->prepare("DELETE FROM offers WHERE id = ?");
	$deleteone->execute(array($ois));

	$deleteonet = $ac->prepare("DELETE FROM offermatters WHERE oid = ?");
	$deleteonet->execute(array($ois));
} else {
	//header("Location: index.php?p=errormd&errorcode=MD9999");
}
///




if (@$_GET["st"] == "newsuccess" and @$_GET["code"] == "acmd006" and @$_GET["oisid"]) {



?>
	<div class="alert alert-success" role="alert">
		Teklif başarıyla oluşturuldu. Şu anda bekleme durumunda, aşağıdaki listeden teklifinizi görüntüleyerek durumunu değiştirebilir, fiyatlarını güncelleyebilir, ve teklifi inceleyebilirsiniz.
	</div>
<?php
} else if (@$_GET["type"] == "delete" and $ois) {
?>
	<div class="alert alert-success" role="alert">
		<?php echo "#" . $ois; ?> numaralı teklif başarıyla silindi.
	</div>
<?php
} else if (@$_GET["st"] == "newerror") {
?>
	<div class="alert alert-success" role="alert">
		HATA OLUŞTU
	</div>
<?php
}
?>


<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Teklifleri Görüntüle</h5>
			<p class="font-14">Önceden oluşturulmuş teklifleri buradan görüntüleyebilirsiniz. </p>
		</div>
	</div>

	<table class="table stripe hover select-row data-table-export nowrap ">
		<thead>
			<tr>

				<th>Hazırlandığı Tarih</th>

				<th>Toplam Tutar</th>
				<th>Müşteri</th>
				<th>Durum</th>
				<th>#No</th>
				<th class="datatable-nosort">İşlem</th>

			</tr>
		</thead>
		<tbody>
			<?php
			$ofqu = $ac->prepare("SELECT * FROM offers ORDER BY regdate DESC");
			$ofqu->execute();
			while ($of = $ofqu->fetch(PDO::FETCH_ASSOC)) {
				$findc = $ac->prepare("SELECT * FROM customers WHERE id = ?");
				$findc->execute(array($of["cid"]));
				$ffc = $findc->fetch(PDO::FETCH_ASSOC);

				$stats = $ac->prepare("SELECT * FROM ofstats WHERE sid = ?");
				$stats->execute(array($of["statu"]));
				$st = $stats->fetch(PDO::FETCH_ASSOC);

				$usqs = $ac->prepare("SELECT * FROM users WHERE id = ?");
				$usqs->execute(array($of["creativer"]));
				$usp = $usqs->fetch(PDO::FETCH_ASSOC);

				if ($of["currency"] == "tl") {
					$prx = "₺";
				} elseif ($of["currency"] == "dollar") {
					$prx = "$";
				} elseif ($of["currency"] == "euro") {
					$prx = "€";
				} else {
					$prx = "₺";
				}
			?>
				<tr>

					<td class="table-plus "><?php echo $of["reg_date"]; ?></td>
					<td><?php echo $of["total_price"] . " $prx + KDV"; ?></td>
					<td><a href="index.php?p=customer&cid=<?php echo $ffc["id"]; ?>"><?php echo $ffc["name"]; ?></a></td>
					<td style="color:<?php echo $st["color"]; ?>" title="<?php echo $st["description"]; ?>"><?php echo $st["title"]; ?>&nbsp;&nbsp;<i class="<?php echo $st["icon"]; ?>"></i></td>
					<td>#<?php echo $of["id"]; ?></td>

					<td>
						<?php if (permtrue("oview")) { ?>
							<a href="index.php?p=offer&oid=<?php echo $of["id"]; ?>"><span class="badge badge-success">Görüntüle</span></a>
						<?php }
						if (permtrue("oedit")) { ?>
							<a href="index.php?p=edit-offer&type=fileupload&insert=update&ccs=083y3&oid=<?php echo $of["id"]; ?>&stx=updreg"><span class="badge badge-info">Düzenle</span></a><br>

							
							<a href="index.php?p=edit-offer&type=fileupload&oid=<?php echo $of["id"]; ?>"><span class="badge badge-primary">Dökümanlar</span></a>
						<?php }
						if (permtrue("odelete")) {

						?>

							<a onClick="return confirm('Teklifi tamamen kaldırmak istediğinize emin misiniz?')" href="index.php?p=all-offers&type=delete&oid=<?php echo $of["id"]; ?>&codes=mdac4343"><span class="badge badge-danger">Sil</span></a><?php } ?>
					</td>



				</tr>
			<?php
			}
			?>

		</tbody>
	</table>
</div>