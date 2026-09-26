<!-- Basic Page Info -->
<meta charset="utf-8">
<title>

	<?php echo set("site_title"); ?>
</title>

<!-- Site favicon -->
<!-- <link rel="shortcut icon" href="images/favicon.ico"> -->

<!-- Mobile Specific Metas -->
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="csrf-token" content="<?php echo htmlspecialchars(\App\Helper\Security::csrf(), ENT_QUOTES, 'UTF-8'); ?>">

<!-- Google Fonts (Geist, Inter, Plus Jakarta Sans, Poppins, Outfit, Roboto, Montserrat, DM Sans, Manrope, Space Grotesk, Urbanist, Figtree, Sora) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Figtree:wght@400;500;600;700&family=Geist:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;600;700&family=Sora:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- Critical Early Scrollbar CSS (Native Scrollbar Flash / FOUC Önleme) -->
<style id="critical-scrollbar-style">
	html, body {
		scrollbar-width: thin;
		scrollbar-color: #cbd5e1 transparent;
	}
	html.dark-mode, html.dark-mode body, [data-theme-preset="koyu-gece"] {
		scrollbar-color: #475569 transparent;
	}
	::-webkit-scrollbar {
		width: 7px;
		height: 7px;
	}
	::-webkit-scrollbar-track {
		background: transparent;
	}
	::-webkit-scrollbar-thumb {
		background: #cbd5e1;
		border-radius: 8px;
	}
	::-webkit-scrollbar-thumb:hover {
		background: #94a3b8;
	}
	.dark-mode ::-webkit-scrollbar-thumb {
		background: #3f3f46;
	}
	.dark-mode ::-webkit-scrollbar-thumb:hover {
		background: #52525b;
	}
	.left-side-bar .menu-block.customscroll:not(.mCustomScrollbar),
	.customscroll:not(.mCustomScrollbar) {
		scrollbar-width: none !important;
		-ms-overflow-style: none !important;
		overflow-y: hidden !important;
	}
	.left-side-bar .menu-block.customscroll:not(.mCustomScrollbar)::-webkit-scrollbar,
	.customscroll:not(.mCustomScrollbar)::-webkit-scrollbar {
		display: none !important;
		width: 0 !important;
		height: 0 !important;
	}
</style>

<!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="src/plugins/datatables/media/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="src/plugins/datatables/media/css/dataTables.bootstrap4.css">
<link rel="stylesheet" type="text/css" href="src/plugins/datatables/media/css/responsive.dataTables.css">
<link rel="stylesheet" type="text/css" href="src/fonts/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Leaflet Harita Kütüphanesi (Yerel) -->
<link rel="stylesheet" href="src/plugins/leaflet/leaflet.css" />
<script src="src/plugins/leaflet/leaflet.js"></script>

<!-- <script src="//code.jquery.com/jquery-3.6.0.min.js"></script> -->
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <script src="include/js/security.js?v=<?php echo filemtime('include/js/security.js'); ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
  <script src="include/js/table-filter.js?v=<?php echo file_exists('include/js/table-filter.js') ? filemtime('include/js/table-filter.js') : time(); ?>"></script>


<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
	integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
	</script>

<link rel="canonical" href="https://aydinogullariysc.com/index.php?p=home" />

<!-- Global site tag (gtag.js) - Google Analytics -->
<link rel="stylesheet" href="vendors/styles/style.css?v=<?php echo filemtime('vendors/styles/style.css'); ?>">
<link rel="stylesheet" href="vendors/styles/premium-theme.css?v=<?php echo filemtime('vendors/styles/premium-theme.css'); ?>">

<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
<!-- manifest.json -->
<link rel="manifest" href="/manifest.json">

<!-- Styles -->
<script>
	window.dataLayer = window.dataLayer || [];

	function gtag() {
		dataLayer.push(arguments);
	}
	gtag('js', new Date());

	gtag('config', 'UA-119386393-1');
</script>

