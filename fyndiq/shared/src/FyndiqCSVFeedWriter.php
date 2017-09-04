<?php

/**
 * Class FyndiqCSVFeedWriter generates CSV export feed
 */
class FyndiqCSVFeedWriter extends FyndiqFeedWriter
{

    /**
     * Export column names
     * @var array
     */
    private $header = array(
        // Product specific fields
        self::PRODUCT_ID,
        self::PRODUCT_PAUSED,
        self::PRODUCT_PRICE,
        self::PRODUCT_OLDPRICE,
        self::PRODUCT_MARKET,
        self::PRODUCT_TITLE,
        self::PRODUCT_DESCRIPTION,
        self::PRODUCT_BRAND_NAME,
        self::PRODUCT_CATEGORY_ID,
        self::PRODUCT_CATEGORY_NAME,
        self::PRODUCT_CATEGORY_FYNDIQ_ID,
        self::PRODUCT_CURRENCY,
        self::PRODUCT_VAT_PERCENT,
        self::PRODUCT_PORTION,
        self::PRODUCT_COMPARISON_UNIT,

        // Article specific fields
        self::ARTICLE_NAME,
        self::ARTICLE_SKU,
        self::ARTICLE_QUANTITY,
        self::ARTICLE_LOCATION,
        self::ARTICLE_IDENTIFIER_TYPE,
        self::ARTICLE_IDENTIFIER_VALUE,
        self::ARTICLE_EAN,
        self::ARTICLE_ISBN,
        self::ARTICLE_MPN,
    );

    /**
     * Product specific field mapping
     * @var array
     */
    private $productFields = array(
        self::ID => self::PRODUCT_ID,
        self::PAUSED => self::PRODUCT_PAUSED,
        self::PRICE => self::PRODUCT_PRICE,
        self::OLDPRICE => self::PRODUCT_OLDPRICE,
        self::PRODUCT_MARKET => self::PRODUCT_MARKET,
        self::PRODUCT_TITLE => self::PRODUCT_TITLE,
        self::PRODUCT_DESCRIPTION => self::PRODUCT_DESCRIPTION,
        self::PRODUCT_BRAND_NAME => self::PRODUCT_BRAND_NAME,
        self::PRODUCT_CATEGORY_ID => self::PRODUCT_CATEGORY_ID,
        self::PRODUCT_CATEGORY_NAME => self::PRODUCT_CATEGORY_NAME,
        self::PRODUCT_CATEGORY_FYNDIQ_ID => self::PRODUCT_CATEGORY_FYNDIQ_ID,
        self::PRODUCT_CURRENCY => self::PRODUCT_CURRENCY,
        self::PRODUCT_VAT_PERCENT =>self::PRODUCT_VAT_PERCENT,
        self::PRODUCT_PORTION =>self::PRODUCT_PORTION,
        self::PRODUCT_COMPARISON_UNIT => self::PRODUCT_COMPARISON_UNIT,
        self::LOCATION => self::ARTICLE_LOCATION,
    );

    /**
     * Article specific field mapping
     * @var array
     */
    private $articleFields = array(
        self::ARTICLE_NAME => self::ARTICLE_NAME,
        self::SKU => self::ARTICLE_SKU,
        self::QUANTITY => self::ARTICLE_QUANTITY,
        self::LOCATION => self::ARTICLE_LOCATION,
        self::ARTICLE_IDENTIFIER_TYPE => self::ARTICLE_IDENTIFIER_TYPE,
        self::ARTICLE_IDENTIFIER_TYPE => self::ARTICLE_IDENTIFIER_VALUE,
        self::ARTICLE_EAN => self::ARTICLE_EAN,
        self::ARTICLE_ISBN => self::ARTICLE_ISBN,
        self::ARTICLE_MPN => self::ARTICLE_MPN,
    );

    protected $tagsReplace = array(
        'div',
        'li',
        'p',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'blockquote',
    );

    protected $tagsRegexp = '';

