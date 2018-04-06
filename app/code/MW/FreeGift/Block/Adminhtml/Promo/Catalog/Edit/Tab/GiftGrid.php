<?php

namespace MW\FreeGift\Block\Adminhtml\Promo\Catalog\Edit\Tab;

use Magento\Store\Model\Store;
use Magento\Backend\Block\Widget\Grid\Extended;


class GiftGrid extends Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory]
     */
    protected $_setsFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

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
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $_websiteFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter
     */
    protected $_converter;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\Product\Type $type
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $status
     * @param \Magento\Catalog\Model\Product\Visibility $visibility
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $status,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter $converter,
        array $data = []
    ) {
        $this->_websiteFactory = $websiteFactory;
        $this->_setsFactory = $setsFactory;
        $this->_productFactory = $productFactory;
        $this->_type = $type;
        $this->_status = $status;
        $this->_visibility = $visibility;
        $this->moduleManager = $moduleManager;
        $this->_coreRegistry = $registry;
        $this->_converter = $converter;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('productGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('product_filter');
    }

    /**
     * @return Store
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return $this->_storeManager->getStore($storeId);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = $this->_productFactory->create()->getCollection()->addAttributeToSelect(
            'sku'
        )->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'attribute_set_id'
        )->addAttributeToSelect(
            'type_id'
        )->setStore(
            $store
        );

        if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            $collection->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );
        }
        if ($store->getId()) {
            //$collection->setStoreId($store->getId());
            $collection->addStoreFilter($store);
            $collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                Store::DEFAULT_STORE_ID
            );
            $collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
        } else {
            $collection->addAttributeToSelect('price');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }

        $productIds = $this->_getSelectedProducts();
        $collection->addFieldToFilter('entity_id', ['in' => implode(',', $productIds)]);

        $this->setCollection($collection);

        $this->getCollection()->addWebsiteNamesToResult();

        parent::_prepareCollection();

        return $this;
    }

    protected function _getSelectedProducts(){
        $model = $this->_coreRegistry->registry('current_promo_catalog_rule');
        $productIds = $model->getGiftProductIds();
        return explode(',', $productIds);
    }

    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            if ($column->getId() == 'websites') {
                $this->getCollection()->joinField(
                    'websites',
                    'catalog_product_website',
                    'website_id',
                    'product_id=entity_id',
                    null,
                    'left'
                );
            }
        }
        return parent::_addColumnFilterToCollection($column);
    }

    /**
     * @throws
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {

//        if (!$this->getCategory()->getProductsReadonly()) {
            $this->addColumn(
                'product_ids',
                [
                    'type' => 'checkbox',
                    'name' => 'entity_id',
                    'values' => $this->_getSelectedProducts(),
                    'index' => 'entity_id',
                    'header_css_class' => 'col-select col-massaction',
                    'column_css_class' => 'col-select col-massaction',
                    'data-form-part' => 'mw_freegift_catalog_rule_form'

                ]
            );
//        }

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'class' => 'xxx'
            ]
        );

        $store = $this->_getStore();
        if ($store->getId()) {
            $this->addColumn(
                'custom_name',
                [
                    'header' => __('Name in %1', $store->getName()),
                    'index' => 'custom_name',
                    'header_css_class' => 'col-name',
                    'column_css_class' => 'col-name'
                ]
            );
        }

        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'type' => 'options',
                'options' => $this->_type->getOptionArray()
            ]
        );

        $sets = $this->_setsFactory->create()->setEntityTypeFilter(
            $this->_productFactory->create()->getResource()->getTypeId()
        )->load()->toOptionHash();

        $this->addColumn(
            'set_name',
            [
                'header' => __('Attribute Set'),
                'index' => 'attribute_set_id',
                'type' => 'options',
                'options' => $sets,
                'header_css_class' => 'col-attr-name',
                'column_css_class' => 'col-attr-name'
            ]
        );

        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku'
            ]
        );

        $store = $this->_getStore();
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'price',
                'currency_code' => $store->getBaseCurrency()->getCode(),
                'index' => 'price',
                'header_css_class' => 'col-price',
                'column_css_class' => 'col-price'
            ]
        );

        if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            $this->addColumn(
                'qty',
                [
                    'header' => __('Quantity'),
                    'type' => 'number',
                    'index' => 'qty'
                ]
            );
        }

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
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->_status->getOptionArray()
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'websites',
                [
                    'header' => __('Websites'),
                    'sortable' => false,
                    'index' => 'websites',
                    'type' => 'options',
                    'options' => $this->_websiteFactory->create()->getCollection()->toOptionHash(),
                    'header_css_class' => 'col-websites',
                    'column_css_class' => 'col-websites'
                ]
            );
        }

//        $this->addColumn(
//            'edit',
//            [
//                'header' => __('Edit'),
//                'type' => 'action',
//                'getter' => 'getId',
//                'actions' => [
//                    [
//                        'caption' => __('Edit'),
//                        'url' => [
//                            'base' => '*/*/edit',
//                            'params' => ['store' => $this->getRequest()->getParam('store')]
//                        ],
//                        'field' => 'id'
//                    ]
//                ],
//                'filter' => false,
//                'sortable' => false,
//                'index' => 'stores',
//                'header_css_class' => 'col-action',
//                'column_css_class' => 'col-action'
//            ]
//        );

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
//    protected function _prepareMassaction()
//    {
//        $this->setMassactionIdField('product_ids');
////        $this->getMassactionBlock()->setTemplate('Magento_Catalog::product/grid/massaction_extended.phtml');
//        $this->getMassactionBlock()->setFormFieldName('product');
//        $this->getMassactionBlock()->setUseAjax(true);
//        $this->getMassactionBlock()->setHideFormElement(true);
//
//
//        $this->getMassactionBlock()->addItem(
//            'update',
//            [
//                'label' => __('Update'),
//                'url' => $this->getUrl('*/*/giftsMassUpdate',['_current' => true]),
//                'confirm' => __('Are you sure?'),
//                'selected' => true
//            ]
//        );
//        return $this;
//    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('catalog/*/grid', ['_current' => true]);
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
//    public function getRowUrl($row)
//    {
//        return $this->getUrl(
//            'catalog/*/edit',
//            ['store' => $this->getRequest()->getParam('store'), 'id' => $row->getId()]
//        );
//    }

    /**
     * get selected row values.
     *
     * @return array
     */
    public function getSelectedGiftProducts()
    {
        $selectedStores = $this->_converter->toFlatArray(
            $this->getTreeSelectedStores()
        );

        return array_values($selectedStores);
    }


    /**
     * get selected stores in serilaze grid store.
     *
     * @return array
     */
    public function getTreeSelectedStores()
    {

        $ids = $this->_getSelectedProducts();//[2046,2045];
        return $this->_converter->toTreeArray($ids);

//        $sessionData = $this->_getSessionData();
//
//        if ($sessionData) {
//            return $this->_converter->toTreeArray(
//                $this->_backendHelperJs->decodeGridSerializedInput($sessionData)
//            );
//        }
//
//        $entityType = $this->_getRequest()->getParam('entity_type');
//        $id = $this->_getRequest()->getParam('enitity_id');
//
//        /** @var \Magestore\Storelocator\Model\AbstractModelManageStores $model */
//        $model = $this->_factory->create($entityType)->load($id);
//
//        return $model->getId() ? $this->_converter->toTreeArray($model->getStorelocatorIds()) : [];
    }

    /**
     * Get session data.
     *
     * @return array
     */
//    protected function _getSessionData()
//    {
//        $serializedName = $this->_getRequest()->getParam('serialized_name');
//        if ($this->_sessionData === null) {
//            $this->_sessionData = $this->_backendSession->getData($serializedName, true);
//        }
//
//        return $this->_sessionData;
//    }


    /**
     * Retrieve related products
     *
     * @return array
     */
//    public function getSelectedGiftProducts()
//    {
//        return $products = [
//            2046 => ['position' => 0],
//            2045 => ['position' => 0]
//        ];
//        $products = [];
//
//        if($this->getRequest()->getPost('gift_product_ids', null) == null){
//            if(!isset($this->gift_product_id)){
//                $model = $this->getRule();
//                if($model){
//                    $this->gift_product_id = explode(',',$model->getGiftProductIds());
//                }else if($gift_product_ids = $this->_coreRegistry->registry('gift_product_ids')) {
//                    $this->gift_product_id = explode(',', $gift_product_ids);
//                }else{
//                    $this->gift_product_id = [];
//                }
////                return $this->gift_product_id;
//                if(!empty($this->gift_product_id)){
//                    foreach ($this->gift_product_id as $product_id) {
//                        $products[$product_id] = ['position' => 0];
//                    }
//                }
//                $this->gift_products = $products;
//                return $products;
//            }
//            if($this->gift_products){
//                return $this->gift_products;
//            }
//        }
//
//        $gift_product_ids = $this->getRequest()->getPost('gift_product_ids', null);
//        if(!empty($gift_product_ids)){
//            foreach ($gift_product_ids as $product_id) {
//                $products[$product_id] = ['position' => 0];
//            }
//        }
//        return $products;
//    }


}
