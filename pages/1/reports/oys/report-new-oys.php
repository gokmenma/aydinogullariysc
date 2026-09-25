<?php
// Rapor Ekleme Yetkisi
permcontrol("reportadd");

$getNumber = setNumber("oys");
$getNumber = sprintf("%04d", $getNumber);
$new_report_number = "OYS" . $getNumber;
$type = 5;

if ($_POST) {
    // Eğer dedektör / ekipman varsa kayıt yapar
    $cinsi = $_POST["cinsi"] ?? [];
    $customer_id = (int)($_POST["customer"] ?? 0);

    if (empty($customer_id)) {
        header("Location: index.php?p=reports/oys/report-new-oys&st=empties");
        exit;
    }

    if (!empty($cinsi) && is_array($cinsi)) {
        $report_number = trim($_POST["report_number"] ?? $new_report_number);
        $isemrino = trim($_POST["isemrino"] ?? '');
        $control_date = trim($_POST["control_date"] ?? date('d.m.Y'));
        $next_control_date = trim($_POST["next_control_date"] ?? date('d.m.Y', strtotime('+1 year')));
        $controller_id = (int)($_POST["controller"] ?? 0);
        $creator = sesset("id");
        $regDate = date("Y-m-d H:i:s");

        $bulundugu_bolge = $_POST["bulundugu_bolge"] ?? [];
        $cevre_kontrolu = $_POST["cevre_kontrolu"] ?? [];
        $dis_muhafaza = $_POST["dis_muhafaza"] ?? [];
        $calisabilirlik_testi = $_POST["calisabilirlik_testi"] ?? [];
        $dedektor_sayisi = count($cinsi);

        $data = [];
        $controller_peak_info = [
            "name" => $_POST["controller_peak"] ?? "",
            "title" => $_POST["controller_peak_title"] ?? "",
            "diploma" => $_POST["controller_peak_diploma"] ?? "",
            "emo" => $_POST["controller_peak_emo"] ?? "",
            "ekipnet" => $_POST["controller_peak_ekipnet"] ?? ""
        ];

        $jsonDataControllerPeak = json_encode($controller_peak_info, JSON_UNESCAPED_UNICODE);

        // 1'den 12'ye kadar olan maddeleri diziye ekle
        for ($i = 1; $i <= 12; $i++) {
            $data["oysmadde$i"] = $_POST["oysmadde$i"] ?? "1";
        }
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

        $bakim = [
            "bakim1" => $_POST["is_control"] ?? "1",
            "bakim2" => $_POST["is_report"] ?? "1",
            "bakim3" => $_POST["last_control_date"] ?? "",
            "sondurme_sinifi" => $_POST["sondurme_sinifi"] ?? "",
            "sondurme_cesidi" => $_POST["sondurme_cesidi"] ?? "",
            "sonuc_kanaat" => $_POST["sonuc_kanaat"] ?? ""
        ];

        $jsonDataBakim = json_encode($bakim, JSON_UNESCAPED_UNICODE);

        try {
            $query = $ac->prepare("INSERT INTO reports SET 
                report_number = ?, 
                isemrino = ?,
                report_type = ?, 
                customer_id = ?, 
                control_date = ?, 
                next_control_date = ?, 
                controller_id = ?, 
                oys_general_matters = ?,
                bakim_bilgileri = ?,
                controller_peak_info = ?,
                creator = ?, 
                create_time = ?");
            $query->execute([
                $report_number,
                $isemrino,
                $type,
                $customer_id,
                $control_date,
                $next_control_date,
                $controller_id,
                $jsonData,
                $jsonDataBakim,
                $jsonDataControllerPeak,
                $creator,
                $regDate
            ]);
            $lastid = $ac->lastInsertId();

            // DEDEKTÖR / EKİPMAN BİLGİLERİ KAYDI
            $insq = $ac->prepare("INSERT INTO report_contents SET 
                report_id = ?, 
                algilama_cinsi = ?, 
                bulundugu_bolge = ?, 
                cevre_kontrolu = ?, 
                dis_muhafaza = ?, 
                calisabilirlik_testi = ?");

            for ($i = 0; $i < $dedektor_sayisi; $i++) {
                if (empty($cinsi[$i]) && empty($bulundugu_bolge[$i])) {
                    continue;
                }
                $insq->execute([
                    $lastid, 
                    $cinsi[$i] ?? '', 
                    $bulundugu_bolge[$i] ?? '', 
                    $cevre_kontrolu[$i] ?? '1', 
                    $dis_muhafaza[$i] ?? '1', 
                    $calisabilirlik_testi[$i] ?? '1'
                ]);
            }

            // EKLER SAYFASI KAYIT BİLGİLERİ
            if (isset($_FILES["report_attach"]) && is_array($_FILES["report_attach"]["name"])) {
                $dosyaSayisi = count($_FILES["report_attach"]["name"]);
                $dosya_aciklama = $_POST["attach_description"] ?? [];
                $upload_dir = "files/reports/";

                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0775, true);
                }

                for ($i = 0; $i < $dosyaSayisi; $i++) {
                    if (isset($_FILES["report_attach"]["error"][$i]) && $_FILES["report_attach"]["error"][$i] == UPLOAD_ERR_OK) {
                        $orig_name = basename($_FILES["report_attach"]["name"][$i]);
                        $clean_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig_name);
                        $rast1 = uniqid();
                        $new_filename = $rast1 . "_" . $clean_name;
                        $target_file = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES["report_attach"]["tmp_name"][$i], $target_file)) {
                            $ins = $ac->prepare("INSERT INTO files SET
                                report_id = ?,
                                fileDescription = ?,
                                filename = ?,
                                size = ?,
                                creativer = ?");
                            $ins->execute([
                                $lastid, 
                                $dosya_aciklama[$i] ?? '', 
                                $new_filename, 
                                $_FILES["report_attach"]["size"][$i] ?? 0, 
                                sesset("id")
                            ]);
                        }
                    }
                }
            }

            $getNumber = (int)$getNumber + 1;
            $upquery = $ac->prepare("UPDATE define_numbers SET oys = ?");
            $upquery->execute([$getNumber]);

            audit_log("create", "report", "Otomatik Yangın Söndürme Raporu oluşturuldu: " . $report_number, "reports", $lastid);

            header("Location: index.php?p=reports/oys/report-new-oys&st=newsuccess&last_id=" . $lastid);
            exit;

        } catch (PDOException $e) {
            error_log("OYS Raporu Kayıt Hatası: " . $e->getMessage());
            header("Location: index.php?p=reports/oys/report-new-oys&st=error");
            exit;
        }
    } else {
        header("Location: index.php?p=reports/oys/report-new-oys&st=nodevice");
        exit;
    }
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
            text: 'Otomatik Yangın Söndürme Sistemi Kontrol Raporu başarıyla oluşturuldu.',
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
                window.history.replaceState({}, document.title, 'index.php?p=reports/oys/report-new-oys');
            }
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
            window.history.replaceState({}, document.title, 'index.php?p=reports/oys/report-new-oys');
        });
    }
});
</script>
<?php elseif ($st === "nodevice"): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: 'Ekipman Ekleyiniz!',
            text: 'Liste sekmesinde en az bir adet ekipman/cihaz satırı eklemeniz gerekmektedir.',
            icon: 'warning',
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#f59e0b'
        }).then(function() {
            window.history.replaceState({}, document.title, 'index.php?p=reports/oys/report-new-oys');
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
            window.history.replaceState({}, document.title, 'index.php?p=reports/oys/report-new-oys');
        });
    }
});
</script>
<?php endif; ?>

