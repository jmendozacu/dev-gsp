<?php


class Ecom_Utils_Model_Currency extends Mage_Directory_Model_Currency
{
    /**
     * Format price to currency format
     *
     * @param float $price
     * @param array $options
     * @param bool $includeContainer
     * @param bool $addBrackets
     * @return string
     */
    public function format($price, $options = array(), $includeContainer = true, $addBrackets = false)
    {
        $precision = (int) Mage::getStoreConfig("currency/options/frontend_precision");

        if($price == round($price) && Mage::getStoreConfig("currency/options/remove_trailing_zeros")) $precision = 0;
        else {
            $price = round($price,(int) Mage::getStoreConfig('currency/options/frontend_round_precision'));
        }

        return $this->formatPrecision($price, $precision, $options, $includeContainer, $addBrackets);
    }

}