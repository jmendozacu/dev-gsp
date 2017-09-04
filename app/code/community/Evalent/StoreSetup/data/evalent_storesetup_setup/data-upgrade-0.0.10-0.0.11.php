<?php

/**
 * Fix cms-pages that was imported by ultimo
 * This sets the root template of all pages that doesn't have one (null)
 */
$pages = Mage::getModel("cms/page")->getCollection();
foreach($pages as $page) {
    if($page->getRootTemplate() === null){
        $page->setRootTemplate("one_column")->save();
    }
}