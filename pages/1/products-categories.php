<?php
permcontrol("sercategory");
if($_POST && $_GET["mode"] == "new"){

	$title = $_POST["title"];

	$ekle = $ac->prepare("INSERT INTO products_categories SET
	title = ?,
	regdate = ?");
	
	$ekle->execute(array($title,TODAY." - ".date("H:i:s")));
	header("Location:index.php?p=products-categories&st=newsuccess");
}

	$xid = @$_GET["id"];
	if($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){
			$stcontrol = $ac->prepare("SELECT * FROM products_categories WHERE id = ?");
			$stcontrol->execute(array($xid));
			$sts = $stcontrol->fetch(PDO::FETCH_ASSOC);
			if(!$sts){
				header("Location: index.php?p=products-categories&err=true");
				exit;
				die;
			}	
			$stdel = $ac->prepare("DELETE FROM products_categories WHERE id = ?");
			$stdel->execute(array($xid));
			header("Location: index.php?p=products-categories&type=delete&code=0882md25&pid=$pids");
		}
		if (@$_GET["st"] == "newsuccess") {
			showAlert("success", "İşlem Başarı ile tamamlandı!");
		}
?>

<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	
	<?php
	if(@$_GET["type"] == "delete" AND @$_GET["cid"]){
	?>
		<div class="alert alert-success" role="alert">
			<?php echo "#".$_GET["pid"]; ?> no'lu hizmetinize ait bilgiler başarıyla silindi.
		</div>
	<?php
}
	?>
	<form method="POST" action="index.php?p=products-categories&mode=new&code=38&cc=087s3">
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Ürün&Hizmet Listesi Kategorileri</h5>				
		</div>	
		<button type="submit" style="float:right;" type="button" class="btn btn-success">Ekle</button>	
	</div>
	<div class="row">
		<div class="col-sm-12 col-md-12">
			<div class="form-group"> 
				<label><font color="red">(*)</font>Yeni Kategori Adı:</label>
				<input required name="title" value="" class="form-control" type="text" >
			</div>
		</div>
	</div>
	</form>

	<table class="data-table select-row table-bordered table-hover">
  <thead>
    <tr>
      <th width="70" >#Sıra No</th>
      <th>Kategori Başlığı</th>
      <th >Oluşturulma Tarihi</th>
      <th class="datatable-nosort">İşlem Yap</th>
	</tr>
  </thead>
  <tbody> <br>
    	<?php
    		$categ1 = $ac->prepare("SELECT * FROM products_categories ORDER BY id ASC");
    		$categ1->execute();
    		$kx = 1;
    		while($ccs = $categ1->fetch(PDO::FETCH_ASSOC)){
		?>
    	<tr>
			<td scope="row" align="center"><?php echo $kx;?></td>
			<td><?php echo $ccs["title"];?></td>
			<td><?php echo $ccs["regdate"];?></td>
			<td>
			&nbsp;&nbsp; <a href="#" onClick="deleteRecord('<?php echo $ccs["title"]; ?> isimli ürün/hizmeti sistemden kaldırmak istediğinize emin misiniz?',<?php echo $ccs["id"]; ?>,'products-categories')"><span class="badge badge-danger">Sil</span></a>
			</td>
    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>