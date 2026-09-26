<?php

if (@$_GET["id"] && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
	permcontrol("userdelete");

	if (@$_GET["id"] == sesset("id") || $_GET["id"] == 1) {
		header("Location: index.php?p=users&st=cannotdeleted");
		exit;
	}
	$cdid = (int)$_GET["id"];
	$contq = $ac->prepare("SELECT * FROM users WHERE id = ?");
	$contq->execute(array($cdid));

	if ($contq->fetch(PDO::FETCH_ASSOC)) {
		$deletq = $ac->prepare("DELETE FROM users WHERE id = ?");
		$deletq->execute(array($cdid));
		if ($deletq) {
			header("Location: index.php?p=users&uid=$cdid&type=delete");
			exit;
		}
	}
}

if (@$_GET["id"] && @$_GET["mode"] == "updatest") {
	permcontrol("useredit");
	if (@$_GET["id"] == sesset("id") || $_GET["id"] == 1) {
		header("Location: index.php?p=users&st=cannotupdate");
		exit;
	}
	$cdid = (int)$_GET["id"];
	$gunc = @$_GET["stu"];
	if ($gunc != 1 && $gunc != 0) {
		header("Location:index.php?p=users");
		exit;
	}

	$contq = $ac->prepare("UPDATE users SET statu = ? WHERE id = ?");
	$contq->execute(array($gunc, $cdid));

	header("Location:index.php?p=users");
	exit;
}

