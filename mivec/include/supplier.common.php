<?php
function getSupplier()
{
	global $db;
	$sql = "SELECT * FROM mivec_erp_supplier ORDER BY id DESC";
	if ($row = $db->fetchAll($sql)) {
		$arr = array();
		foreach ($row as $rs) {
			$arr[$rs['id']] = $rs['name'];
		}
		return $arr;
	}
}

function getSupplierQuote($_productId)
{
	global $db;
	
	$_supplier = getSupplier();

	$sql = "SELECT DISTINCT(supplier_id),price,updated_at FROM mivec_erp_supplier_quote WHERE product_id=$_productId ORDER BY id DESC";
	
	if ($row = $db->fetchAll($sql)) {
		$arr = array();
		foreach ($row as $rs) {
			$arr[] = array(
				"product_id"	=> $_productId,
				"supplier_id"	=> $rs['supplier_id'],
				"name"	=> $_supplier[$rs['supplier_id']],
				'price'			=> $rs['price']
			);
		}
		return $arr;
	}
}

function unserializeQuote($arr)
{
	if (is_array($arr)) {
		$str = "";
		foreach ($arr as $r) {
			$str .= $r['name'] . "&nbsp;" . $r['price'];
			$str .= "</br>";
		}
		return $str;
	}
}