<?php

$pids = @$_GET["id"];

// Görev Silme İşlemi
if ($pids && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
	if (function_exists('permcontrol')) {
		permcontrol("tododelete");
	}
	$pdq = $ac->prepare("DELETE FROM todolist WHERE id = ?");
	$pdq->execute(array($pids));
	
	if (function_exists('audit_log')) {
		audit_log('delete', 'tasks', 'Görev silindi (ID: ' . $pids . ')', 'task', $pids);
	}

	header("Location: index.php?p=tasks&st=deleted");
	exit;
}

// Hızlı Durum Güncelleme İşlemi
if ($pids && @$_GET["md"] == "update" && isset($_GET["tt"])) {
	if (function_exists('permcontrol')) {
		permcontrol("todoedit");
	}
	$tts = (int)$_GET["tt"];

	if ($tts === 1) {
		$gg = 1; // Yapıldı
		$statusLabel = 'Yapıldı';
	} elseif ($tts === 2) {
		$gg = 2; // Ertelendi
		$statusLabel = 'Ertelendi';
	} else {
		$gg = 0; // Yapılmadı
		$statusLabel = 'Yapılmadı';
	}

	$gunc = $ac->prepare("UPDATE todolist SET okey = ? WHERE id = ?");
	$gunc->execute(array($gg, $pids));

	if (function_exists('audit_log')) {
		audit_log('status_change', 'tasks', 'Görev durumu güncellendi: ' . $statusLabel, 'task', $pids, ['new_status' => $gg]);
	}

	header("Location: index.php?p=tasks&st=status_updated");
	exit;
}

// Modal Üzerinden Hızlı Görev Ekleme
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST" && @$_POST["action"] === "create_task") {
	if (function_exists('permcontrol')) {
		permcontrol("todoadd");
	}
	$title = trim(@$_POST["title"]);
	$desc = trim(@$_POST["desc"]);
	$okey = isset($_POST["okey"]) ? (int)$_POST["okey"] : 0;
	$ldate = !empty($_POST["lastdate"]) ? (function_exists('date_tr') ? date_tr($_POST["lastdate"]) : $_POST["lastdate"]) : '';
	$sdate = !empty($_POST["startdate"]) ? (function_exists('date_tr') ? date_tr($_POST["startdate"]) : $_POST["startdate"]) : TODAY;

	if (empty($title) || empty($desc) || empty($ldate)) {
		header("Location: index.php?p=tasks&st=empties");
		exit;
	}

	$creativer = function_exists('sesset') ? sesset("id") : ($_SESSION["lid"] ?? ($_SESSION["id"] ?? 0));

	$insq = $ac->prepare("INSERT INTO todolist SET
		title = ?,
		description = ?,
		regdate = ?,
		last_date = ?,
		creativer = ?,
		okey = ?");
	$result = $insq->execute(array($title, $desc, $sdate, $ldate, $creativer, $okey));

	if ($result) {
		$newId = $ac->lastInsertId();
		if (function_exists('audit_log')) {
			audit_log('create', 'tasks', 'Yeni görev eklendi: ' . $title, 'task', $newId, ['title' => $title]);
		}
		header("Location: index.php?p=tasks&st=created");
		exit;
	}
}

