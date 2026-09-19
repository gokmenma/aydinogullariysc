<?php
$page = $_GET['p'];

?>


<!-- js -->


<!-- <script src="src/scripts/setting.js"></script> -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
	integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
</script>

<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.js"></script> -->



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
<script src="vendors/scripts/script.js"></script>
<script src="src/scripts/validate/core.js"></script>
<script src="include/js/app.js?v=<?php echo filemtime('include/js/app.js'); ?>"></script>


<?php

if ($page == 'purchases/manage' || $page == 'offers/offer-manage' || $page =="purchase-demand-new" || $page =="purchase-demand-edit") {
	//echo '<script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>';
	echo '<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>';
}
?>

