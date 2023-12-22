<?php
permcontrol("cedit");
$id = @$_GET["id"];

if(!$id) header("Location: index.php?p=index");


$qq = $ac->prepare("SELECT * FROM cgroups WHERE id = ?");
$qq->execute(array($id));
$dat = $qq->fetch(PDO::FETCH_ASSOC);

if(!$dat){
	header("Location: index.php");
	exit;
}

if($_POST && $_GET["mode"] == "new"){


	$title = $_POST["title"];


	$ekle = $ac->prepare("UPDATE cgroups SET
	title = ? WHERE id = ?");
	$ekle->execute(array($title,$id));

	header("Location:index.php?p=customer-groups&st=newsuccess");
}
	
?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">

	<div class="clearfix mb-20">
						<div class="pull-left">
							<h5 class="text-blue">Müşteri Grubunu Düzenle</h5>
							
						</div>
						
					</div><form method="POST" action="index.php?p=edit-cgroup&id=<?php echo $id; ?>&mode=new&code=38&cc=087s3">
	
	<div class="row">
		

		<div class="col-sm-12 col-md-12">

		
	
			<div class="form-group"> 
				<label><font color="red">(*)</font>Başlık:</label>
				<input name="title" value="<?php echo $dat["title"]; ?>" class="form-control" type="text" ><br>
				
			</div><button type="submit" style="float:right;" type="button" class="btn btn-success">Kaydet</button><br><br><br>
		</div></form>

</div>