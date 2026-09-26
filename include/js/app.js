function validateForm(routelink = null) {
  var form = document.getElementById("myForm") || document.querySelector("form");
  if (!form) return;

  var elements = form.elements;
  var emptyFields = [];
  var invalidElements = [];

  // Önceki geçersizlik işaretlemelerini temizle
  $(form).find(".is-invalid, .form-control-invalid, .select2-invalid").removeClass("is-invalid form-control-invalid select2-invalid");
  $(form).find(".select2-selection").removeClass("is-invalid select2-invalid");

  for (var i = 0; i < elements.length; i++) {
    var el = elements[i];
    if (!el || el.type === "submit" || el.type === "button" || el.type === "hidden" || el.type === "reset") continue;

    // Form elemanının parent node'u bir td ise, tablo içinde yer alır ve checkRequiredCells'te kontrol edilmeli
    if (!isDescendantOfTable(el)) {
      var isRequired = el.hasAttribute("required") || $(el).hasClass("required");
      var val = (el.value !== undefined && el.value !== null) ? String(el.value).trim() : "";

      if (isRequired && (val === "" || val === null)) {
        var label = document.querySelector('label[for="' + el.getAttribute("name") + '"]') ||
                    document.querySelector('label[for="' + el.getAttribute("id") + '"]') ||
                    $(el).closest('.form-field, .form-group').find('label').first()[0];
        var labelText = label
          ? label.textContent.trim().replace(/[:\(\*\)]/g, "")
          : (el.getAttribute("placeholder") || el.getAttribute("name") || "Zorunlu Alan");

        if (labelText && !emptyFields.includes(labelText)) {
          emptyFields.push(labelText);
        }
        invalidElements.push(el);
        $(el).addClass("is-invalid");

        // Select2 kontrolü
        var $select2 = $(el).next(".select2-container");
        if ($select2.length) {
          $select2.find(".select2-selection").addClass("is-invalid select2-invalid");
        }
      }
    }
  }

  // Tablodaki zorunlu alanları kontrol ederek diziye ekler ve elemanları renklendirir
  checkRequiredCells(emptyFields, invalidElements);

  if (emptyFields.length > 0) {
    // İlk hatalı alana odaklan ve oraya yumuşakça kaydır
    if (invalidElements.length > 0) {
      var firstEl = invalidElements[0];
      var $scrollTarget = $(firstEl).closest('.form-field, .form-group, td, tr');
      if ($scrollTarget.length) {
        $('html, body').animate({
          scrollTop: Math.max(0, $scrollTarget.offset().top - 130)
        }, 250);
      }
      setTimeout(function () {
        try {
          if ($(firstEl).is("select") && $(firstEl).data("select2")) {
            $(firstEl).select2("open");
          } else {
            firstEl.focus();
          }
        } catch (e) {}
      }, 300);
    }

    // Modern SweetAlert2 ile şık uyarı penceresi
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
        confirmButtonText: 'Tamam!',
        confirmButtonColor: '#2563eb',
        customClass: {
          popup: 'swal2-border-radius-16'
        }
      });
    } else {
      var errorMessage = "Lütfen zorunlu alanları doldurun: <br/>" + emptyFields.join(", ");
      showMessage(errorMessage, "alert", routelink);
    }
    return false;
  } else {
    var button = document.getElementById("submitButton");
    if (button) {
      button.disabled = true;
    }
    form.submit();
  }
}

function isDescendantOfTable(element) {
  var parent = element.parentNode;
  while (parent !== null) {
    if (parent.tagName && parent.tagName.toLowerCase() === "table") {
      return true;
    }
    parent = parent.parentNode;
  }
  return false;
}

// Tablodaki zorunlu olan td elemanlarını kontrol eder ve boş olanların sütun başlıklarını diziye ekler
function checkRequiredCells(emptyFields, invalidElements) {
  var tables = document.querySelectorAll("#tProduct, .premium-table, table.table");
  tables.forEach(function (table) {
    var rows = table.querySelectorAll("tbody tr");
    rows.forEach(function (row) {
      var cells = row.querySelectorAll("td");
      cells.forEach(function (cell, colIndex) {
        var formElements = cell.querySelectorAll("input, select, textarea");
        formElements.forEach(function (formElement) {
          if (formElement.type === "hidden" || formElement.type === "submit" || formElement.type === "button") return;

          var isRequired = formElement.hasAttribute("required") || $(formElement).hasClass("required");
          var val = (formElement.value !== undefined && formElement.value !== null) ? String(formElement.value).trim() : "";

          if (isRequired && val === "") {
            var columnHeader = getTableHeaderForColumn(table, colIndex);
            if (columnHeader && !emptyFields.includes(columnHeader)) {
              emptyFields.push(columnHeader);
            }
            if (invalidElements) {
              invalidElements.push(formElement);
            }
            $(formElement).addClass("is-invalid");

            var $select2 = $(formElement).next(".select2-container");
            if ($select2.length) {
              $select2.find(".select2-selection").addClass("is-invalid select2-invalid");
            }
          }
        });
      });
    });
  });
}

