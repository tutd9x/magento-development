<?php
/**
 * Created by PhpStorm.
 * User: Ngoc Tu
 * Date: 1/9/2015
 * Time: 9:46 AM
 */
Class MW_SimpleNews_IndexController extends Mage_Core_Controller_Front_Action
/*
 * Module name: MV_SimpleNews
 * Controller name: Index
 * URL: index.php/frontendname/actionControllername/actionmethod = index.php/simplenews/index/index
 *  */
{
    public function indexAction()
    {
        echo "Hello World";
        $this->loadLayout();
        $this->renderLayout();

    }

    public function testAction(){
        /* url: index.php/simplenews/index/test */
        echo "Hello Test World";
    }
}