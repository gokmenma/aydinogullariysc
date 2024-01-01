function validateForm() {

    var form = document.getElementById('myForm');
    var elements = form.elements;
    var emptyFields = [];

    for (var i = 0; i < elements.length; i++) {
        if (elements[i].hasAttribute('required') && elements[i].value.trim() === '') {

            var label = document.querySelector('label[for="' + elements[i].getAttribute('name') + '"]');
            var labelText = label ? label.textContent.trim().replace(/[:\(\*\)]/g, '') : elements[i].getAttribute('name');
            emptyFields.push(labelText);

        }
    }
    console.log(emptyFields);

    if (emptyFields.length > 0) {
        var errorMessage = 'Lütfen zorunlu alanları doldurun: ' + emptyFields.join(', ');
        showMessage(errorMessage, 'alert');
    } else {
        var form = document.getElementById("myForm");
        form.submit(); // Formu gönder 
        //SubmitForm();

    }
}

function SubmitForm() {
      setTimeout(function () {
         showMessage("İşlem Başarı ile tamamlandı!", "success"); // Mesajı göster 
    }, 3000); // 2000 milisaniye (2 saniye) sonra göster


}

function showMessage(message, type) {
    var alertClass = '';
    var firstLetter = '';
//console.log(message);

    if (type === 'success') {
        alertClass = 'alert-success';
        firstLetter = "Başarılı!";
    } else if (type === 'alert') {
        alertClass = 'alert-danger';
        firstLetter = "Uyarı!";
    } else if (type === 'error') {
        alertClass = 'alert-warning';
        firstLetter = "Hata";
    } else if (type === 'info') {
        alertClass = 'alert-info';
        firstLetter = "Bilgi";
    }

    if (alertClass && message) {
        var alertMessage = $('<div class="message alert ' + alertClass + ' alert-dismissible fade show">' +
            '<strong>' + firstLetter + '</strong> ' + message +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            '</div>');

        $('#maincontainer').before(alertMessage);

        window.setTimeout(function () {
            alertMessage.fadeTo(500, 0).slideUp(500, function () {
                $(this).remove();
            });
        }, 3000);
    }
    
}


function deleteRecord(msg, ID, pLink) {
    // console.log(ID);
    Swal.fire({
        title: "Emin misiniz?",
        text: ID + ' numaralı ' + msg,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Evet,Sil!",
        cancelButtonText: "Vazgeç!"

    }).then((result) => {
        if (result.isConfirmed) {
            // If confirmed, trigger AJAX request to delete product
            $.ajax({
                type: "POST",
                url: "index.php?p=" + pLink + "&mode=delete&code=04md177&reg=true&md=active&id=" + ID, // PHP script for deletion
                data: {
                    id: ID
                },
                success: function (response) {
                    // Handle success response (optional)
                    Swal.fire({
                        title: "Başarılı!",
                        text: "Kayıt başarı ile silindi!",
                        icon: "success"
                    }).then(() => {
                        // Redirect to page
                        window.location.href = "index.php?p=" + pLink;
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


$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})



function SaveNewKategory(p_name, selectName) {
    var Addcategory = document.getElementById('Addcategory').value;
    if (Addcategory != "") {


        fetch('index.php?p=' + p_name, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'Addcategory=' + encodeURIComponent(Addcategory),
        })
            .then(response => {

                var selectElement = document.getElementById(selectName);
                var newOption = document.createElement('option');
                newOption.value = Addcategory;
                newOption.textContent = Addcategory;
                selectElement.appendChild(newOption);
                document.getElementById('Addcategory').value = '';

            })
            .catch(error => {
                // Hata durumunda burada işlemler yapabilirsiniz
            });
         
    }
}