    /**
     * Constructor
     *
     * @param resource $stream File handler for the stream
     */
    public function __construct($stream, $onlyInit = false)
    {
        parent::__construct($stream);
        // Add image columns
        for ($i = 1; $i <= FyndiqUtils::NUMBER_OF_ALLOWED_IMAGES; $i++) {
            $this->header[] = sprintf(self::PRODUCT_IMAGE_URL_TEMPLATE, $i);
            $this->header[] = sprintf(self::PRODUCT_IMAGE_IDENTIFIER_TEMPLATE, $i);
        }
        // Add property columns
        for ($i = 1; $i <= FyndiqUtils::NUMBER_OF_ALLOWED_PROPERTIES; $i++) {
            $this->header[] = sprintf(self::ARTICLE_PROPERTY_NAME_TEMPLATE, $i);
            $this->header[] = sprintf(self::ARTICLE_PROPERTY_VALUE_TEMPLATE, $i);
        }
        $this->tagsRegexp = $this->getTagRegexp($this->tagsReplace);
        if (!$onlyInit) {
            // Write header
            $this->writeRow($this->header);
        }
    }

    /**
     * getTagRegexp returns RegExp to match tags to be fixed
     *
     * @param  array $tags
     * @return string
     */
    protected function getTagRegexp($tags)
    {
        $elements = array();
        foreach ($tags as $tag) {
            $elements[] = '</' . $tag. '>';
        }
        return '#(' . implode('|', $elements) .')([^\n])#i';
    }


    protected function writeRow($row)
    {
        return fputcsv($this->stream, $row);
    }

    /**
     * cleanTextField cleans text field from HTML
     *
     * @param  string $text
     * @return string
     */
    public function clearTextField($text)
    {
        return trim(strip_tags($text));
    }

