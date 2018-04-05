<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace MW\FreeGift\Block\Adminhtml\Promo\Catalog\Edit\Tab\Gift;
use Magento\Backend\Block\Widget\Grid\Column;

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

    protected $gift_product_id = null;
    protected $gift_products = null;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory
     */
    protected $_salesRuleCoupon;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $_type;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $_status;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_visibility;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;


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
//        \Magento\Catalog\Model\Product\LinkFactory $linkFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
//        \Magento\Eav\Model\Resource\Entity\Attribute\Set\CollectionFactory $setsFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $status,
        \Magento\Catalog\Model\Product\Visibility $visibility,
//        \Magento\SalesRule\Model\Resource\Coupon\CollectionFactory $salesRuleCoupon,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
//        $this->_linkFactory = $linkFactory;
//        $this->_setsFactory = $setsFactory;
        $this->_productFactory = $productFactory;
        $this->_type = $type;
        $this->_status = $status;
        $this->_visibility = $visibility;
//        $this->_salesRuleCoupon = $salesRuleCoupon;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Set grid params
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('gift_product_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        //$this->setSaveParametersInSession(true);
//        if ($this->getProduct() && $this->getProduct()->getId()) {
            $this->setDefaultFilter(['gift_product_ids' => 1]);
//        }
        if ($this->isReadonly()) {
            $this->setFilterVisibility(false);
        }
    }

    /**
     * Retirve currently edited product model
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getRule()
    {
        return $this->_coreRegistry->registry('current_promo_catalog_rule');
    }

    /**
     * Retirve currently edited product model
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->_coreRegistry->registry('current_product');
    }

    /**
     * Add filter
     *
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'gift_product_ids') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }


    /**
     * Checks when this block is readonly
     *
     * @return boolean
     */
    public function isReadonly()
    {
//        return $this->_authorization->isAllowed('MW_FreeGift::promo_catalog');
        return $this->getProduct() && $this->getProduct()->getRelatedReadonly();
    }


    /**
     * Apply sorting and filtering to collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_productFactory->create()->getCollection()
        ->addAttributeToSelect(
            '*'
//        )->addAttributeToSelect(
//            'name'
//        )->addAttributeToSelect(
//            'sku'
//        )->addAttributeToSelect(
//            'price'
//        )->addAttributeToSelect(
//            'attribute_set_id'
//        )->addAttributeToSelect(
//            'status'
//        )->addAttributeToSelect(
//            'visibility'
//        )->addAttributeToFilter(
//            'entity_id',
//            ['in' => $this->_getSelectedProducts()]
//        )->addAttributeToFilter(
//        'type_id',
//        ['in' => $this->getAllowedSelectionTypes()]
//        )->addFilterByRequiredOptions()->addStoreFilter(
//            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );

        if($this->gift_products != null){
            $collection->addAttributeToFilter(
                'entity_id',
                ['in' => $this->_getSelectedProducts()]
            );
        }

        if ($this->isReadonly()) {
            $model = $this->getRule();
            if($model && $model->getGiftProductIds()){
                $productIds = explode(',',$model->getGiftProductIds());
                if(!empty($productIds)){
                    $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
                }
            }
        }

//        if ($this->getFirstShow()) {
//            $collection->addIdFilter('-1');
//            $this->setEmptyText(__('What are you looking for?'));
//        }

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
        if (!$this->isReadonly()) {
            $this->addColumn(
                'gift_product_ids',
                [
                    'type' => 'checkbox',
                    'field_name' => 'gift_product_ids[]',
                    'values' => $this->_getSelectedProducts(),
                    'align' => 'center',
                    'index' => 'entity_id',
                    'header_css_class' => 'col-select',
                    'column_css_class' => 'col-select'
                ]
            );
        }

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
                'header' => __('Name'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'type' => 'options',
                'options' => $this->_type->getOptionArray(),
                'header_css_class' => 'col-type',
                'column_css_class' => 'col-type'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->_status->getOptionArray(),
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
                'options' => $this->_visibility->getOptionArray(),
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
            'position',
            [
                'filter' => false,
                'header' => __('Position'),
                'name' => 'position',
                'type' => 'number',
                'validate_class' => 'validate-number',
                'index' => 'position',
//                'editable' => !$this->getProduct()->getUpsellReadonly(),
//                'edit_only' => !$this->getProduct()->getId(),
                'header_css_class' => 'col-position',
                'column_css_class' => 'col-position',
                'filter_condition_callback' => [$this, 'filterProductPosition']
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Rerieve grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getData(
            'grid_url'
        ) ? $this->getData(
            'grid_url'
        ) : $this->getUrl(
            '*/*/giftGrid',
