<?php if ($satirNo > 0) {
    ?>
	    <td> <a href="#" class="btn btn-sm">
	            <i class="fa fa-arrows-alt"></i>
	        </a>
	    </td> 
    <!-- İşlem -->
    <td class="app-item-action-2">
        <a href="#" class="sil btn btn-sm btn-danger">Sil</a>
        <div class="dropdown d-inline">
            <button class="btn btn-secondary btn-sm" type="button" id="dropdownMenu2" data-toggle="dropdown">
                <i class="fa fa-list ml-1 mr-1"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu2">
                <button type="button" class="moveUp dropdown-item">
                    <i class="fa fa-arrow-up mr-2"></i>
                    Yukarı Taşı</button>
                <button type="button" class="moveDown dropdown-item">
                    <i class="fa fa-arrow-down mr-2"></i>
                    Aşağı Taşı</button>
            </div>
        </div>
    </td>
    <!-- İşlem -->

    <!--Sırano-->
    <td class="app-item-number">
        <input class="form-control" type="text" name = "satirno[]" value="<?php echo $satirNo; ?>">
    </td>
    <!--Sırano-->

    <!--Stok Kodu-->
    <td class="app-item-stock"><input type="text" id="stokKodu<?php echo $satirNo; ?>" value="<?php echo $stokKodu; ?>"
            name="stokKodu[]" class="form-control" placeholder="Stok Kodu giriniz!">
    </td>
    <!--Stok Kodu-->

    <!-- Ürün/Malzeme -->
    <td class="app-item-name">
        <div class="input-group m-0">
            <input required type="text" class="urunAdi form-control" name="urunAdi[]" id="urunAdi<?php echo $satirNo; ?>"
                value="<?php echo htmlspecialchars($urunAdi); ?>" placeholder="Ürün adını giriniz!">
            <button type="button" id="<?php echo $satirNo; ?>" data-tooltip="Ürün Seçiniz"
                class="btn btn-info btn-sm selectProduct" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                <i class="fa fa-plus-circle"></i>
            </button>
        </div>
    <?php } ?>
    
</td>
<!-- Ürün/Malzeme -->

<!-- Miktar -->
<td class="app-item-amount">
    <input required id="amount<?php echo $satirNo; ?>" autocomplete="off" name="amount[]" value="<?php echo $amount; ?>"
        type="text" class="form-control">
</td>
<!-- Miktar -->

<td class="app-item-unit">
    <?php OlcuBirimleri('unit[]', $unit, "", "unit" . $satirNo) ?>
</td>


<td class="app-item-price">
    <input required id="saleprice<?php echo $satirNo; ?>" name="saleprice[]" type="text"
        value="<?php echo $saleprice; ?>" class="form-control" style="min-width: 90px;" autocomplete="off">
</td>


<td class="app-item-cur">
    <?php ParaBirimleri("salecur[]", $salecur, "salecur" . $satirNo) ?>
</td>

<td class="app-item-rowtotal">
    <input type="text" readonly id="total<?php echo $satirNo; ?>" name="total[]" class="form-control"
        value="<?php echo $rowTotal; ?>">
</td>

<td class="app-item-price">
    <input id="buyprice<?php echo $satirNo; ?>" name="buyprice[]" type="text"
        value="<?php echo $buyprice; ?>" class="form-control mr-1" style="min-width: 90px;" autocomplete="off">

</td>
<td class="app-item-cur">
    <?php ParaBirimleri("buycur[]", $buycur, "buycur" . $satirNo) ?>
</td>
