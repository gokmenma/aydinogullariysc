<?php
/**
 * Birim Tanımlama
 * Premium Tema Entegrasyonu
 */

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$statuCode = 1; // Birim Tanımları
$pageSlug = "define-units";
$pageTitle = "Birim Tanımlama";

$userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
$userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? 0));
$isAdmin = in_array($userId, [1, 12]) || in_array($userPerm, [1, 13]);

// POST İşlemleri (Ekleme / Güncelleme)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
    $title = trim($_POST["title"] ?? '');
    $type = $_GET["type"] ?? ($id > 0 ? 'update' : 'new');
    $regdate = TODAY . " - " . date("H:i:s");
    $creator = function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? null);

    if (empty($title)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['status' => 'error', 'message' => 'Lütfen birim adını giriniz.']);
            exit;
        }
    } else {
        if ($type === "new" || $id === 0) {
            try {
                $ekle = $ac->prepare("INSERT INTO units SET title = ?, regdate = ?, statu = ?, creator = ?, note = ?");
                $ekle->execute([$title, $regdate, $statuCode, $creator, "Birim Tanımı"]);
                $lastId = $ac->lastInsertId();

                if (function_exists('audit_log')) {
                    audit_log("create", $pageSlug, "Yeni birim eklendi: " . $title, "units", (string)$lastId);
                }

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => 'Birim başarıyla eklendi.', 'id' => $lastId]);
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Unit insert error: " . $e->getMessage());
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json', true, 500);
                    echo json_encode(['status' => 'error', 'message' => 'Kayıt sırasında veritabanı hatası oluştu.']);
                    exit;
                }
            }
        } elseif ($type === "update" && $id > 0) {
            try {
                $up = $ac->prepare("UPDATE units SET title = ? WHERE id = ? AND statu = ?");
                $up->execute([$title, $id, $statuCode]);

                if (function_exists('audit_log')) {
                    audit_log("update", $pageSlug, "Birim güncellendi: " . $title, "units", (string)$id);
                }

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => 'Birim başarıyla güncellendi.', 'id' => $id]);
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Unit update error: " . $e->getMessage());
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json', true, 500);
                    echo json_encode(['status' => 'error', 'message' => 'Güncelleme sırasında veritabanı hatası oluştu.']);
                    exit;
                }
            }
        }
    }
}

// GET ile Silme Desteği
if (!empty($_GET["id"]) && @$_GET["mode"] === "delete" && @$_GET["code"] === "04md177") {
    $delId = (int)$_GET["id"];
    $delStmt = $ac->prepare("DELETE FROM units WHERE id = ? AND statu = ?");
    $delStmt->execute([$delId, $statuCode]);
    if (function_exists('audit_log')) {
        audit_log("delete", $pageSlug, "Birim silindi. ID: " . $delId, "units", (string)$delId);
    }
}

// İstatistikler
$totalCount = 0;
$lastAddedTitle = "-";
$lastAddedDate = "-";

try {
    $statQuery = $ac->prepare("SELECT COUNT(*) as total FROM units WHERE statu = ?");
    $statQuery->execute([$statuCode]);
    $totalCount = (int)($statQuery->fetchColumn() ?? 0);

    $lastQuery = $ac->prepare("SELECT title, regdate FROM units WHERE statu = ? ORDER BY id DESC LIMIT 1");
    $lastQuery->execute([$statuCode]);
    if ($lastRow = $lastQuery->fetch(PDO::FETCH_ASSOC)) {
        $lastAddedTitle = $lastRow['title'];
        $lastAddedDate = $lastRow['regdate'];
    }
} catch (Exception $e) {
    error_log("Units stats error: " . $e->getMessage());
}
?>

