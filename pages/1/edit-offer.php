<?php
define("MATROW",10);
if(@$_GET["stx"] == "newreg"){
		?>
			<div class="alert alert-success" role="alert">
								Teklif başarıyla oluşturuldu. Döküman yüklemesini aşağıya sürükle-bırak yöntemi ile yapabilirsiniz.
							</div>
		<?php
	}

	permcontrol("oedit");

	if(!@$_GET["oid"]){
		header("Location: index.php?p=all-offers");
		exit;
	}



	$cerq = $ac->prepare("SELECT * FROM offers WHERE id = ?");
	$cerq->execute(array($_GET["oid"]));
	$cc = $cerq->fetch(PDO::FETCH_ASSOC);

	$ososx = $_GET["oid"];
	if(!$cc){
		header("Location: index.php?p=all-offers&err=01735");
		exit;
	}
 ?>
 

<?php
	if(@$_GET["type"] == "fileupload"){
		include("pages/1/fileuploadx.php");
		
	
	}
	define("MAXSX",set("max_sr"));

			
	if($_POST){

			

			if(!$_POST["customers"] ||  !$_POST["permings"] ){
				header("Location: index.php?p=new-offer&st=empties");
				exit;
			}
		
			

			$cur = $_POST["cur"];
			$customerx = $_POST["customers"];
			$craetiverx = sesset("id");
		
			$companyx = set("company_name");
			$taxx = @$_POST["tax"];
			if($taxx != 0 AND $taxx != 1 AND $taxx != 8 AND $taxx != 18 ){
				header("Location: index.php");
				exit; 
			}

			$pps = "";
			foreach ($_POST["permings"] as $psx){
			$pps.=$psx."|";
			}
		

			$notesx = $_POST["notesx"];

			$tdg = 1;
			$tot = 0;
			while($tdg <= MATROW){
				$tektop = $_POST["price$tdg"]*$_POST["amount$tdg"];
				$tot = $tot+$tektop;
				$tdg++;
			}

$regxs = $ac->prepare("UPDATE offers SET
                cid = ?,
                total_price = ?,
                authors = ?,
                tax = ?,
                notes = ?,
                currency = ?,
                statu = ? WHERE id = ?");

	$regxs->execute(array($customerx,$tot,$pps,$taxx,$notesx,$_POST["cur"],0,$ososx));
	$lastid = $ac->lastInsertId();

	$dg = 1;
	while($dg <= MATROW){
    
	if(trim($_POST["matter$dg"]) != ""){
	    $sellect = $ac->prepare("SELECT * FROM offermatters WHERE oid = ? AND xid = ?");
	    $sellect->execute(array($ososx,$dg));
	    if($sellect->rowCount() > 0){
	$regmatter = $ac->prepare("UPDATE offermatters SET
	title = ?,
	unit = ?,
	amount = ?,
	price = ?,
	total = ? WHERE oid = ? AND xid = ?");

	$totals= $_POST["amount$dg"]*$_POST["price$dg"];

	$regmatter->execute(array($_POST["matter$dg"],$_POST["unit$dg"],$_POST["amount$dg"],$_POST["price$dg"],$totals,$ososx,$dg));
	   }else{

        
        
    $regmatter = $ac->prepare("INSERT INTO offermatters SET
	xid = ?,
	oid = ?,
	title = ?,
	unit = ?,
	amount = ?,
	price = ?,
	total = ?");

	$totals= $_POST["amount$dg"]*$_POST["price$dg"];

	$regmatter->execute(array($dg,$ososx,$_POST["matter$dg"],$_POST["unit$dg"],$_POST["amount$dg"],$_POST["price$dg"],$totals));    
        
	   }
	    
	}

	$dg++;
	}

	if($regxs){
    header("Location: index.php?p=edit-offer&type=fileupload&insert=new&ccs=083y3&oid=$lastid&stx=newreg");
	}else{
   		header("Location: index.php?p=all-offers&st=newerror&code=acmd008");
	}


		
	}


	if(@$_GET["st"] == "empties"){
		?>
			<div class="alert alert-danger" role="alert">
								(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
							</div>
		<?php
	}elseif(@$_GET["err"] == "upload" && @$_GET["errorbec"] == "name"){
		?>
			<div class="alert alert-warning" role="alert">
								Aynı adda bir excel dosyası bulunuyor, lütfen ismini değiştirerek teklifi tekrar oluşturmayı deneyin.
							</div>
		<?php
	}elseif(@$_GET["err"] == "upload" && @$_GET["errorbec"] == "size"){
		?>
			<div class="alert alert-warning" role="alert">
								Yüklediğiniz dosyaın boyutu <b>10 MB</b>'dan daha büyük olamaz. Teklif oluşturulamadı, tekrar deneyin.
							</div>
		<?php
	}elseif(@$_GET["err"] == "upload" && @$_GET["errorbec"] == "erno"){
		?>
			<div class="alert alert-danger" role="alert">
								Teklif oluşturuldu ancak, dosya yüklenirken bir problem yaşandı.
							</div>
		<?php
	}


	?>
		<div class="pd-ltr-20 xs-pd-20-10">
			<div class="min-height-200px">
			
				<!-- Default Basic Forms Start -->
				<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
					<div class="clearfix">
						<div class="pull-left">
							<h4 class="text-blue"><?php echo $pdat["p_title"];?></h4>
							<p class="mb-30 font-14">Oluşturduğunuz teklif'in dökümanlarını sisteme upload etmeyi unutmayınız.<br></p>
						</div>
						
					</div>
					<form enctype="multipart/form-data" action="index.php?p=edit-offer&oid=<?=$ososx;?>" method="POST">

						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red">(*)</font>Müşteri:</label>
							<div class="col-sm-12 col-md-10">
								<select name="customers" class="selectpicker form-control ">
									<option value ="0" disabled selected="">Seçiniz...</option>
									<?php
										$qct = $ac->prepare("SELECT * FROM customers ORDER BY id DESC");
										$qct->execute();
										while($cscs = $qct->fetch(PDO::FETCH_ASSOC)){
									?>
									<option <?php echo $cscs["id"] == $cc["cid"] ? "selected" : ""; ?> value="<?php echo $cscs["id"];?>"><?php echo "#".$cscs["id"]." - ".$cscs["name"];?></option>
									<?php
								}
								?>
								</select>
							</div>
						</div>
						<div class="form-group row">
							<label style="font-weight: bold" class="col-sm-12 col-md-2 col-form-label">Teklif Para Birimi:</label>
							<div class="col-sm-12 col-md-10">

								<select name="cur" class="selectpicker col-sm-3">

									<option <?php echo $cc["currency"] == "tl" ? "selected" : ""; ?> value="tl">TL [Türk Lirası]</option>
									<option <?php echo $cc["currency"] == "dollar" ? "selected" : ""; ?> value="dollar">$ [Dolar]</option>
									<option <?php echo $cc["currency"] == "euro" ? "selected" : ""; ?> value="euro">€ [Euro]</option>
									
								
								</select>
							</div></div>
						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red">(*)</font>Hazırlayan</label>
							<div class="col-sm-12 col-md-10">
								<input disabled class="form-control" value="<?php echo sesset("username");?>" type="text">
							</div>
						</div>

						<?php
							
							
							$MT = 1;
							while($MT <= MATROW){
							
							$quer = $ac->prepare("SELECT * FROM offermatters WHERE oid = ? AND xid = ?");
							$quer->execute(array($_GET["oid"],$MT));
							$qx = $quer->fetch(PDO::FETCH_ASSOC);


								
						?>
						
						<div id="mattersx" class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red"></font>Kalem <?php echo $MT; ?>:</label>
							<div class="col-sm-12 col-md-10">
								<select name="matter<?php echo $MT;?>" class="selectpicker col-sm-3">
									<option value ="" >Seçiniz...</option>
									<?php
										$qcts = $ac->prepare("SELECT * FROM services ORDER BY mid ASC");
										$qcts->execute();
										while($svs = $qcts->fetch(PDO::FETCH_ASSOC)){

									?>

									<option <?php echo $qx["title"] == $svs["stitle"] ? "selected" : ""; ?> value="<?php echo $svs["stitle"]; ?>"><?php echo $svs["stitle"];?>
										
									</option>
									
									<?php

								}
								?>
								</select>
								<select name="unit<?php echo $MT;?>"class="selectpicker col-sm-3">
									<option value="0" >Birim seçiniz</option>
									<?php
										$unq = $ac->prepare("SELECT * FROM units ");
										$unq->execute();
										while($uu = $unq->fetch(PDO::FETCH_ASSOC)){
									?>
									<option <?php echo $qx["unit"] == $uu["title"] ? "selected" : ""; ?> value="<?php echo $uu["title"]; ?>"><?php echo $uu["title"]; ?></option>
									<?php } ?>
								</select>Miktar :  <input name="amount<?php echo $MT;?>" class="selectpicker col-sm-1" value="<?php echo $qx["amount"]; ?>"></input>&nbsp;&nbsp;B. Fiyatı:  <input value="<?php echo $qx["price"]; ?>" name="price<?php echo $MT;?>" class="selectpicker col-sm-2"></input>
								
							</div>
							</div>
						
						<?php
						$MT++;
						 } ?>

							
						

						
						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red">(*)</font>Şirket:</label>
							<div class="col-sm-12 col-md-10">
								<input disabled class="form-control" name="compx" value="<?php echo $cc["mycompany"];?>" type="email">
							</div>
						</div><br>
						<div class="col-md-6 col-sm-12">
									<font color="red">(*)</font><label class="weight-600">KDV</label>
									<select name="tax" class="form-control ">
										<option <?php echo $cc["tax"] == 0 ? "selected" : ""; ?> value="0">0%</option>
										<option <?php echo $cc["tax"] == 1 ? "selected" : ""; ?> value="1">1%</option>
										<option <?php echo $cc["tax"] == 8 ? "selected" : ""; ?> value="8">8%</option>
										<option <?php echo $cc["tax"] == 18 ? "selected" : ""; ?> value="18">18%</option>
									</select>
								</div>
							<br>

						<div class="form-group">
							<label>Notlar</label>
							<textarea name="notesx" placeholder="Teklif hakkında bilgilendirici nitelikte not ekleyiniz."  class="form-control"><?php echo $cc["notes"]; ?></textarea>
						</div>
							
						<div class="form-group row col-md-4 col-sm-12">
								<div class="form-group">
									<font color="red">(*)</font><label>Teklif Yetkilileri</label>
									<select name="permings[]" class="selectpicker form-control" data-style="btn-outline-secondary" multiple data-actions-box="true" data-selected-text-format="count">
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
												$autx = explode("|",$cc["authors"]);
											foreach($autx as $au){
												if($au == $px["id"]){
													$active = true;
												}
											}
												?>
											<option <?php echo @$active == true ? "selected" : ""; ?> value="<?php echo $px["id"];?>"><?php echo $px["username"];?></option>
										<?php } ?>
										</optgroup>
									<?php } ?>
									</select>



								</div>
							</div>

						
						
						<div class="form-group">
							<input id="submit-all" type="submit" value="Teklifi Kayıt Et" class="form-control btn-outline-secondary">
						</div>
					</form>
					
				

					
						</div>
					</div>
				</div>
				<!-- Input Validation End -->

			</div>

