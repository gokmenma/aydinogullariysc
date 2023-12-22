<?php

permcontrol("mistake");
	$nid = @$_GET["nid"];

	

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
      <th>Oluşturan</th>
      <th>Baş. Tarihi</th>
      <th>Son Tarih</th>
      <th>İşlem</th>

    </tr>
  </thead>
  <tbody>

    	<?php
    		$cq = $ac->prepare("SELECT * FROM missions WHERE authors = ? ORDER by id DESC ");
    		$cq->execute(array(sesset("id")));
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    			$miq = $ac->prepare("SELECT * FROM users WHERE id = ?");
    			$miq->execute(array($as["creativer"]));
    			$mms = $miq->fetch(PDO::FETCH_ASSOC);
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
      <td><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo uset($as["creativer"],"username");?></td>
      <td><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["regdate"];?></td>
      <td><?php echo $as["statu"] == 1 ? "<s>" : ""; ?><?php echo $as["lastdate"];?></td>
      
      <td>&nbsp;&nbsp;  
      	<?php?>
      		<a href="index.php?p=view-mission&mid=<?php echo $as["id"];?>"><span class="badge badge-info">Görüntüle</span></a>
      	 <?php if($as["statu"] == 0){ ?>
      		<a onClick="return confirm('<?php echo $as["title"]; ?> başlıklı görevi yapıldı işaretlemek istediğinize emin misiniz? Bu işlem geri alınamaz.')"href="index.php?p=view-mission&mode=update&statu=1&code=04md177&reg=true&md=active&mid=<?php echo $as["id"];?>"><span class="badge badge-success">Yapıldı işaretle</span></a>
      	<?php } ?>
		
      </td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>