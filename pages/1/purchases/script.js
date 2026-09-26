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
  var form = $("#myForm");
  

  var formData = new FormData(form[0]);
  formData.append("action", "savePurchases");

    // for (var pair of formData.entries()) {
    //   console.log(pair[0] + ", " + pair[1]);
    // }

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

  fetch("App/api/purchase.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      title = data.status == "success" ? "Başarılı" : "Hata";
      swal
        .fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        })
        .then((result) => {
          if (result.isConfirmed) {
            var link ="index.php?p=purchases/manage&id=" + data.id;
            //window.location.href = link;
          }
        });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).ready(function () {
    $("#company").on("change", function () {
        getcustomerInfo(this);
    })

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
    })

    $("#addRow").click(function () {
        var sayac = $("#rowNumberId");
        purchaseRowAdd(sayac.val());
        sayac.val(parseInt(sayac.val(), 10) + 1);
    })

    $("#currency").change(function () {
        getCurrencyData();
    })

    $("[id^='currency']").each(function () {
        $(this).on("change", function () {
            updateToplamPurchase();
        });
    });


    $("#payPeriod").on("keyup", function () {
        var paymentDays = parseInt($("#payPeriod").val());
        var futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + paymentDays);
        var formattedDate = formatDate(futureDate);
        $("#payment_date").val(formattedDate);
    });
   
});
