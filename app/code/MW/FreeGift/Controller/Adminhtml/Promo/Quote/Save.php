<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Controller\Adminhtml\Promo\Quote;

class Save extends \MW\FreeGift\Controller\Adminhtml\Promo\Quote
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
                /** @var $model \Magento\SalesRule\Model\Rule */
                $model = $this->_objectManager->create('MW\FreeGift\Model\SalesRule');
                $this->_eventManager->dispatch(
                    'adminhtml_controller_salesrule_prepare_save',
                    ['request' => $this->getRequest()]
                );

                $request = $this->getRequest();
                $data = $this->getRequest()->getPostValue();

                $inputFilter = new \Zend_Filter_Input(
                    ['from_date' => $this->_dateFilter, 'to_date' => $this->_dateFilter],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();
//                $data['name'] = $data['rule_name'];
//                unset($data['rule_name']);
                $id = $this->getRequest()->getParam('rule_id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong rule is specified.'));
                    }
                }

                $session = $this->_objectManager->get('Magento\Backend\Model\Session');

                $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));
                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $this->messageManager->addError($errorMessage);
                    }
                    $session->setPageData($data);
                    $this->_redirect('*/*/edit', ['id' => $model->getId()]);
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
                if(isset($data['gift_product_ids']) && is_array($data['gift_product_ids'])){
                    $data['gift_product_ids'] = implode(',', $data['gift_product_ids']);
                }

                //Upload image
                if ((isset($_FILES['promotion_banner']['name'])) and (file_exists($_FILES['promotion_banner']['tmp_name']))) {
                    try {
                        $uploader = new \Magento\Framework\File\Uploader($_FILES['promotion_banner']);
                        $uploader->setAllowedExtensions(['jpg','jpeg','gif','png']);
                        $uploader->setFilesDispersion(false);
                        $uploader->setAllowRenameFiles(false);
                        $uploader->save($this->_directory->getAbsolutePath('promotionbanner'),  md5($_FILES['promotion_banner']['name']).'.'.$uploader->getFileExtension());
                        $fileName = 'promotionbanner/'.$uploader->getUploadedFileName();
                        $data['promotion_banner'] = $fileName;

                    }catch (\Exception $e) {
                        $this->_logger->critical($e);
                    }

                } else {
                    if (isset($data['promotion_banner']['delete']) && $data['promotion_banner']['delete'] == 1) {
                        $data['promotion_banner'] = '';
                    } else {
                        unset($data['promotion_banner']);
                    }
                }

                if (!isset($data['number_of_free_gift'])) {
                    $data['number_of_free_gift'] = 1 ;
                }
                $model->loadPost($data);
                $model->setData($data);
                $useAutoGeneration = (int)(!empty($data['use_auto_generation']));
                $model->setUseAutoGeneration($useAutoGeneration);

                $session->setPageData($model->getData());

                $model->save();
                \Zend_Debug::dump("ddd");  die("awkwkss");
                $this->messageManager->addSuccess(__('You saved the rule.'));
                $session->setPageData(false);
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
                $data = $this->getRequest()->getPostValue();
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('rule_id')]);
                return;
            }
        }
        $this->_redirect('*/*/');
    }


    /**
     * Generate content to log file debug.log By Hattetek.Com
     *
     * @param  $message string|array
     * @return void
     */
    function xlog($message = 'null')
    {
        $log = print_r($message, true);
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Psr\Log\LoggerInterface')
            ->debug($log)
        ;
    }
}
