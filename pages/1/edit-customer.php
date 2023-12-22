<?php
permcontrol("cedit");
if (@!$_GET["cid"]) {
	header("Location:index.php?p=customer-list");
	exit;
}





$cerq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$cerq->execute(array($_GET["cid"]));
$cc = $cerq->fetch(PDO::FETCH_ASSOC);

$cid = $_GET["cid"];
if (!$cc) {
	header("Location: index.php?p=customer-list&err=01735");
	exit;
}

$todos = $ac->prepare("SELECT COUNT(*) FROM projects WHERE pcid = ?");
$todos->execute(array($cid));
$pjs = $todos->fetchColumn();

$todoso = $ac->prepare("SELECT COUNT(*) FROM offers WHERE cid = ?");
$todoso->execute(array($cid));
$ojs = $todoso->fetchColumn();


$tdx = $ac->prepare("SELECT * FROM offers WHERE cid = ? ORDER BY id DESC");
$tdx->execute(array($cid));
$ojsx = $tdx->fetch(PDO::FETCH_ASSOC);

$tdxp = $ac->prepare("SELECT * FROM projects WHERE pcid = ? ORDER BY id DESC");
$tdxp->execute(array($cid));
$ojsp = $tdxp->fetch(PDO::FETCH_ASSOC);



