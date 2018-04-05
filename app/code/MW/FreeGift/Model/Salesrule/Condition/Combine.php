<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Rule Combine Condition data model
 */
namespace MW\FreeGift\Model\Salesrule\Condition;

class Combine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager = null;

    /**
     * @var \Magento\SalesRule\Model\Rule\Condition\Address
     */
    protected $_conditionAddress;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CatalogRule\Model\Rule\Condition\ProductFactory $conditionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\SalesRule\Model\Rule\Condition\Address $conditionAddress,
        \MW\FreeGift\Model\Rule\Condition\ProductFactory $conditionFactory,
        array $data = []
    ) {
        $this->_eventManager = $eventManager;
        $this->_conditionAddress = $conditionAddress;
        $this->_productFactory = $conditionFactory;
        parent::__construct($context, $data);
        $this->setType('MW\FreeGift\Model\Salesrule\Condition\Combine');
    }

    /**
     * Get new child select options
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
//        $productAttributes = $this->_productFactory->create()->loadAttributeOptions()->getAttributeOption();
//        $attributes = [];
//        foreach ($productAttributes as $code => $label) {
//            $attributes[] = [
//                'value' => 'MW\FreeGift\Model\Salesrule\Condition\Product|' . $code,
//                'label' => $label,
//            ];
//        }

        $addressAttributes = $this->_conditionAddress->loadAttributeOptions()->getAttributeOption();
        $attributes = [];
        foreach ($addressAttributes as $code => $label) {
            $attributes[] = [
                'value' => 'Magento\SalesRule\Model\Rule\Condition\Address|' . $code,
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions,
            [
                [
                    'value' => 'Magento\SalesRule\Model\Rule\Condition\Product\Found',
                    'label' => __('Product attribute combination'),
                ],
                [
                    'value' => 'Magento\SalesRule\Model\Rule\Condition\Product\Subselect',
                    'label' => __('Products subselection')
                ],
                [
                    'value' => 'Magento\SalesRule\Model\Rule\Condition\Combine',
                    'label' => __('Conditions combination')
                ],
                ['label' => __('Cart Attribute'), 'value' => $attributes]
            ]
        );

        $additional = new \Magento\Framework\DataObject();
        $this->_eventManager->dispatch('salesrule_rule_condition_combine', ['additional' => $additional]);
        $additionalConditions = $additional->getConditions();
        if ($additionalConditions) {
            $conditions = array_merge_recursive($conditions, $additionalConditions);
        }

        return $conditions;
    }

    /**
     * @param array $productCollection
     * @return $this
     */
    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            /** @var Product|Combine $condition */
            $condition->collectValidatedAttributes($productCollection);
        }
        return $this;
    }
}
