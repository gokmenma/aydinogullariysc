
let url = "pages/1/products/api.php";
$(document).on("click", "#submitButton", function () {
  var id = $("#id").val();
  var form = $("#productForm");
  $(".selectpicker").selectpicker("refresh");
  form.validate({
    rules: {
      urunAdi: {
        required: true
      },
      price: {
        required: true,
        number: true
      },
      description: {
        required: true
      }
    },
    messages: {
      urunAdi: {
        required: "Ürün adı giriniz"
      },
      price: {
        required: "Please enter a price",
        number: "Please enter a valid number"
      },
      description: {
        required: "Please enter a description"
      }
    },
    errorElement: "em",
    errorPlacement: function (error, element) {
      if (element.hasClass("selectpicker")) {
        element.next().addClass("is-invalid");
      } else {
        element.addClass("is-invalid");
      }
    },
    success: function (label, element) {
      if ($(element).hasClass("selectpicker")) {
        $(element).next().removeClass("is-invalid");
      } else {
        $(element).removeClass("is-invalid");
      }
    }
  });

  if (!form.valid()) {
    return;
  }

  var formData = new FormData(form[0]);
  formData.append("id", id);
  formData.append("action", "save-product");

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }

  fetch(url, {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      title = data.status == "success" ? "Başarılı" : "Hata";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status
      });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

//Selectpicker da değişiklik olunca formu validate et
$(document).on("change", ".selectpicker", function () {
  $(this).valid();
});

$(document).on("click", ".product-delete", function () {
  let id = $(this).attr("data-id");
  let product_name = $(this).attr("data-name");

  let formData = new FormData();
  formData.append("id", id);
  formData.append("action", "delete-product");

  swal
    .fire({
      title: "Emin misiniz?",
      html: product_name + " <br> adlı ürün silinecektir!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Evet",
      cancelButtonText: "Hayır",
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6"
    })
    .then((result) => {
      if (result.isConfirmed) {
        fetch(url, {
          method: "POST",
          body: formData
        })
          .then((response) => response.json())
          .then((data) => {
            title = data.status == "success" ? "Başarılı" : "Hata";
            Swal.fire({
              title: title,
              text: data.message,
              icon: data.status
            });
            if ($.fn.DataTable.isDataTable('#tblProducts')) {
              $('#tblProducts').DataTable().ajax.reload(null, false);
            }
          })
          .catch((error) => {
            console.error("Error:", error);
          });
      }
    });
});

$(document).ready(function () {
  // KPI Summary Section Toggle & LocalStorage
  $('html').removeClass('kpi-products-collapsed-early');
  var isKpiCollapsed = localStorage.getItem('aydinogullari_kpi_products_collapsed') === 'true';
  if (isKpiCollapsed) {
    $('#kpiSummarySection').addClass('is-collapsed');
    $('#toggleKpiSummary i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
  }

  $(document).on('click', '#toggleKpiSummary', function () {
    var $kpi = $('#kpiSummarySection');
    var willCollapse = !$kpi.hasClass('is-collapsed');

    if (willCollapse) {
      $kpi.addClass('is-collapsed');
      $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
      localStorage.setItem('aydinogullari_kpi_products_collapsed', 'true');
    } else {
      $kpi.removeClass('is-collapsed');
      $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
      localStorage.setItem('aydinogullari_kpi_products_collapsed', 'false');
    }
  });

  if ($("#tblProducts").length) {
    let dtTable = $("#tblProducts").DataTable({
      processing: true,
      serverSide: true,
      autoWidth: false,
      responsive: false,
      ajax: {
        url: "api/products_datatables.php",
        type: "GET"
      },
      columns: [
        { data: 0, width: "38px", orderable: false, className: "text-center" }, // Sıra
        { data: 1, width: "105px" }, // Stok Kodu
        { data: 2 }, // Ürün/Hizmet Adı
        { data: 3, width: "75px", className: "text-center" }, // Birimi
        { data: 4, width: "95px", className: "text-right text-nowrap" }, // Alış Fiyatı
        { data: 5, width: "105px", className: "text-right text-nowrap" }, // Satış Fiyatı
        { data: 6, width: "130px" }, // Açıklama
        { data: 7, width: "85px", className: "text-center text-nowrap" }, // Kayıt Tarihi
        { data: 8, width: "85px", orderable: false, className: "text-center text-nowrap" } // İşlem
      ],
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      language: {
        url: "include/js/tr.json",
        processing: '<div class="d-flex align-items-center justify-content-center p-3 text-primary"><i class="fa fa-spinner fa-spin fa-2x mr-2"></i><span class="font-weight-bold">Yükleniyor...</span></div>'
      },
      order: [[1, "asc"]],
      orderCellsTop: true,
      initComplete: function () {
        if (window.App && window.App.TableFilter) {
          App.TableFilter.attachToTable(this.api().table().node());
          App.TableFilter.relocateSearchInput(this.api().table().node());
        }
      }
    });

    // Tabloyu Yenile Butonu
    $("#btnRefreshProducts").on("click", function () {
      let $btn = $(this);
      let $icon = $btn.find("i");
      $icon.addClass("fa-spin");
      dtTable.ajax.reload(function () {
        setTimeout(function () {
          $icon.removeClass("fa-spin");
        }, 400);
      }, false);
    });

    // Excel Export Butonu
    $("#btnExportProducts").on("click", function () {
      let $btn = $(this);
      let originalHtml = $btn.html();
      $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Hazırlanıyor...');

      // Tüm filtrelenmiş verileri çek
      let dtParams = dtTable.ajax.params();
      let exportParams = $.extend({}, dtParams, {
        start: 0,
        length: 5000 // Maksimum dışa aktarılacak kayıt sayısı
      });

      $.ajax({
        url: "api/products_datatables.php",
        type: "GET",
        data: exportParams,
        dataType: "json",
        success: function (res) {
          $btn.prop("disabled", false).html(originalHtml);
          if (!res || !res.data || res.data.length === 0) {
            Swal.fire({
              icon: "info",
              title: "Bilgi",
              text: "Dışa aktarılacak veri bulunamadı."
            });
            return;
          }

          // HTML taglerini temizleyen yardımcı
          function stripHtml(html) {
            let tmp = document.createElement("DIV");
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || "";
          }

          // Excel verisini hazırla
          let excelData = [];
          // Başlık satırı
          excelData.push([
            "Sıra",
            "Stok Kodu",
            "Ürün/Hizmet Adı",
            "Birimi",
            "Alış Fiyatı",
            "Satış Fiyatı",
            "Açıklama",
            "Kayıt Tarihi"
          ]);

          res.data.forEach(function (row, idx) {
            excelData.push([
              idx + 1,
              stripHtml(row[1]),
              stripHtml(row[2]),
              stripHtml(row[3]),
              stripHtml(row[4]),
              stripHtml(row[5]),
              stripHtml(row[6]),
              stripHtml(row[7])
            ]);
          });

          // XLSX ile indir
          if (typeof XLSX !== "undefined") {
            let ws = XLSX.utils.aoa_to_sheet(excelData);
            let wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Ürünler");
            let today = new Date().toISOString().slice(0, 10);
            XLSX.writeFile(wb, "Urun_Hizmet_Listesi_" + today + ".xlsx");
          } else {
            // Fallback CSV
            let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + excelData.map(e => e.map(cell => '"' + String(cell).replace(/"/g, '""') + '"').join(";")).join("\n");
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "Urun_Hizmet_Listesi.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          }
        },
        error: function () {
          $btn.prop("disabled", false).html(originalHtml);
          Swal.fire({
            icon: "error",
            title: "Hata",
            text: "Excel verisi indirilirken bir hata oluştu."
          });
        }
      });
    });

    // Tabloda Sağ Tık (Context Menu) İşlemleri
    $(document).on('contextmenu', '#tblProducts tbody tr', function (e) {
      if ($(this).find('td').length <= 1) return;

      e.preventDefault();

      var $tr = $(this);
      $('#tblProducts tbody tr').removeClass('context-menu-active');
      $tr.addClass('context-menu-active');

      var productSku = $tr.find('td:nth-child(2)').text().trim();
      var productName = $tr.find('td:nth-child(3)').text().trim() || 'Ürün İşlemleri';
      var headerTitle = productSku && productSku !== '-' ? (productSku + ' - ' + productName) : productName;
      var $actionTd = $tr.find('td:last-child');

      var menuHtml = '<div class="cm-header"><i class="fa fa-cube mr-1 text-primary"></i> ' + $('<div>').text(headerTitle).html() + '</div>';

      // 1. Düzenle Butonu
      var $editBtn = $actionTd.find('a[data-tooltip="Düzenle"], a.btn-outline-primary');
      if ($editBtn.length) {
        menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-primary mr-2"></i> Düzenle / İncele</a>';
      }

      // 2. Dropdown içindeki elemanlar (Stok Hareketleri vb.)
      var $dropdownItems = $actionTd.find('.dropdown-menu .dropdown-item');
      if ($dropdownItems.length) {
        $dropdownItems.each(function () {
          var $item = $(this);
          var href = $item.attr('href') || '#';
          var target = $item.attr('target') ? ' target="' + $item.attr('target') + '"' : '';
          var text = $item.html();
          var dataId = $item.attr('data-id') ? ' data-id="' + $item.attr('data-id') + '"' : '';
          var classAttr = $item.attr('class') || '';

          if (text.indexOf('Düzenle') === -1) {
            menuHtml += '<a href="' + href + '"' + target + dataId + ' class="' + classAttr + '">' + text + '</a>';
          }
        });
      }

      // 3. Sil Butonu
      var $deleteBtn = $actionTd.find('.product-delete');
      if ($deleteBtn.length) {
        menuHtml += '<div class="cm-divider"></div>';
        var delId = $deleteBtn.attr('data-id');
        var delName = $deleteBtn.attr('data-name') || productName;
        menuHtml += '<button type="button" class="product-delete cm-danger" data-id="' + delId + '" data-name="' + $('<div>').text(delName).html() + '"><i class="fa fa-trash-o text-danger mr-2"></i> Ürünü Sil</button>';
      }

      var $contextMenu = $('#customContextMenu');
      if (!$contextMenu.length) {
        $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
      }

      $contextMenu.html(menuHtml);

      var mouseX = e.clientX;
      var mouseY = e.clientY;

      $contextMenu.css({ display: 'block', visibility: 'hidden' });
      var menuWidth = $contextMenu.outerWidth();
      var menuHeight = $contextMenu.outerHeight();
      var windowWidth = $(window).width();
      var windowHeight = $(window).height();

      if (mouseX + menuWidth > windowWidth) {
        mouseX = windowWidth - menuWidth - 10;
      }
      if (mouseY + menuHeight > windowHeight) {
        mouseY = windowHeight - menuHeight - 10;
      }

      $contextMenu.css({
        top: mouseY + 'px',
        left: mouseX + 'px',
        visibility: 'visible',
        opacity: '1'
      });
    });

    // Menü dışına tıklanınca veya sayfayı kaydırınca kapat
    $(document).on('click scroll', function (e) {
      if (!$(e.target).closest('#customContextMenu').length) {
        $('#customContextMenu').hide();
        $('#tblProducts tbody tr').removeClass('context-menu-active');
      }
    });

    // Menü seçilince kapat
    $(document).on('click', '#customContextMenu a, #customContextMenu button', function () {
      $('#customContextMenu').hide();
      $('#tblProducts tbody tr').removeClass('context-menu-active');
    });

    // ESC ile kapat
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        $('#customContextMenu').hide();
        $('#tblProducts tbody tr').removeClass('context-menu-active');
      }
    });
  }
});

