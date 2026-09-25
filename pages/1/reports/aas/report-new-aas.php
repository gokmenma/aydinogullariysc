<?php
// Rapor Ekleme Yetkisi
permcontrol("reportadd");

$getNumber = setNumber("aas");
$getNumber = sprintf("%04d", $getNumber);
$new_report_number = "AAS" . $getNumber;
$type = 6;

if ($_POST) {
    $cinsi = $_POST["cinsi"] ?? [];
    $customer_id = (int)($_POST["customer"] ?? 0);

    if (empty($customer_id)) {
        header("Location: index.php?p=reports/aas/report-new-aas&st=empties");
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
        $markasi = $_POST["markasi"] ?? [];
        $kontroltarihi = $_POST["kontroltarihi"] ?? [];
        $problems = $_POST["problems"] ?? [];
        $islemler = $_POST["islemler"] ?? [];
        $cihaz_sayisi = count($cinsi);

        $data = [];
        for ($i = 1; $i <= 9; $i++) {
            $data["madde$i"] = $_POST["madde$i"] ?? "1";
        }
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

        $data2 = [];
        for ($i = 1; $i <= 10; $i++) {
            $data2["dedector$i"] = $_POST["dedector$i"] ?? "";
        }
        $jsonDataDedector = json_encode($data2, JSON_UNESCAPED_UNICODE);

        $bakim = [
            "bakim1" => $_POST["is_control"] ?? "1",
            "bakim2" => $_POST["is_report"] ?? "1",
            "bakim3" => $_POST["last_control_date"] ?? ""
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
                report_matters = ?,
                bakim_bilgileri = ?,
                dedektor_info = ?, 
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
                $jsonDataDedector,
                $creator,
                $regDate
            ]);
            $lastid = $ac->lastInsertId();

            // CİHAZ / ARMATÜR LİSTESİ KAYDI
            $insq = $ac->prepare("INSERT INTO report_contents SET 
                report_id = ?, 
                algilama_cinsi = ?, 
                bulundugu_bolge = ?, 
                cevre_kontrolu = ?, 
                dis_muhafaza = ?, 
                calisabilirlik_testi = ?");

            for ($i = 0; $i < $cihaz_sayisi; $i++) {
                if (empty($cinsi[$i]) && empty($bulundugu_bolge[$i])) {
                    continue;
                }
                $insq->execute([
                    $lastid, 
                    $cinsi[$i] ?? '', 
                    $bulundugu_bolge[$i] ?? '', 
                    $markasi[$i] ?? '', 
                    $problems[$i] ?? '1', 
                    $islemler[$i] ?? '1'
                ]);
            }

            $getNumber = (int)$getNumber + 1;
            $upquery = $ac->prepare("UPDATE define_numbers SET aas = ?");
            $upquery->execute([$getNumber]);

            audit_log("create", "report", "Acil Aydınlatma Raporu oluşturuldu: " . $report_number, "reports", $lastid);

            header("Location: index.php?p=reports/aas/report-new-aas&st=newsuccess&last_id=" . $lastid);
            exit;

        } catch (PDOException $e) {
            error_log("AAS Raporu Kayıt Hatası: " . $e->getMessage());
            header("Location: index.php?p=reports/aas/report-new-aas&st=error");
            exit;
        }
    } else {
        header("Location: index.php?p=reports/aas/report-new-aas&st=nodevice");
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
            text: 'Acil Aydınlatma ve Yönlendirme Sistemi Kontrol Raporu başarıyla oluşturuldu.',
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
                window.history.replaceState({}, document.title, 'index.php?p=reports/aas/report-new-aas');
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
            window.history.replaceState({}, document.title, 'index.php?p=reports/aas/report-new-aas');
        });
    }
});
</script>
<?php elseif ($st === "nodevice"): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: 'Cihaz Ekleyiniz!',
            text: 'Liste sekmesinde en az bir adet cihaz/armatür satırı eklemeniz gerekmektedir.',
            icon: 'warning',
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#f59e0b'
        }).then(function() {
            window.history.replaceState({}, document.title, 'index.php?p=reports/aas/report-new-aas');
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
            window.history.replaceState({}, document.title, 'index.php?p=reports/aas/report-new-aas');
        });
    }
});
</script>
<?php endif; ?>

