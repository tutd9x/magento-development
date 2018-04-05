<?php
namespace MW\FreeGift\Block\Hidden\Inject\Checkout\Cart;
class Init extends \Magento\Framework\View\Element\Template
{
    protected $checkoutSession;
//    protected $_coreRegistry;
//    protected $helperFreeGift;
//    protected $_resourceRule;
    protected $productRepository;
    protected $imageHelper;

//    /**
//     * @var string
//     */
//    protected $_template = 'MW_FreeGift::promotion_banner.phtml';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
//        \MW\FreeGift\Helper\Data $helperFreeGift,
//        \Magento\Framework\Registry $coreRegistry,
//        \MW\FreeGift\Model\ResourceModel\Rule $resourceRule,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Image $imageHelper,
//        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
//        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
//        \Magento\Customer\Helper\View $helperView,
        array $data = []
    ) {
//        $this->currentCustomer = $currentCustomer;
//        $this->_subscriberFactory = $subscriberFactory;
//        $this->_helperView = $helperView;
        $this->checkoutSession = $checkoutSession;
//        $this->helperFreeGift = $helperFreeGift;
//        $this->_coreRegistry = $coreRegistry;
//        $this->_resourceRule = $resourceRule;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;


        parent::__construct($context, $data);
    }

    public function init(){

        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
        if(count($items) > 0){
            $init = array();
            foreach($items as $item){
                $product = $this->productRepository->getById($item->getProduct()->getId());
                $product_name = str_replace("'", "", $product->getName());
                $init[$item->getItemId()] = array(
                    'product_id'                =>  $product->getId(),
                    'product_name'              =>  $product_name,
                    'product_image'             =>  (string)$this->imageHelper->init($product, 'category_page_list')->constrainOnly(TRUE)->keepAspectRatio(TRUE)->keepFrame(TRUE)->resize(265,265)->getUrl(),
                    'product_has_options'       =>  ($product->getOptions() ? "1" : "0"),
                    'product_type'              =>  $product->getTypeId(),
                );
            }

            return json_encode($init);
        }

        return "";
    }

    function xlog($message = 'null'){
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($message)
        ;
    }
}