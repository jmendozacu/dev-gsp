<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   RMA
 * @version   2.0.1
 * @build     982
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */



/**
 * @method Mirasvit_Rma_Model_Resource_Template_Collection|Mirasvit_Rma_Model_Template[] getCollection()
 * @method Mirasvit_Rma_Model_Template load(int $id)
 * @method bool getIsMassDelete()
 * @method Mirasvit_Rma_Model_Template setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method Mirasvit_Rma_Model_Template setIsMassStatus(bool $flag)
 * @method Mirasvit_Rma_Model_Resource_Template getResource()
 */
class Mirasvit_Rma_Model_Template extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('rma/template');
    }

    public function toOptionArray($emptyOption = false)
    {
        return $this->getCollection()->toOptionArray($emptyOption);
    }

    /************************/

    public function getParsedTemplate($rma)
    {
        $storeId = $rma->getStoreId();
        $storeOb = Mage::getModel('core/store')->load($storeId);
        if (!$name = Mage::getStoreConfig('general/store_information/name', $storeId)) {
            $name = $storeOb->getName();
        }
        $store = new Varien_Object(array(
            'name' => $name,
            'phone' => Mage::getStoreConfig('general/store_information/phone', $storeId),
            'address' => Mage::getStoreConfig('general/store_information/address', $storeId),
        ));
        $user = Mage::getSingleton('admin/session')->getUser();

        $result = Mage::helper('mstcore/parsevariables')->parse($this->getTemplate(), array(
            'rma' => $rma,
            'store' => $store,
            'user' => $user,
        ),
            array(), $store->getId());

        return $result;
    }
}
