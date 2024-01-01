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
	$grra = @$_POST["categoryName"];

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

	// if ($cpass) {

	// 	$sifre = md5(md5(md5($cpass)));
	// 	$upcus = $ac->prepare("UPDATE customers SET password = ? WHERE id = ?");
	// 	$upcus->execute(array($sifre, $cid));

	// 	$upcus = $ac->prepare("UPDATE users SET password = ? WHERE cid = ?");
	// 	$upcus->execute(array($sifre, $cid));
	// }


	if ($ahce) {
		header("Location:index.php?p=edit-customer&cid=$cid&st=newsuccess");
	} else {
	}
}

//Uyarı mesajları
if (@$_GET["st"] == "empties") {
	showAlert("alert", "(*) ile işaretli alanları boş bırakmadan tekrar deneyin.");
}

if (@$_GET["st"] == "newsuccess") {
	showAlert("success", "İşlem Başarı ile tamamlandı!");
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "name") {
	showAlert("alert", "Aynı adda bir dosya bulunuyor, lütfen ismini değiştirerek projeyi tekrar oluşturmayı deneyin.");
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "size") {
	showAlert("alert", "Yüklediğiniz dosyaın boyutu <b>3 MB</b>'dan daha büyük olamaz. Proje oluşturulamadı, tekrar deneyin.");
} elseif (@$_GET["err"] == "upload" && @$_GET["errorbec"] == "erno") {
	showAlert("alert", "Proje oluşturuldu ancak, dosya yüklenirken bir problem yaşandı.");
}
?>


<!-- Default Basic Forms Start -->
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix ">

		<div class="pull-left">
			<div class="d-flex">
				<a href="index.php?p=customer-list">
					<button class="btn-mini btn-info" data-toggle="tooltip" data-placement="top" title="Listeye Geri Dön">
						<i class="fa fa-arrow-left"></i>
					</button>
				</a>
				<h4 class="text-blue ml-2"><?php echo $pdat["p_title"]; ?></h4><br>
			</div>

			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
		</div>


		<a href="index.php?p=view-sales-c&id=<?php echo $cc["id"]; ?>"><button class="btn btn-success" style="float:right">Ödemeleri Görüntüle</button></a>
		<input type="submit" id="submitButton" onclick="validateForm()" value="Kaydet" class="float-right mr-2 btn btn-primary">

	</div>

	<div class="row">
		<div class="col-lg-3 col-md-6 col-sm-12 mb-30">
			<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
				<div class="project-info">
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
				<div class="project-info">
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
						<div class="icon box-shadow bg-danger text-white">
							<i class="fa fa-truck"></i>
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
						<div class="icon box-shadow bg-light-orange text-white">
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



	<form enctype="multipart/form-data" id="myForm" action="" method="POST">
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font> Ad-Soyad:
			</label>
			<div class="col-sm-12 col-md-4"><input required name="cname" value="<?php echo $cc["name"]; ?>" type="text" class="form-control">
			</div>

			<label class="col-sm-12 col-md-2 col-form-label">E-Posta:</label>
			<div class="col-sm-12 col-md-4"><input name="cemail" value="<?php echo $cc["email"]; ?>" type="text" class="form-control">
			</div>
		</div>

		<div class="form-group row">
			<label for="categoryName" class="col-sm-12 col-md-2 col-form-label">
				<font color="red">(*)</font> Grup:
			</label>
			<div class="input-group col-md-4">
				<select required name="categoryName" id="categoryName" class="form-control">
					<?php $cek = $ac->prepare("SELECT * FROM cgroups WHERE statu = ?");
					$cek->execute(array(1));
					while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
					?>
						<option <?php echo $dat["id"] == $cc["grp"] ? "selected" : ""; ?> value="<?php echo $dat["id"]; ?>"><?php echo $dat["title"]; ?></option>
					<?php
					}
					?>
					?>
				</select>

				<?php if (permtrue("cedit")) { ?>
					<div class="chooseitem">
						<!-- Button trigger modal -->

						<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exampleModal2">
							<i class="fa fa-plus-circle"></i>
						</button>




						<!-- Modal -->
						<div class="modal fade" id="exampleModal2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title" id="exampleModalLabel">Kategori Adı:</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body m-2">
										<input type="text" class="form-control" value="" name="Addcategory" id="Addcategory" placeholder="Eklenecek kategori adını yazınız...">
									</div>
									<div class="modal-footer mb-2">
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>

										<button type="button" id="ModalSaveButton" onclick="SaveNewKategory('customer-groups','categoryName')" data-bs-dismiss="modal" class="btn btn-primary">Kaydet</button>
									</div>
								</div>
							</div>
						</div>
						<!-- Modal -->
					</div>
				<?php  } ?>
			</div>

			<label class="col-sm-12 col-md-2 col-form-label">Sektör:</label>
			<div class="col-sm-12 col-md-4"><input name="csector" value="<?php echo $cc["sector"]; ?>" type="text" class="form-control">
			</div>
		</div>



		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label">Şirket İsmi:</label>
			<div class="col-sm-12 col-md-10"><input name="ccompany" value="<?php echo $cc["company"]; ?>" type="text" class="form-control">
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
			<div class="col-sm-12 col-md-4"><input placeholder="05XXXXXXXXX" maxlength="11" minlength="11" name="cgsm" value="<?php echo $cc["gsm"]; ?>" type="text" class="form-control">
			</div>

			<label class="col-sm-12 col-md-2 col-form-label"> Telefon 2:</label>
			<div class="col-sm-12 col-md-4"><input name="cgsm2" value="<?php echo $cc["gsm2"]; ?>" type="text" class="form-control">
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
			<div class="col-sm-12 col-md-4"><input value="<?php echo $cc["vdaire"]; ?>" name="vdaire" type="text" class="form-control">
			</div>

			<label class="col-sm-12 col-md-2 col-form-label"> Vergi No:</label>
			<div class="col-sm-12 col-md-4"><input value="<?php echo $cc["vno"]; ?>" name="vno" type="text" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-12 col-md-2 col-form-label"> Notlar :</label>
			<div class="col-sm-12 col-md-10">
				<textarea name="cnotes" value="" placeholder="Müşteri hakkında yöneticilerin görebileceği bir not ekleyebilirsiniz." class="form-control"><?php echo $cc["cdesc"]; ?></textarea>
			</div>
		</div>



	</form>


	<!-- Input Validation End -->

</div>