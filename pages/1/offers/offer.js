$(document).on("click", "#btn_save_offer", function () {
  //işlem yapılıncaya kadar butonu disable et
  $(this).prop("disabled", true);
  TeklifKaydet();
  //işlem bittikten sonra butonu tekrar aktif et
  $(this).prop("disabled", false);
});

function TeklifKaydet(autosave = false) {
  // WYSIWYG editor içeriklerini textarea'lara aktar
  $(".offerHeaderContent .wysihtml5-sandbox").each(function () {
    var html = $(this).contents().find("body").html();
    if (html !== undefined) {
      $("#offerHeaderContent textarea").val(html);
    }
  });
  $(".offerFooterContent .wysihtml5-sandbox").each(function () {
    var html = $(this).contents().find("body").html();
    if (html !== undefined) {
      $("#offerFooterContent textarea").val(html);
    }
  });

  var form = $("#myForm");
  var formData = new FormData(form[0]);
  if ($.fn.selectpicker && $(".selectpicker").length > 0) {
    $(".selectpicker").selectpicker("refresh");
  }
  form.validate({
    rules: {
      customers: {
        required: true
      },
      "urunAdi[]": {
        required: true
      }
    },
    messages: {
      customers: {
        required: "Müşteri seçimi yapınız"
      },
      "urunAdi[]": {
        required: "Ürün adı seçimi yapınız"
      }
    },
    errorPlacement: function (error, element) {
      if (element.hasClass("select2-hidden-accessible")) {
        element.next(".select2-container").addClass("is-invalid");
      } else if (element.hasClass("selectpicker")) {
        element.next().addClass("is-invalid");
      } else {
        element.addClass("is-invalid");
      }
    },
    success: function (label, element) {
      if ($(element).hasClass("select2-hidden-accessible")) {
        $(element).next(".select2-container").removeClass("is-invalid");
      } else if ($(element).hasClass("selectpicker")) {
        $(element).next().removeClass("is-invalid");
      } else {
        $(element).removeClass("is-invalid");
      }
    }
  });

  if (!form.valid()) {
    swal.fire({
      title: "Hata!",
      text: "Lütfen zorunlu alanları doldurunuz",
      icon: "error",
      confirmButtonText: "Tamam"
    });

    return;
  }

  formData.append("action", "saveOffer");

  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ", " + pair[1]);
  // }

  //preloader göster
  $("#preloader").fadeIn(200);

  fetch("App/api/offer.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      if (data.status == "success") {
        title = "Başarılı!";
      } else {
        title = "Hata!";
      }

      $("#preloader").fadeOut(200);
      if (autosave) return;
      swal
        .fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        })
        .then((result) => {
          if (result.isConfirmed) {
            location.reload();
          }
        });
    })
    .catch((error) => {
      console.error("There was a problem with the fetch operation:", error);
    });

}

// //1 dakikada bir otomatik kaydet
// setInterval(function () {
//   var id = $("#offer_id").val();
//   //Eğer id = 0 ise return et
//   if (id == 0) return;
//   TeklifKaydet(true);
// }, 60000);

//ctrl + s ile TeklifKaydet
$(document).on("keydown", function (e) {
  if (e.ctrlKey && e.key === "s") {
    e.preventDefault();
    TeklifKaydet();
  }
});




