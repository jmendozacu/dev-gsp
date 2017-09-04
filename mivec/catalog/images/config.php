<?php
require dirname(dirname(__FILE__)) . '/config.php';
define('__DATA_PATH__',dirname(__FILE__) . '/data');

$app = Mage::app();
$db = Mage::getSingleton('core/resource')
        ->getConnection('core_read');

define("__TABLE_PRODUCT__" , "catalog_product_entity");
define('__TABLE_GALLERY__' , 'catalog_product_entity_media_gallery');

$_mediaDir = Mage::getBaseDir('media')."/catalog/product";

function getImageParam($_img , $_method = 'name')
{
	$_value = '';
	if ($_info = pathinfo($_img)) {
		if ($_method == 'name') {
			$_value = $_info['basename'];
		}
		
		if ($_method == 'dir') {
			$_value = $_info['dirname'];
		}
		return $_value;
	}
}

function getImages($_entityId)
{
	global $db;
	$sql = "SELECT `value` as url FROM " . __TABLE_GALLERY__." WHERE entity_id=$_entityId";
	return $db->fetchAll($sql);
}