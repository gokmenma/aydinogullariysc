<?php

require_once '../../configs/config.php';
  
    if(isset($_POST['company_id'])) {
    $company_id = $_POST['company_id'];
    // Şirketin şehir ve ilçe bilgisini al
    $stmt = $ac->prepare("SELECT city, ilce,region FROM customers WHERE id = ?");
    $stmt->execute([$company_id]);
    $result= $stmt->fetch(PDO::FETCH_ASSOC);

    // Şirketin onaylanmış teklif numaralarını al
    $stmt_offers = $ac->prepare("SELECT id, offerNumber FROM offers WHERE cid = ? AND statu = 2");
    $stmt_offers->execute([$company_id]);
    $result['offers'] = $stmt_offers->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
} else {
    // Tüm müşterilerin ID ve şirket adlarını al
    $stmt = $ac->query("SELECT id, company FROM customers");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($companies);
}






?>