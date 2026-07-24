
<?php
// Hata raporlamayı açmak geliştirme aşamasında faydalıdır.
require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Model'e artık gerek yok, çünkü tüm mantık VIEW'de.
// require_once ROOT . '/App/Model/OfferModel.php'; 

// --- 1. DataTables Parametreleri (Aynı kalıyor) ---
$draw = $_POST['draw'] ?? 0;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search_value = $_POST['search']['value'] ?? '';
$order_column_index = $_POST['order'][0]['column'] ?? 0;
$order_direction = $_POST['order'][0]['dir'] ?? 'desc';


// --- 2. Sıralama için Sütun Eşleştirmesi (DÜZELTİLDİ) ---
// Artık takma adlar (o., c., u.) yok! Sadece VIEW'deki sütun adları var.
$column_map = [
    0 => 'id',
    1 => 'created_at',
    2 => 'offerNumber',
    3 => 'company_name', // VIEW'deki sütun adı
    4 => 'total_price',
    5 => 'durum', // VIEW'deki sütun adı
    6 => 'onay_tarihi',
    7 => 'offer_subject',
    8 => 'payment_period',
    9 => 'creator_name', // VIEW'deki sütun adı
];
$order_column_name = $column_map[$order_column_index] ?? 'id';

$base_table = "view_offers"; // Artık tüm sorgular bu tabloyu kullanacak.

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

// --- BÖLÜM A: Genel Arama (DÜZELTİLDİ) ---
if (!empty($search_value)) {
    $search_param = "%{$search_value}%";
    $global_search_conditions = [];
    // VIEW'deki sütun adlarını kullanıyoruz.
    $searchable_columns = ['offerNumber', 'company_name', 'offer_subject', 'creator_name', 'durum'];
    
    foreach ($searchable_columns as $col) {
        $global_search_conditions[] = "$col LIKE ?";
        $params[] = $search_param;
    }
    
    if(!empty($global_search_conditions)){
        $where_conditions[] = "(" . implode(' OR ', $global_search_conditions) . ")";
    }
}

// --- BÖLÜM B: Sütuna Özel Arama (DÜZELTİLDİ) ---
$columns_post = $_POST['columns'] ?? [];
foreach ($columns_post as $index => $column_data) {
    if (isset($column_data['search']['value']) && !empty($column_data['search']['value'])) {
        if (isset($column_map[$index])) {
            $column_name = $column_map[$index];
            $val = $column_data['search']['value'];
            if ($column_name === 'created_at') {
                $vsql = ddmmyyyy_to_sql($val);
                $val = ($vsql !== '' ? $vsql : $val);
            }
            $where_conditions[] = "$column_name LIKE ?";
            $params[] = "%" . $val . "%";
        }
    }
}

// --- BÖLÜM C: Form Filtreleri (Yeni) ---
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



// --- Final WHERE Cümlesi (Aynı kalıyor) ---
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = " WHERE " . implode(' AND ', $where_conditions);
}

//eğer sablonları göster 1 ise
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
// --- 4. Toplam Kayıt Sayılarını Al (DÜZELTİLDİ) ---

// Filtresiz toplam kayıt sayısı
$total_records_query = $ac->query("SELECT COUNT(id) FROM $base_table");
$recordsTotal = $total_records_query->fetchColumn();

// Filtrelenmiş kayıt sayısı - ARTIK VIEW'DEN SAYIYOR
$filtered_records_query = $ac->prepare("SELECT COUNT(id) FROM $base_table " . $where_clause);
$filtered_records_query->execute($params);
$recordsFiltered = $filtered_records_query->fetchColumn();


// --- 5. Asıl Veriyi Çek (DÜZELTİLDİ) ---
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

// --- 6. Çıktıyı Formatlama (DÜZELTİLDİ) ---
$data = [];
$sirano = $start + 1;

