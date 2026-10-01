<?php 


namespace App\Helper;

class Date
{
 
    public static function getMonthSelect($name = 'month', $id = null)
    {
        $months = [
            '' => 'Hepsi',
            '01' => 'Ocak',
            '02' => 'Şubat',
            '03' => 'Mart',
            '04' => 'Nisan',
            '05' => 'Mayıs',
            '06' => 'Haziran',
            '07' => 'Temmuz',
            '08' => 'Ağustos',
            '09' => 'Eylül',
            '10' => 'Ekim',
            '11' => 'Kasım',
            '12' => 'Aralık'
        ];

        $select = '<select name="' . $name . '" id="' . $name . '" class="selectpicker form-control" data-style="border bg-white" data-size="8">';

        foreach ($months as $key => $value) {
            $selected = $key == $id ? 'selected' : null;
            $select .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
        }
        $select .= '</select>';

        return $select;
    }

    public static function getYearSelect($name = 'year', $id = null)
    {
        $select = '<select name="' . $name . '" id="' . $name . '" class="selectpicker form-control" data-style="border bg-white" data-size="8">';
        $select .= '<option value="">Hepsi</option>';
        for ($i = 2020; $i <= date('Y') + 4; $i++) {
            $selected = $i == $id ? 'selected' : null;
            $select .= '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
        }
        $select .= '</select>';

        return $select;
    }

    public static function getThisMonth()
    {
        return date('m');
    }
    
    public static function setMonth($month)
    {
        return str_pad($month, 2, '0', STR_PAD_LEFT);
    }

    public static function getThisYear()
    {
        return date('Y');
    }

    //Gün adlarını Türkçe olarak döndüren fonksiyon
    public static function getDayNames($gun)
    {
       //ingilizce olarak gelen gün adını türkçeye çevir
        $gunler = [
            'Monday' => 'Pazartesi',
            'Tuesday' => 'Salı',
            'Wednesday' => 'Çarşamba',
            'Thursday' => 'Perşembe',
            'Friday' => 'Cuma',
            'Saturday' => 'Cumartesi',
            'Sunday' => 'Pazar'
        ];
        return $gunler[$gun];

    }

    //Tarihi 01.01.2024 şeklinde döndürür
    public static function dmY($date)
    {
        //tarih boş değilse veya 0'dan farklı ise
        if ( $date != null) {
            return date('d.m.Y', strtotime($date));
        }
        return null;
    }

    /** Tarihi 01.01.2024 00:00:00 şeklinde döndürür */
    public static function dmyHis($date)
    {
        //tarih boş değilse veya 0'dan farklı ise
        if ( $date != null) {
            return date('d.m.Y H:i:s', strtotime($date));
        }
        return null;
    }

    /** Tarihi 01.01.2024 00:00:00 şeklinde döndürür */
    public static function YmdHis($date)
    {
        //tarih boş değilse veya 0'dan farklı ise
        if ( $date != null) {
            return date('Y-m-d H:i:s', strtotime($date));
        }
        return null;
    }

   
    /**Ymd formatında döndürür */
    public static function Ymd($date)
    {
        //tarih boş değilse veya 0'dan farklı ise
        if ( $date != null) {
            return date('Y-m-d', strtotime($date));
        }
        return null;
    }

}