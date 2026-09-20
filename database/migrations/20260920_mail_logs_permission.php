<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';

try {
    // 1. Yetkileri authority tablosuna ekle
    $authorities = [
        [
            'authName' => 'mail-logs-view',
            'authTitle' => 'Mail Kayıtlarını Görüntüle',
            'authGroup' => 12,
            'authValue' => 1,
            'isActive' => 1
        ],
        [
            'authName' => 'mail-logs-delete',
            'authTitle' => 'Mail Kayıtlarını Sil',
            'authGroup' => 12,
            'authValue' => 1,
            'isActive' => 1
        ]
    ];

    foreach ($authorities as $auth) {
        $checkStmt = $ac->prepare("SELECT id FROM `authority` WHERE `authName` = ?");
        $checkStmt->execute([$auth['authName']]);
        $existingId = $checkStmt->fetchColumn();

        if (!$existingId) {
            $insertStmt = $ac->prepare("INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([
                $auth['authName'],
                $auth['authTitle'],
                $auth['authGroup'],
                $auth['authValue'],
                $auth['isActive']
            ]);
            $existingId = $ac->lastInsertId();
            echo "Eklendi: {$auth['authName']} (ID: {$existingId})\n";
        } else {
            echo "Zaten var: {$auth['authName']} (ID: {$existingId})\n";
        }

        // Admin rolüne ata (roleID = 1)
        $checkAuthStmt = $ac->prepare("SELECT 1 FROM `userauths` WHERE `roleID` = 1 AND `authID` = ?");
        $checkAuthStmt->execute([$existingId]);
        if (!$checkAuthStmt->fetchColumn()) {
            $insertAuthStmt = $ac->prepare("INSERT INTO `userauths` (`roleID`, `authID`) VALUES (1, ?)");
            $insertAuthStmt->execute([$existingId]);
            echo "Admin rolüne atandı: {$auth['authName']}\n";
        }
    }

    echo "Migration başarıyla tamamlandı.\n";
} catch (\PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
