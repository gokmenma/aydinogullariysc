<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Model\MissionModel;

permcontrol('missiontake');

$currentUserId = function_exists('sesset') ? sesset("id") : ($_SESSION["lid"] ?? ($_SESSION["id"] ?? 0));
$mid = isset($_GET['mid']) ? (int)$_GET['mid'] : 0;

if ($mid <= 0) {
    header("Location: index.php?p=mygmissions&errorcode=00254");
    exit;
}

$missionModel = new MissionModel();
$mission = $missionModel->getMissionById($mid);

if (!$mission || ($mission['deleted'] ?? '') === 'yes') {
    header("Location: index.php?p=mygmissions&errorcode=00784");
    exit;
}

$isCreator = ((int)$mission["creativer"] === (int)$currentUserId);
$hasAllAccess = permtrue("allmisview");

// Yetki kontrolü: Sadece görevi oluşturan veya tüm görevleri görme/yönetme yetkisine sahip kullanıcı düzenleyebilir
if (!$isCreator && !$hasAllAccess) {
    header("Location: index.php?p=mygmissions&errorcode=00403");
    exit;
}

// POST Güncelleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userstring = '';
    $FirmaAdi = trim($_POST['firma_adi'] ?? '');
    $categoryName = trim($_POST['categoryName'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $mdesc = trim($_POST['mdesc'] ?? '');
    $startdate = !empty($_POST['startdate']) ? $_POST['startdate'] : date('d-m-Y');
    $lastdate = trim($_POST['lastdate'] ?? '');
    $urg = trim($_POST['urg'] ?? 'Orta');
    $statu = isset($_POST['statu']) ? (int)$_POST['statu'] : (int)($mission['statu'] ?? 0);

    if (!empty($_POST['permings']) && is_array($_POST['permings'])) {
        foreach ($_POST['permings'] as $autx) {
            $userstring .= (int)$autx . '|';
        }
    }

    $updateData = [
        'FirmaAdi'     => $FirmaAdi,
        'categoryName' => $categoryName,
        'title'        => $title,
        'mdesc'        => $mdesc,
        'startdate'    => $startdate,
        'lastdate'     => !empty($lastdate) ? (function_exists('date_tr') ? date_tr($lastdate) : $lastdate) : '',
        'authors'      => $userstring,
        'urgency'      => $urg,
        'statu'        => $statu
    ];

    $result = $missionModel->updateMission($mid, $updateData, $currentUserId);

    if ($result) {
        $redirectUrl = $isCreator ? 'index.php?p=mygmissions&st=updatesuccess' : 'index.php?p=all-missions&st=updatesuccess';
        header("Location: " . $redirectUrl);
        exit;
    } else {
        $errorMsg = 'Görev güncellenirken bir hata oluştu.';
    }
}

// Mevcut Atanan Kullanıcılar
$currentAuthors = array_filter(explode('|', $mission['authors'] ?? ''));

// Görevi Oluşturan Bilgisi
$usersMap = $missionModel->getUsersMap();
$creatorUser = $usersMap[(int)$mission['creativer']] ?? null;
$creatorName = $creatorUser ? htmlspecialchars($creatorUser['username'] ?? '') : 'Bilinmeyen';
$creatorTitle = $creatorUser ? htmlspecialchars($creatorUser['Unvan'] ?? '') : '';

// Tarih Formatlama
$startDateVal = htmlspecialchars($mission['startdate'] ?? date('d-m-Y'));
$lastDateVal = htmlspecialchars($mission['lastdate'] ?? '');
$currentUrgency = trim($mission['urgency'] ?? 'Orta');
$currentStatu = (int)($mission['statu'] ?? 0);
?>

<style>
    .urgency-selector-wrapper {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 8px !important;
        width: 100% !important;
        margin-top: 6px;
    }
    .urgency-option-premium {
        position: relative;
        cursor: pointer;
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
    }
    .urgency-option-premium input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }
    .urgency-custom-radio {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100% !important;
        box-sizing: border-box !important;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 8px;
        border: 1.5px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 0.88rem;
        font-weight: 600;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        color: #4a5568;
        text-align: center;
    }
    .urgency-custom-radio i {
        font-size: 0.75rem;
    }
    
    .urgency-high input:checked ~ .urgency-custom-radio {
        background-color: #fef2f2;
        border-color: #ef4444;
        color: #ef4444;
        box-shadow: 0 3px 6px rgba(239, 68, 68, 0.12);
    }
    .urgency-high .urgency-custom-radio i { color: #ef4444; }
    
    .urgency-medium input:checked ~ .urgency-custom-radio {
        background-color: #eff6ff;
        border-color: #3b82f6;
        color: #3b82f6;
        box-shadow: 0 3px 6px rgba(59, 130, 246, 0.12);
    }
    .urgency-medium .urgency-custom-radio i { color: #3b82f6; }
    
    .urgency-low input:checked ~ .urgency-custom-radio {
        background-color: #f0fdf4;
        border-color: #22c55e;
        color: #22c55e;
        box-shadow: 0 3px 6px rgba(34, 197, 94, 0.12);
    }
    .urgency-low .urgency-custom-radio i { color: #22c55e; }
    
    .urgency-option-premium:hover .urgency-custom-radio {
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    .editor-wrapper {
        position: relative;
        width: 100%;
    }
    .note-editor .note-placeholder,
    .note-placeholder {
        padding: 6px 12px !important;
        color: #94a3b8 !important;
        top: 0 !important;
        left: 0 !important;
        font-size: 13.5px !important;
        line-height: 1.45 !important;
        pointer-events: none !important;
        box-sizing: border-box !important;
    }
    .note-editor .note-editable,
    .note-editable {
        padding: 6px 12px !important;
        font-size: 13.5px !important;
        line-height: 1.45 !important;
        color: #334155 !important;
        box-sizing: border-box !important;
    }

    /* Select2 Custom Styles */
    .select2-container {
        width: 100% !important;
    }
    .select2-container--default .select2-selection--single {
        height: 44px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background-color: #f8fafc;
        display: flex;
        align-items: center;
        padding: 0 10px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #1e293b;
        font-weight: 500;
        font-size: 13.5px;
        line-height: 42px;
        padding-left: 4px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px;
        right: 10px;
    }
    .select2-container--default .select2-selection--multiple {
        min-height: 44px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background-color: #f8fafc;
        padding: 5px 8px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        border-radius: 6px;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: 600;
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #ef4444;
        margin-right: 4px;
        border: none;
        background: transparent;
        font-weight: bold;
    }
    .select2-dropdown {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        z-index: 1060;
    }
    .select2-container--default .select2-results__group {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        background: #f1f5f9;
        padding: 6px 12px;
    }
    
    /* Select2 User Option Item Layout */
    .s2-user-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 2px 0;
    }
    .s2-user-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .s2-user-details {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }
    .s2-user-name {
        font-weight: 600;
        font-size: 13px;
        color: #1e293b;
    }
    .s2-user-title {
        font-size: 11px;
        color: #64748b;
    }
    .select2-quick-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .btn-select-all, .btn-clear-all {
        border: none;
        background: transparent;
        color: #2563eb;
        font-size: 11.5px;
        font-weight: 600;
        padding: 2px 6px;
        cursor: pointer;
        border-radius: 4px;
        transition: background 0.15s ease;
    }
    .btn-clear-all {
        color: #64748b;
    }
    .btn-select-all:hover, .btn-clear-all:hover {
        background: #f1f5f9;
    }

    /* Dark Mode Support */
    .dark-mode .select2-container--default .select2-selection--single,
    .dark-mode .select2-container--default .select2-selection--multiple {
        background-color: #1e293b;
        border-color: #334155;
    }
    .dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #f1f5f9;
    }
    .dark-mode .select2-dropdown {
        background-color: #1e293b;
        border-color: #334155;
    }
    .dark-mode .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }
    .dark-mode .select2-container--default .select2-results__group {
        background-color: #0f172a;
        color: #94a3b8;
    }
    .dark-mode .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #334155;
        color: #93c5fd;
    }
    .dark-mode .s2-user-name {
        color: #f1f5f9;
    }
    .dark-mode .s2-user-title {
        color: #94a3b8;
    }
