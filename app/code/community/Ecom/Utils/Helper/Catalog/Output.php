<?php
class Ecom_Utils_Helper_Catalog_Output extends Mage_Catalog_Helper_Output {

	/**
	 * pass the "description"-attribute through the cms-processor
	 */
	public function categoryAttribute($category, $attributeHtml, $attributeName) {
		$attributeHtml = parent::categoryAttribute($category, $attributeHtml, $attributeName);
		
		if (!Mage::getStoreConfig('catalog/frontend/allow_widgets')) return $attributeHtml;
		
		if($attributeName != 'description') return $attributeHtml;
		
		return Mage::helper('cms')->getPageTemplateProcessor()->filter($attributeHtml);
	}
	
	/**
     * Prepare product attribute html output
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   string $attributeHtml
     * @param   string $attributeName
     * @return  string
     */
    public function productAttribute($product, $attributeHtml, $attributeName) {
		$attributeHtml = parent::productAttribute($product, $attributeHtml, $attributeName);
		
		if (!Mage::getStoreConfig('catalog/frontend/allow_widgets')) return $attributeHtml;

		$attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeName);

		if ($attribute->getIsHtmlAllowedOnFront() && $attribute->getIsWysiwygEnabled()) {
			return Mage::helper('cms')->getPageTemplateProcessor()->filter($attributeHtml);
		}

		return $attributeHtml;
	}

}
