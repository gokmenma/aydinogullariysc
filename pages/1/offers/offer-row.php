<?php if ($satirNo > 0) { ?>
    <!-- Taşıma Tutamacı -->
    <td class="text-center" style="vertical-align: middle; cursor: grab;">
        <span class="btn btn-sm text-muted p-0 drag-handle" title="Sıralamayı Değiştirmek İçin Sürükleyin">
            <i class="fa fa-arrows-alt"></i>
        </span>
    </td>

    <!-- İşlem Butonları -->
    <td class="app-item-action-2" style="vertical-align: middle; white-space: nowrap;">
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="sil btn btn-danger btn-sm" title="Satırı Sil">
                <i class="fa fa-trash"></i>
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm btn-clone-row" title="Satırı Klonla (Kopyala)">
                <i class="fa fa-clone"></i>
            </button>
        </div>
    </td>

    <!-- Sıra No -->
    <td class="app-item-number" style="vertical-align: middle;">
        <input class="form-control text-center font-weight-bold" type="text" name="satirno[]" value="<?php echo $satirNo; ?>" readonly style="background: #f8fafc; border-radius: 6px;">
    </td>

    <!-- Stok Kodu -->
    <td class="app-item-stock" style="vertical-align: middle;">
        <div class="product-autocomplete-wrap">
            <input type="text" id="stokKodu<?php echo $satirNo; ?>" value="<?php echo htmlspecialchars($stokKodu ?? ''); ?>" name="stokKodu[]" class="form-control stokKodu-input" placeholder="Stok Kodu" autocomplete="off" style="border-radius: 6px;">
        </div>
    </td>

    <!-- Ürün/Malzeme (Canlı Otomatik Tamamlama) -->
    <td class="app-item-name" style="vertical-align: middle;">
        <div class="product-autocomplete-wrap position-relative">
            <input required type="text" class="urunAdi form-control urunAdi-input" name="urunAdi[]" id="urunAdi<?php echo $satirNo; ?>" value="<?php echo htmlspecialchars($urunAdi ?? ''); ?>" placeholder="Ürün adı yazarak arayın veya seçin..." autocomplete="off" style="border-radius: 6px;">
            <div class="product-autocomplete-results" style="display: none;"></div>
        </div>
    </td>
<?php } ?>

<!-- Miktar -->
<td class="app-item-amount" style="vertical-align: middle;">
    <input required id="amount<?php echo $satirNo; ?>" autocomplete="off" name="amount[]" value="<?php echo $amount; ?>" type="number" step="any" min="0" class="form-control text-center amount-input" placeholder="0" style="border-radius: 6px;">
</td>

<!-- Birim -->
<td class="app-item-unit" style="vertical-align: middle;">
    <?php OlcuBirimleri('unit[]', $unit, '', 'unit' . $satirNo); ?>
</td>

<!-- Satış Fiyatı -->
<td class="app-item-price" style="vertical-align: middle;">
    <input required id="saleprice<?php echo $satirNo; ?>" name="saleprice[]" type="text" value="<?php echo $saleprice; ?>" class="form-control text-right saleprice-input" style="min-width: 85px; border-radius: 6px;" autocomplete="off" placeholder="0.00">
</td>

<!-- Satış Para Birimi -->
<td class="app-item-cur" style="vertical-align: middle;">
    <?php ParaBirimleri('salecur[]', $salecur, 'salecur' . $satirNo); ?>
</td>

<!-- Satır Tutarı -->
<td class="app-item-rowtotal" style="vertical-align: middle;">
    <input type="text" readonly id="total<?php echo $satirNo; ?>" name="total[]" class="form-control text-right font-weight-bold row-total-input" value="<?php echo $rowTotal; ?>" style="background: #f8fafc; border-radius: 6px; min-width: 85px;">
</td>

<!-- Alış Fiyatı -->
<td class="app-item-price" style="vertical-align: middle;">
    <input id="buyprice<?php echo $satirNo; ?>" name="buyprice[]" type="text" value="<?php echo $buyprice; ?>" class="form-control text-right buyprice-input" style="min-width: 85px; border-radius: 6px;" autocomplete="off" placeholder="0.00">
</td>

<!-- Alış Para Birimi -->
<td class="app-item-cur" style="vertical-align: middle;">
    <?php ParaBirimleri('buycur[]', $buycur, 'buycur' . $satirNo); ?>
</td>
