<?php

# add config for Lesti_Fpc

$key = "catalog/frontend/default_sort_by";
$currentValue = Mage::getStoreConfig($key,0);
$newValue = "bestseller_index";

Mage::getModel("core/config")->saveConfig($key, $newValue, 'default', 0);