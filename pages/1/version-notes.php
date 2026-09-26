<?php
use App\Model\VersionNoteModel;

$versionModel = new VersionNoteModel();
$stats = $versionModel->getStats();
$userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
$userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? ($_SESSION['perm'] ?? 0)));
$isAdmin = in_array($userId, [1, 12], true) || in_array($userPerm, [1, 13], true) || (function_exists('permtrue') && (permtrue("panelsettings") || permtrue("authdefine")));

// Varsayılan olarak son 1 ayın başlangıç ve bitiş tarihleri
$defaultStartDate = date('Y-m-d', strtotime('-30 days'));
$defaultEndDate = date('Y-m-d');
?>

<style>
/* ==================== Sürüm Notları Modern Tasarımı ==================== */
.vn-container {
    padding-bottom: 40px;
}

.vn-hero-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    border-radius: 20px;
    padding: 28px 30px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.1);
    position: relative;
    overflow: hidden;
    margin-bottom: 22px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.vn-hero-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, rgba(0, 0, 0, 0) 70%);
    pointer-events: none;
    border-radius: 50%;
}

.vn-hero-title {
    font-size: 1.7rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 12px;
}

.vn-hero-subtitle {
    color: #94a3b8;
    font-size: 0.92rem;
    margin-bottom: 18px;
    max-width: 680px;
    line-height: 1.5;
}

.vn-stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.vn-stat-pill {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    padding: 6px 14px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #e2e8f0;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.vn-stat-pill:hover {
    background: rgba(255, 255, 255, 0.14);
    transform: translateY(-2px);
}

.vn-stat-pill .vn-stat-count {
    background: #3b82f6;
    color: #fff;
    font-weight: 700;
    font-size: 0.76rem;
    padding: 2px 7px;
    border-radius: 20px;
}

