<tr tabindex="<?php echo $tabindex ?? 0; ?>" class="align-middle"> 
    <td class="text-center" style="width: 44px;">
        <button type="button" class="sil btn btn-delete-row" data-tooltip="Satırı Sil">
            <i class="fa fa-trash-o"></i>
        </button>
    </td>
    <td style="width: 55px;">
        <input required type="text" class="form-control text-center satir_no" id="cihazno" name="cihazno[]" value="<?php echo htmlspecialchars($cihaz_no ?? 1, ENT_QUOTES, 'UTF-8'); ?>">
    </td>
    <td style="min-width: 150px;">
        <input type="text" class="form-control region text-left" name="cihazbolge[]" value="<?php echo htmlspecialchars($cihazbolge ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Bölge / Mahit">
    </td>
    <td style="min-width: 150px;">
        <input required type="text" class="form-control region text-left" name="cinsi[]" value="<?php echo htmlspecialchars($cinsi ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Örn: 6 KG KKT">
    </td>
    <td style="min-width: 85px;" data-tooltip="aa/yyyy şeklinde giriniz">
        <input type="text" autocomplete="off" class="form-control text-center filling-date" placeholder="aa/yyyy" name="dolumtarihi[]" value="<?php echo htmlspecialchars($dolumtarihi ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </td>
    <td style="min-width: 85px;">
        <input type="text" autocomplete="off" class="form-control text-center expiration-date" placeholder="aa/yyyy" name="sonkullanimtarihi[]" value="<?php echo htmlspecialchars($sonkullanimtarihi ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </td>
    <td style="min-width: 95px;">
        <input type="text" autocomplete="off" class="form-control text-center date-input rpr-date" placeholder="gg-aa-yyyy" name="kontoltarihi1[]" value="<?php echo htmlspecialchars($kontoltarihi1 ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </td>
    <td style="min-width: 95px;">
        <input type="text" autocomplete="off" class="form-control text-center date-input rpr-date" placeholder="gg-aa-yyyy" name="kontoltarihi2[]" value="<?php echo htmlspecialchars($kontoltarihi2 ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </td>
    <td style="min-width: 110px;">
        <div class="input-group m-0 p-0">
            <input name="islemkontroltarihi1[]" value="<?php echo htmlspecialchars($islemkontroltarihi1 ?? '', ENT_QUOTES, 'UTF-8'); ?>" type="text" class="form-control islemkontroltarihi text-center" placeholder="İşlem">
            <div class="input-group-append">
                <button class="btn dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item btn cursor-pointer">Basınç</a>
                    <a class="dropdown-item btn cursor-pointer">Kontrol</a>
                    <a class="dropdown-item btn cursor-pointer">Dolum</a>
                </div>
            </div>
        </div>
    </td>
    <td style="min-width: 110px;">
        <div class="input-group m-0 p-0">
            <input name="islemkontroltarihi2[]" value="<?php echo htmlspecialchars($islemkontroltarihi2 ?? '', ENT_QUOTES, 'UTF-8'); ?>" type="text" class="form-control islemkontroltarihi text-center" placeholder="İşlem">
            <div class="input-group-append">
                <button class="btn dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item btn cursor-pointer">Basınç</a>
                    <a class="dropdown-item btn cursor-pointer">Kontrol</a>
                    <a class="dropdown-item btn cursor-pointer">Dolum</a>
                </div>
            </div>
        </div>
    </td>
    <td style="min-width: 100px;">
        <select name="dismuhafaza[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option <?php echo (isset($dismuhafaza) && $dismuhafaza == "0") ? " selected" : "" ?> value="0">UYGUN DEĞİL</option>
            <option <?php echo (!isset($dismuhafaza) || $dismuhafaza == "1") ? " selected" : "" ?> value="1">UYGUN</option>
        </select>
    </td>
    <td style="min-width: 100px;">
        <select name="cevrekontrolu[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option <?php echo (isset($cevrekontrolu) && $cevrekontrolu == "0") ? " selected" : "" ?> value="0">UYGUN DEĞİL</option>
            <option <?php echo (!isset($cevrekontrolu) || $cevrekontrolu == "1") ? " selected" : "" ?> value="1">UYGUN</option>
        </select>
    </td>
    <td style="min-width: 100px;">
        <select name="pimkontrolu[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option <?php echo (isset($pimkontrolu) && $pimkontrolu == "0") ? " selected" : "" ?> value="0">UYGUN DEĞİL</option>
            <option <?php echo (!isset($pimkontrolu) || $pimkontrolu == "1") ? " selected" : "" ?> value="1">UYGUN</option>
        </select>
    </td>
    <td style="min-width: 100px;">
        <select name="manometrekontrolu[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option <?php echo (isset($manometrekontrolu) && $manometrekontrolu == "0") ? " selected" : "" ?> value="0">UYGUN DEĞİL</option>
            <option <?php echo (!isset($manometrekontrolu) || $manometrekontrolu == "1") ? " selected" : "" ?> value="1">UYGUN</option>
        </select>
    </td>
    <td style="min-width: 100px;">
        <select name="hortumkontrolu[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option <?php echo (isset($hortumkontrolu) && $hortumkontrolu == "0") ? " selected" : "" ?> value="0">UYGUN DEĞİL</option>
            <option <?php echo (!isset($hortumkontrolu) || $hortumkontrolu == "1") ? " selected" : "" ?> value="1">UYGUN</option>
        </select>
    </td>
    <td style="min-width: 100px;">
        <select name="talimatkontrolu[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option <?php echo (isset($talimatkontrolu) && $talimatkontrolu == "0") ? " selected" : "" ?> value="0">UYGUN DEĞİL</option>
            <option <?php echo (!isset($talimatkontrolu) || $talimatkontrolu == "1") ? " selected" : "" ?> value="1">UYGUN</option>
        </select>
    </td>
    <td style="min-width: 100px;">
        <select name="agirlikkontrolu[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option <?php echo (isset($agirlikkontrolu) && $agirlikkontrolu == "0") ? " selected" : "" ?> value="0">UYGUN DEĞİL</option>
            <option <?php echo (!isset($agirlikkontrolu) || $agirlikkontrolu == "1") ? " selected" : "" ?> value="1">UYGUN</option>
        </select>
    </td>
</tr>