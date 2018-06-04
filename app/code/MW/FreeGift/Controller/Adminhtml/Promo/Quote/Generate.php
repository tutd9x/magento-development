<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

use MW\FreeGift\Controller\Adminhtml\Promo\Quote;
use MW\FreeGift\Model\SalesRuleFactory;

class Generate extends Quote
{
    /**
     * @var \MW\FreeGift\Api\Data\CouponGenerationSpecInterfaceFactory
     */
    private $generationSpecFactory;

    /**
     * @var \MW\FreeGift\Model\Service\CouponManagementService
     */
    private $couponManagementService;

    /**
     * Generate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     * @param \MW\FreeGift\Model\Service\CouponManagementService|null $couponManagementService
     * @param \MW\FreeGift\Api\Data\CouponGenerationSpecInterfaceFactory|null $generationSpecFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        SalesRuleFactory $salesruleFactory,
        \MW\FreeGift\Model\Service\CouponManagementService $couponManagementService = null,
        \MW\FreeGift\Api\Data\CouponGenerationSpecInterfaceFactory $generationSpecFactory = null
    ) {
        parent::__construct($context, $coreRegistry, $fileFactory, $dateFilter, $salesruleFactory);
        $this->generationSpecFactory = $generationSpecFactory ?:
            $this->_objectManager->get(\MW\FreeGift\Api\Data\CouponGenerationSpecInterfaceFactory::class);
        $this->couponManagementService = $couponManagementService ?:
            $this->_objectManager->get(\MW\FreeGift\Model\Service\CouponManagementService::class);
    }

    /**
     * Generate Coupons action
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_forward('noroute');

            return;
        }
        $result = [];
        $this->_initRule();

        /** @var $rule \Magento\SalesRule\Model\Rule */
        $rule = $this->_coreRegistry->registry('current_promo_sales_rule');

        if (!$rule->getId()) {
            $result['error'] = __('Rule is not defined');
        } else {
            try {
                $data = $this->getRequest()->getParams();
                if (!empty($data['to_date'])) {
                    $inputFilter = new \Zend_Filter_Input(['to_date' => $this->_dateFilter], [], $data);
                    $data = $inputFilter->getUnescaped();
                }

                $data = $this->convertCouponSpecData($data);
                $couponSpec = $this->generationSpecFactory->create(['data' => $data]);
                $couponCodes = $this->couponManagementService->generate($couponSpec);
                $generated = count($couponCodes);
                $this->messageManager->addSuccess(__('%1 coupon(s) have been generated.', $generated));
                $this->_view->getLayout()->initMessages();
                $result['messages'] = $this->_view->getLayout()->getMessagesBlock()->getGroupedHtml();
            } catch (\Magento\Framework\Exception\InputException $inputException) {
                $result['error'] = __('Invalid data provided');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $result['error'] = $e->getMessage();
            } catch (\Exception $e) {
                $result['error'] = __(
                    'Something went wrong while generating coupons. Please review the log and try again.'
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            }
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }

    /**
     * We should map old values to new one
     * We need to do this, as new service with another key names was added
     *
     * @param array $data
     * @return array
     */
    private function convertCouponSpecData(array $data)
    {
        $data['quantity'] = $data['qty'];

        return $data;
    }
}
