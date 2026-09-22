<div class="header clearfix">
	<div class="header-right">
		<!-- Sol: Mobil Logo + Menü İkonu + Breadcrumb -->
		<div class="header-left d-flex align-items-center">
			<div class="brand-logo d-lg-none">
				<a href="index.php">
					<img width="70" src="<?php echo set("logo"); ?>" alt="" class="mobile-logo">
				</a>
			</div>

			<div class="menu-icon" title="Menüyü Aç/Kapat">
				<span></span>
				<span></span>
				<span></span>
				<span></span>
			</div>

			<div class="header-breadcrumb ml-2">
				<nav aria-label="breadcrumb">
					<ol class="breadcrumb m-0 p-0 bg-transparent align-items-center">
						<li class="breadcrumb-item"><a href="index.php"><?php echo set("site_title"); ?></a></li>
						<li class="breadcrumb-item active" aria-current="page"><?php echo $pdat["p_title"] ?? 'Ana Sayfa'; ?></li>
					</ol>
				</nav>
			</div>
		</div>

		<!-- Orta: Global Arama (Teklif, Ürün, Firma, Servis, Keşif, Rapor) -->
		<div class="header-search-wrap">
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

		<!-- Sağ: Hızlı Araçlar (Tema, SMS, Mail) + Kullanıcı Profili -->
		<div class="header-right-actions d-flex align-items-center">
			<!-- Tema Özelleştirici Ayarlar Butonu -->
			<a href="javascript:void(0)" class="header-action-btn theme-customizer-btn" id="theme-customizer-btn" onclick="openThemeCustomizer(event);" data-tooltip="Tema Özelleştirici" data-tooltip-location="bottom">
				<i class="fa fa-sliders"></i>
			</a>

			<!-- Dark mode / Light mode Toggle Butonu -->
			<a href="javascript:void(0)" class="header-action-btn theme-toggle-btn" id="theme-toggle" onclick="toggleTheme(event);" data-tooltip="Temayı Değiştir" data-tooltip-location="bottom">
				<i class="fa fa-moon-o theme-icon-moon"></i>
				<i class="fa fa-sun-o theme-icon-sun"></i>
			</a>

			<a href="index.php?p=send-sms" class="header-action-btn" target="_blank" data-tooltip="SMS Gönder" data-tooltip-location="bottom">
				<i class="fa fa-paper-plane-o"></i>
			</a>
			<a href="index.php?p=send-mail" class="header-action-btn" target="_blank" data-tooltip="E-Posta Gönder" data-tooltip-location="bottom">
				<i class="fa fa-envelope-o"></i>
			</a>

			<!-- Kullanıcı Profili Dropdown -->
			<div class="dropdown user-dropdown ml-2">
				<a class="dropdown-toggle user-toggle-btn no-arrow" href="#" role="button" data-toggle="dropdown">
					<span class="user-avatar-badge">
						<i class="fa fa-user"></i>
					</span>
					<span class="user-name">
						<?php echo sesset("username"); ?>
					</span>
					<i class="fa fa-angle-down ml-1 text-muted font-12"></i>
				</a>
				<div class="dropdown-menu dropdown-menu-right">
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
		</div>

		<!-- 2. Bölüm: Yazı Tipi (Font) Seçimi -->
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
			<button type="button" class="theme-font-btn" data-font="roboto" onclick="selectThemeFont('roboto', true);" style="font-family: 'Roboto', sans-serif;">
				<span class="theme-font-name">Roboto</span>
				<span class="theme-font-sample">Klasik & Sade</span>
			</button>
		</div>
	</div>
</div>
