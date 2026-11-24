<div class="modal fade" id="purchaseHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-history"></i> İşlem Tarihçesi <small class="text-muted" id="purchaseHistoryTitle"></small></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="purchaseHistoryBody"></div>
        </div>
    </div>
</div>
<style>
    .ph-entry { border-left: 3px solid #cbd5e1; padding: 6px 0 6px 12px; margin-bottom: 12px; }
    .ph-entry.ph-create { border-color: #10b981; }
    .ph-entry.ph-update { border-color: #3b82f6; }
    .ph-entry.ph-delete { border-color: #f43f5e; }
    .ph-entry.ph-status_change { border-color: #8b5cf6; }
    .ph-meta { font-size: 12px; color: #64748b; }
    .ph-changes { font-size: 12px; margin: 4px 0 0; padding-left: 16px; }
    .ph-old { text-decoration: line-through; color: #94a3b8; }
</style>
<script>
(function () {
    function esc(v) {
        return $('<div>').text(v === null || v === undefined ? '' : String(v)).html();
    }
    var typeLabels = { added: 'Eklendi', removed: 'Silindi', changed: 'Değişti' };

    function render(items) {
        if (!items.length) {
            return '<p class="text-muted mb-0">Bu kayıt için işlem geçmişi bulunamadı.</p>';
        }
        return items.map(function (it) {
            var html = '<div class="ph-entry ph-' + esc(it.event_type) + '">' +
                '<div><strong>' + esc(it.event_label) + '</strong> — ' + esc(it.summary) + '</div>' +
                '<div class="ph-meta"><i class="fa fa-user"></i> ' + esc(it.username) +
                ' &middot; <i class="fa fa-clock-o"></i> ' + esc(it.created_at) +
                ' &middot; ' + esc(it.ip_address) + '</div>';
            if (it.changes && it.changes.length) {
                html += '<ul class="ph-changes">' + it.changes.map(function (c) {
                    return '<li>' + esc(c.field) + ': <span class="ph-old">' + esc(c.old) + '</span> → <strong>' + esc(c.new) + '</strong></li>';
                }).join('') + '</ul>';
            }
            if (it.item_changes && it.item_changes.length) {
                html += '<ul class="ph-changes">' + it.item_changes.map(function (c) {
                    var line = '<li>Kalem ' + esc(typeLabels[c.type] || c.type) + ': <strong>' + esc(c.item) + '</strong>';
                    if (c.changes) {
                        line += ' (' + c.changes.map(function (d) {
                            return esc(d.field) + ': <span class="ph-old">' + esc(d.old) + '</span> → ' + esc(d.new);
                        }).join(', ') + ')';
                    }
                    return line + '</li>';
                }).join('') + '</ul>';
            }
            return html + '</div>';
        }).join('');
    }

    window.openPurchaseHistory = function (id, title) {
        $('#purchaseHistoryTitle').text(title ? '(' + title + ')' : '');
        $('#purchaseHistoryBody').html('<p class="text-muted mb-0">Yükleniyor...</p>');
        $('#purchaseHistoryModal').modal('show');
        $.ajax({
            url: 'App/api/purchase.php',
            method: 'POST',
            dataType: 'json',
            data: { action: 'getPurchaseHistory', id: id }
        }).done(function (res) {
            $('#purchaseHistoryBody').html(render(res.items || []));
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Tarihçe alınamadı.';
            $('#purchaseHistoryBody').html('<p class="text-danger mb-0">' + esc(msg) + '</p>');
        });
    };

    $(document).on('click', '.btn-purchase-history', function () {
        window.openPurchaseHistory($(this).data('id'), $(this).data('title'));
    });
})();
</script>
