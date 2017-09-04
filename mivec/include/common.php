<?php
function is_crawler() {
	$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']); 
	$spiders = array( 
		'Googlebot', // Google 爬虫 
		'Baiduspider', // 百度爬虫 
		'Yahoo! Slurp', // 雅虎爬虫 
		'YodaoBot', // 有道爬虫 
		'msnbot', // Bing爬虫
		'facebookexternalhit',
		'YandexBot',
		'Feedfetcher'
	); 
	foreach ($spiders as $spider) {
		$spider = strtolower($spider); 
		if (strpos($userAgent, $spider) !== false) { 
			return true; 
		} 
	}
	return false; 
}

//default timezone = Hongkong
function dateFormat($_date , $_dateZone = 'Hongkong')
{
	//Hongkong = UTC + 8
	if ($_dateZone == 'Hongkong') {
		$_date = date('Y-m-d H:i' , strtotime($_date) + (8 * 3600));
	}elseif ($_dateZone == 'UTC') {
		$_date = date('Y-m-d H:i' , strtotime($_date) - (8 * 3600));
	}
	return $_date;
}

function jsLocation($msg='' , $url='') 
{
	$str = '<script type="text/javascript">';
	$str .= !empty($msg) ? "alert('$msg');" : '';
	$str .= !empty($url) ? "window.location.href='$url'" : 'history.back(-1)';
	$str .= '</script>';
	echo $str;
	exit;
}

function rands($size) {
	for($i=0;$i<$size;$i++) {
		$num.= rand(0,9);
	}
	return $num;
}

function charReplace($str) {
	$str = str_replace('&','',$str);
	//$str = str_replace(' ','',$str);
	$str = str_replace(',',' ',$str);
	return $str;
}

function dhtmlspecialchars($string) { 
	if(is_array($string)) { 
		foreach($string as $key => $val) { 
			$string[$key] = dhtmlspecialchars($val); 
		} 
	} else { 
		//$string = preg_replace("@<(.*?)>@is","",$string);
		$string = preg_replace('/&((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1',
			str_replace(array("\r",chr(10),'&', '"', '<', '>','&nbsp;','é','Nü','（','）','#','amp;','160;',','), '', $string)
		); 
	} 
	return $string; 
}

function htmlFilter($str) {
	return preg_replace("@<(.*?)>@is","",$str);
}

function numberToArray($start,$end) {
	$arr = array();
	$start = trim($start);
	$end = trim($end);
	
	for(;$start <= $end;$start++) {
		$arr[] = $start;
	}
	return $arr;
}

function multiDir($dirs) {
    if (strpos($dirs,"/") !==false){
		$tmpArr = explode('/',$dirs);
		$_dir = '';
    	foreach ($tmpArr as $tmpDir) {
    		$_dir.= $tmpDir."/";
    		if (!file_exists($_dir)) {
    			return mkdir($_dir,0777);
    		}
    	}
    } else {
    	if (!file_exists($dirs))
			return mkdir($dirs,0777);
    }
}

function getImage($url,$filename="") {
	if(!$url) return false;
	if(!$filename) {
		$ext = strrchr(strtolower($url),".");
		if($ext!=".gif" && $ext!=".jpg" && $ext!=".png") return false;
	}
	ob_start();
	readfile($url);
	$img = ob_get_contents();
	ob_end_clean();
	
	$fp2=@fopen($filename, "wb");
	fwrite($fp2,$img);
	fclose($fp2);
  	return $filename;
}

function getDirByImage($img)
{
	$_split = split('/',$img);
	$dir = $_split[1] . '/' . $_split[2];
	return $dir;
}

function mkdirByProductImage($img)
{
	$_split = split('/',$img);
	$dir = $_split[1] . '/' . $_split[2];
	if (multiDir($dir)) {
		return true;
	}
}

function arrayToStr($arr,$deno=',') {
	if ($arr) {
		$str = "";
		foreach ($arr as $_val) {
			$str .= $_val . $deno;
		}
		$str = trim($str,$deno);
		return $str;
	}
}

function strToArray($str,$deno=',') {
	if ($s = split($deno,$str)) {
		$arr = array();
		foreach ($s as $v) {
			$arr[] = $v;
		}
		return $arr;
	}else{
		$arr = array($str);
	}
}