function getTableHeaderForColumn(table, columnIndex) {
  var headerRow = table.querySelector("thead tr");
  if (headerRow) {
    var headers = headerRow.getElementsByTagName("th");
    if (columnIndex < headers.length) {
      var th = headers[columnIndex];
      var clone = th.cloneNode(true);
      var triggers = clone.querySelectorAll('.tf-trigger, button, i, svg');
      triggers.forEach(function(t) { t.remove(); });
      return clone.textContent.trim() || th.textContent.trim();
    }
  }
  return null;
}

function showMessage(message, type, routelink) {
  var icon = "info";
  var title = "Bilgi";
  var confirmBtnColor = "#2563eb";

  if (type === "success") {
    icon = "success";
    title = "Başarılı!";
    confirmBtnColor = "#10b981";
  } else if (type === "alert" || type === "danger") {
    icon = "warning";
    title = "Uyarı!";
    confirmBtnColor = "#f59e0b";
  } else if (type === "error") {
    icon = "error";
    title = "Hata!";
    confirmBtnColor = "#ef4444";
  }

  if (routelink) {
    try {
      window.history.pushState({}, "", "index.php?p=" + routelink);
    } catch (e) {}
  }

  if (typeof Swal !== "undefined" && typeof Swal.fire === "function") {
    Swal.fire({
      icon: icon,
      title: title,
      html: message,
      confirmButtonText: "Tamam",
      confirmButtonColor: confirmBtnColor,
      customClass: {
        popup: 'swal2-border-radius-16'
      }
    });
    return;
  }

  // Fallback to legacy alert banner if SweetAlert is somehow unavailable
  var alertClass = (type === "success") ? "alert-success" : ((type === "alert" || type === "danger") ? "alert-danger" : ((type === "error") ? "alert-warning" : "alert-info"));
  var alertMessage = $(
    '<div class="message alert ' +
      alertClass +
      ' alert-dismissible fade show">' +
      "<strong>" + title + "</strong> " +
      message +
      '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
      "</div>"
  );
  $("#maincontainer").before(alertMessage);
  window.setTimeout(function () {
    alertMessage.fadeTo(500, 0).slideUp(500, function () {
      $(this).remove();
    });
  }, 5000);
}

// Form doğrulama hata sınıflarını kullanıcı veri girdiğinde otomatik temizle
$(document).on("input change keyup", "input.is-invalid, textarea.is-invalid", function () {
  if ($(this).val() && String($(this).val()).trim() !== "") {
    $(this).removeClass("is-invalid form-control-invalid");
  }
});

$(document).on("change select2:select", "select", function () {
  if ($(this).val() && String($(this).val()).trim() !== "") {
    $(this).removeClass("is-invalid form-control-invalid");
    var $s2 = $(this).next(".select2-container");
    if ($s2.length) {
      $s2.find(".select2-selection").removeClass("is-invalid select2-invalid");
    }
  }
});

function addDataTableColumnSearchRow(api) {
  if (window.App && window.App.TableFilter) {
    App.TableFilter.attachToTable(api.table().node());
  }
}

function deleteRecord(msg, ID, pLink, table=null, redirectLink=null) {

  Swal.fire({
    title: "Emin misiniz?" ,
    text: msg,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Evet,Sil!",
    cancelButtonText: "Vazgeç!",
  }).then((result) => {
    if (result.isConfirmed) {

      // If confirmed, trigger AJAX request to delete product
      $.ajax({
        type: "POST",
        url: "pages/1/ajax.php?mode=delete&code=04md177&id=" + ID,
        data: {
          id: ID,
          page : pLink,
          table: table
        },
        success: function (response) {
          var res = JSON.parse(response);
          if(res.status == 200) {
          Swal.fire({ 
            title: "Başarılı!",
            text: res.message, 
            icon: "success",
          }).then(() => {
            // Redirect to page
            window.location.href = "index.php?p=" + (redirectLink || pLink);
          });
       
        }else{
          Swal.fire({ 
            title: "Hata!",
            text: res.message, 
            icon: "warning",
        })
        }},
        error: function (xhr, status, error) {
          // Handle error if deletion fails (optional)
          console.error(xhr.responseText);
          Swal.fire({
            title: "Hata!",
            text: "Bir şeyler ters gitti!",
            icon: "error",
          });
        },
      });
    }
  });
}

