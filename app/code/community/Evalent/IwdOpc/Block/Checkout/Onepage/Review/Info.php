<?php

class Evalent_IwdOpc_Block_Checkout_Onepage_Review_Info extends Mage_Checkout_Block_Onepage_Review_Info {
    public function getTemplate(){
        $parent = parent::getTemplate();
        if($parent == "opc/onepage/review/info.phtml") return "evalent/iwdopc/onepage/review/info.phtml";
        else return $parent;
    }
}