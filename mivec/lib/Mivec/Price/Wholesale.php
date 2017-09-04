<?php
class Mivec_Price_Wholesale extends Mivec_Price_Abstract
{
	protected $cost;
	protected $weight;
	
	protected $additional;
	
	protected $_ship;
	
	protected $price;
	public function __construct($file,$cost,$weight)
	{
		$this->cost = $cost;
		$this->weight = $weight;
		
		parent::initRule($file,'wholesale');
		self::initAdditional();
	}
	
	protected function initAdditional()
	{
		$this->additional = array(1.06,0.03);
		
		$this->_ship = 110 * $this->weight;
	}
	
	public function calculate()
	{
		//如果600、1000以上则用加法
		$this->_definedPrice = array(
			'600'=>100,
			'1000' => 130
		);
		
		if ($rule = $this->rule) {
			//成本600-1000,1000-15000 特别处理
			if (($this->cost >= 600 && $this->cost < 1000) || ($this->cost >= 1000 && $this->cost < 1500) || $this->cost > 1500) {
				if ($this->cost >= 600 && $this->cost < 1000) {
					$price = ($this->cost + 100 + $this->_ship);
				}elseif($this->cost >= 1000 && $this->cost < 1500) {
					$price = $this->cost + 130 + $this->_ship;
				}elseif ($this->cost > 1500){
					$price = ($this->cost * 1.1 + $this->_ship);
				}
			}else{
				foreach ($rule as $key=>$r) {
					if ($this->cost >=$r['start'] && $this->cost < $r['end']) {
						$price = $this->cost * $r['cardinal'] + $this->_ship;
						break; 
					}
				}
			}
			$price = $price * 1.06 / $this->_exchange + 0.03;
			$price = round($price,2);
			return $price;
		}
	}
	
}