function SaveNewKategory(p_name, selectName) {
  var Addcategory = document.getElementById("Addcategory").value;
  if (Addcategory != "") {
    fetch("index.php?p=" + p_name, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "Addcategory=" + encodeURIComponent(Addcategory),
    })
      .then((response) => {
        var selectElement = document.getElementById(selectName);
        var newOption = document.createElement("option");
        newOption.value = Addcategory;
        newOption.textContent = Addcategory;
        selectElement.appendChild(newOption);
        document.getElementById("Addcategory").value = "";
      })
      .catch((error) => {
        // Hata durumunda burada işlemler yapabilirsiniz
      });
  }
}

function offerControl() {
  var customers = document.getElementById("customers");
  showMessage("success", customers.value);
}

function setTodayDate() {
  var today = new Date();
  var day = String(today.getDate()).padStart(2, "0");
  var month = String(today.getMonth() + 1).padStart(2, "0"); // Ocak 0'dan başlar
  var year = today.getFullYear();

  return day + "." + month + "." + year;
}

function getProductInfoPurchase() {
  var rowID = $("#rowID").val();
  var productId = $("#productName").val();
  var row = $(this).closest("tr");

  // Ajax isteğini yap
  var ajaxPromise = $.ajax({
    type: "POST",
    url: "pages/1/getProduct.php",
    data: {
      id: productId,
    },
  });

  // Ajax isteği tamamlandığında çalışacak işlev
  ajaxPromise.then(function (response) {
    var data = JSON.parse(response);

    $("#urunAdi" + rowID).val(data.Adi);
    $("#stokKodu" + rowID).val(data.StokKodu);
    $("#buyprice" + rowID).val(data.AlisFiyati);
    $("#buycur" + rowID).val(data.buycur);
    $("#unit" + rowID).val(data.unit);
    $(".selectpicker").selectpicker("refresh");

   // Bootstrap 4 için modalı kapatma
   $('#staticBackdrop').modal('hide');
  });
}

//Satın Alma sayfasındaki Toplamı güncelleyen fonksiyon


function formatNumber(num) {
  var parts = num.toFixed(2).toString().split(".");
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  return parts.join(",");
}

function kdvTutari(toplamtutar,kdvOrani){
  
  // return kdvTutari;
}
function kdvHesapla(toplamTutar) {
  var kdvOrani = parseFloat($("#Kdv").val(), 10) || 0; // KDV oranı
  var iskontoTutari = parseFloat($("#iskonto").val(), 10) || 0; // KDV oranı

  var toplamTutarNumber = toplamTutar - Number(iskontoTutari);
  var kdvTutari = (toplamTutarNumber * kdvOrani) / 100;
  var toplamSonuc = Number(toplamTutarNumber) + Number(kdvTutari);
  return formatNumber(toplamSonuc); // KDV'yi toplam tutara ekleyerek döndür
}

function tableRowNumberUpdate(tabloadi = "kalem_ekle") {
  $('#' + tabloadi + ' tr').each(function(index){
    var satirNo = index ;
    $(this).find('input[name="satirno[]"]').val(satirNo);
  });
}

function rowMove(direction) {
  //alert (direction + " direction")
  var currentRow = $(this).closest("tr");
  if (direction === "up") {
    var targetRow = currentRow.prev("tr");
    if (targetRow.length !== 0) {
      currentRow.insertBefore(targetRow);
    }
  } else {
    var targetRow = currentRow.next("tr");
    if (targetRow.length !== 0) {
      currentRow.insertAfter(targetRow);
    }
  }
  tableRowNumberUpdate();
}

