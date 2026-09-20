<?php
// Rapor Düzenleme Yetkisi
permcontrol("reportedit");

$id = (int)($_GET["id"] ?? 0);
$type = $_GET["type"] ?? 3;

if ($id <= 0) {
    header("Location: index.php?p=reports/reports");
    exit;
}

if ($_POST) {
    $cinsi = $_POST["cinsi"] ?? [];
    $dolapSayisi = count($cinsi);

    $report_number = $_POST["report_number"] ?? '';
    $customer_id = (int)($_POST["customer"] ?? 0);
    $control_date = $_POST["control_date"] ?? date("d.m.Y");
    $next_control_date = $_POST["next_control_date"] ?? date("d.m.Y", strtotime("+1 year"));
    $controller_id = (int)($_POST["controller"] ?? 0);
    $updater = sesset("id");
    $update_time = date("Y-m-d H:i:s");
    $criteria = $_POST["criteria"] ?? '';

    if (empty($customer_id)) {
        header("Location: index.php?p=reports/met/report-edit-met&id=" . $id . "&type=3&st=empties");
        exit;
    }

    // DOLAP BİLGİLERİ
    $bulundugu_kisim = $_POST["bulundugu_kisim"] ?? [];
    $ozellikler = $_POST["ozellikler"] ?? [];
    $control_date_closet = $_POST["control_date_closet"] ?? [];
    $next_control_date_closet = $_POST["next_control_date_closet"] ?? [];
    $vana_durum = $_POST["vana_durum"] ?? [];
    $hortum_baglanti_durum = $_POST["hortum_baglanti_durum"] ?? [];
    $levha_durum = $_POST["levha_durum"] ?? [];
    $pas_durum = $_POST["pas_durum"] ?? [];
    $kilit_durum = $_POST["kilit_durum"] ?? [];
    $hortum_durum = $_POST["hortum_durum"] ?? [];
    $basinc_degeri = $_POST["basinc_degeri"] ?? [];
    $nozul_durum = $_POST["nozul_durum"] ?? [];
    $aciklama = $_POST["aciklama"] ?? [];

    $data = [];
    for ($i = 1; $i <= 30; $i++) {
        $data["madde$i"] = $_POST["madde$i"] ?? "1";
    }
    $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

    try {
        $query = $ac->prepare("UPDATE reports SET 
            report_number = ?, 
            customer_id = ?, 
            control_date = ?, 
            next_control_date = ?, 
            controller_id = ?, 
            report_matters = ?,
            updater = ?, 
            update_time = ?, 
            subNotes = ? 
            WHERE id = ?");
        $query->execute([
            $report_number,
            $customer_id,
            $control_date,
            $next_control_date,
            $controller_id,
            $jsonData,
            $updater,
            $update_time,
            $criteria,
            $id
        ]);

        // DOLAP SAYFASI GÜNCELLEME (Eski kayıtları silip yenilerini ekle)
        $delete_content = $ac->prepare("DELETE FROM report_met_content WHERE report_id = ?");
        $delete_content->execute([$id]);

        if ($dolapSayisi > 0) {
            $insq = $ac->prepare("INSERT INTO report_met_content SET 
                report_id = ?, 
                cinsi = ?, 
                bulundugu_kisim = ?, 
                ozellikler = ?, 
                control_date_closet = ?, 
                next_control_date_closet = ?, 
                vana_durum = ?, 
                hortum_baglanti_durum = ?, 
                levha_durum = ?, 
                pas_durum = ?, 
                kilit_durum = ?, 
                hortum_durum = ?, 
                basinc_degeri = ?, 
                nozul_durum = ?, 
                aciklama = ?");

            for ($i = 0; $i < $dolapSayisi; $i++) {
                if (empty($cinsi[$i]) && empty($bulundugu_kisim[$i])) {
                    continue;
                }
                $insq->execute([
                    $id,
                    $cinsi[$i] ?? '',
                    $bulundugu_kisim[$i] ?? '',
                    $ozellikler[$i] ?? '',
                    $control_date_closet[$i] ?? $control_date,
                    $next_control_date_closet[$i] ?? $next_control_date,
                    $vana_durum[$i] ?? '1',
                    $hortum_baglanti_durum[$i] ?? '1',
                    $levha_durum[$i] ?? '1',
                    $pas_durum[$i] ?? '1',
                    $kilit_durum[$i] ?? '1',
                    $hortum_durum[$i] ?? '1',
                    $basinc_degeri[$i] ?? '',
                    $nozul_durum[$i] ?? '1',
                    $aciklama[$i] ?? ''
                ]);
            }
        }

        // YENİ EKLENEN DOSYALAR
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
                    $new_filename = uniqid() . "_" . $clean_name;
                    $target_file = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES["report_attach"]["tmp_name"][$i], $target_file)) {
                        $ins = $ac->prepare("INSERT INTO files SET
                            report_id = ?,
                            fileDescription = ?,
                            filename = ?,
                            size = ?,
                            creativer = ?");
                        $ins->execute([
                            $id,
                            $dosya_aciklama[$i] ?? '',
                            $new_filename,
                            $_FILES["report_attach"]["size"][$i] ?? 0,
                            sesset("id")
                        ]);
                    }
                }
            }
        }

        audit_log("update", "report", "Mekanik Tesisat Kontrol Raporu güncellendi: " . $report_number, "reports", $id);

        header("Location: index.php?p=reports/met/report-edit-met&id=" . $id . "&st=newsuccess&type=3");
        exit;

    } catch (PDOException $e) {
        error_log("MET Raporu Güncelleme Hatası: " . $e->getMessage());
        header("Location: index.php?p=reports/met/report-edit-met&id=" . $id . "&type=3&st=error");
        exit;
    }
}

