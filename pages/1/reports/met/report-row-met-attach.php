<?php
$attach_id = $files["id"] ?? ($file_id ?? "");
$attach_description = $attach_description ?? ($files["fileDescription"] ?? "");
$filename = $filename ?? ($files["filename"] ?? "");
$row_type = isset($id) || isset($files["id"]) ? "edit" : "new";
?>
<tr>
    <td class="text-center align-middle" style="width: 45px;">
        <button type="button" class="sil btn btn-sm btn-delete-row" title="Satırı Sil">
            <i class="fa fa-trash"></i>
        </button>
    </td>
    <td style="min-width: 250px;">
        <input required type="text" autocomplete="off" class="form-control" name="attach_description[]" value="<?php echo htmlspecialchars($attach_description, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Dosya Açıklaması">
    </td>
    <td style="min-width: 200px;">
        <?php if ($row_type == "edit" && !empty($filename)) { ?>
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-truncate font-12 text-muted mr-2" style="max-width: 220px;" title="<?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fa fa-paperclip mr-1 text-primary"></i><?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <a href="files/reports/<?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-xs btn-outline-info font-11 px-2 py-1" style="border-radius: 6px;">
                    <i class="fa fa-eye mr-1"></i> Görüntüle
                </a>
            </div>
            <input type="hidden" name="existing_files[]" value="<?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>">
        <?php } else { ?>
            <input required type="file" class="form-control font-12" name="report_attach[]" style="padding: 3px 6px; height: 32px;">
        <?php } ?>
    </td>
</tr>