(function () {
    // Sayfa hazır olduğunda localStorage'daki temayı senkronize et
    $(document).ready(function () {
        try {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                $('html').addClass('dark-mode');
                $('body').addClass('dark-mode');
                $('#theme-toggle').attr('data-tooltip', 'Aydınlık Mod');
            } else {
                $('html').removeClass('dark-mode');
                $('body').removeClass('dark-mode');
                $('#theme-toggle').attr('data-tooltip', 'Karanlık Mod');
            }
        } catch (e) {}
    });
})();