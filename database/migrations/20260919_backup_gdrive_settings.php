<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

try {
    $settings = [
        'backup_gdrive_folder_id' => '',
        'backup_gdrive_service_account_json' => ''
    ];

    $checkStmt = $ac->prepare("SELECT COUNT(*) FROM `settings` WHERE `var` = ?");
    $insertStmt = $ac->prepare("INSERT INTO `settings` (`var`, `val`) VALUES (?, ?)");

    foreach ($settings as $key => $defaultVal) {
        $checkStmt->execute([$key]);
        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([$key, $defaultVal]);
        }
    }

    echo "Google Drive ayarları veritabanına başarıyla eklendi.\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
