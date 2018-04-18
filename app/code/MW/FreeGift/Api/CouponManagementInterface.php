<?php
namespace MW\FreeGift\Api;

/**
 * Coupon management interface
 *
 * @api
 */
interface CouponManagementInterface
{
    /**
     * Generate coupon for a rule
     *
     * @param \MW\FreeGift\Api\Data\CouponGenerationSpecInterface $couponSpec
     * @return string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate(\MW\FreeGift\Api\Data\CouponGenerationSpecInterface $couponSpec);

    /**
     * Delete coupon by coupon ids.
     *
     * @param int[] $ids
     * @param bool $ignoreInvalidCoupons
     * @return \MW\FreeGift\Api\Data\CouponMassDeleteResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByIds(array $ids, $ignoreInvalidCoupons = true);

    /**
     * Delete coupon by coupon codes.
     *
     * @param string[] $codes
     * @param bool $ignoreInvalidCoupons
     * @return \MW\FreeGift\Api\Data\CouponMassDeleteResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByCodes(array $codes, $ignoreInvalidCoupons = true);
}
