<?php
abstract class Mivec_Catalog_Product_Price_Abstract extends Mivec_Abstract
{
	protected $_rule = array(); //公式规则,数组
	protected $_weightRule = array(); // 重量规则
	
	protected $_exchange = 6.2; //汇率
	protected $_dir;
	
	protected function init()
	{
		$this->_dir = dirname(__FILE__) . '/Config';
	}
	
	protected function _initRuleR()
	{
		self::init();
		if ( $content = file_get_contents($this->_dir . '/Formula') ) {
			$arr = parent::splitCsvContent($content);
			foreach ( $arr as $key => $val) {
				//,号切割
				$tmp = split(',',$val);
				if ( strpos($tmp[0],'-') && $cost = split('-',$tmp[0]) ) {
					$r[] = array(
						'start'	=> $cost[0],
						'end'	=> $cost[1],
						'profit'	=> $tmp[1], //利润率
						'constant'	=> $tmp[2], // 批发零售差-常数
						'vip'	=> $tmp[3], // VIP常数,递减
					);
				}
			}
			$this->_rule = $r;
		}
	}
	
	//override
	protected function _initRule()
	{
		self::init();
		if ($content = file_get_contents($this->_dir . '/Formula.110719')) {
			$arr = parent::splitCsvContent($content);
			foreach ( $arr as $key => $val) {
				//,号切割
				$tmp = split(',',$val);
				if ( strpos($tmp[0],'-') && $cost = split('-',$tmp[0]) ) {
					$r[] = array(
						'start'	=> $cost[0],
						'end'	=> $cost[1],
						'profit'	=> $tmp[1], //利润率
						//'constant'	=> $tmp[2], // 批发零售差-常数
						'retail'	=> array(
							1	=> $tmp[2], //零售
							5	=> $tmp[3], // 5 pcs
							3	=> $tmp[4], 
						),
						'silver'	=> $this->convert($tmp[5]),
						'gold'	=> $this->convert($tmp[6]),
						'diamond'	=> $this->convert($tmp[7]),
					);
				}
			}
			//print_r($r);
			$this->_rule = $r;
		}
	}
	
	//从百分比转换成小数
	protected function convert($v) 
	{
		return 1 - str_replace('%' , '' , $v) / 100;
	}
	//重量规则
	protected function _initWeight()
	{
		self::init();
		if ( $content = file_get_contents($this->_dir . '/Weight') ) {
			$arr = parent::splitCsvContent($content);
			$this->_weightRule = $arr;
		}
	}
}