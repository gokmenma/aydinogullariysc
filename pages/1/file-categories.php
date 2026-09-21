<?php

$userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
$userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? 0));

// Dosya yetkileri kontrolü
if (!permtrue("fileview") && !permtrue("fileadd") && !permtrue("filedelete") && $userPerm !== 1 && $userId !== 1 && $userId !== 12) {
    header("Location: index.php?error=nopermission");
    exit;
}

$canAdd = permtrue("fileadd") || $userPerm === 1 || $userId === 1 || $userId === 12;
$canDelete = permtrue("filedelete") || $userPerm === 1 || $userId === 1 || $userId === 12;

// Kategori Ekleme / Güncelleme İşlemleri
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add' && $canAdd) {
        $title = trim((string)($_POST['title'] ?? ''));
        if ($title !== '') {
            $checkQ = $ac->prepare("SELECT id FROM upfile_categories WHERE LOWER(title) = LOWER(?)");
            $checkQ->execute([$title]);
            if ($checkQ->fetch()) {
                header("Location: index.php?p=file-categories&st=exists");
                exit;
            }

            $ins = $ac->prepare("INSERT INTO upfile_categories (title) VALUES (?)");
            $ins->execute([$title]);
            $newId = $ac->lastInsertId();

            if (function_exists('audit_log')) {
                audit_log("insert", "file-categories", "Yeni dosya kategorisi eklendi: " . $title, "upfile_categories", $newId);
            }
            header("Location: index.php?p=file-categories&st=created");
            exit;
        }
    } elseif ($action === 'edit' && $canAdd) {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        if ($id > 0 && $title !== '') {
            $oldQ = $ac->prepare("SELECT * FROM upfile_categories WHERE id = ?");
            $oldQ->execute([$id]);
            $oldData = $oldQ->fetch(PDO::FETCH_ASSOC);

            if ($oldData) {
                $up = $ac->prepare("UPDATE upfile_categories SET title = ? WHERE id = ?");
                $up->execute([$title, $id]);

                if (function_exists('audit_log')) {
                    audit_log("update", "file-categories", "Dosya kategorisi güncellendi: " . $oldData['title'] . " -> " . $title, "upfile_categories", $id, $oldData);
                }
                header("Location: index.php?p=file-categories&st=updated");
                exit;
            }
        }
    }
}

// İstatistikler (KPI)
$totalCatsStmt = $ac->query("SELECT COUNT(*) FROM upfile_categories");
$totalCats = (int)$totalCatsStmt->fetchColumn();

$usedCatsStmt = $ac->query("SELECT COUNT(DISTINCT cid) FROM upfiles WHERE cid > 0");
$usedCats = (int)$usedCatsStmt->fetchColumn();

$totalFilesStmt = $ac->query("SELECT COUNT(*) FROM upfiles");
$totalFiles = (int)$totalFilesStmt->fetchColumn();

// Kategorileri ve dosya sayılarını listele
$catsStmt = $ac->prepare("
    SELECT c.*, COUNT(f.id) AS file_count 
    FROM upfile_categories c
    LEFT JOIN upfiles f ON f.cid = c.id
    GROUP BY c.id
    ORDER BY c.id DESC
");
$catsStmt->execute();
$categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);

