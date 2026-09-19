<?php
// require_once "App/Helper/date.php";
// require_once "App/Model/OfferModel.php";

use App\Helper\Date;
use App\Model\OfferModel;

$ois = @$_GET["id"];
$cid = @$_GET["cid"];
$sablonlari_goster = isset($_GET["sablon"]) ? true : false;
if($sablonlari_goster){
    $sayfa_basligi = "Şablon Teklifler";
}else{
    $sayfa_basligi = "Teklifleri Görüntüle";
};

$OfferModel = new OfferModel();


$pendingOffersQuery = $ac->query('SELECT COUNT(*) as cnt, COALESCE(SUM(total_price), 0) as total FROM offers WHERE statu = 1');
$pendingOffersData = $pendingOffersQuery ? $pendingOffersQuery->fetch(PDO::FETCH_ASSOC) : [];
$pendingOffersCount = (int) ($pendingOffersData['cnt'] ?? 0);
$pendingOffersSum = (float) ($pendingOffersData['total'] ?? 0);

$wonOffersQuery = $ac->query('SELECT COUNT(*) as cnt, COALESCE(SUM(total_price), 0) as total FROM offers WHERE statu = 2');
$wonOffersData = $wonOffersQuery ? $wonOffersQuery->fetch(PDO::FETCH_ASSOC) : [];
$wonOffersCount = (int) ($wonOffersData['cnt'] ?? 0);
$wonOffersSum = (float) ($wonOffersData['total'] ?? 0);

$totalOffers = $pendingOffersCount + $wonOffersCount;
$offerWinRate = $totalOffers > 0 ? round(($wonOffersCount / $totalOffers) * 100, 1) : 0;


if (@$_GET["st"] == "offercopy") {
    $creator = sesset("lid");
    $newoffernumber = "TK" . newNumber("offers");
    $ofcopy = $ac->prepare("INSERT INTO offers (offerNumber, cid, company_authors,
											   total_price,mycompany,
									   		   authors,reg_date,tax,creativer,
											   notes,currency,statu,
											   dollar,euro,payment_period,
											   offer_header,offer_header_content,
											   offer_footer,offer_footer_content,
											   description,
											   file, kdv,iskonto, subdescription,
											   buyTotal,saleTotal,amounttotal,
											   curDollar,curEuro,
											   DolarTotal ,EuroTotal ,TLTotal ) 
										SELECT ? , cid, company_authors,
											   total_price,mycompany,
											   authors,reg_date,tax,?,
											   notes,currency,statu,
											   dollar,euro,payment_period,
											   offer_header,offer_header_content,
											   offer_footer,offer_footer_content,
											   description,
											   file, kdv, iskonto, subdescription,
											   buyTotal,saleTotal,amounttotal,
											   curDollar,curEuro,
											   DolarTotal ,EuroTotal ,TLTotal
						   FROM offers	WHERE id = ?;");
    $ofcopy->execute(array($newoffernumber, $creator, $ois));
    $lastid = $ac->lastInsertId();

    $offmat = $ac->prepare("SELECT * FROM offermatters WHERE oid = ?");
    $offmat->execute(array($ois));

    $items = $offmat->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $insq = $ac->prepare("INSERT INTO offermatters SET xid = ?,oid = ?,stokKodu = ? , 
														   title = ? ,unit = ? ,amount = ? , 
														   buyprice = ? ,buycur = ?,saleprice = ? ,
														   salecur = ? ,total_price = ?");

        $insq->execute(
            array(
                $item["xid"],
                $lastid,
                $item["stokKodu"],
                $item["title"],
                $item["unit"],
                $item["amount"],
                $item["buyprice"],
                $item["buycur"],
                $item["saleprice"],
                $item["salecur"],
                $item["total_price"]
            )
        );
    }
}

///
if (@$_GET["st"] == "success-mail") {
    showAlert("success", "Mail başarı ile gönderildi!");
}

?>

