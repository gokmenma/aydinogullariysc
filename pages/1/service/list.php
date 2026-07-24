<?php

$cid = @$_GET["cid"]; //customer id
$sid = @$_GET["id"];
permcontrol("serviceView");


use App\Helper\Helper;
use App\Model\UnitsModel;
use App\Model\ServiceModel;

$Services = new ServiceModel();
$Units = new UnitsModel();

$bekleyen_id = $Units->getUnitId("Bekliyor")->id;
$calisilan_id = $Units->getUnitId("Çalışıyor")->id;
$tamamlanan_id = $Units->getUnitId("Tamamlandı")->id;
$iptal_id = $Units->getUnitId("İptal Edildi")->id;

$bekleyen_servis_sayisi = $Services->getServiceCount($bekleyen_id)->count;
$calisilan_servis_sayisi = $Services->getServiceCount($calisilan_id)->count;
$tamamlanan_servis_sayisi = $Services->getServiceCount($tamamlanan_id)->count;
$iptal_servis_sayisi = $Services->getServiceCount($iptal_id)->count;

// Yetki kontrollerini döngü dışında yap
$canEdit = permtrue("serviceEdit");
$canDel = permtrue("serviceDel");
$canAccountingReceipt = permtrue("muhasebe_teslim_alma_yetkisi");

