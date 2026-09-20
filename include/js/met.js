// Mekanik Tesisat Kontrol Raporu (MET) JavaScript Modülü

function updateMetTableRowNumbers() {
    $("#metTable tbody tr").each(function (index) {
        var satirNo = index + 1;
        $(this).find('input[name="satirno[]"]').val(satirNo);
    });
}

function updateMetRowCount() {
    var count = $("#metTable tbody tr").length;
    $("#rowCountBadge").text(count + " Satır");
    $("#totalDeviceCount").text(count);
    updateMetTableRowNumbers();
}

function formatExcelDate(dateVal) {
    if (!dateVal) return "";
    if (typeof dateVal === 'string' && dateVal.includes('.')) return dateVal;
    
    let date;
    if (typeof dateVal === 'number') {
        date = new Date(Math.round((dateVal - 25569) * 86400 * 1000));
    } else {
        date = new Date(dateVal);
    }

    if (isNaN(date.getTime())) return dateVal;

    let day = ("0" + date.getDate()).slice(-2);
    let month = ("0" + (date.getMonth() + 1)).slice(-2);
    let year = date.getFullYear();

    return day + "." + month + "." + year;
}

function parseSelectVal(val, defaultVal) {
    if (val === undefined || val === null || val === "") return defaultVal || "1";
    var str = String(val).trim().toUpperCase();
    if (str === "1" || str === "UYGUN" || str === "YOK" || str === "EVET" || str === "VAR" && defaultVal === "pas_var") return "1";
    if (str === "0" || str === "UYGUN DEĞİL" || str === "UYGUN DEGIL" || str === "HAYIR" || str === "VAR" && defaultVal !== "pas_var") return "0";
    if (str === "2" || str === "DEĞERLENDİRME DIŞI" || str === "DEGERLENDIRME DISI" || str === "D.D" || str === "D.DIŞI") return "2";
    return defaultVal || "1";
}

