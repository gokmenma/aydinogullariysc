    $(document).ready(function () {
       
        $('#company').change(function () {
            var selectedCompanyId = $(this).val();
            if (!selectedCompanyId) {
                $('#address').val('');
                return;
            }
            $.ajax({
                url: "pages/1/ajax.php",
                type: "POST",
                dataType: 'json',
                data: {
                    customer_id: selectedCompanyId,
                },
                success: function (data) {
                    if (!data) return;

                    $('#address').val((data.city || '') + " / " + (data.ilce || ''));
                    if (data.region !== undefined) {
                        $('#region').val(data.region);
                        if ($.fn.select2) {
                            $('#region').trigger('change.select2');
                        }
                        if ($.fn.selectpicker && $('#region').hasClass('selectpicker')) {
                            $('#region').selectpicker('refresh');
                        }
                    }

                    var offers = data.offers;
                    var options = '';
                   
                    if (offers && offers.length > 0) {
                        options += '<option value="">Onaylanmış Teklif Seçin</option>';
                        $.each(offers, function (index, offer) {
                            options += '<option value="' + offer.id + '">' + offer.offerNumber + '</option>';
                        });
                    } else {
                        options += '<option value="">Teklif No Yok</option>';
                    }
                    $('#offerno').html(options).val('');
                    if ($.fn.select2) {
                        $('#offerno').trigger('change');
                    }
                    if ($.fn.selectpicker && $('#offerno').hasClass('selectpicker')) {
                        $('#offerno').selectpicker("refresh");
                    }
                },
                error: function (xhr, status, error) {
                    console.error(error);                 
                }
            });
        });

    });
