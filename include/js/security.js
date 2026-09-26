(function () {
    'use strict';
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';
    if (!token) return;

    function isSameOrigin(url) {
        try { return new URL(url, window.location.href).origin === window.location.origin; }
        catch (e) { return false; }
    }

    if (window.fetch) {
        var originalFetch = window.fetch;
        window.fetch = function (input, init) {
            init = init || {};
            var url = typeof input === 'string' ? input : input.url;
            if (isSameOrigin(url)) {
                var headers = new Headers(init.headers || (typeof input !== 'string' ? input.headers : undefined));
                headers.set('X-CSRF-Token', token);
                init.headers = headers;
            }
            return originalFetch.call(this, input, init);
        };
    }

    if (window.jQuery) {
        window.jQuery.ajaxSetup({ headers: { 'X-CSRF-Token': token } });
    }

    var originalOpen = XMLHttpRequest.prototype.open;
    var originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function (method, url) {
        this.__securitySameOrigin = isSameOrigin(url);
        return originalOpen.apply(this, arguments);
    };
    XMLHttpRequest.prototype.send = function () {
        if (this.__securitySameOrigin) this.setRequestHeader('X-CSRF-Token', token);
        return originalSend.apply(this, arguments);
    };
})();
