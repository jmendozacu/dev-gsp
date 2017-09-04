<?php

# import cms-blocks and pages from ultimo

$overwrite = true;
Mage::getSingleton('ultimo/import_cms')->importCmsItems('cms/block', 'blocks', $overwrite);
Mage::getSingleton('ultimo/import_cms')->importCmsItems('cms/page', 'pages', $overwrite);