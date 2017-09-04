<?php

# add config for Lesti_Fpc

$key = "system/fpc/uri_params";
$currentValue = Mage::getStoreConfig($key,0);
$newValue = $currentValue.",
price,
color,
size_shoes,
size_clothes";

Mage::getModel("core/config")->saveConfig($key, $newValue, 'default', 0);