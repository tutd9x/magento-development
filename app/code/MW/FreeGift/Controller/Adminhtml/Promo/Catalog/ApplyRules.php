<?php

namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

use MW\FreeGift\Model\Rule\Job;
use Magento\Framework\Controller\ResultFactory;

class ApplyRules extends \MW\FreeGift\Controller\Adminhtml\Promo\Catalog
{
    /**
     * Apply all active catalog price rules
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $errorMessage = __('We can\'t apply the rules.');
        try {
            /** @var Job $ruleJob */
            $ruleJob = $this->_objectManager->get('MW\FreeGift\Model\Rule\Job');
//            $rule_id = $this->getRequest()->getParam('rule_id');
//            if($rule_id){
//                $ruleJob->applyById($rule_id);
//            }else{
                $ruleJob->applyAll();
//            }

            if ($ruleJob->hasSuccess()) {
                $this->messageManager->addSuccess($ruleJob->getSuccess());
//                if(!$rule_id) {
                    $this->_objectManager->create('MW\FreeGift\Model\Flag')->loadSelf()->setState(0)->save();
//                }
            } elseif ($ruleJob->hasError()) {
                $this->messageManager->addError($errorMessage . ' ' . $ruleJob->getError());
            }
        } catch (\Exception $e) {
            $this->_objectManager->create('Psr\Log\LoggerInterface')->critical($e);
            $this->messageManager->addError($errorMessage);
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('mw_freegift/*');
    }
}
