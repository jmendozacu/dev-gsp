<?php
class Mivec_Price_Wholesale extends Mivec_Price_Abstract
{
	protected $cost;
	protected $weight;
	//protected $qty;
	
	protected $_definedPrice;
	protected $_paramValue;
	protected $_cardinal; //基数
	protected $_formula; //公式
	protected $_value = 1.06; //固定值
	protected $_exchange = 6.7;
	
	public function __construct($file,$cost,$weight)
	{
		$this->cost = $cost;
		$this->weight = $weight;
		self::init($file);
		//self::initFormula();
	}
	
	protected function init($file)
	{
		$content = file_get_contents($file);
		if ($arr = parent::splitCsvContent($content)) {
			
			foreach ($arr as $val) {
				$tmp = split(',',$val);
				if (strpos($tmp[0],'-') && $cost = split('-',$tmp[0])) {
					$parr[] = array(
						'cost'	=> $cost[1],
						'cardinal'	=> $tmp[1],
						//'border'	=> 0.03
					);
				}
			}
			$this->_paramValue = $parr;
		}
	}
	
	public function calculate()
	{
		//如果600、1000以上则用加法
		$this->_definedPrice = array(
			'600'=>100,
			'1000' => 130
		);
		
		//基本公式
		$this->_formula = 110 * $this->weight;
		
		if ($this->_paramValue) {
			//如果成本600以上和1000以上
			$i[0] = 600;
			$i[1] = 1000;
			if (($this->cost > $i[0] || $this->cost > $i[1]) && $this->cost < 1500) {
				$v = $this->cost > 1000 ? 130 : 100;
				$this->_formula = $this->_formula + $this->cost + $v;
				
			}elseif ($this->cost >= 1500){ //如果大于1500
				$this->_formula = $this->_formula + $this->cost * 1.1;
			}else{
				//常规公式
				//print_r($this->_paramValue);
				$i = 0;
				foreach ($this->_paramValue as $key=>$val) {
					$n = @$this->_paramValue[$i + 1];
					if ($this->cost >= $val['cost'] && $n['cost'] > $this->cost) {
						$this->_formula = $this->_formula + $this->cost * $val['cardinal'];
					}
					$i++;
				}
			}
			$this->price = $this->_formula * 1.06 / $this->_exchange + 0.03;
			$this->price = round($this->price,2);
			return $this->price;
		}
	}
}