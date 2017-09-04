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
 * @method Mirasvit_Rma_Model_Resource_Rule_Collection|Mirasvit_Rma_Model_Rule[] getCollection()
 * @method Mirasvit_Rma_Model_Rule load(int $id)
 * @method bool getIsMassDelete()
 * @method Mirasvit_Rma_Model_Rule setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method Mirasvit_Rma_Model_Rule setIsMassStatus(bool $flag)
 * @method Mirasvit_Rma_Model_Resource_Rule getResource()
 * @method int getStatusId()
 * @method $this setStatusId(int $param)
 * @method int getUserId()
 * @method $this setUserId(int $param)
 * @method bool getIsSendUser()
 * @method $this setIsSendUser(bool $param)
 * @method bool getIsSendCustomer()
 * @method $this setIsSendCustomer(bool $param)
 * @method string getEmailSubject()
 * @method $this setEmailSubject(string $param)
 * @method string getEmailBody()
 * @method $this setEmailBody(string $param)
 * @method bool getIsSendAttachment()
 * @method $this setIsSendAttachment(bool $param)
 * @method bool getIsStopProcessing()
 * @method $this setIsStopProcessing(bool $param)
 * @method string getOtherEmail()
 * @method $this setOtherEmail(string $param)
 */
class Mirasvit_Rma_Model_Rule extends Mage_Rule_Model_Rule
{
    const TYPE_PRODUCT = 'product';
    const TYPE_CART = 'cart';
    const TYPE_CUSTOM = 'custom';

    protected function _construct()
    {
        $this->_init('rma/rule');
    }

    public function toOptionArray($emptyOption = false)
    {
        return $this->getCollection()->toOptionArray($emptyOption);
    }

    /** Rule Methods **/
    public function getConditionsInstance()
    {
        return Mage::getModel('rma/rule_condition_combine');
    }

    public function getActionsInstance()
    {
        return Mage::getModel('rma/rule_action_collection');
    }

    public function getProductIds()
    {
        return $this->_getResource()->getRuleProductIds($this->getId());
    }

    public function toString($format = '')
    {
        $this->load($this->getId());
        $string = $this->getConditions()->asStringRecursive();

        $string = nl2br(preg_replace('/ /', '&nbsp;', $string));

        return $string;
    }
    /************************/
}
