<?php
namespace MW\FreeGift\Controller\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\NoSuchEntityException;


class Addg extends \Magento\Checkout\Controller\Cart
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    protected $helperFreeGift;
    protected $_ruleFactory;
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \MW\FreeGift\Model\RuleFactory $ruleFactory
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->layoutFactory = $layoutFactory;
        $this->productRepository = $productRepository;
        $this->helperFreeGift = $helperFreeGift;
        $this->_ruleFactory = $ruleFactory;
    }
    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product || false
     */
    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $storeId = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Add product to shopping cart action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $cart = $this->cart;
        $product = $this->_initProduct();
        $params = $this->getRequest()->getParams();
//        $quote_parent_item = null;

        //$cart = $this->checkoutSession;
        $layout = $this->layoutFactory->create();
//        $update = $layout->getUpdate();
        $layout->getUpdate()->load(['checkout_cart_index']);
        $layout->generateXml();
        $layout->generateElements();
        //$output = $layout->getOutput();

        // process for gift by catalog rule
        if (isset($params['free_catalog_gift'])) {
            $flag_update = true;
            if (isset($params['super_attribute'])) {
                foreach ($params['super_attribute'] as $k => $attr) {
                    if (empty($attr)) {
                        $flag_update = false;
                    }
                }
            }
            if (isset($params['options'])) {
                foreach ($params['options'] as $k => $attr) {
                    if (empty($attr)) {
                        $flag_update = false;
                    }
                }
            }
            if (!isset($params['upd'])) {
                $block_product = $layout->createBlock('MW\FreeGift\Block\Product');
                $missingGiftProducts = $this->helperFreeGift->getGiftDataByRule(); //$block_product->getFreeGiftCatalogProduct();
                $quote_parent_item = false; // = $this->getQuoteItemByGiftItemId($params['free_catalog_gift']);
                $items = $this->_checkoutSession->getQuote()->getAllItems();

                foreach ($items as $item) {

//                    $this->xlog($item->getItemId());
                    if ($params['free_catalog_gift'] == $item->getItemId()){
                        $quote_parent_item = $item;
                        break;
                    }
                }
//                $optionParentCollection = Mage::getModel('sales/quote_item_option')
//                    ->getCollection()
//                    ->addFieldToFilter('item_id', $params['free_catalog_gift']);
//
//                foreach ($optionParentCollection as $opt) {
//                    if ($opt->getCode() == 'info_buyRequest') {
//                        $infoRequest = unserialize($opt->getValue());
//                        break;
//                    }
//                }
            }

            if ($flag_update === false) {
                echo json_encode(array('message' => 'Empty options.', 'error' => 1, 'action' => 'load_in_page', 'item_id' => ''));
                exit;
            }
//            if (isset($params['upd'])) {
//                if ($flag_update) {
//                    $quote_item = false; // $this->getQuoteItemByGiftItemId($params['item_id']);
//
//                    $items = $this->checkoutSession->getQuote()->getAllItems();
//                    foreach ($items as $item) {
//                        if ($params['item_id'] == $item->getItemId())
//                            $quote_item = $item;
//                    }
//
//                    if (isset($params['options'])) {
//                        foreach ($params['options'] as $optId => $opt) {
//                            $quote_item->getOptionByCode('option_' . $optId)->setValue($opt);
//                        }
//                    }
//                    $quote_item->getOptionByCode('attributes')->setValue(serialize($params['super_attribute']));
//                    $quote_item->getQuote()->save();
//                    $cart->save();
//
//                    $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');
//                    echo json_encode(array(
//                        'message'   => '',
//                        'error'     => 0,
//                        'upd'       => 1,
//                        'item_id'   => $quote_item->getItemId(),
//                        'item_html' => $block_cart->getItemHtml($quote_item)
//                    ));
//                    return;
//                }
//            }
            $params['qty'] = 1;

            if ($quote_parent_item && $quote_parent_item->getItemId()) {

                $_product = $this->productRepository->getById($quote_parent_item->getProductId());
                $stock_qty = 100 ; //(int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty();
                $infoRequestParent = unserialize($quote_parent_item->getOptionByCode('info_buyRequest')->getValue());
                $catalogRules = unserialize($infoRequestParent['mw_applied_catalog_rule']);

                if(!in_array($params['applied_catalog_rule'], $catalogRules)){
                    return false;
                }
                $rule = $this->_ruleFactory->create()->load($params['applied_catalog_rule']);

                if ( 1 == 1
                    //isset($missingGiftProducts[$params['free_catalog_gift']])
                    //&& in_array($params['product'], $missingGiftProducts[$params['free_catalog_gift']][$params['applied_catalog_rule']])
                ) {

                    $qty_4gift = 1 ; //$this->getQtyToAdded($product, $params, $quote_parent_item, $stock_qty);
                    unset($params['freegift']);

                    if ($qty_4gift > 0) {

//                        $product->addCustomOption(base64_encode(microtime()), serialize(array(time())));

                        $product->setFinalPrice(0);
                        $product->setCustomPrice(0);
                        $product->setPrice(0);
                        $product->setOriginalPrice(0);
                        $product->setPriceCalculation(0);

                        $product->addCustomOption('free_catalog_gift', 1);
                        $product->addCustomOption('freegift_parent_key', $infoRequestParent['freegift_key']);

                        $additionalOptions = [[
                            'label' => 'Free Gift',
                            'value' => $rule->getName(),
                            'print_value' => $rule->getName(),
                            'option_type' => 'text',
                            'custom_view' => TRUE,
                            'mw_freegift_rule_gift' => 1,
                            'mw_applied_catalog_rule' => $rule->getRuleId(),
                            'freegift_parent_key' => $infoRequestParent['freegift_key']
                        ]];
                        // add the additional options array with the option code additional_options
                        $product->addCustomOption('additional_options', serialize($additionalOptions));

                        $params['qty'] = $qty_4gift;
                        $params['mw_freegift_rule_gift'] = 1;
                        $params['freegift_parent_key'] = $infoRequestParent['freegift_key'];
                        $params['mw_applied_catalog_rule'] = $params['applied_catalog_rule'];
                        $params['text_gift'] = array(
                            'label' => 'Free Gift',
                            'value' => $rule->getName()
                        );

                        $cart->addProduct($product, $params);
                        $cart->save();

                        $last_item = '';
                        $items_reloaded = $this->checkoutSession->getQuote()->getAllItems();
                        $last_item = end($items_reloaded);

//                        foreach ($items as $item) {
//                            $last_item = $item;
//                        }
//                        $this->xlog(__LINE__);
                        if (isset($params['ajax_gift']) && $params['ajax_gift'] == "1") {
                            //$block_cart = $layout->getBlock('checkout.cart.form');
                            $block_cart = $layout
                                ->createBlock('Magento\Checkout\Block\Cart');

                            $block_cart->addChild('renderer.list', '\Magento\Framework\View\Element\RendererList');
                            $block_cart->getChildBlock(
                                'renderer.list'
                            )->addChild(
                                'default',
                                '\Magento\Checkout\Block\Cart\Item\Renderer',
                                ['template' => 'cart/item/default.phtml']
                            );
//                            $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');

                            $block_freegift = $layout->createBlock('MW\FreeGift\Block\Product'); //Mage::app()->getLayout()->createBlock('freegift/product');
//                            $last_item = $this->getLastItemAdded();
                            $quote_item = $last_item; // $this->getQuoteItemByGiftItemId($last_item['item_id']);
                            echo json_encode(array(
                                'message'       => '',
                                'error'         => 0,
                                'item_id'       => $quote_item->getItemId(),
                                'item_html'     => $block_cart->getItemHtml($quote_item),
                                'freegift'      => $block_freegift->toHtml(),
                            ));
                            exit;
                        }
                        $this->checkoutSession->setCartWasUpdated(false);
                    } else {
                        echo json_encode(array('message' => 'Out of stock.', 'error' => 1));
                        exit;
                    }
                }
            }
        }
        // process for shopping cart rule
        if ((isset($params['freegift']) && $params['freegift']) || isset($params['freegift_with_code']) && $params['freegift_with_code'])
        {
            if (isset($params['product']) && is_numeric($params['product'])) {
//                $this->xlog(__LINE__);
                $params['qty'] = 1; // (!isset($params['qty'])) ? 1 : $params['qty'];
                $stock_qty = 100 ; //(int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty();
                $qty_4gift = 1 ; //$this->getQtyToAdded($product, $params, $quote_parent_item, $stock_qty);
                if ($qty_4gift > 0) {

//                    $product->addCustomOption(base64_encode(microtime()), serialize(array(time())));
                    $product->setFinalPrice(0);
                    $product->setCustomPrice(0);
                    $product->setOriginalCustomPrice(0);
                    $product->setPrice(0);
                    $product->setOriginalPrice(0);
                    $product->setPriceCalculation(0);

                    $product->addCustomOption('freegift_coupon_code', 1);
//                    $this->xlog(__LINE__);
                    $options = [];
                    $options = [
                        'label' => 'Free Gift',
                        'value' => $params['rule_name'],
                        'print_value' => $params['rule_name'],
                        'option_type' => 'text',
                        'custom_view' => TRUE
                    ];
//                    $this->xlog(__LINE__);
                    if(isset($params['freegift_with_code'])){
                        $options['freegift_with_code'] = 1;
                        $options['freegift_coupon_code'] = $params['freegift_coupon_code'];
                    }
                    if(isset($params['freegift'])){
                        $options['freegift'] = 1;
                    }

                    $additionalOptions = [$options];
//                    $this->xlog(__LINE__);
                    if ($product->getTypeId() == 'grouped')
                    {
                        $_associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
                        foreach($_associatedProducts as $child_product){
                            $child_product->addCustomOption('additional_options', serialize($additionalOptions));
                            $child_product->addCustomOption('freegift', 1);
                            $child_product->setPrice(0);
                            $child_product->setCustomPrice(0);
                            $child_product->setOriginalCustomPrice(0);
                            $child_product->setOriginalPrice(0);
                            $child_product->setFinalPrice(0);
                            $child_product->setPriceCalculation(0);
                            $child_product->setIsSuperMode(true);
                        }
                    }
//                    $this->xlog(__LINE__);
                    // add the additional options array with the option code additional_options
                    $product->addCustomOption('additional_options', serialize($additionalOptions));
                    $product->addCustomOption('freegift', 1);

                    //$params['qty'] = $qty_4gift;
                    $params['freegift_with_code'] = 1;
                    //$params['freegift_coupon_code'] = $params['freegift_coupon_code'];
                    $params['rule_id'] = $params['applied_rule'];

                    $this->cart->addProduct($product, $params);
                    $cart->save();
//                    $this->xlog(__LINE__);
                    $last_item = '';
                    $quote = $this->checkoutSession->getQuote();
                    $items_reloaded = $quote->getAllItems();
                    $last_item = $quote->getItemByProduct($product);
                    if(!isset($last_item) || $last_item == false){
                        foreach($items_reloaded as $item){

                            $item_option_freegift = ($item->getOptionByCode('freegift') ? $item->getOptionByCode('freegift')->getValue() : null);
                            $item_option_info_buyRequest = ($item->getOptionByCode('info_buyRequest') ? unserialize($item->getOptionByCode('info_buyRequest')->getValue()) : null);
//                            $item_option_additional_options = ($item->getOptionByCode('additional_options') ? unserialize($item->getOptionByCode('additional_options')->getValue()) : null);
                            if($item_option_freegift){
                                if($item_option_info_buyRequest['super_product_config']['product_id']){
                                    if($item_option_info_buyRequest['super_product_config']['product_id'] == $product->getId()){
                                        $item->setNoDiscount(1);
                                        $item->setPrice(0);
                                        $item->setCustomPrice(0);
                                        $item->setOriginalCustomPrice(0);
                                        $item->setIsSuperMode(true);
//                                        $item->save();
                                        $last_item = $item;
                                    }
                                }

                            }
                        }
                    }else{
                        $last_item = end($items_reloaded);
                    }
//                    $last_item = $quote->getItemByProduct($product);

//                        foreach ($items as $item) {
//                            $last_item = $item;
//                        }
//                    $this->xlog(__LINE__);
                    if (isset($params['ajax_gift']) && $params['ajax_gift'] == "1") {
//                        $block_cart = $layout->getBlock('checkout.cart.form');
                        $block_cart = $layout
                            ->createBlock('Magento\Checkout\Block\Cart');
                        $block_cart->addChild('renderer.list', '\Magento\Framework\View\Element\RendererList');
                        $block_cart->getChildBlock(
                            'renderer.list'
                        )->addChild(
                            'default',
                            '\Magento\Checkout\Block\Cart\Item\Renderer',
                            ['template' => 'cart/item/default.phtml']
                        );
//                        $this->xlog(__LINE__);
//                            $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');

                        $block_freegift = $layout->createBlock('MW\FreeGift\Block\Product'); //Mage::app()->getLayout()->createBlock('freegift/product');
//                            $last_item = $this->getLastItemAdded();
                        $quote_item = $last_item; // $this->getQuoteItemByGiftItemId($last_item['item_id']);
                        echo json_encode(array(
                            'message'       => '',
                            'error'         => 0,
                            'item_id'       => $quote_item->getItemId(),
                            'item_html'     => $block_cart->getItemHtml($quote_item),
                            'freegift'      => $block_freegift->toHtml(),
                        ));
                        exit;
                    }
                    $this->checkoutSession->setCartWasUpdated(false);
                } else {
                    echo json_encode(array('message' => 'Out of stock.', 'error' => 1));
                    exit;
                }
            }
        }

        /*if ((isset($params['freegift']) && $params['freegift']) || isset($params['freegift_with_code']) && $params['freegift_with_code']) {
            if (isset($params['product']) && is_numeric($params['product'])) {
                $flag_update = true;
                if (isset($params['super_attribute'])) {
                    foreach ($params['super_attribute'] as $k => $attr) {
                        if (empty($attr)) {
                            $flag_update = false;
                        }
                    }
                }
                if (isset($params['options'])) {
                    foreach ($params['options'] as $k => $attr) {
                        if (empty($attr)) {
                            $flag_update = false;
                        }
                    }
                }
                if ($flag_update === false) {
                    echo json_encode(array('message' => 'Empty options.', 'error' => 1, 'action' => 'load_in_page', 'item_id' => ''));
                    exit;
                }

                if (isset($params['upd'])) {
                    $quote_item = $this->getQuoteItemByGiftItemId($params['item_id']);
                    if (isset($params['options'])) {
                        foreach ($params['options'] as $optId => $opt) {
                            $quote_item->getOptionByCode('option_' . $optId)->setValue($opt);
                        }
                    }
                    $quote_item->getOptionByCode('attributes')->setValue(serialize($params['super_attribute']));
                    $quote_item->getQuote()->save();
                    $cart->save();
                    $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');
                    echo json_encode(array(
                        'message'   => '',
                        'error'     => 0,
                        'upd'       => 1,
                        'item_id'   => $quote_item->getItemId(),
                        'item_html' => $block_cart->addItemRender($quote_item->getProduct()->getTypeId(), 'checkout/cart_item_renderer', 'mw_freegift/checkout/cart/item/default.phtml')->getItemHtml($quote_item)
                    ));
                    return;
                }

                $params['qty'] = (!isset($params['qty'])) ? 1 : $params['qty'];
                $stock_qty = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();

                $qty_4gift = $this->getQtyToAdded($product, $params, null, $stock_qty);
                if (Mage::app()->getFrontController()->getRequest()->getParam('apllied_rule')) {
                    $rule = Mage::getModel('freegift/salesrule')->load((int)Mage::app()->getFrontController()->getRequest()->getParam('apllied_rule'));
                    $params['text_gift'] = array(
                        'label' => (isset($params['freegift_coupon_code']) || isset($params['freegift_with_code']) ? 'Free Gift with coupon' : 'Free Gift'),
                        'value' => $rule->getName()
                    );
                }

                $product->addCustomOption((isset($params['freegift_with_code']) ? 'freegift_with_code' : 'freegift'), 1);
                $product->addCustomOption(base64_encode(microtime()), serialize(array(time())));
                $product->setPrice(0);

                $quote_item = $cart->addProduct($product, $params);
                $cart->save();
                $last_item = $cart->getItems()->addFieldToFilter('product_id', $params['product'])->getLastItem();
                if ($last_item->getParentItem()) {
                    $last_item_insert_id = $last_item->getParentItemId();
                } else {
                    $last_item_insert_id = $last_item->getItemId();
                }

                $last_item_insert = Mage::getModel('sales/quote_item')->setStoreId(Mage::app()->getStore()->getId())->load($last_item_insert_id);

                foreach ($cart->getQuote()->getAllVisibleItems() as $item) {
                    if ($item->getItemId() != $last_item_insert_id) continue;
                    $quote_item = $item;
                    break;
                }

                $this->addProductWithRule($params, $product, $quote_item);
                if (isset($params['ajax_gift']) && $params['ajax_gift']) {
                    $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');
                    $block_totals = Mage::app()->getLayout()->createBlock('checkout/cart_totals');
                    $block_freegift = Mage::app()->getLayout()->createBlock('freegift/product');
                    echo json_encode(array(
                        'message'       => '',
                        'error'         => 0,
                        'item_id'       => $quote_item->getItemId(),
                        'item_html'     => $block_cart->addItemRender($quote_item->getProduct()->getTypeId(), 'checkout/cart_item_renderer', 'mw_freegift/checkout/cart/item/default.phtml')->getItemHtml($quote_item),
                        'freegift'      => $block_freegift->toHtml(),
                    ));
                    exit;
                }
                Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                exit;
            }
        }*/

        echo json_encode(array(
            'message'   => '',
            'error'     => 0
        ));
        return false;


    }

    public function execute0()
    {
        try {
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if (!$this->cart->getQuote()->getHasError()) {
                    $message = __(
                        'You added %1 to your shopping cart.',
                        $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($product->getName())
                    );
                    echo $message;
                    $this->messageManager->addSuccess($message);
                }
                return $this->goBack(null, $product);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                echo $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage());
                $this->messageManager->addNotice(
                    $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    echo $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($message);
                    $this->messageManager->addError(
                        $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);

            if (!$url) {
                $cartUrl = $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl();
                $url = $this->_redirect->getRedirectUrl($cartUrl);
            }

            return $this->goBack($url);

        } catch (\Exception $e) {
            echo __('We can\'t add this item to your shopping cart right now.');
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            return $this->goBack();
        }
    }

    public function execute1()
    {
        $params = $this->getRequest()->getPostValue();

        if (isset($params['free_catalog_gift']))
        {
            $flag_update = true;
            if (isset($params['super_attribute'])) {
                foreach ($params['super_attribute'] as $k => $attr) {
                    if (empty($attr)) {
                        $flag_update = false;
                    }
                }
            }
            if (isset($params['options'])) {
                foreach ($params['options'] as $k => $attr) {
                    if (empty($attr)) {
                        $flag_update = false;
                    }
                }
            }
            if (!isset($params['upd'])) {
//                $block_product = Mage::app()->getLayout()->getBlockSingleton('freegift/product');
//                $missingGiftProducts = $block_product->getFreeGiftCatalogProduct();
//
//                $quote_parent_item = $this->getQuoteItemByGiftItemId($params['free_catalog_gift']);
//                $optionParentCollection = Mage::getModel('sales/quote_item_option')
//                    ->getCollection()
//                    ->addFieldToFilter('item_id', $params['free_catalog_gift']);
//
//                foreach ($optionParentCollection as $opt) {
//                    if ($opt->getCode() == 'info_buyRequest') {
//                        $infoRequest = unserialize($opt->getValue());
//                        break;
//                    }
//                }
            }

            if ($flag_update === false) {
                echo json_encode(array('message' => 'Empty options.', 'error' => 1, 'action' => 'load_in_page', 'item_id' => ''));
                exit;
            }


        }

        echo json_encode(array('message' => $this->getRequest()->getPostValue(), 'error' => 1));
        exit;



//        $cart = $this->_getCart();
//        $product = $this->_initProduct();
//        $params = $this->getRequest()->getParams();
//        Mage::getModel('freegift/observer')->checkout_cart_add_product($params, $product, $cart);
    }

    function xlog($message = 'null'){
        if(gettype($message) == 'string'){
        }else{
            $message = serialize($message);
        }
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($message)
        ;
    }


    /**
     * Resolve response
     *
     * @param string $backUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return $this|\Magento\Framework\Controller\Result\Redirect
     */
    protected function goBack($backUrl = null, $product = null)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_goBack($backUrl);
        }

        $result = [];

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;
        } else {
            if ($product && !$product->getIsSalable()) {
                $result['product'] = [
                    'statusText' => __('Out of stock')
                ];
            }
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }


}