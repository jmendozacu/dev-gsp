<?php

# add config for Lesti_Fpc

$key = "system/fpc/refresh_actions";
$currentValue = Mage::getStoreConfig($key,0);
$newValue = $currentValue.",
klarnacheckout_ajax_updatecheckout,
klarnacheckout_index_index";

Mage::getModel("core/config")->saveConfig($key, $newValue, 'default', 0);