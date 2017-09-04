<?php
class AAIT_Payexautopay_Block_Adminhtml_Config_Hint extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

    protected $_template = 'payexautopay/hint.phtml';

    /**
     * Render fieldset html
     * @param Varien_Data_Form_Element_Abstract $element element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        // Prevent duplicate show
        if(isset($_SESSION['payex_hint_message_showed']) && $_SESSION['payex_hint_message_showed'] != 'payexautopay') {
            return '';
        }

        // Show hint message
        $message = Mage::helper('payexautopay')->__('<p>Thank you for using the official PayEx module!</p>');
        $message .= Mage::helper('payexautopay')->__('<h4>To follow the <a href="%s" target="_blank">EULA</a> you need to <a href="%s" target="_blank">register</a> the use of the module</h4>', 'http://shop.aait.se/license.txt', 'http://shop.aait.se/registration');
        $this->assign('message', $message);
        $_SESSION['payex_hint_message_showed'] = 'payexautopay';
        return $this->toHtml();
    }
}
