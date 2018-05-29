<?php
namespace MW\FreeGift\Controller\Index;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Addtocart extends \Magento\Checkout\Controller\Cart
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->productRepository = $productRepository;
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart);
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product|false
     */
    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $storeId = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }


    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        if(!$this->_formKeyValidator->validate($this->getRequest())){
            $this->resultRedirectFactory->create()->setPath("*/*/");
        };

        $params = $this->getRequest()->getParams();
        try{
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();

            if(!$product){
                $result->setData(["status" => "error"]);
                return $result;
            }
//            \Zend_Debug::dump($params); die("FFFF");
            $this->cart->addProduct($product, $params);
            $this->cart->save();
            $result->setData(["status" => "success"]);
            return $result;
        }catch (\Exception $e) {
            $result->setData(["error" => "Exception"]);
            return $result;
        }
    }
}