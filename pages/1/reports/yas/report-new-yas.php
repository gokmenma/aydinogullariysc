<?php
// Rapor Ekleme Yetkisi
permcontrol("reportadd");

$id = (int)($_GET["id"] ?? 0);
$is_edit = $id > 0;

if ($is_edit) {
    $query = $ac->prepare("SELECT * FROM reports WHERE id = ?");
    $query->execute([$id]);
    $report = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        header("Location: index.php?p=reports/reports");
        exit;
    }
    
    $matters = json_decode($report["report_matters"] ?? "[]", true);
    $bakim = json_decode($report["bakim_bilgileri"] ?? "[]", true);
    $dedectors = json_decode($report["dedektor_info"] ?? "[]", true);
    $controller_peak = json_decode($report["controller_peak_info"] ?? "[]", true);
    
    $report_number = $report["report_number"] ?? "";
} else {
    $getNumber = setNumber("yas");
    $getNumber = sprintf("%04d", $getNumber);
    $report_number = "YAS" . $getNumber;
    $matters = []; 
    $bakim = []; 
    $dedectors = []; 
    $controller_peak = []; 
    $report = [];
}

$type = 4;

if ($_POST) {
    if (isset($_POST["report_number"])) {
        $report_number_post = trim($_POST["report_number"] ?? $report_number);
        $isemrino = trim($_POST["isemrino"] ?? '');
        $customer_id = (int)($_POST["customer"] ?? 0);
        $control_date = trim($_POST["control_date"] ?? date('d.m.Y'));
        $next_control_date = trim($_POST["next_control_date"] ?? date('d.m.Y', strtotime('+1 year')));
        $controller_id = (int)($_POST["controller"] ?? 0);
        
        if (empty($customer_id)) {
            header("Location: index.php?p=reports/yas/report-new-yas" . ($is_edit ? "&id=" . $id : "") . "&st=empties");
            exit;
        }

        $report_matters_data = [
            "header_extra" => [
                "kontrol_adresi" => $_POST["kontrol_adresi"] ?? "",
                "isg_katip_id" => $_POST["isg_katip_id"] ?? "",
                "sgk_sicil" => $_POST["sgk_sicil"] ?? "",
                "metot_kapsam" => $_POST["metot_kapsam"] ?? "",
                "test_degerleri" => $_POST["test_degerleri"] ?? "",
                "kusur_aciklamalari" => $_POST["kusur_aciklamalari"] ?? "",
                "notlar" => $_POST["notlar"] ?? "",
                "sonuc_kanaat" => $_POST["sonuc_kanaat"] ?? ""
            ],
            "tesis_detay" => [
                "algilama_tipi" => $_POST["algilama_tipi"] ?? "",
                "uyari_sistemi" => $_POST["uyari_sistemi"] ?? "",
                "calisma_tipi" => $_POST["calisma_tipi"] ?? "",
                "proje_onay_kurum" => $_POST["proje_onay_kurum"] ?? "",
                "kontrol_nedeni" => $_POST["kontrol_nedeni"] ?? "",
                "proje_onay_tarih" => $_POST["proje_onay_tarih"] ?? "",
                "panel_marka" => $_POST["panel_marka"] ?? "",
                "ilk_kontrol_tarihi" => $_POST["ilk_kontrol_tarihi"] ?? "",
                "last_control_date" => $_POST["last_control_date"] ?? "",
                "panel_seri_no" => $_POST["panel_seri_no"] ?? "",
                "panel_gerilim" => $_POST["panel_gerilim"] ?? "",
                "panel_yeri" => $_POST["panel_yeri"] ?? "",
                "algilama_ekipmanlari" => $_POST["algilama_ekipmanlari"] ?? [],
                "uyari_ekipmanlari" => $_POST["uyari_ekipmanlari"] ?? [],
                "sondurme_ekipmanlari" => $_POST["sondurme_ekipmanlari"] ?? []
            ],
            "bina_tespitleri" => [
                "tesisat_degisiklik" => $_POST["tesisat_degisiklik"] ?? "",
                "etiket_varmi" => $_POST["etiket_varmi"] ?? "",
                "bina_sinifi" => $_POST["bina_sinifi"] ?? [],
                "tehlike_sinifi" => $_POST["tehlike_sinifi"] ?? "",
                "tehlike_kategorisi" => $_POST["tehlike_kategorisi"] ?? "",
                "alan" => $_POST["bina_alan"] ?? "",
                "kat" => $_POST["bina_kat"] ?? "",
                "yukseklik" => $_POST["bina_yukseklik"] ?? "",
                "izin_tarihi" => $_POST["bina_izin_tarihi"] ?? "",
                "bolum_sayisi" => $_POST["bina_bolum_sayisi"] ?? "",
                "diger" => $_POST["bina_diger"] ?? ""
            ],
            "olcum_cihazlari" => [
                [
                    "ad" => $_POST["cihaz1_ad"] ?? "",
                    "seri" => $_POST["cihaz1_seri"] ?? "",
                    "kal_no" => $_POST["cihaz1_kal_no"] ?? "",
                    "kal_tar" => $_POST["cihaz1_kal_tar"] ?? "",
                    "gec_tar" => $_POST["cihaz1_gec_tar"] ?? ""
                ],
                [
                    "ad" => $_POST["cihaz2_ad"] ?? "",
                    "seri" => $_POST["cihaz2_seri"] ?? "",
                    "kal_no" => $_POST["cihaz2_kal_no"] ?? "",
                    "kal_tar" => $_POST["cihaz2_kal_tar"] ?? "",
                    "gec_tar" => $_POST["cihaz2_gec_tar"] ?? ""
                ]
            ],
            "inspections" => []
        ];

        for ($i = 1; $i <= 50; $i++) {
            $report_matters_data["inspections"]["madde$i"] = isset($_POST["madde$i"]) ? "UYGUN" : "UYGUN DEĞİL";
        }
        $jsonDataMatters = json_encode($report_matters_data, JSON_UNESCAPED_UNICODE);
        
        $jsonDataDedektor = (!empty($_POST["equipment_data_json"])) ? $_POST["equipment_data_json"] : "[]";
        $jsonDataControllerPeak = json_encode([
            "name" => $_POST["controller_peak"] ?? "",
            "diploma" => $_POST["controller_peak_diploma"] ?? "",
            "emo" => $_POST["controller_peak_emo"] ?? "",
            "ekipnet" => $_POST["controller_peak_ekipnet"] ?? ""
        ], JSON_UNESCAPED_UNICODE);
        $jsonDataBakim = json_encode(["result_note" => $_POST["sonuc_kanaat"] ?? ""], JSON_UNESCAPED_UNICODE);

        try {
            if ($is_edit) {
                $query = $ac->prepare("UPDATE reports SET 
                    report_number = ?, 
                    isemrino = ?, 
                    customer_id = ?, 
                    control_date = ?, 
                    next_control_date = ?, 
                    controller_id = ?, 
                    report_matters = ?, 
                    bakim_bilgileri = ?, 
                    dedektor_info = ?, 
                    controller_peak_info = ?,
                    updater = ?,
                    update_time = ?
                    WHERE id = ?");
                $query->execute([
                    $report_number_post,
                    $isemrino,
                    $customer_id,
                    $control_date,
                    $next_control_date,
                    $controller_id,
                    $jsonDataMatters,
                    $jsonDataBakim,
                    $jsonDataDedektor,
                    $jsonDataControllerPeak,
                    sesset("id"),
                    date("Y-m-d H:i:s"),
                    $id
                ]);
                
                audit_log("update", "report", "Yangın Algılama Raporu güncellendi: " . $report_number_post, "reports", $id);
                header("Location: index.php?p=reports/yas/report-new-yas&id=" . $id . "&st=updatesuccess");
                exit;
            } else {
                $query = $ac->prepare("INSERT INTO reports SET 
                    report_number = ?, 
                    isemrino = ?, 
                    report_type = ?, 
                    customer_id = ?, 
                    control_date = ?, 
                    next_control_date = ?, 
                    controller_id = ?, 
                    report_matters = ?, 
                    bakim_bilgileri = ?, 
                    dedektor_info = ?, 
                    controller_peak_info = ?, 
                    creator = ?, 
                    create_time = ?");
                $query->execute([
                    $report_number_post,
                    $isemrino,
                    $type,
                    $customer_id,
                    $control_date,
                    $next_control_date,
                    $controller_id,
                    $jsonDataMatters,
                    $jsonDataBakim,
                    $jsonDataDedektor,
                    $jsonDataControllerPeak,
                    sesset("id"),
                    date("Y-m-d H:i:s")
                ]);
                $lastid = $ac->lastInsertId();

                $getNumber = (int)$getNumber + 1;
                $upquery = $ac->prepare("UPDATE define_numbers SET yas = ?");
                $upquery->execute([$getNumber]);

                audit_log("create", "report", "Yangın Algılama Raporu oluşturuldu: " . $report_number_post, "reports", $lastid);
                header("Location: index.php?p=reports/yas/report-new-yas&st=newsuccess&last_id=" . $lastid);
                exit;
            }
        } catch (PDOException $e) { 
            error_log("YAS Raporu Kayıt Hatası: " . $e->getMessage());
            header("Location: index.php?p=reports/yas/report-new-yas" . ($is_edit ? "&id=" . $id : "") . "&st=error");
            exit;
        }
    }
}

$header_extra = $matters["header_extra"] ?? [];
$tesis_detay = $matters["tesis_detay"] ?? [];
$bina_tespitleri = $matters["bina_tespitleri"] ?? [];
$olcum_cihazlari = $matters["olcum_cihazlari"] ?? [];
$inspections = $matters["inspections"] ?? [];

function getCheck($val, $arr) { 
    return in_array($val, (array)$arr) ? "checked" : ""; 
}

function options($arr, $selected) { 
    $o = ""; 
    foreach($arr as $v) { 
        $s = ($v == $selected) ? "selected" : ""; 
        $o .= "<option value='" . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . "' $s>" . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . "</option>"; 
    } 
    return $o; 
}

$st = $_GET["st"] ?? '';
$created_id = (int)($_GET["last_id"] ?? 0);
?>

<?php if ($st === "newsuccess"): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: 'Başarılı!',
            text: 'Yangın Algılama Sistemi Kontrol Raporu başarıyla oluşturuldu.',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-list"></i> Rapor Listesine Git',
            cancelButtonText: '<i class="fa fa-plus"></i> Yeni Rapor Ekle',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = 'index.php?p=reports/reports';
            } else {
                window.history.replaceState({}, document.title, 'index.php?p=reports/yas/report-new-yas');
            }
        });
    }
});
</script>
<?php elseif ($st === "updatesuccess"): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: 'Başarılı!',
            text: 'Yangın Algılama Sistemi Kontrol Raporu güncellendi.',
            icon: 'success',
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#2563eb'
        }).then(function() {
            window.history.replaceState({}, document.title, 'index.php?p=reports/yas/report-new-yas&id=<?php echo $id; ?>');
        });
    }
});
</script>
<?php elseif ($st === "empties"): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: 'Eksik Bilgi!',
            text: 'Lütfen firma seçimini ve zorunlu alanları doldurunuz.',
            icon: 'warning',
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#f59e0b'
        }).then(function() {
            window.history.replaceState({}, document.title, 'index.php?p=reports/yas/report-new-yas<?php echo $is_edit ? "&id=".$id : ""; ?>');
        });
    }
});
</script>
<?php elseif ($st === "error"): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: 'Hata!',
            text: 'Rapor kaydedilirken bir hata oluştu. Lütfen tekrar deneyiniz.',
            icon: 'error',
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#ef4444'
        }).then(function() {
            window.history.replaceState({}, document.title, 'index.php?p=reports/yas/report-new-yas<?php echo $is_edit ? "&id=".$id : ""; ?>');
        });
    }
});
</script>
<?php endif; ?>

