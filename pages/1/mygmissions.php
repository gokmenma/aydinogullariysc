<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Model\MissionModel;

if (!permtrue("missionadd") && !permtrue("allmisview")) {
    permcontrol("missionadd");
}

$currentUserId = function_exists('sesset') ? sesset("id") : ($_SESSION["lid"] ?? ($_SESSION["id"] ?? 0));
$missionModel = new MissionModel();

// Görev Silme İşlemi (Soft Delete)
if (isset($_GET["id"]) && @$_GET["mode"] === "delete") {
    $delId = (int)$_GET["id"];
    $deleted = $missionModel->softDelete($delId, $currentUserId);
    if ($deleted) {
        header("Location: index.php?p=mygmissions&deleted=true");
    } else {
        header("Location: index.php?p=mygmissions&st=error");
    }
    exit;
}

// Görev listesi ve istatistikler
$givenMissions = $missionModel->getGivenMissions($currentUserId);
$stats = $missionModel->getMissionStats($givenMissions);
$usersMap = $missionModel->getUsersMap();

$today = date('Y-m-d');
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_mygmissions_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-mygmissions-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<div class="mission-module-wrapper">
    <!-- Header Card -->
    <div class="premium-header-card animate-fade-in mb-3">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon mission-header-icon" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9) !important;">
                    <i class="fa fa-paper-plane-o"></i>
                </div>
                <div class="header-title">
                    <h4>Verdiğim Görevler</h4>
                    <div class="mission-stat-pills">
                        <span class="mission-stat-pill">
                            <i class="fa fa-list-ul"></i> Toplam: <strong><?php echo $stats['total']; ?></strong>
                        </span>
                        <span class="mission-stat-pill mission-stat-pending">
                            <i class="fa fa-clock-o"></i> Bekleyen: <strong><?php echo $stats['pending']; ?></strong>
                        </span>
                        <span class="mission-stat-pill mission-stat-completed">
                            <i class="fa fa-check-circle"></i> Tamamlanan: <strong><?php echo $stats['completed']; ?></strong>
                        </span>
                        <?php if ($stats['urgent'] > 0): ?>
                            <span class="mission-stat-pill mission-stat-urgent">
                                <i class="fa fa-bolt"></i> Yüksek Acil: <strong><?php echo $stats['urgent']; ?></strong>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <?php if (permtrue("missionadd")): ?>
                    <a href="index.php?p=new-mission" class="btn-header btn-header-save">
                        <i class="fa fa-plus-circle"></i> Yeni Görev Oluştur
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Compact KPI Summary Grid (Exact Alignment) -->
    <div id="kpiSummarySection" class="mission-kpi-grid mb-3 animate-fade-in kpi-summary-collapse">
        <div class="crm-kpi-card">
            <div class="crm-kpi-header">
                <div>
                    <span class="crm-kpi-label">Verilen Görevler</span>
                    <div class="crm-kpi-value"><?php echo $stats['total']; ?></div>
                </div>
                <div class="crm-kpi-icon icon-purple">
                    <i class="fa fa-paper-plane"></i>
                </div>
            </div>
        </div>
        <div class="crm-kpi-card">
            <div class="crm-kpi-header">
                <div>
                    <span class="crm-kpi-label">İşlem Bekleyen</span>
                    <div class="crm-kpi-value"><?php echo $stats['pending']; ?></div>
                </div>
                <div class="crm-kpi-icon icon-amber">
                    <i class="fa fa-hourglass-half"></i>
                </div>
            </div>
        </div>
        <div class="crm-kpi-card">
            <div class="crm-kpi-header">
                <div>
                    <span class="crm-kpi-label">Tamamlanan</span>
                    <div class="crm-kpi-value"><?php echo $stats['completed']; ?></div>
                </div>
                <div class="crm-kpi-icon icon-emerald">
                    <i class="fa fa-check-circle-o"></i>
                </div>
            </div>
        </div>
        <div class="crm-kpi-card">
            <div class="crm-kpi-header">
                <div>
                    <span class="crm-kpi-label">Acil / Öncelikli</span>
                    <div class="crm-kpi-value"><?php echo $stats['urgent']; ?></div>
                </div>
                <div class="crm-kpi-icon icon-rose">
                    <i class="fa fa-exclamation-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if (@$_GET["deleted"] === "true"): ?>
        <div class="alert alert-success alert-dismissible fade show animate-fade-in mb-3" role="alert" style="border-radius: 10px;">
            <i class="fa fa-check-circle mr-2"></i> Görev başarıyla silindi.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="form-card mb-4 animate-fade-in mission-table-card">
        <div class="form-card-header mission-table-header">
            <div class="d-flex align-items-center">
                <div class="card-icon card-icon-purple mr-3">
                    <i class="fa fa-list-alt"></i>
                </div>
                <div>
                    <h5 class="mb-0">Verdiğim Görevler Listesi</h5>
                    <p class="mb-0">Personellere atadığınız tüm görevlerin durumunu ve ilerlemesini takip edin.</p>
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap: 8px;">
                <div id="mygSearchContainer" class="mission-search-container"></div>
                <button type="button" id="toggleKpiSummary" class="btn btn-outline-secondary btn-sm btn-kpi-toggle" title="Özet Kartlarını Gizle / Göster">
                    <i class="fa fa-chevron-up"></i>
                </button>
            </div>
        </div>

        <div class="table-responsive mission-table-responsive">
            <table id="mygMissionsTable" class="data-table select-row table-hover table-bordered premium-table">
                <thead>
                    <tr>
                        <th scope="col" class="text-center no-filter" style="width: 45px;">#Sıra</th>
                        <th data-filter-type="select" style="width: 95px;">Aciliyet</th>
                        <th data-filter-type="text">Görev & Firma</th>
                        <th data-filter-type="text" style="width: 220px;">Atanan Personel(ler)</th>
                        <th data-filter-type="date" style="width: 110px;">Başlangıç</th>
                        <th data-filter-type="date" style="width: 140px;">Son Tarih & Süre</th>
                        <th data-filter-type="select" class="text-center" style="width: 110px;">Durum</th>
                        <th scope="col" class="text-center no-filter" style="width: 130px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($givenMissions)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="empty-state-wrap">
                                    <div class="empty-icon mb-3">
                                        <i class="fa fa-paper-plane-o" style="font-size: 38px; color: #cbd5e1;"></i>
                                    </div>
                                    <h6 style="color: #64748b; font-weight: 600;">Henüz Başkasına Atadığınız Görev Yok</h6>
                                    <p class="small text-muted mb-0">Yukarıdaki "Yeni Görev Oluştur" butonunu kullanarak ekip arkadaşlarınıza görev atayabilirsiniz.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $kx = 1;
                        foreach ($givenMissions as $row):
                            $authorIds = array_filter(explode('|', $row['authors'] ?? ''));
                            $isDone = ((int)$row['statu'] === 1);
                            $urgency = trim($row['urgency'] ?? 'Orta');
                            
                            // Aciliyet Rozetleri
                            $urgencyBadgeClass = 'mission-urgency-medium';
                            $urgencyIcon = 'fa fa-circle';
                            if ($urgency === 'Yüksek') {
                                $urgencyBadgeClass = 'mission-urgency-high';
                                $urgencyIcon = 'fa fa-fire';
                            } elseif ($urgency === 'Düşük') {
                                $urgencyBadgeClass = 'mission-urgency-low';
                                $urgencyIcon = 'fa fa-leaf';
                            }

                            // Tarih & Kalan Gün Hesaplama
                            $lastDateRaw = trim($row['lastdate'] ?? '');
                            $remainingDaysBadge = '';
                            $isOverdue = false;

                            if (!empty($lastDateRaw)) {
                                $lastDateFormatted = $lastDateRaw;
                                $targetDate = null;
                                if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $lastDateRaw, $m)) {
                                    $targetDate = "{$m[3]}-{$m[2]}-{$m[1]}";
                                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', $lastDateRaw)) {
                                    $targetDate = substr($lastDateRaw, 0, 10);
                                }

                                if ($targetDate) {
                                    $diff = (int)floor((strtotime($targetDate) - strtotime($today)) / 86400);
                                    if ($isDone) {
                                        $remainingDaysBadge = '<span class="badge-sub-date text-muted"><i class="fa fa-check"></i> Tamamlandı</span>';
                                    } elseif ($diff > 0) {
                                        $badgeColor = $diff <= 2 ? 'warning' : 'info';
                                        $remainingDaysBadge = "<span class=\"badge-sub-date badge-sub-{$badgeColor}\"><i class=\"fa fa-clock-o\"></i> {$diff} gün kaldı</span>";
                                    } elseif ($diff === 0) {
                                        $remainingDaysBadge = '<span class="badge-sub-date badge-sub-warning"><i class="fa fa-exclamation-circle"></i> Bugün son gün!</span>';
                                    } else {
                                        $isOverdue = true;
                                        $overdueDays = abs($diff);
                                        $remainingDaysBadge = "<span class=\"badge-sub-date badge-sub-danger\"><i class=\"fa fa-exclamation-triangle\"></i> {$overdueDays} gün gecikti</span>";
                                    }
                                }
                            } else {
                                $lastDateFormatted = '-';
                            }

                            // Başlangıç tarihi
                            $startDateRaw = trim($row['startdate'] ?? '');
                            $startDateFormatted = !empty($startDateRaw) ? date('d.m.Y', strtotime($startDateRaw)) : '-';
                            if ($startDateFormatted === '01.01.1970' || empty($startDateRaw)) {
                                $startDateFormatted = !empty($row['regdate']) ? date('d.m.Y', strtotime($row['regdate'])) : '-';
                            }
                            // Atanan Kullanıcılar
                            $authorIds = array_filter(explode('|', $row['authors'] ?? ''));
                            $authorsData = [];
                            foreach ($authorIds as $authId) {
                                $u = $usersMap[(int)$authId] ?? null;
                                if ($u) {
                                    $authorsData[] = [
                                        'name' => htmlspecialchars($u['username'] ?? ''),
                                        'title' => htmlspecialchars($u['Unvan'] ?? ''),
                                        'initial' => mb_strtoupper(mb_substr($u['username'] ?? '', 0, 1, 'UTF-8'), 'UTF-8')
                                    ];
                                }
                            }

                            $creator = $usersMap[(int)$row['creativer']] ?? null;
                            $creatorName = $creator ? htmlspecialchars($creator['username'] ?? '') : 'Bilinmeyen';
                            $creatorTitle = $creator ? htmlspecialchars($creator['Unvan'] ?? '') : '';
                            $creatorInitial = !empty($creatorName) ? mb_strtoupper(mb_substr($creatorName, 0, 1, 'UTF-8'), 'UTF-8') : '?';

                            $missionDataJson = htmlspecialchars(json_encode([
                                'id' => (int)$row['id'],
                                'title' => $row['title'] ?? '',
                                'firma' => $row['FirmaAdi'] ?? '',
                                'category' => $row['categoryName'] ?? '',
                                'urgency' => $urgency,
                                'urgencyBadgeClass' => $urgencyBadgeClass,
                                'urgencyIcon' => $urgencyIcon,
                                'statu' => (int)$row['statu'],
                                'startDate' => $startDateFormatted,
                                'lastDate' => $lastDateFormatted,
                                'remainingBadge' => $remainingDaysBadge,
                                'okeyDate' => !empty($row['okeydate']) && $row['okeydate'] !== '-' ? date('d.m.Y H:i', strtotime($row['okeydate'])) : '',
                                'creator' => [
                                    'name' => $creatorName,
                                    'title' => $creatorTitle,
                                    'initial' => $creatorInitial
                                ],
                                'authors' => $authorsData,
                                'desc' => $row['mdesc'] ?? '',
                                'canComplete' => false
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="<?php echo $isDone ? 'mission-row-done' : ($isOverdue ? 'mission-row-overdue' : ''); ?>">
                                <td class="text-center font-weight-bold text-muted"><?php echo $kx; ?></td>
                                
                                <!-- Aciliyet -->
                                <td>
                                    <span class="mission-urgency-badge <?php echo $urgencyBadgeClass; ?>">
                                        <i class="<?php echo $urgencyIcon; ?> mr-1"></i> <?php echo htmlspecialchars($urgency); ?>
                                    </span>
                                </td>

                                <!-- Başlık & Firma -->
                                <td>
                                    <div class="mission-title-box">
                                        <a href="javascript:void(0);" 
                                           class="mission-title-link btn-preview-mission <?php echo $isDone ? 'text-decoration-line-through text-muted' : ''; ?>"
                                           data-mission='<?php echo $missionDataJson; ?>'
                                           title="Hızlı Önizleme">
                                            <?php echo htmlspecialchars($row['title'] ?? ''); ?>
                                        </a>
                                        <div class="mission-meta-tags mt-1">
                                            <?php if (!empty($row['FirmaAdi'])): ?>
                                                <span class="mission-firm-tag" title="Bağlı Firma">
                                                    <i class="fa fa-building-o"></i> <?php echo htmlspecialchars($row['FirmaAdi']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($row['categoryName'])): ?>
                                                <span class="mission-cat-tag">
                                                    <i class="fa fa-tag"></i> <?php echo htmlspecialchars($row['categoryName']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Atanan Personel(ler) -->
                                <td>
                                    <div class="mission-assignees-wrap">
                                        <?php if (empty($authorIds)): ?>
                                            <span class="text-muted small">Atanan yok</span>
                                        <?php else: ?>
                                            <?php foreach ($authorIds as $authId): 
                                                $u = $usersMap[(int)$authId] ?? null;
                                                $uName = $u ? htmlspecialchars($u['username'] ?? '') : 'Kullanıcı #' . $authId;
                                                $uInit = !empty($uName) ? mb_strtoupper(mb_substr($uName, 0, 1, 'UTF-8'), 'UTF-8') : '?';
                                            ?>
                                                <div class="mission-user-chip" title="<?php echo $uName . (!empty($u['Unvan']) ? ' - ' . htmlspecialchars($u['Unvan']) : ''); ?>">
                                                    <span class="chip-avatar"><?php echo $uInit; ?></span>
                                                    <span class="chip-name"><?php echo $uName; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Başlangıç Tarihi -->
                                <td>
                                    <span class="mission-date-text">
                                        <i class="fa fa-calendar-o mr-1 text-muted"></i> <?php echo $startDateFormatted; ?>
                                    </span>
                                </td>

                                <!-- Son Tarih & Kalan Süre -->
                                <td>
                                    <div class="mission-deadline-box">
                                        <span class="mission-date-text font-weight-600 <?php echo ($isOverdue && !$isDone) ? 'text-danger' : ''; ?>">
                                            <i class="fa fa-calendar-check-o mr-1"></i> <?php echo htmlspecialchars($lastDateFormatted); ?>
                                        </span>
                                        <?php echo $remainingDaysBadge; ?>
                                    </div>
                                </td>

                                <!-- Durum -->
                                <td class="text-center">
                                    <?php if ($isDone): ?>
                                        <span class="mission-status-badge mission-status-done">
                                            <i class="fa fa-check-circle mr-1"></i> Tamamlandı
                                        </span>
                                    <?php elseif ($isOverdue): ?>
                                        <span class="mission-status-badge mission-status-overdue">
                                            <i class="fa fa-exclamation-triangle mr-1"></i> Gecikti
                                        </span>
                                    <?php else: ?>
                                        <span class="mission-status-badge mission-status-pending">
                                            <i class="fa fa-clock-o mr-1"></i> Bekliyor
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- İşlemler -->
                                <td class="text-center">
                                    <div class="mission-action-buttons">
                                        <button type="button" 
                                                class="btn-table-action btn-table-view btn-preview-mission" 
                                                title="Görevi Hızlı Önizle"
                                                data-mission='<?php echo $missionDataJson; ?>'>
                                            <i class="fa fa-eye"></i> <span>Görüntüle</span>
                                        </button>

                                        <button type="button" 
                                                class="btn-table-action btn-table-delete" 
                                                title="Görevi Sil" 
                                                onclick="confirmDeleteMission(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['title'])); ?>')">
                                            <i class="fa fa-trash-o"></i> <span>Sil</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php
                            $kx++;
                        endforeach;
                        ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Hızlı Önizleme Modalı -->
<div class="modal fade" id="missionPreviewModal" tabindex="-1" aria-labelledby="missionPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content premium-modal-content">
            <div class="modal-header premium-modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3 min-w-0 pr-3">
                    <div class="modal-header-icon" id="previewModalIcon">
                        <i class="fa fa-clipboard"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="preview-modal-id-badge" id="previewModalId"></div>
                        <h5 class="modal-title mb-0 font-weight-bold text-truncate" id="missionPreviewModalLabel">Görev Detayı</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap" id="previewModalBadges">
                        <span id="previewModalUrgency"></span>
                        <span id="previewModalStatus"></span>
                    </div>
                    <button type="button" class="modal-close-btn ml-1" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
            </div>
            <div class="modal-body p-4">
                <!-- Meta Bilgi Grid -->
                <div class="preview-meta-grid">
                    <div class="preview-meta-item" id="previewMetaFirmBox">
                        <div class="preview-meta-icon text-primary"><i class="fa fa-building-o"></i></div>
                        <div class="preview-meta-content">
                            <span class="preview-meta-label">Firma</span>
                            <strong class="preview-meta-val" id="previewModalFirm">-</strong>
                        </div>
                    </div>
                    <div class="preview-meta-item" id="previewMetaCatBox">
                        <div class="preview-meta-icon text-info"><i class="fa fa-tag"></i></div>
                        <div class="preview-meta-content">
                            <span class="preview-meta-label">Kategori</span>
                            <strong class="preview-meta-val" id="previewModalCategory">-</strong>
                        </div>
                    </div>
                    <div class="preview-meta-item">
                        <div class="preview-meta-icon text-purple" style="color: #8b5cf6;"><i class="fa fa-user-circle-o"></i></div>
                        <div class="preview-meta-content">
                            <span class="preview-meta-label">Görevi Veren</span>
                            <strong class="preview-meta-val" id="previewModalCreator">-</strong>
                        </div>
                    </div>
                    <div class="preview-meta-item">
                        <div class="preview-meta-icon text-success"><i class="fa fa-calendar-check-o"></i></div>
                        <div class="preview-meta-content">
                            <span class="preview-meta-label">Zamanlama</span>
                            <div class="preview-meta-val" id="previewModalDates">-</div>
                        </div>
                    </div>
                </div>

                <!-- Göreve Atanan Personeller -->
                <div class="preview-section" id="previewAuthorsSection">
                    <label class="preview-section-title"><i class="fa fa-users text-muted mr-1"></i> Göreve Atanan Personeller</label>
                    <div class="preview-authors-list d-flex flex-wrap gap-2" id="previewModalAuthors"></div>
                </div>

                <!-- Görev Açıklaması -->
                <div class="preview-section mb-0">
                    <label class="preview-section-title"><i class="fa fa-align-left text-muted mr-1"></i> Görev Açıklaması & Notlar</label>
                    <div class="preview-desc-box" id="previewModalDesc"></div>
                </div>
            </div>
            <div class="modal-footer premium-modal-footer d-flex justify-content-between">
                <a href="#" id="previewModalFullLink" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-external-link mr-1"></i> Tam Sayfada Görüntüle
                </a>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Flicker Prevention */
.kpi-mygmissions-collapsed-early #kpiSummarySection {
    display: none !important;
}

/* Mission Module Styles - Premium Theme */
.mission-module-wrapper {
    position: relative;
    width: 100%;
}

.mission-header-icon {
    color: #fff !important;
}

/* Header Stat Pills - High Contrast */
.mission-stat-pills {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}

.mission-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 16px;
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
    font-size: 11.5px;
    font-weight: 600;
}

.mission-stat-pending {
    background: #fef3c7;
    color: #92400e;
    border-color: #fde68a;
}

.mission-stat-completed {
    background: #dcfce7;
    color: #166534;
    border-color: #bbf7d0;
}

.mission-stat-urgent {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fecaca;
}

.mission-stat-overdue {
    background: #ffe4e6;
    color: #9f1239;
    border-color: #fecdd3;
}

/* Mission KPI Grid (Exact Alignment) */
.mission-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    width: 100%;
    margin-left: 0;
    margin-right: 0;
}

.crm-kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.crm-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.crm-kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.crm-kpi-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    display: block;
    margin-bottom: 2px;
}

