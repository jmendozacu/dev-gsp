<?php 

$installer = $this;
$installer->startSetup();
 
$installer->run("

CREATE TABLE `{$installer->getTable('klarnacheckout/quotemap')}` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`quote_id` INT NOT NULL,	
	`order_id` VARCHAR(256) NOT NULL
) ENGINE = InnoDB ;

");
 
$installer->endSetup();