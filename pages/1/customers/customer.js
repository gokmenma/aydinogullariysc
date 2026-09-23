
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
    $('#customerForm .select2-container .select2-selection').css({ 'border-color': '', 'border-width': '', 'background': '' });
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
                var s2El = el.next('.select2-container');
                var bsEl = el.next('.bootstrap-select');

                if (s2El.length) {
                    s2El.find('.select2-selection').css({ 'border-color': '#ef4444', 'border-width': '2px', 'background': '#fff5f5' });
                    s2El.after(errorHtml);
                    if (!firstErrorEl) firstErrorEl = s2El[0];
                } else if (bsEl.length) {
                    bsEl.find('.dropdown-toggle').css({ 'border-color': '#ef4444', 'border-width': '2px', 'background': '#fff5f5' });
                    bsEl.after(errorHtml);
                    if (!firstErrorEl) firstErrorEl = bsEl[0];
                } else {
                    el.addClass('is-invalid').css({ 'border-color': '#ef4444', 'background': '#fff5f5' });
                    el.after(errorHtml);
                    if (!firstErrorEl) firstErrorEl = el[0];
                }
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
    if (bsEl.length) {
        bsEl.find('.dropdown-toggle').css({ 'border-color': '', 'border-width': '', 'background': '' });
    }
    var s2El = $(this).next('.select2-container');
    if (s2El.length) {
        s2El.find('.select2-selection').css({ 'border-color': '', 'border-width': '', 'background': '' });
    }
    $(this).siblings('.field-inline-error').remove();
    $(this).next('.select2-container').next('.field-inline-error').remove();
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

// ─── Select2 İlklendirme ve Şehir/İlçe/Bölge Senkronizasyonu ───
$(document).ready(function () {
    if ($.fn.select2) {
        $('#customerForm .select2').each(function () {
            var $this = $(this);
            $this.select2({
                width: '100%',
                placeholder: $this.data('placeholder') || 'Seçim Yapınız',
                allowClear: true
            });
        });
    }
});

// Şehir değişince ilçe + bölge
$("#il").on("change", function () {
    var il = $(this).val();
    var bolge = $(this).find('option:selected').data('subtext');

    if (bolge) {
        $("#region").val(bolge).trigger('change');
    }

    var $ilce = $("#ilce");
    $ilce.prop("disabled", false);

    if (!il) {
        $ilce.empty().append('<option value="">İlçe Seçiniz</option>').trigger('change');
        return;
    }

    $.getJSON("src/scripts/il-ilce.json", function (sonuc) {
        var currentIlceVal = $ilce.val();
        $ilce.empty().append('<option value="">İlçe Seçiniz</option>');
        
        $.each(sonuc, function (index, value) {
            if (value.il == il) {
                var isSelected = (value.ilce === currentIlceVal) ? ' selected' : '';
                $ilce.append('<option value="' + value.ilce + '"' + isSelected + '>' + value.ilce + '</option>');
            }
        });

        if ($.fn.selectpicker && $ilce.hasClass('selectpicker')) {
            $ilce.selectpicker('refresh');
        }
        $ilce.trigger('change.select2');
    });
});

// ─── Özet Kartları Göster / Gizle Mantığı (LocalStorage & Early State) ───
$(document).ready(function () {
    var STATS_STORAGE_KEY = 'aydinogullari_customer_manage_stats_collapsed';
    var $statsSection = $('#customerStatsGrid');
    var $toggleBtn = $('#toggleCustomerStats');

    if ($statsSection.length && $toggleBtn.length) {
        function updateStatsToggleState(isCollapsed, animate) {
            document.documentElement.classList.remove('customer-stats-collapsed-early');
            if (isCollapsed) {
                if (animate) {
                    $statsSection.stop(true, true).slideUp(200);
                } else {
                    $statsSection.hide();
                }
                $toggleBtn.find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $toggleBtn.attr('title', 'Özet Kartlarını Göster');
            } else {
                if (animate) {
                    $statsSection.stop(true, true).slideDown(200, function() {
                        $(this).css('display', 'grid');
                    });
                } else {
                    $statsSection.css('display', 'grid').show();
                }
                $toggleBtn.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $toggleBtn.attr('title', 'Özet Kartlarını Gizle');
            }
        }

        var isSavedCollapsed = localStorage.getItem(STATS_STORAGE_KEY) === 'true';
        updateStatsToggleState(isSavedCollapsed, false);

        $(document).off('click', '#toggleCustomerStats').on('click', '#toggleCustomerStats', function (e) {
            e.preventDefault();
            var currentlyCollapsed = $statsSection.is(':hidden');
            var newState = !currentlyCollapsed;
            localStorage.setItem(STATS_STORAGE_KEY, newState ? 'true' : 'false');
            updateStatsToggleState(newState, true);
        });
    }
});

