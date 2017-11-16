<?php
      require_once 'app/Mage.php';
      $app = Mage::app('admin');
      umask(0);

      error_reporting(E_ALL & ~E_NOTICE);
      if (Mage::app()->getCacheInstance()->flush()) {
          echo Mage::helper('adminhtml')->__("The cache storage has been flushed.");
      }
?>
