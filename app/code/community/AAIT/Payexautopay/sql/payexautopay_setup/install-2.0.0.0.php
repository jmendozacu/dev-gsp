<?php
$installer = $this;
$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS `payex_autopay`;
CREATE TABLE IF NOT EXISTS `payex_autopay` (
  `customer_id` int(10) NOT NULL,
  `agreement_id` varchar(255) NOT NULL DEFAULT '',
  UNIQUE KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");
$installer->endSetup();