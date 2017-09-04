<?php

class Ecom_BestsellerIndex_Model_Resource_Eav_Mysql4_Setup extends Mage_Eav_Model_Entity_Setup {

	/**
	 * @return array
	 */
	public function getDefaultEntities() {
		return array(
			'catalog_product' => array(
				'entity_model' => 'catalog/product',
				'attribute_model' => 'catalog/resource_eav_attribute',
				'table' => 'catalog/product',
				'additional_attribute_table' => 'catalog/eav_attribute',
				'entity_attribute_collection' => 'catalog/product_attribute_collection',
				'attributes' => array(
					'bestseller_index' => array(
						'group' => 'General',
						'label' => 'Bästsäljare',
						'type' => 'int',
						'input' => 'text',
						'default' => '0',
						'class' => 'validate-digits', // doesn't work
						'backend' => '',
						'frontend' => '',
						'source' => '',
						'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
						'visible' => true,
						'required' => false,
						'user_defined' => false,
						'searchable' => false,
						'filterable' => false,
						'comparable' => false,
						'visible_on_front' => false,
						'visible_in_advanced_search' => false,
						'unique' => false,
						'used_in_product_listing' => true, // doesn't work
						'used_for_sort_by' => true, // doesn't work
						'used_for_promo_rules' => true // doesn't work
					)
				)
			)
		);
	}

}