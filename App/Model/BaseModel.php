<?php
namespace App\Model;

use PDO;
use App\Logging\LoggerFactory;
use App\Helper\UploadSecurity;



class BaseModel extends PDO
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $uploadDir = 'files/offer/';

    protected $logger;

    protected $log;
    protected $securityLog;

    public function __construct($table)
    {
        global $ac;
        $this->db = $ac;
        $this->table = $table;

        // log sadece dosya
        $this->log = LoggerFactory::file();

        // güvenlik logu DB'ye (sadece $db varsa)
        if ($this->db instanceof PDO) {
            $this->securityLog = LoggerFactory::security($this->db);
        }
    }

    public function getAll()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function find($id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE $this->primaryKey = ?");
        $sql->execute(array($id));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function delete($id)
    {
        $sql = $this->db->prepare("DELETE FROM $this->table where id = ?");
        $sql->execute([$id]);
        return $sql->rowCount();
    }

    public function save($data)
    {
        if (isset($data['id']) && $data['id'] > 0) {
            return $this->update($data);
        } else {
            return $this->insert($data);
        }
    }

    public function insert($data)
    {
        $keys = array_keys($data);
        $values = array_values($data);
        $sql = $this->db->prepare("INSERT INTO $this->table (" . implode(',', $keys) . ") VALUES (" . str_repeat('?,', count($values) - 1) . "?)");
        $sql->execute($values);
        return $this->db->lastInsertId();
    }

    public function update($data)
    {
        $id = $data['id'];
        unset($data['id']);
        $keys = array_keys($data);
        $values = array_values($data);
        $sql = $this->db->prepare("UPDATE $this->table SET " . implode('=?,', $keys) . "=? WHERE id = ?");
        $values[] = $id; // id'yi WHERE koşuluna eklemek için values dizisine ekleyin
        $sql->execute($values);
    }

    public function uploadFile($file)
    {
        $file = UploadSecurity::validate($file);
        $uploadPath = rtrim($this->uploadDir, '/\\') . DIRECTORY_SEPARATOR . UploadSecurity::randomName($file);
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $uploadPath;
        } else {
            return false;
        }
    }


    public function findFileName($id)
    {
        $sql = $this->db->prepare("SELECT file FROM $this->table where id = ?");
        $sql->execute([$id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }
}