<div class="definition-page-container animate-fade-in">
    <!-- Premium Header Card -->
    <div class="premium-header-card mb-4">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon" style="background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%); color: #ffffff; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);">
                    <i class="fa fa-balance-scale"></i>
                </div>
                <div class="header-title">
                    <h4><?php echo htmlspecialchars($pdat["p_title"] ?? $pageTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                    <div class="header-stat-pills d-flex align-items-center flex-wrap" style="gap: 8px; margin-top: 6px;">
                        <span class="header-pill header-pill-total" style="background: #e0f2fe; border-color: #bae6fd; color: #0369a1;" title="Toplam Kayıt">
                            <i class="fa fa-list-ul mr-1"></i> Toplam: <strong style="color: #075985;"><?php echo $totalCount; ?></strong>
                        </span>
                        <?php if ($lastAddedTitle !== "-"): ?>
                        <span class="header-pill header-pill-recent" title="Son Eklenen Birim">
                            <i class="fa fa-clock-o mr-1"></i> Son: <strong><?php echo htmlspecialchars($lastAddedTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <a href="index.php?p=products" class="btn-header btn-header-secondary" title="Ürünler Listesine Git">
                    <i class="fa fa-cubes mr-1 text-primary"></i> Ürün Listesi
                </a>
                <button type="button" class="btn-header btn-header-save" style="background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%); box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);" id="btnOpenNewModal">
                    <i class="fa fa-plus-circle mr-1"></i> Yeni Birim Tanımla
                </button>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="form-card mb-4 animate-fade-in" style="padding: 0; overflow: hidden; border-radius: 14px;">
        <div class="form-card-header d-flex justify-content-between align-items-center flex-wrap" style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; gap: 15px;">
            <div class="d-flex align-items-center header-left-inner">
                <div class="card-icon mr-3" style="width: 40px; height: 40px; border-radius: 10px; font-size: 16px; display: flex; align-items: center; justify-content: center; background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                    <i class="fa fa-tags"></i>
                </div>
                <div>
                    <h5 class="mb-0" style="font-size: 16px; font-weight: 700;">Tanımlı Ürün ve Hizmet Birimleri</h5>
                    <p class="mb-0 text-muted" style="font-size: 12.5px;">Ürün, stok, malzeme ve teklif formlarında geçerli ölçü birimleri (Sağ tık menüsü desteklenir)</p>
                </div>
            </div>
        </div>

        <div class="table-responsive" style="padding: 0; margin: 0;">
            <table id="unitsTable" class="data-table select-row table-bordered table-hover" style="width: 100%; margin: 0 !important;">
                <thead>
                    <tr>
                        <th class="text-center no-filter" style="width: 60px; max-width: 60px;">#Sıra</th>
                        <th style="min-width: 240px;">Birim Adı</th>
                        <th style="min-width: 160px;">Ekleyen Kullanıcı</th>
                        <th class="text-center" style="width: 180px;">Eklenme Tarihi</th>
                        <th class="text-center" style="width: 110px;">Durum</th>
                        <th class="datatable-nosort no-filter text-center" style="width: 120px; min-width: 110px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $cq = $ac->prepare("
                            SELECT 
                                u.*, 
                                usr.username, 
                                usr.Unvan
                            FROM units u
                            LEFT JOIN users usr ON u.creator = usr.id
                            WHERE u.statu = ?
                            ORDER BY u.id DESC
                        ");
                        $cq->execute([$statuCode]);
                        $kx = 1;
                        while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {
                            $creatorName = !empty($as["username"]) ? $as["username"] : (!empty($as["Unvan"]) ? $as["Unvan"] : 'Sistem');
                            $userInitial = mb_strtoupper(mb_substr($creatorName, 0, 1, 'UTF-8'), 'UTF-8');
                            ?>
                            <tr id="row-unit-<?php echo $as["id"]; ?>" data-id="<?php echo $as["id"]; ?>" data-title="<?php echo htmlspecialchars($as["title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="text-center font-weight-bold text-muted" style="vertical-align: middle;">
                                    <?php echo $kx; ?>
                                </td>
                                <td style="vertical-align: middle;">
                                    <div class="d-flex align-items-center">
                                        <div class="definition-tag-dot mr-2" style="background: #0284c7;"></div>
                                        <span class="font-weight-600 text-dark unit-title-text" style="font-size: 14px;">
                                            <?php echo htmlspecialchars($as["title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="vertical-align: middle;">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-mini mr-2" style="width: 28px; height: 28px; border-radius: 50%; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                                            <?php echo htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <span class="text-dark font-weight-500" style="font-size: 13px;">
                                            <?php echo htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center text-muted" style="vertical-align: middle; font-size: 12.5px;">
                                    <i class="fa fa-calendar-o mr-1 text-primary"></i> <?php echo htmlspecialchars($as["regdate"] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="text-center" style="vertical-align: middle;">
                                    <span class="badge-status-pill badge-status-active">
                                        <i class="fa fa-check-circle mr-1"></i> Aktif
                                    </span>
                                </td>
                                <td class="text-center" style="vertical-align: middle; white-space: nowrap;">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if ($isAdmin || permtrue("customeredit") || $userPerm == 1) { ?>
                                            <button type="button" class="btn btn-outline-primary btn-edit-unit" 
                                                data-id="<?php echo $as["id"]; ?>" 
                                                data-title="<?php echo htmlspecialchars($as["title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Düzenle">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                        <?php } ?>
                                        <button type="button" class="btn btn-outline-danger btn-delete-unit" 
                                            data-id="<?php echo $as["id"]; ?>" 
                                            data-title="<?php echo htmlspecialchars($as["title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            title="Sil" 
                                            onClick="deleteRecord('\'<?php echo htmlspecialchars($as["title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>\' birimini silmek istediğinize emin misiniz?','<?php echo $as["id"]; ?>','define-units','units')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                            $kx++;
                        }
                    } catch (Exception $e) {
                        echo '<tr><td colspan="6" class="text-center text-danger py-4"><i class="fa fa-exclamation-triangle mr-2"></i> Birimler yüklenirken bir hata oluştu: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Birim Ekleme / Düzenleme Modalı -->
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content premium-modal-content">
            <form method="POST" id="myForm" onsubmit="return false;">
                <input id="id" type="hidden" name="id" value="0">
                
                <div class="modal-header premium-modal-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="modal-header-icon mr-3" style="background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);">
                            <i class="fa fa-balance-scale" id="modalHeaderIcon"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-weight-bold mb-0" id="exampleModalLongTitle">Yeni Birim Tanımla</h5>
                            <small class="text-muted" id="modalSubtitle">Sistemde kullanılacak yeni bir ölçü birimi tanımlayın</small>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="font-size: 24px; padding: 10px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label for="title" class="font-weight-600 text-dark mb-1">
                            Birim Adı / Kısaltması <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-modern">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-tag text-muted"></i></span>
                            </div>
                            <input required type="text" class="form-control form-control-modern" name="title" id="title" placeholder="Örn: Adet, Kg, Metre" autocomplete="off">
                        </div>
                    </div>

                    <!-- Hızlı Öneri Etiketleri -->
                    <div class="suggestion-box p-3 rounded" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                        <div class="font-weight-600 text-dark mb-2 d-flex align-items-center" style="font-size: 13px;">
                            <i class="fa fa-lightbulb-o text-warning mr-1" style="font-size: 15px;"></i> Hızlı Örnekler:
                        </div>
                        <div class="suggestion-tags d-flex flex-wrap" style="gap: 8px;">
                            <span class="badge badge-pill quick-tag" role="button" data-val="Adet"><i class="fa fa-tag mr-1 text-muted"></i> Adet</span>
                            <span class="badge badge-pill quick-tag" role="button" data-val="Kg"><i class="fa fa-tag mr-1 text-muted"></i> Kg</span>
                            <span class="badge badge-pill quick-tag" role="button" data-val="Metre"><i class="fa fa-tag mr-1 text-muted"></i> Metre</span>
                            <span class="badge badge-pill quick-tag" role="button" data-val="Litre"><i class="fa fa-tag mr-1 text-muted"></i> Litre</span>
                            <span class="badge badge-pill quick-tag" role="button" data-val="Paket"><i class="fa fa-tag mr-1 text-muted"></i> Paket</span>
                            <span class="badge badge-pill quick-tag" role="button" data-val="Kutu"><i class="fa fa-tag mr-1 text-muted"></i> Kutu</span>
                            <span class="badge badge-pill quick-tag" role="button" data-val="Set"><i class="fa fa-tag mr-1 text-muted"></i> Set</span>
                            <span class="badge badge-pill quick-tag" role="button" data-val="Saat"><i class="fa fa-tag mr-1 text-muted"></i> Saat</span>
                            <span class="badge badge-pill quick-tag" role="button" data-val="Rulo"><i class="fa fa-tag mr-1 text-muted"></i> Rulo</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer premium-modal-footer d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-secondary btn-modern-cancel" data-dismiss="modal" data-bs-dismiss="modal">
                        <i class="fa fa-times mr-1"></i> İptal
                    </button>
                    <button type="button" id="submitButtonByAjax" class="btn btn-primary btn-modern-save" style="background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%);">
                        <i class="fa fa-save mr-1"></i> <span id="submitBtnText">Kaydet</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Premium Definition Theme Styles */
.premium-header-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    padding: 20px 24px;
    transition: all 0.3s ease;
}
.premium-header-card .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}
.premium-header-card .header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.premium-header-card .header-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.premium-header-card .header-title h4 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.02em;
}

/* Header Stat Pills */
.header-pill {
    font-size: 12px;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.header-pill strong {
    margin-left: 3px;
    font-weight: 700;
    color: #0f172a;
}
.header-pill-recent {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #15803d;
}
.header-pill-recent strong { color: #166534; }

/* Buttons */
.premium-header-card .btn-header {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 9px 18px !important;
    border-radius: 10px !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
    cursor: pointer !important;
    text-decoration: none !important;
    line-height: 1.4 !important;
}
.premium-header-card .btn-header-save {
    color: #ffffff !important;
    border: none !important;
}
.premium-header-card .btn-header-save:hover {
    transform: translateY(-1px);
    color: #ffffff !important;
    filter: brightness(0.95);
}
.premium-header-card .btn-header-secondary {
    background: #f1f5f9 !important;
    border: 1px solid #cbd5e1 !important;
    color: #334155 !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
}
.premium-header-card .btn-header-secondary:hover {
    background: #e2e8f0 !important;
    border-color: #94a3b8 !important;
    color: #0f172a !important;
    transform: translateY(-1px);
}

/* Table Card */
.form-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
}
.definition-tag-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.badge-status-pill {
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
}
.badge-status-active {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

/* Modal Styling */
.premium-modal-content {
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.premium-modal-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 18px 24px;
}
.modal-header-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 18px;
}
/* Modern Unified Input Group */
.input-group-modern {
    display: flex;
    align-items: stretch;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #ffffff;
    overflow: hidden;
    transition: all 0.2s ease;
}
.input-group-modern:focus-within {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}
.input-group-modern .input-group-prepend {
    margin-right: 0;
    display: flex;
}
.input-group-modern .input-group-text {
    background-color: #f8fafc;
    border: none;
    border-right: 1px solid #e2e8f0;
    border-radius: 0;
    padding: 10px 14px;
    color: #64748b;
    font-size: 15px;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
}
.input-group-modern:focus-within .input-group-text {
    background-color: #f0f9ff;
    color: #0284c7;
    border-right-color: #bae6fd;
}
.input-group-modern .form-control-modern {
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 10px 14px;
    font-size: 14.5px;
    color: #1e293b;
    background: transparent;
    height: auto;
}
.input-group-modern .form-control-modern:focus {
    box-shadow: none !important;
}

.premium-modal-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 14px 24px;
}
.btn-modern-save {
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-weight: 600;
    font-size: 13.5px;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
}
.btn-modern-save:hover {
    filter: brightness(0.92);
}
.btn-modern-cancel {
    background: #e2e8f0;
    border: none;
    color: #475569;
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 600;
    font-size: 13.5px;
}
.btn-modern-cancel:hover {
    background: #cbd5e1;
    color: #1e293b;
}

/* Quick Tags Badges */
.quick-tag {
    cursor: pointer;
    font-size: 13px !important;
    font-weight: 500 !important;
    padding: 6px 13px !important;
    border-radius: 20px !important;
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    color: #334155 !important;
    display: inline-flex !important;
    align-items: center !important;
    transition: all 0.2s ease !important;
    user-select: none;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
.quick-tag i {
    font-size: 11px;
    transition: color 0.2s ease;
}
.quick-tag:hover {
    background: #e0f2fe !important;
    color: #0369a1 !important;
    border-color: #bae6fd !important;
    transform: translateY(-1.5px);
    box-shadow: 0 3px 6px rgba(2, 132, 199, 0.15) !important;
}
.quick-tag:hover i {
    color: #0284c7 !important;
}
.quick-tag:active {
    transform: translateY(0);
}
</style>

<script src="include/js/data-table.js"></script>
<script>
$(document).ready(function() {
    var pageSlug = "define-units";
    var entityLabel = "Birim";

    // Yeni Modal Açıldığında
    function openNewModal() {
        $("#id").val("0");
        $("#title").val("");
        $("#exampleModalLongTitle").text("Yeni " + entityLabel + " Tanımla");
        $("#modalSubtitle").text("Sistemde kullanılacak yeni bir " + entityLabel.toLowerCase() + " tanımlayın");
        $("#submitBtnText").text("Kaydet");
        $("#exampleModalCenter").modal("show");
        setTimeout(function() { $("#title").focus(); }, 400);
    }

    // Düzenleme Modalını Aç
    function openEditModal(id, title) {
        $("#id").val(id);
        $("#title").val(title);
        $("#exampleModalLongTitle").text(entityLabel + " Düzenle");
        $("#modalSubtitle").text("Seçili kaydı güncelliyorsunuz");
        $("#submitBtnText").text("Güncelle");
        $("#exampleModalCenter").modal("show");
        setTimeout(function() { $("#title").focus(); }, 400);
    }

    $("#btnOpenNewModal").on("click", function() {
        openNewModal();
    });

    // Düzenle Butonuna Tıklandığında
    $(document).on("click", ".btn-edit-unit", function() {
        var id = $(this).attr("data-id");
        var title = $(this).attr("data-title");
        openEditModal(id, title);
    });

    // Modal Kapatıldığında Backdrop ve Body Scroll Temizliği
    $("#exampleModalCenter").on("hidden.bs.modal", function() {
        $(".modal-backdrop").remove();
        $("body").removeClass("modal-open").css("padding-right", "");
    });

    $(document).on("click", "#exampleModalCenter [data-dismiss='modal'], #exampleModalCenter [data-bs-dismiss='modal']", function() {
        $("#exampleModalCenter").modal("hide");
        setTimeout(function() {
            $(".modal-backdrop").remove();
            $("body").removeClass("modal-open").css("padding-right", "");
        }, 150);
    });

    // Hızlı Örnek Etiketine Tıklama
    $(document).on("click", ".quick-tag", function() {
        var val = $(this).attr("data-val");
        $("#title").val(val).focus();
    });

    // ==========================================
    // SAĞ TIK (CONTEXT MENU) ENTEGRASYONU
    // ==========================================
    $(document).on("contextmenu", "#unitsTable tbody tr", function(e) {
        if ($(this).find("td").length <= 1) return;

        e.preventDefault();

        var $tr = $(this);
        $("#unitsTable tbody tr").removeClass("context-menu-active");
        $tr.addClass("context-menu-active");

        var id = $tr.data("id") || $tr.find(".btn-edit-unit").data("id");
        var title = $tr.data("title") || $tr.find(".unit-title-text").text().trim();

        var menuHtml = '<div class="cm-header"><i class="fa fa-balance-scale mr-1 text-primary"></i> ' + $("<div>").text(title || entityLabel).html() + '</div>';
        menuHtml += '<button type="button" class="cm-action-edit" data-id="' + id + '" data-title="' + $("<div>").text(title).html() + '"><i class="fa fa-pencil text-primary mr-2"></i> Düzenle</button>';
        menuHtml += '<div class="cm-divider"></div>';
        menuHtml += '<button type="button" class="cm-action-delete cm-danger" data-id="' + id + '" data-title="' + $("<div>").text(title).html() + '"><i class="fa fa-trash text-danger mr-2"></i> Sil</button>';

        var $contextMenu = $("#customContextMenu");
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo("body");
        }

        $contextMenu.html(menuHtml);

        var mouseX = e.clientX;
        var mouseY = e.clientY;

        $contextMenu.css({ display: "block", visibility: "hidden" });
        var menuWidth = $contextMenu.outerWidth();
        var menuHeight = $contextMenu.outerHeight();
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();

        if (mouseX + menuWidth > windowWidth) mouseX = windowWidth - menuWidth - 10;
        if (mouseY + menuHeight > windowHeight) mouseY = windowHeight - menuHeight - 10;

        $contextMenu.css({
            top: mouseY + "px",
            left: mouseX + "px",
            visibility: "visible",
            opacity: "1"
        });
    });

    // Menü Dışına Tıklanınca Kapat
    $(document).on("click", function(e) {
        if (!$(e.target).closest("#customContextMenu").length) {
            $("#customContextMenu").hide();
            $("#unitsTable tbody tr").removeClass("context-menu-active");
        }
    });

    $(document).on("keydown", function(e) {
        if (e.key === "Escape") {
            $("#customContextMenu").hide();
            $("#unitsTable tbody tr").removeClass("context-menu-active");
        }
    });

    // Context Menu Aksiyonları
    $(document).on("click", ".cm-action-edit", function(e) {
        e.preventDefault();
        $("#customContextMenu").hide();
        var id = $(this).data("id");
        var title = $(this).data("title");
        openEditModal(id, title);
    });

    $(document).on("click", ".cm-action-new", function(e) {
        e.preventDefault();
        $("#customContextMenu").hide();
        openNewModal();
    });

    $(document).on("click", ".cm-action-delete", function(e) {
        e.preventDefault();
        $("#customContextMenu").hide();
        var id = $(this).data("id");
        var title = $(this).data("title");
        deleteRecord("'" + title + "' " + entityLabel.toLowerCase() + " kaydını silmek istediğinize emin misiniz?", id, pageSlug, 'units');
    });

    // AJAX ile Kaydetme / Güncelleme
    $("#submitButtonByAjax").on("click", function() {
        var id = $("#id").val();
        var title = $("#title").val().trim();
        var type = id > 0 ? "update" : "new";
        var actionLabel = id > 0 ? "güncellendi" : "eklendi";

        if (!title) {
            Swal.fire({
                title: "Uyarı!",
                text: entityLabel + " adı boş bırakılamaz!",
                icon: "warning"
            });
            return;
        }

        var btn = $(this);
        btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');

        $.ajax({
            url: "index.php?p=" + pageSlug + "&type=" + type,
            type: "POST",
            data: {
                id: id,
                title: title,
                type: type
            },
            dataType: "json",
            success: function(res) {
                btn.prop("disabled", false).html('<i class="fa fa-save mr-1"></i> ' + (id > 0 ? 'Güncelle' : 'Kaydet'));
                $("#exampleModalCenter").modal("hide");
                Swal.fire({
                    title: "Başarılı!",
                    text: entityLabel + " başarıyla " + actionLabel + ".",
                    icon: "success",
                    timer: 1500,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "index.php?p=" + pageSlug;
                });
            },
            error: function(xhr) {
                btn.prop("disabled", false).html('<i class="fa fa-save mr-1"></i> ' + (id > 0 ? 'Güncelle' : 'Kaydet'));
                var errMessage = "İşlem sırasında bir hata oluştu!";
                try {
                    var jsonRes = JSON.parse(xhr.responseText);
                    if (jsonRes && jsonRes.message) errMessage = jsonRes.message;
                } catch(e) {}
                Swal.fire({
                    title: "Hata!",
                    text: errMessage,
                    icon: "error"
                });
            }
        });
    });
});
</script>