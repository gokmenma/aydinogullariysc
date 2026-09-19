<?php

namespace App\Model;

use App\Model\BaseModel;
use PDO;

class PurchaseModel extends BaseModel
{
    protected $table = 'purchases';
    protected $item_table = 'purchase_items';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Satın almanın ürünlerini getirir
     */
    public function getPurchaseItems($id)
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->item_table} WHERE purID = ?");
        $sql->execute([$id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Satın almanın ürünlerini siler
     */
    public function deletePurchaseItems($id)
    {
        $sql = $this->db->prepare("DELETE FROM {$this->item_table} WHERE purID = ?");
        $sql->execute([$id]);
    }

    /**
     * Satın almanın ürünlerini ekler
     */
    public function savePurchaseItems($data)
    {
        $this->table = $this->item_table;
        return $this->save($data);
    }

    /**
     * String veya karışık formatlı para değerini float sayıya dönüştürür
     */
    public static function parseAmount($value)
    {
        if (empty($value)) {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        $clean = trim((string)$value);
        // Örn: 7.137.320,02 -> 7137320.02
        if (strpos($clean, ',') !== false) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }
        return (float)$clean;
    }

    /**
     * Satın Alma Dashboard KPI Özet İstatistikleri
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
            $where[] = "DATE(p.create_time) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.create_time) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    p.id,
                    p.altToplam,
                    p.ToplamTL,
                    p.TLTotal,
                    p.state,
                    p.type,
                    p.companyID,
                    p.creator,
                    p.create_time
                FROM {$this->table} p
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $summary = (object)[
            'total_count' => count($rows),
            'pending_count' => 0,
            'approved_count' => 0,
            'completed_count' => 0,
            'rejected_count' => 0,
            'demand_count' => 0,
            'price_request_count' => 0,
            'order_count' => 0,
            'total_amount' => 0.0,
            'completed_amount' => 0.0,
            'pending_amount' => 0.0,
            'avg_amount' => 0.0,
            'unique_suppliers' => 0,
            'completion_rate' => 0.0
        ];

        $suppliers = [];

        foreach ($rows as $row) {
            $amount = self::parseAmount($row->altToplam);
            if ($amount <= 0) {
                $amount = self::parseAmount($row->ToplamTL);
            }
            if ($amount <= 0) {
                $amount = self::parseAmount($row->TLTotal);
            }

            $summary->total_amount += $amount;

            $st = (int)($row->state ?? 0);
            if ($st === 0) {
                $summary->pending_count++;
                $summary->pending_amount += $amount;
            } elseif ($st === 1) {
                $summary->approved_count++;
                $summary->pending_amount += $amount;
            } elseif ($st === 2) {
                $summary->completed_count++;
                $summary->completed_amount += $amount;
            } elseif ($st === 3) {
                $summary->rejected_count++;
            }

            $tp = (int)($row->type ?? 0);
            if ($tp === 1) {
                $summary->demand_count++;
            } elseif ($tp === 2) {
                $summary->price_request_count++;
            } else {
                $summary->order_count++;
            }

            if (!empty($row->companyID)) {
                $suppliers[$row->companyID] = true;
            }
        }

        $summary->unique_suppliers = count($suppliers);
        $summary->completion_rate = $summary->total_count > 0 
            ? round(($summary->completed_count / $summary->total_count) * 100, 1) 
            : 0;
        $summary->avg_amount = $summary->total_count > 0 
            ? round($summary->total_amount / $summary->total_count, 2) 
            : 0;

        // Bu ayki metrikler
        $thisMonthStart = date('Y-m-01');
        $thisMonthEnd = date('Y-m-t');
        $stmtThisMonth = $this->db->prepare("SELECT altToplam, ToplamTL, TLTotal, state FROM {$this->table} WHERE DATE(create_time) BETWEEN ? AND ?");
        $stmtThisMonth->execute([$thisMonthStart, $thisMonthEnd]);
        $thisMonthRows = $stmtThisMonth->fetchAll(PDO::FETCH_OBJ);

        $thisMonthCount = count($thisMonthRows);
        $thisMonthAmount = 0.0;
        $thisMonthCompleted = 0.0;
        foreach ($thisMonthRows as $tm) {
            $amt = self::parseAmount($tm->altToplam) ?: (self::parseAmount($tm->ToplamTL) ?: self::parseAmount($tm->TLTotal));
            $thisMonthAmount += $amt;
            if ((int)$tm->state === 2) {
                $thisMonthCompleted += $amt;
            }
        }

        $summary->this_month = (object)[
            'count' => $thisMonthCount,
            'amount' => $thisMonthAmount,
            'completed_amount' => $thisMonthCompleted
        ];

        // Geçen ayki metrikler
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
        $stmtLastMonth = $this->db->prepare("SELECT altToplam, ToplamTL, TLTotal, state FROM {$this->table} WHERE DATE(create_time) BETWEEN ? AND ?");
        $stmtLastMonth->execute([$lastMonthStart, $lastMonthEnd]);
        $lastMonthRows = $stmtLastMonth->fetchAll(PDO::FETCH_OBJ);

        $lastMonthCount = count($lastMonthRows);
        $lastMonthAmount = 0.0;
        foreach ($lastMonthRows as $lm) {
            $amt = self::parseAmount($lm->altToplam) ?: (self::parseAmount($lm->ToplamTL) ?: self::parseAmount($lm->TLTotal));
            $lastMonthAmount += $amt;
        }

        $summary->last_month = (object)[
            'count' => $lastMonthCount,
            'amount' => $lastMonthAmount
        ];

        // Aylık büyüme oranı
        if ($summary->last_month->amount > 0) {
            $summary->month_growth_rate = round((($summary->this_month->amount - $summary->last_month->amount) / $summary->last_month->amount) * 100, 1);
        } else {
            $summary->month_growth_rate = $summary->this_month->amount > 0 ? 100 : 0;
        }

        return $summary;
    }

    /**
     * Durum Dağılımı (Donut Grafiği)
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
            $where[] = "DATE(create_time) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(create_time) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    COALESCE(state, 0) as state,
                    COUNT(*) as count
                FROM {$this->table}
                WHERE {$whereClause}
                GROUP BY state";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $counts = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $counts[(int)$row->state] = (int)$row->count;
        }

        $labels = [
            0 => 'Bekliyor',
            1 => 'Onaylandı',
            2 => 'Tamamlandı',
            3 => 'Reddedildi'
        ];

        $colors = [
            0 => '#f59e0b', // Amber
            1 => '#3b82f6', // Blue
            2 => '#10b981', // Emerald
            3 => '#ef4444'  // Red
        ];

        $result = [];
        foreach ($labels as $st => $lbl) {
            $result[] = (object)[
                'state' => $st,
                'label' => $lbl,
                'count' => $counts[$st] ?? 0,
                'color' => $colors[$st]
            ];
        }

        return $result;
    }

    /**
     * İşlem Tipi Dağılımı (Donut Grafiği)
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTypeDistribution($startDate = null, $endDate = null)
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
                    COALESCE(type, 0) as type,
                    COUNT(*) as count
                FROM {$this->table}
                WHERE {$whereClause}
                GROUP BY type";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $counts = [0 => 0, 1 => 0, 2 => 0];
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $counts[(int)$row->type] = (int)$row->count;
        }

        return [
            (object)['type' => 0, 'label' => 'Satın Alma Siparişi', 'count' => $counts[0], 'color' => '#10b981'],
            (object)['type' => 1, 'label' => 'Satın Alma Talebi', 'count' => $counts[1], 'color' => '#3b82f6'],
            (object)['type' => 2, 'label' => 'Fiyat Talebi', 'count' => $counts[2], 'color' => '#f59e0b']
        ];
    }

    /**
     * Aylık Satın Alma & Harcama Trendleri (Son N Ay)
     *
     * @param int $months
     * @return array
     */
    public function getMonthlyPurchaseTrends($months = 12)
    {
        $results = [];
        $months = max(1, min(24, (int)$months));

        $turkishMonths = [
            '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
            '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
            '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
        ];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthDate = date('Y-m', strtotime("-$i months"));
            $monthNum = date('m', strtotime("-$i months"));
            $yearNum = date('Y', strtotime("-$i months"));
            $label = ($turkishMonths[$monthNum] ?? $monthNum) . ' ' . $yearNum;

            $startDate = date('Y-m-01', strtotime("-$i months"));
            $endDate = date('Y-m-t', strtotime("-$i months"));

            $sql = "SELECT 
                        id, altToplam, ToplamTL, TLTotal, state
                    FROM {$this->table}
                    WHERE DATE(create_time) BETWEEN :start_date AND :end_date";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $startDate);
            $stmt->bindValue(':end_date', $endDate);
            $stmt->execute();
            $monthRows = $stmt->fetchAll(PDO::FETCH_OBJ);

            $totalCount = count($monthRows);
            $completedCount = 0;
            $totalAmount = 0.0;
            $completedAmount = 0.0;

            foreach ($monthRows as $r) {
                $amt = self::parseAmount($r->altToplam) ?: (self::parseAmount($r->ToplamTL) ?: self::parseAmount($r->TLTotal));
                $totalAmount += $amt;
                if ((int)$r->state === 2) {
                    $completedCount++;
                    $completedAmount += $amt;
                }
            }

            $results[] = [
                'month_key' => $monthDate,
                'label' => $label,
                'total_orders' => $totalCount,
                'completed_orders' => $completedCount,
                'total_amount' => round($totalAmount, 2),
                'completed_amount' => round($completedAmount, 2)
            ];
        }

        return $results;
    }

    /**
     * En Çok Satın Alma Yapılan Tedarikçiler / Firmalar
     *
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopSuppliers($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["p.companyID > 0"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(p.create_time) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.create_time) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    p.id,
                    p.companyID,
                    p.altToplam,
                    p.ToplamTL,
                    p.TLTotal,
                    p.state,
                    p.create_time,
                    COALESCE(c.company, 'Bilinmeyen Firma') as company_name,
                    c.city,
                    c.sector,
                    c.deleted_at
                FROM {$this->table} p
                LEFT JOIN customers c ON p.companyID = c.id
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $suppliers = [];
        foreach ($rows as $row) {
            $cid = $row->companyID;
            if (!isset($suppliers[$cid])) {
                $suppliers[$cid] = (object)[
                    'companyID' => $cid,
                    'company_name' => $row->company_name,
                    'city' => $row->city,
                    'sector' => $row->sector,
                    'deleted_at' => $row->deleted_at,
                    'total_orders' => 0,
                    'completed_orders' => 0,
                    'total_amount' => 0.0,
                    'last_order_date' => $row->create_time
                ];
            }

            $suppliers[$cid]->total_orders++;
            if ((int)$row->state === 2) {
                $suppliers[$cid]->completed_orders++;
            }

            $amt = self::parseAmount($row->altToplam) ?: (self::parseAmount($row->ToplamTL) ?: self::parseAmount($row->TLTotal));
            $suppliers[$cid]->total_amount += $amt;

            if (strtotime($row->create_time) > strtotime($suppliers[$cid]->last_order_date)) {
                $suppliers[$cid]->last_order_date = $row->create_time;
            }
        }

        // Toplam tutara ve sipariş sayısına göre sırala
        usort($suppliers, function ($a, $b) {
            if ($b->total_amount == $a->total_amount) {
                return $b->total_orders <=> $a->total_orders;
            }
            return $b->total_amount <=> $a->total_amount;
        });

        return array_slice($suppliers, 0, (int)$limit);
    }

    /**
     * En Çok Talep / Sipariş Açan Personeller
     *
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopRequesters($limit = 10, $startDate = null, $endDate = null)
    {
        $where = ["p.creator > 0"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(p.create_time) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(p.create_time) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    p.id,
                    p.creator,
                    p.altToplam,
                    p.ToplamTL,
                    p.TLTotal,
                    p.state,
                    p.create_time,
                    COALESCE(u.username, 'Bilinmeyen Personel') as user_name,
                    COALESCE(u.Unvan, 'Personel') as user_title
                FROM {$this->table} p
                LEFT JOIN users u ON p.creator = u.id
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $requesters = [];
        foreach ($rows as $row) {
            $uid = $row->creator;
            if (!isset($requesters[$uid])) {
                $requesters[$uid] = (object)[
                    'user_id' => $uid,
                    'user_name' => $row->user_name,
                    'user_title' => $row->user_title,
                    'total_orders' => 0,
                    'completed_orders' => 0,
                    'total_amount' => 0.0,
                    'last_order_date' => $row->create_time
                ];
            }

            $requesters[$uid]->total_orders++;
            if ((int)$row->state === 2) {
                $requesters[$uid]->completed_orders++;
            }

            $amt = self::parseAmount($row->altToplam) ?: (self::parseAmount($row->ToplamTL) ?: self::parseAmount($row->TLTotal));
            $requesters[$uid]->total_amount += $amt;

            if (strtotime($row->create_time) > strtotime($requesters[$uid]->last_order_date)) {
                $requesters[$uid]->last_order_date = $row->create_time;
            }
        }

        usort($requesters, function ($a, $b) {
            if ($b->total_orders == $a->total_orders) {
                return $b->total_amount <=> $a->total_amount;
            }
            return $b->total_orders <=> $a->total_orders;
        });

        return array_slice($requesters, 0, (int)$limit);
    }

    /**
     * Son Eklenen Satın Alma / Talep Kayıtları
     *
     * @param int $limit
     * @return array
     */
    public function getRecentPurchases($limit = 10)
    {
        $limit = (int)$limit;

        $sql = "SELECT 
                    p.id,
                    p.siparisNo,
                    p.companyID,
                    COALESCE(c.company, 'Bilinmeyen Tedarikçi') as company_name,
                    c.city,
                    p.altToplam,
                    p.ToplamTL,
                    p.TLTotal,
                    p.state,
                    p.type,
                    p.deadline,
                    p.payment_period,
                    p.invoice_number,
                    p.invoice_date,
                    p.create_time,
                    COALESCE(u.username, 'Sistem') as creator_name
                FROM {$this->table} p
                LEFT JOIN customers c ON p.companyID = c.id
                LEFT JOIN users u ON p.creator = u.id
                ORDER BY p.id DESC
                LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($results as $r) {
            $r->parsed_amount = self::parseAmount($r->altToplam) ?: (self::parseAmount($r->ToplamTL) ?: self::parseAmount($r->TLTotal));
        }

        return $results;
    }
}
