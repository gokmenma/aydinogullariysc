<?php
$tabindex = $tabindex ?? 0;
$sirano = $sirano ?? 1;
$cinsi = $cinsi ?? "";
$bulundugu_kisim = $bulundugu_kisim ?? "";
$ozellikler = $ozellikler ?? "";
$control_date_closet = $control_date_closet ?? date("d.m.Y");
$next_control_date_closet = $next_control_date_closet ?? date("d.m.Y", strtotime("+1 year"));
$vana_durum = isset($vana_durum) ? (string)$vana_durum : "1";
$hortum_baglanti_durum = isset($hortum_baglanti_durum) ? (string)$hortum_baglanti_durum : "1";
$levha_durum = isset($levha_durum) ? (string)$levha_durum : "1";
$pas_durum = isset($pas_durum) ? (string)$pas_durum : "1";
$kilit_durum = isset($kilit_durum) ? (string)$kilit_durum : "1";
$hortum_durum = isset($hortum_durum) ? (string)$hortum_durum : "1";
$basinc_degeri = $basinc_degeri ?? "";
$nozul_durum = isset($nozul_durum) ? (string)$nozul_durum : "1";
$aciklama = $aciklama ?? "";
?>
<tr tabindex="<?php echo (int)$tabindex; ?>">
    <td class="text-center align-middle" style="width: 45px;">
        <button type="button" class="sil btn btn-sm btn-delete-row" title="Satırı Sil">
            <i class="fa fa-trash"></i>
        </button>
    </td>
    <td class="text-center" style="width: 50px;">
        <input type="text" class="form-control font-weight-bold text-center bg-light" name="satirno[]" value="<?php echo htmlspecialchars((string)$sirano, ENT_QUOTES, 'UTF-8'); ?>" readonly>
    </td>
    <td style="min-width: 130px;">
        <input required type="text" autocomplete="off" class="form-control" name="cinsi[]" value="<?php echo htmlspecialchars($cinsi, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cinsi">
    </td>
    <td style="min-width: 140px;">
        <input required type="text" autocomplete="off" class="form-control" name="bulundugu_kisim[]" value="<?php echo htmlspecialchars($bulundugu_kisim, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Bulunduğu Kısım">
    </td>
    <td style="min-width: 100px;">
        <input required type="text" autocomplete="off" class="form-control text-center" name="ozellikler[]" value="<?php echo htmlspecialchars($ozellikler, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Özellikler (Mt)">
    </td>
    <td style="min-width: 105px;">
        <input required type="text" autocomplete="off" class="form-control text-center date-input" name="control_date_closet[]" value="<?php echo htmlspecialchars($control_date_closet, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Kontrol Tarihi">
    </td>
    <td style="min-width: 105px;">
        <input required type="text" autocomplete="off" class="form-control text-center date-input" name="next_control_date_closet[]" value="<?php echo htmlspecialchars($next_control_date_closet, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Sonraki Kontrol">
    </td>
    <td style="min-width: 110px;">
        <select required name="vana_durum[]" class="form-control custom-select-status">
            <option value="1" <?php echo $vana_durum === "1" ? "selected" : ""; ?>>UYGUN</option>
            <option value="0" <?php echo $vana_durum === "0" ? "selected" : ""; ?>>UYGUN DEĞİL</option>
            <option value="2" <?php echo $vana_durum === "2" ? "selected" : ""; ?>>DEĞERLENDİRME DIŞI</option>
        </select>
    </td>
    <td style="min-width: 110px;">
        <select required name="hortum_baglanti_durum[]" class="form-control custom-select-status">
            <option value="1" <?php echo $hortum_baglanti_durum === "1" ? "selected" : ""; ?>>UYGUN</option>
            <option value="0" <?php echo $hortum_baglanti_durum === "0" ? "selected" : ""; ?>>UYGUN DEĞİL</option>
            <option value="2" <?php echo $hortum_baglanti_durum === "2" ? "selected" : ""; ?>>DEĞERLENDİRME DIŞI</option>
        </select>
    </td>
    <td style="min-width: 110px;">
        <select required name="levha_durum[]" class="form-control custom-select-status">
            <option value="1" <?php echo $levha_durum === "1" ? "selected" : ""; ?>>UYGUN</option>
            <option value="0" <?php echo $levha_durum === "0" ? "selected" : ""; ?>>UYGUN DEĞİL</option>
            <option value="2" <?php echo $levha_durum === "2" ? "selected" : ""; ?>>DEĞERLENDİRME DIŞI</option>
        </select>
    </td>
    <td style="min-width: 110px;">
        <select required name="pas_durum[]" class="form-control custom-select-status">
            <option value="1" <?php echo $pas_durum === "1" ? "selected" : ""; ?>>YOK</option>
            <option value="0" <?php echo $pas_durum === "0" ? "selected" : ""; ?>>VAR</option>
            <option value="2" <?php echo $pas_durum === "2" ? "selected" : ""; ?>>DEĞERLENDİRME DIŞI</option>
        </select>
    </td>
    <td style="min-width: 110px;">
        <select required name="kilit_durum[]" class="form-control custom-select-status">
            <option value="1" <?php echo $kilit_durum === "1" ? "selected" : ""; ?>>UYGUN</option>
            <option value="0" <?php echo $kilit_durum === "0" ? "selected" : ""; ?>>UYGUN DEĞİL</option>
            <option value="2" <?php echo $kilit_durum === "2" ? "selected" : ""; ?>>DEĞERLENDİRME DIŞI</option>
        </select>
    </td>
    <td style="min-width: 110px;">
        <select required name="hortum_durum[]" class="form-control custom-select-status">
            <option value="1" <?php echo $hortum_durum === "1" ? "selected" : ""; ?>>UYGUN</option>
            <option value="0" <?php echo $hortum_durum === "0" ? "selected" : ""; ?>>UYGUN DEĞİL</option>
            <option value="2" <?php echo $hortum_durum === "2" ? "selected" : ""; ?>>DEĞERLENDİRME DIŞI</option>
        </select>
    </td>
    <td style="min-width: 90px;">
        <input required type="text" autocomplete="off" class="form-control text-center" name="basinc_degeri[]" value="<?php echo htmlspecialchars((string)$basinc_degeri, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Bar">
    </td>
    <td style="min-width: 110px;">
        <select required name="nozul_durum[]" class="form-control custom-select-status">
            <option value="1" <?php echo $nozul_durum === "1" ? "selected" : ""; ?>>UYGUN</option>
            <option value="0" <?php echo $nozul_durum === "0" ? "selected" : ""; ?>>UYGUN DEĞİL</option>
            <option value="2" <?php echo $nozul_durum === "2" ? "selected" : ""; ?>>DEĞERLENDİRME DIŞI</option>
        </select>
    </td>
    <td style="min-width: 160px;">
        <input type="text" autocomplete="off" class="form-control" name="aciklama[]" value="<?php echo htmlspecialchars((string)$aciklama, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Açıklama">
    </td>
</tr>