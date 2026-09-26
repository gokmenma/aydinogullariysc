<?php
permcontrol("mailandsmssend");
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'App/Helper/date.php';
require_once 'App/Model/PurchaseModel.php';
require_once 'App/Model/CustomerModel.php';
require_once 'App/Model/OfferModel.php';
require_once 'App/Model/ReportsModel.php';


use App\Helper\Date;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';



$type = $_GET["type"];
$id = $_GET["id"];

$customerObj = new CustomerModel();
if ($type == "purchase") {

    $mail_subject = $file_type = "Satın Alma Formu";

    $purchaseObj = new PurchaseModel();

    $purchase = $purchaseObj->find($id);
    $customer = $customerObj->find($purchase->companyID);

} elseif($type == "offer") {
    
    $mail_subject = $file_type = "Teklif Formu";
    
    $offerObj = new OfferModel();

    $offer = $offerObj->find($id);
    $customer = $customerObj->find($offer->cid);

}elseif($type == "ysc") {
    
    $mail_subject = $file_type = "Yangın Söndürme Cihazı";
    
    $reportObj = new ReportsModel();

    $report = $reportObj->find($id);
    $customer = $customerObj->find($report->customer_id);

}




if (@$_GET["st"] == "success-mail") {
$link ="&type=" . $_GET["type"] ;
        showAlert("success", "Mail başarıyla gönderildi",$link);
}


?>
<form method="POST" id="myForm">
    <input type="hidden" class="form-control" name="id" id="id" value="<?php echo $id; ?>">
    <div class="pd-20 bg-white border-radius-16 box-shadow mb-30">
        <div class="clearfix">
            <div class="pull-left">
                <h4 class="text-blue">
                    Raporu Mail Olarak Gönder
                </h4>
                <p class="mb-30 font-14">Sayfadaki <font color="red">(*)</font> yıldız ile belirtilen alanları boş
                    bırakmayın..<br></p>
            </div>
            <button type="button" id="submitButton" style="float:right" class="btn btn-sm btn-primary mt-3 mb-3">
                <i class="fa fa-paper-plane"> </i> Mail Gönder
            </button>
            <button onclick="window.history.back()" type="button" style="float:right"
                class="btn btn-sm btn-secondary mt-3 mr-2 mb-3">
                <i class="fa fa-left"> </i> Geri
            </button>
        </div>



        <!-- GÖNDEREN MAİL ADRESİ -->
        <div class="form-group row">
            <label id="labelkategori" class="col-md-2 col-sm-6">
                <font color="red">(*)</font>Kopya Olarak Gönder:
            </label>


            <div class="col-md-10 col-sm-12">
                <select name="mail_address[]" class="selectpicker form-control" multiple data-actions-box="true"
                    data-style="border bg-white">

                    <?php

                    $sql = $ac->prepare("SELECT * from users");
                    $sql->execute();
                    while ($row = $sql->fetch(PDO::FETCH_OBJ)) {
                        $selected = sesset("email") == $row->email ? "selected" : "";
                        echo "<option value=" . $row->email . " $selected > " . $row->username . " / " . $row->Unvan . " / " . $row->email . "</option>";
                    }
                    ; ?>

                </select>
            </div>
        </div>
        <!-- GÖNDEREN MAİL ADRESİ -->


        <!-- FİRMA SEÇİMİ -->
        <div class="form-group row">
            <label id="labelkategori" class="col-md-2 col-sm-6">
                <font color="red">(*)</font>Firma Mail adresi :

            </label>
            <div class="col-md-10 col-sm-12">
                <input type="text" class="form-control" name="customer_mail_address"
                    value="<?php echo $customer->email ?>">
                <p class="m-0"><small>Firmanın birden fazla mail adresi varsa virgül ile diğer mail adreslerini
                        ekleyebilirsiniz</small></p>
                <p><small>Örnek : firmamail1@mailadresi.com, firmamail2@mailadresi.com</small></p>
            </div>

        </div>
        <!-- FİRMA SEÇİMİ -->

        <!-- KONU -->
        <div class="form-group row">

            <label class="col-md-2 col-sm-6">Konu Başlığı :</label>
            <div class="col-md-10 col-sm-12">
                <input autocomplete="off" required type="text" class="form-control" name="mailkonu"
                    value="<?php echo $mail_subject; ?>">

            </div>
        </div>
        <!-- KONU -->

        <!-- EK -->
        <div class="form-group row">

            <label class="col-md-2 col-sm-6">Gönderilecek Pdf Türü :</label>
            <div class="col-md-10 col-sm-12">
                <input class="form-control form-control-sm" name="file_type" id="file_type" type="text" value="<?php echo $file_type ?>"
                    readonly disabled>
            </div>
        </div>
        <!-- EK -->


        <div class="form-group row">
            <div class="col-md-2 col-sm-6">

                <label>
                    Mail İçeriği :
                </label>

            </div>
            <div class="col-md-10 col-sm-12">
                <textarea class="textarea_editor form-control border-radius-0" name="mail_body" value="" type="text"
                    placeholder="Gönderilecek raporun altına not ekleyebilirsiniz"></textarea>
            </div>
        </div>

    </div>


</form>

<script>

    $(document).ready(function () {
        $("#submitButton").click(function () {

            let action = '';
            let type = $("#file_type").val();

            if(type == "Satın Alma Formu"){
                action = "index.php?p=purchase-detail&id=" + $("#id").val() + "&send-mail=true";
            }else if(type == "Teklif Formu"){
                action = "index.php?p=offer-view&id=" + $("#id").val() + "&send-mail=true";
            }else if(type == "Yangın Söndürme Cihazı"){
                action = "index.php?p=reports/ysc/report-view-ysc&id=" + $("#id").val() + "&send-mail=true";
            }

            var form = $("#myForm");
            let id = $("#id").val();
            form.attr("action", action);
            form.submit();
        });
    });
</script>
<script>
    $(document).ready(function () {

        $(".selectpicker").selectpicker({
            noneSelectedText: "Kullanıcı Seçin!",
            size: 8,
            deselectAllText: "Seçimi Temizle",
            selectAllText: "Tümünü Seç",
            countSelectedText: "{0} seçildi",
            liveSearch: "true"
        })
    });
</script>