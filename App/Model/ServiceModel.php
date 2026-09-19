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



 
   
}