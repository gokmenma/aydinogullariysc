<?php if (!permtrue('report_alarm')) { return; } ?>
<style>
    #reportAlarmModal .modal-content { border: 0; border-radius: 14px; }
    #reportAlarmModal .ra-sub { font-size: 12px; color: #64748b; margin: 2px 0 0; }
    #reportAlarmModal .ra-card { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 10px; background: #fff; }
    #reportAlarmModal .ra-card.is-off { opacity: .6; }
    #reportAlarmModal .ra-card-info { flex: 1; min-width: 0; }
    #reportAlarmModal .ra-card-name { font-weight: 700; font-size: 14px; }
    #reportAlarmModal .ra-card-meta { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #reportAlarmModal .ra-pill { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; margin-right: 4px; }
    #reportAlarmModal .ra-pill-over { background: #fee2e2; color: #b91c1c; }
    #reportAlarmModal .ra-pill-soon { background: #fef3c7; color: #b45309; }
    #reportAlarmModal .ra-pill-ok { background: #dcfce7; color: #15803d; }
    #reportAlarmModal .ra-empty { text-align: center; padding: 28px 12px; color: #64748b; }
    #reportAlarmModal .ra-empty i { font-size: 34px; color: #cbd5e1; display: block; margin-bottom: 8px; }
    #reportAlarmModal .ra-section { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #475569; margin: 16px 0 8px; }
    #reportAlarmModal .ra-days { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    #reportAlarmModal .ra-day-chip { border: 1px solid #cbd5e1; background: #fff; border-radius: 999px; padding: 3px 12px; font-size: 12px; font-weight: 600; cursor: pointer; color: #334155; }
    #reportAlarmModal .ra-day-chip.active { background: #1e293b; border-color: #1e293b; color: #fff; }
    #reportAlarmModal .ra-help { font-size: 12px; color: #64748b; }
    #reportAlarmModal .ra-preview { margin-top: 14px; padding: 10px 12px; border-radius: 10px; background: #f1f5f9; font-size: 13px; }
    #reportAlarmModal .ra-opt { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; }
    #reportAlarmModal .ra-error { padding: 10px 12px; border-radius: 8px; background: #fef2f2; color: #991b1b; font-size: 13px; margin-bottom: 10px; }
    #reportAlarmModal .select2-container { width: 100% !important; }
    #reportAlarmModal .select2-container--default .select2-selection--multiple { min-height: 38px; border: 1px solid #ced4da; border-radius: 6px; padding: 2px 6px; background: #fff; }
    #reportAlarmModal .select2-container--default.select2-container--focus .select2-selection--multiple,
    #reportAlarmModal .select2-container--default.select2-container--open .select2-selection--multiple { border-color: #64748b; box-shadow: 0 0 0 2px rgba(100,116,139,.18); }
    #reportAlarmModal .select2-selection--multiple .select2-selection__rendered { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; padding: 0; }
    #reportAlarmModal .select2-search--inline { flex: 1; min-width: 90px; margin: 0; }
    #reportAlarmModal .select2-search--inline .select2-search__field { border: 0 !important; box-shadow: none !important; background: transparent !important; outline: 0 !important; height: 28px !important; min-height: 0 !important; margin: 0 !important; padding: 0 4px !important; width: 100% !important; font-size: 13px; }
    #reportAlarmModal .select2-container--default .select2-selection--multiple { position: relative; padding-right: 26px; }
    #reportAlarmModal .select2-selection--multiple .select2-selection__choice { position: relative; display: inline-flex; align-items: center; margin: 0; padding: 3px 10px 3px 24px; max-width: 100%; border-radius: 6px; font-size: 12px; line-height: 1.3; white-space: normal; word-break: break-word; }
    #reportAlarmModal .select2-selection--multiple .select2-selection__choice__remove { position: absolute; left: 4px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; margin: 0; padding: 0; border: 0; background: transparent; color: #fff !important; font-size: 15px; font-weight: 700; line-height: 16px; text-align: center; cursor: pointer; opacity: .85; }
    #reportAlarmModal .select2-selection--multiple .select2-selection__choice__remove:hover { opacity: 1; }
    #reportAlarmModal .select2-selection--multiple .select2-selection__clear { position: absolute; right: 8px; top: 8px; margin: 0; float: none; font-size: 16px; line-height: 20px; color: #64748b; cursor: pointer; }
    #reportAlarmModal .select2-dropdown { border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; box-shadow: 0 8px 24px rgba(15,23,42,.12); }
    #reportAlarmModal .select2-results__options { max-height: 240px; overflow-y: auto; overflow-x: hidden !important; }
    #reportAlarmModal .select2-results__option { white-space: normal; word-break: break-word; padding: 7px 12px; font-size: 13px; }
    .dark-mode #reportAlarmModal .select2-container--default .select2-selection--multiple,
    .dark-mode #reportAlarmModal .select2-dropdown { background: #111827; border-color: #2b3952; color: #e2e8f0; }
    .dark-mode #reportAlarmModal .ra-card { background: #111827; border-color: #2b3952; }
    .dark-mode #reportAlarmModal .ra-preview { background: #1e293b; }
    .dark-mode #reportAlarmModal .ra-day-chip { background: #111827; border-color: #2b3952; color: #cbd5e1; }
    .dark-mode #reportAlarmModal .ra-day-chip.active { background: #e2e8f0; color: #0f172a; }
    .dark-mode #reportAlarmModal .ra-sub, .dark-mode #reportAlarmModal .ra-help, .dark-mode #reportAlarmModal .ra-card-meta { color: #94a3b8; }
