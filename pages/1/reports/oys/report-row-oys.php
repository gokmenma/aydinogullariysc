<?php
$cinsi = $cinsi ?? '';
$bulundugu_bolge = $bulundugu_bolge ?? '';
$cevre_kontrolu = $cevre_kontrolu ?? '1';
$dis_muhafaza = $dis_muhafaza ?? '1';
$calisabilirlik_testi = $calisabilirlik_testi ?? '1';
?>
<tr>
    <td class="text-center">
        <button type="button" class="btn btn-delete-row sil" title="Satırı Sil">
            <i class="fa fa-trash"></i>
        </button>
    </td>
    <td>
        <input required type="text" class="form-control" name="cinsi[]" value="<?php echo htmlspecialchars($cinsi, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ekipman Cinsi">
    </td>
    <td>
        <input required type="text" class="form-control" name="bulundugu_bolge[]" value="<?php echo htmlspecialchars($bulundugu_bolge, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Bulunduğu Bölge">
    </td>
    <td>
        <select name="cevre_kontrolu[]" class="form-control custom-select-status">
            <option value="1" <?php echo ($cevre_kontrolu == '1' || $cevre_kontrolu == 'UYGUN') ? 'selected' : ''; ?>>UYGUN</option>
            <option value="0" <?php echo ($cevre_kontrolu == '0' || $cevre_kontrolu == 'UYGUN DEĞİL') ? 'selected' : ''; ?>>UYGUN DEĞİL</option>
        </select>
    </td>
    <td>
        <select name="dis_muhafaza[]" class="form-control custom-select-status">
            <option value="1" <?php echo ($dis_muhafaza == '1' || $dis_muhafaza == 'UYGUN') ? 'selected' : ''; ?>>UYGUN</option>
            <option value="0" <?php echo ($dis_muhafaza == '0' || $dis_muhafaza == 'UYGUN DEĞİL') ? 'selected' : ''; ?>>UYGUN DEĞİL</option>
        </select>
    </td>
    <td>
        <select name="calisabilirlik_testi[]" class="form-control custom-select-status">
            <option value="1" <?php echo ($calisabilirlik_testi == '1' || $calisabilirlik_testi == 'UYGUN') ? 'selected' : ''; ?>>UYGUN</option>
            <option value="0" <?php echo ($calisabilirlik_testi == '0' || $calisabilirlik_testi == 'UYGUN DEĞİL') ? 'selected' : ''; ?>>UYGUN DEĞİL</option>
        </select>
    </td>
</tr>