.vn-stat-pill.stat-feature .vn-stat-count { background: #10b981; }
.vn-stat-pill.stat-improvement .vn-stat-count { background: #3b82f6; }
.vn-stat-pill.stat-bugfix .vn-stat-count { background: #f59e0b; }
.vn-stat-pill.stat-security .vn-stat-count { background: #ef4444; }

/* Kontrol ve Filtreleme Çubuğu */
.vn-controls-container {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    margin-bottom: 22px;
    border: 1px solid #e2e8f0;
}

.vn-controls-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}

.vn-controls-bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 14px;
}

.vn-search-wrapper {
    position: relative;
    flex: 1;
    min-width: 250px;
    max-width: 380px;
}

.vn-search-input {
    width: 100%;
    padding: 8px 14px 8px 38px;
    font-size: 0.88rem;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background-color: #f8fafc;
    color: #1e293b;
    transition: all 0.2s ease;
}

.vn-search-input:focus {
    outline: none;
    border-color: #3b82f6;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.vn-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}

/* Tarih Aralığı Seçim Butonları */
.vn-range-group, .vn-filter-pills, .vn-view-switch {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}

.vn-pill-btn {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    padding: 5px 12px;
    font-size: 0.82rem;
    font-weight: 500;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    user-select: none;
}

.vn-pill-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.vn-pill-btn.active {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
}

.vn-pill-btn.btn-view-switch {
    border-radius: 10px;
    padding: 6px 12px;
}

.vn-pill-btn.btn-view-switch.active {
    background: #2563eb;
    border-color: #2563eb;
}

.vn-custom-date-input {
    width: 220px;
    padding: 5px 10px;
    font-size: 0.82rem;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background-color: #ffffff;
    color: #1e293b;
}

/* Timeline Görünümü */
.vn-timeline {
    position: relative;
    padding-left: 32px;
    margin-top: 10px;
}

.vn-timeline::before {
    content: '';
    position: absolute;
    top: 15px;
    bottom: 15px;
    left: 11px;
    width: 2px;
    background: linear-gradient(180deg, #3b82f6 0%, #cbd5e1 50%, #e2e8f0 100%);
    border-radius: 2px;
}

.vn-item {
    position: relative;
    margin-bottom: 24px;
    transition: transform 0.2s ease;
}

.vn-node {
    position: absolute;
    left: -32px;
    top: 20px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ffffff;
    border: 3px solid #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    transition: all 0.2s ease;
}

.vn-item:hover .vn-node {
    transform: scale(1.15);
}

.vn-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    padding: 22px 24px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}

.vn-card:hover {
    box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.07), 0 4px 8px -4px rgba(0, 0, 0, 0.04);
    border-color: #cbd5e1;
    transform: translateY(-2px);
}

.vn-card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}

.vn-meta-left {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.vn-version-pill {
    font-size: 0.8rem;
    font-weight: 700;
    background: #0f172a;
    color: #ffffff;
    padding: 3px 9px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.vn-category-badge {
    font-size: 0.76rem;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: 1px solid transparent;
}

.badge-vn-feature {
    background-color: rgba(16, 185, 129, 0.12);
    color: #059669;
    border-color: rgba(16, 185, 129, 0.25);
}

.badge-vn-improvement {
    background-color: rgba(59, 130, 246, 0.12);
    color: #2563eb;
    border-color: rgba(59, 130, 246, 0.25);
}

.badge-vn-bugfix {
    background-color: rgba(245, 158, 11, 0.12);
    color: #d97706;
    border-color: rgba(245, 158, 11, 0.25);
}

.badge-vn-security {
    background-color: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    border-color: rgba(239, 68, 68, 0.25);
}

.badge-vn-other {
    background-color: rgba(100, 116, 139, 0.12);
    color: #475569;
    border-color: rgba(100, 116, 139, 0.25);
}

.vn-date-text {
    font-size: 0.82rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
}

.vn-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 10px;
    line-height: 1.4;
    letter-spacing: -0.01em;
}

.vn-body {
    color: #334155;
    font-size: 0.91rem;
    line-height: 1.6;
}

.vn-desc-paragraph {
    margin-bottom: 6px;
}

.vn-desc-paragraph:last-child {
    margin-bottom: 0;
}

.vn-desc-list {
    list-style: none;
    padding: 0;
    margin: 6px 0;
}

.vn-desc-list li {
    position: relative;
    padding-left: 18px;
    margin-bottom: 5px;
    color: #334155;
}

.vn-bullet-icon {
    position: absolute;
    left: 2px;
    top: 3px;
    color: #3b82f6;
    font-size: 0.82rem;
}

.vn-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 14px;
    padding-top: 10px;
    border-top: 1px dashed #e2e8f0;
    font-size: 0.8rem;
    color: #64748b;
}

.vn-author-tag {
    display: flex;
    align-items: center;
    gap: 5px;
}

.vn-actions {
    display: flex;
    gap: 6px;
}

.vn-action-btn {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.76rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.vn-action-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.vn-action-btn.btn-delete:hover {
    background: #fef2f2;
    color: #ef4444;
    border-color: #fecaca;
}

/* Tablo Görünümü Stilleri */
.vn-table-wrapper {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
}

.vn-table {
    width: 100% !important;
    border-collapse: separate;
    border-spacing: 0;
}

.vn-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 0.82rem;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
}

.vn-table td {
    padding: 12px 14px;
    font-size: 0.86rem;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
}

.vn-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Sayfalama (Pagination) */
.vn-pagination-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    margin-top: 24px;
    padding: 14px 20px;
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
}

.vn-pagination-info {
    font-size: 0.85rem;
    color: #64748b;
}

.vn-pagination-btns {
    display: flex;
    gap: 4px;
    align-items: center;
}

.vn-page-btn {
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.vn-page-btn:hover:not(:disabled) {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #cbd5e1;
}

.vn-page-btn.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}

.vn-page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Loading & Empty State */
.vn-loading-overlay {
    text-align: center;
    padding: 50px 20px;
}

.vn-empty-state {
    text-align: center;
    padding: 50px 20px;
    background: #ffffff;
    border-radius: 16px;
    border: 1px dashed #cbd5e1;
}

.vn-empty-icon {
    font-size: 2.8rem;
    color: #94a3b8;
    margin-bottom: 12px;
}

