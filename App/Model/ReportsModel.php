<?php

namespace App\Model;

use App\Model\BaseModel;
use PDO;

class ReportsModel extends BaseModel
{
    protected $table = 'reports';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Rapor Dashboard KPI Özet İstatistikleri
     *
     * @param string|null $startDate YYYY-MM-DD
     * @param string|null $endDate YYYY-MM-DD
     * @return object
     */
    public function getDashboardSummary($startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(create_time) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(create_time) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    COUNT(*) as total_count,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    COUNT(DISTINCT controller_id) as unique_controllers,
                    COUNT(CASE WHEN report_type = 1 THEN 1 END) as ysc_count,
                    COUNT(CASE WHEN report_type = 2 THEN 1 END) as hst_count,
                    COUNT(CASE WHEN report_type = 3 THEN 1 END) as met_count,
                    COUNT(CASE WHEN report_type = 4 THEN 1 END) as yas_count,
                    COUNT(CASE WHEN report_type = 5 THEN 1 END) as oys_count,
                    COUNT(CASE WHEN report_type = 6 THEN 1 END) as aas_count,
                    COUNT(CASE WHEN report_type NOT IN (1, 2) THEN 1 END) as other_count
                FROM {$this->table} 
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_OBJ);

        // Bu ayki metrikler
        $thisMonthStart = date('Y-m-01');
        $thisMonthEnd = date('Y-m-t');
        $stmtThisMonth = $this->db->prepare("SELECT 
                                                COUNT(*) as count,
                                                COUNT(CASE WHEN report_type = 1 THEN 1 END) as ysc_count,
                                                COUNT(CASE WHEN report_type = 2 THEN 1 END) as hst_count
                                            FROM {$this->table} 
                                            WHERE DATE(create_time) BETWEEN ? AND ?");
        $stmtThisMonth->execute([$thisMonthStart, $thisMonthEnd]);
        $summary->this_month = $stmtThisMonth->fetch(PDO::FETCH_OBJ);

        // Geçen ayki metrikler
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
        $stmtLastMonth = $this->db->prepare("SELECT 
                                                COUNT(*) as count,
                                                COUNT(CASE WHEN report_type = 1 THEN 1 END) as ysc_count,
                                                COUNT(CASE WHEN report_type = 2 THEN 1 END) as hst_count
                                            FROM {$this->table} 
                                            WHERE DATE(create_time) BETWEEN ? AND ?");
        $stmtLastMonth->execute([$lastMonthStart, $lastMonthEnd]);
        $summary->last_month = $stmtLastMonth->fetch(PDO::FETCH_OBJ);

        // Aylık büyüme oranı
        if ($summary->last_month->count > 0) {
            $summary->month_growth_rate = round((($summary->this_month->count - $summary->last_month->count) / $summary->last_month->count) * 100, 1);
        } else {
            $summary->month_growth_rate = $summary->this_month->count > 0 ? 100 : 0;
        }

        // YSC ve HST Oranları
        $summary->ysc_rate = $summary->total_count > 0 
            ? round(($summary->ysc_count / $summary->total_count) * 100, 1) 
            : 0;
        $summary->hst_rate = $summary->total_count > 0 
            ? round(($summary->hst_count / $summary->total_count) * 100, 1) 
            : 0;

        return $summary;
    }

    /**
     * Rapor Türlerine Göre Dağılım
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getReportTypeDistribution($startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(r.create_time) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(r.create_time) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    rt.id as type_id,
                    COALESCE(rt.reportName, 'Diğer Raporlar') as report_name,
                    rt.page_link,
                    COUNT(r.id) as report_count
                FROM report_types rt
                LEFT JOIN {$this->table} r ON rt.id = r.report_type AND {$whereClause}
                GROUP BY rt.id, rt.reportName, rt.page_link
                ORDER BY report_count DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        $total = array_sum(array_column($results, 'report_count'));
        foreach ($results as $row) {
            $row->percentage = $total > 0 ? round(($row->report_count / $total) * 100, 1) : 0;
        }

        return $results;
    }

    /**
     * Aylık Rapor Oluşturma Trendleri (Son N Ay)
     *
     * @param int $months
     * @return array
     */
    public function getMonthlyReportTrends($months = 12)
    {
        $results = [];
        $months = max(1, min(24, (int)$months));

        $turkishMonths = [
            '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
            '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
            '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
        ];

        // Son N ayı geriden ileriye doğru oluştur
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthDate = date('Y-m', strtotime("-$i months"));
            $monthNum = date('m', strtotime("-$i months"));
            $yearNum = date('Y', strtotime("-$i months"));
            $label = ($turkishMonths[$monthNum] ?? $monthNum) . ' ' . $yearNum;

            $startDate = date('Y-m-01', strtotime("-$i months"));
            $endDate = date('Y-m-t', strtotime("-$i months"));

            $sql = "SELECT 
                        COUNT(*) as total_count,
                        COUNT(CASE WHEN report_type = 1 THEN 1 END) as ysc_count,
                        COUNT(CASE WHEN report_type = 2 THEN 1 END) as hst_count,
                        COUNT(CASE WHEN report_type NOT IN (1, 2) THEN 1 END) as other_count
                    FROM {$this->table}
                    WHERE DATE(create_time) BETWEEN :start_date AND :end_date";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_OBJ);

            $results[] = [
                'month_key' => $monthDate,
                'label' => $label,
                'total_count' => (int)($data->total_count ?? 0),
                'ysc_count' => (int)($data->ysc_count ?? 0),
                'hst_count' => (int)($data->hst_count ?? 0),
                'other_count' => (int)($data->other_count ?? 0)
            ];
        }

        return $results;
    }

