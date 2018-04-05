<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;

use Magento\Framework\Exception\LocalizedException;

class Save extends \MW\FreeGift\Controller\Adminhtml\Promo\Catalog
{
    /**
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                $model = $this->_objectManager->create('MW\FreeGift\Model\Rule');
                $this->_eventManager->dispatch(
                    'adminhtml_controller_catalogrule_prepare_save',
                    ['request' => $this->getRequest()]
                );
                $data = $this->getRequest()->getPostValue();

                $inputFilter = new \Zend_Filter_Input(
                    ['from_date' => $this->_dateFilter, 'to_date' => $this->_dateFilter],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();
                $data['name'] = $data['rule_name'];
                unset($data['rule_name']);

                $id = $this->getRequest()->getParam('rule_id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new LocalizedException(__('Wrong rule specified.'));
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

                /* Add new feature Buy X get Y - 17/12/13 */
                $custom_cdn['buy_x_get_y']['bx'] = ( $data['buy_x'] ? (int)$data['buy_x'] : 1);
                $custom_cdn['buy_x_get_y']['gy'] = ( $data['get_y'] ? (int)$data['get_y'] : 1);
                $custom_cdn = serialize($custom_cdn);
                $data['condition_customized'] = $custom_cdn;
                /* [bX-gY]*/

                $data['conditions'] = $data['rule']['conditions'];
                unset($data['rule']);

                if(isset($data['gift_product_ids']) && is_array($data['gift_product_ids'])){
                    $data['gift_product_ids'] = implode(',', $data['gift_product_ids']);
                }

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
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the rule data. Please review the error log.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('mw_freegift/*/edit', ['id' => $this->getRequest()->getParam('rule_id')]);
                return;
            }
        }
        $this->_redirect('mw_freegift/*/');
    }

}
