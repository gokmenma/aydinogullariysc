<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Helper\Financial;

$draw = $_POST['draw'] ?? 0;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search_value = $_POST['search']['value'] ?? '';
$order_column_index = $_POST['order'][0]['column'] ?? 0;
$order_direction = $_POST['order'][0]['dir'] ?? 'desc';

$column_map = [
    0 => 'id', // sira no placeholder
    2 => 'offerNumber',
    3 => 'company_name',
    4 => 'created_at',
    5 => 'stokKodu',
    6 => 'title',
    7 => 'amount',
    8 => 'saleprice',
    9 => 'total_price',
    12 => 'durum'
];

$order_column_name = $column_map[$order_column_index] ?? 'om.id';
if ($order_column_name == 'id') { $order_column_name = 'om.id'; }

$base_table = "offermatters om JOIN view_offers vo ON om.oid = vo.id";

$where_conditions = [];
$params = [];

function ddmmyyyy_to_sql($s){
    $s = trim((string)$s);
    if ($s === '') return '';
    $s = str_replace(['/', '-'], '.', $s);
    $parts = explode('.', $s);
    if (count($parts) === 3) {
        $d = (int)$parts[0]; $m = (int)$parts[1]; $y = (int)$parts[2];
        if ($y > 0 && $m > 0 && $d > 0) { return sprintf('%04d-%02d-%02d', $y, $m, $d); }
    }
    return '';
}

function parse_number_value($val) {
    $str = trim((string)$val);
    if ($str === '') return null;
    $str = str_replace(['₺', '$', '€', ' '], '', $str);
    if (strpos($str, '.') !== false && strpos($str, ',') !== false) {
        $str = str_replace('.', '', $str);
        $str = str_replace(',', '.', $str);
    } elseif (strpos($str, ',') !== false) {
        $str = str_replace(',', '.', $str);
    }
    $clean = preg_replace('/[^0-9.-]/', '', $str);
    return is_numeric($clean) ? (float)$clean : null;
}

// Sadece şablon olmayan gerçek tekliflerin öğeleri
$where_conditions[] = "vo.is_template = 0";

if (!empty($search_value)) {
    $search_param = "%{$search_value}%";
    $where_conditions[] = "(vo.offerNumber LIKE ? OR vo.company_name LIKE ? OR om.stokKodu LIKE ? OR om.title LIKE ?)";
    $params[] = $search_param; $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
}

