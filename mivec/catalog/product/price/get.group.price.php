<?php
require 'config.php';

//set export file & data
$file = __DATA_PATH__ . "/group.price.csv";
$fp = fopen($file , "wb");
$header = "id,sku,retail price,group_1,group_2,group_3,group_4";
fputcsv($fp , strToArray($header));

$sql = "SELECT * FROM " . __TABLE_PRODUCT__
    . " WHERE 1"
    . " ORDER BY " .__PRIMARY_PRODUCT_ID__. " DESC";
    //. " LIMIT 10";

if ($row = $db->fetchAll($sql)) {
    $data = array();
    foreach ($row as $rs) {
        $_id = $rs['entity_id'];
        if (hasGroupPrice($_id)) {
            //get group price
            $_groupPrice = getGroupPrice($_id);

            $data = array($_id , $rs['sku'] , getRetailPrice($_id));
            foreach ($_groupPrice as $_gPrice) {
                array_push($data , $_gPrice);
            }

            if (fputcsv($fp , $data)) {
                echo $rs['sku'] . " export success</p>";
            }
        }
    }
}

function getGroupPrice($_productId , $_groupId = "")
{
    global $db;
    $sql = "SELECT * FROM " . __TABLE_PRICE_GROUP__
        ." WHERE " . __PRIMARY_PRODUCT_ID__ . "=" . $_productId;

    if (!empty($_groupId)) {
        $sql .= " AND customer_group_id=" . $_groupId;
    }

    $_return = array();
    if ($row = $db->fetchAll($sql)) {
        foreach ($row as $rs) {
            array_push($_return , $rs['value']);
        }
        return $_return;
    }
}

function hasGroupPrice($_productId)
{
    global $db;
    $sql = "SELECT COUNT(*) FROM " . __TABLE_PRICE_GROUP__
        ." WHERE " . __PRIMARY_PRODUCT_ID__ . "=" . $_productId;
    return $db->fetchOne($sql);
}

fclose($fp);