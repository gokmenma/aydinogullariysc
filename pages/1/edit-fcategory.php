<?php

$userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
$userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? 0));

if (!permtrue("fileadd") && !permtrue("fileview") && $userPerm !== 1 && $userId !== 1 && $userId !== 12) {
    header("Location: index.php?error=nopermission");
    exit;
}

$id = (int)(@$_GET["id"] ?? 0);
if (!$id) {
    header("Location: index.php?p=file-categories");
    exit;
}

$bral = $ac->prepare("SELECT * FROM upfile_categories WHERE id = ?");
$bral->execute([$id]);
$data = $bral->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    header("Location: index.php?p=file-categories");
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $title = trim((string)($_POST["title"] ?? ''));
    if ($title !== '') {
        $up = $ac->prepare("UPDATE upfile_categories SET title = ? WHERE id = ?");
        $up->execute([$title, $id]);

        if (function_exists('audit_log')) {
            audit_log("update", "file-categories", "Dosya kategorisi güncellendi: " . $data['title'] . " -> " . $title, "upfile_categories", $id, $data);
        }
        header("Location: index.php?p=file-categories&st=updated");
        exit;
    }
}
?>

<div class="pd-20 bg-white border-radius-16 box-shadow mb-30">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <h4 class="text-dark font-18 font-weight-bold mb-1">
                <i class="fa fa-pencil text-primary mr-2"></i>Kategori Bilgisini Düzenle
            </h4>
            <p class="text-muted font-13 mb-0">Dosya kategorisi adını güncelleyin</p>
        </div>
        <a href="index.php?p=file-categories" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left mr-1"></i> Listeye Dön
        </a>
    </div>

    <form method="POST" action="index.php?p=edit-fcategory&id=<?php echo $id; ?>">
        <div class="row">
            <div class="col-md-8 col-lg-6">
                <div class="form-group mb-4">
                    <label class="font-weight-600 text-dark font-14">
                        <span class="text-danger font-weight-bold mr-1">*</span>Kategori Adı
                    </label>
                    <input required name="title" value="<?php echo htmlspecialchars($data["title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-lg font-14" type="text">
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa fa-save mr-1"></i> Değişiklikleri Kaydet
                    </button>
                    <a href="index.php?p=file-categories" class="btn btn-light px-3">İptal</a>
                </div>
            </div>
        </div>
    </form>
</div>