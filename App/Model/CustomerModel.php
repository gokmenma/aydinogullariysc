<?php 
namespace App\Model;

use App\Model\BaseModel;

class CustomerModel extends BaseModel
{
    protected $table = 'customers';

    public function __construct()
    {
       parent::__construct($this->table);
    }

    public function companyNameExists($company, $excludeId = 0)
    {
        $sql = "SELECT 1
                FROM customers
                WHERE deleted_at IS NULL
                  AND LOWER(TRIM(company)) = LOWER(TRIM(?))";
        $params = [trim($company)];

        if ((int) $excludeId > 0) {
            $sql .= " AND id <> ?";
            $params[] = (int) $excludeId;
        }

        $sql .= " LIMIT 1";
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetchColumn();
    }

    public function softDelete($id, $deletedBy)
    {
        $statement = $this->db->prepare(
            "UPDATE customers
             SET deleted_at = ?, deleted_by = ?
             WHERE id = ? AND deleted_at IS NULL"
        );
        $statement->execute([
            date('Y-m-d H:i:s'),
            (int) $deletedBy,
            (int) $id
        ]);

        return $statement->rowCount();
    }

    public function getSummaryStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_customers,
                    SUM(CASE WHEN email IS NOT NULL AND TRIM(email) != '' THEN 1 ELSE 0 END) as with_email_count,
                    SUM(CASE WHEN gsm IS NOT NULL AND TRIM(gsm) != '' THEN 1 ELSE 0 END) as with_gsm_count,
                    SUM(CASE WHEN regdate >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_30_days_count
                FROM customers
                WHERE deleted_at IS NULL";
        $stmt = $this->db->query($sql);
        return $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : [
            'total_customers' => 0,
            'with_email_count' => 0,
            'with_gsm_count' => 0,
            'recent_30_days_count' => 0
        ];
    }

