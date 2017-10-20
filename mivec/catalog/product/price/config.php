<?php
require dirname(dirname(__FILE__)) . '/config.php';

define("__DATA_PATH__" , dirname(__FILE__) . "/data/");

define("__TABLE_PRODUCT__" , "catalog_product_entity");
define("__TABLE_PRICE__" , "catalog_product_entity_decimal");
define("__TABLE_PRICE_GROUP__" , "catalog_product_entity_group_price");
define("__TABLE_PRICE_TIER__" , "catalog_product_entity_tier_price");

//retail price's attribute_id is 75
define("__ATTR_RETAIL_PRICE__", 75);
define("__ATTR_SPECIAL_PRICE__" , 76);

//name of primary key in product's table
define("__PRIMARY_PRODUCT_ID__" , "entity_id");


//group price
$_priceRule["group"] = array(
    7   => "Återförsäljare 1%",
    8   => "Återförsäljare 2%",
    9   => "Återförsäljare 3%",
    10  => "Återförsäljare 4%"
);
$_priceRule["rule"] = array(
    7   => 0.01,
    8   => 0.02,
    9   => 0.03,
    10  => 0.04
);


function hasSpecialPrice($_entityId)
{
    global $db;
    $sql = "SELECT COUNT(*) FROM " . __TABLE_PRICE__
        . " WHERE attribute_id=" . __ATTR_SPECIAL_PRICE__
        . " AND entity_id=" . $_entityId;
    return $db->fetchOne($sql);
}

function getRetailPrice($_entityId)
{
    global $db;
    $sql = "SELECT `value` FROM " . __TABLE_PRICE__
        . " WHERE attribute_id=" . __ATTR_RETAIL_PRICE__
        . " AND entity_id=" . $_entityId;
    return $db->fetchOne($sql);
}

function truncateGroupPrice()
{
    global $db;
    $sql = "TRUNCATE " . __TABLE_PRICE_GROUP__;
    return $db->query($sql);
}

function generateGroupPrice($_entityId)
{
    global $db,$_priceRule;
    $_return = false;
    foreach ($_priceRule["group"] as $_key => $_group) {
        //get retail price
        $_price = getRetailPrice($_entityId);
        if (!empty($_price)) {
            $_result = $_price * (1 - $_priceRule["rule"][$_key]);

            //set field for group_price table;
            $_data = array(
                "entity_id"  => $_entityId,
                "all_groups"    => 0,
                "customer_group_id" => $_key,
                "value"     => $_result
            );

            try {
                //insert into database
                if ($db->insert(__TABLE_PRICE_GROUP__ , $_data)) {
                    $_return = true;
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }
    return $_return;
}

function deleteSpeicalPrice($_entityId)
{
    global $db;
    $sql = "DELETE FROM " . __TABLE_PRICE__
        . " WHERE attribute_id=" . __ATTR_SPECIAL_PRICE__
        . " AND entity_id=" . $_entityId;
    return $db->query($sql);
}

function deleteGroupPrice($_entityId)
{
    global $db;
    $sql = "DELETE FROM " . __TABLE_PRICE_GROUP__
        . " WHERE entity_id=" . $_entityId;
    return $db->query($sql);
}

//tier
function hasTierPrice($_entityId)
{
    global $db;
    $sql = "SELECT COUNT(*) FROM " . __TABLE_PRICE_TIER__
        . " WHERE entity_id=" . $_entityId;
    return $db->fetchOne($sql);
}

function getTierPrice($_entityId)
{
    global $db;
    $sql = "SELECT `value` FROM " . __TABLE_PRICE_TIER__
        ." WHERE entity_id=" . $_entityId;
    return $db->fetchOne($sql);
}

function destroyTierPrice()
{
    global $db;
    $sql = "TRUNCATE TABLE `".__TABLE_PRICE_TIER__."`";
    return $db->query($sql);
}