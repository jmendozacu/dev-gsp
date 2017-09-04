<?php

/**
 *
 */
class Evalent_StoreSetup_Model_Moduleinstaller_Evalent_Blog extends Evalent_StoreSetup_Model_Moduleinstaller_Abstractinstaller {

    public $_version = '0.0.1';

    /**
     * Do all you need to do
     */
    public function run(){

        $allow = array(
            "admin/blog",
            "admin/blog/author",
            "admin/blog/author/save",
            "admin/blog/author/save_others",
            "admin/blog/category",
            "admin/blog/category/delete",
            "admin/blog/category/save",
            "admin/blog/post",
            "admin/blog/post/author",
            "admin/blog/post/delete",
            "admin/blog/post/save",
            "admin/blog/post/save_others",
            "admin/blog/post_comment",
            "admin/blog/post_comment/delete",
            "admin/blog/post_comment/save",
            "admin/blog/tag",
            "admin/blog/tag/delete",
            "admin/blog/tag/save",
            "admin/system/config/blog"
        );

        foreach($allow as $rule) $this->_updateRule($rule,'allow');

        return true;
    }
}