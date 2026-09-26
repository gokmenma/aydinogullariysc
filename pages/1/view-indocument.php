<?php

if (@$_GET["id"] && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
    permcontrol("");
    $cdid = (int)$_GET["id"];
    $contq = $ac->prepare("SELECT * FROM evraktakip WHERE id = ?");
    $contq->execute(array($cdid));
    if ($contq->fetch(PDO::FETCH_ASSOC)) {
        $deletq = $ac->prepare("DELETE FROM evraktakip WHERE id = ?");
        $deletq->execute(array($cdid));

        if ($deletq) {
            header("Location: index.php?p=view-indocument&id=$cdid&type=delete");
            exit;
        }
    }
}

// KPI İstatistikleri
$statsQuery = $ac->query("
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN estatu = 'Bekliyor' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN estatu = 'Çalışıyor' THEN 1 ELSE 0 END) as processing_count,
        SUM(CASE WHEN estatu = 'Tamamlandı' THEN 1 ELSE 0 END) as completed_count
    FROM evraktakip
    WHERE evrakturu = 'Gelen'
");
$stats = $statsQuery ? $statsQuery->fetch(PDO::FETCH_ASSOC) : [];
$totalCount = (int)($stats['total_count'] ?? 0);
$pendingCount = (int)($stats['pending_count'] ?? 0);
$processingCount = (int)($stats['processing_count'] ?? 0);
$completedCount = (int)($stats['completed_count'] ?? 0);
$completedRate = $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0;

