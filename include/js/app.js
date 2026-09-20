function validateForm(routelink = null) {
  var form = document.getElementById("myForm");

  var elements = form.elements;
  var emptyFields = [];

  for (var i = 0; i < elements.length; i++) {
    // Form elemanının parent node'u bir td ise, tablo içinde yer alır ve kontrol edilmemeli
    if (!isDescendantOfTable(elements[i])) {
      if (
        (elements[i].hasAttribute("required") &&
          elements[i].value.trim() === "") ||
        elements[i].value.trim() === null
      ) {
        var label = document.querySelector(
          'label[for="' + elements[i].getAttribute("name") + '"]'
        );
        var labelText = label
          ? label.textContent.trim().replace(/[:\(\*\)]/g, "")
          : elements[i].getAttribute("name");
        emptyFields.push(labelText);
      }
    }
  }

  //Tablodaki zorunlu alanları kontrol ederek diziye ekler
  checkRequiredCells(emptyFields);
  //console.log("Boş olan zorunlu alanlar: ", emptyFields);

  if (emptyFields.length > 0) {
    var errorMessage =
      "Lütfen zorunlu alanları doldurun: <br/>" + emptyFields.join(", ");

    if (routelink == null) {
      var url = window.location.href;
      var params = new URLSearchParams(new URL(url).search);
      var pValue = params.get("p");
      var id = params.get("id");
      if (id != null) {
        if (routelink != null) {
          routelink = pValue + "&id=" + id;
        } else {
          routelink = pValue + "&id=" + id;
        }
      } else {
        routelink = pValue;
      }
    }
    showMessage(errorMessage, "alert", routelink);
  } else {
    var form = document.getElementById("myForm");
    var button = document.getElementById("submitButton");

    button.disabled = "true";
    form.submit(); // Formu gönder
    button.disabled = "false";
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
function checkRequiredCells(emptyFields) {
  var tables = document.getElementsByClassName("table");
  for (var i = 0; i < tables.length; i++) {
    var table = tables[i];
    var rows = table.getElementsByTagName("tr");
    for (var j = 0; j < rows.length; j++) {
      var cells = rows[j].getElementsByTagName("td");
      for (var k = 0; k < cells.length; k++) {
        var cell = cells[k];
        // td içindeki tüm form elemanlarını al
        var formElements = cell.querySelectorAll("input, select, textarea");
        for (var l = 0; l < formElements.length; l++) {
          var formElement = formElements[l];
          // Form elemanı zorunlu mu ve değeri boş mu?
          if (
            formElement.hasAttribute("required") &&
            formElement.value.trim() === ""
          ) {
            // Zorunlu ve boş olan form elemanının bulunduğu sütunun başlığını bul
            var columnHeader = getTableHeaderForColumn(table, k);
            if (columnHeader && !emptyFields.includes(columnHeader)) {
              emptyFields.push(columnHeader);
            }
          }
        }
      }
    }
  }
}

function getTableHeaderForColumn(table, columnIndex) {
  var headerRow = table.querySelector("thead tr");
  if (headerRow) {
    var headers = headerRow.getElementsByTagName("th");
    if (columnIndex < headers.length) {
      return headers[columnIndex].textContent.trim();
    }
  }
  return null;
}

// function getColumnName(tdElement) {
//   // tdElement'in içinde bulunduğu satırı bul
//   var row = tdElement.parentNode;

//   // Satırın içindeki tüm hücreleri al
//   var cells = row.getElementsByTagName("td");

//   // tdElement'in hangi sütuna ait olduğunu belirlemek için indeksini bul
//   for (var i = 0; i < cells.length; i++) {
//       if (cells[i] === tdElement) {
//           // Bu indeksteki sütun başlığını al
//           var columnHeader = row.parentNode.getElementsByTagName("th")[i].textContent;
//           return columnHeader;
//       }
//   }
//   // Eğer sütun başlığı bulunamazsa boş döndür
//   return "";
// }
// function isTdElement(element) {
//   return element instanceof HTMLTableCellElement;
// }

function showMessage(message, type, routelink) {
  var alertClass = "";
  var firstLetter = "";

  if (type === "success") {
    alertClass = "alert-success";
    firstLetter = "Başarılı!";
  } else if (type === "alert") {
    alertClass = "alert-danger";
    firstLetter = "Uyarı!";
  } else if (type === "error") {
    alertClass = "alert-warning";
    firstLetter = "Hata";
  } else if (type === "info") {
    alertClass = "alert-info";
    firstLetter = "Bilgi";
  }

  if (alertClass && message) {
    var alertMessage = $(
      '<div class="message alert ' +
        alertClass +
        ' alert-dismissible fade show">' +
        "<strong>" +
        firstLetter +
        "</strong> " +
        message +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        "</div>"
    );

    window.history.pushState({}, "", "index.php?p=" + routelink);
    $("#maincontainer").before(alertMessage);
    window.setTimeout(function () {
      alertMessage.fadeTo(500, 0).slideUp(500, function () {
        $(this).remove();
      });
    }, 5000);
  }
}

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

  $("#tProduct tbody").append(
    "<tr>" +
    '<td><a href="#" class="btn btn-sm"><i class="fa fa-arrows-alt"></i></a></td>' +
      '<td class="app-item-action"><a href="#" class="sil btn btn-sm btn-danger"><i class="fa fa-trash"></i></a></td>' +
      '<td class="app-item-number"><input class="form-control" type="text" value="' +
      sayac +
      '"></td>' +
      '<td class="mw-100p"><input type="text" id="stokKodu' +
      sayac +
      '" name="stokKodu[]" class="form-control" placeholder="Stok Kodu giriniz!"></td>' +
      "<td>" +
      '<div class="input-group m-0">' +
      '<input type="text" required name="urunAdi[]" id="urunAdi' +
      sayac +
      '"  placeholder="Ürün adını giriniz!" class="urunAdi form-control">' +
      '<button type="button" id="' +
      sayac +
      '" class="selectProduct btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#staticBackdrop"> ' +
      '<i class="fa fa-plus-circle"></i>' +
      "</button>" +
      "</div>" +
      "</td>" +
      '<td class="app-item-amount"><input type="number" required id="amount' +
      sayac +
      '" name="amount[]" autocomplete="off"  class="Adet form-control"></td>' +
      '<td class="app-item-unit">' +
      selectUnit +
      "</td>" +
      '<td class="app-item-price"><input type="text" required id="price' +
      sayac +
      '" name="price[]" class="BirimFiyat form-control"></td>' +
      '<td class="app-item-cur">' +
      selectmoneys +
      '</td> ' + rowdescription + 
      "</tr>"
  );
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
  $.ajax({
    url: "pages/1/customer-info.php",
    method: "POST",
    data: {
      id: customerID,
    },
    dataType: "JSON",
    success: function (response) {
      var compAuths = $("#compAuths");
      var payPeriod = $("#payPeriod");
      compAuths.val(response.yetkili);
      payPeriod.val(response.odemevadesi);
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

    // Sidebar desktop toggle and persistence functionality
    function syncMenuIconState() {
        if ($(window).width() > 1200) {
            if ($('html').hasClass('sidebar-collapsed')) {
                $('.menu-icon').removeClass('open');
            } else {
                $('.menu-icon').addClass('open');
            }
        }
    }

    // On page load, sync menu icon state
    syncMenuIconState();

    // On resize, sync menu icon state (override vendor script logic for desktop)
    $(window).on('resize', function() {
        syncMenuIconState();
    });

    // Handle click on desktop menu icon
    $('.menu-icon').on('click', function(e) {
        if ($(window).width() > 1200) {
            e.preventDefault();
            var isCollapsed = $('html').toggleClass('sidebar-collapsed').hasClass('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
            syncMenuIconState();
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