<style>
    /* Premium offer list page styles */
    .offer-list-wrapper {
        width: 100%;
    }

    /* Form Card styling */
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
        overflow-x: auto;
        width: 100%;
        min-height: 280px;
    }

    .filters-form .form-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }

    .filters-form .form-control,
    .filters-form .bootstrap-select .btn {
        border-radius: 8px !important;
        border: 1.5px solid #e5e7eb !important;
        padding: 8px 12px;
        font-size: 13.5px;
        background: #fafafa;
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
    .dark-mode .filters-form .form-label {
        color: #c4cdd8 !important;
    }
    .dark-mode .filters-form .form-control,
    .dark-mode .filters-form .bootstrap-select .btn {
        background: #1e1e1e !important;
        color: #e2e8f0 !important;
        border-color: #383838 !important;
    }
    .dark-mode .filters-form .bootstrap-select .btn .filter-option-inner-inner {
        color: #e2e8f0 !important;
    }
    .dark-mode #clearFilters.btn-outline-secondary {
        background-color: #383838 !important;
        color: #e2e8f0 !important;
        border-color: #4f4f50 !important;
    }
    .dark-mode #clearFilters.btn-outline-secondary:hover {
        background-color: #484848 !important;
    }
    .dark-mode .data-table .form-control {
        background: #1e1e1e !important;
        color: #e2e8f0 !important;
        border-color: #383838 !important;
    }
</style>

