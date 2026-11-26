(function ($) {
    'use strict';

    if (!$ || !$.fn || !$.fn.dataTable || !$.fn.dataTable.ColReorder) {
        return;
    }

    var STORE_PREFIX = 'dtcols:v1:';
    var DRAG_THRESHOLD = 6;
    var menuEl = null;

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

    function titleOf(th) {
        return $(th).clone().children().remove().end().text().replace(/\s+/g, ' ').trim();
    }

    function isServerSide(settings) {
        var init = settings.oInit || {};
        return !!(settings.oFeatures.bServerSide || init.serverSide || init.bServerSide);
    }

    function isEligible(settings) {
        var $table = $(settings.nTable);

        if ($table.is('[data-column-manage="off"]')) return false;
        if (settings.aoColumns.length < 2) return false;
        if ($table.find('> thead > tr').length !== 1) return false;

        var ths = $table.find('> thead > tr > th');
        if (!ths.length || ths.length !== settings.aoColumns.length) return false;

        var merged = false;
        ths.each(function () {
            if ((parseInt(this.getAttribute('colspan'), 10) || 1) > 1 || (parseInt(this.getAttribute('rowspan'), 10) || 1) > 1) {
                merged = true;
            }
        });
        return !merged;
    }

    function isFixedHeader(th) {
        var $th = $(th);
        return $th.hasClass('no-reorder') || $th.find('input[type="checkbox"]').length > 0 || titleOf(th) === '';
    }

    function origOf(col) {
        return typeof col._ColReorder_iOrigCol === 'number' ? col._ColReorder_iOrigCol : col.idx;
    }

    function bounds(settings) {
        var cols = settings.aoColumns;
        var n = cols.length;
        var left = 0;
        var right = n - 1;
        while (left < n && isFixedHeader(cols[left].nTh)) left++;
        while (right >= left && isFixedHeader(cols[right].nTh)) right--;
        return { left: left, right: right };
    }

    function createContext(settings, titles) {
        var tableId = settings.sTableId || settings.nTable.id || 'table';
        return {
            settings: settings,
            api: new $.fn.dataTable.Api(settings),
            reorderable: true,
            titles: titles,
            key: STORE_PREFIX + pageKey() + '#' + tableId,
            sig: titles.length + ':' + titles.join('|')
        };
    }

    function currentOrder(ctx) {
        return ctx.settings.aoColumns.map(origOf);
    }

    function hiddenList(ctx) {
        var out = [];
        ctx.settings.aoColumns.forEach(function (col) {
            if (col.bVisible === false) out.push(origOf(col));
        });
        return out;
    }

    function persist(ctx) {
        var order = currentOrder(ctx);
        var hidden = hiddenList(ctx);
        var identity = order.every(function (v, i) {
            return v === i;
        });
        if (identity && !hidden.length) {
            writeState(ctx.key, null);
            return;
        }
        writeState(ctx.key, { sig: ctx.sig, order: order, hidden: hidden });
    }

    function isPermutation(order, n) {
        if (!Array.isArray(order) || order.length !== n) return false;
        var seen = {};
        for (var i = 0; i < n; i++) {
            var v = order[i];
            if (typeof v !== 'number' || v < 0 || v >= n || seen[v]) return false;
            seen[v] = true;
        }
        return true;
    }

    function visibleManaged(ctx) {
        var b = bounds(ctx.settings);
        var count = 0;
        ctx.settings.aoColumns.forEach(function (col, i) {
            if (i >= b.left && i <= b.right && col.bVisible !== false) count++;
        });
        return count;
    }

    function setVisible(ctx, pos, visible) {
        if (!visible && visibleManaged(ctx) <= 1) return false;
        ctx.api.column(pos).visible(visible, false);
        $(ctx.settings.nTable).trigger('dtc-change', [ctx]);
        persist(ctx);
        return true;
    }

    function restore(ctx) {
        var saved = readState(ctx.key);
        if (!saved || saved.sig !== ctx.sig) return;
        var n = ctx.settings.aoColumns.length;

        if (ctx.reorderable && isPermutation(saved.order, n)) {
            var b = bounds(ctx.settings);
            var safe = saved.order.every(function (orig, pos) {
                var fixedPos = pos < b.left || pos > b.right;
                return fixedPos ? orig === pos : orig >= b.left && orig <= b.right;
            });
            if (safe) {
                var moved = false;
                for (var p = 0; p < n; p++) {
                    var curPos = currentOrder(ctx).indexOf(saved.order[p]);
                    if (curPos !== p) {
                        ctx.api.colReorder.move(curPos, p, false, false);
                        moved = true;
                    }
                }
                if (moved) {
                    ctx.api.rows().invalidate('data').draw(false);
                }
            }
        }

        if (Array.isArray(saved.hidden)) {
            saved.hidden.forEach(function (orig) {
                var pos = currentOrder(ctx).indexOf(orig);
                var b2 = bounds(ctx.settings);
                if (pos >= b2.left && pos <= b2.right && visibleManaged(ctx) > 1) {
                    ctx.api.column(pos).visible(false, false);
                }
            });
        }
        $(ctx.settings.nTable).trigger('dtc-change', [ctx]);
    }

    function resetAll(ctx) {
        if (ctx.reorderable) {
            ctx.api.colReorder.reset();
        }
        ctx.settings.aoColumns.forEach(function (col, pos) {
            if (col.bVisible === false) ctx.api.column(pos).visible(true, false);
        });
        writeState(ctx.key, null);
        $(ctx.settings.nTable).trigger('dtc-change', [ctx]);
    }

    function labelOf(ctx, col, pos) {
        var t = ctx.titles[origOf(col)];
        return t || 'Kolon ' + (pos + 1);
    }

    function closeMenu() {
        if (menuEl) {
            $(menuEl).remove();
            menuEl = null;
        }
        $(document).off('.dtcMenu');
    }

    function openMenu(ctx, x, y) {
        closeMenu();
        var b = bounds(ctx.settings);
        var $menu = $('<div class="dtc-menu" role="menu"></div>');
        $menu.append('<div class="dtc-menu-title"><i class="fa fa-columns"></i> Kolonlar</div>');

        var lastVisible = visibleManaged(ctx) <= 1;
        ctx.settings.aoColumns.forEach(function (col, pos) {
            if (pos < b.left || pos > b.right) return;
            var visible = col.bVisible !== false;
            var $row = $('<label class="dtc-menu-item"></label>');
            var $cb = $('<input type="checkbox">').prop('checked', visible).prop('disabled', visible && lastVisible);
            $cb.on('change', function () {
                if (!setVisible(ctx, pos, this.checked)) {
                    this.checked = true;
                }
                openMenuRefresh(ctx);
            });
            $row.append($cb).append($('<span></span>').text(labelOf(ctx, col, pos)));
            $menu.append($row);
        });

        $menu.append('<div class="dtc-menu-sep"></div>');
        $('<button type="button" class="dtc-menu-action"><i class="fa fa-eye"></i> Tümünü Göster</button>')
            .on('click', function () {
                ctx.settings.aoColumns.forEach(function (col, pos) {
                    if (col.bVisible === false) ctx.api.column(pos).visible(true, false);
                });
                $(ctx.settings.nTable).trigger('dtc-change', [ctx]);
                persist(ctx);
                openMenuRefresh(ctx);
            })
            .appendTo($menu);
        $('<button type="button" class="dtc-menu-action"><i class="fa fa-undo"></i> Varsayılana Döndür</button>')
            .on('click', function () {
                resetAll(ctx);
                closeMenu();
            })
            .appendTo($menu);

        $('body').append($menu);
        menuEl = $menu[0];
        menuEl._pos = { x: x, y: y };
        placeMenu($menu, x, y);

        setTimeout(function () {
            $(document)
                .on('mousedown.dtcMenu touchstart.dtcMenu', function (e) {
                    if (menuEl && !$(e.target).closest('.dtc-menu').length) closeMenu();
                })
                .on('keydown.dtcMenu', function (e) {
                    if (e.key === 'Escape') closeMenu();
                })
                .on('scroll.dtcMenu', closeMenu);
            $(window).one('resize.dtcMenu', closeMenu);
        }, 0);
    }

    function openMenuRefresh(ctx) {
        if (!menuEl) return;
        var p = menuEl._pos;
        openMenu(ctx, p.x, p.y);
    }

    function placeMenu($menu, x, y) {
        var w = $menu.outerWidth();
        var h = $menu.outerHeight();
        var left = Math.min(x, window.innerWidth - w - 8);
        var top = Math.min(y, window.innerHeight - h - 8);
        $menu.css({ left: Math.max(8, left) + 'px', top: Math.max(8, top) + 'px' });
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

    function pointOf(ev) {
        var t = ev.originalEvent && ev.originalEvent.touches && ev.originalEvent.touches[0];
        if (!t && ev.originalEvent && ev.originalEvent.changedTouches) t = ev.originalEvent.changedTouches[0];
        return t || ev;
    }

    function inRect(x, y, r) {
        return x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
    }

    function startDrag(ctx, th, downEv) {
        var settings = ctx.settings;
        var fromPos = settings.aoColumns.findIndex(function (c) {
            return c.nTh === th;
        });
        var b = bounds(settings);
        if (fromPos < b.left || fromPos > b.right) return;

        var start = pointOf(downEv);
        var startX = start.clientX;
        var startY = start.clientY;
        var dragging = false;
        var $ghost = null;
        var $line = null;
        var target = null;
        var overBody = false;
        var $table = $(settings.nTable);

        function collect() {
            var items = [];
            settings.aoColumns.forEach(function (col, pos) {
                if (pos < b.left || pos > b.right || col.bVisible === false || !col.nTh) return;
                items.push({ pos: pos, rect: col.nTh.getBoundingClientRect() });
            });
            return items;
        }

        function begin() {
            dragging = true;
            $('body').addClass('dtc-dragging');
            $ghost = $('<div class="dtc-ghost"></div>').text(labelOf(ctx, settings.aoColumns[fromPos], fromPos)).appendTo('body');
            $line = $('<div class="dtc-drop-line"></div>').appendTo('body');
            $(th).addClass('dtc-drag-source');
        }

        function update(p) {
            $ghost.css({ left: p.clientX + 12 + 'px', top: p.clientY + 12 + 'px' });

            var tbody = settings.nTBody ? settings.nTBody.getBoundingClientRect() : null;
            var theadRect = settings.nTHead.getBoundingClientRect();
            var tableRect = settings.nTable.getBoundingClientRect();
            overBody = !!tbody && p.clientY > theadRect.bottom && inRect(p.clientX, p.clientY, {
                left: tableRect.left,
                right: tableRect.right,
                top: theadRect.bottom,
                bottom: Math.max(tbody.bottom, theadRect.bottom + 40)
            });
            $table.toggleClass('dtc-hide-zone', overBody);
            $ghost.toggleClass('dtc-ghost-hide', overBody);

            target = null;
            if (overBody || !ctx.reorderable) {
                $line.hide();
                return;
            }

            var items = collect();
            if (!items.length) return;
            var chosen = null;
            for (var i = 0; i < items.length; i++) {
                var r = items[i].rect;
                if (p.clientX < r.left + r.width / 2) {
                    chosen = { pos: items[i].pos, after: false, x: r.left };
                    break;
                }
                chosen = { pos: items[i].pos, after: true, x: r.right };
            }
            target = chosen;
            var head = theadRect;
            $line.css({ left: chosen.x - 1 + 'px', top: head.top + 'px', height: head.height + 'px' }).show();
        }

        function onMove(mv) {
            var p = pointOf(mv);
            if (!dragging) {
                if (Math.abs(p.clientX - startX) < DRAG_THRESHOLD && Math.abs(p.clientY - startY) < DRAG_THRESHOLD) return;
                begin();
            }
            mv.preventDefault();
            update(p);
        }

        function cleanup() {
            $(document).off('.dtcDrag');
            $('body').removeClass('dtc-dragging');
            $table.removeClass('dtc-hide-zone');
            $(th).removeClass('dtc-drag-source');
            if ($ghost) $ghost.remove();
            if ($line) $line.remove();
        }

        function onUp() {
            var hideIt = dragging && overBody;
            var drop = dragging ? target : null;
            var wasDragging = dragging;
            cleanup();
            if (!wasDragging) return;
            swallowNextClick();

            if (hideIt) {
                setVisible(ctx, fromPos, false);
                return;
            }
            if (!drop) return;

            var to = drop.after ? (fromPos < drop.pos ? drop.pos : drop.pos + 1) : (fromPos < drop.pos ? drop.pos - 1 : drop.pos);
            if (to === fromPos) return;
            to = Math.max(b.left, Math.min(b.right, to));
            ctx.api.colReorder.move(fromPos, to, true, true);
            $table.trigger('dtc-change', [ctx]);
            persist(ctx);
        }

        $(document)
            .on('mousemove.dtcDrag touchmove.dtcDrag', onMove)
            .on('mouseup.dtcDrag touchend.dtcDrag touchcancel.dtcDrag', onUp)
            .on('keydown.dtcDrag', function (e) {
                if (e.key === 'Escape') {
                    dragging = false;
                    cleanup();
                }
            });
    }

    function bindHeaders(ctx) {
        var $thead = $(ctx.settings.nTHead);

        $thead.on('mousedown.dtcCols touchstart.dtcCols', 'th', function (ev) {
            if (ev.type === 'mousedown' && ev.which !== 1) return;
            if ($(ev.target).closest('.dt-resizer, .tf-trigger, input, button, a, select, textarea').length) return;
            var th = this;
            if (!ctx.settings.aoColumns.some(function (c) {
                return c.nTh === th;
            })) return;
            startDrag(ctx, th, ev);
        });

        $thead.on('contextmenu.dtcCols', 'th', function (ev) {
            ev.preventDefault();
            var p = pointOf(ev);
            openMenu(ctx, p.clientX, p.clientY);
        });
    }

    $(document).on('preInit.dt', function (e, settings) {
        if (!settings || settings.nTable !== e.target) return;
        if (!isEligible(settings)) return;

        var titles = settings.aoColumns.map(function (c) {
            return titleOf(c.nTh);
        });
        settings.aoColumns.forEach(function (c, i) {
            if (c.nTh) c.nTh.setAttribute('data-orig-col', i);
        });
        settings._dtcTitles = titles;

        if (!settings._colReorder) {
            new $.fn.dataTable.ColReorder(settings, {});
        }
        settings._dtcEligible = true;
    });

    function toOriginalRequest(settings, data) {
        if (!data || !Array.isArray(data.columns)) return;
        var cols = settings.aoColumns;
        if (data.columns.length !== cols.length) return;

        var restored = [];
        cols.forEach(function (col, pos) {
            restored[origOf(col)] = data.columns[pos];
        });
        data.columns = restored;

        if (Array.isArray(data.order)) {
            data.order.forEach(function (o) {
                var col = cols[parseInt(o.column, 10)];
                if (col) o.column = origOf(col);
            });
        }
    }

    $(document).on('preXhr.dt', function (e, settings, data) {
        if (!settings || !settings._dtcEligible || !isServerSide(settings)) return;
        toOriginalRequest(settings, data);
    });

    $(document).on('init.dt', function (e, settings) {
        if (!settings || settings.nTable !== e.target || !settings._dtcEligible) return;

        $(settings.aoColumns.map(function (c) {
            return c.nTh;
        })).off('.ColReorder');

        var ctx = createContext(settings, settings._dtcTitles);
        settings._dtcContext = ctx;
        bindHeaders(ctx);
        restore(ctx);
        $(settings.nTable).addClass('dtc-managed');
    });
})(window.jQuery);
