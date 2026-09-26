<?php

// permcontrol("reportcontentsview");
$type=$_GET["type"];
?>
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h5 class="text-blue">YSC rapor İçerik Listesi</h5>
            <p class="font-14"> </p>
        </div>
        <div class="float-right">
            <a href="index.php?p=reports/reports" class="btn btn-sm btn-secondary"><i class="fa fa-list"></i> Listeye
                Dön</a>
        </div>
    </div>

    <table id="reportContent" class="data-table table-hover table-bordered table-responsive">
        <thead>
            <tr>

                <th class="w-10 text-nowrap">id</th>
                <th class="w-10 text-nowrap">Cihaz No</th>
                <th>Rapor Türü</th>
                <th>Bulunduğu Bölge</th>
                <th>Cihaz Dolum Tarihi</th>
                <th>Cihaz Son Kullanma Tarihi</th>
                <th>Firma</th>


            </tr>
        </thead>
        <tbody>
            <?php

            $query = $ac->prepare("SELECT * FROM report_ysc_content");
            $query->execute(array());

            $typequery = $ac->prepare("SELECT * FROM report_types WHERE id = ?");
            $typequery->execute(array($type));
            $report_type= $typequery->fetch(PDO::FETCH_ASSOC);


            $sirano = 1;
            while ($content = $query->fetch(PDO::FETCH_ASSOC)) {
                $custquery =$ac->prepare("SELECT c.company FROM report_ysc_content ryc 
                                            LEFT JOIN reports r on r.id= ryc.report_id 
                                            LEFT JOIN customers c on c.id=r.customer_id 
                                            WHERE ryc.report_id = ?");
                $custquery->execute(array($content["report_id"],));
                $customer=$custquery->fetch(PDO::FETCH_ASSOC);


                ?>
                <tr>
                 <!-- id -->
                 <td class="text-center">
                        <?php echo $content["id"]; ?>
                    </td>
                    
                <!-- Sıra No -->
                    <td class="text-center">
                        <?php echo $content["cihaz_no"]; ?>
                    </td>

                    <!-- Rapor Türü -->
                    <td class="table-plus ">
                    <?php echo $report_type["deviceType"]; ?>
                    </td>

                    <!-- is Emri No -->
                    <td class="table-plus ">
                        <?php echo $content["bulundugu_bolge"]; ?>
                    </td>

                    <!-- Geçerlilik Tarihi -->
                    <td class="table-plus ">
                        <?php echo $content["cihaz_dolum_tarihi"]; ?>
                    </td>

                    <!-- Firma -->
                    <td class="table-plus ">
                        <?php echo $content["cihaz_sonkullanma_tarihi"]; ?>
                    </td>

                    <td class="table-plus ">
                    <?php echo $customer["company"]; ?>
                    </td>
                <?php } ?>

            </tr>


        </tbody>
        <tfoot>
            <tr>

                <th class="w-10 text-nowrap">id</th>
                <th class="w-10 text-nowrap">Cihaz No</th>
                <th>Rapor Türü</th>
                <th>Bulunduğu Bölge</th>
                <th>Cihaz Dolum Tarihi</th>
                <th>Cihaz Son Kullanma Tarihi</th>
                <th>Firma</th>


            </tr>
        </tfoot>
    </table>
</div>
<script src="include/js/data-table.js"></script>