$st = $_GET['st'] ?? '';
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_filecats_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-filecats-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    /* Teklifler listesi ile birebir uyumlu stiller */
    .file-cats-wrapper {
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
    .icon-indigo  { background: #e0e7ff; color: #4f46e5; }

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
    .soft-indigo  { background: #e0e7ff; color: #3730a3; }

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

    /* Page Header */
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

    /* Action Buttons */
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

    /* Table Styles */
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

    .badge-count {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
    }
    .badge-count.has-files {
        background: #e0f2fe;
        color: #0369a1;
    }

    .category-title-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: #1e293b;
    }
    .category-title-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background: #f0f9ff;
        color: #0284c7;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
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
    }
    .btn-table-action:hover {
        transform: translateY(-1px);
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
    .dark-mode .category-title-cell { color: #f1f5f9 !important; }
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
    <div class="file-cats-wrapper">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap px-1" style="gap: 12px;">
            <div class="page-title-box">
                <div class="page-title-icon">
                    <i class="fa fa-folder-open"></i>
                </div>
                <div class="page-title-text">
                    <h4>Dosya Kategorileri</h4>
                    <p>Yüklenen dökümanların kategorilendirilmesi ve yönetimi</p>
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <a href="index.php?p=all-files" class="btn btn-outline-primary btn-action-outline">
                    <i class="fa fa-file-text-o"></i> <span class="d-none d-sm-inline">Tüm Dosyalar</span>
                </a>

                <?php if ($canAdd): ?>
                <button type="button" class="btn btn-action-primary" data-toggle="modal" data-target="#modalNewCategory">
                    <i class="fa fa-plus-circle"></i> <span>Yeni Kategori</span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- KPI Summary Section -->
        <div class="row mx-0 mb-3" id="kpiSummarySection">
            <div class="col-12 col-sm-6 col-lg-4 mb-3 mb-lg-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Kategori</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalCats); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-folder"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Tanımlı Gruplar</span>
                        <span class="crm-badge-soft soft-primary">Aktif</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4 mb-3 mb-lg-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Dosyalı Kategoriler</span>
                            <div class="crm-kpi-value"><?php echo number_format($usedCats); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-indigo">
                            <i class="fa fa-folder-open"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">İçerikli Gruplar</span>
                        <span class="crm-badge-soft soft-indigo">Kullanımda</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Dosya</span>
                            <div class="crm-kpi-value"><?php echo number_format($totalFiles); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-files-o"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11">Bağlı Dökümanlar</span>
                        <span class="crm-badge-soft soft-emerald">Kayıtlı</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories List Table Card -->
        <div class="form-card animate-fade-in mx-1">
            <div class="form-card-header d-flex justify-content-between align-items-center">
                <div class="header-left-inner">
                    <div class="card-icon">
                        <i class="fa fa-list"></i>
                    </div>
                    <div>
                        <h5>Kategori Listesi</h5>
                        <p>Anlık arama ve kategori düzenleme</p>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <!-- Global DataTable Arama Kutusu Taşıma Alanı -->
                    <div id="categoriesSearchContainer" class="dt-header-filter-box"></div>

                    <button type="button" id="toggleKpiSummary" class="btn btn-toggle-kpi-custom" title="Özet Kartlarını Gizle / Göster">
                        <i class="fa fa-chevron-up" id="kpiChevronIcon"></i>
                    </button>
                </div>
            </div>

            <div class="responsive">
                <table class="data-table table-hover table-bordered" id="categoriesTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">Sıra No</th>
                            <th>Kategori Adı</th>
                            <th style="width: 160px;" class="text-center">Dosya Sayısı</th>
                            <th style="width: 180px;">Oluşturulma Tarihi</th>
                            <th style="width: 110px;" class="text-center datatable-nosort no-export">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                        <tr class="odd data-row text-center">
                            <td colspan="5" class="py-4 text-muted">
                                <i class="fa fa-info-circle mr-1"></i> Henüz tanımlanmış bir dosya kategorisi bulunmuyor.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $rowNum = 1; foreach ($categories as $cat): ?>
                            <tr>
                                <td class="text-center text-muted font-weight-bold"><?php echo $rowNum++; ?></td>
                                <td>
                                    <div class="category-title-cell">
                                        <div class="category-title-icon">
                                            <i class="fa fa-folder"></i>
                                        </div>
                                        <div>
                                            <span class="font-weight-600"><?php echo htmlspecialchars($cat['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge-count <?php echo ($cat['file_count'] > 0) ? 'has-files' : ''; ?>">
                                        <i class="fa fa-file-text-o font-10"></i>
                                        <?php echo (int)$cat['file_count']; ?> Dosya
                                    </span>
                                </td>
                                <td class="text-muted font-12">
                                    <i class="fa fa-calendar-o mr-1 text-secondary"></i>
                                    <?php echo !empty($cat['regdate']) ? date('d.m.Y H:i', strtotime($cat['regdate'])) : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex" style="gap: 4px;">
                                        <?php if ($canAdd): ?>
                                        <button type="button" class="btn btn-table-action btn-outline-secondary" 
                                                data-toggle="modal" data-target="#modalEditCategory"
                                                data-id="<?php echo (int)$cat['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($cat['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Kategoriyi Düzenle">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if ($canDelete): ?>
                                        <button type="button" class="btn btn-table-action btn-outline-danger" 
                                                onclick="deleteRecord('Bu kategoriyi silmek istediğinize emin misiniz?','<?php echo (int)$cat['id']; ?>','file-categories')"
                                                title="Kategoriyi Sil">
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

<!-- Modal: Yeni Kategori Ekle -->
<div class="modal fade" id="modalNewCategory" tabindex="-1" role="dialog" aria-labelledby="modalNewCategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-light border-0 py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title font-16 font-weight-bold text-dark" id="modalNewCategoryLabel">
                    <i class="fa fa-folder-plus text-primary mr-2"></i>Yeni Dosya Kategorisi Oluştur
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="index.php?p=file-categories">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="form-group mb-0">
                        <label class="font-weight-600 text-dark font-13">
                            <span class="text-danger font-weight-bold mr-1">*</span>Kategori Adı
                        </label>
                        <input type="text" name="title" required class="form-control form-control-lg font-14" 
                               placeholder="Örn: Sözleşmeler, Fatura Ekleri, Personel Evrakları" autofocus>
                        <small class="form-text text-muted mt-2">
                            Dosyaları gruplandırmak ve filtrelemek için anlaşılır bir isim belirleyin.
                        </small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-secondary font-13 px-3" data-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-action-primary font-13 px-4">
                        <i class="fa fa-check mr-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Kategori Düzenle -->
<div class="modal fade" id="modalEditCategory" tabindex="-1" role="dialog" aria-labelledby="modalEditCategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-light border-0 py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title font-16 font-weight-bold text-dark" id="modalEditCategoryLabel">
                    <i class="fa fa-edit text-primary mr-2"></i>Kategori Bilgisini Düzenle
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="index.php?p=file-categories">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_cat_id" value="">
                <div class="modal-body p-4">
                    <div class="form-group mb-0">
                        <label class="font-weight-600 text-dark font-13">
                            <span class="text-danger font-weight-bold mr-1">*</span>Kategori Adı
                        </label>
                        <input type="text" name="title" id="edit_cat_title" required class="form-control form-control-lg font-14" 
                               placeholder="Kategori adı">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-secondary font-13 px-3" data-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-action-primary font-13 px-4">
                        <i class="fa fa-save mr-1"></i> Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Edit Modal Doldurma
    $('#modalEditCategory').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var title = button.data('title');
        var modal = $(this);
        modal.find('#edit_cat_id').val(id);
        modal.find('#edit_cat_title').val(title);
    });

    // KPI Summary Toggle (Teklifler Sayfasıyla Birebir)
    $('#toggleKpiSummary').on('click', function() {
        var section = document.getElementById('kpiSummarySection');
        var icon = document.getElementById('kpiChevronIcon');
        var isHidden = $(section).is(':hidden') || document.documentElement.classList.contains('kpi-filecats-collapsed-early');

        if (isHidden) {
            $(section).slideDown(200);
            document.documentElement.classList.remove('kpi-filecats-collapsed-early');
            icon.className = 'fa fa-chevron-up';
            localStorage.setItem('aydinogullari_kpi_filecats_collapsed', 'false');
        } else {
            $(section).slideUp(200);
            icon.className = 'fa fa-chevron-down';
            localStorage.setItem('aydinogullari_kpi_filecats_collapsed', 'true');
        }
    });

    // Global DataTable Arama Kutusunu Header'a Yerleştirme
    function initDataTableSearch() {
        var filterEl = $("#categoriesTable_wrapper .dataTables_filter");
        if (filterEl.length && $("#categoriesSearchContainer").length) {
            if (!$("#categoriesSearchContainer").find(".dataTables_filter").length) {
                filterEl.appendTo("#categoriesSearchContainer");
                filterEl.find("input").attr("placeholder", "Kategorilerde ara...").attr("autocomplete", "off");
            }
        }
    }

    // Durum Bildirimleri (SweetAlert)
    $(document).ready(function() {
        if (localStorage.getItem('aydinogullari_kpi_filecats_collapsed') === 'true') {
            var icon = document.getElementById('kpiChevronIcon');
            if (icon) icon.className = 'fa fa-chevron-down';
        }

        // Global Arama Kutusunu Taşı
        initDataTableSearch();
        setTimeout(initDataTableSearch, 50);
        setTimeout(initDataTableSearch, 200);
        setTimeout(initDataTableSearch, 500);

        var st = "<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>";
        if (st === 'created' || st === 'newsuccess') {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Başarılı!",
                    text: "Yeni dosya kategorisi başarıyla eklendi.",
                    type: "success",
                    timer: 2500,
                    showConfirmButton: false
                });
            }
            window.history.pushState({}, '', 'index.php?p=file-categories');
        } else if (st === 'updated') {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Güncellendi!",
                    text: "Dosya kategorisi başarıyla güncellendi.",
                    type: "success",
                    timer: 2500,
                    showConfirmButton: false
                });
            }
            window.history.pushState({}, '', 'index.php?p=file-categories');
        } else if (st === 'exists') {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Uyarı!",
                    text: "Bu isimde bir dosya kategorisi zaten mevcut.",
                    type: "warning",
                    confirmButtonClass: "btn btn-warning"
                });
            }
            window.history.pushState({}, '', 'index.php?p=file-categories');
        }
    });
</script>