// ─── Haritadan Adres Seçim Modülü (Leaflet & OpenStreetMap) ───
(function () {
    var map = null;
    var marker = null;
    var isMapInitialized = false;
    var selectedLocation = {
        lat: null,
        lng: null,
        city: '',
        district: '',
        neighbourhood: '',
        road: '',
        houseNumber: '',
        postcode: '',
        formattedAddress: ''
    };

    // Türkçe karakter duyarlı büyük harfe dönüştürme
    function toTurkishUpper(str) {
        if (!str) return '';
        return str.replace(/i/g, 'İ').replace(/ı/g, 'I').toUpperCase().trim();
    }

    // Temiz il ismi çıkarma (örn: "Bursa İli" -> "BURSA")
    function cleanCityName(cityName) {
        if (!cityName) return '';
        var cleaned = cityName.replace(/\s*(ili|il|province|state|büyükşehir belediyesi)\s*/gi, '').trim();
        return toTurkishUpper(cleaned);
    }

    // Temiz ilçe ismi çıkarma (örn: "Nilüfer İlçesi" -> "NİLÜFER")
    function cleanDistrictName(districtName) {
        if (!districtName) return '';
        var cleaned = districtName.replace(/\s*(ilçesi|ilçe|district|town|municipality)\s*/gi, '').trim();
        return toTurkishUpper(cleaned);
    }

    // Haritayı İlklendir
    function initCustomerMap(initialLat, initialLng, zoom) {
        if (typeof L === 'undefined') return;

        // Leaflet varsayılan ikon yolları
        if (L.Icon && L.Icon.Default) {
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: 'src/plugins/leaflet/images/marker-icon-2x.png',
                iconUrl: 'src/plugins/leaflet/images/marker-icon.png',
                shadowUrl: 'src/plugins/leaflet/images/marker-shadow.png'
            });
        }

        var defaultLat = initialLat || 40.1885; // Bursa / Nilüfer varsayılan
        var defaultLng = initialLng || 29.0610;
        var defaultZoom = zoom || 13;

        if (!map) {
            map = L.map('customerAddressMap', {
                center: [defaultLat, defaultLng],
                zoom: defaultZoom,
                zoomControl: true
            });

            // Google Maps Sokak Katmanı (Yüksek netlik, Türkçe yer adları, filigransız)
            L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                subdomains: ['0', '1', '2', '3'],
                attribution: '&copy; Google Maps'
            }).addTo(map);

            // Sürüklenebilir Marker
            marker = L.marker([defaultLat, defaultLng], {
                draggable: true,
                autoPan: true
            }).addTo(map);

            // Haritaya tıklanınca marker'ı taşı ve adresi getir
            map.on('click', function (e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;
                marker.setLatLng([lat, lng]);
                reverseGeocode(lat, lng);
            });

            // Marker sürüklenmesi bitince adresi getir
            marker.on('dragend', function () {
                var pos = marker.getLatLng();
                reverseGeocode(pos.lat, pos.lng);
            });

            isMapInitialized = true;
        } else {
            map.setView([defaultLat, defaultLng], defaultZoom);
            marker.setLatLng([defaultLat, defaultLng]);
        }

        setTimeout(function() {
            if (map) map.invalidateSize();
        }, 100);

        // İlk koordinat için adres çözümle
        reverseGeocode(defaultLat, defaultLng);
    }

    // Backend Proxy Reverse Geocoding (Ters Coğrafi Kodlama)
    function reverseGeocode(lat, lng) {
        selectedLocation.lat = lat;
        selectedLocation.lng = lng;

        $('#mapSelectedCoords').text('Koordinat: ' + lat.toFixed(5) + ', ' + lng.toFixed(5));
        $('#mapLoadingSpinner').css('display', 'flex');
        $('#btnApplyMapAddress').prop('disabled', true);

        var url = 'App/api/geocode.php?action=reverse&lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng);

        fetch(url)
        .then(function (res) { return res.json(); })
        .then(function (json) {
            $('#mapLoadingSpinner').hide();
            if (json && json.status === 'success' && json.data) {
                var data = json.data;
                var addr = data.address || {};

                var cityRaw = addr.province || addr.state || addr.city || addr.admin_level_4 || '';
                var districtRaw = addr.county || addr.town || addr.district || addr.city_district || addr.suburb || '';
                var neighbourhoodRaw = addr.neighbourhood || addr.suburb || addr.quarter || addr.village || addr.residential || '';
                var roadRaw = addr.road || addr.pedestrian || addr.street || addr.footway || '';
                var houseNumberRaw = addr.house_number || '';
                var postcodeRaw = addr.postcode || '';

                var city = cleanCityName(cityRaw);
                var district = cleanDistrictName(districtRaw);
                var neighbourhood = neighbourhoodRaw ? neighbourhoodRaw.replace(/\s*(mahallesi|mah\.|mah)\s*/gi, '').trim() + ' Mah.' : '';
                var road = roadRaw ? roadRaw.trim() : '';
                var houseNumber = houseNumberRaw ? 'No:' + houseNumberRaw.trim() : '';

                // Yapılandırılmış Açık Adres Metni
                var addressParts = [];
                if (neighbourhood) addressParts.push(neighbourhood);
                if (road) {
                    if (houseNumber) {
                        addressParts.push(road + ' ' + houseNumber);
                    } else {
                        addressParts.push(road);
                    }
                } else if (houseNumber) {
                    addressParts.push(houseNumber);
                }
                
                var locationSuffix = [];
                if (postcodeRaw) locationSuffix.push(postcodeRaw);
                if (districtRaw) locationSuffix.push(districtRaw);
                if (cityRaw) locationSuffix.push(cityRaw);

                var finalAddress = '';
                if (addressParts.length > 0 && locationSuffix.length > 0) {
                    finalAddress = addressParts.join(', ') + ', ' + locationSuffix.join('/');
                } else if (data.display_name) {
                    finalAddress = data.display_name;
                } else {
                    finalAddress = [neighbourhood, road, houseNumber, district, city].filter(Boolean).join(' ');
                }

                selectedLocation.city = city;
                selectedLocation.district = district;
                selectedLocation.neighbourhood = neighbourhoodRaw;
                selectedLocation.road = roadRaw;
                selectedLocation.houseNumber = houseNumberRaw;
                selectedLocation.postcode = postcodeRaw;
                selectedLocation.formattedAddress = finalAddress;

                // Arayüzü güncelle
                $('#mapSelectedAddressText').html('<strong>' + finalAddress + '</strong>');

                if (city) {
                    $('#badgeIl').text('İl: ' + city).show();
                } else {
                    $('#badgeIl').hide();
                }

                if (district) {
                    $('#badgeIlce').text('İlçe: ' + district).show();
                } else {
                    $('#badgeIlce').hide();
                }

                if (neighbourhoodRaw) {
                    $('#badgeMahalle').text('Mahalle: ' + neighbourhoodRaw).show();
                } else {
                    $('#badgeMahalle').hide();
                }

                $('#btnApplyMapAddress').prop('disabled', false);
            } else {
                $('#mapSelectedAddressText').text('Adres bilgisi alınamadı. Lütfen başka bir nokta seçiniz.');
            }
        })
        .catch(function (err) {
            $('#mapLoadingSpinner').hide();
            console.error('Reverse geocode error:', err);
            $('#mapSelectedAddressText').text('Adres servisine bağlanırken bir sorun oluştu.');
        });
    }

    // Arama Fonksiyonu (Backend Geocoding Proxy)
    function searchLocation(query) {
        if (!query || query.trim() === '') return;
        query = query.trim();

        $('#mapLoadingSpinner').css('display', 'flex');
        var url = 'App/api/geocode.php?action=search&q=' + encodeURIComponent(query);

        fetch(url)
        .then(function (res) { return res.json(); })
        .then(function (json) {
            $('#mapLoadingSpinner').hide();
            var $resultsContainer = $('#mapSearchResults');
            $resultsContainer.empty();

            if (json && json.status === 'success' && json.data && json.data.length > 0) {
                var results = json.data;
                if (results.length === 1) {
                    // Tek sonuç varsa direkt git
                    var item = results[0];
                    var lat = parseFloat(item.lat);
                    var lon = parseFloat(item.lon);
                    if (!map) {
                        initCustomerMap(lat, lon, 16);
                    } else {
                        map.setView([lat, lon], 16);
                        marker.setLatLng([lat, lon]);
                        map.invalidateSize();
                    }
                    reverseGeocode(lat, lon);
                    $resultsContainer.addClass('d-none');
                } else {
                    // Birden çok sonuç varsa listele
                    $resultsContainer.removeClass('d-none');
                    results.forEach(function (item) {
                        var $btn = $('<button type="button" class="list-group-item list-group-item-action font-13 py-2 px-3 text-left">' +
                            '<i class="fa fa-map-marker text-danger mr-2"></i> ' + item.display_name +
                        '</button>');

                        $btn.on('click', function () {
                            var lat = parseFloat(item.lat);
                            var lon = parseFloat(item.lon);
                            if (!map) {
                                initCustomerMap(lat, lon, 16);
                            } else {
                                map.setView([lat, lon], 16);
                                marker.setLatLng([lat, lon]);
                                map.invalidateSize();
                            }
                            reverseGeocode(lat, lon);
                            $resultsContainer.addClass('d-none');
                        });

                        $resultsContainer.append($btn);
                    });
                }
            } else {
                showCustomerToast(json.message || 'Aranan konum bulunamadı.', 'error');
            }
        })
        .catch(function (err) {
            $('#mapLoadingSpinner').hide();
            console.error('Search error:', err);
            showCustomerToast('Arama servisine ulaşılamadı.', 'error');
        });
    }

    // "Haritadan Seç" Butonuna Tıklanınca Modal Aç
    $(document).on('click', '#btnOpenCustomerMap', function (e) {
        e.preventDefault();
        
        var $modal = $('#customerMapModal');
        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
        } else if (window.bootstrap && window.bootstrap.Modal) {
            try {
                var modalEl = document.getElementById('customerMapModal');
                var bsModal = new window.bootstrap.Modal(modalEl);
                bsModal.show();
            } catch (err) {
                $modal.addClass('show').css('display', 'block');
            }
        }

        setTimeout(function() {
            if (typeof L !== 'undefined') {
                if (!isMapInitialized) {
                    initCustomerMap(40.1885, 29.0610, 13);
                }
                if (map) {
                    map.invalidateSize();
                }
            }
        }, 150);
    });

    // Modal Açıldığında Haritayı Konumlandır
    $('#customerMapModal').on('shown.bs.modal', function () {
        var currentCity = $('#il').val();
        var currentAddress = $('#customer_address').val();

        function tryInitAndResize() {
            if (typeof L !== 'undefined') {
                if (!isMapInitialized) {
                    initCustomerMap(40.1885, 29.0610, 13);
                }
                if (map) {
                    map.invalidateSize();
                }
            }
        }

        tryInitAndResize();
        setTimeout(tryInitAndResize, 100);
        setTimeout(tryInitAndResize, 300);
        setTimeout(tryInitAndResize, 600);

        // Eğer adreste veya ilde değer varsa arama kutusuna ön doldurma yap
        if (currentAddress && currentAddress.trim().length > 3) {
            $('#mapSearchInput').val(currentAddress.trim());
        } else if (currentCity && currentCity.trim() !== '') {
            $('#mapSearchInput').val(currentCity.trim());
        }
    });

    // Arama Butonları & Enter Tetikleyicisi
    $(document).on('click', '#btnMapSearch', function () {
        searchLocation($('#mapSearchInput').val());
    });

    $(document).on('keypress', '#mapSearchInput', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            searchLocation($(this).val());
        }
    });

    // "Konumumu Bul" Butonu
    $(document).on('click', '#btnMapLocateMe', function () {
        if (!navigator.geolocation) {
            showCustomerToast('Tarayıcınız konum servisini desteklemiyor.', 'error');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-circle-o-notch fa-spin text-primary"></i> Konum Alınıyor...');

        navigator.geolocation.getCurrentPosition(
            function (position) {
                $btn.prop('disabled', false).html('<i class="fa fa-crosshairs text-primary"></i> Konumumu Bul');
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                reverseGeocode(lat, lng);
            },
            function (error) {
                $btn.prop('disabled', false).html('<i class="fa fa-crosshairs text-primary"></i> Konumumu Bul');
                var msg = 'Konumunuz alınamadı.';
                if (error.code === 1) msg = 'Konum izni verilmedi.';
                else if (error.code === 2) msg = 'Konum tespit edilemedi.';
                else if (error.code === 3) msg = 'Konum alma zaman aşımına uğradı.';
                showCustomerToast(msg, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });

    // "Bu Adresi Aktar" Butonuna Tıklanınca Forma Yaz
    $(document).on('click', '#btnApplyMapAddress', function () {
        if (!selectedLocation.formattedAddress) return;

        // 1. Adresi textarea'ya yaz
        var $addressField = $('#customer_address');
        $addressField.val(selectedLocation.formattedAddress);
        $addressField.removeClass('is-invalid').css({ 'border-color': '', 'background': '' });
        $addressField.next('.field-inline-error').remove();

        // 2. İl Seçimini Eşleştir ve Tetikle
        var targetCity = selectedLocation.city;
        var targetDistrict = selectedLocation.district;

        if (targetCity) {
            var $ilSelect = $('#il');
            var matchedCityVal = '';

            $ilSelect.find('option').each(function () {
                var optVal = $(this).val();
                if (optVal && toTurkishUpper(optVal) === targetCity) {
                    matchedCityVal = optVal;
                    return false;
                }
            });

            if (matchedCityVal) {
                if ($.fn.selectpicker && $ilSelect.hasClass('selectpicker')) {
                    $ilSelect.selectpicker('val', matchedCityVal);
                } else {
                    $ilSelect.val(matchedCityVal);
                }
                $ilSelect.trigger('change').trigger('change.select2');

                // İl seçildikten sonra ilçeleri bekle ve ilçeyi seç
                if (targetDistrict) {
                    var checkCount = 0;
                    var interval = setInterval(function () {
                        checkCount++;
                        var $ilceSelect = $('#ilce');
                        var matchedDistrictVal = '';

                        $ilceSelect.find('option').each(function () {
                            var optVal = $(this).val();
                            if (optVal && toTurkishUpper(optVal) === targetDistrict) {
                                matchedDistrictVal = optVal;
                                return false;
                            }
                        });

                        if (matchedDistrictVal) {
                            if ($.fn.selectpicker && $ilceSelect.hasClass('selectpicker')) {
                                $ilceSelect.selectpicker('val', matchedDistrictVal);
                            } else {
                                $ilceSelect.val(matchedDistrictVal);
                            }
                            $ilceSelect.trigger('change').trigger('change.select2');
                            clearInterval(interval);
                        } else if (checkCount > 15) { // 1.5 sn sonra vazgeç
                            clearInterval(interval);
                        }
                    }, 100);
                }
            }
        }

        // Modalı Kapat ve Bildirim Göster
        $('#customerMapModal').modal('hide');
        showCustomerToast('Haritadan seçilen adres ve konum bilgileri forma aktarıldı.', 'success');
    });
})();
