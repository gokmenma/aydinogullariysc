<div class="modal fade" id="purchaseLogsModal" tabindex="-1" role="dialog" aria-labelledby="purchaseLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 850px;">
        <div class="modal-content custom-log-modal-content">
            <div class="modal-header custom-log-modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center" style="gap: 10px;">
                    <div class="modal-icon-badge"><i class="fa fa-history text-primary"></i></div>
                    <div>
                        <h5 class="modal-title font-16 weight-700 mb-0" id="purchaseLogsModalLabel">İşlem &amp; Log Kayıtları</h5>
                        <small class="text-muted">Kim, ne zaman, hangi işlemi yapmış geçmişi</small>
                    </div>
                </div>
                <button type="button" class="close btn-log-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="offer-log-summary-card px-4 py-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center" style="gap: 12px;">
                <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
                    <span class="offer-badge-no font-14 font-weight-bold text-primary" id="logPurchaseNo">-</span>
                    <span class="text-muted font-13 font-weight-500" id="logPurchaseCompany">-</span>
                </div>
                <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
                    <div class="font-13"><span class="text-muted">Toplam Tutar:</span> <strong id="logPurchaseTotal" class="text-dark font-weight-bold">-</strong></div>
                    <div id="logPurchaseStatus">-</div>
                </div>
            </div>
            <div class="modal-body p-4" style="max-height: calc(80vh - 180px); overflow-y: auto;">
                <div id="purchaseLogsLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status" style="width: 2.2rem; height: 2.2rem;"><span class="sr-only">Yükleniyor...</span></div>
                    <div class="text-muted font-13 font-weight-500">Log kayıtları yükleniyor...</div>
                </div>
                <div id="purchaseLogsError" class="alert alert-danger d-none my-3" role="alert">
                    <i class="fa fa-exclamation-triangle mr-2"></i><span id="purchaseLogsErrorMessage">Kayıtlar yüklenirken bir sorun oluştu.</span>
                </div>
                <div id="purchaseLogsEmpty" class="text-center py-5 d-none">
                    <div class="empty-icon-circle mb-3"><i class="fa fa-folder-open-o text-muted" style="font-size: 38px;"></i></div>
                    <h6 class="weight-600 text-dark mb-1">Kayıt Bulunamadı</h6>
                    <p class="text-muted font-13 mb-0">Bu kayda ait henüz detaylı bir aktivite kaydı bulunmuyor.</p>
                </div>
                <div id="purchaseLogsContainer" class="offer-log-timeline d-none"></div>
            </div>
            <div class="modal-footer custom-log-modal-footer d-flex justify-content-between align-items-center px-4 py-3">
                <div class="text-muted font-12" id="purchaseLogsCountText">Toplam 0 işlem kaydı</div>
                <div class="d-flex" style="gap: 8px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="btnRefreshPurchaseLogs"><i class="fa fa-refresh mr-1"></i> Yenile</button>
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-dismiss="modal" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
#purchaseLogsModal .modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    overflow: hidden;
}
#purchaseLogsModal .modal-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 20px;
}
#purchaseLogsModal .modal-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(2, 132, 199, 0.1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
}
.offer-log-summary-card {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.offer-badge-no {
    display: inline-block;
    padding: 3px 8px;
    background: #e0f2fe;
    color: #0369a1 !important;
    border-radius: 6px;
    letter-spacing: 0.5px;
}
.offer-log-timeline {
    position: relative;
    padding-left: 26px;
}
.offer-log-timeline::before {
    content: '';
    position: absolute;
    top: 12px;
    bottom: 12px;
    left: 11px;
    width: 2px;
    background: #e2e8f0;
}
.offer-log-item {
    position: relative;
    margin-bottom: 18px;
}
.offer-log-item:last-child {
    margin-bottom: 0;
}
.offer-log-icon {
    position: absolute;
    left: -26px;
    top: 4px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    z-index: 2;
    border: 2px solid #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.offer-log-content {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.offer-log-content:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.offer-log-diff-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.dark-mode #purchaseLogsModal .modal-content {
    background: #1e293b;
    color: #f1f5f9;
}
.dark-mode #purchaseLogsModal .modal-header,
.dark-mode .offer-log-summary-card {
    background: #0f172a !important;
    border-color: #334155 !important;
}
.dark-mode .offer-badge-no {
    background: #1e3a5f;
    color: #38bdf8 !important;
}
.dark-mode #purchaseLogsModal .modal-footer {
    background: #0f172a;
    border-color: #334155;
}
.dark-mode .offer-log-timeline::before {
    background: #334155;
}
.dark-mode .offer-log-item .offer-log-icon {
    border-color: #1e293b;
}
.dark-mode .offer-log-content {
    background: #0f172a;
    border-color: #334155;
}
.dark-mode .offer-log-diff-box {
    background: #1e293b;
    border-color: #334155;
}
.dark-mode #logPurchaseTotal { color: #f8fafc !important; }
.dark-mode #logPurchaseCompany { color: #cbd5e1 !important; }
.dark-mode #purchaseLogsModal .close { color: #cbd5e1; text-shadow: none; }
</style>
<script>
(function () {
    var currentId = null, currentNo = '';
    function esc(v) { return $('<div>').text(v === null || v === undefined ? '' : String(v)).html(); }
    var typeLabels = { added: 'Eklendi', removed: 'Silindi', changed: 'Değişti' };

    function diffRow(label, o, n) {
        return '<tr><td style="width: 35%;" class="font-weight-600 text-muted">' + esc(label) + ':</td>' +
            '<td style="width: 30%;" class="text-danger"><del>' + esc(o) + '</del></td>' +
            '<td style="width: 5%;" class="text-muted text-center"><i class="fa fa-arrow-right"></i></td>' +
            '<td style="width: 30%;" class="text-success font-weight-600">' + esc(n) + '</td></tr>';
    }

    function render(logs) {
        var $c = $('#purchaseLogsContainer').empty();
        logs.forEach(function (log) {
            var unvan = log.user_unvan ? '<span class="text-muted font-12">(' + esc(log.user_unvan) + ')</span>' : '';
            var rel = log.relative_time ? '<span class="badge badge-light border text-muted ml-2 font-11"><i class="fa fa-clock-o mr-1"></i>' + esc(log.relative_time) + '</span>' : '';
            var ip = (log.ip_address && log.ip_address !== '-') ? '<span class="text-muted font-11 ml-auto"><i class="fa fa-globe mr-1"></i>IP: ' + esc(log.ip_address) + '</span>' : '';
            var rows = '';
            (log.changed_fields || []).forEach(function (ch) { rows += diffRow(ch.label, ch.old, ch.new); });
            (log.item_changes || []).forEach(function (ic) {
                if (ic.changes) {
                    ic.changes.forEach(function (d) { rows += diffRow('Kalem: ' + ic.item + ' / ' + d.field, d.old, d.new); });
                } else {
                    rows += '<tr><td colspan="4" class="font-weight-600 text-muted">Kalem ' + esc(typeLabels[ic.type] || ic.type) + ': <span class="text-dark">' + esc(ic.item) + '</span></td></tr>';
                }
            });
            var diff = rows ? '<div class="offer-log-diff-box mt-2 p-2 rounded"><div class="font-12 font-weight-bold text-secondary mb-1"><i class="fa fa-exchange mr-1"></i> Değiştirilen Alanlar:</div><table class="table table-sm table-borderless font-12 mb-0">' + rows + '</table></div>' : '';
            $c.append(
                '<div class="offer-log-item"><div class="offer-log-icon ' + esc(log.badge_class) + '"><i class="' + esc(log.event_icon) + '"></i></div>' +
                '<div class="offer-log-content shadow-sm">' +
                '<div class="d-flex flex-wrap align-items-center justify-content-between mb-1" style="gap: 8px;">' +
                '<div class="d-flex align-items-center flex-wrap" style="gap: 6px;"><span class="crm-badge-soft ' + esc(log.badge_class) + '">' + esc(log.event_label) + '</span><strong class="text-dark font-13">' + esc(log.user_name) + '</strong>' + unvan + '</div>' +
                '<div class="d-flex align-items-center"><span class="text-muted font-12">' + esc(log.created_at_formatted) + '</span>' + rel + '</div></div>' +
                '<div class="offer-log-summary font-13 text-secondary mt-1">' + esc(log.summary) + '</div>' + diff +
                '<div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top border-light"><span class="text-muted font-11"><i class="fa fa-shield mr-1"></i>#' + esc(log.id) + '</span>' + ip + '</div>' +
                '</div></div>'
            );
        });
    }

    function load(id, no) {
        $('#logPurchaseNo').text(no || ('#' + id));
        $('#logPurchaseCompany').text('Yükleniyor...');
        $('#logPurchaseTotal').text('-');
        $('#logPurchaseStatus').html('');
        $('#purchaseLogsCountText').text('Kayıtlar getiriliyor...');
        $('#purchaseLogsLoading').removeClass('d-none');
        $('#purchaseLogsError, #purchaseLogsEmpty').addClass('d-none');
        $('#purchaseLogsContainer').addClass('d-none').empty();
        $('#purchaseLogsModal').modal('show');

        $.ajax({ url: 'App/api/purchase.php', type: 'POST', dataType: 'json', data: { action: 'getPurchaseHistory', id: id } })
            .done(function (res) {
                $('#purchaseLogsLoading').addClass('d-none');
                var p = res.purchase || {}, logs = res.logs || [];
                $('#logPurchaseNo').text(p.number || ('#' + id));
                $('#logPurchaseCompany').text(p.company_name || 'Firma Belirtilmemiş');
                $('#logPurchaseTotal').text('₺ ' + (parseFloat(p.total_price) || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                if (p.statu_label) {
                    $('#logPurchaseStatus').html('<span class="badge ' + esc(p.statu_badge_class) + '">' + esc(p.statu_label) + '</span>');
                }
                $('#purchaseLogsCountText').text('Toplam ' + logs.length + ' işlem kaydı bulundu');
                if (!logs.length) { $('#purchaseLogsEmpty').removeClass('d-none'); return; }
                render(logs);
                $('#purchaseLogsContainer').removeClass('d-none');
            })
            .fail(function (xhr) {
                $('#purchaseLogsLoading').addClass('d-none');
                $('#purchaseLogsErrorMessage').text((xhr.responseJSON && xhr.responseJSON.message) || 'Log kayıtları yüklenirken sunucu hatası oluştu.');
                $('#purchaseLogsError').removeClass('d-none');
            });
    }

    window.openPurchaseHistory = function (id, no) { currentId = id; currentNo = no || ''; load(id, currentNo); };
    $('#btnRefreshPurchaseLogs').on('click', function () { if (currentId) { load(currentId, currentNo); } });
    $(document).on('click', '.btn-purchase-history', function () {
        window.openPurchaseHistory($(this).data('id'), $(this).data('title'));
    });
})();
</script>
