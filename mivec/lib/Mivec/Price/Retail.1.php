<?php
class Mivec_Price_Retail extends Mivec_Price_Abstract
{
	protected $cost; //产品成本
	protected $weight; //产品重量
	protected $ship; //运费 用在总成本超过200的时候
	
	protected $exchange = 6.7;
	
	protected $retailArr;
	protected $costOver;
	
	public function __construct($file)
	{
		//declare variable
		self::initFormula($file);
	}
	
	protected function initFormula($file)
	{
		$content = file_get_contents($file);
		if ($arr = parent::splitCsvContent($content)) {
			
			foreach ($arr as $val) {
				$tmp = split(',',$val);
				if (strpos($tmp[0],'-') && $cost = split('-',$tmp[0])) {
					$parr[] = array(
						'cost'	=> $cost[1],
						'cardinal'	=> $tmp[1],
						'silver' => $tmp[2],
						'gold'	=> $tmp[3],
						'diamond'	=> $tmp[4]
					);
				}
			}
			$this->retailArr = $parr;
		}
	}
	
	//如果成本>200,则成本需要重新计算
	protected function costOver($cost,$weight)
	{
		$this->costOver->field = array('150','200','250','300','500','600','800','10000','2000','3000');
		$this->costOver->value = array(
			'150'	=> '',
			'200'	=> 60,
			'250'	=> 75,
			'300'	=> 100,
			'500'	=> 125,
			'600'	=> 135,
			'800'	=> 170,
			'1000'	=> 200,
			'2000'	=> 300,
			'3000'	=> 500
		);
		$this->costOver->cardinal = 1.06;////超过200的时候需要*这个值
		$i = 0;
		foreach ($this->costOver->field as $key=>$o) {
			$n = @$this->costOver->field[$i + 1];
			if (($cost >= $o) && ($cost < $n)) {
				$ship = 110 * $weight + 1;
				$cost = $cost + $this->costOver->value[$o] + $ship * $this->costOver->cardinal;
				break;
			}
			$i++;
		}
		return $cost;
	}
	
	public function calculate($cost,$weight,$method="150")
	{
		//产品成本大于200的时候+
		//$cost = self::costOver($cost,$weight);
		
		self::setFinalCost($cost,$weight);
		$this->weight = $weight;
		
		if ($ruleArr = $this->retailArr) {
			$customer = array();
			//if ($this->cost > $ruleArr[count($ruleArr) - 1]['cost']) {
			if ($cost > 199) {
				$customer['silver'] = $ruleArr[count($ruleArr) - 1]['silver'];
				$customer['gold'] = $ruleArr[count($ruleArr) - 1]['gold'];
				$customer['diamond'] = $ruleArr[count($ruleArr) - 1]['diamond'];

				$cardinal = 1.06;
				$price = self::costOver($this->cost,$this->weight);
				
			}elseif ($cost < 200){
				//print_r($ruleArr);
				$i = 0;
				foreach ($ruleArr as $key=>$val) {
					if ($cost >= $val['cost'] && ($cost < $ruleArr[$key + 1]['cost'])) {
						$cardinal = $ruleArr[$key + 1]['cardinal'];
						
						$customer['silver'] = $ruleArr[$key+1]['silver'];
						$customer['gold'] = $ruleArr[$key+1]['gold'];
						$customer['diamond'] = $ruleArr[$key+1]['diamond'];
						
						$price = $this->cost * $cardinal;
					}
					$i++;
				}
			}
			$price = $price / $this->exchange;
			$price = round($price,2);
					
			$arr = array(
				'cost'	=> $this->cost,
				'cardinal'	=> $cardinal,
				'price'	=> $price,
				'silver'	=> $price - $customer['silver'],
				'gold'	=> $price - $customer['silver'] - $customer['gold'],
				'diamond'	=> $price - $customer['gold'] - $customer['silver'] - $customer['diamond'],
			);
			return $arr;
		}
	}
	
	protected function setFinalCost($cost,$weight)
	{
		if ($cost <= 199) {
			$this->cost = $cost + 110 * $weight + 1;
		}else{
			$this->cost = $cost;
		}
	}
	
	protected function calculateForCustmer()
	{
		
	}
}