<?php

	$xid = @$_GET["xid"];
	if($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){
		permcontrol("pmdelete");
		$qcont = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
		$qcont->execute(array($xid));
		$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
		if($qkx){
			$indel = $ac->prepare("DELETE FROM inexps WHERE pay_method = ?");
			$indel->execute(array($xid));
			$pdq = $ac->prepare("DELETE FROM pay_methods WHERE id = ?");
			$pdq->execute(array($xid));

			header("Location: index.php?p=pay-methods-list&type=delete&code=0882md25&pid=$pids");

		}

	}
	

?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if(@$_GET["st"] == "newsuccess" ){



?>

	<?php
}
	if(@$_GET["type"] == "delete" AND @$_GET["cid"]){
	?>
		
	<?php
}
	?>
	<div class="clearfix mb-20">
						<div class="pull-left">
							<h5 class="text-blue">Ödeme Hesapları</h5>
							<p>
								Gelir-Gider takiplerinin yapılacağı hesapları düzenleyebilirsiniz.
							</p>
						</div>
					</div>
<table class="stripe hover select-row data-table-export nowrap">
  <thead>
    <tr>
      <th scope="col">#Sıra</th>
      <th>Başlık</th>
      <th>Bakiye</th>
      <th>Son İşlem</th>
      <th>İşlem</th>
		<?php

			$inq = $ac->prepare("SELECT * FROM pay_methods WHERE currency = ?");
			$inq->execute(array("euro"));
			$totie = 0;
			while($inx = $inq->fetch(PDO::FETCH_ASSOC)){
				$totie = $totie+$inx["total"];
			}


			$inqt = $ac->prepare("SELECT * FROM pay_methods WHERE currency = ?");
			$inqt->execute(array("tl"));
			$totit = 0;
			while($inxt = $inqt->fetch(PDO::FETCH_ASSOC)){
				$totit = $totit+$inxt["total"];
			}


			$inqd = $ac->prepare("SELECT * FROM pay_methods WHERE currency = ?");
			$inqd->execute(array("dollar"));
			$totid = 0;
			while($inxd = $inqd->fetch(PDO::FETCH_ASSOC)){
				$totid = $totid+$inxd["total"];
			}





		?>
    </tr>
  </thead>
  <?php if($totit && $totit != 0){ ?>
  <button class="btn btn-info">TL Hesap Bakiye Toplamı: <?=$totit;?> ₺</button> <br><br>
  <?php } ?>
  <?php if($totid && $totid != 0){ ?>
  <button class="btn btn-info">Dolar Hesap Bakiye Toplamı: <?=$totid;?> $</button><br><br>
  <?php } ?>

<?php if($totie && $totie != 0){ ?>
  <button class="btn btn-info">Euro Hesap Bakiye Toplamı: <?=$totie;?> €</button> <br><br>
  <?php } ?>
  <tbody>
  
    	<?php
    		$cq = $ac->prepare("SELECT * FROM pay_methods ");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){
    		if($as["currency"] == "tl"){
    			$pr = "₺";
    		}elseif($as["currency"] == "euro"){
    			$pr = "€";
    		}elseif($as["currency"] == "dollar"){
    			$pr = "$";
    		}else{
    			$pr = "";
    		}
    	?>
    	<tr>
      <th scope="row"><?php echo $kx;?></th>
      <th ><?php echo "[$pr]".$as["title"];?></th>
      <td style="font-weight:bold"><?php echo $as["total"]." ".$pr;?></td>
       <td style="font-weight:bold"><?php echo $as["last_action"];?></td>
      <td >
      	<?php if(permtrue("pmdelete")){ ?>
      	&nbsp;&nbsp; <a onClick="return confirm('Söz konusu ödeme hesabını silmeniz durumunda, hesaba ait tüm gelir/gider raporları da silinecektir. Onaylıyor musunuz?')" href="index.php?p=pay-methods-list&mode=delete&code=04md177&md=active&xid=<?php echo $as["id"];?>"><span class="badge badge-danger">Sil</span></a>
      	<?php } if(permtrue("pmview")){ ?><a href="index.php?p=edit-pay-method&mode=edit&pid=<?php echo $as["id"];?>"><span class="badge badge-info">Hesap Bilgileri</span></a><?php } ?></td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>