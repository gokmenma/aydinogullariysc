<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Model\MailAccountModel;

$canManage = permtrue("mail-accounts-manage") || permtrue("mailandsmssend") || (isset($_SESSION['perm']) && $_SESSION['perm'] == 1) || (isset($_SESSION['lid']) && in_array($_SESSION['lid'], [1, 12]));

$mailModel = new MailAccountModel();

// AJAX İstekleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_POST)) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');

    // Yetki kontrolü
    if (!$canManage) {
        echo json_encode([
            "status" => 403,
            "message" => "Bu işlem için yetkiniz bulunmamaktadır."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $action = $_POST["action"] ?? '';

    try {
        if ($action === "get_detail") {
            $id = (int)($_POST["id"] ?? 0);
            $account = $mailModel->getAccountById($id);
            if ($account) {
                echo json_encode([
                    "status" => 200,
                    "data" => $account
                ]);
            } else {
                echo json_encode([
                    "status" => 404,
                    "message" => "Mail hesabı bulunamadı."
                ]);
            }
            exit;
        }

        $id = (int)($_POST["id"] ?? 0);
        $mailAddress = trim($_POST["mail_address"] ?? '');
        $mailPassword = $_POST["mail_password"] ?? '';
        $description = trim($_POST["description"] ?? '');
        $accountType = (int)($_POST["account_type"] ?? 1);
        $mailUser = ($accountType === 2) ? (int)($_POST["mail_user"] ?? 1) : 1;

        if (empty($mailAddress)) {
            echo json_encode([
                "status" => 400,
                "message" => "Lütfen geçerli bir mail adresi giriniz."
            ]);
            exit;
        }

        if (!filter_var($mailAddress, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                "status" => 400,
                "message" => "Geçersiz e-posta formatı!"
            ]);
            exit;
        }

        // Mükerrer kontrolü
        if ($mailModel->isEmailExists($mailAddress, $action === "update" ? $id : null)) {
            echo json_encode([
                "status" => 400,
                "message" => "Bu e-posta adresi sistemde zaten kayıtlıdır."
            ]);
            exit;
        }

        if ($action === "new") {
            $createdId = $mailModel->createAccount([
                'mail_address'  => $mailAddress,
                'mail_password' => $mailPassword,
                'description'   => $description,
                'account_type'  => $accountType,
                'mail_user'     => $mailUser,
                'creator'       => $_SESSION['lid'] ?? 1
            ]);

            if ($createdId) {
                echo json_encode([
                    "status" => 200,
                    "message" => "Mail adresi başarıyla eklendi."
                ]);
            } else {
                echo json_encode([
                    "status" => 400,
                    "message" => "Mail adresi eklenirken bir sorun oluştu."
                ]);
            }
            exit;

        } else if ($action === "update") {
            if ($id <= 0) {
                echo json_encode([
                    "status" => 400,
                    "message" => "Geçersiz kayıt kimliği."
                ]);
                exit;
            }

            $updatePayload = [
                'mail_address' => $mailAddress,
                'description'  => $description,
                'account_type' => $accountType,
                'mail_user'    => $mailUser
            ];

            if ($mailPassword !== '') {
                $updatePayload['mail_password'] = $mailPassword;
            }

            $updated = $mailModel->updateAccount($id, $updatePayload);

            if ($updated) {
                echo json_encode([
                    "status" => 200,
                    "message" => "Mail adresi başarıyla güncellendi."
                ]);
            } else {
                echo json_encode([
                    "status" => 400,
                    "message" => "Güncelleme başarısız oldu."
                ]);
            }
            exit;

        } else if ($action === "delete") {
            if ($id <= 0) {
                echo json_encode([
                    "status" => 400,
                    "message" => "Geçersiz kayıt kimliği."
                ]);
                exit;
            }

            $deleted = $mailModel->deleteAccount($id);
            if ($deleted) {
                echo json_encode([
                    "status" => 200,
                    "message" => "Mail hesabı başarıyla silindi."
                ]);
            } else {
                echo json_encode([
                    "status" => 400,
                    "message" => "Silme işlemi gerçekleştirilemedi."
                ]);
            }
            exit;
        }

        echo json_encode([
            "status" => 400,
            "message" => "Bilinmeyen işlem türü."
        ]);
        exit;

    } catch (Exception $e) {
        error_log("send-mail-accounts POST Error: " . $e->getMessage());
        echo json_encode([
            "status" => 500,
            "message" => "Sunucu hatası: İşlem tamamlanamadı."
        ]);
        exit;
    }
}

// Sayfa verileri
$accounts = $mailModel->getAllAccounts();
$stats = $mailModel->getStats();
$usersList = $mailModel->getActiveUsers();
?>

<!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
<script>
    (function() {
        try {
            if (localStorage.getItem('aydinogullari_kpi_mailaccounts_collapsed') === 'true') {
                document.documentElement.classList.add('kpi-mailaccounts-collapsed-early');
            }
        } catch(e) {}
    })();
</script>

<style>
    .kpi-mailaccounts-collapsed-early #kpiSummarySection {
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
        cursor: pointer;
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
        text-decoration: none;
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

    /* Tablo Kompakt & Sıfır Kenar Boşluğu (Flush) */
    #mailAccountsTable {
        border-top: none !important;
        border-left: none !important;
        border-right: none !important;
        margin-bottom: 0 !important;
    }
    #mailAccountsTable th {
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
    #mailAccountsTable td {
        font-size: 12.5px;
        padding: 8px 12px;
        vertical-align: middle;
    }

    /* Segment Card Styling */
    .acc-type-radio:checked + .card-box {
        border-color: #0284c7 !important;
        background: #f0f9ff !important;
        box-shadow: 0 3px 10px rgba(2, 132, 199, 0.15) !important;
    }
    .acc-type-radio:checked + .card-box strong {
        color: #0369a1 !important;
    }
    .account-type-card:hover .card-box {
        border-color: #bae6fd !important;
        transform: translateY(-1px);
    }

    /* Yüksek Kontrastlı Özel Rozetler (Badge Styles) */
    .mail-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        padding: 4px 9px !important;
        border-radius: 6px !important;
        font-size: 11.5px !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
        letter-spacing: 0.1px !important;
        white-space: nowrap !important;
        text-decoration: none !important;
    }
    .mail-badge-smtp {
        background: #ecfdf5 !important;
        color: #047857 !important;
        border: 1px solid #6ee7b7 !important;
        font-size: 10.5px !important;
        padding: 2px 7px !important;
    }
    .mail-badge-general {
        background: #ecfdf5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
    }
    .mail-badge-user {
        background: #eff6ff !important;
        color: #1d4ed8 !important;
        border: 1px solid #bfdbfe !important;
    }
    .mail-badge-all-staff {
        background: #f8fafc !important;
        color: #1e293b !important;
        border: 1px solid #cbd5e1 !important;
        font-weight: 600 !important;
    }
    .mail-badge-creator {
        background: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #e2e8f0 !important;
        font-size: 11px !important;
        font-weight: 600 !important;
    }
