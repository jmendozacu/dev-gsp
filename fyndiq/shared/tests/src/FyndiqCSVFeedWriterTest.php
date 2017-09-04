<?php

class FmFileHandlerTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $this->feedWriter = $this->getMockBuilder('FyndiqCSVFeedWriter')
            ->setConstructorArgs(array(null))
            ->setMethods(array('writeRow'))
            ->getMock();
        $this->feedWriter->method('writeRow')
            ->willReturn(true);
    }



    private function getStream($stream)
    {
        $result = '';
        rewind($stream);
        while (!feof($stream)) {
            $result .= fread($stream, 1024);
        }
        return $result;
    }

    function testFeedWriteProvider()
    {
        return array(
            array(
                'One invalid product',
                fopen('php://memory', 'rw+'),
                array(
                    array(
                        'one' => 1
                    )
                ),
                array(
                     'Missing required column `product-id`'
                ),
                true,
                'product-id,product-paused,product-price,product-oldprice,product-market,product-title,product-description,product-brand-name,product-category-id,product-category-name,product-category-fyndiq-id,product-currency,product-vat-percent,product-portion,product-comparison-unit,article-name,article-sku,article-quantity,article-location,article-identifier-type,article-identifier-value,article-ean,article-isbn,article-mpn,product-image-1-url,product-image-1-identifier,product-image-2-url,product-image-2-identifier,product-image-3-url,product-image-3-identifier,product-image-4-url,product-image-4-identifier,product-image-5-url,product-image-5-identifier,product-image-6-url,product-image-6-identifier,product-image-7-url,product-image-7-identifier,product-image-8-url,product-image-8-identifier,product-image-9-url,product-image-9-identifier,product-image-10-url,product-image-10-identifier,article-property-1-name,article-property-1-value,article-property-2-name,article-property-2-value,article-property-3-name,article-property-3-value,article-property-4-name,article-property-4-value,article-property-5-name,article-property-5-value,article-property-6-name,article-property-6-value,article-property-7-name,article-property-7-value,article-property-8-name,article-property-8-value,article-property-9-name,article-property-9-value,article-property-10-name,article-property-10-value,article-property-11-name,article-property-11-value,article-property-12-name,article-property-12-value,article-property-13-name,article-property-13-value,article-property-14-name,article-property-14-value,article-property-15-name,article-property-15-value,article-property-16-name,article-property-16-value,article-property-17-name,article-property-17-value,article-property-18-name,article-property-18-value,article-property-19-name,article-property-19-value,article-property-20-name,article-property-20-value,article-property-21-name,article-property-21-value,article-property-22-name,article-property-22-value,article-property-23-name,article-property-23-value,article-property-24-name,article-property-24-value,article-property-25-name,article-property-25-value,article-property-26-name,article-property-26-value,article-property-27-name,article-property-27-value,article-property-28-name,article-property-28-value,article-property-29-name,article-property-29-value,article-property-30-name,article-property-30-value,article-property-31-name,article-property-31-value,article-property-32-name,article-property-32-value,article-property-33-name,article-property-33-value,article-property-34-name,article-property-34-value,article-property-35-name,article-property-35-value,article-property-36-name,article-property-36-value,article-property-37-name,article-property-37-value,article-property-38-name,article-property-38-value,article-property-39-name,article-property-39-value,article-property-40-name,article-property-40-value,article-property-41-name,article-property-41-value,article-property-42-name,article-property-42-value,article-property-43-name,article-property-43-value,article-property-44-name,article-property-44-value,article-property-45-name,article-property-45-value,article-property-46-name,article-property-46-value,article-property-47-name,article-property-47-value,article-property-48-name,article-property-48-value,article-property-49-name,article-property-49-value,article-property-50-name,article-property-50-value
'
            ),
            array(
                'One valid product',
                fopen('php://memory', 'rw+'),
                array(
                    array(
                        'product-id' => 1,
                        'product-image-1-url' => 1,
                        'product-image-1-identifier' => 1,
                        'product-title' => 12345,
                        'product-market' => 1,
                        'product-description' => 1234567890,
                        'product-price' => 1,
                        'product-oldprice' => 1,
                        'product-currency' => 1,
                        'product-vat-percent' => 7,
                        'article-quantity' => 1,
                        'article-sku' => 1,
                        'article-name' => 1,
                    )
                ),
                array(),
                true,
                'product-id,product-paused,product-price,product-oldprice,product-market,product-title,product-description,product-brand-name,product-category-id,product-category-name,product-category-fyndiq-id,product-currency,product-vat-percent,product-portion,product-comparison-unit,article-name,article-sku,article-quantity,article-location,article-identifier-type,article-identifier-value,article-ean,article-isbn,article-mpn,product-image-1-url,product-image-1-identifier,product-image-2-url,product-image-2-identifier,product-image-3-url,product-image-3-identifier,product-image-4-url,product-image-4-identifier,product-image-5-url,product-image-5-identifier,product-image-6-url,product-image-6-identifier,product-image-7-url,product-image-7-identifier,product-image-8-url,product-image-8-identifier,product-image-9-url,product-image-9-identifier,product-image-10-url,product-image-10-identifier,article-property-1-name,article-property-1-value,article-property-2-name,article-property-2-value,article-property-3-name,article-property-3-value,article-property-4-name,article-property-4-value,article-property-5-name,article-property-5-value,article-property-6-name,article-property-6-value,article-property-7-name,article-property-7-value,article-property-8-name,article-property-8-value,article-property-9-name,article-property-9-value,article-property-10-name,article-property-10-value,article-property-11-name,article-property-11-value,article-property-12-name,article-property-12-value,article-property-13-name,article-property-13-value,article-property-14-name,article-property-14-value,article-property-15-name,article-property-15-value,article-property-16-name,article-property-16-value,article-property-17-name,article-property-17-value,article-property-18-name,article-property-18-value,article-property-19-name,article-property-19-value,article-property-20-name,article-property-20-value,article-property-21-name,article-property-21-value,article-property-22-name,article-property-22-value,article-property-23-name,article-property-23-value,article-property-24-name,article-property-24-value,article-property-25-name,article-property-25-value,article-property-26-name,article-property-26-value,article-property-27-name,article-property-27-value,article-property-28-name,article-property-28-value,article-property-29-name,article-property-29-value,article-property-30-name,article-property-30-value,article-property-31-name,article-property-31-value,article-property-32-name,article-property-32-value,article-property-33-name,article-property-33-value,article-property-34-name,article-property-34-value,article-property-35-name,article-property-35-value,article-property-36-name,article-property-36-value,article-property-37-name,article-property-37-value,article-property-38-name,article-property-38-value,article-property-39-name,article-property-39-value,article-property-40-name,article-property-40-value,article-property-41-name,article-property-41-value,article-property-42-name,article-property-42-value,article-property-43-name,article-property-43-value,article-property-44-name,article-property-44-value,article-property-45-name,article-property-45-value,article-property-46-name,article-property-46-value,article-property-47-name,article-property-47-value,article-property-48-name,article-property-48-value,article-property-49-name,article-property-49-value,article-property-50-name,article-property-50-value
1,,1,1,1,12345,1234567890,,,,,1,7,,,1,1,1,,,,,,,1,1,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
'
            ),
            array(
                'One valid and two invalid products',
                fopen('php://memory', 'rw+'),
                array(
                    array(
                        'product-id' => 1,
                        'product-image-1-url' => 2,
                        'product-image-1-identifier' => 3,
                        'product-title' => 456789,
                        'product-market' => 5,
                        'product-description' => 6789012345,
                        'product-price' => 7,
                        'product-oldprice' => 8,
                        'product-currency' => 9,
                        'product-vat-percent' => 19,
                        'article-quantity' => 11,
                        'article-sku' => 12,
                        'article-name' => 13,
                    ),
                    array(
                        'no way' => 'this is valid'
                    ),
                    2
                ),
                array(
                    'Missing required column `product-id`',
                    'Passed product is not array'
                ),
                true,
                'product-id,product-paused,product-price,product-oldprice,product-market,product-title,product-description,product-brand-name,product-category-id,product-category-name,product-category-fyndiq-id,product-currency,product-vat-percent,product-portion,product-comparison-unit,article-name,article-sku,article-quantity,article-location,article-identifier-type,article-identifier-value,article-ean,article-isbn,article-mpn,product-image-1-url,product-image-1-identifier,product-image-2-url,product-image-2-identifier,product-image-3-url,product-image-3-identifier,product-image-4-url,product-image-4-identifier,product-image-5-url,product-image-5-identifier,product-image-6-url,product-image-6-identifier,product-image-7-url,product-image-7-identifier,product-image-8-url,product-image-8-identifier,product-image-9-url,product-image-9-identifier,product-image-10-url,product-image-10-identifier,article-property-1-name,article-property-1-value,article-property-2-name,article-property-2-value,article-property-3-name,article-property-3-value,article-property-4-name,article-property-4-value,article-property-5-name,article-property-5-value,article-property-6-name,article-property-6-value,article-property-7-name,article-property-7-value,article-property-8-name,article-property-8-value,article-property-9-name,article-property-9-value,article-property-10-name,article-property-10-value,article-property-11-name,article-property-11-value,article-property-12-name,article-property-12-value,article-property-13-name,article-property-13-value,article-property-14-name,article-property-14-value,article-property-15-name,article-property-15-value,article-property-16-name,article-property-16-value,article-property-17-name,article-property-17-value,article-property-18-name,article-property-18-value,article-property-19-name,article-property-19-value,article-property-20-name,article-property-20-value,article-property-21-name,article-property-21-value,article-property-22-name,article-property-22-value,article-property-23-name,article-property-23-value,article-property-24-name,article-property-24-value,article-property-25-name,article-property-25-value,article-property-26-name,article-property-26-value,article-property-27-name,article-property-27-value,article-property-28-name,article-property-28-value,article-property-29-name,article-property-29-value,article-property-30-name,article-property-30-value,article-property-31-name,article-property-31-value,article-property-32-name,article-property-32-value,article-property-33-name,article-property-33-value,article-property-34-name,article-property-34-value,article-property-35-name,article-property-35-value,article-property-36-name,article-property-36-value,article-property-37-name,article-property-37-value,article-property-38-name,article-property-38-value,article-property-39-name,article-property-39-value,article-property-40-name,article-property-40-value,article-property-41-name,article-property-41-value,article-property-42-name,article-property-42-value,article-property-43-name,article-property-43-value,article-property-44-name,article-property-44-value,article-property-45-name,article-property-45-value,article-property-46-name,article-property-46-value,article-property-47-name,article-property-47-value,article-property-48-name,article-property-48-value,article-property-49-name,article-property-49-value,article-property-50-name,article-property-50-value
1,,7,8,5,456789,6789012345,,,,,9,19,,,13,12,11,,,,,,,2,3,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
'
            ),
            array(
                'One valid product',
                fopen('php://memory', 'rw+'),
                array(
                    array(
                        "product-id" => "14",
                        "product-title" => "Skolplansch XL Tulpaner",
                        "product-description" => 1234567890,
                        "product-price" => "289.10",
                        "product-vat-percent" => 25,
                        "product-oldprice" => "490.00",
                        "product-market" => "SE",
                        "product-currency" => "SEK",
                        "product-brand-name" => "Unknown",
                        "product-category-name" => "Interiör",
                        "product-category-id" => "8",
                        "product-image-1-url" => "http://odd-living.com/media/catalog/product/cache/1/image/9df78eab33525d08d6e5fb8d27136e95/s/k/skolplansch_skolplanscher_skolaffisch_tulpaner.jpg",
                        "product-image-1-identifier" => "991c7fcc2a",
                        "article-quantity" => 13,
                        "article-location" => "Unknown",
                        "article-sku" => "SKOL-S4",
                        "article-name" => "Skolplansch XL Tulpaner",
                    )
                ),
                array(),
                true,
                'product-id,product-paused,product-price,product-oldprice,product-market,product-title,product-description,product-brand-name,product-category-id,product-category-name,product-category-fyndiq-id,product-currency,product-vat-percent,product-portion,product-comparison-unit,article-name,article-sku,article-quantity,article-location,article-identifier-type,article-identifier-value,article-ean,article-isbn,article-mpn,product-image-1-url,product-image-1-identifier,product-image-2-url,product-image-2-identifier,product-image-3-url,product-image-3-identifier,product-image-4-url,product-image-4-identifier,product-image-5-url,product-image-5-identifier,product-image-6-url,product-image-6-identifier,product-image-7-url,product-image-7-identifier,product-image-8-url,product-image-8-identifier,product-image-9-url,product-image-9-identifier,product-image-10-url,product-image-10-identifier,article-property-1-name,article-property-1-value,article-property-2-name,article-property-2-value,article-property-3-name,article-property-3-value,article-property-4-name,article-property-4-value,article-property-5-name,article-property-5-value,article-property-6-name,article-property-6-value,article-property-7-name,article-property-7-value,article-property-8-name,article-property-8-value,article-property-9-name,article-property-9-value,article-property-10-name,article-property-10-value,article-property-11-name,article-property-11-value,article-property-12-name,article-property-12-value,article-property-13-name,article-property-13-value,article-property-14-name,article-property-14-value,article-property-15-name,article-property-15-value,article-property-16-name,article-property-16-value,article-property-17-name,article-property-17-value,article-property-18-name,article-property-18-value,article-property-19-name,article-property-19-value,article-property-20-name,article-property-20-value,article-property-21-name,article-property-21-value,article-property-22-name,article-property-22-value,article-property-23-name,article-property-23-value,article-property-24-name,article-property-24-value,article-property-25-name,article-property-25-value,article-property-26-name,article-property-26-value,article-property-27-name,article-property-27-value,article-property-28-name,article-property-28-value,article-property-29-name,article-property-29-value,article-property-30-name,article-property-30-value,article-property-31-name,article-property-31-value,article-property-32-name,article-property-32-value,article-property-33-name,article-property-33-value,article-property-34-name,article-property-34-value,article-property-35-name,article-property-35-value,article-property-36-name,article-property-36-value,article-property-37-name,article-property-37-value,article-property-38-name,article-property-38-value,article-property-39-name,article-property-39-value,article-property-40-name,article-property-40-value,article-property-41-name,article-property-41-value,article-property-42-name,article-property-42-value,article-property-43-name,article-property-43-value,article-property-44-name,article-property-44-value,article-property-45-name,article-property-45-value,article-property-46-name,article-property-46-value,article-property-47-name,article-property-47-value,article-property-48-name,article-property-48-value,article-property-49-name,article-property-49-value,article-property-50-name,article-property-50-value
14,,289.10,490.00,SE,"Skolplansch XL Tulpaner",1234567890,Unknown,8,Interiör,,SEK,25,,,"Skolplansch XL Tulpaner",SKOL-S4,13,Unknown,,,,,,http://odd-living.com/media/catalog/product/cache/1/image/9df78eab33525d08d6e5fb8d27136e95/s/k/skolplansch_skolplanscher_skolaffisch_tulpaner.jpg,991c7fcc2a,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
'
            ),
            array(
                'Another valid product',
                fopen('php://memory', 'rw+'),
                array(
                    array(
                        "product-id" => "14",
                        "product-title" => "Skolplansch <b>XL Tulpaner</b>",
                        "product-description" => '<script>alert(0)</script>',
                        "product-price" => "289.10",
                        "product-vat-percent" => 25,
                        "product-oldprice" => "490.00",
                        "product-market" => "SE",
                        "product-currency" => "SEK",
                        "product-brand-name" => '<a href="#">Unknown</a>',
                        "product-category-name" => "Interiör",
                        "product-category-id" => "8",
                        "product-image-1-url" => "http://odd-living.com/media/catalog/product/cache/1/image/9df78eab33525d08d6e5fb8d27136e95/s/k/skolplansch_skolplanscher_skolaffisch_tulpaner.jpg",
                        "product-image-1-identifier" => "991c7fcc2a",
                        "article-quantity" => 13,
                        "article-location" => "Unknown",
                        "article-sku" => "SKOL-S4",
                        "article-name" => "Skolplansch <i>XL Tulpaner</i>",
                    )
                ),
                array(),
                true,
                'product-id,product-paused,product-price,product-oldprice,product-market,product-title,product-description,product-brand-name,product-category-id,product-category-name,product-category-fyndiq-id,product-currency,product-vat-percent,product-portion,product-comparison-unit,article-name,article-sku,article-quantity,article-location,article-identifier-type,article-identifier-value,article-ean,article-isbn,article-mpn,product-image-1-url,product-image-1-identifier,product-image-2-url,product-image-2-identifier,product-image-3-url,product-image-3-identifier,product-image-4-url,product-image-4-identifier,product-image-5-url,product-image-5-identifier,product-image-6-url,product-image-6-identifier,product-image-7-url,product-image-7-identifier,product-image-8-url,product-image-8-identifier,product-image-9-url,product-image-9-identifier,product-image-10-url,product-image-10-identifier,article-property-1-name,article-property-1-value,article-property-2-name,article-property-2-value,article-property-3-name,article-property-3-value,article-property-4-name,article-property-4-value,article-property-5-name,article-property-5-value,article-property-6-name,article-property-6-value,article-property-7-name,article-property-7-value,article-property-8-name,article-property-8-value,article-property-9-name,article-property-9-value,article-property-10-name,article-property-10-value,article-property-11-name,article-property-11-value,article-property-12-name,article-property-12-value,article-property-13-name,article-property-13-value,article-property-14-name,article-property-14-value,article-property-15-name,article-property-15-value,article-property-16-name,article-property-16-value,article-property-17-name,article-property-17-value,article-property-18-name,article-property-18-value,article-property-19-name,article-property-19-value,article-property-20-name,article-property-20-value,article-property-21-name,article-property-21-value,article-property-22-name,article-property-22-value,article-property-23-name,article-property-23-value,article-property-24-name,article-property-24-value,article-property-25-name,article-property-25-value,article-property-26-name,article-property-26-value,article-property-27-name,article-property-27-value,article-property-28-name,article-property-28-value,article-property-29-name,article-property-29-value,article-property-30-name,article-property-30-value,article-property-31-name,article-property-31-value,article-property-32-name,article-property-32-value,article-property-33-name,article-property-33-value,article-property-34-name,article-property-34-value,article-property-35-name,article-property-35-value,article-property-36-name,article-property-36-value,article-property-37-name,article-property-37-value,article-property-38-name,article-property-38-value,article-property-39-name,article-property-39-value,article-property-40-name,article-property-40-value,article-property-41-name,article-property-41-value,article-property-42-name,article-property-42-value,article-property-43-name,article-property-43-value,article-property-44-name,article-property-44-value,article-property-45-name,article-property-45-value,article-property-46-name,article-property-46-value,article-property-47-name,article-property-47-value,article-property-48-name,article-property-48-value,article-property-49-name,article-property-49-value,article-property-50-name,article-property-50-value
14,,289.10,490.00,SE,"Skolplansch XL Tulpaner",alert(0),Unknown,8,Interiör,,SEK,25,,,"Skolplansch XL Tulpaner",SKOL-S4,13,Unknown,,,,,,http://odd-living.com/media/catalog/product/cache/1/image/9df78eab33525d08d6e5fb8d27136e95/s/k/skolplansch_skolplanscher_skolaffisch_tulpaner.jpg,991c7fcc2a,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
'
            ),
            array(
                'Bad VAT',
                fopen('php://memory', 'rw+'),
                array(
                    array(
                        'product-id' => 1,
                        'product-image-1-url' => 1,
                        'product-image-1-identifier' => 1,
                        'product-title' => 1,
                        'product-market' => 1,
                        'product-description' => 1,
                        'product-price' => 1,
                        'product-oldprice' => 1,
                        'product-currency' => 1,
                        'product-vat-percent' => 1,
                        'article-quantity' => 1,
                        'article-sku' => 1,
                        'article-name' => 1,
                    )
                ),
                array(
                    'Incorrect VAT = 1'
                ),
                true,
                'product-id,product-paused,product-price,product-oldprice,product-market,product-title,product-description,product-brand-name,product-category-id,product-category-name,product-category-fyndiq-id,product-currency,product-vat-percent,product-portion,product-comparison-unit,article-name,article-sku,article-quantity,article-location,article-identifier-type,article-identifier-value,article-ean,article-isbn,article-mpn,product-image-1-url,product-image-1-identifier,product-image-2-url,product-image-2-identifier,product-image-3-url,product-image-3-identifier,product-image-4-url,product-image-4-identifier,product-image-5-url,product-image-5-identifier,product-image-6-url,product-image-6-identifier,product-image-7-url,product-image-7-identifier,product-image-8-url,product-image-8-identifier,product-image-9-url,product-image-9-identifier,product-image-10-url,product-image-10-identifier,article-property-1-name,article-property-1-value,article-property-2-name,article-property-2-value,article-property-3-name,article-property-3-value,article-property-4-name,article-property-4-value,article-property-5-name,article-property-5-value,article-property-6-name,article-property-6-value,article-property-7-name,article-property-7-value,article-property-8-name,article-property-8-value,article-property-9-name,article-property-9-value,article-property-10-name,article-property-10-value,article-property-11-name,article-property-11-value,article-property-12-name,article-property-12-value,article-property-13-name,article-property-13-value,article-property-14-name,article-property-14-value,article-property-15-name,article-property-15-value,article-property-16-name,article-property-16-value,article-property-17-name,article-property-17-value,article-property-18-name,article-property-18-value,article-property-19-name,article-property-19-value,article-property-20-name,article-property-20-value,article-property-21-name,article-property-21-value,article-property-22-name,article-property-22-value,article-property-23-name,article-property-23-value,article-property-24-name,article-property-24-value,article-property-25-name,article-property-25-value,article-property-26-name,article-property-26-value,article-property-27-name,article-property-27-value,article-property-28-name,article-property-28-value,article-property-29-name,article-property-29-value,article-property-30-name,article-property-30-value,article-property-31-name,article-property-31-value,article-property-32-name,article-property-32-value,article-property-33-name,article-property-33-value,article-property-34-name,article-property-34-value,article-property-35-name,article-property-35-value,article-property-36-name,article-property-36-value,article-property-37-name,article-property-37-value,article-property-38-name,article-property-38-value,article-property-39-name,article-property-39-value,article-property-40-name,article-property-40-value,article-property-41-name,article-property-41-value,article-property-42-name,article-property-42-value,article-property-43-name,article-property-43-value,article-property-44-name,article-property-44-value,article-property-45-name,article-property-45-value,article-property-46-name,article-property-46-value,article-property-47-name,article-property-47-value,article-property-48-name,article-property-48-value,article-property-49-name,article-property-49-value,article-property-50-name,article-property-50-value
'
            ),
            array(
                'Short columns',
                fopen('php://memory', 'rw+'),
                array(
                    array(
                        'product-id' => 1,
                        'product-image-1-url' => 1,
                        'product-image-1-identifier' => 1,
                        'product-title' => 1,
                        'product-market' => 1,
                        'product-description' => 1,
                        'product-price' => 1,
                        'product-oldprice' => 1,
                        'product-currency' => 1,
                        'product-vat-percent' => 7,
                        'article-quantity' => 1,
                        'article-sku' => 1,
                        'article-name' => 1,
                    )
                ),
                array(
                    'Value too short for `product-title` expected length: `5`, sent `1`'
                ),
                true,
                'product-id,product-paused,product-price,product-oldprice,product-market,product-title,product-description,product-brand-name,product-category-id,product-category-name,product-category-fyndiq-id,product-currency,product-vat-percent,product-portion,product-comparison-unit,article-name,article-sku,article-quantity,article-location,article-identifier-type,article-identifier-value,article-ean,article-isbn,article-mpn,product-image-1-url,product-image-1-identifier,product-image-2-url,product-image-2-identifier,product-image-3-url,product-image-3-identifier,product-image-4-url,product-image-4-identifier,product-image-5-url,product-image-5-identifier,product-image-6-url,product-image-6-identifier,product-image-7-url,product-image-7-identifier,product-image-8-url,product-image-8-identifier,product-image-9-url,product-image-9-identifier,product-image-10-url,product-image-10-identifier,article-property-1-name,article-property-1-value,article-property-2-name,article-property-2-value,article-property-3-name,article-property-3-value,article-property-4-name,article-property-4-value,article-property-5-name,article-property-5-value,article-property-6-name,article-property-6-value,article-property-7-name,article-property-7-value,article-property-8-name,article-property-8-value,article-property-9-name,article-property-9-value,article-property-10-name,article-property-10-value,article-property-11-name,article-property-11-value,article-property-12-name,article-property-12-value,article-property-13-name,article-property-13-value,article-property-14-name,article-property-14-value,article-property-15-name,article-property-15-value,article-property-16-name,article-property-16-value,article-property-17-name,article-property-17-value,article-property-18-name,article-property-18-value,article-property-19-name,article-property-19-value,article-property-20-name,article-property-20-value,article-property-21-name,article-property-21-value,article-property-22-name,article-property-22-value,article-property-23-name,article-property-23-value,article-property-24-name,article-property-24-value,article-property-25-name,article-property-25-value,article-property-26-name,article-property-26-value,article-property-27-name,article-property-27-value,article-property-28-name,article-property-28-value,article-property-29-name,article-property-29-value,article-property-30-name,article-property-30-value,article-property-31-name,article-property-31-value,article-property-32-name,article-property-32-value,article-property-33-name,article-property-33-value,article-property-34-name,article-property-34-value,article-property-35-name,article-property-35-value,article-property-36-name,article-property-36-value,article-property-37-name,article-property-37-value,article-property-38-name,article-property-38-value,article-property-39-name,article-property-39-value,article-property-40-name,article-property-40-value,article-property-41-name,article-property-41-value,article-property-42-name,article-property-42-value,article-property-43-name,article-property-43-value,article-property-44-name,article-property-44-value,article-property-45-name,article-property-45-value,article-property-46-name,article-property-46-value,article-property-47-name,article-property-47-value,article-property-48-name,article-property-48-value,article-property-49-name,article-property-49-value,article-property-50-name,article-property-50-value
'
            ),
        );
    }

    /**
     * Test Feed Writer
     *
     * @param string $message
     * @param resource $stream
     * @param array $products
     * @param bool $writeResult
     * @param string $expected
     * @dataProvider testFeedWriteProvider
     */
    public function testFeedWrite($message, $stream, $products, $errors, $writeResult, $expected)
    {
        $this->feedWriter = new FyndiqCSVFeedWriter($stream);
        foreach ($products as $product) {
            $this->feedWriter->addProduct($product, false);
        }
        $result = $this->feedWriter->write();
        $this->assertEquals($errors, $this->feedWriter->getLastProductErrors(), $message);
        $this->assertEquals($writeResult, $result, $message);
        $this->assertEquals($expected, $this->getStream($stream), $message);
        fclose($stream);
    }

    public function testIsColumnTooLongProvider()
    {
        return array(
            array(
                'non-existing',
                'whatever',
                false,
                'non defined columns can always be as long as they want',
            ),
            array(
                'article-name',
                'short',
                false,
                'not so long value must return false',
            ),
            array(
                'article-name',
                'looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong',
                true,
                'long value must return true',
            ),
        );
    }

    /**
     * Test IsColumnTooLong function
     * @param  string $columnName [description]
     * @param  string $value      [description]
     * @param  bool $expected   [description]
     * @return void
     * @dataProvider testIsColumnTooLongProvider
     */
    public function testIsColumnTooLong($columnName, $value, $expected, $message)
    {
        $result = FyndiqCSVFeedWriter::isColumnTooLong($columnName, $value);
        $this->assertEquals($expected, $result, $message);
    }

    public function testSanitizeColumnProvider()
    {
        return array(
            array(
                'non-exsting',
                'le value',
                'le value',
                mb_strlen('le value'),
                'non defined column must return the same value'
            ),
            array(
                'article-name',
                'short',
                'short',
                mb_strlen('short'),
                'short defined values must return the same value'
            ),
            array(
                'article-name',
                'looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong',
                'loooooooooooooooooooooooooooo…',
                FyndiqCSVFeedWriter::$lengthLimitedColumns['article-name'],
                'long values must be shortened to the max length for the column'
            ),
            array(
                'article-name',
                'тумбалумбакозикракзлатопоявисепак',
                'тумбалумбакозикракзлатопоявис…',
                FyndiqCSVFeedWriter::$lengthLimitedColumns['article-name'],
                'utf-8 shortening must work correctly'
            ),
            array(
                FyndiqCSVFeedWriter::PRODUCT_CATEGORY_NAME,
                '123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
                '…0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
                FyndiqCSVFeedWriter::$lengthLimitedColumns[FyndiqCSVFeedWriter::PRODUCT_CATEGORY_NAME],
                'category names should be shortened from the left'
            )
        );
    }

    /**
     * Test Sanitize Column
     * @param  string $columnName [description]
     * @param  string $value      [description]
     * @param  bool $expected   [description]
     * @return void
     * @dataProvider testSanitizeColumnProvider
     */
    public function testSanitizeColumn($columnName, $value, $expected, $size, $message)
    {
        $result = FyndiqCSVFeedWriter::sanitizeColumn($columnName, $value);
        $this->assertEquals($expected, $result, $message);
        $this->assertEquals($size, mb_strlen($result));
    }


    public function testGetLastPriductErrors()
    {
        $this->feedWriter->addProduct(null);
        $result = $this->feedWriter->getLastPriductErrors();
        $this->assertEquals($result, array('Passed product is not array'));

        $this->feedWriter->addProduct(array());
        $result = $this->feedWriter->getLastPriductErrors();
        $this->assertEquals($result, array('Missing required column `product-id`'));
    }

    public function testIsValidContentProvider()
    {
        return array(
            array(
                array(
                    'product-vat-percent' => 19
                ),
                true
            ),
            array(
                array(
                    'product-vat-percent' => 19.00
                ),
                true
            ),
            array(
                array(
                    'product-vat-percent' => '19'
                ),
                true
            ),
            array(
                array(
                    'product-vat-percent' => '19.00'
                ),
                true
            ),
            array(
                array(
                    'product-vat-percent' => '11.00'
                ),
                false
            ),
            array(
                array(
                    FyndiqCSVFeedWriter::ARTICLE_SKU => '12345678901234567890123456789012'
                ),
                true
            ),
            array(
                array(
                    FyndiqCSVFeedWriter::ARTICLE_SKU => '12345678901234567890123456789012345678901234567890123456789012345'
                ),
                false
            ),
            array(
                array(
                    FyndiqCSVFeedWriter::PRODUCT_PRICE => 1.22
                ),
                true
            ),
            array(
                array(
                    FyndiqCSVFeedWriter::PRODUCT_PRICE => 0.00
                ),
                false
            ),
            array(
                array(
                    FyndiqCSVFeedWriter::PRODUCT_OLDPRICE => 0.00
                ),
                false
            ),
        );
    }

    /**
     * [testIsValidContent description]
     * @param  [type] $product  [description]
     * @param  [type] $expected [description]
     * @return [type]           [description]
     * @dataProvider testIsValidContentProvider
     */
    public function testIsValidContent($product, $expected)
    {
        $result = $this->feedWriter->isValidContent($product);
        $this->assertEquals($expected, $result);
    }


    public function testAddCompleteProductProvider()
    {
        return array(
            array(
                array(),
                array(),
                array('Missing required column `product-id`'),
                false,
                false,
            ),
            array(
                array(
                    FyndiqCSVFeedWriter::ID => '1',
                    FyndiqCSVFeedWriter::PRICE => 2.22,
                    FyndiqCSVFeedWriter::OLDPRICE => '3.33',
                    FyndiqCSVFeedWriter::QUANTITY => 123,
                    FyndiqCSVFeedWriter::SKU => 'SKU-123',
                    FyndiqCSVFeedWriter::PRODUCT_MARKET => 'DE',
                    FyndiqCSVFeedWriter::PRODUCT_TITLE => 'Test product',
                    FyndiqCSVFeedWriter::PRODUCT_DESCRIPTION => 'Test product description',
                    FyndiqCSVFeedWriter::PRODUCT_BRAND_NAME => 'Brand0',
                    FyndiqCSVFeedWriter::PRODUCT_CATEGORY_ID => 12,
                    FyndiqCSVFeedWriter::PRODUCT_CATEGORY_NAME => 'Category 2',
                    FyndiqCSVFeedWriter::PRODUCT_CURRENCY => 'SEK',
                    FyndiqCSVFeedWriter::PRODUCT_VAT_PERCENT => 25,
                    FyndiqCSVFeedWriter::LOCATION => 'Warehouse',
                    FyndiqCSVFeedWriter::IMAGES => array(
                        'http://example.com/image1.jpg',
                    ),
                    FyndiqCSVFeedWriter::PROPERTIES => array(
                        array(
                            'name' => 'Key',
                            'value' => 'Value'
                        )
                    )
                ),
                array(),
                array(),
                array(
                    FyndiqCSVFeedWriter::PRODUCT_ID => '1',
                    FyndiqCSVFeedWriter::PRODUCT_PRICE => '2.22',
                    FyndiqCSVFeedWriter::PRODUCT_OLDPRICE => '3.33',
                    FyndiqCSVFeedWriter::PRODUCT_MARKET => 'DE',
                    FyndiqCSVFeedWriter::PRODUCT_TITLE => 'Test product',
                    FyndiqCSVFeedWriter::PRODUCT_DESCRIPTION => 'Test product description',
                    FyndiqCSVFeedWriter::PRODUCT_BRAND_NAME => 'Brand0',
                    FyndiqCSVFeedWriter::PRODUCT_CATEGORY_ID => 12,
                    FyndiqCSVFeedWriter::PRODUCT_CATEGORY_NAME => 'Category 2',
                    FyndiqCSVFeedWriter::PRODUCT_CURRENCY => 'SEK',
                    FyndiqCSVFeedWriter::PRODUCT_VAT_PERCENT => 25,
                    FyndiqCSVFeedWriter::ARTICLE_LOCATION => 'Warehouse',
                    FyndiqCSVFeedWriter::ARTICLE_NAME => 'Test product',
                    FyndiqCSVFeedWriter::ARTICLE_SKU => 'SKU-123',
                    FyndiqCSVFeedWriter::ARTICLE_QUANTITY => 123,
                    sprintf(FyndiqCSVFeedWriter::PRODUCT_IMAGE_URL_TEMPLATE, 1) => 'http://example.com/image1.jpg',
                    sprintf(FyndiqCSVFeedWriter::PRODUCT_IMAGE_IDENTIFIER_TEMPLATE, 1) => 'a131bb8951',
                    sprintf(FyndiqCSVFeedWriter::ARTICLE_PROPERTY_NAME_TEMPLATE, 1) => 'Key',
                    sprintf(FyndiqCSVFeedWriter::ARTICLE_PROPERTY_VALUE_TEMPLATE, 1) => 'Value',
                ),
                true,
            ),
            array(
                array(
                    FyndiqCSVFeedWriter::ID => '1',
                    FyndiqCSVFeedWriter::PRICE => 2.22,
                    FyndiqCSVFeedWriter::OLDPRICE => '3.33',
                    FyndiqCSVFeedWriter::QUANTITY => 123,
                    FyndiqCSVFeedWriter::SKU => 'SKU-123',
                    FyndiqCSVFeedWriter::PRODUCT_MARKET => 'DE',
                    FyndiqCSVFeedWriter::PRODUCT_TITLE => 'Test product',
                    FyndiqCSVFeedWriter::PRODUCT_DESCRIPTION => 'Test product description',
                    FyndiqCSVFeedWriter::PRODUCT_BRAND_NAME => 'Brand0',
                    FyndiqCSVFeedWriter::PRODUCT_CATEGORY_ID => 12,
                    FyndiqCSVFeedWriter::PRODUCT_CATEGORY_NAME => 'Category 2',
                    FyndiqCSVFeedWriter::PRODUCT_CURRENCY => 'SEK',
                    FyndiqCSVFeedWriter::PRODUCT_VAT_PERCENT => 25,
                    FyndiqCSVFeedWriter::LOCATION => 'Warehouse',
                    FyndiqCSVFeedWriter::IMAGES => array(
                        'http://example.com/image1.jpg',
                    ),
                    FyndiqCSVFeedWriter::PROPERTIES => array(
                        array(
                            'name' => 'Key',
                            'value' => 'Value'
                        )
                    )
                ),
                array(
                    array(
                        FyndiqCSVFeedWriter::ID => '2',
                        FyndiqCSVFeedWriter::PRICE => 4.44,
                        FyndiqCSVFeedWriter::OLDPRICE => 5.55,
                        FyndiqCSVFeedWriter::QUANTITY => 134,
                        FyndiqCSVFeedWriter::SKU => '134-SKU',
                        FyndiqCSVFeedWriter::PROPERTIES => array(
                           array(
                                'name' => 'Key',
                                'value' => 'Value'
                            )
                        ),
                        FyndiqCSVFeedWriter::IMAGES => array(
                          'http://example.com/image1.jpg',
                        ),
                    )
                ),
                array(),
                array(
                    FyndiqCSVFeedWriter::PRODUCT_ID => '1',
                    FyndiqCSVFeedWriter::PRODUCT_PRICE => '4.44',
                    FyndiqCSVFeedWriter::PRODUCT_OLDPRICE => '5.55',
                    FyndiqCSVFeedWriter::PRODUCT_MARKET => 'DE',
                    FyndiqCSVFeedWriter::PRODUCT_TITLE => 'Test product',
                    FyndiqCSVFeedWriter::PRODUCT_DESCRIPTION => 'Test product description',
                    FyndiqCSVFeedWriter::PRODUCT_BRAND_NAME => 'Brand0',
                    FyndiqCSVFeedWriter::PRODUCT_CATEGORY_ID => 12,
                    FyndiqCSVFeedWriter::PRODUCT_CATEGORY_NAME => 'Category 2',
                    FyndiqCSVFeedWriter::PRODUCT_CURRENCY => 'SEK',
                    FyndiqCSVFeedWriter::PRODUCT_VAT_PERCENT => 25,
                    FyndiqCSVFeedWriter::ARTICLE_LOCATION => 'Warehouse',
                    FyndiqCSVFeedWriter::ARTICLE_NAME => 'Key: Value',
                    FyndiqCSVFeedWriter::ARTICLE_SKU => '134-SKU',
                    FyndiqCSVFeedWriter::ARTICLE_QUANTITY => 134,
                    sprintf(FyndiqCSVFeedWriter::PRODUCT_IMAGE_URL_TEMPLATE, 1) => 'http://example.com/image1.jpg',
                    sprintf(FyndiqCSVFeedWriter::PRODUCT_IMAGE_IDENTIFIER_TEMPLATE, 1) => 'a131bb8951',
                    sprintf(FyndiqCSVFeedWriter::ARTICLE_PROPERTY_NAME_TEMPLATE, 1) => 'Key',
                    sprintf(FyndiqCSVFeedWriter::ARTICLE_PROPERTY_VALUE_TEMPLATE, 1) => 'Value',
                ),
                true,
            ),
            array(
                false,
                false,
                array(),
                false,
                false,
            ),
            array(
                array(
                    'id' => '3',
                    'product-title' => 'Product name',
                    'product-market' => 'DE',
                    'product-description' => 'descrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescr',
                    'price' => 99.999984,
                    'oldprice' => 99.999984,
                    'product-currency' => 'EUR',
                    'product-vat-percent' => 19,
                    'quantity' => 0,
                    'sku' => '3',
                    'article-ean' => '',
                    'article-isbn' => '',
                    'article-mpn' => '',
                    'product-category-id' => '2',
                    'product-category-name' => 'test category / new category',
                    'images' => array (
                        'http://gambio.local/images/product_images/original_images/new-update_3_0.png'
                    )
                ),
                array(
                    array (
                      'quantity' => 0,
                      'id' => '19',
                      'article-name' => 'Product name',
                      'sku' => '3-19',
                      'properties' => array (),
                      'article-ean' => '',
                      'price' => 115.00003,
                      'oldprice' => '115.00',
                    )
                ),
                array(),
                array(
                    'product-id' => '3',
                    'product-price' => '115.00',
                    'product-oldprice' => '115.00',
                    'product-market' => 'DE',
                    'product-title' => 'Product name',
                    'product-description' => 'descrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescrdescr',
                    'product-category-id' => '2',
                    'product-category-name' => 'test category / new category',
                    'product-currency' => 'EUR',
                    'product-vat-percent' => 19,
                    'article-name' => 'Product name',
                    'article-sku' => '3-19',
                    'article-quantity' => 0,
                    'article-ean' => '',
                    'product-image-1-url' => 'http://gambio.local/images/product_images/original_images/new-update_3_0.png',
                    'product-image-1-identifier' => '558b11c592',
                ),
                true
            ),
        );
    }

    /**
     * testAddCompleteProduct
     * @param  mixed $product
     * @param  array $articles
     * @param  bool $expected
     * @dataProvider testAddCompleteProductProvider
     */
    public function testAddCompleteProduct($product, $articles, $errors, $exportProduct, $expected)
    {
        if ($exportProduct) {
            $this->feedWriter = $this->getMockBuilder('FyndiqCSVFeedWriter')
                ->setConstructorArgs(array(null))
                ->setMethods(array('addProduct', 'writeRow'))
                ->getMock();
            $this->feedWriter->method('writeRow')->willReturn(true);
            $this->feedWriter->expects($this->once())
                ->method('addProduct')
                ->with(
                    $this->equalTo($exportProduct)
                )
                ->willReturn(true);
        }
        $result = $this->feedWriter->addCompleteProduct($product, $articles);
        $this->assertEquals($errors, $this->feedWriter->getLastProductErrors());
        $this->assertEquals($expected, $result);
    }

    public function testGetImagesProvider()
    {
        return array(
            array(
                array(
                    'http://example.com/image1.jpg',
                    'http://example.com/image2.jpg',
                    'http://example.com/image3.jpg',
                ),
                3,
                array(
                    'product-image-1-url' => 'http://example.com/image1.jpg',
                    'product-image-1-identifier' => 'a131bb8951',
                    'product-image-2-url' => 'http://example.com/image2.jpg',
                    'product-image-2-identifier' => '2f5bcaf029',
                    'product-image-3-url' => 'http://example.com/image3.jpg',
                    'product-image-3-identifier' => '095651d72f',
                ),
            ),
            array(
                array(
                    'http://example.com/image1.jpg',
                    'http://example.com/image2.jpg',
                    'http://example.com/image3.jpg',
                ),
                2,
                array(
                    'product-image-1-url' => 'http://example.com/image1.jpg',
                    'product-image-1-identifier' => 'a131bb8951',
                    'product-image-2-url' => 'http://example.com/image2.jpg',
                    'product-image-2-identifier' => '2f5bcaf029',
                ),
            ),
            array(
                array(),
                10,
                array(),
            ),
        );
    }

    /**
     * testGetImages
     * @param  [type] $images   [description]
     * @param  [type] $limit    [description]
     * @param  [type] $expected [description]
     * @dataProvider testGetImagesProvider
     */
    public function testGetImages($images, $limit, $expected)
    {
        $result = $this->feedWriter->getImages($images, $limit);
        $this->assertEquals($expected, $result);
    }

    public function testGetPropertiesProvider()
    {
        return array(
            array(
                array(
                    array(
                        'name' => 'Color',
                        'value' => 'Red'
                    ),
                    array(
                        'name' => 'Size',
                        'value' => 'L'
                    ),
                ),
                2,
                array(
                    'article-property-1-name' => 'Color',
                    'article-property-1-value' => 'Red',
                    'article-property-2-name' => 'Size',
                    'article-property-2-value' => 'L',
                ),
            ),
            array(
                array(
                    array(
                        'name' => 'Pattern',
                        'value' => 'Stripes',
                    ),
                    array(
                        'name' => 'Color',
                        'value' => 'Red',
                    ),
                    array(
                        'name' => 'Size',
                        'value' => 'L',
                    ),
                ),
                2,
                array(
                    'article-property-1-name' => 'Pattern',
                    'article-property-1-value' => 'Stripes',
                    'article-property-2-name' => 'Color',
                    'article-property-2-value' => 'Red',
                ),
            ),
            array(
                array(),
                10,
                array(),
            ),
        );
    }

    /**
     * testGetProperties
     * @param  [type] $properties   [description]
     * @param  [type] $limit    [description]
     * @param  [type] $expected [description]
     * @dataProvider testGetPropertiesProvider
     */
    public function testGetProperties($images, $limit, $expected)
    {
        $result = $this->feedWriter->getProperties($images, $limit);
        $this->assertEquals($expected, $result);
    }

    public function testAddCompleteProductSplit()
    {
        $product = array(
            FyndiqFeedWriter::ID => 1,
            FyndiqFeedWriter::IMAGES => array(
                'http://example.com/product.jpg',
            )
        );
        $articles = array(
            array(
                FyndiqFeedWriter::ID => 1,
                FyndiqFeedWriter::PRICE => 12,
                FyndiqFeedWriter::OLDPRICE => 14,
                FyndiqFeedWriter::PROPERTIES => array(
                    array(
                        'name' => 'Color',
                        'value' => 'Red',
                    )
                ),
                FyndiqFeedWriter::IMAGES => array(
                    'http://example.com/article1.jpg',
                ),
            ),
            array(
                FyndiqFeedWriter::ID => 2,
                FyndiqFeedWriter::PRICE => 13,
                FyndiqFeedWriter::OLDPRICE => 16,
                FyndiqFeedWriter::PROPERTIES => array(
                    array(
                        'name' => 'Color',
                        'value' => 'Blue',
                    )
                ),
                FyndiqFeedWriter::IMAGES => array(
                   'http://example.com/article2.jpg',
                )
            ),
        );
        $errors = array();
        $exportProduct1 = array(
            FyndiqFeedWriter::PRODUCT_ID => '1-1',
            FyndiqFeedWriter::ARTICLE_NAME => 'Color: Red',
            FyndiqFeedWriter::PRODUCT_PRICE => '12.00',
            FyndiqFeedWriter::PRODUCT_OLDPRICE => '14.00',
            sprintf(FyndiqFeedWriter::PRODUCT_IMAGE_URL_TEMPLATE, 1) => 'http://example.com/article1.jpg',
            sprintf(FyndiqFeedWriter::PRODUCT_IMAGE_IDENTIFIER_TEMPLATE, 1) => 'e2c7d76db8',
            sprintf(FyndiqFeedWriter::PRODUCT_IMAGE_URL_TEMPLATE, 2) => 'http://example.com/product.jpg',
            sprintf(FyndiqFeedWriter::PRODUCT_IMAGE_IDENTIFIER_TEMPLATE, 2) => 'dd51a6b164',
            sprintf(FyndiqFeedWriter::ARTICLE_PROPERTY_NAME_TEMPLATE, 1) => 'Color',
            sprintf(FyndiqFeedWriter::ARTICLE_PROPERTY_VALUE_TEMPLATE, 1) => 'Red',
            FyndiqFeedWriter::PRODUCT_TITLE => 'Color: Red',
        );
        $exportProduct2 = array(
            FyndiqFeedWriter::PRODUCT_ID => '1-2',
            FyndiqFeedWriter::ARTICLE_NAME => 'Color: Blue',
            FyndiqFeedWriter::PRODUCT_PRICE => '13.00',
            FyndiqFeedWriter::PRODUCT_OLDPRICE => '16.00',
            sprintf(FyndiqFeedWriter::PRODUCT_IMAGE_URL_TEMPLATE, 1) => 'http://example.com/article2.jpg',
            sprintf(FyndiqFeedWriter::PRODUCT_IMAGE_IDENTIFIER_TEMPLATE, 1) => 'cddb9eb7fd',
            sprintf(FyndiqFeedWriter::PRODUCT_IMAGE_URL_TEMPLATE, 2) => 'http://example.com/product.jpg',
            sprintf(FyndiqFeedWriter::PRODUCT_IMAGE_IDENTIFIER_TEMPLATE, 2) => 'dd51a6b164',
            sprintf(FyndiqFeedWriter::ARTICLE_PROPERTY_NAME_TEMPLATE, 1) => 'Color',
            sprintf(FyndiqFeedWriter::ARTICLE_PROPERTY_VALUE_TEMPLATE, 1) => 'Blue',
            FyndiqFeedWriter::PRODUCT_TITLE => 'Color: Blue',
        );

        $feedWriter = $this->getMockBuilder('FyndiqCSVFeedWriter')
            ->setConstructorArgs(array(null))
            ->setMethods(array('addProduct', 'writeRow'))
            ->getMock();
        $feedWriter->method('writeRow')->willReturn(true);
        $feedWriter->expects($this->at(0))
            ->method('addProduct')
            ->with(
                $this->equalTo($exportProduct1)
            )
            ->willReturn(true);
        $feedWriter->expects($this->at(1))
            ->method('addProduct')
            ->with(
                $this->equalTo($exportProduct2)
            )
            ->willReturn(true);

        $result = $feedWriter->addCompleteProduct($product, $articles);
        $this->assertEquals($errors, $feedWriter->getLastProductErrors());
        $this->assertTrue($result);
    }

    public function testGetArticleNameProvider()
    {
        return array(
            array(
                array(),
                array(
                    FyndiqCSVFeedWriter::ARTICLE_NAME => 'Article as product'
                ),
                true,
                'Article as product',
                'Use articles name when Article as product'
            ),
            array(
                array(),
                array(
                    FyndiqCSVFeedWriter::PROPERTIES => array(
                        array(
                            FyndiqCSVFeedWriter::PROPERTY_NAME => 'Color',
                            FyndiqCSVFeedWriter::PROPERTY_VALUE => 'Red',
                        ),
                    )
                ),
                true,
                'Color: Red',
                'Use properties when Article as product but not article name set'
            ),
            array(
                array(
                    FyndiqCSVFeedWriter::PRODUCT_TITLE => 'Product title'
                ),
                array(),
                true,
                'Product title',
                'Use product title as last resort'
            ),

        );
    }

    /**
     * testGetArticleName
     * @dataProvider testGetArticleNameProvider
     */
    public function testGetArticleName($feedProduct, $article, $articleAsProduct, $expected, $message)
    {
        $result = $this->feedWriter->getArticleName($feedProduct, $article, $articleAsProduct);
        $this->assertEquals($expected, $result);
    }


    public function testClearTextFieldProvider()
    {
        return array(
            array(
                '<asdasd></asd>',
                '',
            ),
            array(
                'test<asdasd></asd><br/>test2',
                'test
test2',
            ),
            array(
                'test<asdasd></asd><BR/><BR><br class="custom"/>test2',
                'test

test2',
            ),
            array(
                '<p>asdsad</p>asdsad',
                'asdsad
asdsad',
            ),
            array(
                '<p>Available in Sharp fit. Refined collar. Button cuff. Cotton. Machine wash. Made in US.</p>


<ul>
<li>asdf</li>
<li>asdf</li>
<li>adsf</li>
<li>dsaf</li>
<li>adsf</li>
</ul>
<ol>
<li>test1</li><li>test2</li><li>test3</li><li>test4</li><li>test5</li>
</ol>',
                'Available in Sharp fit. Refined collar. Button cuff. Cotton. Machine wash. Made in US.

asdf
asdf
adsf
dsaf
adsf

test1
test2
test3
test4
test5',
            ),
            array('<div class="std">
        <h3>AS Creation - Dekora Natur 6 Vinyl-Tapete  958332:</h3><br><p><strong>Herstellernr.: </strong>958332</p><p><strong>EAN: </strong>4051315104668</p><br><p>Von eher schlicht und dezent bis hin zu auffällig und farbenfroh – die acht individuellen Kollektionsthemen der nunmehr sechsten Edition der natürlichen Tapetenkollektion «Dekora Natur 6» begeistern mit einem Facettenreichtum aus natürlichen Oberflächen und dekorativen Printmotiven. Im Tapetenshop seit dem 28.11.2014.</p><br><h3>Details:</h3><ul><li><strong>Artikelart: </strong>Tapete</li><li><strong>Marke: </strong>AS Creation</li><li><strong>Kollektion: </strong>Dekora Natur 6</li><li><strong>Wohnwelt: </strong>Bad , Büro , Küche , SchlafenWohnen</li><li><strong>Muster: </strong>Landhaus , Modern</li><li><strong>Farbe: </strong>Beige , Braun ,</li></ul><h3>Ausführung:</h3><ul><li><strong>Material: </strong>Vinyl</li><li><strong>Aufbau: </strong>Papier duplex (TD Rapportpräge)</li><li><strong>Format: </strong>10,05 m x 0,53 m</li><li><strong>Rapport: </strong>53 / 26 cm (versetzt)</li><li><strong>Gewicht: </strong>1,08</li></ul><h3>Eigenschaften:</h3><ul><li><strong>Pflegeeigenschaft: </strong>scheuerbeständig</li><li><strong>Lichtbeständig: </strong>gut lichtbeständig</li><li><strong>Entfernung: </strong>restlos trocken abziehbar</li><li><strong>RAL: </strong>Ja</li><li><strong>FSC: </strong>Ja</li><li><strong>CE: </strong>CE C - s2, d0</li><li><strong>Brandverhalten: </strong>schwerentflammbar, kein brennendes Abtropfen/Abfallen</li></ul>    </div>',
            'AS Creation - Dekora Natur 6 Vinyl-Tapete  958332:

Herstellernr.: 958332
EAN: 4051315104668

Von eher schlicht und dezent bis hin zu auffällig und farbenfroh – die acht individuellen Kollektionsthemen der nunmehr sechsten Edition der natürlichen Tapetenkollektion «Dekora Natur 6» begeistern mit einem Facettenreichtum aus natürlichen Oberflächen und dekorativen Printmotiven. Im Tapetenshop seit dem 28.11.2014.

Details:
Artikelart: Tapete
Marke: AS Creation
Kollektion: Dekora Natur 6
Wohnwelt: Bad , Büro , Küche , SchlafenWohnen
Muster: Landhaus , Modern
Farbe: Beige , Braun ,
Ausführung:
Material: Vinyl
Aufbau: Papier duplex (TD Rapportpräge)
Format: 10,05 m x 0,53 m
Rapport: 53 / 26 cm (versetzt)
Gewicht: 1,08
Eigenschaften:
Pflegeeigenschaft: scheuerbeständig
Lichtbeständig: gut lichtbeständig
Entfernung: restlos trocken abziehbar
RAL: Ja
FSC: Ja
CE: CE C - s2, d0
Brandverhalten: schwerentflammbar, kein brennendes Abtropfen/Abfallen'
            )
        );
    }

    /**
     * testClearTextField
     * @param  string $text     [description]
     * @param  string $expected [description]
     * @dataProvider testClearTextFieldProvider
     */
    public function testClearTextField($text, $expected)
    {
        $result = $this->feedWriter->clearDescription($text);
        $this->assertEquals($expected, $result);
    }
}
