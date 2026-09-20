<?php if ($satirNo > 0) {
    ?>
    <td class="app-item-action">
        <a type="button" class="sil btn btn-sm btn-danger text-white">Sil</a>
    </td>

    <!--Sırano-->
    <td class="app-item-number">
        <input class="form-control" type="text" value="<?php echo $satirNo; ?>">
    </td>
    <!--Sırano-->

    <!-- Stok Kodu -->
    <td class="app-item-stock"><input type="text" id="stokKodu<?php echo $satirNo; ?>" value="<?php echo $stokKodu; ?>"
            name="stokKodu[]" class="form-control" placeholder="Stok Kodu giriniz!">
    </td>
    <!-- Stok Kodu -->

    <td class="app-item-name">
        <!-- Button trigger modal -->
        <div class="input-group m-0">

            <input type="text" class="urunAdi form-control" name="urunAdi[]" id="urunAdi<?php echo $satirNo; ?>"
                value="<?php echo $urunAdi; ?>" placeholder="Ürün adını giriniz!">
            <button type="button" id="<?php echo $satirNo; ?>" class="btn btn-sm btn-info selectProduct"
                data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                <i class="fa fa-plus-circle"></i>
            </button>
        </div>
    </td>

    <!-- MİKTAR -->
    <td class="app-item-amount">
        <input type="number" autocomplete="off" required id="amount" name="amount[]" value="<?php echo $amount; ?>"
            class="Adet form-control">
    </td>
    <!-- MİKTAR -->

    <!-- ÖLÇÜ BİRİMLERİ -->
    <td class="app-item-unit">
        <?php OlcuBirimleri('unit[]', $unit, "required", "unit" . $satirNo) ?>
    </td>
    <!-- ÖLÇÜ BİRİMLERİ -->

    <!-- FİYAT -->
    <td class="app-item-price">
        <input required id="buyprice<?php echo $satirNo; ?>" name="buyprice[]" type="number"
            value="<?php echo $buyprice; ?>" class="form-control mr-1" autocomplete="off">

    </td>
    <!-- FİYAT -->

    <!-- PARA BİRİMLERİ -->
    <td class="app-item-cur">
        <?php ParaBirimleri("buycur[]", $buycur, "buycur" . $satirNo) ?>
    </td>
    <!-- PARA BİRİMLERİ -->

<?php } ?>