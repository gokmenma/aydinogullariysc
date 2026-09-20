<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';

try {
    // 1. mail_logs tablosunu oluştur
    $ac->exec("
        CREATE TABLE IF NOT EXISTS `mail_logs` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `tomail` varchar(255) NOT NULL,
          `from_mail` varchar(100) NOT NULL,
          `mail_file` varchar(255) DEFAULT NULL,
          `mail_body` varchar(10000) DEFAULT NULL,
          `datest` timestamp NOT NULL DEFAULT current_timestamp(),
          `statu` int(11) NOT NULL DEFAULT 1,
          `sender` int(20) NOT NULL DEFAULT 1,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] mail_logs tablosu kontrol edildi.\n";

    // 2. pages tablosuna ekle
    $checkPage = $ac->prepare("SELECT id FROM `pages` WHERE `p_link` = 'mail-logs'");
    $checkPage->execute();
    if (!$checkPage->fetchColumn()) {
        $insPage = $ac->prepare("INSERT INTO `pages` (`p_title`, `p_link`, `pid`) VALUES ('Mail Kayıtları', 'mail-logs', 15)");
        $insPage->execute();
        echo "[OK] pages tablosuna 'mail-logs' eklendi.\n";
    } else {
        echo "[OK] pages tablosunda 'mail-logs' zaten mevcut.\n";
    }

    // 3. authority tablosuna ekle ve userauths'a bağla
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
            echo "[OK] authority eklendi: {$auth['authName']} (ID: {$existingId})\n";
        } else {
            echo "[OK] authority mevcut: {$auth['authName']} (ID: {$existingId})\n";
        }

        // Admin rolüne ata (roleID = 1)
        $checkAuthStmt = $ac->prepare("SELECT 1 FROM `userauths` WHERE `roleID` = 1 AND `authID` = ?");
        $checkAuthStmt->execute([$existingId]);
        if (!$checkAuthStmt->fetchColumn()) {
            $insertAuthStmt = $ac->prepare("INSERT INTO `userauths` (`roleID`, `authID`) VALUES (1, ?)");
            $insertAuthStmt->execute([$existingId]);
            echo "[OK] Admin rolüne atandı: {$auth['authName']}\n";
        } else {
            echo "[OK] Admin rolünde zaten tanımlı: {$auth['authName']}\n";
        }
    }

    echo "\n[BAŞARILI] Kurulum tamamlandı.\n";
} catch (\PDOException $e) {
    echo "[HATA] " . $e->getMessage() . "\n";
}
