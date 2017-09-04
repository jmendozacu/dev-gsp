<?php
/**
 * Index Controller
 *
 */
class Gsp_Price_IndexController extends Mage_Core_Controller_Front_Action
{
      private function GetCategories($categoryId){
        $category = Mage::getModel('catalog/category')->load($categoryId);                      
        $path =  array_map('intval', explode('/', $category->getPath()));
        unset($path[0]);
        unset($path[1]);
        
        $categories = Mage::getModel('catalog/category')
                      ->getCollection()
                      ->addAttributeToSelect('name') //you can add more attributes using this
                      ->addAttributeToFilter('entity_id', array('in'=>$path));
         
        $data = array();              
        foreach($categories as $category)    
        {
            $data[] = trim($category->getName());
        }                 
                
        return implode('/', $data);
    }
    
    /**
     * Index Action
     */
    public function indexAction()
    {       
        $products =Mage::getModel('catalog/product')
                        ->getCollection()
                        ->addAttributeToSelect('*');
                                             
        $content = "Produktnamn;Art.nr.;Kategori;Pris exkl.moms;Produkt-URL;Tillverkare;Bild-URL;Lagerstatus\n";              
        
        foreach ($products as $product)
        {                             
                if(substr_compare( $product->getSku(),"REP",0,3) == 0)
                    continue;
            
                            
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $fullproduct = Mage::getModel('catalog/product')->load($product->getId());
              
            
                $product_data = array();
                $product_data[] = $fullproduct->getName();
                $product_data[] = $fullproduct->getSku();
                $product_data[] = $this->GetCategories($fullproduct->getCategoryIds()[0]);
                $product_data[] = $fullproduct->getPrice();
                $product_data[] = $fullproduct->getProductUrl();
                $product_data[] = $fullproduct->getManufacture();
                $product_data[] = $fullproduct->getImageUrl();
                $product_data[] = $stock->getQty();
            
 
                $content .= implode(';', $product_data)."\n";
            
        }
 
	Mage::log("Price file created using " . round(memory_get_usage(TRUE)/1048576,2) . "M memory.");
        $this->getResponse()->setBody($content);
    }
    
  
}