// KPI İstatistikleri
$statsQuery = $ac->query("
	SELECT 
		COUNT(*) as total_users,
		SUM(CASE WHEN statu = 1 THEN 1 ELSE 0 END) as active_users,
		SUM(CASE WHEN statu = 0 THEN 1 ELSE 0 END) as passive_users,
		SUM(CASE WHEN permission = 1 THEN 1 ELSE 0 END) as admin_users
	FROM users
");
$stats = $statsQuery ? $statsQuery->fetch(PDO::FETCH_ASSOC) : [];
$totalUsers = (int)($stats['total_users'] ?? 0);
$activeUsers = (int)($stats['active_users'] ?? 0);
$passiveUsers = (int)($stats['passive_users'] ?? 0);
$adminUsers = (int)($stats['admin_users'] ?? 0);
$activeRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0;

try {
    $logger = \getLogger("Kullanıcılar");
    $logger->info("Ekip üyeleri listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_users_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-users-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* ==========================================
       PREMIUM USERS / TEAM LIST THEME
       ========================================== */
    .kpi-users-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    .users-list-wrapper {
        width: 100%;
        padding: 0;
        margin: 0;
    }

    .users-list-page-container {
        padding: 0;
        width: 100%;
    }

    /* Page Header Styles */
    .page-title-box {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .page-title-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.28);
    }
    .page-title-text h4 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.3px;
    }
    .page-title-text p {
        margin: 2px 0 0 0;
        font-size: 13px;
        color: #64748b;
    }

    /* KPI Summary Cards */
    .crm-kpi-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 18px 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .crm-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.07);
    }
    .crm-kpi-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .crm-kpi-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        display: block;
        margin-bottom: 4px;
    }
    .crm-kpi-value {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .crm-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .icon-indigo  { background: #e0e7ff; color: #4338ca; }
    .icon-emerald { background: #ecfdf5; color: #059669; }
    .icon-rose    { background: #ffe4e6; color: #e11d48; }
    .icon-amber   { background: #fffbeb; color: #d97706; }

    .crm-kpi-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
        font-size: 11.5px;
    }
    .crm-badge-soft {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 11px;
    }
    .soft-indigo  { background: #e0e7ff; color: #3730a3; }
    .soft-emerald { background: #d1fae5; color: #065f46; }
    .soft-rose    { background: #ffe4e6; color: #9f1239; }
    .soft-amber   { background: #fef3c7; color: #92400e; }

    /* Form & Table Card styling */
    .form-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 4px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        margin-bottom: 0;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 12px;
    }
    .form-card-header .header-left-inner {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .form-card-header .card-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        background: #f1f5f9;
        color: #475569;
    }
    .form-card-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    .form-card-header p {
        margin: 2px 0 0 0;
        font-size: 12.5px;
        color: #64748b;
    }

    /* Action Buttons in Header */
    .btn-action-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: #fff !important;
        border: none;
        border-radius: 8px;
        padding: 8px 18px;
        font-weight: 600;
        font-size: 13.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.28);
        transition: all 0.2s ease;
        height: 38px;
        text-decoration: none;
    }
    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.38);
        color: #fff !important;
    }
    .btn-action-outline {
        border-radius: 8px;
        padding: 8px 14px;
        height: 38px;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }

    /* DataTables Container & Reset Spacing */
    .form-card .table-responsive {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
    }
    .form-card .dataTables_wrapper {
        padding: 0 !important;
    }
    .form-card .dataTables_wrapper .row:first-child {
        display: none !important;
        margin: 0 !important;
    }
    .form-card .dataTables_wrapper .row:last-child {
        padding: 12px 18px;
        margin: 0;
        border-top: 1px solid #f1f5f9;
        background: #fafafa;
    }

    /* Table Base Styling */
    #tblUsers {
        margin: 0 !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        width: 100% !important;
        table-layout: auto !important;
    }
    #tblUsers thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 10px;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        vertical-align: middle;
        white-space: nowrap;
        position: relative;
    }
    #tblUsers thead th.sorting,
    #tblUsers thead th.sorting_asc,
    #tblUsers thead th.sorting_desc {
        padding-left: 28px !important;
        padding-right: 28px !important;
    }
    #tblUsers thead th:not(.sorting):not(.sorting_asc):not(.sorting_desc) {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
    #tblUsers tbody td {
        padding: 10px 10px;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
        border-top: 1px solid #f1f5f9;
        min-width: 0 !important;
    }
    #tblUsers tbody tr:hover {
        background-color: #f8fafc;
    }
    #tblUsers tbody tr.context-menu-active {
        background-color: rgba(79, 70, 229, 0.08) !important;
    }

    /* Row Index Badge */
    .row-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 26px;
        height: 22px;
        padding: 0 6px;
        border-radius: 5px;
        background: #334155;
        color: #ffffff !important;
        font-size: 11.5px;
        font-weight: 700;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        letter-spacing: -0.3px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    }
    .dark-mode .row-index-badge {
        background: #0f172a !important;
        color: #38bdf8 !important;
        border: 1px solid #334155;
    }

    /* Badges & Tags */
    .user-title-cell {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #0f172a;
    }
    .user-avatar-sm {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #e0e7ff;
        color: #4338ca;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .badge-group {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 2px 7px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-soft-success {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-soft-secondary {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #cbd5e1;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Action buttons in Table */
    .action-btn-group {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        justify-content: center;
        white-space: nowrap;
    }
    .action-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        font-size: 11.5px;
        transition: all 0.15s ease;
    }
    .action-btn:hover {
        transform: translateY(-1px);
    }

    /* Context Menu */
    .custom-context-menu {
        display: none;
        position: fixed;
        z-index: 99999;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.08);
        padding: 8px 0;
        min-width: 220px;
        backdrop-filter: blur(8px);
        transition: opacity 0.15s ease, transform 0.15s ease;
    }
    .custom-context-menu .cm-header {
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 260px;
    }
    .custom-context-menu a,
    .custom-context-menu button {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 9px 16px;
        font-size: 13px;
        color: #334155;
        background: transparent;
        border: none;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .custom-context-menu a:hover,
    .custom-context-menu button:hover {
        background: #f1f5f9;
        color: #4338ca;
    }
    .custom-context-menu a.cm-danger,
    .custom-context-menu button.cm-danger {
        color: #ef4444;
    }
    .custom-context-menu a.cm-danger:hover,
    .custom-context-menu button.cm-danger:hover {
        background: #fef2f2;
        color: #dc2626;
    }
    .custom-context-menu i {
        width: 20px;
        font-size: 14px;
        margin-right: 10px;
        text-align: center;
    }
    .custom-context-menu .cm-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 4px 0;
    }

    /* ==========================================
       DARK MODE OVERRIDES
       ========================================== */
    .dark-mode .page-title-text h4 { color: #f1f5f9 !important; }
    .dark-mode .page-title-text p { color: #94a3b8 !important; }
    .dark-mode .crm-kpi-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    }
    .dark-mode .crm-kpi-label { color: #94a3b8 !important; }
    .dark-mode .crm-kpi-value { color: #f8fafc !important; }
    .dark-mode .crm-kpi-footer { border-top-color: #334155 !important; }
    .dark-mode .icon-indigo  { background: rgba(79, 70, 229, 0.15) !important; color: #a5b4fc !important; }
    .dark-mode .icon-emerald { background: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; }
    .dark-mode .icon-rose    { background: rgba(225, 29, 72, 0.15) !important; color: #fda4af !important; }
    .dark-mode .icon-amber   { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }

    .dark-mode .soft-indigo  { background: rgba(79, 70, 229, 0.2) !important; color: #c7d2fe !important; }
    .dark-mode .soft-emerald { background: rgba(16, 185, 129, 0.2) !important; color: #6ee7b7 !important; }
    .dark-mode .soft-rose    { background: rgba(225, 29, 72, 0.2) !important; color: #fecdd3 !important; }
    .dark-mode .soft-amber   { background: rgba(245, 158, 11, 0.2) !important; color: #fde68a !important; }

    .dark-mode .form-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
    }
    .dark-mode .form-card-header { border-bottom-color: #334155 !important; }
    .dark-mode .form-card-header .card-icon { background: #0f172a !important; color: #94a3b8 !important; }
    .dark-mode .form-card-header h5 { color: #f1f5f9 !important; }
    .dark-mode .form-card-header p { color: #94a3b8 !important; }
    .dark-mode .form-card .dataTables_wrapper .row:last-child {
        background: #151c27 !important;
        border-top-color: #334155 !important;
    }
    .dark-mode #tblUsers thead th {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode #tblUsers tbody td {
        border-top-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode #tblUsers tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    .dark-mode .user-title-cell { color: #f1f5f9 !important; }
    .dark-mode .user-avatar-sm {
        background: rgba(79, 70, 229, 0.2) !important;
        color: #a5b4fc !important;
    }
    .dark-mode .badge-group {
        background: #0f172a !important;
        color: #cbd5e1 !important;
        border-color: #334155 !important;
    }
    .dark-mode .badge-soft-success {
        background: rgba(16, 185, 129, 0.15) !important;
        color: #6ee7b7 !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }
    .dark-mode .badge-soft-secondary {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-color: #334155 !important;
    }

    .dark-mode .custom-context-menu {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
    }
    .dark-mode .custom-context-menu .cm-header {
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode .custom-context-menu a,
    .dark-mode .custom-context-menu button { color: #e2e8f0 !important; }
    .dark-mode .custom-context-menu a:hover,
    .dark-mode .custom-context-menu button:hover {
        background: #334155 !important;
        color: #a5b4fc !important;
    }
    .dark-mode .custom-context-menu a.cm-danger:hover,
    .dark-mode .custom-context-menu button.cm-danger:hover {
        background: rgba(239, 68, 68, 0.15) !important;
        color: #f87171 !important;
    }
    .dark-mode .custom-context-menu .cm-divider { background: #334155 !important; }
</style>

<div class="users-list-page-container">
    <div class="users-list-wrapper">

        <!-- Uyarı Mesajları -->
        <?php if (@$_GET["st"] == "cannotdeleted") { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                <i class="fa fa-exclamation-triangle mr-2"></i> Kendi üyeliğinizi veya ana yönetici hesabını silemezsiniz.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>
        <?php if (@$_GET["type"] == "delete") { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                <i class="fa fa-check-circle mr-2"></i> Kullanıcı başarıyla sistemden kaldırıldı.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>

        <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 12px; padding: 0;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-user-circle"></i>
                </div>
                <div class="page-title-text">
                    <h4>Ekip & Kullanıcı Yönetimi</h4>
                    <p>Sistemde tanımlı tüm kullanıcılar, roller ve yetki durumları</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshUsers" title="Tabloyu Yenile">
                    <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
                </button>
                <button type="button" class="btn btn-outline-success btn-action-outline" id="btnExportUsers" title="Excel Olarak İndir">
                    <i class="fa fa-file-excel-o"></i> <span class="d-none d-sm-inline">Excel'e Aktar</span>
                </button>
                <?php if (permtrue("useradd")) { ?>
                    <a href="index.php?p=user-new" class="btn btn-action-primary">
                        <i class="fa fa-plus-circle"></i> <span>Yeni Üye Ekle</span>
                    </a>
                <?php } ?>
            </div>
        </div>

        <!-- KPI Özet / İstatistik Kartları -->
        <div id="kpiSummarySection" class="row mx-0 mb-2 kpi-summary-collapse">
            <!-- Toplam Üye -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Ekip Üyesi</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalUsers, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-indigo">
                            <i class="fa fa-users"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Kayıtlı Personel</span>
                        <span class="crm-badge-soft soft-indigo">Kullanıcılar</span>
                    </div>
                </div>
            </div>

            <!-- Aktif Kullanıcılar -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Aktif Kullanıcılar</span>
                            <div class="crm-kpi-value"><?php echo number_format($activeUsers, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-check-circle-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">%<?php echo $activeRate; ?> Aktiflik Oranı</span>
                        <span class="crm-badge-soft soft-emerald">Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Pasif Kullanıcılar -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Pasif Kullanıcılar</span>
                            <div class="crm-kpi-value"><?php echo number_format($passiveUsers, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-rose">
                            <i class="fa fa-times-circle-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Erişimi Kapatılanlar</span>
                        <span class="crm-badge-soft soft-rose">Pasif</span>
                    </div>
                </div>
            </div>

            <!-- Yönetici / Admin -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Yöneticiler (Admin)</span>
                            <div class="crm-kpi-value"><?php echo number_format($adminUsers, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-amber">
                            <i class="fa fa-shield"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Tam Yetkili Hesaplar</span>
                        <span class="crm-badge-soft soft-amber">Yönetici</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tablo Kartı -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header">
                <div class="header-left-inner">
                    <div class="card-icon">
                        <i class="fa fa-list"></i>
                    </div>
                    <div>
                        <h5>Ekip Listesi</h5>
                        <p>Kullanıcı profilleri, iletişim ve yetki yönetimi</p>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <div class="dt-header-filter-box d-flex align-items-center"></div>
                    <button type="button" id="toggleKpiSummary" class="btn btn-outline-secondary btn-sm" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa fa-chevron-up"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tblUsers" class="data-table table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 42px;" class="no-sort text-center">#Sıra</th>
                            <th style="width: 140px;">Pozisyon</th>
                            <th style="min-width: 180px;">Adı Soyadı</th>
                            <th style="width: 200px;">E-Posta Adresi</th>
                            <th style="width: 120px;">GSM</th>
                            <th style="width: 100px;" class="text-center">Durum</th>
                            <th style="width: 95px;" class="no-sort text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cq = $ac->prepare("
                            SELECT u.*, ur.roleName 
                            FROM users u 
                            LEFT JOIN userroles ur ON ur.id = u.permission 
                            ORDER by u.id DESC
                        ");
                        $cq->execute([]);
                        $sirano = 1;
                        $canEdit = permtrue("useredit");
                        $canDelete = permtrue("userdelete");
                        $currentUserId = sesset("id");

                        while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {
                            $uid = (int)$as["id"];
                            $username = htmlspecialchars($as["username"] ?? '');
                            $roleName = htmlspecialchars($as["roleName"] ?? 'Genel Kullanıcı');
                            $email = htmlspecialchars($as["email"] ?? '');
                            $gsm = htmlspecialchars($as["gsm"] ?? '');
                            $statu = (int)($as["statu"] ?? 0);
                            $initials = mb_strtoupper(mb_substr($as["username"] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8');
                        ?>
                        <tr data-user-id="<?php echo $uid; ?>" data-username="<?php echo $username; ?>">
                            <td class="text-center">
                                <span class="row-index-badge"><?php echo $sirano; ?></span>
                            </td>
                            <td>
                                <span class="badge-group"><?php echo $roleName; ?></span>
                            </td>
                            <td>
                                <div class="user-title-cell">
                                    <span class="user-avatar-sm"><?php echo $initials; ?></span>
                                    <span><?php echo $username; ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($email)) { ?>
                                    <a href="mailto:<?php echo $email; ?>" class="text-muted text-nowrap font-12">
                                        <i class="fa fa-envelope-o mr-1 text-primary"></i> <?php echo $email; ?>
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted font-12">-</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if (!empty($gsm)) { ?>
                                    <a href="tel:<?php echo $gsm; ?>" class="text-muted text-nowrap font-12">
                                        <i class="fa fa-phone mr-1 text-success"></i> <?php echo $gsm; ?>
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted font-12">-</span>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <?php if ($statu == 1) { ?>
                                    <span class="badge-soft-success"><i class="fa fa-check-circle mr-1"></i> Aktif</span>
                                <?php } else { ?>
                                    <span class="badge-soft-secondary"><i class="fa fa-times-circle mr-1"></i> Pasif</span>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <div class="action-btn-group">
                                    <?php if ($canEdit && $uid != 1) { ?>
                                        <a href="index.php?p=user-edit&id=<?php echo $uid; ?>" class="btn btn-sm btn-outline-info action-btn" data-tooltip="Düzenle">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        <?php if ($statu == 1) { ?>
                                            <a href="index.php?p=users&mode=updatest&code=3222891&reg=true&md=active&id=<?php echo $uid; ?>&stu=0" class="btn btn-sm btn-outline-secondary action-btn" data-tooltip="Pasifleştir">
                                                <i class="fa fa-user-times"></i>
                                            </a>
                                        <?php } else { ?>
                                            <a href="index.php?p=users&mode=updatest&code=3222891&reg=true&md=active&id=<?php echo $uid; ?>&stu=1" class="btn btn-sm btn-outline-success action-btn" data-tooltip="Aktifleştir">
                                                <i class="fa fa-user-plus"></i>
                                            </a>
                                        <?php } ?>
                                    <?php } ?>
                                    <?php if ($canDelete && $uid != $currentUserId && $uid != 1) { ?>
                                        <a href="#" class="btn btn-sm btn-outline-danger action-btn" data-tooltip="Sil" onClick="deleteRecord('Devam ettiğiniz takdirde, kullanıcıya ait tüm bilgiler ve kullanıcı adına düzenlenmiş olan teklif & projeler tamamen silinecektir. Devam etmek istiyor musunuz?','<?php echo $uid; ?>','users')">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                            $sirano++;
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-center">#Sıra</th>
                            <th>Pozisyon</th>
                            <th>Adı Soyadı</th>
                            <th>E-Posta Adresi</th>
                            <th>GSM</th>
                            <th class="text-center">Durum</th>
                            <th class="text-center">İşlem</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    $(document).ready(function () {
        // KPI Kartları Göster / Gizle Mantığı
        var KPI_STORAGE_KEY = 'aydinogullari_kpi_users_collapsed';
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

        $toggleBtn.on('click', function() {
            var currentState = $kpiSection.is(':visible');
            var newState = currentState;
            localStorage.setItem(KPI_STORAGE_KEY, newState ? 'true' : 'false');
            updateKpiToggleState(newState, true);
        });

        // DataTables Kurulumu
        var userTable = $('#tblUsers').DataTable({
            responsive: false,
            autoWidth: false,
            scrollX: false,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                url: 'include/js/tr.json',
                processing: '<div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"><span class="sr-only">Yükleniyor...</span></div>'
            },
            order: [[0, 'asc']],
            orderCellsTop: true,
            initComplete: function () {
                var api = this.api();
                
                // Arama kutusunu Form Card Header içine taşıma
                var $filterContainer = $('.form-card-header .dt-header-filter-box');
                var $searchBox = $('#tblUsers_filter');
                if ($filterContainer.length && $searchBox.length) {
                    $searchBox.detach().appendTo($filterContainer);
                    $searchBox.find('input').addClass('form-control form-control-sm').css({
                        'border-radius': '8px',
                        'height': '36px',
                        'width': '220px',
                        'padding': '6px 12px'
                    });
                }

                if (window.App && window.App.TableFilter) {
                    App.TableFilter.attachToTable(api.table().node());
                }
            }
        });

        // Yenile Butonu
        $('#btnRefreshUsers').on('click', function() {
            var $icon = $(this).find('i');
            $icon.addClass('fa-spin');
            window.location.reload();
        });

        // Excel Export (SheetJS / XLSX)
        $('#btnExportUsers').on('click', function () {
            if (typeof XLSX === 'undefined') {
                alert('Excel kütüphanesi yüklenemedi.');
                return;
            }

            var rows = [];
            rows.push(['Sıra No', 'Pozisyon', 'Adı Soyadı', 'E-Posta Adresi', 'GSM', 'Durum']);

            userTable.rows({ search: 'applied' }).every(function() {
                var $row = $(this.node());
                var col0 = $row.find('td:nth-child(1)').text().trim();
                var col1 = $row.find('td:nth-child(2)').text().trim();
                var col2 = $row.find('td:nth-child(3)').text().trim();
                var col3 = $row.find('td:nth-child(4)').text().trim();
                var col4 = $row.find('td:nth-child(5)').text().trim();
                var col5 = $row.find('td:nth-child(6)').text().trim();

                rows.push([col0, col1, col2, col3, col4, col5]);
            });

            var ws = XLSX.utils.aoa_to_sheet(rows);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Ekip Listesi');
            XLSX.writeFile(wb, 'Ekip_Listesi_' + new Date().toISOString().slice(0, 10) + '.xlsx');
        });

        // Tabloda Sağ Tık (Context Menu) İşlemleri
        $(document).on('contextmenu', '#tblUsers tbody tr', function(e) {
            if ($(this).find('td').length <= 1) return;

            e.preventDefault();
            
            var $tr = $(this);
            $('#tblUsers tbody tr').removeClass('context-menu-active');
            $tr.addClass('context-menu-active');

            var userName = $tr.attr('data-username') || $tr.find('td:nth-child(3)').text().trim() || 'Kullanıcı İşlemleri';
            var $actionTd = $tr.find('td:last-child');
            
            var menuHtml = '<div class="cm-header"><i class="fa fa-user-circle-o mr-1"></i> ' + $('<div>').text(userName).html() + '</div>';

            // 1. Düzenle Butonu Varsa
            var $editBtn = $actionTd.find('a[data-tooltip="Düzenle"], a.btn-outline-info');
            if ($editBtn.length) {
                menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-info mr-2"></i> Profili Düzenle</a>';
            }

            // 2. Pasif / Aktif Butonu Varsa
            var $statuBtn = $actionTd.find('a[data-tooltip="Pasifleştir"], a[data-tooltip="Aktifleştir"], a.btn-outline-secondary, a.btn-outline-success');
            if ($statuBtn.length) {
                var isPassiveAction = $statuBtn.attr('data-tooltip') === 'Pasifleştir' || $statuBtn.find('i').hasClass('fa-user-times');
                if (isPassiveAction) {
                    menuHtml += '<a href="' + $statuBtn.attr('href') + '"><i class="fa fa-user-times text-warning mr-2"></i> Hesabı Pasifleştir</a>';
                } else {
                    menuHtml += '<a href="' + $statuBtn.attr('href') + '"><i class="fa fa-user-plus text-success mr-2"></i> Hesabı Aktifleştir</a>';
                }
            }

            // 3. Sil Butonu Varsa
            var $deleteBtn = $actionTd.find('a[data-tooltip="Sil"], a.btn-outline-danger');
            if ($deleteBtn.length) {
                menuHtml += '<div class="cm-divider"></div>';
                var onClickAttr = $deleteBtn.attr('onclick') || $deleteBtn.attr('onClick') || '';
                menuHtml += '<a href="#" class="cm-danger" onclick="' + onClickAttr + '; return false;"><i class="fa fa-trash text-danger mr-2"></i> Kullanıcıyı Sil</a>';
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

        // Menü dışına tıklanınca kapat
        $(document).on('click scroll', function(e) {
            if (!$(e.target).closest('#customContextMenu').length) {
                $('#customContextMenu').hide();
                $('#tblUsers tbody tr').removeClass('context-menu-active');
            }
        });

        // Menüdeki seçeneğe basılınca kapat
        $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
            $('#customContextMenu').hide();
            $('#tblUsers tbody tr').removeClass('context-menu-active');
        });

        // ESC basılınca kapat
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#customContextMenu').hide();
                $('#tblUsers tbody tr').removeClass('context-menu-active');
            }
        });
    });
</script>