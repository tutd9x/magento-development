<?php
namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

use MW\FreeGift\Controller\Adminhtml\Promo\Quote;
use MW\FreeGift\Model\SalesRuleFactory;



class Save extends Quote
{
    /**
     * Promo quote save action
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                /** @var $model \MW\FreeGift\Model\SalesRule */
                $model = $this->salesruleFactory->create();
                //$model = $this->_objectManager->create('MW\FreeGift\Model\SalesRule');
//                $this->_eventManager->dispatch(
//                    'adminhtml_controller_salesrule_prepare_save',
//                    ['request' => $this->getRequest()]
//                );
                $data = $this->getRequest()->getPostValue();
                if( isset($data['rule_information']) ) {
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
                $id = $this->getRequest()->getParam('rule_id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong rule is specified.'));
                    }
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

                if (isset(
                    $data['simple_action']
                ) && $data['simple_action'] == 'by_percent' && isset(
                    $data['discount_amount']
                )
                ) {
                    $data['discount_amount'] = min(100, $data['discount_amount']);
                }
                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }
                if (isset($data['rule']['actions'])) {
                    $data['actions'] = $data['rule']['actions'];
                }
                unset($data['rule']);

                if(isset($data['product_ids']) && $data['product_ids'] != ""){

                    $selected_product_ids = str_replace("&on","",$data['product_ids']);
                    $selected_product_ids = str_replace("&",",",$selected_product_ids);
                    $selected_product_ids = rtrim($selected_product_ids,",");

                    $data['gift_product_ids'] = $selected_product_ids;
                    unset($data['product_ids']);
                }
                
                if (isset($data['promotion_banner']) && is_array($data['promotion_banner'])) {
                    if (!empty($data['promotion_banner']['delete'])) {
                        $data['promotion_banner'] = null;
                    } else {
                        if (isset($data['promotion_banner'][0]['name']) && isset($data['promotion_banner'][0]['tmp_name'])) {
                            $data['promotion_banner'] = $data['promotion_banner'][0]['name'];
                        } else {
                            unset($data['promotion_banner']);
                        }
                    }
                } else {
                    $data['promotion_banner'] = null;
                }

                $model->loadPost($data);

                $useAutoGeneration = (int)(
                    !empty($data['use_auto_generation']) && $data['use_auto_generation'] !== 'false'
                );
                $model->setUseAutoGeneration($useAutoGeneration);
                $model->save();
                $this->messageManager->addSuccess(__('You saved the rule.'));

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('rule_id');
                if (!empty($id)) {
                    $this->_redirect('*/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('*/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the rule data. Please review the error log.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('rule_id')]);
                return;
            }
        }
        $this->_redirect('*/*/');
    }

}
