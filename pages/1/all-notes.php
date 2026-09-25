<?php
$currentUserId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION["lid"] ?? ($_SESSION["id"] ?? 0)));

// Silme İşlemi
$nid = @$_GET["nid"];
if ($nid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
	if (function_exists('permcontrol')) {
		permcontrol("notedelete");
	}
	$qcont = $ac->prepare("SELECT * FROM notes WHERE id = ? AND (visibility = 'general' OR creativer = ?)");
	$qcont->execute(array($nid, $currentUserId));
	$qkx = $qcont->fetch(PDO::FETCH_ASSOC);
	if ($qkx) {
		$pdq = $ac->prepare("DELETE FROM notes WHERE id = ?");
		$pdq->execute(array($nid));

		if (function_exists('audit_log')) {
			audit_log('delete', 'notes', 'Not silindi: ' . ($qkx['title'] ?? ''), 'note', $nid, ['deleted_title' => $qkx['title'] ?? '']);
		}

		header("Location: index.php?p=all-notes&type=delete&code=0882md25&tid=" . urlencode($nid));
		exit;
	}
}

// Yeni Not Ekleme İşlemi (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_note') {
	if (function_exists('permcontrol')) {
		permcontrol("noteadd");
	}

	$title = trim($_POST["title"] ?? '');
	$desc = trim($_POST["desc"] ?? '');
	$sdate = !empty($_POST["startdate"]) ? (function_exists('date_tr') ? date_tr($_POST["startdate"]) : $_POST["startdate"]) : TODAY;
	$lastdate = !empty($_POST["lastdate"]) ? (function_exists('date_tr') ? date_tr($_POST["lastdate"]) : $_POST["lastdate"]) : '';
	$urg = trim($_POST["urgency"] ?? 'Orta');
	$cat = (int)($_POST["cat"] ?? 0);
	$visibility = ($_POST["visibility"] ?? 'general') === 'private' ? 'private' : 'general';

	if (empty($title)) {
		header("Location: index.php?p=all-notes&st=empty_title");
		exit;
	}

	$creativer = $currentUserId;

	$insq = $ac->prepare("INSERT INTO notes (category, title, dates, lastdate, creativer, urgency, descs, visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
	$result = $insq->execute(array($cat, $title, $sdate, $lastdate, $creativer, $urg, $desc, $visibility));

	if ($result) {
		$newId = $ac->lastInsertId();
		if (function_exists('audit_log')) {
			audit_log('create', 'notes', 'Yeni not eklendi: ' . $title, 'note', $newId, [
				'title' => $title,
				'urgency' => $urg,
				'category' => $cat,
				'visibility' => $visibility
			]);
		}
		header("Location: index.php?p=all-notes&st=newsuccess");
		exit;
	} else {
		header("Location: index.php?p=all-notes&st=dberror");
		exit;
	}
}

