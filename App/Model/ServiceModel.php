<?php
namespace App\Model;

use PDO;
use PDOException;
use App\Helper\Date;
use App\Model\BaseModel;
use App\Model\ActivityLogModel;

class ServiceModel extends BaseModel
{
    protected $db;
    protected $table = 'projects';

    public function __construct()
    {
        global $ac;
        $this->db = $ac;
    }

    public function getServiceList()
    {
        $sql = $this->db->prepare("SELECT 
                                        p.id,
                                        p.service_number,
                                        c.company as firma_adi,
                                        c.region,
                                        u.title,
                                        p.pregdate,
                                        p.pstart_date,
                                        p.contract_statu,
                                        p.pstatu,
                                        us.username as olusturan
                                    FROM {$this->table} p
                                    LEFT JOIN customers c ON c.id = p.pcid
                                    LEFT JOIN units u ON u.id = p.servicestype
                                    LEFT JOIN users us ON us.id = p.pcreativer");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    // Güne göre servis listesi
    public function getDailyServiceList($date)
    {
        try {
            $sql = $this->db->prepare("SELECT 
                                            p.id,
                                            c.company as firma_adi,
                                            service_number,
                                            pstart_date,
                                            psecond_date,
                                            u.title,
                                            pauthors,
                                            pstatu
                                        FROM {$this->table} p
                                        LEFT JOIN customers c ON c.id = p.pcid
                                        LEFT JOIN units u ON u.id = p.servicestype
                                        WHERE STR_TO_DATE(REPLACE(pstart_date, '.', '-'), '%d-%m-%Y') = ? 
                                           OR STR_TO_DATE(REPLACE(psecond_date, '.', '-'), '%d-%m-%Y') = ?");
            $sql->execute([$date, $date]);
            return $sql->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Veritabanı hatası getDailyServiceList: " . $e->getMessage());
            return [];
        }
    }

    public function getServiceBackColour($id)
    {
        $sql = $this->db->prepare("SELECT colour FROM units WHERE id = ?");
        $sql->execute([$id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    // Gelen id değerine göre servis sayısını getir
    public function getServiceCount($id)
    {
        $sql = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE pstatu = ?");
        $sql->execute([$id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Servisler için özet KPI istatistiklerini tek bir optimize sorguyla getirir
     * 
     * @return array
     */
    public function getSummaryStats()
    {
        try {
            $sql = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN pstatu = 15 THEN 1 ELSE 0 END) as bekleyen_count,
                    SUM(CASE WHEN pstatu = 16 THEN 1 ELSE 0 END) as calisilan_count,
                    SUM(CASE WHEN pstatu = 17 THEN 1 ELSE 0 END) as tamamlanan_count,
                    SUM(CASE WHEN pstatu = 18 THEN 1 ELSE 0 END) as iptal_count
                FROM {$this->table}
            ");
            $sql->execute();
            $result = $sql->fetch(PDO::FETCH_ASSOC);

            return [
                'total_count'      => (int) ($result['total_count'] ?? 0),
                'bekleyen_count'   => (int) ($result['bekleyen_count'] ?? 0),
                'calisilan_count'  => (int) ($result['calisilan_count'] ?? 0),
                'tamamlanan_count' => (int) ($result['tamamlanan_count'] ?? 0),
                'iptal_count'      => (int) ($result['iptal_count'] ?? 0),
            ];
        } catch (PDOException $e) {
            error_log("Veritabanı hatası getSummaryStats: " . $e->getMessage());
            return [
                'total_count'      => 0,
                'bekleyen_count'   => 0,
                'calisilan_count'  => 0,
                'tamamlanan_count' => 0,
                'iptal_count'      => 0,
            ];
        }
    }

    /**
     * Dashboard Özet İstatistikleri
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getDashboardSummary($startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(p.pregdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.pregdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    COUNT(*) as total_services,
                    SUM(CASE WHEN p.pstatu = 15 THEN 1 ELSE 0 END) as pending_services,
                    SUM(CASE WHEN p.pstatu = 16 THEN 1 ELSE 0 END) as working_services,
                    SUM(CASE WHEN p.pstatu = 17 THEN 1 ELSE 0 END) as completed_services,
                    SUM(CASE WHEN p.pstatu = 18 THEN 1 ELSE 0 END) as cancelled_services,
                    SUM(CASE WHEN p.pstatu = 34 THEN 1 ELSE 0 END) as invoiced_services,
                    SUM(CASE WHEN (p.poid IS NOT NULL AND p.poid > 0) OR (p.teklifID IS NOT NULL AND p.teklifID != '') THEN 1 ELSE 0 END) as offer_linked_services,
                    COUNT(DISTINCT p.pcid) as unique_customers,
                    COUNT(DISTINCT p.pcreativer) as active_creators
                FROM {$this->table} p
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (int)($row['total_services'] ?? 0);
        $completed = (int)($row['completed_services'] ?? 0);
        $pending = (int)($row['pending_services'] ?? 0);
        $working = (int)($row['working_services'] ?? 0);
        $cancelled = (int)($row['cancelled_services'] ?? 0);
        $invoiced = (int)($row['invoiced_services'] ?? 0);
        $offerLinked = (int)($row['offer_linked_services'] ?? 0);

        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
        $pendingRate = $total > 0 ? round(($pending / $total) * 100, 1) : 0;
        $workingRate = $total > 0 ? round(($working / $total) * 100, 1) : 0;
        $offerLinkedRate = $total > 0 ? round(($offerLinked / $total) * 100, 1) : 0;

        return [
            'total_services'        => $total,
            'pending_services'      => $pending,
            'working_services'      => $working,
            'completed_services'    => $completed,
            'cancelled_services'    => $cancelled,
            'invoiced_services'     => $invoiced,
            'offer_linked_services' => $offerLinked,
            'unique_customers'      => (int)($row['unique_customers'] ?? 0),
            'active_creators'       => (int)($row['active_creators'] ?? 0),
            'completion_rate'       => $completionRate,
            'pending_rate'          => $pendingRate,
            'working_rate'          => $workingRate,
            'offer_linked_rate'     => $offerLinkedRate
        ];
    }

    /**
     * En çok servis alan müşteriler
     * 
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopCustomers($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["p.pcid IS NOT NULL", "p.pcid > 0"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(p.pregdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.pregdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int) $limit;

        $sql = "SELECT 
                    p.pcid as customer_id,
                    COALESCE(c.company, 'Bilinmeyen Müşteri') as company,
                    c.city,
                    c.sector,
                    c.deleted_at as customer_deleted_at,
                    COUNT(p.id) as total_services,
                    COUNT(CASE WHEN p.pstatu = 17 THEN 1 END) as completed_services,
                    COUNT(CASE WHEN p.pstatu = 15 THEN 1 END) as pending_services,
                    COUNT(CASE WHEN p.pstatu = 16 THEN 1 END) as working_services,
                    MAX(p.pregdate) as last_service_date
                FROM {$this->table} p
                LEFT JOIN customers c ON p.pcid = c.id
                WHERE {$whereClause}
                GROUP BY p.pcid, c.company, c.city, c.sector, c.deleted_at
                ORDER BY total_services DESC, last_service_date DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($results as $row) {
            $row->completion_rate = $row->total_services > 0 
                ? round(($row->completed_services / $row->total_services) * 100, 1) 
                : 0;
        }

        return $results;
    }

    /**
     * En çok servis oluşturan kullanıcılar
     * 
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopUsers($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(p.pregdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.pregdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int) $limit;

        $sql = "SELECT 
                    p.pcreativer as user_id,
                    COALESCE(u.username, 'Bilinmeyen Kullanıcı') as username,
                    u.Unvan as user_title,
                    u.avatar_link,
                    COUNT(p.id) as total_services,
                    COUNT(CASE WHEN p.pstatu = 17 THEN 1 END) as completed_services,
                    COUNT(CASE WHEN p.pstatu = 15 THEN 1 END) as pending_services,
                    COUNT(CASE WHEN p.pstatu = 16 THEN 1 END) as working_services
                FROM {$this->table} p
                LEFT JOIN users u ON p.pcreativer = u.id
                WHERE {$whereClause}
                GROUP BY p.pcreativer, u.username, u.Unvan, u.avatar_link
                ORDER BY total_services DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($results as $row) {
            $row->completion_rate = $row->total_services > 0 
                ? round(($row->completed_services / $row->total_services) * 100, 1) 
                : 0;
        }

        return $results;
    }

    /**
     * Son 12 ayın servis trendleri
     * 
     * @param int $monthsCount
     * @return array
     */
    public function getMonthlyTrends($monthsCount = 12)
    {
        $monthsCount = (int)$monthsCount;
        $startDate = date('Y-m-01', strtotime("-{$monthsCount} months +1 month"));

        $sql = "SELECT 
                    LEFT(pregdate, 7) as ym,
                    COUNT(*) as total_services,
                    COUNT(CASE WHEN pstatu = 17 THEN 1 END) as completed_services,
                    COUNT(CASE WHEN pstatu = 15 THEN 1 END) as pending_services,
                    COUNT(CASE WHEN pstatu = 16 THEN 1 END) as working_services
                FROM {$this->table}
                WHERE pregdate >= ?
                GROUP BY LEFT(pregdate, 7)
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
            'categories'         => [],
            'total_services'     => [],
            'completed_services' => [],
            'pending_services'   => [],
            'working_services'   => []
        ];

        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $parts = explode('-', $ym);
            $label = ($turkishMonthsShort[$parts[1]] ?? $parts[1]) . ' ' . substr($parts[0], 2);

            $trendData['categories'][] = $label;
            if (isset($map[$ym])) {
                $trendData['total_services'][]     = (int)$map[$ym]['total_services'];
                $trendData['completed_services'][] = (int)$map[$ym]['completed_services'];
                $trendData['pending_services'][]   = (int)$map[$ym]['pending_services'];
                $trendData['working_services'][]   = (int)$map[$ym]['working_services'];
            } else {
                $trendData['total_services'][]     = 0;
                $trendData['completed_services'][] = 0;
                $trendData['pending_services'][]   = 0;
                $trendData['working_services'][]   = 0;
            }
        }

        return $trendData;
    }

    /**
     * Durum Dağılımı (Donut Chart)
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getStatusDistribution($startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(p.pregdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.pregdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    COALESCE(u.id, 0) as status_id,
                    COALESCE(u.title, 'Tanımsız') as status_title,
                    COALESCE(u.colour, '#64748b') as status_color,
                    COUNT(p.id) as count
                FROM {$this->table} p
                LEFT JOIN units u ON p.pstatu = u.id AND u.statu = 4
                WHERE {$whereClause}
                GROUP BY u.id, u.title, u.colour
                ORDER BY count DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        $total = array_sum(array_column($results, 'count'));
        foreach ($results as $row) {
            $row->percentage = $total > 0 ? round(($row->count / $total) * 100, 1) : 0;
        }

        return $results;
    }

    /**
     * Servis Türü Dağılımı
     * 
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getServiceTypeDistribution($limit = 8, $startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(p.pregdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.pregdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int)$limit;

        $sql = "SELECT 
                    COALESCE(u.id, 0) as type_id,
                    COALESCE(u.title, 'Belirtilmemiş') as type_title,
                    COALESCE(u.colour, '#3b82f6') as type_color,
                    COUNT(p.id) as count
                FROM {$this->table} p
                LEFT JOIN units u ON p.servicestype = u.id AND u.statu = 2
                WHERE {$whereClause}
                GROUP BY u.id, u.title, u.colour
                ORDER BY count DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        $total = array_sum(array_column($results, 'count'));
        foreach ($results as $row) {
            $row->percentage = $total > 0 ? round(($row->count / $total) * 100, 1) : 0;
        }

        return $results;
    }

    /**
     * Bölge Dağılımı
     * 
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getRegionDistribution($limit = 8, $startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(p.pregdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.pregdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int)$limit;

        $sql = "SELECT 
                    COALESCE(u.id, 0) as region_id,
                    COALESCE(u.title, 'Belirtilmemiş') as region_title,
                    COUNT(p.id) as count
                FROM {$this->table} p
                LEFT JOIN units u ON p.region = u.id AND u.statu = 5
                WHERE {$whereClause}
                GROUP BY u.id, u.title
                ORDER BY count DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        $total = array_sum(array_column($results, 'count'));
        foreach ($results as $row) {
            $row->percentage = $total > 0 ? round(($row->count / $total) * 100, 1) : 0;
        }

        return $results;
    }

    /**
     * Tahsilat / Ödeme Türü Dağılımı
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getPaymentTypeDistribution($startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(p.pregdate) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.pregdate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    COALESCE(u.id, 0) as pay_id,
                    COALESCE(u.title, 'Belirtilmemiş') as pay_title,
                    COUNT(p.id) as count
                FROM {$this->table} p
                LEFT JOIN units u ON p.collectiontype = u.id AND u.statu = 3
                WHERE {$whereClause}
                GROUP BY u.id, u.title
                ORDER BY count DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        $total = array_sum(array_column($results, 'count'));
        foreach ($results as $row) {
            $row->percentage = $total > 0 ? round(($row->count / $total) * 100, 1) : 0;
        }

        return $results;
    }

    /**
     * Son Eklenen Servisler
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentServicesSummary($limit = 10)
    {
        $limit = (int)$limit;
        $sql = "SELECT 
                    p.id,
                    p.service_number,
                    p.pcid,
                    p.pregdate,
                    p.pstart_date,
                    p.address,
                    p.pdesc,
                    COALESCE(c.company, 'Bilinmeyen Müşteri') as company_name,
                    c.deleted_at as customer_deleted_at,
                    COALESCE(st.title, 'Belirtilmemiş') as service_type_title,
                    COALESCE(rg.title, 'Belirtilmemiş') as region_title,
                    COALESCE(s.title, 'Bekliyor') as status_title,
                    COALESCE(s.colour, '#b8bf2a') as status_color,
                    p.pstatu as status_id,
                    COALESCE(u.username, 'Sistem') as creator_username
                FROM {$this->table} p
                LEFT JOIN customers c ON p.pcid = c.id
                LEFT JOIN units st ON p.servicestype = st.id AND st.statu = 2
                LEFT JOIN units rg ON p.region = rg.id AND rg.statu = 5
                LEFT JOIN units s ON p.pstatu = s.id AND s.statu = 4
                LEFT JOIN users u ON p.pcreativer = u.id
                ORDER BY p.id DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Bekleyen Acil / Öncelikli Servisler
     * 
     * @param int $limit
     * @return array
     */
    public function getPendingUrgentServices($limit = 6)
    {
        $limit = (int)$limit;
        $sql = "SELECT 
                    p.id,
                    p.service_number,
                    p.pcid,
                    p.pregdate,
                    p.pstart_date,
                    p.address,
                    p.pdesc,
                    COALESCE(c.company, 'Bilinmeyen Müşteri') as company_name,
                    c.deleted_at as customer_deleted_at,
                    COALESCE(st.title, 'Belirtilmemiş') as service_type_title,
                    COALESCE(rg.title, 'Belirtilmemiş') as region_title,
                    COALESCE(u.username, 'Sistem') as creator_username
                FROM {$this->table} p
                LEFT JOIN customers c ON p.pcid = c.id
                LEFT JOIN units st ON p.servicestype = st.id AND st.statu = 2
                LEFT JOIN units rg ON p.region = rg.id AND rg.statu = 5
                LEFT JOIN users u ON p.pcreativer = u.id
                WHERE p.pstatu = 15
                ORDER BY p.id ASC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Belirli bir servise ait tüm aktivite/log kayıtlarını getirir.
     *
     * @param int $serviceId
     * @return array
     */
    public function getServiceLogs(int $serviceId): array
    {
        // 1. Servis ve bağlı bilgileri al
        $stmtService = $this->db->prepare("
            SELECT p.*,
                   c.company as company_name,
                   u_srv.title as servicestype_title,
                   u_col.title as collectiontype_title,
                   u_reg.title as region_title,
                   u_st.title as status_title,
                   u_st.colour as status_color,
                   u_cr.username as creator_name,
                   u_cr.Unvan as creator_unvan,
                   u_up.username as updater_name,
                   u_up.Unvan as updater_unvan
            FROM {$this->table} p
            LEFT JOIN customers c ON p.pcid = c.id
            LEFT JOIN units u_srv ON p.servicestype = u_srv.id
            LEFT JOIN units u_col ON p.collectiontype = u_col.id
            LEFT JOIN units u_reg ON p.region = u_reg.id
            LEFT JOIN units u_st ON p.pstatu = u_st.id
            LEFT JOIN users u_cr ON p.pcreativer = u_cr.id
            LEFT JOIN users u_up ON p.updater = u_up.id
            WHERE p.id = ?
        ");
        $stmtService->execute([$serviceId]);
        $service = $stmtService->fetch(PDO::FETCH_OBJ);

        if (!$service) {
            return [
                'service' => null,
                'logs' => [],
            ];
        }

        $serviceNumber = trim($service->service_number ?? '');
        $serviceIdStr = (string)$serviceId;

        // 2. Log kayıtlarını sorgula
        $whereClauses = [];
        $params = [];

        if ($serviceNumber !== '') {
            $whereClauses[] = "(l.entity_type = 'service' AND (l.entity_id = :e_id1 OR l.entity_id = :e_no1))";
            $whereClauses[] = "(l.module = 'services' AND (l.entity_id = :e_id2 OR l.entity_id = :e_no2))";
            $whereClauses[] = "(l.module = 'services' AND l.summary LIKE :summary_like)";
            $params[':e_id1'] = $serviceIdStr;
            $params[':e_no1'] = $serviceNumber;
            $params[':e_id2'] = $serviceIdStr;
            $params[':e_no2'] = $serviceNumber;
            $params[':summary_like'] = '%' . $serviceNumber . '%';
        } else {
            $whereClauses[] = "(l.entity_type = 'service' AND l.entity_id = :e_id1)";
            $whereClauses[] = "(l.module = 'services' AND l.entity_id = :e_id2)";
            $params[':e_id1'] = $serviceIdStr;
            $params[':e_id2'] = $serviceIdStr;
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
            if ($eventType === 'create' || stripos((string)$row->summary, 'Yeni servis oluşturuldu') !== false) {
                $hasCreateLog = true;
            }

            // JSON detaylarını ayrıştır
            $detailsData = null;
            $changedFields = [];
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
            ];
        }

        // Eğer logs tablosunda oluşturma kaydı yoksa başlangıç oluşturma kaydı üret
        if (!$hasCreateLog && (!empty($service->pcreativer) || !empty($service->pregdate))) {
            $createdAt = !empty($service->pregdate) ? date('Y-m-d H:i:s', strtotime($service->pregdate)) : null;
            $formattedLogs[] = [
                'id' => 0,
                'event_type' => 'create',
                'event_label' => 'Oluşturma',
                'event_icon' => 'fa fa-plus-circle',
                'badge_class' => 'soft-emerald',
                'summary' => 'Servis Oluşturuldu: ' . $serviceNumber,
                'user_name' => $service->creator_name ?: ($service->pcreativer ? 'Kullanıcı #' . $service->pcreativer : 'Sistem'),
                'user_unvan' => $service->creator_unvan ?: '',
                'user_id' => (int)($service->pcreativer ?: 0),
                'created_at' => $createdAt,
                'created_at_formatted' => $createdAt ? date('d.m.Y H:i:s', strtotime($createdAt)) : '-',
                'relative_time' => $createdAt ? ActivityLogModel::formatRelativeTime($createdAt) : '-',
                'ip_address' => '-',
                'changed_fields' => [],
            ];
        }

        return [
            'service' => [
                'id' => (int)$service->id,
                'service_number' => $service->service_number,
                'company_name' => $service->company_name,
                'customer_id' => (int)$service->pcid,
                'service_type' => $service->servicestype_title ?: '-',
                'region' => $service->region_title ?: '-',
                'collection_type' => $service->collectiontype_title ?: '-',
                'status_title' => $service->status_title ?: 'Belirtilmemiş',
                'status_color' => $service->status_color ?: '#64748b',
                'start_date' => $service->pstart_date,
                'second_date' => $service->psecond_date,
                'price' => $service->price ? (float)$service->price : null,
                'contract_statu' => (int)$service->contract_statu,
                'contract_statu_label' => $this->getContractStatusLabel((int)$service->contract_statu),
                'created_at' => $service->pregdate,
                'creator_name' => $service->creator_name,
                'creator_unvan' => $service->creator_unvan,
                'updater_name' => $service->updater_name,
                'updated_at' => $service->update_at,
            ],
            'logs' => $formattedLogs,
        ];
    }

    /**
     * Sözleşme durum etiketi döndürür.
     */
    public function getContractStatusLabel(int $status): string
    {
        $map = [
            1 => 'Bekliyor',
            2 => 'Sözleşmeli',
            3 => 'Yapılmadı',
            4 => 'S. Kapsamında Değildir'
        ];
        return $map[$status] ?? 'Diğer';
    }

    /**
     * Loglanan servis alanı değişikliklerini formatlar.
     */
    protected function formatChangedFields(array $changes): array
    {
        $fieldMap = [
            'pstatu' => ['label' => 'Servis Durumu', 'type' => 'unit_status'],
            'contract_statu' => ['label' => 'Sözleşme Durumu', 'type' => 'contract_status'],
            'servicestype' => ['label' => 'Servis Konusu / Türü', 'type' => 'unit_general'],
            'collectiontype' => ['label' => 'Tahsilat Türü', 'type' => 'unit_general'],
            'region' => ['label' => 'Servis Bölgesi', 'type' => 'unit_general'],
            'pstart_date' => ['label' => 'İş Emri / Başlangıç Tarihi', 'type' => 'text'],
            'psecond_date' => ['label' => 'Bitiş Tarihi', 'type' => 'text'],
            'price' => ['label' => 'Servis Bedeli', 'type' => 'money'],
            'price_desc' => ['label' => 'Fiyat Açıklaması', 'type' => 'text'],
            'address' => ['label' => 'Servis Adresi', 'type' => 'text'],
            'pdesc' => ['label' => 'Açıklama', 'type' => 'text'],
            'pnotes' => ['label' => 'Notlar', 'type' => 'text'],
            'pcid' => ['label' => 'Firma ID', 'type' => 'text'],
            'poid' => ['label' => 'Bağlı Teklif ID', 'type' => 'text'],
        ];

        // Units tablosundaki başlıkları yükle
        static $cachedUnits = null;
        if ($cachedUnits === null) {
            try {
                $stUnits = $this->db->query("SELECT id, title FROM units");
                $cachedUnits = $stUnits ? $stUnits->fetchAll(PDO::FETCH_KEY_PAIR) : [];
            } catch (\Throwable $e) {
                $cachedUnits = [];
            }
        }

        $result = [];
        foreach ($changes as $field => $val) {
            $old = $val['old'] ?? null;
            $new = $val['new'] ?? null;

            $meta = $fieldMap[$field] ?? ['label' => ucfirst($field), 'type' => 'text'];
            $label = $meta['label'];
            $type = $meta['type'];

            $formatVal = function ($v) use ($type, $cachedUnits) {
                if ($v === null || $v === '') {
                    return '<i class="text-muted">Boş</i>';
                }
                if ($type === 'money') {
                    return '₺ ' . number_format((float)$v, 2, ',', '.');
                }
                if ($type === 'contract_status') {
                    return $this->getContractStatusLabel((int)$v);
                }
                if ($type === 'unit_status' || $type === 'unit_general') {
                    $uId = (int)$v;
                    return htmlspecialchars($cachedUnits[$uId] ?? (string)$v, ENT_QUOTES, 'UTF-8');
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

