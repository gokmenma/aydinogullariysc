<?php
permcontrol("indoccategories");
if($_POST && $_GET["mode"] == "new")
{
	$title = $_POST["title"];
	$type = $_POST["inexp"];

	$ekle = $ac->prepare("INSERT INTO indocument_categories SET
	title = ?,
	dstatu = ?");

	$ekle->execute(array($title,$type));
	header("Location:index.php?p=indocument-categories&st=newsuccess");
}

	$id = @$_GET["id"];
	if($id && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177")
	{	
			$pdq = $ac->prepare("DELETE FROM indocument_categories WHERE id = ?");
			$pdq->execute(array($id));
			header("Location: index.php?p=indocument-categories&type=delete&code=0882md25&pid=$id");
		}

	?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<?php
	if(@$_GET["st"] == "newsuccess" ){

	?>
	<div class="alert alert-success" role="alert">	İşlem başarılı!	</div>
	
	<?php
	}
	
	?>

	<form method="POST" action="index.php?p=indocument-categories&mode=new&code=38&cc=087s3">
	
	<div class="clearfix mb-20">
		<div class="pull-left">
			<h5 class="text-blue">Gelen - Giden Evrak Kategorileri</h5>
		</div>
		<button type="submit" style="float:right;" type="button" class="btn btn-success">Ekle</button>
	</div>
	
	<div class="form-group row">
        	
			<label class="col-md-2"><font color="red">(*)</font> Evrak Türü : </label>
		<div class="input-group col-md-2">
			<select name="inexp" class="selectpicker form-control " data-style=" border bg-white"  >
				<option value="1" >Gelen Evrak </option>
				<option value="2" >Giden Evrak </option>
			</select>

		</div>
			<label class="col-md-2"><font color="red">(*)</font>Yeni Kategori Adı :</label>
			<div class="input-group col-md-4" >
				<input required name="title" placeholder="Kategori adı giriniz" class="form-control" type="text" >	
			</div>
		</div>
	
	
	</form>
	<table class="data-table select-row table-bordered table-hover">
		<thead>
			<tr>
			<th width="70" scope="col">#Sıra No</th>
			<th>Evrak Türü</th>
			<th>Kategori Başlığı</th>
			<th class="datatable-nosort">İşlem</th>
			</tr>
		</thead>
		<tbody> 
    	<?php
    		$cq = $ac->prepare("SELECT * FROM indocument_categories ORDER by dstatu ASC");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    	?>
    <tr>
      <td scope="row" align="center"><?php echo $kx;?></td>
      <td><?php echo $as["dstatu"] == 1 ? "<font color='green'>GELEN EVRAK</font> " : "<font color='red'>GİDEN EVRAK</font>";?></td>
      <td><?php echo $as["title"];?></td>
      <td>
      	&nbsp;&nbsp; 
		  <button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil" 
          onClick="deleteRecord('Kaydı silmek istediğinize emin misiniz?',<?php echo $as["id"]; ?>,'indocument-categories')"
          ><i class="fa fa-trash"></i></button>

	  </td>
    </tr>
	
	<?php 	$kx = $kx+1;	} ?>
  </tbody>
</table>
</div>
