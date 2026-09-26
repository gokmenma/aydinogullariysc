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

// Ürün Arama, Canlı Tamamlama, Satır Klonlama ve Toplu Ürün Seçim işlemleri
// merkezi olarak include/js/product-picker.js üzerinden yürütülmektedir.


