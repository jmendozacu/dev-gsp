<?php

/**
 * Class FyndiqUtils contains utility functions
 */
class FyndiqUtils
{
    const PAGINATION_ITEMS_PER_PAGE = 50;
    const PAGINATION_PAGE_FRAME = 4;

    const NUMBER_OF_ALLOWED_IMAGES = 10;
    const NUMBER_OF_ALLOWED_PROPERTIES = 50;

    const NAME_PRODUCT_FEED_URL = 'product_feed_url';
    const NAME_NOTIFICATION_URL = 'order_notification_url';
    const NAME_PING_URL = 'product_feed_notification_url';

    const USER_AGENT_PREFIX = 'Fyndiq';
    const FYNDIQ_DEBUG_FLAG = 'FYNDIQ_DEBUG';

    // product_info statuses;
    const STATUS_FOR_SALE = 'FOR_SALE';
    const STATUS_NOT_FOR_SALE = 'NOT_FOR_SALE';

    const FILE_CACHE_INTERVAL = '1 hour';

    /**
     * County code mapping
     * @var array
     */
    private static $countryCodes = array(
        'Germany' => 'DE',
        'Sweden' => 'SE'
    );

    /**
     * Flag toggling the debug mode
     * @var boolean
     */
    private static $debugMode = false;

    /**
     * Debug start time stamp
     * @var integer
     */
    private static $debugStart = 0;

    /**
     * List of allowed currencies
     * @var array
     */
    public static $allowedCurrencies = array('SEK', 'EUR');

    /**
     * List of allowed markets
     * @var array
     */
    public static $allowedMarkets = array('DE', 'SE');

    /**
     * Generates pagination HTML
     *
     * @param int $total - total number of items to paginate
     * @param int $currentPage - current page
     * @param int $itemPerPage - optional items per page
     * @param int $pageFrame - optional page frame size
     * @return string
     */
    public static function getPaginationHTML($total, $currentPage, $itemPerPage = 0, $pageFrame = 0)
    {
        $html = '';
        $itemPerPage = $itemPerPage ? $itemPerPage : self::PAGINATION_ITEMS_PER_PAGE;
        $pageFrame = $pageFrame ? $pageFrame : self::PAGINATION_PAGE_FRAME;

        if ($total > $itemPerPage) {
            $pages = (int)($total / $itemPerPage);
            $count = ($total % $itemPerPage === 0) ? $pages : $pages + 1;
            $start = 1;

            $html .= '<ol class="pageslist">';

            $end = $start + $pageFrame;
            if ($currentPage != 1) {
                $start = $currentPage - 1;
                $end = $start + $pageFrame;
            }
            if ($end > $count) {
                $start = $count - ($pageFrame - 1);
            } else {
                $count = $end - 1;
            }

            if ($currentPage > $count - 1) {
                $html .= '<li><a href="#" data-page="' . ($currentPage - 1) . '">&lt;</a></li>';
            }

            for ($i = $start; $i <= $count; $i++) {
                if ($i >= 1) {
                    $html .= ($currentPage == $i) ? '<li class="current">' . $i . '</li>' : '<li><a href="#" data-page="' . $i . '">' . $i . '</a></li>';
                }
            }

            if ($currentPage < $count) {
                $html .= '<li><a href="#" data-page="' . ($currentPage + 1) . '">&gt;</a></li>';
            }

            $html .= '</ol>';
        }

        return $html;
    }

    /**
     * Returns the Fyndiq price
     *
     * @param float $productPrice Original product price
     * @param float $discountPercentage Discount percentage
     * @return float Fyndiq price
     */
    public static function getFyndiqPrice($productPrice, $discountPercentage)
    {
        return (float)($productPrice - (($discountPercentage / 100) * $productPrice));
    }

    /**
     * Formats price for export
     *
     * @param float $price
     * @return string
     */
    public static function formatPrice($price)
    {
        return number_format((float)$price, 2, '.', '');
    }

