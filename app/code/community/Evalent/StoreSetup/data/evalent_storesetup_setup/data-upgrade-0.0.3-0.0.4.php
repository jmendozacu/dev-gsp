<?php

Mage::getModel("core/config")->saveConfig("design/package/name", "evalent", 'default', 0);
Mage::getModel("core/config")->saveConfig("design/header/logo_alt", "eValent Group AB", 'default', 0);
Mage::getModel("core/config")->saveConfig("design/header/welcome", "Välkommen till vår E-handel", 'default', 0);
Mage::getModel("core/config")->saveConfig("design/footer/copyright", "&copy; 2015 All Rights Reserved.", 'default', 0);

Mage::getModel("core/config")->saveConfig("design/head/default_description", "", 'default', 0);
Mage::getModel("core/config")->saveConfig("design/head/default_keywords", "", 'default', 0);
Mage::getModel("core/config")->saveConfig("design/head/default_title", "", 'default', 0);

Mage::getModel("core/config")->saveConfig("general/store_informaion/merchant_country", "SE", 'default', 0);