<?php

$cid = @$_GET["cid"]; //customer id
$sid = @$_GET["id"];
permcontrol("serviceView");


use App\Helper\Helper;

// Yetki kontrollerini döngü dışında yap
$canEdit = permtrue("serviceEdit");
$canDel = permtrue("serviceDel");

// Optimize edilmiş tek sorgu ile tüm verileri çek
if ($cid) {
    $query = $ac->prepare("
        SELECT p.*, 
               c.company as company_name, 
               r.title as region_name, 
               s.title as service_title, 
               u.username as creator_username,
               cs.title as contract_status_title,
               cs.colour as contract_status_color,
               st.title as status_title,
               st.colour as status_color
        FROM projects p
        LEFT JOIN customers c ON c.id = p.pcid
        LEFT JOIN units r ON r.id = p.region
        LEFT JOIN units s ON s.id = p.servicestype
        LEFT JOIN users u ON u.id = p.pcreativer
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
               r.title as region_name, 
               s.title as service_title, 
               u.username as creator_username,
               cs.title as contract_status_title,
               cs.colour as contract_status_color,
               st.title as status_title,
               st.colour as status_color
        FROM projects p
        LEFT JOIN customers c ON c.id = p.pcid
        LEFT JOIN units r ON r.id = p.region
        LEFT JOIN units s ON s.id = p.servicestype
        LEFT JOIN users u ON u.id = p.pcreativer
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
               r.title as region_name, 
               s.title as service_title, 
               u.username as creator_username,
               cs.title as contract_status_title,
               cs.colour as contract_status_color,
               st.title as status_title,
               st.colour as status_color
        FROM projects p
        LEFT JOIN customers c ON c.id = p.pcid
        LEFT JOIN units r ON r.id = p.region
        LEFT JOIN units s ON s.id = p.servicestype
        LEFT JOIN users u ON u.id = p.pcreativer
        LEFT JOIN units cs ON cs.id = p.contract_statu AND cs.statu = 4
        LEFT JOIN units st ON st.id = p.pstatu AND st.statu = 4
        ORDER BY p.id desc
    ");
    $query->execute();
}

$projects = $query->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h5 class="text-blue">Oluşturulan Tüm Servislerq</h5>
        </div>
        <div class="float-right">
            <?php if (permtrue("serviceAdd")) { ?>
                <a href="index.php?p=service-new" class="btn btn-sm btn-primary"><i class="fa fa-save"></i> Yeni Giriş
                    Yap</a>

            <?php } ?>
        </div>

    </div>
    <div class="search-input-area d-flex"></div>
    <table class="data-table table-hover table-bordered table-responsive-sm" id="service-table">
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
                <th class="text-nowrap">İşlem</th>

            </tr>
        </thead>
        <tbody>
            <?php

            if ($cid) {
                $query = $ac->prepare("SELECT * FROM projects WHERE pcid = ?  ORDER BY id desc");
                $query->execute(array($cid));
            } else if ($sid) {
                $query = $ac->prepare("SELECT * FROM projects WHERE id = ?  ORDER BY id desc");
                $query->execute(array($sid));
            } else {
                $query = $ac->prepare("SELECT * FROM projects ORDER BY id desc");
                $query->execute();
            }
            $sirano = 1;
            foreach ($projects as $purc) {
                ?>
                <tr>
                    <td class="text-center"><?php echo $sirano ?></td>
                    <td>
                        <?php echo $purc["service_number"]; ?>
                    </td>

                    <?php
                    // Firma adı zaten sorguda geldi 
                
                    $pid = $purc["id"];

                    ?>
                    <td data-tooltip="<?php echo $purc['company_name']; ?>">
                        <?php echo shorted($purc['company_name'], 40); ?>
                    </td>
                    <td>
                        <?php echo $purc['region_name'] ?>
                    </td>


                    <td>
                        <?php
                        // Servis konusu zaten sorguda geldi 
                    



                        $pid = $purc["id"];

                        echo $purc['service_title']; ?>
                    </td>
                    <td>
                        <?php echo $purc["pregdate"] ?>
                    </td>
                    <td> <?php
                    echo ($purc["pstart_date"]);
                    ?>

                    </td>
                    <td class="text-center">
                        <?php
                        $color = (!empty($purc['contract_status_color'])) ? $purc['contract_status_color'] : '#777';
                        $title = $purc['contract_status_title'] ?? '';
                        echo "<span class='badge' style='background-color:{$color}'>{$title}</span>";
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        $color = (!empty($purc['status_color'])) ? $purc['status_color'] : '#777';
                        $title = $purc['status_title'] ?? '';
                        echo "<span class='badge' style='background-color:{$color}'>{$title}</span>";
                        ?>
                    </td>
                    <td>
                        <?php echo $purc['creator_username']; ?>
                    </td>

                    <td style="width:10%; white-space: nowrap;">

                        <?php if ($canEdit) { ?>
                            <a type="button" href="index.php?p=service/manage&id=<?php echo $pid; ?>"
                                class="btn btn-sm btn-outline-info" data-tooltip="Düzenle"><i class="fa fa-pencil"></i></a>

                        <?php }
                        if ($canDel) { ?>
                            <button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil"
                                onClick="deleteRecord('<?php echo $purc["id"]; ?> nolu Servisi silmek istediğinize emin misiniz?','<?php echo $pid; ?>','services','projects')"><i
                                    class="fa fa-trash"></i></button>

                        <?php } ?>
                        <a type="button" href="index.php?p=service-view&id=<?php echo encrypt($pid) ?>" target="_blank"
                            class="btn btn-sm btn-secondary" data-tooltip="Detay"><i class="fa fa-info-circle"></i></a>
                    </td>
                </tr>
                <?php
                $sirano += 1;
            } ?>
        </tbody>
    </table>
</div>
<script src="include/js/data-table.js"></script>