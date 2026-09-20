<?php
use App\Model\OfferModel;

$OfferModel = new OfferModel();
$sayfa_basligi = "Teklif Satır Listesi";
?>
<style>
    /* Premium Page Styles mimicking offers/list */
    .page-wrapper {
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

    /* Form & Table Card styling */
    .form-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 0 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        margin-bottom: 0;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 10px;
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
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
    }
    .form-card-header p {
        margin: 1px 0 0 0;
        font-size: 11.5px;
        color: #64748b;
    }

    .form-card .filters-form {
        padding: 16px 18px 8px 18px;
    }

    .form-card .responsive {
        padding: 4px !important;
        overflow-x: hidden;
        overflow-y: visible;
        width: 100%;
    }

    /* Custom Input and Label modernizations */
    .form-label {
        color: #475569;
        font-weight: 600;
        font-size: 12.5px;
        margin-bottom: 6px;
        display: block;
    }

    .custom-filter-input,
    .bootstrap-select .btn {
        height: 38px !important;
        border-radius: 8px !important;
        border: 1.5px solid #e5e7eb !important;
        padding: 8px 12px !important;
        font-size: 13.5px !important;
        background: #fafafa !important;
        transition: all 0.15s ease-in-out;
    }
    
    .custom-filter-input:focus {
        border-color: #3b82f6 !important;
        background: #fff !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    /* Table & DataTable spacing and sizing */
    table.dataTable {
        width: 100% !important;
    }
    
    .dataTables_length {
        margin-left: 10px;
    }

    .thead-colored { 
        background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%); 
        border-bottom: 2px solid #e5e7eb;
    }
    .thead-colored th {
        color: #374151;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        font-size: 0.75rem;
        padding: 12px 8px !important;
    }
    
    .btn-modern {
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .btn-modern-success {
        background-color: #059669;
        color: white;
        border: none;
    }
    .btn-modern-success:hover { background-color: #047857; transform: translateY(-1px); }
    
    .btn-modern-light {
        background-color: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
    }
    .btn-modern-light:hover { background-color: #e5e7eb; }

    .date-range-separator {
        display: flex;
        align-items: center;
        padding: 0 8px;
        color: #9ca3af;
    }

    /* Dark Mode Overrides */
    .dark-mode .page-title-text h4 {
        color: #f1f5f9 !important;
    }
    .dark-mode .page-title-text p {
        color: #94a3b8 !important;
    }
    .dark-mode .form-card {
        background: #282828 !important;
        border-color: #383838 !important;
    }
    .dark-mode .form-card-header {
        border-bottom: 2px solid #383838 !important;
    }
    .dark-mode .form-card-header h5 {
        color: #60a5fa !important;
    }
    .dark-mode .form-card-header p {
        color: #94a3b8 !important;
    }
    .dark-mode .form-card-header .card-icon {
        background: #1e293b !important;
        color: #60a5fa !important;
    }
    .dark-mode .form-label {
        color: #c4cdd8 !important;
    }
    .dark-mode .custom-filter-input,
    .dark-mode .bootstrap-select .btn {
        background: #1e1e1e !important;
        color: #e2e8f0 !important;
        border-color: #383838 !important;
    }
    .dark-mode .bootstrap-select .btn .filter-option-inner-inner {
        color: #e2e8f0 !important;
    }
    .dark-mode .btn-modern-light {
        background-color: #383838 !important;
        color: #e2e8f0 !important;
        border-color: #4f4f50 !important;
    }
    .dark-mode .btn-modern-light:hover {
        background-color: #484848 !important;
    }
    .dark-mode .thead-colored { 
        background: linear-gradient(180deg, #282828 0%, #1e1e1e 100%) !important; 
        border-bottom: 2px solid #383838 !important;
    }
    .dark-mode .thead-colored th {
        color: #c4cdd8 !important;
    }
</style>

<div class="pd-ltr-20 xs-pd-20-10">
    <div class="page-wrapper">

    <!-- Sayfa Üst Bölümü (Header + Quick Actions) -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap px-1" style="gap: 12px;">
        <div class="page-title-box">
            <div class="page-title-icon">
                <i class="fa fa-cubes"></i>
            </div>
            <div class="page-title-text">
                <h4>Teklif Satır Listesi</h4>
                <p>Tekliflere ait tüm ürün ve hizmet kalemlerinin detaylı dökümü</p>
            </div>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <?php if (permtrue("offers_dashboard") || permtrue("offersView") || permtrue("offerView")) { ?>
                <a href="index.php?p=offers/dashboard" class="btn btn-outline-primary btn-action-outline" title="Dashboard">
                    <i class="fa fa-dashboard"></i> <span class="d-none d-sm-inline">Dashboard</span>
                </a>
            <?php } ?>
            <a href="index.php?p=offers/list" class="btn btn-outline-secondary btn-action-outline" title="Teklifler Listesi">
                <i class="fa fa-list"></i> <span class="d-none d-sm-inline">Teklif Listesi</span>
            </a>
            <button type="button" class="btn btn-outline-secondary btn-action-outline" id="btnRefreshItems" title="Tabloyu Yenile">
                <i class="fa fa-refresh"></i> <span class="d-none d-sm-inline">Yenile</span>
            </button>
            <?php if (permtrue("data_export_offers")) { ?>
                <button type="button" class="btn btn-outline-success btn-action-outline" id="exportExcel" title="Excel Olarak İndir">
                    <i class="fa fa-file-excel-o"></i> <span class="d-none d-sm-inline">Excel'e Aktar</span>
                </button>
            <?php } ?>
            <?php if (permtrue("offerAdd")) { ?>
                <a href="index.php?p=offers/offer-manage" class="btn btn-action-primary">
                    <i class="fa fa-plus-circle"></i> <span>Yeni Teklif Oluştur</span>
                </a>
            <?php } ?>
        </div>
    </div>

    <!-- Main Data View (Filtre + Tablo Kartı) -->
    <div class="form-card animate-fade-in mx-1">
        <div class="form-card-header d-flex justify-content-between align-items-center">
            <div class="header-left-inner">
                <div class="card-icon">
                    <i class="fa fa-list-alt"></i>
                </div>
                <div>
                    <h5>Teklif Satırları</h5>
                    <p>Anlık arama, kalem bazlı filtreleme ve satır yönetimi</p>
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap: 8px;">
                <button type="button" id="filtersToggle" class="btn btn-outline-secondary btn-action-outline" style="height: 34px;">
                    <i class="fa fa-filter"></i> <span class="d-none d-sm-inline">Detaylı Filtreleme</span>
                </button>
            </div>
        </div>
        
        <div id="filtersCollapse" style="display:none; border-bottom: 1px solid #e5e7eb;" class="filters-form">
            <div class="row">
                <div class="col-md-3 mb-15">
                    <label class="form-label">Teklif No</label>
                    <input type="text" id="filter_offer_no" class="form-control custom-filter-input" placeholder="Örn: TK2024...">
                </div>
                <div class="col-md-3 mb-15">
                    <label class="form-label">Firma</label>
                    <!-- Using 'ajax-select' class to prevent automatic duplicate rendering via default 'selectpicker' logic -->
                    <select id="filter_company" class="form-control ajax-select" data-live-search="true" title="Firma Seçiniz"></select>
                </div>
                <div class="col-md-3 mb-15">
                    <label class="form-label">Kontak</label>
                    <select id="filter_contact" class="form-control ajax-select" data-live-search="true" title="Yetkili Seçiniz"></select>
                </div>
                <div class="col-md-3 mb-15">
                    <label class="form-label">Durum</label>
                    <select id="filter_status" class="form-control ajax-select" data-live-search="true" title="Durum Seçiniz"></select>
                </div>
                
                <div class="col-md-3 mb-15">
                    <label class="form-label">Temsilci</label>
                    <select id="filter_representative" class="form-control ajax-select" data-live-search="true" title="Temsilci Seçiniz"></select>
                </div>
                <div class="col-md-3 mb-15">
                    <label class="form-label">Stok Kodu</label>
                    <select id="filter_stok_kodu" class="form-control ajax-select" data-live-search="true" title="Stok Kodu Seçiniz"></select>
                </div>
                <div class="col-md-3 mb-15">
                    <label class="form-label">Ürün Adı</label>
                    <input type="text" id="filter_urun_adi" class="form-control custom-filter-input" placeholder="Kelime girin...">
                </div>
                <div class="col-md-3 mb-15">
                    <label class="form-label">Para Birimi</label>
                    <select id="filter_currency" class="form-control ajax-select" data-live-search="true" title="Para Birimi Seçiniz"></select>
                </div>
                
                <div class="col-md-6 mb-15">
                    <label class="form-label">Teklif Tarih Aralığı</label>
                    <div class="d-flex w-100 align-items-center">
                        <div class="position-relative flex-fill">
                            <input type="text" id="filter_date_start" class="form-control custom-filter-input date-picker" placeholder="gg.aa.yyyy">
                        </div>
                        <span class="date-range-separator">-</span>
                        <div class="position-relative flex-fill">
                            <input type="text" id="filter_date_end" class="form-control custom-filter-input date-picker" placeholder="gg.aa.yyyy">
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-15">
                    <label class="form-label">Açıklama</label>
                    <input type="text" id="filter_desc" class="form-control custom-filter-input" placeholder="İçerikte ara...">
                </div>
                
                <div class="col-md-3 mb-15 d-flex align-items-end justify-content-end">
                    <button type="button" id="clearFilters" class="btn btn-modern btn-modern-light mr-2">
                        <i class="fa fa-rotate-left"></i> Sıfırla
                    </button>
                    <button type="button" id="applyFilters" class="btn btn-modern btn-modern-success">
                        <i class="fa fa-search"></i> UYGULA
                    </button>
                </div>
            </div>
        </div>
        
        <div class="responsive">
            <table id="itemsTable" class="data-table table-hover table-bordered" style="width: 100%;">
                <thead class="thead-colored">
                    <tr>
                        <th style="width:40px">#</th>
                        <th class="no-export" style="width:70px">İŞLEMLER</th>
                        <th>T.NO</th>
                        <th>FİRMA ADI</th>
                        <th>TARİH</th>
                        <th>STOK KODU</th>
                        <th style="width:250px">ÜRÜN / HİZMET ADI</th>
                        <th>MİKTAR</th>
                        <th>BİRİM FİYAT</th>
                        <th>TUTAR</th>
                        <th>İSKONTO</th>
                        <th>KDV</th>
                        <th>TOPLAM</th>
                        <th>DURUM</th>
                    </tr>
                </thead>
                <tbody class="font-13">
                </tbody>
            </table>
        </div>
    </div>

    </div>
</div>

<script src="src/plugins/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
<script src="src/plugins/bootstrap-select/dist/js/i18n/defaults-tr_TR.min.js"></script>
<script>
$(document).ready(function() {
    // Manual selective initialization to avoid duplicates
    function initSelectPicker(selector) {
        $(selector).selectpicker({
            iconBase: 'fa',
            tickIcon: 'fa-check',
            style: '',
            styleBase: 'form-control',
            liveSearchStyle: 'contains'
        });
    }

    // Populate drop-downs then manually initialize them
    $.getJSON('App/api/get-offer-item-filters.php', function(resp) {
        function fillSelect(id, arr) {
            var $s = $(id);
            $s.empty().append('<option value="">Tümü</option>');
            (arr || []).forEach(function(v) {
                var esc = $('<div>').text(v).html();
                $s.append('<option value="' + esc + '">' + esc + '</option>');
            });
            // Once content added, initialize manually
            initSelectPicker(id);
        }
        
        fillSelect('#filter_company', resp.company_name);
        fillSelect('#filter_contact', resp.company_authors);
        fillSelect('#filter_representative', resp.creator_name);
        fillSelect('#filter_status', resp.durum);
        fillSelect('#filter_stok_kodu', resp.stok_kodu);
        fillSelect('#filter_currency', resp.salecur);
    });

    var table = $('#itemsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        autoWidth: false,
        ajax: {
            url: 'App/api/get-offer-items.php',
            type: 'POST',
            data: function(d) {
                d.filters = {
                    offer_no: $('#filter_offer_no').val(),
                    company: $('#filter_company').val(),
                    contact: $('#filter_contact').val(),
                    status: $('#filter_status').val(),
                    representative: $('#filter_representative').val(),
                    stok_kodu: $('#filter_stok_kodu').val(),
                    urun_adi: $('#filter_urun_adi').val(),
                    currency: $('#filter_currency').val(),
                    date_start: $('#filter_date_start').val(),
                    date_end: $('#filter_date_end').val(),
                    description: $('#filter_desc').val()
                };
            }
        },
        columns: [
            { data: 'sira_no', orderable: false },
            { data: 'islemler', orderable: false, className: 'text-center' },
            { data: 'teklif_no', className: 'font-weight-bold text-blue' },
            { data: 'firma' },
            { data: 'tarih' },
            { data: 'stok_kodu', className: 'font-weight-600' },
            { data: 'urun_adi' },
            { data: 'miktar', className: 'text-right font-weight-600' },
            { data: 'birim_fiyat', className: 'text-right' },
            { data: 'tutar', className: 'text-right font-weight-600' },
            { data: 'iskonto', className: 'text-right text-danger' },
            { data: 'kdv', className: 'text-right text-primary' },
            { data: 'toplam', className: 'text-right font-weight-bold' },
            { data: 'durum', className: 'text-center' }
        ],
        order: [[2, 'desc']],

        language: {
            url: "include/js/tr.json",
            search: "_INPUT_",
            searchPlaceholder: "Genel Arama...",
            lengthMenu: "Göster: _MENU_"
        },
        buttons: [
            {
                extend: "excel",
                className: "d-none",
                exportOptions: {
                    columns: ":visible:not(.no-export)"
                }
            }
        ],
        initComplete: function() {
            if (typeof addDataTableColumnSearchRow === "function") {
                addDataTableColumnSearchRow(this.api());
            }
        },
        drawCallback: function() {
            $('.dataTables_paginate .paginate_button').addClass('btn btn-sm');
        }
    });

    // Apply filters automatically on change for Select fields
    $('#filtersCollapse select').on('change', function() {
         table.ajax.reload();
    });

    // Click hooks
    $('#applyFilters').on('click', function() { table.ajax.reload(); });
    
    $('#clearFilters').on('click', function() {
        $('#filtersCollapse input').val('');
        $('#filtersCollapse select').val('').selectpicker('refresh');
        table.ajax.reload();
    });

    $('#filtersToggle').on('click', function() {
        var $c = $('#filtersCollapse');
        var $i = $('#toggleIcon');
        if($c.is(':visible')) {
            $c.slideUp(250);
            $i.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            $c.slideDown(250);
            $i.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });

    $(document).on('input keyup change blur', '.date-picker', function() {
        var v = (this.value || '').replace(/[^0-9]/g,'');
        if(v.length > 2) v = v.slice(0,2) + '.' + v.slice(2);
        if(v.length > 5) v = v.slice(0,5) + '.' + v.slice(5);
        this.value = v.slice(0,10);
    });

    // Tabloyu Yenile Butonu
    $(document).on('click', '#btnRefreshItems', function() {
        var $btn = $(this);
        var $icon = $btn.find('i');
        $icon.addClass('fa-spin');
        table.ajax.reload(function() {
            setTimeout(function() {
                $icon.removeClass('fa-spin');
            }, 300);
        }, false);
    });

    // Wire up standard Excel Export trigger
    $('#exportExcel').on('click', function() {
        table.button('.buttons-excel').trigger();
    });
});
</script>

