<?php
/**
 * PayEx Library: Soap Adapter: NuSoap
 * Created by AAIT Team.
 */
class Payexautopay_Px_Soap_NuSoap extends Payexautopay_Px_Soap_Abstract
{
    /**
     * Get Adapter Instance
     * @static
     * @return object
     */
    static public function getAdapter()
    {
        // Check Requirements
        if (!extension_loaded('curl')) {
            throw new Payexautopay_Px_Exception('Failed to load NuSoapClientEx: curl extension required.');
        }
        if (!extension_loaded('openssl')) {
            throw new Payexautopay_Px_Exception('Failed to load NuSoapClientEx: openssl extension required.');
        }
        throw new Payexautopay_Px_Exception('NuSoap not available. Please use the extension of SOAP.');

        // Load NuSoapClientEx library
        //require_once realpath(dirname(__FILE__) . '/NuSoapClientEx/NuSoapClientEx.php');

        // Get Object using Reflection
        //$arg_list = func_get_args();
        //$rc = new ReflectionClass('NuSoapClientEx');
        //return $rc->newInstanceArgs($arg_list);
    }
}