/* ==================== Dark Mode Uyumlamaları ==================== */
body.dark-mode .vn-controls-container,
body.dark-mode .vn-table-wrapper,
body.dark-mode .vn-pagination-container {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

body.dark-mode .vn-controls-top {
    border-bottom-color: #334155;
}

body.dark-mode .vn-search-input,
body.dark-mode .vn-custom-date-input {
    background-color: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

body.dark-mode .vn-search-input:focus {
    border-color: #3b82f6;
    background-color: #0f172a;
}

body.dark-mode .vn-pill-btn {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
}

body.dark-mode .vn-pill-btn:hover {
    background: #334155;
    color: #f8fafc;
}

body.dark-mode .vn-pill-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #ffffff;
}

body.dark-mode .vn-timeline::before {
    background: linear-gradient(180deg, #3b82f6 0%, #334155 50%, #1e293b 100%);
}

body.dark-mode .vn-node {
    background: #1e293b;
    border-color: #3b82f6;
}

body.dark-mode .vn-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

body.dark-mode .vn-card:hover {
    border-color: #475569;
}

body.dark-mode .vn-card-header {
    border-bottom-color: #334155;
}

body.dark-mode .vn-version-pill {
    background: #334155;
    color: #f8fafc;
}

body.dark-mode .vn-title {
    color: #f8fafc;
}

body.dark-mode .vn-body,
body.dark-mode .vn-desc-list li {
    color: #cbd5e1;
}

body.dark-mode .vn-date-text,
body.dark-mode .vn-card-footer,
body.dark-mode .vn-pagination-info {
    color: #94a3b8;
    border-top-color: #334155;
}

body.dark-mode .vn-table th {
    background: #0f172a;
    color: #94a3b8;
    border-bottom-color: #334155;
}

body.dark-mode .vn-table td {
    color: #cbd5e1;
    border-bottom-color: #334155;
}

body.dark-mode .vn-table tbody tr:hover {
    background-color: #0f172a;
}

body.dark-mode .vn-page-btn,
body.dark-mode .vn-action-btn {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
}

body.dark-mode .vn-page-btn:hover:not(:disabled),
body.dark-mode .vn-action-btn:hover {
    background: #334155;
    color: #f8fafc;
}

body.dark-mode .vn-page-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #ffffff;
}

body.dark-mode .vn-empty-state {
    background: #1e293b;
    border-color: #334155;
}
</style>

<div class="vn-container">
    <!-- Hero Banner & KPI Stats -->
    <div class="vn-hero-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
                <h1 class="vn-hero-title">
                    <i class="fa fa-code-fork text-primary"></i> Sürüm Notları
                </h1>
                <p class="vn-hero-subtitle">
                    Aydınoğulları YSC sistemindeki en son güncellemeler, yeni özellikler, güvenlik geliştirmeleri ve performans iyileştirmeleri.
                </p>
            </div>
            <?php if ($isAdmin): ?>
            <div>
                <button type="button" class="btn btn-primary btn-sm px-3 py-2 font-weight-bold" style="border-radius: 10px;" onclick="openVersionModal();">
                    <i class="fa fa-plus-circle mr-1"></i> Yeni Sürüm Notu Ekle
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- İstatistik Sayaçları -->
        <div class="vn-stats-row">
            <div class="vn-stat-pill">
                <i class="fa fa-layer-group"></i> Toplam Güncelleme: <span class="vn-stat-count"><?php echo $stats['total']; ?></span>
            </div>
            <div class="vn-stat-pill stat-feature">
                <i class="fa fa-rocket text-success"></i> Yeni Özellikler: <span class="vn-stat-count"><?php echo $stats['feature']; ?></span>
            </div>
            <div class="vn-stat-pill stat-improvement">
                <i class="fa fa-magic text-info"></i> İyileştirmeler: <span class="vn-stat-count"><?php echo $stats['improvement']; ?></span>
            </div>
            <div class="vn-stat-pill stat-bugfix">
                <i class="fa fa-wrench text-warning"></i> Düzeltmeler: <span class="vn-stat-count"><?php echo $stats['bugfix']; ?></span>
            </div>
            <?php if (!empty($stats['last_date'])): ?>
            <div class="vn-stat-pill">
                <i class="fa fa-clock-o text-muted"></i> Son Güncelleme: <span class="text-white font-weight-bold ml-1"><?php echo date('d.m.Y H:i', strtotime($stats['last_date'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtreler, Zaman Aralığı ve Arama Çubuğu -->
    <div class="vn-controls-container">
        <!-- Üst Satır: Zaman Aralığı ve Görünüm Değiştirici -->
        <div class="vn-controls-top">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted font-weight-bold font-12 mr-1"><i class="fa fa-calendar mr-1"></i> Zaman:</span>
                <div class="vn-range-group" id="vnDateRangeGroup">
                    <button type="button" class="vn-pill-btn active" data-range="1m">Son 1 Ay</button>
                    <button type="button" class="vn-pill-btn" data-range="3m">Son 3 Ay</button>
                    <button type="button" class="vn-pill-btn" data-range="6m">Son 6 Ay</button>
                    <button type="button" class="vn-pill-btn" data-range="year">Bu Yıl (<?php echo date('Y'); ?>)</button>
                    <button type="button" class="vn-pill-btn" data-range="all">Tüm Zamanlar</button>
                </div>
                <div class="ml-2 d-none d-md-block">
                    <input type="text" id="vnCustomDateRange" class="vn-custom-date-input" placeholder="Özel Tarih Aralığı Seç...">
                </div>
            </div>

            <!-- Görünüm Değiştirici (Zaman Çizelgesi / Tablo) -->
            <div class="vn-view-switch">
                <span class="text-muted font-weight-bold font-12 mr-1">Görünüm:</span>
                <button type="button" class="vn-pill-btn btn-view-switch active" id="btnViewTimeline" onclick="switchView('timeline')">
                    <i class="fa fa-stream"></i> Zaman Çizelgesi
                </button>
                <button type="button" class="vn-pill-btn btn-view-switch" id="btnViewTable" onclick="switchView('table')">
                    <i class="fa fa-table"></i> Tablo
                </button>
            </div>
        </div>

        <!-- Alt Satır: Kategori Filtreleri ve Canlı Arama -->
        <div class="vn-controls-bottom">
            <div class="vn-filter-pills" id="vnFilterGroup">
                <span class="text-muted font-weight-bold font-12 mr-1"><i class="fa fa-filter mr-1"></i> Kategori:</span>
                <button type="button" class="vn-pill-btn active" data-category="all">Tümü</button>
                <button type="button" class="vn-pill-btn" data-category="feature"><i class="fa fa-rocket text-success"></i> Yeni Özellik</button>
                <button type="button" class="vn-pill-btn" data-category="improvement"><i class="fa fa-magic text-primary"></i> İyileştirme</button>
                <button type="button" class="vn-pill-btn" data-category="bugfix"><i class="fa fa-wrench text-warning"></i> Hata Düzeltme</button>
                <button type="button" class="vn-pill-btn" data-category="security"><i class="fa fa-shield text-danger"></i> Güvenlik</button>
            </div>

            <div class="vn-search-wrapper">
                <i class="fa fa-search vn-search-icon"></i>
                <input type="text" id="vnSearchInput" class="vn-search-input" placeholder="Başlık, sürüm veya açıklamalarda ara...">
            </div>
        </div>
    </div>

    <!-- Yükleniyor Göstergesi -->
    <div class="vn-loading-overlay d-none" id="vnLoading">
        <i class="fa fa-spinner fa-spin fa-2x text-primary mb-2"></i>
        <div class="text-muted font-13">Sürüm notları yükleniyor...</div>
    </div>

    <!-- 1. GÖRÜNÜM: Zaman Çizelgesi (Timeline) -->
    <div id="vnTimelineContainer">
        <div class="vn-timeline" id="vnTimelineList">
            <!-- AJAX ile dinamik yüklenecek -->
        </div>
    </div>

    <!-- 2. GÖRÜNÜM: Tablo Görünümü -->
    <div id="vnTableContainer" class="d-none">
        <div class="vn-table-wrapper">
            <div class="table-responsive">
                <table class="table vn-table" id="vnDataTable">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Tarih</th>
                            <th style="width: 90px;">Sürüm</th>
                            <th style="width: 120px;">Kategori</th>
                            <th style="width: 250px;">Başlık</th>
                            <th>Açıklama & Maddeler</th>
                            <th style="width: 110px;">Ekleyen</th>
                            <?php if ($isAdmin): ?>
                            <th style="width: 90px; text-align: right;">İşlemler</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="vnTableBody">
                        <!-- AJAX ile dinamik yüklenecek -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Boş Durum (Empty State) -->
    <div class="vn-empty-state d-none" id="vnEmptyState">
        <div class="vn-empty-icon"><i class="fa fa-history"></i></div>
        <h5 class="font-weight-bold text-dark mb-1">Kayıt Bulunamadı</h5>
        <p class="text-muted mb-0">Seçili tarih aralığı veya arama kriterine uygun sürüm notu bulunamadı.</p>
    </div>

    <!-- Sayfalama (Pagination Bar) -->
    <div class="vn-pagination-container" id="vnPaginationBar">
        <div class="vn-pagination-info" id="vnPaginationInfo">
            Yükleniyor...
        </div>
        <div class="vn-pagination-btns" id="vnPaginationBtns">
            <!-- Dinamik butonlar -->
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Sürüm Notu Ekleme / Düzenleme Modal -->
<div class="modal fade" id="versionNoteModal" tabindex="-1" role="dialog" aria-labelledby="versionNoteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);">
            <div class="modal-header bg-dark text-white" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h5 class="modal-title text-white font-weight-bold" id="versionNoteModalTitle">
                    <i class="fa fa-code-fork mr-2 text-primary"></i> Sürüm Notu Ekle / Düzenle
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="versionNoteForm" onsubmit="saveVersionNote(event);">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\App\Helper\Security::csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="id" id="vn_id" value="">
                <input type="hidden" name="action" value="save">
                
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Başlık <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="vn_title" class="form-control" required placeholder="Örn: Servis Formları İyileştirmeleri">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Sürüm Etiketi</label>
                                <input type="text" name="version_tag" id="vn_version_tag" class="form-control" placeholder="Örn: v2.5.0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Kategori <span class="text-danger">*</span></label>
                                <select name="category" id="vn_category" class="form-control" required>
                                    <option value="feature">🚀 Yeni Özellik (Feature)</option>
                                    <option value="improvement">⚡ İyileştirme (Improvement)</option>
                                    <option value="bugfix">🛠️ Hata Düzeltme (Bug Fix)</option>
                                    <option value="security">🔒 Güvenlik (Security)</option>
                                    <option value="other">📋 Genel Güncelleme</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Tarih</label>
                                <input type="text" name="created_at" id="vn_created_at" class="form-control" value="<?php echo date('Y-m-d H:i:s'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Açıklama / Değişiklik Maddeleri <span class="text-danger">*</span></label>
                        <textarea name="description" id="vn_description" class="form-control" rows="6" required placeholder="- Yapılan değişiklik 1&#10;- Yapılan değişiklik 2&#10;- İyileştirilen ekran bilgisi"></textarea>
                        <small class="form-text text-muted">Maddeli liste oluşturmak için satır başlarına tire (-) veya yıldız (*) koyabilirsiniz.</small>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-dismiss="modal">İptal</button>
                    <button type="submit" id="btnSaveVn" class="btn btn-primary px-4 font-weight-bold">
                        <i class="fa fa-check mr-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
var isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
var currentView = 'timeline';
var currentPage = 1;
var perPage = 15;
var currentCategory = 'all';
var currentSearch = '';
var currentStartDate = '<?php echo $defaultStartDate; ?>';
var currentEndDate = '<?php echo $defaultEndDate; ?>';
var searchTimer = null;

$(document).ready(function() {
    // Özel Tarih Seçici Flatpickr
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#vnCustomDateRange", {
            mode: "range",
            dateFormat: "Y-m-d",
            locale: "tr",
            onClose: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    var start = instance.formatDate(selectedDates[0], "Y-m-d");
                    var end = instance.formatDate(selectedDates[1], "Y-m-d");
                    $('#vnDateRangeGroup .vn-pill-btn').removeClass('active');
                    currentStartDate = start;
                    currentEndDate = end;
                    currentPage = 1;
                    loadVersionNotes();
                }
            }
        });

        flatpickr("#vn_created_at", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            time_24hr: true,
            locale: "tr"
        });
    }

    // Tarih Aralığı Butonları
    $('#vnDateRangeGroup .vn-pill-btn').on('click', function() {
        $('#vnDateRangeGroup .vn-pill-btn').removeClass('active');
        $(this).addClass('active');
        $('#vnCustomDateRange').val('');

        var range = $(this).data('range');
        var now = new Date();
        var y = now.getFullYear();
        var m = String(now.getMonth() + 1).padStart(2, '0');
        var d = String(now.getDate()).padStart(2, '0');
        var todayStr = y + '-' + m + '-' + d;

        if (range === '1m') {
            var past = new Date();
            past.setDate(past.getDate() - 30);
            currentStartDate = formatDate(past);
            currentEndDate = todayStr;
        } else if (range === '3m') {
            var past = new Date();
            past.setDate(past.getDate() - 90);
            currentStartDate = formatDate(past);
            currentEndDate = todayStr;
        } else if (range === '6m') {
            var past = new Date();
            past.setDate(past.getDate() - 180);
            currentStartDate = formatDate(past);
            currentEndDate = todayStr;
        } else if (range === 'year') {
            currentStartDate = y + '-01-01';
            currentEndDate = todayStr;
        } else if (range === 'all') {
            currentStartDate = '';
            currentEndDate = '';
        }

        currentPage = 1;
        loadVersionNotes();
    });

    // Kategori Filtresi
    $('#vnFilterGroup .vn-pill-btn').on('click', function() {
        $('#vnFilterGroup .vn-pill-btn').removeClass('active');
        $(this).addClass('active');
        currentCategory = $(this).data('category');
        currentPage = 1;
        loadVersionNotes();
    });

    // Canlı Arama (Debounced)
    $('#vnSearchInput').on('keyup input', function() {
        clearTimeout(searchTimer);
        var val = $(this).val().trim();
        searchTimer = setTimeout(function() {
            currentSearch = val;
            currentPage = 1;
            loadVersionNotes();
        }, 300);
    });

    // İlk Yükleme (Son 1 Ay)
    loadVersionNotes();
});

