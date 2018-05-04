<?php
namespace MW\FreeGift\Block;
use Magento\Store\Model\ScopeInterface;
class Product extends \Magento\Framework\View\Element\Template
{
    protected $checkoutSession;
    protected $checkoutCart;
    protected $_coreRegistry;
    protected $helperFreeGift;
    protected $helperCart;
    protected $_resourceRule;
    protected $productRepository;
    protected $salesruleModel;
    protected $_ruleArr = array();
    protected $_priceBlock = array();
    protected $_free_product = array();
    protected $_block = 'catalog/product_price';
    protected $_priceBlockDefaultTemplate = 'catalog/product/price.phtml';
    protected $_priceBlockTypes = array();
//    protected $_template = 'MW_FreeGift::freegift.phtml';
    /**
     * @var string
     */
    protected $_template = 'MW_FreeGift::checkout/cart/free_gift.phtml';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \Magento\Checkout\Helper\Cart $helperCart,
        \Magento\Framework\Registry $coreRegistry,
        \MW\FreeGift\Model\ResourceModel\Rule $resourceRule,
        \MW\FreeGift\Model\SalesRule $salesruleModel,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
//        ScopeConfigInterface $scopeConfig,
//        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
//        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
//        \Magento\Customer\Helper\View $helperView,
        array $data = []
    ) {
//        $this->currentCustomer = $currentCustomer;
//        $this->_subscriberFactory = $subscriberFactory;
//        $this->_helperView = $helperView;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutCart = $checkoutCart;
        $this->helperFreeGift = $helperFreeGift;
        $this->helperCart = $helperCart;
        $this->_coreRegistry = $coreRegistry;
        $this->_resourceRule = $resourceRule;
        $this->productRepository = $productRepository;
        $this->salesruleModel = $salesruleModel;
        parent::__construct($context, $data);
    }
    /* begin move */
    public function _toHtml()
    {
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/active',ScopeInterface::SCOPE_STORE))
            return '';
        return $this->fetchView($this->getTemplateFile());
    }
    /**
     * Retrieve url for add product to cart
     * Will return product view page URL if product has required options
     *
     * @param  $product
     * @param array $additional
     * @return string
     */
    public function getAddToCartUrl($product, $additional = array())
    {
        $isRequire = false;
        foreach ($product->getOptions() as $o) {
            if($o->getIsRequire()) $isRequire = true;
        }
        if ($product->getTypeInstance(true)->hasRequiredOptions($product) || $product->isConfigurable() || $isRequire) {
            if (!isset($additional['_escape'])) {
                $additional['_escape'] = true;
            }
            if (!isset($additional['_query'])) {
                $additional['_query'] = array();
            }
            $additional['_query']['options']  = 'cart';
            $additional['_query']['freegift'] = $additional['freegift'];
            if(isset($additional['rule_id']))
                $additional['_query']['apllied_rule']  = $additional['rule_id'];
            if(isset($additional['free_catalog_gift']))
                $additional['_query']['free_catalog_gift'] = $additional['free_catalog_gift'];
            if(isset($additional['freegift_with_code']))
                $additional['_query']['freegift_with_code'] = $additional['freegift_with_code'];
            if(isset($additional['freegift_coupon_code']))
                $additional['_query']['freegift_coupon_code'] = $additional['freegift_coupon_code'];
            if (isset($additional['apllied_rule'])) {

                $additional['_query']['apllied_rule'] = $additional['apllied_rule'];
            }
            return $this->getProductUrl($product, $additional);
        }
        if($product->isGrouped()){
            $additional['_query']['freegift'] = $additional['freegift'];
            if(isset($additional['rule_id']))
                $additional['_query']['apllied_rule']  = $additional['rule_id'];
            if(isset($additional['freegift_with_code']))
                $additional['_query']['freegift_with_code'] = $additional['freegift_with_code'];
            if(isset($additional['freegift_coupon_code']))
                $additional['_query']['freegift_coupon_code'] = $additional['freegift_coupon_code'];
            if (isset($additional['apllied_rule'])) {

                $additional['_query']['apllied_rule'] = $additional['apllied_rule'];
            }
            return $this->getProductUrl($product, $additional);
        }
        return $this->helper('checkout/cart')->getAddUrl($product, $additional);
    }
    /**
     * Retrieve Product URL using UrlDataObject
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $additional the route params
     * @return string
     */
    public function getProductUrl($product, $additional = array())
    {
        if ($this->hasProductUrl($product)) {
            if (!isset($additional['_escape'])) {
                $additional['_escape'] = true;
            }
            return $product->getUrlModel()->getUrl($product, $additional);
        }
        return '#';
    }
    /**
     * Check Product has URL
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function hasProductUrl($product)
    {
        if ($product->getVisibleInSiteVisibilities()) {
            return true;
        }
        if ($product->hasUrlDataObject()) {
            if (in_array($product->hasUrlDataObject()->getVisibility(), $product->getVisibleInSiteVisibilities())) {
                return true;
            }
        }
        return false;
    }

    public function getNumberOfAddedFreeItems(){
        $items         = $this->checkoutSession->getQuote()->getAllVisibleItems();
        $countFreeItem = 0;
        foreach ($items as $item) {
            $params = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
            if (isset($params['freegift']) && $params['freegift']) {
                $countFreeItem++;
            }
        }
        return $countFreeItem;
    }
    public function getRuleApplieQuote()
    {
        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();

        if (count($items) < 1) {
            $quote = $this->checkoutSession->getQuote();
            $quote->setFreegiftAppliedRuleIds('');
            $quote->setFreegiftIds('');
        }
        if ($ruleids = $this->checkoutSession->getQuote()->getFreegiftAppliedRuleIds()) {
            return explode(",", $ruleids);
        }
        return false;
    }
    public function getFreeProducts()
    {
        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
        if (count($items) < 1) {
            $quote = $this->checkoutSession->getQuote();
            $quote->setFreegiftAppliedRuleIds('');
            $quote->setFreegiftIds('');
        }
        $listProduct = array();

        if ($freeids = $this->checkoutSession->getQuote()->getFreegiftIds()) {
            $this->_free_product = explode(",", $freeids);
        }

        return $this->_free_product;
    }
    public function getProductIdByRuleFree($ruleId)
    {
        $rules = $this->salesruleModel->load($ruleId);
        $giftProductIds = explode(',', $rules->getGiftProductIds());
        return $giftProductIds;
    }
    public function getMaxFreeItem()
    {
        $kbc = $this->_free_product;
        $arr = array();
        if ($kbc != null) {
            foreach ($kbc as $value) {
                $rules = $this->getRulesByProductId($value);
                if ($rules){
                    foreach($rules as $rule){
                        $arr[$rule->getId()] = $rule->getNumberOfFreeGift();
                    }
                }
            }
        }
        return $arr;
    }
    public function getNumberOfFreeGift()
    {
        $kbc = $this->getFreeProducts();
        $dem = 0;
        if ($kbc != null) {
            foreach ($kbc as $value) {
                $abc = $this->getRuleByFreeProductId($value);
                if ($abc)
                    $arr[] = $abc->getId();
            }
            ksort($arr);
            for ($i = 0; $i < count((array_unique($arr))); $i++) {
                $rule = $this->salesruleModel->load($arr[$i]);
                $dem += $rule->getNumberOfFreeGift();
            }
        }
        return $dem;
    }

    public function getRuleByFreeProductId($productId)
    {
        $quote        = $this->checkoutSession->getQuote();
        $aplliedRules = $quote->getFreegiftAppliedRuleIds();
        $aplliedRules = explode(',', $aplliedRules);
        foreach ($aplliedRules as $rule_id) {
            $rule       = $this->salesruleModel->load($rule_id);
            $productIds = explode(',', $rule->getData('gift_product_ids'));
            if (in_array($productId, $productIds)) {
                return $rule;
            }
        }
        return false;
    }
    public function getRulesByProductId($productId)
    {
        $quote        = $this->checkoutSession->getQuote();
        $aplliedRules = $quote->getFreegiftAppliedRuleIds();
        $aplliedRules = explode(',', $aplliedRules);
        $rules = array();
        foreach ($aplliedRules as $rule_id) {
            $rule       = $this->salesruleModel->load($rule_id);
            $productIds = explode(',', $rule->getData('gift_product_ids'));
            if (in_array($productId, $productIds)) {
                $rules[] = $rule;
            }
        }
        return $rules;
    }

    public function getPriceBlockTemplate()
    {
        return $this->_getData('freegift_price_block_template');
    }

    /**
     * Prepares and returns block to render some product type
     *
     * @param string $productType
     * @return Mage_Core_Block_Template
     */
    public function _preparePriceRenderer($productType)
    {
        return $this->_getPriceBlock($productType)->setTemplate($this->_getPriceBlockTemplate($productType))->setUseLinkForAsLowAs($this->_useLinkForAsLowAs);
    }
    /**
     * Returns product price block html
     *
     * @param Mage_Catalog_Model_Product $product
     * @param boolean $displayMinimalPrice
     * @param string $idSuffix
     * @return string
     */
    public function getPriceHtml($product, $displayMinimalPrice = false, $idSuffix = '')
    {
        return $this->_preparePriceRenderer($product->getTypeId())->setProduct($product)->setDisplayMinimalPrice($displayMinimalPrice)->setIdSuffix($idSuffix)->toHtml();
    }
    protected function _getPriceBlock($productTypeId)
    {
        if (!isset($this->_priceBlock[$productTypeId])) {
            $block = $this->_block;
            if (isset($this->_priceBlockTypes[$productTypeId])) {
                if ($this->_priceBlockTypes[$productTypeId]['block'] != '') {
                    $block = $this->_priceBlockTypes[$productTypeId]['block'];
                }
            }
            $this->_priceBlock[$productTypeId] = $this->getLayout()->createBlock($block);
        }
        return $this->_priceBlock[$productTypeId];
    }
    protected function _getPriceBlockTemplate($productTypeId)
    {
        if (isset($this->_priceBlockTypes[$productTypeId])) {
            if ($this->_priceBlockTypes[$productTypeId]['template'] != '') {
                return $this->_priceBlockTypes[$productTypeId]['template'];
            }
        }
        return $this->_priceBlockDefaultTemplate;
    }

    /* not use */
    public function getRuleFreeProductIds($productId)
    {
        $rules = $this->salesruleModel->getCollection();
        foreach ($rules as $rule) {
            $productIds = explode(',', $rule->getData('gift_product_ids'));
            if (in_array($productId, $productIds)) {
                return $rule;
            }
        }
        return false;
    }
    public function getItemProductHtml($data){
        $block       = Mage::getSingleton('core/layout');
        $freegiftbox = $block->createBlock('freegift/product_item')->setTemplate('mw_freegift/freegift_catalog.phtml')->setData($data);
        return $freegiftbox->fetchView($this->getTemplateFile());
    }
    public function _canAddFreeGift($ruleId,$productId){
        $canAdd = true;
        $items  = $this->checkoutSession->getQuote()->getAllVisibleItems();
        foreach ($items as $it) {
            $params = unserialize($it->getOptionByCode('info_buyRequest')->getValue());
            if(isset($params['apllied_rule'])){
                if($params['apllied_rule'] == $ruleId && $params['product'] == $productId) $canAdd = false;
            }
            if(isset($params['rule_id'])){
                if($params['rule_id'] == $ruleId && $params['product'] == $productId) $canAdd = false;
            }
        }
        return $canAdd;
    }
    public function _displayFreeGift()
    {
        $ruleApplieFree  = $this->getRuleApplieQuote();
        $productIds      = $this->getFreeProducts();
        $maxFreeItems    = $this->getMaxFreeItem();
        $itemsFirst      = $this->checkoutSession->getQuote()->getAllVisibleItems();
        $strFirst        = "";
        $display 		 = true;
        $skip = false;
        if ($productIds) {
            /* start check for rules */
            $items         = $this->checkoutSession->getQuote()->getAllVisibleItems();
            $countFreeGift = 0;
            foreach ($items as $item) {
                $params1 = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                $ruleApply = "";
                if(isset($params1['rule_id'])) $ruleApply = $params1['rule_id'];
                else if(isset($params1['apllied_rule'])){
                    /*$countFreeGift++;*/
                    $ruleApply = $params1['apllied_rule'];
                }
                else $ruleApply = "";
                if ((isset($params1['freegift']) || isset($params1['freegift_coupon_code'])) && in_array($ruleApply, $this->_ruleArr)) {
                    $countFreeGift++;
                }
            }

            if ($countFreeGift >= $this->_numberFreeAllow) {
                $display = false;
            }
        }else{
            $display = false;
        }

        return $display;
    }
    public function addProductsByRuleCart(){
        $cart    = $this->checkoutCart;
        $ruleApplieFree  = $this->getRuleApplieQuote();
        $productIds      = $this->getFreeProducts();
        $maxFreeItems    = $this->getMaxFreeItem();

        foreach ($maxFreeItems as $key => $value) {
            $ruleId        = $key;
            $this->_ruleArr[] = $ruleId;
            $productByRule = $this->getProductIdByRuleFree($ruleId);
            if ($ruleId == $key) {
                $maxFree = $value;

                // Auto add product to cart if numberoffreegift equal freegift item.
                if (count($productByRule) <= $maxFree) {
                    foreach ($productByRule as $proId) {
                        $canAdd = $this->_canAddFreeGift($key,$proId);

                        if($canAdd){
                            $product = $this->productRepository->getById($proId);
                            $isRequire = false;
                            foreach ($product->getOptions() as $o) {
                                if($o->getIsRequire()) $isRequire = true;
                            }
                            if(($product->getTypeId()=='simple') && !$product->getTypeInstance(true)->hasRequiredOptions($product) && !$isRequire){
                                $rule = $this->salesruleModel->load($ruleId);
                                $params1 = array(
                                    'product' => $proId, // This would be $product->getId()
                                    'qty' => 1,
                                    'freegift' => 1,
                                    'apllied_rule' => $ruleId,
                                    'in_cart' => 1,
                                    'text_gift' => array(
                                        'label' => 'Free Gift',
                                        'value' => $rule->getName()
                                    )
                                );
                                $product->addCustomOption('freegift', 1);
                                $cart->addProduct($product, $params1);
                                $cart->save();
                                $this->helperCart->getCart()->setCartWasUpdated(false);
                            }
                        }
                    }
                }
            }
        }
    }

    public function checkProductInQuote($ruleId, $productId)
    {
        $items          = $this->checkoutSession->getQuote()->getAllVisibleItems();
        $dem            = 0;
        $rules          = $this->salesruleModel->load($ruleId);
        $giftProductIds = explode(',', $rules->getGiftProductIds());
        $max            = $rules->getNumberOfFreeGift();
        foreach ($items as $it) {
            $productQuote = $it->getProductId();
            $params1      = unserialize($it->getOptionByCode('info_buyRequest')->getValue());
            if (isset($params1['apllied_rule'])) {
                if (in_array($productQuote, $giftProductIds) && !isset($params1['free_catalog_gift']) && $params1['apllied_rule'] == $ruleId) {
                    $dem++;
                }
                if ($productQuote == $productId && $params1['apllied_rule'] == $ruleId) {
                    return false;
                }
            }
            if (isset($params1['apllied_rules'])) {
                $ruleAppli = unserialize($params1['apllied_rules']);
                if ($productQuote == $productId && $ruleAppli[0] == $ruleId) {
                    return false;
                }
            }
            if (isset($params1['rule_id'])) {
                $rule_id = $params1['rule_id'];
                if (in_array($productQuote, $giftProductIds) && !isset($params1['free_catalog_gift']) && $rule_id == $ruleId) {
                    $dem++;
                }
                if ($productQuote == $productId && $rule_id == $ruleId) {
                    return false;
                }
            }
        }
        if ($dem >= $max) {
            return false;
        }
        return true;
    }
    /* moved */
    /**
     * Returns the Magento Customer Model for this block
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        try {
            return $this->currentCustomer->getCustomer();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get the full name of a customer
     *
     * @return string full name
     */
    public function getName()
    {
        return ($this->checkoutSession->getGiftProductIds() ? json_encode($this->checkoutSession->getGiftProductIds()) : '' );
    }

    public function getFreeGiftCatalog(){
        $freeGiftCatalogData = []; $product_ids = []; $item_ids = []; $item_product_ids = [];
//        $this->xlog("Product ".__LINE__);
        $freeGiftCatalogData = $this->helperFreeGift->getGiftDataByRule();
        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
        if(count($items) > 0) {
            foreach($items as $item) {
                // get product gift in cart
                if($item->getOptionByCode('additional_options')) {
                    $params = unserialize($item->getOptionByCode('additional_options')->getValue());
                    foreach($params as $param) {
                        if(isset($param['mw_freegift_rule_gift']) && $param['mw_freegift_rule_gift'] == 1) {
                            $product_ids[] = $item->getProduct()->getId();
                        }
                    }
                }
                // get parent product gift
                $info_buyRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
//                $this->xlog($info_buyRequest);
//                $this->xlog(__LINE__.' '.__METHOD__);
                if(isset($info_buyRequest['freegift_key'])) {
                    $item_product_ids[] = $item->getProductId(); //13
                    $item_ids[$item->getProductId()] = $item->getItemId(); //27
                }
            }
            // unset product gift in cart
            if(count($product_ids) > 0) {
                foreach($freeGiftCatalogData as $key => $value) {
                    if(in_array($value['rule_gift_ids'],$product_ids)) {
                        unset($freeGiftCatalogData[$key]);
                    }
                }
            }

//            $this->xlog($freeGiftCatalogData);
//            Array
//            (
//                [0] => Array
//                (
//                    [product_id] => 13
//                    [rule_id] => 1
//                    [rule_gift_ids] => 20
//                    [name] => Category is 4
//                )
//            )
//            $this->xlog($item_product_ids);
//            Array
//            (
//                [0] => 13
//            )

            // add item_id to array by parent
            if(count($item_product_ids) > 0) {
                foreach($freeGiftCatalogData as $key => $value) {
                    if(in_array($value['product_id'],$item_product_ids)) {
//                        $this->xlog(__FILE__ . ' - ' . __LINE__);
                        $freeGiftCatalogData[$key]['item_id'] = $item_ids[$value['product_id']];
                    } else {
                        $freeGiftCatalogData[$key]['item_id'] = 0;
                    }
                }
            } else {
                $this->checkoutSession->unsGiftProductIds();
                return $freeGiftCatalogData = [];
            }

//            $this->xlog("freeGiftCatalogData");
//            $this->xlog($freeGiftCatalogData);
            return $freeGiftCatalogData;
        }else{
            return $freeGiftCatalogData = [];
        }
    }

    public function getFreeGiftSalesRule(){
        $freeGiftSalesRuleData = array();
        $freeGiftSalesRuleData = $this->helperFreeGift->getGiftDataBySalesRule();


        $product_ids = []; $item_ids = []; $item_product_ids = [];
        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
        if(count($items) > 0){
            foreach($items as $item){
                // get product gift in cart
                if($item->getOptionByCode('info_buyRequest')){
                    $params = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                    if(isset($params['freegift_with_code']) && $params['freegift_with_code'] == '1'){
                        $product_ids[] = $item->getProduct()->getId();
                    }else if(isset($params['freegift']) && $params['freegift'] == '1'){
                        $product_ids[] = $item->getProduct()->getId();
                    }else if(isset($params['super_product_config']) && count($params['super_product_config']) > 0 && isset($params['super_product_config']['product_id']) ){
                        $product_ids[] = $params['super_product_config']['product_id'];
                    }
                }
            }

            // unset product gift in cart
            if(count($product_ids) > 0){
                foreach($freeGiftSalesRuleData as $key => $value){
                    if(in_array($value['rule_gift_ids'],$product_ids)){
                        unset($freeGiftSalesRuleData[$key]);
                    }
                }
            }
            return $freeGiftSalesRuleData;
        }else{
            return $freeGiftSalesRuleData = [];
        }
    }

    /**
     * @return string
     */
    public function getFreegiftCode()
    {
        return $this->checkoutSession->getQuote()->getFreegiftCouponCode();
    }

