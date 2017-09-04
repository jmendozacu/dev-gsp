<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Lanot
 * @package     Lanot_FileManager
 * @copyright   Copyright (c) 2012 Lanot
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lanot_FileManager_Block_Adminhtml_Content_Uploader
    extends Mage_Adminhtml_Block_Cms_Wysiwyg_Images_Content_Uploader
{
    public function __construct()
    {
        parent::__construct();
        $params = $this->getConfig()->getParams();
        $type = $this->_getMediaType();

        $allowed = Mage::getSingleton('lanot_filemanager/storage')->getAllowedExtensions($type);
        $labels = array();
        $files = array();

        if (!is_array($allowed)) {
            $labels[] = '.*';
            $files[] = '*.*';
        } else {
            foreach ($allowed as $ext) {
                $labels[] = '.' . $ext;
                $files[] = '*.' . $ext;
            }
        }

        $this->getConfig()
            ->setUrl(Mage::getModel('adminhtml/url')->addSessionParam()->getUrl('*/*/upload', array('type' => $type)))
            ->setParams($params)
            ->setFileField('image')//@todo: post key
            ->setFilters(array(
                'files' => array(
                    'label' => $this->helper('cms')->__('Files (%s)', implode(', ', $labels)),
                    'files' => $files
                )
            ));
    }
}
