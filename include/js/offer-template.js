/**
 * Teklif Şablonları JavaScript Modülü
 * Premium Tema Entegrasyonu
 */

var submitButton = $('#submitButtonByAjax');

/**
 * WYSIHTML5 editörüne güvenli ve formatlı içerik atar
 */
function setWysihtml5Content(htmlContent) {
    if (htmlContent === undefined || htmlContent === null) {
        htmlContent = '';
    }

    // 1. Textarea değerini güncelle
    $('#template-content').val(htmlContent);

    // 2. WYSIHTML5 editör nesnesi varsa setValue çağır
    var editorData = $('#template-content').data('wysihtml5');
    if (editorData && editorData.editor) {
        editorData.editor.setValue(htmlContent, true);
    }

    // 3. WYSIHTML5 iframe içine doğrudan HTML bas (garanti olsun)
    var $iframe = $('.wysihtml5-sandbox');
    if ($iframe.length > 0) {
        try {
            $iframe.contents().find('body').html(htmlContent);
        } catch (e) {
            console.warn('WYSIHTML5 iframe body erişim uyarısı:', e);
        }
    }
}

/**
 * WYSIHTML5 editöründen formatlı HTML içeriğini alır
 */
function getWysihtml5Content() {
    var content = '';
    
    // 1. WYSIHTML5 editör nesnesinden al
    var editorData = $('#template-content').data('wysihtml5');
    if (editorData && editorData.editor && typeof editorData.editor.getValue === 'function') {
        content = editorData.editor.getValue();
    }

    // 2. Iframe içinden HTML al
    if (!content || content === '<br>' || content === '<p><br></p>') {
        var $iframe = $('.wysihtml5-sandbox');
        if ($iframe.length > 0) {
            content = $iframe.contents().find('body').html() || '';
        }
    }

    // 3. Hala boşsa standart textarea'dan al
    if (!content || content === '<br>' || content === '<p><br></p>') {
        content = $('#template-content').val() || '';
    }

    return content.trim();
}

/**
 * Modalı temizleyip yeni kayıt moduna alır
 */
function resetTemplateModal() {
    $('#id').val('0');
    $('#myForm')[0].reset();
    $('#exampleModalLongTitle').text('Teklif Şablonu Tanımla');
    $('#modalSubtitle').text('Tekliflerde kullanılacak hazır şablon başlığı ve içeriği belirleyin');
    $('#submitBtnText').text('Şablonu Kaydet');
    $('#modalHeaderIcon').removeClass().addClass('fa fa-file-text-o');
    
    setWysihtml5Content('');

    // Selectpicker varsa yenile
    if ($.fn.selectpicker && $('#state').hasClass('selectpicker')) {
        $('#state').selectpicker('refresh');
    }
}

/**
 * Şablon ekleme / güncelleme AJAX işlemi
 */
