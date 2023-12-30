 <?php
	permcontrol("misadd");
	if ($_POST) {

		$userstring="";

		$FirmaAdi = @$_POST["FirmaAdi"];
		$categoryName = @$_POST["categoryName"];
		$title = @$_POST["title"];
		$mdesc = @$_POST["mdesc"];
		$startdate = @$_POST["startdate"] ? date_tr($_POST["startdate"]) : TODAY;
		$lastdate = @$_POST["lastdate"];
		$urg = @$_POST["urg"];
		$statu = @$_POST["statu"];

		foreach ($_POST["permings"] as $autx) {

		$userstring .= $autx . "|" ;
		}

		$insq = $ac->prepare("INSERT INTO missions SET 
								FirmaAdi = ? , categoryName = ? , title = ? , 
								mdesc = ? ,	regdate = ? , startdate = ? , 
								lastdate = ? , authors = ? , creativer = ? , 
								urgency = ? , okeydate = ? , statu = ? ");
		
		$insq->execute(array($FirmaAdi,$categoryName,$title,
								$mdesc,TODAY,$startdate,
								date_tr($lastdate),$userstring,sesset("id"),
								$urg,"-",0));




		// $title = @$_POST["title"];
		// $desc = @$_POST["desc"];
		// $sdate = @$_POST["startdate"] ? date_tr($_POST["startdate"]) : TODAY;
		// $lastdate = date_tr(@$_POST["lastdate"]);
		// $urg = $_POST["urg"];
		// $cat = $_POST["cat"];


		

			// $insq = $ac->prepare("INSERT INTO missions SET
			// 									title = ?,
			// 									mdesc = ?,
			// 									regdate = ?,
			// 									lastdate = ?,
			// 									authors = ?,
			// 									creativer = ?,
			// 									urgency = ?,
			// 									okeydate = ?,
			// 									statu = ?");

			// $insq->execute(array(@$_POST["title"], @$_POST["desc"], date_tr(@$_POST["startdate"]), date_tr(@$_POST["lastdate"]), $autx, sesset("id"), $urg, "-", 0));
		






		if ($insq) {
			header("Location: index.php?p=new-mission");
		} else {
		}
		// header("Location: index.php?p=new-mission&st=newsuccess");
	}

	if (@$_GET["st"] == "empties") {
		showAlert('alert', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.');
	}
	if (@$_GET["st"] == "newsuccess") {
		showAlert('success', 'Görev oluşturuldu.');
	}
	?>

 <div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
 	<div class="clearfix">
 		<div class="pull-left">
 			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
 			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş bırakmayın..<br></p>
 		</div>

 		<input type="submit" value="Kaydet" id="submitButton" onclick="validateForm()" class="float-right btn btn-primary"><br><br>

 	</div>
 	<form action="" method="POST" id="myForm">

 		<div class="form-group row">
 			<label for="title" class="col-md-2">
 				<font color="red">(*)</font>Firma
 			</label>
 			<div class="input-group col-md-4">
 				<input type="text" class="form-control" id="FirmaAdi" placeholder="Firma seçiniz veya yazınız!">

 				<div class="chooseitem">
 					<!-- Button trigger modal -->
 					<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
 						<i class="fa fa-hand-o-up"></i>
 					</button>

 					<!-- Modal -->
 					<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
 						<div class="modal-dialog">
 							<div class="modal-content">
 								<div class="modal-header">
 									<h5 class="modal-title" id="exampleModalLabel">Firma Seç</h5>
 									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 								</div>
 								<div class="modal-body">
 									<<select id="FirmaSec" name="FirmaSec" class="form-control">
 										<?php $cek = $ac->prepare("SELECT * FROM customers");
											$cek->execute();
											while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
											?>
 											<option value="<?php echo $dat["name"]; ?>"><?php echo $dat["name"]; ?> </option>
 										<?php
											}
											?>
 										</select>
 								</div>
 								<div class="modal-footer">
 									<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
 									<button type="button" id="ModalSaveButton" onclick="Sec()" data-bs-dismiss="modal" class="btn btn-primary">Seç</button>
 								</div>
 							</div>
 						</div>
 					</div>
 					<!-- Modal -->

 				</div>
 			</div>
 			<label for="title" class="col-md-2">
 				<font color="red">(*)</font>Kategori
 			</label>
 			<div class="input-group col-md-4">
 				<select name="categoryName" id="categoryName" value="" class="form-control" required>
 					<?php
						$sql = $ac->prepare("SELECT * FROM `missioncategory` where NOT categoryName IS NULL");
						$sql->execute();
						$categories = $sql->fetchAll(PDO::FETCH_ASSOC);
						foreach ($categories as $category) { ?>
 						<option value="<?php echo $category["id"]; ?>"><?php echo $category["categoryName"]; ?></option>
 					<?php } ?>

 				</select>




 				<div class="chooseitem">
 					<!-- Button trigger modal -->
 					<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal2">
 						<i class="fa fa-plus-circle"></i>
 					</button>

 					<!-- Modal -->
 					<div class="modal fade" id="exampleModal2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
 						<div class="modal-dialog">
 							<div class="modal-content">
 								<div class="modal-header">
 									<h5 class="modal-title" id="exampleModalLabel">Kategori Adı:</h5>
 									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 								</div>
 								<div class="modal-body">
 									<input type="text" class="form-control" name="Addcategory" id="Addcategory" placeholder="Eklenecek kategori adını yazınız...">
 								</div>
 								<div class="modal-footer">
 									<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>

 									<button type="button" id="ModalSaveButton" onclick="SaveNewKategory()" data-bs-dismiss="modal" class="btn btn-primary">Kaydet</button>
 								</div>
 							</div>
 						</div>
 					</div>
 					<!-- Modal -->

 				</div>
 			</div>

 		</div>
 		<div class="form-group row">
 			<label for="title" class="col-md-2">
 				<font color="red">(*)</font>Konu :
 			</label>
 			<div class="col-md-4">
 				<input name="title" value="" class="form-control" required type="text">
 			</div>

 			<label for="title" class="col-md-2">
 				<font color="red">(*)</font>Görevi Oluşturan
 			</label>
 			<div class="col-md-4">
 				<select disabled name="Olusturan" value="" class="form-control" required>
 					<option selected value="1">Admin</option>
 				</select>
 			</div>

 		</div>

 		<div class="form-group row">
 			<label class="col-md-2">Başlangıç Tarihi</label>
 			<div class="col-md-4">
 				<input name="startdate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">
 			</div>


 			<label class="col-md-2" for="lastdate">Son Tarih</label>
 			<div class="col-md-4">
 				<input name="lastdate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">

 			</div>


 		</div>


 		<div class="form-group row">
 			<label for="permings[]" class="col-md-2">Görevin Atanacağı Kullanıcılar:</label>
 			<div class="col-md-4">
 				<select required name="permings[]" class="selectpicker form-control" data-style="btn-outline-secondary" multiple data-actions-box="true" data-selected-text-format="count">
 					<?php
						$permq = $ac->prepare("SELECT * FROM perms ");
						$permq->execute();
						while ($pp = $permq->fetch(PDO::FETCH_ASSOC)) {
							if ($pp["mistake"] == "on") {
						?>
 							<optgroup label="<?php echo $pp["p_title"]; ?>">
 								<?php
									$permx = $ac->prepare("SELECT * FROM users WHERE permission = ? ");
									$permx->execute(array($pp["id"]));
									while ($px = $permx->fetch(PDO::FETCH_ASSOC)) { ?>
 									<option value="<?php echo $px["id"]; ?>"><?php echo $px["username"]; ?></option>
 								<?php }
									?>
 							</optgroup>
 					<?php }
						} ?>
 				</select>
 			</div>


 			<label class="col-md-2" for="lastdate">Aciliyet</label>
 			<div class="col-md-4">
 				<div class="custom-control custom-radio custom-control-inline">
 					<input type="radio" id="customRadioInline1" name="urg" value="Yüksek" class="custom-control-input">
 					<label class="custom-control-label font-weight-bold" for="customRadioInline1">
 						<font color="red">Yüksek</font>
 					</label>
 				</div>
 				<div class="custom-control custom-radio custom-control-inline">
 					<input type="radio" id="customRadioInline2" checked name="urg" value="Orta" class="custom-control-input">
 					<label class="custom-control-label font-weight-bold" for="customRadioInline2">
 						<font color="blue">Orta</font>
 					</label>
 				</div>
 				<div class="custom-control custom-radio custom-control-inline">
 					<input type="radio" id="customRadioInline3" name="urg" value="Düşük" class="custom-control-input">
 					<label class="custom-control-label font-weight-bold" for="customRadioInline3">
 						<font color="green">Düşük</font>
 					</label>
 				</div>
 			</div>


 		</div>



 		<div class="row">
 			<div class="col-md-12 col-sm-12">
 				<div class="html-editor">
 					<h3 class="weight-500">Görev Açıklaması</h3>
 					<p></p>
 					<textarea name="mdesc" class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ..."></textarea><br>
 				</div>
 			</div>
 		</div>
 		<br>
 	</form>




 </div>
 <script>
 	function Sec() {
 		// Seçilen değeri al
 		var selectedValue = document.getElementById('FirmaSec').value;

 		// Firma ID'li input alanına ata
 		document.getElementById('FirmaAdi').value = selectedValue;

 	}



 	function SaveNewKategory() {
 		var Addcategory = document.getElementById('Addcategory').value; 

 		fetch('index.php?p=categoryAdd', {
 				method: 'POST',
 				headers: {
 					'Content-Type': 'application/x-www-form-urlencoded',
 				},
 				body: 'Addcategory=' + encodeURIComponent(Addcategory),
 			})
 			.then(response => {
 				var selectElement = document.getElementById('categoryName');
 				var newOption = document.createElement('option');
 				newOption.value = Addcategory;
 				newOption.textContent = Addcategory;
 				selectElement.appendChild(newOption);
 			})
 			.catch(error => {
 				// Hata durumunda burada işlemler yapabilirsiniz
 			});
 	}
 </script>
 <?php include('include/footer.php'); ?>