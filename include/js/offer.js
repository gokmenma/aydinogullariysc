



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

    var moneys = ["TRY", "USD", "EUR"]; // "birimler" adı düzeltildi
    for (var i = 0; i < moneys.length; i++) {
      saleselectmoneys +=
        '<option value="' + moneys[i] + '">' + moneys[i] + "</option>";
    }

    saleselectmoneys += "</select>";
    //**** SATIŞ PARA BİRİMLERİ*****//

    //******************************/

    //****ALIŞ PARA BİRİMLERİ*****//
    var buyselectmoneys =
      '<select required id="buycur' +
      sayac +
      '" name="buycur[]" ' +
      'data-header="Birimler" data-style="border bg-white" class="selectpicker form-control">';

    var moneys = ["TRY", "USD", "EUR"]; // "birimler" adı düzeltildi
    for (var i = 0; i < moneys.length; i++) {
      buyselectmoneys +=
        '<option value="' + moneys[i] + '">' + moneys[i] + "</option>";
    }

    buyselectmoneys += "</select>";
    //**** ALIŞ PARA BİRİMLERİ*****//

    $("#kalem_ekle tbody").append(
      "<tr> " +
      '<td> <a href="#" class="btn btn-sm"> ' +
           '<i class="fa fa-arrows-alt"></i>' +
        '</a> '+
    '</td> '+
        '<td class="app-item-action-2">' +
        '<a href="#" class="sil btn btn-sm btn-danger">Sil</a>' +
        '<div class="dropdown d-inline">' +
        '<button class="btn btn-secondary btn-sm ml-1" type="button" id="dropdownMenu2" data-toggle="dropdown">' +
        '<i class="fa fa-list ml-1 mr-1"></i>' +
        "</button>" +
        '<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu2">' +
        '<button type="button" class="moveUp dropdown-item" type="button">' +
        '<i class="fa fa-arrow-up mr-2"></i>Yukarı Taşı</button>' +
        '<button type="button" class="moveDown dropdown-item" type="button">' +
        '<i class="fa fa-arrow-down mr-2"></i>Aşağı Taşı</button>' +
        "</div>" +
        "</div>" +
        "</td>" +
        '<td class="app-item-number"> ' +
        '<input type="text" name = "satirno[]" class="form-control" value="' +
        sayac +
        '">' +
        "</td>" +
        '<td ><input type="text" id="stokKodu' +
        sayac +
        '" name="stokKodu[]" class="form-control" placeholder="Stok Kodu giriniz!"></td>' +
        "<td>" +
        '<div class="input-group m-0">' +
        '<input required type="text" required name="urunAdi[]" id="urunAdi' +
        sayac +
        '"  placeholder="Ürün adını giriniz!" class="form-control">' +
        '<button type="button" id="' +
        sayac +
        '" class="btn btn-info btn-sm selectProduct" data-bs-toggle="modal" data-bs-target="#staticBackdrop"> ' +
        '<i class="fa fa-plus-circle"></i></button>' +
        "</div>" +
        "</td>" +
        '<td><input required id="amount' +
        sayac +
        '" autocomplete="off" name="amount[]" type="number"' +
        'class="form-control">' +
        "</td>" +
        "<td>" +
        selectUnit +
        "</td>" +
        '<td><input required id="saleprice' +
        sayac +
        '" name="saleprice[]" type="number" class="form-control" autocomplete="off">' +
        "</td>" +
        "<td >" +
        saleselectmoneys +
        "</td>" +
        '<td><input required type="text" id="total" name="total[]" class="form-control" readonly></td>' +
        '<td class="d-flex text-nowrap">' +
        '<input id="buyprice' +
        sayac +
        '" name="buyprice[]" type="number" class="form-control mr-1" autocomplete="off"></td> ' +
        "<td >" +
        buyselectmoneys +
        "</td>" +
        "</tr>"
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


