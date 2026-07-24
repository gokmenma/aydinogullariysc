<?php

if (($_GET['st'] ?? '') === 'customer-deleted') {
    showAlert("alert", "Bu firma silinmiş olduğu için düzenleme sayfası açılamaz.");
}

if (@$_GET["id"] && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177") {
    permcontrol("customerdelete");
    $cdid = $_GET["id"];
    $contq = $ac->prepare("SELECT * FROM customers WHERE id = ?");
    $contq->execute(array($cdid));
    if ($contq->fetch(PDO::FETCH_ASSOC)) {
        $deletq = $ac->prepare(
            "UPDATE customers
             SET deleted_at = ?, deleted_by = ?
             WHERE id = ? AND deleted_at IS NULL"
        );
        $deletq->execute(array(date('Y-m-d H:i:s'), $_SESSION['lid'] ?? 0, $cdid));


        if ($deletq) {
            header("Location: index.php?p=customers&id=$cdid&type=delete");
        }
    }
}

?>
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
    <style>
        table tr td{
           padding: 5px;
        }
        /* Sağ Tık (Context Menu) Stilleri */
        .custom-context-menu {
            display: none;
            position: fixed;
            z-index: 99999;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.08);
            padding: 8px 0;
            min-width: 220px;
            backdrop-filter: blur(8px);
            transition: opacity 0.15s ease, transform 0.15s ease;
        }
        .dark-mode .custom-context-menu {
            background: #1e293b !important;
            border-color: #334155 !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
        }
        .custom-context-menu .cm-header {
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 4px;
        }
        .dark-mode .custom-context-menu .cm-header {
            color: #94a3b8 !important;
            border-bottom-color: #334155 !important;
        }
        .custom-context-menu a,
        .custom-context-menu button {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 9px 16px;
            font-size: 13.5px;
            color: #334155;
            background: transparent;
            border: none;
            text-align: left;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .dark-mode .custom-context-menu a,
        .dark-mode .custom-context-menu button {
            color: #e2e8f0 !important;
        }
        .custom-context-menu a:hover,
        .custom-context-menu button:hover {
            background: #f1f5f9;
            color: #0284c7;
        }
        .dark-mode .custom-context-menu a:hover,
        .dark-mode .custom-context-menu button:hover {
            background: #334155 !important;
            color: #38bdf8 !important;
        }
        .custom-context-menu a.cm-danger,
        .custom-context-menu button.cm-danger {
            color: #ef4444;
        }
        .custom-context-menu a.cm-danger:hover,
        .custom-context-menu button.cm-danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        .dark-mode .custom-context-menu a.cm-danger:hover,
        .dark-mode .custom-context-menu button.cm-danger:hover {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #f87171 !important;
        }
        .custom-context-menu i {
            width: 20px;
            font-size: 14px;
            margin-right: 10px;
            text-align: center;
        }
        .custom-context-menu .cm-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 4px 0;
        }
        .dark-mode .custom-context-menu .cm-divider {
            background: #334155 !important;
        }
        tr.context-menu-active {
            background-color: rgba(59, 130, 246, 0.1) !important;
        }
    </style>
    <!-- Modal -->
    <div class="modal fade" id="customerdetails" tabindex="-1" role="dialog"
        aria-labelledby="customerdetailsCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerdeteailHeader"> Detay Bilgisi</h5>
                    <button type="button" class="closeModal close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table>
                        <tr>
                            <td><label for="">Kayıt Yapan Personel :</label></td>
                            <td><label for="" id="creator"></label></td>
                        </tr>
                        <tr>
                            <td><label for="">Kayıt Tarihi :</label></td>
                            <td><label for="" id="create_time"></label></td>
                        </tr>
                        <tr>
                            <td><label for="">Güncelleme Yapan Personel :</label></td>
                            <td><label for="" id="updater"></label></td>
                        </tr>
                        <tr>
                            <td><label for="">Güncelleme Tarihi :</label></td>
                            <td><label for="" id="updated_at"></label></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="closeModal btn btn-primary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->

    <div class="clearfix mb-20">
        <div class="pull-left">
            <h5 class="text-blue">Müşteri Listesi</h5>
            <p class="font-14"> </p>
        </div>
        <?php if (permtrue("customeradd")) { ?>
            <a href="index.php?p=customers/manage"><button type="button" class="btn btn-primary btn-sm float-right"><i
                        class="fa fa-plus"></i> Yeni
                    Müşteri</button></a>
        <?php } ?>
        <?php if (permtrue("customerexport")) { ?>
            <button type="button" id="exportCustomers" class="btn btn-success btn-sm float-right mr-2">
                <i class="fa fa-file-excel-o"></i> Excel'e Aktar
            </button>
        <?php } ?>
    </div>
    <table id="customerlist" class="data-table table-bordered table-hover table-sm table-responsive">
        <thead>
            <tr>
                <th scope="col" class="app-item-number">Sıra</th>
                <th>Firma Adı</th>
                <th>Grup</th>
                <th>Satış Temsilcisi</th>
                <th>Teklif/Servis Sayısı</th>
                <th>E-Posta Adresi</th>
                <th>GSM</th>
                <th class="datatable-nosort" style="min-width:90px">İşlem</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script src="include/js/data-table.js"></script>
