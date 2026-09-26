<?php

use App\Helper\Security;

$userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
$userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? 0));

if ($userPerm !== 1 && $userId !== 1 && $userId !== 12) {
    permcontrol("mail-logs-view");
}

$canDelete = permtrue("mail-logs-delete") || $userPerm === 1 || $userId === 1 || $userId === 12;

// Silme İşlemi (Güvenli ID ve Yetki Kontrolü)
if (isset($_GET["type"]) && $_GET["type"] === "delete" && isset($_GET["id"])) {
    if (!$canDelete) {
        header("Location: index.php?p=mail-logs&st=noperm");
        exit;
    }

    $logId = (int)$_GET["id"];
    
    // Log detayını al
    $checkStmt = $ac->prepare("SELECT * FROM mail_logs WHERE id = ?");
    $checkStmt->execute([$logId]);
    $logData = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($logData) {
        $delStmt = $ac->prepare("DELETE FROM mail_logs WHERE id = ?");
        $delStmt->execute([$logId]);

        if (function_exists('audit_log')) {
            audit_log(
                "delete",
                "mail-logs",
                "Mail günlüğü silindi: #" . $logId . " (" . ($logData['tomail'] ?? '') . ")",
                "mail_logs",
                $logId,
                $logData
            );
        }
        header("Location: index.php?p=mail-logs&st=deleted");
        exit;
    } else {
        header("Location: index.php?p=mail-logs&st=notfound");
        exit;
    }
}

// İstatistikler (KPI)
$statsStmt = $ac->query("
    SELECT 
        COUNT(*) AS total_count,
        SUM(CASE WHEN statu = 1 THEN 1 ELSE 0 END) AS success_count,
        SUM(CASE WHEN statu = 0 THEN 1 ELSE 0 END) AS failed_count,
        SUM(CASE WHEN mail_file IS NOT NULL AND TRIM(mail_file) != '' THEN 1 ELSE 0 END) AS file_count
    FROM mail_logs
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_count' => 0,
    'success_count' => 0,
    'failed_count' => 0,
    'file_count' => 0
];

$totalLogsCount = (int)$stats['total_count'];
$successLogsCount = (int)$stats['success_count'];
$failedLogsCount = (int)$stats['failed_count'];
$fileLogsCount = (int)$stats['file_count'];
$successRate = $totalLogsCount > 0 ? round(($successLogsCount / $totalLogsCount) * 100, 1) : 0;

// Tüm Mail Kayıtları
$logsStmt = $ac->prepare("SELECT * FROM mail_logs ORDER BY id DESC");
$logsStmt->execute();
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

