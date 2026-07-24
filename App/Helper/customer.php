<?php 


namespace App\Helper;
use PDO;

class customer
{
    //getCustomerSelect
    public static function getCustomerSelect($name="customers",$id = null)
    {
        global $ac;
        $sql = $ac->prepare(
            "SELECT * FROM customers
             WHERE deleted_at IS NULL OR id = ?
             ORDER BY company"
        );
        $sql->execute([(int) $id]);
        $customers = $sql->fetchAll(PDO::FETCH_OBJ);
        $select = "<select name='$name' id='$id' class='form-control selectpicker' data-style='bg-white' data-size='8'
                            data-live-search='true'>";
        $select .= "<option value=''>Müşteri Seçiniz</option>";
        foreach ($customers as $customer) {
            //gelen id ile veritabanındaki id eşleşirse selected yap
            $selected = $customer->id == $id ? "selected" : null;
            $select .= "<option value='$customer->id' $selected>$customer->company</option>";
        }
        $select .= "</select>";
        return $select;
    }

    //getCustomerGroups
    public static function getCustomerGroups($name="grp",$id = null)
    {
        global $ac;
        $sql = $ac->prepare("SELECT * FROM cgroups WHERE statu = 1"); 
        $sql->execute();
        $groups = $sql->fetchAll(PDO::FETCH_OBJ);
        
        $select = "<select name='$name' id='$id' class='form-control selectpicker' data-style='bg-white' data-size='8'
                            data-live-search='true'>";
        $select .= "<option value=''>Grup Seçiniz</option>";
        foreach ($groups as $group) {
            //gelen id ile veritabanındaki id eşleşirse selected yap
            $selected = $group->id == $id ? "selected" : null;
            $select .= "<option value='$group->id' $selected>$group->title</option>";
        }
        $select .= "</select>";
        return $select;
    }

    //Gelen id numarasından firmanın bilgilerini getirir
    public static function getCustomer($id)
    {
        global $ac;
        $sql = $ac->prepare("SELECT * FROM customers WHERE id = ?");
        $sql->execute(array($id));
        return $sql->fetch(PDO::FETCH_OBJ);
    }
}
