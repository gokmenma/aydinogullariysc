$(document).on("click", "#addMultiRowModal", function () {
  var satir_sayisi = parseInt($("#eklenecek_satir_sayisi").val()) || 0;

  if (satir_sayisi > 100) {
    if (typeof swal !== "undefined" && swal.fire) {
      swal.fire({
        title: "Uyarı!",
        text: "Bir seferde en fazla 100 satır ekleyebilirsiniz.",
        icon: "warning",
        confirmButtonText: "Tamam",
      });
    } else {
      alert("Bir seferde en fazla 100 satır ekleyebilirsiniz.");
    }
    satir_sayisi = 100;
  }
  if (satir_sayisi > 0) {
    for (var i = 0; i < satir_sayisi; i++) {
      addRow();
    }
  }

  if ($.fn.selectpicker) {
    $(".selectpicker").selectpicker("refresh");
  }

  $(".rpr-date").each(function () {
    if ($.fn.datepicker) {
      $(this).datepicker({
        language: "tr",
        dateFormat: "dd-mm-yyyy",
        autoclose: true,
      });
    }
  });
});

function addRow() {
  var rowCount = $("#yscTable tbody tr").length + 1;

  var newRow = 
    "<tr tabindex='" + rowCount + "' class='align-middle'>" +
      "<td class='text-center' style='width: 44px;'>" +
        "<button type='button' class='sil btn btn-delete-row' data-tooltip='Satırı Sil'>" +
          "<i class='fa fa-trash-o'></i>" +
        "</button>" +
      "</td>" +
      "<td style='width: 55px;'>" +
        "<input required type='text' class='form-control text-center satir_no' id='cihazno' value='" + rowCount + "' name='cihazno[]'>" +
      "</td>" +
      "<td style='min-width: 150px;'>" +
        "<input type='text' class='form-control region text-left' name='cihazbolge[]' placeholder='Bölge / Mahit'>" +
      "</td>" +
      "<td style='min-width: 150px;'>" +
        "<input required type='text' class='form-control region text-left' name='cinsi[]' placeholder='Örn: 6 KG KKT'>" +
      "</td>" +
      "<td style='min-width: 85px;' data-tooltip='aa/yyyy şeklinde giriniz'>" +
        "<input type='text' autocomplete='off' class='form-control text-center filling-date' placeholder='aa/yyyy' name='dolumtarihi[]'>" +
      "</td>" +
      "<td style='min-width: 85px;'>" +
        "<input type='text' autocomplete='off' class='form-control text-center expiration-date' placeholder='aa/yyyy' name='sonkullanimtarihi[]'>" +
      "</td>" +
      "<td style='min-width: 95px;'>" +
        "<input type='text' autocomplete='off' class='form-control text-center date-input rpr-date' placeholder='gg-aa-yyyy' name='kontoltarihi1[]'>" +
      "</td>" +
      "<td style='min-width: 95px;'>" +
        "<input type='text' autocomplete='off' class='form-control text-center date-input rpr-date' placeholder='gg-aa-yyyy' name='kontoltarihi2[]'>" +
      "</td>" +
      "<td style='min-width: 110px;'>" +
        "<div class='input-group m-0 p-0'>" +
          "<input name='islemkontroltarihi1[]' type='text' class='form-control islemkontroltarihi text-center' placeholder='İşlem'>" +
          "<div class='input-group-append'>" +
            "<button class='btn dropdown-toggle' type='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'></button>" +
            "<div class='dropdown-menu dropdown-menu-right'>" +
              "<a class='dropdown-item btn cursor-pointer'>Basınç</a>" +
              "<a class='dropdown-item btn cursor-pointer'>Kontrol</a>" +
              "<a class='dropdown-item btn cursor-pointer'>Dolum</a>" +
            "</div>" +
          "</div>" +
        "</div>" +
      "</td>" +
      "<td style='min-width: 110px;'>" +
        "<div class='input-group m-0 p-0'>" +
          "<input name='islemkontroltarihi2[]' value='' type='text' class='form-control islemkontroltarihi text-center' placeholder='İşlem'>" +
          "<div class='input-group-append'>" +
            "<button class='btn dropdown-toggle' type='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'></button>" +
            "<div class='dropdown-menu dropdown-menu-right'>" +
              "<a class='dropdown-item btn cursor-pointer'>Basınç</a>" +
              "<a class='dropdown-item btn cursor-pointer'>Kontrol</a>" +
              "<a class='dropdown-item btn cursor-pointer'>Dolum</a>" +
            "</div>" +
          "</div>" +
        "</div>" +
      "</td>" +
      "<td style='min-width: 100px;'>" +
        "<select name='dismuhafaza[]' class='form-control custom-select-status'>" +
          "<option value=''>Seçiniz</option>" +
          "<option value='0'>UYGUN DEĞİL</option>" +
          "<option selected value='1'>UYGUN</option>" +
        "</select>" +
      "</td>" +
      "<td style='min-width: 100px;'>" +
        "<select name='cevrekontrolu[]' class='form-control custom-select-status'>" +
          "<option value=''>Seçiniz</option>" +
          "<option value='0'>UYGUN DEĞİL</option>" +
          "<option selected value='1'>UYGUN</option>" +
        "</select>" +
      "</td>" +
      "<td style='min-width: 100px;'>" +
        "<select name='pimkontrolu[]' class='form-control custom-select-status'>" +
          "<option value=''>Seçiniz</option>" +
          "<option value='0'>UYGUN DEĞİL</option>" +
          "<option selected value='1'>UYGUN</option>" +
        "</select>" +
      "</td>" +
      "<td style='min-width: 100px;'>" +
        "<select name='manometrekontrolu[]' class='form-control custom-select-status'>" +
          "<option value=''>Seçiniz</option>" +
          "<option value='0'>UYGUN DEĞİL</option>" +
          "<option selected value='1'>UYGUN</option>" +
        "</select>" +
      "</td>" +
      "<td style='min-width: 100px;'>" +
        "<select name='hortumkontrolu[]' class='form-control custom-select-status'>" +
          "<option value=''>Seçiniz</option>" +
          "<option value='0'>UYGUN DEĞİL</option>" +
          "<option selected value='1'>UYGUN</option>" +
        "</select>" +
      "</td>" +
      "<td style='min-width: 100px;'>" +
        "<select name='talimatkontrolu[]' class='form-control custom-select-status'>" +
          "<option value=''>Seçiniz</option>" +
          "<option value='0'>UYGUN DEĞİL</option>" +
          "<option selected value='1'>UYGUN</option>" +
        "</select>" +
      "</td>" +
      "<td style='min-width: 100px;'>" +
        "<select name='agirlikkontrolu[]' class='form-control custom-select-status'>" +
          "<option value=''>Seçiniz</option>" +
          "<option value='0'>UYGUN DEĞİL</option>" +
          "<option selected value='1'>UYGUN</option>" +
        "</select>" +
      "</td>" +
    "</tr>";

  $("#yscTable tbody").append(newRow);
}

