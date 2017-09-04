<?php

class FyndiqTranslationTest extends PHPUnit_Framework_TestCase
{

    function testInitProvider()
    {
        return array(
            array(
                'English must work',
                'en',
                'unhandled-error-title',
                'Unhandled error'
            ),
            array(
                'Non-existing language must fall back to English',
                'zzz',
                'unhandled-error-title',
                'Unhandled error'
            ),
            array(
                'Non existing keys must say so',
                'en',
                'barumbazumba',
                'barumbazumba'
            ),
            array(
                'Language with region must also work',
                'en_US',
                'unhandled-error-title',
                'Unhandled error'
            ),
        );
    }

    /**
     * @param $message
     * @param $language
     * @param $key
     * @param $expected
     * @dataProvider testInitProvider
     */
    function testInit($message, $language, $key, $expected)
    {
        $this->assertTrue(FyndiqTranslation::init($language));
        $this->assertEquals($expected, FyndiqTranslation::get($key), $message);
    }

    /**
     * @covers FyndiqTranslation::getAll
     */
    function testGetAll()
    {
        FyndiqTranslation::init('test');
        $this->assertInternalType('array', FyndiqTranslation::getAll());
    }
}
