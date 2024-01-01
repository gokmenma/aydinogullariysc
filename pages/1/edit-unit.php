<?php

$id = @$_GET["id"];

$veri = $ac->prepare("SELECT * FROM units WHERE id = ?");
$veri->execute(array($id));
$verib = $veri->fetch(PDO::FETCH_ASSOC);
?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<form method="POST" action="index.php?p=units&mode=new&code=38&cc=087s3">

		<div class="row">
			<h4>&nbsp;&nbsp;Yeni Oluştur</h4><br><br>

			<div class="col-sm-12 col-md-12">



				<div class="form-group">
					<label>
						<font color="red">(*)</font>Birim Adı:
					</label>
					<input name="title" placeholder="örn: Adet"="" class="form-control" type="text">

				</div><button type="submit" style="float:right;" type="button" class="btn btn-success">Ekle</button>
			</div>
	</form><br>
</div>