// İstatistikleri Çek
$statsQuery = $ac->prepare("SELECT
	COUNT(*) AS total_count,
	SUM(CASE WHEN urgency = 'Yüksek' THEN 1 ELSE 0 END) AS high_count,
	SUM(CASE WHEN urgency = 'Orta' THEN 1 ELSE 0 END) AS mid_count,
	SUM(CASE WHEN urgency = 'Düşük' THEN 1 ELSE 0 END) AS low_count
FROM notes
WHERE visibility = 'general' OR creativer = ?");
$statsQuery->execute(array($currentUserId));
$stats = $statsQuery ? $statsQuery->fetch(PDO::FETCH_ASSOC) : ['total_count' => 0, 'high_count' => 0, 'mid_count' => 0, 'low_count' => 0];

$totalCount = (int)($stats['total_count'] ?? 0);
$highCount = (int)($stats['high_count'] ?? 0);
$midCount = (int)($stats['mid_count'] ?? 0);
$lowCount = (int)($stats['low_count'] ?? 0);

// Kategorileri Çek (Modal için)
$catStmt = $ac->query("SELECT id, title FROM note_categories ORDER BY title ASC");
$allCategories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div class="all-notes-wrapper">
	<!-- Bildirimler -->
	<?php if (@$_GET["st"] == "newsuccess") { ?>
		<div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);">
			<i class="fa fa-check-circle mr-2"></i> Yeni not başarıyla oluşturuldu ve listeye eklendi.
			<button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	<?php } ?>

	<?php if (@$_GET["type"] == "delete" && @$_GET["tid"]) { ?>
		<div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);">
			<i class="fa fa-check-circle mr-2"></i> Not başarıyla silindi.
			<button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	<?php } ?>

	<?php if (@$_GET["st"] == "empty_title") { ?>
		<div class="alert alert-danger alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px;">
			<i class="fa fa-exclamation-triangle mr-2"></i> Not başlığı zorunludur. Lütfen (*) ile belirtilen alanları doldurunuz.
			<button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	<?php } ?>

	<?php if (@$_GET["st"] == "access_denied") { ?>
		<div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; margin-bottom: 20px;">
			<i class="fa fa-lock mr-2"></i> Bu özel nota erişim yetkiniz bulunmuyor.
			<button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		</div>
	<?php } ?>

	<!-- Header Card -->
	<div class="premium-header-card animate-fade-in">
		<div class="header-content">
			<div class="header-left">
				<div class="header-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;">
					<i class="fa fa-sticky-note"></i>
				</div>
				<div class="header-title">
					<h4>Tüm Notlar</h4>
					<div class="header-stat-pills d-flex align-items-center flex-wrap" style="gap: 8px; margin-top: 6px;">
						<span class="header-pill header-pill-total">
							<i class="fa fa-list-ul mr-1"></i> Toplam: <strong><?php echo $totalCount; ?></strong>
						</span>
						<span class="header-pill header-pill-high">
							<i class="fa fa-exclamation-circle mr-1"></i> Yüksek: <strong><?php echo $highCount; ?></strong>
						</span>
						<span class="header-pill header-pill-mid">
							<i class="fa fa-clock-o mr-1"></i> Orta: <strong><?php echo $midCount; ?></strong>
						</span>
						<span class="header-pill header-pill-low">
							<i class="fa fa-check-circle mr-1"></i> Düşük: <strong><?php echo $lowCount; ?></strong>
						</span>
					</div>
				</div>
			</div>
			<div class="header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
				<a href="index.php?p=note-categories" class="btn-header btn-header-list">
					<i class="fa fa-tags"></i> Not Tipleri
				</a>
				<?php if (function_exists('permtrue') && permtrue("noteadd")) { ?>
					<button type="button" class="btn-header btn-header-save" id="btnOpenNewNoteModal" data-bs-toggle="modal" data-bs-target="#newNoteModal" data-toggle="modal" data-target="#newNoteModal">
						<i class="fa fa-plus-circle"></i> Yeni Not Oluştur
					</button>
				<?php } ?>
			</div>
		</div>
	</div>

	<!-- Not Tablosu Kartı -->
	<div class="form-card mb-4 animate-fade-in" style="padding: 0; overflow: hidden; border-radius: 12px;">
		<div class="form-card-header d-flex justify-content-between align-items-center flex-wrap" style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; gap: 15px;">
			<div class="d-flex align-items-center header-left-inner">
				<div class="card-icon card-icon-blue mr-3" style="width: 38px; height: 38px; border-radius: 10px; font-size: 16px; display: flex; align-items: center; justify-content: center;">
					<i class="fa fa-list-alt"></i>
				</div>
				<div>
					<h5 class="mb-0" style="font-size: 16px; font-weight: 700;">Not Listesi</h5>
					<p class="mb-0 text-muted" style="font-size: 12.5px;">Tüm notlarınızı inceleyin, filtreleyin veya düzenleyin</p>
				</div>
			</div>
			<div class="header-right-inner ml-auto d-flex align-items-center" id="notesSearchContainer">
				<!-- Global Arama Kutusu Buraya Yerleşir -->
			</div>
		</div>

		<div class="table-responsive" style="padding: 0; margin: 0;">
			<table id="notesTable" class="data-table select-row table-bordered table-hover" style="width: 100%; margin: 0 !important;">
				<thead>
					<tr>
						<th class="text-center no-filter col-shrink">#Sıra</th>
						<th class="text-center col-shrink" data-filter-type="select">Aciliyet</th>
						<th class="col-expand" data-filter-type="text">Başlık & Not Detayı</th>
						<th class="col-shrink" data-filter-type="select">Not Tipi / Kategori</th>
						<th class="col-shrink" data-filter-type="select">Görünürlük</th>
						<th class="col-shrink" data-filter-type="select">Oluşturan</th>
						<th class="text-center col-shrink" data-filter-type="date">Başlangıç Tarihi</th>
						<th class="text-center col-shrink" data-filter-type="date">Bitiş Tarihi</th>
						<th class="datatable-nosort no-filter text-center col-shrink">İşlem</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$notesQuery = $ac->prepare("SELECT 
						n.*, 
						c.title AS category_name,
						u.username AS creator_username,
						u.Unvan AS creator_unvan
					FROM notes n
					LEFT JOIN note_categories c ON n.category = c.id
					LEFT JOIN users u ON n.creativer = u.id
					WHERE n.visibility = 'general' OR n.creativer = ?
					ORDER BY n.id DESC");
					$notesQuery->execute(array($currentUserId));
					$rowNum = 1;

					while ($row = $notesQuery->fetch(PDO::FETCH_ASSOC)) {
						$urgency = trim($row["urgency"] ?? 'Orta');
						
						// Aciliyet Badge Tasarımı
						$badgeClass = 'badge-urgency-mid';
						$badgeIcon = 'fa-circle';
						if ($urgency === 'Yüksek') {
							$badgeClass = 'badge-urgency-high';
							$badgeIcon = 'fa-arrow-up';
						} elseif ($urgency === 'Düşük') {
							$badgeClass = 'badge-urgency-low';
							$badgeIcon = 'fa-arrow-down';
						}

						$categoryName = !empty($row["category_name"]) ? $row["category_name"] : 'Genel';
						$creatorName = !empty($row["creator_unvan"]) ? $row["creator_unvan"] : (!empty($row["creator_username"]) ? $row["creator_username"] : 'Sistem');
						$cleanDesc = strip_tags($row["descs"] ?? '');
						$shortDesc = mb_substr($cleanDesc, 0, 90) . (mb_strlen($cleanDesc) > 90 ? '...' : '');
						$lastDateFormatted = !empty($row["lastdate"]) ? htmlspecialchars($row["lastdate"]) : '-';
						$startDateFormatted = !empty($row["dates"]) ? htmlspecialchars($row["dates"]) : '-';
					?>
						<tr data-note-row="1" data-note-id="<?php echo (int)$row['id']; ?>" data-note-title="<?php echo htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
							<td class="text-center font-weight-bold" style="vertical-align: middle; color: #64748b;">
								<?php echo $rowNum; ?>
							</td>

							<td style="vertical-align: middle;">
								<span class="badge-urgency <?php echo $badgeClass; ?>">
									<i class="fa <?php echo $badgeIcon; ?>"></i> <?php echo htmlspecialchars($urgency, ENT_QUOTES, 'UTF-8'); ?>
								</span>
							</td>

							<td style="vertical-align: middle;">
								<div class="d-flex flex-column">
									<a href="javascript:void(0);" 
										class="note-title-link view-note-btn font-weight-600" 
										style="color: #1e293b; font-size: 14.5px; text-decoration: none;"
										data-id="<?php echo $row['id']; ?>"
										data-title="<?php echo htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
										data-urgency="<?php echo htmlspecialchars($urgency, ENT_QUOTES, 'UTF-8'); ?>"
										data-urgency-class="<?php echo $badgeClass; ?>"
										data-category="<?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>"
										data-creator="<?php echo htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8'); ?>"
										data-sdate="<?php echo $startDateFormatted; ?>"
										data-lastdate="<?php echo $lastDateFormatted; ?>"
										data-desc="<?php echo htmlspecialchars($row['descs'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<?php echo htmlspecialchars($row["title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>
									</a>
									<?php if (!empty($shortDesc)) { ?>
										<small class="text-muted mt-1" style="font-size: 12.5px; line-height: 1.35;">
											<?php echo htmlspecialchars($shortDesc, ENT_QUOTES, 'UTF-8'); ?>
										</small>
									<?php } ?>
								</div>
							</td>

							<td style="vertical-align: middle;">
								<span class="badge-category">
									<i class="fa fa-folder-o mr-1"></i> <?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>
								</span>
							</td>

							<td style="vertical-align: middle;">
								<?php if (($row['visibility'] ?? 'general') === 'private') { ?>
									<span class="badge-visibility badge-visibility-private"><i class="fa fa-lock mr-1"></i> Özel</span>
								<?php } else { ?>
									<span class="badge-visibility"><i class="fa fa-users mr-1"></i> Genel</span>
								<?php } ?>
							</td>

							<td style="vertical-align: middle; font-size: 13px; color: #475569;">
								<div class="d-flex align-items-center">
									<div class="user-avatar-placeholder mr-2">
										<i class="fa fa-user"></i>
									</div>
									<span><?php echo htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8'); ?></span>
								</div>
							</td>

							<td style="vertical-align: middle; font-size: 13px; color: #475569;">
								<?php if ($startDateFormatted !== '-') { ?>
									<span class="badge-date">
										<i class="fa fa-calendar mr-1"></i> <?php echo $startDateFormatted; ?>
									</span>
								<?php } else { ?>
									<span class="text-muted">-</span>
								<?php } ?>
							</td>

							<td style="vertical-align: middle; font-size: 13px; color: #475569;">
								<?php if ($lastDateFormatted !== '-') { ?>
									<span class="badge-date">
										<i class="fa fa-calendar-check-o mr-1"></i> <?php echo $lastDateFormatted; ?>
									</span>
								<?php } else { ?>
									<span class="text-muted">-</span>
								<?php } ?>
							</td>

							<td class="text-center" style="vertical-align: middle; white-space: nowrap;">
								<div class="action-btn-group">
									<!-- İncele Butonu -->
									<button type="button" 
										class="btn btn-sm btn-outline-primary note-action-btn view-note-btn" 
										title="Detay Görüntüle"
										data-id="<?php echo $row['id']; ?>"
										data-title="<?php echo htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
										data-urgency="<?php echo htmlspecialchars($urgency, ENT_QUOTES, 'UTF-8'); ?>"
										data-urgency-class="<?php echo $badgeClass; ?>"
										data-category="<?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>"
										data-creator="<?php echo htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8'); ?>"
										data-sdate="<?php echo $startDateFormatted; ?>"
										data-lastdate="<?php echo $lastDateFormatted; ?>"
										data-desc="<?php echo htmlspecialchars($row['descs'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<i class="fa fa-eye"></i>
									</button>

									<!-- Düzenle Butonu -->
									<?php if (function_exists('permtrue') && permtrue("noteedit")) { ?>
										<a href="index.php?p=edit-note&nid=<?php echo $row["id"]; ?>" 
											class="btn btn-sm btn-outline-info note-action-btn" 
											title="Düzenle">
											<i class="fa fa-pencil"></i>
										</a>
									<?php } ?>

									<!-- Sil Butonu -->
									<?php if (function_exists('permtrue') && permtrue("notedelete")) { ?>
										<button type="button" 
											class="btn btn-sm btn-outline-danger note-action-btn btn-delete-note" 
											title="Sil"
											data-id="<?php echo $row['id']; ?>"
											data-title="<?php echo htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
											data-url="index.php?p=all-notes&mode=delete&code=04md177&reg=true&md=active&nid=<?php echo $row['id']; ?>">
											<i class="fa fa-trash"></i>
										</button>
									<?php } ?>
								</div>
							</td>
						</tr>
					<?php
						$rowNum++;
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ======================================================== -->
<!-- MODAL: YENİ NOT EKLEME MODALI                            -->
<!-- ======================================================== -->
<div class="modal fade" id="newNoteModal" tabindex="-1" role="dialog" aria-labelledby="newNoteModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content premium-modal-content">
			<form method="POST" action="index.php?p=all-notes" id="newNoteForm">
				<input type="hidden" name="action" value="create_note">
				
				<!-- Modal Header -->
				<div class="modal-header premium-modal-header">
					<div class="d-flex align-items-center">
						<div class="modal-header-icon mr-3">
							<i class="fa fa-sticky-note-o"></i>
						</div>
						<div>
							<h5 class="modal-title font-weight-700" id="newNoteModalLabel" style="color: #1e293b; margin-bottom: 2px;">Yeni Not Oluştur</h5>
							<small class="text-muted">Notunuza ait detayları eksiksiz doldurunuz.</small>
						</div>
					</div>
					<button type="button" class="close text-secondary" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="font-size: 24px; opacity: 0.7;">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>

				<!-- Modal Body -->
				<div class="modal-body" style="padding: 24px;">
					<div class="row">
						<!-- Başlık (Full Width) -->
						<div class="col-12 mb-3">
							<label class="font-weight-600" for="note_title" style="font-size: 13.5px; color: #334155;">
								<span class="text-danger font-weight-bold">(*)</span> Not Başlığı
							</label>
							<input type="text" name="title" id="note_title" class="form-control form-control-modern" placeholder="Örn: Müşteri Görüşme Notları..." required>
						</div>

						<!-- Aciliyet Derecesi -->
						<div class="col-md-6 col-12 mb-3">
							<label class="font-weight-600" for="note_urgency" style="font-size: 13.5px; color: #334155;">
								Aciliyet Derecesi
							</label>
							<select name="urgency" id="note_urgency" class="form-control form-control-modern">
								<option value="Düşük">🟢 Düşük Aciliyet</option>
								<option value="Orta" selected>🟡 Orta Aciliyet</option>
								<option value="Yüksek">🔴 Yüksek Aciliyet</option>
							</select>
						</div>

						<!-- Kategori / Not Tipi -->
						<div class="col-md-6 col-12 mb-3">
							<label class="font-weight-600" for="note_cat" style="font-size: 13.5px; color: #334155;">
								Not Tipi / Kategori
							</label>
							<select name="cat" id="note_cat" class="form-control form-control-modern">
								<option value="0">-- Genel / Kategorisiz --</option>
								<?php foreach ($allCategories as $catItem) { ?>
									<option value="<?php echo $catItem['id']; ?>">
										<?php echo htmlspecialchars($catItem['title'], ENT_QUOTES, 'UTF-8'); ?>
									</option>
								<?php } ?>
							</select>
						</div>

						<!-- Başlangıç Tarihi -->
						<div class="col-md-6 col-12 mb-3">
							<label class="font-weight-600" for="note_startdate" style="font-size: 13.5px; color: #334155;">
								Başlangıç Tarihi
							</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text bg-light border-right-0"><i class="fa fa-calendar"></i></span>
								</div>
								<input type="text" name="startdate" id="note_startdate" class="form-control form-control-modern date-picker-input" value="<?php echo TODAY; ?>" placeholder="DD-MM-YYYY">
							</div>
						</div>

						<!-- Bitiş / Son Tarih -->
						<div class="col-md-6 col-12 mb-3">
							<label class="font-weight-600" for="note_lastdate" style="font-size: 13.5px; color: #334155;">
								Bitiş / Son Tarih
							</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text bg-light border-right-0"><i class="fa fa-calendar-check-o"></i></span>
								</div>
								<input type="text" name="lastdate" id="note_lastdate" class="form-control form-control-modern date-picker-input" placeholder="Tarih Seçin (İsteğe Bağlı)">
							</div>
						</div>

						<div class="col-md-6 col-12 mb-3">
							<label class="font-weight-600" for="note_visibility" style="font-size: 13.5px; color: #334155;">Görünürlük</label>
							<select name="visibility" id="note_visibility" class="form-control form-control-modern">
								<option value="general" selected>Genel — Herkes görebilir</option>
								<option value="private">Özel — Yalnızca ben görebilirim</option>
							</select>
						</div>

						<!-- Not İçeriği / Açıklama -->
						<div class="col-12 mb-2">
							<label class="font-weight-600" for="note_desc" style="font-size: 13.5px; color: #334155;">
								Not İçeriği & Açıklama
							</label>
							<textarea name="desc" id="note_desc" rows="5" class="form-control form-control-modern" placeholder="Notunuza ait detayları buraya yazınız..." style="resize: vertical;"></textarea>
						</div>
					</div>
				</div>

				<!-- Modal Footer -->
				<div class="modal-footer premium-modal-footer">
					<button type="button" class="btn btn-light btn-modern-cancel" data-dismiss="modal" data-bs-dismiss="modal">
						<i class="fa fa-times mr-1"></i> İptal
					</button>
					<button type="submit" class="btn btn-success btn-modern-save">
						<i class="fa fa-save mr-1"></i> Notu Kaydet
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- ======================================================== -->
<!-- MODAL: NOT DETAY GÖRÜNTÜLEME MODALI                     -->
<!-- ======================================================== -->
<div class="modal fade" id="viewNoteModal" tabindex="-1" role="dialog" aria-labelledby="viewNoteModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content premium-modal-content">
			<!-- Modal Header -->
			<div class="modal-header premium-modal-header">
				<div class="d-flex align-items-center">
					<div class="modal-header-icon mr-3" style="background: #eff6ff; color: #3b82f6;">
						<i class="fa fa-file-text-o"></i>
					</div>
					<div>
						<h5 class="modal-title font-weight-700" id="viewModalTitle" style="color: #1e293b; margin-bottom: 2px;">Not Detayı</h5>
						<small class="text-muted" id="viewModalSubtitle">Kayıt bilgileri</small>
					</div>
				</div>
				<button type="button" class="close text-secondary" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="font-size: 24px; opacity: 0.7;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<!-- Modal Body -->
			<div class="modal-body" style="padding: 24px;">
				<!-- Meta Bilgiler Grid -->
				<div class="note-meta-grid mb-4">
					<div class="note-meta-item">
						<span class="meta-label">Aciliyet</span>
						<span class="meta-value" id="viewModalUrgency"></span>
					</div>
					<div class="note-meta-item">
						<span class="meta-label">Kategori / Tip</span>
						<span class="meta-value font-weight-600" id="viewModalCategory"></span>
					</div>
					<div class="note-meta-item">
						<span class="meta-label">Oluşturan</span>
						<span class="meta-value" id="viewModalCreator"></span>
					</div>
					<div class="note-meta-item">
						<span class="meta-label">Tarihler</span>
						<span class="meta-value" id="viewModalDates"></span>
					</div>
				</div>

				<!-- Not Metni -->
				<div>
					<label class="font-weight-600 text-secondary" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">
						<i class="fa fa-align-left mr-1"></i> Not İçeriği
					</label>
					<div id="viewModalDesc" class="note-desc-box"></div>
				</div>
			</div>

			<!-- Modal Footer -->
			<div class="modal-footer premium-modal-footer justify-content-between">
				<div id="viewModalActions"></div>
				<button type="button" class="btn btn-secondary btn-modern-cancel" data-dismiss="modal" data-bs-dismiss="modal">
					Kapat
				</button>
			</div>
		</div>
	</div>
</div>

<!-- ======================================================== -->
<!-- ÖZEL STİLLER (PREMIUM THEME UYUMLU)                      -->
<!-- ======================================================== -->
<style>
/* Header Stat Pills (Üst Kart İçi Sayı Rozetleri) */
.header-stat-pills {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 8px;
}
.header-pill {
	display: inline-flex;
	align-items: center;
	padding: 4px 11px;
	border-radius: 20px;
	font-size: 12.5px;
	font-weight: 500;
	backdrop-filter: blur(6px);
	transition: all 0.2s ease;
}
.header-pill-total {
	background: rgba(255, 255, 255, 0.22);
	color: #ffffff;
	border: 1px solid rgba(255, 255, 255, 0.35);
}
.header-pill-high {
	background: rgba(239, 68, 68, 0.3);
	color: #ffffff;
	border: 1px solid rgba(239, 68, 68, 0.45);
}
.header-pill-mid {
	background: rgba(245, 158, 11, 0.3);
	color: #ffffff;
	border: 1px solid rgba(245, 158, 11, 0.45);
}
.header-pill-low {
	background: rgba(16, 185, 129, 0.3);
	color: #ffffff;
	border: 1px solid rgba(16, 185, 129, 0.45);
}

/* Tablonun Üstündeki Boşlukları Sıfırla */
.all-notes-wrapper .form-card {
	padding: 0 !important;
	border-radius: 12px !important;
	overflow: hidden !important;
	border: 1px solid #e2e8f0 !important;
}
.all-notes-wrapper .form-card-header {
	margin-bottom: 0 !important;
}
.all-notes-wrapper .table-responsive {
	padding: 0 !important;
	margin: 0 !important;
}
.all-notes-wrapper .dataTables_wrapper {
	padding: 0 !important;
	margin: 0 !important;
}
.all-notes-wrapper .dataTables_wrapper > .row:first-child,
.all-notes-wrapper .dt-layout-row:first-child,
.all-notes-wrapper .dataTables_wrapper .top {
	display: none !important;
	margin: 0 !important;
	padding: 0 !important;
	height: 0 !important;
	min-height: 0 !important;
}
.all-notes-wrapper .dataTables_wrapper .row:last-child {
	padding: 12px 18px !important;
	margin: 0 !important;
	border-top: 1px solid #f1f5f9 !important;
	background: #fafafa !important;
	border-bottom-left-radius: 12px;
	border-bottom-right-radius: 12px;
}
.dark-mode .all-notes-wrapper .dataTables_wrapper .row:last-child {
	background: #151c27 !important;
	border-top-color: #334155 !important;
}
.all-notes-wrapper table.dataTable {
	margin-top: 0 !important;
	margin-bottom: 0 !important;
}
.all-notes-wrapper table.dataTable thead th {
	border-top: none !important;
}

/* Tablo Başlığındaki Sütun Arama Kutularını ve Huni İkonlarını Kaldır */
.all-notes-wrapper .search-input-row,
.all-notes-wrapper tr.search-input-row,
.all-notes-wrapper thead th input,
.all-notes-wrapper thead tr:not(:first-child),
.all-notes-wrapper .tf-trigger {
	display: none !important;
}

/* Tablo Sütun Genişlik Dağılımı: Başlık Maksimum, Diğerleri Minimum */
#notesTable {
	width: 100% !important;
	table-layout: auto !important;
}
#notesTable th.col-shrink,
#notesTable td:not(:nth-child(3)) {
	width: 1% !important;
	white-space: nowrap !important;
}
#notesTable th.col-expand,
#notesTable td:nth-child(3) {
	width: auto !important;
	min-width: 260px !important;
	white-space: normal !important;
}

