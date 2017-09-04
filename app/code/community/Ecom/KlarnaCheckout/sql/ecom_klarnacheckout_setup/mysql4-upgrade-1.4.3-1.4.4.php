<?php 

$installer = $this;
$installer->startSetup();
 
$installer->run("

CREATE TABLE `{$installer->getTable('klarnacheckout/lock')}` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`quote_id` INT NOT NULL,
	`time` DATETIME NOT NULL
) ENGINE = InnoDB ;

");
 
$installer->endSetup();