<?php

use App\Helper\Helper;
use App\Helper\Security;
use App\Model\ProductModel;
use App\Model\DefineModel;

// Sayfa ve DataTables endpoint'i aynı ürün modülü yetki kümesini kullanır.
$canViewProducts = permtrue('product_dashboard')
    || permtrue('productcategory')
    || permtrue('productadd')
    || permtrue('productedit')
    || permtrue('productdelete');

if (!$canViewProducts) {
    echo '<div class="alert alert-danger m-4"><i class="fa fa-exclamation-triangle"></i> Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>';
    return;
}

// Model sınıfları
$ProductModel = new ProductModel();
$Define = new DefineModel();

// KPI İstatistikleri
$stats = $ProductModel->getSummaryStats();
$totalCount = (int) ($stats['total_count'] ?? 0);
$tryCount = (int) ($stats['try_count'] ?? 0);
$eurCount = (int) ($stats['eur_count'] ?? 0);
$usdCount = (int) ($stats['usd_count'] ?? 0);
$foreignCount = $eurCount + $usdCount;
$skuCount = (int) ($stats['with_sku_count'] ?? 0);
$skuRate = $totalCount > 0 ? round(($skuCount / $totalCount) * 100, 1) : 0;

try {
    $logger = \getLogger("Ürünler");
    $logger->info("Ürün/Hizmet listesi görüntülendi.", [
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
} catch (\Throwable $e) {}
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_products_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-products-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* ==========================================
       PREMIUM PRODUCTS LIST THEME
       ========================================== */
    .kpi-products-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    .products-list-wrapper {
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
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.28);
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
    .icon-emerald { background: #ecfdf5; color: #059669; }
    .icon-purple  { background: #f5f3ff; color: #7c3aed; }
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
    .soft-primary { background: #dbeafe; color: #1e40af; }
    .soft-emerald { background: #d1fae5; color: #065f46; }
    .soft-purple  { background: #ede9fe; color: #5b21b6; }
    .soft-amber   { background: #fef3c7; color: #92400e; }

    /* Form & Table Card styling */
    .form-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 4px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
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
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff !important;
        border: none;
        border-radius: 8px;
        padding: 8px 18px;
        font-weight: 600;
        font-size: 13.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.28);
        transition: all 0.2s ease;
        height: 38px;
        text-decoration: none;
    }
    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.38);
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
        display: none !important; /* Üstteki gereksiz DataTables arama satırı boşluğunu gizler */
        margin: 0 !important;
    }
    .form-card .dataTables_wrapper .row:last-child {
        padding: 12px 20px !important;
        margin: 0 !important;
        border-top: 1px solid #f1f5f9;
        background: #fafafa;
    }

    /* Force Reset min-width imposed by global styles */
    .products-list-wrapper table,
    .products-list-wrapper table th,
    .products-list-wrapper table td,
    .products-list-wrapper .table th,
    .products-list-wrapper .table td,
    .products-list-wrapper .data-table th,
    .products-list-wrapper .data-table td,
    #tblProducts th,
    #tblProducts td {
        min-width: 0 !important;
        box-sizing: border-box !important;
    }

    /* Custom Table & Badge Styles */
    #tblProducts {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        table-layout: auto !important;
    }
    #tblProducts thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 9px 8px !important;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        vertical-align: middle;
        white-space: nowrap;
        position: relative !important;
    }
    #tblProducts thead th.sorting,
    #tblProducts thead th.sorting_asc,
    #tblProducts thead th.sorting_desc {
        padding-left: 28px !important;
        padding-right: 28px !important;
    }
    #tblProducts thead th.no-sort {
        padding-left: 6px !important;
        padding-right: 6px !important;
    }
    #tblProducts thead th.sorting:before,
    #tblProducts thead th.sorting_asc:before,
    #tblProducts thead th.sorting_desc:before {
        left: 8px !important;
        right: auto !important;
        bottom: 50% !important;
        transform: translateY(50%) !important;
        line-height: 1 !important;
        opacity: 0.4;
    }
    #tblProducts thead th.sorting:after,
    #tblProducts thead th.sorting_asc:after,
    #tblProducts thead th.sorting_desc:after {
        left: 16px !important;
        right: auto !important;
        bottom: 50% !important;
        transform: translateY(50%) !important;
        line-height: 1 !important;
        opacity: 0.4;
    }
    #tblProducts thead th .tf-trigger {
        position: absolute !important;
        right: 6px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        margin: 0 !important;
    }

    #tblProducts tbody td {
        padding: 6px 6px !important;
        vertical-align: middle;
        font-size: 12.5px;
        border-top: 1px solid #f1f5f9;
    }
    #tblProducts tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Sıra Numarası Badge (Yüksek Kontrast & Net Okunurluk) */
    .row-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 22px;
        padding: 0 5px;
        background: #e2e8f0;
        color: #0f172a !important;
        font-weight: 700;
        font-size: 12px;
        border-radius: 5px;
        border: 1px solid #cbd5e1;
    }
    .dark-mode .row-index-badge {
        background: #1e293b !important;
        color: #f8fafc !important;
        border-color: #475569 !important;
    }

    /* Badges */
    .badge-sku {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 2px 6px;
        font-size: 11.5px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-unit {
        display: inline-flex;
        align-items: center;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 5px;
        padding: 2px 6px;
        font-size: 11.5px;
        font-weight: 500;
        white-space: nowrap;
    }
    .badge-currency {
        font-size: 10.5px;
        padding: 1px 4px;
        border-radius: 3px;
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        margin-left: 2px;
    }
    .badge-currency-success {
        background: #ecfdf5;
        color: #047857;
    }

    .product-title-cell {
        font-size: 13px;
        line-height: 1.25;
        color: #1e293b;
    }

    /* Action buttons in Table */
    .action-btn-group {
        display: inline-flex;
        align-items: center;
        gap: 2px;
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

    /* Sağ Tık (Context Menu) Stilleri */
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
    .dark-mode .custom-context-menu {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
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
    .dark-mode .custom-context-menu .cm-header {
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .custom-context-menu a,
    .custom-context-menu button {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 9px 16px;
        font-size: 13.5px;
        color: #334155;
        background: transparent;
        border: none;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .dark-mode .custom-context-menu a,
    .dark-mode .custom-context-menu button {
        color: #e2e8f0 !important;
    }
    .custom-context-menu a:hover,
    .custom-context-menu button:hover {
        background: #f1f5f9;
        color: #0284c7;
    }
    .dark-mode .custom-context-menu a:hover,
    .dark-mode .custom-context-menu button:hover {
        background: #334155 !important;
        color: #38bdf8 !important;
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
    .dark-mode .custom-context-menu a.cm-danger:hover,
    .dark-mode .custom-context-menu button.cm-danger:hover {
        background: rgba(239, 68, 68, 0.15) !important;
        color: #f87171 !important;
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
    .dark-mode .custom-context-menu .cm-divider {
        background: #334155 !important;
    }
    tr.context-menu-active {
        background-color: rgba(59, 130, 246, 0.08) !important;
    }

    /* ==========================================
       DARK MODE OVERRIDES
       ========================================== */
    .dark-mode .page-title-text h4 {
        color: #f1f5f9 !important;
    }
    .dark-mode .page-title-text p {
        color: #94a3b8 !important;
    }
    .dark-mode .crm-kpi-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    }
    .dark-mode .crm-kpi-label {
        color: #94a3b8 !important;
    }
    .dark-mode .crm-kpi-value {
        color: #f8fafc !important;
    }
    .dark-mode .crm-kpi-footer {
        border-top-color: #334155 !important;
    }
    .dark-mode .icon-primary { background: rgba(59, 130, 246, 0.15) !important; color: #60a5fa !important; }
    .dark-mode .icon-emerald { background: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; }
    .dark-mode .icon-purple  { background: rgba(139, 92, 246, 0.15) !important; color: #a78bfa !important; }
    .dark-mode .icon-amber   { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }

    .dark-mode .soft-primary { background: rgba(59, 130, 246, 0.2) !important; color: #93c5fd !important; }
    .dark-mode .soft-emerald { background: rgba(16, 185, 129, 0.2) !important; color: #6ee7b7 !important; }
    .dark-mode .soft-purple  { background: rgba(139, 92, 246, 0.2) !important; color: #c4b5fd !important; }
    .dark-mode .soft-amber   { background: rgba(245, 158, 11, 0.2) !important; color: #fde68a !important; }

    .dark-mode .form-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
    }
    .dark-mode .form-card-header {
        border-bottom-color: #334155 !important;
    }
    .dark-mode .form-card-header .card-icon {
        background: #0f172a !important;
        color: #94a3b8 !important;
    }
    .dark-mode .form-card-header h5 {
        color: #f1f5f9 !important;
    }
    .dark-mode .form-card-header p {
        color: #94a3b8 !important;
    }

    .dark-mode .form-card .dataTables_wrapper .row:last-child {
        background: #151c27 !important;
        border-top-color: #334155 !important;
    }

    .dark-mode #tblProducts thead th {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode #tblProducts tbody td {
        border-top-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode #tblProducts tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    .dark-mode .badge-sku {
        background: #0f172a !important;
        color: #cbd5e1 !important;
        border-color: #334155 !important;
    }
    .dark-mode .badge-unit {
        background: rgba(59, 130, 246, 0.15) !important;
        color: #93c5fd !important;
        border-color: rgba(59, 130, 246, 0.3) !important;
    }
    .dark-mode .badge-currency {
        background: #0f172a !important;
        color: #94a3b8 !important;
    }
    .dark-mode .badge-currency-success {
        background: rgba(16, 185, 129, 0.15) !important;
        color: #6ee7b7 !important;
    }
    .dark-mode .product-title-cell span {
        color: #f1f5f9 !important;
    }
    .dark-mode .dropdown-menu-detail {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark-mode .dropdown-menu-detail .dropdown-item {
        color: #cbd5e1 !important;
    }
    .dark-mode .dropdown-menu-detail .dropdown-item:hover {
        background: #334155 !important;
        color: #ffffff !important;
    }
    .dark-mode .dropdown-menu-detail .dropdown-divider {
        border-top-color: #334155 !important;
    }
    .products-list-page-container {
        padding: 0;
        width: 100%;
    }
</style>

<div class="products-list-page-container">
    <div class="products-list-wrapper">
        
        <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 12px; padding: 0;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-cubes"></i>
                </div>
                <div class="page-title-text">
                    <h4>Ürün & Hizmet Kataloğu</h4>
                    <p>Sistemde tanımlı tüm ürün ve hizmetlerin stok, birim ve fiyat listesi</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <?php if (permtrue("product_dashboard") || permtrue("productcategory") || permtrue("productadd")) { ?>
                    <a href="index.php?p=products/dashboard" class="btn btn-outline-primary btn-action-outline" title="Dashboard">
                        <i class="fa fa-dashboard"></i> <span class="d-none d-sm-inline">Dashboard</span>
                    </a>
                <?php } ?>
                <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshProducts" title="Tabloyu Yenile">
                    <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
                </button>
                <button type="button" class="btn btn-outline-success btn-action-outline" id="btnExportProducts" title="Excel Olarak İndir">
                    <i class="fa fa-file-excel-o"></i> <span class="d-none d-sm-inline">Excel'e Aktar</span>
                </button>
                <?php if (permtrue("productadd")) { ?>
                    <a href="index.php?p=products/manage" class="btn btn-action-primary">
                        <i class="fa fa-plus-circle"></i> <span>Yeni Ürün Ekle</span>
                    </a>
                <?php } ?>
            </div>
        </div>

        <!-- KPI Özet / İstatistik Kartları -->
        <div id="kpiSummarySection" class="row mx-0 mb-2 kpi-summary-collapse">
            <!-- Toplam Ürün & Hizmet -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Ürün / Hizmet</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-cubes"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Kayıtlı Katalog</span>
                        <span class="crm-badge-soft soft-primary">Aktif</span>
                    </div>
                </div>
            </div>

            <!-- TRY Fiyatlı Ürünler -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">TRY Fiyatlı Kalemler</span>
                            <div class="crm-kpi-value"><?php echo number_format($tryCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-try"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Yerel Para Birimi</span>
                        <span class="crm-badge-soft soft-emerald">TRY / TL</span>
                    </div>
                </div>
            </div>

            <!-- Dövizli Ürünler (EUR / USD) -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Dövizli Kalemler</span>
                            <div class="crm-kpi-value"><?php echo number_format($foreignCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-purple">
                            <i class="fa fa-globe"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11"><?php echo $eurCount; ?> EUR · <?php echo $usdCount; ?> USD</span>
                        <span class="crm-badge-soft soft-purple">Döviz</span>
                    </div>
                </div>
            </div>

            <!-- Stok Kodlu Ürünler -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Stok Kodu Tanımlı</span>
                            <div class="crm-kpi-value"><?php echo number_format($skuCount, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-amber">
                            <i class="fa fa-barcode"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">%<?php echo $skuRate; ?> SKU Tanımlı</span>
                        <span class="crm-badge-soft soft-amber">Barkod/SKU</span>
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
                        <h5>Ürün ve Hizmet Listesi</h5>
                        <p>Anlık arama, sütun filtreleme ve kayıt yönetimi</p>
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
                <table id="tblProducts" class="data-table table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 38px;" class="no-sort text-center">#Sıra</th>
                            <th style="width: 105px;">Stok Kodu</th>
                            <th style="min-width: 180px;">Ürün/Hizmet Adı</th>
                            <th style="width: 75px;" class="text-center">Birimi</th>
                            <th style="width: 95px;" class="text-right">Alış Fiyatı</th>
                            <th style="width: 105px;" class="text-right">Satış Fiyatı</th>
                            <th style="width: 130px;">Açıklama</th>
                            <th style="width: 85px;" class="text-center">Kayıt Tarihi</th>
                            <th style="width: 85px;" class="no-sort text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-center">#Sıra</th>
                            <th>Stok Kodu</th>
                            <th>Ürün/Hizmet Adı</th>
                            <th>Birimi</th>
                            <th>Alış Fiyatı</th>
                            <th>Satış Fiyatı</th>
                            <th>Açıklama</th>
                            <th>Kayıt Tarihi</th>
                            <th class="text-center">İşlem</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>
