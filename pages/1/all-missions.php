 <?php

	permcontrol("allmisview");
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
 			<h5 class="text-blue">Sistemde Oluşturulmuş Tüm Görevler</h5>

 		</div>
 	</div>
 	<table class="data-table stripe hover">
 		<thead>
 			<tr>
 				<th scope="col" width="10">#Sıra</th>
 				<th>Aciliyet</th>
 				<th>Başlık</th>
 				<th>Oluşturan</th>
 				<th>Görevi Yapacak Kişi</th>
 				<th>Son Tarih</th>
 				<th>İşlem</th>

 			</tr>
 		</thead>
 		<tbody>

 			<?php
				$cq = $ac->prepare("SELECT * FROM missions WHERE deleted != ? ORDER by id DESC ");
				$cq->execute(array("yes"));
				$results = $cq->fetchAll(PDO::FETCH_ASSOC);
				$kx = 1;


					foreach ($results as $as) {
						$authors = $as['authors'];
						$userstring = "";
						$userArrays = explode("|", $authors);
						// print_r($userArrays);
						// echo "<br/>";


						// // Örneğin, belirli bir kullanıcının bu dizide olup olmadığını kontrol etmek istiyorsanız:
						foreach ($userArrays as $userid) {

							$miqa = $ac->prepare("SELECT * FROM users WHERE id = ?");
							$miqa->execute(array($userid));
							$at = $miqa->fetch(PDO::FETCH_ASSOC);

							$userstring .= $at["username"] . ",";
					
						}

					$frk = dtf(TODAY, $as["lastdate"]);
				?>
 				<tr title="">

 					<td scope="row"><?php echo $kx; ?> </td>
 					<?php if ($as["urgency"] == "Yüksek") { ?>
 						<td style="font-weight:bold;color:red;"><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["urgency"]; ?></td>

 					<?php } elseif ($as["urgency"] == "Orta") { ?>
 						<td style="font-weight:bold;color:blue;"><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["urgency"]; ?></td>

 					<?php } elseif ($as["urgency"] == "Düşük") { ?>
 						<td style="font-weight:bold;color:green;"><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["urgency"]; ?></td>
 					<?php } ?>


 					<td><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["title"]; ?></td>
 					<?php



						?>
 					<td style=""><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo uset($as["creativer"], "username"); ?></td>
 					<td><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo rtrim($userstring, ',') ?></td>
 					<td <?php echo $frk <= 1 && $as["statu"] == 0 ? "style='color:red;'" : ""; ?>><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["lastdate"]; ?></td>

 					<td>&nbsp;&nbsp;

 						<a href="index.php?p=view-mission&mid=<?php echo $as["id"]; ?>"><span class="badge badge-info">Görüntüle</span></a>


 					</td>

 				</tr>
 			<?php
					$kx = $kx + 1;
				} ?>
 		</tbody>
 	</table>
 </div>