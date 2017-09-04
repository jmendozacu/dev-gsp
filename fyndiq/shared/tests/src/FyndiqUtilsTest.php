<?php

class FyndiqUtilsTest extends \PHPUnit_Framework_TestCase
{

    function testGetPaginationHTMLProvider()
    {
        return array(
            array(
                'Check if it returns empty html if total < itemsPerPage',
                1,
                1,
                10,
                4,
                ''
            ),
            array(
                'Check if it works properly',
                11,
                1,
                10,
                4,
                '<ol class="pageslist"><li class="current">1</li><li><a href="#" data-page="2">2</a></li><li><a href="#" data-page="2">&gt;</a></li></ol>'
            ),
            array(
                'Check if the span properly',
                30,
                3,
                2,
                2,
                '<ol class="pageslist"><li><a href="#" data-page="2">&lt;</a></li><li><a href="#" data-page="2">2</a></li><li class="current">3</li></ol>'
            ),
        );
    }

    /**
     * @param $message
     * @param $total
     * @param $currentPage
     * @param $itemPerPage
     * @param $pageFrame
     * @param $expected
     * @dataProvider testGetPaginationHTMLProvider
     */
    public function testGetPaginationHTML($message, $total, $currentPage, $itemPerPage, $pageFrame, $expected)
    {
        $this->assertEquals(
            $expected,
            FyndiqUtils::getPaginationHTML($total, $currentPage, $itemPerPage, $pageFrame),
            $message
        );
    }

    function testGetFyndiqPriceProvider()
    {
        return array(
            array(
                'Calculates the discount properly',
                100,
                10,
                90
            )
        );
    }

    /**
     * @param $message
     * @param $productPrice
     * @param $discountPercentage
     * @param $expected
     * @dataProvider testGetFyndiqPriceProvider
     */
    public function testGetFyndiqPrice($message, $productPrice, $discountPercentage, $expected)
    {
        $this->assertEquals($expected, FyndiqUtils::getFyndiqPrice($productPrice, $discountPercentage), $message);
    }

    public function testGetVersionLabel()
    {
        $expected = 'v. 6.6.6 (123321)';
        $label = FyndiqUtils::getVersionLabel('6.6.6', '123321');
        $this->assertEquals($expected, $label);
    }

    public function testGetUserAgentStringProvider()
    {
        return array(
            array(
                'platform',
                '6.6.6',
                'module',
                '',
                '',
                'Fyndiq-platform/6.6.6',
                'Module is optional',
            ),
            array(
                'platform',
                '6.6.6',
                'module_name',
                '7.7.7',
                '',
                'Fyndiq-platform/6.6.6 module_name/7.7.7',
                'Module build is optional',
            ),
            array(
                'platform',
                '6.6.6',
                'module_name',
                '7.7.7',
                '8888888',
                'Fyndiq-platform/6.6.6 module_name/7.7.7 (8888888)',
                'Module build is shown if present',
            ),
        );
    }

    /**
     * Test UserAgentString
     * @param  string $platformName
     * @param  string $platformVersion
     * @param  string $moduleName
     * @param  string $moduleVersion
     * @param  string $moduleBuild
     * @param  string $expected
     * @param  string $message
     * @dataProvider testGetUserAgentStringProvider
     */
    public function testGetUserAgentString(
        $platformName,
        $platformVersion,
        $moduleName,
        $moduleVersion,
        $moduleBuild,
        $expected,
        $message
    ) {
        $result = FyndiqUtils::getUserAgentString(
            $platformName,
            $platformVersion,
            $moduleName,
            $moduleVersion,
            $moduleBuild
        );
        $this->assertEquals($expected, $result, $message);
    }

    public function testAllowedArrays()
    {
        $this->assertInternalType('array', FyndiqUtils::$allowedCurrencies);
        $this->assertInternalType('array', FyndiqUtils::$allowedMarkets);
    }

    public function testFormatPriceProvider()
    {
        return array(
            array(1.22, 1.22),
            array(1.2232323, 1.22),
            array(1.2172323, 1.22),
            array('1.22', 1.22),
        );
    }

    /**
     * testFormatPrice
     * @param  mixed $price
     * @param  decimal $expected
     * @dataProvider testFormatPriceProvider
     */
    public function testFormatPrice($price, $expected)
    {
        $result = FyndiqUtils::formatPrice($price);
        $this->assertEquals($expected, $result);
    }

    public function testGetCountryCodeProvider()
    {
        return array(
            array('Sweden', 'SE'),
            array('Germany', 'DE'),
            array('Iceland', null),
        );
    }

    /**
     * testGetCountryCode
     * @param  string $countryName
     * @param  string $expected
     * @dataProvider testGetCountryCodeProvider
     */
    public function testGetCountryCode($countryName, $expected)
    {
        $result = FyndiqUtils::getCountryCode($countryName);
        $this->assertEquals($expected, $result);
    }
}
