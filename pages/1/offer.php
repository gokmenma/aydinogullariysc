
<?php
permcontrol("oview");

	if(!@$_GET["oid"]){
		
			header("Location: index.php?p=all-offers");
		exit;
		die;

	}else{

			$spick = $ac->prepare("SELECT * FROM offers WHERE id = ?");
			 	$spick->execute(array($_GET["oid"]));
			 	$serry = $spick->fetch(PDO::FETCH_ASSOC);
			if(!$serry){
				header("Location: index.php?p=all-offers&errno=true&code=1452MD&AC31=success");
			}

			if($serry["currency"] == "tl"){
				$pr = "₺";
			}elseif($serry["currency"] == "euro"){
				$pr = "€";
			}elseif($serry["currency"] == "dollar"){
				$pr = "$";
			}
			$oids = $_GET["oid"];
	if(@$_GET["update"] == "true" AND permtrue("oedit")){
		if(@$_GET["stat"] || @$_GET["stat"] == 0){


			 $st = $_GET["stat"];


				 $scont = $serry["statu"];
			 if($st == 1){

			 	if($scont == 0){

			 		$upones = $ac->prepare("UPDATE offers SET statu = ? WHERE id = ?");
			 		$upones->execute(array(1,$oids));

			 		if($upones){
			 			header("Location: index.php?p=offer&oid=$oids&success=true&code=114");
			 		}else{
			 			header("Location: index.php?p=errormd");
			 		}
			 	}else{
			 		header("Location: index.php?p=offer&oid=$oids&errcd=MD012&update=none");
			 	}


			 }else if($st == 2){

			 	if($scont == 1){

			 		$upone = $ac->prepare("UPDATE offers SET statu = ? WHERE id = ?");
			 		$upone->execute(array(2,$oids));

			 		if($upone){
			 			header("Location: index.php?p=offer&oid=$oids&success=true&code=114");
			 		}else{
			 			header("Location: index.php?p=errormd");
			 		}

			 	}else{
			 		header("Location: index.php?p=offer&oid=$oids&errcd=MD012&update=none");
			 	}

			 }else if($st == 4){

			 	if($scont == 1){

			 		$upone = $ac->prepare("UPDATE offers SET statu = ? WHERE id = ?");
			 		$upone->execute(array(4,$oids));

			 		if($upone){
			 			header("Location: index.php?p=offer&oid=$oids&success=true&code=114");
			 		}else{
			 			header("Location: index.php?p=errormd");
			 		}

			 	}else{
			 		header("Location: index.php?p=offer&oid=$oids&errcd=MD012&update=none");
			 	}

			 }else if($st == 10){

			 		

			 		if($deleteone){

			 			header("Location:index.php?p=all-offers&type=delete&oid=$oids");

			 		}else{
			 			//header("Location: index.php?p=errormd");
			 		}
///
			 		
			

			 }else if($st == 0){
			 	if($scont == 1 || $scont == 4 || $scont == 2){

			 		$upone = $ac->prepare("UPDATE offers SET statu = ? WHERE id = ?");
			 		$upone->execute(array(0,$oids));

			 		if($upone){
			 			header("Location: index.php?p=offer&oid=$oids&success=true&code=114");
			 		}else{
			 			header("Location: index.php?p=errormd");
			 		}

			 	}else{
			 		header("Location: index.php?p=offer&oid=$oids&errcd=MD012&update=none");
			 	}
			 }

		}else{
			header("Location: index.php?p=all-offers&oidsxx=none");
		}


		
	}
	
	

	$oqr = $ac->prepare("SELECT * FROM offers WHERE id = ?");
	$oqr->execute(array($oids));
	$os = $oqr->fetch(PDO::FETCH_ASSOC);

	$csq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
	$csq->execute(array($os["cid"]));
	$cc = $csq->fetch(PDO::FETCH_ASSOC);

	$usqsx = $ac->prepare("SELECT * FROM users WHERE id = ?");
	$usqsx->execute(array($os["creativer"]));
	$usx = $usqsx->fetch(PDO::FETCH_ASSOC);

	$statt = $ac->prepare("SELECT * FROM ofstats WHERE sid = ?");
	$statt->execute(array($os["statu"]));
	$st = $statt->fetch(PDO::FETCH_ASSOC);

	$als = explode("|",$os["authors"]);
	
	//$afps = $ac->prepare("SELECT * FROM users WHERE");
?>

<div class="col-lg-12 col-md-6 col-sm-12 mb-30">
						<div class="pd-20 bg-white border-radius-4 box-shadow">
							<h5 class="mb-20">Teklif Durumu</h5>
							<div class="progress mb-20">
								<?php
								if($os["statu"] == 0){
									$yuzde = 0;
									$renk = "success";
								}elseif($os["statu"] == 1){
									$yuzde = 20;
									$renk = "success";
								}elseif($os["statu"] == 2){
									$yuzde = 40;
									$renk = "success";
								}elseif($os["statu"] == 3){
									$yuzde = 60;
									$renk = "success";
								}elseif($os["statu"] == 4){
									$yuzde = 100;
									$renk = "danger";
								}elseif($os["statu"] == 5){
									$yuzde = 100;
									$renk = "danger";
								}

								?>
								<div class="progress-bar bg-<?php echo $renk; ?> progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo $yuzde;?>%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
								
							</div>
						</div>
					</div>


			<div class="min-height-200px">
				<div class="page-header">
					<div class="row">
						
						
					</div>
				</div>
				<div id="printf" class="invoice-wrap">
					<div class="invoice-box">
						<div class="invoice-header">
							<div class="logo text-center">
								<br><br><br><button title="<?php echo $st["description"]; ?>" style="float:left;" type="button" class="btn btn-<?php echo $st["button"];?>">Teklif Durumu: <?php echo $st["title"];?></button>
							</div>
						</div>
						<?php if(permtrue("oedit")){ ?>
						<h4 class="text-center mb-30 weight-600">&nbsp; <a href="index.php?p=edit-offer&oid=<?php echo $oids; ?>"><button style="float:right;" type="button" class="btn btn-info">Teklifi Düzenle</button></a></h4> 
						<?php

							if($os["statu"] == 0){
								?>
							<h4 class="text-center mb-30 weight-600">&nbsp; <a OnClick="return confirm(' <?php echo "#".$os["id"];?> nolu teklifi müşteriye iletildi olarak işaretlemek istediğinize emin misinz?')" href="index.php?p=offer&oid=<?php echo $oids; ?>&update=true&stat=1"><button style="float:right;" type="button" class="btn btn-success">Müşteriye İlet</button></a> 	<a OnClick="return confirm(' <?php echo "#".$os["id"];?> nolu teklifi kalıcı olarak silmek istediğinize emin misiniz? ')" href="index.php?p=all-offers&type=delete&oid=<?php echo $oids; ?>&update=true&stat=10"> <button style="float:right;" type="button" class="btn btn-secondary">Teklifi Sil  </button></a></h4> 
								<?php
							}else if($os["statu"] == 1){
								?>
							<h4 class="text-center mb-30 weight-600">Teklif <a OnClick="return confirm(' <?php echo "#".$os["id"];?> nolu teklifi müşteri tarafından onaylandı şeklinde işaretlemek istediğinize emin misiniz?')" href="index.php?p=offer&oid=<?php echo $oids; ?>&update=true&stat=2"><button style="float:right;" type="button" class="btn btn-success">Teklif Onaylandı</button></a> 	<a OnClick="return confirm(' <?php echo "#".$os["id"];?> nolu teklifi müşteri tarafından reddedildi şeklinde işaretlemek istediğinize emin misiniz?')" href="index.php?p=offer&oid=<?php echo $oids; ?>&update=true&stat=4"> <button style="float:right;" type="button" class="btn btn-danger">Teklifi Reddet  </button></a></h4> 
								<?php
							}else if($os["statu"] == 2){
								?>
							<h4 class="text-center mb-30 weight-600">Teklif <a href="index.php?p=new-project&oid=<?php echo $oids; ?>"><button style="float:right;" type="button" class="btn btn-success">Proje Oluştur</button></a> 	<a onClick="return confirm('Teklif, hazırlık modunda, bekletilmeye alınacaktır. Devam etmek istiyoru musunuz.?')" href="index.php?p=offer&oid=<?php echo $oids; ?>&update=true&stat=BOS"><button style="float:right;" type="button" class="btn btn-info">Teklifi Beklet</button></a></h4> 
								<?php
							}else if($os["statu"] == 3){

								$pqah = $ac->prepare("SELECT * FROM projects WHERE poid = ?");
								$pqah->execute(array($oids));
								$ppx = $pqah->fetch(PDO::FETCH_ASSOC);

								$ayx = $ppx["id"];

								?>
								<h4 class="text-center mb-30 weight-600"><?php echo set ("company_name");?> <a href="index.php?p=edit-project&pid=<?php echo $ayx;?>"><button style="float:right;" type="button" class="btn btn-info">Proje Sayfası'na git</button></a> 	</h4> 
								<?php
							}else if($os["statu"] == 4){
								
								?>
								<h4 class="text-center mb-30 weight-600"><?php echo set ("company_name");?> <a onClick="return confirm('Teklif, kalıcı olarak silinecektir. Eğer var ise, scriptin excel dosyası otomatik olarak bilgisayarınıza indirilecektir.')" href="index.php?p=all-offers&type=delete&oid=<?php echo $oids; ?>&update=true&stat=10"><button style="float:right;" type="button" class="btn btn-secondary">Teklifi Sil</button></a><a onClick="return confirm('Teklif, hazırlık modunda, bekletilmeye alınacaktır. Devam etmek istiyoru musunuz.?')" href="index.php?p=offer&oid=<?php echo $oids; ?>&update=true&stat=BOS"><button style="float:right;" type="button" class="btn btn-success">Teklifi Beklet</button></a> 	</h4> 
								<?php
							}
						}

						?>
						
						<div class="row pb-30">
							<div class="col-md-6">
								<h5 class="mb-15"><?php echo $cc["name"];?></h5>
								<p class="font-14 mb-5">Teklif tarihi: <strong class="weight-600"><?php echo $os["reg_date"];?></strong></p>
								<p class="font-14 mb-5">Teklif No: <strong class="weight-600">#<?php echo $os["id"]; ?></strong></p>
							</div>
							<div class="col-md-6">
								<div class="text-right">
									<p class="font-14 mb-5"><?php echo set("company_name");?> </strong></p>
									<p class="font-14 mb-5"><?php echo set("company_address");?></p>
									<p class="font-14 mb-5"><?php echo set("company_city");?></p>
									<p class="font-14 mb-5"><?php echo set("company_phone1");?></p>
								</div>
							</div>
						</div>
						<?php if(@$os["filename"]){
							?><a href="<?php echo PANEL_URL;?>/projects/offers/<?php echo $os['filename']; ?>"><button type="button" class="btn btn-warning">Excel Dosyasını İndir</button></a>
							<?php }
							?><br><br>
						<div class="invoice-desc pb-30">
							<div class="invoice-desc-head clearfix">
								<div class="invoice-sub">Ürün/Hizmet</div>

								<div class="invoice-rate">Fiyat</div>
								<div class="invoice-hours">Miktar</div>
								<div class="invoice-subtotal">Tutar</div>
							</div>
							<div class="invoice-desc-body">
								<ul>

									<?php 

									$matq = $ac->prepare("SELECT * FROM offermatters WHERE oid = ?");
									$matq->execute(array($oids));
									while($mm = $matq->fetch(PDO::FETCH_ASSOC)){
									?>
									<li class="clearfix">
										<div class="invoice-sub"><?php echo $mm["title"];?></div>
										<div class="invoice-rate"><?php echo $pr.$mm["price"]."+KDV";?>(<?php echo $os["tax"]; ?>%)</div>
										<div class="invoice-hours"><?php echo $mm["amount"]." ".$mm["unit"];?></div>
										<?php 
										@$ttal = @$mm["price"]*@$mm["amount"];
										?>
										<div class="invoice-subtotal"><span class="weight-600"><?php echo $pr.$ttal."+KDV";?></span></div>
									</li>
								<?php } ?>
									
									
									

								</ul>
							</div>
							<div class="invoice-desc-footer">
								<div class="invoice-desc-head clearfix">
									<div class="invoice-sub">Uyarılar & Bilgilendirme</div>
									<div class="invoice-rate">Hazırlandığı Tarih</div>
									<div class="invoice-subtotal">Toplam</div>
								</div>
								<div class="invoice-desc-body">
									<ul>
										<li class="clearfix">
											<div class="invoice-sub">
												<p class="font-14 mb-5">Birim fiyatlara KDV <u>dahil değildir</u>. </p>
												<p class="font-14 mb-5">Dökümanları sisteme upload etmeyi unutmayın. </p><br>
												
												<p class="font-14 mb-5">Teklifi Oluşturan: <strong><?php echo $usx["username"];?></strong> </p>
												<p class="font-14 mb-5">Teklif Yetkilileri: <strong><?php 
													foreach($als as $pxs){
														$kwk = $ac->prepare("SELECT * FROM users WHERE id = ?");
														$kwk->execute(array($pxs));
														$kkx = $kwk->fetch(PDO::FETCH_ASSOC);
														echo "<a>".$kkx["username"]."</a> ";
													}
												?>
													
												</strong><br></p><p class="font-14 mb-5">Not: <strong><?php echo $os["notes"]; ?></strong></p>
											</div>
											<?php 
												$ttlx = $os["total_price"]*$os["tax"]/100;
												$ttxs = $ttlx+$os["total_price"];
											?>
											<div class="invoice-rate font-20 weight-600"><?php echo $os["regdate"];?></div>
											<div class="invoice-subtotal"><span class="weight-600 font-24 text-success"><?php echo $pr.$os["total_price"];?><br></span>+KDV(<?php echo $os["tax"]; ?>%)<br><span class="weight-600 font-24 text-danger"><?php echo $pr.$ttxs;?><br></span></div>
										</li>
									</ul>
								</div>
							</div>
						</div>
						<h4 class="text-center pb-20"><?php echo set("company_name");?></h4>
					</div>
				</div>
			</div>
	<?php

		}

	?>