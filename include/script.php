<?php
$page = $_GET['p'] ?? '';

?>

<!-- js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
	integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
</script>

<?php

//Sayfa purchases/manage ise
if ($page == 'purchases/manage') {
	echo '<script src="include/js/purchase.js" defer></script>';
	echo '<script src="pages/1/purchases/script.js" defer></script>';
}

//Sayfa products/manage ise
if ($page == 'products/manage' || $page == 'products/list' || $page == 'products') {
    $jsVer = file_exists('pages/1/products/products.js') ? filemtime('pages/1/products/products.js') : time();
	echo '<script src="pages/1/products/products.js?v=' . $jsVer . '" defer></script>';
}

?>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="vendors/scripts/script.js?v=<?php echo file_exists('vendors/scripts/script.js') ? filemtime('vendors/scripts/script.js') : time(); ?>"></script>
<script src="src/scripts/validate/core.js?v=<?php echo file_exists('src/scripts/validate/core.js') ? filemtime('src/scripts/validate/core.js') : time(); ?>"></script>
<script src="include/js/app.js?v=<?php echo file_exists('include/js/app.js') ? filemtime('include/js/app.js') : time(); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="include/js/menu-order.js?v=<?php echo file_exists('include/js/menu-order.js') ? filemtime('include/js/menu-order.js') : time(); ?>"></script>
<script src="include/js/maintenance-notice.js?v=<?php echo file_exists('include/js/maintenance-notice.js') ? filemtime('include/js/maintenance-notice.js') : time(); ?>"></script>
