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
		header("Location: index.php?p=for-once-payments&st=newsuccess");
	}

	if(@$_GET["mode"] == "delete" && @$_GET["tid"]){
		permcontrol("paydelete");
		$sil = $ac->prepare("DELETE FROM payments WHERE id = ?");
		$sil->execute(array(@$_GET["tid"]));
		if($sil){
			header("Location:index.php?p=for-once-payments&type=delete");
		}
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
							<h5 class="text-blue">Tek Seferlik Ödeme Listesi</h5>
							
						</div>
					</div>
<table class="data-table stripe hover">
  <thead>
    <tr>
      <th scope="col">#Sıra</th>
      <th scope="col">#SatışNo</th>
      <th>Müşteri Adı </th>
      <th>Son Ödeme Tarihi</th>
      <th>Son Tarih</th>
      <th>İşlem</th>

    </tr>
  </thead>
  <tbody>
  	 <br><br>
    	<?php
    		$cq = $ac->prepare("SELECT * FROM payments WHERE type = ? ORDER BY id ASC ");
    		$cq->execute(array("tekseferlik"));
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    			$miq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
    			$miq->execute(array($as["cid"]));
    			$mms = $miq->fetch(PDO::FETCH_ASSOC);
    	?>
    	<tr title="">

      <td scope="row"><?php echo $kx;?> </td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $as["saleid"];?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $mms["name"];?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $as["lastdate"];?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo dtf(TODAY,$as["lastdate"]);?> gün kaldı<?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td>&nbsp;&nbsp; 
		<?php
			if($as["okey"] == 0){
				?>
				<a href="index.php?p=new-payment&tid=<?php echo $as["id"];?>&tt=1"><span class="badge badge-success">ÖDEME YAPILDI İŞARETLE</span></a>
				<?php
			}else{
				?>
				<a href="index.php?p=info-payment&tid=<?php echo $as["id"];?>&tt=1"><span class="badge badge-success">ÖDEME İNCELE</span></a>


				<?php
			}
		?>
     <a onClick="return confirm('Ödemeyi silmek istediğinize emin misiniz?')" href="index.php?p=for-once-payments&mode=delete&tid=<?php echo $as["id"];?>&tt=1"><span class="badge badge-danger">ÖDEMEYİ SİL</span></a> </td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>