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

    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        border: 1px solid #f0f0f0;
    }

    .form-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f3f4f6;
    }

    .form-card-header .header-left-inner {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .form-card-header .card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        background: #eff6ff;
        color: #3b82f6;
    }

    .form-card-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e3a5f;
    }

    .responsive {
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

<div class="page-header mb-20">
    <div class="row align-items-center">
        <div class="col-md-12 col-sm-12">
            <nav aria-label="breadcrumb" role="navigation">
                <ol class="breadcrumb bg-transparent p-0 m-0 mb-2 font-12">
                    <li class="breadcrumb-item"><a href="index.php" class="text-muted">Anasayfa</a></li>
                    <li class="breadcrumb-item"><a href="index.php?p=offers/list" class="text-muted">Teklif Listesi</a></li>
                    <li class="breadcrumb-item active font-weight-bold text-dark" aria-current="page">Teklif Satır Listesi</li>
                </ol>
            </nav>
            <div class="title">
                <h4 class="font-weight-bold text-dark mb-0"><i class="fa fa-list-alt text-secondary mr-2"></i> <?php echo $sayfa_basligi; ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="form-card animate-fade-in mb-20">
    <div class="form-card-header d-flex align-items-center justify-content-between" id="filtersToggle" style="cursor: pointer; border-bottom: none; margin-bottom: 0;">
        <div class="header-left-inner">
            <div class="card-icon">
                <i class="fa fa-sliders"></i>
            </div>
            <div>
                <h5>Gelişmiş Filtreler</h5>
            </div>
        </div>
        <span class="text-muted font-12"><i class="fa fa-chevron-down" id="toggleIcon"></i></span>
    </div>
    
    <div id="filtersCollapse" style="display:none; margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 20px;" class="filters-form">
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
</div>

<!-- Main Data View -->
<div class="form-card animate-fade-in mb-30">
    <div class="form-card-header d-flex justify-content-between align-items-center">
        <div class="header-left-inner">
            <div class="card-icon">
                <i class="fa fa-table"></i>
            </div>
            <div>
                <h5>Sonuçlar</h5>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <button id="exportExcel" class="btn btn-sm btn-outline-success font-12 font-weight-bold px-3" style="border-radius:6px; height: 36px; display: inline-flex; align-items: center; gap: 4px;">
                <i class="fa fa-file-excel-o"></i> Excel'e Aktar
            </button>
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

    // Wire up standard Excel Export trigger
    $('#exportExcel').on('click', function() {
        table.button('.buttons-excel').trigger();
    });
});
</script>

