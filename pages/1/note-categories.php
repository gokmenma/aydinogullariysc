<?php

if ($_POST && $_GET["mode"] == "new") {

	$title = trim($_POST["title"]);

	if (empty($title)) {
		header("Location: /not-kategorileri?st=empty");
		exit;
	}

	$ekle = $ac->prepare("INSERT INTO note_categories SET
	title = ?,
	regdate = ?");
	$ekle->execute(array($title, TODAY));
	header("Location: /not-kategorileri?st=newsuccess");
	exit;
}

$xid = @$_GET["xid"];
if ($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {


	$pdq = $ac->prepare("DELETE FROM note_categories WHERE id = ?");
	$pdq->execute(array($xid));


	header("Location: /not-kategorileri?type=delete&code=0882md25");
	exit;
}


?>
<div class="note-categories-manage-wrapper">
	<!-- Alerts -->
	<?php
	if (@$_GET["st"] == "newsuccess") {
		?>
		<div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px;">
			<i class="fa fa-check-circle mr-2"></i> Kayıt başarıyla oluşturuldu.
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<?php
	}
	if (@$_GET["st"] == "empty") {
		?>
		<div class="alert alert-danger alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px;">
			<i class="fa fa-exclamation-circle mr-2"></i> Kategori adı boş olamaz!
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<?php
	}
	if (@$_GET["type"] == "delete") {
		?>
		<div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px;">
			<i class="fa fa-check-circle mr-2"></i> Silme işlemi başarılı!
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<?php
	}
	?>

	<!-- Header Card -->
	<div class="premium-header-card animate-fade-in">
		<div class="header-content">
			<div class="header-left">
				<div class="header-icon">
					<i class="fa fa-sticky-note"></i>
				</div>
				<div class="header-title">
					<h4>Not Kategori Ayarları</h4>
					<span class="header-number-badge">
						<i class="fa fa-folder-open"></i> Tanımlı Not Tipleri ve Kategorileri
					</span>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<!-- Yeni Kategori Ekleme Formu -->
		<div class="col-lg-4 col-md-12 mb-4 animate-fade-in">
			<div class="form-card" style="height: 100%;">
				<div class="form-card-header">
					<div class="card-icon card-icon-green">
						<i class="fa fa-plus-circle"></i>
					</div>
					<div>
						<h5>Yeni Oluştur</h5>
						<p>Sisteme yeni bir not tipi ekleyin</p>
					</div>
				</div>
				
				<form method="POST" action="/not-kategorileri?mode=new&code=38&cc=087s3">
					<div class="form-group">
						<label class="font-weight-500"><font color="red">(*)</font> Yeni Kategori Adı:</label>
						<input name="title" placeholder="örn: Telefon Görüşmesi" class="form-control" type="text" required style="border-radius: 8px; padding: 12px 14px; font-size: 14px; border: 1px solid #cbd5e1;">
					</div>
					<div class="mt-4">
						<button type="submit" class="btn btn-success btn-header-save w-100" style="padding: 12px; border-radius: 8px; font-weight: 600; border: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
							<i class="fa fa-plus"></i> Ekle
						</button>
					</div>
				</form>
			</div>
		</div>
		
		<!-- Kategori Listesi -->
		<div class="col-lg-8 col-md-12 mb-4 animate-fade-in">
			<div class="form-card">
				<div class="form-card-header">
					<div class="card-icon card-icon-blue">
						<i class="fa fa-list"></i>
					</div>
					<div>
						<h5>Not Tipleri Listesi</h5>
						<p>Sistemde kayıtlı olan tüm not tipleri</p>
					</div>
				</div>
				
				<div class="table-responsive">
					<table class="data-table select-row table-bordered table-hover" style="width: 100%;">
						<thead>
							<tr>
								<th width="70" class="text-center">#Sıra</th>
								<th>Kategori Adı</th>
								<th>Eklenme Tarihi</th>
								<th width="100" class="datatable-nosort text-center">İşlem</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$cq = $ac->prepare("SELECT * FROM note_categories ORDER by id DESC");
							$cq->execute();
							$kx = 1;
							while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {
								?>
								<tr>
									<td class="text-center" style="vertical-align: middle;"><?php echo $kx; ?></td>
									<td style="vertical-align: middle; font-weight: 600; color: #1f2937;"><?php echo htmlspecialchars($as["title"]); ?></td>
									<td style="vertical-align: middle; color: #4b5563;"><?php echo $as["regdate"]; ?></td>
									<td class="text-center" style="vertical-align: middle;">
										<a onClick="return confirm('Silmek istediğinize emin misiniz?')"
											href="/not-kategorileri?mode=delete&code=04md177&md=active&xid=<?php echo $as["id"]; ?>"
											class="btn btn-outline-danger btn-sm">
											<i class="fa fa-trash"></i> Sil
										</a>
									</td>
								</tr>
								<?php
								$kx = $kx + 1;
							} ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="include/js/data-table.js"></script>