/* Global Arama Kutusunu Sağa Yasla */
.all-notes-wrapper .dataTables_filter {
	float: right !important;
	margin-left: auto !important;
	margin-bottom: 0 !important;
	display: flex !important;
	align-items: center !important;
	justify-content: flex-end !important;
}
.all-notes-wrapper .dataTables_filter label {
	margin-bottom: 0 !important;
	display: flex !important;
	align-items: center !important;
	justify-content: flex-end !important;
	width: 100% !important;
}
.all-notes-wrapper .dataTables_filter input {
	width: 260px !important;
	max-width: 100% !important;
	border-radius: 8px !important;
	padding: 8px 34px 8px 36px !important;
	border: 1px solid #cbd5e1 !important;
	font-size: 13.5px !important;
	outline: none !important;
	transition: all 0.2s ease !important;
	background-color: #f8fafc !important;
}
.all-notes-wrapper .dataTables_filter input:focus {
	background-color: #ffffff !important;
	border-color: #6366f1 !important;
	box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
}

/* İşlem Butonları Arasındaki Boşluk ve Tasarım */
.action-btn-group {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 6px !important;
}
.note-action-btn {
	width: 32px !important;
	height: 32px !important;
	padding: 0 !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	border-radius: 6px !important;
	font-size: 13px !important;
	transition: all 0.2s ease !important;
}
.note-action-btn:hover {
	transform: translateY(-1px) !important;
	box-shadow: 0 2px 5px rgba(0, 0, 0, 0.12) !important;
}

