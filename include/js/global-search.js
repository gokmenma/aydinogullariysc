/**
 * Global Topbar Search Engine (Teklif, Ürün, Firma, Servis, Keşif, Rapor)
 * Aydınoğulları YSC
 */
(function ($) {
    'use strict';

    var GlobalSearch = {
        input: null,
        clearBtn: null,
        spinner: null,
        dropdown: null,
        resultsContainer: null,
        categoriesContainer: null,
        footerInfo: null,
        totalCountBadge: null,

        debounceTimer: null,
        activeCategory: 'all',
        currentQuery: '',
        lastData: null,
        selectedIndex: -1,
        totalVisibleItems: 0,
        isOpen: false,

        init: function () {
            this.input = $('#global-search-input');
            if (!this.input.length) return;

            this.clearBtn = $('#global-search-clear');
            this.spinner = $('#global-search-spinner');
            this.dropdown = $('#global-search-dropdown');
            this.resultsContainer = $('#global-search-results');
            this.categoriesContainer = $('#global-search-categories');
            this.footerInfo = $('#gs-footer-info');
            this.totalCountBadge = $('#gs-total-count');

            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            // Global Keyboard Shortcut: Ctrl+K / Cmd+K / Slash (/)
            $(document).on('keydown', function (e) {
                var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
                var isCmdOrCtrl = isMac ? e.metaKey : e.ctrlKey;

                if (isCmdOrCtrl && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    self.input.focus();
                    self.input.select();
                    if (self.input.val().trim().length >= 1) {
                        self.openDropdown();
                    } else {
                        self.renderInitialSuggestions();
                    }
                } else if (e.key === 'Escape' && self.isOpen) {
                    e.preventDefault();
                    self.closeDropdown();
                    self.input.blur();
                }
            });

            // Input Events
            this.input.on('focus', function () {
                var val = $(this).val().trim();
                if (val.length >= 1) {
                    if (self.lastData && self.currentQuery === val) {
                        self.openDropdown();
                    } else {
                        self.triggerSearch(val);
                    }
                } else {
                    self.renderInitialSuggestions();
                }
            });

            this.input.on('input', function () {
                var val = $(this).val().trim();
                if (val.length > 0) {
                    self.clearBtn.show();
                } else {
                    self.clearBtn.hide();
                }

                clearTimeout(self.debounceTimer);
                if (val.length >= 1) {
                    self.debounceTimer = setTimeout(function () {
                        self.triggerSearch(val);
                    }, 220);
                } else {
                    self.renderInitialSuggestions();
                }
            });

            // Keyboard Navigation inside input
            this.input.on('keydown', function (e) {
                if (!self.isOpen) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    self.moveSelection(1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    self.moveSelection(-1);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    self.activateSelected();
                }
            });

            // Clear Button
            this.clearBtn.on('click', function (e) {
                e.stopPropagation();
                self.input.val('').focus();
                self.clearBtn.hide();
                self.renderInitialSuggestions();
            });

            // Category Tab Clicks
            this.categoriesContainer.on('click', '.gs-cat-pill', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var cat = $(this).data('cat');
                self.setCategory(cat);
            });

            // Click outside to close
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#global-search-container').length) {
                    self.closeDropdown();
                }
            });

            // Prevent closing when clicking inside dropdown
            this.dropdown.on('click', function (e) {
                e.stopPropagation();
            });

            // Click on result item
            this.resultsContainer.on('click', '.gs-result-item', function (e) {
                var itemType = $(this).data('type');
                var itemId = $(this).data('id');
                if (itemType === 'kesif' && typeof window.openKesifDetailModal === 'function' && window.location.href.indexOf('p=kesif/list') !== -1) {
                    e.preventDefault();
                    self.closeDropdown();
                    self.input.blur();
                    window.openKesifDetailModal(itemId);
                    if (window.history && window.history.pushState) {
                        window.history.pushState({}, '', $(this).attr('href'));
                    }
                }
            });

            // Mouse hover on result items
            this.resultsContainer.on('mouseenter', '.gs-result-item', function () {
                var index = $(this).data('index');
                if (typeof index !== 'undefined') {
                    self.setSelectedIndex(index);
                }
            });
        },

        openDropdown: function () {
            this.dropdown.addClass('show');
            this.isOpen = true;
        },

        closeDropdown: function () {
            this.dropdown.removeClass('show');
            this.isOpen = false;
            this.selectedIndex = -1;
        },

        setCategory: function (cat) {
            this.activeCategory = cat;
            this.categoriesContainer.find('.gs-cat-pill').removeClass('active');
            this.categoriesContainer.find('.gs-cat-pill[data-cat="' + cat + '"]').addClass('active');

            if (this.lastData) {
                this.renderResults(this.lastData);
            }
        },

        triggerSearch: function (query) {
            var self = this;
            this.currentQuery = query;
            this.spinner.show();
            this.clearBtn.hide();

            $.ajax({
                url: 'api/global_search.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    q: query,
                    category: 'all',
                    limit: 8
                },
                success: function (res) {
                    self.spinner.hide();
                    if (self.input.val().trim().length > 0) {
                        self.clearBtn.show();
                    }

                    if (res && res.status === 'success') {
                        self.lastData = res;
                        self.updateCounters(res.counts);
                        self.renderResults(res);
                        self.openDropdown();
                    }
                },
                error: function () {
                    self.spinner.hide();
                    if (self.input.val().trim().length > 0) {
                        self.clearBtn.show();
                    }
                }
            });
        },

        updateCounters: function (counts) {
            if (!counts) return;
            $('#count-all').text(counts.all || 0);
            $('#count-offers').text(counts.offers || 0);
            $('#count-products').text(counts.products || 0);
            $('#count-customers').text(counts.customers || 0);
            $('#count-services').text(counts.services || 0);
            $('#count-kesifler').text(counts.kesifler || 0);
            $('#count-reports').text(counts.reports || 0);
            this.totalCountBadge.text(counts.all || 0);
        },

        highlightText: function (text, query) {
            if (!text) return '';
            if (!query) return $('<div>').text(text).html();

            var safeText = $('<div>').text(text).html();
            var escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            var regex = new RegExp('(' + escapedQuery + ')', 'gi');
            return safeText.replace(regex, '<mark class="gs-highlight">$1</mark>');
        },

        renderResults: function (data) {
            var self = this;
            var html = '';
            var itemGlobalIndex = 0;
            var cat = this.activeCategory;
            var query = this.currentQuery;
            var results = data.results || {};

            var moduleConfig = {
                offers: { title: 'TEKLİFLER', icon: 'fa-file-text-o', color: 'purple', items: results.offers || [] },
                products: { title: 'ÜRÜNLER & HİZMETLER', icon: 'fa-cube', color: 'amber', items: results.products || [] },
                customers: { title: 'FİRMALAR & MÜŞTERİLER', icon: 'fa-building-o', color: 'blue', items: results.customers || [] },
                services: { title: 'SERVİSLER', icon: 'fa-wrench', color: 'emerald', items: results.services || [] },
                kesifler: { title: 'KEŞİFLER', icon: 'fa-search-plus', color: 'cyan', items: results.kesifler || [] },
                reports: { title: 'RAPORLAR', icon: 'fa-file-text', color: 'indigo', items: results.reports || [] }
            };

            var modulesToRender = [];
            if (cat === 'all') {
                modulesToRender = ['offers', 'products', 'customers', 'services', 'kesifler', 'reports'];
            } else if (moduleConfig[cat]) {
                modulesToRender = [cat];
            }

            var totalCount = 0;
            modulesToRender.forEach(function (mKey) {
                var mod = moduleConfig[mKey];
                if (mod && mod.items && mod.items.length > 0) {
                    totalCount += mod.items.length;
                    html += '<div class="gs-category-group">';
                    html += '  <div class="gs-category-header">';
                    html += '    <span class="gs-cat-title"><i class="fa ' + mod.icon + '"></i> ' + mod.title + '</span>';
                    html += '    <span class="gs-cat-count-badge">' + mod.items.length + '</span>';
                    html += '  </div>';
                    html += '  <div class="gs-items-list">';

                    mod.items.forEach(function (item) {
                        var isFirst = (itemGlobalIndex === 0);
                        var activeClass = isFirst ? 'active' : '';

                        html += '<a href="' + item.url + '" class="gs-result-item ' + activeClass + '" data-index="' + itemGlobalIndex + '" data-type="' + item.type + '" data-id="' + item.id + '">';
                        
                        // Left Avatar / Badge
                        html += '  <div class="gs-item-avatar gs-avatar-' + item.color_theme + '">';
                        html += '    <span>' + item.initial + '</span>';
                        html += '    <span class="gs-avatar-dot"></span>';
                        html += '  </div>';

                        // Middle Content
                        html += '  <div class="gs-item-content">';
                        html += '    <div class="gs-item-row-primary">';
                        html += '      <span class="gs-item-title">' + self.highlightText(item.title, query) + '</span>';
                        if (item.extra_info) {
                            html += '      <span class="gs-item-extra">' + self.highlightText(item.extra_info, query) + '</span>';
                        }
                        if (item.date) {
                            html += '      <span class="gs-item-date"><i class="fa fa-calendar-o"></i> ' + item.date + '</span>';
                        }
                        html += '    </div>';

                        html += '    <div class="gs-item-row-secondary">';
                        html += '      <span class="gs-item-subtitle">' + self.highlightText(item.subtitle, query) + '</span>';
                        html += '    </div>';
                        html += '  </div>';

                        // Right Status & Chevron
                        html += '  <div class="gs-item-actions">';
                        if (item.badge) {
                            html += '    <span class="gs-status-pill ' + item.badge_class + '">' + item.badge + '</span>';
                        }
                        html += '    <i class="fa fa-angle-right gs-item-arrow"></i>';
                        html += '  </div>';

                        html += '</a>';
                        itemGlobalIndex++;
                    });

                    html += '  </div>';
                    html += '</div>';
                }
            });

            this.totalVisibleItems = itemGlobalIndex;
            this.selectedIndex = itemGlobalIndex > 0 ? 0 : -1;

            if (totalCount === 0) {
                html = '<div class="gs-empty-state">';
                html += '  <div class="gs-empty-icon"><i class="fa fa-search"></i></div>';
                html += '  <div class="gs-empty-title">"' + $('<div>').text(query).html() + '" ile eşleşen kayıt bulunamadı</div>';
                html += '  <div class="gs-empty-subtitle">Farklı bir anahtar kelime, numara veya müşteri adı deneyebilirsiniz.</div>';
                html += '</div>';
            }

            this.resultsContainer.html(html);
            this.totalCountBadge.text(data.counts ? (cat === 'all' ? data.counts.all : (data.counts[cat] || 0)) : totalCount);
        },

        renderInitialSuggestions: function () {
            var html = '<div class="gs-suggestions-wrap">';
            html += '  <div class="gs-suggestions-header">Hızlı Modül Sayfaları</div>';
            html += '  <div class="gs-suggestions-grid">';
            html += '    <a href="index.php?p=offers/list" class="gs-suggestion-card"><i class="fa fa-file-text-o text-purple"></i><span>Teklifler</span></a>';
            html += '    <a href="index.php?p=products/list" class="gs-suggestion-card"><i class="fa fa-cube text-amber"></i><span>Ürünler</span></a>';
            html += '    <a href="index.php?p=customers/list" class="gs-suggestion-card"><i class="fa fa-building-o text-blue"></i><span>Firmalar</span></a>';
            html += '    <a href="index.php?p=service/list" class="gs-suggestion-card"><i class="fa fa-wrench text-emerald"></i><span>Servisler</span></a>';
            html += '    <a href="index.php?p=kesif/list" class="gs-suggestion-card"><i class="fa fa-search-plus text-cyan"></i><span>Keşifler</span></a>';
            html += '    <a href="index.php?p=reports/reports" class="gs-suggestion-card"><i class="fa fa-file-text text-indigo"></i><span>Raporlar</span></a>';
            html += '  </div>';
            html += '</div>';

            this.resultsContainer.html(html);
            this.updateCounters({ all: 0, offers: 0, products: 0, customers: 0, services: 0, kesifler: 0, reports: 0 });
            this.totalVisibleItems = 0;
            this.selectedIndex = -1;
            this.openDropdown();
        },

        moveSelection: function (step) {
            if (this.totalVisibleItems <= 0) return;

            var newIndex = this.selectedIndex + step;
            if (newIndex < 0) {
                newIndex = this.totalVisibleItems - 1;
            } else if (newIndex >= this.totalVisibleItems) {
                newIndex = 0;
            }

            this.setSelectedIndex(newIndex);
        },

        setSelectedIndex: function (index) {
            this.selectedIndex = index;
            var items = this.resultsContainer.find('.gs-result-item');
            items.removeClass('active');

            var target = items.filter('[data-index="' + index + '"]');
            if (target.length) {
                target.addClass('active');
                // Auto scroll into view
                var container = this.resultsContainer;
                var targetTop = target.position().top;
                var targetBottom = targetTop + target.outerHeight();
                var containerHeight = container.height();

                if (targetBottom > containerHeight) {
                    container.scrollTop(container.scrollTop() + (targetBottom - containerHeight) + 10);
                } else if (targetTop < 0) {
                    container.scrollTop(container.scrollTop() + targetTop - 10);
                }
            }
        },

        activateSelected: function () {
            if (this.selectedIndex >= 0) {
                var target = this.resultsContainer.find('.gs-result-item[data-index="' + this.selectedIndex + '"]');
                if (target.length) {
                    var itemType = target.data('type');
                    var itemId = target.data('id');

                    if (itemType === 'kesif' && typeof window.openKesifDetailModal === 'function' && window.location.href.indexOf('p=kesif/list') !== -1) {
                        this.closeDropdown();
                        this.input.blur();
                        window.openKesifDetailModal(itemId);
                        if (window.history && window.history.pushState) {
                            window.history.pushState({}, '', target.attr('href'));
                        }
                        return;
                    }

                    if (target.attr('href')) {
                        window.location.href = target.attr('href');
                    }
                }
            }
        }
    };

    $(document).ready(function () {
        GlobalSearch.init();
    });

})(jQuery);
