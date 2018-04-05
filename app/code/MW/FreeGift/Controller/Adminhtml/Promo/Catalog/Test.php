<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MW\FreeGift\Controller\Adminhtml\Promo\Catalog;
use Magento\Framework\Exception\LocalizedException;

class Test extends \Magento\Framework\App\Action\Action
{
    /**
     * @return void
     */
    public function execute()
    {
        echo 123; exit;
        $data = [
//            'rule_id' => 1,
            'name' => 'Rule 4',
            'website_ids' => [1],
//            'customer_group_ids' => [1,2]
        ];

//        try {
            $model = $this->_objectManager->create('MW\FreeGift\Model\Rule');

            $id = (isset($data['rule_id']) ? $data['rule_id'] : null);
            if ($id) {
                $model->load($id);
                if ($id != $model->getId()) {
                    throw new LocalizedException(__('Wrong rule specified.'));
                }
            }
            $model->loadPost($data);
//            echo "<pre>";
//            print_r(get_class_methods($model));
//            print_r($model->getWebsiteIds());
//            exit;

            //$this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($model->getData());

            $model->save();

            $this->messageManager->addSuccess(__('You saved the rule.'));
//            $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData(false);

            return;
//        } catch (LocalizedException $e) {
//            $this->messageManager->addError($e->getMessage());
//        } catch (\Exception $e) {
//            $this->messageManager->addError(
//                __('Something went wrong while saving the rule data. Please review the error log.')
//            );
//            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
//            $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
//            $this->_redirect('mw_freegift/*/edit', ['id' => $this->getRequest()->getParam('rule_id')]);
//            return;
//        }
    }
}
