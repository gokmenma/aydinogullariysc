<?php
$tabindex = $tabindex ?? 0;
$testno = $testno ?? '';
$kg = $kg ?? '';
$cinsi = $cinsi ?? '';
$imalatci_firma = $imalatci_firma ?? '';
$imal_tarihi = $imal_tarihi ?? '';
$serino = $serino ?? '';
$tse_belgesi = isset($tse_belgesi) ? (string)$tse_belgesi : '1';
$yuzey_durumu = isset($yuzey_durumu) ? (string)$yuzey_durumu : '1';
$sizdirmazlik_deneyi = isset($sizdirmazlik_deneyi) ? (string)$sizdirmazlik_deneyi : '1';
$esneme_deneyi = isset($esneme_deneyi) ? (string)$esneme_deneyi : '1';
$things = $things ?? '';
?>
<tr tabindex="<?php echo (int)$tabindex; ?>">
    <td class="text-center align-middle" style="width: 45px;">
        <button type="button" class="sil btn btn-sm btn-delete-row" title="Satırı Sil">
            <i class="fa fa-trash"></i>
        </button>
    </td>
    <td style="min-width: 90px;">
        <input required type="text" class="form-control font-weight-bold text-center" name="testno[]" value="<?php echo htmlspecialchars($testno, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Test No">
    </td>
    <td style="min-width: 80px;">
        <input type="text" class="form-control text-center" name="kg[]" value="<?php echo htmlspecialchars($kg, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Kg">
    </td>
    <td style="min-width: 110px;">
        <input type="text" required autocomplete="off" class="form-control" name="cinsi[]" value="<?php echo htmlspecialchars($cinsi, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cinsi">
    </td>
    <td style="min-width: 140px;">
        <input type="text" required autocomplete="off" class="form-control" name="imalatci_firma[]" value="<?php echo htmlspecialchars($imalatci_firma, ENT_QUOTES, 'UTF-8'); ?>" placeholder="İmalatçı Firma">
    </td>
    <td style="min-width: 95px;">
        <input type="text" required autocomplete="off" class="form-control text-center imal" name="imal_tarihi[]" value="<?php echo htmlspecialchars($imal_tarihi, ENT_QUOTES, 'UTF-8'); ?>" placeholder="İmal Tarihi">
    </td>
    <td style="min-width: 110px;">
        <input type="text" required autocomplete="off" class="form-control text-center" name="serino[]" value="<?php echo htmlspecialchars($serino, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Seri No">
    </td>
    <td style="min-width: 100px;">
        <select required name="tse_belgesi[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option value="1" <?php echo $tse_belgesi === "1" ? "selected" : ""; ?>>VAR</option>
            <option value="0" <?php echo $tse_belgesi === "0" ? "selected" : ""; ?>>YOK</option>
        </select>
    </td>
    <td style="min-width: 110px;">
        <select required name="yuzey_durumu[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option value="1" <?php echo $yuzey_durumu === "1" ? "selected" : ""; ?>>OLUMLU</option>
            <option value="0" <?php echo $yuzey_durumu === "0" ? "selected" : ""; ?>>OLUMSUZ</option>
        </select>
    </td>
    <td style="min-width: 120px;">
        <select required name="sizdirmazlik_deneyi[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option value="1" <?php echo $sizdirmazlik_deneyi === "1" ? "selected" : ""; ?>>VAR</option>
            <option value="0" <?php echo $sizdirmazlik_deneyi === "0" ? "selected" : ""; ?>>YOK</option>
        </select>
    </td>
    <td style="min-width: 110px;">
        <select required name="esneme_deneyi[]" class="form-control custom-select-status">
            <option value="">Seçiniz</option>
            <option value="1" <?php echo $esneme_deneyi === "1" ? "selected" : ""; ?>>OLUMLU</option>
            <option value="0" <?php echo $esneme_deneyi === "0" ? "selected" : ""; ?>>OLUMSUZ</option>
        </select>
    </td>
    <td style="min-width: 180px;">
        <input type="text" autocomplete="off" class="form-control" name="things[]" value="<?php echo htmlspecialchars($things, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Düşünceler / Not">
    </td>
</tr>