async function purchaseRowAdd(sayac,demand=false) {
  

  $("#preloader").show();
   //*****AÇIKLAMA*********//
   //satına alma talep sayfasında açıklama alanı var
   if(demand) {
    var rowdescription = 
      '<td>' +
          '<input type="text" class="form-control" style="min-width: 150px; width: 100%;" ' +
          'name="rowdescription[]" value="">' +
      '</td>'
   }
   //*****AÇIKLAMA*********//
  
  //*****BİRİMLER*********//
  var selectUnit =
    '<select required id="unit' +
    sayac +
    '" name="unit[]" ' +
    'data-header="Birimler" data-style="border bg-white" class="selectpicker form-control">';

  var birimler2 = ["Kg", "Ad.", "Gram", "Metre", "Litre", "m2"]; //
  //birimleri veritabanından çekmek için
  var birimler = [];
  var formData = new FormData();
  formData.append("action", "getUnits");

const responde = await fetch("App/api/units.php",
  {
    method: "POST",
    body: formData,
  }
  ).then(response => response.json())
  .then(data => {
    if (data.status === "success") {
      //console.log(data.data);
      
      birimler = data.data;
      for (var i = 0; i < birimler.length; i++) {
        selectUnit +=
          '<option value="' +
          birimler[i]["title"] +
          '">' +
          birimler[i]["title"] +
          "</option>";
      }
      selectUnit += "</select>";
      //*****BİRİMLER*********//
      
    } 
  })
 

  //****PARA BİRİMLERİ*****//
  var selectmoneys =
    '<select required id="currency' +
    sayac +
    '" name="currency[]" ' +
    'data-header="Birimler" data-style="border bg-white" class="selectpicker form-control">';

  var moneys = ["TRY", "USD", "EUR"]; //
  for (var i = 0; i < moneys.length; i++) {
    selectmoneys +=
      '<option value="' + moneys[i] + '">' + moneys[i] + "</option>";
  }

  selectmoneys += "</select>";
  //****PARA BİRİMLERİ*****//

  var rowHtml = 
    "<tr class='ui-state-default'>" +
    '<td style="width: 35px; min-width: 35px; text-align: center; vertical-align: middle;"><span class="btn btn-sm text-muted p-0 drag-handle" style="cursor: grab;"><i class="fa fa-arrows-alt"></i></span></td>' +
    '<td class="app-item-action-2 text-center" style="width: 80px; min-width: 80px; vertical-align: middle; white-space: nowrap;">' +
      '<div class="btn-group btn-group-sm" role="group" style="display: inline-flex;">' +
        '<a type="button" class="sil btn btn-sm btn-danger text-white" title="Satırı Sil" style="padding: 4px 8px; border-radius: 6px 0 0 6px;"><i class="fa fa-trash"></i></a>' +
        '<button type="button" class="btn btn-sm btn-outline-primary btn-clone-row" title="Satırı Klonla" style="padding: 4px 8px; border-radius: 0 6px 6px 0;"><i class="fa fa-clone"></i></button>' +
      '</div>' +
    '</td>' +
    '<td class="app-item-number text-center" style="width: 55px; min-width: 55px; vertical-align: middle;">' +
      '<input class="form-control text-center font-weight-bold" name="satirno[]" type="text" value="' + sayac + '" readonly style="background: #f8fafc; border-radius: 6px; width: 45px; margin: 0 auto;">' +
    '</td>' +
    '<td class="app-item-stock" style="width: 140px; min-width: 120px; vertical-align: middle;">' +
      '<div class="product-autocomplete-wrap">' +
        '<input type="text" id="stokKodu' + sayac + '" name="stokKodu[]" class="form-control stokKodu-input" placeholder="Stok Kodu" autocomplete="off" style="border-radius: 6px; border-color: #cbd5e1;">' +
      '</div>' +
    '</td>' +
    '<td class="app-item-name" style="min-width: 220px; vertical-align: middle;">' +
      '<div class="product-autocomplete-wrap position-relative">' +
        '<input type="text" required name="urunAdi[]" id="urunAdi' + sayac + '" placeholder="Ürün adı yazarak arayın veya seçin..." class="urunAdi form-control urunAdi-input" autocomplete="off" style="border-radius: 6px; border-color: #cbd5e1;">' +
      '</div>' +
    '</td>' +
    '<td class="app-item-amount text-center" style="width: 90px; min-width: 80px; vertical-align: middle;">' +
      '<input type="number" step="any" min="0" required id="amount' + sayac + '" name="amount[]" autocomplete="off" class="Adet form-control amount-input text-center" placeholder="0" style="border-radius: 6px; border-color: #cbd5e1;">' +
    '</td>' +
    '<td class="app-item-unit" style="width: 110px; min-width: 100px; vertical-align: middle;">' + selectUnit + '</td>' +
    '<td class="app-item-price" style="width: 120px; min-width: 100px; vertical-align: middle;">' +
      '<input type="text" required id="price' + sayac + '" name="price[]" class="BirimFiyat form-control price-input text-right" autocomplete="off" placeholder="0.00" style="border-radius: 6px; border-color: #cbd5e1;">' +
    '</td>' +
    '<td class="app-item-cur" style="width: 100px; min-width: 90px; vertical-align: middle;">' + selectmoneys + '</td> ' +
    (demand ? ('<td style="min-width: 180px; vertical-align: middle;"><input type="text" class="form-control" style="min-width: 150px; width: 100%; border-radius: 6px; border-color: #cbd5e1;" name="rowdescription[]" value="" placeholder="Kalem açıklaması..."></td>') : '') + 
    "</tr>";

  if ($("#sortable").length) {
    $("#sortable").append(rowHtml);
  } else {
    $("#tProduct tbody").append(rowHtml);
  }

  $(".selectpicker").selectpicker("refresh");
  $("#preloader").hide();
}

