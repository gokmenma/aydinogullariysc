<?php

if($_POST && $_GET["mode"] == "new"){
	$title = $_POST["title"];
	$ekle = $ac->prepare("INSERT INTO units SET
	title = ?,
	regdate = ?,
	statu = ?");
	$ekle->execute(array($title,TODAY." - ".date("H:i:s")."",2));
	header("Location:index.php?p=servicestype&st=newsuccess");
}

	$xid = @$_GET["id"];
	if($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){	

			$pdq = $ac->prepare("DELETE FROM units WHERE id = ?");
			$pdq->execute(array($xid));
			header("Location: index.php?p=servicestype&type=delete&code=0882md25");
		}
?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if(@$_GET["st"] == "newsuccess" )
	{
		showAlert("success", "İşlem Başarı ile tamamlandı!");
	}
	if(@$_GET["type"] == "delete")
	{
		showAlert("alert", "Kayıt Başarı ile silindi!");
	}
	?>
	<form method="POST" action="index.php?p=servicestype&mode=new&code=38&cc=087s3">
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Servis Konusu Ekleme Sayfası</h5>
		</div>
		
	</div>
	<div class="form-group row">
			
			<label class="col-md-2"><font color="red">(*)</font>Servis Konusu :</label>
			<div class="input-group col-md-8">
					<input required name="title" placeholder="örn: Bakım" ="" class="form-control" type="text" >
			</div>
			<div class="input-group col-md-2">
				<button type="submit" style="float:right;" type="button" class="btn btn-success"><i class="fa fa-plus"></i> Ekle</button>	
			</div>
		</div>
	</form>
	<table class="data-table select-row table-bordered table-hover" style="text-align: center;">
  <thead>
    <tr>
      <th width="15" scope="col">#Sıra</th>
      <th>Servis Konusu</th>
      <th>Eklenme Tarihi</th>
      <th class="datatable-nosort">İşlem</th>
    </tr>
  </thead>
  <tbody> 
    	<?php
    		$cq = $ac->prepare("SELECT * FROM units WHERE statu = '2' ");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){
    	?>
    <tr>
      <td scope="row"><?php echo $kx;?></td>
      <td><?php echo $as["title"]; ?></td>
      <td><?php echo $as["regdate"];?></td>
      <td>
	  <a class="btn btn-sm btn-danger text-white" data-tooltip="Sil"  onClick="deleteRecord('Kaydı silmek istediğinize emin misiniz?',<?php echo $as["id"]; ?>,'servicestype')"
          ><i class="fa fa-trash"></i></a>
	  </td>
    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div>