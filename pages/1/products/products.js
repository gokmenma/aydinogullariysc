
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
  if ($("#tblProducts").length) {
    $("#tblProducts").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "api/products_datatables.php",
        type: "GET"
      },
      columns: [
        { data: 0, className: "text-center" }, // Sıra
        { data: 1 }, // Stok Kodu
        { data: 2 }, // Ürün/Hizmet Adı
        { data: 3 }, // Birimi
        { data: 4 }, // Alış Fiyatı
        { data: 5 }, // Satış Fiyatı
        { data: 6 }, // Açıklama
        { data: 7, className: "text-nowrap" }, // Kayıt Tarihi
        { data: 8, orderable: false, className: "text-center" } // İşlem
      ],
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      language: {
        url: "include/js/tr.json",
        processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Yükleniyor...</span>'
      },
      responsive: true,
      order: [[0, "asc"]],
      orderCellsTop: true,
      initComplete: function () {
        if (window.App && window.App.TableFilter) {
          App.TableFilter.attachToTable(this.api().table().node());
        }
      }
    });
  }
});

