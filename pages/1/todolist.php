<?php

	$pids = @$_GET["tid"];
	if($pids && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){
		permcontrol("tododelete");
		$qcont = $ac->prepare("SELECT * FROM todolist WHERE id = ?");
		$qcont->execute(array($pids));
		$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
		if($qkx){
			$pdq = $ac->prepare("DELETE FROM todolist WHERE id = ?");
			$pdq->execute(array($pids));

			header("Location: index.php?p=todolist&type=delete&code=0882md25&tid=$pids");

		}

	}
	if($pids && @$_GET["md"] == "update" && @$_GET["tt"]){
		permcontrol("todoedit");
		$tts = $_GET["tt"];

		if($tts == 1){
			$gg = 1;
		}elseif($tts == 2){
			$gg = 2;
		}else{
			$gg = 0;
		}

		$gunc = $ac->prepare("UPDATE todolist SET okey = ? WHERE id = ?");
		$gunc->execute(array($gg,$pids));
		header("Location: index.php?p=todolist&st=newsuccess");
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
	if(@$_GET["type"] == "delete" AND @$_GET["tid"]){
	?>
		<div class="alert alert-success" role="alert">
								Silme işlemi başarılı!
							</div>
	<?php
}
	?>
	<div class="clearfix mb-20">
						<div class="pull-left">
							<h5 class="text-blue">Yapılacaklar Listesi</h5>
							
						</div>
					</div>
<table class="data-table stripe hover">
  <thead>
    <tr>
      <th scope="col">#Sıra</th>
      <th>Başlık</th>
      <th>Oluşturan</th>
      <th>Durum</th>
      <th>Son Tarih</th>
      <th width="250">İşlem</th>

    </tr>
  </thead>
  <tbody><?php if(permtrue("todoadd")){?>
    <a href="index.php?p=new-task&cc=087s3"><button style="float:right;" type="button" class="btn btn-success">Yeni Oluştur</button></a><?php } ?> <br><br>
    	<?php
    		$cq = $ac->prepare("SELECT * FROM todolist ORDER by id DESC ");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    			$miq = $ac->prepare("SELECT * FROM users WHERE id = ?");
    			$miq->execute(array($as["creativer"]));
    			$mms = $miq->fetch(PDO::FETCH_ASSOC);

    			if($as["okey"] == 0){
    				$durumx = '<font style="font-weight:bold; color:red">Yapılmadı</font>';
    			}elseif($as["okey"] == 1){
    				$durumx = '<font style="font-weight:bold; color:green">Yapıldı</font>';
    			}elseif($as["okey"] == 2){
    				$durumx = '<font style="font-weight:bold; color:blue">Ertelendi</font>';
    			}
    	?>
    	<tr title="<?php echo $as["description"];?>">

      <td scope="row"><?php echo $kx;?> </td>
      <td><?php echo $as["okey"] == 2 ? "<font style='font-weight:bold'>[Ertelendi]</font> " : "" ; ?> <?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $as["title"];?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?> </td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $mms["username"];?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $durumx;?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td><?php echo $as["okey"] == 1 ? "<s>" : ""; ?><?php echo $as["last_date"];?><?php echo $as["okey"] == 1 ? "</s>" : ""; ?></td>
      <td>&nbsp;&nbsp;<?php if(permtrue("todoedit")){ ?><a href="index.php?p=edit-task&reg=true&md=update&tid=<?php echo $as["id"];?>&tt=2"><span class="badge badge-info">Düzenle</span></a> <?php } 
      if(permtrue("tododelete")){?>
       <a onClick="return confirm('<?php echo $as["title"]; ?> başlıklı maddeyi silmek istediğinize emin misiniz?')"href="index.php?p=todolist&mode=delete&code=04md177&reg=true&md=active&tid=<?php echo $as["id"];?>"><span class="badge badge-danger">Sil</span></a> <?php } ?>
		<?php
			if($as["okey"] == 0 && permtrue("todoedit")){
				?>
					<a href="index.php?p=todolist&reg=true&md=update&tid=<?php echo $as["id"];?>&tt=1"><span class="badge badge-success">Yapıldı</span></a>
					<a href="index.php?p=todolist&reg=true&md=update&tid=<?php echo $as["id"];?>&tt=2"><span class="badge badge-primary">Ertele</span></a>
				<?php
			}elseif($as["okey"] == 1 && permtrue("todoedit")){
				?>
					<a href="index.php?p=todolist&reg=true&md=update&tid=<?php echo $as["id"];?>&tt=3"><span class="badge badge-danger">Yapılmadı</span></a>
				<?php
			}elseif($as["okey"] == 2 && permtrue("todoedit")){
				?>
					<a href="index.php?p=todolist&reg=true&md=update&tid=<?php echo $as["id"];?>&tt=1"><span class="badge badge-success">Yapıldı</span></a>
				<?php
			}
		?>
      </td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>