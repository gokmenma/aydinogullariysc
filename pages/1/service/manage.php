<?php

use App\Helper\Helper;

$getNextServiceSequence = static function () use ($ac): int {
    $counterQuery = $ac->prepare("SELECT service FROM define_numbers LIMIT 1");
    $counterQuery->execute();
    $counter = (int) $counterQuery->fetchColumn();

    $maxQuery = $ac->prepare(
        "SELECT COALESCE(MAX(CAST(SUBSTRING(service_number, 4) AS UNSIGNED)), 0)
         FROM projects
         WHERE service_number REGEXP '^SRV[0-9]+$'"
    );
    $maxQuery->execute();
    $nextFromProjects = ((int) $maxQuery->fetchColumn()) + 1;

    return max($counter, $nextFromProjects, 1);
};

// ─── Mod Belirleme: Yeni mi, Düzenleme mi? ───
$isEdit = isset($_GET['id']) && is_numeric($_GET['id']);
$oid = @$_GET["oid"]; // Tekliften gelen servis oluşturma
$isOffer = $oid ? "true" : "false";

if ($isEdit) {
    permcontrol('serviceEdit');
} else {
    permcontrol("serviceAdd");
}

// ─── Düzenleme Modu: Mevcut Servis Verisi ───
$cc = null;
$sid = 0;
if ($isEdit) {
    $sid = $_GET['id'];
    $cerq = $ac->prepare('SELECT * FROM projects WHERE id = ?');
    $cerq->execute(array($sid));
    $cc = $cerq->fetch(PDO::FETCH_ASSOC);

    if (!$cc) {
        header('Location: index.php?p=service/list&err=01735');
        exit;
    }

    // Oluşturan kişi
    $secilen_pcid = $cc['pcreativer'];
    $qctUser = $ac->prepare("SELECT * FROM users WHERE id=?");
    $qctUser->execute(array($secilen_pcid));
    $cscs = $qctUser->fetch(PDO::FETCH_ASSOC);

    // Teklif bilgisi
    $ofinf = $ac->prepare('SELECT * FROM offers WHERE id = ?');
    $ofinf->execute(array($cc['poid']));
    $ofinfo = $ofinf->fetch(PDO::FETCH_ASSOC);
}

// ─── Yeni Mod: Servis Numarası Oluştur ───
$service_number = '';
$getNumber = 0;
if (!$isEdit) {
    $getNumber = $getNextServiceSequence();
    $service_number = "SRV" . str_pad($getNumber, 5, "0", STR_PAD_LEFT);
} else {
    $service_number = $cc['service_number'];
}

// ─── Tekliften Gelen Bilgiler (Yeni Mod) ───
$comp_name = "";
$comp_city = "";
$comp_region = "";
$comp_ilce = "";
$comp_id = "";
$offer = null;
$description = "";

if ($oid && !$isEdit) {
    $qct = $ac->prepare("SELECT * FROM offers WHERE id= ?");
    $qct->execute(array($oid));
    $offer = $qct->fetch(PDO::FETCH_ASSOC);

    $description = $offer["offerNumber"] . " numaralı teklife ait servis.";

    $cust_id = $offer["cid"];
    $sql = $ac->prepare("SELECT * FROM customers WHERE id = ?");
    $sql->execute(array($cust_id));
    $cust = $sql->fetch(PDO::FETCH_ASSOC);

    $comp_name = $cust["company"];
    $comp_city = $cust["city"];
    $comp_ilce = $cust["ilce"];
    $comp_id = $cust["id"];
    $comp_region = $cust["region"];
}

// Servis kayıt ve güncelleme işlemleri App/api/service-save.php üzerinden yürütülür.

// ─── Uyarı Mesajları ───
if (@$_GET["st"] == "newsuccess") {
    echo Helper::alert('success', 'Servis başarıyla oluşturuldu!', 'Başarılı!');
} elseif (@$_GET["st"] == "updatesuccess") {
    echo Helper::alert('success', 'Servis başarıyla güncellendi!', 'Başarılı!');
} elseif (@$_GET["st"] == "empties") {
    echo Helper::alert('danger', '(*) ile işaretli alanları boş bırakmadan tekrar deneyin.', 'Hata!');
} elseif (@$_GET["st"] == "newerror") {
    echo Helper::alert('danger', 'İşlem sırasında bir hata oluştu. Lütfen tekrar deneyin.', 'Hata!');
} elseif (@$_GET["st"] == "iptal") {
    echo Helper::alert('warning', 'Servis İPTAL edildiği için Servis Sonucu alanına kim tarafından neden iptal edildiği bilgisini giriniz.', 'Dikkat!');
}

$pageTitle = $isEdit ? 'Servis Düzenle' : 'Yeni Servis Oluştur';
$pageIcon = $isEdit ? 'fa-pencil-square-o' : 'fa-plus-circle';
?>