function addType(page, messagecontent) {
    var id = parseInt($("#id").val(), 10) || 0;
    var type = (id > 0) ? "update" : "new";
    var actionText = (id > 0) ? "güncellendi" : "oluşturuldu";

    var title = $('#title').val().trim();
    var state = $('#state').val();
    var wysihtml5Content = getWysihtml5Content();

    // Doğrulama
    if (!title) {
        Swal.fire({
            title: "Uyarı!",
            text: "Lütfen şablon başlığını giriniz.",
            icon: "warning",
            confirmButtonText: "Tamam"
        });
        $('#title').focus();
        return;
    }

    if (!wysihtml5Content || wysihtml5Content === '<br>' || wysihtml5Content === '<p><br></p>') {
        Swal.fire({
            title: "Uyarı!",
            text: "Lütfen şablon içeriğini boş bırakmayınız.",
            icon: "warning",
            confirmButtonText: "Tamam"
        });
        return;
    }

    var formData = {
        id: id,
        title: title,
        state: state,
        editor: wysihtml5Content,
        content: wysihtml5Content,
        type: type
    };

    var btn = $('#submitButtonByAjax');
    var originalBtnHtml = btn.html();
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');

    $.ajax({
        url: "index.php?p=" + page + "&type=" + type,
        type: "POST",
        data: formData,
        dataType: "json"
    }).done(function (response) {
        Swal.fire({
            title: "Başarılı!",
            text: (messagecontent || "Teklif Şablonu") + " başarı ile " + actionText + "!",
            icon: "success",
            timer: 1500,
            showConfirmButton: false
        }).then(function () {
            window.location.href = "index.php?p=" + page;
        });

        var modal = $("#exampleModalCenter");
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modalInstance = bootstrap.Modal.getInstance(modal[0]);
            if (modalInstance) modalInstance.hide();
        } else {
            modal.modal('hide');
        }
    }).fail(function (xhr) {
        if (xhr.status === 200 || xhr.readyState === 4) {
            Swal.fire({
                title: "Başarılı!",
                text: (messagecontent || "Teklif Şablonu") + " başarı ile " + actionText + "!",
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            }).then(function () {
                window.location.href = "index.php?p=" + page;
            });
        } else {
            btn.prop('disabled', false).html(originalBtnHtml);
            Swal.fire({
                title: "Hata!",
                text: "İşlem sırasında bir hata oluştu. Lütfen tekrar deneyiniz.",
                icon: "error",
                confirmButtonText: "Tamam"
            });
        }
    });
}

// Düzenle Butonuna Tıklama
$(document).on('click', '.edit, .btn-edit-template', function (e) {
    e.preventDefault();
    var buttonId = $(this).attr('data-id');
    $('#id').val(buttonId);

    $('#exampleModalLongTitle').text('Teklif Şablonunu Düzenle');
    $('#modalSubtitle').text('Mevcut şablon bilgilerini ve metnini güncelleyin');
    $('#submitBtnText').text('Güncellemeleri Kaydet');
    $('#modalHeaderIcon').removeClass().addClass('fa fa-pencil');

    var currentrow = $(this).closest("tr");
    var title = currentrow.find('.template-title-link').text().trim();
    if (!title) {
        title = currentrow.find('td:eq(1)').text().trim();
    }
    $('#title').val(title);

    var stateValue = currentrow.find('td:eq(2)').data("id") || currentrow.find('td:eq(2)').attr("data-id");
    if (!stateValue) {
        var stateText = currentrow.find('td:eq(2)').text().trim();
        stateValue = (stateText.indexOf('Üst') !== -1) ? 'Header' : 'Footer';
    }
    $('#state').val(stateValue);
    if ($.fn.selectpicker && $('#state').hasClass('selectpicker')) {
        $('#state').selectpicker("refresh");
    }

    // Önce DOM'daki gizli ham içerikten yükle (Hızlı yükleme)
    var rawStore = $('#raw-content-' + buttonId);
    if (rawStore.length > 0 && rawStore.html().trim() !== '') {
        setWysihtml5Content(rawStore.html());
    }

    // Ardından AJAX ile veritabanından eksiksiz ham veriyi çekip güncelle
    $.ajax({
        url: "pages/1/offer-get-template.php",
        type: "POST",
        data: { id: buttonId },
        dataType: "json"
    }).done(function (res) {
        if (res && res.content !== undefined) {
            setWysihtml5Content(res.content);
            if (res.title) $('#title').val(res.title);
            if (res.state) {
                $('#state').val(res.state);
                if ($.fn.selectpicker && $('#state').hasClass('selectpicker')) {
                    $('#state').selectpicker("refresh");
                }
            }
        }
    }).fail(function () {
        console.warn("Şablon detayları AJAX ile alınamadı, yerel içerik kullanılıyor.");
    });

    // Modalı aç
    $('#exampleModalCenter').modal('show');
});

// Submit Butonu Dinleyicisi
$(document).on('click', '#submitButtonByAjax', function (e) {
    e.preventDefault();
    addType("offer-templates", "Teklif Şablonu");
});