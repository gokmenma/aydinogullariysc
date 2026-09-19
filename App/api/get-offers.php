<?php
// Hata raporlamayı açmak geliştirme aşamasında faydalıdır.
require_once dirname(__DIR__, 2) . '/bootstrap.php';

// --- 1. DataTables Parametreleri ---
$draw = $_POST['draw'] ?? 0;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search_value = $_POST['search']['value'] ?? '';
$order_column_index = $_POST['order'][0]['column'] ?? 0;
$order_direction = $_POST['order'][0]['dir'] ?? 'desc';


// --- 2. Sıralama için Sütun Eşleştirmesi ---
$column_map = [
    0 => 'id',
    1 => 'created_at',
    2 => 'offerNumber',
    3 => 'company_name',
    4 => 'total_price',
    5 => 'durum',
    6 => 'onay_tarihi',
    7 => 'offer_subject',
    8 => 'payment_period',
    9 => 'creator_name',
];
$order_column_name = $column_map[$order_column_index] ?? 'id';

$base_table = "view_offers";

$where_conditions = [];
$params = [];

function ddmmyyyy_to_sql($s){
    $s = trim((string)$s);
    if ($s === '') return '';
    $s = str_replace(['/', '-'], '.', $s);
    $parts = explode('.', $s);
    if (count($parts) === 3) {
        $d = (int)$parts[0];
        $m = (int)$parts[1];
        $y = (int)$parts[2];
        if ($y > 0 && $m > 0 && $d > 0) {
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
    }
    return '';
}

// --- BÖLÜM A: Genel Arama ---
if (!empty($search_value)) {
    $search_param = "%{$search_value}%";
    $global_search_conditions = [];
    $searchable_columns = ['offerNumber', 'company_name', 'offer_subject', 'creator_name', 'durum'];
    
    foreach ($searchable_columns as $col) {
        $global_search_conditions[] = "$col LIKE ?";
        $params[] = $search_param;
    }
    
    if(!empty($global_search_conditions)){
        $where_conditions[] = "(" . implode(' OR ', $global_search_conditions) . ")";
    }
}

function apply_column_filter($column_name, $raw_val, &$where_conditions, &$params) {
    $raw_val = trim((string)$raw_val);
    if ($raw_val === '') return;

    $is_date = in_array($column_name, ['created_at', 'onay_tarihi']);
    $is_num = in_array($column_name, ['id', 'total_price']);

    // Check if JSON from TableFilter
    $json = json_decode($raw_val, true);
    if (is_array($json) && (isset($json['rules']) || isset($json['values']))) {
        if (isset($json['values']) && is_array($json['values']) && !empty($json['values'])) {
            $in_clauses = [];
            foreach ($json['values'] as $v) {
                $in_clauses[] = "$column_name = ?";
                $params[] = $v;
            }
            if (!empty($in_clauses)) {
                $where_conditions[] = "(" . implode(' OR ', $in_clauses) . ")";
            }
            return;
        }

        if (isset($json['rules']) && is_array($json['rules']) && !empty($json['rules'])) {
            $logic = (isset($json['logic']) && strtolower($json['logic']) === 'or') ? ' OR ' : ' AND ';
            $rule_conds = [];

            foreach ($json['rules'] as $rule) {
                $op = $rule['operator'] ?? 'contains';
                $val = trim((string)($rule['value'] ?? ''));

                if ($op === 'empty') {
                    $rule_conds[] = "($column_name IS NULL OR $column_name = '')";
                    continue;
                }
                if ($op === 'not_empty') {
                    $rule_conds[] = "($column_name IS NOT NULL AND $column_name != '')";
                    continue;
                }

                if ($val === '') continue;

                if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                    $vsql = ddmmyyyy_to_sql($val);
                    $val = ($vsql !== '' ? $vsql : $val);
                }

                if ($op === 'contains') {
                    $rule_conds[] = "$column_name LIKE ?";
                    $params[] = "%{$val}%";
                } elseif ($op === 'not_contains') {
                    $rule_conds[] = "$column_name NOT LIKE ?";
                    $params[] = "%{$val}%";
                } elseif ($op === 'starts') {
                    $rule_conds[] = "$column_name LIKE ?";
                    $params[] = "{$val}%";
                } elseif ($op === 'ends') {
                    $rule_conds[] = "$column_name LIKE ?";
                    $params[] = "%{$val}";
                } elseif ($op === 'equals') {
                    if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                        $rule_conds[] = "DATE($column_name) = ?";
                        $params[] = $val;
                    } elseif ($is_num || (isset($json['type']) && $json['type'] === 'number')) {
                        $rule_conds[] = "$column_name = ?";
                        $params[] = (float)$val;
                    } else {
                        $rule_conds[] = "$column_name LIKE ?";
                        $params[] = $val;
                    }
                } elseif ($op === 'gt' || $op === 'after') {
                    if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                        $rule_conds[] = "DATE($column_name) > ?";
                    } else {
                        $rule_conds[] = "$column_name > ?";
                    }
                    $params[] = $val;
                } elseif ($op === 'lt' || $op === 'before') {
                    if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                        $rule_conds[] = "DATE($column_name) < ?";
                    } else {
                        $rule_conds[] = "$column_name < ?";
                    }
                    $params[] = $val;
                } elseif ($op === 'gte') {
                    if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                        $rule_conds[] = "DATE($column_name) >= ?";
                    } else {
                        $rule_conds[] = "$column_name >= ?";
                    }
                    $params[] = $val;
                } elseif ($op === 'lte') {
                    if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                        $rule_conds[] = "DATE($column_name) <= ?";
                    } else {
                        $rule_conds[] = "$column_name <= ?";
                    }
                    $params[] = $val;
                }
            }

            if (!empty($rule_conds)) {
                $where_conditions[] = "(" . implode($logic, $rule_conds) . ")";
            }
            return;
        }
    }

    // Fallback: simple text or pipe-separated regex
    $val = $raw_val;
    if ($is_date) {
        $vsql = ddmmyyyy_to_sql($val);
        $val = ($vsql !== '' ? $vsql : $val);
    }

    if (strpos($val, '|') !== false) {
        $parts = explode('|', $val);
        $or_parts = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $or_parts[] = "$column_name LIKE ?";
                $params[] = "%{$p}%";
            }
        }
        if (!empty($or_parts)) {
            $where_conditions[] = "(" . implode(' OR ', $or_parts) . ")";
        }
    } else {
        $where_conditions[] = "$column_name LIKE ?";
        $params[] = "%" . $val . "%";
    }
}

