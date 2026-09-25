<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Model\MissionModel;

permcontrol("missiontake");

if (!@$_GET["mid"]) {
	header("Location: index.php?p=home&errorcode=00254");
	exit;
}

$mid = (int)$_GET["mid"];
$currentUserId = function_exists('sesset') ? sesset("id") : ($_SESSION["lid"] ?? ($_SESSION["id"] ?? 0));

$missionModel = new MissionModel();
$as = $missionModel->getMissionById($mid);

if (!$as) {
	header("Location: index.php?p=home&errorcode=00784");
	exit;
}

$authors = array_filter(explode('|', $as["authors"] ?? ''));
$isAuthor = in_array((string)$currentUserId, $authors) || in_array($currentUserId, $authors);
$isCreator = ((int)$as["creativer"] === (int)$currentUserId);

if (!$isAuthor && permfalse("allmisview") && !$isCreator) {
	header("Location: index.php");
	exit;
}

if (@$_GET["mode"] === "update") {
	$missionModel->updateStatus($mid, 1, $currentUserId);
	header("Location: index.php?p=my-missions&update=true&mid=" . $mid);
	exit;
}

$usersMap = $missionModel->getUsersMap();
$creator = $usersMap[(int)$as["creativer"]] ?? null;
$creatorName = $creator ? htmlspecialchars($creator['username'] ?? '') : 'Bilinmeyen';
$creatorTitle = $creator ? htmlspecialchars($creator['Unvan'] ?? '') : '';

$today = date('Y-m-d');
$lastDateRaw = trim($as['lastdate'] ?? '');
$isOverdue = false;
$remainingText = '';

if (!empty($lastDateRaw)) {
	$targetDate = null;
	if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $lastDateRaw, $m)) {
		$targetDate = "{$m[3]}-{$m[2]}-{$m[1]}";
	} elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', $lastDateRaw)) {
		$targetDate = substr($lastDateRaw, 0, 10);
	}

	if ($targetDate) {
		$diff = (int)floor((strtotime($targetDate) - strtotime($today)) / 86400);
		if ((int)$as['statu'] === 1) {
			$remainingText = '<span class="badge badge-success"><i class="fa fa-check"></i> Tamamlandı</span>';
		} elseif ($diff > 0) {
			$remainingText = "<span class=\"badge badge-info\"><i class=\"fa fa-clock-o\"></i> {$diff} gün kaldı</span>";
		} elseif ($diff === 0) {
			$remainingText = '<span class="badge badge-warning text-white"><i class="fa fa-exclamation-circle"></i> Bugün son gün!</span>';
		} else {
			$isOverdue = true;
			$overdueDays = abs($diff);
			$remainingText = "<span class=\"badge badge-danger\"><i class=\"fa fa-exclamation-triangle\"></i> {$overdueDays} gün gecikti</span>";
		}
	}
}

$urgency = trim($as['urgency'] ?? 'Orta');
$urgencyBadgeClass = 'badge-warning text-white';
if ($urgency === 'Yüksek') {
	$urgencyBadgeClass = 'badge-danger';
} elseif ($urgency === 'Düşük') {
	$urgencyBadgeClass = 'badge-success';
}
?>

