(function () {
    'use strict';

    var banner = document.getElementById('maintenanceNotice');
    var messageNode = document.getElementById('maintenanceNoticeMessage');
    var timeNode = document.getElementById('maintenanceNoticeTime');
    var endpoint = 'api/maintenance-status.php';
    var maintenanceUrl = 'maintenance.php';
    var lastStatus = null;

    function parseLocalDate(value) {
        return value ? new Date(value.replace(' ', 'T')) : null;
    }

    function formatDate(value) {
        var date = parseLocalDate(value);
        if (!date || isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat('tr-TR', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        }).format(date);
    }

    function remainingText(value) {
        var target = parseLocalDate(value);
        if (!target) return '';
        var seconds = Math.max(0, Math.floor((target.getTime() - Date.now()) / 1000));
        var days = Math.floor(seconds / 86400);
        var hours = Math.floor((seconds % 86400) / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        if (days > 0) return days + ' gün ' + hours + ' saat kaldı';
        if (hours > 0) return hours + ' saat ' + minutes + ' dakika kaldı';
        return Math.max(1, minutes) + ' dakika kaldı';
    }

    function render(status) {
        lastStatus = status;
        if (!banner) return;

        if (status.active && status.has_access) {
            banner.hidden = false;
            document.body.classList.add('maintenance-notice-visible');
            banner.classList.add('is-active');
            messageNode.textContent = 'Bakım modu aktif. Yetkiniz nedeniyle sisteme erişmeye devam ediyorsunuz.';
            timeNode.textContent = status.ends_at ? 'Planlanan bitiş: ' + formatDate(status.ends_at) : '';
            return;
        }

        if (!status.announcement) {
            banner.hidden = true;
            document.body.classList.remove('maintenance-notice-visible');
            return;
        }

        banner.hidden = false;
        document.body.classList.add('maintenance-notice-visible');
        banner.classList.remove('is-active');
        messageNode.textContent = status.message || 'Planlı bakım sırasında sistem geçici olarak kullanılamayacaktır. Lütfen çalışmalarınızı önceden kaydedin.';
        timeNode.textContent = formatDate(status.starts_at) + ' – ' + formatDate(status.ends_at) + ' · ' + remainingText(status.starts_at);
    }

    function poll() {
        fetch(endpoint, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (response.status === 503) {
                window.location.replace(maintenanceUrl);
                return null;
            }
            if (response.status === 401) {
                window.location.reload();
                return null;
            }
            if (!response.ok) throw new Error('Bakım durumu alınamadı.');
            return response.json();
        }).then(function (status) {
            if (!status) return;
            if (status.active && !status.has_access) {
                window.location.replace(maintenanceUrl);
                return;
            }
            render(status);
        }).catch(function () {
            // Geçici ağ hataları mevcut sayfadaki çalışmayı kesmemelidir.
        });
    }

    if (banner && banner.dataset.status) {
        try { render(JSON.parse(banner.dataset.status)); } catch (e) {}
    }

    window.setInterval(function () {
        if (lastStatus && lastStatus.announcement && timeNode) render(lastStatus);
    }, 30000);
    window.setInterval(poll, 30000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll();
    });
})();
