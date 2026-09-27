//Satırlarda işlem yapıldığında
function updateToplamPurchase() {
  var alisToplam = 0;
  var DolarToplam = 0;
  var EuroToplam = 0;
  var TLToplam = 0;
  var TotalTL = 0;
  var total = 0;


  var curDollar = $("#cur-Dollar").val().replace(",", ".");
  var curEuro = $("#cur-Euro").val().replace(",", ".");

  // Her bir satır için birim fiyat ve adet bilgilerini alarak toplamı hesapla
  $("#tProduct tbody tr").each(function () {
    //Birim Fiyatı

    var alisFiyat = parseFloat($(this).find("[id^='price']").val());
      
    
    //Miktarı
    var amount = parseFloat($(this).find("[id^='amount']").val());
    //Para Birimi
    var buycurType = $(this).find("[id^='currency']").val();

    if (!isNaN(alisFiyat)) {
      //Para Birimi Dolar ise
      if (buycurType === "USD") {
        alisToplam = curDollar * alisFiyat * amount;
        total = alisFiyat * amount * curDollar;

        //Alttoplamdaki Dolar Toplamı hesaplanır
        DolarToplam = DolarToplam + alisFiyat * amount;
      } else if (buycurType === "EUR") {
        alisToplam = curEuro * alisFiyat * amount;
        total = alisFiyat * amount * curEuro;

        //Alttoplamdaki Euro Toplamı hesaplanır
        EuroToplam = EuroToplam + alisFiyat * amount;
      } else if (buycurType === "TRY") {
        alisToplam = alisFiyat * amount;
        total = alisFiyat * amount;

        //Alttoplamdaki TL Toplamı hesaplanır
        TLToplam = TLToplam + alisFiyat * amount;
      }
    }
  });
  //Toplam TL Karşılığı yazdırılır
  TotalTL = (DolarToplam * curDollar) + (EuroToplam * curEuro) + TLToplam;
  //console.log("Alış Toplamı"+ alisToplam);
  
  // Hesaplanan toplamı AltToplam etiketine yaz
  $(".AltToplam").text(formatNumber(alisToplam));
  $("#buy-tl").text(formatNumber(alisToplam));
  $("#discount").text($("#iskonto").val());
  $("#kdv-rate").text($("#Kdv").val());

  //console.log(TotalTL);
  //Kdv eklenip iskonto oranı düşüldükten sonra kalan miktar yazdırılır
  if (!isNaN(TotalTL)) {
    $("#altToplamInput").val(kdvHesapla(TotalTL));

    $("#lblTotalTL").text(kdvHesapla(TotalTL));
    $("#sonToplamFiyat").val(kdvHesapla(TotalTL));
  } else {
    $("#altToplamInput").val("0,00");
  }

  //Dolar cinsinden olan satırların toplam tutar hesaplanır
  $("#DolarAlttoplam").val(formatNumber(DolarToplam));

  //Euro cinsinden olan satırların toplam tutar hesaplanır
  $("#EuroAlttoplam").val(formatNumber(EuroToplam));

  //TL cinsinden olan satırların toplam tutar hesaplanır
  $("#TLAlttoplam").val(formatNumber(TLToplam));
}