</style>

<form action="" method="POST" id="editMissionForm">
    <div class="edit-mission-wrapper mission-module-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in mb-3">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important; color: #fff;">
                        <i class="fa fa-pencil-square-o"></i>
                    </div>
                    <div class="header-title">
                        <h4>Görev Düzenle: <?php echo htmlspecialchars($mission['title'] ?? ''); ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-info-circle"></i> Görev No: #<?php echo $mission['id']; ?> &bull; Bilgileri Güncelleme
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=view-mission&mid=<?php echo $mission['id']; ?>" class="btn-header btn-header-list mr-1">
                        <i class="fa fa-eye"></i> Görüntüle
                    </a>
                    <a href="javascript:history.back();" class="btn-header btn-header-list">
                        <i class="fa fa-arrow-left"></i> Geri Dön
                    </a>
                    <button type="button" id="submitButton" onclick="validateEditForm()" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Değişiklikleri Kaydet
                    </button>
                </div>
            </div>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($errorMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Kart 1: Görev Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div>
                    <h5>Görev Bilgileri</h5>
                    <p>Görevin firma, kategori, konu, zamanlama ve durum detayları</p>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Firma -->
                <div class="form-field">
                    <label for="FirmaAdi"><font color="red">(*)</font> Firma</label>
                    <div class="input-group m-0" style="flex-wrap: nowrap;">
                        <input type="text" class="form-control" name="firma_adi" id="FirmaAdi" value="<?php echo htmlspecialchars($mission['FirmaAdi'] ?? ''); ?>" placeholder="Firma seçiniz veya yazınız!">
                        <button type="button" class="btn btn-primary" style="border-top-left-radius: 0; border-bottom-left-radius: 0;" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            <i class="fa fa-hand-o-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Kategori -->
                <div class="form-field">
                    <label for="categoryName"><font color="red">(*)</font> Kategori</label>
                    <div class="input-group m-0" style="flex-wrap: nowrap;">
                        <select name="categoryName" id="categoryName" class="form-control select2" required style="width: 100%;">
                            <option value=""></option>
                            <?php
                                $sql = $ac->prepare('SELECT * FROM `missioncategory` where NOT categoryName IS NULL');
                                $sql->execute();
                                $categories = $sql->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($categories as $category) {
                                    $catName = $category['categoryName'];
                                    $isSelected = ($catName === ($mission['categoryName'] ?? '')) ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($catName); ?>" <?php echo $isSelected; ?>>
                                    <?php echo htmlspecialchars($catName); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <button type="button" class="btn btn-primary" style="border-top-left-radius: 0; border-bottom-left-radius: 0;" data-bs-toggle="modal" data-bs-target="#exampleModal2">
                            <i class="fa fa-plus-circle"></i>
                        </button>
                    </div>
                </div>

                <!-- Konu -->
                <div class="form-field">
                    <label for="title"><font color="red">(*)</font> Konu / Başlık :</label>
                    <input name="title" id="title" value="<?php echo htmlspecialchars($mission['title'] ?? ''); ?>" class="form-control" required type="text" placeholder="Görev konusunu giriniz">
                </div>

                <!-- Görevi Oluşturan -->
                <div class="form-field">
                    <label for="Olusturan">Görevi Oluşturan</label>
                    <input disabled class="form-control" style="background-color: #f8fafc; font-weight: 600; color: #1e293b;" value="<?php echo $creatorName . ($creatorTitle ? " ({$creatorTitle})" : ''); ?>">
                </div>

                <!-- Başlangıç Tarihi -->
                <div class="form-field">
                    <label for="startdate">Başlangıç Tarihi</label>
                    <input name="startdate" id="startdate" class="form-control date-picker" autocomplete="off" value="<?php echo $startDateVal; ?>" placeholder="Tarih Seçin" type="text">
                </div>

                <!-- Son Tarih -->
                <div class="form-field">
                    <label for="lastdate">Son Tarih</label>
                    <input name="lastdate" id="lastdate" class="form-control date-picker" autocomplete="off" value="<?php echo $lastDateVal; ?>" placeholder="Tarih Seçin" type="text">
                </div>

                <!-- Görev Durumu -->
                <div class="form-field">
                    <label for="statu">Görev Durumu</label>
                    <select name="statu" id="statu" class="form-control selectpicker" data-style="btn-outline-secondary">
                        <option value="0" <?php echo $currentStatu === 0 ? 'selected' : ''; ?>>Bekliyor / İşlemde</option>
                        <option value="1" <?php echo $currentStatu === 1 ? 'selected' : ''; ?>>Tamamlandı (Yapıldı)</option>
                    </select>
                </div>

                <!-- Görevin Atanacağı Kullanıcılar -->
                <div class="form-field full-width">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="permings" class="mb-0"><font color="red">(*)</font> Görevin Atanacağı Kullanıcılar</label>
                        <div class="select2-quick-actions">
                            <button type="button" class="btn-select-all" id="btnSelectAllUsers">
                                <i class="fa fa-check-square-o"></i> Tümünü Seç
                            </button>
                            <button type="button" class="btn-clear-all" id="btnClearAllUsers">
                                <i class="fa fa-square-o"></i> Temizle
                            </button>
                        </div>
                    </div>
                    <select required name="permings[]" id="permings" class="form-control select2" multiple="multiple" style="width: 100%;">
                        <?php
                            $permq = $ac->prepare('SELECT * FROM perms ORDER BY id ASC');
                            $permq->execute();
                            while ($pp = $permq->fetch(PDO::FETCH_ASSOC)) {
                                $permx = $ac->prepare('SELECT id, username, Unvan FROM users WHERE permission = ? AND (statu = 1 OR statu IS NULL) ORDER BY username ASC');
                                $permx->execute(array($pp['id']));
                                $usersInRole = $permx->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (!empty($usersInRole)) {
                        ?>
                                    <optgroup label="<?php echo htmlspecialchars($pp['p_title']); ?>">
                                        <?php foreach ($usersInRole as $px): 
                                            $isAuthorSelected = in_array((string)$px['id'], $currentAuthors) || in_array($px['id'], $currentAuthors);
                                        ?>
                                            <option value="<?php echo $px['id']; ?>" 
                                                    data-title="<?php echo htmlspecialchars($px['Unvan'] ?? ''); ?>"
                                                    <?php echo $isAuthorSelected ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($px['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php }
                            } ?>
                    </select>
                </div>

                <!-- Aciliyet -->
                <div class="form-field full-width">
                    <label>Aciliyet Seviyesi</label>
                    <div class="urgency-selector-wrapper">
                        <label class="urgency-option-premium urgency-high">
                            <input type="radio" id="customRadioInline1" name="urg" value="Yüksek" <?php echo $currentUrgency === 'Yüksek' ? 'checked' : ''; ?>>
                            <span class="urgency-custom-radio"><i class="fa fa-fire"></i> Yüksek</span>
                        </label>
                        <label class="urgency-option-premium urgency-medium">
                            <input type="radio" id="customRadioInline2" name="urg" value="Orta" <?php echo ($currentUrgency === 'Orta' || empty($currentUrgency)) ? 'checked' : ''; ?>>
                            <span class="urgency-custom-radio"><i class="fa fa-bolt"></i> Orta</span>
                        </label>
                        <label class="urgency-option-premium urgency-low">
                            <input type="radio" id="customRadioInline3" name="urg" value="Düşük" <?php echo $currentUrgency === 'Düşük' ? 'checked' : ''; ?>>
                            <span class="urgency-custom-radio"><i class="fa fa-leaf"></i> Düşük</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Görev Açıklaması -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-purple">
                    <i class="fa fa-pencil-square-o"></i>
                </div>
                <div>
                    <h5>Görev Açıklaması</h5>
                    <p>Görevin içeriği, gereksinimleri ve süreç notları</p>
                </div>
            </div>
            <div class="editor-wrapper">
                <textarea name="mdesc" class="textarea_editor form-control border-radius-8" placeholder="Görev ile ilgili notlar ve açıklamalar..."><?php echo htmlspecialchars($mission['mdesc'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Modal 1: Firma Seç -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Firma Seç</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="small text-muted mb-2">Listeden arayarak firma seçebilirsiniz:</label>
                        <select id="FirmaSec" name="FirmaSec" class="form-control select2" style="width: 100%;">
                            <option value=""></option>
                            <?php
                                $cek = $ac->prepare('SELECT id, company FROM customers WHERE deleted_at IS NULL ORDER BY company ASC');
                                $cek->execute();
                                while ($dat = $cek->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                                <option value="<?php echo htmlspecialchars($dat['company']); ?>">
                                    <?php echo htmlspecialchars($dat['company']); ?>
                                </option>
                            <?php
                                }
                            ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="button" id="ModalSaveButton" onclick="Sec()" data-bs-dismiss="modal" class="btn btn-primary">Seç</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 2: Kategori Ekle -->
        <div class="modal fade" id="exampleModal2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Kategori Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" class="form-control" name="Addcategory" id="Addcategory" placeholder="Eklenecek kategori adını yazınız...">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="button" id="ModalSaveCatButton" onclick="SaveNewCategory()" class="btn btn-primary">Kaydet</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function Sec() {
    var selectedValue = document.getElementById('FirmaSec').value;
    if (selectedValue) {
        document.getElementById('FirmaAdi').value = selectedValue;
    }
}

function SaveNewCategory() {
    var catName = $('#Addcategory').val().trim();
    if (!catName) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Lütfen kategori adını yazınız.' });
        } else {
            alert('Lütfen kategori adını yazınız.');
        }
        return;
    }

    var newOption = new Option(catName, catName, true, true);
    $('#categoryName').append(newOption).trigger('change');
    $('#exampleModal2').modal('hide');
    $('#Addcategory').val('');
}

function validateEditForm() {
    var title = $('#title').val().trim();
    var permings = $('#permings').val();

    if (!title) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Eksik Alan', text: 'Lütfen görev konusunu (başlık) giriniz.' });
        } else {
            alert('Lütfen görev konusunu giriniz.');
        }
        $('#title').focus();
        return false;
    }

    if (!permings || permings.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Eksik Alan', text: 'Lütfen görevin atanacağı en az bir kullanıcı seçiniz.' });
        } else {
            alert('Lütfen görevin atanacağı kullanıcıyı seçiniz.');
        }
        return false;
    }

    $('#editMissionForm').submit();
}

$(document).ready(function () {
    function formatUserOption(state) {
        if (!state.id) return state.text;
        var title = $(state.element).data('title');
        var initial = state.text.trim().charAt(0).toUpperCase() || '?';
        var $state = $(
            '<div class="s2-user-item">' +
                '<div class="s2-user-avatar">' + initial + '</div>' +
                '<div class="s2-user-details">' +
                    '<span class="s2-user-name">' + state.text + '</span>' +
                    (title ? '<span class="s2-user-title">' + title + '</span>' : '') +
                '</div>' +
            '</div>'
        );
        return $state;
    }

    // Kategori Select2
    $('#categoryName').select2({
        placeholder: 'Kategori Seçiniz...',
        allowClear: true,
        width: '100%'
    });

    // Kullanıcılar Select2 Multi-Select
    $('#permings').select2({
        placeholder: 'Görevin atanacağı kullanıcıları seçiniz...',
        allowClear: true,
        width: '100%',
        templateResult: formatUserOption
    });

    // Modal Firma Select2
    $('#FirmaSec').select2({
        placeholder: 'Firma arayınız veya seçiniz...',
        allowClear: true,
        dropdownParent: $('#exampleModal'),
        width: '100%'
    });

    // Tümünü Seç / Temizle Butonları
    $('#btnSelectAllUsers').on('click', function () {
        $('#permings option').prop('selected', true);
        $('#permings').trigger('change');
    });

    $('#btnClearAllUsers').on('click', function () {
        $('#permings').val(null).trigger('change');
    });

    // WYSIHTML5 / Summernote Editör Padding Ayarı
    function applyEditorPadding() {
        $('iframe.wysihtml5-sandbox').each(function () {
            try {
                var doc = this.contentDocument || this.contentWindow.document;
                if (doc && doc.body) {
                    if (!doc.getElementById('wysi-placeholder-padding-style')) {
                        var style = doc.createElement('style');
                        style.id = 'wysi-placeholder-padding-style';
                        style.innerHTML = 'html, body { padding: 6px 12px !important; margin: 0 !important; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important; font-size: 13.5px !important; line-height: 1.45 !important; color: #334155 !important; box-sizing: border-box !important; } body.placeholder { color: #94a3b8 !important; padding: 6px 12px !important; margin: 0 !important; }';
                        doc.head.appendChild(style);
                    }
                    doc.body.style.padding = '6px 12px';
                }
            } catch (e) {}
        });
    }

    applyEditorPadding();
    setTimeout(applyEditorPadding, 250);
    setTimeout(applyEditorPadding, 800);
    setTimeout(applyEditorPadding, 2000);
    $(window).on('load', applyEditorPadding);
});
</script>
