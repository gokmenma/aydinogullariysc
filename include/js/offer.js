



// Ürün bilgisinin yanındaki buton
$(document).on("click", ".selectProduct", function () {
  var buttonId = $(this).attr("id");
  $("#rowID").val(buttonId);
});

//Yeni satır ekleme Butonu
$("#ekle").click(function () {
  var sayac = $("#rowNumberId");
  offerRowAdd(sayac.val());
  sayac.val(parseInt(sayac.val(), 10) + 1);
});

//Satır silme Butonu
$("#kalem_ekle").on("click", ".sil", function (e) {
  //user click on remove text
  e.preventDefault();
  var removedRowIndex = $(this).closest("tr").index() + 1;
  $(this).closest("tr").remove();
  updateAltToplam();
  // Kalan satırların sıra numaralarını güncelle
  $("#kalem_ekle tbody tr").each(function (index) {
    $(this)
      .find('input[name="satirno[]"]')
      .val(index + 1);
  });

  //sayac -= 1; // Sayacı güncelle
});

//Satırı yukarı taşıma butonu
$("#kalem_ekle").on("click", ".moveUp", function (e) {
  rowMove.call(this, "up");
});

//Satırı aşağı taşıma butonu
$("#kalem_ekle").on("click", ".moveDown", function (e) {
  rowMove.call(this, "down");
});

//Teklif ve teklif kalemleri ile birlikte kopyalanır
function offercopy(id) {
  swal
    .fire({
      title: "Emin misiniz?",
      text: id + " numaralı teklif kopyalanacaktır!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Evet,Kopyala!",
      cancelButtonText: "Vazgeç!"
    })
    .then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          type: "GET",
          url: "index.php?p=offers&st=offercopy&id=" + id,
          success: function (response) {
            // Handle success response (optional)
            Swal.fire({
              title: "Başarılı!",
              text: "Teklif başarı ile kopyalandı",
              icon: "success"
            }).then(() => {
              // Redirect to page
              window.location.href = "index.php?p=offers";
            });
          },
          error: function (xhr, status, error) {
            // Handle error if deletion fails (optional)
            console.error(xhr.responseText);
            Swal.fire({
              title: "Hata!",
              text: "Bir şeyler ters gitti!",
              icon: "error"
            });
          }
        });
      }
    });
}

function getOfferTemplate(name, type) {
  var id = name.val();

  var contentEditableElement = $("#offer" + type + "Content")
    .find("iframe")
    .contents()
    .find(".textarea_editor")
    .get(0);

  var ajaxpromise = $.ajax({
    type: "POST",
    url: "pages/1/offer-get-template.php",
    data: {
      id: id
    }
  }).then(function (response) {
    var data = JSON.parse(response);
    $(".offer" + type + "Content .wysihtml5-sandbox")
      .contents()
      .find("body")
      .html(data.content);
    $("#offer" + type + "Content textarea").val(data.content);
  });
}

function araToplamHesapla(tutar) {
  var iskonto = $("#iskonto").val();
  return formatNumber(tutar - iskonto);
}

function getProductInfoOffer() {
  var rowID = $("#rowID").val();
  var productId = $("#productName").val();
  var row = $(this).closest("tr");

  // Ajax isteğini yap
  var ajaxPromise = $.ajax({
    type: "POST",
    url: "pages/1/getProduct.php",
    data: {
      id: productId
    }
  });

  // Ajax isteği tamamlandığında çalışacak işlev
  ajaxPromise.then(function (response) {
    var data = JSON.parse(response);

    $("#urunAdi" + rowID).val(data.Adi);
    $("#stokKodu" + rowID).val(data.StokKodu);
    $("#buyprice" + rowID).val(data.AlisFiyati);
    $("#buycur" + rowID).val(data.buycur);
    $("#saleprice" + rowID).val(data.SatisFiyati);
    $("#salecur" + rowID).val(data.salecur);
    $("#unit" + rowID).val(data.unit);
    // console.log(data);
    $(".selectpicker").selectpicker("refresh");

   
    // Bootstrap 4 için modalı kapatma
    $('#staticBackdrop').modal('hide');
    //updateAltToplam();
  });
}

