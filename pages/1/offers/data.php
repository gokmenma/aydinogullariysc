<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> TEKLİF GÖRÜNTÜLE </title>
</head>
<style>
    body {
        font-family: dejavu sans;
        margin: 0;
        padding: auto;
        font-size: 10px;
    }

    @page {
        margin: 40px;
        padding: auto;
        font-size: 8px !important;
    }


    table {
        width: 100%;
        border-collapse: collapse;
        max-width: 790px;

    }

    td {
        white-space: wrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }



    .form-head {
        font-size: 16px !important;
        text-align: center;
        border-bottom: 1px solid #808080;
    }

    .border {
        border: 1px solid #ccc !important;
    }

    .text-left {
        text-align: left !important;
    }

    .text-center {
        text-align: center !important;
    }

    .text-right {
        text-align: right !important;
    }

    .text-white {
        color: #fff;
    }

    .fs-16 {
        font-size: 16px;
    }

    .bg-gray {
        background-color: #808080;
    }

    .border {
        border: 1px solid #808080;
    }

    .border-right {
        border-right: 2px solid #808080;
    }

    .border-bottom {
        border-bottom: 2px solid #808080;
    }

    .border-bottom-1 {
        border-bottom: 1px solid #808080;
    }

    .border-top {
        border-top: 2px solid #808080;
    }

    .m-0 {
        margin: 0 auto;
    }

    p {
        margin: 0;
    }

    .brand {
        text-align: right;

    }

    .header strong {
        border-bottom: 2px solid #808080;
        border-top: 2px solid #808080;
        padding: 5px;
        font-size: 16px;
        display: block;
        margin: 10px 0;

    }

    table-header,
    .rows {
        border-bottom: 1px solid #808080;
    }

    .rows {
        border-top: 1px solid #808080;
    }

    #alt_toplam_table {
        width: 100%;

    }

    #alt_toplam_table tr {
        border-bottom: 1px solid #808080;
    }

    .col-10 {
        width: 20, 83%;
        min-width: 20, 83%;
        max-width: 20, 83%;
    }

    .col-6 {
        width: 12, 5%;
        min-width: 12, 5%;
        max-width: 12, 5%;
    }

    .border-none {
        border: none !important;    
    }

    .p-1 {
        padding: 5px !important;
    }
</style>

<body>
    <table>

        <tbody>
            <tr>
                <td colspan="24">
                    <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
                </td>
                <td colspan="24" class="brand">
                

                </td>
            </tr>

            <tr>
                <td colspan="48" class="text-center header" style>
                    <strong> Sayfa Başlığı</strong>
                </td>
            </tr>

            <tr>
                <td colspan="6"><strong>Firma:</strong></td>
                <td colspan="30">' . $customer['company'] . '</td>

                <td colspan="4"><strong>Teklif No:</strong></td>
                <td colspan="8">' . $offer['offerNumber'] . '</td>
            </tr>
            <tr>
                <td colspan="6"><strong>Telefon :</strong></td>
                <td colspan="30">' . $customer['gsm'] . '</td>


                <td colspan="4"><strong>Tarih :</strong></td>
                <td colspan="8">' . $offer['offer_date'] . '</td>
            </tr>
            <tr>
                <td colspan="6"><strong>E Posta:</strong></td>
                <td colspan="30">' . $customer['email'] . '</td>


                <td colspan="4"><strong>Referans :</strong></td>
                <td colspan="8"></td>
            </tr>
            <tr>
                <td colspan="6"><strong>İlgili :</strong></td>
                <td colspan="30">' . $customer['yetkili'] . '</td>

                <td colspan="4"><strong>Teklif Konusu :</strong></td>
                <td colspan="8">' . $offer['offer_subject'] . '</td>
            </tr>



            <tr>
                <td colspan="48" style="padding:30px 0">
                    ' . $offer['offer_header_content'] . '

                </td>

            </tr>


            <tr class="table-header" style="font-weight:bold;background:#bbb;">
                <td colspan="2" style="max-width:30px;">NO</td>
                <td colspan="28" style="max-width:100px">ÜRÜN / HİZMET AÇIKLAMASI</td>
                <td colspan="6" style="max-width:57px" class="text-right">MİKTAR</td>
                <td colspan="6" style="max-width:57px" class="text-right">BİRİM FİYAT</td>
                <td colspan="6" style="max-width:57px" class="text-right"> TUTAR </td>


            </tr>';

        </tbody>
    </table>
</body>

</html>