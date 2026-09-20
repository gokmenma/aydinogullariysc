// Hidrostatik Test Raporu (HST) JavaScript Modülü

function updateRowCount() {
    var count = $("#hstTable tbody tr").length;
    $("#rowCountBadge").text(count + " Satır");
    $("#totalDeviceCount").text(count);
}

function getRowTemplate(index, data) {
    data = data || {};
    var testno = data.testno || "";
    var kg = data.kg || "";
    var cinsi = data.cinsi || "";
    var imalatci_firma = data.imalatci_firma || "";
    var imal_tarihi = data.imal_tarihi || "";
    var serino = data.serino || "";
    var tse_belgesi = data.tse_belgesi !== undefined ? String(data.tse_belgesi) : "1";
    var yuzey_durumu = data.yuzey_durumu !== undefined ? String(data.yuzey_durumu) : "1";
    var sizdirmazlik_deneyi = data.sizdirmazlik_deneyi !== undefined ? String(data.sizdirmazlik_deneyi) : "1";
    var esneme_deneyi = data.esneme_deneyi !== undefined ? String(data.esneme_deneyi) : "1";
    var things = data.things || "";

    return '<tr tabindex="' + index + '">' +
        '<td class="text-center align-middle" style="width: 45px;">' +
            '<button type="button" class="sil btn btn-sm btn-delete-row" title="Satırı Sil">' +
                '<i class="fa fa-trash"></i>' +
            '</button>' +
        '</td>' +
        '<td style="min-width: 90px;">' +
            '<input required type="text" class="form-control font-weight-bold text-center" name="testno[]" value="' + testno + '" placeholder="Test No">' +
        '</td>' +
        '<td style="min-width: 80px;">' +
            '<input type="text" class="form-control text-center" name="kg[]" value="' + kg + '" placeholder="Kg">' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<input type="text" required autocomplete="off" class="form-control" name="cinsi[]" value="' + cinsi + '" placeholder="Cinsi">' +
        '</td>' +
        '<td style="min-width: 140px;">' +
            '<input type="text" required autocomplete="off" class="form-control" name="imalatci_firma[]" value="' + imalatci_firma + '" placeholder="İmalatçı Firma">' +
        '</td>' +
        '<td style="min-width: 95px;">' +
            '<input type="text" required autocomplete="off" class="form-control text-center imal" name="imal_tarihi[]" value="' + imal_tarihi + '" placeholder="İmal Tarihi">' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<input type="text" required autocomplete="off" class="form-control text-center" name="serino[]" value="' + serino + '" placeholder="Seri No">' +
        '</td>' +
        '<td style="min-width: 100px;">' +
            '<select required name="tse_belgesi[]" class="form-control custom-select-status">' +
                '<option value="">Seçiniz</option>' +
                '<option value="1" ' + (tse_belgesi === "1" ? 'selected' : '') + '>VAR</option>' +
                '<option value="0" ' + (tse_belgesi === "0" ? 'selected' : '') + '>YOK</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<select required name="yuzey_durumu[]" class="form-control custom-select-status">' +
                '<option value="">Seçiniz</option>' +
                '<option value="1" ' + (yuzey_durumu === "1" ? 'selected' : '') + '>OLUMLU</option>' +
                '<option value="0" ' + (yuzey_durumu === "0" ? 'selected' : '') + '>OLUMSUZ</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 120px;">' +
            '<select required name="sizdirmazlik_deneyi[]" class="form-control custom-select-status">' +
                '<option value="">Seçiniz</option>' +
                '<option value="1" ' + (sizdirmazlik_deneyi === "1" ? 'selected' : '') + '>VAR</option>' +
                '<option value="0" ' + (sizdirmazlik_deneyi === "0" ? 'selected' : '') + '>YOK</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<select required name="esneme_deneyi[]" class="form-control custom-select-status">' +
                '<option value="">Seçiniz</option>' +
                '<option value="1" ' + (esneme_deneyi === "1" ? 'selected' : '') + '>OLUMLU</option>' +
                '<option value="0" ' + (esneme_deneyi === "0" ? 'selected' : '') + '>OLUMSUZ</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 180px;">' +
            '<input type="text" autocomplete="off" class="form-control" name="things[]" value="' + things + '" placeholder="Düşünceler / Not">' +
        '</td>' +
    '</tr>';
}

