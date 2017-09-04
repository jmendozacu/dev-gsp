<?php

/**
 * Class FyndiqFeedWriter
 */
abstract class FyndiqFeedWriter
{
    const ELLIPSIS = '…';
    const ENCODING = 'UTF-8';

    // Shared fields
    const ID = 'id';
    const PAUSED = 'paused';
    const PRICE = 'price';
    const OLDPRICE = 'oldprice';
    const QUANTITY = 'quantity';
    const SKU = 'sku';
    const LOCATION = 'location';
    const IMAGES = 'images';
    const PROPERTIES = 'properties';

    const PROPERTY_NAME = 'name';
    const PROPERTY_VALUE = 'value';

    // Product specific fields
    const PRODUCT_ID = 'product-id';
    const PRODUCT_PAUSED = 'product-paused';
    const PRODUCT_PRICE = 'product-price';
    const PRODUCT_OLDPRICE = 'product-oldprice';
    const PRODUCT_MARKET = 'product-market';
    const PRODUCT_TITLE = 'product-title';
    const PRODUCT_DESCRIPTION = 'product-description';
    const PRODUCT_BRAND_NAME = 'product-brand-name';
    const PRODUCT_CATEGORY_ID = 'product-category-id';
    const PRODUCT_CATEGORY_NAME = 'product-category-name';
    const PRODUCT_CATEGORY_FYNDIQ_ID = 'product-category-fyndiq-id';
    const PRODUCT_CURRENCY = 'product-currency';
    const PRODUCT_VAT_PERCENT = 'product-vat-percent';
    const PRODUCT_PORTION = 'product-portion';
    const PRODUCT_COMPARISON_UNIT = 'product-comparison-unit';

    // These are used for validation purposes
    const PRODUCT_IMAGE_1_IDENTIFIER = 'product-image-1-identifier';
    const PRODUCT_IMAGE_1_URL = 'product-image-1-url';

    // Image templates
    const PRODUCT_IMAGE_URL_TEMPLATE = 'product-image-%d-url';
    const PRODUCT_IMAGE_IDENTIFIER_TEMPLATE = 'product-image-%d-identifier';

    // Article specific fields
    const ARTICLE_NAME = 'article-name';
    const ARTICLE_SKU = 'article-sku';
    const ARTICLE_QUANTITY = 'article-quantity';
    const ARTICLE_LOCATION = 'article-location';
    const ARTICLE_IDENTIFIER_TYPE = 'article-identifier-type'; // OBSOLETE
    const ARTICLE_IDENTIFIER_VALUE = 'article-identifier-value'; // OBSOLETE
    const ARTICLE_EAN = 'article-ean';
    const ARTICLE_ISBN = 'article-isbn';
    const ARTICLE_MPN = 'article-mpn';

    const ARTICLE_PROPERTY_NAME_TEMPLATE = 'article-property-%d-name';
    const ARTICLE_PROPERTY_VALUE_TEMPLATE = 'article-property-%d-value';


    /**
     * @var resource feed file handler
     */
    protected $stream = null;

    /**
     * Required columns
     * @var array
     */
    public static $requiredColumns = array(
        self::PRODUCT_ID,
        self::PRODUCT_IMAGE_1_IDENTIFIER,
        self::PRODUCT_IMAGE_1_URL,
        self::PRODUCT_TITLE,
        self::PRODUCT_MARKET,
        self::PRODUCT_DESCRIPTION,
        self::PRODUCT_PRICE,
        self::PRODUCT_OLDPRICE,
        self::PRODUCT_CURRENCY,
        self::PRODUCT_VAT_PERCENT,
        self::ARTICLE_QUANTITY,
        self::ARTICLE_SKU,
        self::ARTICLE_NAME,
    );

    /**
     * Textual fields
     * @var array
     */
    protected $textColumns = array(
        self::PRODUCT_BRAND_NAME,
        self::PRODUCT_TITLE,
        self::PRODUCT_CATEGORY_NAME,
        self::PRODUCT_DESCRIPTION,
        self::ARTICLE_LOCATION,
        self::ARTICLE_NAME,
        self::ARTICLE_SKU,
    );

    /**
     * Fields with length limitations
     * @var array
     */
    public static $lengthLimitedColumns = array(
        self::PRODUCT_ID => 32,
        self::PRODUCT_BRAND_NAME => 32,
        self::PRODUCT_CATEGORY_ID => 32,
        self::PRODUCT_CATEGORY_NAME => 512,
        self::PRODUCT_TITLE => 64,
        self::PRODUCT_DESCRIPTION => 4096,
        self::ARTICLE_SKU => 64,
        self::ARTICLE_LOCATION => 64,
        self::ARTICLE_NAME => 30,
    );

    public static $minLength = array(
        self::PRODUCT_ID => 1,
        self::PRODUCT_TITLE => 5,
        self::PRODUCT_DESCRIPTION => 10,
        self::ARTICLE_SKU => 1,
        self::ARTICLE_NAME => 1,
        self::PRODUCT_IMAGE_1_IDENTIFIER => 1,
        self::PRODUCT_IMAGE_1_URL => 1,
    );

    /**
     * Columns with discrete values
     * @var array
     */
    protected $validContent = array(
        // VAT * 100
        self::PRODUCT_VAT_PERCENT => array(6, 7, 12, 19, 25),
    );

    protected $invalidContent = array(
        // price can't be 0
        self::PRODUCT_PRICE => 0.00,
        self::PRODUCT_OLDPRICE => 0.00
    );

