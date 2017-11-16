<?php
require 'config.php';

//把short description 更新成和产品名称一样
$sql = "SELECT * FROM " . __TABLE_PRODUCT__ . " WHERE 1"
    //. " AND entity_id=5550"
    . " ORDER BY entity_id DESC";

if ($row = $db->fetchAll($sql)) {
    foreach ($row as $rs) {
        $_id = $rs['entity_id'];
        $_title = getProductName($_id);

/*        $_data = array(
            "value" => str_replace('"' , "" , $_title)
        );*/
        $_data = str_replace('"' , "" , $_title);
        //print_r($_data);exit;

        if (updateShortDesc($_id , $_data)) {
            echo $_id . " was update short desc.succeed.</br>";
            usleep(5);
        }
    }
}