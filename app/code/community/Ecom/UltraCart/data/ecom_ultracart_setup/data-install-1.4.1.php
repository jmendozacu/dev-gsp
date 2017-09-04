<?php

# add config for Lesti_Fpc

$key = "system/fpc/refresh_actions";
$currentValue = Mage::getStoreConfig($key,0);
$newValue = $currentValue.",
ultracart_ajax_reload,
ultracart_ajax_setqty";

Mage::getModel("core/config")->saveConfig($key, $newValue, 'default', 0);

$key = "system/fpc/lazy_blocks";
$currentValue = Mage::getStoreConfig($key,0);
$newValue = $currentValue.",
ultracart.block,
ultracart.clone.block";

Mage::getModel("core/config")->saveConfig($key, $newValue, 'default', 0);