$(document).on("click", "#addRow", function () {
  addRow();

  if ($.fn.selectpicker) {
    $(".selectpicker").selectpicker("refresh");
  }

  $(".rpr-date").each(function () {
    if ($.fn.datepicker) {
      $(this).datepicker({
        language: "tr",
        dateFormat: "dd-mm-yyyy",
        autoclose: true,
      });
    }
  });
});

// Satır silme Butonu
$("#yscTable").on("click", ".sil", function (e) {
  e.preventDefault();
  $(this).closest("tr").remove();

  var i = 1;
  $("#yscTable tbody tr").each(function () {
    $(this).find(".satir_no").val(i);
    $(this).attr("tabindex", i);
    i++;
  });
});

$(document).on("keyup", ".filling-date", function () {
  var row = $(this).closest("tr");
  var expdate = row.find(".expiration-date");
  var currentDate = $(this).val().trim();
  var parts = currentDate.split(/[\/\-\.]/);
  
  if (parts.length >= 2) {
    var month = parseInt(parts[0], 10);
    var year = parseInt(parts[1], 10);

    if (!isNaN(month) && !isNaN(year) && year.toString().length === 4) {
      year += 4;
      var newDate = month.toString().padStart(2, "0") + "/" + year.toString();
      expdate.val(newDate);
    }
  }
});

$(document).on("click", ".dropdown-item", function () {
  var text = $(this).text().trim();
  $(this).closest(".input-group").find(".islemkontroltarihi").val(text);
});

