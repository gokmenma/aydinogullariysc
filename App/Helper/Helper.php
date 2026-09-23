<?php

namespace App\Helper;
use PDO;

class Helper
{

    const STATE = [
        0 => 'Bekliyor',
        1 => 'Onaylandı',
        2 => 'Tamamlandı',
        3 => 'Reddedildi',
    ];

 

    //Bölgeleri seçebilecek select oluşturur
    public static function selectRegion($name, $selected = null, $className = 'form-control select2')
    {
        global $ac;
        $sql = $ac->prepare("SELECT * FROM units WHERE statu = 5");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_OBJ);

        $select = '<select name="' . $name . '" id="' . $name . '" class="' . $className . '" data-placeholder="Bölge Seçiniz" data-style="border bg-white" data-container="body" required>';
        $select .= '<option value="">Bölge Seçiniz</option>';
        foreach ($result as $unit) {
            $isSelected = ($selected == $unit->id) ? 'selected' : '';
            $select .= '<option value="' . $unit->id . '" ' . $isSelected . '>' . htmlspecialchars($unit->title) . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

    //getRegionName
    public static function getRegionName($id)
    {
        global $ac;
        $sql = $ac->prepare("SELECT title FROM units WHERE id = ?");
        $sql->execute([$id]);
        $result = $sql->fetch(PDO::FETCH_OBJ);
        return $result->title ?? '';
    }

    //Servis, Teklif, Rapor gibi numaraları oluştururken kullanılacak fonksiyon
    public static function generateNumber($column, $prefix)
    {
        global $ac;
        $sql = $ac->prepare("SELECT $column FROM define_numbers LIMIT 1");
        $sql->execute();
        $number = $sql->fetch(PDO::FETCH_OBJ)->$column;
        // Column'u büyük harfe çevir ve prefix ile birleştir
        $number = str_pad($number, 4, '0', STR_PAD_LEFT);
        $number = strtoupper($prefix) . $number;
        return $number;


    }

    // alandaki değeri 1 artırarak güncellemek için
    public static function setDefineNumber($column, $value = null)
    {
        global $ac;
        $sql = $ac->prepare("SELECT $column From define_numbers LIMIT 1");
        $sql->execute();
        $number = $sql->fetch(PDO::FETCH_OBJ)->$column;

        $sql = $ac->prepare("UPDATE define_numbers SET $column = ? ");
        $sql->execute([$value ?? $number + 1]);

    }


    //Teklif numaraları arasından en yüksek numarayı bulur
    public static function getHighestOffereNumber()
    {

        //Teklif numaraları arasından en yüksek numarayı bulur

        global $ac;
        $sql = $ac->prepare("SELECT MAX(CAST(REGEXP_REPLACE(offerNumber, '[^0-9]', '') AS UNSIGNED)) AS max_offer FROM offers");
        $sql->execute();
        $result = $sql->fetch(PDO::FETCH_OBJ);
        return $result->max_offer;
    }

    /** src\scripts\il-bolge.json sayfasında illeri select olarak oluşturur */
    public static function selectCity($name, $selected = null, $className = 'form-control select2')
    {
        // il-bolge.json dosyasını oku
        $json = file_get_contents(__DIR__ . '/../../src/scripts/il-bolge.json');
        $cities = json_decode($json, true);

        $select = '<select name="' . $name . '" id="' . $name . '" class="' . $className . '" data-placeholder="İl Seçiniz" data-style="border bg-white" data-container="body">';
        $select .= '<option value="">İl Seçiniz</option>';
        foreach ($cities as $city) {
            $isSelected = ($selected == $city['il']) ? 'selected' : '';
            $select .= '<option value="' . $city['il'] . '" data-subtext="' . $city['bolge'] . '" ' . $isSelected . '>' . $city['il'] . '</option>';
        }
        $select .= '</select>';
        return $select;
    }


    //Durum ile ilgili select oluştur, Bekliyor, Onaylandı, Reddedildi
    public static function selectState($name, $selected = null)
    {
        $select = '<select name="' . $name . '" class="selectpicker form-control" data-style="border bg-white" data-container="body">';
        foreach (self::STATE as $key => $value) {
            $isSelected = ($selected == $key) ? 'selected' : '';
            $select .= '<option value="' . $key . '" ' . $isSelected . '>' . $value . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

    //Durum bilgisini döndürür
    public static function getState($key)
    {
        return self::STATE[$key];
    }


    /**dumd die metodu */
    public static function dd($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }

    //Durum bilgisini döndürür
    // <span class='badge badge-success'>Tamamlandı</span>
    public static function getStateBadge($key)
    {
        $state = self::getState($key);
        $badge = '';
        switch ($key) {
            case 0:
                $badge = '<span class="badge badge-warning">' . $state . '</span>';
                break;
            case 1:
                $badge = '<span class="badge badge-primary">' . $state . '</span>';
                break;
            case 2:
                $badge = '<span class="badge badge-success">' . $state . '</span>';
                break;
            case 3:
                $badge = '<span class="badge badge-danger">' . $state . '</span>';
                break;
        }
        return $badge;
    }



    // Bootstrap alert mesajı oluşturur
    public static function alert($type = 'success', $message = '', $strong = 'Başarılı!')
    {
        return '<div class="mb-3 alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
            . '<strong>' . $strong . '</strong> ' . $message
            . '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>';
    }
}
