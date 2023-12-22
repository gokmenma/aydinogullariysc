  <?php
permcontrol("mistake");
if(!@$_GET["mid"]){
	header("Location: index.php?p=home&errorcode=00254");
	exit;
}

$quer = $ac->prepare("SELECT * FROM missions WHERE id = ?");
$quer->execute(array($_GET["mid"]));
$as = $quer->fetch(PDO::FETCH_ASSOC);
if(!$as){
	header("Location: index.php?p=home&errorcode=00784");
	exit;
}
if($as["authors"] != sesset("id") && permfalse("allmisview") && $as["creativer"] != sesset("id")){
	header("Location: index.php");
	exit;

}
if(@$_GET["mode"] == "update"){
	$upx = $ac->prepare("UPDATE missions SET okeydate = ?, statu = ? WHERE id = ?");
	$upx->execute(array(TODAY." ".date("H:i:s"),1,$_GET["mid"]));

	header("Location:index.php?p=my-missions&update=true&mid=".$as["id"]);
}
?>


<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
					<div class="clearfix">
						<div class="pull-left">
							<h4 class="text-blue"><?php echo $pdat["p_title"];?></h4>
							<p class="mb-30 font-14">Tamamladığınız görevleri "yapıldı" işaretlemeyi unutmayın..<br></p>
						</div>
						


						<?php if($as["statu"] == 0){ ?><a OnClick="return confirm('Görevi yapıldı işaretlemeniz durumunda, bu işlemi geri alamazsınız.')" href="index.php?p=view-mission&mode=update&statu=1&code=04md177&reg=true&md=active&mid=<?php echo $as["id"];?>"><button style="float:right;" type="button" class="btn btn-success">Yapıldı İşaretle</button></a> <?php } ?>
						
					</div>
	
	<div class="row">
		<div class="col-md-12 col-sm-12"><?php if($as["statu"] == 1){ ?><button style="float:left;" type="button" class="btn btn-success">Görev Tamamlanmış</button><?php }
		?>
			<div class="col-md-12 col-sm-12"><?php if($as["statu"] == 0){ ?><button style="float:left;" type="button" class="btn btn-warning">Görev Henüz Tamamlanmamış</button>
		<?php
		} ?><br><br><br>
			<div class="form-group"> 
				<label>Başlık</label>
				<input disabled name="title" value="<?php echo $as["title"]; ?>" class="form-control" type="text" >
				
			
			<br><br>
			<div class="pd-20 bg-white border-radius-10 box-shadow mb-30">
					<h3 class="weight-500">Görev Bilgileri</h3><br>
					<font color="red">Görev Açıklaması:</font> <?php echo $as["mdesc"]; ?><br><br><br><b>Görev Kayıt Tarihi:</b> <?php echo $as["reg_date"]; ?><br><b>Görev Başlangıç Tarihi:</b> <?php echo $as["regdate"]; ?><br><b>Görev Sonlanma Tarihi:</b> <?php echo $as["lastdate"]; ?><br><b>Görevi Oluşturan:</b> <?php echo uset($as["creativer"], "username"); ?><br><b>Görev Alan Kişi: <?php echo uset($as["authors"], "username"); ?></b><br><br> <?php echo $as["statu"] == 1 ? "Görev Tamamlanma Tarihi:" : ""; ?> <?php echo $as["okeydate"]; ?>
				</div>
		</div>
		
		


	</div><br>
	

							</div> 												