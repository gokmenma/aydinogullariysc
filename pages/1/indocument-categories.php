<?php
permcontrol("indoccategories");
if($_POST && $_GET["mode"] == "new"){

	$title = $_POST["title"];
	$type = $_POST["inexp"];

	$ekle = $ac->prepare("INSERT INTO indocument_categories SET
	title = ?,
	type = ?");

	$ekle->execute(array($title,$type));
	header("Location:index.php?p=indocument-categories&st=newsuccess");
}

	$xid = @$_GET["xid"];
	if($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){
			

			$pdq = $ac->prepare("DELETE FROM indocument_categories WHERE id = ?");
			$pdq->execute(array($xid));


			header("Location: index.php?p=indocument-categories&type=delete&code=0882md25&pid=$pids");

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
		<div class="alert alert-success" role="alert">
								<?php echo "#".$_GET["pid"]; ?> no'lu hizmetinize ait bilgiler başarıyla silindi.
							</div>
	<?php
}
	?>
	<form method="POST" action="index.php?p=indocument-categories&mode=new&code=38&cc=087s3">
	<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Gelen - Giden Evrak Kategorileri</h5>
		</div>
		<button type="submit" style="float:right;" type="button" class="btn btn-success">Ekle</button>
	</div>
	
	<div class="row">
        
        <div class="col-md-3 col-sm-6">
		<div class="form-group">
			<label>	<font color="red">(*)</font>Evrak Türü</label>
			<select name="inexp" class="form-control">
				<option value="1" >Gelen Evrak </option>
				<option value="2" >Giden Evrak </option>
			</select>

		</div>
		</div>

		<div class="col-md-6 col-sm-6">
		<div class="form-group"><label>
				<label><font color="red">(*)</font>Yeni Kategori Adı:</label>
				<input required name="title" placeholder="Kategori adı giriniz" class="form-control" type="text" >	
		</div>
		</div>
	
	</div>
	</form>
<table class="stripe hover select-row data-table-export nowrap">
  <thead>
    <tr>
      <th width="15" scope="col">#Sıra</th>
      <th>Gelir/Gider</th>
      <th>Kategori Başlığı</th>
      <th class="datatable-nosort">İşlem</th>
    </tr>
  </thead>
  <tbody> 
    	<?php
    		$cq = $ac->prepare("SELECT * FROM indocument_categories ORDER by type ASC");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    	?>
    	<tr>
      <td scope="row"><?php echo $kx;?></td>
      <td><?php echo $as["type"] == 1 ? "<font color='green'>GELEN EVRAK</font> " : "<font color='red'>GİDEN EVRAK</font>";?></td>
      <td><?php echo $as["title"];?></td>
    
    

      <td>

      	&nbsp;&nbsp; <a onClick="return confirm('Bu kategori başlığını silmek istediğinize emin misiniz?')" href="index.php?p=indocument-categories&mode=delete&code=04md177&md=active&xid=<?php echo $as["id"];?>"><span class="badge badge-danger">Sil</span></a></td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>
