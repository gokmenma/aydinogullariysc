<?php
namespace App\Model;

use PDO;
use Exception;
use PDOException;
use App\Model\BaseModel;
use App\Model\ActivityLogModel;
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
                                            WHEN o.statu = 2 THEN 'Tamamlandı'
                                            WHEN o.statu = 3 THEN 'Kabul Edilmedi'
                                            ELSE 'Diğer'
                                        END AS durum
                                    FROM 
                                        $this->table o
                                    LEFT JOIN 
                                        customers c ON o.cid = c.id
                                    LEFT JOIN 
                                        users u ON o.creativer = u.id
                                    $condition");
                                           
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

    // Teklifi Kopyala
    public function copyOffer($id)
    {
        $offer = $this->find($id);
        if (!$offer) {
            return false;
        }

        $data = [];
        // Offer'ın tüm alanlarını döngüyle al
        foreach ($offer as $key => $value) {
            // id ve created_at hariç diğer alanları yeni offer'a ekle
            if ($key != 'id' && $key != 'created_at' && $key != 'offerNumber') {
                $data[$key] = $value;
            } else if ($key == 'offerNumber') {
                // Teklif numarasını oluştur
                $data[$key] = Helper::generateNumber('offer', 'TK');
            }
        }

        // is_template alanını 0 yap
        $data['is_template'] = 0;
        // Kopyalanan teklifin başlangıç durumu Bekliyor olsun
        $data['statu'] = 1;
        $data['reject_reason'] = null;
        $data['reject_detail'] = null;
        $data['reject_date'] = null;
        $data['onay_tarihi'] = null;

        // oluşturan kullanıcıyı al
        $data['creativer'] = $_SESSION["lid"] ?? null;

        // tarihi bugün yap
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Offer'ı kaydet
        $this->table = 'offers';
        $newOfferId = $this->save($data);
        Helper::setDefineNumber('offer');

        // Offer'a ait ürünleri al
        $offerProducts = $this->getOfferProducts($id);
        // Ürünleri döngüyle al, oid alanına yeni offer'ın id'sini ekle
        foreach ($offerProducts as $product) {
            $productData = [];
            foreach ($product as $key => $value) {
                if ($key != 'id' && $key != 'created_at' && $key != 'oid') {
                    $productData[$key] = $value;
                } else if ($key == 'oid') {
                    $productData[$key] = $newOfferId;
                }
            }
            // Ürünü kaydet
            $this->saveOfferProduct($productData);
        }

        return $newOfferId;
    }

    // convertToTry
    public function convertToTry($id)
    {
        $offer = $this->find($id);

        if (!$offer) {
            $res = [
                'status' => 'error',
                'message' => 'Teklif bulunamadı.'
            ];
            return json_encode($res);
        }

        $offerProducts = $this->getOfferProducts($id);
        $alt_toplam = 0;
        foreach ($offerProducts as $product) {
            if ($product->salecur == 'TRY') {
                $alt_toplam += $product->total_price;
                continue;
            }

            $currency = $product->salecur == "EUR" ? $offer->curEuro : $offer->curDollar;
            $buyprice = $product->buyprice * $currency;
            $saleprice = $product->saleprice * $currency;
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
            $alt_toplam += $satır_toplam;
        }

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

    /* Bekleyen ve tamamlanan teklif sayılarını döndürür */
    public function getOfferCountWaitingAndDone()
    {
        $sql = $this->db->prepare("SELECT
                                        COUNT(CASE WHEN statu = 1 THEN 1 END) AS bekleyen_teklif,
                                        COUNT(CASE WHEN statu = 2 THEN 1 END) AS tamamlanan_teklif,
                                        COUNT(CASE WHEN statu = 3 THEN 1 END) AS reddedilen_teklif
                                    FROM offers;");
        $sql->execute();
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    /* Teklif Silme */
    public function deleteOffer($id)
    {
        try {
            $this->db->beginTransaction();

            $this->deleteOfferProduct($id);
            $sql = $this->db->prepare("DELETE FROM offers WHERE id = :id");
            $sql->bindParam(':id', $id, PDO::PARAM_INT);
            $sql->execute();
            $this->db->commit();
            return $sql->rowCount();
        } catch (PDOException $ex) {
            $this->db->rollBack();
            throw new Exception("Teklif silinirken hata oluştu: " . $ex->getMessage());
        }
    }

    /* Teklifin durumunu getir */
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

    // Teklif Numarası Var mı Kontrol
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
                    COUNT(CASE WHEN statu = 3 THEN 1 END) as lost_count,
                    COALESCE(SUM(tl_toplam_karsilik), SUM(total_price), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN statu = 1 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as pending_amount,
                    COALESCE(SUM(CASE WHEN statu = 2 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as won_amount,
                    COALESCE(SUM(CASE WHEN statu = 3 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as lost_amount,
                    COALESCE(AVG(COALESCE(tl_toplam_karsilik, total_price, 0)), 0) as avg_amount
                FROM offers 
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_OBJ);

        // Kazanma / Başarı Oranı & Kayıp Oranı
        $summary->win_rate = $summary->total_count > 0 
            ? round(($summary->won_count / $summary->total_count) * 100, 1) 
            : 0;

        $summary->lost_rate = $summary->total_count > 0 
            ? round(($summary->lost_count / $summary->total_count) * 100, 1) 
            : 0;

        // Bu ayki metrikler
        $thisMonthStart = date('Y-m-01');
        $thisMonthEnd = date('Y-m-t');
        $stmtThisMonth = $this->db->prepare("SELECT 
                                                COUNT(*) as count,
                                                COUNT(CASE WHEN statu = 2 THEN 1 END) as won_count,
                                                COUNT(CASE WHEN statu = 3 THEN 1 END) as lost_count,
                                                COALESCE(SUM(tl_toplam_karsilik), SUM(total_price), 0) as amount,
                                                COALESCE(SUM(CASE WHEN statu = 2 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as won_amount,
                                                COALESCE(SUM(CASE WHEN statu = 3 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as lost_amount
                                            FROM offers 
                                            WHERE is_template = 0 AND DATE(created_at) BETWEEN ? AND ?");
        $stmtThisMonth->execute([$thisMonthStart, $thisMonthEnd]);
        $summary->this_month = $stmtThisMonth->fetch(PDO::FETCH_OBJ);

        // Geçen ayki metrikler
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
        $stmtLastMonth = $this->db->prepare("SELECT 
                                                COUNT(*) as count,
                                                COUNT(CASE WHEN statu = 2 THEN 1 END) as won_count,
                                                COUNT(CASE WHEN statu = 3 THEN 1 END) as lost_count,
                                                COALESCE(SUM(tl_toplam_karsilik), SUM(total_price), 0) as amount,
                                                COALESCE(SUM(CASE WHEN statu = 2 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as won_amount,
                                                COALESCE(SUM(CASE WHEN statu = 3 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as lost_amount
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
     * Kabul edilmeme (ret) nedenlerine göre adet ve tutar dağılımı
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getRejectReasonDistribution($startDate = null, $endDate = null)
    {
        $where = ["is_template = 0", "statu = 3"];
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
                    COALESCE(NULLIF(TRIM(reject_reason), ''), 'Belirtilmedi') as reason,
                    COUNT(*) as count,
                    COALESCE(SUM(COALESCE(tl_toplam_karsilik, total_price, 0)), 0) as amount
                FROM offers
                WHERE {$whereClause}
                GROUP BY reason
                ORDER BY count DESC, amount DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        $totalLostCount = 0;
        $totalLostAmount = 0;
        foreach ($results as $r) {
            $totalLostCount += (int)$r->count;
            $totalLostAmount += (float)$r->amount;
        }

        foreach ($results as $r) {
            $r->percentage = $totalLostCount > 0 ? round(($r->count / $totalLostCount) * 100, 1) : 0;
            $r->amount_percentage = $totalLostAmount > 0 ? round(($r->amount / $totalLostAmount) * 100, 1) : 0;
        }

        return [
            'total_count' => $totalLostCount,
            'total_amount' => $totalLostAmount,
            'items' => $results
        ];
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
                    COUNT(CASE WHEN o.statu = 3 THEN 1 END) as lost_offers,
                    COALESCE(SUM(COALESCE(o.tl_toplam_karsilik, o.total_price, 0)), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN o.statu = 2 THEN COALESCE(o.tl_toplam_karsilik, o.total_price, 0) ELSE 0 END), 0) as won_amount,
                    COALESCE(SUM(CASE WHEN o.statu = 3 THEN COALESCE(o.tl_toplam_karsilik, o.total_price, 0) ELSE 0 END), 0) as lost_amount
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
                    COUNT(CASE WHEN o.statu = 3 THEN 1 END) as lost_offers,
                    COALESCE(SUM(COALESCE(o.tl_toplam_karsilik, o.total_price, 0)), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN o.statu = 2 THEN COALESCE(o.tl_toplam_karsilik, o.total_price, 0) ELSE 0 END), 0) as won_amount,
                    COALESCE(SUM(CASE WHEN o.statu = 3 THEN COALESCE(o.tl_toplam_karsilik, o.total_price, 0) ELSE 0 END), 0) as lost_amount
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
                    COUNT(CASE WHEN statu = 3 THEN 1 END) as lost_offers,
                    COALESCE(SUM(COALESCE(tl_toplam_karsilik, total_price, 0)), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN statu = 2 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as won_amount,
                    COALESCE(SUM(CASE WHEN statu = 3 THEN COALESCE(tl_toplam_karsilik, total_price, 0) ELSE 0 END), 0) as lost_amount
                FROM offers
                WHERE is_template = 0 AND created_at >= ?
                GROUP BY LEFT(created_at, 7)
                ORDER BY ym ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            'lost_offers' => [],
            'total_amount' => [],
            'won_amount' => [],
            'lost_amount' => []
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
                $trendData['lost_offers'][] = (int)($map[$ym]['lost_offers'] ?? 0);
                $trendData['total_amount'][] = round((float)$map[$ym]['total_amount'], 2);
                $trendData['won_amount'][] = round((float)$map[$ym]['won_amount'], 2);
                $trendData['lost_amount'][] = round((float)($map[$ym]['lost_amount'] ?? 0), 2);
            } else {
                $trendData['total_offers'][] = 0;
                $trendData['won_offers'][] = 0;
                $trendData['pending_offers'][] = 0;
                $trendData['lost_offers'][] = 0;
                $trendData['total_amount'][] = 0;
                $trendData['won_amount'][] = 0;
                $trendData['lost_amount'][] = 0;
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
                        WHEN statu = 2 THEN 'Tamamlandı / Kabul Edildi' 
                        WHEN statu = 3 THEN 'Kabul Edilmedi'
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
                    o.reject_reason,
                    o.reject_detail,
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
                    o.reject_reason,
                    o.reject_detail,
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

    /**
     * Belirli bir müşteriye ait tüm teklifleri, kalemlerini ve konsolide icmal özetini getirir
     * 
     * @param int $customerId
     * @return array
     */
    public function getCustomerOfferSummary($customerId)
    {
        $customerId = (int)$customerId;
        $stmt = $this->db->prepare("SELECT 
                o.*,
                u.username as creator_name
            FROM offers o
            LEFT JOIN users u ON o.creativer = u.id
            WHERE o.cid = :cid AND o.is_template = 0
            ORDER BY o.id DESC");
        $stmt->bindParam(':cid', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($offers)) {
            return [
                'offers' => [],
                'summary' => [
                    'total_count' => 0,
                    'pending_count' => 0,
                    'won_count' => 0,
                    'lost_count' => 0,
                    'total_items' => 0,
                    'total_tl' => 0,
                    'total_usd' => 0,
                    'total_eur' => 0,
                    'total_try_direct' => 0,
                    'win_rate' => 0
                ]
            ];
        }

        $offerIds = array_column($offers, 'id');
        $placeholders = implode(',', array_fill(0, count($offerIds), '?'));
        $stmtMatters = $this->db->prepare("SELECT * FROM offermatters WHERE oid IN ($placeholders) ORDER BY oid DESC, satirno ASC, id ASC");
        $stmtMatters->execute($offerIds);
        $allMatters = $stmtMatters->fetchAll(PDO::FETCH_ASSOC);

        $mattersByOffer = [];
        foreach ($allMatters as $m) {
            $mattersByOffer[$m['oid']][] = $m;
        }

        $totalCount = count($offers);
        $pendingCount = 0;
        $wonCount = 0;
        $lostCount = 0;
        $totalItems = count($allMatters);
        $totalTl = 0;
        $totalUsd = 0;
        $totalEur = 0;
        $totalTryDirect = 0;

        foreach ($offers as &$offer) {
            $oid = (int)$offer['id'];
            $offer['items'] = $mattersByOffer[$oid] ?? [];
            $offer['item_count'] = count($offer['items']);

            $statu = (int)$offer['statu'];
            if ($statu === 1) {
                $pendingCount++;
            } elseif ($statu === 2) {
                $wonCount++;
            } elseif ($statu === 3) {
                $lostCount++;
            }

            $totalVal = !empty($offer['tl_toplam_karsilik']) && (float)$offer['tl_toplam_karsilik'] > 0 
                ? (float)$offer['tl_toplam_karsilik'] 
                : (float)($offer['total_price'] ?? 0);
            $totalTl += $totalVal;

            $totalUsd += (float)($offer['DolarTotal'] ?? 0);
            $totalEur += (float)($offer['EuroTotal'] ?? 0);
            $totalTryDirect += (float)($offer['TLTotal'] ?? 0);
        }
        unset($offer);

        $winRate = $totalCount > 0 ? round(($wonCount / $totalCount) * 100, 1) : 0;

        return [
            'offers' => $offers,
            'summary' => [
                'total_count' => $totalCount,
                'pending_count' => $pendingCount,
                'won_count' => $wonCount,
                'lost_count' => $lostCount,
                'total_items' => $totalItems,
                'total_tl' => $totalTl,
                'total_usd' => $totalUsd,
                'total_eur' => $totalEur,
                'total_try_direct' => $totalTryDirect,
                'win_rate' => $winRate
            ]
        ];
    }

    /**
     * Belirli bir teklife ait tüm aktivite/log kayıtlarını getirir.
     *
     * @param int $offerId
     * @return array
     */
    public function getOfferLogs(int $offerId): array
    {
        // 1. Teklif ve bağlı bilgileri al
        $stmtOffer = $this->db->prepare("
            SELECT o.*, 
                   c.company as company_name,
                   u_cr.username as creator_name,
                   u_cr.Unvan as creator_unvan,
                   u_up.username as updater_name,
                   u_up.Unvan as updater_unvan
            FROM {$this->table} o
            LEFT JOIN customers c ON o.cid = c.id
            LEFT JOIN users u_cr ON o.creativer = u_cr.id
            LEFT JOIN users u_up ON o.updater = u_up.id
            WHERE o.id = ?
        ");
        $stmtOffer->execute([$offerId]);
        $offer = $stmtOffer->fetch(PDO::FETCH_OBJ);

        if (!$offer) {
            return [
                'offer' => null,
                'logs' => [],
            ];
        }

        $offerNumber = trim($offer->offerNumber ?? '');
        $offerIdStr = (string)$offerId;

        // 2. Log kayıtlarını sorgula
        $whereClauses = [];
        $params = [];

        if ($offerNumber !== '') {
            $whereClauses[] = "(l.entity_type = 'offer' AND (l.entity_id = :e_id1 OR l.entity_id = :e_no1))";
            $whereClauses[] = "(l.module = 'offers' AND (l.entity_id = :e_id2 OR l.entity_id = :e_no2))";
            $whereClauses[] = "(l.module = 'offers' AND l.summary LIKE :summary_like)";
            $params[':e_id1'] = $offerIdStr;
            $params[':e_no1'] = $offerNumber;
            $params[':e_id2'] = $offerIdStr;
            $params[':e_no2'] = $offerNumber;
            $params[':summary_like'] = '%' . $offerNumber . '%';
        } else {
            $whereClauses[] = "(l.entity_type = 'offer' AND l.entity_id = :e_id1)";
            $whereClauses[] = "(l.module = 'offers' AND l.entity_id = :e_id2)";
            $params[':e_id1'] = $offerIdStr;
            $params[':e_id2'] = $offerIdStr;
        }

        $sql = "SELECT l.*,
                       COALESCE(NULLIF(u.username, ''), IF(COALESCE(NULLIF(l.user_id, 0), l.author) = 0, 'Sistem', CONCAT('Kullanıcı #', COALESCE(NULLIF(l.user_id, 0), l.author)))) as username,
                       u.Unvan as user_unvan,
                       p.p_title as role_title
                FROM logs l
                LEFT JOIN users u ON u.id = COALESCE(NULLIF(l.user_id, 0), l.author)
                LEFT JOIN perms p ON u.permission = p.id
                WHERE (" . implode(' OR ', $whereClauses) . ")
                ORDER BY l.created_at DESC, l.id DESC";

        $stmtLogs = $this->db->prepare($sql);
        $stmtLogs->execute($params);
        $rawLogs = $stmtLogs->fetchAll(PDO::FETCH_OBJ);

        $formattedLogs = [];
        $hasCreateLog = false;

        foreach ($rawLogs as $row) {
            $eventType = $row->event_type ?: ($row->action ?: 'update');
            if ($eventType === 'create' || stripos((string)$row->summary, 'Yeni Teklif Oluşturuldu') !== false) {
                $hasCreateLog = true;
            }

            // JSON detaylarını ayrıştır
            $detailsData = null;
            $changedFields = [];
            $contextData = [];
            if (!empty($row->details)) {
                $decoded = json_decode($row->details, true);
                if (is_array($decoded)) {
                    $detailsData = $decoded;
                    $contextData = $decoded['context']['data'] ?? [];
                    if (!empty($contextData['changed_fields']) && is_array($contextData['changed_fields'])) {
                        $changedFields = $this->formatChangedFields($contextData['changed_fields']);
                    }
                }
            }

            $formattedLogs[] = [
                'id' => (int)$row->id,
                'event_type' => $eventType,
                'event_label' => ActivityLogModel::getEventLabel($eventType),
                'event_icon' => ActivityLogModel::getEventIcon($eventType),
                'badge_class' => ActivityLogModel::getEventBadgeClass($eventType),
                'summary' => $row->summary ?: ActivityLogModel::getEventLabel($eventType),
                'user_name' => $row->username ?: 'Bilinmeyen Kullanıcı',
                'user_unvan' => $row->user_unvan ?: ($row->role_title ?: ''),
                'user_id' => (int)($row->user_id ?: $row->author),
                'created_at' => $row->created_at ?: ($row->dates . ' ' . $row->clock),
                'created_at_formatted' => !empty($row->created_at) ? date('d.m.Y H:i:s', strtotime($row->created_at)) : ($row->dates . ' ' . $row->clock),
                'relative_time' => ActivityLogModel::formatRelativeTime($row->created_at, $row->dates, $row->clock),
                'ip_address' => $row->ip_address ?: '-',
                'changed_fields' => $changedFields,
                'meta' => [
                    'currency' => $contextData['currency'] ?? null,
                    'status' => isset($contextData['status']) ? (int)$contextData['status'] : null,
                    'item_count' => $contextData['item_count'] ?? null,
                    'reject_reason' => $contextData['reject_reason'] ?? null,
                ]
            ];
        }

        // Eğer logs tablosunda oluşturma kaydı yoksa teklif tablosundaki creativer ile başlangıç oluşturma kaydı üret
        if (!$hasCreateLog && (!empty($offer->creativer) || !empty($offer->created_at) || !empty($offer->reg_date))) {
            $createdAt = !empty($offer->created_at) ? $offer->created_at : (!empty($offer->reg_date) ? $offer->reg_date . ' 00:00:00' : null);
            $formattedLogs[] = [
                'id' => 0,
                'event_type' => 'create',
                'event_label' => 'Oluşturma',
                'event_icon' => 'fa fa-plus-circle',
                'badge_class' => 'soft-emerald',
                'summary' => 'Teklif Oluşturuldu: ' . $offerNumber,
                'user_name' => $offer->creator_name ?: ($offer->creativer ? 'Kullanıcı #' . $offer->creativer : 'Sistem'),
                'user_unvan' => $offer->creator_unvan ?: '',
                'user_id' => (int)($offer->creativer ?: 0),
                'created_at' => $createdAt,
                'created_at_formatted' => $createdAt ? date('d.m.Y H:i:s', strtotime($createdAt)) : '-',
                'relative_time' => $createdAt ? ActivityLogModel::formatRelativeTime($createdAt) : '-',
                'ip_address' => '-',
                'changed_fields' => [],
                'meta' => []
            ];
        }

        return [
            'offer' => [
                'id' => (int)$offer->id,
                'offer_number' => $offer->offerNumber,
                'company_name' => $offer->company_name,
                'customer_id' => (int)$offer->cid,
                'total_price' => (float)($offer->tl_toplam_karsilik ?: $offer->total_price),
                'currency' => $offer->currency,
                'statu' => (int)$offer->statu,
                'statu_label' => $offer->statu == 2 ? 'Tamamlandı' : ($offer->statu == 3 ? 'Kabul Edilmedi' : 'Bekliyor'),
                'statu_badge_class' => $offer->statu == 2 ? 'badge-success' : ($offer->statu == 3 ? 'badge-danger' : 'badge-warning'),
                'created_at' => $offer->created_at,
                'creator_name' => $offer->creator_name,
                'creator_unvan' => $offer->creator_unvan,
                'updater_name' => $offer->updater_name,
                'updated_at' => $offer->updated_at,
                'onay_tarihi' => $offer->onay_tarihi,
                'reject_date' => $offer->reject_date,
                'reject_reason' => $offer->reject_reason,
            ],
            'logs' => $formattedLogs,
        ];
    }

    /**
     * Loglanan alan değişikliklerini kullanıcı dostu Türkçe formatına dönüştürür.
     */
    protected function formatChangedFields(array $changes): array
    {
        $fieldMap = [
            'total_price' => ['label' => 'Toplam Tutar', 'type' => 'money'],
            'tl_toplam_karsilik' => ['label' => 'TL Toplam Karşılık', 'type' => 'money'],
            'tl_alis_toplam' => ['label' => 'Alış Toplamı (TL)', 'type' => 'money'],
            'tl_satis_toplam' => ['label' => 'Satış Toplamı (TL)', 'type' => 'money'],
            'statu' => ['label' => 'Teklif Durumu', 'type' => 'status'],
            'currency' => ['label' => 'Para Birimi', 'type' => 'text'],
            'reject_reason' => ['label' => 'Red Gerekçesi', 'type' => 'text'],
            'reject_detail' => ['label' => 'Red Detayı', 'type' => 'text'],
            'offer_subject' => ['label' => 'Teklif Konusu', 'type' => 'text'],
            'payment_period' => ['label' => 'Ödeme Vadesi', 'type' => 'text'],
            'description' => ['label' => 'Açıklama', 'type' => 'text'],
            'offer_date' => ['label' => 'Teklif Tarihi', 'type' => 'text'],
            'company_authors' => ['label' => 'Firma İlgilisi', 'type' => 'text'],
            'authors' => ['label' => 'Yetkili', 'type' => 'text'],
            'Kdv' => ['label' => 'KDV Oranı (%)', 'type' => 'number'],
            'is_template' => ['label' => 'Şablon Durumu', 'type' => 'template_status'],
            'file' => ['label' => 'Ekli Dosya', 'type' => 'text'],
            'notes' => ['label' => 'Notlar', 'type' => 'text'],
            'subdescription' => ['label' => 'Alt Açıklama', 'type' => 'text'],
        ];

        $statusMap = [
            1 => 'Bekliyor',
            2 => 'Tamamlandı / Onaylandı',
            3 => 'Kabul Edilmedi'
        ];

        $result = [];
        foreach ($changes as $field => $val) {
            $old = $val['old'] ?? null;
            $new = $val['new'] ?? null;

            $meta = $fieldMap[$field] ?? ['label' => ucfirst($field), 'type' => 'text'];
            $label = $meta['label'];
            $type = $meta['type'];

            $formatVal = function ($v) use ($type, $statusMap) {
                if ($v === null || $v === '') {
                    return '<i class="text-muted">Boş</i>';
                }
                if ($type === 'money') {
                    return '₺ ' . number_format((float)$v, 2, ',', '.');
                }
                if ($type === 'status') {
                    $stInt = (int)$v;
                    return $statusMap[$stInt] ?? (string)$v;
                }
                if ($type === 'template_status') {
                    return (int)$v === 1 ? 'Şablon Teklif' : 'Standart Teklif';
                }
                return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            };

            $result[] = [
                'field' => $field,
                'label' => $label,
                'old_raw' => $old,
                'new_raw' => $new,
                'old' => $formatVal($old),
                'new' => $formatVal($new),
            ];
        }

        return $result;
    }
}

