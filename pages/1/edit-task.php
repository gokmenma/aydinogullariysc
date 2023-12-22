<?php
permcontrol("todoedit");
if(!$_GET["tid"]){
	header("Location: index.php");
	exit;
}
$tid = $_GET["tid"];
if($_POST){

	$title = @$_POST["title"];
	$desc = @$_POST["desc"];
	$okey = @$_POST["okey"];
	$ldate = date_tr($_POST["lastdate"]);
	
	if(empty($title) || empty($desc) || empty($ldate)){
		header("Location: index.php?p=edit-task&tid=$tid&st=empties");
		exit;
	}


	$insq = $ac->prepare("UPDATE todolist SET
	title = ?,
	description = ?,
	last_date = ?,
	okey = ? WHERE id = ?");

	$insq->execute(array($title,$desc,$ldate,$okey,$tid));

	


}



$dat = $ac->prepare("SELECT * FROM todolist WHERE id = ?");
$dat->execute(array($tid));
$dd = $dat->fetch(PDO::FETCH_ASSOC);

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
		<div class="col-md-6 col-sm-12">
			<div class="form-group"> 
				<label><font color="red">(*)</font>Başlık</label>
				<input name="title" value="<?php echo $dd["title"];?>" class="form-control" type="text" >
				
			</div>
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Durum</label>
			<select name="okey" class="custom-select col-12">
				<option <?php echo $dd["okey"] == 1 ? "selected" : "";?> value="1">Yapıldı</option>
				<option <?php echo $dd["okey"] == 0 ? "selected" : "";?> value="0">Yapılmadı</option>
				
			</select>
		</div>
	</div>
	<div class="col-md-11 col-sm-6">
			<div class="html-editor pd-20 bg-white border-radius-4 box-shadow mb-30">
					<h3 class="weight-500">Açıklama</h3>
					<p></p>
					<textarea name="desc" class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ..."><?php echo $dd["description"];?></textarea><br>
				</div>
		</div>
		<div class="col-md-11 col-sm-6">
		<div class="form-group">
									<label><font color="red">(*)</font>Son Tarih</label>
										<input name="lastdate" class="form-control date-picker" placeholder="Tarih Seçin" value="<?php echo redate_tr($dd["last_date"]);?>" type="text">
									</div>
							</div>
	</div><br>
	


<input type="submit" value="Değişiklikleri Kaydet" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
</form>
							</div>