function upload($input,$fileName,$dir="")
{
	$dir = !empty($dir) ? $dir : dirname(__FILE__) . '/data';
	if ($file = @$_FILES[$input]) {
		//$file_name = $_FILES[$input]['name'];
		$file_name = !empty($fileName) ? $fileName : $_FILES[$input]['name'];
		$file_tmp = $_FILES[$input]['tmp_name'];
		
		$toFile = $dir . '/' . $file_name;
		
		if (move_uploaded_file($file_tmp, $toFile)) {
			return $file_name;
		}
	}
}

function splitCsvContent($content , $header = false)
{
	if (strpos($content,"\r") && $tmp = split("\r",$content)) {
		$i = 1;
		foreach ($tmp as $key => $c) {
			$val = trim($c);
			if ($header) {
				$arr[] = $val;
				$i++;
			}else{
				if ($key > 0 && !empty($val)) {
					$arr[] = $val;
					$i++;
				}
			}
		}
		return $arr;
	}
}

function getCsvContent($file , $header = false)
{
	if (($handle = fopen($file, "r")) !== FALSE) {
		$i = 0;
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			if ($header) {
				$csv[] = $data;
			}
			else {
				if ($i > 0) {
					$csv[] = $data;
				}
			}
			$i++;
		}
		return $csv;
		fclose($handle);
	}
}

function createFCKeditor($value="",$name="content",$width="100%",$height=350,$toolbar="Default")
{
	//include "/js/fontis/fckeditor/fckeditor.php";
	include $_SERVER['DOCUMENT_ROOT'] .'/js/fontis/fckeditor/fckeditor.php';
	
    $oFCKeditor = new FCKeditor('FCKeditor1');
    $oFCKeditor->ToolbarSet = "$toolbar";
    $oFCKeditor->BasePath = 'http://'.$_SERVER['HTTP_HOST'] . "/js/fontis/fckeditor/";
    $oFCKeditor->InstanceName = $name;
    $oFCKeditor->Value = $value;
    $oFCKeditor->Width = $width;
    $oFCKeditor->Height = $height;
    return $oFCKeditor->Create();
	//return $oFCKeditor->CreateHtml();
}

function convertToCount($sql)
{
	//split to get count
	$rowsql = preg_replace('/SELECT (.*?) FROM/is','SELECT COUNT(*) FROM',$sql);
	$rowsql = preg_replace('/(ORDER BY *.+\w+)/is','',$rowsql);
	return $rowsql;
}

function pager($config , $page)
{
	global $db;
	Zend_Loader::loadClass('Mivec_Page_Pager');
	//require "Mivec/Page/Pager.php";
	
	$limit = intval($config['limit']) ? $config['limit'] : 50;
	$offset = ($page - 1) * $limit;

	$sql = $config['sql'];
	$sql .= ' LIMIT ' . $offset . ' , ' . $limit;

    //echo $sql;
	$rowsql = convertToCount($sql);
	$row = $db->fetchAll($sql);
	
	$page = new Mivec_Page_Pager($db->fetchOne($rowsql),$limit);
	return array(
		'row'		=> $row,
		'page'		=> $page->getNavigation(),
		'sql'		=> $sql
	);
}

//form select
function formText($_name , $_value , $_class = 'input-text')
{
	$str = "<label id='$_name'><input type='text' name='$_name' value='$_value' class='$_class' />";
	return $str;
}

function formSelect($_name, $_data , $_value = '')
{
	$str = "<label id='$_name'><select id='$_name' name='$_name'><option value=0> -- Please Select -- </option>";
	
	foreach ($_data as $key => $d) {
		$str .= "<option value='$key'";
		if ($key == $_value) {
			$str .= ' selected="selected"';
		}
		$str .= ">$d</option>\r";
	}
	$str .= "</select><label>";
	return $str;
}

//hold
function generationPage($config = array(),$page)
{
	Zend_Loader::loadClass('Mivec_Db_Page');
	
	$limit = intval($config['limit']) ? $config['limit'] : 5;
	
	$offset = ($page - 1) * $limit;
	$select = $this->select;
	$select->from($config['table'],'*')
		->order($config['order'])
		->limit($limit,$offset);
	
	if (count($config['params']) > 1) {
		foreach ($config['params'] as $_param) {
			$select->where($_param);
		}
	}elseif (!empty($config['params'])){
		$select->where($config['params']);
	}
	unset($sql);
	
	$sql = $select->__toString();
	//echo $sql;exit;
	
	$rowsql = self::convertToCount($sql);
	$result = $this->db->fetchAll($select->__toString());
	$page = new Mivec_Db_Page($this->db->fetchOne($rowsql),$limit);
	
	return array(
		'result'	=> $result,
		'pager'		=> $page->getNavigation()
	);
}

?>