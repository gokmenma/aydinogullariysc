<?php

namespace App\Model;


use App\Model\BaseModel;
use PDO;

use App\Helper\Date;

class ReportControlModel extends BaseModel
{
    protected $db;
    protected $control_table = 'sqlsonkullanmatarihi';
    protected $validity_table = 'sqlvalidity_date';

    /**
     * @param PDO|null $db Optional explicit PDO injection (recommended for scripts).
     */
    public function __construct(?PDO $db = null)
    {
        // Prefer explicit injection
        if ($db instanceof PDO) {
            $this->db = $db;
            return;
        }

        // Backwards-compat: try global $ac (defined by configs/config.php or bootstrap.php)
        global $ac;
        if (isset($ac) && $ac instanceof PDO) {
            $this->db = $ac;
            return;
        }

        // If called from standalone scripts that forgot bootstrap, load it.
        // This will set global $ac.
        $bootstrapPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_file($bootstrapPath)) {
            require_once $bootstrapPath;
        } else {
            // Fallback (in case bootstrap.php isn't present or path differs)
            $configPath = $_SERVER['DOCUMENT_ROOT'] . '/configs/config.php';
            if (!empty($_SERVER['DOCUMENT_ROOT']) && is_file($configPath)) {
                require_once $configPath;
            }
        }

