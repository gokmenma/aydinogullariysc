<?php if ($satirNo > 0) {
    ?>
    <td style="width: 35px; min-width: 35px; text-align: center; vertical-align: middle;">
        <span class="btn btn-sm text-muted p-0 drag-handle" style="cursor: grab;" title="Sıralamayı Değiştirmek İçin Sürükleyin">
            <i class="fa fa-arrows-alt"></i>
        </span>
    </td>

    <td class="app-item-action-2 text-center" style="width: 80px; min-width: 80px; vertical-align: middle; white-space: nowrap;">
        <div class="btn-group btn-group-sm" role="group" style="display: inline-flex;">
            <button type="button" class="sil btn btn-sm btn-danger text-white" title="Satırı Sil" style="padding: 4px 8px; border-radius: 6px 0 0 6px;">
                <i class="fa fa-trash"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary btn-clone-row" title="Satırı Klonla (Kopyala)" style="padding: 4px 8px; border-radius: 0 6px 6px 0;">
                <i class="fa fa-clone"></i>
            </button>
        </div>
    </td>

    <!-- Sıra No -->
    <td class="app-item-number text-center" style="width: 55px; min-width: 55px; vertical-align: middle;">
        <input class="form-control text-center font-weight-bold" type="text" name="satirno[]" value="<?php echo $satirNo; ?>" readonly style="background: #f8fafc; border-radius: 6px; width: 45px; margin: 0 auto;">
    </td>

    <!-- Stok Kodu -->
    <td class="app-item-stock" style="width: 140px; min-width: 120px; vertical-align: middle;">
        <div class="product-autocomplete-wrap">
            <input type="text" id="stokKodu<?php echo $satirNo; ?>" value="<?php echo htmlspecialchars($stokKodu ?? ''); ?>"
                name="stokKodu[]" class="form-control stokKodu-input" placeholder="Stok Kodu" autocomplete="off" style="border-radius: 6px;">
        </div>
    </td>

    <!-- Ürün Adı (Canlı Otomatik Tamamlama) -->
    <td class="app-item-name" style="min-width: 220px; vertical-align: middle;">
        <div class="product-autocomplete-wrap position-relative">
            <input type="text" class="urunAdi form-control urunAdi-input" name="urunAdi[]" id="urunAdi<?php echo $satirNo; ?>"
                value="<?php echo htmlspecialchars($urunAdi ?? ''); ?>" placeholder="Ürün adı yazarak arayın..." autocomplete="off" style="border-radius: 6px;" required>
        </div>
    </td>

    <!-- MİKTAR -->
    <td class="app-item-amount text-center" style="width: 90px; min-width: 80px; vertical-align: middle;">
        <input type="number" step="any" min="0" autocomplete="off" required id="amount<?php echo $satirNo; ?>" name="amount[]" value="<?php echo $amount; ?>" class="Adet form-control amount-input text-center" placeholder="0" style="border-radius: 6px;">
    </td>

    <!-- ÖLÇÜ BİRİMLERİ -->
    <td class="app-item-unit" style="width: 110px; min-width: 100px; vertical-align: middle;">
        <?php OlcuBirimleri('unit[]', $unit, "required", "unit" . $satirNo) ?>
    </td>

    <!-- FİYAT -->
    <td class="app-item-price" style="width: 120px; min-width: 100px; vertical-align: middle;">
        <input required id="buyprice<?php echo $satirNo; ?>" name="buyprice[]" type="text"
            value="<?php echo $buyprice; ?>" class="form-control buyprice-input text-right" autocomplete="off" placeholder="0.00" style="border-radius: 6px;">
    </td>

    <!-- PARA BİRİMLERİ -->
    <td class="app-item-cur" style="width: 100px; min-width: 90px; vertical-align: middle;">
        <?php ParaBirimleri("buycur[]", $buycur, "buycur" . $satirNo) ?>
    </td>

    <!-- AÇIKLAMA -->
    <td style="min-width: 180px; vertical-align: middle;">
        <input type="text" class="form-control" style="min-width: 150px; width: 100%; border-radius: 6px;" 
            name="rowdescription[]" value="<?php echo htmlspecialchars($rowdescription ?? ''); ?>" placeholder="Kalem açıklaması...">
    </td>

<?php } ?>