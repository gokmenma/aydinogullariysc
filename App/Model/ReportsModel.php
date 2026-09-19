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
}
