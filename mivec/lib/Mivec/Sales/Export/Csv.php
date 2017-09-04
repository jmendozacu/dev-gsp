<?php
class Mivec_Sales_Export_Csv extends Mivec_Sales_Export_Abstract 
{
    const ENCLOSURE = '"';
    const DELIMITER = ',';

    /**
     * Concrete implementation of abstract method to export given orders to csv file in var/export.
     *
     * @param $orders List of orders of type Mage_Sales_Model_Order or order ids to export.
     * @return String The name of the written csv file in var/export
     */
    public function exportOrders($orders,$file) 
    {
        //$fileName = 'order_export_'.date("Ymd_His").'.csv';
        //$fp = fopen(Mage::getBaseDir('export').'/'.$fileName, 'w');
		$fp = fopen($file,'w');
		
        $this->writeHeadRow($fp);
        foreach ($orders as $order) {
            $order = Mage::getModel('sales/order')->load($order);
            $this->writeOrder($order, $fp);
        }

        fclose($fp);

        return $file;
    }

    /**
	 * Writes the head row with the column names in the csv file.
	 * 
	 * @param $fp The file handle of the csv file
	 */
    protected function writeHeadRow($fp) 
    {
        fputcsv($fp, $this->getHeadRowValues(), self::DELIMITER, self::ENCLOSURE);
    }

    /**
	 * Writes the row(s) for the given order in the csv file.
	 * A row is added to the csv file for each ordered item. 
	 * 
	 * @param Mage_Sales_Model_Order $order The order to write csv of
	 * @param $fp The file handle of the csv file
	 */
    protected function writeOrder($order, $fp) 
    {
        $common = $this->getCommonOrderValues($order);

        $orderItems = $order->getItemsCollection();
        $itemInc = 0;
        foreach ($orderItems as $item)
        {
            if (!$item->isDummy()) {
                $record = $this->getOrderItemValues($item, $order, ++$itemInc);
			
			//print_r($common);exit;
			
			foreach($common as $k=>$v)
			{
			   $reconize[$k] = $v;
			}
			foreach($record as $k=>$v)
			{
			  $reconize[$k] = $v;
			}
			ksort($reconize);
            fputcsv($fp, $reconize, self::DELIMITER, self::ENCLOSURE);
            }
        }
    }

    /**
	 * Returns the head column names.
	 * 
	 * @return Array The array containing all column names
	 */
    protected function getHeadRowValues() 
    {
        return array(
				0=>'Order Date',
				1=>'Order Number',
				2=>'Order Remark',
				3=>'Order Shipping Method',
				4=>'Shipping Name',
				5=>'Shipping Country Name',
				
				6=>'Item Name',
				7=>'Item SKU',
				8=>'Item Qty Ordered',
				9=>'Item Price',
				10=>'Item Total',
				
				11=>'Order Shipping',
				12=>'Order Grand Total',
				13=>'Total Qty Items Ordered',
				14=>'Customer Name',
				15=>'Customer Email',
				
				16=>'Shipping Company',
				17=>'Shipping Street1',
				18=>'Shipping Street2',
				19=>'Shipping Zip',
				20=>'Shipping City',
				21=>'Shipping State Name',
				
				22=>'Shipping Phone Number',
				23=>'Item Status',
				24 => 'currency',
				25 =>'bak1',
				26 =>'bak2',
				27 =>'bak3',
				28 =>'bak4',
    	);
    }

