<?php
class Evalent_Social_Block_Facebook_Og extends Mage_Core_Block_Template {

    private $_tags = array();

    /**
     * Get all tags for printing
     * @return array
     */
    public function getTags(){
        return array_filter($this->_tags);
    }

    /**
     * Add/update a tag
     * Set to empty string to exclude from output
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function setTag($name, $value){
        $this->_tags[$name] = $value;
        return $this;
    }

    /**
     * Prepare the tags for different kinds of pages.
     * What is set here can be overwritten in layouts (with action method="setTag")
     * @return $this
     */
    public function preparePage(){
        if (Mage::getStoreConfig('facebook/opengraph/admin') != '') $this->setTag("fb:admins", Mage::getStoreConfig('facebook/opengraph/admin'));

        $this->setTag("og:site_name", Mage::getStoreConfig('basicsetup/contact_details/name'));
        $this->setTag("og:street-address", Mage::getStoreConfig('basicsetup/contact_details/address'));
        $this->setTag("og:locality", Mage::getStoreConfig('facebook/opengraph/city'));
        $this->setTag("og:postal-code:", Mage::getStoreConfig('facebook/opengraph/zip'));
        $this->setTag("og:country-name:", Mage::getStoreConfig('basicsetup/contact_details/country'));
        $this->setTag("og:phone_number", Mage::getStoreConfig('basicsetup/contact_details/phone'));

        // add defaults
        $this->setTag("og:title",Mage::getStoreConfig('facebook/opengraph/title'));
        $this->setTag("og:type",'product');
        $this->setTag("og:url",Mage::getStoreConfig('facebook/opengraph/url'));
        $this->setTag("og:description",Mage::getStoreConfig('facebook/opengraph/description'));
        $this->setTag("og:image",Mage::getStoreConfig('facebook/opengraph/image'));

        // Detect product & category page
        $controller = Mage::app()->getFrontController()->getRequest()->getControllerName();
        if ($controller == 'product' && Mage::registry('current_product')){
            // Product page
            $this->setTag("og:title",trim(Mage::registry('current_product')->getName()));
            $this->setTag("og:type",'product');
            $this->setTag("og:url",$this->helper('core/url')->getCurrentUrl());
            $this->setTag("og:description",strip_tags(str_replace(array("<br />","<br>"),", ",Mage::registry('current_product')->getDescription())));
            $this->setTag("og:image",Mage::helper('catalog/image')->init(Mage::registry('current_product'), 'small_image')->resize(200,200));
        } elseif ($controller == 'category' && Mage::registry('current_category')) {
            // Category page
            $this->setTag("og:title",trim(Mage::registry('current_category')->getName()));
            $this->setTag("og:type",'product');
            $this->setTag("og:url",$this->helper('core/url')->getCurrentUrl());
            $this->setTag("og:description",strip_tags(str_replace(array("<br />","<br>"),", ",Mage::registry('current_category')->getDescription())));

            if(trim(Mage::registry('current_category')->getImageUrl()=="")) {
                $this->setTag("og:image",trim(Mage::registry('current_category')->getImageUrl()));
            } else {
                $this->setTag("og:image",$this->getLogoSrc());
            }
        }

        Mage::dispatchEvent('social_share_facebook_og_tags_prepare_after', array(
            'block' => $this
        ));

        return $this;
    }
}