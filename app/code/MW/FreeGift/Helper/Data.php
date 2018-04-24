<?php
namespace MW\FreeGift\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session as CustomerModelSession;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $checkoutSession;
    protected $_ruleFactory;

    protected $layoutFactory;
    protected $_layout;
    protected $cart;
    protected $_scopeConfig;
    /**
     * @var CustomerModelSession
     */
    protected $_customerSession;
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \MW\FreeGift\Model\RuleFactory $ruleFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CustomerModelSession $customerSession
        ) {
        $this->checkoutSession = $checkoutSession;
        $this->_ruleFactory = $ruleFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->layoutFactory = $layoutFactory;
        $this->_layout = $layout;
        $this->cart = $cart;
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }


    public function getStoreConfig($xmlPath)
    {
        return $this->_scopeConfig->getValue(
            $xmlPath,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Algorithm for calculating price by rule
     *
     * @param  string $actionOperator
     * @param  int $ruleAmount
     * @param  float $price
     * @return float|int
     */
    public function calcPriceRule($actionOperator, $ruleAmount, $price)
    {
        $priceRule = 0;
        switch ($actionOperator) {
            case 'to_fixed':
                $priceRule = min($ruleAmount, $price);
                break;
            case 'to_percent':
                $priceRule = $price * $ruleAmount / 100;
                break;
            case 'by_fixed':
                $priceRule = max(0, $price - $ruleAmount);
                break;
            case 'by_percent':
                $priceRule = $price * (1 - $ruleAmount / 100);
                break;
        }
        return $priceRule;
    }
    public function renderFreeGiftLabel($product)
    {
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/active',ScopeInterface::SCOPE_STORE))
            return '';

        $url_image = '';

        if ($additionalOption = $product->getCustomOption('mw_free_catalog_gift'))
        {
            if($additionalOption->getValue() == 1) {
                $url_image = $this->getStoreConfig('mw_freegift/group_general/showfreegiftlabel');
                if(!$url_image){
                    $url_image = $this->_layout->createBlock('Magento\Framework\View\Element\Template')->getViewFileUrl('MW_FreeGift::images/freegift_50.png');
                }else{
                    $url_image = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'catalog/product/placeholder/'.$url_image;
                }
                return '<span style="display: block; position: relative; z-index:2;">
                             <img src="'.$url_image.'" alt="freegift" class="label-freegift">
                         </span>';
            }
        }
        return '';
    }
    public function renderFreeGiftCatalogList($product)
    {
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/active',ScopeInterface::SCOPE_STORE))
            return '';
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/showfreegiftoncategory',ScopeInterface::SCOPE_STORE))
            return '';

        $block_freegift = $this->_layout->createBlock('MW\FreeGift\Block\Category\Freeproduct');
        $productIds = $block_freegift->getFreeGiftCatalog($product);

        if(count($productIds) > 0)
        {
            $block_item = $this->_layout->createBlock('MW\FreeGift\Block\Category\Freeproduct')->setTemplate("MW_FreeGift::freegift_catalog_list.phtml")->setProductIds($productIds)->setOriProductId($product->getId())->toHtml();
            return $block_item;
        }

        return '';
    }

    /*
     * use:
     * <?php echo $this->helper('MW\FreeGift\Helper\Data')->renderFreegitCodeForm();?>
     * in template file
     * */
    public function renderFreegitCodeForm()
    {
        if (!$this->_scopeConfig->getValue('mw_freegift/group_general/active',ScopeInterface::SCOPE_STORE))
            return '';

        $block_html = '';

        //$validator = Mage::getSingleton('freegift/validator');

        //$quote = Mage::getSingleton('checkout/session')->getQuote();
        //$items = $quote->getAllVisibleItems();

        //$_validator = $validator->getAllRuleActive(Mage::app()->getStore()->getWebsiteId(),Mage::getSingleton('customer/session')->getCustomerGroupId());
        //$ruleApplieId = $_validator->processCoupon($items);

        //if(count($ruleApplieId)>0) $displayBox = true;

        $block_html = $this->_layout->createBlock('MW\FreeGift\Block\Cart\Coupon')->setTemplate("MW_FreeGift::checkout/cart/coupon.phtml")->toHtml();
        return $block_html;
    }


    protected function processArrayGiftData($giftData, $data)
    {
        $giftData[$data['rule_id']]['rule_gift_ids'] = $data['rule_gift_ids'];
        if(isset($data['rule_product_id'])){
            $giftData[$data['rule_id']]['rule_product_id'] = $data['rule_product_id'];
        }
        return $giftData;
    }
    /*
     * return array gift
     * */
    public function getFreeGiftCatalogProduct($ruleData = null, $getOnlyGiftId = FALSE)
    {
        if(is_array($ruleData)){
            // giftDataAT, priorityAT use for action_stop = 1
            $giftData = $giftDataAT = [];
            $priority = $priorityAT = null;
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
            $customerGroupId = $this->_customerSession->getCustomerGroupId();
            foreach($ruleData as $data) {
                if (isset($data['rule_id'])) {
                    if(!isset($data['rule_gift_ids']) && isset($data['gift_product_ids']) && isset($data['coupon_type'])) {
                        $data['rule_gift_ids'] = $data['gift_product_ids'];
                    }
                    if(isset($data['action_stop']) && ($data['action_stop'] == '1')) {
                        if($priorityAT == null){
                            $priorityAT = $data['sort_order'];
                            $giftDataAT = $this->processArrayGiftData($giftDataAT, $data);
                        }else{
                            if($data['sort_order'] == $priorityAT) {
                                $giftDataAT = $this->processArrayGiftData($giftDataAT, $data);
                            }else if($data['sort_order'] < $priorityAT){
                                $priorityAT = $data['sort_order'];
                                unset($giftDataAT); $giftDataAT = [];
                                $giftDataAT = $this->processArrayGiftData($giftDataAT, $data);
                            }
                        }
                    }else{
                        if($priority == null){
                            $priority = $data['sort_order'];
                            $giftData = $this->processArrayGiftData($giftData, $data);
                        }else{
                            if($data['sort_order'] == $priority) {
                                $giftData = $this->processArrayGiftData($giftData, $data);
                            }else if($data['sort_order'] < $priority){
                                $priority = $data['sort_order'];
//                                unset($giftData); $giftData = []; //leric comment
                                $giftData = $this->processArrayGiftData($giftData, $data);

                            }else{
                                $giftData = $this->processArrayGiftData($giftData, $data);
                            }
                        }
                    }
                }
            }

            if(count($giftDataAT) > 0) {
                $giftData = $giftDataAT;
            }
            if(count($giftData)) {
                if ($getOnlyGiftId === TRUE) {
                    return $giftData = $this->_prepareFreeGiftIds($giftData);
                } else if ($getOnlyGiftId === 'getGiftOfSalesRule') {
                    $freeGiftSalesRule = [];
                    $num = 0;
                    foreach($giftData as $id => $gift){
                        $data = $ruleData[$id];
                        // $rule_ids use for save to infoBuyRequest
                        $ruleIds = [];
                        $ruleIds = explode(',',$data['gift_product_ids']);
                        if(count($ruleIds) > 1){
                            foreach($ruleIds as $k => $v){
                                $freeGiftSalesRule[$num]['rule_gift_ids'] = $v;
                                $freeGiftSalesRule[$num]['rule_id'] = $data['rule_id'];
                                $freeGiftSalesRule[$num]['name'] = $data['name'];
                                $freeGiftSalesRule[$num]['number_of_free_gift'] = $data['number_of_free_gift'];
                                $num++;
                            }
                        }else{
                            $freeGiftSalesRule[$num]['rule_id'] = $data['rule_id'];
                            $freeGiftSalesRule[$num]['rule_gift_ids'] = $data['gift_product_ids'];
                            $freeGiftSalesRule[$num]['name'] = $data['name'];
                            $freeGiftSalesRule[$num]['number_of_free_gift'] = $data['number_of_free_gift'];
                            $num++;
                        }
                    }
                    return $freeGiftSalesRule;
                } else {
                    $freeGiftCatalog = [];
                    $num = 0;
                    foreach($giftData as $rule_id => $gift) {
                        $rule_gift_ids = $gift['rule_gift_ids'];
                        $rule_product_id = $gift['rule_product_id'];

                        $data = $this->_ruleFactory->create()->_getRulesFromProductGift($rule_id,$rule_gift_ids,$websiteId,$customerGroupId,$rule_product_id);

                        if(!empty($data)) :
                            $condition_customized = unserialize($data['condition_customized']);
                            // $rule_ids use for save to infoBuyRequest
                            $ruleIds = [];
                            $ruleIds = explode(',',$data['rule_gift_ids']);
                            if (count($ruleIds) > 1) {
                                foreach($ruleIds as $k => $v) {
                                    $freeGiftCatalog[$num]['rule_gift_ids'] = $v;
                                    $freeGiftCatalog[$num]['product_id'] = $data['product_id'];
                                    $freeGiftCatalog[$num]['rule_id'] = $data['rule_id'];
                                    $freeGiftCatalog[$num]['name'] = $data['name'];
                                    $freeGiftCatalog[$num]['buy_x'] = $condition_customized['buy_x_get_y']['bx'];
                                    $num++;
                                }
                            } else {
                                $freeGiftCatalog[$num]['product_id'] = $data['product_id'];
                                $freeGiftCatalog[$num]['rule_id'] = $data['rule_id'];
                                $freeGiftCatalog[$num]['rule_gift_ids'] = $data['rule_gift_ids'];
                                $freeGiftCatalog[$num]['name'] = $data['name'];
                                $freeGiftCatalog[$num]['buy_x'] = $condition_customized['buy_x_get_y']['bx'];
                                $num++;
                            }
                        endif;
                    }
                    return $freeGiftCatalog;
                }
            }
        }

        if($getOnlyGiftId === 'getGiftOfSalesRule') {
            return $this->checkoutSession->getGiftSalesProductIds() ? $this->checkoutSession->getGiftSalesProductIds() : [];
        }

        return $this->checkoutSession->getGiftProductIds() ? $this->checkoutSession->getGiftProductIds() : [];
    }

    /**
     * var $arrayGift string
     * return array
     */
    function _prepareFreeGiftIds($arrayGift)
    {
        $rule_gift_ids = array();
        foreach ($arrayGift as $item) {
            $rule_gift_ids[] = $item['rule_gift_ids'];
        }

        $ids = implode(",",$rule_gift_ids); // gộp mảng thành chuỗi nối nhau bởi dấu ,
        $ids = explode(",",$ids); // tách chuỗi thành mảng qua dấu ,
        $ids = array_unique($ids); // xóa trùng
//        $ids = array(20);
        return $ids;
    }
    /*
     * use when save applied_rule_ids to quote option
     * return @array rule id*/
    function _prepareRuleIds($arrayGift)
    {
        $ids = [];
        foreach($arrayGift as $data){
            if(isset($data['rule_id']))
                $ids[] = $data['rule_id'];
        }
        $ids = array_unique($ids);
        return $ids;
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

    /**
     * todo: Add configuration validation, that checks if all required xml config nodes are populated
     *
     */
    const RULES_XML_PATH = 'global/jarlssen_custom_cart_validation/rules';
    /**
     * Fetch all rules from the global config
     *
     * @return array
     */
//    public function getAllRules()
//    {
//        $rulesConfig = array();
//        $rules = Mage::getConfig()
//            ->getNode(self::RULES_XML_PATH);
//        if(!empty($rules)) {
//            $rulesConfig = $rules->asCanonicalArray();
//        }
//        return $rulesConfig;
//    }
    public function dataInCart()
    {
        $cart = $this->checkoutSession;
        $layout = $this->layoutFactory->create();
        $layout->getUpdate()->load(['checkout_cart_index']);
        $layout->generateXml();
        $layout->generateElements();

//        $block_cart1 = $layout->getBlock('checkout.cart.form');

        /*
         * var Magento\Checkout\Block\Cart
         * */
        $block_cart = $layout
            ->createBlock('Magento\Checkout\Block\Cart');

        $block_cart->addChild('renderer.list', '\Magento\Framework\View\Element\RendererList');
        $block_cart->getChildBlock(
            'renderer.list'
        )->addChild(
            'default',
            '\Magento\Checkout\Block\Cart\Item\Renderer',
            ['template' => 'Magento_Checkout::cart/item/default.phtml']
        );

        $block_cart->getChildBlock(
            'renderer.list'
        )->addChild(
            'actions',
            '\Magento\Checkout\Block\Cart\Item\Renderer\Actions',
            []
        );

        $block_render_list = $block_cart->getChildBlock(
            'renderer.list'
        );
        $block_render_list->getChildBlock(
            'actions'
        )->addChild(
            'actions.edit',
            '\Magento\Checkout\Block\Cart\Item\Renderer\Actions\Edit',
            ['template' => 'Magento_Checkout::cart/item/renderer/actions/edit.phtml']
        );
        $block_render_list->getChildBlock(
            'actions'
        )->addChild(
            'actions.remove',
            '\Magento\Checkout\Block\Cart\Item\Renderer\Actions\Remove',
            ['template' => 'Magento_Checkout::cart/item/renderer/actions/remove.phtml']
        );

//        $this->xlog(
//            $block_cart->getChildNames()
//        );
//        $this->xlog(
//            $block_render_list->getChildBlock(
//                'actions'
//            )->getChildNames()
//        );
//        $block_cart->getChildBlock(
//            'renderer.list'
//        );

        $block_totals = $layout->createBlock('Magento\Checkout\Block\Cart\Totals');
        $block_freegift = $layout->createBlock('MW\FreeGift\Block\Product');
        $block_freegift_quote = $layout->createBlock('MW\FreeGift\Block\Quote')->setAttribute('ajax', 1);
        $block_freegift_banner = $layout->createBlock('MW\FreeGift\Block\Promotionbanner')->setAttribute('ajax', 1);

        $html = "";

        $quote = $this->checkoutSession->getQuote();
        if($quote->getSubtotal() == 0){
            $html = "";
        }else{
            $html_action = "";
            foreach($cart->getQuote()->getAllVisibleItems() as $item){
                $findtext = "<div class=\"actions-toolbar\">";
                $item_html  = $block_cart->getItemHtml($item);
                $pos = strpos($item_html, $findtext) + strlen($findtext);
                $html_action = $block_render_list->getChildBlock('actions')->setItem($item)->toHtml();
                $item_html = substr_replace($item_html, $html_action, $pos, 0);
                $html .= $item_html;
            }
        }

        $cart->getQuote()->collectTotals();
        $data['html_items']         = $html;
        $data['html_gift']          = $block_freegift->toHtml();
        $data['html_gift_quote']    = $block_freegift_quote->_toHtml();
        $data['html_gift_banner']   = $block_freegift_banner->_toHtml();

        $data['html_total']         = $block_totals->renderTotals();
        $data['html_grand_total']   = $block_totals->renderTotals('footer');
        return $data;
    }

}

