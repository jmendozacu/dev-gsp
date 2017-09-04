<?php
require 'config.php';

$_file = __DATA_PATH__."/check.img.csv";
$fp = fopen($_file,'wb');
$header = array("id" , "SKU" , "TITLE" ,'Status',"URL");
fputcsv($fp , $header);

$sql = "SELECT a.entity_id,a.sku,b.`value` AS img FROM catalog_product_entity a LEFT JOIN catalog_product_entity_media_gallery b ON(a.entity_id=b.entity_id) WHERE `value` IS NULL";
if ($row = $db->fetchAll($sql)) {
	foreach ($row as $rs) {
		$_id = $rs['entity_id'];
		$_sku = $rs['sku'];
		$_product = Mage::getModel('catalog/product')
			->load($_id);
		
		//check enabled & disable
		if ($_product->getStatus() == 2) {
			//check stock
			//if (checkInventory($_id)) {
				$arr = array($_id , "," . $_sku , $_product->getName() , $_product->getStatus() , str_replace(__RUNTIME_DEV__ , __RUNTIME_PROD__ , $_product->getProductUrl()));
				//print_r($arr);exit;
				
				if (fputcsv($fp , $arr)) {
					echo "$_sku was success to save</p>";
				}
			//}
		}
		usleep(5);
	}
}

fclose($fp);

function checkInventory($_productId)
{
	$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_productId);
	//print_r($stockItem->getData("is_in_stock"));exit;
	if ($stockItem->getQty() > 0 && $stockItem->getData("is_in_stock") !=0) {
		return true;
	}
	return false;
}