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

<!-- Google Font (Geist) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
<!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="src/plugins/datatables/media/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="src/plugins/datatables/media/css/dataTables.bootstrap4.css">
<link rel="stylesheet" type="text/css" href="src/plugins/datatables/media/css/responsive.dataTables.css">
<link rel="stylesheet" type="text/css" href="src/fonts/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- <script src="//code.jquery.com/jquery-3.6.0.min.js"></script> -->
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
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
	// Global Tema Değiştirme Fonksiyonu
	window.syncWysihtml5Theme = function() {
		try {
			var isDark = document.documentElement.classList.contains('dark-mode') || (document.body && document.body.classList.contains('dark-mode'));
			document.querySelectorAll('iframe.wysihtml5-sandbox').forEach(function(iframe) {
				try {
					var doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
					if (doc && doc.body) {
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

	// Sayfa render edilmeden önce tema durumunu ayarla (flicker önleme)
	(function () {
		try {
			var theme = localStorage.getItem('theme');
			if (theme === 'dark') {
				document.documentElement.classList.add('dark-mode');
				document.addEventListener('DOMContentLoaded', function () {
					if (document.body) document.body.classList.add('dark-mode');
					setTimeout(window.syncWysihtml5Theme, 300);
					setTimeout(window.syncWysihtml5Theme, 1000);
				});
			} else {
				document.documentElement.classList.remove('dark-mode');
				document.addEventListener('DOMContentLoaded', function () {
					if (document.body) document.body.classList.remove('dark-mode');
					setTimeout(window.syncWysihtml5Theme, 300);
					setTimeout(window.syncWysihtml5Theme, 1000);
				});
			}

			// Sidebar collapse state
			var sidebarCollapsed = localStorage.getItem('sidebar-collapsed');
			if (sidebarCollapsed === 'true' && window.innerWidth > 1200) {
				document.documentElement.classList.add('sidebar-collapsed');
			}
		} catch (e) {
			console.error('Theme init error:', e);
		}
	})();
</script>