    /**
	 * Returns the values which are identical for each row of the given order. These are
	 * all the values which are not item specific: order data, shipping address, billing
	 * address and order totals.
	 * 
	 * @param Mage_Sales_Model_Order $order The order to get values from
	 * @return Array The array containing the non item specific values
	 */
    protected function getCommonOrderValues($order) 
    {
		//echo 'order:'.$order->getRealOrderId() . '</p>';
		
		//dropship
		$dropship = '';
		if ($customer_id = $order->getData('customer_id')) {
			$customer = Mage::getModel('customer/customer')->load($customer_id);
			$dropship = $customer->getGroupId() == 2 ? "dropship" : "";
		}
		
		//remark
		$db = Mage::getSingleton('core/resource')->getConnection('core_read');
		$sql = "SELECT remark FROM mivec_order_status WHERE increment_id =" . $order->getRealOrderId();
		$remark = $db->fetchOne($sql);
		
		//shipping address
        $shippingAddress = !$order->getIsVirtual() ? $order->getShippingAddress() : null;
        $billingAddress = $order->getBillingAddress();
        
		$grand_total = $this->formatPrice($order->getData('grand_total'), $order);
		$shipping_price = $this->formatPrice($order->getData('base_shipping_amount'), $order);
		//echo $grand_total;
		//echo $order->getData('grand_total');
		
		$provider = $this->getShippingMethod($order);
		
		//模块变更,增加新模块为afree_afree
		if ($provider == 'afree_afree') {
			$provider = str_replace('afree_afree','freeshipping_freeshipping',$provider);
		}

		$method = '';
		if ($provider == 'freeshipping_freeshipping') {
			$method = 'Airmail';
		}
		
		/**
		 * 修改备注:
		 * 更改运输方式的公式:
		 * condition1 : 如果订单金额 > 30,承运商变更 Registered Airmail
		 * condition2 : 如果承运商 == airmail,且运费 > 0，则承运商变更为 Registered Airmail
		 *
		 * 注 几大承运商改成大写,并把shipping模块名称替换
		 * 3.联系电话中增加字符前缀:T: 
		 */
		if ($order->getData('grand_total') >= 30 && $provider == 'freeshipping_freeshipping') {
			$method = 'Registered Airmail';
		} elseif ($provider == 'freeshipping_freeshipping' && $order->getData('base_shipping_amount') > 0) {
			$method = 'Registered Airmail';
		}
		
		 /**  2011-07-06 增加
		  * 以前从网站下载的订单，自动把30美金以上的平邮改为挂号了。
		  *	现在把俄罗斯和乌克兰 USD20美金以上的平邮包裹 改成挂号
		  * Ukraine
		  */
		$countryName = strtolower($shippingAddress->getCountryModel()->getName());
		if ($provider == 'freeshipping_freeshipping' && ($countryName == 'ukraine' || $countryName == 'russia')) {
			if ($order->getData('grand_total') >= 20 ) {
				$method = 'Registered Airmail';
			}
		}
		
		//如果是其他承运商,ems ups dhl,则取模块名并大写,再删除第一个字符 a
		if ($provider != 'freeshipping_freeshipping') {
			$method = preg_replace('/_\w+/is','',$provider);
			$method = str_replace('a','',$method);
			$method = strtoupper($method);
		}
		
		//echo $method;exit;
		//echo $order->getData('created_at');exit;
		//电话
		$telephone = 'T:';
		$telephone .= $shippingAddress ? $shippingAddress->getData("telephone") : '';
		
        return array(
           // 0=>Mage::helper('core')->formatDate($order->getCreatedAt(), 'medium', true),	//订单日期
		    0=>$order->getData('created_at'),
            1=>$order->getRealOrderId(),        											//订单�?
			2=>$remark, //remark
            3=>$method,                                         //运输方式
            4=>$shippingAddress ? self::replace($shippingAddress->getName()) : '',                      //收件人名�?
            5=>$shippingAddress ? $shippingAddress->getCountryModel()->getName() : '',   //国家
            
            11=>self::destroyCurrency($shipping_price),      //运费
            12=>self::destroyCurrency($grand_total),            //总计
            13=>$this->getTotalQtyItemsOrdered($order),                                   //总数�?
            14=>self::replace($order->getCustomerName()),												//客户�?
            15=>$order->getCustomerEmail(),                                               //客户邮箱
			
            //16=>$shippingAddress ? $shippingAddress->getName() : '',                      //收件人名�?
            16=>$shippingAddress ? self::replace($shippingAddress->getData("company")) : '',             //收件人公�?
            17=>$shippingAddress ? self::replace($this->getStreet1($shippingAddress)) : '',              //收件人地址1
            18=>$shippingAddress ? self::replace($this->getStreet2($shippingAddress)) : '',              //收件人地址2
            19=>$shippingAddress ? '`'.$shippingAddress->getData("postcode") : '',            //收件人邮�?
            20=>$shippingAddress ? self::replace($shippingAddress->getData("city")) : '',                //收件人城�?
            21=>$shippingAddress ? self::replace($shippingAddress->getRegion()) : '',                    //收件人州
            //23=>$shippingAddress ? $shippingAddress->getCountryModel()->getName() : '',   //收件人国�?
            22=>$telephone,           //收件人电�?
     		
			
			24=>'',
			25=>$dropship,
			26=>'',
			27=>'',
			28=>''
        );
    }
	
	private function destroyCurrency($amount) 
	{
		return str_replace('$','',$amount);
	}
	
