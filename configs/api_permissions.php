<?php

// İnsan tarafından okunabilir API yetki envanteri. Çalışan matris App/Helper/ApiSecurity.php içindedir.
return [
    'public' => ['api/maintenance-status.php' => 'Bakım durumu (salt okunur)'],
    'authenticated_only' => ['api/global_search.php', 'api/menu_order.php', 'api/search_customers.php', 'api/search_products.php', 'App/api/define.php', 'App/api/geocode.php', 'App/api/units.php', 'pages/1/ajax.php'],
    'permission_protected' => 'Diğer tüm api/ ve App/api/ endpointleri modül yetkisi gerektirir.',
    'csrf' => 'GET/HEAD/OPTIONS dışındaki tüm kayıtlı API istekleri X-CSRF-Token gerektirir.',
];