    /**
     * Validation errors
     * @var array
     */
    protected $errors = array();

    /**
     * Add product to the export
     * @param array $product
     * @return bool
     */
    abstract public function addProduct($product);

    /**
     * Write the feed
     *
     * @return bool
     */
    abstract public function write();


    /**
     * Constructor
     *
     * @param resource $stream File handler for the stream
     */
    public function __construct($stream)
    {
        $this->stream = $stream;
    }

    /**
     * Check if passed product is valid
     *
     * @param array $product
     * @return bool
     */
    public function isValidProduct($product)
    {
        if (!is_array($product)) {
            $this->errors[] = 'Passed product is not array';
            return false;
        }
        $columns = array_keys($product);
        foreach (self::$requiredColumns as $columnName) {
            if (!in_array($columnName, $columns)) {
                $this->errors[] = 'Missing required column `' . $columnName . '`';
                return false;
            }
        }
        return $this->isValidContent($product);
    }

    /**
     * isValidContent checks if the values adhere to validation rules
     *
     * @param  array  $product product data
     * @return boolean
     */
    public function isValidContent($product)
    {
        if (isset($product[self::ARTICLE_SKU]) &&
            mb_strlen($product[self::ARTICLE_SKU], self::ENCODING) > self::$lengthLimitedColumns[self::ARTICLE_SKU]) {
            $this->errors[] = sprintf('Article SKU is too long: `%s`', $product[self::ARTICLE_SKU]);
            return false;
        }
        foreach ($this->validContent as $key => $values) {
            if (array_key_exists($key, $product)) {
                if ($key === 'product-vat-percent') {
                    // NOTE: This is ridiculous, but for some reason float(19) translates to int(18)
                    if (!in_array(intval((string)$product[$key]), $values)) {
                        $this->errors[] = 'Incorrect VAT = ' . $product[$key];
                        return false;
                    }
                    continue;
                }
                if (!in_array($product[$key], $value)) {
                    $this->errors[] = 'Incorrect value for column `' . $key . '` = ' . $product[$key];
                    return false;
                }
            }
        }
        foreach ($this->invalidContent as $key => $value) {
            if (array_key_exists($key, $product)) {
                if ($product[$key] == $value) {
                    $this->errors[] = 'Incorrect price = ' . $product[$key];
                    return false;
                }
                continue;
            }
        }
        foreach (self::$minLength as $field => $minLen) {
            if (array_key_exists($field, $product)) {
                $len = mb_strlen(trim($product[$field]), self::ENCODING);
                if ($len < $minLen) {
                    $this->errors[] =
                        sprintf('Value too short for `%s` expected length: `%d`, sent `%d`', $field, $minLen, $len);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * isColumnTooLong returns true if value is longer than defined
     *
     * @param  string  $columnName name of the field
     * @param  string  $value value of the field
     * @return boolean
     */
    public static function isColumnTooLong($columnName, $value)
    {
        if (isset(self::$lengthLimitedColumns[$columnName])) {
            $maxLength = self::$lengthLimitedColumns[$columnName];
            return mb_strlen($value, self::ENCODING) > $maxLength;
        }
        return false;
    }

    /**
     * shorten shortens text content if longer than defined
     *
     * @param  string $text text to check
     * @param  integer $maxLength maximum string length
     * @return string
     */
    public static function shorten($text, $maxLength)
    {
        if (mb_strlen($text, self::ENCODING) > $maxLength) {
            return mb_substr($text, 0, $maxLength - 1, self::ENCODING) . self::ELLIPSIS;
        }
        return $text;
    }

    /**
     * shortenLeft shortens text content from the left side if longer than defined
     *
     * @param  string $text text to check
     * @param  integer $maxLength maximum string length
     * @return string
     */
    public static function shortenLeft($text, $maxLength)
    {
        $len = mb_strlen($text, self::ENCODING);
        if ($len > $maxLength) {
            return self::ELLIPSIS . mb_substr($text, $len - $maxLength + 1, $len, self::ENCODING);
        }
        return $text;
    }

    /**
     * sanitizeColumn truncates fields to the defined maximum lengths
     * @param  string $columnName name of the field
     * @param  string $value value of the field
     * @return string shortened text
     */
    public static function sanitizeColumn($columnName, $value)
    {
        if (isset(self::$lengthLimitedColumns[$columnName])) {
            $maxLength = self::$lengthLimitedColumns[$columnName];
            if (self::isColumnTooLong($columnName, $value)) {
                if ($columnName === self::PRODUCT_CATEGORY_NAME) {
                    return self::shortenLeft($value, $maxLength);
                }
                $value = self::shorten($value, $maxLength);
            }
        }
        return $value;
    }

    /**
     * Sanitizes product data
     * @param  array $product
     * @return array
     */
    public function sanitizeProduct($product)
    {
        $sanitizedProduct = array();
        foreach ($product as $columnName => $value) {
            $sanitizedProduct[$columnName] = self::sanitizeColumn($columnName, $value);
        }
        return $sanitizedProduct;
    }

    /**
     * Typo version of getLastProductErrors
     *
     * @deprecated
     */
    public function getLastPriductErrors()
    {
        return $this->getLastProductErrors();
    }

    /**
     * Returns array of of errors for the last product
     *
     * @return array
     */
    public function getLastProductErrors()
    {
        return $this->errors;
    }

    /**
     * resetErrors resets the error list
     */
    protected function resetErrors()
    {
        $this->errors = array();
    }
}