$(document).keydown(function (event) {
  var table = $("#yscTable");
  var rows = table.find("tr");
  var focusedRowIndex = -1;
  var focusedColIndex = -1;

  rows.each(function (rowIndex) {
    $(this)
      .find("td")
      .each(function (colIndex) {
        if ($(this).find("input:focus, select:focus").length > 0) {
          focusedRowIndex = rowIndex;
          focusedColIndex = colIndex;
          return false;
        }
      });
    if (focusedRowIndex >= 0) {
      return false;
    }
  });

  switch (event.key) {
    case "ArrowUp":
      if (focusedRowIndex > 0) {
        rows
          .eq(focusedRowIndex - 1)
          .find("td")
          .eq(focusedColIndex)
          .find("input, select")
          .focus();
      }
      break;
    case "ArrowDown":
      if (focusedRowIndex < rows.length - 1) {
        rows
          .eq(focusedRowIndex + 1)
          .find("td")
          .eq(focusedColIndex)
          .find("input, select")
          .focus();
      }
      break;
    case "ArrowLeft":
      if (focusedColIndex > 0) {
        rows
          .eq(focusedRowIndex)
          .find("td")
          .eq(focusedColIndex - 1)
          .find("input, select")
          .focus();
      }
      break;
    case "ArrowRight":
      var maxColIndex = rows.eq(focusedRowIndex).find("td").length - 1;
      if (focusedColIndex < maxColIndex) {
        rows
          .eq(focusedRowIndex)
          .find("td")
          .eq(focusedColIndex + 1)
          .find("input, select")
          .focus();
      }
      break;
  }
});

$(document).ready(function () {
  if ($.fn.selectpicker) {
    $(".selectpicker").selectpicker();
    $(".selectpicker").on("shown.bs.select", function () {
      $(this).closest(".bootstrap-select").find(".dropdown-toggle").addClass("focused");
    });
    $(".selectpicker").on("hidden.bs.select", function () {
      $(this).closest(".bootstrap-select").find(".dropdown-toggle").removeClass("focused");
    });
  }
});

$("#file_name").change(function () {
  var filename = $(this).val();
  var fileExtension = ["xlsx", "xls", "csv"];
  if ($.inArray(filename.split(".").pop().toLowerCase(), fileExtension) === -1) {
    $("#lblWarning").show().text("Yalnızca xls veya xlsx uzantılı dosyalar yükleyebilirsiniz.");
    $("#file_name").val("");
  } else {
    $("#lblWarning").hide();
  }
});

function readExcel(file) {
  return new Promise((resolve, reject) => {
    var reader = new FileReader();
    reader.onload = function (e) {
      var data = e.target.result;
      var workbook = XLSX.read(data, {
        type: "binary",
      });
      var json_object = [];
      workbook.SheetNames.forEach(function (sheetName) {
        var XL_row_object = XLSX.utils.sheet_to_row_object_array(
          workbook.Sheets[sheetName]
        );
        json_object = json_object.concat(XL_row_object);
      });
      resolve(json_object);
    };
    reader.onerror = function (ex) {
      reject(ex);
    };
    reader.readAsBinaryString(file);
  });
}

$(document).on("click", "#uploadFromXlsButton", function () {
  var file_name = $("#file_name");
  if (!file_name[0].files || file_name[0].files.length === 0) {
    return;
  }
  var file = file_name[0].files[0];

  readExcel(file)
    .then(function (json_object) {
      if (json_object && json_object.length > 0) {
        for (var i = 0; i < json_object.length; i++) {
          var row = json_object[i];
          addRow();
          var lastRow = $("#yscTable tbody tr:last");
          lastRow.find("input, select").each(function () {
            var nameAttr = $(this).attr("name");
            if (nameAttr) {
              var name = nameAttr.replace("[]", "");
              if (row[name] !== undefined) {
                var value = row[name];
                if ($(this).is("select")) {
                  $(this).val(value);
                } else {
                  $(this).val(value);
                }
              }
            }
          });
        }
      }
    })
    .catch(function (error) {
      console.error(error);
    });
  setTooltip();
});

$(document).on("click", "#deleteAll", function () {
  if (typeof swal !== "undefined" && swal.fire) {
    swal
      .fire({
        title: "Emin misiniz?",
        text: "Tüm cihaz satırlarını silmek istediğinize emin misiniz?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Evet, Temizle",
        cancelButtonText: "Vazgeç",
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#64748b",
      })
      .then((result) => {
        if (result.isConfirmed) {
          $("#yscTable tbody tr").remove();
          addRow();
        }
      });
  } else {
    if (confirm("Tüm satırları silmek istediğinize emin misiniz?")) {
      $("#yscTable tbody tr").remove();
      addRow();
    }
  }
});

function setTooltip() {
  $(".region").each(function () {
    var selfText = $(this).val();
    if (selfText) {
      $(this).attr("data-tooltip", selfText);
    }
  });
}