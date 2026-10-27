<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\BaseModel;
use PDO;

class FileModel extends BaseModel
{
    protected $table = 'upfiles';
    protected $categoryTable = 'upfile_categories';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Dosya Yönetimi KPI Özet İstatistikleri
     */
    public function getSummaryStats(): array
    {
        // Dosya tablosu istatistikleri
        $sql = "
            SELECT 
                COUNT(*) AS total_files,
                COALESCE(SUM(CAST(size AS UNSIGNED)), 0) AS total_size_bytes,
                COALESCE(SUM(CASE WHEN cid > 0 THEN 1 ELSE 0 END), 0) AS categorized_count,
                COALESCE(SUM(CASE WHEN cid IS NULL OR cid = 0 THEN 1 ELSE 0 END), 0) AS uncategorized_count,
                COALESCE(SUM(CASE WHEN regdate >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS recent_30d_count,
                COALESCE(SUM(CASE WHEN DATE(regdate) = CURRENT_DATE() THEN 1 ELSE 0 END), 0) AS today_count
            FROM {$this->table}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $fileStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Kategori sayısı
        $catStmt = $this->db->query("SELECT COUNT(*) FROM {$this->categoryTable}");
        $totalCategories = (int) ($catStmt ? $catStmt->fetchColumn() : 0);

        return [
            'total_files' => (int) ($fileStats['total_files'] ?? 0),
            'total_size_bytes' => (int) ($fileStats['total_size_bytes'] ?? 0),
            'total_categories' => $totalCategories,
            'categorized_count' => (int) ($fileStats['categorized_count'] ?? 0),
            'uncategorized_count' => (int) ($fileStats['uncategorized_count'] ?? 0),
            'recent_30d_count' => (int) ($fileStats['recent_30d_count'] ?? 0),
            'today_count' => (int) ($fileStats['today_count'] ?? 0),
        ];
    }

    /**
     * Filtrelenmiş dosya listesini getirir
     */
    public function getFiles(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;

        $params = [];
        $where = $this->buildWhereClause($filters, $params);

        $sql = "
            SELECT 
                f.id,
                f.cid,
                f.filename,
                f.size,
                f.creativer,
                f.regdate,
                c.title AS category_title,
                COALESCE(u.username, CONCAT('Kullanıcı #', f.creativer)) AS uploader_name,
                u.Unvan AS uploader_title,
                u.email AS uploader_email
            FROM {$this->table} f
            LEFT JOIN {$this->categoryTable} c ON c.id = f.cid
            LEFT JOIN users u ON u.id = f.creativer
            {$where}
            ORDER BY f.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Filtreye uyan toplam dosya sayısı
     */
    public function getFilesCount(array $filters = []): int
    {
        $params = [];
        $where = $this->buildWhereClause($filters, $params);

        $sql = "
            SELECT COUNT(*) 
            FROM {$this->table} f
            LEFT JOIN {$this->categoryTable} c ON c.id = f.cid
            LEFT JOIN users u ON u.id = f.creativer
            {$where}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Tek dosya kaydı getirir
     */
    public function getFileById(int $id): ?array
    {
        $sql = "
            SELECT 
                f.id,
                f.cid,
                f.filename,
                f.size,
                f.creativer,
                f.regdate,
                c.title AS category_title,
                COALESCE(u.username, CONCAT('Kullanıcı #', f.creativer)) AS uploader_name,
                u.Unvan AS uploader_title,
                u.email AS uploader_email
            FROM {$this->table} f
            LEFT JOIN {$this->categoryTable} c ON c.id = f.cid
            LEFT JOIN users u ON u.id = f.creativer
            WHERE f.id = ?
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Yeni dosya kaydı ekler
     */
    public function createFile(int $cid, string $filename, int $size, int $userId): int
    {
        $sql = "
            INSERT INTO {$this->table} (cid, filename, size, creativer, regdate)
            VALUES (?, ?, ?, ?, NOW())
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cid, $filename, $size, $userId]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Dosya kategorisini veya bilgilerini günceller
     */
    public function updateFile(int $id, array $data): bool
    {
        $allowed = ['cid', 'filename'];
        $fields = [];
        $params = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $params[] = $data[$col];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Dosya kaydını siler
     */
    public function deleteFile(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Tüm kategorileri ve bağlı dosya sayılarını getirir
     */
    public function getAllCategories(): array
    {
        $sql = "
            SELECT 
                c.id,
                c.title,
                c.regdate,
                COUNT(f.id) AS file_count,
                COALESCE(SUM(CAST(f.size AS UNSIGNED)), 0) AS total_size
            FROM {$this->categoryTable} c
            LEFT JOIN {$this->table} f ON f.cid = c.id
            GROUP BY c.id, c.title, c.regdate
            ORDER BY c.title ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * ID ile kategori getirir
     */
    public function getCategoryById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->categoryTable} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * İsim kontrolü (mükerrer kategori önleme)
     */
    public function categoryTitleExists(string $title, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM {$this->categoryTable} WHERE LOWER(TRIM(title)) = LOWER(TRIM(?))";
        $params = [$title];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Yeni kategori ekler
     */
    public function createCategory(string $title): int
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->categoryTable} (title, regdate) VALUES (?, NOW())");
        $stmt->execute([trim($title)]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Kategori günceller
     */
    public function updateCategory(int $id, string $title): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->categoryTable} SET title = ? WHERE id = ?");
        return $stmt->execute([trim($title), $id]);
    }

    /**
     * Kategori siler (ve bağlı dosyaları kategorisiz 0 yapar)
     */
    public function deleteCategory(int $id): bool
    {
        // Önce dosyaların kategorisini 0 yap
        $unassignStmt = $this->db->prepare("UPDATE {$this->table} SET cid = 0 WHERE cid = ?");
        $unassignStmt->execute([$id]);

        $stmt = $this->db->prepare("DELETE FROM {$this->categoryTable} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * SQL WHERE şartlarını dinamik inşa eder
     */
    private function buildWhereClause(array $filters, array &$params): string
    {
        $where = [];

        // Arama sorgusu
        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $where[] = "(f.filename LIKE ? OR c.title LIKE ? OR u.username LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Kategori filtresi
        if (isset($filters['cid']) && $filters['cid'] !== '' && $filters['cid'] !== 'all') {
            $cid = (int) $filters['cid'];
            if ($cid === 0) {
                $where[] = "(f.cid = 0 OR f.cid IS NULL)";
            } else {
                $where[] = "f.cid = ?";
                $params[] = $cid;
            }
        }

        // Tab/Tür filtresi: pdf, image, doc, excel, archive, other
        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $type = strtolower(trim((string) $filters['type']));
            switch ($type) {
                case 'pdf':
                    $where[] = "LOWER(f.filename) LIKE '%.pdf'";
                    break;
                case 'image':
                    $where[] = "(LOWER(f.filename) LIKE '%.jpg' OR LOWER(f.filename) LIKE '%.jpeg' OR LOWER(f.filename) LIKE '%.png' OR LOWER(f.filename) LIKE '%.webp' OR LOWER(f.filename) LIKE '%.gif' OR LOWER(f.filename) LIKE '%.svg')";
                    break;
                case 'excel':
                    $where[] = "(LOWER(f.filename) LIKE '%.xlsx' OR LOWER(f.filename) LIKE '%.xls' OR LOWER(f.filename) LIKE '%.csv')";
                    break;
                case 'word':
                case 'doc':
                    $where[] = "(LOWER(f.filename) LIKE '%.docx' OR LOWER(f.filename) LIKE '%.doc')";
                    break;
                case 'archive':
                    $where[] = "(LOWER(f.filename) LIKE '%.zip' OR LOWER(f.filename) LIKE '%.rar' OR LOWER(f.filename) LIKE '%.7z' OR LOWER(f.filename) LIKE '%.tar' OR LOWER(f.filename) LIKE '%.gz')";
                    break;
                case 'recent':
                    $where[] = "f.regdate >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }

        return !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    }
}
