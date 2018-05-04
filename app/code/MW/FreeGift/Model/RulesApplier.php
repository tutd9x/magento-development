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
     * @var \MW\FreeGift\Model\Utility
     */
    protected $validatorUtility;
    protected $checkoutSession;

    /**
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $calculatorFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \MW\FreeGift\Model\Utility $utility
     */
    public function __construct(
        \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $calculatorFactory,
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
     * @param \MW\FreeGift\Model\ResourceModel\SalesRule\Collection $rules
     * @param bool $skipValidation
     * @param mixed $couponCode
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function applyRules($item, $rules, $skipValidation, $couponCode)
    {
        $address = $item->getAddress();
        $items = $address->getAllVisibleItems();
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


        /* @var $rule \MW\FreeGift\Model\SalesRule */
        foreach ($rules as $rule) {
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


        $myId = array();
        $quoteid = $this->checkoutSession->getQuote();
        $cartItems = $quoteid->getAllVisibleItems();
        foreach ($cartItems as $item)
        {
            $productId = $item->getProductId();
            array_push($myId,$productId);
        }
        $this->checkoutSession->setProductgiftid($myId);

        $quote = $item->getQuote();
        $quote->setFreegiftIds($freegiftIds);
        $quote->setFreegiftCouponCode($freegiftCouponCode);

        return $appliedRuleIds;
    }


    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param int[] $appliedRuleIds
     * @return $this
     */
    public function setAppliedRuleIds(\Magento\Quote\Model\Quote\Item\AbstractItem $item, array $appliedRuleIds)
    {
        $address = $item->getAddress();
        $quote = $item->getQuote();
        if (count($appliedRuleIds) > 0) {
            $quote->setFreegiftAppliedRuleIds($this->validatorUtility->mergeIds([], $appliedRuleIds));
            $item->setFreegiftAppliedRuleIds($this->validatorUtility->mergeIds([], $appliedRuleIds));
        } else {
            $quote->setFreegiftAppliedRuleIds('');
            $quote->setFreegiftIds('');
            $quote->setFreegiftCouponCode('');
            $item->setFreegiftAppliedRuleIds('');
            $this->checkoutSession->unsetData('gift_sales_product_ids');
            $this->checkoutSession->unsetData('productgiftid');
            $this->checkoutSession->unsetData('sales_gift_removed');
            $this->removeItemGift($item);
        }

        return $this;
    }

    function removeItemGift($item = null)
    {
        if ($item) {
            if ($item->getOptionByCode('free_sales_gift') && $item->getOptionByCode('free_sales_gift')->getValue() == 1) {
                $quote = $item->getQuote();
                $quote->removeItem($item->getItemId());
            }
        }
    }
}
