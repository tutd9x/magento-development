<?php
namespace MW\FreeGift\Model;

/**
 * SalesRule Coupon Model
 *
 * @method \MW\FreeGift\Model\ResourceModel\Coupon _getResource()
 * @method \MW\FreeGift\Model\ResourceModel\Coupon getResource()
 */
class Coupon extends \Magento\Framework\Model\AbstractExtensibleModel implements \Magento\SalesRule\Api\Data\CouponInterface
{
    const KEY_COUPON_ID = 'coupon_id';
    const KEY_RULE_ID = 'rule_id';
    const KEY_CODE = 'code';
    const KEY_USAGE_LIMIT = 'usage_limit';
    const KEY_USAGE_PER_CUSTOMER = 'usage_per_customer';
    const KEY_TIMES_USED = 'times_used';
    const KEY_EXPIRATION_DATE = 'expiration_date';
    const KEY_IS_PRIMARY = 'is_primary';
    const KEY_CREATED_AT = 'created_at';
    const KEY_TYPE = 'type';

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MW\FreeGift\Model\ResourceModel\Coupon');
    }

    /**
     * Set rule instance
     *
     * @param \Magento\SalesRule\Model\Rule $rule
     * @return $this
     */
    public function setRule(\Magento\SalesRule\Model\Rule $rule)
    {
        $this->setRuleId($rule->getId());
        return $this;
    }

    /**
     * Load primary coupon for specified rule
     *
     * @param \Magento\SalesRule\Model\Rule|int $rule
     * @return $this
     */
    public function loadPrimaryByRule($rule)
    {
        $this->getResource()->loadPrimaryByRule($this, $rule);
        return $this;
    }

    /**
     * Load Cart Price Rule by coupon code
     *
     * @param string $couponCode
     * @return $this
     */
    public function loadByCode($couponCode)
    {
        $this->load($couponCode, 'code');
        return $this;
    }
}
