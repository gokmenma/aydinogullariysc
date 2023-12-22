 
	<?php
	permcontrol("pmview");
if(!@$_GET["mode"] != "edit" && !@$_GET["pid"]){
	header("Location:index.php?p=pay-methods-list&error=noid");
	die;
	exit;
}
$pid = $_GET["pid"];
$cska = $ac->prepare("SELECT * FROM pay_methods WHERE id = ?");
$cska->execute(array($_GET["pid"]));
$cs = $cska->fetch(PDO::FETCH_ASSOC);
if(!$cs){
	header("Location:index.php?p=pay-methods-list&error=nolog");
	die;
	exit;
}

if(@$_POST){

	$title = @$_POST["title"];
	$iban = @$_POST["iban"];
	$hesapno = @$_POST["hesapno"];
	$hesapsahibi = @$_POST["hesapsahibi"];
	$subekodu = @$_POST["subekodu"];
	$cur = @$_POST["cur"];

	$reggs = $ac->prepare("UPDATE pay_methods SET
    title = ?,
    iban = ?,
    author = ?,
    accountno = ?,
    branchcode = ?,
    currency = ? WHERE id = ?");

    $reggs->execute(array($title,$iban,$hesapsahibi,$hesapno,$subekodu,$cur,$pid));

    header("Location: index.php?p=edit-pay-method&mode=edit&pid=".$pid);


}
	?>
		<div class="pd-ltr-20 xs-pd-20-10">
			<div class="min-height-200px">
			
				<!-- Default Basic Forms Start -->
				<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
					<div class="clearfix">
						<div class="pull-left">
							<h4 class="text-blue"><?php echo $pdat["p_title"];?></h4>
							<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p><br>
							<h5>Son Hesap Hareketleri</h><br><br>
						</div>
					</div>

					<table class="data-table stripe hover ">
  <thead>
    <tr>
      <th scope="col">#Sıra</th>
      <th>İşlem Tipi</th>
      <th>Başlık</th>
      <th>Tarih</th>
      <th>Bakiye</th>
      
		<?php
									if($cs["currency"] == "tl"){
										$prx = "₺";
									}elseif($cs["currency"] == "dollar"){
										$prx = "$";
									}elseif($cs["currency"] == "euro"){
										$prx = "€";
									}else{
										$prx = "₺";
									}
			$inq = $ac->prepare("SELECT * FROM inexps WHERE pay_method = ? AND type = ?");
			$inq->execute(array($pid,"exp"));
			$toti = 0;
			while($inx = $inq->fetch(PDO::FETCH_ASSOC)){
				$toti = $toti+$inx["pay"];
			}

			$exq = $ac->prepare("SELECT * FROM inexps WHERE pay_method = ? AND type = ?");
			$exq->execute(array($pid,"in"));
			$tote = 0;
			while($exx = $exq->fetch(PDO::FETCH_ASSOC)){
				$tote = $tote+$exx["pay"];
			}



		?>
    </tr>
  </thead>
  <tbody>
  
    	<?php
    		$cq = $ac->prepare("SELECT * FROM inexps WHERE pay_method = ? LIMIT 10");
    		$cq->execute(array($pid));
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){

    	?>
    	<tr title="">
    		
      <th style="font-weight:bold"scope="row"><?php echo $kx;?></th>
      <th><?php echo $as["type"] == "in" ? "<font style='font-weight:bold;color:green'>Gelir</font>" : "<font style='font-weight:bold;color:red'>Gider</font>"; ?></th>
      <th style="font-weight:bold"><?php echo $as["title"];?></th>
      <th style="font-weight:bold"><?php echo $as["sdate"];?></th>
       <th <?php echo $as["type"] == "in" ? "style='color:green'" : "style='color:red'"; ?> style="font-weight:bold"><?php echo $as["pay"]."$prx";?></th>
      

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
	
	<font style="font-weight: bold; font-size:18px">Güncel Bakiye: <?php echo $tote-$toti;?> <?php echo $prx; ?></font>
<br><br>

					<form action="" method="POST">

						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"><font color="red">(*)</font> Başlık:</label>
							<div class="col-sm-12 col-md-10"><input  value="<?php echo $cs["title"]; ?>" name="title" required type="text" class="form-control">
							</div>
						</div>

						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"> IBAN:</label>
							<div class="col-sm-12 col-md-10"><input value="<?php echo $cs["iban"]; ?>" name="iban" type="text" class="form-control">
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label">Kasa Para Birimi:</label>
							<div class="col-sm-12 col-md-10">

								<select required name="cur" class="selectpicker col-sm-3">

									<option <?php echo $cs["currency"] == "tl" ? "selected" : ""; ?> value="tl">₺ [Türk Lirası]</option>
									<option <?php echo $cs["currency"] == "dollar" ? "selected" : ""; ?> value="dollar">$ [Dolar]</option>
									<option <?php echo $cs["currency"] == "euro" ? "selected" : ""; ?> value="euro">€ [Euro]</option>
									
								
								</select>
							</div></div>
						
						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"> Hesap Sahibi:</label>
							<div class="col-sm-12 col-md-10"><input  value="<?php echo $cs["author"]; ?>" name="hesapsahibi" type="text" class="form-control">
							</div>
						</div>

						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"> Hesap No:</label>
							<div class="col-sm-12 col-md-10"><input  value="<?php echo $cs["accountno"]; ?>" name="hesapno" type="text" class="form-control">
							</div>
						</div>

						<div class="form-group row">
							<label class="col-sm-12 col-md-2 col-form-label"> Şube Kodu:</label>
							<div class="col-sm-12 col-md-10"><input  value="<?php echo $cs["branchcode"]; ?>" name="subekodu" type="text" class="form-control">
							</div>
						</div>

				<br><br>
							<button type="submit" class="btn btn-success col-md-12">Kaydet</button>
			


					</form>
				</div>

					
						</div>
					</div>
				</div>
				<!-- Input Validation End -->

			</div>
			<?php include('include/footer.php'); ?>
}
