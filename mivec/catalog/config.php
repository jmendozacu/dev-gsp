<?php
header('Content-type:text/html;charset=utf-8');

require dirname(dirname(__FILE__)) . '/include/config.php';

$app = Mage::app();
$db = Mage::getSingleton('core/resource')
        ->getConnection('core_read');


define("__BASE_URL__" , Mage::getBaseUrl());
define("__MIVEC_URL__" , __RUNTIME_DEV__."/mivec/");


$conn['status'] = array(
    1   => 'Enabled',
    2  => 'Disabled'
);

//db object for global
function db()
{
    global $db;
    return $db;
}