try {
    $ac->exec("CREATE TABLE IF NOT EXISTS service_accounting_receipt_logs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id INT UNSIGNED NOT NULL,
        action VARCHAR(20) NOT NULL,
        action_by INT UNSIGNED NOT NULL,
        action_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_service_id (service_id),
        KEY idx_action_at (action_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Tablo oluşturulamasa da liste ekranı çalışmaya devam etsin.
}

// Optimize edilmiş tek sorgu ile tüm verileri çek
if ($cid) {
    $query = $ac->prepare("
        SELECT p.*,
               c.company as company_name,
               c.deleted_at as customer_deleted_at,
               r.title as region_name,
               s.title as service_title, 
               u.username as creator_username,
             uu.username as updater_username,
             ar.action as accounting_action,
             ar.action_at as accounting_action_at,
             au.username as accounting_actor_username,
               cs.title as contract_status_title,
               cs.colour as contract_status_color,
               st.title as status_title,
               st.colour as status_color
        FROM projects p
        LEFT JOIN customers c ON c.id = p.pcid
        LEFT JOIN units r ON r.id = p.region
        LEFT JOIN units s ON s.id = p.servicestype
        LEFT JOIN users u ON u.id = p.pcreativer
        LEFT JOIN users uu ON uu.id = p.updater
        LEFT JOIN (
            SELECT l.service_id, l.action, l.action_by, l.action_at
            FROM service_accounting_receipt_logs l
            INNER JOIN (
                SELECT service_id, MAX(id) as max_id
                FROM service_accounting_receipt_logs
                GROUP BY service_id
            ) lm ON lm.max_id = l.id
        ) ar ON ar.service_id = p.id
        LEFT JOIN users au ON au.id = ar.action_by
        LEFT JOIN units cs ON cs.id = p.contract_statu AND cs.statu = 4
        LEFT JOIN units st ON st.id = p.pstatu AND st.statu = 4
        WHERE p.pcid = ? 
        ORDER BY p.id desc
    ");
    $query->execute(array($cid));
} else if ($sid) {
    $query = $ac->prepare("
        SELECT p.*,
               c.company as company_name,
               c.deleted_at as customer_deleted_at,
               r.title as region_name,
               s.title as service_title, 
               u.username as creator_username,
             uu.username as updater_username,
             ar.action as accounting_action,
             ar.action_at as accounting_action_at,
             au.username as accounting_actor_username,
               cs.title as contract_status_title,
               cs.colour as contract_status_color,
               st.title as status_title,
               st.colour as status_color
        FROM projects p
        LEFT JOIN customers c ON c.id = p.pcid
        LEFT JOIN units r ON r.id = p.region
        LEFT JOIN units s ON s.id = p.servicestype
        LEFT JOIN users u ON u.id = p.pcreativer
        LEFT JOIN users uu ON uu.id = p.updater
        LEFT JOIN (
            SELECT l.service_id, l.action, l.action_by, l.action_at
            FROM service_accounting_receipt_logs l
            INNER JOIN (
                SELECT service_id, MAX(id) as max_id
                FROM service_accounting_receipt_logs
                GROUP BY service_id
            ) lm ON lm.max_id = l.id
        ) ar ON ar.service_id = p.id
        LEFT JOIN users au ON au.id = ar.action_by
        LEFT JOIN units cs ON cs.id = p.contract_statu AND cs.statu = 4
        LEFT JOIN units st ON st.id = p.pstatu AND st.statu = 4
        WHERE p.id = ? 
        ORDER BY p.id desc
    ");
    $query->execute(array($sid));
} else {
    $query = $ac->prepare("
        SELECT p.*,
               c.company as company_name,
               c.deleted_at as customer_deleted_at,
               r.title as region_name,
               s.title as service_title, 
               u.username as creator_username,
             uu.username as updater_username,
             ar.action as accounting_action,
             ar.action_at as accounting_action_at,
             au.username as accounting_actor_username,
               cs.title as contract_status_title,
               cs.colour as contract_status_color,
               st.title as status_title,
               st.colour as status_color
        FROM projects p
        LEFT JOIN customers c ON c.id = p.pcid
        LEFT JOIN units r ON r.id = p.region
        LEFT JOIN units s ON s.id = p.servicestype
        LEFT JOIN users u ON u.id = p.pcreativer
        LEFT JOIN users uu ON uu.id = p.updater
        LEFT JOIN (
            SELECT l.service_id, l.action, l.action_by, l.action_at
            FROM service_accounting_receipt_logs l
            INNER JOIN (
                SELECT service_id, MAX(id) as max_id
                FROM service_accounting_receipt_logs
                GROUP BY service_id
            ) lm ON lm.max_id = l.id
        ) ar ON ar.service_id = p.id
        LEFT JOIN users au ON au.id = ar.action_by
        LEFT JOIN units cs ON cs.id = p.contract_statu AND cs.statu = 4
        LEFT JOIN units st ON st.id = p.pstatu AND st.statu = 4
        ORDER BY p.id desc
    ");
    $query->execute();
}

$projects = $query->fetchAll(PDO::FETCH_ASSOC);



// Server-side processing için gerekli değişkenleri tanımla
$use_server_side = true; // Server-side processing aktif
$ajax_url = "api/services_datatables.php";

// Eğer spesifik bir müşteri veya servis ID'si varsa, server-side processing'i devre dışı bırak
if ($cid || $sid) {
    $use_server_side = false;
}

?>
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

    .service-summary-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        margin-right: -6px;
        margin-left: -6px;
        margin-bottom: 20px !important;
    }

    .service-summary-grid > [class*="col-"] {
        display: flex;
        padding-right: 6px;
        padding-left: 6px;
    }

    .minimal-summary-card {
        width: 100%;
        height: 100%;
        min-height: 64px;
        padding: 10px 14px;
        border: 1px solid #e8edf3;
        border-left: 4px solid #ccc;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
        display: flex;
        align-items: center;
        box-sizing: border-box;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .minimal-summary-card .card-inner {
        display: flex;
        width: 100%;
        justify-content: space-between;
        align-items: center;
    }

    .minimal-summary-card:hover {
        transform: none;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }

    .minimal-summary-card .summary-title {
        margin-bottom: 2px !important;
        font-size: 11.5px !important;
        font-weight: 600;
        color: #64748b !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        line-height: 1.2;
    }

    .minimal-summary-card .summary-number {
        font-size: 20px !important;
        font-weight: 800 !important;
        line-height: 1;
    }

    .minimal-summary-card .icon {
        width: 34px;
        height: 34px;
        min-width: 34px;
        border-radius: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: none !important;
    }

    .minimal-summary-card:hover .icon {
        transform: none;
    }

    .minimal-summary-card.card-yellow { border-left-color: #f7b500; }
    .minimal-summary-card.card-blue { border-left-color: #1f8ef1; }
    .minimal-summary-card.card-green { border-left-color: #20a144; }
    .minimal-summary-card.card-red { border-left-color: #dc3545; }

    @media (max-width: 991.98px) {
        .service-summary-grid > [class*="col-"] {
            margin-bottom: 10px;
        }
    }
</style>

<div class="row service-summary-grid">
    <!-- Bekleyen Servisler -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="dashboard-card minimal-summary-card card-yellow">
            <div class="card-inner">
                <div>
                    <span class="d-block text-muted summary-title">Bekleyen Servis Sayısı</span>
                    <span class="no text-warning summary-number">
                        <?php echo $bekleyen_servis_sayisi; ?>
                    </span>
                </div>
                <div class="icon bg-warning text-white">
                    <i class="fa fa-hourglass-o"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Çalışılan Servisler -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="dashboard-card minimal-summary-card card-blue">
            <div class="card-inner">
                <div>
                    <span class="d-block text-muted summary-title">Çalışılan Servis Sayısı</span>
                    <span class="no text-blue summary-number">
                        <?php echo $calisilan_servis_sayisi; ?>
                    </span>
                </div>
                <div class="icon bg-blue text-white">
                    <i class="fa fa-wrench"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tamamlanan Servisler -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="dashboard-card minimal-summary-card card-green">
            <div class="card-inner">
                <div>
                    <span class="d-block text-muted summary-title">Tamamlanan Servis Sayısı</span>
                    <span class="no text-success summary-number">
                        <?php echo $tamamlanan_servis_sayisi; ?>
                    </span>
                </div>
                <div class="icon bg-success text-white">
                    <i class="fa fa-check"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İptal Edilen Servisler -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="dashboard-card minimal-summary-card card-red">
            <div class="card-inner">
                <div>
                    <span class="d-block text-muted summary-title">İptal Edilen Servis Sayısı</span>
                    <span class="no text-danger summary-number">
                        <?php echo $iptal_servis_sayisi; ?>
                    </span>
                </div>
                <div class="icon bg-danger text-white">
                    <i class="fa fa-close"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-white premium-section-card box-shadow mb-30 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-20" style="flex-wrap: wrap; gap: 10px;">
        <div>
            <h5 class="weight-600 mb-0">Oluşturulan Tüm Servisler</h5>
        </div>
        <div>
            <!-- Excele Aktar -->
            <?php if (permtrue("data_export_service")) { ?>
                <a href="#" class="btn btn-outline-success btn-sm mr-2" id="exportExcel">
                    <i class="fa fa-file-excel-o mr-1"></i> Excel'e Aktar
                </a>
            <?php } ?>
            <?php if (permtrue("serviceAdd")) { ?>
                <a href="index.php?p=service/manage" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus-circle mr-1"></i> Yeni Servis Oluştur
                </a>
            <?php } ?>
        </div>
    </div>
    <div class="search-input-area d-flex"></div>
    <div class="table-responsive">
        <table class="data-table table-hover table-bordered" id="service-table" style="width:100%">
            <thead>
                <tr>
                    <th scope="col">Sıra No</th>
                    <th scope="col">Servis No</th>
                    <th>Firma Adı</th>
                    <th>Bölge</th>
                    <th>Servis Konusu </th>
                    <th>İş Emri Oluşturma Tarihi</th>
                    <th>Servis Planlama Tarihi</th>
                    <th>Sözleşme Durum</th>
                    <th>Durum</th>
                    <th>İş Emrini Oluşturan</th>
                    <th>Son İşlem Yapan</th>
                    <th>Muhasebe Teslim</th>
                    <th>İşlemler</th>

                </tr>
            </thead>
            <tbody>
                <?php if (!$use_server_side): ?>
                    <?php $sirano = 1;
                    foreach ($projects as $purc) {
                        $pid = $purc["id"]; ?>
                        <tr>
                            <td class="text-center"><?php echo $sirano; ?></td>
                            <td><?php echo $purc["service_number"]; ?></td>
                            <td data-tooltip="<?php echo $purc['company_name']; ?>">
                                <?php if (!empty($purc['customer_deleted_at'])): ?>
                                    <span class="text-muted">
                                        <?php echo htmlspecialchars(shorted($purc['company_name'], 40)); ?>
                                        <small class="badge badge-secondary">Silinmiş</small>
                                    </span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars(shorted($purc['company_name'], 40)); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $purc['region_name']; ?></td>
                            <td><?php echo $purc['service_title']; ?></td>
                            <td><?php echo $purc["pregdate"]; ?></td>
                            <td><?php echo $purc["pstart_date"]; ?></td>
                            <td class="text-center">
                                <?php $color = (!empty($purc['contract_status_color'])) ? $purc['contract_status_color'] : '#777';
                                $title = $purc['contract_status_title'] ?? '';
                                echo "<span class='badge' style='background-color:{$color}'>{$title}</span>"; ?>
                            </td>
                            <td class="text-center">
                                <?php $color = (!empty($purc['status_color'])) ? $purc['status_color'] : '#777';
                                $title = $purc['status_title'] ?? '';
                                echo "<span class='badge' style='background-color:{$color}'>{$title}</span>"; ?>
                            </td>
                            <td><?php echo $purc['creator_username']; ?></td>
                            <td><?php echo $purc['updater_username'] ?: $purc['creator_username']; ?></td>
                            <td class="text-center">
                                <?php
                                $isAccountingReceived = ($purc['accounting_action'] ?? '') === 'received';
                                $accLabel = $isAccountingReceived ? 'Teslim Alındı' : 'Teslim Bekliyor';
                                $accClass = $isAccountingReceived ? 'badge-success' : 'badge-warning';
                                echo "<span class='badge {$accClass}'>{$accLabel}</span>";

                                if (!empty($purc['accounting_actor_username']) && !empty($purc['accounting_action_at'])) {
                                    echo '<div class="small text-muted">' . htmlspecialchars($purc['accounting_actor_username']) . ' - ' . htmlspecialchars($purc['accounting_action_at']) . '</div>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="text-nowrap d-inline-flex align-items-center" style="flex-wrap:nowrap; gap:4px">

                                    <?php if ($canEdit): ?>
                                        <a type="button" href="index.php?p=service/manage&id=<?php echo $pid; ?>"
                                            class="btn btn-sm btn-outline-info" data-tooltip="Düzenle"><i
                                                class="fa fa-pencil"></i></a>
                                    <?php endif;
                                    if ($canDel): ?>
                                        <button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil"
                                            onClick="deleteRecord('<?php echo $purc["id"]; ?> nolu Servisi silmek istediğinize emin misiniz?','<?php echo $pid; ?>','services','projects')"><i
                                                class="fa fa-trash"></i></button>
                                    <?php endif; ?>
                                    <a type="button" href="index.php?p=service-view&id=<?php echo encrypt($pid) ?>"
                                        target="_blank" class="btn btn-sm btn-secondary" data-tooltip="Detay"><i
                                            class="fa fa-info-circle"></i></a>
                                    <?php if ($canAccountingReceipt): ?>
                                        <?php
                                        $btnClass = $isAccountingReceived ? 'btn-outline-danger' : 'btn-outline-success';
                                        $btnText = $isAccountingReceived ? 'İade Al' : 'Teslim Al';
                                        $confirmText = $isAccountingReceived
                                            ? 'Bu servis için muhasebe teslim kaydını iade almak istediğinize emin misiniz?'
                                            : 'Bu servisi muhasebe teslim alındı olarak işaretlemek istediğinize emin misiniz?';
                                        ?>
                                        <button type="button" class="btn btn-sm <?php echo $btnClass; ?> js-accounting-receipt-toggle"
                                            data-service-id="<?php echo (int) $pid; ?>"
                                            data-confirm="<?php echo htmlspecialchars($confirmText); ?>"><?php echo $btnText; ?></button>
                                        <button type="button" class="btn btn-sm btn-dark js-accounting-log"
                                            data-service-id="<?php echo (int) $pid; ?>" data-service-number="<?php echo htmlspecialchars($purc['service_number']); ?>"
                                            data-tooltip="Muhasebe Teslim Log"><i class="fa fa-history"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php $sirano++;
                    } ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="accountingReceiptLogModal" tabindex="-1" role="dialog" aria-labelledby="accountingReceiptLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountingReceiptLogModalLabel">Muhasebe Teslim Logları</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" id="accountingReceiptLogTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>İşlem</th>
                                <th>Yapan</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Kayıt bulunamadı.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        var useServerSide = <?php echo $use_server_side ? 'true' : 'false'; ?>;
        
        var dtOptions = {
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                url: 'include/js/tr.json',
                processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Yükleniyor...</span>'
            },
            responsive: true,
            order: [
                [0, 'desc']
            ],
            orderCellsTop: true,
            initComplete: function () {
                var api = this.api();
                var tableId = api.table().node().id;
                // Arama satırını <thead> içine ekle
                $("#" + tableId + " thead").append('<tr class="search-input-row"></tr>');

                api.columns().every(function (index) { // Sütun index'ini al
                    let column = this;
                    let header = $(column.header());
                    let title = header.text();

                    // İşlem ve checkbox olmayan sütunlar için input oluştur
                    if (header.find('input[type="checkbox"]').length === 0 && column.visible() && title && title.trim() !== 'İşlem' && title.trim() !== 'İşlemler') {

                        let input = $('<input type="text" class="form-control form-control-sm" placeholder="' + title + '" autocomplete="off">')
                            .appendTo($('<th class="search"></th>').appendTo("#" + tableId + " .search-input-row"))
                            .on('keyup change clear', function () {
                                // === ANAHTAR DEĞİŞİKLİK BURADA ===
                                // Eğer sütunun arama değeri bu input'un değeriyle aynı değilse,
                                // yeni değeri ata ve tabloyu yeniden çiz
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    } else {
                        // Diğer sütunlar için boş bir <th> ekle
                        $("#" + tableId + " .search-input-row").append('<th></th>');
                    }
                });
            }
        }            dtOptions.ajax = {
                url: '<?php echo $ajax_url; ?>',
                type: 'GET'
            };
            dtOptions.columns = [{
                data: null,
                orderable: false,
                className: 'text-center',
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 1
            }, // service_number
            {
                data: 2
            }, // company_name
            {
                data: 3
            }, // region_name
            {
                data: 4
            }, // service_title
            {
                data: 5
            }, // pregdate
            {
                data: 6
            }, // pstart_date
            {
                data: 7
            }, // contract_status
            {
                data: 8
            }, // status
            {
                data: 9
            }, // creator_username
            {
                data: 10
            }, // updater_username
            {
                data: 11
            }, // accounting status
            {
                data: 12,
                orderable: false,
                className: 'all text-nowrap',
                responsivePriority: 1
            } // actions
            ];
        }

        $('#service-table').DataTable(dtOptions);
    });
</script>

<div class="modal fade" id="accountingReceiptLogModal" tabindex="-1" role="dialog" aria-labelledby="accountingReceiptLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountingReceiptLogModalLabel">Muhasebe Teslim Logları</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" id="accountingReceiptLogTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>İşlem</th>
                                <th>Yapan</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Kayıt bulunamadı.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        var useServerSide = <?php echo $use_server_side ? 'true' : 'false'; ?>;
        
        var dtOptions = {
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                url: 'include/js/tr.json',
                processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Yükleniyor...</span>'
            },
            responsive: true,
            order: [
                [0, 'desc']
            ],
            orderCellsTop: true,
            initComplete: function () {
                var api = this.api();
                var tableId = api.table().node().id;
                // Arama satırını <thead> içine ekle
                $("#" + tableId + " thead").append('<tr class="search-input-row"></tr>');

                api.columns().every(function (index) { // Sütun index'ini al
                    let column = this;
                    let header = $(column.header());
                    let title = header.text();

                    // İşlem ve checkbox olmayan sütunlar için input oluştur
                    if (header.find('input[type="checkbox"]').length === 0 && column.visible() && title && title.trim() !== 'İşlem' && title.trim() !== 'İşlemler') {

                        let input = $('<input type="text" class="form-control form-control-sm" placeholder="' + title + '" autocomplete="off">')
                            .appendTo($('<th class="search"></th>').appendTo("#" + tableId + " .search-input-row"))
                            .on('keyup change clear', function () {
                                // === ANAHTAR DEĞİŞİKLİK BURADA ===
                                // Eğer sütunun arama değeri bu input'un değeriyle aynı değilse,
                                // yeni değeri ata ve tabloyu yeniden çiz
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    } else {
                        // Diğer sütunlar için boş bir <th> ekle
                        $("#" + tableId + " .search-input-row").append('<th></th>');
                    }
                });
            }
        };

        if (useServerSide) {
            dtOptions.processing = true;
            dtOptions.serverSide = true;
            dtOptions.ajax = {
                url: '<?php echo $ajax_url; ?>',
                type: 'GET'
            };
            dtOptions.columns = [{
                data: null,
                orderable: false,
                className: 'text-center',
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 1
            }, // service_number
            {
                data: 2
            }, // company_name
            {
                data: 3
            }, // region_name
            {
                data: 4
            }, // service_title
            {
                data: 5
            }, // pregdate
            {
                data: 6
            }, // pstart_date
            {
                data: 7
            }, // contract_status
            {
                data: 8
            }, // status
            {
                data: 9
            }, // creator_username
            {
                data: 10
            }, // updater_username
            {
                data: 11
            }, // accounting status
            {
                data: 12,
                orderable: false,
                className: 'all text-nowrap',
                responsivePriority: 1
            } // actions
            ];
        }

        $('#service-table').DataTable(dtOptions);
    });
</script>

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
        function showSwal(options) {
            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                return Swal.fire(options);
            }
            if (typeof swal !== 'undefined' && typeof swal.fire === 'function') {
                return swal.fire(options);
            }
            return null;
        }

        function showSimpleMessage(icon, title, text) {
            var instance = showSwal({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'Tamam'
            });

            if (!instance) {
                window.alert(text || title);
            }
        }

        var hasDT = $.fn.DataTable && $.fn.DataTable.isDataTable('#service-table');
        var t = hasDT ? $('#service-table').DataTable() : null;
        $('#exportExcel').off('click').on('click', function (e) {
            e.preventDefault();
            showExportLoadingNotification();
            var params = {};
            if (hasDT) {
                var order = t.order();
                if (order && order.length) {
                    params['order[0][column]'] = order[0][0];
                    params['order[0][dir]'] = order[0][1];
                }
                var gs = t.search();
                if (gs) params['search[value]'] = gs;
                t.columns().every(function (index) {
                    var v = this.search();
                    if (v) params['columns[' + index + '][search][value]'] = v;
                });
            }
            <?php if ($cid) { ?> params['cid'] = '<?php echo $cid; ?>'; <?php } ?>
            <?php if ($sid) { ?> params['sid'] = '<?php echo $sid; ?>'; <?php } ?>
            var qs = $.param(params);
            window.location = 'api/services_export.php' + (qs ? ('?' + qs) : '');
        });

        $(document).on('click', '.js-accounting-receipt-toggle', function () {
            var $btn = $(this);
            var serviceId = parseInt($btn.data('service-id'), 10);
            var confirmText = $btn.data('confirm') || 'Bu işlemi yapmak istediğinize emin misiniz?';

            if (!serviceId) {
                return;
            }

            var swalConfirm = showSwal({
                icon: 'warning',
                title: 'Emin misiniz?',
                text: confirmText,
                showCancelButton: true,
                confirmButtonText: 'Evet',
                cancelButtonText: 'Vazgeç'
            });

            var proceed = function () {
                $btn.prop('disabled', true);

                $.ajax({
                    url: 'api/services_datatables.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'toggle_accounting_receipt',
                        service_id: serviceId
                    }
                }).done(function (response) {
                    if (response && response.success) {
                        showSimpleMessage('success', 'Başarılı', response.message || 'İşlem tamamlandı.');
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#service-table')) {
                            $('#service-table').DataTable().ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        showSimpleMessage('error', 'Hata', (response && response.message) ? response.message : 'İşlem başarısız oldu.');
                    }
                }).fail(function () {
                    showSimpleMessage('error', 'Hata', 'İşlem sırasında bir hata oluştu.');
                }).always(function () {
                    $btn.prop('disabled', false);
                });
            };

            if (swalConfirm && typeof swalConfirm.then === 'function') {
                swalConfirm.then(function (result) {
                    if (result && (result.isConfirmed || result.value === true)) {
                        proceed();
                    }
                });
            } else if (window.confirm(confirmText)) {
                proceed();
            }
        });

        $(document).on('click', '.js-accounting-log', function () {
            var serviceId = parseInt($(this).data('service-id'), 10);
            var serviceNumber = $(this).data('service-number') || '';

            if (!serviceId) {
                return;
            }

            $('#accountingReceiptLogModalLabel').text('Muhasebe Teslim Logları - Servis No: ' + serviceNumber);
            $('#accountingReceiptLogTable tbody').html('<tr><td colspan="4" class="text-center">Yükleniyor...</td></tr>');
            $('#accountingReceiptLogModal').modal('show');

            $.ajax({
                url: 'api/services_datatables.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_accounting_receipt_logs',
                    service_id: serviceId
                }
            }).done(function (response) {
                if (!response || !response.success) {
                    $('#accountingReceiptLogTable tbody').html('<tr><td colspan="4" class="text-center text-danger">Loglar alınamadı.</td></tr>');
                    return;
                }

                var logs = response.logs || [];
                if (!logs.length) {
                    $('#accountingReceiptLogTable tbody').html('<tr><td colspan="4" class="text-center text-muted">Kayıt bulunamadı.</td></tr>');
                    return;
                }

                var html = '';
                for (var i = 0; i < logs.length; i++) {
                    var log = logs[i];
                    var actionText = log.action === 'received' ? 'Teslim Alındı' : 'İade Alındı';
                    html += '<tr>' +
                        '<td>' + (i + 1) + '</td>' +
                        '<td>' + actionText + '</td>' +
                        '<td>' + (log.action_by_name || '-') + '</td>' +
                        '<td>' + (log.action_at || '-') + '</td>' +
                        '</tr>';
                }
                $('#accountingReceiptLogTable tbody').html(html);
            }).fail(function () {
                $('#accountingReceiptLogTable tbody').html('<tr><td colspan="4" class="text-center text-danger">Loglar alınırken hata oluştu.</td></tr>');
            });
        });

        // Servisler Tablosunda Sağ Tık (Context Menu) İşlemleri
        $(document).on('contextmenu', '#service-table tbody tr', function(e) {
            if ($(this).find('td').length <= 1) return;

            e.preventDefault();
            
            var $tr = $(this);
            $('#service-table tbody tr').removeClass('context-menu-active');
            $tr.addClass('context-menu-active');

            var serviceNo = $tr.find('td:nth-child(2)').text().trim() || 'Servis İşlemleri';
            var $actionTd = $tr.find('td:last-child');
            
            var menuHtml = '<div class="cm-header"><i class="fa fa-wrench mr-1"></i> ' + $('<div>').text(serviceNo).html() + '</div>';

            // 1. Düzenle Butonu Varsa
            var $editBtn = $actionTd.find('a[data-tooltip="Düzenle"], a.btn-outline-info');
            if ($editBtn.length) {
                menuHtml += '<a href="' + $editBtn.attr('href') + '"><i class="fa fa-pencil text-info mr-2"></i> Düzenle</a>';
            }

            // 2. Detay / Görüntüle Butonu Varsa
            var $detailBtn = $actionTd.find('a[data-tooltip="Detay"], a.btn-secondary');
            if ($detailBtn.length) {
                menuHtml += '<a href="' + $detailBtn.attr('href') + '" target="_blank"><i class="fa fa-info-circle text-secondary mr-2"></i> Detay Bilgisi</a>';
            }

            // 3. Teslim Al / İade Al Butonu Varsa
            var $accToggle = $actionTd.find('.js-accounting-receipt-toggle');
            if ($accToggle.length) {
                var serviceId = $accToggle.data('service-id');
                var confirmMsg = $accToggle.attr('data-confirm') || '';
                var btnText = $accToggle.text().trim();
                var iconClass = btnText.indexOf('İade') !== -1 ? 'fa-undo text-warning' : 'fa-check text-success';
                var classNames = $accToggle.attr('class') || '';

                menuHtml += '<button type="button" class="' + classNames + '" data-service-id="' + serviceId + '" data-confirm="' + $('<div>').text(confirmMsg).html() + '"><i class="fa ' + iconClass + ' mr-2"></i> ' + $('<div>').text(btnText).html() + '</button>';
            }

            // 4. Muhasebe Teslim Log Butonu Varsa
            var $accLog = $actionTd.find('.js-accounting-log');
            if ($accLog.length) {
                var logServiceId = $accLog.data('service-id');
                var logServiceNum = $accLog.attr('data-service-number') || '';
                menuHtml += '<button type="button" class="js-accounting-log" data-service-id="' + logServiceId + '" data-service-number="' + $('<div>').text(logServiceNum).html() + '"><i class="fa fa-history text-dark mr-2"></i> Muhasebe Logları</button>';
            }

            // 5. Sil Butonu Varsa
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

        // Menü dışına tıklanınca veya kaydırılınca kapat
        $(document).on('click scroll', function(e) {
            if (!$(e.target).closest('#customContextMenu').length) {
                $('#customContextMenu').hide();
                $('#service-table tbody tr').removeClass('context-menu-active');
            }
        });

        // Menüdeki seçeneğe basılınca kapat
        $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
            $('#customContextMenu').hide();
            $('#service-table tbody tr').removeClass('context-menu-active');
        });

        // ESC basılınca kapat
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#customContextMenu').hide();
                $('#service-table tbody tr').removeClass('context-menu-active');
            }
        });
    });
</script>
