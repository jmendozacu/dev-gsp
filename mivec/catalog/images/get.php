<?php
/**
 * 根据SKU获取产品的图片并下载保存至data,目录用SKU命名
 */
require 'config.php';
//$_destinDir = __DATA_PATH__ . "/";

$id = 669; //Spare Parts-apple-iphone
$id = 207; //samsung-smartphone-spare-parts
/*$id = 513; //huawei

$id = 143; //HTC

//$id = 124; //LG

$id = 176; //SONY

$id = 269; //NOKIA

$id = 349; //ACER
//$id = 352; //ASUS
$id = 516; //ONE PLUS*/

$category = Mage::getModel('catalog/category')->load($id);
$collection = Mage::getModel('catalog/product')
	->getCollection()
	->addCategoryFilter($category)
	->addAttributeToSelect(array("id" , 'sku'))
	->setOrder("entity_id" , 'DESC');
	//->setPageSize(10);

echo $collection->count();exit;

foreach ($collection->getItems() as $_item) {
	$_id = $_item->getId();
	$_sku = $_item->getSku();
	$_destinDir = __DATA_PATH__ . "/" . $_sku;
	if (!file_exists($_destinDir)) {
		multiDir($_destinDir);
	}
	
	//echo $_id;
	if ($imgRows = getImages($_id)) {
		foreach ($imgRows as $_img) {
			//print_r($_img);
			$_source = $_mediaDir . $_img['url'];
			$_destin = $_destinDir . "/" . getImageParam($_img['url']);
			echo $_destin;exit;
			
			if (copy($_source,$_destin)) {
				echo "SKU $_sku was successfully to copy</p>";
				usleep(5);
			}
		}
	}
	unset($_destinDir);
}
