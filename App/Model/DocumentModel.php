<?php 

namespace App\Model;

use App\Model\BaseModel;
use App\Helper\Helper;
use App\Helper\Security;
use PDO;

class DocumentModel extends BaseModel
{
    protected $table = 'evraktakip';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Gelen evraklar için KPI özet istatistikleri
     */
    public function getInDocumentsSummaryStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN estatu = 'Bekliyor' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN estatu = 'Çalışıyor' THEN 1 ELSE 0 END) as processing_count,
                    SUM(CASE WHEN estatu = 'Tamamlandı' THEN 1 ELSE 0 END) as completed_count
                FROM {$this->table}
                WHERE evrakturu = 'Gelen'";
        $stmt = $this->db->query($sql);
        return $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
    }

    /**
     * Giden evraklar için KPI özet istatistikleri
     */
    public function getOutDocumentsSummaryStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN teslimalmatarihi IS NULL OR teslimalmatarihi = '' OR teslimalmatarihi = '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as waiting_receive_count,
                    SUM(CASE WHEN teslimalmatarihi IS NOT NULL AND teslimalmatarihi != '' AND teslimalmatarihi != '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as received_count,
                    SUM(CASE WHEN estatu = 'Tamamlandı' THEN 1 ELSE 0 END) as completed_count
                FROM {$this->table}
                WHERE evrakturu = 'Giden'";
        $stmt = $this->db->query($sql);
        return $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
    }

    /**
     * Tüm evraklar için genel KPI özet istatistikleri
     */
    public function getOverallSummaryStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN evrakturu = 'Gelen' OR evrakturu = '1' THEN 1 ELSE 0 END) as incoming_count,
                    SUM(CASE WHEN evrakturu = 'Giden' OR evrakturu = '2' THEN 1 ELSE 0 END) as outgoing_count,
                    SUM(CASE WHEN estatu = 'Bekliyor' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN estatu = 'Çalışıyor' THEN 1 ELSE 0 END) as processing_count,
                    SUM(CASE WHEN estatu = 'Tamamlandı' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN teslimalmatarihi IS NULL OR teslimalmatarihi = '' OR teslimalmatarihi = '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as waiting_receive_count,
                    SUM(CASE WHEN teslimalmatarihi IS NOT NULL AND teslimalmatarihi != '' AND teslimalmatarihi != '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as received_count
                FROM {$this->table}";
        $stmt = $this->db->query($sql);
        $res = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        $total = (int)($res['total_count'] ?? 0);
        $completed = (int)($res['completed_count'] ?? 0);
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        return [
            'total_count' => $total,
            'incoming_count' => (int)($res['incoming_count'] ?? 0),
            'outgoing_count' => (int)($res['outgoing_count'] ?? 0),
            'pending_count' => (int)($res['pending_count'] ?? 0),
            'processing_count' => (int)($res['processing_count'] ?? 0),
            'completed_count' => $completed,
            'waiting_receive_count' => (int)($res['waiting_receive_count'] ?? 0),
            'received_count' => (int)($res['received_count'] ?? 0),
            'completion_rate' => $completionRate,
        ];
    }

    /**
     * Sayfalanmış ve filtrelenmiş evrak listesi
     */
    public function getPaginatedList(array $filters = [], int $page = 1, int $limit = 25): array
    {
        $page = max(1, $page);
        $limit = min(100, max(5, $limit));
        $offset = ($page - 1) * $limit;

        $conditions = ["1=1"];
        $params = [];

        // Tür filtresi ('Gelen', 'Giden')
        $type = trim((string)($filters['type'] ?? 'all'));
        if ($type === 'Gelen' || $type === 'gelen') {
            $conditions[] = "(e.evrakturu = 'Gelen' OR e.evrakturu = '1')";
        } elseif ($type === 'Giden' || $type === 'giden') {
            $conditions[] = "(e.evrakturu = 'Giden' OR e.evrakturu = '2')";
        }

        // Statü filtresi ('Bekliyor', 'Çalışıyor', 'Tamamlandı')
        $status = trim((string)($filters['status'] ?? 'all'));
        if ($status !== '' && $status !== 'all') {
            $conditions[] = "e.estatu = :status";
            $params[':status'] = $status;
        }

        // Teslim durumu filtresi ('waiting', 'received')
        $receive = trim((string)($filters['receive'] ?? 'all'));
        if ($receive === 'waiting') {
            $conditions[] = "(e.teslimalmatarihi IS NULL OR e.teslimalmatarihi = '' OR e.teslimalmatarihi = '0000-00-00 00:00:00')";
        } elseif ($receive === 'received') {
            $conditions[] = "(e.teslimalmatarihi IS NOT NULL AND e.teslimalmatarihi != '' AND e.teslimalmatarihi != '0000-00-00 00:00:00')";
        }

        // Müşteri ID filtresi
        if (!empty($filters['customer_id'])) {
            $conditions[] = "e.firma = :customer_id";
            $params[':customer_id'] = (string)$filters['customer_id'];
        }

        // Arama sorgusu
        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $conditions[] = "(
                e.id LIKE :search_id OR
                c.company LIKE :search_company OR
                e.firma LIKE :search_firma OR
                e.kategori LIKE :search_kat OR
                e.adet LIKE :search_adet OR
                u_teslimeden.username LIKE :search_te OR
                u_teslimalan.username LIKE :search_ta OR
                e.aciklama LIKE :search_aciklama
            )";
            $term = "%{$search}%";
            $params[':search_id'] = $term;
            $params[':search_company'] = $term;
            $params[':search_firma'] = $term;
            $params[':search_kat'] = $term;
            $params[':search_adet'] = $term;
            $params[':search_te'] = $term;
            $params[':search_ta'] = $term;
            $params[':search_aciklama'] = $term;
        }

        $whereSql = implode(' AND ', $conditions);

        // Toplam kayıt sayısı
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} e 
                     LEFT JOIN customers c ON c.id = e.firma 
                     LEFT JOIN users u_teslimeden ON u_teslimeden.id = e.teslimeden 
                     LEFT JOIN users u_teslimalan ON u_teslimalan.id = e.teslimalan 
                     WHERE {$whereSql}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Liste sorgusu
        $listSql = "SELECT 
                        e.id,
                        e.firma,
                        e.evrakturu,
                        e.kategori,
                        e.adet,
                        e.teslimeden,
                        e.teslimalan,
                        e.teslimtarihi,
                        e.teslimalmatarihi,
                        e.estatu,
                        e.aciklama,
                        c.company as customer_company,
                        c.yetkili as customer_authorized,
                        u_teslimeden.username as teslimeden_username,
                        u_teslimeden.Unvan as teslimeden_unvan,
                        u_teslimalan.username as teslimalan_username,
                        u_teslimalan.Unvan as teslimalan_unvan
                    FROM {$this->table} e
                    LEFT JOIN customers c ON c.id = e.firma
                    LEFT JOIN users u_teslimeden ON u_teslimeden.id = e.teslimeden
                    LEFT JOIN users u_teslimalan ON u_teslimalan.id = e.teslimalan
                    WHERE {$whereSql}
                    ORDER BY e.id DESC
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($listSql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $items = [];

        foreach ($rows as $row) {
            $rawId = (int)$row['id'];
            $encId = Security::encrypt((string)$rawId);

            $firmaAdi = trim((string)($row['customer_company'] ?? ''));
            if ($firmaAdi === '') {
                $firmaAdi = trim((string)$row['firma']);
            }

            $typeLabel = $row['evrakturu'];
            if ($typeLabel === '1') $typeLabel = 'Gelen';
            if ($typeLabel === '2') $typeLabel = 'Giden';
            if ($typeLabel === '') $typeLabel = 'Gelen';

            $isReceived = !empty($row['teslimalmatarihi']) && $row['teslimalmatarihi'] !== '0000-00-00 00:00:00';

            $items[] = [
                'id' => $encId,
                'raw_id' => $rawId,
                'firma_id' => $row['firma'],
                'firma_adi' => $firmaAdi,
                'firma_yetkili' => $row['customer_authorized'] ?? '',
                'evrakturu' => $typeLabel,
                'is_gelen' => ($typeLabel === 'Gelen'),
                'kategori' => (string)($row['kategori'] ?? ''),
                'adet' => (string)($row['adet'] ?? '1'),
                'teslimeden_id' => $row['teslimeden'],
                'teslimeden_adi' => $row['teslimeden_username'] ?? (string)$row['teslimeden'],
                'teslimalan_id' => $row['teslimalan'],
                'teslimalan_adi' => $row['teslimalan_username'] ?? (string)$row['teslimalan'],
                'teslimtarihi' => (string)($row['teslimtarihi'] ?? ''),
                'teslimalmatarihi' => $isReceived ? (string)$row['teslimalmatarihi'] : null,
                'is_received' => $isReceived,
                'estatu' => (string)($row['estatu'] ?: 'Bekliyor'),
                'aciklama' => (string)($row['aciklama'] ?? ''),
            ];
        }

        $totalPages = $limit > 0 ? (int)ceil($totalRecords / $limit) : 1;

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalRecords,
                'total_pages' => $totalPages,
                'has_more' => ($page < $totalPages),
            ],
        ];
    }

    /**
     * Tekil evrak detayını getirir
     */
    public function getDocumentById(int $id): ?array
    {
        $sql = "SELECT 
                    e.id,
                    e.firma,
                    e.evrakturu,
                    e.kategori,
                    e.adet,
                    e.teslimeden,
                    e.teslimalan,
                    e.teslimtarihi,
                    e.teslimalmatarihi,
                    e.estatu,
                    e.aciklama,
                    c.company as customer_company,
                    c.yetkili as customer_authorized,
                    c.gsm as customer_gsm,
                    c.email as customer_email,
                    u_teslimeden.username as teslimeden_username,
                    u_teslimeden.Unvan as teslimeden_unvan,
                    u_teslimalan.username as teslimalan_username,
                    u_teslimalan.Unvan as teslimalan_unvan
                FROM {$this->table} e
                LEFT JOIN customers c ON c.id = e.firma
                LEFT JOIN users u_teslimeden ON u_teslimeden.id = e.teslimeden
                LEFT JOIN users u_teslimalan ON u_teslimalan.id = e.teslimalan
                WHERE e.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $rawId = (int)$row['id'];
        $firmaAdi = trim((string)($row['customer_company'] ?? ''));
        if ($firmaAdi === '') {
            $firmaAdi = trim((string)$row['firma']);
        }

        $typeLabel = $row['evrakturu'];
        if ($typeLabel === '1') $typeLabel = 'Gelen';
        if ($typeLabel === '2') $typeLabel = 'Giden';
        if ($typeLabel === '') $typeLabel = 'Gelen';

        $isReceived = !empty($row['teslimalmatarihi']) && $row['teslimalmatarihi'] !== '0000-00-00 00:00:00';

        return [
            'id' => Security::encrypt((string)$rawId),
            'raw_id' => $rawId,
            'firma_id' => $row['firma'],
            'firma_adi' => $firmaAdi,
            'firma_yetkili' => $row['customer_authorized'] ?? '',
            'firma_gsm' => $row['customer_gsm'] ?? '',
            'firma_email' => $row['customer_email'] ?? '',
            'evrakturu' => $typeLabel,
            'is_gelen' => ($typeLabel === 'Gelen'),
            'kategori' => (string)($row['kategori'] ?? ''),
            'adet' => (string)($row['adet'] ?? '1'),
            'teslimeden_id' => $row['teslimeden'],
            'teslimeden_adi' => $row['teslimeden_username'] ?? (string)$row['teslimeden'],
            'teslimalan_id' => $row['teslimalan'],
            'teslimalan_adi' => $row['teslimalan_username'] ?? (string)$row['teslimalan'],
            'teslimtarihi' => (string)($row['teslimtarihi'] ?? ''),
            'teslimalmatarihi' => $isReceived ? (string)$row['teslimalmatarihi'] : null,
            'is_received' => $isReceived,
            'estatu' => (string)($row['estatu'] ?: 'Bekliyor'),
            'aciklama' => (string)($row['aciklama'] ?? ''),
        ];
    }

    /**
     * Yeni evrak kaydı oluşturur
     */
    public function createDocument(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (firma, evrakturu, kategori, adet, teslimalan, teslimeden, teslimtarihi, estatu, aciklama) 
                VALUES 
                (:firma, :evrakturu, :kategori, :adet, :teslimalan, :teslimeden, :teslimtarihi, :estatu, :aciklama)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':firma' => (string)($data['firma'] ?? ''),
            ':evrakturu' => (string)($data['evrakturu'] ?? 'Gelen'),
            ':kategori' => (string)($data['kategori'] ?? ''),
            ':adet' => (string)($data['adet'] ?? '1'),
            ':teslimalan' => (string)($data['teslimalan'] ?? ''),
            ':teslimeden' => (string)($data['teslimeden'] ?? ''),
            ':teslimtarihi' => (string)($data['teslimtarihi'] ?: date('d-m-Y')),
            ':estatu' => (string)($data['estatu'] ?: 'Bekliyor'),
            ':aciklama' => (string)($data['aciklama'] ?? ''),
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Evrak kaydını günceller
     */
    public function updateDocument(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        if (array_key_exists('firma', $data)) {
            $fields[] = "firma = :firma";
            $params[':firma'] = (string)$data['firma'];
        }
        if (array_key_exists('evrakturu', $data)) {
            $fields[] = "evrakturu = :evrakturu";
            $params[':evrakturu'] = (string)$data['evrakturu'];
        }
        if (array_key_exists('kategori', $data)) {
            $fields[] = "kategori = :kategori";
            $params[':kategori'] = (string)$data['kategori'];
        }
        if (array_key_exists('adet', $data)) {
            $fields[] = "adet = :adet";
            $params[':adet'] = (string)$data['adet'];
        }
        if (array_key_exists('teslimeden', $data)) {
            $fields[] = "teslimeden = :teslimeden";
            $params[':teslimeden'] = (string)$data['teslimeden'];
        }
        if (array_key_exists('teslimalan', $data)) {
            $fields[] = "teslimalan = :teslimalan";
            $params[':teslimalan'] = (string)$data['teslimalan'];
        }
        if (array_key_exists('teslimtarihi', $data)) {
            $fields[] = "teslimtarihi = :teslimtarihi";
            $params[':teslimtarihi'] = (string)$data['teslimtarihi'];
        }
        if (array_key_exists('estatu', $data)) {
            $fields[] = "estatu = :estatu";
            $params[':estatu'] = (string)$data['estatu'];
        }
        if (array_key_exists('aciklama', $data)) {
            $fields[] = "aciklama = :aciklama";
            $params[':aciklama'] = (string)$data['aciklama'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Evrakı siler
     */
    public function deleteDocument(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Evrakı teslim alındı yap
     */
    public function receiveDocument($id, ?int $userId = null)
    {
        $uid = $userId ?: (int)($_SESSION['uid'] ?? ($_SESSION['user_id'] ?? ($_SESSION['id'] ?? 0)));
        $sql = $this->db->prepare("UPDATE {$this->table} SET 
                                    teslimalan = :teslimalan, 
                                    teslimalmatarihi = :teslimalmatarihi
                                   WHERE id = :id");
        $sql->execute([
            ':id' => (int)$id,
            ':teslimalan' => (string)$uid,
            ':teslimalmatarihi' => date('Y-m-d H:i:s')
        ]);
        return $sql->rowCount();
    }

    /**
     * Evrak statüsünü günceller
     */
    public function updateStatus(int $id, string $status): bool
    {
        $validStatuses = ['Bekliyor', 'Çalışıyor', 'Tamamlandı'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'Bekliyor';
        }
        $stmt = $this->db->prepare("UPDATE {$this->table} SET estatu = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * Form ve filtreler için gerekli tanımlı listeleri getirir
     */
    public function getFormData(): array
    {
        // 1. Müşteriler / Firmalar
        $customersStmt = $this->db->query("SELECT id, company, yetkili FROM customers WHERE deleted_at IS NULL ORDER BY company ASC");
        $customers = [];
        if ($customersStmt) {
            while ($c = $customersStmt->fetch(PDO::FETCH_ASSOC)) {
                $customers[] = [
                    'id' => (int)$c['id'],
                    'company' => (string)$c['company'],
                    'yetkili' => (string)($c['yetkili'] ?? ''),
                ];
            }
        }

        // 2. Kullanıcılar / Personeller
        $usersStmt = $this->db->query("SELECT id, username, Unvan FROM users WHERE statu = 1 ORDER BY username ASC");
        $users = [];
        if ($usersStmt) {
            while ($u = $usersStmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = [
                    'id' => (int)$u['id'],
                    'name' => (string)$u['username'],
                    'unvan' => (string)($u['Unvan'] ?? ''),
                ];
            }
        }

        // 3. Kategoriler
        $categories = $this->getDistinctCategories();

        return [
            'customers' => $customers,
            'users' => $users,
            'categories' => $categories,
            'statuses' => ['Bekliyor', 'Çalışıyor', 'Tamamlandı'],
            'types' => ['Gelen', 'Giden'],
        ];
    }

    /**
     * Önceden kaydedilmiş ve tanımlı benzersiz evrak kategorilerini getirir (Otomatik tamamlama için)
     */
    public function getDistinctCategories(?string $type = null): array
    {
        $categories = [];

        // 1. evraktakip tablosundaki mevcut kategoriler
        $sql = "SELECT DISTINCT TRIM(kategori) as cat FROM {$this->table} WHERE kategori IS NOT NULL AND TRIM(kategori) != ''";
        if ($type) {
            $sql .= " AND evrakturu = " . $this->db->quote($type);
        }
        $stmt = $this->db->query($sql);
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cat = trim($row['cat'] ?? '');
                if ($cat !== '') {
                    $key = mb_strtolower($cat, 'UTF-8');
                    if (!isset($categories[$key])) {
                        $categories[$key] = $cat;
                    }
                }
            }
        }

        // 2. indocument_categories tablosundaki ön tanımlı kategoriler
        try {
            $sql2 = "SELECT DISTINCT TRIM(title) as cat FROM indocument_categories WHERE title IS NOT NULL AND TRIM(title) != ''";
            if ($type) {
                $dstatu = ($type === 'Gelen') ? 1 : 2;
                $sql2 .= " AND dstatu = " . (int)$dstatu;
            }
            $stmt2 = $this->db->query($sql2);
            if ($stmt2) {
                while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                    $cat = trim($row['cat'] ?? '');
                    if ($cat !== '') {
                        $key = mb_strtolower($cat, 'UTF-8');
                        if (!isset($categories[$key])) {
                            $categories[$key] = $cat;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // sessizce devam et
        }

        $result = array_values($categories);
        natcasesort($result);
        return array_values($result);
    }
}