<style>
    .oys-report-wrapper {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        padding-bottom: 40px;
    }

    /* Custom Nav Pills */
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
        font-size: 13px;
        padding: 8px 18px;
        border-radius: 8px;
        border: none;
        transition: all 0.2s;
        background: transparent;
    }

    .custom-report-pills .nav-link:hover {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.6);
    }

    .custom-report-pills .nav-link.active {
        color: #0284c7;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    }

    /* Minimal Table Container Matching premium-theme */
    .oys-table-wrapper {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .oys-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .premium-table.oys-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 0;
        margin-bottom: 0;
        border: none !important;
    }

    .premium-table.oys-table thead th {
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

    .premium-table.oys-table thead tr.main-head th {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
    }

    .premium-table.oys-table td {
        padding: 4px 4px !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f8fafc;
        background: #ffffff;
    }

    .premium-table.oys-table tbody tr:hover td {
        background: #f8fafc;
    }

    .premium-table.oys-table .form-control {
        height: 30px !important;
        padding: 2px 6px !important;
        font-size: 12px !important;
        border-radius: 6px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff;
        transition: all 0.15s ease-in-out;
    }

    .premium-table.oys-table .btn-delete-row {
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

    .premium-table.oys-table .btn-delete-row:hover {
        background-color: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .premium-table.oys-table select.custom-select-status {
        font-size: 11px !important;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
        padding: 2px 4px !important;
    }

    .matter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s;
    }

    .matter-card:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .matter-number {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: #e0f2fe;
        color: #0284c7;
        font-weight: 700;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .matter-text {
        flex: 1;
        font-size: 12px;
        color: #334155;
        line-height: 1.4;
    }

    .matter-select {
        width: 140px;
        flex-shrink: 0;
    }

    .table-actions-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }

    .table-actions-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

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
    .dark-mode .oys-table-wrapper { background: #1e293b; border-color: #334155; }
    .dark-mode .premium-table.oys-table thead th { background: #0f172a !important; color: #cbd5e1; border-color: #334155; }
    .dark-mode .premium-table.oys-table thead tr.main-head th { background: #1e293b !important; color: #f8fafc; border-color: #334155; }
    .dark-mode .premium-table.oys-table td { background: #1e293b; border-color: #334155; color: #e2e8f0; }
    .dark-mode .premium-table.oys-table tbody tr:hover td { background: #283548; }
    .dark-mode .premium-table.oys-table .form-control { background: #0f172a !important; border-color: #334155 !important; color: #f8fafc !important; }
    .dark-mode .custom-report-pills { background: #0f172a; }
    .dark-mode .custom-report-pills .nav-link { color: #94a3b8; }
    .dark-mode .custom-report-pills .nav-link.active { color: #38bdf8; background: #1e293b; }
    .dark-mode .matter-card { background: #1e293b; border-color: #334155; }
    .dark-mode .matter-card:hover { background: #283548; }
    .dark-mode .matter-text { color: #cbd5e1; }
    .dark-mode .matter-number { background: #0369a1; color: #e0f2fe; }
</style>

<form enctype="multipart/form-data" id="myForm" method="POST">
    <div class="oys-report-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                        <i class="fa fa-shower"></i>
                    </div>
                    <div class="header-title">
                        <h4>Otomatik Yangın Söndürme Sistemi Kontrol Raporu</h4>
                        <span class="header-number-badge">
                            <i class="fa fa-tag"></i> Rapor No: <?php echo htmlspecialchars($new_report_number, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=reports/reports" class="btn-header btn-header-list">
                        <i class="fa fa-list"></i> Listeye Dön
                    </a>
                    <button type="submit" id="submitButton" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Raporu Kaydet
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Pills Navigation -->
        <ul class="nav custom-report-pills animate-fade-in" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-home-tab" data-toggle="pill" data-target="#pills-home" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">
                    <i class="fa fa-info-circle mr-1"></i> Giriş Bilgileri & Maddeler
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-content-tab" data-toggle="pill" data-target="#pills-content" data-bs-toggle="pill" data-bs-target="#pills-content" type="button" role="tab" aria-controls="pills-content" aria-selected="false">
                    <i class="fa fa-list-alt mr-1"></i> Ekipman Listesi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-result-tab" data-toggle="pill" data-target="#pills-result" data-bs-toggle="pill" data-bs-target="#pills-result" type="button" role="tab" aria-controls="pills-result" aria-selected="false">
                    <i class="fa fa-certificate mr-1"></i> Sonuç & Onay
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-attach-tab" data-toggle="pill" data-target="#pills-attach" data-bs-toggle="pill" data-bs-target="#pills-attach" type="button" role="tab" aria-controls="pills-attach" aria-selected="false">
                    <i class="fa fa-paperclip mr-1"></i> Ek Belgeler
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- TAB 1: GİRİŞ BİLGİLERİ & MADDELER -->
            <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                <!-- Kart 1: Giriş Bilgileri -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-blue">
                            <i class="fa fa-building-o"></i>
                        </div>
                        <div>
                            <h5>Genel Giriş Bilgileri</h5>
                            <p>Rapor numarası, müşteri seçimi, denetçi ve kontrol tarihleri</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <!-- Rapor No -->
                        <div class="form-field">
                            <label for="report_number"><font color="red">(*)</font> Rapor No:</label>
                            <input required name="report_number" id="report_number" type="text" readonly value="<?php echo htmlspecialchars($new_report_number, ENT_QUOTES, 'UTF-8'); ?>" class="form-control font-weight-bold bg-light" placeholder="Rapor No">
                        </div>

                        <!-- Firma Adı -->
                        <div class="form-field">
                            <label for="customer"><font color="red">(*)</font> Firma:</label>
                            <select required name="customer" id="customer" class="form-control select2" style="width: 100%;">
                                <option disabled selected value="">Firma Seçiniz</option>
                                <?php
                                $compquery = $ac->prepare("SELECT id, company FROM customers WHERE deleted_at IS NULL ORDER BY company ASC");
                                $compquery->execute();
                                while ($company = $compquery->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                    <option value="<?php echo $company["id"]; ?>">
                                        <?php echo htmlspecialchars($company["company"], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- İş Emri No -->
                        <div class="form-field">
                            <label for="isemrino"><font color="red">(*)</font> İş Emri No:</label>
                            <select name="isemrino" id="isemrino" class="form-control select2" style="width: 100%;">
                                <option value="">İş Emri Seçiniz</option>
                                <?php
                                $servicequery = $ac->prepare("SELECT id FROM projects ORDER BY id DESC");
                                $servicequery->execute();
                                while ($isemri = $servicequery->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                    <option value="<?php echo "SN" . $isemri["id"]; ?>">
                                        <?php echo "SN" . $isemri["id"]; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Kontrolü Yapan Mühendis -->
                        <div class="form-field">
                            <label for="controller"><font color="red">(*)</font> Kontrolü Yapan Mühendis:</label>
                            <select required name="controller" id="controller" class="form-control select2" style="width: 100%;">
                                <option disabled selected value="">Mühendis Seçiniz</option>
                                <?php
                                $userquery = $ac->prepare("SELECT id, username, meslek FROM users ORDER BY username ASC");
                                $userquery->execute();
                                while ($usr = $userquery->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                    <option value="<?php echo $usr["id"]; ?>">
                                        <?php echo htmlspecialchars($usr["username"] . (!empty($usr["meslek"]) ? " (" . $usr["meslek"] . ")" : ""), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Kontrol Tarihi -->
                        <div class="form-field">
                            <label for="control_date"><font color="red">(*)</font> Kontrol Tarihi:</label>
                            <input required type="text" autocomplete="off" name="control_date" id="control_date" class="form-control date-picker" value="<?php echo date('d.m.Y'); ?>" placeholder="Kontrol Tarihi">
                        </div>

                        <!-- Sonraki Kontrol Tarihi -->
                        <div class="form-field">
                            <label for="next_control_date"><font color="red">(*)</font> Sonraki Kontrol Tarihi:</label>
                            <input required type="text" autocomplete="off" name="next_control_date" id="next_control_date" class="form-control date-picker" value="<?php echo date('d.m.Y', strtotime('+1 year')); ?>" placeholder="Sonraki Kontrol Tarihi">
                        </div>
                    </div>
                </div>

                <!-- Kart 2: Sistem Sınıfı & Önceki Bakım Bilgileri -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-orange">
                            <i class="fa fa-cogs"></i>
                        </div>
                        <div>
                            <h5>Yangın Söndürme Sistemi Sınıfı & Bakım Bilgileri</h5>
                            <p>Söndürme sistemi kategorisi ve geçmiş kontrol verileri</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="sondurme_sinifi">Otomatik Yangın Söndürme Sistemi Sınıfı:</label>
                            <select name="sondurme_sinifi" id="sondurme_sinifi" class="form-control select2" style="width: 100%;">
                                <option value="KÖPÜKLÜ SİSTEM">KÖPÜKLÜ SİSTEM</option>
                                <option value="GAZLI SİSTEM">GAZLI SİSTEM</option>
                                <option value="TOZLU SİSTEM">TOZLU SİSTEM</option>
                                <option value="SULU SİSTEM (SPRİNKLER)">SULU SİSTEM (SPRİNKLER)</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="sondurme_cesidi">Otomatik Yangın Söndürme Sistemi Çeşidi:</label>
                            <input type="text" name="sondurme_cesidi" id="sondurme_cesidi" class="form-control" placeholder="Örn: FM200, Novec 1230, CO2, Köpüklü vb.">
                        </div>

                        <div class="form-field">
                            <label for="is_control">Daha Önce Kontrolü Yapılmış Mı?:</label>
                            <select name="is_control" id="is_control" class="form-control select2" style="width: 100%;">
                                <option value="1" selected>EVET</option>
                                <option value="0">HAYIR</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="is_report">Önceki Bakım Tutanakları Mevcut Mu?:</label>
                            <select name="is_report" id="is_report" class="form-control select2" style="width: 100%;">
                                <option value="1" selected>VAR</option>
                                <option value="0">YOK</option>
                            </select>
                        </div>

                        <div class="form-field full-width">
                            <label for="last_control_date">En Son Yapılan Kontrol Tarihi:</label>
                            <input type="text" name="last_control_date" id="last_control_date" class="form-control date-picker" autocomplete="off" placeholder="Tarih seçiniz">
                        </div>
                    </div>
                </div>

                <!-- Kart 3: Genel Kontroller (Maddeler) -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-green">
                            <i class="fa fa-check-square-o"></i>
                        </div>
                        <div>
                            <h5>Genel Kontrol Maddeleri</h5>
                            <p>Otomatik yangın söndürme tesisat ve sistem genel muayenesi</p>
                        </div>
                    </div>

                    <?php
                    $sql = $ac->prepare("SELECT oys_genel as soru FROM report_questions WHERE oys_genel IS NOT NULL AND oys_genel != '' ORDER BY id ASC");
                    $sql->execute();
                    $questions = $sql->fetchAll(PDO::FETCH_ASSOC);
                    $totalQuestions = count($questions);
                    $half = ceil($totalQuestions / 2);
                    ?>

                    <div class="row">
                        <div class="col-lg-6 col-md-12">
                            <?php for ($i = 0; $i < $half; $i++) { 
                                $num = $i + 1;
                                $soru = $questions[$i]["soru"] ?? "";
                            ?>
                                <div class="matter-card">
                                    <div class="matter-number"><?php echo $num; ?></div>
                                    <div class="matter-text"><?php echo htmlspecialchars($soru, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="matter-select">
                                        <select name="oysmadde<?php echo $num; ?>" class="form-control custom-select-status">
                                            <option value="1" selected>UYGUN</option>
                                            <option value="0">UYGUN DEĞİL</option>
                                        </select>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="col-lg-6 col-md-12">
                            <?php for ($i = $half; $i < $totalQuestions; $i++) { 
                                $num = $i + 1;
                                $soru = $questions[$i]["soru"] ?? "";
                            ?>
                                <div class="matter-card">
                                    <div class="matter-number"><?php echo $num; ?></div>
                                    <div class="matter-text"><?php echo htmlspecialchars($soru, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="matter-select">
                                        <select name="oysmadde<?php echo $num; ?>" class="form-control custom-select-status">
                                            <option value="1" selected>UYGUN</option>
                                            <option value="0">UYGUN DEĞİL</option>
                                        </select>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: EKİPMAN LİSTESİ -->
            <div class="tab-pane fade" id="pills-content" role="tabpanel" aria-labelledby="pills-content-tab">
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-blue">
                            <i class="fa fa-list"></i>
                        </div>
                        <div>
                            <h5>Periyodik Kontrol Ekipman & Cihaz Listesi</h5>
                            <p>Söndürme sistemine bağlı nozul, vana, dedektör ve ekipmanların durumları</p>
                        </div>
                    </div>

                    <div class="table-actions-toolbar">
                        <div class="table-actions-left">
                            <button type="button" class="btn btn-sm btn-primary" id="addRow">
                                <i class="fa fa-plus mr-1"></i> Yeni Satır Ekle
                            </button>
                        </div>
                        <div>
                            <span class="badge badge-primary px-3 py-2 font-13 font-weight-bold" id="oysRowCountBadge">1 Ekipman</span>
                        </div>
                    </div>

                    <div class="oys-table-wrapper">
                        <div class="oys-table-responsive">
                            <table id="yasTable" class="table premium-table oys-table mb-0">
                                <thead>
                                    <tr class="main-head text-center">
                                        <th style="width: 45px;"><i class="fa fa-cog"></i></th>
                                        <th style="min-width: 180px;">Sistem Ekipman Cinsi</th>
                                        <th style="min-width: 180px;">Bulunduğu Bölge</th>
                                        <th style="min-width: 130px;">Çevre Kontrolü</th>
                                        <th style="min-width: 130px;">Dış Muhafaza</th>
                                        <th style="min-width: 140px;">Çalışabilirlik Testi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php include "report-row-oys.php"; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: SONUÇ VE ONAY -->
            <div class="tab-pane fade" id="pills-result" role="tabpanel" aria-labelledby="pills-result-tab">
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-orange">
                            <i class="fa fa-certificate"></i>
                        </div>
                        <div>
                            <h5>Muayeneyi Yapan Yetkili & Onay Bilgileri</h5>
                            <p>Denetçi mühendis kimlik/sicil bilgileri ve periyodik kontrol onay parametreleri</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="controller_peak">Muayeneyi Yapan Mühendis:</label>
                            <input type="text" name="controller_peak" id="controller_peak" class="form-control" placeholder="Mühendis Adı Soyadı">
                        </div>

                        <div class="form-field">
                            <label for="controller_peak_title">Unvanı:</label>
                            <input type="text" name="controller_peak_title" id="controller_peak_title" class="form-control" placeholder="Örn: Makine Mühendisi">
                        </div>

                        <div class="form-field">
                            <label for="controller_peak_diploma">Diploma No:</label>
                            <input type="text" name="controller_peak_diploma" id="controller_peak_diploma" class="form-control" placeholder="Diploma No">
                        </div>

                        <div class="form-field">
                            <label for="controller_peak_emo">Oda / EMO / MMO Sicil No:</label>
                            <input type="text" name="controller_peak_emo" id="controller_peak_emo" class="form-control" placeholder="Oda Sicil No">
                        </div>

                        <div class="form-field full-width">
                            <label for="controller_peak_ekipnet">Ekipnet Kayıt No:</label>
                            <input type="text" name="controller_peak_ekipnet" id="controller_peak_ekipnet" class="form-control" placeholder="Ekipnet No">
                        </div>
                    </div>
                </div>

                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-green">
                            <i class="fa fa-file-text-o"></i>
                        </div>
                        <div>
                            <h5>Sonuç ve Kanaat & Standart Kriterleri</h5>
                            <p>Yönetmelik referansları ve sonuç kanaat metni</p>
                        </div>
                    </div>

                    <div class="form-field full-width">
                        <label class="font-weight-600 mb-1">1.7.7. Sonuç Ve Kanaat Metni:</label>
                        <div class="html-editor">
                            <textarea name="sonuc_kanaat" id="sonuc_kanaat" class="textarea_editor form-control" style="height: 140px;">SONUÇ VE KANAAT : 6331 Sayılı Kanun gereği çıkartılan İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik şartları Yönetmeliğine ve BİNALARIN YANGINDAN KORUNMASI HAKKINDA YÖNETMELİK kriterlerine, ayrıca TS ISO 15004 ve TS EN 15004 standartlarında belirtilen kriterlere göre yapılan kontroller sonucunda OTOMATİK YANGIN SÖNDÜRME SİSTEMİ 1 YIL SÜREYLE KULLANIMA UYGUNDUR. Uygunluğunun devamlılığından işveren sorumludur.</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: EK BELGELER -->
            <div class="tab-pane fade" id="pills-attach" role="tabpanel" aria-labelledby="pills-attach-tab">
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-purple">
                            <i class="fa fa-paperclip"></i>
                        </div>
                        <div>
                            <h5>Rapor Ek Dosyaları & Belgeleri</h5>
                            <p>Fotoğraflar, sertifikalar, proje çizimleri ve ek belgeler</p>
                        </div>
                    </div>

                    <div class="table-actions-toolbar">
                        <div class="table-actions-left">
                            <button type="button" class="btn btn-sm btn-primary" id="addRowfile">
                                <i class="fa fa-plus mr-1"></i> Yeni Dosya Ekle
                            </button>
                        </div>
                    </div>

                    <div class="oys-table-wrapper">
                        <div class="oys-table-responsive">
                            <table id="yasTablefile" class="table premium-table oys-table mb-0">
                                <thead>
                                    <tr class="main-head text-center">
                                        <th style="width: 45px;"><i class="fa fa-cog"></i></th>
                                        <th style="min-width: 250px;">Açıklama</th>
                                        <th style="min-width: 200px;">Dosya</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php include "report-row-oys-attach.php"; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    function updateOysRowCount() {
        var count = $('#yasTable tbody tr').length;
        $('#oysRowCountBadge').text(count + ' Ekipman');
    }

    function syncOysEditors() {
        if (typeof $.fn.wysihtml5 !== 'undefined') {
            $('.textarea_editor').each(function() {
                var editor = $(this).data("wysihtml5");
                if (editor && editor.editor) {
                    $(this).val(editor.editor.getValue());
                }
            });
        }
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

        // WYSIWYG
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

        $('button[data-toggle="pill"], a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
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
        });

        // Yeni Ekipman Satırı
        $("#addRow").click(function() {
            var html = '<tr>' +
                '<td class="text-center"><button type="button" class="btn btn-delete-row sil" title="Satırı Sil"><i class="fa fa-trash"></i></button></td>' +
                '<td><input required type="text" class="form-control" name="cinsi[]" placeholder="Ekipman Cinsi"></td>' +
                '<td><input required type="text" class="form-control" name="bulundugu_bolge[]" placeholder="Bulunduğu Bölge"></td>' +
                '<td><select name="cevre_kontrolu[]" class="form-control custom-select-status"><option value="1" selected>UYGUN</option><option value="0">UYGUN DEĞİL</option></select></td>' +
                '<td><select name="dis_muhafaza[]" class="form-control custom-select-status"><option value="1" selected>UYGUN</option><option value="0">UYGUN DEĞİL</option></select></td>' +
                '<td><select name="calisabilirlik_testi[]" class="form-control custom-select-status"><option value="1" selected>UYGUN</option><option value="0">UYGUN DEĞİL</option></select></td>' +
                '</tr>';
            $('#yasTable tbody').append(html);
            updateOysRowCount();
        });

        // Ekipman Satırı Sil
        $(document).on('click', '#yasTable .sil', function() {
            if ($('#yasTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                $(this).closest('tr').find('input').val('');
            }
            updateOysRowCount();
        });

        // Yeni Dosya Satırı
        $("#addRowfile").click(function() {
            var html = '<tr>' +
                '<td class="text-center"><button type="button" class="btn btn-delete-row sil" title="Dosyayı Kaldır"><i class="fa fa-trash"></i></button></td>' +
                '<td><input required type="text" class="form-control" name="attach_description[]" placeholder="Dosya / Belge Açıklaması"></td>' +
                '<td><input required type="file" class="form-control form-control-sm" name="report_attach[]"></td>' +
                '</tr>';
            $('#yasTablefile tbody').append(html);
        });

        // Dosya Satırı Sil
        $(document).on('click', '#yasTablefile .sil', function() {
            if ($('#yasTablefile tbody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                $(this).closest('tr').find('input').val('');
            }
        });

        updateOysRowCount();
    });

    $('#myForm').on('submit', function(e) {
        syncOysEditors();
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
            return false;
        }
        $("#submitButton").attr("disabled", true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');
    });
</script>