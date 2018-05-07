<?php
// @codingStandardsIgnoreFile

namespace MW\FreeGift\Model;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * SalesRule Validator Model
 *
 * Allows dispatching before and after events for each controller action
 *
 * @method mixed getCouponCode()
 * @method Validator setCouponCode($code)
 * @method mixed getWebsiteId()
 * @method Validator setWebsiteId($id)
 * @method mixed getCustomerGroupId()
 * @method Validator setCustomerGroupId($id)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Validator extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Rule source collection
     *
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    protected $_rules;

    /**
     * Defines if method \Magento\SalesRule\Model\Validator::reset() wasn't called
     * Used for clearing applied rule ids in Quote and in Address
     *
     * @var bool
     */
    protected $_isFirstTimeResetRun = true;

    /**
     * Information about item totals for rules
     *
     * @var array
     */
    protected $_rulesItemTotals = [];

    /**
     * Skip action rules validation flag
     *
     * @var bool
     */
    protected $_skipActionsValidation = false;

    /**
     * Catalog data
     *
     * @var \Magento\Catalog\Helper\Data|null
     */
    protected $_catalogData = null;

    /**
     * @var \MW\FreeGift\Model\ResourceModel\Salesrule\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\SalesRule\Model\Utility
     */
    protected $validatorUtility;

    /**
     * @var \Magento\SalesRule\Model\RulesApplier
     */
    protected $rulesApplier;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\SalesRule\Model\Validator\Pool
     */
    protected $validators;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Counter is used for assigning temporary id to quote address
     *
     * @var int
     */
    protected $counter = 0;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param Utility $utility
     * @param RulesApplier $rulesApplier
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param Validator\Pool $validators
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \MW\FreeGift\Model\ResourceModel\SalesRule\CollectionFactory $collectionFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \MW\FreeGift\Model\Utility $utility,
        \MW\FreeGift\Model\RulesApplier $rulesApplier,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\SalesRule\Model\Validator\Pool $validators,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_catalogData = $catalogData;
        $this->validatorUtility = $utility;
        $this->rulesApplier = $rulesApplier;
        $this->priceCurrency = $priceCurrency;
        $this->validators = $validators;
        $this->messageManager = $messageManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Init validator
     * Init process load collection of rules for specific website,
     * customer group and coupon code
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @return $this
     */
    public function init($websiteId, $customerGroupId, $freegiftCode)
    {
        $this->setWebsiteId($websiteId)->setCustomerGroupId($customerGroupId)->setFreegiftCouponCode($freegiftCode);

        $key = 'freegift_'.$websiteId . '_' . $customerGroupId . '_' . $freegiftCode;
        if (!isset($this->_rules[$key])) {
            $collection = $this->_collectionFactory->create()
                ->setValidationFilter(
                    $websiteId,
                    $customerGroupId,
                    $freegiftCode
                );
//            $collection->getSelect()->where('(discount_qty > main_table.times_used) OR (discount_qty = 0)');
            $collection->addFieldToFilter('is_active', 1)
                ->load();

            $this->_rules[$key] = $collection;
        }
        $this->_freegift_ids = array();

        return $this;
    }

    /**
     * Get rules collection for current object state
     *
     * @param Address|null $address
     * @return \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    protected function _getRules(Address $address = null)
    {
        $key = 'freegift_'.$this->getWebsiteId() . '_' . $this->getCustomerGroupId() . '_' . $this->getFreegiftCouponCode();
        return $this->_rules[$key];
    }

    /**
     * @param Address $address
     * @return string
     */
    protected function getAddressId(Address $address)
    {
        if ($address == null) {
            return '';
        }
        if (!$address->hasData('address_sales_rule_id')) {
            if ($address->hasData('address_id')) {
                $address->setData('address_sales_rule_id', $address->getData('address_id'));
            } else {
                $type = $address->getAddressType();
                $tempId = $type . $this->counter++;
                $address->setData('address_sales_rule_id', $tempId);
            }
        }
        return $address->getData('address_sales_rule_id');
    }

    /**
     * Set skip actions validation flag
     *
     * @param bool $flag
     * @return $this
     */
    public function setSkipActionsValidation($flag)
    {
        $this->_skipActionsValidation = $flag;
        return $this;
    }

    /**
     * Can apply rules check
     *
     * @param AbstractItem $item
     * @return bool
     */
    public function canApplyRules(AbstractItem $item)
    {
        $address = $item->getAddress();
        foreach ($this->_getRules($address) as $rule) {
            if (!$this->validatorUtility->canProcessRule($rule, $address) || !$rule->getActions()->validate($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reset quote and address applied rules
     *
     * @param Address $address
     * @return $this
     */
    public function reset(Address $address)
    {
        $this->validatorUtility->resetRoundingDeltas();
        if ($this->_isFirstTimeResetRun) {
            $address->setFreegiftAppliedRuleIds('');
            $address->getQuote()->setFreegiftAppliedRuleIds('');
            $this->_isFirstTimeResetRun = false;
        }

        return $this;
    }

    /**
     * Quote item discount calculation process
     *
     * @param AbstractItem $item
     * @return $this
     */
    public function process(AbstractItem $item)
    {
//        $item->setDiscountAmount(0);
//        $item->setBaseDiscountAmount(0);
//        $item->setDiscountPercent(0);
        $item->setFreegiftAppliedRuleIds('');

        $itemPrice = $this->getItemPrice($item);
        if ($itemPrice < 0) {
            return $this;
        }

        // set freegift_code, freegift_ids to quote table
        $appliedRuleIds = $this->rulesApplier->applyRules(
            $item,
            $this->_getRules($item->getAddress()),
            $this->_skipActionsValidation,
            $this->getFreegiftCouponCode()
        );

        //set applied_rule_ids to quote table
        $this->rulesApplier->setAppliedRuleIds($item, $appliedRuleIds);

        return $this;
    }

    /**
     * Calculate quote totals for each rule and save results
     *
     * @param mixed $items
     * @param Address $address
     * @return $this
     */
    public function initTotals($items, Address $address)
    {
        $address->setCartFixedRules([]);

        if (!$items) {
            return $this;
        }

        /** @var \Magento\SalesRule\Model\Rule $rule */
        foreach ($this->_getRules($address) as $rule) {
            if (\Magento\SalesRule\Model\Rule::CART_FIXED_ACTION == $rule->getSimpleAction()
                && $this->validatorUtility->canProcessRule($rule, $address)
            ) {
                $ruleTotalItemsPrice = 0;
                $ruleTotalBaseItemsPrice = 0;
                $validItemsCount = 0;

                foreach ($items as $item) {
                    //Skipping child items to avoid double calculations
                    if ($item->getParentItemId()) {
                        continue;
                    }
                    if (!$rule->getActions()->validate($item)) {
                        continue;
                    }
                    if (!$this->canApplyDiscount($item)) {
                        continue;
                    }
                    $qty = $this->validatorUtility->getItemQty($item, $rule);
                    $ruleTotalItemsPrice += $this->getItemPrice($item) * $qty;
                    $ruleTotalBaseItemsPrice += $this->getItemBasePrice($item) * $qty;
                    $validItemsCount++;
                }

                $this->_rulesItemTotals[$rule->getId()] = [
                    'items_price' => $ruleTotalItemsPrice,
                    'base_items_price' => $ruleTotalBaseItemsPrice,
                    'items_count' => $validItemsCount,
                ];
            }
        }

        return $this;
    }

    /**
     * Return item price
     *
     * @param AbstractItem $item
     * @return float
     */
    public function getItemPrice($item)
    {
        $price = $item->getDiscountCalculationPrice();
        $calcPrice = $item->getCalculationPrice();
        return $price === null ? $calcPrice : $price;
    }

    /**
     * Return item original price
     *
     * @param AbstractItem $item
     * @return float
     */
    public function getItemOriginalPrice($item)
    {
        return $this->_catalogData->getTaxPrice($item, $item->getOriginalPrice(), true);
    }

    /**
     * Return item base price
     *
     * @param AbstractItem $item
     * @return float
     */
    public function getItemBasePrice($item)
    {
        $price = $item->getDiscountCalculationPrice();
        return $price !== null ? $item->getBaseDiscountCalculationPrice() : $item->getBaseCalculationPrice();
    }

    /**
     * Return item base original price
     *
     * @param AbstractItem $item
     * @return float
     */
    public function getItemBaseOriginalPrice($item)
    {
        return $this->_catalogData->getTaxPrice($item, $item->getBaseOriginalPrice(), true);
    }

    /**
     * Convert address discount description array to string
     *
     * @param Address $address
     * @param string $separator
     * @return $this
     */
    public function prepareDescription($address, $separator = ', ')
    {
        $descriptionArray = $address->getDiscountDescriptionArray();
        if (!$descriptionArray && $address->getQuote()->getItemVirtualQty() > 0) {
            $descriptionArray = $address->getQuote()->getBillingAddress()->getDiscountDescriptionArray();
        }

        $description = $descriptionArray && is_array(
            $descriptionArray
        ) ? implode(
            $separator,
            array_unique($descriptionArray)
        ) : '';

        $address->setDiscountDescription($description);
        return $this;
    }

    /**
     * Return items list sorted by possibility to apply prioritized rules
     *
     * @param array $items
     * @param Address $address
     * @return array $items
     */
    public function sortItemsByPriority($items, Address $address = null)
    {
        $itemsSorted = [];
        /** @var $rule \Magento\SalesRule\Model\Rule */
        foreach ($this->_getRules($address) as $rule) {
            foreach ($items as $itemKey => $itemValue) {
                if ($rule->getActions()->validate($itemValue)) {
                    unset($items[$itemKey]);
                    array_push($itemsSorted, $itemValue);
                }
            }
        }

        if (!empty($itemsSorted)) {
            $items = array_merge($itemsSorted, $items);
        }

        return $items;
    }

    /**
     * @param int $key
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRuleItemTotalsInfo($key)
    {
        if (empty($this->_rulesItemTotals[$key])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Item totals are not set for the rule.'));
        }

        return $this->_rulesItemTotals[$key];
    }

    /**
     * @param int $key
     * @return $this
     */
    public function decrementRuleItemTotalsCount($key)
    {
        $this->_rulesItemTotals[$key]['items_count']--;

        return $this;
    }

    /**
     * Check if we can apply discount to current QuoteItem
     *
     * @param AbstractItem $item
     * @return bool
     */
    public function canApplyDiscount(AbstractItem $item)
    {
        $result = true;
        /** @var \Zend_Validate_Interface $validator */
        foreach ($this->validators->getValidators('mw_freegift') as $validator) {
            $result = $validator->isValid($item);
            if (!$result) {
                break;
            }
        }
        return $result;
    }
}
