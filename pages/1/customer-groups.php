<?php

if ($_POST) {
    if (isset($_POST['Addcategory'])) {
        $categoryName = $_POST['Addcategory'];

        $today = date("Y-m-d"); // Bugünün tarihini al

        $insq = $ac->prepare("INSERT INTO cgroups SET title = ? ,regdate =? , statu = ?");
        $insq->execute(array($categoryName, $today, 1));
        getTableColumns("cgroups");
        //     $lastInsertId = $ac->lastInsertId();
        // echo $lastInsertId;

    }
}