function formatDate(d) {
    var year = d.getFullYear();
    var month = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
}

function switchView(view) {
    currentView = view;
    $('.btn-view-switch').removeClass('active');
    if (view === 'timeline') {
        $('#btnViewTimeline').addClass('active');
        $('#vnTableContainer').addClass('d-none');
        $('#vnTimelineContainer').removeClass('d-none');
    } else {
        $('#btnViewTable').addClass('active');
        $('#vnTimelineContainer').addClass('d-none');
        $('#vnTableContainer').removeClass('d-none');
    }
}

function loadVersionNotes() {
    $('#vnLoading').removeClass('d-none');
    $('#vnEmptyState').addClass('d-none');

    $.ajax({
        url: 'App/api/version-notes.php',
        type: 'GET',
        data: {
            action: 'list',
            page: currentPage,
            per_page: perPage,
            category: currentCategory,
            search: currentSearch,
            start_date: currentStartDate,
            end_date: currentEndDate
        },
        dataType: 'json',
        success: function(res) {
            $('#vnLoading').addClass('d-none');
            if (res.status === 'success') {
                renderNotes(res.data, res.pagination);
            } else {
                toastr.error(res.message || 'Veriler yüklenemedi.');
            }
        },
        error: function() {
            $('#vnLoading').addClass('d-none');
            toastr.error('Sunucu ile iletişim kurulurken bir hata oluştu.');
        }
    });
}

