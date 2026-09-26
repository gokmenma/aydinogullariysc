<?php
use App\Model\MenuOrderModel;

$userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
$userPerm = (int)(function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? 0));
$isAdmin = in_array($userId, [1, 12]) || in_array($userPerm, [1, 13]);

// Menü tanımları
$menuDefinitions = [
    'home' => [
        'title' => 'Ana Sayfa',
        'section' => 'Ana Sayfa',
        'icon' => 'fa fa-home',
        'link' => 'index.php?p=home',
        'visible' => true,
        'items' => []
    ],
    'offers' => [
        'title' => 'Teklifler',
        'section' => 'Operasyon & Süreç',
        'icon' => 'fa fa-file-text-o',
        'visible' => permtrue("offerview"),
        'items' => [
            'offers/dashboard' => [
                'title' => 'Teklif Dashboard',
                'link' => 'index.php?p=offers/dashboard',
                'visible' => (permtrue("offer_dashboard") || permtrue("offerview"))
            ],
            'offers/offer-manage' => [
                'title' => 'Yeni Teklif Oluştur',
                'link' => 'index.php?p=offers/offer-manage',
                'visible' => permtrue("offeradd")
            ],
            'offers/list_sablon' => [
                'title' => 'Şablon Teklifler',
                'link' => 'index.php?p=offers/list&sablon=true',
                'visible' => true
            ],
            'offers/list' => [
                'title' => 'Teklifleri Görüntüle',
                'link' => 'index.php?p=offers/list',
                'visible' => true
            ],
            'offers/items-list' => [
                'title' => 'Teklif Kalemleri Listesi',
                'link' => 'index.php?p=offers/items-list',
                'visible' => true
            ]
        ]
    ],
    'service' => [
        'title' => 'Servis Yönetimi',
        'section' => 'Operasyon & Süreç',
        'icon' => 'fa fa-wrench',
        'visible' => true,
        'items' => [
            'service/dashboard' => [
                'title' => 'Servis Dashboard',
                'link' => 'index.php?p=service/dashboard',
                'visible' => (permtrue("service_dashboard") || permtrue("serviceView"))
            ],
            'service/manage' => [
                'title' => 'Servis Oluştur',
                'link' => 'index.php?p=service/manage',
                'visible' => permtrue("serviceAdd")
            ],
            'service/list' => [
                'title' => 'Servisleri Görüntüle',
                'link' => 'index.php?p=service/list',
                'visible' => permtrue("serviceView")
            ]
        ]
    ],
    'kesif' => [
        'title' => 'Keşifler',
        'section' => 'Operasyon & Süreç',
        'icon' => 'fa fa-map-marker',
        'visible' => true,
        'items' => [
            'kesif/dashboard' => [
                'title' => 'Keşif Dashboard',
                'link' => 'index.php?p=kesif/dashboard',
                'visible' => (permtrue("kesif_dashboard") || permtrue("kesifView"))
            ],
            'kesif/list' => [
                'title' => 'Keşifleri Görüntüle',
                'link' => 'index.php?p=kesif/list',
                'visible' => permtrue("kesifView")
            ]
        ]
    ],
    'purchases' => [
        'title' => 'Satın Alma',
        'section' => 'Operasyon & Süreç',
        'icon' => 'fa fa-shopping-cart',
        'visible' => true,
        'items' => [
            'purchases/dashboard' => [
                'title' => 'Satın Alma Dashboard',
                'link' => 'index.php?p=purchases/dashboard',
                'visible' => (permtrue("purchase_dashboard") || permtrue("purchaseadd") || permtrue("purchases"))
            ],
            'purchase-demand-new' => [
                'title' => 'Satın Alma Talebi Oluştur',
                'link' => 'index.php?p=purchase-demand-new',
                'visible' => permtrue("purchase-demand-add")
            ],
            'purchases/manage' => [
                'title' => 'Yeni Sipariş',
                'link' => 'index.php?p=purchases/manage',
                'visible' => permtrue("purchaseadd")
            ],
            'purchases/price-request-list' => [
                'title' => 'Fiyat Talepleri',
                'link' => 'index.php?p=purchases/price-request-list',
                'visible' => true
            ],
            'purchases' => [
                'title' => 'Tümünü Görüntüle',
                'link' => 'index.php?p=purchases',
                'visible' => true
            ]
        ]
    ],
    'customers' => [
        'title' => 'Firma Yönetimi',
        'section' => 'Operasyon & Süreç',
        'icon' => 'fa fa-building-o',
        'visible' => true,
        'items' => [
            'customers/dashboard' => [
                'title' => 'Firma Dashboard',
                'link' => 'index.php?p=customers/dashboard',
                'visible' => (permtrue("customer_dashboard") || permtrue("customerview"))
            ],
            'customers/manage' => [
                'title' => 'Yeni Firma',
                'link' => 'index.php?p=customers/manage',
                'visible' => permtrue("customeradd")
            ],
            'customers/list' => [
                'title' => 'Firma Listesi',
                'link' => 'index.php?p=customers/list',
                'visible' => true
            ]
        ]
    ],
    'products' => [
        'title' => 'Ürün/Hizmetler',
        'section' => 'Operasyon & Süreç',
        'icon' => 'fa fa-cubes',
        'visible' => true,
        'items' => [
            'products/dashboard' => [
                'title' => 'Ürün Dashboard',
                'link' => 'index.php?p=products/dashboard',
                'visible' => (permtrue("product_dashboard") || permtrue("productcategory") || permtrue("productadd"))
            ],
            'products/manage' => [
                'title' => 'Yeni Ürün/Hizmet',
                'link' => 'index.php?p=products/manage',
                'visible' => permtrue("productadd")
            ],
            'products/list' => [
                'title' => 'Ürün&Hizmet Listesi',
                'link' => 'index.php?p=products/list',
                'visible' => (permtrue("product_dashboard") || permtrue("productcategory") || permtrue("productadd") || permtrue("productedit") || permtrue("productdelete"))
            ]
        ]
    ],
    'stock-activity' => [
        'title' => 'Stok Yönetimi',
        'section' => 'Operasyon & Süreç',
        'icon' => 'fa fa-archive',
        'visible' => permtrue("stock-activity"),
        'items' => [
            'stock-activity/manage' => [
                'title' => 'Stok Hareketi Ekle',
                'link' => 'index.php?p=stock-activity/manage',
                'visible' => permtrue("stock-activity-manage")
            ],
            'stock-activity/list' => [
                'title' => 'Stok Hareketleri',
                'link' => 'index.php?p=stock-activity/list',
                'visible' => true
            ],
            'stock-activity/order-list' => [
                'title' => 'Sipariş Listesi',
                'link' => 'index.php?p=stock-activity/order-list',
                'visible' => permtrue("stock-activity-manage")
            ]
        ]
    ],
    'reports' => [
        'title' => 'Raporlar',
        'section' => 'Raporlar & Analiz',
        'icon' => 'fa fa-pie-chart',
        'visible' => true,
        'items' => [
            'reports/dashboard' => [
                'title' => 'Rapor Dashboard',
                'link' => 'index.php?p=reports/dashboard',
                'visible' => (permtrue("report_dashboard") || permtrue("reportview"))
            ],
            'reports/reports' => [
                'title' => 'Rapor Listesi',
                'link' => 'index.php?p=reports/reports',
                'visible' => permtrue("reportview")
            ],
            'reports/filling-list' => [
                'title' => 'Dolum Listesi',
                'link' => 'index.php?p=reports/filling-list',
                'visible' => permtrue("reportview")
            ],
            'reports/control-list' => [
                'title' => 'Kontrol Listesi',
                'link' => 'index.php?p=reports/control-list',
                'visible' => permtrue("reportview")
            ]
        ]
    ],
    'indocument' => [
        'title' => 'Evrak Takip',
        'section' => 'Evrak & İş Takip',
        'icon' => 'fa fa-folder-open-o',
        'visible' => true,
        'items' => [
            'new-indocument' => [
                'title' => 'Evrak Ekle',
                'link' => 'index.php?p=new-indocument',
                'visible' => permtrue("indocadd")
            ],
            'view-outdocument' => [
                'title' => 'Giden Evrak Listesi',
                'link' => 'index.php?p=view-outdocument',
                'visible' => permtrue("outdocview")
            ],
            'view-indocument' => [
                'title' => 'Gelen Evrak Listesi',
                'link' => 'index.php?p=view-indocument',
                'visible' => permtrue("indocview")
            ],
            'indocument-categories' => [
                'title' => 'Kategoriler',
                'link' => 'index.php?p=indocument-categories',
                'visible' => permtrue("indoccategories")
            ]
        ]
    ],
    'files' => [
        'title' => 'Dosya Yönetimi',
        'section' => 'Evrak & İş Takip',
        'icon' => 'fa fa-file-archive-o',
        'visible' => (permtrue("fileadd") || permtrue("fileview") || permtrue("filedelete")),
        'items' => [
            'all-files' => [
                'title' => 'Tüm Dosyalar',
                'link' => 'index.php?p=all-files',
                'visible' => (permtrue("fileview") || permtrue("fileadd"))
            ],
            'file-categories' => [
                'title' => 'Dosya Kategorileri',
                'link' => 'index.php?p=file-categories',
                'visible' => (permtrue("fileview") || permtrue("fileadd") || permtrue("filedelete"))
            ]
        ]
    ],
    'missions' => [
        'title' => 'Görev Yönetimi',
        'section' => 'Evrak & İş Takip',
        'icon' => 'fa fa-check-square-o',
        'visible' => (permtrue("missionadd") || permtrue("missiontake") || permtrue("allmisview")),
        'items' => [
            'new-mission' => [
                'title' => 'Görev Oluştur',
                'link' => 'index.php?p=new-mission',
                'visible' => permtrue("missionadd")
            ],
            'mygmissions' => [
                'title' => 'Verdiğim Görevler',
                'link' => 'index.php?p=mygmissions',
                'visible' => permtrue("missionadd")
            ],
            'my-missions' => [
                'title' => 'Görevlerim',
                'link' => 'index.php?p=my-missions',
                'visible' => permtrue("missiontake")
            ],
            'all-missions' => [
                'title' => 'Sistemdeki Tüm Görevler',
                'link' => 'index.php?p=all-missions',
                'visible' => permtrue("allmisview")
            ]
        ]
    ],
    'tasks' => [
        'title' => 'Yapılacaklar',
        'section' => 'Evrak & İş Takip',
        'icon' => 'fa fa-calendar-check-o',
        'visible' => (permtrue("todoadd") || permtrue("todoedit") || permtrue("tododelete")),
        'items' => [
            'task-new' => [
                'title' => 'Yeni Oluştur',
                'link' => 'index.php?p=task-new',
                'visible' => permtrue("todoadd")
            ],
            'tasks' => [
                'title' => 'Yapılacaklar Listesi',
                'link' => 'index.php?p=tasks',
                'visible' => true
            ]
        ]
    ],
    'mail-sms' => [
        'title' => 'Mail & SMS',
        'section' => 'Evrak & İş Takip',
        'icon' => 'fa fa-paper-plane-o',
        'visible' => (permtrue("mailandsmssend") || permtrue("mail-logs-view") || $isAdmin),
        'items' => [
            'send-mail' => [
                'title' => 'Mail Gönder',
                'link' => 'index.php?p=send-mail',
                'visible' => (permtrue("mailandsmssend") || $isAdmin)
            ],
            'send-sms' => [
                'title' => 'SMS Gönder',
                'link' => 'index.php?p=send-sms',
                'visible' => (permtrue("mailandsmssend") || $isAdmin)
            ],
            'mail-logs' => [
                'title' => 'Mail Kayıtları',
                'link' => 'index.php?p=mail-logs',
                'visible' => (permtrue("mail-logs-view") || permtrue("mailandsmssend") || $isAdmin)
            ],
            'send-mail-accounts' => [
                'title' => 'Mail Hesapları',
                'link' => 'index.php?p=send-mail-accounts',
                'visible' => (permtrue("mail-accounts-manage") || $isAdmin || in_array($userId, [1, 12]))
            ]
        ]
    ],
    'notes' => [
        'title' => 'Notlar',
        'section' => 'Evrak & İş Takip',
        'icon' => 'fa fa-sticky-note-o',
        'visible' => (permtrue("noteadd") || permtrue("noteedit")),
        'items' => [
            'new-note' => [
                'title' => 'Yeni Not',
                'link' => 'index.php?p=new-note',
                'visible' => permtrue("noteadd")
            ],
            'all-notes' => [
                'title' => 'Tümünü Görüntüle',
                'link' => 'index.php?p=all-notes',
                'visible' => true
            ],
            'note-categories' => [
                'title' => 'Not Kategorileri',
                'link' => 'index.php?p=note-categories',
                'visible' => ($isAdmin || permtrue("noteedit"))
            ]
        ]
    ],
    'support' => [
        'title' => 'Destek Sistemi',
        'section' => 'Sistem & Yönetim',
        'icon' => 'fa fa-life-ring',
        'visible' => (permtrue("support-request-view") || permtrue("support-request-add")),
        'items' => [
            'support-new' => [
                'title' => 'Yeni Destek Talebi',
                'link' => 'index.php?p=support-new',
                'visible' => permtrue("support-request-add")
            ],
            'support-list' => [
                'title' => 'Destek Talepleri',
                'link' => 'index.php?p=support-list',
                'visible' => permtrue("support-request-view")
            ]
        ]
    ],
    'team' => [
        'title' => 'Ekip',
        'section' => 'Sistem & Yönetim',
        'icon' => 'fa fa-users',
        'visible' => true,
        'items' => [
            'user-new' => [
                'title' => 'Yeni Üye Oluştur',
                'link' => 'index.php?p=user-new',
                'visible' => permtrue("useradd")
            ],
            'users' => [
                'title' => 'Ekip Üyeleri',
                'link' => 'index.php?p=users',
                'visible' => true
            ],
            'permission-settings' => [
                'title' => 'Pozisyon Ayarları',
                'link' => 'index.php?p=permission-settings',
                'visible' => (permtrue("authdefine") || $isAdmin)
            ]
        ]
    ],
    'definitions' => [
        'title' => 'Tanımlamalar',
        'section' => 'Sistem & Yönetim',
        'icon' => 'fa fa-sliders',
        'visible' => true,
        'items' => [
            'service-type' => [
                'title' => 'Servis Konusu Tanımlama',
                'link' => 'index.php?p=service-type',
                'visible' => ($isAdmin || permtrue("panelsettings") || permtrue("authdefine") || permtrue("serviceAdd") || permtrue("serviceView"))
            ],
            'service-status' => [
                'title' => 'Servis Durumu Tanımlama',
                'link' => 'index.php?p=service-status',
                'visible' => ($isAdmin || permtrue("panelsettings") || permtrue("authdefine") || permtrue("serviceAdd") || permtrue("serviceView"))
            ],
            'service-region' => [
                'title' => 'Servis Bölgesi Tanımlama',
                'link' => 'index.php?p=service-region',
                'visible' => ($isAdmin || permtrue("panelsettings") || permtrue("authdefine") || permtrue("serviceAdd") || permtrue("serviceView"))
            ],
            'paytype' => [
                'title' => 'Tahsilat Türü Tanımlama',
                'link' => 'index.php?p=paytype',
                'visible' => ($isAdmin || permtrue("panelsettings") || permtrue("authdefine") || permtrue("offerview") || permtrue("serviceView"))
            ],
            'offer-templates' => [
                'title' => 'Teklif Üst/Alt Bilgi Tanımlama',
                'link' => 'index.php?p=offer-templates',
                'visible' => ($isAdmin || permtrue("offertemplateview") || permtrue("offertemplateadd") || permtrue("panelsettings") || permtrue("authdefine"))
            ],
            'define-units' => [
                'title' => 'Birim Tanımlama',
                'link' => 'index.php?p=define-units',
                'visible' => ($isAdmin || permtrue("productcategory") || permtrue("productadd") || permtrue("panelsettings") || permtrue("authdefine"))
            ]
        ]
    ],
    'panel-settings' => [
        'title' => 'Panel Ayarları',
        'section' => 'Sistem & Yönetim',
        'icon' => 'fa fa-cog',
        'link' => 'index.php?p=settings',
        'visible' => (permtrue("panelsettings") || $isAdmin),
        'items' => []
    ],
    'logs' => [
        'title' => 'Sistem Aktiviteleri',
        'section' => 'Sistem & Yönetim',
        'icon' => 'fa fa-history',
        'link' => 'index.php?p=logs/index',
        'visible' => ($isAdmin || in_array($userId, [1, 12])),
        'items' => []
    ],
    'backups' => [
        'title' => 'Yedekleme & Kurtarma',
        'section' => 'Sistem & Yönetim',
        'icon' => 'fa fa-database',
        'link' => 'index.php?p=backups',
        'visible' => (permtrue("backupmanage") || $isAdmin),
        'items' => []
    ],
    'version-notes' => [
        'title' => 'Sürüm Notları',
        'section' => 'Sistem & Yönetim',
        'icon' => 'fa fa-code-fork',
        'link' => 'index.php?p=version-notes',
        'visible' => true,
        'items' => []
    ]
];

