<?php
/**
 * @category    Evalent Modules
 * @package     Evalent_Social
 * @author      Evalent Group AB
 */

class Evalent_Social_Model_Verb
{
     public function toOptionArray()
    {
        return array(
            array('value'=>'like', 'label'=>Mage::helper('social')->__('Like')),
            array('value'=>'recommend', 'label'=>Mage::helper('social')->__('Recommend'))
        );
    }
}