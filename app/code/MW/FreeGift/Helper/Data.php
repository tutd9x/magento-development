<?php
namespace MW\FreeGift\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session as CustomerModelSession;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $checkoutSession;
    protected $_ruleFactory;
    protected $_salesruleFactory;

    protected $layoutFactory;
    protected $_layout;
    protected $cart;
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
        \MW\FreeGift\Model\SalesRuleFactory $salesRuleFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CustomerModelSession $customerSession
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->_ruleFactory = $ruleFactory;
        $this->_salesruleFactory = $salesRuleFactory;
        $this->layoutFactory = $layoutFactory;
        $this->_layout = $layout;
        $this->cart = $cart;
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }

    protected function resetSession()
    {
        $this->checkoutSession->unsetData('gift_product_ids');
        $this->checkoutSession->unsetData('gift_sales_product_ids');
        $this->checkoutSession->unsetData('sales_gift_removed');
        $this->checkoutSession->unsRulegifts();
        $this->checkoutSession->unsProductgiftid();
        $this->checkoutSession->unsGooglePlus();
        $this->checkoutSession->unsLikeFb();
        $this->checkoutSession->unsShareFb();
        $this->checkoutSession->unsTwitter();

        return $this;
    }

    public function getStoreConfig($xmlPath)
    {
        return $this->scopeConfig->getValue(
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
        if (!$this->scopeConfig->getValue('mw_freegift/group_general/active', ScopeInterface::SCOPE_STORE)) {
            return '';
        }

        $url_image = '';

        if ($additionalOption = $product->getCustomOption('mw_free_catalog_gift')) {
            if ($additionalOption->getValue() == 1) {
                $url_image = $this->getStoreConfig('mw_freegift/group_general/showfreegiftlabel');
                if (!$url_image) {
                    $url_image = $this->_layout->createBlock('Magento\Framework\View\Element\Template')->getViewFileUrl('MW_FreeGift::images/freegift_50.png');
                } else {
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
        if (!$this->scopeConfig->getValue('mw_freegift/group_general/active', ScopeInterface::SCOPE_STORE)) {
            return '';
        }
        if (!$this->scopeConfig->getValue('mw_freegift/group_general/showfreegiftoncategory', ScopeInterface::SCOPE_STORE)) {
            return '';
        }

        $block_freegift = $this->_layout->createBlock('MW\FreeGift\Block\Category\Freeproduct');
        $productIds = $block_freegift->getFreeGiftCatalog($product);

        if (!empty($productIds)) {
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
        if (!$this->scopeConfig->getValue('mw_freegift/group_general/active', ScopeInterface::SCOPE_STORE)) {
            return '';
        }

        $block_html = '';

        $block_html = $this->_layout->createBlock('MW\FreeGift\Block\Cart\Coupon')->setTemplate("MW_FreeGift::checkout/cart/coupon.phtml")->toHtml();
        return $block_html;
    }

    protected function processArrayGiftData($giftData, $data)
    {
        $giftData[$data['rule_id']]['rule_gift_ids'] = $data['rule_gift_ids'];
        if (isset($data['rule_product_id'])) {
            $giftData[$data['rule_id']]['rule_product_id'] = $data['rule_product_id'];
        }
        $giftData[$data['rule_id']]['sort_order'] =  $data['sort_order'];
        return $giftData;
    }

    public function getGiftDataByRule($ruleData, $getOnlyGiftId = false)
    {
        $giftData = [];
        $num = 0;
        foreach ($ruleData as $data) {
            $condition_customized = unserialize($data['condition_customized']);
            $giftIds = [];
            $giftIds = explode(',', $data['rule_gift_ids']);

            if (count($giftIds) >= 1) {
                foreach ($giftIds as $giftId) {
                    $giftData[$num]['rule_id'] = $data['rule_id'];
                    $giftData[$num]['name'] = $data['name'];
                    $giftData[$num]['product_id'] = $data['product_id'];
                    $giftData[$num]['rule_product_id'] = $data['rule_product_id'];
                    $giftData[$num]['gift_id'] = $giftId;
                    $giftData[$num]['buy_x'] = $condition_customized['buy_x_get_y']['bx'];
                    $giftData[$num]['freegift_parent_key'] = $data['rule_product_id'] . '_' . $data['rule_id'] . '_' . $data['product_id'] . '_' . $giftId;
                    $num++;
                }
            }
        }

        if ($getOnlyGiftId === true) {
            return $giftData = $this->_prepareFreeGiftIds($giftData);
        }

        return $giftData;
    }

    public function getGiftDataBySalesRule($ruleData, $getOnlyGiftId = false)
    {
        $giftData = [];
        $num = 0;
        foreach ($ruleData as $data) {
            $giftIds = [];
            $giftIds = explode(',', $data['gift_product_ids']);

            if (count($giftIds) >= 1) {
                foreach ($giftIds as $giftId) {
                    $giftData[$num]['rule_id'] = $data['rule_id'];
                    $giftData[$num]['name'] = $data['name'];
                    $giftData[$num]['gift_id'] = $giftId;
                    $giftData[$num]['number_of_free_gift'] = $data['number_of_free_gift'];
                    $giftData[$num]['freegift_sales_key'] = $data['rule_id'] .'_'. $giftId .'_'. $data['number_of_free_gift'];
                    $num++;
                }
            }
        }
        return $giftData;
    }

    public function getSalesKeysByGiftData($giftData)
    {
        $result = [];
        foreach ($giftData as $data) {
            $key = $data['freegift_sales_key'];
            $result[$data['rule_id']][$key] = $key;
        }
        return $result;
    }

    /**
     * var $arrayGift string
     * return array
     */
    function _prepareFreeGiftIds($arrayGift)
    {
        $rule_gift_ids = [];
        foreach ($arrayGift as $item) {
            $rule_gift_ids[] = $item['gift_id'];
        }

        $ids = implode(",", $rule_gift_ids); // gộp mảng thành chuỗi nối nhau bởi dấu ,
        $ids = explode(",", $ids); // tách chuỗi thành mảng qua dấu ,
        $ids = array_unique($ids); // xóa trùng

        return $ids;
    }

    /**
     *
     * @return array()
     */

    public function _removed_getFreeGiftCatalogProduct($ruleData = null, $getOnlyGiftId = false)
    {
        if (is_array($ruleData)) {
            // giftDataAT, priorityAT use for action_stop = 1
            $giftData = $giftDataAT = [];
            $priority = $priorityAT = null;
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
            $customerGroupId = $this->_customerSession->getCustomerGroupId();

            foreach ($ruleData as $data) {
                if (isset($data['rule_id'])) {
                    if (!isset($data['rule_gift_ids']) && isset($data['gift_product_ids']) && isset($data['coupon_type'])) {
                        $data['rule_gift_ids'] = $data['gift_product_ids'];
                    }
                    if (isset($data['action_stop']) && ($data['action_stop'] == '1')) {
                        if ($priorityAT == null) {
                            $priorityAT = $data['sort_order'];
                            $giftDataAT = $this->processArrayGiftData($giftDataAT, $data);
                        } else {
                            if ($data['sort_order'] == $priorityAT) {
                                $giftDataAT = $this->processArrayGiftData($giftDataAT, $data);
                            } elseif ($data['sort_order'] < $priorityAT) {
                                $priorityAT = $data['sort_order'];
                                unset($giftDataAT);
                                $giftDataAT = [];
                                $giftDataAT = $this->processArrayGiftData($giftDataAT, $data);
                            }
                        }
                    } else {
                        if ($priority == null) {
                            $priority = $data['sort_order'];
                            $giftData = $this->processArrayGiftData($giftData, $data);
                        } else {
                            if ($data['sort_order'] == $priority) {
                                $giftData = $this->processArrayGiftData($giftData, $data);
                            } elseif ($data['sort_order'] < $priority) {
                                $priority = $data['sort_order'];
                                $giftData = $this->processArrayGiftData($giftData, $data);
                            } else {
                                $giftData = $this->processArrayGiftData($giftData, $data);
                            }
                        }
                    }
                }
            }

            if (!empty($giftDataAT)) {
                $giftData = $giftDataAT;
            }
            if (!empty($giftData)) {
                if ($getOnlyGiftId === true) {
                    return $giftData = $this->_prepareFreeGiftIds($giftData);
                } elseif ($getOnlyGiftId === 'getGiftOfSalesRule') {
                    $freeGiftSalesRule = [];
                    $num = 0;
                    foreach ($giftData as $id => $gift) {
                        $data = $ruleData[$id];
                        // $rule_ids use for save to infoBuyRequest
                        $ruleIds = [];
                        $ruleIds = explode(',', $data['gift_product_ids']);
                        if (count($ruleIds) > 1) {
                            foreach ($ruleIds as $k => $v) {
                                $freeGiftSalesRule[$num]['rule_gift_ids'] = $v;
                                $freeGiftSalesRule[$num]['rule_id'] = $data['rule_id'];
                                $freeGiftSalesRule[$num]['name'] = $data['name'];
                                $freeGiftSalesRule[$num]['number_of_free_gift'] = $data['number_of_free_gift'];
                                $num++;
                            }
                        } else {
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
                    foreach ($giftData as $rule_id => $gift) {
                        $rule_gift_ids = $gift['rule_gift_ids'];
                        $rule_product_id = $gift['rule_product_id'];

                        $data = $this->_ruleFactory->create()->_getRulesFromProductGift($rule_id, $rule_gift_ids, $websiteId, $customerGroupId, $rule_product_id);

                        if (!empty($data)) :
                            $condition_customized = unserialize($data['condition_customized']);
                            // $rule_ids use for save to infoBuyRequest
                            $ruleIds = [];
                            $ruleIds = explode(',', $data['rule_gift_ids']);
                            if (count($ruleIds) > 1) {
                                foreach ($ruleIds as $k => $v) {
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

        if ($getOnlyGiftId === 'getGiftOfSalesRule') {
            return $this->checkoutSession->getGiftSalesProductIds() ? $this->checkoutSession->getGiftSalesProductIds() : [];
        }

        return $this->checkoutSession->getGiftProductIds() ? $this->checkoutSession->getGiftProductIds() : [];
    }

    /*
     * use when save applied_rule_ids to quote option
     * return @array rule id*/
    function _prepareRuleIds($arrayGift)
    {
        $ids = [];
        foreach ($arrayGift as $data) {
            if (isset($data['rule_id'])) {
                $ids[] = $data['rule_id'];
            }
        }
        $ids = array_unique($ids);
        return $ids;
    }

    const RULES_XML_PATH = 'global/jarlssen_custom_cart_validation/rules';

    public function dataInCart()
    {
        $cart = $this->checkoutSession;
        $layout = $this->layoutFactory->create();
        $layout->getUpdate()->load(['checkout_cart_index']);
        $layout->generateXml();
        $layout->generateElements();

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

        $block_totals = $layout->createBlock('Magento\Checkout\Block\Cart\Totals');
        $block_freegift = $layout->createBlock('MW\FreeGift\Block\Product');
        $block_freegift_quote = $layout->createBlock('MW\FreeGift\Block\Quote')->setAttribute('ajax', 1);
        $block_freegift_banner = $layout->createBlock('MW\FreeGift\Block\Promotionbanner')->setAttribute('ajax', 1);

        $html = "";

        $quote = $this->checkoutSession->getQuote();
        if ($quote->getSubtotal() == 0) {
            $html = "";
        } else {
            $html_action = "";
            foreach ($cart->getQuote()->getAllVisibleItems() as $item) {
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

    public function getProductGiftAvailable()
    {
        /** @var \Magento\Quote\Model\Quote  */
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();

        $parentData = [];
        $giftData = [];
        foreach ($items as $item) {
            /* Lay product chua Gift*/
            if ($item->getOptionByCode('mw_free_catalog_gift') && $item->getOptionByCode('mw_free_catalog_gift')->getValue() == 1) {
                $key = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                $freegift_keys = isset($key['freegift_keys']) ? $key['freegift_keys'] : null;
                array_push($parentData, $freegift_keys);
            }

            /* Lay product Gift*/
            if ($item->getOptionByCode('free_catalog_gift') && $item->getOptionByCode('free_catalog_gift')->getValue() == 1) {
                $data_buy = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                if (array_key_exists('freegift_parent_key', $data_buy)) {
                    array_push($giftData, $data_buy['freegift_parent_key']);
                } else {
                    array_push($giftData, $data_buy['free_sales_key']);
                }
            }
        }
        $results = $this->checkDiffGifts($parentData, $giftData);
        if (count($results) == 0) {
            return [];
        }
        $listGiftProductId = [];
        $i = 0;
        foreach ($results as $result) {
            $keyData = $this->splitKey($result);
            $pars = $this->getParentOfGift($result);
            $listGiftProductId[] = $keyData;
            $listGiftProductId[$i]['freegift_parent_key'] = $result;
            $listGiftProductId[$i]['rule_id'] = $keyData['rule_id'];
            $ruleData = $this->_ruleFactory->create()->load($keyData['rule_id']);
            $listGiftProductId[$i]['rule_name'] = $ruleData->getName();
            $listGiftProductId[$i]['qty'] = 0;
            foreach ($pars as $par) {
                $parentItem = $this->checkoutSession->getQuote()->getItemById($par);
                $condition_customized = unserialize(unserialize($parentItem->getOptionByCode('info_buyRequest')->getValue())['freegift_rule_data'][$keyData['rule_id']]['condition_customized']);
                $buyX = $condition_customized['buy_x_get_y']['bx'];
                $listGiftProductId[$i]['qty'] += $parentItem->getQty() * $buyX;
            }
            $i++;
        }
        $keyRemove = [];
        foreach ($listGiftProductId as $keyA => $giftA) {
            foreach ($listGiftProductId as $keyB => $giftB) {
                if ($keyA != $keyB && !in_array($keyA, $keyRemove)) {
                    if ($giftA['freegift_parent_key'] == $giftB['freegift_parent_key']) {
                        $keyRemove[] = $keyB;
//                        $listGiftProductId[$keyA]['qty'] += $giftB['qty'];
                        unset($listGiftProductId[$keyB]);
                    }
                }
            }
        }
        return $listGiftProductId;
    }
    public function checkDiffGifts($parentData, $giftData)
    {
        $parent = [];
        $gift = [];

        foreach ($parentData as $keyp) {
            if (!empty($keyp)) {
                foreach ($keyp as $key) {
                    $parent[] = $key;
                }
            }
        }

        foreach ($giftData as $keyg) {
            foreach ($keyg as $key) {
                $gift[] = $key;
            }
        }

        $diffData = array_diff($parent, $gift);
        return $diffData;
    }

    public function splitKey($key)
    {
        $data = explode('_', $key);
        $result = [
            'key_id' => $data[0],
            'rule_id' => $data[1],
            'product_parent_id' => $data[2],
            'product_gift_id' => $data[3]
        ];
        return $result;
    }

    public function getProductGiftSalesRuleAvailable()
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();
        if (count($items) >= 0 && $quote->getSubtotal() == 0) {
            $this->resetSession();
            return [];
        }

        $productGift = $quote->getFreegiftIds();
        $rules  = $quote->getFreegiftAppliedRuleIds(); //1,2
        $giftDataByRule = $this->checkoutSession->getGiftSalesProductIds();
        $allGift = [];
        if (!$giftDataByRule) {
            return [];
        }
        foreach ($giftDataByRule as $gift) {
            $free_sales_key = $gift['rule_id'].'_'.$gift['gift_id'].'_'.$gift['number_of_free_gift'];
            $allGift[] = $free_sales_key;
        }
        $productList = explode(",", $productGift);
        $ruleList = explode(",", $rules);

        $items = $quote->getAllVisibleItems();

        $parentData = $productList;
        $giftData = [];
        foreach ($items as $item) {
            /* Lay product Gift trong cart */
            if ($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1) {
                $key = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                $keyVal = $key['free_sales_key'];
                $giftData[] = $keyVal;
            }
        }
        $diffGifts = $this->checkDiffSalesRuleGifts($allGift, $giftData);
        if (count($diffGifts) == 0) {
            return [];
        }
        $listGiftProductId = [];
        $i = 0;
        foreach ($diffGifts as $result) {
            $keyData = $this->splitSalesRuleKey($result);
            $listGiftProductId[$i]['rule_id'] = $keyData['rule_id'];
            $listGiftProductId[$i]['rule_gift_ids'] = $keyData['rule_gift_ids'];
            $listGiftProductId[$i]['gift_id'] = $keyData['gift_id'];
                $listGiftProductId[$i]['number_of_free_gift'] = $keyData['number_of_free_gift'];
            $ruleData = $this->_salesruleFactory->create()->load($keyData['rule_id']);
            $listGiftProductId[$i]['rule_name'] = $ruleData->getName();
            $listGiftProductId[$i]['is_able'] = $this->checkAbleSalesRuleGift($keyData['rule_id'], $keyData['number_of_free_gift']) ? 1 : 0;
            $i++;
        }
        return $listGiftProductId;
    }

    public function splitSalesRuleKey($key)
    {
        $data = explode('_', $key);
        $result = [
            'rule_gift_ids' => $data[1],
            'gift_id' => $data[1],
            'rule_id' => $data[0],
            'rule_name' => $data[0],
            'number_of_free_gift' => $data[2]
        ];
        return $result;
    }

    public function checkDiffSalesRuleGifts($parentData, $giftData)
    {
        $parent = $parentData;
        $gift = [];

        foreach ($giftData as $keyg) {
            foreach ($keyg as $key) {
                $gift[] = $key;
            }
        }

        $diffData = array_diff($parent, $gift);
        return $diffData;
    }

    public function checkAbleSalesRuleGift($ruleId, $numberGifts)
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();
        if (count($items) >= 0 && $quote->getSubtotal() == 0) {
            $this->resetSession();
            return false;
        }

        $giftDataByRule = $this->checkoutSession->getGiftSalesProductIds();
        $allGift = [];
        if (!$giftDataByRule) {
            return [];
        }
        foreach ($giftDataByRule as $gift) {
            $free_sales_key = $gift['rule_id'].'_'.$gift['gift_id'].'_'.$gift['number_of_free_gift'];
            $allGift[] = $free_sales_key;
        }
        $items = $quote->getAllVisibleItems();
        $giftData = [];
        foreach ($items as $item) {
            /* Lay product Gift trong cart */
            if ($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1) {
                $key = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                $keyVal = $key['free_sales_key'];
                $giftData[] = $keyVal;
            }
        }
        $diffGifts = $this->checkIntersectSalesRuleGifts($allGift, $giftData);
        $giftsOfRuleInCart = 0;
        foreach ($diffGifts as $result) {
            $keyData = $this->splitSalesRuleKey($result);
            if ($keyData['rule_id'] == $ruleId) {
                $giftsOfRuleInCart++;
            }
        }
        if ($giftsOfRuleInCart >= $numberGifts) {
            return false;
        }
        return true;
    }

    public function checkIntersectSalesRuleGifts($parentData, $giftData)
    {
        $parent = $parentData; //array();
        $gift = [];

        foreach ($giftData as $keyg) {
            foreach ($keyg as $key) {
                $gift[] = $key;
            }
        }
        $diffData = array_intersect($parent, $gift);
        return $diffData;
    }

    /**
     * Counting gift item in cart
     * @param $gift
     * @param $parent_keys
     * @return int $count
     */
    public function getParentOfGift($gift_keys)
    {

        $resultParent = [];
        foreach ($this->checkoutSession->getQuote()->getAllItems() as $item) {
            /* @var $item \Magento\Quote\Model\Quote\Item */

            $dataKey = $this->splitKey($gift_keys);

            if ($item->getParentItem()) {
                $item = $item->getParentItem();
            }
            if ($this->_isParentGift($item)) {
                if ($item->getProductId() == $dataKey['product_parent_id']) {
                    $info = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                    $freegift_parent_key = $info['freegift_keys'];
                    $keyGift = $gift_keys;
                    $keyGift = [
                        $keyGift => $keyGift
                    ];
                    $result = array_intersect($freegift_parent_key, $keyGift);
                    if (empty($result)) {
                        continue;
                    } else {
                        $resultParent[] = $item->getId();
                    }
                }
            }
        }

        //merge array
        $keyRemove = [];
        foreach ($resultParent as $keyA => $parentA) {
            foreach ($resultParent as $keyB => $parentB) {
                if ($keyA != $keyB && !in_array($keyA, $keyRemove)) {
                    if ($parentA == $parentB) {
                        $keyRemove[] = $keyB;
                        unset($resultParent[$keyB]);
                    }
                }
            }
        }
        if (!empty($resultParent)) {
            return $resultParent;
        }
        return false;
    }

    public function _isParentGift($item)
    {
        if ($item->getOptionByCode('mw_free_catalog_gift') && $item->getOptionByCode('mw_free_catalog_gift')->getValue() == 1) {
            return true;
        }

        return false;
    }
}
