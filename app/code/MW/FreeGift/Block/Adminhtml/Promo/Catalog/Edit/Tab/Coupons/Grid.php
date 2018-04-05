<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace MW\FreeGift\Block\Adminhtml\Promo\Catalog\Edit\Tab\Coupons;

/**
 * Coupon codes grid
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory
     */
    protected $_salesRuleCoupon;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory $salesRuleCoupon
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory $salesRuleCoupon,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Model\ProductFactory $productFactory,

        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_productFactory = $productFactory;

        $this->_salesRuleCoupon = $salesRuleCoupon;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('couponCodesGrid');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection for grid
     *
     * @return $this
     */
    protected function _prepareCollection1()
    {
        $priceRule = $this->_coreRegistry->registry('current_promo_quote_rule');

        /**
         * @var \Magento\SalesRule\Model\ResourceModel\Coupon\Collection $collection
         */
        $collection = $this->_salesRuleCoupon->create()->addRuleToFilter($priceRule)->addGeneratedCouponsFilter();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Apply sorting and filtering to collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_productFactory->create()->getCollection()->setOrder(
            'id'
        )->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'sku'
        )->addAttributeToSelect(
            'price'
        )->addAttributeToSelect(
            'attribute_set_id'
        )->addAttributeToSelect(
            'status'
        )->addAttributeToSelect(
            'visibility'
//        )->addAttributeToFilter(
//            'entity_id',
//            ['nin' => $this->_getSelectedProducts()]
        //)->addAttributeToFilter(
        //'type_id'
        //['in' => $this->getAllowedSelectionTypes()]
        )->addFilterByRequiredOptions()->addStoreFilter(
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );

        if ($this->getFirstShow()) {
            $collection->addIdFilter('-1');
            $this->setEmptyText(__('What are you looking for?'));
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Define grid columns
     *
     * @return $this
     */
    /**
     * Initialize grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {

        $this->addColumn(
            'in_products',
            [
                'type' => 'checkbox',
                'name' => 'in_products',
//                'values' => $this->_getSelectedProducts(),
                'align' => 'center',
                'index' => 'entity_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Product'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'name col-name'
            ]
        );
        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'type' => 'options',
//                'options' => $this->_type->getOptionArray(),
                'header_css_class' => 'col-name',
                'column_css_class' => 'name col-name'
            ]
        );
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
//                'options' => $this->_status->getOptionArray(),
                'header_css_class' => 'col-status',
                'column_css_class' => 'col-status'
            ]
        );
        $this->addColumn(
            'visibility',
            [
                'header' => __('Visibility'),
                'index' => 'visibility',
                'type' => 'options',
//                'options' => $this->_visibility->getOptionArray(),
                'header_css_class' => 'col-visibility',
                'column_css_class' => 'col-visibility'
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'width' => '80px',
                'index' => 'sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'sku col-sku'
            ]
        );
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'index' => 'price',
                'header_css_class' => 'col-price',
                'column_css_class' => 'col-price'
            ]
        );

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'align' => 'center',
                'type' => 'currency',
                'index' => 'price',
                'header_css_class' => 'col-price',
                'column_css_class' => 'col-price'
            ]
        );
        return parent::_prepareColumns();
    }

    /**
     * Configure grid mass actions
     *
     * @return $this
     */
//    protected function _prepareMassaction()
//    {
//        $this->setMassactionIdField('coupon_id');
//        $this->getMassactionBlock()->setFormFieldName('ids');
//        $this->getMassactionBlock()->setUseAjax(true);
//        $this->getMassactionBlock()->setHideFormElement(true);
//
//        $this->getMassactionBlock()->addItem(
//            'delete',
//            [
//                'label' => __('Delete'),
//                'url' => $this->getUrl('sales_rule/*/couponsMassDelete', ['_current' => true]),
//                'confirm' => __('Are you sure you want to delete the selected coupon(s)?'),
//                'complete' => 'refreshCouponCodesGrid'
//            ]
//        );
//        return $this;
//    }

    /**
     * Get grid url
     *
     * @return string
     */
//    public function getGridUrl()
//    {
//        return $this->getUrl('sales_rule/*/couponsGrid', ['_current' => true]);
//    }
}
