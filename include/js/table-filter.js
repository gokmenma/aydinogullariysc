/**
 * Centralized DataTable Column Filter Module
 * Features: String (text), Number, Date and Select filters with multi-rule popover modals.
 */

window.App = window.App || {};

App.TableFilter = {
    activeFilters: {}, // tableId -> { colIndex -> { type: 'text'|'number'|'date'|'select', rules: [...], values: [...] } }
    columnOptionPool: {}, // tableId -> { colIndex -> { [val]: count } }
    hooksBound: false,
    xhrBound: false,

    KNOWN_COLUMN_OPTIONS: {
        'durum': [
            'Bekliyor', 'Çalışıyor', 'Tamamlandı', 'İptal Edildi', 'İptal',
            'FATURA KESİLDİ', 'BEDELSİZ', 'PRF', 'KEŞİF / ZİYARET', 'MUHASEBEYE TESLİM EDİLDİ.',
            'Onaylandı', 'Reddedildi', 'Revize', 'Hazırlanıyor', 'Gönderildi', 'Beklemede',
            'Aktif', 'Pasif', 'Randevu Verildi', 'Yedek Parça Bekleniyor', 'Atölyede', 'Test Aşamasında'
        ],
        'status': [
            'Bekliyor', 'Çalışıyor', 'Tamamlandı', 'İptal Edildi', 'İptal',
            'Onaylandı', 'Reddedildi', 'Revize', 'Aktif', 'Pasif'
        ],
        'sözleşme': [
            'Sözleşmeli', 'Sözleşme Bekliyor', 'Bekliyor', 'Sözleşme Yapıldı', 'S.Kapsamında Değildir', 'Sözleşme Yapılmadı', 'Yapılmadı'
        ],
        'muhasebe': [
            'Teslim Bekliyor', 'Teslim Alındı', 'İade Alındı'
        ],
        'işlem türü': [
            'Oluşturma', 'Güncelleme', 'Silme', 'Giriş', 'Çıkış', 'Dışa Aktarma', 'Sayfa Ziyareti', 'Görüntüleme', 'Durum Değişikliği', 'Hata', 'Kritik'
        ],
        'olay': [
            'Oluşturma', 'Güncelleme', 'Silme', 'Giriş', 'Çıkış', 'Dışa Aktarma', 'Sayfa Ziyareti', 'Görüntüleme', 'Durum Değişikliği', 'Hata'
        ],
        'modül': [
            'Genel', 'Auth', 'Offers', 'Services', 'Customers', 'Products', 'Purchases', 'Reports', 'Settings', 'Users', 'Version_notes', 'Backup_gdrive', 'Send-mail-accounts', 'Permissions', 'Logs'
        ],
        'para birimi': [
            'TRY', 'USD', 'EUR', 'GBP', 'TL', '₺', '$', '€'
        ],
        'ödeme vadesi': [
            'Peşin', '15 Gün', '30 Gün', '45 Gün', '60 Gün', '90 Gün', '120 Gün', 'Aylık', 'Yıllık', 'Kredi Kartı', 'Havale / EFT'
        ],
        'seviye': [
            'INFO', 'WARNING', 'ERROR', 'CRITICAL', 'DEBUG', 'NOTICE', 'ALERT', 'EMERGENCY'
        ]
    },

    SVG_FILTER_ICON: '<svg class="tf-funnel-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; pointer-events:none;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>',
    SVG_PLUS_ICON: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    SVG_TRASH_ICON: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    SVG_CALENDAR_ICON: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
    SVG_SEARCH_ICON: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',

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

    harvestRowsFromData: function (table, rows) {
        if (!table || !rows || !Array.isArray(rows)) return;
        const tableId = table.id;
        if (!tableId) return;

        App.TableFilter.columnOptionPool[tableId] = App.TableFilter.columnOptionPool[tableId] || {};

        rows.forEach(row => {
            if (Array.isArray(row)) {
                row.forEach((cellData, colIdx) => {
                    const txt = App.TableFilter.extractCellTextFromRaw(cellData);
                    if (txt && txt !== 'Veriler Yükleniyor...' && txt !== 'Hiç kayıt bulunamadı!' && txt !== '-') {
                        App.TableFilter.columnOptionPool[tableId][colIdx] = App.TableFilter.columnOptionPool[tableId][colIdx] || {};
                        App.TableFilter.columnOptionPool[tableId][colIdx][txt] = (App.TableFilter.columnOptionPool[tableId][colIdx][txt] || 0) + 1;
                    }
                });
            } else if (typeof row === 'object' && row !== null) {
                const keys = Object.keys(row);
                keys.forEach((key, colIdx) => {
                    const txt = App.TableFilter.extractCellTextFromRaw(row[key]);
                    if (txt && txt !== 'Veriler Yükleniyor...' && txt !== 'Hiç kayıt bulunamadı!' && txt !== '-') {
                        App.TableFilter.columnOptionPool[tableId][colIdx] = App.TableFilter.columnOptionPool[tableId][colIdx] || {};
                        App.TableFilter.columnOptionPool[tableId][colIdx][txt] = (App.TableFilter.columnOptionPool[tableId][colIdx][txt] || 0) + 1;
                    }
                });
            }
        });
    },

    findPageSelectOptions: function (th, cleanTitle) {
        const titleLower = App.TableFilter.toTrLower(cleanTitle);
        const options = [];

        let selectors = [];
        if (titleLower.indexOf('durum') !== -1 || titleLower.indexOf('statu') !== -1) {
            selectors = ['#filter_status', '#filter_statu', '#filter_pstatu', 'select[name*="status"]', 'select[name*="statu"]', 'select[name*="durum"]'];
        } else if (titleLower.indexOf('oluşturan') !== -1 || titleLower.indexOf('kullanıcı') !== -1 || titleLower.indexOf('user') !== -1 || titleLower.indexOf('yetkili') !== -1) {
            selectors = ['#filter_user', '#filter_creator', '#filter_author', 'select[name*="user"]', 'select[name*="creator"]', 'select[name*="author"]'];
        } else if (titleLower.indexOf('firma') !== -1 || titleLower.indexOf('müşteri') !== -1 || titleLower.indexOf('customer') !== -1 || titleLower.indexOf('company') !== -1) {
            selectors = ['#filter_company', '#filter_customer', '#filter_cid', 'select[name*="company"]', 'select[name*="customer"]', 'select[name*="cid"]'];
        } else if (titleLower.indexOf('modül') !== -1 || titleLower.indexOf('module') !== -1) {
            selectors = ['#filter_module', 'select[name*="module"]'];
        } else if (titleLower.indexOf('bölge') !== -1 || titleLower.indexOf('region') !== -1) {
            selectors = ['#filter_region', '#filter_bolge', 'select[name*="region"]', 'select[name*="bolge"]'];
        } else if (titleLower.indexOf('sözleşme') !== -1 || titleLower.indexOf('contract') !== -1) {
            selectors = ['#filter_contract', '#filter_contract_status', '#filter_sozlesme', 'select[name*="contract"]', 'select[name*="sozlesme"]'];
        } else if (titleLower.indexOf('para') !== -1 || titleLower.indexOf('currency') !== -1) {
            selectors = ['#filter_currency', 'select[name*="currency"]', 'select[name*="para"]'];
        } else if (titleLower.indexOf('vade') !== -1 || titleLower.indexOf('ödeme') !== -1 || titleLower.indexOf('payment') !== -1) {
            selectors = ['#filter_payment_period', 'select[name*="payment"]', 'select[name*="vade"]'];
        } else if (titleLower.indexOf('seviye') !== -1 || titleLower.indexOf('level') !== -1) {
            selectors = ['#filter_level', 'select[name*="level"]'];
        }

        selectors.forEach(sel => {
            document.querySelectorAll(sel).forEach(selectEl => {
                selectEl.querySelectorAll('option').forEach(opt => {
                    const text = opt.textContent.trim();
                    const val = opt.value;
                    if (text && text !== '-' && text !== 'Seçiniz' && text !== 'Seçiniz...' && text !== 'Tümü' && text !== 'Tümünü Seç' && text !== 'Filtrele' && val !== '') {
                        if (!options.includes(text)) {
                            options.push(text);
                        }
                    }
                });
            });
        });

        return options;
    },

    bindDataTableHooks: function () {
        if (!window.jQuery || !$.fn || !$.fn.dataTable || App.TableFilter.hooksBound) return;
        App.TableFilter.hooksBound = true;

        if ($.fn.dataTable.ext && $.fn.dataTable.ext.search) {
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex, rowData, counter) {
                const tableId = settings.sTableId || (settings.nTable ? (settings.nTable.id || $(settings.nTable).attr('id')) : null);
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
                            const cText = App.TableFilter.extractCellTextFromRaw(cellValue);
                            const cTextLower = App.TableFilter.toTrLower(cText);
                            const match = selectedVals.some(v => {
                                const vLower = App.TableFilter.toTrLower(v);
                                return vLower === cTextLower || cTextLower.indexOf(vLower) !== -1;
                            });
                            if (!match) return false;
                        }
                    } else if (filterDef.rules && filterDef.rules.length > 0) {
                        const logic = filterDef.logic || (filterDef.type === 'text' ? 'or' : 'and');
                        if (logic === 'or') {
                            const passed = filterDef.rules.some(r => App.TableFilter.evaluateRule(cellValue, r, filterDef.type));
                            if (!passed) return false;
                        } else {
                            const passed = filterDef.rules.every(r => App.TableFilter.evaluateRule(cellValue, r, filterDef.type));
                            if (!passed) return false;
                        }
                    }
                }

                return true;
            });
        }

        // Global XHR interceptor for all DataTables
        if (!App.TableFilter.xhrBound) {
            App.TableFilter.xhrBound = true;
            $(document).on('xhr.dt', function (e, settings, json, xhr) {
                if (json && json.data && Array.isArray(json.data) && settings.nTable) {
                    App.TableFilter.harvestRowsFromData(settings.nTable, json.data);
                }
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
                    try {
                        const rows = api.rows({ page: 'current' }).data().toArray();
                        if (rows && rows.length) {
                            App.TableFilter.harvestRowsFromData(tableNode, rows);
                        }
                    } catch (e) {}
                }
            },
            drawCallback: function () {
                const api = this.api();
                const tableNode = api.table().node();
                if (tableNode) {
                    App.TableFilter.attachToTable(tableNode);
                    App.TableFilter.relocateSearchInput(tableNode);
                    try {
                        const rows = api.rows({ page: 'current' }).data().toArray();
                        if (rows && rows.length) {
                            App.TableFilter.harvestRowsFromData(tableNode, rows);
                        }
                    } catch (e) {}
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

    extractCellTextFromRaw: function (raw) {
        if (raw === null || raw === undefined) return '';
        let str = String(raw).trim();
        if (!str) return '';

        // If it looks like HTML, strip tags while keeping clean text
        if (str.indexOf('<') !== -1) {
            const temp = document.createElement('div');
            temp.innerHTML = str;

            // Remove decorative elements, avatars, icons, buttons
            temp.querySelectorAll('.user-mini-avatar, .avatar, script, style, button, i, svg').forEach(el => el.remove());

            // Look for semantic text elements first
            const mainTextEl = temp.querySelector('.font-12.weight-600, .weight-600, .badge, .module-tag, .entity-pill, .log-message, span, strong');
            if (mainTextEl && mainTextEl.textContent.trim()) {
                str = mainTextEl.textContent.trim();
            } else {
                str = temp.textContent || '';
            }
        }

        return str.replace(/\s+/g, ' ').trim();
    },

    extractCellTextFromNode: function (cellNode) {
        if (!cellNode) return '';
        const clone = cellNode.cloneNode(true);
        clone.querySelectorAll('.user-mini-avatar, .avatar, script, style, button, i, svg').forEach(el => el.remove());

        const mainTextEl = clone.querySelector('.font-12.weight-600, .weight-600, .badge, .module-tag, .entity-pill, .log-message');
        if (mainTextEl && mainTextEl.textContent.trim()) {
            return mainTextEl.textContent.replace(/\s+/g, ' ').trim();
        }

        return (clone.textContent || '').replace(/\s+/g, ' ').trim();
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
            t.includes('baslangic') || t.includes('başlangıç') || t.includes('vade') || t.includes('onay') ||
            t.includes('zaman') || t.includes('saat')) {
            return 'date';
        }

        if (t.includes('tutar') || t.includes('fiyat') || t.includes('ucret') || t.includes('ücret') ||
            t.includes('adet') || t.includes('miktar') || t.includes('oran') || t.includes('kdv') ||
            t.includes('iskonto') || t.includes('toplam') || t.includes('sira no') || t.includes('sıra no') ||
            t === '#' || t === 'id') {
            return 'number';
        }

        if (t.includes('durum') || t.includes('statu') || t.includes('statü') ||
            t.includes('kullanici') || t.includes('kullanıcı') || t.includes('user') ||
            t.includes('islem turu') || t.includes('işlem türü') || t.includes('islem tipi') || t.includes('işlem tipi') ||
            t.includes('modul') || t.includes('modül') || t.includes('seviye') || t.includes('level') ||
            t.includes('kategori') || t.includes('birim') || t.includes('para birimi') || t.includes('odeme') || t.includes('ödeme')) {
            return 'select';
        }

        return 'text';
    },

    attachToTable: function (table) {
        if (!table || table.classList.contains('no-filter') || table.classList.contains('table-modern') || table.classList.contains('dash-table')) return;
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

                App.TableFilter.bindSelectSearch(popover);
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
                    App.TableFilter.bindSelectSearch(popover);
                }

                // Position popover
                const rect = triggerBtn.getBoundingClientRect();
                popover.style.top = (rect.bottom + window.scrollY + 6) + 'px';
                let leftPos = rect.left + window.scrollX - 20;
                if (leftPos + 320 > window.innerWidth) {
                    leftPos = window.innerWidth - 335;
                }
                if (leftPos < 10) leftPos = 10;
                popover.style.left = leftPos + 'px';

                popover.classList.add('show');

                // Initialize Flatpickr if date
                if (filterType === 'date') {
                    App.TableFilter.initDateInputs(popover);
                }

                // Focus first input
                setTimeout(function () {
                    const firstInput = popover.querySelector('.tf-select-search') || popover.querySelector('.tf-input');
                    if (firstInput) firstInput.focus();
                }, 50);
            });
        });
    },

    bindSelectSearch: function (popover) {
        if (!popover) return;
        const searchInput = popover.querySelector('.tf-select-search');
        const countLabel = popover.querySelector('.tf-selected-count');
        const rows = popover.querySelectorAll('.tf-checkbox-row');

        function updateSelectedCount() {
            if (!countLabel) return;
            const total = rows.length;
            const checked = popover.querySelectorAll('.tf-checkbox-control:checked').length;
            if (checked > 0) {
                countLabel.textContent = `${checked} / ${total} seçili`;
                countLabel.classList.add('text-primary');
                countLabel.classList.remove('text-muted');
            } else {
                countLabel.textContent = `${total} öğe`;
                countLabel.classList.remove('text-primary');
                countLabel.classList.add('text-muted');
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = App.TableFilter.toTrLower(this.value);
                rows.forEach(row => {
                    const text = App.TableFilter.toTrLower(row.querySelector('.tf-checkbox-text').textContent);
                    if (text.indexOf(query) !== -1) {
                        row.style.display = 'flex';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Live visual toggle for row checkbox
        popover.querySelectorAll('.tf-checkbox-control').forEach(cb => {
            cb.addEventListener('change', function () {
                const row = this.closest('.tf-checkbox-row');
                if (row) {
                    if (this.checked) row.classList.add('is-checked');
                    else row.classList.remove('is-checked');
                }
                updateSelectedCount();
            });
        });

        // "Tümünü Seç"
        const selectAllBtn = popover.querySelector('.tf-select-all');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                popover.querySelectorAll('.tf-checkbox-row').forEach(row => {
                    if (row.style.display !== 'none') {
                        const cb = row.querySelector('.tf-checkbox-control');
                        if (cb) {
                            cb.checked = true;
                            row.classList.add('is-checked');
                        }
                    }
                });
                updateSelectedCount();
            });
        }

        // "Temizle / Seçimi Kaldır"
        const deselectAllBtn = popover.querySelector('.tf-deselect-all');
        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                popover.querySelectorAll('.tf-checkbox-row').forEach(row => {
                    const cb = row.querySelector('.tf-checkbox-control');
                    if (cb) {
                        cb.checked = false;
                        row.classList.remove('is-checked');
                    }
                });
                updateSelectedCount();
            });
        }

        updateSelectedCount();
    },

    getDistinctColumnValues: function (table, colIndex) {
        const counts = {}; // value -> count
        const tableId = table.id;

        // 1. DataTables API'sinden çek (tüm hafızadaki veriler veya mevcut sayfa)
        if (window.jQuery && $.fn.dataTable && $.fn.dataTable.isDataTable(table)) {
            try {
                const dt = $(table).DataTable();
                dt.column(colIndex).data().each(function (cellData) {
                    const txt = App.TableFilter.extractCellTextFromRaw(cellData);
                    if (txt && txt !== 'Veriler Yükleniyor...' && txt !== 'Hiç kayıt bulunamadı!' && txt !== '-') {
                        counts[txt] = (counts[txt] || 0) + 1;
                    }
                });
            } catch (err) {}
        }

        // 2. DOM hücrelerinden de kontrol et ve say
        table.querySelectorAll('tbody tr').forEach(row => {
            if (row.classList.contains('search-input-row') || row.classList.contains('dataTables_empty') || row.classList.contains('tf-no-records-row')) return;
            const cell = row.cells[colIndex];
            if (cell) {
                const txt = App.TableFilter.extractCellTextFromNode(cell);
                if (txt && txt !== 'Veriler Yükleniyor...' && txt !== 'Hiç kayıt bulunamadı!' && txt !== '-') {
                    if (window.jQuery && $.fn.dataTable && $.fn.dataTable.isDataTable(table)) {
                        if (!counts[txt]) counts[txt] = 1;
                    } else {
                        counts[txt] = (counts[txt] || 0) + 1;
                    }
                }
            }
        });

        // 3. Havuzda birikmiş geçmiş AJAX veya sayfa verileri varsa ekle (sayfa değişse de seçenek kaybolmasın)
        if (tableId && App.TableFilter.columnOptionPool[tableId] && App.TableFilter.columnOptionPool[tableId][colIndex]) {
            const pool = App.TableFilter.columnOptionPool[tableId][colIndex];
            Object.keys(pool).forEach(val => {
                if (counts[val] === undefined) {
                    counts[val] = 0;
                }
            });
        }

        // 4. Sütun başlığından bilinen sistem seçeneklerini ve sayfa formlarındaki select seçeneklerini ekle
        const th = table.querySelectorAll('thead th')[colIndex];
        if (th) {
            const rawTitle = th.childNodes.length > 0 ? (th.childNodes[0].textContent || th.textContent).trim() : th.textContent.trim();
            const cleanTitle = rawTitle.replace(/\s+/g, ' ');
            const titleLower = App.TableFilter.toTrLower(cleanTitle);

            // 4a. th dataset custom options
            if (th.dataset.filterOptions) {
                try {
                    const customOpts = JSON.parse(th.dataset.filterOptions);
                    if (Array.isArray(customOpts)) {
                        customOpts.forEach(o => {
                            const trimmed = String(o).trim();
                            if (trimmed && counts[trimmed] === undefined) counts[trimmed] = 0;
                        });
                    }
                } catch (e) {
                    th.dataset.filterOptions.split(',').forEach(o => {
                        const trimmed = o.trim();
                        if (trimmed && counts[trimmed] === undefined) counts[trimmed] = 0;
                    });
                }
            }

            // 4b. Sayfadaki ilgili filtre select kutularından tüm seçenekleri çek
            const pageSelectOpts = App.TableFilter.findPageSelectOptions(th, cleanTitle);
            pageSelectOpts.forEach(optVal => {
                if (counts[optVal] === undefined) {
                    counts[optVal] = 0;
                }
            });

            // 4c. Sistem genelindeki bilinen domain seçeneklerini ekle
            Object.keys(App.TableFilter.KNOWN_COLUMN_OPTIONS).forEach(knownKey => {
                if (titleLower.indexOf(knownKey) !== -1) {
                    App.TableFilter.KNOWN_COLUMN_OPTIONS[knownKey].forEach(knownVal => {
                        if (counts[knownVal] === undefined) {
                            counts[knownVal] = 0;
                        }
                    });
                }
            });
        }

        return counts;
    },

    renderFilterBody: function (type, table, colIndex) {
        if (type === 'select') {
            const counts = App.TableFilter.getDistinctColumnValues(table, colIndex);
            const values = Object.keys(counts).sort((a, b) => a.localeCompare(b, 'tr', { sensitivity: 'base' }));

            // Önceden seçilmiş değerler varsa koru
            const tableId = table.id;
            const existingFilter = (App.TableFilter.activeFilters[tableId] && App.TableFilter.activeFilters[tableId][colIndex]) || null;
            const preselectedVals = (existingFilter && existingFilter.values) || [];

            let html = `
                <div class="tf-select-filter-wrap">
                    <div class="tf-search-box">
                        <span class="tf-search-icon">${App.TableFilter.SVG_SEARCH_ICON}</span>
                        <input type="text" class="tf-select-search" placeholder="Listede ara..." autocomplete="off">
                    </div>
                    <div class="tf-select-actions">
                        <div class="tf-select-quick-links">
                            <button type="button" class="tf-link-btn tf-select-all">Tümünü Seç</button>
                            <span class="tf-link-divider">•</span>
                            <button type="button" class="tf-link-btn tf-deselect-all">Temizle</button>
                        </div>
                        <span class="tf-selected-count text-muted font-11">${preselectedVals.length > 0 ? preselectedVals.length + ' / ' + values.length + ' seçili' : values.length + ' öğe'}</span>
                    </div>
                    <div class="tf-checkbox-list">
            `;

            if (values.length === 0) {
                html += '<div class="tf-no-items">Filtrelenecek kayıt bulunamadı</div>';
            } else {
                values.forEach((val, idx) => {
                    const chkId = `tf-chk-${table.id}-${colIndex}-${idx}`;
                    const isChecked = preselectedVals.indexOf(val) !== -1 ? 'checked' : '';
                    const cnt = counts[val] || 0;
                    const escapedVal = val.replace(/"/g, '&quot;');
                    const badgeHtml = cnt > 0
                        ? `<span class="tf-checkbox-badge">${cnt}</span>`
                        : `<span class="tf-checkbox-badge tf-badge-zero" title="Şu anki sayfada yok">-</span>`;
                    html += `
                        <label class="tf-checkbox-row ${isChecked ? 'is-checked' : ''}" for="${chkId}">
                            <input class="tf-checkbox-control" type="checkbox" value="${escapedVal}" id="${chkId}" ${isChecked}>
                            <span class="tf-checkbox-text" title="${escapedVal}">${val}</span>
                            ${badgeHtml}
                        </label>
                    `;
                });
            }

            html += `
                    </div>
                </div>
            `;
            return html;
        }

        const tableId = table.id;
        const defaultLogic = type === 'text' ? 'or' : 'and';
        return `
            <div class="tf-rules-container">
                ${App.TableFilter.renderRuleRow(type, false)}
            </div>
            <div class="tf-logic-wrap" style="display:none; margin: 8px 0 10px 0;">
                <div class="d-flex align-items-center justify-content-between p-1 px-2 bg-light rounded border">
                    <span class="text-muted" style="font-size: 11px; font-weight: 600;">Kuralları Birleştir:</span>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input tf-logic-radio" type="radio" name="tf_l_${tableId}_${colIndex}" id="tf_l_and_${tableId}_${colIndex}" value="and" ${defaultLogic === 'and' ? 'checked' : ''}>
                            <label class="form-check-label" style="font-size: 11px; cursor: pointer;" for="tf_l_and_${tableId}_${colIndex}">VE</label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input tf-logic-radio" type="radio" name="tf_l_${tableId}_${colIndex}" id="tf_l_or_${tableId}_${colIndex}" value="or" ${defaultLogic === 'or' ? 'checked' : ''}>
                            <label class="form-check-label" style="font-size: 11px; cursor: pointer;" for="tf_l_or_${tableId}_${colIndex}">VEYA</label>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="tf-rule-add" onclick="App.TableFilter.addRule(this, '${type}')">
                ${App.TableFilter.SVG_PLUS_ICON} Kural Ekle
            </button>
        `;
    },

    renderRuleRow: function (type, isAdditional, ruleData) {
        const operators = type === 'date' ? [
            { val: 'equals', text: 'Eşittir' },
            { val: 'after', text: 'Sonra (>)' },
            { val: 'before', text: 'Önce (<)' },
            { val: 'gte', text: 'Büyük Eşit (≥)' },
            { val: 'lte', text: 'Küçük Eşit (≤)' },
            { val: 'empty', text: 'Boş' },
            { val: 'not_empty', text: 'Dolu' }
        ] : (type === 'number') ? [
            { val: 'contains', text: 'İçerir' },
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

        const selOp = ruleData ? ruleData.operator : operators[0].val;
        const val = ruleData ? (ruleData.value || '') : '';

        // Native temiz select - global select2 eklentilerinden etkilenmez
        let selectHtml = `<select class="form-select form-select-sm tf-operator-select" data-select2-ignore="true" onchange="App.TableFilter.onOperatorChange(this)">`;
        operators.forEach(op => {
            const isSel = op.val === selOp ? ' selected' : '';
            selectHtml += `<option value="${op.val}"${isSel}>${op.text}</option>`;
        });
        selectHtml += `</select>`;

        const isHidden = (selOp === 'empty' || selOp === 'not_empty') ? ' style="display:none;"' : '';
        let inputHtml = '';
        if (type === 'date') {
            inputHtml = `<div class="tf-input-wrapper"${isHidden}><input type="text" class="form-control form-control-sm tf-input tf-date-input" placeholder="Tarih seçin..." autocomplete="off" value="${val.replace(/"/g, '&quot;')}"><span class="tf-calendar-icon">${App.TableFilter.SVG_CALENDAR_ICON}</span></div>`;
        } else if (type === 'number') {
            inputHtml = `<input type="text" inputmode="decimal" class="form-control form-control-sm tf-input"${isHidden} placeholder="Değer girin..." autocomplete="off" value="${val.replace(/"/g, '&quot;')}">`;
        } else {
            inputHtml = `<input type="text" class="form-control form-control-sm tf-input"${isHidden} placeholder="Değer girin..." autocomplete="off" value="${val.replace(/"/g, '&quot;')}">`;
        }

        return `
            <div class="tf-rule-row">
                ${isAdditional ? `<button type="button" class="tf-rule-remove" onclick="App.TableFilter.removeRule(this)" title="Kuralı Sil">${App.TableFilter.SVG_TRASH_ICON}</button>` : ''}
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

    updateLogicVisibility: function (popover) {
        if (!popover) return;
        const rows = popover.querySelectorAll('.tf-rule-row');
        const logicWrap = popover.querySelector('.tf-logic-wrap');
        if (logicWrap) {
            logicWrap.style.display = rows.length > 1 ? 'block' : 'none';
        }
    },

    addRule: function (btn, type) {
        const popover = btn.closest('.tf-popover');
        const container = popover.querySelector('.tf-rules-container');
        const temp = document.createElement('div');
        temp.innerHTML = App.TableFilter.renderRuleRow(type, true);
        const newRow = temp.firstElementChild;
        container.appendChild(newRow);

        if (type === 'date') {
            App.TableFilter.initDateInputs(newRow);
        }

        App.TableFilter.updateLogicVisibility(popover);
    },

    removeRule: function (btn) {
        const popover = btn.closest('.tf-popover');
        const row = btn.closest('.tf-rule-row');
        if (row) row.remove();
        App.TableFilter.updateLogicVisibility(popover);
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
            const logicInput = popover.querySelector('.tf-logic-radio:checked');
            const logic = logicInput ? logicInput.value : (filterType === 'text' ? 'or' : 'and');
            filterData.logic = logic;

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
        popover.querySelectorAll('.tf-checkbox-control:checked').forEach(c => c.checked = false);
        popover.querySelectorAll('.tf-checkbox-row').forEach(r => {
            r.classList.remove('is-checked');
            r.style.display = 'flex';
        });
        const searchInp = popover.querySelector('.tf-select-search');
        if (searchInp) searchInp.value = '';

        const countLabel = popover.querySelector('.tf-selected-count');
        if (countLabel) {
            const total = popover.querySelectorAll('.tf-checkbox-row').length;
            countLabel.textContent = `${total} öğe`;
            countLabel.classList.remove('text-primary');
            countLabel.classList.add('text-muted');
        }

        const ruleRows = popover.querySelectorAll('.tf-rule-row');
        for (let i = 1; i < ruleRows.length; i++) {
            ruleRows[i].remove();
        }
        $(popover).find('.tf-operator-select').each(function () {
            this.selectedIndex = 0;
            $(this).trigger('change');
        });

        App.TableFilter.updateLogicVisibility(popover);

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
            pop.querySelectorAll('.tf-checkbox-control:checked').forEach(c => c.checked = false);
            pop.querySelectorAll('.tf-checkbox-row').forEach(r => {
                r.classList.remove('is-checked');
                r.style.display = 'flex';
            });
            const searchInp = pop.querySelector('.tf-select-search');
            if (searchInp) searchInp.value = '';

            const countLabel = pop.querySelector('.tf-selected-count');
            if (countLabel) {
                const total = pop.querySelectorAll('.tf-checkbox-row').length;
                countLabel.textContent = `${total} öğe`;
                countLabel.classList.remove('text-primary');
                countLabel.classList.add('text-muted');
            }

            const ruleRows = pop.querySelectorAll('.tf-rule-row');
            for (let i = 1; i < ruleRows.length; i++) ruleRows[i].remove();
            $(pop).find('.tf-operator-select').each(function () {
                this.selectedIndex = 0;
                $(this).trigger('change');
            });
            App.TableFilter.updateLogicVisibility(pop);
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
        if (op === 'equals') return `Eşittir: ${val}`;
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
                const glue = filterDef.logic === 'or' ? ' VEYA ' : ' VE ';
                desc = filterDef.rules.map(r => App.TableFilter.formatRuleText(r, filterDef.type)).join(glue);
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

    filterDOMTable: function (tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const tableFilters = App.TableFilter.activeFilters[tableId];
        const rows = table.querySelectorAll('tbody tr');
        if (!rows.length) return;

        // Remove existing no-records row if present
        const existingNoRec = table.querySelector('.tf-no-records-row');
        if (existingNoRec) existingNoRec.remove();

        if (!tableFilters || Object.keys(tableFilters).length === 0) {
            rows.forEach(row => {
                if (!row.classList.contains('search-input-row') && !row.classList.contains('tf-no-records-row')) {
                    row.style.display = '';
                }
            });
            return;
        }

        const colIndexes = Object.keys(tableFilters);
        let visibleCount = 0;
        let totalCountableRows = 0;

        rows.forEach(row => {
            if (row.classList.contains('search-input-row') || row.classList.contains('tf-no-records-row')) return;
            totalCountableRows++;

            let matchRow = true;

            for (let i = 0; i < colIndexes.length; i++) {
                const colIdx = parseInt(colIndexes[i], 10);
                const filterDef = tableFilters[colIdx];
                const cell = row.cells[colIdx];
                const cellText = cell ? App.TableFilter.extractCellTextFromNode(cell) : '';

                if (filterDef.type === 'select') {
                    const selectedVals = filterDef.values || [];
                    if (selectedVals.length > 0) {
                        const cTextLower = App.TableFilter.toTrLower(cellText);
                        const match = selectedVals.some(v => {
                            const vLower = App.TableFilter.toTrLower(v);
                            return vLower === cTextLower || cTextLower.indexOf(vLower) !== -1;
                        });
                        if (!match) {
                            matchRow = false;
                            break;
                        }
                    }
                } else if (filterDef.rules && filterDef.rules.length > 0) {
                    const logic = filterDef.logic || (filterDef.type === 'text' ? 'or' : 'and');
                    if (logic === 'or') {
                        const passed = filterDef.rules.some(r => App.TableFilter.evaluateRule(cellText, r, filterDef.type));
                        if (!passed) {
                            matchRow = false;
                            break;
                        }
                    } else {
                        const passed = filterDef.rules.every(r => App.TableFilter.evaluateRule(cellText, r, filterDef.type));
                        if (!passed) {
                            matchRow = false;
                            break;
                        }
                    }
                }
            }

            if (matchRow) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCount === 0 && totalCountableRows > 0) {
            const colCount = table.querySelectorAll('thead th').length || 6;
            const tbody = table.querySelector('tbody');
            if (tbody) {
                const noRecTr = document.createElement('tr');
                noRecTr.className = 'tf-no-records-row text-center';
                noRecTr.innerHTML = `<td colspan="${colCount}" class="text-muted p-4"><i class="fa fa-filter mr-1 text-primary"></i> Seçilen filtre kriterlerine uygun kayıt bulunamadı.</td>`;
                tbody.appendChild(noRecTr);
            }
        }
    },

    redrawTable: function (tableId) {
        const $table = $('#' + tableId);
        if (window.jQuery && $.fn.dataTable && $.fn.dataTable.isDataTable('#' + tableId)) {
            const dt = $table.DataTable();
            if (dt.init().serverSide) {
                const colFilters = App.TableFilter.activeFilters[tableId] || {};
                dt.columns().every(function (idx) {
                    const f = colFilters[idx];
                    if (f && ((f.rules && f.rules.length) || (f.values && f.values.length))) {
                        this.search(JSON.stringify(f));
                    } else {
                        this.search('');
                    }
                });
            }
            dt.draw();
        }
        // Also perform DOM row filtering to guarantee instant UI update across all table types
        App.TableFilter.filterDOMTable(tableId);
    },

    evaluateRule: function (cellRaw, rule, type) {
        const op = rule.operator;
        const cellClean = App.TableFilter.extractCellTextFromRaw(cellRaw);
        const cellText = App.TableFilter.toTrLower(cellClean);
        const isBlank = cellText === '' || cellText === '-' || cellText === 'null';

        if (op === 'empty') return isBlank;
        if (op === 'not_empty') return !isBlank;

        if (type === 'number') {
            const cellNum = App.TableFilter.parseNum(cellRaw);
            const targetNum = rule.numValue;
            const targetValStr = String(rule.value || '').trim();

            if (op === 'contains') {
                const cClean = String(cellClean).replace(/[₺$€\s.]/g, '').replace(',', '.');
                const targetClean = targetValStr.replace(/[₺$€\s.]/g, '').replace(',', '.');
                if (cClean.includes(targetClean)) return true;
                if (!isNaN(cellNum) && !isNaN(targetNum)) {
                    if (String(cellNum).includes(targetClean)) return true;
                }
                return false;
            }

            if (isNaN(cellNum) || isNaN(targetNum)) return false;

            if (op === 'equals') {
                const hasDecimals = targetValStr.includes('.') || targetValStr.includes(',');
                if (!hasDecimals) {
                    return Math.floor(cellNum) === Math.floor(targetNum) || Math.round(cellNum) === Math.round(targetNum) || Math.abs(cellNum - targetNum) < 1.0;
                }
                return Math.abs(cellNum - targetNum) < 0.01;
            }
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
}, 2500);