function renderNotes(items, pagination) {
    var timelineHtml = '';
    var tableHtml = '';

    if (!items || items.length === 0) {
        $('#vnEmptyState').removeClass('d-none');
        $('#vnTimelineList').html('');
        $('#vnTableBody').html('');
        $('#vnPaginationBar').addClass('d-none');
        return;
    }

    $('#vnEmptyState').addClass('d-none');
    $('#vnPaginationBar').removeClass('d-none');

    items.forEach(function(note) {
        var cat = note.category_info;
        var versionTagBadge = note.version_tag ? '<span class="vn-version-pill"><i class="fa fa-tag"></i> ' + note.version_tag + '</span>' : '';
        
        var adminActionBtns = '';
        if (isAdmin) {
            adminActionBtns = '<div class="vn-actions">' +
                '<button type="button" class="vn-action-btn" title="Düzenle" onclick="editVersionNote(' + note.id + ')"><i class="fa fa-pencil"></i> Düzenle</button>' +
                '<button type="button" class="vn-action-btn btn-delete" title="Sil" onclick="deleteVersionNote(' + note.id + ')"><i class="fa fa-trash-o"></i> Sil</button>' +
            '</div>';
        }

        // 1. Timeline Kart HTML
        timelineHtml += '<div class="vn-item" data-id="' + note.id + '">' +
            '<div class="vn-node" style="border-color: ' + cat.color + ';">' +
                '<i class="fa ' + cat.icon + '" style="color: ' + cat.color + '; font-size: 10px;"></i>' +
            '</div>' +
            '<div class="vn-card">' +
                '<div class="vn-card-header">' +
                    '<div class="vn-meta-left">' +
                        versionTagBadge +
                        '<span class="vn-category-badge ' + cat.class + '"><i class="fa ' + cat.icon + '"></i> ' + cat.name + '</span>' +
                    '</div>' +
                    '<div class="vn-date-text"><i class="fa fa-calendar-o"></i> ' + note.formatted_date + '</div>' +
                '</div>' +
                '<h3 class="vn-title">' + note.title + '</h3>' +
                '<div class="vn-body">' + note.rendered_description + '</div>' +
                '<div class="vn-card-footer">' +
                    '<div class="vn-author-tag"><i class="fa fa-user-circle-o text-muted"></i> <span>' + note.author + '</span></div>' +
                    adminActionBtns +
                '</div>' +
            '</div>' +
        '</div>';

        // 2. Tablo Satır HTML
        var tableActionBtns = '';
        if (isAdmin) {
            tableActionBtns = '<td style="text-align: right;">' +
                '<button class="btn btn-sm btn-light p-1 px-2 mr-1" onclick="editVersionNote(' + note.id + ')" title="Düzenle"><i class="fa fa-pencil text-primary"></i></button>' +
                '<button class="btn btn-sm btn-light p-1 px-2" onclick="deleteVersionNote(' + note.id + ')" title="Sil"><i class="fa fa-trash text-danger"></i></button>' +
            '</td>';
        }

        tableHtml += '<tr>' +
            '<td class="font-weight-bold text-nowrap"><i class="fa fa-calendar-o text-muted mr-1"></i> ' + note.formatted_date + '</td>' +
            '<td>' + (note.version_tag ? '<span class="badge badge-dark">' + note.version_tag + '</span>' : '<span class="text-muted">-</span>') + '</td>' +
            '<td><span class="vn-category-badge ' + cat.class + '"><i class="fa ' + cat.icon + '"></i> ' + cat.name + '</span></td>' +
            '<td class="font-weight-bold">' + note.title + '</td>' +
            '<td><div class="vn-body font-12">' + note.rendered_description + '</div></td>' +
            '<td><span class="text-muted"><i class="fa fa-user mr-1"></i> ' + note.author + '</span></td>' +
            tableActionBtns +
        '</tr>';
    });

    $('#vnTimelineList').html(timelineHtml);
    $('#vnTableBody').html(tableHtml);

    renderPagination(pagination);
}

