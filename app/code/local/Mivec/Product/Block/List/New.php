<?php
class Mivec_Product_Block_List_New extends Mivec_Product_Block_Abstract
{
    protected $_productCollection;
    protected $_category;
    const DEFAULT_SIZE = 36;

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $web['title'] = $this->__("Products");
        //page title
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->setTitle($web['title']);
        }

        if ($bread = $this->getLayout()->getBlock("breadcrumbs")) {
            $bread->addCrumb("home" , array(
                "label" => "Home",
                'link'  => Mage::getBaseUrl()
            ))
            ->addCrumb("new" , array(
                "label" => "All Products",
                "read-only" => "yes"
            ));
        }

        $this->setTemplate('mivec/product/list/new.phtml');
    }

    public function getProductCollection()
    {
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addStoreFilter()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status' , 1);

        $collection->setOrder('entity_id' , 'DESC')
            ->setCurPage(1)
            ->setPageSize(self::DEFAULT_SIZE);

        $collection->getSelect()->limit(self::DEFAULT_SIZE);

        //$collection->joinField('inventory_in_stock', 'cataloginventory_stock_item', 'is_in_stock', 'product_id=entity_id','is_in_stock>=0', 'left')
           // ->setOrder('inventory_in_stock','desc');
        //echo $collection->getSelect()->__toString();

        //Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($collection);
        //Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

        return $collection;
    }


    public function setListOrders()
    {
        $category = Mage::getSingleton('catalog/layer')
            ->getCurrentCategory();

        /* @var $category Mage_Catalog_Model_Category */
        $availableOrders = $category->getAvailableSortByOptions();
        //print_r($availableOrders);exit;
        $availableOrders = array(
            //"price"     => "Price",
            "entity_id" => "Release Date",
        );
        $this->getChild('product_new_list')
            ->setAvailableOrders($availableOrders);
    }

    public function setListCollection()
    {
        $this->getChild('product_new_list')
            ->setCollection($this->getProductCollection());
    }

    public function getProductListHtml()
    {
        return $this->getChildHtml('product_new_list');
    }

    public function getResultCount()
    {
        if (!$this->getData('result_count'))
        {
            $size = $this->getProductCollection()->getSize();
            $this->setResultCount($size);
        }
        return $this->getData('result_count');
    }
}