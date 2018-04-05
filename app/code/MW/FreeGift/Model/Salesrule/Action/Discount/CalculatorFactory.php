<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Model\Salesrule\Action\Discount;

class CalculatorFactory
{
    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @var array
     */
    protected $classByType = [
        \MW\FreeGift\Model\Salesrule::TO_PERCENT_ACTION => 'MW\FreeGift\Model\Salesrule\Action\Discount\ToPercent',
        \MW\FreeGift\Model\Salesrule::BY_PERCENT_ACTION => 'MW\FreeGift\Model\Salesrule\Action\Discount\ByPercent',
        \MW\FreeGift\Model\Salesrule::TO_FIXED_ACTION => 'MW\FreeGift\Model\Salesrule\Action\Discount\ToFixed',
        \MW\FreeGift\Model\Salesrule::BY_FIXED_ACTION => 'MW\FreeGift\Model\Salesrule\Action\Discount\ByFixed',
        \MW\FreeGift\Model\Salesrule::CART_FIXED_ACTION => 'MW\FreeGift\Model\Salesrule\Action\Discount\CartFixed',
        \MW\FreeGift\Model\Salesrule::BUY_X_GET_Y_ACTION => 'MW\FreeGift\Model\Salesrule\Action\Discount\BuyXGetY',
    ];

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $discountRules
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, array $discountRules = [])
    {
        $this->classByType = array_merge($this->classByType, $discountRules);
        $this->_objectManager = $objectManager;
    }

    /**
     * @param string $type
     * @return \MW\FreeGift\Model\Salesrule\Action\Discount\DiscountInterface
     * @throws \InvalidArgumentException
     */
    public function create($type)
    {
        if (!isset($this->classByType[$type])) {
            throw new \InvalidArgumentException($type . ' is unknown type');
        }

        return $this->_objectManager->create($this->classByType[$type]);
    }
}
