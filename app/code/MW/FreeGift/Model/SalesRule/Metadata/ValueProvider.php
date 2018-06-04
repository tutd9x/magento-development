<?php
namespace MW\FreeGift\Model\SalesRule\Metadata;

use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\Rule;
use Magento\Store\Model\System\Store;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Convert\DataObject;

/**
 * Metadata provider for sales rule edit form.
 */
class ValueProvider
{
    /**
     * @var Store
     */
    protected $store;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var DataObject
     */
    protected $objectConverter;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $salesRuleFactory;

    /**
     * Initialize dependencies.
     *
     * @param Store $store
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DataObject $objectConverter
     * @param \MW\FreeGift\Model\SalesRuleFactory $salesRuleFactory
     */
    public function __construct(
        Store $store,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataObject $objectConverter,
        \MW\FreeGift\Model\SalesRuleFactory $salesRuleFactory
    ) {
        $this->store = $store;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->objectConverter = $objectConverter;
        $this->salesRuleFactory = $salesRuleFactory;
    }

    /**
     * Get metadata for sales rule form. It will be merged with form UI component declaration.
     *
     * @param Rule $rule
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getMetadataValues(\MW\FreeGift\Model\SalesRule $rule)
    {
        $customerGroups = $this->groupRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        $couponTypesOptions = [];
        $couponTypes = $this->salesRuleFactory->create()->getCouponTypes();
        foreach ($couponTypes as $key => $couponType) {
            $couponTypesOptions[] = [
                'label' => $couponType,
                'value' => $key,
            ];
        }

        $labels = $rule->getStoreLabels();

        return [
            'rule_information' => [
                'children' => [
                    'coupon_type' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'options' => $couponTypesOptions,
                                ],
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }
}