<div class="view-mission-wrapper mission-module-wrapper">
	<!-- Header Card -->
	<div class="premium-header-card animate-fade-in">
		<div class="header-content">
			<div class="header-left">
				<div class="header-icon mission-header-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;">
					<i class="fa fa-clipboard"></i>
				</div>
				<div class="header-title">
					<h4><?php echo htmlspecialchars($as["title"] ?? 'Görev Detayları'); ?></h4>
					<span class="header-number-badge">
						<i class="fa fa-info-circle"></i> Görev No: #<?php echo $as["id"]; ?> &bull; Durum ve Süreç Detayları
					</span>
				</div>
			</div>
			<div class="header-actions">
				<a href="javascript:history.back();" class="btn-header btn-header-list">
					<i class="fa fa-arrow-left"></i> Geri Dön
				</a>

				<?php if ($isCreator) { ?>
					<a href="index.php?p=edit-mission&mid=<?php echo $as['id']; ?>" class="btn-header btn-header-list" style="border-color: #3b82f6; color: #2563eb;">
						<i class="fa fa-pencil"></i> Düzenle
					</a>
				<?php } ?>

				<?php if ((int)$as["statu"] === 0 && ($isAuthor || $isCreator || permtrue("allmisview"))) { ?>
					<button type="button" 
							onclick="confirmCompleteInView(<?php echo $as['id']; ?>, '<?php echo htmlspecialchars(addslashes($as['title'])); ?>')" 
							class="btn-header btn-header-save">
						<i class="fa fa-check"></i> Yapıldı İşaretle
					</button>
				<?php } ?>
			</div>
		</div>
	</div>

	<div class="row">
		<!-- Sol Kolon: Görev Tanımı ve İçerik -->
		<div class="col-lg-8 col-md-12 mb-4 animate-fade-in">
			<div class="form-card" style="height: 100%;">
				<div class="form-card-header">
					<div class="card-icon card-icon-blue">
						<i class="fa fa-align-left"></i>
					</div>
					<div>
						<h5>Görev İçeriği & Bilgileri</h5>
						<p>Görevin konusu, firma ilişkisi ve detaylı açıklaması</p>
					</div>
				</div>

				<div class="row mb-3">
					<?php if (!empty($as["FirmaAdi"])): ?>
						<div class="col-md-6 mb-3">
							<label style="font-weight: 600; font-size: 12.5px; color: #64748b;">İlgili Firma</label>
							<div class="p-2 px-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; color: #0369a1;">
								<i class="fa fa-building-o mr-1"></i> <?php echo htmlspecialchars($as["FirmaAdi"]); ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if (!empty($as["categoryName"])): ?>
						<div class="col-md-6 mb-3">
							<label style="font-weight: 600; font-size: 12.5px; color: #64748b;">Görev Kategorisi</label>
							<div class="p-2 px-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500; color: #475569;">
								<i class="fa fa-tag mr-1"></i> <?php echo htmlspecialchars($as["categoryName"]); ?>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<div class="form-group mb-4">
					<label style="font-weight: 600; font-size: 13px; color: #475569;">Görev Başlığı</label>
					<div class="p-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; font-size: 15px; color: #1e293b;">
						<?php echo htmlspecialchars($as["title"] ?? ''); ?>
					</div>
				</div>

				<div class="form-group">
					<label style="font-weight: 600; font-size: 13px; color: #475569;">Görev Açıklaması</label>
					<div class="p-4" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; min-height: 140px; font-size: 14px; line-height: 1.6; color: #334155;">
						<?php 
						$mdesc = $as["mdesc"] ?? '';
						if (strip_tags($mdesc) !== $mdesc) {
							echo $mdesc;
						} else {
							echo nl2br(htmlspecialchars($mdesc));
						}
						?>
					</div>
				</div>
			</div>
		</div>

		<!-- Sağ Kolon: Durum & Tarih Çizelgesi -->
		<div class="col-lg-4 col-md-12 mb-4 animate-fade-in">
			<div class="form-card" style="height: 100%;">
				<div class="form-card-header">
					<div class="card-icon card-icon-purple">
						<i class="fa fa-history"></i>
					</div>
					<div>
						<h5>Durum & Bilgiler</h5>
						<p>Tarihler, aciliyet ve görev atamaları</p>
					</div>
				</div>

				<!-- Durum Rozeti -->
				<div class="text-center mb-4 p-3" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
					<span style="font-size: 11px; font-weight: 700; display: block; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">GÖREV DURUMU</span>
					<?php if ((int)$as["statu"] === 1) { ?>
						<span class="badge badge-success" style="font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 20px; border: none; box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2);">
							<i class="fa fa-check-circle mr-1"></i> Görev Tamamlandı
						</span>
					<?php } else { ?>
						<span class="badge badge-warning text-white" style="font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 20px; border: none; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);">
							<i class="fa fa-clock-o mr-1"></i> Görev Bekliyor
						</span>
					<?php } ?>
				</div>

				<!-- Bilgi Satırları -->
				<div class="audit-trail-card" style="display: flex; flex-direction: column; gap: 16px;">
					<!-- Aciliyet -->
					<div class="audit-row" style="display: flex; align-items: flex-start; gap: 12px;">
						<div class="audit-icon" style="color: #f59e0b; font-size: 15px; margin-top: 2px;"><i class="fa fa-bolt"></i></div>
						<div style="display: flex; flex-direction: column;">
							<span style="font-size: 11px; color: #64748b; font-weight: 600;">Aciliyet Seviyesi</span>
							<div>
								<span class="badge <?php echo $urgencyBadgeClass; ?>" style="font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 12px;">
									<?php echo htmlspecialchars($urgency); ?>
								</span>
							</div>
						</div>
					</div>

					<!-- Görevi Oluşturan -->
					<div class="audit-row" style="display: flex; align-items: flex-start; gap: 12px;">
						<div class="audit-icon" style="color: #6366f1; font-size: 15px; margin-top: 2px;"><i class="fa fa-user-circle"></i></div>
						<div style="display: flex; flex-direction: column;">
							<span style="font-size: 11px; color: #64748b; font-weight: 600;">Görevi Oluşturan</span>
							<span style="font-size: 13px; color: #1e293b; font-weight: 600;">
								<?php echo $creatorName; ?> 
								<?php if (!empty($creatorTitle)): ?><small class="text-muted">(<?php echo $creatorTitle; ?>)</small><?php endif; ?>
							</span>
						</div>
					</div>

					<!-- Görevlendirilen Kişiler -->
					<div class="audit-row" style="display: flex; align-items: flex-start; gap: 12px;">
						<div class="audit-icon" style="color: #10b981; font-size: 15px; margin-top: 2px;"><i class="fa fa-users"></i></div>
						<div style="display: flex; flex-direction: column; width: 100%;">
							<span style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px;">Görevlendirilen Kişiler</span>
							<div style="display: flex; flex-wrap: wrap; gap: 5px;">
								<?php if (empty($authors)): ?>
									<span class="text-muted small">Atanan yok</span>
								<?php else: ?>
									<?php foreach ($authors as $aid): 
										$u = $usersMap[(int)$aid] ?? null;
										$uName = $u ? htmlspecialchars($u['username'] ?? '') : 'Kullanıcı #' . $aid;
									?>
										<span class="badge badge-light" style="border: 1px solid #e2e8f0; font-size: 12px; font-weight: 500; color: #334155; padding: 4px 8px; border-radius: 6px;">
											<i class="fa fa-user mr-1 text-muted"></i> <?php echo $uName; ?>
										</span>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Kayıt Tarihi -->
					<div class="audit-row" style="display: flex; align-items: flex-start; gap: 12px;">
						<div class="audit-icon" style="color: #3b82f6; font-size: 15px; margin-top: 2px;"><i class="fa fa-calendar-plus-o"></i></div>
						<div style="display: flex; flex-direction: column;">
							<span style="font-size: 11px; color: #64748b; font-weight: 600;">Oluşturulma Tarihi</span>
							<span style="font-size: 13px; color: #334155; font-weight: 500;">
								<?php echo !empty($as["regdate"]) ? date('d.m.Y H:i', strtotime($as["regdate"])) : '-'; ?>
							</span>
						</div>
					</div>

					<!-- Başlangıç Tarihi -->
					<div class="audit-row" style="display: flex; align-items: flex-start; gap: 12px;">
						<div class="audit-icon" style="color: #3b82f6; font-size: 15px; margin-top: 2px;"><i class="fa fa-play-circle-o"></i></div>
						<div style="display: flex; flex-direction: column;">
							<span style="font-size: 11px; color: #64748b; font-weight: 600;">Başlangıç Tarihi</span>
							<span style="font-size: 13px; color: #334155; font-weight: 500;">
								<?php echo !empty($as["startdate"]) ? date('d.m.Y H:i', strtotime($as["startdate"])) : (!empty($as["regdate"]) ? date('d.m.Y', strtotime($as["regdate"])) : '-'); ?>
							</span>
						</div>
					</div>

					<!-- Sonlanma Tarihi -->
					<div class="audit-row" style="display: flex; align-items: flex-start; gap: 12px;">
						<div class="audit-icon" style="color: #ef4444; font-size: 15px; margin-top: 2px;"><i class="fa fa-calendar-times-o"></i></div>
						<div style="display: flex; flex-direction: column;">
							<span style="font-size: 11px; color: #64748b; font-weight: 600;">Son Teslim Tarihi</span>
							<span style="font-size: 13px; color: #334155; font-weight: 600;">
								<?php echo htmlspecialchars($as["lastdate"] ?? '-'); ?>
								<?php if (!empty($remainingText)): ?>
									<div class="mt-1"><?php echo $remainingText; ?></div>
								<?php endif; ?>
							</span>
						</div>
					</div>

					<!-- Tamamlanma Tarihi -->
					<?php if ((int)$as["statu"] === 1) { ?>
						<div class="audit-row" style="display: flex; align-items: flex-start; gap: 12px;">
							<div class="audit-icon" style="color: #10b981; font-size: 15px; margin-top: 2px;"><i class="fa fa-calendar-check-o"></i></div>
							<div style="display: flex; flex-direction: column;">
								<span style="font-size: 11px; color: #64748b; font-weight: 600;">Tamamlanma Tarihi</span>
								<span style="font-size: 13px; color: #047857; font-weight: 600;"><?php echo htmlspecialchars($as["okeydate"] ?? '-'); ?></span>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
function confirmCompleteInView(missionId, missionTitle) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Görevi Tamamla',
            html: '<b>"' + missionTitle + '"</b> başlıklı görevi yapıldı olarak işaretlemek istiyor musunuz?<br><small class="text-muted">Bu işlem geri alınamaz.</small>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa fa-check"></i> Evet, Tamamla',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?p=view-mission&mode=update&statu=1&mid=' + missionId;
            }
        });
    } else {
        if (confirm('"' + missionTitle + '" başlıklı görevi yapıldı olarak işaretlemek istediğinize emin misiniz?')) {
            window.location.href = 'index.php?p=view-mission&mode=update&statu=1&mid=' + missionId;
        }
    }
}
</script>
