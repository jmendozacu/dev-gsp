<?php


class Ecom_Utils_Model_Catalog_Config extends Mage_Catalog_Model_Config
{


    /**
     * Retrieve Attributes Used for Sort by as array
     * key = code, value = name
     *
     * @return array
     */
    public function getAttributeUsedForSortByArray()
    {
        $options = parent::getAttributeUsedForSortByArray();

        if(!Mage::getStoreConfig("catalog/frontend/show_position_sort")) unset($options['position']);

        return $options;
    }
}
