<?php 

namespace App\Model;

use App\Model\BaseModel;
use App\Helper\Helper;
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
     * Evrakı teslim alındı yap
     */
    public function receiveDocument($id)
    {
        $sql = $this->db->prepare("UPDATE {$this->table} SET 
                                    teslimalan = :teslimalan, 
                                    teslimalmatarihi = :teslimalmatarihi
                                   WHERE id = :id");
        $sql->execute([
            'id' => (int)$id,
            'teslimalan' => $_SESSION['uid'] ?? 0,
            'teslimalmatarihi' => date('Y-m-d H:i:s')
        ]);
        return $sql->rowCount();
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