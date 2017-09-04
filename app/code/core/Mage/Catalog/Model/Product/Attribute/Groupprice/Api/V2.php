<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog Product tier price api V2
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Product_Attribute_Groupprice_Api_V2 extends Mage_Catalog_Model_Product_Attribute_Groupprice_Api
{
    /**
     *  Prepare tier prices for save
     *
     *  @param      Mage_Catalog_Model_Product $product
     *  @param      array $groupPrices
     *  @return     array
     */
    public function prepareGroupPrices($product, $groupPrices = null)
    {
        if (!is_array($groupPrices)) {
            return null;
        }

        $updateValue = array();

        foreach ($groupPrices as $groupPrice) {
            if (!is_object($groupPrice)
                || !isset($groupPrice->price)) {
                $this->_fault('data_invalid', Mage::helper('catalog')->__('Invalid Group Prices'));
            }

            if (!isset($groupPrice->website) || $groupPrice->website == 'all') {
                $groupPrice->website = 0;
            } else {
                try {
                    $groupPrice->website = Mage::app()->getWebsite($groupPrice->website)->getId();
                } catch (Mage_Core_Exception $e) {
                    $groupPrice->website = 0;
                }
            }

            if (intval($groupPrice->website) > 0 && !in_array($groupPrice->website, $product->getWebsiteIds())) {
                $this->_fault('data_invalid', Mage::helper('catalog')->__('Invalid group prices. The product is not associated to the requested website.'));
            }

            if (!isset($groupPrice->customer_group_id)) {
                $groupPrice->customer_group_id = 'all';
            }

            if ($groupPrice->customer_group_id == 'all') {
                $groupPrice->customer_group_id = Mage_Customer_Model_Group::CUST_GROUP_ALL;
            }

            $updateValue[] = array(
                'website_id' => $groupPrice->website,
                'cust_group' => $groupPrice->customer_group_id,
                'price'      => $groupPrice->price
            );

        }

        return $updateValue;
    }
}
