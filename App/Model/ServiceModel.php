<?php
namespace App\Model;

use PDO;
use PDOException;
use App\Helper\Date;
use App\Model\BaseModel;

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
        $sql =  $this->db->prepare("SELECT 
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
                                            FROM $this->table p
                                            LEFT JOIN customers c ON c.id = p.pcid
                                            LEFT JOIN units u ON u.id = p.servicestype
                                            LEFT JOIN users us ON us.id = p.pcreativer");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }




    //Güne göre servis listesi
    public function getDailyServiceList($date)
    {
        //Date alanındaki - işaretini kaldır
        try {
            $sql =  $this->db->prepare("SELECT 
                                                        p.id,
                                                     c.company as firma_adi,
                                                      service_number,
                                                      pstart_date,
                                                        psecond_date,
                                                      u.title,pauthors,
                                                      pstatu
                                                      FROM $this->table p
                                                      LEFT JOIN customers c ON c.id =  p.pcid
                                                      LEFT JOIN units u ON u.id = p.servicestype
                                                      WHERE STR_TO_DATE(REPLACE(pstart_date, '.', '-'), '%d-%m-%Y') = ? OR STR_TO_DATE(REPLACE(psecond_date, '.', '-'), '%d-%m-%Y') = ?");
            $sql->execute([$date, $date]);
            return $sql->fetchAll(PDO::FETCH_OBJ);
            // return $date;
        } catch (PDOException $e) {
            error_log("Veritabanı hatası getDailyServiceList: " . $e->getMessage());
            return [];
        }
    }

    public function getServiceBackColour($id){
        $sql =  $this->db->prepare("SELECT colour FROM units WHERE id = ?");
        $sql->execute([$id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    //Gelen id değerine göre servis sayısını getir
    public function getServiceCount($id)
    {
        $sql =  $this->db->prepare("SELECT COUNT(*) as count FROM $this->table WHERE pstatu = ?");
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
}