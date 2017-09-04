<?php
/**
 * @category    Evalent Modules
 * @package     Evalent_Social
 * @author      Evalent Group AB
 */

class Evalent_Social_Model_Stream
{
     public function toOptionArray()
    {
        return array(
        	array('value'=>'true', 'label'=>Mage::helper('social')->__('Yes')),
            array('value'=>'false', 'label'=>Mage::helper('social')->__('No'))
        );
    }
}