<?php
permcontrol("sercategory");
if($_POST && $_GET["mode"] == "new"){

	$title = $_POST["title"];
	$sdesc = $_POST["sdesc"];

	$ekle = $ac->prepare("INSERT INTO mainservices SET
	stitle = ?,
	sdesc = ?,
	regdate = ?,
	color = ?");
	$ekle->execute(array($title,$sdesc,TODAY." - ".date("H:i:s"),"black"));
	header("Location:index.php?p=categories&st=newsuccess");
}

	$xid = @$_GET["xid"];
	if($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){
			$stcontrol = $ac->prepare("SELECT * FROM mainservices WHERE id = ?");
			$stcontrol->execute(array($xid));
			$sts = $stcontrol->fetch(PDO::FETCH_ASSOC);
			if(!$sts){
				header("Location: index.php?p=categories&err=true");
				exit;
				die;
			}
			
			$stdel = $ac->prepare("DELETE FROM mainservices WHERE id = ?");
			$stdel->execute(array($xid));

			


			header("Location: index.php?p=categories&type=delete&code=0882md25&pid=$pids");

		}

	
?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if(@$_GET["st"] == "newsuccess" ){



?>
<div class="alert alert-success" role="alert">
								Kayıt oluşturuldu.
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
							<h5 class="text-blue">Hizmet Listesi Kategorileri</h5>
							
						</div>
						
					</div><form method="POST" action="index.php?p=categories&mode=new&code=38&cc=087s3">
	
	<div class="row">
		<h4>&nbsp;&nbsp;Yeni Kategori Oluştur</h4><br><br>

		<div class="col-sm-12 col-md-12">

		
	
			<div class="form-group"> 
				<label><font color="red">(*)</font>Yeni Kategori Adı:</label>
				<input required name="title" value="" class="form-control" type="text" ><br>
				<label>Açıklama:</label>
				<textarea name="sdesc" value="" class="form-control" type="text" ></textarea>
				
			</div><button type="submit" style="float:right;" type="button" class="btn btn-success">Ekle</button><br><br><br>
		</div></form>
<table class="data-table stripe hover "><br>
  <thead>
    <tr>
      <th  >#Sıra <br> No</th>
      <th>Kategori <br> Başlığı</th>
      <th >Oluşturulma Tarihi</th>
      <th >Ürün/Hizmet <br> Sayısı</th>
      <th class="datatable-nosort">İşlem <br> Yap</th>
		<?php




		?>
    </tr>
  </thead>
  <tbody> <br><br>
    	<?php
    		$categ1 = $ac->prepare("SELECT * FROM mainservices ORDER BY id ASC");
    		$categ1->execute();
    		$kx = 1;
    		while($ccs = $categ1->fetch(PDO::FETCH_ASSOC)){

    	$sfetch = $ac->prepare("SELECT COUNT(*) FROM services WHERE mid = ?");
    	$sfetch->execute(array($ccs["id"]));
    	$ss = $sfetch->fetchColumn();

    	?>
    	<tr title="<?php echo $ccs["sdesc"]; ?>">
      <td scope="row"><?php echo $kx;?></td>
      <td><?php echo $ccs["stitle"];?></td>
      <td><?php echo $ccs["regdate"];?></td>
      <td><?php echo $ss; ?></td>
    

      <td>

      	&nbsp;&nbsp; <a onClick="return confirm('Bu kategori başlığını silmek istediğinize emin misiniz?')" href="index.php?p=categories&mode=delete&code=04md177&md=active&xid=<?php echo $ccs["id"];?>"><span class="badge badge-danger">Sil</span></a></td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>