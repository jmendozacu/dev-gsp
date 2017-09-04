<?php

class Ecom_Utils_Model_Observer
{
    /**
     * When the "flush all cache" button is pressed this event is fired
     * If Lesti_Fpc is enabled and the method ->clean is present
     * Clear the Full Page Cache
     * @param Varien_Event_Observer $observer Our event observer
     * @return Varien_Event_Observer $this Itself
     */
    public function flushLestiFpcOnFlushAll($observer)
    {
        //Is Lesti_Fpc available AND enabled?
        if(Mage::helper('core')->isModuleEnabled('Lesti_Fpc')){

            //Load the model responsible for cleaning
            $lestiFpcModel = Mage::getModel('fpc/fpc');

            //Is the model found and the method clean available?
            if(is_object($lestiFpcModel) && method_exists($lestiFpcModel,'clean')){

                //If so, Go CLEAN!
                $lestiFpcModel->clean();

            }
        }

        //Make a chainable return for style
        return $this;
    }
}