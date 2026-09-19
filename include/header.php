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

		<!-- Sağ: Hızlı Araçlar (Tema, SMS, Mail) + Kullanıcı Profili -->
		<div class="header-right-actions d-flex align-items-center">
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
					<a class="dropdown-item" href="index.php?p=user-edit&id=<?php echo sesset("id"); ?>">
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