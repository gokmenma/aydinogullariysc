<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Server-side DataTable processing
header('Content-Type: application/json');

// Log request for debugging
file_put_contents('logs/api_debug.log', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

try {
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 100;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

    file_put_contents('logs/api_debug.log', date('Y-m-d H:i:s') . " - draw=$draw, start=$start, length=$length\n", FILE_APPEND);

    // Check if $ac is available
    global $ac;
    if (!$ac) {
        file_put_contents('logs/api_debug.log', date('Y-m-d H:i:s') . " - ERROR: \$ac is null\n", FILE_APPEND);
        throw new Exception('Database connection failed - $ac is null');
    }

    file_put_contents('logs/api_debug.log', date('Y-m-d H:i:s') . " - Database connected successfully\n", FILE_APPEND);

    // Build WHERE clause for search
    $whereClause = '';
    if (!empty($search)) {
        $search = '%' . $search . '%';
        $whereClause = " WHERE c.company LIKE :search OR c.email LIKE :search OR c.gsm LIKE :search OR cg.title LIKE :search";
    }

    // Get total records count
    $totalQuery = $ac->prepare("SELECT COUNT(*) as total FROM customers c");
    $totalQuery->execute();
    $totalRecords = $totalQuery->fetch(PDO::FETCH_ASSOC)['total'];

    // Get filtered records count
    $filteredQuery = $ac->prepare("
        SELECT COUNT(*) as total FROM customers c
        LEFT JOIN cgroups cg ON c.grp = cg.id
        $whereClause
    ");
    if (!empty($search)) {
        $filteredQuery->bindParam(':search', $search);
    }
    $filteredQuery->execute();
    $filteredRecords = $filteredQuery->fetch(PDO::FETCH_ASSOC)['total'];

    // Get paginated data with JOIN for counts
    $query = $ac->prepare("
        SELECT 
            c.id, 
            c.company, 
            c.grp, 
            c.represant, 
            c.email, 
            c.gsm,
            cg.title as group_title,
            COALESCE(offers_count.cnt, 0) as offer_count,
            COALESCE(projects_count.cnt, 0) as project_count
        FROM customers c
        LEFT JOIN cgroups cg ON c.grp = cg.id
        LEFT JOIN (SELECT cid, COUNT(*) as cnt FROM offers GROUP BY cid) offers_count ON c.id = offers_count.cid
        LEFT JOIN (SELECT pcid, COUNT(*) as cnt FROM projects GROUP BY pcid) projects_count ON c.id = projects_count.pcid
        $whereClause
        ORDER BY c.id DESC
        LIMIT :start, :length
    ");

    if (!empty($search)) {
        $query->bindParam(':search', $search);
    }
    $query->bindParam(':start', $start, PDO::PARAM_INT);
    $query->bindParam(':length', $length, PDO::PARAM_INT);
    $query->execute();
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    // Format data for DataTable
    $records = [];
    foreach ($data as $row) {
        $tps = $row["offer_count"] . " / " . $row["project_count"];
        
        // Action buttons HTML - simplified without permission checks for API
        $actions = '<a href="index.php?p=customers/manage&id=' . $row["id"] . '" data-tooltip="Görüntüle-Düzenle">
            <span class="btn btn-sm btn-outline-info"><i class="fa fa-pencil"></i></span></a>
            <a href="#" data-tooltip="Sil"
                onClick="deleteRecord(\'Devam ettiğiniz takdirde, müşteriye ait tüm bilgiler ve müşterinin adına düzenlenmiş olan teklif & projeler tamamen silinecektir. Devam etmek istiyor musunuz?\',\'' . $row['id'] . '\',\'customers\')">
                <span class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></span></a>';
        
        $records[] = [
            $row["id"],
            '<a href="index.php?p=customers/manage&id=' . $row["id"] . '" data-toggle="tooltip" data-tooltip="' . htmlspecialchars($row["company"]) . '">' . shorted($row["company"], 40) . '</a>',
            htmlspecialchars($row["group_title"] ?? ''),
            htmlspecialchars($row["represant"]),
            $tps,
            htmlspecialchars($row["email"]),
            htmlspecialchars($row["gsm"]),
            $actions
        ];
    }

    $columnCounts = [];
    try {
        $stGrp = $ac->query("SELECT cg.title, COUNT(*) as cnt FROM customers c LEFT JOIN cgroups cg ON c.grp = cg.id WHERE cg.title IS NOT NULL AND cg.title != '' GROUP BY cg.id, cg.title ORDER BY cnt DESC");
        if ($stGrp) { $columnCounts[2] = $stGrp->fetchAll(PDO::FETCH_KEY_PAIR); }
        $stRep = $ac->query("SELECT c.represant, COUNT(*) as cnt FROM customers c WHERE c.represant IS NOT NULL AND c.represant != '' GROUP BY c.represant ORDER BY cnt DESC");
        if ($stRep) { $columnCounts[3] = $stRep->fetchAll(PDO::FETCH_KEY_PAIR); }
    } catch (Exception $e) {}

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $filteredRecords,
        "data" => $records,
        "columnCounts" => $columnCounts
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage(),
        "draw" => isset($draw) ? $draw : 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => []
    ]);
}
