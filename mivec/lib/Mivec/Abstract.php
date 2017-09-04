<?php
abstract class Mivec_Abstract extends Mage_Core_Model_Abstract
{
	protected function splitCsvContent($content)
	{
		if (strpos($content,"\r") && $tmp = split("\r",$content)) {
			$i = 1;
			foreach ($tmp as $key=>$c) {
				//$val = str_replace("\r",'',$c);
				$val = trim($c);
				if ($key > 0 && !empty($val)) {
					$arr[] = $val;
					$i++;
				}
			}
			return $arr;
		}
	}
}