        global $ac;
        $this->db = (isset($ac) && $ac instanceof PDO) ? $ac : null;
    }

    public function getReportFillingList($month, $year)
    {
        $query = "SELECT report_id,report_number, firma_adi,bulundugu_bolge,cihaz_no, ay, yil FROM $this->control_table";
        $conditions = [];
        $params = [];

        if (!empty($year)) {
            $conditions[] = 'yil = ?';
            $params[] = $year;
        }

        if (!empty($month)) {
            $conditions[] = 'ay = ?';
            $params[] = Date::setMonth($month);
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        //$query .= ' GROUP BY firma_adi';

        $sql = $this->db->prepare($query);
        $sql->execute($params);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getReportControlList($month, $year)
    {
        $query = "SELECT report_id,report_number, firma_adi,validity_date, ay, yil FROM $this->validity_table";
        $conditions = [];
        $params = [];

        if (!empty($year)) {
            $conditions[] = 'yil = ?';
            $params[] = $year;
        }

        if (!empty($month)) {
            $conditions[] = 'ay = ?';
            $params[] = Date::setMonth($month);
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= ' GROUP BY firma_adi';

        $sql = $this->db->prepare($query);
        $sql->execute($params);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Dolum Tarihi Gelen Cihazlar - Sayfalı ve Filtreli Liste
     *
     * @param array $options
     * @return array
     */
    public function getReportFillingListPaginated(array $options = []): array
    {
        $page = max(1, (int)($options['page'] ?? 1));
        $limit = max(1, min(100, (int)($options['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $month = $options['month'] ?? null;
        $year = $options['year'] ?? null;
        $search = trim((string)($options['search'] ?? ''));

        $conditions = ["1=1"];
        $params = [];

        if (!empty($year) && $year !== 'all') {
            $conditions[] = 'yil = :year';
            $params[':year'] = (string)$year;
        }

        if (!empty($month) && $month !== 'all') {
            $conditions[] = 'ay = :month';
            $params[':month'] = Date::setMonth($month);
        }

        if (!empty($search)) {
            $conditions[] = '(firma_adi LIKE :search OR report_number LIKE :search OR cihaz_no LIKE :search OR bulundugu_bolge LIKE :search)';
            $params[':search'] = "%{$search}%";
        }

        $whereClause = implode(' AND ', $conditions);

        // Toplam kayıt sayısı
        $countQuery = "SELECT COUNT(*) as total FROM {$this->control_table} WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countQuery);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $totalItems = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        $totalPages = (int)ceil($totalItems / $limit);

        // Sayfalı veri sorgusu
        $query = "SELECT content_id, report_id, report_number, firma_adi, bulundugu_bolge, cihaz_no, cihaz_sonkullanma_tarihi, ay, yil 
                  FROM {$this->control_table} 
                  WHERE {$whereClause}
                  ORDER BY yil ASC, ay ASC, report_id DESC
                  LIMIT :offset, :limit";

        $stmt = $this->db->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $curYear = (int)date('Y');
        $curMonth = (int)date('m');

        foreach ($items as &$item) {
            $itemYear = (int)($item['yil'] ?? 0);
            $itemMonth = (int)($item['ay'] ?? 0);

            if ($itemYear < $curYear || ($itemYear === $curYear && $itemMonth < $curMonth)) {
                $item['status'] = 'expired'; // Dolum tarihi geçmiş
            } elseif ($itemYear === $curYear && $itemMonth === $curMonth) {
                $item['status'] = 'this_month'; // Bu ay dolacak
            } else {
                $item['status'] = 'upcoming'; // Gelecek dönem
            }
        }
        unset($item);

        return [
            'items' => $items,
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
     * Dolum Listesi KPI İstatistikleri
     *
     * @param string|null $month
     * @param string|null $year
     * @return array
     */
    public function getReportFillingKpi(?string $month = null, ?string $year = null): array
    {
        $conditions = ["1=1"];
        $params = [];

        if (!empty($year) && $year !== 'all') {
            $conditions[] = 'yil = :year';
            $params[':year'] = (string)$year;
        }

        if (!empty($month) && $month !== 'all') {
            $conditions[] = 'ay = :month';
            $params[':month'] = Date::setMonth($month);
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "SELECT 
                    COUNT(*) as total_count,
                    COUNT(DISTINCT firma_adi) as unique_companies,
                    COUNT(DISTINCT report_id) as unique_reports
                FROM {$this->control_table} 
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_count' => (int)($res['total_count'] ?? 0),
            'unique_companies' => (int)($res['unique_companies'] ?? 0),
            'unique_reports' => (int)($res['unique_reports'] ?? 0),
            'selected_month' => $month ?: 'all',
            'selected_year' => $year ?: 'all',
        ];
    }

    /**
     * Kontrol / Geçerlilik Listesi - Sayfalı ve Filtreli
     *
     * @param array $options
     * @return array
     */
    public function getReportControlListPaginated(array $options = []): array
    {
        $page = max(1, (int)($options['page'] ?? 1));
        $limit = max(1, min(100, (int)($options['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $month = $options['month'] ?? null;
        $year = $options['year'] ?? null;
        $search = trim((string)($options['search'] ?? ''));

        $conditions = ["1=1"];
        $params = [];

        if (!empty($year) && $year !== 'all') {
            $conditions[] = 'yil = :year';
            $params[':year'] = (string)$year;
        }

        if (!empty($month) && $month !== 'all') {
            $conditions[] = 'ay = :month';
            $params[':month'] = Date::setMonth($month);
        }

        if (!empty($search)) {
            $conditions[] = '(firma_adi LIKE :search OR report_number LIKE :search)';
            $params[':search'] = "%{$search}%";
        }

        $whereClause = implode(' AND ', $conditions);

        $countQuery = "SELECT COUNT(*) as total FROM {$this->validity_table} WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countQuery);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $totalItems = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        $totalPages = (int)ceil($totalItems / $limit);

        $query = "SELECT report_id, report_number, firma_adi, validity_date, ay, yil 
                  FROM {$this->validity_table} 
                  WHERE {$whereClause}
                  ORDER BY yil ASC, ay ASC, report_id DESC
                  LIMIT :offset, :limit";

        $stmt = $this->db->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $curYear = (int)date('Y');
        $curMonth = (int)date('m');

        foreach ($items as &$item) {
            $itemYear = (int)($item['yil'] ?? 0);
            $itemMonth = (int)($item['ay'] ?? 0);

            if ($itemYear < $curYear || ($itemYear === $curYear && $itemMonth < $curMonth)) {
                $item['status'] = 'expired';
            } elseif ($itemYear === $curYear && $itemMonth === $curMonth) {
                $item['status'] = 'this_month';
            } else {
                $item['status'] = 'upcoming';
            }
        }
        unset($item);

        return [
            'items' => $items,
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
     * Kontrol Listesi KPI İstatistikleri
     *
     * @param string|null $month
     * @param string|null $year
     * @return array
     */
    public function getReportControlKpi(?string $month = null, ?string $year = null): array
    {
        $conditions = ["1=1"];
        $params = [];

        if (!empty($year) && $year !== 'all') {
            $conditions[] = 'yil = :year';
            $params[':year'] = (string)$year;
        }

        if (!empty($month) && $month !== 'all') {
            $conditions[] = 'ay = :month';
            $params[':month'] = Date::setMonth($month);
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "SELECT 
                    COUNT(*) as total_count,
                    COUNT(DISTINCT firma_adi) as unique_companies,
                    COUNT(DISTINCT report_id) as unique_reports
                FROM {$this->validity_table} 
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_count' => (int)($res['total_count'] ?? 0),
            'unique_companies' => (int)($res['unique_companies'] ?? 0),
            'unique_reports' => (int)($res['unique_reports'] ?? 0),
            'selected_month' => $month ?: 'all',
            'selected_year' => $year ?: 'all',
        ];
    }
}
