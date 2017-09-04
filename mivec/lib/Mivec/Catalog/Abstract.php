<?php
abstract class Mivec_Catalog_Abstract extends Mivec_Abstract
{
	protected function getProductData(Mage_Catalog_Model_Product $product)
	{
		print_r($product->getData());
	}
}