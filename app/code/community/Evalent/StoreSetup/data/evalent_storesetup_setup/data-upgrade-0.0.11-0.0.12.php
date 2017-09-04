<?php

/**
 * Fix cms-pages that was imported by ultimo
 * This makes sure that all pages is added to correct stores
 */
$pages = Mage::getModel("cms/page")->getCollection();
foreach($pages as $page) {
    $stores = (array)$page->getStores();
    if(!in_array(0,$stores)) $stores[]=0;
    if(!in_array(1,$stores)) $stores[]=1;
    $page->setStores($stores)->save();
}