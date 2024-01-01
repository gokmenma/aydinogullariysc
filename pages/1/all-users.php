<?php

if (@$_GET["id"] && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
	permcontrol("udelete");
	
	if (@$_GET["id"] == sesset("id") || $_GET["id"] == 1) {
		header("Location: index.php?p=all-users&st=cannotdeleted");
		?><script> showMessage("Kendi üyeliğinizi silemezsiniz","alert");</script><?php
		exit;
	}
	$cdid = $_GET["id"];
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
if (@$_GET["id"] && @$_GET["mode"] == "updatest") {
	permcontrol("uedit");
	if (@$_GET["id"] == sesset("id") || $_GET["id"] == 1) {
		header("Location: index.php?p=all-users&st=cannotupdate");
		exit;
	}
	$cdid = $_GET["id"];
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
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
	<?php
	
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
		<?php if (permtrue("uadd")) { ?>
			<a href="index.php?p=new-user"><button type="button" class="btn btn-success float-right"> Yeni </button></a>
		<?php } ?><br><br>
	</div>
	<table class="data-table stripe hover">
		<thead>
			<tr>
				<th scope="col">Sıra No</th>
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
			$sirano=1;
			while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {

				$perqx = $ac->prepare("SELECT * FROM perms WHERE id = ?");
				$perqx->execute(array($as["permission"]));
				$ppa = $perqx->fetch(PDO::FETCH_ASSOC);
				

			?>
				<tr>
					<td scope="row"><?php echo $sirano; ?></td>
					<td><?php echo $ppa["p_title"]; ?></td>
					<td><?php echo $as["username"]; ?></td>
					<td><?php echo $as["email"]; ?></td>
					<td><?php echo $as["gsm"]; ?></td>
					<td>
						<?php if (permtrue("uedit")) { ?>
							<a href="index.php?p=edit-user&id=<?php echo $as["id"]; ?>"><span class="badge badge-primary">Düzenle</span></a>
							<?php
							if ($as["statu"] == 1 && $as["permission"] != 1) {
							?>
								<a href="index.php?p=all-users&mode=updatest&code=3222891&reg=true&md=active&id=<?php echo $as["id"]; ?>&stu=0"><span class="badge badge-danger">Pasifleştir</span></a>
							<?php
							} elseif ($as["statu"] == 0 && $as["permission"] != 1) {
							?>
								<a href="index.php?p=all-users&mode=updatest&code=3222891&reg=true&md=active&id=<?php echo $as["id"]; ?>&stu=1"><span class="badge badge-success">Aktif Yap</span></a>
							<?php
							}
						}
						if (permtrue("udelete") and $as["id"] != sesset("id") and $as["id"] != 1) { ?>
							<a  onClick="deleteRecord('Devam ettiğiniz takdirde,kullanıcıya ait tüm bilgiler ve müşterinin adına düzenlenmiş olan teklif & projeler tamamen silinecektir. Devam etmek istiyor musunuz?','<?php echo $as['id']; ?>','all-users')" >
										<span class="btn badge badge-danger">Sil</span></a>
										<?php } ?>
							
					</td>

				</tr>
				
			<?php 
				$sirano += 1;
				} ?>
		</tbody>
	</table>
</div>