// --- BÖLÜM B: Sütuna Özel Arama ---
$columns_post = $_POST['columns'] ?? [];
foreach ($columns_post as $index => $column_data) {
    if (isset($column_data['search']['value']) && $column_data['search']['value'] !== '') {
        if (isset($column_map[$index])) {
            $column_name = $column_map[$index];
            apply_column_filter($column_name, $column_data['search']['value'], $where_conditions, $params);
        }
    }
}

// --- BÖLÜM C: Form Filtreleri ---
$filters = $_POST['filters'] ?? [];
if (!empty($filters)) {
    if (!empty($filters['offer_no'])) {
        $where_conditions[] = "offerNumber LIKE ?";
        $params[] = "%" . $filters['offer_no'] . "%";
    }
    if (!empty($filters['company'])) {
        $where_conditions[] = "company_name LIKE ?";
        $params[] = "%" . $filters['company'] . "%";
    }
    if (!empty($filters['subject'])) {
        $where_conditions[] = "offer_subject LIKE ?";
        $params[] = "%" . $filters['subject'] . "%";
    }
    if (!empty($filters['creator'])) {
        $where_conditions[] = "creator_name LIKE ?";
        $params[] = "%" . $filters['creator'] . "%";
    }
    if (!empty($filters['payment_period'])) {
        $where_conditions[] = "payment_period LIKE ?";
        $params[] = "%" . $filters['payment_period'] . "%";
    }
    if (!empty($filters['status'])) {
        $where_conditions[] = "durum LIKE ?";
        $params[] = "%" . $filters['status'] . "%";
    }
    if (!empty($filters['currency'])) {
        $where_conditions[] = "currency = ?";
        $params[] = $filters['currency'];
    }
    $date_start = ddmmyyyy_to_sql($filters['date_start'] ?? '');
    $date_end   = ddmmyyyy_to_sql($filters['date_end'] ?? '');
    if (!empty($date_start) && !empty($date_end)) {
        $where_conditions[] = "DATE(created_at) BETWEEN ? AND ?";
        $params[] = $date_start;
        $params[] = $date_end;
    } elseif (!empty($date_start)) {
        $where_conditions[] = "DATE(created_at) >= ?";
        $params[] = $date_start;
    } elseif (!empty($date_end)) {
        $where_conditions[] = "DATE(created_at) <= ?";
        $params[] = $date_end;
    }
    // Toplam aralığı
    $total_min = $filters['total_min'] ?? '';
    $total_max = $filters['total_max'] ?? '';
    if ($total_min !== '' && $total_min !== null) {
        $where_conditions[] = "total_price >= ?";
        $params[] = $total_min;
    }
    if ($total_max !== '' && $total_max !== null) {
        $where_conditions[] = "total_price <= ?";
        $params[] = $total_max;
    }
}