function addRow(data) {
    var rowCount = $("#hstTable tbody tr").length + 1;
    var rowHtml = getRowTemplate(rowCount, data);
    $("#hstTable tbody").append(rowHtml);
    updateRowCount();
}

// Tek satır ekle butonu
$(document).on("click", "#addRow", function () {
    addRow();
});

// Satır silme butonu
$("#hstTable").on("click", ".sil", function (e) {
    e.preventDefault();
    var tbody = $("#hstTable tbody");
    if (tbody.find("tr").length <= 1) {
        if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
            var sw = typeof Swal !== "undefined" ? Swal : swal;
            sw.fire({
                title: "Bilgi",
                text: "Tabloda en az 1 satır bulunmalıdır.",
                icon: "info",
                confirmButtonText: "Tamam"
            });
        } else {
            alert("Tabloda en az 1 satır bulunmalıdır.");
        }
        return;
    }
    $(this).closest("tr").remove();
    updateRowCount();
});

// Çoklu satır ekleme butonu modal tetikleyici
$(document).on("click", "#addMultiRowModal", function () {
    var satir_sayisi = parseInt($("#eklenecek_satir_sayisi").val(), 10) || 0;

    if (satir_sayisi > 100) {
        if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
            var sw = typeof Swal !== "undefined" ? Swal : swal;
            sw.fire({
                title: "Uyarı!",
                text: "Bir seferde en fazla 100 satır ekleyebilirsiniz.",
                icon: "warning",
                confirmButtonText: "Tamam"
            });
        }
        satir_sayisi = 100;
    }

    if (satir_sayisi > 0) {
        for (var i = 0; i < satir_sayisi; i++) {
            addRow();
        }
    }
});

// Excel dosya uzantı kontrolü
$("#file_name").change(function () {
    var filename = $(this).val();
    var fileExtension = ["xlsx", "xls", "csv"];
    if (filename && $.inArray(filename.split(".").pop().toLowerCase(), fileExtension) === -1) {
        $("#lblWarning").show().text("Yalnızca .xlsx, .xls veya .csv uzantılı dosyalar yükleyebilirsiniz.");
        $("#file_name").val("");
    } else {
        $("#lblWarning").hide().text("");
    }
});

function readExcel(file) {
    return new Promise(function (resolve, reject) {
        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = e.target.result;
                var workbook = XLSX.read(data, { type: "binary" });
                var json_object = [];
                workbook.SheetNames.forEach(function (sheetName) {
                    var XL_row_object = XLSX.utils.sheet_to_row_object_array(workbook.Sheets[sheetName]);
                    json_object = json_object.concat(XL_row_object);
                });
                resolve(json_object);
            } catch (err) {
                reject(err);
            }
        };
        reader.onerror = function (ex) {
            reject(ex);
        };
        reader.readAsBinaryString(file);
    });
}

