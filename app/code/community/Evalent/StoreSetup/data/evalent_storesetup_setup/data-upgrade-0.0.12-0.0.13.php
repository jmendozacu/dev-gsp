<?php
/**
 * Set logo urls for theme
 */
Mage::getModel("core/config")->saveConfig("design/header/logo_src", "wysiwyg/logos/logo.png", 'default', 0);
Mage::getModel("core/config")->saveConfig("design/header/logo_src_small", "wysiwyg/logos/logo-mobile.png", 'default', 0);