function sayacGuncelle(tabloId) {
  var trSayisi = $(tabloId + " tbody tr").length;
  return trSayisi;
}

function getCurrencyData() {
  var currency = $("#currency");
  var inputDollar = $("#cur-Dollar");
  var inputEuro = $("#cur-Euro");

  
  $.ajax({
    type: "POST",
    url: "pages/1/doviz-kuru.php",
    dataType: "json",
    success: function (data) {
      var dolar = data.dolar;
      var euro = data.euro;
      
      // Elde edilen verileri kullan
      var curType = currency.val();
      if (curType == "Döviz Alış") {
        inputDollar.val(dolar.alis);
        inputEuro.val(euro.alis);
      } else if (curType == "Döviz Satış") {
        inputDollar.val(dolar.satis);
        inputEuro.val(euro.satis);
      } else if (curType == "Efektif Alış") {
        inputDollar.val(dolar.alis_efektif);
        inputEuro.val(euro.alis_efektif);
      } else if (curType == "Efektif Satış") {
        inputDollar.val(dolar.satis_efektif);
        inputEuro.val(euro.satis_efektif);
      }
     console.log("Dolar Alış: " + dolar.alis);
    },
    error: function (xhr, status, error) {
      console.error("Bir hata oluştu:", error);
    },
  });
}

//veri yüklendikten sonra geri dönüş yapar
function getCurrencyData() {
  return new Promise((resolve, reject) => {
    var currency = $("#currency");
    var inputDollar = $("#cur-Dollar");
    var inputEuro = $("#cur-Euro");

    console.log("Döviz kuru alınıyor...");

    $.ajax({
      type: "POST",
      url: "pages/1/doviz-kuru.php",
      dataType: "json",
      success: function (data) {
        var dolar = data.dolar;
        var euro = data.euro;
        console.log(data);

        // Elde edilen verileri kullan
        var curType = currency.val();
        if (curType == "Döviz Alış") {
          inputDollar.val(dolar.alis);
          inputEuro.val(euro.alis);
        } else if (curType == "Döviz Satış") {
          inputDollar.val(dolar.satis);
          inputEuro.val(euro.satis);
        } else if (curType == "Efektif Alış") {
          inputDollar.val(dolar.alis_efektif);
          inputEuro.val(euro.alis_efektif);
        } else if (curType == "Efektif Satış") {
          inputDollar.val(dolar.satis_efektif);
          inputEuro.val(euro.satis_efektif);
        }

        resolve();
      },
      error: function (xhr, status, error) {
        console.error("Bir hata oluştu:", error);
        reject(error);
      },
    });
  });
}



function formatDate(date) {
  var day = date.getDate();
  var month = date.getMonth() + 1; // JavaScript'te ay 0'dan başlar, bu yüzden 1 ekliyoruz
  var year = date.getFullYear();

  // Gerekli biçimlendirme işlemlerini yap
  if (day < 10) {
    day = "0" + day;
  }
  if (month < 10) {
    month = "0" + month;
  }

  return day + "-" + month + "-" + year; // dd-mm-yyyy biçiminde tarihi döndür
}