function renderPagination(p) {
    if (!p) return;
    var start = (p.page - 1) * p.per_page + 1;
    var end = Math.min(p.page * p.per_page, p.total);
    $('#vnPaginationInfo').html('Toplam <b>' + p.total + '</b> kayıttan <b>' + start + ' - ' + end + '</b> arası gösteriliyor (Sayfa ' + p.page + ' / ' + p.total_pages + ')');

    var btnsHtml = '';
    btnsHtml += '<button type="button" class="vn-page-btn" ' + (!p.has_prev ? 'disabled' : '') + ' onclick="goToPage(' + (p.page - 1) + ')"><i class="fa fa-angle-left"></i></button>';

    var maxButtons = 5;
    var startPage = Math.max(1, p.page - Math.floor(maxButtons / 2));
    var endPage = Math.min(p.total_pages, startPage + maxButtons - 1);

    if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }

    for (var i = startPage; i <= endPage; i++) {
        btnsHtml += '<button type="button" class="vn-page-btn ' + (i === p.page ? 'active' : '') + '" onclick="goToPage(' + i + ')">' + i + '</button>';
    }

    btnsHtml += '<button type="button" class="vn-page-btn" ' + (!p.has_next ? 'disabled' : '') + ' onclick="goToPage(' + (p.page + 1) + ')"><i class="fa fa-angle-right"></i></button>';

    $('#vnPaginationBtns').html(btnsHtml);
}

