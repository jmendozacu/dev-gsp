<?php 

$installer = $this;
$installer->startSetup();
 
$installer->run("

CREATE TABLE `{$installer->getTable('klarnacheckout/validationlog')}` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`quote_id` INT NOT NULL,	
	`messages` text NOT NULL,
	`time` DATETIME NOT NULL
) ENGINE = InnoDB ;

");
 
$installer->endSetup();