try {
    $logger = \getLogger("Evrak Takip");
    $logger->info("Gelen evrak listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_indoc_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-indoc-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* ==========================================
       PREMIUM IN-DOCUMENTS LIST THEME
       ========================================== */
    .kpi-indoc-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    .indoc-list-wrapper {
        width: 100%;
        padding: 0;
        margin: 0;
    }

    .indoc-list-page-container {
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
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
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
    .icon-primary { background: #eff6ff; color: #2563eb; }
    .icon-amber   { background: #fffbeb; color: #d97706; }
    .icon-cyan    { background: #ecfeff; color: #0891b2; }
    .icon-emerald { background: #ecfdf5; color: #059669; }

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
    .soft-primary { background: #dbeafe; color: #1e40af; }
    .soft-amber   { background: #fef3c7; color: #92400e; }
    .soft-cyan    { background: #cffafe; color: #0e7490; }
    .soft-emerald { background: #d1fae5; color: #065f46; }

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
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff !important;
        border: none;
        border-radius: 8px;
        padding: 8px 18px;
        font-weight: 600;
        font-size: 13.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
        transition: all 0.2s ease;
        height: 38px;
        text-decoration: none;
    }
    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(2, 132, 199, 0.38);
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
    #tblInDocuments {
        margin: 0 !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        width: 100% !important;
        table-layout: auto !important;
    }
    #tblInDocuments thead th {
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
    #tblInDocuments thead th.sorting,
    #tblInDocuments thead th.sorting_asc,
    #tblInDocuments thead th.sorting_desc {
        padding-left: 28px !important;
        padding-right: 28px !important;
    }
    #tblInDocuments thead th:not(.sorting):not(.sorting_asc):not(.sorting_desc) {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
    #tblInDocuments tbody td {
        padding: 10px 10px;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
        border-top: 1px solid #f1f5f9;
        min-width: 0 !important;
    }
    #tblInDocuments tbody tr:hover {
        background-color: #f8fafc;
    }
    #tblInDocuments tbody tr.context-menu-active {
        background-color: rgba(2, 132, 199, 0.08) !important;
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
    .badge-doc-type {
        display: inline-flex;
        align-items: center;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 5px;
        padding: 2px 7px;
        font-size: 11.5px;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-category {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 2px 7px;
        font-size: 11.5px;
        font-weight: 500;
        white-space: nowrap;
    }
    .badge-stat-soft-warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-stat-soft-primary {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-stat-soft-success {
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
        color: #0284c7;
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
    .dark-mode .icon-primary { background: rgba(59, 130, 246, 0.15) !important; color: #60a5fa !important; }
    .dark-mode .icon-amber   { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }
    .dark-mode .icon-cyan    { background: rgba(8, 145, 178, 0.15) !important; color: #38bdf8 !important; }
    .dark-mode .icon-emerald { background: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; }

    .dark-mode .soft-primary { background: rgba(59, 130, 246, 0.2) !important; color: #93c5fd !important; }
    .dark-mode .soft-amber   { background: rgba(245, 158, 11, 0.2) !important; color: #fde68a !important; }
    .dark-mode .soft-cyan    { background: rgba(8, 145, 178, 0.2) !important; color: #7dd3fc !important; }
    .dark-mode .soft-emerald { background: rgba(16, 185, 129, 0.2) !important; color: #6ee7b7 !important; }

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
    .dark-mode #tblInDocuments thead th {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode #tblInDocuments tbody td {
        border-top-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode #tblInDocuments tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    .dark-mode .badge-doc-type {
        background: rgba(59, 130, 246, 0.15) !important;
        color: #93c5fd !important;
        border-color: rgba(59, 130, 246, 0.3) !important;
    }
    .dark-mode .badge-category {
        background: #0f172a !important;
        color: #cbd5e1 !important;
        border-color: #334155 !important;
    }
    .dark-mode .badge-stat-soft-warning {
        background: rgba(245, 158, 11, 0.15) !important;
        color: #fde68a !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }
    .dark-mode .badge-stat-soft-primary {
        background: rgba(59, 130, 246, 0.15) !important;
        color: #93c5fd !important;
        border-color: rgba(59, 130, 246, 0.3) !important;
    }
    .dark-mode .badge-stat-soft-success {
        background: rgba(16, 185, 129, 0.15) !important;
        color: #6ee7b7 !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
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
        color: #38bdf8 !important;
    }
    .dark-mode .custom-context-menu a.cm-danger:hover,
    .dark-mode .custom-context-menu button.cm-danger:hover {
        background: rgba(239, 68, 68, 0.15) !important;
        color: #f87171 !important;
    }
    .dark-mode .custom-context-menu .cm-divider { background: #334155 !important; }
</style>

<div class="indoc-list-page-container">
    <div class="indoc-list-wrapper">

        <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 12px; padding: 0;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-folder-open"></i>
                </div>
                <div class="page-title-text">
                    <h4>Gelen Evrak Takibi</h4>
                    <p>Sisteme gelen tüm evrak, teslim tutanakları ve işlem durumları</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshInDocs" title="Tabloyu Yenile">
                    <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
                </button>
                <button type="button" class="btn btn-outline-success btn-action-outline" id="btnExportInDocs" title="Excel Olarak İndir">
                    <i class="fa fa-file-excel-o"></i> <span class="d-none d-sm-inline">Excel'e Aktar</span>
                </button>
                <a href="index.php?p=new-indocument" class="btn btn-action-primary">
                    <i class="fa fa-plus-circle"></i> <span>Yeni Gelen Evrak</span>
                </a>
            </div>
        </div>

        <!-- KPI Özet / İstatistik Kartları -->
        <div id="kpiSummarySection" class="row mx-0 mb-2 kpi-summary-collapse">
            <!-- Toplam Gelen Evrak -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Gelen Evrak</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-inbox"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Kayıtlı Gelen Evrak</span>
                        <span class="crm-badge-soft soft-primary">Gelen</span>
                    </div>
                </div>
            </div>

            <!-- Bekleyen Evraklar -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Bekleyen Evraklar</span>
                            <div class="crm-kpi-value"><?php echo number_format($pendingCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-amber">
                            <i class="fa fa-clock-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">İşlem Sırasında</span>
                        <span class="crm-badge-soft soft-amber">Bekliyor</span>
                    </div>
                </div>
            </div>

            <!-- İşlemdeki (Çalışıyor) -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">İşlemde Olanlar</span>
                            <div class="crm-kpi-value"><?php echo number_format($processingCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-cyan">
                            <i class="fa fa-spinner"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Süreç Devam Ediyor</span>
                        <span class="crm-badge-soft soft-cyan">Çalışıyor</span>
                    </div>
                </div>
            </div>

            <!-- Tamamlanan Evraklar -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Tamamlanan Evrak</span>
                            <div class="crm-kpi-value"><?php echo number_format($completedCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-check-circle-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">%<?php echo $completedRate; ?> Çözülme Oranı</span>
                        <span class="crm-badge-soft soft-emerald">Tamamlandı</span>
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
                        <h5>Gelen Evrak Listesi</h5>
                        <p>Evrak kayıtları, teslim bilgileri ve takip durumu</p>
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
                <table id="tblInDocuments" class="data-table table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 42px;" class="no-sort text-center">#Sıra</th>
                            <th style="min-width: 180px;">Firma</th>
                            <th style="width: 100px;">Evrak Türü</th>
                            <th style="width: 120px;">Kategori</th>
                            <th style="width: 65px;" class="text-center">Adet</th>
                            <th style="width: 120px;">Teslim Eden</th>
                            <th style="width: 120px;">Teslim Alan</th>
                            <th style="width: 100px;" class="text-center">Teslim Tarihi</th>
                            <th style="width: 105px;" class="text-center">Evrak Durumu</th>
                            <th style="width: 140px;">Açıklama</th>
                            <th style="width: 80px;" class="no-sort text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cq = $ac->prepare("
                            SELECT e.*, 
                                   c.company AS customer_company,
                                   u_teslimalan.username AS teslim_alan_username, 
                                   u_teslimeden.username AS teslim_eden_username
                            FROM evraktakip e 
                            LEFT JOIN customers c ON c.id = e.firma
                            LEFT JOIN users u_teslimalan ON e.teslimalan = u_teslimalan.id 
                            LEFT JOIN users u_teslimeden ON e.teslimeden = u_teslimeden.id 
                            WHERE e.evrakturu = 'Gelen'
                            ORDER BY e.id DESC
                        ");
                        $cq->execute();
                        $siraNo = 1;

                        while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {
                            $docId = (int)$as["id"];
                            $companyName = htmlspecialchars($as["customer_company"] ?? ($as["firma"] ?? '-'));
                            $evrakTuru = htmlspecialchars($as["evrakturu"] ?? 'Gelen');
                            $kategori = htmlspecialchars($as["kategori"] ?? '-');
                            $adet = htmlspecialchars($as["adet"] ?? '1');
                            $teslimEden = htmlspecialchars($as["teslim_eden_username"] ?? '-');
                            $teslimAlan = htmlspecialchars($as["teslim_alan_username"] ?? '-');
                            $teslimTarihi = htmlspecialchars($as["teslimtarihi"] ?? '-');
                            $estatu = $as["estatu"] ?? 'Bekliyor';
                            $aciklama = htmlspecialchars($as["aciklama"] ?? '');
                        ?>
                        <tr data-doc-id="<?php echo $docId; ?>" data-company="<?php echo $companyName; ?>">
                            <td class="text-center">
                                <span class="row-index-badge"><?php echo $siraNo; ?></span>
                            </td>
                            <td>
                                <span class="font-weight-600 text-dark"><?php echo $companyName; ?></span>
                            </td>
                            <td>
                                <span class="badge-doc-type"><?php echo $evrakTuru; ?></span>
                            </td>
                            <td>
                                <span class="badge-category"><?php echo $kategori; ?></span>
                            </td>
                            <td class="text-center font-weight-600">
                                <?php echo $adet; ?>
                            </td>
                            <td>
                                <span class="font-12 text-muted"><i class="fa fa-user-o mr-1"></i><?php echo $teslimEden; ?></span>
                            </td>
                            <td>
                                <span class="font-12 text-muted"><i class="fa fa-user-check mr-1"></i><?php echo $teslimAlan; ?></span>
                            </td>
                            <td class="text-center text-nowrap font-12 text-muted">
                                <i class="fa fa-calendar-o mr-1"></i><?php echo $teslimTarihi; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                if ($estatu == "Bekliyor") {
                                    echo "<span class='badge-stat-soft-warning'><i class='fa fa-clock-o mr-1'></i>Bekliyor</span>";
                                } elseif ($estatu == "Çalışıyor") {
                                    echo "<span class='badge-stat-soft-primary'><i class='fa fa-spinner fa-spin mr-1'></i>Çalışıyor</span>";
                                } elseif ($estatu == "Tamamlandı") {
                                    echo "<span class='badge-stat-soft-success'><i class='fa fa-check mr-1'></i>Tamamlandı</span>";
                                } else {
                                    echo "<span class='badge badge-secondary'>" . htmlspecialchars($estatu) . "</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <span class="font-12 text-muted" title="<?php echo $aciklama; ?>"><?php echo shorted($aciklama, 30); ?></span>
                            </td>
                            <td class="text-center">
                                <div class="action-btn-group">
                                    <a class="btn btn-sm btn-outline-info action-btn" data-tooltip="Düzenle" href="index.php?p=indocument-edit&id=<?php echo $docId; ?>">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger action-btn" data-tooltip="Sil" onClick="deleteRecord('<?php echo addslashes($companyName); ?> firmasına ait evrak kaydını silmek istediğinize emin misiniz?', '<?php echo $docId; ?>', 'view-indocument', 'evraktakip')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php
                            $siraNo++;
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-center">#Sıra</th>
                            <th>Firma</th>
                            <th>Evrak Türü</th>
                            <th>Kategori</th>
                            <th class="text-center">Adet</th>
                            <th>Teslim Eden</th>
                            <th>Teslim Alan</th>
                            <th class="text-center">Teslim Tarihi</th>
                            <th class="text-center">Evrak Durumu</th>
                            <th>Açıklama</th>
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
        var KPI_STORAGE_KEY = 'aydinogullari_kpi_indoc_collapsed';
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
        var inDocTable = $('#tblInDocuments').DataTable({
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
                var $searchBox = $('#tblInDocuments_filter');
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
        $('#btnRefreshInDocs').on('click', function() {
            var $icon = $(this).find('i');
            $icon.addClass('fa-spin');
            window.location.reload();
        });

        // Excel Export (SheetJS / XLSX)
        $('#btnExportInDocs').on('click', function () {
            if (typeof XLSX === 'undefined') {
                alert('Excel kütüphanesi yüklenemedi.');
                return;
            }

            var rows = [];
            rows.push(['Sıra', 'Firma', 'Evrak Türü', 'Kategori', 'Adet', 'Teslim Eden', 'Teslim Alan', 'Teslim Tarihi', 'Evrak Durumu', 'Açıklama']);

            inDocTable.rows({ search: 'applied' }).every(function() {
                var $row = $(this.node());
                var col0 = $row.find('td:nth-child(1)').text().trim();
                var col1 = $row.find('td:nth-child(2)').text().trim();
                var col2 = $row.find('td:nth-child(3)').text().trim();
                var col3 = $row.find('td:nth-child(4)').text().trim();
                var col4 = $row.find('td:nth-child(5)').text().trim();
                var col5 = $row.find('td:nth-child(6)').text().trim();
                var col6 = $row.find('td:nth-child(7)').text().trim();
                var col7 = $row.find('td:nth-child(8)').text().trim();
                var col8 = $row.find('td:nth-child(9)').text().trim();
                var col9 = $row.find('td:nth-child(10)').text().trim();

                rows.push([col0, col1, col2, col3, col4, col5, col6, col7, col8, col9]);
            });

            var ws = XLSX.utils.aoa_to_sheet(rows);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Gelen Evrak Listesi');
            XLSX.writeFile(wb, 'Gelen_Evrak_Listesi_' + new Date().toISOString().slice(0, 10) + '.xlsx');
        });

        // Tabloda Sağ Tık (Context Menu) İşlemleri
        $(document).on('contextmenu', '#tblInDocuments tbody tr', function(e) {
            if ($(this).find('td').length <= 1) return;

            e.preventDefault();
            
            var $tr = $(this);
            $('#tblInDocuments tbody tr').removeClass('context-menu-active');
            $tr.addClass('context-menu-active');

            var companyName = $tr.attr('data-company') || $tr.find('td:nth-child(2)').text().trim() || 'Evrak İşlemleri';
            var $actionTd = $tr.find('td:last-child');
            
            var menuHtml = '<div class="cm-header"><i class="fa fa-folder-open-o mr-1"></i> ' + $('<div>').text(companyName).html() + '</div>';

            // 1. Düzenle Butonu Varsa
            var $editBtn = $actionTd.find('a[data-tooltip="Düzenle"], a.btn-outline-info');
            if ($editBtn.length) {
                menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-info mr-2"></i> Evrağı Düzenle</a>';
            }

            // 2. Sil Butonu Varsa
            var $deleteBtn = $actionTd.find('a[data-tooltip="Sil"], a.btn-outline-danger');
            if ($deleteBtn.length) {
                menuHtml += '<div class="cm-divider"></div>';
                var onClickAttr = $deleteBtn.attr('onclick') || $deleteBtn.attr('onClick') || '';
                menuHtml += '<a href="#" class="cm-danger" onclick="' + onClickAttr + '; return false;"><i class="fa fa-trash text-danger mr-2"></i> Evrağı Sil</a>';
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
                $('#tblInDocuments tbody tr').removeClass('context-menu-active');
            }
        });

        // Menüdeki seçeneğe basılınca kapat
        $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
            $('#customContextMenu').hide();
            $('#tblInDocuments tbody tr').removeClass('context-menu-active');
        });

        // ESC basılınca kapat
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#customContextMenu').hide();
                $('#tblInDocuments tbody tr').removeClass('context-menu-active');
            }
        });
    });
</script>