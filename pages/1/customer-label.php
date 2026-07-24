<?php
use App\Model\CustomerModel;

$cid = $_GET["id"] ?? $_GET["customer"] ?? 0;

// Şifrelenmiş ID gelirse çözme denemesi
if (!is_numeric($cid) && !empty($cid)) {
    $decrypted = decrypt($cid);
    if ($decrypted) {
        $cid = $decrypted;
    }
}

$cid = (int)$cid;

$customer = null;

// Müşterileri dropdown seçimi için çek
$allStmt = $ac->prepare("SELECT id, company FROM customers WHERE deleted_at IS NULL ORDER BY company ASC");
$allStmt->execute();
$allCustomers = $allStmt->fetchAll(PDO::FETCH_ASSOC);

if ($cid > 0) {
    $stmt = $ac->prepare("
        SELECT c.*, cg.title as group_title 
        FROM customers c 
        LEFT JOIN cgroups cg ON cg.id = c.grp 
        WHERE c.id = ? AND c.deleted_at IS NULL
    ");
    $stmt->execute([$cid]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
    /* Ekran Stilleri */
    .label-container {
        padding: 20px 0;
    }
    .label-control-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid #f0f0f0;
        margin-bottom: 25px;
    }
    .dark-mode .label-control-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #e2e8f0;
    }

    /* Önizleme Alanı */
    .label-preview-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
        margin-top: 20px;
    }

    /* Etiket Şablonu (Kargo / Adres Etiketi) */
    .shipping-label {
        width: 420px;
        background: #ffffff;
        border: 2px solid #1e293b;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        position: relative;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #0f172a;
        box-sizing: border-box;
    }

    .shipping-label.label-size-small {
        width: 320px;
        padding: 14px;
    }

    .shipping-label .label-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px dashed #cbd5e1;
        padding-bottom: 12px;
        margin-bottom: 14px;
    }
    .shipping-label .brand-badge {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        background: #0f172a;
        color: #ffffff;
        padding: 4px 10px;
        border-radius: 6px;
    }
    .shipping-label .label-id {
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
    }

    .shipping-label .recipient-title {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .shipping-label .company-name {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 10px;
        line-height: 1.3;
        word-break: break-word;
    }
    .shipping-label.label-size-small .company-name {
        font-size: 15px;
    }

    .shipping-label .info-row {
        display: flex;
        align-items: flex-start;
        margin-bottom: 8px;
        font-size: 13px;
    }
    .shipping-label .info-icon {
        width: 22px;
        color: #0284c7;
        font-size: 14px;
        margin-top: 2px;
        flex-shrink: 0;
    }
    .shipping-label .info-text {
        color: #334155;
        font-weight: 500;
        line-height: 1.4;
    }

    .shipping-label .label-footer {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        border-top: 2px solid #e2e8f0;
        padding-top: 12px;
        margin-top: 14px;
    }

    .shipping-label .qr-box img {
        width: 70px;
        height: 70px;
        border-radius: 6px;
    }

    /* Yazdırma (Print) CSS Kuralları */
    @media print {
        @page {
            margin: 10mm;
            size: auto;
        }
        html, body {
            background: #ffffff !important;
            color: #000000 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Yazdırılmayacak dış elemanları gizle */
        .header, 
        .left-side-bar, 
        .footer-wrap, 
        .label-control-card, 
        .preloader, 
        #preloader, 
        .btn, 
        nav, 
        .breadcrumb,
        .custom-context-menu {
            display: none !important;
        }

        /* Sayfa kapsayıcılarının kısıtlamalarını kaldır ki etiket alanı gözüksün */
        .main-container, 
        #content, 
        #maincontainer, 
        .pd-ltr-20, 
        .xs-pd-10-10,
        .label-container {
            display: block !important;
            position: static !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
        }

        .label-preview-wrapper {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 20px !important;
            justify-content: flex-start !important;
            margin: 0 !important;
            padding: 0 !important;
            position: static !important;
        }

        .shipping-label {
            box-shadow: none !important;
            border: 2px solid #000000 !important;
            page-break-inside: avoid !important;
            background: #ffffff !important;
            color: #000000 !important;
        }

        .shipping-label .brand-badge {
            background: #000000 !important;
            color: #ffffff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .shipping-label .company-name, 
        .shipping-label .info-text, 
        .shipping-label .label-id {
            color: #000000 !important;
        }
    }
</style>

<div class="label-container pd-ltr-20">
    <!-- Kontrol Kartı -->
    <div class="label-control-card">
        <div class="row align-items-center">
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="font-weight-bold mb-1" style="font-size: 13px;">Müşteri Seçin:</label>
                <select id="selectCustomer" class="form-control selectpicker" data-live-search="true">
                    <?php foreach ($allCustomers as $cItem): ?>
                        <option value="<?php echo $cItem['id']; ?>" <?php echo $cItem['id'] == $cid ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cItem['company']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="font-weight-bold mb-1" style="font-size: 13px;">Etiket Boyutu:</label>
                <select id="selectLabelSize" class="form-control">
                    <option value="standard">Standart Kargo Etiketi (420px)</option>
                    <option value="small">Küçük Kutu Etiketi (320px)</option>
                </select>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <label class="font-weight-bold mb-1" style="font-size: 13px;">Kopyala Adedi:</label>
                <input type="number" id="labelCopies" class="form-control" value="1" min="1" max="20">
            </div>
            <div class="col-md-3 text-right">
                <button type="button" onclick="window.print()" class="btn btn-primary btn-md mr-1" style="border-radius: 8px;">
                    <i class="fa fa-print mr-1"></i> Etiketi Yazdır
                </button>
                <a href="index.php?p=customers/list" class="btn btn-outline-secondary btn-md" style="border-radius: 8px;">
                    <i class="fa fa-arrow-left mr-1"></i> Listeye Dön
                </a>
            </div>
        </div>
    </div>

    <!-- Etiket Önizleme Alanı -->
    <?php if ($customer): ?>
        <?php 
            $fullAddress = trim(($customer['address'] ?? '') . ' ' . ($customer['ilce'] ?? '') . ' ' . ($customer['city'] ?? ''));
            $qrData = urlencode("Firma: " . $customer['company'] . "\nTel: " . ($customer['gsm'] ?? '') . "\nEmail: " . ($customer['email'] ?? ''));
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $qrData;
        ?>
        <div id="labelPreviewArea" class="label-preview-wrapper">
            <!-- JS ile kopya sayısına göre tekrarlanacak etiket -->
            <div class="shipping-label" id="masterLabel">
                <div class="label-header">
                    <span class="brand-badge">ALICI FİRMA ETİKETİ</span>
                    <span class="label-id">#MUST-<?php echo sprintf('%05d', $customer['id']); ?></span>
                </div>
                
                <div class="recipient-title">FİRMA ADI / ÜNVAN</div>
                <div class="company-name"><?php echo htmlspecialchars($customer['company']); ?></div>

                <?php if (!empty($customer['yetkili']) || !empty($customer['represant'])): ?>
                    <div class="info-row">
                        <i class="fa fa-user info-icon"></i>
                        <div class="info-text">
                            <strong>İlgili / Temsilci:</strong> <?php echo htmlspecialchars($customer['yetkili'] ?: $customer['represant']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($fullAddress)): ?>
                    <div class="info-row">
                        <i class="fa fa-map-marker info-icon"></i>
                        <div class="info-text">
                            <strong>Adres:</strong> <?php echo htmlspecialchars($fullAddress); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($customer['gsm']) || !empty($customer['gsm2'])): ?>
                    <div class="info-row">
                        <i class="fa fa-phone info-icon"></i>
                        <div class="info-text">
                            <strong>Tel:</strong> <?php echo htmlspecialchars($customer['gsm'] . ($customer['gsm2'] ? ' / ' . $customer['gsm2'] : '')); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($customer['email'])): ?>
                    <div class="info-row">
                        <i class="fa fa-envelope info-icon"></i>
                        <div class="info-text">
                            <strong>E-Posta:</strong> <?php echo htmlspecialchars($customer['email']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($customer['group_title'])): ?>
                    <div class="info-row">
                        <i class="fa fa-tag info-icon"></i>
                        <div class="info-text">
                            <strong>Grup:</strong> <?php echo htmlspecialchars($customer['group_title']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="label-footer">
                    <div>
                        <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">TARİH</div>
                        <div style="font-size: 12px; font-weight: 700; color: #0f172a;"><?php echo date('d.m.Y'); ?></div>
                    </div>
                    <div class="qr-box">
                        <img src="<?php echo $qrUrl; ?>" alt="QR Code">
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center p-4" style="border-radius: 12px;">
            <i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i>
            Müşteri bilgisi bulunamadı veya geçersiz müşteri ID'si seçildi.
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Müşteri seçimi değiştiğinde sayfayı yenile
    $('#selectCustomer').on('change', function() {
        var val = $(this).val();
        if(val) {
            window.location.href = 'index.php?p=customer-label&id=' + val;
        }
    });

    // Boyut değişimi
    $('#selectLabelSize').on('change', function() {
        var size = $(this).val();
        if(size === 'small') {
            $('.shipping-label').addClass('label-size-small');
        } else {
            $('.shipping-label').removeClass('label-size-small');
        }
    });

    // Kopya sayısı değişimi
    $('#labelCopies').on('input change', function() {
        var count = parseInt($(this).val()) || 1;
        if(count < 1) count = 1;
        if(count > 20) count = 20;

        var $wrapper = $('#labelPreviewArea');
        var $master = $('#masterLabel');
        if(!$master.length) return;

        // Temizle ve tekrar üret
        $wrapper.find('.shipping-label:not(#masterLabel)').remove();
        
        for(var i = 1; i < count; i++) {
            var $clone = $master.clone().removeAttr('id');
            $wrapper.append($clone);
        }
    });
});
</script>
