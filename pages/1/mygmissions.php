 <?php

permcontrol("misadd");
	$nid = @$_GET["nid"];

if(@$_GET["mid"] && @$_GET["types"] == "delete"){

  $six = $ac->prepare("UPDATE missions SET deleted = ? WHERE creativer = ? AND id = ? ");
  $six->execute(array("yes",sesset("id"),$_GET["mid"]));
  header("Location: index.php?p=mygmissions&delete=true");
}


?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if(@$_GET["st"] == "newsuccess" ){



?>
<div class="alert alert-success" role="alert">
								Başarılı!
							</div>
	<?php
}
	if(@$_GET["update"] == "true"){
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
<table class="data-table stripe hover">
  <thead>
    <tr>
      <th scope="col" width="10">#Sıra</th>
      <th>Aciliyet</th>
      <th>Başlık</th>
      <th>Kalan Gün</th>
      <th>Görevi Yapacak Kişi</th>
      <th>Son Tarih</th>
      <th>İşlem</th>

    </tr>
  </thead>
  <tbody>

    	<?php
    		$cq = $ac->prepare("SELECT * FROM missions WHERE creativer = ? AND deleted != ? ORDER by id DESC ");
    		$cq->execute(array(sesset("id"),"yes"));
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    			$miq = $ac->prepare("SELECT * FROM users WHERE id = ?");
    			$miq->execute(array($as["creativer"]));
    			$mms = $miq->fetch(PDO::FETCH_ASSOC);

    			$miqa = $ac->prepare("SELECT * FROM users WHERE id = ?");
    			$miqa->execute(array($as["authors"]));
    			$at = $miqa->fetch(PDO::FETCH_ASSOC);

    		$frk = dtf(TODAY,$as["lastdate"]);
    	?>
    	<tr title="">

      <td scope="row"><?php echo $kx;?> </td>
      <?php if($as["urgency"] == "Yüksek"){ ?>
      <td style="font-weight:bold;color:red;"><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["urgency"];?></td>

    <?php }elseif($as["urgency"] == "Orta"){ ?>
      <td style="font-weight:bold;color:blue;"><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["urgency"];?></td>

    <?php }elseif($as["urgency"] == "Düşük"){ ?>
      <td style="font-weight:bold;color:green;"><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["urgency"];?></td>
    <?php } ?>


      <td><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["title"]; ?></td>
      <?php

  
     
      ?>
      <td style=""><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $frk >= 0 ? $frk." Gün" : $frk-(2*$frk)." Gün Önce";?></td>
      <td><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $at["username"];?></td>
      <td <?php echo $frk <= 1 && $as["statu"] == 0 ? "style='color:red;'" : ""; ?>><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["lastdate"];?></td>
      
      <td>&nbsp;&nbsp;  
      	<?php?>
      		<a href="index.php?p=view-mission&mid=<?php echo $as["id"];?>"><span class="badge badge-info">Görüntüle</span></a>
          <a onClick="return confirm('Silmek istediginize emin misiniz?')" href="index.php?p=mygmissions&mid=<?php echo $as["id"];?>&types=delete"><span class="badge badge-danger">Sil</span></a>

      
		
      </td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>