<script>
    function showExportLoadingNotification() {
        var swalObj = (typeof swal !== 'undefined') ? swal : ((typeof Swal !== 'undefined') ? Swal : null);
        if (swalObj) {
            swalObj.fire({
                title: "Excel Dosyası Hazırlanıyor",
                html: "Lütfen bekleyiniz, veriler indiriliyor...<br><small style='color:#888;'>İndirme işlemi birazdan otomatik başlayacaktır.</small>",
                icon: "info",
                showConfirmButton: false,
                allowOutsideClick: true,
                timer: 4000,
                timerProgressBar: true,
                didOpen: function() {
                    if (typeof swalObj.showLoading === 'function') {
                        swalObj.showLoading();
                    }
                }
            });
        }
    }

    $(document).ready(function () {
        var customerTable = $('#customerlist').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/customers_datatables.php',
                type: 'GET'
            },
            columns: [
                { data: 0, className: 'text-center' },
                { data: 1 },
                { data: 2 },
                { data: 3 },
                { data: 4, orderable: false },
                { data: 5 },
                { data: 6 },
                { data: 7, orderable: false }
            ],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                url: 'include/js/tr.json',
                processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Yükleniyor...</span>'
            },
            responsive: true,
            order: [[0, 'desc']],
            orderCellsTop: true,
            initComplete: function () {
                var api = this.api();
                var tableId = api.table().node().id;
                // Arama satırını <thead> içine ekle
                $("#" + tableId + " thead").append('<tr class="search-input-row"></tr>');

                api.columns().every(function (index) {
                    let column = this;
                    let header = $(column.header());
                    let title = header.text();

                    // Sadece arama yapılabilecek alanlar için input oluştur
                    if (column.visible() && title && title.trim() !== 'İşlem' && title.trim() !== 'İşlemler' && title.trim() !== 'Sıra' && title.trim() !== 'Teklif/Servis Sayısı') {
                        let input = $('<input type="text" class="form-control form-control-sm" placeholder="' + title + '" autocomplete="off">')
                            .appendTo($('<th class="search"></th>').appendTo("#" + tableId + " .search-input-row"))
                            .on('keyup change clear', function () {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    } else {
                        $("#" + tableId + " .search-input-row").append('<th></th>');
                    }
                });
            }
        });

        $('#exportCustomers').on('click', function () {
            showExportLoadingNotification();
            var query = $.param(customerTable.ajax.params());
            window.location.href = 'api/customers_export.php?' + query;
        });

        // Tabloda Sağ Tık (Context Menu) İşlemleri
        $(document).on('contextmenu', '#customerlist tbody tr', function(e) {
            if ($(this).find('td').length <= 1) return;

            e.preventDefault();
            
            var $tr = $(this);
            $('#customerlist tbody tr').removeClass('context-menu-active');
            $tr.addClass('context-menu-active');

            var companyName = $tr.find('td:nth-child(2)').text().trim() || 'Müşteri İşlemleri';
            var $actionTd = $tr.find('td:last-child');
            
            var menuHtml = '<div class="cm-header"><i class="fa fa-building-o mr-1"></i> ' + $('<div>').text(companyName).html() + '</div>';

            // 1. Düzenle Butonu Varsa
            var $editBtn = $actionTd.find('a[data-tooltip="Görüntüle-Düzenle"], a.btn-outline-info');
            if ($editBtn.length) {
                menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-info mr-2"></i> Düzenle / Görüntüle</a>';
            }

            // 2. Dropdown içindeki elemanlar
            var $dropdownItems = $actionTd.find('.dropdown-menu .dropdown-item');
            if ($dropdownItems.length) {
                $dropdownItems.each(function() {
                    var $item = $(this);
                    var href = $item.attr('href') || '#';
                    var target = $item.attr('target') ? ' target="' + $item.attr('target') + '"' : '';
                    var text = $item.html();
                    var dataId = $item.attr('data-id') ? ' data-id="' + $item.attr('data-id') + '"' : '';
                    var classAttr = $item.attr('class') || '';

                    menuHtml += '<a href="' + href + '"' + target + dataId + ' class="' + classAttr + '">' + text + '</a>';
                });
            }

            // 3. Sil Butonu Varsa
            var $deleteBtn = $actionTd.find('a[data-tooltip="Sil"], a.btn-danger');
            if ($deleteBtn.length) {
                menuHtml += '<div class="cm-divider"></div>';
                var onClickAttr = $deleteBtn.attr('onclick') || $deleteBtn.attr('onClick') || '';
                menuHtml += '<a href="#" class="cm-danger" onclick="' + onClickAttr + '; return false;"><i class="fa fa-trash text-danger mr-2"></i> Sil</a>';
            }

            var $contextMenu = $('#customContextMenu');
            if (!$contextMenu.length) {
                $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
            }
            
            $contextMenu.html(menuHtml);

            var mouseX = e.clientX;
            var mouseY = e.clientY;
            
            $contextMenu.css({ display: 'block', visibility: 'hidden' });
            var menuWidth = $contextMenu.outerWidth();
            var menuHeight = $contextMenu.outerHeight();
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();

            if (mouseX + menuWidth > windowWidth) {
                mouseX = windowWidth - menuWidth - 10;
            }
            if (mouseY + menuHeight > windowHeight) {
                mouseY = windowHeight - menuHeight - 10;
            }

            $contextMenu.css({
                top: mouseY + 'px',
                left: mouseX + 'px',
                visibility: 'visible',
                opacity: '1'
            });
        });

        // Menü dışına tıklanınca veya kaydırılınca kapat
        $(document).on('click scroll', function(e) {
            if (!$(e.target).closest('#customContextMenu').length) {
                $('#customContextMenu').hide();
                $('#customerlist tbody tr').removeClass('context-menu-active');
            }
        });

        // Menüdeki seçeneğe basılınca kapat
        $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
            $('#customContextMenu').hide();
            $('#customerlist tbody tr').removeClass('context-menu-active');
        });

        // ESC basılınca kapat
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#customContextMenu').hide();
                $('#customerlist tbody tr').removeClass('context-menu-active');
            }
        });

        // Detay butonu için event delegation kullan
        $(document).on("click", ".btn-detail", function () {
            var id = $(this).data("id");
            $.ajax({
                method: "POST",
                url: "pages/1/ajax.php?type=customer-detail",
                dataType: "json",
                data: {
                    id: id
                },
                success: function (response) {
                    $("#customerdetails").modal("show");
                    $("#creator").text(response.creator);
                    $("#create_time").text(response.create_time);
                    $("#updater").text(response.updater);
                    $("#updated_at").text(response.updated_at);
                }
            });
        });
    });

    $(".closeModal").click(function () {
        $("#customerdetails").modal("hide");
    });
</script>
