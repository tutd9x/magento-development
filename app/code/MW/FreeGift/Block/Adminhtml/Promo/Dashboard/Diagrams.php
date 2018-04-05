<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Block\Adminhtml\Promo\Dashboard;

/**
 * Adminhtml dashboard diagram tabs
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Diagrams extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @var string
     */
    protected $_template = 'MW_FreeGift::widget/tabshoriz.phtml';

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('diagram_tab');
        $this->setDestElementId('diagram_tab_content');
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
//        $this->addTab(
//            'orders',
//            [
//                'label' => __('Orders'),
//                'content' => $this->getLayout()->createBlock('MW\FreeGift\Block\Adminhtml\Promo\Dashboard\Tab\Orders')->toHtml(),
//                'active' => true
//            ]
//        );
//
//        $this->addTab(
//            'amounts',
//            [
//                'label' => __('Amounts'),
//                'content' => $this->getLayout()->createBlock('MW\FreeGift\Block\Adminhtml\Promo\Dashboard\Tab\Amounts')->toHtml()
//            ]
//        );
        return parent::_prepareLayout();
    }
}