</style>
<div class="modal fade" id="reportAlarmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0"><i class="fa fa-bell text-warning mr-2"></i>Rapor Hatırlatma Alarmları</h5>
                    <p class="ra-sub">Geçerlilik tarihi yaklaşan raporlar için ana sayfada ve rapor listesinde uyarı alın.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body" id="raListView">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong id="raListCount">Alarmlarım</strong>
                    <button type="button" class="btn btn-primary btn-sm" id="raNew"><i class="fa fa-plus mr-1"></i>Yeni Alarm</button>
                </div>
                <div id="raList"></div>
            </div>

            <form id="raForm" autocomplete="off" style="display:none;">
                <div class="modal-body">
                    <input type="hidden" id="raId" value="0">
                    <div class="d-flex align-items-center mb-2">
                        <button type="button" class="btn btn-link btn-sm p-0 mr-3" id="raBack"><i class="fa fa-arrow-left mr-1"></i>Alarmlarım</button>
                        <h6 class="font-weight-bold mb-0" id="raFormTitle">Yeni Alarm</h6>
                    </div>
                    <div id="raFormError" class="ra-error" style="display:none;"></div>

                    <div class="ra-section">1. Alarm adı</div>
                    <input type="text" class="form-control" id="raName" maxlength="120" placeholder="Örn: PT Firma yıllık kontroller" required>

                    <div class="ra-section">2. Hangi raporlar?</div>
                    <div class="form-row">
                        <div class="form-group col-md-6 mb-2">
                            <label for="raCustomers" class="font-12 mb-1">Firmalar</label>
                            <select id="raCustomers" multiple style="width:100%"></select>
                            <span class="ra-help">Boş bırakırsanız tüm firmalar.</span>
                        </div>
                        <div class="form-group col-md-6 mb-2">
                            <label for="raTypes" class="font-12 mb-1">Rapor türleri</label>
                            <select id="raTypes" multiple style="width:100%"></select>
                            <span class="ra-help">Boş bırakırsanız tüm türler.</span>
                        </div>
                    </div>

                    <div class="ra-section">3. Ne zaman uyarılsın?</div>
                    <div class="ra-days" id="raDayChips">
                        <button type="button" class="ra-day-chip" data-days="0">Bitiş günü</button>
                        <button type="button" class="ra-day-chip" data-days="7">7 gün önce</button>
                        <button type="button" class="ra-day-chip" data-days="15">15 gün önce</button>
                        <button type="button" class="ra-day-chip" data-days="30">30 gün önce</button>
                        <button type="button" class="ra-day-chip" data-days="60">60 gün önce</button>
                        <button type="button" class="ra-day-chip" data-days="90">90 gün önce</button>
                        <span class="ml-2 ra-help">veya</span>
                        <input type="number" class="form-control form-control-sm" id="raDays" min="0" max="3650" value="30" style="width:80px;">
                        <span class="ra-help">gün</span>
                    </div>

                    <div class="ra-section">4. Seçenekler</div>
                    <label class="ra-opt" for="raOverdue">
                        <input type="checkbox" id="raOverdue" class="mt-1" checked>
                        <span><strong>Süresi geçmiş raporları da göster</strong><br><span class="ra-help">Kapalıysa yalnızca yaklaşan raporlar uyarılır.</span></span>
                    </label>
                    <label class="ra-opt" for="raLatest">
                        <input type="checkbox" id="raLatest" class="mt-1" checked>
                        <span><strong>Yenilenmiş eski raporları sayma</strong><br><span class="ra-help">Aynı firma ve türde daha yeni rapor varsa eski rapor için uyarı verilmez.</span></span>
                    </label>

                    <div class="ra-preview" id="raPreview"><i class="fa fa-info-circle mr-1"></i>Eşleşen rapor sayısı hesaplanıyor…</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" id="raCancel">Vazgeç</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="raSave"><i class="fa fa-check mr-1"></i>Alarmı Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        var API = 'api/report_alarms.php';
        var CSRF = <?php echo json_encode(\App\Helper\Security::csrf()); ?>;
        var optionsReady = false;
        var alarms = [];
        var previewTimer = null;

        function notify(type, msg) {
            if (window.toastr && toastr[type]) { toastr[type](msg); } else { alert(msg); }
        }

        function failMsg(xhr) {
            return (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'İşlem başarısız oldu.';
        }

        function post(action, data) {
            return $.ajax({ url: API, type: 'POST', dataType: 'json', data: $.extend({ action: action, csrf_token: CSRF }, data) });
        }

        function showList() {
            $('#raForm').hide();
            $('#raListView').show();
        }

        function showForm() {
            $('#raListView').hide();
            $('#raForm').show();
            $('#raFormError').hide();
            loadOptions();
            schedulePreview();
            $('#raName').trigger('focus');
        }

        function loadOptions() {
            if (optionsReady) { return; }
            $('#raSave').prop('disabled', true);
            $.getJSON(API, { action: 'options' }).done(function (res) {
                var $c = $('#raCustomers').empty();
                (res.customers || []).forEach(function (c) { $c.append($('<option>').val(c.id).text(c.company)); });
                var $t = $('#raTypes').empty();
                (res.types || []).forEach(function (t) { $t.append($('<option>').val(t.id).text(t.reportName)); });
                if ($.fn.select2) {
                    $('#raCustomers').select2({ dropdownParent: $('#reportAlarmModal'), width: '100%', placeholder: 'Tüm firmalar', allowClear: true, closeOnSelect: false });
                    $('#raTypes').select2({ dropdownParent: $('#reportAlarmModal'), width: '100%', placeholder: 'Tüm türler', allowClear: true, closeOnSelect: false });
                }
                optionsReady = true;
                $('#raSave').prop('disabled', false);
                if (pendingEdit) { applyEdit(pendingEdit); pendingEdit = null; }
            }).fail(function (x) {
                $('#raFormError').text(failMsg(x) + ' Firma ve tür listesi yüklenemedi, formu kapatıp tekrar açın.').show();
            });
        }

        var pendingEdit = null;

        function setDays(n) {
            $('#raDays').val(n);
            syncChips();
        }

        function syncChips() {
            var v = String(parseInt($('#raDays').val(), 10));
            $('#raDayChips .ra-day-chip').each(function () {
                $(this).toggleClass('active', String($(this).data('days')) === v);
            });
        }

        function resetForm() {
            $('#raId').val(0);
            $('#raFormTitle').text('Yeni Alarm');
            $('#raName').val('');
            $('#raOverdue, #raLatest').prop('checked', true);
            $('#raCustomers, #raTypes').val([]).trigger('change');
            setDays(30);
        }

        function applyEdit(a) {
            $('#raCustomers').val(a.customer_ids.map(String)).trigger('change');
            $('#raTypes').val(a.type_ids.map(String)).trigger('change');
            schedulePreview();
        }

        function formData() {
            return {
                days_before: $('#raDays').val() || 0,
                include_overdue: $('#raOverdue').is(':checked') ? 1 : 0,
                latest_only: $('#raLatest').is(':checked') ? 1 : 0,
                customer_ids: $('#raCustomers').val() || [],
                type_ids: $('#raTypes').val() || []
            };
        }

        function schedulePreview() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(runPreview, 350);
        }

        function runPreview() {
            var $p = $('#raPreview');
            $p.html('<i class="fa fa-spinner fa-spin mr-1"></i>Eşleşen rapor sayısı hesaplanıyor…');
            $.getJSON(API, $.extend({ action: 'preview' }, formData())).done(function (res) {
                var c = res.counts || { total: 0, overdue: 0, upcoming: 0 };
                $p.empty().append($('<i class="fa fa-info-circle mr-1"></i>'));
                $p.append(document.createTextNode('Bu ayarlarla şu an '));
                $p.append($('<strong></strong>').text(c.total + ' rapor'));
                $p.append(document.createTextNode(' eşleşiyor (' + c.overdue + ' süresi geçmiş, ' + c.upcoming + ' yaklaşan).'));
            }).fail(function () {
                $p.text('Eşleşen rapor sayısı hesaplanamadı.');
            });
        }

        function renderList() {
            var $l = $('#raList').empty();
            $('#raListCount').text(alarms.length ? 'Alarmlarım (' + alarms.length + ')' : 'Alarmlarım');
            if (!alarms.length) {
                $l.append($('<div class="ra-empty"><i class="fa fa-bell-o"></i><div><strong>Henüz alarm kurmadınız</strong></div><div class="ra-help mt-1">“Yeni Alarm” ile firma ve rapor türü seçip hatırlatma kurabilirsiniz.</div></div>'));
                return;
            }
            alarms.forEach(function (a) {
                var scope = (a.customer_names.length ? a.customer_names.join(', ') : 'Tüm firmalar') + ' · ' + (a.type_names.length ? a.type_names.join(', ') : 'Tüm türler') + ' · ' + (a.days_before === 0 ? 'bitiş günü' : a.days_before + ' gün önce');
                var c = a.counts || { total: 0, overdue: 0, upcoming: 0 };
                var $card = $('<div class="ra-card"></div>').toggleClass('is-off', !a.is_active);
                var $info = $('<div class="ra-card-info"></div>');
                $info.append($('<div class="ra-card-name"></div>').text(a.name));
                $info.append($('<div class="ra-card-meta"></div>').text(scope).attr('title', scope));
                var $pills = $('<div class="mt-1"></div>');
                if (c.overdue > 0) { $pills.append($('<span class="ra-pill ra-pill-over"></span>').text(c.overdue + ' süresi geçmiş')); }
                if (c.upcoming > 0) { $pills.append($('<span class="ra-pill ra-pill-soon"></span>').text(c.upcoming + ' yaklaşan')); }
                if (c.total === 0) { $pills.append($('<span class="ra-pill ra-pill-ok"></span>').text('Uyarı yok')); }
                $info.append($pills);

                var sid = 'raSw' + a.id;
                var $sw = $('<div class="custom-control custom-switch" title="Aktif / Pasif"></div>');
                $sw.append($('<input type="checkbox" class="custom-control-input">').attr('id', sid).prop('checked', !!a.is_active).on('change', function () {
                    var on = this.checked;
                    var $c = $(this).closest('.ra-card').toggleClass('is-off', !on);
                    post('toggle', { id: a.id, active: on ? 1 : 0 }).fail(function (x) { notify('error', failMsg(x)); $c.toggleClass('is-off', on); });
                }));
                $sw.append($('<label class="custom-control-label"></label>').attr('for', sid));

                var $show = c.total > 0 ? $('<a class="btn btn-sm btn-outline-secondary" title="Raporları listele"><i class="fa fa-list"></i></a>').attr('href', 'index.php?p=reports/reports&alarm=' + a.id) : $();
                var $edit = $('<button type="button" class="btn btn-sm btn-outline-primary" title="Düzenle"><i class="fa fa-pencil"></i></button>').on('click', function () { editAlarm(a); });
                var $del = $('<button type="button" class="btn btn-sm btn-outline-danger" title="Sil"><i class="fa fa-trash"></i></button>').on('click', function () {
                    if (!confirm('"' + a.name + '" alarmı silinsin mi?')) { return; }
                    post('delete', { id: a.id }).done(function () { notify('success', 'Alarm silindi.'); refresh(); }).fail(function (x) { notify('error', failMsg(x)); });
                });
                $card.append($info, $sw, $show, $edit, $del);
                $l.append($card);
            });
        }

        function editAlarm(a) {
            resetForm();
            $('#raId').val(a.id);
            $('#raFormTitle').text('Alarmı Düzenle');
            $('#raName').val(a.name);
            $('#raOverdue').prop('checked', !!a.include_overdue);
            $('#raLatest').prop('checked', !!a.latest_only);
            setDays(a.days_before);
            if (optionsReady) { applyEdit(a); } else { pendingEdit = a; }
            showForm();
        }

        function refresh() {
            $('#raList').html('<div class="ra-empty"><i class="fa fa-spinner fa-spin"></i>Yükleniyor…</div>');
            return $.getJSON(API, { action: 'list' }).done(function (res) {
                alarms = res.alarms || [];
                renderList();
            }).fail(function (x) {
                $('#raList').empty().append($('<div class="ra-error"></div>').text(failMsg(x)));
            });
        }

        $(document).on('click', '#btnReportAlarms', function () {
            showList();
            $('#reportAlarmModal').modal('show');
            refresh();
        });

        $('#raNew').on('click', function () { resetForm(); showForm(); });
        $('#raBack, #raCancel').on('click', showList);

        $('#raDayChips').on('click', '.ra-day-chip', function () { setDays($(this).data('days')); schedulePreview(); });
        $('#raDays').on('input', function () { syncChips(); schedulePreview(); });
        $('#raOverdue, #raLatest').on('change', schedulePreview);
        $('#raCustomers, #raTypes').on('change', schedulePreview);

        $('#raForm').on('submit', function (e) {
            e.preventDefault();
            var name = $.trim($('#raName').val());
            if (!name) { $('#raFormError').text('Alarm adı zorunludur.').show(); return; }
            var $btn = $('#raSave').prop('disabled', true);
            post('save', $.extend({ id: $('#raId').val(), name: name }, formData())).done(function () {
                notify('success', 'Alarm kaydedildi.');
                showList();
                refresh();
            }).fail(function (x) {
                $('#raFormError').text(failMsg(x)).show();
            }).always(function () { $btn.prop('disabled', !optionsReady); });
        });
    })();
</script>