// Excel'den Yükle butonu
$(document).on("click", "#uploadFromXlsButton", function () {
    var fileInput = $("#file_name")[0];
    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
            var sw = typeof Swal !== "undefined" ? Swal : swal;
            sw.fire({
                title: "Dosya Seçilmedi",
                text: "Lütfen önce geçerli bir Excel dosyası seçiniz.",
                icon: "warning",
                confirmButtonText: "Tamam"
            });
        }
        return;
    }

    var file = fileInput.files[0];

    readExcel(file)
        .then(function (json_object) {
            if (!json_object || json_object.length === 0) {
                if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
                    var sw = typeof Swal !== "undefined" ? Swal : swal;
                    sw.fire({
                        title: "Veri Bulunamadı",
                        text: "Yüklenen dosyada okunabilir veri satırı bulunamadı.",
                        icon: "info",
                        confirmButtonText: "Tamam"
                    });
                }
                return;
            }

            // Tablodaki tek boş satır varsa temizleyip ekleyelim
            var currentRows = $("#hstTable tbody tr");
            var isFirstEmpty = false;
            if (currentRows.length === 1) {
                var firstTestNo = currentRows.find('input[name="testno[]"]').val();
                var firstCinsi = currentRows.find('input[name="cinsi[]"]').val();
                if (!firstTestNo && !firstCinsi) {
                    isFirstEmpty = true;
                }
            }
            if (isFirstEmpty) {
                $("#hstTable tbody").empty();
            }

            for (var i = 0; i < json_object.length; i++) {
                var row = json_object[i];
                addRow({
                    testno: row["testno"] || row["Test No"] || row["TEST NO"] || "",
                    kg: row["kg"] || row["Kg"] || row["KG"] || "",
                    cinsi: row["cinsi"] || row["Cinsi"] || row["CİNSİ"] || "",
                    imalatci_firma: row["imalatci_firma"] || row["İmalatçı Firma"] || row["İMALATÇI FİRMA"] || "",
                    imal_tarihi: row["imal_tarihi"] || row["İmal Tarihi"] || row["İMAL TARİHİ"] || "",
                    serino: row["serino"] || row["Seri No"] || row["SERİ NO"] || "",
                    tse_belgesi: (row["tse_belgesi"] !== undefined ? row["tse_belgesi"] : (row["TSE Belgesi"] === "VAR" || row["TSE"] === "1" ? "1" : "0")),
                    yuzey_durumu: (row["yuzey_durumu"] !== undefined ? row["yuzey_durumu"] : (row["Yüzey Durumu"] === "OLUMSUZ" || row["YÜZEY DURUMU"] === "0" ? "0" : "1")),
                    sizdirmazlik_deneyi: (row["sizdirmazlik_deneyi"] !== undefined ? row["sizdirmazlik_deneyi"] : (row["Sızdırmazlık"] === "YOK" || row["SIZDIRMAZLIK"] === "0" ? "0" : "1")),
                    esneme_deneyi: (row["esneme_deneyi"] !== undefined ? row["esneme_deneyi"] : (row["Esneme Deneyi"] === "OLUMSUZ" || row["ESNEME"] === "0" ? "0" : "1")),
                    things: row["things"] || row["Düşünceler"] || row["DÜŞÜNCELER"] || row["Not"] || ""
                });
            }

            if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
                var sw = typeof Swal !== "undefined" ? Swal : swal;
                sw.fire({
                    title: "Başarılı!",
                    text: json_object.length + " adet cihaz kaydı başarıyla aktarıldı.",
                    icon: "success",
                    confirmButtonText: "Harika"
                });
            }
        })
        .catch(function (error) {
            console.error(error);
            if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
                var sw = typeof Swal !== "undefined" ? Swal : swal;
                sw.fire({
                    title: "Hata!",
                    text: "Excel dosyası okunurken bir sorun oluştu: " + error.message,
                    icon: "error",
                    confirmButtonText: "Tamam"
                });
            }
        });
});

// Tüm Satırları Sil butonu
$(document).on("click", "#deleteAll", function () {
    var executeDelete = function () {
        $("#hstTable tbody").empty();
        addRow();
    };

    if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
        var sw = typeof Swal !== "undefined" ? Swal : swal;
        sw.fire({
            title: "Emin misiniz?",
            text: "Tablodaki tüm satırlar silinecektir!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Evet, Sil",
            cancelButtonText: "Vazgeç",
            confirmButtonColor: "#ef4444"
        }).then(function (result) {
            if (result.isConfirmed) {
                executeDelete();
            }
        });
    } else {
        if (confirm("Tablodaki tüm satırları silmek istediğinize emin misiniz?")) {
            executeDelete();
        }
    }
});

// Sayfa yüklendiğinde satır sayacını başlat
$(document).ready(function () {
    updateRowCount();

    // Form Gönderiminde Çift Tıklamayı Önleme ve Yükleniyor Göstergesi
    $("#myForm").on("submit", function (e) {
        var customerVal = $("#customer").val();
        if (!customerVal) {
            e.preventDefault();
            if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
                var sw = typeof Swal !== "undefined" ? Swal : swal;
                sw.fire({
                    title: "Eksik Bilgi!",
                    text: "Lütfen tüp sahibi firmayı seçiniz.",
                    icon: "warning",
                    confirmButtonText: "Tamam",
                    confirmButtonColor: "#f59e0b"
                });
            } else {
                alert("Lütfen tüp sahibi firmayı seçiniz.");
            }
            return false;
        }

        var $btn = $("#submitButton");
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');
    });
});

