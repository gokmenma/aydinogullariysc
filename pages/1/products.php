<?php

	$pids = @$_GET["pid"];
	if($pids && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){
		permcontrol("serdelete");
		$qcont = $ac->prepare("SELECT * FROM services WHERE id = ?");
		$qcont->execute(array($pids));
		$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
		if($qkx){
			$pdq = $ac->prepare("DELETE FROM services WHERE id = ?");
			$pdq->execute(array($pids));

			header("Location: index.php?p=products&type=delete&code=0882md25&pid=$pids");

		}

	}
	

?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if(@$_GET["st"] == "newsuccess" ){



?>
<div class="alert alert-success" role="alert">
								Müşteri başarıyla sisteme eklendi. Aşağıdaki listeden  görüntüleyebilirsiniz.
							</div>
	<?php
}
	if(@$_GET["type"] == "delete" AND @$_GET["cid"]){
	?>
		<div class="alert alert-success" role="alert">
								<?php echo "#".$_GET["pid"]; ?> no'lu hizmetinize ait bilgiler başarıyla silindi.
							</div>
	<?php
}
	?>
	<div class="clearfix mb-20">
						<div class="pull-left">
							<h5 class="text-blue">Ürün/Hizmet Fiyat Listesi</h5>
							
						</div>
					</div>
<table class="data-table stripe hover">
  <thead>
    <tr>
      <th scope="col">#Sıra</th>
      <th>Ürün/Hizmet Adı</th>
      <th>Kategori</th>
      <th>İşlem</th>

    </tr>
  </thead>
  <tbody>	<?php if(permtrue("seradd")){?>
    <a href="index.php?p=new-product&cc=087s3"><button style="float:right;" type="button" class="btn btn-success">Yeni Hizmet Oluştur</button></a> <?php } ?><br><br>
    	<?php
    		$cq = $ac->prepare("SELECT * FROM services ORDER by mid ASC");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){



    			$miq = $ac->prepare("SELECT * FROM mainservices WHERE id = ?");
    			$miq->execute(array($as["mid"]));
    			$mms = $miq->fetch(PDO::FETCH_ASSOC);
    	?>
    	<tr>
      <td scope="row"><?php echo $kx;?></td>
      <td><?php echo $as["stitle"];?></td>
      <td><?php echo $mms["stitle"];?></td>
      <td>
      	<?php 
      	if(permtrue("seredit")){?><a href="index.php?p=edit-product&reg=true&md=update&pid=<?php echo $as["id"];?>"><span class="badge badge-info">Düzenle</span></a>
      <?php } ?>
      	&nbsp;&nbsp;
      	<?php if(permtrue("serdelete")){?><a onClick="return confirm('<?php echo $as["stitle"]; ?> isimli ürün/hizmeti sistemden kaldırmak istediğinize emin misiniz?')"href="index.php?p=products&mode=delete&code=04md177&reg=true&md=active&pid=<?php echo $as["id"];?>"><span class="badge badge-danger">Sil</span></a><?php } ?></td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>