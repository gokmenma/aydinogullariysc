/**
 * Centralized DataTable Column Filter Module
 * Features: String (text), Number, Date and Select filters with multi-rule popover modals.
 */

window.App = window.App || {};

App.TableFilter = {
    activeFilters: {}, // tableId -> { colIndex -> { type: 'text'|'number'|'date'|'select', rules: [...] } }
    hooksBound: false,

    SVG_FILTER_ICON: '<svg class="tf-funnel-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; pointer-events:none;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>',
    SVG_PLUS_ICON: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    SVG_TRASH_ICON: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    SVG_CALENDAR_ICON: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',

    init: function (container) {
        App.TableFilter.bindDataTableHooks();

        container = container || document;
        const tables = container.querySelectorAll('table.data-table, table.dataTable, .responsive table, .card table, .form-card table, table');
        tables.forEach(table => {
            if (table.querySelector('thead th')) {
                if (!table.id) {
                    table.id = 'dt-tbl-' + Math.random().toString(36).substr(2, 8);
                }
                App.TableFilter.attachToTable(table);
                App.TableFilter.relocateSearchInput(table);
            }
        });

        // Global outside click listener to close popovers
        if (!document.body.dataset.tfGlobalBound) {
            document.addEventListener('click', function (e) {
                const popover = e.target.closest('.tf-popover');
                const trigger = e.target.closest('.tf-trigger');
                const flatpickrCalendar = e.target.closest('.flatpickr-calendar');
                const select2Container = e.target.closest('.select2-container');
                const select2Dropdown = e.target.closest('.select2-dropdown');
                if (!popover && !trigger && !flatpickrCalendar && !select2Container && !select2Dropdown) {
                    document.querySelectorAll('.tf-popover.show').forEach(function (p) {
                        p.classList.remove('show');
                    });
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.tf-popover.show').forEach(function (p) {
                        p.classList.remove('show');
                    });
                }
            });

            document.body.dataset.tfGlobalBound = 'true';
        }
    },

    bindDataTableHooks: function () {
        if (!window.jQuery || !$.fn || !$.fn.dataTable || App.TableFilter.hooksBound) return;
        App.TableFilter.hooksBound = true;

        if ($.fn.dataTable.ext && $.fn.dataTable.ext.search) {
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex, rowData, counter) {
                const tableId = settings.sTableId || (settings.nTable ? settings.nTable.id : null);
                if (!tableId || !App.TableFilter.activeFilters[tableId]) return true;

                const tableFilters = App.TableFilter.activeFilters[tableId];
                const colIndexes = Object.keys(tableFilters);
                if (colIndexes.length === 0) return true;

                for (let i = 0; i < colIndexes.length; i++) {
                    const colIdx = parseInt(colIndexes[i], 10);
                    const filterDef = tableFilters[colIdx];
                    const cellValue = data[colIdx] || '';

                    if (filterDef.type === 'select') {
                        const selectedVals = filterDef.values || [];
                        if (selectedVals.length > 0) {
                            const cText = (cellValue || '').trim();
                            const match = selectedVals.some(v => v.trim() === cText);
                            if (!match) return false;
                        }
                    } else if (filterDef.rules && filterDef.rules.length > 0) {
                        for (let r = 0; r < filterDef.rules.length; r++) {
                            const rule = filterDef.rules[r];
                            const passed = App.TableFilter.evaluateRule(cellValue, rule, filterDef.type);
                            if (!passed) return false;
                        }
                    }
                }

                return true;
            });
        }

        // Auto-hook into DataTable defaults
        $.extend(true, $.fn.dataTable.defaults, {
            initComplete: function () {
                const api = this.api();
                const tableNode = api.table().node();
                if (tableNode) {
                    App.TableFilter.attachToTable(tableNode);
                    App.TableFilter.relocateSearchInput(tableNode);
                }
            },
            drawCallback: function () {
                const api = this.api();
                const tableNode = api.table().node();
                if (tableNode) {
                    App.TableFilter.attachToTable(tableNode);
                    App.TableFilter.relocateSearchInput(tableNode);
                }
            }
        });
    },

    relocateSearchInput: function (table) {
        if (!table) return;
        const wrapper = table.closest('.dataTables_wrapper');
        if (!wrapper) return;
        const filterEl = wrapper.querySelector('.dataTables_filter');
        if (!filterEl) return;

        // Strip "Ara:", "Search:" or any text node from label
        const label = filterEl.querySelector('label');
        if (label) {
            Array.from(label.childNodes).forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.textContent = '';
                }
            });
        }
        const input = filterEl.querySelector('input');
        if (input && !input.getAttribute('placeholder')) {
            input.setAttribute('placeholder', 'Arayın...');
        }

        if (filterEl.dataset.relocated === 'true') return;

        const card = table.closest('.form-card, .card, .content, .pd-20');
        if (card) {
            const filtersToggle = card.querySelector('#filtersToggle, .filters-toggle-btn');
            const cardHeader = card.querySelector('.form-card-header, .card-header');

            if (filtersToggle && filtersToggle.parentNode) {
                filtersToggle.parentNode.classList.add('d-flex', 'align-items-center', 'gap-2');
                filtersToggle.parentNode.insertBefore(filterEl, filtersToggle);
                filterEl.classList.add('dt-header-filter', 'mr-2');
                filterEl.dataset.relocated = 'true';
            } else if (cardHeader && !cardHeader.querySelector('.dataTables_filter')) {
                let rightBox = cardHeader.querySelector('.header-right-inner');
                if (!rightBox) {
                    rightBox = cardHeader.querySelector('.d-flex.align-items-center:last-child:not(.header-left-inner)');
                }
                if (!rightBox) {
                    rightBox = document.createElement('div');
                    rightBox.className = 'd-flex align-items-center gap-2';
                    cardHeader.appendChild(rightBox);
                }
                rightBox.appendChild(filterEl);
                filterEl.classList.add('dt-header-filter');
                filterEl.dataset.relocated = 'true';
            }
        }
    },

    toTrLower: function (str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/İ/g, 'i')
            .replace(/I/g, 'ı')
            .replace(/Ğ/g, 'ğ')
            .replace(/Ü/g, 'ü')
            .replace(/Ş/g, 'ş')
            .replace(/Ö/g, 'ö')
            .replace(/Ç/g, 'ç')
            .toLowerCase()
            .trim();
    },

    parseNum: function (val) {
        if (val === null || val === undefined) return NaN;
        let str = String(val).replace(/<[^>]*>/g, '').trim();
        if (str === '') return NaN;
        str = str.replace(/[₺$€\s]/g, '');
        if (str.indexOf('.') !== -1 && str.indexOf(',') !== -1) {
            str = str.replace(/\./g, '').replace(',', '.');
        } else if (str.indexOf(',') !== -1) {
            str = str.replace(',', '.');
        }
        str = str.replace(/[^0-9.-]/g, '');
        return parseFloat(str);
    },

    parseDate: function (val) {
        if (!val) return NaN;
        let str = String(val).replace(/<[^>]*>/g, '').trim();
        if (!str) return NaN;

        let datePart = str.split(' ')[0];
        let parts = [];
        if (datePart.includes('.')) {
            parts = datePart.split('.');
        } else if (datePart.includes('-')) {
            parts = datePart.split('-');
        } else if (datePart.includes('/')) {
            parts = datePart.split('/');
        }

        if (parts.length === 3) {
            if (parts[0].length === 4) {
                return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10)).getTime();
            }
            return new Date(parseInt(parts[2], 10), parseInt(parts[1], 10) - 1, parseInt(parts[0], 10)).getTime();
        }

        let parsed = Date.parse(str);
        return isNaN(parsed) ? NaN : parsed;
    },

    detectColumnType: function (th, title) {
        if (th.dataset.filterType) return th.dataset.filterType;
        let t = App.TableFilter.toTrLower(title);

        if (t.includes('tarih') || t.includes('date') || t.includes('bitis') || t.includes('bitiş') ||
            t.includes('baslangic') || t.includes('başlangıç') || t.includes('vade') || t.includes('onay')) {
            return 'date';
        }

        if (t.includes('tutar') || t.includes('fiyat') || t.includes('ucret') || t.includes('ücret') ||
            t.includes('adet') || t.includes('miktar') || t.includes('oran') || t.includes('kdv') ||
            t.includes('iskonto') || t.includes('toplam') || t.includes('sira no') || t.includes('sıra no') ||
            t === '#' || t === 'id') {
            return 'number';
        }

        if (t === 'durum' || t === 'statu' || t === 'statü') {
            return 'select';
        }

        return 'text';
    },

    attachToTable: function (table) {
        if (!table || table.classList.contains('no-filter')) return;
        App.TableFilter.relocateSearchInput(table);
        const tableId = table.id;
        if (!tableId) return;

        const headers = table.querySelectorAll('thead th');
        if (!headers.length) return;

        headers.forEach((th, index) => {
            if (th.querySelector('.tf-trigger')) return;

            // Extract text ignoring any existing tags
            const rawTitle = th.childNodes.length > 0 ? (th.childNodes[0].textContent || th.textContent).trim() : th.textContent.trim();
            if (th.classList.contains('no-filter') ||
                th.classList.contains('no-export') ||
                th.querySelector('input[type="checkbox"]') ||
                rawTitle === 'İşlem' ||
                rawTitle === 'İşlemler' ||
                rawTitle === '') {
                return;
            }

            const cleanTitle = rawTitle.replace(/\s+/g, ' ');
            const filterType = App.TableFilter.detectColumnType(th, cleanTitle);
            th.setAttribute('data-filter-type', filterType);

            // Create filter trigger button
            const triggerBtn = document.createElement('button');
            triggerBtn.type = 'button';
            triggerBtn.className = 'tf-trigger';
            triggerBtn.title = cleanTitle + ' Filtrele';
            triggerBtn.innerHTML = App.TableFilter.SVG_FILTER_ICON;
            triggerBtn.dataset.tableId = tableId;
            triggerBtn.dataset.colIndex = index;

            th.classList.add('tf-header-cell');
            th.appendChild(triggerBtn);

            // Generate unique Popover
            const popoverId = `tf-pop-${tableId}-${index}`;
            let popover = document.getElementById(popoverId);
            if (!popover) {
                popover = document.createElement('div');
                popover.id = popoverId;
                popover.className = 'tf-popover';
                popover.dataset.tableId = tableId;
                popover.dataset.colIndex = index;
                popover.dataset.type = filterType;

                popover.innerHTML = `
                    <div class="tf-header">
                        <span class="tf-title">${cleanTitle}</span>
                        <button type="button" class="tf-close" title="Kapat">&times;</button>
                    </div>
                    <div class="tf-body">
                        ${App.TableFilter.renderFilterBody(filterType, table, index)}
                    </div>
                    <div class="tf-footer">
                        <button type="button" class="btn btn-sm tf-clear">Temizle</button>
                        <button type="button" class="btn btn-sm tf-apply">Uygula</button>
                    </div>
                `;

                document.body.appendChild(popover);

                // Close button
                popover.querySelector('.tf-close').addEventListener('click', function () {
                    popover.classList.remove('show');
                });

                // Clear button
                popover.querySelector('.tf-clear').addEventListener('click', function () {
                    App.TableFilter.clear(tableId, index, popover);
                });

                // Apply button
                popover.querySelector('.tf-apply').addEventListener('click', function () {
                    App.TableFilter.apply(tableId, index, popover);
                });

                // Enter key support
                popover.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT')) {
                        e.preventDefault();
                        App.TableFilter.apply(tableId, index, popover);
                    }
                });

                App.TableFilter.initSelect2Inputs(popover, popover);
            }

            // Prevent DataTables sorting on trigger click / mousedown
            triggerBtn.addEventListener('mousedown', function (e) {
                e.stopPropagation();
            });

            triggerBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();

                // Close other popovers
                document.querySelectorAll('.tf-popover.show').forEach(function (p) {
                    if (p !== popover) p.classList.remove('show');
                });

                const isShown = popover.classList.contains('show');
                if (isShown) {
                    popover.classList.remove('show');
                    return;
                }

                // If type is select, refresh distinct options
                if (filterType === 'select') {
                    const body = popover.querySelector('.tf-body');
                    body.innerHTML = App.TableFilter.renderFilterBody('select', table, index);
                }

                // Position popover
                const rect = triggerBtn.getBoundingClientRect();
                popover.style.top = (rect.bottom + window.scrollY + 6) + 'px';
                let leftPos = rect.left + window.scrollX - 20;
                if (leftPos + 310 > window.innerWidth) {
                    leftPos = window.innerWidth - 325;
                }
                if (leftPos < 10) leftPos = 10;
                popover.style.left = leftPos + 'px';

                popover.classList.add('show');

                // Initialize Select2 in popover
                App.TableFilter.initSelect2Inputs(popover, popover);

                // Initialize Flatpickr if date
                if (filterType === 'date') {
                    App.TableFilter.initDateInputs(popover);
                }

                // Focus first input
                setTimeout(function () {
                    const firstInput = popover.querySelector('.tf-input');
                    if (firstInput) firstInput.focus();
                }, 50);
            });
        });
    },

    initSelect2Inputs: function (container, popover) {
        if (!window.jQuery || !$.fn.select2) return;
        const $target = $(container || document);
        const parentEl = popover || ($target.hasClass('tf-popover') ? $target : $target.closest('.tf-popover'));

        $target.find('.tf-operator-select').each(function () {
            const $this = $(this);
            if ($this.hasClass('select2-hidden-accessible')) return;

            $this.select2({
                dropdownParent: parentEl && parentEl.length ? parentEl : $('body'),
                minimumResultsForSearch: Infinity,
                width: '100%'
            });

            $this.on('change', function () {
                App.TableFilter.onOperatorChange(this);
            });
        });
    },

    renderFilterBody: function (type, table, colIndex) {
        if (type === 'select') {
            const values = new Set();
            table.querySelectorAll('tbody tr:not(.odd):not(.even):not(.dataTables_empty), tbody tr').forEach(row => {
                if (row.classList.contains('search-input-row')) return;
                const cell = row.cells[colIndex];
                if (cell) {
                    let txt = cell.textContent.trim();
                    if (txt && txt !== 'Veriler Yükleniyor...' && txt !== 'Hiç kayıt bulunamadı!') {
                        values.add(txt);
                    }
                }
            });

            let html = '<div class="tf-checkbox-list">';
            if (values.size === 0) {
                html += '<div class="text-muted small p-2">Kayıtlı değer bulunamadı</div>';
            } else {
                Array.from(values).sort().forEach((val, idx) => {
                    const chkId = `tf-chk-${table.id}-${colIndex}-${idx}`;
                    html += `
                        <div class="form-check tf-checkbox-item">
                            <input class="form-check-input" type="checkbox" value="${val.replace(/"/g, '&quot;')}" id="${chkId}">
                            <label class="form-check-label" for="${chkId}">${val}</label>
                        </div>
                    `;
                });
            }
            html += '</div>';
            return html;
        }

        return `
            <div class="tf-rules-container">
                ${App.TableFilter.renderRuleRow(type, false)}
            </div>
            <button type="button" class="tf-rule-add" onclick="App.TableFilter.addRule(this, '${type}')">
                ${App.TableFilter.SVG_PLUS_ICON} Kural Ekle
            </button>
        `;
    },

    renderRuleRow: function (type, isAdditional) {
        const operators = type === 'date' ? [
            { val: 'equals', text: 'Eşittir' },
            { val: 'after', text: 'Sonra (>)' },
            { val: 'before', text: 'Önce (<)' },
            { val: 'gte', text: 'Büyük Eşit (≥)' },
            { val: 'lte', text: 'Küçük Eşit (≤)' },
            { val: 'empty', text: 'Boş' },
            { val: 'not_empty', text: 'Dolu' }
        ] : (type === 'number') ? [
            { val: 'equals', text: 'Eşittir (=)' },
            { val: 'gt', text: 'Büyüktür (>)' },
            { val: 'lt', text: 'Küçüktür (<)' },
            { val: 'gte', text: 'Büyük Eşit (≥)' },
            { val: 'lte', text: 'Küçük Eşit (≤)' },
            { val: 'empty', text: 'Boş' },
            { val: 'not_empty', text: 'Dolu' }
        ] : [
            { val: 'contains', text: 'İçerir' },
            { val: 'equals', text: 'Eşittir' },
            { val: 'starts', text: 'İle Başlar' },
            { val: 'ends', text: 'İle Biter' },
            { val: 'not_contains', text: 'İçermez' },
            { val: 'empty', text: 'Boş' },
            { val: 'not_empty', text: 'Dolu' }
        ];

        let selectHtml = `<select class="form-select form-select-sm tf-operator-select" onchange="App.TableFilter.onOperatorChange(this)">`;
        operators.forEach(op => {
            selectHtml += `<option value="${op.val}">${op.text}</option>`;
        });
        selectHtml += `</select>`;

        let inputHtml = '';
        if (type === 'date') {
            inputHtml = `<div class="tf-input-wrapper"><input type="text" class="form-control form-control-sm tf-input tf-date-input" placeholder="Tarih seçin..." autocomplete="off"><span class="tf-calendar-icon">${App.TableFilter.SVG_CALENDAR_ICON}</span></div>`;
        } else if (type === 'number') {
            inputHtml = `<input type="number" step="any" class="form-control form-control-sm tf-input" placeholder="Değer girin..." autocomplete="off">`;
        } else {
            inputHtml = `<input type="text" class="form-control form-control-sm tf-input" placeholder="Değer girin..." autocomplete="off">`;
        }

        return `
            <div class="tf-rule-row">
                ${isAdditional ? `<button type="button" class="tf-rule-remove" onclick="this.parentElement.remove()" title="Kuralı Sil">${App.TableFilter.SVG_TRASH_ICON}</button>` : ''}
                ${selectHtml}
                ${inputHtml}
            </div>
        `;
    },

    onOperatorChange: function (selectEl) {
        const val = selectEl.value;
        const row = selectEl.closest('.tf-rule-row');
        if (!row) return;
        const inputWrap = row.querySelector('.tf-input-wrapper') || row.querySelector('.tf-input');
        if (inputWrap) {
            if (val === 'empty' || val === 'not_empty') {
                inputWrap.style.display = 'none';
            } else {
                inputWrap.style.display = '';
            }
        }
    },

    addRule: function (btn, type) {
        const container = btn.previousElementSibling;
        const popover = btn.closest('.tf-popover');
        const temp = document.createElement('div');
        temp.innerHTML = App.TableFilter.renderRuleRow(type, true);
        const newRow = temp.firstElementChild;
        container.appendChild(newRow);

        App.TableFilter.initSelect2Inputs(newRow, popover);

        if (type === 'date') {
            App.TableFilter.initDateInputs(newRow);
        }
    },

    initDateInputs: function (container) {
        if (!container || !window.flatpickr) return;
        container.querySelectorAll('input.tf-date-input:not([data-fp-initialized])').forEach(input => {
            input.dataset.fpInitialized = 'true';
            flatpickr(input, {
                dateFormat: 'd.m.Y',
                allowInput: true,
                locale: 'tr'
            });
        });
    },

    apply: function (tableId, colIndex, popover) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const filterType = popover.dataset.type;
        const filterData = { type: filterType, colIndex: colIndex, rules: [] };

        if (filterType === 'select') {
            const checked = Array.from(popover.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
            if (checked.length === 0) {
                App.TableFilter.clear(tableId, colIndex, popover);
                return;
            }
            filterData.values = checked;
        } else {
            const rows = popover.querySelectorAll('.tf-rule-row');
            rows.forEach(row => {
                const operator = row.querySelector('.tf-operator-select').value;
                const inputEl = row.querySelector('.tf-input');
                let val = inputEl ? inputEl.value.trim() : '';

                if (val || operator === 'empty' || operator === 'not_empty') {
                    filterData.rules.push({
                        operator: operator,
                        value: val,
                        numValue: App.TableFilter.parseNum(val),
                        dateValue: App.TableFilter.parseDate(val)
                    });
                }
            });

            if (filterData.rules.length === 0) {
                App.TableFilter.clear(tableId, colIndex, popover);
                return;
            }
        }

        App.TableFilter.activeFilters[tableId] = App.TableFilter.activeFilters[tableId] || {};
        App.TableFilter.activeFilters[tableId][colIndex] = filterData;

        // Highlight header trigger button
        const trigger = table.querySelector(`.tf-trigger[data-col-index="${colIndex}"]`);
        if (trigger) trigger.classList.add('active');

        popover.classList.remove('show');
        App.TableFilter.redrawTable(tableId);
        App.TableFilter.updateActiveSummary(tableId);
    },

    clear: function (tableId, colIndex, popover) {
        const table = document.getElementById(tableId);
        if (!table) return;

        if (App.TableFilter.activeFilters[tableId]) {
            delete App.TableFilter.activeFilters[tableId][colIndex];
            if (Object.keys(App.TableFilter.activeFilters[tableId]).length === 0) {
                delete App.TableFilter.activeFilters[tableId];
            }
        }

        const trigger = table.querySelector(`.tf-trigger[data-col-index="${colIndex}"]`);
        if (trigger) trigger.classList.remove('active');

        // Reset inputs in popover
        popover.querySelectorAll('.tf-input').forEach(i => i.value = '');
        popover.querySelectorAll('input[type="checkbox"]:checked').forEach(c => c.checked = false);
        const ruleRows = popover.querySelectorAll('.tf-rule-row');
        for (let i = 1; i < ruleRows.length; i++) {
            ruleRows[i].remove();
        }
        $(popover).find('.tf-operator-select').each(function () {
            this.selectedIndex = 0;
            $(this).trigger('change');
        });

        popover.classList.remove('show');
        App.TableFilter.redrawTable(tableId);
        App.TableFilter.updateActiveSummary(tableId);
    },

    clearAll: function (tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        delete App.TableFilter.activeFilters[tableId];
        table.querySelectorAll('.tf-trigger.active').forEach(t => t.classList.remove('active'));

        document.querySelectorAll(`.tf-popover[data-table-id="${tableId}"]`).forEach(pop => {
            pop.querySelectorAll('.tf-input').forEach(i => i.value = '');
            pop.querySelectorAll('input[type="checkbox"]:checked').forEach(c => c.checked = false);
            const ruleRows = pop.querySelectorAll('.tf-rule-row');
            for (let i = 1; i < ruleRows.length; i++) ruleRows[i].remove();
            $(pop).find('.tf-operator-select').each(function () {
                this.selectedIndex = 0;
                $(this).trigger('change');
            });
        });

        App.TableFilter.redrawTable(tableId);
        App.TableFilter.updateActiveSummary(tableId);
    },

    removeColumnFilter: function (tableId, colIndex) {
        const popover = document.getElementById(`tf-pop-${tableId}-${colIndex}`);
        if (popover) {
            App.TableFilter.clear(tableId, colIndex, popover);
        } else {
            if (App.TableFilter.activeFilters[tableId]) {
                delete App.TableFilter.activeFilters[tableId][colIndex];
                if (Object.keys(App.TableFilter.activeFilters[tableId]).length === 0) {
                    delete App.TableFilter.activeFilters[tableId];
                }
            }
            const table = document.getElementById(tableId);
            if (table) {
                const trigger = table.querySelector(`.tf-trigger[data-col-index="${colIndex}"]`);
                if (trigger) trigger.classList.remove('active');
            }
            App.TableFilter.redrawTable(tableId);
            App.TableFilter.updateActiveSummary(tableId);
        }
    },

    formatRuleText: function (rule, type) {
        const op = rule.operator;
        if (op === 'empty') return 'Boş';
        if (op === 'not_empty') return 'Dolu';
        const val = rule.value || '';
        if (op === 'equals') return `${val}`;
        if (op === 'contains') return `İçerir: ${val}`;
        if (op === 'not_contains') return `İçermez: ${val}`;
        if (op === 'starts') return `İle başlar: ${val}`;
        if (op === 'ends') return `İle biter: ${val}`;
        if (op === 'gt' || op === 'after') return `> ${val}`;
        if (op === 'lt' || op === 'before') return `< ${val}`;
        if (op === 'gte') return `≥ ${val}`;
        if (op === 'lte') return `≤ ${val}`;
        return val;
    },

    updateActiveSummary: function (tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const summaryId = `tf-summary-${tableId}`;
        let summaryEl = document.getElementById(summaryId);

        const tableFilters = App.TableFilter.activeFilters[tableId] || {};
        const colIndexes = Object.keys(tableFilters);

        if (colIndexes.length === 0) {
            if (summaryEl) summaryEl.remove();
            return;
        }

        const headers = table.querySelectorAll('thead th');
        let chipsHtml = '';

        colIndexes.forEach(colIdxStr => {
            const colIdx = parseInt(colIdxStr, 10);
            const filterDef = tableFilters[colIdx];
            const th = headers[colIdx];
            let colTitle = 'Kolon ' + (colIdx + 1);
            if (th) {
                const rawTitle = th.childNodes.length > 0 ? (th.childNodes[0].textContent || th.textContent).trim() : th.textContent.trim();
                colTitle = rawTitle.replace(/\s+/g, ' ');
            }

            let desc = '';
            if (filterDef.type === 'select') {
                desc = (filterDef.values || []).join(', ');
            } else if (filterDef.rules && filterDef.rules.length) {
                desc = filterDef.rules.map(r => App.TableFilter.formatRuleText(r, filterDef.type)).join(' & ');
            }

            chipsHtml += `
                <div class="tf-filter-chip">
                    <span class="tf-chip-text"><strong>${colTitle}:</strong> ${desc}</span>
                    <button type="button" class="tf-chip-remove" onclick="App.TableFilter.removeColumnFilter('${tableId}', ${colIdx})" title="Filtreyi Kaldır">&times;</button>
                </div>
            `;
        });

        const summaryHtml = `
            <div class="tf-summary-left">
                <span class="tf-summary-label">Aktif filtreler:</span>
                <div class="tf-chips-wrap">
                    ${chipsHtml}
                </div>
            </div>
            <div class="tf-summary-right">
                <button type="button" class="tf-clear-all-summary" onclick="App.TableFilter.clearAll('${tableId}')">
                    ${App.TableFilter.SVG_FILTER_ICON} Filtreleri Temizle
                </button>
            </div>
        `;

        if (!summaryEl) {
            summaryEl = document.createElement('div');
            summaryEl.id = summaryId;
            summaryEl.className = 'tf-active-filters-bar';
            
            const wrapper = table.closest('.dataTables_wrapper');
            if (wrapper) {
                wrapper.parentNode.insertBefore(summaryEl, wrapper);
            } else {
                table.parentNode.insertBefore(summaryEl, table);
            }
        }

        summaryEl.innerHTML = summaryHtml;
    },

    redrawTable: function (tableId) {
        const $table = $('#' + tableId);
        if ($.fn.dataTable && $.fn.dataTable.isDataTable('#' + tableId)) {
            const dt = $table.DataTable();
            if (dt.init().serverSide) {
                const colFilters = App.TableFilter.activeFilters[tableId] || {};
                dt.columns().every(function (idx) {
                    const f = colFilters[idx];
                    if (f && f.rules && f.rules.length) {
                        this.search(f.rules[0].value);
                    } else if (f && f.values && f.values.length) {
                        this.search(f.values.join('|'), true, false);
                    } else {
                        this.search('');
                    }
                });
            }
            dt.draw();
        }
    },

    evaluateRule: function (cellRaw, rule, type) {
        const op = rule.operator;
        const cellText = App.TableFilter.toTrLower(cellRaw);
        const isBlank = cellText === '' || cellText === '-' || cellText === 'null';

        if (op === 'empty') return isBlank;
        if (op === 'not_empty') return !isBlank;

        if (type === 'number') {
            const cellNum = App.TableFilter.parseNum(cellRaw);
            const targetNum = rule.numValue;
            if (isNaN(cellNum) || isNaN(targetNum)) return false;

            if (op === 'equals') return Math.abs(cellNum - targetNum) < 0.0001;
            if (op === 'gt') return cellNum > targetNum;
            if (op === 'lt') return cellNum < targetNum;
            if (op === 'gte') return cellNum >= targetNum;
            if (op === 'lte') return cellNum <= targetNum;
            return false;
        }

        if (type === 'date') {
            const cellDate = App.TableFilter.parseDate(cellRaw);
            const targetDate = rule.dateValue;
            if (isNaN(cellDate) || isNaN(targetDate)) return false;

            const d1 = new Date(cellDate).setHours(0, 0, 0, 0);
            const d2 = new Date(targetDate).setHours(0, 0, 0, 0);

            if (op === 'equals') return d1 === d2;
            if (op === 'after') return d1 > d2;
            if (op === 'before') return d1 < d2;
            if (op === 'gte') return d1 >= d2;
            if (op === 'lte') return d1 <= d2;
            return false;
        }

        const targetText = App.TableFilter.toTrLower(rule.value);
        if (!targetText) return true;

        if (op === 'contains') return cellText.includes(targetText);
        if (op === 'equals') return cellText === targetText;
        if (op === 'starts') return cellText.startsWith(targetText);
        if (op === 'ends') return cellText.endsWith(targetText);
        if (op === 'not_contains') return !cellText.includes(targetText);

        return true;
    }
};

// Auto-initialize on DOM ready and continuously scan for tables
$(document).ready(function () {
    App.TableFilter.init();
    setTimeout(App.TableFilter.init, 300);
    setTimeout(App.TableFilter.init, 800);
    setTimeout(App.TableFilter.init, 1800);
});

// Periodic scanner for dynamically loaded AJAX tables
setInterval(function () {
    App.TableFilter.init();
}, 2000);
