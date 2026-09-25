<?php
$cinsi = $cinsi ?? '';
$bulundugu_bolge = $bulundugu_bolge ?? '';
$markasi = $markasi ?? '';
$kontrol_tarihi = $kontrol_tarihi ?? date('d.m.Y');
$problems = $problems ?? '1';
$islemler = $islemler ?? '1';
?>
<tr>
    <td class="text-center">
        <button type="button" class="btn btn-delete-row sil" title="Satırı Sil">
            <i class="fa fa-trash"></i>
        </button>
    </td>
    <td>
        <input required type="text" class="form-control" name="cinsi[]" value="<?php echo htmlspecialchars($cinsi, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cihaz / Armatür Cinsi">
    </td>
    <td>
        <input required type="text" class="form-control" name="bulundugu_bolge[]" value="<?php echo htmlspecialchars($bulundugu_bolge, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Bulunduğu Kısım / Konum">
    </td>
    <td>
        <input required type="text" class="form-control" name="markasi[]" value="<?php echo htmlspecialchars($markasi, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Marka / Model">
    </td>
    <td>
        <input type="text" class="form-control date-picker" name="kontroltarihi[]" value="<?php echo htmlspecialchars($kontrol_tarihi, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tarih" autocomplete="off">
    </td>
    <td>
        <select name="problems[]" class="form-control custom-select-status">
            <option value="1" <?php echo ($problems == '1' || $problems == 'UYGUN') ? 'selected' : ''; ?>>UYGUN / SORUN YOK</option>
            <option value="0" <?php echo ($problems == '0' || $problems == 'UYGUN DEĞİL') ? 'selected' : ''; ?>>UYGUN DEĞİL / SORUNLU</option>
        </select>
    </td>
    <td>
        <select name="islemler[]" class="form-control custom-select-status">
            <option value="1" <?php echo ($islemler == '1' || $islemler == 'UYGUN') ? 'selected' : ''; ?>>İŞLEM GEREKMEZ</option>
            <option value="0" <?php echo ($islemler == '0' || $islemler == 'UYGUN DEĞİL') ? 'selected' : ''; ?>>BAKIM / ONARIM GEREKİR</option>
        </select>
    </td>
</tr>