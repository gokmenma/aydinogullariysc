<?php 
namespace App\Model;

use App\Model\BaseModel;

class CustomerModel extends BaseModel
{
    protected $table = 'customers';

    public function __construct()
    {
       parent::__construct($this->table);
    }

    public function companyNameExists($company, $excludeId = 0)
    {
        $sql = "SELECT 1
                FROM customers
                WHERE deleted_at IS NULL
                  AND LOWER(TRIM(company)) = LOWER(TRIM(?))";
        $params = [trim($company)];

        if ((int) $excludeId > 0) {
            $sql .= " AND id <> ?";
            $params[] = (int) $excludeId;
        }

        $sql .= " LIMIT 1";
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetchColumn();
    }

    public function softDelete($id, $deletedBy)
    {
        $statement = $this->db->prepare(
            "UPDATE customers
             SET deleted_at = ?, deleted_by = ?
             WHERE id = ? AND deleted_at IS NULL"
        );
        $statement->execute([
            date('Y-m-d H:i:s'),
            (int) $deletedBy,
            (int) $id
        ]);

        return $statement->rowCount();
    }

}
