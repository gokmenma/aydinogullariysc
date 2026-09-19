<div class="left-side-bar">
    <div class="brand-logo">
        <a href="index.php">
            <img style="margin: 0px" width="250" src="<?php echo set("logo"); ?>"
                alt="<?php echo set("site_title"); ?> Logo">
        </a>
    </div>
    <div class="menu-block customscroll">
        <div class="sidebar-menu">
            <div class="sidebar-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="text" class="sidebar-search-input" placeholder="Menude ara..." aria-label="Menude ara">
            </div>
            <ul id="accordion-menu">
                <li class="dropdown">
                    <a href="index.php?p=home" class="dropdown-toggle no-arrow">
                        <span class="fa fa-home"></span><span class="mtext">Ana Sayfa</span>
                    </a>

                </li>



                <?php if (permtrue("offerview")) {
                    ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="fa fa-file-o"></span><span class="mtext">Teklifler</span>
                        </a>
                        <ul class="submenu">

                            <?php if (permtrue("offeradd")) { ?>
                                <li><a href="index.php?p=offers/offer-manage">Yeni Teklif Oluştur</a></li>
                            <?php } ?>


                            <li><a href="index.php?p=offers/list&sablon=true">Şablon Teklifler</a></li>


                            <li><a href="index.php?p=offers/list">Teklifleri Görüntüle</a></li>
                            <li><a href="index.php?p=offers/items-list">Teklif Kalemleri Listesi</a></li>

                        </ul>
                    </li>
                    <?php
                } ?>


                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="fa fa-table"></span><span class="mtext">Servis Yönetimi</span>
                    </a>
                    <ul class="submenu">

                        <?php if (permtrue("serviceAdd")) { ?>
                            <li><a href="index.php?p=service/manage">Servis Oluştur</a></li>
                        <?php }
                        if (permtrue("serviceView")) { ?>
                            <li><a href="index.php?p=service/list">Servisleri Görüntüle</a></li>
                        <?php } ?>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="fa fa-street-view"></span><span class="mtext">Keşifler</span>
                    </a>
                    <ul class="submenu">

                        <?php
                        if (permtrue("kesifView")) { ?>
                            <li><a href="index.php?p=kesif/list">Keşifleri Görüntüle</a></li>
                        <?php } ?>
                    </ul>
                </li>


                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="fa fa-shopping-cart"></span><span class="mtext">Satın Alma</span>
                    </a>
                    <ul class="submenu">
                        <?php if (permtrue("purchase-demand-add")) { ?>
                            <li><a href="index.php?p=purchase-demand-new">Satın Alma Talebi Oluştur</a></li>
                        <?php }
                        if (permtrue("purchaseadd")) { ?>
                            <li><a href="index.php?p=purchases/manage">Yeni Sipariş</a></li>
                        <?php } ?>
                        <li><a href="index.php?p=purchases/price-request-list">Fiyat Talepleri</a></li>
                        <li><a href="index.php?p=purchases">Tümünü Görüntüle</a></li>

                    </ul>
                </li>



                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="fa fa-user-plus"></span><span class="mtext">Firma Yönetimi</span>
                    </a>
                    <ul class="submenu">

                        <?php if (permtrue("customeradd")) { ?>
                            <li><a href="index.php?p=customers/manage">Yeni Firma</a></li>
                        <?php } ?>
                        <li><a href="index.php?p=customers/list">Firma Listesi</a></li>


                    </ul>
                </li>
                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="fa fa-paint-brush"></span><span class="mtext">Ürün/Hizmetler</span>
                    </a>
                    <ul class="submenu">

                        <?php if (permtrue("productadd")) { ?>
                            <li><a href="index.php?p=products/manage">Yeni Ürün/Hizmet</a></li>
                        <?php } ?>
                        <li><a href="index.php?p=products/list">Ürün&Hizmet Listesi</a></li>

                    </ul>

                </li>
                <?php if (permtrue("stock-activity")) { ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="fa fa-list-ol"></span><span class="mtext">Stok Yönetimi</span>
                        </a>
                        <ul class="submenu">

                            <?php if (permtrue("stock-activity-manage")) { ?>
                                <li><a href="index.php?p=stock-activity/manage">Stok Hareketi Ekle</a></li>
                            <?php } ?>
                            <li><a href="index.php?p=stock-activity/list">Stok Hareketleri</a></li>
                            <?php if (permtrue("stock-activity-manage")) { ?>
                                <li><a href="index.php?p=stock-activity/order-list">Sipariş Listesi</a></li>
                            <?php } ?>

                        </ul>

                    </li>
                <?php } ?>

                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="fa fa-bar-chart"></span><span class="mtext">Raporlar</span>
                    </a>
                    <ul class="submenu">

                        <?php if (permtrue("reportview")) { ?>
                            <li><a href="index.php?p=reports/reports">Rapor Listesi</a></li>
                            <li><a href="index.php?p=reports/filling-list">Dolum Listesi</a></li>
                            <li><a href="index.php?p=reports/control-list">Kontrol Listesi</a></li>
                        <?php } ?>


                    </ul>

                </li>
                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle">

                        <span class="fa fa-folder-open"></span><span class="mtext">Evrak Takip</span>
                    </a>
                    <ul class="submenu">
                        <?php if (permtrue("indocadd")) { ?>
                            <li><a href="index.php?p=new-indocument">Evrak Ekle</a></li>
                        <?php } ?>
                        <?php if (permtrue("outdocview")) { ?>
                            <li><a href="index.php?p=view-outdocument">Giden Evrak Listesi</a></li>
                        <?php } ?>
                        <?php if (permtrue("indocview")) { ?>
                            <li><a href="index.php?p=view-indocument">Gelen Evrak Listesi</a></li>
                        <?php } ?>
                        <?php if (permtrue("indoccategories")) { ?>
                            <li><a href="index.php?p=indocument-categories">Kategoriler</a></li>
                        <?php } ?>

                    </ul>
                </li>
                <?php if (permtrue("fileadd") or permtrue("fileview") or permtrue("filedelete")) { ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="fa fa-file-zip-o"></span><span class="mtext">Dosya Yönetimi</span>
                        </a>
                        <ul class="submenu">

                            <?php if (permtrue("fileadd")) { ?>
                                <li><a href="index.php?p=new-file">Dosya Yükle</a></li>
                            <?php } ?>
                            <?php if (permtrue("fileview")) { ?>
                                <li><a href="index.php?p=all-files">Dosyaları Görüntüle</a></li>
                            <?php } ?>
                            <?php if (permtrue("fileadd") and permtrue("fileview") and permtrue("filedelete")) {
                                ?>
                                <li><a href="index.php?p=file-categories">Dosya Kategorileri</a></li>
                                <?php
                            } ?>

                        </ul>
                    </li>
                <?php } ?>

                <?php if (permtrue("missionadd") or permtrue("missiontake") or permtrue("allmisview")) { ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="fa fa-bookmark-o"></span><span class="mtext">Görev Yönetimi</span>
                        </a>
                        <ul class="submenu">

                            <?php if (permtrue("missionadd")) { ?>
                                <li><a href="index.php?p=new-mission">Görev Oluştur</a></li>
                            <?php } ?>
                            <?php if (permtrue("missionadd")) { ?>
                                <li><a href="index.php?p=mygmissions">Verdiğim Görevler</a></li>
                            <?php } ?>

                            <?php if (permtrue("missiontake")) { ?>
                                <li><a href="index.php?p=my-missions">Görevlerim</a></li>
                            <?php }
                            if (permtrue("allmisview")) { ?>
                                <li><a href="index.php?p=all-missions">Sistemdeki Tüm Görevler</a></li>
                            <?php } ?>

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

                            <?php if (permtrue("todoadd")) { ?>
                                <li><a href="index.php?p=task-new">Yeni Oluştur</a></li>
                            <?php } ?>
                            <li><a href="index.php?p=tasks">Yapılacaklar Listesi</a></li>

                        </ul>
                    </li>
                    <?php
                } ?>
                <?php if (permtrue("mailandsmssend")) {
                    ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="fa fa-envelope"></span><span class="mtext">Mail & SMS</span>
                        </a>
                        <ul class="submenu">

                            <li><a href="index.php?p=send-mail">Mail Gönder</a></li>
                            <?php if (set("sms_active") == "on") { ?>
                                <li><a href="index.php?p=send-sms">SMS Gönder</a></li>
                                <li><a href="index.php?p=mail-logs">Mail Kayıtları</a></li>
                            <?php }
                            if (sesset("id") == 12 || sesset("id") == 1) { ?>
                                <li><a href="index.php?p=send-mail-accounts">Mail Hesapları</a></li>
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

                            <?php if (permtrue("noteadd")) { ?>
                                <li><a href="index.php?p=new-note">Yeni Not</a></li>
                            <?php } ?>
                            <li><a href="index.php?p=all-notes">Tümünü Görüntüle</a></li>
                            <?php if (sesset("permission") == 1 or permtrue("noteedit")) { ?>
                                <li><a href="index.php?p=note-categories">Not Kategorileri</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php
                } ?>

                <?php if (permtrue("support-request-view") or permtrue("support-request-add")) { ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="fa fa-support"></span><span class="mtext">Destek Sistemi</span>
                        </a>
                        <ul class="submenu">
                            <?php if (permtrue("support-request-add")) { ?>
                                <li><a href="index.php?p=support-new">Yeni Destek Talebi</a></li>
                            <?php } ?>
                            <?php if (permtrue("support-request-view")) { ?>
                                <li><a href="index.php?p=support-list">Destek Talepleri</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>

                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="fa fa-user"></span><span class="mtext">Ekip</span>
                    </a>
                    <ul class="submenu">

                        <?php if (permtrue("useradd")) { ?>
                            <li><a href="index.php?p=user-new">Yeni Üye Oluştur</a></li>
                        <?php } ?>
                        <li><a href="index.php?p=users">Ekip Üyeleri</a></li>
                        <?php if (permtrue("authdefine")) { ?>
                            <li><a href="index.php?p=permission-settings">Pozisyon Ayarları</a></li>
                        <?php } ?>

                    </ul>
                </li>
                <li class="dropdown">
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="fa fa-gears"></span><span class="mtext">Tanımlamalar</span>
                    </a>
                    <ul class="submenu">
                        <?php if (sesset("permission") == 1) { ?>
                            <li><a href="index.php?p=service-type">Servis Konusu Tanımlama</a></li>
                            <li><a href="index.php?p=service-status">Servis Durumu Tanımlama</a></li>
                            <li><a href="index.php?p=service-region">Servis Bölgesi Tanımlama</a></li>
                            <li><a href="index.php?p=paytype">Tahsilat Türü Tanımlama</a></li>
                            <li><a href="index.php?p=offer-templates">Teklif Üst/Alt Bilgi Tanımlama</a></li>
                            <li><a href="index.php?p=define-units">Birim Tanımlama</a></li>
                        <?php } ?>


                    </ul>
                </li>

                <?php if (permtrue("panelsettings")) { ?>
                    <li class="dropdown">
                        <a href="index.php?p=settings" class="dropdown-toggle no-arrow">
                            <span class="fa fa-sitemap"></span><span class="mtext">Panel Ayarları</span>
                        </a>
                    </li>
                <?php } ?>
                <?php if (in_array(sesset("id"), [1, 12])) { ?>
                    <li class="dropdown">
                        <a href="index.php?p=logs/index" class="dropdown-toggle no-arrow">
                            <span class="fa fa-history"></span><span class="mtext">Sistem Aktiviteleri</span>
                        </a>
                    </li>
                <?php } ?>
                <?php if (permtrue("backupmanage") || sesset("id") == 1) { ?>
                    <li class="dropdown">
                        <a href="index.php?p=backups" class="dropdown-toggle no-arrow">
                            <span class="fa fa-database"></span><span class="mtext">Yedekleme & Kurtarma</span>
                        </a>
                    </li>
                <?php } ?>
                <li class="dropdown">
                    <a href="index.php?p=version-notes" class="dropdown-toggle no-arrow">
                        <span class="fa fa-file-text"></span><span class="mtext">Sürüm Notları</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</div>