function validateForm() {
    var form = document.getElementById('myForm');
    var elements = form.elements;
    var emptyFields = [];

    for (var i = 0; i < elements.length; i++) {
        if (elements[i].hasAttribute('required') && elements[i].value.trim() === '') {
            
            var label = document.querySelector('label[for="' + elements[i].getAttribute('name') + '"]');
            var labelText = label ? label.textContent.trim() : elements[i].getAttribute('name'); // Eğer label varsa içeriğini al, yoksa input'un name özelliğini kullan
            // var labelText = label.textContent.trim(); // Label içeriğini alıyoruz
            emptyFields.push(labelText);
            // emptyFields.push(elements[i].name);
            
        }
    }
//console.log(emptyFields);

    if (emptyFields.length > 0) {
        var errorMessage = 'Lütfen zorunlu alanları doldurun: ' + emptyFields.join(', ');
        showMessage(errorMessage, 'alert');
    } else {
       
        SubmitForm();

    }
}

function SubmitForm() {
    var form = document.getElementById("myForm");
    setTimeout(function() {
       
       form.submit(); // Formu gönder 
    }, 2000); // 2000 milisaniye (2 saniye) sonra göster
    showMessage("İşlem Başarı ile tamamlandı!", "success"); // Mesajı göster
    
}

function showMessage(message, type) {
    var alertClass = '';
    var firstLetter = '';

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






