<?php
$maintenanceHeaderStatus = \App\Helper\MaintenanceMode::getStatus($ac);
$maintenanceHeaderStatus['has_access'] = \App\Helper\MaintenanceMode::hasAccessPermission($ac);
$maintenanceHeaderJson = htmlspecialchars(
	json_encode($maintenanceHeaderStatus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
	ENT_QUOTES,
	'UTF-8'
);
?>
<div id="maintenanceNotice" class="maintenance-notice" data-status="<?php echo $maintenanceHeaderJson; ?>" hidden role="status" aria-live="polite">
	<div class="maintenance-notice-inner">
		<i class="fa fa-clock-o" aria-hidden="true"></i>
		<div class="maintenance-notice-copy">
			<strong>Planlı bakım bildirimi</strong>
			<span id="maintenanceNoticeMessage"></span>
		</div>
		<span id="maintenanceNoticeTime" class="maintenance-notice-time"></span>
	</div>
</div>
<div class="header clearfix">
	<div class="header-right">
		<!-- Sol: Menü İkonu (Hamburger) + Mobil Logo + Breadcrumb -->
		<div class="header-left d-flex align-items-center">
			<div class="menu-icon" id="sidebar-menu-toggle" onclick="toggleSidebarMenu(event);" title="Menüyü Aç/Kapat">
				<span></span>
				<span></span>
				<span></span>
				<span></span>
			</div>

			<div class="brand-logo d-lg-none ml-2">
				<a href="index.php">
					<img src="<?php echo set("logo"); ?>" alt="" class="mobile-logo">
				</a>
			</div>

			<div class="header-breadcrumb ml-2 d-none d-md-block">
				<nav aria-label="breadcrumb">
					<ol class="breadcrumb m-0 p-0 bg-transparent align-items-center">
						<li class="breadcrumb-item"><a href="index.php"><?php echo set("site_title"); ?></a></li>
						<li class="breadcrumb-item active" aria-current="page"><?php echo $pdat["p_title"] ?? 'Ana Sayfa'; ?></li>
					</ol>
				</nav>
			</div>
		</div>

		<!-- Orta: Global Arama (Teklif, Ürün, Firma, Servis, Keşif, Rapor) -->
		<div class="header-search-wrap d-none d-lg-flex">
			<div class="global-search-container" id="global-search-container">
				<div class="global-search-input-box">
					<i class="fa fa-search global-search-icon" aria-hidden="true"></i>
					<input type="text" 
						id="global-search-input" 
						class="global-search-input" 
						placeholder="Teklif, ürün, firma, servis, keşif veya rapor ara..." 
						autocomplete="off" 
						spellcheck="false">
					<button type="button" class="global-search-clear-btn" id="global-search-clear" title="Temizle" style="display: none;">
						<i class="fa fa-times"></i>
					</button>
					<div class="global-search-kbd-badge" title="Kısayol: Ctrl + K">
						<kbd>ctrl</kbd><kbd>K</kbd>
					</div>
					<div class="global-search-spinner" id="global-search-spinner" style="display: none;">
						<i class="fa fa-circle-o-notch fa-spin"></i>
					</div>
				</div>

				<!-- Arama Sonuç Dropdown Kartı -->
				<div class="global-search-dropdown" id="global-search-dropdown">
					<!-- Kategori Filtreleme Sekmeleri -->
					<div class="global-search-categories" id="global-search-categories">
						<button type="button" class="gs-cat-pill active" data-cat="all">
							<i class="fa fa-th-large"></i> Tümü <span class="gs-count" id="count-all">0</span>
						</button>
						<button type="button" class="gs-cat-pill" data-cat="offers">
							<i class="fa fa-file-text-o"></i> Teklifler <span class="gs-count" id="count-offers">0</span>
						</button>
						<button type="button" class="gs-cat-pill" data-cat="products">
							<i class="fa fa-cube"></i> Ürünler <span class="gs-count" id="count-products">0</span>
						</button>
						<button type="button" class="gs-cat-pill" data-cat="customers">
							<i class="fa fa-building-o"></i> Firmalar <span class="gs-count" id="count-customers">0</span>
						</button>
						<button type="button" class="gs-cat-pill" data-cat="services">
							<i class="fa fa-wrench"></i> Servisler <span class="gs-count" id="count-services">0</span>
						</button>
						<button type="button" class="gs-cat-pill" data-cat="kesifler">
							<i class="fa fa-search-plus"></i> Keşifler <span class="gs-count" id="count-kesifler">0</span>
						</button>
						<button type="button" class="gs-cat-pill" data-cat="reports">
							<i class="fa fa-file-text"></i> Raporlar <span class="gs-count" id="count-reports">0</span>
						</button>
					</div>

					<!-- Sonuç İçerik Alanı -->
					<div class="global-search-results" id="global-search-results">
						<!-- JS dinamik render edecek -->
					</div>

					<!-- Alt Bilgi / Kısayol İpuçları Çubuğu -->
					<div class="global-search-footer">
						<div class="gs-footer-info" id="gs-footer-info">
							Toplam <span id="gs-total-count">0</span> sonuç bulundu
						</div>
						<div class="gs-footer-hints">
							<span class="gs-hint-item"><kbd>↑</kbd><kbd>↓</kbd> Gezin</span>
							<span class="gs-hint-item"><kbd>↵</kbd> Seç</span>
							<span class="gs-hint-item"><kbd>Esc</kbd> Kapat</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Sağ: Hızlı Araçlar (Tema Ayarları & Toggle) + Kullanıcı Profili -->
		<div class="header-right-actions d-flex align-items-center">
			<!-- Tema Özelleştirici Ayarlar Butonu (Her zaman görünür) -->
			<a href="javascript:void(0)" class="header-action-btn theme-customizer-btn" id="theme-customizer-btn" onclick="openThemeCustomizer(event);" data-tooltip="Tema Özelleştirici" data-tooltip-location="bottom" title="Tema Özelleştirici">
				<i class="fa fa-sliders"></i>
			</a>

			<!-- Dark mode / Light mode Toggle Butonu (Her zaman görünür) -->
			<a href="javascript:void(0)" class="header-action-btn theme-toggle-btn" id="theme-toggle" onclick="toggleTheme(event);" data-tooltip="Temayı Değiştir" data-tooltip-location="bottom" title="Temayı Değiştir">
				<i class="fa fa-moon-o theme-icon-moon"></i>
				<i class="fa fa-sun-o theme-icon-sun"></i>
			</a>

			<!-- Kullanıcı Profili Dropdown -->
			<div class="dropdown user-dropdown ml-1 ml-sm-2">
				<a class="dropdown-toggle user-toggle-btn no-arrow" href="#" role="button" data-toggle="dropdown" data-bs-toggle="dropdown">
					<span class="user-avatar-badge">
						<i class="fa fa-user"></i>
					</span>
					<span class="user-name d-none d-md-inline">
						<?php echo sesset("username"); ?>
					</span>
					<i class="fa fa-angle-down ml-1 text-muted font-12 d-none d-md-inline"></i>
				</a>
				<div class="dropdown-menu dropdown-menu-right">
					<div class="dropdown-header d-md-none font-weight-bold text-dark border-bottom pb-2 mb-2">
						<i class="fa fa-user mr-1"></i> <?php echo sesset("username"); ?>
					</div>
					<a class="dropdown-item" href="index.php?p=profile">
						<i class="fa fa-user-circle-o mr-2" aria-hidden="true"></i> Profil Düzenle
					</a>
					<a class="dropdown-item" href="index.php?p=settings">
						<i class="fa fa-cog mr-2" aria-hidden="true"></i> Ayarlar
					</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item text-danger" href="logout.php">
						<i class="fa fa-sign-out mr-2" aria-hidden="true"></i> Çıkış Yap
					</a>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Mobil Sidebar Backdrop (Karartma Overlay) -->
<div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeMobileSidebar();"></div>

<!-- Tema Özelleştirici Backdrop -->
<div class="theme-customizer-backdrop" id="theme-customizer-backdrop" onclick="closeThemeCustomizer();"></div>

<!-- Tema Özelleştirici Sağ Çekmece Paneli -->
<div class="theme-customizer-drawer" id="theme-customizer-drawer">
	<div class="theme-customizer-header">
		<h5 class="theme-customizer-title">Tema Özelleştirici</h5>
		<button type="button" class="theme-customizer-close" onclick="closeThemeCustomizer();" title="Kapat">
			<i class="fa fa-times"></i>
		</button>
	</div>
	<div class="theme-customizer-body">
		<div class="theme-customizer-section-title-wrap">
			<h6 class="theme-customizer-section-title">Hazır Temalar (Ön Tanımlı)</h6>
			<span class="theme-customizer-badge">Tek Tıkla Uygula</span>
		</div>
		<div class="theme-presets-scroll-wrap">
			<div class="theme-presets-grid">
				<!-- 1. Kode -->
				<div class="theme-preset-card" data-preset="kode" onclick="selectThemePreset('kode');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #2563eb;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1e293b;"></div>
							<div class="theme-preview-content" style="background: #f8fafc;">
								<div class="theme-preview-pill" style="background: #2563eb;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Kode</div>
				</div>

				<!-- 2. Ersan Gold -->
				<div class="theme-preset-card" data-preset="ersan-gold" onclick="selectThemePreset('ersan-gold');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1e293b;"></div>
							<div class="theme-preview-content" style="background: #f8fafc;">
								<div class="theme-preview-pill" style="background: #d97706;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Ersan Gold</div>
				</div>

				<!-- 3. Zümrüt -->
				<div class="theme-preset-card" data-preset="zumrut" onclick="selectThemePreset('zumrut');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #059669;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #132a24;"></div>
							<div class="theme-preview-content" style="background: #f0fdf4;">
								<div class="theme-preview-pill" style="background: #10b981;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Zümrüt</div>
				</div>

				<!-- 4. Kraliyet Moru -->
				<div class="theme-preset-card" data-preset="kraliyet-moru" onclick="selectThemePreset('kraliyet-moru');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #5b21b6;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1e1b4b;"></div>
							<div class="theme-preview-content" style="background: #faf5ff;">
								<div class="theme-preview-pill" style="background: #7c3aed;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Kraliyet Moru</div>
				</div>

				<!-- 5. Rose -->
				<div class="theme-preset-card" data-preset="rose" onclick="selectThemePreset('rose');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #e11d48;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1f1924;"></div>
							<div class="theme-preview-content" style="background: #fff1f2;">
								<div class="theme-preview-pill" style="background: #e11d48;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Rose</div>
				</div>

				<!-- 6. Sade Beyaz -->
				<div class="theme-preset-card" data-preset="sade-beyaz" onclick="selectThemePreset('sade-beyaz');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #ffffff; border-right: 1px solid #e2e8f0;"></div>
							<div class="theme-preview-content" style="background: #f8fafc;">
								<div class="theme-preview-pill" style="background: #0f172a;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Sade Beyaz</div>
				</div>

				<!-- 7. Koyu Gece -->
				<div class="theme-preset-card" data-preset="koyu-gece" onclick="selectThemePreset('koyu-gece');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #1e293b;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #0f172a;"></div>
							<div class="theme-preview-content" style="background: #0b0f19;">
								<div class="theme-preview-pill" style="background: #06b6d4;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Koyu Gece</div>
				</div>

				<!-- 8. Safir Okyanus -->
				<div class="theme-preset-card" data-preset="safir-okyanus" onclick="selectThemePreset('safir-okyanus');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #0284c7;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #0f172a;"></div>
							<div class="theme-preview-content" style="background: #f0f9ff;">
								<div class="theme-preview-pill" style="background: #0284c7;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Safir Okyanus</div>
				</div>

				<!-- 9. Gün Batımı -->
				<div class="theme-preset-card" data-preset="gun-batimi" onclick="selectThemePreset('gun-batimi');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #ea580c;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1c1917;"></div>
							<div class="theme-preview-content" style="background: #fff7ed;">
								<div class="theme-preview-pill" style="background: #ea580c;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gün Batımı</div>
				</div>

				<!-- 10. Gece Altını -->
				<div class="theme-preset-card" data-preset="gece-altini" onclick="selectThemePreset('gece-altini');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #18181b;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #09090b;"></div>
							<div class="theme-preview-content" style="background: #18181b;">
								<div class="theme-preview-pill" style="background: #eab308;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gece Altını</div>
				</div>

				<!-- 11. Mistik Bordo -->
				<div class="theme-preset-card" data-preset="mistik-bordo" onclick="selectThemePreset('mistik-bordo');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #881337;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1e1117;"></div>
							<div class="theme-preview-content" style="background: #fff1f2;">
								<div class="theme-preview-pill" style="background: #be123c;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Mistik Bordo</div>
				</div>

				<!-- 12. Nordik Çam -->
				<div class="theme-preset-card" data-preset="nordik-cam" onclick="selectThemePreset('nordik-cam');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #14532d;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #0c1f15;"></div>
							<div class="theme-preview-content" style="background: #f0fdf4;">
								<div class="theme-preview-pill" style="background: #16a34a;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Nordik Çam</div>
				</div>

				<!-- 13. Soft Lavanta -->
				<div class="theme-preset-card" data-preset="soft-lavanta" onclick="selectThemePreset('soft-lavanta');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #f5f3ff; border-bottom: 1px solid #e9d5ff;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1e1b4b;"></div>
							<div class="theme-preview-content" style="background: #faf5ff;">
								<div class="theme-preview-pill" style="background: #8b5cf6;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Soft Lavanta</div>
				</div>

				<!-- 14. Soft Adaçayı -->
				<div class="theme-preset-card" data-preset="soft-adacayi" onclick="selectThemePreset('soft-adacayi');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #f0fdfa; border-bottom: 1px solid #ccfbf1;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #132a24;"></div>
							<div class="theme-preview-content" style="background: #f0fdf4;">
								<div class="theme-preview-pill" style="background: #14b8a6;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Soft Adaçayı</div>
				</div>

				<!-- 15. Soft Şeftali -->
				<div class="theme-preset-card" data-preset="soft-seftali" onclick="selectThemePreset('soft-seftali');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #fff7ed; border-bottom: 1px solid #ffedd5;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1c1917;"></div>
							<div class="theme-preview-content" style="background: #fffaf5;">
								<div class="theme-preview-pill" style="background: #fb923c;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Soft Şeftali</div>
				</div>

				<!-- 16. Soft Buz Mavisi -->
				<div class="theme-preset-card" data-preset="soft-buz-mavisi" onclick="selectThemePreset('soft-buz-mavisi');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #f0f9ff; border-bottom: 1px solid #e0f2fe;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #0f172a;"></div>
							<div class="theme-preview-content" style="background: #f8fafc;">
								<div class="theme-preview-pill" style="background: #38bdf8;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Soft Buz Mavisi</div>
				</div>

				<!-- 17. Soft Vizon -->
				<div class="theme-preset-card" data-preset="soft-vizon" onclick="selectThemePreset('soft-vizon');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #fafaf9; border-bottom: 1px solid #e7e5e4;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #292524;"></div>
							<div class="theme-preview-content" style="background: #fafaf9;">
								<div class="theme-preview-pill" style="background: #a8a29e;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Soft Vizon</div>
				</div>

				<!-- 18. Modern Çelik (Mavi Topbar + Çelik Slate Gri Sidebar) -->
				<div class="theme-preset-card" data-preset="modern-celik" onclick="selectThemePreset('modern-celik');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #2563eb;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #334155;"></div>
							<div class="theme-preview-content" style="background: #f8fafc;">
								<div class="theme-preview-pill" style="background: #2563eb;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Modern Çelik</div>
				</div>

				<!-- 19. Antrasit Zümrüt (Zümrüt Topbar + Antrasit Gri Sidebar) -->
				<div class="theme-preset-card" data-preset="antrasit-zumrut" onclick="selectThemePreset('antrasit-zumrut');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #059669;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #374151;"></div>
							<div class="theme-preview-content" style="background: #f0fdf4;">
								<div class="theme-preview-pill" style="background: #10b981;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Antrasit Zümrüt</div>
				</div>

				<!-- 20. Dumanlı Bordo (Bordo Topbar + Duman Gri Sidebar) -->
				<div class="theme-preset-card" data-preset="dumanli-bordo" onclick="selectThemePreset('dumanli-bordo');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #9f1239;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #3f3f46;"></div>
							<div class="theme-preview-content" style="background: #fff1f2;">
								<div class="theme-preview-pill" style="background: #e11d48;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Dumanlı Bordo</div>
				</div>

				<!-- 21. Grafiti Mor (Mor Topbar + Grafit Gri Sidebar) -->
				<div class="theme-preset-card" data-preset="grafiti-mor" onclick="selectThemePreset('grafiti-mor');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #7c3aed;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #27272a;"></div>
							<div class="theme-preview-content" style="background: #faf5ff;">
								<div class="theme-preview-pill" style="background: #8b5cf6;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Grafiti Mor</div>
				</div>

				<!-- 22. Kül Amber (Amber Topbar + Kül Slate Gri Sidebar) -->
				<div class="theme-preset-card" data-preset="kul-amber" onclick="selectThemePreset('kul-amber');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #d97706;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #334155;"></div>
							<div class="theme-preview-content" style="background: #fffbeb;">
								<div class="theme-preview-pill" style="background: #f59e0b;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Kül Amber</div>
				</div>

				<!-- 23. Petrol Taş (Petrol Topbar + Taş Gri Sidebar) -->
				<div class="theme-preset-card" data-preset="petrol-tas" onclick="selectThemePreset('petrol-tas');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #0f766e;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #374151;"></div>
							<div class="theme-preview-content" style="background: #f0fdfa;">
								<div class="theme-preview-pill" style="background: #14b8a6;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Petrol Taş</div>
				</div>

				<!-- 24. Platin Mavi (Safir Topbar + Açık Platin Gri Sidebar) -->
				<div class="theme-preset-card" data-preset="platin-mavi" onclick="selectThemePreset('platin-mavi');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #0284c7;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #e2e8f0; border-right: 1px solid #cbd5e1;"></div>
							<div class="theme-preview-content" style="background: #f8fafc;">
								<div class="theme-preview-pill" style="background: #0284c7;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Platin Mavi</div>
				</div>

				<!-- 25. Titan Okyanus (Gece Laciverti Topbar + Titan Çelik Gri Sidebar) -->
				<div class="theme-preset-card" data-preset="titan-okyanus" onclick="selectThemePreset('titan-okyanus');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: #1e3a8a;"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #475569;"></div>
							<div class="theme-preview-content" style="background: #f0f9ff;">
								<div class="theme-preview-pill" style="background: #3b82f6;"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Titan Okyanus</div>
				</div>

				<!-- 26. Gradient Siber Mor (Sidebar Tonundan Royale Mora Akış) -->
				<div class="theme-preset-card" data-preset="gradient-mor" onclick="selectThemePreset('gradient-mor');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #1e1b4b 0%, #4338ca 45%, #7c3aed 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1e1b4b;"></div>
							<div class="theme-preview-content" style="background: #faf5ff;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #4338ca, #7c3aed);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Siber Mor</div>
				</div>

				<!-- 27. Gradient Safir Okyanus (Sidebar Tonundan Derin Maviye Akış) -->
				<div class="theme-preset-card" data-preset="gradient-safir" onclick="selectThemePreset('gradient-safir');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #0f172a 0%, #1e3a8a 45%, #0284c7 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #0f172a;"></div>
							<div class="theme-preview-content" style="background: #f0f9ff;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #1e3a8a, #0284c7);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Safir Okyanus</div>
				</div>

				<!-- 28. Gradient Zümrüt Gece (Sidebar Tonundan Zümrüt Yeşiline Akış) -->
				<div class="theme-preset-card" data-preset="gradient-zumrut" onclick="selectThemePreset('gradient-zumrut');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #132a24 0%, #065f46 45%, #059669 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #132a24;"></div>
							<div class="theme-preview-content" style="background: #f0fdf4;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #065f46, #059669);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Zümrüt Gece</div>
				</div>

				<!-- 29. Gradient Yakut Bordo (Sidebar Tonundan Ateş Yakuta Akış) -->
				<div class="theme-preset-card" data-preset="gradient-yakut" onclick="selectThemePreset('gradient-yakut');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #1e1117 0%, #881337 45%, #e11d48 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1e1117;"></div>
							<div class="theme-preview-content" style="background: #fff1f2;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #881337, #e11d48);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Yakut Bordo</div>
				</div>

				<!-- 30. Gradient Gün Batımı (Sidebar Tonundan Altın Ambere Akış) -->
				<div class="theme-preset-card" data-preset="gradient-amber" onclick="selectThemePreset('gradient-amber');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #1c1917 0%, #9a3412 45%, #ea580c 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1c1917;"></div>
							<div class="theme-preview-content" style="background: #fff7ed;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #9a3412, #ea580c);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Gün Batımı</div>
				</div>

				<!-- 31. Gradient Siber Petrol (Sidebar Tonundan Turkuaz Yeşiline Akış) -->
				<div class="theme-preset-card" data-preset="gradient-petrol" onclick="selectThemePreset('gradient-petrol');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #0d1f1e 0%, #115e59 45%, #0d9488 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #0d1f1e;"></div>
							<div class="theme-preview-content" style="background: #f0fdfa;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #115e59, #0d9488);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Siber Petrol</div>
				</div>

				<!-- 32. Gradient Kozmik Lacivert (Sidebar Tonundan Elektrik Mavisine Akış) -->
				<div class="theme-preset-card" data-preset="gradient-lacivert" onclick="selectThemePreset('gradient-lacivert');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #0a0f1d 0%, #1e3a8a 45%, #3b82f6 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #0a0f1d;"></div>
							<div class="theme-preview-content" style="background: #f0f9ff;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #1e3a8a, #3b82f6);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Kozmik Lacivert</div>
				</div>

				<!-- 33. Gradient Titanyum Çelik (Sidebar Tonundan Çelik Griye Akış) -->
				<div class="theme-preset-card" data-preset="gradient-titanyum" onclick="selectThemePreset('gradient-titanyum');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #18181b 0%, #334155 45%, #64748b 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #18181b;"></div>
							<div class="theme-preview-content" style="background: #f8fafc;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #334155, #64748b);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Titanyum Çelik</div>
				</div>

				<!-- 34. Gradient Siber Fuşya (Sidebar Tonundan Neon Pembe-Mora Akış) -->
				<div class="theme-preset-card" data-preset="gradient-magenta" onclick="selectThemePreset('gradient-magenta');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #1f1124 0%, #831843 45%, #db2777 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #1f1124;"></div>
							<div class="theme-preview-content" style="background: #fdf2f8;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #831843, #db2777);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Siber Fuşya</div>
				</div>

				<!-- 35. Gradient Lüks Altın (Sidebar Obsidyenden Parlayan Altına Akış) -->
				<div class="theme-preset-card" data-preset="gradient-altin" onclick="selectThemePreset('gradient-altin');">
					<div class="theme-preview-box">
						<div class="theme-preview-header" style="background: linear-gradient(90deg, #18181b 0%, #713f12 45%, #ca8a04 100%);"></div>
						<div class="theme-preview-body">
							<div class="theme-preview-sidebar" style="background: #18181b;"></div>
							<div class="theme-preview-content" style="background: #fefce8;">
								<div class="theme-preview-pill" style="background: linear-gradient(90deg, #713f12, #ca8a04);"></div>
							</div>
						</div>
					</div>
					<div class="theme-preset-name">Gradient Lüks Altın</div>
				</div>
			</div>
		</div>

		<!-- 2. Bölüm: Topbar (Üst Menü) Rengi -->
		<div class="theme-customizer-section-title-wrap mt-4">
			<h6 class="theme-customizer-section-title">Topbar (Üst Menü) Rengi</h6>
			<span class="theme-customizer-badge" style="background: #dbeafe; color: #2563eb;">Üst Bar</span>
		</div>
		<div class="theme-color-palette-grid">
			<!-- Düz Renkler -->
			<button type="button" class="theme-color-swatch-btn" data-topbar="mavi" onclick="selectTopbarTheme('mavi', true);">
				<span class="theme-color-dot" style="background: #2563eb;"></span>
				<span class="theme-color-label">Mavi</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="zumrut" onclick="selectTopbarTheme('zumrut', true);">
				<span class="theme-color-dot" style="background: #059669;"></span>
				<span class="theme-color-label">Zümrüt</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="mor" onclick="selectTopbarTheme('mor', true);">
				<span class="theme-color-dot" style="background: #7c3aed;"></span>
				<span class="theme-color-label">Mor</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="rose" onclick="selectTopbarTheme('rose', true);">
				<span class="theme-color-dot" style="background: #e11d48;"></span>
				<span class="theme-color-label">Rose</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="bordo" onclick="selectTopbarTheme('bordo', true);">
				<span class="theme-color-dot" style="background: #9f1239;"></span>
				<span class="theme-color-label">Bordo</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="amber" onclick="selectTopbarTheme('amber', true);">
				<span class="theme-color-dot" style="background: #d97706;"></span>
				<span class="theme-color-label">Amber</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="safir" onclick="selectTopbarTheme('safir', true);">
				<span class="theme-color-dot" style="background: #0284c7;"></span>
				<span class="theme-color-label">Safir</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="nordik" onclick="selectTopbarTheme('nordik', true);">
				<span class="theme-color-dot" style="background: #15803d;"></span>
				<span class="theme-color-label">Nordik Çam</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="lacivert" onclick="selectTopbarTheme('lacivert', true);">
				<span class="theme-color-dot" style="background: #1e3a8a;"></span>
				<span class="theme-color-label">Lacivert</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="petrol" onclick="selectTopbarTheme('petrol', true);">
				<span class="theme-color-dot" style="background: #0f766e;"></span>
				<span class="theme-color-label">Petrol</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="oniks" onclick="selectTopbarTheme('oniks', true);">
				<span class="theme-color-dot" style="background: #18181b;"></span>
				<span class="theme-color-label">Koyu Oniks</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="beyaz" onclick="selectTopbarTheme('beyaz', true);">
				<span class="theme-color-dot" style="background: #ffffff; border: 1px solid #cbd5e1;"></span>
				<span class="theme-color-label">Beyaz</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="lavanta" onclick="selectTopbarTheme('lavanta', true);">
				<span class="theme-color-dot" style="background: #ede9fe;"></span>
				<span class="theme-color-label">Soft Lavanta</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="adacayi" onclick="selectTopbarTheme('adacayi', true);">
				<span class="theme-color-dot" style="background: #ccfbf1;"></span>
				<span class="theme-color-label">Soft Adaçayı</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="buz-mavisi" onclick="selectTopbarTheme('buz-mavisi', true);">
				<span class="theme-color-dot" style="background: #e0f2fe;"></span>
				<span class="theme-color-label">Soft Buz</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="vizon" onclick="selectTopbarTheme('vizon', true);">
				<span class="theme-color-dot" style="background: #f5f5f4; border: 1px solid #d6d3d1;"></span>
				<span class="theme-color-label">Soft Vizon</span>
			</button>

			<!-- Gradient (Degrade) Topbar Seçenekleri -->
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-mor" onclick="selectTopbarTheme('gradient-mor', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #1e1b4b 0%, #7c3aed 100%);"></span>
				<span class="theme-color-label">Grad. Mor</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-safir" onclick="selectTopbarTheme('gradient-safir', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #0f172a 0%, #0284c7 100%);"></span>
				<span class="theme-color-label">Grad. Safir</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-zumrut" onclick="selectTopbarTheme('gradient-zumrut', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #132a24 0%, #059669 100%);"></span>
				<span class="theme-color-label">Grad. Zümrüt</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-yakut" onclick="selectTopbarTheme('gradient-yakut', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #1e1117 0%, #e11d48 100%);"></span>
				<span class="theme-color-label">Grad. Yakut</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-amber" onclick="selectTopbarTheme('gradient-amber', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #1c1917 0%, #ea580c 100%);"></span>
				<span class="theme-color-label">Grad. Gün Batımı</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-petrol" onclick="selectTopbarTheme('gradient-petrol', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #0d1f1e 0%, #0d9488 100%);"></span>
				<span class="theme-color-label">Grad. Petrol</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-lacivert" onclick="selectTopbarTheme('gradient-lacivert', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #0a0f1d 0%, #3b82f6 100%);"></span>
				<span class="theme-color-label">Grad. Lacivert</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-titanyum" onclick="selectTopbarTheme('gradient-titanyum', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #18181b 0%, #64748b 100%);"></span>
				<span class="theme-color-label">Grad. Çelik</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-magenta" onclick="selectTopbarTheme('gradient-magenta', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #1f1124 0%, #db2777 100%);"></span>
				<span class="theme-color-label">Grad. Fuşya</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-topbar="gradient-altin" onclick="selectTopbarTheme('gradient-altin', true);">
				<span class="theme-color-dot" style="background: linear-gradient(135deg, #18181b 0%, #ca8a04 100%);"></span>
				<span class="theme-color-label">Grad. Altın</span>
			</button>
		</div>

		<!-- 3. Bölüm: Sidebar (Sol Menü) Rengi -->
		<div class="theme-customizer-section-title-wrap mt-4">
			<h6 class="theme-customizer-section-title">Sidebar (Sol Menü) Rengi</h6>
			<span class="theme-customizer-badge" style="background: #f1f5f9; color: #475569;">Sol Menü</span>
		</div>
		<div class="theme-color-palette-grid">
			<button type="button" class="theme-color-swatch-btn" data-sidebar="klasik-koyu" onclick="selectSidebarTheme('klasik-koyu', true);">
				<span class="theme-color-dot" style="background: #1e1e2d;"></span>
				<span class="theme-color-label">Klasik Koyu</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="slate-gri" onclick="selectSidebarTheme('slate-gri', true);">
				<span class="theme-color-dot" style="background: #334155;"></span>
				<span class="theme-color-label">Slate Gri</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="antrasit-gri" onclick="selectSidebarTheme('antrasit-gri', true);">
				<span class="theme-color-dot" style="background: #374151;"></span>
				<span class="theme-color-label">Antrasit Gri</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="duman-gri" onclick="selectSidebarTheme('duman-gri', true);">
				<span class="theme-color-dot" style="background: #3f3f46;"></span>
				<span class="theme-color-label">Duman Gri</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="grafit-gri" onclick="selectSidebarTheme('grafit-gri', true);">
				<span class="theme-color-dot" style="background: #27272a;"></span>
				<span class="theme-color-label">Grafit Gri</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="titan-gri" onclick="selectSidebarTheme('titan-gri', true);">
				<span class="theme-color-dot" style="background: #475569;"></span>
				<span class="theme-color-label">Titan Gri</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-zumrut" onclick="selectSidebarTheme('koyu-zumrut', true);">
				<span class="theme-color-dot" style="background: #132a24;"></span>
				<span class="theme-color-label">Koyu Zümrüt</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-mor" onclick="selectSidebarTheme('koyu-mor', true);">
				<span class="theme-color-dot" style="background: #1e1b4b;"></span>
				<span class="theme-color-label">Koyu Mor</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-bordo" onclick="selectSidebarTheme('koyu-bordo', true);">
				<span class="theme-color-dot" style="background: #1e1117;"></span>
				<span class="theme-color-label">Koyu Bordo</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-okyanus" onclick="selectSidebarTheme('koyu-okyanus', true);">
				<span class="theme-color-dot" style="background: #0f172a;"></span>
				<span class="theme-color-label">Koyu Okyanus</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-volkan" onclick="selectSidebarTheme('koyu-volkan', true);">
				<span class="theme-color-dot" style="background: #1c1917;"></span>
				<span class="theme-color-label">Koyu Volkan</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-petrol" onclick="selectSidebarTheme('koyu-petrol', true);">
				<span class="theme-color-dot" style="background: #0d1f1e;"></span>
				<span class="theme-color-label">Koyu Petrol</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-nebula" onclick="selectSidebarTheme('koyu-nebula', true);">
				<span class="theme-color-dot" style="background: #0a0f1d;"></span>
				<span class="theme-color-label">Koyu Nebula</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-magenta" onclick="selectSidebarTheme('koyu-magenta', true);">
				<span class="theme-color-dot" style="background: #1f1124;"></span>
				<span class="theme-color-label">Koyu Fuşya</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="platin-gri" onclick="selectSidebarTheme('platin-gri', true);">
				<span class="theme-color-dot" style="background: #e2e8f0; border: 1px solid #cbd5e1;"></span>
				<span class="theme-color-label">Platin Gri</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-sidebar="sade-beyaz" onclick="selectSidebarTheme('sade-beyaz', true);">
				<span class="theme-color-dot" style="background: #ffffff; border: 1px solid #cbd5e1;"></span>
				<span class="theme-color-label">Sade Beyaz</span>
			</button>
		</div>

		<!-- 4. Bölüm: Vurgu & Birincil Renk (Primary Color) -->
		<div class="theme-customizer-section-title-wrap mt-4">
			<h6 class="theme-customizer-section-title">Vurgu & Birincil Renk (Primary)</h6>
			<span class="theme-customizer-badge" style="background: #fef3c7; color: #b45309;">Buton & Focus</span>
		</div>
		<div class="theme-color-palette-grid">
			<button type="button" class="theme-color-swatch-btn" data-primary="mavi" onclick="selectPrimaryTheme('#2563eb', 'mavi', true);">
				<span class="theme-color-dot" style="background: #2563eb;"></span>
				<span class="theme-color-label">Mavi</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="zumrut" onclick="selectPrimaryTheme('#059669', 'zumrut', true);">
				<span class="theme-color-dot" style="background: #059669;"></span>
				<span class="theme-color-label">Zümrüt</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="mor" onclick="selectPrimaryTheme('#7c3aed', 'mor', true);">
				<span class="theme-color-dot" style="background: #7c3aed;"></span>
				<span class="theme-color-label">Mor</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="rose" onclick="selectPrimaryTheme('#e11d48', 'rose', true);">
				<span class="theme-color-dot" style="background: #e11d48;"></span>
				<span class="theme-color-label">Rose</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="bordo" onclick="selectPrimaryTheme('#9f1239', 'bordo', true);">
				<span class="theme-color-dot" style="background: #9f1239;"></span>
				<span class="theme-color-label">Bordo</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="amber" onclick="selectPrimaryTheme('#ea580c', 'amber', true);">
				<span class="theme-color-dot" style="background: #ea580c;"></span>
				<span class="theme-color-label">Amber</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="altin" onclick="selectPrimaryTheme('#ca8a04', 'altin', true);">
				<span class="theme-color-dot" style="background: #ca8a04;"></span>
				<span class="theme-color-label">Altın</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="safir" onclick="selectPrimaryTheme('#0284c7', 'safir', true);">
				<span class="theme-color-dot" style="background: #0284c7;"></span>
				<span class="theme-color-label">Safir</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="petrol" onclick="selectPrimaryTheme('#0f766e', 'petrol', true);">
				<span class="theme-color-dot" style="background: #0f766e;"></span>
				<span class="theme-color-label">Petrol</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="indigo" onclick="selectPrimaryTheme('#4f46e5', 'indigo', true);">
				<span class="theme-color-dot" style="background: #4f46e5;"></span>
				<span class="theme-color-label">İndigo</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="magenta" onclick="selectPrimaryTheme('#db2777', 'magenta', true);">
				<span class="theme-color-dot" style="background: #db2777;"></span>
				<span class="theme-color-label">Fuşya</span>
			</button>
			<button type="button" class="theme-color-swatch-btn" data-primary="slate" onclick="selectPrimaryTheme('#475569', 'slate', true);">
				<span class="theme-color-dot" style="background: #475569;"></span>
				<span class="theme-color-label">Çelik Slate</span>
			</button>
			<!-- Özel Renk Seçici -->
			<div class="theme-custom-color-picker-wrap" style="grid-column: span 2; display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px dashed #cbd5e1; padding: 6px 12px; border-radius: 8px; margin-top: 4px;">
				<div style="display: flex; align-items: center; gap: 8px;">
					<input type="color" id="customPrimaryColorPicker" style="width: 28px; height: 28px; border: none; border-radius: 6px; cursor: pointer; padding: 0; background: transparent;" value="#2563eb" oninput="selectPrimaryTheme(this.value, 'custom', true);">
					<label for="customPrimaryColorPicker" style="margin: 0; font-size: 12px; font-weight: 600; color: #475569; cursor: pointer;">Özel Renk Seç (HEX)</label>
				</div>
				<span id="customPrimaryHexLabel" style="font-size: 11px; font-family: monospace; color: #64748b; font-weight: 600;">#2563eb</span>
			</div>
		</div>

		<!-- 5. Bölüm: Yazı Tipi (Font) Seçimi -->
		<div class="theme-customizer-section-title-wrap mt-4">
			<h6 class="theme-customizer-section-title">Yazı Tipi (Font)</h6>
			<span class="theme-customizer-badge" style="background: #e0f2fe; color: #0284c7;">Tipografi</span>
		</div>
		<div class="theme-fonts-grid">
			<button type="button" class="theme-font-btn" data-font="inter" onclick="selectThemeFont('inter', true);" style="font-family: 'Inter', sans-serif;">
				<span class="theme-font-name">Inter</span>
				<span class="theme-font-sample">Modern UI</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="plus-jakarta-sans" onclick="selectThemeFont('plus-jakarta-sans', true);" style="font-family: 'Plus Jakarta Sans', sans-serif;">
				<span class="theme-font-name">Plus Jakarta</span>
				<span class="theme-font-sample">Kurumsal & SaaS</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="outfit" onclick="selectThemeFont('outfit', true);" style="font-family: 'Outfit', sans-serif;">
				<span class="theme-font-name">Outfit</span>
				<span class="theme-font-sample">Estetik & Yuvarlak</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="poppins" onclick="selectThemeFont('poppins', true);" style="font-family: 'Poppins', sans-serif;">
				<span class="theme-font-name">Poppins</span>
				<span class="theme-font-sample">Geometrik & Canlı</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="montserrat" onclick="selectThemeFont('montserrat', true);" style="font-family: 'Montserrat', sans-serif;">
				<span class="theme-font-name">Montserrat</span>
				<span class="theme-font-sample">Prestij & Şık</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="geist" onclick="selectThemeFont('geist', true);" style="font-family: 'Geist', sans-serif;">
				<span class="theme-font-name">Geist</span>
				<span class="theme-font-sample">Minimal & Tech</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="dm-sans" onclick="selectThemeFont('dm-sans', true);" style="font-family: 'DM Sans', sans-serif;">
				<span class="theme-font-name">DM Sans</span>
				<span class="theme-font-sample">SaaS & Ultra Temiz</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="manrope" onclick="selectThemeFont('manrope', true);" style="font-family: 'Manrope', sans-serif;">
				<span class="theme-font-name">Manrope</span>
				<span class="theme-font-sample">Modern & Profesyonel</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="space-grotesk" onclick="selectThemeFont('space-grotesk', true);" style="font-family: 'Space Grotesk', sans-serif;">
				<span class="theme-font-name">Space Grotesk</span>
				<span class="theme-font-sample">Tekno & Karakteristik</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="urbanist" onclick="selectThemeFont('urbanist', true);" style="font-family: 'Urbanist', sans-serif;">
				<span class="theme-font-name">Urbanist</span>
				<span class="theme-font-sample">Geometrik & Yalın</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="figtree" onclick="selectThemeFont('figtree', true);" style="font-family: 'Figtree', sans-serif;">
				<span class="theme-font-name">Figtree</span>
				<span class="theme-font-sample">Dinamik & Net</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="sora" onclick="selectThemeFont('sora', true);" style="font-family: 'Sora', sans-serif;">
				<span class="theme-font-name">Sora</span>
				<span class="theme-font-sample">Fütüristik & Şık</span>
			</button>
			<button type="button" class="theme-font-btn" data-font="roboto" onclick="selectThemeFont('roboto', true);" style="font-family: 'Roboto', sans-serif;">
				<span class="theme-font-name">Roboto</span>
				<span class="theme-font-sample">Klasik & Sade</span>
			</button>
		</div>

		<!-- 5. Bölüm: Yazı Tipi Kalınlığı (Font Weight) Seçimi -->
		<div class="theme-customizer-section-title-wrap mt-4">
			<h6 class="theme-customizer-section-title">Yazı Tipi Kalınlığı (Font Weight)</h6>
			<span class="theme-customizer-badge" style="background: #fef3c7; color: #b45309;">Kalınlık</span>
		</div>
		<div class="theme-weights-grid">
			<button type="button" class="theme-weight-btn" data-weight="400" onclick="selectThemeWeight('400', true);">
				<span class="theme-weight-name" style="font-weight: 400;">Normal (400)</span>
				<span class="theme-weight-sample">Varsayılan & İnce/Zarif</span>
			</button>
			<button type="button" class="theme-weight-btn" data-weight="500" onclick="selectThemeWeight('500', true);">
				<span class="theme-weight-name" style="font-weight: 500;">Orta (500)</span>
				<span class="theme-weight-sample">Daha Belirgin & Net</span>
			</button>
			<button type="button" class="theme-weight-btn" data-weight="600" onclick="selectThemeWeight('600', true);">
				<span class="theme-weight-name" style="font-weight: 600;">Yarı Kalın (600)</span>
				<span class="theme-weight-sample">Tok & Güçlü Okuma</span>
			</button>
			<button type="button" class="theme-weight-btn" data-weight="700" onclick="selectThemeWeight('700', true);">
				<span class="theme-weight-name" style="font-weight: 700;">Kalın (700)</span>
				<span class="theme-weight-sample">Vurgulu & Dolgun</span>
			</button>
		</div>
	</div>
</div>
