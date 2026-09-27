<?php

return [
    'core' => [
        '' => ['page' => 'home', 'permissions' => []],
        'anasayfa' => ['page' => 'home', 'permissions' => []],
        'profil' => ['page' => 'profile', 'permissions' => []],
        'surum-notlari' => ['page' => 'version-notes', 'permissions' => []],
    ],
    'offers' => [
        'teklif-paneli' => ['page' => 'offers/dashboard', 'permissions' => ['offer_dashboard', 'offerview']],
        'yeni-teklif' => ['page' => 'offers/offer-manage', 'permissions' => ['offeradd']],
        'teklifler' => ['page' => 'offers/list', 'permissions' => ['offerview']],
        'teklif-sablonlari' => ['page' => 'offers/list', 'permissions' => ['offerview'], 'query' => ['sablon' => 'true']],
        'teklif-kalemleri' => ['page' => 'offers/items-list', 'permissions' => ['offerview']],
        'teklif-duzenle' => ['page' => 'offers/offer-manage', 'permissions' => ['offeredit'], 'encrypted_id' => true],
    ],
    'purchases' => [
        'satin-alma-paneli' => ['page' => 'purchases/dashboard', 'permissions' => ['purchase_dashboard', 'purchaseadd']],
        'satin-almalar' => ['page' => 'purchases', 'permissions' => []],
        'yeni-siparis' => ['page' => 'purchases/manage', 'permissions' => ['purchaseadd']],
        'siparis-duzenle' => ['page' => 'purchases/manage', 'permissions' => ['purchaseedit'], 'encrypted_id' => true],
        'yeni-satin-alma-talebi' => ['page' => 'purchase-demand-new', 'permissions' => ['purchase-demand-add']],
        'fiyat-talepleri' => ['page' => 'purchases/price-request-list', 'permissions' => []],
        'yeni-fiyat-talebi' => ['page' => 'purchases/price-request-manage', 'permissions' => ['purchaseadd']],
        'fiyat-talebi-duzenle' => ['page' => 'purchases/price-request-manage', 'permissions' => ['purchaseedit'], 'encrypted_id' => true],
    ],
    'service' => [
        'servis-paneli' => ['page' => 'service/dashboard', 'permissions' => ['service_dashboard', 'serviceView']],
        'servisler' => ['page' => 'service/list', 'permissions' => ['serviceView']],
        'yeni-servis' => ['page' => 'service/manage', 'permissions' => ['serviceAdd']],
        'servis-duzenle' => ['page' => 'service/manage', 'permissions' => ['serviceEdit'], 'encrypted_id' => true],
    ],
    'discovery' => [
        'kesif-paneli' => ['page' => 'kesif/dashboard', 'permissions' => ['kesif_dashboard', 'kesifView']],
        'kesifler' => ['page' => 'kesif/list', 'permissions' => ['kesifView']],
    ],
    'customers' => [
        'firma-paneli' => ['page' => 'customers/dashboard', 'permissions' => ['customer_dashboard', 'customerview']],
        'firmalar' => ['page' => 'customers/list', 'permissions' => []],
        'yeni-firma' => ['page' => 'customers/manage', 'permissions' => ['customeradd']],
        'firma-duzenle' => ['page' => 'customers/manage', 'permissions' => ['customeredit'], 'encrypted_id' => true],
    ],
    'products' => [
        'urun-paneli' => ['page' => 'products/dashboard', 'permissions' => ['product_dashboard', 'productcategory', 'productadd']],
        'urun-hizmetler' => ['page' => 'products/list', 'permissions' => ['product_dashboard', 'productcategory', 'productadd', 'productedit', 'productdelete']],
        'yeni-urun-hizmet' => ['page' => 'products/manage', 'permissions' => ['productadd']],
        'urun-hizmet-duzenle' => ['page' => 'products/manage', 'permissions' => ['productedit'], 'encrypted_id' => true],
    ],
    'stock' => [
        'stok-hareketi-ekle' => ['page' => 'stock-activity/manage', 'permissions' => ['stock-activity-manage']],
        'stok-hareketleri' => ['page' => 'stock-activity/list', 'permissions' => ['stock-activity']],
        'stok-siparisleri' => ['page' => 'stock-activity/order-list', 'permissions' => ['stock-activity-manage']],
    ],
    'reports' => [
        'rapor-paneli' => ['page' => 'reports/dashboard', 'permissions' => ['report_dashboard', 'reportview']],
        'raporlar' => ['page' => 'reports/reports', 'permissions' => ['reportview']],
        'dolum-listesi' => ['page' => 'reports/filling-list', 'permissions' => ['reportview']],
        'kontrol-listesi' => ['page' => 'reports/control-list', 'permissions' => ['reportview']],
    ],
    'documents' => [
        'evrak-ekle' => ['page' => 'new-indocument', 'permissions' => ['indocadd']],
        'giden-evraklar' => ['page' => 'view-outdocument', 'permissions' => ['outdocview']],
        'gelen-evraklar' => ['page' => 'view-indocument', 'permissions' => ['indocview']],
        'evrak-kategorileri' => ['page' => 'indocument-categories', 'permissions' => ['indoccategories']],
    ],
    'files' => [
        'dosyalar' => ['page' => 'all-files', 'permissions' => ['fileview', 'fileadd']],
        'dosya-kategorileri' => ['page' => 'file-categories', 'permissions' => ['fileview', 'fileadd', 'filedelete']],
    ],
    'missions' => [
        'gorev-olustur' => ['page' => 'new-mission', 'permissions' => ['missionadd']],
        'verdigim-gorevler' => ['page' => 'mygmissions', 'permissions' => ['missionadd']],
        'gorevlerim' => ['page' => 'my-missions', 'permissions' => ['missiontake']],
        'tum-gorevler' => ['page' => 'all-missions', 'permissions' => ['allmisview']],
    ],
    'tasks' => [
        'yapilacak-ekle' => ['page' => 'task-new', 'permissions' => ['todoadd']],
        'yapilacaklar' => ['page' => 'tasks', 'permissions' => ['todoadd', 'todoedit', 'tododelete']],
    ],
    'communications' => [
        'mail-sms' => ['page' => 'send-mail', 'permissions' => ['mailandsmssend']],
        'sms-gonder' => ['page' => 'send-sms', 'permissions' => ['mailandsmssend']],
        'mail-kayitlari' => ['page' => 'mail-logs', 'permissions' => ['mail-logs-view', 'mailandsmssend']],
        'mail-hesaplari' => ['page' => 'send-mail-accounts', 'permissions' => ['mail-accounts-manage']],
    ],
    'notes' => [
        'not-ekle' => ['page' => 'new-note', 'permissions' => ['noteadd']],
        'notlar' => ['page' => 'all-notes', 'permissions' => ['noteadd', 'noteedit']],
        'not-kategorileri' => ['page' => 'note-categories', 'permissions' => ['noteedit']],
    ],
    'support' => [
        'destek-talebi-olustur' => ['page' => 'support-new', 'permissions' => ['support-request-add']],
        'destek-talepleri' => ['page' => 'support-list', 'permissions' => ['support-request-view']],
    ],
    'team' => [
        'yeni-ekip-uyesi' => ['page' => 'user-new', 'permissions' => ['useradd']],
        'ekip-uyeleri' => ['page' => 'users', 'permissions' => []],
        'pozisyon-ayarlari' => ['page' => 'permission-settings', 'permissions' => ['authdefine']],
    ],
    'definitions' => [
        'servis-konulari' => ['page' => 'service-type', 'permissions' => ['panelsettings', 'authdefine', 'serviceAdd', 'serviceView']],
        'servis-durumlari' => ['page' => 'service-status', 'permissions' => ['panelsettings', 'authdefine', 'serviceAdd', 'serviceView']],
        'servis-bolgeleri' => ['page' => 'service-region', 'permissions' => ['panelsettings', 'authdefine', 'serviceAdd', 'serviceView']],
        'tahsilat-turleri' => ['page' => 'paytype', 'permissions' => ['panelsettings', 'authdefine', 'offerview', 'serviceView']],
        'teklif-sablon-tanimlari' => ['page' => 'offer-templates', 'permissions' => ['offertemplateview', 'offertemplateadd', 'panelsettings', 'authdefine']],
        'birimler' => ['page' => 'define-units', 'permissions' => ['productcategory', 'productadd', 'panelsettings', 'authdefine']],
    ],
    'administration' => [
        'panel-ayarlari' => ['page' => 'settings', 'permissions' => ['panelsettings']],
        'sistem-aktiviteleri' => ['page' => 'logs/index', 'permissions' => ['panelsettings']],
        'yedekleme' => ['page' => 'backups', 'permissions' => ['backupmanage']],
    ],
];
