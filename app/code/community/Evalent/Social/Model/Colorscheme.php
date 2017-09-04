<?php
/**
 * @category    Evalent Modules
 * @package     Evalent_Social
 * @author      Evalent Group AB
 */

class Evalent_Social_Model_Colorscheme
{
     public function toOptionArray()
    {
        return array(
            array('value'=>'light', 'label'=>Mage::helper('social')->__('Light')),
            array('value'=>'dark', 'label'=>Mage::helper('social')->__('Dark'))
        );
    }
}
