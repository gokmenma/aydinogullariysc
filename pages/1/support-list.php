<?php
permcontrol("support-request-view");

$userId = $_SESSION["lid"];
$isAdmin = permtrue("support-request-process");
$isApprover = permtrue("support-request-approve");

if ($isAdmin) {
    $queryStr = "SELECT sr.*, u.username as requester_name 
                 FROM support_requests sr 
                 LEFT JOIN users u ON u.id = sr.created_by 
                 ORDER BY sr.id DESC";
    $query = $ac->prepare($queryStr);
    $query->execute();
} elseif ($isApprover) {
    $queryStr = "SELECT sr.*, u.username as requester_name 
                 FROM support_requests sr 
                 LEFT JOIN users u ON u.id = sr.created_by 
                 WHERE sr.status = 'pending_approval' OR sr.created_by = ? 
                 ORDER BY sr.id DESC";
    $query = $ac->prepare($queryStr);
    $query->execute([$userId]);
} else {
    $queryStr = "SELECT sr.*, u.username as requester_name 
                 FROM support_requests sr 
                 LEFT JOIN users u ON u.id = sr.created_by 
                 WHERE sr.created_by = ? 
                 ORDER BY sr.id DESC";
    $query = $ac->prepare($queryStr);
    $query->execute([$userId]);
}

if (@$_GET["st"] == "newsuccess") {
    showAlert("success", "Destek talebiniz başarıyla oluşturuldu ve onaya gönderildi.");
}

$supportRequests = $query->fetchAll(PDO::FETCH_ASSOC);
$totalCount = count($supportRequests);
$pendingCount = 0;
$inProgressCount = 0;
$completedCount = 0;

foreach ($supportRequests as $supportRequest) {
    if ($supportRequest["status"] === "pending_approval") {
        $pendingCount++;
    } elseif (in_array($supportRequest["status"], ["approved", "in_progress"], true)) {
        $inProgressCount++;
    } elseif ($supportRequest["status"] === "completed") {
        $completedCount++;
    }
}
?>

