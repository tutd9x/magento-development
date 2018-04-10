<?php
namespace MW\FreeGift\Model\SalesRule;

use MW\FreeGift\Model\ResourceModel\SalesRule\Collection;
use MW\FreeGift\Model\ResourceModel\SalesRule\CollectionFactory;
use MW\FreeGift\Model\SalesRule;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\SalesRule\Model\Rule\Metadata\ValueProvider
     */
    protected $metadataValueProvider;

    /**
     * Initialize dependencies.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $registry
     * @param \MW\FreeGift\Model\SalesRule\Metadata\ValueProvider $metadataValueProvider
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $registry,
        \MW\FreeGift\Model\SalesRule\Metadata\ValueProvider $metadataValueProvider,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->coreRegistry = $registry;
        $this->metadataValueProvider = $metadataValueProvider;
        $meta = array_replace_recursive($this->getMetadataValues(), $meta);
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get metadata values
     *
     * @return array
     */
    protected function getMetadataValues()
    {
        $rule = $this->coreRegistry->registry('current_promo_sales_rule');
        return $this->metadataValueProvider->getMetadataValues($rule);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var Rule $rule */
        foreach ($items as $rule) {
            $rule->load($rule->getId());
            $rule->setDiscountAmount($rule->getDiscountAmount() * 1);
            $rule->setDiscountQty($rule->getDiscountQty() * 1);

            $this->loadedData[$rule->getId()] = $rule->getData();
        }

        return $this->loadedData;
    }
}
