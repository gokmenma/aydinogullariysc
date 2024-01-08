<?php
permcontrol("docinadd");
if ($_POST) {
    $firma = $_POST["firma"];
    $evrakturu = $_POST["evrakturu"];
    $kategori = $_POST["kategori"];
    $adet = $_POST["adet"];
    $teslimalan = sesset("id"); 
    $teslimeden = $_POST["teslimeden"];
    $aciklama = $_POST["aciklama"];

    if (@$_POST["teslimtarihi"]) {
		$teslimtarihi = $_POST["teslimtarihi"];
	} else {
		$teslimtarihi = TODAY;
	}

    try {
        $kyt = $ac->prepare("INSERT INTO evraktakip SET
            firma = ?,
            evrakturu = ?,
            kategori = ?,
            adet = ?,
            teslimalan = ?,
            teslimeden = ?,
            teslimtarihi = ?,
            aciklama = ?");
        $kyt->execute(array($firma, $evrakturu, $kategori, $adet, $teslimalan, $teslimeden, $teslimtarihi, $aciklama));

        if ($kyt->rowCount() > 0) {
            header("Location: index.php?p=new-indocument&st=newsuccess");
        } else {
            header("Location: index.php?p=new-indocument&st=newerror&code=acmd008");
        }
    } catch (PDOException $e) {
        echo "Veri kaydetme hatası: " . $e->getMessage();
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
    showAlert("success", "İşlem Başarı ile tamamlandı!");
}
?>


<form  method="POST" action="index.php?p=new-indocument">
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
	<div class="clearfix">
		<div class="pull-left">
			<h4 class="text-blue"><?php echo $pdat["p_title"]; ?></h4>
			<p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
				bırakmayın..<br></p>
		</div>
		<input type="submit" id="submitbuton"  value="Kaydet" class="float-right btn btn-primary mr-2">
		
	</div>
	

		<div class="row">
        
        <div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font>Firma
					</label>
					<input required name="firma"  placeholder="Firma adını giriniz" class="form-control" type="text">

				</div>
			</div>

			<div class="col-md-6 col-sm-12">
				<div class="form-group"><label>
						<font color="red">(*)</font>Evrak Türü
					</label>
					<select required name="evrakturu" class="custom-select col-12">
						<option disabled selected>Seçim Yapınız</option>	
                        <option value="Giden">Giden Evrak</option>
                        <option value="Gelen">Gelen Evrak</option>
					</select>
				</div>
			</div>
			<div class="col-md-6 col-sm-12">
				<div class="form-group">
					<label>
						<font color="red">(*)</font>Kategori
					</label>
					<input required name="kategori"  placeholder="Yapılan İşlem Türünü Seçiniz" class="form-control" type="text">
				</div>
			</div>

			<div class="col-md-6 col-sm-12">
				<div class="form-group"><label><font color="red">(*)</font>Adet</label>
                <input required name="adet"  placeholder="Evrak sayısını girin" class="form-control" type="number">
				</div>
			</div>
            <div class="col-md-4 col-sm-12">
				<div class="form-group"><label>
						<font color="red">(*)</font>Teslim Alan
					</label>
					<input disabled class="form-control" value="<?php echo sesset("username"); ?>" type="text">
				</div>
			</div>
            <div class="col-md-4 col-sm-12">
				<div class="form-group"><label>
						<font color="red">(*)</font>Teslim Eden
					</label>
					<select name="teslimeden" title="Seçiniz" class="selectpicker form-control" data-style="btn-outline-secondary"  data-selected-text-format="count">
							<?php
							$permq = $ac->prepare("SELECT * FROM perms ");
							$permq->execute();
							while ($pp = $permq->fetch(PDO::FETCH_ASSOC)) {
							?>
								<optgroup label="<?php echo $pp["p_title"]; ?>">
									<?php
									$permx = $ac->prepare("SELECT * FROM users WHERE permission = ? ");
									$permx->execute(array($pp["id"]));
									while ($px = $permx->fetch(PDO::FETCH_ASSOC)) { ?>
										<option value="<?php echo $px["id"]; ?>"><?php echo $px["username"]; ?></option>
									<?php } ?>
								</optgroup>
							<?php } ?>
						</select>
				</div>
			</div>

            <div class="col-md-4 col-sm-12">
				<div class="form-group"><label>Teslim Alma Tarihi</label>
                <input name="teslimtarihi"	 type="text"  placeholder="Boş bırakırsanız otomatik bugün seçilir." class="custom-select col-12 date-picker" >
				</div>
			</div>

			<div class="col-md-12 col-sm-6">
				<div class="form-group">
					<label>Açıklama </label>
					<textarea name="aciklama"  class="form-control" type="text" placeholder="Evrakla ilgili açıklama girebilirsiniz"></textarea>
				</div>
				
			</div>
			
		</div>
		
	</form>
</div>