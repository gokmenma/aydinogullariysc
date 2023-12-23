
<?php
	
	if(@$_GET["stat"] == "deletelog"){
		$del = $ac->prepare("DELETE FROM logs WHERE type = ?");
		$del->execute(array(1));
		header("Location:index.php?p=home&stat=true&code=MD0084&ac=yes#logs");
		exit;
	}
	$projectquery = $ac->prepare("SELECT COUNT(*) FROM projects WHERE pstatu=?");
	$projectquery->execute(array(0));
	$contproject = $projectquery->fetchColumn();

	$offerquery = $ac->prepare("SELECT COUNT(*) FROM offers WHERE statu = ?");
	$offerquery->execute(array(0));
	$contoffer = $offerquery->fetchColumn();

	$offall = $ac->prepare("SELECT COUNT(*) FROM offers ");
	$offall->execute();
	$ofacs = $offall->fetchColumn();


	$projectquerys = $ac->prepare("SELECT COUNT(*) FROM projects WHERE pstatu = ?");
	$projectquerys->execute(array("1"));
	$contokproject = $projectquerys->fetchColumn();

	$notqx = $ac->prepare("SELECT COUNT(*) FROM notes ");
	$notqx->execute();
	$nots = $notqx->fetchColumn();

	$todos = $ac->prepare("SELECT COUNT(*) FROM todolist WHERE okey != ?");
	$todos->execute(array(1));
	$tdq = $todos->fetchColumn();

	$tdc = $ac->prepare("SELECT * FROM todolist WHERE okey != ? ORDER BY reg_date ASC" );
	$tdc->execute(array(1));
	$tooku = $tdc->fetch(PDO::FETCH_ASSOC);

	while($tooku = $tdc->fetch(PDO::FETCH_ASSOC)){

		if(dtf(TODAY,$tooku["last_date"] >= 0)){
			$ass = $tooku["last_date"];
			break;
		}
	}

    $tdca = $ac->prepare("SELECT * FROM todolist WHERE okey = ? ORDER BY last_date ASC LIMIT 1" );
	$tdca->execute(array(0));
	$tzk = $tdca->fetch(PDO::FETCH_ASSOC);


    if ($tzk){
        $tdxs = dtf(TODAY,$tzk["last_date"]);
        if($tdxs >= 0){
            $tdxs = $tdxs." GÜN";

        }elseif($tdxs > -70){
            $tdxs = "Gecikme";
        }else{
            $tdxs = "YOK";
        }
    }else{
        $tdxs = ' - ';
    }

	$allpq = $ac->prepare("SELECT COUNT(*) FROM projects ");
	$allpq->execute();
	$pqr = $allpq->fetchColumn();


    $pqr = $pqr <= 0 ? 1 : $pqr;
    $ofacs = $ofacs <= 0 ? 1 : $ofacs;

	@$st1rep = @$contokproject/@$pqr*100;
	@$st1reps = @$contproject/@$pqr*100;
	@$of1rep = @$contoffer/@$ofacs*100;


	$inq = $ac->prepare("SELECT * FROM inexps WHERE type = ?");
			$inq->execute(array("in"));
			$toti = 0;
			while($inx = $inq->fetch(PDO::FETCH_ASSOC)){
				$toti = $toti+$inx["pay"];
			}

			$exq = $ac->prepare("SELECT * FROM inexps WHERE type = ?");
			$exq->execute(array("exp"));
			$tote = 0;
			while($exx = $exq->fetch(PDO::FETCH_ASSOC)){
				$tote = $tote+$exx["pay"];
			}
?> 

