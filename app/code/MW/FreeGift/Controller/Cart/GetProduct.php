<?php
/**
 * Created by PhpStorm.
 * User: lap15
 * Date: 10/8/2015
 * Time: 5:21 PM
 */

namespace MW\FreeGift\Controller\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
class GetProduct extends \Magento\Checkout\Controller\Cart
{
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * Coupon factory
     *
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager = null;
    protected $_layout;
    protected $helperFreeGift;
    protected $checkoutSession;
    protected $layoutFactory;
    protected $_coreRegistry;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \MW\FreeGift\Model\CouponFactory $couponFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
//        \Magento\Framework\Event\ManagerInterface $eventManager
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\View\LayoutInterface $layout,
        \MW\FreeGift\Helper\Data $helperFreeGift,
        \Magento\Checkout\Model\Session $checkoutSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->couponFactory = $couponFactory;
        $this->quoteRepository = $quoteRepository;
//        $this->_eventManager = $eventManager;
        $this->_layout = $layout;
        $this->helperFreeGift = $helperFreeGift;
        $this->checkoutSession = $checkoutSession;
        $this->layoutFactory = $layoutFactory;
        $this->productRepository = $productRepository;
        $this->_coreRegistry = $registry;
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product || false
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


    public function execute()
    {

        //$block = Mage::getSingleton('core/layout');

        //$layout = $this->_layout;
        $cart = $this->checkoutSession;
        $layout = $this->layoutFactory->create();
        $update = $layout->getUpdate();
        $update->load('catalog_product_view');
        $layout->generateXml();
        $layout->generateElements();


        $params = $this->getRequest()->getParams();


        if(!isset($params['action'])) $params['action'] = 'view';

        switch($params['action']){
            case 'view':
                $product = $this->_initProduct();
                if (!$product) {
                    return;
                }
                $textBtn = __("Add to Cart");
                $session_id = false;
                $qty = 1;
                break;
            case 'configure':

                /* Edit product in cart based id of item */
                $id = (int) $params['item_id'];
                $quoteItem = null;
                $cart = $this->cart; //Mage::getSingleton('checkout/cart');

                if ($id) {
                    $quoteItem = $cart->getQuote()->getItemById($id);
                }

                if (!$quoteItem) {
                    return;
                }

                $qty = $quoteItem->getQty();
                $product = $this->_initProduct($quoteItem->getProduct()->getId());

                if (!$product) {
                    return;
                }

                try {
                    $_params = new \Magento\Framework\DataObject();
                    $_params->setCategoryId(false);
                    $_params->setConfigureMode(true);
                    $_params->setBuyRequest($quoteItem->getBuyRequest());

                    $product = $this->helperFreeGift->prepareAndRender($quoteItem->getProduct()->getId(), $_params);
                } catch (\Exception $e) {
                    $this->messageManager->addError(
                        $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml(__('Cannot configure product.'))
                    );
                    $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                    return;
                }
                $textBtn = __("Update Cart");
                $session_id = true;
                break;
        }


        //$options_js = $layout->createBlock('Magento\Catalog\Block\Product\View')->setData('area','frontend')->setProductId($product->getId())->getJsonConfig();

//        $options        = $layout->createBlock('Magento\Catalog\Block\Product\View\Options')
//            ->setData('area','frontend')
//            ->setProduct($product)
//            ->setTemplate('catalog/product/view/options.phtml')
//            ->addOptionRenderer('text', 'catalog/product_view_options_type_text', 'catalog/product/view/options/type/text.phtml')
//            ->addOptionRenderer('file', 'catalog/product_view_options_type_file', 'catalog/product/view/options/type/file.phtml')
//            ->addOptionRenderer('select', 'catalog/product_view_options_type_select', 'catalog/product/view/options/type/select.phtml')
//            ->addOptionRenderer('date', 'catalog/product_view_options_type_date', 'catalog/product/view/options/type/date.phtml')
//        ;

//        $js             = $layout->createBlock('core/template', 'product_js')->setData('area','frontend')->setTemplate('catalog/product/view/options/js.phtml');
//        $product_price  = $layout->createBlock('catalog/product_price')->setData('area','frontend')->setProduct($product)->setTemplate('catalog/product/view/price_clone.phtml');

        $html  = "";
//        $html .= '
//            <script type="text/javascript">
//                require([
//                    "Magento_ConfigurableProduct/js/configurable"
//                ], function($){
//
//                });
                //var optionsPrice = new Product.OptionsPrice('.$options_js.');
//            </script>';
        $html .= "<div class='product-options-top'>";
//        $html .= $js->toHtml();
//        $html .= $options->renderView();

        if ($product->getTypeId() == 'configurable'){
            try{
                $priceRender = $this->_layout->createBlock(
                    'Magento\Framework\Pricing\Render',
                    'product.price.render.default',
                    ['data' => ['price_render_handle' => 'catalog_product_prices']]
                );

                $priceHtml = '';
                if ($priceRender) {
                    $priceHtml = $priceRender->render(
                        \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
                        $product,
                        [
                            'display_minimal_price'  => false,
                            'use_link_for_as_low_as' => true,
                            'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_VIEW
                        ]
                    );
                }

                $addtocartHtml = '';
                $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
                if($addtocartBlock){
                    $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
                }

                //$this->xlog($price);
                //$attr_renderers = $layout->createBlock('core/text_list', 'product.info.options.configurable.renderers');
                $configurable     = $layout->createBlock('Magento\ConfigurableProduct\Block\Product\View\Type\Configurable')
                    ->setData('area','frontend')
                    ->setProduct($product)
                    //->setChild('attr_renderers', $attr_renderers)
                    ->setTemplate('Magento_ConfigurableProduct::product/view/type/options/configurable.phtml')
                    ;

//                $html .= $configurable->renderView();
                $html .= $configurable->tohtml();

                //$html .= $configurable->fetchView('catalog/product/view/type/options/configurable.phtml');
                $html .= "</div>"; /* End from div product-options-top */

                if($params['is_gift'] == "true" || $params['action'] == 'configure'){
                    $html .= "<div class='product-options-bottom'>"
                                .$addtocartHtml.
//                                ."<div class=\"add-to-cart\">
//                                    <button type=\"submit\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
//                                </div>".
                                //$layout->createBlock('Magento\Catalog\Block\Product\View')->getPriceHtml($product).
//                                $priceHtml.
                            "</div>";
                }else{
                    $html .= "<div class='product-options-bottom'>"
                                .$addtocartHtml.
//                                ."<div class=\"add-to-cart\">
//                                    <label for=\"qty\">Qty:</label>
//                                    <input type=\"text\" name=\"qty\" id=\"product_qty\" maxlength=\"12\" value=\"$qty\" title=\"Qty\" class=\"input-text qty\">
//                                    <button type=\"submit\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
//                                </div>".
                                //$layout->createBlock('Magento\Catalog\Block\Product\View')->getPriceHtml($product)
//                                $priceHtml.
                            "</div>";
                }

            }catch(\Exception $e){

            }
        }
        else if($product->getTypeId() == 'simple'){
            $addtocartHtml = '';
            $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
            if($addtocartBlock){
                $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
            }
            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "<div class=\"add-to-cart\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    //$block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "<div class=\"add-to-cart\">
                        <label for=\"qty\">Qty:</label>
                        <input type=\"text\" name=\"qty\" id=\"product_qty\" maxlength=\"12\" value=\"$qty\" title=\"Qty\" class=\"input-text qty\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    //$block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }
        }
        else if($product->getTypeId() == 'bundle'){
            $this->_coreRegistry->register('product', $product);
            $this->_coreRegistry->register('current_product', $product);
            // Get the product and the product's options - In this case, the options are associated products attached to a bundled parent product

//            $productOptionIds = $product->getTypeInstance(true)->getChildrenIds($product->getId());

//            foreach ($productOptionIds as $optionId) {
//                $this->xlog($optionId);
                // The option ID is the ID of the product, let's load that associated product
//                $associatedProduct = $this->productRepository->load($optionId);
//                // Now lets get the custom options assigned to the associated product
//                foreach ($associatedProduct->getProductOptionsCollection() as $option) {
//
//                    // Now we get the block in the layout responsible for rendering, you'll see this declared in catalog.xml most likely.
//                    $block = $layout->getBlock('product.info.options');
//                    // Assuming the type of option we're using has a renderer added, we can then just get the HTML
//                    $this->xlog($block->getOptionHtml($option));
//                }
//            }
            $addtocartHtml = '';
            $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
            if($addtocartBlock){
                $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
            }
            //Mage::register('current_product', $product);
//            $product_price = $layout->createBlock('bundle/catalog_product_price')->setProduct($product)->setTemplate('bundle/catalog/product/view/price.phtml');
//            $tierprices = $layout->createBlock('bundle/catalog_product_view')->setProduct($product)->setTemplate('bundle/catalog/product/view/tierprices.phtml');
//            $extrahind  = $layout->createBlock('cataloginventory/qtyincrements')->setTemplate('cataloginventory/qtyincrements.phtml');

            $bundle = $layout->createBlock('Magento\Bundle\Block\Catalog\Product\View\Type\Bundle')
                ->setTemplate('Magento_Bundle::catalog/product/view/type/bundle/options.phtml');
            $bundle->setChild('select', $layout->createBlock(
                'Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option\Select',
                'product.info.bundle.options.select'
            )->setTemplate('MW_FreeGift::bundle/catalog/product/view/type/bundle/option/select.phtml'));
            $bundle->setChild('multi', $layout->createBlock(
                'Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option\Multi',
                'product.info.bundle.options.multi'
            )->setTemplate('MW_FreeGift::bundle/catalog/product/view/type/bundle/option/multi.phtml'));
            $bundle->setChild('radio', $layout->createBlock(
                'Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option\Radio',
                'product.info.bundle.options.radio'
            )->setTemplate('MW_FreeGift::bundle/catalog/product/view/type/bundle/option/radio.phtml'));
            $bundle->setChild('checkbox', $layout->createBlock(
                'Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option\Checkbox',
                'product.info.bundle.options.checkbox'
            )->setTemplate('MW_FreeGift::bundle/catalog/product/view/type/bundle/option/checkbox.phtml'));

//            $bundle->addRenderer('select', 'bundle/catalog_product_view_type_bundle_option_select');
//            $bundle->addRenderer('multi', 'bundle/catalog_product_view_type_bundle_option_multi');
//            $bundle->addRenderer('radio', 'bundle/catalog_product_view_type_bundle_option_radio');
//            $bundle->addRenderer('checkbox', 'bundle/catalog_product_view_type_bundle_option_checkbox');

//            if(Mage::helper('freegift/version')->isMageEnterprise()){
//                $bundle_type_template = 'mw_freegift/bundle/catalog/product/view/type/bundle.phtml';
//            }else{
//                $bundle_type_template = 'bundle/catalog/product/view/type/bundle.phtml';
//            }
            //$bundlejs_custom = $block->createBlock('bundle/catalog_product_view_type_bundle')->setProduct($product)->setTemplate($bundle_type_template);

            $html .= $bundle->toHtml();
            $html .= "</div>"; /* End from div product-options-top */

            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    //$bundlejs_custom->renderView().
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    //$bundlejs_custom->renderView().
                    "</div>";
            }

        }
        else if($product->getTypeId() == 'downloadable'){

            $addtocartHtml = '';
            $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
            if($addtocartBlock){
                $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
            }
            $downloadable     = $this->_layout->createBlock('Magento\Downloadable\Block\Catalog\Product\Links')
                ->setData('area','frontend')
                ->setProduct($product)
                ->setTemplate('MW_FreeGift::downloadable/catalog/product/links.phtml')
            ;

            $downloadableData     = $this->_layout->createBlock('Magento\Downloadable\Block\Catalog\Product\View\Type')
                ->setData('area','frontend')
                ->setProduct($product)
                ->setTemplate('Magento_Downloadable::catalog/product/type.phtml')
            ;
            $html .= $downloadable->tohtml();
            $html .= $downloadableData->tohtml();
            $html .= "</div>"; /* End from div product-options-top */

            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    //$priceHtml.
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    //$priceHtml.
                    "</div>";
            }
        }
        else if($product->getTypeId() == 'grouped'){
//            Mage::register('current_product', $product);
            $this->_coreRegistry->register('current_product', $product);

            $addtocartHtml = '';
            $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
            if($addtocartBlock){
                $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
            }

//            $product_type_data_extra = $layout->createBlock('Magento\Framework\View\Element\Text\ListText');
//            if (version_compare(Mage::getVersion(), '1.4.0.1', '>')) {
//                $reference_product_type_data_extra = $block->createBlock('cataloginventory/stockqty_type_grouped')->setTemplate('cataloginventory/stockqty/composite.phtml');
//                $product_type_data_extra->append($reference_product_type_data_extra);
//            }

            $grouped   = $layout->createBlock('Magento\GroupedProduct\Block\Product\View\Type\Grouped')
                ->setProduct($product)
                ->setTemplate('Magento_GroupedProduct::product/view/type/grouped.phtml');

            $html .= $grouped->tohtml();
            $html .= "</div>"; /* End from div product-options-top */
            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    "</div>";
            }
        }
        else{

            $addtocartHtml = '';
            $addtocartBlock = $layout->getBlock('product.info.addtocart')->setTemplate('MW_FreeGift::product/view/addtocart.phtml');
            if($addtocartBlock){
                $addtocartHtml = $addtocartBlock->setProductId($product->getId())->toHtml();
            }

            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    //$block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>"
                    .$addtocartHtml.
                    //$block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }
        }

        $this->getResponse()->setBody($html);
        //echo $html;
    }

    function xlog($message = 'null'){
        if(gettype($message) == 'string'){
        }else{
            $message = serialize($message);
        }

        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($message)
        ;
    }
}