	private function getStreet1($billingAddress)
	{
		$rawStreet = $billingAddress->getData("street");
		$sArray = explode("\n",$rawStreet);
		if(count($sArray)>1)
		{
			return $sArray[0];
		}
		else
		{
			return $rawStreet;
		}
	}
	private function getStreet2($billingAddress)
	{
		$rawStreet = $billingAddress->getData("street");
		$sArray = explode("\n",$rawStreet);
		if(count($sArray)>1)
		{
			return $sArray[1];
		}
		else
		{
			return "";
		}
	}
	
    /**
	 * Returns the item specific values.
	 * 
	 * @param Mage_Sales_Model_Order_Item $item The item to get values from
	 * @param Mage_Sales_Model_Order $order The order the item belongs to
	 * @return Array The array containing the item specific values
	 */
    protected function getOrderItemValues($item, $order, $itemInc=1) 
    {
        return array(
        		//$itemInc,
			6=>$item->getName(),    																	   //产品名称
			7=>$this->getItemSku($item),                                //SKU
			8=>(int)$item->getQtyOrdered(),                              //数量
			9=> self::destroyCurrency($this->formatPrice($item->getOriginalPrice(), $order)),   //价格
			10=>self::destroyCurrency($this->formatPrice($this->getItemTotal($item), $order)),  //小计
			//10=>$item->getWeight(),                                      //网重
			23=>$item->getStatus(),                                      //SKU状�?
        );
    }
	
	private function replace($subject)
	{
		$replacement = array(
			'original'	=> array('ǹ' , ' ã' , '°' , 'Ⅰ' , 'ª' , 'ª' , 'á' , 'à' , 'â' , 'ā' , 'ǎ' , 'ä' , 'å' , 'Å' , 'Á' , 'À' , 'Â' , 'Ä' , 'Ã' , 'Ç' , 'ç' , 'Ð' , 'É' , 'é' , 'È' , 'è' , 'Ê' , 'ê' , 'ë' , 'Ë' , 'ě' , 'ē' , 'ƒ' , 'í' , 'Í' , 'Í' , 'Ì' , 'ì' , 'Î' , 'Ï' , 'ï' , 'ǐ' , 'ī' , 'ń' , 'ň' , 'ñ' , 'Ñ' , 'ó' , 'Ó' , 'Ò' , 'ò' , 'Ô' , 'ô' , 'ö' , 'Ö' , 'ǒ' , 'ō' , 'Õ' , 'õ' , 'Š' , 'š' , 'þ' , 'Þ' , 'ú' , 'Ú' , 'Ù' , 'ù' , 'Û' , 'û' , 'Ü' , 'ü' , 'ǔ' , 'ū' , 'ǘ' , 'ǜ' , 'ǚ' , 'ǖ' , 'ý' , 'Ý' , 'ÿ' , 'Ÿ' , 'ÿ' , 'Θ' , 'Λ' , 'ã' , 'º' , 'ё' , 'Ć' , 'ć' , 'č' , 'ė' , '№ ' , 'Ø'),
			
			'standard'	=> array('n' , 'a' , 'o' , 'I' , 'a' , 'a' , 'a' , 'a' , 'a' , 'a' , 'a' ,'a' , 'a' , 'A' , 'A' , 'A' , 'A' , 'A' , 'A' , 'C' , 'c' , 'D' , 'E' , 'e' , 'E' , 'e' , 'E' , 'e' , 'e' , 'E' , 'e' , 'e' , 'f' , 'i' , 'i' , 'i' , 'i' , 'i' , 'i' , 'i' , 'i' , 'i' , 'i' , 'n' , 'n' , 'n' , 'N' , 'o' , 'O' , 'O' , 'o' , 'O' , 'o' , 'o' , 'O' , 'o' , 'o' , 'O' , 'o' , 'S' , 's' , 'p' , 'P' , 'u' , 'U' , 'U' , 'u' , 'U' , 'u' , 'U' , 'u' , 'U' , 'u' , 'u' , 'u' , 'u' , 'u' , 'y' , 'Y' , 'y' , 'Y' , 'y' , 'o' , 'A' , 'a' , 'o' , 'e' , 'C' , 'c' , 'c' , 'e' , 'N' , 'o')
		);
		$str = str_replace($replacement['original'] , $replacement['standard'] , $subject);
		$str = str_replace(array('º') , '' , $str);
		return $str;
	}
	
}