function goToPage(page) {
    if (page < 1) return;
    currentPage = page;
    loadVersionNotes();
    $('html, body').animate({ scrollTop: $('.vn-controls-container').offset().top - 20 }, 200);
}

<?php if ($isAdmin): ?>
function openVersionModal() {
    $('#versionNoteForm')[0].reset();
    $('#vn_id').val('');
    $('#vn_created_at').val('<?php echo date('Y-m-d H:i:s'); ?>');
    $('#versionNoteModalTitle').html('<i class="fa fa-plus-circle mr-2 text-primary"></i> Yeni Sürüm Notu Ekle');
    $('#versionNoteModal').modal('show');
}

function editVersionNote(id) {
    $.ajax({
        url: 'App/api/version-notes.php',
        type: 'GET',
        data: { action: 'get', id: id },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success' && res.data) {
                var d = res.data;
                $('#vn_id').val(d.id);
                $('#vn_title').val(d.title);
                $('#vn_version_tag').val(d.version_tag || '');
                $('#vn_category').val(d.category || 'feature');
                $('#vn_created_at').val(d.created_at || '');
                $('#vn_description').val(d.description || '');
                
                $('#versionNoteModalTitle').html('<i class="fa fa-pencil mr-2 text-primary"></i> Sürüm Notunu Düzenle');
                $('#versionNoteModal').modal('show');
            } else {
                toastr.error(res.message || 'Kayıt verisi alınamadı.');
            }
        },
        error: function() {
            toastr.error('Sunucu ile iletişim kurulurken bir hata oluştu.');
        }
    });
}

function saveVersionNote(e) {
    e.preventDefault();
    var form = $('#versionNoteForm');
    var btn = $('#btnSaveVn');
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');

    $.ajax({
        url: 'App/api/version-notes.php',
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Kaydet');
            if (res.status === 'success') {
                toastr.success(res.message);
                $('#versionNoteModal').modal('hide');
                loadVersionNotes();
            } else {
                toastr.error(res.message || 'Kayıt sırasında bir hata oluştu.');
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Kaydet');
            var errMsg = 'Kayıt sırasında bir hata oluştu.';
            try {
                var json = JSON.parse(xhr.responseText);
                if (json.message) errMsg = json.message;
            } catch(e) {}
            toastr.error(errMsg);
        }
    });
}

function deleteVersionNote(id) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu sürüm notunu silmek istediğinizden emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'App/api/version-notes.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        toastr.success(res.message);
                        loadVersionNotes();
                    } else {
                        toastr.error(res.message || 'Silme işlemi başarısız oldu.');
                    }
                },
                error: function() {
                    toastr.error('Sunucu hatası oluştu.');
                }
            });
        }
    });
}
<?php endif; ?>
</script>