function offerRowAdd(sayac) {
  var form = $("#kalem_ekle")[0];

  fetchAndPopulateUnits(sayac).then((selectUnit) => {
    //**** SATIŞ PARA BİRİMLERİ*****//
    var saleselectmoneys =
      '<select required id="salecur' +
      sayac +
      '" name="salecur[]" ' +
      ' data-style="border bg-white" class="selectpicker form-control">';

    var moneys = ["TRY", "USD", "EUR"];
    for (var i = 0; i < moneys.length; i++) {
      saleselectmoneys +=
        '<option value="' + moneys[i] + '">' + moneys[i] + "</option>";
    }

    saleselectmoneys += "</select>";

    //****ALIŞ PARA BİRİMLERİ*****//
    var buyselectmoneys =
      '<select required id="buycur' +
      sayac +
      '" name="buycur[]" ' +
      'data-header="Birimler" data-style="border bg-white" class="selectpicker form-control">';

    for (var i = 0; i < moneys.length; i++) {
      buyselectmoneys +=
        '<option value="' + moneys[i] + '">' + moneys[i] + "</option>";
    }

    buyselectmoneys += "</select>";

    $("#kalem_ekle tbody").append(
      '<tr class="ui-state-default">' +
        '<td class="text-center" style="vertical-align: middle; cursor: grab;">' +
          '<span class="btn btn-sm text-muted p-0 drag-handle" title="Sıralamayı Değiştirmek İçin Sürükleyin">' +
            '<i class="fa fa-arrows-alt"></i>' +
          '</span>' +
        '</td>' +
        '<td class="app-item-action-2" style="vertical-align: middle; white-space: nowrap;">' +
          '<div class="btn-group btn-group-sm" role="group">' +
            '<button type="button" class="sil btn btn-danger btn-sm" title="Satırı Sil"><i class="fa fa-trash"></i></button>' +
            '<button type="button" class="btn btn-outline-primary btn-sm btn-clone-row" title="Satırı Klonla"><i class="fa fa-clone"></i></button>' +
          '</div>' +
        '</td>' +
        '<td class="app-item-number" style="vertical-align: middle;">' +
          '<input type="text" name="satirno[]" class="form-control text-center font-weight-bold" value="' + sayac + '" readonly style="background: #f8fafc; border-radius: 6px;">' +
        '</td>' +
        '<td class="app-item-stock" style="vertical-align: middle;">' +
          '<div class="product-autocomplete-wrap">' +
            '<input type="text" id="stokKodu' + sayac + '" name="stokKodu[]" class="form-control stokKodu-input" placeholder="Stok Kodu" autocomplete="off" style="border-radius: 6px;">' +
          '</div>' +
        '</td>' +
        '<td class="app-item-name" style="vertical-align: middle;">' +
          '<div class="product-autocomplete-wrap position-relative">' +
            '<input required type="text" name="urunAdi[]" id="urunAdi' + sayac + '" placeholder="Ürün adı yazarak arayın veya seçin..." class="form-control urunAdi-input" autocomplete="off" style="border-radius: 6px;">' +
            '<div class="product-autocomplete-results" style="display: none;"></div>' +
          '</div>' +
        '</td>' +
        '<td class="app-item-amount" style="vertical-align: middle;">' +
          '<input required id="amount' + sayac + '" autocomplete="off" name="amount[]" type="number" step="any" min="0" class="form-control text-center amount-input" placeholder="0" style="border-radius: 6px;">' +
        '</td>' +
        '<td class="app-item-unit" style="vertical-align: middle;">' +
          selectUnit +
        '</td>' +
        '<td class="app-item-price" style="vertical-align: middle;">' +
          '<input required id="saleprice' + sayac + '" name="saleprice[]" type="text" class="form-control text-right saleprice-input" autocomplete="off" placeholder="0.00" style="min-width: 85px; border-radius: 6px;">' +
        '</td>' +
        '<td class="app-item-cur" style="vertical-align: middle;">' +
          saleselectmoneys +
        '</td>' +
        '<td class="app-item-rowtotal" style="vertical-align: middle;">' +
          '<input type="text" id="total' + sayac + '" name="total[]" class="form-control text-right font-weight-bold row-total-input" value="0.00" readonly style="background: #f8fafc; border-radius: 6px; min-width: 85px;">' +
        '</td>' +
        '<td class="app-item-price" style="vertical-align: middle;">' +
          '<input id="buyprice' + sayac + '" name="buyprice[]" type="text" class="form-control text-right buyprice-input" autocomplete="off" placeholder="0.00" style="min-width: 85px; border-radius: 6px;">' +
        '</td>' +
        '<td class="app-item-cur" style="vertical-align: middle;">' +
          buyselectmoneys +
        '</td>' +
      '</tr>'
    );
    $(".selectpicker").selectpicker("refresh");
  });
}

function DeleteFile(id) {
  $.ajax({
    url: "pages/1/ajax.php?type=delete-file",
    type: "POST",
    data: { id: id },
    success: function (response) {
      var offerFile = $("#offerFile");
      var downloadfile = $("#downloadfile");
      var deleteFile = $("#deleteFile");
      var res = JSON.parse(response);

      if (res.status == "success") {
        offerFile.val("");
        downloadfile.remove();
        deleteFile.remove();
        offerFile.attr("type", "file");
      }
    },
    error: function (xhr, status, error) {
      console.error(xhr.responseText);
    }
  });
}

function fetchAndPopulateUnits(sayac) {
  return new Promise((resolve, reject) => {
    var selectUnit =
      '<select required id="unit' +
      sayac +
      '" name="unit[]" ' +
      'data-style="border bg-white" data-container="body" class="selectpicker form-control" required>';

    var formData = new FormData();
    formData.append("action", "getUnits");

    fetch("App/api/units.php", {
      method: "POST",
      body: formData
    })
      .then((response) => response.json())
      .then((data) => {
        var unit = data.data;

        for (var i = 0; i < unit.length; i++) {
          selectUnit +=
            '<option value="' + unit[i].title + '">' + unit[i].title + "</option>";
        }

        selectUnit += "</select>";
        //console.log(selectUnit); // selectUnit'i kontrol etmek için

        // selectUnit'i resolve ederek geri döndür
        resolve(selectUnit);
      })
      .catch((error) => {
        console.error("Error:", error);
        reject(error);
      });
  });
}


