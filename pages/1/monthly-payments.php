<?php
permcontrol("payview");
	$pids = @$_GET["tid"];

	if($pids && @$_GET["md"] == "update" && @$_GET["tt"]){
		permcontrol("payadd");
		$tts = $_GET["tt"];

		if($tts == 1){
			$gg = 1;
		}else{
			$gg = 0;
		}

		$gunc = $ac->prepare("UPDATE payments SET okey = ? WHERE id = ?");
		$gunc->execute(array($gg,$pids));
		header("Location: index.php?p=monthly-payments&st=newsuccess");
	}

	if(@$_GET["mode"] == "delete" && @$_GET["tid"]){
		permcontrol("paydelete");
		$sil = $ac->prepare("DELETE FROM payments WHERE id = ?");
		$sil->execute(array(@$_GET["tid"]));
		if($sil){
			header("Location:index.php?p=monthly-payments&type=delete");
		}
	}

?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if(@$_GET["st"] == "newsuccess" ){



?>
<div class="alert alert-success" role="alert">
							
							</div>
	<?php
}
	if(@$_GET["type"] == "delete"){
	?>
		<div class="alert alert-success" role="alert">
								Silme işlemi başarılı!
							</div>
	<?php
}
	?>
	<div class="clearfix mb-20">
						<div class="pull-left">
							<h5 class="text-blue">Aylık Ödeme Listesi</h5>
							
						</div>
					</div>
<table class="data-table stripe hover">
  <thead>
    <tr>
      <th scope="col">#Sıra</th>
      <th scope="col">Ürün/Hizmet</th>
      <th>Müşteri Adı </th>
      <th>Son Ödeme Tarihi</th>
      <th>Kalan Gün</th>
      <th>İşlem</th>

    </tr>
  </thead>
  <tbody>
  	 <br><br>
    	<?php
    		$cq = $ac->prepare("SELECT * FROM payments WHERE type = ? ORDER BY id ASC ");
    		$cq->execute(array("aylik"));
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    			$miq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
    			$miq->execute(array($as["cid"]));
    			$mms = $miq->fetch(PDO::FETCH_ASSOC);

    			$salcek = $ac->prepare("SELECT * FROM sales WHERE id = ? ");
    			$salcek->execute(array($as["saleid"]));
    			$salbil = $salcek->fetch(PDO::FETCH_ASSOC);

    			$sercek = $ac->prepare("SELECT * FROM services WHERE id = ?");
    			$sercek->execute(array($salbil["sid"]));
    			$serbil = $sercek->fetch(PDO::FETCH_ASSOC);
    	?>
    	<tr title="">

      <td scope="row"><?php echo $kx;?> </td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $serbil["stitle"];?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $mms["name"];?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $as["lastdate"];?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo dtf(TODAY,$as["lastdate"]) <= 1 ? "<font color='red'>".dtf(TODAY,$as["lastdate"])."</font>" : dtf(TODAY,$as["lastdate"]);?> <?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td>&nbsp;&nbsp; 
		<?php
			if($as["okey"] == 0){
				if(permtrue("payadd")){
				?>
				<a href="index.php?p=new-payment&tid=<?php echo $as["id"];?>&tt=1"><span class="badge badge-success">ÖDEME YAPILDI İŞARETLE</span></a>
				<?php
			}
			}else{
				if(permtrue("payview")){
				?>
				<a href="index.php?p=info-payment&tid=<?php echo $as["id"];?>&tt=1"><span class="badge badge-success">ÖDEME İNCELE</span></a>
			<?php } } ?>

				<?php
			if(permtrue("paydelete")){
			
		?>
     <a onClick="return confirm('Ödemeyi silmek istediğinize emin misiniz?')" href="index.php?p=monthly-payments&mode=delete&tid=<?php echo $as["id"];?>&tt=1"><span class="badge badge-danger">ÖDEMEYİ SİL</span></a> <?php } ?> </td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>