//            ['_current' => true, 'gift_product_ids' => $this->getRule()->getGiftProductIds()]
            ['_current' => true]
        );
    }


//    protected function _prepareMassaction()
//    {
//        $this->setMassactionIdField('entity_id');
//        $this->getMassactionBlock()->setFormFieldName('gift[]');
//
//        $this->getMassactionBlock()->addItem(
//            'delete',
//            [
//                'label' => __('Delete'),
//                'url' => $this->getUrl(
//                    '*/*/massDelete',
//                    ['ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : 'index']
//                ),
//                'confirm' => __('Are you sure?'),
//            ]
//        );
//
//        $this->getMassactionBlock()->removeItem('delete');
//
//        return $this;
//    }

    /**
     * Retrieve selected related products
     *
     * @return array
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getGiftProductIds();
        if (!is_array($products)) {
            $products = array_keys($this->getSelectedGiftProducts()); // 0
        }
        return $products;

//        if(!isset($this->gift_product_id)){
//            $model = $this->getRule();
//            if($model){
//                $this->gift_product_id = explode(',',$model->getGiftProductIds());
//            }else if($gift_product_ids = $this->_coreRegistry->registry('gift_product_ids')) {
//                $this->gift_product_id = explode(',', $gift_product_ids);
//            }else{
//                $this->gift_product_id = [];
//            }
//        }
//        return $this->gift_product_id;

    }

    /**
     * Retrieve related products
     *
     * @return array
     */
    public function getSelectedGiftProducts()
    {
//        return $products = [
//            2046 => ['position' => 0],
//            2045 => ['position' => 0]
//        ];
        $products = [];

        if($this->getRequest()->getPost('gift_product_ids', null) == null){
            if(!isset($this->gift_product_id)){
                $model = $this->getRule();
                if($model){
                    $this->gift_product_id = explode(',',$model->getGiftProductIds());
                }else if($gift_product_ids = $this->_coreRegistry->registry('gift_product_ids')) {
                    $this->gift_product_id = explode(',', $gift_product_ids);
                }else{
                    $this->gift_product_id = [];
                }
//                return $this->gift_product_id;
                if(!empty($this->gift_product_id)){
                    foreach ($this->gift_product_id as $product_id) {
                        $products[$product_id] = ['position' => 0];
                    }
                }
                $this->gift_products = $products;
                return $products;
            }
            if($this->gift_products){
                return $this->gift_products;
            }
        }

        $gift_product_ids = $this->getRequest()->getPost('gift_product_ids', null);
        if(!empty($gift_product_ids)){
            foreach ($gift_product_ids as $product_id) {
                $products[$product_id] = ['position' => 0];
            }
        }
        return $products;
    }

    /**
     * Apply `position` filter to cross-sell grid.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection $collection
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @return $this
     */
    public function filterProductPosition($collection, $column)
    {
        $collection->addLinkAttributeToFilter($column->getIndex(), $column->getFilter()->getCondition());
        return $this;
    }

    /**
     * Generate content to log file debug.log By Hattetek.Com
     *
     * @param  $message string|array
     * @return void
     */
    function xlog($message = 'null')
    {
        $log = print_r($message, true);
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($log)
        ;
    }
}