function updateAltToplam() {
  let tl_alt_toplam = $("#tl_alt_toplam");
  let dolar_alt_toplam = $("#dolar_alt_toplam");
  let euro_alt_toplam = $("#euro_alt_toplam");
  var table = $("#kalem_ekle");
  let rowTotalDollar = 0;
  let rowTotalDollarBuy = 0;

  let rowTotalEuro = 0;
  let rowTotalEuroBuy = 0;
  let rowTotalTL = 0;
  let rowTotalTLBuy = 0;
  let totalBuy = 0;
  let totalSale = 0;

  table.find("tr").each(function (index, element) {
    if (index > 0) {
      var row = $(element);

      // Para birimi alınır
      var currency = row.find("select[name='salecur[]']").val();

      //Alış para birimi alınır
      var buyCurrency = row.find("select[name='buycur[]']").val();

      //Miktar değeri alınır
      var amount = parseFloat(row.find("input[name='amount[]']").val()) || 0;

      //Satış fiyatı alınır
      var saleprice =
        parseFloat(row.find("input[name='saleprice[]']").val()) || 0;

      //Alış fiyatı alınır
      var buyprice =
        parseFloat(row.find("input[name='buyprice[]']").val()) || 0;

      //Toplam satış hesaplanır
      var total = amount * saleprice;

      //Toplam alış hesaplanır
      var totalBuy = amount * buyprice;

      //Toplam değeri yazdırılır
      row.find("input[name='total[]']").val(total.toFixed(2));

      // Para birimine göre toplamları hesapla
      if (currency === "USD") {
        rowTotalDollar += total;
      } else if (currency === "EUR") {
        rowTotalEuro += total;
      } else if (currency === "TRY") {
        rowTotalTL += total;
      }

      // Alış para birimine göre toplamları hesapla
      if (buyCurrency === "USD") {
        rowTotalDollarBuy += totalBuy;
      } else if (buyCurrency === "EUR") {
        rowTotalEuroBuy += totalBuy;
      } else if (buyCurrency === "TRY") {
        rowTotalTLBuy += totalBuy;
      }
    }
  });

  //toplam TL, USD, EUR hesaplamaları
  // Kur hesaplamaları


  let curDollar = parseFloat($("#cur-Dollar").val()) || 0;
  let curEuro = parseFloat($("#cur-Euro").val()) || 0;

  //Döviz kurlarına göre toplamların hesaplanması
  totalBuy =
    rowTotalDollarBuy * curDollar + rowTotalEuroBuy * curEuro + rowTotalTLBuy ||
    0;

  //Satış toplamı hesaplanması
  totalSale =
    rowTotalDollar * curDollar + rowTotalEuro * curEuro + rowTotalTL || 0;

  //Kâr hesaplamaları
  let profit = totalSale - totalBuy;

  //Kâr oranı hesaplamaları--    //alış fiyatı toplamı sıfır veya boş ise alış fiyatı toplamı sıfırla
  let alis_toplam = totalBuy == 0 ? 1 : totalBuy;
  let profitRate = (profit / alis_toplam) * 100;

  //ÖZET ALANLARI
  $("#buy-tl").text(formatCurrency(totalBuy));
  $("#buy-tl-input").val(totalBuy.toFixed(2));

  $("#sale-tl").text(totalSale.toFixed(2));
  $("#sale-tl-input").val(totalSale.toFixed(2));

  $("#profit-tl").text(formatCurrency(profit));
  $("#profit-rate").text(profitRate.toFixed(2) + "%");

  //ALIŞ TOPLAMLARI

  // console.log("Toplam USD:", rowTotalDollar, "Toplam EUR:", rowTotalEuro, "Toplam TL:", rowTotalTL);
  tl_alt_toplam.val(rowTotalTL.toFixed(2));
  dolar_alt_toplam.val(rowTotalDollar.toFixed(2));
  euro_alt_toplam.val(rowTotalEuro.toFixed(2));
  araToplam();

}

