<?php
permcontrol("todoadd");
if($_POST){

	$title = @$_POST["title"];
	$desc = @$_POST["desc"];
	$okey = @$_POST["okey"];
	$ldate = date_tr($_POST["lastdate"]);
	$sdate = date_tr($_POST["startdate"]);
	
	if(empty($title) || empty($desc) || empty($ldate)){
		header("Location: index.php?p=new-task&st=empties");
		exit;
	}


	$insq = $ac->prepare("INSERT INTO todolist SET
	title = ?,
	description = ?,
	regdate = ?,
	last_date = ?,
	creativer = ?,
	okey = ?");

	$insq->execute(array($title,$desc,$sdate,$ldate,sesset("id"),$okey));

	header("Location: index.php?p=todolist&st=newsuccess");


}



$dat = $ac->prepare("SELECT * FROM mainservices ");
$dat->execute();

if(@$_GET["st"] == "empties"){
		?>
			<div class="alert alert-danger" role="alert">
								(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
							</div>
		<?php
	}
	if(@$_GET["st"] == "newsuccess" ){
		?>
	<div class="alert alert-success" role="alert">
									Bilgiler kaydedildi.
								</div>
		<?php
	}
?>

<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
					<div class="clearfix">
						<div class="pull-left">
							<h4 class="text-blue"><?php echo $pdat["p_title"];?></h4>
							<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
						</div>
						
					</div>
<form method="POST" action="index.php?p=new-task">
	
	<div class="row">
		<div class="col-md-6 col-sm-12">
			<div class="form-group"> 
				<label><font color="red">(*)</font>Başlık</label>
				<input name="title" value="" required class="form-control" type="text" >
				
			</div>
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Durum</label>
			<select name="okey" class="custom-select col-12">
				<option value="1">Yapıldı</option>
				<option selected value="0">Yapılmadı</option>
				
			</select>
		</div>
	</div>
	<div class="col-md-11 col-sm-6">
			<div class="html-editor pd-20 bg-white border-radius-4 box-shadow mb-30">
					<h3 class="weight-500">Açıklama</h3>
					<p></p>
					<textarea required name="desc" class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ..."></textarea><br>
				</div>
		</div>
		<div class="col-md-6 col-sm-12">
			<div class="form-group"> 
					<label>Başlangıç Tarihi</label>
					<input name="startdate" class="form-control date-picker" placeholder="Tarih Seçin" type="text">
			
				
			</div>
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12">
			<label><font color="red">(*)</font>Son Tarih</label>
					<input name="lastdate" class="form-control date-picker" required placeholder="Tarih Seçin" type="text">
		</div>
	</div>

			

	</div><br>
	


<input type="submit" value="Değişiklikleri Kaydet" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
</form>
							</div>