<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * description
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace MW\FreeGift\Block\Adminhtml\Promo\Catalog\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('promo_catalog_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Catalog Price Rule'));
    }
//    protected function _beforeToHtml()
//    {
//        $this->addTab(
//            'Gift Items',
//            [
//                'label' => __('Gift Items'),
//                'title' => __('Gift Items'),
//                'class'     => 'ajax',
////                'url'       => getUrl('*/*/gift', array('_current' => true)),
//                'content' => $this->getChildHtml('main'),
//                'active' => true
//
//            ]
//        );
//        return parent::_beforeToHtml();
//    }
}