    /**
     * Müşteri Dashboard Genel Özet ve KPI Metrikleri
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return object
     */
    public function getDashboardSummary($startDate = null, $endDate = null)
    {
        $summary = new \stdClass();

        $where = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(regdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(regdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        // Ana Metrikler
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN email IS NOT NULL AND TRIM(email) != '' THEN 1 ELSE 0 END) as with_email_count,
                    SUM(CASE WHEN gsm IS NOT NULL AND TRIM(gsm) != '' THEN 1 ELSE 0 END) as with_gsm_count,
                    SUM(CASE WHEN (email IS NOT NULL AND TRIM(email) != '') AND (gsm IS NOT NULL AND TRIM(gsm) != '') THEN 1 ELSE 0 END) as full_contact_count,
                    SUM(CASE WHEN address IS NOT NULL AND TRIM(address) != '' THEN 1 ELSE 0 END) as with_address_count,
                    SUM(CASE WHEN city IS NOT NULL AND TRIM(city) != '' THEN 1 ELSE 0 END) as with_city_count
                FROM customers
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetch(\PDO::FETCH_OBJ);

        $summary->total_count = (int) ($data->total_count ?? 0);
        $summary->with_email_count = (int) ($data->with_email_count ?? 0);
        $summary->with_gsm_count = (int) ($data->with_gsm_count ?? 0);
        $summary->full_contact_count = (int) ($data->full_contact_count ?? 0);
        $summary->with_address_count = (int) ($data->with_address_count ?? 0);
        $summary->with_city_count = (int) ($data->with_city_count ?? 0);

        // İletişim doluluk oranları
        $summary->email_rate = $summary->total_count > 0 
            ? round(($summary->with_email_count / $summary->total_count) * 100, 1) 
            : 0;
        $summary->gsm_rate = $summary->total_count > 0 
            ? round(($summary->with_gsm_count / $summary->total_count) * 100, 1) 
            : 0;
        $summary->full_contact_rate = $summary->total_count > 0 
            ? round(($summary->full_contact_count / $summary->total_count) * 100, 1) 
            : 0;

        // Bu ay eklenen müşteriler
        $thisMonthStart = date('Y-m-01');
        $thisMonthEnd = date('Y-m-t');
        $stmtThisMonth = $this->db->prepare("SELECT COUNT(*) as count FROM customers WHERE deleted_at IS NULL AND DATE(regdate) BETWEEN ? AND ?");
        $stmtThisMonth->execute([$thisMonthStart, $thisMonthEnd]);
        $summary->this_month_count = (int) ($stmtThisMonth->fetchColumn() ?? 0);

        // Geçen ay eklenen müşteriler
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
        $stmtLastMonth = $this->db->prepare("SELECT COUNT(*) as count FROM customers WHERE deleted_at IS NULL AND DATE(regdate) BETWEEN ? AND ?");
        $stmtLastMonth->execute([$lastMonthStart, $lastMonthEnd]);
        $summary->last_month_count = (int) ($stmtLastMonth->fetchColumn() ?? 0);

        // Aylık büyüme oranı
        if ($summary->last_month_count > 0) {
            $summary->month_growth_rate = round((($summary->this_month_count - $summary->last_month_count) / $summary->last_month_count) * 100, 1);
        } else {
            $summary->month_growth_rate = $summary->this_month_count > 0 ? 100 : 0;
        }

        // Ticari Hacim & Teklif İstatistikleri (Filtreli dönem veya genel)
        $offerWhere = ["o.is_template = 0"];
        $offerParams = [];
        if (!empty($startDate)) {
            $offerWhere[] = "DATE(o.created_at) >= :o_start_date";
            $offerParams[':o_start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $offerWhere[] = "DATE(o.created_at) <= :o_end_date";
            $offerParams[':o_end_date'] = $endDate;
        }
        $offerWhereClause = implode(" AND ", $offerWhere);

        $stmtTrading = $this->db->prepare("SELECT 
                                                COUNT(DISTINCT o.cid) as active_trading_customers,
                                                COUNT(o.id) as total_offers_count,
                                                COALESCE(SUM(COALESCE(o.tl_toplam_karsilik, o.total_price, 0)), 0) as total_trading_amount,
                                                COALESCE(SUM(CASE WHEN o.statu = 2 THEN COALESCE(o.tl_toplam_karsilik, o.total_price, 0) ELSE 0 END), 0) as won_trading_amount
                                            FROM offers o
                                            WHERE {$offerWhereClause}");
        $stmtTrading->execute($offerParams);
        $tradingData = $stmtTrading->fetch(\PDO::FETCH_OBJ);

        $summary->active_trading_customers = (int) ($tradingData->active_trading_customers ?? 0);
        $summary->total_offers_count = (int) ($tradingData->total_offers_count ?? 0);
        $summary->total_trading_amount = (float) ($tradingData->total_trading_amount ?? 0);
        $summary->won_trading_amount = (float) ($tradingData->won_trading_amount ?? 0);

        // Teklif alma oranı
        $summary->trading_rate = $summary->total_count > 0 
            ? round(($summary->active_trading_customers / $summary->total_count) * 100, 1) 
            : 0;

        return $summary;
    }

    /**
     * En yüksek ticari hacme (teklif hacmi) sahip müşteriler
     * 
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopCustomersByVolume($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["c.deleted_at IS NULL", "o.is_template = 0"];
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
                    c.id as customer_id,
                    c.company as company_name,
                    c.city,
                    c.ilce,
                    cg.title as group_title,
                    COUNT(o.id) as total_offers,
                    COUNT(CASE WHEN o.statu = 2 THEN 1 END) as won_offers,
                    COUNT(CASE WHEN o.statu = 1 THEN 1 END) as pending_offers,
                    COALESCE(SUM(COALESCE(o.tl_toplam_karsilik, o.total_price, 0)), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN o.statu = 2 THEN COALESCE(o.tl_toplam_karsilik, o.total_price, 0) ELSE 0 END), 0) as won_amount
                FROM offers o
                INNER JOIN customers c ON o.cid = c.id
                LEFT JOIN cgroups cg ON c.grp = cg.id
                WHERE {$whereClause}
                GROUP BY c.id, c.company, c.city, c.ilce, cg.title
                ORDER BY total_amount DESC, total_offers DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

        foreach ($results as $row) {
            $row->win_rate = $row->total_offers > 0 
                ? round(($row->won_offers / $row->total_offers) * 100, 1) 
                : 0;
        }

        return $results;
    }

    /**
     * Şehir bazlı müşteri dağılımı
     * 
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getCityDistribution($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(regdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(regdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int) $limit;

        $sql = "SELECT 
                    CASE 
                        WHEN city IS NULL OR TRIM(city) = '' THEN 'Belirtilmemiş'
                        ELSE UPPER(TRIM(city))
                    END as city_name,
                    COUNT(*) as count
                FROM customers
                WHERE {$whereClause}
                GROUP BY city_name
                ORDER BY count DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Müşteri Grubu Dağılımı (Müşteri, Tedarikçi vb.)
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getGroupDistribution($startDate = null, $endDate = null)
    {
        $where = ["c.deleted_at IS NULL"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(c.regdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(c.regdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    COALESCE(cg.title, 'Diğer / Tanımsız') as group_name,
                    COUNT(c.id) as count
                FROM customers c
                LEFT JOIN cgroups cg ON c.grp = cg.id
                WHERE {$whereClause}
                GROUP BY cg.title
                ORDER BY count DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Son 12 Ayın Müşteri Kazanım ve Teklif Trendi
     * 
     * @param int $months
     * @return array
     */
    public function getMonthlyCustomerTrends($months = 12)
    {
        $trends = [];
        $turkishMonths = [
            '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
            '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
            '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
        ];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-{$i} months"));
            $yearMonth = date('Y-m', strtotime($date));
            $monthNum = date('m', strtotime($date));
            $year = date('Y', strtotime($date));
            $label = ($turkishMonths[$monthNum] ?? $monthNum) . ' ' . substr($year, 2);

            $startDate = date('Y-m-01', strtotime($date));
            $endDate = date('Y-m-t', strtotime($date));

            // Yeni Müşteri Sayısı
            $stmtCust = $this->db->prepare("SELECT COUNT(*) as count FROM customers WHERE deleted_at IS NULL AND DATE(regdate) BETWEEN ? AND ?");
            $stmtCust->execute([$startDate, $endDate]);
            $newCustomers = (int) ($stmtCust->fetchColumn() ?? 0);

            // Teklif Verilen Tekil Müşteri Sayısı ve Teklif Hacmi
            $stmtOff = $this->db->prepare("SELECT 
                                                COUNT(DISTINCT cid) as trading_customers,
                                                COALESCE(SUM(COALESCE(tl_toplam_karsilik, total_price, 0)), 0) as total_volume
                                            FROM offers 
                                            WHERE is_template = 0 AND DATE(created_at) BETWEEN ? AND ?");
            $stmtOff->execute([$startDate, $endDate]);
            $offData = $stmtOff->fetch(\PDO::FETCH_OBJ);

            $trends[] = [
                'period' => $yearMonth,
                'label' => $label,
                'new_customers' => $newCustomers,
                'trading_customers' => (int) ($offData->trading_customers ?? 0),
                'total_volume' => (float) ($offData->total_volume ?? 0)
            ];
        }

        return $trends;
    }

    /**
     * En Çok Müşteri Ekleyen Personeller
     * 
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopCustomerCreators($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["c.deleted_at IS NULL"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(c.regdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(c.regdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int) $limit;

        $sql = "SELECT 
                    c.creativer as user_id,
                    COALESCE(u.username, 'Sistem / Bilinmeyen') as username,
                    u.Unvan as user_title,
                    u.avatar_link,
                    COUNT(c.id) as total_customers,
                    SUM(CASE WHEN c.email IS NOT NULL AND TRIM(c.email) != '' THEN 1 ELSE 0 END) as with_email,
                    SUM(CASE WHEN c.gsm IS NOT NULL AND TRIM(c.gsm) != '' THEN 1 ELSE 0 END) as with_gsm
                FROM customers c
                LEFT JOIN users u ON c.creativer = u.id
                WHERE {$whereClause}
                GROUP BY c.creativer, u.username, u.Unvan, u.avatar_link
                ORDER BY total_customers DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Son Eklenen Müşteriler
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentCustomers($limit = 10)
    {
        $limit = (int) $limit;
        $sql = "SELECT 
                    c.id,
                    c.company,
                    c.city,
                    c.ilce,
                    c.email,
                    c.gsm,
                    c.yetkili,
                    c.regdate,
                    cg.title as group_title,
                    u.username as creator_name
                FROM customers c
                LEFT JOIN cgroups cg ON c.grp = cg.id
                LEFT JOIN users u ON c.creativer = u.id
                WHERE c.deleted_at IS NULL
                ORDER BY c.id DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}