    /**
     * clearDescription clears and normalizes description text
     *
     * @param  string $text
     * @return string
     */
    public function clearDescription($text)
    {
        //Fix one line HTML
        $text = preg_replace($this->tagsRegexp, "$1\n$2", $text);
        // Replace <br> tags with new lines
        $text = preg_replace('/<[h|b]r[^>]*>/i', "\n", $text);
        $text =  $this->clearTextField($text);
        // Normalize space
        $text = str_replace("\r", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return $text;
    }

    /**
     * Apply field transformations
     *
     * @param array $product
     * @return array mixed
     */
    private function processProduct($product)
    {
        foreach ($this->textColumns as $field) {
            if (!empty($product[$field])) {
                if ($field === self::PRODUCT_DESCRIPTION) {
                    $product[$field] = $this->clearDescription($product[$field]);
                }
                $product[$field] = $this->clearTextField($product[$field]);
            }
        }
        $fullProduct = array();
        foreach ($this->header as $field) {
            $fullProduct[$field] = isset($product[$field]) ? $product[$field] : '';
        }
        return $fullProduct;
    }

    /**
     * Add product to the feed
     *
     * @param array $product assoc. array containing the product data
     * @param bool $resetErrors optional reset errors before adding the product
     * @return bool
     */
    public function addProduct($product, $resetErrors = true)
    {
        if ($resetErrors) {
            $this->resetErrors();
        }
        if ($this->isValidProduct($product)) {
            $product = $this->sanitizeProduct($product);
            return $this->writeRow($this->processProduct($product));
        }
        return false;
    }

    /**
     * getImages returns list of image columns for the feed
     *
     * @param  array  $images array containing image URLs
     * @param  integer  $limit Maximum allowed number of images per article
     * @param  integer $imageId Optional starting image index
     * @return array
     */
    public function getImages($images, $limit, $imageId = 1)
    {
        $result = array();
        foreach ($images as $image) {
            if ($imageId > $limit) {
                return $result;
            }
            $result[sprintf(self::PRODUCT_IMAGE_URL_TEMPLATE, $imageId)] = $image;
            $result[sprintf(self::PRODUCT_IMAGE_IDENTIFIER_TEMPLATE, $imageId)] = substr(md5($image), 0, 10);
            $imageId++;
        }
        return $result;
    }

    /**
     * getProperties returns list of properties for the feed
     *
     * @param  array  $properties assoc. array containing properties
     * @param  integer  $limit Maximum allowed number of properties per article
     * @param  integer $propertyId Optional starting property index
     * @return array
     */
    public function getProperties($properties, $limit, $propertyId = 1)
    {
        $result = array();
        foreach ($properties as $property) {
            if ($propertyId > $limit) {
                return $result;
            }
            $result[sprintf(self::ARTICLE_PROPERTY_NAME_TEMPLATE, $propertyId)] =
                self::shorten($property[self::PROPERTY_NAME], 64);
            $result[sprintf(self::ARTICLE_PROPERTY_VALUE_TEMPLATE, $propertyId)] =
                self::shorten($property[self::PROPERTY_VALUE], 64);
            $propertyId++;
        }
        return $result;
    }

    /**
     * getArticleName returns Article name
     *
     * @param  array $feedProduct
     * @param  array $article
     * @param  bool $articleAsProduct
     * @return string
     */
    public function getArticleName($feedProduct, $article, $articleAsProduct)
    {
        if ($articleAsProduct && isset($article[self::ARTICLE_NAME]) && $article[self::ARTICLE_NAME]) {
            return $article[self::ARTICLE_NAME];
        }
        // Generate article name from article properties;
        if (isset($article[self::PROPERTIES]) && $article[self::PROPERTIES]) {
            $fromProperties = $this->getArticleNameFromProperties($article[self::PROPERTIES]);
            if ($fromProperties) {
                return $fromProperties;
            }
        }
        // Fallback to product title
        return $feedProduct[self::PRODUCT_TITLE];
    }

    /**
     * getArticleNameFromProperties generates article name based on the article properties
     *
     * @param  array $properties assoc. array containing properties
     * @return string
     */
    protected function getArticleNameFromProperties($properties)
    {
        $productName = array();
        foreach ($properties as $property) {
            $productName[] = $property[self::PROPERTY_NAME] . ': ' . $property[self::PROPERTY_VALUE];
        }
        return implode(', ', $productName);
    }

    /**
     * getExportSimpleProduct returns simple product (no articles)
     *
     * @param  array $feedProduct basic simple product data
     * @param  array $product product data
     * @return array
     */
    protected function getExportSimpleProduct($feedProduct, $product)
    {
        // No article, fill the required article fields
        $articleFields = array(
            self::PRODUCT_TITLE => self::ARTICLE_NAME,
            self::SKU => self::ARTICLE_SKU,
            self::QUANTITY => self::ARTICLE_QUANTITY
        );
        foreach ($articleFields as $columnName => $exportName) {
            if (isset($product[$columnName])) {
                $feedProduct[$exportName] = $product[$columnName];
            }
        }
        // Add Images
        if (isset($product[self::IMAGES])) {
            $feedProduct = array_merge(
                $feedProduct,
                $this->getImages($product[self::IMAGES], FyndiqUtils::NUMBER_OF_ALLOWED_IMAGES)
            );
        }
        // Add Properties
        if (isset($product[self::PROPERTIES])) {
            $feedProduct = array_merge($feedProduct, $this->getProperties(
                $product[self::PROPERTIES],
                FyndiqUtils::NUMBER_OF_ALLOWED_PROPERTIES
            ));
        }
        return $feedProduct;
    }

    /**
     * getExportProduct returns export product array
     *
     * @param  array   $product product data
     * @param  array   $article article data
     * @param  boolean $articleAsProduct weather article is part of a split
     * @return array
     */
    protected function getExportProduct($product, $article = array(), $articleAsProduct = false)
    {
        $feedProduct = array();
        $productImages = isset($product[self::IMAGES]) ? $product[self::IMAGES] : array();
        $articleImages = isset($article[self::IMAGES]) ? $article[self::IMAGES] : array();

        // Fill ProductData
        foreach ($this->productFields as $columnName => $exportName) {
            if (isset($product[$columnName])) {
                // Prices must be formatted
                if (in_array($columnName, array(self::PRICE, self::OLDPRICE))) {
                    $feedProduct[$exportName] = FyndiqUtils::formatPrice($product[$columnName]);
                    continue;
                }
                $feedProduct[$exportName] = $product[$columnName];
            }
        }
        if (empty($article)) {
            return $this->getExportSimpleProduct($feedProduct, $product);
        }

        // Add article data
        foreach ($this->articleFields as $columnName => $exportName) {
            if (isset($article[$columnName])) {
                $feedProduct[$exportName] = $article[$columnName];
            }
        }

        $feedProduct[self::ARTICLE_NAME] = $this->getArticleName($feedProduct, $article, $articleAsProduct);
        if ($articleAsProduct) {
            $feedProduct[self::PRODUCT_TITLE] = $feedProduct[self::ARTICLE_NAME];
        }

        if (isset($article[self::PROPERTIES])) {
            // Add article properties
            $feedProduct = array_merge($feedProduct, $this->getProperties(
                $article[self::PROPERTIES],
                FyndiqUtils::NUMBER_OF_ALLOWED_PROPERTIES
            ));
        }
        if ($articleAsProduct) {
            // New product ID
            $feedProduct[self::PRODUCT_ID] = $product[self::ID] . '-' . $article[self::ID];
            // New prices
            $feedProduct[self::PRODUCT_PRICE] = FyndiqUtils::formatPrice($article[self::PRICE]);
            $feedProduct[self::PRODUCT_OLDPRICE] = FyndiqUtils::formatPrice($article[self::OLDPRICE]);
            $feedProduct = array_merge(
                $feedProduct,
                $this->getImages(
                    array_unique(array_merge($articleImages, $productImages)),
                    FyndiqUtils::NUMBER_OF_ALLOWED_IMAGES
                )
            );
            return $feedProduct;
        }
        $feedProduct = array_merge(
            $feedProduct,
            $this->getImages(
                array_unique(array_merge($productImages, $articleImages)),
                FyndiqUtils::NUMBER_OF_ALLOWED_IMAGES
            )
        );
        return $feedProduct;
    }

    /**
     * isSplitArticles checks if articles have to be split into products
     *
     * @param  array  $articles array with articles
     * @return boolean
     */
    protected function isSplitArticles($articles)
    {
        $prices = array();
        foreach ($articles as $article) {
            $prices[] = FyndiqUtils::formatPrice($article[self::PRICE]);
        }
        return count(array_unique($prices)) !== 1;
    }

    /**
     * getCombinedImages returns combined list of all images
     *
     * @param  array $product
     * @param  array $article
     * @return array
     */
    protected function getCombinedImages($product, $articles)
    {
        $images = isset($product[self::IMAGES]) ? $product[self::IMAGES] : array();
        foreach ($articles as $article) {
            $articleImages = isset($article[self::IMAGES]) ? $article[self::IMAGES] : array();
            $images = array_merge($images, $articleImages);
        }
        return array_unique($images);
    }

    /**
     * addCompleteProduct adds product and its articles to the feed
     *
     * @param array $product product data
     * @param array  $articles array of articles
     */
    public function addCompleteProduct($product, $articles = array())
    {
        $this->resetErrors();
        if (is_array($product) && empty($articles)) {
            $exportProduct = $this->getExportProduct($product);
            return $this->addProduct($exportProduct, false);
        }
        if (is_array($articles)) {
            $result = true;
            $splitArticles = $this->isSplitArticles($articles);
            if (!$splitArticles) {
                $product[self::IMAGES] = $this->getCombinedImages($product, $articles);
            }
            if (!$splitArticles && count($articles) > 0) {
                //If there is one article, use its price for export
                $product[self::PRICE] = $articles[0][self::PRICE];
                $product[self::OLDPRICE] = $articles[0][self::OLDPRICE];
            }
            foreach ($articles as $article) {
                $exportProduct = $this->getExportProduct($product, $article, $splitArticles);
                $result &= $this->addProduct($exportProduct, false);
            }
            return (bool)$result;
        }
        return false;
    }

    /**
     * Flush the feed data to the stream
     *
     * @return bool
     */
    public function write()
    {
        return true;
    }

    public function getHeader()
    {
        return $this->header;
    }
}
