(function ($) {
    'use strict';

    if (!$ || !$.fn || !$.fn.dataTable) {
        return;
    }

    var STORE_PREFIX = 'dtresize:v1:';
    var MIN_WIDTH = 50;

    function pageKey() {
        var p = '';
        try {
            p = new URLSearchParams(window.location.search).get('p') || '';
        } catch (e) {}
        return window.location.pathname + '?' + p;
    }

    function readState(key) {
        try {
            var raw = window.localStorage.getItem(key);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function writeState(key, state) {
        try {
            if (state) {
                window.localStorage.setItem(key, JSON.stringify(state));
            } else {
                window.localStorage.removeItem(key);
            }
        } catch (e) {}
    }

    function isEligible(settings) {
        var init = settings.oInit || {};
        var $table = $(settings.nTable);

        if ($table.is('[data-column-resize="off"]')) return false;
        if (init.scrollX || init.scrollY || init.sScrollX || init.sScrollY) return false;
        if (init.responsive || settings._responsive) return false;
        if (settings.aoColumns.length < 2) return false;
        if ($table.find('> thead > tr').length !== 1) return false;
        var merged = false;
        $table.find('> thead > tr > th').each(function () {
            if ((parseInt(this.getAttribute('colspan'), 10) || 1) > 1 || (parseInt(this.getAttribute('rowspan'), 10) || 1) > 1) {
                merged = true;
            }
        });
        if (merged) return false;
        if (!$table.find('> thead > tr > th').length) return false;

        return true;
    }

    function titleOf(th) {
        return $(th).clone().children().remove().end().text().replace(/\s+/g, ' ').trim();
    }

    function createContext(settings) {
        var titles = settings.aoColumns.map(function (c) {
            return titleOf(c.nTh);
        });
        var tableId = settings.sTableId || settings.nTable.id || 'table';
        var ctx = {
            settings: settings,
            key: STORE_PREFIX + pageKey() + '#' + tableId,
            sig: settings.aoColumns.length + ':' + titles.join('|'),
            widths: {},
            autoWidth: settings.oFeatures.bAutoWidth
        };

        var saved = readState(ctx.key);
        if (saved && saved.sig === ctx.sig && saved.widths) {
            ctx.widths = saved.widths;
        }
        return ctx;
    }

    function hasWidths(ctx) {
        return Object.keys(ctx.widths).length > 0;
    }

    function widthOf(ctx, idx) {
        var w = ctx.widths[idx];
        return typeof w === 'number' && w >= MIN_WIDTH ? w : null;
    }

    function paintWidth(th, w) {
        th.style.setProperty('width', w + 'px', 'important');
        th.style.setProperty('min-width', w + 'px', 'important');
        th.style.setProperty('max-width', w + 'px', 'important');
    }

    function clearWidth(th) {
        th.style.removeProperty('width');
        th.style.removeProperty('min-width');
        th.style.removeProperty('max-width');
    }

    function syncTable(ctx) {
        var table = ctx.settings.nTable;

        ctx.settings.oFeatures.bAutoWidth = hasWidths(ctx) ? false : ctx.autoWidth;

        if (!hasWidths(ctx)) {
            $(table).removeClass('dt-resize-fixed');
            table.style.removeProperty('width');
            return;
        }

        var total = 0;
        ctx.settings.aoColumns.forEach(function (col, idx) {
            if (col.bVisible === false) return;
            total += widthOf(ctx, idx) || 140;
        });

        $(table).addClass('dt-resize-fixed');
        table.style.setProperty('width', total + 'px', 'important');
    }

    function paintAll(ctx) {
        ctx.settings.aoColumns.forEach(function (col, idx) {
            var w = widthOf(ctx, idx);
            if (w && col.nTh) paintWidth(col.nTh, w);
        });
        syncTable(ctx);
    }

    function freeze(ctx) {
        ctx.settings.aoColumns.forEach(function (col, idx) {
            if (col.bVisible === false || !col.nTh || widthOf(ctx, idx)) return;
            ctx.widths[idx] = Math.max(MIN_WIDTH, Math.round(col.nTh.getBoundingClientRect().width));
        });
        paintAll(ctx);
    }

    function persist(ctx) {
        if (!hasWidths(ctx)) {
            writeState(ctx.key, null);
            return;
        }
        writeState(ctx.key, { sig: ctx.sig, widths: ctx.widths });
    }

    function swallowNextClick() {
        var handler = function (e) {
            e.stopPropagation();
            e.preventDefault();
            document.removeEventListener('click', handler, true);
        };
        document.addEventListener('click', handler, true);
        setTimeout(function () {
            document.removeEventListener('click', handler, true);
        }, 0);
    }

    function startResize(ctx, th, ev) {
        var idx = ctx.settings.aoColumns.findIndex(function (c) {
            return c.nTh === th;
        });
        if (idx < 0) return;

        var point = ev.type === 'touchstart' ? ev.originalEvent.touches[0] : ev;
        freeze(ctx);

        var startX = point.pageX;
        var startW = widthOf(ctx, idx) || th.getBoundingClientRect().width;
        var moved = false;

        $('body').addClass('dt-resizing');

        function onMove(mv) {
            var p = mv.type === 'touchmove' ? mv.originalEvent.touches[0] : mv;
            if (Math.abs(p.pageX - startX) > 2) moved = true;
            var w = Math.max(MIN_WIDTH, Math.round(startW + (p.pageX - startX)));
            ctx.widths[idx] = w;
            paintWidth(th, w);
            syncTable(ctx);
        }

        function onUp() {
            $(document).off('.dtResizeDrag');
            $('body').removeClass('dt-resizing');
            if (moved) swallowNextClick();
            persist(ctx);
        }

        $(document)
            .on('mousemove.dtResizeDrag touchmove.dtResizeDrag', onMove)
            .on('mouseup.dtResizeDrag touchend.dtResizeDrag', onUp);
    }

    function resetColumn(ctx, th) {
        var idx = ctx.settings.aoColumns.findIndex(function (c) {
            return c.nTh === th;
        });
        if (idx < 0) return;

        delete ctx.widths[idx];
        clearWidth(th);
        if (hasWidths(ctx)) {
            ctx.widths[idx] = 140;
            paintWidth(th, 140);
        }
        syncTable(ctx);
        persist(ctx);
    }

    function addResizers(ctx) {
        ctx.settings.aoColumns.forEach(function (col) {
            var th = col.nTh;
            if (!th || $(th).children('.dt-resizer').length) return;

            $('<span class="dt-resizer" role="separator" aria-orientation="vertical" title="Genişliği ayarlamak için sürükleyin, sıfırlamak için çift tıklayın"></span>')
                .on('mousedown touchstart', function (ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    startResize(ctx, th, ev);
                })
                .on('click', function (ev) {
                    ev.stopPropagation();
                })
                .on('dblclick', function (ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    resetColumn(ctx, th);
                })
                .appendTo(th);
        });
    }

    $(document).on('init.dt', function (e, settings) {
        if (!settings || settings.nTable !== e.target) return;
        if (!isEligible(settings)) return;

        var ctx = createContext(settings);
        addResizers(ctx);
        if (hasWidths(ctx)) paintAll(ctx);

        $(settings.nTable).on('draw.dt column-visibility.dt', function () {
            addResizers(ctx);
        });
    });
})(window.jQuery);
