	<div class="left-side-bar">
		<div class="brand-logo">
			<a href="index.php">
				<img style="margin: 0px" width="50" src="<?php echo set("logo"); ?>" alt="<?php echo set("site_title"); ?> Logo">
			</a><br><?php echo set("site_title"); ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</div>
		<div class="menu-block customscroll">
			<div class="sidebar-menu">
				<ul id="accordion-menu">
					<li class="dropdown">
						<a href="index.php?p=home" class="dropdown-toggle no-arrow">
							<span class="fa fa-home"></span><span class="mtext">Ana Sayfa</span>
						</a>

					</li>



					<?php if (permtrue("oview")) {
					?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-file-o"></span><span class="mtext">Teklifler</span>
							</a>
							<ul class="submenu">

								<?php if (permtrue("oadd")) { ?><li><a href="index.php?p=new-offer">Yeni Teklif Oluştur</a></li><?php } ?>
								<li><a href="index.php?p=all-offers">Teklifleri Görüntüle</a></li>
								<?php if (sesset("permission") == 1) { ?>
									<li><a href="index.php?p=units">Teklif Birimleri</a></li>
								<?php } ?>

							</ul>
						</li>
					<?php
					} ?>
					<?php if (permtrue("servview")) {
					?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-gear"></span><span class="mtext">Servisler</span>
							</a>
							<ul class="submenu">
							
								<?php if (permtrue("servadd")) { ?><li><a href="index.php?p=new-service">Yeni Servis Oluştur</a></li><?php } ?>
								<li><a href="index.php?p=all-services">Servisleri Görüntüle</a></li>

							</ul>
						</li>
					<?php
					} ?>

					<li class="dropdown">
						<a href="javascript:;" class="dropdown-toggle">
							<span class="fa fa-table"></span><span class="mtext">Proje Yönetimi</span>
						</a>
						<ul class="submenu">

							<?php if (permtrue("padd")) { ?><li><a href="index.php?p=new-project">Yeni Proje Oluştur</a></li><?php } ?>
							<li><a href="index.php?p=all-projects">Projeleri Görüntüle</a></li>

						</ul>
					</li>
					<li class="dropdown">
						<a href="javascript:;" class="dropdown-toggle">
							<span class="fa fa-arrow-right"></span><span class="mtext">Satış</span>
						</a>
						<ul class="submenu">

							<?php if (permtrue("sadd")) { ?><li><a href="index.php?p=new-sales">Yeni Satış Oluştur</a></li><?php } ?>
							<li><a href="index.php?p=sales">Satışları Görüntüle</a></li>

						</ul>
					</li>



					<li class="dropdown">
						<a href="javascript:;" class="dropdown-toggle">
							<span class="fa fa-pencil"></span><span class="mtext">Müşteri Yönetimi</span>
						</a>
						<ul class="submenu">

							<?php if (permtrue("cadd")) { ?><li><a href="index.php?p=new-customer">Yeni Müşteri</a></li><?php } ?>
							<li><a href="index.php?p=customer-list">Müşteri Listesi</a></li>
							<?php if (permtrue("cedit")) {
							?>
								<li><a href="index.php?p=customer-groups">Müşteri Grupları</a></li>
							<?php
							} ?>

						</ul>
					</li>
					<li class="dropdown">
						<a href="javascript:;" class="dropdown-toggle">
							<span class="fa fa-paint-brush"></span><span class="mtext">Ürün/Hizmetler</span>
						</a>
						<ul class="submenu">

							<?php if (permtrue("seradd") or permtrue("seredit") or permtrue("sercategory") or permtrue("serdelete")) { ?><li><a href="index.php?p=new-product">Yeni Oluştur</a></li><?php } ?>
							<li><a href="index.php?p=products">Ürün&Hizmet Listesi</a></li>
							<?php if (permtrue("sercategory")) { ?>
								<li><a href="index.php?p=categories">Kategorileri Düzenle</a></li>
							<?php } ?>
						</ul>

					</li>
					<li class="dropdown">
						<a href="javascript:;" class="dropdown-toggle">

							<span class="fa fa-folder-open"></span><span class="mtext">Evrak Takip</span>
						</a>
						<ul class="submenu">

							<?php if (permtrue("docoutadd")) { ?><li><a href="index.php?p=new-outdocument">Giden Evrak Ekle</a></li><?php } ?>
							<?php if (permtrue("docinadd")) { ?><li><a href="index.php?p=new-indocument">Gelen Evrak Ekle</a></li><?php } ?>
							<li><a href="index.php?p=document-list">Evrakları Görüntüle</a></li>

						</ul>
					</li>
					<?php if (permtrue("fadd") or permtrue("fview") or permtrue("fdelete")) { ?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-file-zip-o"></span><span class="mtext">Dosya Yönetimi</span>
							</a>
							<ul class="submenu">

								<?php if (permtrue("fadd")) { ?><li><a href="index.php?p=new-file">Dosya Yükle</a></li><?php } ?>
								<?php if (permtrue("fview")) { ?><li><a href="index.php?p=all-files">Dosyaları Görüntüle</a></li>
								<?php } ?>
								<?php if (permtrue("fadd") and permtrue("fview") and permtrue("fdelete")) {
								?>
									<li><a href="index.php?p=file-categories">Dosya Kategorileri</a></li>
								<?php
								} ?>

							</ul>
						</li>
					<?php } ?>
					<?php if (permtrue("payview")) { ?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-dollar"></span><span class="mtext">Ödemeler</span>
							</a>
							<ul class="submenu">

								<li><a href="index.php?p=monthly-payments">Aylık Ödemeler</a></li>
								<li><a href="index.php?p=yearly-payments">Yıllık Ödemeler</a></li>
								<li><a href="index.php?p=for-once-payments">Tek Seferlik Ödemeler</a></li>

							</ul>
						</li>
					<?php } ?>


					<?php if (permtrue("pmview")) { ?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-calculator"></span><span class="mtext">Ödeme Kanalları</span>
							</a>
							<ul class="submenu">

								<?php if (permtrue("pmadd")) { ?><li><a href="index.php?p=new-pay-method">Yeni Ödeme Kanalı </a></li><?php } ?>
								<li><a href="index.php?p=pay-methods-list">Ödeme Kanalları</a></li>

							</ul>
						</li>
					<?php } ?>







					<?php if (permtrue("misadd") or permtrue("mistake") or permtrue("allmisview")) { ?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-bookmark-o"></span><span class="mtext">Görev Yönetimi</span>
							</a>
							<ul class="submenu">

								<?php if (permtrue("misadd")) { ?><li><a href="index.php?p=new-mission">Görev Oluştur</a></li><?php } ?>
								<?php if (permtrue("misadd")) { ?><li><a href="index.php?p=mygmissions">Verdiğim Görevler</a></li><?php } ?>

								<?php if (permtrue("mistake")) { ?>
									<li><a href="index.php?p=my-missions">Görevlerim</a></li>
								<?php }
								if (permtrue("allmisview")) { ?>
									<li><a href="index.php?p=all-missions">Sistemdeki Tüm Görevler</a></li>
								<?php } ?>

							</ul>

						</li>
					<?php } ?>


					<?php if (permtrue("exview")) { ?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-calculator"></span><span class="mtext">Gelir/Gider Takibi</span>
							</a>
							<ul class="submenu">

								<?php if (permtrue("exadd")) { ?><li><a href="index.php?p=new-entry">Gelir Ekle</a></li><?php } ?>
								<?php if (permtrue("exadd")) { ?><li><a href="index.php?p=new-expense">Gider Ekle</a></li><?php } ?>
								<?php if (permtrue("excategory")) { ?><li><a href="index.php?p=entry-expense-categories">Kategoriler</a></li><?php } ?>
								<?php if (permtrue("exview")) { ?><li><a href="index.php?p=income-expense-list">Gelir-Gider Listesi</a></li><?php } ?>

							</ul>
						</li>
					<?php } ?>

					<?php if (permtrue("todoadd") or permtrue("todoedit") or permtrue("tododelete")) {
					?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-calendar"></span><span class="mtext">Yapılacaklar</span>
							</a>
							<ul class="submenu">

								<?php if (permtrue("todoadd")) { ?><li><a href="index.php?p=new-task">Yeni Oluştur</a></li><?php } ?>
								<li><a href="index.php?p=todolist">Yapılacaklar Listesi</a></li>

							</ul>
						</li>
					<?php
					} ?>
					<?php if (permtrue("mlogview")) {
					?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-wpexplorer"></span><span class="mtext">Mail & SMS</span>
							</a>
							<ul class="submenu">

								<li><a href="index.php?p=send-mail">Mail Gönder</a></li>
								<?php if (set("sms_active") == "on") { ?>
									<li><a href="index.php?p=send-sms">SMS Gönder</a></li>
								<?php } ?>
							</ul>
						</li>
					<?php
					}
					?>
					<?php if (permtrue("noteadd") or permtrue("noteedit")) {
					?>
						<li class="dropdown">
							<a href="javascript:;" class="dropdown-toggle">
								<span class="fa fa-sticky-note-o"></span><span class="mtext">Notlar</span>
							</a>
							<ul class="submenu">

								<?php if (permtrue("noteadd")) { ?><li><a href="index.php?p=new-note">Yeni Not</a></li><?php } ?>
								<li><a href="index.php?p=all-notes">Tümünü Görüntüle</a></li>
								<?php if (sesset("permission") == 1 or permtrue("noteedit")) { ?>
									<li><a href="index.php?p=note-categories">Not Kategorileri</a></li>
								<?php } ?>
							</ul>
						</li>
					<?php
					} ?>

					<li class="dropdown">
						<a href="javascript:;" class="dropdown-toggle">
							<span class="fa fa-clone"></span><span class="mtext">Ekip</span>
						</a>
						<ul class="submenu">

							<?php if (permtrue("uadd")) { ?>
								<li><a href="index.php?p=new-user">Yeni Üye Oluştur</a></li>
							<?php } ?>
							<li><a href="index.php?p=all-users">Ekip Üyeleri</a></li>
							<?php if (permtrue("uperm")) { ?>
								<li><a href="index.php?p=permission-settings">Pozisyon Ayarları</a></li><?php } ?>

						</ul>
					</li>






					<?php if (permtrue("setview")) {
					?>
						<li>
							<a href="index.php?p=settings" class="dropdown-toggle no-arrow">
								<span class="fa fa-sitemap"></span><span class="mtext">Panel Ayarları</span>
							</a>
						</li>
					<?php
					} ?>

				</ul>
			</div>
		</div>
	</div>