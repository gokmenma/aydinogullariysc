<?php
namespace App\Model;

use App\Model\BaseModel;
use PDO;

class VersionNoteModel extends BaseModel
{
    protected $table = 'version_notes';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Sürüm notlarını tarih, kategori, arama ve sayfalama kriterlerine göre getirir.
     */
    public function getNotes($limit = null, $offset = 0, $category = null, $search = null, $startDate = null, $endDate = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if (!empty($startDate)) {
            $sql .= " AND created_at >= ?";
            $params[] = $startDate . ' 00:00:00';
        }

        if (!empty($endDate)) {
            $sql .= " AND created_at <= ?";
            $params[] = $endDate . ' 23:59:59';
        }

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR version_tag LIKE ? OR author LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY created_at DESC, id DESC";

        if ($limit !== null && is_numeric($limit)) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null && is_numeric($offset) && $offset > 0) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Filtrelere göre toplam kayıt sayısını döner.
     */
    public function countNotes($category = null, $search = null, $startDate = null, $endDate = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if (!empty($startDate)) {
            $sql .= " AND created_at >= ?";
            $params[] = $startDate . ' 00:00:00';
        }

        if (!empty($endDate)) {
            $sql .= " AND created_at <= ?";
            $params[] = $endDate . ' 23:59:59';
        }

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR version_tag LIKE ? OR author LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Sürüm notları istatistiklerini getirir.
     */
    public function getStats()
    {
        $stats = [
            'total' => 0,
            'feature' => 0,
            'improvement' => 0,
            'bugfix' => 0,
            'security' => 0,
            'other' => 0,
            'last_date' => null
        ];

        $stmt = $this->db->query("SELECT category, COUNT(*) as count FROM {$this->table} GROUP BY category");
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach ($results as $row) {
            $cat = $row->category ?? 'feature';
            if (isset($stats[$cat])) {
                $stats[$cat] = (int)$row->count;
            }
            $stats['total'] += (int)$row->count;
        }

        $lastStmt = $this->db->query("SELECT created_at FROM {$this->table} ORDER BY created_at DESC, id DESC LIMIT 1");
        $lastRow = $lastStmt->fetch(PDO::FETCH_OBJ);
        if ($lastRow) {
            $stats['last_date'] = $lastRow->created_at;
        }

        return $stats;
    }

    /**
     * Yeni sürüm notu ekler.
     */
    public function addVersionNote($data)
    {
        $insertData = [
            'title' => trim($data['title'] ?? ''),
            'version_tag' => !empty($data['version_tag']) ? trim($data['version_tag']) : null,
            'category' => !empty($data['category']) ? trim($data['category']) : 'feature',
            'description' => trim($data['description'] ?? ''),
            'author' => !empty($data['author']) ? trim($data['author']) : ($_SESSION['username'] ?? 'Admin'),
            'created_at' => !empty($data['created_at']) ? trim($data['created_at']) : date('Y-m-d H:i:s')
        ];

        return $this->insert($insertData);
    }
}
