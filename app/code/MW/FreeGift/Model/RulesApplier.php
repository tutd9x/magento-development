<?php
namespace MW\FreeGift\Model;

use Magento\Quote\Model\Quote\Address;

/**
 * Class RulesApplier
 * @package MW\FreeGift\Model\Validator
 */
class RulesApplier
{
    /**
     * Application Event Dispatcher
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\SalesRule\Model\Utility
     */
    protected $validatorUtility;
    protected $checkoutSession;

    /**
     * @param \MW\FreeGift\Model\Salesrule\Action\Discount\CalculatorFactory $calculatorFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \MW\FreeGift\Model\Utility $utility
     */
    public function __construct(
        \MW\FreeGift\Model\Salesrule\Action\Discount\CalculatorFactory $calculatorFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \MW\FreeGift\Model\Utility $utility,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->calculatorFactory = $calculatorFactory;
        $this->validatorUtility = $utility;
        $this->_eventManager = $eventManager;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Apply rules to current order item
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $rules
     * @param bool $skipValidation
     * @param mixed $couponCode
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function applyRules($item, $rules, $skipValidation, $couponCode)
    {
//        $this->xlog($this->debug_backtrace_string());
        $address = $item->getAddress();
        $items = $address->getAllVisibleItems();
//        $freegiftIds    = array(); $appliedRuleIds = array();
        //fix item freegift weight to 0
        $weight = 0;
        $qty = 0;
        foreach($items as $it){
            $params = unserialize($it->getOptionByCode('info_buyRequest')->getValue());
            if(!isset($params['freegift']) && !isset($params['free_catalog_gift'])) {
                $weight = $weight + ($it->getWeight() * $it->getQty());
                $qty = $qty + $it->getQty();
            }
        }
        $temp_rule = array();
        $this->checkoutSession->setRulegifts($temp_rule);

        $appliedRuleIds = [];
        $freegiftIds    = [];
        $freegiftCouponCode    = [];


        /* @var $rule \Magento\SalesRule\Model\Rule */
        foreach ($rules as $rule)
        {
            $address->setWeight($weight);
            $address->setTotalQty($qty);
            if (!$this->validatorUtility->canProcessRule($rule, $address)) {
                continue;
            }
            if (!$skipValidation && !$rule->getActions()->validate($item)) {
                $childItems = $item->getChildren();
                $isContinue = true;
                if (!empty($childItems)) {
                    foreach ($childItems as $childItem) {
                        if ($rule->getActions()->validate($childItem)) {
                            $isContinue = false;
                        }
                    }
                }
                if ($isContinue) {
                    continue;
                }
            }
            //$this->applyRule($item, $rule, $address, $couponCode);

            $appliedRuleIds[$rule->getRuleId()] = $rule->getRuleId();
            $freegiftIds = $this->validatorUtility->mergeIds($freegiftIds, $rule->getData('gift_product_ids'));

            if($rule->getData('use_auto_generation') == 1){
                $freegiftCode = $rule->getData('code');
            }else{
                $freegiftCode = $rule->getData('coupon_code');
            }
            $freegiftCouponCode = $this->validatorUtility->mergeIds($freegiftCouponCode, $freegiftCode);
            if ($rule->getStopRulesProcessing()) {
                break;
            }
        }

        $quote   = $item->getQuote();
        $myId = array();
        $quoteid = $this->checkoutSession->getQuote();
        $cartItems = $quoteid->getAllVisibleItems();
        foreach ($cartItems as $item)
        {
            $productId = $item->getProductId();
            array_push($myId,$productId);
        }
        $this->checkoutSession->setProductgiftid($myId);
        $quote->setFreegiftIds($freegiftIds);
        $quote->setFreegiftCouponCode($freegiftCouponCode);

        return $appliedRuleIds;
    }

    /**
     * Add rule discount description label to address object
     *
     * @param Address $address
     * @param \Magento\SalesRule\Model\Rule $rule
     * @return $this
     */
    public function addDiscountDescription($address, $rule)
    {
        $description = $address->getDiscountDescriptionArray();
        $ruleLabel = $rule->getStoreLabel($address->getQuote()->getStore());
        $label = '';
        if ($ruleLabel) {
            $label = $ruleLabel;
        } else {
            if (strlen($address->getCouponCode())) {
                $label = $address->getCouponCode();
            }
        }

        if (strlen($label)) {
            $description[$rule->getId()] = $label;
        }

        $address->setDiscountDescriptionArray($description);

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param mixed $couponCode
     * @return $this
     */
    protected function applyRule($item, $rule, $address, $couponCode)
    {
//        $discountData = $this->getDiscountData($item, $rule);
//        $this->setDiscountData($discountData, $item);

        $this->maintainAddressCouponCode($address, $rule, $couponCode);
        $this->addDiscountDescription($address, $rule);

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\Rule $rule
     * @return \Magento\SalesRule\Model\Rule\Action\Discount\Data
     */
    protected function getDiscountData($item, $rule)
    {
        $qty = $this->validatorUtility->getItemQty($item, $rule);

        $discountCalculator = $this->calculatorFactory->create($rule->getSimpleAction());
        $qty = $discountCalculator->fixQuantity($qty, $rule);
        $discountData = $discountCalculator->calculate($rule, $item, $qty);

        //$this->eventFix($discountData, $item, $rule, $qty);
        //$this->validatorUtility->deltaRoundingFix($discountData, $item);

        /**
         * We can't use row total here because row total not include tax
         * Discount can be applied on price included tax
         */

        //$this->validatorUtility->minFix($discountData, $item, $qty);

        return $discountData;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return $this
     */
    protected function setDiscountData($discountData, $item)
    {
        $item->setDiscountAmount($discountData->getAmount());
        $item->setBaseDiscountAmount($discountData->getBaseAmount());
        $item->setOriginalDiscountAmount($discountData->getOriginalAmount());
        $item->setBaseOriginalDiscountAmount($discountData->getBaseOriginalAmount());

        return $this;
    }

    /**
     * Set coupon code to address if $rule contains validated coupon
     *
     * @param Address $address
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param mixed $couponCode
     * @return $this
     */
    public function maintainAddressCouponCode($address, $rule, $couponCode)
    {
        /*
        Rule is a part of rules collection, which includes only rules with 'No Coupon' type or with validated coupon.
        As a result, if rule uses coupon code(s) ('Specific' or 'Auto' Coupon Type), it always contains validated coupon
        */

        if ($rule->getCouponType() != \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON) {
//            $address->setCouponCode($couponCode);
            $address->setFreegiftCouponCode($couponCode);
        }

        return $this;
    }


    /**
     * Fire event to allow overwriting of discount amounts
     *
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param float $qty
     * @return $this
     */
//    protected function eventFix(
//        \MW\FreeGift\Model\Salesrule\Action\Discount\Data $discountData,
//        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
//        \MW\FreeGift\Model\Salesrule $rule,
//        $qty
//    ) {
//        $quote = $item->getQuote();
//        $address = $item->getAddress();
//
//        $this->_eventManager->dispatch(
//            'salesrule_validator_process',
//            [
//                'rule' => $rule,
//                'item' => $item,
//                'address' => $address,
//                'quote' => $quote,
//                'qty' => $qty,
//                'result' => $discountData
//            ]
//        );
//
//        return $this;
//    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param int[] $appliedRuleIds
     * @return $this
     */
    public function setAppliedRuleIds(\Magento\Quote\Model\Quote\Item\AbstractItem $item, array $appliedRuleIds)
    {
        //$this->xlog($this->debug_backtrace_string());

        $address = $item->getAddress();
        $quote = $item->getQuote();
        if(count($appliedRuleIds) > 0){
            $this->xlog("OK");
            $quote->setFreegiftAppliedRuleIds($this->validatorUtility->mergeIds([], $appliedRuleIds));
        }else{
            $this->xlog("Cancel");
            $quote->setFreegiftAppliedRuleIds('');
            $quote->setFreegiftIds('');
            $quote->setFreegiftCouponCode('');
            $this->checkoutSession->unsetData('gift_sales_product_ids');
            $this->removeItemGift($item);
        }
        $quote->save();
        return $this;
    }

    function removeItemGift($item = null){
        if($item){
            $itemOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
            if(isset($itemOptions['info_buyRequest']['applied_rule'])){
                //$this->xlog(__LINE__.' '.__FILE__);
                $quote = $item->getQuote();
                $quote->removeItem($item->getItemId());
            }
        }
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

    function debug_backtrace_string() {
        $stack = '';
        $i = 1;
        $trace = debug_backtrace();
        unset($trace[0]); //Remove call to this function from stack trace
        foreach($trace as $node) {
//            $this->xlog(array_keys($node));
            $stack .= "#$i ";
//            $stack .= $node['file'];
//            $stack .= "(" .$node['line']."): ";
            if(isset($node['class'])) {
                $stack .= $node['class'] . "->";
            }
            $stack .= $node['function'] . "()" . PHP_EOL;
            $i++;
        }
        return $stack;
    }
}