.badge-visibility { display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 20px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 12px; font-weight: 600; }
.badge-visibility-private { background: #f3e8ff; color: #7e22ce; border-color: #e9d5ff; }
#notesTable tbody tr.context-menu-active td { background-color: #eef2ff !important; }
.notes-context-menu { position: fixed; z-index: 1090; min-width: 190px; padding: 6px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; box-shadow: 0 12px 30px rgba(15,23,42,.18); }
.notes-context-menu .cm-header { padding: 7px 10px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.notes-context-menu button, .notes-context-menu a { width: 100%; display: flex; align-items: center; gap: 9px; padding: 8px 10px; border: 0; border-radius: 6px; background: transparent; color: #334155; font-size: 13px; text-align: left; text-decoration: none; }
.notes-context-menu button:hover, .notes-context-menu a:hover { background: #f1f5f9; color: #1e293b; }
.notes-context-menu .cm-danger { color: #dc2626; }
.notes-context-menu .cm-divider { height: 1px; margin: 4px 2px; background: #e2e8f0; }

/* Aciliyet Badge Tasarımları */
.badge-urgency {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	padding: 5px 10px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	letter-spacing: 0.3px;
}
.badge-urgency-high {
	background-color: #fee2e2;
	color: #dc2626;
	border: 1px solid #fecaca;
}
.badge-urgency-mid {
	background-color: #fef3c7;
	color: #d97706;
	border: 1px solid #fde68a;
}
.badge-urgency-low {
	background-color: #dcfce7;
	color: #16a34a;
	border: 1px solid #bbf7d0;
}

/* Kategori ve Tarih Rozetleri */
.badge-category {
	display: inline-flex;
	align-items: center;
	padding: 4px 9px;
	border-radius: 6px;
	background-color: #f1f5f9;
	color: #334155;
	font-size: 12.5px;
	font-weight: 500;
	border: 1px solid #e2e8f0;
}
.badge-date {
	display: inline-flex;
	align-items: center;
	color: #475569;
	font-size: 12.5px;
	font-weight: 500;
}

/* User Avatar Placeholder */
.user-avatar-placeholder {
	width: 24px;
	height: 24px;
	border-radius: 50%;
	background: #e2e8f0;
	color: #64748b;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 11px;
}

/* Modal Stilleri */
.premium-modal-content {
	border-radius: 16px;
	border: 1px solid #e2e8f0;
	box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
	overflow: hidden;
}
.premium-modal-header {
	background: #ffffff;
	border-bottom: 1px solid #f1f5f9;
	padding: 18px 24px;
}
.modal-header-icon {
	width: 44px;
	height: 44px;
	border-radius: 12px;
	background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
	color: #ffffff;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20px;
}
.premium-modal-footer {
	background: #f8fafc;
	border-top: 1px solid #f1f5f9;
	padding: 14px 24px;
}

/* Form Elemanları */
.form-control-modern {
	border-radius: 8px;
	border: 1px solid #cbd5e1;
	padding: 10px 14px;
	font-size: 14px;
	transition: all 0.2s ease;
}
.form-control-modern:focus {
	border-color: #6366f1;
	box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.btn-modern-save {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
	border: none;
	border-radius: 8px;
	padding: 9px 20px;
	font-weight: 600;
	color: #fff;
	box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
}
.btn-modern-save:hover {
	background: linear-gradient(135deg, #059669 0%, #047857 100%);
	color: #fff;
}
.btn-modern-cancel {
	border-radius: 8px;
	padding: 9px 18px;
	font-weight: 500;
}

/* Not Detay Modalı Alanları */
.note-meta-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
	gap: 12px;
	background: #f8fafc;
	padding: 14px;
	border-radius: 10px;
	border: 1px solid #e2e8f0;
}
.note-meta-item {
	display: flex;
	flex-direction: column;
}
.note-meta-item .meta-label {
	font-size: 11.5px;
	text-transform: uppercase;
	color: #64748b;
	font-weight: 600;
	margin-bottom: 3px;
}
.note-meta-item .meta-value {
	font-size: 13.5px;
	color: #1e293b;
}

.note-desc-box {
	background: #ffffff;
	border: 1px solid #e2e8f0;
	border-radius: 10px;
	padding: 18px 20px;
	font-size: 14px;
	color: #334155;
	line-height: 1.65;
	min-height: 120px;
	max-height: 420px;
	overflow-y: auto;
	word-break: break-word;
	overflow-wrap: break-word;
}

.note-desc-box p {
	margin-bottom: 0.75rem;
}

.note-desc-box p:last-child,
.note-desc-box ul:last-child,
.note-desc-box ol:last-child {
	margin-bottom: 0;
}

.note-desc-box ul,
.note-desc-box ol {
	padding-left: 24px;
	margin-top: 4px;
	margin-bottom: 12px;
}

.note-desc-box ul {
	list-style-type: disc;
}

.note-desc-box ol {
	list-style-type: decimal;
}

.note-desc-box li {
	margin-bottom: 5px;
	line-height: 1.55;
}

.note-desc-box b,
.note-desc-box strong {
	font-weight: 600;
	color: #1e293b;
}

.note-desc-box a {
	color: #2563eb;
	text-decoration: underline;
}

.note-desc-box blockquote {
	border-left: 3.5px solid #3b82f6;
	background: #f8fafc;
	padding: 10px 16px;
	margin: 12px 0;
	border-radius: 0 6px 6px 0;
	color: #475569;
	font-style: italic;
}

.note-desc-box table {
	width: 100%;
	margin-bottom: 1rem;
	border-collapse: collapse;
}

.note-desc-box table th,
.note-desc-box table td {
	border: 1px solid #e2e8f0;
	padding: 8px 12px;
	text-align: left;
}

/* Dark Mode Desteği */
.dark-mode .premium-modal-content,
.dark-mode .premium-modal-header,
.dark-mode .note-desc-box {
	background: #1e293b !important;
	border-color: #334155 !important;
	color: #f1f5f9 !important;
}

.dark-mode .note-desc-box b,
.dark-mode .note-desc-box strong {
	color: #f8fafc !important;
}

.dark-mode .note-desc-box blockquote {
	background: #0f172a !important;
	border-left-color: #60a5fa !important;
	color: #cbd5e1 !important;
}

.dark-mode .note-desc-box table th,
.dark-mode .note-desc-box table td {
	border-color: #334155 !important;
}
.dark-mode .premium-modal-footer,
.dark-mode .note-meta-grid {
	background: #0f172a !important;
	border-color: #334155 !important;
}
.dark-mode .form-control-modern {
	background: #0f172a !important;
	border-color: #334155 !important;
	color: #f8fafc !important;
}
.dark-mode .note-title-link {
	color: #f8fafc !important;
}
.dark-mode .badge-category {
	background: #334155 !important;
	color: #e2e8f0 !important;
	border-color: #475569 !important;
}
.dark-mode .badge-visibility { background: #0c4a6e; color: #bae6fd; border-color: #075985; }
.dark-mode .badge-visibility-private { background: #581c87; color: #e9d5ff; border-color: #6b21a8; }
.dark-mode #notesTable tbody tr.context-menu-active td { background-color: #312e81 !important; }
.dark-mode .notes-context-menu { background: #1e293b; border-color: #334155; }
.dark-mode .notes-context-menu .cm-header { color: #94a3b8; }
.dark-mode .notes-context-menu button, .dark-mode .notes-context-menu a { color: #e2e8f0; }
.dark-mode .notes-context-menu button:hover, .dark-mode .notes-context-menu a:hover { background: #334155; }
.dark-mode .notes-context-menu .cm-danger { color: #fca5a5; }
.dark-mode .notes-context-menu .cm-divider { background: #334155; }
.dark-mode .all-notes-wrapper .dataTables_filter input {
	background-color: #0f172a !important;
	border-color: #334155 !important;
	color: #f8fafc !important;
}
</style>

<!-- Data Table ve Modal JS Betikleri -->
<script src="include/js/data-table.js"></script>

<script>
$(document).ready(function () {
	// Sütun filtrelerini (TableFilter) ve Arama Kutusunu Düzenle
	function initTableFilters() {
		$("#notesTable").find("tr.search-input-row").remove();
		
		// Global Arama Kutusunu Header'ın Sağına Yerleştir
		var filterEl = $(".dataTables_filter");
		if (filterEl.length && $("#notesSearchContainer").length) {
			if (!$("#notesSearchContainer").find(".dataTables_filter").length) {
				filterEl.appendTo("#notesSearchContainer");
			}
		}

		// TableFilter Huni İkonlarını ve Popoverlarını Bağla
		if (window.App && window.App.TableFilter) {
			App.TableFilter.attachToTable(document.getElementById('notesTable'));
		}
	}

	initTableFilters();
	setTimeout(initTableFilters, 100);
	setTimeout(initTableFilters, 300);
	setTimeout(initTableFilters, 700);

	function closeNotesContextMenu() {
		$("#notesContextMenu").remove();
		$("#notesTable tbody tr").removeClass("context-menu-active");
	}

	$(document).on("contextmenu", "#notesTable tbody tr[data-note-row]", function (e) {
		e.preventDefault();
		closeNotesContextMenu();
		var row = $(this).addClass("context-menu-active");
		var title = row.attr("data-note-title") || "Not işlemleri";
		var menu = $('<div id="notesContextMenu" class="notes-context-menu" role="menu"></div>');
		menu.append($('<div class="cm-header"></div>').text(title));
		menu.append('<button type="button" data-action="view"><i class="fa fa-eye"></i> Detayı Görüntüle</button>');
		if (row.find('a[title="Düzenle"]').length) {
			menu.append('<a href="' + row.find('a[title="Düzenle"]').attr('href') + '"><i class="fa fa-pencil"></i> Düzenle</a>');
		}
		if (row.find('.btn-delete-note').length) {
			menu.append('<div class="cm-divider"></div><button type="button" class="cm-danger" data-action="delete"><i class="fa fa-trash"></i> Sil</button>');
		}
		$("body").append(menu);
		var menuWidth = menu.outerWidth();
		var menuHeight = menu.outerHeight();
		var left = Math.min(e.clientX, window.innerWidth - menuWidth - 8);
		var top = Math.min(e.clientY, window.innerHeight - menuHeight - 8);
		menu.css({ left: Math.max(8, left), top: Math.max(8, top) });
		menu.on("click", '[data-action="view"]', function () { row.find('.view-note-btn').first().trigger('click'); closeNotesContextMenu(); });
		menu.on("click", '[data-action="delete"]', function () { row.find('.btn-delete-note').trigger('click'); closeNotesContextMenu(); });
	});

	$(document).on("click scroll", function (e) {
		if (!$(e.target).closest("#notesContextMenu").length) closeNotesContextMenu();
	});
	$(window).on("resize blur", closeNotesContextMenu);

	// Flatpickr / Datepicker Başlatma
	if (typeof flatpickr !== 'undefined') {
		$(".date-picker-input").flatpickr({
			dateFormat: "d-m-Y",
			locale: "tr",
			allowInput: true
		});
	}

	// Modal Açma Desteği (Hem Bootstrap 4 hem Bootstrap 5 uyumluluğu)
	$(document).on("click", "#btnOpenNewNoteModal", function (e) {
		e.preventDefault();
		if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
			var myModal = new bootstrap.Modal(document.getElementById('newNoteModal'));
			myModal.show();
		} else {
			$('#newNoteModal').modal('show');
		}
	});

	// Not Detay Modalı Tetikleyici
	$(document).on("click", ".view-note-btn", function (e) {
		e.preventDefault();
		var btn = $(this);
		var nid = btn.attr("data-id");
		var title = btn.attr("data-title");
		var urgency = btn.attr("data-urgency");
		var urgencyClass = btn.attr("data-urgency-class");
		var category = btn.attr("data-category");
		var creator = btn.attr("data-creator");
		var sdate = btn.attr("data-sdate");
		var lastdate = btn.attr("data-lastdate");
		var desc = btn.attr("data-desc");

		$("#viewModalTitle").text(title || "Not Detayı");
		$("#viewModalSubtitle").text("Not Kaydı #" + nid);

		$("#viewModalUrgency").html('<span class="badge-urgency ' + urgencyClass + '">' + urgency + '</span>');
		$("#viewModalCategory").text(category || "-");
		$("#viewModalCreator").text(creator || "-");
		$("#viewModalDates").text(sdate + " / " + lastdate);

		if (desc && desc.trim() !== "") {
			var isHtml = /<[a-z][\s\S]*>/i.test(desc);
			var renderedContent = isHtml ? desc : desc.replace(/\n/g, '<br>');
			$("#viewModalDesc").html(renderedContent);
		} else {
			$("#viewModalDesc").html('<span class="text-muted font-italic">Bu nota ait açıklama girilmemiş.</span>');
		}

		var editUrl = "index.php?p=edit-note&nid=" + nid;
		var deleteUrl = "index.php?p=all-notes&mode=delete&code=04md177&reg=true&md=active&nid=" + nid;
		var safeTitle = $('<div>').text(title || '').html();
		var actionsHtml = '';
		<?php if (function_exists('permtrue') && permtrue("noteedit")) { ?>
		actionsHtml += '<a href="' + editUrl + '" class="btn btn-outline-info btn-sm mr-2" style="border-radius: 6px;"><i class="fa fa-pencil mr-1"></i> Bu Notu Düzenle</a>';
		<?php } ?>
		<?php if (function_exists('permtrue') && permtrue("notedelete")) { ?>
		actionsHtml += '<button type="button" class="btn btn-outline-danger btn-sm btn-delete-note" style="border-radius: 6px;" data-id="' + nid + '" data-title="' + safeTitle + '" data-url="' + deleteUrl + '"><i class="fa fa-trash mr-1"></i> Bu Notu Sil</button>';
		<?php } ?>
		$("#viewModalActions").html(actionsHtml);

		if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
			var viewModal = new bootstrap.Modal(document.getElementById('viewNoteModal'));
			viewModal.show();
		} else {
			$('#viewNoteModal').modal('show');
		}
	});

	// Not Silme İşlemi (SweetAlert2 Onay Modalı)
	$(document).on("click", ".btn-delete-note", function (e) {
		e.preventDefault();
		var btn = $(this);
		var noteTitle = btn.attr("data-title") || "Seçilen";
		var deleteUrl = btn.attr("data-url");

		if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
			Swal.fire({
				title: 'Notu Silmek İstiyor Musunuz?',
				html: '<b>"' + $('<div>').text(noteTitle).html() + '"</b> başlıklı not kalıcı olarak silinecektir.<br><small class="text-muted" style="font-size: 13px; display: inline-block; margin-top: 6px;">Bu işlem geri alınamaz.</small>',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#ef4444',
				cancelButtonColor: '#64748b',
				confirmButtonText: '<i class="fa fa-trash mr-1"></i> Evet, Sil',
				cancelButtonText: '<i class="fa fa-times mr-1"></i> Vazgeç',
				reverseButtons: false,
				focusCancel: true,
				customClass: {
					confirmButton: 'btn btn-danger font-weight-600',
					cancelButton: 'btn btn-secondary font-weight-600',
					actions: 'd-flex justify-content-center align-items-center gap-2'
				},
				buttonsStyling: false
			}).then(function (result) {
				if (result.isConfirmed) {
					window.location.href = deleteUrl;
				}
			});
		} else {
			if (confirm('"' + noteTitle + '" başlıklı notu silmek istediğinize emin misiniz?')) {
				window.location.href = deleteUrl;
			}
		}
	});
});
</script>
