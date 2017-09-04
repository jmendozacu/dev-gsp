<?php
require 'config.php';

$file = "product.csv";
$fp = fopen(__PATH_DATA_DIR__ . $file , "wb");
$header = array("SKU" , "title" , "Category Path");
fputcsv($fp , $header);

$_cid = 176; // samsung

$_category = Mage::getModel('catalog/category')->load($_cid);

$collection = Mage::getModel('catalog/product')
	->getCollection()
	->addCategoryFilter($_category)
	->addAttributeToSelect("*")
	->setOrder('entity_id' ,'DESC');
	//->setPageSize(10);

print_r($collection->count());

$data = array();
foreach ($collection->getItems() as $_item) {
	$_id = $_item->getId();
	$_product = Mage::getModel('catalog/product')->load($_id);
	$categoryIds = $_product->getCategoryIds(); 
	$_categories = getCategoryPath($categoryIds);
	
	$data = array("'".$_item->getSku()."'" , $_item->getName() , arrayToStr($_categories));
	if (fputcsv($fp , $data)) {
		echo $_item->getSku() . " was successfully to export\r\n";
		//exit;
	}
}


fclose($fp);

function getCategoryPath($_ids)
{
	if (is_array($_ids)) {
		$data = array();
		foreach ($_ids as $_cid) {
			$_category = Mage::getModel("catalog/category")->load($_cid);
			//$data[] = array('id'=>$_cid,"name"=>$_category->getName());
			$data[] = $_category->getName();
		}
		return $data;
	}
}