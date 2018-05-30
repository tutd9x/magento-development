<?php
namespace MW\FreeGift\Model\SalesRule;

use MW\FreeGift\Model\ResourceModel\SalesRule\Collection;
use MW\FreeGift\Model\ResourceModel\SalesRule\CollectionFactory;
use MW\FreeGift\Model\SalesRule;
use Magento\Framework\App\Request\DataPersistorInterface;

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
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    public $_storeManager;

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
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->coreRegistry = $registry;
        $this->metadataValueProvider = $metadataValueProvider;
        $meta = array_replace_recursive($this->getMetadataValues(), $meta);
        $this->dataPersistor = $dataPersistor;
        $this->_storeManager=$storeManager;
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
        $baseurl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        /** @var SalesRule $rule */
        foreach ($items as $rule) {
            $rule->load($rule->getId());

            if ($rule['promotion_banner']) :
                $img = [];
                $img[0]['tmp_name'] = $rule['promotion_banner'];
                $img[0]['name'] = $rule['promotion_banner'];
                $img[0]['url'] = $baseurl . 'mw_freegift/salesrule/' . $rule['promotion_banner'];
                $rule['promotion_banner'] = $img;
            endif;

            $this->loadedData[$rule->getId()]['rule_information'] = $rule->getData();
        }

        return $this->loadedData;
    }
}
