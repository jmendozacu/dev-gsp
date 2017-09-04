<?php
class Mivec_Price_Retail extends Mivec_Price_Abstract
{
	protected $cost; // 产品成本
	protected $weight; // 产品重量
	protected $ship;
	protected $price; // 最终价格
	
	protected $costOver;
	
	public function __construct($file,$cost,$weight)
	{
		$this->cost = $cost;
		$this->weight = $weight;
		
		parent::initRule($file);
		
		$this->ship = 110 * $this->weight + 1;
		$this->costOver = new stdClass; // it's object for item price > 200
	}
	
	public function calculate()
	{
		$this->costOver->value = array('200'=>60,'250'=>75,'300'=>100,'500'=>125,'600'=>135,'800'=>170,'1000'=>200,'2000'=>300,'3000'=>500); //成本大于200时使用
		
		if ($rule = $this->rule) {
			$customer = "";
			foreach ($rule as $key=>$r) {
				//总成本
				$total = $this->cost + $this->ship;
				
				if ($total >= $r['start'] && $total < $r['end']) {
					//大于=200的计算方法
					if ($this->cost >= 200) {
						$additionalPrice = $this->costOver->value[$r['start']];
						$price = ($this->cost + $additionalPrice + $this->ship) * $r['cardinal'];
						
						$customer['silver'] = $r['silver'];
						$customer['gold'] = $r['gold'];
						$customer['diamond'] = $r['diamond'];

					}else{
						//常规计算方法
						$additionalPrice = $r['cardinal'];
						$price = ($total) * $additionalPrice;
						
						$customer['silver'] = $r['silver'];
						$customer['gold'] = $r['gold'];
						$customer['diamond'] = $r['diamond'];
						
						//echo $additionalPrice;
					}
					break;
				}
			}
			$price = $price / $this->_exchange;
			$price = round($price,2);
			$this->price = $price;
			
			$arr = array(
				'cost'	=> $this->cost,
				'additional'	=> $additionalPrice,
				'weight'	=> $this->weight,
				'price'	=> $price,
				'silver'	=> $price - $customer['silver'],
				'gold'	=> $price - $customer['silver'] - $customer['gold'],
				'diamond'	=> $price - $customer['gold'] - $customer['silver'] - $customer['diamond'],
			);
			return $arr;
		}
	}
}