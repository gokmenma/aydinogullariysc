<?php
use App\Model\MenuOrderModel;

$userId = (int)(function_exists('sesset') ? sesset("id") : ($_SESSION['id'] ?? ($_SESSION['lid'] ?? 0)));
$userPerm = function_exists('sesset') ? sesset("permission") : ($_SESSION['permission'] ?? 0);

// Menü tanımları
$menuDefinitions = [
    'home' => [
        'title' => 'Ana Sayfa',
        'icon' => 'fa fa-home',
        'link' => 'index.php?p=home',
        'visible' => true,
        'items' => []
    ],
    'offers' => [
        'title' => 'Teklifler',
        'icon' => 'fa fa-file-o',
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
        'icon' => 'fa fa-table',
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
        'icon' => 'fa fa-street-view',
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
        'icon' => 'fa fa-user-plus',
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
        'icon' => 'fa fa-paint-brush',
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
                'visible' => true
            ]
        ]
    ],
    'stock-activity' => [
        'title' => 'Stok Yönetimi',
        'icon' => 'fa fa-list-ol',
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
        'icon' => 'fa fa-bar-chart',
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
        'icon' => 'fa fa-folder-open',
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
        'icon' => 'fa fa-file-zip-o',
        'visible' => (permtrue("fileadd") || permtrue("fileview") || permtrue("filedelete")),
        'items' => [
            'new-file' => [
                'title' => 'Dosya Yükle',
                'link' => 'index.php?p=new-file',
                'visible' => permtrue("fileadd")
            ],
            'all-files' => [
                'title' => 'Dosyaları Görüntüle',
                'link' => 'index.php?p=all-files',
                'visible' => permtrue("fileview")
            ],
            'file-categories' => [
                'title' => 'Dosya Kategorileri',
                'link' => 'index.php?p=file-categories',
                'visible' => (permtrue("fileadd") && permtrue("fileview") && permtrue("filedelete"))
            ]
        ]
    ],
    'missions' => [
        'title' => 'Görev Yönetimi',
        'icon' => 'fa fa-bookmark-o',
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
        'icon' => 'fa fa-calendar',
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
        'icon' => 'fa fa-envelope',
        'visible' => permtrue("mailandsmssend"),
        'items' => [
            'send-mail' => [
                'title' => 'Mail Gönder',
                'link' => 'index.php?p=send-mail',
                'visible' => true
            ],
            'send-sms' => [
                'title' => 'SMS Gönder',
                'link' => 'index.php?p=send-sms',
                'visible' => (set("sms_active") == "on")
            ],
            'mail-logs' => [
                'title' => 'Mail Kayıtları',
                'link' => 'index.php?p=mail-logs',
                'visible' => (set("sms_active") == "on")
            ],
            'send-mail-accounts' => [
                'title' => 'Mail Hesapları',
                'link' => 'index.php?p=send-mail-accounts',
                'visible' => ($userId == 12 || $userId == 1)
            ]
        ]
    ],
    'notes' => [
        'title' => 'Notlar',
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
                'visible' => ($userPerm == 1 || permtrue("noteedit"))
            ]
        ]
    ],
    'support' => [
        'title' => 'Destek Sistemi',
        'icon' => 'fa fa-support',
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
        'icon' => 'fa fa-user',
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
                'visible' => permtrue("authdefine")
            ]
        ]
    ],
    'definitions' => [
        'title' => 'Tanımlamalar',
        'icon' => 'fa fa-gears',
        'visible' => true,
        'items' => [
            'service-type' => [
                'title' => 'Servis Konusu Tanımlama',
                'link' => 'index.php?p=service-type',
                'visible' => ($userPerm == 1)
            ],
            'service-status' => [
                'title' => 'Servis Durumu Tanımlama',
                'link' => 'index.php?p=service-status',
                'visible' => ($userPerm == 1)
            ],
            'service-region' => [
                'title' => 'Servis Bölgesi Tanımlama',
                'link' => 'index.php?p=service-region',
                'visible' => ($userPerm == 1)
            ],
            'paytype' => [
                'title' => 'Tahsilat Türü Tanımlama',
                'link' => 'index.php?p=paytype',
                'visible' => ($userPerm == 1)
            ],
            'offer-templates' => [
                'title' => 'Teklif Üst/Alt Bilgi Tanımlama',
                'link' => 'index.php?p=offer-templates',
                'visible' => ($userPerm == 1)
            ],
            'define-units' => [
                'title' => 'Birim Tanımlama',
                'link' => 'index.php?p=define-units',
                'visible' => ($userPerm == 1)
            ]
        ]
    ],
    'panel-settings' => [
        'title' => 'Panel Ayarları',
        'icon' => 'fa fa-sitemap',
        'link' => 'index.php?p=settings',
        'visible' => permtrue("panelsettings"),
        'items' => []
    ],
    'logs' => [
        'title' => 'Sistem Aktiviteleri',
        'icon' => 'fa fa-history',
        'link' => 'index.php?p=logs/index',
        'visible' => in_array($userId, [1, 12]),
        'items' => []
    ],
    'backups' => [
        'title' => 'Yedekleme & Kurtarma',
        'icon' => 'fa fa-database',
        'link' => 'index.php?p=backups',
        'visible' => (permtrue("backupmanage") || $userId == 1),
        'items' => []
    ],
    'version-notes' => [
        'title' => 'Sürüm Notları',
        'icon' => 'fa fa-file-text',
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

// Aktif menü ve alt menü tespiti (sayfa yüklenmeden önce sunucu tarafında açık getirmek için)
$currentP = (string)($_GET['p'] ?? 'home');
$currentFullQuery = (string)($_SERVER['QUERY_STRING'] ?? ('p=' . $currentP));
parse_str($currentFullQuery, $currentGetParams);

$isMenuLinkActive = function($link) use ($currentP, $currentGetParams) {
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
            <img style="margin: 0px" width="250" src="<?php echo set("logo"); ?>"
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
                <?php foreach ($sortedMenu as $menuKey => $menu): 
                    if (!$menu['visible']) continue;
                    
                    // Alt menü varsa en az bir alt öğenin görünür olduğunu kontrol et
                    $hasVisibleSubItems = false;
                    $isParentActive = false;
                    $activeSubKeys = [];
                    
                    if (!empty($menu['items'])) {
                        foreach ($menu['items'] as $subKey => $item) {
                            if (!empty($item['visible'])) {
                                $hasVisibleSubItems = true;
                                if ($isMenuLinkActive($item['link'])) {
                                    $isParentActive = true;
                                    $activeSubKeys[$subKey] = true;
                                }
                            }
                        }
                        if (!$hasVisibleSubItems) continue;
                    } else {
                        $isParentActive = $isMenuLinkActive($menu['link'] ?? '');
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
