<?php
/**
 * Migration: Add backupdelete authority
 */

return function (PDO $db) {
    // 1. authority kaydı
    $stmt = $db->prepare("SELECT id FROM authority WHERE authName = ?");
    $stmt->execute(['backupdelete']);
    $auth = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$auth) {
        $insertStmt = $db->prepare("INSERT INTO authority (authName, authTitle, authGroup, authValue, isActive) VALUES (?, ?, 1, 1, 1)");
        $insertStmt->execute(['backupdelete', 'Sistem Yedeklerini Silme']);
        $authId = (int)$db->lastInsertId();
    } else {
        $authId = (int)$auth['id'];
    }

    // 2. Admin (roleID = 1) yetkilendirme
    if ($authId > 0) {
        $checkStmt = $db->prepare("SELECT 1 FROM userauths WHERE roleID = 1 AND authID = ?");
        $checkStmt->execute([$authId]);
        if (!$checkStmt->fetch()) {
            $db->prepare("INSERT INTO userauths (roleID, authID) VALUES (1, ?)")->execute([$authId]);
        }
    }
};