<div class="main-container">
		<div class="pd-ltr-20 xs-pd-20-10">
			<div class="row clearfix progress-box">
				<div class="col-lg-3 col-md-6 col-sm-12 mb-30">
					<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
						<div class="project-info clearfix">
							<div class="project-info-left">
								<div class="icon box-shadow bg-blue text-white">
									<i class="fa fa-briefcase"></i>
								</div>
							</div>
							<div class="project-info-right">
								<span class="no text-blue weight-500 font-24"><?php echo $contproject != 0 ? $contproject : "0"; ?></span>
								<p class="weight-400 font-18">Devam eden projeler </p>
							</div>
						</div>
						<div class="project-info-progress">
							<div class="row clearfix">
								<div class="col-sm-6 text-muted weight-500">0</div>
								<div class="col-sm-6 text-right weight-500 font-14 text-muted"><?php echo $pqr; ?></div>
							</div>
							<div class="progress" style="height: 10px;">
								<div class="progress-bar bg-blue progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo $st1reps; ?>%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
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
								<span class="no text-light-green weight-500 font-24"><?php echo $contoffer>0 ? $contoffer : "0" ; ?></span>
								<p class="weight-400 font-18">Bekleyen Teklif Sayısı</p>
							</div>
						</div>
						<div class="project-info-progress">
							<div class="row clearfix">
								<div class="col-sm-6 text-muted weight-500"><?php echo "0";?></div>
								<div class="col-sm-6 text-right weight-500 font-14 text-muted"><?php echo $ofacs; ?></div>
							</div>
							<div class="progress" style="height: 10px;">
								<div class="progress-bar bg-light-green progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo $of1rep;?>%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-sm-12 mb-30">
					<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
						<div class="project-info clearfix">
							<div class="project-info-left">
								<div class="icon box-shadow bg-light-orange text-white">
									<i class="fa fa-list-alt"></i>
								</div>
							</div>
							<?php 
							$gq = $ac->prepare("SELECT COUNT(*) FROM missions WHERE statu = ?");
							$gq->execute(array(0));
							$ab = $gq->fetchColumn();

							$gq1 = $ac->prepare("SELECT COUNT(*) FROM missions ");
							$gq1->execute();
							$ab2 = $gq1->fetchColumn();

                            $ab2 = $ab2 != 0 ? $ab2 : 1;
							@$oran = ($ab2-$ab)/$ab2*100;
							 ?>
							<div class="project-info-right">
								<span class="no text-light-orange weight-500 font-24"><?php echo $ab; ?></span>
								<p class="weight-400 font-18">Tamamlanmayan Görev<br></p>
							</div>
						</div>
						<div class="project-info-progress">
							<div class="row clearfix">
								<div class="col-sm-6 text-muted weight-500">0</div>
								<div class="col-sm-6 text-right weight-500 font-14 text-muted"><?php echo $ab2; ?></div>
							</div>
							<div class="progress" style="height: 10px;">
								<div class="progress-bar bg-light-orange progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo $oran; ?>%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-sm-12 mb-30">
					<div class="bg-white pd-20 box-shadow border-radius-5 margin-5 height-100-p">
						<div class="project-info clearfix">
							<div class="project-info-left">
								<div class="icon box-shadow bg-light-purple text-white">
									<i class="fa fa-podcast"></i>
								</div>
							</div>
							<div class="project-info-right">
								<span class="no text-light-purple weight-500 font-24"><?php echo $tdq;?></span>
								<p class="weight-400 font-18">Yapılacaklar Listesi 67</p>
							</div>
						</div>
						<div class="project-info-progress">
							<div class="row clearfix">
								<div class="col-sm-6 text-muted weight-500"><?php echo "Kalan";?></div>
								<div style="font-size:15px;" class="col-sm-6 text-right weight-500 font-14 text-muted"><?php echo $tdxs; ?></div>
							</div>
							<div class="progress" style="height: 10px;">
								<div class="progress-bar bg-light-purple progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php if(permtrue("exview")){
			?>
				<div class="row clearfix">
				<div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-30">
					<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
						<h4 class="mb-30"><a href="index.php?p=pay-methods-list">Kasa Durumu</a></h4>
						<div class="clearfix device-usage-chart">
							
							<div class="width-50-p pull-left">
								<table style="width: 100%;">
									<thead>
										<?php

											$inq = $ac->prepare("SELECT * FROM inexps WHERE type = ?");
											$inq->execute(array("in"));
											$toti = 0;
											while($inx = $inq->fetch(PDO::FETCH_ASSOC)){
												$toti = $toti+$inx["pay"];
											}

											$exq = $ac->prepare("SELECT * FROM inexps WHERE type = ?");
											$exq->execute(array("exp"));
											$tote = 0;
											while($exx = $exq->fetch(PDO::FETCH_ASSOC)){
												$tote = $tote+$exx["pay"];
											}
										?>
										<tr>
											<th class="weight-500"><p>Ödeme Kanalı</p></th>
											<th class="text-right weight-500"><p>Bakiye</p></th>
										</tr>

									</thead>
									<tbody>
										<?php

											$serq = $ac->prepare("SELECT * FROM pay_methods ");
											$serq->execute(array());
											while($ser = $serq->fetch(PDO::FETCH_ASSOC)){
											if($ser["currency"] == "tl"){
								    			$pr = "₺";
								    		}elseif($ser["currency"] == "euro"){
								    			$pr = "€";
								    		}elseif($ser["currency"] == "dollar"){
								    			$pr = "$";
								    		}else{
								    			$pr = "";
								    		}
										?>

										<tr>
											<td width="100%"><p title="" class="weight-500 mb-5"><i class="fa fa-square text"></i>&nbsp; <?php echo "[$pr] ".$ser["title"];?></p></td>
											<td class="text-right weight-400"><?php echo $ser["total"]." ".$pr;?></td>
										</tr>
										

										<?php
										}
											
										
										?>
									
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 mb-30">
					<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">

						<h4 class="mb-30"><a href="index.php?p=income-expense-list">Son Gelir-Gider Durumu</a></h4><br>
						<div class="device-manage-progress-chart">
							<ul>
								<?php
									$ofq = $ac->prepare("SELECT * FROM inexps ORDER BY id DESC LIMIT 6");
									$ofq->execute();
									while($offs = $ofq->fetch(PDO::FETCH_ASSOC)){
									$pmxa = $ac->prepare("SELECT * FROM pay_methods WHERE id = ? ");
									$pmxa->execute(array($offs["pay_method"]));
									$xxp = $pmxa->fetch(PDO::FETCH_ASSOC);
									if($xxp["currency"] == "tl"){
										$prx = "₺";
									}elseif($xxp["currency"] == "dollar"){
										$prx = "$";
									}elseif($xxp["currency"] == "euro"){
										$prx = "€";
									}else{
										$prx = "";
									}

										if($offs["type"] =="in"){
											?>
											<li class="clearfix">
									<div title="<?php echo $offs["descs"]; ?>" class="device-name"><?php echo $offs["title"];?></div>
									<div class="device-progress">
										<div class="progress">
											<div title="<?php echo $offs["descs"]; ?>" class="progress-bar bg-success border-radius-8" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">
											</div>
										</div>
									</div>
									<div class="device-total"><?php echo "+".$offs["pay"].$prx;?></div>
								</li>
											<?php
										}else{
								?>
								<li class="clearfix">
									<div title="<?php echo $offs["descs"]; ?>" class="device-name"><?php echo $offs["title"];?></div>
									<div class="device-progress">
										<div class="progress">
											<div title="<?php echo $offs["descs"]; ?>" class="progress-bar bg-danger border-radius-8" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">
											</div>
										</div>
									</div>
									<div class="device-total"><?php echo "-".$offs["pay"].$prx;?></div>
								</li>

								<?php
							}
										}
								?>

							</ul>
						</div>
					</div>
				</div>
				
			
			</div>
			<?php
		} ?>
			<div class="row clearfix">
				
				<div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-30">
					<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
						<h4 class="mb-30"><a href="index.php?p=all-users">Ekip</a></h4>
						<div class="clearfix device-usage-chart">
							
							<div class="width-50-p pull-left">
								<table style="width: 100%;">
									<thead>
										<tr>
											<th class="weight-500"><p>Kullanıcı</p></th>
											<th class="text-right weight-500"><p>Tamamlanan Görev</p></th>&nbsp;&nbsp;&nbsp;&nbsp;
											<th class="text-right weight-500">&nbsp;</th>
											
											
										</tr>
									</thead>
									<tbody >
										<?php 
											$pqr = $ac->prepare("SELECT * FROM perms");
											$pqr->execute();
											while($pp = $pqr->fetch(PDO::FETCH_ASSOC)){
										 ?>
										<tr>
											<td width="70%"><p title="" class="weight-500 mb-5"><i class="fa fa-square text-black"></i> <?php echo $pp["p_title"]; ?></p></td>
											<td class="text-right weight-400">&nbsp;</td>
										</tr>
										
										<?php 
											$upq = $ac->prepare("SELECT * FROM users WHERE permission = ?");
											$upq->execute(array($pp["id"]));
											while($uu = $upq->fetch(PDO::FETCH_ASSOC)){

											$gorevq = $ac->prepare("SELECT COUNT(*) FROM missions WHERE authors = ? AND statu = ?");
											$gorevq->execute(array($uu["id"], 1));
											$sg1 = $gorevq->fetchColumn();

											$gorevq2 = $ac->prepare("SELECT COUNT(*) FROM missions WHERE authors = ? ");
											$gorevq2->execute(array($uu["id"]));
											$sg2 = $gorevq2->fetchColumn();





										 ?>
										<tr >
											<td width="70%"><p class="weight-500 mb-5"><i style="margin-left:18px" class="fa fa-square text-green"></i> <?php echo $uu["username"]; ?></p></td>
											<td class="text-right weight-400"><?php echo $sg1."/".$sg2; ?></td>
											<td class="text-right weight-400"></td>
										</tr>
										<?php 
									}
									} ?>									
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			



				
				<div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-30">
					<div class="bg-white pd-20 box-shadow border-radius-5 height-100-p">
						<h4 id="logs" class="mb-20">Yapılacaklar Listesi </h4>
						<div class="notification-list mx-h-450 customscroll">
							<ul>
								<?php 
								$toq = $ac->prepare("SELECT * FROM todolist WHERE okey = ?");
								$toq->execute(array(0));
								while($tto = $toq->fetch(PDO::FETCH_ASSOC)){
								 ?>
								
								<li>
									<a href="index.php?p=edit-task&reg=true&tid=<?php echo $tto["id"]; ?>">
										
										<h3 class="clearfix"><?php echo uset($tto["creativer"],"username"); ?><span><?php echo $tto["last_date"]; ?></span></h3>
										<p><?php echo shorted($tto["title"]); ?></p>
									</a>
								</li>	
								<?php } ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<?php include('include/footer.php'); ?>
		</div>