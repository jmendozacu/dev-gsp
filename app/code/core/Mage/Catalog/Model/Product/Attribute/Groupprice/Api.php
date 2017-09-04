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
 * Catalog Product tier price api
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Product_Attribute_Groupprice_Api extends Mage_Catalog_Model_Api_Resource
{
    const ATTRIBUTE_CODE = 'group_price';

    public function __construct()
    {
        $this->_storeIdSessionField = 'product_store_id';
    }

    public function info($productId, $identifierType = null)
    {
        $product = $this->_initProduct($productId, $identifierType);
        $groupPrices = $product->getData(self::ATTRIBUTE_CODE);

        if (!is_array($groupPrices)) {
            return array();
        }

        $result = array();

        foreach ($groupPrices as $groupPrice) {
            $row = array();
            $row['customer_group_id'] = (empty($groupPrice['all_groups']) ? $groupPrice['cust_group'] : 'all' );
            $row['website']           = ($groupPrice['website_id'] ?
                            Mage::app()->getWebsite($groupPrice['website_id'])->getCode() :
                            'all'
                    );            
            $row['price']             = $groupPrice['price'];

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Update tier prices of product
     *
     * @param int|string $productId
     * @param array $groupPrices
     * @return boolean
     */
    public function update($productId, $groupPrices, $identifierType = null)
    {
        $product = $this->_initProduct($productId, $identifierType);

        $updatedGroupPrices = $this->prepareGroupPrices($product, $groupPrices);
        if (is_null($updatedGroupPrices)) {
            $this->_fault('data_invalid', Mage::helper('catalog')->__('Invalid Group Prices'));
        }

        $product->setData(self::ATTRIBUTE_CODE, $updatedGroupPrices);
        try {
            /**
             * @todo implement full validation process with errors returning which are ignoring now
             * @todo see Mage_Catalog_Model_Product::validate()
             */
            if (is_array($errors = $product->validate())) {
                $strErrors = array();
                foreach($errors as $code=>$error) {
                    $strErrors[] = ($error === true)? Mage::helper('catalog')->__('Value for "%s" is invalid.', $code) : Mage::helper('catalog')->__('Value for "%s" is invalid: %s', $code, $error);
                }
                $this->_fault('data_invalid', implode("\n", $strErrors));
            }

            $product->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('not_updated', $e->getMessage());
        }

        return true;
    }

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

        if (!is_array($groupPrices)) {
            $this->_fault('data_invalid', Mage::helper('catalog')->__('Invalid Tier Prices'));
        }

        $updateValue = array();

        foreach ($groupPrices as $groupPrice) {
            if (!is_array($groupPrice)           
                || !isset($groupPrice['price'])) {
                $this->_fault('data_invalid', Mage::helper('catalog')->__('Invalid Group Prices'));
            }

            if (!isset($groupPrice['website']) || $groupPrice['website'] == 'all') {
                $groupPrice['website'] = 0;
            } else {
                try {
                    $groupPrice['website'] = Mage::app()->getWebsite($groupPrice['website'])->getId();
                } catch (Mage_Core_Exception $e) {
                    $groupPrice['website'] = 0;
                }
            }

            if (intval($groupPrice['website']) > 0 && !in_array($groupPrice['website'], $product->getWebsiteIds())) {
                $this->_fault('data_invalid', Mage::helper('catalog')->__('Invalid group prices. The product is not associated to the requested website.'));
            }

            if (!isset($groupPrice['customer_group_id'])) {
                $groupPrice['customer_group_id'] = 'all';
            }

            if ($groupPrice['customer_group_id'] == 'all') {
                $groupPrice['customer_group_id'] = Mage_Customer_Model_Group::CUST_GROUP_ALL;
            }

            $updateValue[] = array(
                'website_id' => $groupPrice['website'],
                'cust_group' => $groupPrice['customer_group_id'],
                'price'      => $groupPrice['price']
            );
        }

        return $updateValue;
    }

    /**
     * Retrieve product
     *
     * @param int $productId
     * @param  string $identifierType
     * @return Mage_Catalog_Model_Product
     */
    protected function _initProduct($productId, $identifierType = null)
    {
        $product = Mage::helper('catalog/product')->getProduct($productId, $this->_getStoreId(), $identifierType);
        if (!$product->getId()) {
            $this->_fault('product_not_exists');
        }

        return $product;
    }
} // Class Mage_Catalog_Model_Product_Attribute_Groupprices End
