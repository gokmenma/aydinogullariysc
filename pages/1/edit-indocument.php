<?php
permcontrol("docinadd");
$eid = $_GET["eid"];

$cerq = $ac->prepare("SELECT * FROM evraktakip WHERE id = ?");
$cerq->execute(array($_GET["eid"]));
$cc = $cerq->fetch(PDO::FETCH_ASSOC);


if ($_POST) {
	if(!$_POST["firma"] || !$_POST["evrakturu"] || !$_POST["kategori"] || !$_POST["teslimeden"]){
		header("Location: index.php?p=edit-indocument&st=empties");
		exit;
	}
    $firma = $_POST["firma"];
    $evrakturu = $_POST["evrakturu"];
    $kategori = $_POST["kategori"];
    $adet = $_POST["adet"];
    $teslimalan = sesset("id"); 
   	$teslimtarihi = $_POST["teslimtarihi"];
	$estatu=$_POST["estatu"];
    $aciklama = $_POST["aciklama"];
	foreach ($_POST["teslimeden"] as $psx){
	$teslimeden .=$psx."|";
	}

	$upxsx = $ac->prepare("UPDATE evraktakip SET
				firma = ?,
                evrakturu = ?,
				kategori = ?,
				adet = ?,
				teslimalan = ?,
                teslimeden = ?,
                teslimtarihi = ?,
				estatu = ?,
                aciklama = ?	WHERE id = ?");

		$upxsx->execute(array($firma,$evrakturu,$kategori,$adet,$teslimalan,$teslimeden,$teslimtarihi,$estatu,$aciklama,$eid ));

		if($upxsx)
		{
			if ($evrakturu==1)
			{
				header("Location: index.php?p=view-indocument&id=$eid&up=success&st=yes&mdcode=14");
			}
			else
			{
				header("Location: index.php?p=view-outdocument&id=$eid&up=success&st=yes&mdcode=14");
			}
	    	
		}
		else
		{
	   		header("Location: index.php?p=indocument-categories&st=newerror&code=acmd008");
		}
	}
	
	if(@$_GET["st"] == "empties"){
		showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
		
	}
	
?>

<form enctype="multipart/form-data" method="POST" id="myForm" action="">


<div class="pd-20 bg-white border-radius-16 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
				bırakmayın..<br></p>
		</div>
		<input type="submit" id="submitbuton"   value="Güncelle" class="float-right btn btn-success mr-2">
		
	</div>
	
	<div class="form-group row">
    	<label class="col-md-2"> <font color="red">(*)</font>Firma : </label>
			<div class="input-group col-md-4" >	    
			<select required name="firma" id="firma" class="selectpicker form-control" data-live-search="true"	data-style="border bg-white">
			<?php
					$selected_company = $cc["firma"];
					$tt = $ac->prepare("SELECT * FROM customers");
					$tt->execute();
					while ($mm2 = $tt->fetch(PDO::FETCH_ASSOC)) {
						$selected = ($mm2["id"] == $selected_company) ? "selected" : "";
			?>
						<option <?php echo $selected;?> value="<?php echo $mm2["id"];?>"><?php echo $mm2["company"];?></option>
			<?php 
				}
			?>
			</select>
			</div>
			<label class="col-md-2"> <font color="red">(*)</font>Evrak Türü : </label>
			<div class="input-group col-md-4" >	 
			<select required name="evrakturu"  id="evrakturu" class="selectpicker form-control" data-style="border bg-white ">
						<option <?php echo $cc["evrakturu"] == 1 ? "selected" : "";?> value="1">Gelen Evrak</option>
                        <option <?php echo $cc["evrakturu"] == 2 ? "selected" : "";?> value="2">Giden Evrak</option>
					</select>
			</div>
		</div>
		
			<div class="form-group row">
				<label class="col-md-2"> <font color="red">(*)</font>Kategori : </label>
					<div class="input-group col-md-4" >	
						<select required name="kategori" id="kategori" class="selectpicker form-control" data-live-search="true"	data-style="border bg-white">
						<?php
    					$evrak_id = $_GET['evrk'];
						$selected_categories = $cc["kategori"];
						$tt = $ac->prepare("SELECT * FROM indocument_categories WHERE dstatu = :dstatu");
						$tt->execute(array(':dstatu' => $evrak_id));
						while ($mm2 = $tt->fetch(PDO::FETCH_ASSOC)) {
							$selected = ($mm2["id"] == $selected_categories) ? "selected" : "";
						?>
							<option <?php echo $selected;?> value="<?php echo $mm2["id"];?>"><?php echo $mm2["title"];?></option>
						<?php 
						}
						?>
					</select>
					</div>
					<label class="col-md-2">Adet :</label>
					<div class="input-group col-md-4" >	
						<input required name="adet"  placeholder="Evrak sayısını girin" class="form-control" type="number" value="<?php echo $cc['adet']; ?>">
					</div>
			</div>
	
			<div class="form-group row">
				<label class="col-md-2"> <font color="red">(*)</font>Teslim Alan : </label>
					<div class="input-group col-md-4" >	
						<input disabled class="form-control" value="<?php echo sesset("username"); ?>" type="text">
					</div>
					<label class="col-md-2"> <font color="red">(*)</font>Teslim Eden : </label>
					<div class="input-group col-md-4" >	
					<select name="teslimeden[]" class="selectpicker form-control" class="selectpicker form-control" data-container="body" data-style="border bg-white " multiple data-max-options="3">
										<?php
											$permq = $ac->prepare("SELECT * FROM perms ");
											$permq->execute();
											while($pp = $permq->fetch(PDO::FETCH_ASSOC)){
										?>
										<optgroup label="<?php echo $pp["p_title"]; ?>">
											<?php 
												$permx = $ac->prepare("SELECT * FROM users WHERE permission = ? ");
											$permx->execute(array($pp["id"]));
											while($px = $permx->fetch(PDO::FETCH_ASSOC)){

												?>
											<option <?php
											$caks = explode("|", $cc["teslimeden"]);
												foreach($caks as $kiks){
													if($kiks == $px["id"]){
														echo "selected ";
													}
												}
											?> value="<?php echo $px["id"];?>"><?php echo $px["username"];?></option>
										<?php } ?>
										</optgroup>
									<?php } ?>
									</select>
					</div>
			</div>

            

            <div class="form-group row">
			<label class="col-md-2"> <font color="red">(*)</font>Teslim Alma Tarihi : </label>
				<div class="input-group col-md-4" >	
					<input name="teslimtarihi"	class="form-control date-picker"  class="custom-select col-12 " value="<?php echo $cc['teslimtarihi']; ?>">
				</div>
				<label class="col-md-2">Evrak Durumu :	</label>
								<div class="input-group col-md-4">	
								<select name="estatu" id="estatu" class="selectpicker">
									<option <?php echo $cc["estatu"] == "Bekliyor" ? "selected" : ""; ?> data-content="<span class='badge badge-warning'>Bekliyor</span>" value="Bekliyor">Bekliyor</option>
									<option <?php echo $cc["estatu"] == "Çalışıyor" ? "selected" : ""; ?> data-content="<span class='badge badge-primary'>Çalışıyor</span>" value="Çalışıyor">Çalışıyor</option>
									<option <?php echo $cc["estatu"] == "Tamamlandı" ? "selected" : ""; ?> data-content="<span class='badge badge-success'>Tamamlandı</span>" value="Tamamlandı">Tamamlandı</option>
								</select>

								</div>
			</div>

			<div class="form-group row">	
				<label class="col-md-2"> Açıklama : </label>
				<div class="input-group col-md-4" >	
					<textarea name="aciklama"  class="form-control" type="text" placeholder="Evrakla ilgili açıklama girebilirsiniz"> <?php echo trim($cc["aciklama"]);?></textarea>
				</div>
				
			</div>
			
			
		</div>
		
	</form>
</div>
<script>
        $(document).ready(function(){
            $('.selectpicker').selectpicker();

            $('#evrakturu').change(function(){
                var evrakturu = $(this).val();
                $.ajax({
                    url: 'pages/1/veri_al.php',
					method: 'POST',
                    dataType: 'json',
                    data: {evrakturu: evrakturu},
                    success:function(response){
                        $('#kategori').empty();
                        $.each(response, function(index, category){
                            $('#kategori').append('<option value="' + category.id + '">' + category.title + '</option>');
                        });
                        $('#kategori').selectpicker('refresh'); // Selectpicker'ı güncelle
                    }
                });
            });
        });
    </script>