.crm-kpi-value {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
}

.crm-kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.icon-primary { background: #eff6ff; color: #2563eb; }
.icon-amber   { background: #fffbeb; color: #d97706; }
.icon-emerald { background: #ecfdf5; color: #16a34a; }
.icon-rose    { background: #fef2f2; color: #dc2626; }
.icon-purple  { background: #f5f3ff; color: #7c3aed; }

/* Table Card */
.mission-table-card {
    padding: 0 !important;
    overflow: hidden;
    border-radius: 12px;
}

.mission-table-header {
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 14px 18px !important;
    margin: 0 !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

.mission-table-header .card-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mission-table-header h5 {
    font-size: 15px !important;
    font-weight: 700 !important;
    color: #1e293b !important;
    margin: 0 !important;
}

.mission-table-header p {
    color: #64748b !important;
    font-size: 12px !important;
    margin: 2px 0 0 0 !important;
}

.mission-search-container {
    margin-left: auto;
}

.btn-kpi-toggle {
    border-radius: 8px;
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-color: #cbd5e1;
    color: #475569;
}

.btn-kpi-toggle:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #94a3b8;
}

.mission-table-responsive {
    padding: 0 !important;
    margin: 0 !important;
}

#mygMissionsTable {
    width: 100%;
    margin: 0 !important;
}

#mygMissionsTable td {
    vertical-align: middle;
    padding: 10px 12px;
}

/* Urgency Badges */
.mission-urgency-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 3px 8px;
    border-radius: 16px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}

.mission-urgency-high {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
}

.mission-urgency-medium {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.mission-urgency-low {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

/* Title & Firm */
.mission-title-box {
    display: flex;
    flex-direction: column;
}

.mission-title-link {
    color: #0f172a;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.15s ease;
}

.mission-title-link:hover {
    color: #2563eb;
}

.mission-meta-tags {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}

.mission-firm-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 500;
    color: #0369a1;
    background: #f0f9ff;
    padding: 2px 7px;
    border-radius: 5px;
    border: 1px solid #e0f2fe;
}

.mission-cat-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 500;
    color: #475569;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 5px;
}

/* Assignees Chips */
.mission-assignees-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.mission-user-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 7px 2px 3px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    font-size: 11.5px;
    color: #334155;
}

.chip-avatar {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chip-name {
    font-weight: 500;
    white-space: nowrap;
}

/* Date Text & Deadline */
.mission-date-text {
    font-size: 12px;
    color: #334155;
    display: inline-flex;
    align-items: center;
}

.mission-deadline-box {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.badge-sub-date {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10.5px;
    font-weight: 600;
    padding: 1px 5px;
    border-radius: 4px;
    width: fit-content;
}

.badge-sub-info {
    background: #f0fdf4;
    color: #166534;
}

.badge-sub-warning {
    background: #fffbeb;
    color: #b45309;
}

.badge-sub-danger {
    background: #fef2f2;
    color: #b91c1c;
}

/* Status Badges */
.mission-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 9px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.mission-status-done {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #d1fae5;
}

.mission-status-pending {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #dbeafe;
}

.mission-status-overdue {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fee2e2;
}

/* Action Buttons */
.mission-action-buttons {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.btn-table-action {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    text-decoration: none !important;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-table-view {
    background: #f1f5f9;
    color: #334155;
}

.btn-table-view:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.btn-table-delete {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.btn-table-delete:hover {
    background: #fee2e2;
    color: #b91c1c;
}

/* Row modifiers */
.mission-row-done td {
    opacity: 0.75;
}

/* Dark Mode Styles */
.dark-mode .mission-stat-pill {
    background: #1e293b;
    color: #cbd5e1;
    border-color: #334155;
}

.dark-mode .mission-stat-pending {
    background: rgba(245, 158, 11, 0.2);
    color: #fcd34d;
    border-color: rgba(245, 158, 11, 0.4);
}

.dark-mode .mission-stat-completed {
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    border-color: rgba(34, 197, 94, 0.4);
}

.dark-mode .mission-stat-urgent {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border-color: rgba(239, 68, 68, 0.4);
}

.dark-mode .mission-stat-overdue {
    background: rgba(220, 38, 38, 0.25);
    color: #fecdd3;
    border-color: rgba(220, 38, 38, 0.45);
}

.dark-mode .crm-kpi-card {
    background: #1e293b;
    border-color: #334155;
}

.dark-mode .crm-kpi-label {
    color: #94a3b8;
}

.dark-mode .crm-kpi-value {
    color: #f1f5f9;
}

.dark-mode .icon-primary { background: rgba(37, 99, 235, 0.2); color: #60a5fa; }
.dark-mode .icon-amber   { background: rgba(217, 119, 6, 0.2); color: #fbbf24; }
.dark-mode .icon-emerald { background: rgba(22, 163, 74, 0.2); color: #4ade80; }
.dark-mode .icon-rose    { background: rgba(220, 38, 38, 0.2); color: #f87171; }
.dark-mode .icon-purple  { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; }

.dark-mode .mission-table-header {
    border-bottom-color: #334155 !important;
}

.dark-mode .mission-table-header h5 {
    color: #f1f5f9 !important;
}

.dark-mode .mission-table-header p {
    color: #94a3b8 !important;
}

.dark-mode .btn-kpi-toggle {
    border-color: #475569;
    color: #cbd5e1;
}

.dark-mode .btn-kpi-toggle:hover {
    background: #334155;
    color: #f8fafc;
}

.dark-mode .mission-title-link {
    color: #f1f5f9;
}

.dark-mode .mission-title-link:hover {
    color: #60a5fa;
}

.dark-mode .mission-firm-tag {
    background: #1e3a5f;
    border-color: #334f70;
    color: #93c5fd;
}

.dark-mode .mission-cat-tag {
    background: #334155;
    color: #cbd5e1;
}

.dark-mode .mission-user-chip {
    background: #334155;
    border-color: #475569;
    color: #e2e8f0;
}

.dark-mode .mission-date-text {
    color: #cbd5e1;
}

.dark-mode .btn-table-view {
    background: #334155;
    color: #e2e8f0;
}

.dark-mode .btn-table-view:hover {
    background: #475569;
    color: #ffffff;
}

.dark-mode .btn-table-delete {
    background: rgba(220, 38, 38, 0.2);
    border-color: rgba(220, 38, 38, 0.4);
    color: #fca5a5;
}

.dark-mode .btn-table-delete:hover {
    background: rgba(220, 38, 38, 0.35);
    color: #fecaca;
}

@media (max-width: 991px) {
    .mission-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575px) {
    .mission-kpi-grid {
        grid-template-columns: 1fr;
    }
    .mission-stat-pills {
        flex-direction: column;
        align-items: flex-start;
    }
    .mission-search-container {
        width: 100%;
        margin-left: 0;
    }
    .btn-table-action span {
        display: none;
    }
    .btn-table-action {
        padding: 5px 8px;
    }
}
</style>

<script src="include/js/data-table.js"></script>
<script>
function confirmDeleteMission(missionId, missionTitle) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Görevi Sil',
            html: '<b>"' + missionTitle + '"</b> başlıklı görevi silmek istediğinize emin misiniz?<br><small class="text-muted">Bu işlem geri alınamaz.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa fa-trash"></i> Evet, Sil',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?p=mygmissions&mode=delete&id=' + missionId;
            }
        });
    } else {
        if (confirm('"' + missionTitle + '" başlıklı görevi silmek istediğinize emin misiniz?')) {
            window.location.href = 'index.php?p=mygmissions&mode=delete&id=' + missionId;
        }
    }
}

$(document).ready(function () {
    // KPI Kartları Göster / Gizle Mantığı
    var KPI_STORAGE_KEY = 'aydinogullari_kpi_mygmissions_collapsed';
    var $kpiSection = $('#kpiSummarySection');
    var $toggleBtn = $('#toggleKpiSummary');

    function updateKpiToggleState(isCollapsed, animate) {
        if (isCollapsed) {
            if (animate) {
                $kpiSection.slideUp(200);
            } else {
                $kpiSection.hide();
            }
            $toggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $toggleBtn.attr('title', 'Özet Kartlarını Göster');
        } else {
            if (animate) {
                $kpiSection.slideDown(200);
            } else {
                $kpiSection.show();
            }
            $toggleBtn.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $toggleBtn.attr('title', 'Özet Kartlarını Gizle');
        }
    }

    var savedState = localStorage.getItem(KPI_STORAGE_KEY) === 'true';
    updateKpiToggleState(savedState, false);

    $toggleBtn.on('click', function () {
        var currentState = localStorage.getItem(KPI_STORAGE_KEY) === 'true';
        var newState = !currentState;
        localStorage.setItem(KPI_STORAGE_KEY, newState);
        updateKpiToggleState(newState, true);
    });

    function initMygMissionsTable() {
        $("#mygMissionsTable").find("tr.search-input-row").remove();

        var filterEl = $("#mygMissionsTable_wrapper .dataTables_filter");
        if (filterEl.length && !$("#mygSearchContainer .dataTables_filter").length) {
            filterEl.appendTo("#mygSearchContainer");
            if (filterEl[0]) filterEl[0].dataset.relocated = 'true';
        }

        if (window.App && window.App.TableFilter) {
            App.TableFilter.attachToTable(document.getElementById("mygMissionsTable"));
        }
    }

    // Görev Hızlı Önizleme Modalı Açma
    $(document).on('click', '.btn-preview-mission', function (e) {
        e.preventDefault();
        var data = $(this).data('mission');
        if (typeof data === 'string') {
            try {
                data = JSON.parse(data);
            } catch (err) {
                console.error('JSON parse error:', err);
                return;
            }
        }
        if (!data) return;

        $('#missionPreviewModalLabel').text(data.title || 'Görev Detayı');
        $('#previewModalId').text('#' + data.id);
        
        // Aciliyet Rozeti (Pill)
        var urgClass = 'mission-pill-badge urgency-medium';
        var urgIcon = 'fa fa-circle';
        if (data.urgency === 'Yüksek') {
            urgClass = 'mission-pill-badge urgency-high';
            urgIcon = 'fa fa-bolt';
        } else if (data.urgency === 'Düşük') {
            urgClass = 'mission-pill-badge urgency-low';
            urgIcon = 'fa fa-circle';
        }

        $('#previewModalUrgency')
            .attr('class', urgClass)
            .html('<i class="' + (data.urgencyIcon || urgIcon) + ' mr-1"></i> ' + (data.urgency || 'Orta'));

        // Durum Rozeti (Pill)
        if (data.statu === 1) {
            $('#previewModalStatus')
                .attr('class', 'mission-pill-badge status-done')
                .html('<i class="fa fa-check-circle mr-1"></i> Tamamlandı');
        } else {
            $('#previewModalStatus')
                .attr('class', 'mission-pill-badge status-pending')
                .html('<i class="fa fa-clock-o mr-1"></i> Bekliyor');
        }

        // Firma
        if (data.firma && data.firma.trim() !== '') {
            $('#previewModalFirm').text(data.firma);
            $('#previewMetaFirmBox').show();
        } else {
            $('#previewModalFirm').text('-');
            $('#previewMetaFirmBox').hide();
        }

        // Kategori
        if (data.category && data.category.trim() !== '') {
            $('#previewModalCategory').text(data.category);
            $('#previewMetaCatBox').show();
        } else {
            $('#previewModalCategory').text('-');
            $('#previewMetaCatBox').hide();
        }

        // Görevi Veren
        var creatorHtml = (data.creator && data.creator.name) ? data.creator.name : 'Bilinmeyen';
        if (data.creator && data.creator.title) {
            creatorHtml += ' <small class="text-muted">(' + data.creator.title + ')</small>';
        }
        $('#previewModalCreator').html(creatorHtml);

        // Tarihler & Kalan Süre
        var datesHtml = '<span class="text-muted">Başlangıç:</span> ' + (data.startDate || '-');
        if (data.lastDate && data.lastDate !== '-') {
            datesHtml += ' &bull; <span class="text-muted">Bitiş:</span> ' + data.lastDate;
        }
        if (data.remainingBadge) {
            datesHtml += '<div class="mt-1">' + data.remainingBadge + '</div>';
        }
        if (data.statu === 1 && data.okeyDate) {
            datesHtml += '<div class="mt-1 small text-success"><i class="fa fa-check"></i> ' + data.okeyDate + ' tarihinde tamamlandı</div>';
        }
        $('#previewModalDates').html(datesHtml);

        // Atanan Personeller
        if (data.authors && data.authors.length > 0) {
            var authorsHtml = '';
            data.authors.forEach(function(u) {
                authorsHtml += '<div class="mission-user-chip mr-1 mb-1">' +
                    '<span class="chip-avatar">' + (u.initial || '?') + '</span>' +
                    '<span class="chip-name">' + u.name + (u.title ? ' <small class="text-muted">(' + u.title + ')</small>' : '') + '</span>' +
                '</div>';
            });
            $('#previewModalAuthors').html(authorsHtml);
            $('#previewAuthorsSection').show();
        } else {
            $('#previewAuthorsSection').hide();
        }

        // Açıklama
        if (data.desc && data.desc.trim() !== '') {
            $('#previewModalDesc').html(data.desc);
        } else {
            $('#previewModalDesc').html('<em class="text-muted">Açıklama belirtilmemiş.</em>');
        }

        // Tam Sayfa Linki
        $('#previewModalFullLink').attr('href', 'index.php?p=view-mission&mid=' + data.id);

        $('#missionPreviewModal').modal('show');
    });

    initMygMissionsTable();
    setTimeout(initMygMissionsTable, 100);
    setTimeout(initMygMissionsTable, 300);
});
</script>