<?php
namespace App\Model;

use PDO;
use Exception;
use PDOException;
use App\Model\BaseModel;
use App\Helper\Helper;


class OfferModel extends BaseModel 
{

    protected $table = 'offers';
    protected $productTable = 'offermatters';

    public function __construct()
    {
        parent::__construct($this->table);
    }


    /**
     * Teklifleri Listelemek için firma adıyla birlikte getirir
     * 
     */
    public function getOffersWithCompanyName($sablonlari_goster = false)

    {
        $condition = $sablonlari_goster ? "WHERE o.is_template = 1" : "WHERE o.is_template = 0";
        $sql = $this->db->prepare("SELECT 
                                            o.*,  
                                            c.company as company_name, 
                                            c.id as customer_id,
                                            u.username as creator_name,
                                            CASE
                                                WHEN o.statu = 1 THEN 'Bekliyor'
                                                WHEN o.statu = 2 then 'Tamamlandı'
                                            END AS durum
                                        FROM 
                                            $this->table o
                                        LEFT JOIN 
                                            customers c ON o.cid = c.id
                                        LEFT JOIN 
                                            users u ON o.creativer = u.id");
                                               
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function saveOfferProduct($data)
    {
        $this->table = $this->productTable;
        return $this->save($data);
    }

    public function getOfferProducts($id)
    {
        $this->table = $this->productTable;
        $sql = $this->db->prepare("SELECT * FROM $this->table where oid = ?");
        $sql->execute([$id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function deleteOfferProduct($id)
    {
        $this->table = $this->productTable;
        $sql = $this->db->prepare("DELETE FROM $this->table where oid = ?");
        $sql->execute([$id]);
        return $sql->rowCount();
    }

    //Teklifi Kopyala
    public function copyOffer($id)
    {

        $offer = $this->find($id);
        //Offer'ın tüm alanlarını döngüyle al
        foreach ($offer as $key => $value) {
            //id ve created_at hariç diğer alanları yeni offer'a ekle
            if ($key != 'id' && $key != 'created_at' && $key != 'offerNumber') {
                $data[$key] = $value;
            } else if ($key == 'offerNumber') {
                //Teklif numarasını oluştur
                $data[$key] = Helper::generateNumber('offer', 'TK');
            }
            //is_template alanını 0 yap
            $data['is_template'] = 0;

            //oluşturan kullanıcıyı al
            $data['creativer'] = $_SESSION["lid"];

            //tarihi bugn yap
            $data['created_at'] = date('Y-m-d H:i:s');

            //Güncelleme tarihini de bugün yap
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        //Offer'ı kaydet
        $newOfferId = $this->save($data);
        Helper::setDefineNumber('offer');

        //Offer'a ait ürünleri al
        $offerProducts = $this->getOfferProducts($id);
        //Ürünleri döngüyle al,oid alanına yeni offer'ın id'sini ekle
        foreach ($offerProducts as $product) {
            //id ve created_at hariç diğer alanları yeni offer'a ekle
            foreach ($product as $key => $value) {
                if ($key != 'id' && $key != 'created_at' && $key != 'oid') {
                    $productData[$key] = $value;
                } else if ($key == 'oid') {
                    $productData[$key] = $newOfferId;
                }
            }
            //Ürünü kaydet
            $this->saveOfferProduct($productData);
        }
    }

    //convertToTry
    public function convertToTry($id)
    {
        $offer = $this->find($id);

        // offer yoksa hata döndür
        if (!$offer) {
            $status = 'error';
            $message = 'Teklif bulunamadı.';

            $res = [
                'status' => $status,
                'message' => $message
            ];
            return json_encode($res);
        }


        $offerProducts = $this->getOfferProducts($id);
        $alt_toplam = 0;
        foreach ($offerProducts as $product) {
            // Para birimi TL ise devam et
            if ($product->salecur == 'TRY') {
                $alt_toplam += $product->total_price;
                continue;
            }


            $currency = $product->salecur == "EUR" ? $offer->curEuro : $offer->curDollar;
            //Ürünün alış fiyatını TL'ye çevir
            $buyprice = $product->buyprice * $currency;

            // Ürünün satış fiyatını TL'ye çevir
            $saleprice = $product->saleprice * $currency;

            //Alt Toplam Hesapla
            $satır_toplam = $product->amount * $saleprice;
            $data = [
                "id" => $product->id,
                'buyprice' => $buyprice,
                'buycur' => "TRY",
                "saleprice" => $saleprice,
                "salecur" => 'TRY',
                "total_price" => $satır_toplam,

            ];


            $this->saveOfferProduct($data);

            //Alt toplamı hesapla
            $alt_toplam += $satır_toplam;
        }

        //Teklifin toplamını güncelle
        $iskonto = ($offer->euro_iskonto * $offer->curEuro) + ($offer->dolar_iskonto * $offer->curDollar) + ($offer->tl_iskonto);
        $kdv = ($offer->euro_kdv * $offer->curEuro) + ($offer->dolar_kdv * $offer->curDollar) + ($offer->tl_kdv);
        $tl_toplam = $alt_toplam + $kdv - $iskonto;
        $data = [
            "id" => $id,
            'euro_ara_toplam' => 0,
            'euro_alt_toplam' => 0,
            'dolar_ara_toplam' => 0,
            'dolar_alt_toplam' => 0,
            'tl_alt_toplam' => $alt_toplam,
            "euro_kdv" => 0,
            "dolar_kdv" => 0,
            "tl_kdv" => $kdv,
            "euro_kdvli_toplam" => 0,
            "dolar_kdvli_toplam" => 0,
            "tl_kdvli_toplam" => $tl_toplam,
            "tl_toplam_karsilik" => $tl_toplam,
        ];

        $this->table = 'offers';
        $this->save($data);


        $res = [
            'status' => 'success',
            'message' => 'Teklif TRY\'ye çevrildi.'
        ];
        return json_encode($res);
    }


    /*Bekleyen ve tamamlanan teklif sayılarını döndürür 
    * return int
    */
    public function getOfferCountWaitingAndDone()
    {
        $sql = $this->db->prepare("SELECT
                                            COUNT(CASE WHEN statu = 1 THEN 1 END) AS bekleyen_teklif,
                                            COUNT(CASE WHEN statu = 2 THEN 1 END) AS tamamlanan_teklif
                                        FROM offers;");
        $sql->execute();
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    /* Teklif Silme
    * return int
    */
    public function deleteOffer($id)
    {
        try {
            $this->db->beginTransaction();

            // Teklifin ürünlerini sil
            $this->deleteOfferProduct($id);
            // Teklifi sil
            $sql = $this->db->prepare("DELETE FROM offers WHERE id = :id");
            $sql->bindParam(':id', $id, PDO::PARAM_INT);
            $sql->execute();
            // Eğer silme işlemi başarılıysa commit et
            $this->db->commit();
            // Silinen satır sayısını döndür
            return $sql->rowCount();
        } catch (PDOException $ex) {
            // Hata durumunda rollback yap
            $this->db->rollBack();
            // Hata mesajını döndür
            throw new Exception("Teklif silinirken hata oluştu: " . $ex->getMessage());
        }
    }

    /*Teklfifin durumunu getir 
    * @param int $id Teklif ID'si
    * @return int 1: Bekliyor, 2: Tamamlandı
    */
    public function getOfferStatus($id)
    {
        $sql = $this->db->prepare("SELECT statu FROM $this->table WHERE id = :id");
        $sql->bindParam(':id', $id, PDO::PARAM_INT);
        $sql->execute();
        $result = $sql->fetch(PDO::FETCH_OBJ);
        
        if ($result) {
            return (int)$result->statu;
        } else {
            throw new Exception("Teklif bulunamadı.");
        }
    }

    //Teklif Numarası Var mı Kontrol
    public function checkOfferNumberExists($offerNumber, $excludeId = 0)
    {
        if ($excludeId > 0) {
            $sql = $this->db->prepare("SELECT COUNT(*) as count 
                                              FROM $this->table 
                                              WHERE offerNumber = :offerNumber AND id != :excludeId");
            $sql->bindParam(':offerNumber', $offerNumber, PDO::PARAM_STR);
            $sql->bindParam(':excludeId', $excludeId, PDO::PARAM_INT);
        } else {
            $sql = $this->db->prepare("SELECT COUNT(*) as count 
                                          FROM $this->table 
                                          WHERE offerNumber = :offerNumber");
            $sql->bindParam(':offerNumber', $offerNumber, PDO::PARAM_STR);
        }
        
        $sql->execute();
        $result = $sql->fetch(PDO::FETCH_OBJ);
        
        return $result->count > 0;
    }

    /**
     * Teklif Dashboard KPI Özet İstatistikleri
     * 
     * @param string|null $startDate YYYY-MM-DD
     * @param string|null $endDate YYYY-MM-DD
     * @return object
     */
    public function getDashboardSummary($startDate = null, $endDate = null)
    {
        $where = ["is_template = 0"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(created_at) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(created_at) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    COUNT(*) as total_count,
                    COUNT(CASE WHEN statu = 1 THEN 1 END) as pending_count,
                    COUNT(CASE WHEN statu = 2 THEN 1 END) as won_count,
                    COALESCE(SUM(tl_toplam_karsilik), SUM(total_price), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN statu = 1 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as pending_amount,
                    COALESCE(SUM(CASE WHEN statu = 2 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as won_amount,
                    COALESCE(AVG(COALESCE(tl_toplam_karsilik, total_price, 0)), 0) as avg_amount
                FROM offers 
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_OBJ);

        // Kazanma / Başarı Oranı
        $summary->win_rate = $summary->total_count > 0 
            ? round(($summary->won_count / $summary->total_count) * 100, 1) 
            : 0;

        // Bu ayki metrikler
        $thisMonthStart = date('Y-m-01');
        $thisMonthEnd = date('Y-m-t');
        $stmtThisMonth = $this->db->prepare("SELECT 
                                                COUNT(*) as count,
                                                COUNT(CASE WHEN statu = 2 THEN 1 END) as won_count,
                                                COALESCE(SUM(tl_toplam_karsilik), SUM(total_price), 0) as amount,
                                                COALESCE(SUM(CASE WHEN statu = 2 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as won_amount
                                            FROM offers 
                                            WHERE is_template = 0 AND DATE(created_at) BETWEEN ? AND ?");
        $stmtThisMonth->execute([$thisMonthStart, $thisMonthEnd]);
        $summary->this_month = $stmtThisMonth->fetch(PDO::FETCH_OBJ);

        // Geçen ayki metrikler
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
        $stmtLastMonth = $this->db->prepare("SELECT 
                                                COUNT(*) as count,
                                                COALESCE(SUM(tl_toplam_karsilik), SUM(total_price), 0) as amount 
                                            FROM offers 
                                            WHERE is_template = 0 AND DATE(created_at) BETWEEN ? AND ?");
        $stmtLastMonth->execute([$lastMonthStart, $lastMonthEnd]);
        $summary->last_month = $stmtLastMonth->fetch(PDO::FETCH_OBJ);

        // Aylık büyüme oranı
        if ($summary->last_month->count > 0) {
            $summary->month_growth_rate = round((($summary->this_month->count - $summary->last_month->count) / $summary->last_month->count) * 100, 1);
        } else {
            $summary->month_growth_rate = $summary->this_month->count > 0 ? 100 : 0;
        }

        return $summary;
    }

    /**
     * En çok teklif verilen firmalar
     * 
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopCustomers($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["o.is_template = 0"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(o.created_at) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(o.created_at) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int) $limit;

        $sql = "SELECT 
                    o.cid,
                    COALESCE(c.company, 'Bilinmeyen Firma') as company_name,
                    c.city,
                    c.sector,
                    c.deleted_at,
                    COUNT(o.id) as total_offers,
                    COUNT(CASE WHEN o.statu = 2 THEN 1 END) as won_offers,
                    COUNT(CASE WHEN o.statu = 1 THEN 1 END) as pending_offers,
                    COALESCE(SUM(COALESCE(o.tl_toplam_karsilik, o.total_price, 0)), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN o.statu = 2 THEN COALESCE(o.tl_toplam_karsilik, o.total_price, 0) ELSE 0 END), 0) as won_amount
                FROM offers o
                LEFT JOIN customers c ON o.cid = c.id
                WHERE {$whereClause}
                GROUP BY o.cid, c.company, c.city, c.sector, c.deleted_at
                ORDER BY total_offers DESC, total_amount DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($results as $row) {
            $row->win_rate = $row->total_offers > 0 
                ? round(($row->won_offers / $row->total_offers) * 100, 1) 
                : 0;
        }

        return $results;
    }

    /**
     * En çok teklif hazırlayan personeller / kullanıcılar
     * 
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopUsers($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["o.is_template = 0"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(o.created_at) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(o.created_at) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int) $limit;

        $sql = "SELECT 
                    o.creativer as user_id,
                    COALESCE(u.username, 'Bilinmeyen Kullanıcı') as username,
                    u.Unvan as user_title,
                    u.avatar_link,
                    COUNT(o.id) as total_offers,
                    COUNT(CASE WHEN o.statu = 2 THEN 1 END) as won_offers,
                    COUNT(CASE WHEN o.statu = 1 THEN 1 END) as pending_offers,
                    COALESCE(SUM(COALESCE(o.tl_toplam_karsilik, o.total_price, 0)), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN o.statu = 2 THEN COALESCE(o.tl_toplam_karsilik, o.total_price, 0) ELSE 0 END), 0) as won_amount
                FROM offers o
                LEFT JOIN users u ON o.creativer = u.id
                WHERE {$whereClause}
                GROUP BY o.creativer, u.username, u.Unvan, u.avatar_link
                ORDER BY total_offers DESC, total_amount DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($results as $row) {
            $row->win_rate = $row->total_offers > 0 
                ? round(($row->won_offers / $row->total_offers) * 100, 1) 
                : 0;
        }

        return $results;
    }

    /**
     * Son 12 ayın veya belirli periyodun aylık trendleri
     * 
     * @param int $monthsCount
     * @return array
     */
    public function getMonthlyTrends($monthsCount = 12)
    {
        $monthsCount = (int)$monthsCount;
        $startDate = date('Y-m-01', strtotime("-{$monthsCount} months +1 month"));

        $sql = "SELECT 
                    LEFT(created_at, 7) as ym,
                    COUNT(*) as total_offers,
                    COUNT(CASE WHEN statu = 2 THEN 1 END) as won_offers,
                    COUNT(CASE WHEN statu = 1 THEN 1 END) as pending_offers,
                    COALESCE(SUM(COALESCE(tl_toplam_karsilik, total_price, 0)), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN statu = 2 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as won_amount
                FROM offers
                WHERE is_template = 0 AND created_at >= ?
                GROUP BY LEFT(created_at, 7)
                ORDER BY ym ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // İndeksli harita yapalım
        $map = [];
        foreach ($rows as $r) {
            if (!empty($r['ym'])) {
                $map[$r['ym']] = $r;
            }
        }

        $turkishMonthsShort = [
            '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
            '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
            '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
        ];

        $trendData = [
            'categories' => [],
            'total_offers' => [],
            'won_offers' => [],
            'pending_offers' => [],
            'total_amount' => [],
            'won_amount' => []
        ];

        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $parts = explode('-', $ym);
            $label = ($turkishMonthsShort[$parts[1]] ?? $parts[1]) . ' ' . substr($parts[0], 2);

            $trendData['categories'][] = $label;
            if (isset($map[$ym])) {
                $trendData['total_offers'][] = (int)$map[$ym]['total_offers'];
                $trendData['won_offers'][] = (int)$map[$ym]['won_offers'];
                $trendData['pending_offers'][] = (int)$map[$ym]['pending_offers'];
                $trendData['total_amount'][] = round((float)$map[$ym]['total_amount'], 2);
                $trendData['won_amount'][] = round((float)$map[$ym]['won_amount'], 2);
            } else {
                $trendData['total_offers'][] = 0;
                $trendData['won_offers'][] = 0;
                $trendData['pending_offers'][] = 0;
                $trendData['total_amount'][] = 0;
                $trendData['won_amount'][] = 0;
            }
        }

        return $trendData;
    }

    /**
     * Teklif durumlarına göre adet ve tutar dağılımı
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getStatusDistribution($startDate = null, $endDate = null)
    {
        $where = ["is_template = 0"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(created_at) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(created_at) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    statu,
                    CASE 
                        WHEN statu = 1 THEN 'Bekliyor' 
                        WHEN statu = 2 THEN 'Tamamlandı / Onaylandı' 
                        ELSE 'Diğer' 
                    END as status_title,
                    COUNT(*) as count,
                    COALESCE(SUM(COALESCE(tl_toplam_karsilik, total_price, 0)), 0) as amount
                FROM offers
                WHERE {$whereClause}
                GROUP BY statu
                ORDER BY statu ASC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * En son oluşturulan teklifler
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentOffersSummary($limit = 10)
    {
        $limit = (int)$limit;
        $sql = "SELECT 
                    o.id,
                    o.offerNumber,
                    o.offer_subject,
                    o.created_at,
                    o.statu,
                    COALESCE(o.tl_toplam_karsilik, o.total_price, 0) as amount,
                    c.id as customer_id,
                    COALESCE(c.company, 'Bilinmeyen Firma') as company_name,
                    u.username as creator_name
                FROM offers o
                LEFT JOIN customers c ON o.cid = c.id
                LEFT JOIN users u ON o.creativer = u.id
                WHERE o.is_template = 0
                ORDER BY o.id DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * En yüksek tutarlı teklifler
     * 
     * @param int $limit
     * @return array
     */
    public function getTopValueOffers($limit = 5)
    {
        $limit = (int)$limit;
        $sql = "SELECT 
                    o.id,
                    o.offerNumber,
                    o.offer_subject,
                    o.created_at,
                    o.statu,
                    COALESCE(o.tl_toplam_karsilik, o.total_price, 0) as amount,
                    c.id as customer_id,
                    COALESCE(c.company, 'Bilinmeyen Firma') as company_name,
                    u.username as creator_name
                FROM offers o
                LEFT JOIN customers c ON o.cid = c.id
                LEFT JOIN users u ON o.creativer = u.id
                WHERE o.is_template = 0
                ORDER BY amount DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
