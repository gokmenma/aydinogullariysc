<?php

require_once dirname(__DIR__, 2) . "/bootstrap.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["login"])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Oturum açmanız gerekiyor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function fetchUrlWithUserAgent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AydinogullariYSC-App/1.0 (internal-crm-geocoding)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: tr-TR,tr;q=0.9,en;q=0.8'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    return null;
}

if ($action === 'reverse') {
    $lat = filter_var($_GET['lat'] ?? $_POST['lat'] ?? 0, FILTER_VALIDATE_FLOAT);
    $lng = filter_var($_GET['lng'] ?? $_POST['lng'] ?? 0, FILTER_VALIDATE_FLOAT);

    if ($lat === false || $lng === false) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz koordinatlar.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lng}&addressdetails=1&accept-language=tr";
    $data = fetchUrlWithUserAgent($url);

    if ($data) {
        echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
    } else {
        // Fallback to photon reverse
        $photonUrl = "https://photon.komoot.io/reverse?lat={$lat}&lon={$lng}&lang=default";
        $photonData = fetchUrlWithUserAgent($photonUrl);
        if ($photonData && !empty($photonData['features'])) {
            $prop = $photonData['features'][0]['properties'] ?? [];
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'display_name' => implode(', ', array_filter([
                        $prop['name'] ?? '',
                        $prop['street'] ?? '',
                        $prop['housenumber'] ?? '',
                        $prop['district'] ?? '',
                        $prop['city'] ?? '',
                        $prop['state'] ?? '',
                        $prop['country'] ?? ''
                    ])),
                    'address' => [
                        'road' => $prop['street'] ?? '',
                        'house_number' => $prop['housenumber'] ?? '',
                        'suburb' => $prop['district'] ?? '',
                        'city' => $prop['city'] ?? '',
                        'state' => $prop['state'] ?? '',
                        'postcode' => $prop['postcode'] ?? ''
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Adres çözümlenemedi.'], JSON_UNESCAPED_UNICODE);
        }
    }
    exit;
}

if ($action === 'search') {
    $query = trim($_GET['q'] ?? $_POST['q'] ?? '');
    if ($query === '') {
        echo json_encode(['status' => 'error', 'message' => 'Arama ifadesi boş olamaz.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    function searchOsm($term) {
        $term = trim($term);
        if ($term === '' || mb_strlen($term) < 2) return [];
        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($term) . "&countrycodes=tr&addressdetails=1&limit=5&accept-language=tr";
        $res = fetchUrlWithUserAgent($url);
        return is_array($res) ? $res : [];
    }

    // Arama varyasyonları oluştur
    $searchCandidates = [];
    $searchCandidates[] = $query; // 1. Orijinal sorgu

    // Türkçe kısaltmaları genişlet ve temizle
    $expanded = $query;
    $expanded = preg_replace('/\b(no|no:|numara|apt|kat|daire|d:)\s*[\w\d\/-]+/iu', '', $expanded);
    $expanded = preg_replace('/\b\d{5}\b/', '', $expanded); // Posta kodunu temizle (örn: 16159)
    $expanded = str_replace(['/', ',', '-', '.', ':', '  '], ' ', $expanded);
    
    // Kısaltmaları aç
    $expanded = preg_replace('/\bcd\b/iu', 'Caddesi', $expanded);
    $expanded = preg_replace('/\bsk\b/iu', 'Sokağı', $expanded);
    $expanded = preg_replace('/\bmh\b|\bmah\b/iu', 'Mahallesi', $expanded);
    $expanded = preg_replace('/\bblv\b|\bbulv\b/iu', 'Bulvarı', $expanded);
    $expanded = trim(preg_replace('/\s+/', ' ', $expanded));

    if ($expanded !== '' && !in_array($expanded, $searchCandidates)) {
        $searchCandidates[] = $expanded; // 2. Genişletilmiş sorgu
    }

    // Kelimeleri ayrıştırarak önemli kombinasyonları dene (Cadde/Mahalle + İlçe + İl)
    $words = explode(' ', $expanded);
    if (count($words) > 3) {
        // Sondaki 2 kelime genellikle İlçe ve İl'dir (örn: Nilüfer Bursa)
        $cityDistrict = implode(' ', array_slice($words, -2));
        $firstPart = implode(' ', array_slice($words, 0, -2));

        // Cadde + İlçe/İl
        $caddeMatches = [];
        if (preg_match('/([\wğüşıöçĞÜŞİÖÇ]+\s+Caddesi)/iu', $expanded, $caddeMatches)) {
            $searchCandidates[] = $caddeMatches[1] . ' ' . $cityDistrict;
        }
        // Sokak + İlçe/İl
        $sokakMatches = [];
        if (preg_match('/([\wğüşıöçĞÜŞİÖÇ]+\s+Sokağı)/iu', $expanded, $sokakMatches)) {
            $searchCandidates[] = $sokakMatches[1] . ' ' . $cityDistrict;
        }
        // Mahalle + İlçe/İl
        $mahalleMatches = [];
        if (preg_match('/([\wğüşıöçĞÜŞİÖÇ]+\s+Mahallesi)/iu', $expanded, $mahalleMatches)) {
            $searchCandidates[] = $mahalleMatches[1] . ' ' . $cityDistrict;
        }

        $searchCandidates[] = $firstPart . ' ' . $cityDistrict;
        $searchCandidates[] = $cityDistrict; // En kötü ihtimalle ilçe + il
    }

    $results = [];
    foreach ($searchCandidates as $candidate) {
        $results = searchOsm($candidate);
        if (!empty($results)) {
            break;
        }
    }

    // Nominatim sonuç vermezse Photon API ile dene
    if (empty($results)) {
        foreach ($searchCandidates as $candidate) {
            $photonUrl = "https://photon.komoot.io/api/?q=" . urlencode($candidate) . "&limit=5&lang=default";
            $pData = fetchUrlWithUserAgent($photonUrl);
            if (!empty($pData['features'])) {
                $results = [];
                foreach ($pData['features'] as $f) {
                    $coords = $f['geometry']['coordinates'] ?? [0, 0];
                    $prop = $f['properties'] ?? [];
                    $displayName = implode(', ', array_filter([
                        $prop['name'] ?? '',
                        $prop['street'] ?? '',
                        $prop['housenumber'] ?? '',
                        $prop['district'] ?? '',
                        $prop['city'] ?? '',
                        $prop['state'] ?? '',
                        $prop['country'] ?? ''
                    ]));
                    $results[] = [
                        'lat' => $coords[1],
                        'lon' => $coords[0],
                        'display_name' => $displayName,
                        'address' => [
                            'road' => $prop['street'] ?? '',
                            'house_number' => $prop['housenumber'] ?? '',
                            'suburb' => $prop['district'] ?? '',
                            'city' => $prop['city'] ?? '',
                            'state' => $prop['state'] ?? '',
                            'postcode' => $prop['postcode'] ?? ''
                        ]
                    ];
                }
                break;
            }
        }
    }

    if (!empty($results)) {
        echo json_encode(['status' => 'success', 'data' => $results], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Aranan konum bulunamadı.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem.'], JSON_UNESCAPED_UNICODE);