</style>

<div class="mail-list-wrapper">
    <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap px-1" style="gap: 12px;">
        <div class="page-title-box">
            <div class="page-title-icon">
                <i class="fa fa-at"></i>
            </div>
            <div class="page-title-text">
                <h4>Mail Hesapları Yönetimi</h4>
                <p>Sistem üzerinden gönderilecek e-postalar için tanımlı genel ve kullanıcı hesapları</p>
            </div>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <button type="button" class="btn btn-outline-secondary btn-action-outline" id="kpiToggleBtn" title="Özet Kartları Göster/Gizle">
                <i class="fa fa-bar-chart"></i> <span class="d-none d-sm-inline">Özet Kartlar</span>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshLogs" title="Sayfayı Yenile" onclick="window.location.reload();">
                <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
            </button>
            <button type="button" class="btn-action-primary" id="btnOpenAddModal">
                <i class="fa fa-plus-circle"></i> <span>Yeni Mail Hesabı</span>
            </button>
        </div>
    </div>

    <!-- Özet Bilgiler (CRM KPI Kartları) -->
    <div id="kpiSummarySection" class="row mx-0 mb-3 kpi-summary-collapse">
        <!-- Toplam Mail Hesabı -->
        <div class="col-xl-3 col-md-6 col-sm-12 mb-2 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Toplam Mail Hesabı</span>
                        <div class="crm-kpi-value"><?php echo (int)$stats['total']; ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-primary">
                        <i class="fa fa-envelope-open-o"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Sistem Kayıtları</span>
                    <span class="crm-badge-soft soft-primary">Aktif Hesaplar</span>
                </div>
            </div>
        </div>

        <!-- Genel Kurumsal Mailler -->
        <div class="col-xl-3 col-md-6 col-sm-12 mb-2 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Genel Kurumsal Mailler</span>
                        <div class="crm-kpi-value"><?php echo (int)$stats['general']; ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-emerald">
                        <i class="fa fa-globe"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Kurumsal Erişim</span>
                    <span class="crm-badge-soft soft-emerald">Tüm Personele Açık</span>
                </div>
            </div>
        </div>

        <!-- Bireysel Kullanıcı Hesapları -->
        <div class="col-xl-3 col-md-6 col-sm-12 mb-2 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Bireysel Kullanıcı Mailleri</span>
                        <div class="crm-kpi-value"><?php echo (int)$stats['user']; ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-purple">
                        <i class="fa fa-user-circle"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11">Bireysel Atanmış</span>
                    <span class="crm-badge-soft soft-purple">Özel Hesaplar</span>
                </div>
            </div>
        </div>

        <!-- Son Tanımlanan Mail -->
        <div class="col-xl-3 col-md-6 col-sm-12 mb-2 mb-xl-0 px-1">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Son Tanımlanan Mail</span>
                        <div class="crm-kpi-value font-14 text-truncate" style="max-width: 170px;" title="<?php echo htmlspecialchars($stats['last_account'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($stats['last_account'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                    <div class="crm-kpi-icon icon-amber" style="background: #fffbeb; color: #d97706;">
                        <i class="fa fa-history"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="text-muted font-11"><i class="fa fa-clock-o mr-1"></i> <?php echo htmlspecialchars($stats['last_date'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="crm-badge-soft" style="background: #fef3c7; color: #92400e;">Son Eklenen</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="form-card animate-fade-in">
        <div class="form-card-header">
            <div class="header-left-inner">
                <div class="card-icon">
                    <i class="fa fa-list-alt"></i>
                </div>
                <div>
                    <h5>Tanımlı E-Posta Hesapları</h5>
                    <p>Giden e-postalarda kullanılacak yetkilendirilmiş hesap listesi</p>
                </div>
            </div>
        </div>

        <div class="table-responsive p-0">
            <table class="table table-hover table-striped" id="mailAccountsTable" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;">#</th>
                        <th>MAIL ADRESI</th>
                        <th class="text-center" style="width: 130px;">HESAP TÜRÜ</th>
                        <th>YETKILI / KULLANICI</th>
                        <th>AÇIKLAMA</th>
                        <th class="text-center" style="width: 140px;">EKLEYEN</th>
                        <th class="text-center" style="width: 130px;">KAYIT TARIHI</th>
                        <th class="text-center" style="width: 80px;">İŞLEM</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $seq = 1;
                    foreach ($accounts as $row):
                        $accId = (int)$row['id'];
                        $mailAddr = htmlspecialchars($row['mail_address'] ?? '', ENT_QUOTES, 'UTF-8');
                        $accType = (int)($row['account_type'] ?? 1);
                        $desc = htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8');
                        $createTime = htmlspecialchars($row['create_time'] ?? '-', ENT_QUOTES, 'UTF-8');
                        $creatorName = htmlspecialchars($row['creator_name'] ?? ($row['creator'] ? 'Kullanıcı #' . $row['creator'] : 'Sistem'), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr id="mail-row-<?php echo $accId; ?>" data-id="<?php echo $accId; ?>">
                            <td class="text-center font-weight-bold text-muted"><?php echo $seq++; ?></td>
                            <td>
                                <span class="font-weight-bold text-dark font-13">
                                    <i class="fa fa-envelope-o text-primary mr-1"></i> <?php echo $mailAddr; ?>
                                </span>
                                <?php if (!empty($row['mail_password'])): ?>
                                    <span class="mail-badge mail-badge-smtp ml-1" title="Bu hesaba özel SMTP şifresi tanımlanmıştır">
                                        <i class="fa fa-key mr-1"></i> SMTP Tanımlı
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($accType === 1): ?>
                                    <span class="mail-badge mail-badge-general">
                                        <i class="fa fa-globe mr-1"></i> Genel Mail
                                    </span>
                                <?php else: ?>
                                    <span class="mail-badge mail-badge-user">
                                        <i class="fa fa-user mr-1"></i> Kullanıcı Maili
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($accType === 1): ?>
                                    <span class="mail-badge mail-badge-all-staff">
                                        <i class="fa fa-users mr-1"></i> Tüm Personel
                                    </span>
                                <?php else: 
                                    $userName = $row['mail_user_name'] ?? '';
                                    $userTitle = $row['mail_user_title'] ?? '';
                                    $displayName = !empty($userName) ? $userName : 'Kullanıcı #' . $row['mail_user'];
                                ?>
                                    <span class="font-weight-bold text-dark"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if (!empty($userTitle)): ?>
                                        <small class="text-muted d-block font-11"><?php echo htmlspecialchars($userTitle, ENT_QUOTES, 'UTF-8'); ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($desc)): ?>
                                    <span class="text-dark font-12"><?php echo $desc; ?></span>
                                <?php else: ?>
                                    <span class="text-muted font-italic font-11">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="mail-badge mail-badge-creator">
                                    <i class="fa fa-user-circle-o mr-1"></i> <?php echo $creatorName; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="text-nowrap font-12"><i class="fa fa-clock-o text-muted mr-1"></i> <?php echo $createTime; ?></span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-edit-account" 
                                        data-id="<?php echo $accId; ?>" 
                                        data-email="<?php echo $mailAddr; ?>" 
                                        data-type="<?php echo $accType; ?>" 
                                        data-user="<?php echo (int)$row['mail_user']; ?>" 
                                        data-desc="<?php echo $desc; ?>"
                                        data-has-pass="<?php echo !empty($row['mail_password']) ? '1' : '0'; ?>"
                                        title="Düzenle">
                                        <i class="fa fa-pencil"></i>
                                    </button>

                                    <button type="button" class="btn btn-outline-danger btn-delete-account" 
                                        data-id="<?php echo $accId; ?>" 
                                        data-email="<?php echo $mailAddr; ?>"
                                        title="Sil">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ekle/Düzenle -->
<div class="modal fade" id="mailAccountModal" tabindex="-1" role="dialog" aria-labelledby="mailAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.12);">
            <div class="modal-header d-flex align-items-center justify-content-between" style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 17px; box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);">
                        <i class="fa fa-at" id="modalHeaderIcon"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold mb-0" id="mailAccountModalLabel" style="font-size: 16px; color: #1e293b;">Yeni Mail Hesabı Ekle</h5>
                        <small class="text-muted font-12">E-posta, şifre ve kullanıcı tanımlaması</small>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat" style="font-size: 24px; color: #94a3b8; outline: none; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="mailAccountForm" method="post" novalidate>
                <div class="modal-body p-4" style="background: #ffffff;">
                    <input type="hidden" id="form_account_id" name="id" value="0">
                    <input type="hidden" id="form_action" name="action" value="new">

                    <!-- Hesap Türü Segment Seçici -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-12 mb-2 d-block">
                            Hesap Türü <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex" style="gap: 10px;">
                            <label class="account-type-card flex-fill cursor-pointer mb-0" for="acc_type_general">
                                <input type="radio" name="account_type" id="acc_type_general" value="1" class="d-none acc-type-radio">
                                <div class="card-box p-2 text-center border rounded transition-all" style="border-radius: 8px; cursor: pointer; border: 1px solid #e2e8f0; background: #f8fafc;">
                                    <div class="type-icon mb-1" style="font-size: 16px; color: #059669;">
                                        <i class="fa fa-globe"></i>
                                    </div>
                                    <strong class="d-block font-12 text-dark">Genel Mail</strong>
                                    <small class="text-muted font-11">Tüm Personel</small>
                                </div>
                            </label>

                            <label class="account-type-card flex-fill cursor-pointer mb-0" for="acc_type_user">
                                <input type="radio" name="account_type" id="acc_type_user" value="2" class="d-none acc-type-radio" checked>
                                <div class="card-box p-2 text-center border rounded transition-all" style="border-radius: 8px; cursor: pointer; border: 1px solid #e2e8f0; background: #f8fafc;">
                                    <div class="type-icon mb-1" style="font-size: 16px; color: #2563eb;">
                                        <i class="fa fa-user"></i>
                                    </div>
                                    <strong class="d-block font-12 text-dark">Kullanıcı Maili</strong>
                                    <small class="text-muted font-11">Özel Atanmış</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- E-Posta Adresi -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-12 mb-1" for="modal_mail_address">
                            E-Posta Adresi <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light text-muted border-right-0" style="border-radius: 6px 0 0 6px;">
                                    <i class="fa fa-envelope-o"></i>
                                </span>
                            </div>
                            <input type="email" class="form-control" id="modal_mail_address" name="mail_address" placeholder="ornek@aydinogullari.com" required style="border-radius: 0 6px 6px 0; font-size: 13px;">
                        </div>
                    </div>

                    <!-- E-Posta Şifresi (SMTP Parolası) -->
                    <div class="form-group mb-3" id="group_mail_password">
                        <label class="font-weight-bold text-dark font-12 mb-1" for="modal_mail_password">
                            E-Posta / SMTP Şifresi <small class="text-muted font-11 font-weight-normal">(Opsiyonel)</small>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light text-muted border-right-0" style="border-radius: 6px 0 0 6px;">
                                    <i class="fa fa-lock"></i>
                                </span>
                            </div>
                            <input type="password" class="form-control" id="modal_mail_password" name="mail_password" placeholder="Bu hesaba özel SMTP şifresi..." style="border-radius: 0; font-size: 13px;">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary border-left-0" id="btnToggleMailPass" style="border-radius: 0 6px 6px 0; background: #f8fafc;" title="Şifreyi Göster/Gizle">
                                    <i class="fa fa-eye text-muted" id="iconToggleMailPass"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted font-11 mt-1 d-block" id="help_mail_password">
                            <i class="fa fa-info-circle mr-1"></i> Mail gönderiminde bu hesaba özel SMTP şifresi kullanılır. Boş bırakırsanız panel ana ayarları kullanılır.
                        </small>
                    </div>

                    <!-- Kullanıcı Seçimi (Kullanıcı Maili için) -->
                    <div class="form-group mb-3" id="group_mail_user">
                        <label class="font-weight-bold text-dark font-12 mb-1" for="modal_mail_user">
                            Hesap Sahibi Personel <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="modal_mail_user" name="mail_user" style="width: 100%; font-size: 13px; border-radius: 6px;">
                            <option value="">-- Personel Seçiniz --</option>
                            <?php foreach ($usersList as $u):
                                $uid = (int)$u['id'];
                                $uname = htmlspecialchars($u['username'] ?? '', ENT_QUOTES, 'UTF-8');
                                $utitle = htmlspecialchars($u['Unvan'] ?? '', ENT_QUOTES, 'UTF-8');
                            ?>
                                <option value="<?php echo $uid; ?>">
                                    <?php echo $uname . (!empty($utitle) ? " ({$utitle})" : ""); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Açıklama -->
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark font-12 mb-1" for="modal_description">
                            Açıklama / Not
                        </label>
                        <input type="text" class="form-control" id="modal_description" name="description" placeholder="Örn: Proje & Satış Departmanı" style="border-radius: 6px; font-size: 13px;">
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-dismiss="modal" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4" id="btnSaveAccount">
                        <i class="fa fa-save mr-1"></i> <span id="saveBtnText">Kaydet</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Şifre Göster/Gizle
    $('#btnToggleMailPass').on('click', function(e) {
        e.preventDefault();
        var passInput = $('#modal_mail_password');
        var icon = $('#iconToggleMailPass');
        if (passInput.attr('type') === 'password') {
            passInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // KPI Collapse Durumu Kontrolü
    var $kpiSection = $('#kpiSummarySection');
    var $kpiBtn = $('#kpiToggleBtn');
    
    if (localStorage.getItem('aydinogullari_kpi_mailaccounts_collapsed') === 'true') {
        $kpiSection.addClass('is-collapsed');
        $kpiBtn.addClass('active btn-secondary').removeClass('btn-outline-secondary');
    }

    $kpiBtn.on('click', function(e) {
        e.preventDefault();
        $kpiSection.toggleClass('is-collapsed');
        var isCollapsed = $kpiSection.hasClass('is-collapsed');
        localStorage.setItem('aydinogullari_kpi_mailaccounts_collapsed', isCollapsed);
        
        if (isCollapsed) {
            $kpiBtn.addClass('active btn-secondary').removeClass('btn-outline-secondary');
        } else {
            $kpiBtn.removeClass('active btn-secondary').addClass('btn-outline-secondary');
        }
    });

    // DataTable Başlatma
    var table = null;
    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#mailAccountsTable')) {
        table = $('#mailAccountsTable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tümü"]],
            order: [[0, 'asc']]
        });
    }

    // Modal Hesap Türü Değişimi
    $('input[name="account_type"]').on('change', function() {
        if ($(this).val() == '2') {
            $('#group_mail_user').slideDown(150);
        } else {
            $('#group_mail_user').slideUp(150);
        }
    });

    // Yeni Mail Hesabı Butonu Tıklama
    $('#btnOpenAddModal').on('click', function(e) {
        e.preventDefault();
        $('#mailAccountForm')[0].reset();
        $('#form_account_id').val('0');
        $('#form_action').val('new');
        $('#mailAccountModalLabel').text('Yeni Mail Hesabı Ekle');
        $('#modalHeaderIcon').attr('class', 'fa fa-at');
        $('#saveBtnText').text('Kaydet');
        $('#modal_mail_password').val('').attr('placeholder', 'Bu hesaba özel SMTP şifresi...');
        $('#modal_mail_password').attr('type', 'password');
        $('#iconToggleMailPass').removeClass('fa-eye-slash').addClass('fa-eye');
        
        // Varsayılan Kullanıcı Hesabı
        $('#acc_type_user').prop('checked', true).trigger('change');
        $('#modal_mail_user').val('');

        $('#mailAccountModal').modal('show');
    });

    // Düzenle Butonu Tıklama
    $(document).on('click', '.btn-edit-account', function(e) {
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        var email = btn.data('email');
        var type = btn.data('type');
        var user = btn.data('user');
        var desc = btn.data('desc');
        var hasPass = btn.data('has-pass');

        $('#form_account_id').val(id);
        $('#form_action').val('update');
        $('#mailAccountModalLabel').text('Mail Hesabını Düzenle');
        $('#modalHeaderIcon').attr('class', 'fa fa-pencil');
        $('#saveBtnText').text('Güncelle');

        $('#modal_mail_address').val(email);
        $('#modal_description').val(desc);
        $('#modal_mail_password').val('');
        $('#modal_mail_password').attr('type', 'password');
        $('#iconToggleMailPass').removeClass('fa-eye-slash').addClass('fa-eye');

        if (hasPass == 1 || hasPass === '1') {
            $('#modal_mail_password').attr('placeholder', '•••••••• (Değiştirmek istemiyorsanız boş bırakın)');
        } else {
            $('#modal_mail_password').attr('placeholder', 'Şifre tanımlı değil (İsteğe bağlı giriniz)');
        }

        if (type == 1) {
            $('#acc_type_general').prop('checked', true).trigger('change');
        } else {
            $('#acc_type_user').prop('checked', true).trigger('change');
            $('#modal_mail_user').val(user);
        }

        $('#mailAccountModal').modal('show');
    });

    // Form Gönderimi (AJAX)
    $('#mailAccountForm').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var mailAddr = $.trim($('#modal_mail_address').val());
        var accType = $('input[name="account_type"]:checked').val();
        var mailUser = $('#modal_mail_user').val();

        if (!mailAddr) {
            Swal.fire({
                icon: 'warning',
                title: 'Eksik Bilgi',
                text: 'Lütfen mail adresini giriniz.'
            });
            return false;
        }

        if (accType == '2' && !mailUser) {
            Swal.fire({
                icon: 'warning',
                title: 'Kullanıcı Seçimi Gerekli',
                text: 'Kullanıcı hesabı için lütfen sistemden bir personel seçiniz.'
            });
            return false;
        }

        var btn = $('#btnSaveAccount');
        var originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> İşleniyor...');

        $.ajax({
            url: 'pages/1/send-mail-accounts.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html(originalHtml);
                if (res.status === 200) {
                    $('#mailAccountModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: res.message || 'İşlem gerçekleştirilemedi.'
                    });
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).html(originalHtml);
                Swal.fire({
                    icon: 'error',
                    title: 'Sunucu Hatası',
                    text: 'İşlem sırasında beklenmeyen bir hata oluştu.'
                });
            }
        });
    });

    // Silme Butonu Tıklama (SweetAlert2 ile Güvenli Silme)
    $(document).on('click', '.btn-delete-account', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var email = $(this).data('email');

        Swal.fire({
            title: 'Silmek İstediğinize Emin misiniz?',
            html: '<strong class="text-danger">' + email + '</strong> mail hesabı sistemden kalıcı olarak silinecektir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa fa-trash mr-1"></i> Evet, Sil',
            cancelButtonText: 'Vazgeç'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'pages/1/send-mail-accounts.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        id: id
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Silindi!',
                                text: res.message,
                                timer: 1400,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Hata',
                                text: res.message || 'Silme işlemi başarısız oldu.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata',
                            text: 'Silme işlemi sırasında sunucuyla iletişim kurulamadı.'
                        });
                    }
                });
            }
        });
    });
});
</script>