foreach ($results as $of) {
    // Para birimi, tarih vb. aynı...
    
    // Durum Badge'i
    $durum_badge = $of["statu"] == 2 
        ? "<span class='badge badge-success' data-tooltip='".$of['durum']."'>".$of['durum']."</span>" 
        : "<span class='badge badge-warning' data-tooltip='".$of['durum']."'>".$of['durum']."</span>";

   
    // İşlem Butonları (HTML'i burada oluşturuyoruz)
    // NOT: permtrue() gibi session bazlı fonksiyonların burada çalışabilmesi için 
    if(($of["is_template"] == 1 && checkAuth("template_offer_edit")) || ($of["is_template"] == 0 && checkAuth("offeredit"))) {
        $islem_butonlari = '
        <a type="button" href="index.php?p=offers/offer-manage&id=' . $of["id"] . '" class="btn btn-sm btn-outline-primary" data-tooltip="Düzenle"><i class="fa fa-pencil"></i></a>';
    }
    else{
        $islem_butonlari = '';
    }


      if(($of["is_template"] == 1 && checkAuth("offertemplatedel")) || ($of["is_template"] == 0 && checkAuth("offerdelete"))) {
            $islem_butonlari .= '<button type="button" class="btn btn-sm btn-danger teklif-sil" data-id="' . $of["id"] . '" data-tooltip="Sil"><i class="fa fa-trash"></i></button>
        ';
    }

   
    $islem_butonlari .= '<div class="dropdown d-inline">
            <button class="btn btn-secondary btn-sm" type="button" data-toggle="dropdown"><i class="fa fa-ellipsis-v ml-1 mr-1"></i></button>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail">
                <a href="index.php?p=offer-view&id=' . $of["id"] . '" target="_blank" class="dropdown-item" type="button"><i class="fa fa-file-text-o mr-2"></i> Standart Teklifi Göster</a>
                <a href="index.php?p=offer-view&id=' . $of["id"] . '&summary=false" target="_blank" class="dropdown-item" type="button"><i class="fa fa-copy mr-2"></i> Toplamsız Şablonu Göster</a>
                <a href="index.php?p=offer-view&id=' . $of["id"] . '&all_currency=true" target="_blank" class="dropdown-item" type="button"><i class="fa fa-copy mr-2"></i> Çoklu Döviz Şablonunu Göster</a>
                <a href="index.php?p=offer-view&id=' . $of["id"] . '&proforma=true" target="_blank" class="dropdown-item" type="button"><i class="fa fa-copy mr-2"></i> Proforma Göster</a>
                ';
       
     
        if (checkAuth("mailandsmssend")) { 
            $islem_butonlari .= '<a href="index.php?p=report-send-as-mail&type=offer&id=<?php echo $offer->id ?>"
                class="dropdown-item" type="button">
                <i class="fa fa-envelope-o mr-2"></i>
                Mail Gönder</a>';
         };
       if (checkAuth("offercopy") && $of["is_template"] == 0) { 
             $islem_butonlari .= '<a href="#" class="dropdown-item offer-copy" type="button"
                data-id="' . $of["id"] . '">
                <i class="fa fa-copy mr-2"></i>
                Teklifi Kopyala</a>';
        }
         
        //Şablon teklif ise ve kopyalama yetkisi varsa butonu göster
        if ($of["is_template"] == 1 && checkAuth("template_offer_copy")) {    
            $islem_butonlari .= '<a href="#" class="dropdown-item offer-copy" type="button"
               data-id="' . $of["id"] . '">
               <i class="fa fa-copy mr-2"></i>
               Teklifi Kopyala</a>';

        }

        $islem_butonlari .='</div>
        </div>';
    
    $customerName = htmlspecialchars(shorted($of["company_name"], 40));
    $customerCell = !empty($of["customer_deleted_at"])
        ? '<span class="text-muted">' . $customerName . ' <small class="badge badge-secondary">Silinmiş</small></span>'
        : '<a href="index.php?p=customers/manage&id=' . $of["customer_id"] . '">' . $customerName . '</a>';

    // Data dizisine satırı ekle
    $data[] = [
        "sira_no"       => $sirano,
        "islem_tarihi"  => (!empty($of["created_at"]) ? (new DateTime($of["created_at"]))->format('d.m.Y H:i') : ''),
        "teklif_no"     => htmlspecialchars($of['offerNumber']),
        "musteri"       => $customerCell,
//"toplam_tutar"  => tlFormat($of["total_price"] ?? 0) . " " . ($of["currency"] == "TRY" ? "₺" : ($of["currency"] == "dollar" ? "$" : "€")),
        "toplam_tutar"  => "₺ " . tlFormat($of["tl_toplam_karsilik"] ?? 0) ,
        "durum"         => $durum_badge,
        "onay_tarihi"   => $of["onay_tarihi"],
        "konusu"        => htmlspecialchars($of['offer_subject']),
        "odeme_vadesi"  => htmlspecialchars($of['payment_period']),
        "teklif_veren"  => htmlspecialchars($of['creator_name']), // VIEW'den gelen doğru sütun adı
        "islem"         => $islem_butonlari
    ];

    $sirano++;
}

// --- 7. Final JSON Çıktısı (Aynı kalıyor) ---
$response = [
    "draw" => intval($draw),
    "recordsTotal" => intval($recordsTotal),
    "recordsFiltered" => intval($recordsFiltered),
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