<style>
    /* ═══════════════════════════════════════════════════════════════
   SERVIS MANAGE - MODERN STEPPED WIZARD UI
   ═══════════════════════════════════════════════════════════════ */

    /* Ana container */
    .service-manage-wrapper {
        max-width: 1400px;
        margin: 0 auto;
    }



    /* ─── Step Wizard İlerleme ─── */
    .wizard-steps {
        display: flex;
        justify-content: center;
        gap: 0;
        margin-bottom: 24px;
        background: #fff;
        border-radius: 16px;
        padding: 20px 30px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        position: relative;
    }

    .wizard-step {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }

    .wizard-step:hover {
        background: #f0f7ff;
    }

    .wizard-step.active {
        background: #eef5ff;
    }

    .wizard-step .step-number {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        background: #e5e7eb;
        color: #6b7280;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .wizard-step.active .step-number {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #fff;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    }

    .wizard-step.completed .step-number {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    .wizard-step .step-label {
        font-size: 14px;
        font-weight: 500;
        color: #9ca3af;
        transition: color 0.3s ease;
    }

    .wizard-step.active .step-label {
        color: #1e40af;
        font-weight: 600;
    }

    .wizard-step.completed .step-label {
        color: #16a34a;
    }

    .wizard-connector {
        display: flex;
        align-items: center;
        padding: 0 8px;
    }

    .wizard-connector .connector-line {
        width: 40px;
        height: 3px;
        background: #e5e7eb;
        border-radius: 2px;
        transition: background 0.3s ease;
    }

    .wizard-connector.completed .connector-line {
        background: linear-gradient(90deg, #22c55e, #3b82f6);
    }

    /* ─── Step İçerik Panelleri ─── */
    .step-panel {
        display: block;
        animation: fadeSlideIn 0.4s ease;
    }

    @keyframes fadeSlideIn {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Form Kartları */
    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        margin-bottom: 20px;
        border: 1px solid #f0f0f0;
        transition: box-shadow 0.3s ease;
    }

    .form-card:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .form-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f3f4f6;
    }

    .form-card-header .card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .card-icon-blue {
        background: #eff6ff;
        color: #3b82f6;
    }

    .card-icon-green {
        background: #f0fdf4;
        color: #22c55e;
    }

    .card-icon-purple {
        background: #faf5ff;
        color: #a855f7;
    }

    .card-icon-orange {
        background: #fff7ed;
        color: #f97316;
    }

    .card-icon-red {
        background: #fef2f2;
        color: #ef4444;
    }

    .form-card-header h5 {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #1e3a5f;
    }

    .form-card-header p {
        margin: 4px 0 0;
        font-size: 13.5px;
        color: #64748b;
    }

    /* Form grupları - Modern Grid */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px 30px;
    }

    .form-grid .full-width {
        grid-column: 1 / -1;
    }

    .form-field {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        gap: 8px;
    }

    .form-field select.select2-hidden-accessible {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        min-height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    .form-field label {
        font-size: 13.5px;
        font-weight: 600;
        color: #334155;
        display: flex;
        align-items: center;
        margin-bottom: 2px;
    }

    .form-field label .required-dot {
        width: 7px;
        height: 7px;
        background: #ef4444;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
    }

    .form-field .form-control,
    .form-field .bootstrap-select .btn,
    .form-field .select2-container--default .select2-selection--single {
        border-radius: 10px !important;
        border: 1.5px solid #e5e7eb !important;
        padding: 10px 14px !important;
        font-size: 14px !important;
        transition: all 0.25s ease !important;
        background: #fafafa !important;
        height: 46px !important;
        display: flex !important;
        align-items: center !important;
    }

    .form-field .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #1e293b !important;
        line-height: normal !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        font-size: 14px !important;
    }

    .form-field .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px !important;
        right: 12px !important;
    }

    .form-field .select2-container--default .select2-selection--multiple {
        border-radius: 10px !important;
        border: 1.5px solid #e5e7eb !important;
        padding: 4px 8px !important;
        font-size: 14px !important;
        transition: all 0.25s ease !important;
        background: #fafafa !important;
        min-height: 46px !important;
    }

    .form-field .form-control:focus,
    .form-field .select2-container--default.select2-container--focus .select2-selection--single,
    .form-field .select2-container--default.select2-container--open .select2-selection--single,
    .form-field .select2-container--default.select2-container--focus .select2-selection--multiple,
    .form-field .select2-container--default.select2-container--open .select2-selection--multiple {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12) !important;
        background: #fff !important;
    }

    .form-field.field-error .form-control,
    .form-field.field-error .bootstrap-select .btn,
    .form-field.field-error .select2-container--default .select2-selection {
        border-color: #ef4444 !important;
        background: #fef2f2 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
    }

    .form-field .input-group {
        display: flex !important;
        align-items: stretch;
        width: 100%;
    }

    .form-field > span.select2.select2-container {
        width: 100% !important;
        min-width: 0 !important;
        margin: 0 !important;
        align-self: stretch;
    }

    .form-field .select2-container,
    .form-field .select2-selection,
    .form-field .select2-selection:focus {
        outline: none !important;
    }

    .form-field .input-group .bootstrap-select,
    .form-field .input-group span.select2.select2-container {
        flex: 1 1 auto !important;
        width: 1% !important;
        min-width: 0 !important;
        margin: 0 !important;
    }

    .form-field .input-group .bootstrap-select .btn,
    .form-field .input-group .select2-container .select2-selection {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    .form-field .offer-input-group:not(.has-view-offer) .form-control,
    .form-field .offer-input-group:not(.has-view-offer) .select2-container .select2-selection {
        border-top-right-radius: 10px !important;
        border-bottom-right-radius: 10px !important;
    }

    .form-field .input-group .btn-add-new,
    .form-field .input-group .btn-view-offer {
        border-radius: 0 10px 10px 0 !important;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        border: none;
        padding: 0 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        flex-shrink: 0;
        font-size: 16px;
        height: auto;
    }

    .btn-view-offer {
        background: linear-gradient(135deg, #ef4444, #b91c1c) !important;
        margin-left: -1px;
        /* Overlap borders if needed */
    }

    /* If there are two buttons, adjust the first one's border */
    .form-field .input-group .btn-add-new:not(:last-child) {
        border-radius: 0 !important;
    }

    .form-field .input-group .btn-add-new:hover,
    .form-field .input-group .btn-view-offer:hover {
        filter: brightness(1.1);
        color: #fff;
    }

    .form-field textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    /* Readonly görünüm */
    .readonly-value {
        background: #f3f4f6;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 14px;
        color: #374151;
        border: 1.5px solid #e5e7eb;
        font-weight: 500;
    }

    /* ─── İlerleme Çubuğu (Düzenleme Modu) ─── */
    .progress-modern {
        height: 10px;
        border-radius: 10px;
        background: #f3f4f6;
        overflow: hidden;
    }

    .progress-modern .progress-bar {
        border-radius: 10px;
        transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ─── Alt Butonlar ─── */
    .step-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
    }

    .step-navigation .btn-step {
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn-step-prev {
        background: #f3f4f6;
        color: #4b5563;
    }

    .btn-step-prev:hover {
        background: #e5e7eb;
        color: #1f2937;
    }

    .btn-step-next {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        color: #fff;
        box-shadow: 0 4px 15px rgba(22, 163, 74, 0.35);
    }

    .btn-step-next:hover {
        background: linear-gradient(135deg, #15803d 0%, #166534 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(22, 163, 74, 0.45);
        color: #fff;
    }

    /* ─── Bilgi Kartı (Audit Trail) ─── */
    .audit-trail-card {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
    }

    .audit-trail-card .audit-row {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .audit-trail-card .audit-row:last-child {
        border-bottom: none;
    }

    .audit-trail-card .audit-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #64748b;
        font-size: 14px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .audit-trail-card .audit-label {
        font-size: 13px;
        color: #64748b;
        min-width: 140px;
    }

    .audit-trail-card .audit-value {
        font-size: 14px;
        color: #1e293b;
        font-weight: 500;
    }

    /* ─── Dikkat Uyarısı ─── */
    .cancel-warning {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border: 1px solid #fca5a5;
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: pulseWarning 2s infinite;
    }

    @keyframes pulseWarning {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }
    }

    .cancel-warning .warning-icon {
        color: #dc2626;
        font-size: 20px;
    }

    .cancel-warning .warning-text {
        color: #991b1b;
        font-size: 14px;
        font-weight: 500;
    }

    /* ─── Responsive ─── */
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .wizard-steps {
            flex-wrap: wrap;
            gap: 8px;
            padding: 16px;
        }

        .wizard-connector {
            display: none;
        }

        .service-header-card .header-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .service-header-card .header-actions {
            width: 100%;
        }

        .service-header-card .header-actions .btn-header {
            flex: 1;
            justify-content: center;
        }

        .step-navigation {
            flex-direction: column;
            gap: 12px;
        }

        .step-navigation .btn-step {
            width: 100%;
            justify-content: center;
        }
    }

    /* wait-span güncelleme */
    .wait-span-modern {
        background: #fef3c7;
        border: 1px solid #fbbf24;
        border-radius: 8px;
        padding: 8px 12px;
        margin-top: 8px;
        font-size: 13px;
        color: #92400e;
        display: none;
    }

    /* Selectpicker uyumu */
    .form-field .bootstrap-select {
        width: 100% !important;
    }

    .form-field .bootstrap-select .dropdown-toggle {
        border-radius: 10px !important;
        border: 1.5px solid #e5e7eb !important;
        padding: 10px 14px !important;
        height: auto !important;
        background: #fafafa !important;
    }

    .form-field .chooseitem {
        margin-left: 0;
    }

    /* ═══════════════════════════════════════════════════════════════
       DARK MODE OVERRIDES
       ═══════════════════════════════════════════════════════════════ */
    .dark-mode .wizard-steps,
    .dark-mode .form-card {
        background: #2a2a2a;
        border-color: #3f3f3f;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .dark-mode .form-card-header {
        border-bottom-color: #3f3f3f;
    }

    .dark-mode .form-card-header h5 {
        color: #e5e7eb;
    }

    .dark-mode .form-card-header p {
        color: #9ca3af;
    }

    .dark-mode .form-field label {
        color: #d1d5db;
    }

    .dark-mode .form-field .form-control,
    .dark-mode .form-field .bootstrap-select .btn {
        background: #1f1f1f !important;
        border-color: #4b5563 !important;
        color: #f3f4f6 !important;
    }

    .dark-mode .form-field .form-control:focus {
        border-color: #3b82f6 !important;
        background: #1a1a1a !important;
    }

    .dark-mode .readonly-value {
        background: #1f1f1f;
        border-color: #374151;
        color: #9ca3af;
    }

    .dark-mode .audit-trail-card {
        background: linear-gradient(135deg, #1f2937, #111827);
        border-color: #374151;
    }

    .dark-mode .audit-row {
        border-bottom-color: #374151;
    }

    .dark-mode .audit-icon {
        background: #374151;
        color: #9ca3af;
    }

    .dark-mode .audit-label {
        color: #9ca3af;
    }

    .dark-mode .audit-value {
        color: #f3f4f6;
    }

    .dark-mode .cancel-warning {
        background: linear-gradient(135deg, #450a0a, #7f1d1d);
        border-color: #991b1b;
    }

    .dark-mode .cancel-warning .warning-text {
        color: #fecaca;
    }

    .dark-mode .wait-span-modern {
        background: #451a03;
        border-color: #92400e;
        color: #fbbf24;
    }

    .dark-mode .wizard-step:hover {
        background: #2563eb20;
    }

    .dark-mode .wizard-step.active {
        background: #2563eb30;
    }

    .dark-mode .wizard-step .step-number {
        background: #374151;
        color: #9ca3af;
    }

    .dark-mode .wizard-step .step-label {
        color: #6b7280;
    }

    .dark-mode .wizard-step.active .step-label {
        color: #60a5fa;
    }

    /* Bootstrap select dropdown menu in dark mode */
    .dark-mode .bootstrap-select .dropdown-menu {
        background-color: #1f1f1f;
        border: 1px solid #4b5563;
    }

    .dark-mode .bootstrap-select .dropdown-menu li a {
        color: #f3f4f6;
    }

    .dark-mode .bootstrap-select .dropdown-menu li a:hover {
        background-color: #3b82f6;
        color: #fff;
    }

    .dark-mode .bootstrap-select .dropdown-header {
        color: #9ca3af;
    }

    .dark-mode .bootstrap-select .bs-searchbox .form-control {
        background: #111827 !important;
        border-color: #4b5563 !important;
        color: #fff !important;
    }

    /* ─── Zorunlu Alan Hata Durumu ─── */
    .form-field.field-error label {
        color: #ef4444;
    }

    .form-field.field-error .form-control {
        border-color: #ef4444 !important;
        background: #fff5f5 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }

    .form-field.field-error .bootstrap-select .dropdown-toggle {
        border-color: #ef4444 !important;
        background: #fff5f5 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }

    .form-field.field-error .required-dot {
        background: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3);
        animation: pulseDot 1s ease-in-out infinite;
    }

    .field-error-msg {
        font-size: 12px;
        color: #ef4444;
        font-weight: 500;
        display: none;
        margin-top: 4px;
    }

    .form-field.field-error .field-error-msg {
        display: block;
    }

    @keyframes pulseDot {
        0%, 100% { box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2); }
        50%       { box-shadow: 0 0 0 5px rgba(239, 68, 68, 0.4); }
    }

    .dark-mode .form-field.field-error .form-control,
    .dark-mode .form-field.field-error .bootstrap-select .dropdown-toggle {
        background: #3b1111 !important;
    }
</style>

<div class="service-manage-wrapper pd-ltr-20 xs-pd-20-10">

    <!-- ═══════ HEADER ═══════ -->
    <div class="service-header-card">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fa <?php echo $pageIcon; ?>"></i>
                </div>
                <div class="header-title">
                    <h4><?php echo $pageTitle; ?></h4>
                    <div class="service-number-badge">
                        <i class="fa fa-hashtag"></i>
                        <?php echo $service_number; ?>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="index.php?p=service/list" class="btn-header btn-header-list">
                    <i class="fa fa-list"></i> Listeye Dön
                </a>
                <button type="button" id="submitButton" onclick="submitServiceForm()"
                    class="btn-header btn-header-save">
                    <i class="fa fa-check-circle"></i> <?php echo $isEdit ? 'Güncelle' : 'Kaydet'; ?>
                </button>
            </div>
        </div>
    </div>



    <form name="serviceForm" id="serviceForm" action="App/api/service-save.php" enctype="multipart/form-data" method="POST">

        <!-- ═══════ STEP 1: Temel Bilgiler ═══════ -->
        <div class="step-panel active" id="step-1">
            <div class="form-card">
                <div class="form-card-header">
                    <div class="card-icon card-icon-blue"><i class="fa fa-building"></i></div>
                    <div>
                        <h5>Firma & Servis Bilgileri</h5>
                        <p>Firma, servis konusu ve tahsilat bilgilerini girin</p>
                    </div>
                </div>
                <div class="form-grid">
                    <!-- Firma -->
                    <div class="form-field" id="field-company">
                        <label><span class="required-dot"></span> Firma</label>
                        <div class="input-group">
                            <?php echo customers("company", $isEdit ? $cc['pcid'] : $comp_id) ?>
                            <a href="index.php?p=customers/manage" target="_blank" class="btn-add-new"
                                data-tooltip="Yeni Firma Ekle">
                                <i class="fa fa-plus"></i>
                            </a>
                        </div>
                        <span class="field-error-msg"><i class="fa fa-exclamation-circle"></i> Firma seçimi zorunludur</span>
                    </div>

                    <!-- Servis Konusu -->
                    <div class="form-field" id="field-ServisKonusu">
                        <label><span class="required-dot"></span> Servis Konusu</label>
                        <div class="input-group">
                            <select required name="ServisKonusu" id="ServisKonusu" class="form-control select2"
                                data-placeholder="Servis Konusu Seçiniz!">
                                <option value="">Servis Konusu Seçiniz!</option>
                                <?php
                                $sk = $ac->prepare("SELECT * FROM units WHERE statu='2' ORDER BY title ASC");
                                $sk->execute();
                                while ($mm1 = $sk->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                    <option <?php echo ($isEdit && $mm1['id'] == $cc['servicestype']) ? 'selected' : ''; ?>
                                        value="<?php echo $mm1["id"]; ?>">
                                        <?php echo htmlspecialchars($mm1["title"]); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <a href="index.php?p=servicestype" target="_blank" class="btn-add-new"
                                data-tooltip="Servis Konusu Ekle">
                                <i class="fa fa-plus"></i>
                            </a>
                        </div>
                        <span class="wait-span-modern" id="waitSpan">
                            Sözleşme durumu <strong>Sözleşme Bekliyor</strong> olarak seçildi
                        </span>
                        <span class="field-error-msg"><i class="fa fa-exclamation-circle"></i> Servis konusu seçimi zorunludur</span>
                    </div>

                    <!-- Tahsilat Türü -->
                    <div class="form-field" id="field-TahsilatTuru">
                        <label><span class="required-dot"></span> Tahsilat Türü</label>
                        <div class="input-group">
                            <select required name="TahsilatTuru" id="TahsilatTuru" class="form-control select2"
                                data-placeholder="Tahsilat Türü Seçiniz!">
                                <option value="">Tahsilat Türü Seçiniz!</option>
                                <?php
                                $tt = $ac->prepare("SELECT * FROM units WHERE statu='3' ORDER BY title ASC");
                                $tt->execute();
                                while ($mm2 = $tt->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                    <option <?php echo ($isEdit && $mm2['id'] == $cc['collectiontype']) ? 'selected' : ''; ?>
                                        value="<?php echo $mm2["id"]; ?>">
                                        <?php echo htmlspecialchars($mm2["title"]); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <a href="index.php?p=paytype" target="_blank" class="btn-add-new"
                                data-tooltip="Tahsilat Türü Ekle">
                                <i class="fa fa-plus"></i>
                            </a>
                        </div>
                        <span class="field-error-msg"><i class="fa fa-exclamation-circle"></i> Tahsilat türü seçimi zorunludur</span>
                    </div>

                    <!-- Başlama Tarihi -->
                    <div class="form-field">
                        <label>Başlama Tarihi</label>
                        <?php if ($isEdit && !in_array($_SESSION['lid'], array(4, 6, 12, 11, 28))) { ?>
                            <div class="readonly-value"><?php echo $cc['pstart_date']; ?></div>
                        <?php } else { ?>
                            <input name="pstartdate" class="form-control date-picker" autocomplete="off"
                                placeholder="Tarih Seçin" type="text"
                                value="<?php echo $isEdit ? $cc['pstart_date'] : ''; ?>">
                        <?php } ?>
                    </div>

                    <!-- Adres Bölge -->
                    <div class="form-field" id="field-region">
                        <label><span class="required-dot"></span> Adres Bölge</label>
                        <?php echo Helper::selectRegion("region", $isEdit ? ($cc['region'] ?? '') : ($comp_region ?? ''), "form-control select2"); ?>
                        <span class="field-error-msg"><i class="fa fa-exclamation-circle"></i> Adres bölge seçimi zorunludur</span>
                    </div>

                    <!-- Adres İl/İlçe -->
                    <div class="form-field">
                        <label>Adres İl/İlçe</label>
                        <input type="text" id="address" readonly name="address"
                            value="<?php echo $isEdit ? $cc['address'] : ($comp_city . " / " . $comp_ilce); ?>"
                            class="form-control">
                    </div>

                    <?php if ($isEdit) { ?>
                        <!-- 2. Planlama Tarihi (Sadece Düzenleme) -->
                        <div class="form-field">
                            <label>2. Planlama Tarihi</label>
                            <?php if (permtrue("second_plan_edit")) { ?>
                                <input name="pseconddate" autocomplete="off" class="form-control date-picker"
                                    placeholder="2. Planlama Tarihi Seçin" type="text"
                                    value="<?php echo $cc['psecond_date']; ?>">
                            <?php } else { ?>
                                <div class="readonly-value"><?php echo $cc['psecond_date'] ?: '-'; ?></div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>


        </div>

        <!-- ═══════ STEP 2: Detaylar & Yetkililer ═══════ -->
        <div class="step-panel" id="step-2">
            <div class="form-card">
                <div class="form-card-header">
                    <div class="card-icon card-icon-green"><i class="fa fa-users"></i></div>
                    <div>
                        <h5>Yetkililer & Fiyat Bilgileri</h5>
                        <p>Servis yetkililerini atayın ve fiyat bilgilerini girin</p>
                    </div>
                </div>
                <div class="form-grid">
                    <!-- Servis Yetkilileri -->
                    <div class="form-field">
                        <label>Servis Yetkilileri</label>
                        <?php if ($isEdit) { ?>
                            <select name="permings[]" id="permings" class="form-control select2" multiple
                                data-placeholder="Servis Yetkililerini Seçiniz">
                                <?php
                                $selectedValues = explode('|', $cc['pauthors']);
                                $permx = $ac->prepare('SELECT * FROM users ORDER BY username ASC');
                                $permx->execute();
                                while ($px = $permx->fetch(PDO::FETCH_ASSOC)) {
                                    $isSelected = in_array($px['id'], $selectedValues) ? 'selected' : '';
                                    ?>
                                    <option <?php echo $isSelected; ?> value="<?php echo $px['id']; ?>">
                                        <?php echo htmlspecialchars($px['username']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        <?php } else { ?>
                            <select name="permings[]" id="permings" class="form-control select2" multiple
                                data-placeholder="Servis Yetkililerini Seçiniz">
                                <?php
                                $permq = $ac->prepare("SELECT * FROM userroles ORDER BY id ASC");
                                $permq->execute();
                                while ($pp = $permq->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                    <optgroup label="<?php echo htmlspecialchars($pp["roleName"]); ?>">
                                        <?php
                                        $permx = $ac->prepare("SELECT * FROM users WHERE permission = ? ORDER BY username ASC");
                                        $permx->execute(array($pp["id"]));
                                        while ($px = $permx->fetch(PDO::FETCH_ASSOC)) { ?>
                                            <option value="<?php echo $px["id"]; ?>">
                                                <?php echo htmlspecialchars($px["username"]); ?>
                                            </option>
                                        <?php } ?>
                                    </optgroup>
                                <?php } ?>
                            </select>
                        <?php } ?>
                    </div>

                    <!-- Fiyat Bilgisi -->
                    <?php if (in_array($_SESSION['lid'], array(1, 2, 3, 4, 6, 10, 11, 12, 14))) { ?>
                        <div class="form-field">
                            <label>Fiyat Bilgisi</label>
                            <input name="price" class="form-control" type="text"
                                value="<?php echo $isEdit ? $cc['price'] : ($offer["total_price"] ?? ''); ?>">
                        </div>

                        <div class="form-field full-width">
                            <label>Fiyat Açıklaması</label>
                            <textarea name="price_desc" class="form-control"
                                rows="3"><?php echo $isEdit ? $cc['price_desc'] : ''; ?></textarea>
                        </div>
                    <?php } ?>

                    <!-- Teklif Numarası -->
                    <div class="form-field">
                        <label>Teklif Numarası</label>
                        <div class="input-group offer-input-group">
                            <?php if ($isEdit) { ?>
                                <select id="offerno" name="offerno" class="form-control select2"
                                    data-placeholder="Teklif Seçiniz">
                                    <option selected value="<?php echo $ofinfo['id'] ?? '' ?>">
                                        <?php echo htmlspecialchars($ofinfo['offerNumber'] ?? 'Teklif Yok') ?>
                                    </option>
                                </select>
                            <?php } elseif ($isOffer == "false") { ?>
                                <select id="offerno" name="offerno" class="form-control select2"
                                    data-placeholder="Teklif Seçiniz">
                                    <option selected value="<?php echo $oid ?>">
                                        <?php echo htmlspecialchars($offer["offerNumber"] ?? '') ?>
                                    </option>
                                </select>
                            <?php } else { ?>
                                <input name="offerno" id="offerno_input" class="form-control" type="text" readonly
                                    value="<?php echo htmlspecialchars($offer["offerNumber"] ?? '') ?>">
                                <input type="hidden" id="offerno" value="<?php echo $offer['id'] ?? '' ?>">
                            <?php } ?>

                            <!-- PDF Görüntüleme Butonu -->
                            <a href="javascript:void(0)" id="viewOfferBtn" class="btn-view-offer" style="display: none;"
                                target="_blank" data-tooltip="Teklifi Görüntüle (PDF)">
                                <i class="fa fa-file-pdf-o"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Teklif Dosyası -->
                    <div class="form-field">
                        <label>Teklif Dosyası</label>
                        <?php if ($isEdit) {
                            $cq = $ac->prepare('SELECT * FROM files WHERE pid = ? ORDER by id DESC');
                            $cq->execute(array($sid));
                            $as = $cq->fetch(PDO::FETCH_ASSOC);
                            if ($as != NULL) { ?>
                                <a href="servicefiles/<?php echo $as['filename']; ?>" class="btn btn-sm btn-outline-success"
                                    style="border-radius:10px; padding: 10px 14px;">
                                    <i class="fa fa-download"></i> Dosyayı İndir
                                </a>
                            <?php } else { ?>
                                <div class="readonly-value"><i class="fa fa-info-circle text-muted"></i> Teklif dosyası yok
                                </div>
                            <?php }
                        } else { ?>
                            <input name="dosya" type="file" class="form-control form-control-sm" style="padding: 8px;">
                        <?php } ?>
                    </div>
                </div>
            </div>


        </div>

        <!-- ═══════ STEP 3: Durum & Notlar ═══════ -->
        <div class="step-panel" id="step-3">
            <div class="form-card">
                <div class="form-card-header">
                    <div class="card-icon card-icon-purple"><i class="fa fa-clipboard"></i></div>
                    <div>
                        <h5>Açıklama & Durum</h5>
                        <p>Servis açıklamasını, durumunu ve notlarını belirleyin</p>
                    </div>
                </div>
                <div class="form-grid">
                    <!-- Açıklama -->
                    <div class="form-field">
                        <label>Açıklama</label>
                        <textarea id="pdesc" name="pdesc" rows="5" class="form-control"
                            placeholder="Servis hakkında açıklama yazın..."><?php echo $isEdit ? trim($cc['pdesc']) : ($description ?? ''); ?></textarea>
                    </div>

                    <!-- Servis Sonucu / Notlar -->
                    <div class="form-field">
                        <label>Servis Sonucu</label>
                        <textarea oninput="kontrolEt()" id="servicesnote" name="servicesnote" rows="5"
                            class="form-control"
                            placeholder="Servis sonucu hakkında bir açıklama ekleyiniz."><?php echo $isEdit ? trim($cc['pnotes']) : ''; ?></textarea>
                    </div>

                    <!-- Servis Durumu -->
                    <div class="form-field">
                        <label>Servis Durumu</label>
                        <?php servisDurum("pstatu", $isEdit ? $cc['pstatu'] : "", "form-control select2") ?>
                    </div>

                    <!-- Sözleşme Durumu -->
                    <div class="form-field">
                        <label>Sözleşme Durumu</label>
                        <?php sozlesmeDurumu("contract_statu", $isEdit ? $cc["contract_statu"] : 4, "form-control select2") ?>
                    </div>

                    <!-- İptal Uyarısı -->
                    <div class="form-field full-width" id="cancelWarningArea" style="display:none;">
                        <div class="cancel-warning">
                            <i class="fa fa-exclamation-triangle warning-icon"></i>
                            <span class="warning-text"><b>İptal Edildi</b> seçildiği için <b>Servis Sonucu</b> alanını
                                mutlaka doldurunuz!</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isEdit) { ?>
                <!-- ─── İlerleme Durumu ─── -->
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-orange"><i class="fa fa-tasks"></i></div>
                        <div>
                            <h5>İlerleme Durumu</h5>
                            <p>Servisin genel ilerleme durumu</p>
                        </div>
                    </div>
                    <div class="progress-modern">
                        <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0"
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- ─── Denetim İzi ─── -->
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="card-icon card-icon-red"><i class="fa fa-history"></i></div>
                        <div>
                            <h5>Kayıt Bilgileri</h5>
                            <p>Servisin oluşturma ve güncelleme geçmişi</p>
                        </div>
                    </div>
                    <div class="audit-trail-card">
                        <div class="audit-row">
                            <div class="audit-icon"><i class="fa fa-user-plus"></i></div>
                            <span class="audit-label">Oluşturan</span>
                            <span class="audit-value"><?php echo getUserName($cc['pcreativer']); ?></span>
                        </div>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="fa fa-calendar-plus-o"></i></div>
                            <span class="audit-label">Oluşturma Tarihi</span>
                            <span class="audit-value"><?php echo date_tr($cc['pregdate']); ?></span>
                        </div>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="fa fa-user-circle"></i></div>
                            <span class="audit-label">Son Güncelleyen</span>
                            <span class="audit-value"><?php echo getUserName($cc['updater']); ?></span>
                        </div>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="fa fa-clock-o"></i></div>
                            <span class="audit-label">Son Güncelleme</span>
                            <span class="audit-value"><?php echo date_tr($cc['update_at']); ?></span>
                        </div>
                        <?php if ($cc['contract_statu'] == 2 && !empty($cc['contract_updated_at'])) { ?>
                            <div class="audit-row">
                                <div class="audit-icon"><i class="fa fa-file-text-o"></i></div>
                                <span class="audit-label">Sözleşmeyi Yapıldı İşaretleyen</span>
                                <span class="audit-value"><?php echo getUserName($cc['contract_updated_by']); ?></span>
                            </div>
                            <div class="audit-row">
                                <div class="audit-icon"><i class="fa fa-calendar-check-o"></i></div>
                                <span class="audit-label">Sözleşme Tarihi</span>
                                <span class="audit-value"><?php echo date_tr($cc['contract_updated_at']); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <div class="step-navigation" style="justify-content: flex-end;">
                <button type="button" class="btn-step btn-step-next btn-header-save" onclick="submitServiceForm()">
                    <i class="fa fa-check-circle"></i> <?php echo $isEdit ? 'Güncelle' : 'Kaydet'; ?>
                </button>
            </div>
        </div>

        <input type="hidden" name="posted" value="true">
    </form>
    <input type="hidden" value="<?php echo $isOffer ?>" id="isOffer">
</div>

<!-- Şirket seçildiğinde il,ilçe seçimi ve o şirkete ait varsa teklif no seçimi -->
<script src="include/js/service.js?v=<?php echo filemtime('include/js/service.js'); ?>"></script>

<script>


    // ═══════ FORM GÖNDERİM ═══════
    var REQUIRED_FIELDS = [
        { id: 'company',       fieldId: 'field-company',       label: 'Firma' },
        { id: 'ServisKonusu',  fieldId: 'field-ServisKonusu',  label: 'Servis Konusu' },
        { id: 'TahsilatTuru',  fieldId: 'field-TahsilatTuru',  label: 'Tahsilat Türü' },
        { id: 'region',        fieldId: 'field-region',         label: 'Adres Bölge' }
    ];

    var serviceFormSubmitting = false;

    async function submitServiceForm() {
        if (serviceFormSubmitting) return;

        // Önceki hataları temizle
        $('.form-field.field-error').removeClass('field-error');

        var errors = [];
        var firstErrorEl = null;

        REQUIRED_FIELDS.forEach(function (f) {
            var val = $('#' + f.id).val();
            var isEmpty = !val || val === '' || val === '0' || (Array.isArray(val) && val.length === 0);
            if (isEmpty) {
                errors.push(f.label);
                var fieldDiv = document.getElementById(f.fieldId);
                if (fieldDiv) {
                    fieldDiv.classList.add('field-error');
                    if (!firstErrorEl) firstErrorEl = fieldDiv;
                }
            }
        });

        if (errors.length > 0) {
            showToast('Zorunlu alanlar eksik: <b>' + errors.join(', ') + '</b>', 'error');
            if (firstErrorEl) {
                firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        var form = document.getElementById('serviceForm');
        var submitButtons = document.querySelectorAll('.btn-header-save');
        var buttonContents = [];

        serviceFormSubmitting = true;
        submitButtons.forEach(function (button) {
            buttonContents.push(button.innerHTML);
            button.disabled = true;
            button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> İşleniyor';
        });

        try {
            var formData = new FormData(form);
            <?php if ($isEdit) { ?>
            formData.append('service_id', '<?php echo (int) $sid; ?>');
            <?php } ?>

            var response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });
            var responseText = await response.text();
            var result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error('Sunucudan geçersiz bir yanıt alındı. Lütfen tekrar deneyin.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'İşlem sırasında bir hata oluştu.');
            }

            <?php if (!$isEdit) { ?>
            try { localStorage.removeItem('<?php echo 'svcForm_' . $service_number; ?>'); } catch(e) {}
            <?php } ?>

            await Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: result.message,
                confirmButtonText: 'Tamam',
                allowOutsideClick: false
            });

            <?php if (!$isEdit) { ?>
            window.location.href = 'index.php?p=service/manage&id=' + encodeURIComponent(result.service_id);
            <?php } ?>
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: error.message || 'İşlem sırasında bir hata oluştu. Lütfen tekrar deneyin.',
                confirmButtonText: 'Tamam'
            });
        } finally {
            serviceFormSubmitting = false;
            submitButtons.forEach(function (button, index) {
                button.disabled = false;
                button.innerHTML = buttonContents[index];
            });
        }
    }

    // Hata göstergelerini alana tekrar tıklandığında/değiştirildiğinde temizle
    $(document).on('change', '#company, #ServisKonusu, #TahsilatTuru, #region', function () {
        var fieldId = 'field-' + this.id;
        var fieldDiv = document.getElementById(fieldId);
        if (fieldDiv) fieldDiv.classList.remove('field-error');
    });

    // ═══════ TOAST BİLDİRİM ═══════
    function showToast(message, type) {
        // Varsa eski toast'u kaldır
        var existing = document.getElementById('svc-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.id = 'svc-toast';
        toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;padding:14px 24px;border-radius:12px;color:#fff;font-size:14px;font-weight:500;box-shadow:0 8px 30px rgba(0,0,0,0.2);animation:fadeSlideIn 0.4s ease;display:flex;align-items:center;gap:10px;max-width:420px;';

        if (type === 'error') {
            toast.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            toast.innerHTML = '<i class="fa fa-exclamation-circle" style="flex-shrink:0;font-size:18px;"></i><span>' + message + '</span>';
        } else {
            toast.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
            toast.innerHTML = '<i class="fa fa-check-circle" style="flex-shrink:0;font-size:18px;"></i><span>' + message + '</span>';
        }

        document.body.appendChild(toast);
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
        }, 4000);
    }

    <?php if (!$isEdit) { ?>
    // ═══════ FORM OTOMATİK KAYDET / GERİ YÜKLE (Yeni Servis Modu) ═══════
    var SVC_STORAGE_KEY = '<?php echo 'svcForm_' . $service_number; ?>';

    function saveFormDraft() {
        try {
            var draft = {
                ServisKonusu:   $('#ServisKonusu').val(),
                TahsilatTuru:   $('#TahsilatTuru').val(),
                pstartdate:     $('input[name="pstartdate"]').val(),
                pdesc:          $('#pdesc').val(),
                servicesnote:   $('#servicesnote').val(),
                price:          $('input[name="price"]').val(),
                price_desc:     $('textarea[name="price_desc"]').val(),
                pstatu:         $('#pstatu').val(),
                contract_statu: $('#contract_statu').val()
            };
            localStorage.setItem(SVC_STORAGE_KEY, JSON.stringify(draft));
        } catch(e) {}
    }

    function restoreFormDraft() {
        try {
            var raw = localStorage.getItem(SVC_STORAGE_KEY);
            if (!raw) return;
            var d = JSON.parse(raw);

            if (d.ServisKonusu)   { $('#ServisKonusu').val(d.ServisKonusu).trigger('change'); }
            if (d.TahsilatTuru)   { $('#TahsilatTuru').val(d.TahsilatTuru).trigger('change'); }
            if (d.pstartdate)     { $('input[name="pstartdate"]').val(d.pstartdate); }
            if (d.pdesc)          { $('#pdesc').val(d.pdesc); }
            if (d.servicesnote)   { $('#servicesnote').val(d.servicesnote); }
            if (d.price)          { $('input[name="price"]').val(d.price); }
            if (d.price_desc)     { $('textarea[name="price_desc"]').val(d.price_desc); }
            if (d.pstatu)         { $('#pstatu').val(d.pstatu).trigger('change'); }
            if (d.contract_statu) { $('#contract_statu').val(d.contract_statu).trigger('change'); }

            showToast('Önceki yarım kalan form verileri geri yüklendi.', 'success');
        } catch(e) {}
    }

    // Değişiklikleri izle ve kaydet
    $(document).on('change', '#ServisKonusu, #TahsilatTuru, #pstatu, #contract_statu', saveFormDraft);
    $(document).on('input', '[name="pstartdate"], #pdesc, #servicesnote, [name="price"], [name="price_desc"]', saveFormDraft);
    <?php } ?>

    // ═══════ SELECT2 / SELECTPICKER INIT ═══════
    $(document).ready(function () {
        if ($.fn.select2) {
            $('.select2').each(function () {
                var $this = $(this);
                $this.select2({
                    placeholder: $this.attr('placeholder') || $this.data('placeholder') || 'Seçim Yapınız',
                    allowClear: !$this.prop('required') && !$this.prop('multiple'),
                    width: '100%'
                });
            });
        }

        if ($.fn.selectpicker) {
            $(".selectpicker").selectpicker({
                liveSearchPlaceholder: "Ara..",
                noneResultsText: 'Eşleşen kayıt yok {0}',
                noneSelectedText: "Seçim Yapılmadı",
                size: 5,
            });
        }

        // Teklif seçildiğinde butonu güncelle
        $("#offerno").on("change", function () {
            updateViewOfferButton($(this).val());
        });

        // Başlangıçta (sayfa yüklendiğinde) düzenleme modundaysa veya teklif seçiliyse butonu göster
        updateViewOfferButton($("#offerno").val());

        <?php if ($isEdit) { ?>
            kontrolEt();
        <?php } else { ?>
            // Yeni mod: taslak veri varsa geri yükle
            restoreFormDraft();
        <?php } ?>
    });

    function updateViewOfferButton(offerId) {
        var btn = $("#viewOfferBtn");
        var inputGroup = btn.closest(".offer-input-group");
        if (offerId && offerId != "" && offerId != "0") {
            inputGroup.addClass("has-view-offer");
            btn.attr("href", "index.php?p=offer-view&id=" + offerId);
            btn.fadeIn(300).css("display", "flex");
        } else {
            btn.fadeOut(200, function () {
                inputGroup.removeClass("has-view-offer");
            });
        }
    }

    // ═══════ SERVİS KONUSU DEĞİŞİKLİĞİ ═══════
    $("#ServisKonusu").on("change", function () {
        var servisKonusu = $(this).find('option:selected').text().trim();
        if (servisKonusu.toLowerCase().includes('kontrol/raporlama')) {
            $("#contract_statu").val(1).trigger('change');
            $("#waitSpan").show();
        } else {
            $("#contract_statu").val(4).trigger('change');
            $("#waitSpan").hide();
        }
    });

    // ═══════ DURUM KONTROLÜ (Düzenleme) ═══════
    $("#pstatu").on("change", function () {
        kontrolEt();
    });

    function kontrolEt() {
        var servicesNote = $("#servicesnote");
        var submitButton = $("#submitButton");
        var selectedStatus = $("#pstatu option:selected").text();
        var yuzde = 0, renk = "";

        if (selectedStatus === "Bekliyor") {
            yuzde = 33;
            renk = "bg-warning";
        } else if (selectedStatus === "Çalışıyor") {
            yuzde = 66;
            renk = "bg-primary";
        } else if (selectedStatus === "Tamamlandı") {
            yuzde = 100;
            renk = "bg-success";
        } else if (selectedStatus === "İptal Edildi") {
            yuzde = 100;
            renk = "bg-danger";
            $("#cancelWarningArea").show();
            if (servicesNote.val() !== '') {
                submitButton.prop("disabled", true);
            } else {
                submitButton.prop("disabled", false);
            }
        }

        if (selectedStatus !== "İptal Edildi") {
            $("#cancelWarningArea").hide();
            submitButton.prop("disabled", false);
        }

        $('#progress-bar').css('width', yuzde + '%')
            .attr('aria-valuenow', yuzde)
            .removeClass()
            .addClass('progress-bar ' + renk);
    }
</script>
