<?php
permcontrol("noteadd");
if($_POST){

	$title = @$_POST["title"];
	$desc = @$_POST["desc"];
	$sdate = @$_POST["startdate"] ? date_tr($_POST["startdate"]) : TODAY;
	$lastdate = date_tr(@$_POST["lastdate"]);
	$urg = $_POST["urgency"];
	$cat = $_POST["cat"];

	if(empty($title) || empty($desc)){
		header("Location: index.php?p=new-note&st=empties");
		exit;
	}


	$insq = $ac->prepare("INSERT INTO notes SET
	category = ?,
	title = ?,
	dates = ?,
	lastdate = ?,
	creativer = ?,
	urgency = ?,
	descs = ?");

	$insq->execute(array($cat,$title,$sdate,$lastdate,sesset("id"),$urg,$desc));

	header("Location: index.php?p=all-notes&st=newsuccess");


}




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
<form method="POST" action="">
	
	<div class="row">
		<div class="col-md-12 col-sm-12">
			<div class="form-group"> 
				<label><font color="red">(*)</font>Başlık</label>
				<input name="title" value="" class="form-control" type="text" >
				
			</div>
			<div class="form-group"> 
					<label>Aciliyet</label>
					<select name="urgency" class="selectpicker">
						<option value="Yüksek">Yüksek</option>
						<option value="Orta">Orta</option>
						<option value="Düşük">Düşük</option>

						
					</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<label>Kategori</label>
					<select  name="cat" class="selectpicker">
						<?php 
						$nqu = $ac->prepare("SELECT * FROM note_categories");
						$nqu->execute();
						while($nn = $nqu->fetch(PDO::FETCH_ASSOC)){
						 ?>
						
						<option value="<?php echo $nn["id"]; ?>"><?php echo $nn["title"]; ?></option>
						<?php } ?>
					</select>
					
			
			
				
			</div>
			<div class="html-editor pd-20 bg-white border-radius-4 box-shadow mb-30">
					<h3 class="weight-500">Not Oluştur</h3>
					<p></p>
					<textarea name="desc" class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ..."></textarea><br>
				</div>
		</div>
	<div class="col-md-6 col-sm-12">
			<div class="form-group"> 
					<label>Başlangıç Tarihi</label>
					<input name="startdate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">
			
				
			</div>
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12">
			<label>Son Tarih</label>
					<input name="lastdate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">
		</div>
	</div>
	</div><br>
	


<input type="submit" value="Değişiklikleri Kaydet" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
</form>
							</div>