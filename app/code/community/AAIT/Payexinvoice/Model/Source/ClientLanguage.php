<?php
/**
 * PayEx Invoice Payment
 * Created by AAIT Team.
 */
class AAIT_Payexinvoice_Model_Source_ClientLanguage
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'en-US',
                'label' => Mage::helper('payexinvoice')->__('English')
            ),
            array(
                'value' => 'sv-SE',
                'label' => Mage::helper('payexinvoice')->__('Swedish')
            ),
            array(
                'value' => 'nb-NO',
                'label' => Mage::helper('payexinvoice')->__('Norway')
            ),
            array(
                'value' => 'da-DK',
                'label' => Mage::helper('payexinvoice')->__('Danish')
            ),
            array(
                'value' => 'es-ES',
                'label' => Mage::helper('payexinvoice')->__('Spanish')
            ),
            array(
                'value' => 'de-DE',
                'label' => Mage::helper('payexinvoice')->__('German')
            ),
            array(
                'value' => 'fi-FI',
                'label' => Mage::helper('payexinvoice')->__('Finnish')
            ),
            array(
                'value' => 'fr-FR',
                'label' => Mage::helper('payexinvoice')->__('French')
            ),
            array(
                'value' => 'pl-PL',
                'label' => Mage::helper('payexinvoice')->__('Polish')
            ),
            array(
                'value' => 'cs-CZ',
                'label' => Mage::helper('payexinvoice')->__('Czech')
            ),
            array(
                'value' => 'hu-HU',
                'label' => Mage::helper('payexinvoice')->__('Hungarian')
            ),
        );
    }
}