// Kullanıcının kayıtlı menü sırasını al ve sırala
$orderModel = new MenuOrderModel();
$userOrder = $orderModel->getOrderByUserId($userId);

$sortedMenu = [];
if (!empty($userOrder['main_order']) && is_array($userOrder['main_order'])) {
    // Kayıtlı sıraya göre ana menüleri ekle
    foreach ($userOrder['main_order'] as $menuKey) {
        if (isset($menuDefinitions[$menuKey])) {
            $sortedMenu[$menuKey] = $menuDefinitions[$menuKey];
        }
    }
    // Listede olmayan veya sonradan eklenmiş yeni menüleri sona ekle
    foreach ($menuDefinitions as $menuKey => $menuData) {
        if (!isset($sortedMenu[$menuKey])) {
            $sortedMenu[$menuKey] = $menuData;
        }
    }
} else {
    $sortedMenu = $menuDefinitions;
}

// Alt menüleri kullanıcının sırasına göre düzenle
foreach ($sortedMenu as $menuKey => &$menuData) {
    if (!empty($menuData['items'])) {
        $sortedSubItems = [];
        if (!empty($userOrder['sub_order'][$menuKey]) && is_array($userOrder['sub_order'][$menuKey])) {
            foreach ($userOrder['sub_order'][$menuKey] as $subKey) {
                if (isset($menuData['items'][$subKey])) {
                    $sortedSubItems[$subKey] = $menuData['items'][$subKey];
                }
            }
            foreach ($menuData['items'] as $subKey => $subData) {
                if (!isset($sortedSubItems[$subKey])) {
                    $sortedSubItems[$subKey] = $subData;
                }
            }
            $menuData['items'] = $sortedSubItems;
        }
    }
}
unset($menuData);

