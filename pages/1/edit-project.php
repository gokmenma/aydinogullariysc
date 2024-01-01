<?php
permcontrol("pedit");
	if(!@$_GET["pid"]){
		header("Location: index.php?p=all-projects");
		exit;

		}

	$cerq = $ac->prepare("SELECT * FROM projects WHERE id = ?");
	$cerq->execute(array($_GET["pid"]));
	$cc = $cerq->fetch(PDO::FETCH_ASSOC);

	$ososx = $_GET["pid"];
	$pis = $ososx;
	if(!$cc){
		header("Location: index.php?p=all-projects&err=01735");
		exit;
	}

	if(@$_GET["type"] == "fileupload"){
		include("pages/1/project-files.php");
		
	
	}


	if($_POST){

		if(@$_POST["pstatu"]){
			$pstatu = $_POST["pstatu"];
		}else{
			$pstatu = 0;
		}

			print_r($_POST);

			if(!$_POST["ptitle"] || !$_POST["pdesc"] || !$_POST["permings"] ){
				header("Location: index.php?p=edit-project&st=empties&pid=$ososx");
				exit;
			}
			
			$ptitle = $_POST["ptitle"];
			$pdesc = $_POST["pdesc"];
			$pstartdate = date_tr($_POST["pstartdate"]);
			$pfinaldate = date_tr($_POST["pfinaldate"]);
			$pnotes = addslashes($_POST["pnotes"]);
			$pstatu = $_POST["pstatu"];

			if($pstatu == 1){
				$upof = $ac->prepare("UPDATE offers SET
				statu = ? WHERE id = ?");
				$upof->execute(array(5,$cc["poid"]));
			}

			$pps = "";
			foreach ($_POST["permings"] as $psx){
			$pps.=$psx."|";
			}

			
				
		$upxsx = $ac->prepare("UPDATE projects SET
			ptitle = ?,
			pdesc = ?,
			pstart_date = ?,
			pfinal_date = ?,
			pauthors = ?,
			pnotes = ?,
			pstatu = ? WHERE id = ?");

		$upxsx->execute(array($ptitle,$pdesc,$pstartdate,$pfinaldate,$pps,$pnotes,$pstatu,$ososx));

		if($upxsx){
	    	header("Location: index.php?p=edit-project&pid=$ososx&up=success&st=yes&mdcode=14");
		}else{
	   		header("Location: index.php?p=all-projects&st=newerror&code=acmd008");
		}


		
	}

	if(@$_GET["st"] == "yes"){
		
			?>
							<div class="alert alert-success" role="alert">
								Proje bilgileri başarıyla kaydedildi. 
							</div>
		<?php
	}
	if(@$_GET["st"] == "empties"){
		?>
			<div class="alert alert-danger" role="alert">
								(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
							</div>
		<?php
	}

	$ofinf = $ac->prepare("SELECT * FROM offers WHERE id = ?");
	$ofinf->execute(array($cc["poid"]));
	$ofi = $ofinf->fetch(PDO::FETCH_ASSOC);
	?>

<div class="col-lg-12 col-md-6 col-sm-12 mb-30">
						<div class="pd-20 bg-white border-radius-4 box-shadow">
							<h5 class="mb-20">Proje Durumu</h5>
							<div class="progress mb-20">
								<?php
								if($cc["pstatu"] == 0){
									$yuzde = 50;
									$renk = "success";
								}else{
									$yuzde = 100;
									$renk = "danger";
								}
								?>
								<div class="progress-bar bg-<?php echo $renk; ?> progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo $yuzde;?>%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
								
							</div>
						</div>
					</div>
		<div class="pd-ltr-20 xs-pd-20-10">
			<div class="min-height-200px">
			<form enctype="multipart/form-data" action="index.php?p=edit-project&reg=true&pid=<?php echo $_GET["pid"];?>" method="POST">
				<!-- Default Basic Forms Start -->


				<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
					<div class="clearfix">
						<div class="pull-left">

							


							<h4 class="text-blue"><?php echo $pdat["p_title"]." #".$cc["id"];?></h4>
							<br>
								<?php
								
									?>
											<p style="color:red" class="mb-30 font-14">Proje'yi tamamlanmış göstermeniz durumunda bu işlemi geri alamazsınız!</p><label class="">Proje Durumu:</label><select <?php echo $cc["pstatu"] == "1" ? "disabled" : ""; ?> name="pstatu" id="" class="selectpicker" >
								<option <?php echo $cc["pstatu"] == "1" ? "selected" :"";?> value="1">Proje Tamamlandı!</option>
								<option <?php echo $cc["pstatu"] == "0" ? "selected" :"";?> value="0">Proje Üzerinde Çalışılıyor!</option>
								<?php
							
						
								?>

							</select> <br><br><br><br>
							
						</div>

					<?php
						


					?>
						<h4 class="text-center mb-30 weight-600">&nbsp;<a target="_blank" href="index.php?p=offer&oid=<?php echo $cc["poid"]; ?>"><button style="float:right;" type="button" class="btn btn-info">Teklif'i Görüntüle</button></a></h4> 
						

					</div>
					
						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red">(*)</font>Müşteri:</label>
							<div class="col-sm-12 col-md-10">
								<select name="pcustomer" disabled name="customers" class="selectpicker form-control ">
									<option value ="0" disabled ="">Seçiniz...</option>
									<?php
										$qct = $ac->prepare("SELECT * FROM customers ORDER BY id DESC");
										$qct->execute();
										while($cscs = $qct->fetch(PDO::FETCH_ASSOC)){
									?>
									<option disabled  <?php echo $cscs["id"] == $cc["pcid"] ? "selected" : "";?> value="<?php echo $cscs["id"];?>"><?php echo "#".$cscs["id"]." - ".$cscs["name"];?></option>
									<?php
								}
								?>
								</select>
							</div>
						</div>
						
						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red">(*)</font>Hazırlayan:</label>
							<div class="col-sm-12 col-md-10">
								<input disabled name="pcreativer" class="form-control" value="<?php echo uset($cc['pcreativer'], "username"); ?>" type="text">
							</div>
						</div>

						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red">(*)</font>Teklif No:</label>
							<div class="col-sm-12 col-md-10">
								<input disabled class="form-control" value="#<?php echo $ofi["id"]; ?>" type="text">
							</div>
						</div>

						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red">(*)</font>Şirket:</label>
							<div class="col-sm-12 col-md-10">
								<input disabled class="form-control" value="<?php echo $ofi["mycompany"];?>" type="text">
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red">(*)</font>Proje Başlığı:</label>
							<div class="col-sm-12 col-md-10">
								<input name="ptitle" class="form-control" value="<?php echo $cc['ptitle']; ?>" type="text">
							</div>
						</div>
							<br>

						<div class="form-group">
							<label><font color="red">(*)</font>Proje Açıklaması</label>
							<textarea name="pdesc" placeholder="Teklif hakkında bilgilendirici nitelikte not ekleyiniz." class="form-control"><?php echo $cc["pdesc"];?></textarea>
						</div>
							
						<div class="form-group row col-md-4 col-sm-12">
								<div class="form-group">
									<font color="red">(*)</font><label>Proje Yetkilileri</label>
									<select name="permings[]" class="selectpicker form-control" data-style="btn-outline-secondary" multiple data-max-options="3">
										<?php
											$permq = $ac->prepare("SELECT * FROM perms ");
											$permq->execute();
											while($pp = $permq->fetch(PDO::FETCH_ASSOC)){
										?>
										<optgroup label="<?php echo $pp["p_title"]; ?>">
											<?php 
												$permx = $ac->prepare("SELECT * FROM users WHERE perm = ? ");
											$permx->execute(array($pp["id"]));
											while($px = $permx->fetch(PDO::FETCH_ASSOC)){

												?>
											<option <?php
											$caks = explode("|", $cc["pauthors"]);
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
							
							<div class="form-group">
									<label><font color="red">(*)</font>Çalışma Başlangıç Tarihi</label>
										<input <?php echo $cc["pstatu"] == "1" ? "disabled" : ""; ?> name="pstartdate" value="<?php echo $cc["pstart_date"];?>" class="form-control date-picker" placeholder="Tarih Seçin" type="text">
							</div>
							<div class="form-group">
									<label><font color="red">(*)</font>Planlanan Bitiş Tarihi</label>
										<input <?php echo $cc["pstatu"] == "1" ? "disabled" : ""; ?> name="pfinaldate" value="<?php echo $cc["pfinal_date"];?>" class="form-control date-picker" placeholder="Tarih Seçin" type="text">
							</div>
								

	
							<div class="html-editor pd-20 bg-white border-radius-4 box-shadow mb-30">
					<h3 class="weight-500">Projenin Son Durumu</h3>
					<p>Projenin son durumu hakkında bir mesaj bırakınız. Çalışma süresince bu not yetkililer tarafından güncellenebilir.</p>
					<textarea name="pnotes" class="textarea_editor form-control border-radius-0" placeholder="Çalışmaya yeni başlanıldı..."><?php echo $cc["pnotes"];?></textarea>
				</div>





						
						<div class="form-group">
							<input type="submit" value="Projeyi Güncelle"class="form-control btn-outline-secondary">
						</div>
					</form>
				

					
						</div>
					</div>
				</div>
				<!-- Input Validation End -->

			</div>