//müşteri seçildiği zaman Yetkili adını getirmek için
//başka bilgilerde gerekirse customer-info.php sayfasına ekleme yapılabilir
function getcustomerInfo(e) {
  var customerID = $(e).val();
  if (!customerID) {
    return;
  }
  $.ajax({
    url: "pages/1/customer-info.php",
    method: "POST",
    data: {
      id: customerID,
    },
    dataType: "JSON",
    success: function (response) {
      if (response) {
        var compAuths = $("#compAuths");
        var payPeriod = $("#payPeriod");
        var yetkili = (response.yetkili && response.yetkili !== '.' && response.yetkili !== '-') ? response.yetkili : '';
        compAuths.val(yetkili);

        var vade = (response.odemevadesi && response.odemevadesi !== null) ? response.odemevadesi : '';
        payPeriod.val(vade);
      }
    },
    error: function (xhr, status, error) {
      console.error(error);
    },
  });
}

function createSelect(options) {
  var name = options.name ? "name =" + options.name : "";
  var required = options.required ? options.required : "";
  var id = options.id ? "id=" + options.id : "";
  var colwidth = options.colwidth ? options.colwidth : "";
  var type = options.type ? options.type : null;
  var val = options.val ? options.val : "";

  var selectHtml =
    "<select " +
    required +
    " " +
    name +
    " " +
    id +
    '" class="selectpicker form-control ' +
    colwidth +
    '" ' +
    'data-container="body" data-style="border bg-white">';
  selectHtml += '<option disabled value="">Seçiniz</option>';
  var _options;

  if (type == 1 || type == null) {
    _options = ["UYGUN", "UYGUN DEĞİL"];
  } else if (type == 2) {
    _options = ["VAR", "YOK"];
  } else if (type == 3) {
    _options = ["EVET", "HAYIR"];
  }

  _options.forEach(function (option) {
    if (option == val) {
      selectHtml +=
        '<option value="' + option + '" selected>' + option + "</option>";
    } else {
      selectHtml += '<option value="' + option + '">' + option + "</option>";
    }
  });

  selectHtml += "</select>";

  return selectHtml;
}



function deleteReport(id,table){
  $.ajax({
    url:'pages/1/reports',
    data : {id:id},
    type:'POST',
    success: function(data){
      swal.fire({
        title:'Delete Report',
        text : 'Rapor silindi'
      })
    }
  });
}

$('#some-textarea').wysihtml5();