// Menüde doğrudan linki olmayan alt/detay/düzenleme sayfalarının ilgili ana menü ve alt öğe eşleştirmeleri
$pageMenuAliases = [
    // Satın Alma (Purchases)
    'purchases/price-request-manage' => ['menu' => 'purchases', 'item' => 'purchases/price-request-list'],
    'purchases/price-request-print'  => ['menu' => 'purchases', 'item' => 'purchases/price-request-list'],
    'purchases/price-request-detail-modal' => ['menu' => 'purchases', 'item' => 'purchases/price-request-list'],
    'purchase-demand-edit'           => ['menu' => 'purchases', 'item' => 'purchase-demand-new'],
    'purchase-demand-detail'         => ['menu' => 'purchases', 'item' => 'purchase-demand-new'],
    'purchase-detail'                => ['menu' => 'purchases', 'item' => 'purchases'],
    'purchase-edit'                  => ['menu' => 'purchases', 'item' => 'purchases'],
    'purchase-print'                 => ['menu' => 'purchases', 'item' => 'purchases'],
    'purchase-new'                   => ['menu' => 'purchases', 'item' => 'purchases/manage'],

    // Teklifler (Offers)
    'offer-view'                     => ['menu' => 'offers', 'item' => 'offers/list'],
    'offer-edit'                     => ['menu' => 'offers', 'item' => 'offers/list'],
    'offers/icmal-view'              => ['menu' => 'offers', 'item' => 'offers/list'],
    'offers/icmal-to-xls'            => ['menu' => 'offers', 'item' => 'offers/list'],

    // Servis (Service)
    'service-view'                   => ['menu' => 'service', 'item' => 'service/list'],
    'service-edit'                   => ['menu' => 'service', 'item' => 'service/list'],
    'service-detail'                 => ['menu' => 'service', 'item' => 'service/list'],
    'new-service'                    => ['menu' => 'service', 'item' => 'service/manage'],

    // Keşif (Kesif)
    'kesif/view-pdf'                 => ['menu' => 'kesif', 'item' => 'kesif/list'],
    'kesif/export'                   => ['menu' => 'kesif', 'item' => 'kesif/list'],

    // Firmalar (Customers)
    'customer-edit'                  => ['menu' => 'customers', 'item' => 'customers/list'],
    'customer-info'                  => ['menu' => 'customers', 'item' => 'customers/list'],
    'customer-new'                   => ['menu' => 'customers', 'item' => 'customers/manage'],
    'customer-label'                 => ['menu' => 'customers', 'item' => 'customers/list'],

    // Ürünler (Products)
    'product-edit'                   => ['menu' => 'products', 'item' => 'products/list'],
    'product-new'                    => ['menu' => 'products', 'item' => 'products/manage'],

    // Stok Yönetimi (Stock Activity)
    'stock-activity'                 => ['menu' => 'stock-activity', 'item' => 'stock-activity/list'],

    // Evrak Takip (Indocument)
    'edit-indocument'                => ['menu' => 'indocument', 'item' => 'view-indocument'],
    'indocument-edit'                => ['menu' => 'indocument', 'item' => 'view-indocument'],

    // Görevler (Missions)
    'edit-mission'                   => ['menu' => 'missions', 'item' => 'all-missions'],
    'view-mission'                   => ['menu' => 'missions', 'item' => 'all-missions'],

    // Yapılacaklar (Tasks)
    'task-edit'                      => ['menu' => 'tasks', 'item' => 'tasks'],

    // Notlar (Notes)
    'edit-note'                      => ['menu' => 'notes', 'item' => 'all-notes'],

    // Destek Talepleri (Support)
    'support-detail'                 => ['menu' => 'support', 'item' => 'support-list'],

    // Ekip (Team)
    'user-edit'                      => ['menu' => 'team', 'item' => 'users'],
    'permission-edit'                => ['menu' => 'team', 'item' => 'permission-settings'],
    'permission-new'                 => ['menu' => 'team', 'item' => 'permission-settings'],

    // Tanımlamalar (Definitions)
    'edit-unit'                      => ['menu' => 'definitions', 'item' => 'define-units'],

    // Mail & SMS
    'report-send-as-mail'            => ['menu' => 'mail-sms', 'item' => 'send-mail'],


    // Sürüm Notları (Version Notes)
    'version-note-manage'            => ['menu' => 'version-notes', 'item' => 'version-notes'],
];