// --- Final WHERE Cümlesi ---
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = " WHERE " . implode(' AND ', $where_conditions);
}

// eğer sablonları göster 1 ise
$sablonlari_goster = isset($_GET['sablon']) && $_GET['sablon'] == '1';

if ($sablonlari_goster) {
    if (!empty($where_clause)) {
        $where_clause .= " AND is_template = 1";
    } else {
        $where_clause = " WHERE is_template = 1";
    }
} else {
    if (!empty($where_clause)) {
        $where_clause .= " AND is_template = 0";
    } else {
        $where_clause = " WHERE is_template = 0";
    }
}

// --- 4. Toplam Kayıt Sayılarını Al ---
$total_records_query = $ac->query("SELECT COUNT(id) FROM $base_table");
$recordsTotal = $total_records_query->fetchColumn();

// Filtrelenmiş kayıt sayısı - VIEW'DEN SAYIYOR
$filtered_records_query = $ac->prepare("SELECT COUNT(id) FROM $base_table " . $where_clause);
$filtered_records_query->execute($params);
$recordsFiltered = $filtered_records_query->fetchColumn();

// --- 5. Asıl Veriyi Çek ---
$data_query_sql = "SELECT vo.*,
                           (SELECT c.deleted_at
                            FROM customers c
                            WHERE c.id = vo.customer_id
                            LIMIT 1) AS customer_deleted_at
                    FROM $base_table vo "
                    . $where_clause . " "
                    . "ORDER BY " . $order_column_name . " " . strtoupper($order_direction) . " "
                    . "LIMIT ? OFFSET ?";

$data_query = $ac->prepare($data_query_sql);

$i = 1;
foreach ($params as $param) {
    $data_query->bindValue($i, $param, PDO::PARAM_STR);
    $i++;
}
$data_query->bindValue($i, (int)$length, PDO::PARAM_INT);
$i++;
$data_query->bindValue($i, (int)$start, PDO::PARAM_INT);

$data_query->execute();
$results = $data_query->fetchAll(PDO::FETCH_ASSOC);

// --- 6. Çıktıyı Formatlama ---
$data = [];
$sirano = $start + 1;

