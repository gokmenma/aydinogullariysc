/**
 * ProductPicker - Merkezi Ürün Arama, Canlı Tamamlama ve Toplu Ürün Seçim Yöneticisi
 * Aydınoğulları YSC
 */
(function (window, $) {
  'use strict';

  var ProductPicker = {
    cache: [],
    searchTimer: null,
    currentConfig: null,

    defaultConfig: {
      tableSelector: '#tProduct, #sortable, #kalem_ekle',
      rowSelector: 'tr',
      fields: {
        title: 'input[name="urunAdi[]"], .urunAdi, .urunAdi-input',
        stock: 'input[name="stokKodu[]"], .stokKodu, .stokKodu-input',
        amount: 'input[name="amount[]"], .amount-input, .Adet',
        unit: 'select[name="unit[]"]',
        saleprice: 'input[name="saleprice[]"], .saleprice-input',
        buyprice: 'input[name="buyprice[]"], .buyprice-input',
        price: 'input[name="price[]"], .price-input, .BirimFiyat',
        salecur: 'select[name="salecur[]"]',
        buycur: 'select[name="buycur[]"]',
        currency: 'select[name="currency[]"]',
        description: 'input[name="rowdescription[]"]'
      },
      onSelect: null,
      onAddRow: null
    },

    init: function (options) {
      this.currentConfig = $.extend(true, {}, this.defaultConfig, options || {});
      this.bindEvents();
    },

    trNormalize: function (str) {
      if (!str) return '';
      return String(str)
        .replace(/İ/g, 'i')
        .replace(/I/g, 'i')
        .replace(/ı/g, 'i')
        .replace(/Ğ/g, 'g')
        .replace(/ğ/g, 'g')
        .replace(/Ü/g, 'u')
        .replace(/ü/g, 'u')
        .replace(/Ş/g, 's')
        .replace(/ş/g, 's')
        .replace(/Ö/g, 'o')
        .replace(/ö/g, 'o')
        .replace(/Ç/g, 'c')
        .replace(/ç/g, 'c')
        .toLowerCase()
        .trim();
    },

    getApiUrl: function () {
      if (window.PRODUCT_PICKER_API) {
        return window.PRODUCT_PICKER_API;
      }
      return 'api/search_products.php';
    },

    getFloatingDropdown: function () {
      var $dropdown = $('#globalProductAutocompleteDropdown');
      if (!$dropdown.length) {
        $dropdown = $(
          '<div id="globalProductAutocompleteDropdown" class="product-autocomplete-results" style="display:none; position:fixed; z-index:9999999;"></div>'
        ).appendTo('body');
      }
      return $dropdown;
    },

    positionFloatingDropdown: function ($input) {
      var $dropdown = this.getFloatingDropdown();
      if (!$input || !$input.length || !$input.is(':visible')) {
        $dropdown.hide();
        return;
      }

      var rect = $input[0].getBoundingClientRect();
      var dropdownWidth = Math.max(rect.width, 380);
      var left = rect.left;

      if (left + dropdownWidth > window.innerWidth - 12) {
        left = window.innerWidth - dropdownWidth - 12;
      }
      if (left < 10) left = 10;

      var spaceBelow = window.innerHeight - rect.bottom;
      var spaceAbove = rect.top;

      if (spaceBelow < 150 && spaceAbove > spaceBelow) {
        $dropdown.css({
          top: 'auto',
          bottom: window.innerHeight - rect.top + 4 + 'px',
          left: left + 'px',
          width: dropdownWidth + 'px',
          maxHeight: Math.min(280, Math.max(100, spaceAbove - 16)) + 'px',
          overflowY: 'auto'
        });
      } else {
        $dropdown.css({
          top: rect.bottom + 4 + 'px',
          bottom: 'auto',
          left: left + 'px',
          width: dropdownWidth + 'px',
          maxHeight: Math.min(280, Math.max(120, spaceBelow - 16)) + 'px',
          overflowY: 'auto'
        });
      }
    },

    fetchAndRender: function ($input) {
      var self = this;
      var term = $.trim($input.val());
      var apiUrl = self.getApiUrl() + '?q=' + encodeURIComponent(term) + '&limit=25';

      fetch(apiUrl)
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if ($input.is(':visible')) {
            self.renderProductResults($input, data.results || []);
          }
        })
        .catch(function (err) {
          console.error('Ürün arama hatası:', err);
        });
    },

    renderProductResults: function ($input, products) {
      var $dropdown = this.getFloatingDropdown();
      $dropdown.data('activeInput', $input);

      if (!products || products.length === 0) {
        $dropdown
          .html(
            '<div class="p-3 text-center text-muted font-12"><i class="fa fa-info-circle mr-1"></i> Eşleşen ürün bulunamadı. Serbest metin olarak devam edebilirsiniz.</div>'
          )
          .show();
        this.positionFloatingDropdown($input);
        return;
      }

      var html = '';
      $.each(products, function (i, p) {
        var stockCode = p.stock_code ? p.stock_code : '-';
        var salePrice = p.sale_price ? parseFloat(p.sale_price).toFixed(2) : '0.00';
        var buyPrice = p.buy_price ? parseFloat(p.buy_price).toFixed(2) : '0.00';
        var unit = p.unit || 'Adet';
        var saleCur = p.sale_cur || 'TRY';
        var buyCur = p.buy_cur || 'TRY';

        html +=
          '<div class="product-autocomplete-item" ' +
          'data-title="' + $('<div>').text(p.title).html() + '" ' +
          'data-stock="' + $('<div>').text(stockCode).html() + '" ' +
          'data-saleprice="' + salePrice + '" ' +
          'data-salecur="' + saleCur + '" ' +
          'data-buyprice="' + buyPrice + '" ' +
          'data-buycur="' + buyCur + '" ' +
          'data-unit="' + $('<div>').text(unit).html() + '">' +
          '<div class="d-flex justify-content-between align-items-center mb-1">' +
          '<div class="product-item-title"><i class="fa fa-cube mr-1 text-primary"></i> ' + $('<div>').text(p.title).html() + '</div>' +
          (stockCode !== '-' ? '<span class="product-item-stock">' + stockCode + '</span>' : '') +
          '</div>' +
          '<div class="d-flex align-items-center justify-content-between product-item-meta">' +
          '<span>Birim: <b>' + unit + '</b></span>' +
          '<span>Satış: <b class="text-success">' + salePrice + ' ' + saleCur + '</b></span>' +
          '<span>Alış: <b class="text-secondary">' + buyPrice + ' ' + buyCur + '</b></span>' +
          '</div>' +
          '</div>';
      });

      $dropdown.html(html).show();
      this.positionFloatingDropdown($input);
    },

    selectProductItem: function ($row, data) {
      if (!$row || !$row.length) return;

      var cfg = this.currentConfig || this.defaultConfig;
      var f = cfg.fields;

      // 1. Ürün Adı
      var $title = $row.find(f.title);
      if ($title.length) {
        $title.val(data.title);
      }

      // 2. Stok Kodu
      var $stock = $row.find(f.stock);
      if ($stock.length && data.stock && data.stock !== '-') {
        $stock.val(data.stock);
      }

      // 3. Fiyat Alanları
      var $saleprice = $row.find(f.saleprice);
      var $buyprice = $row.find(f.buyprice);
      var $singlePrice = $row.find(f.price);

      if ($saleprice.length && data.saleprice && parseFloat(data.saleprice) > 0) {
        $saleprice.val(data.saleprice);
      }
      if ($buyprice.length && data.buyprice && parseFloat(data.buyprice) > 0) {
        $buyprice.val(data.buyprice);
      }

      // Tek fiyat alanı olan sayfalarda (Fiyat talebi, Satın alma vb.)
      if ($singlePrice.length) {
        var pVal = (data.saleprice && parseFloat(data.saleprice) > 0) ? data.saleprice : (data.buyprice || '');
        if (pVal && parseFloat(pVal) > 0) {
          $singlePrice.val(pVal);
        }
      }

      // 4. Birim
      if (data.unit) {
        var $unit = $row.find(f.unit);
        if ($unit.length) {
          $unit.val(data.unit);
          if ($.fn.selectpicker) $unit.selectpicker('refresh');
          if ($.fn.select2) $unit.trigger('change');
        }
      }

      // 5. Para Birimi
      if (data.salecur) {
        var $salecur = $row.find(f.salecur);
        if ($salecur.length) {
          $salecur.val(data.salecur);
          if ($.fn.selectpicker) $salecur.selectpicker('refresh');
          if ($.fn.select2) $salecur.trigger('change');
        }
      }
      if (data.buycur) {
        var $buycur = $row.find(f.buycur);
        if ($buycur.length) {
          $buycur.val(data.buycur);
          if ($.fn.selectpicker) $buycur.selectpicker('refresh');
          if ($.fn.select2) $buycur.trigger('change');
        }
      }
      if (data.salecur || data.buycur) {
        var $cur = $row.find(f.currency);
        if ($cur.length) {
          $cur.val(data.salecur || data.buycur);
          if ($.fn.selectpicker) $cur.selectpicker('refresh');
          if ($.fn.select2) $cur.trigger('change');
        }
      }

      // 6. Miktar (boşsa 1 yap)
      var $amount = $row.find(f.amount);
      if ($amount.length && (!parseFloat($amount.val()) || parseFloat($amount.val()) <= 0)) {
        $amount.val(1);
      }

      this.getFloatingDropdown().hide().empty();

      // Toplam hesaplamalarını tetikle
      if (typeof cfg.onSelect === 'function') {
        cfg.onSelect($row, data);
      } else {
        if (typeof window.updateAltToplam === 'function') {
          window.updateAltToplam();
        }
        if (typeof window.updateToplamPurchase === 'function') {
          window.updateToplamPurchase();
        }
      }

      // Miktara odaklan
      if ($amount.length) {
        setTimeout(function () {
          $amount.focus().select();
        }, 50);
      }
    },

    loadMultiProductList: function () {
      var self = this;
      var $tbody = $('#multiProductListBody');
      $tbody.html(
        '<tr><td colspan="7" class="text-center py-4 text-muted">' +
        '<i class="fa fa-spinner fa-spin fa-2x mb-2 text-primary"></i><div>Ürünler yükleniyor...</div>' +
        '</td></tr>'
      );

      var apiUrl = self.getApiUrl() + '?limit=1000';
      fetch(apiUrl)
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          self.cache = data.results || [];
          self.renderMultiProductTable(self.cache);
        })
        .catch(function (err) {
          $tbody.html(
            '<tr><td colspan="7" class="text-center py-3 text-danger">Ürünler yüklenirken hata oluştu.</td></tr>'
          );
        });
    },

    renderMultiProductTable: function (products) {
      var $tbody = $('#multiProductListBody');
      if (!products || products.length === 0) {
        $tbody.html(
          '<tr><td colspan="7" class="text-center py-4 text-muted">Kriterlere uygun ürün bulunamadı.</td></tr>'
        );
        return;
      }

      var html = '';
      $.each(products, function (i, p) {
        var stockCode = p.stock_code || '-';
        var salePrice = p.sale_price ? parseFloat(p.sale_price).toFixed(2) : '0.00';
        var buyPrice = p.buy_price ? parseFloat(p.buy_price).toFixed(2) : '0.00';
        var unit = p.unit || 'Adet';
        var saleCur = p.sale_cur || 'TRY';
        var buyCur = p.buy_cur || 'TRY';

        html +=
          '<tr class="multi-product-row" data-id="' + p.id + '">' +
          '<td class="text-center" style="vertical-align: middle;">' +
          '<input type="checkbox" class="multi-product-checkbox" style="width: 16px; height: 16px; cursor: pointer;">' +
          '</td>' +
          '<td style="vertical-align: middle;"><span class="badge badge-light border">' + stockCode + '</span></td>' +
          '<td style="vertical-align: middle;">' +
          '<div class="font-weight-bold text-dark">' + $('<div>').text(p.title).html() + '</div>' +
          '</td>' +
          '<td class="text-center" style="vertical-align: middle;">' + unit + '</td>' +
          '<td class="text-right font-weight-bold text-success" style="vertical-align: middle;">' + salePrice + ' ' + saleCur + '</td>' +
          '<td class="text-right text-muted" style="vertical-align: middle;">' + buyPrice + ' ' + buyCur + '</td>' +
          '<td class="text-center" style="vertical-align: middle;">' +
          '<div class="d-flex align-items-center justify-content-center" style="gap: 4px;">' +
          '<button type="button" class="btn btn-sm btn-outline-secondary btn-qty-minus px-2 py-0" style="height: 28px; line-height: 1;">-</button>' +
          '<input type="number" step="any" min="1" value="1" class="form-control form-control-sm text-center multi-qty-input" style="width: 60px; height: 28px; font-weight: 600;">' +
          '<button type="button" class="btn btn-sm btn-outline-secondary btn-qty-plus px-2 py-0" style="height: 28px; line-height: 1;">+</button>' +
          '</div>' +
          '</td>' +
          '</tr>';
      });

      $tbody.html(html);
      this.updateMultiSelectedCounter();
    },

    updateMultiSelectedCounter: function () {
      var count = $('.multi-product-checkbox:checked').length;
      $('#multiSelectedCounter').text(count + ' Kalem Seçildi');
      var $btn = $('#btnAddSelectedProductsToOffer, #btnAddSelectedProductsToTable, .btn-add-selected-products');
      $btn.prop('disabled', count === 0);
      $btn.find('span').text('Seçilenleri Aktar (' + count + ')');
    },

    addSelectedProductsToTable: function () {
      var self = this;
      var $checked = $('.multi-product-checkbox:checked');
      if ($checked.length === 0) return;

      var selectedItems = [];
      $checked.each(function () {
        var $tr = $(this).closest('tr');
        var id = parseInt($tr.data('id'), 10);
        var qty = parseFloat($tr.find('.multi-qty-input').val()) || 1;
        var product = self.cache.find(function (p) {
          return parseInt(p.id, 10) === id;
        });
        if (product) {
          selectedItems.push({
            product: product,
            qty: qty
          });
        }
      });

      if (selectedItems.length === 0) return;

      var cfg = self.currentConfig || self.defaultConfig;
      var $table = $(cfg.tableSelector).first();
      var $tbody = $table.find('tbody');
      var $firstRow = $tbody.find('tr').first();

      var isFirstRowEmpty =
        $firstRow.length > 0 &&
        !$firstRow.find(cfg.fields.title).val() &&
        !$firstRow.find(cfg.fields.stock).val();

      var itemIndex = 0;
      if (isFirstRowEmpty) {
        var first = selectedItems[0];
        self.selectProductItem($firstRow, {
          title: first.product.title,
          stock: first.product.stock_code,
          saleprice: first.product.sale_price,
          salecur: first.product.sale_cur,
          buyprice: first.product.buy_price,
          buycur: first.product.buy_cur,
          unit: first.product.unit
        });
        $firstRow.find(cfg.fields.amount).val(first.qty);
        itemIndex = 1;
      }

      function addRemaining(idx) {
        if (idx >= selectedItems.length) {
          // Sıra numaralarını yeniden düzenle
          $tbody.find('tr').each(function (index) {
            $(this).find('input[name="satirno[]"]').val(index + 1);
            $(this).find('.row-index-label').text(index + 1);
            $(this).find('.app-item-number input').val(index + 1);
          });

          if (typeof window.updateAltToplam === 'function') {
            window.updateAltToplam();
          }
          if (typeof window.updateToplamPurchase === 'function') {
            window.updateToplamPurchase();
          }
          if (typeof window.updateRowIndexes === 'function') {
            window.updateRowIndexes();
          }

          $('#multiProductPickerModal').modal('hide');

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: 'Başarılı!',
              text: selectedItems.length + ' adet ürün listeye eklendi.',
              icon: 'success',
              timer: 1800,
              showConfirmButton: false
            });
          }
          return;
        }

        var item = selectedItems[idx];

        // Satır ekleme fonksiyonunu çağır
        if (typeof cfg.onAddRow === 'function') {
          cfg.onAddRow(item.product, item.qty, function () {
            addRemaining(idx + 1);
          });
          return;
        }

        if (typeof window.offerRowAdd === 'function') {
          var sayac = parseInt($('#rowNumberId').val(), 10) || ($tbody.find('tr').length + 1);
          window.offerRowAdd(sayac);
          $('#rowNumberId').val(sayac + 1);
          setTimeout(function () {
            var $newRow = $tbody.find('tr').last();
            self.selectProductItem($newRow, {
              title: item.product.title,
              stock: item.product.stock_code,
              saleprice: item.product.sale_price,
              salecur: item.product.sale_cur,
              buyprice: item.product.buy_price,
              buycur: item.product.buy_cur,
              unit: item.product.unit
            });
            $newRow.find(cfg.fields.amount).val(item.qty);
            addRemaining(idx + 1);
          }, 120);
        } else if (typeof window.priceRequestRowAdd === 'function') {
          var sayac = parseInt($('#rowNumberId').val(), 10) || ($tbody.find('tr').length + 1);
          window.priceRequestRowAdd(sayac).then ? window.priceRequestRowAdd(sayac).then(function() {
            var $newRow = $tbody.find('tr').last();
            self.selectProductItem($newRow, {
              title: item.product.title,
              stock: item.product.stock_code,
              saleprice: item.product.sale_price,
              salecur: item.product.sale_cur,
              buyprice: item.product.buy_price,
              buycur: item.product.buy_cur,
              unit: item.product.unit
            });
            $newRow.find(cfg.fields.amount).val(item.qty);
            addRemaining(idx + 1);
          }) : (function() {
            window.priceRequestRowAdd(sayac);
            setTimeout(function() {
              var $newRow = $tbody.find('tr').last();
              self.selectProductItem($newRow, {
                title: item.product.title,
                stock: item.product.stock_code,
                saleprice: item.product.sale_price,
                salecur: item.product.sale_cur,
                buyprice: item.product.buy_price,
                buycur: item.product.buy_cur,
                unit: item.product.unit
              });
              $newRow.find(cfg.fields.amount).val(item.qty);
              addRemaining(idx + 1);
            }, 180);
          })();
        } else if (typeof window.purchaseRowAdd === 'function') {
          var sayac = parseInt($('#rowNumberId').val(), 10) || ($tbody.find('tr').length + 1);
          var isDemand = $('#sortable').length > 0 || window.location.href.indexOf('demand') > -1;
          window.purchaseRowAdd(sayac, isDemand);
          $('#rowNumberId').val(sayac + 1);
          setTimeout(function () {
            var $newRow = $tbody.find('tr').last();
            self.selectProductItem($newRow, {
              title: item.product.title,
              stock: item.product.stock_code,
              saleprice: item.product.sale_price,
              salecur: item.product.sale_cur,
              buyprice: item.product.buy_price,
              buycur: item.product.buy_cur,
              unit: item.product.unit
            });
            $newRow.find(cfg.fields.amount).val(item.qty);
            addRemaining(idx + 1);
          }, 180);
        } else {
          $('#ekle, #addRow, #addRowFooter').first().trigger('click');
          setTimeout(function () {
            var $newRow = $tbody.find('tr').last();
            self.selectProductItem($newRow, {
              title: item.product.title,
              stock: item.product.stock_code,
              saleprice: item.product.sale_price,
              salecur: item.product.sale_cur,
              buyprice: item.product.buy_price,
              buycur: item.product.buy_cur,
              unit: item.product.unit
            });
            $newRow.find(cfg.fields.amount).val(item.qty);
            addRemaining(idx + 1);
          }, 150);
        }
      }

      addRemaining(itemIndex);
    },

    bindEvents: function () {
      var self = this;
      var inputSelector = '.urunAdi-input, .stokKodu-input, input[name="urunAdi[]"], input[name="stokKodu[]"], .urunAdi, .stokKodu';

      // 1. Canlı Arama: Focus ve Click olayları (tıklandığında ve odaklandığında anında açılır)
      $(document).off('focus.productpicker click.productpicker', inputSelector)
        .on('focus.productpicker click.productpicker', inputSelector, function (e) {
          var $input = $(this);
          
          if (e.type === 'focus') {
            setTimeout(function () {
              $input.select();
            }, 50);
          }

          clearTimeout(self.searchTimer);
          self.searchTimer = setTimeout(function () {
            self.fetchAndRender($input);
          }, 30);
        });

      // 2. Canlı Arama: Input (yazarken canlı filtreleme)
      $(document).off('input.productpicker', inputSelector)
        .on('input.productpicker', inputSelector, function (e) {
          var $input = $(this);
          clearTimeout(self.searchTimer);
          self.searchTimer = setTimeout(function () {
            self.fetchAndRender($input);
          }, 60);
        });

      // 3. Klavye ile Gezinme (Yukarı / Aşağı / Enter / Esc / Tab)
      $(document).off('keydown.productpicker', inputSelector)
        .on('keydown.productpicker', inputSelector, function (e) {
          var $input = $(this);
          var $dropdown = self.getFloatingDropdown();
          var $items = $dropdown.find('.product-autocomplete-item');

          if (!$dropdown.is(':visible') || $items.length === 0) {
            return;
          }

          var $active = $items.filter('.active');

          if (e.key === 'ArrowDown') {
            e.preventDefault();
            if ($active.length === 0 || $active.is(':last-child')) {
              $items.removeClass('active');
              $items.first().addClass('active');
            } else {
              $active.removeClass('active').next().addClass('active');
            }
            var $newActive = $items.filter('.active');
            if ($newActive.length) {
              $newActive[0].scrollIntoView({ block: 'nearest' });
            }
          } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if ($active.length === 0 || $active.is(':first-child')) {
              $items.removeClass('active');
              $items.last().addClass('active');
            } else {
              $active.removeClass('active').prev().addClass('active');
            }
            var $newActive = $items.filter('.active');
            if ($newActive.length) {
              $newActive[0].scrollIntoView({ block: 'nearest' });
            }
          } else if (e.key === 'Enter') {
            if ($active.length > 0) {
              e.preventDefault();
              var itemData = $active.data();
              self.selectProductItem($input.closest('tr'), itemData);
            }
          } else if (e.key === 'Escape' || e.key === 'Tab') {
            $dropdown.hide();
          }
        });

      // 4. Dropdown Tıklama & Mousedown (blur olmasını engeller)
      $(document).off('mousedown.productpicker', '#globalProductAutocompleteDropdown')
        .on('mousedown.productpicker', '#globalProductAutocompleteDropdown', function (e) {
          e.preventDefault();
        });

      $(document).off('click.productpicker', '#globalProductAutocompleteDropdown .product-autocomplete-item')
        .on('click.productpicker', '#globalProductAutocompleteDropdown .product-autocomplete-item', function (e) {
          e.preventDefault();
          var itemData = $(this).data();
          var $dropdown = self.getFloatingDropdown();
          var $input = $dropdown.data('activeInput');
          if ($input && $input.length) {
            self.selectProductItem($input.closest('tr'), itemData);
          }
          $dropdown.hide().empty();
        });

      // 5. Dışarı Tıklama
      $(document).off('click.productpicker_outside')
        .on('click.productpicker_outside', function (e) {
          if (!$(e.target).closest(inputSelector + ', #globalProductAutocompleteDropdown').length) {
            self.getFloatingDropdown().hide();
          }
        });

      // 6. Scroll ve Resize Senkronizasyonu
      $(window).off('scroll.productpicker resize.productpicker')
        .on('scroll.productpicker resize.productpicker', function () {
          var $dropdown = self.getFloatingDropdown();
          if ($dropdown.is(':visible')) {
            var $input = $dropdown.data('activeInput');
            if ($input && $input.length && $input.is(':visible')) {
              self.positionFloatingDropdown($input);
            } else {
              $dropdown.hide();
            }
          }
        });

      $('.hack2, .table-responsive, .multi-product-table-wrapper').off('scroll.productpicker')
        .on('scroll.productpicker', function () {
          var $dropdown = self.getFloatingDropdown();
          if ($dropdown.is(':visible')) {
            var $input = $dropdown.data('activeInput');
            if ($input && $input.length) {
              self.positionFloatingDropdown($input);
            }
          }
        });

      // 7. Toplu Ürün Modalı Açma
      $(document).off('click.productpicker_modal', '#btnOpenMultiProductModal, .btn-open-multi-product')
        .on('click.productpicker_modal', '#btnOpenMultiProductModal, .btn-open-multi-product', function (e) {
          e.preventDefault();
          $('#multiProductSearchInput').val('');
          $('#multiProductPickerModal').modal('show');
          if (self.cache.length === 0) {
            self.loadMultiProductList();
          } else {
            self.renderMultiProductTable(self.cache);
          }
        });

      // 8. Toplu Arama Filtreleme
      $(document).off('input.productpicker_search', '#multiProductSearchInput')
        .on('input.productpicker_search', '#multiProductSearchInput', function () {
          var rawTerm = $.trim($(this).val());
          if (!rawTerm) {
            self.renderMultiProductTable(self.cache);
            return;
          }

          var termNorm = self.trNormalize(rawTerm);
          var filtered = self.cache.filter(function (p) {
            var titleNorm = self.trNormalize(p.title);
            var codeNorm = self.trNormalize(p.stock_code);
            return titleNorm.indexOf(termNorm) > -1 || codeNorm.indexOf(termNorm) > -1;
          });

          self.renderMultiProductTable(filtered);
        });

      // 9. Checkbox ve Miktar Butonları
      $(document).off('change.productpicker_cb', '.multi-product-checkbox')
        .on('change.productpicker_cb', '.multi-product-checkbox', function () {
          $(this).closest('tr').toggleClass('table-active', $(this).is(':checked'));
          self.updateMultiSelectedCounter();
        });

      $(document).off('click.productpicker_qty', '.btn-qty-plus')
        .on('click.productpicker_qty', '.btn-qty-plus', function () {
          var $input = $(this).siblings('.multi-qty-input');
          var val = parseFloat($input.val()) || 1;
          $input.val(val + 1);
          var $cb = $(this).closest('tr').find('.multi-product-checkbox');
          if (!$cb.is(':checked')) {
            $cb.prop('checked', true).trigger('change');
          }
        });

      $(document).off('click.productpicker_qty_min', '.btn-qty-minus')
        .on('click.productpicker_qty_min', '.btn-qty-minus', function () {
          var $input = $(this).siblings('.multi-qty-input');
          var val = parseFloat($input.val()) || 1;
          if (val > 1) {
            $input.val(val - 1);
          }
        });

      $(document).off('click.productpicker_select_all', '#btnMultiSelectAll')
        .on('click.productpicker_select_all', '#btnMultiSelectAll', function () {
          $('.multi-product-checkbox').prop('checked', true).closest('tr').addClass('table-active');
          self.updateMultiSelectedCounter();
        });

      $(document).off('click.productpicker_clear_all', '#btnMultiClearAll')
        .on('click.productpicker_clear_all', '#btnMultiClearAll', function () {
          $('.multi-product-checkbox').prop('checked', false).closest('tr').removeClass('table-active');
          self.updateMultiSelectedCounter();
        });

      // 10. Seçilenleri Tabloya Aktar
      $(document).off('click.productpicker_add_selected', '#btnAddSelectedProductsToOffer, #btnAddSelectedProductsToTable, .btn-add-selected-products')
        .on('click.productpicker_add_selected', '#btnAddSelectedProductsToOffer, #btnAddSelectedProductsToTable, .btn-add-selected-products', function () {
          self.addSelectedProductsToTable();
        });

      // 11. Satır Klonlama (.btn-clone-row)
      $(document).off('click.productpicker_clone', '.btn-clone-row')
        .on('click.productpicker_clone', '.btn-clone-row', function (e) {
          e.preventDefault();
          var $row = $(this).closest('tr');
          var cfg = self.currentConfig || self.defaultConfig;
          var f = cfg.fields;

          var stokKodu = $row.find(f.stock).val() || '';
          var urunAdi = $row.find(f.title).val() || '';
          var amount = $row.find(f.amount).val() || '1';
          var unit = $row.find(f.unit).val() || '';
          var saleprice = $row.find(f.saleprice).val() || '';
          var salecur = $row.find(f.salecur).val() || 'TRY';
          var buyprice = $row.find(f.buyprice).val() || '';
          var buycur = $row.find(f.buycur).val() || 'TRY';
          var price = $row.find(f.price).val() || '';
          var currency = $row.find(f.currency).val() || 'TRY';
          var desc = $row.find(f.description).val() || '';

          var $tbody = $row.closest('tbody');

          function populateNewRow($newRow) {
            $newRow.find(f.stock).val(stokKodu);
            $newRow.find(f.title).val(urunAdi);
            $newRow.find(f.amount).val(amount);
            if ($newRow.find(f.saleprice).length) $newRow.find(f.saleprice).val(saleprice);
            if ($newRow.find(f.buyprice).length) $newRow.find(f.buyprice).val(buyprice);
            if ($newRow.find(f.price).length) $newRow.find(f.price).val(price || saleprice || buyprice);
            if ($newRow.find(f.description).length) $newRow.find(f.description).val(desc);

            if (unit && $newRow.find(f.unit).length) {
              $newRow.find(f.unit).val(unit);
            }
            if (salecur && $newRow.find(f.salecur).length) {
              $newRow.find(f.salecur).val(salecur);
            }
            if (buycur && $newRow.find(f.buycur).length) {
              $newRow.find(f.buycur).val(buycur);
            }
            if (currency && $newRow.find(f.currency).length) {
              $newRow.find(f.currency).val(currency);
            }

            if ($.fn.selectpicker) {
              $newRow.find('.selectpicker').selectpicker('refresh');
            }

            $tbody.find('tr').each(function (index) {
              $(this).find('input[name="satirno[]"]').val(index + 1);
              $(this).find('.row-index-label').text(index + 1);
              $(this).find('.app-item-number input').val(index + 1);
            });

            if (typeof window.updateAltToplam === 'function') {
              window.updateAltToplam();
            }
            if (typeof window.updateToplamPurchase === 'function') {
              window.updateToplamPurchase();
            }

            $newRow.find(f.amount).focus().select();
          }

          if (typeof window.offerRowAdd === 'function') {
            var sayac = parseInt($('#rowNumberId').val(), 10) || ($tbody.find('tr').length + 1);
            window.offerRowAdd(sayac);
            $('#rowNumberId').val(sayac + 1);
            setTimeout(function () {
              populateNewRow($tbody.find('tr').last());
            }, 150);
          } else if (typeof window.priceRequestRowAdd === 'function') {
            var sayac = parseInt($('#rowNumberId').val(), 10) || ($tbody.find('tr').length + 1);
            window.priceRequestRowAdd(sayac).then ? window.priceRequestRowAdd(sayac).then(function() {
              populateNewRow($tbody.find('tr').last());
            }) : (function() {
              window.priceRequestRowAdd(sayac);
              setTimeout(function() {
                populateNewRow($tbody.find('tr').last());
              }, 180);
            })();
          } else if (typeof window.purchaseRowAdd === 'function') {
            var sayac = parseInt($('#rowNumberId').val(), 10) || ($tbody.find('tr').length + 1);
            var isDemand = $('#sortable').length > 0 || window.location.href.indexOf('demand') > -1;
            window.purchaseRowAdd(sayac, isDemand);
            $('#rowNumberId').val(sayac + 1);
            setTimeout(function () {
              populateNewRow($tbody.find('tr').last());
            }, 180);
          } else {
            $('#ekle, #addRow, #addRowFooter').first().trigger('click');
            setTimeout(function () {
              populateNewRow($tbody.find('tr').last());
            }, 150);
          }
        });
    }
  };

  // Otomatik başlatma
  $(document).ready(function () {
    ProductPicker.init();
  });

  window.ProductPicker = ProductPicker;
})(window, jQuery);
