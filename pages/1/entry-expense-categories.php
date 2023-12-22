<?php
permcontrol("excategory");
if($_POST && $_GET["mode"] == "new"){

	$title = $_POST["title"];
	$type = $_POST["inexp"];

	$ekle = $ac->prepare("INSERT INTO entry_categories SET
	title = ?,
	type = ?");
	$ekle->execute(array($title,$type));
	header("Location:index.php?p=entry-expense-categories&st=newsuccess");
}

	$xid = @$_GET["xid"];
	if($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){
			

			$pdq = $ac->prepare("DELETE FROM entry_categories WHERE id = ?");
			$pdq->execute(array($xid));


			header("Location: index.php?p=entry-expense-categories&type=delete&code=0882md25&pid=$pids");

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
	<div class="clearfix mb-20">
						<div class="pull-left">
							<h5 class="text-blue">Gelir - Gider Kategorileri</h5>
							
						</div>
						
					</div><form method="POST" action="index.php?p=entry-expense-categories&mode=new&code=38&cc=087s3">
	
	<div class="row">
		<h4>&nbsp;&nbsp;Yeni Kategori Oluştur</h4><br><br>

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Gelir / Gider</label>
			<select name="inexp" class="custom-select col-12">
				<option value="1" >Gelir(+)</option>
				<option value="2" >Gider(-)</option>
				
			</select><br><br>
		
	
			<div class="form-group"> 
				<label><font color="red">(*)</font>Yeni Kategori Adı:</label>
				<input name="title" value="" class="form-control" type="text" >
				
			</div><button type="submit" style="float:right;" type="button" class="btn btn-success">Ekle</button>
		</div></form><br><br><br>
<table class="stripe hover select-row data-table-export nowrap"><br>
  <thead>
    <tr>
      <th width="15" scope="col">#Sıra</th>
      <th>Gelir/Gider</th>
      <th>Kategori Başlığı</th>
      <th class="datatable-nosort">İşlem</th>
		<?php




		?>
    </tr>
  </thead>
  <tbody> <br><br>
    	<?php
    		$cq = $ac->prepare("SELECT * FROM entry_categories ORDER by type ASC");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    	?>
    	<tr>
      <td scope="row"><?php echo $kx;?></td>
      <td><?php echo $as["type"] == 1 ? "<font color='green'>GELİR(+)</font> " : "<font color='red'>GİDER(-)</font>";?></td>
      <td><?php echo $as["title"];?></td>
    
    

      <td>

      	&nbsp;&nbsp; <a onClick="return confirm('Bu kategori başlığını silmek istediğinize emin misiniz?')" href="index.php?p=entry-expense-categories&mode=delete&code=04md177&md=active&xid=<?php echo $as["id"];?>"><span class="badge badge-danger">Sil</span></a></td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>