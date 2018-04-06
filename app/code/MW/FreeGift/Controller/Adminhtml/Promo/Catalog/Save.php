<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

use Magento\Framework\Exception\LocalizedException;


use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
//use MW\FreeGift\Model\RuleFactory;

class Save extends \MW\FreeGift\Controller\Adminhtml\Promo\Catalog
{

    /**
//     * @var RuleFactory
     */
//    protected $ruleFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Date $dateFilter
//     * @param RuleFactory $ruleFactory
     */
//    public function __construct(
//        Context $context,
//        Registry $coreRegistry,
//        Date $dateFilter,
//        RuleFactory $ruleFactory
//    ) {
//        $this->ruleFactory = $ruleFactory;
//        parent::__construct($context, $coreRegistry, $dateFilter);
//    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            $model = $this->ruleFactory->create(); //$this->_objectManager->create('MW\FreeGift\Model\Rule');
            try {
//                $this->_eventManager->dispatch(
//                    'adminhtml_controller_catalogrule_prepare_save',
//                    ['request' => $this->getRequest()]
//                );
                $data = $this->getRequest()->getPostValue();
                if( isset($data['rule_information']) ){
                    $rule_information = ( isset($data['rule_information']) ? $data['rule_information'] : []);
                    unset($data['rule_information']);
                    $data = array_merge($rule_information,$data);
                }

                $inputFilter = new \Zend_Filter_Input(
                    ['from_date' => $this->_dateFilter, 'to_date' => $this->_dateFilter],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();

                $id = ( isset($data['rule_id']) ? (int) $data['rule_id'] : null);
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new LocalizedException(__('Wrong rule specified.'));
                    }
                }else{
                    unset($data['rule_id']);
                }

                $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));

                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $this->messageManager->addError($errorMessage);
                    }
                    $this->_getSession()->setPageData($data);
                    $this->_redirect('mw_freegift/*/edit', ['id' => $model->getId()]);
                    return;
                }

                /* [bX-gY] */
                if(isset($data['buy_x'])){
                    $custom_cdn['buy_x_get_y']['bx'] = ( isset($data['buy_x']) ? (int)$data['buy_x'] : 1);
                    $custom_cdn['buy_x_get_y']['gy'] = ( isset($data['get_y']) ? (int)$data['get_y'] : 1);
                    $custom_cdn = serialize($custom_cdn);
                    $data['condition_customized'] = $custom_cdn;
                }
                /* [bX-gY] */


                if( isset($data['rule']) ){
                    $data['conditions'] = ( isset($data['rule']['conditions']) ? $data['rule']['conditions'] : []);
                    unset($data['rule']);
                }

//                if(isset($data['product_ids']) && is_array($data['gift_product_ids'])){
                    $data['gift_product_ids'] = str_replace("&",",",$data['product_ids']);
//                }

                $model->loadPost($data);
                //$model->setData('condition_customized', $custom_cdn);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($model->getData());

                $model->save();

                $this->messageManager->addSuccess(__('You saved the rule.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData(false);
                if ($this->getRequest()->getParam('auto_apply')) {
                    $this->getRequest()->setParam('rule_id', $model->getId());
                    $this->_forward('applyRules');
                } else {
                    $this->_objectManager->create('MW\FreeGift\Model\Flag')->loadSelf()->setState(1)->save();
                    if ($this->getRequest()->getParam('back')) {
                        $this->_redirect('mw_freegift/*/edit', ['id' => $model->getId()]);
                        return;
                    }
                    $this->_redirect('mw_freegift/*/');
                }
                return;
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_objectManager->get('Psr\Log\LoggerInterface')->debug($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the rule data. Please review the error log.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_objectManager->get('Psr\Log\LoggerInterface')->debug($e->getMessage());
                $this->_redirect('mw_freegift/*/edit', ['id' => $this->getRequest()->getParam('rule_id')]);
                return;
            }
        }
        $this->_redirect('mw_freegift/*/');
    }

}