$st = $_GET['st'] ?? '';
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_maillogs_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-maillogs-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    .kpi-maillogs-collapsed-early #kpiSummarySection {
        display: none !important;
    }

    .mail-list-wrapper {
        width: 100%;
    }

    /* Page Header Styles */
    .page-title-box {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .page-title-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
    }
    .page-title-text h4 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.3px;
    }
    .page-title-text p {
        margin: 1px 0 0 0;
        font-size: 12px;
        color: #64748b;
    }

    /* Action Buttons in Header */
    .btn-action-primary {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #fff !important;
        border: none;
        border-radius: 6px;
        padding: 6px 14px;
        font-weight: 600;
        font-size: 12.5px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);
        transition: all 0.2s ease;
        height: 34px;
        text-decoration: none;
    }
    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(2, 132, 199, 0.35);
        color: #fff !important;
    }
    .btn-action-outline {
        border-radius: 6px;
        padding: 6px 12px;
        height: 34px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
    }

    /* CRM KPI Cards (Compact) */
    .crm-kpi-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 12px 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .crm-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }
    .crm-kpi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .crm-kpi-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #64748b;
        display: block;
        margin-bottom: 2px;
    }
    .crm-kpi-value {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.1;
    }
    .crm-kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .icon-primary { background: #eff6ff; color: #2563eb; }
    .icon-emerald { background: #ecfdf5; color: #059669; }
    .icon-rose    { background: #fff1f2; color: #e11d48; }
    .icon-purple  { background: #faf5ff; color: #9333ea; }

    .crm-kpi-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 6px;
        border-top: 1px solid #f1f5f9;
        font-size: 11px;
    }
    .crm-badge-soft {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 10.5px;
    }
    .soft-primary { background: #dbeafe; color: #1e40af; }
    .soft-emerald { background: #d1fae5; color: #065f46; }
    .soft-rose    { background: #ffe4e6; color: #9f1239; }
    .soft-purple  { background: #f3e8ff; color: #6b21a8; }

    /* KPI Collapse Animation */
    .kpi-summary-collapse {
        transition: all 0.3s ease;
    }
    .kpi-summary-collapse.is-collapsed {
        display: none !important;
    }

    /* Form & Table Card styling (Compact Header, Flush Table) */
    .form-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 0 !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        margin-bottom: 0;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 8px;
    }

    .form-card-header .header-left-inner {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-card-header .card-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        background: #f1f5f9;
        color: #475569;
    }

    .form-card-header h5 {
        margin: 0;
        font-size: 14.5px;
        font-weight: 700;
        color: #1e293b;
    }
    .form-card-header p {
        margin: 1px 0 0 0;
        font-size: 11.5px;
        color: #64748b;
    }

    .mail-body-preview {
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
        font-size: 12.5px;
        color: #334155;
    }
    .mail-body-preview:hover {
        color: #0284c7;
        text-decoration: underline;
    }

    /* Ek Dosya Badge (Yüksek Kontrastlı ve Okunabilir) */
    .file-attachment-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        color: #1e293b;
        max-width: 200px;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .file-attachment-badge:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .file-attachment-badge .file-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
        vertical-align: middle;
    }
    .file-attachment-badge i {
        color: #0284c7;
        font-size: 12px;
        flex-shrink: 0;
    }

    /* Tablo Kompakt & Sıfır Kenar Boşluğu (Flush) */
    #mailLogsTable {
        border-top: none !important;
        border-left: none !important;
        border-right: none !important;
        margin-bottom: 0 !important;
    }
    #mailLogsTable th {
        font-size: 12px;
        font-weight: 600;
        padding: 8px 12px;
        background: #f8fafc;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        border-top: none !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }
    #mailLogsTable td {
        font-size: 12.5px;
        padding: 8px 12px;
        vertical-align: middle;
    }

    /* Premium Modal Tasarımı */
    .custom-mail-modal-content {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        background: #ffffff;
    }
    .custom-mail-modal-header {
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        padding: 14px 20px;
    }
    .modal-header-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);
    }
    .modal-meta-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .meta-label {
        font-size: 10.5px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.3px;
        color: #64748b;
        margin-bottom: 2px;
        display: block;
    }
    .meta-val {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        word-break: break-all;
    }
    .mail-content-display {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px;
        min-height: 120px;
        max-height: 340px;
        overflow-y: auto;
        white-space: pre-wrap;
        font-size: 13px;
        line-height: 1.6;
        color: #1e293b;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
</style>

<div class="mail-list-wrapper">
    <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap px-1" style="gap: 12px;">
        <div class="page-title-box">
            <div class="page-title-icon">
                <i class="fa fa-envelope-o"></i>
            </div>
            <div class="page-title-text">
                <h4>E-Posta Gönderim Kayıtları</h4>
                <p>Sistem üzerinden gönderilen tüm e-postaların durum ve iletim geçmişi</p>
            </div>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <button type="button" class="btn btn-outline-secondary btn-action-outline" id="kpiToggleBtn" title="Özet Kartları Göster/Gizle">
                <i class="fa fa-bar-chart"></i> <span class="d-none d-sm-inline">Özet Kartlar</span>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshLogs" title="Sayfayı Yenile" onclick="window.location.reload();">
                <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
            </button>
            <?php if (permtrue("mailandsmssend") || sesset("permission") == 1): ?>
                <a href="index.php?p=send-mail" class="btn btn-action-primary">
                    <i class="fa fa-paper-plane"></i> <span>Yeni E-Posta Gönder</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($st === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show border-radius-8 shadow-sm mb-3 mx-1" role="alert">
            <i class="fa fa-check-circle mr-2"></i> Mail kaydı başarıyla silindi.
            <button type="button" class="close" data-dismiss="alert" aria-label="Kapat">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php elseif ($st === 'noperm'): ?>
        <div class="alert alert-danger alert-dismissible fade show border-radius-8 shadow-sm mb-3 mx-1" role="alert">
            <i class="fa fa-exclamation-triangle mr-2"></i> Bu işlem için silme yetkiniz bulunmamaktadır.
            <button type="button" class="close" data-dismiss="alert" aria-label="Kapat">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php elseif ($st === 'notfound'): ?>
        <div class="alert alert-warning alert-dismissible fade show border-radius-8 shadow-sm mb-3 mx-1" role="alert">
            <i class="fa fa-info-circle mr-2"></i> Silinmek istenen mail kaydı bulunamadı.
            <button type="button" class="close" data-dismiss="alert" aria-label="Kapat">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Özet Bilgiler (CRM KPI Kartları) -->
    <div id="kpiSummarySection" class="row mx-0 mb-3 kpi-summary-collapse">
        <!-- Toplam Gönderim -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-2 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Toplam Gönderim</span>
                        <div class="crm-kpi-value"><?php echo number_format($totalLogsCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-primary">
                        <i class="fa fa-paper-plane"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Sistem Kayıtları</span>
                    <span class="crm-badge-soft soft-primary">Tüm Mailler</span>
                </div>
            </div>
        </div>

        <!-- Başarılı İletim -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-2 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Başarılı İletim</span>
                        <div class="crm-kpi-value"><?php echo number_format($successLogsCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-emerald">
                        <i class="fa fa-check-circle"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">İletilme Oranı</span>
                    <span class="crm-badge-soft soft-emerald">%<?php echo $successRate; ?> Başarı</span>
                </div>
            </div>
        </div>

        <!-- Başarısız / Hatalı -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-2 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Başarısız / Hatalı</span>
                        <div class="crm-kpi-value"><?php echo number_format($failedLogsCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-rose">
                        <i class="fa fa-times-circle"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Gönderilemedi</span>
                    <span class="crm-badge-soft soft-rose"><?php echo $failedLogsCount; ?> Adet</span>
                </div>
            </div>
        </div>

        <!-- Ekli Dosya Gönderimi -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-2 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Ek Dosyalı</span>
                        <div class="crm-kpi-value"><?php echo number_format($fileLogsCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-purple">
                        <i class="fa fa-paperclip"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Ek İçerenler</span>
                    <span class="crm-badge-soft soft-purple"><?php echo $fileLogsCount; ?> Adet</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste Card (Kompakt ve Temiz - Sıfır Kenar Boşluğu) -->
    <div class="form-card mx-1">
        <div class="form-card-header d-flex justify-content-between align-items-center">
            <div class="header-left-inner">
                <div class="card-icon">
                    <i class="fa fa-list"></i>
                </div>
                <div>
                    <h5>Gönderilen E-Posta Listesi</h5>
                    <p>İletim durumu, alıcı bilgisi ve içerik detayları</p>
                </div>
            </div>
            <div class="header-right-inner d-flex align-items-center"></div>
        </div>

        <div class="table-responsive">
            <table class="data-table table table-hover table-striped table-bordered text-nowrap" id="mailLogsTable" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">#</th>
                        <th>Alıcı (To)</th>
                        <th>Kopya (CC)</th>
                        <th>Gönderen (From)</th>
                        <th>İçerik Özeti</th>
                        <th>Ek Dosya</th>
                        <th>Gönderen Personel</th>
                        <th>Tarih & Saat</th>
                        <th class="text-center" style="width: 80px;">Durum</th>
                        <th class="text-center" style="width: 70px;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $seq = 1;
                    foreach ($logs as $row):
                        $id = (int)$row['id'];
                        $toMail = htmlspecialchars($row['tomail'] ?? '', ENT_QUOTES, 'UTF-8');
                        $ccMail = htmlspecialchars($row['cc_mail'] ?? '', ENT_QUOTES, 'UTF-8');
                        $fromMail = htmlspecialchars($row['from_mail'] ?? '', ENT_QUOTES, 'UTF-8');
                        $mailBody = $row['mail_body'] ?? '';
                        $bodySummary = htmlspecialchars(function_exists('shorted') ? shorted(strip_tags($mailBody), 30) : mb_substr(strip_tags($mailBody), 0, 30) . '...', ENT_QUOTES, 'UTF-8');
                        $mailFile = htmlspecialchars(trim($row['mail_file'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $dateStr = !empty($row['datest']) ? date('d.m.Y H:i', strtotime($row['datest'])) : '-';
                        $senderName = function_exists('getUsername') ? htmlspecialchars(getUsername($row['sender']) ?: 'Sistem', ENT_QUOTES, 'UTF-8') : 'Sistem';
                        $isSuccess = ((int)$row['statu'] === 1);
                    ?>
                        <tr>
                            <td class="text-center font-weight-bold text-muted"><?php echo $seq++; ?></td>
                            <td>
                                <span class="font-weight-bold text-dark"><?php echo $toMail; ?></span>
                            </td>
                            <td>
                                <?php if (!empty($ccMail)): ?>
                                    <span class="text-muted font-12" title="<?php echo $ccMail; ?>">
                                        <i class="fa fa-copy text-secondary mr-1 font-11"></i><?php echo $ccMail; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted font-12">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-muted font-12"><?php echo $fromMail ?: '-'; ?></span>
                            </td>
                            <td>
                                <div class="mail-body-preview" onclick="viewMailDetail(<?php echo $id; ?>, '<?php echo addslashes($toMail); ?>', '<?php echo addslashes($ccMail); ?>', '<?php echo addslashes($fromMail); ?>', '<?php echo addslashes($dateStr); ?>', '<?php echo addslashes($mailFile); ?>', '<?php echo $isSuccess ? '1' : '0'; ?>', '<?php echo addslashes($senderName); ?>')" title="İçeriği görmek için tıklayın">
                                    <i class="fa fa-file-text-o text-primary mr-1"></i>
                                    <?php echo !empty($bodySummary) ? $bodySummary : '<span class="text-muted font-italic">Boş içerik</span>'; ?>
                                </div>
                                <!-- Gizli tam içerik -->
                                <textarea id="mail_raw_<?php echo $id; ?>" style="display:none;"><?php echo htmlspecialchars($mailBody, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </td>
                            <td>
                                <?php if (!empty($mailFile)): ?>
                                    <span class="file-attachment-badge" title="<?php echo $mailFile; ?>">
                                        <i class="fa fa-paperclip"></i>
                                        <span class="file-name"><?php echo $mailFile; ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary font-12">
                                    <i class="fa fa-user-circle-o mr-1"></i> <?php echo $senderName; ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-nowrap font-12"><i class="fa fa-clock-o text-muted mr-1"></i> <?php echo $dateStr; ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($isSuccess): ?>
                                    <span class="badge badge-success px-2 py-1 font-11">
                                        <i class="fa fa-check mr-1"></i> İletildi
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger px-2 py-1 font-11">
                                        <i class="fa fa-times mr-1"></i> Başarısız
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="viewMailDetail(<?php echo $id; ?>, '<?php echo addslashes($toMail); ?>', '<?php echo addslashes($ccMail); ?>', '<?php echo addslashes($fromMail); ?>', '<?php echo addslashes($dateStr); ?>', '<?php echo addslashes($mailFile); ?>', '<?php echo $isSuccess ? '1' : '0'; ?>', '<?php echo addslashes($senderName); ?>')" title="Detay">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <?php if ($canDelete): ?>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $id; ?>)" class="btn btn-outline-danger" title="Sil">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Premium E-Posta Detay Modalı -->
<div class="modal fade" id="mailDetailModal" tabindex="-1" role="dialog" aria-labelledby="mailDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content custom-mail-modal-content">
            <!-- Modal Header -->
            <div class="modal-header custom-mail-modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <div class="modal-header-icon-box">
                        <i class="fa fa-envelope-open-o"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold mb-0" id="mailDetailModalLabel" style="font-size: 16px; color: #1e293b;">
                            E-Posta Gönderim Detayı
                        </h5>
                        <span class="text-muted font-12" id="modalSubtitle">İletim bilgisi ve mesaj içeriği</span>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat" style="font-size: 24px; color: #94a3b8; outline: none; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- Üst Bilgi Kartları -->
                <div class="row mb-3">
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">Alıcı (To)</span>
                            <strong id="modalToMail" class="meta-val text-primary">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">Kopya (CC)</span>
                            <strong id="modalCcMail" class="meta-val text-secondary">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">Gönderen (From)</span>
                            <strong id="modalFromMail" class="meta-val">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">Gönderen Personel</span>
                            <span id="modalSender" class="meta-val">-</span>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">Tarih & Saat</span>
                            <span id="modalDate" class="meta-val">-</span>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">İletim Durumu</span>
                            <div id="modalStatus" class="mt-1">-</div>
                        </div>
                    </div>
                    <div class="col-12 mt-1" id="modalFileWrap" style="display:none;">
                        <div class="modal-meta-box">
                            <span class="meta-label">Ekli Dosya</span>
                            <div id="modalFile" class="mt-1">-</div>
                        </div>
                    </div>
                </div>

                <!-- E-Posta Gövdesi -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="font-weight-bold text-dark mb-0 font-13">
                            <i class="fa fa-align-left text-primary mr-1"></i> Gönderilen E-Posta Metni
                        </label>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-11" id="btnCopyBody" onclick="copyMailBody()">
                            <i class="fa fa-copy mr-1"></i> Metni Kopyala
                        </button>
                    </div>
                    <div id="modalMailBody" class="mail-content-display"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <small class="text-muted font-11" id="modalLogIdText">Kayıt # -</small>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-dismiss="modal" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewMailDetail(id, toMail, ccMail, fromMail, dateStr, mailFile, status, senderName) {
    let raw = document.getElementById('mail_raw_' + id);
    let body = raw ? raw.value : '';

    document.getElementById('modalToMail').textContent = toMail || '-';
    document.getElementById('modalCcMail').textContent = ccMail || '-';
    document.getElementById('modalFromMail').textContent = fromMail || '-';
    document.getElementById('modalSender').textContent = senderName || 'Sistem';
    document.getElementById('modalDate').textContent = dateStr || '-';
    document.getElementById('modalLogIdText').textContent = 'Kayıt ID: #' + id;
    
    let statusEl = document.getElementById('modalStatus');
    if (status === '1') {
        statusEl.innerHTML = '<span class="badge badge-success px-2 py-1"><i class="fa fa-check mr-1"></i> Başarıyla İletildi</span>';
    } else {
        statusEl.innerHTML = '<span class="badge badge-danger px-2 py-1"><i class="fa fa-times mr-1"></i> İletilemedi / Hatalı</span>';
    }

    let fileWrap = document.getElementById('modalFileWrap');
    let fileEl = document.getElementById('modalFile');
    if (mailFile && mailFile.trim() !== '') {
        fileWrap.style.display = 'block';
        fileEl.innerHTML = '<span class="file-attachment-badge"><i class="fa fa-paperclip"></i> <span class="file-name">' + $('<div>').text(mailFile).html() + '</span></span>';
    } else {
        fileWrap.style.display = 'none';
    }

    document.getElementById('modalMailBody').textContent = body ? body : '(Boş mesaj içeriği)';
    $('#mailDetailModal').modal('show');
}

function copyMailBody() {
    let text = document.getElementById('modalMailBody').textContent;
    if (!text) return;

    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            let btn = document.getElementById('btnCopyBody');
            btn.innerHTML = '<i class="fa fa-check text-success mr-1"></i> Kopyalandı!';
            setTimeout(function() {
                btn.innerHTML = '<i class="fa fa-copy mr-1"></i> Metni Kopyala';
            }, 2000);
        });
    } else {
        let textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        let btn = document.getElementById('btnCopyBody');
        btn.innerHTML = '<i class="fa fa-check text-success mr-1"></i> Kopyalandı!';
        setTimeout(function() {
            btn.innerHTML = '<i class="fa fa-copy mr-1"></i> Metni Kopyala';
        }, 2000);
    }
}

function confirmDelete(id) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Kayıt Silinecek',
            text: 'Bu e-posta kaydını silmek istediğinize emin misiniz?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?p=mail-logs&type=delete&id=' + id;
            }
        });
    } else {
        if (confirm('Bu e-posta kaydını silmek istediğinize emin misiniz?')) {
            window.location.href = 'index.php?p=mail-logs&type=delete&id=' + id;
        }
    }
}

$(document).ready(function() {
    // KPI Collapse Durumu Kontrolü
    var $kpiSection = $('#kpiSummarySection');
    var $kpiBtn = $('#kpiToggleBtn');
    
    if (localStorage.getItem('aydinogullari_kpi_maillogs_collapsed') === 'true') {
        $kpiSection.addClass('is-collapsed');
        $kpiBtn.addClass('active btn-secondary').removeClass('btn-outline-secondary');
    }

    $kpiBtn.on('click', function(e) {
        e.preventDefault();
        $kpiSection.toggleClass('is-collapsed');
        var isCollapsed = $kpiSection.hasClass('is-collapsed');
        localStorage.setItem('aydinogullari_kpi_maillogs_collapsed', isCollapsed);
        
        if (isCollapsed) {
            $kpiBtn.addClass('active btn-secondary').removeClass('btn-outline-secondary');
        } else {
            $kpiBtn.removeClass('active btn-secondary').addClass('btn-outline-secondary');
        }
    });

    // DataTable Başlatma (table-filter.js ile tam uyumlu)
    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#mailLogsTable')) {
        $('#mailLogsTable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tümü"]],
            order: [[0, 'asc']]
        });
    }
});
</script>