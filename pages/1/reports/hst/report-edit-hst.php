<?php
// Rapor Düzenleme Yetkisi
permcontrol("reportedit");

$id = (int)($_GET["id"] ?? 0);
$type = $_GET["type"] ?? 2;

if ($id <= 0) {
    header("Location: index.php?p=reports/reports");
    exit;
}

if ($_POST) {
    $report_number = $_POST["report_number"] ?? '';
    $servisno = $_POST["servisno"] ?? '';
    $customer_id = (int)($_POST["customer"] ?? 0);
    $test_date = $_POST["test_date"] ?? '';
    $notes = $_POST["notes"] ?? '';
    $updater = sesset("id");
    $update_time = date("Y-m-d H:i:s");

    $testno = $_POST["testno"] ?? [];
    $cihazSayisi = count($testno);

    if (empty($customer_id)) {
        header("Location: index.php?p=reports/hst/report-edit-hst&id=" . $id . "&type=2&st=empties");
        exit;
    }

    try {
        $sql = $ac->prepare("UPDATE reports SET 
            report_number = ?, 
            report_type = ?, 
            isemrino = ?, 
            customer_id = ?, 
            test_date = ?, 
            notes = ?, 
            updater = ?,
            update_time = ? 
            WHERE id = ?");
        $sql->execute([$report_number, 2, $servisno, $customer_id, $test_date, $notes, $updater, $update_time, $id]);

        $kg = $_POST["kg"] ?? [];
        $cinsi = $_POST["cinsi"] ?? [];
        $imalatci_firma = $_POST["imalatci_firma"] ?? [];
        $imal_tarihi = $_POST["imal_tarihi"] ?? [];
        $serino = $_POST["serino"] ?? [];
        $tse_belgesi = $_POST["tse_belgesi"] ?? [];
        $yuzey_durumu = $_POST["yuzey_durumu"] ?? [];
        $sizdirmazlik_deneyi = $_POST["sizdirmazlik_deneyi"] ?? [];
        $esneme_deneyi = $_POST["esneme_deneyi"] ?? [];
        $things = $_POST["things"] ?? [];

        // Eski satırları temizle ve yenilerini ekle
        $delquery = $ac->prepare("DELETE FROM report_hst_content WHERE report_id = ?");
        $delquery->execute([$id]);

        if ($cihazSayisi > 0) {
            $rowquery = $ac->prepare("INSERT INTO report_hst_content SET 
                report_id = ?, 
                testno = ?, 
                kg = ?, 
                cinsi = ?, 
                imalatci_firma = ?, 
                imal_tarihi = ?, 
                serino = ?, 
                tse_belgesi = ?, 
                yuzey_durumu = ?, 
                sizdirmazlik_deneyi = ?, 
                esneme_deneyi = ?, 
                things = ?");

            for ($i = 0; $i < $cihazSayisi; $i++) {
                if (empty($testno[$i]) && empty($cinsi[$i])) {
                    continue;
                }
                $rowquery->execute([
                    $id,
                    $testno[$i] ?? '',
                    $kg[$i] ?? '',
                    $cinsi[$i] ?? '',
                    $imalatci_firma[$i] ?? '',
                    $imal_tarihi[$i] ?? '',
                    $serino[$i] ?? '',
                    $tse_belgesi[$i] ?? '1',
                    $yuzey_durumu[$i] ?? '1',
                    $sizdirmazlik_deneyi[$i] ?? '1',
                    $esneme_deneyi[$i] ?? '1',
                    $things[$i] ?? ''
                ]);
            }
        }

        audit_log("update", "report", "Hidrostatik Test Raporu güncellendi: " . $report_number, "reports", $id);

        header("Location: index.php?p=reports/hst/report-edit-hst&id=" . $id . "&type=2&st=newsuccess");
        exit;

    } catch (PDOException $e) {
        error_log("HST Raporu Güncelleme Hatası: " . $e->getMessage());
        header("Location: index.php?p=reports/hst/report-edit-hst&id=" . $id . "&type=2&st=error");
        exit;
    }
}

$st = $_GET["st"] ?? '';
?>

<?php if ($st === "newsuccess"): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: 'Başarılı!',
            text: 'Hidrostatik Test Raporu başarıyla güncellendi.',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-list"></i> Rapor Listesine Git',
            cancelButtonText: '<i class="fa fa-pencil"></i> Düzenlemeye Devam Et',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = 'index.php?p=reports/reports';
            } else {
                window.history.replaceState({}, document.title, 'index.php?p=reports/hst/report-edit-hst&id=<?php echo (int)$id; ?>&type=2');
            }
        });
    } else {
        alert('Hidrostatik Test Raporu başarıyla güncellendi!');
    }
});
</script>
<?php elseif ($st === "empties"): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: 'Eksik Bilgi!',
            text: 'Lütfen tüp sahibi firmayı ve zorunlu alanları doldurunuz.',
            icon: 'warning',
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#f59e0b'
        }).then(function() {
            window.history.replaceState({}, document.title, 'index.php?p=reports/hst/report-edit-hst&id=<?php echo (int)$id; ?>&type=2');
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
            text: 'Rapor güncellenirken bir hata oluştu. Lütfen tekrar deneyiniz.',
            icon: 'error',
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#ef4444'
        }).then(function() {
            window.history.replaceState({}, document.title, 'index.php?p=reports/hst/report-edit-hst&id=<?php echo (int)$id; ?>&type=2');
        });
    }
});
</script>
<?php endif; ?>