function getMetRowTemplate(index, data) {
    data = data || {};
    var cinsi = data.cinsi || "";
    var bulundugu_kisim = data.bulundugu_kisim || "";
    var ozellikler = data.ozellikler || "";
    var control_date_closet = data.control_date_closet || "";
    var next_control_date_closet = data.next_control_date_closet || "";
    var vana_durum = data.vana_durum !== undefined ? String(data.vana_durum) : "1";
    var hortum_baglanti_durum = data.hortum_baglanti_durum !== undefined ? String(data.hortum_baglanti_durum) : "1";
    var levha_durum = data.levha_durum !== undefined ? String(data.levha_durum) : "1";
    var pas_durum = data.pas_durum !== undefined ? String(data.pas_durum) : "1";
    var kilit_durum = data.kilit_durum !== undefined ? String(data.kilit_durum) : "1";
    var hortum_durum = data.hortum_durum !== undefined ? String(data.hortum_durum) : "1";
    var basinc_degeri = data.basinc_degeri || "";
    var nozul_durum = data.nozul_durum !== undefined ? String(data.nozul_durum) : "1";
    var aciklama = data.aciklama || "";

    return '<tr tabindex="' + index + '">' +
        '<td class="text-center align-middle" style="width: 45px;">' +
            '<button type="button" class="sil btn btn-sm btn-delete-row" title="Satırı Sil">' +
                '<i class="fa fa-trash"></i>' +
            '</button>' +
        '</td>' +
        '<td class="text-center" style="width: 50px;">' +
            '<input type="text" class="form-control font-weight-bold text-center bg-light" name="satirno[]" value="' + index + '" readonly>' +
        '</td>' +
        '<td style="min-width: 130px;">' +
            '<input required type="text" autocomplete="off" class="form-control" name="cinsi[]" value="' + cinsi + '" placeholder="Cinsi">' +
        '</td>' +
        '<td style="min-width: 140px;">' +
            '<input required type="text" autocomplete="off" class="form-control" name="bulundugu_kisim[]" value="' + bulundugu_kisim + '" placeholder="Bulunduğu Kısım">' +
        '</td>' +
        '<td style="min-width: 100px;">' +
            '<input required type="text" autocomplete="off" class="form-control text-center" name="ozellikler[]" value="' + ozellikler + '" placeholder="Özellikler (Mt)">' +
        '</td>' +
        '<td style="min-width: 105px;">' +
            '<input required type="text" autocomplete="off" class="form-control text-center date-input" name="control_date_closet[]" value="' + control_date_closet + '" placeholder="Kontrol Tarihi">' +
        '</td>' +
        '<td style="min-width: 105px;">' +
            '<input required type="text" autocomplete="off" class="form-control text-center date-input" name="next_control_date_closet[]" value="' + next_control_date_closet + '" placeholder="Sonraki Kontrol">' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<select required name="vana_durum[]" class="form-control custom-select-status">' +
                '<option value="1" ' + (vana_durum === "1" ? 'selected' : '') + '>UYGUN</option>' +
                '<option value="0" ' + (vana_durum === "0" ? 'selected' : '') + '>UYGUN DEĞİL</option>' +
                '<option value="2" ' + (vana_durum === "2" ? 'selected' : '') + '>DEĞERLENDİRME DIŞI</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<select required name="hortum_baglanti_durum[]" class="form-control custom-select-status">' +
                '<option value="1" ' + (hortum_baglanti_durum === "1" ? 'selected' : '') + '>UYGUN</option>' +
                '<option value="0" ' + (hortum_baglanti_durum === "0" ? 'selected' : '') + '>UYGUN DEĞİL</option>' +
                '<option value="2" ' + (hortum_baglanti_durum === "2" ? 'selected' : '') + '>DEĞERLENDİRME DIŞI</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<select required name="levha_durum[]" class="form-control custom-select-status">' +
                '<option value="1" ' + (levha_durum === "1" ? 'selected' : '') + '>UYGUN</option>' +
                '<option value="0" ' + (levha_durum === "0" ? 'selected' : '') + '>UYGUN DEĞİL</option>' +
                '<option value="2" ' + (levha_durum === "2" ? 'selected' : '') + '>DEĞERLENDİRME DIŞI</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<select required name="pas_durum[]" class="form-control custom-select-status">' +
                '<option value="1" ' + (pas_durum === "1" ? 'selected' : '') + '>YOK</option>' +
                '<option value="0" ' + (pas_durum === "0" ? 'selected' : '') + '>VAR</option>' +
                '<option value="2" ' + (pas_durum === "2" ? 'selected' : '') + '>DEĞERLENDİRME DIŞI</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<select required name="kilit_durum[]" class="form-control custom-select-status">' +
                '<option value="1" ' + (kilit_durum === "1" ? 'selected' : '') + '>UYGUN</option>' +
                '<option value="0" ' + (kilit_durum === "0" ? 'selected' : '') + '>UYGUN DEĞİL</option>' +
                '<option value="2" ' + (kilit_durum === "2" ? 'selected' : '') + '>DEĞERLENDİRME DIŞI</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<select required name="hortum_durum[]" class="form-control custom-select-status">' +
                '<option value="1" ' + (hortum_durum === "1" ? 'selected' : '') + '>UYGUN</option>' +
                '<option value="0" ' + (hortum_durum === "0" ? 'selected' : '') + '>UYGUN DEĞİL</option>' +
                '<option value="2" ' + (hortum_durum === "2" ? 'selected' : '') + '>DEĞERLENDİRME DIŞI</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 90px;">' +
            '<input required type="text" autocomplete="off" class="form-control text-center" name="basinc_degeri[]" value="' + basinc_degeri + '" placeholder="Bar">' +
        '</td>' +
        '<td style="min-width: 110px;">' +
            '<select required name="nozul_durum[]" class="form-control custom-select-status">' +
                '<option value="1" ' + (nozul_durum === "1" ? 'selected' : '') + '>UYGUN</option>' +
                '<option value="0" ' + (nozul_durum === "0" ? 'selected' : '') + '>UYGUN DEĞİL</option>' +
                '<option value="2" ' + (nozul_durum === "2" ? 'selected' : '') + '>DEĞERLENDİRME DIŞI</option>' +
            '</select>' +
        '</td>' +
        '<td style="min-width: 160px;">' +
            '<input type="text" autocomplete="off" class="form-control" name="aciklama[]" value="' + aciklama + '" placeholder="Açıklama">' +
        '</td>' +
    '</tr>';
}

function addMetRow(data) {
    var rowCount = $("#metTable tbody tr").length + 1;
    var rowHtml = getMetRowTemplate(rowCount, data);
    $("#metTable tbody").append(rowHtml);
    updateMetRowCount();
}

// Tek satır ekle butonu
$(document).on("click", "#addRow", function () {
    addMetRow();
});

