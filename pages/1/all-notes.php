<?php
$nid = @$_GET["nid"];
if ($nid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
	permcontrol("notedelete");
	$qcont = $ac->prepare("SELECT * FROM notes WHERE id = ?");
	$qcont->execute(array($nid));
	$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
	if ($qkx) {
		$pdq = $ac->prepare("DELETE FROM notes WHERE id = ?");
		$pdq->execute(array($nid));

		header("Location: index.php?p=all-notes&type=delete&code=0882md25&tid=$nid");
	}
}


?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if (@$_GET["st"] == "newsuccess") {



	?>
		<div class="alert alert-success" role="alert">
			Başarılı!
		</div>
	<?php
	}
	if (@$_GET["type"] == "delete" and @$_GET["tid"]) {
	?>
		<div class="alert alert-success" role="alert">
			Silme işlemi başarılı!
		</div>
	<?php
	}
	?>
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Tüm Notlar</h5>
		</div>

		<div class="float-right">

			<?php if (permtrue("noteadd")) { ?>
				<button type="button" class="btn btn-success">Yeni Oluştur</button><?php } ?>
		</div>


	</div>
	<table class="data-table stripe hover">
		<thead>
			<tr>
				<th scope="col">#Sıra</th>
				<th>Aciliyet</th>
				<th>Başlık</th>
				<th>Not Tipi</th>
				<th>Bitiş Tarihi</th>
				<th>İşlem</th>

			</tr>
		</thead>
		<tbody>
			<br><br>
			<?php
			$cq = $ac->prepare("SELECT * FROM notes ORDER by id DESC ");
			$cq->execute();
			$kx = 1;
			while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {

				$miq = $ac->prepare("SELECT * FROM users WHERE id = ?");
				$miq->execute(array($as["creativer"]));
				$mms = $miq->fetch(PDO::FETCH_ASSOC);
			?>
				<tr title="">

					<td scope="row"><?php echo $kx; ?> </td>
					<?php if ($as["urgency"] == "Yüksek") { ?>
						<td style="font-weight:bold;color:red;"><?php echo $as["urgency"]; ?></td>

					<?php } elseif ($as["urgency"] == "Orta") { ?>
						<td style="font-weight:bold;color:blue;"><?php echo $as["urgency"]; ?></td>

					<?php } elseif ($as["urgency"] == "Düşük") { ?>
						<td style="font-weight:bold;color:green;"><?php echo $as["urgency"]; ?></td>
					<?php } ?>


					<td><?php echo $as["title"]; ?></td>
					<?php

					$tyq = $ac->prepare("SELECT * FROM note_categories WHERE id = ?");
					$tyq->execute(array($as["category"]));
					$tt = $tyq->fetch(PDO::FETCH_ASSOC);

					?>
					<td><?php echo $tt["title"]; ?></td>
					<td><?php echo $as["lastdate"]; ?></td>
					<td>&nbsp;&nbsp;
						<?php if (permtrue("noteedit")) { ?>
							<a href="index.php?p=edit-note&nid=<?php echo $as["id"]; ?>"><span class="badge badge-info">Düzenle</span></a><?php } ?>
						<?php if (permtrue("notedelete")) { ?>
							<a onClick="return confirm('<?php echo $as["title"]; ?> başlıklı notu silmek istediğinize emin misiniz?')" href="index.php?p=all-notes&mode=delete&code=04md177&reg=true&md=active&nid=<?php echo $as["id"]; ?>"><span class="badge badge-danger">Sil</span></a><?php } ?>


					</td>

				</tr>
			<?php
				$kx = $kx + 1;
			} ?>
		</tbody>
	</table>
</div>