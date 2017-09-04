<?php
abstract class Mivec_Price_Abstract extends Mivec_Abstract
{
	protected $rule = array(); //公式规则,数组
	protected $_exchange = 6.4; //汇率
	
	protected function initRule($file,$method='retail')
	{
		$content = file_get_contents($file);
		if ($arr = parent::splitCsvContent($content)) {
			foreach ($arr as $val) {
				$tmp = split(',',$val);
				if (strpos($tmp[0],'-') && $cost = split('-',$tmp[0])) {
					if ($method == 'retail') {
						$parr[] = array(
							'start'	=> $cost[0],
							'end'	=> $cost[1],
							'cost'	=> $cost[1],
							'cardinal'	=> $tmp[1],
							'silver' => $tmp[2],
							'gold'	=> $tmp[3],
							'diamond'	=> $tmp[4]
						);
					} else {
						$parr[] = array(
							'start'	=> $cost[0],
							'end'	=> $cost[1],
							'cost'	=> $cost[1],
							'cardinal'	=> $tmp[1]
						);
					}
				}
			}
			$this->rule = $parr;
		}
	}
}