// Satır silme butonu
$("#metTable").on("click", ".sil", function (e) {
    e.preventDefault();
    var tbody = $("#metTable tbody");
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
    updateMetRowCount();
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
            addMetRow();
        }
    }
    $("#exampleModalCenter").modal("hide");
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
                var workbook = XLSX.read(data, { type: "binary", cellDates: true });
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

            // Tablodaki tek boş satır varsa temizleyelim
            var currentRows = $("#metTable tbody tr");
            var isFirstEmpty = false;
            if (currentRows.length === 1) {
                var firstCinsi = currentRows.find('input[name="cinsi[]"]').val();
                var firstKisim = currentRows.find('input[name="bulundugu_kisim[]"]').val();
                if (!firstCinsi && !firstKisim) {
                    isFirstEmpty = true;
                }
            }
            if (isFirstEmpty) {
                $("#metTable tbody").empty();
            }

            for (var i = 0; i < json_object.length; i++) {
                var row = json_object[i];
                var cinsiVal = row["cinsi"] || row["Cinsi"] || row["CİNSİ"] || row["Cihazın Cinsi"] || row["CİHAZIN CİNSİ"] || "";
                if (!cinsiVal && !row["bulundugu_kisim"] && !row["Bulunduğu Kısım"]) continue;

                addMetRow({
                    cinsi: cinsiVal,
                    bulundugu_kisim: row["bulundugu_kisim"] || row["Bulunduğu Kısım"] || row["BULUNDUĞU KISIM"] || row["Konum"] || "",
                    ozellikler: row["ozellikler"] || row["Özellikler"] || row["Özellikleri"] || row["ÖZELLİKLER"] || "",
                    control_date_closet: formatExcelDate(row["control_date_closet"] || row["Kontrol Tarihi"] || row["KONTROL TARİHİ"] || ""),
                    next_control_date_closet: formatExcelDate(row["next_control_date_closet"] || row["Sonraki Kontrol"] || row["Bir Sonraki Kontrol Tarihi"] || ""),
                    vana_durum: parseSelectVal(row["vana_durum"] || row["Vana Uygun Mu"] || row["VANA"]),
                    hortum_baglanti_durum: parseSelectVal(row["hortum_baglanti_durum"] || row["Hortum Bağlantıları"] || row["HORTUM BAĞLANTI"]),
                    levha_durum: parseSelectVal(row["levha_durum"] || row["Levha Uygun Mu"] || row["LEVHA"]),
                    pas_durum: parseSelectVal(row["pas_durum"] || row["Paslanma Var Mı"] || row["PASLANMA"], "1"),
                    kilit_durum: parseSelectVal(row["kilit_durum"] || row["Kilit Uygun Mu"] || row["KİLİT"]),
                    hortum_durum: parseSelectVal(row["hortum_durum"] || row["Hortum Durumu"] || row["HORTUM DURUMU"]),
                    basinc_degeri: row["basinc_degeri"] || row["Basınç Değeri"] || row["Basınç"] || row["BASINÇ"] || "",
                    nozul_durum: parseSelectVal(row["nozul_durum"] || row["Nozul Durumu"] || row["NOZUL"]),
                    aciklama: row["aciklama"] || row["Açıklama"] || row["AÇIKLAMA"] || row["Not"] || ""
                });
            }

            $("#uploadfromxlsModal").modal("hide");

            if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
                var sw = typeof Swal !== "undefined" ? Swal : swal;
                sw.fire({
                    title: "Başarılı!",
                    text: json_object.length + " adet dolap/cihaz kaydı başarıyla aktarıldı.",
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
        $("#metTable tbody").empty();
        addMetRow();
    };

    if (typeof Swal !== "undefined" || typeof swal !== "undefined") {
        var sw = typeof Swal !== "undefined" ? Swal : swal;
        sw.fire({
            title: "Emin misiniz?",
            text: "Tablodaki tüm dolap/cihaz satırları silinecektir!",
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

// Ek Dosyalar Satır Ekleme
$(document).on("click", "#addRowfile", function () {
    $("#metTablefile tbody").append(
        '<tr>' +
            '<td class="text-center align-middle" style="width: 45px;">' +
                '<button type="button" class="sil btn btn-sm btn-delete-row" title="Satırı Sil">' +
                    '<i class="fa fa-trash"></i>' +
                '</button>' +
            '</td>' +
            '<td style="min-width: 250px;"><input required type="text" autocomplete="off" class="form-control" name="attach_description[]" placeholder="Dosya Açıklaması"></td>' +
            '<td style="min-width: 200px;"><input required type="file" class="form-control font-12" name="report_attach[]" style="padding: 3px 6px; height: 32px;"></td>' +
        '</tr>'
    );
});

// Ek Dosya Satır Silme
$("#metTablefile").on("click", ".sil", function (e) {
    e.preventDefault();
    $(this).closest("tr").remove();
});

// Sayfa yüklendiğinde satır sayacını ve numaralarını başlat
$(document).ready(function () {
    updateMetRowCount();
});