// İstatistikleri Hesapla
$countAllStmt = $ac->query("SELECT 
	COUNT(*) AS total_count,
	SUM(CASE WHEN okey = 0 THEN 1 ELSE 0 END) AS pending_count,
	SUM(CASE WHEN okey = 1 THEN 1 ELSE 0 END) AS done_count,
	SUM(CASE WHEN okey = 2 THEN 1 ELSE 0 END) AS postponed_count
FROM todolist");
$stats = $countAllStmt ? $countAllStmt->fetch(PDO::FETCH_ASSOC) : ['total_count' => 0, 'pending_count' => 0, 'done_count' => 0, 'postponed_count' => 0];

$totalCount = (int)($stats['total_count'] ?? 0);
$pendingCount = (int)($stats['pending_count'] ?? 0);
$doneCount = (int)($stats['done_count'] ?? 0);
$postponedCount = (int)($stats['postponed_count'] ?? 0);

?>

<div class="tasks-manage-wrapper">

	<!-- Bildirim / Alert Mesajları -->
	<?php if (@$_GET["st"] == "created") { ?>
		<div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);">
			<i class="fa fa-check-circle mr-2"></i> Yeni görev başarıyla oluşturuldu.
			<button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	<?php } ?>

	<?php if (@$_GET["st"] == "status_updated" || @$_GET["st"] == "newsuccess" || @$_GET["st"] == "success") { ?>
		<div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);">
			<i class="fa fa-check-circle mr-2"></i> Görev durumu başarıyla güncellendi.
			<button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	<?php } ?>

	<?php if (@$_GET["st"] == "deleted") { ?>
		<div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);">
			<i class="fa fa-check-circle mr-2"></i> Görev başarıyla silindi.
			<button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	<?php } ?>

	<?php if (@$_GET["st"] == "empties") { ?>
		<div class="alert alert-danger alert-dismissible fade show animate-fade-in" role="alert" style="border-radius: 12px; margin-bottom: 20px;">
			<i class="fa fa-exclamation-triangle mr-2"></i> Zorunlu alanları (*) boş bırakmayınız.
			<button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	<?php } ?>

	<!-- Header Card -->
	<div class="premium-header-card animate-fade-in">
		<div class="header-content">
			<div class="header-left">
				<div class="header-icon" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;">
					<i class="fa fa-tasks"></i>
				</div>
				<div class="header-title">
					<h4>Yapılacaklar Listesi</h4>
					<div class="header-stat-pills d-flex align-items-center flex-wrap" style="gap: 8px; margin-top: 6px;">
						<span class="header-pill header-pill-total">
							<i class="fa fa-list-ul mr-1"></i> Toplam: <strong><?php echo $totalCount; ?></strong>
						</span>
						<span class="header-pill header-pill-pending">
							<i class="fa fa-hourglass-half mr-1"></i> Bekleyen: <strong><?php echo $pendingCount; ?></strong>
						</span>
						<span class="header-pill header-pill-done">
							<i class="fa fa-check-circle mr-1"></i> Tamamlanan: <strong><?php echo $doneCount; ?></strong>
						</span>
						<span class="header-pill header-pill-postponed">
							<i class="fa fa-clock-o mr-1"></i> Ertelenen: <strong><?php echo $postponedCount; ?></strong>
						</span>
					</div>
				</div>
			</div>
			<div class="header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
				<?php if (function_exists('permtrue') && permtrue("todoadd")) { ?>
					<button type="button" class="btn-header btn-header-save" id="btnOpenNewTaskModal" data-bs-toggle="modal" data-bs-target="#newTaskModal" data-toggle="modal" data-target="#newTaskModal">
						<i class="fa fa-plus-circle"></i> Yeni Görev Oluştur
					</button>
					<a href="index.php?p=task-new" class="btn-header btn-header-list d-none d-md-inline-flex">
						<i class="fa fa-external-link"></i> Tam Form
					</a>
				<?php } ?>
			</div>
		</div>
	</div>

	<!-- Görev Tablosu Kartı -->
	<div class="form-card mb-4 animate-fade-in" style="padding: 0; overflow: hidden; border-radius: 12px;">
		<div class="form-card-header d-flex justify-content-between align-items-center flex-wrap" style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; gap: 15px;">
			<div class="d-flex align-items-center header-left-inner">
				<div class="card-icon card-icon-blue mr-3" style="width: 38px; height: 38px; border-radius: 10px; font-size: 16px; display: flex; align-items: center; justify-content: center;">
					<i class="fa fa-list-ul"></i>
				</div>
				<div>
					<h5 class="mb-0" style="font-size: 16px; font-weight: 700;">Görev Listesi</h5>
					<p class="mb-0 text-muted" style="font-size: 12.5px;">Yapılacak işleri takip edin, tamamlananları işaretleyin veya düzenleyin.</p>
				</div>
			</div>
			<div class="header-right-inner ml-auto d-flex align-items-center" id="tasksSearchContainer">
				<!-- Global Arama Kutusu Buraya Yerleşir -->
			</div>
		</div>

		<div class="table-responsive" style="padding: 0; margin: 0;">
			<table id="tasksTable" class="data-table select-row table-bordered table-hover" style="width: 100%; margin: 0 !important;">
				<thead>
					<tr>
						<th class="text-center no-filter col-shrink">#Sıra</th>
						<th class="text-center col-shrink" data-filter-type="select">Durum</th>
						<th class="col-expand" data-filter-type="text">Başlık & Görev Detayı</th>
						<th class="col-shrink" data-filter-type="select">Oluşturan</th>
						<th class="text-center col-shrink" data-filter-type="date">Son Tarih</th>
						<th class="datatable-nosort no-filter text-center col-shrink">İşlem</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$cq = $ac->prepare("SELECT 
						t.*, 
						u.username AS creator_username,
						u.Unvan AS creator_unvan
					FROM todolist t
					LEFT JOIN users u ON t.creativer = u.id
					ORDER BY t.id DESC");
					$cq->execute();
					$rowNum = 1;

					$todayTimestamp = strtotime(date('Y-m-d'));

					while ($row = $cq->fetch(PDO::FETCH_ASSOC)) {
						$status = (int)$row["okey"];
						
						// Durum Rozet Ayarları
						if ($status === 1) {
							$statusBadge = '<span class="badge-task-status badge-task-done"><i class="fa fa-check-circle"></i> Yapıldı</span>';
							$statusText = 'Yapıldı';
						} elseif ($status === 2) {
							$statusBadge = '<span class="badge-task-status badge-task-postponed"><i class="fa fa-pause-circle"></i> Ertelendi</span>';
							$statusText = 'Ertelendi';
						} else {
							$statusBadge = '<span class="badge-task-status badge-task-pending"><i class="fa fa-clock-o"></i> Yapılmadı</span>';
							$statusText = 'Yapılmadı';
						}

						$creatorName = !empty($row["creator_unvan"]) ? $row["creator_unvan"] : (!empty($row["creator_username"]) ? $row["creator_username"] : 'Sistem');
						$cleanDesc = strip_tags($row["description"] ?? '');
						$shortDesc = mb_substr($cleanDesc, 0, 95) . (mb_strlen($cleanDesc) > 95 ? '...' : '');
						
						$lastDateFormatted = !empty($row["last_date"]) ? htmlspecialchars($row["last_date"], ENT_QUOTES, 'UTF-8') : '-';
						$regDateFormatted = !empty($row["regdate"]) ? htmlspecialchars($row["regdate"], ENT_QUOTES, 'UTF-8') : '-';
						$taskTitleEscaped = htmlspecialchars($row["title"] ?? '', ENT_QUOTES, 'UTF-8');
						$taskDescEscaped = htmlspecialchars($row["description"] ?? '', ENT_QUOTES, 'UTF-8');
						$creatorNameEscaped = htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8');

						// Gecikme Kontrolü (Son tarih geçmiş ve yapılmamış ise)
						$isOverdue = false;
						if ($status !== 1 && !empty($row["last_date"])) {
							$parsedDate = strtotime(str_replace('/', '-', $row["last_date"]));
							if ($parsedDate && $parsedDate < $todayTimestamp) {
								$isOverdue = true;
							}
						}

						$canEdit = (function_exists('permtrue') && permtrue("todoedit")) ? '1' : '0';
						$canDelete = (function_exists('permtrue') && permtrue("tododelete") && $status !== 1) ? '1' : '0';
					?>
						<tr class="<?php echo $status === 1 ? 'task-row-done' : ''; ?>"
							data-id="<?php echo $row['id']; ?>"
							data-title="<?php echo $taskTitleEscaped; ?>"
							data-status="<?php echo $status; ?>"
							data-status-text="<?php echo $statusText; ?>"
							data-creator="<?php echo $creatorNameEscaped; ?>"
							data-sdate="<?php echo $regDateFormatted; ?>"
							data-lastdate="<?php echo $lastDateFormatted; ?>"
							data-desc="<?php echo $taskDescEscaped; ?>"
							data-can-edit="<?php echo $canEdit; ?>"
							data-can-delete="<?php echo $canDelete; ?>">
							<!-- #Sıra -->
							<td class="text-center font-weight-bold" style="vertical-align: middle; color: #64748b;">
								<?php echo $rowNum; ?>
							</td>

							<!-- Durum -->
							<td class="text-center" style="vertical-align: middle;">
								<?php echo $statusBadge; ?>
							</td>

							<!-- Başlık & Detay -->
							<td style="vertical-align: middle;">
								<div class="d-flex flex-column">
									<a href="javascript:void(0);" 
										class="task-title-link view-task-btn <?php echo $status === 1 ? 'task-title-done' : ''; ?>" 
										style="font-size: 14.5px; text-decoration: none;"
										data-id="<?php echo $row['id']; ?>"
										data-title="<?php echo $taskTitleEscaped; ?>"
										data-status="<?php echo $status; ?>"
										data-status-text="<?php echo $statusText; ?>"
										data-creator="<?php echo $creatorNameEscaped; ?>"
										data-sdate="<?php echo $regDateFormatted; ?>"
										data-lastdate="<?php echo $lastDateFormatted; ?>"
										data-desc="<?php echo $taskDescEscaped; ?>">
										<?php if ($status === 1) { ?>
											<i class="fa fa-check text-success mr-1"></i>
										<?php } ?>
										<?php echo $taskTitleEscaped; ?>
									</a>
									<?php if (!empty($shortDesc)) { ?>
										<small class="text-muted mt-1" style="font-size: 12.5px; line-height: 1.35;">
											<?php echo htmlspecialchars($shortDesc, ENT_QUOTES, 'UTF-8'); ?>
										</small>
									<?php } ?>
								</div>
							</td>

							<!-- Oluşturan -->
							<td style="vertical-align: middle; font-size: 13px; color: #475569;">
								<div class="d-flex align-items-center">
									<div class="user-avatar-placeholder mr-2">
										<i class="fa fa-user"></i>
									</div>
									<span class="font-weight-500"><?php echo $creatorNameEscaped; ?></span>
								</div>
							</td>

							<!-- Son Tarih -->
							<td class="text-center" style="vertical-align: middle; font-size: 13px;">
								<?php if ($lastDateFormatted !== '-') { ?>
									<span class="badge-date <?php echo $isOverdue ? 'badge-date-overdue' : ''; ?>">
										<i class="fa <?php echo $isOverdue ? 'fa-exclamation-circle mr-1' : 'fa-calendar-check-o mr-1'; ?>"></i> 
										<?php echo $lastDateFormatted; ?>
									</span>
								<?php } else { ?>
									<span class="text-muted">-</span>
								<?php } ?>
							</td>

							<!-- İşlemler -->
							<td class="text-center" style="vertical-align: middle; white-space: nowrap;">
								<div class="action-btn-group">
									
									<!-- İncele Butonu -->
									<button type="button" 
										class="btn btn-sm btn-outline-primary task-action-btn view-task-btn" 
										title="Görevi İncele"
										data-id="<?php echo $row['id']; ?>"
										data-title="<?php echo $taskTitleEscaped; ?>"
										data-status="<?php echo $status; ?>"
										data-status-text="<?php echo $statusText; ?>"
										data-creator="<?php echo $creatorNameEscaped; ?>"
										data-sdate="<?php echo $regDateFormatted; ?>"
										data-lastdate="<?php echo $lastDateFormatted; ?>"
										data-desc="<?php echo $taskDescEscaped; ?>">
										<i class="fa fa-eye"></i>
									</button>

									<!-- Hızlı Durum Butonları -->
									<?php if (function_exists('permtrue') && permtrue("todoedit")) { ?>
										<?php if ($status === 0) { ?>
											<!-- Yapılmadı ise: Yapıldı ve Ertele -->
											<a href="index.php?p=tasks&reg=true&md=update&id=<?php echo $row['id']; ?>&tt=1" 
												class="btn btn-sm btn-outline-success task-action-btn" 
												title="Yapıldı Olarak İşaretle">
												<i class="fa fa-check"></i>
											</a>
											<a href="index.php?p=tasks&reg=true&md=update&id=<?php echo $row['id']; ?>&tt=2" 
												class="btn btn-sm btn-outline-warning task-action-btn" 
												title="Görevi Ertele">
												<i class="fa fa-pause"></i>
											</a>
										<?php } elseif ($status === 1) { ?>
											<!-- Yapıldı ise: Geri Al (Yapılmadı) -->
											<a href="index.php?p=tasks&reg=true&md=update&id=<?php echo $row['id']; ?>&tt=3" 
												class="btn btn-sm btn-outline-secondary task-action-btn" 
												title="Yapılmadı Olarak Geri Al">
												<i class="fa fa-undo"></i>
											</a>
										<?php } elseif ($status === 2) { ?>
											<!-- Ertelendi ise: Yapıldı veya Yapılmadı -->
											<a href="index.php?p=tasks&reg=true&md=update&id=<?php echo $row['id']; ?>&tt=1" 
												class="btn btn-sm btn-outline-success task-action-btn" 
												title="Yapıldı Olarak İşaretle">
												<i class="fa fa-check"></i>
											</a>
											<a href="index.php?p=tasks&reg=true&md=update&id=<?php echo $row['id']; ?>&tt=3" 
												class="btn btn-sm btn-outline-danger task-action-btn" 
												title="Yapılmadı Olarak İşaretle">
												<i class="fa fa-times"></i>
											</a>
										<?php } ?>

										<!-- Düzenle Butonu -->
										<a href="index.php?p=task-edit&id=<?php echo $row["id"]; ?>" 
											class="btn btn-sm btn-outline-info task-action-btn" 
											title="Düzenle">
											<i class="fa fa-pencil"></i>
										</a>
									<?php } ?>

									<!-- Sil Butonu (SweetAlert2 Tetikleyici) -->
									<?php if (function_exists('permtrue') && permtrue("tododelete") && $status !== 1) { ?>
										<button type="button" 
											class="btn btn-sm btn-outline-danger task-action-btn btn-delete-task" 
											title="Sil"
											data-id="<?php echo $row["id"]; ?>"
											data-title="<?php echo $taskTitleEscaped; ?>"
											data-url="index.php?p=tasks&mode=delete&code=04md177&id=<?php echo $row["id"]; ?>">
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
<!-- MODAL: GÖREV DETAY / İNCELE MODALI                        -->
<!-- ======================================================== -->
<div class="modal fade" id="viewTaskModal" tabindex="-1" role="dialog" aria-labelledby="viewTaskModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content premium-modal-content">
			<!-- Modal Header -->
			<div class="modal-header premium-modal-header">
				<div class="d-flex align-items-center">
					<div class="modal-header-icon mr-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;">
						<i class="fa fa-tasks"></i>
					</div>
					<div>
						<h5 class="modal-title font-weight-700" id="viewTaskModalLabel" style="color: #1e293b; margin-bottom: 2px;">Görev Detayı</h5>
						<small class="text-muted" id="viewModalTaskSubtitle">Görev Kaydı</small>
					</div>
				</div>
				<button type="button" class="close text-secondary" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="font-size: 24px; opacity: 0.7;">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<!-- Modal Body -->
			<div class="modal-body" style="padding: 24px;">
				<h4 id="viewModalTitle" class="font-weight-700 mb-3" style="color: #0f172a; font-size: 18px; line-height: 1.4;"></h4>

				<!-- Meta Bilgiler Grid -->
				<div class="task-meta-grid mb-3">
					<div class="task-meta-item">
						<span class="meta-label">Durum</span>
						<div id="viewModalStatusBadge" class="meta-value"></div>
					</div>
					<div class="task-meta-item">
						<span class="meta-label">Oluşturan</span>
						<span id="viewModalCreator" class="meta-value">-</span>
					</div>
					<div class="task-meta-item">
						<span class="meta-label">Başlangıç / Kayıt</span>
						<span id="viewModalStartDate" class="meta-value">-</span>
					</div>
					<div class="task-meta-item">
						<span class="meta-label">Son Teslim Tarihi</span>
						<span id="viewModalLastDate" class="meta-value">-</span>
					</div>
				</div>

				<!-- Açıklama Kutusu -->
				<label class="font-weight-600 mb-1" style="font-size: 13px; color: #475569;">
					<i class="fa fa-align-left mr-1"></i> Görev Açıklaması & Yapılacaklar
				</label>
				<div id="viewModalDesc" class="task-desc-box"></div>
			</div>

			<!-- Modal Footer -->
			<div class="modal-footer premium-modal-footer d-flex justify-content-between align-items-center">
				<div id="viewModalStatusActions" class="d-flex gap-2"></div>
				<div>
					<span id="viewModalEditBtn"></span>
					<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 6px; padding: 6px 14px;">Kapat</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- ======================================================== -->
<!-- MODAL: YENİ GÖREV OLUŞTURMA MODALI                        -->
<!-- ======================================================== -->
<div class="modal fade" id="newTaskModal" tabindex="-1" role="dialog" aria-labelledby="newTaskModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content premium-modal-content">
			<form method="POST" action="index.php?p=tasks" id="newTaskForm">
				<input type="hidden" name="action" value="create_task">
				
				<!-- Modal Header -->
				<div class="modal-header premium-modal-header">
					<div class="d-flex align-items-center">
						<div class="modal-header-icon mr-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;">
							<i class="fa fa-calendar-plus-o"></i>
						</div>
						<div>
							<h5 class="modal-title font-weight-700" id="newTaskModalLabel" style="color: #1e293b; margin-bottom: 2px;">Yeni Görev Oluştur</h5>
							<small class="text-muted">Göreve ait bilgileri ve tarih sınırlarını giriniz.</small>
						</div>
					</div>
					<button type="button" class="close text-secondary" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="font-size: 24px; opacity: 0.7;">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>

				<!-- Modal Body -->
				<div class="modal-body" style="padding: 24px;">
					<div class="row">
						<!-- Başlık -->
						<div class="col-12 mb-3">
							<label class="font-weight-600" for="task_title" style="font-size: 13.5px; color: #334155;">
								<span class="text-danger font-weight-bold">(*)</span> Görev Başlığı
							</label>
							<input type="text" name="title" id="task_title" class="form-control form-control-modern" placeholder="Örn: Servis sözleşmesi imzalanacak..." required>
						</div>

						<!-- Durum -->
						<div class="col-md-6 col-12 mb-3">
							<label class="font-weight-600" for="task_okey" style="font-size: 13.5px; color: #334155;">
								Durum
							</label>
							<select name="okey" id="task_okey" class="form-control form-control-modern">
								<option value="0" selected>⏳ Yapılmadı (Bekliyor)</option>
								<option value="1">✅ Yapıldı (Tamamlandı)</option>
								<option value="2">⏸️ Ertelendi</option>
							</select>
						</div>

						<!-- Başlangıç Tarihi -->
						<div class="col-md-6 col-12 mb-3">
							<label class="font-weight-600" for="task_startdate" style="font-size: 13.5px; color: #334155;">
								Başlangıç Tarihi
							</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text bg-light border-right-0"><i class="fa fa-calendar"></i></span>
								</div>
								<input type="text" name="startdate" id="task_startdate" class="form-control form-control-modern date-picker-input" value="<?php echo TODAY; ?>" placeholder="DD-MM-YYYY">
							</div>
						</div>

						<!-- Son Tarih -->
						<div class="col-md-6 col-12 mb-3">
							<label class="font-weight-600" for="task_lastdate" style="font-size: 13.5px; color: #334155;">
								<span class="text-danger font-weight-bold">(*)</span> Son Teslim Tarihi
							</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text bg-light border-right-0"><i class="fa fa-calendar-check-o"></i></span>
								</div>
								<input type="text" name="lastdate" id="task_lastdate" class="form-control form-control-modern date-picker-input" required placeholder="DD-MM-YYYY">
							</div>
						</div>

						<!-- Açıklama -->
						<div class="col-12 mb-2">
							<label class="font-weight-600" for="task_desc" style="font-size: 13.5px; color: #334155;">
								<span class="text-danger font-weight-bold">(*)</span> Görev Açıklaması
							</label>
							<textarea name="desc" id="task_desc" class="form-control form-control-modern" rows="4" placeholder="Görevle ilgili yapılacak işlemleri ve detayları buraya yazınız..." required></textarea>
						</div>
					</div>
				</div>

				<!-- Modal Footer -->
				<div class="modal-footer premium-modal-footer">
					<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 6px; padding: 7px 16px;">Vazgeç</button>
					<button type="submit" class="btn btn-success btn-sm" style="border-radius: 6px; padding: 7px 20px; font-weight: 600; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; color: #fff;">
						<i class="fa fa-save mr-1"></i> Görevi Kaydet
					</button>
				</div>
			</form>
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
.header-pill-pending {
	background: rgba(239, 68, 68, 0.3);
	color: #ffffff;
	border: 1px solid rgba(239, 68, 68, 0.45);
}
.header-pill-done {
	background: rgba(16, 185, 129, 0.3);
	color: #ffffff;
	border: 1px solid rgba(16, 185, 129, 0.45);
}
.header-pill-postponed {
	background: rgba(245, 158, 11, 0.3);
	color: #ffffff;
	border: 1px solid rgba(245, 158, 11, 0.45);
}

/* Tablonun Üstündeki Boşlukları Sıfırla */
.tasks-manage-wrapper .form-card {
	padding: 0 !important;
	border-radius: 12px !important;
	overflow: hidden !important;
	border: 1px solid #e2e8f0 !important;
}
.tasks-manage-wrapper .form-card-header {
	margin-bottom: 0 !important;
}
.tasks-manage-wrapper .table-responsive {
	padding: 0 !important;
	margin: 0 !important;
}
.tasks-manage-wrapper .dataTables_wrapper {
	padding: 0 !important;
	margin: 0 !important;
}
.tasks-manage-wrapper .dataTables_wrapper > .row:first-child,
.tasks-manage-wrapper .dt-layout-row:first-child,
.tasks-manage-wrapper .dataTables_wrapper .top {
	display: none !important;
	margin: 0 !important;
	padding: 0 !important;
	height: 0 !important;
	min-height: 0 !important;
}
.tasks-manage-wrapper table.dataTable {
	margin-top: 0 !important;
	margin-bottom: 0 !important;
}
.tasks-manage-wrapper table.dataTable thead th {
	border-top: none !important;
}

/* Tablo Başlığındaki Çirkin Sütun Arama Input Satırını Kaldır */
.tasks-manage-wrapper .search-input-row,
.tasks-manage-wrapper tr.search-input-row,
.tasks-manage-wrapper thead th input,
.tasks-manage-wrapper thead tr:not(:first-child) {
	display: none !important;
}

/* Tablo Sütun Genişlik Dağılımı: Başlık Maksimum, Diğerleri Minimum */
#tasksTable {
	width: 100% !important;
	table-layout: auto !important;
}
#tasksTable th.col-shrink,
#tasksTable td:not(:nth-child(3)) {
	width: 1% !important;
	white-space: nowrap !important;
}
#tasksTable th.col-expand,
#tasksTable td:nth-child(3) {
	width: auto !important;
	min-width: 260px !important;
	white-space: normal !important;
}