<div class="pd-ltr-20 xs-pd-20-10">
    <div class="offer-list-wrapper">
    <!-- Clean Title & Actions Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-dark font-weight-bold" style="font-size: 22px; margin: 0;"><?php echo $sayfa_basligi; ?></h4>
        <div class="d-flex align-items-center">
            <?php if (permtrue("data_export_offers")) { ?>
                <a href="#" class="btn btn-outline-secondary btn-sm d-flex align-items-center" id="exportExcel" style="border-radius: 8px; padding: 8px 16px; height: 38px;">
                    <i class="fa fa-file-excel-o mr-1"></i> Excel'e Aktar
                </a>
            <?php } ?>
            <?php if (permtrue("offerAdd")) { ?>
                <a href="index.php?p=offers/offer-manage" class="btn btn-primary btn-sm d-flex align-items-center ml-2" style="border-radius: 8px; padding: 8px 16px; height: 38px; background: linear-gradient(135deg, #1e3a5f, #3b7dd8); border: none;">
                    <i class="fa fa-plus mr-1"></i> Yeni Teklif Oluştur
                </a>
            <?php } ?>
        </div>
    </div>

    <!-- Erken LocalStorage Kontrolü (Flicker Önleme) -->
    <script>
        (function() {
            try {
                if (localStorage.getItem('aydinogullari_kpi_offers_collapsed') === 'true') {
                    document.documentElement.classList.add('kpi-offers-collapsed-early');
                }
            } catch(e) {}
        })();
    </script>

    <!-- Özet Bilgiler (CRM KPI Kartları) -->
    <div id="kpiSummarySection" class="row mx-0 mb-4 kpi-summary-collapse">
        <!-- Bekleyen Teklifler -->
        <div class="col-lg-6 col-md-6 col-sm-12 mb-3 mb-md-0 px-2">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Bekleyen Teklifler</span>
                        <div class="crm-kpi-value"><?php echo number_format($pendingOffersCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-amber">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="weight-600 text-dark" style="font-size: 11px;">
                        Hacim: <span class="text-primary"><?php echo tlFormat($pendingOffersSum); ?></span>
                    </span>
                    <span class="crm-badge-soft soft-amber">Pipeline</span>
                </div>
            </div>
        </div>

        <!-- Kazanılan / Tamamlanan Teklifler -->
        <div class="col-lg-6 col-md-6 col-sm-12 mb-3 mb-md-0 px-2">
            <div class="crm-kpi-card">
                <div class="crm-kpi-header">
                    <div>
                        <span class="crm-kpi-label">Kazanılan Teklifler</span>
                        <div class="crm-kpi-value"><?php echo number_format($wonOffersCount, 0, ',', '.'); ?></div>
                    </div>
                    <div class="crm-kpi-icon icon-emerald">
                        <i class="fa fa-trophy"></i>
                    </div>
                </div>
                <div class="crm-kpi-footer">
                    <span class="weight-600 text-dark" style="font-size: 11px;">
                        Ciro: <span class="text-success"><?php echo tlFormat($wonOffersSum); ?></span>
                    </span>
                    <span class="crm-badge-soft soft-emerald">%<?php echo $offerWinRate; ?> Başarı</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste Card -->
    <div class="form-card animate-fade-in">
        <div class="form-card-header d-flex justify-content-between align-items-center">
            <div class="header-left-inner">
                <div class="card-icon">
                    <i class="fa fa-list"></i>
                </div>
                <div>
                    <h5>Teklif Listesi</h5>
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap: 8px;">
                <button type="button" id="filtersToggle" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px; height: 36px;">
                    <i class="fa fa-filter mr-1"></i> Detaylı Filtreleme
                </button>
                <button type="button" id="toggleKpiSummary" class="btn btn-outline-secondary btn-sm" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fa fa-chevron-up"></i>
                </button>
            </div>
        </div>
        
        <div id="filtersCollapse" style="display:none; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 20px;" class="filters-form">
            <div class="row">
                <div class="col-md-3 mb-10">
                    <label class="form-label">Teklif No</label>
                    <input type="text" id="filter_offer_no" class="form-control" placeholder="Teklif numarasını yazınız.">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Firma</label>
                    <select id="filter_company" class="form-control select-picker" data-live-search="true" title="Firma seçin veya yazın"></select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Ürün / Konu</label>
                    <select id="filter_subject" class="form-control select-picker" data-live-search="true" title="Konu seçin veya yazın"></select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Durum</label>
                    <select id="filter_status" class="form-control select-picker" data-live-search="true" title="Durum seçin veya yazın"></select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Başlangıç Tarihi</label>
                    <input type="text" id="filter_date_start" class="form-control date-picker" placeholder="01.11.2024">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="text" id="filter_date_end" class="form-control date-picker" placeholder="01.01.2025">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Toplam (min)</label>
                    <input type="number" step="0.01" id="filter_total_min" class="form-control" placeholder="Başlangıç toplamını yazınız.">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Toplam (max)</label>
                    <input type="number" step="0.01" id="filter_total_max" class="form-control" placeholder="Bitiş toplamını yazınız.">
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Para Birimi</label>
                    <select id="filter_currency" class="form-control select-picker" data-live-search="true" title="Para birimi seçin veya yazın">
                        <option value="">Tümü</option>
                    </select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Personel / Temsilci</label>
                    <select id="filter_creator" class="form-control select-picker" data-live-search="true" title="Personel seçin veya yazın"></select>
                </div>
                <div class="col-md-3 mb-10">
                    <label class="form-label">Ödeme Vadesi</label>
                    <select id="filter_payment_period" class="form-control select-picker" data-live-search="true" title="Vade seçin veya yazın"></select>
                </div>
            </div>
            <div class="text-right mt-15">
                <button type="button" id="applyFilters" class="btn btn-success" style="border-radius: 8px; padding: 8px 20px;">ARA</button>
                <button type="button" id="clearFilters" class="btn btn-outline-secondary ml-1" style="border-radius: 8px; padding: 8px 20px;">Temizle</button>
            </div>
        </div>
        
        <div class="responsive">
            <table id="offerTable" class="data-table table-hover table-bordered" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Sıra No</th>
                        <th class="w-10">Oluşturma Tarihi</th>
                        <th>Teklif No</th>
                        <th>Müşteri</th>
                        <th>Toplam Tl Tutar</th>
                        <th>Durum</th>
                        <th>Onay Tarihi</th>
                        <th>Konusu</th>
                        <th>Ödeme Vadesi</th>
                        <th>Teklif Veren</th>
                        <th class="no-export" style="width: 7%;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="odd data-row text-center">
                        <td colspan="11">Veriler Yükleniyor...</td>
                    </tr>
                </tbody>
            </table>
    </div>
</div>
</div>
<style>
    /* DataTables'ın sabit genişliklerini ezmek için */
table.dataTable {
    width: 100% !important;
}

.dataTables_length{
    margin-left: 10px;
}

/* Dropdown menünün tablonun dışına taşabilmesi için */
table.data-table,
table.dataTable {
    overflow: visible !important;
}

/* Sağ Tık (Context Menu) Stilleri */
.custom-context-menu {
    display: none;
    position: fixed;
    z-index: 99999;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.08);
    padding: 8px 0;
    min-width: 220px;
    backdrop-filter: blur(8px);
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.dark-mode .custom-context-menu {
    background: #1e293b !important;
    border-color: #334155 !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
}
.custom-context-menu .cm-header {
    padding: 8px 16px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
}
.dark-mode .custom-context-menu .cm-header {
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
}
.custom-context-menu a,
.custom-context-menu button {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 9px 16px;
    font-size: 13.5px;
    color: #334155;
    background: transparent;
    border: none;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}
.dark-mode .custom-context-menu a,
.dark-mode .custom-context-menu button {
    color: #e2e8f0 !important;
}
.custom-context-menu a:hover,
.custom-context-menu button:hover {
    background: #f1f5f9;
    color: #0284c7;
}
.dark-mode .custom-context-menu a:hover,
.dark-mode .custom-context-menu button:hover {
    background: #334155 !important;
    color: #38bdf8 !important;
}
.custom-context-menu a.cm-danger,
.custom-context-menu button.cm-danger {
    color: #ef4444;
}
.custom-context-menu a.cm-danger:hover,
.custom-context-menu button.cm-danger:hover {
    background: #fef2f2;
    color: #dc2626;
}
.dark-mode .custom-context-menu a.cm-danger:hover,
.dark-mode .custom-context-menu button.cm-danger:hover {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #f87171 !important;
}
.custom-context-menu i {
    width: 20px;
    font-size: 14px;
    margin-right: 10px;
    text-align: center;
}
.custom-context-menu .cm-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 4px 0;
}
.dark-mode .custom-context-menu .cm-divider {
    background: #334155 !important;
}
tr.context-menu-active {
    background-color: rgba(59, 130, 246, 0.1) !important;
}
</style>
<script src="pages/1/offers/offer.js"></script>
<script src="src/plugins/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
<script src="src/plugins/bootstrap-select/dist/js/i18n/defaults-tr_TR.min.js"></script>

