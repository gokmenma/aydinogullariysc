<?php
// new-file sayfası iptal edilerek all-files üzerindeki modal yapısına devredilmiştir.
if (headers_sent()) {
    echo "<script>window.location.href = 'index.php?p=all-files&open_upload=1';</script>";
} else {
    header("Location: index.php?p=all-files&open_upload=1");
}
exit;