function apply_column_filter($column_name, $raw_val, &$where_conditions, &$params) {
    $raw_val = trim((string)$raw_val);
    if ($raw_val === '') return;

    $is_date = in_array($column_name, ['created_at', 'vo.created_at', 'om.created_at']);
    $is_num = in_array($column_name, ['id', 'om.id', 'amount', 'om.amount', 'saleprice', 'om.saleprice', 'total_price', 'om.total_price']);

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
                    if ($is_num || (isset($json['type']) && $json['type'] === 'number')) {
                        $clean_digits = preg_replace('/[^0-9]/', '', $val);
                        if ($clean_digits !== '') {
                            $rule_conds[] = "(CAST($column_name AS CHAR) LIKE ? OR ($column_name >= ? AND $column_name < ?))";
                            $params[] = "%{$clean_digits}%";
                            $params[] = (float)$clean_digits;
                            $params[] = (float)($clean_digits + 1.0);
                        }
                    } else {
                        $rule_conds[] = "$column_name LIKE ?";
                        $params[] = "%{$val}%";
                    }
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
                        $num = parse_number_value($val);
                        if ($num !== null) {
                            $has_decimals = (strpos($val, '.') !== false || strpos($val, ',') !== false);
                            if ($has_decimals) {
                                $rule_conds[] = "ROUND($column_name, 2) = ?";
                                $params[] = round($num, 2);
                            } else {
                                $rule_conds[] = "($column_name >= ? AND $column_name < ?)";
                                $params[] = (float)$num;
                                $params[] = (float)($num + 1.0);
                            }
                        }
                    } else {
                        $rule_conds[] = "$column_name LIKE ?";
                        $params[] = $val;
                    }
                } elseif ($op === 'gt' || $op === 'after') {
                    if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                        $rule_conds[] = "DATE($column_name) > ?";
                        $params[] = $val;
                    } elseif ($is_num || (isset($json['type']) && $json['type'] === 'number')) {
                        $num = parse_number_value($val);
                        if ($num !== null) {
                            $rule_conds[] = "$column_name > ?";
                            $params[] = $num;
                        }
                    } else {
                        $rule_conds[] = "$column_name > ?";
                        $params[] = $val;
                    }
                } elseif ($op === 'lt' || $op === 'before') {
                    if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                        $rule_conds[] = "DATE($column_name) < ?";
                        $params[] = $val;
                    } elseif ($is_num || (isset($json['type']) && $json['type'] === 'number')) {
                        $num = parse_number_value($val);
                        if ($num !== null) {
                            $rule_conds[] = "$column_name < ?";
                            $params[] = $num;
                        }
                    } else {
                        $rule_conds[] = "$column_name < ?";
                        $params[] = $val;
                    }
                } elseif ($op === 'gte') {
                    if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                        $rule_conds[] = "DATE($column_name) >= ?";
                        $params[] = $val;
                    } elseif ($is_num || (isset($json['type']) && $json['type'] === 'number')) {
                        $num = parse_number_value($val);
                        if ($num !== null) {
                            $rule_conds[] = "$column_name >= ?";
                            $params[] = $num;
                        }
                    } else {
                        $rule_conds[] = "$column_name >= ?";
                        $params[] = $val;
                    }
                } elseif ($op === 'lte') {
                    if ($is_date || (isset($json['type']) && $json['type'] === 'date')) {
                        $rule_conds[] = "DATE($column_name) <= ?";
                        $params[] = $val;
                    } elseif ($is_num || (isset($json['type']) && $json['type'] === 'number')) {
                        $num = parse_number_value($val);
                        if ($num !== null) {
                            $rule_conds[] = "$column_name <= ?";
                            $params[] = $num;
                        }
                    } else {
                        $rule_conds[] = "$column_name <= ?";
                        $params[] = $val;
                    }
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

// Sütun Bazlı Arama (Header Inputs)
$columns_post = $_POST['columns'] ?? [];
foreach ($columns_post as $idx => $cdata) {
    if (!empty($cdata['search']['value']) && isset($column_map[$idx])) {
        $col = $column_map[$idx];
        if (in_array($col, ['offerNumber', 'company_name', 'created_at', 'durum'])) {
            $col = "vo." . $col;
        } else if (in_array($col, ['stokKodu', 'title', 'amount', 'saleprice', 'total_price'])) {
            $col = "om." . $col;
        }
        apply_column_filter($col, $cdata['search']['value'], $where_conditions, $params);
    }
}

$filters = $_POST['filters'] ?? [];
if (!empty($filters)) {
    if (!empty($filters['offer_no'])) {
        $where_conditions[] = "vo.offerNumber LIKE ?";
        $params[] = "%" . $filters['offer_no'] . "%";
    }
    if (!empty($filters['company'])) {
        $where_conditions[] = "vo.company_name LIKE ?";
        $params[] = "%" . $filters['company'] . "%";
    }
    if (!empty($filters['contact'])) {
        $where_conditions[] = "vo.company_authors LIKE ?";
        $params[] = "%" . $filters['contact'] . "%";
    }
    if (!empty($filters['stok_kodu'])) {
        $where_conditions[] = "om.stokKodu LIKE ?";
        $params[] = "%" . $filters['stok_kodu'] . "%";
    }
    if (!empty($filters['urun_adi'])) {
        $where_conditions[] = "om.title LIKE ?";
        $params[] = "%" . $filters['urun_adi'] . "%";
    }
    if (!empty($filters['representative'])) {
        $where_conditions[] = "vo.creator_name LIKE ?";
        $params[] = "%" . $filters['representative'] . "%";
    }
    if (!empty($filters['status'])) {
        $where_conditions[] = "vo.durum LIKE ?";
        $params[] = "%" . $filters['status'] . "%";
    }
    if (!empty($filters['currency'])) {
        $where_conditions[] = "om.salecur = ?";
        $params[] = $filters['currency'];
    }
    if (!empty($filters['description'])) {
        $where_conditions[] = "(om.title LIKE ? OR vo.description LIKE ?)";
        $params[] = "%" . $filters['description'] . "%";
        $params[] = "%" . $filters['description'] . "%";
    }
    $date_start = ddmmyyyy_to_sql($filters['date_start'] ?? '');
    $date_end   = ddmmyyyy_to_sql($filters['date_end'] ?? '');
    if (!empty($date_start) && !empty($date_end)) {
        $where_conditions[] = "DATE(vo.created_at) BETWEEN ? AND ?";
        $params[] = $date_start; $params[] = $date_end;
    } elseif (!empty($date_start)) {
        $where_conditions[] = "DATE(vo.created_at) >= ?";
        $params[] = $date_start;
    } elseif (!empty($date_end)) {
        $where_conditions[] = "DATE(vo.created_at) <= ?";
        $params[] = $date_end;
    }
}

$where_clause = " WHERE " . implode(' AND ', $where_conditions);

$total_records_query = $ac->query("SELECT COUNT(om.id) FROM offermatters om JOIN offers o ON om.oid = o.id WHERE o.is_template = 0");
$recordsTotal = $total_records_query->fetchColumn();

$filtered_records_query = $ac->prepare("SELECT COUNT(om.id) FROM $base_table $where_clause");
$filtered_records_query->execute($params);
$recordsFiltered = $filtered_records_query->fetchColumn();

$sql = "SELECT om.*, vo.offerNumber, vo.company_name, vo.customer_id, vo.created_at, vo.tl_alt_toplam, vo.dolar_alt_toplam, vo.euro_alt_toplam, vo.tl_iskonto, vo.dolar_iskonto, vo.euro_iskonto, vo.Kdv, vo.durum, vo.statu 
        FROM $base_table $where_clause 
        ORDER BY $order_column_name " . strtoupper($order_direction) . " LIMIT ? OFFSET ?";

$stmt = $ac->prepare($sql);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p, PDO::PARAM_STR); }
$stmt->bindValue($i++, (int)$length, PDO::PARAM_INT);
$stmt->bindValue($i++, (int)$start, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

function format_curr($val, $curr) {
    $s = number_format((float)$val, 2, ',', '.');
    if ($curr == 'TRY') $curr = 'TL';
    return $s . ' ' . $curr;
}

$data = [];
$sirano = $start + 1;

foreach ($results as $r) {
    $item_total = (float)$r['total_price'];
    $curr = $r['salecur'];
    $discount = 0;
    
    // Iskonto Orantılama
    if ($curr == 'TRY' || $curr == 'TL') {
        $sub = (float)($r['tl_alt_toplam'] ?? 0);
        $disc_total = (float)($r['tl_iskonto'] ?? 0);
        if ($sub > 0) $discount = ($item_total / $sub) * $disc_total;
    } elseif ($curr == 'USD' || $curr == 'dollar') {
        $sub = (float)($r['dolar_alt_toplam'] ?? 0);
        $disc_total = (float)($r['dolar_iskonto'] ?? 0);
        if ($sub > 0) $discount = ($item_total / $sub) * $disc_total;
    } elseif ($curr == 'EUR' || $curr == 'euro') {
        $sub = (float)($r['euro_alt_toplam'] ?? 0);
        $disc_total = (float)($r['euro_iskonto'] ?? 0);
        if ($sub > 0) $discount = ($item_total / $sub) * $disc_total;
    }
    
    $kdv_rate = (float)($r['Kdv'] ?? 0);
    $item_net = $item_total - $discount;
    $item_kdv = $item_net * ($kdv_rate / 100);
    $item_grand_total = $item_net + $item_kdv;
    
    if ($r["statu"] == 2) {
        $durum_badge = "<span class='badge badge-success'>".$r['durum']."</span>";
    } elseif ($r["statu"] == 3) {
        $durum_badge = "<span class='badge badge-danger'>".$r['durum']."</span>";
    } else {
        $durum_badge = "<span class='badge badge-warning'>".$r['durum']."</span>";
    }
        
    $actions = '
        <a href="index.php?p=offer-view&id='.$r['oid'].'" target="_blank" class="btn btn-sm btn-outline-secondary" data-tooltip="Göster"><i class="fa fa-eye"></i></a>
    ';

    $data[] = [
        "sira_no" => $sirano++,
        "islemler" => $actions,
        "teklif_no" => htmlspecialchars($r['offerNumber']),
        "firma" => '<a href="index.php?p=customers/manage&id='.$r['customer_id'].'">'.htmlspecialchars($r['company_name']).'</a>',
        "tarih" => (!empty($r["created_at"]) ? (new DateTime($r["created_at"]))->format('d.m.Y') : ''),
        "stok_kodu" => htmlspecialchars($r['stokKodu'] ?? '-'),
        "urun_adi" => htmlspecialchars($r['title']),
        "miktar" => floatval($r['amount']) . ' ' . htmlspecialchars($r['unit']),
        "birim_fiyat" => format_curr($r['saleprice'], $curr),
        "tutar" => format_curr($item_total, $curr),
        "iskonto" => format_curr($discount, $curr),
        "kdv" => format_curr($item_kdv, $curr),
        "toplam" => format_curr($item_grand_total, $curr),
        "durum" => $durum_badge
    ];
}

echo json_encode([
    "draw" => intval($draw),
    "recordsTotal" => intval($recordsTotal),
    "recordsFiltered" => intval($recordsFiltered),
    "data" => $data
]);
exit;