// Initialize AJAX searchable customer select (Select2)
$(document).ready(function() {
    if ($.fn.select2) {
        $('.ajax-customer-select').each(function() {
            var $this = $(this);
            $this.select2({
                placeholder: $this.attr('placeholder') || 'Müşteri Seçiniz',
                allowClear: !$this.prop('required'),
                ajax: {
                    url: 'pages/1/ajax.php?action=search-customers',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });
        });
    }

    // Sidebar toggle and persistence functionality (Desktop & Mobile)
    function syncMenuIconState() {
        if ($(window).width() > 1200) {
            $('#sidebar-backdrop').removeClass('open');
            $('.left-side-bar').removeClass('open');
            if ($('html').hasClass('sidebar-collapsed')) {
                $('.menu-icon').removeClass('open');
            } else {
                $('.menu-icon').addClass('open');
            }
        } else {
            if ($('.left-side-bar').hasClass('open')) {
                $('.menu-icon').addClass('open');
                $('#sidebar-backdrop').addClass('open');
            } else {
                $('.menu-icon').removeClass('open');
                $('#sidebar-backdrop').removeClass('open');
            }
        }
    }

    // On page load, sync menu icon state
    syncMenuIconState();

    // On resize, sync menu icon state
    $(window).on('resize', function() {
        syncMenuIconState();
    });

    // Handle click on menu icon (both mobile & desktop)
    $(document).on('click', '.menu-icon, #sidebar-menu-toggle', function(e) {
        if (window.toggleSidebarMenu) {
            window.toggleSidebarMenu(e);
        }
    });

    // Handle click on mobile backdrop to close sidebar
    $(document).on('click touchstart', '#sidebar-backdrop', function(e) {
        e.preventDefault();
        $('.left-side-bar').removeClass('open');
        $('.menu-icon').removeClass('open');
        $('#sidebar-backdrop').removeClass('open');
    });

    // Mobilde sidebar dışına tıklandığında kapatma
    $(document).on('click touchstart', function(e) {
        if ($(window).width() <= 1200) {
            if ($('.left-side-bar').hasClass('open')) {
                if ($(e.target).closest('.left-side-bar').length === 0 && 
                    $(e.target).closest('.menu-icon, #sidebar-menu-toggle').length === 0) {
                    $('.left-side-bar').removeClass('open');
                    $('.menu-icon').removeClass('open');
                    $('#sidebar-backdrop').removeClass('open');
                }
            }
        }
    });

    // Mobilde menü linkine tıklandığında menüyü otomatik kapat
    $(document).on('click', '.left-side-bar .sidebar-menu a:not(.dropdown-toggle)', function() {
        if ($(window).width() <= 1200) {
            $('.left-side-bar').removeClass('open');
            $('.menu-icon').removeClass('open');
            $('#sidebar-backdrop').removeClass('open');
        }
    });

    // Fix for dropdown clipping inside responsive tables / overflow containers
    try {
        document.addEventListener('show.bs.dropdown', function (event) {
            try {
                var dropdown = event.target;
                if (!dropdown) return;
                if (!dropdown.classList.contains('dropdown') && !dropdown.classList.contains('dropup') && !dropdown.classList.contains('btn-group')) {
                    dropdown = dropdown.closest('.dropdown, .dropup, .btn-group');
                }
                if (!dropdown) return;

                var scrollParents = [];
                var parent = dropdown.parentElement;
                while (parent && parent !== document.documentElement && parent !== document.body) {
                    var style = window.getComputedStyle(parent);
                    if (style) {
                        var overflow = (style.overflow || '') + (style.overflowX || '') + (style.overflowY || '');
                        if (/auto|scroll|hidden/.test(overflow)) {
                            scrollParents.push(parent);
                        }
                    }
                    parent = parent.parentElement;
                }

                scrollParents.forEach(function (el) {
                    if (!el.dataset.originalOverflow) {
                        el.dataset.originalOverflow = el.style.overflow || '';
                    }
                    if (!el.dataset.originalOverflowX) {
                        el.dataset.originalOverflowX = el.style.overflowX || '';
                    }
                    if (!el.dataset.originalOverflowY) {
                        el.dataset.originalOverflowY = el.style.overflowY || '';
                    }
                    el.style.setProperty('overflow', 'visible', 'important');
                    el.style.setProperty('overflow-x', 'visible', 'important');
                    el.style.setProperty('overflow-y', 'visible', 'important');
                });

                dropdown.__scrollParents = scrollParents;
            } catch (e) {
                console.error("Dropdown show overflow fix error:", e);
            }
        }, true);

        document.addEventListener('hide.bs.dropdown', function (event) {
            try {
                var dropdown = event.target;
                if (!dropdown) return;
                if (!dropdown.classList.contains('dropdown') && !dropdown.classList.contains('dropup') && !dropdown.classList.contains('btn-group')) {
                    dropdown = dropdown.closest('.dropdown, .dropup, .btn-group');
                }
                if (!dropdown) return;

                var scrollParents = dropdown.__scrollParents;
                if (scrollParents) {
                    scrollParents.forEach(function (el) {
                        el.style.overflow = el.dataset.originalOverflow || '';
                        el.style.overflowX = el.dataset.originalOverflowX || '';
                        el.style.overflowY = el.dataset.originalOverflowY || '';
                        
                        delete el.dataset.originalOverflow;
                        delete el.dataset.originalOverflowX;
                        delete el.dataset.originalOverflowY;
                    });
                    delete dropdown.__scrollParents;
                }
            } catch (e) {
                console.error("Dropdown hide overflow fix error:", e);
            }
        }, true);
    } catch (err) {
        console.error("Failed to bind dropdown overflow listeners:", err);
    }
});

// Global WYSIHTML5 / Rich Text Editor Placeholder ve Padding Hizalama
function initGlobalWysiPadding() {
    function applyPadding() {
        if (typeof $ === 'undefined') return;
        $('iframe.wysihtml5-sandbox').each(function () {
            try {
                this.style.setProperty('padding', '0', 'important');
                var doc = this.contentDocument || this.contentWindow.document;
                if (doc && doc.body) {
                    var cssContent = 'html { margin: 0 !important; padding: 0 !important; } ' +
                        'body { padding: 8px 12px !important; margin: 0 !important; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important; font-size: 13.5px !important; line-height: 1.5 !important; color: #334155 !important; box-sizing: border-box !important; } ' +
                        'body.placeholder { color: #94a3b8 !important; padding: 8px 12px !important; margin: 0 !important; } ' +
                        'body.dark-mode { color: #f8fafc !important; } ' +
                        'p { margin: 0 0 6px 0 !important; } ' +
                        'p:last-child { margin-bottom: 0 !important; } ' +
                        'ul, ol { margin: 0 0 6px 0 !important; padding-left: 20px !important; }';

                    var existingStyle = doc.getElementById('wysi-global-padding-style');
                    if (!existingStyle) {
                        var style = doc.createElement('style');
                        style.id = 'wysi-global-padding-style';
                        style.innerHTML = cssContent;
                        doc.head.appendChild(style);
                    } else if (existingStyle.innerHTML !== cssContent) {
                        existingStyle.innerHTML = cssContent;
                    }
                    if (doc.documentElement) {
                        doc.documentElement.style.margin = '0';
                        doc.documentElement.style.padding = '0';
                    }
                    doc.body.style.padding = '8px 12px';
                    doc.body.style.margin = '0';
                }
            } catch (e) {}
        });
    }

    applyPadding();
    setTimeout(applyPadding, 50);
    setTimeout(applyPadding, 150);
    setTimeout(applyPadding, 300);
    setTimeout(applyPadding, 700);
    setTimeout(applyPadding, 1500);

    if (window.MutationObserver && document.body) {
        var observer = new MutationObserver(function (mutations) {
            var hasIframe = false;
            for (var i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
                    hasIframe = true;
                    break;
                }
            }
            if (hasIframe) {
                applyPadding();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
}

// Müşteri E-Posta Şablon / Tekrarlı Kullanım Uyarısı (Kaydetmeyi engellemez)
function initCustomerEmailDuplicateCheck() {
    var emailTimer = null;

    $(document).on('input change blur', 'input[name="cemail"], #cemail', function () {
        var $input = $(this);
        clearTimeout(emailTimer);

        emailTimer = setTimeout(function () {
            var email = $.trim($input.val());
            var $container = $input.closest('.form-field, .col-md-4, .col-sm-12, .form-group, div');
            var $existingBox = $container.find('.customer-email-warning-box');

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $existingBox.slideUp(150, function () { $(this).remove(); });
                return;
            }

            var customerId = $('input[name="company_id"]').val() || 
                             $('input[name="id"]').val() || 
                             $('input[name="cid"]').val() || 
                             (new URLSearchParams(window.location.search).get('id')) || 
                             (new URLSearchParams(window.location.search).get('cid')) || 0;

            $.ajax({
                url: 'App/api/customer.php',
                type: 'GET',
                data: {
                    action: 'check_email',
                    email: email,
                    company_id: customerId
                },
                dataType: 'json',
                success: function (res) {
                    if (res && res.status === 'success' && res.exists && res.count > 0) {
                        var companyNames = [];
                        if (res.companies && res.companies.length > 0) {
                            for (var i = 0; i < Math.min(res.companies.length, 3); i++) {
                                if (res.companies[i].company) {
                                    companyNames.push(res.companies[i].company);
                                }
                            }
                        }
                        var previewStr = companyNames.join(', ');
                        if (res.count > 3) {
                            previewStr += ' ve diğerleri';
                        }
                        if (previewStr) {
                            previewStr = ' (Örn: ' + previewStr + ')';
                        }

                        var warningHtml = '<div class="customer-email-warning-box animate-fade-in">' +
                            '<i class="fa fa-exclamation-triangle"></i>' +
                            '<div>' +
                                '<strong style="display:block;margin-bottom:2px;">Şablon / Tekrarlanan E-Posta Uyarısı:</strong>' +
                                '<span>Bu e-posta adresi daha önce <b>' + res.count + '</b> aktif firmada' + previewStr + ' kullanılmıştır. Firma ile sağlıklı iletişim ve doğru e-posta iletimi için firmanın gerçek e-posta adresini yazmanız önerilir.</span>' +
                            '</div>' +
                        '</div>';

                        if ($existingBox.length > 0) {
                            $existingBox.replaceWith(warningHtml);
                        } else {
                            $input.after(warningHtml);
                        }
                    } else {
                        $existingBox.slideUp(150, function () { $(this).remove(); });
                    }
                },
                error: function () {}
            });
        }, 350);
    });
}

if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        initGlobalWysiPadding();
        initCustomerEmailDuplicateCheck();
    });
    $(window).on('load', function() {
        initGlobalWysiPadding();
    });
} else {
    document.addEventListener('DOMContentLoaded', function() {
        initGlobalWysiPadding();
        initCustomerEmailDuplicateCheck();
    });
    window.addEventListener('load', initGlobalWysiPadding);
}




