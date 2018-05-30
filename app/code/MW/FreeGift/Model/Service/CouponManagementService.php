<?php
namespace MW\FreeGift\Model\Service;

/**
 * Coupon management service class
 *
 */
class CouponManagementService implements \MW\FreeGift\Api\CouponManagementInterface
{
    /**
     * @var \MW\FreeGift\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @var \MW\FreeGift\Model\SalesRuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \MW\FreeGift\Model\ResourceModel\Coupon\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \MW\FreeGift\Model\Coupon\Massgenerator
     */
    protected $couponGenerator;

    /**
     * @var \MW\FreeGift\Model\Spi\CouponResourceInterface
     */
    protected $resourceModel;

    /**
     * var \MW\FreeGift\Api\Data\CouponMassDeleteResultInterfaceFactory
     */
    protected $couponMassDeleteResultFactory;

    /**
     * @param \MW\FreeGift\Model\CouponFactory $couponFactory
     * @param \MW\FreeGift\Model\SalesRuleFactory $ruleFactory
     * @param \MW\FreeGift\Model\ResourceModel\Coupon\CollectionFactory $collectionFactory
     * @param \MW\FreeGift\Model\Coupon\Massgenerator $couponGenerator
     * @param \MW\FreeGift\Model\Spi\CouponResourceInterface $resourceModel
     * @param \MW\FreeGift\Api\Data\CouponMassDeleteResultInterfaceFactory $couponMassDeleteResultFactory
     */
    public function __construct(
        \MW\FreeGift\Model\CouponFactory $couponFactory,
        \MW\FreeGift\Model\SalesRuleFactory $ruleFactory,
        \MW\FreeGift\Model\ResourceModel\Coupon\CollectionFactory $collectionFactory,
        \MW\FreeGift\Model\Coupon\Massgenerator $couponGenerator,
        \MW\FreeGift\Model\Spi\CouponResourceInterface $resourceModel,
        \MW\FreeGift\Api\Data\CouponMassDeleteResultInterfaceFactory $couponMassDeleteResultFactory
    ) {
        $this->couponFactory = $couponFactory;
        $this->ruleFactory = $ruleFactory;
        $this->collectionFactory = $collectionFactory;
        $this->couponGenerator = $couponGenerator;
        $this->resourceModel = $resourceModel;
        $this->couponMassDeleteResultFactory = $couponMassDeleteResultFactory;
    }

    /**
     * Generate coupon for a rule
     *
     * @param \MW\FreeGift\Api\Data\CouponGenerationSpecInterface $couponSpec
     * @return string[]
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate(\MW\FreeGift\Api\Data\CouponGenerationSpecInterface $couponSpec)
    {
        $data = $this->convertCouponSpec($couponSpec);
        if (!$this->couponGenerator->validateData($data)) {
            throw new \Magento\Framework\Exception\InputException();
        }

        try {
            $rule = $this->ruleFactory->create()->load($couponSpec->getRuleId());
            if (!$rule->getRuleId()) {
                throw \Magento\Framework\Exception\NoSuchEntityException::singleField(
                    \MW\FreeGift\Model\Coupon::KEY_RULE_ID,
                    $couponSpec->getRuleId()
                );
            }
            if (!($rule->getUseAutoGeneration() || $rule->getCouponType() == '3')) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Specified rule does not allow automatic coupon generation')
                );
            }

            $this->couponGenerator->setData($data);
            $this->couponGenerator->setData('to_date', $rule->getToDate());
            $this->couponGenerator->setData('uses_per_coupon', $rule->getUsesPerCoupon());
            $this->couponGenerator->setData('usage_per_customer', $rule->getUsesPerCustomer());

            $this->couponGenerator->generatePool();
            return $this->couponGenerator->getGeneratedCodes();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error occurred when generating coupons: %1', $e->getMessage())
            );
        }
    }

    /**
     * Convert CouponGenerationSpecInterface to data array expected by Massgenerator
     *
     * @param \MW\FreeGift\Api\Data\CouponGenerationSpecInterface $couponSpec
     * @return array
     */
    protected function convertCouponSpec(\MW\FreeGift\Api\Data\CouponGenerationSpecInterface $couponSpec)
    {
        $data = [];
        $data['rule_id'] = $couponSpec->getRuleId();
        $data['qty'] = $couponSpec->getQuantity();
        $data['format'] = $couponSpec->getFormat();
        $data['length'] = $couponSpec->getLength();
        $data['prefix'] = $couponSpec->getPrefix();
        $data['suffix'] = $couponSpec->getSuffix();
        $data['dash'] = $couponSpec->getDelimiterAtEvery();

        //ensure we have a format
        if (empty($data['format'])) {
            $data['format'] = $couponSpec::COUPON_FORMAT_ALPHANUMERIC;
        }

        //if specified, use the supplied delimiter
        if ($couponSpec->getDelimiter()) {
            $data['delimiter'] = $couponSpec->getDelimiter();
        }
        return $data;
    }

    /**
     * Delete coupon by coupon ids.
     *
     * @param int[] $ids
     * @param bool $ignoreInvalidCoupons
     * @return \MW\FreeGift\Api\Data\CouponMassDeleteResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByIds(array $ids, $ignoreInvalidCoupons = true)
    {
        return $this->massDelete('coupon_id', $ids, $ignoreInvalidCoupons);
    }

    /**
     * Delete coupon by coupon codes.
     *
     * @param string[] codes
     * @param bool $ignoreInvalidCoupons
     * @return \MW\FreeGift\Api\Data\CouponMassDeleteResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByCodes(array $codes, $ignoreInvalidCoupons = true)
    {
        return $this->massDelete('code', $codes, $ignoreInvalidCoupons);
    }

    /**
     * Delete coupons by filter
     *
     * @param string $fieldName
     * @param string[] fieldValues
     * @param bool $ignoreInvalid
     * @return \MW\FreeGift\Api\Data\CouponMassDeleteResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function massDelete($fieldName, array $fieldValues, $ignoreInvalid)
    {
        $couponsCollection = $this->collectionFactory->create()
            ->addFieldToFilter(
                $fieldName,
                ['in' => $fieldValues]
            );

        if (!$ignoreInvalid) {
            if ($couponsCollection->getSize() != count($fieldValues)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Some coupons are invalid.'));
            }
        }

        $results = $this->couponMassDeleteResultFactory->create();
        $failedItems = [];
        $fieldValues = array_flip($fieldValues);
        /** @var \MW\FreeGift\Model\Coupon $coupon */
        foreach ($couponsCollection->getItems() as $coupon) {
            $couponValue = ($fieldName == 'code') ? $coupon->getCode() : $coupon->getCouponId();
            try {
                $coupon->delete();
            } catch (\Exception $e) {
                $failedItems[] = $couponValue;
            }
            unset($fieldValues[$couponValue]);
        }
        $results->setFailedItems($failedItems);
        $results->setMissingItems(array_flip($fieldValues));
        return $results;
    }
}
