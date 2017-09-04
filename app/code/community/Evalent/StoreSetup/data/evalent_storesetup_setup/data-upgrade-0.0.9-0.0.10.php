<?php

## import categorys
$categorys = array(
    "klader" => "Kläder",
    "accessoarer" => "Accessoarer",
    "skor" => "Skor",
    "inredning" => "Inredning",
    "alla-produkter" => "Alla produkter"
);

foreach($categorys as $urlKey => $categoryName){
  Mage::getModel('catalog/category')
      ->setName($categoryName)
      ->setUrlKey($urlKey)
      ->setIsActive(1)
      ->setDisplayMode('PRODUCTS')
      ->setIsAnchor(1)
      ->setStoreId(1)
      ->setPath("1/2")
      ->save();
}

Mage::getModel("core/config")->saveConfig("catalog/frontend/flat_catalog_category", "1", 'default', 0); // enable flat catalog