//    public function getProductGiftIds()
//    {
//        $product_ids = [];
//        $missingGiftProducts = [];
//
//        $giftIds = $this->helperFreeGift->getFreeGiftCatalogProduct();
//        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
//        foreach($items as $item){
////            print_r($item->getOptionByCode('additional_options')->getValue());
//            if($item->getOptionByCode('additional_options')){
//                $params = unserialize($item->getOptionByCode('additional_options')->getValue());
//                foreach($params as $param){
//                    if(isset($param['mw_freegift_rule_gift']) && $param['mw_freegift_rule_gift'] == 1){
//                        $product_ids[] = $item->getProduct()->getId();
//                    }
//                }
//            }
//        }
//
//        $missingGiftProducts = array_diff($giftIds, $product_ids);
//        return $missingGiftProducts;
//    }
    public function getProductGiftData($productId)
    {
        // @@TODO: get current store id
        $storeId = 1;
        $product_gift = $this->productRepository->getById($productId, false, $storeId);

//        $product_gift->addCustomOption('gift', 1);
//
//        $additionalOptions = [[
//            'label' => 'Free Gift',
//            'value' => 'Free Gift with Rule',
//            'print_value' => 'Free Gift with Rule',
//            'option_type' => 'text',
//            'custom_view' => TRUE,
//            'mw_freegift_rule_gift' => 1
//        ]];
//        // add the additional options array with the option code additional_options
//        $product_gift->addCustomOption('additional_options', serialize($additionalOptions));
        return $product_gift;
    }

    /**
     * @return string
     */
    public function getChangePasswordUrl()
    {
        return $this->_urlBuilder->getUrl('customer/account/edit/changepass/1');
    }

    /**
     * Get Customer Subscription Object Information
     *
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function getSubscriptionObject()
    {
        if (!$this->_subscription) {
            $this->_subscription = $this->_createSubscriber();
            $customer = $this->getCustomer();
            if ($customer) {
                $this->_subscription->loadByEmail($customer->getEmail());
            }
        }
        return $this->_subscription;
    }

    /**
     * Gets Customer subscription status
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsSubscribed()
    {
        return $this->getSubscriptionObject()->isSubscribed();
    }

    /**
     * Newsletter module availability
     *
     * @return bool
     */
    public function isNewsletterEnabled()
    {
        return $this->getLayout()->getBlockSingleton('Magento\Customer\Block\Form\Register')->isNewsletterEnabled();
    }

    /**
     * @return \Magento\Newsletter\Model\Subscriber
     */
    protected function _createSubscriber()
    {
        return $this->_subscriberFactory->create();
    }

    /**
     * @return string
     */
//    protected function _toHtml()
//    {
//        return $this->currentCustomer->getCustomerId() ? parent::_toHtml() : '';
//    }

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

    function getHello(){
        return "Good Morning";
    }
}