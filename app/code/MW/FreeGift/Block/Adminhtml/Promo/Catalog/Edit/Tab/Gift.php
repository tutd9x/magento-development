<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Block\Adminhtml\Promo\Catalog\Edit\Tab;

/**
 * "Manage Coupons Codes" Tab
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Gift extends \Magento\Backend\Block\Text\ListText implements \Magento\Backend\Block\Widget\Tab\TabInterface
//class Gift extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
//    public function __construct(
//        \Magento\Framework\View\Element\Context $context,
//        \Magento\Framework\Registry $registry,
//        array $data = []
//    ) {
//        $this->_coreRegistry = $registry;
//        parent::__construct($context, $data);
////        $model = $this->_coreRegistry->registry('current_promo_catalog_rule');
////        print_r($model->getStopRulesProcessing()); exit;
//    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Gift Items');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Gift Items');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return $this->_isEditing();
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return !$this->_isEditing();
    }

//    /**
//     * @return string
//     */
    public function getTabClass()
    {
        return 'ajax';
    }
//
//    /**
//     * @return string
//     */
    public function getTabUrl()
    {
        //$model = $this->_coreRegistry->registry('current_promo_catalog_rule');
        //return $this->getUrl('*/*/gift', ['_current' => true, 'stop_rules_processing' => $model->getStopRulesProcessing()]);
        return $this->getUrl('*/*/giftGrid', ['_current' => true]);
    }

    /**
     * Check whether we edit existing rule or adding new one
     *
     * @return bool
     */
    protected function _isEditing()
    {
        return true;
//        $priceRule = $this->_coreRegistry->registry('current_promo_catalog_rule');
//        return $priceRule->getRuleId() !== null;
    }
}
