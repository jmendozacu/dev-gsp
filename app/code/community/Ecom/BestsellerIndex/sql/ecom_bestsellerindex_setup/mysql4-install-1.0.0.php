<?php

    $installer = $this;
     
    $installer->installEntities();
	
	$code = 'bestseller_index';
	
	$installer->updateAttribute('catalog_product',$code,'frontend_class','validate-digits');
	$installer->updateAttribute('catalog_product',$code,'used_in_product_listing',true);
	$installer->updateAttribute('catalog_product',$code,'used_for_sort_by',true);
	$installer->updateAttribute('catalog_product',$code,'used_for_promo_rules',true);
	