<?php

namespace App\Model;
use App\Model\BaseModel;
use PDO;
class ProductModel extends BaseModel
{

    protected $table = 'products';
    protected $item_table = 'product_items';

    public function __construct()
    {
         parent::__construct($this->table);
    }

    /** Ürünleri birimi ile beraber getirir
     * @return object[]
     */
    public function getAllWithUnits()
    {
        $sql = $this->db->prepare("SELECT p.*, u.title AS birim FROM $this->table p
                                          LEFT JOIN units u ON u.id = p.Birimi
                                          ORDER BY p.Adi ASC");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }   

    /**
     * Ürün ve hizmet listesi için özet istatistikleri getirir
     * @return array
     */
    public function getSummaryStats(): array
    {
        $sql = $this->db->prepare("
            SELECT 
                COUNT(*) AS total_count,
                SUM(CASE WHEN SatisParaBirimi = 'TRY' OR SatisParaBirimi = 'TL' OR SatisParaBirimi = '' OR SatisParaBirimi IS NULL THEN 1 ELSE 0 END) AS try_count,
                SUM(CASE WHEN SatisParaBirimi = 'EUR' THEN 1 ELSE 0 END) AS eur_count,
                SUM(CASE WHEN SatisParaBirimi = 'USD' THEN 1 ELSE 0 END) AS usd_count,
                SUM(CASE WHEN StokKodu IS NOT NULL AND TRIM(StokKodu) != '' THEN 1 ELSE 0 END) AS with_sku_count,
                SUM(CASE WHEN SatisFiyati IS NOT NULL AND TRIM(SatisFiyati) != '' AND CAST(REPLACE(REPLACE(SatisFiyati, '.', ''), ',', '.') AS DECIMAL(12,2)) > 0 THEN 1 ELSE 0 END) AS with_price_count,
                COUNT(DISTINCT Birimi) AS unit_types_count
            FROM {$this->table}
        ");
        $sql->execute();
        $res = $sql->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $res['total_count'] = (int)($res['total_count'] ?? 0);
            $res['try_count'] = (int)($res['try_count'] ?? 0);
            $res['eur_count'] = (int)($res['eur_count'] ?? 0);
            $res['usd_count'] = (int)($res['usd_count'] ?? 0);
            $res['foreign_count'] = $res['eur_count'] + $res['usd_count'];
            $res['with_sku_count'] = (int)($res['with_sku_count'] ?? 0);
            $res['with_price_count'] = (int)($res['with_price_count'] ?? 0);
            $res['unit_types_count'] = (int)($res['unit_types_count'] ?? 0);
            return $res;
        }
        return [
            'total_count' => 0,
            'try_count' => 0,
            'eur_count' => 0,
            'usd_count' => 0,
            'foreign_count' => 0,
            'with_sku_count' => 0,
            'with_price_count' => 0,
            'unit_types_count' => 0,
        ];
    }

    /**
     * Dashboard KPI ve Özet Verileri
     * @param string|null $startDate
     * @param string|null $endDate
     * @return \stdClass
     */
    public function getDashboardSummary($startDate = null, $endDate = null)
    {
        $summary = new \stdClass();

        // 1. Ürün Kataloğu Ana Metrikleri
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN SatisParaBirimi = 'TRY' OR SatisParaBirimi = 'TL' OR SatisParaBirimi = '' OR SatisParaBirimi IS NULL THEN 1 ELSE 0 END) as try_count,
                    SUM(CASE WHEN SatisParaBirimi = 'EUR' THEN 1 ELSE 0 END) as eur_count,
                    SUM(CASE WHEN SatisParaBirimi = 'USD' THEN 1 ELSE 0 END) as usd_count,
                    SUM(CASE WHEN StokKodu IS NOT NULL AND TRIM(StokKodu) != '' THEN 1 ELSE 0 END) as with_sku_count,
                    SUM(CASE WHEN Barkod IS NOT NULL AND TRIM(Barkod) != '' THEN 1 ELSE 0 END) as with_barcode_count,
                    SUM(CASE WHEN Birimi IS NOT NULL AND TRIM(Birimi) != '' THEN 1 ELSE 0 END) as with_unit_count,
                    SUM(CASE WHEN SatisFiyati IS NOT NULL AND TRIM(SatisFiyati) != '' AND CAST(REPLACE(REPLACE(SatisFiyati, '.', ''), ',', '.') AS DECIMAL(12,2)) > 0 THEN 1 ELSE 0 END) as with_price_count
                FROM {$this->table}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $prodData = $stmt->fetch(PDO::FETCH_OBJ);

        $summary->total_count = (int) ($prodData->total_count ?? 0);
        $summary->try_count = (int) ($prodData->try_count ?? 0);
        $summary->eur_count = (int) ($prodData->eur_count ?? 0);
        $summary->usd_count = (int) ($prodData->usd_count ?? 0);
        $summary->foreign_count = $summary->eur_count + $summary->usd_count;
        $summary->foreign_ratio = $summary->total_count > 0 ? round(($summary->foreign_count / $summary->total_count) * 100, 1) : 0;

        $summary->with_sku_count = (int) ($prodData->with_sku_count ?? 0);
        $summary->sku_rate = $summary->total_count > 0 ? round(($summary->with_sku_count / $summary->total_count) * 100, 1) : 0;
        
        $summary->with_barcode_count = (int) ($prodData->with_barcode_count ?? 0);
        $summary->barcode_rate = $summary->total_count > 0 ? round(($summary->with_barcode_count / $summary->total_count) * 100, 1) : 0;

        $summary->with_unit_count = (int) ($prodData->with_unit_count ?? 0);
        $summary->unit_rate = $summary->total_count > 0 ? round(($summary->with_unit_count / $summary->total_count) * 100, 1) : 0;

        $summary->with_price_count = (int) ($prodData->with_price_count ?? 0);
        $summary->price_rate = $summary->total_count > 0 ? round(($summary->with_price_count / $summary->total_count) * 100, 1) : 0;

        // 2. Bu Ay & Geçen Ay Eklenen Ürün Sayıları
        $thisMonthStr = date('m.Y');
        $thisMonthAltStr = date('m-Y');
        $lastMonthStr = date('m.Y', strtotime('-1 month'));
        $lastMonthAltStr = date('m-Y', strtotime('-1 month'));

        $stmtMonth = $this->db->prepare("SELECT 
            SUM(CASE WHEN OlusturmaTarihi LIKE :tm1 OR OlusturmaTarihi LIKE :tm2 THEN 1 ELSE 0 END) as this_month_count,
            SUM(CASE WHEN OlusturmaTarihi LIKE :lm1 OR OlusturmaTarihi LIKE :lm2 THEN 1 ELSE 0 END) as last_month_count
        FROM {$this->table}");
        $stmtMonth->execute([
            ':tm1' => '%' . $thisMonthStr,
            ':tm2' => '%' . $thisMonthAltStr,
            ':lm1' => '%' . $lastMonthStr,
            ':lm2' => '%' . $lastMonthAltStr,
        ]);
        $monthData = $stmtMonth->fetch(PDO::FETCH_OBJ);
        $summary->this_month_count = (int) ($monthData->this_month_count ?? 0);
        $summary->last_month_count = (int) ($monthData->last_month_count ?? 0);

        if ($summary->last_month_count > 0) {
            $summary->month_growth_rate = round((($summary->this_month_count - $summary->last_month_count) / $summary->last_month_count) * 100, 1);
        } else {
            $summary->month_growth_rate = $summary->this_month_count > 0 ? 100 : 0;
        }

        // 3. Teklif Kalemleri (Offermatters) Hacim ve Kullanım İstatistikleri
        $offerWhere = ["o.is_template = 0"];
        $offerParams = [];
        if (!empty($startDate)) {
            $offerWhere[] = "DATE(o.created_at) >= :start_date";
            $offerParams[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $offerWhere[] = "DATE(o.created_at) <= :end_date";
            $offerParams[':end_date'] = $endDate;
        }
        $offerWhereClause = implode(" AND ", $offerWhere);

        $stmtOffer = $this->db->prepare("SELECT 
            COUNT(DISTINCT om.title) as unique_offered_products,
            COUNT(om.id) as total_offer_items_count,
            COALESCE(SUM(om.total_price), 0) as total_offered_amount,
            COUNT(DISTINCT om.oid) as total_offers_with_items
        FROM offermatters om
        JOIN offers o ON o.id = om.oid
        WHERE {$offerWhereClause}");
        $stmtOffer->execute($offerParams);
        $offerData = $stmtOffer->fetch(PDO::FETCH_OBJ);

        $summary->unique_offered_products = (int) ($offerData->unique_offered_products ?? 0);
        $summary->total_offer_items_count = (int) ($offerData->total_offer_items_count ?? 0);
        $summary->total_offered_amount = (float) ($offerData->total_offered_amount ?? 0);
        $summary->total_offers_with_items = (int) ($offerData->total_offers_with_items ?? 0);

        return $summary;
    }

    /**
     * Tekliflerde en çok kullanılan / talep gören ürünler
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return object[]
     */
    public function getTopUsedProducts($limit = 10, $startDate = null, $endDate = null)
    {
        $limit = (int)$limit;
        $where = ["o.is_template = 0", "om.title IS NOT NULL", "TRIM(om.title) != ''"];
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

        $sql = "SELECT 
                    om.title,
                    MAX(om.stokKodu) as stokKodu,
                    MAX(om.unit) as unit,
                    MAX(om.salecur) as salecur,
                    COUNT(DISTINCT om.oid) as offer_count,
                    COALESCE(SUM(CAST(om.amount AS DECIMAL(12,2))), 0) as total_qty,
                    COALESCE(SUM(om.total_price), 0) as total_revenue,
                    COALESCE(AVG(om.saleprice), 0) as avg_price
                FROM offermatters om
                JOIN offers o ON o.id = om.oid
                WHERE {$whereClause}
                GROUP BY om.title
                ORDER BY offer_count DESC, total_revenue DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Para Birimi Dağılımı
     * @param string|null $startDate
     * @param string|null $endDate
     * @return object[]
     */
    public function getCurrencyDistribution($startDate = null, $endDate = null)
    {
        $sql = "SELECT 
                    CASE 
                        WHEN SatisParaBirimi = 'TRY' OR SatisParaBirimi = 'TL' OR SatisParaBirimi = '' OR SatisParaBirimi IS NULL THEN 'TRY'
                        WHEN SatisParaBirimi = 'EUR' THEN 'EUR'
                        WHEN SatisParaBirimi = 'USD' THEN 'USD'
                        ELSE SatisParaBirimi
                    END AS currency,
                    COUNT(*) as count
                FROM {$this->table}
                GROUP BY currency
                ORDER BY count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Birim Dağılımı
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return object[]
     */
    public function getUnitDistribution($limit = 8, $startDate = null, $endDate = null)
    {
        $limit = (int)$limit;
        $sql = "SELECT 
                    COALESCE(u.title, 'Birim Tanımsız') AS unit_title,
                    COUNT(p.ID) AS count
                FROM {$this->table} p
                LEFT JOIN units u ON (u.id = p.Birimi OR u.title = p.Birimi)
                GROUP BY unit_title
                ORDER BY count DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Son N ay için teklif kalemleri & ürün trendi
     * @param int $months
     * @return array
     */
    public function getMonthlyProductTrends($months = 12)
    {
        $trends = [];
        $start = new \DateTime("-{$months} months");
        $start->modify('first day of this month');
        $end = new \DateTime('last day of this month');

        $interval = new \DateInterval('P1M');
        $periodRange = new \DatePeriod($start, $interval, $end->modify('+1 day'));

        $turkishMonths = [
            '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
            '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
            '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
        ];

        foreach ($periodRange as $dt) {
            $yearMonth = $dt->format('Y-m');
            $year = $dt->format('Y');
            $month = $dt->format('m');
            $label = ($turkishMonths[$month] ?? $month) . ' ' . substr($year, 2);

            $startDate = $dt->format('Y-m-01');
            $endDate = $dt->format('Y-m-t');

            // Teklif Kalemleri Hacmi ve Satır Sayısı
            $stmt = $this->db->prepare("SELECT 
                COUNT(om.id) as item_count,
                COUNT(DISTINCT om.title) as product_variety,
                COALESCE(SUM(om.total_price), 0) as total_revenue
            FROM offermatters om
            JOIN offers o ON o.id = om.oid
            WHERE o.is_template = 0 AND DATE(o.created_at) BETWEEN ? AND ?");
            $stmt->execute([$startDate, $endDate]);
            $row = $stmt->fetch(PDO::FETCH_OBJ);

            $trends[] = [
                'period' => $yearMonth,
                'label' => $label,
                'item_count' => (int) ($row->item_count ?? 0),
                'product_variety' => (int) ($row->product_variety ?? 0),
                'total_revenue' => (float) ($row->total_revenue ?? 0),
            ];
        }

        return $trends;
    }

    /**
     * En Yüksek Fiyatlı Katma Değerli Ürünler
     * @param int $limit
     * @return object[]
     */
    public function getTopValueProducts($limit = 6)
    {
        $limit = (int)$limit;
        $sql = "SELECT 
                    p.ID,
                    p.Adi,
                    p.StokKodu,
                    p.Birimi,
                    p.AlisFiyati,
                    p.AlisParaBirimi,
                    p.SatisFiyati,
                    p.SatisParaBirimi,
                    u.title AS birim_adi
                FROM {$this->table} p
                LEFT JOIN units u ON (u.id = p.Birimi OR u.title = p.Birimi)
                WHERE p.SatisFiyati IS NOT NULL AND TRIM(p.SatisFiyati) != ''
                ORDER BY CAST(REPLACE(REPLACE(p.SatisFiyati, '.', ''), ',', '.') AS DECIMAL(12,2)) DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * En Son Eklenen / Güncellenen Ürünler
     * @param int $limit
     * @return object[]
     */
    public function getRecentProductsSummary($limit = 10)
    {
        $limit = (int)$limit;
        $sql = "SELECT 
                    p.ID,
                    p.Adi,
                    p.StokKodu,
                    p.Birimi,
                    p.AlisFiyati,
                    p.AlisParaBirimi,
                    p.SatisFiyati,
                    p.SatisParaBirimi,
                    p.OlusturmaTarihi,
                    u.title AS birim_adi
                FROM {$this->table} p
                LEFT JOIN units u ON (u.id = p.Birimi OR u.title = p.Birimi)
                ORDER BY p.ID DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Mobil SPA için filtrelenmiş ve sayfalanmış ürün listesi
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getMobileList(array $filters = [], int $page = 1, int $limit = 25): array
    {
        $page = max(1, $page);
        $limit = min(100, max(5, $limit));
        $offset = ($page - 1) * $limit;

        $where = ["1=1"];
        $params = [];

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = "(p.Adi LIKE :search_name 
                         OR p.StokKodu LIKE :search_sku 
                         OR p.Aciklama LIKE :search_desc 
                         OR p.Barkod LIKE :search_barcode 
                         OR p.ID = :search_id)";
            $params[':search_name'] = "%{$search}%";
            $params[':search_sku'] = "%{$search}%";
            $params[':search_desc'] = "%{$search}%";
            $params[':search_barcode'] = "%{$search}%";
            $params[':search_id'] = is_numeric($search) ? (int)$search : 0;
        }

        $tab = (string)($filters['tab'] ?? 'all');
        if ($tab === 'with_stock') {
            $where[] = "(p.StokKodu IS NOT NULL AND TRIM(p.StokKodu) != '')";
        } elseif ($tab === 'with_price') {
            $where[] = "(p.SatisFiyati IS NOT NULL AND TRIM(p.SatisFiyati) != '' AND CAST(REPLACE(REPLACE(p.SatisFiyati, '.', ''), ',', '.') AS DECIMAL(12,2)) > 0)";
        } elseif ($tab === 'try') {
            $where[] = "(p.SatisParaBirimi = 'TRY' OR p.SatisParaBirimi = 'TL' OR p.SatisParaBirimi = '' OR p.SatisParaBirimi IS NULL)";
        } elseif ($tab === 'foreign') {
            $where[] = "(p.SatisParaBirimi IN ('USD', 'EUR'))";
        }

        $unit = trim((string)($filters['unit'] ?? ''));
        if ($unit !== '') {
            $where[] = "(p.Birimi = :unit OR u.title = :unit_title)";
            $params[':unit'] = $unit;
            $params[':unit_title'] = $unit;
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "
            SELECT COUNT(*) 
            FROM {$this->table} p
            LEFT JOIN units u ON (u.id = p.Birimi OR u.title = p.Birimi)
            WHERE {$whereSql}
        ";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $totalItems = (int)$countStmt->fetchColumn();

        $dataSql = "
            SELECT 
                p.ID,
                p.Adi,
                p.StokKodu,
                p.Birimi,
                COALESCE(u.title, p.Birimi) AS birim_adi,
                p.AlisFiyati,
                p.AlisParaBirimi,
                p.SatisFiyati,
                p.SatisParaBirimi,
                p.Aciklama,
                p.Barkod,
                p.RafKodu,
                p.MinStok,
                p.OlusturmaTarihi
            FROM {$this->table} p
            LEFT JOIN units u ON (u.id = p.Birimi OR u.title = p.Birimi)
            WHERE {$whereSql}
            ORDER BY p.ID DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($dataSql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = $totalItems > 0 ? (int)ceil($totalItems / $limit) : 1;

        return [
            'items' => $items,
            'total_items' => $totalItems,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];
    }

    /**
     * Mobil SPA için ürün detay ve ilişkili veriler
     * @param int $id
     * @return array|null
     */
    public function getMobileDetail(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                COALESCE(u.title, p.Birimi) AS birim_adi,
                (SELECT COUNT(*) FROM offermatters om WHERE om.title = p.Adi OR (p.StokKodu IS NOT NULL AND p.StokKodu != '' AND om.stokKodu = p.StokKodu)) AS usage_count
            FROM {$this->table} p
            LEFT JOIN units u ON (u.id = p.Birimi OR u.title = p.Birimi)
            WHERE p.ID = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        // İlişkili son teklifler
        $stmtOffers = $this->db->prepare("
            SELECT 
                o.id,
                o.offerNumber,
                c.company,
                om.amount,
                om.saleprice AS unit_price,
                om.salecur AS currency,
                om.total_price,
                o.created_at
            FROM offermatters om
            JOIN offers o ON o.id = om.oid
            LEFT JOIN customers c ON c.id = o.cid
            WHERE (om.title = ? OR (om.stokKodu = ? AND ? != ''))
            ORDER BY o.id DESC
            LIMIT 5
        ");
        $stmtOffers->execute([$row['Adi'], $row['StokKodu'] ?? '', $row['StokKodu'] ?? '']);
        $recentOffers = $stmtOffers->fetchAll(PDO::FETCH_ASSOC);

        return [
            'product' => $row,
            'recent_offers' => $recentOffers,
        ];
    }

    /**
     * Mobil SPA için ürün ekleme / güncelleme işlemi
     * @param array $input
     * @param int $userId
     * @return array
     */
    public function saveMobileProduct(array $input, int $userId): array
    {
        $name = trim((string)($input['name'] ?? ($input['Adi'] ?? ($input['urunAdi'] ?? ''))));
        if ($name === '') {
            return ['success' => false, 'message' => 'Lütfen ürün veya hizmet adını belirtin.'];
        }

        $unit = trim((string)($input['unit'] ?? ($input['Birimi'] ?? '')));
        if ($unit === '' || $unit === '0') {
            return ['success' => false, 'message' => 'Lütfen geçerli bir ölçü birimi seçin.'];
        }

        $id = (int)($input['id'] ?? 0);
        $existing = $id > 0 ? $this->find($id) : null;
        if ($id > 0 && !$existing) {
            return ['success' => false, 'message' => 'Güncellenecek ürün kaydı bulunamadı.'];
        }

        $stockCode = trim((string)($input['stock_code'] ?? ($input['StokKodu'] ?? '')));
        if ($stockCode === '' && $id === 0) {
            $stockCode = 'STK' . mt_rand(1000000, 9999999);
        }

        $alisFiyati = trim((string)($input['buy_price'] ?? ($input['AlisFiyati'] ?? '')));
        if ($alisFiyati !== '') {
            $alisFiyati = str_replace(',', '.', $alisFiyati);
        }

        $satisFiyati = trim((string)($input['sale_price'] ?? ($input['SatisFiyati'] ?? '')));
        if ($satisFiyati !== '') {
            $satisFiyati = str_replace(',', '.', $satisFiyati);
        }

        $alisParaBirimi = trim((string)($input['buy_currency'] ?? ($input['AlisParaBirimi'] ?? 'TRY')));
        if (!in_array($alisParaBirimi, ['TRY', 'USD', 'EUR', 'TL'], true)) {
            $alisParaBirimi = 'TRY';
        }

        $satisParaBirimi = trim((string)($input['sale_currency'] ?? ($input['SatisParaBirimi'] ?? 'TRY')));
        if (!in_array($satisParaBirimi, ['TRY', 'USD', 'EUR', 'TL'], true)) {
            $satisParaBirimi = 'TRY';
        }

        $aciklama = trim((string)($input['description'] ?? ($input['Aciklama'] ?? '')));
        $barkod = trim((string)($input['barcode'] ?? ($input['Barkod'] ?? '')));
        $rafKodu = trim((string)($input['shelf_code'] ?? ($input['RafKodu'] ?? '')));
        $minStok = trim((string)($input['min_stock'] ?? ($input['MinStok'] ?? '')));

        $data = [
            'id' => $id,
            'Adi' => $name,
            'StokKodu' => $stockCode,
            'Birimi' => $unit,
            'AlisFiyati' => $alisFiyati,
            'AlisParaBirimi' => $alisParaBirimi,
            'SatisFiyati' => $satisFiyati,
            'SatisParaBirimi' => $satisParaBirimi,
            'Aciklama' => $aciklama,
            'Barkod' => $barkod,
            'RafKodu' => $rafKodu,
            'MinStok' => $minStok,
        ];

        if ($id === 0) {
            $data['OlusturmaTarihi'] = date('d.m.Y');
            $data['PersonelID'] = $userId;
        } else {
            $data['Duzenleyen'] = (string)$userId;
            $data['DuzenlemeTarihi'] = date('d.m.Y');
        }

        $savedId = (int)$this->save($data);
        $finalId = $id > 0 ? $id : $savedId;

        return [
            'success' => true,
            'id' => $finalId,
            'message' => $id > 0 ? 'Ürün/hizmet başarıyla güncellendi.' : 'Yeni ürün/hizmet başarıyla eklendi.',
            'data' => $data,
        ];
    }

    /**
     * Mobil SPA için ürün silme işlemi
     * @param int $id
     * @param int $userId
     * @return array
     */
    public function deleteMobileProduct(int $id, int $userId): array
    {
        $product = $this->find($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Silinecek ürün bulunamadı.'];
        }

        // Tekliflerde kullanım durumu
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM offermatters WHERE title = ? OR (stokKodu = ? AND ? != '')");
        $stmtCheck->execute([$product->Adi, $product->StokKodu ?? '', $product->StokKodu ?? '']);
        $usageCount = (int)$stmtCheck->fetchColumn();

        $this->delete($id);

        return [
            'success' => true,
            'message' => $usageCount > 0 
                ? 'Ürün silindi (Daha önce tekliflerde geçmiş kaydı bulunuyordu).'
                : 'Ürün başarıyla silindi.',
            'product_name' => $product->Adi ?? ('#' . $id),
            'usage_count' => $usageCount,
        ];
    }

    /**
     * Aktif ölçü birimlerini listeler
     * @return array
     */
    public function getActiveUnits(): array
    {
        $stmt = $this->db->prepare("SELECT id, title FROM units WHERE statu = 1 ORDER BY title ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}