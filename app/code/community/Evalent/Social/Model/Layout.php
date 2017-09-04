<?php
/**
 * @category    Evalent Modules
 * @package     Evalent_Social
 * @author      Evalent Group AB
 */

class Evalent_Social_Model_Layout
{
     public function toOptionArray()
    {
        return array(
        	array('value'=>'button_count', 'label'=>Mage::helper('social')->__('box_count')),
            array('value'=>'button_count', 'label'=>Mage::helper('social')->__('button_count')),
            array('value'=>'standard', 'label'=>Mage::helper('social')->__('standard'))
        );
    }
}
