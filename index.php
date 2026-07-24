<?php

require_once "bootstrap.php";

// phpinfo(); exit;
// Debug için hata göster
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Monolog logger'ı başlat

//global $ac;

// //Logger başlat - şimdilik devre dışı
// $logger = LoggerFactory::database($ac, $_SESSION['lid'] ?? 0, $_SESSION['username'] ?? 'Guest');
// $logger->info('Dashboard accessed', ['page' => $_GET['p'] ?? 'home']);


//echo "Sayfa Yükleniyor...\n"; exit();
if (set("system_statu") == 1) {
    if (!isset($_SESSION["login"])) {
        // Tam URL'yi al ve URL kodlaması yap
        $returnUrl = urlencode($_SERVER["REQUEST_URI"]);

        // Kullanıcıyı login sayfasına yönlendir
        header("Location: login.php?returnUrl=" . $returnUrl);
        exit;

    }
} else {
    exit; // Sistem durumu 1 değilse scripti durdurun
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

    // Sayfa ziyareti logla
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
            <div id="maincontainer" class="content pd-ltr-10 xs-pd-10-10">

                <!--<div class="min-height-200px">
                     <div class="page-header">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <div class="title">
                                <h4></h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">
                                        <?php echo set("site_title"); ?></a></li>
                                    <li class="breadcrumb-item active" aria-current="page"><?php echo $pdat["p_title"]; ?>

                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div> -->
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
                        //  header("Location:404.php");
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




    <script
        src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-1.13.8/af-2.6.0/b-2.4.2/b-colvis-2.4.2/b-html5-2.4.2/b-print-2.4.2/cr-1.7.0/date-1.5.1/fc-4.3.0/fh-3.4.0/kt-2.11.0/r-2.5.0/rg-1.4.1/rr-1.4.1/sc-2.3.0/sb-1.6.0/sp-2.2.0/sl-1.7.0/sr-1.3.0/datatables.min.js">
        </script>
    <script>
        if (window.jQuery && $.fn.dataTable) {
            $.extend(true, $.fn.dataTable.defaults, {
                dom: 'rt<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>><"row mt-2"<"col-sm-12 col-md-12"l>>'
            });
        }
    </script>
    <!-- Excel dosyasından verileri okumak için -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- <script src="src/plugins/switchery/dist/switchery.js"></script> -->
    <script src="include/js/apperance.js"></script>

    <script>
        function removeActiveClass() {
            var dropdown = document.querySelector('.sidebar-menu .dropdown-toggle');
            dropdown.classList.remove('active');

        }
        document.querySelector('.sidebar-menu').addEventListener('click', removeActiveClass);
    </script>

    <script>
        (function () {
            var searchInput = document.querySelector('.sidebar-search-input');
            if (!searchInput) return;

            // Enter tuşu ile sayfa yenilenmesini engelle
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            // Gerçek zamanlı menü filtreleme
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

        //Sayfa yüklendiğinde içeriği göster, preloader'ı gizle
        window.addEventListener('load', function () {
            var preloader = document.getElementById('preloader');
            var content = document.getElementById('content');

            preloader.style.display = 'none';
            content.style.display = 'block';
        });

    </script>
</body>

</html>
