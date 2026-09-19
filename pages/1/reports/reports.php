<?php
$ris = $_GET["id"] ?? null;
if (($_GET["st"] ?? "") == "success-mail") {
	showAlert("success", "Mail başarı ile gönderildi!");
}

?>


<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">

    <!-- Modal -->
    <div class="modal fade" id="reportdetail" tabindex="-1" role="dialog" aria-labelledby="reportdetailCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportdetailLongTitle"> Detay Bilgisi</h5>
                    <button type="button" class="closeModal close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row ml-2 mt-4">
                        Kayıt Yapan Personel : <label for="" id="creator"></label>
                    </div>
                    <div class="row ml-2 mb-4">
                        Kayıt Tarihi : <label for="" id="create_time"></label>
                    </div>



                </div>
                <div class="modal-footer">

                    <button type="button" class="closeModal btn btn-primary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->



    <div class="clearfix mb-30">
        <div class="pull-left">
            <h5 class="text-blue">Rapor Listesi</h5>
            <p class="font-14"> </p>
        </div>
        <div class="float-right mb-20">
            <a href="index.php?p=reports/dashboard" class="btn btn-sm btn-info mr-1"><i class="fa fa-dashboard"></i> Dashboard</a>
            <a href="#" class="btn btn-sm btn-primary" id="report-new" data-toggle="modal" data-type="new"
                data-target="#reporttypeModal"><i class="fa fa-plus"></i> Yeni Oluştur</a>
            <a href="#" id="content-view" class="btn btn-sm btn-success" data-type="content" data-toggle="modal"
                data-target="#reporttypeModal"><i class="fa fa-folder"></i>
                İçerik Listesi</a>

        </div>

        <!-- Modal -->
        <div class="modal fade" id="reporttypeModal" tabindex="-1" role="dialog" aria-labelledby="reporttypeModalTitle"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reporttypeModalLongTitle">Rapor Türü Seçiniz</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <select name="reporttype" id="reporttype" class="form-control selectpicker"
                            data-style="bg-white border">
                            <?php
                            $sql = $ac->prepare("SELECT * FROM report_types ");
                            $sql->execute();

                            while ($type = $sql->fetch(PDO::FETCH_ASSOC)) {
                                $newpagelink = "reports/" . $type["page_link"] . "/report-new-" . $type["page_link"];
                                $content_pagelink = "reports/" . $type["page_link"] . "/report-content-" . $type["page_link"];
                                ?>

                                <option value="<?php echo $type["id"] ?>" data-new="<?php echo $newpagelink ?>"
                                    data-view="<?php echo $content_pagelink ?>">
                                   
                                    <?php echo $type["reportName"] ?>
                                </option>

                            <?php } ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                        <button type="button" id="forwardtoreport" data-type="" class="btn btn-primary">Devam
                            Et</button>
                    </div>
                </div>
            </div>
        </div>



<div class="table-responsive">


        <table id="reportTable" class="data-table table-hover table-bordered text-nowrap" style="width: 100%;">
            <thead>
                <tr>

                    <th class="w-10 text-nowrap">ID</th>
                    <th class="w-10 text-nowrap">Rapor No</th>
                    <th>Firma</th>
                    <th>Rapor Türü</th>
                    <th>İş Emri No</th>
                    <th>Kontrol Tarihi</th>
                    <th>Geçerlilik Tarihi</th>
                    <th>İşlem</th>

                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

        </div>
    </div>
</div>

<style>
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