/* Global Arama Kutusunu Sağa Yasla */
.tasks-manage-wrapper .dataTables_filter {
	float: right !important;
	margin-left: auto !important;
	margin-bottom: 0 !important;
	display: flex !important;
	align-items: center !important;
	justify-content: flex-end !important;
}
.tasks-manage-wrapper .dataTables_filter label {
	margin-bottom: 0 !important;
	display: flex !important;
	align-items: center !important;
	justify-content: flex-end !important;
	width: 100% !important;
}
.tasks-manage-wrapper .dataTables_filter input {
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
.tasks-manage-wrapper .dataTables_filter input:focus {
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
.task-action-btn {
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
.task-action-btn:hover {
	transform: translateY(-1px) !important;
	box-shadow: 0 2px 5px rgba(0, 0, 0, 0.12) !important;
}

/* Durum Rozetleri */
.badge-task-status {
	display: inline-flex;
	align-items: center;
	padding: 5px 12px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	letter-spacing: 0.3px;
	border: 1px solid transparent;
	white-space: nowrap;
}
.badge-task-status i {
	margin-right: 5px;
	font-size: 11px;
}
.badge-task-pending {
	background: #fef2f2;
	color: #ef4444;
	border-color: #fecaca;
}
.badge-task-done {
	background: #f0fdf4;
	color: #10b981;
	border-color: #bbf7d0;
}
.badge-task-postponed {
	background: #fffbeb;
	color: #f59e0b;
	border-color: #fde68a;
}

/* Tarih Rozeti */
.badge-date {
	display: inline-flex;
	align-items: center;
	padding: 4px 10px;
	border-radius: 6px;
	background: #f1f5f9;
	color: #475569;
	font-size: 12.5px;
	font-weight: 500;
	border: 1px solid #e2e8f0;
}
.badge-date-overdue {
	background: #fef2f2;
	color: #dc2626;
	border-color: #fecaca;
	font-weight: 600;
}

/* Kullanıcı Avatar Placeholder */
.user-avatar-placeholder {
	width: 26px;
	height: 26px;
	border-radius: 50%;
	background: #e0e7ff;
	color: #4f46e5;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 11px;
	font-weight: 600;
	flex-shrink: 0;
}

/* Görev Başlık Linki */
.task-title-link {
	font-weight: 600;
	color: #1e293b;
	text-decoration: none;
	transition: color 0.2s ease;
}
.task-title-link:hover {
	color: #4f46e5;
	text-decoration: underline;
}
.task-title-done {
	text-decoration: line-through;
	color: #94a3b8 !important;
}

/* Modal Özel Stilleri */
.premium-modal-content {
	border-radius: 14px;
	border: none;
	box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
	overflow: hidden;
}
.premium-modal-header {
	background: #ffffff;
	border-bottom: 1px solid #f1f5f9;
	padding: 18px 24px;
}
.premium-modal-footer {
	background: #f8fafc;
	border-top: 1px solid #f1f5f9;
	padding: 14px 24px;
}
.modal-header-icon {
	width: 42px;
	height: 42px;
	border-radius: 10px;
	background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
	color: #ffffff;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 18px;
	flex-shrink: 0;
}

.form-control-modern {
	border-radius: 8px;
	border: 1px solid #cbd5e1;
	padding: 9px 13px;
	font-size: 14px;
}
.form-control-modern:focus {
	border-color: #6366f1;
	box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.task-meta-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	gap: 12px;
	background: #f8fafc;
	padding: 14px;
	border-radius: 10px;
	border: 1px solid #e2e8f0;
}
.task-meta-item {
	display: flex;
	flex-direction: column;
}
.task-meta-item .meta-label {
	font-size: 11.5px;
	text-transform: uppercase;
	color: #64748b;
	font-weight: 600;
	margin-bottom: 3px;
}
.task-meta-item .meta-value {
	font-size: 13.5px;
	color: #1e293b;
	font-weight: 500;
}

.task-desc-box {
	background: #ffffff;
	border: 1px solid #e2e8f0;
	border-radius: 10px;
	padding: 16px;
	font-size: 14px;
	color: #334155;
	line-height: 1.6;
	min-height: 100px;
	max-height: 350px;
	overflow-y: auto;
	white-space: pre-wrap;
	word-break: break-word;
}

/* Dark Mode Desteği */
/* Context Menu Stilleri */
.custom-context-menu {
	position: fixed;
	z-index: 99999;
	background: #ffffff;
	border: 1px solid #e2e8f0;
	border-radius: 10px;
	box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
	padding: 6px 0;
	min-width: 220px;
	max-width: 320px;
	display: none;
	animation: cmFadeIn 0.15s ease-out;
}
@keyframes cmFadeIn {
	from { opacity: 0; transform: scale(0.96); }
	to { opacity: 1; transform: scale(1); }
}
.custom-context-menu .cm-header {
	padding: 8px 14px 6px;
	font-size: 12px;
	font-weight: 700;
	color: #64748b;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	border-bottom: 1px solid #f1f5f9;
	margin-bottom: 4px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.custom-context-menu a,
.custom-context-menu button {
	display: flex;
	align-items: center;
	width: 100%;
	padding: 8px 14px;
	font-size: 13.5px;
	color: #334155;
	text-decoration: none;
	background: none;
	border: none;
	cursor: pointer;
	text-align: left;
	transition: background 0.15s ease, color 0.15s ease;
}
.custom-context-menu a:hover,
.custom-context-menu button:hover {
	background-color: #f1f5f9;
	color: #0f172a;
	text-decoration: none;
}
.custom-context-menu a.cm-danger,
.custom-context-menu button.cm-danger {
	color: #ef4444;
}
.custom-context-menu a.cm-danger:hover,
.custom-context-menu button.cm-danger:hover {
	background-color: #fef2f2;
	color: #dc2626;
}
.custom-context-menu i {
	width: 18px;
	text-align: center;
	font-size: 14px;
	margin-right: 8px;
}
.custom-context-menu .cm-divider {
	height: 1px;
	background: #f1f5f9;
	margin: 4px 0;
}
tr.context-menu-active {
	background-color: rgba(99, 102, 241, 0.08) !important;
}

/* Dark Mode Desteği */
.dark-mode .custom-context-menu {
	background: #1e293b !important;
	border-color: #334155 !important;
	box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
}
.dark-mode .custom-context-menu .cm-header {
	color: #94a3b8 !important;
	border-bottom-color: #334155 !important;
}
.dark-mode .custom-context-menu a,
.dark-mode .custom-context-menu button {
	color: #e2e8f0 !important;
}
.dark-mode .custom-context-menu a:hover,
.dark-mode .custom-context-menu button:hover {
	background-color: #334155 !important;
	color: #ffffff !important;
}
.dark-mode .custom-context-menu a.cm-danger:hover,
.dark-mode .custom-context-menu button.cm-danger:hover {
	background-color: rgba(239, 68, 68, 0.2) !important;
	color: #fca5a5 !important;
}
.dark-mode .custom-context-menu .cm-divider {
	background: #334155 !important;
}
.dark-mode tr.context-menu-active {
	background-color: rgba(99, 102, 241, 0.18) !important;
}
.dark-mode .premium-modal-content,
.dark-mode .premium-modal-header,
.dark-mode .task-desc-box {
	background: #1e293b !important;
	border-color: #334155 !important;
	color: #f1f5f9 !important;
}
.dark-mode .premium-modal-footer,
.dark-mode .task-meta-grid {
	background: #0f172a !important;
	border-color: #334155 !important;
}
.dark-mode .task-meta-item .meta-label {
	color: #94a3b8 !important;
}
.dark-mode .task-meta-item .meta-value {
	color: #f8fafc !important;
}
.dark-mode .task-title-link {
	color: #f8fafc !important;
}
.dark-mode .badge-date {
	background: #334155 !important;
	border-color: #475569 !important;
	color: #e2e8f0 !important;
}
.dark-mode .badge-date-overdue {
	background: rgba(239, 68, 68, 0.2) !important;
	border-color: #ef4444 !important;
	color: #fca5a5 !important;
}
.dark-mode .badge-task-pending {
	background: rgba(239, 68, 68, 0.15) !important;
	border-color: rgba(239, 68, 68, 0.4) !important;
	color: #f87171 !important;
}
.dark-mode .badge-task-done {
	background: rgba(16, 185, 129, 0.15) !important;
	border-color: rgba(16, 185, 129, 0.4) !important;
	color: #34d399 !important;
}
.dark-mode .badge-task-postponed {
	background: rgba(245, 158, 11, 0.15) !important;
	border-color: rgba(245, 158, 11, 0.4) !important;
	color: #fbbf24 !important;
}
.dark-mode .tasks-manage-wrapper .dataTables_filter input {
	background-color: #0f172a !important;
	border-color: #334155 !important;
	color: #f8fafc !important;
}
.dark-mode .form-control-modern {
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
		$("#tasksTable").find("tr.search-input-row").remove();

		// Global Arama Kutusunu Header'ın Sağına Yerleştir
		var filterEl = $(".dataTables_filter");
		if (filterEl.length && $("#tasksSearchContainer").length) {
			if (!$("#tasksSearchContainer").find(".dataTables_filter").length) {
				filterEl.appendTo("#tasksSearchContainer");
			}
		}

		// TableFilter Huni İkonlarını ve Popoverlarını Bağla
		if (window.App && window.App.TableFilter) {
			App.TableFilter.attachToTable(document.getElementById('tasksTable'));
		}
	}

	initTableFilters();
	setTimeout(initTableFilters, 100);
	setTimeout(initTableFilters, 300);
	setTimeout(initTableFilters, 700);

	// Flatpickr / Datepicker Başlatma
	if (typeof flatpickr !== 'undefined') {
		$(".date-picker-input").flatpickr({
			dateFormat: "d-m-Y",
			locale: "tr",
			allowInput: true
		});
	}

	// Modal Açma Desteği
	$(document).on("click", "#btnOpenNewTaskModal", function (e) {
		e.preventDefault();
		if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
			var myModal = new bootstrap.Modal(document.getElementById('newTaskModal'));
			myModal.show();
		} else {
			$('#newTaskModal').modal('show');
		}
	});

	// Görev Detay Modalı Gösterme Fonksiyonu
	function showTaskDetails(data) {
		var tid = data.id || "";
		var title = data.title || "";
		var status = parseInt(data.status, 10);
		var statusText = data.statusText || "";
		var creator = data.creator || "";
		var sdate = data.sdate || "";
		var lastdate = data.lastdate || "";
		var desc = data.desc || "";

		$("#viewModalTitle").text(title || "Görev Detayı");
		$("#viewModalTaskSubtitle").text("Görev Kaydı #" + tid);

		var badgeHtml = '';
		if (status === 1) {
			badgeHtml = '<span class="badge-task-status badge-task-done"><i class="fa fa-check-circle"></i> Yapıldı</span>';
		} else if (status === 2) {
			badgeHtml = '<span class="badge-task-status badge-task-postponed"><i class="fa fa-pause-circle"></i> Ertelendi</span>';
		} else {
			badgeHtml = '<span class="badge-task-status badge-task-pending"><i class="fa fa-clock-o"></i> Yapılmadı</span>';
		}
		$("#viewModalStatusBadge").html(badgeHtml);

		$("#viewModalCreator").text(creator || "-");
		$("#viewModalStartDate").text(sdate || "-");
		$("#viewModalLastDate").text(lastdate || "-");
		$("#viewModalDesc").text(desc ? desc : "Bu göreve ait bir açıklama girilmemiş.");

		// Hızlı durum güncelleme butonları modal içine
		var actionsHtml = '';
		if (status === 0) {
			actionsHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=1" class="btn btn-sm btn-success mr-2" style="border-radius: 6px;"><i class="fa fa-check mr-1"></i> Yapıldı Olarak İşaretle</a>';
			actionsHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=2" class="btn btn-sm btn-warning" style="border-radius: 6px;"><i class="fa fa-pause mr-1"></i> Ertele</a>';
		} else if (status === 1) {
			actionsHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=3" class="btn btn-sm btn-danger mr-2" style="border-radius: 6px;"><i class="fa fa-undo mr-1"></i> Yapılmadı Olarak Geri Al</a>';
		} else if (status === 2) {
			actionsHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=1" class="btn btn-sm btn-success mr-2" style="border-radius: 6px;"><i class="fa fa-check mr-1"></i> Yapıldı Olarak İşaretle</a>';
			actionsHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=3" class="btn btn-sm btn-danger" style="border-radius: 6px;"><i class="fa fa-times mr-1"></i> Yapılmadı Olarak İşaretle</a>';
		}
		$("#viewModalStatusActions").html(actionsHtml);

		var editUrl = "index.php?p=task-edit&id=" + tid;
		$("#viewModalEditBtn").html(
			'<a href="' + editUrl + '" class="btn btn-outline-info btn-sm mr-2" style="border-radius: 6px;">' +
			'<i class="fa fa-pencil mr-1"></i> Düzenle</a>'
		);

		if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
			var viewModal = new bootstrap.Modal(document.getElementById('viewTaskModal'));
			viewModal.show();
		} else {
			$('#viewTaskModal').modal('show');
		}
	}

	// Görev Detay Modalı Tetikleyici
	$(document).on("click", ".view-task-btn", function (e) {
		e.preventDefault();
		var btn = $(this);
		showTaskDetails({
			id: btn.attr("data-id"),
			title: btn.attr("data-title"),
			status: btn.attr("data-status"),
			statusText: btn.attr("data-status-text"),
			creator: btn.attr("data-creator"),
			sdate: btn.attr("data-sdate"),
			lastdate: btn.attr("data-lastdate"),
			desc: btn.attr("data-desc")
		});
	});

	// SweetAlert2 ile Görev Silme Onay Fonksiyonu
	function confirmDeleteTask(taskTitle, deleteUrl) {
		if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
			Swal.fire({
				title: 'Görevi Silmek İstiyor Musunuz?',
				html: '<b>"' + $('<div>').text(taskTitle || 'Seçilen Görev').html() + '"</b> başlıklı görev silinecektir.<br><small class="text-muted" style="font-size: 13px; display: inline-block; margin-top: 6px;">Bu işlem geri alınamaz.</small>',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#ef4444',
				cancelButtonColor: '#64748b',
				confirmButtonText: '<i class="fa fa-trash mr-1"></i> Evet, Sil',
				cancelButtonText: '<i class="fa fa-times mr-1"></i> Vazgeç',
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
			if (confirm("'" + taskTitle + "' başlıklı görevi silmek istediğinize emin misiniz?")) {
				window.location.href = deleteUrl;
			}
		}
	}

	// Tablodaki Sil Butonu Tıklaması
	$(document).on("click", ".btn-delete-task", function (e) {
		e.preventDefault();
		var btn = $(this);
		var taskTitle = btn.attr("data-title") || "";
		var deleteUrl = btn.attr("data-url");
		confirmDeleteTask(taskTitle, deleteUrl);
	});

	// ========================================================
	// TABLODA SAĞ TIK (CONTEXT MENU) İŞLEMLERİ
	// ========================================================
	$(document).on('contextmenu', '#tasksTable tbody tr', function (e) {
		if ($(this).find('td').length <= 1) return;

		e.preventDefault();

		var $tr = $(this);
		$('#tasksTable tbody tr').removeClass('context-menu-active');
		$tr.addClass('context-menu-active');

		var tid = $tr.attr('data-id');
		var taskTitle = $tr.attr('data-title') || 'Görev';
		var status = parseInt($tr.attr('data-status'), 10);
		var canEdit = $tr.attr('data-can-edit') === '1';
		var canDelete = $tr.attr('data-can-delete') === '1';

		var shortHeaderTitle = taskTitle.length > 28 ? taskTitle.substring(0, 28) + '...' : taskTitle;
		var menuHtml = '<div class="cm-header"><i class="fa fa-tasks mr-1"></i> ' + $('<div>').text(shortHeaderTitle).html() + '</div>';

		// 1. İncele / Detay Seçeneği
		menuHtml += '<a href="#" class="cm-action-view" data-id="' + tid + '"><i class="fa fa-eye text-primary mr-2"></i> Görevi İncele</a>';

		// 2. Durum Değiştirme Seçenekleri (Yetki varsa)
		if (canEdit) {
			menuHtml += '<div class="cm-divider"></div>';
			if (status === 0) { // Yapılmadı ise
				menuHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=1"><i class="fa fa-check text-success mr-2"></i> Yapıldı Olarak İşaretle</a>';
				menuHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=2"><i class="fa fa-pause text-warning mr-2"></i> Görevi Ertele</a>';
			} else if (status === 1) { // Yapıldı ise
				menuHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=3"><i class="fa fa-undo text-secondary mr-2"></i> Yapılmadı Olarak Geri Al</a>';
			} else if (status === 2) { // Ertelendi ise
				menuHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=1"><i class="fa fa-check text-success mr-2"></i> Yapıldı Olarak İşaretle</a>';
				menuHtml += '<a href="index.php?p=tasks&reg=true&md=update&id=' + tid + '&tt=3"><i class="fa fa-times text-danger mr-2"></i> Yapılmadı Olarak İşaretle</a>';
			}

			// 3. Düzenle Seçeneği
			menuHtml += '<div class="cm-divider"></div>';
			menuHtml += '<a href="index.php?p=task-edit&id=' + tid + '"><i class="fa fa-pencil text-info mr-2"></i> Görevi Düzenle</a>';
		}

		// 4. Sil Seçeneği (Yetki varsa ve tamamlanmamışsa)
		if (canDelete) {
			var deleteUrl = 'index.php?p=tasks&mode=delete&code=04md177&id=' + tid;
			menuHtml += '<div class="cm-divider"></div>';
			menuHtml += '<a href="#" class="cm-danger cm-action-delete" data-id="' + tid + '" data-title="' + $('<div>').text(taskTitle).html() + '" data-url="' + deleteUrl + '"><i class="fa fa-trash text-danger mr-2"></i> Görevi Sil</a>';
		}

		var $contextMenu = $('#customContextMenu');
		if (!$contextMenu.length) {
			$contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
		}

		$contextMenu.html(menuHtml);

		var mouseX = e.clientX;
		var mouseY = e.clientY;

		$contextMenu.css({ display: 'block', visibility: 'hidden' });
		var menuWidth = $contextMenu.outerWidth();
		var menuHeight = $contextMenu.outerHeight();
		var windowWidth = $(window).width();
		var windowHeight = $(window).height();

		if (mouseX + menuWidth > windowWidth) {
			mouseX = windowWidth - menuWidth - 10;
		}
		if (mouseY + menuHeight > windowHeight) {
			mouseY = windowHeight - menuHeight - 10;
		}

		$contextMenu.css({
			top: mouseY + 'px',
			left: mouseX + 'px',
			visibility: 'visible',
			opacity: '1'
		});
	});

	// Context Menu'den İncele Tıklaması
	$(document).on('click', '#customContextMenu .cm-action-view', function (e) {
		e.preventDefault();
		var tid = $(this).attr('data-id');
		var $tr = $('#tasksTable tbody tr[data-id="' + tid + '"]');
		if ($tr.length) {
			showTaskDetails({
				id: $tr.attr("data-id"),
				title: $tr.attr("data-title"),
				status: $tr.attr("data-status"),
				statusText: $tr.attr("data-status-text"),
				creator: $tr.attr("data-creator"),
				sdate: $tr.attr("data-sdate"),
				lastdate: $tr.attr("data-lastdate"),
				desc: $tr.attr("data-desc")
			});
		}
		$('#customContextMenu').hide();
		$('#tasksTable tbody tr').removeClass('context-menu-active');
	});

	// Context Menu'den Sil Tıklaması
	$(document).on('click', '#customContextMenu .cm-action-delete', function (e) {
		e.preventDefault();
		var item = $(this);
		var taskTitle = item.attr('data-title');
		var deleteUrl = item.attr('data-url');
		$('#customContextMenu').hide();
		$('#tasksTable tbody tr').removeClass('context-menu-active');
		confirmDeleteTask(taskTitle, deleteUrl);
	});

	// Menü dışına tıklanınca veya sayfada kaydırma yapılınca kapat
	$(document).on('click scroll', function (e) {
		if (!$(e.target).closest('#customContextMenu').length) {
			$('#customContextMenu').hide();
			$('#tasksTable tbody tr').removeClass('context-menu-active');
		}
	});

	// ESC basılınca context menu kapat
	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' || e.keyCode === 27) {
			$('#customContextMenu').hide();
			$('#tasksTable tbody tr').removeClass('context-menu-active');
		}
	});
});
</script>