// Aktif menü ve alt menü tespiti (sayfa yüklenmeden önce sunucu tarafında açık getirmek için)
$currentP = (string)($_GET['p'] ?? 'home');
$currentFullQuery = (string)($_SERVER['QUERY_STRING'] ?? ('p=' . $currentP));
parse_str($currentFullQuery, $currentGetParams);

$targetMenuKey = null;
$targetSubKey = null;

if (isset($pageMenuAliases[$currentP])) {
    $targetMenuKey = $pageMenuAliases[$currentP]['menu'] ?? null;
    $targetSubKey = $pageMenuAliases[$currentP]['item'] ?? null;
} elseif (strpos($currentP, '/') !== false) {
    $prefix = explode('/', $currentP, 2)[0];
    if (isset($menuDefinitions[$prefix])) {
        $targetMenuKey = $prefix;
    }
}

$isMenuLinkActive = function($link, $itemKey = null, $menuKey = null) use ($currentP, $currentGetParams, $targetMenuKey, $targetSubKey) {
    if (!empty($targetMenuKey) && $targetMenuKey === $menuKey) {
        if (!empty($targetSubKey) && $targetSubKey === $itemKey) {
            return true;
        }
    }

    if (empty($link)) return false;
    
    $parsed = parse_url($link);
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $params);
        if (isset($params['p'])) {
            // Eğer ek parametreler varsa (örn: ?p=offers/list&sablon=true) tüm parametreler eşleşmeli
            if (count($params) > 1) {
                foreach ($params as $k => $v) {
                    if ((string)($currentGetParams[$k] ?? '') !== (string)$v) {
                        return false;
                    }
                }
                return true;
            } elseif ($params['p'] === $currentP) {
                // Link sadece tek p parametresi içeriyorsa, URL'de ek filtre parametreleri yokken tam uyar
                return empty($currentGetParams['sablon']) || ($params['p'] !== 'offers/list');
            }
        }
    }
    return false;
};
?>
<div class="left-side-bar">
    <div class="brand-logo">
        <a href="index.php">
            <img src="<?php echo set("logo"); ?>"
                alt="<?php echo set("site_title"); ?> Logo">
        </a>
    </div>

    <!-- Sabit Arama ve Menü Ayarları Alanı (Scroll dışı, kesinlikle sabit) -->
    <div class="sidebar-search-wrap">
        <div class="sidebar-search">
            <i class="fa fa-search" aria-hidden="true"></i>
            <input type="text" class="sidebar-search-input" placeholder="Menüde ara..." aria-label="Menüde ara">
        </div>
        <div class="sidebar-menu-settings dropdown">
            <button type="button" class="btn btn-sm btn-menu-settings" id="sidebarMenuSettingsDropdown" data-toggle="dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Menü Ayarları">
                <i class="fa fa-cog"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="sidebarMenuSettingsDropdown">
                <h6 class="dropdown-header">Menü Ayarları</h6>
                <a class="dropdown-item" href="javascript:;" id="btn-reset-menu-order">
                    <i class="fa fa-undo text-primary mr-2"></i> Varsayılan Menü Sırası
                </a>
            </div>
        </div>
    </div>

    <div class="menu-block customscroll">
        <div class="sidebar-menu">
            <ul id="accordion-menu">
                <?php 
                foreach ($sortedMenu as $menuKey => $menu): 
                    if (!$menu['visible']) continue;
                    
                    // Alt menü varsa en az bir alt öğenin görünür olduğunu kontrol et
                    $hasVisibleSubItems = false;
                    $isParentActive = false;
                    $activeSubKeys = [];
                    
                    if (!empty($menu['items'])) {
                        foreach ($menu['items'] as $subKey => $item) {
                            if (!empty($item['visible'])) {
                                $hasVisibleSubItems = true;
                                if ($isMenuLinkActive($item['link'], $subKey, $menuKey)) {
                                    $isParentActive = true;
                                    $activeSubKeys[$subKey] = true;
                                }
                            }
                        }
                        if (!$hasVisibleSubItems) continue;
                        
                        // Eğer hiçbir alt öğe doğrudan aktif olmadıysa ama hedef ana menü burasıysa (örn: prefix eşleşmesi):
                        if (!$isParentActive && $targetMenuKey === $menuKey) {
                            $isParentActive = true;
                        }
                    } else {
                        $isParentActive = $isMenuLinkActive($menu['link'] ?? '', null, $menuKey) || ($targetMenuKey === $menuKey);
                    }
                ?>
                    <li class="dropdown<?php echo $isParentActive ? ' show active' : ''; ?>" data-menu-key="<?php echo htmlspecialchars($menuKey, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (empty($menu['items'])): ?>
                            <a href="<?php echo htmlspecialchars($menu['link'], ENT_QUOTES, 'UTF-8'); ?>" class="dropdown-toggle no-arrow<?php echo $isParentActive ? ' active' : ''; ?>">
                                <span class="<?php echo htmlspecialchars($menu['icon'], ENT_QUOTES, 'UTF-8'); ?>"></span>
                                <span class="mtext"><?php echo htmlspecialchars($menu['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        <?php else: ?>
                            <a href="javascript:;" class="dropdown-toggle" data-option="<?php echo $isParentActive ? 'on' : 'off'; ?>">
                                <span class="<?php echo htmlspecialchars($menu['icon'], ENT_QUOTES, 'UTF-8'); ?>"></span>
                                <span class="mtext"><?php echo htmlspecialchars($menu['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                            <ul class="submenu" data-parent-key="<?php echo htmlspecialchars($menuKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isParentActive ? ' style="display: block;"' : ''; ?>>
                                <?php foreach ($menu['items'] as $itemKey => $item): 
                                    if (empty($item['visible'])) continue;
                                    $isSubActive = !empty($activeSubKeys[$itemKey]);
                                ?>
                                    <li data-item-key="<?php echo htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8'); ?>">
                                        <a href="<?php echo htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $isSubActive ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script>
(function () {
    try {
        var menuBlock = document.querySelector('.left-side-bar .menu-block');
        if (!menuBlock) return;

        function getSidebarDesiredScroll() {
            var saved = sessionStorage.getItem('sidebar_scroll_top');
            if (saved !== null && !isNaN(parseInt(saved, 10)) && parseInt(saved, 10) > 0) {
                return parseInt(saved, 10);
            }

            var active = menuBlock.querySelector('#accordion-menu a.active') ||
                         menuBlock.querySelector('#accordion-menu li.show.active') ||
                         menuBlock.querySelector('#accordion-menu li.show') ||
                         menuBlock.querySelector('#accordion-menu li.active');

            if (active) {
                var conRect = menuBlock.getBoundingClientRect();
                var actRect = active.getBoundingClientRect();
                var mcs = menuBlock.querySelector('.mCSB_container');
                var currScroll = mcs ? Math.abs(mcs.offsetTop || 0) : (menuBlock.scrollTop || 0);

                var relativeTop = (actRect.top - conRect.top) + currScroll;
                var blockH = menuBlock.clientHeight || (window.innerHeight - 120);
                var target = Math.max(0, Math.round(relativeTop - Math.floor(blockH / 3)));
                return target;
            }
            return 0;
        }

        window.__restoreSidebarScroll = function () {
            var targetTop = getSidebarDesiredScroll();
            if (targetTop > 0) {
                menuBlock.scrollTop = targetTop;
                if (typeof $ !== 'undefined' && typeof $.fn.mCustomScrollbar !== 'undefined' && $(menuBlock).data('mCS')) {
                    $(menuBlock).mCustomScrollbar("scrollTo", targetTop, {
                        scrollInertia: 0,
                        timeout: 0
                    });
                }
            }
        };

        // 1. Sayfa parse edilir edilmez hemen uygula
        window.__restoreSidebarScroll();

        // 2. DOMContentLoaded olduğunda tekrar garanti et
        document.addEventListener('DOMContentLoaded', function () {
            window.__restoreSidebarScroll();
        });

        // Native scroll takibi
        menuBlock.addEventListener('scroll', function () {
            if (menuBlock.scrollTop >= 0) {
                sessionStorage.setItem('sidebar_scroll_top', Math.round(menuBlock.scrollTop));
            }
        }, { passive: true });

        // Menü linklerine tıklandığında anlık scroll pozisyonunu kaydet
        var menuUl = document.getElementById('accordion-menu');
        if (menuUl) {
            menuUl.addEventListener('click', function (e) {
                var link = e.target.closest('a');
                if (!link) return;
                var currentTop = 0;
                var mcsContainer = menuBlock.querySelector('.mCSB_container');
                if (mcsContainer) {
                    currentTop = Math.abs(mcsContainer.offsetTop || 0);
                } else {
                    currentTop = menuBlock.scrollTop || 0;
                }
                sessionStorage.setItem('sidebar_scroll_top', Math.round(currentTop));
            });
        }
    } catch (e) {
        console.error('Sidebar scroll init error:', e);
    }
})();
</script>
