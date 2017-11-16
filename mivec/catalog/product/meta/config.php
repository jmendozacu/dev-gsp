<?php
require dirname(dirname(__FILE__)) . "/config.php";

define("__DATA_PATH__" , dirname(__FILE__) . "/data/");

define("__TABLE_PRODUCT__" , "catalog_product_entity");
define("__TABLE_PRODUCT_VARCHAR__" , "catalog_product_entity_varchar");
define("__TABLE_PRODUCT_DESC__" , "catalog_product_entity_text");

define("__ATTRIBUTE_TITLE__" , 71);
define("__ATTRIBUTE_SHORTDESC__" , 73);


function updateShortDesc($_entityId , $_data)
{
    global $db;
    $_return = false;

/*    $_where = array(
        "attribute_id=" . __ATTRIBUTE_SHORTDESC__,
        "entity_id="    .$_entityId
    );

    try {
        if ($db->update(__TABLE_PRODUCT_DESC__ , $_data , $_where)){
            $_return = true;
        }
    } catch (Exception $e) {
        print_r($e);
    }*/

    $sql = "UPDATE " . __TABLE_PRODUCT_DESC__
        . " SET `value`='" . $_data."'"
        . " WHERE attribute_id=" . __ATTRIBUTE_SHORTDESC__
        . " AND entity_id=" . $_entityId;
    try {
        if ($db->query($sql)) {
            $_return = TRUE;
        }
    } catch (Exception $e) {
        print_r($e);
    }

    return $_return;
}

function getProductName($_entityId)
{
    global $db;
    $sql = "SELECT `value` FROM " . __TABLE_PRODUCT_VARCHAR__
        ." WHERE attribute_id=" . __ATTRIBUTE_TITLE__
        ." AND entity_id=" . $_entityId;

    return $db->fetchOne($sql);
}