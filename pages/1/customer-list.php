<?php

if (@$_GET["cid"] && @$_GET["mode"] == "delete" && @$_GET["code"] == "3222891") {
	permcontrol("cdelete");
	$cdid = $_GET["cid"];
	$contq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
	$contq->execute(array($cdid));
	if ($contq->fetch(PDO::FETCH_ASSOC)) {
		$deletq = $ac->prepare("DELETE FROM customers WHERE id = ?");
		$deletq->execute(array($cdid));

		$deletqp = $ac->prepare("DELETE FROM projects WHERE pcid = ?");
		$deletqp->execute(array($cdid));

		$deletqo = $ac->prepare("DELETE FROM offers WHERE cid = ?");
		$deletqo->execute(array($cdid));


		if ($deletq) {
			header("Location: index.php?p=customer-list&cid=$cdid&type=delete");
		}
	}
}

?>

<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
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
			<?php echo "#" . $_GET["cid"]; ?> numaralı müşteri bilgileri başarıyla silindi.
		</div>
	<?php
	}
	?>

	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Müşteri Listesi</h5>
			<p class="font-14"> </p>
		</div>
		<?php if (permtrue("cadd")) { ?>
			<a href="index.php?p=new-customer&cc=0014s1"><button type="button" class="btn btn-success float-right">Yeni Müşteri</button></a><?php } ?>
	</div>
	<table class="data-table stripe hover">
		<thead>
			<tr>
				<th scope="col">#</th>
				<th>Ad-Soyad</th>
				<th>Grup</th>
				<th>Teklif/Proje Sayısı</th>
				<th>E-Posta Adresi</th>
				<th>GSM</th>
				<th class="datatable-nosort">İşlem</th>

			</tr>
		</thead>
		<tbody>

			<?php
			$cq = $ac->prepare("SELECT * FROM customers ORDER by id DESC");
			$cq->execute();
			while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {
				$tqm = $ac->prepare("SELECT * FROM offers WHERE cid = ?");
				$tqm->execute(array($as["id"]));
				$tsay = $tqm->rowCount();

				$pqm = $ac->prepare("SELECT * FROM projects WHERE pcid = ?");
				$pqm->execute(array($as["id"]));
				$psay = $pqm->rowCount();

				$tsayi = $tsay > 0 ? $tsay : "0";
				$psayi = $psay > 0 ? $psay : "0";
				$tps = $tsayi . " / " . $psayi;

				$grcek = $ac->prepare("SELECT * FROM cgroups WHERE id = ?");
				$grcek->execute(array($as["grp"]));
				$gaar = $grcek->fetch(PDO::FETCH_ASSOC);
			?>
				<tr>
					<td scope="row"><?php echo $as["id"]; ?></td>
					<td><?php echo $as["name"]; ?></td>
					<td><?php echo $gaar["title"]; ?></td>
					<td><?php echo $tps; ?></td>
					<td><?php echo $as["email"]; ?></td>
					<td><?php echo $as["gsm"]; ?></td>
					<?php $ptr = permtrue("cedit"); ?>
					<td><?php echo $ptr ? "<a href='index.php?p=edit-customer&cid=" . $as["id"] . "'><span class='badge badge-primary'>Görüntüle/Düzenle</span></a>" : ""; ?>
						<?php if (permtrue("cdelete")) { ?><a onClick="return confirm('Devam ettiğiniz takdirde, müşteriye ait tüm bilgiler ve müşterinin adına düzenlenmiş olan teklif & projeler tamamen silinecektir. Devam etmek istiyor musunuz?')" href="index.php?p=customer-list&mode=delete&code=3222891&reg=true&md=active&cid=<?php echo $as["id"]; ?>"><span class="badge badge-danger">Sil</span></a><?php } ?></th>

				</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php include('include/footer.php'); ?>