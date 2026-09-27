<?php
use App\Helper\Security;
use App\Model\CustomerModel;

$cid = (int)($_GET["id"] ?? $_GET["customer"] ?? 0);

// Şifrelenmiş ID gelirse çözme denemesi (Router çözmediyse fallback)
if ($cid === 0 && !empty($_GET["id"]) && !is_numeric($_GET["id"])) {
    $decrypted = Security::decrypt($_GET["id"]);
    if (!$decrypted) {
        $decrypted = decrypt($_GET["id"]);
    }
    if ($decrypted) {
        $cid = (int)$decrypted;
    }
}

$customer = null;

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

    /* Select2 Özel Müşteri Seçim Kutusu */
    .select2-container .select2-selection--single {
        height: 42px !important;
        border-radius: 8px !important;
        border: 1px solid #dcdfe6 !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        font-size: 13.5px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        padding-left: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 10px !important;
    }
    .select2-dropdown {
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        z-index: 99999 !important;
    }
    .select2-results__option {
        padding: 10px 14px !important;
        font-size: 13px !important;
        border-bottom: 1px solid #f1f5f9;
        background-color: #ffffff !important;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .select2-results__option:last-child {
        border-bottom: none;
    }
    .select2-customer-option {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .customer-title {
        font-weight: 600;
        color: #0f172a !important;
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 13.5px;
    }
    .customer-title i {
        color: var(--focus-color, var(--theme-primary, #2563eb)) !important;
    }
    .customer-sub {
        font-size: 12px;
        color: #475569 !important;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }
    .customer-sub i {
        color: #64748b !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option--highlighted,
    .select2-results__option:hover {
        background: #2563eb !important;
        color: #ffffff !important;
    }
    .select2-container--default .select2-results__option--highlighted .customer-title,
    .select2-container--default .select2-results__option--highlighted .customer-title i,
    .select2-results__option:hover .customer-title,
    .select2-results__option:hover .customer-title i {
        color: #ffffff !important;
    }
    .select2-container--default .select2-results__option--highlighted .customer-sub,
    .select2-container--default .select2-results__option--highlighted .customer-sub i,
    .select2-results__option:hover .customer-sub,
    .select2-results__option:hover .customer-sub i {
        color: rgba(255, 255, 255, 0.85) !important;
    }

    /* Dark Mode Uyumluluğu */
    .dark-mode .select2-container .select2-selection--single {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #f1f5f9 !important;
    }
    .dark-mode .select2-dropdown {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark-mode .select2-results__option {
        background-color: #1e293b !important;
        border-bottom: 1px solid #334155 !important;
        color: #e2e8f0 !important;
    }
    .dark-mode .customer-title {
        color: #f8fafc !important;
    }
    .dark-mode .customer-sub {
        color: #94a3b8 !important;
    }
    .dark-mode .select2-search--dropdown .select2-search__field {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
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
            <div class="col-md-5 mb-2 mb-md-0">
                <label class="font-weight-bold mb-1" style="font-size: 13px;">Müşteri Seçin:</label>
                <select id="selectCustomer" data-placeholder="Firma adı veya yetkili yazarak arayın..." class="form-control select2-customer-select" style="width: 100%;">
                    <?php if ($customer): ?>
                        <option value="<?php echo (int)$customer['id']; ?>" selected
                            data-company="<?php echo htmlspecialchars($customer['company'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-yetkili="<?php echo htmlspecialchars($customer['yetkili'] ?? $customer['represant'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-gsm="<?php echo htmlspecialchars($customer['gsm'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-email="<?php echo htmlspecialchars($customer['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-city="<?php echo htmlspecialchars($customer['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-ilce="<?php echo htmlspecialchars($customer['ilce'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-encrypted-id="<?php echo htmlspecialchars(Security::encrypt((string)$customer['id']), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($customer['company'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php else: ?>
                        <option value="">Firma adı veya yetkili yazarak arayın...</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="font-weight-bold mb-1" style="font-size: 13px;">Etiket Boyutu:</label>
                <select id="selectLabelSize" class="form-control" style="height: 42px; border-radius: 8px;">
                    <option value="standard">Standart Kargo Etiketi (420px)</option>
                    <option value="small">Küçük Kutu Etiketi (320px)</option>
                </select>
            </div>
            <div class="col-md-1 mb-2 mb-md-0">
                <label class="font-weight-bold mb-1" style="font-size: 13px;">Adet:</label>
                <input type="number" id="labelCopies" class="form-control" value="1" min="1" max="20" style="height: 42px; border-radius: 8px;">
            </div>
            <div class="col-md-3 text-right">
                <button type="button" onclick="window.print()" class="btn btn-primary btn-md mr-1" style="height: 42px; border-radius: 8px; font-weight: 600;">
                    <i class="fa fa-print mr-1"></i> Etiketi Yazdır
                </button>
                <a href="firmalar" class="btn btn-outline-secondary btn-md" style="height: 42px; border-radius: 8px; line-height: 28px;">
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
            Lütfen etiket oluşturmak için yukarıdaki arama kutusundan bir müşteri seçin.
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    function formatCustomerResult(item) {
        if (item.loading) {
            return item.text;
        }
        if (!item.id) {
            return item.text;
        }

        var company = item.company || item.text || '';
        var yetkili = item.yetkili || '';
        var gsm = item.gsm || '';
        var email = item.email || '';
        var city = item.city || '';
        var ilce = item.ilce || '';

        var subParts = [];
        if (yetkili && yetkili !== '.' && yetkili !== '-') {
            subParts.push('<i class="fa fa-user mr-1"></i>' + $('<div>').text(yetkili).html());
        }
        if (gsm && gsm !== '.' && gsm !== '-') {
            subParts.push('<i class="fa fa-phone mr-1"></i>' + $('<div>').text(gsm).html());
        }
        if (email && email !== '.' && email !== '-') {
            subParts.push('<i class="fa fa-envelope mr-1"></i>' + $('<div>').text(email).html());
        }
        if (city && city !== '') {
            var cityText = city + (ilce ? ' / ' + ilce : '');
            subParts.push('<i class="fa fa-map-marker mr-1"></i>' + $('<div>').text(cityText).html());
        }
        var subHtml = subParts.length > 0 ? subParts.join(' &bull; ') : '<span style="opacity: 0.7;">Ek iletişim bilgisi bulunmuyor</span>';

        return $(
            '<div class="select2-customer-option">' +
                '<div class="customer-title"><i class="fa fa-building"></i> ' + $('<div>').text(company).html() + '</div>' +
                '<div class="customer-sub">' + subHtml + '</div>' +
            '</div>'
        );
    }

    function formatCustomerSelection(item) {
        if (!item.id) {
            return item.text || 'Firma adı veya yetkili yazarak arayın...';
        }
        var company = item.company || (item.element ? $(item.element).data('company') : '') || item.text;
        return company;
    }

    var $customerSelect = $('#selectCustomer');
    if ($customerSelect.length && $.fn.select2) {
        if ($customerSelect.data('select2')) {
            $customerSelect.select2('destroy');
        }

        $customerSelect.select2({
            placeholder: 'Firma adı veya yetkili yazarak arayın...',
            allowClear: false,
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: 'api/search_customers.php',
                dataType: 'json',
                delay: 100,
                data: function(params) {
                    return {
                        q: params.term || '',
                        limit: 30
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            },
            templateResult: formatCustomerResult,
            templateSelection: formatCustomerSelection,
            language: {
                searching: function() { return "Aranıyor..."; },
                noResults: function() { return "Eşleşen firma bulunamadı"; },
                loadingMore: function() { return "Daha fazla yükleniyor..."; },
                inputTooShort: function() { return "Aramak için yazmaya başlayın..."; }
            }
        });

        // Tıklandığında anında ilk 30 firmayı getir
        $customerSelect.on('select2:open', function() {
            var s2 = $customerSelect.data('select2');
            if (s2 && s2.dataAdapter) {
                s2.dataAdapter.query({ term: '' }, function(data) {
                    s2.results.append(data);
                });
            }
        });

        // Müşteri seçildiğinde sayfayı yeni müşteriyle aç
        $customerSelect.on('select2:select', function(e) {
            var data = e.params ? e.params.data : null;
            if (data) {
                var encId = data.encrypted_id || (data.element ? $(data.element).data('encrypted-id') : null);
                if (encId) {
                    window.location.href = 'etiket-goster?id=' + encodeURIComponent(encId);
                } else if (data.id) {
                    window.location.href = 'etiket-goster?id=' + encodeURIComponent(data.id);
                }
            }
        });
    }

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