<script>
	// Global Tema & Tema Özelleştirici Fonksiyonları
	window.syncWysihtml5Theme = function() {
		try {
			var isDark = document.documentElement.classList.contains('dark-mode') || (document.body && document.body.classList.contains('dark-mode'));
			document.querySelectorAll('iframe.wysihtml5-sandbox').forEach(function(iframe) {
				try {
					iframe.style.setProperty('padding', '0', 'important');
					var doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
					if (doc && doc.body) {
						if (doc.documentElement) {
							doc.documentElement.style.margin = '0';
							doc.documentElement.style.padding = '0';
						}
						doc.body.style.padding = '8px 12px';
						doc.body.style.margin = '0';
						if (isDark) {
							doc.body.style.backgroundColor = '#0f172a';
							doc.body.style.color = '#f8fafc';
							doc.body.classList.add('dark-mode');
						} else {
							doc.body.style.backgroundColor = '#ffffff';
							doc.body.style.color = '#1e293b';
							doc.body.classList.remove('dark-mode');
						}
					}
				} catch(innerErr) {}
			});
		} catch(e) {}
	};

	window.toggleTheme = function(e) {
		if (e) {
			if (e.preventDefault) e.preventDefault();
			if (e.stopPropagation) e.stopPropagation();
		}
		var html = document.documentElement;
		var body = document.body;
		var isDark = html.classList.contains('dark-mode') || (body && body.classList.contains('dark-mode'));
		var toggleBtn = document.getElementById('theme-toggle');
		
		if (isDark) {
			html.classList.remove('dark-mode');
			if (body) body.classList.remove('dark-mode');
			if (toggleBtn) toggleBtn.setAttribute('data-tooltip', 'Karanlık Mod');
			try { localStorage.setItem('theme', 'light'); } catch(err){}
		} else {
			html.classList.add('dark-mode');
			if (body) body.classList.add('dark-mode');
			if (toggleBtn) toggleBtn.setAttribute('data-tooltip', 'Aydınlık Mod');
			try { localStorage.setItem('theme', 'dark'); } catch(err){}
		}
		window.syncWysihtml5Theme();
	};

	// Primary (Birincil / Vurgu) Rengi Değiştirme Fonksiyonu
	window.selectPrimaryTheme = function(hexColor, nameKey, isManual) {
		if (!hexColor) return;
		try {
			if (isManual) {
				localStorage.setItem('app_primary_manual', 'true');
			}
			localStorage.setItem('app_primary_color', hexColor);
			localStorage.setItem('app_primary_name', nameKey || 'custom');
		} catch (err) {}

		var html = document.documentElement;
		html.style.setProperty('--theme-primary', hexColor);
		html.style.setProperty('--theme-primary-hover', hexColor);
		html.style.setProperty('--theme-primary-shadow', 'color-mix(in srgb, ' + hexColor + ' 30%, transparent)');
		html.style.setProperty('--theme-primary-light', 'color-mix(in srgb, ' + hexColor + ' 12%, #ffffff)');
		html.style.setProperty('--focus-color', hexColor);

		if (document.body) {
			document.body.style.setProperty('--theme-primary', hexColor);
			document.body.style.setProperty('--theme-primary-hover', hexColor);
			document.body.style.setProperty('--theme-primary-shadow', 'color-mix(in srgb, ' + hexColor + ' 30%, transparent)');
			document.body.style.setProperty('--theme-primary-light', 'color-mix(in srgb, ' + hexColor + ' 12%, #ffffff)');
			document.body.style.setProperty('--focus-color', hexColor);
		}

		var topbarTheme = html.getAttribute('data-topbar-theme') || 'mavi';
		var isLightTopbar = ['beyaz', 'lavanta', 'adacayi', 'buz-mavisi', 'vizon'].indexOf(topbarTheme) !== -1;
		if (isLightTopbar) {
			html.style.setProperty('--focus-color', hexColor);
			if (document.body) document.body.style.setProperty('--focus-color', hexColor);
		}

		var picker = document.getElementById('customPrimaryColorPicker');
		var hexLabel = document.getElementById('customPrimaryHexLabel');
		if (picker) picker.value = hexColor.startsWith('#') && hexColor.length === 7 ? hexColor : '#2563eb';
		if (hexLabel) hexLabel.textContent = hexColor;

		window.syncActivePrimaryButtons();
	};

	// Aktif Primary Butonunu Eşitleme
	window.syncActivePrimaryButtons = function() {
		var activeName = localStorage.getItem('app_primary_name') || '';
		var activeColor = localStorage.getItem('app_primary_color') || '';
		
		document.querySelectorAll('[data-primary]').forEach(function(btn) {
			if (btn.getAttribute('data-primary') === activeName) {
				btn.classList.add('active');
			} else {
				btn.classList.remove('active');
			}
		});

		var pickerWrap = document.querySelector('.theme-custom-color-picker-wrap');
		if (pickerWrap) {
			if (activeName === 'custom') {
				pickerWrap.style.borderColor = '#2563eb';
				pickerWrap.style.background = '#eff6ff';
			} else {
				pickerWrap.style.borderColor = '#cbd5e1';
				pickerWrap.style.background = '#f8fafc';
			}
		}
		var picker = document.getElementById('customPrimaryColorPicker');
		var hexLabel = document.getElementById('customPrimaryHexLabel');
		if (picker && activeColor && activeColor.startsWith('#') && activeColor.length === 7) picker.value = activeColor;
		if (hexLabel && activeColor) hexLabel.textContent = activeColor;
	};

	// Tema Özelleştirici Aç / Kapat
	window.openThemeCustomizer = function(e) {
		if (e) {
			if (e.preventDefault) e.preventDefault();
			if (e.stopPropagation) e.stopPropagation();
		}
		var drawer = document.getElementById('theme-customizer-drawer');
		var backdrop = document.getElementById('theme-customizer-backdrop');
		if (drawer) drawer.classList.add('open');
		if (backdrop) backdrop.classList.add('open');
		window.syncActiveThemePresetCard();
		window.syncActiveTopbarButtons();
		window.syncActiveSidebarButtons();
		window.syncActivePrimaryButtons();
		window.syncActiveThemeFontButtons();
		window.syncActiveThemeWeightButtons();
	};

	window.closeThemeCustomizer = function() {
		var drawer = document.getElementById('theme-customizer-drawer');
		var backdrop = document.getElementById('theme-customizer-backdrop');
		if (drawer) drawer.classList.remove('open');
		if (backdrop) backdrop.classList.remove('open');
	};

	// Mobil & Masaüstü Sidebar Menü Aç / Kapat
	window.toggleSidebarMenu = function(e) {
		if (e) {
			if (e.preventDefault) e.preventDefault();
			if (e.stopPropagation) e.stopPropagation();
			if (e.stopImmediatePropagation) e.stopImmediatePropagation();
		}

		var isDesktop = window.innerWidth > 1200;
		if (isDesktop) {
			var html = document.documentElement;
			var isCollapsed = html.classList.toggle('sidebar-collapsed');
			try { localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false'); } catch(err){}
			var icons = document.querySelectorAll('.menu-icon, #sidebar-menu-toggle');
			icons.forEach(function(icon) {
				if (isCollapsed) icon.classList.remove('open');
				else icon.classList.add('open');
			});
		} else {
			var sidebar = document.querySelector('.left-side-bar');
			var backdrop = document.getElementById('sidebar-backdrop');
			var icons = document.querySelectorAll('.menu-icon, #sidebar-menu-toggle');
			
			if (sidebar) {
				var isOpen = sidebar.classList.toggle('open');
				if (backdrop) backdrop.classList.toggle('open', isOpen);
				icons.forEach(function(icon) {
					icon.classList.toggle('open', isOpen);
				});
			}
		}
		return false;
	};

	window.closeMobileSidebar = function() {
		var sidebar = document.querySelector('.left-side-bar');
		var backdrop = document.getElementById('sidebar-backdrop');
		var icons = document.querySelectorAll('.menu-icon, #sidebar-menu-toggle');
		
		if (sidebar) sidebar.classList.remove('open');
		if (backdrop) backdrop.classList.remove('open');
		icons.forEach(function(icon) {
			icon.classList.remove('open');
		});
	};

	// Tema - Yazı Tipi Eşleştirme Haritası (Ön Tanımlı)
	var themePresetFonts = {
		'kode': 'inter',
		'ersan-gold': 'montserrat',
		'zumrut': 'plus-jakarta-sans',
		'kraliyet-moru': 'outfit',
		'rose': 'poppins',
		'sade-beyaz': 'inter',
		'koyu-gece': 'geist',
		'safir-okyanus': 'outfit',
		'gun-batimi': 'poppins',
		'gece-altini': 'montserrat',
		'mistik-bordo': 'montserrat',
		'nordik-cam': 'plus-jakarta-sans',
		'soft-lavanta': 'outfit',
		'soft-adacayi': 'plus-jakarta-sans',
		'soft-seftali': 'poppins',
		'soft-buz-mavisi': 'inter',
		'soft-vizon': 'montserrat',
		'modern-celik': 'dm-sans',
		'antrasit-zumrut': 'manrope',
		'dumanli-bordo': 'figtree',
		'grafiti-mor': 'space-grotesk',
		'kul-amber': 'urbanist',
		'petrol-tas': 'plus-jakarta-sans',
		'platin-mavi': 'sora',
		'titan-okyanus': 'outfit',
		'gradient-mor': 'outfit',
		'gradient-safir': 'plus-jakarta-sans',
		'gradient-zumrut': 'manrope',
		'gradient-yakut': 'figtree',
		'gradient-amber': 'urbanist',
		'gradient-petrol': 'space-grotesk',
		'gradient-lacivert': 'sora',
		'gradient-titanyum': 'dm-sans',
		'gradient-magenta': 'outfit',
		'gradient-altin': 'montserrat'
	};

	// Hazır Tema -> Varsayılan Topbar & Sidebar Eşleştirme Haritası
	var presetTopbarSidebarMap = {
		'kode': { topbar: 'mavi', sidebar: 'klasik-koyu' },
		'ersan-gold': { topbar: 'beyaz', sidebar: 'klasik-koyu' },
		'zumrut': { topbar: 'zumrut', sidebar: 'koyu-zumrut' },
		'kraliyet-moru': { topbar: 'mor', sidebar: 'koyu-mor' },
		'rose': { topbar: 'rose', sidebar: 'klasik-koyu' },
		'sade-beyaz': { topbar: 'beyaz', sidebar: 'sade-beyaz' },
		'koyu-gece': { topbar: 'oniks', sidebar: 'grafit-gri' },
		'safir-okyanus': { topbar: 'safir', sidebar: 'klasik-koyu' },
		'gun-batimi': { topbar: 'amber', sidebar: 'duman-gri' },
		'gece-altini': { topbar: 'oniks', sidebar: 'grafit-gri' },
		'mistik-bordo': { topbar: 'bordo', sidebar: 'koyu-bordo' },
		'nordik-cam': { topbar: 'nordik', sidebar: 'koyu-zumrut' },
		'soft-lavanta': { topbar: 'lavanta', sidebar: 'koyu-mor' },
		'soft-adacayi': { topbar: 'adacayi', sidebar: 'koyu-zumrut' },
		'soft-seftali': { topbar: 'amber', sidebar: 'duman-gri' },
		'soft-buz-mavisi': { topbar: 'buz-mavisi', sidebar: 'klasik-koyu' },
		'soft-vizon': { topbar: 'vizon', sidebar: 'slate-gri' },
		'modern-celik': { topbar: 'mavi', sidebar: 'slate-gri' },
		'antrasit-zumrut': { topbar: 'zumrut', sidebar: 'antrasit-gri' },
		'dumanli-bordo': { topbar: 'bordo', sidebar: 'duman-gri' },
		'grafiti-mor': { topbar: 'mor', sidebar: 'grafit-gri' },
		'kul-amber': { topbar: 'amber', sidebar: 'slate-gri' },
		'petrol-tas': { topbar: 'petrol', sidebar: 'antrasit-gri' },
		'platin-mavi': { topbar: 'safir', sidebar: 'platin-gri' },
		'titan-okyanus': { topbar: 'lacivert', sidebar: 'titan-gri' },
		'gradient-mor': { topbar: 'gradient-mor', sidebar: 'koyu-mor' },
		'gradient-safir': { topbar: 'gradient-safir', sidebar: 'koyu-okyanus' },
		'gradient-zumrut': { topbar: 'gradient-zumrut', sidebar: 'koyu-zumrut' },
		'gradient-yakut': { topbar: 'gradient-yakut', sidebar: 'koyu-bordo' },
		'gradient-amber': { topbar: 'gradient-amber', sidebar: 'koyu-volkan' },
		'gradient-petrol': { topbar: 'gradient-petrol', sidebar: 'koyu-petrol' },
		'gradient-lacivert': { topbar: 'gradient-lacivert', sidebar: 'koyu-nebula' },
		'gradient-titanyum': { topbar: 'gradient-titanyum', sidebar: 'grafit-gri' },
		'gradient-magenta': { topbar: 'gradient-magenta', sidebar: 'koyu-magenta' },
		'gradient-altin': { topbar: 'gradient-altin', sidebar: 'grafit-gri' }
	};

	// Topbar (Üst Menü) Rengi Değiştirme Fonksiyonu
	window.selectTopbarTheme = function(topbarName, isManual) {
		if (!topbarName) return;
		try {
			if (isManual) {
				localStorage.setItem('app_topbar_theme_manual', 'true');
			}
			localStorage.setItem('app_topbar_theme', topbarName);
		} catch (err) {}

		document.documentElement.setAttribute('data-topbar-theme', topbarName);
		if (document.body) {
			document.body.setAttribute('data-topbar-theme', topbarName);
		}
		window.syncActiveTopbarButtons();
	};

	// Sidebar (Sol Menü) Rengi Değiştirme Fonksiyonu
	window.selectSidebarTheme = function(sidebarName, isManual) {
		if (!sidebarName) return;
		try {
			if (isManual) {
				localStorage.setItem('app_sidebar_theme_manual', 'true');
			}
			localStorage.setItem('app_sidebar_theme', sidebarName);
		} catch (err) {}

		document.documentElement.setAttribute('data-sidebar-theme', sidebarName);
		if (document.body) {
			document.body.setAttribute('data-sidebar-theme', sidebarName);
		}
		window.syncActiveSidebarButtons();
	};

	// Yazı Tipi Değiştirme Fonksiyonu
	window.selectThemeFont = function(fontName, isManual) {
		if (!fontName) return;
		try {
			if (isManual) {
				localStorage.setItem('app_theme_font_manual', 'true');
			}
			localStorage.setItem('app_theme_font', fontName);
		} catch (err) {}

		document.documentElement.setAttribute('data-theme-font', fontName);
		if (document.body) {
			document.body.setAttribute('data-theme-font', fontName);
		}
		window.syncActiveThemeFontButtons();
	};

	// Yazı Tipi Kalınlığı Değiştirme Fonksiyonu
	window.selectThemeWeight = function(weightName, isManual) {
		if (!weightName) return;
		try {
			if (isManual) {
				localStorage.setItem('app_theme_weight_manual', 'true');
			}
			localStorage.setItem('app_theme_weight', weightName);
		} catch (err) {}

		document.documentElement.setAttribute('data-theme-weight', weightName);
		if (document.body) {
			document.body.setAttribute('data-theme-weight', weightName);
		}
		window.syncActiveThemeWeightButtons();
	};

	// Hazır Tema Seçme Fonksiyonu (Topbar, Sidebar ve Font'u Birlikte Ayarlar)
	window.selectThemePreset = function(presetName) {
		if (!presetName) return;
		try {
			localStorage.setItem('app_theme_preset', presetName);
			// Yeni hazır tema seçildiğinde manuel primary rengi temizle (temanın kendi primary rengi geçerli olsun)
			localStorage.removeItem('app_primary_manual');
			localStorage.removeItem('app_primary_color');
			localStorage.removeItem('app_primary_name');
		} catch (err) {}

		var html = document.documentElement;
		html.removeAttribute('style'); // Manuel inline renkleri sıfırla
		html.setAttribute('data-theme-preset', presetName);
		if (document.body) {
			document.body.removeAttribute('style');
			document.body.setAttribute('data-theme-preset', presetName);
		}

		// İlgili temanın topbar ve sidebar rengini de uygula
		var mapping = presetTopbarSidebarMap[presetName] || { topbar: 'mavi', sidebar: 'klasik-koyu' };
		window.selectTopbarTheme(mapping.topbar, false);
		window.selectSidebarTheme(mapping.sidebar, false);

		// Otomatik tema fontu ata
		var suggestedFont = themePresetFonts[presetName] || 'inter';
		window.selectThemeFont(suggestedFont, false);

		// Koyu temalarda Dark Mode'u otomatik aktif et; açık temalarda dark mode kaldır
		var body = document.body;
		var toggleBtn = document.getElementById('theme-toggle');

		if (presetName === 'koyu-gece' || presetName === 'gece-altini') {
			html.classList.add('dark-mode');
			if (body) body.classList.add('dark-mode');
			if (toggleBtn) toggleBtn.setAttribute('data-tooltip', 'Aydınlık Mod');
			try { localStorage.setItem('theme', 'dark'); } catch(err){}
		} else {
			html.classList.remove('dark-mode');
			if (body) body.classList.remove('dark-mode');
			if (toggleBtn) toggleBtn.setAttribute('data-tooltip', 'Karanlık Mod');
			try { localStorage.setItem('theme', 'light'); } catch(err){}
		}

		window.syncActiveThemePresetCard();
		window.syncActiveTopbarButtons();
		window.syncActiveSidebarButtons();
		window.syncActivePrimaryButtons();
		window.syncActiveThemeFontButtons();
		window.syncActiveThemeWeightButtons();
		window.syncWysihtml5Theme();
	};

	// Aktif Tema Kartını Eşitleme
	window.syncActiveThemePresetCard = function() {
		var activePreset = localStorage.getItem('app_theme_preset') || document.documentElement.getAttribute('data-theme-preset') || 'kode';
		document.querySelectorAll('.theme-preset-card').forEach(function(card) {
			if (card.getAttribute('data-preset') === activePreset) {
				card.classList.add('active');
			} else {
				card.classList.remove('active');
			}
		});
	};

	// Aktif Topbar Butonunu Eşitleme
	window.syncActiveTopbarButtons = function() {
		var activeTopbar = localStorage.getItem('app_topbar_theme') || document.documentElement.getAttribute('data-topbar-theme') || 'mavi';
		document.querySelectorAll('[data-topbar]').forEach(function(btn) {
			if (btn.getAttribute('data-topbar') === activeTopbar) {
				btn.classList.add('active');
			} else {
				btn.classList.remove('active');
			}
		});
	};

	// Aktif Sidebar Butonunu Eşitleme
	window.syncActiveSidebarButtons = function() {
		var activeSidebar = localStorage.getItem('app_sidebar_theme') || document.documentElement.getAttribute('data-sidebar-theme') || 'klasik-koyu';
		document.querySelectorAll('[data-sidebar]').forEach(function(btn) {
			if (btn.getAttribute('data-sidebar') === activeSidebar) {
				btn.classList.add('active');
			} else {
				btn.classList.remove('active');
			}
		});
	};

	// Aktif Yazı Tipi Butonunu Eşitleme
	window.syncActiveThemeFontButtons = function() {
		var activeFont = localStorage.getItem('app_theme_font') || document.documentElement.getAttribute('data-theme-font') || 'inter';
		document.querySelectorAll('.theme-font-btn').forEach(function(btn) {
			if (btn.getAttribute('data-font') === activeFont) {
				btn.classList.add('active');
			} else {
				btn.classList.remove('active');
			}
		});
	};

	// Aktif Yazı Tipi Kalınlığı Butonunu Eşitleme
	window.syncActiveThemeWeightButtons = function() {
		var activeWeight = localStorage.getItem('app_theme_weight') || document.documentElement.getAttribute('data-theme-weight') || '400';
		document.querySelectorAll('.theme-weight-btn').forEach(function(btn) {
			if (btn.getAttribute('data-weight') === activeWeight) {
				btn.classList.add('active');
			} else {
				btn.classList.remove('active');
			}
		});
	};

	// Sayfa render edilmeden önce tema ve font durumunu ayarla (flicker önleme)
	(function () {
		try {
			var html = document.documentElement;

			// 1. Hazır Tema (Preset) Yükleme
			var savedPreset = localStorage.getItem('app_theme_preset');
			if (!savedPreset) {
				savedPreset = 'kode'; // Varsayılan tema
			}
			html.setAttribute('data-theme-preset', savedPreset);

			// 2. Topbar & Sidebar Ayrı Renk Yükleme
			var defaultMap = presetTopbarSidebarMap[savedPreset] || { topbar: 'mavi', sidebar: 'klasik-koyu' };
			var savedTopbar = localStorage.getItem('app_topbar_theme') || defaultMap.topbar;
			var savedSidebar = localStorage.getItem('app_sidebar_theme') || defaultMap.sidebar;
			html.setAttribute('data-topbar-theme', savedTopbar);
			html.setAttribute('data-sidebar-theme', savedSidebar);

			// 3. Özel/Ayrı Primary Renk Yükleme
			var savedPrimaryColor = localStorage.getItem('app_primary_color');
			var savedPrimaryManual = localStorage.getItem('app_primary_manual');
			if (savedPrimaryColor && savedPrimaryManual === 'true') {
				html.style.setProperty('--theme-primary', savedPrimaryColor);
				html.style.setProperty('--theme-primary-hover', savedPrimaryColor);
				html.style.setProperty('--theme-primary-shadow', 'color-mix(in srgb, ' + savedPrimaryColor + ' 30%, transparent)');
				html.style.setProperty('--theme-primary-light', 'color-mix(in srgb, ' + savedPrimaryColor + ' 12%, #ffffff)');
				html.style.setProperty('--focus-color', savedPrimaryColor);
			}

			// 4. Yazı Tipi Yükleme
			var savedFont = localStorage.getItem('app_theme_font');
			if (!savedFont) {
				savedFont = themePresetFonts[savedPreset] || 'inter';
			}
			html.setAttribute('data-theme-font', savedFont);

			// 5. Yazı Tipi Kalınlığı Yükleme
			var savedWeight = localStorage.getItem('app_theme_weight') || '400';
			html.setAttribute('data-theme-weight', savedWeight);

			// 6. Dark/Light Mode Yükleme
			var theme = localStorage.getItem('theme');
			if (theme === 'dark' || savedPreset === 'koyu-gece') {
				html.classList.add('dark-mode');
				document.addEventListener('DOMContentLoaded', function () {
					if (document.body) {
						document.body.classList.add('dark-mode');
						document.body.setAttribute('data-theme-preset', savedPreset);
						document.body.setAttribute('data-topbar-theme', savedTopbar);
						document.body.setAttribute('data-sidebar-theme', savedSidebar);
						document.body.setAttribute('data-theme-font', savedFont);
						document.body.setAttribute('data-theme-weight', savedWeight);
						if (savedPrimaryColor && savedPrimaryManual === 'true') {
							document.body.style.setProperty('--theme-primary', savedPrimaryColor);
							document.body.style.setProperty('--theme-primary-hover', savedPrimaryColor);
							document.body.style.setProperty('--focus-color', savedPrimaryColor);
						}
					}
					window.syncActiveThemePresetCard();
					window.syncActiveTopbarButtons();
					window.syncActiveSidebarButtons();
					window.syncActivePrimaryButtons();
					window.syncActiveThemeFontButtons();
					window.syncActiveThemeWeightButtons();
					setTimeout(window.syncWysihtml5Theme, 300);
					setTimeout(window.syncWysihtml5Theme, 1000);
				});
			} else {
				html.classList.remove('dark-mode');
				document.addEventListener('DOMContentLoaded', function () {
					if (document.body) {
						document.body.classList.remove('dark-mode');
						document.body.setAttribute('data-theme-preset', savedPreset);
						document.body.setAttribute('data-topbar-theme', savedTopbar);
						document.body.setAttribute('data-sidebar-theme', savedSidebar);
						document.body.setAttribute('data-theme-font', savedFont);
						document.body.setAttribute('data-theme-weight', savedWeight);
						if (savedPrimaryColor && savedPrimaryManual === 'true') {
							document.body.style.setProperty('--theme-primary', savedPrimaryColor);
							document.body.style.setProperty('--theme-primary-hover', savedPrimaryColor);
							document.body.style.setProperty('--focus-color', savedPrimaryColor);
						}
					}
					window.syncActiveThemePresetCard();
					window.syncActiveTopbarButtons();
					window.syncActiveSidebarButtons();
					window.syncActivePrimaryButtons();
					window.syncActiveThemeFontButtons();
					window.syncActiveThemeWeightButtons();
					setTimeout(window.syncWysihtml5Theme, 300);
					setTimeout(window.syncWysihtml5Theme, 1000);
				});
			}

			// Sidebar collapse state
			var sidebarCollapsed = localStorage.getItem('sidebar-collapsed');
			if (sidebarCollapsed === 'true' && window.innerWidth > 1200) {
				html.classList.add('sidebar-collapsed');
			}
		} catch (e) {
			console.error('Theme init error:', e);
		}
	})();
</script>
