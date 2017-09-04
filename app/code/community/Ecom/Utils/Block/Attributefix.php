<?php
class Ecom_Utils_Block_Attributefix extends Mage_Catalog_Block_Product_View_Attributes
	{
    /**
     * $excludeAttr is optional array of attribute codes to
     * exclude them from additional data array
     *
     * @param array $excludeAttr
     * @return array
     */
    public function getAdditionalData(array $excludeAttr = array())
    {
	    $data = array();
        $product = $this->getProduct();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($product);
					 
                if (!$product->hasData($attribute->getAttributeCode())) {
                    //$value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    //$value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                } elseif($attribute->getFrontend()->getConfigField('input') == 'select') {
                    if(!$attribute->getFrontend()->getOption($product->getData($attribute->getFrontend()->getAttribute()->getAttributeCode()))) $value = '';
                }
				
				if($product->getData($attribute->getAttributeCode()) === NULL) $value = '';

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
        return $data;
    }	}
