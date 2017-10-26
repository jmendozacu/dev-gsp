<?php
require 'config.php';

//log
$_toFile = "group-".date('Ymd_h').".log";
$fp = fopen(__DATA_PATH__ . $_toFile , "wb");

//insert data automatically rule of group price for all products
$sql = "SELECT * FROM " . __TABLE_PRODUCT__
    . " WHERE 1"
    . " ORDER BY " .__PRIMARY_PRODUCT_ID__. " DESC";
    //. " ";

if ($row = $db->fetchAll($sql)) {
    //truncate group price
    truncateGroupPrice();

    foreach ($row as $rs) {
        $_id = $rs["entity_id"];

        //delete special price
        //deleteSpeicalPrice($_id);

        //group price
        if (generateGroupPrice($_id)) {
            echo $_id . " was insert into Group Price Successfully<br>";

            usleep(10);

            //exit;
        }
    }
}
fclose($fp);