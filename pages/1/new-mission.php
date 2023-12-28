 <?php
	permcontrol("misadd");
	if ($_POST) {

		$title = @$_POST["title"];
		$desc = @$_POST["desc"];
		$sdate = @$_POST["startdate"] ? date_tr($_POST["startdate"]) : TODAY;
		$lastdate = date_tr(@$_POST["lastdate"]);
		$urg = $_POST["urg"];
		$cat = $_POST["cat"];


		foreach ($_POST["permings"] as $autx) {

			$insq = $ac->prepare("INSERT INTO missions SET
												title = ?,
												mdesc = ?,
												regdate = ?,
												lastdate = ?,
												authors = ?,
												creativer = ?,
												urgency = ?,
												okeydate = ?,
												statu = ?");

			$insq->execute(array(@$_POST["title"], @$_POST["desc"], date_tr(@$_POST["startdate"]), date_tr(@$_POST["lastdate"]), $autx, sesset("id"), $urg, "-", 0));
		}


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
 		<div class="form-group">
 			<button type="submit" id="submitButton" onclick="validateForm()" value="Kaydet" class="btn btn-primary float-lg-right ">Kaydet</button<>
 		</div>
 		<!-- <input type="submit" value="Kaydet" id="submitButton" class="float-right btn btn-primary"><br><br> -->

 	</div>
 	<form action="" method="POST" id="myForm">

 		<div class="row">
 			<div class="col-md-12 col-sm-12">
 				<div class="form-group">
 					<label for="title">
 						<font color="red">(*)</font>Başlık
 					</label>
 					<input name="title" value="" class="form-control" required type="text">

 				</div>
 			</div>
 			<div class="col-md-6 col-sm-12">
 				<div class="form-group">
 					<label>Başlangıç Tarihi</label>
 					<input name="startdate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">
 				</div>
 			</div>

 			<div class="col-md-6 col-sm-12">
 				<div class="form-group">

 					<label for="lastdate">Son Tarih</label>
 					<input name="lastdate" class="form-control date-picker" value="" placeholder="Tarih Seçin" type="text">
 				</div>
 			</div>


 			<div class="col-md-6 col-sm-12">
 				<div class="form-group">
 					<font color="red">(*)</font>
 					<label for="permings[]">Görevin Atanacağı Kullanıcılar:</label>
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

 			</div>
 			<div class="col-sm-12 col-md-6">
 				<font color="red">(*)</font><label class="weight-800">Aciliyet</label>
 				<div class="col-sm-12 col-md-12">
 					<div class="custom-control custom-radio custom-control-inline">
 						<input type="radio" id="customRadioInline1" name="urg" value="Yüksek" class="custom-control-input">
 						<label class="custom-control-label font-weight-bold" for="customRadioInline1">
 							<font color="red">Yüksek</font>
 						</label>
 					</div>
 					<div class="custom-control custom-radio custom-control-inline">
 						<input type="radio" id="customRadioInline2" name="urg" value="Orta" class="custom-control-input">
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

 			<div class="col-md-12 col-sm-12">
 				<div class="html-editor">
 					<h3 class="weight-500">Görev Açıklaması</h3>
 					<p></p>
 					<textarea name="desc" class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ..."></textarea><br>
 				</div>
 			</div>
 		</div><br>
 		<!-- <input type="submit" value="Değişiklikleri Kaydet" style="float:right" class="col-md-6 form-control btn-outline-success"><br><br> -->
 	</form>
 </div>


 <?php include('include/footer.php'); ?>