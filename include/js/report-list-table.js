(function () {
    'use strict';

    var ENDPOINT = 'api/report_lists_datatables.php';

    var COLUMNS = {
        filling: [
            { data: 0, className: 'text-center font-weight-600 text-muted', orderable: false, searchable: false },
            { data: 1 },
            { data: 2, className: 'text-center' },
            { data: 3, className: 'text-center' },
            { data: 4 },
            { data: 5, className: 'text-center' },
            { data: 6, className: 'text-center' },
            { data: 7, className: 'text-center', orderable: false, searchable: false }
        ],
        control: [
            { data: 0, className: 'text-center font-weight-600 text-muted', orderable: false, searchable: false },
            { data: 1 },
            { data: 2, className: 'text-center' },
            { data: 3 },
            { data: 4, className: 'text-center' },
            { data: 5, className: 'text-center' },
            { data: 6, className: 'text-center', orderable: false, searchable: false }
        ]
    };

    function relocateSearch(api, tableId) {
        var $ = window.jQuery;
        var $container = $('.form-card-header .dt-header-filter-box');
        var $searchBox = $('#' + tableId + '_filter');
        if (!$container.length || !$searchBox.length) {
            return;
        }
        $searchBox.detach().appendTo($container);
        $searchBox.find('label').css({
            'margin-bottom': '0',
            'display': 'flex',
            'align-items': 'center',
            'position': 'relative'
        });
        $searchBox.find('input').addClass('form-control form-control-sm').attr('placeholder', 'Arayın...').css({
            'border-radius': '8px',
            'height': '36px',
            'width': '220px',
            'padding': '6px 12px'
        });
    }

    function init(options) {
        var $ = window.jQuery;
        var tableId = options.tableId;
        var list = options.list;
        if (!$('#' + tableId).length || !COLUMNS[list]) {
            return null;
        }

        return $('#' + tableId).DataTable({
            serverSide: true,
            processing: true,
            deferRender: true,
            autoWidth: false,
            responsive: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Tümü']],
            language: { url: 'include/js/tr.json' },
            stateSave: true,
            order: [],
            columns: COLUMNS[list],
            ajax: {
                url: ENDPOINT,
                type: 'GET',
                data: function (d) {
                    d.list = list;
                    var period = typeof options.period === 'function' ? options.period() : (options.period || {});
                    $.extend(d, period);
                },
                error: function (xhr) {
                    if (window.console) {
                        console.error('Liste yüklenemedi', xhr.status);
                    }
                    if (window.toastr) {
                        toastr.error('Liste yüklenirken bir hata oluştu.');
                    }
                }
            },
            initComplete: function () {
                var api = this.api();
                relocateSearch(api, tableId);
                if (window.App && window.App.TableFilter) {
                    App.TableFilter.attachToTable(api.table().node());
                }
            }
        });
    }

    function initKpiToggle(slug) {
        var $ = window.jQuery;
        var storageKey = 'aydinogullari_kpi_' + slug + '_collapsed';
        var $section = $('#kpiSummarySection');
        var $button = $('#toggleKpiSummary');

        function apply(collapsed) {
            $section.toggleClass('is-collapsed', collapsed);
            $button.find('i')
                .toggleClass('fa-chevron-down', collapsed)
                .toggleClass('fa-chevron-up', !collapsed);
            $button.attr('title', collapsed ? 'Özet Kartlarını Göster' : 'Özet Kartlarını Gizle');
        }

        var collapsed = false;
        try {
            collapsed = localStorage.getItem(storageKey) === 'true';
        } catch (e) {}

        $('html').removeClass('kpi-' + slug + '-collapsed-early');
        apply(collapsed);

        $(document).on('click', '#toggleKpiSummary', function () {
            var willCollapse = !$section.hasClass('is-collapsed');
            apply(willCollapse);
            try {
                localStorage.setItem(storageKey, willCollapse ? 'true' : 'false');
            } catch (e) {}
        });
    }

    window.ReportListTable = { init: init, initKpiToggle: initKpiToggle };
})();
