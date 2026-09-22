(function () {
    // Sayfa hazır olduğunda localStorage'daki temayı senkronize et
    $(document).ready(function () {
        try {
            var theme = localStorage.getItem('theme');
            var preset = localStorage.getItem('app_theme_preset') || 'kode';

            $('html').attr('data-theme-preset', preset);
            $('body').attr('data-theme-preset', preset);

            if (theme === 'dark' || preset === 'koyu-gece') {
                $('html').addClass('dark-mode');
                $('body').addClass('dark-mode');
                $('#theme-toggle').attr('data-tooltip', 'Aydınlık Mod');
            } else {
                $('html').removeClass('dark-mode');
                $('body').removeClass('dark-mode');
                $('#theme-toggle').attr('data-tooltip', 'Karanlık Mod');
            }

            if (typeof window.syncActiveThemePresetCard === 'function') {
                window.syncActiveThemePresetCard();
            }
        } catch (e) {}

        // ESC tuşu ile Tema Özelleştirici Drawer'ı kapatma
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                if (typeof window.closeThemeCustomizer === 'function') {
                    window.closeThemeCustomizer();
                }
            }
        });
    });
})();