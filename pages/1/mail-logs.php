<?php
	permcontrol("mlogview");
	if(@$_GET["type"] == "delete"){
		$ois = $_GET["id"];
			
			 		$deleteone = $ac->prepare("DELETE FROM mail_logs WHERE id = ?");
			 		$deleteone->execute(array($ois));
			 		
	
			 	
			 		header("Location: index.php?p=mail-logs&st=newsuccess");

}
			 		
			

			 
	if(@$_GET["st"] == "newsuccess"){



?>
<div class="alert alert-success" role="alert">
								Silme işlemi başarılı
							</div>
	<?php
} 
?>

		<div class="pd-ltr-20 xs-pd-20-10">
			<div class="min-height-200px">
				<div class="page-header">
					<div class="row">
					
					</div>
				</div>
				<!-- Simple Datatable start -->
				<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
					<div class="clearfix mb-20">
						<div class="pull-left">
							<h5 class="text-blue">Mail Kayıtları</h5>
							
						</div>
					</div>
					<div class="row">
						<table class="data-table stripe hover ">
							<thead>
								<tr>
									
									<th>#</th>

									<th>Alıcı Mail</th>
									<th>Mail Türü</th>
									<th>Tarih-Saat</th>
									<th >Durum</th>
									<th class="datatable-nosort">İşlem</th>

								</tr>
							</thead>
							<tbody>
								<?php 
								$ofqu = $ac->prepare("SELECT * FROM mail_logs ORDER BY id DESC");
								$ofqu->execute();
								$kk = 1;
								while($of = $ofqu->fetch(PDO::FETCH_ASSOC)){
									
								?>
								<tr>
									
									<td class="table-plus "><?php echo $kk;?></td>
									<td><?php echo $of["tomail"];?></td>
									<td><?php echo $of["type"];?></td>
									<td><?php echo $of["datest"];?></td>
									<td style="font-weight:bold"><?php echo $of["statu"] == 1 ? '<font color="green">Gönderildi</font>' : '<font color="red">Gönderilemedi</font>';?></td>

										 <td>
      	<a href="index.php?p=mail-logs&type=delete&oid=&codes=mdac4343&id=<?php echo $of["id"]; ?>"><span class="badge badge-danger">Sil</span></a></td>

										
									
								</tr>
								<?php
								$kk++;
								}
								?>
								
							</tbody>
						</table>
					</div>
				</div>
				<!-- Simple Datatable End -->
				<!-- multiple select row Datatable start -->
				
				<!-- multiple select row Datatable End -->
				<!-- Export Datatable start -->
				
				<!-- Export Datatable End -->
			</div>
		</div>
	</div>