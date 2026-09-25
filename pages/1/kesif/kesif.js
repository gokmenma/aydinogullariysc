let apiUrl = "pages/1/kesif/api.php";

$(document).ready(function () {
  // Aktif firmalar arasında arama/seçim yapılabilir; listede olmayan bir
  // firma adı da yazılarak mevcut serbest giriş akışı korunur.
  if ($.fn.select2) {
    $("#firma").select2({
      placeholder: "Firma arayın, seçin veya yazın",
      allowClear: true,
      tags: true,
      dropdownParent: $("#kesifModal"),
      width: "100%",
      createTag: function (params) {
        var term = $.trim(params.term || "");
        return term ? { id: term, text: term, newTag: true } : null;
      }
    });

    $("#gidecek_kisi, #formun_bulundugu_kisi").each(function () {
      var placeholder = this.id === "gidecek_kisi"
        ? "Personel seçin veya yazın"
        : "Formun bulunduğu kişiyi seçin veya yazın";

      $(this).select2({
        placeholder: placeholder,
        allowClear: true,
        tags: true,
        dropdownParent: $("#kesifModal"),
        width: "100%",
        createTag: function (params) {
          var term = $.trim(params.term || "");
          return term ? { id: term, text: term, newTag: true } : null;
        }
      });
    });

    $("#durum").select2({
      minimumResultsForSearch: Infinity,
      dropdownParent: $("#kesifModal"),
      width: "100%"
    });
  }

  function setTaggedSelectValue(selector, value) {
    var cleanValue = $.trim(value || "");
    var $select = $(selector);
    if (cleanValue && $select.find("option").filter(function () {
      return $(this).val() === cleanValue;
    }).length === 0) {
      $select.append(new Option(cleanValue, cleanValue, true, true));
    }
    $select.val(cleanValue || null).trigger("change");
  }

  $("#firma").on("change", function () {
    // Programatik değişikliklerde ve elle yazılan etiketlerde konum verisi yoktur.
    var $selectedOption = $(this).find("option:selected");
    var location = $.trim($selectedOption.attr("data-location") || "");
    if (location) {
      $("#konum").val(location).trigger("input");
    }
  });

  // KPI Summary Section Toggle & LocalStorage
  $("html").removeClass("kpi-kesif-collapsed-early");
  var isKpiCollapsed = localStorage.getItem("aydinogullari_kpi_kesif_collapsed") === "true";
  if (isKpiCollapsed) {
    $("#kpiSummarySection").addClass("is-collapsed");
    $("#toggleKpiSummary i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
  }

  $(document).on("click", "#toggleKpiSummary", function () {
    var $kpi = $("#kpiSummarySection");
    var willCollapse = !$kpi.hasClass("is-collapsed");

    if (willCollapse) {
      $kpi.addClass("is-collapsed");
      $(this).find("i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
      localStorage.setItem("aydinogullari_kpi_kesif_collapsed", "true");
    } else {
      $kpi.removeClass("is-collapsed");
      $(this).find("i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
      localStorage.setItem("aydinogullari_kpi_kesif_collapsed", "false");
    }
  });

  // Tablo Başlığındaki Arama Satırını Temizle
  $("#kesifTable").find("tr.search-input-row").remove();
  $(document).on("draw.dt init.dt", "#kesifTable", function () {
    $("#kesifTable").find("tr.search-input-row").remove();
  });

  // Tooltip Yönetimi (Bootstrap 4 / Body Delegation - Tablo ve kart içi kesilmeleri önler)
  try {
    $("body").tooltip({
      selector: '#kesifTable [data-tooltip]',
      title: function () {
        var text = $(this).attr("data-tooltip");
        return (text && text.trim() !== "-" && text.trim() !== ".") ? text : "";
      },
      container: "body",
      boundary: "window",
      placement: "auto",
      trigger: "hover"
    });
  } catch (err) {
    console.warn("Tooltip init error:", err);
  }

  // Tablo Yenileme Butonu
  $(document).on("click", "#btnRefreshKesif", function () {
    var $btn = $(this);
    var $icon = $btn.find("i");
    $icon.addClass("fa-spin");
    setTimeout(function () {
      location.reload();
    }, 300);
  });

  // Dropzone Etkileşimleri (Drag & Drop)
  var $dropzone = $("#kesifDropzone");
  var $fileInput = $("#kesif_gorseller");

  $dropzone.on("dragover dragenter", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass("dragover");
  });

  $dropzone.on("dragleave dragend drop", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass("dragover");
  });

  $dropzone.on("drop", function (e) {
    var dt = e.originalEvent.dataTransfer;
    if (dt && dt.files && dt.files.length) {
      $fileInput[0].files = dt.files;
      renderSelectedFiles(dt.files);
    }
  });

  $fileInput.on("change", function () {
    renderSelectedFiles(this.files);
  });

  function renderSelectedFiles(files) {
    var $container = $("#selected_files_list");
    $container.empty();

    if (!files || !files.length) return;

    for (var i = 0; i < files.length; i++) {
      var file = files[i];
      var ext = file.name.split(".").pop().toLowerCase();
      var icon = "fa-file-o";
      if (["jpg", "jpeg", "png", "webp", "gif"].includes(ext)) {
        icon = "fa-file-image-o text-primary";
      } else if (ext === "pdf") {
        icon = "fa-file-pdf-o text-danger";
      } else if (["doc", "docx"].includes(ext)) {
        icon = "fa-file-word-o text-info";
      } else if (["xls", "xlsx"].includes(ext)) {
        icon = "fa-file-excel-o text-success";
      }

      var sizeKb = Math.round(file.size / 1024);
      var sizeText = sizeKb > 1024 ? (sizeKb / 1024).toFixed(1) + " MB" : sizeKb + " KB";

      $container.append(`
        <div class="selected-file-chip">
          <i class="fa ${icon} chip-icon"></i>
          <span class="chip-name" title="${file.name}">${file.name}</span>
          <span class="chip-size">(${sizeText})</span>
        </div>
      `);
    }
  }

  // Yeni keşif ekleme modalını aç
  $("#kesifModal").on("show.bs.modal", function () {
    if ($("#kesif_id").val() === "") {
      $("#kesifModalLabel").text("Yeni Keşif Ekle");
      $("#kesifForm")[0].reset();
      $("#firma").val(null).trigger("change");
      $("#gidecek_kisi, #formun_bulundugu_kisi").val(null).trigger("change");
      $("#durum").val("bekliyor").trigger("change");
      $("#current_gorseller").empty();
      $("#selected_files_list").empty();
    }
  });

  // Modal kapandığında formu sıfırla
  $("#kesifModal").on("hidden.bs.modal", function () {
    $("#kesif_id").val("");
    $("#kesifForm")[0].reset();
    $("#firma").val(null).trigger("change");
    $("#gidecek_kisi, #formun_bulundugu_kisi").val(null).trigger("change");
    $("#durum").val("bekliyor").trigger("change");
    $("#current_gorseller").empty();
    $("#selected_files_list").empty();
  });

  // Düzenleme modalını açan fonksiyon
  window.openKesifEditModal = function (kesif_id) {
    if (!kesif_id) return;

    if ($("#detaylarModal").is(":visible")) {
      $("#detaylarModal").modal("hide");
    }

    $("#kesifModalLabel").text("Keşfi Düzenle");

    // AJAX ile keşif verilerini getir
    $.ajax({
      url: apiUrl,
      type: "GET",
      data: {
        action: "get",
        id: kesif_id,
      },
      dataType: "json",
      success: function (data) {
        if (data.success) {
          var kesif = data.data;
          $("#kesif_id").val(kesif.id);
          $("#kesif_tarihi").val(formatDateTime(kesif.kesif_tarihi));
          setTaggedSelectValue("#gidecek_kisi", kesif.gidecek_kisi);
          var firma = $.trim(kesif.firma || "");
          if (firma && $("#firma option").filter(function () { return $(this).val() === firma; }).length === 0) {
            $("#firma").append(new Option(firma, firma, true, true));
          }
          $("#firma").val(firma).trigger("change");
          $("#yapilacak_is").val(kesif.yapilacak_is);
          $("#konum").val(kesif.konum);
          setTaggedSelectValue("#formun_bulundugu_kisi", kesif.formun_bulundugu_kisi);
          $("#durum").val(kesif.durum || "bekliyor").trigger("change");
          $("#kesif_sonu_notu").val(kesif.kesif_sonu_notu || "");

          // Seçilen yeni dosyaları sıfırla
          $("#selected_files_list").empty();
          $("#kesif_gorseller").val("");

          // Mevcut görselleri göster
          $("#current_gorseller").empty();
          if (kesif.gorseller) {
            try {
              var gorseller = typeof kesif.gorseller === "string" ? JSON.parse(kesif.gorseller) : kesif.gorseller;
              if (Array.isArray(gorseller) && gorseller.length > 0) {
                gorseller.forEach(function (rawSrc) {
                  var src = rawSrc.startsWith("/") ? rawSrc : "/" + rawSrc;
                  var ext = src.split(".").pop().toLowerCase();
                  var isImg = ["jpg", "jpeg", "png", "gif", "webp", "svg"].includes(ext);

                  if (isImg) {
                    $("#current_gorseller").append(`
                      <div class="gorsel-item-card">
                          <img src="${src}" alt="Görsel" onclick="window.open('${src}','_blank')" onerror="this.onerror=null; this.src='assets/images/no-image.png';">
                          <button type="button" class="btn-delete-gorsel" data-kesif-id="${kesif.id}" data-src="${rawSrc}" title="Görseli Sil">
                              <i class="fa fa-times"></i>
                          </button>
                      </div>
                    `);
                  } else {
                    var icon = "fa-file-text-o text-secondary";
                    if (ext === "pdf") icon = "fa-file-pdf-o text-danger";
                    else if (["doc", "docx"].includes(ext)) icon = "fa-file-word-o text-primary";
                    else if (["xls", "xlsx"].includes(ext)) icon = "fa-file-excel-o text-success";

                    $("#current_gorseller").append(`
                      <div class="gorsel-item-card">
                          <a href="${src}" target="_blank" rel="noopener" class="doc-preview">
                              <i class="fa ${icon} font-20 mb-1"></i>
                              <span class="font-10 text-truncate text-dark font-weight-600">${ext.toUpperCase()}</span>
                          </a>
                          <button type="button" class="btn-delete-gorsel" data-kesif-id="${kesif.id}" data-src="${rawSrc}" title="Dosyayı Sil">
                              <i class="fa fa-times"></i>
                          </button>
                      </div>
                    `);
                  }
                });
              }
            } catch (err) {}
          }

          $("#kesifModal").modal("show");
        }
      },
      error: function () {
        Swal.fire({
          icon: "error",
          title: "Hata!",
          text: "Keşif verileri yüklenirken bir hata oluştu!",
          confirmButtonText: "Tamam",
          confirmButtonColor: "#d33",
        });
      },
    });
  };

  // Düzenle butonuna tıklandığında
  $(document).on("click", ".edit-btn, #cmActionEdit, #detail_edit_btn", function (e) {
    e.preventDefault();
    hideContextMenu();
    var kesif_id = $(this).data("id") || $("#kesifContextMenu").data("id");
    window.openKesifEditModal(kesif_id);
  });

  // Detay görüntüleme modalını açan fonksiyon
  window.openKesifDetailModal = function (kesif_id, enc_id) {
    if (!kesif_id) return;
    enc_id = enc_id || "";

    // AJAX ile keşif detaylarını getir
    $.ajax({
      url: apiUrl,
      type: "GET",
      data: {
        action: "get",
        id: kesif_id,
      },
      dataType: "json",
      success: function (data) {
        if (data.success) {
          var kesif = data.data;

          // Durum badge
          var durum_badge = "";
          if (kesif.durum == "bekliyor") {
            durum_badge = '<span class="badge-soft-warning"><i class="fa fa-clock-o mr-1"></i>Bekliyor</span>';
          } else if (kesif.durum == "iptal_edildi") {
            durum_badge = '<span class="badge-soft-danger"><i class="fa fa-times-circle mr-1"></i>İptal Edildi</span>';
          } else if (kesif.durum == "kesif_tamamlandi") {
            durum_badge = '<span class="badge-soft-info"><i class="fa fa-check mr-1"></i>Keşif Tamamlandı</span>';
          } else if (kesif.durum == "teklif_hazirlandi") {
            durum_badge = '<span class="badge-soft-purple"><i class="fa fa-pencil-square-o mr-1"></i>Teklif Hazırlandı</span>';
          } else if (kesif.durum == "teklif_gonderildi") {
            durum_badge = '<span class="badge-soft-success"><i class="fa fa-paper-plane mr-1"></i>Teklif Gönderildi</span>';
          } else {
            durum_badge = '<span class="badge badge-secondary">' + (kesif.durum || "-") + "</span>";
          }

          // Detaylar modalını doldur
          $("#detail_firma").text(kesif.firma || "-");
          $("#detail_durum").html(durum_badge);
          $("#detail_kesif_tarihi").text(kesif.kesif_tarihi ? formatDateTime(kesif.kesif_tarihi) : "-");
          $("#detail_gidecek_kisi").text(kesif.gidecek_kisi || "-");
          $("#detail_form_kimde").text(kesif.formun_bulundugu_kisi || "-");
          $("#detail_konum").text(kesif.konum || "-");
          $("#detail_yapilacak_is").text(kesif.yapilacak_is || "-");
          $("#detail_kesif_sonu_notu").text(kesif.kesif_sonu_notu || "Henüz keşif sonu notu eklenmemiş.");
          $("#detail_kayit_tarihi").text(kesif.kayit_tarihi ? formatDateTime(kesif.kayit_tarihi) : "-");
          $("#detail_kayit_yapan").text(kesif.kullanici_adi || "Bilinmiyor");

          // Güncelleme bilgileri
          if (kesif.guncelleme_tarihi) {
            $("#detail_guncelleme_tarihi").text(formatDateTime(kesif.guncelleme_tarihi));
            $("#detail_guncelleyen_kullanici").text(kesif.guncelleyen_adi || "Yetkili");
          } else {
            $("#detail_guncelleme_tarihi").text("Henüz Güncellenmedi");
            $("#detail_guncelleyen_kullanici").text("-");
          }

          // Modal butonları
          $("#detail_edit_btn").data("id", kesif.id);
          $("#detail_map_btn").data("id", kesif.id);
          if (kesif.konum) {
            $("#detail_map_btn").show();
          } else {
            $("#detail_map_btn").hide();
          }

          // PDF Buton linki
          if (enc_id) {
            $("#detail_pdf_btn").attr("href", "/pages/1/kesif/view-pdf.php?id=" + enc_id).show();
          } else {
            $("#detail_pdf_btn").hide();
          }

          // Görselleri ve belgeleri göster
          $("#detail_gorseller").empty();
          if (kesif.gorseller) {
            try {
              var gorseller = typeof kesif.gorseller === "string" ? JSON.parse(kesif.gorseller) : kesif.gorseller;
              if (Array.isArray(gorseller) && gorseller.length > 0) {
                gorseller.forEach(function (rawSrc) {
                  var src = rawSrc.startsWith("/") ? rawSrc : "/" + rawSrc;
                  var ext = src.split(".").pop().toLowerCase();
                  var isImg = ["jpg", "jpeg", "png", "gif", "webp", "svg"].includes(ext);

                  if (isImg) {
                    $("#detail_gorseller").append(`
                      <a href="${src}" target="_blank" rel="noopener" class="d-inline-block m-1">
                        <img src="${src}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; transition: transform 0.15s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" onerror="this.onerror=null; this.src='assets/images/no-image.png';">
                      </a>
                    `);
                  } else {
                    var icon = "fa-file-text-o text-secondary";
                    if (ext === "pdf") icon = "fa-file-pdf-o text-danger";
                    else if (["doc", "docx"].includes(ext)) icon = "fa-file-word-o text-primary";
                    else if (["xls", "xlsx"].includes(ext)) icon = "fa-file-excel-o text-success";

                    $("#detail_gorseller").append(`
                      <a href="${src}" target="_blank" rel="noopener" class="d-inline-flex flex-column align-items-center justify-content-center border rounded bg-white p-2 m-1 text-decoration-none shadow-sm" style="width: 100px; height: 100px;">
                        <i class="fa ${icon} fa-2x mb-2"></i>
                        <span class="font-10 text-dark text-truncate w-100 text-center font-weight-600">${ext.toUpperCase()}</span>
                      </a>
                    `);
                  }
                });
              } else {
                $("#detail_gorseller").html('<span class="text-muted font-12"><i class="fa fa-info-circle mr-1"></i> Kayıtlı görsel veya ek belge bulunmuyor.</span>');
              }
            } catch (e) {
              $("#detail_gorseller").html('<span class="text-muted font-12"><i class="fa fa-info-circle mr-1"></i> Kayıtlı görsel veya ek belge bulunmuyor.</span>');
            }
          } else {
            $("#detail_gorseller").html('<span class="text-muted font-12"><i class="fa fa-info-circle mr-1"></i> Kayıtlı görsel veya ek belge bulunmuyor.</span>');
          }

          // Modalı aç
          $("#detaylarModal").modal("show");
        } else {
          Swal.fire({
            icon: "error",
            title: "Hata!",
            text: data.message,
            confirmButtonText: "Tamam",
            confirmButtonColor: "#d33",
          });
        }
      },
      error: function () {
        Swal.fire({
          icon: "error",
          title: "Hata!",
          text: "Keşif detayları yüklenirken bir hata oluştu!",
          confirmButtonText: "Tamam",
          confirmButtonColor: "#d33",
        });
      },
    });
  };

  // Detayları Görüntüle butonuna tıklandığında
  $(document).on("click", ".view-btn, #cmActionView", function (e) {
    e.preventDefault();
    hideContextMenu();
    var kesif_id = $(this).data("id") || $("#kesifContextMenu").data("id");
    var enc_id = $(this).closest("tr").data("enc-id") || $("#kesifContextMenu").data("enc-id") || "";
    window.openKesifDetailModal(kesif_id, enc_id);
  });

  // Adresi Google Haritalar'da görüntüle
  $(document).on("click", ".map-view-btn, #cmActionMap", function (e) {
    e.preventDefault();
    hideContextMenu();
    var kesif_id = $(this).data("id") || $("#kesifContextMenu").data("id");
    var address = $(this).closest("tr").data("konum") || $("#kesifContextMenu").data("konum") || $("#detail_konum").text();

    if ((!address || address === "-") && kesif_id) {
      $.ajax({
        url: apiUrl,
        type: "GET",
        data: { action: "get", id: kesif_id },
        dataType: "json",
        success: function (data) {
          if (data.success && data.data && data.data.konum) {
            openMapModal(data.data.konum);
          } else {
            Swal.fire({
              icon: "warning",
              title: "Konum Bilgisi Yok",
              text: "Bu keşif kaydında geçerli bir adres bilgisi bulunmuyor.",
              confirmButtonText: "Tamam",
            });
          }
        },
      });
    } else if (address && address !== "-") {
      openMapModal(address);
    }
  });

  function openMapModal(address) {
    var mapsUrl = "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(address);
    var embedUrl = "https://maps.google.com/maps?q=" + encodeURIComponent(address) + "&output=embed";
    $("#mapsLink").attr("href", mapsUrl);
    $("#mapFrame").attr("src", embedUrl);
    $("#mapModal").modal("show");
  }

  // Sağ Tık (Context Menu) Etkileşimi
  $(document).on("contextmenu", "#kesifTable tbody tr", function (e) {
    e.preventDefault();
    var $row = $(this);
    var kesifId = $row.data("id");
    var encId = $row.data("enc-id");
    var firmaName = $row.data("firma") || "Keşif #" + kesifId;
    var konum = $row.data("konum") || "";

    if (!kesifId) return;

    $("#kesifTable tbody tr").removeClass("context-menu-active");
    $row.addClass("context-menu-active");

    var $menu = $("#kesifContextMenu");
    $menu.data("id", kesifId);
    $menu.data("enc-id", encId);
    $menu.data("konum", konum);

    $("#cmKesifTitle").text(firmaName);
    $("#cmActionPdf").attr("href", "/pages/1/kesif/view-pdf.php?id=" + encId);

    if (!konum) {
      $("#cmActionMap").hide();
    } else {
      $("#cmActionMap").show();
    }

    var menuWidth = 230;
    var menuHeight = 220;
    var posX = e.clientX;
    var posY = e.clientY;

    if (posX + menuWidth > $(window).width()) {
      posX = $(window).width() - menuWidth - 10;
    }
    if (posY + menuHeight > $(window).height()) {
      posY = $(window).height() - menuHeight - 10;
    }

    $menu.css({
      top: posY + "px",
      left: posX + "px",
      display: "block",
    });
  });

  function hideContextMenu() {
    $("#kesifContextMenu").hide();
    $("#kesifTable tbody tr").removeClass("context-menu-active");
  }

  $(document).on("click", function (e) {
    if (!$(e.target).closest("#kesifContextMenu").length) {
      hideContextMenu();
    }
  });

  $(document).on("keydown", function (e) {
    if (e.key === "Escape") {
      hideContextMenu();
    }
  });

  // Görsel silme butonu
  $(document).on("click", ".btn-delete-gorsel", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var btn = $(this);
    var kesifId = btn.data("kesif-id");
    var src = btn.data("src");

    Swal.fire({
      title: "Görseli Sil",
      text: "Bu görseli silmek istediğinize emin misiniz?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Evet, Sil!",
      cancelButtonText: "İptal",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: apiUrl,
          type: "POST",
          data: {
            action: "delete_image",
            id: kesifId,
            image_path: src,
          },
          dataType: "json",
          success: function (data) {
            if (data.success) {
              btn.closest(".gorsel-item-card").fadeOut(300, function () {
                $(this).remove();
              });
              Swal.fire({
                icon: "success",
                title: "Silindi!",
                text: "Görsel başarıyla silindi.",
                timer: 1500,
                showConfirmButton: false,
              });
            } else {
              Swal.fire({
                icon: "error",
                title: "Hata!",
                text: data.message,
              });
            }
          },
          error: function () {
            Swal.fire({
              icon: "error",
              title: "Hata!",
              text: "Görsel silinirken bir hata oluştu!",
            });
          },
        });
      }
    });
  });

  // Formu gönder (Yeni Ekle veya Güncelle)
  $("#kesifForm").on("submit", function (e) {
    e.preventDefault();

    var formData = new FormData(this);
    var action = $("#kesif_id").val() ? "update" : "create";
    formData.append("action", action);

    var btn = $("#btnSaveKesif");
    var btnText = btn.find(".btn-text");
    var originalText = btnText.text();

    $.ajax({
      url: apiUrl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      beforeSend: function () {
        btn.prop("disabled", true);
        btnText.html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');
      },
      success: function (data) {
        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Başarılı!",
            text: data.message,
            confirmButtonText: "Tamam",
            confirmButtonColor: "#0284c7",
          }).then((result) => {
            if (result.isConfirmed) {
              $("#kesifModal").modal("hide");
              location.reload();
            }
          });
        } else {
          btn.prop("disabled", false);
          btnText.text(originalText);
          Swal.fire({
            icon: "error",
            title: "Hata!",
            text: data.message,
            confirmButtonText: "Tamam",
            confirmButtonColor: "#d33",
          });
        }
      },
      error: function () {
        btn.prop("disabled", false);
        btnText.text(originalText);
        Swal.fire({
          icon: "error",
          title: "Hata!",
          text: "İşlem sırasında bir hata oluştu!",
          confirmButtonText: "Tamam",
          confirmButtonColor: "#d33",
        });
      },
    });
  });

  // Sil butonuna tıklandığında
  $(document).on("click", ".delete-btn, #cmActionDelete", function (e) {
    e.preventDefault();
    hideContextMenu();
    var kesif_id = $(this).data("id") || $("#kesifContextMenu").data("id");
    if (!kesif_id) return;

    Swal.fire({
      title: "Emin misiniz?",
      text: "Bu keşifi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Evet, Sil!",
      cancelButtonText: "İptal",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: apiUrl,
          type: "POST",
          data: {
            action: "delete",
            id: kesif_id,
          },
          dataType: "json",
          success: function (data) {
            if (data.success) {
              Swal.fire({
                icon: "success",
                title: "Silindi!",
                text: data.message,
                confirmButtonText: "Tamam",
                confirmButtonColor: "#0284c7",
              }).then((result) => {
                if (result.isConfirmed) {
                  location.reload();
                }
              });
            } else {
              Swal.fire({
                icon: "error",
                title: "Hata!",
                text: data.message,
                confirmButtonText: "Tamam",
                confirmButtonColor: "#d33",
              });
            }
          },
          error: function () {
            Swal.fire({
              icon: "error",
              title: "Hata!",
              text: "Silme işlemi sırasında bir hata oluştu!",
              confirmButtonText: "Tamam",
              confirmButtonColor: "#d33",
            });
          },
        });
      }
    });
  });

  function formatDateTime(dateStr) {
    if (!dateStr) return "";
    var date = new Date(dateStr.replace(" ", "T"));
    if (isNaN(date.getTime())) return dateStr;
    var pad = function (n) { return n < 10 ? "0" + n : n; };
    return (
      pad(date.getDate()) +
      "." +
      pad(date.getMonth() + 1) +
      "." +
      date.getFullYear() +
      " " +
      pad(date.getHours()) +
      ":" +
      pad(date.getMinutes()) +
      ":" +
      pad(date.getSeconds())
    );
  }

  // URL parametresine göre otomatik Keşif modalı açma
  try {
    var urlParams = new URLSearchParams(window.location.search);
    var actionParam = urlParams.get("action");
    var idParam = urlParams.get("id");
    if (idParam) {
      setTimeout(function () {
        if (actionParam === "edit") {
          window.openKesifEditModal(idParam);
        } else {
          window.openKesifDetailModal(idParam);
        }
      }, 150);
    }
  } catch (err) {}
});

