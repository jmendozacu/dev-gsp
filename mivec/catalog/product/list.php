<?php
header('Content-type:text/html;charset=utf-8');
require 'config.php';

$_productCollection = Mage::getModel('catalog/product')
    ->getCollection();
//->setOrder('entity_id' , 'DESC');

if ($_sku = $app->getRequest()->getParam('sku')) {
    $_productCollection->addAttributeToFilter('sku' , array('like'	=> "%$_sku%"));
}
if ($_name = $app->getRequest()->getParam('name')) {
    if (strpos($_name , " ") !== false) {
        foreach (split(" " , $_name) as $_n) {
            $_productCollection->addAttributeToFilter(
                array(
                    array('attribute'=>'name','like'=>"%$_n%"),
                    //array('attribute'=>'erp_chinese','like'=>"%$_n%"),
                ));
        }
    }
    else {
        $_productCollection->addAttributeToFilter('name' , array('like'	=> "%$_name%"));
    }
}

if ($_categoryId = $app->getRequest()->getParam('category_id')) {
	$_category = Mage::getModel('catalog/category')->load($_categoryId);
	$_productCollection->addCategoryFilter($_category);
}

//echo $_productCollection->getSelect()->__toString();

//分页
$curPage = 1;
if ($app->getRequest()->getParam('page')) {
    $curPage = $app->getRequest()->getParam('page');
}
//$_sql = $_productCollection->getSelect()->__toString();
$_productCollection->getSelect()->order('entity_id DESC');

$config['sql'] = $_productCollection->getSelect()->__toString();
$config['limit'] = 50;
$result = pager($config , $curPage);

if ($row = $result['row']) {
    foreach ($row as $rs) {
        $_id = $rs['entity_id'];

        $_product = Mage::getModel('catalog/product')
            ->load($_id);

        $_media = $_product->getData('media_gallery');
		//print_r($_media);exit;
        //print_r($_product->getThumbnail());exit;

        $_img = Mage::app('baseurl') . '/media/catalog/product/' . $_product->getThumbnail();//$_media['images'][0]['file'];

        $stocklevel = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty();

        $_price = $_product->getSpecialPrice() ? $_product->getSpecialPrice() : $_product->getPrice();
        $arr[] = array(
            'id'	=> $_id ,
            'product_name'	=> $_product->getName(),
            'product_cname'	=> $_product->getData('erp_chinese'),
            'sku'	=> $_product->getSku(),
            'price'	=> $app->getStore(0)->getBaseCurrency()->getCode() . " " . number_format($_price , 2),
            'cost'	=> "CNY " . number_format($_product->getData('product_cost') , 2),
            //'quote'	=> $_quote,
            'quote' => '',
            'stock'	=> $stocklevel,
            'status'	=> $conn['status'][$_product->getStatus()],
            'image' => $_img,
			//'image'	=> getProductImages($_product),
            'url'	=> $_product->getProductUrl(),
        );
    }
}

function getProductImages(Mage_Catalog_Product_Model $_product)
{
	if ($_media = $_product->getData('media_gallery')) {
		$_images = array();
		foreach ($_media['images'] as $img) {
			$_images[] = array(
				'file'	=> Mage::app('baseurl') . '/media/catalog/product/' . $img['file']
			);
		}
		return $_images;
	}
}
?>
<?php include 'header.php';?>
<title>产品列表</title>
<table width="100%" border="0" cellspacing="1" cellpadding="0" class="list_row" style="background:#e8e8e8">
    <tr>
        <td height="25" colspan="8" bgcolor="#FFFFFF">Product List</td>
    </tr>
    <tr>
        <td height="12" colspan="8" bgcolor="#FFFFFF">
            <form id="form_filter" method="get" action="?">
                <table width="100%" border="0" cellspacing="1" cellpadding="0">
                    <tr>
                        <td width="7%">SKU</td>
                        <td><?php echo formText('sku' , $app->getRequest()->getParam('sku'))?></td>
                    </tr>
                    <tr>
                        <td>name</td>
                        <td><?php echo formText('name' , $app->getRequest()->getParam('name'))?></td>
                    </tr>
                    <tr>
                        <td>Category Id</td>
                        <td><?php echo formText('category_id' , $app->getRequest()->getParam('category_id'))?></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button id="submit" type="submit" class="button btn-cart">
                                <span><span>Filter</span></span>
                            </button>
                            <button id="reset" type="button" class="button btn-cart">
                                <span><span>Reset</span></span>
                            </button></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <label>
                                <input type="checkbox" onclick='checkAll(this)' />
                                &nbsp;&nbsp;Select
                            </label>
                            <button id="export" type="button" class="button btn-cart" onclick="exportItems()"> <span><span>Export</span></span></button></td>
                    </tr>
                </table>
            </form></td>
    </tr>
    <tr>
        <td height="25" colspan="8" bgcolor="#FFFFFF"><?php echo $result['page'];?></td>
    </tr>
    <tr>
        <td width="4%" height="25" bgcolor="#FFFFFF">ID</td>
        <td width="40%" bgcolor="#FFFFFF">Name</td>
        <td width="12%" bgcolor="#FFFFFF">SKU</td>
        <td width="15%" bgcolor="#FFFFFF">IMAGES</td>
        <td width="6%" bgcolor="#FFFFFF">Price</td>
        <td width="7%" bgcolor="#FFFFFF">Stock</td>
        <td width="8%" bgcolor="#FFFFFF">Status</td>
    </tr>
    <?php
    $i = 0;
    $color = '#FFFFFF';
    foreach ($arr as $rs) :
        if ($i % 2 == 0) {
            $color = "#F0FFF0";
        } else {
            $color = '#FFFFFF';
        }
        ?>
        <tr bgcolor="<?php echo $color?>">
            <td height="25"><label>
                    <input type="checkbox" name="id" value="<?php echo $rs['id']?>"  />
                    <?php echo $rs['id']?></label></td>
            <td height="30"><?php echo $rs['product_name']?></td>
            <td height="30"><?php echo $rs['sku']?></td>
            <td>
            <?php
			/*
			foreach ($rs['image'] as $_img) :?>
            <a href="<?php echo $_img['file']?>" target="_blank">
            <img src="<?php echo $_img['file']?>" width="295" height="295"/>
            </a>
            <?php endforeach;*/?>
            <img src="<?php echo $rs['image']?>" width="295" height="295"/>
            
            </td>
            <td><?php echo $rs['price']?></td>
            <td><span class="important"><?php echo $rs['stock']?></span></td>
            <td><?php echo $rs['status']?></td>
        </tr>
        <?php $i++;endforeach;?>
    <tr>
        <td height="30" colspan="8" bgcolor="#FFFFFF"><?php echo $result['page'];?></td>
    </tr>
</table>

<script type="text/javascript">
    $(document).ready(function(){
        /*	$('#submit').click(function(){
         $('#form_filter').submit();
         });*/

        $('#reset').click(function(){
            window.location.href = 'list.php';
        });
    });

    function checkAll(argu)
    {
        var obj = document.getElementsByName("id");
        for(var i= 0;i<obj.length;i++){
            obj[i].checked = argu.checked;
        }
    }
</script>