//Kaydet butonuna basıldığında
$(document).on("click", "#saveButton", function () {
  var form = document.getElementById("myForm") || document.querySelector("form");
  if (!form) return;

  var emptyFields = [];
  var invalidElements = [];

  // Önceki geçersizlik işaretlemelerini temizle
  $(form).find(".is-invalid, .form-control-invalid, .select2-invalid").removeClass("is-invalid form-control-invalid select2-invalid");
  $(form).find(".select2-selection, .bootstrap-select").removeClass("is-invalid select2-invalid");

  // 1. Müşteri/Firma kontrolü
  var customerVal = $("select[name='customers']").val();
  if (!customerVal || customerVal === "") {
    emptyFields.push("Firma Seçimi");
    var $custEl = $("select[name='customers']");
    if ($custEl.length) {
      invalidElements.push($custEl[0]);
      $custEl.addClass("is-invalid");
      $custEl.closest(".purchase-customer-control").find(".bootstrap-select").addClass("is-invalid");
    }
  }

  // 2. Termin Tarihi kontrolü
  var deadline = $("input[name='deadline']").val();
  if (!deadline || deadline.trim() === "") {
    emptyFields.push("Termin Tarihi");
    var $elDeadline = $("input[name='deadline']");
    invalidElements.push($elDeadline[0]);
    $elDeadline.addClass("is-invalid");
  }

  // 3. Tablo ürün satırları kontrolü
  var $rows = $("#tProduct tbody tr");
  if ($rows.length === 0) {
    emptyFields.push("En az 1 adet ürün kalemi eklemelisiniz");
  } else {
    $rows.each(function (index) {
      var rowNum = index + 1;
      var $row = $(this);
      var urunAdi = ($row.find("input[name='urunAdi[]']").val() || "").trim();
      var amount = ($row.find("input[name='amount[]']").val() || "").trim();
      var price = ($row.find("input[name='price[]']").val() || "").trim();
      var unit = $row.find("select[name='unit[]']").val();

      if (!urunAdi) {
        emptyFields.push("Ürün Adı (Satır " + rowNum + ")");
        var $elUrun = $row.find("input[name='urunAdi[]']");
        invalidElements.push($elUrun[0]);
        $elUrun.addClass("is-invalid");
      }

      if (!amount || parseFloat(amount) <= 0 || isNaN(parseFloat(amount))) {
        emptyFields.push("Geçerli Miktar (Satır " + rowNum + ")");
        var $elAmount = $row.find("input[name='amount[]']");
        invalidElements.push($elAmount[0]);
        $elAmount.addClass("is-invalid");
      }

      if (price === "" || isNaN(parseFloat(price.replace(",", ".")))) {
        emptyFields.push("Birim Fiyat (Satır " + rowNum + ")");
        var $elPrice = $row.find("input[name='price[]']");
        invalidElements.push($elPrice[0]);
        $elPrice.addClass("is-invalid");
      }

      if (!unit || unit === "") {
        emptyFields.push("Birim Seçimi (Satır " + rowNum + ")");
        var $elUnit = $row.find("select[name='unit[]']");
        invalidElements.push($elUnit[0]);
        $elUnit.addClass("is-invalid");
        $elUnit.closest("td").find(".bootstrap-select").addClass("is-invalid");
      }
    });
  }

  // Eksik veya hatalı alan varsa SweetAlert ile bildir
  if (emptyFields.length > 0) {
    if (invalidElements.length > 0) {
      var firstEl = invalidElements[0];
      var $scrollTarget = $(firstEl).closest('.form-group, tr, td');
      if ($scrollTarget.length) {
        $('html, body').animate({
          scrollTop: Math.max(0, $scrollTarget.offset().top - 130)
        }, 250);
      }
      setTimeout(function () {
        try {
          firstEl.focus();
        } catch (e) {}
      }, 300);
    }

    if (typeof Swal !== "undefined" && typeof Swal.fire === "function") {
      var fieldItemsHtml = emptyFields.map(function (field) {
        return '<li style="margin-bottom: 5px; font-weight: 600; color: #b91c1c;">' + field + '</li>';
      }).join('');

      var alertBodyHtml = '<div style="text-align: left; background: #fff5f5; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px; margin-top: 10px;">' +
        '<div style="font-size: 13px; font-weight: 600; color: #dc2626; margin-bottom: 8px;">' +
        '<i class="fa fa-exclamation-triangle mr-1"></i> Lütfen aşağıdaki zorunlu alanları doldurunuz:' +
        '</div>' +
        '<ul style="margin: 0; padding-left: 20px; font-size: 13.5px; line-height: 1.6;">' +
        fieldItemsHtml +
        '</ul>' +
        '</div>';

      Swal.fire({
        icon: 'warning',
        title: 'Zorunlu Alanlar Eksik',
        html: alertBodyHtml,
        confirmButtonText: 'Tamam',
        confirmButtonColor: '#2563eb',
        customClass: {
          popup: 'swal2-border-radius-16'
        }
      });
    } else {
      alert("Lütfen zorunlu alanları doldurunuz:\n- " + emptyFields.join("\n- "));
    }
    return;
  }

  var $saveBtn = $("#saveButton");
  var origHtml = $saveBtn.html();
  $saveBtn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');

  var formData = new FormData(form);
  formData.append("action", "savePurchases");

  fetch("App/api/purchase.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      $saveBtn.prop("disabled", false).html(origHtml);
      var isSuccess = data.status === "success";
      var title = isSuccess ? "Başarılı!" : "Hata!";
      var msg = data.message || (isSuccess ? "Sipariş başarıyla kaydedildi." : "Bir hata oluştu.");

      if (typeof Swal !== "undefined" && typeof Swal.fire === "function") {
        Swal.fire({
          title: title,
          text: msg,
          icon: isSuccess ? "success" : "error",
          confirmButtonText: "Tamam",
          confirmButtonColor: isSuccess ? "#10b981" : "#ef4444",
          customClass: {
            popup: 'swal2-border-radius-16'
          }
        }).then((result) => {
          if (result.isConfirmed && isSuccess && data.edit_url) {
            window.location.href = data.edit_url;
          }
        });
      } else {
        alert(msg);
        if (isSuccess && data.edit_url) {
          window.location.href = data.edit_url;
        }
      }
    })
    .catch((error) => {
      $saveBtn.prop("disabled", false).html(origHtml);
      console.error("Error:", error);
      if (typeof Swal !== "undefined" && typeof Swal.fire === "function") {
        Swal.fire({
          title: "Hata!",
          text: "Sunucu ile iletişim kurulurken bir hata oluştu.",
          icon: "error",
          confirmButtonText: "Tamam",
          confirmButtonColor: "#ef4444"
        });
      } else {
        alert("Sunucu ile iletişim kurulurken bir hata oluştu.");
      }
    });
});

