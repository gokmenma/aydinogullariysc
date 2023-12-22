
<?php
permcontrol("exview");
	$xid = @$_GET["xid"];
	if($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){
		permcontrol("exdelete");
		$qcont = $ac->prepare("SELECT * FROM inexps WHERE id = ?");
		$qcont->execute(array($xid));
		$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
		if($qkx){
			

			$aac = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
			$aac->execute(array($qkx["pay_method"]));
			$xax = $aac->fetch(PDO::FETCH_ASSOC);

			$old = $xax["total"];
			if($qkx["type"] == "in"){
				$new = $old-$qkx["pay"];
			}else{
				$new = $old+$qkx["pay"];
			}

			$pdaqs = $ac->prepare("UPDATE pay_methods SET total = ? WHERE id = ?");
			$pdaqs->execute(array($new,$qkx["pay_method"]));

			$pdq = $ac->prepare("DELETE FROM inexps WHERE id = ?");
			$pdq->execute(array($xid));


			header("Location: index.php?p=income-expense-list&type=delete&code=0882md25&pid=$pids");

		}

	}
	if(@$_GET["mode"] == "delete-all" && @$_GET["code"] == 38){
		permcontrol("exdelete");
		$pdaq = $ac->prepare("DELETE FROM inexps ");
			$pdaq->execute(array());
		$pdaqs = $ac->prepare("UPDATE pay_methods SET total = ? AND last_action = ?");
			$pdaqs->execute(array(0,date("d-m-Y - H:i:s")));


	}

?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if(@$_GET["st"] == "newsuccess" ){



?>
<div class="alert alert-success" role="alert">
								İşlem başarılı!
							</div>
	<?php
}
	if(@$_GET["type"] == "delete" AND @$_GET["cid"]){
	?>

	<?php
}
	?>
	

	
	<div class="clearfix mb-20">
						<div class="pull-left">
							<h5 class="text-blue">Gelir - Gider Listesi</h5>
							<p>
								Verilerin açıklamasını görüntülemek için mouse imlecini ilgili tablo satırı üzerinde bekletebilirsiniz.
							</p>
						</div>
					</div>
<table class="data-table-export stripe hover">
  <thead>
    <tr>
      <th scope="col">#Sıra</th>
      <th>Gelir/Gider</th>
      <th>Kategori</th>
      <th>Başlık</th>
      <th>Tarih</th>
      <th>Ödeme Yöntemi</th>
      
      <th>Ödeme</th>
      <th>İşlem</th>
		<?php

			$inq = $ac->prepare("SELECT * FROM inexps WHERE type = ?");
			$inq->execute(array("in"));
			$toti = 0;
			while($inx = $inq->fetch(PDO::FETCH_ASSOC)){
				$toti = $toti+$inx["pay"];
			}

			$exq = $ac->prepare("SELECT * FROM inexps WHERE type = ?");
			$exq->execute(array("exp"));
			$tote = 0;
			while($exx = $exq->fetch(PDO::FETCH_ASSOC)){
				$tote = $tote+$exx["pay"];
			}



		?>
    </tr>
  </thead>
  <tbody>
  	<?php if(permtrue("exdelete")){?>
    <a onClick="return confirm('Tüm gelir/gider raporlarını silmek istediğinize emin misiniz?')" href="index.php?p=income-expense-list&mode=delete-all&code=38&cc=087s3"><button style="float:right;" type="button" class="btn btn-success">Tabloyu Sıfırla</button></a><?php } ?> <br><br>
    	<?php
    		$cq = $ac->prepare("SELECT * FROM inexps ORDER by id DESC");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    		$asd = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
    		$asd->execute(array($as["pay_method"]));
    		$asdv = $asd->fetch(PDO::FETCH_ASSOC);

    		if($asdv["currency"] == "tl"){
				$prx = "₺";
			}elseif($asdv["currency"] == "dollar"){
				$prx = "$";
			}elseif($asdv["currency"] == "euro"){
				$prx = "€";
			}else{
				$prx = "₺";
			}

    	?>
    	<tr title="<?php echo $as["descs"]; ?>">
      <td scope="row"><?php echo $kx;?></td>
      <th><?php echo $as["type"] == "in" ? "<font color='green'>GELİR(+)</font> " : "<font color='red'>GİDER(-)</font>";?></th">
      <td><?php echo $as["cat"];?></td>
      <td><?php echo $as["title"];?></td>
      <td><?php echo $as["sdate"];?></td>
      <?php
      	$shw = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
		$shw->execute(array($as["pay_method"]));
		$sh = $shw->fetch(PDO::FETCH_ASSOC);

      ?>
       <td><?php echo $sh["title"];?></td>

      <th><?php echo "$prx".$as["pay"];?></th>
      <th>

      	&nbsp;&nbsp; <?php if(permtrue("exdelete")){?> <a onClick="return confirm('Söz konusu gelir/gider raporunu silmek istediğinize emin misiniz?')" href="index.php?p=income-expense-list&mode=delete&code=04md177&md=active&xid=<?php echo $as["id"];?>"><span class="badge badge-danger">Sil</span></a><?php } ?></th>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>