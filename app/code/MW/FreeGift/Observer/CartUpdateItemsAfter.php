<?php
namespace MW\FreeGift\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Customer\Api\GroupManagementInterface;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;


class CartUpdateItemsAfter implements ObserverInterface
{
    protected $productRepository;
    protected $_storeManager;
    protected $_ruleFactory;
    protected $_productFactory;
    protected $_stockState;
    protected $_configurableProduct;
    protected $cart;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
//        CustomerModelSession $customerSession,
//        \MW\FreeGift\Model\ResourceModel\Rule $resourceRule,
//        \MW\FreeGift\Helper\Data $helperFreeGift,
//        \MW\FreeGift\Model\CouponFactory $couponFactory,
//        \MW\FreeGift\Model\SalesruleFactory $salesruleFactory,
//        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        CustomerCart $cart,
//        GroupManagementInterface $groupManagement
        \MW\FreeGift\Model\RuleFactory $ruleFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Model\StockState $stockState,
        ConfigurableProduct $configurableProduct
    ) {
        $this->_storeManager = $storeManager;
//        $this->_localeDate = $localeDate;
//        $this->_customerSession = $customerSession;
//        $this->_resourceRule = $resourceRule;
//        $this->helperFreeGift = $helperFreeGift;
//        $this->_couponFactory = $couponFactory;
//        $this->_salesruleFactory = $salesruleFactory;
//        $this->checkoutSession = $checkoutSession;
        $this->productRepository = $productRepository;
        $this->cart = $cart;
//        $this->groupManagement = $groupManagement;
        $this->_ruleFactory = $ruleFactory;
        $this->_productFactory = $productFactory;
        $this->_stockState = $stockState;
        $this->_configurableProduct = $configurableProduct;
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

    protected function _initProduct($productId)
    {
        $this->_storeManager->getStore()->getName();
        if ($productId) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;

    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $_cart = $observer->getCart();
        $quote = $_cart->getQuote();
        $items = $quote->getAllVisibleItems();
        $loop = true;
        $qtyProductsUsed = array();

//        return false;
        foreach ($items as $item) {
            if($item->getOptionByCode('additional_options')){
                $params = unserialize($item->getOptionByCode('additional_options')->getValue());
                $params = $params[0];
            }else{
                continue;
            }
            /** $quote_parent_item_id: if the current item have parent item ( configurable, bundle ) then query parent item to get original quantity */
            $quote_parent_item_id = 0;
            if ($item->getParentItem()) {
                if (isset($params['freegift_parent_key'])) {
                    if ($quote_parent_item_id == 0) {
                        foreach ($items as $_item) {
                            $_params = unserialize($_item->getOptionByCode('info_buyRequest')->getValue());
                            if ($params['freegift_parent_key'] == $_params['freegift_key']) {
                                $quote_parent_item_id = $_item->getId();
                                break;
                            }
                        }
                    }
                }
            } else {
                $quote_parent_item_id = $item->getItemId();
            }
//            $qty = 2;
//            $item->setQty($qty);

            if (!isset($params['freegift_key'])) {
                //If item is gift product
                if (!isset($params['free_catalog_gift']) || !isset($params['freegift']) || !isset($params['freegift_with_code'])) {
                    foreach ($item->getChildren() as $_item) {
                        $_params = unserialize($_item->getOptionByCode('info_buyRequest')->getValue());
                        if (isset($_params['freegift_key'])) {
                            //Re-save infoBuy_request
//                            $collection = Mage::getModel('sales/quote_item_option')->getCollection();
//                            $con = Mage::getModel('core/resource')->getConnection('core_write');
//                            $sql = "UPDATE {$collection->getTable('sales/quote_item_option')} SET value= '" . serialize($_params) . "' WHERE product_id={$_params['product']} AND code = 'info_buyRequest'";
//                            $con->query($sql);
                            break;
                        }
                    }
                }
            }

            $_quotes_parent = $quote->load($quote_parent_item_id);

            $product = $this->_initProduct($item->getProductId());
            $_product = $this->_productFactory->create()->load($item->getProductId());
            $stock_qty = (int)$_quotes_parent->getQty();

            if ($_product->getTypeId() == 'configurable') {
//                /** Attributes on view product (frontend) */
//                $childProduct = Mage::getModel('catalog/product_type_configurable')->getProductByAttributes($params['super_attribute'], $product);
                $childProduct = $this->_configurableProduct->getProductByAttributes($params['super_attribute'], $product);
//                $childStockQty = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct)->getQty();
                $childStockQty = (int)$this->_stockState->getStockQty($childProduct->getId());
                $managechildStock = $childProduct->getStockItem()->getManageStock();
                $stockQty = $childStockQty;
            } else if ($_product->getTypeId() == 'simple') {
//                $stockQty = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getProduct())->getQty();
                $stockQty =  (int)$this->_stockState->getStockQty($item->getProductId());
//                $manageStock = $_product->getStockItem()->getManageStock();
            } else {
                $stockQty = $item->getQty();
            }

            /** check if product is Catalog Rule */
            if (isset($params['mw_freegift_rule_gift']) && $params['mw_freegift_rule_gift']) {
                /** Get main product from this item */
                $parent_key_item = $this->getParentQuoteItemByKey($params);

                //@@TODO recheck
                if (!$parent_key_item) {
                    $quote->removeItem($item->getId());
                    $_cart->removeItem($item->getId())->save();
                    continue;
                }

//                $infoBuyRequest = unserialize($parent_key_item->getOptionByCode('info_buyRequest')->getValue());
//                $appliedCatalogRule = unserialize($infoBuyRequest['mw_applied_catalog_rule']);

                $rule = $this->_ruleFactory->create()->load($params['mw_applied_catalog_rule']);
                /** get condition buy X get Y */
                $custom_cdn = unserialize($rule->getconditionCustomized());

                $first_rem = false;

                if (!$rule->getIsActive()) {
                    $first_rem = true;
                }
                if ($rule->getData('discount_qty') && ($rule->getData('discount_qty') < $rule->getData('times_used'))) {
                    $first_rem = true;
                }

                if ($first_rem) {
                    $quote->removeItem($item->getId());
                    $item->save();
                    continue;
                }

                /* check quantity */
                $qty_4gift = 0;
                if ($product->getTypeId() == 'configurable') {
                    if ((int)$_quotes_parent->getQty() > $childStockQty) {
                        $qty_4gift = $childStockQty;
                    } else {
                        $qty_4gift = (int)$_quotes_parent->getQty();
                    }
                } else if ($product->getTypeId() == 'simple') {
                    $qty_4gift = $this->getFinalQty($parent_key_item, $item, $stockQty);
                } else {
                    $qty_4gift = $this->getFinalQty($_quotes_parent, $item, $stockQty);
                }

//                $this->xlog($qty_4gift);
//                $this->xlog($this->_storeManager->getStore()->getId());

//                return false;

                if ($qty_4gift > 0) {
                    if (isset($custom_cdn['buy_x_get_y'])) {
                        if ($parent_key_item->getQty() < $custom_cdn['buy_x_get_y']['bx']) {
                            $quote->removeItem($item->getId());
                            $item->save();
                            continue;
                        }
                    }
                    if (isset($custom_cdn['buy_x_get_y']) && $custom_cdn['buy_x_get_y']['bx'] == 1 && $custom_cdn['buy_x_get_y']['gy'] == 1) {
                        $item->setQty($qty_4gift);
                        $item->save();
                    } else if ($custom_cdn['buy_x_get_y']['bx'] < $custom_cdn['buy_x_get_y']['gy']) {
                        $new_qty_4gift = floor($parent_key_item->getQty() / $custom_cdn['buy_x_get_y']['bx']) * $custom_cdn['buy_x_get_y']['gy'];
                        $item->setQty(($new_qty_4gift > $stockQty) ? $stockQty : $new_qty_4gift);
                    } else {
                        $qty_4gift = floor($parent_key_item->getQty() / $custom_cdn['buy_x_get_y']['bx']) * $custom_cdn['buy_x_get_y']['gy'];

                        $item->setQty(($qty_4gift > $stockQty) ? $stockQty : $qty_4gift);
                    }
                    $item->setPrice(0);
                    $item->setRowTotal(0);
                    $item->setBaseRowTotal(0);
                    $item->save();
                } else {
                    $quote->removeItem($item->getId());
                    $item->save();
                }
            }
//            $this->xlog($infoDataObject);
//            $this->xlog($params);
//            $this->xlog($this->_storeManager->getStore()->getId());
//            return false;
        }
    }
    protected function getParentQuoteItemByKey($params)
    {
        $items = $this->cart->getQuote()->getAllItems();
        foreach ($items as $item) {
            $info_buyRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
            if (!isset($info_buyRequest['freegift_key'])) continue;
            if (isset($params['freegift_parent_key'])) {
                if ($params['freegift_parent_key'] == $info_buyRequest['freegift_key']) {
                    return $item;
                }
            }
        }
        return false;
    }

    protected function getFinalQty($parent_item, $item, $stockQty)
    {
        $manageStock = 0;

        if ($parent_item->getProductId() == $item->getProductId()) {

            $product = $this->_productFactory->create()->load($parent_item->getProductId());
            $manageStock = $product->getStockItem()->getManageStock();
            /** If Parent quote product is the same children quote product */
            /* if new quantity greater than stock qty */
            if ($manageStock) {
                if ((int)$parent_item->getQty() >= $stockQty) {
                    $qty_4gift = $stockQty - $parent_item->getQty();
                } else if (2 * $parent_item->getQty() > $stockQty) {
                    $qty_4gift = $stockQty - $parent_item->getQty();
                } else {
                    $qty_4gift = (int)$parent_item->getQty();
                }
            } else {
                $qty_4gift = (int)$parent_item->getQty();
            }
        } else {

            if ($manageStock) {
                /* if new quantity greater than stock qty */
                if ((int)$parent_item->getQty() > $stockQty) {
                    $qty_4gift = $stockQty;
                } else {
                    $qty_4gift = (int)$parent_item->getQty();
                }
            } else {
                $qty_4gift = (int)$parent_item->getQty();
            }
        }

        return $qty_4gift;
    }
}
?>