<style>
    .aas-report-wrapper {
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
        color: #d97706;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    }

    /* Minimal Table Container Matching premium-theme */
    .aas-table-wrapper {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .aas-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .premium-table.aas-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 0;
        margin-bottom: 0;
        border: none !important;
    }

    .premium-table.aas-table thead th {
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

    .premium-table.aas-table thead tr.main-head th {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
    }

    .premium-table.aas-table td {
        padding: 4px 4px !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f8fafc;
        background: #ffffff;
    }

    .premium-table.aas-table tbody tr:hover td {
        background: #f8fafc;
    }

    .premium-table.aas-table .form-control {
        height: 30px !important;
        padding: 2px 6px !important;
        font-size: 12px !important;
        border-radius: 6px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff;
        transition: all 0.15s ease-in-out;
    }

    .premium-table.aas-table .btn-delete-row {
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

    .premium-table.aas-table .btn-delete-row:hover {
        background-color: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .premium-table.aas-table select.custom-select-status {
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
        background: #fef3c7;
        color: #d97706;
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
    .dark-mode .aas-table-wrapper { background: #1e293b; border-color: #334155; }
    .dark-mode .premium-table.aas-table thead th { background: #0f172a !important; color: #cbd5e1; border-color: #334155; }
    .dark-mode .premium-table.aas-table thead tr.main-head th { background: #1e293b !important; color: #f8fafc; border-color: #334155; }
    .dark-mode .premium-table.aas-table td { background: #1e293b; border-color: #334155; color: #e2e8f0; }
    .dark-mode .premium-table.aas-table tbody tr:hover td { background: #283548; }
    .dark-mode .premium-table.aas-table .form-control { background: #0f172a !important; border-color: #334155 !important; color: #f8fafc !important; }
    .dark-mode .custom-report-pills { background: #0f172a; }
    .dark-mode .custom-report-pills .nav-link { color: #94a3b8; }
    .dark-mode .custom-report-pills .nav-link.active { color: #fbbf24; background: #1e293b; }
    .dark-mode .matter-card { background: #1e293b; border-color: #334155; }
    .dark-mode .matter-card:hover { background: #283548; }
    .dark-mode .matter-text { color: #cbd5e1; }
    .dark-mode .matter-number { background: #78350f; color: #fef3c7; }
</style>

<form enctype="multipart/form-data" id="myForm" method="POST">
    <div class="aas-report-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                        <i class="fa fa-lightbulb-o"></i>
                    </div>
                    <div class="header-title">
                        <h4>Acil Aydınlatma ve Yönlendirme Kontrol Raporu</h4>
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
                    <i class="fa fa-list-alt mr-1"></i> Cihaz & Armatür Listesi
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- TAB 1: GİRİŞ BİLGİLERİ & MADDELER -->
            <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                <!-- Kart 1: Giriş Bilgileri -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-orange">
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

                <!-- Kart 2: Önceki Bakım Bilgileri -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-blue">
                            <i class="fa fa-history"></i>
                        </div>
                        <div>
                            <h5>Önceki Bakım & Kontrol Bilgileri</h5>
                            <p>Tesisatın geçmiş denetim ve bakım kayıtları</p>
                        </div>
                    </div>

                    <div class="form-grid">
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

                <!-- Kart 3: Kontrolü Yapılan Dedektör / Ekipman Parametreleri -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-purple">
                            <i class="fa fa-sliders"></i>
                        </div>
                        <div>
                            <h5>Kontrolü Yapılan Dedektör & Aydınlatma Parametreleri</h5>
                            <p>Sistem ekipman özellikleri ve kapasite değerleri</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <?php
                        $sql = $ac->prepare("SELECT aas_dedektor as soru FROM report_questions WHERE aas_dedektor IS NOT NULL AND aas_dedektor != '' ORDER BY id ASC");
                        $sql->execute();
                        $dedectors = $sql->fetchAll(PDO::FETCH_ASSOC);
                        $dedectors_count = count($dedectors);

                        for ($i = 0; $i < $dedectors_count; $i++) {
                            $dedector_name = "dedector" . ($i + 1);
                            $soru_text = $dedectors[$i]["soru"];
                        ?>
                            <div class="form-field">
                                <label for="<?php echo $dedector_name; ?>"><?php echo htmlspecialchars($soru_text, ENT_QUOTES, 'UTF-8'); ?>:</label>
                                <input type="text" autocomplete="off" name="<?php echo $dedector_name; ?>" id="<?php echo $dedector_name; ?>" class="form-control" placeholder="Değer giriniz">
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Kart 4: Genel Bilgiler & Kontrol Maddeleri -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-green">
                            <i class="fa fa-check-square-o"></i>
                        </div>
                        <div>
                            <h5>Genel Kontrol Maddeleri</h5>
                            <p>Acil aydınlatma ve yönlendirme armatürlerinin standart uygunluk kriterleri</p>
                        </div>
                    </div>

                    <?php
                    $sql = $ac->prepare("SELECT aas_genel as soru FROM report_questions WHERE aas_genel IS NOT NULL AND aas_genel != '' ORDER BY id ASC");
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
                                        <select name="madde<?php echo $num; ?>" class="form-control custom-select-status">
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
                                        <select name="madde<?php echo $num; ?>" class="form-control custom-select-status">
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

            <!-- TAB 2: CİHAZ / ARMATÜR LİSTESİ -->
            <div class="tab-pane fade" id="pills-content" role="tabpanel" aria-labelledby="pills-content-tab">
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-orange">
                            <i class="fa fa-list"></i>
                        </div>
                        <div>
                            <h5>Acil Aydınlatma & Yönlendirme Armatür Listesi</h5>
                            <p>Mahal bazında armatür kontrolü, test sonuçları ve işlem durumları</p>
                        </div>
                    </div>

                    <div class="table-actions-toolbar">
                        <div class="table-actions-left">
                            <button type="button" class="btn btn-sm btn-primary" id="addRow">
                                <i class="fa fa-plus mr-1"></i> Yeni Satır Ekle
                            </button>
                        </div>
                        <div>
                            <span class="badge badge-warning text-white px-3 py-2 font-13 font-weight-bold" id="aasRowCountBadge">1 Armatür</span>
                        </div>
                    </div>

                    <div class="aas-table-wrapper">
                        <div class="aas-table-responsive">
                            <table id="yasTable" class="table premium-table aas-table mb-0">
                                <thead>
                                    <tr class="main-head text-center">
                                        <th style="width: 45px;"><i class="fa fa-cog"></i></th>
                                        <th style="min-width: 160px;">Cihaz / Armatür Cinsi</th>
                                        <th style="min-width: 160px;">Bulunduğu Kısım / Mahal</th>
                                        <th style="min-width: 130px;">Markası / Modeli</th>
                                        <th style="min-width: 110px;">Kontrol Tarihi</th>
                                        <th style="min-width: 140px;">Problemler / Durum</th>
                                        <th style="min-width: 150px;">Yapılacak İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php include "report-row-aas.php"; ?>
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
    function updateAasRowCount() {
        var count = $('#yasTable tbody tr').length;
        $('#aasRowCountBadge').text(count + ' Armatür');
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

        // Yeni Satır Ekle
        $("#addRow").click(function() {
            var html = '<tr>' +
                '<td class="text-center"><button type="button" class="btn btn-delete-row sil" title="Satırı Sil"><i class="fa fa-trash"></i></button></td>' +
                '<td><input required type="text" class="form-control" name="cinsi[]" placeholder="Cihaz / Armatür Cinsi"></td>' +
                '<td><input required type="text" class="form-control" name="bulundugu_bolge[]" placeholder="Bulunduğu Kısım / Konum"></td>' +
                '<td><input required type="text" class="form-control" name="markasi[]" placeholder="Marka / Model"></td>' +
                '<td><input type="text" class="form-control date-picker" name="kontroltarihi[]" value="<?php echo date('d.m.Y'); ?>" placeholder="Tarih" autocomplete="off"></td>' +
                '<td><select name="problems[]" class="form-control custom-select-status"><option value="1" selected>UYGUN / SORUN YOK</option><option value="0">UYGUN DEĞİL / SORUNLU</option></select></td>' +
                '<td><select name="islemler[]" class="form-control custom-select-status"><option value="1" selected>İŞLEM GEREKMEZ</option><option value="0">BAKIM / ONARIM GEREKİR</option></select></td>' +
                '</tr>';
            $('#yasTable tbody').append(html);
            if (typeof $.fn.datepicker !== 'undefined') {
                $('.date-picker').datepicker({
                    language: 'tr',
                    autoClose: true,
                    dateFormat: 'dd.mm.yyyy'
                });
            }
            updateAasRowCount();
        });

        // Satır Sil
        $(document).on('click', '#yasTable .sil', function() {
            if ($('#yasTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                $(this).closest('tr').find('input').val('');
            }
            updateAasRowCount();
        });

        updateAasRowCount();
    });

    $('#myForm').on('submit', function(e) {
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