    /**
     * En Çok Rapor Düzenlenen Müşteriler (Top Müşteriler)
     *
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopCustomers($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(r.create_time) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(r.create_time) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int) $limit;

        $sql = "SELECT 
                    r.customer_id,
                    COALESCE(c.company, 'Bilinmeyen Firma') as company_name,
                    c.city,
                    c.sector,
                    c.deleted_at,
                    COUNT(r.id) as total_reports,
                    COUNT(CASE WHEN r.report_type = 1 THEN 1 END) as ysc_reports,
                    COUNT(CASE WHEN r.report_type = 2 THEN 1 END) as hst_reports,
                    COUNT(CASE WHEN r.report_type NOT IN (1, 2) THEN 1 END) as other_reports,
                    MAX(r.create_time) as last_report_date
                FROM {$this->table} r
                LEFT JOIN customers c ON r.customer_id = c.id
                WHERE {$whereClause}
                GROUP BY r.customer_id, c.company, c.city, c.sector, c.deleted_at
                ORDER BY total_reports DESC, last_report_date DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * En Çok Rapor Düzenleyen Kontrolörler / Teknisyenler
     *
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopControllers($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(r.create_time) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(r.create_time) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);
        $limit = (int) $limit;

        $sql = "SELECT 
                    r.controller_id,
                    COALESCE(u.username, 'Bilinmeyen Personel') as controller_name,
                    COALESCE(u.Unvan, 'Kontrolör') as user_title,
                    COUNT(r.id) as total_reports,
                    COUNT(CASE WHEN r.report_type = 1 THEN 1 END) as ysc_reports,
                    COUNT(CASE WHEN r.report_type = 2 THEN 1 END) as hst_reports,
                    COUNT(CASE WHEN r.report_type NOT IN (1, 2) THEN 1 END) as other_reports,
                    MAX(r.create_time) as last_report_date
                FROM {$this->table} r
                LEFT JOIN users u ON r.controller_id = u.id
                WHERE {$whereClause} AND r.controller_id > 0
                GROUP BY r.controller_id, u.username, u.Unvan
                ORDER BY total_reports DESC, last_report_date DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Son Oluşturulan Raporlar
     *
     * @param int $limit
     * @return array
     */
    public function getRecentReports($limit = 10)
    {
        $limit = (int) $limit;

        $sql = "SELECT 
                    r.id,
                    r.report_number,
                    r.report_type,
                    COALESCE(rt.reportName, 'Rapor') as report_name,
                    rt.page_link,
                    r.isemrino,
                    r.control_date,
                    r.validity_date,
                    r.create_time,
                    r.customer_id,
                    COALESCE(c.company, 'Bilinmeyen Firma') as company_name,
                    c.city,
                    COALESCE(u.username, '-') as controller_name
                FROM {$this->table} r
                LEFT JOIN report_types rt ON r.report_type = rt.id
                LEFT JOIN customers c ON r.customer_id = c.id
                LEFT JOIN users u ON r.controller_id = u.id
                ORDER BY r.id DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Geçerliliği Yaklaşan veya Dolmuş Raporlar (Sonraki N Gün)
     *
     * @param int $limit
     * @return array
     */
    public function getExpiringReports($limit = 10)
    {
        $limit = (int) $limit;

        // validity_date formatı DD.MM.YYYY string olduğu için STR_TO_DATE ile sıralama ve filtreleme yapıyoruz
        $sql = "SELECT 
                    r.id,
                    r.report_number,
                    r.report_type,
                    COALESCE(rt.reportName, 'Rapor') as report_name,
                    rt.page_link,
                    r.control_date,
                    r.validity_date,
                    r.customer_id,
                    COALESCE(c.company, 'Bilinmeyen Firma') as company_name,
                    c.city,
                    STR_TO_DATE(r.validity_date, '%d.%m.%Y') as parsed_validity_date,
                    DATEDIFF(STR_TO_DATE(r.validity_date, '%d.%m.%Y'), CURDATE()) as days_left
                FROM {$this->table} r
                LEFT JOIN report_types rt ON r.report_type = rt.id
                LEFT JOIN customers c ON r.customer_id = c.id
                WHERE r.validity_date IS NOT NULL 
                  AND r.validity_date != ''
                  AND STR_TO_DATE(r.validity_date, '%d.%m.%Y') IS NOT NULL
                ORDER BY parsed_validity_date ASC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Tüm Rapor Türleri Listesi
     *
     * @return array
     */
    public function getReportTypesList()
    {
        $stmt = $this->db->prepare("SELECT * FROM report_types ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Belirli bir müşteriye ait tüm raporları, ekipman/kalem dökümlerini ve konsolide icmal özetini getirir
     *
     * @param int $customerId
     * @return array
     */
    public function getCustomerReportSummary($customerId)
    {
        $customerId = (int)$customerId;
        if ($customerId <= 0) {
            return ['reports' => [], 'summary' => []];
        }

        $sql = "SELECT 
                    r.*,
                    COALESCE(rt.reportName, 'Rapor') as report_name,
                    COALESCE(rt.page_link, 'ysc') as page_link,
                    rt.deviceType as device_type,
                    u_cr.username as creator_name,
                    u_co.username as controller_name,
                    u_of.username as company_official_name
                FROM {$this->table} r
                LEFT JOIN report_types rt ON r.report_type = rt.id
                LEFT JOIN users u_cr ON r.creator = u_cr.id
                LEFT JOIN users u_co ON r.controller_id = u_co.id
                LEFT JOIN users u_of ON r.company_official = u_of.id
                WHERE r.customer_id = :cid
                ORDER BY r.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':cid', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($reports)) {
            return [
                'reports' => [],
                'summary' => [
                    'total_reports'     => 0,
                    'total_items'       => 0,
                    'ysc_reports_count' => 0,
                    'ysc_items_count'   => 0,
                    'hst_reports_count' => 0,
                    'hst_items_count'   => 0,
                    'other_reports_count' => 0,
                    'other_items_count' => 0,
                ]
            ];
        }

        $totalItems = 0;
        $yscReportsCount = 0;
        $yscItemsCount = 0;
        $hstReportsCount = 0;
        $hstItemsCount = 0;
        $otherReportsCount = 0;
        $otherItemsCount = 0;

        // Her rapor türü için alt kalemleri hazırlıklı sorgularla çekelim
        $stmtYsc = $this->db->prepare("SELECT * FROM report_ysc_content WHERE report_id = ? ORDER BY id ASC");
        $stmtHst = $this->db->prepare("SELECT * FROM report_hst_content WHERE report_id = ? ORDER BY id ASC");
        $stmtMet = $this->db->prepare("SELECT * FROM report_met_content WHERE report_id = ? ORDER BY id ASC");
        $stmtGen = $this->db->prepare("SELECT * FROM report_contents WHERE report_id = ? ORDER BY id ASC");

        foreach ($reports as &$report) {
            $rid = (int)$report['id'];
            $pageLink = strtolower(trim((string)($report['page_link'] ?? '')));
            $reportType = (int)($report['report_type'] ?? 1);
            $items = [];

            if ($pageLink === 'ysc' || $reportType === 1) {
                $stmtYsc->execute([$rid]);
                $items = $stmtYsc->fetchAll(PDO::FETCH_ASSOC);
                $count = count($items);
                $yscReportsCount++;
                $yscItemsCount += $count;
            } elseif ($pageLink === 'hst' || $reportType === 2) {
                $stmtHst->execute([$rid]);
                $items = $stmtHst->fetchAll(PDO::FETCH_ASSOC);
                $count = count($items);
                $hstReportsCount++;
                $hstItemsCount += $count;
            } elseif ($pageLink === 'met' || $reportType === 3) {
                $stmtMet->execute([$rid]);
                $items = $stmtMet->fetchAll(PDO::FETCH_ASSOC);
                $count = count($items);
                $otherReportsCount++;
                $otherItemsCount += $count;
            } else {
                $stmtGen->execute([$rid]);
                $items = $stmtGen->fetchAll(PDO::FETCH_ASSOC);
                $count = count($items);
                $otherReportsCount++;
                $otherItemsCount += $count;
            }

            $report['items'] = $items;
            $report['item_count'] = count($items);
            $totalItems += count($items);
        }
        unset($report);

        return [
            'reports' => $reports,
            'summary' => [
                'total_reports'       => count($reports),
                'total_items'         => $totalItems,
                'ysc_reports_count'   => $yscReportsCount,
                'ysc_items_count'     => $yscItemsCount,
                'hst_reports_count'   => $hstReportsCount,
                'hst_items_count'     => $hstItemsCount,
                'other_reports_count' => $otherReportsCount,
                'other_items_count'   => $otherItemsCount,
            ]
        ];
    }

    /**
     * Mobil SPA ve Sayfalı Rapor Listesi Sorgusu
     *
     * @param array $options
     * @return array
     */
    public function getReportsPaginated(array $options = []): array
    {
        $page = max(1, (int)($options['page'] ?? 1));
        $limit = max(1, min(100, (int)($options['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $type = $options['type'] ?? 'all';
        $search = trim((string)($options['search'] ?? ''));
        $startDate = $options['start_date'] ?? null;
        $endDate = $options['end_date'] ?? null;
        $sortField = $options['sort_field'] ?? 'r.id';
        $sortDirection = strtoupper($options['sort_direction'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $allowedSorts = [
            'id' => 'r.id',
            'report_number' => 'r.report_number',
            'create_time' => 'r.create_time',
            'control_date' => 'r.control_date',
            'validity_date' => 'r.validity_date',
            'company' => 'c.company'
        ];
        $orderBy = $allowedSorts[$sortField] ?? 'r.id';

        $where = ["1=1"];
        $params = [];

        if ($type !== 'all' && $type !== '' && $type !== null) {
            $where[] = "r.report_type = :type";
            $params[':type'] = (int)$type;
        }

        if (!empty($search)) {
            $where[] = "(r.report_number LIKE :search OR c.company LIKE :search OR r.isemrino LIKE :search OR u.username LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($startDate)) {
            $where[] = "DATE(r.create_time) >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $where[] = "DATE(r.create_time) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        // Toplam kayıt sayısı
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} r 
                     LEFT JOIN customers c ON r.customer_id = c.id 
                     LEFT JOIN users u ON r.controller_id = u.id 
                     WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $totalItems = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        $totalPages = (int)ceil($totalItems / $limit);

        // Sayfalı veri sorgusu
        $dataSql = "SELECT 
                        r.id,
                        r.report_number,
                        r.report_type,
                        COALESCE(rt.reportName, 'Rapor') as report_name,
                        COALESCE(rt.page_link, 'ysc') as page_link,
                        rt.deviceType as device_type,
                        r.isemrino,
                        r.control_date,
                        r.validity_date,
                        r.create_time,
                        r.customer_id,
                        COALESCE(c.company, 'Bilinmeyen Firma') as company_name,
                        c.city as customer_city,
                        c.gsm as customer_phone,
                        COALESCE(u.username, '-') as controller_name,
                        u.Unvan as controller_title
                    FROM {$this->table} r
                    LEFT JOIN report_types rt ON r.report_type = rt.id
                    LEFT JOIN customers c ON r.customer_id = c.id
                    LEFT JOIN users u ON r.controller_id = u.id
                    WHERE {$whereClause}
                    ORDER BY {$orderBy} {$sortDirection}
                    LIMIT :offset, :limit";

        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $val) {
            $dataStmt->bindValue($key, $val);
        }
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->execute();
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        $currentDate = new \DateTime('today');

        // Satır verilerini zenginleştir
        foreach ($rows as &$row) {
            $daysLeft = null;
            $status = 'valid';

            if (!empty($row['validity_date'])) {
                // validity_date formatı DD.MM.YYYY veya DD-MM-YYYY olabilir
                $vStr = trim((string)$row['validity_date']);
                $vDate = \DateTime::createFromFormat('d.m.Y', $vStr) 
                    ?: \DateTime::createFromFormat('d-m-Y', $vStr)
                    ?: \DateTime::createFromFormat('Y-m-d', $vStr);

                if ($vDate) {
                    $diff = $currentDate->diff($vDate);
                    $daysLeft = (int)$diff->format('%r%a');
                    if ($daysLeft < 0) {
                        $status = 'expired';
                    } elseif ($daysLeft <= 30) {
                        $status = 'expiring';
                    } else {
                        $status = 'valid';
                    }
                }
            }

            $row['days_left'] = $daysLeft;
            $row['status'] = $status;
        }
        unset($row);

        return [
            'reports' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_items' => $totalItems,
                'total_pages' => max(1, $totalPages),
                'has_more' => $page < $totalPages
            ]
        ];
    }

    /**
     * Rapor Detayını İlişkili Tablolarla Birlikte Eksiksiz Getirir
     *
     * @param int $reportId
     * @return array|null
     */
    public function getReportFullDetail(int $reportId): ?array
    {
        $reportId = (int)$reportId;
        if ($reportId <= 0) {
            return null;
        }

        $sql = "SELECT 
                    r.*,
                    COALESCE(rt.reportName, 'Rapor') as report_name,
                    COALESCE(rt.page_link, 'ysc') as page_link,
                    rt.deviceType as device_type,
                    COALESCE(c.company, 'Bilinmeyen Firma') as company_name,
                    c.gsm as customer_phone,
                    c.gsm2 as customer_phone2,
                    c.email as customer_email,
                    c.yetkili as customer_contact,
                    c.address as customer_address,
                    c.city as customer_city,
                    c.ilce as customer_district,
                    u_cr.username as creator_name,
                    u_co.username as controller_name,
                    u_co.Unvan as controller_title,
                    u_of.username as company_official_name
                FROM {$this->table} r
                LEFT JOIN report_types rt ON r.report_type = rt.id
                LEFT JOIN customers c ON r.customer_id = c.id
                LEFT JOIN users u_cr ON r.creator = u_cr.id
                LEFT JOIN users u_co ON r.controller_id = u_co.id
                LEFT JOIN users u_of ON r.company_official = u_of.id
                WHERE r.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            return null;
        }

        $pageLink = strtolower(trim((string)($report['page_link'] ?? 'ysc')));
        $reportType = (int)($report['report_type'] ?? 1);
        $items = [];

        if ($pageLink === 'ysc' || $reportType === 1) {
            $stmtYsc = $this->db->prepare("SELECT * FROM report_ysc_content WHERE report_id = ? ORDER BY id ASC");
            $stmtYsc->execute([$reportId]);
            $items = $stmtYsc->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($pageLink === 'hst' || $reportType === 2) {
            $stmtHst = $this->db->prepare("SELECT * FROM report_hst_content WHERE report_id = ? ORDER BY id ASC");
            $stmtHst->execute([$reportId]);
            $items = $stmtHst->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($pageLink === 'met' || $reportType === 3) {
            $stmtMet = $this->db->prepare("SELECT * FROM report_met_content WHERE report_id = ? ORDER BY id ASC");
            $stmtMet->execute([$reportId]);
            $items = $stmtMet->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmtGen = $this->db->prepare("SELECT * FROM report_contents WHERE report_id = ? ORDER BY id ASC");
            $stmtGen->execute([$reportId]);
            $items = $stmtGen->fetchAll(PDO::FETCH_ASSOC);
        }

        $report['items'] = $items;
        $report['item_count'] = count($items);

        // JSON decode edilecek alanlar
        if (!empty($report['report_matters'])) {
            $report['report_matters_data'] = json_decode((string)$report['report_matters'], true) ?: null;
        }
        if (!empty($report['bakim_bilgileri'])) {
            $report['bakim_bilgileri_data'] = json_decode((string)$report['bakim_bilgileri'], true) ?: null;
        }
        if (!empty($report['dedektor_info'])) {
            $report['dedektor_info_data'] = json_decode((string)$report['dedektor_info'], true) ?: null;
        }

        // Kalan gün hesabı
        $daysLeft = null;
        $status = 'valid';
        if (!empty($report['validity_date'])) {
            $currentDate = new \DateTime('today');
            $vStr = trim((string)$report['validity_date']);
            $vDate = \DateTime::createFromFormat('d.m.Y', $vStr) 
                ?: \DateTime::createFromFormat('d-m-Y', $vStr)
                ?: \DateTime::createFromFormat('Y-m-d', $vStr);

            if ($vDate) {
                $diff = $currentDate->diff($vDate);
                $daysLeft = (int)$diff->format('%r%a');
                $status = $daysLeft < 0 ? 'expired' : ($daysLeft <= 30 ? 'expiring' : 'valid');
            }
        }
        $report['days_left'] = $daysLeft;
        $report['status'] = $status;

        return $report;
    }

    /**
     * Rapor KPI ve İstatistik Özeti
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getReportsKpi(?string $startDate = null, ?string $endDate = null): array
    {
        $summary = $this->getDashboardSummary($startDate, $endDate);

        // Yaklaşan veya dolmuş rapor sayısı
        $expiringCount = 0;
        try {
            $expStmt = $this->db->query("SELECT COUNT(*) FROM reports WHERE validity_date IS NOT NULL AND validity_date != '' AND STR_TO_DATE(validity_date, '%d.%m.%Y') <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
            if ($expStmt) {
                $expiringCount = (int)$expStmt->fetchColumn();
            }
        } catch (\Throwable $e) {}

        return [
            'total_count' => (int)($summary->total_count ?? 0),
            'this_month_count' => (int)($summary->this_month->count ?? 0),
            'unique_customers' => (int)($summary->unique_customers ?? 0),
            'ysc_count' => (int)($summary->ysc_count ?? 0),
            'hst_count' => (int)($summary->hst_count ?? 0),
            'met_count' => (int)($summary->met_count ?? 0),
            'yas_count' => (int)($summary->yas_count ?? 0),
            'oys_count' => (int)($summary->oys_count ?? 0),
            'aas_count' => (int)($summary->aas_count ?? 0),
            'other_count' => (int)($summary->other_count ?? 0),
            'expiring_count' => $expiringCount,
            'month_growth_rate' => $summary->month_growth_rate ?? 0,
            'ysc_rate' => $summary->ysc_rate ?? 0,
            'hst_rate' => $summary->hst_rate ?? 0,
        ];
    }

    /**
     * Raporu ve Bağlı Kalemlerini Güvenli Şekilde Siler
     *
     * @param int $reportId
     * @return bool
     */
    public function deleteReportById(int $reportId): bool
    {
        $reportId = (int)$reportId;
        if ($reportId <= 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Alt kalemleri temizle
            $this->db->prepare("DELETE FROM report_ysc_content WHERE report_id = ?")->execute([$reportId]);
            $this->db->prepare("DELETE FROM report_hst_content WHERE report_id = ?")->execute([$reportId]);
            $this->db->prepare("DELETE FROM report_met_content WHERE report_id = ?")->execute([$reportId]);
            $this->db->prepare("DELETE FROM report_contents WHERE report_id = ?")->execute([$reportId]);

            // Ana rapor kaydını sil
            $delStmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $delStmt->execute([$reportId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("deleteReportById error: " . $e->getMessage());
            return false;
        }
    }
}