// Rapor Bilgilerini Çek
$sql = $ac->prepare("SELECT * FROM reports WHERE id = ?");
$sql->execute([$id]);
$report = $sql->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    header("Location: index.php?p=reports/reports");
    exit;
}
?>

<style>
    .hst-report-wrapper {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        padding-bottom: 40px;
    }

    /* Minimal Table Container Matching premium-theme */
    .hst-table-wrapper {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .hst-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .premium-table.hst-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 0;
        margin-bottom: 0;
        border: none !important;
    }

    .premium-table.hst-table thead th {
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

    .premium-table.hst-table thead tr.main-head th {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
    }

    .premium-table.hst-table td {
        padding: 4px 4px !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f8fafc;
        background: #ffffff;
    }

    .premium-table.hst-table tbody tr:hover td {
        background: #f8fafc;
    }

    .premium-table.hst-table .form-control {
        height: 30px !important;
        padding: 2px 6px !important;
        font-size: 12px !important;
        border-radius: 6px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff;
        transition: all 0.15s ease-in-out;
    }

    .premium-table.hst-table .form-control:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12) !important;
        background: #fff !important;
    }

    .premium-table.hst-table .btn-delete-row {
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

    .premium-table.hst-table .btn-delete-row:hover {
        background-color: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .premium-table.hst-table select.custom-select-status {
        font-size: 11px !important;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
        padding: 2px 4px !important;
    }

    .premium-table.hst-table select.custom-select-status option[value="1"] {
        color: #16a34a;
        font-weight: 600;
    }

    .premium-table.hst-table select.custom-select-status option[value="0"] {
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

    /* Dark Mode Overrides */
    .dark-mode .hst-table-wrapper {
        background: #1e293b;
        border-color: #334155;
    }

    .dark-mode .premium-table.hst-table thead th {
        background: #0f172a !important;
        color: #cbd5e1;
        border-color: #334155;
    }

    .dark-mode .premium-table.hst-table thead tr.main-head th {
        background: #1e293b !important;
        color: #f8fafc;
        border-color: #334155;
    }

    .dark-mode .premium-table.hst-table td {
        background: #1e293b;
        border-color: #334155;
        color: #e2e8f0;
    }

    .dark-mode .premium-table.hst-table tbody tr:hover td {
        background: #283548;
    }

    .dark-mode .premium-table.hst-table .form-control {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }

    .dark-mode .premium-table.hst-table .btn-delete-row {
        background-color: #450a0a;
        border-color: #7f1d1d;
        color: #f87171;
    }

    .dark-mode .premium-table.hst-table .btn-delete-row:hover {
        background-color: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }
</style>

<form enctype="multipart/form-data" id="myForm" method="POST">
    <div class="hst-report-wrapper">
        <!-- Header Card -->
        <div class="premium-header-card animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                        <i class="fa fa-tint"></i>
                    </div>
                    <div class="header-title">
                        <h4>Hidrostatik Test Raporunu Düzenle</h4>
                        <span class="header-number-badge">
                            <i class="fa fa-tag"></i> Rapor No: <?php echo htmlspecialchars($report["report_number"] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php?p=reports/hst/report-view-hst&id=<?php echo (int)$id; ?>" target="_blank" class="btn-header btn-header-list" style="background: #ef4444; border-color: #ef4444; color: #fff;">
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

        <!-- Kart 1: Genel Rapor & Tüp Sahibi Tanımı -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-building-o"></i>
                </div>
                <div>
                    <h5>Genel Rapor & Tüp Sahibi Tanımı</h5>
                    <p>Rapor numarası, müşteri seçimi, servis fişi ve deney tarihi</p>
                </div>
            </div>

            <div class="form-grid">
                <!-- Rapor No -->
                <div class="form-field">
                    <label for="report_number"><font color="red">(*)</font> Rapor No:</label>
                    <input required name="report_number" id="report_number" type="text" readonly value="<?php echo htmlspecialchars($report["report_number"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control font-weight-bold bg-light" placeholder="Rapor No">
                </div>

                <!-- Tüp Sahibi Firma -->
                <div class="form-field">
                    <label for="customer"><font color="red">(*)</font> Tüp Sahibi Firma:</label>
                    <select required name="customer" id="customer" data-live-search="true" data-size="10" class="form-control selectpicker" data-style="bg-white border">
                        <option disabled value="">Firma Seçiniz</option>
                        <?php
                        $compquery = $ac->prepare("SELECT id, company FROM customers WHERE deleted_at IS NULL ORDER BY company ASC");
                        $compquery->execute();
                        while ($company = $compquery->fetch(PDO::FETCH_ASSOC)) {
                            $isSelected = ((int)$company["id"] === (int)$report["customer_id"]) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $company["id"]; ?>" <?php echo $isSelected; ?>>
                                <?php echo htmlspecialchars($company["company"], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Servis Fiş No -->
                <div class="form-field">
                    <label for="servisno"><font color="red">(*)</font> Servis Fiş No / İş Emri:</label>
                    <input required type="text" name="servisno" id="servisno" class="form-control" value="<?php echo htmlspecialchars($report["isemrino"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Örn: SF-2026-001 veya SN123">
                </div>

                <!-- Deney Tarihi -->
                <div class="form-field">
                    <label for="test_date"><font color="red">(*)</font> Deney Tarihi:</label>
                    <input required type="text" autocomplete="off" name="test_date" id="test_date" class="form-control date-picker" value="<?php echo htmlspecialchars($report["test_date"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Deney Tarihi">
                </div>
            </div>

            <!-- Rapor Altı Açıklama -->
            <div class="form-field full-width mt-3">
                <label for="notes" class="font-weight-600 mb-1">Rapor Altı Açıklama:</label>
                <div class="html-editor">
                    <textarea style="height: 100px; resize: vertical;" name="notes" id="notes" class="textarea_editor form-control" placeholder="Rapor altına eklenecek açıklama metnini yazınız"><?php echo htmlspecialchars($report["notes"] != '' ? $report["notes"] : "Yukarıda künyesi belirtilen tüp / tüplerin 16-HYB-1004 Nolu 11.11.2010 Tarihli TSE HİZMET YETERLİLİK BELGESİ' ne dayanılarak bu rapor hazırlanmıştır.", ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Kart 2: Cihaz Test & Kontrol Bilgileri -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-green">
                    <i class="fa fa-flask"></i>
                </div>
                <div>
                    <h5>Cihaz / Tüp Test & Kontrol Bilgileri</h5>
                    <p>Hidrostatik deney ve kontrol sonuçları tablosu</p>
                </div>
            </div>

            <!-- Tablo İşlem Araç Çubuğu (Toolbar) -->
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
                    <span class="badge badge-primary px-3 py-2 font-13 font-weight-bold" id="rowCountBadge">1 Satır</span>
                </div>
            </div>

            <!-- Tablo -->
            <div class="hst-table-wrapper">
                <div class="hst-table-responsive">
                    <table id="hstTable" class="table premium-table hst-table mb-0">
                        <thead>
                            <tr class="main-head text-center">
                                <th style="width: 45px;"><i class="fa fa-cog"></i></th>
                                <th style="min-width: 90px;">Test No</th>
                                <th style="min-width: 80px;">Kg</th>
                                <th style="min-width: 110px;">Cinsi</th>
                                <th style="min-width: 140px;">İmalatçı Firma</th>
                                <th style="min-width: 95px;">İmal Tarihi</th>
                                <th style="min-width: 110px;">Seri No</th>
                                <th style="min-width: 100px;">TSE Belgesi</th>
                                <th style="min-width: 110px;">Yüzey Durumu</th>
                                <th style="min-width: 120px;">Sızdırmazlık Deneyi</th>
                                <th style="min-width: 110px;">Esneme Deneyi</th>
                                <th style="min-width: 180px;">Düşünceler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $contquery = $ac->prepare("SELECT * FROM report_hst_content WHERE report_id = ? ORDER BY id ASC");
                            $contquery->execute([$id]);
                            $satir = 0;

                            while ($content = $contquery->fetch(PDO::FETCH_ASSOC)) {
                                $tabindex = $satir;
                                $testno = $content["testno"];
                                $kg = $content["kg"];
                                $cinsi = $content["cinsi"];
                                $imalatci_firma = $content["imalatci_firma"];
                                $imal_tarihi = $content["imal_tarihi"];
                                $serino = $content["serino"];
                                $tse_belgesi = $content["tse_belgesi"];
                                $yuzey_durumu = $content["yuzey_durumu"];
                                $sizdirmazlik_deneyi = $content["sizdirmazlik_deneyi"];
                                $esneme_deneyi = $content["esneme_deneyi"];
                                $things = $content["things"];

                                include "report-row-hst.php";
                                $satir++;
                            }

                            if ($satir === 0) {
                                $tabindex = 0;
                                $testno = '';
                                $kg = '';
                                $cinsi = '';
                                $imalatci_firma = '';
                                $imal_tarihi = '';
                                $serino = '';
                                $tse_belgesi = 1;
                                $yuzey_durumu = 1;
                                $sizdirmazlik_deneyi = 1;
                                $esneme_deneyi = 1;
                                $things = '';
                                include "report-row-hst.php";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include_once "upload-from-xls-modal.php"; ?>
<?php include_once "addMultipleRow-modal.php"; ?>
<script src="src/plugins/xlsx/xlsx.full.min.js"></script>
<script src="include/js/hst.js"></script>