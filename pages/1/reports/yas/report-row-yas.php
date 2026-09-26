<tr>
    <td class="pl-2">
        <button class="sil btn btn-sm btn-danger"> Sil</button>
    </td>
    <td>
        <input required type="text" class="form-control region" name="marka[]" value="<?php echo $marka ?>">
    </td>
    <td>
        <input required type="text" class="form-control region" name="imalatyili[]" value="<?php echo $imalatyili ?>">
    </td>
    <td>
        <input required type="text" class="form-control region" name="serino[]" value="<?php echo $serino ?>">
    </td>
    <td>
        <input required type="text" class="form-control region" name="cinsi[]" value="<?php echo $cinsi ?>">
    </td>
    <td>
        <input required type="text" class="form-control region" name="bulundugu_bolge[]"
            value="<?php echo $bulundugu_bolge ?>">
    </td>
    <td>
        <?php optionselect("cevre_kontrolu[]", $cevre_kontrolü) ?>

    </td>
    <td>
        <?php optionselect("dis_muhafaza[]", $dis_muhafaza) ?>

    </td>
    <td>
        <?php optionselect("calisabilirlik_testi[]", $calisabilirlik_testi) ?>

    </td>
</tr>