<?php

class Ecom_Utils_Block_Checkout_Cart_Totals extends Mage_Checkout_Block_Cart_Totals
{
   
    public function renderTotal($total, $area = null, $colspan = 1)
    {
        $code = $total->getCode();
        if ($total->getAs()) {
            $code = $total->getAs();
        }
        return $this->_getTotalRenderer($code)
            ->setTotal($total)
            ->setColspan($colspan)
            ->setRenderingArea(is_null($area) ? -1 : $area)
				->setQuote($this->getQuote()) // we need access to the quote,,
            //->setDelog()// JUST DEBUG
				->toHtml();
    }

}