<script>
$(document).ready(function() {
    // Açılır menünün kesilmesini önlemek için tablonun sarmalayıcısının overflow'unu geçici olarak değiştiriyoruz
    $(document).on('show.bs.dropdown', '#offerTable .dropdown', function () {
        var $responsive = $('#offerTable').closest('.responsive');
        if ($responsive.length) {
            $responsive[0].style.setProperty('overflow', 'visible', 'important');
            $responsive[0].style.setProperty('overflow-x', 'visible', 'important');
            $responsive[0].style.setProperty('overflow-y', 'visible', 'important');
        }
    });

    $(document).on('hide.bs.dropdown', '#offerTable .dropdown', function () {
        var $responsive = $('#offerTable').closest('.responsive');
        if ($responsive.length) {
            $responsive[0].style.setProperty('overflow', '', '');
            $responsive[0].style.setProperty('overflow-x', '', '');
            $responsive[0].style.setProperty('overflow-y', '', '');
        }
    });

    $('.selectpicker').selectpicker();
    $.getJSON('App/api/get-offer-filter-options.php?sablon=<?php echo $sablonlari_goster ? 1 : 0; ?>', function(resp){
        function fillSelect(id, arr){
            var $s = $(id);
            $s.empty();
            $s.append('<option value=""></option>');
            if($s.attr('id') === 'filter_currency'){
                $s.append('<option value="">Tümü</option>');
            }
            (arr || []).forEach(function(v){
                if(v && v.trim() !== ''){
                    var esc = $('<div>').text(v).html();
                    $s.append('<option value="'+esc+'">'+esc+'</option>');
                }
            });
            $s.selectpicker('refresh');
            enableManualEntry('#'+$s.attr('id'));
        }
        fillSelect('#filter_company', resp.company_name);
        fillSelect('#filter_subject', resp.offer_subject);
        fillSelect('#filter_status', resp.durum);
        fillSelect('#filter_creator', resp.creator_name);
        fillSelect('#filter_payment_period', resp.payment_period);
        fillSelect('#filter_currency', resp.currency);
    });

    function enableManualEntry(sel){
        var $s = $(sel);
        var $wrap = $s.next('.bootstrap-select');
        $wrap.off('keydown.manual').on('keydown.manual', '.bs-searchbox input', function(e){
            if(e.key === 'Enter'){
                e.preventDefault();
                var q = $(this).val();
                if(!q) return;
                var exists = false;
                $s.find('option').each(function(){ if($(this).text() === q){ exists = true; } });
                var val = '__manual__:'+q;
                if(!exists){
                    $s.append('<option data-manual="1" value="'+val+'">'+q+'</option>');
                } else {
                    // if exists, set its value to its text
                    val = q;
                }
                $s.selectpicker('val', val);
            }
        });
    }
      
    // DataTable örneğini bir değişkende saklayın, böylece ona daha sonra erişebiliriz.
    var offerTable = $('#offerTable').DataTable({
        retrieve: true,
        "processing": false,
        "serverSide": true,
        "ajax": {
            "url": "App/api/get-offers.php?sablon=<?php echo $sablonlari_goster ? 1 : 0; ?>", 
            "type": "POST",
            "data": function(d){
                function sv(id){
                    var v = $(id).val();
                    if(typeof v === 'string' && v.indexOf('__manual__:') === 0){ v = v.replace('__manual__:', ''); }
                    return v;
                }
                d.filters = {
                    offer_no: $('#filter_offer_no').val(),
                    company: sv('#filter_company'),
                    subject: sv('#filter_subject'),
                    status: sv('#filter_status'),
                    date_start: $('#filter_date_start').val(),
                    date_end: $('#filter_date_end').val(),
                    total_min: $('#filter_total_min').val(),
                    total_max: $('#filter_total_max').val(),
                    currency: sv('#filter_currency'),
                    creator: sv('#filter_creator'),
                    payment_period: sv('#filter_payment_period')
                };
            },
            "error": function (xhr, error, thrown) {
                console.log("DataTables AJAX Hatası:", xhr, error, thrown);
                alert("Veriler yüklenirken bir hata oluştu. Sunucu yanıtını kontrol edin.");
            }
        },
        "columns": [
            { "data": "sira_no", "name": "sira_no", "orderable": false, "searchable": false },
            { "data": "islem_tarihi", "name": "created_at" }, 
            { "data": "teklif_no", "name": "offerNumber" },
            { "data": "musteri", "name": "company" },
            { "data": "toplam_tutar", "name": "total_price" },
            { "data": "durum", "name": "durum" },
            { "data": "onay_tarihi", "name": "onay_tarihi" },
            { "data": "konusu", "name": "offer_subject" },
            { "data": "odeme_vadesi", "name": "payment_period" },
            { "data": "teklif_veren", "name": "creator_name" },
            { "data": "islem", "orderable": false, "searchable": false, "className": "text-right" }
        ],
        "order": [[ 1, "desc" ]],
        "language": {
            "url": "include/js/tr.json"
        },
        initComplete: function () {
            if (window.App && window.App.TableFilter) {
                App.TableFilter.attachToTable(this.api().table().node());
            }
        }
    });
  
});



    function showExportLoadingNotification() {
        var swalObj = (typeof swal !== 'undefined') ? swal : ((typeof Swal !== 'undefined') ? Swal : null);
        if (swalObj) {
            swalObj.fire({
                title: "Excel Dosyası Hazırlanıyor",
                html: "Lütfen bekleyiniz, veriler indiriliyor...<br><small style='color:#888;'>İndirme işlemi birazdan otomatik başlayacaktır.</small>",
                icon: "info",
                showConfirmButton: false,
                allowOutsideClick: true,
                timer: 4000,
                timerProgressBar: true,
                didOpen: function() {
                    if (typeof swalObj.showLoading === 'function') {
                        swalObj.showLoading();
                    }
                }
            });
        }
    }

    // Excel'e aktar butonu için
    $('#exportExcel').on('click', function(e){
        e.preventDefault();
        showExportLoadingNotification();
        var form = $('<form>', { action: 'App/api/export-offers.php?sablon=<?php echo $sablonlari_goster ? 1 : 0; ?>', method: 'POST' });
        function sv(id){
            var v = $(id).val();
            if(typeof v === 'string' && v.indexOf('__manual__:') === 0){ v = v.replace('__manual__:', ''); }
            return v;
        }
        var filters = {
            offer_no: $('#filter_offer_no').val(),
            company: sv('#filter_company'),
            subject: sv('#filter_subject'),
            status: sv('#filter_status'),
            date_start: $('#filter_date_start').val(),
            date_end: $('#filter_date_end').val(),
            total_min: $('#filter_total_min').val(),
            total_max: $('#filter_total_max').val(),
            currency: sv('#filter_currency'),
            creator: sv('#filter_creator'),
            payment_period: sv('#filter_payment_period')
        };
        form.append($('<input>', { type: 'hidden', name: 'filters', value: JSON.stringify(filters) }));
        $('body').append(form);
        form.submit();
        setTimeout(function(){ form.remove(); }, 1000);
    });




    $(document).on('click', '.teklif-sil', function() {
    
        var teklif_id = $(this).data('id');
        swal.fire({
            title: "Teklifi Sil",
            text: "Bu teklifi silmek istediğinizden emin misiniz?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Evet, Sil!",
            cancelButtonText: "İptal"
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX ile teklif silme işlemi
                $.ajax({
                    url: 'App/api/offer.php',
                    type: 'POST',
                    data: { id: teklif_id , action: 'deleteOffer' },
                    success: function(response) {
                        response = JSON.parse(response); // JSON verisini ayrıştır
                        
                        if (response.status === 'success') {
                            swal.fire("Silindi!", response.message, "success");
                            // Tabloyu yeniden yükle
                            $('#offerTable').DataTable().ajax.reload();
                        } else {
                            swal.fire("Hata!", response.message, "error");
                        }
                    },
                    error: function() {
                        swal.fire("Hata!", "Teklif silinirken bir hata oluştu.", "error");
                    }
                });
            }
        });
    });
 
    // Filtreleri uygula
    $(document).on('click', '#applyFilters', function() {
        $('#offerTable').DataTable().ajax.reload();
    });

    // Filtreleri temizle
    $(document).on('click', '#clearFilters', function() {
        $('#filter_offer_no, #filter_date_start, #filter_date_end, #filter_total_min, #filter_total_max')
            .val('');
        $('#filter_company, #filter_subject, #filter_status, #filter_currency, #filter_creator, #filter_payment_period')
            .find('option[data-manual="1"]').remove();
        $('#filter_company, #filter_subject, #filter_status, #filter_currency, #filter_creator, #filter_payment_period')
            .selectpicker('val', '')
            .selectpicker('render');
        $('#offerTable').DataTable().ajax.reload();
    });

    // KPI Summary Section Toggle & LocalStorage
    $('html').removeClass('kpi-offers-collapsed-early');
    var isKpiCollapsed = localStorage.getItem('aydinogullari_kpi_offers_collapsed') === 'true';
    if (isKpiCollapsed) {
        $('#kpiSummarySection').addClass('is-collapsed');
        $('#toggleKpiSummary i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    }

    $(document).on('click', '#toggleKpiSummary', function(){
        var $kpi = $('#kpiSummarySection');
        var willCollapse = !$kpi.hasClass('is-collapsed');
        
        if(willCollapse){
            $kpi.addClass('is-collapsed');
            $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            localStorage.setItem('aydinogullari_kpi_offers_collapsed', 'true');
        } else {
            $kpi.removeClass('is-collapsed');
            $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            localStorage.setItem('aydinogullari_kpi_offers_collapsed', 'false');
        }
    });

    // Accordion toggle
    $(document).on('click', '#filtersToggle', function(){
        var $c = $('#filtersCollapse');
        if($c.is(':visible')){
            $c.slideUp(150);
            $('#filtersToggle').html('<i class="fa fa-filter mr-1"></i> Detaylı Filtreleme');
        }else{
            $c.slideDown(150);
            $('#filtersToggle').html('<i class="fa fa-chevron-up mr-1"></i> Gizle');
        }
    });

    // Enter ile arama
    $(document).on('keydown', '#filtersCollapse input, #filtersCollapse select', function(e){
        if(e.key === 'Enter'){
            e.preventDefault();
            $('#applyFilters').trigger('click');
        }
    });
    $(document).on('input keyup change blur', '.date-picker', function(){
        var v = (this.value || '').replace(/[^0-9]/g,'');
        if(v.length > 2) v = v.slice(0,2) + '.' + v.slice(2);
        if(v.length > 5) v = v.slice(0,5) + '.' + v.slice(5);
        this.value = v.slice(0,10);
    });

    // Tabloda Sağ Tık (Context Menu) İşlemleri
    $(document).on('contextmenu', '#offerTable tbody tr', function(e) {
        // Eğer boş satır veya yükleniyor uyarısı ise dur
        if ($(this).find('td').length <= 1) return;

        e.preventDefault();
        
        var $tr = $(this);
        $('#offerTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var offerNo = $tr.find('td:nth-child(3)').text().trim() || 'Teklif İşlemleri';
        var $actionTd = $tr.find('td:last-child');
        
        var menuHtml = '<div class="cm-header"><i class="fa fa-file-text-o mr-1"></i> ' + $('<div>').text(offerNo).html() + '</div>';

        // 1. Düzenle Butonu Varsa
        var $editBtn = $actionTd.find('a[data-tooltip="Düzenle"], a.btn-outline-primary');
        if ($editBtn.length) {
            menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-primary mr-2"></i> Düzenle</a>';
        }

        // 2. Dropdown içindeki elemanlar
        var $dropdownItems = $actionTd.find('.dropdown-menu .dropdown-item');
        if ($dropdownItems.length) {
            $dropdownItems.each(function() {
                var $item = $(this);
                var href = $item.attr('href') || '#';
                var target = $item.attr('target') ? ' target="' + $item.attr('target') + '"' : '';
                var text = $item.html();
                var dataId = $item.attr('data-id') ? ' data-id="' + $item.attr('data-id') + '"' : '';
                var classAttr = $item.attr('class') || '';

                menuHtml += '<a href="' + href + '"' + target + dataId + ' class="' + classAttr + '">' + text + '</a>';
            });
        }

        // 3. Sil Butonu Varsa
        var $deleteBtn = $actionTd.find('.teklif-sil');
        if ($deleteBtn.length) {
            menuHtml += '<div class="cm-divider"></div>';
            var delId = $deleteBtn.data('id');
            menuHtml += '<button type="button" class="teklif-sil cm-danger" data-id="' + delId + '"><i class="fa fa-trash text-danger mr-2"></i> Sil</button>';
        }

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }
        
        $contextMenu.html(menuHtml);

        var mouseX = e.clientX;
        var mouseY = e.clientY;
        
        $contextMenu.css({ display: 'block', visibility: 'hidden' });
        var menuWidth = $contextMenu.outerWidth();
        var menuHeight = $contextMenu.outerHeight();
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();

        if (mouseX + menuWidth > windowWidth) {
            mouseX = windowWidth - menuWidth - 10;
        }
        if (mouseY + menuHeight > windowHeight) {
            mouseY = windowHeight - menuHeight - 10;
        }

        $contextMenu.css({
            top: mouseY + 'px',
            left: mouseX + 'px',
            visibility: 'visible',
            opacity: '1'
        });
    });

    // Menü dışına tıklanınca veya sayfayı kaydırınca context menu kapat
    $(document).on('click scroll', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#offerTable tbody tr').removeClass('context-menu-active');
        }
    });

    // Menüdeki seçeneğe basılınca context menu kapat
    $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
        $('#customContextMenu').hide();
        $('#offerTable tbody tr').removeClass('context-menu-active');
    });

    // ESC basılınca kapat
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#customContextMenu').hide();
            $('#offerTable tbody tr').removeClass('context-menu-active');
        }
    });
</script>
<!-- <script src="include/js/data-table.js"></script> -->