<div class="support-list-wrapper">
    <div class="premium-header-card animate-fade-in">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon support-header-icon">
                    <i class="fa fa-life-ring"></i>
                </div>
                <div class="header-title">
                    <h4>Destek Talepleri</h4>
                    <div class="support-stat-pills">
                        <span class="support-stat-pill"><i class="fa fa-list-ul"></i> Toplam: <strong><?php echo $totalCount; ?></strong></span>
                        <span class="support-stat-pill support-stat-pending"><i class="fa fa-clock-o"></i> Onay Bekleyen: <strong><?php echo $pendingCount; ?></strong></span>
                        <span class="support-stat-pill support-stat-progress"><i class="fa fa-spinner"></i> Aktif: <strong><?php echo $inProgressCount; ?></strong></span>
                        <span class="support-stat-pill support-stat-completed"><i class="fa fa-check-circle"></i> Tamamlanan: <strong><?php echo $completedCount; ?></strong></span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <?php if (permtrue("support-request-add")) { ?>
                    <a href="index.php?p=support-new" class="btn-header btn-header-save">
                        <i class="fa fa-plus-circle"></i> Yeni Destek Talebi
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="form-card mb-4 animate-fade-in support-table-card">
        <div class="form-card-header support-table-header">
            <div class="d-flex align-items-center">
                <div class="card-icon card-icon-blue mr-3">
                    <i class="fa fa-ticket"></i>
                </div>
                <div>
                    <h5 class="mb-0">Talep Listesi</h5>
                    <p class="mb-0">Destek taleplerini inceleyin, durumlarını takip edin ve detaylarına ulaşın.</p>
                </div>
            </div>
            <div id="supportSearchContainer" class="support-search-container"></div>
        </div>

        <div class="table-responsive support-table-responsive">
        <table id="supportTable" class="data-table select-row table-hover table-bordered premium-table">
            <thead>
                <tr>
                    <th scope="col" class="text-center no-filter">#Sıra</th>
                    <th data-filter-type="text">Talep No</th>
                    <th data-filter-type="text">Başlık</th>
                    <th data-filter-type="select">Kategori</th>
                    <th data-filter-type="select">Aciliyet</th>
                    <th data-filter-type="select">Talep Eden</th>
                    <th data-filter-type="select" class="text-center">Durum</th>
                    <th data-filter-type="date">Oluşturma Tarihi</th>
                    <th class="datatable-nosort no-filter text-center">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $kx = 1;
                foreach ($supportRequests as $row) {
                    // Durum Badgelerini Hazırla
                    $statusBadge = '';
                    switch ($row["status"]) {
                        case 'pending_approval':
                            $statusBadge = '<span class="support-badge support-badge-pending"><i class="fa fa-clock-o"></i> Onay Bekliyor</span>';
                            break;
                        case 'approved':
                            $statusBadge = '<span class="support-badge support-badge-approved"><i class="fa fa-check"></i> Onaylandı</span>';
                            break;
                        case 'rejected':
                            $statusBadge = '<span class="support-badge support-badge-rejected"><i class="fa fa-times"></i> Reddedildi</span>';
                            break;
                        case 'in_progress':
                            $statusBadge = '<span class="support-badge support-badge-progress"><i class="fa fa-spinner"></i> İşlemde</span>';
                            break;
                        case 'completed':
                            $statusBadge = '<span class="support-badge support-badge-completed"><i class="fa fa-check-circle"></i> Tamamlandı</span>';
                            break;
                        default:
                            $statusBadge = '<span class="support-badge"><i class="fa fa-minus-circle"></i> Belirsiz</span>';
                    }
                    
                    // Aciliyet Renklendirme
                    $urgencyText = htmlspecialchars($row["urgency"], ENT_QUOTES, 'UTF-8');
                    $urgencyCell = $urgencyText;
                    if ($row["urgency"] == "Çok Acil") {
                        $urgencyCell = '<span class="text-danger font-weight-bold"><i class="fa fa-exclamation-triangle"></i> Çok Acil</span>';
                    } elseif ($row["urgency"] == "Acil") {
                        $urgencyCell = '<span class="text-warning font-weight-bold">' . $urgencyText . '</span>';
                    } elseif ($row["urgency"] == "Normal") {
                        $urgencyCell = '<span class="text-primary">' . $urgencyText . '</span>';
                    } else {
                        $urgencyCell = '<span class="text-success">' . $urgencyText . '</span>';
                    }
                ?>
                    <tr>
                        <td class="text-center"><?php echo $kx; ?></td>
                        <td><span class="support-ticket-no"><i class="fa fa-ticket"></i> <?php echo htmlspecialchars($row["ticket_no"], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><a class="support-title-link" href="index.php?p=support-detail&amp;id=<?php echo (int)$row["id"]; ?>"><?php echo htmlspecialchars($row["title"], ENT_QUOTES, 'UTF-8'); ?></a></td>
                        <td><span class="support-category"><i class="fa fa-folder-o"></i> <?php echo htmlspecialchars($row["category"], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo $urgencyCell; ?></td>
                        <td><span class="support-requester"><i class="fa fa-user-circle-o"></i> <?php echo htmlspecialchars($row["requester_name"] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td class="text-center"><?php echo $statusBadge; ?></td>
                        <td><span class="support-date"><i class="fa fa-calendar-o"></i> <?php echo date("d-m-Y H:i", strtotime($row["created_at"])); ?></span></td>
                        <td class="text-center support-actions">
                            <a href="index.php?p=support-detail&amp;id=<?php echo (int)$row["id"]; ?>" class="btn btn-sm btn-outline-info action-btn" data-tooltip="Detay">
                                <i class="fa fa-eye"></i>
                            </a>
                            <?php if ($isAdmin || ($row["created_by"] == $userId && $row["status"] == 'pending_approval')) { ?>
                                <button class="btn btn-sm btn-outline-danger action-btn" data-tooltip="Sil" onClick="deleteRecord('Bu destek talebini tamamen silmek istediğinize emin misiniz?', <?php echo (int)$row["id"]; ?>, 'support-list', 'support_requests')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php
                    $kx++;
                }
                ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<style>
.support-header-icon { background: linear-gradient(135deg, #0ea5e9, #2563eb) !important; color: #fff; }
.support-stat-pills { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-top: 7px; }
.support-stat-pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; background: rgba(255,255,255,.16); color: #eaf4ff; font-size: 12px; backdrop-filter: blur(8px); }
.support-stat-pending { background: rgba(245,158,11,.24); }
.support-stat-progress { background: rgba(14,165,233,.24); }
.support-stat-completed { background: rgba(34,197,94,.24); }
.support-table-card { padding: 0 !important; overflow: hidden; border-radius: 12px; }
.support-table-header { justify-content: space-between; flex-wrap: wrap; gap: 15px; padding: 16px 20px !important; margin: 0 !important; border-bottom: 1px solid #f1f5f9 !important; }
.support-table-header .card-icon { width: 38px; height: 38px; border-radius: 10px; font-size: 16px; display: flex; align-items: center; justify-content: center; }
.support-table-header h5 { font-size: 16px; font-weight: 700; }
.support-table-header p { color: #64748b; font-size: 12.5px; }
.support-search-container { margin-left: auto; }
.support-table-responsive { padding: 0 !important; margin: 0 !important; }
#supportTable { width: 100%; margin: 0 !important; }
#supportTable td { vertical-align: middle; }
.support-ticket-no, .support-category, .support-requester, .support-date { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
.support-ticket-no { color: #2563eb; font-weight: 700; }
.support-title-link { color: #1e293b; font-weight: 600; text-decoration: none; }
.support-title-link:hover { color: #2563eb; }
.support-category { padding: 4px 9px; border: 1px solid #dbeafe; border-radius: 7px; background: #eff6ff; color: #1d4ed8; font-size: 12px; }
.support-requester, .support-date { color: #475569; font-size: 12.5px; }
.support-badge { display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-width: 108px; padding: 6px 9px; border-radius: 20px; background: #f1f5f9; color: #475569; font-size: 11.5px; font-weight: 700; white-space: nowrap; }
.support-badge-pending { background: #fff7ed; color: #c2410c; }
.support-badge-approved { background: #ecfeff; color: #0e7490; }
.support-badge-rejected { background: #fef2f2; color: #b91c1c; }
.support-badge-progress { background: #eef2ff; color: #4338ca; }
.support-badge-completed { background: #ecfdf5; color: #047857; }
.support-actions { white-space: nowrap; }
.support-actions .action-btn { display: inline-flex; align-items: center; justify-content: center; margin: 0 2px; }
.dark-mode .support-table-header { border-bottom-color: #334155 !important; }
.dark-mode .support-title-link { color: #f1f5f9; }
.dark-mode .support-category { background: #1e3a5f; border-color: #334f70; color: #bfdbfe; }
.dark-mode .support-requester, .dark-mode .support-date { color: #cbd5e1; }
.dark-mode .support-badge-pending { background: rgba(245,158,11,.16); color: #fbbf24; }
.dark-mode .support-badge-approved { background: rgba(6,182,212,.16); color: #67e8f9; }
.dark-mode .support-badge-rejected { background: rgba(239,68,68,.16); color: #fca5a5; }
.dark-mode .support-badge-progress { background: rgba(99,102,241,.18); color: #c7d2fe; }
.dark-mode .support-badge-completed { background: rgba(34,197,94,.16); color: #86efac; }
@media (max-width: 767px) {
    .support-list-wrapper .premium-header-card { padding: 20px; }
    .support-list-wrapper .header-left { align-items: flex-start; }
    .support-list-wrapper .header-actions, .support-list-wrapper .btn-header { width: 100%; justify-content: center; }
    .support-search-container { width: 100%; margin-left: 0; }
}
</style>

<script src="include/js/data-table.js"></script>
<script>
$(document).ready(function () {
    function initSupportTable() {
        $("#supportTable").find("tr.search-input-row").remove();

        var filterEl = $("#supportTable_wrapper .dataTables_filter");
        if (filterEl.length && !$("#supportSearchContainer .dataTables_filter").length) {
            filterEl.appendTo("#supportSearchContainer");
        }

        if (window.App && window.App.TableFilter) {
            App.TableFilter.attachToTable(document.getElementById("supportTable"));
        }
    }

    initSupportTable();
    setTimeout(initSupportTable, 100);
    setTimeout(initSupportTable, 300);
});
</script>