foreach ($results as $of) {
    // Durum Badge'i
    $durum_badge = $of["statu"] == 2 
        ? "<span class='badge badge-success' data-tooltip='".$of['durum']."'>".$of['durum']."</span>" 
        : "<span class='badge badge-warning' data-tooltip='".$of['durum']."'>".$of['durum']."</span>";

    // İşlem Butonları
    if(($of["is_template"] == 1 && checkAuth("template_offer_edit")) || ($of["is_template"] == 0 && checkAuth("offeredit"))) {
        $islem_butonlari = '
        <a type="button" href="index.php?p=offers/offer-manage&id=' . $of["id"] . '" class="btn btn-sm btn-outline-primary" data-tooltip="Düzenle"><i class="fa fa-pencil"></i></a>';
    }
    else{
        $islem_butonlari = '';
    }

    if(($of["is_template"] == 1 && checkAuth("offertemplatedel")) || ($of["is_template"] == 0 && checkAuth("offerdelete"))) {
        $islem_butonlari .= '<button type="button" class="btn btn-sm btn-danger teklif-sil" data-id="' . $of["id"] . '" data-tooltip="Sil"><i class="fa fa-trash"></i></button>';
    }

    $islem_butonlari .= '<div class="dropdown d-inline">
            <button class="btn btn-secondary btn-sm" type="button" data-toggle="dropdown"><i class="fa fa-ellipsis-v ml-1 mr-1"></i></button>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail">
                <a href="index.php?p=offer-view&id=' . $of["id"] . '" target="_blank" class="dropdown-item" type="button"><i class="fa fa-file-text-o mr-2"></i> Standart Teklifi Göster</a>
                <a href="index.php?p=offer-view&id=' . $of["id"] . '&summary=false" target="_blank" class="dropdown-item" type="button"><i class="fa fa-copy mr-2"></i> Toplamsız Şablonu Göster</a>
                <a href="index.php?p=offer-view&id=' . $of["id"] . '&all_currency=true" target="_blank" class="dropdown-item" type="button"><i class="fa fa-copy mr-2"></i> Çoklu Döviz Şablonunu Göster</a>
                <a href="index.php?p=offer-view&id=' . $of["id"] . '&proforma=true" target="_blank" class="dropdown-item" type="button"><i class="fa fa-copy mr-2"></i> Proforma Göster</a>';
       
    if (checkAuth("mailandsmssend")) { 
        $islem_butonlari .= '<a href="index.php?p=report-send-as-mail&type=offer&id=' . $of['id'] . '"
            class="dropdown-item" type="button">
            <i class="fa fa-envelope-o mr-2"></i>
            Mail Gönder</a>';
    }
    if (checkAuth("offercopy") && $of["is_template"] == 0) { 
        $islem_butonlari .= '<a href="#" class="dropdown-item offer-copy" type="button"
            data-id="' . $of["id"] . '">
            <i class="fa fa-copy mr-2"></i>
            Teklifi Kopyala</a>';
    }
     
    if ($of["is_template"] == 1 && checkAuth("template_offer_copy")) {    
        $islem_butonlari .= '<a href="#" class="dropdown-item offer-copy" type="button"
           data-id="' . $of["id"] . '">
           <i class="fa fa-copy mr-2"></i>
           Teklifi Kopyala</a>';
    }

    $islem_butonlari .='</div></div>';
    
    $customerName = htmlspecialchars(shorted($of["company_name"], 40));
    $customerCell = !empty($of["customer_deleted_at"])
        ? '<span class="text-muted">' . $customerName . ' <small class="badge badge-secondary">Silinmiş</small></span>'
        : '<a href="index.php?p=customers/manage&id=' . $of["customer_id"] . '">' . $customerName . '</a>';

    $data[] = [
        "sira_no"       => $sirano,
        "islem_tarihi"  => (!empty($of["created_at"]) ? (new DateTime($of["created_at"]))->format('d.m.Y H:i') : ''),
        "teklif_no"     => htmlspecialchars($of['offerNumber']),
        "musteri"       => $customerCell,
        "toplam_tutar"  => "₺ " . tlFormat($of["tl_toplam_karsilik"] ?? 0),
        "durum"         => $durum_badge,
        "onay_tarihi"   => $of["onay_tarihi"],
        "konusu"        => htmlspecialchars($of['offer_subject']),
        "odeme_vadesi"  => htmlspecialchars($of['payment_period']),
        "teklif_veren"  => htmlspecialchars($of['creator_name']),
        "islem"         => $islem_butonlari
    ];

    $sirano++;
}

// --- 7. Final JSON Çıktısı ---
$response = [
    "draw" => intval($draw),
    "recordsTotal" => intval($recordsTotal),
    "recordsFiltered" => intval($recordsFiltered),
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
exit();