function araToplam() {
  let table = $("#tblAltToplam");

  // Değişkenlerin sayısal değerlere dönüştürülmesi
  let euro_alt_toplam = parseFloat($("#euro_alt_toplam").val()) || 0;
  let dolar_alt_toplam = parseFloat($("#dolar_alt_toplam").val()) || 0;
  let tl_alt_toplam = parseFloat($("#tl_alt_toplam").val()) || 0;
  let euro_iskonto = parseFloat($("#euro_iskonto").val()) || 0;
  let dolar_iskonto = parseFloat($("#dolar_iskonto").val()) || 0;
  let tl_iskonto = parseFloat($("#tl_iskonto").val()) || 0;

  // Toplamların hesaplanması
  $("#euro_ara_toplam").val((euro_alt_toplam - euro_iskonto).toFixed(2));
  $("#dolar_ara_toplam").val((dolar_alt_toplam - dolar_iskonto).toFixed(2));
  $("#tl_ara_toplam").val((tl_alt_toplam - tl_iskonto).toFixed(2));

  // KDV'li toplam hesaplanması
  let Kdv = parseFloat($("#Kdv").val()) || 0;

  let tl_kdv_tutari = (parseFloat($("#tl_ara_toplam").val()) * Kdv) / 100;
  let dolar_kdv_tutari = (parseFloat($("#dolar_ara_toplam").val()) * Kdv) / 100;
  let euro_kdv_tutari = (parseFloat($("#euro_ara_toplam").val()) * Kdv) / 100;

  $("#tl_kdv").val(tl_kdv_tutari.toFixed(2));
  $("#dolar_kdv").val(dolar_kdv_tutari.toFixed(2));
  $("#euro_kdv").val(euro_kdv_tutari.toFixed(2));

  $("#tl_kdvli_toplam").val(
    (parseFloat($("#tl_ara_toplam").val()) + tl_kdv_tutari).toFixed(2)
  );
  $("#dolar_kdvli_toplam").val(
    (parseFloat($("#dolar_ara_toplam").val()) + dolar_kdv_tutari).toFixed(2)
  );
  $("#euro_kdvli_toplam").val(
    (parseFloat($("#euro_ara_toplam").val()) + euro_kdv_tutari).toFixed(2)
  );

  // Kur hesaplamaları
  let curDollar = parseFloat($("#cur-Dollar").val()) || 0;
  let curEuro = parseFloat($("#cur-Euro").val()) || 0;

  let tl_toplam_karsilik =
    parseFloat($("#tl_kdvli_toplam").val()) +
    curDollar * parseFloat($("#dolar_kdvli_toplam").val()) +
    curEuro * parseFloat($("#euro_kdvli_toplam").val());

  //Kdv'siz, ara toplamın tl karşılığı

  let tl_ara_toplam_karsilik =
    parseFloat($("#tl_ara_toplam").val()) +
    curDollar * parseFloat($("#dolar_ara_toplam").val()) +
    curEuro * parseFloat($("#euro_ara_toplam").val());

  console.log("TL Ara Toplam KDV'siz:", tl_ara_toplam_karsilik);

  let alis_toplam = $("#buy-tl-input").val();
  let satis_toplam = tl_ara_toplam_karsilik;

  let kar = satis_toplam - alis_toplam;

  let kar_oran = (kar / alis_toplam) * 100;
  // console.log("Kar:", kar_oran);

  $("#profit-tl").text(formatCurrency(kar));
  $("#profit-rate").text(kar_oran.toFixed(2) + "%");

  // console.log("TL Toplam Karşılık:", tl_toplam_karsilik);
  $("#tl_toplam_karsilik").val(formatCurrency(tl_toplam_karsilik));
  $("#sale-tl").text(formatCurrency(tl_ara_toplam_karsilik));
  $("#sale-tl-input").val(tl_ara_toplam_karsilik.toFixed(2));

  if (typeof syncOfferTotalsDrawer === "function") {
    syncOfferTotalsDrawer();
  }
}

