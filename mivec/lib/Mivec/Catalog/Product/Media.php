<?php
class Mivec_Catalog_Product_Media extends Mivec_Catalog_Abstract
{
	protected $_product;
	protected $_data;
	protected $_media;
	protected $_mediaId;
	
	public function __construct(Mivec_Catalog_Product $product)
	{
		$this->_product = $product;
		$this->_data = $this->_product->getData();
	}
}