<script src="include/js/data-table.js"></script>
<script src="include/js/report.js"></script>
<script>
    $(document).ready(function () {
        if ($("#reportTable").length) {
            $("#reportTable").DataTable({
                processing: true,
                serverSide: true,
                stateSave: true,
                autoWidth: false,
                ajax: {
                    url: "api/reports_datatables.php",
                    type: "GET"
                },
                columns: [
                    { data: 0, className: "text-center" }, // ID
                    { data: 1, className: "text-center" }, // Rapor No
                    { data: 2 }, // Firma
                    { data: 3 }, // Rapor Türü
                    { data: 4 }, // İş Emri No
                    { data: 5 }, // Kontrol Tarihi
                    { data: 6 }, // Geçerlilik Tarihi
                    { data: 7, orderable: false, className: "text-center" } // İşlem
                ],
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    url: "include/js/tr.json",
                    processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Yükleniyor...</span>'
                },
                responsive: true,
                order: [[0, "desc"]],
                orderCellsTop: true,
                initComplete: function () {
                    if (window.App && window.App.TableFilter) {
                        App.TableFilter.attachToTable(this.api().table().node());
                    }
                }
            });
        }

        $(document).on("click", ".btn-report-detail", function () {
            var id = $(this).data("id");
            $.ajax({
                method: "POST",
                url: "pages/1/ajax.php?type=report-detail",
                dataType: "json",
                data: {
                    id: id
                },
                success: function (data) {
                    $("#reportdetail").modal("show");
                    $("#creator").text(data.creator);
                    $("#create_time").text(data.create_time);
                }
            });
        });

        // Tabloda Sağ Tık (Context Menu) İşlemleri
        $(document).on('contextmenu', '#reportTable tbody tr', function(e) {
            if ($(this).find('td').length <= 1) return;

            e.preventDefault();
            
            var $tr = $(this);
            $('#reportTable tbody tr').removeClass('context-menu-active');
            $tr.addClass('context-menu-active');

            var reportNo = $tr.find('td:nth-child(2)').text().trim() || 'Rapor İşlemleri';
            var $actionTd = $tr.find('td:last-child');
            
            var menuHtml = '<div class="cm-header"><i class="fa fa-file-text-o mr-1"></i> ' + $('<div>').text(reportNo).html() + '</div>';

            // 1. Düzenle Butonu Varsa
            var $editBtn = $actionTd.find('a[data-tooltip="Düzenle"], a.btn-outline-primary');
            if ($editBtn.length) {
                menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-primary mr-2"></i> Düzenle</a>';
            }

            // 2. Dropdown içindeki elemanlar (Raporu Göster, İmzasız Raporu Göster, Mail gönder, Detay Bilgisi vb.)
            var $dropdownItems = $actionTd.find('.dropdown-menu .dropdown-item');
            if ($dropdownItems.length) {
                $dropdownItems.each(function() {
                    var $item = $(this);
                    var href = $item.attr('href');
                    var isLink = href && href !== '#' && href !== 'javascript:void(0);';
                    var target = $item.attr('target') ? ' target="' + $item.attr('target') + '"' : '';
                    var text = $item.html();
                    var dataId = $item.attr('data-id') ? ' data-id="' + $item.attr('data-id') + '"' : '';
                    var classAttr = $item.attr('class') || '';

                    if (isLink) {
                        menuHtml += '<a href="' + href + '"' + target + dataId + ' class="' + classAttr + '">' + text + '</a>';
                    } else {
                        menuHtml += '<button type="button" class="' + classAttr + '"' + dataId + '>' + text + '</button>';
                    }
                });
            }

            // 3. Sil Butonu Varsa
            var $deleteBtn = $actionTd.find('button.btn-danger, a.btn-danger');
            if ($deleteBtn.length) {
                menuHtml += '<div class="cm-divider"></div>';
                var onClickAttr = $deleteBtn.attr('onclick') || $deleteBtn.attr('onClick') || '';
                menuHtml += '<button type="button" class="cm-danger" onclick="' + $('<div>').text(onClickAttr).html() + '; return false;"><i class="fa fa-trash text-danger mr-2"></i> Sil</button>';
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

        // Menü dışına tıklanınca veya kaydırılınca context menu kapat
        $(document).on('click scroll', function(e) {
            if (!$(e.target).closest('#customContextMenu').length) {
                $('#customContextMenu').hide();
                $('#reportTable tbody tr').removeClass('context-menu-active');
            }
        });

        // Menüdeki seçeneğe basılınca context menu kapat
        $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
            $('#customContextMenu').hide();
            $('#reportTable tbody tr').removeClass('context-menu-active');
        });

        // ESC basılınca kapat
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#customContextMenu').hide();
                $('#reportTable tbody tr').removeClass('context-menu-active');
            }
        });
    });
</script>