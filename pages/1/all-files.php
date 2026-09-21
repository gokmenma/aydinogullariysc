<?php

$userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
$userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? 0));

// Yetki kontrolü
if (!permtrue("fileview") && !permtrue("fileadd") && !permtrue("filedelete") && $userPerm !== 1 && $userId !== 1 && $userId !== 12) {
    header("Location: index.php?error=nopermission");
    exit;
}

$canAdd = permtrue("fileadd") || $userPerm === 1 || $userId === 1 || $userId === 12;
$canDelete = permtrue("filedelete") || $userPerm === 1 || $userId === 1 || $userId === 12;

// Dosya Yükleme İşlemi (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (!$canAdd) {
        header("Location: index.php?p=all-files&st=noperm");
        exit;
    }

    if (isset($_FILES["dosya"]) && $_FILES["dosya"]["error"] === UPLOAD_ERR_OK) {
        $cid = (int)($_POST["cid"] ?? 0);
        $originalName = $_FILES["dosya"]["name"];
        $fileSize = (int)$_FILES["dosya"]["size"];
        $tmpName = $_FILES["dosya"]["tmp_name"];

        // Güvenli dosya adı oluşturma
        $fileInfo = pathinfo($originalName);
        $extension = strtolower($fileInfo['extension'] ?? '');
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileInfo['filename'] ?? 'file');
        if (strlen($baseName) > 40) {
            $baseName = substr($baseName, 0, 40);
        }
        
        $uniquePrefix = date('Ymd_His') . '_' . bin2hex(random_bytes(3));
        $storedFileName = $uniquePrefix . '_' . $baseName . ($extension ? '.' . $extension : '');

        $targetDir = __DIR__ . "/../../files/";
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
        $targetPath = $targetDir . $storedFileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            $ins = $ac->prepare("
                INSERT INTO upfiles (cid, filename, size, creativer, regdate) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $ins->execute([$cid, $storedFileName, $fileSize, $userId]);
            $newFileId = $ac->lastInsertId();

            if (function_exists('audit_log')) {
                audit_log(
                    "insert",
                    "all-files",
                    "Yeni dosya yüklendi: " . $storedFileName . " (" . $originalName . ")",
                    "upfiles",
                    $newFileId
                );
            }

            header("Location: index.php?p=all-files&st=newsuccess");
            exit;
        } else {
            header("Location: index.php?p=all-files&st=uploaderror");
            exit;
        }
    } else {
        header("Location: index.php?p=all-files&st=invalidfile");
        exit;
    }
}

// GET üzerinden geleneksel Silme Fallback
if (isset($_GET["mode"]) && $_GET["mode"] === "delete" && isset($_GET["id"])) {
    if (!$canDelete) {
        header("Location: index.php?p=all-files&st=noperm");
        exit;
    }

    $fileId = (int)$_GET["id"];
    $fileQ = $ac->prepare("SELECT * FROM upfiles WHERE id = ?");
    $fileQ->execute([$fileId]);
    $fileData = $fileQ->fetch(PDO::FETCH_ASSOC);

    if ($fileData) {
        $physicalFile = __DIR__ . "/../../files/" . $fileData["filename"];
        if (file_exists($physicalFile) && is_file($physicalFile)) {
            @unlink($physicalFile);
        }

        $delQ = $ac->prepare("DELETE FROM upfiles WHERE id = ?");
        $delQ->execute([$fileId]);

        if (function_exists('audit_log')) {
            audit_log("delete", "all-files", "Dosya silindi: #" . $fileId . " - " . $fileData["filename"], "upfiles", $fileId, $fileData);
        }

        header("Location: index.php?p=all-files&st=deleted");
        exit;
    }
}

