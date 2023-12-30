<?php

permcontrol("mistake");
$nid = @$_GET["nid"];



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
	if (@$_GET["update"] == "true") {
	?>
		<div class="alert alert-success" role="alert">
			Görev, yapıldı işaretlendi.
		</div>
	<?php
	}
	?>
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Size Atanan Tüm Görevler</h5>

		</div>
	</div>
	<table class="data-table table-bordered table-hover">
		<thead>
			<tr>
				<th scope="col" width="10">#Sıra</th>
				<th>Aciliyet</th>
				<th>Başlık</th>
				<th>Oluşturan</th>
				<th>Baş. Tarihi</th>
				<th>Son Tarih</th>
				<th>Atanan Kullanıcı</th>

				<th>İşlem</th>

			</tr>
		</thead>
		<tbody>

			<?php

			$mg = $ac->prepare("SELECT * FROM missions");
			$mg->execute();
			$result = $mg->fetchAll(); // Tüm sonuçları al
			$kx = 1;
			foreach ($result as $row) {
				$authors = $row['authors']; // Her satırdaki 'authors' sütunundaki veriyi al
				$usersArray = explode('|', $authors);
				
				// Örneğin, belirli bir kullanıcının bu dizide olup olmadığını kontrol etmek istiyorsanız:
				if (in_array(sesset("id"), $usersArray)) {
				// 	print_r($usersArray); 
				// echo "<br>";	
					// $cq = $ac->prepare("SELECT * FROM missions WHERE authors = ? ORDER by id DESC ");
					// $cq->execute(array(sesset("id")));

					// while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {

					$miq = $ac->prepare("SELECT * FROM users WHERE id = ?");
					$miq->execute(array(sesset("id")));
					$mms = $miq->fetch(PDO::FETCH_ASSOC);
			?>
					<tr title="">

						<td scope="row"><?php echo $kx; ?> </td>
						<?php if ($row["urgency"] == "Yüksek") { ?>
							<td style="font-weight:bold;color:red;"><?php echo $row["statu"] == 1 ? "<s>" : ""; ?><?php echo $row["urgency"]; ?></td>

						<?php } elseif ($row["urgency"] == "Orta") { ?>
							<td style="font-weight:bold;color:blue;"><?php echo $row["statu"] == 1 ? "<s>" : ""; ?><?php echo $row["urgency"]; ?></td>

						<?php } elseif ($row["urgency"] == "Düşük") { ?>
							<td style="font-weight:bold;color:green;"><?php echo $row["statu"] == 1 ? "<s>" : ""; ?><?php echo $row["urgency"]; ?></td>
						<?php } ?>


						<td><?php echo $row["statu"] == 1 ? "<s>" : ""; ?><?php echo $row["title"]; ?></td>
						<?php



						?>
						<td><?php echo $row["statu"] == 1 ? "<s>" : ""; ?><?php echo uset($row["creativer"], "username"); ?></td>
						<td><?php echo $row["statu"] == 1 ? "<s>" : ""; ?><?php echo $row["regdate"]; ?></td>
						<td><?php echo $row["statu"] == 1 ? "<s>" : ""; ?><?php echo $row["lastdate"]; ?></td>
						<td><?php echo $mms["name"] . " " . $mms["surname"] . " --" . sesset("id")?></td>

						<td>&nbsp;&nbsp;

							<a href="index.php?p=view-mission&mid=<?php echo $row["id"]; ?>"><span class="badge badge-info">Görüntüle</span></a>
							<?php if ($row["statu"] == 0) { ?>
								<a onClick="return confirm('<?php echo $row["title"]; ?> başlıklı görevi yapıldı işaretlemek istediğinize emin misiniz? Bu işlem geri alınamaz.')" href="index.php?p=view-mission&mode=update&statu=1&code=04md177&reg=true&md=active&mid=<?php echo $row["id"]; ?>"><span class="badge badge-success">Yapıldı işaretle</span></a>
							<?php } ?>

						</td>

					</tr>
			<?php
					$kx = $kx + 1;
				}
			} ?>
		</tbody>
	</table>
</div>