    /**
     * Simple debug function
     */
    public static function debug()
    {
        if (self::$debugMode) {
            $arguments = func_get_args();
            $name = array_shift($arguments);
            echo '<b>' . $name. '</b>' . ':<br/>';
            foreach ($arguments as $argument) {
                if (gettype($argument) == 'string') {
                    echo '<br/ ><pre>' . $argument . '</pre>';
                    continue;
                }
                var_dump($argument);
            }
            echo '<hr />';
        }
    }

    /**
     * debugStart enables module debugging
     */
    public static function debugStart()
    {
        self::$debugMode = true;
        self::$debugStart = microtime(true);
        error_reporting(-1);
        self::debug('PHP_VERSION', phpversion());
        self::debug('PHP_INTERNAL_ENCODING', mb_internal_encoding());
        self::debug('MEMORY LIMIT', ini_get('memory_limit'));
        self::debug('MAX EXECUTION TIME', ini_get('max_execution_time'));
        self::debug('SERVER TIME', date('c', time()));
    }

    /**
     * debugStop disables module debugging
     */
    public static function debugStop()
    {
        self::debug('ELAPESED TIME', microtime(true) - self::$debugStart);
        self::debug('PEAK MEMORY USAGE', memory_get_peak_usage(true));
        self::debug('SERVER TIME', date('c', time()));
        self::$debugMode = false;
    }

    /**
     * getUserAgentString returns the User-Agent string
     *
     * @param  string $platformName name of the platform
     * @param  string $platformVersion the version of the platform
     * @param  string $moduleName name of the module
     * @param  string $moduleVersion module version
     * @param  string $moduleBuild module build hash
     * @return string
     */
    public static function getUserAgentString(
        $platformName,
        $platformVersion,
        $moduleName = 'module',
        $moduleVersion = '',
        $moduleBuild = ''
    ) {
        $agent = sprintf('%s-%s/%s', self::USER_AGENT_PREFIX, $platformName, $platformVersion);
        if ($moduleVersion) {
            $agent .= sprintf(' %s/%s', $moduleName, $moduleVersion);
            if ($moduleBuild) {
                $agent .= sprintf(' (%s)', $moduleBuild);
            }
        }
        return $agent;
    }

    /**
     * getVersionLabel returns user friendly module version label
     *
     * @param  string $version module version
     * @param  string $build build hash
     * @return string
     */
    public static function getVersionLabel($version, $build)
    {
        return sprintf('v. %s (%s)', $version, $build);
    }
    /**
     * isDebug checks if debug mode is enabled
     *
     */
    public static function isDebug()
    {
        return getenv(self::FYNDIQ_DEBUG_FLAG) == 1;
    }

    /**
     * Returns true if export file must be regenerated
     *
     * @param  string $filePath path to the export file
     * @return bool
     */
    public static function mustRegenerateFile($filePath)
    {
        if (self::isDebug()) {
            return true;
        }
        if (file_exists($filePath) && filemtime($filePath) > strtotime('-' . self::FILE_CACHE_INTERVAL)) {
            return false;
        }
        return true;
    }

    /**
     * getCountryCode returns the country code by given country name
     *
     * @param  string $countryName Country name
     * @return string
     */
    public static function getCountryCode($countryName)
    {
        return array_key_exists($countryName, self::$countryCodes) ? self::$countryCodes[$countryName] : null;
    }

    /**
     * getTempFilename returns temporary file name in the provided directory
     *
     * @param  string $dir
     * @param  string $prefix
     * @return bool
     */
    public static function getTempFilename($dir, $prefix = 'fyndiq-')
    {
        return tempnam($dir, $prefix);
    }

    /**
     * moveFile moves file
     *
     * @param  string $oldName old file name
     * @param  string $newName new file name
     * @return bool
     */
    public static function moveFile($oldName, $newName)
    {
        return rename($oldName, $newName);
    }

    /**
     * deleteFile deletes a file
     *
     * @param  string $fileName file name
     * @return bool
     */
    public static function deleteFile($fileName)
    {
        return unlink($fileName);
    }
}
