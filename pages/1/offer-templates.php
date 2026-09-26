<?php
/**
 * Teklif Şablonları Yönetimi
 * Premium Tema Entegrasyonu
 */

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
$userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? 0));
$isAdmin = in_array($userId, [1, 12]) || in_array($userPerm, [1, 13]);

// POST İşlemleri (Ekleme / Güncelleme)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
    $title = trim($_POST["title"] ?? '');
    $state = trim($_POST["state"] ?? 'Header');
    $content = $_POST["editor"] ?? ($_POST["content"] ?? '');
    $regDate = date("Y-m-d");
    $creator = function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? 1);

    $type = $_GET["type"] ?? ($id > 0 ? 'update' : 'new');

    if ($type === "new") {
        try {
            $regxs = $ac->prepare("INSERT INTO offertemplate SET State = ?, Title = ?, Content = ?, regDate = ?, creator = ?");
            $regxs->execute([$state, $title, $content, $regDate, $creator]);
            $lastid = $ac->lastInsertId();

            if (function_exists('audit_log')) {
                audit_log("create", "offer_template", "Yeni teklif şablonu eklendi: " . $title, "offertemplate", (string)$lastid);
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Şablon başarıyla oluşturuldu.', 'id' => $lastid]);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Offer template insert error: " . $e->getMessage());
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json', true, 500);
                echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası oluştu.']);
                exit;
            }
        }
    } elseif ($type === "update" && $id > 0) {
        try {
            $regxs = $ac->prepare("UPDATE offertemplate SET State = ?, Title = ?, Content = ?, regDate = ?, creator = ? WHERE id = ?");
            $regxs->execute([$state, $title, $content, $regDate, $creator, $id]);

            if (function_exists('audit_log')) {
                audit_log("update", "offer_template", "Teklif şablonu güncellendi: " . $title, "offertemplate", (string)$id);
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Şablon başarıyla güncellendi.', 'id' => $id]);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Offer template update error: " . $e->getMessage());
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json', true, 500);
                echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası oluştu.']);
                exit;
            }
        }
    }
}

// GET ile Silme İsteği (Geriye Dönük Uyumluluk)
if (!empty($_GET["id"]) && @$_GET["mode"] === "delete" && @$_GET["code"] === "04md177") {
    $delId = (int)$_GET["id"];
    $delStmt = $ac->prepare("DELETE FROM offertemplate WHERE id = ?");
    $delStmt->execute([$delId]);
    if (function_exists('audit_log')) {
        audit_log("delete", "offer_template", "Teklif şablonu silindi. ID: " . $delId, "offertemplate", (string)$delId);
    }
}

// Şablon İstatistiklerini Çekme
$totalCount = 0;
$headerCount = 0;
$footerCount = 0;

try {
    $statQuery = $ac->query("SELECT State, COUNT(*) as count FROM offertemplate GROUP BY State");
    $stats = $statQuery->fetchAll(PDO::FETCH_ASSOC);
    foreach ($stats as $st) {
        $totalCount += (int)$st['count'];
        if (strtolower($st['State']) === 'header') {
            $headerCount = (int)$st['count'];
        } elseif (strtolower($st['State']) === 'footer') {
            $footerCount = (int)$st['count'];
        }
    }
} catch (Exception $e) {
    error_log("Stats count error: " . $e->getMessage());
}
?>

