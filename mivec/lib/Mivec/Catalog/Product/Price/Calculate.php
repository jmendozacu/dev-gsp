<?php
class Mivec_Catalog_Product_Price_Calculate extends Mivec_Catalog_Product_Price_Abstract
{
	protected $_cost; // 产品成本
	protected $_weight; // 产品重量
	protected $_ship;  //运费
	protected $_tenth; //10pcs 批发价
	protected $_wholesale; //批发价
	protected $price; // 最终价格
	
	public function __construct($cost="",$weight="")
	{
		parent::_initRule();
		$this->_cost = $cost;
		$this->_weight = $weight;
	}
	
	public function calculate()
	{
		if ( $rule = $this->_rule ) {
			//重构重量
			$this->_weight = self::setWeight($this->_weight);
			//echo $this->_weight;
			foreach ( $rule as $key => $r ) {
				if ( $this->_cost >= $r['start'] && $this->_cost < $r['end'] ) {
					$this->_ship = ( 102 * $this->_weight);
					//10pcs的价格为: （成本*利润率+运费）*1.06/汇率
					$this->_tenth = round( ( $this->_cost * $r['profit'] + $this->_ship) * 1.06 / $this->_exchange , 2 );
					//批发价
					$wholesale = array(
						'10'	=> $this->_tenth,
						'5'	=> $this->_tenth + 0.1,
						'3'	=> $this->_tenth + 0.2
					);
					
					//零售价,公式:10pcs + 批发零售差 + 0.3
					$retail = $this->_tenth + $r['constant'] + 0.3;
					
					//VIP零售价,递减
					$vPrice = $retail - $r['vip'];
					$vip = array(
						'silver'	=> $vPrice,
						'gold'	=> $vPrice - $r['vip'],
						'diamond'	=> $vPrice - ( $r['vip'] * 2 )
					);
					
					$this->price = array(
						'retail'	=> $retail,
						'wholesale'	=> $wholesale,
						'vip'	=> $vip
					);
				}
			}
			return $this->price;
		}
	}
	
	
	public function exportResult()
	{
		if ( $rule = $this->_rule ) {
			//重构重量
			$this->_weight = self::setWeight($this->_weight);
			//echo $this->_weight;
			foreach ( $rule as $key => $r ) {
				if ( $this->_cost >= $r['start'] && $this->_cost < $r['end'] ) {
					$this->_ship = ( 100 * $this->_weight);
				//	echo $this->_ship;
					
					//10pcs的价格为: （成本*利润率+运费）*1.06/汇率
					//echo ($this->_cost * $r['profit'] + $this->_ship + 1) * 1.06;

					$this->_tenth = round( ( $this->_cost * $r['profit'] + $this->_ship + 1) * 1.06 / $this->_exchange , 2 );
					
					//批发价
					$wholesale = array(
						'10'	=> $this->_tenth,
						'5'	=> $this->_tenth + $r['retail'][5],
						'3'	=> $this->_tenth + $r['retail'][3]
					);
					
					//零售价,公式:10pcs + 批发零售差 + 0.3
					//$retail = $this->_tenth + $r['constant'] + 0.3;
					$retail = $this->_tenth + $r['retail'][1];
					
					//tier price
					$vip['silver'] = round($retail * $r['silver'] , 2);
					$vip['gold'] = round($retail * $r['gold'] , 2);
					$vip['diamond'] = round($retail * $r['diamond'] , 2);
					
					$this->price = array(
						'retail'	=> $retail,
						'wholesale'	=> $wholesale,
						'vip'	=> $vip
					);
				}
			}
			//print_r($this->price);
			return $this->price;
		}
	}
	
	//从零售价得到10/5/3 pcs的价格
	public function getPriceByRetail($current_price)
	{
		$_formula = 'Formula.retail';
		if ( $content = file_get_contents($this->_dir . "/$_formula")) {
			$arr = parent::splitCsvContent($content);
			foreach ( $arr as $key => $val) {
				//,号切割
				$tmp = split(',',$val);
				if ( strpos($tmp[0],'-') && $retail = split('-',$tmp[0])) {
					$rule[] = array(
						'start'	=> $retail[0],
						'end'	=> $retail[1],
						'constant'	=> $tmp[1], // 批发零售差-常数
						'vip'	=> $tmp[2], // VIP常数,递减
					);
				}
			}
			foreach ($rule as $r) {
				if ($current_price >= $r['start'] && $current_price < $r['end']) {
					$define = 0.3; //常数-0.3
					//wholesale
					$tenth = $current_price - $r['constant'] - $define; //10个批发价
					$wholesale = array(
						10	=> $tenth,
						5	=>	$tenth + 0.1,
						3	=>	$tenth + 0.2
					);
					
/*					$diamond = $tenth + $r['vip'];
					$vip = array(
						'silver'	=> $diamond + ($r['vip'] * 3),
						'gold'	=> $diamond + ( $r['vip'] * 2 ),
						'diamond'	=> $diamond
					);*/
					
					$silver = $tenth + $r['vip'] * 3;
					$vip = array(
						'silver'	=> $silver,
						'gold'	=> $silver - $r['vip'],
						'diamond'	=> $silver - $r['vip'] * 2
					);
					
					$price = array(
						'wholesale'	=> $wholesale,
						'vip'	=> $vip
					);
				}
			}
			return $price;
		}
	}
	
	public function setWeight($current_weight)
	{
		parent::_initWeight();
		if ( $weight = $this->_weightRule ) {
			//print_r($weight);
			$w = '';
			foreach ( $weight as $key => $val) {
				//,先拆分
				$v = split(',',$val);
				//如果有-,则拆分
				if ( strpos($v[0],'-') ) {
					$t = split('-',$val);
					//print_r($t);
					if ( $current_weight >= $t[0] && $current_weight <= $t[1]) {
						$w = $v[1];
						break;
					}
				}elseif ($val == $current_weight){
					$w = $v[1];
					break;
				}
			}
			return !empty($w) ? $w : $current_weight;
		}
	}
}