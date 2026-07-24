<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$columns = $ac->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('deleted_at', $columns, true)) {
    $ac->exec("ALTER TABLE customers ADD deleted_at DATETIME NULL DEFAULT NULL");
}
if (!in_array('deleted_by', $columns, true)) {
    $ac->exec("ALTER TABLE customers ADD deleted_by INT NULL DEFAULT NULL");
}

$permission = $ac->prepare("SELECT id FROM authority WHERE authName = ? LIMIT 1");
$permission->execute(['customerexport']);
$permissionId = $permission->fetchColumn();

if (!$permissionId) {
    $insert = $ac->prepare(
        "INSERT INTO authority (authName, authTitle, authGroup, authValue, isActive)
         VALUES (?, ?, ?, ?, ?)"
    );
    $insert->execute(['customerexport', "Firmaları Excel'e Aktar", 1, 1, 1]);
    $permissionId = $ac->lastInsertId();
}

// Yeni yetki ilk kurulumda yalnızca Admin rolüne verilir.
$grant = $ac->prepare(
    "INSERT INTO userauths (roleID, authID)
     SELECT 1, ?
     WHERE NOT EXISTS (
         SELECT 1 FROM userauths WHERE roleID = 1 AND authID = ?
     )"
);
$grant->execute([$permissionId, $permissionId]);

echo "Customer safety migration completed.\n";