function formatCurrency(value) {
  return value
    .toFixed(2)
    .replace(".", ",")
    .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

$("table").on("input change", "tr input, tr select", function () {
  updateAltToplam();
});

$("#currency").change(function () {
  getCurrencyData().then(() => {
    updateAltToplam();
  }).catch((error) => {
    console.error("Döviz kuru alınırken bir hata oluştu:", error);
  });
});


// $("#cur-Euro").change(function () {
//   updateAltToplam();
// });
// $("#cur-Dollar").change(function () {
//   updateAltToplam();
// });

document.addEventListener("DOMContentLoaded", function () {
  getCurrencyData();
});

$("[id^='buycur'], [id^='salecur']").each(function () {
  $(this).on("change", function () {
    getCurrencyData();
  });
});

// $("#offer_date").on("input change", function () {
//   const selectedDate = $(this).val(); // Seçilen tarihi alır
//   console.log("Seçilen tarih:", selectedDate);
//   alert("Teklif tarihi değiştirildi: " + selectedDate);
//   updateAltToplam(); // Tarih değiştiğinde toplamları güncelle
// });

$(document).ready(function () {
  $("#offer_date").on("focusout", function () {
    const selectedDate = $(this).val(); // Seçilen tarihi alır
    if (selectedDate) {
      console.log("Seçilen tarih:", selectedDate);
      updateAltToplam(); // Tarih değiştiğinde toplamları güncelle
    } else {
      console.log("Tarih alanı boş.");
    }
  });
});

$("#offerHeader").change(function () {
  var content = $(this); // olay tetiklendiğinde 'this' kullanarak #offerHeader öğesini alıyoruz
  getOfferTemplate(content, "Header");
});

$("#offerFooter").change(function () {
  var content = $(this); // olay tetiklendiğinde 'this' kullanarak #offerHeader öğesini alıyoruz
  getOfferTemplate(content, "Footer");
});
//getOfferTemplate($('#offerHeader'), "Header");
//getOfferTemplate($('#offerFooter'), "Footer");

$("#servicebutton").on("click", function (event) {
  var offerstatu = $("#offerstatu").val();
  if (offerstatu != 2) {
    if (event) event.preventDefault();
    swal.fire({
      title: "Uyarı",
      text: "Sadece tamamlanan (onaylanan) tekliflere servis oluşturabilirsiniz",
      icon: "warning"
    });
  }
});

$(document).on("change select2:select", "#customers", function (e) {
  var data = (e && e.params) ? e.params.data : null;
  var $selected = $(this).find("option:selected");
  var author = (data && typeof data.yetkili !== "undefined") ? data.yetkili : ($selected.data("author") || "");
  var payPeriod = (data && (data.odemevadesi || data.payperiod)) ? (data.odemevadesi || data.payperiod) : ($selected.data("payperiod") || "");

  if (author && author !== "." && author !== "-") {
    $("#compAuths").val(author);
  } else {
    $("#compAuths").val("");
  }

  if (payPeriod && payPeriod !== "") {
    $("#payPeriod").val(payPeriod);
  } else {
    $("#payPeriod").val("");
  }

  if (typeof getcustomerInfo === "function" && $(this).val()) {
    getcustomerInfo(this);
  }
});

$(document).on("click", "#convert_to_try", function () {
  //sweetalert ile onay al
  swal
    .fire({
      title: "Uyarı!",
      text: "Ürünlerin para birimi TL'ye dönüştürmek istediğinize emin misiniz?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Evet",
      cancelButtonText: "Hayır",
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33"
    })
    .then((result) => {
      if (result.isConfirmed) {
        //onay verildiğinde
        var form = $("#myForm");
        var formData = new FormData(form[0]);
        formData.append("action", "convertToTry");

        //updateAltToplam();

        $("#preloader").fadeIn(200);
        fetch("App/api/offer.php", {
          method: "POST",
          body: formData
        })
          .then((response) => response.json())
          .then((data) => {
            console.log(data);
            if (data.status == "success") {
              title = "Başarılı!";
            } else {
              title = "Hata!";
            }
            $("#preloader").fadeOut(200);
            swal
              .fire({
                title: title,
                text: data.message,
                icon: data.status,
                confirmButtonText: "Tamam"
              })
              .then((result) => {
                if (result.isConfirmed) {
                  location.reload();
                }
              });
          })
          .catch((error) => {
            console.error(
              "There was a problem with the fetch operation:",
              error
            );
            $("#preloader").fadeOut(200);
          });
      }
    });
});

//Şablon Teklif yap inputa basınca
$(document).on("change", "#is_template", function () {
  //eğer seçili ise offerNumber id'sine sahip h5 elemanının değerini templateOfferNumber id'sine sahip inputun değerini al
  if ($(this).is(":checked")) {
    $("#offerNumberLabel").text($("#templateOfferNumber").val());
  } else {
    $("#offerNumberLabel").text($("#offerNumber").val());
  }
});

//Teklif kopyala
$(document).on("click", ".offer-copy", function () {
  swal
    .fire({
      title: "Emin misiniz?",
      text: "Teklif kopyalanacaktır! Bu işlem geri alınamaz!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Evet, Kopyala!",
      cancelButtonText: "İptal"
    })
    .then((result) => {
      if (result.isConfirmed) {
        var id = $(this).data("id");
        var formData = new FormData();
        formData.append("action", "copyOffer");
        formData.append("id", id);

        fetch("App/api/offer.php", {
          method: "POST",
          body: formData
        })
          .then((response) => response.json())
          .then((data) => {
            console.log(data);
            if (data.status == 200) {
              swal
                .fire("Kopyalandı!", "Teklif başarı ile kopyalandı.", "success")
                .then(() => {
                  location.reload();
                });
            } else {
              swal.fire(
                "Hata!",
                "Teklif kopyalanırken bir hata oluştu.",
                "error"
              );
            }
          });
      }
    });
});

// =============================================================================
// 1. SATIR İÇİ CANLI ÜRÜN ARAMA & OTOMATİK TAMAMLAMA (INLINE AUTOCOMPLETE)
// =============================================================================
var productSearchTimer = null;

function getFloatingDropdown() {
  var $dropdown = $('#globalProductAutocompleteDropdown');
  if (!$dropdown.length) {
    $dropdown = $('<div id="globalProductAutocompleteDropdown" class="product-autocomplete-results" style="display:none; position:fixed; z-index:9999999;"></div>').appendTo('body');
  }
  return $dropdown;
}

function positionFloatingDropdown($input) {
  var $dropdown = getFloatingDropdown();
  if (!$input || !$input.length || !$input.is(':visible')) {
    $dropdown.hide();
    return;
  }

  var rect = $input[0].getBoundingClientRect();
  var dropdownWidth = Math.max(rect.width, 380);
  var left = rect.left;

  // Sağdan taşmayı önle
  if (left + dropdownWidth > window.innerWidth - 12) {
    left = window.innerWidth - dropdownWidth - 12;
  }
  if (left < 10) left = 10;

  var spaceBelow = window.innerHeight - rect.bottom;
  var spaceAbove = rect.top;

  // Yalnızca altta çok az yer kaldığında (< 150px) ve üstte daha çok yer varsa yukarıda aç
  if (spaceBelow < 150 && spaceAbove > spaceBelow) {
    $dropdown.css({
      top: 'auto',
      bottom: (window.innerHeight - rect.top + 4) + 'px',
      left: left + 'px',
      width: dropdownWidth + 'px',
      maxHeight: Math.min(280, Math.max(100, spaceAbove - 16)) + 'px',
      overflowY: 'auto'
    });
  } else {
    $dropdown.css({
      top: (rect.bottom + 4) + 'px',
      bottom: 'auto',
      left: left + 'px',
      width: dropdownWidth + 'px',
      maxHeight: Math.min(280, Math.max(120, spaceBelow - 16)) + 'px',
      overflowY: 'auto'
    });
  }
}

function renderProductResults($input, products) {
  var $dropdown = getFloatingDropdown();
  $dropdown.data('activeInput', $input);

  if (!products || products.length === 0) {
    $dropdown.html(
      '<div class="p-3 text-center text-muted font-12"><i class="fa fa-info-circle mr-1"></i> Eşleşen ürün bulunamadı. Serbest metin olarak devam edebilirsiniz.</div>'
    ).show();
    positionFloatingDropdown($input);
    return;
  }

  var html = '';
  $.each(products, function(i, p) {
    var stockCode = p.stock_code ? p.stock_code : '-';
    var salePrice = p.sale_price ? parseFloat(p.sale_price).toFixed(2) : '0.00';
    var buyPrice = p.buy_price ? parseFloat(p.buy_price).toFixed(2) : '0.00';
    var unit = p.unit || 'Adet';
    var saleCur = p.sale_cur || 'TRY';
    var buyCur = p.buy_cur || 'TRY';

    html += '<div class="product-autocomplete-item" ' +
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
  positionFloatingDropdown($input);
}

function selectProductItem($row, data) {
  $row.find('.urunAdi-input').val(data.title);
  if (data.stock && data.stock !== '-') {
    $row.find('.stokKodu-input').val(data.stock);
  }
  if (data.saleprice && parseFloat(data.saleprice) > 0) {
    $row.find('.saleprice-input').val(data.saleprice);
  }
  if (data.buyprice && parseFloat(data.buyprice) > 0) {
    $row.find('.buyprice-input').val(data.buyprice);
  }

  // Birim seçimi
  if (data.unit) {
    var $unitSelect = $row.find('select[name="unit[]"]');
    $unitSelect.val(data.unit);
    if ($.fn.selectpicker) {
      $unitSelect.selectpicker('refresh');
    }
  }

  // Para birimleri
  if (data.salecur) {
    var $saleCur = $row.find('select[name="salecur[]"]');
    $saleCur.val(data.salecur);
    if ($.fn.selectpicker) {
      $saleCur.selectpicker('refresh');
    }
  }

  if (data.buycur) {
    var $buyCur = $row.find('select[name="buycur[]"]');
    $buyCur.val(data.buycur);
    if ($.fn.selectpicker) {
      $buyCur.selectpicker('refresh');
    }
  }

  // Miktar boşsa 1 yap
  var $amountInput = $row.find('.amount-input');
  if (!$amountInput.val() || parseFloat($amountInput.val()) <= 0) {
    $amountInput.val(1);
  }

  getFloatingDropdown().hide().empty();
  updateAltToplam();

  // Miktar kutusuna odaklan
  setTimeout(function() {
    $amountInput.focus().select();
  }, 50);
}

// Ürün adı veya stok kodu kutusuna yazıldığında anlık arama
$(document).on('input focus', '.urunAdi-input, .stokKodu-input', function(e) {
  var $input = $(this);
  var term = $.trim($input.val());

  clearTimeout(productSearchTimer);
  productSearchTimer = setTimeout(function() {
    fetch('api/search_products.php?q=' + encodeURIComponent(term) + '&limit=20')
      .then(function(res) { return res.json(); })
      .then(function(data) {
        renderProductResults($input, data.results || []);
      })
      .catch(function(err) {
        console.error('Ürün arama hatası:', err);
      });
  }, 100);
});

// Klavye ile listede gezinme (Yukarı / Aşağı / Enter / Esc)
$(document).on('keydown', '.urunAdi-input, .stokKodu-input', function(e) {
  var $input = $(this);
  var $dropdown = getFloatingDropdown();
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
    // Seçili öğeyi görünür kıl
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
      selectProductItem($input.closest('tr'), itemData);
    }
  } else if (e.key === 'Escape') {
    $dropdown.hide();
  }
});

// Arama sonucuna tıklama
$(document).on('click', '#globalProductAutocompleteDropdown .product-autocomplete-item', function(e) {
  e.preventDefault();
  var itemData = $(this).data();
  var $dropdown = getFloatingDropdown();
  var $input = $dropdown.data('activeInput');
  if ($input && $input.length) {
    selectProductItem($input.closest('tr'), itemData);
  }
  $dropdown.hide().empty();
});

// Dışarı tıklandığında açık arama kutusunu kapat
$(document).on('click', function(e) {
  if (!$(e.target).closest('.urunAdi-input, .stokKodu-input, #globalProductAutocompleteDropdown').length) {
    getFloatingDropdown().hide();
  }
});

// Kaydırma ve boyutlandırma durumunda dropdown pozisyonunu senkronize et
$(window).on('scroll resize', function() {
  var $dropdown = getFloatingDropdown();
  if ($dropdown.is(':visible')) {
    var $input = $dropdown.data('activeInput');
    if ($input && $input.length && $input.is(':visible')) {
      positionFloatingDropdown($input);
    } else {
      $dropdown.hide();
    }
  }
});

$('.hack2, .table-responsive').on('scroll', function() {
  var $dropdown = getFloatingDropdown();
  if ($dropdown.is(':visible')) {
    var $input = $dropdown.data('activeInput');
    if ($input && $input.length) {
      positionFloatingDropdown($input);
    }
  }
});

// =============================================================================
// 2. SATIR KLONLAMA (ÇOĞALTMA) İŞLEMİ
// =============================================================================
$(document).on('click', '.btn-clone-row', function(e) {
  e.preventDefault();
  var $row = $(this).closest('tr');
  var sayac = parseInt($('#rowNumberId').val(), 10) || ($('#kalem_ekle tbody tr').length + 1);

  // Satırın değerlerini oku
  var stokKodu = $row.find('.stokKodu-input').val() || '';
  var urunAdi = $row.find('.urunAdi-input').val() || '';
  var amount = $row.find('.amount-input').val() || '1';
  var unit = $row.find('select[name="unit[]"]').val() || '';
  var saleprice = $row.find('.saleprice-input').val() || '';
  var salecur = $row.find('select[name="salecur[]"]').val() || 'TRY';
  var buyprice = $row.find('.buyprice-input').val() || '';
  var buycur = $row.find('select[name="buycur[]"]').val() || 'TRY';

  // Yeni satır oluştur ve hemen altına ekle
  offerRowAdd(sayac);
  $('#rowNumberId').val(sayac + 1);

  // Az sonra eklenen satıra değerleri doldur
  setTimeout(function() {
    var $newRow = $('#kalem_ekle tbody tr').last();
    $newRow.find('.stokKodu-input').val(stokKodu);
    $newRow.find('.urunAdi-input').val(urunAdi);
    $newRow.find('.amount-input').val(amount);
    $newRow.find('.saleprice-input').val(saleprice);
    $newRow.find('.buyprice-input').val(buyprice);

    if (unit) {
      $newRow.find('select[name="unit[]"]').val(unit);
    }
    if (salecur) {
      $newRow.find('select[name="salecur[]"]').val(salecur);
    }
    if (buycur) {
      $newRow.find('select[name="buycur[]"]').val(buycur);
    }

    if ($.fn.selectpicker) {
      $newRow.find('.selectpicker').selectpicker('refresh');
    }

    // Sıra numaralarını yeniden düzenle
    $('#kalem_ekle tbody tr').each(function(index) {
      $(this).find('input[name="satirno[]"]').val(index + 1);
    });

    updateAltToplam();

    // Klonlanan yeni satırın miktarına odaklan
    $newRow.find('.amount-input').focus().select();
  }, 150);
});

// =============================================================================
// 3. KLAVYE İLE HIZLI SATIR EKLEME (SON SATIRDA TAB/ENTER BASINCA)
// =============================================================================
$(document).on('keydown', '#kalem_ekle tbody tr:last-child .buyprice-input', function(e) {
  if (e.key === 'Tab' && !e.shiftKey) {
    e.preventDefault();
    $('#ekle').trigger('click');
    setTimeout(function() {
      $('#kalem_ekle tbody tr:last-child .urunAdi-input').focus();
    }, 200);
  }
});

// =============================================================================
// 4. TOPLU ÜRÜN SEÇME MODALI (MULTI-PRODUCT PICKER)
// =============================================================================
var multiProductCache = [];

function trNormalize(str) {
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
}

function loadMultiProductList() {
  var $tbody = $('#multiProductListBody');
  $tbody.html(
    '<tr><td colspan="7" class="text-center py-4 text-muted">' +
    '<i class="fa fa-spinner fa-spin fa-2x mb-2"></i><div>Ürünler yükleniyor...</div>' +
    '</td></tr>'
  );

  fetch('api/search_products.php?limit=1000')
    .then(function(res) { return res.json(); })
    .then(function(data) {
      multiProductCache = data.results || [];
      renderMultiProductTable(multiProductCache);
    })
    .catch(function(err) {
      $tbody.html('<tr><td colspan="7" class="text-center py-3 text-danger">Ürünler yüklenirken hata oluştu.</td></tr>');
    });
}

function renderMultiProductTable(products) {
  var $tbody = $('#multiProductListBody');
  if (!products || products.length === 0) {
    $tbody.html('<tr><td colspan="7" class="text-center py-4 text-muted">Kriterlere uygun ürün bulunamadı.</td></tr>');
    return;
  }

  var html = '';
  $.each(products, function(i, p) {
    var stockCode = p.stock_code || '-';
    var salePrice = p.sale_price ? parseFloat(p.sale_price).toFixed(2) : '0.00';
    var buyPrice = p.buy_price ? parseFloat(p.buy_price).toFixed(2) : '0.00';
    var unit = p.unit || 'Adet';
    var saleCur = p.sale_cur || 'TRY';
    var buyCur = p.buy_cur || 'TRY';

    html += '<tr class="multi-product-row" data-id="' + p.id + '">' +
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
  updateMultiSelectedCounter();
}

function updateMultiSelectedCounter() {
  var count = $('.multi-product-checkbox:checked').length;
  $('#multiSelectedCounter').text(count + ' Kalem Seçildi');
  var $btn = $('#btnAddSelectedProductsToOffer');
  $btn.prop('disabled', count === 0);
  $btn.find('span').text('Seçilenleri Teklife Aktar (' + count + ')');
}

// Modal açma
$(document).on('click', '#btnOpenMultiProductModal', function(e) {
  e.preventDefault();
  $('#multiProductSearchInput').val('');
  $('#multiProductPickerModal').modal('show');
  if (multiProductCache.length === 0) {
    loadMultiProductList();
  } else {
    renderMultiProductTable(multiProductCache);
  }
});

// Arama kutusu canlı filtre (Türkçe karakter duyarsız)
$(document).on('input', '#multiProductSearchInput', function() {
  var rawTerm = $.trim($(this).val());
  if (!rawTerm) {
    renderMultiProductTable(multiProductCache);
    return;
  }

  var termNorm = trNormalize(rawTerm);
  var filtered = multiProductCache.filter(function(p) {
    var titleNorm = trNormalize(p.title);
    var codeNorm = trNormalize(p.stock_code);
    return titleNorm.indexOf(termNorm) > -1 || codeNorm.indexOf(termNorm) > -1;
  });

  renderMultiProductTable(filtered);
});

// Checkbox değişimi
$(document).on('change', '.multi-product-checkbox', function() {
  $(this).closest('tr').toggleClass('table-active', $(this).is(':checked'));
  updateMultiSelectedCounter();
});

// Miktar butonları
$(document).on('click', '.btn-qty-plus', function() {
  var $input = $(this).siblings('.multi-qty-input');
  var val = parseFloat($input.val()) || 1;
  $input.val(val + 1);
  var $cb = $(this).closest('tr').find('.multi-product-checkbox');
  if (!$cb.is(':checked')) {
    $cb.prop('checked', true).trigger('change');
  }
});

$(document).on('click', '.btn-qty-minus', function() {
  var $input = $(this).siblings('.multi-qty-input');
  var val = parseFloat($input.val()) || 1;
  if (val > 1) {
    $input.val(val - 1);
  }
});

// Tümünü seç / Temizle
$(document).on('click', '#btnMultiSelectAll', function() {
  $('.multi-product-checkbox').prop('checked', true).closest('tr').addClass('table-active');
  updateMultiSelectedCounter();
});

$(document).on('click', '#btnMultiClearAll', function() {
  $('.multi-product-checkbox').prop('checked', false).closest('tr').removeClass('table-active');
  updateMultiSelectedCounter();
});

// Seçilenleri teklife aktar
$(document).on('click', '#btnAddSelectedProductsToOffer', function() {
  var $checked = $('.multi-product-checkbox:checked');
  if ($checked.length === 0) return;

  var selectedItems = [];
  $checked.each(function() {
    var $tr = $(this).closest('tr');
    var id = parseInt($tr.data('id'), 10);
    var qty = parseFloat($tr.find('.multi-qty-input').val()) || 1;
    var product = multiProductCache.find(function(p) { return parseInt(p.id, 10) === id; });
    if (product) {
      selectedItems.push({
        product: product,
        qty: qty
      });
    }
  });

  if (selectedItems.length === 0) return;

  var $tbody = $('#kalem_ekle tbody');
  var $firstRow = $tbody.find('tr').first();
  var isFirstRowEmpty = $firstRow.length > 0 &&
    !$firstRow.find('.urunAdi-input').val() &&
    !$firstRow.find('.stokKodu-input').val();

  var itemIndex = 0;
  if (isFirstRowEmpty) {
    var first = selectedItems[0];
    selectProductItem($firstRow, {
      title: first.product.title,
      stock: first.product.stock_code,
      saleprice: first.product.sale_price,
      salecur: first.product.sale_cur,
      buyprice: first.product.buy_price,
      buycur: first.product.buy_cur,
      unit: first.product.unit
    });
    $firstRow.find('.amount-input').val(first.qty);
    itemIndex = 1;
  }

  function addRemaining(idx) {
    if (idx >= selectedItems.length) {
      $('#kalem_ekle tbody tr').each(function(index) {
        $(this).find('input[name="satirno[]"]').val(index + 1);
      });
      updateAltToplam();
      $('#multiProductPickerModal').modal('hide');

      swal.fire({
        title: 'Başarılı!',
        text: selectedItems.length + ' adet ürün teklife eklendi.',
        icon: 'success',
        timer: 1800,
        showConfirmButton: false
      });
      return;
    }

    var item = selectedItems[idx];
    var sayac = parseInt($('#rowNumberId').val(), 10) || ($('#kalem_ekle tbody tr').length + 1);
    offerRowAdd(sayac);
    $('#rowNumberId').val(sayac + 1);

    setTimeout(function() {
      var $newRow = $('#kalem_ekle tbody tr').last();
      selectProductItem($newRow, {
        title: item.product.title,
        stock: item.product.stock_code,
        saleprice: item.product.sale_price,
        salecur: item.product.sale_cur,
        buyprice: item.product.buy_price,
        buycur: item.product.buy_cur,
        unit: item.product.unit
      });
      $newRow.find('.amount-input').val(item.qty);
      addRemaining(idx + 1);
    }, 120);
  }

  addRemaining(itemIndex);
});

