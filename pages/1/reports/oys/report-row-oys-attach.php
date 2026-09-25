<?php
$attach_description = $attach_description ?? '';
$report_attach = $report_attach ?? '';
?>
<tr>
    <td class="text-center">
        <button type="button" class="btn btn-delete-row sil" title="Dosyayı Kaldır">
            <i class="fa fa-trash"></i>
        </button>
    </td>
    <td>
        <input required type="text" class="form-control" name="attach_description[]" value="<?php echo htmlspecialchars($attach_description, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Dosya / Belge Açıklaması">
    </td>
    <td>
        <?php if (isset($type) && $type == "edit") { ?>
            <button type="button" class="btn btn-sm btn-success">Görüntüle</button>
        <?php } else { ?>
            <input required type="file" class="form-control form-control-sm" name="report_attach[]">
        <?php } ?>
    </td>
</tr>