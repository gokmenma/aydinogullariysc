<?php


if (@$_GET["uid"] && @$_GET["mode"] == "delete" && @$_GET["code"] == "3222891") {
	permcontrol("udelete");
	if (@$_GET["uid"] == sesset("id") || $_GET["uid"] == 1) {
		header("Location: index.php?p=all-users&st=cannotdeleted");
		exit;
	}
	$cdid = $_GET["uid"];
	$contq = $ac->prepare("SELECT * FROM users WHERE id = ?");
	$contq->execute(array($cdid));
	if ($contq->fetch(PDO::FETCH_ASSOC)) {
		$deletq = $ac->prepare("DELETE FROM users WHERE id = ?");
		$deletq->execute(array($cdid));
		if ($deletq) {
			header("Location: index.php?p=all-users&uid=$cdid&type=delete");
		}
	}
}
if (@$_GET["uid"] && @$_GET["mode"] == "updatest") {
	permcontrol("uedit");
	if (@$_GET["uid"] == sesset("id") || $_GET["uid"] == 1) {
		header("Location: index.php?p=all-users&st=cannotupdate");
		exit;
	}
	$cdid = $_GET["uid"];
	$gunc = @$_GET["stu"];
	if ($gunc != 1 && $gunc != 0) {
		header("Location:index.php");
		exit;
	}

	$contq = $ac->prepare("UPDATE users SET statu = ? WHERE id = ?");
	$contq->execute(array($gunc, $cdid));

	header("Location:index.php?p=all-users");
}

?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if (@$_GET["st"] == "newsuccess") {
		showAlert('success', 'Kullanıcı başarıyla eklendi!.');
	}
	if (@$_GET["st"] == "cannotdeleted") {



	?>
		<div class="alert alert-error" role="alert">
			Kendi üyeliğinizi silemezsiniz.
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
			<h5 class="text-blue">Ekip Listesi</h5>
			<p class="font-14"> </p>
		</div>
	</div>
	<table class="data-table stripe hover">
		<thead>
			<tr>
				<th scope="col">#</th>
				<th>Pozisyon</th>
				<th>Kullanıcı Adı</th>
				<th>E-Posta Adresi</th>
				<th>GSM</th>
				<th>İşlem</th>

			</tr>
		</thead>
		<tbody>

			<?php
			$cq = $ac->prepare("SELECT * FROM users ORDER by id DESC");
			$cq->execute([]);
			while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {

				$perqx = $ac->prepare("SELECT * FROM perms WHERE id = ?");
				$perqx->execute(array($as["permission"]));
				$ppa = $perqx->fetch(PDO::FETCH_ASSOC);

			?>
				<tr>
					<td scope="row"><?php echo $as["id"]; ?></td>
					<td><?php echo $ppa["p_title"]; ?></td>
					<td><?php echo $as["username"]; ?></td>
					<td><?php echo $as["email"]; ?></td>
					<td><?php echo $as["gsm"]; ?></td>
					<td>
						<?php if (permtrue("uedit")) { ?>
							<a href="index.php?p=edit-user&uid=<?php echo $as["id"]; ?>"><span class="badge badge-primary">Düzenle</span></a>
							<?php
							if ($as["statu"] == 1 && $as["permission"] != 1) {
							?>
								<a href="index.php?p=all-users&mode=updatest&code=3222891&reg=true&md=active&uid=<?php echo $as["id"]; ?>&stu=0"><span class="badge badge-danger">Pasifleştir</span></a>
							<?php
							} elseif ($as["statu"] == 0 && $as["permission"] != 1) {
							?>
								<a href="index.php?p=all-users&mode=updatest&code=3222891&reg=true&md=active&uid=<?php echo $as["id"]; ?>&stu=1"><span class="badge badge-success">Aktif Yap</span></a>
							<?php
							}
						}
						if (permtrue("udelete") and $as["id"] != sesset("id") and $as["id"] != 1) { ?>
							<a onClick="return confirm('Devam ettiğiniz takdirde, kullanıcıya ait tüm bilgiler ve müşterinin adına düzenlenmiş olan teklif & projeler tamamen silinecektir. Devam etmek istiyor musunuz?')" href="index.php?p=all-users&mode=delete&code=3222891&reg=true&md=active&uid=<?php echo $as["id"]; ?>"><span class="badge badge-danger">Sil</span></a><?php } ?>
					</td>

				</tr>
			<?php } ?>
		</tbody>
	</table>
</div>