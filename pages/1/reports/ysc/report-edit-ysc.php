<?php
// Rapor Düzenleme Yetkisi
permcontrol("reportedit");
$id = (int)($_GET["id"] ?? 0);
$type = $_GET["type"] ?? 1;

if ($_POST) {
    $cihazno = $_POST["cihazno"] ?? [];
    $cihazSayisi = count($cihazno);

    if (empty($_POST["customer_id"]) && $cihazSayisi < 1) {
        header("Location: index.php?p=reports/ysc/report-edit-ysc&id=" . $id . "&type=1&st=empties");
        exit;
    }

    $report_type = $_POST["report_type"] ?? 1;
    $report_number = $_POST["reportnumber"] ?? '';
    $isemrino = $_POST["isemrino"] ?? '';
    $control_date = $_POST["control_date"] ?? '';
    $control_period = $_POST["control_period"] ?? '';
    $validity_date = $_POST["validity_date"] ?? '';
    $controller_id = $_POST["controller_id"] ?? '';
    $company_official = $_POST["company_official"] ?? '';
    $customer_id = $_POST["customer_id"] ?? '';
    $standarts = $_POST["standarts"] ?? '';
    $warnings = $_POST["warnings"] ?? '';
    $equipments = $_POST["equipment"] ?? '';
    $notes = $_POST["notes"] ?? '';
    $subnotes = $_POST["subNotes"] ?? '';

    try {
        $insq = $ac->prepare("UPDATE reports SET 
            report_type = ?,
            report_number = ?,
            isemrino = ?,
            control_date = ?,
            control_period = ?,
            validity_date = ?,
            customer_id = ?,
            controller_id = ?,
            company_official = ?,
            standarts = ?,
            warnings = ?, 
            equipments = ?,
            notes = ?,
            subNotes = ? 
            WHERE id = ?");

        $insq->execute([
            1,
            $report_number,
            $isemrino,
            $control_date,
            $control_period,
            $validity_date,
            $customer_id,
            $controller_id,
            $company_official,
            $standarts,
            $warnings,
            $equipments,
            $notes,
            $subnotes,
            $id
        ]);

        $bulundugu_bolge = $_POST["cihazbolge"] ?? [];
        $cinsi = $_POST["cinsi"] ?? [];
        $cihaz_dolum_tarihi = $_POST["dolumtarihi"] ?? [];
        $cihaz_sonkullanma_tarihi = $_POST["sonkullanimtarihi"] ?? [];
        $kontrol_tarihi_1 = $_POST["kontoltarihi1"] ?? [];
        $kontrol_tarihi_2 = $_POST["kontoltarihi2"] ?? [];
        $islem_kontrol_tarihi_1 = $_POST["islemkontroltarihi1"] ?? [];
        $islem_kontrol_tarihi_2 = $_POST["islemkontroltarihi2"] ?? [];
        $dis_muhafaza = $_POST["dismuhafaza"] ?? [];
        $cevre_kontrolu = $_POST["cevrekontrolu"] ?? [];
        $pim_kontrolu = $_POST["pimkontrolu"] ?? [];
        $manometre_kontrolu = $_POST["manometrekontrolu"] ?? [];
        $hortum_kontrolu = $_POST["hortumkontrolu"] ?? [];
        $talimat_kontrolu = $_POST["talimatkontrolu"] ?? [];
        $agirlik_kontrolu = $_POST["agirlikkontrolu"] ?? [];

        if ($cihazSayisi > 0) {
            $delquery = $ac->prepare("DELETE FROM report_ysc_content WHERE report_id = ?");
            $delquery->execute([$id]);

            $placeholders = [];
            $data = [];
            for ($i = 0; $i < $cihazSayisi; $i++) {
                $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                array_push(
                    $data,
                    $id,
                    $cihazno[$i] ?? ($i + 1),
                    $bulundugu_bolge[$i] ?? '',
                    $cinsi[$i] ?? '',
                    $cihaz_dolum_tarihi[$i] ?? '',
                    $cihaz_sonkullanma_tarihi[$i] ?? '',
                    $kontrol_tarihi_1[$i] ?? '',
                    $kontrol_tarihi_2[$i] ?? '',
                    $islem_kontrol_tarihi_1[$i] ?? '',
                    $islem_kontrol_tarihi_2[$i] ?? '',
                    $dis_muhafaza[$i] ?? '1',
                    $cevre_kontrolu[$i] ?? '1',
                    $pim_kontrolu[$i] ?? '1',
                    $manometre_kontrolu[$i] ?? '1',
                    $hortum_kontrolu[$i] ?? '1',
                    $talimat_kontrolu[$i] ?? '1',
                    $agirlik_kontrolu[$i] ?? '1'
                );
            }

            $sql = "INSERT INTO report_ysc_content (report_id, cihaz_no, bulundugu_bolge, cinsi, cihaz_dolum_tarihi, cihaz_sonkullanma_tarihi, kontrol_tarihi_1, kontrol_tarihi_2, islem_kontrol_tarihi_1, islem_kontrol_tarihi_2, dis_muhafaza, cevre_kontrolu, pim_kontrolu, manometre_kontrolu, hortum_kontrolu, talimat_kontrolu, agirlik_kontrolu) VALUES " . implode(', ', $placeholders);
            $insq = $ac->prepare($sql);
            $insq->execute($data);
        }

        header("Location: index.php?p=reports/ysc/report-edit-ysc&st=newsuccess&id=" . $id . "&type=1");
        exit;
    } catch (PDOException $e) {
        error_log("YSC Raporu Güncelleme Hatası: " . $e->getMessage());
        header("Location: index.php?p=reports/ysc/report-edit-ysc&st=error&id=" . $id . "&type=1");
        exit;
    }
}

$query = $ac->prepare("SELECT * FROM reports WHERE id = ?");
$query->execute([$id]);
$reports = $query->fetch(PDO::FETCH_ASSOC);

if (!$reports) {
    echo '<div class="alert alert-danger m-4">Rapor bulunamadı!</div>';
    return;
}

if (@$_GET["st"] == "empties") {
    showAlert('alert', "(*) ile işaretli zorunlu alanları doldurmadan tekrar deneyin.");
}
if (@$_GET["st"] == "newsuccess") {
    showAlert("success", "Yangın Söndürme Tüpü Kontrol Raporu başarı ile güncellendi!");
}
if (@$_GET["st"] == "error") {
    showAlert("alert", "Rapor güncellenirken bir hata oluştu. Lütfen tekrar deneyiniz.");
}
?>

<style>
    .ysc-report-wrapper {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        padding-bottom: 40px;
    }

    /* WYSIWYG Editor Styling */
    .html-editor {
        width: 100%;
    }

    .html-editor .wysihtml5-toolbar {
        margin-bottom: 8px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        padding-left: 0;
        list-style: none;
    }

    .html-editor .wysihtml5-toolbar li {
        margin: 0 4px 4px 0;
    }

    .html-editor .wysihtml5-toolbar li a.btn {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        padding: 4px 8px;
        font-size: 12px;
        border-radius: 6px;
    }

    .html-editor .wysihtml5-toolbar li a.btn:hover,
    .html-editor .wysihtml5-toolbar li a.btn.wysihtml5-command-active {
        background: #e2e8f0;
        color: #1e293b;
    }

    .html-editor .wysihtml5-sandbox {
        border-radius: 10px !important;
        border: 1.5px solid #e5e7eb !important;
        background: #fafafa !important;
        width: 100% !important;
        min-height: 120px !important;
        padding: 8px !important;
    }

    /* Minimal Table Container Matching offer-manage */
    .ysc-table-wrapper {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .ysc-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .premium-table.ysc-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 0;
        margin-bottom: 0;
        border: none !important;
    }

    .premium-table.ysc-table thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 11px !important;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 8px 4px !important;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #f1f5f9;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    .premium-table.ysc-table thead tr.main-head th {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
    }

    .premium-table.ysc-table td {
        padding: 4px 3px !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f8fafc;
        background: #ffffff;
    }

    .premium-table.ysc-table tbody tr:hover td {
        background: #f8fafc;
    }

    /* Minimal Compact Inputs Matching offer-manage */
    .premium-table.ysc-table .form-control {
        height: 28px !important;
        padding: 2px 6px !important;
        font-size: 12px !important;
        border-radius: 5px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff;
        transition: all 0.15s ease-in-out;
    }

    .premium-table.ysc-table .form-control:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12) !important;
        background: #fff !important;
    }

    .premium-table.ysc-table .btn-delete-row {
        padding: 0 !important;
        width: 26px;
        height: 26px;
        line-height: 26px;
        border-radius: 4px !important;
        font-size: 11px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
        transition: all 0.2s;
    }

    .premium-table.ysc-table .btn-delete-row:hover {
        background-color: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }

    .premium-table.ysc-table .input-group-append .btn {
        height: 28px !important;
        padding: 2px 6px !important;
        font-size: 11px !important;
        border-radius: 0 5px 5px 0 !important;
        border: 1px solid #e2e8f0 !important;
        border-left: none !important;
        background: #f8fafc;
        color: #64748b;
    }

    .premium-table.ysc-table .input-group .form-control {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    .premium-table.ysc-table select.custom-select-status {
        font-size: 11px !important;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
        padding: 2px 4px !important;
    }

    .premium-table.ysc-table select.custom-select-status option[value="1"] {
        color: #16a34a;
        font-weight: 600;
    }

    .premium-table.ysc-table select.custom-select-status option[value="0"] {
        color: #dc2626;
        font-weight: 600;
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

    .cursor-pointer {
        cursor: pointer !important;
    }

    /* Dark Mode Overrides */
    .dark-mode .html-editor .wysihtml5-toolbar li a.btn {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }

    .dark-mode .html-editor .wysihtml5-toolbar li a.btn:hover,
    .dark-mode .html-editor .wysihtml5-toolbar li a.btn.wysihtml5-command-active {
        background: #334155 !important;
        color: #60a5fa !important;
    }

    .dark-mode .html-editor .wysihtml5-sandbox {
        border-color: #334155 !important;
        background: #1f1f1f !important;
    }

    .dark-mode .ysc-table-wrapper {
        background: #1e293b;
        border-color: #334155;
    }

    .dark-mode .premium-table.ysc-table thead th {
        background: #0f172a !important;
        color: #cbd5e1;
        border-color: #334155;
    }

    .dark-mode .premium-table.ysc-table thead tr.main-head th {
        background: #1e293b !important;
        color: #f8fafc;
        border-color: #334155;
    }

    .dark-mode .premium-table.ysc-table td {
        background: #1e293b;
        border-color: #334155;
        color: #e2e8f0;
    }

    .dark-mode .premium-table.ysc-table tbody tr:hover td {
        background: #283548;
    }

    .dark-mode .premium-table.ysc-table .form-control {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }

    .dark-mode .premium-table.ysc-table .input-group-append .btn {
        background: #1e293b;
        border-color: #334155 !important;
        color: #cbd5e1;
    }

    .dark-mode .premium-table.ysc-table tfoot td {
        background: #0f172a;
        border-color: #334155;
    }
</style>

<form enctype="multipart/form-data" id="myForm" method="POST">
    <div class="ysc-report-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="fa fa-fire-extinguisher"></i>
                    </div>
                    <div class="header-title">
                        <h4><?php echo htmlspecialchars($pdat["p_title"] ?? 'Yangın Söndürme Raporu Düzenle', ENT_QUOTES, 'UTF-8'); ?></h4>
                        <span class="header-number-badge">
                            <i class="fa fa-tag"></i> Rapor No: <?php echo htmlspecialchars($reports["report_number"] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=reports/reports" class="btn-header btn-header-list">
                        <i class="fa fa-list"></i> Listeye Dön
                    </a>
                    <button type="button" id="submitButton" onclick="validateForm()" class="btn-header btn-header-save">
                        <i class="fa fa-save"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>

        <!-- Kart 1: Rapor & Müşteri Tanımı -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-building-o"></i>
                </div>
                <div>
                    <h5>Genel Rapor & Müşteri Tanımı</h5>
                    <p>Rapor numarası, müşteri seçimi, iş emri ve denetim sorumluları</p>
                </div>
            </div>

            <div class="form-grid">
                <!-- Rapor No -->
                <div class="form-field">
                    <label for="reportnumber"><font color="red">(*)</font> Rapor No:</label>
                    <input required name="reportnumber" id="reportnumber" type="text" value="<?php echo htmlspecialchars($reports["report_number"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control font-weight-bold" placeholder="Rapor numarası">
                </div>

                <!-- İş Emri No -->
                <div class="form-field">
                    <label for="isemrino">İş Emri No:</label>
                    <select name="isemrino" id="isemrino" data-size="10" class="form-control selectpicker" data-style="bg-white border">
                        <option value="">İş Emri Seçiniz</option>
                        <?php
                        $servicequery = $ac->prepare("SELECT id FROM projects ORDER BY id DESC");
                        $servicequery->execute();
                        while ($isemri = $servicequery->fetch(PDO::FETCH_ASSOC)) {
                            $snValue = "SN" . $isemri["id"];
                            $selected = ($reports["isemrino"] == $snValue || $reports["isemrino"] == $isemri["id"] || substr($reports["isemrino"], 2) == $isemri["id"]) ? "selected" : "";
                        ?>
                            <option value="<?php echo htmlspecialchars($snValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($snValue, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Firma Adı -->
                <div class="form-field">
                    <label for="customer_id"><font color="red">(*)</font> Firma Adı:</label>
                    <select required name="customer_id" id="customer_id" data-live-search="true" data-size="10" class="form-control selectpicker" data-style="bg-white border">
                        <option disabled value="">Firma Seçiniz</option>
                        <?php
                        $compquery = $ac->prepare("SELECT id, company FROM customers WHERE deleted_at IS NULL ORDER BY company ASC");
                        $compquery->execute();
                        while ($company = $compquery->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($reports["customer_id"] == $company["id"]) ? "selected" : "";
                        ?>
                            <option value="<?php echo $company["id"]; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($company["company"], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Kontrol Eden Mühendis -->
                <div class="form-field">
                    <label for="controller_id"><font color="red">(*)</font> Kontrol Eden Mühendis:</label>
                    <select required name="controller_id" id="controller_id" class="form-control selectpicker" data-style="bg-white border">
                        <option value="">Mühendis Seçiniz</option>
                        <?php
                        $userquery = $ac->prepare("SELECT id, username, meslek FROM users WHERE id != 1 AND statu = 1 ORDER BY username ASC");
                        $userquery->execute();
                        while ($user = $userquery->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($reports["controller_id"] == $user["id"]) ? "selected" : "";
                        ?>
                            <option value="<?php echo $user["id"]; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($user["username"] . ($user["meslek"] ? " - " . $user["meslek"] : ""), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Kontrol & Geçerlilik Tarihi -->
                <div class="form-field">
                    <label for="control_date"><font color="red">(*)</font> Kontrol Tarihi:</label>
                    <input required type="text" name="control_date" id="control_date" autocomplete="off" class="form-control date-picker" placeholder="Tarih seçiniz" value="<?php echo htmlspecialchars($reports["control_date"] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-field">
                    <label for="validity_date"><font color="red">(*)</font> Geçerlilik Tarihi:</label>
                    <input required type="text" name="validity_date" id="validity_date" autocomplete="off" class="form-control date-picker" placeholder="Tarih seçiniz" value="<?php echo htmlspecialchars($reports["validity_date"] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Kontrol Periyodu -->
                <div class="form-field">
                    <label for="control_period">Kontrol Periyodu:</label>
                    <input type="text" autocomplete="off" name="control_period" id="control_period" class="form-control" placeholder="Örn: 1 Yıl / 6 Ay" value="<?php echo htmlspecialchars($reports["control_period"] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Onaylayan Yetkili -->
                <div class="form-field">
                    <label for="company_official"><font color="red">(*)</font> Onaylayan Yetkili:</label>
                    <select required name="company_official" id="company_official" class="form-control selectpicker" data-style="bg-white border">
                        <option value="">Yetkili Seçiniz</option>
                        <?php
                        $userquery = $ac->prepare("SELECT id, username, meslek FROM users WHERE id != 1 AND statu = 1 ORDER BY username ASC");
                        $userquery->execute();
                        while ($user = $userquery->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($reports["company_official"] == $user["id"]) ? "selected" : "";
                        ?>
                            <option value="<?php echo $user["id"]; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($user["username"] . ($user["meslek"] ? " - " . $user["meslek"] : ""), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Kart 2: Standartlar, Ekipman & Değerlendirme -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-purple">
                    <i class="fa fa-sliders"></i>
                </div>
                <div>
                    <h5>Standartlar, Ekipman & Rapor Notları</h5>
                    <p>İlgili mevzuat standartları, test ekipmanları, ikazlar ve yasal bildirim metni</p>
                </div>
            </div>

            <div class="form-grid">
                <!-- İlgili Standartlar (Zengin Metin Editörü) -->
                <div class="form-field full-width">
                    <label for="standarts">İlgili Standartlar:</label>
                    <div class="html-editor">
                        <textarea name="standarts" id="standarts" class="textarea_editor form-control" style="height: 120px; resize: vertical;" placeholder="İlgili Standartları yazınız"><?php echo htmlspecialchars($reports["standarts"] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>

                <!-- Test Sırasında Kullanılan Ekipmanlar -->
                <div class="form-field full-width">
                    <label for="equipment">Test Sırasında Kullanılan Ekipmanlar:</label>
                    <textarea name="equipment" id="equipment" class="form-control" style="height: 90px; resize: vertical;" placeholder="Kullanılan cihaz ve ekipmanları yazınız"><?php echo htmlspecialchars($reports["equipments"] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <!-- İkaz ve Uyarılar (Zengin Metin Editörü) -->
                <div class="form-field full-width">
                    <label for="warnings">İkaz ve Uyarılar:</label>
                    <div class="html-editor">
                        <textarea name="warnings" id="warnings" class="textarea_editor form-control" style="height: 120px; resize: vertical;" placeholder="İkaz ve uyarıları yazınız"><?php echo htmlspecialchars($reports["warnings"] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>

                <!-- Sonuç ve Kanaat -->
                <div class="form-field full-width">
                    <label for="notes">Sonuç ve Kanaat:</label>
                    <textarea name="notes" id="notes" class="form-control" style="height: 90px; resize: vertical;" placeholder="Rapor hakkında genel sonuç ve kanaat yazınız"><?php echo htmlspecialchars($reports["notes"] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <!-- Alt Bilgi -->
                <div class="form-field full-width">
                    <label for="subNotes">Yasal Alt Bilgi & Kriterler:</label>
                    <textarea name="subNotes" id="subNotes" class="form-control" style="min-height: 80px; resize: vertical;" placeholder="Yasal bildirim alt metni"><?php echo htmlspecialchars($reports["subNotes"] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Kart 3: Cihaz Kontrol Bilgileri Tablosu -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center">
                    <div class="card-icon card-icon-green mr-3">
                        <i class="fa fa-th-list"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Cihaz Muayene & Kontrol Listesi</h5>
                        <p class="mb-0">Periyodik kontrolü gerçekleştirilen yangın söndürme tüplerinin teknik parametreleri</p>
                    </div>
                </div>
            </div>

            <!-- Table Actions Toolbar -->
            <div class="table-actions-toolbar">
                <div class="table-actions-left">
                    <button type="button" class="btn btn-sm btn-primary font-12" id="addRow" style="border-radius: 6px; padding: 5px 12px;">
                        <i class="fa fa-plus-circle mr-1"></i> Yeni Satır
                    </button>
                    <button type="button" class="btn btn-sm btn-success font-12" data-toggle="modal" data-target="#exampleModalCenter" id="addMultiRow" style="border-radius: 6px; padding: 5px 12px;">
                        <i class="fa fa-clone mr-1"></i> Çoklu Satır Ekle
                    </button>
                    <button type="button" class="btn btn-sm btn-info text-white font-12" data-toggle="modal" data-target="#uploadfromxlsModal" style="border-radius: 6px; padding: 5px 12px;">
                        <i class="fa fa-file-excel-o mr-1"></i> Excel'den Yükle
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-danger font-12" id="deleteAll" style="border-radius: 6px; padding: 5px 12px;">
                        <i class="fa fa-trash-o mr-1"></i> Tümünü Temizle
                    </button>
                </div>
            </div>

            <!-- Cihaz Tablosu (Minimal Premium-Table) -->
            <div class="ysc-table-wrapper">
                <div class="ysc-table-responsive">
                    <table id="yscTable" class="table premium-table ysc-table">
                        <thead>
                            <tr class="main-head">
                                <th rowspan="2" style="width: 44px;">İşlem</th>
                                <th rowspan="2" style="width: 55px;">Sıra</th>
                                <th rowspan="2" style="min-width: 150px;">Bulunduğu Bölge</th>
                                <th rowspan="2" style="min-width: 150px;">Cihaz Cinsi</th>
                                <th colspan="2">Cihaz Kullanım Tarihleri</th>
                                <th colspan="2">Kontrol Tarihleri</th>
                                <th colspan="2">Kontrollerde Yapılan İşlemler</th>
                                <th rowspan="2" style="min-width: 100px;">Dış Muhafaza</th>
                                <th rowspan="2" style="min-width: 100px;">Çevre Kontrolü</th>
                                <th rowspan="2" style="min-width: 100px;">Pim Mühür</th>
                                <th rowspan="2" style="min-width: 100px;">Manometre</th>
                                <th rowspan="2" style="min-width: 100px;">Hortum/Nozül</th>
                                <th rowspan="2" style="min-width: 100px;">Talimat</th>
                                <th rowspan="2" style="min-width: 100px;">Ağırlık</th>
                            </tr>
                            <tr>
                                <th style="min-width: 85px;">Dolum</th>
                                <th style="min-width: 85px;">Son Kul.</th>
                                <th style="min-width: 95px;">1. Kontrol</th>
                                <th style="min-width: 95px;">2. Kontrol</th>
                                <th style="min-width: 110px;">1. İşlem</th>
                                <th style="min-width: 110px;">2. İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $contquery = $ac->prepare("SELECT * FROM report_ysc_content WHERE report_id = ? ORDER BY id ASC");
                            $contquery->execute([$reports["id"]]);
                            $rows = $contquery->fetchAll(PDO::FETCH_ASSOC);

                            if (count($rows) > 0) {
                                foreach ($rows as $i => $content) {
                                    $tabindex = $i;
                                    $cihaz_no = $content["cihaz_no"];
                                    $cihazbolge = $content["bulundugu_bolge"];
                                    $cinsi = $content["cinsi"];
                                    $dolumtarihi = $content["cihaz_dolum_tarihi"];
                                    $sonkullanimtarihi = $content["cihaz_sonkullanma_tarihi"];
                                    $kontoltarihi1 = $content["kontrol_tarihi_1"];
                                    $kontoltarihi2 = $content["kontrol_tarihi_2"];
                                    $islemkontroltarihi1 = $content["islem_kontrol_tarihi_1"];
                                    $islemkontroltarihi2 = $content["islem_kontrol_tarihi_2"];
                                    $dismuhafaza = $content["dis_muhafaza"];
                                    $cevrekontrolu = $content["cevre_kontrolu"];
                                    $pimkontrolu = $content["pim_kontrolu"];
                                    $manometrekontrolu = $content["manometre_kontrolu"];
                                    $hortumkontrolu = $content["hortum_kontrolu"];
                                    $talimatkontrolu = $content["talimat_kontrolu"];
                                    $agirlikkontrolu = $content["agirlik_kontrolu"];

                                    include "report-row-ysc.php";
                                }
                            } else {
                                $tabindex = 1;
                                $dismuhafaza = 1;
                                $cevrekontrolu = 1;
                                $pimkontrolu = 1;
                                $manometrekontrolu = 1;
                                $hortumkontrolu = 1;
                                $talimatkontrolu = 1;
                                $agirlikkontrolu = 1;
                                include "report-row-ysc.php";
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="17" class="py-2 px-3 bg-light">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="d-flex align-items-center">
                                            <button type="button" class="btn btn-sm btn-primary font-12" onclick="$('#addRow').click();" style="border-radius: 6px; padding: 4px 10px;">
                                                <i class="fa fa-plus mr-1"></i> Yeni Satır
                                            </button>
                                            <span class="text-muted font-11 ml-2">İpucu: Hücreler arasında yön tuşları ile geçiş yapabilirsiniz.</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Çoklu Satır Ekleme Modalı -->
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-light border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3" style="width: 38px; height: 38px;">
                        <i class="fa fa-clone"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0" id="exampleModalCenterTitle">Çoklu Satır Ekle</h5>
                        <small class="text-muted">Tabloya tek seferde birden fazla cihaz satırı ekleyin</small>
                    </div>
                </div>
                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close" style="outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group mb-2">
                    <label for="eklenecek_satir_sayisi" class="font-weight-600 text-dark mb-2">Eklenecek Satır Sayısı (Maksimum 100)</label>
                    <input type="number" min="1" max="100" class="form-control font-weight-bold" id="eklenecek_satir_sayisi" placeholder="Örn: 10" style="border-radius: 10px; padding: 10px;">
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-3 px-4">
                <button type="button" class="btn btn-secondary px-4 font-13" data-dismiss="modal" style="border-radius: 8px;">Vazgeç</button>
                <button type="button" class="btn btn-primary px-4 font-13" data-dismiss="modal" id="addMultiRowModal" style="border-radius: 8px;">
                    <i class="fa fa-plus mr-1"></i> Satırları Ekle
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once "upload-from-xls-modal.php"; ?>

<script src="include/js/ysc.js?v=<?php echo file_exists('include/js/ysc.js') ? filemtime('include/js/ysc.js') : time(); ?>"></script>