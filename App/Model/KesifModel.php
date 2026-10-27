<?php 

namespace App\Model;

use PDO;
use App\Model\BaseModel;


class KesifModel extends BaseModel
{
    protected $table = 'kesifler';

    public function __construct()
    {
       parent::__construct($this->table);
    }

    /**
     * Tüm kesifleri getir (silinmemiş olanları)
     */
    public function getAllActive()
    {
        $sql = $this->db->prepare("SELECT k.*, u.username AS kullanici_adi FROM $this->table k
                                          LEFT JOIN users u ON u.id = k.kayit_yapan
                                          WHERE k.silinme_tarihi IS NULL
                                          ORDER BY k.kesif_tarihi DESC");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * ID'ye göre keşif getir (silinmemiş olanları)
     */
    public function findActive($id)
    {
        $sql = $this->db->prepare("SELECT k.*, 
                                          u1.username AS kullanici_adi,
                                          u2.username AS guncelleyen_adi
                                   FROM $this->table k
                                   LEFT JOIN users u1 ON u1.id = k.kayit_yapan
                                   LEFT JOIN users u2 ON u2.id = k.guncelleyen_kullanici
                                   WHERE k.id = ? AND k.silinme_tarihi IS NULL");
        $sql->execute([$id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Keşifi sil (Soft Delete)
     */
    public function softDelete($id, $userId)
    {
        $sql = $this->db->prepare("UPDATE $this->table SET silinme_tarihi = NOW(), silen_kullanici = ? WHERE id = ?");
        return $sql->execute([$userId, $id]);
    }

    /**
     * Keşif özet istatistiklerini getir
     */
    public function getSummaryStats()
    {
        $sql = $this->db->prepare("SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN durum = 'bekliyor' THEN 1 ELSE 0 END) as bekleyen_count,
            SUM(CASE WHEN durum = 'iptal_edildi' THEN 1 ELSE 0 END) as iptal_count,
            SUM(CASE WHEN durum IN ('teklif_hazirlandi', 'teklif_gonderildi') THEN 1 ELSE 0 END) as teklif_count,
            SUM(CASE WHEN durum = 'teklif_hazirlandi' THEN 1 ELSE 0 END) as teklif_hazirlandi_count,
            SUM(CASE WHEN durum = 'teklif_gonderildi' THEN 1 ELSE 0 END) as teklif_gonderildi_count,
            SUM(CASE WHEN durum = 'kesif_tamamlandi' THEN 1 ELSE 0 END) as tamamlanan_count,
            SUM(CASE WHEN kesif_tarihi >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00') THEN 1 ELSE 0 END) as this_month_count,
            SUM(CASE WHEN DATE(kesif_tarihi) = CURDATE() THEN 1 ELSE 0 END) as today_count,
            SUM(CASE WHEN durum = 'bekliyor' AND kesif_tarihi < NOW() THEN 1 ELSE 0 END) as overdue_count
        FROM $this->table 
        WHERE silinme_tarihi IS NULL");
        $sql->execute();
        return $sql->fetch(PDO::FETCH_ASSOC) ?: [
            'total_count' => 0,
            'bekleyen_count' => 0,
            'iptal_count' => 0,
            'teklif_count' => 0,
            'teklif_hazirlandi_count' => 0,
            'teklif_gonderildi_count' => 0,
            'tamamlanan_count' => 0,
            'this_month_count' => 0,
            'today_count' => 0,
            'overdue_count' => 0
        ];
    }

    /**
     * Filtreli ve sayfalanmış keşif listesi
     */
    public function getPaginatedList(array $filters = [], int $page = 1, int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $where = ["k.silinme_tarihi IS NULL"];
        $params = [];

        // Durum filtresi
        $status = trim((string) ($filters['status'] ?? 'all'));
        if ($status !== '' && $status !== 'all') {
            if ($status === 'teklif') {
                $where[] = "k.durum IN ('teklif_hazirlandi', 'teklif_gonderildi')";
            } elseif ($status === 'bugun') {
                $where[] = "DATE(k.kesif_tarihi) = CURDATE()";
            } elseif ($status === 'gecikmis') {
                $where[] = "k.durum = 'bekliyor' AND k.kesif_tarihi < NOW()";
            } elseif (in_array($status, ['bekliyor', 'kesif_tamamlandi', 'teklif_hazirlandi', 'teklif_gonderildi', 'iptal_edildi'], true)) {
                $where[] = "k.durum = ?";
                $params[] = $status;
            }
        }

        // Arama filtresi
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = "(k.firma LIKE ? OR k.yapilacak_is LIKE ? OR k.konum LIKE ? OR k.gidecek_kisi LIKE ? OR k.formun_bulundugu_kisi LIKE ? OR k.kesif_sonu_notu LIKE ? OR u1.username LIKE ?)";
            for ($i = 0; $i < 7; $i++) {
                $params[] = $like;
            }
        }

        $whereSql = implode(' AND ', $where);

        // Toplam kayıt sayısı
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} k LEFT JOIN users u1 ON u1.id = k.kayit_yapan WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Sayfalı veri
        $sql = "SELECT k.*, 
                       u1.username AS kullanici_adi,
                       u2.username AS guncelleyen_adi
                FROM {$this->table} k
                LEFT JOIN users u1 ON u1.id = k.kayit_yapan
                LEFT JOIN users u2 ON u2.id = k.guncelleyen_kullanici
                WHERE {$whereSql}
                ORDER BY k.kesif_tarihi DESC, k.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_OBJ);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) ceil($total / $limit),
            'has_more' => ($offset + count($items)) < $total,
        ];
    }

    /**
     * Hızlı durum güncelleme
     */
    public function updateStatus(int $id, string $durum, int $userId, ?string $note = null): bool
    {
        $allowed = ['bekliyor', 'kesif_tamamlandi', 'teklif_hazirlandi', 'teklif_gonderildi', 'iptal_edildi'];
        if (!in_array($durum, $allowed, true)) {
            return false;
        }

        if ($note !== null && trim($note) !== '') {
            $sql = $this->db->prepare("UPDATE {$this->table} 
                SET durum = ?, kesif_sonu_notu = ?, guncelleme_tarihi = NOW(), guncelleyen_kullanici = ? 
                WHERE id = ? AND silinme_tarihi IS NULL");
            return $sql->execute([$durum, trim($note), $userId, $id]);
        }

        $sql = $this->db->prepare("UPDATE {$this->table} 
            SET durum = ?, guncelleme_tarihi = NOW(), guncelleyen_kullanici = ? 
            WHERE id = ? AND silinme_tarihi IS NULL");
        return $sql->execute([$durum, $userId, $id]);
    }

    /**
     * Tarih aralığına göre dashboard özetini getirir.
     */
    public function getDashboardSummary($startDate = null, $endDate = null)
    {
        [$dateSql, $params] = $this->buildDashboardDateFilter($startDate, $endDate);
        $sql = $this->db->prepare("SELECT
            COUNT(*) AS total_count,
            SUM(CASE WHEN durum = 'bekliyor' THEN 1 ELSE 0 END) AS waiting_count,
            SUM(CASE WHEN durum = 'kesif_tamamlandi' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN durum IN ('teklif_hazirlandi', 'teklif_gonderildi') THEN 1 ELSE 0 END) AS offer_count,
            SUM(CASE WHEN durum = 'iptal_edildi' THEN 1 ELSE 0 END) AS cancelled_count,
            SUM(CASE WHEN durum = 'bekliyor' AND kesif_tarihi < CURDATE() THEN 1 ELSE 0 END) AS overdue_count,
            SUM(CASE WHEN durum = 'bekliyor' AND DATE(kesif_tarihi) = CURDATE() THEN 1 ELSE 0 END) AS today_count,
            SUM(CASE WHEN durum = 'bekliyor' AND kesif_tarihi > CURDATE() THEN 1 ELSE 0 END) AS upcoming_count
        FROM {$this->table}
        WHERE silinme_tarihi IS NULL {$dateSql}");
        $sql->execute($params);
        return $sql->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getDashboardStatusDistribution($startDate = null, $endDate = null)
    {
        [$dateSql, $params] = $this->buildDashboardDateFilter($startDate, $endDate);
        $sql = $this->db->prepare("SELECT durum, COUNT(*) AS total
            FROM {$this->table}
            WHERE silinme_tarihi IS NULL {$dateSql}
            GROUP BY durum ORDER BY total DESC");
        $sql->execute($params);
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardMonthlyTrend($monthCount = 12)
    {
        $monthCount = max(1, min(24, (int) $monthCount));
        $startDate = date('Y-m-01', strtotime('-' . ($monthCount - 1) . ' months'));
        $sql = $this->db->prepare("SELECT DATE_FORMAT(kesif_tarihi, '%Y-%m') AS month_key, COUNT(*) AS total
            FROM {$this->table}
            WHERE silinme_tarihi IS NULL AND kesif_tarihi >= ?
            GROUP BY month_key ORDER BY month_key ASC");
        $sql->execute([$startDate]);
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardTopPeople($limit = 8, $startDate = null, $endDate = null)
    {
        [$dateSql, $params] = $this->buildDashboardDateFilter($startDate, $endDate);
        $limit = max(1, min(20, (int) $limit));
        $sql = $this->db->prepare("SELECT TRIM(gidecek_kisi) AS person_name, COUNT(*) AS total
            FROM {$this->table}
            WHERE silinme_tarihi IS NULL {$dateSql}
              AND gidecek_kisi IS NOT NULL AND TRIM(gidecek_kisi) NOT IN ('', '.')
            GROUP BY TRIM(gidecek_kisi) ORDER BY total DESC, person_name ASC LIMIT {$limit}");
        $sql->execute($params);
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardTopCompanies($limit = 8, $startDate = null, $endDate = null)
    {
        [$dateSql, $params] = $this->buildDashboardDateFilter($startDate, $endDate);
        $limit = max(1, min(20, (int) $limit));
        $sql = $this->db->prepare("SELECT TRIM(firma) AS company_name, COUNT(*) AS total
            FROM {$this->table}
            WHERE silinme_tarihi IS NULL {$dateSql}
              AND firma IS NOT NULL AND TRIM(firma) <> ''
            GROUP BY TRIM(firma) ORDER BY total DESC, company_name ASC LIMIT {$limit}");
        $sql->execute($params);
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardRecent($limit = 8, $startDate = null, $endDate = null)
    {
        [$dateSql, $params] = $this->buildDashboardDateFilter($startDate, $endDate);
        $limit = max(1, min(20, (int) $limit));
        $sql = $this->db->prepare("SELECT id, kesif_tarihi, firma, gidecek_kisi, durum, konum
            FROM {$this->table}
            WHERE silinme_tarihi IS NULL {$dateSql}
            ORDER BY kesif_tarihi DESC, id DESC LIMIT {$limit}");
        $sql->execute($params);
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildDashboardDateFilter($startDate, $endDate)
    {
        $clauses = [];
        $params = [];
        if ($startDate) {
            $clauses[] = 'kesif_tarihi >= ?';
            $params[] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $clauses[] = 'kesif_tarihi <= ?';
            $params[] = $endDate . ' 23:59:59';
        }
        return [$clauses ? ' AND ' . implode(' AND ', $clauses) : '', $params];
    }

}
