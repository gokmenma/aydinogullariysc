
	<?php

	permcontrol("seredit");

	if(@!$_GET["pid"]){
		header("Location:index.php?p=products&update=none&code=md88763");
		exit;

	}

	$cerq = $ac->prepare("SELECT * FROM services WHERE id = ?");
	$cerq->execute(array($_GET["pid"]));
	$cc = $cerq->fetch(PDO::FETCH_ASSOC);

	$pid = $_GET["pid"];
	if(!$cc){
		header("Location: index.php?p=products&err=01735");
		exit;
	}





if($_POST){

	$stitle = @$_POST["stitle"];
	$sdesc = @$_POST["sdesc"];
	$smain = @$_POST["smain"];
	
	if(empty($stitle) || empty($sdesc) || empty($smain)){
		header("Location: index.php?p=edit-product&st=empties&pid=$pid");
		exit;
	}

	


	$insq = $ac->prepare("UPDATE services SET
	mid = ?,
	stitle = ?,
	sdesc = ? WHERE id = ?");

	$insq->execute(array($smain,$stitle,$sdesc,$pid));

	header("Location: index.php?p=edit-product&pid=$pid&st=newsuccess");


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
	}else if(@$_GET["st"] == "numericerror"){
		?>
			<div class="alert alert-danger" role="alert">
								Fiyat kısmına sadece rakamlardan oluşan değer girebilirsiniz.
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
<form method="POST" action="index.php?p=edit-product&pid=<?php echo $_GET["pid"];?>">
	
	<div class="row">
		<div class="col-md-6 col-sm-12">
			<div class="form-group"> 
				<label><font color="red">(*)</font>Hizmet Adı</label>
				<input name="stitle" value="<?php echo $cc["stitle"];?>" class="form-control" type="text" >
				
			</div>
		</div>
		
		<div class="form-group row col-md-6 col-sm-12">

		<div class="col-sm-12 col-md-12"><label><font color="red">(*)</font>Kategori Seçimi</label>
			<select name="smain" class="custom-select col-12">
				<option disabled selected="">Kategori Seçimi</option>
				<?php
					$ms = $ac->prepare("SELECT * FROM mainservices ");
					$ms->execute();
					while($mm = $ms->fetch(PDO::FETCH_ASSOC)){
						if($mm["stitle"]){
				?>
				<option <?php echo $cc["mid"] == $mm["id"] ? "selected" : "";?> value="<?php echo $mm["id"];?>"><?php echo $mm["stitle"]; ?></option>
				<?php
					}
				}
				?>
			</select>
		</div>
	</div>
	<div class="col-md-11 col-sm-12">
			<div class="form-group"> 
				<label><font color="red">(*)</font>Açıklama </label>
				<textarea name="sdesc" value="" class="form-control" type="text" ><?php echo $cc["sdesc"];?></textarea>
				
			</div>
		</div>

	</div><br>
	


<input type="submit" value="Değişiklikleri Kaydet" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br>
</form>
							</div>