<?php
/**
 * PayEx API Helper: API Handler
 * Created by AAIT Team.
 */
class AAIT_Payexautopay_Helper_Api extends Mage_Core_Helper_Abstract
{
    protected static $_px = null;

    /**
     * Get PayEx Api Handler
     * @static
     * @return Px_Px
     */
    public static function getPx()
    {
        // Use Singleton
        if (is_null(self::$_px)) {
            // Use Virtual Namespaces to PHP 5.2 compatibility
            // In new version use PHP 5.3 namespaces
            require_once dirname(__FILE__) . '/library/Px/Px.php';
            self::$_px = new Payexautopay_Px_Px();
        }
        return self::$_px;
    }
}