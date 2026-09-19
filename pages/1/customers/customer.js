
// ─── Zorunlu alan tanımları ───
var CUSTOMER_REQUIRED = [
    { sel: '#company',                   label: 'Firma Adı',  type: 'input'  },
    { sel: '[name="cemail"]',            label: 'E-Posta',    type: 'input'  },
    { sel: '[name="categoryName"]',      label: 'Grup',       type: 'select' },
    { sel: '#il',                        label: 'İl',         type: 'select' },
    { sel: '#region',                    label: 'Bölge',      type: 'select' },
    { sel: '#cgsm',                      label: 'Telefon',    type: 'input'  },
    { sel: '[name="customer_address"]',  label: 'Adres',      type: 'input'  }
];

function clearCustomerErrors() {
    $('#customerForm .is-invalid').removeClass('is-invalid');
    $('#customerForm .bootstrap-select .dropdown-toggle').css({ 'border-color': '', 'border-width': '', 'background': '' });
    $('#customerForm .field-inline-error').remove();
}

function validateCustomerForm() {
    clearCustomerErrors();

    var errors = [];
    var firstErrorEl = null;

    CUSTOMER_REQUIRED.forEach(function (f) {
        var el = $('#customerForm').find(f.sel);
        if (!el.length) return;

        var val = el.val();
        var isEmpty = !val || val === '' || val === '0' || (Array.isArray(val) && val.length === 0);

        if (isEmpty) {
            errors.push(f.label);
            var errorHtml = '<div class="field-inline-error" style="color:#ef4444;font-size:12px;font-weight:500;margin-top:4px;">'
                          + '<i class="fa fa-exclamation-circle"></i> ' + f.label + ' zorunludur</div>';

            if (f.type === 'select') {
                var bsEl = el.next('.bootstrap-select');
                bsEl.find('.dropdown-toggle').css({ 'border-color': '#ef4444', 'border-width': '2px', 'background': '#fff5f5' });
                bsEl.after(errorHtml);
                if (!firstErrorEl) firstErrorEl = bsEl[0];
            } else {
                el.addClass('is-invalid').css({ 'border-color': '#ef4444', 'background': '#fff5f5' });
                el.after(errorHtml);
                if (!firstErrorEl) firstErrorEl = el[0];
            }
        }
    });

    if (errors.length > 0) {
        showCustomerToast('Zorunlu alanlar eksik: <b>' + errors.join(', ') + '</b>', 'error');
        if (firstErrorEl) firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }
    return true;
}

// Hata temizleme — alana geri dönüldüğünde
$(document).on('input', '#customerForm input, #customerForm textarea', function () {
    $(this).removeClass('is-invalid').css({ 'border-color': '', 'background': '' });
    $(this).next('.field-inline-error').remove();
});

$(document).on('change', '#customerForm select', function () {
    var bsEl = $(this).next('.bootstrap-select');
    bsEl.find('.dropdown-toggle').css({ 'border-color': '', 'border-width': '', 'background': '' });
    bsEl.next('.field-inline-error').remove();
});

// ─── Kaydet butonu ───
$(document).on("click", "#saveCustomer", function () {
    if (!validateCustomerForm()) return;

    var form = $("#customerForm");
    var formData = new FormData(form[0]);
    formData.append("action", "create");

    fetch("App/api/customer.php", {
        method: "POST",
        body: formData
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data.status === "success") {
            showCustomerToast(data.message, 'success');
        } else {
            Swal.fire({ title: "Hata!", text: data.message, icon: "error", confirmButtonText: "Tamam" });
        }
    })
    .catch(function (error) {
        console.error("Error:", error);
    });
});

// ─── Toast bildirimi ───
function showCustomerToast(message, type) {
    var existing = document.getElementById('customer-toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'customer-toast';
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;padding:14px 24px;border-radius:12px;color:#fff;font-size:14px;font-weight:500;box-shadow:0 8px 30px rgba(0,0,0,0.2);display:flex;align-items:center;gap:10px;max-width:420px;';

    if (type === 'error') {
        toast.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        toast.innerHTML = '<i class="fa fa-exclamation-circle" style="flex-shrink:0;font-size:18px;"></i><span>' + message + '</span>';
    } else {
        toast.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
        toast.innerHTML = '<i class="fa fa-check-circle" style="flex-shrink:0;font-size:18px;"></i><span>' + message + '</span>';
    }

    document.body.appendChild(toast);
    setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
    }, 4000);
}

// ─── Şehir değişince ilçe + bölge ───
$.getJSON("/src/scripts/il-bolge.json", function (sonuc) {
    let ilSelect = $("#il");

    $.each(sonuc, function (index, value) {
        var option = '<option value="' + value.il + '" data-subtext="' + value.bolge + '"';
        if (value.il === ilSelect.attr("title")) {
            option += ' selected';
        }
        option += '>' + value.il + '</option>';
        $("#il").append(option);
    });
});

$("#il").on("change", function () {
    var il = $(this).val();
    $("#ilce").prop("disabled", false);
    var bolge = $(this).find('option:selected').data('subtext');
    $("#region").val(bolge).trigger('change');

    $.getJSON("src/scripts/il-ilce.json", function (sonuc) {
        $("#ilce").empty();
        $.each(sonuc, function (index, value) {
            if (value.il == il) {
                $("#ilce").append('<option value="' + value.ilce + '">' + value.ilce + '</option>');
            }
        });
        $('#ilce').selectpicker('refresh');
    });
});