if (@$_GET["st"] == "empties") {
    showAlert("alert", "Lütfen firma seçimini yapınız!");
}
if (@$_GET["st"] == "newsuccess") {
    showAlert("success", "Mekanik Tesisat Kontrol Raporu başarıyla güncellendi!");
}
if (@$_GET["st"] == "error") {
    showAlert("alert", "Rapor güncellenirken bir hata oluştu. Lütfen tekrar deneyiniz.");
}

// Rapor Bilgilerini Çek
$sql = $ac->prepare("SELECT * FROM reports WHERE id = ?");
$sql->execute([$id]);
$report = $sql->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    header("Location: index.php?p=reports/reports");
    exit;
}

$matters = json_decode($report["report_matters"] ?? "{}", true);
if (!is_array($matters)) {
    $matters = [];
}
?>

<style>
    .met-report-wrapper {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        padding-bottom: 40px;
    }

    /* Minimal Table Container Matching premium-theme */
    .met-table-wrapper {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .met-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .premium-table.met-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 0;
        margin-bottom: 0;
        border: none !important;
    }

    .premium-table.met-table thead th {
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

    .premium-table.met-table thead tr.main-head th {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
    }

    .premium-table.met-table td {
        padding: 4px 4px !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f8fafc;
        background: #ffffff;
    }

    .premium-table.met-table tbody tr:hover td {
        background: #f8fafc;
    }

    .premium-table.met-table .form-control {
        height: 30px !important;
        padding: 2px 6px !important;
        font-size: 12px !important;
        border-radius: 6px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff;
        transition: all 0.15s ease-in-out;
    }

    .premium-table.met-table .form-control:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12) !important;
        background: #fff !important;
    }

    .premium-table.met-table .btn-delete-row {
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

    .premium-table.met-table .btn-delete-row:hover {
        background-color: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .premium-table.met-table select.custom-select-status {
        font-size: 11px !important;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
        padding: 2px 4px !important;
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

    /* Custom Nav Pills Matching Premium Theme */
    .custom-report-pills {
        display: flex;
        gap: 8px;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .custom-report-pills .nav-link {
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 18px;
        border-radius: 8px;
        border: none;
        transition: all 0.2s;
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

    /* Dark Mode Overrides */
    .dark-mode .met-table-wrapper {
        background: #1e293b;
        border-color: #334155;
    }

    .dark-mode .premium-table.met-table thead th {
        background: #0f172a !important;
        color: #cbd5e1;
        border-color: #334155;
    }

    .dark-mode .premium-table.met-table thead tr.main-head th {
        background: #1e293b !important;
        color: #f8fafc;
        border-color: #334155;
    }

    .dark-mode .premium-table.met-table td {
        background: #1e293b;
        border-color: #334155;
        color: #e2e8f0;
    }

    .dark-mode .premium-table.met-table tbody tr:hover td {
        background: #283548;
    }

    .dark-mode .premium-table.met-table .form-control {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }

    .dark-mode .custom-report-pills {
        background: #0f172a;
    }

    .dark-mode .custom-report-pills .nav-link {
        color: #94a3b8;
    }

    .dark-mode .custom-report-pills .nav-link.active {
        color: #38bdf8;
        background: #1e293b;
    }

    .dark-mode .matter-card {
        background: #1e293b;
        border-color: #334155;
    }

    .dark-mode .matter-card:hover {
        background: #283548;
    }

    .dark-mode .matter-text {
        color: #cbd5e1;
    }

    .dark-mode .matter-number {
        background: #0369a1;
        color: #e0f2fe;
    }
</style>

<form enctype="multipart/form-data" id="myForm" method="POST">
    <div class="met-report-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                        <i class="fa fa-wrench"></i>
                    </div>
                    <div class="header-title">
                        <h4>Mekanik Tesisat Kontrol Raporu Düzenle</h4>
                        <span class="header-number-badge">
                            <i class="fa fa-tag"></i> Rapor No: <?php echo htmlspecialchars($report["report_number"] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=reports/met/report-view-met&id=<?php echo $id; ?>" target="_blank" class="btn-header btn-header-print" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;">
                        <i class="fa fa-file-pdf-o"></i> PDF Raporu
                    </a>
                    <a href="index.php?p=reports/reports" class="btn-header btn-header-list">
                        <i class="fa fa-list"></i> Listeye Dön
                    </a>
                    <button type="submit" id="submitButton" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Değişiklikleri Kaydet
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
                    <i class="fa fa-list-alt mr-1"></i> Dolap & Cihaz Listesi
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
                            <input required name="report_number" id="report_number" type="text" readonly value="<?php echo htmlspecialchars($report["report_number"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control font-weight-bold bg-light" placeholder="Rapor No">
                        </div>

                        <!-- Firma Adı -->
                        <div class="form-field">
                            <label for="customer"><font color="red">(*)</font> Firma:</label>
                            <select required name="customer" id="customer" data-live-search="true" data-size="10" class="form-control selectpicker" data-style="bg-white border">
                                <option disabled value="">Firma Seçiniz</option>
                                <?php
                                $compquery = $ac->prepare("SELECT id, company FROM customers WHERE deleted_at IS NULL ORDER BY company ASC");
                                $compquery->execute();
                                while ($company = $compquery->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = ($company["id"] == $report["customer_id"]) ? "selected" : "";
                                ?>
                                    <option value="<?php echo $company["id"]; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($company["company"], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Kontrolü Yapan Mühendis -->
                        <div class="form-field">
                            <label for="controller"><font color="red">(*)</font> Kontrolü Yapan Mühendis:</label>
                            <select required name="controller" id="controller" data-live-search="true" class="form-control selectpicker" data-style="bg-white border">
                                <option disabled value="">Mühendis Seçiniz</option>
                                <?php
                                $userquery = $ac->prepare("SELECT id, username, meslek FROM users ORDER BY username ASC");
                                $userquery->execute();
                                while ($usr = $userquery->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = ($usr["id"] == $report["controller_id"]) ? "selected" : "";
                                ?>
                                    <option value="<?php echo $usr["id"]; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($usr["username"] . (!empty($usr["meslek"]) ? " (" . $usr["meslek"] . ")" : ""), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Kontrol Tarihi -->
                        <div class="form-field">
                            <label for="control_date"><font color="red">(*)</font> Kontrol Tarihi:</label>
                            <input required type="text" autocomplete="off" name="control_date" id="control_date" class="form-control date-picker" value="<?php echo htmlspecialchars($report["control_date"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Kontrol Tarihi">
                        </div>

                        <!-- Sonraki Kontrol Tarihi -->
                        <div class="form-field">
                            <label for="next_control_date"><font color="red">(*)</font> Sonraki Kontrol Tarihi:</label>
                            <input required type="text" autocomplete="off" name="next_control_date" id="next_control_date" class="form-control date-picker" value="<?php echo htmlspecialchars($report["next_control_date"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Sonraki Kontrol Tarihi">
                        </div>
                    </div>
                </div>

                <!-- Kart 2: Kontrol Maddeleri -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-green">
                            <i class="fa fa-check-square-o"></i>
                        </div>
                        <div>
                            <h5>Rapor Kontrol Maddeleri (Yönetmelik ve Standartlar)</h5>
                            <p class="mb-0">6331 Sayılı Kanun İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği (Madde 1.7)</p>
                        </div>
                    </div>

                    <?php
                    $sql = $ac->prepare("SELECT met as soru FROM report_questions WHERE met != '' ORDER BY id ASC");
                    $sql->execute();
                    $question = $sql->fetchAll(PDO::FETCH_ASSOC);
                    $totalQuestions = count($question);
                    $half = ceil($totalQuestions / 2);
                    ?>

                    <div class="row">
                        <div class="col-lg-6 col-md-12">
                            <?php for ($i = 0; $i < $half; $i++) { 
                                $num = $i + 1;
                                $soru = $question[$i]["soru"] ?? "";
                                $savedVal = isset($matters["madde$num"]) ? (string)$matters["madde$num"] : "1";
                            ?>
                                <div class="matter-card">
                                    <div class="matter-number"><?php echo $num; ?></div>
                                    <div class="matter-text"><?php echo htmlspecialchars($soru, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="matter-select">
                                        <select name="madde<?php echo $num; ?>" class="form-control custom-select-status">
                                            <option value="1" <?php echo $savedVal === "1" ? "selected" : ""; ?>>UYGUN</option>
                                            <option value="0" <?php echo $savedVal === "0" ? "selected" : ""; ?>>UYGUN DEĞİL</option>
                                            <option value="2" <?php echo $savedVal === "2" ? "selected" : ""; ?>>DEĞERLENDİRME DIŞI</option>
                                        </select>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="col-lg-6 col-md-12">
                            <?php for ($i = $half; $i < $totalQuestions; $i++) { 
                                $num = $i + 1;
                                $soru = $question[$i]["soru"] ?? "";
                                $savedVal = isset($matters["madde$num"]) ? (string)$matters["madde$num"] : "1";
                            ?>
                                <div class="matter-card">
                                    <div class="matter-number"><?php echo $num; ?></div>
                                    <div class="matter-text"><?php echo htmlspecialchars($soru, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="matter-select">
                                        <select name="madde<?php echo $num; ?>" class="form-control custom-select-status">
                                            <option value="1" <?php echo $savedVal === "1" ? "selected" : ""; ?>>UYGUN</option>
                                            <option value="0" <?php echo $savedVal === "0" ? "selected" : ""; ?>>UYGUN DEĞİL</option>
                                            <option value="2" <?php echo $savedVal === "2" ? "selected" : ""; ?>>DEĞERLENDİRME DIŞI</option>
                                        </select>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: DOLAP & CİHAZ LİSTESİ -->
            <div class="tab-pane fade" id="pills-content" role="tabpanel" aria-labelledby="pills-content-tab">
                <!-- Kart: Kontrol Kriterleri -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-orange">
                            <i class="fa fa-file-text-o"></i>
                        </div>
                        <div>
                            <h5>Kontrol Kriterleri & Standart Açıklamaları</h5>
                            <p>İlgili standartlar, yapılan deneyler ve sonuç kanaatleri</p>
                        </div>
                    </div>

                    <div class="form-field full-width">
                        <label for="criteria" class="font-weight-600 mb-1">Kontrol Kriterleri & İlgili Standartlar:</label>
                        <div class="html-editor">
                            <textarea style="height: 100px; resize: vertical;" name="criteria" id="criteria" class="textarea_editor form-control" placeholder="İlgili standartları ve kriterleri yazınız"><?php echo htmlspecialchars($report["subNotes"] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Kart: Cihaz / Dolap Tablosu -->
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-blue">
                            <i class="fa fa-list"></i>
                        </div>
                        <div>
                            <h5>Yangın Dolap & Tesisat Kontrol Bilgileri</h5>
                            <p>Tesisat ve dolap ekipmanlarının detaylı muayene sonuçları</p>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <div class="table-actions-toolbar">
                        <div class="table-actions-left">
                            <button type="button" class="btn btn-sm btn-primary" id="addRow">
                                <i class="fa fa-plus mr-1"></i> Yeni Satır Ekle
                            </button>
                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#exampleModalCenter" id="addMultiRow">
                                <i class="fa fa-list-ol mr-1"></i> Çoklu Satır Ekle
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#uploadfromxlsModal">
                                <i class="fa fa-file-excel-o mr-1"></i> Excel'den Yükle
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="deleteAll">
                                <i class="fa fa-trash-o mr-1"></i> Tümünü Sil
                            </button>
                        </div>
                        <div>
                            <span class="badge badge-primary px-3 py-2 font-13 font-weight-bold" id="rowCountBadge">0 Satır</span>
                        </div>
                    </div>

                    <!-- Tablo -->
                    <div class="met-table-wrapper">
                        <div class="met-table-responsive">
                            <table id="metTable" class="table premium-table met-table mb-0">
                                <thead>
                                    <tr class="main-head text-center">
                                        <th style="width: 45px;"><i class="fa fa-cog"></i></th>
                                        <th style="width: 50px;">S.N</th>
                                        <th style="min-width: 130px;">Cihazın Cinsi</th>
                                        <th style="min-width: 140px;">Bulunduğu Kısım</th>
                                        <th style="min-width: 100px;">Özellikleri (Mt)</th>
                                        <th style="min-width: 105px;">Kontrol Tarihi</th>
                                        <th style="min-width: 105px;">Sonraki Kontrol</th>
                                        <th style="min-width: 110px;">Vana Uygun Mu?</th>
                                        <th style="min-width: 110px;">Hortum Bağlantı</th>
                                        <th style="min-width: 110px;">Levha Uygun Mu?</th>
                                        <th style="min-width: 110px;">Paslanma Var Mı?</th>
                                        <th style="min-width: 110px;">Kilit Uygun Mu?</th>
                                        <th style="min-width: 110px;">Hortum Durumu</th>
                                        <th style="min-width: 90px;">Basınç (Bar)</th>
                                        <th style="min-width: 110px;">Nozul Durumu</th>
                                        <th style="min-width: 160px;">Açıklama</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $closet_query = $ac->prepare("SELECT * FROM report_met_content WHERE report_id = ? ORDER BY id ASC");
                                    $closet_query->execute([$id]);
                                    $hasRows = false;
                                    $sirano = 1;

                                    while ($closet = $closet_query->fetch(PDO::FETCH_ASSOC)) {
                                        $hasRows = true;
                                        $tabindex = $sirano;
                                        $cinsi = $closet["cinsi"];
                                        $bulundugu_kisim = $closet["bulundugu_kisim"];
                                        $ozellikler = $closet["ozellikler"];
                                        $control_date_closet = $closet["control_date_closet"];
                                        $next_control_date_closet = $closet["next_control_date_closet"];
                                        $vana_durum = $closet["vana_durum"];
                                        $hortum_baglanti_durum = $closet["hortum_baglanti_durum"];
                                        $levha_durum = $closet["levha_durum"];
                                        $pas_durum = $closet["pas_durum"];
                                        $kilit_durum = $closet["kilit_durum"];
                                        $hortum_durum = $closet["hortum_durum"];
                                        $basinc_degeri = $closet["basinc_degeri"];
                                        $nozul_durum = $closet["nozul_durum"];
                                        $aciklama = $closet["aciklama"];

                                        include "report-row-met.php";
                                        $sirano++;
                                    }

                                    if (!$hasRows) {
                                        $tabindex = 0;
                                        $sirano = 1;
                                        include "report-row-met.php";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: EK BELGELER -->
            <div class="tab-pane fade" id="pills-attach" role="tabpanel" aria-labelledby="pills-attach-tab">
                <div class="form-card mb-4 animate-fade-in">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-green">
                            <i class="fa fa-folder-open-o"></i>
                        </div>
                        <div>
                            <h5>Rapor Ek Dosyaları & Belgeleri</h5>
                            <p>Raporla ilişkili test sertifikaları, fotoğraflar ve ek belgeler</p>
                        </div>
                    </div>

                    <div class="table-actions-toolbar">
                        <div class="table-actions-left">
                            <button type="button" class="btn btn-sm btn-primary" id="addRowfile">
                                <i class="fa fa-plus mr-1"></i> Yeni Dosya Ekle
                            </button>
                        </div>
                    </div>

                    <div class="met-table-wrapper">
                        <div class="met-table-responsive">
                            <table id="metTablefile" class="table premium-table met-table mb-0">
                                <thead>
                                    <tr class="main-head text-center">
                                        <th style="width: 45px;"><i class="fa fa-cog"></i></th>
                                        <th style="min-width: 250px;">Açıklama</th>
                                        <th style="min-width: 200px;">Dosya</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $files_query = $ac->prepare("SELECT * FROM files WHERE report_id = ? ORDER BY id ASC");
                                    $files_query->execute([$id]);
                                    $hasFiles = false;

                                    while ($files = $files_query->fetch(PDO::FETCH_ASSOC)) {
                                        $hasFiles = true;
                                        include "report-row-met-attach.php";
                                    }

                                    if (!$hasFiles) {
                                        $files = [];
                                        include "report-row-met-attach.php";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include_once "upload-from-xls-modal.php"; ?>
<?php include_once "addMultipleRow-modal.php"; ?>
<script src="src/plugins/xlsx/xlsx.full.min.js"></script>
<script src="include/js/met.js"></script>