<style>
    .yas-report-wrapper {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        padding-bottom: 40px;
    }

    /* Custom Nav Pills Matching Premium Theme */
    .custom-report-pills {
        display: flex;
        gap: 8px;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 10px;
        margin-bottom: 20px;
        overflow-x: auto;
        white-space: nowrap;
    }

    .custom-report-pills .nav-link {
        color: #64748b;
        font-weight: 600;
        font-size: 12px;
        padding: 8px 16px;
        border-radius: 8px;
        border: none;
        transition: all 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background: transparent;
    }

    .custom-report-pills .nav-link:hover {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.6);
    }

    .custom-report-pills .nav-link.active {
        color: #2563eb;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    }

    /* Minimal Table Container Matching premium-theme */
    .yas-table-wrapper {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .yas-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .premium-table.yas-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 0;
        margin-bottom: 0;
        border: none !important;
    }

    .premium-table.yas-table thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 11px !important;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 8px 6px !important;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #f1f5f9;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    .premium-table.yas-table thead tr.main-head th {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
    }

    .premium-table.yas-table td {
        padding: 4px 4px !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f8fafc;
        background: #ffffff;
        text-align: center;
    }

    .premium-table.yas-table tbody tr:hover td {
        background: #f8fafc;
    }

    .premium-table.yas-table .form-control {
        height: 30px !important;
        padding: 2px 6px !important;
        font-size: 12px !important;
        border-radius: 6px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff;
        transition: all 0.15s ease-in-out;
    }

    .premium-table.yas-table .form-control:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12) !important;
        background: #fff !important;
    }

    .premium-table.yas-table .btn_remove {
        padding: 0 !important;
        width: 26px;
        height: 26px;
        line-height: 26px;
        border-radius: 6px !important;
        font-size: 11px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #fee2e2;
        border-color: #fecaca;
        color: #ef4444;
        transition: all 0.2s;
    }

    .premium-table.yas-table .btn_remove:hover {
        background-color: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .table-actions-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }

    .ins-row { 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
        padding: 8px 12px; 
        border: 1px solid #e2e8f0; 
        border-radius: 8px; 
        background: #fff; 
        margin-bottom: 8px; 
        transition: all 0.15s ease-in-out;
    }
    .ins-row:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }
    .ins-label { 
        font-size: 11px; 
        color: #334155; 
        font-weight: 600; 
        line-height: 1.4; 
    }

    /* Switch Style */
    .switch { position: relative; display: inline-block; width: 34px; height: 18px; flex-shrink: 0; margin-bottom: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 20px; }
    .slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    input:checked + .slider { background-color: #2563eb; }
    input:checked + .slider:before { transform: translateX(16px); }

    .group-title { 
        font-size: 11px; 
        font-weight: 700; 
        color: #1e293b; 
        background: #f1f5f9; 
        padding: 8px 14px; 
        border-radius: 8px; 
        margin: 15px 0 10px 0; 
        text-transform: uppercase; 
        border: 1px solid #e2e8f0; 
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cb-group { background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .cb-item { font-size: 11.5px; font-weight: 500; color: #475569; display: flex; align-items: center; margin-bottom: 6px; }
    .cb-item input { margin-right: 8px; }

    .btn-template { 
        background: #eff6ff; 
        color: #2563eb; 
        border: 1px solid #bfdbfe; 
        font-size: 11px; 
        font-weight: 600; 
        border-radius: 6px; 
        padding: 4px 10px; 
        transition: 0.2s; 
    }
    .btn-template:hover { background: #dbeafe; color: #1d4ed8; }

    /* Select2 Tweaks */
    .select2-container .select2-selection--single {
        height: 38px !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 12px !important;
        font-size: 13px !important;
        color: #334155 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
    }

    /* Dark Mode */
    .dark-mode .custom-report-pills { background: #0f172a; }
    .dark-mode .custom-report-pills .nav-link { color: #94a3b8; }
    .dark-mode .custom-report-pills .nav-link.active { color: #38bdf8; background: #1e293b; }
    .dark-mode .yas-table-wrapper { background: #1e293b; border-color: #334155; }
    .dark-mode .premium-table.yas-table thead th { background: #0f172a !important; color: #cbd5e1; border-color: #334155; }
    .dark-mode .premium-table.yas-table thead tr.main-head th { background: #1e293b !important; color: #f8fafc; border-color: #334155; }
    .dark-mode .premium-table.yas-table td { background: #1e293b; border-color: #334155; color: #e2e8f0; }
    .dark-mode .premium-table.yas-table tbody tr:hover td { background: #283548; }
    .dark-mode .premium-table.yas-table .form-control { background: #0f172a !important; border-color: #334155 !important; color: #f8fafc !important; }
    .dark-mode .ins-row { background: #1e293b; border-color: #334155; }
    .dark-mode .ins-row:hover { background: #283548; }
    .dark-mode .ins-label { color: #e2e8f0; }
    .dark-mode .group-title { background: #0f172a; border-color: #334155; color: #cbd5e1; }
    .dark-mode .cb-group { background: #0f172a; border-color: #334155; }
    .dark-mode .cb-item { color: #cbd5e1; }
</style>

<form enctype="multipart/form-data" id="myForm" method="POST">
    <input type="hidden" name="equipment_data_json" id="equipment_data_json">
    <div class="yas-report-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                        <i class="fa fa-bell-o"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo $is_edit ? "Yangın Algılama Raporunu Düzenle" : "Yeni Yangın Algılama ve Uyarı Raporu"; ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-tag"></i> Rapor No: <?php echo htmlspecialchars($report_number, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=reports/reports" class="btn-header btn-header-list">
                        <i class="fa fa-list"></i> Listeye Dön
                    </a>
                    <button type="button" onclick="previewReport()" class="btn-header" style="background: #f59e0b; color: #fff;">
                        <i class="fa fa-eye"></i> Önizle
                    </button>
                    <button type="submit" id="submitButton" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> <?php echo $is_edit ? "Değişiklikleri Kaydet" : "Raporu Kaydet"; ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Pills Navigation -->
        <ul class="nav custom-report-pills animate-fade-in" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="p1-tab" data-toggle="pill" data-target="#p1" data-bs-toggle="pill" data-bs-target="#p1" type="button" role="tab" aria-controls="p1" aria-selected="true">
                    <i class="fa fa-building-o mr-1"></i> 1. Firma Bilgileri
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="p2-tab" data-toggle="pill" data-target="#p2" data-bs-toggle="pill" data-bs-target="#p2" type="button" role="tab" aria-controls="p2" aria-selected="false">
                    <i class="fa fa-home mr-1"></i> 2. Tesis / Bina
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="p3-tab" data-toggle="pill" data-target="#p3" data-bs-toggle="pill" data-bs-target="#p3" type="button" role="tab" aria-controls="p3" aria-selected="false">
                    <i class="fa fa-flask mr-1"></i> 3. Test Değerleri
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="p4-tab" data-toggle="pill" data-target="#p4" data-bs-toggle="pill" data-bs-target="#p4" type="button" role="tab" aria-controls="p4" aria-selected="false">
                    <i class="fa fa-tachometer mr-1"></i> 4. Ölçüm Aletleri
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="p5-tab" data-toggle="pill" data-target="#p5" data-bs-toggle="pill" data-bs-target="#p5" type="button" role="tab" aria-controls="p5" aria-selected="false">
                    <i class="fa fa-check-square-o mr-1"></i> 5. Muayene & Ürünler
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="p69-tab" data-toggle="pill" data-target="#p69" data-bs-toggle="pill" data-bs-target="#p69" type="button" role="tab" aria-controls="p69" aria-selected="false">
                    <i class="fa fa-certificate mr-1"></i> 6-9. Sonuç & Onay
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- TAB 1: FİRMA BİLGİLERİ -->
            <div class="tab-pane fade show active" id="p1" role="tabpanel" aria-labelledby="p1-tab">
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-blue">
                            <i class="fa fa-building-o"></i>
                        </div>
                        <div>
                            <h5>1. Firma ve Denetim Bilgileri</h5>
                            <p>Rapor numarası, müşteri seçimi, iş emri ve denetim parametreleri</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <!-- Firma Adı -->
                        <div class="form-field full-width">
                            <label for="customer"><font color="red">(*)</font> Firma Adı:</label>
                            <select required name="customer" id="customer" class="form-control select2" style="width:100%;">
                                <option disabled <?php echo empty($report["customer_id"] ?? 0) ? "selected" : ""; ?> value="">Firma Seçiniz</option>
                                <?php
                                $compquery = $ac->prepare("SELECT id, company FROM customers WHERE deleted_at IS NULL ORDER BY company ASC");
                                $compquery->execute();
                                while ($company = $compquery->fetch(PDO::FETCH_ASSOC)) {
                                    $sel = (($report["customer_id"] ?? 0) == $company["id"]) ? "selected" : "";
                                ?>
                                    <option value="<?php echo $company["id"]; ?>" <?php echo $sel; ?>>
                                        <?php echo htmlspecialchars($company["company"], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Rapor No -->
                        <div class="form-field">
                            <label for="report_number"><font color="red">(*)</font> Rapor Numarası:</label>
                            <input required name="report_number" id="report_number" type="text" value="<?php echo htmlspecialchars($report_number, ENT_QUOTES, 'UTF-8'); ?>" class="form-control font-weight-bold bg-light">
                        </div>

                        <!-- İş Emri No -->
                        <div class="form-field">
                            <label for="isemrino"><font color="red">(*)</font> İş Emri No:</label>
                            <select name="isemrino" id="isemrino" class="form-control select2" style="width:100%;">
                                <option value="">İş Emri Seçiniz</option>
                                <?php
                                $servicequery = $ac->prepare("SELECT id FROM projects ORDER BY id DESC");
                                $servicequery->execute();
                                while ($isemri = $servicequery->fetch(PDO::FETCH_ASSOC)) {
                                    $sn_val = "SN" . $isemri["id"];
                                    $sel = (trim($report["isemrino"] ?? '') == $sn_val) ? "selected" : "";
                                ?>
                                    <option value="<?php echo $sn_val; ?>" <?php echo $sel; ?>><?php echo $sn_val; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Kontrol Adresi -->
                        <div class="form-field full-width">
                            <label for="kontrol_adresi">Periyodik Kontrol Adresi:</label>
                            <input name="kontrol_adresi" id="kontrol_adresi" value="<?php echo htmlspecialchars($header_extra["kontrol_adresi"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Adres giriniz">
                        </div>

                        <!-- Rapor Tarihi -->
                        <div class="form-field">
                            <label for="control_date"><font color="red">(*)</font> Rapor / Kontrol Tarihi:</label>
                            <input required name="control_date" id="control_date" value="<?php echo htmlspecialchars($report["control_date"] ?? date('d.m.Y'), ENT_QUOTES, 'UTF-8'); ?>" class="form-control date-picker" autocomplete="off">
                        </div>

                        <!-- İSG-KATİP Sözleşme ID -->
                        <div class="form-field">
                            <label for="isg_katip_id">İSG-KATİP Sözleşme ID:</label>
                            <input name="isg_katip_id" id="isg_katip_id" value="<?php echo htmlspecialchars($header_extra["isg_katip_id"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Örn: 12345678">
                        </div>

                        <!-- SGK Sicil No -->
                        <div class="form-field">
                            <label for="sgk_sicil">SGK Sicil Numarası:</label>
                            <input name="sgk_sicil" id="sgk_sicil" value="<?php echo htmlspecialchars($header_extra["sgk_sicil"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="SGK Sicil No">
                        </div>

                        <!-- Kontrol Personeli -->
                        <div class="form-field">
                            <label for="controller"><font color="red">(*)</font> Kontrol Personeli:</label>
                            <select required name="controller" id="controller" class="form-control select2" style="width:100%;">
                                <option disabled <?php echo empty($report["controller_id"] ?? 0) ? "selected" : ""; ?> value="">Personel Seçiniz</option>
                                <?php
                                $userquery = $ac->prepare("SELECT id, username, meslek FROM users ORDER BY username ASC");
                                $userquery->execute();
                                while ($usr = $userquery->fetch(PDO::FETCH_ASSOC)) {
                                    $sel = (($report["controller_id"] ?? 0) == $usr["id"]) ? "selected" : "";
                                ?>
                                    <option value="<?php echo $usr["id"]; ?>" <?php echo $sel; ?>>
                                        <?php echo htmlspecialchars($usr["username"] . (!empty($usr["meslek"]) ? " (" . $usr["meslek"] . ")" : ""), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Bir Sonraki Periyodik Kontrol Tarihi -->
                        <div class="form-field">
                            <label for="next_control_date"><font color="red">(*)</font> Bir Sonraki Kontrol Tarihi:</label>
                            <input required name="next_control_date" id="next_control_date" value="<?php echo htmlspecialchars($report["next_control_date"] ?? date('d.m.Y', strtotime('+1 year')), ENT_QUOTES, 'UTF-8'); ?>" class="form-control date-picker" autocomplete="off">
                        </div>

                        <!-- Periyodik Kontrol Metodu ve Kapsamı -->
                        <div class="form-field full-width">
                            <label for="metot_kapsam">Periyodik Kontrol Metodu ve Kapsamı:</label>
                            <div class="html-editor">
                                <textarea name="metot_kapsam" id="metot_kapsam" class="textarea_editor form-control" style="height: 120px;"><?php echo htmlspecialchars($header_extra["metot_kapsam"] ?? "<ul><li>TSE CEN/TS 54-14: Yangın Algılama ve Yangın Alarm Sistemleri - Planlama, Tasarım, Montaj, İşletmeye Alma, Kullanım ve Bakım Kriterleri</li></ul>", ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: TESİS / BİNA BİLGİLERİ -->
            <div class="tab-pane fade" id="p2" role="tabpanel" aria-labelledby="p2-tab">
                <!-- 2.1 Sistem Detay Bilgileri -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-orange">
                            <i class="fa fa-cogs"></i>
                        </div>
                        <div>
                            <h5>2.1. Sistem Detay Bilgileri</h5>
                            <p>Yangın algılama ve uyarı sisteminin teknik parametreleri</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label>Yangın Algılama Tipi:</label>
                            <select name="algilama_tipi" class="form-control select2" style="width:100%;">
                                <?php echo options(["Otomatik", "Manuel"], $tesis_detay["algilama_tipi"] ?? ""); ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Yangın Uyarı Sistemi:</label>
                            <select name="uyari_sistemi" class="form-control select2" style="width:100%;">
                                <?php echo options(["Işıklı+Sesli", "Sesli", "Işıklı", "Anons"], $tesis_detay["uyari_sistemi"] ?? ""); ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Sistem Çalışma Tipi:</label>
                            <select name="calisma_tipi" class="form-control select2" style="width:100%;">
                                <?php echo options(["Adresli", "Konvansiyonel"], $tesis_detay["calisma_tipi"] ?? ""); ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Proje Onay Kurumu:</label>
                            <input name="proje_onay_kurum" value="<?php echo htmlspecialchars($tesis_detay["proje_onay_kurum"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Kurum adı">
                        </div>
                        <div class="form-field">
                            <label>Kontrol Nedeni:</label>
                            <select name="kontrol_nedeni" class="form-control select2" style="width:100%;">
                                <?php echo options(["Periyodik Kontrol", "İlk Kontrol"], $tesis_detay["kontrol_nedeni"] ?? ""); ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Proje Onay Tarih ve Sayısı:</label>
                            <input name="proje_onay_tarih" value="<?php echo htmlspecialchars($tesis_detay["proje_onay_tarih"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Tarih / Sayı">
                        </div>
                        <div class="form-field">
                            <label>Kontrol Paneli Marka/Model:</label>
                            <input name="panel_marka" value="<?php echo htmlspecialchars($tesis_detay["panel_marka"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Marka / Model">
                        </div>
                        <div class="form-field">
                            <label>İlk Kontrol Tarihi:</label>
                            <input name="ilk_kontrol_tarihi" value="<?php echo htmlspecialchars($tesis_detay["ilk_kontrol_tarihi"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control date-picker" autocomplete="off">
                        </div>
                        <div class="form-field">
                            <label>Son Kontrol Tarihi:</label>
                            <input name="last_control_date" value="<?php echo htmlspecialchars($tesis_detay["last_control_date"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control date-picker" autocomplete="off">
                        </div>
                        <div class="form-field">
                            <label>Panel Seri No / İmal Yılı:</label>
                            <input name="panel_seri_no" value="<?php echo htmlspecialchars($tesis_detay["panel_seri_no"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Seri No">
                        </div>
                        <div class="form-field">
                            <label>Panel Çalışma Gerilimi:</label>
                            <input name="panel_gerilim" value="<?php echo htmlspecialchars($tesis_detay["panel_gerilim"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Örn: 24V DC / 220V AC">
                        </div>
                        <div class="form-field">
                            <label>Panel Yeri:</label>
                            <input name="panel_yeri" value="<?php echo htmlspecialchars($tesis_detay["panel_yeri"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Panel konumu">
                        </div>

                        <!-- Checkbox Grupları -->
                        <div class="form-field">
                            <label class="font-weight-600">Algılama Ekipmanları:</label>
                            <div class="cb-group">
                                <div class="cb-item"><input type="checkbox" name="algilama_ekipmanlari[]" value="Duman" <?php echo getCheck("Duman", $tesis_detay["algilama_ekipmanlari"] ?? []); ?>> Duman (Optik) Dedektörü</div>
                                <div class="cb-item"><input type="checkbox" name="algilama_ekipmanlari[]" value="Isı" <?php echo getCheck("Isı", $tesis_detay["algilama_ekipmanlari"] ?? []); ?>> Isı Dedektörü</div>
                                <div class="cb-item"><input type="checkbox" name="algilama_ekipmanlari[]" value="Buton" <?php echo getCheck("Buton", $tesis_detay["algilama_ekipmanlari"] ?? []); ?>> İhbar Butonu</div>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="font-weight-600">Uyarı Ekipmanları:</label>
                            <div class="cb-group">
                                <div class="cb-item"><input type="checkbox" name="uyari_ekipmanlari[]" value="Siren" <?php echo getCheck("Siren", $tesis_detay["uyari_ekipmanlari"] ?? []); ?>> Siren</div>
                                <div class="cb-item"><input type="checkbox" name="uyari_ekipmanlari[]" value="Flaşör" <?php echo getCheck("Flaşör", $tesis_detay["uyari_ekipmanlari"] ?? []); ?>> Flaşör</div>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="font-weight-600">Söndürme Ekipmanları:</label>
                            <div class="cb-group">
                                <div class="cb-item"><input type="checkbox" name="sondurme_ekipmanlari[]" value="Otomatik" <?php echo getCheck("Otomatik", $tesis_detay["sondurme_ekipmanlari"] ?? []); ?>> Otomatik Söndürme</div>
                                <div class="cb-item"><input type="checkbox" name="sondurme_ekipmanlari[]" value="KKT" <?php echo getCheck("KKT", $tesis_detay["sondurme_ekipmanlari"] ?? []); ?>> KKT Özellikli Tüp</div>
                                <div class="cb-item"><input type="checkbox" name="sondurme_ekipmanlari[]" value="CO2" <?php echo getCheck("CO2", $tesis_detay["sondurme_ekipmanlari"] ?? []); ?>> CO2 Özellikli Tüp</div>
                                <div class="cb-item"><input type="checkbox" name="sondurme_ekipmanlari[]" value="Hidrant" <?php echo getCheck("Hidrant", $tesis_detay["sondurme_ekipmanlari"] ?? []); ?>> Hidrantlar</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2.2 Bina ile İlgili Tespit Edilen Bilgiler -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-green">
                            <i class="fa fa-building"></i>
                        </div>
                        <div>
                            <h5>2.2. Bina İle İlgili Tespit Edilen Bilgiler</h5>
                            <p>Binanın mimari ve tehlike sınıflandırması detayları</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label>Tesisatta Kapsamlı Değişiklik:</label>
                            <select name="tesisat_degisiklik" class="form-control select2" style="width:100%;">
                                <?php echo options(["Belirlenemedi", "Var", "Yok"], $bina_tespitleri["tesisat_degisiklik"] ?? ""); ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Önceki Periyodik Kontrol Etiketi Var Mı?:</label>
                            <select name="etiket_varmi" class="form-control select2" style="width:100%;">
                                <?php echo options(["Var", "Yok"], $bina_tespitleri["etiket_varmi"] ?? ""); ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Bina Tehlike Sınıfı:</label>
                            <select name="tehlike_sinifi" class="form-control select2" style="width:100%;">
                                <?php echo options(["Düşük Tehlike", "Orta Tehlike", "Yüksek Tehlike"], $bina_tespitleri["tehlike_sinifi"] ?? ""); ?>
                            </select>
                        </div>

                        <div class="form-field full-width">
                            <label class="font-weight-600">Bina Kullanma Sınıfı:</label>
                            <div class="cb-group" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px;">
                                <?php 
                                $bina_listesi = ["Konut", "Toplanma amaçlı bina", "Depolama amaçlı tesis", "Yüksek tehlikeli bina", "Karışık kullanım amaçlı bina", "Endüstriyel yapı", "Konaklama amaçlı bina", "Kurumsal bina", "Büro binası", "Ticari"];
                                foreach($bina_listesi as $bk){ 
                                    echo '<div class="cb-item"><input type="checkbox" name="bina_sinifi[]" value="'.htmlspecialchars($bk, ENT_QUOTES, 'UTF-8').'" '.getCheck($bk, $bina_tespitleri["bina_sinifi"] ?? []).'> '.htmlspecialchars($bk, ENT_QUOTES, 'UTF-8').'</div>'; 
                                } 
                                ?>
                            </div>
                        </div>

                        <div class="form-field">
                            <label>Tehlike Kategorisi:</label>
                            <select name="tehlike_kategorisi" class="form-control select2" style="width:100%;">
                                <?php echo options(["1", "2", "3", "4"], $bina_tespitleri["tehlike_kategorisi"] ?? ""); ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Bina Toplam Kullanım Alanı (m²):</label>
                            <input name="bina_alan" value="<?php echo htmlspecialchars($bina_tespitleri["alan"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="m²">
                        </div>
                        <div class="form-field">
                            <label>Kat Sayısı:</label>
                            <input name="bina_kat" value="<?php echo htmlspecialchars($bina_tespitleri["kat"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Kat">
                        </div>
                        <div class="form-field">
                            <label>Bina / Yapı Yüksekliği (m):</label>
                            <input name="bina_yukseklik" value="<?php echo htmlspecialchars($bina_tespitleri["yukseklik"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Metre">
                        </div>
                        <div class="form-field">
                            <label>Yapı Kullanım İzin Tarihi:</label>
                            <input name="bina_izin_tarihi" value="<?php echo htmlspecialchars($bina_tespitleri["izin_tarihi"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control date-picker" autocomplete="off">
                        </div>
                        <div class="form-field">
                            <label>Bölüm Sayısı:</label>
                            <input name="bina_bolum_sayisi" value="<?php echo htmlspecialchars($bina_tespitleri["bolum_sayisi"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Bölüm">
                        </div>
                        <div class="form-field full-width">
                            <label>Varsa Diğer Tespitler:</label>
                            <input name="bina_diger" value="<?php echo htmlspecialchars($bina_tespitleri["diger"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Diğer açıklamalar">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: TEST DEĞERLERİ -->
            <div class="tab-pane fade" id="p3" role="tabpanel" aria-labelledby="p3-tab">
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-purple">
                            <i class="fa fa-flask"></i>
                        </div>
                        <div>
                            <h5>3. Test Değerleri & Teknik Notlar</h5>
                            <p>Sistemde yapılan test sonuçları, gerilim, akım ve akü ölçüm değerleri</p>
                        </div>
                    </div>
                    <div class="form-field full-width">
                        <div class="html-editor">
                            <textarea name="test_degerleri" id="test_degerleri" class="textarea_editor form-control" style="height: 300px;"><?php echo htmlspecialchars($header_extra["test_degerleri"] ?? "", ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: ÖLÇÜM ALETLERİ BİLGİLERİ -->
            <div class="tab-pane fade" id="p4" role="tabpanel" aria-labelledby="p4-tab">
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-blue">
                            <i class="fa fa-tachometer"></i>
                        </div>
                        <div>
                            <h5>4. Ölçüm Aletleri Bilgileri</h5>
                            <p>Denetimde kullanılan cihazların seri no ve kalibrasyon bilgileri</p>
                        </div>
                    </div>

                    <div class="row">
                        <?php for($i=1; $i<=2; $i++){ $c = $olcum_cihazlari[$i-1] ?? []; ?>
                        <div class="col-md-6 mb-3">
                            <div class="p-3 border rounded" style="background:#f8fafc; border-color:#e2e8f0;">
                                <h6 class="font-weight-bold text-dark mb-3"><i class="fa fa-microchip text-primary mr-1"></i> Cihaz <?php echo $i; ?> Bilgileri</h6>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Cihaz Adı / Marka:</label>
                                    <input name="cihaz<?php echo $i; ?>_ad" value="<?php echo htmlspecialchars($c["ad"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm" placeholder="Örn: Multimetre / Kyoritsu">
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <label class="small font-weight-bold">Seri No:</label>
                                        <input name="cihaz<?php echo $i; ?>_seri" value="<?php echo htmlspecialchars($c["seri"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm" placeholder="Seri No">
                                    </div>
                                    <div class="col-6">
                                        <label class="small font-weight-bold">Kalibrasyon No:</label>
                                        <input name="cihaz<?php echo $i; ?>_kal_no" value="<?php echo htmlspecialchars($c["kal_no"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm" placeholder="Kalibrasyon No">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <label class="small font-weight-bold">Kalibrasyon Tarihi:</label>
                                        <input name="cihaz<?php echo $i; ?>_kal_tar" value="<?php echo htmlspecialchars($c["kal_tar"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm date-picker" autocomplete="off" placeholder="Tarih">
                                    </div>
                                    <div class="col-6">
                                        <label class="small font-weight-bold">Geçerlilik Tarihi:</label>
                                        <input name="cihaz<?php echo $i; ?>_gec_tar" value="<?php echo htmlspecialchars($c["gec_tar"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm date-picker" autocomplete="off" placeholder="Tarih">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- TAB 5: MUAYENE & ÜRÜNLER -->
            <div class="tab-pane fade" id="p5" role="tabpanel" aria-labelledby="p5-tab">
                <!-- 5.1 Gözle Muayeneler -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-green">
                            <i class="fa fa-check-square-o"></i>
                        </div>
                        <div>
                            <h5>5.1. Gözle Muayene Kontrolleri</h5>
                            <p>TSE CEN/TS 54-14 standardı kapsamındaki kontrol maddeleri</p>
                        </div>
                    </div>

                    <?php
                    $full_ins = [
                        "ÖN KONTROLLER" => [
                            "Yetkili ve eğitimli personel var mı?",
                            "Acil durum anons sistemi mevcudiyeti",
                            "Yangın güvenliği sorumluları belirlenmiş mi?",
                            "Bakım/servis kayıtları tutuluyor mu?",
                            "Yangın alarm panelinin durumu",
                            "Sistem kütüğü belgesi var mı?"
                        ],
                        "YANGIN ALGILAMA VE TESİSAT" => [
                            "Kontrol paneli ve tekrarlayıcı paneller",
                            "Kullanma talimatı var mı?",
                            "Kontrol paneli izlenebilirliği",
                            "Akü durumu",
                            "Adresleme/Harita var mı?",
                            "Dedektör uygunluğu",
                            "Paralel ihbar lambaları",
                            "Uyarı cihazları yeterliliği",
                            "Kısa/Açık devre koruması",
                            "Kablo uygunluğu",
                            "Güvenlik devre ayrılması"
                        ],
                        "ACİL AYDINLATMA VE YÖNLENDİRME" => [
                            "Kaçış yolu armatürleri",
                            "Panel önü aydınlatma",
                            "Riskli alan aydınlatma",
                            "Çıkış yönlendirme",
                            "Kaçış yolu yönlendirme",
                            "Aydınlatma süreleri",
                            "Aydınlatma seviyeleri",
                            "Şebeke kesilme testi"
                        ],
                        "ENTEGRASYON VE DİĞER" => [
                            "Duman damperleri entegrasyonu",
                            "İklimlendirme entegrasyonu",
                            "Asansör entegrasyonu",
                            "Yangın kapıları entegrasyonu",
                            "Gaz kesme valfleri entegrasyonu",
                            "Yangın butonları yerleşimi",
                            "Kablo tavaları yalıtımı",
                            "Sistem test edilmesi (Sprey)",
                            "Arıza geçmişi kontrolü",
                            "Sıçrama riski",
                            "Genel temizlik ve bakım"
                        ]
                    ];
                    $m_idx = 1;
                    foreach($full_ins as $title => $items){
                        echo '<div class="group-title"><i class="fa fa-folder-open text-primary"></i> '.$title.'</div><div class="row">';
                        foreach($items as $label){
                            $checked = ($inspections["madde$m_idx"] ?? "UYGUN") == "UYGUN" ? "checked" : "";
                            echo '<div class="col-lg-6 col-md-12">
                                <div class="ins-row">
                                    <span class="ins-label">'.$m_idx.'. '.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</span>
                                    <label class="switch"><input type="checkbox" name="madde'.$m_idx.'" '.$checked.'><span class="slider"></span></label>
                                </div>
                            </div>';
                            $m_idx++;
                        }
                        echo '</div>';
                    }
                    ?>
                </div>

                <!-- 5.2 Ürün Listesi Tablosu -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-blue">
                            <i class="fa fa-list"></i>
                        </div>
                        <div>
                            <h5>5.2. Ekipman ve Dedektör Listesi</h5>
                            <p>Sistemdeki dedektör, buton ve saha elemanlarının listesi</p>
                        </div>
                    </div>

                    <div class="table-actions-toolbar">
                        <div class="table-actions-left">
                            <button type="button" class="btn btn-sm btn-primary" id="addRow">
                                <i class="fa fa-plus mr-1"></i> Yeni Ürün Ekle
                            </button>
                        </div>
                        <div>
                            <span class="badge badge-primary px-3 py-2 font-13 font-weight-bold" id="yasRowCountBadge">0 Ekipman</span>
                        </div>
                    </div>

                    <div class="yas-table-wrapper">
                        <div class="yas-table-responsive">
                            <table id="yasTable" class="table premium-table yas-table mb-0">
                                <thead>
                                    <tr class="main-head">
                                        <th style="width: 45px;"><i class="fa fa-cog"></i></th>
                                        <th style="min-width: 70px;">Kod</th>
                                        <th style="min-width: 140px;">Bölüm</th>
                                        <th style="min-width: 140px;">Ekipman/Adet</th>
                                        <th style="min-width: 60px;">Yer</th>
                                        <th style="min-width: 60px;">Erişim</th>
                                        <th style="min-width: 60px;">Montaj</th>
                                        <th style="min-width: 60px;">Test</th>
                                        <th style="min-width: 60px;">Sesli</th>
                                        <th style="min-width: 60px;">Işıklı</th>
                                        <th style="min-width: 60px;">Adres</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 6-9: SONUÇ VE ONAY -->
            <div class="tab-pane fade" id="p69" role="tabpanel" aria-labelledby="p69-tab">
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-orange">
                            <i class="fa fa-certificate"></i>
                        </div>
                        <div>
                            <h5>6-9. Kusur Açıklamaları, Sonuç Kanaat & Yetkili Bilgileri</h5>
                            <p>Rapor sonuç ve kanaatleri ile onaylayan mühendisin yetki bilgileri</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label for="kusur_aciklamalari">6. Kusur Açıklamaları:</label>
                            <div class="html-editor">
                                <textarea name="kusur_aciklamalari" id="kusur_aciklamalari" class="textarea_editor form-control" style="height: 120px;"><?php echo htmlspecialchars($header_extra["kusur_aciklamalari"] ?? "", ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>

                        <div class="form-field full-width">
                            <label for="notlar">7. Notlar:</label>
                            <div class="html-editor">
                                <textarea name="notlar" id="notlar" class="textarea_editor form-control" style="height: 120px;"><?php echo htmlspecialchars($header_extra["notlar"] ?? "", ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>

                        <div class="form-field full-width">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="sonuc_kanaat" class="mb-0 font-weight-bold">8. Sonuç ve Kanaat:</label>
                                <button type="button" class="btn-template" onclick="applyExcelTemplate()"><i class="fa fa-copy mr-1"></i> Standart Şablonu Uygula</button>
                            </div>
                            <div class="html-editor">
                                <textarea id="sonuc_kanaat" name="sonuc_kanaat" class="textarea_editor form-control" style="height: 200px;"><?php echo htmlspecialchars($header_extra["sonuc_kanaat"] ?? ($bakim["result_note"] ?? ""), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="group-title mt-4"><i class="fa fa-user-circle text-primary"></i> 9. YETKİLİ / MÜHENDİS BİLGİLERİ</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Yetkili Adı Soyadı:</label>
                            <input name="controller_peak" value="<?php echo htmlspecialchars($controller_peak["name"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Ad Soyad">
                        </div>
                        <div class="form-field">
                            <label>Diploma Numarası:</label>
                            <input name="controller_peak_diploma" value="<?php echo htmlspecialchars($controller_peak["diploma"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Diploma No">
                        </div>
                        <div class="form-field">
                            <label>EMO Sicil Numarası:</label>
                            <input name="controller_peak_emo" value="<?php echo htmlspecialchars($controller_peak["emo"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="EMO Sicil No">
                        </div>
                        <div class="form-field">
                            <label>Ekipnet Kayıt Numarası:</label>
                            <input name="controller_peak_ekipnet" value="<?php echo htmlspecialchars($controller_peak["ekipnet"] ?? "", ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Ekipnet No">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    var existingDedectors = <?php echo json_encode($dedectors ?? []); ?>;
    var rowCounter = 0;

    function updateRowCountBadge() {
        var count = $('#yasTable tbody tr').length;
        $('#yasRowCountBadge').text(count + ' Ekipman');
    }

    function addRowWithData(d) {
        rowCounter++;
        var yerChecked = (d.yer === undefined || d.yer === true || d.yer === "1" || d.yer === "on") ? 'checked' : '';
        var erisimChecked = (d.erisim === undefined || d.erisim === true || d.erisim === "1" || d.erisim === "on") ? 'checked' : '';
        var montajChecked = (d.montaj === undefined || d.montaj === true || d.montaj === "1" || d.montaj === "on") ? 'checked' : '';
        var testChecked = (d.test === undefined || d.test === true || d.test === "1" || d.test === "on") ? 'checked' : '';
        var sesliChecked = (d.sesli === undefined || d.sesli === true || d.sesli === "1" || d.sesli === "on") ? 'checked' : '';
        var isikliChecked = (d.isikli === undefined || d.isikli === true || d.isikli === "1" || d.isikli === "on") ? 'checked' : '';
        var adresChecked = (d.adresleme === undefined || d.adresleme === true || d.adresleme === "1" || d.adresleme === "on") ? 'checked' : '';

        var html = '<tr id="row' + rowCounter + '">' +
            '<td><button type="button" class="btn btn_remove" data-row="row' + rowCounter + '" title="Sil"><i class="fa fa-trash"></i></button></td>' +
            '<td><input type="text" name="p_kod[]" value="' + (d.kod || 'P' + rowCounter) + '" placeholder="P1" class="form-control form-control-sm"></td>' +
            '<td><input type="text" name="p_bolum[]" value="' + (d.bolum || '') + '" placeholder="Bölüm" class="form-control form-control-sm"></td>' +
            '<td><input type="text" name="p_ekipman[]" value="' + (d.ekipman || '') + '" placeholder="Ekipman/Adet" class="form-control form-control-sm"></td>' +
            '<td><label class="switch"><input type="checkbox" name="p_yer[]" ' + yerChecked + '><span class="slider"></span></label></td>' +
            '<td><label class="switch"><input type="checkbox" name="p_erisim[]" ' + erisimChecked + '><span class="slider"></span></label></td>' +
            '<td><label class="switch"><input type="checkbox" name="p_montaj[]" ' + montajChecked + '><span class="slider"></span></label></td>' +
            '<td><label class="switch"><input type="checkbox" name="p_test[]" ' + testChecked + '><span class="slider"></span></label></td>' +
            '<td><label class="switch"><input type="checkbox" name="p_sesli[]" ' + sesliChecked + '><span class="slider"></span></label></td>' +
            '<td><label class="switch"><input type="checkbox" name="p_isikli[]" ' + isikliChecked + '><span class="slider"></span></label></td>' +
            '<td><label class="switch"><input type="checkbox" name="p_adresleme[]" ' + adresChecked + '><span class="slider"></span></label></td>' +
            '</tr>';
        $('#yasTable tbody').append(html);
        updateRowCountBadge();
    }

    $(document).ready(function() {
        // Select2 Başlat
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                width: '100%',
                placeholder: 'Seçiniz...',
                allowClear: false
            });
        }

        // Mevcut verileri yükle veya ilk satırı ekle
        if (Array.isArray(existingDedectors) && existingDedectors.length > 0) {
            existingDedectors.forEach(function(d) {
                addRowWithData(d);
            });
        } else {
            addRowWithData({});
        }

        $("#addRow").click(function() {
            addRowWithData({});
        });

        $(document).on('click', '.btn_remove', function() {
            var rowId = $(this).data('row');
            $('#' + rowId).remove();
            updateRowCountBadge();
        });

        initEditors();
        $('button[data-toggle="pill"], a[data-toggle="pill"]').on('shown.bs.tab', function (e) { 
            initEditors(); 
        });
    });

    function previewReport() { 
        syncEditors();
        collectEquipmentData();
        var oldTarget = $('#myForm').attr('target'); 
        var oldAction = $('#myForm').attr('action'); 
        $('#myForm').attr('target', '_blank'); 
        $('#myForm').attr('action', 'index.php?p=reports/yas/report-view-yas&preview=1'); 
        $('#myForm').submit(); 
        $('#myForm').attr('target', oldTarget ? oldTarget : ''); 
        $('#myForm').attr('action', oldAction ? oldAction : ''); 
    }
    
    function syncEditors() { 
        if (typeof $.fn.wysihtml5 !== 'undefined') { 
            $('.textarea_editor').each(function() { 
                var editor = $(this).data("wysihtml5"); 
                if(editor && editor.editor) { 
                    $(this).val(editor.editor.getValue()); 
                } 
            }); 
        } 
    }
    
    function collectEquipmentData() {
        var equipment = [];
        $('#yasTable tbody tr').each(function() {
            var row = $(this);
            equipment.push({
                kod: row.find('input[name="p_kod[]"]').val() || '',
                bolum: row.find('input[name="p_bolum[]"]').val() || '',
                ekipman: row.find('input[name="p_ekipman[]"]').val() || '',
                yer: row.find('input[name="p_yer[]"]').is(':checked') ? 1 : 0,
                erisim: row.find('input[name="p_erisim[]"]').is(':checked') ? 1 : 0,
                montaj: row.find('input[name="p_montaj[]"]').is(':checked') ? 1 : 0,
                test: row.find('input[name="p_test[]"]').is(':checked') ? 1 : 0,
                sesli: row.find('input[name="p_sesli[]"]').is(':checked') ? 1 : 0,
                isikli: row.find('input[name="p_isikli[]"]').is(':checked') ? 1 : 0,
                adresleme: row.find('input[name="p_adresleme[]"]').is(':checked') ? 1 : 0
            });
        });
        $('#equipment_data_json').val(JSON.stringify(equipment));
    }
    
    $('#myForm').on('submit', function(e) { 
        syncEditors(); 
        collectEquipmentData(); 
        var isValid = true;
        if ($('#customer').val() === "" || $('#customer').val() === null) {
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Firma Seçimi Zorunludur!',
                    text: 'Lütfen listeden bir firma seçiniz.'
                });
            } else {
                alert("Lütfen firma seçimini yapınız!");
            }
            isValid = false;
            return false;
        }
        if ($('input[name="report_number"]').val() === "") {
            alert("Rapor numarası boş bırakılamaz!");
            isValid = false;
            return false;
        }
        if (isValid) {
            $("#submitButton").attr("disabled", true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');
        }
    });

    function initEditors() { 
        if (typeof $.fn.wysihtml5 !== 'undefined') { 
            $('.textarea_editor').each(function() { 
                if (!$(this).data("wysihtml5")) { 
                    $(this).wysihtml5({ 
                        "font-styles": true, 
                        "emphasis": true, 
                        "lists": true, 
                        "html": false, 
                        "link": true, 
                        "image": false, 
                        "color": false 
                    }); 
                } 
            }); 
        } 
    }

    function applyExcelTemplate() { 
        var template = "<b>Periyodik kontrol tarihi itibariyle TS CEN/TS 54-14 standardı ve Binaların Yangından Korunması Hakkında Yönetmelik hükümleri doğrultusunda incelenen Yangın Algılama ve Uyarı Sisteminin çalışır ve uygun durumda olduğu tespit edilmiştir. 1 (BİR) YIL süreyle kullanıma uygundur.</b>"; 
        var editorObj = $('#sonuc_kanaat').data("wysihtml5"); 
        if(editorObj && editorObj.editor) { 
            editorObj.editor.setValue(template); 
        } else {
            $('#sonuc_kanaat').val(template);
        }
    }
</script>
