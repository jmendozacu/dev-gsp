<?php

## Set product attribute to be visible in admin
$attribute_code = 'created_at';

$model = Mage::getModel('catalog/resource_eav_attribute')->loadByCode(4, $attribute_code);
if ($model){
    $model->setIsVisible(1)->setUsedForSortBy(1)->save();

    $labels = array();
    $labels[0] = 'Created at';//default store label
    $labels[1] = 'Nyast';
    $oAttribute = Mage::getSingleton('eav/config')->getAttribute(4, $attribute_code);
    $oAttribute->setData('store_labels', $labels);
    $oAttribute->save();

}

