<?php
$installer = $this;

$installer->startSetup();

$options =  array("type"=>"varchar","required"=>false);
$boptions = array("type"=>"boolean","required"=>true,"default" => 0 ,"grid" => true);
	
$installer->addAttribute("quote", "repair_imei", $options);
$installer->addAttribute("quote", "repair_problem", $options);
$installer->addAttribute("quote", "repair_pincode", $options);
$installer->addAttribute("quote", "repair_screencode",$options);
$installer->addAttribute("quote", "repair_extracodes",$options);
$installer->addAttribute("quote", "repair_isrepair", $boptions);

$installer->addAttribute("order", "repair_imei", $options);
$installer->addAttribute("order", "repair_problem",$options);
$installer->addAttribute("order", "repair_pincode",$options);
$installer->addAttribute("order", "repair_screencode",$options);
$installer->addAttribute("order", "repair_extracodes", $options);
$installer->addAttribute("order", "repair_isrepair", $boptions);

$installer->endSetup();