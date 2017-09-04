<?php

class TestPagination extends FyndiqPaginatedFetch
{
    function getInitialPath()
    {
        return 'test/';
    }
    function getPageData($path)
    {
        $res = new stdClass();
        $res->results = true;
        $res->next = null;
        return $res;
    }
    function processData($data)
    {
        return true;
    }
    function getSleepIntervalSeconds()
    {
        return .01;
    }
}


class FyndiqPaginatedFetchTest extends PHPUnit_Framework_TestCase
{

    function testGetAll()
    {
        $test = new TestPagination();
        $result = $test->getAll();
        $this->assertTrue($result);
    }

    function testGetUSleepIntervalDataProvider()
    {
        return array(
            array(
                0.2,
                0.42,
                1 * TestPagination::NS_IN_SEC,
                780000,
                'Calculates properly the usleep interval',
            ),
            array(
                0.2,
                1.42,
                1 * TestPagination::NS_IN_SEC,
                0,
                'Use the max interval if the start-end exceeds it',
            ),
            array(
                0.2,
                0.3,
                .2 * TestPagination::NS_IN_SEC,
                100000,
                'Fractional seconds',
            ),
        );
    }

    /**
     * @param float $start
     * @param float $stop
     * @param int $max
     * @param int $expected
     * @param string $message
     *
     * @dataProvider testGetUSleepIntervalDataProvider
     */
    function testGetUSleepInterval($start, $stop, $max, $expected, $message)
    {
        $test = new TestPagination();
        $actual = $test->getUSleepInterval($start, $stop, $max);
        $this->assertEquals($expected, $actual, $message);
    }
}