if ($_POST) {

	if (!$_POST["cname"]) {
		header("Location: index.php?p=edit-customer&cid=$cid&st=empties");
		exit;
	}


	$cname = @$_POST["cname"];
	$cemail = @$_POST["cemail"];
	$ccompany = @$_POST["ccompany"];
	$csector = @$_POST["csector"];
	$caddress = @$_POST["caddress"];
	$ccity = @$_POST["ccity"];
	$cnotes = @$_POST["cnotes"];
	$cgsm = @$_POST["cgsm"];
	$cgsm2 = @$_POST["cgsm2"];
	$yetkiliadi = @$_POST["yetkili"];
	$sunvan = @$_POST["sunvan"];
	$vdaire = @$_POST["vdaire"];
	$vno = @$_POST["vno"];
	$cpass = "abccc";
	$grra = @$_POST["grp"];

	$ahce = $ac->prepare("UPDATE customers SET
	grp = ?,
    name = ?,
    email = ?,
    company = ?,
    sector = ?,
    address = ?,
    city = ?,
    cdesc = ?,
    gsm = ?,
    gsm2 = ?,
    yetkili = ?,
    sunvan = ?,
    vdaire = ?,
    vno = ? WHERE id = ?");

	$ahce->execute(array($grra, $cname, $cemail, $ccompany, $csector, $caddress, $ccity, $cnotes, $cgsm, $cgsm2, $yetkiliadi, $sunvan, $vdaire, $vno, $cid));

	if ($cpass) {

		$sifre = md5(md5(md5($cpass)));
		$upcus = $ac->prepare("UPDATE customers SET password = ? WHERE id = ?");
		$upcus->execute(array($sifre, $cid));

		$upcus = $ac->prepare("UPDATE users SET password = ? WHERE cid = ?");
		$upcus->execute(array($sifre, $cid));
	}


	if ($ahce) {


		header("Location:index.php?p=edit-customer&cid=$cid&st=newsuccess");
	} else {
		//header("Location: index.php?p=all-customers&st=newerror&code=acmd008");
	}
}


if (@$_GET["st"] == "empties") {
?>
	<div class="alert alert-danger" role="alert">
		(*) ile işaretli alanları boş bırakmadan tekrar deneyin.
	</div>
<?php
}
if (@$_GET["st"] == "newsuccess") {



?>
	<div class="alert alert-success" role="alert">
		Bilgiler kaydedildi.
	</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "name") {
?>
	<div class="alert alert-warning" role="alert">
		Aynı adda bir dosya bulunuyor, lütfen ismini değiştirerek projeyi tekrar oluşturmayı deneyin.
	</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "size") {
?>
	<div class="alert alert-warning" role="alert">
		Yüklediğiniz dosyaın boyutu <b>3 MB</b>'dan daha büyük olamaz. Proje oluşturulamadı, tekrar deneyin.
	</div>
<?php
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "erno") {
?>
	<div class="alert alert-danger" role="alert">
		Proje oluşturuldu ancak, dosya yüklenirken bir problem yaşandı.
	</div>
<?php
}


?>
<!-- Default Basic Forms Start -->
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<!-- <h6> <span style="font-weight: bold; color:brown">Toplam Proje Sayısı:</span>
			<font color="" style="font-size: 18px; font-weight: bold"> <?php echo $pjs; ?></font><br>
			<span style="font-weight: bold; color:brown">Toplam Teklif Sayısı:</span>
			<font color="" style="font-size: 18px; font-weight: bold"> <?php echo $ojs; ?></font><br><br>
			<span style="font-weight: bold; color:brown">Son Oluşturulan Teklif:</span><a href="index.php?p=offer&oid=<?php echo $ojsx["id"]; ?>">
				<font color="" style="font-size: 16px; font-weight: bold"> <u>#<?php echo $ojsx["id"]; ?></font>
			</a></u><br>
			<span style="font-weight: bold; color:brown">Son Oluşturulan Proje:</span><a href="index.php?p=edit-project&pid=<?php echo $ojsp["id"]; ?>">
				<font color="" style="font-size: 16px; font-weight: bold"> <u><?php echo $ojsp["ptitle"]; ?></font>
			</a></u><br>

		</h6><br> -->
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4><br>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>
		<div class="form-group">
			
			<a href="index.php?p=view-sales-c&id=<?php echo $cc["id"]; ?>"><button class="btn btn-success" style="float:right">Ödemeleri Görüntüle</button></a>
			<input type="submit" id="submitButton" value="Kaydet" class="float-right mr-2 btn btn-primary">
		</div>




	</div>


	<div class="row">
		<div class="col-lg-3 col-md-6 col-sm-12 mb-30">
			<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
				<div class="project-info clearfix">
					<div class="project-info-left">
						<div class="icon box-shadow bg-blue text-white">
							<i class="fa fa-briefcase"></i>
						</div>
					</div>
					<div class="project-info-right">
						<span class="no text-blue weight-500 font-24"><?php echo $pjs; ?></span>
						<p class="weight-400 font-18">Toplam Proje Sayısı</p>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-12 mb-30">
			<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
				<div class="project-info clearfix">
					<div class="project-info-left">
						<div class="icon box-shadow bg-light-green text-white">
							<i class="fa fa-handshake-o"></i>
						</div>
					</div>
					<div class="project-info-right">
						<span class="no text-blue weight-500 font-24"><?php echo $ojs; ?></span>
						<p class="weight-400 font-18">Toplam Teklif Sayısı:</p>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-12 mb-30">
			<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
				<div class="project-info clearfix">
					<div class="project-info-left">
						<div class="icon box-shadow bg-blue text-white">
							<i class="fa fa-briefcase"></i>
						</div>
					</div>
					<div class="project-info-right">
						<span class="no text-blue weight-500 font-24"><?php echo $ojsp["ptitle"]; ?></span>
						<p class="weight-400 font-18">Son Oluşturulan Teklif</p>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-12 mb-30">
			<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
				<div class="project-info clearfix">
					<div class="project-info-left">
						<div class="icon box-shadow bg-light-green text-white">
							<i class="fa fa-handshake-o"></i>
						</div>
					</div>
					<div class="project-info-right">
						<span class="no text-blue weight-500 font-24"><?php echo $ojs; ?></span>
						<p class="weight-400 font-18">Son Oluşturulan Proje</p>
					</div>
				</div>
			</div>
		</div>
	</div>



	<form enctype="multipart/form-data" action="" method="POST">
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font> Ad-Soyad:
			</label>
			<div class="col-sm-12 col-md-10"><input required name="cname" value="<?php echo $cc["name"]; ?>" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font> Grup:
			</label>
			<div class="col-sm-12 col-md-10">
				<select name="grp" class="form-control">
					<?php $cek = $ac->prepare("SELECT * FROM cgroups WHERE statu = ?");
					$cek->execute(array(1));
					while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
					?>
						<option <?php echo $dat["id"] == $cc["grp"] ? "selected" : ""; ?> value="<?php echo $dat["id"]; ?>"><?php echo $dat["title"]; ?></option>
					<?php
					}
					?>
				</select>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">E-Posta:</label>
			<div class="col-sm-12 col-md-10"><input name="cemail" value="<?php echo $cc["email"]; ?>" type="text" class="form-control">
			</div>
		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Şirket İsmi:</label>
			<div class="col-sm-12 col-md-10"><input name="ccompany" value="<?php echo $cc["company"]; ?>" type="text" class="form-control">
			</div>
		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Sektör:</label>
			<div class="col-sm-12 col-md-10"><input name="csector" value="<?php echo $cc["sector"]; ?>" type="text" class="form-control">
			</div>
		</div>



		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Adres:</label>
			<div class="col-sm-12 col-md-10"><input name="caddress" value="<?php echo $cc["address"]; ?>" type="text" class="form-control">
			</div>
		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Şehir:</label>
			<div class="col-sm-12 col-md-10"><input name="ccity" value="<?php echo $cc["city"]; ?>" type="text" class="form-control">
			</div>
		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Telefon:</label>
			<div class="col-sm-12 col-md-10"><input placeholder="05XXXXXXXXX" maxlength="11" minlength="11" name="cgsm" value="<?php echo $cc["gsm"]; ?>" type="text" class="form-control">
			</div>
		</div>

		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label"> Telefon 2:</label>
			<div class="col-sm-12 col-md-10"><input name="cgsm2" value="<?php echo $cc["gsm2"]; ?>" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label"> Yetkili Ad-Soyad:</label>
			<div class="col-sm-12 col-md-10"><input name="yetkili" value="<?php echo $cc["yetkili"]; ?>" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label"> Şirket Unvanı:</label>
			<div class="col-sm-12 col-md-10"><input value="<?php echo $cc["sunvan"]; ?>" name="sunvan" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label"> Vergi Dairesi:</label>
			<div class="col-sm-12 col-md-10"><input value="<?php echo $cc["vdaire"]; ?>" name="vdaire" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label"> Vergi No:</label>
			<div class="col-sm-12 col-md-10"><input value="<?php echo $cc["vno"]; ?>" name="vno" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group">
			<label>
				<font color="red"></font>Notlar
			</label>
			<textarea name="cnotes" value="" placeholder="Müşteri hakkında yöneticilerin görebileceği bir not ekleyebilirsiniz." class="form-control"><?php echo $cc["cdesc"]; ?></textarea>
		</div>
		<div class="form-group">
			<input type="submit" value="Müşteriyi Kaydet" class="form-control btn-outline-success">
		</div>

</div>





</form>



</div>
</div>
</div>
<!-- Input Validation End -->

</div>
<?php include('include/footer.php'); ?>