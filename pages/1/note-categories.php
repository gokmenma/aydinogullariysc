<?php

if($_POST && $_GET["mode"] == "new"){

	$title = $_POST["title"];

	$ekle = $ac->prepare("INSERT INTO note_categories SET
	title = ?,
	regdate = ?");
	$ekle->execute(array($title, TODAY));
	header("Location:index.php?p=note-categories&st=newsuccess");
}

	$xid = @$_GET["xid"];
	if($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){
			

			$pdq = $ac->prepare("DELETE FROM note_categories WHERE id = ?");
			$pdq->execute(array($xid));


			header("Location: index.php?p=note-categories&type=delete&code=0882md25");

		}

	
?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if(@$_GET["st"] == "newsuccess" ){



?>
<div class="alert alert-success" role="alert">
								Kayıt başarıyla oluşturuldu.
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
							<h5 class="text-blue">Not Tipleri</h5>
							
						</div>
						
					</div><form method="POST" action="index.php?p=note-categories&mode=new&code=38&cc=087s3">
	
	<div class="row">
		<h4>&nbsp;&nbsp;Yeni Oluştur</h4><br><br>

		<div class="col-sm-12 col-md-12">

		
	
			<div class="form-group"> 
				<label><font color="red">(*)</font>Yeni Kategori Adı:</label>
				<input name="title" placeholder="örn: Telefon Görüşmesi" ="" class="form-control" type="text" >
				
			</div><button type="submit" style="float:right;" type="button" class="btn btn-success">Ekle</button>
		</div></form><br><br><br>
<table class="data-table stripe hover"><br>
  <thead>
    <tr>
      <th width="15" scope="col">#Sıra</th>
      <th>Birim Adı</th>
      <th>Eklenme Tarihi</th>
      <th class="datatable-nosort">İşlem</th>
		<?php




		?>
    </tr>
  </thead>
  <tbody> <br><br>
    	<?php
    		$cq = $ac->prepare("SELECT * FROM note_categories ORDER by id DESC");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    	?>
    	<tr>
      <td scope="row"><?php echo $kx;?></td>
      <td><?php echo $as["title"]; ?></td>
      <td><?php echo $as["regdate"];?></td>
    
    

      <td>

      	&nbsp;&nbsp; <a onClick="return confirm('Silmek istediğinize emin misiniz?')" href="index.php?p=note-categories&mode=delete&code=04md177&md=active&xid=<?php echo $as["id"];?>"><span class="badge badge-danger">Sil</span></a></td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>