$(document).ready(function () {
    $(document).on("change", "select[name='customers'], #customers, #company", function () {
        getcustomerInfo(this);
    });

    $(document).on("input change", "input, select, textarea", function () {
        $(this).removeClass("is-invalid form-control-invalid");
        $(this).closest(".bootstrap-select, .select2-container").removeClass("is-invalid select2-invalid");
    });

    getCurrencyData();
    $("table").on("input change", "tr input, tr select", function () {
        updateToplamPurchase();
    });

    $("#tProduct").on("click", ".sil", function (e) {
        e.preventDefault();
        $(this).closest("tr").remove();
        
        // Re-index remaining rows and file inputs
        $("#tProduct tbody tr").each(function(index) {
            $(this).find("td:first-child span.text-muted").text(index + 1);
            $(this).find("input[name='satirno[]']").val(index + 1);
            $(this).find("input[type='file']").attr("name", "row_file_" + index);
        });
        
        // Update the counter for adding new rows
        $("#rowNumberId").val($("#tProduct tbody tr").length + 1);
        
        updateToplamPurchase();
    });

    $("#addRow").click(function () {
        var sayac = $("#rowNumberId");
        purchaseRowAdd(sayac.val());
        sayac.val(parseInt(sayac.val(), 10) + 1);
    });

    $("#currency").change(function () {
        getCurrencyData();
    });

    $("[id^='currency']").each(function () {
        $(this).on("change", function () {
            updateToplamPurchase();
        });
    });

    $(document).on("keyup change input", "#payPeriod", function () {
        var val = $(this).val();
        var paymentDays = parseInt(val, 10);
        if (isNaN(paymentDays) || paymentDays < 0) {
            paymentDays = 0;
        }
        var futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + paymentDays);
        var formattedDate = formatDate(futureDate);
        $("#payment_date").val(formattedDate);
    });
   
});
