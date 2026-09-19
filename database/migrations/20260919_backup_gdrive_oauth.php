<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

try {
    $settings = [
        'backup_gdrive_client_id' => '',
        'backup_gdrive_client_secret' => '',
        'backup_gdrive_refresh_token' => '',
        'backup_gdrive_access_token' => ''
    ];

    $checkStmt = $ac->prepare("SELECT COUNT(*) FROM `settings` WHERE `var` = ?");
    $insertStmt = $ac->prepare("INSERT INTO `settings` (`var`, `val`) VALUES (?, ?)");

    foreach ($settings as $key => $defaultVal) {
        $checkStmt->execute([$key]);
        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([$key, $defaultVal]);
        }
    }

    echo "Google Drive OAuth 2.0 ayarları veritabanına başarıyla eklendi.\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
