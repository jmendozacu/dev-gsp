<?php

## import matrixrate
$obj = new Varien_Object();
$obj->setScopeId(1); // website
$_FILES["groups"]["tmp_name"]["matrixrate"]["fields"]["import"]["value"] = Mage::getBaseDir('var')."/import/matrixrates-se-eu-2015.csv";
$_POST['groups']['matrixrate']['fields']['condition_name']['inherit'] = 1;
Mage::getResourceModel('matrixrate_shipping/carrier_matrixrate')->uploadAndImport($obj);

// cant set this in config (matrixrate loads after this))
Mage::getModel("core/config")->saveConfig("carriers/matrixrate/active", "1", 'default', 0);
Mage::getModel("core/config")->saveConfig("carriers/matrixrate/free_method_text", "Gratis frakt", 'default', 0);
Mage::getModel("core/config")->saveConfig("carriers/matrixrate/title", "", 'default', 0);