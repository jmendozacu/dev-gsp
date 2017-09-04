<?php

/**
 * Class FyndiqPaginatedFetch is used to fetch paginated resources
 */
abstract class FyndiqPaginatedFetch
{

    const THROTTLE_PRODUCT_INFO_RPS = 50;
    const THROTTLE_ORDER_RPS = 50;

    const NS_IN_SEC = 1000000;

    /**
     * getInitialPath returns the initial resource path
     *
     * @return string [description]
     */
    abstract function getInitialPath();

    /**
     * getPageData returns the content of a resource page
     *
     * @param  string $path page path
     * @return object
     */
    abstract function getPageData($path);

    /**
     * processData processes page of data
     * @param  object $data data object
     * @return boolean
     */
    abstract function processData($data);

    /**
     * getSleepIntervalSeconds returns the sleep interval between requests
     * @return int
     */
    abstract function getSleepIntervalSeconds();

    /**
     * Gets all data
     *
     * @return bool
     */
    public function getAll()
    {
        $nextPagePath = $this->getInitialPath();
        $sleepInterval = $this->getSleepIntervalSeconds();
        do {
            $data = $this->getPageData($nextPagePath);
            $start = microtime(true);
            $result = false;
            if ($data) {
                $result = $this->processData($data->results);
                $nextPagePath= $this->getPath($data->next);
            }
            // Sleep the remaining microseconds
            if ($sleepInterval) {
                usleep($this->getUSleepInterval($start, microtime(true), $sleepInterval * self::NS_IN_SEC));
            }
        } while ($result && $nextPagePath);
        return $result;
    }

    /**
     * Gets the usleep interval
     *
     * @param float $start starting time
     * @param float $stop stopping time
     * @param int $max $maximum sleeping time in nanoseconds
     * @return int mixed nanoseconds to sleep before next request
     */
    public function getUSleepInterval($start, $stop, $max)
    {
        return intval($max - min($max, ($stop - $start) * self::NS_IN_SEC));
    }

    /**
     * Gets page path from pagination URL
     *
     * @param string $url
     * @return string
     */
    protected function getPath($url)
    {
        if (empty($url)) {
            return '';
        }
        return implode('/', array_slice(explode('/', $url), 5));
    }
}
