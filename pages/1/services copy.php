<?php

$cid = @$_GET["cid"]; //customer id
$sid = @$_GET["id"];
permcontrol("serviceView");


use App\Helper\Helper;
use App\Helper\Security;
use App\Helper\Date;
use App\Model\ServiceModel;
use App\Model\UnitsModel;


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


?>


<style>
  
</style>
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-10">
    <div class="clearfix mb-10">

        <!-- Özet bilgiler -->
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-12 mb-10">
                <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                    <div class="project-info">
                        <div class="project-info-left">
                            <div class="icon box-shadow bg-yellow text-white">
                            <i class="fa fa-hourglass-o"></i>
                            </div>
                        </div>
                        <div class="project-info-right">
                            <span class="no text-blue weight-500 font-24">
                                <?php echo $bekleyen_servis_sayisi; ?>
                            </span>
                            <p>
                                <a target="_blank" class="weight-400 font-18" href="">Bekleyen Servis Sayısı</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 mb-10">
                <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                    <div class="project-info">
                        <div class="project-info-left">
                            <div class="icon box-shadow bg-blue text-white">
                                <i class="fa fa-wrench"></i>
                            </div>
                        </div>
                        <div class="project-info-right">
                            <span class="no text-blue weight-500 font-24">
                                <?php echo $calisilan_servis_sayisi; ?>
                            </span>
                            <p>
                                <a target="_blank" class="weight-400 font-18" href="">Çalışılan Servis Sayısı:</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 mb-10">
                <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                    <div class="project-info clearfix">
                        <div class="project-info-left">
                            <div class="icon box-shadow bg-green text-white">
                                <i class="fa fa-check"></i>
                            </div>
                        </div>
                        <div class="project-info-right">

                            <span class="no text-blue weight-500 font-24">
                                <?php echo $tamamlanan_servis_sayisi; ?>

                            </span>
                            <p class="weight-400 font-18">
                                <a target="_blank" href="">
                                    Tamamlanan Servis Sayısı
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 mb-10">
                <div class="sum-customer pd-20 box-shadow border-radius-5 height-100-p">
                    <div class="project-info clearfix">
                        <div class="project-info-left">
                            <div class="icon box-shadow bg-danger text-white">
                                <i class="fa fa-close"></i>
                            </div>
                        </div>
                        <div class="project-info-right">
                            <span class="no text-blue weight-500 font-24">
                                <?php echo $iptal_servis_sayisi; ?>
                            </span>
                            <p class="weight-400 font-18">
                                <a target="_blank" href="">
                                    İptal Edilen Servis Sayısı
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h5 class="text-blue">Oluşturulan Tüm Servisler</h5>
        </div>
        <div class="float-right">
            <!-- Excele Aktar -->
            <?php if (permtrue("data_export_service")) { ?>
            <a href="#" class="btn btn-secondary" id="exportExcel"><i class="fa fa-file-excel-o"></i>
                Excele Aktar</a>
            <!-- Excele Aktar -->
            </a>
            <?php } ?>

            <?php if (permtrue("serviceAdd")) { ?>
                <a href="index.php?p=service-new" class="btn btn-primary"><i class="fa fa-save"></i> Yeni Giriş
                    Yap</a>

            <?php } ?>
        </div>

    </div>
    <div class="search-input-area d-flex"></div>
    <table id="serviceTable" class="data-table table-hover table-bordered table-responsive-sm">
        <thead>
            <tr>
                <th scope="col">Sıra No</th>
                <th scope="col">Servis No</th>
                <th>Firma Adı</th>
                <th>Bölge</th>
                <th>Servis Konusu </th>
                <th>İş Emri Oluşturma Tarihi</th>
                <th class="text-center">Servis Planlama Tarihi</th>
                <th>Sözleşme Durum</th>
                <th>Durum</th>
                <th>İş Emrini Oluşturan</th>
                <th class="text-nowrap no-export">İşlem</th>

            </tr>
        </thead>
        <tbody>
            <?php
            $sira = 1;
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
            while ($purc = $query->fetch(PDO::FETCH_ASSOC)) {
                $pid = $purc["id"];
                ?>
                <tr>
                    <td class="text-center"><?php echo $pid ?></td>
                    <td>
                        <?php echo $purc["service_number"]; ?>
                    </td>

                    <?php
                    // Firma id'si ile eşleşen kaydın Firma Adı getirilir 
                    $compid = $purc["pcid"];

                    $company_name = getCustomerName($compid);
                    ?>
                    <td data-tooltip="<?php echo $company_name; ?>">
                        <?php echo shorted($company_name, 40); ?>
                    </td>
                    <td class="text-center">
                        <?php echo Helper::getRegionName($purc["region"]) ?>
                    </td>


                    <td class="text-center">
                        <?php
                        // Firma id'si ile eşleşen kaydın Firma Adı getirilir 
                        $compid = $purc["servicestype"];
                        $sql = $ac->prepare("SELECT * FROM units WHERE id = ? ");
                        $sql->execute(array($compid));
                        $servicestype = $sql->fetch(PDO::FETCH_ASSOC);
                        $pid = $purc["id"];
                        $enc_id = Security::encrypt($pid);

                        echo $servicestype["title"]; ?>
                    </td>
                    <td class="text-center">
                        <?php echo Date::dmyHis($purc["pregdate"]) ?>
                    </td>
                    <td class="text-center"> <?php
                    echo (Date::dmyHis($purc["pstart_date"]));
                    ?>

                    </td>
                    <td class="text-center">
                        <?php
                        echo getSozlesmeStatusBadge($purc["contract_statu"]);
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        echo getStatusBadge($purc["pstatu"]);
                        ?>
                    </td>
                    <td>
                        <?php echo getUserName($purc["pcreativer"]); ?>
                    </td>

                    <td class="text-center" style="width:7%; white-space: nowrap;">

                        <?php if (permtrue("serviceEdit")) { ?>
                            <a type="button" href="index.php?p=service-edit&id=<?php echo $pid; ?>"
                                class="btn btn-sm btn-outline-info" data-tooltip="Düzenle"><i class="fa fa-pencil"></i></a>

                        <?php }
                        if (permtrue("serviceDel")) { ?>
                            <button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil"
                                onClick="deleteRecord('<?php echo $purc["id"]; ?> nolu Servisi silmek istediğinize emin misiniz?','<?php echo $pid; ?>','services','projects')"><i
                                    class="fa fa-trash"></i></button>

                        <?php } ?>


                        <button class="btn btn-secondary btn-sm" type="button" id="dropdownMenu2" data-toggle="dropdown">
                            <i class="fa fa-ellipsis-v ml-1 mr-1"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail" aria-labelledby="dropdownMenu2">
                            <a href="index.php?p=service-view&id=<?php echo ($pid) ?>" target="_blank" class="dropdown-item"
                                type="button">
                                <i class="fa fa-file-text-o mr-2"></i>
                                Servis Formunu Göster</a>
                                
                                <a href="service-view.php?id=<?php echo ($enc_id) ?>" target="_blank" class="dropdown-item" >
                                <i class="fa fa-list mr-2"></i>    
                                Servisi Görüntüle</a>
                        </div>
                    </td>
                </tr>
                <?php
                $sirano += 1;
            } ?>
        <tfoot>
            <tr>
                <th scope="col">Sıra No</th>
                <th scope="col">Servis No</th>
                <th>Firma Adı</th>
                <th>Bölge</th>
                <th>Servis Konusu </th>
                <th>İş Emri Oluşturma Tarihi</th>
                <th>Servis Planlama Tarihi</th>
                <th>Sözleşme Durum</th>
                <th>İş Emrini Oluşturan</th>
                <th>Durum</th>
                <th class="text-nowrap">İşlem</th>

            </tr>
        </tfoot>
        </tbody>
    </table>
</div>
<script src="include/js/data-table.js"></script>