<div class="offer-templates-container animate-fade-in">
    <!-- Premium Header Card -->
    <div class="premium-header-card mb-4">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon" style="background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%); color: #ffffff;">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div class="header-title">
                    <h4><?php echo !empty($pdat["p_title"]) ? htmlspecialchars($pdat["p_title"], ENT_QUOTES, 'UTF-8') : 'Teklif Şablonları'; ?></h4>
                    <div class="header-stat-pills d-flex align-items-center flex-wrap" style="gap: 8px; margin-top: 6px;">
                        <span class="header-pill header-pill-total" data-filter="" role="button" title="Tümünü Göster">
                            <i class="fa fa-list-ul mr-1"></i> Toplam: <strong><?php echo $totalCount; ?></strong>
                        </span>
                        <span class="header-pill header-pill-header" data-filter="Üst Bilgi" role="button" title="Üst Bilgi Şablonları">
                            <i class="fa fa-arrow-circle-up mr-1"></i> Üst Bilgi: <strong><?php echo $headerCount; ?></strong>
                        </span>
                        <span class="header-pill header-pill-footer" data-filter="Alt Bilgi" role="button" title="Alt Bilgi Şablonları">
                            <i class="fa fa-arrow-circle-down mr-1"></i> Alt Bilgi: <strong><?php echo $footerCount; ?></strong>
                        </span>
                    </div>
                </div>
            </div>
            <div class="header-actions" style="display: flex;gap: 10px; align-items: center; flex-wrap: wrap;">
                <a href="index.php?p=offers/list" class="btn-header btn-header-offers" title="Teklifler Listesine Git">
                    <i class="fa fa-briefcase mr-1 text-primary"></i> Teklifler
                </a>
                <button type="button" class="btn-header btn-header-save" id="btnOpenNewModal" data-toggle="modal" data-target="#exampleModalCenter">
                    <i class="fa fa-plus-circle mr-1"></i> Yeni Şablon Tanımla
                </button>
            </div>
        </div>
    </div>

    <!-- Şablon Tablosu Kartı -->
    <div class="form-card mb-4 animate-fade-in" style="padding: 0; overflow: hidden; border-radius: 14px;">
        <div class="form-card-header d-flex justify-content-between align-items-center flex-wrap" style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; gap: 15px;">
            <div class="d-flex align-items-center header-left-inner">
                <div class="card-icon card-icon-blue mr-3" style="width: 40px; height: 40px; border-radius: 10px; font-size: 16px; display: flex; align-items: center; justify-content: center; background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                    <i class="fa fa-th-list"></i>
                </div>
                <div>
                    <h5 class="mb-0" style="font-size: 16px; font-weight: 700;">Tanımlı Şablon Listesi</h5>
                    <p class="mb-0 text-muted" style="font-size: 12.5px;">Teklif formlarında kullanılabilecek üst ve alt metin bloklarını yönetin</p>
                </div>
            </div>

            <!-- Hızlı Filtre Tab Butonları -->
            <div class="filter-tabs-wrapper d-flex align-items-center" style="gap: 6px;">
                <button type="button" class="btn btn-sm btn-filter-tab active" data-filter="">
                    <i class="fa fa-list mr-1"></i> Tümü (<?php echo $totalCount; ?>)
                </button>
                <button type="button" class="btn btn-sm btn-filter-tab" data-filter="Üst Bilgi">
                    <i class="fa fa-arrow-up mr-1"></i> Üst Bilgi (<?php echo $headerCount; ?>)
                </button>
                <button type="button" class="btn btn-sm btn-filter-tab" data-filter="Alt Bilgi">
                    <i class="fa fa-arrow-down mr-1"></i> Alt Bilgi (<?php echo $footerCount; ?>)
                </button>
            </div>
        </div>

        <div class="table-responsive" style="padding: 0; margin: 0;">
            <table id="offerTemplatesTable" class="data-table select-row table-bordered table-hover" style="width: 100%; margin: 0 !important;">
                <thead>
                    <tr>
                        <th class="text-center no-filter" style="width: 60px; max-width: 60px;">#Sıra</th>
                        <th style="min-width: 200px;">Şablon Başlığı</th>
                        <th class="text-center" style="width: 140px; min-width: 130px;">Şablon Türü</th>
                        <th>İçerik Önizleme</th>
                        <th class="datatable-nosort no-filter text-center" style="width: 130px; min-width: 120px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $templates = $ac->prepare("
                            SELECT 
                                ot.*, 
                                u.username, 
                                u.Unvan
                            FROM offertemplate ot
                            LEFT JOIN users u ON ot.creator = u.id
                            ORDER BY ot.id DESC
                        ");
                        $templates->execute();
                        $satir = 1;

                        while ($row = $templates->fetch(PDO::FETCH_ASSOC)) {
                            $isHeader = ($row["State"] === "Header");
                            $stateBadgeClass = $isHeader ? "badge-template-header" : "badge-template-footer";
                            $stateLabel = $isHeader ? "Üst Bilgi" : "Alt Bilgi";
                            $stateIcon = $isHeader ? "fa-arrow-circle-up" : "fa-arrow-circle-down";

                            $creatorName = !empty($row["Unvan"]) ? $row["Unvan"] : (!empty($row["username"]) ? $row["username"] : 'Sistem');
                            
                            $rawContent = $row["Content"] ?? '';
                            $cleanContent = trim(preg_replace('/\s+/', ' ', strip_tags($rawContent)));
                            $shortContent = mb_substr($cleanContent, 0, 140) . (mb_strlen($cleanContent) > 140 ? '...' : '');

                            $formattedDate = !empty($row["regDate"]) ? date("d.m.Y", strtotime($row["regDate"])) : '-';
                            ?>
                            <tr id="row-template-<?php echo $row["id"]; ?>">
                                <td class="text-center font-weight-bold text-muted" style="vertical-align: middle;">
                                    <?php echo $satir; ?>
                                </td>
                                <td style="vertical-align: middle;">
                                    <div class="template-title-box">
                                        <a href="javascript:void(0);" class="template-title-link font-weight-bold text-dark btn-preview-template" data-id="<?php echo $row["id"]; ?>">
                                            <i class="fa fa-file-text-o text-primary mr-1"></i> <?php echo htmlspecialchars($row["Title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <div class="template-meta-sub text-muted" style="font-size: 11.5px; margin-top: 4px;">
                                            <span><i class="fa fa-user-circle-o mr-1"></i> <?php echo htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="mx-1">•</span>
                                            <span><i class="fa fa-calendar-o mr-1"></i> <?php echo htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center" data-id="<?php echo htmlspecialchars($row["State"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="vertical-align: middle;">
                                    <span class="badge-template-pill <?php echo $stateBadgeClass; ?>">
                                        <i class="fa <?php echo $stateIcon; ?> mr-1"></i> <?php echo $stateLabel; ?>
                                    </span>
                                </td>
                                <td style="vertical-align: middle;">
                                    <div class="template-content-preview" title="Detayı görüntülemek için tıklayın" role="button" class="btn-preview-template" data-id="<?php echo $row["id"]; ?>">
                                        <?php if (!empty($shortContent)): ?>
                                            <span><?php echo htmlspecialchars($shortContent, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted font-italic">(İçerik belirtilmemiş)</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Ham İçerik Saklama Alanı (Önizleme ve Edit için) -->
                                    <div class="raw-content-store d-none" id="raw-content-<?php echo $row["id"]; ?>">
                                        <?php echo $rawContent; ?>
                                    </div>
                                    <textarea class="d-none raw-template-data" id="raw-template-<?php echo $row["id"]; ?>"><?php echo htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </td>
                                <td class="text-center" style="vertical-align: middle; white-space: nowrap;">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-info btn-preview-template" data-id="<?php echo $row["id"]; ?>" data-title="<?php echo htmlspecialchars($row["Title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-state="<?php echo $stateLabel; ?>" title="Şablonu Önizle">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary edit btn-edit-template" data-id="<?php echo $row["id"]; ?>" data-title="<?php echo htmlspecialchars($row["Title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-state="<?php echo $row["State"]; ?>" data-toggle="modal" data-target="#exampleModalCenter" title="Düzenle">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-delete-template" data-id="<?php echo $row["id"]; ?>" data-title="<?php echo htmlspecialchars($row["Title"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" title="Sil" onClick="deleteRecord('<?php echo htmlspecialchars($row["Title"] ?? '', ENT_QUOTES, 'UTF-8'); ?> başlıklı şablonu silmek istediğinize emin misiniz?','<?php echo $row["id"]; ?>','offer-templates','offertemplate')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                            $satir++;
                        }
                    } catch (Exception $e) {
                        echo '<tr><td colspan="5" class="text-center text-danger py-4"><i class="fa fa-exclamation-triangle mr-2"></i> Şablonlar yüklenirken bir hata oluştu: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Şablon Ekleme / Düzenleme Modalı -->
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content premium-modal-content">
            <form method="POST" id="myForm" onsubmit="return false;">
                <input id="id" type="hidden" name="id" value="0">
                
                <div class="modal-header premium-modal-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="modal-header-icon mr-3" style="background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);">
                            <i class="fa fa-file-text-o" id="modalHeaderIcon"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-weight-bold mb-0" id="exampleModalLongTitle">Teklif Şablonu Tanımla</h5>
                            <small class="text-muted" id="modalSubtitle">Tekliflerde kullanılacak hazır şablon başlığı ve içeriği belirleyin</small>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; padding: 10px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4">
                    <div class="row">
                        <!-- Şablon Başlığı -->
                        <div class="col-md-7 mb-3">
                            <label for="title" class="font-weight-600 text-dark mb-1">
                                Şablon Başlığı <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light border-right-0"><i class="fa fa-tag text-muted"></i></span>
                                </div>
                                <input required type="text" class="form-control form-control-modern border-left-0" name="title" id="title" placeholder="Örn: GENEL TEKLİF ÜST METNİ">
                            </div>
                        </div>

                        <!-- Şablon Türü (Kategori) -->
                        <div class="col-md-5 mb-3">
                            <label for="state" class="font-weight-600 text-dark mb-1">
                                Şablon Türü / Konumu <span class="text-danger">*</span>
                            </label>
                            <select required name="state" id="state" class="form-control form-control-modern">
                                <option value="Header">Üst Bilgi (Header)</option>
                                <option value="Footer">Alt Bilgi (Footer)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dinamik Değişken İpucu Kutusu -->
                    <div class="template-variables-box p-3 mb-3 rounded" style="background: #f8fafc; border: 1px dashed #cbd5e1; font-size: 13px;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 8px;">
                            <div>
                                <span class="font-weight-bold text-dark"><i class="fa fa-info-circle text-primary mr-1"></i> Dinamik Değişken Etiketleri:</span>
                                <span class="text-muted ml-1">Teklif formunda otomatik çekilecek değerleri eklemek için tıklayıp kopyalayabilirsiniz:</span>
                            </div>
                        </div>
                        <div class="tags-row mt-2 d-flex flex-wrap" style="gap: 8px;">
                            <span class="badge badge-light border template-tag py-1 px-2" role="button" title="Kopyalamak için tıklayın" data-tag="{{OdemeTuru}}"><code>{{OdemeTuru}}</code> (Ödeme Türü)</span>
                            <span class="badge badge-light border template-tag py-1 px-2" role="button" title="Kopyalamak için tıklayın" data-tag="{{OdemeVadesi}}"><code>{{OdemeVadesi}}</code> (Ödeme Vadesi)</span>
                            <span class="badge badge-light border template-tag py-1 px-2" role="button" title="Kopyalamak için tıklayın" data-tag="{{GecerlilikTarihi}}"><code>{{GecerlilikTarihi}}</code> (Geçerlilik Tarihi)</span>
                            <span class="badge badge-light border template-tag py-1 px-2" role="button" title="Kopyalamak için tıklayın" data-tag="{{FirmaAdi}}"><code>{{FirmaAdi}}</code> (Firma Adı)</span>
                        </div>
                    </div>

                    <!-- Şablon İçeriği -->
                    <div class="form-group mb-1">
                        <label for="template-content" class="font-weight-600 text-dark mb-1">
                            Şablon Metni / İçerik <span class="text-danger">*</span>
                        </label>
                        <div class="html-editor-wrapper">
                            <textarea required name="content" id="template-content" class="textarea_editor form-control" rows="10" placeholder="Teklif şablonu metnini veya maddelerini buraya giriniz..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer premium-modal-footer d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-secondary btn-modern-cancel" data-dismiss="modal">
                        <i class="fa fa-times mr-1"></i> İptal
                    </button>
                    <button type="button" id="submitButtonByAjax" class="btn btn-primary btn-modern-save">
                        <i class="fa fa-save mr-1"></i> <span id="submitBtnText">Şablonu Kaydet</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Şablon Önizleme Modalı -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content premium-modal-content">
            <div class="modal-header premium-modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="modal-header-icon mr-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                        <i class="fa fa-eye"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold mb-0" id="previewModalTitle">Şablon Önizleme</h5>
                        <small class="text-muted" id="previewModalSubtitle">Şablonun teklifte nasıl görüntüleneceğini inceleyin</small>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; padding: 10px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-4" style="background: #f8fafc;">
                <div class="preview-meta-bar p-3 mb-3 bg-white rounded border d-flex justify-content-between align-items-center flex-wrap" style="gap: 10px;">
                    <div>
                        <span class="text-muted font-12 d-block">ŞABLON BAŞLIĞI</span>
                        <strong class="font-15 text-dark" id="previewTemplateTitle">-</strong>
                    </div>
                    <div>
                        <span class="text-muted font-12 d-block">TÜR</span>
                        <span class="badge-template-pill" id="previewTemplateTypeBadge">-</span>
                    </div>
                </div>

                <div class="preview-document-card p-4 bg-white rounded border shadow-sm">
                    <div class="preview-document-header text-muted border-bottom pb-2 mb-3 font-12 d-flex justify-content-between">
                        <span><i class="fa fa-file-text-o mr-1"></i> Şablon Doküman İçeriği</span>
                        <button type="button" class="btn btn-sm btn-link p-0 text-primary btn-copy-preview-content" title="Metni Kopyala">
                            <i class="fa fa-copy mr-1"></i> Kopyala
                        </button>
                    </div>
                    <div class="preview-document-body" id="previewDocumentContent" style="line-height: 1.6; color: #334155; font-size: 13.5px; min-height: 100px;">
                        <!-- Dinamik İçerik Buraya Gelecek -->
                    </div>
                </div>
            </div>

            <div class="modal-footer premium-modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-modern-cancel" data-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary btn-modern-edit" id="btnEditFromPreview">
                    <i class="fa fa-pencil mr-1"></i> Bu Şablonu Düzenle
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Premium Header Card */
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
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
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
    transition: all 0.2s ease;
    cursor: pointer;
}
.header-pill:hover {
    transform: translateY(-1px);
}
.header-pill-total {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.header-pill-header {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.header-pill-footer {
    background: #f5f3ff;
    color: #6d28d9;
    border: 1px solid #ddd6fe;
}

/* Header Buttons */
.btn-header {
    padding: 9px 16px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}
.btn-header-save {
    background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}
.btn-header-save:hover {
    background: linear-gradient(135deg, #0369a1 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
    transform: translateY(-1px);
}
.btn-header-offers {
    background: #f1f5f9 !important;
    color: #334155 !important;
    border: 1px solid #cbd5e1 !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.btn-header-offers:hover {
    background: #e2e8f0 !important;
    color: #0f172a !important;
    border-color: #94a3b8 !important;
    transform: translateY(-1px);
}
.btn-header-list {
    background: #f8fafc !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0 !important;
}
.btn-header-list:hover {
    background: #f1f5f9 !important;
    color: #1e293b !important;
}

/* Filter Tabs */
.btn-filter-tab {
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    padding: 6px 12px;
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}
.btn-filter-tab:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.btn-filter-tab.active {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 2px 5px rgba(37, 99, 235, 0.25);
}

/* Badges */
.badge-template-pill {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.badge-template-header {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.badge-template-footer {
    background: #f5f3ff;
    color: #6d28d9;
    border: 1px solid #ddd6fe;
}

/* Template Content Preview Box */
.template-content-preview {
    font-size: 13px;
    color: #475569;
    max-height: 48px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    cursor: pointer;
    line-height: 1.45;
}
.template-content-preview:hover {
    color: #2563eb;
}

.template-title-link {
    font-size: 14px;
    text-decoration: none !important;
    transition: color 0.15s ease;
}
.template-title-link:hover {
    color: #2563eb !important;
}

/* Modals */
.modal-xl {
    max-width: 1050px;
    width: 95%;
}
.wysihtml5-sandbox {
    min-height: 280px !important;
    height: 320px !important;
    border-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    padding: 0 !important;
}
.premium-modal-content {
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}
.premium-modal-header {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 18px 24px;
}
.modal-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.premium-modal-footer {
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    padding: 14px 24px;
}
.form-control-modern {
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    padding: 9px 13px;
    font-size: 14px;
    transition: all 0.2s ease;
}
.form-control-modern:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
.btn-modern-save, .btn-modern-edit {
    background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
    border: none;
    border-radius: 8px;
    padding: 9px 20px;
    font-weight: 600;
    color: #fff;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
}
.btn-modern-save:hover, .btn-modern-edit:hover {
    background: linear-gradient(135deg, #0369a1 0%, #1d4ed8 100%);
    color: #fff;
}
.btn-modern-cancel {
    border-radius: 8px;
    padding: 9px 18px;
    font-weight: 500;
}
.template-tag {
    cursor: pointer;
    transition: all 0.15s ease;
}
.template-tag:hover {
    background: #e2e8f0 !important;
    transform: scale(1.04);
}

/* Dark Mode Desteği */
.dark-mode .premium-header-card,
.dark-mode .form-card,
.dark-mode .premium-modal-content,
.dark-mode .premium-modal-header,
.dark-mode .preview-meta-bar,
.dark-mode .preview-document-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
.dark-mode .form-card-header,
.dark-mode .premium-modal-footer,
.dark-mode .modal-body[style*="background: #f8fafc"],
.dark-mode .template-variables-box {
    background: #0f172a !important;
    border-color: #334155 !important;
}
.dark-mode .header-title h4,
.dark-mode .form-card-header h5,
.dark-mode .template-title-link,
.dark-mode .modal-title,
.dark-mode #previewTemplateTitle,
.dark-mode label.text-dark {
    color: #f8fafc !important;
}
.dark-mode .form-control-modern {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
.dark-mode .btn-filter-tab {
    background: #0f172a;
    color: #94a3b8;
    border-color: #334155;
}
.dark-mode .btn-filter-tab.active {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}
.dark-mode .btn-header-offers {
    background: #334155 !important;
    border-color: #475569 !important;
    color: #f8fafc !important;
}
.dark-mode .btn-header-offers:hover {
    background: #475569 !important;
    color: #ffffff !important;
}
.dark-mode .btn-header-list {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
.dark-mode .preview-document-body {
    color: #cbd5e1 !important;
}
</style>

<script src="include/js/data-table.js"></script>
<script src="include/js/offer-template.js?v=<?php echo time(); ?>"></script>
<script>
$(document).ready(function() {
    // DataTable örneğini güvenli şekilde al veya başlat
    var table;
    if ($.fn.DataTable.isDataTable('#offerTemplatesTable')) {
        table = $('#offerTemplatesTable').DataTable();
    } else {
        table = $('#offerTemplatesTable').DataTable({
            responsive: true,
            pageLength: 25,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Şablonlarda ara..."
            },
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [4] }
            ]
        });
    }

    // Filtre Tab Butonları Olayı
    $('.btn-filter-tab').on('click', function() {
        $('.btn-filter-tab').removeClass('active');
        $(this).addClass('active');
        var filterVal = $(this).data('filter');
        table.column(2).search(filterVal).draw();
    });

    // Header Pill Tıklama Olayları
    $('.header-pill').on('click', function() {
        var filterVal = $(this).data('filter');
        $('.btn-filter-tab').removeClass('active');
        if (filterVal === '') {
            $('.btn-filter-tab[data-filter=""]').addClass('active');
        } else if (filterVal === 'Üst Bilgi') {
            $('.btn-filter-tab[data-filter="Üst Bilgi"]').addClass('active');
        } else if (filterVal === 'Alt Bilgi') {
            $('.btn-filter-tab[data-filter="Alt Bilgi"]').addClass('active');
        }
        table.column(2).search(filterVal).draw();
    });

    // Yeni Şablon Butonu
    $('#btnOpenNewModal').on('click', function() {
        if (typeof resetTemplateModal === 'function') {
            resetTemplateModal();
        }
    });

    // Dinamik Tag Tıklama ve Panoya Kopyalama
    $('.template-tag').on('click', function() {
        var tag = $(this).data('tag');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(tag).then(function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: tag + ' kopyalandı!',
                    showConfirmButton: false,
                    timer: 1500
                });
            });
        }
    });

    // Şablon Önizleme Modalı
    $(document).on('click', '.btn-preview-template', function(e) {
        e.preventDefault();
        var templateId = $(this).data('id');
        var row = $('#row-template-' + templateId);
        var title = row.find('.template-title-link').text().trim();
        var state = row.find('td:eq(2)').text().trim();
        var isHeader = state.indexOf('Üst') !== -1;
        var rawHtml = $('#raw-template-' + templateId).val() || $('#raw-content-' + templateId).html();

        $('#previewTemplateTitle').text(title);
        $('#previewTemplateTypeBadge')
            .text(state)
            .removeClass('badge-template-header badge-template-footer')
            .addClass(isHeader ? 'badge-template-header' : 'badge-template-footer');

        $('#previewDocumentContent').html(rawHtml);
        $('#btnEditFromPreview').data('id', templateId);
        $('#previewModal').modal('show');
    });

    // Önizleme İçeriğini Kopyalama
    $('.btn-copy-preview-content').on('click', function() {
        var textToCopy = $('#previewDocumentContent').text().trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Şablon metni kopyalandı!',
                    showConfirmButton: false,
                    timer: 1500
                });
            });
        }
    });

    // Önizleme Modalından Düzenlemeye Geçiş
    $('#btnEditFromPreview').on('click', function() {
        var templateId = $(this).data('id');
        $('#previewModal').modal('hide');
        setTimeout(function() {
            $('.btn-edit-template[data-id="' + templateId + '"]').trigger('click');
        }, 300);
    });

    // Modal gösterildiğinde editör iframe içeriğini garantiye al
    $('#exampleModalCenter').on('shown.bs.modal', function () {
        var templateId = parseInt($('#id').val(), 10);
        if (templateId > 0) {
            var rawHtml = $('#raw-template-' + templateId).val();
            if (rawHtml) {
                if (typeof setWysihtml5Content === 'function') {
                    setWysihtml5Content(rawHtml);
                }
            }
        }
    });
});
</script>