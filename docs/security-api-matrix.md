# API Güvenlik ve Yetki Matrisi

Bu envanter `App/Helper/ApiSecurity.php` tarafından çalışma anında uygulanır. `api/maintenance-status.php` dışındaki tüm API uçları oturum gerektirir. GET/HEAD/OPTIONS dışındaki istekler ayrıca CSRF token gerektirir.

| Alan | Endpointler | Gerekli yetkiler |
|---|---|---|
| Firma | `App/api/customer.php`, `customers-list.php`, `api/customers_*` | `customer_dashboard`, işlem bazında `customeradd/edit/delete/export` |
| Teklif | `get-offers.php`, filtre/kalem uçları, `offer.php`, `export-offers.php` | `offerview`; işlem bazında `offeradd/edit/delete/copy`; dışa aktarmada `data_export_offers` |
| Servis | `service-save.php`, `api/services_*` | `serviceView`, işlem bazında `serviceAdd/Edit`, dışa aktarmada `data_export_service` |
| Ürün | `products.php`, `products_datatables.php`, `pages/1/products/api.php` | ürün görüntüleme/ekleme/düzenleme/silme yetkileri |
| Satın alma | `purchase.php` | işlem bazında `purchaseadd/edit/delete` |
| Rapor/keşif | `reports_datatables.php`, `pages/1/kesif/api.php` | `reportview`, işlem bazında `kesifView/Create/Edit/Delete` |
| Yönetim | `permission_save.php`, `test_smtp.php`, `get-logs.php`, `version-notes.php` | `authdefine/authEdit` veya `panelsettings` |
| E-posta | `mail_templates.php`, `search_recipients.php` | `mailandsmssend` |
| Dosya yükleme | `uploadoffer.php`, `upfileof*.php` | `fileadd` |
| Kullanıcı yardımcıları | `global_search.php`, `menu_order.php`, `define.php`, `units.php`, `geocode.php` | doğrulanmış oturum |
| Bakım durumu | `api/maintenance-status.php` | herkese açık, salt okunur |

Tanımsız yeni bir `api/` veya `App/api/` uç noktası varsayılan olarak reddedilir. Yeni endpoint eklenirken çalışma matrisi ve bu belge birlikte güncellenmelidir.
