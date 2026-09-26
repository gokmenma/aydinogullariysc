<?php
$pid = $_GET["id"];

// Servis bilgileri
$sql = $ac->prepare("Select * from projects  WHERE id = ?");
$sql->execute(array($pid));
$result = $sql->fetch(PDO::FETCH_ASSOC);

// Firma Bilgi
$sql = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$sql->execute(array($result['pcid']));
$row = $sql->fetch(PDO::FETCH_ASSOC);

$sql = $ac->prepare("Select * from users WHERE id = ?");
$sql->execute(array($result['pcreativer']));
$person = $sql->fetch(PDO::FETCH_ASSOC);


?>


<div class="content bg-white border-radius-8 box-shadow mb-30">
    <section id="printable-content" id="invoice">
        <div class="container my-1 py-5" id="ELEMENTID">


            <div class="pb-3">

                <!-- Invoice Company Details -->
                <div class="float-right">
                    <button href="javascript" onclick="printContent()" class="btn btn-secondary btn-sm"><i
                            class="fa fa-print"></i> Yazdır</button>
                    <a href="index.php?p=service/list" class="btn btn-info btn-sm"> <i class="fa fa-list m-1"></i> Listeye
                        Dön</a>
                    <a href="index.php?p=generate_pdf" class="btn btn-danger btn-sm "> <i
                            class="fa fa-download m-1"></i>
                        PDF
                        KAYDET</a>

                </div>

            </div><br>

            <div class="text-right pr-1 ">
                <div>

                    <img class="float-left" src="files/46_logo.png" alt="">
                </div>
                <div>
                    <p class="font-weight-bold"><?= htmlspecialchars(mb_strtoupper(set('company_name')), ENT_QUOTES, 'UTF-8') ?></p>

                    <ul class="list-unstyled m-0">
                        <li><?= htmlspecialchars(set('company_address'), ENT_QUOTES, 'UTF-8') ?></li>
                        <li>Tel: <?= htmlspecialchars(set('company_phone1') . ' / ' . set('company_phone2'), ENT_QUOTES, 'UTF-8') ?></li>
                        <li><?= htmlspecialchars(set('admin_mail') . ' / ' . set('panel_url'), ENT_QUOTES, 'UTF-8') ?></li>
                    </ul>
                </div>

            </div>


            <div class="align-items-center border-top border-bottom border-primary my-5 py-3">
                <h5 class="text-center font-weight-bold">
                    İŞ EMRİ / GÖREVLENDİRME FORMU
                </h5>
            </div>
            <table>
                <thead>
                    <th width="160"></th>
                    <th width="500"></th>
                    <th width="250"></th>
                    <th width="200"></th>
                </thead>
                <tbody>
                    <tr height="50">
                        <td class="font-weight-bold ">Firma Adı : </td>
                        <td>
                            <?php echo $row["company"] ?>
                        </td>
                        <td class="font-weight-bold"> Servis No :</td>
                        <td>
                            <?php echo "SN" . $result["id"]; ?>
                        </td>
                    </tr>
                    <tr height="50">
                        <td class="font-weight-bold ">Firma İlgili Kişi : </td>
                        <td>
                            <?php echo $row["yetkili"] ?>
                        </td>
                        <td class="font-weight-bold"> Servis Oluşturma Tarihi :</td>
                        <td>
                            <?php echo $result["pstart_date"]; ?>
                        </td>
                    </tr>
                    <tr height="50">
                        <td class="font-weight-bold ">Firma İrtibat No : </td>
                        <td>
                            <?php echo $row["gsm"] ?>
                        </td>
                        <td class="font-weight-bold"> Servisi Oluşturan Personel :</td>
                        <td>
                            <?php echo $person["username"]; ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class=" border-top border-bottom border-primary my-5 py-3">
                            <h5 class="text-center font-weight-bold"> İŞİN TANIMI </h5>
                        </td>
                    </tr>
                    <tr height="250">
                        <td colspan="4" style="vertical-align: top;">
                            <?php echo $result["pdesc"] ?>
                        </td>
                    </tr>
                    <tr height="50" class=" border-top border-primary my-5 py-3">
                        <td class="font-weight-bold ">Firmaya Giriş : </td>
                        <td></td>
                        <td class="font-weight-bold"> Personel Miktarı :</td>
                        <td> </td>
                    </tr>
                    <tr height="50">
                        <td class="font-weight-bold ">Firmadan Çıkış: </td>
                        <td></td>
                        <td class="font-weight-bold"> Varsa Gecikme Nedeni :</td>
                        <td> </td>
                    </tr>
                    <tr height="400">
                        <td class="font-weight-bold ">Servis Sonucu : </td>
                        <td></td>
                        <td></td>
                        <td> </td>
                    </tr>
                    <tr class=" border-top border-primary my-5 py-3 font-weight-bold" style="text-align: center;">
                        <td> İŞİ TESLİM EDEN <br>PERSONEL</td>
                        <td></td>
                        <td></td>
                        <td>TESLİM ALAN <br>YETKİLİ İMZA</td>

                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../vendors/scripts/xepOnline.jqPlugin.js"></script>


<script>
    function printContent() {
        var content = document.getElementById("printable-content").innerHTML;

        // AJAX isteği oluştur
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "pages/1/purchase-print.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // AJAX yanıtını işle
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                // Yanıtı al ve yeni pencerede aç
                var printWindow = window.open('', '');
                printWindow.document.body.innerHTML = xhr.responseText;
                setTimeout(function () {
                    printWindow.print(); // Yazdırma işlemi
                    printWindow.close(); // Pencereyi kapat
                }, 10); // Yazdırma işlemini bir saniye sonra başlat
            }
        };
        // İsteği gönder
        xhr.send("content=" + encodeURIComponent(content));
    }
</script>