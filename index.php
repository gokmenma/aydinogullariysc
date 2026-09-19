<?php

require_once "bootstrap.php";

if (set("system_statu") == 1) {
    if (!isset($_SESSION["login"])) {
        $returnUrl = urlencode($_SERVER["REQUEST_URI"]);
        header("Location: login.php?returnUrl=" . $returnUrl);
        exit;
    }
} else {
    exit;
}

$skid = sesset("perm");
$plink = @$_GET["p"];

if ($plink) {
    $ttlinks = $plink;
} else {
    header("Location:index.php?p=home");
}

try {
    $pquery = $ac->prepare("SELECT * FROM pages WHERE p_link = ?");
    $pquery->execute(array($plink));
    $pdat = $pquery->fetch(PDO::FETCH_ASSOC);

    if (isset($_SESSION['login'])) {
        $p_title = $pdat['p_title'] ?? $plink;
        audit_log(
            "view",
            "navigation",
            "Sayfa görüntülendi: " . $p_title,
            "page",
            $plink,
            ['page_title' => $p_title]
        );
    }
} catch (PDOException $ex) {
    echo "Error: " . $ex->getMessage();
} 
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'include/head.php'; ?>
</head>
<body>
    <div id="preloader">
        <div class="loader"></div>
    </div>

    <?php include 'include/header.php'; ?>
    <?php include 'include/sidebar.php'; ?>
    <?php
    if (sesset("permission") != $_SESSION["perm"]) {
        header("Location: logout.php");
        exit;
    }
    if ($plink == "home") {
    } else {
        ?>
        <div class="main-container" id="content">
            <div id="maincontainer" class="content crm-inner-page-wrapper pd-ltr-20 xs-pd-20-10">
    <?php } ?>
    <?php
    if ($plink) {
        $pl = $ac->prepare("SELECT * FROM pages WHERE p_link = ?");
        $pl->execute(array($plink));
        $pn = $pl->fetch(PDO::FETCH_ASSOC);

        if ($pn) {
            $pln = $pn["p_link"];
            if (file_exists("pages/1/" . $pln . ".php")) {
                include "pages/1/" . $pln . ".php";
            } else {
                echo $pln;
            }
        } else {
            header("Location:index.php?p=home&code=0121");
        }
    } else {
        include "pages/" . $skid . "/home.php";
    }
    if (!$plink || $plink == "home") {
    } else {
        ?>
            </div>
            <?php include('include/footer.php'); ?>
        </div>
        <?php
    }
    ?>

    <?php include 'include/script.php'; ?>

    <script src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-1.13.8/af-2.6.0/b-2.4.2/b-colvis-2.4.2/b-html5-2.4.2/b-print-2.4.2/cr-1.7.0/date-1.5.1/fc-4.3.0/fh-3.4.0/kt-2.11.0/r-2.5.0/rg-1.4.1/rr-1.4.1/sc-2.3.0/sb-1.6.0/sp-2.2.0/sl-1.7.0/sr-1.3.0/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
    <script>
        if (window.jQuery && $.fn.dataTable) {
            $.extend(true, $.fn.dataTable.defaults, {
                dom: '<"row mb-2"<"col-12 text-md-right d-flex justify-content-md-end justify-content-start"f>>rt<"row mt-3 align-items-center"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4 text-md-center text-left"i><"col-sm-12 col-md-4 text-md-right text-left"p>>',
                language: {
                    search: "",
                    searchPlaceholder: "Tabloda ara...",
                    lengthMenu: "Sayfada _MENU_ kayıt göster",
                    info: "_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor",
                    infoEmpty: "Kayıt yok",
                    infoFiltered: "(_MAX_ kayıt içerisinden filtrelendi)",
                    zeroRecords: "Eşleşen kayıt bulunamadı",
                    paginate: {
                        first: "İlk",
                        previous: "Önceki",
                        next: "Sonraki",
                        last: "Son"
                    }
                }
            });
        }
    </script>
    <script src="include/js/table-filter.js?v=<?php echo file_exists('include/js/table-filter.js') ? filemtime('include/js/table-filter.js') : time(); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="include/js/apperance.js?v=<?php echo file_exists('include/js/apperance.js') ? filemtime('include/js/apperance.js') : time(); ?>"></script>

    <script>
        function removeActiveClass() {
            var dropdown = document.querySelector('.sidebar-menu .dropdown-toggle');
            if (dropdown) dropdown.classList.remove('active');
        }
        var sidebarMenu = document.querySelector('.sidebar-menu');
        if (sidebarMenu) sidebarMenu.addEventListener('click', removeActiveClass);
    </script>

    <script>
        (function () {
            var searchInput = document.querySelector('.sidebar-search-input');
            if (!searchInput) return;

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            searchInput.addEventListener('input', function () {
                var query = this.value.trim().toLowerCase();
                var items = document.querySelectorAll('#accordion-menu > li');

                items.forEach(function (li) {
                    var toggle = li.querySelector('.dropdown-toggle');
                    var label = toggle ? toggle.querySelector('.mtext') : null;
                    var labelText = label ? label.textContent.toLowerCase() : '';

                    var subLinks = li.querySelectorAll('.submenu li a');
                    var subMatch = false;
                    subLinks.forEach(function (a) {
                        if (a.textContent.toLowerCase().indexOf(query) !== -1) {
                            subMatch = true;
                        }
                    });

                    if (query === '' || labelText.indexOf(query) !== -1 || subMatch) {
                        li.style.display = '';
                        if (query !== '' && subMatch && !li.classList.contains('show')) {
                            li.querySelector('.submenu') && (li.querySelector('.submenu').style.display = 'block');
                        } else if (query === '') {
                            li.querySelector('.submenu') && (li.querySelector('.submenu').style.display = '');
                        }
                    } else {
                        li.style.display = 'none';
                    }
                });
            });
        })();
    </script>

    <script>
        window.addEventListener('load', function () {
            var preloader = document.getElementById('preloader');
            var content = document.getElementById('content');
            if (preloader) preloader.style.display = 'none';
            if (content) content.style.display = 'block';
        });
    </script>
</body>
</html>