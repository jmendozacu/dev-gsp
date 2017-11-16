<?php
require 'config.php';

//log
$_toFile = "group-update-".date('Ymd_h_i').".csv";
$fp = fopen(__DATA_PATH__ . $_toFile , "wb");
fputcsv($fp , array("entity_id" , "retail_price" , "date"));

//检测group的价和零售价比例是否正确.如果不正确就重新生成
$_groupId = 7; //1% discount
$_discount = $_priceRule["rule"][$_groupId];

$sql = "SELECT * FROM " .__TABLE_PRICE_GROUP__ . " WHERE 1"
    ." AND entity_id=6874"
    ." AND customer_group_id=" . $_groupId
    . " ORDER BY entity_id DESC";

if ($row = $db->fetchAll($sql)) {
    foreach ($row as $rs) {
        $_entityId = $rs['entity_id'];
        $retailPrice = (float)number_format(getRetailPrice($_entityId) , 2 ,"." , "");
        $groupPrice = (float)number_format($rs['value'] ,2,".","");

        //对比
        if (round($retailPrice * (1 - $_discount),2) != round($groupPrice,2)) {

            //update
            deleteGroupPrice($_entityId);

            if (generateGroupPrice($_entityId)) {
                fputcsv($fp , array($_entityId , $retailPrice , date('Y-m-d H:i')));
                echo $_entityId . " was update Group Price Successfully<br>";
                usleep(5);
                //exit;
            }
        }
    }
}
fclose($fp);