// Dosya Boyutu Biçimlendirici Yardımcı Fonksiyon
if (!function_exists('formatFileBytes')) {
    function formatFileBytes($bytes, $precision = 2) {
        $bytes = (float)$bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// Dosya Uzantısı İkon ve Renk Belirleme
if (!function_exists('getFileTypeBadge')) {
    function getFileTypeBadge($fileName) {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'pdf':
                return ['icon' => 'fa fa-file-pdf-o', 'bg' => '#fee2e2', 'color' => '#dc2626', 'label' => 'PDF'];
            case 'doc':
            case 'docx':
                return ['icon' => 'fa fa-file-word-o', 'bg' => '#e0e7ff', 'color' => '#4338ca', 'label' => strtoupper($ext)];
            case 'xls':
            case 'xlsx':
            case 'csv':
                return ['icon' => 'fa fa-file-excel-o', 'bg' => '#dcfce7', 'color' => '#15803d', 'label' => strtoupper($ext)];
            case 'ppt':
            case 'pptx':
                return ['icon' => 'fa fa-file-powerpoint-o', 'bg' => '#ffedd5', 'color' => '#c2410c', 'label' => strtoupper($ext)];
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'webp':
            case 'svg':
                return ['icon' => 'fa fa-file-image-o', 'bg' => '#f3e8ff', 'color' => '#7e22ce', 'label' => strtoupper($ext)];
            case 'zip':
            case 'rar':
            case '7z':
            case 'tar':
            case 'gz':
                return ['icon' => 'fa fa-file-archive-o', 'bg' => '#fef3c7', 'color' => '#b45309', 'label' => strtoupper($ext)];
            case 'txt':
            case 'sql':
            case 'log':
            case 'json':
                return ['icon' => 'fa fa-file-text-o', 'bg' => '#f1f5f9', 'color' => '#475569', 'label' => strtoupper($ext)];
            default:
                return ['icon' => 'fa fa-file-o', 'bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $ext ? strtoupper($ext) : 'FILE'];
        }
    }
}

// KPI İstatistikleri
$statsFiles = $ac->query("SELECT COUNT(*) FROM upfiles")->fetchColumn() ?: 0;
$statsSize = $ac->query("SELECT SUM(CAST(size AS UNSIGNED)) FROM upfiles")->fetchColumn() ?: 0;
$statsCats = $ac->query("SELECT COUNT(*) FROM upfile_categories")->fetchColumn() ?: 0;
$statsRecent = $ac->query("SELECT COUNT(*) FROM upfiles WHERE regdate >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn() ?: 0;

// Dosya Kategorileri Listesi
$catListQ = $ac->query("SELECT id, title FROM upfile_categories ORDER BY title ASC");
$allCategories = $catListQ->fetchAll(PDO::FETCH_ASSOC);

// Dosyaları Listele
$sql = "
    SELECT f.*, 
           c.title AS category_title, 
           u.username AS uploader_name,
           u.email AS uploader_email
    FROM upfiles f
    LEFT JOIN upfile_categories c ON c.id = f.cid
    LEFT JOIN users u ON u.id = f.creativer
    ORDER BY f.id DESC
";
$filesStmt = $ac->prepare($sql);
$filesStmt->execute();
$fileList = $filesStmt->fetchAll(PDO::FETCH_ASSOC);

$st = $_GET['st'] ?? '';
$openUpload = isset($_GET['open_upload']) && $_GET['open_upload'] == 1;
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_allfiles_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-allfiles-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* Teklifler sayfası ile birebir uyumlu stil tanımları */
    .all-files-wrapper {
        width: 100%;
    }

    /* KPI Summary Cards */
    .crm-kpi-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 14px 16px;
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
        margin-bottom: 8px;
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
        font-size: 22px;
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
        font-size: 16px;
        flex-shrink: 0;
    }
    .icon-primary { background: #eff6ff; color: #2563eb; }
    .icon-emerald { background: #ecfdf5; color: #059669; }
    .icon-sky     { background: #f0f9ff; color: #0284c7; }
    .icon-amber   { background: #fffbeb; color: #d97706; }
    .icon-rose    { background: #fff1f2; color: #e11d48; }

    .crm-kpi-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
        font-size: 11px;
    }
    .crm-badge-soft {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 10.5px;
    }
    .soft-primary { background: #dbeafe; color: #1e40af; }
    .soft-emerald { background: #d1fae5; color: #065f46; }
    .soft-sky     { background: #e0f2fe; color: #0369a1; }
    .soft-amber   { background: #fef3c7; color: #92400e; }
    .soft-rose    { background: #ffe4e6; color: #9f1239; }

    /* KPI Collapse Animation */
    .kpi-allfiles-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    /* Form & Table Card styling */
    .form-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 0 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        margin-bottom: 0;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 10px;
    }

    .form-card-header .header-left-inner {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-card-header .card-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        background: #f1f5f9;
        color: #475569;
    }

    .form-card-header h5 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
    }
    .form-card-header p {
        margin: 1px 0 0 0;
        font-size: 11.5px;
        color: #64748b;
    }

    .form-card .responsive {
        padding: 4px !important;
        overflow-x: hidden;
        overflow-y: visible;
        width: 100%;
        min-height: 280px;
    }

    /* Page Header Styles */
    .page-title-box {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .page-title-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
    }
    .page-title-text h4 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.3px;
    }
    .page-title-text p {
        margin: 1px 0 0 0;
        font-size: 12px;
        color: #64748b;
    }

    /* Action Buttons in Header */
    .btn-action-primary {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff !important;
        border: none;
        border-radius: 6px;
        padding: 6px 14px;
        font-weight: 600;
        font-size: 12.5px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);
        transition: all 0.2s ease;
        height: 34px;
        text-decoration: none;
        cursor: pointer;
    }
    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(2, 132, 199, 0.35);
        color: #fff !important;
    }
    .btn-action-outline {
        border-radius: 6px;
        padding: 6px 12px;
        height: 34px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
    }

    /* Global Search Box in Header */
    .dt-header-filter-box {
        display: flex;
        align-items: center;
        margin: 0;
    }
    .dt-header-filter-box .dataTables_filter {
        margin: 0 !important;
        padding: 0 !important;
    }
    .dt-header-filter-box .dataTables_filter label {
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
        font-size: 0 !important;
        position: relative !important;
    }
    .dt-header-filter-box .dataTables_filter input {
        height: 34px !important;
        min-width: 220px !important;
        padding: 6px 12px 6px 34px !important;
        font-size: 12.5px !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #ffffff !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: left 10px center !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.2s ease !important;
        margin: 0 !important;
    }
    .dt-header-filter-box .dataTables_filter input:focus {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
        outline: none !important;
    }

    /* Hide cards toggle button */
    .btn-toggle-kpi-custom {
        border-radius: 6px !important;
        width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        color: #64748b !important;
        transition: all 0.2s ease !important;
        cursor: pointer !important;
    }
    .btn-toggle-kpi-custom:hover {
        background: #f8fafc !important;
        color: #1e293b !important;
        border-color: #94a3b8 !important;
    }

    /* Table specifics */
    table.data-table {
        width: 100% !important;
    }
    table.data-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 14px;
        border-bottom: 2px solid #e2e8f0;
    }
    table.data-table tbody td {
        padding: 11px 14px;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
    }

    .file-item-box {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .file-type-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }
    .file-title-link {
        font-weight: 600;
        color: #1e293b;
        text-decoration: none;
        word-break: break-all;
    }
    .file-title-link:hover {
        color: #0284c7;
        text-decoration: underline;
    }

    .uploader-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .uploader-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10.5px;
        font-weight: 700;
    }

    .badge-category {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        background: #e0f2fe;
        color: #0369a1;
    }
    .badge-category.empty {
        background: #f1f5f9;
        color: #64748b;
    }

    .btn-table-action {
        width: 30px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 12px;
        transition: all 0.15s ease;
        text-decoration: none !important;
    }
    .btn-table-action:hover {
        transform: translateY(-1px);
    }

    /* Modal Dropzone */
    .upload-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 28px 20px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .upload-dropzone:hover, .upload-dropzone.dragover {
        border-color: #0284c7;
        background: #f0f9ff;
    }
    .upload-dropzone-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #e0f2fe;
        color: #0284c7;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-bottom: 8px;
    }
    .selected-file-preview {
        display: none;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        margin-top: 12px;
    }

    /* Dark Mode Overrides */
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
    .dark-mode .form-card {
        background: #282828 !important;
        border-color: #383838 !important;
    }
    .dark-mode .form-card-header {
        border-bottom: 2px solid #383838 !important;
    }
    .dark-mode .form-card-header h5 { color: #60a5fa !important; }
    .dark-mode .form-card-header p { color: #94a3b8 !important; }
    .dark-mode .form-card-header .card-icon {
        background: #1e293b !important;
        color: #60a5fa !important;
    }
    .dark-mode table.data-table thead th {
        background: #1e293b !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode table.data-table tbody td {
        color: #cbd5e1 !important;
        border-bottom-color: #334155 !important;
    }
    .dark-mode .file-title-link { color: #f1f5f9 !important; }
    .dark-mode .file-title-link:hover { color: #38bdf8 !important; }
    .dark-mode .dt-header-filter-box .dataTables_filter input {
        background-color: #1e293b !important;
        border-color: #475569 !important;
        color: #f8fafc !important;
    }
    .dark-mode .btn-toggle-kpi-custom {
        background: #1e293b !important;
        border-color: #475569 !important;
        color: #94a3b8 !important;
    }
    .dark-mode .btn-toggle-kpi-custom:hover {
        background: #334155 !important;
        color: #f8fafc !important;
    }
</style>

<div class="pd-ltr-20 xs-pd-20-10">
    <div class="all-files-wrapper">
        <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap px-1" style="gap: 12px;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-folder-open"></i>
                </div>
                <div class="page-title-text">
                    <h4>Dosya Yönetimi</h4>
                    <p>Sistem genelindeki döküman ve dosyaları görüntüleyin, yükleyin ve yönetin</p>
                </div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <a href="index.php?p=file-categories" class="btn btn-outline-primary btn-action-outline">
                    <i class="fa fa-tags"></i> <span class="d-none d-sm-inline">Dosya Kategorileri</span>
                </a>

                <?php if ($canAdd): ?>
                <button type="button" class="btn btn-action-primary" data-toggle="modal" data-target="#modalUploadFile">
                    <i class="fa fa-cloud-upload"></i> <span>Yeni Dosya Yükle</span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- KPI Summary Section (4'lü Kart Yapısı) -->
        <div class="row mx-0 mb-3" id="kpiSummarySection">
            <!-- Toplam Dosya -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Dosya</span>
                            <div class="crm-kpi-value"><?php echo number_format($statsFiles, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-files-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Sistem Genelinde</span>
                        <span class="crm-badge-soft soft-primary">Aktif Arşiv</span>
                    </div>
                </div>
            </div>

            <!-- Toplam Depolama -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Depolama</span>
                            <div class="crm-kpi-value"><?php echo formatFileBytes($statsSize); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-database"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Kullanılan Alan</span>
                        <span class="crm-badge-soft soft-emerald">Disk Boyutu</span>
                    </div>
                </div>
            </div>

            <!-- Dosya Kategorileri -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Kategoriler</span>
                            <div class="crm-kpi-value"><?php echo number_format($statsCats, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-amber">
                            <i class="fa fa-tags"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Tanımlı Gruplar</span>
                        <span class="crm-badge-soft soft-amber">Sınıflandırma</span>
                    </div>
                </div>
            </div>

            <!-- Son 30 Gün -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Son 30 Gün</span>
                            <div class="crm-kpi-value"><?php echo number_format($statsRecent, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-sky">
                            <i class="fa fa-calendar-check-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Yeni Yüklemeler</span>
                        <span class="crm-badge-soft soft-sky">Bu Ay</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste Card (Teklifler Sayfasıyla Birebir Form Card) -->
        <div class="form-card animate-fade-in mx-1">
            <div class="form-card-header d-flex justify-content-between align-items-center">
                <div class="header-left-inner">
                    <div class="card-icon">
                        <i class="fa fa-list"></i>
                    </div>
                    <div>
                        <h5>Dosya Listesi</h5>
                        <p>Anlık arama ve dosya yönetimi</p>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <!-- Global DataTable Arama Kutusu Taşıma Alanı -->
                    <div id="filesSearchContainer" class="dt-header-filter-box"></div>

                    <button type="button" id="toggleKpiSummary" class="btn btn-toggle-kpi-custom" title="Özet Kartlarını Gizle / Göster">
                        <i class="fa fa-chevron-up" id="kpiChevronIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Tablo Alanı -->
            <div class="responsive">
                <table id="filesTable" class="data-table table-hover table-bordered" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">Sıra No</th>
                            <th>Dosya Adı</th>
                            <th style="width: 170px;">Kategori</th>
                            <th style="width: 110px;">Boyut</th>
                            <th style="width: 170px;">Yükleyen</th>
                            <th style="width: 140px;">Yükleme Tarihi</th>
                            <th class="no-export text-center" style="width: 1%; white-space: nowrap;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fileList)): ?>
                        <tr class="odd data-row text-center">
                            <td colspan="7" class="py-4 text-muted">
                                <i class="fa fa-folder-open-o mr-1"></i> Kayıtlı dosya bulunamadı.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $rowNum = 1; foreach ($fileList as $file): ?>
                            <?php 
                                $badge = getFileTypeBadge($file['filename']);
                                $filePath = "files/" . htmlspecialchars($file['filename'], ENT_QUOTES, 'UTF-8');
                                $userInitial = !empty($file['uploader_name']) ? mb_strtoupper(mb_substr($file['uploader_name'], 0, 1, 'UTF-8'), 'UTF-8') : '?';
                            ?>
                            <tr>
                                <td class="text-center font-weight-bold text-muted"><?php echo $rowNum++; ?></td>
                                <td>
                                    <div class="file-item-box">
                                        <div class="file-type-icon" style="background: <?php echo $badge['bg']; ?>; color: <?php echo $badge['color']; ?>;">
                                            <i class="<?php echo $badge['icon']; ?>"></i>
                                        </div>
                                        <div>
                                            <a href="<?php echo $filePath; ?>" target="_blank" class="file-title-link" title="İndir / Önizle">
                                                <?php echo htmlspecialchars($file['filename'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($file['category_title'])): ?>
                                        <span class="badge-category">
                                            <i class="fa fa-tag font-10"></i>
                                            <?php echo htmlspecialchars($file['category_title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-category empty">Kategorisiz</span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-weight-600 text-muted font-12">
                                    <?php echo formatFileBytes($file['size']); ?>
                                </td>
                                <td>
                                    <div class="uploader-badge">
                                        <div class="uploader-avatar">
                                            <?php echo $userInitial; ?>
                                        </div>
                                        <span class="font-12 font-weight-600 text-dark">
                                            <?php echo htmlspecialchars($file['uploader_name'] ?? 'Sistem', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-muted font-12">
                                    <i class="fa fa-clock-o mr-1 text-secondary"></i>
                                    <?php echo !empty($file['regdate']) ? date('d.m.Y H:i', strtotime($file['regdate'])) : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex" style="gap: 4px;">
                                        <a href="<?php echo $filePath; ?>" target="_blank" download class="btn btn-table-action btn-outline-primary" title="İndir">
                                            <i class="fa fa-download"></i>
                                        </a>

                                        <?php if ($canDelete): ?>
                                        <button type="button" class="btn btn-table-action btn-outline-danger" 
                                                onclick="deleteRecord('Dosyayı sistemden kalıcı olarak silmek istediğinize emin misiniz?','<?php echo (int)$file['id']; ?>','all-files')"
                                                title="Dosyayı Sil">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Yeni Dosya Yükle -->
<div class="modal fade" id="modalUploadFile" tabindex="-1" role="dialog" aria-labelledby="modalUploadFileLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-light border-0 py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title font-16 font-weight-bold text-dark" id="modalUploadFileLabel">
                    <i class="fa fa-cloud-upload text-primary mr-2"></i>Yeni Dosya Yükle
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="index.php?p=all-files" enctype="multipart/form-data" id="uploadFileForm">
                <input type="hidden" name="action" value="upload">
                <div class="modal-body p-4">
                    <!-- Dropzone Area -->
                    <div class="form-group mb-3">
                        <label class="font-weight-600 text-dark font-13 mb-2">
                            <span class="text-danger font-weight-bold mr-1">*</span>Dosya Seçin
                        </label>
                        <div class="upload-dropzone" id="dropzoneArea" onclick="document.getElementById('fileInput').click();">
                            <input type="file" name="dosya" id="fileInput" class="d-none" required onchange="handleFileSelected(this)">
                            <div class="upload-dropzone-icon">
                                <i class="fa fa-cloud-upload"></i>
                            </div>
                            <h6 class="font-14 font-weight-bold text-dark mb-1">Dosyayı buraya sürükleyin veya tıklayın</h6>
                            <p class="text-muted font-12 mb-0">PDF, Word, Excel, Görsel veya Arşiv dosyaları</p>
                        </div>

                        <!-- Selected File Preview -->
                        <div class="selected-file-preview" id="filePreviewBox">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2 overflow-hidden mr-2">
                                    <i class="fa fa-file-text-o text-primary font-18"></i>
                                    <div class="overflow-hidden">
                                        <div class="font-13 font-weight-600 text-dark text-truncate" id="previewFileName">-</div>
                                        <div class="font-11 text-muted" id="previewFileSize">-</div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm text-danger p-1" onclick="clearSelectedFile(event)" title="Kaldır">
                                    <i class="fa fa-times font-14"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Category Selector -->
                    <div class="form-group mb-0">
                        <label class="font-weight-600 text-dark font-13 mb-2">
                            <span class="text-danger font-weight-bold mr-1">*</span>Dosya Kategorisi
                        </label>
                        <select name="cid" class="form-control form-control-lg font-14 selectpicker" data-style="border bg-white" data-live-search="true" required>
                            <option value="" disabled selected>Kategori Seçiniz...</option>
                            <?php foreach ($allCategories as $catOption): ?>
                                <option value="<?php echo (int)$catOption['id']; ?>">
                                    <?php echo htmlspecialchars($catOption['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-secondary font-13 px-3" data-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-action-primary font-13 px-4" id="btnUploadSubmit">
                        <i class="fa fa-upload mr-1"></i> Dosyayı Yükle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // KPI Summary Toggle (Teklifler Sayfasıyla Birebir)
    $('#toggleKpiSummary').on('click', function() {
        var section = document.getElementById('kpiSummarySection');
        var icon = document.getElementById('kpiChevronIcon');
        var isHidden = $(section).is(':hidden') || document.documentElement.classList.contains('kpi-allfiles-collapsed-early');

        if (isHidden) {
            $(section).slideDown(200);
            document.documentElement.classList.remove('kpi-allfiles-collapsed-early');
            icon.className = 'fa fa-chevron-up';
            localStorage.setItem('aydinogullari_kpi_allfiles_collapsed', 'false');
        } else {
            $(section).slideUp(200);
            icon.className = 'fa fa-chevron-down';
            localStorage.setItem('aydinogullari_kpi_allfiles_collapsed', 'true');
        }
    });

    // Global DataTable Arama Kutusunu Header'a Yerleştirme
    function initDataTableSearch() {
        var filterEl = $("#filesTable_wrapper .dataTables_filter");
        if (filterEl.length && $("#filesSearchContainer").length) {
            if (!$("#filesSearchContainer").find(".dataTables_filter").length) {
                filterEl.appendTo("#filesSearchContainer");
                filterEl.find("input").attr("placeholder", "Dosyalarda ara...").attr("autocomplete", "off");
            }
        }
    }

    // Dosya Seçimi İşleme
    function handleFileSelected(input) {
        if (input.files && input.files[0]) {
            var file = input.files[0];
            document.getElementById('previewFileName').innerText = file.name;
            document.getElementById('previewFileSize').innerText = formatBytesJS(file.size);
            document.getElementById('filePreviewBox').style.display = 'block';
            document.getElementById('dropzoneArea').style.borderColor = '#16a34a';
        }
    }

    function clearSelectedFile(e) {
        if (e) e.stopPropagation();
        var input = document.getElementById('fileInput');
        input.value = '';
        document.getElementById('filePreviewBox').style.display = 'none';
        document.getElementById('dropzoneArea').style.borderColor = '#cbd5e1';
    }

    function formatBytesJS(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Drag and Drop Desteği
    var dropzone = document.getElementById('dropzoneArea');
    if (dropzone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            }, false);
        });

        dropzone.addEventListener('drop', function(e) {
            var dt = e.dataTransfer;
            var files = dt.files;
            if (files.length > 0) {
                var input = document.getElementById('fileInput');
                input.files = files;
                handleFileSelected(input);
            }
        }, false);
    }

    // Yükleme Başlatıldığında Buton Durumu
    $('#uploadFileForm').on('submit', function() {
        var btn = document.getElementById('btnUploadSubmit');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Yükleniyor...';
        }
    });

    // Sayfa Yüklendiğinde
    $(document).ready(function() {
        if (localStorage.getItem('aydinogullari_kpi_allfiles_collapsed') === 'true') {
            var icon = document.getElementById('kpiChevronIcon');
            if (icon) icon.className = 'fa fa-chevron-down';
        }

        // Global Arama Kutusunu Taşı
        initDataTableSearch();
        setTimeout(initDataTableSearch, 50);
        setTimeout(initDataTableSearch, 200);
        setTimeout(initDataTableSearch, 500);

        <?php if ($openUpload): ?>
            $('#modalUploadFile').modal('show');
        <?php endif; ?>

        var st = "<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>";
        if (st === 'newsuccess') {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Başarılı!",
                    text: "Dosya başarıyla yüklendi ve sisteme kaydedildi.",
                    type: "success",
                    timer: 2500,
                    showConfirmButton: false
                });
            }
            window.history.pushState({}, '', 'index.php?p=all-files');
        } else if (st === 'deleted') {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Silindi!",
                    text: "Dosya sistemden başarıyla silindi.",
                    type: "success",
                    timer: 2500,
                    showConfirmButton: false
                });
            }
            window.history.pushState({}, '', 'index.php?p=all-files');
        } else if (st === 'uploaderror' || st === 'invalidfile') {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Hata!",
                    text: "Dosya yüklenirken bir sorun oluştu. Lütfen dosyanızı kontrol ediniz.",
                    type: "error",
                    confirmButtonClass: "btn btn-danger"
                });
            }
            window.history.pushState({}, '', 'index.php?p=all-files');
        } else if (st === 'noperm') {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Yetkisiz İşlem!",
                    text: "Bu işlem için yetkiniz bulunmamaktadır.",
                    type: "warning",
                    confirmButtonClass: "btn btn-warning"
                });
            }
            window.history.pushState({}, '', 'index.php?p=all-files');
        }
    });
</script>